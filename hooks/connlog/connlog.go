package hooks

import (
	"bytes"
	"encoding/json"
	"net"
	"net/http"
	"strconv"
	"strings"
	"sync"
	"time"

	"github.com/wind-c/comqtt/v2/mqtt"
	"github.com/wind-c/comqtt/v2/mqtt/packets"
)

// ConnEvent represents a single connection-related event.
type ConnEvent struct {
	Timestamp int64  `json:"timestamp"`
	ClientID  string `json:"client_id"`
	Event     string `json:"event"` // connect | disconnect

	// Connection metadata
	Remote   string `json:"remote,omitempty"`
	Listener string `json:"listener,omitempty"`

	// From CONNECT packet (when available)
	Keepalive uint16 `json:"keepalive,omitempty"`
	Clean     bool   `json:"clean"`
	Username  string `json:"username,omitempty"`

	// Disconnect details
	Expire         bool   `json:"expire,omitempty"`
	StopCause      string `json:"stop_cause,omitempty"`
	Error          string `json:"error,omitempty"`
	HeartbeatTimed bool   `json:"heartbeat_timeout,omitempty"`
}

// ring buffer for connection events (LRU-like, fixed capacity)
type connRing struct {
	mu       sync.RWMutex
	items    []ConnEvent
	idx      int
	count    int
	capacity int
}

func newConnRing(capacity int) *connRing {
	return &connRing{items: make([]ConnEvent, capacity), capacity: capacity}
}

func (r *connRing) append(ev ConnEvent) {
	r.mu.Lock()
	r.items[r.idx] = ev
	r.idx = (r.idx + 1) % r.capacity
	if r.count < r.capacity {
		r.count++
	}
	r.mu.Unlock()
}

// queryByClient returns up to limit newest events for the specified client id.
func (r *connRing) queryByClient(clientID string, limit int) []ConnEvent {
	r.mu.RLock()
	defer r.mu.RUnlock()

	if limit <= 0 {
		limit = 100
	}
	out := make([]ConnEvent, 0, limit)
	if r.count == 0 {
		return out
	}
	// start from the last inserted index - 1
	i := (r.idx - 1 + r.capacity) % r.capacity
	scanned := 0
	for scanned < r.count && len(out) < limit {
		ev := r.items[i]
		if ev.ClientID == clientID {
			out = append(out, ev)
		}
		if i == 0 {
			i = r.capacity - 1
		} else {
			i--
		}
		scanned++
	}
	return out
}

// ConnLogHook captures client connect/disconnect events
type ConnLogHook struct {
	mqtt.HookBase
	buf *connRing
}

func NewConnLogHook(capacity int) *ConnLogHook {
	if capacity <= 0 {
		capacity = 100000
	}
	return &ConnLogHook{buf: newConnRing(capacity)}
}

// ID returns the ID of the hook.
func (h *ConnLogHook) ID() string { return "connlog-hook" }

// Provides indicates which hook methods this hook provides.
func (h *ConnLogHook) Provides(b byte) bool {
	return bytes.Contains([]byte{
		mqtt.OnConnect,
		mqtt.OnSessionEstablished,
		mqtt.OnDisconnect,
	}, []byte{b})
}

// OnConnect logs an initial connect attempt (pre-session established).
func (h *ConnLogHook) OnConnect(cl *mqtt.Client, pk packets.Packet) error {
	if h.buf == nil || cl == nil {
		return nil
	}
	ev := ConnEvent{
		Timestamp: time.Now().Unix(),
		ClientID:  cl.ID,
		Event:     "connect",
		Remote:    cl.Net.Remote,
		Listener:  cl.Net.Listener,
		Clean:     cl.Properties.Clean,
		Username:  string(cl.Properties.Username),
	}
	h.buf.append(ev)
	return nil
}

// OnSessionEstablished logs a connect event.
func (h *ConnLogHook) OnSessionEstablished(cl *mqtt.Client, pk packets.Packet) {
	if h.buf == nil || cl == nil {
		return
	}
	ev := ConnEvent{
		Timestamp: time.Now().Unix(),
		ClientID:  cl.ID,
		Event:     "connect",
		Remote:    cl.Net.Remote,
		Listener:  cl.Net.Listener,
		Clean:     cl.Properties.Clean,
		Username:  string(cl.Properties.Username),
	}
	h.buf.append(ev)
}

// OnDisconnect logs a disconnect event with reason.
func (h *ConnLogHook) OnDisconnect(cl *mqtt.Client, err error, expire bool) {
	if h.buf == nil || cl == nil {
		return
	}
	stop := cl.StopCause()
	ev := ConnEvent{
		Timestamp: time.Now().Unix(),
		ClientID:  cl.ID,
		Event:     "disconnect",
		Remote:    cl.Net.Remote,
		Listener:  cl.Net.Listener,
		Clean:     cl.Properties.Clean,
		Username:  string(cl.Properties.Username),
		Expire:    expire,
	}
	if stop != nil {
		ev.StopCause = stop.Error()
	}
	if err != nil {
		ev.Error = err.Error()
		if ne, ok := err.(net.Error); ok {
			if ne.Timeout() {
				ev.HeartbeatTimed = true
			}
		} else if strings.Contains(strings.ToLower(err.Error()), "timeout") {
			ev.HeartbeatTimed = true
		}
	}
	h.buf.append(ev)
}

// HTTP handler to serve connection logs; intended to be registered under the stats HTTP listener.
func (h *ConnLogHook) HTTPHandler() http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		clientID := r.URL.Query().Get("client_id")
		if clientID == "" {
			http.Error(w, "client_id is required", http.StatusBadRequest)
			return
		}
		limit := 100
		if l := r.URL.Query().Get("limit"); l != "" {
			if n, err := strconv.Atoi(l); err == nil {
				if n > 0 && n <= 100000 {
					limit = n
				}
			}
		}
		res := h.buf.queryByClient(clientID, limit)
		w.Header().Set("Content-Type", "application/json")
		enc := json.NewEncoder(w)
		_ = enc.Encode(res)
	}
}

// HTTPClients returns a simple list of known client IDs with last-seen timestamp.
func (h *ConnLogHook) HTTPClients() http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		type Item struct {
			ClientID  string `json:"client_id"`
			LastSeen  int64  `json:"last_seen"`
			LastEvent string `json:"last_event"`
		}
		m := make(map[string]Item)
		// scan buffer newest-first and keep the most recent event per client
		h.buf.mu.RLock()
		if h.buf.count > 0 {
			i := (h.buf.idx - 1 + h.buf.capacity) % h.buf.capacity
			scanned := 0
			for scanned < h.buf.count {
				ev := h.buf.items[i]
				if ev.ClientID != "" {
					if _, ok := m[ev.ClientID]; !ok {
						m[ev.ClientID] = Item{ClientID: ev.ClientID, LastSeen: ev.Timestamp, LastEvent: ev.Event}
					}
				}
				if i == 0 {
					i = h.buf.capacity - 1
				} else {
					i--
				}
				scanned++
			}
		}
		h.buf.mu.RUnlock()
		// convert map to slice
		out := make([]Item, 0, len(m))
		for _, v := range m {
			out = append(out, v)
		}
		w.Header().Set("Content-Type", "application/json")
		enc := json.NewEncoder(w)
		_ = enc.Encode(out)
	}
}
