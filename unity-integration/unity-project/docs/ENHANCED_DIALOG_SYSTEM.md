# 🎭 Sistema de Diálogos Ramificados Mejorado

## 📋 Tabla de Contenidos

1. [Resumen](#resumen)
2. [Arquitectura del Sistema](#arquitectura-del-sistema)
3. [Componentes Principales](#componentes-principales)
4. [Flujo de Trabajo](#flujo-de-trabajo)
5. [Integración con Laravel](#integración-con-laravel)
6. [Configuración](#configuración)
7. [Uso](#uso)
8. [API Reference](#api-reference)
9. [Troubleshooting](#troubleshooting)

---

## 🎯 Resumen

El Sistema de Diálogos Ramificados Mejorado es una implementación completa que permite a los usuarios unirse a sesiones de Laravel con roles pre-asignados y participar en diálogos interactivos en tiempo real. El sistema integra Unity con Laravel, Photon PUN2 y PeerJS para crear una experiencia de simulación de juicios orales inmersiva.

### Características Principales
- ✅ **Gestión de Sesiones**: Unirse a sesiones por código
- ✅ **Asignación Automática de Roles**: Roles pre-asignados por el instructor
- ✅ **Diálogos Ramificados**: Sistema interactivo de diálogos en tiempo real
- ✅ **Sincronización en Tiempo Real**: Estado de diálogos y participantes
- ✅ **Integración Multiplayer**: Photon PUN2 para salas virtuales
- ✅ **Audio Compartido**: PeerJS para comunicación de voz
- ✅ **UI Avanzada**: Interfaz mejorada con historial y lista de participantes

---

## 🏗️ Arquitectura del Sistema

### Diagrama de Arquitectura

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Unity Client  │    │   Laravel API   │    │   Photon Cloud  │
│                 │    │                 │    │                 │
│ ┌─────────────┐ │    │ ┌─────────────┐ │    │ ┌─────────────┐ │
│ │SessionMgr   │ │◄──►│ │ Session API │ │    │ │ Room Mgmt   │ │
│ └─────────────┘ │    │ └─────────────┘ │    │ └─────────────┘ │
│ ┌─────────────┐ │    │ ┌─────────────┐ │    │ ┌─────────────┐ │
│ │EnhancedUI   │ │◄──►│ │ Dialog API  │ │    │ │ Player Sync │ │
│ └─────────────┘ │    │ └─────────────┘ │    │ └─────────────┘ │
│ ┌─────────────┐ │    │ ┌─────────────┐ │    │ ┌─────────────┐ │
│ │RealtimeSync │ │◄──►│ │ Real-time   │ │    │ │ Voice Chat  │ │
│ └─────────────┘ │    │ └─────────────┘ │    │ └─────────────┘ │
│ ┌─────────────┐ │    └─────────────────┘    └─────────────────┘
│ │PhotonInt    │ │
│ └─────────────┘ │
└─────────────────┘
```

### Flujo de Datos

1. **Inicialización**: `EnhancedGameInitializer` → `EnhancedGameManager`
2. **Gestión de Sesiones**: `SessionManager` ↔ Laravel API
3. **Diálogos**: `EnhancedDialogoUI` ↔ Laravel API
4. **Sincronización**: `RealtimeSyncManager` ↔ Laravel API
5. **Multiplayer**: `EnhancedPhotonIntegration` ↔ Photon Cloud

---

## 🔧 Componentes Principales

### 1. SessionManager.cs
**Propósito**: Gestión completa de sesiones y asignación de roles

**Características**:
- Unirse a sesiones por código
- Asignación automática de roles
- Confirmación de roles
- Gestión de estado de sesión

**Métodos Principales**:
```csharp
public void JoinSessionByCode(string sessionCode)
public void LeaveSession()
public SesionData GetCurrentSession()
public AsignacionRolData GetCurrentRoleAssignment()
```

### 2. EnhancedDialogoUI.cs
**Propósito**: UI avanzada para diálogos ramificados

**Características**:
- Interfaz de selección de sesión
- Visualización de información de rol
- Diálogos interactivos en tiempo real
- Historial de diálogos
- Lista de participantes

**Métodos Principales**:
```csharp
public void SetSessionCode(string code)
public void RefreshDialog()
public bool IsInSession()
public bool IsMyTurn()
```

### 3. RealtimeSyncManager.cs
**Propósito**: Sincronización en tiempo real de estado y participantes

**Características**:
- Sincronización de estado de diálogos
- Actualización de participantes
- Heartbeat para mantener conexión
- Manejo de errores y reconexión

**Métodos Principales**:
```csharp
public void ForceSync()
public void SetSyncInterval(float interval)
public bool IsConnected()
```

### 4. EnhancedPhotonIntegration.cs
**Propósito**: Integración mejorada con Photon PUN2

**Características**:
- Gestión de salas por sesión
- Sincronización de datos de jugadores
- Gestión de audio y voz
- RPC para acciones de jugadores

**Métodos Principales**:
```csharp
public void CreateOrJoinRoom(string roomName)
public void UpdatePlayerData(PlayerData data)
public void SetAudioState(bool microphoneEnabled, bool audioEnabled)
```

### 5. EnhancedGameManager.cs
**Propósito**: Gestor central del sistema

**Características**:
- Inicialización de todos los componentes
- Gestión de estados del juego
- Coordinación entre componentes
- Manejo de errores centralizado

**Métodos Principales**:
```csharp
public void JoinSession(string sessionCode)
public void LeaveSession()
public GameState GetCurrentState()
public bool IsInSession()
```

### 6. EnhancedGameInitializer.cs
**Propósito**: Inicializador mejorado del juego

**Características**:
- Inicialización del sistema mejorado
- Fallback al sistema legacy
- Configuración automática
- Testing y debugging

**Métodos Principales**:
```csharp
public void RestartGame()
public void JoinSession(string sessionCode)
public string GetGameStatus()
```

---

## 🔄 Flujo de Trabajo

### 1. Inicialización del Sistema
```
EnhancedGameInitializer
    ↓
EnhancedGameManager
    ↓
[SessionManager, EnhancedDialogoUI, RealtimeSyncManager, EnhancedPhotonIntegration]
    ↓
Sistema Listo
```

### 2. Unirse a una Sesión
```
Usuario ingresa código de sesión
    ↓
SessionManager.JoinSessionByCode()
    ↓
Laravel API: Obtener información de sesión
    ↓
Laravel API: Obtener asignación de rol
    ↓
Mostrar información de rol
    ↓
Usuario confirma rol
    ↓
Unirse a sala de Photon
    ↓
Iniciar sincronización en tiempo real
```

### 3. Participar en Diálogos
```
RealtimeSyncManager detecta cambio de estado
    ↓
EnhancedDialogoUI actualiza interfaz
    ↓
Si es turno del usuario: Mostrar respuestas
    ↓
Usuario selecciona respuesta
    ↓
Enviar decisión a Laravel API
    ↓
Actualizar historial de diálogos
    ↓
Sincronizar con otros participantes
```

---

## 🔗 Integración con Laravel

### Endpoints Utilizados

#### Gestión de Sesiones
```http
GET /api/unity/sesiones/buscar-por-codigo/{codigo}
GET /api/unity/sesiones/{id}/mi-rol
POST /api/unity/sesiones/{id}/confirmar-rol
GET /api/unity/sesiones/disponibles
```

#### Diálogos
```http
GET /api/unity/sesion/{id}/dialogo-estado
GET /api/unity/sesion/{id}/participantes
POST /api/unity/sesion/{id}/enviar-decision
```

#### Tiempo Real
```http
GET /api/unity/sesion/{id}/events
POST /api/unity/sesion/{id}/heartbeat
```

### Estructura de Datos

#### SesionData
```csharp
public class SesionData
{
    public int id;
    public string nombre;
    public string descripcion;
    public string estado;
    public int max_participantes;
    public int participantes_count;
    public UserData instructor;
    public string unity_room_id;
}
```

#### AsignacionRolData
```csharp
public class AsignacionRolData
{
    public int id;
    public int sesion_id;
    public int usuario_id;
    public int rol_id;
    public bool confirmado;
    public RolData rol;
    public UserData usuario;
}
```

---

## ⚙️ Configuración

### 1. Configuración de Unity

#### Scripts Requeridos
- `EnhancedGameInitializer` (en lugar de `GameInitializer`)
- `EnhancedGameManager`
- `SessionManager`
- `EnhancedDialogoUI`
- `RealtimeSyncManager`
- `EnhancedPhotonIntegration`

#### Configuración de Photon
```csharp
// En UnityConfig.cs
public string photonAppId = "YOUR_PHOTON_APP_ID";
public string photonRegion = "us";
```

#### Configuración de API
```csharp
// En UnityConfig.cs
public string apiBaseURL = "https://juiciosorales.site/api";
public bool debugMode = false;
```

### 2. Configuración de Laravel

#### Rutas API Requeridas
```php
// En routes/api.php
Route::prefix('unity')->group(function () {
    Route::get('sesiones/buscar-por-codigo/{codigo}', [UnitySessionController::class, 'buscarPorCodigo']);
    Route::get('sesiones/{id}/mi-rol', [UnitySessionController::class, 'obtenerMiRol']);
    Route::post('sesiones/{id}/confirmar-rol', [UnitySessionController::class, 'confirmarRol']);
    Route::get('sesiones/disponibles', [UnitySessionController::class, 'disponibles']);
    Route::get('sesion/{id}/participantes', [UnitySessionController::class, 'participantes']);
    Route::post('sesion/{id}/heartbeat', [UnitySessionController::class, 'heartbeat']);
});
```

---

## 🎮 Uso

### 1. Configuración Inicial

#### En Unity
1. Agregar `EnhancedGameInitializer` a la escena
2. Configurar `UnityConfig` con los valores correctos
3. Asignar referencias a los componentes

#### En Laravel
1. Crear sesión de juicio
2. Asignar roles a usuarios
3. Generar código de sesión

### 2. Unirse a una Sesión

#### Desde Unity
```csharp
// Obtener referencia al inicializador
EnhancedGameInitializer initializer = FindObjectOfType<EnhancedGameInitializer>();

// Unirse a sesión
initializer.JoinSession("CODIGO_SESION");
```

#### Desde URL (WebGL)
```
https://tu-sitio.com/unity?session=CODIGO_SESION
```

### 3. Participar en Diálogos

El sistema maneja automáticamente:
- Detección de turnos
- Mostrar respuestas disponibles
- Envío de decisiones
- Sincronización con otros participantes

---

## 📚 API Reference

### SessionManager

#### Eventos
```csharp
public static event Action<SesionData> OnSessionJoined;
public static event Action<AsignacionRolData> OnRoleAssigned;
public static event Action<string> OnSessionError;
public static event Action OnSessionLeft;
```

#### Métodos
```csharp
public void JoinSessionByCode(string sessionCode)
public void LeaveSession()
public SesionData GetCurrentSession()
public AsignacionRolData GetCurrentRoleAssignment()
public bool IsInSession()
```

### EnhancedDialogoUI

#### Eventos
```csharp
public static event Action<DialogoEstado> OnDialogStateChanged;
public static event Action<bool> OnTurnChanged;
public static event Action<RespuestaUsuario> OnResponseSelected;
```

#### Métodos
```csharp
public void SetSessionCode(string code)
public void RefreshDialog()
public bool IsInSession()
public bool IsMyTurn()
```

### RealtimeSyncManager

#### Eventos
```csharp
public static event Action<DialogoEstado> OnDialogStateChanged;
public static event Action<List<Participante>> OnParticipantsChanged;
public static event Action<bool> OnConnectionStatusChanged;
public static event Action<string> OnSyncError;
```

#### Métodos
```csharp
public void ForceSync()
public void SetSyncInterval(float interval)
public bool IsConnected()
public float GetLastSyncTime()
```

---

## 🐛 Troubleshooting

### Problemas Comunes

#### 1. No se puede unir a la sesión
**Síntomas**: Error al unirse a sesión
**Solución**:
- Verificar que el código de sesión sea correcto
- Verificar que la sesión esté activa en Laravel
- Verificar que el usuario tenga rol asignado

#### 2. No se muestran las respuestas
**Síntomas**: Diálogo se muestra pero no hay respuestas
**Solución**:
- Verificar que sea el turno del usuario
- Verificar conexión con Laravel API
- Verificar que el diálogo esté activo

#### 3. Sincronización no funciona
**Síntomas**: Cambios no se sincronizan entre usuarios
**Solución**:
- Verificar conexión de red
- Verificar que RealtimeSyncManager esté activo
- Verificar logs de Laravel

#### 4. Audio no funciona
**Síntomas**: No se escucha audio de otros usuarios
**Solución**:
- Verificar permisos de micrófono
- Verificar conexión a Photon
- Verificar configuración de PeerJS

### Logs de Debug

#### Unity Console
```csharp
Debug.Log("Session joined: " + session.nombre);
Debug.Log("Role assigned: " + role.rol.nombre);
Debug.Log("Dialog state changed: " + dialogState.estado);
```

#### Laravel Logs
```bash
tail -f storage/logs/laravel.log | grep "Unity"
```

#### Photon Dashboard
- Ir a [Photon Dashboard](https://dashboard.photonengine.com/)
- Revisar métricas de conexión

---

## 🚀 Próximas Mejoras

### Funcionalidades Planificadas
- [ ] Grabación de sesiones
- [ ] Análisis de comportamiento
- [ ] Integración con sistemas de videoconferencia
- [ ] Soporte para realidad virtual
- [ ] Sistema de notificaciones push

### Optimizaciones
- [ ] Caching de datos de sesión
- [ ] Compresión de datos de red
- [ ] Optimización de UI para móviles
- [ ] Sistema de backup automático

---

**¡Sistema de Diálogos Ramificados Mejorado implementado exitosamente! 🎉**
