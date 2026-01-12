#!/bin/bash

# Crear directorio de uploads si no existe
UPLOADS_DIR="/uploads"
if [ ! -d "$UPLOADS_DIR" ]; then
    echo "🚀 Creando directorio de uploads..."
    mkdir -p "$UPLOADS_DIR/profiles"
    mkdir -p "$UPLOADS_DIR/temp"
    chmod -R 755 "$UPLOADS_DIR"
    echo "✅ Directorio de uploads creado en $UPLOADS_DIR"
fi

# Copiar foto por defecto si no existe
DEFAULT_PHOTO="$UPLOADS_DIR/profiles/default.png"
if [ ! -f "$DEFAULT_PHOTO" ]; then
    echo "🚀 Creando foto por defecto..."
    # Crear una imagen por defecto simple con ImageMagick o base64
    if command -v convert &> /dev/null; then
        convert -size 150x150 xc:#2c3e50 -pointsize 20 -fill white -gravity center -draw "text 0,0 'USER'" "$DEFAULT_PHOTO"
    else
        # Si no hay ImageMagick, crear un archivo vacío o copiar desde assets
        echo "⚠️  ImageMagick no encontrado, creando placeholder..."
        cp /app/public/default-profile.png "$DEFAULT_PHOTO" 2>/dev/null || true
    fi
    echo "✅ Foto por defecto creada"
fi

# Verificar permisos
if [ ! -w "$UPLOADS_DIR" ]; then
    echo "⚠️  Advertencia: Directorio de uploads no tiene permisos de escritura"
    echo "📝 Intentando cambiar permisos..."
    chmod -R 755 "$UPLOADS_DIR" || true
fi

echo "🚀 Iniciando servidor PHP..."
php -S 0.0.0.0:${PORT:-3000} index.php