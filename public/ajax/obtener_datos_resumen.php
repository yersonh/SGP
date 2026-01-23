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
    
    // Obtener parámetros con filtros
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    $tipo = $_POST['tipo'] ?? 'todos';
    $zona = $_POST['zona'] ?? 'todas';
    
    // 1. Obtener estadísticas CON filtros
    $estadisticas = $referenciadoModel->getEstadisticasPorFecha($fecha, $tipo, $zona);
    
    // 2. Datos por hora con filtros
    $horas_data = $referenciadoModel->getReferenciadosPorHora($fecha, $tipo, $zona);
    $por_hora = [];
    
    // Formatear datos por hora
    foreach ($horas_data as $hora_data) {
        $por_hora[] = [
            'hora' => (int)$hora_data['hora'],
            'cantidad' => (int)$hora_data['cantidad']
        ];
    }
    
    // 3. Top referenciadores con filtros
    $top_data = $referenciadoModel->getTopReferenciadoresPorFecha($fecha, 5, $tipo, $zona);
    $top_referenciadores = [];
    
    foreach ($top_data as $top) {
        $top_referenciadores[] = [
            'nombre' => $top['nombre'],
            'cantidad' => (int)$top['cantidad'],
            'zona_nombre' => $top['zona_nombre'] ?? 'Sin zona'
        ];
    }
    
    // 4. Top zonas con filtros
    $zonas_data = $referenciadoModel->getDistribucionPorZona($fecha, $tipo, $zona);
    $top_zonas = [];
    
    // Limitar a 5 zonas
    $zonas_limitadas = array_slice($zonas_data, 0, 5);
    
    foreach ($zonas_limitadas as $zona_data) {
        $top_zonas[] = [
            'nombre' => $zona_data['zona'],
            'cantidad' => (int)$zona_data['cantidad']
        ];
    }
    
    // 5. Hora pico del día
    $hora_pico = null;
    if (!empty($por_hora)) {
        $max_cantidad = max(array_column($por_hora, 'cantidad'));
        $hora_pico_data = array_filter($por_hora, function($item) use ($max_cantidad) {
            return $item['cantidad'] == $max_cantidad;
        });
        $hora_pico_data = array_values($hora_pico_data);
        
        if (!empty($hora_pico_data)) {
            $hora_pico = [
                'hora' => $hora_pico_data[0]['hora'],
                'descripcion' => "Máxima actividad con {$max_cantidad} referenciados"
            ];
        }
    }
    
    // 6. Cambio vs ayer (calculamos diferencia con el día anterior)
    $ayer = date('Y-m-d', strtotime($fecha . ' -1 day'));
    $estadisticas_ayer = $referenciadoModel->getEstadisticasPorFecha($ayer, $tipo, $zona);
    
    $total_hoy = (int)($estadisticas['total'] ?? 0);
    $total_ayer = (int)($estadisticas_ayer['total'] ?? 0);
    
    $diferencia = $total_hoy - $total_ayer;
    $porcentaje_cambio = $total_ayer > 0 ? round(($diferencia / $total_ayer) * 100, 2) : 0;
    
    // 7. Preparar respuesta simplificada
    $respuesta = [
        'success' => true,
        'data' => [
            // KPIs principales
            'total_referenciados' => $total_hoy,
            'referenciadores_activos' => (int)($estadisticas['referenciadores_activos'] ?? 0),
            'camara' => (int)($estadisticas['total_camara_con_ambos'] ?? 0),
            'senado' => (int)($estadisticas['total_senado_con_ambos'] ?? 0),
            'cambio_vs_ayer' => $diferencia,
            'porcentaje_cambio' => $porcentaje_cambio,
            
            // Para gráficas
            'grafica_horas' => $por_hora,
            
            // Para mini gráficas
            'top_referenciadores' => $top_referenciadores,
            'top_zonas' => $top_zonas,
            'hora_pico' => $hora_pico
        ]
    ];
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Error en obtener_datos_resumen: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>