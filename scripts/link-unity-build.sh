#!/usr/bin/env bash
set -euo pipefail

# Este script asegura que el build de Unity WebGL sea accesible desde Laravel (public/unity-build)
# Uso: ./scripts/link-unity-build.sh

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
UNITY_BUILD_SRC="$ROOT_DIR/storage/unity-build"
UNITY_BUILD_DEST="$ROOT_DIR/public/unity-build"

# Elimina el symlink o carpeta anterior si existe
if [ -L "$UNITY_BUILD_DEST" ] || [ -d "$UNITY_BUILD_DEST" ]; then
  echo "Eliminando enlace o carpeta anterior: $UNITY_BUILD_DEST"
  rm -rf "$UNITY_BUILD_DEST"
fi

# Crea el symlink
ln -s "$UNITY_BUILD_SRC" "$UNITY_BUILD_DEST"
echo "Enlace simbólico creado: $UNITY_BUILD_DEST -> $UNITY_BUILD_SRC"

echo "¡Listo! Ahora los archivos de Unity WebGL son accesibles desde public/unity-build/"
