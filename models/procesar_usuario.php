<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UsuarioModel.php';

// Verificar permisos (solo administradores pueden agregar usuarios)
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'Administrador') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'No tiene permisos para realizar esta acción'
    ]);
    exit();
}

// Establecer conexión
$pdo = Database::getConnection();
$usuarioModel = new UsuarioModel($pdo);

// Configurar cabeceras para JSON
header('Content-Type: application/json');

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit();
}

try {
    // Validar datos obligatorios
    $requiredFields = [
        'nombres', 'apellidos', 'cedula', 'nickname', 
        'correo', 'telefono', 'tipo_usuario', 'password'
    ];
    
    $errors = [];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = "El campo '$field' es obligatorio";
        }
    }
    
    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'message' => 'Faltan campos obligatorios',
            'errors' => $errors
        ]);
        exit();
    }
    
    // Validar que las contraseñas coincidan
    if ($_POST['password'] !== $_POST['confirm_password']) {
        echo json_encode([
            'success' => false,
            'message' => 'Las contraseñas no coinciden'
        ]);
        exit();
    }
    
    // Validar cédula (solo números, 6-10 dígitos)
    $cedula = preg_replace('/\D/', '', $_POST['cedula']);
    if (strlen($cedula) < 6 || strlen($cedula) > 10) {
        echo json_encode([
            'success' => false,
            'message' => 'La cédula debe tener entre 6 y 10 dígitos'
        ]);
        exit();
    }
    
    // Validar teléfono (solo números, 10 dígitos)
    $telefono = preg_replace('/\D/', '', $_POST['telefono']);
    if (strlen($telefono) !== 10) {
        echo json_encode([
            'success' => false,
            'message' => 'El teléfono debe tener 10 dígitos'
        ]);
        exit();
    }
    
    // Validar email
    if (!filter_var($_POST['correo'], FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'success' => false,
            'message' => 'El correo electrónico no es válido'
        ]);
        exit();
    }
    
    // Validar nickname (mínimo 4 caracteres)
    if (strlen($_POST['nickname']) < 4) {
        echo json_encode([
            'success' => false,
            'message' => 'El nombre de usuario debe tener al menos 4 caracteres'
        ]);
        exit();
    }
    
    // Verificar si el nickname o cédula ya existen
    if ($usuarioModel->verificarExistencia($_POST['nickname'], $cedula)) {
        echo json_encode([
            'success' => false,
            'message' => 'El nombre de usuario o cédula ya están registrados'
        ]);
        exit();
    }
    
    // Preparar datos para el modelo
    $datosUsuario = [
        'nombres' => trim($_POST['nombres']),
        'apellidos' => trim($_POST['apellidos']),
        'cedula' => $cedula,
        'nickname' => trim($_POST['nickname']),
        'correo' => trim($_POST['correo']),
        'telefono' => $telefono,
        'tipo_usuario' => $_POST['tipo_usuario'],
        'password' => $_POST['password'], // En producción usar password_hash()
        'tope' => isset($_POST['tope']) ? intval($_POST['tope']) : 0,
        'id_zona' => !empty($_POST['zona']) ? intval($_POST['zona']) : null,
        'id_sector' => !empty($_POST['sector']) ? intval($_POST['sector']) : null,
        'id_puesto' => !empty($_POST['puesto']) ? intval($_POST['puesto']) : null,
        'activo' => true
    ];
    
    // Procesar foto si se subió
    $foto = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $foto = $_FILES['foto'];
    }
    
    // Crear usuario usando el modelo
    $id_usuario = $usuarioModel->crearUsuario($datosUsuario, $foto);
    
    // Registrar actividad (opcional - si tienes un sistema de logs)
    // $this->registrarActividad($_SESSION['id_usuario'], "creó un nuevo usuario: " . $datosUsuario['nombres']);
    
    // Obtener datos del usuario creado para la respuesta
    $usuarioCreado = $usuarioModel->getUsuarioById($id_usuario);
    
    echo json_encode([
        'success' => true,
        'message' => 'Usuario creado exitosamente',
        'usuario' => [
            'id' => $usuarioCreado['id_usuario'],
            'nombre_completo' => $usuarioCreado['nombres'] . ' ' . $usuarioCreado['apellidos'],
            'nickname' => $usuarioCreado['nickname'],
            'tipo_usuario' => $usuarioCreado['tipo_usuario'],
            'foto_url' => $usuarioCreado['foto_url']
        ],
        'redirect' => 'dashboard.php?success=usuario_creado'
    ]);
    
} catch (Exception $e) {
    error_log('Error al crear usuario: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error al crear usuario: ' . $e->getMessage()
    ]);
}
?>