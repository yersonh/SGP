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
    'id_barrio' => !empty($_POST['barrio']) ? intval($_POST['barrio']) : null, // NUEVO
    'id_oferta_apoyo' => !empty($_POST['apoyo']) ? intval($_POST['apoyo']) : null,
    'id_grupo_poblacional' => !empty($_POST['grupo_poblacional']) ? intval($_POST['grupo_poblacional']) : null,
    'compromiso' => trim($_POST['compromiso'] ?? ''),
    'id_referenciador' => $_POST['id_referenciador'] ?? $_SESSION['id_usuario'],
    'insumos' => $_POST['insumos'] ?? [] // NUEVO: Array de insumos seleccionados
];

// Debug: Ver qué datos llegan
error_log("Datos recibidos: " . print_r($data, true));
error_log("Insumos recibidos: " . print_r($data['insumos'], true));

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

// Validar cédula (solo números)
if (!empty($data['cedula']) && !preg_match('/^\d+$/', $data['cedula'])) {
    $errors[] = 'La cédula solo debe contener números';
}

// Validar email
if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'El email no tiene un formato válido';
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

// Verificar si barrio existe (si se seleccionó)
if (!empty($data['id_barrio'])) {
    try {
        $stmt = $pdo->prepare("SELECT id_barrio FROM barrio WHERE id_barrio = ? AND activo = true");
        $stmt->execute([$data['id_barrio']]);
        if (!$stmt->fetch()) {
            $data['id_barrio'] = null; // Si no existe, establecer como null
        }
    } catch (Exception $e) {
        error_log("Error verificando barrio: " . $e->getMessage());
        $data['id_barrio'] = null;
    }
}

// Verificar insumos válidos
$insumosValidos = ['carro', 'caballo', 'cicla', 'moto', 'motocarro', 'publicidad'];
if (!empty($data['insumos']) && is_array($data['insumos'])) {
    // Filtrar solo insumos válidos
    $data['insumos'] = array_filter($data['insumos'], function($insumo) use ($insumosValidos) {
        return in_array($insumo, $insumosValidos);
    });
    
    // Si quedan insumos válidos, convertir a array numérico
    if (!empty($data['insumos'])) {
        $data['insumos'] = array_values($data['insumos']);
    }
}

try {
    // Guardar el referenciado (ahora retorna el ID en lugar de true/false)
    $id_referenciado = $referenciadoModel->guardarReferenciado($data);
    
    if ($id_referenciado) {
        $mensaje = 'Referenciado guardado exitosamente';
        
        // Agregar info sobre insumos guardados
        if (!empty($data['insumos']) && is_array($data['insumos'])) {
            $mensaje .= ' con ' . count($data['insumos']) . ' insumo(s)';
        }
        
        echo json_encode([
            'success' => true, 
            'message' => $mensaje,
            'id_referenciado' => $id_referenciado
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar el referenciado']);
    }
} catch (Exception $e) {
    error_log("Error en guardar_referenciado: " . $e->getMessage());
    
    // Verificar si es error de duplicado de cédula
    $errorMessage = $e->getMessage();
    if (strpos($errorMessage, 'duplicate key') !== false || 
        strpos($errorMessage, 'Duplicate entry') !== false ||
        strpos($errorMessage, '23505') !== false ||
        strpos($errorMessage, 'cedula') !== false) {
        
        echo json_encode(['success' => false, 'message' => 'La cédula ya está registrada']);
    } else {
        // Mensaje más amigable para el usuario
        echo json_encode(['success' => false, 'message' => 'Error al guardar el registro. Por favor intente nuevamente.']);
    }
}

header('Content-Type: application/json');
?>