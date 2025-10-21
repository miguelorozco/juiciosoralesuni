# 🎮 Guía Completa de Integración Unity + Laravel

Esta guía te llevará paso a paso para integrar tu proyecto de Unity con el sistema Laravel de simulador de juicios.

## 📋 Tabla de Contenidos

1. [Configuración Inicial](#configuración-inicial)
2. [Instalación de Dependencias](#instalación-de-dependencias)
3. [Configuración de Unity](#configuración-de-unity)
4. [Configuración de Laravel](#configuración-de-laravel)
5. [Scripts de Unity](#scripts-de-unity)
6. [API Endpoints](#api-endpoints)
7. [Comunicación en Tiempo Real](#comunicación-en-tiempo-real)
8. [Troubleshooting](#troubleshooting)
9. [Ejemplos de Uso](#ejemplos-de-uso)

## 🚀 Configuración Inicial

### Requisitos Previos

- **Unity 2022.3.15f1** o superior
- **Laravel 12** con PHP 8.2+
- **Node.js 18+** para compilación de assets
- **Composer** para dependencias PHP

### Estructura de Archivos

```
tu-proyecto-unity/
├── Assets/
│   ├── Scripts/
│   │   ├── API/
│   │   │   ├── LaravelAPI.cs
│   │   │   └── UnityConfig.cs
│   │   ├── UI/
│   │   │   └── DialogoUI.cs
│   │   └── Characters/
│   │       └── PersonajeController.cs
│   └── Resources/
│       └── UnityConfig.asset
```

## 📦 Instalación de Dependencias

### 1. Dependencias de Unity

Instala estos paquetes desde el Package Manager:

```json
{
  "dependencies": {
    "com.unity.nuget.newtonsoft-json": "3.2.1",
    "com.unity.textmeshpro": "3.0.6",
    "com.unity.ugui": "1.0.0"
  }
}
```

### 2. Dependencias de Laravel

Las dependencias ya están instaladas en tu proyecto Laravel:

- `tymon/jwt-auth` - Autenticación JWT
- `fruitcake/laravel-cors` - CORS para Unity
- `laravel/sanctum` - Autenticación API

## ⚙️ Configuración de Unity

### 1. Crear ScriptableObject de Configuración

1. En Unity, ve a `Assets > Create > Juicios Simulator > Unity Config`
2. Configura los valores:

```csharp
API Base URL: http://localhost:8000/api
Unity Version: 2022.3.15f1
Unity Platform: WindowsPlayer
Sesión ID: 1
Usuario ID: 1
```

### 2. Configurar Escena Principal

1. **Crear GameObject para API**:
   - Nombre: "LaravelAPI"
   - Agregar script: `LaravelAPI`
   - Configurar valores en Inspector

2. **Crear Canvas para UI**:
   - Nombre: "DialogoCanvas"
   - Agregar script: `DialogoUI`
   - Configurar elementos UI

3. **Crear Personajes**:
   - Para cada personaje, agregar script: `PersonajeController`
   - Configurar `usuarioId` y `rolId`

### 3. Configurar Build Settings

1. **WebGL Build**:
   - File > Build Settings
   - Platform: WebGL
   - Player Settings > Publishing Settings
   - Data Caching: Disabled
   - Compression Format: Disabled

2. **Standalone Build**:
   - Platform: Windows/Mac/Linux
   - Configuration: Release

## 🔧 Configuración de Laravel

### 1. Verificar CORS

El archivo `config/cors.php` ya está configurado para Unity:

```php
'allowed_origins' => [
    'http://localhost:3000',  // Unity WebGL
    'https://localhost:3000',
    'http://127.0.0.1:3000',
    'https://127.0.0.1:3000',
    // ... más orígenes
],
```

### 2. Verificar Rutas API

Las rutas Unity están en `routes/api.php`:

```php
// Autenticación Unity
Route::group(['prefix' => 'unity/auth'], function () {
    Route::post('login', [UnityAuthController::class, 'login']);
    Route::get('status', [UnityAuthController::class, 'status']);
    // ... más rutas
});

// Diálogos Unity
Route::middleware('unity.auth')->group(function () {
    Route::group(['prefix' => 'sesion'], function () {
        Route::get('/{sesionJuicio}/dialogo-estado', [UnityDialogoController::class, 'obtenerEstadoDialogo']);
        // ... más rutas
    });
});
```

### 3. Iniciar Servidor Laravel

```bash
cd /var/www/juicios_local
php artisan serve --host=0.0.0.0 --port=8000
```

## 📝 Scripts de Unity

### 1. LaravelAPI.cs

Script principal para comunicación con Laravel:

```csharp
// Login
LaravelAPI.Instance.Login("alumno@example.com", "password");

// Obtener estado del diálogo
LaravelAPI.Instance.GetDialogoEstado(sesionId);

// Enviar decisión
LaravelAPI.Instance.EnviarDecision(sesionId, usuarioId, respuestaId, "Texto adicional");

// Comunicación en tiempo real
LaravelAPI.Instance.StartRealtimeEvents(sesionId);
```

### 2. DialogoUI.cs

Controlador de interfaz de usuario:

```csharp
// Configurar IDs
dialogoUI.SetSessionInfo(sesionId, usuarioId);

// Actualizar diálogo manualmente
dialogoUI.RefreshDialogo();
```

### 3. PersonajeController.cs

Controlador de personajes:

```csharp
// Configurar personaje
personajeController.ConfigurarPersonaje(usuarioId, rolId, "Nombre", Color.blue);

// Obtener información
var info = personajeController.GetPersonajeInfo();
```

## 🌐 API Endpoints

### Autenticación

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/api/unity/auth/login` | Login de usuario |
| GET | `/api/unity/auth/status` | Estado del servidor |
| POST | `/api/unity/auth/refresh` | Renovar token |
| POST | `/api/unity/auth/logout` | Cerrar sesión |
| GET | `/api/unity/auth/me` | Información del usuario |

### Diálogos

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/unity/sesion/{id}/dialogo-estado` | Estado del diálogo |
| GET | `/api/unity/sesion/{id}/respuestas-usuario/{user}` | Respuestas disponibles |
| POST | `/api/unity/sesion/{id}/enviar-decision` | Enviar decisión |
| POST | `/api/unity/sesion/{id}/notificar-hablando` | Notificar habla |
| GET | `/api/unity/sesion/{id}/movimientos-personajes` | Movimientos de personajes |

### Tiempo Real

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/unity/sesion/{id}/events` | Server-Sent Events |
| POST | `/api/unity/sesion/{id}/broadcast` | Broadcast de evento |
| GET | `/api/unity/sesion/{id}/events/history` | Historial de eventos |

## ⚡ Comunicación en Tiempo Real

### Server-Sent Events (SSE)

Unity se conecta a SSE para recibir actualizaciones en tiempo real:

```csharp
// En LaravelAPI.cs
public void StartRealtimeEvents(int sesionId)
{
    StartCoroutine(RealtimeEventsCoroutine(sesionId));
}
```

### Eventos Disponibles

- `dialogo_actualizado` - Estado del diálogo cambió
- `usuario_hablando` - Usuario comenzó/terminó de hablar
- `decision_procesada` - Decisión fue procesada
- `sesion_finalizada` - Sesión terminó

## 🔧 Troubleshooting

### Problemas Comunes

#### 1. Error de CORS

**Síntoma**: Error "CORS policy" en Unity

**Solución**:
```bash
# Verificar configuración CORS
php artisan config:clear
php artisan cache:clear
```

#### 2. Token JWT Expirado

**Síntoma**: Error 401 "Token expired"

**Solución**:
```csharp
// Renovar token automáticamente
LaravelAPI.Instance.RefreshToken();
```

#### 3. Conexión SSE Fallida

**Síntoma**: No se reciben eventos en tiempo real

**Solución**:
```csharp
// Verificar conexión
LaravelAPI.Instance.CheckServerStatus();
```

#### 4. Personajes No Se Mueven

**Síntoma**: Personajes no responden a eventos

**Solución**:
```csharp
// Verificar suscripción a eventos
LaravelAPI.OnDialogoUpdated += OnDialogoUpdated;
```

### Logs de Debug

Habilitar logs detallados en Unity:

```csharp
// En UnityConfig
enableDebugLogs = true;
showNetworkInfo = true;
```

Ver logs de Laravel:

```bash
tail -f storage/logs/laravel.log
```

## 💡 Ejemplos de Uso

### 1. Flujo Completo de Login

```csharp
public class GameManager : MonoBehaviour
{
    void Start()
    {
        // Configurar eventos
        LaravelAPI.OnUserLoggedIn += OnUserLoggedIn;
        LaravelAPI.OnError += OnError;
        
        // Login automático
        LaravelAPI.Instance.Login("alumno@example.com", "password");
    }
    
    void OnUserLoggedIn(UserData user)
    {
        Debug.Log($"Usuario logueado: {user.name}");
        // Iniciar juego
        StartGame();
    }
    
    void OnError(string error)
    {
        Debug.LogError($"Error: {error}");
    }
}
```

### 2. Manejo de Diálogos

```csharp
public class DialogoManager : MonoBehaviour
{
    void Start()
    {
        LaravelAPI.OnDialogoUpdated += OnDialogoUpdated;
        LaravelAPI.OnRespuestasReceived += OnRespuestasReceived;
    }
    
    void OnDialogoUpdated(DialogoEstado estado)
    {
        // Actualizar UI
        UpdateDialogoUI(estado);
        
        // Actualizar personajes
        UpdatePersonajes(estado.participantes);
    }
    
    void OnRespuestasReceived(List<RespuestaUsuario> respuestas)
    {
        // Mostrar opciones de respuesta
        ShowRespuestas(respuestas);
    }
}
```

### 3. Control de Personajes

```csharp
public class PersonajeManager : MonoBehaviour
{
    public PersonajeController[] personajes;
    
    void Start()
    {
        // Configurar personajes
        for (int i = 0; i < personajes.Length; i++)
        {
            personajes[i].ConfigurarPersonaje(
                i + 1, // usuarioId
                i + 1, // rolId
                $"Personaje {i + 1}",
                GetRandomColor()
            );
        }
    }
    
    void UpdatePersonajes(List<Participante> participantes)
    {
        foreach (var participante in participantes)
        {
            var personaje = personajes.FirstOrDefault(p => p.usuarioId == participante.usuario_id);
            if (personaje != null)
            {
                personaje.UpdateCharacterState(participante.es_turno, null);
            }
        }
    }
}
```

## 📊 Monitoreo y Métricas

### 1. Métricas de Unity

```csharp
// En LaravelAPI.cs
public class Metrics
{
    public int requestsSent = 0;
    public int requestsFailed = 0;
    public float averageResponseTime = 0f;
    public int eventsReceived = 0;
}
```

### 2. Métricas de Laravel

Ver en el dashboard de Laravel:
- Usuarios conectados
- Eventos enviados
- Tiempo de respuesta promedio
- Errores de conexión

## 🚀 Despliegue

### 1. Build de Unity

```bash
# WebGL
File > Build Settings > WebGL > Build

# Standalone
File > Build Settings > Windows/Mac/Linux > Build
```

### 2. Despliegue de Laravel

```bash
# Producción
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm run build
```

### 3. Configuración de Producción

Actualizar `UnityConfig` para producción:

```csharp
apiBaseURL = "https://tu-dominio.com/api";
```

## 📞 Soporte

### Recursos Adicionales

- **Documentación API**: `/api/documentation` (Swagger)
- **Logs**: `storage/logs/laravel.log`
- **Configuración**: `config/cors.php`, `config/jwt.php`

### Contacto

- **Email**: soporte@simulador-juicios.com
- **Documentación**: `/docs/`
- **Issues**: GitHub Issues

---

## ✅ Checklist de Integración

- [ ] Unity configurado con scripts
- [ ] Laravel con CORS habilitado
- [ ] Autenticación JWT funcionando
- [ ] Endpoints API accesibles
- [ ] Comunicación en tiempo real activa
- [ ] Personajes respondiendo a eventos
- [ ] UI actualizándose correctamente
- [ ] Logs de debug habilitados
- [ ] Build de Unity exitoso
- [ ] Despliegue en producción

**¡Tu integración Unity + Laravel está lista! 🎉**

