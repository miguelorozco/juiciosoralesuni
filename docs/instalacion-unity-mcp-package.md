# 📦 Instalación del Paquete Unity Editor MCP

## ✅ Estado Actual

El paquete `com.unity.editor-mcp` está instalado en el PackageCache pero **NO está registrado** en el `manifest.json`, lo que significa que Unity no lo reconoce oficialmente.

## 🔧 Instalación Correcta

### Opción 1: Instalar desde Git URL (Recomendado)

1. Abre Unity Editor
2. Ve a **Window > Package Manager**
3. Haz clic en el botón **+** (arriba a la izquierda)
4. Selecciona **Add package from git URL...**
5. Ingresa esta URL:
   ```
   https://github.com/ozankasikci/unity-editor-mcp.git?path=/package
   ```
6. Haz clic en **Add**

### Opción 2: Agregar Manualmente al manifest.json

Si prefieres hacerlo manualmente, edita el archivo:
`unity-integration/unity-project/Packages/manifest.json`

Y agrega esta línea en la sección `dependencies`:

```json
{
  "dependencies": {
    "com.unity.editor-mcp": "https://github.com/ozankasikci/unity-editor-mcp.git?path=/package",
    // ... otros paquetes
  }
}
```

### Opción 3: Instalar Versión Específica

Si necesitas una versión específica, puedes usar:

```
https://github.com/ozankasikci/unity-editor-mcp.git?path=/package#v0.15.0
```

## 📋 Dependencias Requeridas

El paquete MCP requiere automáticamente:
- ✅ `com.unity.nuget.newtonsoft-json` (versión 3.2.1)

Unity debería instalarlo automáticamente, pero si no:

1. **Window > Package Manager**
2. Busca **Newtonsoft Json** en el registro de Unity
3. O agrega manualmente:
   ```json
   "com.unity.nuget.newtonsoft-json": "3.2.1"
   ```

## ✅ Verificación

Después de instalar, verifica que:

1. El paquete aparece en **Window > Package Manager** bajo "In Project"
2. El servidor MCP se inicia automáticamente cuando abres Unity
3. Puedes ver logs en la consola de Unity:
   ```
   [Unity Editor MCP] Initializing...
   [Unity Editor MCP] TCP listener started on port 6400
   ```

## 🔍 Troubleshooting

### El paquete no aparece en Package Manager
- Verifica que la URL de Git sea correcta
- Asegúrate de tener conexión a internet
- Revisa la consola de Unity para errores

### Error de dependencias
- Instala manualmente `com.unity.nuget.newtonsoft-json`
- Reinicia Unity Editor

### El servidor no inicia
- Verifica que no haya otro proceso usando el puerto 6400
- Revisa los logs de Unity para errores específicos
- Asegúrate de que el proyecto esté completamente cargado

## 📝 Notas

- El paquete se instala automáticamente cuando Unity se abre
- El servidor TCP se inicia en el puerto 6400 por defecto
- Solo escucha en `localhost` (127.0.0.1) por seguridad
- No requiere configuración adicional después de la instalación

