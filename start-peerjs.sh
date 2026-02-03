#!/bin/bash

###############################################################################
# Script de Inicio Automático para Servidor PeerJS
# 
# Este script inicia el servidor PeerJS automáticamente con la configuración
# del archivo .env del proyecto Laravel
###############################################################################

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Directorio del proyecto (ruta absoluta del script)
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$PROJECT_DIR"

# Archivo de configuración
ENV_FILE="$PROJECT_DIR/.env"
PID_FILE="/tmp/juiciosorales-peerjs.pid"
LOG_FILE="$PROJECT_DIR/storage/logs/peerjs.log"

echo -e "${BLUE}╔══════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   Iniciador Automático - Servidor PeerJS            ║${NC}"
echo -e "${BLUE}╚══════════════════════════════════════════════════════╝${NC}"
echo ""

# Verificar que existe el archivo .env
if [ ! -f "$ENV_FILE" ]; then
    echo -e "${RED}❌ Error: No se encuentra el archivo .env${NC}"
    echo -e "   Ubicación esperada: $ENV_FILE"
    exit 1
fi

# Función para leer variables del .env
get_env_var() {
    local var_name=$1
    local default_value=$2
    
    # Intentar leer del .env
    local value=$(grep "^${var_name}=" "$ENV_FILE" 2>/dev/null | cut -d '=' -f2- | tr -d '"' | tr -d "'")
    
    # Si no existe o está vacío, usar valor por defecto
    if [ -z "$value" ]; then
        echo "$default_value"
    else
        echo "$value"
    fi
}

# Leer configuración del .env
PEERJS_HOST=$(get_env_var "PEERJS_HOST" "0.0.0.0")
PEERJS_PORT=$(get_env_var "PEERJS_PORT" "9000")
PEERJS_PATH=$(get_env_var "PEERJS_PATH" "/")
PEERJS_KEY=$(get_env_var "PEERJS_KEY" "peerjs")
APP_URL=$(get_env_var "APP_URL" "http://localhost")

echo -e "${GREEN}✓${NC} Configuración cargada desde .env:"
echo -e "  Host:     ${YELLOW}$PEERJS_HOST${NC}"
echo -e "  Puerto:   ${YELLOW}$PEERJS_PORT${NC}"
echo -e "  Path:     ${YELLOW}$PEERJS_PATH${NC}"
echo -e "  Key:      ${YELLOW}$PEERJS_KEY${NC}"
echo -e "  APP_URL:  ${YELLOW}$APP_URL${NC}"
echo ""

# Verificar si ya está corriendo
if [ -f "$PID_FILE" ]; then
    OLD_PID=$(cat "$PID_FILE")
    if ps -p "$OLD_PID" > /dev/null 2>&1; then
        echo -e "${YELLOW}⚠️  El servidor PeerJS ya está corriendo (PID: $OLD_PID)${NC}"
        echo ""
        read -p "¿Deseas reiniciarlo? (s/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[SsYy]$ ]]; then
            echo -e "${BLUE}ℹ️  Manteniendo servidor actual${NC}"
            exit 0
        fi
        
        echo -e "${YELLOW}⏹️  Deteniendo servidor actual...${NC}"
        kill "$OLD_PID" 2>/dev/null
        sleep 2
        
        # Forzar si aún está corriendo
        if ps -p "$OLD_PID" > /dev/null 2>&1; then
            kill -9 "$OLD_PID" 2>/dev/null
        fi
        
        rm -f "$PID_FILE"
        echo -e "${GREEN}✓${NC} Servidor anterior detenido"
    else
        # PID file obsoleto
        rm -f "$PID_FILE"
    fi
fi

# Verificar si el puerto está en uso
if lsof -Pi :$PEERJS_PORT -sTCP:LISTEN -t >/dev/null 2>&1 ; then
    echo -e "${RED}❌ Error: El puerto $PEERJS_PORT ya está en uso${NC}"
    echo ""
    echo "Procesos usando el puerto:"
    lsof -Pi :$PEERJS_PORT -sTCP:LISTEN
    echo ""
    read -p "¿Deseas matar estos procesos? (s/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[SsYy]$ ]]; then
        lsof -ti:$PEERJS_PORT | xargs kill -9 2>/dev/null
        sleep 2
        echo -e "${GREEN}✓${NC} Procesos eliminados"
    else
        exit 1
    fi
fi

# Verificar que Node.js está instalado
if ! command -v node &> /dev/null; then
    echo -e "${RED}❌ Error: Node.js no está instalado${NC}"
    exit 1
fi

NODE_VERSION=$(node --version)
echo -e "${GREEN}✓${NC} Node.js: ${YELLOW}$NODE_VERSION${NC}"

# Verificar dependencias
if [ ! -d "$PROJECT_DIR/node_modules" ]; then
    echo -e "${YELLOW}⚠️  Dependencias no instaladas. Instalando...${NC}"
    npm install
    if [ $? -ne 0 ]; then
        echo -e "${RED}❌ Error instalando dependencias${NC}"
        exit 1
    fi
fi

# Verificar que existe el servidor PeerJS
PEERJS_SERVER="$PROJECT_DIR/peerjs-server-local.js"
if [ ! -f "$PEERJS_SERVER" ]; then
    echo -e "${RED}❌ Error: No se encuentra peerjs-server-local.js${NC}"
    exit 1
fi

# Crear directorio de logs si no existe
mkdir -p "$(dirname "$LOG_FILE")"

echo ""
echo -e "${BLUE}🚀 Iniciando servidor PeerJS...${NC}"

# Exportar variables de entorno para el servidor Node
export PEERJS_PORT="$PEERJS_PORT"
export PEERJS_HOST="$PEERJS_HOST"
export PEERJS_PATH="$PEERJS_PATH"
export PEERJS_KEY="$PEERJS_KEY"

# Iniciar servidor en segundo plano
nohup node "$PEERJS_SERVER" > "$LOG_FILE" 2>&1 &
PEERJS_PID=$!

# Guardar PID
echo $PEERJS_PID > "$PID_FILE"

# Esperar un momento para verificar que inició correctamente
sleep 2

if ps -p $PEERJS_PID > /dev/null 2>&1; then
    echo -e "${GREEN}✅ Servidor PeerJS iniciado correctamente${NC}"
    echo ""
    echo -e "${BLUE}╔══════════════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║   Información del Servidor                          ║${NC}"
    echo -e "${BLUE}╠══════════════════════════════════════════════════════╣${NC}"
    echo -e "${BLUE}║${NC} PID:          ${GREEN}$PEERJS_PID${NC}"
    echo -e "${BLUE}║${NC} URL Local:    ${YELLOW}http://localhost:$PEERJS_PORT${NC}"
    echo -e "${BLUE}║${NC} URL Red:      ${YELLOW}http://$PEERJS_HOST:$PEERJS_PORT${NC}"
    echo -e "${BLUE}║${NC} Health:       ${YELLOW}http://localhost:$PEERJS_PORT/health${NC}"
    echo -e "${BLUE}║${NC} Info:         ${YELLOW}http://localhost:$PEERJS_PORT/info${NC}"
    echo -e "${BLUE}║${NC} Logs:         ${YELLOW}$LOG_FILE${NC}"
    echo -e "${BLUE}║${NC} PID File:     ${YELLOW}$PID_FILE${NC}"
    echo -e "${BLUE}╚══════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "${GREEN}💡 Comandos útiles:${NC}"
    echo -e "   Ver logs:          tail -f $LOG_FILE"
    echo -e "   Estado del server: curl http://localhost:$PEERJS_PORT/health"
    echo -e "   Detener server:    kill $PEERJS_PID"
    echo ""
    
    # Mostrar primeras líneas del log
    echo -e "${BLUE}📋 Últimas líneas del log:${NC}"
    tail -10 "$LOG_FILE"
    
else
    echo -e "${RED}❌ Error: El servidor no pudo iniciar${NC}"
    echo ""
    echo -e "${YELLOW}Últimas líneas del log:${NC}"
    tail -20 "$LOG_FILE"
    rm -f "$PID_FILE"
    exit 1
fi
