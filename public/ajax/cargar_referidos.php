<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/ReferenciadoModel.php';

// Verificar permisos
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$pdo = Database::getConnection();
$model = new ReferenciadoModel($pdo);

// Obtener parámetros de filtro
$filtros = $_GET;

// Cargar datos
$referidos = $model->getReferidosConFiltros($filtros);

// Formatear respuesta
$data = [];
foreach ($referidos as $referido) {
    $data[] = [
        'id_referido' => $referido['id_referido'],
        'nombre_completo' => $referido['nombres'] . ' ' . $referido['apellidos'],
        'cedula' => $referido['cedula'],
        'referenciador' => $referido['referenciador_nombre'],
        'fecha_referencia' => $referido['fecha_referencia'],
        'estado' => $referido['descargado'] ? 'descargado' : 'pendiente',
        'zona' => $referido['zona_nombre']
    ];
}

echo json_encode(['success' => true, 'data' => $data]);
?>