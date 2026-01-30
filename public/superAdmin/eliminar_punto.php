<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/PuntoMapaModel.php';

header('Content-Type: application/json');

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

// Obtener datos del POST
$input = json_decode(file_get_contents('php://input'), true);

// Validar datos
if (empty($input['id_punto'])) {
    echo json_encode(['success' => false, 'message' => 'ID de punto no especificado']);
    exit();
}

try {
    $pdo = Database::getConnection();
    $puntoModel = new PuntoMapaModel($pdo);
    
    // Eliminar punto
    $success = $puntoModel->eliminarPunto($input['id_punto'], $_SESSION['id_usuario']);
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Punto eliminado correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar el punto o punto no encontrado']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}