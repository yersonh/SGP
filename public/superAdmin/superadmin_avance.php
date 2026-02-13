<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../models/ZonaModel.php';
require_once __DIR__ . '/../../models/SectorModel.php';
require_once __DIR__ . '/../../models/PuestoVotacionModel.php';
require_once __DIR__ . '/../../models/SistemaModel.php';
require_once __DIR__ . '/../../models/LlamadaModel.php';

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
$sistemaModel = new SistemaModel($pdo);
$llamadaModel = new LlamadaModel($pdo);

// Obtener datos del usuario logueado
$usuario_logueado = $usuarioModel->getUsuarioById($_SESSION['id_usuario']);

// Obtener listas para los filtros
$zonas = $zonaModel->getAll();
$sectores = $sectorModel->getAll();
$puestosVotacion = $puestoVotacionModel->getAll();

// ============================================================================
// MANTENER: Obtener todos los referenciadores con sus estadísticas (INICIAL)
// ============================================================================
try {
    // Obtener todos los usuarios con estadísticas para cálculo global
    $todosReferenciadores = $usuarioModel->getAllUsuariosActivos();
    
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
    
    // Obtener TODOS los referenciadores para mostrar inicialmente (sin filtros)
    $referenciadoresIniciales = array_filter($todosReferenciadores, function($usuario) {
        return $usuario['tipo_usuario'] === 'Referenciador' && $usuario['activo'] == true;
    });
    
    // Ordenar inicialmente por fecha de creación (más reciente primero)
    usort($referenciadoresIniciales, function($a, $b) {
        $fecha_a = strtotime($a['fecha_creacion'] ?? '2000-01-01');
        $fecha_b = strtotime($b['fecha_creacion'] ?? '2000-01-01');
        return $fecha_b <=> $fecha_a; // Más recientes primero
    });
    
    // Calcular top 5 por referidos
    $referenciadoresOrdenadosPorReferidos = $referenciadoresIniciales;
    usort($referenciadoresOrdenadosPorReferidos, function($a, $b) {
        $referidos_a = $a['total_referenciados'] ?? 0;
        $referidos_b = $b['total_referenciados'] ?? 0;
        return $referidos_b <=> $referidos_a;
    });
    
    // Tomar los primeros 5
    $top5PorReferidos = array_slice($referenciadoresOrdenadosPorReferidos, 0, 5);
    
    // Crear un array con IDs de los top 5 para fácil verificación
    $top5Ids = [];
    foreach ($top5PorReferidos as $top) {
        $top5Ids[] = $top['id_usuario'];
    }
    
    // Calcular estadísticas para los referenciadores iniciales
    $totalIniciales = count($referenciadoresIniciales);
    
} catch (Exception $e) {
    // Manejo de error (mantener como estaba)
    $referenciadoresIniciales = [];
    $referenciadoresGlobales = [];
    $totalReferenciadores = 0;
    $totalReferidos = 0;
    $totalTope = 0;
    $porcentajeGlobal = 0;
    $totalIniciales = 0;
    $top5PorReferidos = [];
    $top5Ids = [];
    error_log("Error al obtener referenciadores: " . $e->getMessage());
}

// Obtener referenciadores para el combo box
$referenciadoresCombo = $usuarioModel->getReferenciadoresParaCombo();

// Info del sistema (igual que antes)
$licenciaInfo = $sistemaModel->getInfoCompletaLicencia();
$infoSistema = $licenciaInfo['info'];
$diasRestantes = $licenciaInfo['dias_restantes'];
$validaHastaFormatted = $licenciaInfo['valida_hasta_formatted'];
$fechaInstalacionFormatted = $licenciaInfo['fecha_instalacion_formatted'];
$porcentajeRestante = $sistemaModel->getPorcentajeRestanteLicencia();

// Color de la barra
if ($porcentajeRestante > 50) {
    $barColor = 'bg-success';
} elseif ($porcentajeRestante > 25) {
    $barColor = 'bg-warning';
} else {
    $barColor = 'bg-danger';
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
    <link rel="stylesheet" href="../styles/superadmin_avance.css">
    <style>
        /* Estilos adicionales para top 5 */
        .top5-badge {
            background: linear-gradient(45deg, #FFD700, #FFA500);
            color: #000;
            font-weight: bold;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            margin-left: 5px;
        }
        
        .stat-item.top5 {
            position: relative;
        }
        
        .stat-item.top5::after {
            content: '👑';
            position: absolute;
            top: -5px;
            right: -5px;
            font-size: 12px;
        }
        
        .referenciador-card.top5 {
            border-left: 4px solid #FFD700;
            background: linear-gradient(90deg, rgba(255,215,0,0.05) 0%, rgba(255,255,255,1) 100%);
        }
        
        .crown-icon {
            color: #FFD700;
            margin-left: 3px;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <!-- Header (mantener igual) -->
    <header class="main-header">
        <div class="header-container">
            <div class="header-top">
                <div class="header-title">
                    <h1><i class="fas fa-user-shield"></i> Panel Super Admin</h1>
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
                <li class="breadcrumb-item active"><a href="superadmin_monitoreos.php"><i class="fas fa-database"></i> Monitores</a></li>
                <li class="breadcrumb-item active"><i class="fas fa-database"></i> Avance Referenciadores</li>
            </ol>
        </nav>
    </div>
<!-- CONTADOR COMPACTO -->
    <div class="countdown-compact-container">
        <div class="countdown-compact">
            <div class="countdown-compact-title">
                <i class="fas fa-hourglass-half"></i>
                <span>Elecciones Legislativas 2026</span>
            </div>
            <div class="countdown-compact-timer">
                <span id="compact-days">00</span>d 
                <span id="compact-hours">00</span>h 
                <span id="compact-minutes">00</span>m 
                <span id="compact-seconds">00</span>s
            </div>
            <div class="countdown-compact-date">
                <i class="fas fa-calendar-alt"></i>
                8 Marzo 2026
            </div>
        </div>
    </div>
    <!-- Main Content -->
    <div class="main-container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <h2><i class="fas fa-chart-line"></i> Avance de Referenciadores</h2>
            <p class="dashboard-subtitle">Monitoreo del progreso de referenciadores en el sistema</p>
        </div>
        
        <!-- ====================================================================
             MANTENER: Estadísticas Globales - EXACTAMENTE IGUAL
        ===================================================================== -->
        <div class="global-stats">
            <div class="stats-title">
                <i class="fas fa-chart-bar"></i>
                <span>Resumen del Avance Global</span>
            </div>
            
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
        
        <!-- ====================================================================
             REEMPLAZAR: Filtros de Búsqueda - NUEVA VERSIÓN CON AJAX
        ===================================================================== -->
        <div class="filtros-container">
            <div class="filtros-title">
                <i class="fas fa-filter"></i>
                <span>Filtrar y Ordenar Referenciadores</span>
            </div>
            
            <!-- Formulario SIN action/method, solo para capturar datos -->
            <div class="filtros-form" id="filtrosForm">
                <!-- Nombre -->
                <div class="form-group">
                    <label for="nombre"><i class="fas fa-user"></i> Nombre</label>
                    <input type="text" 
                           id="nombre" 
                           class="form-control filtro-input" 
                           placeholder="Buscar por nombre..." 
                           data-filtro="nombre">
                </div>
                
                <!-- NUEVO: Combo Box de Referenciadores -->
                <div class="form-group">
                    <label for="id_referenciador"><i class="fas fa-user-tie"></i> Referenciador</label>
                    <select id="id_referenciador" class="form-select filtro-select" data-filtro="id_referenciador">
                        <option value="">Todos los referenciadores</option>
                        <?php foreach ($referenciadoresCombo as $ref): ?>
                            <option value="<?php echo $ref['id_usuario']; ?>">
                                <?php echo htmlspecialchars($ref['nombres'] . ' ' . $ref['apellidos'] . ' '); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Zona -->
                <div class="form-group">
                    <label for="id_zona"><i class="fas fa-map-marker-alt"></i> Zona</label>
                    <select id="id_zona" class="form-select filtro-select" data-filtro="id_zona">
                        <option value="">Todas las zonas</option>
                        <?php foreach ($zonas as $zona): ?>
                            <option value="<?php echo $zona['id_zona']; ?>">
                                <?php echo htmlspecialchars($zona['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Sector -->
                <div class="form-group">
                    <label for="id_sector"><i class="fas fa-th-large"></i> Sector</label>
                    <select id="id_sector" class="form-select filtro-select" data-filtro="id_sector">
                        <option value="">Todos los sectores</option>
                        <?php foreach ($sectores as $sector): ?>
                            <option value="<?php echo $sector['id_sector']; ?>">
                                <?php echo htmlspecialchars($sector['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Puesto de Votación -->
                <div class="form-group">
                    <label for="id_puesto"><i class="fas fa-vote-yea"></i> Puesto de Votación</label>
                    <select id="id_puesto" class="form-select filtro-select" data-filtro="id_puesto">
                        <option value="">Todos los puestos</option>
                        <?php foreach ($puestosVotacion as $puesto): ?>
                            <option value="<?php echo $puesto['id_puesto']; ?>">
                                <?php echo htmlspecialchars($puesto['nombre'] . ' (' . $puesto['sector_nombre'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Porcentaje Mínimo -->
                <div class="form-group">
                    <label for="porcentaje_minimo"><i class="fas fa-percentage"></i> % Avance Mínimo</label>
                    <select id="porcentaje_minimo" class="form-select filtro-select" data-filtro="porcentaje_minimo">
                        <option value="">Todos los porcentajes</option>
                        <option value="100">100% (Completado)</option>
                        <option value="90">90% o más</option>
                        <option value="75">75% o más</option>
                        <option value="50">50% o más</option>
                        <option value="25">25% o más</option>
                        <option value="0">Con algún avance</option>
                    </select>
                </div>
                
                <!-- Ordenar por -->
                <div class="form-group filtro-orden">
                    <label for="ordenar_por"><i class="fas fa-sort-amount-down"></i> Ordenar por</label>
                    <select id="ordenar_por" class="form-select filtro-select" data-filtro="ordenar_por">
                        <option value="fecha_creacion">Fecha de creación</option>
                        <option value="porcentaje_desc">% Avance</option>
                        <option value="referidos_desc">Cantidad de Referidos</option>
                    </select>
                </div>
                
                <!-- Fecha de Acceso -->
                <div class="form-group">
                    <label for="fecha_acceso"><i class="fas fa-calendar-alt"></i> Último acceso</label>
                    <input type="date" 
                           id="fecha_acceso" 
                           class="form-control filtro-input" 
                           data-filtro="fecha_acceso">
                </div>
                
                <!-- Botones de acción -->
                <div class="form-group filtros-actions">
                    <button type="button" class="btn-buscar" id="btnBuscarAjax">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                    <button type="button" class="btn-limpiar" id="btnLimpiarAjax">
                        <i class="fas fa-times"></i> Limpiar
                    </button>
                </div>
            </div>
            
            <!-- Contenedor para filtros activos (se llena dinámicamente) -->
            <div class="active-filters" id="activeFiltersContainer" style="display: none;">
                <div class="filter-section-title">
                    <i class="fas fa-check-circle"></i> Filtros aplicados:
                </div>
                <div id="activeFiltersList"></div>
            </div>
        </div>
        
        <!-- Info de resultados (dinámica) -->
        <div class="resultados-info" id="resultadosInfo" style="display: none;">
            <div class="resultados-text">
                <i class="fas fa-info-circle"></i>
                <span id="resultadosText"></span>
            </div>
        </div>
        
        <!-- ====================================================================
             MODIFICAR: Lista de Referenciadores - Mostrar inicial + actualizar con AJAX
        ===================================================================== -->
        <div class="referenciadores-list" id="referenciadoresContainer">
            <div class="list-title">
                <i class="fas fa-list-ol"></i>
                <span>Progreso Individual por Referenciador</span>
                <span id="filtroIndicator" class="badge bg-info ms-2" style="display: none;">Filtrados</span>
            </div>
            
            <!-- Loading indicator (oculto inicialmente) -->
            <div id="loadingIndicator" class="text-center py-5" style="display: none;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2 text-muted">Buscando referenciadores...</p>
            </div>
            
            <!-- Contenedor para los resultados (inicialmente con datos PHP) -->
            <div id="referenciadoresList">
                <?php if (empty($referenciadoresIniciales)): ?>
                    <div class="no-data">
                        <i class="fas fa-users-slash"></i>
                        <p>No hay referenciadores activos registrados en el sistema.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($referenciadoresIniciales as $referenciador): ?>
                        <?php 
                        $porcentaje = $referenciador['porcentaje_tope'] ?? 0;
                        if (!$porcentaje && $referenciador['tope'] > 0) {
                            $porcentaje = round(($referenciador['total_referenciados'] / $referenciador['tope']) * 100, 2);
                        }
                        
                        // Determinar clase de color según porcentaje
                        $progressClass = 'progress-bajo';
                        if ($porcentaje >= 75) $progressClass = 'progress-excelente';
                        elseif ($porcentaje >= 50) $progressClass = 'progress-bueno';
                        elseif ($porcentaje >= 25) $progressClass = 'progress-medio';
                        
                        // Verificar si está en el top 5
                        $esTop5 = in_array($referenciador['id_usuario'], $top5Ids);
                        $cardClass = $esTop5 ? 'referenciador-card top5' : 'referenciador-card';
                        ?>
                        
                        <div class="<?php echo $cardClass; ?>" data-id="<?php echo $referenciador['id_usuario']; ?>">
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
                                            
                                            <?php if ($esTop5): ?>
                                                <span class="top5-badge" title="Top 5 en referidos">
                                                    <i class="fas fa-trophy"></i> Top 5
                                                </span>
                                            <?php elseif ($porcentaje >= 100): ?>
                                                <span style="color: #4caf50; margin-left: 5px; font-size: 0.8rem;">
                                                    <i class="fas fa-check-circle"></i> Completado
                                                </span>
                                            <?php elseif ($porcentaje >= 75): ?>
                                                <span style="color: #2196f3; margin-left: 5px; font-size: 0.8rem;">
                                                    <i class="fas fa-chart-line"></i> Avanzado
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="user-info-text">
                                            <span>Cédula: <?php echo htmlspecialchars($referenciador['cedula'] ?? 'N/A'); ?></span>
                                            <span>Usuario: <?php echo htmlspecialchars($referenciador['nickname'] ?? 'N/A'); ?></span>
                                            <span>Fecha registro: <?php echo date('d/m/Y', strtotime($referenciador['fecha_creacion'])); ?></span>
                                            <?php if (!empty($referenciador['ultimo_registro'])): ?>
                                            <span>Último acceso: <?php echo date('d/m/Y H:i', strtotime($referenciador['ultimo_registro'])); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="user-stats">
                                    <div class="stat-item <?php echo $esTop5 ? 'top5' : ''; ?>">
                                        <div class="stat-number <?php echo $esTop5 ? 'text-warning fw-bold' : ''; ?>">
                                            <?php echo $referenciador['total_referenciados'] ?? 0; ?>
                                            <?php if ($esTop5): ?>
                                                <i class="fas fa-crown crown-icon"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="stat-desc">Referidos</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-number"><?php echo $referenciador['tope'] ?? 0; ?></div>
                                        <div class="stat-desc">Tope</div>
                                    </div>
                                    <div class="stat-item">
                                        <?php
                                        // Obtener cantidad de trackeados
                                        $trackeados = $llamadaModel->getCantidadTrackeados($referenciador['id_usuario']);
                                        ?>
                                        <div class="stat-number">
                                            <?php echo $trackeados; ?>
                                        </div>
                                        <div class="stat-desc">Trackeados</div>
                                    </div>
                                    <!-- NUEVO: Porcentaje de Calidad -->
                                    <div class="stat-item">
                                        <?php
                                        // Obtener porcentaje de calidad
                                        $porcentajeCalidad = $llamadaModel->getPorcentajeCalidadPorRating($referenciador['id_usuario']);
                                        ?>
                                        <div class="stat-number 
                                            <?php 
                                            // Color según porcentaje
                                            if ($porcentajeCalidad >= 80) echo 'text-success';      // Excelente: 80-100%
                                            elseif ($porcentajeCalidad >= 60) echo 'text-warning';  // Bueno: 60-79%
                                            else echo 'text-danger';                               // Bajo: 0-59%
                                            ?>">
                                            <?php echo $porcentajeCalidad; ?>%
                                        </div>
                                        <div class="stat-desc">Calidad</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Barra de Progreso Individual -->
                            <div class="individual-progress">
                                <div class="progress-label-small">
                                    Progreso individual: <?php echo $referenciador['total_referenciados'] ?? 0; ?> de <?php echo $referenciador['tope'] ?? 0; ?> referidos
                                    <span style="float: right; font-weight: bold; color: <?php echo $porcentaje >= 100 ? '#4caf50' : ($porcentaje >= 75 ? '#2196f3' : '#666'); ?>">
                                        <?php echo $porcentaje; ?>%
                                    </span>
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
            
            <!-- Paginación (se llena dinámicamente con AJAX) -->
            <div id="paginacionContainer" class="pagination-container mt-4" style="display: none;">
                <!-- Aquí se insertará la paginación desde AJAX -->
            </div>
        </div>
    </div>

    <!-- Footer (mantener igual) -->
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

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
       // ====================================================================
// FUNCIONES PRINCIPALES PARA EL SISTEMA DE FILTRADO AJAX
// ====================================================================

// Variable para controlar el estado actual
var vistaActual = 'referenciadores'; // 'referenciadores' o 'lideres'
var referenciadorSeleccionado = null;

// 1. Función para obtener valores de los filtros
function obtenerFiltros() {
    return {
        nombre: $('#nombre').val().trim(),
        id_referenciador: $('#id_referenciador').val() || 0,
        id_zona: $('#id_zona').val() || 0,
        id_sector: $('#id_sector').val() || 0,
        id_puesto: $('#id_puesto').val() || 0,
        fecha_acceso: $('#fecha_acceso').val() || '',
        porcentaje_minimo: $('#porcentaje_minimo').val() || 0,
        ordenar_por: $('#ordenar_por').val() || 'fecha_creacion',
        page: 1,
        limit: 50
    };
}

// 2. Función para mostrar/ocultar filtros activos
function actualizarFiltrosActivos(filtros) {
    var container = $('#activeFiltersContainer');
    var list = $('#activeFiltersList');
    list.empty();
    
    var filtrosMostrar = [];
    
    // Solo mostrar filtros activos cuando no estamos en vista de líderes
    if (vistaActual === 'referenciadores') {
        // Nombre
        if (filtros.nombre) {
            filtrosMostrar.push({
                key: 'nombre',
                label: 'Nombre: ' + filtros.nombre
            });
        }
        
        // Zona
        if (filtros.id_zona > 0) {
            var zonaNombre = $('#id_zona option:selected').text();
            filtrosMostrar.push({
                key: 'id_zona',
                label: 'Zona: ' + zonaNombre
            });
        }
        
        // Sector
        if (filtros.id_sector > 0) {
            var sectorNombre = $('#id_sector option:selected').text();
            filtrosMostrar.push({
                key: 'id_sector',
                label: 'Sector: ' + sectorNombre
            });
        }
        
        // Puesto de votación
        if (filtros.id_puesto > 0) {
            var puestoNombre = $('#id_puesto option:selected').text();
            filtrosMostrar.push({
                key: 'id_puesto',
                label: 'Puesto: ' + puestoNombre
            });
        }
        
        // Porcentaje mínimo
        if (filtros.porcentaje_minimo > 0) {
            var porcentajeText = '';
            switch(filtros.porcentaje_minimo.toString()) {
                case '100': porcentajeText = '100% (Completado)'; break;
                case '90': porcentajeText = '90% o más'; break;
                case '75': porcentajeText = '75% o más'; break;
                case '50': porcentajeText = '50% o más'; break;
                case '25': porcentajeText = '25% o más'; break;
                case '0': porcentajeText = 'Con algún avance'; break;
                default: porcentajeText = filtros.porcentaje_minimo + '% o más';
            }
            filtrosMostrar.push({
                key: 'porcentaje_minimo',
                label: 'Avance mínimo: ' + porcentajeText
            });
        }
        
        // Fecha acceso
        if (filtros.fecha_acceso) {
            filtrosMostrar.push({
                key: 'fecha_acceso',
                label: 'Último acceso: ' + filtros.fecha_acceso
            });
        }
        
        // Ordenar por (si no es el default)
        if (filtros.ordenar_por !== 'fecha_creacion') {
            var ordenText = '';
            switch(filtros.ordenar_por) {
                case 'porcentaje_desc': ordenText = 'Orden: % Avance'; break;
                case 'referidos_desc': ordenText = 'Orden: Cant. Referidos'; break;
            }
            filtrosMostrar.push({
                key: 'ordenar_por',
                label: ordenText
            });
        }
    }
    
    // Mostrar filtro de referenciador si está seleccionado
    if (referenciadorSeleccionado) {
        var referenciadorNombre = $('#id_referenciador option:selected').text();
        filtrosMostrar.push({
            key: 'id_referenciador',
            label: 'Referenciador: ' + referenciadorNombre
        });
    }
    
    // Mostrar u ocultar contenedor
    if (filtrosMostrar.length > 0) {
        container.show();
        $('#filtroIndicator').show();
        
        // Crear badges para cada filtro
        filtrosMostrar.forEach(function(filtro) {
            var badge = $('<span class="filter-badge">')
                .html(filtro.label + ' <span class="close" data-filtro="' + filtro.key + '">&times;</span>')
                .appendTo(list);
        });
    } else {
        container.hide();
        $('#filtroIndicator').hide();
    }
}

// 3. Función principal para buscar
function buscarReferenciadores() {
    var id_referenciador = $('#id_referenciador').val() || 0;
    
    // Si se seleccionó un referenciador, mostrar sus líderes
    if (id_referenciador > 0) {
        buscarLideresPorReferenciador(id_referenciador);
        return;
    }
    
    // Si no hay referenciador seleccionado, buscar referenciadores normalmente
    vistaActual = 'referenciadores';
    referenciadorSeleccionado = null;
    
    filtrosActuales = obtenerFiltros();
    
    // Mostrar loading
    $('#loadingIndicator').show();
    $('#referenciadoresList').hide();
    $('#paginacionContainer').hide();
    
    // Actualizar filtros activos
    actualizarFiltrosActivos(filtrosActuales);
    
    // Restaurar título
    $('.list-title span:first').text('Progreso Individual por Referenciador');
    
    // Construir URL para AJAX
    var url = '../ajax/filtros_referenciadores.php?' + $.param(filtrosActuales);
    
    $.ajax({
        url: url,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                mostrarResultadosReferenciadores(response.data);
            } else {
                mostrarError(response.error || 'Error en la búsqueda');
            }
        },
        error: function(xhr, status, error) {
            mostrarError('Error de conexión: ' + error);
        },
        complete: function() {
            $('#loadingIndicator').hide();
        }
    });
}

// 4. Función para mostrar resultados de referenciadores
function mostrarResultadosReferenciadores(data) {
    var container = $('#referenciadoresList');
    container.empty();
    
    // Actualizar info de resultados
    if (data.filtrosActivos) {
        $('#resultadosInfo').show();
        $('#resultadosText').html(
            'Mostrando <strong>' + data.paginacion.mostrandoDesde + '-' + data.paginacion.mostrandoHasta + 
            '</strong> de <strong>' + data.paginacion.totalResultados + '</strong> referenciadores filtrados'
        );
    } else {
        $('#resultadosInfo').hide();
    }
    
    // Mostrar referenciadores
    if (data.referenciadores.length === 0) {
        var noResults = $('<div class="no-data">')
            .html('<i class="fas fa-search"></i><p>No se encontraron referenciadores con los filtros aplicados.</p>')
            .appendTo(container);
        
        if (data.filtrosActivos) {
            $('<button type="button" class="btn-reset-filters" onclick="limpiarFiltros()">')
                .html('<i class="fas fa-times"></i> Limpiar filtros')
                .appendTo(noResults);
        }
    } else {
        data.referenciadores.forEach(function(referenciador) {
            var porcentaje = referenciador.porcentaje_tope || 0;
            
            // Determinar clase de progreso
            var progressClass = 'progress-bajo';
            if (porcentaje >= 75) progressClass = 'progress-excelente';
            else if (porcentaje >= 50) progressClass = 'progress-bueno';
            else if (porcentaje >= 25) progressClass = 'progress-medio';
            
            // Crear tarjeta
            var card = $('<div class="referenciador-card">')
                .attr('data-id', referenciador.id_usuario)
                .appendTo(container);
            
            // Construir contenido
            var html = `
                <div class="referenciador-header">
                    <div class="user-info-section">
                        <div class="user-avatar">
                            ${referenciador.foto_url ? 
                                '<img src="' + referenciador.foto_url + '" alt="Foto">' : 
                                '<div style="width: 100%; height: 100%; background: #eaeaea; display: flex; align-items: center; justify-content: center;">' +
                                '<i class="fas fa-user" style="color: #95a5a6; font-size: 1.5rem;"></i></div>'}
                        </div>
                        <div class="user-details">
                            <div class="user-name">
                                ${referenciador.nombres} ${referenciador.apellidos}
                                ${porcentaje >= 100 ? 
                                    '<span style="color: #4caf50; margin-left: 5px; font-size: 0.8rem;"><i class="fas fa-check-circle"></i> Completado</span>' : 
                                    (porcentaje >= 75 ? 
                                        '<span style="color: #2196f3; margin-left: 5px; font-size: 0.8rem;"><i class="fas fa-trophy"></i> Avanzado</span>' : '')}
                            </div>
                            <div class="user-info-text">
                                <span>Cédula: ${referenciador.cedula || 'N/A'}</span>
                                <span>Usuario: ${referenciador.nickname || 'N/A'}</span>
                                <span>Fecha registro: ${new Date(referenciador.fecha_creacion).toLocaleDateString('es-ES')}</span>
                                ${referenciador.ultimo_registro ? 
                                    '<span>Último acceso: ' + new Date(referenciador.ultimo_registro).toLocaleString('es-ES') + '</span>' : ''}
                            </div>
                        </div>
                    </div>
                    
                    <div class="user-stats">
                        <div class="stat-item ${filtrosActuales.ordenar_por === 'referidos_desc' ? 'stat-destacado' : ''}">
                            <div class="stat-number">${referenciador.total_referenciados || 0}</div>
                            <div class="stat-desc">Referidos</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">${referenciador.tope || 0}</div>
                            <div class="stat-desc">Tope</div>
                        </div>
                        <div class="stat-item ${filtrosActuales.ordenar_por === 'porcentaje_desc' ? 'stat-destacado' : ''}">
                            <div class="stat-number ${porcentaje >= 100 ? 'text-success' : (porcentaje >= 75 ? 'text-primary' : '')}">
                                ${porcentaje}%
                            </div>
                            <div class="stat-desc">Avance</div>
                        </div>
                    </div>
                </div>
                
                <div class="individual-progress">
                    <div class="progress-label-small">
                        Progreso individual: ${referenciador.total_referenciados || 0} de ${referenciador.tope || 0} referidos
                        <span style="float: right; font-weight: bold; color: ${porcentaje >= 100 ? '#4caf50' : (porcentaje >= 75 ? '#2196f3' : '#666')}">
                            ${porcentaje}%
                        </span>
                    </div>
                    <div class="progress-container-small">
                        <div class="progress-bar-small ${progressClass}" 
                             style="width: ${porcentaje}%">
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
            `;
            
            card.html(html);
        });
    }
    
    // Mostrar paginación
    if (data.paginacion.totalPaginas > 1) {
        mostrarPaginacionReferenciadores(data.paginacion);
    } else {
        $('#paginacionContainer').hide();
    }
    
    // Mostrar contenedor
    container.show();
    
    // Animación de barras
    setTimeout(function() {
        $('.progress-bar-small').each(function() {
            var width = $(this).css('width');
            $(this).css('width', '0');
            $(this).animate({ width: width }, 1000);
        });
    }, 300);
}

// 5. NUEVA FUNCIÓN: Buscar líderes por referenciador
function buscarLideresPorReferenciador(id_referenciador) {
    vistaActual = 'lideres';
    referenciadorSeleccionado = id_referenciador;
    
    // Mostrar loading
    $('#loadingIndicator').show();
    $('#referenciadoresList').hide();
    $('#paginacionContainer').hide();
    
    // Obtener nombre del referenciador seleccionado
    var referenciadorNombre = $('#id_referenciador option:selected').text();
    
    // Actualizar filtros activos
    $('#activeFiltersContainer').show();
    $('#filtroIndicator').show();
    $('#activeFiltersList').html(`
        <span class="filter-badge">
            Referenciador: ${referenciadorNombre}
            <span class="close" onclick="limpiarFiltroReferenciador()">&times;</span>
        </span>
    `);
    
    // Actualizar título
    $('.list-title span:first').text('Líderes del Referenciador');
    
    // Construir URL para AJAX
    var url = '../ajax/filtro_referenciador_lideres.php?id_referenciador=' + id_referenciador;
    
    $.ajax({
        url: url,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                mostrarResultadosLideres(response.data);
            } else {
                mostrarError(response.error || 'Error al obtener líderes');
            }
        },
        error: function(xhr, status, error) {
            mostrarError('Error de conexión: ' + error);
        },
        complete: function() {
            $('#loadingIndicator').hide();
        }
    });
}

// 6. NUEVA FUNCIÓN: Mostrar líderes
function mostrarResultadosLideres(data) {
    var container = $('#referenciadoresList');
    container.empty();
    
    // Mostrar estadísticas
    if (data.estadisticas) {
        $('#resultadosInfo').show();
        $('#resultadosText').html(`
            <strong>${data.estadisticas.total_lideres}</strong> líderes | 
            <strong>${data.estadisticas.lideres_activos}</strong> activos | 
            <strong>${data.estadisticas.total_referidos}</strong> referidos totales
        `);
    }
    
    // Mostrar líderes
    if (data.lideres.length === 0) {
        var noResults = $('<div class="no-data">')
            .html('<i class="fas fa-users-slash"></i><p>El referenciador seleccionado no tiene líderes asignados.</p>')
            .appendTo(container);
    } else {
        data.lideres.forEach(function(lider) {
            // Crear tarjeta de líder
            var card = $('<div class="referenciador-card lider-card">')
                .attr('data-id', lider.id_lider)
                .appendTo(container);
            
            // Determinar estado
            var estado = lider.estado ? true : false;
            var estadoText = estado ? 
                '<span class="badge bg-success">Activo</span>' : 
                '<span class="badge bg-secondary">Inactivo</span>';
            
            // Determinar color según cantidad de referidos
            var referidos = lider.cantidad_referidos || 0;
            var referidosClass = '';
            if (referidos >= 50) referidosClass = 'text-success fw-bold';
            else if (referidos >= 20) referidosClass = 'text-primary';
            else if (referidos >= 10) referidosClass = 'text-warning';
            
            // Porcentaje de eficiencia (basado en referidos)
            var eficiencia = Math.min(referidos, 100);
            
            // Construir HTML de la tarjeta
            var html = `
                <div class="referenciador-header">
                    <div class="user-info-section">
                        <div class="user-avatar">
                            <div style="width: 100%; height: 100%; background: #e0f7fa; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
                                <i class="fas fa-user-friends" style="color: #00796b; font-size: 1.5rem;"></i>
                            </div>
                        </div>
                        <div class="user-details">
                            <div class="user-name">
                                ${lider.nombres} ${lider.apellidos}
                                ${estadoText}
                            </div>
                            <div class="user-info-text">
                                <span>Cédula: ${lider.cc || 'N/A'}</span>
                                <span>Teléfono: ${lider.telefono || 'N/A'}</span>
                                <span>Correo: ${lider.correo || 'N/A'}</span>
                                <span>Fecha registro: ${new Date(lider.fecha_creacion).toLocaleDateString('es-ES')}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="user-stats">
                        <div class="stat-item stat-destacado">
                            <div class="stat-number ${referidosClass}">
                                ${referidos}
                            </div>
                            <div class="stat-desc">Referidos</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">
                                <i class="fas ${estado ? 'fa-check-circle text-success' : 'fa-times-circle text-secondary'}"></i>
                            </div>
                            <div class="stat-desc">Estado</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">
                                ${eficiencia}%
                            </div>
                            <div class="stat-desc">Eficiencia</div>
                        </div>
                    </div>
                </div>
                
                <div class="individual-progress">
                    <div class="progress-label-small">
                        Referidos conseguidos: <strong>${referidos}</strong>
                        <span style="float: right; font-weight: bold; color: #00796b;">
                            <i class="fas fa-user-tie"></i> Líder
                        </span>
                    </div>
                    <div class="progress-container-small">
                        <div class="progress-bar-small progress-bueno" 
                             style="width: ${eficiencia}%">
                        </div>
                    </div>
                    <div class="progress-numbers">
                        <span>0</span>
                        <span>25</span>
                        <span>50</span>
                        <span>75</span>
                        <span>100+</span>
                    </div>
                </div>
            `;
            
            card.html(html);
        });
    }
    
    // Mostrar paginación si es necesario
    if (data.paginacion.totalPaginas > 1) {
        mostrarPaginacionLideres(data.paginacion, data.estadisticas.referenciador_id);
    } else {
        $('#paginacionContainer').hide();
    }
    
    // Mostrar contenedor
    container.show();
}

// 7. Función para mostrar paginación de referenciadores
function mostrarPaginacionReferenciadores(paginacion) {
    var container = $('#paginacionContainer');
    container.empty().show();
    
    var paginacionHTML = `
        <nav aria-label="Paginación de referenciadores">
            <ul class="pagination justify-content-center">
    `;
    
    // Botón anterior
    paginacionHTML += `
        <li class="page-item ${paginacion.paginaActual === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="cambiarPaginaReferenciadores(${paginacion.paginaActual - 1})" aria-label="Anterior">
                <span aria-hidden="true">&laquo;</span>
            </a>
        </li>
    `;
    
    // Páginas
    for (var i = 1; i <= paginacion.totalPaginas; i++) {
        if (i === 1 || i === paginacion.totalPaginas || 
            (i >= paginacion.paginaActual - 2 && i <= paginacion.paginaActual + 2)) {
            
            paginacionHTML += `
                <li class="page-item ${i === paginacion.paginaActual ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="cambiarPaginaReferenciadores(${i})">${i}</a>
                </li>
            `;
        } else if (i === paginacion.paginaActual - 3 || i === paginacion.paginaActual + 3) {
            paginacionHTML += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    // Botón siguiente
    paginacionHTML += `
        <li class="page-item ${paginacion.paginaActual === paginacion.totalPaginas ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="cambiarPaginaReferenciadores(${paginacion.paginaActual + 1})" aria-label="Siguiente">
                <span aria-hidden="true">&raquo;</span>
            </a>
        </li>
    `;
    
    paginacionHTML += `
            </ul>
        </nav>
        <div class="text-center text-muted mt-2">
            Mostrando ${paginacion.mostrandoDesde}-${paginacion.mostrandoHasta} de ${paginacion.totalResultados} resultados
        </div>
    `;
    
    container.html(paginacionHTML);
}

// 8. NUEVA FUNCIÓN: Paginación para líderes
function mostrarPaginacionLideres(paginacion, id_referenciador) {
    var container = $('#paginacionContainer');
    container.empty().show();
    
    var paginacionHTML = `
        <nav aria-label="Paginación de líderes">
            <ul class="pagination justify-content-center">
    `;
    
    // Botón anterior
    paginacionHTML += `
        <li class="page-item ${paginacion.paginaActual === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="cambiarPaginaLideres(${id_referenciador}, ${paginacion.paginaActual - 1})" aria-label="Anterior">
                <span aria-hidden="true">&laquo;</span>
            </a>
        </li>
    `;
    
    // Páginas
    for (var i = 1; i <= paginacion.totalPaginas; i++) {
        if (i === 1 || i === paginacion.totalPaginas || 
            (i >= paginacion.paginaActual - 2 && i <= paginacion.paginaActual + 2)) {
            
            paginacionHTML += `
                <li class="page-item ${i === paginacion.paginaActual ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="cambiarPaginaLideres(${id_referenciador}, ${i})">${i}</a>
                </li>
            `;
        } else if (i === paginacion.paginaActual - 3 || i === paginacion.paginaActual + 3) {
            paginacionHTML += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    // Botón siguiente
    paginacionHTML += `
        <li class="page-item ${paginacion.paginaActual === paginacion.totalPaginas ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="cambiarPaginaLideres(${id_referenciador}, ${paginacion.paginaActual + 1})" aria-label="Siguiente">
                <span aria-hidden="true">&raquo;</span>
            </a>
        </li>
    `;
    
    paginacionHTML += `
            </ul>
        </nav>
        <div class="text-center text-muted mt-2">
            Mostrando ${paginacion.mostrandoDesde}-${paginacion.mostrandoHasta} de ${paginacion.totalResultados} líderes
        </div>
    `;
    
    container.html(paginacionHTML);
}

// 9. Función para cambiar página de referenciadores
function cambiarPaginaReferenciadores(pagina) {
    filtrosActuales.page = pagina;
    
    // Actualizar URL de AJAX con nueva página
    var url = '../ajax/filtros_referenciadores.php?' + $.param(filtrosActuales);
    
    $('#loadingIndicator').show();
    
    $.ajax({
        url: url,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                mostrarResultadosReferenciadores(response.data);
                // Scroll suave hacia la lista
                $('html, body').animate({
                    scrollTop: $('#referenciadoresContainer').offset().top - 20
                }, 500);
            }
        },
        complete: function() {
            $('#loadingIndicator').hide();
        }
    });
}

// 10. NUEVA FUNCIÓN: Cambiar página de líderes
function cambiarPaginaLideres(id_referenciador, pagina) {
    $('#loadingIndicator').show();
    
    $.ajax({
        url: '../ajax/filtro_referenciador_lideres.php?id_referenciador=' + id_referenciador + '&page=' + pagina,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                mostrarResultadosLideres(response.data);
                // Scroll suave hacia la lista
                $('html, body').animate({
                    scrollTop: $('#referenciadoresContainer').offset().top - 20
                }, 500);
            }
        },
        complete: function() {
            $('#loadingIndicator').hide();
        }
    });
}

// 11. Función para limpiar filtro de referenciador específico
function limpiarFiltroReferenciador() {
    $('#id_referenciador').val('');
    referenciadorSeleccionado = null;
    buscarReferenciadores();
}

// 12. Función para limpiar todos los filtros
function limpiarFiltros() {
    // Limpiar todos los campos
    $('.filtro-input').val('');
    $('.filtro-select').val('');
    $('#id_referenciador').val('');
    $('#ordenar_por').val('fecha_creacion');
    
    // Resetear estado
    vistaActual = 'referenciadores';
    referenciadorSeleccionado = null;
    
    // Restaurar título original
    $('.list-title span:first').text('Progreso Individual por Referenciador');
    
    // Ocultar contenedores
    $('#activeFiltersContainer').hide();
    $('#resultadosInfo').hide();
    $('#filtroIndicator').hide();
    
    // Resetear resultados a estado inicial
    $('#loadingIndicator').show();
    $('#referenciadoresList').hide();
    $('#paginacionContainer').hide();
    
    // Recargar la página para mostrar datos iniciales
    setTimeout(function() {
        location.reload();
    }, 300);
}

// 13. Función para mostrar error
function mostrarError(mensaje) {
    $('#referenciadoresList').html(`
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i> ${mensaje}
        </div>
    `).show();
}

// ====================================================================
// EVENT LISTENERS
// ====================================================================

$(document).ready(function() {
    // Buscar al hacer clic en botón
    $('#btnBuscarAjax').click(buscarReferenciadores);
    
    // Limpiar filtros
    $('#btnLimpiarAjax').click(limpiarFiltros);
    
    // Búsqueda en tiempo real en campo de nombre
    $('#nombre').on('keyup', function() {
        clearTimeout(timerBusqueda);
        timerBusqueda = setTimeout(function() {
            if ($('#nombre').val().trim().length >= 3 || $('#nombre').val().trim().length === 0) {
                buscarReferenciadores();
            }
        }, 500);
    });
    
    // Cambios en selects (excepto ordenar que no dispara búsqueda automática)
    $('#id_zona, #id_sector, #id_puesto, #porcentaje_minimo, #fecha_acceso').change(function() {
        buscarReferenciadores();
    });
    
    // Ordenar por - búsqueda al cambiar
    $('#ordenar_por').change(function() {
        buscarReferenciadores();
    });
    
    // Cambios en el combo box de referenciador
    $('#id_referenciador').change(function() {
        var id_referenciador = $(this).val() || 0;
        
        if (id_referenciador > 0) {
            buscarReferenciadores(); // Esta función ahora detecta si hay referenciador seleccionado
        } else {
            // Si se selecciona "Todos los referenciadores", mostrar todos los referenciadores
            referenciadorSeleccionado = null;
            buscarReferenciadores();
        }
    });
    
    // Eliminar filtro individual al hacer clic en X
    $(document).on('click', '.filter-badge .close', function() {
        var filtroKey = $(this).data('filtro');
        
        if (filtroKey === 'id_referenciador') {
            limpiarFiltroReferenciador();
            return;
        }
        
        // Limpiar ese filtro específico
        $('#' + filtroKey).val('');
        if (filtroKey === 'ordenar_por') {
            $('#' + filtroKey).val('fecha_creacion');
        }
        
        // Volver a buscar
        buscarReferenciadores();
    });
    
    // Efecto hover en tarjetas
    $(document).on('mouseenter', '.referenciador-card', function() {
        $(this).css('transform', 'translateY(-5px)');
        $(this).css('box-shadow', '0 5px 15px rgba(0,0,0,0.1)');
    }).on('mouseleave', '.referenciador-card', function() {
        $(this).css('transform', 'translateY(0)');
        $(this).css('box-shadow', '0 2px 5px rgba(0,0,0,0.05)');
    });
});
    </script>
    <script src="../js/modal-sistema.js"></script>
    <script src="../js/contador.js"></script>
</body>
</html>