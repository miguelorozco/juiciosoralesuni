# 🎮 Guía de Integración Unity + Laravel con Sala y Audio

Esta guía te ayudará a integrar tu proyecto Unity existente (con sala y audio compartido) con el sistema Laravel de simulador de juicios.

## 📋 Pasos para Integrar tu Proyecto

### **Paso 1: Copiar tu Proyecto Unity** 📁

**Ubicación:** `/var/www/juicios_local/unity-integration/unity-project/`

```bash
# Copia TODO tu proyecto Unity aquí:
cp -r /ruta/a/tu/proyecto-unity/* /var/www/juicios_local/unity-integration/unity-project/
```

**Estructura esperada:**
```
unity-project/
├── Assets/
│   ├── Scripts/
│   │   ├── RoomManager.cs          # Tu script de sala
│   │   ├── AudioManager.cs         # Tu script de audio
│   │   ├── PlayerController.cs     # Tu script de jugador
│   │   └── NetworkManager.cs       # Tu script de red
│   └── Scenes/
│       └── MainScene.unity         # Tu escena principal
├── ProjectSettings/
├── UserSettings/
└── Packages/
```

### **Paso 2: Integrar Scripts de Laravel** 🔧

Copia estos scripts a tu proyecto Unity:

1. **`LaravelAPI.cs`** → `Assets/Scripts/API/`
2. **`RoomIntegration.cs`** → `Assets/Scripts/Integration/`
3. **`AudioIntegration.cs`** → `Assets/Scripts/Integration/`
4. **`UnityConfig.cs`** → `Assets/Scripts/Config/`

### **Paso 3: Modificar tus Scripts Existentes** ✏️

#### A. Modificar tu `RoomManager.cs`

```csharp
// Agregar al inicio de tu RoomManager.cs
using JuiciosSimulator.Room;

public class RoomManager : MonoBehaviour
{
    [Header("Integración Laravel")]
    public RoomIntegration roomIntegration;
    
    // Tus métodos existentes...
    public void CreateRoom(string roomName, int maxPlayers)
    {
        // Tu lógica existente de crear sala
        // ...
        
        // Integrar con Laravel
        if (roomIntegration != null)
        {
            roomIntegration.CreateRoom(roomName, maxPlayers);
        }
    }
    
    public void JoinRoom(string roomId)
    {
        // Tu lógica existente de unirse a sala
        // ...
        
        // Integrar con Laravel
        if (roomIntegration != null)
        {
            roomIntegration.JoinRoom(roomId);
        }
    }
    
    // Eventos para integrar con Laravel
    public static event Action<PlayerData> OnPlayerJoined;
    public static event Action<int> OnPlayerLeft;
    
    private void OnPlayerJoinedRoom(PlayerData player)
    {
        // Tu lógica existente
        // ...
        
        // Notificar a Laravel
        OnPlayerJoined?.Invoke(player);
    }
}
```

#### B. Modificar tu `AudioManager.cs`

```csharp
// Agregar al inicio de tu AudioManager.cs
using JuiciosSimulator.Audio;

public class AudioManager : MonoBehaviour
{
    [Header("Integración Laravel")]
    public AudioIntegration audioIntegration;
    
    // Tus métodos existentes...
    public void SetMicrophoneActive(bool active)
    {
        // Tu lógica existente
        // ...
        
        // Integrar con Laravel
        if (audioIntegration != null)
        {
            audioIntegration.SetMicrophoneActive(active);
        }
    }
    
    public void SetVolume(float volume)
    {
        // Tu lógica existente
        // ...
        
        // Integrar con Laravel
        if (audioIntegration != null)
        {
            audioIntegration.SetVolume(volume);
        }
    }
}
```

#### C. Modificar tu `PlayerController.cs`

```csharp
// Agregar al inicio de tu PlayerController.cs
using JuiciosSimulator.Room;

public class PlayerController : MonoBehaviour
{
    [Header("Datos del Jugador")]
    public int usuarioId;
    public string nombreJugador;
    public bool audioEnabled = true;
    public bool microfonoActivo = false;
    
    [Header("Integración Laravel")]
    public RoomIntegration roomIntegration;
    
    private void Update()
    {
        // Tu lógica existente de movimiento
        // ...
        
        // Sincronizar con Laravel cada cierto tiempo
        if (Time.frameCount % 30 == 0) // Cada 30 frames
        {
            SyncWithLaravel();
        }
    }
    
    private void SyncWithLaravel()
    {
        if (roomIntegration != null)
        {
            var playerData = new PlayerData
            {
                usuarioId = this.usuarioId,
                nombre = this.nombreJugador,
                position = transform.position,
                rotation = transform.rotation,
                audioEnabled = this.audioEnabled,
                microfonoActivo = this.microfonoActivo,
                metadata = new Dictionary<string, object>
                {
                    {"health", 100},
                    {"score", 0}
                }
            };
            
            roomIntegration.SyncPlayer(playerData);
        }
    }
}
```

### **Paso 4: Configurar la Escena** 🎬

1. **Crear GameObjects para integración:**
   - `LaravelAPI` → Agregar script `LaravelAPI`
   - `RoomIntegration` → Agregar script `RoomIntegration`
   - `AudioIntegration` → Agregar script `AudioIntegration`

2. **Configurar referencias:**
   - En `RoomIntegration`: Asignar tu `RoomManager`
   - En `AudioIntegration`: Asignar tu `AudioManager`
   - En `PlayerController`: Asignar `RoomIntegration`

3. **Configurar UnityConfig:**
   - Crear `Assets/Resources/UnityConfig.asset`
   - Configurar URL de API: `http://localhost:8000/api`

### **Paso 5: Configurar Build Settings** ⚙️

1. **WebGL Build:**
   - File > Build Settings
   - Platform: WebGL
   - Player Settings > Publishing Settings
   - Data Caching: Disabled

2. **Standalone Build:**
   - Platform: Windows/Mac/Linux
   - Configuration: Release

## 🔌 API Endpoints Disponibles

### **Autenticación**
- `POST /api/unity/auth/login` - Login
- `GET /api/unity/auth/status` - Estado del servidor
- `POST /api/unity/auth/refresh` - Renovar token
- `POST /api/unity/auth/logout` - Logout

### **Salas de Unity**
- `POST /api/unity/rooms/create` - Crear sala
- `GET /api/unity/rooms/{id}/join` - Unirse a sala
- `POST /api/unity/rooms/{id}/leave` - Salir de sala
- `GET /api/unity/rooms/{id}/state` - Estado de sala
- `POST /api/unity/rooms/{id}/sync-player` - Sincronizar jugador
- `POST /api/unity/rooms/{id}/audio-state` - Estado de audio
- `GET /api/unity/rooms/{id}/events` - Eventos de sala

### **Diálogos**
- `GET /api/unity/sesion/{id}/dialogo-estado` - Estado del diálogo
- `GET /api/unity/sesion/{id}/respuestas-usuario/{user}` - Respuestas
- `POST /api/unity/sesion/{id}/enviar-decision` - Enviar decisión

## 🎵 Integración de Audio

### **Configuración de Audio Espacial**

```csharp
// En tu AudioManager.cs
public void ConfigureSpatialAudio()
{
    // Configurar audio espacial
    AudioSource[] audioSources = FindObjectsOfType<AudioSource>();
    
    foreach (AudioSource source in audioSources)
    {
        source.spatialBlend = 1f; // 3D
        source.maxDistance = 10f;
        source.rolloffMode = AudioRolloffMode.Logarithmic;
    }
}
```

### **Sincronización de Audio**

```csharp
// En tu AudioManager.cs
public void SendAudioData(float[] audioData)
{
    if (audioIntegration != null)
    {
        audioIntegration.SendAudioData(audioData);
    }
}

public void ReceiveAudioData(AudioData audioData)
{
    // Reproducir audio recibido
    AudioSource source = GetPlayerAudioSource(audioData.usuarioId);
    if (source != null)
    {
        PlayAudioData(source, audioData.audioData, audioData.sampleRate);
    }
}
```

## 🏠 Gestión de Salas

### **Crear Sala**

```csharp
// En tu RoomManager.cs
public void CreateRoomForSession(int sesionId, string roomName)
{
    // Tu lógica de crear sala en Unity
    // ...
    
    // Crear sala en Laravel
    if (roomIntegration != null)
    {
        roomIntegration.CreateRoom(roomName, 10);
    }
}
```

### **Unirse a Sala**

```csharp
// En tu RoomManager.cs
public void JoinRoomById(string roomId)
{
    // Tu lógica de unirse a sala en Unity
    // ...
    
    // Unirse a sala en Laravel
    if (roomIntegration != null)
    {
        roomIntegration.JoinRoom(roomId);
    }
}
```

### **Sincronización de Jugadores**

```csharp
// En tu PlayerController.cs
private void Update()
{
    // Tu lógica de movimiento
    // ...
    
    // Sincronizar posición con Laravel
    if (roomIntegration != null && Time.frameCount % 30 == 0)
    {
        var playerData = new PlayerData
        {
            usuarioId = this.usuarioId,
            position = transform.position,
            rotation = transform.rotation,
            audioEnabled = this.audioEnabled,
            microfonoActivo = this.microfonoActivo
        };
        
        roomIntegration.SyncPlayer(playerData);
    }
}
```

## 🔄 Flujo de Integración Completo

### **1. Inicialización**
```csharp
void Start()
{
    // 1. Login en Laravel
    LaravelAPI.Instance.Login("usuario@example.com", "password");
    
    // 2. Configurar integraciones
    roomIntegration.Setup();
    audioIntegration.Setup();
    
    // 3. Crear o unirse a sala
    roomIntegration.CreateRoom("Mi Sala", 10);
}
```

### **2. Durante el Juego**
```csharp
void Update()
{
    // 1. Sincronizar jugadores
    SyncPlayers();
    
    // 2. Sincronizar audio
    SyncAudio();
    
    // 3. Procesar eventos de Laravel
    ProcessLaravelEvents();
}
```

### **3. Al Finalizar**
```csharp
void OnApplicationQuit()
{
    // 1. Salir de sala
    roomIntegration.LeaveRoom();
    
    // 2. Logout
    LaravelAPI.Instance.Logout();
}
```

## 🐛 Troubleshooting

### **Problemas Comunes**

#### 1. Error de CORS
**Síntoma:** Error "CORS policy" en Unity
**Solución:**
```bash
php artisan config:clear
php artisan cache:clear
```

#### 2. Audio no se sincroniza
**Síntoma:** Audio no se escucha entre jugadores
**Solución:**
```csharp
// Verificar que AudioIntegration esté configurado
if (audioIntegration != null)
{
    audioIntegration.SetMicrophoneActive(true);
}
```

#### 3. Jugadores no se sincronizan
**Síntoma:** Posiciones no se actualizan
**Solución:**
```csharp
// Verificar que RoomIntegration esté configurado
if (roomIntegration != null)
{
    roomIntegration.SyncPlayer(playerData);
}
```

#### 4. Sala no se crea
**Síntoma:** Error al crear sala
**Solución:**
```csharp
// Verificar que el usuario esté logueado
if (LaravelAPI.Instance.isConnected)
{
    roomIntegration.CreateRoom("Mi Sala", 10);
}
```

## 📊 Monitoreo

### **Logs de Unity**
```csharp
// Habilitar logs detallados
Debug.Log($"Sala creada: {roomId}");
Debug.Log($"Jugador sincronizado: {usuarioId}");
Debug.Log($"Audio enviado: {audioData.Length} samples");
```

### **Logs de Laravel**
```bash
# Ver logs en tiempo real
tail -f storage/logs/laravel.log

# Ver logs específicos de Unity
grep "Unity" storage/logs/laravel.log
```

## ✅ Checklist de Integración

- [ ] Proyecto Unity copiado a `/unity-integration/unity-project/`
- [ ] Scripts de integración agregados
- [ ] Scripts existentes modificados
- [ ] Referencias configuradas en Unity
- [ ] UnityConfig creado y configurado
- [ ] Build settings configurados
- [ ] Pruebas de conexión exitosas
- [ ] Audio compartido funcionando
- [ ] Sincronización de jugadores funcionando
- [ ] Salas creándose correctamente

## 🎉 ¡Listo!

Tu proyecto Unity con sala y audio compartido está ahora completamente integrado con Laravel. Puedes:

- ✅ Crear y gestionar salas desde Unity
- ✅ Sincronizar jugadores en tiempo real
- ✅ Compartir audio espacial
- ✅ Integrar con el sistema de diálogos
- ✅ Gestionar sesiones de juicios

**¡Disfruta tu simulador de juicios integrado! 🎮⚖️**

