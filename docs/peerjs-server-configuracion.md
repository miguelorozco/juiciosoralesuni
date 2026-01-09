# Configuración del Servidor de Audio PeerJS

## 📋 Resumen Ejecutivo

Tu aplicación está usando **PeerJS** para la comunicación de audio en tiempo real entre jugadores en Unity WebGL. Actualmente tienes configurados **3 servidores** con sistema de respaldo automático:

1. **`juiciosorales.site`** (tu servidor propio) - Puerto 443, HTTPS, Path: `/peerjs`
2. **`peerjs.com`** (servidor público) - Puerto 443, HTTPS
3. **`0.peerjs.com`** (servidor público alternativo) - Puerto 443, HTTPS

## 🔍 Estado Actual

### ¿Tienes PeerJS Server instalado?

**Respuesta: Probablemente NO**

Razones:
- No hay evidencia de instalación de `peerjs-server` en tu código
- No hay configuración de Docker para PeerJS Server
- No hay scripts de instalación o configuración de Node.js para PeerJS
- El Dockerfile solo contiene PHP/Laravel, no Node.js

### ¿Qué significa esto?

Actualmente, tu aplicación está usando **principalmente los servidores públicos de PeerJS** (`peerjs.com` y `0.peerjs.com`), ya que tu servidor `juiciosorales.site` probablemente no tiene PeerJS Server instalado y las conexiones fallan, haciendo que el sistema automáticamente use los servidores de respaldo.

## ⚖️ Opciones Disponibles

### Opción 1: Usar Servidores Públicos (Recomendado para Desarrollo)

**Ventajas:**
- ✅ No requiere instalación ni mantenimiento
- ✅ Gratis para uso básico
- ✅ Ya está funcionando en tu código
- ✅ Sin configuración adicional necesaria

**Desventajas:**
- ❌ Límites de conexiones concurrentes (típicamente 50-100 usuarios)
- ❌ Sin control sobre la infraestructura
- ❌ Posibles limitaciones de ancho de banda
- ❌ Dependencia de servicios externos
- ❌ No garantizado para producción a gran escala

**Estado:** ✅ **Ya está configurado y funcionando**

### Opción 2: Instalar PeerJS Server Propio (Recomendado para Producción)

**Ventajas:**
- ✅ Control total sobre la infraestructura
- ✅ Sin límites de usuarios (depende de tu servidor)
- ✅ Mejor rendimiento y latencia
- ✅ Mayor seguridad y privacidad
- ✅ Escalable según tus necesidades

**Desventajas:**
- ❌ Requiere instalación y configuración
- ❌ Necesitas Node.js en tu servidor
- ❌ Mantenimiento y actualizaciones
- ❌ Consumo de recursos del servidor
- ❌ Configuración de SSL/HTTPS

## 🚀 Instalación de PeerJS Server (Si decides hacerlo)

### Requisitos Previos

- Node.js 14+ instalado en el servidor
- NPM o Yarn
- Acceso SSH al servidor `juiciosorales.site`
- Certificado SSL configurado (ya lo tienes)

### Pasos de Instalación

#### 1. Instalar PeerJS Server

```bash
# Opción A: Instalación global
npm install -g peerjs

# Opción B: Instalación local en un directorio específico
mkdir /opt/peerjs-server
cd /opt/peerjs-server
npm init -y
npm install peerjs
```

#### 2. Configurar PeerJS Server

Crear archivo de configuración `/opt/peerjs-server/config.json`:

```json
{
  "port": 9000,
  "path": "/peerjs",
  "allow_discovery": true,
  "proxied": true,
  "key": "peerjs",
  "expire_timeout": 5000,
  "alive_timeout": 60000,
  "concurrent_limit": 5000,
  "ssl": {
    "key": "/etc/ssl/private/your-key.pem",
    "cert": "/etc/ssl/certs/your-cert.pem"
  }
}
```

#### 3. Configurar como Servicio (systemd)

Crear `/etc/systemd/system/peerjs.service`:

```ini
[Unit]
Description=PeerJS Server
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/opt/peerjs-server
ExecStart=/usr/bin/node /opt/peerjs-server/node_modules/peerjs/bin/peerjs --port 9000 --path /peerjs --proxied
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

#### 4. Configurar Nginx como Proxy Reverso

Agregar a tu configuración de Nginx (`/etc/nginx/sites-available/juiciosorales.site`):

```nginx
# Proxy para PeerJS Server
location /peerjs {
    proxy_pass http://localhost:9000;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_read_timeout 86400;
}
```

#### 5. Iniciar el Servicio

```bash
# Recargar systemd
sudo systemctl daemon-reload

# Habilitar servicio
sudo systemctl enable peerjs

# Iniciar servicio
sudo systemctl start peerjs

# Verificar estado
sudo systemctl status peerjs
```

#### 6. Verificar Instalación

```bash
# Probar conexión
curl https://juiciosorales.site/peerjs

# Ver logs
sudo journalctl -u peerjs -f
```

### Instalación con Docker (Alternativa)

Si prefieres usar Docker, crear `docker-compose-peerjs.yml`:

```yaml
version: '3.8'

services:
  peerjs:
    image: peerjs/peerjs-server:latest
    container_name: peerjs-server
    ports:
      - "9000:9000"
    environment:
      - PORT=9000
      - PATH=/peerjs
      - PROXIED=true
    restart: unless-stopped
    networks:
      - web

networks:
  web:
    external: true
```

Luego ejecutar:
```bash
docker-compose -f docker-compose-peerjs.yml up -d
```

## 📊 Comparación de Opciones

| Característica | Servidores Públicos | Servidor Propio |
|----------------|---------------------|-----------------|
| **Costo** | Gratis | Gratis (solo hosting) |
| **Instalación** | ✅ Ya configurado | ⚠️ Requiere instalación |
| **Mantenimiento** | ✅ Sin mantenimiento | ⚠️ Requiere mantenimiento |
| **Escalabilidad** | ⚠️ Limitada (50-100 usuarios) | ✅ Ilimitada |
| **Control** | ❌ Sin control | ✅ Control total |
| **Latencia** | ⚠️ Variable | ✅ Optimizable |
| **Seguridad** | ⚠️ Depende del proveedor | ✅ Control total |
| **Confiabilidad** | ⚠️ Depende del proveedor | ✅ Depende de tu infraestructura |

## 🎯 Recomendación

### Para Desarrollo y Pruebas
**Usar servidores públicos** (`peerjs.com` y `0.peerjs.com`)
- Ya está funcionando
- No requiere cambios
- Suficiente para desarrollo

### Para Producción
**Instalar servidor propio** si:
- Esperas más de 50 usuarios concurrentes
- Necesitas mejor control y seguridad
- Quieres optimizar latencia
- Tienes recursos para mantenerlo

**Continuar con servidores públicos** si:
- Menos de 50 usuarios concurrentes
- No tienes recursos para mantener servidor adicional
- Priorizas simplicidad sobre control

## 🔧 Verificación Actual

Para verificar si tu servidor `juiciosorales.site` tiene PeerJS Server instalado:

```bash
# Desde tu servidor
curl https://juiciosorales.site/peerjs

# O desde tu máquina local
curl -I https://juiciosorales.site/peerjs
```

**Si responde con error 404 o 502:** No tienes PeerJS Server instalado
**Si responde con código 200 o 101:** Tienes PeerJS Server funcionando

## 📝 Notas Adicionales

1. **STUN Servers**: Tu código ya usa servidores STUN públicos de Google, que son necesarios para WebRTC y funcionan independientemente del servidor PeerJS.

2. **Límites de Servidores Públicos**: Los servidores públicos de PeerJS pueden tener límites no documentados. Para producción seria, considera un servidor propio.

3. **Alternativas a PeerJS**: Si decides cambiar de tecnología, considera:
   - **Janus Gateway** (más robusto, más complejo)
   - **Kurento** (más funciones, más pesado)
   - **Mediasoup** (muy escalable, requiere más configuración)

## 🆘 Soporte

Si necesitas ayuda con la instalación o configuración, puedes:
1. Revisar la documentación oficial: https://github.com/peers/peerjs-server
2. Verificar logs del servidor
3. Probar con herramientas de debugging incluidas en tu código

