<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/LlamadaModel.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

if (!isset($_GET['id_referenciado']) || !is_numeric($_GET['id_referenciado'])) {
    echo json_encode(['success' => false, 'message' => 'ID no válido']);
    exit();
}

$id_referenciado = intval($_GET['id_referenciado']);

try {
    $pdo = Database::getConnection();
    $llamadaModel = new LlamadaModel($pdo);
    
    // Obtener historial de llamadas
    $sql = "SELECT 
                l.*,
                tr.nombre as resultado_nombre,
                u.nickname as usuario_nombre
            FROM llamadas_tracking l
            LEFT JOIN tipos_resultado_llamada tr ON l.id_resultado = tr.id_resultado
            LEFT JOIN usuario u ON l.id_usuario = u.id_usuario
            WHERE l.id_referenciado = :id_referenciado
            ORDER BY l.fecha_llamada DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id_referenciado', $id_referenciado, PDO::PARAM_INT);
    $stmt->execute();
    
    $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // También obtener información básica del referenciado para mostrar teléfono
    $sqlReferenciado = "SELECT telefono FROM referenciados WHERE id_referenciado = :id_referenciado";
    $stmtRef = $pdo->prepare($sqlReferenciado);
    $stmtRef->bindValue(':id_referenciado', $id_referenciado, PDO::PARAM_INT);
    $stmtRef->execute();
    $referenciado = $stmtRef->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'historial' => $historial,
        'referenciado' => $referenciado
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error en el servidor: ' . $e->getMessage()
    ]);
}
?>