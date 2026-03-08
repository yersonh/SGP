<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../models/ReferenciadoModel.php';

header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

$pdo = Database::getConnection();
$usuarioModel = new UsuarioModel($pdo);
$referenciadoModel = new ReferenciadoModel($pdo);

try {
    // Obtener todos los referenciadores activos
    $referenciadores = $usuarioModel->getReferenciadoresActivos();
    
    $data = [];
    
    foreach ($referenciadores as $ref) {
        $id_referenciador = $ref['id_usuario'];
        
        // Contar referidos ACTIVOS de este referenciador
        $totalRef = $referenciadoModel->countByReferenciador($id_referenciador);
        
        // Contar referidos ACTIVOS que ya votaron
        $votaronRef = $referenciadoModel->countVotaronByReferenciador($id_referenciador);
        
        $pendientes = $totalRef - $votaronRef;
        
        $data[] = [
            'id_usuario' => $ref['id_usuario'],
            'nombres' => $ref['nombres'],
            'apellidos' => $ref['apellidos'],
            'cedula' => $ref['cedula'] ?? '',
            'telefono' => $ref['telefono'] ?? '',
            'correo' => $ref['correo'] ?? '',
            'total' => $totalRef,
            'votaron' => $votaronRef,
            'pendientes' => $pendientes
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
    
} catch (Exception $e) {
    error_log("Error en get_referenciadores_avance: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error al cargar datos de referenciadores'
    ]);
}
?>