<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../models/ReferenciadoModel.php';
require_once __DIR__ . '/../../models/ZonaModel.php';
require_once __DIR__ . '/../../models/SistemaModel.php';
require_once __DIR__ . '/../../models/LlamadaModel.php';

// Verificar si el usuario está logueado y es SuperAdmin
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    header('Location: ../index.php');
    exit();
}

$pdo = Database::getConnection();
$usuarioModel = new UsuarioModel($pdo);
$referenciadoModel = new ReferenciadoModel($pdo);
$zonaModel = new ZonaModel($pdo);
$sistemaModel = new SistemaModel($pdo);
$llamadaModel = new LlamadaModel($pdo);

// Obtener datos del usuario logueado
$usuario_logueado = $usuarioModel->getUsuarioById($_SESSION['id_usuario']);

// Obtener fecha por defecto (hoy)
$fecha_seleccionada = $_GET['fecha'] ?? date('Y-m-d');

// Obtener todas las zonas para filtros
$zonas = $zonaModel->getAll();

// Obtener tipos de resultado de llamada
$tiposResultado = $llamadaModel->getTiposResultado();

// Info del sistema
$licenciaInfo = $sistemaModel->getInfoCompletaLicencia();
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
    <title>Reporte de Tracking de Llamadas - Super Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../styles/superadmin_reporte_votos.css">
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="header-container">
            <div class="header-top">
                <div class="header-title">
                    <h1><i class="fas fa-phone-alt"></i> Reporte de Tracking de Llamadas</h1>
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

    <!-- Breadcrumb -->
    <div class="breadcrumb-nav">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../superadmin_dashboard.php"><i class="fas fa-home"></i> Panel Super Admin</a></li>
                <li class="breadcrumb-item"><a href="superadmin_analisis_ia.php"><i class="fas fa-chart-bar"></i> Rendimiento</a></li>
                <li class="breadcrumb-item active"><i class="fas fa-phone-chart-line"></i> Reporte Tracking</li>
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

    <!-- Dashboard Principal -->
    <div class="main-container">
        
        <!-- Filtros Superiores -->
        <div class="filtros-superiores">
            <div class="filtro-group">
                <label for="selectFecha"><i class="fas fa-calendar-alt"></i> Fecha:</label>
                <input type="date" id="selectFecha" value="<?php echo $fecha_seleccionada; ?>">
            </div>
            
            <div class="filtro-group">
                <label for="selectTipoResultado"><i class="fas fa-filter"></i> Resultado:</label>
                <select id="selectTipoResultado">
                    <option value="todos">Todos los resultados</option>
                    <?php foreach ($tiposResultado as $resultado): ?>
                        <option value="<?php echo $resultado['id_resultado']; ?>">
                            <?php echo htmlspecialchars($resultado['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filtro-group">
                <label for="selectRating"><i class="fas fa-star"></i> Rating:</label>
                <select id="selectRating">
                    <option value="todos">Todos los ratings</option>
                    <option value="5">★★★★★ (5)</option>
                    <option value="4">★★★★☆ (4)</option>
                    <option value="3">★★★☆☆ (3)</option>
                    <option value="2">★★☆☆☆ (2)</option>
                    <option value="1">★☆☆☆☆ (1)</option>
                    <option value="0">Sin rating</option>
                </select>
            </div>
            
            <div class="filtro-group">
                <label for="selectRangoFechas"><i class="fas fa-calendar-week"></i> Rango:</label>
                <select id="selectRangoFechas">
                    <option value="hoy">Hoy</option>
                    <option value="ayer">Ayer</option>
                    <option value="semana">Esta semana</option>
                    <option value="mes">Este mes</option>
                    <option value="personalizado">Personalizado</option>
                </select>
            </div>
            
            <div class="filtro-group" id="fechaDesdeGroup" style="display: none;">
                <label for="fechaDesde"><i class="fas fa-calendar-start"></i> Desde:</label>
                <input type="date" id="fechaDesde">
            </div>
            
            <div class="filtro-group" id="fechaHastaGroup" style="display: none;">
                <label for="fechaHasta"><i class="fas fa-calendar-end"></i> Hasta:</label>
                <input type="date" id="fechaHasta" value="<?php echo date('Y-m-d'); ?>">
            </div>
            
            <button id="btnAplicarFiltros" class="btn-filtro-aplicar">
                <i class="fas fa-check"></i> Aplicar Filtros
            </button>
            
            <button id="btnExportar" class="btn-exportar">
                <i class="fas fa-download"></i> Exportar
            </button>
        </div>
        
        <!-- Tabs Principales -->
        <div class="tabs-container">
            <div class="tabs-header d-flex justify-content-center">
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <button class="tab-btn active" data-tab="resumen">
                        <i class="fas fa-chart-pie"></i> Resumen
                    </button>
                    <button class="tab-btn" data-tab="calidad">
                        <i class="fas fa-star"></i> Calidad
                    </button>
                    <button class="tab-btn" data-tab="llamadores">
                        <i class="fas fa-user-tie"></i> Llamadores
                    </button>
                    <button class="tab-btn" data-tab="tendencias">
                        <i class="fas fa-chart-line"></i> Tendencias
                    </button>
                    <button class="tab-btn" data-tab="detalle">
                        <i class="fas fa-table"></i> Detalle
                    </button>
                </div>
            </div>
            
            <div class="tabs-content">
                
                <!-- TAB 1: RESUMEN -->
                <div class="tab-pane active" id="tab-resumen">
                    <!-- KPIs Principales -->
                    <div class="kpis-container">
                        <div class="kpi-card kpi-primary">
                            <div class="kpi-icon">
                                <i class="fas fa-phone-volume"></i>
                            </div>
                            <div class="kpi-content">
                                <div class="kpi-title">Total Llamadas</div>
                                <div class="kpi-value" id="kpiTotalLlamadas">0</div>
                                <div class="kpi-change" id="kpiCambioLlamadas">Cargando...</div>
                            </div>
                        </div>
                        
                        <div class="kpi-card kpi-success">
                            <div class="kpi-icon">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div class="kpi-content">
                                <div class="kpi-title">Contactos Efectivos</div>
                                <div class="kpi-value" id="kpiContactosEfectivos">0</div>
                                <div class="kpi-subtitle" id="kpiPorcentajeContactos">0%</div>
                            </div>
                        </div>
                        
                        <div class="kpi-card kpi-warning">
                            <div class="kpi-icon">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="kpi-content">
                                <div class="kpi-title">Calidad Promedio</div>
                                <div class="kpi-value" id="kpiCalidadPromedio">0.0</div>
                                <div class="kpi-subtitle">
                                    <span class="rating-stars" id="kpiStars"></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="kpi-card kpi-info">
                            <div class="kpi-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="kpi-content">
                                <div class="kpi-title">Llamadores Activos</div>
                                <div class="kpi-value" id="kpiLlamadoresActivos">0</div>
                                <div class="kpi-subtitle">Registraron hoy</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Gráficas del Resumen -->
                    <div class="row mt-4">
                        <div class="col-lg-8">
                            <div class="grafica-principal-container">
                                <div class="grafica-header">
                                    <h4><i class="fas fa-chart-bar"></i> Llamadas por Hora</h4>
                                    <div class="grafica-actions">
                                        <button class="btn-grafica-action active" data-chart-type="bar">
                                            <i class="fas fa-chart-bar"></i> Barras
                                        </button>
                                        <button class="btn-grafica-action" data-chart-type="line">
                                            <i class="fas fa-chart-line"></i> Líneas
                                        </button>
                                    </div>
                                </div>
                                <div class="grafica-body">
                                    <canvas id="graficaPorHora"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <div class="grafica-principal-container">
                                <div class="grafica-header">
                                    <h4><i class="fas fa-chart-pie"></i> Distribución por Resultado</h4>
                                </div>
                                <div class="grafica-body">
                                    <canvas id="graficaDistribucionResultado"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Estadísticas Adicionales -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-tachometer-alt"></i> Eficiencia del Tracking</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="text-center mb-3">
                                                <div class="h4 mb-1" id="eficienciaGeneral">0%</div>
                                                <small class="text-muted">Eficiencia General</small>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-center mb-3">
                                                <div class="h4 mb-1" id="tiempoPromedio">0:00</div>
                                                <small class="text-muted">Hora Pico de Llamadas</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="progress progress-quality">
                                        <div id="barraEficiencia" class="progress-bar quality-excellent" role="progressbar" style="width: 0%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-chart-line"></i> Distribución de Ratings</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-2">
                                            <div class="h5 mb-1" id="rating5">0</div>
                                            <small class="text-muted">★★★★★</small>
                                        </div>
                                        <div class="col-2">
                                            <div class="h5 mb-1" id="rating4">0</div>
                                            <small class="text-muted">★★★★☆</small>
                                        </div>
                                        <div class="col-2">
                                            <div class="h5 mb-1" id="rating3">0</div>
                                            <small class="text-muted">★★★☆☆</small>
                                        </div>
                                        <div class="col-2">
                                            <div class="h5 mb-1" id="rating2">0</div>
                                            <small class="text-muted">★★☆☆☆</small>
                                        </div>
                                        <div class="col-2">
                                            <div class="h5 mb-1" id="rating1">0</div>
                                            <small class="text-muted">★☆☆☆☆</small>
                                        </div>
                                        <div class="col-2">
                                            <div class="h5 mb-1" id="sinRating">0</div>
                                            <small class="text-muted">Sin rating</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- TAB 2: CALIDAD -->
                <div class="tab-pane" id="tab-calidad">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-star-half-alt"></i> Análisis de Calidad por Rating</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="graficaCalidad"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-medal"></i> Calidad por Resultado</h5>
                                </div>
                                <div class="card-body">
                                    <div id="calidadPorResultado">
                                        <!-- Se llenará con JavaScript -->
                                        <div class="text-center py-4">
                                            <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                                            <p class="mt-2">Cargando datos...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Rating Promedio por Hora</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="graficaRatingPorHora"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-comment-dots"></i> Observaciones</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6 text-center">
                                            <div class="h2 mb-1" id="porcentajeObservaciones">0%</div>
                                            <small class="text-muted">Con observaciones</small>
                                        </div>
                                        <div class="col-6 text-center">
                                            <div class="h2 mb-1" id="promedioLongitudObs">0</div>
                                            <small class="text-muted">Caracteres promedio</small>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <h6>Observaciones más recientes:</h6>
                                        <div id="ultimasObservaciones" class="small text-muted">
                                            <i class="fas fa-spinner fa-spin"></i> Cargando...
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- TAB 3: LLAMADORES -->
                <div class="tab-pane" id="tab-llamadores">
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-trophy"></i> Top 10 Llamadores</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="tablaTopLlamadores">
                                            <thead>
                                                <tr>
                                                    <th>Posición</th>
                                                    <th>Llamador</th>
                                                    <th>Total Llamadas</th>
                                                    <th>Rating Promedio</th>
                                                    <th>Contactos Efectivos</th>
                                                    <th>Eficiencia</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td colspan="6" class="text-center py-4">
                                                        <i class="fas fa-spinner fa-spin"></i> Cargando datos...
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Distribución por Llamador</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="graficaLlamadores"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-bullseye"></i> Eficiencia por Llamador</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="graficaEficienciaLlamadores"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- TAB 4: TENDENCIAS -->
                <div class="tab-pane" id="tab-tendencias">
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-chart-line"></i> Tendencias de Llamadas</h5>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary active" data-periodo="7">7 días</button>
                                        <button class="btn btn-sm btn-outline-primary" data-periodo="30">30 días</button>
                                        <button class="btn btn-sm btn-outline-primary" data-periodo="90">90 días</button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <canvas id="graficaTendencias"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Comparativa Semanal</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="graficaComparativaSemanal"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-arrow-trend-up"></i> Proyección</h5>
                                </div>
                                <div class="card-body">
                                    <div id="proyeccionContainer">
                                        <div class="text-center py-4">
                                            <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                                            <p class="mt-2">Cargando proyección...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- TAB 5: DETALLE -->
                <div class="tab-pane" id="tab-detalle">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-table"></i> Detalle de Llamadas</h5>
                            <div>
                                <span class="badge bg-secondary" id="totalRegistros">0 registros</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="tablaDetalleLlamadas">
                                    <thead>
                                        <tr>
                                            <th>Fecha/Hora</th>
                                            <th>Llamador</th>
                                            <th>Referenciado</th>
                                            <th>Teléfono</th>
                                            <th>Resultado</th>
                                            <th>Rating</th>
                                            <th>Observaciones</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="8" class="text-center py-4">
                                                <i class="fas fa-spinner fa-spin"></i> Cargando datos...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <nav aria-label="Paginación">
                                <ul class="pagination justify-content-center" id="paginacionDetalle">
                                    <!-- La paginación se generará con JavaScript -->
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
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

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Variables globales
        let chartPorHora = null;
        let chartDistribucionResultado = null;
        let chartCalidad = null;
        let chartRatingPorHora = null;
        let chartLlamadores = null;
        let chartEficienciaLlamadores = null;
        let chartTendencias = null;
        let chartComparativaSemanal = null;
        
        // Inicializar al cargar la página
        $(document).ready(function() {
            // Inicializar sistema de tabs
            inicializarTabs();
            
            // Configurar filtro de rango de fechas
            configurarFiltroRangoFechas();
            
            // Cargar datos iniciales
            cargarDatosIniciales();
            
            // Configurar eventos
            configurarEventos();
            
            // Inicializar contador
            iniciarContadorCompacto();
        });
        
        // Función para inicializar sistema de tabs
        function inicializarTabs() {
            // Cambiar entre tabs
            $('.tab-btn').click(function() {
                const tabId = $(this).data('tab');
                
                // Remover active de todos los tabs
                $('.tab-btn').removeClass('active');
                $('.tab-pane').removeClass('active');
                
                // Agregar active al tab seleccionado
                $(this).addClass('active');
                $('#tab-' + tabId).addClass('active');
                
                // Cargar datos específicos del tab si es necesario
                switch(tabId) {
                    case 'calidad':
                        cargarDatosCalidad();
                        break;
                    case 'llamadores':
                        cargarDatosLlamadores();
                        break;
                    case 'tendencias':
                        cargarDatosTendencias();
                        break;
                    case 'detalle':
                        cargarTablaDetalle();
                        break;
                }
            });
        }
        
        // Función para configurar filtro de rango de fechas
        function configurarFiltroRangoFechas() {
            $('#selectRangoFechas').change(function() {
                if ($(this).val() === 'personalizado') {
                    $('#fechaDesdeGroup').show();
                    $('#fechaHastaGroup').show();
                } else {
                    $('#fechaDesdeGroup').hide();
                    $('#fechaHastaGroup').hide();
                }
            });
        }
        
        // Función para cargar datos iniciales
        function cargarDatosIniciales() {
            const filtros = obtenerFiltros();
            
            // Mostrar loading
            mostrarLoadingKPIs();
            
            // Cargar datos del resumen
            $.ajax({
                url: '../ajax/obtener_datos_tracking_resumen.php',
                type: 'POST',
                data: filtros,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        actualizarKPIs(response.data);
                        crearGraficaPorHora(response.data.grafica_horas);
                        crearGraficaDistribucionResultado(response.data.distribucion_resultado);
                        actualizarEstadisticasAdicionales(response.data);
                    } else {
                        mostrarError('Error al cargar datos: ' + (response.error || 'Error desconocido'));
                    }
                },
                error: function() {
                    mostrarError('Error de conexión con el servidor');
                }
            });
        }
        
        // Función para obtener filtros activos
        function obtenerFiltros() {
            const filtros = {
                fecha: $('#selectFecha').val(),
                tipo_resultado: $('#selectTipoResultado').val(),
                rating: $('#selectRating').val(),
                rango: $('#selectRangoFechas').val()
            };
            
            if (filtros.rango === 'personalizado') {
                filtros.fecha_desde = $('#fechaDesde').val();
                filtros.fecha_hasta = $('#fechaHasta').val();
            }
            
            return filtros;
        }
        
        // Función para mostrar loading en KPIs
        function mostrarLoadingKPIs() {
            $('#kpiTotalLlamadas').html('<i class="fas fa-spinner fa-spin"></i>');
            $('#kpiContactosEfectivos').html('<i class="fas fa-spinner fa-spin"></i>');
            $('#kpiCalidadPromedio').html('<i class="fas fa-spinner fa-spin"></i>');
            $('#kpiLlamadoresActivos').html('<i class="fas fa-spinner fa-spin"></i>');
        }
        
        // Función para actualizar KPIs
        function actualizarKPIs(data) {
            // Total llamadas
            $('#kpiTotalLlamadas').text(data.total_llamadas || 0);
            
            // Contactos efectivos
            const contactosEfectivos = data.contactos_efectivos || 0;
            const porcentajeContactos = data.porcentaje_contactos || 0;
            $('#kpiContactosEfectivos').text(contactosEfectivos);
            $('#kpiPorcentajeContactos').text(porcentajeContactos + '%');
            
            // Calidad promedio
            const calidadPromedio = data.rating_promedio || 0;
            $('#kpiCalidadPromedio').text(calidadPromedio.toFixed(1));
            
            // Estrellas
            let starsHtml = '';
            const estrellasLlenas = Math.floor(calidadPromedio);
            const mediaEstrella = calidadPromedio - estrellasLlenas >= 0.5;
            
            for (let i = 1; i <= 5; i++) {
                if (i <= estrellasLlenas) {
                    starsHtml += '<i class="fas fa-star"></i>';
                } else if (i === estrellasLlenas + 1 && mediaEstrella) {
                    starsHtml += '<i class="fas fa-star-half-alt"></i>';
                } else {
                    starsHtml += '<i class="far fa-star"></i>';
                }
            }
            $('#kpiStars').html(starsHtml);
            
            // Llamadores activos
            $('#kpiLlamadoresActivos').text(data.llamadores_activos || 0);
            
            // Cambio vs ayer
            const cambio = data.cambio_vs_ayer || 0;
            const porcentajeCambio = data.porcentaje_cambio || 0;
            
            $('#kpiCambioLlamadas').html(cambio >= 0 ? 
                `<span class="positive">+${cambio} (${porcentajeCambio}%) vs ayer</span>` :
                `<span class="negative">${cambio} (${porcentajeCambio}%) vs ayer</span>`);
        }
        
        // Función para crear gráfica por hora
        function crearGraficaPorHora(datos) {
    const ctx = document.getElementById('graficaPorHora').getContext('2d');
    const isDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    if (chartPorHora) {
        chartPorHora.destroy();
    }
    
    const horas = datos.map(item => item.hora + ':00');
    const cantidades = datos.map(item => item.cantidad);
    
    chartPorHora = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: horas,
            datasets: [{
                label: 'Llamadas',
                data: cantidades,
                backgroundColor: 'rgba(52, 152, 219, 0.7)',
                borderColor: 'rgba(52, 152, 219, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false,
                    labels: {
                        color: isDarkMode ? '#ffffff' : '#333'
                    }
                },
                tooltip: {
                    backgroundColor: isDarkMode ? '#3d3d3d' : 'rgba(0,0,0,0.8)',
                    titleColor: isDarkMode ? '#ffffff' : '#fff',
                    bodyColor: isDarkMode ? '#ffffff' : '#fff',
                    borderColor: isDarkMode ? '#4d4d4d' : 'transparent',
                    borderWidth: isDarkMode ? 1 : 0
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Cantidad de Llamadas',
                        color: isDarkMode ? '#ffffff' : '#333'
                    },
                    ticks: {
                        color: isDarkMode ? '#ffffff' : '#333'
                    },
                    grid: {
                        color: isDarkMode ? '#4d4d4d' : '#e0e0e0'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Hora del Día',
                        color: isDarkMode ? '#ffffff' : '#333'
                    },
                    ticks: {
                        color: isDarkMode ? '#ffffff' : '#333'
                    },
                    grid: {
                        color: isDarkMode ? '#4d4d4d' : '#e0e0e0'
                    }
                }
            }
        }
    });
}
        function actualizarLogoSegunTema() {
            const logo = document.getElementById('footer-logo');
            if (!logo) return;
            
            const isDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            
            if (isDarkMode) {
                logo.src = logo.getAttribute('data-img-oscuro');
            } else {
                logo.src = logo.getAttribute('data-img-claro');
            }
        }
                document.addEventListener('DOMContentLoaded', function() {
            actualizarLogoSegunTema();
        });

        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
            actualizarLogoSegunTema();
        });
        // Función para crear gráfica de distribución por resultado
        function crearGraficaDistribucionResultado(datos) {
    const ctx = document.getElementById('graficaDistribucionResultado').getContext('2d');
    const isDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    if (chartDistribucionResultado) {
        chartDistribucionResultado.destroy();
    }
    
    const labels = datos.map(item => item.resultado);
    const valores = datos.map(item => item.cantidad);
    const colores = datos.map(item => obtenerColorResultado(item.id_resultado));
    
    chartDistribucionResultado = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: valores,
                backgroundColor: colores,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        color: isDarkMode ? '#ffffff' : '#333'
                    }
                },
                tooltip: {
                    backgroundColor: isDarkMode ? '#3d3d3d' : 'rgba(0,0,0,0.8)',
                    titleColor: isDarkMode ? '#ffffff' : '#fff',
                    bodyColor: isDarkMode ? '#ffffff' : '#fff',
                    borderColor: isDarkMode ? '#4d4d4d' : 'transparent',
                    borderWidth: isDarkMode ? 1 : 0
                }
            }
        }
    });
}
        
        // Función para obtener color según tipo de resultado
        function obtenerColorResultado(idResultado) {
            const colores = {
                1: 'rgba(40, 167, 69, 0.7)',   // Contactado - verde
                2: 'rgba(255, 193, 7, 0.7)',   // No contesta - amarillo
                3: 'rgba(108, 117, 125, 0.7)', // Número equivocado - gris
                4: 'rgba(108, 117, 125, 0.7)', // Teléfono apagado - gris
                5: 'rgba(255, 193, 7, 0.7)',   // Ocupado - amarillo
                6: 'rgba(23, 162, 184, 0.7)',  // Dejó mensaje - azul
                7: 'rgba(220, 53, 69, 0.7)'    // Rechazó llamada - rojo
            };
            
            return colores[idResultado] || 'rgba(108, 117, 125, 0.7)';
        }
        
        // Función para actualizar estadísticas adicionales
        function actualizarEstadisticasAdicionales(data) {
            // Eficiencia
            const eficiencia = data.eficiencia_general || 0;
            $('#eficienciaGeneral').text(eficiencia + '%');
            
            // Configurar barra de eficiencia
            const barra = $('#barraEficiencia');
            barra.css('width', eficiencia + '%');
            
            if (eficiencia >= 80) {
                barra.removeClass().addClass('progress-bar quality-excellent');
            } else if (eficiencia >= 60) {
                barra.removeClass().addClass('progress-bar quality-good');
            } else if (eficiencia >= 40) {
                barra.removeClass().addClass('progress-bar quality-average');
            } else {
                barra.removeClass().addClass('progress-bar quality-poor');
            }
            
            // Hora pico
            if (data.hora_pico) {
                $('#tiempoPromedio').text(data.hora_pico.hora + ':00');
            }
            
            // Distribución de ratings
            const distribucionRating = data.distribucion_rating || {};
            $('#rating5').text(distribucionRating['5'] || 0);
            $('#rating4').text(distribucionRating['4'] || 0);
            $('#rating3').text(distribucionRating['3'] || 0);
            $('#rating2').text(distribucionRating['2'] || 0);
            $('#rating1').text(distribucionRating['1'] || 0);
            $('#sinRating').text(distribucionRating['0'] || 0);
        }
        
        // Función para cargar datos de calidad
        function cargarDatosCalidad() {
            const filtros = obtenerFiltros();
            
            $.ajax({
                url: '../ajax/obtener_datos_tracking_calidad.php',
                type: 'POST',
                data: filtros,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        crearGraficaCalidad(response.data);
                        actualizarCalidadPorResultado(response.data.calidad_por_resultado);
                        crearGraficaRatingPorHora(response.data.rating_por_hora);
                        actualizarEstadisticasObservaciones(response.data);
                    }
                }
            });
        }
        
        // Función para crear gráfica de calidad
function crearGraficaCalidad(data) {
    const ctx = document.getElementById('graficaCalidad').getContext('2d');
    const isDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    if (window.chartCalidad) {
        window.chartCalidad.destroy();
    }
    
    const distribucion = data.distribucion_rating || {};
    const labels = ['★☆☆☆☆ (1)', '★★☆☆☆ (2)', '★★★☆☆ (3)', '★★★★☆ (4)', '★★★★★ (5)'];
    const valores = [
        distribucion['1']?.cantidad || 0,
        distribucion['2']?.cantidad || 0,
        distribucion['3']?.cantidad || 0,
        distribucion['4']?.cantidad || 0,
        distribucion['5']?.cantidad || 0
    ];
    
    window.chartCalidad = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Cantidad de Llamadas',
                data: valores,
                backgroundColor: [
                    'rgba(220, 53, 69, 0.7)',
                    'rgba(253, 126, 20, 0.7)',
                    'rgba(255, 193, 7, 0.7)',
                    'rgba(23, 162, 184, 0.7)',
                    'rgba(40, 167, 69, 0.7)'
                ],
                borderColor: [
                    'rgba(220, 53, 69, 1)',
                    'rgba(253, 126, 20, 1)',
                    'rgba(255, 193, 7, 1)',
                    'rgba(23, 162, 184, 1)',
                    'rgba(40, 167, 69, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: isDarkMode ? '#3d3d3d' : 'rgba(0,0,0,0.8)',
                    titleColor: isDarkMode ? '#ffffff' : '#fff',
                    bodyColor: isDarkMode ? '#ffffff' : '#fff',
                    borderColor: isDarkMode ? '#4d4d4d' : 'transparent',
                    borderWidth: isDarkMode ? 1 : 0,
                    callbacks: {
                        label: function(context) {
                            const valor = context.raw;
                            const total = valores.reduce((a, b) => a + b, 0);
                            const porcentaje = total > 0 ? ((valor / total) * 100).toFixed(1) : 0;
                            return `${valor} llamadas (${porcentaje}%)`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Cantidad de Llamadas',
                        color: isDarkMode ? '#ffffff' : '#333'
                    },
                    ticks: {
                        color: isDarkMode ? '#ffffff' : '#333'
                    },
                    grid: {
                        color: isDarkMode ? '#4d4d4d' : '#e0e0e0'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Rating (Estrellas)',
                        color: isDarkMode ? '#ffffff' : '#333'
                    },
                    ticks: {
                        color: isDarkMode ? '#ffffff' : '#333'
                    },
                    grid: {
                        color: isDarkMode ? '#4d4d4d' : '#e0e0e0'
                    }
                }
            }
        }
    });
}
        
        // Función para actualizar calidad por resultado
        function actualizarCalidadPorResultado(datos) {
            let html = '';
            
            if (datos && datos.length > 0) {
                datos.forEach(item => {
                    const porcentaje = item.porcentaje_exitoso || 0;
                    const color = porcentaje >= 80 ? 'success' : 
                                 porcentaje >= 60 ? 'warning' : 
                                 'danger';
                    
                    html += `
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span>${item.resultado}</span>
                                <span class="badge bg-${color}">${porcentaje}%</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-${color}" 
                                     role="progressbar" 
                                     style="width: ${porcentaje}%">
                                </div>
                            </div>
                            <small class="text-muted">${item.cantidad} llamadas</small>
                        </div>
                    `;
                });
            } else {
                html = '<div class="text-center text-muted">No hay datos disponibles</div>';
            }
            
            $('#calidadPorResultado').html(html);
        }
        
        // Función para crear gráfica de rating por hora
        function crearGraficaRatingPorHora(datos) {
    const ctx = document.getElementById('graficaRatingPorHora').getContext('2d');
    const isDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    if (chartRatingPorHora) {
        chartRatingPorHora.destroy();
    }
    
    const horas = datos.map(item => item.hora + ':00');
    const ratings = datos.map(item => item.rating_promedio);
    
    chartRatingPorHora = new Chart(ctx, {
        type: 'line',
        data: {
            labels: horas,
            datasets: [{
                label: 'Rating Promedio',
                data: ratings,
                borderColor: 'rgba(255, 193, 7, 1)',
                backgroundColor: 'rgba(255, 193, 7, 0.1)',
                borderWidth: 3,
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false,
                    labels: {
                        color: isDarkMode ? '#ffffff' : '#333'
                    }
                },
                tooltip: {
                    backgroundColor: isDarkMode ? '#3d3d3d' : 'rgba(0,0,0,0.8)',
                    titleColor: isDarkMode ? '#ffffff' : '#fff',
                    bodyColor: isDarkMode ? '#ffffff' : '#fff',
                    borderColor: isDarkMode ? '#4d4d4d' : 'transparent',
                    borderWidth: isDarkMode ? 1 : 0
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    min: 0,
                    max: 5,
                    title: {
                        display: true,
                        text: 'Rating Promedio',
                        color: isDarkMode ? '#ffffff' : '#333'
                    },
                    ticks: {
                        color: isDarkMode ? '#ffffff' : '#333'
                    },
                    grid: {
                        color: isDarkMode ? '#4d4d4d' : '#e0e0e0'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Hora del Día',
                        color: isDarkMode ? '#ffffff' : '#333'
                    },
                    ticks: {
                        color: isDarkMode ? '#ffffff' : '#333'
                    },
                    grid: {
                        color: isDarkMode ? '#4d4d4d' : '#e0e0e0'
                    }
                }
            }
        }
    });
}
        
        // Función para actualizar estadísticas de observaciones
        function actualizarEstadisticasObservaciones(data) {
            // Porcentaje con observaciones
            const porcentajeObs = data.porcentaje_con_observaciones || 0;
            $('#porcentajeObservaciones').text(porcentajeObs + '%');
            
            // Longitud promedio
            const longitudPromedio = data.longitud_promedio_observaciones || 0;
            $('#promedioLongitudObs').text(longitudPromedio.toFixed(0));
            
            // Últimas observaciones
            const ultimasObs = data.ultimas_observaciones || [];
            let html = '<div class="list-group">';
            
            if (ultimasObs.length > 0) {
                ultimasObs.forEach(obs => {
                    const fecha = new Date(obs.fecha_llamada).toLocaleString('es-ES', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    
                    html += `
                        <div class="list-group-item list-group-item-action border-0 py-2">
                            <div class="d-flex w-100 justify-content-between">
                                <small class="text-primary">${obs.llamador}</small>
                                <small class="text-muted">${fecha}</small>
                            </div>
                            <p class="mb-1 small">${obs.observaciones.substring(0, 100)}...</p>
                        </div>
                    `;
                });
            } else {
                html += '<div class="text-center text-muted py-2">No hay observaciones recientes</div>';
            }
            
            html += '</div>';
            $('#ultimasObservaciones').html(html);
        }
        
        // Función para cargar datos de llamadores
        function cargarDatosLlamadores() {
            const filtros = obtenerFiltros();
            
            $.ajax({
                url: '../ajax/obtener_datos_tracking_llamadores.php',
                type: 'POST',
                data: filtros,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        actualizarTablaTopLlamadores(response.data.top_llamadores);
                        crearGraficaLlamadores(response.data.distribucion_llamadores);
                        crearGraficaEficienciaLlamadores(response.data.eficiencia_llamadores);
                    }
                }
            });
        }
        
        // Función para actualizar tabla de top llamadores
        function actualizarTablaTopLlamadores(datos) {
    const tbody = $('#tablaTopLlamadores tbody');
    
    console.log("Datos recibidos para tabla:", datos); // DEBUG
    
    // Verificar si hay datos
    if (!datos || !Array.isArray(datos) || datos.length === 0) {
        tbody.html(`
            <tr>
                <td colspan="6" class="text-center py-5">
                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-user-slash me-2"></i>
                        No se encontraron datos de llamadores para los filtros seleccionados.
                    </div>
                </td>
            </tr>
        `);
        return;
    }
    
    // Limpiar tabla
    tbody.empty();
    
    // Agregar cada fila
    datos.forEach((item, index) => {
        console.log("Procesando item:", item); // DEBUG
        
        // Manejar valores nulos o indefinidos SEGURO
        const nombre = item.nombre_completo || 'Usuario desconocido';
        const cedula = item.cedula || 'N/A';
        const totalLlamadas = parseInt(item.total_llamadas) || 0;
        
        // VALIDACIÓN CRÍTICA: Asegurar que rating sea número
        let rating = 0;
        if (item.rating_promedio !== null && item.rating_promedio !== undefined) {
            rating = parseFloat(item.rating_promedio);
            if (isNaN(rating)) rating = 0;
        }
        
        const contactosEfectivos = parseInt(item.contactos_efectivos) || 0;
        
        // VALIDACIÓN CRÍTICA: Asegurar que eficiencia sea número
        let eficiencia = 0;
        if (item.eficiencia !== null && item.eficiencia !== undefined) {
            eficiencia = parseFloat(item.eficiencia);
            if (isNaN(eficiencia)) eficiencia = 0;
        }
        
        // Generar estrellas SEGURO
        let starsHtml = '';
        const ratingEntero = Math.floor(rating);
        const tieneMedia = rating - ratingEntero >= 0.5;
        
        for (let i = 1; i <= 5; i++) {
            if (i <= ratingEntero) {
                starsHtml += '<i class="fas fa-star text-warning"></i>';
            } else if (i === ratingEntero + 1 && tieneMedia) {
                starsHtml += '<i class="fas fa-star-half-alt text-warning"></i>';
            } else {
                starsHtml += '<i class="far fa-star text-muted"></i>';
            }
        }
        
        // Color de eficiencia
        let eficienciaClass = 'success';
        let eficienciaIcon = '<i class="fas fa-arrow-up"></i>';
        
        if (eficiencia < 60) {
            eficienciaClass = 'danger';
            eficienciaIcon = '<i class="fas fa-arrow-down"></i>';
        } else if (eficiencia < 80) {
            eficienciaClass = 'warning';
            eficienciaIcon = '<i class="fas fa-minus"></i>';
        }
        
        // Crear fila
        const filaHtml = `
            <tr>
                <td class="text-center">
                    <span class="badge ${index < 3 ? 'bg-warning' : 'bg-secondary'}">
                        ${index + 1}
                    </span>
                </td>
                <td>
                    <strong>${nombre}</strong><br>
                    <small class="text-muted">${cedula}</small>
                </td>
                <td class="text-center">
                    <span class="h5">${totalLlamadas}</span>
                </td>
                <td class="text-center">
                    <div>${starsHtml}</div>
                    <small class="text-muted">${rating.toFixed(1)}</small>
                </td>
                <td class="text-center">
                    <span class="h6">${contactosEfectivos}</span>
                </td>
                <td class="text-center">
                    <span class="badge bg-${eficienciaClass}">
                        ${eficienciaIcon} ${eficiencia}%
                    </span>
                </td>
            </tr>
        `;
        
        tbody.append(filaHtml);
    });
}
        
        // Función para crear gráfica de distribución por llamador
        function crearGraficaLlamadores(datos) {
    const ctx = document.getElementById('graficaLlamadores').getContext('2d');
    const isDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    if (chartLlamadores) {
        chartLlamadores.destroy();
    }
    
    const labels = datos.map(item => item.nombre.substring(0, 15) + '...');
    const valores = datos.map(item => item.total_llamadas);
    
    chartLlamadores = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: valores,
                backgroundColor: [
                    'rgba(52, 152, 219, 0.7)',
                    'rgba(155, 89, 182, 0.7)',
                    'rgba(46, 204, 113, 0.7)',
                    'rgba(241, 196, 15, 0.7)',
                    'rgba(230, 126, 34, 0.7)',
                    'rgba(231, 76, 60, 0.7)',
                    'rgba(149, 165, 166, 0.7)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: isDarkMode ? '#ffffff' : '#333'
                    }
                },
                tooltip: {
                    backgroundColor: isDarkMode ? '#3d3d3d' : 'rgba(0,0,0,0.8)',
                    titleColor: isDarkMode ? '#ffffff' : '#fff',
                    bodyColor: isDarkMode ? '#ffffff' : '#fff',
                    borderColor: isDarkMode ? '#4d4d4d' : 'transparent',
                    borderWidth: isDarkMode ? 1 : 0
                }
            }
        }
    });
}
        
        // Función para crear gráfica de eficiencia por llamador
        function crearGraficaEficienciaLlamadores(datos) {
    const ctx = document.getElementById('graficaEficienciaLlamadores').getContext('2d');
    const isDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    if (chartEficienciaLlamadores) {
        chartEficienciaLlamadores.destroy();
    }
    
    const labels = datos.map(item => item.nombre.substring(0, 10));
    const eficiencias = datos.map(item => item.eficiencia);
    
    const colores = eficiencias.map(ef => {
        if (ef >= 80) return 'rgba(40, 167, 69, 0.7)';
        if (ef >= 60) return 'rgba(255, 193, 7, 0.7)';
        return 'rgba(220, 53, 69, 0.7)';
    });
    
    chartEficienciaLlamadores = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Eficiencia (%)',
                data: eficiencias,
                backgroundColor: colores,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: isDarkMode ? '#3d3d3d' : 'rgba(0,0,0,0.8)',
                    titleColor: isDarkMode ? '#ffffff' : '#fff',
                    bodyColor: isDarkMode ? '#ffffff' : '#fff',
                    borderColor: isDarkMode ? '#4d4d4d' : 'transparent',
                    borderWidth: isDarkMode ? 1 : 0
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    title: {
                        display: true,
                        text: 'Eficiencia (%)',
                        color: isDarkMode ? '#ffffff' : '#333'
                    },
                    ticks: {
                        color: isDarkMode ? '#ffffff' : '#333'
                    },
                    grid: {
                        color: isDarkMode ? '#4d4d4d' : '#e0e0e0'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Llamador',
                        color: isDarkMode ? '#ffffff' : '#333'
                    },
                    ticks: {
                        color: isDarkMode ? '#ffffff' : '#333'
                    },
                    grid: {
                        color: isDarkMode ? '#4d4d4d' : '#e0e0e0'
                    }
                }
            }
        }
    });
}
        
        // Función para cargar datos de tendencias - VERSIÓN MEJORADA
function cargarDatosTendencias() {
    const filtros = obtenerFiltros();
    
    // Mostrar loading
    $('#graficaTendencias').html(`
        <div class="text-center py-5">
            <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
            <p class="mt-2">Cargando tendencias...</p>
        </div>
    `);
    
    $.ajax({
        url: '../ajax/obtener_datos_tracking_tendencias.php',
        type: 'POST',
        data: filtros,
        dataType: 'json',
        success: function(response) {
            console.log("Respuesta tendencias:", response); // DEBUG
            
            if (response.success) {
                if (response.data.tendencias && response.data.tendencias.length > 0) {
                    // Crear gráfica con período inicial de 7 días
                    crearGraficaTendencias(response.data.tendencias, 7);
                    crearGraficaComparativaSemanal(response.data.comparativa_semanal);
                    actualizarProyeccion(response.data.proyeccion);
                } else {
                    mostrarMensajeSinDatosTendencias();
                }
            } else {
                mostrarErrorTendencias(response.error || 'Error desconocido');
            }
        },
        error: function(xhr, status, error) {
            console.error("Error AJAX tendencias:", error);
            mostrarErrorTendencias('Error de conexión: ' + error);
        }
    });
}

// Función para mostrar mensaje cuando no hay datos de tendencias
function mostrarMensajeSinDatosTendencias() {
    $('#graficaTendencias').html(`
        <div class="text-center py-5">
            <i class="fas fa-chart-line fa-3x text-muted"></i>
            <h5 class="mt-3">No hay datos de tendencias</h5>
            <p class="text-muted">No hay suficientes datos para mostrar tendencias</p>
        </div>
    `);
}

// Función para mostrar error en tendencias
function mostrarErrorTendencias(mensaje) {
    $('#graficaTendencias').html(`
        <div class="text-center py-5">
            <i class="fas fa-exclamation-triangle fa-3x text-danger"></i>
            <h5 class="mt-3">Error</h5>
            <p class="text-muted">${mensaje}</p>
        </div>
    `);
}
        
  // Función para crear gráfica de tendencias - VERSIÓN MEJORADA Y CORREGIDA
function crearGraficaTendencias(datos, periodo = 7) {
    const ctx = document.getElementById('graficaTendencias').getContext('2d');
    const isDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    if (chartTendencias) {
        chartTendencias.destroy();
    }
    
    if (!datos || datos.length === 0) {
        mostrarMensajeSinDatosTendencias();
        return;
    }
    
    const fechaInicio = new Date();
    fechaInicio.setDate(fechaInicio.getDate() - periodo);
    
    const todasFechas = [];
    const fechaActual = new Date(fechaInicio);
    const hoy = new Date();
    
    while (fechaActual <= hoy) {
        todasFechas.push(new Date(fechaActual));
        fechaActual.setDate(fechaActual.getDate() + 1);
    }
    
    const datosMap = {};
    datos.forEach(item => {
        const fecha = new Date(item.fecha);
        const fechaKey = fecha.toISOString().split('T')[0];
        datosMap[fechaKey] = {
            cantidad_llamadas: item.cantidad_llamadas || 0,
            rating_promedio: item.rating_promedio || 0
        };
    });
    
    const fechasFormateadas = [];
    const cantidades = [];
    const ratings = [];
    
    todasFechas.forEach(fecha => {
        const fechaKey = fecha.toISOString().split('T')[0];
        const dia = fecha.getDate().toString().padStart(2, '0');
        const mes = fecha.toLocaleDateString('es-ES', { month: 'short' });
        fechasFormateadas.push(`${dia}/${mes}`);
        
        if (datosMap[fechaKey]) {
            cantidades.push(datosMap[fechaKey].cantidad_llamadas);
            ratings.push((datosMap[fechaKey].rating_promedio || 0) * 20);
        } else {
            cantidades.push(0);
            ratings.push(0);
        }
    });
    
    if (cantidades.every(val => val === 0) && ratings.every(val => val === 0)) {
        mostrarMensajeSinDatosTendencias();
        return;
    }
    
    chartTendencias = new Chart(ctx, {
        type: 'line',
        data: {
            labels: fechasFormateadas,
            datasets: [
                {
                    label: 'Llamadas',
                    data: cantidades,
                    borderColor: 'rgba(52, 152, 219, 1)',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    borderWidth: 3,
                    tension: 0.3,
                    fill: true,
                    yAxisID: 'y'
                },
                {
                    label: 'Rating (escalado)',
                    data: ratings,
                    borderColor: 'rgba(255, 193, 7, 1)',
                    backgroundColor: 'rgba(255, 193, 7, 0.1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: false,
                    borderDash: [5, 5],
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    labels: {
                        usePointStyle: true,
                        boxWidth: 6,
                        color: isDarkMode ? '#ffffff' : '#333'
                    }
                },
                tooltip: {
                    backgroundColor: isDarkMode ? '#3d3d3d' : 'rgba(0,0,0,0.8)',
                    titleColor: isDarkMode ? '#ffffff' : '#fff',
                    bodyColor: isDarkMode ? '#ffffff' : '#fff',
                    borderColor: isDarkMode ? '#4d4d4d' : 'transparent',
                    borderWidth: isDarkMode ? 1 : 0,
                    callbacks: {
                        label: function(context) {
                            if (context.datasetIndex === 0) {
                                return `Llamadas: ${context.raw}`;
                            } else {
                                return `Rating: ${(context.raw / 20).toFixed(1)}/5`;
                            }
                        }
                    }
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Cantidad de Llamadas',
                        color: isDarkMode ? '#ffffff' : '#333'
                    },
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                        color: isDarkMode ? '#ffffff' : '#333'
                    },
                    grid: {
                        color: isDarkMode ? '#4d4d4d' : '#e0e0e0'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Rating Promedio (escalado)',
                        color: isDarkMode ? '#ffffff' : '#333'
                    },
                    grid: {
                        drawOnChartArea: false,
                        color: isDarkMode ? '#4d4d4d' : '#e0e0e0'
                    },
                    min: 0,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return (value / 20).toFixed(1);
                        },
                        color: isDarkMode ? '#ffffff' : '#333'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Fecha',
                        color: isDarkMode ? '#ffffff' : '#333'
                    },
                    ticks: {
                        color: isDarkMode ? '#ffffff' : '#333'
                    },
                    grid: {
                        color: isDarkMode ? '#4d4d4d' : '#e0e0e0'
                    }
                }
            }
        }
    });
    
    // Actualizar botones de período
    $('[data-periodo]').off('click').click(function() {
        const nuevoPeriodo = $(this).data('periodo');
        $('[data-periodo]').removeClass('active');
        $(this).addClass('active');
        crearGraficaTendencias(datos, nuevoPeriodo);
    });
}
        
        // Función para crear gráfica comparativa semanal
        function crearGraficaComparativaSemanal(datos) {
    const ctx = document.getElementById('graficaComparativaSemanal').getContext('2d');
    const isDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    if (chartComparativaSemanal) {
        chartComparativaSemanal.destroy();
    }
    
    const dias = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
    const estaSemana = datos.esta_semana || [0,0,0,0,0,0,0];
    const semanaPasada = datos.semana_pasada || [0,0,0,0,0,0,0];
    
    chartComparativaSemanal = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: dias,
            datasets: [
                {
                    label: 'Esta Semana',
                    data: estaSemana,
                    backgroundColor: 'rgba(52, 152, 219, 0.7)'
                },
                {
                    label: 'Semana Pasada',
                    data: semanaPasada,
                    backgroundColor: 'rgba(155, 89, 182, 0.7)'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: {
                        color: isDarkMode ? '#ffffff' : '#333'
                    }
                },
                tooltip: {
                    backgroundColor: isDarkMode ? '#3d3d3d' : 'rgba(0,0,0,0.8)',
                    titleColor: isDarkMode ? '#ffffff' : '#fff',
                    bodyColor: isDarkMode ? '#ffffff' : '#fff',
                    borderColor: isDarkMode ? '#4d4d4d' : 'transparent',
                    borderWidth: isDarkMode ? 1 : 0
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Cantidad de Llamadas',
                        color: isDarkMode ? '#ffffff' : '#333'
                    },
                    ticks: {
                        color: isDarkMode ? '#ffffff' : '#333'
                    },
                    grid: {
                        color: isDarkMode ? '#4d4d4d' : '#e0e0e0'
                    }
                },
                x: {
                    ticks: {
                        color: isDarkMode ? '#ffffff' : '#333'
                    },
                    grid: {
                        color: isDarkMode ? '#4d4d4d' : '#e0e0e0'
                    }
                }
            }
        }
    });
}
        
        // Función para actualizar proyección
        function actualizarProyeccion(data) {
            let html = '';
            
            if (data) {
                const crecimiento = data.crecimiento || 0;
                const proyeccion = data.proyeccion || 0;
                const color = crecimiento >= 0 ? 'success' : 'danger';
                const icono = crecimiento >= 0 ? 'arrow-up' : 'arrow-down';
                
                html = `
                    <div class="text-center mb-4">
                        <div class="display-4 mb-2 text-${color}">
                            ${crecimiento >= 0 ? '+' : ''}${crecimiento}%
                        </div>
                        <p class="text-muted">Tasa de crecimiento</p>
                    </div>
                    
                    <div class="row">
                        <div class="col-6">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h5 class="card-title">${data.promedio_diario || 0}</h5>
                                    <p class="card-text small">Promedio diario</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h5 class="card-title">${proyeccion}</h5>
                                    <p class="card-text small">Proyección mensual</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <h6>Recomendaciones:</h6>
                        <ul class="small text-muted">
                            ${crecimiento >= 0 ? 
                                '<li>¡Excelente trabajo! Mantén el ritmo actual</li>' : 
                                '<li>Considera aumentar el número de llamadas diarias</li>'}
                            <li>Enfócate en mejorar la calidad de las llamadas</li>
                            <li>Revisa los resultados con menor tasa de éxito</li>
                        </ul>
                    </div>
                `;
            } else {
                html = '<div class="text-center text-muted py-4">No hay datos para proyectar</div>';
            }
            
            $('#proyeccionContainer').html(html);
        }
        
        // Función para cargar tabla de detalle
        function cargarTablaDetalle(pagina = 1) {
            const filtros = obtenerFiltros();
            filtros.pagina = pagina;
            filtros.limite = 10;
            
            $.ajax({
                url: '../ajax/obtener_detalle_tracking.php',
                type: 'POST',
                data: filtros,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        actualizarTablaDetalleLlamadas(response.data.llamadas, response.data.total);
                        actualizarPaginacion(pagina, response.data.total_paginas);
                    }
                }
            });
        }
        
        // Función para actualizar tabla de detalle de llamadas
        function actualizarTablaDetalleLlamadas(datos, total) {
            const tbody = $('#tablaDetalleLlamadas tbody');
            tbody.empty();
            
            $('#totalRegistros').text(total + ' registros');
            
            if (datos.length === 0) {
                tbody.append(`
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            No hay llamadas registradas para los filtros seleccionados
                        </td>
                    </tr>
                `);
                return;
            }
            
            datos.forEach(item => {
                // Formatear fecha/hora
                const fechaHora = new Date(item.fecha_llamada);
                const fecha = fechaHora.toLocaleDateString('es-ES');
                const hora = fechaHora.toLocaleTimeString('es-ES', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                // Estrellas del rating
                let starsHtml = '';
                const rating = item.rating || 0;
                for (let i = 1; i <= 5; i++) {
                    if (i <= rating) {
                        starsHtml += '<i class="fas fa-star text-warning"></i>';
                    } else {
                        starsHtml += '<i class="far fa-star text-muted"></i>';
                    }
                }
                
                // Clase del resultado
                let resultadoClass = 'resultado-otro';
                if (item.id_resultado == 1) resultadoClass = 'resultado-contactado';
                else if (item.id_resultado == 2 || item.id_resultado == 5) resultadoClass = 'resultado-no-contesta';
                else if (item.id_resultado == 7) resultadoClass = 'resultado-rechazado';
                
                tbody.append(`
                    <tr>
                        <td>
                            <small class="text-muted">${fecha}</small><br>
                            <strong>${hora}</strong>
                        </td>
                        <td>
                            <strong>${item.llamador_nombre}</strong>
                        </td>
                        <td>
                            ${item.referenciado_nombre} ${item.referenciado_apellido}<br>
                            <small class="text-muted">${item.referenciado_cedula}</small>
                        </td>
                        <td>
                            <i class="fas fa-phone text-success"></i> ${item.telefono}
                        </td>
                        <td>
                            <span class="resultado-badge ${resultadoClass}">
                                ${item.resultado_nombre}
                            </span>
                        </td>
                        <td>
                            <div class="rating-stars">
                                ${starsHtml}
                            </div>
                            ${rating > 0 ? `<small class="text-muted">(${rating})</small>` : ''}
                        </td>
                        <td>
                            ${item.observaciones ? 
                                `<small>${item.observaciones.substring(0, 50)}...</small>` : 
                                '<span class="text-muted">-</span>'}
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="verDetalleLlamada(${item.id_llamada})">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                `);
            });
        }
        
        // Función para actualizar paginación
        function actualizarPaginacion(paginaActual, totalPaginas) {
            const paginacion = $('#paginacionDetalle');
            paginacion.empty();
            
            if (totalPaginas <= 1) return;
            
            // Botón anterior
            paginacion.append(`
                <li class="page-item ${paginaActual === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="cargarTablaDetalle(${paginaActual - 1})">
                        &laquo;
                    </a>
                </li>
            `);
            
            // Números de página
            const inicio = Math.max(1, paginaActual - 2);
            const fin = Math.min(totalPaginas, paginaActual + 2);
            
            for (let i = inicio; i <= fin; i++) {
                paginacion.append(`
                    <li class="page-item ${i === paginaActual ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="cargarTablaDetalle(${i})">
                            ${i}
                        </a>
                    </li>
                `);
            }
            
            // Botón siguiente
            paginacion.append(`
                <li class="page-item ${paginaActual === totalPaginas ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="cargarTablaDetalle(${paginaActual + 1})">
                        &raquo;
                    </a>
                </li>
            `);
        }
        
        // Función para ver detalle de una llamada (modal)
        window.verDetalleLlamada = function(idLlamada) {
            $.ajax({
                url: '../ajax/obtener_detalle_llamada.php',
                type: 'POST',
                data: { id_llamada: idLlamada },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        mostrarModalDetalleLlamada(response.data);
                    }
                }
            });
        };
        
        // Función para mostrar modal de detalle de llamada
        function mostrarModalDetalleLlamada(datos) {
            const modalHtml = `
                <div class="modal fade" id="modalDetalleLlamada" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="fas fa-phone-alt me-2"></i>
                                    Detalle de Llamada
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-user-tie"></i> Información del Llamador</h6>
                                        <p>
                                            <strong>${datos.llamador_nombre}</strong><br>
                                            <small class="text-muted">${datos.llamador_cedula}</small>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-user"></i> Información del Referenciado</h6>
                                        <p>
                                            <strong>${datos.referenciado_nombre} ${datos.referenciado_apellido}</strong><br>
                                            <small class="text-muted">Cédula: ${datos.referenciado_cedula}</small><br>
                                            <small class="text-muted">Teléfono: ${datos.telefono}</small>
                                        </p>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <h6><i class="fas fa-calendar-alt"></i> Fecha y Hora</h6>
                                        <p>${new Date(datos.fecha_llamada).toLocaleString('es-ES')}</p>
                                    </div>
                                    <div class="col-md-4">
                                        <h6><i class="fas fa-check-circle"></i> Resultado</h6>
                                        <span class="badge ${datos.id_resultado == 1 ? 'bg-success' : 'bg-secondary'}">
                                            ${datos.resultado_nombre}
                                        </span>
                                    </div>
                                    <div class="col-md-4">
                                        <h6><i class="fas fa-star"></i> Calificación</h6>
                                        <div class="rating-stars fs-5">
                                            ${'★'.repeat(datos.rating || 0)}${'☆'.repeat(5 - (datos.rating || 0))}
                                        </div>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <h6><i class="fas fa-comment-dots"></i> Observaciones</h6>
                                <div class="card bg-light">
                                    <div class="card-body">
                                        ${datos.observaciones || '<em class="text-muted">Sin observaciones registradas</em>'}
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    Cerrar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Remover modal anterior si existe
            $('#modalDetalleLlamada').remove();
            
            // Agregar modal al DOM
            $('body').append(modalHtml);
            
            // Mostrar modal
            const modal = new bootstrap.Modal(document.getElementById('modalDetalleLlamada'));
            modal.show();
        }
        
        // Función para configurar eventos
        function configurarEventos() {
            // Aplicar filtros
            $('#btnAplicarFiltros').click(function() {
                cargarDatosIniciales();
                
                // Recargar tab activo si es necesario
                const tabActivo = $('.tab-pane.active').attr('id');
                switch(tabActivo) {
                    case 'tab-calidad':
                        cargarDatosCalidad();
                        break;
                    case 'tab-llamadores':
                        cargarDatosLlamadores();
                        break;
                    case 'tab-tendencias':
                        cargarDatosTendencias();
                        break;
                    case 'tab-detalle':
                        cargarTablaDetalle();
                        break;
                }
            });
            
            // Enter en fecha también aplica filtros
            $('#selectFecha').keypress(function(e) {
                if (e.which === 13) {
                    $('#btnAplicarFiltros').click();
                }
            });
            
            // Exportar
            $('#btnExportar').click(function() {
                exportarReporte();
            });
        }
        
        // Función para exportar reporte
        function exportarReporte() {
            const filtros = obtenerFiltros();
            const queryString = new URLSearchParams(filtros).toString();
            window.open(`../ajax/exportar_reporte_tracking.php?${queryString}`, '_blank');
        }
        
        // Función para mostrar error
        function mostrarError(mensaje) {
            const alertHtml = `
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    ${mensaje}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            $('.main-container').prepend(alertHtml);
            
            setTimeout(() => {
                $('.alert').alert('close');
            }, 5000);
        }
        
        // Función para iniciar contador compacto
        function iniciarContadorCompacto() {
            const fechaElecciones = new Date(2026, 2, 8, 8, 0, 0).getTime();
            
            setInterval(function() {
                const ahora = new Date().getTime();
                const tiempoRestante = fechaElecciones - ahora;
                
                if (!document.getElementById('compact-days')) return;
                
                if (tiempoRestante <= 0) {
                    document.getElementById('compact-days').textContent = '00';
                    document.getElementById('compact-hours').textContent = '00';
                    document.getElementById('compact-minutes').textContent = '00';
                    document.getElementById('compact-seconds').textContent = '00';
                    return;
                }
                
                const totalSegundos = Math.floor(tiempoRestante / 1000);
                const dias = Math.floor(totalSegundos / 86400);
                const horas = Math.floor((totalSegundos % 86400) / 3600);
                const minutos = Math.floor((totalSegundos % 3600) / 60);
                const segundos = totalSegundos % 60;
                
                document.getElementById('compact-days').textContent = dias.toString().padStart(2, '0');
                document.getElementById('compact-hours').textContent = horas.toString().padStart(2, '0');
                document.getElementById('compact-minutes').textContent = minutos.toString().padStart(2, '0');
                document.getElementById('compact-seconds').textContent = segundos.toString().padStart(2, '0');
                
                const daysEl = document.getElementById('compact-days');
                if (dias <= 7) {
                    daysEl.style.color = '#e74c3c';
                } else if (dias <= 30) {
                    daysEl.style.color = '#f39c12';
                } else {
                    daysEl.style.color = '#ffffff';
                }
            }, 1000);
        }
    </script>
    <script src="../js/modal-sistema.js"></script>
</body>
</html>