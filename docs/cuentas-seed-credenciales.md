# 🔐 Cuentas Seed - Credenciales de Acceso

Este documento contiene todas las cuentas de usuario creadas por los seeders del sistema.

---

## 👨‍💼 Administradores

### AdminUserSeeder

| Nombre | Apellido | Email | Contraseña | Tipo |
|--------|----------|-------|------------|------|
| Miguel | Orozco | `miguel.orozco@me.com` | `m1gu314ng31` | admin |
| Administrador | Sistema | `admin@juiciosorales.site` | `password` | admin |

**Nota:** El usuario `miguel.orozco@me.com` es el administrador principal del sistema.

---

## 👨‍🏫 Instructores

### InstructoresSeeder

| Nombre | Apellido | Email | Contraseña | Tipo |
|--------|----------|-------|------------|------|
| Dr. Patricia | Mendoza | `patricia.mendoza@instructor.com` | `Patricia2024!` | instructor |
| Prof. Roberto | Silva | `roberto.silva@instructor.com` | `Roberto2024!` | instructor |
| Dra. Carmen | Vargas | `carmen.vargas@instructor.com` | `Carmen2024!` | instructor |
| Prof. Alejandro | Morales | `alejandro.morales@instructor.com` | `Alejandro2024!` | instructor |
| Dra. Isabel | Jiménez | `isabel.jimenez@instructor.com` | `Isabel2024!` | instructor |

**Total:** 5 instructores

---

## 🎓 Estudiantes

### EstudiantesSeeder

| Nombre | Apellido | Email | Contraseña | Tipo |
|--------|----------|-------|------------|------|
| Ana | García | `ana.garcia@estudiante.com` | `Ana2024!` | alumno |
| Carlos | Rodríguez | `carlos.rodriguez@estudiante.com` | `Carlos2024!` | alumno |
| María | López | `maria.lopez@estudiante.com` | `Maria2024!` | alumno |
| José | Martínez | `jose.martinez@estudiante.com` | `Jose2024!` | alumno |
| Laura | Hernández | `laura.hernandez@estudiante.com` | `Laura2024!` | alumno |
| Diego | González | `diego.gonzalez@estudiante.com` | `Diego2024!` | alumno |
| Sofía | Pérez | `sofia.perez@estudiante.com` | `Sofia2024!` | alumno |
| Andrés | Sánchez | `andres.sanchez@estudiante.com` | `Andres2024!` | alumno |
| Valentina | Ramírez | `valentina.ramirez@estudiante.com` | `Valentina2024!` | alumno |
| Sebastián | Cruz | `sebastian.cruz@estudiante.com` | `Sebastian2024!` | alumno |

**Total:** 10 estudiantes

---

## 📊 Resumen por Tipo de Usuario

| Tipo | Cantidad | Descripción |
|------|----------|-------------|
| **admin** | 2 | Administradores del sistema |
| **instructor** | 5 | Instructores/profesores |
| **alumno** | 10 | Estudiantes |
| **TOTAL** | **17** | Usuarios creados por seeders |

---

## 🚀 Cómo Ejecutar los Seeders

### Ejecutar Todos los Seeders

```bash
php artisan db:seed
```

### Ejecutar Seeders Específicos

```bash
# Solo administradores
php artisan db:seed --class=AdminUserSeeder

# Solo instructores
php artisan db:seed --class=InstructoresSeeder

# Solo estudiantes
php artisan db:seed --class=EstudiantesSeeder
```

### Ejecutar en Orden Recomendado

```bash
# 1. Primero los administradores (necesarios para crear otros usuarios)
php artisan db:seed --class=AdminUserSeeder

# 2. Luego instructores
php artisan db:seed --class=InstructoresSeeder

# 3. Finalmente estudiantes
php artisan db:seed --class=EstudiantesSeeder
```

---

## 🔑 Patrón de Contraseñas

### Administradores
- **Miguel Orozco**: `m1gu314ng31` (personalizada)
- **Admin Genérico**: `password` (genérica)

### Instructores
- **Patrón**: `{Nombre}2024!`
- Ejemplo: `Patricia2024!`, `Roberto2024!`, etc.

### Estudiantes
- **Patrón**: `{Nombre}2024!`
- Ejemplo: `Ana2024!`, `Carlos2024!`, etc.

---

## ⚠️ Notas de Seguridad

1. **Estas credenciales son solo para desarrollo y pruebas**
2. **NO usar en producción** sin cambiar las contraseñas
3. **Todas las contraseñas están hasheadas** en la base de datos usando `bcrypt`
4. **Los usuarios tienen `email_verified_at` establecido** para evitar verificación de email en desarrollo

---

## 🧪 Cuentas Recomendadas para Pruebas

### Para Probar el Editor de Diálogos v2

**Recomendado:** Usar cuenta de administrador
- Email: `admin@juiciosorales.site`
- Contraseña: `password`
- Tipo: `admin`

### Para Probar Roles de Usuario

**Administrador:**
- Email: `miguel.orozco@me.com`
- Contraseña: `m1gu314ng31`

**Instructor:**
- Email: `patricia.mendoza@instructor.com`
- Contraseña: `Patricia2024!`

**Estudiante:**
- Email: `ana.garcia@estudiante.com`
- Contraseña: `Ana2024!`

---

## 📝 Crear Usuario Manualmente

Si necesitas crear un usuario manualmente sin seeder:

```bash
php artisan tinker
```

```php
use App\Models\User;
use Illuminate\Support\Facades\Hash;

User::create([
    'name' => 'Nombre',
    'apellido' => 'Apellido',
    'email' => 'email@ejemplo.com',
    'password' => Hash::make('contraseña'),
    'tipo' => 'admin', // o 'instructor', 'alumno'
    'activo' => true,
    'email_verified_at' => now(),
]);

exit
```

---

## 🔍 Verificar Usuarios Creados

### Desde Tinker

```bash
php artisan tinker
```

```php
use App\Models\User;

// Contar usuarios por tipo
User::where('tipo', 'admin')->count();
User::where('tipo', 'instructor')->count();
User::where('tipo', 'alumno')->count();

// Listar todos los emails
User::pluck('email');

// Verificar un usuario específico
User::where('email', 'admin@juiciosorales.site')->first();

exit
```

### Desde MySQL

```sql
-- Ver todos los usuarios
SELECT id, name, apellido, email, tipo, activo FROM users;

-- Contar por tipo
SELECT tipo, COUNT(*) as total FROM users GROUP BY tipo;

-- Verificar un usuario específico
SELECT * FROM users WHERE email = 'admin@juiciosorales.site';
```

---

## 📚 Archivos Relacionados

- `database/seeders/AdminUserSeeder.php` - Seeders de administradores
- `database/seeders/InstructoresSeeder.php` - Seeders de instructores
- `database/seeders/EstudiantesSeeder.php` - Seeders de estudiantes
- `database/seeders/DatabaseSeeder.php` - Seeder principal que ejecuta todos

---

**Última actualización:** Enero 2025
