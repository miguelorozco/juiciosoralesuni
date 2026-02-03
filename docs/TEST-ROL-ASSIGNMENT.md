# GUÍA DE PRUEBA: Validación del Fix de Asignación de Rol

## REQUISITOS PREVIOS

- ✅ Laravel compilado sin errores
- ✅ Unity compilado sin errores (Scripts validados)
- ✅ Photon conectado y funcionando
- ✅ Base de datos con sesión de prueba y Ana asignada como Juez

---

## PASO 1: Preparar los Datos de Prueba

### En Laravel (Tinker)

```bash
php artisan tinker
```

```php
// Verificar que existe la sesión con id=2
$sesion = \App\Models\SesionJuicio::find(2);
echo "Sesión: " . $sesion->nombre;

// Verificar que existe la asignación de Ana como Juez
$ana = \App\Models\User::where('email', 'ana@example.com')->first();
$asignacion = \App\Models\AsignacionRol::where('usuario_id', $ana->id)
    ->where('sesion_id', 2)
    ->with('rolDisponible')
    ->first();
    
echo "Ana asignada a: " . $asignacion->rolDisponible->nombre; // Debe decir "Juez"
```

---

## PASO 2: Probar en Laravel (Browser)

### 2.1 Inicia sesión como Ana
```
URL: http://localhost:8000/login
Email: ana@example.com
Password: password
```

### 2.2 Abre la sesión
```
URL: http://localhost:8000/sesiones/2
```

### 2.3 Abre DevTools (F12)
- Abre la pestaña **Console**
- Abre la pestaña **Network**

### 2.4 Haz clic en "Entrar a Unity"
- Se debe abrir el modal de selección de roles
- Verifica que veas un badge azul que dice **"Tu rol"** en "Juez"
- Deberías VER:
  - ❌ "Ocupado" en otros roles como "Perito2", "Perito3", etc.
  - ✅ "Tu rol" en "Juez"

### 2.5 Selecciona el rol "Juez"
- Haz clic en el botón "Juez"
- Espera a que se cierre el modal

### 2.6 Observa en Network
Deberías ver estas peticiones HTTP en orden:

1. **POST `/api/unity-entry/generate`**
   - Body: `{ user_id: 2, session_id: 2 }`
   - Response: `{ success: true, token: "eyJ...", url: "/unity-game?token=..." }`

2. **GET `/unity-game?token=...&session=2`**
   - Carga la página Blade template

3. **GET `/api/unity-entry-info?token=...`** ← Este es el nuevo endpoint
   - Response: 
   ```json
   {
     "success": true,
     "data": {
       "user": { "id": 2, "name": "Ana", "email": "ana@example.com" },
       "session": { "id": 2, "nombre": "Sesión prueba", "estado": "activa" },
       "role": { 
         "id": 3, 
         "nombre": "Juez",              // ← Esto es lo importante
         "descripcion": "Juez del caso",
         "color": "#FF6B6B",
         "icono": "judge"
       }
     }
   }
   ```

### 2.7 Observa en Console

Deberías ver logs como:
```
[DEBUG] setupLaravelCommunication() - Parámetros de entrada extraídos
  hasToken: true
  sessionId: 2

[DEBUG] setupLaravelCommunication() - Obteniendo datos de sesión desde Laravel...

[DEBUG] setupLaravelCommunication() - Datos de sesión obtenidos
  userName: Ana
  roleName: Juez        // ← Aquí debe decir "Juez"
  sessionId: 2
```

---

## PASO 3: Verificar en Unity

### 3.1 Unity Console (Ctrl+`)

Una vez que se abra Unity, en la Console deberías ver:

```
[LaravelUnityEntryManager] Recibiendo datos de sesión desde Laravel
[LaravelUnityEntryManager] Datos de sesión recibidos
  rolNombre: Juez       // ← Aquí debe decir "Juez"

[GestionRedJugador] Rol asignado desde sesión (LaravelAPI): Juez

[GestionRedJugador] Conectando a la sala de Photon...

[GestionRedJugador] ✅ Unido a sala Photon con rol: Juez
```

### 3.2 Verifica la Cámara

En la escena, observa:
- ✅ La cámara está posicionada donde está el Juez (usualmente en la cabecera de la sala)
- ❌ NO está en la posición de Perito2 (que estaría a un lado)
- ✅ Puedes ver a los otros personajes frente a ti

### 3.3 Verifica que no hay logs de fallback

Busca en la console estos logs (que NO deberían aparecer):

```
❌ [GestionRedJugador] No se proporcionó ROLE. Intentando reclamar un Player disponible automáticamente...
❌ [GestionRedJugador] Player reclamado automáticamente: Player_Perito2
```

Si ves estos logs, significa que el rol NO se está recibiendo correctamente.

---

## PASO 4: Validación Final

Marca estas casillas:

- [ ] En Laravel Console veo el log `roleName: Juez`
- [ ] En Unity Console veo `Rol asignado desde sesión (LaravelAPI): Juez`
- [ ] La cámara está posicionada correctamente (donde está el Juez)
- [ ] NO veo logs de fallback random (`Player reclamado automáticamente`)
- [ ] Puedo ver a los demás personajes/participantes
- [ ] El micrófono/audio está asociado al rol Juez

---

## TROUBLESHOOTING

### Problema: El modal de roles no aparece

**Causa:** El endpoint `/api/sesiones/{id}/roles-disponibles` no está funcionando

**Solución:**
```bash
# Verificar que la ruta existe
php artisan route:list | grep roles-disponibles

# Si no aparece, agregar a routes/web.php:
Route::get('/api/sesiones/{sesion}/roles-disponibles', [SesionController::class, 'getRolesDisponibles']);
```

---

### Problema: En Network no veo `/api/unity-entry-info`

**Causa:** JavaScript no está extrayendo el token del URL

**Solución:**
1. Abre DevTools Console
2. Ejecuta manualmente:
   ```javascript
   function getQueryParameter(name) {
       const url = window.location.href;
       name = name.replace(/[\[\]]/g, '\\$&');
       const regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)');
       const results = regex.exec(url);
       if (!results) return null;
       if (!results[2]) return '';
       return decodeURIComponent(results[2].replace(/\+/g, ' '));
   }
   
   const token = getQueryParameter('token');
   console.log("Token extraído:", !!token);
   ```
3. Debe imprimir `Token extraído: true`

---

### Problema: `/api/unity-entry-info` responde con error 401/404

**Causa:** Token expirado o inválido

**Solución:**
1. Verifica que el token NO expire antes de enviarlo a Unity
2. En `UnityEntryController.getUnityEntryInfo()`, el tiempo debe estar en segundos (no milisegundos)
3. Verifica que `time()` en PHP está sincronizado con el servidor

---

### Problema: Unity no recibe datos (no veo logs de ReceiveLaravelData)

**Causa:** `LaravelUnityEntryManager.ReceiveLaravelData()` no se está llamando

**Solución:**
1. En Console, ejecuta:
   ```javascript
   // Forzar el envío de datos a Unity
   var testData = {
       user: { id: 2, name: "Ana", email: "ana@example.com" },
       session: { id: 2, nombre: "Test", estado: "activa" },
       role: { id: 3, nombre: "Juez", descripcion: "Test", color: "#FF0000", icono: "test" }
   };
   window.sendToUnity(testData);
   ```
2. En Unity Console, deberías ver: `"Datos de sesión recibidos" { "rolNombre", "Juez" }`
3. Si no aparece, significa que el método NO existe o NO está siendo llamado correctamente

---

### Problema: Unity asigna "Observador" en lugar de "Juez"

**Causa:** `laravelAPI.currentSessionData` está vacío

**Solución:**
1. Verifica que `ReceiveLaravelData()` se está ejecutando
2. Agrega un log manual en `GestionRedJugador.GetAssignedRoleFromSession()`:
   ```csharp
   Debug.Log("laravelAPI.currentSessionData = " + (laravelAPI.currentSessionData == null ? "NULL" : laravelAPI.currentSessionData.role?.nombre));
   ```
3. Si es NULL, significa que `ReceiveLaravelData()` no se ejecutó

---

## LOGS ESPERADOS (ORDEN CORRECTO)

### En Browser Console (Laravel):

```
1. "setupLaravelCommunication() - Parámetros de entrada extraídos"
2. "setupLaravelCommunication() - Obteniendo datos de sesión desde Laravel..."
3. "setupLaravelCommunication() - Datos de sesión obtenidos" { "rolName": "Juez" }
4. Network request GET /api/unity-entry-info?token=... → 200 OK
```

### En Unity Console:

```
1. "[LaravelUnityEntryManager] Recibiendo datos de sesión desde Laravel"
2. "[LaravelUnityEntryManager] Datos de sesión recibidos" { "rolNombre": "Juez" }
3. "[GestionRedJugador] Rol asignado desde sesión (LaravelAPI): Juez"
4. "[GestionRedJugador] Conectando a la sala de Photon..."
5. "[GestionRedJugador] ✅ Unido a sala Photon con rol: Juez"
```

Si ves estos logs EN ESTE ORDEN, todo está funcionando correctamente ✅

