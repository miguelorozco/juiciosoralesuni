# ⚖️ Simulador de Juicios Orales UNI

Sistema completo para la simulación de juicios orales con diálogos ramificados, integración Unity 3D, comunicación en tiempo real vía LiveKit (SFU) y evaluación de estudiantes.

**Producción:** https://simuladorjuicios.udec.edu.mx

---

## 🧱 Stack Tecnológico

- **Backend:** Laravel 12.x / PHP 8.2+
- **Frontend:** TailwindCSS + Alpine.js + Vite
- **Autenticación:** JWT
- **Base de datos:** MySQL/MariaDB
- **WebRTC / Tiempo real:** LiveKit Server (SFU)
- **TURN/STUN:** coturn
- **Web server:** Apache 2.4 + SSL Let's Encrypt
- **Build Unity:** Unity 3D (integración vía API)

---

## 🚀 Instalación en Producción

### 1. Clonar el repositorio

```bash
cd /var/www
sudo git clone https://github.com/miguelorozco/juiciosoralesuni.git
cd juiciosoralesuni
```

### 2. Instalar dependencias PHP

```bash
sudo composer install --no-dev --optimize-autoloader
```

### 3. Instalar dependencias Node y compilar assets

```bash
sudo npm install --legacy-peer-deps
sudo npm run build
```

> **Nota:** Se requiere `--legacy-peer-deps` por conflicto entre `laravel-vite-plugin@1.3.0` y Vite 7.x.

### 4. Configurar el archivo .env

```bash
cp .env.example .env
```

Valores clave para producción:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://simuladorjuicios.udec.edu.mx
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=juiciosorales
DB_USERNAME=juiciosorales_user
DB_PASSWORD=tu_password

LIVEKIT_API_KEY=tu_api_key
LIVEKIT_API_SECRET=tu_api_secret
LIVEKIT_HOST=wss://simuladorjuicios.udec.edu.mx:7880
LIVEKIT_HTTP_URL=http://localhost:7880

COTURN_HOST=simuladorjuicios.udec.edu.mx
COTURN_PORT=3478
COTURN_USERNAME=usuario_turn
COTURN_PASSWORD=password_turn
COTURN_REALM=juiciosoralesuni
```

> `LIVEKIT_HTTP_URL` debe quedarse en `localhost` — Laravel lo usa internamente para llamadas server-to-server. Solo `LIVEKIT_HOST` necesita el dominio público (lo usan los navegadores).

### 5. Permisos de directorios

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 6. Generar caches de producción

```bash
php artisan config:cache
php artisan route:cache
php artisan view:clear
```

### 7. Copiar livekit.yaml

```bash
sudo cp livekit.yaml.example livekit.yaml
# Editar con las credenciales correctas
```

Estructura del `livekit.yaml`:

```yaml
port: 7880
bind_addresses:
  - 0.0.0.0

rtc:
  port_range_start: 50000
  port_range_end: 60000
  use_external_ip: false

keys:
  tu_api_key: tu_api_secret

room:
  auto_create: true
  empty_timeout: 600
  max_participants: 50

logging:
  level: info
```

> Las credenciales `keys` en `livekit.yaml` deben coincidir exactamente con `LIVEKIT_API_KEY` y `LIVEKIT_API_SECRET` en `.env`.

### 8. Iniciar LiveKit

```bash
livekit-server --config /var/www/juiciosoralesuni/livekit.yaml &
```

---

## 🎮 Build de Unity

El directorio `public/unity-build/` contiene los archivos del build de Unity **copiados directamente** (no es un symlink).

> **Importante:** Apache no permite symlinks que apunten fuera del `DocumentRoot`. El symlink original apuntaba a una ruta local de desarrollo (`/Users/miguel/...`) que no existe en el servidor. Por eso se usa una copia directa.

### Actualizar el build de Unity en producción

Cada vez que generes un nuevo build de Unity, cópialo manualmente al servidor:

```bash
sudo cp -r /ruta/local/unity-build/* /var/www/juiciosoralesuni/public/unity-build/
sudo chown -R www-data:www-data /var/www/juiciosoralesuni/public/unity-build
```

---

## 🌐 Configuración Apache

El servidor usa dos VirtualHosts en `/etc/apache2/sites-enabled/`:

- `juiciosoralesuni.conf` — HTTP (puerto 80) → redirige a HTTPS
- `juiciosoralesuni-le-ssl.conf` — HTTPS (puerto 443) con Let's Encrypt

Ambos apuntan a `DocumentRoot /var/www/juiciosoralesuni/public` con `AllowOverride All` y `Options -Indexes +FollowSymLinks`.

---

## 🛠️ Desarrollo Local

### Modo desarrollo

```bash
# Terminal 1: Laravel
php artisan serve --host=0.0.0.0 --port=8000

# Terminal 2: Vite (hot reload)
npm run dev
```

### Compilar para producción

```bash
npm run build
```

---

## 📁 Estructura del Proyecto

```
juiciosoralesuni/
├── app/
│   ├── Http/Controllers/     # Controladores API y Web
│   ├── Models/               # Modelos Eloquent
│   └── Http/Middleware/      # Middleware personalizado
├── database/
│   ├── migrations/           # Migraciones de BD
│   └── seeders/              # Seeders de datos
├── resources/
│   ├── views/                # Vistas Blade
│   ├── css/                  # Estilos TailwindCSS
│   └── js/                   # JavaScript/Alpine.js
├── routes/
│   ├── api.php               # Rutas API
│   └── web.php               # Rutas Web
├── public/
│   ├── build/                # Assets compilados por Vite
│   └── unity-build/          # Build Unity (copia directa, NO symlink)
├── storage/
│   └── unity-build/          # Fuente del build Unity en el servidor
├── livekit.yaml              # Configuración LiveKit Server
└── .env                      # Variables de entorno
```

---

## 🎯 Funcionalidades

- **Autenticación JWT** completa
- **Dashboard interactivo** con estadísticas
- **Gestión de sesiones** con filtros avanzados
- **Editor de diálogos visual** con drag & drop
- **Vista de sesión activa** en tiempo real
- **Sistema de evaluación** con puntuaciones
- **Integración Unity 3D**
- **Comunicación WebRTC** vía LiveKit SFU
- **API documentada** con Swagger en `/api/documentation`

---

## 🌐 URLs Importantes

| Ruta | Descripción |
|------|-------------|
| `/login` | Inicio de sesión |
| `/dashboard` | Panel principal |
| `/sesiones` | Gestión de sesiones |
| `/dialogos` | Editor de diálogos |
| `/api/documentation` | Swagger API Docs |

---

## 📡 API LiveKit — Endpoints

### Obtener token de sala

```http
POST /api/livekit/token
Authorization: Bearer {JWT_TOKEN}

{
  "room_name": "juicio-room-123",
  "participant_name": "Juan Pérez",
  "participant_identity": "user_123"
}
```

### Endpoints Unity

```
GET  /api/unity/sesion/{id}/dialogo-estado
GET  /api/unity/sesion/{id}/respuestas-usuario/{user}
POST /api/unity/sesion/{id}/enviar-decision
POST /api/unity/sesion/{id}/notificar-hablando
GET  /api/unity/sesion/{id}/movimientos-personajes
```

---

## 🚨 Solución de Problemas

### Error 500 al abrir el sitio
```bash
# Ver log de errores
tail -f storage/logs/laravel.log

# Permisos
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Vite manifest not found
```bash
sudo npm install --legacy-peer-deps
sudo npm run build
```

### Error: Permission denied en storage
```bash
sudo chown -R www-data:www-data /var/www/juiciosoralesuni/storage
sudo chmod -R 775 /var/www/juiciosoralesuni/storage
```

### LiveKit no inicia
```bash
ps aux | grep livekit-server
sudo netstat -tlnp | grep 7880
tail -f livekit.log
```

### coturn no responde
```bash
sudo netstat -tlnp | grep 3478
sudo tail -f /var/log/coturn/coturn.log
```

### Credenciales LiveKit no coinciden
Verificar que `LIVEKIT_API_KEY` / `LIVEKIT_API_SECRET` en `.env` sean idénticos a las `keys` en `livekit.yaml`.

---

## 📚 Referencias

- [LiveKit Docs](https://docs.livekit.io/)
- [coturn Wiki](https://github.com/coturn/coturn/wiki)
- [LiveKit Unity SDK](https://github.com/livekit/client-sdk-unity)
- [Laravel Docs](https://laravel.com/docs)
