<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/ReferenciadoModel.php';

// Verificar sesión
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$pdo = Database::getConnection();
$referenciadoModel = new ReferenciadoModel($pdo);

// Recoger datos del formulario
$data = [
    'nombre' => $_POST['nombre'] ?? '',
    'apellido' => $_POST['apellido'] ?? '',
    'cedula' => $_POST['cedula'] ?? '',
    'direccion' => $_POST['direccion'] ?? '',
    'email' => $_POST['email'] ?? '',
    'telefono' => $_POST['telefono'] ?? '',
    'afinidad' => $_POST['afinidad'] ?? 0,
    'id_zona' => $_POST['zona'] ?? null,
    'id_sector' => $_POST['sector'] ?? null,
    'id_puesto_votacion' => $_POST['puesto_votacion'] ?? null,
    'mesa' => $_POST['mesa'] ?? null,
    'id_departamento' => $_POST['departamento'] ?? null,
    'id_municipio' => $_POST['municipio'] ?? null,
    'id_oferta_apoyo' => $_POST['apoyo'] ?? null,
    'id_grupo_poblacional' => $_POST['grupo_poblacional'] ?? null,
    'compromiso' => $_POST['compromiso'] ?? null,
    'id_referenciador' => $_POST['id_referenciador'] ?? $_SESSION['id_usuario']
];

// Validaciones básicas
if (empty($data['nombre']) || empty($data['apellido']) || empty($data['cedula'])) {
    echo json_encode(['success' => false, 'message' => 'Campos obligatorios faltantes']);
    exit();
}

try {
    $resultado = $referenciadoModel->guardarReferenciado($data);
    
    if ($resultado) {
        echo json_encode(['success' => true, 'message' => 'Referenciado guardado exitosamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar el referenciado']);
    }
} catch (Exception $e) {
    // Verificar si es error de duplicado de cédula
    if (strpos($e->getMessage(), 'duplicate key') !== false || 
        strpos($e->getMessage(), 'Duplicate entry') !== false) {
        echo json_encode(['success' => false, 'message' => 'La cédula ya está registrada']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error del sistema: ' . $e->getMessage()]);
    }
}
?>