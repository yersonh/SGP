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
    $datos = $llamadaModel->getAnalisisCalidad($filtros);
    
    // Verificar que se obtuvieron datos
    if (empty($datos)) {
        echo json_encode([
            'success' => true,
            'data' => [
                'distribucion_rating' => [],
                'calidad_por_resultado' => [],
                'rating_por_hora' => [],
                'porcentaje_con_observaciones' => 0,
                'longitud_promedio_observaciones' => 0,
                'ultimas_observaciones' => []
            ]
        ]);
    } else {
        echo json_encode([
            'success' => true, 
            'data' => $datos
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => 'Error al obtener datos de calidad'
    ]);
}
?>