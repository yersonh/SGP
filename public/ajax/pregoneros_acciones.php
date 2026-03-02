<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/PregoneroModel.php';

header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$pdo = Database::getConnection();
$pregoneroModel = new PregoneroModel($pdo);

// Obtener acción
$accion = $_POST['accion'] ?? '';

switch ($accion) {
    case 'desactivar':
        desactivarPregonero($pregoneroModel, $_POST);
        break;
    case 'reactivar':
        reactivarPregonero($pregoneroModel, $_POST);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

function desactivarPregonero($model, $post) {
    $id_pregonero = $post['id_pregonero'] ?? 0;
    
    if (!$id_pregonero) {
        echo json_encode(['success' => false, 'message' => 'ID de pregonero no proporcionado']);
        return;
    }
    
    try {
        $resultado = $model->eliminar($id_pregonero); // eliminar hace UPDATE activo = FALSE
        
        if ($resultado) {
            echo json_encode(['success' => true, 'message' => 'Pregonero desactivado correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo desactivar el pregonero']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function reactivarPregonero($model, $post) {
    $id_pregonero = $post['id_pregonero'] ?? 0;
    
    if (!$id_pregonero) {
        echo json_encode(['success' => false, 'message' => 'ID de pregonero no proporcionado']);
        return;
    }
    
    try {
        // Necesitas agregar este método al modelo o hacer la consulta directa
        // Por ahora lo hacemos con consulta directa
        $sql = "UPDATE public.pregonero SET activo = TRUE WHERE id_pregonero = :id_pregonero";
        $stmt = $model->pdo->prepare($sql);
        $resultado = $stmt->execute([':id_pregonero' => $id_pregonero]);
        
        if ($resultado) {
            echo json_encode(['success' => true, 'message' => 'Pregonero reactivado correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo reactivar el pregonero']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}