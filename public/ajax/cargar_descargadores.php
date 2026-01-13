<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/DescargadoModel.php';

// Verificar permisos
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$pdo = Database::getConnection();
$model = new DescargadoModel($pdo);

// Obtener parámetros de filtro
$filtros = $_GET;

// Cargar datos
$descargadores = $model->getDescargadosConFiltros($filtros);

// Formatear respuesta
$data = [];
foreach ($descargadores as $descargador) {
    $data[] = [
        'id_descargado' => $descargador['id_descargado'],
        'nombre_completo' => $descargador['nombres'] . ' ' . $descargador['apellidos'],
        'cedula' => $descargador['cedula'],
        'referenciador' => $descargador['referenciador_nombre'],
        'fecha_voto' => $descargador['fecha_voto'],
        'hora_voto' => $descargador['hora_voto'],
        'puesto_votacion' => $descargador['puesto_nombre']
    ];
}

echo json_encode(['success' => true, 'data' => $data]);
?>