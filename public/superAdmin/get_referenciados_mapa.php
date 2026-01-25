<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/ReferenciadoModel.php';

header("Content-Type: application/json");
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Access-Control-Allow-Origin: *");

// Verificar si el usuario está logueado y es SuperAdmin
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

$pdo = Database::getConnection();
$referenciadoModel = new ReferenciadoModel($pdo);

try {
    // Obtener todos los referenciados activos
    $referenciados = $referenciadoModel->getAllReferenciados();
    
    // Filtrar solo los necesarios para el mapa
    $referenciadosMapeados = [];
    
    foreach ($referenciados as $ref) {
        // Solo incluir referenciados activos
        if ($ref['activo'] != 1 && $ref['activo'] != true) {
            continue;
        }
        
        // Obtener información básica
        $direccionCompleta = $ref['direccion'] ?? '';
        $barrio = $ref['barrio_nombre'] ?? '';
        
        // Construir dirección para geocodificación
        $direccionParaGeocodificar = $direccionCompleta;
        if (!empty($barrio) && $barrio !== 'Sin barrio') {
            $direccionParaGeocodificar .= ', ' . $barrio;
        }
        
        // Siempre agregar Puerto Gaitán, Meta
        $direccionParaGeocodificar .= ', Puerto Gaitán, Meta, Colombia';
        
        $referenciadosMapeados[] = [
            'id' => $ref['id_referenciado'],
            'nombre' => $ref['nombre'] . ' ' . $ref['apellido'],
            'direccion' => $direccionCompleta,
            'barrio' => $barrio,
            'direccion_completa' => $direccionParaGeocodificar,
            'telefono' => $ref['telefono'] ?? ''
        ];
    }
    
    echo json_encode([
        'success' => true,
        'total' => count($referenciadosMapeados),
        'referenciados' => $referenciadosMapeados
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => true,
        'message' => 'Error al obtener referenciados'
    ]);
}
?>