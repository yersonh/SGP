<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/ReferenciadoModel.php';

// Verificar si el usuario está logueado y es Descargador
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'Descargador') {
    header('HTTP/1.0 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit();
}

// Verificar que se recibió el ID del referenciado
if (!isset($_POST['id_referenciado']) || empty($_POST['id_referenciado'])) {
    echo json_encode(['success' => false, 'message' => 'ID de votante no proporcionado']);
    exit();
}

$id_referenciado = (int)$_POST['id_referenciado'];
$id_usuario = $_SESSION['id_usuario'];

// ============================================
// PROCESAR LA FOTO DEL COMPROBANTE (OPCIONAL)
// ============================================
$foto_ruta = null;

if (isset($_FILES['comprobanteFoto']) && $_FILES['comprobanteFoto']['error'] === UPLOAD_ERR_OK) {
    $archivo = $_FILES['comprobanteFoto'];
    
    // Validar tipo de archivo
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (in_array($extension, $allowed)) {
        // Validar tamaño (máximo 5MB)
        if ($archivo['size'] > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'La imagen no puede ser mayor a 5MB']);
            exit();
        }
        
        // Generar nombre único
        $nombre_unico = 'voto_' . $id_referenciado . '_' . time() . '.' . $extension;
        
        // Ruta en el volumen persistente
        $ruta_destino = '/uploads/profiles/' . $nombre_unico;
        
        // Mover el archivo
        if (move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
            // Guardar la ruta relativa para la BD
            $foto_ruta = '/uploads/profiles/' . $nombre_unico;
            
            // Log para debug
            error_log("Foto guardada en: " . $ruta_destino);
        } else {
            error_log("Error al mover archivo: " . $archivo['tmp_name'] . " a " . $ruta_destino);
            echo json_encode(['success' => false, 'message' => 'Error al guardar la imagen']);
            exit();
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido. Use: jpg, png, gif, webp']);
        exit();
    }
}

$pdo = Database::getConnection();

try {
    // Verificar que el referenciado existe y no ha votado aún
    $stmt = $pdo->prepare("SELECT id_referenciado, voto_registrado FROM referenciados WHERE id_referenciado = ?");
    $stmt->execute([$id_referenciado]);
    $referenciado = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$referenciado) {
        echo json_encode(['success' => false, 'message' => 'El votante no existe']);
        exit();
    }

    if ($referenciado['voto_registrado']) {
        echo json_encode(['success' => false, 'message' => 'Este votante ya registró su voto']);
        exit();
    }

    // Registrar el voto (con o sin foto)
    if ($foto_ruta) {
        $sql = "UPDATE referenciados 
                SET voto_registrado = TRUE, 
                    fecha_voto = NOW(), 
                    id_usuario_registro_voto = :id_usuario,
                    foto_comprobante = :foto_ruta 
                WHERE id_referenciado = :id_referenciado";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id_usuario' => $id_usuario,
            ':id_referenciado' => $id_referenciado,
            ':foto_ruta' => $foto_ruta
        ]);
    } else {
        $sql = "UPDATE referenciados 
                SET voto_registrado = TRUE, 
                    fecha_voto = NOW(), 
                    id_usuario_registro_voto = :id_usuario 
                WHERE id_referenciado = :id_referenciado";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id_usuario' => $id_usuario,
            ':id_referenciado' => $id_referenciado
        ]);
    }

    // Obtener estadísticas actualizadas
    $stmtStats = $pdo->query("SELECT 
                                (SELECT COUNT(*) FROM referenciados WHERE activo = true) as total_activos,
                                (SELECT COUNT(*) FROM referenciados WHERE voto_registrado = TRUE) as ya_votaron,
                                (SELECT COUNT(*) FROM referenciados WHERE voto_registrado = FALSE AND activo = true) as pendientes,
                                (SELECT COUNT(*) FROM referenciados WHERE voto_registrado = TRUE AND DATE(fecha_voto) = CURRENT_DATE) as votaron_hoy
                              ");
    
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Voto registrado exitosamente' . ($foto_ruta ? ' con foto' : ''),
        'foto_ruta' => $foto_ruta, // Opcional: devolver la ruta de la foto
        'stats' => [
            'total_activos' => (int)$stats['total_activos'],
            'ya_votaron' => (int)$stats['ya_votaron'],
            'pendientes' => (int)$stats['pendientes'],
            'votaron_hoy' => (int)$stats['votaron_hoy'],
            'porcentaje' => $stats['total_activos'] > 0 ? round(($stats['ya_votaron'] / $stats['total_activos']) * 100, 2) : 0
        ]
    ]);

} catch (PDOException $e) {
    error_log("Error en registrar_voto: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al registrar el voto: ' . $e->getMessage()]);
}
?>