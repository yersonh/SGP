<?php
// test-upload.php - Archivo de prueba para verificar el volumen persistente

$upload_dir = '/uploads/profiles/';
$base_url = '/uploads/profiles/';

// Crear directorio si no existe
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// ✅ PRG: Procesar POST y luego REDIRIGIR (evita re-envío al refrescar)
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['imagen'])) {
    $file = $_FILES['imagen'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $allowed)) {
            $status = 'error';
            $msg = 'Tipo de archivo no permitido';
        } else {
            $filename = basename($file['name']);
            $filepath = $upload_dir . $filename;

            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                $status = 'ok';
                $msg = 'Imagen guardada en: ' . $filepath;
            } else {
                $status = 'error';
                $msg = 'Error al guardar. ¿Permisos de escritura?';
            }
        }
    } else {
        $status = 'error';
        $msg = 'Error en subida: código ' . $file['error'];
    }

    // ✅ PRG: redirigir para evitar re-POST al refrescar
    header('Location: /test-upload.php?status=' . $status . '&msg=' . urlencode($msg));
    exit;
}

// Leer mensaje de la URL (después del redirect)
if (isset($_GET['status'])) {
    $message = [
        'type' => $_GET['status'],
        'text' => $_GET['msg'] ?? ''
    ];
}

// Obtener lista de archivos reales en disco
$images = [];
if (is_dir($upload_dir)) {
    $files = scandir($upload_dir);
    foreach ($files as $f) {
        if ($f !== '.' && $f !== '..' && !is_dir($upload_dir . $f)) {
            $images[] = $f;
        }
    }
}

// Diagnóstico del volumen
$test_write_path = '/uploads/profiles/_test_write.txt';
$can_write = @file_put_contents($test_write_path, 'test ' . date('Y-m-d H:i:s')) !== false;
if ($can_write) @unlink($test_write_path);

$diag = [
    'RAILWAY_VOLUME_NAME'       => getenv('RAILWAY_VOLUME_NAME') ?: '❌ No detectado',
    'RAILWAY_VOLUME_MOUNT_PATH' => getenv('RAILWAY_VOLUME_MOUNT_PATH') ?: '❌ No detectado',
    '/uploads existe'           => is_dir('/uploads') ? '✅ Sí' : '❌ No',
    '/uploads/profiles existe'  => is_dir('/uploads/profiles') ? '✅ Sí' : '❌ No',
    'Es escribible'             => $can_write ? '✅ Sí' : '❌ No',
    'Ruta real'                 => realpath($upload_dir) ?: '(no resuelta) ' . $upload_dir,
    'Archivos en disco'         => count($images),
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Volumen Persistente</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 850px; margin: 0 auto; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        td, th { border: 1px solid #ddd; padding: 8px 12px; text-align: left; }
        th { background: #f0f0f0; }
        .ok   { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .error{ background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 10px; margin-top: 15px; }
        .gallery-item { border: 1px solid #ddd; padding: 8px; text-align: center; border-radius: 4px; }
        .gallery-item img { max-width: 100%; max-height: 140px; object-fit: cover; }
        .gallery-item a { display: block; margin-top: 5px; font-size: 12px; word-break: break-all; color: #0066cc; }
        form { margin: 20px 0; padding: 20px; background: #f9f9f9; border-radius: 5px; }
        button { background: #0066cc; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #0052a3; }
        .file-list { font-size: 13px; background: #f5f5f5; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>🧪 Test de Volumen Persistente</h1>

    <!-- DIAGNÓSTICO -->
    <h3>📊 Diagnóstico del volumen</h3>
    <table>
        <tr><th>Variable</th><th>Valor</th></tr>
        <?php foreach ($diag as $key => $val): ?>
        <tr><td><?= htmlspecialchars($key) ?></td><td><?= htmlspecialchars($val) ?></td></tr>
        <?php endforeach; ?>
    </table>

    <!-- ARCHIVOS EN DISCO (lista real) -->
    <h3>📋 Archivos reales en <code>/uploads/profiles/</code></h3>
    <div class="file-list">
        <?php if (empty($images)): ?>
            <em>Ninguno todavía.</em>
        <?php else: ?>
            <?php foreach ($images as $f): ?>
                <div><?= htmlspecialchars($f) ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- MENSAJE POST REDIRECT -->
    <?php if ($message): ?>
        <div class="<?= $message['type'] === 'ok' ? 'ok' : 'error' ?>" style="margin-top:15px">
            <?= $message['type'] === 'ok' ? '✅' : '❌' ?> <?= htmlspecialchars($message['text']) ?>
        </div>
    <?php endif; ?>

    <!-- FORMULARIO -->
    <form method="POST" enctype="multipart/form-data">
        <h3>📤 Subir imagen</h3>
        <input type="file" name="imagen" accept="image/*" required>
        <br><br>
        <button type="submit">Subir Imagen</button>
    </form>

    <!-- GALERÍA -->
    <h3>📸 Galería (<?= count($images) ?> imágenes)</h3>
    <?php if (empty($images)): ?>
        <p>Sube una imagen para probar.</p>
    <?php else: ?>
        <div class="gallery">
            <?php foreach ($images as $img): ?>
                <div class="gallery-item">
                    <img src="/serve-image.php?file=<?= urlencode($img) ?>" alt="<?= htmlspecialchars($img) ?>"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='block'">
                    <div style="display:none; color:red; font-size:12px">❌ nginx no sirve este archivo</div>
                    <a href="<?= htmlspecialchars($base_url . $img) ?>" target="_blank"><?= htmlspecialchars($img) ?></a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</body>
</html>