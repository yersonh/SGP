#!/bin/bash
set -e

echo "🔨 Construyendo Sistema de Votaciones..."

# Instalar SOLO envsubst (lo único que falta)
apt-get update && apt-get install -y gettext-base

# Instalar dependencias PHP si existe composer.json
if [ -f "/app/composer.json" ]; then
    composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
fi

# Dar permisos a scripts
chmod +x /app/.platform/scripts/*.sh

echo "✅ Construcción completada"