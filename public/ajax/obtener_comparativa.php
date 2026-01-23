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
    
    // Obtener parámetros
    $dias = $_POST['dias'] ?? 30; // Últimos 30 días por defecto
    
    // Calcular fecha de inicio
    $fecha_inicio = date('Y-m-d', strtotime("-$dias days"));
    $fecha_fin = date('Y-m-d');
    
    // Obtener datos de tendencias por día
    // NOTA: En tu modelo, "afinidad" es un número (1-5), no 'camara'/'senado'
    // Voy a cambiar la consulta para que use la afinidad numérica
    $sql = "SELECT 
                DATE(fecha_registro) as fecha,
                COUNT(*) as total,
                ROUND(AVG(afinidad)::numeric, 2) as afinidad_promedio,
                SUM(CASE WHEN compromiso = true THEN 1 ELSE 0 END) as con_compromiso,
                SUM(CASE WHEN vota_fuera = true THEN 1 ELSE 0 END) as vota_fuera
            FROM referenciados
            WHERE DATE(fecha_registro) BETWEEN :fecha_inicio AND :fecha_fin
            AND activo = true
            GROUP BY DATE(fecha_registro)
            ORDER BY fecha";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'fecha_inicio' => $fecha_inicio,
        'fecha_fin' => $fecha_fin
    ]);
    $tendencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular promedio móvil de 7 días
    $promedio_movil = [];
    $datos_array = array_values($tendencias);
    
    for ($i = 6; $i < count($datos_array); $i++) {
        $suma = 0;
        for ($j = $i - 6; $j <= $i; $j++) {
            $suma += $datos_array[$j]['total'];
        }
        $promedio_movil[] = [
            'fecha' => $datos_array[$i]['fecha'],
            'promedio' => round($suma / 7, 1)
        ];
    }
    
    // Calcular crecimiento porcentual
    $crecimiento = [];
    if (count($tendencias) >= 2) {
        for ($i = 1; $i < count($tendencias); $i++) {
            $actual = $tendencias[$i]['total'];
            $anterior = $tendencias[$i-1]['total'];
            $porcentaje = 0;
            
            if ($anterior > 0) {
                $porcentaje = round((($actual - $anterior) / $anterior) * 100, 1);
            }
            
            $crecimiento[] = [
                'fecha' => $tendencias[$i]['fecha'],
                'porcentaje' => $porcentaje
            ];
        }
    }
    
    // Preparar respuesta
    $respuesta = [
        'success' => true,
        'data' => [
            'tendencias' => $tendencias,
            'promedio_movil' => $promedio_movil,
            'crecimiento' => $crecimiento,
            'rango_fechas' => [
                'inicio' => $fecha_inicio,
                'fin' => $fecha_fin,
                'dias' => $dias
            ]
        ]
    ];
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Error en obtener_datos_tendencias: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>