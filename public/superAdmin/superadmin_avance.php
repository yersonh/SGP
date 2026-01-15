<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../models/ZonaModel.php';
require_once __DIR__ . '/../../models/SectorModel.php';
require_once __DIR__ . '/../../models/PuestoVotacionModel.php';

// Verificar si el usuario está logueado y es SuperAdmin
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    header('Location: ../index.php');
    exit();
}

$pdo = Database::getConnection();
$usuarioModel = new UsuarioModel($pdo);
$zonaModel = new ZonaModel($pdo);
$sectorModel = new SectorModel($pdo);
$puestoVotacionModel = new PuestoVotacionModel($pdo);

// Obtener datos del usuario logueado
$usuario_logueado = $usuarioModel->getUsuarioById($_SESSION['id_usuario']);

// Obtener listas para los filtros
$zonas = $zonaModel->getAll();
$sectores = $sectorModel->getAll();
$puestosVotacion = $puestoVotacionModel->getAll();

// Procesar filtros del formulario
$filtros = [
    'nombre' => $_GET['nombre'] ?? '',
    'id_zona' => $_GET['id_zona'] ?? '',
    'id_sector' => $_GET['id_sector'] ?? '',
    'id_puesto_votacion' => $_GET['id_puesto_votacion'] ?? '',
    'fecha_acceso' => $_GET['fecha_acceso'] ?? ''
];

// Obtener todos los referenciadores con sus estadísticas (SIN FILTROS para estadísticas globales)
try {
    // Obtener todos los usuarios con estadísticas para cálculo global
    $todosReferenciadores = $usuarioModel->getAllUsuarios();
    
    // Filtrar solo referenciadores activos para estadísticas globales
    $referenciadoresGlobales = array_filter($todosReferenciadores, function($usuario) {
        return $usuario['tipo_usuario'] === 'Referenciador' && $usuario['activo'] == true;
    });
    
    // Calcular estadísticas globales (SIEMPRE con todos los referenciadores)
    $totalReferenciadores = count($referenciadoresGlobales);
    $totalReferidos = 0;
    $totalTope = 0;
    
    foreach ($referenciadoresGlobales as &$referenciadorGlobal) {
        $totalReferidos += $referenciadorGlobal['total_referenciados'] ?? 0;
        $totalTope += $referenciadorGlobal['tope'] ?? 0;
        
        // Calcular porcentaje individual si no viene del modelo
        if (!isset($referenciadorGlobal['porcentaje_tope']) && $referenciadorGlobal['tope'] > 0) {
            $referenciadorGlobal['porcentaje_tope'] = round(($referenciadorGlobal['total_referenciados'] / $referenciadorGlobal['tope']) * 100, 2);
        }
        
        // Limitar al 100%
        if ($referenciadorGlobal['porcentaje_tope'] > 100) {
            $referenciadorGlobal['porcentaje_tope'] = 100;
        }
    }
    
    // Calcular porcentaje global
    $porcentajeGlobal = 0;
    if ($totalTope > 0) {
        $porcentajeGlobal = round(($totalReferidos / $totalTope) * 100, 2);
        $porcentajeGlobal = min($porcentajeGlobal, 100);
    }
    
    // Ahora aplicar filtros solo para la lista de referenciadores
    $referenciadores = array_filter($todosReferenciadores, function($usuario) use ($filtros, $zonaModel, $sectorModel, $puestoVotacionModel, $usuarioModel) {
        // Filtrar por tipo de usuario y activo
        if ($usuario['tipo_usuario'] !== 'Referenciador' || $usuario['activo'] != true) {
            return false;
        }
        
        // Filtrar por nombre (nombres o apellidos) - búsqueda en tiempo real
        if (!empty($filtros['nombre'])) {
            $nombreCompleto = $usuario['nombres'] . ' ' . $usuario['apellidos'];
            if (stripos($nombreCompleto, $filtros['nombre']) === false && 
                stripos($usuario['nombres'], $filtros['nombre']) === false &&
                stripos($usuario['apellidos'], $filtros['nombre']) === false) {
                return false;
            }
        }
        
        // Filtrar por zona
        if (!empty($filtros['id_zona']) && $usuario['id_zona'] != $filtros['id_zona']) {
            return false;
        }
        
        // Filtrar por sector
        if (!empty($filtros['id_sector']) && $usuario['id_sector'] != $filtros['id_sector']) {
            return false;
        }
        
        // Filtrar por puesto de votación
        if (!empty($filtros['id_puesto_votacion'])) {
            // Obtener el sector del puesto de votación
            $puesto = $puestoVotacionModel->getById($filtros['id_puesto_votacion']);
            if ($puesto && isset($puesto['id_sector'])) {
                // Obtener el usuario completo con sus relaciones
                $usuarioCompleto = $usuarioModel->getUsuarioById($usuario['id_usuario']);
                if ($usuarioCompleto && $usuarioCompleto['id_sector'] != $puesto['id_sector']) {
                    return false;
                }
            }
        }
        
        // Filtrar por fecha de último acceso (exacta o posterior)
        if (!empty($filtros['fecha_acceso']) && !empty($usuario['ultimo_registro'])) {
            $fechaUltimoAcceso = new DateTime($usuario['ultimo_registro']);
            $fechaFiltro = new DateTime($filtros['fecha_acceso']);
            
            // Comparar solo la fecha (sin horas/minutos)
            if ($fechaUltimoAcceso->format('Y-m-d') != $fechaFiltro->format('Y-m-d')) {
                return false;
            }
        }
        
        return true;
    });

    // Reindexar array después del filtro
    $referenciadores = array_values($referenciadores);
    
    // Calcular estadísticas solo para los filtrados (para mostrar en resultados)
    $referenciadoresFiltradosCount = count($referenciadores);
    $totalReferidosFiltrados = 0;
    $totalTopeFiltrados = 0;
    
    foreach ($referenciadores as &$referenciador) {
        $totalReferidosFiltrados += $referenciador['total_referenciados'] ?? 0;
        $totalTopeFiltrados += $referenciador['tope'] ?? 0;
        
        // Calcular porcentaje individual si no viene del modelo
        if (!isset($referenciador['porcentaje_tope']) && $referenciador['tope'] > 0) {
            $referenciador['porcentaje_tope'] = round(($referenciador['total_referenciados'] / $referenciador['tope']) * 100, 2);
        }
        
        // Limitar al 100%
        if ($referenciador['porcentaje_tope'] > 100) {
            $referenciador['porcentaje_tope'] = 100;
        }
    }
    
    // Calcular porcentaje filtrado
    $porcentajeFiltrado = 0;
    if ($totalTopeFiltrados > 0) {
        $porcentajeFiltrado = round(($totalReferidosFiltrados / $totalTopeFiltrados) * 100, 2);
        $porcentajeFiltrado = min($porcentajeFiltrado, 100);
    }
    
} catch (Exception $e) {
    $referenciadores = [];
    $referenciadoresGlobales = [];
    $totalReferenciadores = 0;
    $totalReferidos = 0;
    $totalTope = 0;
    $porcentajeGlobal = 0;
    $referenciadoresFiltradosCount = 0;
    error_log("Error al obtener referenciadores: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avance Referenciadores - Super Admin - SGP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
            font-size: 14px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Header Styles */
        .main-header {
            background: linear-gradient(135deg, #2c3e50, #1a252f);
            color: white;
            padding: 15px 0;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-container {
            display: flex;
            flex-direction: column;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .header-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .header-title h1 {
            font-size: 1.2rem;
            margin: 0;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255,255,255,0.1);
            padding: 5px 10px;
            border-radius: 20px;
        }
        
        .user-info i {
            color: #3498db;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 6px 12px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 0.8rem;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        /* Breadcrumb Navigation */
        .breadcrumb-nav {
            max-width: 1400px;
            margin: 0 auto 20px;
            padding: 0 15px;
        }
        
        .breadcrumb {
            background: transparent;
            padding: 0;
            margin: 0;
            font-size: 0.9rem;
        }
        
        .breadcrumb-item a {
            color: #3498db;
            text-decoration: none;
        }
        
        .breadcrumb-item.active {
            color: #666;
        }
        
        .breadcrumb-item + .breadcrumb-item::before {
            content: "›";
            color: #95a5a6;
            padding: 0 8px;
        }
        
        /* Main Container */
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 15px 30px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        /* Dashboard Header */
        .dashboard-header {
            text-align: center;
            margin: 20px 0 40px;
            padding: 0 20px;
        }
        
        .dashboard-title {
            font-size: 2rem;
            color: #2c3e50;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .dashboard-subtitle {
            font-size: 1.1rem;
            color: #666;
            max-width: 800px;
            margin: 0 auto;
            line-height: 1.5;
        }
        
        /* Estadísticas Globales */
        .global-stats {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #eaeaea;
        }
        
        .stats-title {
            font-size: 1.2rem;
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .stat-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            border-left: 4px solid #3498db;
        }
        
        .stat-value {
            font-size: 2.2rem;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #666;
        }
        
        /* Barra de progreso global */
        .global-progress {
            margin-top: 20px;
        }
        
        .progress-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .progress-label {
            font-size: 0.9rem;
            color: #666;
        }
        
        .progress-percentage {
            font-size: 1rem;
            color: #3498db;
        }
        
        .progress-container {
            width: 100%;
            height: 14px;
            background-color: #e9ecef;
            border-radius: 7px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #3498db, #2980b9);
            border-radius: 7px;
        }
        
        .progress-stats {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            color: #666;
        }
        
        /* NUEVO: Filtros de Búsqueda (debajo de estadísticas globales) */
        .filtros-container {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #eaeaea;
        }
        
        .filtros-title {
            font-size: 1.2rem;
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .filtros-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .form-group {
            margin-bottom: 0;
        }
        
        .form-group label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 5px;
            display: block;
            font-weight: 500;
        }
        
        .form-control, .form-select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
            outline: none;
        }
        
        .filtros-actions {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }
        
        .btn-buscar {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-buscar:hover {
            background: linear-gradient(135deg, #2980b9, #1c6ea4);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .btn-limpiar {
            background: #95a5a6;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-limpiar:hover {
            background: #7f8c8d;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .filter-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #e3f2fd;
            color: #1976d2;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            margin: 5px 5px 5px 0;
        }
        
        .filter-badge .close {
            cursor: pointer;
            font-size: 1rem;
            opacity: 0.7;
        }
        
        .filter-badge .close:hover {
            opacity: 1;
        }
        
        .active-filters {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .filter-section-title {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        /* NUEVO: Indicador de resultados filtrados */
        .resultados-info {
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
        }
        
        .resultados-text {
            font-size: 0.9rem;
            color: #666;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .resultados-count {
            font-weight: 600;
            color: #3498db;
        }
        
        /* NUEVO: Mensaje cuando no hay resultados con filtros */
        .no-results {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        
        .no-results i {
            font-size: 3rem;
            color: #bdc3c7;
            margin-bottom: 15px;
        }
        
        .no-results p {
            font-size: 1.1rem;
            margin-bottom: 20px;
        }
        
        .btn-reset-filters {
            background: #3498db;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-reset-filters:hover {
            background: #2980b9;
            color: white;
        }
        
        /* Lista de Referenciadores */
        .referenciadores-list {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #eaeaea;
        }
        
        .list-title {
            font-size: 1.4rem;
            color: #2c3e50;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .referenciador-card {
            border: 1px solid #eaeaea;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .referenciador-card:hover {
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            border-color: #3498db;
        }
        
        .referenciador-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        
        .user-info-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid #eaeaea;
        }
        
        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .user-details {
            flex: 1;
        }
        
        .user-name {
            font-size: 1.1rem;
            color: #2c3e50;
            margin-bottom: 3px;
        }
        
        .user-info-text {
            font-size: 0.85rem;
            color: #666;
            display: flex;
            gap: 15px;
        }
        
        .user-stats {
            display: flex;
            gap: 20px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 1.3rem;
            color: #3498db;
        }
        
        .stat-desc {
            font-size: 0.8rem;
            color: #666;
        }
        
        /* Barra de progreso individual */
        .individual-progress {
            margin-top: 15px;
        }
        
        .progress-label-small {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 5px;
        }
        
        .progress-container-small {
            width: 100%;
            height: 10px;
            background-color: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
            margin-bottom: 8px;
        }
        
        .progress-bar-small {
            height: 100%;
            border-radius: 5px;
        }
        
        /* Colores de la barra según porcentaje */
        .progress-excelente { background: linear-gradient(90deg, #27ae60, #219653); }
        .progress-bueno { background: linear-gradient(90deg, #3498db, #2980b9); }
        .progress-medio { background: linear-gradient(90deg, #f39c12, #e67e22); }
        .progress-bajo { background: linear-gradient(90deg, #e74c3c, #c0392b); }
        
        .progress-numbers {
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
            color: #666;
        }
        
        /* Sin datos */
        .no-data {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        
        .no-data i {
            font-size: 3rem;
            color: #bdc3c7;
            margin-bottom: 15px;
        }
        
        .no-data p {
            font-size: 1.1rem;
        }
        
        /* Footer */
        .system-footer {
            text-align: center;
            padding: 25px 0;
            background: white;
            color: black;
            font-size: 0.9rem;
            line-height: 1.6;
            border-top: 2px solid #eaeaea;
            width: 100%;
            margin-top: 60px;
        }
        
        .system-footer p {
            margin: 8px 0;
            color: #333;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .referenciador-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .user-stats {
                width: 100%;
                justify-content: space-around;
            }
            
            /* Responsive para filtros */
            .filtros-form {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 767px) {
            .header-top {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .user-info {
                order: 1;
            }
            
            .logout-btn {
                order: 2;
                align-self: flex-end;
            }
            
            .breadcrumb-nav {
                padding: 0 10px;
                margin-bottom: 15px;
            }
            
            .breadcrumb {
                font-size: 0.85rem;
            }
            
            .dashboard-header {
                margin: 15px 0 30px;
            }
            
            .dashboard-title {
                font-size: 1.6rem;
                flex-direction: column;
                gap: 10px;
            }
            
            .dashboard-subtitle {
                font-size: 1rem;
                padding: 0 10px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .stat-card {
                padding: 15px;
            }
            
            .stat-value {
                font-size: 1.8rem;
            }
            
            .referenciadores-list {
                padding: 20px;
            }
            
            .user-info-section {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            
            .user-info-text {
                flex-direction: column;
                gap: 5px;
            }
            
            .system-footer {
                padding: 20px 15px;
                font-size: 0.85rem;
            }
            
            /* Responsive para filtros */
            .filtros-form {
                grid-template-columns: 1fr;
            }
            
            .filtros-actions {
                flex-direction: column;
            }
            
            .btn-buscar, .btn-limpiar {
                width: 100%;
                justify-content: center;
            }
        }
        
        @media (max-width: 480px) {
            .global-stats, .referenciadores-list, .filtros-container {
                padding: 15px;
            }
            
            .stat-value {
                font-size: 1.6rem;
            }
            
            .user-stats {
                flex-direction: column;
                gap: 10px;
            }
            
            .progress-numbers {
                flex-direction: column;
                gap: 5px;
                text-align: center;
            }
        }
        /* Estadísticas Globales - MEJORADO */
        .global-stats {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #eaeaea;
        }

        .stats-title {
            font-size: 1.2rem;
            color: #2c3e50;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stats-main-container {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        /* Primera fila: Estadísticas en línea */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .stats-box {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #3498db;
            transition: all 0.3s ease;
        }

        .stats-box:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            background: #fff;
        }

        .stats-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .stats-content {
            flex: 1;
        }

        .stats-value {
            font-size: 1.8rem;
            color: #2c3e50;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .stats-label {
            font-size: 0.9rem;
            color: #666;
        }

        /* Segunda fila: Barra de progreso */
        .progress-row {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .progress-title {
            font-size: 1.1rem;
            color: #2c3e50;
            font-weight: 500;
        }

        .progress-percentage {
            font-size: 1.2rem;
            color: #3498db;
            font-weight: 600;
        }

        .progress-bar-container {
            margin-bottom: 15px;
        }

        .progress-track {
            width: 100%;
            height: 16px;
            background-color: #e9ecef;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 10px;
            position: relative;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #3498db, #2980b9);
            border-radius: 8px;
            position: relative;
            transition: width 1s ease-in-out;
        }

        .progress-fill::after {
            content: '';
            position: absolute;
            right: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 0 8px 8px 0;
        }

        .progress-markers {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin-top: 5px;
        }

        .progress-markers::before {
            content: '';
            position: absolute;
            top: -25px;
            left: 0;
            right: 0;
            height: 1px;
            background: #dee2e6;
        }

        .marker {
            font-size: 0.8rem;
            color: #666;
            position: relative;
            transform: translateX(-50%);
        }

        .marker:nth-child(1) { left: 0%; }
        .marker:nth-child(2) { left: 25%; }
        .marker:nth-child(3) { left: 50%; }
        .marker:nth-child(4) { left: 75%; }
        .marker:nth-child(5) { left: 100%; }

        .progress-footer {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            color: #666;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
        }

        .progress-current {
            color: #3498db;
            font-weight: 500;
        }

        .progress-target {
            font-weight: 500;
        }

        /* Responsive para estadísticas */
        @media (max-width: 1200px) {
            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 767px) {
            .stats-row {
                grid-template-columns: 1fr;
            }
            
            .stats-box {
                padding: 15px;
            }
            
            .stats-value {
                font-size: 1.6rem;
            }
            
            .progress-row {
                padding: 20px;
            }
            
            .progress-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .progress-footer {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .global-stats {
                padding: 15px;
            }
            
            .stats-title {
                font-size: 1.1rem;
                margin-bottom: 20px;
            }
            
            .stats-icon {
                width: 40px;
                height: 40px;
                font-size: 1.2rem;
            }
            
            .progress-row {
                padding: 15px;
            }
            
            .progress-track {
                height: 12px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="header-container">
            <div class="header-top">
                <div class="header-title">
                    <h1><i class="fas fa-chart-line"></i> Avance Referenciadores - Super Admin</h1>
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($usuario_logueado['nombres'] . ' ' . $usuario_logueado['apellidos']); ?></span>
                    </div>
                </div>
                <a href="../logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
        </div>
    </header>

    <!-- Breadcrumb Navigation -->
    <div class="breadcrumb-nav">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../superadmin_dashboard.php"><i class="fas fa-home"></i> Panel Super Admin</a></li>
                <li class="breadcrumb-item">
                    <a href="superadmin_avance.php" class="text-decoration-none">
                        <i class="fas fa-chart-line"></i> Avance Referenciadores
                    </a>
                </li>
            </ol>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="dashboard-title">
                <i class="fas fa-users-line"></i>
                <span>Monitoreo de Avance - Referenciadores</span>
            </div>
            <p class="dashboard-subtitle">
                Visualice el progreso de todos los referenciadores activos del sistema. 
                Compare el avance individual y global vs las metas establecidas.
            </p>
        </div>
        
        <!-- Estadísticas Globales - MEJORADA (SIEMPRE muestra todos) -->
        <div class="global-stats">
            <div class="stats-title">
                <i class="fas fa-chart-bar"></i>
                <span>Resumen del Avance Global</span>
            </div>
            
            <!-- Grid de 2 filas: Estadísticas principales arriba, barra abajo -->
            <div class="stats-main-container">
                <!-- Primera fila: 4 estadísticas en línea -->
                <div class="stats-row">
                    <div class="stats-box">
                        <div class="stats-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stats-content">
                            <div class="stats-value"><?php echo $totalReferenciadores; ?></div>
                            <div class="stats-label">Referenciadores Activos</div>
                        </div>
                    </div>
                    
                    <div class="stats-box">
                        <div class="stats-icon">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="stats-content">
                            <div class="stats-value"><?php echo number_format($totalReferidos, 0, ',', '.'); ?></div>
                            <div class="stats-label">Referidos Registrados</div>
                        </div>
                    </div>
                    
                    <div class="stats-box">
                        <div class="stats-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <div class="stats-content">
                            <div class="stats-value"><?php echo number_format($totalTope, 0, ',', '.'); ?></div>
                            <div class="stats-label">Meta Total</div>
                        </div>
                    </div>
                    
                    <div class="stats-box">
                        <div class="stats-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stats-content">
                            <div class="stats-value"><?php echo $porcentajeGlobal; ?>%</div>
                            <div class="stats-label">Avance Global</div>
                        </div>
                    </div>
                </div>
                
                <!-- Segunda fila: Barra de progreso completa -->
                <div class="progress-row">
                    <div class="progress-header">
                        <span class="progress-title">Progreso Global del Sistema</span>
                        <span class="progress-percentage"><?php echo $porcentajeGlobal; ?>%</span>
                    </div>
                    <div class="progress-bar-container">
                        <div class="progress-track">
                            <div class="progress-fill" style="width: <?php echo $porcentajeGlobal; ?>%"></div>
                        </div>
                        <div class="progress-markers">
                            <span class="marker">0%</span>
                            <span class="marker">25%</span>
                            <span class="marker">50%</span>
                            <span class="marker">75%</span>
                            <span class="marker">100%</span>
                        </div>
                    </div>
                    <div class="progress-footer">
                        <span class="progress-current"><?php echo number_format($totalReferidos, 0, ',', '.'); ?> referidos registrados</span>
                        <span class="progress-target">Meta: <?php echo number_format($totalTope, 0, ',', '.'); ?> referidos</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- NUEVO: Filtros de Búsqueda (debajo de estadísticas globales) -->
        <div class="filtros-container">
            <div class="filtros-title">
                <i class="fas fa-filter"></i>
                <span>Filtrar Referenciadores</span>
            </div>
            
            <form method="GET" action="" class="filtros-form" id="filtrosForm">
                <!-- Nombre (con búsqueda en tiempo real) -->
                <div class="form-group">
                    <label for="nombre"><i class="fas fa-user"></i> Nombre</label>
                    <input type="text" 
                           id="nombre" 
                           name="nombre" 
                           class="form-control" 
                           placeholder="Buscar por nombre..." 
                           value="<?php echo htmlspecialchars($filtros['nombre']); ?>"
                           onkeyup="actualizarFiltros()">
                </div>
                
                <!-- Zona -->
                <div class="form-group">
                    <label for="id_zona"><i class="fas fa-map-marker-alt"></i> Zona</label>
                    <select id="id_zona" name="id_zona" class="form-select" onchange="actualizarFiltros()">
                        <option value="">Todas las zonas</option>
                        <?php foreach ($zonas as $zona): ?>
                            <option value="<?php echo $zona['id_zona']; ?>" 
                                <?php echo $filtros['id_zona'] == $zona['id_zona'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($zona['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Sector -->
                <div class="form-group">
                    <label for="id_sector"><i class="fas fa-th-large"></i> Sector</label>
                    <select id="id_sector" name="id_sector" class="form-select" onchange="actualizarFiltros()">
                        <option value="">Todos los sectores</option>
                        <?php foreach ($sectores as $sector): ?>
                            <option value="<?php echo $sector['id_sector']; ?>" 
                                <?php echo $filtros['id_sector'] == $sector['id_sector'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sector['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Puesto de Votación -->
                <div class="form-group">
                    <label for="id_puesto_votacion"><i class="fas fa-vote-yea"></i> Puesto de Votación</label>
                    <select id="id_puesto_votacion" name="id_puesto_votacion" class="form-select" onchange="actualizarFiltros()">
                        <option value="">Todos los puestos</option>
                        <?php foreach ($puestosVotacion as $puesto): ?>
                            <option value="<?php echo $puesto['id_puesto']; ?>" 
                                <?php echo $filtros['id_puesto_votacion'] == $puesto['id_puesto'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($puesto['nombre'] . ' (' . $puesto['sector_nombre'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Última Fecha de Acceso (solo una fecha) -->
                <div class="form-group">
                    <label for="fecha_acceso"><i class="fas fa-calendar-alt"></i> Último acceso</label>
                    <input type="date" 
                           id="fecha_acceso" 
                           name="fecha_acceso" 
                           class="form-control" 
                           value="<?php echo htmlspecialchars($filtros['fecha_acceso']); ?>"
                           onchange="actualizarFiltros()">
                </div>
                
                <!-- Botones de acción -->
                <div class="form-group filtros-actions">
                    <button type="button" class="btn-buscar" onclick="actualizarFiltros()">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                    <button type="button" class="btn-limpiar" onclick="limpiarFiltros()">
                        <i class="fas fa-times"></i> Limpiar
                    </button>
                </div>
            </form>
            
            <!-- Filtros activos -->
            <?php 
            $filtrosActivos = array_filter($filtros, function($valor) {
                return !empty($valor);
            });
            
            if (!empty($filtrosActivos)): ?>
            <div class="active-filters">
                <div class="filter-section-title">
                    <i class="fas fa-check-circle"></i> Filtros aplicados:
                </div>
                <div>
                    <?php foreach ($filtrosActivos as $clave => $valor): 
                        $etiqueta = '';
                        $valorMostrar = htmlspecialchars($valor);
                        
                        switch($clave) {
                            case 'nombre':
                                $etiqueta = "Nombre: $valorMostrar";
                                break;
                            case 'id_zona':
                                $zonaNombre = '';
                                foreach ($zonas as $zona) {
                                    if ($zona['id_zona'] == $valor) {
                                        $zonaNombre = $zona['nombre'];
                                        break;
                                    }
                                }
                                $etiqueta = "Zona: " . htmlspecialchars($zonaNombre);
                                break;
                            case 'id_sector':
                                $sectorNombre = '';
                                foreach ($sectores as $sector) {
                                    if ($sector['id_sector'] == $valor) {
                                        $sectorNombre = $sector['nombre'];
                                        break;
                                    }
                                }
                                $etiqueta = "Sector: " . htmlspecialchars($sectorNombre);
                                break;
                            case 'id_puesto_votacion':
                                $puestoNombre = '';
                                foreach ($puestosVotacion as $puesto) {
                                    if ($puesto['id_puesto'] == $valor) {
                                        $puestoNombre = $puesto['nombre'] . ' (' . $puesto['sector_nombre'] . ')';
                                        break;
                                    }
                                }
                                $etiqueta = "Puesto: " . htmlspecialchars($puestoNombre);
                                break;
                            case 'fecha_acceso':
                                $etiqueta = "Último acceso: " . date('d/m/Y', strtotime($valor));
                                break;
                        }
                        
                        if (!empty($etiqueta)):
                    ?>
                        <span class="filter-badge" id="filtro-<?php echo $clave; ?>">
                            <?php echo $etiqueta; ?>
                            <span class="close" onclick="eliminarFiltro('<?php echo $clave; ?>')">&times;</span>
                        </span>
                    <?php 
                        endif;
                    endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- NUEVO: Resultados de búsqueda -->
        <?php if (!empty($filtrosActivos)): ?>
        <div class="resultados-info">
            <div class="resultados-text">
                <i class="fas fa-info-circle"></i>
                <span>
                    Mostrando <span class="resultados-count"><?php echo $referenciadoresFiltradosCount; ?></span> 
                    referenciador(es) que coinciden con los filtros aplicados
                </span>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Lista de Referenciadores -->
        <div class="referenciadores-list">
            <div class="list-title">
                <i class="fas fa-list-ol"></i>
                <span>Progreso Individual por Referenciador <?php echo !empty($filtrosActivos) ? '(Filtrados)' : ''; ?></span>
            </div>
            
            <?php if (empty($referenciadores)): ?>
                <?php if (!empty($filtrosActivos)): ?>
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <p>No se encontraron referenciadores con los filtros aplicados.</p>
                    <button type="button" class="btn-reset-filters" onclick="limpiarFiltros()">
                        <i class="fas fa-times"></i> Limpiar filtros
                    </button>
                </div>
                <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-users-slash"></i>
                    <p>No hay referenciadores activos registrados en el sistema.</p>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <?php foreach ($referenciadores as $referenciador): ?>
                    <?php 
                    $porcentaje = $referenciador['porcentaje_tope'] ?? 0;
                    
                    // Determinar clase de color según porcentaje
                    $progressClass = 'progress-bajo';
                    if ($porcentaje >= 75) $progressClass = 'progress-excelente';
                    elseif ($porcentaje >= 50) $progressClass = 'progress-bueno';
                    elseif ($porcentaje >= 25) $progressClass = 'progress-medio';
                    ?>
                    
                    <div class="referenciador-card">
                        <div class="referenciador-header">
                            <div class="user-info-section">
                                <div class="user-avatar">
                                    <?php if (!empty($referenciador['foto_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($referenciador['foto_url']); ?>" alt="Foto">
                                    <?php else: ?>
                                        <div style="width: 100%; height: 100%; background: #eaeaea; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-user" style="color: #95a5a6; font-size: 1.5rem;"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="user-details">
                                    <div class="user-name">
                                        <?php echo htmlspecialchars($referenciador['nombres'] . ' ' . $referenciador['apellidos']); ?>
                                    </div>
                                    <div class="user-info-text">
                                        <span>Cédula: <?php echo htmlspecialchars($referenciador['cedula'] ?? 'N/A'); ?></span>
                                        <span>Usuario: <?php echo htmlspecialchars($referenciador['nickname'] ?? 'N/A'); ?></span>
                                        <?php if (!empty($referenciador['ultimo_registro'])): ?>
                                        <span>Último acceso: <?php echo date('d/m/Y H:i', strtotime($referenciador['ultimo_registro'])); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="user-stats">
                                <div class="stat-item">
                                    <div class="stat-number"><?php echo $referenciador['total_referenciados'] ?? 0; ?></div>
                                    <div class="stat-desc">Referidos</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number"><?php echo $referenciador['tope'] ?? 0; ?></div>
                                    <div class="stat-desc">Tope</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number"><?php echo $porcentaje; ?>%</div>
                                    <div class="stat-desc">Avance</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Barra de Progreso Individual -->
                        <div class="individual-progress">
                            <div class="progress-label-small">
                                Progreso individual: <?php echo $referenciador['total_referenciados'] ?? 0; ?> de <?php echo $referenciador['tope'] ?? 0; ?> referidos
                            </div>
                            <div class="progress-container-small">
                                <div class="progress-bar-small <?php echo $progressClass; ?>" 
                                     style="width: <?php echo $porcentaje; ?>%">
                                </div>
                            </div>
                            <div class="progress-numbers">
                                <span>0%</span>
                                <span>25%</span>
                                <span>50%</span>
                                <span>75%</span>
                                <span>100%</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="system-footer">
        <div class="container">
            <p>© Derechos de autor Reservados. 
                Ing. Rubén Darío González García • 
                SISGONTech • Colombia © • <?php echo date('Y'); ?>
            </p>
            <p>Contacto: +57 3106310227 • 
                Email: sisgonnet@gmail.com
            </p>
        </div>
    </footer>

   <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // Timer para búsqueda en tiempo real
    var timer;
    
    // Indicador de carga
    var isLoading = false;
    
    // Función para mostrar/ocultar indicador de carga
    function toggleLoading(mostrar) {
        if (mostrar) {
            $('#contenidoReferenciadores').css('opacity', '0.5');
            isLoading = true;
        } else {
            $('#contenidoReferenciadores').css('opacity', '1');
            isLoading = false;
        }
    }
    
    // Función para buscar con AJAX (sin recargar)
    function buscarConAjax() {
        if (isLoading) return;
        
        clearTimeout(timer);
        timer = setTimeout(function() {
            // Obtener valores del formulario
            var nombre = $('#nombre').val();
            var id_zona = $('#id_zona').val();
            var id_sector = $('#id_sector').val();
            var id_puesto_votacion = $('#id_puesto_votacion').val();
            var fecha_acceso = $('#fecha_acceso').val();
            
            // Mostrar indicador de carga
            toggleLoading(true);
            
            // Hacer petición AJAX
            $.ajax({
                url: '../ajax/buscar_referenciadores.php',
                type: 'GET',
                data: {
                    nombre: nombre,
                    id_zona: id_zona,
                    id_sector: id_sector,
                    id_puesto_votacion: id_puesto_votacion,
                    fecha_acceso: fecha_acceso
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Actualizar el contenido de referenciadores
                        $('#contenidoReferenciadores').html(response.html);
                        
                        // Actualizar los badges de filtros activos
                        actualizarBadgesFiltros(response.filtros_activos, response.tiene_filtros);
                        
                        // Aplicar animaciones a las nuevas barras de progreso
                        animarBarrasProgreso();
                        
                        // Restaurar eventos hover en las nuevas tarjetas
                        restaurarEventosHover();
                    } else {
                        console.error('Error en la búsqueda:', response.error);
                        alert('Error al realizar la búsqueda');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error AJAX:', error);
                    alert('Error de conexión. Por favor, intente nuevamente.');
                },
                complete: function() {
                    // Ocultar indicador de carga
                    toggleLoading(false);
                }
            });
        }, 500); // 500ms de delay para evitar múltiples peticiones
    }
    
    // Función para actualizar los badges de filtros activos
    function actualizarBadgesFiltros(filtrosActivos, tieneFiltros) {
        var container = $('#activeFiltersContainer');
        var listContainer = $('#filtrosActivosList');
        
        if (tieneFiltros && Object.keys(filtrosActivos).length > 0) {
            var html = '<div class="filter-section-title">';
            html += '<i class="fas fa-check-circle"></i> Filtros aplicados:';
            html += '</div>';
            html += '<div id="filtrosActivosList">';
            
            for (var clave in filtrosActivos) {
                if (filtrosActivos.hasOwnProperty(clave)) {
                    var filtro = filtrosActivos[clave];
                    html += '<span class="filter-badge" id="filtro-' + clave + '">';
                    html += filtro.etiqueta;
                    html += '<span class="close" onclick="eliminarFiltro(\'' + clave + '\')">&times;</span>';
                    html += '</span>';
                }
            }
            
            html += '</div>';
            container.html(html).show();
        } else {
            container.html('').hide();
        }
    }
    
    // Función para limpiar todos los filtros
    function limpiarFiltros() {
        $('#nombre').val('');
        $('#id_zona').val('');
        $('#id_sector').val('');
        $('#id_puesto_votacion').val('');
        $('#fecha_acceso').val('');
        
        buscarConAjax();
    }
    
    // Función para eliminar un filtro específico
    function eliminarFiltro(filtro) {
        switch(filtro) {
            case 'nombre':
                $('#nombre').val('');
                break;
            case 'id_zona':
                $('#id_zona').val('');
                break;
            case 'id_sector':
                $('#id_sector').val('');
                break;
            case 'id_puesto_votacion':
                $('#id_puesto_votacion').val('');
                break;
            case 'fecha_acceso':
                $('#fecha_acceso').val('');
                break;
        }
        
        buscarConAjax();
    }
    
    // Función para animar las barras de progreso
    function animarBarrasProgreso() {
        $('.progress-bar-small').each(function() {
            var width = $(this).css('width');
            $(this).css('width', '0');
            
            setTimeout(() => {
                $(this).animate({
                    width: width
                }, 1000);
            }, 300);
        });
    }
    
    // Función para restaurar eventos hover en las tarjetas
    function restaurarEventosHover() {
        $('.referenciador-card').hover(
            function() {
                $(this).css('transform', 'translateY(-5px)');
            },
            function() {
                $(this).css('transform', 'translateY(0)');
            }
        );
    }
    
    $(document).ready(function() {
        // Efecto de animación para las barras de progreso al cargar
        animarBarrasProgreso();
        
        // Efecto hover en tarjetas
        restaurarEventosHover();
        
        // Mejorar UX: Auto-focus en el campo de búsqueda
        $('#nombre').focus();
        
        // Configurar eventos para búsqueda en tiempo real
        $('#nombre').on('keyup', buscarConAjax);
        $('#id_zona').on('change', buscarConAjax);
        $('#id_sector').on('change', buscarConAjax);
        $('#id_puesto_votacion').on('change', buscarConAjax);
        $('#fecha_acceso').on('change', buscarConAjax);
        
        // Configurar botones
        $('.btn-buscar').on('click', buscarConAjax);
        $('.btn-limpiar').on('click', limpiarFiltros);
        
        // Ocultar contenedor de filtros activos si no hay filtros inicialmente
        if ($('#filtrosActivosList').children().length === 0) {
            $('#activeFiltersContainer').hide();
        }
        
        // Actualizar estadísticas cada 30 segundos (opcional)
        setInterval(function() {
            // Aquí podrías agregar una llamada AJAX para actualizar en tiempo real
            // si necesitas datos en vivo
        }, 30000);
    });
</script>
</body>
</html>