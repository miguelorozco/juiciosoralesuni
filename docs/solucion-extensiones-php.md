# 🔧 Solución: Extensiones PHP Faltantes (ext-dom, ext-xml)

## 🔍 Diagnóstico del Problema

El error ocurre porque:

1. **PHP 8.3.6 está instalado** ✅
2. **Las extensiones `ext-dom` y `ext-xml` NO están instaladas** ❌
3. **Composer requiere estas extensiones** para instalar dependencias como:
   - `phpunit/phpunit` (requiere ext-dom)
   - `laravel/pint` (requiere ext-xml)
   - `laravel/framework` (requiere ext-dom indirectamente)

## ✅ Solución

### Paso 1: Instalar las Extensiones

```bash
sudo apt update
sudo apt install php8.3-xml php8.3-dom
```

### Paso 2: Verificar Instalación

```bash
# Verificar que las extensiones están cargadas
php -m | grep -E "(dom|xml)"

# Deberías ver:
# dom
# xml
```

### Paso 3: Instalar Dependencias de Composer

```bash
cd /home/miguel/Documents/github/juiciosorales
composer install
```

## 📋 Extensiones PHP Completas para Laravel

Si quieres instalar todas las extensiones comúnmente necesarias:

```bash
sudo apt install \
    php8.3-xml \
    php8.3-dom \
    php8.3-mbstring \
    php8.3-curl \
    php8.3-zip \
    php8.3-mysql \
    php8.3-pdo-mysql \
    php8.3-bcmath \
    php8.3-tokenizer \
    php8.3-fileinfo \
    php8.3-intl
```

**⚠️ IMPORTANTE**: Necesitas `php8.3-pdo-mysql` para que Laravel pueda conectarse a MySQL. Sin esta extensión, obtendrás el error `could not find driver`.

## 🔍 Verificar Todas las Extensiones

```bash
# Ver todas las extensiones instaladas
php -m

# Verificar extensiones específicas de Laravel
php -m | grep -E "(dom|xml|mbstring|curl|zip|pdo|mysql|bcmath|tokenizer|fileinfo|intl)"
```

## ⚠️ Si las Extensiones No Aparecen Después de Instalar

1. **Reiniciar el servicio PHP-FPM** (si está corriendo):
   ```bash
   sudo systemctl restart php8.3-fpm
   ```

2. **Verificar archivos de configuración**:
   ```bash
   ls -la /etc/php/8.3/cli/conf.d/ | grep -E "(dom|xml)"
   ```

3. **Verificar que los archivos .ini existen**:
   ```bash
   # Deberían existir archivos como:
   # /etc/php/8.3/cli/conf.d/20-dom.ini
   # /etc/php/8.3/cli/conf.d/20-xml.ini
   ```

## 🚀 Después de Instalar

Una vez instaladas las extensiones:

```bash
# Instalar dependencias
composer install

# Ejecutar tests
php artisan test --filter DialogosV2

# Verificar que todo funciona
php artisan --version
```

## 📝 Notas

- `ext-dom`: Extensión para manipulación de documentos DOM (XML/HTML)
- `ext-xml`: Extensión para parsing de XML
- Ambas son **requeridas** por PHPUnit y Laravel
- Sin estas extensiones, Composer no puede instalar las dependencias
