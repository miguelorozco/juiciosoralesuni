# 🔧 Instalación Completa de Extensiones PHP para Laravel

## ⚠️ Problema Actual

El error `could not find driver` indica que falta la extensión **PDO MySQL**.

## ✅ Solución Completa

Ejecuta estos comandos para instalar todas las extensiones necesarias:

```bash
# 1. Actualizar repositorios
sudo apt update

# 2. Instalar extensiones PHP esenciales
sudo apt install \
    php8.3-xml \
    php8.3-dom \
    php8.3-mysql \
    php8.3-pdo \
    php8.3-pdo-mysql \
    php8.3-mbstring \
    php8.3-curl \
    php8.3-zip \
    php8.3-bcmath \
    php8.3-tokenizer \
    php8.3-fileinfo \
    php8.3-intl
```

## 🔍 Verificar Instalación

```bash
# Verificar extensiones instaladas
php -m | grep -E "(pdo|mysql|xml|dom|mbstring|curl|zip|bcmath|tokenizer|fileinfo|intl)"

# Deberías ver:
# PDO
# pdo_mysql
# mysql
# xml
# dom
# mbstring
# curl
# zip
# bcmath
# tokenizer
# fileinfo
# intl
```

## 🧪 Probar Conexión a MySQL

```bash
# Probar conexión desde PHP
php -r "
try {
    \$pdo = new PDO('mysql:host=127.0.0.1;dbname=juiciosorales_test', 'miguel', 'M!gu314ng31');
    echo 'Conexión exitosa a MySQL\n';
} catch (PDOException \$e) {
    echo 'Error: ' . \$e->getMessage() . '\n';
}
"
```

## 🚀 Después de Instalar

```bash
# 1. Instalar dependencias de Composer
cd /home/miguel/Documents/github/juiciosorales
composer install

# 2. Ejecutar migraciones en base de datos de prueba
# (Los tests lo harán automáticamente, pero puedes hacerlo manualmente)
DB_DATABASE=juiciosorales_test DB_USERNAME=miguel DB_PASSWORD='M!gu314ng31' DB_CONNECTION=mysql php artisan migrate

# 3. Ejecutar tests
php artisan test --filter DialogosV2
```

## 📝 Extensiones por Categoría

### Esenciales para Laravel
- `php8.3-xml` - Parsing XML
- `php8.3-dom` - Manipulación DOM
- `php8.3-mysql` - Cliente MySQL
- `php8.3-pdo` - PDO (PHP Data Objects)
- `php8.3-pdo-mysql` - Driver PDO para MySQL ⚠️ **CRÍTICO**

### Recomendadas
- `php8.3-mbstring` - Strings multibyte
- `php8.3-curl` - Cliente HTTP
- `php8.3-zip` - Manipulación de archivos ZIP
- `php8.3-bcmath` - Matemáticas de precisión
- `php8.3-tokenizer` - Tokenización de código
- `php8.3-fileinfo` - Detección de tipo de archivo
- `php8.3-intl` - Internacionalización

## 🔧 Si Aún Hay Problemas

### Reiniciar servicios PHP (si aplica)
```bash
sudo systemctl restart php8.3-fpm
```

### Verificar configuración PHP
```bash
php --ini
php -i | grep -i pdo
php -i | grep -i mysql
```

### Verificar permisos de base de datos
```bash
mysql -u miguel -p'M!gu314ng31' -e "SHOW GRANTS;"
```
