#!/bin/bash

###############################################################################
# Script para detener LiveKit + coturn para juiciosoralesuni
###############################################################################

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  Deteniendo LiveKit + coturn${NC}"
echo -e "${BLUE}========================================${NC}"

# Detener LiveKit
if [ -f "$PROJECT_DIR/.livekit.pid" ]; then
    LIVEKIT_PID=$(cat "$PROJECT_DIR/.livekit.pid")
    if ps -p $LIVEKIT_PID > /dev/null 2>&1; then
        echo -e "${YELLOW}Deteniendo LiveKit Server (PID: $LIVEKIT_PID)...${NC}"
        kill $LIVEKIT_PID
        echo -e "${GREEN}✓ LiveKit Server detenido${NC}"
    fi
    rm "$PROJECT_DIR/.livekit.pid"
elif pgrep -f "livekit-server" > /dev/null; then
    echo -e "${YELLOW}Deteniendo LiveKit Server...${NC}"
    pkill -f "livekit-server"
    echo -e "${GREEN}✓ LiveKit Server detenido${NC}"
else
    echo -e "${YELLOW}LiveKit Server no está ejecutándose${NC}"
fi

# Detener coturn
if [ -f "$PROJECT_DIR/.coturn.pid" ]; then
    COTURN_PID=$(cat "$PROJECT_DIR/.coturn.pid")
    if ps -p $COTURN_PID > /dev/null 2>&1; then
        echo -e "${YELLOW}Deteniendo coturn (PID: $COTURN_PID)...${NC}"
        kill $COTURN_PID
        echo -e "${GREEN}✓ coturn detenido${NC}"
    fi
    rm "$PROJECT_DIR/.coturn.pid"
elif pgrep -f "turnserver" > /dev/null; then
    echo -e "${YELLOW}Deteniendo coturn...${NC}"
    pkill -f "turnserver"
    echo -e "${GREEN}✓ coturn detenido${NC}"
else
    echo -e "${YELLOW}coturn no está ejecutándose${NC}"
fi

echo -e "\n${GREEN}Todos los servicios han sido detenidos${NC}"
