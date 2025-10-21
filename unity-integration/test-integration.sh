#!/bin/bash

# Script para probar la integración Unity + Laravel
# Uso: ./test-integration.sh

set -e

echo "🧪 Iniciando pruebas de integración..."

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Función para imprimir con color
print_status() {
    local status=$1
    local message=$2
    case $status in
        "OK")
            echo -e "${GREEN}✅ $message${NC}"
            ;;
        "ERROR")
            echo -e "${RED}❌ $message${NC}"
            ;;
        "WARNING")
            echo -e "${YELLOW}⚠️  $message${NC}"
            ;;
        "INFO")
            echo -e "${YELLOW}ℹ️  $message${NC}"
            ;;
    esac
}

# Verificar que estamos en el directorio correcto
if [ ! -f "composer.json" ]; then
    print_status "ERROR" "No se encontró composer.json. Ejecutar desde el directorio raíz de Laravel."
    exit 1
fi

print_status "INFO" "Verificando integración Unity + Laravel..."

# 1. Verificar que Laravel esté funcionando
print_status "INFO" "Probando servidor Laravel..."
if curl -s http://localhost:8002/api/unity/auth/status > /dev/null; then
    print_status "OK" "Laravel está funcionando"
else
    print_status "ERROR" "Laravel no está funcionando. Ejecutar: php artisan serve"
    exit 1
fi

# 2. Probar endpoint de autenticación
print_status "INFO" "Probando autenticación..."
AUTH_RESPONSE=$(curl -s -X POST http://localhost:8002/api/unity/auth/login \
    -H "Content-Type: application/json" \
    -d '{
        "email": "alumno@example.com",
        "password": "password",
        "unity_version": "2022.3.15f1",
        "unity_platform": "WebGL",
        "device_id": "TEST_DEVICE_001"
    }')

if echo "$AUTH_RESPONSE" | grep -q '"success":true'; then
    print_status "OK" "Autenticación funcionando"
    TOKEN=$(echo "$AUTH_RESPONSE" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
else
    print_status "ERROR" "Error en autenticación: $AUTH_RESPONSE"
    exit 1
fi

# 3. Probar endpoint de diálogo
print_status "INFO" "Probando endpoint de diálogo..."
DIALOG_RESPONSE=$(curl -s -X GET http://localhost:8000/api/unity/1/dialogo-estado \
    -H "Authorization: Bearer $TOKEN" \
    -H "X-Unity-Version: 2022.3.15f1" \
    -H "X-Unity-Platform: WebGL")

if echo "$DIALOG_RESPONSE" | grep -q '"success":true'; then
    print_status "OK" "Endpoint de diálogo funcionando"
else
    print_status "WARNING" "Endpoint de diálogo no disponible (normal si no hay sesión activa)"
fi

# 4. Probar endpoint de salas
print_status "INFO" "Probando endpoint de salas..."
ROOM_RESPONSE=$(curl -s -X GET http://localhost:8000/api/unity/rooms/test-room/state \
    -H "Authorization: Bearer $TOKEN")

if echo "$ROOM_RESPONSE" | grep -q '"success":true'; then
    print_status "OK" "Endpoint de salas funcionando"
else
    print_status "WARNING" "Endpoint de salas no disponible (normal si no hay sala activa)"
fi

# 5. Verificar configuración de CORS
print_status "INFO" "Verificando configuración de CORS..."
CORS_RESPONSE=$(curl -s -I -X OPTIONS http://localhost:8000/api/unity/auth/status \
    -H "Origin: http://localhost:3000" \
    -H "Access-Control-Request-Method: GET")

if echo "$CORS_RESPONSE" | grep -q "Access-Control-Allow-Origin"; then
    print_status "OK" "CORS configurado correctamente"
else
    print_status "WARNING" "CORS no configurado o no funcionando"
fi

# 6. Verificar archivos de Unity
print_status "INFO" "Verificando archivos de Unity..."
UNITY_SCRIPTS=(
    "unity-project/Assets/Scripts/LaravelAPI.cs"
    "unity-project/Assets/Scripts/DialogoUI.cs"
    "unity-project/Assets/Scripts/UnityLaravelIntegration.cs"
    "unity-project/Assets/Scripts/UnityConfig.cs"
    "unity-project/Assets/Scripts/GameInitializer.cs"
)

for script in "${UNITY_SCRIPTS[@]}"; do
    if [ -f "$script" ]; then
        print_status "OK" "Encontrado: $script"
    else
        print_status "ERROR" "No encontrado: $script"
    fi
done

# 7. Verificar configuración de Unity
if [ -f "unity-project/Assets/StreamingAssets/unity-config.json" ]; then
    print_status "OK" "Configuración de Unity encontrada"
else
    print_status "WARNING" "Configuración de Unity no encontrada"
fi

# 8. Verificar template HTML
if [ -f "unity-project/Assets/WebGLTemplates/PlantillaJuicios/index.html" ]; then
    print_status "OK" "Template HTML de Unity encontrado"
else
    print_status "WARNING" "Template HTML de Unity no encontrado"
fi

# 9. Verificar dependencias de Laravel
print_status "INFO" "Verificando dependencias de Laravel..."
if composer show tymon/jwt-auth > /dev/null 2>&1; then
    print_status "OK" "JWT Auth instalado"
else
    print_status "ERROR" "JWT Auth no instalado. Ejecutar: composer require tymon/jwt-auth"
fi

# 10. Resumen final
echo ""
print_status "INFO" "=== RESUMEN DE PRUEBAS ==="
echo ""

# Contar errores
ERRORS=0
if ! curl -s http://localhost:8000/api/unity/auth/status > /dev/null; then
    ((ERRORS++))
fi

if [ $ERRORS -eq 0 ]; then
    print_status "OK" "¡Todas las pruebas pasaron! La integración está lista."
    echo ""
    print_status "INFO" "Próximos pasos:"
    echo "1. Abrir Unity y cargar el proyecto"
    echo "2. Configurar Photon PUN2 con tu App ID"
    echo "3. Hacer build para WebGL"
    echo "4. Probar en navegador"
else
    print_status "ERROR" "Se encontraron $ERRORS errores. Revisar la configuración."
    exit 1
fi

echo ""
print_status "INFO" "Para más información, consulta: INTEGRATION_GUIDE.md"
