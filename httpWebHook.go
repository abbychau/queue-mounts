package main

import (
	"bytes"
	"encoding/json"
	"fmt"
	"net/http"

	"github.com/wind-c/comqtt/v2/mqtt"
	"github.com/wind-c/comqtt/v2/mqtt/packets"
)

type HttpWebHook struct {
	mqtt.HookBase
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
	fmt.Println("OnPacketRead")
	if pk.TopicName == "" {
		return pk, nil
	}
	type PacketRead struct {
		Payload string `json:"payload"`
		Topic   string `json:"topic"`
	}
	pr := PacketRead{Payload: string(pk.Payload), Topic: pk.TopicName}
	prJsonData, _ := json.Marshal(pr)
	httpPost("https://webhook.site/11866631-b4d4-4c14-a4cb-cd5bf8c1b620", prJsonData)
	return pk, nil
}
func httpPost(url string, data []byte) {
	//sendData := []byte(data)
	_, err := http.Post(url, "application/json", bytes.NewBuffer(data))
	if err != nil {
		return
	}

}
