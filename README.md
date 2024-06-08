# Queue Mounts

A persistent MQTT server that with MySQL-based-Auth, ACL, boltDB-based persistence, Websockets support.

## Usage

```bash
mv auth-mysq.example.yml auth-mysql.yml
# Edit auth-mysql.yml
go run main.go --conf=auth-mysql.yml
```
