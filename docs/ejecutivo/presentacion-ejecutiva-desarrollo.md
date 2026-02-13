# Presentación Ejecutiva / Desarrollo — Simulador de Juicios Orales

**Documento único de referencia** para presentaciones ejecutivas y equipos de desarrollo. Cubre visión, funcionalidades, arquitectura, stack tecnológico y operación del sistema.

---

## 1. Resumen ejecutivo

### 1.1 Qué es el sistema

**Simulador de Juicios Orales** es una plataforma educativa que permite a instituciones simular juicios orales en un entorno virtual 3D. Combina:

- **Panel web (Laravel)** para administrar sesiones, roles, diálogos y usuarios.
- **Cliente 3D (Unity WebGL)** para que varios participantes participen en tiempo real con voz y decisiones ramificadas.
- **Sistema de diálogos ramificados** con opciones, puntuación y evaluación.

### 1.2 Objetivos principales

| Objetivo | Descripción |
|----------|-------------|
| **Educación inmersiva** | Entorno 3D realista para practicar juicios orales. |
| **Evaluación automática y manual** | Puntuación por respuestas y evaluación por profesor. |
| **Multiplayer en tiempo real** | Hasta 20 participantes por sesión con comunicación de voz. |
| **Gestión completa** | Crear sesiones, asignar roles, diseñar diálogos y ver progreso. |
| **Tracking y reportes** | Timeline de decisiones, estadísticas y progreso por sesión. |

### 1.3 Público objetivo

- **Administradores**: configuración global, usuarios, permisos.
- **Instructores**: sesiones, asignación de roles, diálogos, evaluación.
- **Estudiantes (alumnos)**: participar en sesiones, elegir opciones en el diálogo, recibir retroalimentación.

---

## 2. Funcionalidades del sistema

### 2.1 Por módulo

| Módulo | Funcionalidad | Roles |
|--------|----------------|-------|
| **Autenticación** | Login/registro web, JWT para API y Unity, cookies de sesión web. | Todos |
| **Dashboard** | Resumen, accesos rápidos, actividad reciente. | Todos |
| **Sesiones** | Crear, editar, listar, filtrar; iniciar/finalizar/reiniciar diálogo; ver detalle y **progreso (timeline)**. | Admin, Instructor |
| **Roles** | CRUD de roles disponibles (nombre, color, icono, orden); uso en plantillas y sesiones. | Admin, Instructor |
| **Diálogos V2** | Editor visual (nodos, respuestas, condiciones, consecuencias); diálogos ramificados. | Admin, Instructor |
| **Asignaciones** | Asignar usuarios a roles por sesión; confirmación; vista en detalle de sesión. | Admin, Instructor |
| **Unity** | Entrada por código de sesión, selección de rol, sala 3D, diálogo en vivo, envío de decisiones. | Alumno, Instructor |
| **Estadísticas** | Dashboard de métricas, top instructores, actividad reciente, sesiones por mes, distribución de usuarios, tabla de rendimiento por sesión. | Admin, Instructor |
| **Progreso de sesión** | Timeline de opciones elegidas, quién eligió (rol + email), tiempo de respuesta. | Admin, Instructor |
| **Perfil** | Ver/editar perfil, cambiar contraseña, estadísticas personales. | Todos |
| **Configuración** | Parámetros del sistema (actualmente oculta en menú). | Admin |

### 2.2 Flujos principales

1. **Preparación (instructor/admin)**  
   Crear sesión → elegir diálogo y plantilla → asignar participantes a roles → (opcional) iniciar diálogo desde web.

2. **Participación (estudiante)**  
   Entrar a Unity con código de sesión → elegir rol → unirse a la sala → ver nodos del diálogo y elegir opciones cuando es su turno.

3. **Seguimiento (instructor/admin)**  
   Ver sesión en curso, conectados, progreso del diálogo; **Progreso** con timeline de decisiones; reiniciar diálogo (limpia decisiones para tracking correcto).

4. **Evaluación**  
   Puntuación automática por respuesta; campos para calificación y notas del profesor; estados: pendiente, evaluado, revisado.

---

## 3. Stack tecnológico

### 3.1 Backend

| Tecnología | Uso |
|------------|-----|
| **PHP 8.2+** | Lenguaje del servidor. |
| **Laravel 12.x** | Framework, rutas, controladores, modelos, middleware. |
| **MySQL / MariaDB** | Base de datos relacional. |
| **JWT (tymon/jwt-auth)** | Autenticación API y Unity. |
| **Spatie Laravel Permission** | Permisos y roles (opcional). |
| **L5-Swagger** | Documentación API (`/api/documentation`). |

### 3.2 Frontend web

| Tecnología | Uso |
|------------|-----|
| **Blade** | Plantillas HTML. |
| **Bootstrap 5** | UI y paginación. |
| **Alpine.js** | Interactividad en vistas. |
| **Vite** | Build y assets. |

### 3.3 Cliente Unity

| Tecnología | Uso |
|------------|-----|
| **Unity** (2022.3+) | Motor 3D y WebGL. |
| **C# / .NET Standard 2.1** | Lógica y comunicación con Laravel. |
| **REST API** | Estado del diálogo, envío de decisiones, salas, auth. |

### 3.4 Comunicación y servicios

- **REST (JSON)** entre navegador, Unity y Laravel.
- **WebSockets / tiempo real** según configuración (eventos de sala, etc.).
- **LiveKit** (según despliegue) para voz y multijugador.

---

## 4. Arquitectura de alto nivel

```
┌─────────────────────────────────────────────────────────────────┐
│  CLIENTES                                                        │
│  • Navegador (Dashboard, Editor diálogos, Estadísticas, Sesiones) │
│  • Unity WebGL (sala 3D, diálogo, decisiones, voz)               │
└─────────────────────────────────────────────────────────────────┘
                                    │
                                    │ HTTPS / REST API / JWT
                                    ▼
┌─────────────────────────────────────────────────────────────────┐
│  LARAVEL (Backend)                                               │
│  • Auth (web + JWT)  • Sesiones  • Diálogos V2  • Roles         │
│  • Unity API (estado, decisiones, salas)  • Estadísticas          │
│  • Middleware: web.auth, auth:api, unity.auth, user.type        │
└─────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────┐
│  BASE DE DATOS (MySQL/MariaDB)                                   │
│  users, roles_disponibles, sesiones_juicios, asignaciones_roles  │
│  dialogos_v2, nodos_dialogo_v2, respuestas_dialogo_v2           │
│  sesiones_dialogos_v2, decisiones_dialogo_v2, unity_rooms, ...   │
└─────────────────────────────────────────────────────────────────┘
```

---

## 5. Base de datos (resumen)

### 5.1 Entidades principales

| Entidad | Descripción |
|---------|-------------|
| **users** | Usuarios (admin, instructor, alumno). |
| **roles_disponibles** | Roles reutilizables (Juez, Fiscal, Defensa, etc.). |
| **sesiones_juicios** | Sesiones de juicio (nombre, estado, fechas, instructor, diálogo asociado). |
| **asignaciones_roles** | Asignación usuario–rol por sesión (confirmado, fecha). |
| **dialogos_v2** | Diálogos (nombre, estado, público, creador). |
| **nodos_dialogo_v2** | Nodos (inicio, desarrollo, decisión, final); contenido, rol, posición. |
| **respuestas_dialogo_v2** | Opciones por nodo; texto, puntuación, nodo siguiente, consecuencias. |
| **sesiones_dialogos_v2** | Instancia de diálogo en una sesión (nodo actual, estado, historial). |
| **decisiones_dialogo_v2** | Cada elección: usuario, rol, respuesta, texto, puntuación, tiempo, evaluación. |
| **unity_rooms** | Salas Unity (room_id, sesión, participantes, estado). |

### 5.2 Tracking de decisiones

- Cada decisión se guarda en **decisiones_dialogo_v2** (qué opción, quién, rol, tiempo, puntuación).
- La pantalla **Progreso** de una sesión muestra el timeline de esas decisiones (rol + email).
- Al **reiniciar diálogo** se borran las decisiones de esa sesión de diálogo para mantener un tracking coherente.

---

## 6. Rutas y APIs principales

### 6.1 Web (protegidas por sesión web)

| Ruta | Descripción |
|------|-------------|
| `/login`, `/logout` | Autenticación web. |
| `/dashboard` | Panel principal. |
| `/sesiones` | Listado, crear, editar, ver, **progreso**, iniciar/finalizar/reiniciar diálogo. |
| `/roles` | CRUD roles disponibles. |
| `/dialogos-v2`, `/dialogos-v2/{id}/editor` | Listado y editor visual de diálogos. |
| `/estadisticas` | Dashboard de estadísticas. |
| `/profile` | Perfil de usuario. |

### 6.2 API REST (JWT o sesión según contexto)

- **Auth**: `POST /api/auth/login`, `GET /api/auth/me`, etc.
- **Sesiones**: `GET/POST /api/sesiones`, `GET/PUT /api/sesiones/{id}`, iniciar/finalizar.
- **Roles**: `GET/POST /api/roles`, `GET/PUT /api/roles/{id}`.
- **Diálogos V2**: CRUD vía editor (rutas bajo `api/dialogos-v2` y web).
- **Estadísticas**: `GET /api/estadisticas/dashboard`, `top-instructores`, `actividad-reciente`, etc.
- **Unity** (prefijo `/api/unity`):
  - Auth: login, status, refresh, logout, me.
  - Sesiones: buscar por código, mi rol, confirmar rol, disponibles.
  - Diálogo: iniciar, estado, respuestas-usuario, avanzar-nodo, enviar-decision.
  - Salas: create, join, leave, state, sync-player, audio-state, events, close.
  - LiveKit: token, status (si aplica).

---

## 7. Seguridad y permisos

- **Web**: middleware `web.auth`; sesión y/o cookie `web_auth_token` (JWT).
- **API**: `auth:api` (JWT) o uso de cookie web para mismas peticiones.
- **Unity**: middleware `unity.auth` con tokens propios para el cliente Unity.
- **Por tipo de usuario**: `user.type:admin`, `user.type:admin,instructor` en rutas sensibles (edición, eliminación, estadísticas, progreso).
- **Rate limiting** y prevención de enumeración en login.
- Contraseñas hasheadas; JWT con expiración y refresh.

---

## 8. Despliegue y operación

### 8.1 Requisitos

- PHP 8.2+, Composer, Node/NPM.
- MySQL/MariaDB.
- Servidor web (Apache/Nginx) o `php artisan serve` en desarrollo.

### 8.2 Comandos típicos

```bash
composer install
npm install && npm run build
cp .env.example .env && php artisan key:generate
# Configurar .env (DB, JWT, etc.)
php artisan migrate
php artisan db:seed
php artisan config:clear && php artisan cache:clear
```

### 8.3 Inicio de servidores (scripts)

En la raíz del proyecto hay scripts para arrancar y detener los servicios en entorno de desarrollo.

| Script | Uso | Descripción |
|--------|-----|-------------|
| **`./dev.sh`** | `./dev.sh [comando]` | Orquestación de desarrollo. |
| **`./start-laravel-server.sh`** | Iniciar | Laravel en segundo plano (puerto 8000). |
| **`./stop-laravel-server.sh`** | Detener | Detiene el servidor Laravel. |
| **`./start-livekit.sh`** | Iniciar | LiveKit Server + coturn (voz/WebRTC). |
| **`./stop-livekit.sh`** | Detener | Detiene LiveKit y coturn. |

**Comandos de `dev.sh`:**

- `./dev.sh setup` — Instalación completa (npm, composer, migrate, seed, build, clear).
- `./dev.sh install` — Solo instalar dependencias (npm + composer).
- `./dev.sh build` — Compilar assets (npm run build).
- `./dev.sh dev` — Modo desarrollo: Vite en background + `php artisan serve` en el puerto 8000 (proceso en primer plano).
- `./dev.sh clear` — Limpiar caché de Laravel (config, cache, view, route).
- `./dev.sh migrate` — Ejecutar migraciones.
- `./dev.sh seed` — Ejecutar seeders de ejemplo.

**Orden recomendado para levantar todo (desarrollo):**

1. **Laravel** (obligatorio para la web y la API):
   ```bash
   ./start-laravel-server.sh
   ```
   Servidor en http://127.0.0.1. Logs en `storage/logs/laravel-server.log`. Para detener: `./stop-laravel-server.sh`.

2. **LiveKit** (solo si se usa voz en tiempo real en Unity):
   ```bash
   ./start-livekit.sh
   ```
   LiveKit en el puerto 7880 (config en `livekit.yaml`). coturn en 3478 (STUN/TURN). Para detener: `./stop-livekit.sh`.

**Nota:** Los scripts de inicio/detención son para Linux/macOS (bash). En Windows se puede usar WSL o ejecutar manualmente `php artisan serve` y, si aplica, el binario de LiveKit.

### 8.4 URLs de interés

- Aplicación: según vhost o `http://localhost:8000`.
- Login: `/login`.
- API docs: `/api/documentation` (Swagger).
- Health: `/api/health`, `/api/health/ping`.

### 8.5 Usuarios de prueba (seed)

- **Admin**: p. ej. `admin@juiciosorales.site` / `password`.
- **Instructor**: varios en `docs/cuentas-seed-credenciales.md`.
- **Alumno**: varios en el mismo documento.

---

## 9. Resumen para presentación

- **Una frase**: Plataforma educativa para simular juicios orales en 3D con diálogos ramificados, roles, evaluación y seguimiento en tiempo real.
- **Tres pilares**: (1) Panel web para gestión de sesiones, roles y diálogos; (2) Experiencia 3D multijugador en Unity; (3) Sistema de decisiones y evaluación con tracking completo.
- **Valor**: Práctica guiada y evaluable de juicios orales, con trazabilidad de quién eligió qué y cuándo, y estadísticas para instructores y administradores.

---

*Documento generado para uso ejecutivo y de desarrollo. Referencia técnica detallada en `docs/documentacion-tecnica-completa.md` y en los demás archivos de `docs/`.*
