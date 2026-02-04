#!/bin/bash

# Script para configurar Apache con el proyecto JuiciosOralesUni
# Este script debe ejecutarse con sudo

echo "==================================================="
echo "Configurando Apache para JuiciosOralesUni"
echo "==================================================="

# 1. Copiar el archivo de configuración VirtualHost
echo "1. Copiando configuración VirtualHost..."
sudo cp juiciosoralesuni-vhost.conf /etc/apache2/other/juiciosoralesuni.conf

# 2. Habilitar mod_rewrite
echo "2. Habilitando mod_rewrite..."
sudo sed -i.bak 's/#LoadModule rewrite_module/LoadModule rewrite_module/' /etc/apache2/httpd.conf

# 3. Habilitar mod_php
echo "3. Verificando mod_php..."
if ! grep -q "^LoadModule php_module" /etc/apache2/httpd.conf; then
    echo "   Habilitando PHP..."
    sudo sed -i.bak 's/#LoadModule php_module/LoadModule php_module/' /etc/apache2/httpd.conf
fi

# 4. Dar permisos al directorio público
echo "4. Configurando permisos..."
chmod -R 755 /Users/miguel/Local/Github/juiciosoralesuni/public
chmod -R 775 /Users/miguel/Local/Github/juiciosoralesuni/storage
chmod -R 775 /Users/miguel/Local/Github/juiciosoralesuni/bootstrap/cache

# 5. Verificar la configuración de Apache
echo "5. Verificando configuración..."
sudo apachectl configtest

# 6. Reiniciar Apache
echo "6. Reiniciando Apache..."
sudo apachectl restart

echo ""
echo "==================================================="
echo "✅ Configuración completada!"
echo "==================================================="
echo ""
echo "Ahora puedes acceder al proyecto en: http://localhost"
echo ""
echo "Para verificar que Apache está corriendo:"
echo "  sudo apachectl status"
echo ""
echo "Para ver los logs:"
echo "  tail -f /var/log/apache2/juiciosoralesuni-error.log"
echo "  tail -f /var/log/apache2/juiciosoralesuni-access.log"
echo ""
