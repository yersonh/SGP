<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../models/ReferenciadoModel.php';
require_once __DIR__ . '/../../models/ZonaModel.php';
require_once __DIR__ . '/../../models/SistemaModel.php';

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

// Obtener datos del usuario logueado
$usuario_logueado = $usuarioModel->getUsuarioById($_SESSION['id_usuario']);

// Obtener fecha por defecto (hoy)
$fecha_seleccionada = $_GET['fecha'] ?? date('Y-m-d');

// Obtener todas las zonas para filtros
$zonas = $zonaModel->getAll();

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
    <title>Reporte Diario de Referenciados - Super Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../styles/superadmin_reporte_votos.css">
</head>
<style>
    /* Agrega estos estilos al archivo superadmin_reporte_votos.css */

/* Centrar las pestañas completamente */
.tabs-header {
    display: flex !important;
    justify-content: center !important;
    width: 100% !important;
}

.tabs-header > .d-flex {
    width: 100% !important;
    justify-content: center !important;
}

/* Opcional: Ajustar el espaciado entre pestañas */
.tab-btn {
    margin: 0 5px;
    min-width: 120px; /* Ancho mínimo para uniformidad */
}
/* Centrar los KPIs */
.kpis-container {
    display: flex !important;
    justify-content: center !important;
    flex-wrap: wrap !important;
    gap: 20px !important;
    width: 100% !important;
}

/* Ajustar el ancho de cada tarjeta KPI */
.kpi-card {
    flex: 0 1 auto !important; /* No crecer, pero sí encoger */
    min-width: 250px !important; /* Ancho mínimo */
    max-width: 300px !important; /* Ancho máximo */
}

/* Alineación del contenido dentro de cada KPI */
.kpi-content {
    text-align: center !important;
}

/* Para asegurar que el ícono también esté centrado */
.kpi-icon {
    margin: 0 auto 15px auto !important;
    display: flex !important;
    justify-content: center !important;
    align-items: center !important;
}
</style>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="header-container">
            <div class="header-top">
                <div class="header-title">
                    <h1><i class="fas fa-chart-line"></i> Reporte Diario de Referenciados</h1>
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
                <li class="breadcrumb-item"><a href="superadmin_rendimiento_votos.php"><i class="fas fa-chart-bar"></i> Rendimiento</a></li>
                <li class="breadcrumb-item active"><i class="fas fa-file-chart-line"></i> Reporte Diario</li>
            </ol>
        </nav>
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
                <label for="selectTipo"><i class="fas fa-filter"></i> Tipo Elección:</label>
                <select id="selectTipo">
                    <option value="todos">Ambas elecciones</option>
                    <option value="camara">Solo Cámara</option>
                    <option value="senado">Solo Senado</option>
                </select>
            </div>
            
            <div class="filtro-group">
                <label for="selectZona"><i class="fas fa-map-marker-alt"></i> Zona:</label>
                <select id="selectZona">
                    <option value="todas">Todas las zonas</option>
                    <?php foreach ($zonas as $zona): ?>
                        <option value="<?php echo $zona['id_zona']; ?>">
                            <?php echo htmlspecialchars($zona['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
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
                <button class="tab-btn" data-tab="graficas">
                    <i class="fas fa-chart-bar"></i> Gráficas
                </button>
                <button class="tab-btn" data-tab="comparativas">
                    <i class="fas fa-balance-scale"></i> Comparativas
                </button>
                <!--
                <button class="tab-btn" data-tab="tendencias">
                    <i class="fas fa-chart-line"></i> Tendencias
                </button>-->
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
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div class="kpi-content">
                                <div class="kpi-title">Referenciados Hoy</div>
                                <div class="kpi-value" id="kpiReferenciadosHoy">
                                    0
                                </div>
                                <div class="kpi-change" id="kpiCambio">
                                    Cargando...
                                </div>
                            </div>
                        </div>
                        
                        <div class="kpi-card kpi-secondary">
                            <div class="kpi-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="kpi-content">
                                <div class="kpi-title">Referenciadores Activos</div>
                                <div class="kpi-value" id="kpiReferenciadoresActivos">
                                    0
                                </div>
                                <div class="kpi-subtitle">Registraron hoy</div>
                            </div>
                        </div>
                        
                        <div class="kpi-card kpi-warning">
                            <div class="kpi-icon">
                                <i class="fas fa-chart-pie"></i>
                            </div>
                            <div class="kpi-content">
                                <div class="kpi-title">Distribución</div>
                                <div class="kpi-value" id="kpiDistribucion">
                                    0/0
                                </div>
                                <div class="kpi-subtitle">Cámara / Senado</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Gráfica Principal del Resumen -->
                    <div class="grafica-principal-container">
                        <div class="grafica-header">
                            <h4><i class="fas fa-chart-bar"></i> Actividad por Hora</h4>
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
                            <canvas id="graficaResumen"></canvas>
                        </div>
                    </div>
                    
                    <!-- Mini Gráficas -->
                    <div class="mini-graficas-container">
                        <div class="mini-grafica-card">
                            <h5><i class="fas fa-user-tag"></i> Top Referenciadores</h5>
                            <div class="mini-grafica" id="miniGraficaTopReferenciadores">
                                <div class="text-center py-4">
                                    <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                                    <p class="mt-2">Cargando datos...</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mini-grafica-card">
                            <h5><i class="fas fa-map-marker-alt"></i> Top Zonas</h5>
                            <div class="mini-grafica" id="miniGraficaTopZonas">
                                <div class="text-center py-4">
                                    <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                                    <p class="mt-2">Cargando datos...</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mini-grafica-card">
                            <h5><i class="fas fa-clock"></i> Hora Pico</h5>
                            <div class="mini-grafica" id="miniGraficaHoraPico">
                                <div class="hora-pico-info">
                                    <div class="hora-pico-value" id="horaPicoValor">--:--</div>
                                    <div class="hora-pico-desc" id="horaPicoDesc">Cargando...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- TAB 2: GRÁFICAS DIARIAS -->
                <div class="tab-pane" id="tab-graficas">
                    <div class="graficas-grid">
                        <div class="grafica-full-card">
                            <div class="grafica-header">
                                <h4><i class="fas fa-chart-bar"></i> Referenciados por Hora</h4>
                                <select class="select-tipo-grafica" data-grafica="horas">
                                    <option value="bar">Barras</option>
                                    <option value="line">Líneas</option>
                                </select>
                            </div>
                            <div class="grafica-body">
                                <canvas id="graficaPorHora"></canvas>
                            </div>
                        </div>
                        
                        <div class="grafica-half-card">
                            <div class="grafica-header">
                                <h4><i class="fas fa-chart-pie"></i> Distribución por Elección</h4>
                            </div>
                            <div class="grafica-body">
                                <canvas id="graficaDistribucion"></canvas>
                            </div>
                        </div>
                        
                        <div class="grafica-half-card">
                            <div class="grafica-header">
                                <h4><i class="fas fa-map"></i> Distribución por Zona</h4>
                            </div>
                            <div class="grafica-body">
                                <canvas id="graficaPorZona"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <style>
                        .graficas-grid {
                            display: grid;
                            grid-template-columns: 1fr;
                            gap: 20px;
                        }
                        
                        .grafica-full-card,
                        .grafica-half-card {
                            background: white;
                            border-radius: 10px;
                            padding: 20px;
                            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
                        }
                        
                        .grafica-full-card .grafica-body {
                            height: 300px;
                        }
                        
                        .grafica-half-card .grafica-body {
                            height: 250px;
                        }
                        
                        @media (min-width: 992px) {
                            .graficas-grid {
                                grid-template-columns: 1fr 1fr;
                            }
                            
                            .grafica-full-card {
                                grid-column: span 2;
                            }
                        }
                    </style>
                </div>
                
                <!-- TAB 3: COMPARATIVAS -->
                <div class="tab-pane" id="tab-comparativas">
                    <div class="comparativas-header">
                        <div class="comparativa-selector">
                            <button class="btn-comparativa active" data-compara="ayer">
                                <i class="fas fa-calendar-day"></i> Vs Ayer
                            </button>
                            <button class="btn-comparativa" data-compara="semana">
                                <i class="fas fa-calendar-week"></i> Vs Semana Pasada
                            </button>
                            <button class="btn-comparativa" data-compara="mes">
                                <i class="fas fa-calendar-alt"></i> Vs Mes Pasado
                            </button>
                        </div>
                    </div>
                    
                    <div id="comparativasContenido" class="mt-4">
                        <div class="text-center py-5">
                            <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                            <h5>Seleccione un tipo de comparación</h5>
                            <p class="text-muted">Use los botones superiores para seleccionar el tipo de comparación</p>
                        </div>
                    </div>
                    
                    <style>
                        .comparativas-header {
                            background: #f8f9fa;
                            padding: 20px;
                            border-radius: 10px;
                            margin-bottom: 20px;
                        }
                        
                        .comparativa-selector {
                            display: flex;
                            gap: 10px;
                            flex-wrap: wrap;
                        }
                        
                        .btn-comparativa {
                            padding: 10px 20px;
                            background: white;
                            border: 1px solid #ddd;
                            border-radius: 5px;
                            cursor: pointer;
                            display: flex;
                            align-items: center;
                            gap: 8px;
                            transition: all 0.3s;
                        }
                        
                        .btn-comparativa:hover {
                            background: #f0f0f0;
                        }
                        
                        .btn-comparativa.active {
                            background: #3498db;
                            color: white;
                            border-color: #3498db;
                        }
                    </style>
                </div>
                
                <!-- TAB 4: TENDENCIAS 
                <div class="tab-pane" id="tab-tendencias">
                    <div class="text-center py-5">
                        <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                        <h5>Análisis de Tendencias</h5>
                        <p class="text-muted">Esta sección está en desarrollo</p>
                    </div>
                </div>-->
                
                <!-- TAB 5: DETALLE -->
                <div class="tab-pane" id="tab-detalle">
                    <div class="table-responsive">
                        <table class="table table-hover" id="tablaDetalle">
                            <thead>
                                <tr>
                                    <th>Hora</th>
                                    <th>Referenciador</th>
                                    <th>Referenciado</th>
                                    <th>Cédula</th>
                                    <th>Teléfono</th>
                                    <th>Elección</th>
                                    <th>Zona</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
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

    <!-- Footer -->
    <footer class="system-footer">
        <div class="container text-center mb-3">
            <img src="../imagenes/Logo-artguru.png" 
                alt="Logo" 
                class="logo-clickable"
                onclick="mostrarModalSistema()"
                title="Haz clic para ver información del sistema">
        </div>

        <div class="container text-center">
            <p>
                © Derechos de autor Reservados • <strong>Ing. Rubén Darío González García</strong> • Equipo de soporte • SISGONTech<br>
                Email: sisgonnet@gmail.com • Contacto: +57 3106310227 • Puerto Gaitán, Colombia • <?php echo date('Y'); ?>
            </p>
        </div>
    </footer>
    
    <!-- Modal de Información del Sistema -->
    <div class="modal fade" id="modalSistema" tabindex="-1" aria-labelledby="modalSistemaLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalSistemaLabel">
                        <i class="fas fa-info-circle me-2"></i>Información del Sistema
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Logo centrado -->
                    <div class="modal-logo-container">
                        <img src="../imagenes/Logo-artguru.png" alt="Logo del Sistema" class="modal-logo">
                    </div>
                    
                    <!-- Título -->
                    <div class="text-center mb-4">
                        <h4 class="text-secondary mb-4">
                            <strong>Gestión Política de Alta Precisión</strong>
                        </h4>
                        
                        <!-- Información de Licencia -->
                        <div class="licencia-info">
                            <div class="licencia-header">
                                <h6 class="licencia-title">Licencia Runtime</h6>
                                <span class="licencia-dias">
                                    <strong><?php echo $diasRestantes; ?> días restantes</strong>
                                </span>
                            </div>
                            
                            <div class="licencia-progress">
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
                    </div>
                    
                    <!-- Imagen del ingeniero -->
                    <div class="feature-image-container">
                        <img src="../imagenes/ingeniero2.png" alt="Logo de Herramienta" class="feature-img-header">
                        <div class="profile-info mt-3">
                            <h4 class="profile-name"><strong>Rubén Darío Gonzáles García</strong></h4>
                            
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
        let chartResumen = null;
        let chartPorHora = null;
        let chartDistribucion = null;
        let chartPorZona = null;
        
        // Inicializar al cargar la página
        $(document).ready(function() {
            // Inicializar sistema de tabs
            inicializarTabs();
            
            // Cargar datos iniciales
            cargarDatosIniciales();
            
            // Configurar eventos
            configurarEventos();
            
            // Inicializar modal del sistema
            inicializarModalSistema();
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
                    case 'graficas':
                        cargarGraficasCompletas();
                        break;
                    case 'detalle':
                        cargarTablaDetalle();
                        break;
                }
            });
        }
        
        // Función para cargar datos iniciales
        function cargarDatosIniciales() {
            const fecha = $('#selectFecha').val();
            const tipo = $('#selectTipo').val();
            const zona = $('#selectZona').val();
            
            // Mostrar loading
            mostrarLoadingKPIs();
            
            // Cargar datos del resumen
            $.ajax({
                url: '../ajax/obtener_datos_resumen.php',
                type: 'POST',
                data: {
                    fecha: fecha,
                    tipo: tipo,
                    zona: zona
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        actualizarKPIs(response.data);
                        crearGraficaResumen(response.data.grafica_horas);
                        actualizarMiniGraficas(response.data);
                    } else {
                        mostrarError('Error al cargar datos: ' + (response.error || 'Error desconocido'));
                    }
                },
                error: function() {
                    mostrarError('Error de conexión con el servidor');
                }
            });
        }
        
        // Función para mostrar loading en KPIs
        function mostrarLoadingKPIs() {
            $('#kpiReferenciadosHoy').html('<i class="fas fa-spinner fa-spin"></i>');
            $('#kpiReferenciadoresActivos').html('<i class="fas fa-spinner fa-spin"></i>');
            $('#kpiDistribucion').html('<i class="fas fa-spinner fa-spin"></i>');
            $('#kpiCambio').html('Cargando...');
        }
        
        // Función para actualizar KPIs
        // Función para actualizar KPIs
function actualizarKPIs(data) {
    // Referenciados hoy
    $('#kpiReferenciadosHoy').text(data.total_referenciados || 0);
    
    // Referenciadores activos
    $('#kpiReferenciadoresActivos').text(data.referenciadores_activos || 0);
    
    // Distribución Cámara/Senado
    const camara = data.camara || 0;
    const senado = data.senado || 0;
    $('#kpiDistribucion').text(camara + '/' + senado);
    
    // Cambio vs ayer
    const cambio = data.cambio_vs_ayer || 0;
    const porcentaje = data.porcentaje_cambio || 0;
    
    $('#kpiCambio').html(cambio >= 0 ? 
        `+${cambio} (${porcentaje}%) vs ayer` :
        `${cambio} (${porcentaje}%) vs ayer`);
    
    if (cambio >= 0) {
        $('#kpiCambio').removeClass('negative').addClass('positive');
    } else {
        $('#kpiCambio').removeClass('positive').addClass('negative');
    }
    
    // Hora pico
    if (data.hora_pico) {
        $('#horaPicoValor').text(data.hora_pico.hora + ':00');
        $('#horaPicoDesc').text(data.hora_pico.descripcion);
    }
}
        
        // Función para crear gráfica de resumen
        function crearGraficaResumen(datos) {
            const ctx = document.getElementById('graficaResumen').getContext('2d');
            
            // Destruir gráfica anterior si existe
            if (chartResumen) {
                chartResumen.destroy();
            }
            
            // Preparar datos
            const horas = datos.map(item => item.hora + ':00');
            const cantidades = datos.map(item => item.cantidad);
            
            // Crear nueva gráfica
            chartResumen = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: horas,
                    datasets: [{
                        label: 'Referenciados',
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
                            display: false
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Cantidad de Referenciados'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Hora del Día'
                            }
                        }
                    }
                }
            });
            
            // Configurar eventos de botones de tipo de gráfica
            $('.btn-grafica-action').off('click').click(function() {
                const tipo = $(this).data('chart-type');
                
                // Cambiar tipo de gráfica
                chartResumen.config.type = tipo;
                chartResumen.update();
                
                // Actualizar botones activos
                $('.btn-grafica-action').removeClass('active');
                $(this).addClass('active');
            });
        }
        
       // Función para actualizar mini gráficas
function actualizarMiniGraficas(data) {
    // Top referenciadores
    if (data.top_referenciadores && data.top_referenciadores.length > 0) {
        let html = '<div class="list-group">';
        data.top_referenciadores.forEach((item, index) => {
            html += `
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <div>
                        <span class="badge bg-primary me-2">${index + 1}</span>
                        <small>${item.nombre}</small>
                    </div>
                    <span class="badge bg-secondary">${item.cantidad}</span>
                </div>
            `;
        });
        html += '</div>';
        $('#miniGraficaTopReferenciadores').html(html);
    }
    
    // Top zonas
    if (data.top_zonas && data.top_zonas.length > 0) {
        let html = '<div class="list-group">';
        data.top_zonas.forEach((item, index) => {
            html += `
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <div>
                        <span class="badge bg-success me-2">${index + 1}</span>
                        <small>${item.nombre}</small>
                    </div>
                    <span class="badge bg-secondary">${item.cantidad}</span>
                </div>
            `;
        });
        html += '</div>';
        $('#miniGraficaTopZonas').html(html);
    }
}
        
        // Función para cargar gráficas completas
        function cargarGraficasCompletas() {
            const fecha = $('#selectFecha').val();
            const tipo = $('#selectTipo').val();
            const zona = $('#selectZona').val();
            
            $.ajax({
                url: '../ajax/obtener_datos_graficas.php',
                type: 'POST',
                data: {
                    fecha: fecha,
                    tipo: tipo,
                    zona: zona
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        crearGraficasCompletas(response.data);
                    }
                }
            });
        }
        
        // Función para crear gráficas completas
        function crearGraficasCompletas(data) {
            // Gráfica por hora
            crearGraficaPorHora(data.por_hora);
            
            // Gráfica de distribución
            crearGraficaDistribucion(data.distribucion);
            
            // Gráfica por zona
            crearGraficaPorZona(data.por_zona);
        }
        
        // Función para crear gráfica por hora
        function crearGraficaPorHora(datos) {
            const ctx = document.getElementById('graficaPorHora').getContext('2d');
            
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
                        label: 'Referenciados',
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
                            display: false
                        }
                    }
                }
            });
            
            // Cambiar tipo de gráfica desde select
            $('.select-tipo-grafica[data-grafica="horas"]').change(function() {
                chartPorHora.config.type = $(this).val();
                chartPorHora.update();
            });
        }
        
        // Función para crear gráfica de distribución
        function crearGraficaDistribucion(datos) {
            const ctx = document.getElementById('graficaDistribucion').getContext('2d');
            
            if (chartDistribucion) {
                chartDistribucion.destroy();
            }
            
            chartDistribucion = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: ['Cámara', 'Senado'],
                    datasets: [{
                        data: [datos.camara || 0, datos.senado || 0],
                        backgroundColor: [
                            'rgba(52, 152, 219, 0.7)',
                            'rgba(46, 204, 113, 0.7)'
                        ],
                        borderColor: [
                            'rgba(52, 152, 219, 1)',
                            'rgba(46, 204, 113, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
        
        // Función para crear gráfica por zona
        function crearGraficaPorZona(datos) {
            const ctx = document.getElementById('graficaPorZona').getContext('2d');
            
            if (chartPorZona) {
                chartPorZona.destroy();
            }
            
            const zonas = datos.map(item => item.zona);
            const cantidades = datos.map(item => item.cantidad);
            
            chartPorZona = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: zonas,
                    datasets: [{
                        label: 'Referenciados',
                        data: cantidades,
                        backgroundColor: 'rgba(155, 89, 182, 0.7)',
                        borderColor: 'rgba(155, 89, 182, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
        
        // Función para cargar tabla de detalle
        function cargarTablaDetalle() {
            const fecha = $('#selectFecha').val();
            const tipo = $('#selectTipo').val();
            const zona = $('#selectZona').val();
            
            $.ajax({
                url: '../ajax/obtener_detalle_referenciados.php',
                type: 'POST',
                data: {
                    fecha: fecha,
                    tipo: tipo,
                    zona: zona
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        actualizarTablaDetalle(response.data);
                    }
                }
            });
        }
        
        // Función para actualizar tabla de detalle (CON SOPORTE MODO OSCURO)
function actualizarTablaDetalle(datos) {
    const tbody = $('#tablaDetalle tbody');
    tbody.empty();
    
    // Verificar si está en modo oscuro
    const isDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    if (datos.length === 0) {
        // Mensaje cuando no hay datos
        const claseFila = isDarkMode ? 'text-light' : '';
        const claseTexto = isDarkMode ? 'text-light' : 'text-muted';
        
        tbody.append(`
            <tr class="${claseFila}">
                <td colspan="7" class="text-center py-4 ${claseTexto}">
                    <i class="fas fa-info-circle ${isDarkMode ? 'text-primary' : ''}"></i> 
                    No hay registros para la fecha seleccionada
                </td>
            </tr>
        `);
        return;
    }
    
    // Generar filas con estilos condicionales
    datos.forEach((item, index) => {
        const hora = new Date(item.fecha_registro).toLocaleTimeString('es-ES', {
            hour: '2-digit',
            minute: '2-digit'
        });
        
        // Clases condicionales para modo oscuro
        const claseFila = isDarkMode ? 'text-light' : '';
        const claseBadge = isDarkMode ? 
            (item.tipo_eleccion === 'camara' ? 'bg-primary' : 'bg-success') : 
            (item.tipo_eleccion === 'camara' ? 'bg-primary' : 'bg-success');
        
        // Color de fondo alternado para filas (mejor visibilidad en modo oscuro)
        const bgClass = isDarkMode ? 
            (index % 2 === 0 ? 'bg-dark-row' : 'bg-darker-row') : 
            '';
        
        tbody.append(`
            <tr class="${claseFila} ${bgClass}">
                <td>${hora}</td>
                <td>${item.referenciador_nombre || 'N/A'}</td>
                <td>${item.nombres} ${item.apellidos}</td>
                <td>${item.cedula || 'N/A'}</td>
                <td>${item.telefono || 'N/A'}</td>
                <td>
                    <span class="badge ${claseBadge}">
                        ${item.tipo_eleccion === 'camara' ? 'Cámara' : 'Senado'}
                    </span>
                </td>
                <td>${item.zona_nombre || 'N/A'}</td>
            </tr>
        `);
    });
    
    // Aplicar estilos adicionales si está en modo oscuro
    if (isDarkMode) {
        aplicarEstilosTablaModoOscuro();
    }
}

// Función auxiliar para aplicar estilos de modo oscuro a la tabla
function aplicarEstilosTablaModoOscuro() {
    const tabla = $('#tablaDetalle');
    
    // Asegurar que la tabla tenga la clase table-dark de Bootstrap
    tabla.removeClass('table-hover').addClass('table-dark');
    
    // Estilos personalizados para mejor visibilidad
    tabla.find('td, th').css({
        'border-color': '#4d4d4d',
        'background-color': 'transparent'
    });
    
    // Filas alternadas
    tabla.find('tbody tr:nth-child(even)').css('background-color', '#3a3a3a');
    tabla.find('tbody tr:nth-child(odd)').css('background-color', '#2d2d2d');
    
    // Efecto hover
    tabla.find('tbody tr').hover(
        function() {
            $(this).css('background-color', '#4a4a4a');
        },
        function() {
            const index = $(this).index();
            if (index % 2 === 0) {
                $(this).css('background-color', '#2d2d2d');
            } else {
                $(this).css('background-color', '#3a3a3a');
            }
        }
    );
}
// Función para aplicar modo oscuro a TODO el contenido dinámico
function aplicarModoOscuroGlobal() {
    const isDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    if (!isDarkMode) return;
    
    // 1. Aplicar a la tabla
    aplicarEstilosTablaModoOscuro();
    
    // 2. Aplicar a cards de comparativas
    $('.card').each(function() {
        if ($(this).closest('#comparativasContenido').length) {
            $(this).css({
                'background-color': '#3d3d3d',
                'color': '#e0e0e0',
                'border-color': '#4d4d4d'
            });
            
            $(this).find('.card-header').css({
                'background-color': '#4d4d4d',
                'border-color': '#5d5d5d',
                'color': '#ffffff'
            });
        }
    });
    
    // 3. Aplicar a gráficas
    $('.grafica-full-card, .grafica-half-card').css({
        'background-color': '#3d3d3d',
        'color': '#e0e0e0',
        'border-color': '#4d4d4d'
    });
}

// Escuchar cambios en el tema del sistema
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
    aplicarModoOscuroGlobal();
});

// Llamar después de cada carga de contenido dinámico
function cargarTablaDetalle() {
    const fecha = $('#selectFecha').val();
    const tipo = $('#selectTipo').val();
    const zona = $('#selectZona').val();
    
    $.ajax({
        url: '../ajax/obtener_detalle_referenciados.php',
        type: 'POST',
        data: {
            fecha: fecha,
            tipo: tipo,
            zona: zona
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                actualizarTablaDetalle(response.data);
                // Aplicar modo oscuro después de cargar
                setTimeout(aplicarModoOscuroGlobal, 100);
            }
        }
    });
}
        // Función para configurar eventos
        function configurarEventos() {
            // Aplicar filtros
            $('#btnAplicarFiltros').click(function() {
                cargarDatosIniciales();
                
                // Si estamos en la pestaña de gráficas, recargarlas
                if ($('#tab-graficas').hasClass('active')) {
                    cargarGraficasCompletas();
                }
                
                // Si estamos en la pestaña de detalle, recargar tabla
                if ($('#tab-detalle').hasClass('active')) {
                    cargarTablaDetalle();
                }
            });
            
            // Enter en fecha también aplica filtros
            $('#selectFecha').keypress(function(e) {
                if (e.which === 13) {
                    $('#btnAplicarFiltros').click();
                }
            });
            
            // Botones de comparativa
            $('.btn-comparativa').click(function() {
                const tipo = $(this).data('compara');
                
                $('.btn-comparativa').removeClass('active');
                $(this).addClass('active');
                
                cargarComparativa(tipo);
            });
            
            // Exportar
            $('#btnExportar').click(function() {
                exportarReporte();
            });
        }
        
        // Función para cargar comparativa
        function cargarComparativa(tipo) {
            const fecha = $('#selectFecha').val();
            const tipoEleccion = $('#selectTipo').val();
            const zona = $('#selectZona').val();
            
            $('#comparativasContenido').html(`
                <div class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p class="mt-2">Cargando comparativa...</p>
                </div>
            `);
            
            $.ajax({
                url: '../ajax/obtener_comparativa.php',
                type: 'POST',
                data: {
                    fecha: fecha,
                    tipo: tipo,
                    tipo_eleccion: tipoEleccion,
                    zona: zona
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        mostrarComparativa(response.data, tipo);
                    }
                }
            });
        }
// Función para mostrar comparativa
function mostrarComparativa(data, tipo) {
    let html = '';
    
    const labels = data.labels;
    const datosHoy = data.datos_hoy;
    const datosCompara = data.datos_compara;
    const totales = data.totales;
    
    if (tipo === 'ayer' || tipo === 'semana' || tipo === 'mes') {
        const labelTexto = tipo === 'ayer' ? 'Ayer' : tipo === 'semana' ? 'Semana Pasada' : 'Mes Pasado';
        
        html = `
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-calendar-day me-2"></i>
                                Comparativa: ${tipo === 'ayer' ? 'Hoy vs Ayer' : tipo === 'semana' ? 'Esta Semana vs Semana Pasada' : 'Este Mes vs Mes Pasado'}
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Resumen de totales -->
                            <div class="row mb-4">
                                <div class="col-md-4 mb-3">
                                    <div class="card ${totales.diferencias.total >= 0 ? 'border-success' : 'border-danger'}">
                                        <div class="card-body text-center">
                                            <h6 class="card-subtitle mb-2 text-muted">Total Referenciados</h6>
                                            <h2 class="card-title">${totales.hoy.total}</h2>
                                            <p class="card-text">
                                                <small class="${totales.diferencias.total >= 0 ? 'text-success' : 'text-danger'}">
                                                    <i class="fas fa-arrow-${totales.diferencias.total >= 0 ? 'up' : 'down'} me-1"></i>
                                                    ${totales.diferencias.total >= 0 ? '+' : ''}${totales.diferencias.total} 
                                                    (${totales.diferencias.porcentaje_total >= 0 ? '+' : ''}${totales.diferencias.porcentaje_total}%)
                                                </small>
                                                <br>
                                                <small class="text-muted">vs ${labelTexto}: ${totales.compara.total}</small>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <div class="card ${totales.diferencias.camara >= 0 ? 'border-primary' : 'border-danger'}">
                                        <div class="card-body text-center">
                                            <h6 class="card-subtitle mb-2 text-muted">Cámara</h6>
                                            <h2 class="card-title text-primary">${totales.hoy.camara}</h2>
                                            <p class="card-text">
                                                <small class="${totales.diferencias.camara >= 0 ? 'text-success' : 'text-danger'}">
                                                    <i class="fas fa-arrow-${totales.diferencias.camara >= 0 ? 'up' : 'down'} me-1"></i>
                                                    ${totales.diferencias.camara >= 0 ? '+' : ''}${totales.diferencias.camara} 
                                                    (${totales.diferencias.porcentaje_camara >= 0 ? '+' : ''}${totales.diferencias.porcentaje_camara}%)
                                                </small>
                                                <br>
                                                <small class="text-muted">vs ${labelTexto}: ${totales.compara.camara}</small>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <div class="card ${totales.diferencias.senado >= 0 ? 'border-success' : 'border-danger'}">
                                        <div class="card-body text-center">
                                            <h6 class="card-subtitle mb-2 text-muted">Senado</h6>
                                            <h2 class="card-title text-success">${totales.hoy.senado}</h2>
                                            <p class="card-text">
                                                <small class="${totales.diferencias.senado >= 0 ? 'text-success' : 'text-danger'}">
                                                    <i class="fas fa-arrow-${totales.diferencias.senado >= 0 ? 'up' : 'down'} me-1"></i>
                                                    ${totales.diferencias.senado >= 0 ? '+' : ''}${totales.diferencias.senado} 
                                                    (${totales.diferencias.porcentaje_senado >= 0 ? '+' : ''}${totales.diferencias.porcentaje_senado}%)
                                                </small>
                                                <br>
                                                <small class="text-muted">vs ${labelTexto}: ${totales.compara.senado}</small>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Gráfica de comparativa -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-chart-line me-2"></i>
                                        Gráfica Comparativa
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="grafica-body" style="height: 400px;">
                                        <canvas id="graficaComparativa"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('#comparativasContenido').html(html);
        
        // Crear gráfica de comparativa
        crearGraficaComparativa(labels, datosHoy, datosCompara, tipo);
    }
}

// Función para crear gráfica de comparativa
function crearGraficaComparativa(labels, datosHoy, datosCompara, tipo) {
    const ctx = document.getElementById('graficaComparativa').getContext('2d');
    
    // Preparar datos para gráfica
    const datosTotalHoy = datosHoy.map(dia => dia.total);
    const datosTotalCompara = datosCompara.map(dia => dia.total);
    
    // Destruir gráfica anterior si existe
    if (window.chartComparativa) {
        window.chartComparativa.destroy();
    }
    
    const labelActual = tipo === 'ayer' ? 'Hoy' : tipo === 'semana' ? 'Esta Semana' : 'Este Mes';
    const labelCompara = tipo === 'ayer' ? 'Ayer' : tipo === 'semana' ? 'Semana Pasada' : 'Mes Pasado';
    
    window.chartComparativa = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: labelActual,
                    data: datosTotalHoy,
                    borderColor: 'rgba(52, 152, 219, 1)',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    borderWidth: 3,
                    tension: 0.3,
                    fill: true
                },
                {
                    label: labelCompara,
                    data: datosTotalCompara,
                    borderColor: 'rgba(155, 89, 182, 1)',
                    backgroundColor: 'rgba(155, 89, 182, 0.1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true,
                    borderDash: [5, 5]
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Cantidad de Referenciados'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: tipo === 'ayer' ? 'Días' : tipo === 'semana' ? 'Días de la Semana' : 'Días del Mes'
                    }
                }
            }
        }
    });
}
        
        // Función para exportar reporte
        function exportarReporte() {
            const fecha = $('#selectFecha').val();
            const tipo = $('#selectTipo').val();
            const zona = $('#selectZona').val();
            
            // Crear URL de descarga
            const url = `../ajax/exportar_reporte.php?fecha=${fecha}&tipo=${tipo}&zona=${zona}`;
            
            // Descargar archivo
            window.open(url, '_blank');
        }
        
        // Función para mostrar error
        function mostrarError(mensaje) {
            // Puedes implementar un sistema de notificaciones más sofisticado
            alert('Error: ' + mensaje);
        }
        
        // Función para inicializar modal del sistema
        function inicializarModalSistema() {
            window.mostrarModalSistema = function() {
                const modal = new bootstrap.Modal(document.getElementById('modalSistema'));
                modal.show();
            };
        }
    </script>
</body>
</html>