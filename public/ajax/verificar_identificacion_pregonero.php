<?php
// ajax/verificar_identificacion_pregonero.php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/PregoneroModel.php';

// Configurar cabecera para respuesta JSON
header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode([
        'success' => false,
        'message' => 'No autorizado'
    ]);
    exit();
}

// Obtener conexión
$pdo = Database::getConnection();
$pregoneroModel = new PregoneroModel($pdo);

// Recibir identificación
$identificacion = trim($_POST['identificacion'] ?? '');

if (empty($identificacion)) {
    echo json_encode([
        'success' => false,
        'message' => 'Identificación no proporcionada'
    ]);
    exit();
}

// Validar formato (solo números)
if (!preg_match('/^\d+$/', $identificacion)) {
    echo json_encode([
        'success' => false,
        'message' => 'Formato de identificación inválido'
    ]);
    exit();
}

try {
    // Verificar si la identificación ya existe con información adicional
    $datosPregonero = $pregoneroModel->getInfoPorIdentificacion($identificacion);
    
    if ($datosPregonero === false) {
        // Identificación NO existe
        echo json_encode([
            'success' => true,
            'exists' => false,
            'identificacion' => $identificacion
        ]);
    } else {
        // Identificación SÍ existe - formatear fecha
        $fecha = new DateTime($datosPregonero['fecha_registro']);
        $fechaFormateada = $fecha->format('d/m/Y H:i');
        
        echo json_encode([
            'success' => true,
            'exists' => true,
            'identificacion' => $identificacion,
            'nombres' => $datosPregonero['nombres'],
            'apellidos' => $datosPregonero['apellidos'],
            'fecha_registro' => $fechaFormateada,
            'usuario_registro' => $datosPregonero['usuario_registro'],
            'id_pregonero' => $datosPregonero['id_pregonero']
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error verificando identificación: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al verificar la identificación'
    ]);
}
?>