#!/bin/bash
set -e

echo "🔨 Construyendo Sistema de Votaciones..."

# 1. ACTUALIZAR e instalar PHP-FPM específicamente
apt-get update
apt-get install -y php-fpm php-cli php-pgsql gettext-base

# 2. Verificar instalación
echo "✅ Verificando instalaciones:"
php --version
which php-fpm || echo "php-fpm no encontrado"

# 3. Instalar dependencias PHP si existe composer.json
if [ -f "/app/composer.json" ]; then
    echo "📦 Instalando dependencias PHP..."
    composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
fi

# 4. Dar permisos a scripts
chmod +x /app/.platform/scripts/*.sh

echo "✅ Construcción completada"