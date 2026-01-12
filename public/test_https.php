<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/file_helper.php';

echo "<h1>Verificación HTTPS en Railway</h1>";
echo "<h3>Fecha: " . date('Y-m-d H:i:s') . "</h3>";

echo "<h2>Headers del servidor:</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Header</th><th>Valor</th></tr>";

$headers = [
    'HTTPS',
    'HTTP_X_FORWARDED_PROTO',
    'HTTP_X_FORWARDED_SSL',
    'HTTP_X_FORWARDED_PORT',
    'SERVER_PORT',
    'REQUEST_SCHEME',
    'HTTP_HOST',
    'HTTP_X_FORWARDED_FOR'
];

foreach ($headers as $header) {
    $value = $_SERVER[$header] ?? 'No definido';
    echo "<tr><td>$header</td><td>$value</td></tr>";
}
echo "</table>";

echo "<h2>URLs generadas:</h2>";
echo "Database::getUploadsUrl(): <strong>" . Database::getUploadsUrl() . "</strong><br>";

echo "<h2>Prueba FileHelper:</h2>";
echo "Foto null: " . FileHelper::getPhotoUrl(null) . "<br>";
echo "Foto 'mi_foto.jpg': " . FileHelper::getPhotoUrl('mi_foto.jpg') . "<br>";

echo "<h2>Detalles de Railway:</h2>";
echo "¿Usando HTTPS?: <strong>" . (self::isHttps() ? '✅ Sí' : '❌ No') . "</strong><br>";
echo "Protocolo actual: <strong>" . self::getCurrentProtocol() . "</strong><br>";

// Funciones auxiliares
function isHttps() {
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        return true;
    }
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
        return true;
    }
    if (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
        return true;
    }
    return false;
}

function getCurrentProtocol() {
    return isHttps() ? 'https' : 'http';
}
?>

<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    table { border-collapse: collapse; margin: 10px 0; }
    th { background: #f0f0f0; padding: 8px; }
    td { padding: 8px; }
</style>