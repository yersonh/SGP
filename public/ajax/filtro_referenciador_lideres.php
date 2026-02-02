<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/LiderModel.php';
require_once __DIR__ . '/../../models/ReferenciadoModel.php';

// Verificar si el usuario está logueado y es SuperAdmin
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
    exit();
}

// Obtener conexión
$pdo = Database::getConnection();
$liderModel = new LiderModel($pdo);
$referenciadoModel = new ReferenciadoModel($pdo);

// Obtener parámetros
$id_referenciador = isset($_GET['id_referenciador']) ? (int)$_GET['id_referenciador'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

if ($id_referenciador <= 0) {
    echo json_encode([
        'success' => false,
        'error' => 'ID de referenciador inválido'
    ]);
    exit();
}

try {
    // Obtener líderes del referenciador
    $lideres = $liderModel->getByReferenciador($id_referenciador);
    
    // Para cada líder, obtener la cantidad de referidos
    $lideresConEstadisticas = [];
    foreach ($lideres as $lider) {
        $cantidadReferidos = $referenciadoModel->countByLider($lider['id_lider']);
        
        $lideresConEstadisticas[] = [
            'id_lider' => $lider['id_lider'],
            'nombres' => $lider['nombres'],
            'apellidos' => $lider['apellidos'],
            'cc' => $lider['cc'],
            'telefono' => $lider['telefono'],
            'correo' => $lider['correo'],
            'estado' => $lider['estado'],
            'fecha_creacion' => $lider['fecha_creacion'],
            'cantidad_referidos' => $cantidadReferidos,
            // Información del referenciador si está disponible en el modelo
            'referenciador_nombre' => $lider['referenciador_nombre'] ?? 'N/A'
        ];
    }
    
    // Calcular paginación
    $totalResultados = count($lideresConEstadisticas);
    $totalPaginas = ceil($totalResultados / $limit);
    $offset = ($page - 1) * $limit;
    
    // Aplicar paginación
    $lideresPaginados = array_slice($lideresConEstadisticas, $offset, $limit);
    
    // Calcular estadísticas generales
    $totalLideres = count($lideres);
    $lideresActivos = count(array_filter($lideres, function($l) { return $l['estado'] == true; }));
    $totalReferidos = array_sum(array_column($lideresConEstadisticas, 'cantidad_referidos'));
    
    echo json_encode([
        'success' => true,
        'data' => [
            'lideres' => $lideresPaginados,
            'estadisticas' => [
                'total_lideres' => $totalLideres,
                'lideres_activos' => $lideresActivos,
                'total_referidos' => $totalReferidos,
                'referenciador_id' => $id_referenciador
            ],
            'paginacion' => [
                'paginaActual' => $page,
                'totalPaginas' => $totalPaginas,
                'totalResultados' => $totalResultados,
                'resultadosPorPagina' => $limit,
                'mostrandoDesde' => $offset + 1,
                'mostrandoHasta' => min($offset + $limit, $totalResultados)
            ],
            'filtrosActivos' => true
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error en filtro_referenciador_lideres: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener los datos: ' . $e->getMessage()
    ]);
}
?>