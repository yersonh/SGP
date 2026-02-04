<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/LlamadaModel.php';

if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

$pdo = Database::getConnection();
// $llamadaModel = new LlamadaModel($pdo); // No se usa en este archivo

$filtros = [
    'fecha' => $_POST['fecha'] ?? date('Y-m-d'),
    'tipo_resultado' => $_POST['tipo_resultado'] ?? 'todos',
    'rating' => $_POST['rating'] ?? 'todos',
    'rango' => $_POST['rango'] ?? 'hoy',
    'pagina' => $_POST['pagina'] ?? 1,
    'limite' => $_POST['limite'] ?? 10
];

$offset = ($filtros['pagina'] - 1) * $filtros['limite'];

try {
    // Construir WHERE común
    $where = "WHERE 1=1";
    $params = [];
    
    if (!empty($filtros['fecha']) && $filtros['rango'] !== 'personalizado') {
        $where .= " AND DATE(lt.fecha_llamada) = :fecha";
        $params[':fecha'] = $filtros['fecha'];
    }
    
    // Para rango personalizado
    if ($filtros['rango'] === 'personalizado' && isset($_POST['fecha_desde']) && isset($_POST['fecha_hasta'])) {
        $where .= " AND DATE(lt.fecha_llamada) BETWEEN :fecha_desde AND :fecha_hasta";
        $params[':fecha_desde'] = $_POST['fecha_desde'];
        $params[':fecha_hasta'] = $_POST['fecha_hasta'];
    }
    
    // Para otros rangos
    switch ($filtros['rango']) {
        case 'ayer':
            $fechaAyer = date('Y-m-d', strtotime('-1 day'));
            $where .= " AND DATE(lt.fecha_llamada) = :fecha";
            $params[':fecha'] = $fechaAyer;
            break;
        case 'semana':
            $where .= " AND DATE(lt.fecha_llamada) >= DATE_TRUNC('week', CURRENT_DATE)";
            break;
        case 'mes':
            $where .= " AND DATE(lt.fecha_llamada) >= DATE_TRUNC('month', CURRENT_DATE)";
            break;
    }
    
    if (!empty($filtros['tipo_resultado']) && $filtros['tipo_resultado'] !== 'todos') {
        $where .= " AND lt.id_resultado = :tipo_resultado";
        $params[':tipo_resultado'] = $filtros['tipo_resultado'];
    }
    
    if (!empty($filtros['rating']) && $filtros['rating'] !== 'todos') {
        if ($filtros['rating'] === '0') {
            $where .= " AND lt.rating IS NULL";
        } else {
            $where .= " AND lt.rating = :rating";
            $params[':rating'] = $filtros['rating'];
        }
    }
    
    // Consulta para TOTAL de registros (CORREGIDA)
    $sqlTotal = "SELECT COUNT(*) as total FROM llamadas_tracking lt {$where}";
    $stmtTotal = $pdo->prepare($sqlTotal);
    $stmtTotal->execute($params);
    $total = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Consulta paginada para los datos
    $sql = "SELECT 
                lt.*,
                CONCAT(u.nombres, ' ', u.apellidos) as llamador_nombre,
                u.cedula as llamador_cedula,
                r.nombre as referenciado_nombre,
                r.apellido as referenciado_apellido,
                r.cedula as referenciado_cedula,
                tr.nombre as resultado_nombre
            FROM llamadas_tracking lt
            INNER JOIN usuario u ON lt.id_usuario = u.id_usuario
            INNER JOIN referenciados r ON lt.id_referenciado = r.id_referenciado
            LEFT JOIN tipos_resultado_llamada tr ON lt.id_resultado = tr.id_resultado
            {$where}
            ORDER BY lt.fecha_llamada DESC 
            LIMIT :limite OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    
    // Vincular parámetros del WHERE
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    // Vincular parámetros de paginación
    $stmt->bindValue(':limite', (int)$filtros['limite'], PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $llamadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalPaginas = $filtros['limite'] > 0 ? ceil($total / $filtros['limite']) : 0;
    
    // DEBUG: Ver qué datos se están obteniendo
    error_log("Total registros: " . $total);
    error_log("Número de llamadas obtenidas: " . count($llamadas));
    error_log("Filtros aplicados: " . json_encode($filtros));
    error_log("SQL ejecutado: " . $sql);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'llamadas' => $llamadas,
            'total' => $total,
            'total_paginas' => $totalPaginas,
            'pagina_actual' => $filtros['pagina']
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error en obtener_detalle_tracking.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
?>