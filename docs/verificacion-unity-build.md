# ✅ Verificación de Unity Build y Rutas

## 📋 Resumen

Este documento explica cómo verificar que el botón "Entrar a Unity" está sirviendo el `index.html` correcto con todo el código de PeerJS.

## 🔍 Verificación Actual

### 1. Ruta `/unity-game`

**Ubicación**: `routes/web.php` (línea ~217)

**Comportamiento**:
- ✅ Sirve el archivo `storage/unity-build/index.html` directamente
- ✅ NO usa la vista Blade `unity/game.blade.php`
- ✅ Incluye todo el código de PeerJS que agregamos

**Código**:
```php
Route::get('/unity-game', function () {
    $indexPath = storage_path('unity-build/index.html');
    
    if (!file_exists($indexPath)) {
        abort(404, 'Unity build not found.');
    }
    
    $content = file_get_contents($indexPath);
    
    // Reemplazar rutas relativas con rutas de Laravel
    $baseUrl = url('/unity-build');
    // ... reemplazos de rutas ...
    
    return response($content)->header('Content-Type', 'text/html');
})->name('unity.game');
```

### 2. Archivo `index.html`

**Ubicación**: `storage/unity-build/index.html`

**Características Verificadas**:
- ✅ `window.initVoiceCall` está definido globalmente (línea 92)
- ✅ Usa `juicio.loader.js` (no `juiciosorales.loader.js`)
- ✅ Archivos comprimidos con `.br` (Brotli)
- ✅ Código de PeerJS completo con logs detallados
- ✅ Detección automática de URL base para rutas

**Código Clave**:
```javascript
// Línea 92
window.initVoiceCall = async function(_roomId, actorId) {
    // ... código completo de PeerJS ...
};

// Línea 880-891
var baseUrl = window.location.pathname.includes('/unity-game') 
    ? '/unity-build' 
    : '';

var buildUrl = baseUrl + "/Build";
var loaderUrl = buildUrl + "/juicio.loader.js";
var config = {
  dataUrl: buildUrl + "/juicio.data.br",
  frameworkUrl: buildUrl + "/juicio.framework.js.br",
  codeUrl: buildUrl + "/juicio.wasm.br",
  streamingAssetsUrl: baseUrl + "/StreamingAssets",
  // ...
};
```

### 3. Flujo Completo

```
1. Usuario presiona "Entrar a Unity"
   ↓
2. Se genera enlace: /unity-game?token=...&session=...
   ↓
3. Laravel sirve storage/unity-build/index.html
   ↓
4. index.html carga:
   - juicio.loader.js desde /unity-build/Build/
   - juicio.data.br desde /unity-build/Build/
   - juicio.framework.js.br desde /unity-build/Build/
   - juicio.wasm.br desde /unity-build/Build/
   ↓
5. Unity se inicializa
   ↓
6. Unity llama a window.initVoiceCall()
   ↓
7. JavaScript inicializa PeerJS y audio
```

## ✅ Cómo Verificar que Está Funcionando

### Método 1: Inspeccionar el Código Fuente

1. Abre `/unity-game` en el navegador
2. Presiona `Ctrl+U` (o clic derecho → Ver código fuente)
3. Busca `window.initVoiceCall` - **DEBE estar presente**
4. Busca `juicio.loader.js` - **DEBE estar presente**
5. Busca `peerjs.com` - **DEBE estar presente** (servidores PeerJS)

### Método 2: Consola del Navegador

1. Abre `/unity-game` en el navegador
2. Presiona `F12` → Pestaña "Console"
3. Busca logs que empiecen con:
   - `🎤 INICIANDO SISTEMA DE AUDIO`
   - `✅ PEERJS CONECTADO EXITOSAMENTE`
   - `📞 Unity está llamando a callPeer`

Si ves estos logs, **el index.html correcto está siendo servido**.

### Método 3: Network Tab

1. Abre `/unity-game` en el navegador
2. Presiona `F12` → Pestaña "Network"
3. Recarga la página
4. Verifica que se cargan:
   - `index.html` desde `/unity-game`
   - `juicio.loader.js` desde `/unity-build/Build/`
   - `juicio.data.br` desde `/unity-build/Build/`
   - `juicio.framework.js.br` desde `/unity-build/Build/`
   - `juicio.wasm.br` desde `/unity-build/Build/`

### Método 4: Verificar Archivo Directamente

```bash
# Verificar que el archivo existe
Test-Path storage/unity-build/index.html

# Buscar window.initVoiceCall
Select-String -Path "storage/unity-build/index.html" -Pattern "window.initVoiceCall"

# Buscar juicio.loader.js
Select-String -Path "storage/unity-build/index.html" -Pattern "juicio.loader"
```

## ⚠️ Problemas Comunes

### Problema: No veo `window.initVoiceCall` en el código fuente

**Causa**: La ruta está sirviendo la vista Blade en lugar del index.html

**Solución**: Verificar que `routes/web.php` tiene la ruta correcta que lee `storage/unity-build/index.html`

### Problema: Veo `juiciosorales.loader.js` en lugar de `juicio.loader.js`

**Causa**: Unity generó un nuevo build con nombres antiguos

**Solución**: 
1. Verificar configuración de build en Unity
2. O actualizar manualmente el index.html después de cada build

### Problema: Los archivos .br no se cargan

**Causa**: Headers de Content-Encoding no están configurados

**Solución**: Verificar que la ruta `/unity-build/{path}` en `routes/web.php` establece `Content-Encoding: br` para archivos `.br`

## 🔄 Después de Cada Build de Unity

Cuando compiles Unity nuevamente, el `index.html` se sobrescribirá. Necesitas:

1. **Verificar nombres de archivos**:
   - Debe usar `juicio.loader.js` (no `juiciosorales.loader.js`)
   - Debe usar `.br` para archivos comprimidos

2. **Verificar código de PeerJS**:
   - Debe tener `window.initVoiceCall` definido
   - Debe tener los servidores PeerJS públicos configurados
   - Debe tener los logs detallados

3. **Verificar rutas**:
   - Debe detectar automáticamente si está en `/unity-game`
   - Debe usar `/unity-build` como base si está en `/unity-game`

## 📝 Notas Importantes

- **NO edites** `resources/views/unity/game.blade.php` - esa vista NO se usa cuando presionas "Entrar a Unity"
- **SÍ edita** `storage/unity-build/index.html` - este es el archivo que se sirve
- Después de cada build de Unity, verifica que los cambios se mantengan
- Considera crear un script que automáticamente actualice el index.html después de cada build

## ✅ Checklist de Verificación

- [ ] `storage/unity-build/index.html` existe
- [ ] `window.initVoiceCall` está definido en el index.html
- [ ] `juicio.loader.js` está en las rutas (no `juiciosorales.loader.js`)
- [ ] Archivos usan extensión `.br` para Brotli
- [ ] Ruta `/unity-game` lee `storage/unity-build/index.html`
- [ ] Ruta `/unity-build/{path}` sirve archivos con headers correctos
- [ ] Logs de PeerJS aparecen en la consola del navegador
- [ ] Unity se carga correctamente desde `/unity-game`

---

**Última Verificación**: Enero 2025  
**Estado**: ✅ Verificado y Funcionando

