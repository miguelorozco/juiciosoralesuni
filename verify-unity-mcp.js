#!/usr/bin/env node
/**
 * Script de verificación completa de la conexión MCP con Unity
 */

import net from 'net';

const UNITY_HOST = '127.0.0.1';
const UNITY_PORT = 6400;

console.log('🔍 Verificando conexión MCP con Unity...\n');

// Verificar si el puerto está escuchando
function checkPort() {
    return new Promise((resolve) => {
        const testSocket = new net.Socket();
        
        testSocket.setTimeout(2000);
        
        testSocket.on('connect', () => {
            console.log('✅ Puerto 6400 está escuchando y acepta conexiones');
            testSocket.destroy();
            resolve(true);
        });
        
        testSocket.on('timeout', () => {
            console.log('❌ Timeout: El puerto no responde');
            testSocket.destroy();
            resolve(false);
        });
        
        testSocket.on('error', (err) => {
            if (err.code === 'ECONNREFUSED') {
                console.log('❌ Conexión rechazada: El servidor Unity MCP no está activo');
            } else {
                console.log(`❌ Error de conexión: ${err.message}`);
            }
            resolve(false);
        });
        
        testSocket.connect(UNITY_PORT, UNITY_HOST);
    });
}

// Escribe un UInt64 en formato big-endian
function writeUInt64BE(buffer, value, offset) {
    buffer[offset] = Number((BigInt(value) >> 56n) & 0xffn);
    buffer[offset + 1] = Number((BigInt(value) >> 48n) & 0xffn);
    buffer[offset + 2] = Number((BigInt(value) >> 40n) & 0xffn);
    buffer[offset + 3] = Number((BigInt(value) >> 32n) & 0xffn);
    buffer[offset + 4] = Number((BigInt(value) >> 24n) & 0xffn);
    buffer[offset + 5] = Number((BigInt(value) >> 16n) & 0xffn);
    buffer[offset + 6] = Number((BigInt(value) >> 8n) & 0xffn);
    buffer[offset + 7] = Number(BigInt(value) & 0xffn);
}

// Lee un UInt64 en formato big-endian
function readUInt64BE(buffer, offset) {
    return Number(
        (BigInt(buffer[offset]) << 56n) |
        (BigInt(buffer[offset + 1]) << 48n) |
        (BigInt(buffer[offset + 2]) << 40n) |
        (BigInt(buffer[offset + 3]) << 32n) |
        (BigInt(buffer[offset + 4]) << 24n) |
        (BigInt(buffer[offset + 5]) << 16n) |
        (BigInt(buffer[offset + 6]) << 8n) |
        BigInt(buffer[offset + 7])
    );
}

// Enviar comando ping
function sendPing() {
    return new Promise((resolve, reject) => {
        const client = new net.Socket();
        let responseBuffer = Buffer.alloc(0);
        let resolved = false;
        let handshakeReceived = false;
        
        client.setTimeout(5000);
        
        client.connect(UNITY_PORT, UNITY_HOST, () => {
            console.log('📡 Conectado al servidor Unity MCP');
            // Esperar handshake antes de enviar
        });
        
        client.on('data', (data) => {
            responseBuffer = Buffer.concat([responseBuffer, data]);
            
            // Primero, leer el handshake
            if (!handshakeReceived) {
                const newlineIndex = responseBuffer.indexOf('\n');
                if (newlineIndex !== -1) {
                    const handshake = responseBuffer.slice(0, newlineIndex + 1).toString('ascii');
                    responseBuffer = responseBuffer.slice(newlineIndex + 1);
                    handshakeReceived = true;
                    console.log('✅ Handshake recibido:', handshake.trim());
                    
                    // Enviar ping
                    const message = Buffer.from('ping', 'utf8');
                    const length = Buffer.alloc(8);
                    writeUInt64BE(length, message.length, 0);
                    
                    client.write(Buffer.concat([length, message]));
                    console.log('📤 Enviado: ping');
                }
                return;
            }
            
            // Procesar respuesta con framing de 8 bytes
            while (responseBuffer.length >= 8) {
                const messageLength = readUInt64BE(responseBuffer, 0);
                
                if (messageLength > 64 * 1024 * 1024) {
                    reject(new Error(`Invalid framed length: ${messageLength}`));
                    client.destroy();
                    return;
                }
                
                if (responseBuffer.length >= 8 + messageLength) {
                    const message = responseBuffer.slice(8, 8 + messageLength).toString('utf8');
                    responseBuffer = responseBuffer.slice(8 + messageLength);
                    
                    if (!resolved) {
                        resolved = true;
                        console.log('📥 Respuesta recibida:', message);
                        try {
                            const json = JSON.parse(message);
                            console.log('✅ Respuesta JSON válida:', JSON.stringify(json, null, 2));
                            resolve(json);
                        } catch (e) {
                            console.log('✅ Respuesta de texto:', message);
                            resolve(message);
                        }
                        client.destroy();
                    }
                } else {
                    break;
                }
            }
        });
        
        client.on('error', (err) => {
            if (!resolved) {
                resolved = true;
                reject(err);
            }
        });
        
        client.on('timeout', () => {
            if (!resolved) {
                resolved = true;
                client.destroy();
                reject(new Error('Timeout esperando respuesta'));
            }
        });
        
        client.on('close', () => {
            if (!resolved && !handshakeReceived) {
                resolved = true;
                reject(new Error('Conexión cerrada antes del handshake'));
            } else if (!resolved) {
                resolved = true;
                reject(new Error('Conexión cerrada antes de recibir respuesta'));
            }
        });
    });
}

// Enviar comando JSON
function sendCommand(command) {
    return new Promise((resolve, reject) => {
        const client = new net.Socket();
        let responseBuffer = Buffer.alloc(0);
        let resolved = false;
        let handshakeReceived = false;
        
        client.setTimeout(5000);
        
        client.connect(UNITY_PORT, UNITY_HOST, () => {
            // Esperar handshake antes de enviar
        });
        
        client.on('data', (data) => {
            responseBuffer = Buffer.concat([responseBuffer, data]);
            
            // Primero, leer el handshake
            if (!handshakeReceived) {
                const newlineIndex = responseBuffer.indexOf('\n');
                if (newlineIndex !== -1) {
                    const handshake = responseBuffer.slice(0, newlineIndex + 1).toString('ascii');
                    responseBuffer = responseBuffer.slice(newlineIndex + 1);
                    handshakeReceived = true;
                    
                    // Enviar comando
                    const message = Buffer.from(JSON.stringify(command), 'utf8');
                    const length = Buffer.alloc(8);
                    writeUInt64BE(length, message.length, 0);
                    
                    client.write(Buffer.concat([length, message]));
                    console.log(`📤 Enviado: ${JSON.stringify(command)}`);
                }
                return;
            }
            
            // Procesar respuesta con framing de 8 bytes
            while (responseBuffer.length >= 8) {
                const messageLength = readUInt64BE(responseBuffer, 0);
                
                if (messageLength > 64 * 1024 * 1024) {
                    reject(new Error(`Invalid framed length: ${messageLength}`));
                    client.destroy();
                    return;
                }
                
                if (responseBuffer.length >= 8 + messageLength) {
                    const message = responseBuffer.slice(8, 8 + messageLength).toString('utf8');
                    responseBuffer = responseBuffer.slice(8 + messageLength);
                    
                    if (!resolved) {
                        resolved = true;
                        try {
                            const json = JSON.parse(message);
                            console.log('📥 Respuesta JSON:', JSON.stringify(json, null, 2));
                            resolve(json);
                        } catch (e) {
                            console.log('📥 Respuesta de texto:', message);
                            resolve(message);
                        }
                        client.destroy();
                    }
                } else {
                    break;
                }
            }
        });
        
        client.on('error', (err) => {
            if (!resolved) {
                resolved = true;
                reject(err);
            }
        });
        
        client.on('timeout', () => {
            if (!resolved) {
                resolved = true;
                client.destroy();
                reject(new Error('Timeout esperando respuesta'));
            }
        });
        
        client.on('close', () => {
            if (!resolved && !handshakeReceived) {
                resolved = true;
                reject(new Error('Conexión cerrada antes del handshake'));
            } else if (!resolved) {
                resolved = true;
                reject(new Error('Conexión cerrada antes de recibir respuesta'));
            }
        });
    });
}

async function main() {
    try {
        // 1. Verificar puerto
        console.log('1️⃣ Verificando puerto 6400...');
        const portAvailable = await checkPort();
        
        if (!portAvailable) {
            console.log('\n❌ El servidor Unity MCP no está disponible');
            console.log('💡 Asegúrate de que:');
            console.log('   - Unity Editor esté abierto');
            console.log('   - El proyecto Unity esté cargado');
            console.log('   - El servidor MCP esté activo');
            process.exit(1);
        }
        
        console.log('\n2️⃣ Enviando comando ping...');
        try {
            await sendPing();
        } catch (err) {
            console.log(`❌ Error en ping: ${err.message}`);
        }
        
        console.log('\n3️⃣ Enviando comando get_editor_state...');
        try {
            await sendCommand({
                id: 'test-' + Date.now(),
                type: 'get_editor_state',
                params: {}
            });
        } catch (err) {
            console.log(`❌ Error en get_editor_state: ${err.message}`);
        }
        
        console.log('\n✅ Verificación completada');
        process.exit(0);
        
    } catch (err) {
        console.error('\n❌ Error general:', err.message);
        process.exit(1);
    }
}

main();
