#!/bin/bash

# Script para mantener el servidor Laravel corriendo en background
cd "$(dirname "$0")"

# Verificar si ya hay un servidor corriendo
if lsof -Pi :8000 -sTCP:LISTEN -t >/dev/null ; then
    echo "Ya hay un servidor corriendo en el puerto 8000"
    exit 0
fi

# Limpiar caché
/opt/homebrew/opt/php@8.3/bin/php artisan config:clear
/opt/homebrew/opt/php@8.3/bin/php artisan cache:clear

# Iniciar el servidor en background con PHP 8.3
nohup /opt/homebrew/opt/php@8.3/bin/php artisan serve --host=127.0.0.1 --port=8000 > storage/logs/laravel-server.log 2>&1 &

echo "Servidor Laravel iniciado en http://127.0.0.1:8000"
echo "Logs en: storage/logs/laravel-server.log"
echo "PID: $!"

# Guardar el PID para poder detenerlo después
echo $! > storage/laravel-server.pid
