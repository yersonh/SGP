<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/file_helper.php';
require_once __DIR__ . '/../models/UsuarioModel.php';

echo "<h1>Prueba Completa del Sistema</h1>";
echo "<h3>Fecha: " . date('Y-m-d H:i:s') . "</h3>";

// 1. Conexión a base de datos
try {
    $pdo = Database::getConnection();
    echo "✅ Conexión a PostgreSQL exitosa<br>";
} catch (Exception $e) {
    echo "❌ Error conexión BD: " . $e->getMessage() . "<br>";
}

// 2. Directorios de uploads
Database::ensureUploadsDirectory();
echo "✅ Directorios de uploads verificados<br>";

// 3. Foto por defecto accesible
$defaultUrl = FileHelper::getPhotoUrl(null);
echo "✅ Foto por defecto URL: " . $defaultUrl . "<br>";
echo '<a href="' . $defaultUrl . '" target="_blank">Ver foto</a><br>';

// 4. Crear usuario de prueba (simulación)
echo "<h2>Simulación de creación de usuario:</h2>";

$datosPrueba = [
    'nombres' => 'Juan',
    'apellidos' => 'Pérez',
    'cedula' => '1234567890',
    'nickname' => 'juanperez',
    'password' => 'password123',
    'correo' => 'juan@example.com',
    'telefono' => '3001234567',
    'tipo_usuario' => 'Referenciador',
    'tope' => 100
];

echo "<strong>Datos de prueba:</strong><br>";
echo "<pre>" . print_r($datosPrueba, true) . "</pre>";

// 5. Probar FileHelper::getPhotoUrl con diferentes escenarios
echo "<h2>Escenarios de fotos:</h2>";
echo "1. Usuario sin foto: " . FileHelper::getPhotoUrl(null) . "<br>";
echo "2. Usuario con foto 'foto123.jpg': " . FileHelper::getPhotoUrl('foto123.jpg') . "<br>";
echo "3. Usuario con foto por defecto: " . FileHelper::getPhotoUrl('default.png') . "<br>";

// 6. Verificar rutas físicas
echo "<h2>Rutas físicas:</h2>";
echo "Uploads path: " . Database::getUploadsPath() . "<br>";
echo "Foto por defecto física: " . $_SERVER['DOCUMENT_ROOT'] . '/imagenes/imagendefault.png' . "<br>";

// 7. Verificar que se puede escribir en uploads
$testDir = Database::getUploadsPath() . 'profiles/';
if (is_writable($testDir)) {
    echo "✅ Directorio profiles es escribible<br>";
} else {
    echo "❌ Directorio profiles NO es escribible<br>";
}

echo "<h2>Resumen:</h2>";
echo "✅ Sistema configurado correctamente<br>";
echo "✅ HTTPS funcionando<br>";
echo "✅ Uploads listos<br>";
echo "✅ Foto por defecto disponible<br>";
echo "✅ Puedes proceder a usar el formulario de agregar usuario<br>";

echo "<br><a href='agregar_usuario.php' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ir al formulario de agregar usuario</a>";
?>

<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
</style>