# Configuración Unity MCP - Diagnóstico y Solución

## ✅ Diagnóstico

### Estado del Servidor Unity
- **Servidor Unity MCP**: ✅ **FUNCIONANDO**
- **Puerto**: `6400` 
- **Estado**: Escuchando en `127.0.0.1:6400`
- **Proceso**: PID 43328

### Pruebas Realizadas
Se probó la conexión directa al servidor Unity y **funciona correctamente**:
- ✅ Ping/Pong funciona
- ✅ Comandos JSON se procesan correctamente
- ✅ Respuestas se reciben en formato correcto

### Problema Identificado
El error **"No server info found"** en Cursor MCP **NO es un problema del servidor Unity**, sino de la configuración del cliente MCP.

El paquete npm `unity-editor-mcp` puede tener problemas para:
1. Conectarse al servidor TCP de Unity
2. Obtener información del servidor durante la inicialización
3. Configuración incorrecta en Cursor

## 🔧 Soluciones

### Solución 1: Usar Cliente MCP Personalizado (Recomendado)

He creado un cliente MCP personalizado (`unity-mcp-client.js`) que se conecta directamente al servidor TCP de Unity.

**Configuración en Cursor:**

1. Abre la configuración de MCP en Cursor
2. Agrega un nuevo servidor MCP con esta configuración:

```json
{
  "mcpServers": {
    "unity-editor": {
      "command": "node",
      "args": [
        "C:/Users/migue_pu8chth/Local/GitHub/juiciosorales/unity-mcp-client.js"
      ],
      "env": {}
    }
  }
}
```

**Nota**: Ajusta la ruta según tu ubicación del proyecto.

### Solución 2: Verificar Configuración del Paquete npm

Si prefieres usar el paquete `unity-editor-mcp` oficial, verifica:

1. **Variables de entorno necesarias:**
   ```bash
   UNITY_MCP_HOST=127.0.0.1
   UNITY_MCP_PORT=6400
   ```

2. **Configuración en Cursor:**
   ```json
   {
     "mcpServers": {
       "unity-editor-mcp": {
         "command": "npx",
         "args": [
           "-y",
           "unity-editor-mcp@latest"
         ],
         "env": {
           "UNITY_MCP_HOST": "127.0.0.1",
           "UNITY_MCP_PORT": "6400"
         }
       }
     }
   }
   ```

### Solución 3: Verificar que Unity esté Abierto

Asegúrate de que:
- ✅ Unity Editor esté abierto
- ✅ El proyecto Unity esté cargado
- ✅ El servidor MCP esté activo (verifica con `netstat -ano | findstr :6400`)

## 🧪 Pruebas

Para probar la conexión manualmente, usa el script de prueba:

```bash
node test-unity-mcp-connection.js
```

Este script verifica:
- Conexión TCP al servidor
- Comando ping
- Lectura de logs
- Estado del editor

## 📋 Comandos Disponibles

El servidor Unity MCP soporta los siguientes comandos:

### Gestión de GameObjects
- `create_gameobject` - Crear GameObject
- `find_gameobject` - Buscar GameObjects
- `modify_gameobject` - Modificar GameObject
- `delete_gameobject` - Eliminar GameObject
- `get_hierarchy` - Obtener jerarquía de escena

### Gestión de Escenas
- `create_scene` - Crear escena
- `load_scene` - Cargar escena
- `save_scene` - Guardar escena
- `list_scenes` - Listar escenas
- `get_scene_info` - Información de escena

### Scripts
- `create_script` - Crear script C#
- `read_script` - Leer script
- `update_script` - Actualizar script
- `delete_script` - Eliminar script
- `list_scripts` - Listar scripts
- `validate_script` - Validar script

### Componentes
- `add_component` - Agregar componente
- `remove_component` - Remover componente
- `modify_component` - Modificar componente
- `list_components` - Listar componentes

### Play Mode
- `play_game` - Iniciar Play Mode
- `pause_game` - Pausar juego
- `stop_game` - Detener juego
- `get_editor_state` - Estado del editor

### Logs y Consola
- `read_logs` - Leer logs
- `clear_logs` - Limpiar logs
- `clear_console` - Limpiar consola
- `enhanced_read_logs` - Leer logs mejorado

### Assets
- `create_prefab` - Crear prefab
- `create_material` - Crear material
- `refresh_assets` - Refrescar assets

Y muchos más...

## 🔍 Troubleshooting

### Error: "No server info found"
- **Causa**: El cliente MCP no puede conectarse al servidor Unity
- **Solución**: 
  1. Verifica que Unity esté abierto
  2. Verifica que el puerto 6400 esté escuchando
  3. Usa el cliente personalizado en lugar del paquete npm

### Error: "Connection timeout"
- **Causa**: El servidor Unity no responde
- **Solución**: 
  1. Reinicia Unity Editor
  2. Verifica que el proyecto esté cargado
  3. Revisa los logs de Unity para errores

### Error: "Port already in use"
- **Causa**: Otro proceso está usando el puerto 6400
- **Solución**: 
  1. Cierra otras instancias de Unity
  2. O cambia el puerto en UnityEditorMCP.cs

## 📝 Notas

- El servidor Unity MCP se inicia automáticamente cuando Unity Editor se abre
- El servidor escucha solo en `localhost` (127.0.0.1) por seguridad
- Los mensajes usan un protocolo con prefijo de longitud (4 bytes big-endian)
- El formato de comandos es JSON: `{id, type, params}`

