<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/ReferenciadoModel.php';
require_once __DIR__ . '/../../models/DepartamentoModel.php';
require_once __DIR__ . '/../../models/MunicipioModel.php';
require_once __DIR__ . '/../../models/ZonaModel.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../models/LiderModel.php';

header('Content-Type: application/json');

// Verificar permisos
if (!isset($_SESSION['id_usuario']) || !in_array($_SESSION['tipo_usuario'], ['SuperAdmin', 'Administrador'])) {
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
    
    try {
        $departamentos = $departamentoModel->getAll();
        $zonas = $zonaModel->getAll();
        $referenciadores = $usuarioModel->getReferenciadoresParaCombo();
        
        /* ✅ CORREGIDO: Obtener líderes desde LiderModel (tabla lideres) */
        $lideres = $liderModel->getActivos(); // Esto trae SOLO líderes de la tabla lideres
        
        echo json_encode([
            'success' => true,
            'departamentos' => $departamentos,
            'zonas' => $zonas,
            'referenciadores' => $referenciadores,
            'lideres' => $lideres // Ahora viene de la tabla correcta
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

// Continuar con la consulta normal de referenciados
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = isset($_GET['per_page']) ? max(1, (int)$_GET['per_page']) : 50;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$activo = isset($_GET['activo']) ? $_GET['activo'] : '';

// Nuevos parámetros para filtros avanzados
$departamento = isset($_GET['departamento']) ? (int)$_GET['departamento'] : 0;
$municipio = isset($_GET['municipio']) ? (int)$_GET['municipio'] : 0;
$zona = isset($_GET['zona']) ? (int)$_GET['zona'] : 0;
$referenciador = isset($_GET['referenciador']) ? (int)$_GET['referenciador'] : 0;
$oferta_apoyo = isset($_GET['oferta_apoyo']) ? (int)$_GET['oferta_apoyo'] : 0;
$grupo_poblacional = isset($_GET['grupo_poblacional']) ? (int)$_GET['grupo_poblacional'] : 0;
$grupo_parlamentario = isset($_GET['grupo_parlamentario']) ? (int)$_GET['grupo_parlamentario'] : 0;
/* ✅ FILTRO POR LÍDER - AHORA USA ID DE LA TABLA LIDERES */
$lider = isset($_GET['lider']) ? (int)$_GET['lider'] : 0;

// Filtros
$filters = [];
if (!empty($search)) $filters['search'] = $search;
if ($activo !== '') $filters['activo'] = $activo;

// Filtros avanzados (solo agregar si son > 0)
if ($departamento > 0) $filters['departamento'] = $departamento;
if ($municipio > 0) $filters['municipio'] = $municipio;
if ($zona > 0) $filters['zona'] = $zona;
if ($referenciador > 0) $filters['referenciador'] = $referenciador;
if ($oferta_apoyo > 0) $filters['oferta_apoyo'] = $oferta_apoyo;
if ($grupo_poblacional > 0) $filters['grupo_poblacional'] = $grupo_poblacional;
if ($grupo_parlamentario > 0) $filters['grupo_parlamentario'] = $grupo_parlamentario;
/* ✅ FILTRO POR LÍDER - AHORA USA ID DE LA TABLA LIDERES */
if ($lider > 0) $filters['lider'] = $lider;

try {
    // Obtener datos paginados
    $data = $referenciadoModel->getReferenciadosPaginados($page, $perPage, $filters);
    
    // Obtener total para paginación
    $total = (int)$referenciadoModel->getTotalReferenciados($filters);
    
    // Contar activos/inactivos
    $totalActivos = (int)$referenciadoModel->getTotalReferenciados(array_merge($filters, ['activo' => true]));
    $totalInactivos = (int)$referenciadoModel->getTotalReferenciados(array_merge($filters, ['activo' => false]));
    
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
            'total' => $total,
            'activos' => $totalActivos,
            'inactivos' => $totalInactivos
        ]
    ]);
    
} catch (Exception $e) {
    error_log('Error en get_referenciados.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error del servidor: ' . $e->getMessage(),
        'debug' => ['filters' => $filters]
    ]);
}
?>