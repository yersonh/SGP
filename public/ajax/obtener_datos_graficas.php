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
    
    // Obtener parámetros CON FILTROS
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    $tipo = $_POST['tipo'] ?? 'todos';
    $zona = $_POST['zona'] ?? 'todas';
    
    // 1. Obtener estadísticas con filtros
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
    
    // 3. Distribución por elección - usando los campos CORRECTOS
    $distribucion = [
        'camara' => (int)($estadisticas['total_camara_con_ambos'] ?? 0),
        'senado' => (int)($estadisticas['total_senado_con_ambos'] ?? 0),
        'solo_camara' => (int)($estadisticas['camara'] ?? 0),
        'solo_senado' => (int)($estadisticas['senado'] ?? 0),
        'ambos' => (int)($estadisticas['ambos'] ?? 0)
    ];
    
    // 4. Distribución por zona con filtros
    $distribucion_zonas = $referenciadoModel->getDistribucionPorZona($fecha, $tipo, $zona);
    $por_zona = [];
    
    // Limitar a 10 zonas
    $zonas_limitadas = array_slice($distribucion_zonas, 0, 10);
    
    foreach ($zonas_limitadas as $zona_data) {
        $por_zona[] = [
            'zona' => $zona_data['zona'],
            'cantidad' => (int)$zona_data['cantidad'],
            // NOTA: Los métodos actuales no tienen camara/senado por zona
            // Necesitarías modificar getDistribucionPorZona para incluir estos campos
            'camara' => 0, // Placeholder - necesitas modificar el método
            'senado' => 0  // Placeholder - necesitas modificar el método
        ];
    }
    
    // 5. Top referenciadores con filtros
    $top_data = $referenciadoModel->getTopReferenciadoresPorFecha($fecha, 10, $tipo, $zona);
    $top_referenciadores = [];
    
    foreach ($top_data as $top) {
        $top_referenciadores[] = [
            'nombre' => $top['nombre'],
            'cantidad' => (int)$top['cantidad'],
            'zona_nombre' => $top['zona_nombre'] ?? 'Sin zona'
        ];
    }
    
    // 6. Datos adicionales para gráficas
    $por_compromiso = [
        'con_compromiso' => (int)($estadisticas['con_compromiso'] ?? 0),
        'sin_compromiso' => (int)($estadisticas['total'] ?? 0) - (int)($estadisticas['con_compromiso'] ?? 0)
    ];
    
    $por_vota_fuera = [
        'vota_fuera' => (int)($estadisticas['vota_fuera'] ?? 0),
        'vota_aqui' => (int)($estadisticas['total'] ?? 0) - (int)($estadisticas['vota_fuera'] ?? 0)
    ];
    
    // 7. Distribución por sexo (si tienes el campo)
    $sql_sexo = "SELECT 
                    sexo,
                    COUNT(*) as cantidad
                FROM referenciados r
                INNER JOIN usuario u ON r.id_referenciador = u.id_usuario
                WHERE DATE(r.fecha_registro) = :fecha
                AND r.activo = true
                AND u.tipo_usuario = 'Referenciador'
                AND u.activo = true";
    
    $params = [':fecha' => $fecha];
    
    // Agregar filtros
    if ($tipo && $tipo !== 'todos') {
        if ($tipo === 'camara') {
            $sql_sexo .= " AND (r.id_grupo = 1 OR r.id_grupo = 3)";
        } elseif ($tipo === 'senado') {
            $sql_sexo .= " AND (r.id_grupo = 2 OR r.id_grupo = 3)";
        }
    }
    
    if ($zona && $zona !== 'todas') {
        $sql_sexo .= " AND r.id_zona = :zona";
        $params[':zona'] = $zona;
    }
    
    $sql_sexo .= " GROUP BY sexo";
    
    $stmt_sexo = $pdo->prepare($sql_sexo);
    $stmt_sexo->execute($params);
    $sexo_data = $stmt_sexo->fetchAll(PDO::FETCH_ASSOC);
    
    $por_sexo = [];
    foreach ($sexo_data as $sexo) {
        $por_sexo[] = [
            'sexo' => $sexo['sexo'] ?: 'No especificado',
            'cantidad' => (int)$sexo['cantidad']
        ];
    }
    
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
            'por_sexo' => $por_sexo,
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