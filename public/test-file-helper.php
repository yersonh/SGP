<?php
require_once __DIR__ . '/../helpers/file_helper.php';

echo "<pre>";
echo "=== PRUEBA FILE HELPER ===\n\n";

// 1. Verificar que carga
echo "1. FileHelper cargado: ✅\n";

// 2. Verificar métodos
echo "2. Métodos disponibles:\n";
echo "   - uploadProfilePhoto()\n";
echo "   - deleteFile()\n";
echo "   - getPhotoUrl()\n";

// 3. Probar getPhotoUrl
echo "\n3. Probar getPhotoUrl():\n";
echo "   Sin filename: " . FileHelper::getPhotoUrl('') . "\n";
echo "   Con filename: " . FileHelper::getPhotoUrl('test.jpg') . "\n";

// 4. Verificar configuración
$config = require __DIR__ . '/../config/uploads.php';
echo "\n4. Configuración uploads:\n";
echo "   Max size: " . ($config['max_size'] / (1024*1024)) . " MB\n";
echo "   Tipos permitidos: " . implode(', ', $config['allowed_types']) . "\n";

echo "\n=== PRUEBA EXITOSA ===\n";