#!/bin/bash
# Script para configurar Unity MCP en Cursor

set -e

echo "🔧 Configurando Unity MCP en Cursor..."
echo ""

# Ruta del proyecto
PROJECT_DIR="/Users/miguel/Local/Github/juiciosoralesuni"
CLIENT_SCRIPT="$PROJECT_DIR/unity-mcp-client.js"

# Verificar que el script existe
if [ ! -f "$CLIENT_SCRIPT" ]; then
    echo "❌ Error: No se encontró el archivo unity-mcp-client.js"
    echo "   Ruta esperada: $CLIENT_SCRIPT"
    exit 1
fi

# Hacer el script ejecutable
chmod +x "$CLIENT_SCRIPT"

echo "✅ Script encontrado: $CLIENT_SCRIPT"
echo ""

# Verificar que Unity está corriendo
echo "🔍 Verificando conexión con Unity..."
if lsof -i :6400 > /dev/null 2>&1; then
    echo "✅ Unity está escuchando en el puerto 6400"
else
    echo "⚠️  Advertencia: Unity no parece estar escuchando en el puerto 6400"
    echo "   Asegúrate de que Unity Editor esté abierto y el proyecto cargado"
fi

echo ""
echo "📋 Configuración para Cursor:"
echo ""
echo "Para configurar Unity MCP en Cursor, sigue estos pasos:"
echo ""
echo "1. Abre Cursor"
echo "2. Presiona Cmd+Shift+P (o Ctrl+Shift+P) para abrir la paleta de comandos"
echo "3. Busca y selecciona: 'MCP: Edit MCP Settings' o 'Preferences: Open User Settings (JSON)'"
echo "4. Agrega la siguiente configuración:"
echo ""
cat << 'EOF'
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
EOF

echo ""
echo ""
echo "💡 Alternativamente, puedes copiar el contenido del archivo:"
echo "   cursor-mcp-config.json"
echo ""
echo "📝 Nota: La configuración de MCP en Cursor puede estar en:"
echo "   ~/Library/Application Support/Cursor/User/globalStorage/rooveterinaryinc.roo-cline/settings/cline_mcp_settings.json"
echo "   O en la configuración de usuario de Cursor"
echo ""
echo "✅ Configuración lista. Reinicia Cursor después de agregar la configuración."
