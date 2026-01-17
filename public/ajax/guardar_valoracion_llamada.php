<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/LlamadaModel.php';

header('Content-Type: application/json');

// Verificar autenticación
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode([
        'success' => false,
        'message' => 'No autorizado. Por favor, inicie sesión.'
    ]);
    exit();
}

// Verificar que sea una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit();
}

// Obtener datos JSON
$input = json_decode(file_get_contents('php://input'), true);

// Si no viene JSON, intentar con POST normal
if ($input === null) {
    $input = $_POST;
}

// Validar datos mínimos
if (!isset($input['id_referenciado']) || !isset($input['rating'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Datos incompletos. Se requiere id_referenciado y rating.'
    ]);
    exit();
}

try {
    $pdo = Database::getConnection();
    $llamadaModel = new LlamadaModel($pdo);
    
    // Preparar datos para guardar
    $datosLlamada = [
        'id_referenciado' => intval($input['id_referenciado']),
        'id_usuario' => $_SESSION['id_usuario'],
        'id_resultado' => isset($input['id_resultado']) ? intval($input['id_resultado']) : 1,
        'telefono' => $input['telefono'] ?? '',
        'rating' => intval($input['rating']),
        'observaciones' => $input['observaciones'] ?? '',
        'fecha_llamada' => $input['fecha_llamada'] ?? date('Y-m-d H:i:s')
    ];
    
    // Validar rating (1-5)
    if ($datosLlamada['rating'] < 1 || $datosLlamada['rating'] > 5) {
        echo json_encode([
            'success' => false,
            'message' => 'El rating debe estar entre 1 y 5.'
        ]);
        exit();
    }
    
    // Validar id_resultado (1-7 según la tabla)
    if ($datosLlamada['id_resultado'] < 1 || $datosLlamada['id_resultado'] > 7) {
        echo json_encode([
            'success' => false,
            'message' => 'El resultado seleccionado no es válido.'
        ]);
        exit();
    }
    
    // Guardar la valoración
    $id_llamada = $llamadaModel->guardarValoracionLlamada($datosLlamada);
    
    if ($id_llamada) {
        echo json_encode([
            'success' => true,
            'message' => 'Valoración guardada exitosamente.',
            'id_llamada' => $id_llamada,
            'data' => $datosLlamada
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al guardar la valoración.'
        ]);
    }
    
} catch (Exception $e) {
    error_log('Error en guardar_valoracion_llamada.php: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
?>