<?php
// public/ajax/usuario.php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../controllers/UsuarioController.php';

// Configurar cabeceras JSON
header('Content-Type: application/json');

// Verificar que sea una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

// Verificar sesión
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit();
}

// Obtener conexión y controlador
$pdo = Database::getConnection();
$controller = new UsuarioController($pdo);

// Obtener acción
$accion = $_POST['accion'] ?? '';
$id_usuario = $_POST['id_usuario'] ?? '';
$id_admin = $_SESSION['id_usuario'];

// Procesar acciones
switch ($accion) {
    case 'desactivar':
        if (empty($id_usuario)) {
            echo json_encode(['success' => false, 'message' => 'ID de usuario requerido']);
            exit();
        }
        $resultado = $controller->desactivar($id_usuario, $id_admin);
        echo json_encode($resultado);
        break;
        
    case 'reactivar':
        if (empty($id_usuario)) {
            echo json_encode(['success' => false, 'message' => 'ID de usuario requerido']);
            exit();
        }
        $resultado = $controller->reactivar($id_usuario, $id_admin);
        echo json_encode($resultado);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no reconocida']);
        break;
}
?>