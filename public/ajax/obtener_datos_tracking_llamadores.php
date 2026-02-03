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

$filtros = [
    'fecha' => $_POST['fecha'] ?? date('Y-m-d'),
    'tipo_resultado' => $_POST['tipo_resultado'] ?? 'todos',
    'rating' => $_POST['rating'] ?? 'todos',
    'rango' => $_POST['rango'] ?? 'hoy'
];

if ($filtros['rango'] === 'personalizado' && isset($_POST['fecha_desde']) && isset($_POST['fecha_hasta'])) {
    $filtros['fecha_desde'] = $_POST['fecha_desde'];
    $filtros['fecha_hasta'] = $_POST['fecha_hasta'];
}

try {
    // Obtener top llamadores
    $topLlamadores = $llamadaModel->getTopLlamadoresConFiltros(10, $filtros);
    
    // Obtener distribución por llamador
    $distribucionLlamadores = $llamadaModel->getDistribucionPorLlamador($filtros);
    
    // Obtener eficiencia por llamador
    $eficienciaLlamadores = $llamadaModel->getEficienciaPorLlamador($filtros);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'top_llamadores' => $topLlamadores,
            'distribucion_llamadores' => $distribucionLlamadores,
            'eficiencia_llamadores' => $eficienciaLlamadores
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>