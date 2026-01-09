#!/usr/bin/env node
/**
 * Cliente MCP personalizado para Unity Editor
 * Se conecta directamente al servidor TCP de Unity en localhost:6400
 */

import net from 'net';
import readline from 'readline';

// Configuración del servidor Unity
const UNITY_HOST = '127.0.0.1';
const UNITY_PORT = 6400;

// Buffer para mensajes recibidos
let messageBuffer = Buffer.alloc(0);
let currentClient = null;

/**
 * Envía un comando al servidor Unity MCP
 */
function sendCommand(command) {
    return new Promise((resolve, reject) => {
        const client = new net.Socket();
        currentClient = client;
        
        let responseBuffer = Buffer.alloc(0);
        let resolved = false;
        
        client.connect(UNITY_PORT, UNITY_HOST, () => {
            // Formatear comando según el protocolo Unity MCP
            let message;
            if (typeof command === 'string' && command === 'ping') {
                message = Buffer.from('ping', 'utf8');
            } else {
                message = Buffer.from(JSON.stringify(command), 'utf8');
            }
            
            // Prefijo de longitud (4 bytes, big-endian)
            const length = Buffer.alloc(4);
            length.writeUInt32BE(message.length, 0);
            
            client.write(Buffer.concat([length, message]));
        });
        
        client.on('data', (data) => {
            responseBuffer = Buffer.concat([responseBuffer, data]);
            
            // Procesar mensajes con prefijo de longitud
            while (responseBuffer.length >= 4) {
                const messageLength = responseBuffer.readUInt32BE(0);
                
                if (responseBuffer.length >= 4 + messageLength) {
                    const message = responseBuffer.slice(4, 4 + messageLength).toString('utf8');
                    responseBuffer = responseBuffer.slice(4 + messageLength);
                    
                    if (!resolved) {
                        resolved = true;
                        try {
                            const json = JSON.parse(message);
                            resolve(json);
                        } catch (e) {
                            resolve(message);
                        }
                        client.destroy();
                    }
                } else {
                    break; // Esperar más datos
                }
            }
        });
        
        client.on('error', (err) => {
            if (!resolved) {
                resolved = true;
                reject(err);
            }
        });
        
        client.on('close', () => {
            currentClient = null;
        });
        
        // Timeout
        setTimeout(() => {
            if (!resolved && !client.destroyed) {
                resolved = true;
                client.destroy();
                reject(new Error('Connection timeout'));
            }
        }, 10000);
    });
}

/**
 * Procesa mensajes MCP desde stdin (formato stdio)
 */
async function processMcpMessage() {
    const rl = readline.createInterface({
        input: process.stdin,
        output: process.stdout,
        terminal: false
    });
    
    for await (const line of rl) {
        if (!line.trim()) continue;
        
        try {
            const mcpMessage = JSON.parse(line);
            
            // Procesar según el tipo de mensaje MCP
            if (mcpMessage.method) {
                // Es una llamada de método MCP
                await handleMcpMethod(mcpMessage);
            } else if (mcpMessage.jsonrpc === '2.0' && mcpMessage.id) {
                // Es una respuesta
                // No hacemos nada con respuestas aquí
            }
        } catch (e) {
            // Ignorar errores de parsing
        }
    }
}

/**
 * Maneja métodos MCP y los traduce a comandos Unity
 */
async function handleMcpMethod(mcpMessage) {
    const { method, params, id } = mcpMessage;
    
    try {
        let result;
        
        // Traducir métodos MCP a comandos Unity
        switch (method) {
            case 'initialize':
                result = {
                    protocolVersion: '2024-11-05',
                    capabilities: {
                        tools: {},
                        resources: {}
                    },
                    serverInfo: {
                        name: 'unity-editor-mcp',
                        version: '1.0.0'
                    }
                };
                break;
                
            case 'tools/list':
                // Listar herramientas disponibles (comandos Unity)
                result = {
                    tools: [
                        {
                            name: 'read_logs',
                            description: 'Lee los logs de Unity',
                            inputSchema: {
                                type: 'object',
                                properties: {
                                    count: { type: 'number', description: 'Número de logs a leer' }
                                }
                            }
                        },
                        {
                            name: 'get_editor_state',
                            description: 'Obtiene el estado actual del editor Unity',
                            inputSchema: { type: 'object', properties: {} }
                        },
                        {
                            name: 'create_gameobject',
                            description: 'Crea un nuevo GameObject en Unity',
                            inputSchema: {
                                type: 'object',
                                properties: {
                                    name: { type: 'string' },
                                    position: { type: 'object' }
                                }
                            }
                        }
                        // Agregar más herramientas según sea necesario
                    ]
                };
                break;
                
            case 'tools/call':
                // Llamar a una herramienta (comando Unity)
                const toolName = params?.name;
                const toolArgs = params?.arguments || {};
                
                // Convertir a formato de comando Unity
                const unityCommand = {
                    id: `mcp-${Date.now()}`,
                    type: toolName,
                    params: toolArgs
                };
                
                const unityResponse = await sendCommand(unityCommand);
                result = {
                    content: [
                        {
                            type: 'text',
                            text: JSON.stringify(unityResponse, null, 2)
                        }
                    ]
                };
                break;
                
            default:
                result = { error: `Unknown method: ${method}` };
        }
        
        // Enviar respuesta en formato MCP
        const response = {
            jsonrpc: '2.0',
            id,
            result
        };
        
        console.log(JSON.stringify(response));
        
    } catch (error) {
        const errorResponse = {
            jsonrpc: '2.0',
            id,
            error: {
                code: -32603,
                message: error.message
            }
        };
        console.log(JSON.stringify(errorResponse));
    }
}

// Iniciar procesamiento de mensajes MCP
if (import.meta.url === `file://${process.argv[1]}`) {
    processMcpMessage().catch(console.error);
}

export { sendCommand };

