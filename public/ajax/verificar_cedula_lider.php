<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/LiderModel.php';

// Verificar sesión
if (!isset($_SESSION['id_usuario'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

// Obtener conexión
$pdo = Database::getConnection();
$liderModel = new LiderModel($pdo);

// Recibir cédula
$cedula = trim($_POST['cedula'] ?? '');

if (empty($cedula)) {
    echo json_encode(['success' => false, 'message' => 'Cédula no proporcionada']);
    exit();
}

// Validar formato (solo números)
if (!preg_match('/^\d+$/', $cedula)) {
    echo json_encode(['success' => false, 'message' => 'Formato de cédula inválido']);
    exit();
}

try {
    // Verificar si la cédula ya existe en líderes
    $lider = $liderModel->getByCedula($cedula);
    
    if (!$lider) {
        // Cédula NO existe en líderes
        echo json_encode([
            'success' => true,
            'exists' => false,
            'cedula' => $cedula
        ]);
    } else {
        // Cédula SÍ existe en líderes - formatear fecha y obtener información del referenciador
        $infoCompleta = $liderModel->getById($lider['id_lider']);
        
        $fecha = new DateTime($lider['fecha_creacion']);
        $fechaFormateada = $fecha->format('d/m/Y');
        
        $response = [
            'success' => true,
            'exists' => true,
            'cedula' => $cedula,
            'lider_nombre' => $lider['nombres'] . ' ' . $lider['apellidos'],
            'fecha_registro' => $fechaFormateada,
            'id_lider' => $lider['id_lider'],
            'estado' => $lider['estado'] ? 'Activo' : 'Inactivo'
        ];
        
        // Agregar información del referenciador si existe
        if ($infoCompleta && !empty($infoCompleta['referenciador_nombre'])) {
            $response['referenciador_nombre'] = $infoCompleta['referenciador_nombre'];
            $response['id_usuario'] = $infoCompleta['id_usuario'];
        }
        
        // También verificar si la cédula existe en referenciados (opcional)
        // Para ofrecer información más completa
        try {
            require_once __DIR__ . '/../../models/ReferenciadoModel.php';
            $referenciadoModel = new ReferenciadoModel($pdo);
            $datosCedulaReferenciado = $referenciadoModel->cedulaExiste($cedula);
            
            if ($datosCedulaReferenciado) {
                $fechaReferenciado = new DateTime($datosCedulaReferenciado['fecha_registro']);
                $response['tambien_en_referenciados'] = true;
                $response['referenciado_info'] = [
                    'fecha_registro' => $fechaReferenciado->format('d/m/Y'),
                    'referenciador' => $datosCedulaReferenciado['referenciador_nombre']
                ];
            }
        } catch (Exception $e) {
            // Silenciar error si el modelo de referenciado no existe
            error_log("Info: No se pudo verificar cédula en referenciados: " . $e->getMessage());
        }
        
        echo json_encode($response);
    }
    
} catch (Exception $e) {
    error_log("Error verificando cédula de líder: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al verificar la cédula']);
}

header('Content-Type: application/json');
?>