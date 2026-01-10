#!/bin/bash
set -e

echo "🔨 Construyendo Sistema de Votaciones..."
echo "========================================"

# Si existe composer.json, instalar dependencias
if [ -f "/app/composer.json" ]; then
    echo "📦 Instalando dependencias PHP..."
    composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist || echo "⚠️  Sin composer.json, continuando..."
fi

# Si es Laravel, optimizar cache
if [ -f "/app/artisan" ]; then
    echo "⚡ Optimizando Laravel..."
    php artisan config:cache 2>/dev/null || true
    php artisan route:cache 2>/dev/null || true
    php artisan view:cache 2>/dev/null || true
fi

echo "✅ Construcción completada"