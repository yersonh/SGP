<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/PregoneroModel.php';

header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

$pdo = Database::getConnection();
$pregoneroModel = new PregoneroModel($pdo);

// Obtener parámetros
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 50;

// Construir filtros - POR DEFECTO MOSTRAR SOLO LOS QUE YA VOTARON
$filters = [];

// AÑADIDO: Filtro por defecto para mostrar solo los que ya votaron
$filters['voto_registrado'] = true;

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}

if (isset($_GET['activo']) && $_GET['activo'] !== '') {
    $filters['activo'] = $_GET['activo'];
}

// Permitir sobrescribir el filtro de voto_registrado si se envía explícitamente
if (isset($_GET['voto_registrado']) && $_GET['voto_registrado'] !== '') {
    $filters['voto_registrado'] = $_GET['voto_registrado'] === 'true' ? true : false;
}

if (isset($_GET['zona']) && !empty($_GET['zona'])) {
    $filters['zona'] = $_GET['zona'];
}

if (isset($_GET['barrio']) && !empty($_GET['barrio'])) {
    $filters['barrio'] = $_GET['barrio'];
}

if (isset($_GET['puesto']) && !empty($_GET['puesto'])) {
    $filters['puesto'] = $_GET['puesto'];
}

if (isset($_GET['comuna']) && !empty($_GET['comuna'])) {
    $filters['comuna'] = $_GET['comuna'];
}

if (isset($_GET['corregimiento']) && !empty($_GET['corregimiento'])) {
    $filters['corregimiento'] = $_GET['corregimiento'];
}

// NUEVO: Filtro por quien_reporta
if (isset($_GET['quien_reporta']) && !empty($_GET['quien_reporta'])) {
    $filters['quien_reporta'] = $_GET['quien_reporta'];
}

// NUEVO: Filtro por id_referenciador
if (isset($_GET['id_referenciador']) && !empty($_GET['id_referenciador'])) {
    $filters['id_referenciador'] = $_GET['id_referenciador'];
}

if (isset($_GET['usuario_registro']) && !empty($_GET['usuario_registro'])) {
    $filters['usuario_registro'] = $_GET['usuario_registro'];
}

if (isset($_GET['fecha_desde']) && !empty($_GET['fecha_desde'])) {
    $filters['fecha_desde'] = $_GET['fecha_desde'];
}

if (isset($_GET['fecha_hasta']) && !empty($_GET['fecha_hasta'])) {
    $filters['fecha_hasta'] = $_GET['fecha_hasta'];
}

// Si es una petición para obtener opciones de filtros
if (isset($_GET['get_options']) && $_GET['get_options'] === 'true') {
    try {
        // Obtener zonas
        $zonas = $pdo->query("SELECT id_zona, nombre FROM zona ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
        
        // Obtener barrios
        $barrios = $pdo->query("SELECT id_barrio, nombre FROM barrio ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
        
        // Obtener puestos de votación
        $puestos = $pdo->query("SELECT id_puesto, nombre FROM puesto_votacion ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
        
        // NUEVO: Obtener referenciadores (usuarios tipo Referenciador)
        $referenciadores = $pdo->query("
            SELECT id_usuario, nombres, apellidos, cedula 
            FROM usuario 
            WHERE tipo_usuario = 'Referenciador' AND activo = true
            ORDER BY nombres, apellidos
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        // Obtener usuarios que han registrado pregoneros
        $usuarios = $pdo->query("
            SELECT DISTINCT u.id_usuario, u.nombres, u.apellidos, u.cedula 
            FROM usuario u 
            INNER JOIN pregonero p ON u.id_usuario = p.id_usuario_registro 
            ORDER BY u.nombres, u.apellidos
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'zonas' => $zonas,
            'barrios' => $barrios,
            'puestos' => $puestos,
            'referenciadores' => $referenciadores, // NUEVO
            'usuarios_registro' => $usuarios
        ]);
        exit();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit();
    }
}

try {
    // Obtener datos paginados
    $data = $pregoneroModel->getPregonerosPaginados($page, $perPage, $filters);
    
    // 🔍 DEPURACIÓN: Ver qué campos vienen del modelo (opcional - comentar en producción)
    if (!empty($data)) {
        error_log("Campos del primer pregonero: " . print_r(array_keys($data[0]), true));
    }
    
    // Obtener total de registros
    $total = $pregoneroModel->getTotalPregoneros($filters);
    
    // Calcular estadísticas (activos e inactivos) - AHORA INCLUYE FILTRO DE VOTO_REGISTRADO
    $statsActivos = $pregoneroModel->getTotalPregoneros(array_merge($filters, ['activo' => true]));
    $statsInactivos = $pregoneroModel->getTotalPregoneros(array_merge($filters, ['activo' => false]));
    
    $totalPages = ceil($total / $perPage);
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'pagination' => [
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'total_pages' => $totalPages
        ],
        'stats' => [
            'total' => $total,
            'activos' => $statsActivos,
            'inactivos' => $statsInactivos
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>