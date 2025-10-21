#!/bin/bash

# ========================================
# Script de Deploy de Unity Build (LOCAL)
# ========================================
# Este script es para usar en tu máquina local
# Sube el build de Unity al servidor via FTP

# Configuración del servidor FTP
FTP_HOST="187.218.232.139"
FTP_USER="simulador"
FTP_PASS="soporte25\$"
FTP_PATH="/var/www/juicios_local/unity-integration/builds/"

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

# Función para mostrar ayuda
show_help() {
    echo "Uso: $0 [carpeta-build]"
    echo ""
    echo "Opciones:"
    echo "  carpeta-build    Carpeta local con el build de Unity (opcional)"
    echo "                   Si no se especifica, usa 'builds/webgl/'"
    echo ""
    echo "Ejemplos:"
    echo "  $0                           # Usa builds/webgl/"
    echo "  $0 builds/webgl/             # Usa carpeta específica"
    echo "  $0 /path/to/unity/build/     # Usa ruta absoluta"
    echo ""
    echo "Configuración FTP:"
    echo "  Host: $FTP_HOST"
    echo "  User: $FTP_USER"
    echo "  Path: $FTP_PATH"
}

# Verificar si se pidió ayuda
if [[ "$1" == "-h" || "$1" == "--help" ]]; then
    show_help
    exit 0
fi

# Determinar carpeta de build
if [[ -n "$1" ]]; then
    BUILD_DIR="$1"
else
    BUILD_DIR="builds/webgl"
fi

# Verificar que la carpeta de build existe
if [[ ! -d "$BUILD_DIR" ]]; then
    print_status $RED "❌ Error: La carpeta de build '$BUILD_DIR' no existe"
    print_status $YELLOW "💡 Asegúrate de haber hecho el build de Unity primero"
    print_status $YELLOW "💡 O especifica la ruta correcta: $0 [carpeta-build]"
    exit 1
fi

# Verificar que hay archivos en la carpeta de build
if [[ -z "$(ls -A "$BUILD_DIR" 2>/dev/null)" ]]; then
    print_status $RED "❌ Error: La carpeta de build '$BUILD_DIR' está vacía"
    print_status $YELLOW "💡 Asegúrate de haber hecho el build de Unity correctamente"
    exit 1
fi

print_status $BLUE "🚀 Iniciando deploy de Unity build..."
print_status $YELLOW "📁 Carpeta de build: $BUILD_DIR"
print_status $YELLOW "🌐 Servidor FTP: $FTP_HOST"
print_status $YELLOW "📂 Destino: $FTP_PATH"

# Verificar que lftp está instalado
if ! command -v lftp &> /dev/null; then
    print_status $RED "❌ Error: lftp no está instalado"
    print_status $YELLOW "💡 Instala lftp:"
    print_status $YELLOW "   Ubuntu/Debian: sudo apt install lftp"
    print_status $YELLOW "   macOS: brew install lftp"
    print_status $YELLOW "   Windows: choco install lftp"
    exit 1
fi

# Crear archivo temporal con comandos lftp
TEMP_SCRIPT=$(mktemp)
cat > "$TEMP_SCRIPT" << EOF
# Configurar conexión FTP
set ftp:ssl-allow no
set ftp:passive-mode on
set ftp:auto-passive-mode on
set net:timeout 30
set net:max-retries 3

# Conectar al servidor
open $FTP_HOST
user $FTP_USER $FTP_PASS

# Verificar conexión
pwd

# Navegar al directorio de destino
cd $FTP_PATH

# Crear directorio si no existe
!mkdir -p $BUILD_DIR

# Subir archivos
mirror -R $BUILD_DIR/ ./

# Listar archivos subidos
ls -la

# Cerrar conexión
quit
EOF

print_status $BLUE "📤 Subiendo archivos al servidor..."

# Ejecutar lftp con el script
if lftp -f "$TEMP_SCRIPT"; then
    print_status $GREEN "✅ Deploy completado exitosamente!"
    print_status $GREEN "🌐 Build disponible en: https://juiciosorales.site/unity-integration/builds/"
    print_status $YELLOW "💡 Recuerda configurar tu servidor web para servir los archivos estáticos"
else
    print_status $RED "❌ Error durante el deploy"
    print_status $YELLOW "💡 Verifica las credenciales FTP y la conectividad"
    rm -f "$TEMP_SCRIPT"
    exit 1
fi

# Limpiar archivo temporal
rm -f "$TEMP_SCRIPT"

print_status $BLUE "🎉 Deploy de Unity completado!"
print_status $YELLOW "📋 Próximos pasos:"
print_status $YELLOW "   1. Verificar que los archivos se subieron correctamente"
print_status $YELLOW "   2. Configurar el servidor web para servir los archivos"
print_status $YELLOW "   3. Probar la aplicación en el navegador"
