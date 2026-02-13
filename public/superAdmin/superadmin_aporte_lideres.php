<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../models/LiderModel.php';
require_once __DIR__ . '/../../models/ReferenciadoModel.php';
require_once __DIR__ . '/../../models/SistemaModel.php';

// Verificar si el usuario está logueado y es SuperAdmin
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    header('Location: ../../index.php');
    exit();
}

$pdo = Database::getConnection();
$usuarioModel = new UsuarioModel($pdo);
$liderModel = new LiderModel($pdo);
$referenciadoModel = new ReferenciadoModel($pdo);
$sistemaModel = new SistemaModel($pdo);

// Obtener datos del usuario logueado
$usuario_logueado = $usuarioModel->getUsuarioById($_SESSION['id_usuario']);

// Obtener filtros del formulario
$filtro_nombre = $_GET['filtro_nombre'] ?? '';
$filtro_cc = $_GET['filtro_cc'] ?? '';
$filtro_referenciador = $_GET['filtro_referenciador'] ?? '';
$filtro_min_referidos = $_GET['filtro_min_referidos'] ?? '';

// ==============================================
// 1. OBTENER PARÁMETROS DE PAGINACIÓN
// ==============================================
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$perPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 25;
if ($perPage <= 0) $perPage = 25;
if ($page <= 0) $page = 1;

// ==============================================
// 2. CONFIGURAR FILTROS PARA EL MODELO
// ==============================================
$filters = [];
if (!empty($filtro_nombre)) {
    $filters['search'] = $filtro_nombre;
}
if (!empty($filtro_referenciador)) {
    $filters['id_usuario'] = $filtro_referenciador;
}
// NUEVO: Añadir filtros que irán directamente a SQL
if (!empty($filtro_cc)) {
    $filters['cc'] = $filtro_cc;  // Este ahora va a SQL
}
if (!empty($filtro_min_referidos) && is_numeric($filtro_min_referidos)) {
    $filters['min_referidos'] = (int)$filtro_min_referidos;  // Este ahora va a SQL
}

// ==============================================
// 3. OBTENER LÍDERES PAGINADOS CON ESTADÍSTICAS
// ==============================================
$resultadoLideres = $liderModel->getPaginatedWithStats($filters, $page, $perPage, 'cantidad_referidos', 'DESC');

if (!$resultadoLideres['success']) {
    $error_message = 'Error al cargar líderes: ' . $resultadoLideres['message'];
    $lideresConEstadisticas = [];
    $pagination = ['total' => 0, 'current_page' => 1, 'total_pages' => 1, 'per_page' => $perPage];
} else {
    $lideresConEstadisticas = $resultadoLideres['data'];
    $pagination = $resultadoLideres['pagination'];
}

// ==============================================
// 4. OBTENER REFERENCIADORES ACTIVOS PARA EL FILTRO
// ==============================================
$referenciadoresActivos = $usuarioModel->getReferenciadoresActivos();

// ==============================================
// 5. OBTENER INFORMACIÓN DEL SISTEMA (para el footer)
// ==============================================
$infoSistema = $sistemaModel->getInformacionSistema();
$fecha_formateada = date('d/m/Y H:i:s');

// Obtener información completa de la licencia
$licenciaInfo = $sistemaModel->getInfoCompletaLicencia();

// Extraer valores
$infoSistema = $licenciaInfo['info'];
$diasRestantes = $licenciaInfo['dias_restantes'];
$validaHastaFormatted = $licenciaInfo['valida_hasta_formatted'];
$fechaInstalacionFormatted = $licenciaInfo['fecha_instalacion_formatted'];

// PARA LA BARRA QUE DISMINUYE: Calcular porcentaje RESTANTE
$porcentajeRestante = $sistemaModel->getPorcentajeRestanteLicencia();

// Color de la barra basado en lo que RESTA
if ($porcentajeRestante > 50) {
    $barColor = 'bg-success';
} elseif ($porcentajeRestante > 25) {
    $barColor = 'bg-warning';
} else {
    $barColor = 'bg-danger';
}

// ==============================================
// 6. OBTENER ESTADÍSTICAS GLOBALES (INDEPENDIENTES DE FILTROS)
// ==============================================
// ESTAS SON LAS ESTADÍSTICAS QUE SE MUESTRAN EN LAS TARJETAS SUPERIORES
$totalLideresGlobal = $liderModel->countAll([]); // Total global sin filtros
$totalLideresActivosGlobal = $liderModel->countActivos([]); // Activos globales sin filtros
$totalReferidosSistema = $referenciadoModel->countReferenciadosActivos(); // Total referidos del sistema
$totalReferidosPorLideresGlobal = $liderModel->getTotalReferidosGlobal(); // Referidos por líderes global
$topLiderGlobal = $liderModel->getTopLiderGlobal(); // Top líder global

// ==============================================
// 7. PROCESAR LÍDERES DE LA PÁGINA ACTUAL
// ==============================================
// Solo procesamos los líderes de la página actual para mostrar en la tabla
foreach ($lideresConEstadisticas as &$lider) {
    $cantidadReferidos = $lider['cantidad_referidos'] ?? 0;
    
    // Agregar porcentaje de contribución (usando total global)
    $lider['porcentaje_contribucion'] = $totalReferidosSistema > 0 ? 
        round(($cantidadReferidos * 100) / $totalReferidosSistema, 2) : 0;
    
    // Asegurar que tengamos referenciador_nombre y referenciador_id
    $lider['referenciador_nombre'] = $lider['referenciador_nombre'] ?? 'No asignado';
    $lider['referenciador_id'] = $lider['referenciador_id'] ?? null;
}

// ==============================================
// 9. ESTADÍSTICAS QUE SE MUESTRAN (TODAS GLOBALES)
// ==============================================
$estadisticas = [
    // ESTADÍSTICAS GLOBALES (independientes de filtros)
    'total_lideres' => $totalLideresGlobal,
    'total_lideres_activos' => $totalLideresActivosGlobal,
    'total_referidos_por_lideres' => $totalReferidosPorLideresGlobal,
    'porcentaje_total' => $totalReferidosSistema > 0 ? 
        round(($totalReferidosPorLideresGlobal * 100) / $totalReferidosSistema, 2) : 0,
    'total_referidos_sistema' => $totalReferidosSistema,
    'top_lider_nombre' => $topLiderGlobal ? $topLiderGlobal['nombre'] : 'N/A',
    'top_lider_referidos' => $topLiderGlobal ? $topLiderGlobal['referidos'] : 0,
    
    // Información de paginación (solo para referencia interna)
    'mostrando_actualmente' => count($lideresConEstadisticas),
    'total_filtrados' => $pagination['total'] ?? 0,
    'pagina_actual' => $page,
    'total_paginas' => $pagination['total_pages'] ?? 1
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aporte de Líderes - Panel Auditoría</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../styles/aporte_lideres.css">
    <style>
        /* Estilos adicionales para búsqueda en tiempo real */
        #loading-indicator {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 1000;
            background: white;
            padding: 10px 15px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid #dee2e6;
            display: none;
        }
        
        .form-control:focus {
            border-color: #6f42c1;
            box-shadow: 0 0 0 0.25rem rgba(111, 66, 193, 0.25);
        }
        
        .search-highlight {
            background-color: #fff3cd;
            transition: background-color 0.3s ease;
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
        
    </style>
</head>
<body>
    <!-- Header REORGANIZADO - Título a la izquierda con usuario -->
    <header class="header-auditoria">
        <div class="container">
            <div class="header-content">
                <!-- Lado izquierdo: Título + Usuario -->
                <div class="header-left">
                    <div class="header-title-area">
                        <div class="header-title">
                            <h1><i class="fas fa-chart-pie me-2"></i>Aporte de Líderes</h1>
                        </div>
                    </div>
                    <span class="user-info-header">
                        <i class="fas fa-user me-1"></i>
                        <?php echo htmlspecialchars($usuario_logueado['nombres'] . ' ' . $usuario_logueado['apellidos']); ?>
                    </span>
                </div>
                
                <!-- Lado derecho: Botones -->
                <div class="header-right">
                    <a href="../logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt me-1"></i> Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </header>
    <!-- Breadcrumb Navigation -->
    <div class="breadcrumb-nav">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../superadmin_dashboard.php"><i class="fas fa-home"></i> Panel Super Admin</a></li>
                <li class="breadcrumb-item"><a href="superadmin_auditoria.php"><i class="fas fa-database"></i> Auditoría</a></li>
                <li class="breadcrumb-item active"><i class="fas fa-users"></i> Aporte lideres</li>
            </ol>
        </nav>
    </div>
    <!-- Indicador de carga para búsqueda en tiempo real -->
    <div id="loading-indicator">
        <div class="spinner-border spinner-border-sm text-purple me-2" role="status">
            <span class="visually-hidden">Cargando...</span>
        </div>
        <small class="text-muted">Buscando...</small>
    </div>

    <div class="container-fluid">
        <!-- Mostrar error si existe -->
        <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo htmlspecialchars($error_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <!-- Filtros -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="filter-card">
                    <h5 class="mb-3"><i class="fas fa-filter me-2"></i>Filtros de Búsqueda</h5>
                    <form method="GET" action="" id="formFiltros" class="row g-3">
                        <!-- Campos ocultos para paginación -->
                        <input type="hidden" name="page" id="inputPage" value="1">
                        <input type="hidden" name="per_page" id="inputPerPage" value="<?php echo htmlspecialchars($perPage); ?>">
                        
                        <!-- Búsqueda en tiempo real para nombre y cédula -->
                        <div class="col-md-4">
                            <label class="form-label">Nombre del Líder</label>
                            <input type="text" class="form-control" name="filtro_nombre" 
                                id="filtro_nombre"
                                value="<?php echo htmlspecialchars($filtro_nombre); ?>"
                                placeholder="Buscar por nombre..."
                                autocomplete="off">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Cédula</label>
                            <input type="text" class="form-control" name="filtro_cc" 
                                id="filtro_cc"
                                value="<?php echo htmlspecialchars($filtro_cc); ?>"
                                placeholder="Buscar por cédula..."
                                autocomplete="off"
                                oninput="verificarBusquedaCedula(this.value)">
                            <small id="cedula-helper" class="text-muted" style="display: none;">
                                <i class="fas fa-info-circle"></i> Búsqueda por cédula específica
                            </small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Referenciador</label>
                            <select class="form-select" name="filtro_referenciador" id="filtro_referenciador">
                                <option value="">Todos los referenciadores</option>
                                <?php foreach ($referenciadoresActivos as $referenciador): ?>
                                    <option value="<?php echo $referenciador['id_usuario']; ?>"
                                        <?php echo ($filtro_referenciador == $referenciador['id_usuario']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($referenciador['nombres'] . ' ' . $referenciador['apellidos']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Mín. Referidos</label>
                            <input type="number" class="form-control" name="filtro_min_referidos" 
                                id="filtro_min_referidos"
                                value="<?php echo htmlspecialchars($filtro_min_referidos); ?>"
                                placeholder="0" min="0">
                        </div>
                        <div class="col-12 mt-3">
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <button type="submit" class="btn btn-purple" id="btnAplicarFiltros">
                                    <i class="fas fa-search me-1"></i> Aplicar Filtros
                                </button>
                                <a href="superadmin_aporte_lideres.php" class="btn btn-outline-secondary" id="btnLimpiarFiltros">
                                    <i class="fas fa-times me-1"></i> Limpiar Filtros
                                </a>
                                <button type="button" class="btn-export" onclick="exportarExcel()">
                                    <i class="fas fa-file-excel me-1"></i> Exportar Excel
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Estadísticas - AHORA SOLO 3 TARJETAS -->
        <div class="row mb-4" id="estadisticas-container">
            <!-- Tarjeta 1: Total Líderes -->
            <div class="col-md-4">
                <div class="card card-stat">
                    <div class="card-body">
                        <h6 class="card-title text-muted">Total Líderes</h6>
                        <h2 class="card-text" id="total-lideres"><?php echo $estadisticas['total_lideres']; ?></h2>
                        <small class="text-muted" id="lideres-activos">
                            <?php echo $estadisticas['total_lideres_activos']; ?> activos
                        </small>
                    </div>
                </div>
            </div>
            
            <!-- Tarjeta 2: Total Referidos -->
            <div class="col-md-4">
                <div class="card card-stat">
                    <div class="card-body">
                        <h6 class="card-title text-muted">Total Referidos</h6>
                        <h2 class="card-text" id="total-referidos"><?php echo $estadisticas['total_referidos_por_lideres']; ?></h2>
                        <div class="progress">
                            <div class="progress-bar bg-success" 
                                 id="barra-porcentaje"
                                 style="width: <?php echo $estadisticas['porcentaje_total']; ?>%"
                                 role="progressbar">
                                <?php echo $estadisticas['porcentaje_total']; ?>%
                            </div>
                        </div>
                        <small class="text-muted">Porcentaje del total de referidos</small>
                    </div>
                </div>
            </div>
            
            <!-- Tarjeta 3: Top Líder -->
            <div class="col-md-4">
                <div class="card card-stat">
                    <div class="card-body">
                        <h6 class="card-title text-muted">Top Líder</h6>
                        <h4 class="card-text" id="top-lider-nombre">
                            <?php echo htmlspecialchars($estadisticas['top_lider_nombre']); ?>
                        </h4>
                        <small class="text-muted" id="top-lider-referidos">
                            <?php echo $estadisticas['top_lider_referidos']; ?> referidos
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Líderes -->
        <div class="row">
            <div class="col-12">
                <div class="table-container">
                    <div class="table-responsive">
                        <table id="tablaLideres" class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Líder</th>
                                    <th>Cédula</th>
                                    <th>Contacto</th>
                                    <th>Referidos</th>
                                    <th>Referenciador</th>
                                    <th>Contribución</th>
                                    <th>Estado</th>
                                    <th>Fecha Registro</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-lideres">
                                <?php 
                                $contador = 1 + (($page - 1) * $perPage);
                                foreach ($lideresConEstadisticas as $lider): 
                                    $esDestacado = $lider['cantidad_referidos'] >= 10;
                                ?>
                                    <tr class="<?php echo $esDestacado ? 'highlight-row' : ''; ?>" data-id="<?php echo $lider['id_lider']; ?>">
                                        <td><?php echo $contador++; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($lider['nombres'] . ' ' . $lider['apellidos']); ?></strong>
                                            <?php if ($esDestacado): ?>
                                                <span class="badge bg-warning ms-1">
                                                    <i class="fas fa-star"></i> Destacado
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($lider['cc']); ?></td>
                                        <td>
                                            <small class="d-block">
                                                <i class="fas fa-phone me-1"></i>
                                                <?php echo htmlspecialchars($lider['telefono'] ?? 'N/A'); ?>
                                            </small>
                                            <small class="d-block">
                                                <i class="fas fa-envelope me-1"></i>
                                                <?php echo htmlspecialchars($lider['correo'] ?? 'N/A'); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-primary rounded-pill">
                                                    <?php echo $lider['cantidad_referidos']; ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($lider['referenciador_id'])): ?>
                                                <span class="badge bg-info">
                                                    <?php echo htmlspecialchars($lider['referenciador_nombre']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">No asignado</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar bg-success" 
                                                     style="width: <?php echo min($lider['porcentaje_contribucion'], 100); ?>%"
                                                     role="progressbar"
                                                     title="<?php echo $lider['porcentaje_contribucion']; ?>%">
                                                </div>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo $lider['porcentaje_contribucion']; ?>%
                                            </small>
                                        </td>
                                        <td>
                                            <?php if ($lider['estado'] === true || $lider['estado'] === 'true' || $lider['estado'] === 1): ?>
                                                <span class="badge badge-estado-activo">
                                                    <i class="fas fa-check-circle me-1"></i> Activo
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-estado-inactivo">
                                                    <i class="fas fa-times-circle me-1"></i> Inactivo
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                                if (!empty($lider['fecha_creacion'])) {
                                                    echo date('d/m/Y', strtotime($lider['fecha_creacion']));
                                                } else {
                                                    echo 'N/A';
                                                }
                                            ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="button" class="btn btn-outline-info"
                                                        onclick="verDetalleLider(<?php echo $lider['id_lider']; ?>)"
                                                        title="Ver detalles">
                                                    <i class="fas fa-info-circle"></i>
                                                </button>
                                                <?php if ($lider['cantidad_referidos'] > 0): ?>
                                                    <button type="button" class="btn btn-outline-success"
                                                            onclick="verReferidos(<?php echo $lider['id_lider']; ?>, '<?php echo htmlspecialchars($lider['nombres'] . ' ' . $lider['apellidos']); ?>')"
                                                            title="Ver referidos">
                                                        <i class="fas fa-users"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                
                                <?php if (empty($lideresConEstadisticas)): ?>
                                    <tr id="sin-resultados">
                                        <td colspan="10" class="text-center py-4">
                                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                            <h5>No se encontraron líderes</h5>
                                            <p class="text-muted">Intenta con otros filtros de búsqueda</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==============================================
             CONTROLES DE PAGINACIÓN
        ============================================== -->
        <!-- ==============================================
     CONTROLES DE PAGINACIÓN MEJORADOS
============================================== -->
<div class="row mt-4" id="paginacion-container">
    <?php if (isset($pagination) && $pagination['total'] > 0 && count($lideresConEstadisticas) > 0): ?>
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded">
            <div class="text-muted" id="texto-resultados">
                Mostrando <?php echo count($lideresConEstadisticas); ?> de <?php echo $pagination['total']; ?> líderes
                (Página <?php echo $pagination['current_page']; ?> de <?php echo $pagination['total_pages']; ?>)
            </div>
            
            <nav aria-label="Paginación de líderes">
                <ul class="pagination mb-0" id="paginacion-lista">
                    <!-- Botón Primera Página -->
                    <?php if ($pagination['current_page'] > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php 
                            $queryParams = $_GET;
                            $queryParams['page'] = 1;
                            echo http_build_query($queryParams);
                        ?>" title="Primera página">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <!-- Botón Anterior -->
                    <?php if ($pagination['current_page'] > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php 
                            $queryParams = $_GET;
                            $queryParams['page'] = $pagination['current_page'] - 1;
                            echo http_build_query($queryParams);
                        ?>" title="Página anterior">
                            <i class="fas fa-chevron-left"></i> Anterior
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <!-- Mostrar páginas (máximo 5) -->
                    <?php 
                    $startPage = max(1, $pagination['current_page'] - 2);
                    $endPage = min($pagination['total_pages'], $pagination['current_page'] + 2);
                    
                    // Ajustar para mostrar siempre 5 páginas si es posible
                    if ($endPage - $startPage < 4 && $pagination['total_pages'] > 5) {
                        if ($pagination['current_page'] <= 3) {
                            $endPage = min(5, $pagination['total_pages']);
                        } elseif ($pagination['current_page'] >= $pagination['total_pages'] - 2) {
                            $startPage = max($pagination['total_pages'] - 4, 1);
                        }
                    }
                    
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                    <li class="page-item <?php echo $i == $pagination['current_page'] ? 'active' : ''; ?>">
                        <a class="page-link" href="?<?php 
                            $queryParams = $_GET;
                            $queryParams['page'] = $i;
                            echo http_build_query($queryParams);
                        ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    
                    <!-- Botón Siguiente -->
                    <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php 
                            $queryParams = $_GET;
                            $queryParams['page'] = $pagination['current_page'] + 1;
                            echo http_build_query($queryParams);
                        ?>" title="Página siguiente">
                            Siguiente <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <!-- Botón Última Página -->
                    <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php 
                            $queryParams = $_GET;
                            $queryParams['page'] = $pagination['total_pages'];
                            echo http_build_query($queryParams);
                        ?>" title="Última página">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            
            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" 
                        data-bs-toggle="dropdown" aria-expanded="false">
                    Mostrar <?php echo $perPage; ?> por página
                </button>
                <ul class="dropdown-menu">
                    <?php 
                    $itemsPorPagina = [10, 25, 50, 100];
                    foreach ($itemsPorPagina as $num): 
                    ?>
                    <li>
                        <a class="dropdown-item <?php echo $num == $perPage ? 'active' : ''; ?>" 
                           href="?<?php 
                                $queryParams = $_GET;
                                $queryParams['per_page'] = $num;
                                $queryParams['page'] = 1;
                                echo http_build_query($queryParams);
                           ?>">
                            <?php echo $num; ?> por página
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        
        <!-- Navegación rápida -->
        <div class="mt-3 text-center">
            <small class="text-muted me-3">Ir a página:</small>
            <div class="d-inline-flex align-items-center">
                <input type="number" 
                       id="inputIrPagina" 
                       class="form-control form-control-sm" 
                       style="width: 80px;" 
                       min="1" 
                       max="<?php echo $pagination['total_pages']; ?>" 
                       value="<?php echo $page; ?>"
                       onkeypress="if(event.keyCode==13) irAPagina()">
                <button class="btn btn-sm btn-purple ms-2" onclick="irAPagina()">
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
    </div>

    <!-- Footer -->
    <footer class="system-footer">
        <div class="container text-center mb-3">
        <img id="footer-logo" 
            src="../imagenes/Logo-artguru.png" 
            alt="Logo ARTGURU" 
            class="logo-clickable"
            onclick="mostrarModalSistema()"
            title="Haz clic para ver información del sistema"
            data-img-claro="../imagenes/Logo-artguru.png"
            data-img-oscuro="../imagenes/image_no_bg.png">
        </div>

        <div class="container text-center">
            <p>
                <strong>© 2026 Sistema de Gestión Política SGP.</strong> Puerto Gaitán - Meta
                Módulo de SGA Sistema de Gestión Administrativa 2026 SGA Solución de Gestión Administrativa Enterprise Premium 1.0™ desarrollado por SISGONTech Technology®, Conjunto Residencial Portal del Llano, Casa 104, Villavicencio, Meta. - Asesores e-Governance Solutions para Entidades Públicas 2026® SISGONTech
                Propietario software: Yerson Solano Alfonso - ☎️ (+57) 313 333 62 27 - Email: soportesgp@gmail.com © Reservados todos los derechos de autor.
            </p>
        </div>
    </footer>

    <!-- Modal de Información del Sistema -->
    <div class="modal fade modal-system-info" id="modalSistema" tabindex="-1" aria-labelledby="modalSistemaLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalSistemaLabel">
                        <i class="fas fa-info-circle me-2"></i>Información del Sistema
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Logo centrado AGRANDADO -->
                    <div class="modal-logo-container">
                        <img src="../imagenes/Logo-artguru.png" alt="Logo del Sistema" class="modal-logo">
                    </div>
                    
                    <!-- Título del Sistema - ELIMINADO "Sistema SGP" -->
                    <div class="licencia-info">
                        <div class="licencia-header">
                            <h6 class="licencia-title">Licencia Runtime</h6>
                            <span class="licencia-dias">
                                <?php echo $diasRestantes; ?> días restantes
                            </span>
                        </div>
                        
                        <div class="licencia-progress">
                            <!-- BARRA QUE DISMINUYE: muestra el PORCENTAJE RESTANTE -->
                            <div class="licencia-progress-bar <?php echo $barColor; ?>" 
                                style="width: <?php echo $porcentajeRestante; ?>%"
                                role="progressbar" 
                                aria-valuenow="<?php echo $porcentajeRestante; ?>" 
                                aria-valuemin="0" 
                                aria-valuemax="100">
                            </div>
                        </div>
                        
                        <div class="licencia-fecha">
                            <i class="fas fa-calendar-alt me-1"></i>
                            Instalado: <?php echo $fechaInstalacionFormatted; ?> | 
                            Válida hasta: <?php echo $validaHastaFormatted; ?>
                        </div>
                    </div>
                    <div class="feature-image-container">
                        <img src="../imagenes/ingeniero2.png" alt="Logo de Herramienta" class="feature-img-header">
                        <div class="profile-info mt-3">
                            <h4 class="profile-name"><strong>Rubén Darío González García</strong></h4>
                            
                            <small class="profile-description">
                                Ingeniero de Sistemas, administrador de bases de datos, desarrollador de objeto OLE.<br>
                                Magister en Administración Pública.<br>
                                <span class="cio-tag"><strong>CIO de equipo soporte SISGONTECH</strong></span>
                            </small>
                        </div>
                    </div>
                    <!-- Sección de Características -->
                    <div class="row g-4 mb-4">
                        <!-- Efectividad de la Herramienta -->
                        <div class="col-md-6">
                            <div class="feature-card">
                                <div class="feature-icon text-primary mb-3">
                                    <i class="fas fa-bolt fa-2x"></i>
                                </div>
                                <h5 class="feature-title">Efectividad de la Herramienta</h5>
                                <h6 class="text-muted mb-2">Optimización de Tiempos</h6>
                                <p class="feature-text">
                                    Reducción del 70% en el procesamiento manual de datos y generación de reportes de adeptos.
                                </p>
                            </div>
                        </div>
                        
                        <!-- Integridad de Datos -->
                        <div class="col-md-6">
                            <div class="feature-card">
                                <div class="feature-icon text-success mb-3">
                                    <i class="fas fa-database fa-2x"></i>
                                </div>
                                <h5 class="feature-title">Integridad de Datos</h5>
                                <h6 class="text-muted mb-2">Validación Inteligente</h6>
                                <p class="feature-text">
                                    Validación en tiempo real para eliminar duplicados y errores de digitación en la base de datos política.
                                </p>
                            </div>
                        </div>
                        
                        <!-- Monitoreo de Metas -->
                        <div class="col-md-6">
                            <div class="feature-card">
                                <div class="feature-icon text-warning mb-3">
                                    <i class="fas fa-chart-line fa-2x"></i>
                                </div>
                                <h5 class="feature-title">Monitoreo de Metas</h5>
                                <h6 class="text-muted mb-2">Seguimiento Visual</h6>
                                <p class="feature-text">
                                    Seguimiento visual del cumplimiento de objetivos mediante barras de avance dinámicas.
                                </p>
                            </div>
                        </div>
                        
                        <!-- Seguridad Avanzada -->
                        <div class="col-md-6">
                            <div class="feature-card">
                                <div class="feature-icon text-danger mb-3">
                                    <i class="fas fa-shield-alt fa-2x"></i>
                                </div>
                                <h5 class="feature-title">Seguridad Avanzada</h5>
                                <h6 class="text-muted mb-2">Control Total</h6>
                                <p class="feature-text">
                                    Control de acceso jerarquizado y trazabilidad total de ingresos al sistema.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <!-- Botón Uso SGP - Abre enlace en nueva pestaña -->
                    <a href="https://sgp-sistema-de-gestion-politica.webnode.com.co/" 
                       target="_blank" 
                       class="btn btn-primary"
                       onclick="cerrarModalSistema();">
                        <i class="fas fa-external-link-alt me-1"></i> Uso SGP
                    </a>
                    
                    <!-- Botón Cerrar - Solo cierra el modal -->
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para ver referidos del líder -->
    <div class="modal fade" id="modalReferidos" tabindex="-1" aria-labelledby="modalReferidosLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalReferidosLabel">Referidos del Líder</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="loadingReferidos" class="text-center py-5">
                        <div class="spinner-border text-purple" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-3">Cargando referidos...</p>
                    </div>
                    <div id="contenidoReferidos" style="display: none;">
                        <table class="table table-sm" id="tablaReferidos">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Cédula</th>
                                    <th>Teléfono</th>
                                    <th>Fecha Registro</th>
                                    <th>Referenciador</th>
                                </tr>
                            </thead>
                            <tbody id="bodyReferidos">
                                <!-- Los referidos se cargarán aquí -->
                            </tbody>
                        </table>
                    </div>
                    <div id="sinReferidos" class="text-center py-5" style="display: none;">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h5>No tiene referidos</h5>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para detalles del líder -->
    <div class="modal fade" id="modalDetalleLider" tabindex="-1" aria-labelledby="modalDetalleLiderLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalDetalleLiderLabel">Detalles del Líder</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="detalleLiderContent">
                    <!-- Los detalles se cargarán aquí via AJAX -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Inicializar DataTable
        $(document).ready(function() {
            $('#tablaLideres').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                },
                pageLength: <?php echo $perPage; ?>,
                responsive: true,
                order: [[4, 'desc']], // Ordenar por cantidad de referidos descendente
                paging: false, // ← DESACTIVAR paginación de DataTables (usamos la nuestra)
                searching: false, // ← DESACTIVAR búsqueda (usamos nuestros filtros)
                info: false // ← DESACTIVAR info (mostraremos la nuestra)
            });
            
            // Inicializar eventos para búsqueda en tiempo real
            inicializarBusquedaTiempoReal();
        });

        // Variables para búsqueda en tiempo real
        let timeoutBusqueda = null;
        let buscandoActivo = false;

        // Inicializar eventos de búsqueda
        function inicializarBusquedaTiempoReal() {
            // Eventos para inputs de búsqueda en tiempo real
            $('#filtro_nombre, #filtro_cc').on('input', function() {
                buscarEnTiempoReal();
            });
            
            // Eventos para selects
            $('#filtro_referenciador').change(function() {
                buscarEnTiempoReal();
            });
            
            // Evento para mínimo de referidos
            $('#filtro_min_referidos').on('input', function() {
                buscarEnTiempoReal();
            });
            
            // Prevenir envío del formulario con Enter en inputs de búsqueda
            $('#filtro_nombre, #filtro_cc').keypress(function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    buscarEnTiempoReal();
                }
            });
        }
function verificarBusquedaCedula(valor) {
    const helper = document.getElementById('cedula-helper');
    
    // Limpiar solo números para verificar
    const soloNumeros = valor.replace(/[^0-9]/g, '');
    
    if (soloNumeros.length >= 6) { // Si tiene al menos 6 dígitos, probablemente es una cédula
        helper.style.display = 'block';
        helper.className = 'text-info';
        helper.innerHTML = '<i class="fas fa-info-circle"></i> Búsqueda por cédula específica';
    } else if (valor.length > 0 && soloNumeros.length < 6) {
        helper.style.display = 'block';
        helper.className = 'text-warning';
        helper.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Cédula muy corta';
    } else {
        helper.style.display = 'none';
    }
}
        // Función para búsqueda en tiempo real
        function buscarEnTiempoReal() {
    // Cancelar búsqueda anterior si existe
    if (timeoutBusqueda) {
        clearTimeout(timeoutBusqueda);
    }
    
    // Verificar si es búsqueda por cédula
    const filtroCC = $('#filtro_cc').val().trim();
    const soloNumeros = filtroCC.replace(/[^0-9]/g, '');
    
    // Si es búsqueda por cédula (más de 6 dígitos), buscar más rápido
    const delay = (soloNumeros.length >= 6) ? 300 : 500;
    
    // Mostrar indicador de carga
    $('#loading-indicator').show();
    
    // Agregar clase de búsqueda a los inputs
    $('#filtro_nombre, #filtro_cc').addClass('search-highlight');
    
    // Esperar según el tipo de búsqueda
    timeoutBusqueda = setTimeout(function() {
        ejecutarBusqueda();
    }, delay);
}
        // Modificar ejecutarBusqueda para usar los inputs ocultos
function ejecutarBusqueda() {
    if (buscandoActivo) return;
    
    buscandoActivo = true;
    
    // Obtener valores de búsqueda
    const filtroNombre = $('#filtro_nombre').val();
    const filtroCC = $('#filtro_cc').val();
    const filtroReferenciador = $('#filtro_referenciador').val();
    const filtroMinReferidos = $('#filtro_min_referidos').val();
    
    // Obtener parámetros de paginación de inputs ocultos
    const page = $('#inputPage').val() || 1;
    const perPage = $('#inputPerPage').val() || 25;
    
    // Realizar AJAX
    $.ajax({
        url: '../ajax/ajax_buscar_lideres_tiempo_real.php',
        type: 'POST',
        data: {
            action: 'buscar_lideres',
            filtro_nombre: filtroNombre,
            filtro_cc: filtroCC,
            filtro_referenciador: filtroReferenciador,
            filtro_min_referidos: filtroMinReferidos,
            page: page,
            per_page: perPage
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Actualizar tabla
                actualizarTablaLideres(response.lideres, page, perPage);
                
                // Actualizar estadísticas
                actualizarEstadisticas(response.estadisticas);
                
                // Actualizar paginación
                actualizarPaginacion(response.pagination);
                
                // Actualizar contador de resultados
                actualizarContadorResultados(response.pagination);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message || 'Error en la búsqueda'
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo realizar la búsqueda'
            });
        },
        complete: function() {
            $('#loading-indicator').hide();
            $('#filtro_nombre, #filtro_cc').removeClass('search-highlight');
            buscandoActivo = false;
        }
    });
}
// Función para navegar a una página específica
function irAPagina() {
    const input = document.getElementById('inputIrPagina');
    const pagina = parseInt(input.value);
    const maxPaginas = parseInt(input.max);
    
    if (isNaN(pagina) || pagina < 1) {
        Swal.fire({
            icon: 'warning',
            title: 'Página inválida',
            text: 'Por favor ingresa un número de página válido'
        });
        return;
    }
    
    if (pagina > maxPaginas) {
        Swal.fire({
            icon: 'warning',
            title: 'Página fuera de rango',
            text: `La página máxima es ${maxPaginas}`
        });
        return;
    }
    
    // Obtener parámetros actuales
    const urlParams = new URLSearchParams(window.location.search);
    
    // Actualizar página
    urlParams.set('page', pagina);
    
    // Redirigir
    window.location.href = `?${urlParams.toString()}`;
}

// Permitir Enter en el input de ir a página
$('#inputIrPagina').keypress(function(e) {
    if (e.which === 13) {
        irAPagina();
    }
});
        // Función para actualizar la tabla
        function actualizarTablaLideres(lideres, page, perPage) {
            let html = '';
            let contador = 1 + ((page - 1) * perPage);
            
            if (lideres.length === 0) {
                html = `
                    <tr id="sin-resultados">
                        <td colspan="10" class="text-center py-4">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5>No se encontraron líderes</h5>
                            <p class="text-muted">Intenta con otros filtros de búsqueda</p>
                        </td>
                    </tr>
                `;
            } else {
                lideres.forEach(function(lider) {
                    const esDestacado = lider.cantidad_referidos >= 10;
                    const destacadoClass = esDestacado ? 'highlight-row' : '';
                    
                    // Formatear fecha
                    let fechaCreacion = 'N/A';
                    if (lider.fecha_creacion) {
                        const fecha = new Date(lider.fecha_creacion);
                        fechaCreacion = fecha.toLocaleDateString('es-ES', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric'
                        });
                    }
                    
                    // Preparar botones de acción
                    let botones = `
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-info"
                                    onclick="verDetalleLider(${lider.id_lider})"
                                    title="Ver detalles">
                                <i class="fas fa-info-circle"></i>
                            </button>`;
                    
                    if (lider.cantidad_referidos > 0) {
                        const nombreCompleto = escapeHtml(lider.nombres + ' ' + lider.apellidos);
                        botones += `
                            <button type="button" class="btn btn-outline-success"
                                    onclick="verReferidos(${lider.id_lider}, '${nombreCompleto}')"
                                    title="Ver referidos">
                                <i class="fas fa-users"></i>
                            </button>`;
                    }
                    
                    botones += `</div>`;
                    
                    html += `
                        <tr class="${destacadoClass}" data-id="${lider.id_lider}">
                            <td>${contador++}</td>
                            <td>
                                <strong>${escapeHtml(lider.nombres + ' ' + lider.apellidos)}</strong>
                                ${esDestacado ? 
                                    '<span class="badge bg-warning ms-1"><i class="fas fa-star"></i> Destacado</span>' : 
                                    ''}
                            </td>
                            <td>${escapeHtml(lider.cc)}</td>
                            <td>
                                <small class="d-block">
                                    <i class="fas fa-phone me-1"></i>
                                    ${escapeHtml(lider.telefono || 'N/A')}
                                </small>
                                <small class="d-block">
                                    <i class="fas fa-envelope me-1"></i>
                                    ${escapeHtml(lider.correo || 'N/A')}
                                </small>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-primary rounded-pill">
                                        ${lider.cantidad_referidos}
                                    </span>
                                </div>
                            </td>
                            <td>
                                ${lider.referenciador_id ? 
                                    `<span class="badge bg-info">${escapeHtml(lider.referenciador_nombre)}</span>` : 
                                    `<span class="badge bg-secondary">No asignado</span>`}
                            </td>
                            <td>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-success" 
                                         style="width: ${Math.min(lider.porcentaje_contribucion, 100)}%"
                                         role="progressbar"
                                         title="${lider.porcentaje_contribucion}%">
                                    </div>
                                </div>
                                <small class="text-muted">
                                    ${lider.porcentaje_contribucion}%
                                </small>
                            </td>
                            <td>
                                ${(lider.estado === true || lider.estado === 'true' || lider.estado === 1) ? 
                                    '<span class="badge badge-estado-activo"><i class="fas fa-check-circle me-1"></i> Activo</span>' : 
                                    '<span class="badge badge-estado-inactivo"><i class="fas fa-times-circle me-1"></i> Inactivo</span>'}
                            </td>
                            <td>${fechaCreacion}</td>
                            <td>${botones}</td>
                        </tr>
                    `;
                });
            }
            
            // Animación suave para actualizar tabla
            $('#tbody-lideres').fadeOut(200, function() {
                $(this).html(html).fadeIn(300);
            });
        }

        // Función para actualizar estadísticas
        function actualizarEstadisticas(estadisticas) {
            // Actualizar tarjeta 1: Total Líderes
            $('#total-lideres').text(estadisticas.total_lideres || 0);
            $('#lideres-activos').html(`
                ${estadisticas.total_lideres_activos || 0} activos
                ${estadisticas.mostrando_actualmente ? 
                    `<br><small class="text-info">(${estadisticas.mostrando_actualmente} mostrados)</small>` : 
                    ''}
            `);
            
            // Actualizar tarjeta 2: Total Referidos
            $('#total-referidos').text(estadisticas.total_referidos_por_lideres || 0);
            const porcentajeTotal = estadisticas.porcentaje_total || 0;
            $('#barra-porcentaje')
                .css('width', porcentajeTotal + '%')
                .text(porcentajeTotal + '%');
            
            // Actualizar tarjeta 3: Top Líder
            $('#top-lider-nombre').text(estadisticas.top_lider_nombre || 'N/A');
            $('#top-lider-referidos').text(
                (estadisticas.top_lider_referidos || 0) + ' referidos'
            );
        }

       function actualizarPaginacion(pagination) {
    const $paginacionContainer = $('#paginacion-container');
    
    if (!pagination || pagination.total <= 0 || pagination.mostrando <= 0) {
        $paginacionContainer.hide();
        return;
    }
    
    $paginacionContainer.show();
    
    // Actualizar texto de resultados
    $('#texto-resultados').html(`
        Mostrando ${pagination.mostrando} de ${pagination.total} líderes
        (Página ${pagination.current_page} de ${pagination.total_pages})
    `);
    
    // Generar HTML de paginación dinámica
    let paginacionHTML = '';
    const currentPage = pagination.current_page;
    const totalPages = pagination.total_pages;
    
    // Botón Primera Página
    if (currentPage > 1) {
        paginacionHTML += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="cambiarPaginaTiempoReal(1)" title="Primera página">
                    <i class="fas fa-angle-double-left"></i>
                </a>
            </li>
        `;
    }
    
    // Botón Anterior
    if (currentPage > 1) {
        paginacionHTML += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="cambiarPaginaTiempoReal(${currentPage - 1})" title="Página anterior">
                    <i class="fas fa-chevron-left"></i> Anterior
                </a>
            </li>
        `;
    }
    
    // Mostrar puntos suspensivos si hay muchas páginas antes
    if (currentPage > 3) {
        paginacionHTML += `
            <li class="page-item disabled">
                <span class="page-link">...</span>
            </li>
        `;
    }
    
    // Calcular qué páginas mostrar
    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(totalPages, currentPage + 2);
    
    // Asegurar que siempre mostremos 5 páginas si es posible
    if (endPage - startPage < 4 && totalPages > 5) {
        if (currentPage <= 3) {
            endPage = Math.min(5, totalPages);
        } else if (currentPage >= totalPages - 2) {
            startPage = Math.max(totalPages - 4, 1);
        }
    }
    
    // Mostrar páginas
    for (let i = startPage; i <= endPage; i++) {
        paginacionHTML += `
            <li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" onclick="cambiarPaginaTiempoReal(${i})">
                    ${i}
                </a>
            </li>
        `;
    }
    
    // Mostrar puntos suspensivos si hay muchas páginas después
    if (currentPage < totalPages - 2) {
        paginacionHTML += `
            <li class="page-item disabled">
                <span class="page-link">...</span>
            </li>
        `;
    }
    
    // Botón Siguiente
    if (currentPage < totalPages) {
        paginacionHTML += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="cambiarPaginaTiempoReal(${currentPage + 1})" title="Página siguiente">
                    Siguiente <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        `;
    }
    
    // Botón Última Página
    if (currentPage < totalPages) {
        paginacionHTML += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="cambiarPaginaTiempoReal(${totalPages})" title="Última página">
                    <i class="fas fa-angle-double-right"></i>
                </a>
            </li>
        `;
    }
    
    // Actualizar la lista de paginación
    $('#paginacion-lista').html(paginacionHTML);
    
    // Actualizar input de ir a página
    const $inputIrPagina = $('#inputIrPagina');
    if ($inputIrPagina.length) {
        $inputIrPagina.val(currentPage);
        $inputIrPagina.attr('max', totalPages);
    } else {
        // Agregar navegación rápida si no existe
        $paginacionContainer.append(`
            <div class="mt-3 text-center">
                <small class="text-muted me-3">Ir a página:</small>
                <div class="d-inline-flex align-items-center">
                    <input type="number" 
                           id="inputIrPagina" 
                           class="form-control form-control-sm" 
                           style="width: 80px;" 
                           min="1" 
                           max="${totalPages}" 
                           value="${currentPage}">
                    <button class="btn btn-sm btn-purple ms-2" onclick="cambiarPaginaTiempoReal()">
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        `);
        
        // Agregar evento Enter al nuevo input
        $('#inputIrPagina').keypress(function(e) {
            if (e.which === 13) {
                cambiarPaginaTiempoReal();
            }
        });
    }
}
// Función para cambiar de página en tiempo real
function cambiarPaginaTiempoReal(pagina = null) {
    if (pagina === null) {
        const input = document.getElementById('inputIrPagina');
        pagina = parseInt(input.value);
        const maxPaginas = parseInt(input.max);
        
        if (isNaN(pagina) || pagina < 1 || pagina > maxPaginas) {
            Swal.fire({
                icon: 'warning',
                title: 'Página inválida',
                text: 'Por favor ingresa un número de página válido'
            });
            return;
        }
    }
    
    // Actualizar página en inputs ocultos
    $('#inputPage').val(pagina);
    
    // Ejecutar búsqueda con nueva página
    ejecutarBusqueda();
}

        // Función para actualizar contador de resultados
        function actualizarContadorResultados(pagination) {
            // Puedes agregar un indicador visual adicional si quieres
            const $tablaLideres = $('#tablaLideres');
            const $caption = $tablaLideres.find('caption');
            
            if ($caption.length === 0) {
                $tablaLideres.prepend(`
                    <caption class="text-center small text-muted">
                        ${pagination.total} líderes encontrados
                    </caption>
                `);
            } else {
                $caption.text(`${pagination.total} líderes encontrados`);
            }
        }

        // Funciones auxiliares
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

// Función para ver referidos del líder (CORREGIDA - DataTable)
function verReferidos(idLider, nombreLider) {
    $('#modalReferidosLabel').html('Referidos del Líder: ' + nombreLider);
    $('#loadingReferidos').show();
    $('#contenidoReferidos').hide();
    $('#sinReferidos').hide();
    if ($.fn.DataTable.isDataTable('#tablaReferidos')) {
        $('#tablaReferidos').DataTable().destroy();
    }
    $('#bodyReferidos').empty();
    
    $('#modalReferidos').modal('show');
    
    $.ajax({
        url: '../ajax/ajax_aporte_lideres.php',
        type: 'POST',
        data: {
            action: 'get_referidos_lider',
            id_lider: idLider
        },
        dataType: 'json',
        success: function(response) {
            $('#loadingReferidos').hide();
            
            if (response.success && response.referidos && response.referidos.length > 0) {
                var html = '';
                response.referidos.forEach(function(referido) {
                    html += '<tr>';
                    html += '<td>' + (referido.nombre || '') + ' ' + (referido.apellido || '') + '</td>';
                    html += '<td>' + (referido.cedula || 'N/A') + '</td>';
                    html += '<td>' + (referido.telefono || 'N/A') + '</td>';
                    html += '<td>' + (referido.fecha_registro || 'N/A') + '</td>';
                    html += '<td>' + (referido.referenciador_nombre || 'No asignado') + '</td>';
                    html += '</tr>';
                });
                
                $('#bodyReferidos').html(html);
                $('#contenidoReferidos').show();
                setTimeout(function() {
                    $('#tablaReferidos').DataTable({
                        language: {
                            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                        },
                        pageLength: 10,
                        responsive: true,
                        retrieve: false,
                        destroy: true,
                        searching: true,
                        ordering: true
                    });
                }, 100);
                
            } else {
                $('#sinReferidos').show();
            }
        },
        error: function(xhr, status, error) {
            $('#loadingReferidos').hide();
            console.error('Error AJAX:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudieron cargar los referidos'
            });
        }
    });
}

        // Función para ver detalles del líder (mantener la existente)
        function verDetalleLider(idLider) {
            $('#detalleLiderContent').html(`
                <div class="text-center py-5">
                    <div class="spinner-border text-purple" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-3">Cargando detalles...</p>
                </div>
            `);
            
            $('#modalDetalleLider').modal('show');
            
            $.ajax({
                url: '../ajax/ajax_aporte_lideres.php',
                type: 'POST',
                data: {
                    action: 'get_detalle_lider',
                    id_lider: idLider
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        var lider = response.lider;
                        var html = `
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Información Personal</h6>
                                    <table class="table table-sm">
                                        <tr>
                                            <th>Nombres:</th>
                                            <td>${lider.nombres}</td>
                                        </tr>
                                        <tr>
                                            <th>Apellidos:</th>
                                            <td>${lider.apellidos}</td>
                                        </tr>
                                        <tr>
                                            <th>Cédula:</th>
                                            <td>${lider.cc}</td>
                                        </tr>
                                        <tr>
                                            <th>Teléfono:</th>
                                            <td>${lider.telefono || 'N/A'}</td>
                                        </tr>
                                        <tr>
                                            <th>Correo:</th>
                                            <td>${lider.correo || 'N/A'}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6>Información del Sistema</h6>
                                    <table class="table table-sm">
                                        <tr>
                                            <th>Estado:</th>
                                            <td>
                                                ${lider.estado ? 
                                                    '<span class="badge bg-success">Activo</span>' : 
                                                    '<span class="badge bg-danger">Inactivo</span>'}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Referenciador:</th>
                                            <td>${lider.referenciador_nombre || 'No asignado'}</td>
                                        </tr>
                                        <tr>
                                            <th>Cant. Referidos:</th>
                                            <td><span class="badge bg-primary">${lider.cantidad_referidos}</span></td>
                                        </tr>
                                        <tr>
                                            <th>Fecha Creación:</th>
                                            <td>${lider.fecha_creacion}</td>
                                        </tr>
                                        <tr>
                                            <th>Última Actualización:</th>
                                            <td>${lider.fecha_actualizacion || 'N/A'}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <h6>Estadísticas de Contribución</h6>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-success" 
                                             style="width: ${Math.min(lider.porcentaje_contribucion, 100)}%"
                                             role="progressbar">
                                            ${lider.porcentaje_contribucion}% del total de referidos
                                        </div>
                                    </div>
                                    <small class="text-muted">
                                        Este líder ha contribuido con ${lider.cantidad_referidos} referidos, 
                                        representando el ${lider.porcentaje_contribucion}% del total.
                                    </small>
                                </div>
                            </div>
                        `;
                        
                        $('#detalleLiderContent').html(html);
                    } else {
                        $('#detalleLiderContent').html(`
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                ${response.message || 'Error al cargar los detalles'}
                            </div>
                        `);
                    }
                },
                error: function() {
                    $('#detalleLiderContent').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Error al cargar los detalles del líder
                        </div>
                    `);
                }
            });
        }

        // Función para exportar a Excel
        function exportarExcel() {
    Swal.fire({
        title: 'Exportando datos...',
        text: 'Preparando archivo para descarga',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    $.ajax({
        url: 'exportar_excel_lideres.php',
        type: 'POST',
        data: {
            filtros: {
                nombre: $('#filtro_nombre').val(),
                cc: $('#filtro_cc').val(),
                referenciador: $('#filtro_referenciador').val(),
                min_referidos: $('#filtro_min_referidos').val()
            }
        },
        dataType: 'json',
        success: function(response) {
            Swal.close();
            if (response.success) {
                // Crear enlace temporal para descarga
                var link = document.createElement('a');
                link.href = response.file_url;
                link.download = response.file_url.split('/').pop();
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                Swal.fire({
                    icon: 'success',
                    title: '¡Exportación completada!',
                    html: 'Se exportaron <strong>' + response.registros + '</strong> registros<br>' +
                          'El archivo se ha descargado automáticamente',
                    timer: 3000,
                    showConfirmButton: false
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message || 'Error al exportar'
                });
            }
        },
        error: function(xhr, status, error) {
            Swal.close();
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo conectar con el servidor. Error: ' + error
            });
        }
    });
}
    </script>
    <script src="../js/modal-sistema.js"></script> 
</body>
</html>