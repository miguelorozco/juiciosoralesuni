# Guía de Migración: PeerJS → LiveKit

Esta guía documenta los cambios necesarios para migrar de PeerJS (P2P) a LiveKit (SFU) + coturn.

## 🔄 Cambios en la Arquitectura

### Antes (PeerJS - P2P)
```
Cliente A ←→ Servidor PeerJS (Signaling) ←→ Cliente B
              ↓
         Conexión P2P directa
         Cliente A ←→ Cliente B
```

### Después (LiveKit - SFU)
```
Cliente A → LiveKit Server ← Cliente B
              ↓ (SFU)
         Distribuye streams
              ↓
   Cliente A recibe de B
   Cliente B recibe de A
```

## 📝 Cambios en el Backend (Laravel)

### 1. Dependencias

**Eliminar:**
```json
// package.json - NO eliminar, solo no usar
{
  "ws": "^8.19.0"  // Ya no se usa para PeerJS
}
```

**Agregar:**
```json
// composer.json
{
  "agones/livekit-server-sdk": "^1.2"
}

// package.json
{
  "livekit-client": "^2.11.0"
}
```

### 2. Configuración

**Antes (`.env`):**
```env
PEERJS_HOST=localhost
PEERJS_PORT=9000
```

**Después (`.env`):**
```env
LIVEKIT_API_KEY=devkey
LIVEKIT_API_SECRET=secret
LIVEKIT_HOST=ws://localhost:7880
LIVEKIT_HTTP_URL=http://localhost:7880

COTURN_HOST=localhost
COTURN_PORT=3478
COTURN_USERNAME=usuario_turn
COTURN_PASSWORD=password_turn
COTURN_REALM=juiciosoralesuni
```

### 3. Controladores

**Antes (`PeerJSController.php` - no existe, pero conceptualmente):**
```php
// No hay generación de tokens en PeerJS
// Los clientes se conectan directamente
```

**Después (`LiveKitController.php`):**
```php
public function getToken(Request $request)
{
    $validated = $request->validate([
        'room_name' => 'required|string',
        'participant_name' => 'required|string',
    ]);

    $token = (new AccessToken($apiKey, $apiSecret))
        ->init($tokenOptions)
        ->setGrant($videoGrant)
        ->toJwt();

    return response()->json([
        'token' => $token,
        'url' => config('livekit.host'),
        'coturn' => [...]
    ]);
}
```

### 4. Rutas

**Agregar en `routes/api.php`:**
```php
use App\Http\Controllers\LiveKitController;

Route::group(['prefix' => 'livekit'], function () {
    Route::middleware('unity.auth')->group(function () {
        Route::post('/token', [LiveKitController::class, 'getToken']);
        Route::get('/rooms', [LiveKitController::class, 'getRooms']);
        Route::get('/rooms/{roomName}/participants', [LiveKitController::class, 'getParticipants']);
    });
});
```

## 🎮 Cambios en Unity

### 1. Scripts a Modificar/Reemplazar

#### `PeerJSManager.cs` → `LiveKitManager.cs`

**Antes (PeerJS):**
```csharp
public class PeerJSManager : MonoBehaviour
{
    private string peerId;
    private WebSocket ws;
    
    void ConnectToPeer(string targetPeerId)
    {
        // Conexión P2P directa
        var connection = peer.Connect(targetPeerId);
        connection.On("data", HandleData);
    }
    
    void SendData(string data)
    {
        connection.Send(data);
    }
}
```

**Después (LiveKit):**
```csharp
using LiveKit;

public class LiveKitManager : MonoBehaviour
{
    private Room room;
    private string roomName;
    
    async Task ConnectToRoom(string token, string serverUrl)
    {
        room = new Room();
        await room.Connect(serverUrl, token);
        
        // Suscribirse a eventos
        room.TrackSubscribed += OnTrackSubscribed;
        room.ParticipantConnected += OnParticipantConnected;
        
        // Publicar tracks locales
        await PublishLocalTracks();
    }
    
    async Task PublishLocalTracks()
    {
        // Publicar audio
        var audioSource = GetComponent<AudioSource>();
        await room.LocalParticipant.PublishAudioTrack(audioSource);
    }
    
    private void OnTrackSubscribed(IRemoteTrack track, RemoteTrackPublication publication, RemoteParticipant participant)
    {
        if (track is RemoteAudioTrack audioTrack)
        {
            // Reproducir audio del participante remoto
            audioTrack.Attach(GetComponent<AudioSource>());
        }
    }
}
```

### 2. Flujo de Conexión

**Antes (PeerJS):**
```csharp
// 1. Obtener peer ID del servidor Laravel
var peerId = await GetPeerIdFromServer();

// 2. Conectar a PeerJS server
peerJSManager.Initialize(peerId);

// 3. Conectar a otros peers
peerJSManager.ConnectToPeer(otherPeerId);
```

**Después (LiveKit):**
```csharp
// 1. Solicitar token de acceso a Laravel
var tokenResponse = await RequestLiveKitToken(roomName, participantName);

// 2. Conectar a LiveKit server con el token
await liveKitManager.ConnectToRoom(
    tokenResponse.token, 
    tokenResponse.url
);

// 3. Los participantes se conectan automáticamente a la sala
// No es necesario conectar manualmente a cada peer
```

### 3. Gestión de Audio

**Antes (PeerJS):**
```csharp
// Manejo manual de cada conexión peer
foreach (var peer in connectedPeers)
{
    peer.mediaConnection.OnStream += (stream) => {
        AttachStreamToAudioSource(stream, peer.audioSource);
    };
}
```

**Después (LiveKit):**
```csharp
// LiveKit maneja automáticamente los tracks
private void OnTrackSubscribed(IRemoteTrack track, RemoteTrackPublication publication, RemoteParticipant participant)
{
    if (track is RemoteAudioTrack audioTrack)
    {
        // Buscar o crear AudioSource para este participante
        var audioSource = GetAudioSourceForParticipant(participant.Identity);
        audioTrack.Attach(audioSource);
    }
}
```

## 🔧 Configuración de Scripts

### Scripts a Modificar

1. **`start-peerjs.sh`** → No usar (dejarlo para compatibilidad)
2. **Usar:** `start-livekit.sh`

### Nuevos Scripts

```bash
# Iniciar servicios LiveKit
./start-livekit.sh

# Detener servicios LiveKit
./stop-livekit.sh

# Cambiar configuración Apache
~/Documents/github/switch-project.sh juiciosoralesuni
```

## 📊 Tabla de Equivalencias

| Concepto | PeerJS | LiveKit |
|----------|--------|---------|
| **Identificador** | Peer ID | Participant Identity |
| **Sala/Room** | No nativo (manual) | Room (nativo) |
| **Conexión** | `peer.connect(id)` | `room.connect(url, token)` |
| **Enviar datos** | `connection.send(data)` | `dataChannel.send(data)` |
| **Evento de datos** | `connection.on('data')` | `room.DataReceived` |
| **Audio/Video** | MediaStream manual | Tracks automáticos |
| **Autenticación** | Peer ID simple | JWT Token |

## ⚠️ Cambios Importantes a Considerar

### 1. Autenticación
- **PeerJS**: No requiere autenticación (solo peer ID)
- **LiveKit**: Requiere token JWT generado por el servidor

### 2. Manejo de Salas
- **PeerJS**: Concepto de "sala" implementado manualmente
- **LiveKit**: Salas son primera clase, con gestión automática

### 3. Escalabilidad
- **PeerJS**: Limitado por conexiones P2P (máx ~6-8 peers efectivos)
- **LiveKit**: Puede manejar 50+ participantes por sala

### 4. Calidad de Audio/Video
- **PeerJS**: Depende de cada conexión P2P
- **LiveKit**: Calidad consistente manejada por SFU

### 5. Firewall/NAT
- **PeerJS**: Puede fallar en redes restrictivas
- **LiveKit + coturn**: Más robusto, usa TURN cuando es necesario

## 🚀 Pasos de Migración

### Fase 1: Preparación (✅ Completado)
- [x] Clonar proyecto a `juiciosoralesuni`
- [x] Instalar LiveKit SDK para Laravel
- [x] Configurar coturn
- [x] Crear scripts de inicio/detención
- [x] Crear script de switch Apache

### Fase 2: Backend
- [ ] Instalar dependencias: `composer install && npm install`
- [ ] Copiar `.env.example` a `.env`
- [ ] Configurar credenciales de LiveKit y coturn
- [ ] Verificar rutas API funcionan: `php artisan route:list | grep livekit`

### Fase 3: Unity
- [ ] Importar LiveKit Unity SDK
- [ ] Crear `LiveKitManager.cs`
- [ ] Modificar `UnityLaravelIntegration.cs` para usar LiveKit
- [ ] Actualizar UI de conexión
- [ ] Probar conexión básica

### Fase 4: Pruebas
- [ ] Probar conexión de 2 participantes
- [ ] Probar audio bidireccional
- [ ] Probar con 5+ participantes
- [ ] Probar en red local
- [ ] Probar en red pública (con coturn)

## 📚 Recursos Adicionales

- [LiveKit Unity SDK Docs](https://docs.livekit.io/client-sdk-unity/)
- [LiveKit Server Docs](https://docs.livekit.io/home/)
- [coturn GitHub](https://github.com/coturn/coturn)
- [Diferencias P2P vs SFU](https://webrtcglossary.com/sfu/)

## 💡 Tips de Desarrollo

1. **Desarrollo local**: Usa las credenciales por defecto (`devkey`/`secret`)
2. **Testing**: Prueba primero con 2 clientes antes de escalar
3. **Logs**: Revisa `livekit.log` y `coturn.log` para debugging
4. **Networking**: Asegúrate que los puertos 7880, 50000-60000 estén abiertos
5. **Switch rápido**: Usa el script `switch-project.sh` para cambiar entre proyectos

---

**Nota**: Esta migración mantiene Laravel y Unity, solo cambia la capa de comunicación en tiempo real.
