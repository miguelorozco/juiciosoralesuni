-- Crear base de datos juiciosorales
CREATE DATABASE IF NOT EXISTS juiciosorales CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Crear usuario juiciosorales_user
CREATE USER IF NOT EXISTS 'juiciosorales_user'@'localhost' IDENTIFIED BY 'jO#9mK$2pL!5xR@8vN';

-- Otorgar todos los privilegios en la base de datos juiciosorales
GRANT ALL PRIVILEGES ON juiciosorales.* TO 'juiciosorales_user'@'localhost';

-- Aplicar los cambios
FLUSH PRIVILEGES;

-- Mostrar confirmación
SELECT 'Base de datos y usuario creados exitosamente' AS status;
