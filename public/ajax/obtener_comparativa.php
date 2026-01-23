<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

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
    
    // Obtener parámetros
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    $tipo_compara = $_POST['tipo'] ?? 'ayer'; // 'ayer', 'semana', 'mes'
    $tipo_eleccion = $_POST['tipo_eleccion'] ?? 'todos';
    $zona = $_POST['zona'] ?? 'todas';
    
    // Inicializar arrays para respuesta
    $datos_hoy = [];
    $datos_compara = [];
    $labels = [];
    
    // Preparar filtros comunes
    $filtros_sql = "";
    $params = [];
    
    if ($tipo_eleccion && $tipo_eleccion !== 'todos') {
        if ($tipo_eleccion === 'camara') {
            $filtros_sql .= " AND (r.id_grupo = 1 OR r.id_grupo = 3)";
        } elseif ($tipo_eleccion === 'senado') {
            $filtros_sql .= " AND (r.id_grupo = 2 OR r.id_grupo = 3)";
        }
    }
    
    if ($zona && $zona !== 'todas') {
        $filtros_sql .= " AND r.id_zona = :zona";
        $params[':zona'] = $zona;
    }
    
    switch ($tipo_compara) {
        case 'ayer':
            // Solo hoy y ayer
            $labels = [date('d/m', strtotime($fecha . ' -1 day')), date('d/m', strtotime($fecha))];
            
            // Consulta para ayer
            $sql = "SELECT 
                        DATE(r.fecha_registro) as fecha,
                        COUNT(*) as total,
                        (SUM(CASE WHEN r.id_grupo = 1 THEN 1 ELSE 0 END) + 
                         SUM(CASE WHEN r.id_grupo = 3 THEN 1 ELSE 0 END)) as camara,
                        (SUM(CASE WHEN r.id_grupo = 2 THEN 1 ELSE 0 END) + 
                         SUM(CASE WHEN r.id_grupo = 3 THEN 1 ELSE 0 END)) as senado
                    FROM referenciados r
                    INNER JOIN usuario u ON r.id_referenciador = u.id_usuario
                    WHERE DATE(r.fecha_registro) = :fecha
                    AND r.activo = true
                    AND u.tipo_usuario = 'Referenciador'
                    AND u.activo = true
                    $filtros_sql
                    GROUP BY DATE(r.fecha_registro)";
            
            // Ayer
            $params_ayer = array_merge([':fecha' => date('Y-m-d', strtotime($fecha . ' -1 day'))], $params);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params_ayer);
            $ayer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Hoy
            $params_hoy = array_merge([':fecha' => $fecha], $params);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params_hoy);
            $hoy = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // *** CORRECCIÓN: Convertir a array para consistencia ***
            $datos_compara = [[
                'total' => (int)($ayer['total'] ?? 0),
                'camara' => (int)($ayer['camara'] ?? 0),
                'senado' => (int)($ayer['senado'] ?? 0)
            ]];
            
            $datos_hoy = [[
                'total' => (int)($hoy['total'] ?? 0),
                'camara' => (int)($hoy['camara'] ?? 0),
                'senado' => (int)($hoy['senado'] ?? 0)
            ]];
            
            break;
            
        case 'semana':
            // Esta semana (lunes a domingo)
            $fecha_inicio_actual = date('Y-m-d', strtotime('monday this week', strtotime($fecha)));
            $fecha_fin_actual = date('Y-m-d', strtotime('sunday this week', strtotime($fecha)));
            
            // Semana pasada
            $fecha_inicio_pasada = date('Y-m-d', strtotime('monday last week', strtotime($fecha)));
            $fecha_fin_pasada = date('Y-m-d', strtotime('sunday last week', strtotime($fecha)));
            
            // Generar labels para 7 días
            for ($i = 0; $i < 7; $i++) {
                $fecha_dia = date('Y-m-d', strtotime($fecha_inicio_actual . " +$i days"));
                $labels[] = date('d/m D', strtotime($fecha_dia));
            }
            
            // Consulta para esta semana
            $sql_semana = "SELECT 
                            DATE(r.fecha_registro) as fecha,
                            COUNT(*) as total,
                            (SUM(CASE WHEN r.id_grupo = 1 THEN 1 ELSE 0 END) + 
                             SUM(CASE WHEN r.id_grupo = 3 THEN 1 ELSE 0 END)) as camara,
                            (SUM(CASE WHEN r.id_grupo = 2 THEN 1 ELSE 0 END) + 
                             SUM(CASE WHEN r.id_grupo = 3 THEN 1 ELSE 0 END)) as senado
                        FROM referenciados r
                        INNER JOIN usuario u ON r.id_referenciador = u.id_usuario
                        WHERE DATE(r.fecha_registro) BETWEEN :fecha_inicio AND :fecha_fin
                        AND r.activo = true
                        AND u.tipo_usuario = 'Referenciador'
                        AND u.activo = true
                        $filtros_sql
                        GROUP BY DATE(r.fecha_registro)
                        ORDER BY fecha";
            
            // Esta semana
            $params_semana = array_merge([
                ':fecha_inicio' => $fecha_inicio_actual,
                ':fecha_fin' => $fecha_fin_actual
            ], $params);
            
            $stmt = $pdo->prepare($sql_semana);
            $stmt->execute($params_semana);
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Organizar datos por día
            $datos_hoy = [];
            for ($i = 0; $i < 7; $i++) {
                $fecha_dia = date('Y-m-d', strtotime($fecha_inicio_actual . " +$i days"));
                $encontrado = false;
                
                foreach ($resultados as $row) {
                    if ($row['fecha'] == $fecha_dia) {
                        $datos_hoy[] = [
                            'total' => (int)$row['total'],
                            'camara' => (int)$row['camara'],
                            'senado' => (int)$row['senado']
                        ];
                        $encontrado = true;
                        break;
                    }
                }
                
                if (!$encontrado) {
                    $datos_hoy[] = ['total' => 0, 'camara' => 0, 'senado' => 0];
                }
            }
            
            // Semana pasada
            $params_pasada = array_merge([
                ':fecha_inicio' => $fecha_inicio_pasada,
                ':fecha_fin' => $fecha_fin_pasada
            ], $params);
            
            $stmt = $pdo->prepare($sql_semana);
            $stmt->execute($params_pasada);
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Organizar datos por día
            $datos_compara = [];
            for ($i = 0; $i < 7; $i++) {
                $fecha_dia = date('Y-m-d', strtotime($fecha_inicio_pasada . " +$i days"));
                $encontrado = false;
                
                foreach ($resultados as $row) {
                    if ($row['fecha'] == $fecha_dia) {
                        $datos_compara[] = [
                            'total' => (int)$row['total'],
                            'camara' => (int)$row['camara'],
                            'senado' => (int)$row['senado']
                        ];
                        $encontrado = true;
                        break;
                    }
                }
                
                if (!$encontrado) {
                    $datos_compara[] = ['total' => 0, 'camara' => 0, 'senado' => 0];
                }
            }
            
            break;
            
        case 'mes':
            // Este mes
            $fecha_inicio_actual = date('Y-m-01', strtotime($fecha));
            $fecha_fin_actual = date('Y-m-t', strtotime($fecha));
            $dias_mes = date('t', strtotime($fecha));
            
            // Mes pasado
            $fecha_inicio_pasada = date('Y-m-01', strtotime($fecha . ' -1 month'));
            $fecha_fin_pasada = date('Y-m-t', strtotime($fecha . ' -1 month'));
            $dias_mes_pasado = date('t', strtotime($fecha . ' -1 month'));
            
            // Usar el mes con más días para las labels
            $dias_comparar = max($dias_mes, $dias_mes_pasado);
            
            // Generar labels para los días
            for ($i = 1; $i <= $dias_comparar; $i++) {
                $labels[] = "Día $i";
            }
            
            // Consulta para este mes
            $sql_mes = "SELECT 
                        EXTRACT(DAY FROM r.fecha_registro) as dia,
                        COUNT(*) as total,
                        (SUM(CASE WHEN r.id_grupo = 1 THEN 1 ELSE 0 END) + 
                         SUM(CASE WHEN r.id_grupo = 3 THEN 1 ELSE 0 END)) as camara,
                        (SUM(CASE WHEN r.id_grupo = 2 THEN 1 ELSE 0 END) + 
                         SUM(CASE WHEN r.id_grupo = 3 THEN 1 ELSE 0 END)) as senado
                    FROM referenciados r
                    INNER JOIN usuario u ON r.id_referenciador = u.id_usuario
                    WHERE DATE(r.fecha_registro) BETWEEN :fecha_inicio AND :fecha_fin
                    AND r.activo = true
                    AND u.tipo_usuario = 'Referenciador'
                    AND u.activo = true
                    $filtros_sql
                    GROUP BY EXTRACT(DAY FROM r.fecha_registro)
                    ORDER BY dia";
            
            // Este mes
            $params_mes_actual = array_merge([
                ':fecha_inicio' => $fecha_inicio_actual,
                ':fecha_fin' => $fecha_fin_actual
            ], $params);
            
            $stmt = $pdo->prepare($sql_mes);
            $stmt->execute($params_mes_actual);
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Organizar datos por día
            $datos_hoy = [];
            for ($i = 1; $i <= $dias_comparar; $i++) {
                $encontrado = false;
                
                foreach ($resultados as $row) {
                    if ($row['dia'] == $i) {
                        $datos_hoy[] = [
                            'total' => (int)$row['total'],
                            'camara' => (int)$row['camara'],
                            'senado' => (int)$row['senado']
                        ];
                        $encontrado = true;
                        break;
                    }
                }
                
                if (!$encontrado) {
                    $datos_hoy[] = ['total' => 0, 'camara' => 0, 'senado' => 0];
                }
            }
            
            // Mes pasado
            $params_mes_pasado = array_merge([
                ':fecha_inicio' => $fecha_inicio_pasada,
                ':fecha_fin' => $fecha_fin_pasada
            ], $params);
            
            $stmt = $pdo->prepare($sql_mes);
            $stmt->execute($params_mes_pasado);
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Organizar datos por día
            $datos_compara = [];
            for ($i = 1; $i <= $dias_comparar; $i++) {
                $encontrado = false;
                
                foreach ($resultados as $row) {
                    if ($row['dia'] == $i) {
                        $datos_compara[] = [
                            'total' => (int)$row['total'],
                            'camara' => (int)$row['camara'],
                            'senado' => (int)$row['senado']
                        ];
                        $encontrado = true;
                        break;
                    }
                }
                
                if (!$encontrado) {
                    $datos_compara[] = ['total' => 0, 'camara' => 0, 'senado' => 0];
                }
            }
            
            break;
    }
    
    // Calcular totales
    $total_hoy = 0;
    $total_camara_hoy = 0;
    $total_senado_hoy = 0;
    
    $total_compara = 0;
    $total_camara_compara = 0;
    $total_senado_compara = 0;
    
    foreach ($datos_hoy as $dia) {
        $total_hoy += $dia['total'];
        $total_camara_hoy += $dia['camara'];
        $total_senado_hoy += $dia['senado'];
    }
    
    foreach ($datos_compara as $dia) {
        $total_compara += $dia['total'];
        $total_camara_compara += $dia['camara'];
        $total_senado_compara += $dia['senado'];
    }
    
    // Calcular diferencias
    $diferencia_total = $total_hoy - $total_compara;
    $porcentaje_total = $total_compara > 0 ? round(($diferencia_total / $total_compara) * 100, 1) : 0;
    
    $diferencia_camara = $total_camara_hoy - $total_camara_compara;
    $porcentaje_camara = $total_camara_compara > 0 ? round(($diferencia_camara / $total_camara_compara) * 100, 1) : 0;
    
    $diferencia_senado = $total_senado_hoy - $total_senado_compara;
    $porcentaje_senado = $total_senado_compara > 0 ? round(($diferencia_senado / $total_senado_compara) * 100, 1) : 0;
    
    // Preparar respuesta
    $respuesta = [
        'success' => true,
        'data' => [
            'tipo_compara' => $tipo_compara,
            'labels' => $labels,
            'datos_hoy' => $datos_hoy,
            'datos_compara' => $datos_compara,
            'totales' => [
                'hoy' => [
                    'total' => $total_hoy,
                    'camara' => $total_camara_hoy,
                    'senado' => $total_senado_hoy
                ],
                'compara' => [
                    'total' => $total_compara,
                    'camara' => $total_camara_compara,
                    'senado' => $total_senado_compara
                ],
                'diferencias' => [
                    'total' => $diferencia_total,
                    'porcentaje_total' => $porcentaje_total,
                    'camara' => $diferencia_camara,
                    'porcentaje_camara' => $porcentaje_camara,
                    'senado' => $diferencia_senado,
                    'porcentaje_senado' => $porcentaje_senado
                ]
            ]
        ]
    ];
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Error en obtener_comparativa: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}