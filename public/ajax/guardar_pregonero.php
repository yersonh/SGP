<?php
// guardar_pregonero.php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/PregoneroModel.php';

// Configurar cabecera para respuesta JSON
header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'CarguePregoneros') {
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
        'id_usuario_registro' => $_SESSION['id_usuario']
    ];
    
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
    
    // Validar que la identificación no exista
    if ($pregoneroModel->existeIdentificacion($datos['identificacion'])) {
        $errores[] = 'Ya existe un pregonero registrado con esta identificación';
    }
    
    // Si hay errores, responder con error
    if (!empty($errores)) {
        echo json_encode([
            'success' => false,
            'message' => implode('. ', $errores)
        ]);
        exit();
    }
    
    // Insertar en la base de datos
    $id_pregonero = $pregoneroModel->insertar($datos);
    
    if ($id_pregonero) {
        echo json_encode([
            'success' => true,
            'message' => 'Pregonero registrado exitosamente',
            'id_pregonero' => $id_pregonero
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al guardar el pregonero en la base de datos'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>