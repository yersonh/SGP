<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/file_helper.php';

echo "<h1>Verificación de Consistencia HTTPS</h1>";
echo "<h3>Fecha: " . date('Y-m-d H:i:s') . "</h3>";

echo "<h2>1. Database::getUploadsUrl():</h2>";
$dbUrl = Database::getUploadsUrl();
echo "URL: <strong>" . $dbUrl . "</strong><br>";
echo "Protocolo: " . (strpos($dbUrl, 'https://') === 0 ? '✅ HTTPS' : '❌ HTTP') . "<br>";

echo "<h2>2. FileHelper::getPhotoUrl():</h2>";

// Probar diferentes casos
$testCases = [
    'null' => null,
    'vacío' => '',
    'default.png' => 'default.png',
    'imagendefault.png' => 'imagendefault.png',
    'foto personal' => 'mi_foto.jpg'
];

foreach ($testCases as $desc => $filename) {
    $url = FileHelper::getPhotoUrl($filename);
    echo "<strong>$desc</strong> ($filename):<br>";
    echo "URL: " . $url . "<br>";
    echo "Protocolo: " . (strpos($url, 'https://') === 0 ? '✅ HTTPS' : '❌ HTTP') . "<br><br>";
}

echo "<h2>3. Verificar consistencia:</h2>";
$url1 = Database::getUploadsUrl();
$url2 = FileHelper::getPhotoUrl('mi_foto.jpg');

// Extraer la parte antes de /uploads/
$baseDbUrl = substr($url1, 0, strpos($url1, '/uploads/') + 9); // +9 para incluir '/uploads/'
$baseFileUrl = substr($url2, 0, strpos($url2, '/uploads/') + 9);

if ($baseDbUrl === $baseFileUrl) {
    echo "✅ <strong>CONSISTENTE:</strong> Ambas URLs tienen la misma base<br>";
    echo "Base URL: " . $baseDbUrl . "<br>";
} else {
    echo "❌ <strong>INCONSISTENTE:</strong><br>";
    echo "Database base: " . $baseDbUrl . "<br>";
    echo "FileHelper base: " . $baseFileUrl . "<br>";
}

echo "<h2>4. Prueba de foto por defecto:</h2>";
$defaultUrl = FileHelper::getPhotoUrl(null);
echo "URL: " . $defaultUrl . "<br>";
echo '<a href="' . $defaultUrl . '" target="_blank">Verificar en navegador</a><br>';

echo "<h2>5. Header HTTP_X_FORWARDED_PROTO:</h2>";
echo "Valor: <strong>" . ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'No definido') . "</strong><br>";
?>

<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    h2 { border-bottom: 1px solid #ddd; padding-bottom: 5px; }
</style>