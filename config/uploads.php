<?php
// Configuración de uploads
return [
    // Tipos de archivos permitidos
    'allowed_types' => [
        'image/jpeg',
        'image/jpg', 
        'image/png',
        'image/gif',
        'image/webp'
    ],
    
    // Extensiones permitidas
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
    
    // Tamaño máximo en bytes (5MB)
    'max_size' => 5 * 1024 * 1024,
    
    // Calidad de compresión para JPEG (0-100)
    'jpeg_quality' => 85,
    
    // Ancho máximo para redimensionar
    'max_width' => 1200,
    
    // Alto máximo para redimensionar
    'max_height' => 1200,
    
    // Rutas
    'profiles_path' => 'profiles/',
    'temp_path' => 'temp/',
    
    // 🔥 CORRECCIÓN: Ruta absoluta desde la raíz web
    'default_photo' => '/imagenes/imagendefault.png'
];