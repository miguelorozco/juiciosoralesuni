# 🚀 Script de Compilación Unity WebGL

## 📋 Descripción

Script PowerShell para compilar el proyecto Unity en WebGL y colocar automáticamente el build en `storage/unity-build/`, donde Laravel lo puede servir.

## 🎯 Características

- ✅ Compila Unity WebGL automáticamente
- ✅ Coloca el build en `storage/unity-build/` (ruta correcta para Laravel)
- ✅ Crea backup del build anterior
- ✅ Verifica archivos críticos
- ✅ Muestra información detallada del build
- ✅ Limpia archivos temporales automáticamente

## 📦 Requisitos

1. **Unity Hub** instalado
2. **Unity Editor** (versión 2022.3.15f1 o superior recomendada)
3. **PowerShell** (incluido en Windows 10/11)

## 🚀 Uso

### Uso Básico

```powershell
.\build-unity-webgl.ps1
```

### Parámetros Opcionales

```powershell
# Especificar versión de Unity
.\build-unity-webgl.ps1 -UnityVersion "2022.3.15f1"

# Especificar ruta de destino personalizada
.\build-unity-webgl.ps1 -BuildPath "storage\unity-build"

# Solo compilar sin copiar (útil para testing)
.\build-unity-webgl.ps1 -SkipCopy
```

## 📁 Estructura de Archivos

```
juiciosorales/
├── build-unity-webgl.ps1          # Script principal
├── unity-integration/
│   └── unity-project/
│       └── Assets/
│           └── Editor/
│               └── BuildScript.cs  # Script C# de Unity
└── storage/
    └── unity-build/                # Build final (generado)
        ├── index.html
        ├── Build/
        │   ├── unity-build.loader.js
        │   ├── unity-build.data.br
        │   ├── unity-build.framework.js.br
        │   └── unity-build.wasm.br
        └── StreamingAssets/
```

## 🔄 Proceso de Compilación

1. **Búsqueda de Unity**: El script busca Unity Editor en las ubicaciones comunes
2. **Compilación**: Ejecuta Unity en modo batch para compilar WebGL
3. **Verificación**: Verifica que el build se completó exitosamente
4. **Backup**: Crea backup del build anterior (si existe)
5. **Copia**: Copia el build a `storage/unity-build/`
6. **Validación**: Verifica que todos los archivos críticos estén presentes
7. **Limpieza**: Elimina archivos temporales

## 📊 Información Mostrada

El script muestra:
- ✅ Ruta del proyecto Unity
- ✅ Ruta de build temporal y final
- ✅ Versión de Unity encontrada
- ✅ Progreso de compilación
- ✅ Tamaño total del build
- ✅ Lista de archivos generados
- ✅ Verificación de archivos críticos

## ⚠️ Solución de Problemas

### Error: "No se encontró Unity Editor"

**Causa**: Unity no está instalado o no está en las rutas esperadas.

**Solución**:
1. Instala Unity Hub desde [unity.com](https://unity.com/download)
2. Instala Unity Editor 2022.3.15f1 o superior
3. El script buscará automáticamente en las rutas comunes

### Error: "Build falló"

**Causa**: Errores de compilación en Unity.

**Solución**:
1. Revisa el log en `temp-unity-build/build.log`
2. Abre el proyecto en Unity Editor y verifica errores
3. Asegúrate de que todas las escenas estén configuradas en Build Settings

### Error: "Archivos críticos no encontrados"

**Causa**: Unity no generó todos los archivos necesarios.

**Solución**:
1. Verifica la configuración de compresión en Unity:
   - **Edit > Project Settings > Player > Publishing Settings**
   - **Compression Format**: Brotli
2. Verifica que el template `PlantillaJuicios` esté seleccionado
3. Revisa los logs de Unity para errores

## 🔧 Configuración Avanzada

### Cambiar Versión de Unity

Edita el script o usa el parámetro:

```powershell
.\build-unity-webgl.ps1 -UnityVersion "2023.1.0f1"
```

### Cambiar Ruta de Build

```powershell
.\build-unity-webgl.ps1 -BuildPath "custom\build\path"
```

### Solo Compilar (sin copiar)

```powershell
.\build-unity-webgl.ps1 -SkipCopy
```

El build quedará en `temp-unity-build/` para revisión.

## 📝 Notas Importantes

### ⚠️ Sincronización con Laravel

- El build **DEBE** estar en `storage/unity-build/` para que Laravel lo sirva
- Las rutas en `routes/web.php` apuntan a `storage_path('unity-build/')`
- El template `index.html` detecta automáticamente si está en `/unity-game`

### 🔄 Después de Cada Build

1. El script crea automáticamente un backup del build anterior
2. El nuevo build sobrescribe `storage/unity-build/`
3. Laravel servirá automáticamente el nuevo build

### 📦 Tamaño del Build

- Builds WebGL pueden ser grandes (50-200 MB)
- Los archivos `.br` (Brotli) están comprimidos
- El script muestra el tamaño total al finalizar

## 🎯 Próximos Pasos Después del Build

1. **Probar localmente**:
   ```bash
   php artisan serve
   # Visitar: http://localhost:8000/unity-game
   ```

2. **Verificar archivos**:
   - Revisa que `storage/unity-build/index.html` existe
   - Verifica que `storage/unity-build/Build/` contiene los archivos

3. **Desplegar**:
   - El build está listo para producción
   - Solo necesitas copiar `storage/unity-build/` al servidor

## 📚 Referencias

- [Documentación de Unity WebGL](https://docs.unity3d.com/Manual/webgl-building.html)
- [Sincronización de Rutas Laravel ↔ Unity](./sincronizacion-rutas-laravel-unity.md)
- [Guía de Integración Unity](../unity-integration/INTEGRATION_GUIDE.md)

