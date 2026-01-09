#!/usr/bin/env bash
set -euo pipefail

# Verifica dependencias básicas para desarrollar/levantar el proyecto.
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

MISSING=0
echo "=== Binarios requeridos ==="
for bin in php composer npm node git; do
  if command -v "$bin" >/dev/null 2>&1; then
    printf "✔ %-8s %s\n" "$bin" "$($bin --version | head -n 1)"
  else
    printf "✘ %-8s (no encontrado)\n" "$bin"
    MISSING=1
  fi
done

if [ "$MISSING" -ne 0 ]; then
  echo "Instala los binarios faltantes antes de continuar." >&2
fi

echo
echo "=== Estructura de dependencias ==="
if [ -d "vendor" ]; then
  echo "✔ vendor/ presente"
else
  echo "✘ Falta vendor/ (ejecuta: composer install)"
fi

if [ -d "node_modules" ]; then
  echo "✔ node_modules/ presente"
else
  echo "✘ Falta node_modules/ (ejecuta: npm install)"
fi

echo
echo "=== Validaciones rápidas ==="
if command -v composer >/dev/null 2>&1; then
  composer validate --no-check-lock
  composer check-platform-reqs --no-dev
fi

if command -v php >/dev/null 2>&1; then
  php artisan --version
fi

echo
echo "Listo. Si ves advertencias arriba, corrige antes de continuar."
