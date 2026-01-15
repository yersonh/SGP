<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/ReferenciadoModel.php';

// Verificar sesión
if (!isset($_SESSION['id_usuario'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

// Obtener conexión
$pdo = Database::getConnection();
$referenciadoModel = new ReferenciadoModel($pdo);

// Recibir cédula
$cedula = trim($_POST['cedula'] ?? '');

if (empty($cedula)) {
    echo json_encode(['success' => false, 'message' => 'Cédula no proporcionada']);
    exit();
}

// Validar formato (solo números)
if (!preg_match('/^\d+$/', $cedula)) {
    echo json_encode(['success' => false, 'message' => 'Formato de cédula inválido']);
    exit();
}

try {
    // Verificar si la cédula ya existe
    $cedulaExiste = $referenciadoModel->cedulaExiste($cedula);
    
    echo json_encode([
        'success' => true,
        'exists' => $cedulaExiste,
        'cedula' => $cedula
    ]);
    
} catch (Exception $e) {
    error_log("Error verificando cédula: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al verificar la cédula']);
}
?>