<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/ReferenciadoModel.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$pdo = Database::getConnection();
$referenciadoModel = new ReferenciadoModel($pdo);

// Obtener parámetros
$id_referenciador = isset($_GET['id_referenciador']) ? intval($_GET['id_referenciador']) : null;

// Validar parámetros
if (!$id_referenciador) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'ID de referenciador requerido']);
    exit();
}

// Obtener referenciados del referenciador específico
try {
    $referenciados = $referenciadoModel->getReferenciadosByUsuarioActivo($id_referenciador);
    
    // Limpiar datos para evitar problemas con JSON
    $referenciados_limpios = [];
    foreach ($referenciados as $ref) {
        $referenciados_limpios[] = [
            'id_referenciado' => $ref['id_referenciado'] ?? '',
            'nombre' => $ref['nombre'] ?? '',
            'apellido' => $ref['apellido'] ?? '',
            'cedula' => $ref['cedula'] ?? '',
            'direccion' => $ref['direccion'] ?? '',
            'telefono' => $ref['telefono'] ?? '',
            'email' => $ref['email'] ?? '',
            'afinidad' => $ref['afinidad'] ?? 0,
            'compromiso' => $ref['compromiso'] ?? '',
            'vota_fuera' => $ref['vota_fuera'] ?? 'No',
            'fecha_registro' => $ref['fecha_registro'] ?? '',
            'zona_nombre' => $ref['zona_nombre'] ?? '',
            'sector_nombre' => $ref['sector_nombre'] ?? '',
            'puesto_votacion_display' => $ref['puesto_votacion_display'] ?? '',
            'mesa_display' => $ref['mesa_display'] ?? '',
            'grupo_nombre' => $ref['grupo_nombre'] ?? ''
        ];
    }
    
    echo json_encode([
        'success' => true,
        'referenciados' => $referenciados_limpios,
        'total' => count($referenciados_limpios)
    ]);
    
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener referenciados: ' . $e->getMessage()
    ]);
}
?>