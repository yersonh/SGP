<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/CandidatoModel.php';

header('Content-Type: application/json');

// Verificar permisos
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'Administrador') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$id_candidato = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if (empty($id_candidato)) {
    echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
    exit();
}

try {
    $pdo = Database::getConnection();
    $candidatoModel = new CandidatoModel($pdo);
    
    $resultado = $candidatoModel->delete($id_candidato);
    
    if ($resultado) {
        echo json_encode([
            'success' => true,
            'message' => 'Candidato eliminado correctamente'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al eliminar el candidato'
        ]);
    }
    
} catch (Exception $e) {
    error_log('Error en eliminar_candidato.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor'
    ]);
}
?>