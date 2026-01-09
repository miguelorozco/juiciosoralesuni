# Scripts auxiliares

Carpeta para comandos de conveniencia en desarrollo.

## Uso rápido

### Linux/macOS (Bash)

- Arrancar servidor Laravel (debug). Opcional Vite:
  - `WITH_VITE=1 ./scripts/dev-server.sh`
  - Variables: `HOST` (por defecto 127.0.0.1), `PORT` (8000).
- Chequear dependencias:
  - `./scripts/check-deps.sh`

Tras clonar el repo o actualizar scripts, asegúrate de dar permisos:

```bash
chmod +x scripts/*.sh
```

### Windows (PowerShell)

- Arrancar servidor Laravel (debug). Opcional Vite:
  - `$env:WITH_VITE=1; .\scripts\dev-server.ps1`
  - Variables: `$env:HOST` (por defecto 127.0.0.1), `$env:PORT` (8000).
- Chequear dependencias:
  - `.\scripts\check-deps.ps1`

**Nota:** Si PowerShell muestra un error de política de ejecución, ejecuta:
```powershell
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
```

Agrega más scripts aquí según sea necesario (deploy, seeds, backups, etc.).
