# 📚 Documentación Técnica Completa - Simulador de Juicios Orales

## 📋 Tabla de Contenidos

1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Arquitectura del Sistema](#arquitectura-del-sistema)
3. [Stack Tecnológico](#stack-tecnológico)
4. [Componentes Principales](#componentes-principales)
5. [Base de Datos](#base-de-datos)
6. [API REST](#api-rest)
7. [Integración Unity](#integración-unity)
8. [Sistema de Audio](#sistema-de-audio)
9. [Sistema de Diálogos](#sistema-de-diálogos)
10. [Flujos de Trabajo](#flujos-de-trabajo)
11. [Configuración y Deployment](#configuración-y-deployment)
12. [Seguridad](#seguridad)
13. [Troubleshooting](#troubleshooting)

---

## Resumen Ejecutivo

### Descripción del Proyecto

**Simulador de Juicios Orales** es una plataforma educativa completa que permite a instituciones académicas simular juicios orales en un entorno virtual 3D. El sistema combina un backend Laravel robusto con una aplicación Unity WebGL para crear experiencias inmersivas de aprendizaje donde múltiples estudiantes pueden participar simultáneamente en simulaciones de juicios con roles predefinidos.

### Objetivos Principales

- **Educación Inmersiva**: Proporcionar un entorno virtual 3D realista para la práctica de juicios orales
- **Evaluación Automática**: Sistema de puntuación automática basado en decisiones y respuestas de los estudiantes
- **Multiplayer en Tiempo Real**: Hasta 20 participantes simultáneos con comunicación de voz
- **Gestión Completa**: Panel administrativo para crear sesiones, asignar roles y gestionar diálogos
- **Escalabilidad**: Arquitectura preparada para múltiples sesiones concurrentes

### Características Clave

✅ **Sistema de Autenticación JWT** completo con roles (admin, instructor, alumno)  
✅ **Editor Visual de Diálogos** con drag & drop y sistema ramificado  
✅ **Integración Unity WebGL** para experiencia 3D inmersiva  
✅ **Comunicación de Voz en Tiempo Real** mediante PeerJS  
✅ **Multiplayer Sincronizado** con Photon PUN2  
✅ **Sistema de Evaluación Automática** con puntuaciones y consecuencias  
✅ **Dashboard Interactivo** con estadísticas y reportes  
✅ **API REST Completa** documentada con Swagger  

---

## 🏗️ Arquitectura del Sistema

### Diagrama de Arquitectura General

```
┌─────────────────────────────────────────────────────────────────┐
│                        CLIENTE WEB (Navegador)                   │
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────┐  │
│  │  Dashboard Laravel│  │  Editor Diálogos│  │ Unity WebGL │  │
│  │  (Blade + Alpine)│  │  (Vue/React)     │  │  (Build)    │  │
│  └──────────────────┘  └──────────────────┘  └──────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              │ HTTPS/REST API
                              │ WebSocket (opcional)
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    BACKEND LARAVEL (PHP 8.2+)                    │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────┐ │
│  │  Controllers │  │   Models     │  │   Middleware         │ │
│  │  - Auth      │  │   - User     │  │   - JWT Auth         │ │
│  │  - Sessions  │  │   - Session  │  │   - CORS             │ │
│  │  - Dialogues │  │   - Dialogue │  │   - Rate Limiting    │ │
│  │  - Unity API │  │   - Role     │  │   - Permissions      │ │
│  └──────────────┘  └──────────────┘  └──────────────────────┘ │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────┐ │
│  │  Services    │  │   Routes     │  │   Events/Listeners   │ │
│  │  - Processing│  │   - API      │  │   - Session Events   │ │
│  │  - Evaluation│  │   - Web     │  │   - Dialogue Events  │ │
│  └──────────────┘  └──────────────┘  └──────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
                              │
                              │ 
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    BASE DE DATOS (MySQL/MariaDB)                 │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────┐ │
│  │  users       │  │  sesiones_   │  │   dialogos_v2       │ │
│  │  roles_      │  │  juicios     │  │   nodos_dialogo_v2  │ │
│  │  disponibles │  │  asignaciones│  │   decisiones_v2     │ │
│  │  plantillas_ │  │  _roles      │  │   panel_dialogo_*   │ │
│  └──────────────┘  └──────────────┘  └──────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
                              │
                              │ Unity Client
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│              UNITY WEBGL CLIENT (Navegador)                       │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────┐ │
│  │ LaravelAPI   │  │ Photon PUN2  │  │   PeerJS (Audio)    │ │
│  │ (HTTP REST)  │  │ (Multiplayer)│  │   (WebRTC)          │ │
│  └──────────────┘  └──────────────┘  └──────────────────────┘ │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────┐ │
│  │ GameManager  │  │ DialogueUI    │  │   PlayerController  │ │
│  │ SessionMgr   │  │ RoleManager  │  │   AudioManager       │ │
│  └──────────────┘  └──────────────┘  └──────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
                              │
                              │ External Services
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│              SERVICIOS EXTERNOS                                  │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────┐ │
│  │ Photon Cloud │  │ PeerJS       │  │   STUN/TURN         │ │
│  │ (Multiplayer)│  │ (Audio P2P)  │  │   (WebRTC)          │ │
│  └──────────────┘  └──────────────┘  └──────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
```

### Componentes de la Arquitectura

#### 1. **Capa de Presentación (Frontend)**
- **Dashboard Laravel**: Interfaz web administrativa con Blade + Alpine.js
- **Editor de Diálogos**: Editor visual para crear diálogos ramificados
- **Unity WebGL**: Aplicación 3D ejecutándose en el navegador

#### 2. **Capa de Aplicación (Backend)**
- **Laravel Framework**: Lógica de negocio, autenticación, autorización
- **API REST**: Endpoints para comunicación con Unity y frontend
- **Servicios**: Procesamiento automático, evaluación, notificaciones

#### 3. **Capa de Datos**
- **MySQL/MariaDB**: Base de datos relacional
- **Eloquent ORM**: Mapeo objeto-relacional
- **Migrations**: Control de versiones del esquema

#### 4. **Servicios Externos**
- **Photon PUN2**: Multiplayer en tiempo real
- **PeerJS**: Comunicación de voz P2P
- **STUN/TURN**: Servidores para WebRTC

---

## 💻 Stack Tecnológico

### Backend

| Tecnología | Versión | Propósito |
|------------|---------|-----------|
| **PHP** | 8.2+ | Lenguaje del servidor |
| **Laravel** | 12.x | Framework PHP |
| **MySQL/MariaDB** | 8.0+ | Base de datos |
| **JWT (tymon/jwt-auth)** | 2.2 | Autenticación |
| **Spatie Permissions** | 6.21 | Gestión de permisos |
| **L5-Swagger** | 9.0 | Documentación API |

### Frontend Web

| Tecnología | Versión | Propósito |
|------------|---------|-----------|
| **TailwindCSS** | 3.4+ | Framework CSS |
| **Alpine.js** | 3.x | Interactividad |
| **Vite** | 7.3 | Build tool |
| **Bootstrap** | 5.3 | Componentes UI |

### Unity Client

| Tecnología | Versión | Propósito |
|------------|---------|-----------|
| **Unity Engine** | 2022.3.15f1+ | Motor 3D |
| **Photon PUN2** | Latest | Multiplayer |
| **WebGL** | - | Plataforma de build |
| **C#** | .NET Standard 2.1 | Lenguaje de scripts |

### Comunicación y Audio

| Tecnología | Versión | Propósito |
|------------|---------|-----------|
| **PeerJS** | 1.5.5 | Audio P2P |
| **REST API** | HTTP/1.1 | Comunicación Unity-Laravel |

### Herramientas de Desarrollo

| Herramienta | Propósito |
|-------------|-----------|
| **Composer** | Gestión de dependencias PHP |
| **NPM** | Gestión de dependencias JS |
| **Git** | Control de versiones |


---

## 🧩 Componentes Principales

### Backend Laravel

#### 1. **Sistema de Autenticación**

**Controlador**: `App\Http\Controllers\AuthController`

**Características**:
- Login con JWT tokens
- Refresh tokens automático
- Rate limiting en login
- Prevención de enumeración de usuarios
- Logout con invalidación de tokens

**Endpoints**:
```
POST   /api/auth/login
POST   /api/auth/register
POST   /api/auth/logout
POST   /api/auth/refresh
GET    /api/auth/me
PUT    /api/auth/profile
```

#### 2. **Gestión de Sesiones**

**Controlador**: `App\Http\Controllers\SesionJuicioController`

**Modelo**: `App\Models\SesionJuicio`

**Características**:
- Creación de sesiones de juicio
- Estados: programada, en_curso, finalizada, cancelada
- Asignación de participantes con roles
- Generación de Unity Room ID
- Control de fechas y participantes

**Endpoints**:
```
GET    /api/sesiones
POST   /api/sesiones
GET    /api/sesiones/{id}
PUT    /api/sesiones/{id}
DELETE /api/sesiones/{id}
POST   /api/sesiones/{id}/iniciar
POST   /api/sesiones/{id}/finalizar
POST   /api/sesiones/{id}/agregar-participante
```

#### 3. **Sistema de Diálogos V2**

**Controladores**:
- `App\Http\Controllers\DialogoV2EditorController` - Editor visual
- `App\Http\Controllers\DialogoFlujoController` - Flujo de diálogos
- `App\Http\Controllers\UnityDialogoController` - API Unity

**Modelos**:
- `App\Models\DialogoV2` - Diálogos principales
- `App\Models\NodoDialogoV2` - Nodos del diálogo
- `App\Models\DecisionDialogoV2` - Decisiones de usuarios
- `App\Models\SesionDialogoV2` - Diálogos en sesiones

**Características**:
- Diálogos ramificados con múltiples nodos
- Sistema de opciones y respuestas
- Puntuación automática
- Consecuencias por decisiones
- Historial completo de decisiones

#### 4. **Sistema de Roles**

**Controlador**: `App\Http\Controllers\RolDisponibleController`

**Modelo**: `App\Models\RolDisponible`

**Roles Predefinidos**:
- Juez, Fiscal, Defensa, Testigo1, Testigo2
- Policía1, Policía2, Psicólogo, Acusado
- Secretario, Abogado1, Abogado2, Perito1, Perito2
- Víctima, Acusador, Periodista, Público1, Público2, Observador

#### 5. **API Unity**

**Controladores Especializados**:
- `App\Http\Controllers\UnityAuthController` - Autenticación Unity
- `App\Http\Controllers\UnitySessionController` - Sesiones Unity
- `App\Http\Controllers\UnityDialogoController` - Diálogos Unity
- `App\Http\Controllers\UnityRealtimeController` - Tiempo real
- `App\Http\Controllers\UnityRoomController` - Salas Unity

**Middleware**: `unity.auth` - Autenticación JWT para Unity

### Unity Client

#### 1. **LaravelAPI.cs**

**Namespace**: `JuiciosSimulator.API`

**Responsabilidades**:
- Comunicación HTTP con Laravel
- Autenticación JWT
- Obtención de sesiones activas
- Carga de diálogos
- Envío de decisiones

**Configuración**:
```csharp
public string baseURL = "http://localhost:8000/api";
public string authToken = "";
public UserData currentUser;
public SessionData currentSessionData;
```

#### 2. **GestionRedJugador.cs**

**Responsabilidades**:
- Conexión a Photon PUN2
- Gestión de roles asignados
- Instanciación de jugadores
- Inicialización del sistema de audio
- Sincronización con Laravel

**Flujo**:
1. Conectar a Photon
2. Obtener rol desde Laravel
3. Unirse a sala de sesión
4. Instanciar jugador con rol
5. Inicializar audio (PeerJS)

#### 3. **SessionManager.cs**

**Namespace**: `JuiciosSimulator.Session`

**Responsabilidades**:
- Búsqueda de sesiones por código
- Obtención de rol asignado
- Confirmación de rol
- Gestión de UI de sesiones

#### 4. **DialogueManager.cs**

**Namespace**: `JuiciosSimulator.Dialogue`

**Responsabilidades**:
- Gestión de diálogos en tiempo real
- Sincronización de estado
- Procesamiento de respuestas
- Actualización de UI

---

## 🗄️ Base de Datos

### Esquema Principal

#### Tablas de Usuarios y Autenticación

**users**
- `id`, `name`, `apellido`, `email`, `password`
- `tipo`: enum('admin', 'instructor', 'alumno')
- `activo`: boolean
- `configuracion`: JSON

**login_attempts**
- Registro de intentos de login para seguridad

#### Tablas de Sesiones

**sesiones_juicios**
- `id`, `nombre`, `descripcion`, `tipo`
- `instructor_id`, `plantilla_id`
- `estado`: enum('programada', 'en_curso', 'finalizada', 'cancelada')
- `fecha_inicio`, `fecha_fin`
- `max_participantes`
- `unity_room_id`
- `configuracion`: JSON

**plantillas_sesiones**
- Plantillas reutilizables para crear sesiones

**asignaciones_roles**
- `sesion_id`, `usuario_id`, `rol_id`
- `confirmado`: boolean
- `fecha_asignacion`, `notas`

**roles_disponibles**
- `id`, `nombre`, `descripcion`
- `color`, `icono`
- `activo`: boolean

#### Tablas de Diálogos V2

**dialogos_v2**
- `id`, `nombre`, `descripcion`
- `estado`: enum('borrador', 'activo', 'archivado')
- `configuracion`: JSON

**nodos_dialogo_v2**
- `id`, `dialogo_id`
- `tipo`: enum('inicio', 'dialogo', 'decision', 'final')
- `titulo`, `contenido`
- `posicion_x`, `posicion_y`
- `configuracion`: JSON

**respuestas_dialogo_v2**
- `id`, `nodo_id`
- `letra`: char (A, B, C, D)
- `texto`, `puntuacion`
- `consecuencias`: JSON

**sesiones_dialogos_v2**
- `id`, `sesion_id`, `dialogo_id`
- `estado`: enum('iniciado', 'en_curso', 'pausado', 'finalizado')
- `nodo_actual_id`
- `fecha_inicio`, `fecha_fin`

**decisiones_dialogo_v2**
- `id`, `sesion_dialogo_id`, `usuario_id`, `nodo_id`
- `respuesta_id`, `puntuacion`
- `tiempo_respuesta`: integer (segundos)
- `consecuencias_aplicadas`: JSON
- `fecha_decision`

#### Tablas de Unity

**unity_rooms**
- `id`, `room_id`, `sesion_id`
- `estado`: enum('activa', 'cerrada')
- `configuracion`: JSON

**unity_room_events**
- `id`, `room_id`, `tipo`, `datos`: JSON
- `timestamp`

### Relaciones Principales

```
User (1) ──< (N) AsignacionRol (N) >── (1) RolDisponible
User (1) ──< (N) SesionJuicio (instructor)
SesionJuicio (1) ──< (N) AsignacionRol
SesionJuicio (1) ──< (N) SesionDialogoV2
DialogoV2 (1) ──< (N) NodoDialogoV2
NodoDialogoV2 (1) ──< (N) RespuestaDialogoV2
SesionDialogoV2 (1) ──< (N) DecisionDialogoV2
```

---

## 🌐 API REST

### Health Check

Endpoints públicos para verificar el estado de la API y sus servicios.

#### Health Check Completo
```http
GET /api/health

Response 200 (Healthy):
{
  "status": "healthy",
  "timestamp": "2025-01-09T12:00:00.000000Z",
  "version": "1.0.0",
  "environment": "local",
  "checks": {
    "database": {
      "status": "healthy",
      "message": "Conexión a base de datos exitosa",
      "connection": "mysql",
      "host": "127.0.0.1",
      "database": "juiciosorales"
    },
    "cache": {
      "status": "healthy",
      "message": "Sistema de caché funcionando",
      "driver": "file"
    },
    "storage": {
      "status": "healthy",
      "message": "Directorio de almacenamiento escribible",
      "path": "/path/to/storage",
      "writable": true
    }
  },
  "server": {
    "php_version": "8.5.0",
    "laravel_version": "12.34.0",
    "timezone": "UTC",
    "locale": "es",
    "debug_mode": true
  },
  "statistics": {
    "total_users": 25,
    "active_sessions": 2,
    "total_sessions": 15,
    "total_dialogues": 8
  }
}

Response 503 (Unhealthy):
{
  "status": "unhealthy",
  "timestamp": "2025-01-09T12:00:00.000000Z",
  "checks": {
    "database": {
      "status": "unhealthy",
      "message": "Error de conexión a base de datos: ..."
    }
  }
}
```

#### Health Check Simple (Ping)
```http
GET /api/health/ping

Response 200:
{
  "status": "ok",
  "message": "API funcionando correctamente",
  "timestamp": "2025-01-09T12:00:00.000000Z"
}
```

#### Health Check Detallado
```http
GET /api/health/detailed

Response 200:
{
  "status": "healthy",
  "timestamp": "2025-01-09T12:00:00.000000Z",
  "checks": { ... },
  "server": { ... },
  "statistics": { ... },
  "system": {
    "memory_usage": "45.2 MB",
    "memory_peak": "52.1 MB",
    "memory_limit": "256M",
    "max_execution_time": "60",
    "upload_max_filesize": "2M",
    "post_max_size": "8M"
  },
  "extensions": {
    "pdo": true,
    "pdo_mysql": true,
    "mbstring": true,
    "curl": true,
    "zip": true,
    "json": true,
    "openssl": true
  }
}
```

**Uso Recomendado**:
- **Monitoreo**: Usar `/api/health` para verificación periódica del estado
- **Load Balancers**: Usar `/api/health/ping` para checks rápidos
- **Debugging**: Usar `/api/health/detailed` para diagnóstico completo

### Autenticación

#### Login
```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "alumno@example.com",
  "password": "password"
}

Response:
{
  "success": true,
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "token_type": "bearer",
    "expires_in": 3600,
    "user": { ... }
  }
}
```

#### Obtener Usuario Actual
```http
GET /api/auth/me
Authorization: Bearer {token}

Response:
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "Juan",
      "email": "alumno@example.com",
      "tipo": "alumno"
    }
  }
}
```

### Sesiones

#### Listar Sesiones
```http
GET /api/sesiones?estado=en_curso&instructor_id=1
Authorization: Bearer {token}
```

#### Crear Sesión
```http
POST /api/sesiones
Authorization: Bearer {token}
Content-Type: application/json

{
  "nombre": "Juicio Penal - Caso OXXO",
  "descripcion": "Simulación de juicio penal",
  "plantilla_id": 1,
  "max_participantes": 20,
  "participantes": [
    {
      "usuario_id": 2,
      "rol_id": 1,
      "notas": "Asignado como Juez"
    }
  ]
}
```

### API Unity

#### Obtener Sesión Activa
```http
GET /api/unity/auth/session/active
Authorization: Bearer {token}

Response:
{
  "success": true,
  "data": {
    "session": {
      "id": 1,
      "nombre": "Juicio Penal",
      "estado": "en_curso",
      ...
    },
    "role": {
      "id": 1,
      "nombre": "Juez",
      "descripcion": "..."
    },
    "assignment": {
      "id": 1,
      "confirmado": true
    }
  }
}
```

#### Buscar Sesión por Código
```http
GET /api/unity/sesiones/buscar-por-codigo/{codigo}
Authorization: Bearer {token}
```

#### Obtener Mi Rol
```http
GET /api/unity/sesiones/{id}/mi-rol
Authorization: Bearer {token}
```

#### Confirmar Rol
```http
POST /api/unity/sesiones/{id}/confirmar-rol
Authorization: Bearer {token}
Content-Type: application/json

{
  "confirmado": true
}
```

#### Obtener Estado del Diálogo
```http
GET /api/unity/sesion/{sesionId}/dialogo-estado
Authorization: Bearer {token}
```

#### Enviar Decisión
```http
POST /api/unity/sesion/{sesionId}/enviar-decision
Authorization: Bearer {token}
Content-Type: application/json

{
  "nodo_id": 5,
  "respuesta_id": 12,
  "tiempo_respuesta": 45
}
```

### Health Check

Endpoints públicos para monitoreo y verificación del estado de la API:

- **Health Check Completo**: `GET /api/health`
  - Verifica: Base de datos, caché, almacenamiento
  - Incluye: Estadísticas de la aplicación, información del servidor
  - Códigos: `200` (healthy), `503` (unhealthy)

- **Health Check Simple**: `GET /api/health/ping`
  - Verificación rápida de que el servidor responde
  - Útil para load balancers y monitoreo básico

- **Health Check Detallado**: `GET /api/health/detailed`
  - Incluye información del sistema, memoria, extensiones PHP
  - Útil para debugging y diagnóstico

### Documentación Completa

La API está documentada con Swagger/OpenAPI:
- **URL**: `http://localhost:8000/api/documentation`
- **Formato**: OpenAPI 3.0
- **Autenticación**: Bearer Token (JWT)

---

## 🎮 Integración Unity

### Arquitectura de Integración

```
Unity WebGL Build
    │
    ├─── LaravelAPI.cs ──────► Laravel REST API
    │         │
    │         ├─── Autenticación JWT
    │         ├─── Obtener Sesión Activa
    │         ├─── Cargar Diálogo
    │         └─── Enviar Decisiones
    │
    ├─── GestionRedJugador.cs ──► Photon PUN2
    │         │
    │         ├─── Conectar a Photon
    │         ├─── Unirse a Sala
    │         ├─── Sincronizar Jugadores
    │         └─── Inicializar Audio
    │
    └─── JavaScript (index.html) ──► PeerJS
              │
              ├─── initVoiceCall()
              ├─── callPeer()
              └─── WebRTC Audio
```

### Flujo de Conexión Unity

1. **Inicialización**
   ```
   GameInitializer.Start()
   → LaravelAPI.Login()
   → Obtener token JWT
   ```

2. **Obtener Sesión**
   ```
   LaravelAPI.GetActiveSession()
   → Obtener sesión activa del usuario
   → Obtener rol asignado
   ```

3. **Conectar a Photon**
   ```
   GestionRedJugador.ConnectToPhoton()
   → PhotonNetwork.ConnectUsingSettings()
   → OnConnectedToMaster()
   → JoinLobby()
   ```

4. **Unirse a Sala**
   ```
   GetAssignedRoleFromSession()
   → JoinSessionRoom()
   → OnJoinedRoom()
   → InitializeAudioSystem()
   ```

5. **Inicializar Audio**
   ```
   Application.ExternalCall("initVoiceCall", roomId, actorId)
   → JavaScript: initVoiceCall()
   → PeerJS: Crear Peer
   → Notificar a Unity: OnVoiceReady()
   ```

### Scripts Principales de Unity

#### LaravelAPI.cs
- **Ubicación**: `Assets/Scripts/LaravelAPI.cs`
- **Responsabilidades**:
  - Comunicación HTTP con Laravel
  - Gestión de tokens JWT
  - Carga de datos de sesión
  - Envío de decisiones

#### GestionRedJugador.cs
- **Ubicación**: `Assets/Scripts/GestionRedJugador.cs`
- **Responsabilidades**:
  - Gestión de conexión Photon
  - Asignación de roles
  - Instanciación de jugadores
  - Inicialización de audio

#### SessionManager.cs
- **Ubicación**: `Assets/Scripts/SessionManager.cs`
- **Responsabilidades**:
  - Búsqueda de sesiones
  - Confirmación de roles
  - Gestión de UI de sesiones

#### DialogueManager.cs
- **Ubicación**: `Assets/Scripts/DialogueManager.cs`
- **Responsabilidades**:
  - Gestión de diálogos en tiempo real
  - Procesamiento de respuestas
  - Sincronización de estado

### Configuración en Unity

#### UnityConfig.cs (ScriptableObject)
```csharp
[CreateAssetMenu(fileName = "UnityConfig", menuName = "Juicios Simulator/Unity Config")]
public class UnityConfig : ScriptableObject
{
    public string apiBaseURL = "http://localhost:8000/api";
    public string photonAppId = "YOUR_PHOTON_APP_ID";
    public string photonRegion = "us";
    public string peerjsHost = "peerjs.com";
    public int peerjsPort = 443;
    public bool peerjsSecure = true;
}
```

#### Configuración en Inspector

1. **GameInitializer**:
   - `config`: UnityConfig asset
   - `laravelAPI`: Referencia a LaravelAPI
   - `testEmail`: Email para pruebas
   - `testPassword`: Password para pruebas

2. **GestionRedJugador**:
   - `laravelAPI`: Referencia a LaravelAPI
   - `gameInitializer`: Referencia a GameInitializer
   - `sessionRoomName`: Nombre de la sala (ej: "SalaPrincipal")

3. **LaravelAPI**:
   - `baseURL`: "http://localhost:8000/api"
   - `authToken`: Se llena automáticamente después del login

---

## 🔊 Sistema de Audio

### Arquitectura de Audio

```
Unity Client (WebGL)
    │
    ├─── GestionRedJugador.InitializeAudioSystem()
    │         │
    │         └─── Application.ExternalCall("initVoiceCall", roomId, actorId)
    │
    └─── JavaScript (index.html)
              │
              ├─── window.initVoiceCall(roomId, actorId)
              │         │
              │         ├─── navigator.mediaDevices.getUserMedia()
              │         │         └─── Obtener stream de micrófono
              │         │
              │         └─── new Peer(myId, config)
              │                   │
              │                   └─── Conectar a servidor PeerJS
              │
              ├─── peer.on('open', id => ...)
              │         └─── Notificar a Unity: OnVoiceReady(id)
              │
              ├─── startAutoDial()
              │         └─── Buscar otros peers en la sala
              │
              └─── callPeer(peerId)
                        └─── peer.call(peerId, localStream)
                                  └─── Establecer conexión WebRTC
```

### Servidores PeerJS

**Configuración Actual** (servidores públicos):
```javascript
const peerConfigs = [
  {
    host: 'peerjs.com',
    port: 443,
    secure: true,
    path: '/peerjs'
  },
  {
    host: '0.peerjs.com',
    port: 443,
    secure: true,
    path: '/peerjs'
  },
  {
    host: '1.peerjs.com',
    port: 443,
    secure: true,
    path: '/peerjs'
  }
];
```

**Sistema de Respaldo**: Si un servidor falla, automáticamente intenta con el siguiente.

### STUN Servers

Para WebRTC, se utilizan servidores STUN públicos de Google:
```javascript
iceServers: [
  { urls: 'stun:stun.l.google.com:19302' },
  { urls: 'stun:stun1.l.google.com:19302' },
  { urls: 'stun:stun2.l.google.com:19302' }
]
```

### Flujo de Audio

1. **Inicialización**
   - Unity llama a `initVoiceCall(roomId, actorId)`
   - JavaScript solicita acceso al micrófono
   - Se crea un Peer con ID único: `{roomId}_{actorId}`

2. **Conexión**
   - Peer se conecta al servidor PeerJS
   - Se obtiene un Peer ID único
   - Se notifica a Unity con `OnVoiceReady(peerId)`

3. **Descubrimiento**
   - Sistema busca automáticamente otros peers en la sala
   - Intenta conectar con IDs: `{roomId}_1`, `{roomId}_2`, etc.

4. **Llamadas**
   - Cuando se encuentra un peer, se establece una llamada WebRTC
   - El stream de audio se transmite P2P
   - Se crea un elemento `<audio>` para reproducir el stream remoto

5. **Reproducción**
   - Cada stream remoto se reproduce en un elemento de audio oculto
   - Indicadores visuales muestran cuando hay audio activo

### Logs de Audio

El sistema incluye logs detallados en la consola del navegador:
- ✅ Inicialización del sistema
- ✅ Obtención del micrófono
- ✅ Conexión a PeerJS
- ✅ Llamadas entrantes/salientes
- ✅ Streams de audio
- ✅ Errores y advertencias

---

## 💬 Sistema de Diálogos

### Arquitectura de Diálogos V2

El sistema de diálogos utiliza una arquitectura de grafos donde:

- **Nodos**: Representan puntos en el diálogo (inicio, diálogo, decisión, final)
- **Conexiones**: Representan las opciones/respuestas entre nodos
- **Flujos**: Secuencias de nodos que forman el diálogo completo

### Tipos de Nodos

1. **Nodo Inicio**
   - Punto de entrada del diálogo
   - Solo puede haber uno por diálogo
   - No tiene respuestas

2. **Nodo Diálogo**
   - Contiene texto que se muestra al usuario
   - Tiene múltiples respuestas posibles
   - Puede tener condiciones de rol

3. **Nodo Decisión**
   - Requiere que el usuario elija una opción
   - Cada opción tiene puntuación y consecuencias
   - Se registra la decisión en la base de datos

4. **Nodo Final**
   - Punto de salida del diálogo
   - Marca el fin de una rama del diálogo

### Flujo de un Diálogo

```
1. Instructor inicia diálogo en sesión
   → POST /api/sesiones/{id}/iniciar-dialogo
   → Se crea SesionDialogoV2 con estado "iniciado"
   → Se establece nodo_actual_id al nodo inicio

2. Usuario solicita estado actual
   → GET /api/unity/sesion/{id}/dialogo-estado
   → Retorna nodo actual y opciones disponibles

3. Usuario envía decisión
   → POST /api/unity/sesion/{id}/enviar-decision
   → Se crea DecisionDialogoV2
   → Se calcula puntuación
   → Se aplican consecuencias
   → Se avanza al siguiente nodo

4. Sistema evalúa si todos respondieron
   → Si todos respondieron → avanzar automáticamente
   → Si no → esperar más respuestas

5. Diálogo finaliza
   → Estado cambia a "finalizado"
   → Se calculan estadísticas finales
```

### Sistema de Puntuación

Cada respuesta tiene:
- **Puntuación**: 0-10 puntos
- **Consecuencias**: JSON con efectos (ej: afecta reputación, desbloquea opciones)
- **Tiempo de Respuesta**: Se registra para evaluación

### Evaluación Automática

El sistema evalúa automáticamente:
- **Puntuación Total**: Suma de todas las decisiones
- **Tiempo Promedio**: Tiempo promedio de respuesta
- **Consecuencias Aplicadas**: Efectos acumulados de decisiones
- **Rol Desempeñado**: Evaluación específica por rol

---

## 🔄 Flujos de Trabajo

### Flujo: Crear y Ejecutar una Sesión

```
1. INSTRUCTOR: Crear Sesión
   ├─── Ir a /sesiones
   ├─── Click "Nueva Sesión"
   ├─── Seleccionar plantilla (opcional)
   ├─── Asignar participantes con roles
   └─── Guardar sesión (estado: "programada")

2. INSTRUCTOR: Iniciar Sesión
   ├─── Ir a sesión creada
   ├─── Click "Iniciar Sesión"
   ├─── Estado cambia a "en_curso"
   └─── Se genera Unity Room ID

3. ALUMNO: Unirse a Sesión
   ├─── Abrir Unity WebGL
   ├─── Login con credenciales
   ├─── Sistema obtiene sesión activa automáticamente
   ├─── Se muestra rol asignado
   ├─── Alumno confirma rol
   └─── Se une a sala Photon

4. ALUMNO: Participar en Diálogo
   ├─── Instructor inicia diálogo
   ├─── Unity recibe notificación
   ├─── Se muestra UI de diálogo
   ├─── Alumno ve opciones disponibles
   ├─── Alumno selecciona respuesta
   ├─── Se envía decisión a Laravel
   └─── Sistema avanza diálogo

5. INSTRUCTOR: Finalizar Sesión
   ├─── Click "Finalizar Sesión"
   ├─── Estado cambia a "finalizada"
   ├─── Se calculan estadísticas
   └─── Se generan reportes
```

### Flujo: Crear un Diálogo

```
1. INSTRUCTOR: Crear Diálogo
   ├─── Ir a /dialogos
   ├─── Click "Nuevo Diálogo"
   ├─── Nombre y descripción
   └─── Guardar (estado: "borrador")

2. INSTRUCTOR: Diseñar Diálogo
   ├─── Abrir editor visual
   ├─── Agregar nodos (drag & drop)
   ├─── Conectar nodos con respuestas
   ├─── Configurar puntuaciones
   ├─── Asignar roles a nodos
   └─── Guardar cambios

3. INSTRUCTOR: Activar Diálogo
   ├─── Cambiar estado a "activo"
   └─── Diálogo disponible para usar en sesiones
```

### Flujo: Sistema de Audio

```
1. Jugador se une a sala
   ├─── GestionRedJugador.OnJoinedRoom()
   ├─── InitializeAudioSystem()
   └─── Application.ExternalCall("initVoiceCall", roomId, actorId)

2. JavaScript inicializa audio
   ├─── Solicitar acceso al micrófono
   ├─── Crear Peer con ID único
   ├─── Conectar a servidor PeerJS
   └─── Notificar a Unity: OnVoiceReady(peerId)

3. Búsqueda automática de peers
   ├─── Sistema busca otros jugadores en la sala
   ├─── Intenta conectar con cada uno
   └─── Establece llamadas WebRTC

4. Comunicación de voz
   ├─── Stream de micrófono → PeerJS → Otros jugadores
   ├─── Streams remotos → Elementos <audio> → Altavoces
   └─── Indicadores visuales muestran audio activo
```

---

## ⚙️ Configuración y Deployment

### Requisitos del Sistema

#### Servidor
- **OS**: Linux (Ubuntu 20.04+), Windows Server, macOS
- **PHP**: 8.2 o superior
- **MySQL/MariaDB**: 8.0 o superior
- **Node.js**: 18.x o superior
- **NPM**: 9.x o superior
- **Composer**: 2.x

#### Cliente (Navegador)
- **Chrome/Edge**: 90+
- **Firefox**: 88+
- **Safari**: 14+ (con limitaciones WebRTC)
- **WebGL**: Habilitado
- **Micrófono**: Permisos otorgados

### Instalación Local

#### 1. Clonar Repositorio
```bash
git clone https://github.com/tu-usuario/juiciosorales.git
cd juiciosorales
```

#### 2. Instalar Dependencias PHP
```bash
composer install
```

#### 3. Instalar Dependencias Node
```bash
npm install
```

#### 4. Configurar Entorno
```bash
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
```

#### 5. Configurar Base de Datos
Editar `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=juiciosorales
DB_USERNAME=root
DB_PASSWORD=tu_password
```

#### 6. Ejecutar Migraciones
```bash
php artisan migrate
php artisan db:seed
```

#### 7. Compilar Assets
```bash
npm run build
```

#### 8. Iniciar Servidor
```bash
php artisan serve
```

### Configuración de Unity

#### 1. Abrir Proyecto Unity
- Unity Hub → Abrir Proyecto
- Seleccionar `unity-integration/unity-project`

#### 2. Configurar Photon
- Window → Photon Unity Networking → PUN Wizard
- Ingresar App ID de Photon
- Configurar región (us, eu, asia)

#### 3. Configurar LaravelAPI
- En la escena, seleccionar GameObject con `LaravelAPI`
- En Inspector, configurar `baseURL`: `http://localhost:8000/api`

#### 4. Build WebGL
- File → Build Settings
- Seleccionar WebGL
- Build → Seleccionar `storage/unity-build`

### Variables de Entorno Importantes

#### Laravel (.env)
```env
APP_NAME="Simulador de Juicios Orales"
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=juiciosorales
DB_USERNAME=root
DB_PASSWORD=root

JWT_SECRET=...
JWT_TTL=60

CORS_ALLOWED_ORIGINS=http://localhost:8000,http://127.0.0.1:8000
```

### Deployment en Producción

#### Opción 1: Servidor Dedicado

1. **Configurar Servidor Web**
   - Nginx o Apache
   - PHP-FPM
   - SSL/HTTPS

2. **Configurar Laravel**
   ```bash
   APP_ENV=production
   APP_DEBUG=false
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

3. **Configurar Base de Datos**
   - Crear base de datos
   - Ejecutar migraciones
   - Configurar backups

4. **Configurar Unity Build**
   - Compilar para WebGL
   - Subir a `storage/unity-build`
   - Configurar Nginx para servir archivos `.br`

#### Opción 2: Docker

```dockerfile
# Dockerfile.prod ya incluido
docker build -f Dockerfile.prod -t juiciosorales .
docker run -p 8000:8000 juiciosorales
```

---

## 🔒 Seguridad

### Autenticación

- **JWT Tokens**: Tokens con expiración configurable
- **Refresh Tokens**: Renovación automática de tokens
- **Rate Limiting**: Límite de intentos de login
- **Password Hashing**: bcrypt con salt automático

### Autorización

- **Roles**: admin, instructor, alumno
- **Permisos**: Control granular con Spatie Permissions
- **Middleware**: Verificación de permisos en rutas

### Protección de API

- **CORS**: Configurado para dominios específicos
- **CSRF**: Protección en formularios web
- **XSS**: Sanitización de inputs
- **SQL Injection**: Prevenido con Eloquent ORM

### Seguridad de Unity

- **HTTPS Requerido**: Para WebRTC y PeerJS
- **Token Validation**: Verificación de JWT en cada request
- **Origin Validation**: Verificación de origen de requests

---

## 🐛 Troubleshooting

### Problemas Comunes

#### 1. Error: "Vite manifest not found"
**Solución**:
```bash
npm run build
php artisan config:clear
```

#### 2. Error: "JWT Token invalid"
**Solución**:
```bash
php artisan jwt:secret
php artisan config:clear
```

#### 3. Error: "initVoiceCall is not defined"
**Solución**:
- Verificar que `index.html` tiene `window.initVoiceCall` definido
- Limpiar caché del navegador (Ctrl+Shift+R)
- Verificar que se accede a través de Laravel, no `file://`

#### 4. Error: "No se puede acceder al micrófono"
**Solución**:
- Verificar permisos del navegador
- Asegurar que se accede vía HTTPS (o localhost)
- Verificar que no hay otros programas usando el micrófono

#### 5. Error: "PeerJS connection failed"
**Solución**:
- Verificar conexión a internet
- Verificar que los servidores PeerJS están disponibles
- Revisar logs en consola del navegador

#### 6. Error: "Rol vacío" en Unity
**Solución**:
- Verificar que el usuario tiene una asignación de rol en la sesión
- Verificar que `baseURL` está correctamente configurado
- Revisar logs de Laravel: `storage/logs/laravel.log`

### Logs y Debugging

#### Laravel Logs
```bash
tail -f storage/logs/laravel.log
```

#### Unity Console
- En el Editor: Window → General → Console
- En WebGL: Abrir DevTools del navegador (F12)

#### Navegador Console
- F12 → Console
- Ver logs detallados de PeerJS y Unity

---

## 📊 Métricas y Monitoreo

### Métricas Clave

- **Sesiones Activas**: Número de sesiones en curso
- **Usuarios Conectados**: Usuarios en Unity
- **Decisiones por Minuto**: Actividad en diálogos
- **Tiempo Promedio de Respuesta**: Performance de usuarios
- **Tasa de Finalización**: % de sesiones completadas

### Logs Importantes

- **Autenticación**: Intentos de login, tokens generados
- **Sesiones**: Creación, inicio, finalización
- **Decisiones**: Todas las decisiones de usuarios
- **Errores**: Excepciones y errores del sistema

---

## 🚀 Roadmap y Mejoras Futuras

### Corto Plazo
- [ ] Optimización de rendimiento de Unity
- [ ] Mejora de UI/UX del editor de diálogos
- [ ] Sistema de notificaciones en tiempo real
- [ ] Exportación de reportes en PDF

### Mediano Plazo
- [ ] Sistema de grabación de sesiones
- [ ] Análisis de sentimientos en respuestas
- [ ] Integración con sistemas LMS
- [ ] App móvil (iOS/Android)

### Largo Plazo
- [ ] IA para generación automática de diálogos
- [ ] Realidad Virtual (VR) support
- [ ] Multi-idioma completo
- [ ] Sistema de plugins/extensions

---

## 📞 Soporte y Contacto

### Documentación Adicional
- **Guía de Instalación**: `docs/instalacion-dependencias.md`
- **Guía Unity**: `unity-integration/INTEGRATION_GUIDE.md`
- **API Docs**: `http://localhost:8000/api/documentation`

### Recursos
- **Repositorio**: GitHub
- **Issues**: GitHub Issues
- **Wiki**: Documentación en GitHub

---

**Versión del Documento**: 1.0  
**Última Actualización**: Enero 2025  
**Autor**: Equipo de Desarrollo

