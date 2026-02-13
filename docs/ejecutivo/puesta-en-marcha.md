# Puesta en marcha — Simulador de Juicios Orales

Guía paso a paso para instalar, configurar y ejecutar el proyecto: software requerido, versiones, migraciones, seeders y scripts de arranque.

---

## 1. Software y versiones

### 1.1 Requeridos

| Software | Versión mínima | Uso |
|----------|----------------|-----|
| **PHP** | 8.2 | Backend Laravel |
| **Composer** | 2.x | Dependencias PHP |
| **Node.js** | 18.x LTS (recomendado) | Build frontend (Vite, npm) |
| **npm** | 9.x | Dependencias JS y scripts |
| **MySQL** o **MariaDB** | 8.0+ / 10.6+ | Base de datos (recomendado en producción) |
| **SQLite** | 3 | Alternativa para desarrollo (sin servidor DB) |

### 1.2 Unity (cliente 3D / WebGL)

| Software | Versión | Uso |
|----------|---------|-----|
| **Unity Hub** | Última estable | Gestión de instalaciones de Unity. |
| **Unity Editor** | **6000.3.x** (Unity 6) o **2022.3.15f1** LTS (o superior) | Edición y compilación del proyecto 3D. |

El proyecto define la versión en `unity-project/ProjectSettings/ProjectVersion.txt` (actualmente **6000.3.6f1**). Se recomienda usar esa misma versión o la LTS 2022.3.15f1 para compatibilidad.

### 1.3 Opcionales (voz en tiempo real)

| Software | Uso |
|----------|-----|
| **LiveKit Server** | Servicio de voz/WebRTC para Unity |
| **coturn** | Servidor STUN/TURN para WebRTC |

### 1.4 Versiones del proyecto

- **Laravel**: 12.x (`composer.json`)
- **PHP**: ^8.2
- **Vite**: ^7.3
- **Bootstrap**: ^5.3
- **JWT**: tymon/jwt-auth ^2.2

---

## 2. Proceso de instalación

### 2.1 Clonar o ubicar el proyecto

```bash
cd /ruta/donde/quieras
git clone <url-del-repositorio> juiciosoralesuni
cd juiciosoralesuni
```

### 2.2 Dependencias PHP

```bash
composer install
```

Si hay conflictos de versión de PHP, usar el PHP correcto, por ejemplo:

```bash
/usr/bin/php8.2 composer install
# o
php@8.3/bin/php $(which composer) install
```

### 2.3 Dependencias Node (frontend)

```bash
npm install
```

### 2.4 Archivo de entorno

```bash
cp .env.example .env
php artisan key:generate
```

Para **JWT** (login API y Unity):

```bash
php artisan jwt:secret
```

Esto añade o actualiza `JWT_SECRET` en `.env`.

### 2.5 Base de datos

**Opción A — SQLite (desarrollo rápido)**

```bash
touch database/database.sqlite
```

En `.env` dejar o establecer:

```env
DB_CONNECTION=sqlite
# DB_DATABASE suele ser la ruta por defecto a database/database.sqlite
```

**Opción B — MySQL/MariaDB**

Crear la base de datos en el servidor (por ejemplo `juiciosorales`). En `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=juiciosorales
DB_USERNAME=root
DB_PASSWORD=tu_password
```

### 2.6 Migraciones

Ejecutar todas las migraciones:

```bash
php artisan migrate
```

Si ya existe contenido y quieres empezar desde cero (¡borra todos los datos!):

```bash
php artisan migrate:fresh
```

### 2.7 Seeders (datos iniciales)

**Todos los seeders (recomendado la primera vez):**

```bash
php artisan db:seed
```

Orden ejecutado por `DatabaseSeeder`:

1. `RolesDisponiblesSeeder` — Roles del simulador (Juez, Fiscal, etc.)
2. `ConfiguracionesSistemaSeeder` — Configuración global
3. `ConfiguracionRegistroSeeder` — Estado de registro de usuarios
4. `AdminUserSeeder` — Administradores
5. `EstudiantesSeeder` — Estudiantes de prueba
6. `InstructoresSeeder` — Instructores de prueba
7. `DialogoJuicioPenalSeeder` — Diálogo ejemplo penal
8. `RolesDialogoSeeder` — Relación roles–diálogos
9. `DialogoRoboOXXOCompletoSeeder` — Caso OXXO (legacy)
10. `DialogoV2EjemploSeeder` — Diálogo ejemplo v2
11. `DialogoV2OxxoSeeder` — Caso OXXO v2

**Solo un seeder de ejemplo (diálogo):**

```bash
php artisan db:seed --class=DialogoEjemploSeeder
```

**Seeders concretos (ejemplos):**

```bash
php artisan db:seed --class=AdminUserSeeder
php artisan db:seed --class=RolesDisponiblesSeeder
```

Credenciales y cuentas creadas por los seeders: ver `docs/cuentas-seed-credenciales.md`.

### 2.8 Compilar assets (frontend)

Para producción o para servir con `php artisan serve` sin Vite en vivo:

```bash
npm run build
```

### 2.9 Limpiar caché

Después de cambiar `.env` o config:

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

---

## 3. Usar el script de orquestación (dev.sh)

El script `./dev.sh` en la raíz automatiza varios pasos.

| Comando | Acción |
|---------|--------|
| `./dev.sh setup` | `npm install`, `composer install`, `migrate`, `db:seed --class=DialogoEjemploSeeder`, `npm run build`, limpieza de caché. **No** ejecuta todos los seeders; para eso usar `php artisan db:seed` después. |
| `./dev.sh install` | Solo `npm install` y `composer install`. |
| `./dev.sh build` | `npm run build`. |
| `./dev.sh migrate` | `php artisan migrate`. |
| `./dev.sh seed` | `php artisan db:seed --class=DialogoEjemploSeeder` (solo ese seeder). |
| `./dev.sh clear` | Limpia config, cache, view y route. |
| `./dev.sh dev` | Inicia Vite en background y `php artisan serve --host=0.0.0.0 --port=8000` en primer plano. |

**Ejemplo de uso tras clonar:**

```bash
./dev.sh install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
# Configurar DB en .env (sqlite o mysql)
./dev.sh migrate
php artisan db:seed
./dev.sh build
./dev.sh clear
```

---

## 4. Scripts para iniciar y detener servidores

Scripts en la **raíz del proyecto** (Linux/macOS, bash).

### 4.1 Laravel

| Script | Acción |
|--------|--------|
| `./start-laravel-server.sh` | Inicia Laravel en segundo plano en el puerto **8000**. Escribe PID en `storage/laravel-server.pid` y logs en `storage/logs/laravel-server.log`. |
| `./stop-laravel-server.sh` | Detiene el proceso de Laravel usando el PID guardado o libera el puerto 8000. |

**Nota:** `start-laravel-server.sh` puede usar una ruta fija a PHP (por ejemplo `/opt/homebrew/opt/php@8.3/bin/php`). Si tu PHP está en otro sitio, edita el script o asegura que `php` en el PATH sea la versión correcta.

### 4.2 LiveKit + coturn (voz)

| Script | Acción |
|--------|--------|
| `./start-livekit.sh` | Inicia LiveKit Server (puerto **7880**) y, si está instalado, **coturn** (puerto **3478**). Crea `livekit.yaml` si no existe. PIDs en `.livekit.pid` y `.coturn.pid`. |
| `./stop-livekit.sh` | Detiene LiveKit y coturn. |

Requisitos previos (ver `QUICKSTART.md` o documentación LiveKit):

- LiveKit: p. ej. `curl -sSL https://get.livekit.io | bash`
- coturn: p. ej. `sudo apt-get install coturn` (Ubuntu/Debian)

Variables útiles en `.env` para LiveKit:

```env
LIVEKIT_API_KEY=devkey
LIVEKIT_API_SECRET=secret
LIVEKIT_HOST=ws://localhost:7880
LIVEKIT_HTTP_URL=http://localhost:7880
COTURN_HOST=localhost
COTURN_PORT=3478
COTURN_REALM=juiciosoralesuni
```

---

## 5. Orden recomendado para poner en marcha

### Primera vez (instalación completa)

1. Instalar PHP 8.2+, Composer, Node 18+, MySQL o SQLite.
2. Clonar el proyecto y entrar en la carpeta.
3. `composer install` y `npm install`.
4. `cp .env.example .env` → `php artisan key:generate` → `php artisan jwt:secret`.
5. Configurar `DB_*` en `.env` y crear la base si usas MySQL.
6. `php artisan migrate`.
7. `php artisan db:seed` (todos los seeders).
8. `npm run build`.
9. `php artisan config:clear` (y opcionalmente `cache:clear`, `view:clear`, `route:clear`).

### Cada vez que quieras trabajar (desarrollo)

1. **Laravel** (obligatorio):
   ```bash
   ./start-laravel-server.sh
   ```
   O en una terminal: `php artisan serve --host=0.0.0.0 --port=8000`.

2. **Opcional — Vite (recarga en caliente de assets):**
   ```bash
   npm run dev
   ```
   En otra terminal, dejar Laravel con `php artisan serve` si no usaste el script.

3. **Opcional — LiveKit** (si usas voz en Unity):
   ```bash
   ./start-livekit.sh
   ```

Para detener: `./stop-laravel-server.sh` y, si aplica, `./stop-livekit.sh`.

---

## 6. Unity: versión y compilación WebGL

Para que los usuarios puedan entrar a la experiencia 3D desde el navegador, el proyecto Unity debe compilarse a **WebGL** y colocarse donde Laravel lo sirve.

### 6.1 Versión de Unity necesaria

- **Recomendada:** la indicada en `unity-project/ProjectSettings/ProjectVersion.txt` (p. ej. **6000.3.6f1** para Unity 6).
- **Alternativa:** **Unity 2022.3.15f1 LTS** o superior (compatible con la mayoría del proyecto).

Instalación:

1. Descargar **Unity Hub**: [unity.com/download](https://unity.com/download).
2. En Unity Hub, **Añadir** o **Instalar** el módulo **Unity Editor** con la versión que use el proyecto (ver `ProjectVersion.txt`).

### 6.2 Pasos para compilar el proyecto Unity (WebGL)

**Opción A — Desde el Editor (cualquier OS)**

1. Abrir **Unity Hub** y abrir el proyecto: ruta `unity-project/` (en la raíz del repositorio).
2. En el menú: **File → Build Settings** (o **Archivo → Configuración de compilación**).
3. En **Platform** seleccionar **WebGL**. Si no está instalado, pulsar **Open Download Page**, instalar el módulo WebGL y repetir.
4. Pulsar **Switch Platform** si cambiaste de plataforma.
5. En **Build** (o **Compilación**):
   - Elegir carpeta de salida, por ejemplo `storage/unity-build` (crear la carpeta si no existe).
   - Opcional: **Development Build** para depuración.
   - Pulsar **Build** (o **Build And Run** para abrir en el navegador tras compilar).
6. Cuando termine, el build estará en la carpeta elegida. Laravel debe servir esa carpeta (por defecto `storage/unity-build`); ver rutas en `routes/web.php` (p. ej. `/unity-game`, `/unity-build`).

**Opción B — Script automático (Windows, PowerShell)**

En la raíz del proyecto existe el script `build-unity-webgl.ps1` que compila y copia el build a `storage/unity-build/`:

```powershell
.\build-unity-webgl.ps1
```

Parámetros opcionales:

```powershell
# Especificar versión de Unity (debe estar instalada en Hub)
.\build-unity-webgl.ps1 -UnityVersion "2022.3.15f1"

# Ruta de destino
.\build-unity-webgl.ps1 -BuildPath "storage\unity-build"

# Solo compilar, sin copiar al destino final
.\build-unity-webgl.ps1 -SkipCopy
```

**Nota:** El script busca por defecto el proyecto en `unity-integration\unity-project`. Si tu proyecto está en `unity-project\` en la raíz, edita la variable `$UnityProjectPath` en el script o compila desde el Editor (Opción A).

### 6.3 Después de compilar

- El build debe quedar en **`storage/unity-build/`** para que Laravel lo sirva (ruta usada por las rutas web del proyecto).
- Contenido típico: `index.html`, carpeta `Build/` (loader, .data, .framework, .wasm, etc.) y opcionalmente `StreamingAssets/`.
- Probar entrando desde la web a la URL que enlaza con el build (p. ej. **Entrar a Unity** desde el detalle de una sesión, o la ruta configurada tipo `/unity-game`).

Documentación más detallada: `docs/build-unity-webgl.md`, `docs/checklist-webgl-compilacion.md`.

---

## 7. Comprobar que todo funciona

- **Web:** abrir `http://localhost:8000` (o la URL configurada en `APP_URL`).
- **Login:** `http://localhost:8000/login` con un usuario de los seeders (ver `docs/cuentas-seed-credenciales.md`).
- **API:** `http://localhost:8000/api/health` o `http://localhost:8000/api/health/ping`.
- **Documentación API:** `http://localhost:8000/api/documentation` (Swagger).
- **Unity WebGL:** entrar a una sesión desde la web y usar **Entrar a Unity** (o la ruta que sirva el build en `storage/unity-build/`).

---

## 8. Resolución de problemas frecuentes

| Problema | Posible solución |
|----------|------------------|
| "Vite manifest not found" | Ejecutar `npm run build`. |
| Error 500 tras cambiar `.env` | `php artisan config:clear` y `php artisan cache:clear`. |
| Error de conexión a base de datos | Revisar `DB_*` en `.env`, que la base exista (MySQL) o que exista `database/database.sqlite` (SQLite). |
| "JWT Secret not set" | Ejecutar `php artisan jwt:secret`. |
| Puerto 8000 en uso | Cambiar puerto: `php artisan serve --port=8001` o detener el proceso que usa 8000; con script: `./stop-laravel-server.sh`. |
| Permisos en `storage` o `bootstrap/cache` | `chmod -R 775 storage bootstrap/cache` y propietario correcto del servidor web. |
| Unity: "No se encontró Unity Editor" (script) | Instalar Unity Hub y la versión del proyecto (ver `unity-project/ProjectSettings/ProjectVersion.txt`). Ajustar `$UnityProjectPath` en el script si el proyecto está en `unity-project/` y no en `unity-integration/unity-project`. |
| Build WebGL no aparece en la web | Verificar que el build está en `storage/unity-build/` y que las rutas en `routes/web.php` apuntan a esa carpeta. |

---

*Documento de la carpeta `docs/ejecutivo/`. Complementa a `presentacion-ejecutiva-desarrollo.md`.*
