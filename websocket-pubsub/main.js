const WebSocket = require('ws');
const http = require('http');
const url = require('url');

const server = http.createServer();
const wss = new WebSocket.Server({ noServer: true });

const clients = new Map();

server.on('upgrade', function upgrade(request, socket, head) {
  const pathname = url.parse(request.url).pathname;

  if (pathname === '/push' || pathname === '/points') {
    wss.handleUpgrade(request, socket, head, function done(ws) {
      wss.emit('connection', ws, request, pathname);
    });
  } else {
    socket.destroy();
  }
});

wss.on('connection', function connection(ws, request, pathname) {
  if (pathname === '/push') {
    ws.on('message', function incoming(message) {
      try {
        const data = JSON.parse(message);
        broadcastData(data);
      } catch (error) {
        console.error('Invalid JSON:', error);
      }
    });
  } else if (pathname === '/points') {
    ws.on('message', function incoming(message) {
      try {
        const subscription = JSON.parse(message);
        if (subscription.action === 'subscribe' && Array.isArray(subscription.id)) {
          clients.set(ws, subscription.id);
        } else if (subscription.action === 'unsubscribe') {
          clients.delete(ws);
        }
      } catch (error) {
        console.error('Invalid JSON:', error);
      }
    });
  }

  ws.on('close', function close() {
    clients.delete(ws);
  });
});

function broadcastData(data) {
  clients.forEach((subscribedIds, client) => {
    if (subscribedIds.includes(data.id)) {
      client.send(JSON.stringify(data));
    }
  });
}

const PORT = process.env.PORT || 3000;
server.listen(PORT, () => {
  console.log(`WebSocket server is running on port ${PORT}`);
});