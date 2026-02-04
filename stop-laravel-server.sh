#!/bin/bash

# Script para detener el servidor Laravel
cd "$(dirname "$0")"

if [ -f storage/laravel-server.pid ]; then
    PID=$(cat storage/laravel-server.pid)
    if ps -p $PID > /dev/null; then
        echo "Deteniendo servidor Laravel (PID: $PID)..."
        kill $PID
        rm storage/laravel-server.pid
        echo "Servidor detenido"
    else
        echo "El proceso no está corriendo"
        rm storage/laravel-server.pid
    fi
else
    echo "No se encontró el archivo PID"
    echo "Buscando procesos en el puerto 8000..."
    lsof -ti:8000 | xargs kill -9 2>/dev/null && echo "Procesos detenidos" || echo "No hay procesos en el puerto 8000"
fi
