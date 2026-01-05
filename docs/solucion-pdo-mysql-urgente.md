# 🚨 Solución URGENTE: Error "could not find driver" en Tests

## ❌ Error Actual

```
could not find driver (Connection: mysql, SQL: select exists...)
```

## 🔍 Diagnóstico

El error indica que **falta la extensión `php8.3-pdo-mysql`**. Esta es la extensión que permite a PHP conectarse a MySQL usando PDO.

## ✅ Solución INMEDIATA

Ejecuta estos comandos **en tu terminal** (requiere sudo):

```bash
# 1. Instalar la extensión PDO MySQL
sudo apt install php8.3-pdo-mysql

# 2. Verificar que se instaló
php -m | grep pdo_mysql

# Deberías ver: pdo_mysql
```

## 🔧 Si Aún No Funciona

### Verificar que el archivo .ini existe:

```bash
ls -la /etc/php/8.3/cli/conf.d/ | grep pdo_mysql
```

Deberías ver algo como: `20-pdo_mysql.ini`

### Si no existe, crear el enlace simbólico:

```bash
# Verificar dónde está instalado
dpkg -L php8.3-pdo-mysql | grep ini

# Si existe, crear enlace (ajustar ruta según salida anterior)
sudo ln -s /usr/lib/php/*/pdo_mysql.so /etc/php/8.3/cli/conf.d/20-pdo_mysql.ini
```

### Reiniciar PHP (si aplica):

```bash
sudo systemctl restart php8.3-fpm  # Si usas PHP-FPM
```

## 🧪 Probar Conexión

```bash
# Probar que PHP puede conectarse a MySQL
php -r "
try {
    \$pdo = new PDO('mysql:host=127.0.0.1;dbname=juiciosorales_test', 'miguel', 'M!gu314ng31');
    echo '✅ Conexión exitosa a MySQL\n';
} catch (PDOException \$e) {
    echo '❌ Error: ' . \$e->getMessage() . '\n';
}
"
```

## 📋 Instalación Completa de Extensiones

Si quieres instalar todas las extensiones de una vez:

```bash
sudo apt update
sudo apt install \
    php8.3-xml \
    php8.3-dom \
    php8.3-pdo-mysql \
    php8.3-mysql \
    php8.3-mbstring \
    php8.3-curl \
    php8.3-zip \
    php8.3-bcmath \
    php8.3-tokenizer \
    php8.3-fileinfo
```

## ✅ Verificar Instalación Completa

```bash
# Verificar todas las extensiones necesarias
php -m | grep -E "(pdo_mysql|mysql|xml|dom|mbstring|curl|zip|bcmath|tokenizer|fileinfo)"

# Deberías ver todas estas:
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
```

## 🚀 Después de Instalar

```bash
# Ejecutar tests nuevamente
php artisan test --filter DialogosV2
```

## 📝 Nota Importante

**El error "could not find driver" SOLO se soluciona instalando `php8.3-pdo-mysql`**. 

Sin esta extensión, PHP no puede conectarse a MySQL, incluso si:
- ✅ MySQL está corriendo
- ✅ La base de datos existe
- ✅ Las credenciales son correctas
- ✅ PDO está instalado

**PDO y pdo_mysql son diferentes:**
- `PDO` = Interfaz genérica de acceso a datos
- `pdo_mysql` = Driver específico para MySQL (ES LO QUE FALTA)
