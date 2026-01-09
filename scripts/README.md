# Scripts auxiliares

Carpeta para comandos de conveniencia en desarrollo.

## Uso rápido

- Arrancar servidor Laravel (debug). Opcional Vite:
  - `WITH_VITE=1 ./scripts/dev-server.sh`
  - Variables: `HOST` (por defecto 127.0.0.1), `PORT` (8000).
- Chequear dependencias:
  - `./scripts/check-deps.sh`

Tras clonar el repo o actualizar scripts, asegúrate de dar permisos:

```bash
chmod +x scripts/*.sh
```

Agrega más scripts aquí según sea necesario (deploy, seeds, backups, etc.).
