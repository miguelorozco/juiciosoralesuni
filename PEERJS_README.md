# Servidor PeerJS - Juicios Orales

## 📋 Descripción

Servidor PeerJS local para desarrollo que permite comunicación peer-to-peer (P2P) WebRTC entre múltiples clientes Unity y navegadores web.

## 🚀 Inicio Rápido

### Iniciar el servidor

```bash
./start-peerjs.sh
```

El script automáticamente:
- ✅ Lee la configuración del archivo `.env`
- ✅ Verifica que Node.js esté instalado
- ✅ Instala dependencias si es necesario
- ✅ Detecta si el puerto está en uso
- ✅ Inicia el servidor en segundo plano
- ✅ Muestra logs en tiempo real

### Detener el servidor

```bash
./stop-peerjs.sh
```

## ⚙️ Configuración

El servidor lee automáticamente la configuración del archivo `.env`:

```env
# En tu archivo .env
PEERJS_HOST=192.168.0.33  # Host del servidor (0.0.0.0 para todas las interfaces)
PEERJS_PORT=9000           # Puerto del servidor
PEERJS_PATH=/myapp         # Path del endpoint PeerJS
PEERJS_KEY=peerjs          # Clave de autenticación (opcional)
```

### Valores por defecto

Si no existen en el `.env`, se usan estos valores:

```bash
PEERJS_HOST=0.0.0.0
PEERJS_PORT=9000
PEERJS_PATH=/
PEERJS_KEY=peerjs
```

## 📡 Endpoints Disponibles

### Health Check
```bash
curl http://localhost:9000/health
```

Respuesta:
```json
{
  "status": "ok",
  "service": "peerjs-server",
  "timestamp": "2026-02-03T00:37:25.430Z",
  "uptime": 20.227774421,
  "peers": 0
}
```

### Información del Servidor
```bash
curl http://localhost:9000/info
```

Respuesta:
```json
{
  "server": "peerjs-local",
  "port": 9000,
  "peers": [],
  "totalPeers": 0,
  "timestamp": "2026-02-03T00:37:25.430Z"
}
```

### Debug
```bash
curl http://localhost:9000/debug
```

Devuelve información detallada sobre cada peer conectado y uso de memoria.

## 🎮 Uso en Unity

### Configuración Básica

```csharp
using UnityEngine;
using System.Runtime.InteropServices;

public class PeerJSManager : MonoBehaviour
{
    [DllImport("__Internal")]
    private static extern void InitPeerJS(string peerId, string host, int port, string path);
    
    void Start()
    {
        // Configuración desde Laravel .env
        InitPeerJS(
            "unity-player-" + System.Guid.NewGuid().ToString(),
            "192.168.0.33",  // PEERJS_HOST
            9000,             // PEERJS_PORT
            "/"               // PEERJS_PATH
        );
    }
}
```

### JavaScript Bridge (Assets/Plugins/WebGL/peerjs-bridge.jslib)

```javascript
mergeInto(LibraryManager.library, {
    InitPeerJS: function(peerIdPtr, hostPtr, port, pathPtr) {
        const peerId = UTF8ToString(peerIdPtr);
        const host = UTF8ToString(hostPtr);
        const path = UTF8ToString(pathPtr);
        
        window.peer = new Peer(peerId, {
            host: host,
            port: port,
            path: path,
            secure: false,
            config: {
                iceServers: [
                    { urls: 'stun:stun.l.google.com:19302' }
                ]
            }
        });
        
        window.peer.on('open', (id) => {
            console.log('PeerJS conectado:', id);
            SendMessage('PeerJSManager', 'OnPeerOpen', id);
        });
        
        window.peer.on('connection', (conn) => {
            window.currentConnection = conn;
            conn.on('data', (data) => {
                SendMessage('PeerJSManager', 'OnDataReceived', JSON.stringify(data));
            });
        });
    }
});
```

## 🌐 Uso en Navegador Web

```html
<script src="https://unpkg.com/peerjs@1.5.4/dist/peerjs.min.js"></script>
<script>
// Crear peer
const peer = new Peer('browser-user-123', {
    host: '192.168.0.33',
    port: 9000,
    path: '/',
    secure: false
});

// Escuchar cuando esté listo
peer.on('open', (id) => {
    console.log('Mi Peer ID:', id);
});

// Conectar con otro peer (Unity)
const conn = peer.connect('unity-player-xyz');
conn.on('open', () => {
    conn.send({ type: 'message', text: 'Hola desde el navegador!' });
});

// Recibir datos
conn.on('data', (data) => {
    console.log('Datos recibidos:', data);
});
```

## 📊 Monitoreo y Logs

### Ver logs en tiempo real
```bash
tail -f storage/logs/peerjs.log
```

### Ver estado del servidor
```bash
curl http://localhost:9000/health | jq
```

### Ver peers conectados
```bash
curl http://localhost:9000/info | jq '.peers'
```

## 🔧 Troubleshooting

### El puerto 9000 está en uso

El script automáticamente detectará esto y te preguntará si deseas matar los procesos que lo están usando.

Manualmente:
```bash
# Ver qué está usando el puerto
lsof -i :9000

# Matar procesos
lsof -ti:9000 | xargs kill -9
```

### El servidor no inicia

1. Verificar logs:
```bash
cat storage/logs/peerjs.log
```

2. Verificar dependencias:
```bash
npm install
```

3. Verificar Node.js:
```bash
node --version  # Debe ser >= 14.x
```

### No se pueden conectar desde otros dispositivos

1. Verificar firewall:
```bash
sudo ufw status
sudo ufw allow 9000/tcp
```

2. Verificar que escucha en todas las interfaces:
```bash
ss -tuln | grep :9000
# Debe mostrar *:9000 o 0.0.0.0:9000
```

## 📁 Archivos Importantes

```
├── peerjs-server-local.js  # Servidor PeerJS
├── start-peerjs.sh         # Script de inicio automático
├── stop-peerjs.sh          # Script para detener servidor
├── PEERJS_README.md        # Esta documentación
├── .env                    # Configuración (PEERJS_*)
└── storage/
    └── logs/
        └── peerjs.log      # Logs del servidor
```

## 🔐 Seguridad

⚠️ **IMPORTANTE**: Este servidor está configurado para desarrollo local.

Para producción, considera:

1. **HTTPS/WSS**: Usar certificados SSL
2. **Autenticación**: Implementar validación de tokens
3. **Rate Limiting**: Limitar conexiones por IP
4. **CORS**: Restringir orígenes permitidos
5. **Firewall**: Limitar acceso solo a IPs conocidas

## 📝 Notas

- El servidor se ejecuta en **segundo plano** (daemon)
- Los logs se guardan en `storage/logs/peerjs.log`
- El PID se guarda en `/tmp/juiciosorales-peerjs.pid`
- Soporta **auto-reinicio** si ya está corriendo

## 🆘 Soporte

Si encuentras problemas:

1. Revisa los logs: `tail -f storage/logs/peerjs.log`
2. Verifica la configuración en `.env`
3. Asegúrate de que Node.js >= 14.x esté instalado
4. Verifica que el puerto 9000 esté disponible

## 📚 Referencias

- [PeerJS Documentation](https://peerjs.com/docs.html)
- [WebRTC](https://webrtc.org/)
- [Unity WebGL](https://docs.unity3d.com/Manual/webgl.html)
