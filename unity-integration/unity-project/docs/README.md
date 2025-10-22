# 🎮 Documentación del Proyecto Unity - Simulador de Juicios Orales

## 📋 Tabla de Contenidos

1. [Resumen del Proyecto](#resumen-del-proyecto)
2. [Arquitectura del Sistema](#arquitectura-del-sistema)
3. [Estructura del Proyecto](#estructura-del-proyecto)
4. [Scripts y Componentes](#scripts-y-componentes)
5. [Configuración](#configuración)
6. [Integración con Laravel](#integración-con-laravel)
7. [Integración con Photon PUN2](#integración-con-photon-pun2)
8. [Integración con PeerJS](#integración-con-peerjs)
9. [Flujo de Trabajo](#flujo-de-trabajo)
10. [Guía de Desarrollo](#guía-de-desarrollo)
11. [Troubleshooting](#troubleshooting)
12. [TODO List](#todo-list)

---

## 🎯 Resumen del Proyecto

**Simulador de Juicios Orales** es un proyecto Unity 3D que simula juicios orales en tiempo real con múltiples participantes. El proyecto integra Unity con Laravel (backend), Photon PUN2 (multiplayer) y PeerJS (audio compartido) para crear una experiencia inmersiva de simulación legal.

### Características Principales
- ✅ **Multiplayer en Tiempo Real**: Hasta 20 participantes simultáneos
- ✅ **Sistema de Diálogos Ramificados**: Diálogos interactivos con múltiples opciones
- ✅ **Audio Compartido**: Comunicación de voz entre participantes
- ✅ **Sistema de Roles**: 20 roles diferentes predefinidos
- ✅ **Integración Laravel**: Sincronización completa con backend
- ✅ **WebGL Support**: Funciona en navegadores web
- ✅ **Sistema de Evaluación**: Puntuación automática de respuestas

---

## 🏗️ Arquitectura del Sistema

### Diagrama de Arquitectura

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Unity Client  │    │   Laravel API   │    │   Photon Cloud  │
│                 │    │                 │    │                 │
│ ┌─────────────┐ │    │ ┌─────────────┐ │    │ ┌─────────────┐ │
│ │ GameInitial │ │◄──►│ │ Auth API    │ │    │ │ Room Mgmt   │ │
│ └─────────────┘ │    │ └─────────────┘ │    │ └─────────────┘ │
│ ┌─────────────┐ │    │ ┌─────────────┐ │    │ ┌─────────────┐ │
│ │ LaravelAPI  │ │◄──►│ │ Dialog API  │ │    │ │ Player Sync │ │
│ └─────────────┘ │    │ └─────────────┘ │    │ └─────────────┘ │
│ ┌─────────────┐ │    │ ┌─────────────┐ │    │ ┌─────────────┐ │
│ │ DialogoUI   │ │◄──►│ │ Real-time   │ │    │ │ Voice Chat  │ │
│ └─────────────┘ │    │ └─────────────┘ │    │ └─────────────┘ │
│ ┌─────────────┐ │    └─────────────────┘    └─────────────────┘
│ │ Photon Int. │ │
│ └─────────────┘ │
└─────────────────┘
```

### Componentes Principales

1. **Unity Client**: Aplicación Unity 3D
2. **Laravel API**: Backend con autenticación JWT
3. **Photon PUN2**: Servicio de multiplayer
4. **PeerJS**: Servicio de audio compartido
5. **Base de Datos**: MySQL para persistencia

---

## 📁 Estructura del Proyecto

```
unity-project/
├── Assets/
│   ├── Scripts/                    # Scripts C# del proyecto
│   │   ├── GameInitializer.cs     # Inicializador principal
│   │   ├── LaravelAPI.cs          # Cliente HTTP para Laravel
│   │   ├── DialogoUI.cs           # UI de diálogos
│   │   ├── UnityConfig.cs         # Configuración centralizada
│   │   ├── UnityLaravelIntegration.cs # Integración completa
│   │   ├── ControlCamaraJugador.cs # Control de cámara
│   │   ├── GestionRedJugador.cs   # Gestión de red Photon
│   │   ├── RedesJugador.cs        # Red de jugador
│   │   ├── RoleSelectionUI.cs     # UI de selección de roles
│   │   └── RoleLabelDisplay.cs    # Visualización de roles
│   ├── Scenes/                     # Escenas del proyecto
│   │   ├── Sala.unity             # Escena principal de sala
│   │   └── SalaPrincipal.unity    # Escena de lobby
│   ├── Resources/                  # Recursos del proyecto
│   │   ├── ambiente/              # Texturas de ambiente
│   │   └── WallArt_*.png          # Texturas de paredes
│   └── StreamingAssets/           # Archivos de configuración
│       └── unity-config.json      # Configuración de Unity
├── ProjectSettings/               # Configuración del proyecto
├── Packages/                      # Paquetes de Unity
├── Library/                       # Archivos generados por Unity
├── UserSettings/                  # Configuración de usuario
└── docs/                         # Documentación del proyecto
    ├── README.md                 # Este archivo
    ├── API_REFERENCE.md          # Referencia de API
    ├── DEVELOPMENT_GUIDE.md      # Guía de desarrollo
    └── TROUBLESHOOTING.md        # Guía de solución de problemas
```

---

## 📝 Scripts y Componentes

### 1. GameInitializer.cs
**Propósito**: Punto de entrada principal del juego

**Características**:
- Singleton pattern para acceso global
- Sistema de eventos robusto
- Auto-login para testing
- Panel de debug integrado
- Configuración dinámica

**Métodos Principales**:
```csharp
public void InitializeGame()           // Inicializar el juego
public void RestartGame()              // Reiniciar el juego
public void ChangeSession(int id)      // Cambiar sesión
public string GetGameStatus()          // Obtener estado del juego
```

### 2. LaravelAPI.cs
**Propósito**: Cliente HTTP para comunicación con Laravel

**Características**:
- Singleton pattern
- Autenticación JWT
- RESTful API completa
- Server-Sent Events (SSE)
- Manejo robusto de errores

**Endpoints Implementados**:
```csharp
// Autenticación
POST /api/unity/auth/login
GET  /api/unity/auth/status
POST /api/unity/auth/refresh
POST /api/unity/auth/logout

// Diálogos
GET  /api/unity/sesion/{id}/dialogo-estado
GET  /api/unity/sesion/{id}/respuestas-usuario/{user}
POST /api/unity/sesion/{id}/enviar-decision
POST /api/unity/sesion/{id}/notificar-hablando

// Tiempo Real
GET  /api/unity/sesion/{id}/events
POST /api/unity/sesion/{id}/broadcast
```

### 3. DialogoUI.cs
**Propósito**: Controlador de UI para el sistema de diálogos

**Características**:
- UI reactiva basada en eventos
- Generación dinámica de botones
- Manejo de estados de UI
- Feedback visual de selección
- Diseño responsivo

**Funcionalidades**:
- Panel de login integrado
- Visualización de diálogos en tiempo real
- Selección de respuestas interactiva
- Manejo de turnos de usuario

### 4. UnityConfig.cs
**Propósito**: ScriptableObject para configuración centralizada

**Configuraciones Incluidas**:
```csharp
// API Configuration
public string apiBaseURL = "http://localhost:8000/api";
public string unityVersion = "2022.3.15f1";
public string unityPlatform = "WebGL";

// Photon Configuration
public string photonAppId = "YOUR_PHOTON_APP_ID";
public string photonRegion = "us";

// PeerJS Configuration
public string peerjsHost = "juiciosorales.site";
public int peerjsPort = 443;
public bool peerjsSecure = true;

// Audio Configuration
public bool echoCancellation = true;
public bool noiseSuppression = true;
public int sampleRate = 44100;
```

### 5. UnityLaravelIntegration.cs
**Propósito**: Integración completa entre Unity, Photon y Laravel

**Características**:
- Integración Photon PUN2
- Integración PeerJS
- Sincronización con Laravel
- Gestión de salas virtuales
- Sincronización de jugadores

### 6. Scripts de Red (Photon)

#### GestionRedJugador.cs
- Gestión de conexión Photon
- Sistema de selección de roles
- Gestión de salas
- Integración con PeerJS

#### ControlCamaraJugador.cs
- Control de cámara por jugador
- Gestión de AudioListener
- Integración con Photon

#### RedesJugador.cs
- Deshabilitar scripts en jugadores remotos
- Gestión de scripts por jugador

### 7. Scripts de UI

#### RoleSelectionUI.cs
- UI para selección de roles
- Generación dinámica de botones
- Validación de roles disponibles
- 20 roles predefinidos

#### RoleLabelDisplay.cs
- Visualización del rol del jugador
- Actualización en tiempo real
- Sincronización con Photon

---

## ⚙️ Configuración

### Configuración de Unity

#### Build Settings
- **Plataforma**: WebGL
- **Resolución**: 1024x768 (WebGL: 960x600)
- **Color Space**: Linear
- **Stereo Rendering**: Mono

#### Paquetes Requeridos
```json
{
  "com.unity.render-pipelines.universal": "17.2.0",
  "com.unity.inputsystem": "1.14.2",
  "com.unity.cinemachine": "2.10.4",
  "com.unity.postprocessing": "3.5.0",
  "com.unity.ugui": "2.0.0"
}
```

### Configuración de Producción

#### unity-config.json
```json
{
  "laravelApiBaseUrl": "https://juiciosorales.site/api",
  "photonAppId": "2ec23c58-5cc4-419d-8214-13abad14a02f",
  "environment": "production",
  "debugMode": false,
  "enableLogging": true,
  "maxRetries": 3,
  "timeout": 30,
  "version": "1.0.0"
}
```

### Configuración de Photon

#### PhotonServerSettings
- **App ID**: 2ec23c58-5cc4-419d-8214-13abad14a02f
- **Region**: us
- **Max Players**: 20

---

## 🔗 Integración con Laravel

### Autenticación JWT
```csharp
// Login
LaravelAPI.Instance.Login("email", "password");

// Verificar estado
LaravelAPI.Instance.CheckServerStatus();

// Renovar token
LaravelAPI.Instance.RefreshToken();
```

### Comunicación de Diálogos
```csharp
// Obtener estado del diálogo
LaravelAPI.Instance.GetDialogoEstado(sesionId);

// Obtener respuestas disponibles
LaravelAPI.Instance.GetRespuestasUsuario(sesionId, usuarioId);

// Enviar decisión
LaravelAPI.Instance.EnviarDecision(sesionId, usuarioId, respuestaId, texto, tiempo);
```

### Eventos en Tiempo Real
```csharp
// Iniciar escucha de eventos
LaravelAPI.Instance.StartRealtimeEvents(sesionId);

// Suscribirse a eventos
LaravelAPI.OnDialogoUpdated += OnDialogoUpdated;
LaravelAPI.OnRespuestasReceived += OnRespuestasReceived;
```

---

## 🌐 Integración con Photon PUN2

### Conexión y Salas
```csharp
// Conectar a Photon
PhotonNetwork.ConnectUsingSettings();

// Crear sala
PhotonNetwork.CreateRoom(roomName, roomOptions);

// Unirse a sala
PhotonNetwork.JoinRoom(roomName);
```

### Sincronización de Jugadores
```csharp
// Sincronizar posición
photonView.RPC("SyncPosition", RpcTarget.All, position, rotation);

// Sincronizar estado de audio
photonView.RPC("SyncAudioState", RpcTarget.All, microfonoActivo, audioEnabled);
```

### Callbacks de Photon
```csharp
public override void OnJoinedRoom()
{
    // Lógica cuando se une a una sala
}

public override void OnPlayerEnteredRoom(Player newPlayer)
{
    // Lógica cuando un jugador entra
}

public override void OnPlayerLeftRoom(Player otherPlayer)
{
    // Lógica cuando un jugador sale
}
```

---

## 🎤 Integración con PeerJS

### Inicialización
```javascript
// En el HTML template
function initVoiceCall(roomId, actorId) {
    const peer = new Peer(actorId, {
        host: 'juiciosorales.site',
        port: 443,
        secure: true,
        path: '/peerjs'
    });
    
    peer.on('open', function(id) {
        // Notificar a Unity que PeerJS está listo
        gameInstance.SendMessage('UnityLaravelIntegration', 'OnVoiceReady', id);
    });
}
```

### Comunicación de Audio
```csharp
// En Unity
public void OnVoiceReady(string myPeerId)
{
    // Compartir PeerID con otros jugadores
    photonView.RPC("RecibirPeerId", RpcTarget.Others, myPeerId);
}

[PunRPC]
public void RecibirPeerId(string peerId)
{
    // Llamar a JavaScript para conectar con este peer
    Application.ExternalCall("callPeer", peerId);
}
```

---

## 🔄 Flujo de Trabajo

### 1. Inicialización
1. **GameInitializer** inicia el juego
2. **UnityConfig** aplica configuración
3. **LaravelAPI** se conecta al backend
4. **Photon** se conecta al servicio de multiplayer
5. **PeerJS** se inicializa para audio

### 2. Selección de Roles
1. **GestionRedJugador** conecta a Photon
2. **RoleSelectionUI** muestra roles disponibles
3. Usuario selecciona rol
4. Rol se guarda en propiedades de Photon
5. Usuario se une a sala

### 3. Simulación de Juicio
1. **LaravelAPI** obtiene estado del diálogo
2. **DialogoUI** muestra diálogo actual
3. Si es turno del usuario, muestra respuestas
4. Usuario selecciona respuesta
5. **LaravelAPI** envía decisión
6. Sistema actualiza estado y notifica a todos

### 4. Comunicación de Audio
1. **PeerJS** establece conexiones de audio
2. Jugadores pueden hablar entre sí
3. Audio se sincroniza en tiempo real
4. Estado de micrófono se sincroniza

---

## 🛠️ Guía de Desarrollo

### Configuración del Entorno

#### 1. Instalar Unity
- **Versión**: Unity 6000.2.8f1 o superior
- **Módulos**: WebGL Build Support
- **Paquetes**: URP, Input System, Cinemachine

#### 2. Configurar Photon
1. Crear cuenta en [Photon Engine](https://www.photonengine.com/)
2. Crear nueva aplicación
3. Copiar App ID
4. Configurar en `UnityConfig.cs`

#### 3. Configurar Laravel
1. Asegurar que Laravel esté corriendo
2. Verificar endpoints de API
3. Configurar CORS para Unity

### Desarrollo de Nuevas Funcionalidades

#### 1. Crear Nuevo Script
```csharp
using UnityEngine;
using JuiciosSimulator.API;

namespace JuiciosSimulator.Features
{
    public class NewFeature : MonoBehaviour
    {
        // Implementar funcionalidad
    }
}
```

#### 2. Integrar con Laravel
```csharp
// Suscribirse a eventos
LaravelAPI.OnUserLoggedIn += OnUserLoggedIn;

// Hacer llamadas a API
LaravelAPI.Instance.GetDialogoEstado(sesionId);
```

#### 3. Integrar con Photon
```csharp
// Usar RPC para sincronización
photonView.RPC("MethodName", RpcTarget.All, parameter);

// Implementar callbacks
public override void OnPlayerEnteredRoom(Player newPlayer)
{
    // Lógica
}
```

### Testing

#### 1. Testing en Editor
- Usar `GameInitializer` con auto-login
- Verificar logs en Console
- Usar Debug Panel

#### 2. Testing WebGL
- Build para WebGL
- Probar en navegador
- Verificar consola del navegador

#### 3. Testing Multiplayer
- Abrir múltiples instancias
- Probar sincronización
- Verificar audio

---

## 🐛 Troubleshooting

### Problemas Comunes

#### 1. Error de CORS
**Síntoma**: Error "CORS policy" en navegador
**Solución**:
- Verificar configuración CORS en Laravel
- Asegurar que la URL de Unity esté en `allowed_origins`

#### 2. Error de JWT
**Síntoma**: Error 401 "Token expired"
**Solución**:
- Implementar refresh automático de token
- Verificar configuración JWT en Laravel

#### 3. Error de Photon
**Síntoma**: No se puede conectar a Photon
**Solución**:
- Verificar App ID de Photon
- Verificar conexión a internet
- Verificar región configurada

#### 4. Error de PeerJS
**Síntoma**: Audio no funciona
**Solución**:
- Verificar que el micrófono esté habilitado
- Verificar configuración de PeerJS
- Verificar que esté en HTTPS en producción

#### 5. Unity no se conecta a Laravel
**Síntoma**: Error de conexión HTTP
**Solución**:
- Verificar que Laravel esté corriendo
- Verificar URL en `LaravelAPI.cs`
- Verificar logs de Laravel

### Logs de Debug

#### Unity Console
```csharp
Debug.Log("Mensaje informativo");
Debug.LogWarning("Advertencia");
Debug.LogError("Error");
```

#### Laravel Logs
```bash
tail -f storage/logs/laravel.log
```

#### Photon Dashboard
- Ir a [Photon Dashboard](https://dashboard.photonengine.com/)
- Revisar métricas de conexión

---

## 📋 TODO List

### 🔥 Prioridad Alta

#### 1. Optimización de Performance
- [ ] Implementar sistema de pooling para objetos UI
- [ ] Optimizar llamadas a la API (caching)
- [ ] Reducir uso de memoria en WebGL
- [ ] Implementar lazy loading de recursos
- [ ] Optimizar renderizado para WebGL

#### 2. Manejo de Errores Robusto
- [ ] Implementar sistema de retry automático para API
- [ ] Manejo de errores de red más granular
- [ ] Sistema de fallback para conexiones perdidas
- [ ] Recovery automático de sesiones
- [ ] Notificaciones de error más claras para usuarios

#### 3. Seguridad
- [ ] Validación más robusta de datos de entrada
- [ ] Sanitización de datos antes de enviar a Laravel
- [ ] Manejo seguro de tokens JWT (refresh automático)
- [ ] Validación de respuestas del servidor
- [ ] Implementar rate limiting en cliente

### 🚀 Prioridad Media

#### 4. Funcionalidades Adicionales
- [ ] Sistema de chat de texto
- [ ] Grabación de sesiones
- [ ] Sistema de notas personales
- [ ] Indicadores de estado de conexión
- [ ] Sistema de notificaciones push

#### 5. Mejoras de UI/UX
- [ ] Animaciones de transición entre diálogos
- [ ] Efectos visuales para respuestas
- [ ] Sistema de temas personalizables
- [ ] Mejoras en la accesibilidad
- [ ] Soporte para múltiples idiomas

#### 6. Testing y Calidad
- [ ] Implementar tests unitarios
- [ ] Tests de integración automatizados
- [ ] Tests de performance
- [ ] Tests de compatibilidad con navegadores
- [ ] Documentación de API más detallada

### 🔧 Prioridad Baja

#### 7. Optimizaciones Menores
- [ ] Refactoring de código legacy
- [ ] Mejoras en la documentación
- [ ] Optimización de assets
- [ ] Mejoras en el sistema de logs
- [ ] Implementar métricas de uso

#### 8. Funcionalidades Avanzadas
- [ ] Sistema de mods/plugins
- [ ] Integración con sistemas de videoconferencia
- [ ] Soporte para realidad virtual
- [ ] Sistema de analytics avanzado
- [ ] Integración con sistemas de LMS

### 🐛 Bugs Conocidos

#### 9. Bugs a Corregir
- [ ] Fix: Memory leak en generación de botones de respuesta
- [ ] Fix: Race condition en inicialización de PeerJS
- [ ] Fix: Error de sincronización de roles en Photon
- [ ] Fix: Problema de audio en algunos navegadores
- [ ] Fix: Error de timeout en conexiones lentas

### 📚 Documentación

#### 10. Documentación Pendiente
- [ ] Guía de instalación paso a paso
- [ ] Documentación de API completa
- [ ] Guía de troubleshooting detallada
- [ ] Video tutoriales
- [ ] Documentación de arquitectura técnica

### 🔄 Mejoras de Integración

#### 11. Integración con Servicios Externos
- [ ] Integración con Zoom/Teams
- [ ] Integración con Google Meet
- [ ] Soporte para Discord Rich Presence
- [ ] Integración con sistemas de calendario
- [ ] Webhook notifications

### 📊 Monitoreo y Analytics

#### 12. Sistema de Monitoreo
- [ ] Dashboard de métricas en tiempo real
- [ ] Alertas automáticas de errores
- [ ] Sistema de reportes de uso
- [ ] Métricas de performance
- [ ] Análisis de comportamiento de usuarios

---

## 📞 Soporte

### Recursos Adicionales
- **Documentación Laravel**: `/api/documentation` (Swagger)
- **Logs del Sistema**: `storage/logs/laravel.log`
- **Configuración**: `config/cors.php`, `config/jwt.php`
- **Photon Dashboard**: [dashboard.photonengine.com](https://dashboard.photonengine.com/)

### Contacto
- **Email**: soporte@simulador-juicios.com
- **Documentación**: `/docs/`
- **Issues**: GitHub Issues

---

**¡El proyecto Unity está listo para el desarrollo y producción! 🎉**
