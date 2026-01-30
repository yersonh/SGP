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

try {
    $pdo = Database::getConnection();
    $puntoModel = new PuntoMapaModel($pdo);
    
    // Obtener puntos del usuario
    $puntos = $puntoModel->obtenerPuntosPorUsuario($_SESSION['id_usuario']);
    
    echo json_encode(['success' => true, 'puntos' => $puntos]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}