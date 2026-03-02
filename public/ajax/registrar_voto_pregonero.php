<?php
// ajax/registrar_voto_pregonero.php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/PregoneroModel.php';

// Verificar si el usuario está logueado y es SuperAdmin (o el rol que corresponda)
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    header('HTTP/1.0 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit();
}

// Verificar que se recibió el ID del pregonero
if (!isset($_POST['id_pregonero']) || empty($_POST['id_pregonero'])) {
    echo json_encode(['success' => false, 'message' => 'ID de pregonero no proporcionado']);
    exit();
}

$id_pregonero = (int)$_POST['id_pregonero'];
$id_usuario = $_SESSION['id_usuario'];

$pdo = Database::getConnection();
$pregoneroModel = new PregoneroModel($pdo);

try {
    // Verificar que el pregonero existe y no ha votado aún
    $stmt = $pdo->prepare("SELECT id_pregonero, voto_registrado, activo FROM public.pregonero WHERE id_pregonero = ?");
    $stmt->execute([$id_pregonero]);
    $pregonero = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pregonero) {
        echo json_encode(['success' => false, 'message' => 'El pregonero no existe']);
        exit();
    }

    if (!$pregonero['activo']) {
        echo json_encode(['success' => false, 'message' => 'El pregonero está inactivo y no puede registrar su voto']);
        exit();
    }

    if ($pregonero['voto_registrado']) {
        echo json_encode(['success' => false, 'message' => 'Este pregonero ya registró su voto']);
        exit();
    }

    // Registrar el voto
    $sql = "UPDATE public.pregonero 
            SET voto_registrado = TRUE, 
                fecha_voto = NOW(), 
                id_usuario_registro_voto = :id_usuario 
            WHERE id_pregonero = :id_pregonero";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id_usuario' => $id_usuario,
        ':id_pregonero' => $id_pregonero
    ]);

    // Obtener estadísticas actualizadas de pregoneros
    $stmtStats = $pdo->query("SELECT 
                                (SELECT COUNT(*) FROM public.pregonero WHERE activo = true) as total_activos,
                                (SELECT COUNT(*) FROM public.pregonero WHERE voto_registrado = TRUE) as ya_votaron,
                                (SELECT COUNT(*) FROM public.pregonero WHERE voto_registrado = FALSE AND activo = true) as pendientes,
                                (SELECT COUNT(*) FROM public.pregonero WHERE voto_registrado = TRUE AND DATE(fecha_voto) = CURRENT_DATE) as votaron_hoy
                              ");
    
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
    
    // Calcular porcentaje de participación
    $porcentaje = $stats['total_activos'] > 0 
        ? round(($stats['ya_votaron'] / $stats['total_activos']) * 100, 2) 
        : 0;
    
    echo json_encode([
        'success' => true,
        'message' => 'Voto de pregonero registrado exitosamente',
        'stats' => [
            'total_activos' => (int)$stats['total_activos'],
            'ya_votaron' => (int)$stats['ya_votaron'],
            'pendientes' => (int)$stats['pendientes'],
            'votaron_hoy' => (int)$stats['votaron_hoy'],
            'porcentaje' => $porcentaje
        ]
    ]);

} catch (PDOException $e) {
    error_log("Error en registrar_voto_pregonero: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al registrar el voto: ' . $e->getMessage()]);
}
?>