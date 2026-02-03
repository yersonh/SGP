<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/LlamadaModel.php';

if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    header('HTTP/1.0 403 Forbidden');
    exit();
}

$pdo = Database::getConnection();
$llamadaModel = new LlamadaModel($pdo);

// Obtener filtros
$filtros = [
    'fecha' => $_GET['fecha'] ?? date('Y-m-d'),
    'tipo_resultado' => $_GET['tipo_resultado'] ?? 'todos',
    'rating' => $_GET['rating'] ?? 'todos',
    'rango' => $_GET['rango'] ?? 'hoy'
];

if ($filtros['rango'] === 'personalizado' && isset($_GET['fecha_desde']) && isset($_GET['fecha_hasta'])) {
    $filtros['fecha_desde'] = $_GET['fecha_desde'];
    $filtros['fecha_hasta'] = $_GET['fecha_hasta'];
}

try {
    // Obtener datos para exportar
    $sql = "SELECT 
                lt.id_llamada,
                lt.fecha_llamada,
                CONCAT(u.nombres, ' ', u.apellidos) as llamador,
                u.cedula as llamador_cedula,
                r.nombre as referenciado_nombre,
                r.apellido as referenciado_apellido,
                r.cedula as referenciado_cedula,
                r.telefono as telefono_referenciado,
                lt.telefono as telefono_llamado,
                tr.nombre as resultado,
                lt.rating,
                lt.observaciones,
                z.nombre as zona,
                s.nombre as sector,
                r.afinidad,
                r.compromiso,
                CASE 
                    WHEN r.sexo = 'M' THEN 'Masculino'
                    WHEN r.sexo = 'F' THEN 'Femenino'
                    ELSE 'No especificado'
                END as sexo,
                r.fecha_nacimiento,
                TIMESTAMPDIFF(YEAR, r.fecha_nacimiento, CURDATE()) as edad
            FROM llamadas_tracking lt
            INNER JOIN usuario u ON lt.id_usuario = u.id_usuario
            INNER JOIN referenciados r ON lt.id_referenciado = r.id_referenciado
            LEFT JOIN tipos_resultado_llamada tr ON lt.id_resultado = tr.id_resultado
            LEFT JOIN zonas z ON r.id_zona = z.id_zona
            LEFT JOIN sectores s ON r.id_sector = s.id_sector
            WHERE 1=1";
    
    $params = [];
    
    if (!empty($filtros['fecha'])) {
        $sql .= " AND DATE(lt.fecha_llamada) = :fecha";
        $params[':fecha'] = $filtros['fecha'];
    }
    
    if (!empty($filtros['fecha_desde']) && !empty($filtros['fecha_hasta'])) {
        $sql .= " AND DATE(lt.fecha_llamada) BETWEEN :fecha_desde AND :fecha_hasta";
        $params[':fecha_desde'] = $filtros['fecha_desde'];
        $params[':fecha_hasta'] = $filtros['fecha_hasta'];
    }
    
    if (!empty($filtros['tipo_resultado']) && $filtros['tipo_resultado'] !== 'todos') {
        $sql .= " AND lt.id_resultado = :tipo_resultado";
        $params[':tipo_resultado'] = $filtros['tipo_resultado'];
    }
    
    if (!empty($filtros['rating']) && $filtros['rating'] !== 'todos') {
        if ($filtros['rating'] === '0') {
            $sql .= " AND lt.rating IS NULL";
        } else {
            $sql .= " AND lt.rating = :rating";
            $params[':rating'] = $filtros['rating'];
        }
    }
    
    $sql .= " ORDER BY lt.fecha_llamada DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $llamadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener estadísticas para el encabezado
    $sqlEstadisticas = "SELECT 
                            COUNT(*) as total_llamadas,
                            COUNT(DISTINCT id_usuario) as llamadores_activos,
                            AVG(rating) as rating_promedio,
                            COUNT(CASE WHEN id_resultado = 1 THEN 1 END) as contactos_efectivos
                        FROM llamadas_tracking lt
                        WHERE 1=1" . substr($sql, strpos($sql, "WHERE") + 5);
    
    $stmtEstadisticas = $pdo->prepare($sqlEstadisticas);
    $stmtEstadisticas->execute($params);
    $estadisticas = $stmtEstadisticas->fetch(PDO::FETCH_ASSOC);
    
    // Generar archivo CSV
    $filename = 'reporte_tracking_' . date('Y-m-d_H-i') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // BOM para UTF-8
    fwrite($output, "\xEF\xBB\xBF");
    
    // Encabezado del reporte
    fputcsv($output, ['REPORTE DE TRACKING DE LLAMADAS'], ',');
    fputcsv($output, ['Fecha de generación: ' . date('d/m/Y H:i:s')], ',');
    fputcsv($output, ['Filtros aplicados:'], ',');
    fputcsv($output, ['- Fecha: ' . $filtros['fecha']], ',');
    fputcsv($output, ['- Resultado: ' . ($filtros['tipo_resultado'] == 'todos' ? 'Todos' : $filtros['tipo_resultado'])], ',');
    fputcsv($output, ['- Rating: ' . ($filtros['rating'] == 'todos' ? 'Todos' : $filtros['rating'])], ',');
    fputcsv($output, [], ',');
    
    // Estadísticas
    fputcsv($output, ['ESTADÍSTICAS GENERALES'], ',');
    fputcsv($output, ['Total de llamadas', 'Llamadores activos', 'Rating promedio', 'Contactos efectivos'], ',');
    fputcsv($output, [
        $estadisticas['total_llamadas'] ?? 0,
        $estadisticas['llamadores_activos'] ?? 0,
        round($estadisticas['rating_promedio'] ?? 0, 2),
        $estadisticas['contactos_efectivos'] ?? 0
    ], ',');
    fputcsv($output, [], ',');
    
    // Encabezado de datos
    fputcsv($output, ['DETALLE DE LLAMADAS'], ',');
    fputcsv($output, [
        'ID',
        'Fecha y Hora',
        'Llamador',
        'Cédula Llamador',
        'Referenciado',
        'Apellido Referenciado',
        'Cédula Referenciado',
        'Teléfono Referenciado',
        'Teléfono Llamado',
        'Resultado',
        'Rating',
        'Observaciones',
        'Zona',
        'Sector',
        'Afinidad',
        'Compromiso',
        'Sexo',
        'Fecha Nacimiento',
        'Edad'
    ], ',');
    
    // Datos
    foreach ($llamadas as $llamada) {
        // Formatear rating con estrellas
        $rating = '';
        if ($llamada['rating']) {
            $rating = str_repeat('★', $llamada['rating']) . str_repeat('☆', 5 - $llamada['rating']) . " ({$llamada['rating']})";
        }
        
        fputcsv($output, [
            $llamada['id_llamada'],
            $llamada['fecha_llamada'],
            $llamada['llamador'],
            $llamada['llamador_cedula'],
            $llamada['referenciado_nombre'],
            $llamada['referenciado_apellido'],
            $llamada['referenciado_cedula'],
            $llamada['telefono_referenciado'],
            $llamada['telefono_llamado'],
            $llamada['resultado'],
            $rating,
            $llamada['observaciones'] ?? '',
            $llamada['zona'] ?? '',
            $llamada['sector'] ?? '',
            $llamada['afinidad'] ?? '',
            $llamada['compromiso'] ?? '',
            $llamada['sexo'] ?? '',
            $llamada['fecha_nacimiento'] ?? '',
            $llamada['edad'] ?? ''
        ], ',');
    }
    
    fclose($output);
    
} catch (Exception $e) {
    echo "Error al generar el reporte: " . $e->getMessage();
}
?>