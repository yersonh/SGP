#!/bin/bash
set -e

echo "🚀 Iniciando Sistema de Votaciones..."

# =========================
# 1. CREAR DIRECTORIO DE UPLOADS
# =========================
UPLOADS_DIR="/uploads"
echo "📁 Configurando directorio de uploads..."

if [ ! -d "$UPLOADS_DIR" ]; then
    echo "  🚀 Creando directorio de uploads..."
    mkdir -p "$UPLOADS_DIR/profiles"
    mkdir -p "$UPLOADS_DIR/temp"
    chmod -R 755 "$UPLOADS_DIR"
    echo "  ✅ Directorio de uploads creado en $UPLOADS_DIR"
fi

# Copiar foto por defecto si existe
if [ -f "/app/public/default-profile.png" ] && [ ! -f "$UPLOADS_DIR/profiles/default.png" ]; then
    echo "  📸 Copiando foto por defecto..."
    cp "/app/public/default-profile.png" "$UPLOADS_DIR/profiles/default.png"
    echo "  ✅ Foto por defecto copiada"
fi

# =========================
# 2. CONFIGURAR NGINX
# =========================
echo "🌐 Configurando Nginx..."

if command -v envsubst &> /dev/null; then
    envsubst '\$PORT' < /app/.platform/nginx/nginx-votaciones.conf > /etc/nginx/nginx.conf
else
    sed "s/\${PORT}/$PORT/g" /app/.platform/nginx/nginx-votaciones.conf > /etc/nginx/nginx.conf
fi

# =========================
# 3. VALIDAR NGINX
# =========================
nginx -t

# =========================
# 4. BUSCAR PHP-FPM
# =========================
echo "🐘 Buscando PHP-FPM..."
PHP_FPM_CMD=""

if command -v php-fpm8.2 &> /dev/null; then
    PHP_FPM_CMD="php-fpm8.2"
elif command -v php-fpm8.1 &> /dev/null; then
    PHP_FPM_CMD="php-fpm8.1"
elif command -v php-fpm8.0 &> /dev/null; then
    PHP_FPM_CMD="php-fpm8.0"
elif command -v php-fpm &> /dev/null; then
    PHP_FPM_CMD="php-fpm"
fi

# =========================
# 5. SI NO EXISTE PHP-FPM → FALLBACK REAL
# =========================
if [ -z "$PHP_FPM_CMD" ]; then
    echo "❌ PHP-FPM NO está instalado en la imagen."
    echo "⚠️  Fallback: iniciando PHP built-in server (solo para evitar crash)..."

    php -S 0.0.0.0:$PORT -t /app/public &
    sleep 2

    echo "✅ PHP built-in server iniciado en puerto: $PORT"
    wait
    exit 0
fi

# =========================
# 6. INICIAR PHP-FPM FORZANDO PUERTO 9000
# =========================
echo "🚀 Iniciando PHP-FPM: $PHP_FPM_CMD"

$PHP_FPM_CMD \
  --nodaemonize \
  --fpm-config /etc/php/8.2/fpm/php-fpm.conf &

sleep 2

# Verificación rápida
if ! pgrep -f "$PHP_FPM_CMD" > /dev/null; then
    echo "❌ PHP-FPM falló al iniciar."
    exit 1
fi

echo "✅ PHP-FPM activo y escuchando en 127.0.0.1:9000"

# =========================
# 7. INICIAR NGINX
# =========================
echo "🌐 Iniciando Nginx..."
echo "✅ Sistema listo en puerto: $PORT"

exec nginx -g 'daemon off;'
