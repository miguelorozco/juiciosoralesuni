#!/bin/bash

# Script para desarrollo del Simulador de Juicios Orales
# Uso: ./dev.sh [comando]

case "$1" in
    "install")
        echo "Instalando dependencias..."
        npm install
        composer install
        ;;
    "build")
        echo "Compilando assets..."
        npm run build
        ;;
    "dev")
        echo "Iniciando modo desarrollo..."
        npm run dev &
        php artisan serve --host=0.0.0.0 --port=8000
        ;;
    "clear")
        echo "Limpiando caché..."
        php artisan config:clear
        php artisan cache:clear
        php artisan view:clear
        php artisan route:clear
        ;;
    "migrate")
        echo "Ejecutando migraciones..."
        php artisan migrate
        ;;
    "seed")
        echo "Ejecutando seeders..."
        php artisan db:seed --class=DialogoEjemploSeeder
        ;;
    "setup")
        echo "Configurando proyecto completo..."
        npm install
        composer install
        php artisan migrate
        php artisan db:seed --class=DialogoEjemploSeeder
        npm run build
        php artisan config:clear
        php artisan cache:clear
        php artisan view:clear
        php artisan route:clear
        echo "¡Proyecto configurado correctamente!"
        ;;
    *)
        echo "Comandos disponibles:"
        echo "  install  - Instalar dependencias"
        echo "  build     - Compilar assets"
        echo "  dev       - Modo desarrollo"
        echo "  clear     - Limpiar caché"
        echo "  migrate   - Ejecutar migraciones"
        echo "  seed      - Ejecutar seeders"
        echo "  setup     - Configuración completa"
        ;;
esac
