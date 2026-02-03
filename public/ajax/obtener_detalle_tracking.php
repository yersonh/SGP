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
    'rango' => $_POST['rango'] ?? 'hoy',
    'pagina' => $_POST['pagina'] ?? 1,
    'limite' => $_POST['limite'] ?? 10
];

$offset = ($filtros['pagina'] - 1) * $filtros['limite'];

try {
    // Construir consulta
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
            WHERE 1=1";
    
    $params = [];
    
    if (!empty($filtros['fecha'])) {
        $sql .= " AND DATE(lt.fecha_llamada) = :fecha";
        $params[':fecha'] = $filtros['fecha'];
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
    
    // Total de registros
    $sqlTotal = "SELECT COUNT(*) as total FROM llamadas_tracking lt WHERE 1=1" . substr($sql, strpos($sql, "WHERE") + 5);
    $stmtTotal = $pdo->prepare($sqlTotal);
    $stmtTotal->execute($params);
    $total = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Consulta paginada
    $sql .= " ORDER BY lt.fecha_llamada DESC LIMIT :limite OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->bindValue(':limite', (int)$filtros['limite'], PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $llamadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalPaginas = ceil($total / $filtros['limite']);
    
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
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>