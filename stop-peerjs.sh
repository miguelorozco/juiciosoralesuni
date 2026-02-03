#!/bin/bash

###############################################################################
# Script para Detener Servidor PeerJS
###############################################################################

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

PID_FILE="/tmp/juiciosorales-peerjs.pid"

echo -e "${BLUE}╔══════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   Detener Servidor PeerJS                           ║${NC}"
echo -e "${BLUE}╚══════════════════════════════════════════════════════╝${NC}"
echo ""

if [ ! -f "$PID_FILE" ]; then
    echo -e "${YELLOW}⚠️  No se encontró el archivo PID${NC}"
    echo -e "   El servidor no parece estar corriendo"
    
    # Buscar procesos de PeerJS por si acaso
    PEERJS_PIDS=$(pgrep -f "peerjs-server-local.js")
    if [ -n "$PEERJS_PIDS" ]; then
        echo ""
        echo -e "${YELLOW}Sin embargo, se encontraron estos procesos PeerJS:${NC}"
        ps -p $PEERJS_PIDS -o pid,etime,cmd
        echo ""
        read -p "¿Deseas detenerlos? (s/N): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[SsYy]$ ]]; then
            kill $PEERJS_PIDS
            echo -e "${GREEN}✓${NC} Procesos detenidos"
        fi
    fi
    exit 0
fi

PID=$(cat "$PID_FILE")

if ! ps -p "$PID" > /dev/null 2>&1; then
    echo -e "${YELLOW}⚠️  El proceso (PID: $PID) no está corriendo${NC}"
    rm -f "$PID_FILE"
    exit 0
fi

echo -e "${BLUE}Deteniendo servidor PeerJS (PID: $PID)...${NC}"
kill "$PID" 2>/dev/null

# Esperar a que se detenga
for i in {1..5}; do
    if ! ps -p "$PID" > /dev/null 2>&1; then
        break
    fi
    sleep 1
done

# Forzar si aún está corriendo
if ps -p "$PID" > /dev/null 2>&1; then
    echo -e "${YELLOW}⚠️  Forzando detención...${NC}"
    kill -9 "$PID" 2>/dev/null
    sleep 1
fi

if ! ps -p "$PID" > /dev/null 2>&1; then
    echo -e "${GREEN}✅ Servidor PeerJS detenido correctamente${NC}"
    rm -f "$PID_FILE"
else
    echo -e "${RED}❌ Error: No se pudo detener el servidor${NC}"
    exit 1
fi
