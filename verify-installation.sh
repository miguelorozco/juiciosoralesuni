#!/bin/bash

# Script de verificación de instalación - Juicios Orales
# Verifica que todos los componentes necesarios estén listos

echo ""
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║     VERIFICACIÓN DE INSTALACIÓN - JUICIOS ORALES               ║"
echo "║     Photon + PeerJS + Unity WebGL Integration                 ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Contador de verificaciones
PASSED=0
FAILED=0
WARNINGS=0

# Función para verificar archivo
check_file() {
    local file=$1
    local description=$2
    
    if [ -f "$file" ]; then
        echo -e "${GREEN}✓${NC} $description"
        ((PASSED++))
    else
        echo -e "${RED}✗${NC} $description"
        echo "     Ubicación: $file"
        ((FAILED++))
    fi
}

# Función para verificar comando
check_command() {
    local cmd=$1
    local description=$2
    
    if command -v $cmd &> /dev/null; then
        echo -e "${GREEN}✓${NC} $description"
        ((PASSED++))
    else
        echo -e "${RED}✗${NC} $description"
        echo "     Instala con: npm install $cmd"
        ((FAILED++))
    fi
}

# Función para advertencia
warn() {
    local msg=$1
    echo -e "${YELLOW}⚠${NC} $msg"
    ((WARNINGS++))
}

echo ""
echo "📝 ARCHIVOS REQUERIDOS"
echo "═════════════════════════════════════════════════════════════"
echo ""

check_file "peerjs-server-local.js" "Servidor PeerJS local"
check_file "unity-integration/unity-project/Assets/Scripts/DebugUIManager.cs" "Script DebugUIManager"
check_file "unity-integration/unity-project/Assets/Scripts/MicrophonePermissionManager.cs" "Script MicrophonePermissionManager"
check_file "unity-integration/unity-project/Assets/Scripts/PeerJSBridge.cs" "Script PeerJSBridge"
check_file "unity-integration/unity-project/Assets/WebGLTemplates/PlantillaJuicios/index.html" "Template WebGL"
check_file "docs/TODO-LIST-FINAL.md" "Plan de trabajo (TODO-LIST-FINAL)"
check_file "docs/PROGRESO-SESION-ACTUAL.md" "Progreso de sesión actual"

echo ""
echo "🔧 DEPENDENCIAS NODE.JS"
echo "═════════════════════════════════════════════════════════════"
echo ""

# Verificar si package.json existe
if [ -f "package.json" ]; then
    echo -e "${GREEN}✓${NC} package.json encontrado"
    ((PASSED++))
    
    # Verificar si node_modules existe
    if [ -d "node_modules" ]; then
        echo -e "${GREEN}✓${NC} node_modules instalado"
        ((PASSED++))
        
        # Verificar dependencias específicas
        if [ -d "node_modules/express" ]; then
            echo -e "${GREEN}✓${NC} express instalado"
            ((PASSED++))
        else
            warn "express no instalado (necesario)"
            echo "   Ejecuta: npm install express"
        fi
        
        if [ -d "node_modules/peerjs" ]; then
            echo -e "${GREEN}✓${NC} peerjs instalado"
            ((PASSED++))
        else
            warn "peerjs no instalado (necesario)"
            echo "   Ejecuta: npm install peerjs"
        fi
        
        if [ -d "node_modules/cors" ]; then
            echo -e "${GREEN}✓${NC} cors instalado"
            ((PASSED++))
        else
            warn "cors no instalado (necesario)"
            echo "   Ejecuta: npm install cors"
        fi
    else
        warn "node_modules no encontrado"
        echo "   Ejecuta: npm install express peerjs cors"
    fi
else
    warn "package.json no encontrado"
fi

echo ""
echo "🎮 ENTORNO UNITY"
echo "═════════════════════════════════════════════════════════════"
echo ""

# Verificar carpeta de proyecto Unity
if [ -d "unity-integration/unity-project" ]; then
    echo -e "${GREEN}✓${NC} Proyecto Unity detectado"
    ((PASSED++))
    
    # Verificar Asset Store
    if [ -d "unity-integration/unity-project/Assets" ]; then
        echo -e "${GREEN}✓${NC} Carpeta Assets existe"
        ((PASSED++))
    else
        echo -e "${RED}✗${NC} Carpeta Assets no encontrada"
        ((FAILED++))
    fi
    
    # Verificar Photon
    if [ -d "unity-integration/unity-project/Assets/Photon" ]; then
        echo -e "${GREEN}✓${NC} Photon PUN2 detectado"
        ((PASSED++))
    else
        warn "Photon PUN2 no detectado"
        echo "   Importa PUN2 desde Asset Store"
    fi
    
    # Verificar escena main
    if [ -f "unity-integration/unity-project/Assets/Scenes/main.unity" ]; then
        echo -e "${GREEN}✓${NC} Escena main.unity encontrada"
        ((PASSED++))
    else
        warn "Escena main.unity no encontrada"
        echo "   Se debe crear manualmente en Unity"
    fi
else
    echo -e "${RED}✗${NC} Proyecto Unity no encontrado"
    echo "     Ubicación esperada: unity-integration/unity-project"
    ((FAILED++))
fi

echo ""
echo "📡 PUERTOS REQUERIDOS"
echo "═════════════════════════════════════════════════════════════"
echo ""

# Función para verificar si puerto está disponible
check_port() {
    local port=$1
    local service=$2
    
    if lsof -Pi :$port -sTCP:LISTEN -t >/dev/null 2>&1; then
        echo -e "${YELLOW}⚠${NC} Puerto $port en uso ($service)"
        echo "   Servicio actual: $(lsof -i :$port -n -P | tail -1 | awk '{print $1}')"
    else
        echo -e "${GREEN}✓${NC} Puerto $port disponible ($service)"
        ((PASSED++))
    fi
}

check_port 9000 "PeerJS Server"
check_port 8000 "Laravel Server"
check_port 3000 "Backup"

echo ""
echo "🔌 VERIFICACIÓN DE COMANDOS"
echo "═════════════════════════════════════════════════════════════"
echo ""

# Verificar Node.js
if command -v node &> /dev/null; then
    NODE_VERSION=$(node -v)
    echo -e "${GREEN}✓${NC} Node.js instalado ($NODE_VERSION)"
    ((PASSED++))
else
    echo -e "${RED}✗${NC} Node.js no instalado"
    echo "   Descargá desde: https://nodejs.org/"
    ((FAILED++))
fi

# Verificar npm
if command -v npm &> /dev/null; then
    NPM_VERSION=$(npm -v)
    echo -e "${GREEN}✓${NC} npm instalado ($NPM_VERSION)"
    ((PASSED++))
else
    echo -e "${RED}✗${NC} npm no instalado"
    echo "   Se instala con Node.js"
    ((FAILED++))
fi

# Verificar PHP
if command -v php &> /dev/null; then
    PHP_VERSION=$(php -v | head -n1)
    echo -e "${GREEN}✓${NC} PHP instalado"
    ((PASSED++))
else
    warn "PHP no instalado (necesario para Laravel)"
    echo "   Descargá desde: https://www.php.net/"
fi

# Verificar Python
if command -v python &> /dev/null || command -v python3 &> /dev/null; then
    echo -e "${GREEN}✓${NC} Python instalado (para servir WebGL)"
    ((PASSED++))
else
    warn "Python no instalado (necesario para servir WebGL)"
fi

echo ""
echo "📋 RESUMEN"
echo "═════════════════════════════════════════════════════════════"
echo ""
echo -e "  ${GREEN}Pasadas:${NC}    $PASSED"
echo -e "  ${RED}Fallos:${NC}     $FAILED"
echo -e "  ${YELLOW}Advertencias:${NC} $WARNINGS"
echo ""

if [ $FAILED -eq 0 ] && [ $WARNINGS -eq 0 ]; then
    echo -e "${GREEN}✓ TODO LISTO PARA COMENZAR${NC}"
    echo ""
    echo "Próximos pasos:"
    echo "  1. npm install express peerjs cors"
    echo "  2. Abrir Unity Editor"
    echo "  3. Cargar escena: Assets/Scenes/main.unity"
    echo "  4. Ejecutar: node peerjs-server-local.js"
    echo ""
    exit 0
elif [ $FAILED -eq 0 ]; then
    echo -e "${YELLOW}⚠ CONFIGURACIÓN PARCIAL${NC}"
    echo ""
    echo "Hay $WARNINGS advertencia(s) que revisar"
    echo "Lee los mensajes arriba para más detalles"
    echo ""
    exit 1
else
    echo -e "${RED}✗ ERRORES ENCONTRADOS${NC}"
    echo ""
    echo "Se encontraron $FAILED error(es) que resolver"
    echo "Lee los mensajes arriba para instrucciones"
    echo ""
    exit 2
fi
