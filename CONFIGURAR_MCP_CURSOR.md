# 🔌 Configurar Unity MCP en Cursor

## ✅ Estado Actual

- ✅ Unity está corriendo y escuchando en el puerto **6400**
- ✅ El cliente MCP (`unity-mcp-client.js`) está funcionando correctamente
- ✅ La conexión con Unity funciona (ping/pong exitoso)

## 📋 Pasos para Configurar MCP en Cursor

### Opción 1: Configuración Manual (Recomendado)

1. **Abre Cursor**
2. **Presiona `Cmd+Shift+P`** (o `Ctrl+Shift+P` en Windows/Linux) para abrir la paleta de comandos
3. **Busca y selecciona**: `MCP: Edit MCP Settings` o `Preferences: Open User Settings (JSON)`
4. **Agrega la siguiente configuración** al archivo JSON:

```json
{
  "mcpServers": {
    "unity-editor": {
      "command": "node",
      "args": [
        "/Users/miguel/Local/Github/juiciosoralesuni/unity-mcp-client.js"
      ],
      "env": {}
    }
  }
}
```

5. **Guarda el archivo** (`Cmd+S`)
6. **Reinicia Cursor** para que los cambios surtan efecto

### Opción 2: Usar el Archivo de Configuración

Puedes copiar el contenido del archivo `cursor-mcp-config.json` que está en la raíz del proyecto.

### Opción 3: Configuración Automática (si está disponible)

Ejecuta el script de configuración:

```bash
./setup-cursor-mcp.sh
```

## 🧪 Verificar la Configuración

Después de configurar, puedes verificar que MCP funciona:

1. **Abre Cursor**
2. Busca el panel de MCP o herramientas MCP
3. Deberías ver "unity-editor" en la lista de servidores MCP disponibles
4. Prueba ejecutando un comando como "read_logs" o "get_editor_state"

## 🔧 Herramientas Disponibles

Una vez configurado, tendrás acceso a estas herramientas de Unity:

- **`read_logs`** - Lee los logs de Unity
- **`get_editor_state`** - Obtiene el estado actual del editor Unity
- **`create_gameobject`** - Crea un nuevo GameObject en Unity
- Y muchas más...

## 🐛 Troubleshooting

### MCP no aparece en Cursor

- Verifica que hayas reiniciado Cursor después de agregar la configuración
- Asegúrate de que la ruta al script sea correcta y absoluta
- Verifica que Node.js esté instalado: `node --version`

### Error: "Cannot connect to Unity"

- Verifica que Unity Editor esté abierto
- Verifica que el proyecto Unity esté cargado
- Comprueba que el puerto 6400 esté escuchando: `lsof -i :6400`

### Error: "Script not found"

- Verifica que la ruta en la configuración sea correcta
- Asegúrate de que el archivo `unity-mcp-client.js` exista
- Verifica los permisos del archivo: `chmod +x unity-mcp-client.js`

## 📝 Notas

- El servidor Unity MCP se inicia automáticamente cuando Unity Editor se abre
- El servidor escucha solo en `localhost` (127.0.0.1) por seguridad
- Los mensajes usan un protocolo con prefijo de longitud (8 bytes big-endian)
- El formato de comandos es JSON: `{id, type, params}`

## 🔗 Archivos Relacionados

- `unity-mcp-client.js` - Cliente MCP personalizado
- `cursor-mcp-config.json` - Archivo de configuración de ejemplo
- `setup-cursor-mcp.sh` - Script de configuración
- `verify-unity-mcp.js` - Script de verificación de conexión
- `test-unity-mcp-connection.js` - Script de prueba de conexión
