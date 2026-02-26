<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/ReferenciadoModel.php';
require_once __DIR__ . '/../../models/UsuarioModel.php'; // <-- FALTABA ESTE REQUIRE

// Verificar si el usuario está logueado y es Descargador
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'Descargador') {
    header('HTTP/1.0 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit();
}

// Verificar que se recibió la cédula
if (!isset($_GET['cedula']) || empty(trim($_GET['cedula']))) {
    echo json_encode(['success' => false, 'message' => 'Cédula no proporcionada']);
    exit();
}

$cedula = trim($_GET['cedula']);

// Validar que solo contenga números
if (!preg_match('/^\d+$/', $cedula)) {
    echo json_encode(['success' => false, 'message' => 'La cédula solo debe contener números']);
    exit();
}

$pdo = Database::getConnection();
$referenciadoModel = new ReferenciadoModel($pdo);

// Buscar el referenciado por cédula
$referenciado = $referenciadoModel->getReferenciadoPorCedula($cedula);

if (!$referenciado) {
    echo json_encode(['success' => false, 'message' => 'No se encontró ningún votante con esa cédula']);
    exit();
}

// Obtener nombre del referenciador si existe
$nombreReferenciador = 'No asignado';
if (!empty($referenciado['id_referenciador'])) {
    $usuarioModel = new UsuarioModel($pdo); // <-- AHORA SÍ SE INSTANCIA
    $datosReferenciador = $usuarioModel->getUsuarioById($referenciado['id_referenciador']); // <-- CAMBIÉ EL NOMBRE DE LA VARIABLE
    if ($datosReferenciador) {
        $nombreReferenciador = $datosReferenciador['nombres'] . ' ' . $datosReferenciador['apellidos'];
    }
}

// Preparar la respuesta (SOLO DATOS BÁSICOS + ESTADO DE VOTO)
$response = [
    'success' => true,
    'data' => [
        'id_referenciado' => $referenciado['id_referenciado'],
        'nombre' => $referenciado['nombre'],
        'apellido' => $referenciado['apellido'],
        'nombre_completo' => $referenciado['nombre'] . ' ' . $referenciado['apellido'],
        'cedula' => $referenciado['cedula'],
        'telefono' => $referenciado['telefono'] ?? 'No registrado',
        'email' => $referenciado['email'] ?? 'No registrado',
        'direccion' => $referenciado['direccion'] ?? 'No registrada',
        'referenciador' => $nombreReferenciador,
        'voto_registrado' => (bool)$referenciado['voto_registrado']
    ]
];

echo json_encode($response);
?>