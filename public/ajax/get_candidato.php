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

$id_candidato = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (empty($id_candidato)) {
    echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
    exit();
}

try {
    $pdo = Database::getConnection();
    $candidatoModel = new CandidatoModel($pdo);
    
    $candidato = $candidatoModel->getById($id_candidato);
    
    if ($candidato) {
        echo json_encode([
            'success' => true,
            'id_candidato' => $candidato['id_candidato'],
            'nombre' => $candidato['nombre'],
            'apellido' => $candidato['apellido'],
            'id_grupo' => $candidato['id_grupo'],
            'id_partido' => $candidato['id_partido']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Candidato no encontrado'
        ]);
    }
    
} catch (Exception $e) {
    error_log('Error en get_candidato.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor'
    ]);
}
?>