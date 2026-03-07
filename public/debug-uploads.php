<?php
// debug-uploads.php - Diagnóstico completo de uploads
header('Content-Type: text/plain');

echo "=== DIAGNÓSTICO DE UPLOADS ===\n\n";

$upload_dir = '/uploads/profiles/';
echo "Directorio: $upload_dir\n";
echo "Existe: " . (is_dir($upload_dir) ? 'SI' : 'NO') . "\n";
echo "Permisos: " . substr(sprintf('%o', fileperms($upload_dir)), -4) . "\n";
echo "Propietario: " . posix_getpwuid(fileowner($upload_dir))['name'] . "\n\n";

// Listar archivos
echo "=== ARCHIVOS ===\n";
$files = scandir($upload_dir);
foreach ($files as $file) {
    if ($file == '.' || $file == '..') continue;
    $filepath = $upload_dir . $file;
    $perms = substr(sprintf('%o', fileperms($filepath)), -4);
    $owner = posix_getpwuid(fileowner($filepath))['name'];
    echo "$file - Permisos: $perms - Propietario: $owner\n";
}

echo "\n=== PRUEBA DE LECTURA ===\n";
$test_file = '/uploads/nginx-test.txt';
if (file_exists($test_file)) {
    echo "nginx-test.txt existe\n";
    echo "Contenido: " . file_get_contents($test_file) . "\n";
} else {
    echo "nginx-test.txt NO existe\n";
}

echo "\n=== VARIABLES DE ENTORNO ===\n";
echo "RAILWAY_VOLUME_NAME: " . (getenv('RAILWAY_VOLUME_NAME') ?: 'NO DETECTADO') . "\n";
echo "RAILWAY_VOLUME_MOUNT_PATH: " . (getenv('RAILWAY_VOLUME_MOUNT_PATH') ?: 'NO DETECTADO') . "\n";
?>