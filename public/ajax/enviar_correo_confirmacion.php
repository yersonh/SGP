<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../models/BarrioModel.php';
require_once __DIR__ . '/../../models/ReferenciadoModel.php';
require_once __DIR__ . '/../../models/LiderModel.php'; // ← NUEVO: Incluir modelo de líder
require_once __DIR__ . '/../../lib/BrevoEmail.php';

header('Content-Type: application/json');

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

// Verificar que se hayan enviado los datos necesarios
if (!isset($_POST['email']) || !isset($_POST['nombre']) || !isset($_POST['apellido']) || !isset($_POST['cedula'])) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit();
}

try {
    $pdo = Database::getConnection();
    $usuarioModel = new UsuarioModel($pdo);
    $barrioModel = new BarrioModel($pdo);
    $referenciadoModel = new ReferenciadoModel($pdo);
    $liderModel = new LiderModel($pdo); // ← NUEVO: Instanciar modelo de líder
    
    // Obtener datos del referenciador (usuario logueado)
    $id_usuario_logueado = $_SESSION['id_usuario'];
    $referenciador = $usuarioModel->getUsuarioById($id_usuario_logueado);
    
    if (!$referenciador) {
        throw new Exception('Referenciador no encontrado');
    }
    
    // Obtener la fecha de registro desde el modelo
    $cedula = $_POST['cedula'];
    $fechaRegistro = $referenciadoModel->getFechaRegistroByCedula($cedula);
    
    // Si no encontramos fecha, usar hora actual como fallback
    if (!$fechaRegistro) {
        $fechaRegistro = date('d/m/Y H:i:s');
    }
    
    // Obtener datos del líder si se proporcionó
    $lider = null;
    $id_lider = isset($_POST['id_lider']) && !empty($_POST['id_lider']) ? $_POST['id_lider'] : null;
    
    if ($id_lider) {
        $lider = $liderModel->getById($id_lider);
        error_log("📋 Líder encontrado para correo: " . ($lider ? $lider['nombres'] . ' ' . $lider['apellidos'] : 'No encontrado'));
    } else {
        error_log("📋 No se proporcionó ID de líder para el correo");
    }
    
    // Preparar datos del referido
    $referido = [
        'nombre' => $_POST['nombre'],
        'apellido' => $_POST['apellido'],
        'cedula' => $_POST['cedula'],
        'email' => $_POST['email'],
        'telefono' => $_POST['telefono'] ?? '',
        'direccion' => $_POST['direccion'] ?? '',
        'afinidad' => $_POST['afinidad'] ?? '0',
        'fecha_registro' => $fechaRegistro,
        'id_lider' => $id_lider // ← NUEVO: Incluir ID del líder en los datos del referido
    ];
    
    // Si tenemos el ID del barrio, obtener el nombre
    if (!empty($_POST['barrio']) && is_numeric($_POST['barrio'])) {
        $barrioInfo = $barrioModel->getById($_POST['barrio']);
        if ($barrioInfo) {
            $referido['barrio_nombre'] = $barrioInfo['nombre'];
        }
    }
    
    // Enviar correo de confirmación con los datos del líder
    $brevo = new BrevoEmail();
    // Pasar el líder como tercer parámetro
    $resultado = $brevo->enviarConfirmacionRegistro($referido, $referenciador, $lider);
    
    if ($resultado['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Correo enviado exitosamente',
            'message_id' => $resultado['message_id'] ?? null,
            'lider_incluido' => $lider ? true : false // Opcional: para depuración
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => $resultado['error'] ?? 'Error al enviar correo'
        ]);
    }
    
} catch (Exception $e) {
    error_log('Error enviando correo: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>