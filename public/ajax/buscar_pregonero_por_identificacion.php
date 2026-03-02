<?php
// ajax/buscar_pregonero_por_identificacion.php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/PregoneroModel.php';

// Verificar si el usuario está logueado y es SuperAdmin
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    header('HTTP/1.0 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit();
}

// Verificar que se recibió la identificación
if (!isset($_GET['identificacion']) || empty(trim($_GET['identificacion']))) {
    echo json_encode(['success' => false, 'message' => 'Identificación no proporcionada']);
    exit();
}

$identificacion = trim($_GET['identificacion']);

// Validar que solo contenga números
if (!preg_match('/^\d+$/', $identificacion)) {
    echo json_encode(['success' => false, 'message' => 'La identificación solo debe contener números']);
    exit();
}

$pdo = Database::getConnection();
$pregoneroModel = new PregoneroModel($pdo);

// Buscar el pregonero por identificación usando el método del modelo
$pregonero = $pregoneroModel->getPregoneroPorIdentificacion($identificacion);

if (!$pregonero) {
    echo json_encode(['success' => false, 'message' => 'No se encontró ningún pregonero con esa identificación']);
    exit();
}

// Obtener nombre del usuario que registró
$nombreRegistrador = 'No disponible';
if (!empty($pregonero['usuario_nombres']) && !empty($pregonero['usuario_apellidos'])) {
    $nombreRegistrador = $pregonero['usuario_nombres'] . ' ' . $pregonero['usuario_apellidos'];
}

// Formatear fecha
$fechaRegistro = date('d/m/Y H:i', strtotime($pregonero['fecha_registro']));

// Preparar la respuesta
// Preparar la respuesta - VERSIÓN CORREGIDA (incluye voto_registrado)
$response = [
    'success' => true,
    'data' => [
        'id_pregonero' => $pregonero['id_pregonero'],
        'nombres' => $pregonero['nombres'],
        'apellidos' => $pregonero['apellidos'],
        'nombre_completo' => $pregonero['nombres'] . ' ' . $pregonero['apellidos'],
        'identificacion' => $pregonero['identificacion'],
        'telefono' => $pregonero['telefono'] ?? 'No registrado',
        'corregimiento' => $pregonero['corregimiento'] ?? 'No registrado',
        'comuna' => $pregonero['comuna'] ?? 'No registrado',
        'mesa' => $pregonero['mesa'] ?? 'No registrada',
        'barrio' => $pregonero['barrio_nombre'] ?? 'No registrado',
        'puesto' => $pregonero['puesto_nombre'] ?? 'No registrado',
        'sector' => $pregonero['sector_nombre'] ?? 'No registrado',
        'zona' => $pregonero['zona_nombre'] ?? 'No registrado',
        'registrador' => $nombreRegistrador,
        'fecha_registro' => $fechaRegistro,
        'activo' => (bool)$pregonero['activo'],
        'voto_registrado' => (bool)$pregonero['voto_registrado'] // <-- ESTE CAMPO FALTABA
    ]
];

echo json_encode($response);
?>