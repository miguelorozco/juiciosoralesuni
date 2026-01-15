# 🔗 Sincronización de Rutas Laravel ↔ Unity

## 📋 Resumen

Este documento explica cómo están sincronizadas las rutas entre Laravel y Unity para servir el build WebGL correctamente.

## 🗂️ Estructura de Directorios

### Unity Build Output
```
storage/unity-build/
├── index.html          # Template HTML con PeerJS
├── Build/
│   ├── unity-build.loader.js
│   ├── unity-build.data.br
│   ├── unity-build.framework.js.br
│   └── unity-build.wasm.br
└── StreamingAssets/    # (si existe)
```

### Laravel Routes
```
routes/web.php
├── /unity-game          → Sirve storage/unity-build/index.html
└── /unity-build/{path}  → Sirve archivos desde storage/unity-build/{path}
```

## 🔄 Flujo de Sincronización

### 1. Compilación de Unity
- **Ubicación**: Unity compila a `storage/unity-build/`
- **Template usado**: `Assets/WebGLTemplates/PlantillaJuicios/index.html`
- **Resultado**: Unity copia el template y genera los archivos `.br` comprimidos

### 2. Servicio desde Laravel

#### Ruta `/unity-game` (Línea 219 en `routes/web.php`)
```php
Route::get('/unity-game', function () {
    $indexPath = storage_path('unity-build/index.html');
    // Sirve el index.html directamente
    return response(file_get_contents($indexPath), 200)
        ->header('Content-Type', 'text/html; charset=utf-8');
});
```

#### Ruta `/unity-build/{path}` (Línea 149 en `routes/web.php`)
```php
Route::get('/unity-build/{path}', function ($path) {
    $filePath = storage_path('unity-build/' . $path);
    // Sirve archivos con headers correctos para .br
    return response()->file($filePath);
});
```

### 3. Detección Automática en el Template

El `index.html` detecta automáticamente si está siendo servido desde Laravel:

```javascript
// Detectar si estamos en /unity-game
var baseUrl = "";
if (window.location.pathname.includes('/unity-game')) {
  baseUrl = "/unity-build";  // Usar ruta de Laravel
} else {
  baseUrl = "";  // Rutas relativas para desarrollo local
}

var buildUrl = baseUrl + "/Build";
var loaderUrl = buildUrl + "/unity-build.loader.js";
```

## ✅ Verificación de Sincronización

### Checklist de Sincronización

- [x] **Rutas Laravel configuradas**
  - `/unity-game` → `storage/unity-build/index.html`
  - `/unity-build/{path}` → `storage/unity-build/{path}`

- [x] **Template detecta Laravel automáticamente**
  - Detecta `/unity-game` en la URL
  - Ajusta `baseUrl` a `/unity-build`

- [x] **Nombres de archivos sincronizados**
  - Template usa: `unity-build.*` (nombre actual del build)
  - Archivos reales: `unity-build.*` en `storage/unity-build/Build/`

- [x] **Soporte para archivos comprimidos (.br)**
  - Template busca archivos con extensión `.br`
  - Laravel sirve archivos `.br` con header `Content-Encoding: br`

- [x] **Headers CORS configurados**
  - Laravel establece headers CORS para Unity
  - Permite requests desde cualquier origen

## 🔧 Configuración de Unity

### Build Settings
1. **File > Build Settings**
2. Seleccionar **WebGL**
3. **Player Settings > Publishing Settings**:
   - Compression Format: **Brotli** (genera archivos `.br`)
   - Data Caching: Enabled

### WebGL Template
- **Template usado**: `PlantillaJuicios`
- **Ubicación**: `Assets/WebGLTemplates/PlantillaJuicios/`
- **Características**:
  - Detección automática de rutas Laravel
  - Soporte para archivos `.br`
  - Integración PeerJS completa

## 📝 Notas Importantes

### ⚠️ Nombres de Archivos
Unity puede generar diferentes nombres según la configuración:
- `unity-build.*` (actual)
- `juiciosorales.*` (anterior)
- `juicio.*` (alternativo)

El template intenta automáticamente con `unity-build` primero. Si Unity genera un nombre diferente, ajustar en el template.

### 🔄 Después de Cada Build
1. Unity sobrescribe `storage/unity-build/index.html`
2. El template se regenera automáticamente con la detección de rutas
3. **NO es necesario** editar manualmente después de cada build

### 🚀 Desarrollo Local
Para desarrollo local (sin Laravel):
- El template detecta automáticamente y usa rutas relativas
- Funciona directamente desde el sistema de archivos

## 🐛 Troubleshooting

### Problema: Archivos no se cargan
**Causa**: Nombres de archivos no coinciden

**Solución**:
1. Verificar nombres en `storage/unity-build/Build/`
2. Ajustar `buildName` en el template si es necesario

### Problema: 404 en `/unity-build/Build/...`
**Causa**: Ruta de Laravel no encuentra el archivo

**Solución**:
1. Verificar que el archivo existe en `storage/unity-build/Build/`
2. Verificar permisos del directorio `storage/unity-build/`
3. Revisar logs de Laravel: `storage/logs/laravel.log`

### Problema: Archivos .br no se descomprimen
**Causa**: Headers de Content-Encoding no configurados

**Solución**:
- Verificar que la ruta `/unity-build/{path}` en `routes/web.php` establece `Content-Encoding: br` para archivos `.br`

## 📊 Resumen de Rutas

| Ruta Laravel | Archivo Físico | Propósito |
|--------------|----------------|-----------|
| `/unity-game` | `storage/unity-build/index.html` | Página principal del juego |
| `/unity-build/Build/unity-build.loader.js` | `storage/unity-build/Build/unity-build.loader.js` | Loader de Unity |
| `/unity-build/Build/unity-build.data.br` | `storage/unity-build/Build/unity-build.data.br` | Datos del juego (comprimido) |
| `/unity-build/Build/unity-build.framework.js.br` | `storage/unity-build/Build/unity-build.framework.js.br` | Framework (comprimido) |
| `/unity-build/Build/unity-build.wasm.br` | `storage/unity-build/Build/unity-build.wasm.br` | WebAssembly (comprimido) |

## ✅ Estado Actual

- ✅ Rutas Laravel configuradas correctamente
- ✅ Template detecta automáticamente Laravel
- ✅ Soporte para archivos `.br` (Brotli)
- ✅ Headers CORS configurados
- ✅ Nombres de archivos sincronizados (`unity-build.*`)

**Última actualización**: Template actualizado para usar `unity-build` como nombre por defecto y detectar automáticamente rutas Laravel.

