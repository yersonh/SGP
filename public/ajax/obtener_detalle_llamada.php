<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/LlamadaModel.php';

if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

if (!isset($_POST['id_llamada']) || empty($_POST['id_llamada'])) {
    echo json_encode(['success' => false, 'error' => 'ID de llamada no especificado']);
    exit();
}

$pdo = Database::getConnection();
$llamadaModel = new LlamadaModel($pdo);

$id_llamada = (int)$_POST['id_llamada'];

try {
    $sql = "SELECT 
                lt.*,
                CONCAT(u.nombres, ' ', u.apellidos) as llamador_nombre,
                u.cedula as llamador_cedula,
                r.nombre as referenciado_nombre,
                r.apellido as referenciado_apellido,
                r.cedula as referenciado_cedula,
                r.telefono,
                r.email,
                r.afinidad,
                tr.nombre as resultado_nombre,
                tr.descripcion as resultado_descripcion,
                z.nombre as zona_nombre,
                s.nombre as sector_nombre
            FROM llamadas_tracking lt
            INNER JOIN usuario u ON lt.id_usuario = u.id_usuario
            INNER JOIN referenciados r ON lt.id_referenciado = r.id_referenciado
            LEFT JOIN tipos_resultado_llamada tr ON lt.id_resultado = tr.id_resultado
            LEFT JOIN zonas z ON r.id_zona = z.id_zona
            LEFT JOIN sectores s ON r.id_sector = s.id_sector
            WHERE lt.id_llamada = :id_llamada";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id_llamada', $id_llamada, PDO::PARAM_INT);
    $stmt->execute();
    
    $llamada = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$llamada) {
        echo json_encode(['success' => false, 'error' => 'Llamada no encontrada']);
        exit();
    }
    
    // Obtener historial de llamadas para este referenciado
    $sqlHistorial = "SELECT 
                        lt.*,
                        CONCAT(u.nombres, ' ', u.apellidos) as llamador_nombre,
                        tr.nombre as resultado_nombre
                    FROM llamadas_tracking lt
                    INNER JOIN usuario u ON lt.id_usuario = u.id_usuario
                    LEFT JOIN tipos_resultado_llamada tr ON lt.id_resultado = tr.id_resultado
                    WHERE lt.id_referenciado = :id_referenciado
                    AND lt.id_llamada != :id_llamada
                    ORDER BY lt.fecha_llamada DESC
                    LIMIT 5";
    
    $stmtHistorial = $pdo->prepare($sqlHistorial);
    $stmtHistorial->bindValue(':id_referenciado', $llamada['id_referenciado'], PDO::PARAM_INT);
    $stmtHistorial->bindValue(':id_llamada', $id_llamada, PDO::PARAM_INT);
    $stmtHistorial->execute();
    
    $historial = $stmtHistorial->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => array_merge($llamada, ['historial' => $historial])
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>