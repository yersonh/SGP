<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/LiderModel.php'; // Solo necesitas LiderModel

header('Content-Type: application/json');

// Verificar permisos
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'Administrador') {
    echo json_encode([
        'success' => false,
        'message' => 'No tiene permisos para realizar esta acción'
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit();
}

try {
    $pdo = Database::getConnection();
    $liderModel = new LiderModel($pdo);
    
    // Validar y sanitizar datos
    $nombres = trim($_POST['nombres'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $cedula = trim($_POST['cedula'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    
    // El referenciador es OPCIONAL - tomamos el valor tal cual viene del formulario
    $id_referenciador = null;
    if (!empty($_POST['id_referenciador']) && $_POST['id_referenciador'] !== '') {
        $id_referenciador = (int)$_POST['id_referenciador'];
        // NO necesitamos verificar si existe porque ya lo validamos en el frontend
    }
    
    // Validaciones básicas
    if (empty($nombres) || empty($apellidos) || empty($cedula) || empty($telefono) || empty($correo)) {
        echo json_encode([
            'success' => false,
            'message' => 'Todos los campos obligatorios son requeridos'
        ]);
        exit();
    }
    
    // Validar cédula (solo números, 6-10 dígitos)
    $cedula = preg_replace('/\D/', '', $cedula);
    if (strlen($cedula) < 6 || strlen($cedula) > 10) {
        echo json_encode([
            'success' => false,
            'message' => 'La cédula debe tener entre 6 y 10 dígitos'
        ]);
        exit();
    }
    
    // Validar teléfono (10 dígitos)
    $telefono = preg_replace('/\D/', '', $telefono);
    if (strlen($telefono) !== 10) {
        echo json_encode([
            'success' => false, 
            'message' => 'El teléfono debe tener exactamente 10 dígitos'
        ]);
        exit();
    }
    
    // Validar email
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'success' => false,
            'message' => 'Por favor ingrese un correo electrónico válido'
        ]);
        exit();
    }
    
    // Verificar si la cédula ya existe
    if ($liderModel->cedulaExists($cedula)) {
        echo json_encode([
            'success' => false,
            'message' => 'La cédula ya está registrada en el sistema'
        ]);
        exit();
    }
    
    // ============ IMPORTANTE: Aquí está la clave ============
    // NO necesitas verificar el referenciador porque:
    // 1. Ya viene de un select con valores válidos
    // 2. Puede ser NULL (si el usuario no seleccionó nada)
    // 3. Si hay un problema con el ID, la base de datos lo manejará con la FK
    
    // Preparar datos para insertar
    $data = [
        'nombres' => $nombres,
        'apellidos' => $apellidos,
        'cc' => $cedula,
        'telefono' => $telefono,
        'correo' => $correo,
        'id_usuario' => $id_referenciador
    ];
    
    // Crear el líder
    $result = $liderModel->create($data);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Líder registrado exitosamente',
            'data' => [
                'id_lider' => $result['id_lider']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error en guardar_lider.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}