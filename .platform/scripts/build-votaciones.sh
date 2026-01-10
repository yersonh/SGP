#!/bin/bash
set -e

echo "🔨 Construyendo Sistema de Votaciones..."

# 1. Instalar PHP-FPM y herramientas necesarias
apt-get update && apt-get install -y \
    php-fpm \
    php-pgsql \
    gettext-base

# 2. Instalar dependencias PHP si existe composer.json
if [ -f "/app/composer.json" ]; then
    composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
fi

# 3. Dar permisos a scripts
chmod +x /app/.platform/scripts/*.sh

echo "✅ Construcción completada"