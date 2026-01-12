<?php
require_once __DIR__ . '/../config/database.php';

echo "<pre>";
echo "=== PRUEBA DE VOLUMEN EN RAILWAY ===\n\n";

// 1. Verificar database.php
echo "1. Database methods:\n";
echo "   getUploadsPath(): " . Database::getUploadsPath() . "\n";
echo "   getUploadsUrl(): " . Database::getUploadsUrl() . "\n";

// 2. Verificar directorio /uploads
echo "\n2. Directorio /uploads:\n";
$uploadsPath = Database::getUploadsPath();
if (is_dir($uploadsPath)) {
    echo "   ✅ Existe: $uploadsPath\n";
    echo "   📝 Permisos: " . substr(sprintf('%o', fileperms($uploadsPath)), -4) . "\n";
    echo "   📊 Espacio: " . round(disk_free_space($uploadsPath) / (1024*1024), 2) . " MB libre\n";
    
    // Listar contenido
    echo "   📁 Contenido:\n";
    foreach (scandir($uploadsPath) as $item) {
        if ($item !== '.' && $item !== '..') {
            $fullPath = $uploadsPath . $item;
            $type = is_dir($fullPath) ? '📁' : '📄';
            echo "      $type $item\n";
        }
    }
} else {
    echo "   ❌ No existe: $uploadsPath\n";
}

// 3. Crear archivo de prueba
echo "\n3. Prueba de escritura:\n";
$testFile = $uploadsPath . 'test_' . time() . '.txt';
$content = "Prueba de Railway Volume - " . date('Y-m-d H:i:s');

if (file_put_contents($testFile, $content)) {
    echo "   ✅ Archivo creado: " . basename($testFile) . "\n";
    echo "   📄 Contenido: " . file_get_contents($testFile) . "\n";
    
    // Verificar URL
    $testUrl = Database::getUploadsUrl() . basename($testFile);
    echo "   🌐 URL de prueba: $testUrl\n";
    echo "   🔗 <a href='$testUrl' target='_blank'>Abrir archivo</a>\n";
} else {
    echo "   ❌ No se pudo crear archivo\n";
    echo "   📝 Error: " . error_get_last()['message'] . "\n";
}

// 4. Crear estructura de directorios
echo "\n4. Creando directorios:\n";
Database::ensureUploadsDirectory();
echo "   ✅ Directorios creados/verificados\n";

echo "\n=== FIN DE PRUEBA ===\n";
echo "</pre>";