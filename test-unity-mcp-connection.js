import { sendCommand } from './unity-mcp-client.js';

async function main() {
    try {
            console.log('Attempting to send ping to Unity MCP on 127.0.0.1:6400');
            const res = await sendCommand({ type: 'ping' });
        console.log('MCP response:', res);
        process.exit(0);
    } catch (err) {
        console.error('MCP connection error:', err.message || err);
        process.exit(1);
    }
}

main();
