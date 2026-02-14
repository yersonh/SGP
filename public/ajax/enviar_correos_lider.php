<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../lib/BrevoEmail.php';

header('Content-Type: application/json');

// Verificar si el usuario está logueado y es administrador
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'Administrador') {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

// Verificar que se hayan enviado los datos necesarios
if (!isset($_POST['email_lider']) || !isset($_POST['nombres_lider']) || 
    !isset($_POST['apellidos_lider']) || !isset($_POST['cedula_lider'])) {
    echo json_encode(['success' => false, 'error' => 'Datos del líder incompletos']);
    exit();
}

try {
    $pdo = Database::getConnection();
    $usuarioModel = new UsuarioModel($pdo);
    
    // Obtener datos del administrador que está registrando
    $id_usuario_logueado = $_SESSION['id_usuario'];
    $administrador = $usuarioModel->getUsuarioById($id_usuario_logueado);
    
    if (!$administrador) {
        throw new Exception('Administrador no encontrado');
    }
    
    // Obtener información del referenciador asignado (si existe)
    $referenciadorInfo = null;
    $correos_enviados = [];
    $resultados = [];
    
    if (!empty($_POST['id_referenciador'])) {
        $referenciadorInfo = $usuarioModel->getUsuarioById($_POST['id_referenciador']);
    }
    
    // Preparar datos del líder
    $lider = [
        'nombre' => $_POST['nombres_lider'],
        'apellido' => $_POST['apellidos_lider'],
        'cedula' => $_POST['cedula_lider'],
        'email' => $_POST['email_lider'],
        'telefono' => $_POST['telefono_lider'] ?? '',
        'fecha_registro' => date('d/m/Y H:i:s'),
        'tipo_usuario' => 'Lider'
    ];
    
    // Agregar información del coordinador si existe
    if ($referenciadorInfo) {
        $lider['coordinador_nombre'] = $referenciadorInfo['nombres'] . ' ' . $referenciadorInfo['apellidos'];
        $lider['coordinador_email'] = $referenciadorInfo['correo'] ?? 'No disponible';
        $lider['coordinador_telefono'] = $referenciadorInfo['telefono'] ?? 'No disponible';
    } else {
        $lider['coordinador_nombre'] = 'No asignado';
        $lider['coordinador_email'] = 'No disponible';
        $lider['coordinador_telefono'] = 'No disponible';
    }
    
    $brevo = new BrevoEmail();
    
    // 1. Enviar correo al LÍDER
    try {
        $resultadoLider = $brevo->enviarConfirmacionRegistroLider($lider, $administrador);
        if ($resultadoLider['success']) {
            $correos_enviados[] = 'Líder';
            $resultados['lider'] = $resultadoLider;
            error_log("✅ Correo enviado a líder: " . $lider['email']);
        } else {
            $resultados['lider'] = $resultadoLider;
            error_log("❌ Error enviando a líder: " . ($resultadoLider['error'] ?? 'Desconocido'));
        }
    } catch (Exception $e) {
        error_log("❌ Excepción enviando a líder: " . $e->getMessage());
        $resultados['lider'] = ['success' => false, 'error' => $e->getMessage()];
    }
    
    // 2. Enviar correo al REFERENCIADOR (si está asignado)
    if ($referenciadorInfo && !empty($referenciadorInfo['correo'])) {
        try {
            $resultadoReferenciador = $brevo->enviarNotificacionAsignacionLider($lider, $referenciadorInfo, $administrador);
            if ($resultadoReferenciador['success']) {
                $correos_enviados[] = 'Coordinador';
                $resultados['referenciador'] = $resultadoReferenciador;
                error_log("✅ Correo enviado a coordinador: " . $referenciadorInfo['correo']);
            } else {
                $resultados['referenciador'] = $resultadoReferenciador;
                error_log("❌ Error enviando a coordinador: " . ($resultadoReferenciador['error'] ?? 'Desconocido'));
            }
        } catch (Exception $e) {
            error_log("❌ Excepción enviando a coordinador: " . $e->getMessage());
            $resultados['referenciador'] = ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // Preparar respuesta
    $success = !empty($correos_enviados);
    $mensaje = $success 
        ? 'Correos enviados a: ' . implode(' y ', $correos_enviados) 
        : 'No se pudo enviar ningún correo';
    
    echo json_encode([
        'success' => $success,
        'message' => $mensaje,
        'details' => $resultados,
        'correos_enviados' => $correos_enviados
    ]);
    
} catch (Exception $e) {
    error_log('Error general enviando correos: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>