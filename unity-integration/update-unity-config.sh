#!/bin/bash

# ========================================
# Script para actualizar configuración de Unity
# ========================================
# Este script actualiza la URL del servidor en la configuración de Unity

# Configuración
UNITY_CONFIG_FILE="unity-project/Assets/StreamingAssets/unity-config.json"
SERVER_URL="https://juiciosorales.site/api"
PHOTON_APP_ID="2ec23c58-5cc4-419d-8214-13abad14a02f"

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Función para imprimir mensajes con colores
print_status() {
    local color=$1
    local message=$2
    echo -e "${color}${message}${NC}"
}

print_status $BLUE "🔧 Actualizando configuración de Unity..."

# Verificar que el archivo de configuración existe
if [[ ! -f "$UNITY_CONFIG_FILE" ]]; then
    print_status $RED "❌ Error: No se encontró el archivo de configuración"
    print_status $YELLOW "💡 Asegúrate de estar en el directorio correcto"
    exit 1
fi

# Crear backup del archivo original
cp "$UNITY_CONFIG_FILE" "${UNITY_CONFIG_FILE}.backup"
print_status $YELLOW "📋 Backup creado: ${UNITY_CONFIG_FILE}.backup"

# Actualizar la configuración
cat > "$UNITY_CONFIG_FILE" << EOF
{
  "laravelApiBaseUrl": "$SERVER_URL",
  "photonAppId": "$PHOTON_APP_ID",
  "environment": "production",
  "debugMode": false,
  "enableLogging": true,
  "maxRetries": 3,
  "timeout": 30,
  "version": "1.0.0"
}
EOF

print_status $GREEN "✅ Configuración actualizada exitosamente!"
print_status $YELLOW "📋 Configuración actual:"
print_status $YELLOW "   Server URL: $SERVER_URL"
print_status $YELLOW "   Photon App ID: $PHOTON_APP_ID"
print_status $YELLOW "   Environment: production"
print_status $YELLOW "   Debug Mode: false"

print_status $BLUE "💡 Próximos pasos:"
print_status $YELLOW "   1. Hacer build de Unity con la nueva configuración"
print_status $YELLOW "   2. Subir el build al servidor"
print_status $YELLOW "   3. Probar la integración completa"
