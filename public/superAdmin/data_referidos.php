<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../models/ReferenciadoModel.php';
require_once __DIR__ . '/../../models/SistemaModel.php';
require_once __DIR__ . '/../../helpers/navigation_helper.php';

// Verificar si el usuario está logueado y es SuperAdmin
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    header('Location: ../index.php');
    exit();
}

NavigationHelper::pushUrl();

$pdo = Database::getConnection();
$usuarioModel = new UsuarioModel($pdo);
$referenciadoModel = new ReferenciadoModel($pdo);
$sistemaModel = new SistemaModel($pdo);

// Obtener datos del usuario logueado
$usuario_logueado = $usuarioModel->getUsuarioById($_SESSION['id_usuario']);

// Obtener estadísticas iniciales (solo contadores, sin cargar todos los datos)
$totalReferidos = $referenciadoModel->getTotalReferenciados();
$totalActivos = $referenciadoModel->getTotalReferenciados(['activo' => true]);
$totalInactivos = $referenciadoModel->getTotalReferenciados(['activo' => false]);

// Obtener información del sistema
$infoSistema = $sistemaModel->getInformacionSistema();

// Obtener información completa de la licencia
$licenciaInfo = $sistemaModel->getInfoCompletaLicencia();

// Extraer valores
$infoSistema = $licenciaInfo['info'];
$diasRestantes = $licenciaInfo['dias_restantes'];
$validaHastaFormatted = $licenciaInfo['valida_hasta_formatted'];
$fechaInstalacionFormatted = $licenciaInfo['fecha_instalacion_formatted'];

// Calcular porcentaje RESTANTE
$porcentajeRestante = $sistemaModel->getPorcentajeRestanteLicencia();

// Color de la barra basado en lo que RESTA
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
    <title>Data Referidos - Super Admin - SGP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/data_referidos.css">
    <style>
        /* Mejoras para el diseño compacto */
        .input-group-sm {
            height: 35px;
        }

        .btn-group-sm .btn {
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
        }

        .search-container .form-control-sm {
            font-size: 0.875rem;
        }

        /* Asegurar que los select se vean bien */
        .form-select-sm {
            padding: 0.25rem 2.25rem 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        /* Para mejor alineación */
        .table-header {
            padding: 0.75rem 1rem;
            background-color: #f8f9fa;
            border-radius: 0.375rem 0.375rem 0 0;
            border-bottom: 1px solid #dee2e6;
        }

        /* Notificaciones */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            max-width: 500px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            animation: slideIn 0.3s ease;
            border-left: 4px solid;
        }

        .notification-success {
            border-left-color: #28a745;
            background-color: #d4edda;
            color: #155724;
        }

        .notification-error {
            border-left-color: #dc3545;
            background-color: #f8d7da;
            color: #721c24;
        }

        .notification-info {
            border-left-color: #17a2b8;
            background-color: #d1ecf1;
            color: #0c5460;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .notification-close {
            background: none;
            border: none;
            color: inherit;
            cursor: pointer;
            font-size: 1.2rem;
            opacity: 0.7;
            transition: opacity 0.2s;
        }

        .notification-close:hover {
            opacity: 1;
        }

        /* Texto elipsis para celdas largas */
        .text-ellipsis {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="header-container">
            <div class="header-top">
                <div class="header-title">
                    <h1><i class="fas fa-users"></i> Data Referidos - Super Admin</h1>
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
                <li class="breadcrumb-item"><a href="superadmin_datas.php"><i class="fas fa-database"></i> Datas</a></li>
                <li class="breadcrumb-item active"><i class="fas fa-users"></i> Data Referidos</li>
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
            <div class="dashboard-title">
                <i class="fas fa-users"></i>
                <span>Data de Referidos</span>
            </div>
            <div class="stats-summary">
                <div class="stat-item stat-total">
                    <span class="stat-number"><?php echo $totalReferidos; ?></span>
                    <span class="stat-label">Total Referidos</span>
                </div>
                <div class="stat-item stat-activos">
                    <span class="stat-number"><?php echo $totalActivos; ?></span>
                    <span class="stat-label">Referidos Activos</span>
                </div>
                <div class="stat-item stat-fecha">
                    <span class="stat-number"><?php echo date('d/m/Y'); ?></span>
                    <span class="stat-label">Fecha Actual</span>
                </div>
            </div>
        </div>
        
        <!-- Table Container -->
        <div class="table-container">
            <div class="table-header">
                <div class="table-title">
                    <i class="fas fa-table"></i>
                    <span>Listado de Referidos Registrados</span>
                    <?php if ($totalInactivos > 0): ?>
                        <small style="font-size: 0.9rem; color: #f39c12; margin-left: 10px;">
                            <i class="fas fa-info-circle"></i> <?php echo $totalInactivos; ?> referido(s) inactivo(s)
                        </small>
                    <?php endif; ?>
                </div>
                <div class="table-actions">
                    <button class="btn-export" data-bs-toggle="modal" data-bs-target="#exportModal">
                        <i class="fas fa-download"></i> Exportar
                    </button>
                </div>
            </div>
            
            <!-- BUSCADOR COMPACTO OPTIMIZADO -->
            <div class="search-container mb-3">
                <div class="row g-2 align-items-center">
                    <div class="col-md-8">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-primary text-white py-2">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" 
                                   class="form-control form-control-sm" 
                                   id="searchInput" 
                                   placeholder="Buscar por nombre, cédula, teléfono, etc."
                                   onkeyup="handleSearchInput(event)">
                            <button class="btn btn-outline-secondary btn-sm" type="button" onclick="clearSearch()" title="Limpiar búsqueda">
                                <i class="fas fa-times"></i>
                            </button>
                            <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#advancedFilters" 
                                    title="Filtros avanzados">
                                <i class="fas fa-filter"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center h-100">
                            <small class="text-muted me-2 d-none d-md-block">Estado:</small>
                            <div class="btn-group btn-group-sm w-100">
                                <button class="btn btn-primary btn-sm" onclick="filterByStatus('')">Todos</button>
                                <button class="btn btn-outline-success btn-sm" onclick="filterByStatus('1')">Activos</button>
                                <button class="btn btn-outline-warning btn-sm" onclick="filterByStatus('0')">Inactivos</button>
                            </div>
                        </div>
                    </div>
                </div>
                
<!-- Filtros avanzados (en collapse) -->
<div class="collapse mb-3" id="advancedFilters">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Filtros Avanzados</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <!-- Departamento -->
                <div class="col-md-3">
                    <label for="filterDepartamento" class="form-label">Departamento</label>
                    <select class="form-select" id="filterDepartamento" name="departamento">
                        <option value="">Todos los departamentos</option>
                    </select>
                </div>
                
                <!-- Municipio -->
                <div class="col-md-3">
                    <label for="filterMunicipio" class="form-label">Municipio</label>
                    <select class="form-select" id="filterMunicipio" name="municipio" disabled>
                        <option value="">Todos los municipios</option>
                    </select>
                </div>
                
                <!-- Zona -->
                <div class="col-md-3">
                    <label for="filterZona" class="form-label">Zona</label>
                    <select class="form-select" id="filterZona" name="zona">
                        <option value="">Todas las zonas</option>
                    </select>
                </div>
                
                <!-- Referenciador -->
                <div class="col-md-3">
                    <label for="filterReferenciador" class="form-label">Referenciador</label>
                    <select class="form-select" id="filterReferenciador" name="referenciador">
                        <option value="">Todos los referenciadores</option>
                    </select>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-md-12 text-end">
                    <button type="button" class="btn btn-secondary" onclick="clearAdvancedFilters()">
                        <i class="fas fa-times"></i> Limpiar filtros
                    </button>
                    <button type="button" class="btn btn-primary" onclick="applyAdvancedFilters()">
                        <i class="fas fa-filter"></i> Aplicar filtros
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
            </div>
            
            <div class="table-responsive">
                <table id="referidosTable" class="table table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th>Estado</th>
                            <th>Nombre</th>
                            <th>Apellido</th>
                            <th>Cédula</th>
                            <th>Dirección</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Afinidad</th>
                            <th>Zona</th>
                            <th>Sector</th>
                            <th>Puesto</th>
                            <th>Mesa</th>
                            <th>Departamento</th>
                            <th>Municipio</th>
                            <th>Oferta</th>
                            <th>Grupo</th>
                            <th>Grupo Parlamentario</th>
                            <th>Barrio</th>
                            <th>Referenciador</th>
                            <th>Fecha Registro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaBody">
                        <!-- Los datos se cargarán aquí por AJAX -->
                        <tr id="loadingRow">
                            <td colspan="21" class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                                <p class="mt-2">Cargando referenciados...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Controles de paginación -->
            <div class="pagination-container mt-3">
                <nav aria-label="Paginación de referenciados">
                    <ul class="pagination justify-content-center" id="paginationControls">
                        <!-- Los controles se generarán dinámicamente -->
                    </ul>
                </nav>
            </div>
        </div>
        
        <!-- Info Footer -->
        <div class="info-footer" id="infoFooter">
            <p>
                <i class="fas fa-info-circle"></i> 
                Cargando datos...
            </p>
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
                © Derechos de autor Reservados • <strong>Ing. Rubén Darío González García</strong> • Equipo de soporte • SISGONTech<br>
                Email: sisgonnet@gmail.com • Contacto: +57 3106310227 • Puerto Gaitán, Colombia • <?php echo date('Y'); ?>
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

    <!-- Modal de Exportación -->
    <div class="modal fade" id="exportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-download me-2"></i> Exportar Datos</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Seleccione el formato de exportación:</p>
                    <div class="d-grid gap-3">
                        <button class="btn btn-success btn-lg py-3" onclick="exportarReferidos('excel')">
                            <i class="fas fa-file-excel fa-lg me-2"></i> Exportar a Excel (.xls)
                        </button>
                        <button class="btn btn-primary btn-lg py-3" onclick="exportarReferidos('pdf')">
                            <i class="fas fa-file-pdf fa-lg me-2"></i> Exportar a PDF
                        </button>
                    </div>
                    <hr class="my-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="exportSoloActivos" style="transform: scale(1.3);">
                        <label class="form-check-label ms-2" for="exportSoloActivos">
                            <i class="fas fa-filter me-1"></i> Exportar solo referidos activos
                        </label>
                    </div>
                    <div class="mt-3 text-muted small">
                        <i class="fas fa-info-circle me-1"></i> El archivo se descargará automáticamente
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
    $(document).ready(function() {
        let currentPage = 1;
        const perPage = 50;
        let currentFilters = {};
        let searchTimeout = null;
        
        // Clave para sessionStorage
        const STORAGE_KEY = 'referidos_filters';
        
        // ============================================
        // FUNCIONES DE SESSIONSTORAGE
        // ============================================
        // ============================================
// FUNCIONES PARA FILTROS AVANZADOS
// ============================================

// Cargar opciones de filtros avanzados al iniciar
function cargarOpcionesFiltrosAvanzados() {
    $.ajax({
        url: '../ajax/get_referenciados.php',
        type: 'GET',
        data: { get_options: 'true' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Llenar departamentos
                var departamentoSelect = $('#filterDepartamento');
                departamentoSelect.html('<option value="">Todos los departamentos</option>');
                $.each(response.departamentos, function(index, departamento) {
                    departamentoSelect.append('<option value="' + departamento.id_departamento + '">' + departamento.nombre + '</option>');
                });
                
                // Llenar zonas
                var zonaSelect = $('#filterZona');
                zonaSelect.html('<option value="">Todas las zonas</option>');
                $.each(response.zonas, function(index, zona) {
                    zonaSelect.append('<option value="' + zona.id_zona + '">' + zona.nombre + '</option>');
                });
                
                // Llenar referenciadores
                var referenciadorSelect = $('#filterReferenciador');
                referenciadorSelect.html('<option value="">Todos los referenciadores</option>');
                $.each(response.referenciadores, function(index, referenciador) {
                    var nombreCompleto = referenciador.nombres + ' ' + referenciador.apellidos + ' - ' + (referenciador.cedula || '');
                    referenciadorSelect.append('<option value="' + referenciador.id_usuario + '">' + nombreCompleto + '</option>');
                });
                
                // Aplicar filtros guardados después de cargar las opciones
                aplicarFiltrosAvanzadosGuardados();
            } else {
                showNotification('Error al cargar opciones de filtros: ' + (response.error || 'Error desconocido'), 'error');
            }
        },
        error: function(xhr, status, error) {
            showNotification('Error al cargar opciones de filtros: ' + error, 'error');
        }
    });
}

// Función para cargar municipios según departamento
function cargarMunicipios(idDepartamento) {
    if (!idDepartamento) {
        $('#filterMunicipio').html('<option value="">Todos los municipios</option>');
        $('#filterMunicipio').prop('disabled', true);
        return;
    }
    
    $.ajax({
        url: '../ajax/get_referenciados.php',
        type: 'GET',
        data: { get_municipios: 'true', departamento: idDepartamento },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                var municipioSelect = $('#filterMunicipio');
                municipioSelect.html('<option value="">Todos los municipios</option>');
                $.each(response.municipios, function(index, municipio) {
                    municipioSelect.append('<option value="' + municipio.id_municipio + '">' + municipio.nombre + '</option>');
                });
                municipioSelect.prop('disabled', false);
                
                // Si hay municipio guardado en los filtros, seleccionarlo
                if (currentFilters.municipio) {
                    municipioSelect.val(currentFilters.municipio);
                }
            } else {
                showNotification('Error al cargar municipios: ' + (response.error || 'Error desconocido'), 'error');
            }
        },
        error: function(xhr, status, error) {
            showNotification('Error al cargar municipios: ' + error, 'error');
        }
    });
}

// Aplicar filtros avanzados guardados al UI
function aplicarFiltrosAvanzadosGuardados() {
    // Aplicar departamento si existe
    if (currentFilters.departamento) {
        $('#filterDepartamento').val(currentFilters.departamento);
        
        // Si hay departamento, cargar municipios
        if (currentFilters.municipio) {
            setTimeout(() => {
                cargarMunicipios(currentFilters.departamento);
                // Establecer el municipio después de cargar
                setTimeout(() => {
                    $('#filterMunicipio').val(currentFilters.municipio);
                }, 500);
            }, 300);
        } else {
            // Solo cargar municipios sin seleccionar
            cargarMunicipios(currentFilters.departamento);
        }
    }
    
    // Aplicar zona si existe
    if (currentFilters.zona) {
        $('#filterZona').val(currentFilters.zona);
    }
    
    // Aplicar referenciador si existe
    if (currentFilters.referenciador) {
        $('#filterReferenciador').val(currentFilters.referenciador);
    }
}

// Evento para cambio de departamento
$('#filterDepartamento').on('change', function() {
    var idDepartamento = $(this).val();
    cargarMunicipios(idDepartamento);
});

// ============================================
// FUNCIONES GLOBALES PARA FILTROS AVANZADOS
// ============================================

// Función para aplicar filtros avanzados
window.applyAdvancedFilters = function() {
    currentFilters.departamento = $('#filterDepartamento').val() || '';
    currentFilters.municipio = $('#filterMunicipio').val() || '';
    currentFilters.zona = $('#filterZona').val() || '';
    currentFilters.referenciador = $('#filterReferenciador').val() || '';
    
    // Guardar filtros
    saveFilters();
    
    // Cargar datos con los nuevos filtros
    loadReferenciados(1);
    
    // Cerrar el collapse de filtros avanzados
    var advancedFilters = bootstrap.Collapse.getInstance(document.getElementById('advancedFilters'));
    if (advancedFilters) {
        advancedFilters.hide();
    }
    
    showNotification('Filtros avanzados aplicados', 'success');
};

// Función para limpiar filtros avanzados
window.clearAdvancedFilters = function() {
    $('#filterDepartamento').val('');
    $('#filterMunicipio').html('<option value="">Todos los municipios</option>');
    $('#filterMunicipio').prop('disabled', true);
    $('#filterZona').val('');
    $('#filterReferenciador').val('');
    
    // Actualizar currentFilters
    delete currentFilters.departamento;
    delete currentFilters.municipio;
    delete currentFilters.zona;
    delete currentFilters.referenciador;
    
    // Guardar filtros
    saveFilters();
    
    // Cargar datos sin filtros avanzados
    loadReferenciados(1);
    
    showNotification('Filtros avanzados limpiados', 'info');
};
        // Guardar filtros en sessionStorage
        function saveFilters() {
            try {
                const filtersToSave = {
                    search: currentFilters.search || '',
                    activo: currentFilters.activo || '',
                    departamento: currentFilters.departamento || '',
                    municipio: currentFilters.municipio || '',
                    zona: currentFilters.zona || '',
                    referenciador: currentFilters.referenciador || '',
                    currentPage: currentPage || 1
                };
                sessionStorage.setItem(STORAGE_KEY, JSON.stringify(filtersToSave));
            } catch (e) {
                console.error('Error al guardar filtros:', e);
            }
        }
        
        // Cargar filtros desde sessionStorage
        function loadFilters() {
            try {
                const saved = sessionStorage.getItem(STORAGE_KEY);
                if (saved) {
                    const parsed = JSON.parse(saved);
                    
                    // Aplicar filtros al UI
                    if (parsed.search) {
                        $('#searchInput').val(parsed.search);
                        currentFilters.search = parsed.search;
                    }
                    
                    if (parsed.activo !== undefined && parsed.activo !== '') {
                        currentFilters.activo = parsed.activo;
                        updateFilterButtons();
                    }
                    
                    if (parsed.departamento) {
                        currentFilters.departamento = parsed.departamento;
                        $('#filterDepartamento').val(parsed.departamento);
                    }
                    
                    if (parsed.municipio) {
                        currentFilters.municipio = parsed.municipio;
                        $('#filterMunicipio').val(parsed.municipio);
                    }
                    
                    if (parsed.zona) {
                        currentFilters.zona = parsed.zona;
                        $('#filterZona').val(parsed.zona);
                    }
                    
                    if (parsed.referenciador) {
                        currentFilters.referenciador = parsed.referenciador;
                        $('#filterReferenciador').val(parsed.referenciador);
                    }
                    
                    if (parsed.currentPage) {
                        currentPage = parsed.currentPage;
                    }
                    
                    return true;
                }
            } catch (e) {
                console.error('Error al cargar filtros:', e);
                // Limpiar sessionStorage si hay error
                sessionStorage.removeItem(STORAGE_KEY);
            }
            return false;
        }
        
        // Limpiar todos los filtros y sessionStorage
function clearAllFiltersAndStorage() {
    currentFilters = {};
    sessionStorage.removeItem(STORAGE_KEY);
    
    // Limpiar UI
    $('#searchInput').val('');
    $('#filterDepartamento').val('');
    $('#filterMunicipio').html('<option value="">Todos los municipios</option>');
    $('#filterMunicipio').prop('disabled', true);
    $('#filterZona').val('');
    $('#filterReferenciador').val('');
    
    updateFilterButtons();
}
        
        // ============================================
        // FUNCIONES PRINCIPALES
        // ============================================
        
        // Función para cargar datos por AJAX
        function loadReferenciados(page = 1, useSavedPage = false) {
            if (useSavedPage) {
                page = currentPage;
            } else {
                currentPage = page;
            }
            
            // Guardar página actual
            saveFilters();
            
            // Mostrar loading
            $('#tablaBody').html(`
                <tr id="loadingRow">
                    <td colspan="21" class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2">Cargando referenciados...</p>
                    </td>
                </tr>
            `);
            
            // Construir URL con parámetros
            let url = `../ajax/get_referenciados.php?page=${page}&per_page=${perPage}`;
            
            if (currentFilters.search) {
                url += `&search=${encodeURIComponent(currentFilters.search)}`;
            }
            if (currentFilters.activo !== undefined && currentFilters.activo !== '') {
                url += `&activo=${currentFilters.activo}`;
            }
            
            // Agregar filtros avanzados si existen
            if (currentFilters.departamento) {
                url += `&departamento=${currentFilters.departamento}`;
            }
            if (currentFilters.municipio) {
                url += `&municipio=${currentFilters.municipio}`;
            }
            if (currentFilters.zona) {
                url += `&zona=${currentFilters.zona}`;
            }
            if (currentFilters.referenciador) {
                url += `&referenciador=${currentFilters.referenciador}`;
            }
            
            $.ajax({
                url: url,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        renderTable(response.data);
                        renderPagination(response.pagination);
                        updateStats(response.stats);
                        updateTotalInfo(response.stats, response.pagination);
                        
                        // Guardar filtros después de carga exitosa
                        saveFilters();
                    } else {
                        showNotification('Error al cargar datos: ' + (response.error || 'Error desconocido'), 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error AJAX:', error);
                    showNotification('Error de conexión al servidor', 'error');
                }
            });
        }
        
        // Función para renderizar la tabla
        function renderTable(data) {
            if (data.length === 0) {
                $('#tablaBody').html(`
                    <tr>
                        <td colspan="21" class="text-center">
                            <i class="fas fa-info-circle fa-2x text-muted mb-2"></i>
                            <p>No se encontraron referenciados</p>
                            ${currentFilters.search || currentFilters.activo !== undefined ? 
                                '<button class="btn btn-sm btn-primary mt-2" onclick="clearAllFilters()">Limpiar filtros</button>' : 
                                ''}
                        </td>
                    </tr>
                `);
                return;
            }
            
            let html = '';
            
            data.forEach(function(referenciado) {
                const estaActivo = (referenciado.activo === true || referenciado.activo === 't' || referenciado.activo == 1);
                const rowStyle = !estaActivo ? 'style="background-color: #f8f9fa; opacity: 0.8;"' : '';
                const nombreCompleto = escapeHtml(referenciado.nombre || '') + ' ' + escapeHtml(referenciado.apellido || '');
                
                // Resaltar texto de búsqueda si existe
                const searchTerm = currentFilters.search ? currentFilters.search.toLowerCase() : '';
                const highlight = (text) => {
                    if (!searchTerm || !text) return escapeHtml(text || '');
                    const escapedText = escapeHtml(text || '');
                    const regex = new RegExp(`(${searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
                    return escapedText.replace(regex, '<mark class="bg-warning">$1</mark>');
                };
                
                html += `
                <tr ${rowStyle}>
                    <td>
                        ${estaActivo ? 
                            '<span style="color: #27ae60; font-size: 0.8rem;"><i class="fas fa-check-circle"></i> Activo</span>' : 
                            '<span style="color: #e74c3c; font-size: 0.8rem;"><i class="fas fa-times-circle"></i> Inactivo</span>'}
                    </td>
                    <td>${highlight(referenciado.nombre)}</td>
                    <td>${highlight(referenciado.apellido)}</td>
                    <td>${highlight(referenciado.cedula)}</td>
                    <td class="text-ellipsis" title="${escapeHtml(referenciado.direccion || '')}">
                        ${highlight(referenciado.direccion)}
                    </td>
                    <td>${highlight(referenciado.email)}</td>
                    <td>${highlight(referenciado.telefono)}</td>
                    <td>
                        <div class="badge-affinidad badge-affinidad-${referenciado.afinidad || '1'}">
                            ${referenciado.afinidad || '0'}
                        </div>
                    </td>
                    <td>${highlight(referenciado.zona_nombre || 'N/A')}</td>
                    <td>${highlight(referenciado.sector_nombre || 'N/A')}</td>
                    <td>${highlight(referenciado.puesto_votacion_nombre || 'N/A')}</td>
                    <td>${highlight(referenciado.mesa || '')}</td>
                    <td>${highlight(referenciado.departamento_nombre || 'N/A')}</td>
                    <td>${highlight(referenciado.municipio_nombre || 'N/A')}</td>
                    <td>${highlight(referenciado.oferta_apoyo_nombre || 'N/A')}</td>
                    <td>${highlight(referenciado.grupo_poblacional_nombre || 'N/A')}</td>
                    <td>${highlight(referenciado.grupo_nombre || 'N/A')}</td>
                    <td>${highlight(referenciado.barrio_nombre || 'N/A')}</td>
                    <td>${highlight(referenciado.referenciador_nombre || 'N/A')}</td>
                    <td>${formatDate(referenciado.fecha_registro)}</td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-action btn-view" 
                                    title="Ver detalle del referido"
                                    onclick="verDetalleConFiltros(${referenciado.id_referenciado})">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn-action btn-edit" 
                                    title="Editar referido"
                                    onclick="editarReferenciadoConFiltros(${referenciado.id_referenciado})">
                                <i class="fas fa-edit"></i>
                            </button>
                            ${estaActivo ? 
                                `<button class="btn-action btn-deactivate" 
                                        title="Desactivar referido"
                                        onclick="desactivarReferenciado(${referenciado.id_referenciado}, '${nombreCompleto.replace(/'/g, "\\'")}', this)">
                                    <i class="fas fa-user-slash"></i>
                                </button>` :
                                `<button class="btn-action btn-activate" 
                                        title="Activar referido"
                                        onclick="reactivarReferenciado(${referenciado.id_referenciado}, '${nombreCompleto.replace(/'/g, "\\'")}', this)">
                                    <i class="fas fa-user-check"></i>
                                </button>`}
                        </div>
                    </td>
                </tr>`;
            });
            
            $('#tablaBody').html(html);
        }
        
        // Función para renderizar controles de paginación
        function renderPagination(pagination) {
            const totalPages = pagination.total_pages;
            const currentPage = pagination.current_page;
            
            if (totalPages <= 1) {
                $('#paginationControls').html('');
                return;
            }
            
            let html = '';
            
            // Botón anterior
            if (currentPage > 1) {
                html += `<li class="page-item">
                            <a class="page-link" href="#" onclick="return changePage(${currentPage - 1})">
                                <i class="fas fa-chevron-left"></i> Anterior
                            </a>
                         </li>`;
            } else {
                html += `<li class="page-item disabled">
                            <span class="page-link">
                                <i class="fas fa-chevron-left"></i> Anterior
                            </span>
                         </li>`;
            }
            
            // Números de página
            const maxPagesToShow = 5;
            let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
            let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);
            
            if (endPage - startPage + 1 < maxPagesToShow) {
                startPage = Math.max(1, endPage - maxPagesToShow + 1);
            }
            
            for (let i = startPage; i <= endPage; i++) {
                if (i === currentPage) {
                    html += `<li class="page-item active">
                                <span class="page-link">${i}</span>
                             </li>`;
                } else {
                    html += `<li class="page-item">
                                <a class="page-link" href="#" onclick="return changePage(${i})">${i}</a>
                             </li>`;
                }
            }
            
            // Botón siguiente
            if (currentPage < totalPages) {
                html += `<li class="page-item">
                            <a class="page-link" href="#" onclick="return changePage(${currentPage + 1})">
                                Siguiente <i class="fas fa-chevron-right"></i>
                            </a>
                         </li>`;
            } else {
                html += `<li class="page-item disabled">
                            <span class="page-link">
                                Siguiente <i class="fas fa-chevron-right"></i>
                            </span>
                         </li>`;
            }
            
            // Información de página
            html += `<li class="page-item disabled">
                        <span class="page-link">
                            Página ${currentPage} de ${totalPages}
                        </span>
                     </li>`;
            
            $('#paginationControls').html(html);
        }
        
        // Función para cambiar de página (global)
        window.changePage = function(page) {
            loadReferenciados(page);
            return false;
        };
        
        // Función para actualizar estadísticas
        function updateStats(stats) {
            $('.stat-total .stat-number').text(stats.total);
            $('.stat-activos .stat-number').text(stats.activos);
        }
        
        // Función para actualizar información total (mejorada)
function updateTotalInfo(stats, pagination) {
    const from = ((pagination.current_page - 1) * pagination.per_page) + 1;
    const to = Math.min(pagination.current_page * pagination.per_page, pagination.total);
    
    let filterInfo = '';
    if (currentFilters.search) {
        filterInfo += ` | Búsqueda: "${currentFilters.search}"`;
    }
    if (currentFilters.activo === '1') {
        filterInfo += ' | Solo activos';
    } else if (currentFilters.activo === '0') {
        filterInfo += ' | Solo inactivos';
    }
    
    // Agregar información de filtros avanzados
    if (currentFilters.departamento) {
        const deptoName = $('#filterDepartamento option:selected').text();
        filterInfo += ` | Departamento: ${deptoName}`;
    }
    if (currentFilters.municipio) {
        const muniName = $('#filterMunicipio option:selected').text();
        filterInfo += ` | Municipio: ${muniName}`;
    }
    if (currentFilters.zona) {
        const zonaName = $('#filterZona option:selected').text();
        filterInfo += ` | Zona: ${zonaName}`;
    }
    if (currentFilters.referenciador) {
        const refName = $('#filterReferenciador option:selected').text();
        filterInfo += ` | Referenciador: ${refName}`;
    }
    
    $('#infoFooter p').html(`
        <i class="fas fa-info-circle"></i> 
        Mostrando ${from} a ${to} de ${pagination.total} referidos 
        (${stats.activos} activos, ${stats.inactivos} inactivos)${filterInfo}
    `);
}
        
        // Funciones helper
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('es-ES', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        // ============================================
        // MANEJO DE FILTROS CON SESSIONSTORAGE
        // ============================================
        
        // Manejo del buscador en tiempo real
        window.handleSearchInput = function(event) {
            const searchTerm = event.target.value.trim();
            
            // Clear previous timeout
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }
            
            // Set new timeout (debounce de 500ms)
            searchTimeout = setTimeout(() => {
                currentFilters.search = searchTerm;
                saveFilters();
                loadReferenciados(1);
            }, 500);
            
            // Si presiona Enter, buscar inmediatamente
            if (event.key === 'Enter') {
                if (searchTimeout) clearTimeout(searchTimeout);
                currentFilters.search = searchTerm;
                saveFilters();
                loadReferenciados(1);
            }
        };
        
        // Limpiar búsqueda
        window.clearSearch = function() {
            $('#searchInput').val('');
            delete currentFilters.search;
            saveFilters();
            loadReferenciados(1);
            showNotification('Búsqueda limpiada', 'info');
        };
        
        // Limpiar todos los filtros
        window.clearAllFilters = function() {
            clearAllFiltersAndStorage();
            loadReferenciados(1);
            showNotification('Todos los filtros limpiados', 'info');
        };
        
        // Función para limpiar filtros avanzados
        window.clearAdvancedFilters = function() {
            $('#filterDepartamento').val('');
            $('#filterMunicipio').val('');
            $('#filterZona').val('');
            $('#filterReferenciador').val('');
            
            // Actualizar currentFilters
            delete currentFilters.departamento;
            delete currentFilters.municipio;
            delete currentFilters.zona;
            delete currentFilters.referenciador;
            
            saveFilters();
            loadReferenciados(1);
            
            showNotification('Filtros avanzados limpiados', 'info');
        };
        
        // Filtro por estado - actualizar botones activos
        function updateFilterButtons() {
            const activeStatus = currentFilters.activo !== undefined ? currentFilters.activo : '';
            
            $('.filter-status .btn').each(function() {
                const btn = $(this);
                const onclickAttr = btn.attr('onclick') || '';
                const match = onclickAttr.match(/filterByStatus\('([01]?)'\)/);
                
                if (match) {
                    const status = match[1];
                    
                    btn.removeClass('btn-primary btn-success btn-warning')
                       .removeClass('btn-outline-primary btn-outline-success btn-outline-warning');
                    
                    if (status === activeStatus) {
                        if (status === '1') {
                            btn.addClass('btn-success');
                        } else if (status === '0') {
                            btn.addClass('btn-warning');
                        } else {
                            btn.addClass('btn-primary');
                        }
                    } else {
                        if (status === '1') {
                            btn.addClass('btn-outline-success');
                        } else if (status === '0') {
                            btn.addClass('btn-outline-warning');
                        } else {
                            btn.addClass('btn-outline-primary');
                        }
                    }
                }
            });
        }
        
        window.filterByStatus = function(status) {
            currentFilters.activo = status;
            updateFilterButtons();
            saveFilters();
            loadReferenciados(1);
            return false;
        };
        
        // Filtros avanzados
        window.applyAdvancedFilters = function() {
            currentFilters.departamento = $('#filterDepartamento').val();
            currentFilters.municipio = $('#filterMunicipio').val();
            currentFilters.zona = $('#filterZona').val();
            currentFilters.referenciador = $('#filterReferenciador').val();
            
            saveFilters();
            loadReferenciados(1);
        };
        
        // ============================================
// INICIALIZACIÓN
// ============================================

// Cargar filtros guardados
const hasSavedFilters = loadFilters();

// Inicializar botones de filtro
updateFilterButtons();

// Cargar opciones de filtros avanzados
cargarOpcionesFiltrosAvanzados();

// Cargar primera página (con filtros guardados si existen)
if (hasSavedFilters) {
    loadReferenciados(currentPage, true);
} else {
    loadReferenciados(1);
}
        
        // Enfocar el input de búsqueda al cargar
        $('#searchInput').focus();
        
        // Guardar filtros antes de salir de la página
        $(window).on('beforeunload', function() {
            saveFilters();
        });
    });
    
    // ============================================
    // FUNCIONES GLOBALES MODIFICADAS
    // ============================================
    
    // Función para ver detalle manteniendo filtros
    function verDetalleConFiltros(id) {
        // Guardar filtros antes de navegar
        if (typeof saveFilters === 'function') {
            saveFilters();
        }
        window.location.href = 'ver_referenciado.php?id=' + id;
    }
    
    // Función para editar manteniendo filtros
    function editarReferenciadoConFiltros(id) {
        // Guardar filtros antes de navegar
        if (typeof saveFilters === 'function') {
            saveFilters();
        }
        window.location.href = 'editar_referenciador.php?id=' + id;
    }
    
    // Mantener compatibilidad con funciones anteriores
    window.verDetalle = verDetalleConFiltros;
    window.editarReferenciado = editarReferenciadoConFiltros;

    // Función para exportar referidos
    function exportarReferidos(formato) {
        const soloActivos = document.getElementById('exportSoloActivos').checked;
        
        let url = '';
        
        switch(formato) {
            case 'excel':
                url = 'exportar_referidos_excel.php';
                break;
            case 'pdf':
                url = 'exportar_referidos_pdf.php';
                break;
            default:
                url = 'exportar_referidos_excel.php';
                break;
        }
        
        if (soloActivos) {
            url += '?solo_activos=1';
        }
        
        const exportModal = bootstrap.Modal.getInstance(document.getElementById('exportModal'));
        if (exportModal) {
            exportModal.hide();
        }
        
        showNotification('Generando archivo ' + formato.toUpperCase() + '...', 'info');
        
        setTimeout(() => {
            const link = document.createElement('a');
            link.href = url;
            link.target = '_blank';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }, 300);
    }

    // Función para desactivar un referenciado
    async function desactivarReferenciado(idReferenciado, nombreReferenciado, button) {
        if (!confirm(`¿Está seguro de DESACTIVAR al referenciado "${nombreReferenciado}"?\n\nEl referenciado será marcado como inactivo, pero se mantendrá en el sistema.`)) {
            return;
        }
        
        const originalIcon = button.innerHTML;
        const originalClass = button.className;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.disabled = true;
        
        try {
            const response = await fetch('../ajax/referenciados.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `accion=desactivar&id_referenciado=${idReferenciado}`
            });
            
            const data = await response.json();
            
            if (data.success) {
                button.className = 'btn-action btn-activate';
                button.title = 'Activar referido';
                button.innerHTML = '<i class="fas fa-user-check"></i>';
                button.disabled = false;
                
                button.setAttribute('onclick', `reactivarReferenciado(${idReferenciado}, '${nombreReferenciado.replace(/'/g, "\\'")}', this)`);
                
                const row = button.closest('tr');
                row.style.backgroundColor = '#f8f9fa';
                row.style.opacity = '0.8';
                row.cells[0].innerHTML = '<span style="color: #e74c3c; font-size: 0.8rem;"><i class="fas fa-times-circle"></i> Inactivo</span>';
                
                showNotification('Referenciado desactivado correctamente', 'success');
                
                setTimeout(() => {
                    if (typeof loadReferenciados === 'function') {
                        loadReferenciados(currentPage || 1);
                    }
                }, 100);
            } else {
                showNotification('Error: ' + (data.message || 'No se pudo desactivar el referenciado'), 'error');
                button.innerHTML = originalIcon;
                button.className = originalClass;
                button.disabled = false;
            }
        } catch (error) {
            showNotification('Error de conexión: ' + error.message, 'error');
            button.innerHTML = originalIcon;
            button.className = originalClass;
            button.disabled = false;
        }
    }

    // Función para reactivar un referenciado
    async function reactivarReferenciado(idReferenciado, nombreReferenciado, button) {
        if (!confirm(`¿Desea REACTIVAR al referenciado "${nombreReferenciado}"?`)) {
            return;
        }
        
        const originalIcon = button.innerHTML;
        const originalClass = button.className;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.disabled = true;
        
        try {
            const response = await fetch('../ajax/referenciados.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `accion=reactivar&id_referenciado=${idReferenciado}`
            });
            
            const data = await response.json();
            
            if (data.success) {
                button.className = 'btn-action btn-deactivate';
                button.title = 'Desactivar referido';
                button.innerHTML = '<i class="fas fa-user-slash"></i>';
                button.disabled = false;
                
                button.setAttribute('onclick', `desactivarReferenciado(${idReferenciado}, '${nombreReferenciado.replace(/'/g, "\\'")}', this)`);
                
                const row = button.closest('tr');
                row.style.backgroundColor = '';
                row.style.opacity = '';
                row.cells[0].innerHTML = '<span style="color: #27ae60; font-size: 0.8rem;"><i class="fas fa-check-circle"></i> Activo</span>';
                
                showNotification('Referenciado reactivado correctamente', 'success');
                
                setTimeout(() => {
                    if (typeof loadReferenciados === 'function') {
                        loadReferenciados(currentPage || 1);
                    }
                }, 100);
            } else {
                showNotification('Error: ' + data.message, 'error');
                button.innerHTML = originalIcon;
                button.className = originalClass;
                button.disabled = false;
            }
        } catch (error) {
            showNotification('Error de conexión: ' + error.message, 'error');
            button.innerHTML = originalIcon;
            button.className = originalClass;
            button.disabled = false;
        }
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

    // Función para mostrar notificaciones
    function showNotification(message, type = 'info') {
        const oldNotification = document.querySelector('.notification');
        if (oldNotification) {
            oldNotification.remove();
        }
        
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            </div>
            <button class="notification-close">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        document.body.appendChild(notification);
        
        notification.querySelector('.notification-close').addEventListener('click', () => {
            notification.remove();
        });
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }

    // Ejecutar al cargar y cuando cambie el tema
    document.addEventListener('DOMContentLoaded', function() {
        actualizarLogoSegunTema();
    });

    // Escuchar cambios en el tema del sistema
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
        actualizarLogoSegunTema();
    });
    </script>
    
    <script src="../js/modal-sistema.js"></script>
    <script src="../js/contador.js"></script>
</body>
</html>