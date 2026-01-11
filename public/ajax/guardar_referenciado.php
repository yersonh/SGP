<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/ReferenciadoModel.php';

// Verificar sesión
if (!isset($_SESSION['id_usuario'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

// Obtener conexión
$pdo = Database::getConnection();
$referenciadoModel = new ReferenciadoModel($pdo);

// Recoger y sanitizar datos del formulario
$data = [
    'nombre' => trim($_POST['nombre'] ?? ''),
    'apellido' => trim($_POST['apellido'] ?? ''),
    'cedula' => trim($_POST['cedula'] ?? ''),
    'direccion' => trim($_POST['direccion'] ?? ''),
    'email' => trim($_POST['email'] ?? ''),
    'telefono' => trim($_POST['telefono'] ?? ''),
    'afinidad' => intval($_POST['afinidad'] ?? 0),
    'id_zona' => !empty($_POST['zona']) ? intval($_POST['zona']) : null,
    'id_sector' => !empty($_POST['sector']) ? intval($_POST['sector']) : null,
    'id_puesto_votacion' => !empty($_POST['puesto_votacion']) ? intval($_POST['puesto_votacion']) : null,
    'mesa' => !empty($_POST['mesa']) ? intval($_POST['mesa']) : null,
    'id_departamento' => !empty($_POST['departamento']) ? intval($_POST['departamento']) : null,
    'id_municipio' => !empty($_POST['municipio']) ? intval($_POST['municipio']) : null,
    'id_oferta_apoyo' => !empty($_POST['apoyo']) ? intval($_POST['apoyo']) : null,
    'id_grupo_poblacional' => !empty($_POST['grupo_poblacional']) ? intval($_POST['grupo_poblacional']) : null,
    'compromiso' => trim($_POST['compromiso'] ?? ''),
    'id_referenciador' => $_POST['id_referenciador'] ?? $_SESSION['id_usuario']
];

// Validaciones básicas
$errors = [];
if (empty($data['nombre'])) $errors[] = 'El nombre es obligatorio';
if (empty($data['apellido'])) $errors[] = 'El apellido es obligatorio';
if (empty($data['cedula'])) $errors[] = 'La cédula es obligatoria';
if (empty($data['direccion'])) $errors[] = 'La dirección es obligatoria';
if (empty($data['email'])) $errors[] = 'El email es obligatorio';
if (empty($data['telefono'])) $errors[] = 'El teléfono es obligatorio';

// Validar afinidad (DEBE ser entre 1 y 5 según la tabla)
if ($data['afinidad'] < 1 || $data['afinidad'] > 5) {
    $errors[] = 'La afinidad debe estar entre 1 y 5';
}

// Si hay errores, retornarlos
if (!empty($errors)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit();
}

// Validar que el usuario referenciador existe
try {
    $stmt = $pdo->prepare("SELECT id_usuario, tipo_usuario FROM usuario WHERE id_usuario = ?");
    $stmt->execute([$data['id_referenciador']]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        echo json_encode(['success' => false, 'message' => 'Usuario referenciador no encontrado']);
        exit();
    }
    
    if ($usuario['tipo_usuario'] !== 'Referenciador') {
        echo json_encode(['success' => false, 'message' => 'Usuario no autorizado para referenciar']);
        exit();
    }
    
} catch (Exception $e) {
    error_log("Error verificando usuario: " . $e->getMessage());
}

try {
    $resultado = $referenciadoModel->guardarReferenciado($data);
    
    if ($resultado) {
        echo json_encode(['success' => true, 'message' => 'Referenciado guardado exitosamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar el referenciado']);
    }
} catch (Exception $e) {
    error_log("Error en guardar_referenciado: " . $e->getMessage());
    
    // Verificar si es error de duplicado de cédula
    $errorMessage = $e->getMessage();
    if (strpos($errorMessage, 'duplicate key') !== false || 
        strpos($errorMessage, 'Duplicate entry') !== false ||
        strpos($errorMessage, '23505') !== false) {
        echo json_encode(['success' => false, 'message' => 'La cédula ya está registrada']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error del sistema: ' . $errorMessage]);
    }
}

header('Content-Type: application/json');
?>