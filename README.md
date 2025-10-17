# 🎭 Simulador de Juicios Orales

Sistema completo para la simulación de juicios orales con diálogos ramificados, integración Unity y evaluación de estudiantes.

## 🚀 Instalación Rápida

### Opción 1: Script Automático
```bash
./dev.sh setup
```

### Opción 2: Instalación Manual

1. **Instalar dependencias**
```bash
npm install
composer install
```

2. **Configurar base de datos**
```bash
php artisan migrate
php artisan db:seed --class=DialogoEjemploSeeder
```

3. **Compilar assets**
```bash
npm run build
```

4. **Limpiar caché**
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

## 🛠️ Desarrollo

### Modo Desarrollo
```bash
# Terminal 1: Servidor Laravel
php artisan serve --host=0.0.0.0 --port=8000

# Terminal 2: Vite (hot reload)
npm run dev
```

### Compilar para Producción
```bash
npm run build
```

## 📁 Estructura del Proyecto

```
juicios_local/
├── app/
│   ├── Http/Controllers/     # Controladores API y Web
│   ├── Models/              # Modelos Eloquent
│   └── Http/Middleware/     # Middleware personalizado
├── database/
│   ├── migrations/          # Migraciones de BD
│   └── seeders/            # Seeders de datos
├── resources/
│   ├── views/              # Vistas Blade
│   ├── css/               # Estilos CSS/Tailwind
│   └── js/                # JavaScript/Alpine.js
├── routes/
│   ├── api.php            # Rutas API
│   └── web.php            # Rutas Web
└── public/
    └── build/             # Assets compilados
```

## 🎯 Funcionalidades

### ✅ Implementado
- **Sistema de autenticación** JWT completo
- **Dashboard interactivo** con estadísticas
- **Gestión de sesiones** con filtros avanzados
- **Editor de diálogos visual** con drag & drop
- **Vista de sesión activa** en tiempo real
- **Sistema de evaluación** con puntuaciones
- **Integración Unity** preparada
- **Diseño responsivo** moderno

### 🔧 Características Técnicas
- **Laravel 12.x** con PHP 8.2+
- **TailwindCSS** para estilos
- **Alpine.js** para interactividad
- **Vite** para compilación de assets
- **JWT** para autenticación
- **MySQL/MariaDB** para persistencia

## 🌐 URLs Importantes

- **Login**: `/login`
- **Dashboard**: `/dashboard`
- **Sesiones**: `/sesiones`
- **Diálogos**: `/dialogos`
- **API Docs**: `/api/documentation` (Swagger)

## 🔑 Usuarios de Prueba

Después de ejecutar los seeders, tendrás:
- **Admin**: admin@example.com
- **Instructor**: instructor@example.com
- **Alumno**: alumno@example.com

Contraseña por defecto: `password`

## 🎮 Integración Unity

El sistema está preparado para integrarse con Unity 3D:

### Endpoints Unity
```
GET  /api/unity/sesion/{id}/dialogo-estado
GET  /api/unity/sesion/{id}/respuestas-usuario/{user}
POST /api/unity/sesion/{id}/enviar-decision
POST /api/unity/sesion/{id}/notificar-hablando
GET  /api/unity/sesion/{id}/movimientos-personajes
```

## 📊 Sistema de Evaluación

- **Puntuación por respuesta** (0-10 puntos)
- **Tiempo de respuesta** registrado
- **Consecuencias automáticas** aplicadas
- **Historial completo** de decisiones
- **Estadísticas por rol** y usuario

## 🎨 Personalización

### Colores y Estilos
Edita `resources/css/app.css` para personalizar:
- Colores del tema
- Componentes personalizados
- Animaciones
- Modo oscuro

### Funcionalidad
- **Controladores**: `app/Http/Controllers/`
- **Modelos**: `app/Models/`
- **Vistas**: `resources/views/`
- **API**: `routes/api.php`

## 🚨 Solución de Problemas

### Error: Vite manifest not found
```bash
npm run build
```

### Error: Assets no cargan
```bash
php artisan config:clear
npm run build
```

### Error: Base de datos
```bash
php artisan migrate:fresh --seed
```

## 📞 Soporte

Para problemas o dudas:
1. Revisar logs en `storage/logs/`
2. Verificar permisos de archivos
3. Comprobar configuración de BD
4. Ejecutar `./dev.sh clear` para limpiar caché

## 🎉 ¡Listo para Usar!

El sistema está completamente funcional y listo para:
- ✅ Crear sesiones de juicios
- ✅ Diseñar diálogos ramificados
- ✅ Evaluar estudiantes
- ✅ Integrar con Unity 3D
- ✅ Generar reportes

**¡Disfruta simulando juicios orales!** ⚖️🎓