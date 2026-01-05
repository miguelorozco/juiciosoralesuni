# 🧪 Configuración de Tests con MySQL Local

## 📋 Configuración Realizada

### 1. Base de Datos de Prueba

Se ha creado la base de datos `juiciosorales_test` para ejecutar los tests:

```sql
CREATE DATABASE IF NOT EXISTS juiciosorales_test 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;
```

### 2. Configuración en phpunit.xml

Se actualizó `phpunit.xml` para usar MySQL en lugar de SQLite:

```xml
<env name="DB_CONNECTION" value="mysql"/>
<env name="DB_HOST" value="127.0.0.1"/>
<env name="DB_PORT" value="3306"/>
<env name="DB_DATABASE" value="juiciosorales_test"/>
<env name="DB_USERNAME" value="miguel"/>
<env name="DB_PASSWORD" value="M!gu314ng31"/>
```

### 3. Credenciales MySQL

- **Usuario**: `miguel`
- **Contraseña**: `M!gu314ng31`
- **Host**: `127.0.0.1` (localhost)
- **Puerto**: `3306`
- **Base de datos de prueba**: `juiciosorales_test`

## 🚀 Ejecutar Tests

```bash
# Ejecutar todos los tests de diálogos v2
php artisan test --filter DialogosV2

# Ejecutar solo tests de migración
php artisan test --filter DialogosV2MigrationTest

# Ejecutar solo tests de funcionalidad
php artisan test --filter DialogosV2FuncionalidadTest

# Ejecutar todos los tests
php artisan test
```

## 🔧 Verificar Configuración

```bash
# Verificar que la base de datos existe
mysql -u miguel -p'M!gu314ng31' -e "SHOW DATABASES LIKE 'juiciosorales_test';"

# Verificar conexión desde PHP
php artisan tinker
# Luego ejecutar:
# DB::connection()->getPdo();
```

## 📝 Notas Importantes

1. **Base de datos separada**: Los tests usan `juiciosorales_test` para no afectar datos de producción
2. **RefreshDatabase**: Los tests usan `RefreshDatabase` trait que limpia la base de datos antes de cada test
3. **Migraciones automáticas**: Las migraciones se ejecutan automáticamente antes de cada test
4. **Credenciales**: Las credenciales están en `phpunit.xml` y son solo para tests

## ⚠️ Si los Tests Fallan

### Verificar que MySQL está corriendo:
```bash
sudo systemctl status mysql
# O
sudo service mysql status
```

### Verificar permisos del usuario:
```bash
mysql -u miguel -p'M!gu314ng31' -e "SHOW GRANTS;"
```

### Verificar que la base de datos existe:
```bash
mysql -u miguel -p'M!gu314ng31' -e "SHOW DATABASES;"
```

### Recrear la base de datos de prueba:
```bash
mysql -u miguel -p'M!gu314ng31' -e "DROP DATABASE IF EXISTS juiciosorales_test;"
mysql -u miguel -p'M!gu314ng31' -e "CREATE DATABASE juiciosorales_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

## 🔒 Seguridad

**IMPORTANTE**: Las credenciales en `phpunit.xml` son solo para desarrollo local. 
- No commitees archivos `.env` con credenciales reales
- En producción, usa variables de entorno seguras
- Considera usar `.env.testing` para tests en lugar de hardcodear en `phpunit.xml`
