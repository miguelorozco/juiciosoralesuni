# 🚀 Quick Start - juiciosoralesuni

Guía rápida para poner en marcha el proyecto **juiciosoralesuni** (LiveKit Edition).

## ⚡ Instalación Rápida

### 1. Instalar LiveKit Server

```bash
curl -sSL https://get.livekit.io | bash
```

### 2. Instalar coturn

```bash
sudo apt-get update
sudo apt-get install coturn
```

### 3. Configurar el Proyecto

```bash
cd ~/Documents/github/juiciosoralesuni

# Instalar dependencias
composer install
npm install

# Configurar entorno
cp .env.example .env
php artisan key:generate

# Crear base de datos
touch database/database.sqlite
php artisan migrate
php artisan db:seed
```

### 4. Configurar Variables de Entorno

Edita `.env` y asegúrate de tener:

```env
LIVEKIT_API_KEY=devkey
LIVEKIT_API_SECRET=secret
LIVEKIT_HOST=ws://localhost:7880
LIVEKIT_HTTP_URL=http://localhost:7880

COTURN_HOST=localhost
COTURN_PORT=3478
COTURN_USERNAME=usuario_turn
COTURN_PASSWORD=password_turn
COTURN_REALM=juiciosoralesuni
```

## 🎯 Uso Diario

### Iniciar Todo

```bash
# Terminal 1: Iniciar LiveKit + coturn
./start-livekit.sh

# Terminal 2: Iniciar Laravel
php artisan serve

# Terminal 3 (opcional): Iniciar Vite para desarrollo
npm run dev
```

### Cambiar Apache a este Proyecto

```bash
# Desde ~/Documents/github
./switch-project.sh juiciosoralesuni
```

### Detener Servicios

```bash
./stop-livekit.sh
```

## 🔄 Cambiar entre Proyectos

```bash
# Cambiar a proyecto LiveKit
~/Documents/github/switch-project.sh juiciosoralesuni

# Cambiar a proyecto PeerJS original
~/Documents/github/switch-project.sh juiciosorales
```

## 📋 Verificación

### Verificar LiveKit

```bash
curl http://localhost:7880
```

### Verificar coturn

```bash
sudo netstat -tlnp | grep 3478
```

### Verificar Laravel

```bash
curl http://localhost:8000/api/health
```

### Verificar Rutas LiveKit

```bash
php artisan route:list | grep livekit
```

## 🎮 Probar con Unity

1. Compilar Unity WebGL (desde el proyecto original)
2. Conectar al servidor Laravel
3. Solicitar token de LiveKit
4. Conectar a sala usando el token

## 📊 Estado Actual

**✅ Completado:**
- Clon del proyecto original
- Configuración de LiveKit en Laravel
- Configuración de coturn
- Controlador y rutas API para LiveKit
- Scripts de inicio/detención
- Script de switch entre proyectos
- Documentación completa

**🚧 Pendiente (Unity):**
- Importar LiveKit Unity SDK
- Crear `LiveKitManager.cs`
- Modificar scripts de red para usar LiveKit
- Probar conexiones multiplayer

## 🐛 Problemas Comunes

### "livekit-server: command not found"

```bash
# Reinstalar LiveKit
curl -sSL https://get.livekit.io | bash
```

### "turnserver: command not found"

```bash
sudo apt-get install coturn
```

### Error al generar token

Verifica que las credenciales coincidan:
- `.env`: `LIVEKIT_API_KEY=devkey` y `LIVEKIT_API_SECRET=secret`
- `livekit.yaml`: bajo `keys:` debe tener `devkey: secret`

### Apache no cambia de proyecto

```bash
# Verificar configuraciones
ls -la /etc/apache2/sites-enabled/

# Reiniciar Apache manualmente
sudo systemctl restart apache2
```

## 📚 Documentación Completa

- `README_LIVEKIT.md` - Documentación completa del proyecto
- `MIGRATION_GUIDE.md` - Guía de migración desde PeerJS
- `config/livekit.php` - Configuración de LiveKit
- `coturn.conf` - Configuración de coturn

## 🆘 Ayuda

Si tienes problemas:

1. Revisa los logs:
   ```bash
   tail -f livekit.log
   tail -f coturn.log
   tail -f storage/logs/laravel.log
   ```

2. Verifica que todos los servicios estén corriendo:
   ```bash
   pgrep -f livekit-server
   pgrep -f turnserver
   curl http://localhost:8000/api/health
   ```

3. Consulta la documentación oficial:
   - [LiveKit Docs](https://docs.livekit.io/)
   - [Laravel Docs](https://laravel.com/docs)

---

**Siguiente paso**: Integrar LiveKit Unity SDK y modificar los scripts de Unity.
