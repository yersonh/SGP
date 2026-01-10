#!/bin/bash
set -e

echo "🚀 Iniciando Sistema de Votaciones..."

# 1. Configurar Nginx
echo "🌐 Configurando Nginx..."
if command -v envsubst &> /dev/null; then
    envsubst '\$PORT' < /app/.platform/nginx/nginx-votaciones.conf > /etc/nginx/nginx.conf
else
    sed "s/\${PORT}/$PORT/g" /app/.platform/nginx/nginx-votaciones.conf > /etc/nginx/nginx.conf
fi

# 2. Validar Nginx
nginx -t

# 3. Buscar php-fpm en diferentes ubicaciones
echo "🐘 Buscando PHP-FPM..."
PHP_FPM_CMD=""

# Intentar diferentes ubicaciones comunes
if [ -f "/usr/sbin/php-fpm8.2" ]; then
    PHP_FPM_CMD="/usr/sbin/php-fpm8.2"
elif [ -f "/usr/sbin/php-fpm8.1" ]; then
    PHP_FPM_CMD="/usr/sbin/php-fpm8.1"
elif [ -f "/usr/sbin/php-fpm8.0" ]; then
    PHP_FPM_CMD="/usr/sbin/php-fpm8.0"
elif [ -f "/usr/sbin/php-fpm" ]; then
    PHP_FPM_CMD="/usr/sbin/php-fpm"
elif command -v php-fpm &> /dev/null; then
    PHP_FPM_CMD="php-fpm"
else
    echo "⚠️  PHP-FPM no encontrado, usando PHP built-in server..."
    # Fallback: usar PHP built-in server
    php -S 0.0.0.0:$PORT -t /app/public &
    sleep 2
    echo "✅ PHP built-in server iniciado en puerto: $PORT"
    wait
    exit 0
fi

# 4. Iniciar PHP-FPM
echo "🚀 Iniciando PHP-FPM: $PHP_FPM_CMD"
$PHP_FPM_CMD --daemonize

# 5. Iniciar Nginx
echo "🌐 Iniciando Nginx..."
echo "✅ Sistema listo en puerto: $PORT"
exec nginx -g 'daemon off;'