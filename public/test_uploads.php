<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/uploads.php';
require_once __DIR__ . '/../helpers/file_helper.php';

echo "<h1>Verificación del Sistema de Uploads</h1>";
echo "<h3>Fecha: " . date('Y-m-d H:i:s') . "</h3>";

echo "<h2>1. Configuración Database</h2>";
echo "getUploadsPath(): <strong>" . Database::getUploadsPath() . "</strong><br>";
echo "getUploadsUrl(): <strong>" . Database::getUploadsUrl() . "</strong><br>";

echo "<h2>2. Configuración Uploads</h2>";
$config = require __DIR__ . '/../config/uploads.php';
echo "default_photo: <strong>" . $config['default_photo'] . "</strong><br>";

echo "<h2>3. FileHelper::getPhotoUrl()</h2>";
echo "Foto null: " . FileHelper::getPhotoUrl(null) . "<br>";
echo "Foto vacía: " . FileHelper::getPhotoUrl('') . "<br>";
echo "Foto 'default.png': " . FileHelper::getPhotoUrl('default.png') . "<br>";
echo "Foto 'imagendefault.png': " . FileHelper::getPhotoUrl('imagendefault.png') . "<br>";
echo "Foto 'mi_foto.jpg': " . FileHelper::getPhotoUrl('mi_foto.jpg') . "<br>";

echo "<h2>4. Verificación de archivos</h2>";
$defaultPath = $_SERVER['DOCUMENT_ROOT'] . $config['default_photo'];
echo "Ruta física foto por defecto: " . $defaultPath . "<br>";
echo "¿Existe?: " . (file_exists($defaultPath) ? '<span style="color:green">✅ Sí</span>' : '<span style="color:red">❌ No</span>') . "<br>";

if (file_exists($defaultPath)) {
    echo "Tamaño: " . filesize($defaultPath) . " bytes<br>";
    echo "Tipo: " . mime_content_type($defaultPath) . "<br>";
}

echo "<h2>5. Directorios de uploads</h2>";
Database::ensureUploadsDirectory();
echo "✅ Directorios de uploads verificados/creados<br>";

echo "<h2>6. Links de prueba</h2>";
echo "<a href='" . FileHelper::getPhotoUrl(null) . "' target='_blank'>Ver foto por defecto</a><br>";
echo "<a href='" . Database::getUploadsUrl() . "profiles/' target='_blank'>Ver directorio de perfiles</a><br>";

echo "<h2>7. Información del servidor</h2>";
echo "Document root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'No definido') . "<br>";
echo "HTTPS: " . (isset($_SERVER['HTTPS']) ? 'Sí' : 'No') . "<br>";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'No definido') . "<br>";
echo "PHP Version: " . PHP_VERSION . "<br>";
?>

<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    h1 { color: #333; }
    h2 { color: #555; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
    h3 { color: #777; }
</style>