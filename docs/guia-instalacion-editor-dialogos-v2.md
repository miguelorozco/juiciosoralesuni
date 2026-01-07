# 🚀 Guía de Instalación - Editor de Diálogos v2

Esta guía te ayudará a levantar y ejecutar el editor de diálogos v2 en tu máquina Ubuntu local.

## 📋 Índice

1. [Requisitos Previos](#requisitos-previos)
2. [Configuración Inicial](#configuración-inicial)
3. [Instalación de Dependencias](#instalación-de-dependencias)
4. [Configuración de Base de Datos](#configuración-de-base-de-datos)
5. [Ejecutar Migraciones](#ejecutar-migraciones)
6. [Iniciar el Servidor](#iniciar-el-servidor)
7. [Acceder al Editor](#acceder-al-editor)
8. [Troubleshooting](#troubleshooting)

---

## 🔧 Requisitos Previos

### Software Necesario

```bash
# Verificar versiones instaladas
php -v          # PHP 8.2 o superior
composer -v     # Composer 2.x
node -v         # Node.js 18.x o superior
npm -v          # npm 9.x o superior
mysql --version # MySQL 8.0 o MariaDB 10.6+
```

### Instalar PHP y Extensiones

```bash
# Actualizar repositorios
sudo apt update

# Instalar PHP y extensiones necesarias
sudo apt install php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-xml \
                 php8.2-mbstring php8.2-curl php8.2-zip php8.2-bcmath \
                 php8.2-gd php8.2-intl php8.2-soap

# Verificar instalación
php -v
php -m | grep -E "pdo_mysql|mbstring|xml|curl|zip"
```

### Instalar Composer

```bash
# Si no tienes Composer instalado
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

### Instalar Node.js y npm

```bash
# Opción 1: Usando NodeSource (recomendado)
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Opción 2: Usando nvm (Node Version Manager)
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash
source ~/.bashrc
nvm install 18
nvm use 18

# Verificar instalación
node -v
npm -v
```

### Instalar MySQL/MariaDB

```bash
# Instalar MySQL
sudo apt install mysql-server

# O instalar MariaDB
sudo apt install mariadb-server mariadb-client

# Iniciar servicio
sudo systemctl start mysql
sudo systemctl enable mysql

# Configurar MySQL (opcional, si es primera vez)
sudo mysql_secure_installation
```

---

## ⚙️ Configuración Inicial

### 1. Clonar/Navegar al Proyecto

```bash
# Si ya tienes el proyecto clonado
cd /home/miguel/Documents/github/juiciosorales

# O clonar desde el repositorio
# git clone [tu-repositorio] juiciosorales
# cd juiciosorales
```

### 2. Configurar Variables de Entorno

```bash
# Copiar archivo de ejemplo
cp .env.example .env

# Editar archivo .env
nano .env
```

**Configuración mínima en `.env`:**

```env
APP_NAME="Simulador de Juicios Orales"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=juiciosorales
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

# JWT Configuration
JWT_SECRET=
JWT_TTL=60
```

### 3. Generar Clave de Aplicación

```bash
php artisan key:generate
```

### 4. Generar Clave JWT

```bash
php artisan jwt:secret
```

---

## 📦 Instalación de Dependencias

### 1. Instalar Dependencias PHP

```bash
# Instalar dependencias de Composer
composer install

# Si hay problemas de memoria, aumentar límite
php -d memory_limit=512M /usr/local/bin/composer install
```

### 2. Instalar Dependencias Node.js

```bash
# Instalar dependencias npm
npm install

# Si hay problemas, limpiar caché
npm cache clean --force
rm -rf node_modules package-lock.json
npm install
```

---

## 🗄️ Configuración de Base de Datos

### 1. Crear Base de Datos

```bash
# Acceder a MySQL
sudo mysql -u root -p

# O si no tiene contraseña
sudo mysql
```

**En MySQL:**

```sql
-- Crear base de datos
CREATE DATABASE juiciosorales CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Crear usuario (opcional, puedes usar root)
CREATE USER 'juicios_user'@'localhost' IDENTIFIED BY 'tu_password_segura';
GRANT ALL PRIVILEGES ON juiciosorales.* TO 'juicios_user'@'localhost';
FLUSH PRIVILEGES;

-- Salir
EXIT;
```

### 2. Actualizar `.env` con Credenciales

```bash
nano .env
```

**Actualizar estas líneas:**

```env
DB_DATABASE=juiciosorales
DB_USERNAME=root          # O 'juicios_user' si creaste usuario
DB_PASSWORD=              # Tu contraseña de MySQL
```

### 3. Verificar Conexión

```bash
php artisan migrate:status
```

Si hay errores de conexión, verifica:
- MySQL está corriendo: `sudo systemctl status mysql`
- Credenciales en `.env` son correctas
- Usuario tiene permisos en la base de datos

---

## 🔄 Ejecutar Migraciones

### 1. Ejecutar Todas las Migraciones

```bash
# Ejecutar migraciones
php artisan migrate

# Si hay errores, puedes forzar (¡CUIDADO! Solo en desarrollo)
php artisan migrate --force
```

### 2. Verificar Migraciones de Diálogos v2

```bash
# Ver estado de migraciones
php artisan migrate:status | grep dialogos_v2

# Deberías ver:
# - create_dialogos_v2_table
# - create_nodos_dialogo_v2_table
# - create_respuestas_dialogo_v2_table
# - create_sesiones_dialogos_v2_table
# - create_decisiones_dialogo_v2_table
```

### 3. Ejecutar Seeders (Opcional)

```bash
# Si hay seeders de prueba
php artisan db:seed

# O seeders específicos
php artisan db:seed --class=DialogoEjemploSeeder
```

---

## 🚀 Iniciar el Servidor

### Opción 1: Servidor de Desarrollo (Recomendado para pruebas)

**⚠️ IMPORTANTE:** Debes tener DOS terminales abiertas:

```bash
# Terminal 1: Servidor Laravel
php artisan serve --host=0.0.0.0 --port=8000

# Terminal 2: Vite (hot reload para assets) - ¡OBLIGATORIO!
npm run dev
```

**Nota:** Si no ejecutas `npm run dev`, verás el error "Vite manifest not found". Vite debe estar corriendo para que Laravel pueda servir los assets.

**El servidor estará disponible en:**
- Laravel: `http://localhost:8000`
- Vite: `http://localhost:5173` (si está configurado)

### Opción 2: Servidor con Vite Integrado

```bash
# En una sola terminal (Laravel + Vite)
php artisan serve --host=0.0.0.0 --port=8000
# Y en otra terminal
npm run dev
```

### Verificar que Todo Funciona

```bash
# Verificar rutas
php artisan route:list | grep dialogos-v2

# Deberías ver:
# GET|HEAD  dialogos-v2/create ................ DialogoV2EditorController@create
# GET|HEAD  dialogos-v2/{dialogo}/editor ....... DialogoV2EditorController@show
```

---

## 🌐 Acceder al Editor

### 1. Crear Usuario de Prueba (si no existe)

```bash
php artisan tinker
```

**En Tinker:**

```php
use App\Models\User;

// Crear usuario administrador
User::create([
    'name' => 'Admin',
    'apellido' => 'Test',
    'email' => 'admin@test.com',
    'password' => bcrypt('password'),
    'tipo' => 'admin',
    'activo' => true,
]);

exit
```

### 2. Acceder al Sistema

1. **Abrir navegador:**
   ```
   http://localhost:8000
   ```

2. **Iniciar sesión:**
   - Email: `admin@test.com`
   - Password: `password`

3. **Acceder al Editor:**
   - Opción 1: Crear nuevo diálogo
   ```
   http://localhost:8000/dialogos-v2/create
   ```
   
   - Opción 2: Si ya tienes un diálogo con ID 1
   ```
   http://localhost:8000/dialogos-v2/1/editor
   ```

### 3. Probar Funcionalidades

- ✅ Crear nuevo diálogo
- ✅ Agregar nodos (Inicio, Desarrollo, Decisión, Final)
- ✅ Arrastrar nodos en el canvas
- ✅ Crear conexiones entre nodos
- ✅ Editar propiedades de nodos
- ✅ Agregar respuestas a nodos
- ✅ Guardar diálogo
- ✅ Validar estructura

---

## 🔍 Troubleshooting

### Error: "Class 'DialogoV2EditorController' not found"

```bash
# Limpiar caché de autoload
composer dump-autoload

# Limpiar caché de Laravel
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### Error: "Route [dialogos-v2.create] not defined"

```bash
# Verificar que las rutas están registradas
php artisan route:list | grep dialogos-v2

# Si no aparecen, verificar routes/web.php
# Asegúrate de que las rutas estén dentro de middleware(['web.auth'])
```

### Error: "Vite manifest not found"

**Solución:**

Este error ocurre cuando los assets de Vite no han sido compilados. Tienes dos opciones:

**Opción 1: Modo Desarrollo (Recomendado)**
```bash
# En una nueva terminal, ejecutar Vite
npm run dev
```

Esto iniciará Vite en modo desarrollo con hot reload. Debe estar corriendo mientras usas la aplicación.

**Opción 2: Compilar Assets para Producción**
```bash
# Compilar assets una vez
npm run build
```

Esto creará el directorio `public/build/` con los archivos compilados.

**Verificar que funciona:**
```bash
# Verificar que el directorio existe
ls -la public/build/

# Deberías ver:
# - manifest.json
# - assets/ (con archivos CSS y JS compilados)
```

**Nota:** En desarrollo, siempre debes tener `npm run dev` corriendo en una terminal separada mientras usas `php artisan serve`.

### Error: "jsPlumb is not defined"

```bash
# Verificar que jsPlumb se carga correctamente
# En el navegador, abre DevTools (F12) y verifica:
# - Console no muestra errores de jsPlumb
# - Network tab muestra que jsPlumb.min.js se carga (200 OK)

# Si no se carga, verifica la URL en editor.blade.php:
# https://cdn.jsdelivr.net/npm/@jsplumb/browser-ui@5.13.0/js/jsplumb.min.js
```

### Error: "419 CSRF Token Mismatch"

```bash
# Limpiar caché de sesión
php artisan session:clear

# Verificar que el meta tag CSRF está en layouts/app.blade.php
# <meta name="csrf-token" content="{{ csrf_token() }}">
```

### Error: "SQLSTATE[42S02]: Base table or view not found"

```bash
# Ejecutar migraciones faltantes
php artisan migrate

# Si hay problemas, verificar que las migraciones existen:
ls -la database/migrations/*dialogos_v2*
```

### Error: "Connection refused" en MySQL

```bash
# Verificar que MySQL está corriendo
sudo systemctl status mysql

# Si no está corriendo, iniciarlo
sudo systemctl start mysql

# Verificar conexión
mysql -u root -p -e "SHOW DATABASES;"
```

### Error: "npm install" falla

```bash
# Limpiar caché de npm
npm cache clean --force

# Eliminar node_modules y reinstalar
rm -rf node_modules package-lock.json
npm install

# Si sigue fallando, usar yarn
npm install -g yarn
yarn install
```

### El Editor no Carga (Pantalla en Blanco)

1. **Verificar errores en consola del navegador (F12)**
2. **Verificar que Alpine.js está cargado:**
   ```html
   <!-- En layouts/app.blade.php debe estar: -->
   <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
   ```

3. **Verificar que Bootstrap está cargado:**
   ```html
   <!-- En layouts/app.blade.php debe estar: -->
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   ```

4. **Limpiar caché del navegador:**
   - Ctrl + Shift + R (Chrome/Firefox)
   - O abrir en modo incógnito

### Los Nodos no se Mueven (Drag & Drop no funciona)

1. **Verificar que jsPlumb se inicializó correctamente:**
   - Abrir DevTools Console
   - Verificar que no hay errores de jsPlumb

2. **Verificar que los nodos tienen el ID correcto:**
   - Los nodos deben tener `id="node-{id}"`
   - Verificar en el HTML generado

3. **Verificar que jsPlumb.draggable() se llama:**
   - En la función `inicializarJsPlumb()` del script

---

## 📝 Comandos Útiles

```bash
# Limpiar todos los cachés
php artisan optimize:clear

# Ver rutas disponibles
php artisan route:list

# Ver configuración actual
php artisan config:show

# Verificar estado de migraciones
php artisan migrate:status

# Ejecutar tests (si existen)
php artisan test

# Ver logs en tiempo real
tail -f storage/logs/laravel.log

# Verificar permisos de storage
chmod -R 775 storage bootstrap/cache
```

---

## ✅ Checklist de Verificación

Antes de probar el editor, verifica:

- [ ] PHP 8.2+ instalado y funcionando
- [ ] Composer instalado y funcionando
- [ ] Node.js y npm instalados
- [ ] MySQL/MariaDB instalado y corriendo
- [ ] Base de datos creada
- [ ] Archivo `.env` configurado
- [ ] `APP_KEY` generado
- [ ] `JWT_SECRET` generado
- [ ] Dependencias PHP instaladas (`composer install`)
- [ ] Dependencias Node instaladas (`npm install`)
- [ ] Migraciones ejecutadas (`php artisan migrate`)
- [ ] Usuario de prueba creado
- [ ] Servidor Laravel corriendo (`php artisan serve`)
- [ ] Vite corriendo (`npm run dev`)
- [ ] Puedes acceder a `http://localhost:8000`
- [ ] Puedes iniciar sesión
- [ ] Puedes acceder a `/dialogos-v2/create`

---

## 🎯 Próximos Pasos

Una vez que el editor esté funcionando:

1. **Crear un diálogo de prueba:**
   - Crea un diálogo nuevo
   - Agrega varios nodos
   - Conecta los nodos con respuestas
   - Guarda el diálogo

2. **Probar funcionalidades:**
   - Validar estructura
   - Editar nodos
   - Eliminar nodos
   - Agregar/eliminar respuestas

3. **Verificar persistencia:**
   - Recarga la página
   - Verifica que el diálogo se guardó
   - Verifica que los nodos mantienen sus posiciones

---

## 📞 Soporte

Si encuentras problemas:

1. Revisa los logs: `storage/logs/laravel.log`
2. Verifica la consola del navegador (F12)
3. Revisa este documento de troubleshooting
4. Verifica que todas las dependencias están instaladas

---

**¡Listo! Ahora deberías poder usar el editor de diálogos v2 en tu máquina Ubuntu local.** 🎉
