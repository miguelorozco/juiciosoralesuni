# DIAGRAMA: Flujo de Asignación de Rol (ANTES vs DESPUÉS)

## ANTES DEL FIX ❌

```
┌─────────────────────────────────────────────────────────────────┐
│                          LARAVEL (BACKEND)                       │
│                                                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ UnityEntryController.generateUnityEntryLink()           │   │
│  │                                                          │   │
│  │ ✅ Genera token base64:                                  │   │
│  │    {                                                     │   │
│  │      user_id: 2,                                         │   │
│  │      session_id: 2,                                      │   │
│  │      role.nombre: "Juez"  ← ¡DATOS CORRECTOS!          │   │
│  │    }                                                     │   │
│  │                                                          │   │
│  │ ✅ Retorna: /unity-game?token=eyJ...&session=2          │   │
│  └─────────────────────────────────────────────────────────┘   │
│                           ↓                                      │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ Route::get('/unity-game', function() {                  │   │
│  │     return view('unity.game');  ← ❌ SIN PARÁMETROS!    │   │
│  │ })                                                       │   │
│  │                                                          │   │
│  │ ❌ No extrae token del URL                              │   │
│  │ ❌ No decodifica token                                  │   │
│  │ ❌ No pasa $sessionData a la vista                       │   │
│  └─────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────────────┐
│                    BLADE TEMPLATE (game.blade.php)               │
│                                                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ @if(isset($sessionData))                                │   │
│  │     window.sendToUnity($sessionData)  ← ❌ NUNCA ENTRA  │   │
│  │ @endif                                                  │   │
│  │                                                          │   │
│  │ $sessionData = undefined ← ❌ NUNCA SE RECIBE           │   │
│  │                                                          │   │
│  │ ❌ JavaScript NO envía datos a Unity                    │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                   │
│  ❌ Token está en URL pero NO SE EXTRAE                         │
│     URL: /unity-game?token=eyJ...&session=2                     │
│            ↑ aquí está pero nadie lo lee ↑                      │
└─────────────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────────────┐
│                        UNITY (C#)                                │
│                                                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ LaravelUnityEntryManager.ReceiveLaravelData()           │   │
│  │                                                          │   │
│  │ ❌ MÉTODO NO EXISTE                                      │   │
│  │ ❌ Nunca se ejecuta porque JavaScript no llama          │   │
│  └─────────────────────────────────────────────────────────┘   │
│                           ↓                                      │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ GestionRedJugador.GetAssignedRoleFromSession()          │   │
│  │                                                          │   │
│  │ ❌ laravelAPI.currentSessionData = null                 │   │
│  │ ❌ No encuentra datos en GameInitializer                │   │
│  │ ❌ Asigna rol por defecto: "Observador"                 │   │
│  │                                                          │   │
│  │ assignedRole = "Observador"  ← ❌ INCORRECTO!           │   │
│  └─────────────────────────────────────────────────────────┘   │
│                           ↓                                      │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ OnJoinedRoom(): No encuentra Player_Juez               │   │
│  │                                                          │   │
│  │ FindAndClaimAnyAvailablePlayer()  ← ❌ FALLBACK RANDOM  │   │
│  │   ↓                                                      │   │
│  │   Elige primer player disponible: "Player_Perito2"      │   │
│  │                                                          │   │
│  │ assignedRole = "Perito2"  ← ❌ ¡INCORRECTO!             │   │
│  │ Camera = Perito2 position  ← ❌ ¡ERROR VISIBLE!         │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                   │
│  RESULTADO: La pantalla muestra al Juez pero la cámara está     │
│  posicionada en Perito2 ❌                                       │
└─────────────────────────────────────────────────────────────────┘
```

---

## DESPUÉS DEL FIX ✅

```
┌─────────────────────────────────────────────────────────────────┐
│                          LARAVEL (BACKEND)                       │
│                                                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ UnityEntryController.generateUnityEntryLink()           │   │
│  │                                                          │   │
│  │ ✅ Genera token base64:                                  │   │
│  │    {                                                     │   │
│  │      user_id: 2,                                         │   │
│  │      session_id: 2,                                      │   │
│  │      role.nombre: "Juez"  ← ¡DATOS CORRECTOS!          │   │
│  │    }                                                     │   │
│  │                                                          │   │
│  │ ✅ Retorna: /unity-game?token=eyJ...&session=2          │   │
│  └─────────────────────────────────────────────────────────┘   │
│                           ↓                                      │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ UnityEntryController.getUnityEntryInfo()                │   │
│  │                                                          │   │
│  │ ✅ Nueva ruta: /api/unity-entry-info?token=...          │   │
│  │                                                          │   │
│  │ ✅ Decodifica token:                                     │   │
│  │    user_id = 2, session_id = 2                          │   │
│  │                                                          │   │
│  │ ✅ Busca en BD: AsignacionRol.where(user=2, session=2)  │   │
│  │                                                          │   │
│  │ ✅ Retorna JSON:                                         │   │
│  │    {                                                     │   │
│  │      success: true,                                      │   │
│  │      data: {                                             │   │
│  │        role: { nombre: "Juez", ... }                    │   │
│  │      }                                                   │   │
│  │    }                                                     │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                   │
│  (Endpoint ya existía pero no se estaba usando)                 │
└─────────────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────────────┐
│                    BLADE TEMPLATE (game.blade.php)               │
│                                                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ setupLaravelCommunication()                             │   │
│  │                                                          │   │
│  │ ✅ NUEVO: Extrae token del URL:                          │   │
│  │    const token = getQueryParameter('token')             │   │
│  │    const sessionId = getQueryParameter('session')       │   │
│  │                                                          │   │
│  │ ✅ NUEVO: Llama a /api/unity-entry-info con token       │   │
│  │    fetch('/api/unity-entry-info?token=' + token)        │   │
│  │                                                          │   │
│  │ ✅ NUEVO: Recibe datos del backend:                      │   │
│  │    {                                                     │   │
│  │      user: { name: "Ana", ... },                        │   │
│  │      session: { id: 2, ... },                           │   │
│  │      role: { nombre: "Juez", ... }  ← ¡DATOS!           │   │
│  │    }                                                     │   │
│  │                                                          │   │
│  │ ✅ NUEVO: Envía a Unity:                                 │   │
│  │    window.sendToUnity({                                 │   │
│  │      user, session, role                                │   │
│  │    })                                                   │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                   │
│  ✅ Ahora el rol SÍ llega a Unity                              │
└─────────────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────────────┐
│                        UNITY (C#)                                │
│                                                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ LaravelUnityEntryManager.ReceiveLaravelData()           │   │
│  │                                                          │   │
│  │ ✅ NUEVO: Método existe y recibe datos                  │   │
│  │                                                          │   │
│  │ ✅ Parsea JSON con SessionDataPayload                   │   │
│  │                                                          │   │
│  │ ✅ Rellena laravelAPI.currentSessionData:               │   │
│  │    session = { id: 2, nombre: "..." }                   │   │
│  │    role = { nombre: "Juez", ... }  ← ¡DATOS LISTOS!     │   │
│  │                                                          │   │
│  │ Debug: "Datos de sesión recibidos"                      │   │
│  │        { "rolNombre": "Juez" }                          │   │
│  └─────────────────────────────────────────────────────────┘   │
│                           ↓                                      │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ GestionRedJugador.GetAssignedRoleFromSession()          │   │
│  │                                                          │   │
│  │ ✅ Busca en laravelAPI.currentSessionData               │   │
│  │                                                          │   │
│  │ ✅ ENCUENTRA: role.nombre = "Juez"                      │   │
│  │                                                          │   │
│  │ assignedRole = "Juez"  ← ✅ ¡CORRECTO!                  │   │
│  │ hasAssignedRole = true                                  │   │
│  │                                                          │   │
│  │ Debug: "Rol asignado desde sesión (LaravelAPI): Juez"   │   │
│  │                                                          │   │
│  │ JoinSessionRoom()  ← Unirse a Photon con rol           │   │
│  └─────────────────────────────────────────────────────────┘   │
│                           ↓                                      │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ OnJoinedRoom(): Busca Player_Juez                       │   │
│  │                                                          │   │
│  │ ✅ ENCUENTRA: assignedRole = "Juez"                      │   │
│  │                                                          │   │
│  │ ✅ Reclama correctamente: Player_Juez                    │   │
│  │                                                          │   │
│  │ ✅ Camera = Juez position  ← ✅ ¡CORRECTO!              │   │
│  │                                                          │   │
│  │ Debug: "✅ Unido a sala Photon con rol: Juez"           │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                   │
│  RESULTADO: La cámara está correctamente posicionada en el      │
│  lugar del Juez ✅                                              │
└─────────────────────────────────────────────────────────────────┘
```

---

## COMPARACIÓN DE CADENAS DE DATOS

### ANTES ❌

```
Token con rol "Juez"
         ↓
      [PERDIDO]
         ↓
JavaScript recibe undefined
         ↓
Unity no recibe datos
         ↓
Fallback a "Observador"
         ↓
Fallback a primer jugador disponible
         ↓
ERROR: Perito2 instead of Juez
```

### DESPUÉS ✅

```
Token con rol "Juez"
         ↓
JavaScript extrae token de URL
         ↓
JavaScript llama /api/unity-entry-info?token=...
         ↓
Backend decodifica token y retorna datos
         ↓
JavaScript recibe: { role: { nombre: "Juez" } }
         ↓
JavaScript envía a Unity via SendMessage
         ↓
LaravelUnityEntryManager.ReceiveLaravelData()
         ↓
Rellena laravelAPI.currentSessionData.role
         ↓
GestionRedJugador encuentra rol "Juez"
         ↓
Photon instantiation correct
         ↓
Camera en posición de Juez ✅
```

---

## PUNTOS CLAVE

### Antes del fix, la arquitectura tenía estos problemas:

1. **Token generado pero no utilizado**: El token incluía el rol pero ningún código lo extraía
2. **Ruta genérica sin parámetros**: `/unity-game` no recibía ni pasaba datos de sesión
3. **Falta de extracción de URL**: No había código para leer `?token=...` del URL
4. **Método receptor no implementado**: `ReceiveLaravelData()` no existía
5. **Fallback incorrecto**: `FindAndClaimAnyAvailablePlayer()` elegía random en lugar de específico

### Después del fix, la arquitectura:

1. ✅ Extrae token del URL en JavaScript
2. ✅ Llama endpoint `/api/unity-entry-info` para decodificarlo
3. ✅ Envía datos a Unity via `SendMessage`
4. ✅ Implementa método receptor `ReceiveLaravelData()`
5. ✅ Rellena estructuras de datos correctamente
6. ✅ GestionRedJugador encuentra y asigna el rol correcto

