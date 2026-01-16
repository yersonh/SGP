#!/bin/bash
set -e

echo "🔨 Construyendo Sistema de Votaciones..."

# 1. ACTUALIZAR e instalar PHP con extensiones necesarias
apt-get update
apt-get install -y \
    php-fpm \
    php-cli \
    php-pgsql \
    php-gd \
    php-mbstring \
    php-xml \
    php-zip \
    php-curl \
    gettext-base

# 2. Verificar instalación de extensiones
echo "✅ Verificando instalaciones:"
php --version
echo "Extensiones instaladas:"
php -m | grep -E "(gd|pgsql|mbstring|xml|zip)"

# 3. Configurar permisos para directorio de sesiones
echo "📁 Configurando directorio de sesiones..."
mkdir -p /tmp/php_sessions_votaciones
chmod 777 /tmp/php_sessions_votaciones

# 4. Instalar dependencias PHP si existe composer.json
if [ -f "/app/composer.json" ]; then
    echo "📦 Instalando dependencias PHP..."
    composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
fi

# 5. Dar permisos a scripts
chmod +x /app/.platform/scripts/*.sh

echo "✅ Construcción completada"