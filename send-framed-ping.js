import net from 'net';

const client = new net.Socket();
let respBuf = Buffer.alloc(0);
client.connect(6400, '127.0.0.1', () => {
  console.log('connected, sending framed ping');
  const msg = Buffer.from('ping', 'utf8');
  const len = Buffer.alloc(4);
  len.writeUInt32BE(msg.length, 0);
  client.write(Buffer.concat([len, msg]));
});

client.on('data', (data) => {
  respBuf = Buffer.concat([respBuf, data]);
  while (respBuf.length >= 4) {
    const l = respBuf.readUInt32BE(0);
    if (respBuf.length >= 4 + l) {
      const body = respBuf.slice(4, 4 + l).toString('utf8');
      console.log('Received framed response:', body);
      client.destroy();
      process.exit(0);
    } else break;
  }
});

client.on('error', (e) => {
  console.error('socket error:', e.message);
  process.exit(1);
});

setTimeout(() => {
  console.error('framed ping timeout');
  client.destroy();
  process.exit(1);
}, 10000);
