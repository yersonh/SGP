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

    // Registrar el voto
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

    // Obtener estadísticas actualizadas - AHORA CORREGIDO
    $stmtStats = $pdo->query("SELECT 
                                (SELECT COUNT(*) FROM referenciados WHERE activo = true) as total_activos,
                                (SELECT COUNT(*) FROM referenciados WHERE voto_registrado = TRUE) as ya_votaron,
                                (SELECT COUNT(*) FROM referenciados WHERE voto_registrado = FALSE AND activo = true) as pendientes,
                                (SELECT COUNT(*) FROM referenciados WHERE voto_registrado = TRUE AND DATE(fecha_voto) = CURRENT_DATE) as votaron_hoy
                              ");
    
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Voto registrado exitosamente',
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