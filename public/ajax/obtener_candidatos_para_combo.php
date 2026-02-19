<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/LlamadaModel.php';

header('Content-Type: application/json');

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$grupo = isset($_GET['grupo']) ? intval($_GET['grupo']) : null;

try {
    $pdo = Database::getConnection();
    $llamadaModel = new LlamadaModel($pdo);
    
    $candidatos = $llamadaModel->getCandidatosParaCombo($grupo);
    
    echo json_encode([
        'success' => true,
        'candidatos' => $candidatos
    ]);
    
} catch (Exception $e) {
    error_log('Error en obtener_candidatos_para_combo: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor'
    ]);
}
?>