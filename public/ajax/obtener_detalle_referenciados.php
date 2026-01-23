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
    
    // Obtener detalle de referenciados usando el método del modelo
    // Nota: El método getDetalleReferenciadosPorFecha ya tiene un límite de 1000 por defecto
    $referenciados = $referenciadoModel->getDetalleReferenciadosPorFecha($fecha, 500);
    
    // Transformar los datos para que coincidan con el formato esperado
    $datos_formateados = [];
    
    foreach ($referenciados as $ref) {
        $datos_formateados[] = [
            'id_referenciado' => $ref['id_referenciado'] ?? null,
            'nombres' => $ref['nombre'] ?? '', // Tu modelo usa 'nombre', no 'nombres'
            'apellidos' => $ref['apellido'] ?? '', // Tu modelo usa 'apellido', no 'apellidos'
            'cedula' => $ref['cedula'] ?? '',
            'telefono' => $ref['telefono'] ?? '',
            'tipo_eleccion' => $ref['afinidad'] ?? '', // En tu modelo es 'afinidad' no 'tipo_eleccion'
            'compromiso' => $ref['compromiso'] ?? false,
            'vota_fuera' => $ref['vota_fuera'] ?? 'No',
            'fecha_registro' => $ref['fecha_registro'] ?? '',
            'referenciador_nombre' => $ref['referenciador_nombre'] ?? 'Sin referenciador',
            'zona_nombre' => $ref['zona_nombre'] ?? 'Sin zona',
            'sector_nombre' => $ref['sector_nombre'] ?? 'Sin sector',
            'puesto_votacion_nombre' => $ref['puesto_votacion_nombre'] ?? 'Sin puesto'
        ];
    }
    
    // Preparar respuesta
    $respuesta = [
        'success' => true,
        'data' => $datos_formateados
    ];
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Error en obtener_detalle_referenciados: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>