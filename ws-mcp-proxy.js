import http from 'http';
import fs from 'fs';
import path from 'path';
import net from 'net';
import { WebSocketServer } from 'ws';

const HTTP_PORT = 8082;
const TCP_HOST = '127.0.0.1';
const TCP_PORT = 6400;

const server = http.createServer((req, res) => {
  const url = req.url.split('?')[0];
  if (url === '/' || url === '/web-mcp-client.html') {
    const file = path.join(process.cwd(), 'web-mcp-client.html');
    fs.createReadStream(file).pipe(res);
  } else {
    res.statusCode = 404;
    res.end('Not found');
  }
});

const wss = new WebSocketServer({ server });

function writeFramed(socket, obj) {
  const payload = Buffer.from(JSON.stringify(obj), 'utf8');
  const len = Buffer.alloc(4);
  len.writeUInt32BE(payload.length, 0);
  socket.write(Buffer.concat([len, payload]));
}

wss.on('connection', (ws) => {
  let tcp = new net.Socket();
  let respBuf = Buffer.alloc(0);
  let connected = false;

  tcp.connect(TCP_PORT, TCP_HOST, () => {
    connected = true;
    ws.send(JSON.stringify({ event: 'tcp_connected' }));
  });

  tcp.on('data', (data) => {
    respBuf = Buffer.concat([respBuf, data]);
    while (respBuf.length >= 4) {
      const l = respBuf.readUInt32BE(0);
      if (respBuf.length >= 4 + l) {
        const body = respBuf.slice(4, 4 + l).toString('utf8');
        respBuf = respBuf.slice(4 + l);
        try {
          const json = JSON.parse(body);
          ws.send(JSON.stringify({ event: 'unity_response', body: json }));
        } catch (e) {
          ws.send(JSON.stringify({ event: 'unity_response_text', body }));
        }
      } else break;
    }
  });

  tcp.on('error', (err) => {
    ws.send(JSON.stringify({ event: 'tcp_error', message: err.message }));
  });

  tcp.on('close', () => {
    ws.send(JSON.stringify({ event: 'tcp_closed' }));
  });

  ws.on('message', (message) => {
    // Expect JSON from browser
    let obj;
    try {
      obj = JSON.parse(message.toString());
    } catch (e) {
      ws.send(JSON.stringify({ event: 'invalid_json', message: message.toString() }));
      return;
    }

    if (!connected) {
      ws.send(JSON.stringify({ event: 'tcp_not_connected' }));
      return;
    }

    // Forward to Unity as length-prefixed JSON
    writeFramed(tcp, obj);
    ws.send(JSON.stringify({ event: 'sent_to_unity', body: obj }));
  });

  ws.on('close', () => {
    tcp.destroy();
  });
});

server.listen(HTTP_PORT, () => {
  console.log(`Web UI + WS proxy listening at http://localhost:${HTTP_PORT}/web-mcp-client.html`);
});
