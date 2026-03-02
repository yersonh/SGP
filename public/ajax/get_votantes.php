<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/ReferenciadoModel.php';
require_once __DIR__ . '/../../models/DepartamentoModel.php';
require_once __DIR__ . '/../../models/MunicipioModel.php';
require_once __DIR__ . '/../../models/ZonaModel.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../models/LiderModel.php';
require_once __DIR__ . '/../../models/OfertaApoyoModel.php';

header('Content-Type: application/json');

// Verificar permisos (Descargador también puede ver)
if (!isset($_SESSION['id_usuario']) || !in_array($_SESSION['tipo_usuario'], ['SuperAdmin', 'Administrador', 'Descargador'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

$pdo = Database::getConnection();
$referenciadoModel = new ReferenciadoModel($pdo);
$usuarioModel = new UsuarioModel($pdo);
$liderModel = new LiderModel($pdo);

// Verificar si es una solicitud para obtener opciones de filtros
$getOptions = isset($_GET['get_options']) ? $_GET['get_options'] : false;

if ($getOptions === 'true') {
    // Devolver opciones para filtros
    $departamentoModel = new DepartamentoModel($pdo);
    $zonaModel = new ZonaModel($pdo);
    $ofertaApoyoModel = new OfertaApoyoModel($pdo);
    
    try {
        $departamentos = $departamentoModel->getAll();
        $zonas = $zonaModel->getAll();
        $referenciadores = $usuarioModel->getReferenciadoresParaCombo();
        $lideres = $liderModel->getActivos();
        $ofertas_apoyo = $ofertaApoyoModel->getAll();
        
        echo json_encode([
            'success' => true,
            'departamentos' => $departamentos,
            'zonas' => $zonas,
            'referenciadores' => $referenciadores,
            'lideres' => $lideres,
            'ofertas_apoyo' => $ofertas_apoyo
        ]);
    } catch (Exception $e) {
        error_log('Error al obtener opciones de filtros: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Error al cargar opciones de filtros'
        ]);
    }
    exit();
}

// Si se solicita municipios por departamento
if (isset($_GET['get_municipios']) && isset($_GET['departamento'])) {
    $municipioModel = new MunicipioModel($pdo);
    $id_departamento = (int)$_GET['departamento'];
    
    try {
        $municipios = $municipioModel->getByDepartamento($id_departamento);
        echo json_encode([
            'success' => true,
            'municipios' => $municipios
        ]);
    } catch (Exception $e) {
        error_log('Error al obtener municipios: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Error al cargar municipios'
        ]);
    }
    exit();
}

// Continuar con la consulta normal de votantes (SOLO LOS QUE YA VOTARON)
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = isset($_GET['per_page']) ? max(1, (int)$_GET['per_page']) : 50;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Nuevos parámetros para filtros avanzados
$departamento = isset($_GET['departamento']) ? (int)$_GET['departamento'] : 0;
$municipio = isset($_GET['municipio']) ? (int)$_GET['municipio'] : 0;
$zona = isset($_GET['zona']) ? (int)$_GET['zona'] : 0;
$referenciador = isset($_GET['referenciador']) ? (int)$_GET['referenciador'] : 0;
$oferta_apoyo = isset($_GET['oferta_apoyo']) ? (int)$_GET['oferta_apoyo'] : 0;
$lider = isset($_GET['lider']) ? (int)$_GET['lider'] : 0;

// Filtros de fecha de voto
$fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
$fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';

// Filtros - SIEMPRE INCLUIR voto_registrado = true
$filters = ['voto_registrado' => true];

if (!empty($search)) $filters['search'] = $search;

// Filtros avanzados (solo agregar si son > 0)
if ($departamento > 0) $filters['departamento'] = $departamento;
if ($municipio > 0) $filters['municipio'] = $municipio;
if ($zona > 0) $filters['zona'] = $zona;
if ($referenciador > 0) $filters['referenciador'] = $referenciador;
if ($oferta_apoyo > 0) $filters['oferta_apoyo'] = $oferta_apoyo;
if ($lider > 0) $filters['lider'] = $lider;

// Filtros de fecha
if (!empty($fecha_desde)) $filters['fecha_voto_desde'] = $fecha_desde;
if (!empty($fecha_hasta)) $filters['fecha_voto_hasta'] = $fecha_hasta;

// DEBUG - Escribir en log
error_log("=== GET VOTANTES DEBUG ===");
error_log("Search term: '" . $search . "'");
error_log("Filters: " . print_r($filters, true));

try {
    // Obtener datos paginados de votantes
    $data = $referenciadoModel->getVotantesPaginados($page, $perPage, $filters);
    
    // DEBUG - Cuántos resultados
    error_log("Resultados encontrados: " . count($data));
    
    // Obtener total para paginación
    $total = (int)$referenciadoModel->getTotalVotantes($filters);
    
    // Contar votantes de hoy y pendientes
    $totalActivos = (int)$referenciadoModel->countReferenciadosActivos();
    $votantesHoy = (int)$referenciadoModel->getVotantesHoy();
    $pendientes = $totalActivos - $total;
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => ceil($total / $perPage)
        ],
        'stats' => [
            'votantes' => $total,
            'total_activos' => $totalActivos,
            'votantes_hoy' => $votantesHoy,
            'pendientes' => $pendientes
        ]
    ]);
    
} catch (Exception $e) {
    error_log('❌ Error en get_votantes.php: ' . $e->getMessage());
    error_log('Trace: ' . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'error' => 'Error del servidor: ' . $e->getMessage(),
        'debug' => ['filters' => $filters]
    ]);
}
?>