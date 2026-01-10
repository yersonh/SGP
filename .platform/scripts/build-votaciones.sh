#!/bin/bash
set -e

echo "🔨 Construyendo Sistema de Votaciones..."
echo "========================================"

# 1. Instalar herramientas CRÍTICAS
echo "📦 Instalando herramientas del sistema..."
apt-get update && apt-get install -y --no-install-recommends \
    postgresql-client \
    jq \
    curl \
    gettext-base \          # ✅ Contiene envsubst
    nginx \                 # ✅ Asegurar que Nginx esté instalado
    php-fpm \               # ✅ Asegurar que PHP-FPM esté instalado
    && rm -rf /var/lib/apt/lists/*

# 2. Instalar dependencias PHP si existe composer.json
if [ -f "/app/composer.json" ]; then
    echo "📦 Instalando dependencias PHP..."
    composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
else
    echo "⚠️  No se encontró composer.json, omitiendo instalación de dependencias"
fi

# 3. Dar permisos a scripts
echo "🔧 Configurando permisos..."
chmod +x /app/.platform/scripts/*.sh 2>/dev/null || true

# 4. Si es Laravel, optimizar
if [ -f "/app/artisan" ]; then
    echo "⚡ Optimizando Laravel..."
    php artisan config:cache 2>/dev/null || true
    php artisan route:cache 2>/dev/null || true
    php artisan view:cache 2>/dev/null || true
fi

echo "✅ Construcción completada"