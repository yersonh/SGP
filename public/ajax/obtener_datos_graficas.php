<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/ReferenciadoModel.php';

// Validar petición AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    exit(json_encode(['error' => 'Acceso denegado']));
}

// Verificar autorización
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    http_response_code(401);
    exit(json_encode(['error' => 'No autorizado']));
}

try {
    $pdo = Database::getConnection();
    $referenciadoModel = new ReferenciadoModel($pdo);
    
    // Obtener parámetros
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    
    // 1. Datos por hora (detallados) - usar método del modelo
    $horas_data = $referenciadoModel->getReferenciadosPorHora($fecha);
    $por_hora = [];
    
    // Formatear datos por hora
    foreach ($horas_data as $hora_data) {
        $por_hora[] = [
            'hora' => (int)$hora_data['hora'],
            'cantidad' => (int)$hora_data['cantidad']
        ];
    }
    
    // 2. Distribución por elección - usar método getEstadisticasPorFecha
    $estadisticas = $referenciadoModel->getEstadisticasPorFecha($fecha);
    $distribucion = [
        'camara' => (int)($estadisticas['camara'] ?? 0),
        'senado' => (int)($estadisticas['senado'] ?? 0)
    ];
    
    // 3. Distribución por zona - usar método del modelo
    $distribucion_zonas = $referenciadoModel->getDistribucionPorZona($fecha);
    $por_zona = [];
    
    // Limitar a 10 zonas
    $zonas_limitadas = array_slice($distribucion_zonas, 0, 10);
    
    foreach ($zonas_limitadas as $zona_data) {
        $por_zona[] = [
            'zona' => $zona_data['zona'],
            'cantidad' => (int)$zona_data['cantidad'],
            'camara' => (int)$zona_data['camara'] ?? 0,
            'senado' => (int)$zona_data['senado'] ?? 0
        ];
    }
    
    // 4. Top referenciadores - usar método del modelo
    $top_data = $referenciadoModel->getTopReferenciadoresPorFecha($fecha, 10);
    $top_referenciadores = [];
    
    foreach ($top_data as $top) {
        $top_referenciadores[] = [
            'nombre' => $top['nombre'],
            'cantidad' => (int)$top['cantidad'],
            'zona_nombre' => $top['zona_nombre'] ?? 'Sin zona'
        ];
    }
    
    // 5. Datos adicionales para gráficas
    // Datos por compromiso
    $por_compromiso = [
        'con_compromiso' => (int)($estadisticas['con_compromiso'] ?? 0),
        'sin_compromiso' => (int)($estadisticas['total'] ?? 0) - (int)($estadisticas['con_compromiso'] ?? 0)
    ];
    
    // Datos por vota_fuera
    $por_vota_fuera = [
        'vota_fuera' => (int)($estadisticas['vota_fuera'] ?? 0),
        'vota_aqui' => (int)($estadisticas['total'] ?? 0) - (int)($estadisticas['vota_fuera'] ?? 0)
    ];
    
    // Preparar respuesta
    $respuesta = [
        'success' => true,
        'data' => [
            'por_hora' => $por_hora,
            'distribucion' => $distribucion,
            'por_zona' => $por_zona,
            'top_referenciadores' => $top_referenciadores,
            'por_compromiso' => $por_compromiso,
            'por_vota_fuera' => $por_vota_fuera,
            'total_referenciados' => (int)($estadisticas['total'] ?? 0),
            'referenciadores_activos' => (int)($estadisticas['referenciadores_activos'] ?? 0),
            'zonas_activas' => (int)($estadisticas['zonas_activas'] ?? 0),
            'puestos_activos' => (int)($estadisticas['puestos_activos'] ?? 0)
        ]
    ];
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Error en obtener_datos_graficas: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>