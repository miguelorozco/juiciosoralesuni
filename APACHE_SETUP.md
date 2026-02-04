# Configuración de Apache para JuiciosOralesUni

## ✅ Configuración Completada

### 1. Apache Configurado
- **VirtualHost**: Configurado en `/etc/apache2/other/juiciosoralesuni.conf`
- **Módulos habilitados**:
  - `mod_rewrite` (para URL rewriting)
  - `mod_proxy` (para proxy reverso)
  - `mod_proxy_http` (para proxy HTTP)
- **Funcionamiento**: Apache en puerto 80 hace proxy a Laravel en puerto 8000

### 2. Permisos Configurados
```bash
chmod o+x /Users/miguel /Users/miguel/Local /Users/miguel/Local/Github
chmod -R 755 public
chmod -R 775 storage bootstrap/cache
```

### 3. Node.js Actualizado
- **Versión anterior**: v14.21.3 (obsoleta)
- **Versión nueva**: v22.22.0
- **Ubicación**: `/opt/homebrew/opt/node@22/bin`
- **PATH actualizado**: Agregado a `~/.zshrc`

### 4. Assets Compilados
- Dependencias instaladas con `npm install --legacy-peer-deps`
- Build de producción generado: `public/build/manifest.json`

## 🚀 Cómo Usar

### Iniciar el Proyecto
```bash
# 1. Iniciar el servidor Laravel (debe estar corriendo para que Apache funcione)
./start-laravel-server.sh

# 2. Acceder al proyecto
open http://localhost
```

### Detener el Servidor
```bash
./stop-laravel-server.sh
```

### Ver Logs
```bash
# Laravel
tail -f storage/logs/laravel.log

# Apache
tail -f /var/log/apache2/juiciosoralesuni-error.log
tail -f /var/log/apache2/juiciosoralesuni-access.log
```

### Recompilar Assets
```bash
# En modo desarrollo (con hot reload)
npm run dev

# En modo producción
npm run build
```

## 📝 Comandos Útiles

### Apache
```bash
# Reiniciar Apache
sudo apachectl restart

# Detener Apache
sudo apachectl stop

# Iniciar Apache
sudo apachectl start

# Verificar configuración
sudo apachectl configtest

# Ver estado
sudo apachectl status
```

### Laravel
```bash
# Limpiar caché
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Ver rutas
php artisan route:list
```

## 🔧 Configuración de Archivos

### VirtualHost (`juiciosoralesuni-vhost.conf`)
```apache
<VirtualHost *:80>
    ServerName localhost
    
    ProxyPreserveHost On
    ProxyPass / http://127.0.0.1:8000/
    ProxyPassReverse / http://127.0.0.1:8000/
    
    ErrorLog "/var/log/apache2/juiciosoralesuni-error.log"
    CustomLog "/var/log/apache2/juiciosoralesuni-access.log" common
</VirtualHost>
```

### APP_URL en `.env`
```env
APP_URL=http://localhost
```

## 🎯 Acceso

- **URL Principal**: http://localhost
- **Usuario Admin**: miguel.orozco@me.com
- **Contraseña**: m1gu314ng31

- **Usuario Admin Alternativo**: admin@juiciosorales.site
- **Contraseña**: password

## ⚠️ Importante

1. **Siempre debe estar corriendo el servidor Laravel** en puerto 8000 para que Apache pueda hacer proxy
2. Si reinicias tu Mac, ejecuta: `./start-laravel-server.sh`
3. Node@22 ahora está en tu PATH por defecto (agregado a `.zshrc`)
4. Los assets ya están compilados en `public/build/`

## 🐛 Solución de Problemas

### Error 403 Forbidden
```bash
# Verificar permisos
chmod o+x /Users/miguel /Users/miguel/Local /Users/miguel/Local/Github
chmod -R 755 public
```

### Error 500 Internal Server Error
```bash
# Ver logs
tail -f storage/logs/laravel.log

# Limpiar caché
php artisan config:clear
```

### Error "Vite manifest not found"
```bash
# Recompilar assets
npm run build
```

### Servidor Laravel no responde
```bash
# Detener y reiniciar
./stop-laravel-server.sh
./start-laravel-server.sh

# Verificar que está corriendo
lsof -i :8000
```
