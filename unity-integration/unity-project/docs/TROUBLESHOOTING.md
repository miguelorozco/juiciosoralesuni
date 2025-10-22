# 🐛 Guía de Troubleshooting - Unity Simulador de Juicios Orales

## 📋 Tabla de Contenidos

1. [Problemas Comunes](#problemas-comunes)
2. [Errores de Conexión](#errores-de-conexión)
3. [Problemas de Audio](#problemas-de-audio)
4. [Problemas de UI](#problemas-de-ui)
5. [Problemas de Performance](#problemas-de-performance)
6. [Herramientas de Debug](#herramientas-de-debug)
7. [Logs y Diagnósticos](#logs-y-diagnósticos)
8. [Soluciones Rápidas](#soluciones-rápidas)

---

## 🚨 Problemas Comunes

### 1. Error de CORS

#### Síntomas
```
Access to fetch at 'http://localhost:8000/api/unity/auth/login' from origin 'http://localhost:3000' has been blocked by CORS policy
```

#### Causa
El servidor Laravel no está configurado para aceptar requests desde Unity WebGL.

#### Solución
1. **Verificar configuración CORS en Laravel**:
```php
// config/cors.php
'allowed_origins' => [
    'http://localhost:3000',  // Unity WebGL
    'https://localhost:3000',
    'http://127.0.0.1:3000',
    'https://127.0.0.1:3000',
],
```

2. **Limpiar caché de Laravel**:
```bash
php artisan config:clear
php artisan cache:clear
```

3. **Verificar que Laravel esté corriendo**:
```bash
php artisan serve --host=0.0.0.0 --port=8000
```

### 2. Error de JWT Token

#### Síntomas
```
401 Unauthorized
Token expired
Invalid token
```

#### Causa
Token JWT expirado o inválido.

#### Solución
1. **Implementar refresh automático**:
```csharp
// En LaravelAPI.cs
public void RefreshToken()
{
    StartCoroutine(RefreshTokenCoroutine());
}

private IEnumerator RefreshTokenCoroutine()
{
    using (UnityWebRequest request = new UnityWebRequest($"{baseURL}/unity/auth/refresh", "POST"))
    {
        request.SetRequestHeader("Authorization", $"Bearer {authToken}");
        yield return request.SendWebRequest();
        
        if (request.result == UnityWebRequest.Result.Success)
        {
            var response = JsonConvert.DeserializeObject<APIResponse<LoginResponse>>(request.downloadHandler.text);
            if (response.success)
            {
                authToken = response.data.token;
            }
        }
    }
}
```

2. **Verificar configuración JWT en Laravel**:
```bash
php artisan jwt:secret
```

### 3. Error de Photon Connection

#### Síntomas
```
Failed to connect to Photon
Photon connection timeout
```

#### Causa
App ID de Photon incorrecto o problemas de conectividad.

#### Solución
1. **Verificar App ID de Photon**:
```csharp
// En UnityConfig.cs
public string photonAppId = "2ec23c58-5cc4-419d-8214-13abad14a02f";
```

2. **Verificar conexión a internet**:
```csharp
if (Application.internetReachability == NetworkReachability.NotReachable)
{
    Debug.LogError("No hay conexión a internet");
}
```

3. **Verificar región de Photon**:
```csharp
// En PhotonServerSettings
PhotonNetwork.PhotonServerSettings.AppSettings.FixedRegion = "us";
```

### 4. Error de PeerJS

#### Síntomas
```
PeerJS connection failed
Audio not working
```

#### Causa
PeerJS no se inicializa correctamente o problemas de permisos de micrófono.

#### Solución
1. **Verificar permisos de micrófono**:
```csharp
if (Application.HasUserAuthorization(UserAuthorization.Microphone))
{
    Debug.Log("Micrófono autorizado");
}
else
{
    Debug.LogWarning("Micrófono no autorizado");
}
```

2. **Verificar configuración de PeerJS**:
```javascript
// En el HTML template
const peer = new Peer(actorId, {
    host: 'juiciosorales.site',
    port: 443,
    secure: true,
    path: '/peerjs'
});
```

3. **Verificar que esté en HTTPS en producción**:
```csharp
// PeerJS requiere HTTPS en producción
if (Application.platform == RuntimePlatform.WebGLPlayer)
{
    // Usar HTTPS
}
```

---

## 🔌 Errores de Conexión

### 1. Unity no se conecta a Laravel

#### Diagnóstico
```csharp
// Verificar estado de conexión
Debug.Log($"Laravel Connected: {laravelAPI.isConnected}");
Debug.Log($"Base URL: {laravelAPI.baseURL}");
Debug.Log($"Auth Token: {!string.IsNullOrEmpty(laravelAPI.authToken)}");
```

#### Solución
1. **Verificar URL de Laravel**:
```csharp
// En UnityConfig.cs
public string apiBaseURL = "http://localhost:8000/api";
```

2. **Verificar que Laravel esté corriendo**:
```bash
# En terminal
curl http://localhost:8000/api/unity/auth/status
```

3. **Verificar logs de Laravel**:
```bash
tail -f storage/logs/laravel.log
```

### 2. Photon no se conecta

#### Diagnóstico
```csharp
// Verificar estado de Photon
Debug.Log($"Photon Connected: {PhotonNetwork.IsConnected}");
Debug.Log($"In Lobby: {PhotonNetwork.InLobby}");
Debug.Log($"In Room: {PhotonNetwork.InRoom}");
```

#### Solución
1. **Verificar App ID**:
```csharp
// En PhotonServerSettings
Debug.Log($"App ID: {PhotonNetwork.PhotonServerSettings.AppSettings.AppIdRealtime}");
```

2. **Verificar región**:
```csharp
// Cambiar región si es necesario
PhotonNetwork.PhotonServerSettings.AppSettings.FixedRegion = "us";
```

3. **Reintentar conexión**:
```csharp
if (!PhotonNetwork.IsConnected)
{
    PhotonNetwork.ConnectUsingSettings();
}
```

### 3. PeerJS no se conecta

#### Diagnóstico
```javascript
// En consola del navegador
console.log("PeerJS Status:", peer.connected);
console.log("PeerJS ID:", peer.id);
```

#### Solución
1. **Verificar configuración de PeerJS**:
```javascript
const peer = new Peer(actorId, {
    host: 'juiciosorales.site',
    port: 443,
    secure: true,
    path: '/peerjs',
    debug: 3
});
```

2. **Verificar que esté en HTTPS**:
```csharp
// PeerJS requiere HTTPS en producción
if (Application.platform == RuntimePlatform.WebGLPlayer)
{
    // Usar HTTPS
}
```

---

## 🎤 Problemas de Audio

### 1. Micrófono no funciona

#### Síntomas
- No se detecta audio del micrófono
- Error de permisos de micrófono

#### Solución
1. **Verificar permisos**:
```csharp
if (Application.HasUserAuthorization(UserAuthorization.Microphone))
{
    Debug.Log("Micrófono autorizado");
}
else
{
    Debug.LogWarning("Micrófono no autorizado");
    Application.RequestUserAuthorization(UserAuthorization.Microphone);
}
```

2. **Verificar configuración de audio**:
```csharp
// En UnityConfig.cs
public bool echoCancellation = true;
public bool noiseSuppression = true;
public int sampleRate = 44100;
```

3. **Verificar en navegador**:
- Asegurar que el micrófono esté habilitado
- Verificar que no haya otros sitios usando el micrófono

### 2. Audio no se transmite

#### Síntomas
- Micrófono funciona pero no se transmite
- Otros jugadores no escuchan

#### Solución
1. **Verificar conexión PeerJS**:
```javascript
// En consola del navegador
console.log("Connections:", Object.keys(peer.connections));
```

2. **Verificar configuración de audio**:
```csharp
// Configurar AudioSource
audioSource.clip = Microphone.Start(null, true, 10, 44100);
audioSource.loop = true;
audioSource.Play();
```

3. **Verificar sincronización**:
```csharp
// Sincronizar estado de audio
photonView.RPC("SyncAudioState", RpcTarget.All, microfonoActivo, audioEnabled);
```

### 3. Audio de baja calidad

#### Síntomas
- Audio distorsionado
- Latencia alta

#### Solución
1. **Ajustar configuración de audio**:
```csharp
// En UnityConfig.cs
public int sampleRate = 44100;
public int channelCount = 1;
public float audioLatency = 0.01f;
```

2. **Optimizar red**:
```csharp
// Reducir frecuencia de sincronización
private float lastAudioSync = 0f;
private float audioSyncInterval = 0.1f;
```

---

## 🖥️ Problemas de UI

### 1. UI no se actualiza

#### Síntomas
- Diálogos no se muestran
- Botones no responden

#### Solución
1. **Verificar suscripción a eventos**:
```csharp
private void Start()
{
    LaravelAPI.OnDialogoUpdated += OnDialogoUpdated;
    LaravelAPI.OnRespuestasReceived += OnRespuestasReceived;
}
```

2. **Verificar que los eventos se disparen**:
```csharp
private void OnDialogoUpdated(DialogoEstado estado)
{
    Debug.Log("Diálogo actualizado");
    // Actualizar UI
}
```

3. **Verificar que la UI esté activa**:
```csharp
if (dialogoPanel != null && !dialogoPanel.activeInHierarchy)
{
    dialogoPanel.SetActive(true);
}
```

### 2. Botones no funcionan

#### Síntomas
- Botones no responden al clic
- Eventos no se disparan

#### Solución
1. **Verificar que el botón esté interactuable**:
```csharp
if (button != null && button.interactable)
{
    button.onClick.AddListener(OnButtonClicked);
}
```

2. **Verificar que no haya otros elementos bloqueando**:
```csharp
// Verificar que no haya UI elements encima
if (EventSystem.current.IsPointerOverGameObject())
{
    return;
}
```

3. **Verificar que el evento esté configurado**:
```csharp
private void Start()
{
    loginButton.onClick.AddListener(OnLoginClicked);
}
```

### 3. Texto no se muestra

#### Síntomas
- Texto vacío o no visible
- Fuentes no cargan

#### Solución
1. **Verificar que el texto esté asignado**:
```csharp
if (dialogoContentText != null)
{
    dialogoContentText.text = "Texto de prueba";
}
```

2. **Verificar que la fuente esté cargada**:
```csharp
// Usar TextMeshPro en lugar de Text
public TextMeshProUGUI dialogoContentText;
```

3. **Verificar que el objeto esté activo**:
```csharp
if (dialogoContentText.gameObject.activeInHierarchy)
{
    dialogoContentText.text = contenido;
}
```

---

## ⚡ Problemas de Performance

### 1. Juego lento

#### Síntomas
- FPS bajos
- Lag en la UI

#### Solución
1. **Optimizar UI**:
```csharp
// Usar object pooling para botones
private Queue<GameObject> buttonPool = new Queue<GameObject>();

private GameObject GetButton()
{
    if (buttonPool.Count > 0)
    {
        return buttonPool.Dequeue();
    }
    return Instantiate(buttonPrefab);
}
```

2. **Limitar frecuencia de actualizaciones**:
```csharp
private float lastUpdate = 0f;
private float updateInterval = 0.1f;

private void Update()
{
    if (Time.time - lastUpdate > updateInterval)
    {
        UpdateUI();
        lastUpdate = Time.time;
    }
}
```

3. **Usar Profiler de Unity**:
- **Window > Analysis > Profiler**
- Identificar cuellos de botella

### 2. Memoria alta

#### Síntomas
- Uso excesivo de memoria
- Crashes por memoria

#### Solución
1. **Limpiar objetos no utilizados**:
```csharp
private void OnDestroy()
{
    // Limpiar referencias
    laravelAPI = null;
    dialogoUI = null;
    
    // Limpiar eventos
    LaravelAPI.OnUserLoggedIn -= OnUserLoggedIn;
}
```

2. **Usar object pooling**:
```csharp
// Reutilizar objetos en lugar de crear/destruir
private void ReturnButton(GameObject button)
{
    button.SetActive(false);
    buttonPool.Enqueue(button);
}
```

3. **Optimizar texturas**:
- Usar formatos comprimidos
- Reducir resolución si es necesario

---

## 🔧 Herramientas de Debug

### 1. Console de Unity

#### Logs Básicos
```csharp
Debug.Log("Información general");
Debug.LogWarning("Advertencia");
Debug.LogError("Error crítico");
```

#### Logs Condicionales
```csharp
if (Debug.isDebugBuild)
{
    Debug.Log($"Debug info: {detailedInfo}");
}
```

#### Logs con Formato
```csharp
Debug.LogFormat("Usuario {0} conectado en {1}", userName, DateTime.Now);
```

### 2. Debug Panel

#### Panel Básico
```csharp
private void OnGUI()
{
    if (showDebugPanel)
    {
        GUILayout.BeginArea(new Rect(10, 10, 400, 300));
        GUILayout.Label("Debug Information");
        GUILayout.Label($"Estado: {GetStatus()}");
        
        if (GUILayout.Button("Test Button"))
        {
            TestFunction();
        }
        
        GUILayout.EndArea();
    }
}
```

#### Panel Avanzado
```csharp
private void OnGUI()
{
    if (showDebugPanel)
    {
        GUILayout.BeginArea(new Rect(10, 10, 500, 400));
        
        GUILayout.Label("=== DEBUG PANEL ===");
        
        GUILayout.Space(10);
        GUILayout.Label($"Laravel: {(laravelAPI?.isConnected ?? false ? "Conectado" : "Desconectado")}");
        GUILayout.Label($"Photon: {(PhotonNetwork.IsConnected ? "Conectado" : "Desconectado")}");
        GUILayout.Label($"Sala: {(PhotonNetwork.InRoom ? PhotonNetwork.CurrentRoom.Name : "No en sala")}");
        GUILayout.Label($"Jugadores: {(PhotonNetwork.InRoom ? PhotonNetwork.CurrentRoom.PlayerCount : 0)}");
        
        GUILayout.Space(10);
        if (GUILayout.Button("Reconectar Laravel"))
        {
            laravelAPI?.Reconnect();
        }
        
        if (GUILayout.Button("Reconectar Photon"))
        {
            PhotonNetwork.ConnectUsingSettings();
        }
        
        GUILayout.EndArea();
    }
}
```

### 3. Breakpoints

#### En Visual Studio
1. Colocar breakpoint en línea específica
2. Ejecutar en modo Debug
3. Inspeccionar variables

#### En Unity
```csharp
// Usar Debug.Break() para pausar
if (condition)
{
    Debug.Break();
}
```

---

## 📊 Logs y Diagnósticos

### 1. Logs de Unity

#### Habilitar Logs Detallados
```csharp
// En UnityConfig.cs
public bool showDebugLogs = true;
public LogLevel logLevel = LogLevel.Debug;
```

#### Filtrar Logs
```csharp
// Filtrar por tipo
Debug.Log("Info message");
Debug.LogWarning("Warning message");
Debug.LogError("Error message");
```

### 2. Logs de Laravel

#### Ver Logs en Tiempo Real
```bash
tail -f storage/logs/laravel.log
```

#### Filtrar Logs de Unity
```bash
grep "Unity" storage/logs/laravel.log
```

#### Logs de API
```bash
grep "api/unity" storage/logs/laravel.log
```

### 3. Logs de Photon

#### Habilitar Logs de Photon
```csharp
PhotonNetwork.LogLevel = PunLogLevel.Full;
```

#### Ver Logs en Dashboard
- Ir a [Photon Dashboard](https://dashboard.photonengine.com/)
- Revisar métricas de conexión

### 4. Logs de Navegador

#### Console de Navegador
```javascript
// En consola del navegador
console.log("Unity loaded");
console.log("PeerJS status:", peer.connected);
```

#### Network Tab
- Verificar requests HTTP
- Verificar respuestas de API

---

## 🚀 Soluciones Rápidas

### 1. Reset Completo

#### Reiniciar Todo
```csharp
public void ResetEverything()
{
    // Desconectar de Photon
    if (PhotonNetwork.IsConnected)
    {
        PhotonNetwork.Disconnect();
    }
    
    // Limpiar token de Laravel
    authToken = "";
    isConnected = false;
    
    // Reiniciar integración
    StartCoroutine(InitializeIntegration());
}
```

### 2. Verificación Rápida

#### Checklist de Diagnóstico
```csharp
public void QuickDiagnostic()
{
    Debug.Log("=== QUICK DIAGNOSTIC ===");
    Debug.Log($"Unity Version: {Application.unityVersion}");
    Debug.Log($"Platform: {Application.platform}");
    Debug.Log($"Internet: {Application.internetReachability}");
    Debug.Log($"Laravel URL: {baseURL}");
    Debug.Log($"Photon App ID: {PhotonNetwork.PhotonServerSettings.AppSettings.AppIdRealtime}");
    Debug.Log($"Auth Token: {!string.IsNullOrEmpty(authToken)}");
    Debug.Log($"Photon Connected: {PhotonNetwork.IsConnected}");
    Debug.Log($"In Room: {PhotonNetwork.InRoom}");
}
```

### 3. Soluciones Comunes

#### Error de CORS
```bash
# Limpiar caché de Laravel
php artisan config:clear
php artisan cache:clear
```

#### Error de JWT
```bash
# Regenerar secret JWT
php artisan jwt:secret
```

#### Error de Photon
```csharp
// Reconectar a Photon
PhotonNetwork.ConnectUsingSettings();
```

#### Error de PeerJS
```javascript
// Reiniciar PeerJS
peer.destroy();
peer = new Peer(actorId, config);
```

---

## 📞 Soporte Adicional

### Recursos de Ayuda
- **Documentación Unity**: [docs.unity3d.com](https://docs.unity3d.com/)
- **Documentación Photon**: [doc.photonengine.com](https://doc.photonengine.com/pun2)
- **Documentación Laravel**: [laravel.com/docs](https://laravel.com/docs)

### Herramientas de Debug
- **Unity Profiler**: Window > Analysis > Profiler
- **Unity Test Runner**: Window > General > Test Runner
- **Browser DevTools**: F12 en el navegador

### Logs Importantes
- **Unity Console**: Ver logs de Unity
- **Laravel Logs**: `storage/logs/laravel.log`
- **Photon Dashboard**: [dashboard.photonengine.com](https://dashboard.photonengine.com/)

---

**¡Guía de troubleshooting completa! 🐛**
