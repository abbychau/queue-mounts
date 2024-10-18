package hooks

import (
	"bytes"
	"fmt"

	"github.com/wind-c/comqtt/v2/mqtt"
	"github.com/wind-c/comqtt/v2/mqtt/packets"

	resources "queuemounts/resources"
)

type AclHook struct {
	mqtt.HookBase
}

// struct for acl
type Acl struct {
	Username string `json:"username"`
	Topic    string `json:"topic"`
	Access   int    `json:"access"`
}

// ID returns the ID of the hook.
func (h *AclHook) ID() string {
	return "acl-hook"
}

var dbAclCache = make(map[string][]Acl)

func RenewCache() {
	db, err := resources.GetDB()
	if err != nil {
		fmt.Println("get db failed,", err)
		return
	}
	rows, err := db.Query("SELECT username,topic,access FROM acl")
	if err != nil {
		fmt.Println("query failed,", err)
		return
	}
	var tempdbAclCache = make(map[string][]Acl)
	for rows.Next() {
		// id, topic, url
		var username string
		var topic string
		var access int

		err = rows.Scan(&username, &topic, &access)
		if err != nil {
			fmt.Println("scan failed,", err)
			return
		}

		tempdbAclCache[topic] = append(tempdbAclCache[topic], Acl{
			Username: username,
			Topic:    topic,
			Access:   access,
		})
	}
	dbAclCache = tempdbAclCache
}

// Provides indicates which hook methods this hook provides.
func (h *AclHook) Provides(b byte) bool {
	return bytes.Contains([]byte{
		// mqtt.OnRetainMessage,
		mqtt.OnACLCheck,
		mqtt.OnPublish,
	}, []byte{b})
}

// OnACLCheck is called when a user attempts to subscribe or publish to a topic.
func (h *AclHook) OnACLCheck(cl *mqtt.Client, topic string, write bool) bool {
	aclItem := dbAclCache[topic]
	if len(aclItem) == 0 {
		return false
	}
	for _, item := range aclItem {
		if item.Username == string(cl.Properties.Username) {
			if write {
				if item.Access == 1 || item.Access == 3 {
					return true
				}
				return false
			} else {
				return true
			}
		}
	}
	return false
}

// OnPublish is called when a client publishes a message.
func (h *AclHook) OnPublish(cl *mqtt.Client, pk packets.Packet) (pkx packets.Packet, err error) {
	if pkx.TopicName == "$SYS" {
		if cl.Properties.Username != nil {
			pk := packets.Packet{}
			return pk, nil
		}
	}
	return pkx, nil
}
