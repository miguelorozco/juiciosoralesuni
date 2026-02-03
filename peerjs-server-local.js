#!/usr/bin/env node

/**
 * PeerJS Server Local - Desarrollo
 * 
 * Servidor PeerJS simple para desarrollo local con WebGL builds
 * Soporta CORS y permite conexiones desde http://localhost:*
 * 
 * Uso:
 *   node peerjs-server-local.js
 * 
 * Acceso:
 *   - Servidor: http://localhost:9000
 *   - Health: http://localhost:9000/health
 *   - PeerJS: ws://localhost:9000 (WebSocket)
 */

import express from 'express';
import { ExpressPeerServer } from 'peer';
import cors from 'cors';
import http from 'http';

// Configuración
const PORT = process.env.PEERJS_PORT || 9000;
const HOST = '0.0.0.0';

// Crear aplicación Express
const app = express();

// Middleware
app.use(cors({
  origin: (origin, callback) => {
    // Permitir localhost con cualquier puerto
    if (!origin || origin.match(/^https?:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/)) {
      callback(null, true);
    } else {
      callback(new Error('Not allowed by CORS'));
    }
  },
  credentials: true,
  optionsSuccessStatus: 200
}));

app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Health check endpoint
app.get('/health', (req, res) => {
  res.json({
    status: 'ok',
    service: 'peerjs-server',
    timestamp: new Date().toISOString(),
    uptime: process.uptime(),
    peers: Object.keys(peerServer._clients || {}).length
  });
});

// Endpoint para obtener info del servidor
app.get('/info', (req, res) => {
  const clients = peerServer._clients || {};
  const peers = Object.keys(clients).map(peerId => ({
    id: peerId,
    connections: Object.keys(clients[peerId] || {}).length
  }));

  res.json({
    server: 'peerjs-local',
    port: PORT,
    peers: peers,
    totalPeers: peers.length,
    timestamp: new Date().toISOString()
  });
});

// Crear servidor HTTP
const server = http.createServer(app);

// Crear servidor PeerJS
const peerServer = ExpressPeerServer(server, {
  debug: true,
  path: '/peerjs',
  proxied: false,
  ssl: false,
  allow_discovery: true
});

// Event listeners para PeerJS
peerServer.on('connection', (client) => {
  console.log(`[${new Date().toISOString()}] Peer conectado: ${client.getId()}`);
  logStatus();
});

peerServer.on('disconnect', (client) => {
  console.log(`[${new Date().toISOString()}] Peer desconectado: ${client.getId()}`);
  logStatus();
});

// Manejo de errores del servidor
peerServer.on('error', (error) => {
  console.error(`[ERROR PeerServer] ${error.message}`);
});

// Usar el servidor PeerJS en /peerjs
app.use('/peerjs', peerServer);

// Manejo de errores general
process.on('unhandledRejection', (reason, promise) => {
  console.error('[UNHANDLED REJECTION]', reason);
});

process.on('uncaughtException', (error) => {
  console.error('[UNCAUGHT EXCEPTION]', error);
  process.exit(1);
});

// Graceful shutdown
process.on('SIGTERM', () => {
  console.log('\n[SIGTERM] Cerrando servidor...');
  shutdown();
});

process.on('SIGINT', () => {
  console.log('\n[SIGINT] Cerrando servidor...');
  shutdown();
});

function shutdown() {
  console.log('Cerrando servidor HTTP...');
  server.close(() => {
    console.log('Servidor cerrado.');
    process.exit(0);
  });
}

// Función de logging de estado
function logStatus() {
  const clients = peerServer._clients || {};
  const totalPeers = Object.keys(clients).length;
  const timestamp = new Date().toISOString();
  console.log(`[${timestamp}] Estado: ${totalPeers} peer(s) conectado(s)`);
}

// Iniciar servidor
server.listen(PORT, HOST, () => {
  const timestamp = new Date().toISOString();
  console.log(`
╔══════════════════════════════════════════════════════╗
║        PeerJS Server Local - Desarrollo             ║
╠══════════════════════════════════════════════════════╣
║ Hora inicio: ${timestamp}
║ Host:       ${HOST}:${PORT}
║ URL:        http://localhost:${PORT}
║ PeerJS:     http://localhost:${PORT}/peerjs
║ WebSocket:  ws://localhost:${PORT}/peerjs
║ Health:     http://localhost:${PORT}/health
║ Info:       http://localhost:${PORT}/info
╠══════════════════════════════════════════════════════╣
║ Estado: Escuchando...
║ Presiona Ctrl+C para detener
╚══════════════════════════════════════════════════════╝
  `);

  // Log inicial de estado
  logStatus();

  // Log periódico de estado cada 30 segundos
  setInterval(() => {
    logStatus();
  }, 30000);
});

// Información de depuración
app.get('/debug', (req, res) => {
  const clients = peerServer._clients || {};
  const detailed = {};

  Object.keys(clients).forEach(peerId => {
    const client = clients[peerId];
    detailed[peerId] = {
      id: peerId,
      connections: Object.keys(client || {}).length,
      active: client !== null
    };
  });

  res.json({
    debug: true,
    timestamp: new Date().toISOString(),
    uptime: process.uptime(),
    memory: process.memoryUsage(),
    peers: detailed
  });
});

export { peerServer, app, server };
