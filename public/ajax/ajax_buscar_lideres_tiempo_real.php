<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../models/LiderModel.php';
require_once __DIR__ . '/../../models/ReferenciadoModel.php';

// Verificar si el usuario está logueado y es SuperAdmin
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

// Verificar acción
$action = $_POST['action'] ?? '';
if ($action !== 'buscar_lideres') {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    exit();
}

$pdo = Database::getConnection();
$usuarioModel = new UsuarioModel($pdo);
$liderModel = new LiderModel($pdo);
$referenciadoModel = new ReferenciadoModel($pdo);

// Obtener parámetros de búsqueda
$filtro_nombre = $_POST['filtro_nombre'] ?? '';
$filtro_cc = $_POST['filtro_cc'] ?? '';
$filtro_referenciador = $_POST['filtro_referenciador'] ?? '';
$filtro_min_referidos = $_POST['filtro_min_referidos'] ?? '';
$page = isset($_POST['page']) ? intval($_POST['page']) : 1;
$perPage = isset($_POST['per_page']) ? intval($_POST['per_page']) : 25;

if ($perPage <= 0) $perPage = 25;
if ($page <= 0) $page = 1;

// Configurar filtros para el modelo
$filters = [];
if (!empty($filtro_nombre)) {
    $filters['search'] = $filtro_nombre;
}
if (!empty($filtro_referenciador)) {
    $filters['id_usuario'] = $filtro_referenciador;
}
// NUEVO: Estos ahora van directo a SQL
if (!empty($filtro_cc)) {
    $filters['cc'] = $filtro_cc;
}
if (!empty($filtro_min_referidos) && is_numeric($filtro_min_referidos)) {
    $filters['min_referidos'] = (int)$filtro_min_referidos;
}

// Obtener líderes paginados con TODOS los filtros en SQL
$resultadoLideres = $liderModel->getPaginatedWithStats($filters, $page, $perPage, 'cantidad_referidos', 'DESC');

try {
    // Obtener líderes paginados
    $resultadoLideres = $liderModel->getPaginatedWithStats($filters, $page, $perPage, 'cantidad_referidos', 'DESC');
    
    if (!$resultadoLideres['success']) {
        throw new Exception($resultadoLideres['message']);
    }
    
    $lideresConEstadisticas = $resultadoLideres['data'];
    $pagination = $resultadoLideres['pagination'];
    
    // Obtener total de referidos del sistema para calcular porcentajes
    $totalReferidosSistema = $referenciadoModel->countReferenciadosActivos();
    
    // Aplicar filtros en memoria (los que no están en SQL)
    $lideresFiltrados = $lideresConEstadisticas;
    
    // Aplicar filtro mínimo de referidos (en memoria)
    if (!empty($filtro_min_referidos) && is_numeric($filtro_min_referidos)) {
        $minReferidos = intval($filtro_min_referidos);
        $lideresFiltrados = array_filter($lideresFiltrados, 
            function($lider) use ($minReferidos) {
                return ($lider['cantidad_referidos'] ?? 0) >= $minReferidos;
            }
        );
    }
    
    // Aplicar filtro por cédula (en memoria)
    if (!empty($filtro_cc)) {
        $lideresFiltrados = array_filter($lideresFiltrados, 
            function($lider) use ($filtro_cc) {
                return stripos($lider['cc'] ?? '', $filtro_cc) !== false;
            }
        );
    }
    
    // Re-indexar el array después de filtrar
    $lideresFiltrados = array_values($lideresFiltrados);
    
    // Calcular porcentajes para cada líder
    foreach ($lideresFiltrados as &$lider) {
        $cantidadReferidos = $lider['cantidad_referidos'] ?? 0;
        $lider['porcentaje_contribucion'] = $totalReferidosSistema > 0 ? 
            round(($cantidadReferidos * 100) / $totalReferidosSistema, 2) : 0;
    }
    
    // Obtener estadísticas globales (independientes de filtros)
    $totalLideresGlobal = $liderModel->countAll([]);
    $totalLideresActivosGlobal = $liderModel->countActivos([]);
    $totalReferidosPorLideresGlobal = $liderModel->getTotalReferidosGlobal();
    $topLiderGlobal = $liderModel->getTopLiderGlobal();
    $esBusquedaCedula = false;
    if (!empty($filtro_cc)) {
        $cc_clean = preg_replace('/[^0-9]/', '', $filtro_cc);
        if (is_numeric($cc_clean) && strlen($cc_clean) >= 6) {
            $esBusquedaCedula = true;
        }
    }
    // Preparar estadísticas para la respuesta
    $estadisticas = [
        'total_lideres' => $totalLideresGlobal,
        'total_lideres_activos' => $totalLideresActivosGlobal,
        'total_referidos_por_lideres' => $totalReferidosPorLideresGlobal,
        'porcentaje_total' => $totalReferidosSistema > 0 ? 
            round(($totalReferidosPorLideresGlobal * 100) / $totalReferidosSistema, 2) : 0,
        'top_lider_nombre' => $topLiderGlobal ? $topLiderGlobal['nombre'] : 'N/A',
        'top_lider_referidos' => $topLiderGlobal ? $topLiderGlobal['referidos'] : 0,
        'mostrando_actualmente' => count($lideresFiltrados)
    ];
    
    // Preparar respuesta
    echo json_encode([
        'success' => true,
        'lideres' => $lideresFiltrados,
        'estadisticas' => $estadisticas,
        'pagination' => [
            'total' => $pagination['total'],
            'current_page' => $pagination['current_page'],
            'total_pages' => $pagination['total_pages'],
            'per_page' => $pagination['per_page'],
            'mostrando' => count($lideresFiltrados),
            'es_busqueda_cedula' => $esBusquedaCedula
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error en ajax_buscar_lideres_tiempo_real.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error en la búsqueda: ' . $e->getMessage(),
        'lideres' => [],
        'estadisticas' => [],
        'pagination' => []
    ]);
}
?>