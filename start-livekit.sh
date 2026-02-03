#!/bin/bash

###############################################################################
# Script para iniciar LiveKit + coturn para juiciosoralesuni
# Reemplaza a PeerJS del proyecto original
###############################################################################

set -e

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LIVEKIT_PORT=7880
COTURN_PORT=3478

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  Iniciando LiveKit + coturn${NC}"
echo -e "${BLUE}========================================${NC}"

# Verificar si LiveKit está instalado
if ! command -v livekit-server &> /dev/null; then
    echo -e "${YELLOW}⚠️  LiveKit Server no está instalado${NC}"
    echo -e "${YELLOW}Para instalarlo:${NC}"
    echo -e "  ${GREEN}# En Linux:${NC}"
    echo -e "  curl -sSL https://get.livekit.io | bash"
    echo -e ""
    echo -e "  ${GREEN}# O usando Docker:${NC}"
    echo -e "  docker pull livekit/livekit-server:latest"
    echo ""
    read -p "¿Deseas continuar sin LiveKit? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
    LIVEKIT_INSTALLED=false
else
    LIVEKIT_INSTALLED=true
    echo -e "${GREEN}✓ LiveKit Server encontrado${NC}"
fi

# Verificar si coturn está instalado
if ! command -v turnserver &> /dev/null; then
    echo -e "${YELLOW}⚠️  coturn no está instalado${NC}"
    echo -e "${YELLOW}Para instalarlo:${NC}"
    echo -e "  ${GREEN}sudo apt-get install coturn${NC}  # En Ubuntu/Debian"
    echo -e "  ${GREEN}sudo yum install coturn${NC}      # En CentOS/RHEL"
    echo ""
    COTURN_INSTALLED=false
else
    COTURN_INSTALLED=true
    echo -e "${GREEN}✓ coturn encontrado${NC}"
fi

# Detener procesos existentes
echo -e "\n${BLUE}Deteniendo procesos existentes...${NC}"

# Detener LiveKit
if pgrep -f "livekit-server" > /dev/null; then
    echo -e "${YELLOW}Deteniendo LiveKit Server existente...${NC}"
    pkill -f "livekit-server" || true
    sleep 2
fi

# Detener coturn
if pgrep -f "turnserver" > /dev/null; then
    echo -e "${YELLOW}Deteniendo coturn existente...${NC}"
    pkill -f "turnserver" || true
    sleep 2
fi

# Crear archivo de configuración de LiveKit si no existe
LIVEKIT_CONFIG="$PROJECT_DIR/livekit.yaml"
if [ ! -f "$LIVEKIT_CONFIG" ]; then
    echo -e "${YELLOW}Creando configuración de LiveKit...${NC}"
    cat > "$LIVEKIT_CONFIG" << 'EOF'
port: 7880
bind_addresses:
  - 0.0.0.0

rtc:
  port_range_start: 50000
  port_range_end: 60000
  use_external_ip: false

keys:
  devkey: secret

room:
  auto_create: true
  empty_timeout: 600
  max_participants: 50

logging:
  level: info
EOF
fi

# Iniciar LiveKit si está instalado
if [ "$LIVEKIT_INSTALLED" = true ]; then
    echo -e "\n${BLUE}Iniciando LiveKit Server...${NC}"
    nohup livekit-server --config "$LIVEKIT_CONFIG" > "$PROJECT_DIR/livekit.log" 2>&1 &
    LIVEKIT_PID=$!
    echo -e "${GREEN}✓ LiveKit Server iniciado (PID: $LIVEKIT_PID)${NC}"
    echo -e "  ${GREEN}URL: ws://localhost:$LIVEKIT_PORT${NC}"
    echo -e "  ${GREEN}Log: $PROJECT_DIR/livekit.log${NC}"
    
    # Guardar PID
    echo $LIVEKIT_PID > "$PROJECT_DIR/.livekit.pid"
fi

# Iniciar coturn si está instalado
if [ "$COTURN_INSTALLED" = true ]; then
    echo -e "\n${BLUE}Iniciando coturn...${NC}"
    
    # Crear directorio de logs si no existe
    sudo mkdir -p /var/log/coturn
    sudo chmod 755 /var/log/coturn
    
    nohup turnserver -c "$PROJECT_DIR/coturn.conf" > "$PROJECT_DIR/coturn.log" 2>&1 &
    COTURN_PID=$!
    echo -e "${GREEN}✓ coturn iniciado (PID: $COTURN_PID)${NC}"
    echo -e "  ${GREEN}Puerto STUN/TURN: $COTURN_PORT${NC}"
    echo -e "  ${GREEN}Log: $PROJECT_DIR/coturn.log${NC}"
    
    # Guardar PID
    echo $COTURN_PID > "$PROJECT_DIR/.coturn.pid"
fi

# Esperar a que los servicios estén listos
echo -e "\n${BLUE}Esperando a que los servicios estén listos...${NC}"
sleep 3

# Verificar estado
echo -e "\n${BLUE}========================================${NC}"
echo -e "${BLUE}  Estado de los Servicios${NC}"
echo -e "${BLUE}========================================${NC}"

if [ "$LIVEKIT_INSTALLED" = true ] && pgrep -f "livekit-server" > /dev/null; then
    echo -e "${GREEN}✓ LiveKit Server: ACTIVO${NC}"
else
    echo -e "${RED}✗ LiveKit Server: INACTIVO${NC}"
fi

if [ "$COTURN_INSTALLED" = true ] && pgrep -f "turnserver" > /dev/null; then
    echo -e "${GREEN}✓ coturn: ACTIVO${NC}"
else
    echo -e "${RED}✗ coturn: INACTIVO${NC}"
fi

echo -e "\n${BLUE}========================================${NC}"
echo -e "${GREEN}Servicios iniciados correctamente${NC}"
echo -e "${BLUE}========================================${NC}"
echo -e "${YELLOW}Para detener los servicios, ejecuta:${NC}"
echo -e "  ${GREEN}./stop-livekit.sh${NC}"
echo ""
