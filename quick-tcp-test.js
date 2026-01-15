import net from 'net';

const client = new net.Socket();
client.setTimeout(5000);
client.on('connect', () => {
  console.log('TCP connected');
  client.end();
});
client.on('error', (e) => {
  console.error('TCP error:', e.message);
  process.exit(1);
});
client.on('timeout', () => {
  console.error('TCP timeout');
  client.destroy();
  process.exit(1);
});
client.on('close', (hadError) => {
  if (!hadError) console.log('TCP closed');
  process.exit(0);
});

client.connect(6400, '127.0.0.1');
