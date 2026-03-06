<?php
// ajax/actualizar_pregonero.php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/PregoneroModel.php';

// Configurar cabecera para respuesta JSON
header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    echo json_encode([
        'success' => false,
        'message' => 'No tiene permisos para realizar esta acción'
    ]);
    exit();
}

// Verificar que los datos fueron enviados
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit();
}

try {
    $pdo = Database::getConnection();
    $pregoneroModel = new PregoneroModel($pdo);
    
    // Verificar que se haya proporcionado el ID
    if (empty($_POST['id_pregonero'])) {
        echo json_encode([
            'success' => false,
            'message' => 'ID de pregonero no proporcionado'
        ]);
        exit();
    }
    
    // Recoger y validar datos del formulario
    $datos = [
        'nombres' => trim($_POST['nombres'] ?? ''),
        'apellidos' => trim($_POST['apellidos'] ?? ''),
        'identificacion' => trim($_POST['identificacion'] ?? ''),
        'telefono' => trim($_POST['telefono'] ?? ''),
        'id_barrio' => intval($_POST['barrio'] ?? 0),
        'corregimiento' => trim($_POST['corregimiento'] ?? ''),
        'comuna' => trim($_POST['comuna'] ?? ''),
        'id_puesto' => intval($_POST['puesto'] ?? 0),
        'mesa' => intval($_POST['mesa'] ?? 0),
        'quien_reporta' => trim($_POST['quien_reporta'] ?? ''),
        'id_referenciador' => !empty($_POST['id_referenciador']) ? intval($_POST['id_referenciador']) : null
    ];
    
    $id_pregonero = intval($_POST['id_pregonero']);
    
    // Validaciones básicas
    $errores = [];
    
    if (empty($datos['nombres'])) $errores[] = 'El campo Nombres es obligatorio';
    if (empty($datos['apellidos'])) $errores[] = 'El campo Apellidos es obligatorio';
    if (empty($datos['identificacion'])) $errores[] = 'El campo Identificación es obligatorio';
    if (empty($datos['telefono'])) $errores[] = 'El campo Teléfono es obligatorio';
    if ($datos['id_barrio'] <= 0) $errores[] = 'Debe seleccionar un barrio válido';
    if (empty($datos['corregimiento'])) $errores[] = 'El campo Corregimiento es obligatorio';
    if (empty($datos['comuna'])) $errores[] = 'El campo Comuna es obligatorio';
    if ($datos['id_puesto'] <= 0) $errores[] = 'Debe seleccionar un puesto de votación válido';
    if ($datos['mesa'] < 1 || $datos['mesa'] > 60) $errores[] = 'La mesa debe ser un número entre 1 y 60';
    if (empty($datos['id_referenciador'])) $errores[] = 'Debe seleccionar un referenciador';
    
    // Validar formato de identificación (solo números, 6-10 dígitos)
    $identificacion = preg_replace('/\D/', '', $datos['identificacion']);
    if (strlen($identificacion) < 6 || strlen($identificacion) > 10) {
        $errores[] = 'La identificación debe tener entre 6 y 10 dígitos';
    }
    
    // Si hay errores, responder con error
    if (!empty($errores)) {
        echo json_encode([
            'success' => false,
            'message' => implode('. ', $errores)
        ]);
        exit();
    }
    
    // Actualizar en la base de datos
    $resultado = $pregoneroModel->actualizar($id_pregonero, $datos);
    
    if ($resultado) {
        echo json_encode([
            'success' => true,
            'message' => 'Pregonero actualizado exitosamente',
            'id_pregonero' => $id_pregonero
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al actualizar el pregonero en la base de datos'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>