<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/LlamadaModel.php';

if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

$pdo = Database::getConnection();
$llamadaModel = new LlamadaModel($pdo);

// Obtener parámetros
$filtros = [
    'fecha' => $_POST['fecha'] ?? date('Y-m-d'),
    'tipo_resultado' => $_POST['tipo_resultado'] ?? 'todos',
    'rating' => $_POST['rating'] ?? 'todos',
    'rango' => $_POST['rango'] ?? 'hoy'
];

// Aplicar filtros de rango de fechas
if ($filtros['rango'] === 'personalizado' && isset($_POST['fecha_desde']) && isset($_POST['fecha_hasta'])) {
    $filtros['fecha_desde'] = $_POST['fecha_desde'];
    $filtros['fecha_hasta'] = $_POST['fecha_hasta'];
}

try {
    // Obtener datos del resumen
    $datos = $llamadaModel->getResumenTracking($filtros);
    
    echo json_encode([
        'success' => true,
        'data' => $datos
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>