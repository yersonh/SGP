#!/bin/bash
set -euo pipefail

echo "🗳️  Iniciando Sistema de Votaciones Gubernamentales..."
echo "====================================================="

# 🔐 Crear directorios seguros
mkdir -p /var/log/nginx /var/log/php /var/log/php-fpm \
         /tmp/php_sessions_votaciones /tmp/php_uploads_votaciones

chmod 750 /var/log/nginx /var/log/php /var/log/php-fpm
chmod 770 /tmp/php_sessions_votaciones
chmod 770 /tmp/php_uploads_votaciones
chown -R www-data:www-data /tmp/php_sessions_votaciones /tmp/php_uploads_votaciones 2>/dev/null || true

# 📁 Permisos para la aplicación
if [ -d "/app/storage" ]; then
    chmod -R 750 /app/storage 2>/dev/null || true
    chown -R www-data:www-data /app/storage 2>/dev/null || true
fi

if [ -d "/app/bootstrap/cache" ]; then
    chmod -R 750 /app/bootstrap/cache 2>/dev/null || true
fi

# 🌐 Configurar Nginx con variables de entorno
echo "🌐 Configurando Nginx..."
# Opción 1: Usar envsubst si está disponible
if command -v envsubst &> /dev/null; then
    echo "✅ Usando envsubst..."
    envsubst '\$PORT' < /app/.platform/nginx/nginx-votaciones.conf > /etc/nginx/nginx.conf
else
    echo "⚠️  envsubst no encontrado, usando sed..."
    # Opción 2: Usar sed como fallback
    sed "s/\${PORT}/$PORT/g" /app/.platform/nginx/nginx-votaciones.conf > /etc/nginx/nginx.conf
fi

# ✅ Validar configuración Nginx
echo "🔍 Validando configuración Nginx..."
nginx -t || exit 1

# 🚀 Iniciar PHP-FPM
echo "🚀 Iniciando PHP-FPM..."
if [ -f "/app/.platform/php/php-votaciones.ini" ]; then
    php-fpm --daemonize --fpm-config /app/.platform/php/php-votaciones.ini
else
    php-fpm --daemonize
fi

# Esperar que PHP-FPM esté listo
sleep 2

echo "🌐 Iniciando Nginx..."
echo "✅ Sistema listo en puerto: $PORT"
echo "📊 Health check: http://localhost:$PORT/health"
echo "🔒 Modo: PRODUCCIÓN - VOTACIONES GUBERNAMENTALES"

# Auditoría de inicio
echo "$(date '+%Y-%m-%d %H:%M:%S') - Sistema de votaciones iniciado - Puerto: $PORT" >> /var/log/votaciones-audit.log 2>/dev/null || true

# Ejecutar Nginx en primer plano
exec nginx -g 'daemon off;'