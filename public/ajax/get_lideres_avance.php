<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/LiderModel.php';
require_once __DIR__ . '/../../models/ReferenciadoModel.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';

header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

$pdo = Database::getConnection();
$liderModel = new LiderModel($pdo);
$referenciadoModel = new ReferenciadoModel($pdo);
$usuarioModel = new UsuarioModel($pdo);

try {
    $id_referenciador = isset($_GET['id_referenciador']) ? (int)$_GET['id_referenciador'] : 0;
    
    if ($id_referenciador <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID de referenciador no válido']);
        exit();
    }
    
    // Obtener datos del referenciador
    $referenciador = $usuarioModel->getUsuarioById($id_referenciador);
    
    if (!$referenciador) {
        echo json_encode(['success' => false, 'error' => 'Referenciador no encontrado']);
        exit();
    }
    
    // Obtener líderes ACTIVOS del referenciador
    $lideres = $liderModel->getActivosByReferenciador($id_referenciador);
    
    $data = [];
    
    foreach ($lideres as $lider) {
        $id_lider = $lider['id_lider'];
        
        // Contar referidos ACTIVOS de este líder
        $totalRef = $referenciadoModel->countByLider($id_lider);
        
        // Contar referidos ACTIVOS que ya votaron
        $votaronRef = $referenciadoModel->countVotaronByLider($id_lider);
        
        $pendientes = $totalRef - $votaronRef;
        
        $data[] = [
            'id_lider' => $lider['id_lider'],
            'nombres' => $lider['nombres'],
            'apellidos' => $lider['apellidos'],
            'cedula' => $lider['cc'] ?? '',
            'telefono' => $lider['telefono'] ?? '',
            'total' => $totalRef,
            'votaron' => $votaronRef,
            'pendientes' => $pendientes
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'referenciador' => [
            'id_usuario' => $referenciador['id_usuario'],
            'nombres' => $referenciador['nombres'],
            'apellidos' => $referenciador['apellidos'],
            'cedula' => $referenciador['cedula'] ?? ''
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error en get_lideres_avance: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error al cargar datos de líderes'
    ]);
}
?>