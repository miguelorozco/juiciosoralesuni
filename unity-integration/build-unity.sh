#!/bin/bash

# Script para construir Unity WebGL para producción
# Uso: ./build-unity.sh [config-file]

set -e

# Configuración
UNITY_PROJECT_PATH="./unity-project"
BUILD_PATH="./build"
CONFIG_FILE=${1:-"production"}

echo "🚀 Iniciando build de Unity WebGL..."

# Verificar que Unity esté instalado
if ! command -v unity &> /dev/null; then
    echo "❌ Unity no está instalado o no está en PATH"
    echo "Por favor instala Unity Hub y Unity 2022.3.15f1 o superior"
    exit 1
fi

# Crear directorio de build
mkdir -p $BUILD_PATH

# Configurar variables de build
case $CONFIG_FILE in
    "development")
        echo "🔧 Configurando para desarrollo..."
        API_URL="http://localhost:8000/api"
        DEBUG_MODE="true"
        ;;
    "staging")
        echo "🔧 Configurando para staging..."
        API_URL="https://staging.juiciosorales.com/api"
        DEBUG_MODE="true"
        ;;
    "production")
        echo "🔧 Configurando para producción..."
        API_URL="https://juiciosorales.com/api"
        DEBUG_MODE="false"
        ;;
    *)
        echo "❌ Configuración no válida: $CONFIG_FILE"
        echo "Opciones válidas: development, staging, production"
        exit 1
        ;;
esac

# Actualizar configuración
echo "📝 Actualizando configuración..."
cat > $UNITY_PROJECT_PATH/Assets/StreamingAssets/unity-config.json << EOF
{
  "api": {
    "baseURL": "$API_URL",
    "timeout": 30,
    "retryAttempts": 3
  },
  "photon": {
    "appId": "YOUR_PHOTON_APP_ID",
    "region": "us",
    "maxPlayers": 20,
    "connectionTimeout": 30
  },
  "peerjs": {
    "servers": [
      {
        "host": "juiciosorales.site",
        "port": 443,
        "secure": true,
        "path": "/peerjs"
      }
    ],
    "stunServers": [
      "stun:stun.l.google.com:19302"
    ]
  },
  "audio": {
    "echoCancellation": true,
    "noiseSuppression": true,
    "autoGainControl": true,
    "sampleRate": 44100,
    "channelCount": 1,
    "latency": 0.01
  },
  "debug": {
    "enabled": $DEBUG_MODE,
    "logLevel": "info",
    "showDebugPanel": $DEBUG_MODE
  },
  "session": {
    "defaultSesionId": 1,
    "autoLogin": false
  }
}
EOF

# Ejecutar build de Unity
echo "🔨 Ejecutando build de Unity..."
unity -batchmode -quit -projectPath $UNITY_PROJECT_PATH -buildTarget WebGL -executeMethod BuildScript.BuildWebGL -buildPath $BUILD_PATH

# Verificar que el build fue exitoso
if [ ! -d "$BUILD_PATH" ] || [ -z "$(ls -A $BUILD_PATH)" ]; then
    echo "❌ Build falló - directorio de build vacío"
    exit 1
fi

# Optimizar archivos
echo "⚡ Optimizando archivos..."

# Comprimir archivos .js y .wasm
if command -v gzip &> /dev/null; then
    find $BUILD_PATH -name "*.js" -exec gzip -9 -k {} \;
    find $BUILD_PATH -name "*.wasm" -exec gzip -9 -k {} \;
    echo "✅ Archivos comprimidos con gzip"
fi

# Crear archivo de información del build
cat > $BUILD_PATH/build-info.json << EOF
{
  "buildDate": "$(date -u +%Y-%m-%dT%H:%M:%SZ)",
  "config": "$CONFIG_FILE",
  "apiUrl": "$API_URL",
  "debugMode": $DEBUG_MODE,
  "unityVersion": "2022.3.15f1",
  "buildTarget": "WebGL"
}
EOF

# Crear archivo .htaccess para Apache
cat > $BUILD_PATH/.htaccess << EOF
# Configuración para Unity WebGL
RewriteEngine On

# Habilitar compresión
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
    AddOutputFilterByType DEFLATE application/wasm
</IfModule>

# Headers de cache
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType application/wasm "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
</IfModule>

# CORS headers
<IfModule mod_headers.c>
    Header always set Access-Control-Allow-Origin "*"
    Header always set Access-Control-Allow-Methods "GET, POST, OPTIONS"
    Header always set Access-Control-Allow-Headers "Content-Type, Authorization, X-Unity-Version, X-Unity-Platform"
</IfModule>
EOF

echo "✅ Build completado exitosamente!"
echo "📁 Archivos de build en: $BUILD_PATH"
echo "🌐 Para servir: cd $BUILD_PATH && python -m http.server 8080"
echo "📊 Información del build: $BUILD_PATH/build-info.json"

# Mostrar tamaño del build
BUILD_SIZE=$(du -sh $BUILD_PATH | cut -f1)
echo "📦 Tamaño del build: $BUILD_SIZE"
