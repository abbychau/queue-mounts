package hooks

import (
	"bytes"
	"encoding/json"
	"fmt"
	"net/http"
	"time"

	"github.com/wind-c/comqtt/v2/mqtt"
	"github.com/wind-c/comqtt/v2/mqtt/packets"

	resources "queuemounts/resources"
)

type HttpWebHook struct {
	mqtt.HookBase
}

var dbWebHookTopicCache = make(map[string][]string)

func RenewCache() {
	db, err := resources.GetDB()
	if err != nil {
		fmt.Println("get db failed,", err)
		return
	}
	rows, err := db.Query("SELECT id, trigger_topic, url FROM webhooks")
	if err != nil {
		fmt.Println("query failed,", err)
		return
	}
	var tempTriggerCache = make(map[string][]string)
	for rows.Next() {
		// id, trigger_topic, url
		var id int
		var trigger_topic string
		var url string

		err = rows.Scan(&id, &trigger_topic, &url)
		if err != nil {
			fmt.Println("scan failed,", err)
			return
		}

		tempTriggerCache[trigger_topic] = append(tempTriggerCache[trigger_topic], url)
	}
	dbWebHookTopicCache = tempTriggerCache
}

// ID returns the ID of the hook.
func (h *HttpWebHook) ID() string {
	return "http-web-hook"
}

// Provides indicates which hook methods this hook provides.
func (h *HttpWebHook) Provides(b byte) bool {
	return bytes.Contains([]byte{
		// mqtt.OnRetainMessage,
		mqtt.OnPacketRead,
	}, []byte{b})
}

//	func (h *HttpWebHook) OnRetainMessage(cl *mqtt.Client, pk packets.Packet, r int64) {
//		fmt.Println("OnRetainMessage")
//		type RetainMessage struct {
//			Payload string `json:"payload"`
//		}
//		rm := RetainMessage{Payload: string(pk.Payload)}
//		rmJsonData, _ := json.Marshal(rm)
//		httpPost("https://webhook.site/11866631-b4d4-4c14-a4cb-cd5bf8c1b620", rmJsonData)
//	}

func (h *HttpWebHook) OnPacketRead(cl *mqtt.Client, pk packets.Packet) (packets.Packet, error) {
	if pk.TopicName == "" {
		return pk, nil
	}
	fmt.Println("OnPacketRead:" + pk.TopicName)

	// check if the topic is in the cache, if yes, send to url
	if _, ok := dbWebHookTopicCache[pk.TopicName]; ok {
		for _, url := range dbWebHookTopicCache[pk.TopicName] {
			type PacketRead struct {
				Payload string `json:"payload"`
				Topic   string `json:"topic"`
			}
			pr := PacketRead{Payload: string(pk.Payload), Topic: pk.TopicName}

			// check if the topic is in the cache
			prJsonData, _ := json.Marshal(pr)

			go httpPost(url, prJsonData)
		}
	}
	return pk, nil
}

func httpPost(url string, data []byte) {
	// fmt.Println("httpPost:" + url)
	// fmt.Println("data:" + string(data))
	client := http.Client{
		Timeout: 5 * time.Second,
	}

	_, err := client.Post(url, "application/json", bytes.NewBuffer(data))
	if err != nil {
		return
	}

}
