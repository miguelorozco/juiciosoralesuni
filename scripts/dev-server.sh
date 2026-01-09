#!/usr/bin/env bash
set -euo pipefail

# Arranca el servidor Laravel para debug. Opcionalmente puede lanzar Vite
# (npm run dev) si se establece WITH_VITE=1.
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
HOST="${HOST:-127.0.0.1}"
PORT="${PORT:-8000}"
WITH_VITE="${WITH_VITE:-0}"

cd "$ROOT_DIR"

for bin in php composer; do
  if ! command -v "$bin" >/dev/null 2>&1; then
    echo "Falta el binario requerido: $bin" >&2
    exit 1
  fi
done

if [ ! -f ".env" ]; then
  echo "Falta .env. Copia .env.example y genera APP_KEY antes de continuar." >&2
  exit 1
fi

if [ ! -d "vendor" ]; then
  echo "Instalando dependencias PHP (composer install)..."
  composer install
fi

NPM_PID=""
if [ "$WITH_VITE" != "0" ]; then
  if ! command -v npm >/dev/null 2>&1; then
    echo "WITH_VITE=1 requiere npm instalado." >&2
    exit 1
  fi
  if [ ! -d "node_modules" ]; then
    echo "Instalando dependencias JS (npm install)..."
    npm install
  fi
  echo "Arrancando Vite (npm run dev) en segundo plano..."
  npm run dev -- --host --clearScreen=false &
  NPM_PID=$!
fi

cleanup() {
  if [ -n "${NPM_PID}" ]; then
    kill "$NPM_PID" 2>/dev/null || true
  fi
}
trap cleanup EXIT INT TERM

echo "Arrancando servidor Laravel en http://${HOST}:${PORT}"
php artisan serve --host="$HOST" --port="$PORT"
