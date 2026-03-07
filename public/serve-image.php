<?php
// serve-image.php - Sirve imágenes del volumen cuando el servidor built-in no puede
$image = $_GET['file'] ?? '';
if (!$image) {
    http_response_code(400);
    die('No file specified');
}

// Seguridad: evitar path traversal
$image = basename($image);
$filepath = '/uploads/profiles/' . $image;

if (!file_exists($filepath)) {
    http_response_code(404);
    die('File not found');
}

// Determinar content type
$ext = pathinfo($filepath, PATHINFO_EXTENSION);
$content_types = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
    'txt' => 'text/plain'
];

$content_type = $content_types[strtolower($ext)] ?? 'application/octet-stream';

// Servir el archivo
header('Content-Type: ' . $content_type);
header('Content-Length: ' . filesize($filepath));
readfile($filepath);
exit;