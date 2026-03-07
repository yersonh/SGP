<?php

// Configuración
$upload_dir = '/uploads/profiles/';
$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$cache_max_age = 86400; // 24 horas en segundos

// Obtener el nombre del archivo de forma segura
$file = $_GET['file'] ?? '';
if (empty($file)) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    die('Error: No se especificó el archivo');
}

// Seguridad: eliminar cualquier intento de path traversal
$file = basename($file); // ¡Esto es CRÍTICO! Elimina cualquier "../" o "/"

// Validar que el nombre del archivo no esté vacío después de basename
if (empty($file)) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    die('Error: Nombre de archivo inválido');
}

// Validar extensión
$extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
if (!in_array($extension, $allowed_extensions)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    die('Error: Tipo de archivo no permitido');
}

// Construir ruta completa
$filepath = $upload_dir . $file;

// Verificar que el archivo existe
if (!file_exists($filepath)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    die('Error: El archivo no existe');
}

// Verificar que se puede leer
if (!is_readable($filepath)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    die('Error: No se puede leer el archivo');
}

// Determinar el tipo MIME
$content_types = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'webp' => 'image/webp'
];

// Obtener tamaño del archivo
$file_size = filesize($filepath);
if ($file_size === false) {
    http_response_code(500);
    die('Error al obtener tamaño del archivo');
}

// Enviar headers
header('Content-Type: ' . $content_types[$extension]);
header('Content-Length: ' . $file_size);
header('Content-Disposition: inline; filename="' . $file . '"');
header('Cache-Control: public, max-age=' . $cache_max_age);
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cache_max_age) . ' GMT');
header('Pragma: cache');

// Para imágenes, también podemos enviar ETag para mejor caching
$etag = md5_file($filepath);
if ($etag !== false) {
    header('ETag: "' . $etag . '"');
    
    // Verificar If-None-Match para usar caché del navegador
    if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH'], '"') === $etag) {
        http_response_code(304);
        exit;
    }
}

// Leer y enviar el archivo por partes para mejor manejo de memoria
$handle = fopen($filepath, 'rb');
if ($handle === false) {
    http_response_code(500);
    die('Error al abrir el archivo');
}

// Limpiar buffers de salida
if (ob_get_level()) {
    ob_end_clean();
}

// Enviar el archivo en chunks de 1MB
while (!feof($handle)) {
    $buffer = fread($handle, 1048576); // 1MB
    echo $buffer;
    flush();
}

fclose($handle);
exit;
?>