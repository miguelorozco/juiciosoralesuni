# 📦 Instalación de Dependencias - Laravel

## Problema

El error `Failed to open stream: No such file or directory in vendor/autoload.php` indica que las dependencias de Composer no están instaladas.

## Solución

### Opción 1: Instalar Composer (Recomendado)

```bash
# Instalar Composer desde el repositorio de Ubuntu
sudo apt update
sudo apt install composer

# Verificar instalación
composer --version
```

### Opción 2: Instalar Composer Manualmente (Última versión)

```bash
# Descargar e instalar Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Verificar instalación
composer --version
```

## Instalar Dependencias del Proyecto

Una vez instalado Composer, ejecutar:

```bash
cd /home/miguel/Documents/github/juiciosorales

# Instalar dependencias
composer install

# O si prefieres instalar sin dependencias de desarrollo
composer install --no-dev
```

## Verificar Instalación

```bash
# Verificar que vendor/ existe
ls -la vendor/

# Verificar que autoload.php existe
ls -la vendor/autoload.php

# Ejecutar tests
php artisan test --filter DialogosV2
```

## Instalar Extensiones PHP Requeridas

Si obtienes errores sobre extensiones faltantes (`ext-dom`, `ext-xml`), instálalas:

```bash
# Para PHP 8.3 (verificar versión con: php -v)
sudo apt install php8.3-xml php8.3-dom

# O para la versión de PHP que tengas instalada
# Reemplaza 8.3 con tu versión (8.1, 8.2, etc.)
sudo apt install php-xml php-dom

# Verificar que las extensiones están instaladas
php -m | grep -E "(dom|xml)"
```

### Extensiones PHP Necesarias para Laravel

```bash
# Instalar todas las extensiones comúnmente requeridas
sudo apt install \
    php-xml \
    php-dom \
    php-mbstring \
    php-curl \
    php-zip \
    php-pdo \
    php-mysql \
    php-bcmath \
    php-tokenizer \
    php-json \
    php-fileinfo
```

## Solución Rápida

Si necesitas instalar dependencias rápidamente sin las extensiones (no recomendado para producción):

```bash
composer install --ignore-platform-req=ext-dom --ignore-platform-req=ext-xml
```

**Nota**: Esto solo es temporal. Las extensiones son necesarias para que Laravel y PHPUnit funcionen correctamente.

## Notas

- El archivo `composer.json` existe y está correcto
- El directorio `vendor/` se creará automáticamente al ejecutar `composer install`
- Las dependencias incluyen Laravel, PHPUnit para tests, y otras librerías necesarias
- Las extensiones `ext-dom` y `ext-xml` son requeridas por PHPUnit y Laravel