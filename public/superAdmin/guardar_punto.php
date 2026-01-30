<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/PuntoMapaModel.php';

header('Content-Type: application/json');

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

// Obtener datos del POST
$input = json_decode(file_get_contents('php://input'), true);

// Validar datos
if (empty($input['nombre']) || !isset($input['latitud']) || !isset($input['longitud'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit();
}

try {
    $pdo = Database::getConnection();
    $puntoModel = new PuntoMapaModel($pdo);
    
    // Preparar datos
    $data = [
        'nombre' => trim($input['nombre']),
        'descripcion' => isset($input['descripcion']) ? trim($input['descripcion']) : null,
        'latitud' => (float) $input['latitud'],
        'longitud' => (float) $input['longitud'],
        'tipo_punto' => $input['tipo_punto'] ?? 'general',
        'color_marcador' => $input['color_marcador'] ?? '#4fc3f7',
        'id_usuario' => $_SESSION['id_usuario']
    ];
    
    // Si hay ID, actualizar; si no, crear
    if (!empty($input['puntoId'])) {
        $success = $puntoModel->actualizarPunto($input['puntoId'], $data);
        $message = $success ? 'Punto actualizado correctamente' : 'Error al actualizar el punto';
        $puntoId = $input['puntoId'];
    } else {
        $puntoId = $puntoModel->crearPunto($data);
        $success = ($puntoId !== false);
        $message = $success ? 'Punto creado correctamente' : 'Error al crear el punto';
    }
    
    if ($success) {
        echo json_encode([
            'success' => true, 
            'message' => $message,
            'puntoId' => $puntoId
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => $message]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}