<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/ReferenciadoModel.php';

// Verificar permisos (solo SuperAdmin puede desactivar/reactivar referenciados)
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'No tiene permisos para realizar esta acción'
    ]);
    exit();
}

// Establecer conexión
$pdo = Database::getConnection();
$referenciadoModel = new ReferenciadoModel($pdo);

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
    // Validar que se haya enviado la acción y el ID
    if (!isset($_POST['accion']) || !isset($_POST['id_referenciado'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Datos incompletos'
        ]);
        exit();
    }
    
    $accion = $_POST['accion'];
    $id_referenciado = intval($_POST['id_referenciado']);
    
    // Realizar la acción correspondiente
    switch ($accion) {
        case 'desactivar':
            $resultado = $referenciadoModel->desactivarReferenciado($id_referenciado);
            $mensaje = 'Referenciado desactivado correctamente';
            break;
            
        case 'reactivar':
            $resultado = $referenciadoModel->reactivarReferenciado($id_referenciado);
            $mensaje = 'Referenciado reactivado correctamente';
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Acción no válida'
            ]);
            exit();
    }
    
    if ($resultado) {
        echo json_encode([
            'success' => true,
            'message' => $mensaje,
            'accion' => $accion,
            'id_referenciado' => $id_referenciado
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No se pudo realizar la acción'
        ]);
    }
    
} catch (Exception $e) {
    error_log('Error en AJAX referenciados: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
?>