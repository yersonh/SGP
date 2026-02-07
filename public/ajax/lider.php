<?php
session_start();

// Desactivar la salida de errores para no interferir con JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/LiderModel.php';

// Buffer de salida para capturar cualquier contenido no deseado
ob_start();

try {
    // Verificar si el usuario está logueado y es administrador
    if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'Administrador') {
        throw new Exception('Acceso no autorizado');
    }

    // Verificar que sea una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $pdo = Database::getConnection();
    $model = new LiderModel($pdo);

    // Determinar la acción a realizar
    $accion = $_POST['accion'] ?? '';
    
    // Limpiar buffer antes de enviar JSON
    ob_clean();
    
    header('Content-Type: application/json');
    
    switch ($accion) {
        case 'desactivar':
            desactivarLider($model);
            break;
            
        case 'reactivar':
            reactivarLider($model);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
    
} catch (Exception $e) {
    // Limpiar buffer si hay error
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    // Limpiar cualquier buffer restante
    if (ob_get_level() > 0) {
        ob_end_flush();
    }
}

function desactivarLider($model) {
    try {
        if (!isset($_POST['id_lider']) || empty($_POST['id_lider'])) {
            throw new Exception('ID de líder no proporcionado');
        }
        
        $id_lider = intval($_POST['id_lider']);
        
        // DEBUG: Log para verificar
        error_log("Desactivar líder ID: $id_lider");
        
        // Usar changeStatus con el valor como string 'false' que será convertido a booleano
        $result = $model->changeStatus($id_lider, 'false');
        
        if ($result['success']) {
            if ($result['affected_rows'] > 0) {
                echo json_encode(['success' => true, 'message' => 'Líder dado de baja correctamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se encontró el líder o ya estaba inactivo']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => $result['message']]);
        }
        
    } catch (Exception $e) {
        error_log("Error en desactivarLider: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function reactivarLider($model) {
    try {
        if (!isset($_POST['id_lider']) || empty($_POST['id_lider'])) {
            throw new Exception('ID de líder no proporcionado');
        }
        
        $id_lider = intval($_POST['id_lider']);
        
        // DEBUG: Log para verificar
        error_log("Reactivar líder ID: $id_lider");
        
        // Usar changeStatus con el valor como string 'true' que será convertido a booleano
        $result = $model->changeStatus($id_lider, 'true');
        
        if ($result['success']) {
            if ($result['affected_rows'] > 0) {
                echo json_encode(['success' => true, 'message' => 'Líder reactivado correctamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se encontró el líder o ya estaba activo']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => $result['message']]);
        }
        
    } catch (Exception $e) {
        error_log("Error en reactivarLider: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>