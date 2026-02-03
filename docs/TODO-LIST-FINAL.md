# TODO LIST FINAL - Integración Photon + PeerJS + UI Debug

## 🎯 Objetivo General
Integrar Photon para multiplayer, PeerJS para audio P2P, y una interfaz HTML de debug en el WebGL build de Unity. Todo debe funcionar en desarrollo local con servidor PeerJS propio.

---

## 📋 FASE 1: PLANIFICACIÓN Y CONFIGURACIÓN

### 1.1 Servidor PeerJS Local
- **Archivo a crear**: `peerjs-server-local.js` en raíz del proyecto
- **Puerto**: 9000 (desarrollo local)
- **Objetivo**: Servir PeerJS localmente sin dependencias externas
- **Validación**: Server responde en `http://localhost:9000`
- **Incluir**:
  - Manejo de CORS para WebGL
  - Logging de conexiones
  - Health check endpoint

### 1.2 WebGL Template HTML
- **Ubicación**: `Assets/WebGLTemplates/PlantillaJuicios/index.html`
- **Elementos a agregar**:
  - `<div id="debug-panel">` con:
    - **Unity Logs**: `<div id="unity-logs">` (max 20 logs, auto-scroll)
    - **Connection Status**: 
      - `<div id="peerjs-status">` (Connected/Disconnected)
      - `<div id="photon-status">` (Connected/Disconnected)
      - `<div id="laravel-api-status">` (Connected/Disconnected)
    - **Microphone**:
      - `<button id="request-mic-permission">` (Request Permission)
      - `<div id="mic-status">` (Permission status)
      - `<div id="audio-level">` (Visual indicator)
      - `<span id="is-recording">` (Si/No)

### 1.3 Configuración Photon
- **Archivo**: PhotonNetworkInstantiate en escena main
- **App ID**: Debe estar en `Assets/Resources/PhotonServerSettings.asset`
- **Validar**: Region = "us", Max Players = 20
- **Prefab Player**: `Assets/Photon/PhotonUnityNetworking/Resources/Player.prefab`

---

## 📝 FASE 2: SCRIPTS DE SERVIDOR

### 2.1 `peerjs-server-local.js`
```javascript
// Ubicación: /peerjs-server-local.js
// Dependencias: express, peerjs, cors

Funcionalidades:
✓ Iniciar servidor en puerto 9000
✓ Servir PeerJS broker
✓ CORS habilitado para http://localhost:3000-8100
✓ Health check en GET /health
✓ Logging de eventos
✓ Graceful shutdown
```

**Comandos de inicio**:
```bash
npm install express peerjs cors
node peerjs-server-local.js
```

---

## 🎮 FASE 3: SCRIPTS UNITY C#

### 3.1 `DebugUIManager.cs`
- **Ubicación**: `Assets/Scripts/DebugUIManager.cs`
- **Responsabilidades**:
  - Capturar logs de Unity y enviarlos a HTML
  - Actualizar estado de conexión PeerJS
  - Actualizar estado de conexión Photon
  - Actualizar estado de micrófono
  - Mostrar nivel de audio en tiempo real
- **Métodos**:
  - `AddLog(string message, LogType type)`
  - `UpdatePeerJSStatus(bool connected)`
  - `UpdatePhotonStatus(bool connected)`
  - `UpdateLaravelStatus(bool connected)`
  - `UpdateMicrophoneStatus(bool permitted)`
  - `UpdateAudioLevel(float level)`
  - `SetIsRecording(bool recording)`

### 3.2 `MicrophonePermissionManager.cs`
- **Ubicación**: `Assets/Scripts/MicrophonePermissionManager.cs`
- **Responsabilidades**:
  - Solicitar permisos de micrófono
  - Capturar audio del micrófono
  - Calcular nivel de ruido en tiempo real
  - Enviar audio a PeerJS
  - Comunicar estado a DebugUIManager
- **Métodos**:
  - `RequestMicrophonePermission()`
  - `StartAudioCapture()`
  - `StopAudioCapture()`
  - `GetCurrentAudioLevel() -> float`
  - `SendAudioToPeer(AudioClip clip)`

### 3.3 `PeerJSBridge.cs` (Cliente WebGL)
- **Ubicación**: `Assets/Scripts/PeerJSBridge.cs`
- **Responsabilidades**:
  - Comunicarse con PeerJS desde C#
  - Conectar a servidor PeerJS local (puerto 9000)
  - Manejar eventos de conexión/desconexión
  - Enviar/recibir datos P2P
  - Notificar a DebugUIManager de estado
- **Métodos**:
  - `Connect(string peerId)`
  - `Call(string targetPeerId, AudioStream stream)`
  - `OnPeerConnected(string peerId)`
  - `OnPeerDisconnected(string peerId)`
  - `OnConnectionFailed(string error)`

### 3.4 `PhotonManager.cs` (Actualizar existente)
- **Ubicación**: `Assets/Scripts/PhotonManager.cs` o `GestionRedJugador.cs`
- **Cambios**:
  - Agregar evento `OnPhotonConnected`
  - Agregar evento `OnPhotonDisconnected`
  - Notificar a DebugUIManager de estado
  - Instanciar prefab Player correctamente
- **Validar**:
  - `PhotonNetwork.AutomaticallySyncScene = true`
  - Player prefab en Resources/

---

## 🌐 FASE 4: INTEGRACIÓN WEB (HTML + JavaScript)

### 4.1 `index.html` - Agregar elementos HTML
```html
<!-- Debug Panel (lado superior derecho) -->
<div id="debug-panel" style="position: fixed; top: 10px; right: 10px; ...">
  <h3>Debug Info</h3>
  
  <!-- Unity Logs -->
  <div id="unity-logs-container" style="max-height: 200px; overflow-y: auto;">
    <div id="unity-logs"></div>
  </div>
  
  <!-- Connection Status -->
  <div id="connections">
    <p>PeerJS: <span id="peerjs-status">Disconnected</span></p>
    <p>Photon: <span id="photon-status">Disconnected</span></p>
    <p>Laravel API: <span id="laravel-status">Disconnected</span></p>
  </div>
  
  <!-- Microphone -->
  <div id="microphone">
    <button id="request-mic-permission">Request Mic Permission</button>
    <p>Permission: <span id="mic-permission-status">Not Requested</span></p>
    <p>Recording: <span id="is-recording">No</span></p>
    <div id="audio-level-bar" style="width: 200px; height: 20px; border: 1px solid;">
      <div id="audio-level" style="height: 100%; background: green; width: 0%;"></div>
    </div>
  </div>
</div>
```

### 4.2 `web-mcp-interface.js` - Bridge JavaScript/C#
```javascript
// Ubicación: Assets/WebGLTemplates/PlantillaJuicios/web-mcp-interface.js

Funcionalidades:
✓ Recibir datos desde C# (vía SendMessage)
✓ Actualizar DOM dinámicamente
✓ Manejar clicks en botones
✓ Enviar eventos a C# (vía unityInstance.SendMessage)
✓ Logging en consola y panel HTML
```

---

## 🔗 FASE 5: FLUJO DE INTEGRACIÓN

### 5.1 Secuencia de Conexión (En Orden)
1. **Unity Inicializa**
   - DebugUIManager se registra para logs
   - MicrophonePermissionManager se inicializa
   - PeerJSBridge se inicializa

2. **PeerJSBridge Conecta** (localhost:9000)
   - Espera respuesta del servidor PeerJS
   - DebugUIManager → UpdatePeerJSStatus(true)
   - HTML: `<span id="peerjs-status">Connected</span>`

3. **Photon Conecta** (PhotonNetwork.ConnectUsingSettings)
   - OnConnectedToPhoton() → DebugUIManager
   - DebugUIManager → UpdatePhotonStatus(true)
   - HTML: `<span id="photon-status">Connected</span>`

4. **LaravelAPI Conecta** (GET /api/health)
   - OnAPIConnected() → DebugUIManager
   - DebugUIManager → UpdateLaravelStatus(true)
   - HTML: `<span id="laravel-status">Connected</span>`

5. **Usuario Solicita Micrófono**
   - Click en `#request-mic-permission`
   - JavaScript → SendMessage("MicrophonePermissionManager", "RequestPermission")
   - MicrophonePermissionManager → Permissions.RequestUserPermission("Microphone")
   - OnPermissionGranted() → DebugUIManager → UpdateMicrophoneStatus(true)
   - Inicia captura de audio → StartAudioCapture()

6. **Audio Streaming**
   - MicrophonePermissionManager calcula nivel en tiempo real
   - Envía a DebugUIManager → UpdateAudioLevel(level)
   - JavaScript actualiza barra visual
   - Envía a PeerJSBridge → SendAudioToPeer()

---

## 📂 ESTRUCTURA DE ARCHIVOS A CREAR

```
juiciosorales/
├── peerjs-server-local.js                          [NUEVO]
├── Assets/
│   ├── Scripts/
│   │   ├── DebugUIManager.cs                       [NUEVO]
│   │   ├── MicrophonePermissionManager.cs          [NUEVO]
│   │   ├── PeerJSBridge.cs                         [NUEVO]
│   │   ├── PhotonManager.cs                        [ACTUALIZAR]
│   │   └── GameInitializer.cs                      [ACTUALIZAR]
│   ├── Scenes/
│   │   └── main.unity                              [ACTUALIZAR - Agregar Player prefab]
│   └── WebGLTemplates/
│       └── PlantillaJuicios/
│           ├── index.html                          [ACTUALIZAR]
│           ├── web-mcp-interface.js               [NUEVO]
│           └── style.css                           [ACTUALIZAR]
└── docs/
    └── TODO-LIST-FINAL.md                          [ESTE ARCHIVO]
```

---

## ✅ CHECKLIST DE VALIDACIÓN

### Fase 1: Servidor PeerJS
- [ ] `peerjs-server-local.js` funciona sin errores
- [ ] Responde en `http://localhost:9000`
- [ ] CORS está habilitado
- [ ] Logs de conexión en consola

### Fase 2: Scripts Unity
- [ ] DebugUIManager compilar sin errores
- [ ] MicrophonePermissionManager compilar sin errores
- [ ] PeerJSBridge compilar sin errores
- [ ] PhotonManager actualizado

### Fase 3: HTML/WebGL
- [ ] index.html tiene todos los elementos debug
- [ ] `web-mcp-interface.js` se carga correctamente
- [ ] PeerJS library está incluida
- [ ] CSS del debug panel es visible

### Fase 4: Pruebas Funcionales
- [ ] Build WebGL compila sin errores
- [ ] En navegador, debug panel visible
- [ ] Conecta a PeerJS Server local
- [ ] Conecta a Photon
- [ ] Permisos de micrófono funcionan
- [ ] Nivel de audio se muestra en tiempo real
- [ ] Player prefab aparece en escena
- [ ] Múltiples jugadores pueden conectar

---

## 🚀 COMANDOS DE INICIO (Orden)

```bash
# Terminal 1: Servidor PeerJS
cd /home/miguel/Documents/github/juiciosorales
node peerjs-server-local.js

# Terminal 2: Servidor Laravel
cd /home/miguel/Documents/github/juiciosorales
php artisan serve --host=0.0.0.0 --port=8000

# Terminal 3: Abrir Unity Editor (si no está abierto)
# Abrir proyecto en Assets/ con escena main

# Terminal 4: Build WebGL y servir
# En Unity: File → Build Settings → WebGL → Build
# Luego: python -m http.server 8080 (en carpeta de build)

# Navigador: http://localhost:8080
```

---

## 📊 DEPENDENCIAS REQUERIDAS

### npm (Node.js)
```json
{
  "dependencies": {
    "express": "^4.18.2",
    "peerjs": "^1.5.0",
    "cors": "^2.8.5"
  }
}
```

### Unity Packages
```
✓ Photon PUN 2 (v2.67 o superior)
✓ TextMesh Pro (incluido)
✓ Universal Render Pipeline (incluido)
```

### JavaScript Libraries
```html
<!-- En index.html -->
<script src="https://cdn.jsdelivr.net/npm/peerjs@1.5.0/dist/peerjs.min.js"></script>
```

---

## 🐛 TROUBLESHOOTING

| Problema | Causa Probable | Solución |
|----------|---|---|
| PeerJS no conecta | Server no está corriendo | `node peerjs-server-local.js` en terminal |
| Photon desconecta | AppID incorrecto | Verificar PhotonServerSettings.asset |
| Micrófono no funciona | Permisos no otorgados | Hacer click en botón, permitir en navegador |
| WebGL no carga | Build error | Ver Unity Console, revisar logs |
| HTML elements no actualizan | SendMessage falla | Verificar nombres exactos en C# |

---

## 📅 TIMELINE ESTIMADO

| Fase | Tarea | Duración |
|------|-------|----------|
| 1 | Crear servidor PeerJS | 30 min |
| 2 | Scripts C# (DebugUIManager, Mic, PeerJS) | 2 hrs |
| 3 | HTML + JS interface | 1.5 hrs |
| 4 | Integración Photon en escena | 1 hr |
| 5 | Build WebGL y pruebas | 1.5 hrs |
| **TOTAL** | | **~6 hrs** |

---

## 🎯 RESULTADO FINAL

Un WebGL build funcional de Unity con:
- ✅ Multiplayer con Photon (2+ jugadores)
- ✅ Audio P2P con PeerJS
- ✅ Permisos de micrófono solicitados y gestionados
- ✅ Debug panel visible mostrando:
  - Logs de Unity en tiempo real
  - Estado de conexiones
  - Nivel de audio
  - Permisos solicitados
- ✅ Servidor PeerJS local funcionando
- ✅ Prefab Player instanciado en escena

