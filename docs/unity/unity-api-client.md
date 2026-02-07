# Cliente API Unity – Laravel (puente Unity–Laravel)

Cliente C# en Unity para comunicarse con el API Laravel existente (`/api/unity/...`). Permite login, sesiones, diálogos, salas y LiveKit desde código Unity.

## Ubicación en el proyecto

```
unity-project/Assets/Scripts/
├── UnityBridgeConfig.cs   # Configuración (BaseUrl, Token)
├── UnityApiClient.cs      # Cliente HTTP (MonoBehaviour)
└── UnityApiModels.cs      # DTOs para JSON
```

## Dependencias

- **UnityEngine** (UnityWebRequest)
- **Newtonsoft.Json** (paquete `com.unity.nuget.newtonsoft-json` ya presente en el proyecto)

## Configuración

### 1. UnityBridgeConfig (estática)

- **`UnityBridgeConfig.BaseUrl`**: URL base del API (ej. `http://localhost:8000/api`). No incluir barra final.
- **`UnityBridgeConfig.Token`**: Token JWT para peticiones autenticadas. En WebGL suele inyectarse desde la página.

Desde código:

```csharp
using JuiciosSimulator.API;

UnityBridgeConfig.BaseUrl = "https://midominio.com/api";
UnityBridgeConfig.Token = "eyJ0eXAiOiJKV1QiLCJhbGc...";
```

Para WebGL, desde JavaScript se puede llamar a métodos del juego que a su vez llamen a:

```csharp
UnityBridgeConfig.SetBaseUrl(url);
UnityBridgeConfig.SetToken(token);
```

(Desde JS se invocaría el método del GameObject que contenga un script que llame a estos estáticos.)

### 2. UnityApiClient (MonoBehaviour)

- Añadir el componente **UnityApiClient** a un GameObject de la escena (por ejemplo un objeto “GameManager” o “NetworkManager”).
- Opcional: en el Inspector, **Override Base Url** para sobreescribir `UnityBridgeConfig.BaseUrl` al iniciar.
- Opcional: marcar **Log Requests** para depuración.

El cliente se usa como singleton: `UnityApiClient.Instance`.

## Uso por bloques del API

### Autenticación

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `GetAuthStatus(onDone)` | GET `unity/auth/status` | Estado del servidor (público). |
| `Login(email, password, onDone)` | POST `unity/auth/login` | Login; en `onDone` guardar `response.data.token` y asignar a `UnityBridgeConfig.Token`. |
| `Logout(onDone)` | POST `unity/auth/logout` | Cerrar sesión (requiere token). |
| `Me(onDone)` | GET `unity/auth/me` | Usuario actual (requiere token). |
| `GetActiveSession(onDone)` | GET `unity/auth/session/active` | Sesión activa del usuario (requiere token). |

Ejemplo de login y guardado del token:

```csharp
UnityApiClient.Instance.Login("usuario@ejemplo.com", "password", response =>
{
    if (response.success && response.data != null)
    {
        UnityBridgeConfig.Token = response.data.token;
        Debug.Log("Login OK: " + response.data.user.name);
    }
    else
        Debug.LogError(response.message);
});
```

### Sesiones

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `SesionesBuscarPorCodigo(codigo, onDone)` | GET `unity/sesiones/buscar-por-codigo/{codigo}` | Buscar sesión por código. |
| `SesionesMiRol(sesionId, onDone)` | GET `unity/sesiones/{id}/mi-rol` | Rol del usuario en la sesión. |
| `SesionesConfirmarRol(sesionId, onDone)` | POST `unity/sesiones/{id}/confirmar-rol` | Confirmar rol. |
| `SesionesDisponibles(onDone)` | GET `unity/sesiones/disponibles` | Listado de sesiones disponibles. |

### Diálogo (por sesión)

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `IniciarDialogo(sesionJuicio, onDone)` | POST `unity/sesion/{id}/iniciar-dialogo` | Pasar diálogo de iniciado a en_curso (solo instructor/admin). |
| `GetDialogoEstado(sesionJuicio, onDone)` | GET `unity/sesion/{id}/dialogo-estado` | Estado actual del diálogo. |
| `GetRespuestasUsuario(sesionJuicio, usuarioId, onDone)` | GET `unity/sesion/{id}/respuestas-usuario/{usuario}` | Respuestas disponibles. |
| `EnviarDecision(sesionJuicio, usuarioId, respuestaId, decisionTexto, tiempoRespuesta, onDone)` | POST `unity/sesion/{id}/enviar-decision` | Enviar decisión. |
| `NotificarHablando(sesionJuicio, usuarioId, estado, onDone)` | POST `unity/sesion/{id}/notificar-hablando` | Notificar estado de habla. |
| `GetMovimientosPersonajes(sesionJuicio, onDone)` | GET `unity/sesion/{id}/movimientos-personajes` | Movimientos de personajes. |

### Tiempo real (broadcast / historial)

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `BroadcastEvent(sesionJuicio, eventType, payload, onDone)` | POST `unity/sesion/{id}/broadcast` | Enviar evento. |
| `GetEventHistory(sesionJuicio, onDone)` | GET `unity/sesion/{id}/events/history` | Historial de eventos. |

### Salas (rooms) y LiveKit

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `RoomsCreate(nombre, sesionJuicioId, maxParticipantes, onDone)` | POST `unity/rooms/create` | Crear sala. |
| `RoomsJoin(roomId, onDone)` | GET `unity/rooms/{roomId}/join` | Unirse a sala. |
| `RoomsLeave(roomId, onDone)` | POST `unity/rooms/{roomId}/leave` | Salir de sala. |
| `RoomsGetState(roomId, onDone)` | GET `unity/rooms/{roomId}/state` | Estado de la sala. |
| `RoomsSyncPlayer(roomId, usuarioId, nombre, rolNombre, onDone)` | POST `unity/rooms/{roomId}/sync-player` | Sincronizar jugador. |
| `RoomsAudioState(roomId, usuarioId, muted, speaking, onDone)` | POST `unity/rooms/{roomId}/audio-state` | Estado de audio. |
| `RoomsGetEvents(roomId, onDone)` | GET `unity/rooms/{roomId}/events` | Eventos de la sala. |
| `RoomsClose(roomId, onDone)` | POST `unity/rooms/{roomId}/close` | Cerrar sala. |
| `RoomsGetLiveKitToken(roomId, onDone)` | GET `unity/rooms/{roomId}/livekit-token` | Token LiveKit. |
| `RoomsGetLiveKitStatus(roomId, onDone)` | GET `unity/rooms/{roomId}/livekit-status` | Estado LiveKit. |

## DialogoManager (bucle de diálogo)

El script **`DialogoManager`** implementa el bucle descrito en el plan: consulta el estado, detecta si es tu turno, obtiene las respuestas y permite enviar la decisión. No incluye UI; se enlaza por eventos o leyendo propiedades.

- Añadir el componente a un GameObject. Configurar `sesionJuicioId`, `usuarioId` y opcionalmente `pollingInterval` (segundos; 0 = sin polling automático).
- Eventos estáticos: `OnEstadoActualizado`, `OnRespuestasDisponibles`, `OnError`, `OnDialogoFinalizado`.
- Métodos: `RefrescarEstado()`, `EnviarDecision(respuestaId, decisionTexto, tiempoRespuesta)`, `IniciarDialogo(onDone)` (instructor), `NotificarHablando(estado)`, `MarcarInicioRespuesta()`.
- Requiere `UnityApiClient` en escena y `UnityBridgeConfig.Token` asignado.

## Modelos de datos (UnityApiModels.cs)

Las respuestas del API se deserializan a tipos como:

- **`APIResponse<T>`**: `success`, `message`, `data` (tipo `T`).
- **Auth**: `LoginResponse`, `UserData`, `ServerStatus`.
- **Diálogo**: `DialogoEstado`, `NodoActual`, `RolHablando`, `Participante`, `RespuestasResponse`, `RespuestaUsuario`, `DecisionRequest`, `DecisionResponse`, `NuevoEstado`, `HablandoRequest`.
- **Sesiones**: `SesionData`.
- **Rooms**: `RoomCreateRequest`, `RoomCreateResponse`, `SyncPlayerRequest`, `AudioStateRequest`, `BroadcastEventRequest`.

Los nombres de propiedades en C# coinciden con los del JSON (snake_case en el API se mapea a los mismos nombres en los DTOs; Newtonsoft.Json por defecto es case-insensitive).

## Integración con WebGL

En build WebGL, el token y la URL suelen venir de la página que carga el juego:

1. La página obtiene el token (p. ej. desde Laravel o desde `/api/unity-entry-info?token=...`).
2. Al cargar Unity, la página llama a un método expuesto por Unity (por ejemplo `SetToken(string token)`) que internamente haga `UnityBridgeConfig.SetToken(token)` y opcionalmente `UnityBridgeConfig.SetBaseUrl(baseUrl)`.
3. El resto del juego usa `UnityApiClient.Instance` como en editor o standalone.

## Ejemplo mínimo de escena

1. Crear un GameObject vacío (ej. “ApiBridge”).
2. Añadir el componente **UnityApiClient**.
3. Opcional: asignar **Override Base Url** si no se usa `UnityBridgeConfig` desde código.
4. En un script de inicio o menú:

```csharp
// Comprobar servidor
UnityApiClient.Instance.GetAuthStatus(r =>
{
    Debug.Log(r.success ? "Servidor OK" : r.message);
});

// Login
UnityApiClient.Instance.Login("email@ejemplo.com", "password", r =>
{
    if (!r.success) return;
    UnityBridgeConfig.Token = r.data.token;
    // A partir de aquí ya se pueden usar endpoints que requieren token
});
```

## Errores y depuración

- Todas las llamadas reciben un callback `Action<APIResponse<T>>`. Revisar siempre `response.success` y `response.message`.
- Con **Log Requests** activado en el Inspector de `UnityApiClient`, se imprimen en consola las URLs de GET/POST.
- Si el API devuelve 401, comprobar que `UnityBridgeConfig.Token` esté asignado y sea válido.
- Si hay errores de CORS en WebGL, el API Laravel debe permitir el origen del build (middleware CORS configurado en el servidor).
