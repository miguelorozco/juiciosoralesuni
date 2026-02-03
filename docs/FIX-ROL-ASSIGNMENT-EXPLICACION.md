# FIX: Asignación Correcta del Rol en Unity (Explicación Completa)

## PROBLEMA ORIGINAL

Cuando Ana seleccionaba el rol "Juez" en Laravel y entraba a Unity, el personaje aparecía como "Perito2" en lugar de "Juez".

```
Ana clicks "Juez" → Laravel generates token → Unity opens → But camera = Perito2 ❌
```

## CAUSA RAÍZ (El error NO era un bug simple, era un problema de arquitectura)

El problema estaba en **4 capas diferentes que no se comunicaban**:

### Layer 1: Frontend (Laravel) ✅ - FUNCIONABA
- El modal muestra "Juez" como "Tu rol" 
- El usuario hace clic en "Juez"
- `selectRoleForUnity()` envía `user_id` y `session_id` al backend

### Layer 2: Backend (PHP) ✅ - FUNCIONABA
- `UnityEntryController.generateUnityEntryLink()` 
- Genera token base64 con datos de sesión/usuario/rol
- Token incluye: `role.nombre = "Juez"`
- Genera URL: `/unity-game?token=eyJ...&session=2`

### Layer 3: Route & Template ❌ - NO FUNCIONABA
```php
// routes/web.php (línea 317-320)
Route::get('/unity-game', function () {
    return view('unity.game');  // ← ¡SIN PARÁMETROS!
})->name('unity.game');
```

**Problema:** La ruta NO extrae el token del URL ni lo pasa a la vista Blade
- La vista recibe `$sessionData = undefined`
- El JavaScript nunca ejecuta `window.sendToUnity()`
- Unity nunca recibe información del rol seleccionado ❌

### Layer 4: Unity ❌ - NO RECIBÍA DATOS
En `GestionRedJugador.OnJoinedLobby()`:
1. Llama `GetAssignedRoleFromSession()`
2. Busca en `laravelAPI.currentSessionData.role.nombre`
3. Pero `currentSessionData` está vacío ❌
4. Asigna rol por defecto: `assignedRole = "Observador"`
5. En `OnJoinedRoom()` no encuentra coincidencia
6. Ejecuta `FindAndClaimAnyAvailablePlayer()` que elige el primer disponible: **"Perito2"** ❌

---

## SOLUCIÓN IMPLEMENTADA

### PASO 1: JavaScript extrae token de la URL (game.blade.php)

```javascript
// Función para extraer parámetros de URL
function getQueryParameter(name) {
    const url = window.location.href;
    name = name.replace(/[\[\]]/g, '\\$&');
    const regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)');
    const results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, ' '));
}

// Extraer token y sesión
const token = getQueryParameter('token');        // ← Extrae "eyJ..."
const sessionId = getQueryParameter('session');  // ← Extrae "2"

// Si hay token, llamar al endpoint para obtener datos
if (token && sessionId) {
    fetch('/api/unity-entry-info?token=' + encodeURIComponent(token))
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                // Esperar a que Unity esté listo y enviar datos
                setTimeout(function() {
                    window.sendToUnity({
                        user: data.data.user,
                        session: data.data.session,
                        role: data.data.role   // ← ¡IMPORTANTE! Incluye { id, nombre: "Juez", ... }
                    });
                }, 1000);
            }
        });
}
```

**Resultado:** Ahora Unity RECIBE el rol "Juez" via `window.sendToUnity()`

---

### PASO 2: Unity implementa método ReceiveLaravelData (LaravelUnityEntryManager.cs)

JavaScript llama a:
```javascript
unityInstance.SendMessage('LaravelUnityEntryManager', 'ReceiveLaravelData', JSON.stringify(data));
```

Ahora existe el método receptor:

```csharp
public void ReceiveLaravelData(string jsonData)
{
    // Parsear JSON recibido
    var payload = JsonUtility.FromJson<SessionDataPayload>(jsonData);
    
    // Crear SessionData con estructura correcta que GestionRedJugador espera
    laravelAPI.currentSessionData = new LaravelAPI.SessionData()
    {
        session = new LaravelAPI.SessionInfo()
        {
            id = payload.session.id,
            nombre = payload.session.nombre,
            estado = payload.session.estado
        },
        role = new LaravelAPI.RoleInfo()
        {
            id = payload.role.id,
            nombre = payload.role.nombre,  // ← "Juez" ✅
            descripcion = payload.role.descripcion,
            color = payload.role.color,
            icono = payload.role.icono
        }
    };
    
    DebugLogger.LogPhase("LaravelUnityEntryManager", "Datos de sesión recibidos", new Dictionary<string, object>
    {
        { "rolNombre", payload.role.nombre }  // Debug: "Juez"
    });
}
```

**Resultado:** `laravelAPI.currentSessionData` ahora tiene el rol "Juez" ✅

---

### PASO 3: GestionRedJugador encuentra el rol

Cuando `OnJoinedLobby()` se ejecuta:

```csharp
private void GetAssignedRoleFromSession()
{
    // Ahora SÍ encuentra datos en laravelAPI.currentSessionData
    if (laravelAPI != null && laravelAPI.currentSessionData != null && laravelAPI.currentSessionData.role != null)
    {
        assignedRole = laravelAPI.currentSessionData.role.nombre;  // ← "Juez" ✅
        hasAssignedRole = true;
        
        Debug.Log($"Rol asignado desde sesión (LaravelAPI): {assignedRole}");
        
        // Se une a la sala Photon como "Juez"
        JoinSessionRoom();
    }
    // ... resto de lógica no se ejecuta porque ya tenemos el rol
}
```

**Resultado:** 
- `assignedRole = "Juez"` ✅
- No ejecuta `FindAndClaimAnyAvailablePlayer()` ❌
- Busca y reclama el player "Player_Juez" ✅
- Cámara se posiciona en ubicación del Juez ✅

---

## FLUJO COMPLETO DESPUÉS DEL FIX

```
1. Ana selecciona "Juez" en modal Laravel
   ↓
2. Backend genera token con { user_id, session_id, role: "Juez" }
   ↓
3. Abre `/unity-game?token=eyJ...&session=2`
   ↓
4. JavaScript en game.blade.php:
   - Extrae token de URL
   - Llama `/api/unity-entry-info?token=...`
   - Recibe: { user, session, role: { nombre: "Juez", ... } }
   - Envía a Unity via window.sendToUnity()
   ↓
5. LaravelUnityEntryManager.ReceiveLaravelData():
   - Parsea JSON
   - Rellena laravelAPI.currentSessionData.role.nombre = "Juez"
   ↓
6. GestionRedJugador.GetAssignedRoleFromSession():
   - ENCUENTRA laravelAPI.currentSessionData.role.nombre = "Juez" ✅
   - assignedRole = "Juez"
   - hasAssignedRole = true
   ↓
7. Photon instantiation:
   - Busca y reclama "Player_Juez"
   - Cámara en posición de Juez ✅
```

---

## RESUMEN DE CAMBIOS

| Archivo | Cambio |
|---------|--------|
| `game.blade.php` | Nuevo JavaScript que extrae token del URL y llama `/api/unity-entry-info` |
| `LaravelUnityEntryManager.cs` | Nuevo método `ReceiveLaravelData()` que recibe datos de JavaScript |
| `LaravelUnityEntryManager.cs` | Nuevas clases `SessionDataPayload`, `UserPayload`, `SessionPayload`, `RolePayload` |
| `/api/unity-entry-info` | Ya existía, devuelve datos de sesión/usuario/rol cuando se pasa token |

---

## POR QUÉ PASÓ ESTO

El problema ocurrió porque:

1. **El token contenía el rol correcto** pero no se extraía de la URL
2. **Laravel no pasaba datos a la vista** porque la ruta era genérica
3. **JavaScript no enviaba datos a Unity** porque no había datos que enviar
4. **Unity caía al fallback aleatorio** porque no recibía información del rol
5. **La cadena de comunicación estaba rota** en el punto más crítico: entre Laravel y Unity

La arquitectura asumía que los datos se pasarían automáticamente, pero nadie estaba:
- Extrayendo el token de la URL
- Decodificando el token
- Enviando los datos a Unity
- Implementando el receptor de datos en Unity

---

## VALIDACIÓN

Para verificar que funciona:

1. **En Laravel:**
   - Abre DevTools → Console
   - Selecciona "Juez" y haz clic
   - Verifica que se envíe a `/api/unity-entry-info`
   - Confirma que responde con `role: { nombre: "Juez", ... }`

2. **En Unity:**
   - Abre Console (Ctrl+`)
   - Verifica que veas: `"Datos de sesión recibidos" { "rolNombre", "Juez" }`
   - Confirma que en GestionRedJugador dice: `Rol asignado desde sesión (LaravelAPI): Juez`
   - Observa que la cámara está en la posición del Juez (no de Perito2)

---

## ARQUITECTURA MEJORADA

Ahora la comunicación funciona así:

```
Laravel (Backend)
├── Genera token con rol
└── Endpoint `/api/unity-entry-info`

    ↓ (URL parameter)

JavaScript (Blade Template)
├── Extrae token del URL
├── Llama endpoint con token
└── Envía datos a Unity

    ↓ (SendMessage)

Unity C#
├── LaravelUnityEntryManager.ReceiveLaravelData()
├── Rellena LaravelAPI.currentSessionData
└── GestionRedJugador encuentra el rol y lo asigna ✅
```

Esta es la cadena de comunicación que FALTABA en la arquitectura original.

