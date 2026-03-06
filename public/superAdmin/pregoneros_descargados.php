<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../models/PregoneroModel.php';
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
$pregoneroModel = new PregoneroModel($pdo);
$sistemaModel = new SistemaModel($pdo);

// Obtener datos del usuario logueado
$usuario_logueado = $usuarioModel->getUsuarioById($_SESSION['id_usuario']);

// Obtener estadísticas iniciales (solo contadores, sin cargar todos los datos)
$totalPregoneros = $pregoneroModel->getTotalPregoneros();
$totalActivos = $pregoneroModel->getTotalPregoneros(['activo' => true]);
$totalInactivos = $pregoneroModel->getTotalPregoneros(['activo' => false]);

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
    <title>Data Pregoneros - Super Admin - SGP</title>
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

        /* Badge para afinidad (si aplica) */
        .badge-affinidad {
            padding: 0.35rem 0.65rem;
            font-size: 0.85rem;
            font-weight: 600;
            border-radius: 0.375rem;
            display: inline-block;
            text-align: center;
            min-width: 30px;
        }

        .badge-affinidad-1 {
            background-color: #cff4fc;
            color: #055160;
        }

        .badge-affinidad-2 {
            background-color: #d1e7dd;
            color: #0a3622;
        }

        .badge-affinidad-3 {
            background-color: #fff3cd;
            color: #664d03;
        }

        /* Estilo para la columna de quien reporta */
        .quien-reporta-info {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .quien-reporta-info i {
            color: #3498db;
            font-size: 0.8rem;
        }
        
        .mismo-pregonero-badge {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 2px 6px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 3px;
            white-space: nowrap;
        }
        
        .mismo-pregonero-badge i {
            font-size: 0.65rem;
        }

        /* Estilo para referenciador */
        .referenciador-info {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }
        
        .referenciador-nombre {
            font-weight: 500;
        }
        
        .referenciador-cedula {
            font-size: 0.7rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="header-container">
            <div class="header-top">
                <div class="header-title">
                    <h1><i class="fas fa-bullhorn"></i> Data Pregoneros - Super Admin</h1>
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
                <li class="breadcrumb-item active"><i class="fas fa-bullhorn"></i> Data Pregoneros</li>
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
                <i class="fas fa-bullhorn"></i>
                <span>Data de Pregoneros</span>
            </div>
            <div class="stats-summary">
                <div class="stat-item stat-total">
                    <span class="stat-number"><?php echo $totalPregoneros; ?></span>
                    <span class="stat-label">Total Pregoneros</span>
                </div>
                <div class="stat-item stat-activos">
                    <span class="stat-number"><?php echo $totalActivos; ?></span>
                    <span class="stat-label">Pregoneros Activos</span>
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
                    <span>Listado de Pregoneros Registrados</span>
                    <?php if ($totalInactivos > 0): ?>
                        <small style="font-size: 0.9rem; color: #f39c12; margin-left: 10px;">
                            <i class="fas fa-info-circle"></i> <?php echo $totalInactivos; ?> pregonero(s) inactivo(s)
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
                                   placeholder="Buscar por nombres, apellidos, cédula, teléfono, barrio, comuna, quien reporta, referenciador, etc."
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
                                <!-- Zona -->
                                <div class="col-md-4">
                                    <label for="filterZona" class="form-label">Zona</label>
                                    <select class="form-select" id="filterZona" name="zona">
                                        <option value="">Todas las zonas</option>
                                    </select>
                                </div>
                                
                                <!-- Barrio -->
                                <div class="col-md-4">
                                    <label for="filterBarrio" class="form-label">Barrio</label>
                                    <select class="form-select" id="filterBarrio" name="barrio">
                                        <option value="">Todos los barrios</option>
                                    </select>
                                </div>
                                
                                <!-- Puesto de Votación -->
                                <div class="col-md-4">
                                    <label for="filterPuesto" class="form-label">Puesto de Votación</label>
                                    <select class="form-select" id="filterPuesto" name="puesto">
                                        <option value="">Todos los puestos</option>
                                    </select>
                                </div>
                                
                                <!-- Comuna -->
                                <div class="col-md-4">
                                    <label for="filterComuna" class="form-label">Comuna</label>
                                    <input type="text" class="form-control" id="filterComuna" name="comuna" placeholder="Ingrese comuna">
                                </div>
                                
                                <!-- Corregimiento -->
                                <div class="col-md-4">
                                    <label for="filterCorregimiento" class="form-label">Corregimiento</label>
                                    <input type="text" class="form-control" id="filterCorregimiento" name="corregimiento" placeholder="Ingrese corregimiento">
                                </div>
                                
                                <!-- Quien Reporta (NUEVO) -->
                                <div class="col-md-4">
                                    <label for="filterQuienReporta" class="form-label">Quien Reporta</label>
                                    <input type="text" class="form-control" id="filterQuienReporta" name="quien_reporta" placeholder="Nombre de quien reporta">
                                </div>
                                
                                <!-- Referenciador (NUEVO) -->
                                <div class="col-md-4">
                                    <label for="filterReferenciador" class="form-label">Referenciador</label>
                                    <select class="form-select" id="filterReferenciador" name="id_referenciador">
                                        <option value="">Todos los referenciadores</option>
                                    </select>
                                </div>
                                
                                <!-- Usuario que registró -->
                                <div class="col-md-4">
                                    <label for="filterUsuarioRegistro" class="form-label">Registrado por</label>
                                    <select class="form-select" id="filterUsuarioRegistro" name="usuario_registro">
                                        <option value="">Todos los usuarios</option>
                                    </select>
                                </div>
                                
                                <!-- Rango de fechas -->
                                <div class="col-md-6">
                                    <label for="filterFechaDesde" class="form-label">Fecha desde</label>
                                    <input type="date" class="form-control" id="filterFechaDesde" name="fecha_desde">
                                </div>
                                <div class="col-md-6">
                                    <label for="filterFechaHasta" class="form-label">Fecha hasta</label>
                                    <input type="date" class="form-control" id="filterFechaHasta" name="fecha_hasta">
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
                <table id="pregonerosTable" class="table table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th>Estado</th>
                            <th>Nombres</th>
                            <th>Apellidos</th>
                            <th>Identificación</th>
                            <th>Teléfono</th>
                            <th>Barrio</th>
                            <th>Corregimiento</th>
                            <th>Comuna</th>
                            <th>Zona</th>
                            <th>Sector</th>
                            <th>Puesto</th>
                            <th>Mesa</th>
                            <th>Quien Reporta</th> <!-- NUEVA COLUMNA -->
                            <th>Referenciador</th> <!-- NUEVA COLUMNA -->
                            <th>Registrado por</th>
                            <th>Fecha Registro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaBody">
                        <!-- Los datos se cargarán aquí por AJAX -->
                        <tr id="loadingRow">
                            <td colspan="17" class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                                <p class="mt-2">Cargando pregoneros...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Controles de paginación -->
            <div class="pagination-container mt-3">
                <nav aria-label="Paginación de pregoneros">
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
                        <button class="btn btn-success btn-lg py-3" onclick="exportarPregoneros('excel')">
                            <i class="fas fa-file-excel fa-lg me-2"></i> Exportar a Excel (.xls)
                        </button>
                        <button class="btn btn-primary btn-lg py-3" onclick="exportarPregoneros('pdf')">
                            <i class="fas fa-file-pdf fa-lg me-2"></i> Exportar a PDF
                        </button>
                    </div>
                    <hr class="my-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="exportSoloActivos" style="transform: scale(1.3);">
                        <label class="form-check-label ms-2" for="exportSoloActivos">
                            <i class="fas fa-filter me-1"></i> Exportar solo pregoneros activos
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
// ============================================
// VARIABLES GLOBALES
// ============================================
let currentPage = 1;
const perPage = 50;
let currentFilters = {};
let searchTimeout = null;
const STORAGE_KEY = 'pregoneros_filters';

$(document).ready(function() {
    // ============================================
    // FUNCIONES PARA FILTROS AVANZADOS
    // ============================================

    // Cargar opciones de filtros avanzados al iniciar
    function cargarOpcionesFiltrosAvanzados() {
        $.ajax({
            url: '../ajax/get_pregoneros_votaron.php',
            type: 'GET',
            data: { get_options: 'true' },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Llenar zonas
                    var zonaSelect = $('#filterZona');
                    zonaSelect.html('<option value="">Todas las zonas</option>');
                    $.each(response.zonas, function(index, zona) {
                        zonaSelect.append('<option value="' + zona.id_zona + '">' + zona.nombre + '</option>');
                    });
                    
                    // Llenar barrios
                    var barrioSelect = $('#filterBarrio');
                    barrioSelect.html('<option value="">Todos los barrios</option>');
                    $.each(response.barrios, function(index, barrio) {
                        barrioSelect.append('<option value="' + barrio.id_barrio + '">' + barrio.nombre + '</option>');
                    });
                    
                    // Llenar puestos de votación
                    var puestoSelect = $('#filterPuesto');
                    puestoSelect.html('<option value="">Todos los puestos</option>');
                    $.each(response.puestos, function(index, puesto) {
                        puestoSelect.append('<option value="' + puesto.id_puesto + '">' + puesto.nombre + '</option>');
                    });
                    
                    // Llenar referenciadores (NUEVO)
                    var referenciadorSelect = $('#filterReferenciador');
                    referenciadorSelect.html('<option value="">Todos los referenciadores</option>');
                    $.each(response.referenciadores || [], function(index, ref) {
                        var nombreCompleto = ref.nombres + ' ' + ref.apellidos + (ref.cedula ? ' - ' + ref.cedula : '');
                        referenciadorSelect.append('<option value="' + ref.id_usuario + '">' + nombreCompleto + '</option>');
                    });
                    
                    // Llenar usuarios que registraron
                    var usuarioSelect = $('#filterUsuarioRegistro');
                    usuarioSelect.html('<option value="">Todos los usuarios</option>');
                    $.each(response.usuarios_registro, function(index, usuario) {
                        var nombreCompleto = usuario.nombres + ' ' + usuario.apellidos + (usuario.cedula ? ' - ' + usuario.cedula : '');
                        usuarioSelect.append('<option value="' + usuario.id_usuario + '">' + nombreCompleto + '</option>');
                    });
                    
                    // Aplicar filtros guardados después de cargar las opciones
                    aplicarFiltrosAvanzadosGuardados();
                } else {
                    showNotification('Error al cargar opciones de filtros: ' + (response.error || 'Error desconocido'), 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al cargar opciones de filtros:', error);
                showNotification('Error al cargar opciones de filtros: ' + error, 'error');
            }
        });
    }

    // Aplicar filtros avanzados guardados al UI
    function aplicarFiltrosAvanzadosGuardados() {
        if (currentFilters.zona) {
            $('#filterZona').val(currentFilters.zona);
        }
        if (currentFilters.barrio) {
            $('#filterBarrio').val(currentFilters.barrio);
        }
        if (currentFilters.puesto) {
            $('#filterPuesto').val(currentFilters.puesto);
        }
        if (currentFilters.comuna) {
            $('#filterComuna').val(currentFilters.comuna);
        }
        if (currentFilters.corregimiento) {
            $('#filterCorregimiento').val(currentFilters.corregimiento);
        }
        if (currentFilters.quien_reporta) {
            $('#filterQuienReporta').val(currentFilters.quien_reporta);
        }
        if (currentFilters.id_referenciador) {
            $('#filterReferenciador').val(currentFilters.id_referenciador);
        }
        if (currentFilters.usuario_registro) {
            $('#filterUsuarioRegistro').val(currentFilters.usuario_registro);
        }
        if (currentFilters.fecha_desde) {
            $('#filterFechaDesde').val(currentFilters.fecha_desde);
        }
        if (currentFilters.fecha_hasta) {
            $('#filterFechaHasta').val(currentFilters.fecha_hasta);
        }
    }

    // ============================================
    // FUNCIONES GLOBALES PARA FILTROS AVANZADOS
    // ============================================

    // Función para aplicar filtros avanzados
    window.applyAdvancedFilters = function() {
        currentFilters.zona = $('#filterZona').val() || '';
        currentFilters.barrio = $('#filterBarrio').val() || '';
        currentFilters.puesto = $('#filterPuesto').val() || '';
        currentFilters.comuna = $('#filterComuna').val() || '';
        currentFilters.corregimiento = $('#filterCorregimiento').val() || '';
        currentFilters.quien_reporta = $('#filterQuienReporta').val() || '';
        currentFilters.id_referenciador = $('#filterReferenciador').val() || '';
        currentFilters.usuario_registro = $('#filterUsuarioRegistro').val() || '';
        currentFilters.fecha_desde = $('#filterFechaDesde').val() || '';
        currentFilters.fecha_hasta = $('#filterFechaHasta').val() || '';
        
        // Guardar filtros
        saveFilters();
        
        // Cargar datos con los nuevos filtros
        loadPregoneros(1);
        
        // Cerrar el collapse de filtros avanzados
        var advancedFilters = bootstrap.Collapse.getInstance(document.getElementById('advancedFilters'));
        if (advancedFilters) {
            advancedFilters.hide();
        }
        
        showNotification('Filtros avanzados aplicados', 'success');
    };

    // Función para limpiar filtros avanzados
    window.clearAdvancedFilters = function() {
        $('#filterZona').val('');
        $('#filterBarrio').val('');
        $('#filterPuesto').val('');
        $('#filterComuna').val('');
        $('#filterCorregimiento').val('');
        $('#filterQuienReporta').val('');
        $('#filterReferenciador').val('');
        $('#filterUsuarioRegistro').val('');
        $('#filterFechaDesde').val('');
        $('#filterFechaHasta').val('');
        
        // Actualizar currentFilters
        delete currentFilters.zona;
        delete currentFilters.barrio;
        delete currentFilters.puesto;
        delete currentFilters.comuna;
        delete currentFilters.corregimiento;
        delete currentFilters.quien_reporta;
        delete currentFilters.id_referenciador;
        delete currentFilters.usuario_registro;
        delete currentFilters.fecha_desde;
        delete currentFilters.fecha_hasta;
        
        // Guardar filtros
        saveFilters();
        
        // Cargar datos sin filtros avanzados
        loadPregoneros(1);
        
        showNotification('Filtros avanzados limpiados', 'info');
    };

    // ============================================
    // FUNCIONES DE SESSIONSTORAGE
    // ============================================
    
    // Guardar filtros en sessionStorage
    function saveFilters() {
        try {
            const filtersToSave = {
                search: currentFilters.search || '',
                activo: currentFilters.activo || '',
                zona: currentFilters.zona || '',
                barrio: currentFilters.barrio || '',
                puesto: currentFilters.puesto || '',
                comuna: currentFilters.comuna || '',
                corregimiento: currentFilters.corregimiento || '',
                quien_reporta: currentFilters.quien_reporta || '',
                id_referenciador: currentFilters.id_referenciador || '',
                usuario_registro: currentFilters.usuario_registro || '',
                fecha_desde: currentFilters.fecha_desde || '',
                fecha_hasta: currentFilters.fecha_hasta || '',
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
                
                if (parsed.zona) {
                    currentFilters.zona = parsed.zona;
                }
                if (parsed.barrio) {
                    currentFilters.barrio = parsed.barrio;
                }
                if (parsed.puesto) {
                    currentFilters.puesto = parsed.puesto;
                }
                if (parsed.comuna) {
                    currentFilters.comuna = parsed.comuna;
                }
                if (parsed.corregimiento) {
                    currentFilters.corregimiento = parsed.corregimiento;
                }
                if (parsed.quien_reporta) {
                    currentFilters.quien_reporta = parsed.quien_reporta;
                }
                if (parsed.id_referenciador) {
                    currentFilters.id_referenciador = parsed.id_referenciador;
                }
                if (parsed.usuario_registro) {
                    currentFilters.usuario_registro = parsed.usuario_registro;
                }
                if (parsed.fecha_desde) {
                    currentFilters.fecha_desde = parsed.fecha_desde;
                }
                if (parsed.fecha_hasta) {
                    currentFilters.fecha_hasta = parsed.fecha_hasta;
                }
                
                if (parsed.currentPage) {
                    currentPage = parsed.currentPage;
                }
                
                return true;
            }
        } catch (e) {
            console.error('Error al cargar filtros:', e);
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
        $('#filterZona').val('');
        $('#filterBarrio').val('');
        $('#filterPuesto').val('');
        $('#filterComuna').val('');
        $('#filterCorregimiento').val('');
        $('#filterQuienReporta').val('');
        $('#filterReferenciador').val('');
        $('#filterUsuarioRegistro').val('');
        $('#filterFechaDesde').val('');
        $('#filterFechaHasta').val('');
        
        updateFilterButtons();
    }
    
    // ============================================
    // FUNCIONES PRINCIPALES
    // ============================================
    
    // Función para cargar datos por AJAX
    function loadPregoneros(page = 1, useSavedPage = false) {
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
                <td colspan="17" class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2">Cargando pregoneros...</p>
                </td>
            </tr>
        `);
        
        // Construir URL con parámetros
        let url = `../ajax/get_pregoneros_votaron.php?page=${page}&per_page=${perPage}`;
        
        if (currentFilters.search) {
            url += `&search=${encodeURIComponent(currentFilters.search)}`;
        }
        if (currentFilters.activo !== undefined && currentFilters.activo !== '') {
            url += `&activo=${currentFilters.activo}`;
        }
        
        // Agregar filtros avanzados si existen
        if (currentFilters.zona) {
            url += `&zona=${currentFilters.zona}`;
        }
        if (currentFilters.barrio) {
            url += `&barrio=${currentFilters.barrio}`;
        }
        if (currentFilters.puesto) {
            url += `&puesto=${currentFilters.puesto}`;
        }
        if (currentFilters.comuna) {
            url += `&comuna=${encodeURIComponent(currentFilters.comuna)}`;
        }
        if (currentFilters.corregimiento) {
            url += `&corregimiento=${encodeURIComponent(currentFilters.corregimiento)}`;
        }
        if (currentFilters.quien_reporta) {
            url += `&quien_reporta=${encodeURIComponent(currentFilters.quien_reporta)}`;
        }
        if (currentFilters.id_referenciador) {
            url += `&id_referenciador=${currentFilters.id_referenciador}`;
        }
        if (currentFilters.usuario_registro) {
            url += `&usuario_registro=${currentFilters.usuario_registro}`;
        }
        if (currentFilters.fecha_desde) {
            url += `&fecha_desde=${currentFilters.fecha_desde}`;
        }
        if (currentFilters.fecha_hasta) {
            url += `&fecha_hasta=${currentFilters.fecha_hasta}`;
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
                $('#tablaBody').html(`
                    <tr>
                        <td colspan="17" class="text-center text-danger">
                            <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
                            <p>Error al cargar los datos. Por favor, intente de nuevo.</p>
                        </td>
                    </tr>
                `);
            }
        });
    }
    
    // Función para renderizar la tabla
    function renderTable(data) {
        if (data.length === 0) {
            $('#tablaBody').html(`
                <tr>
                    <td colspan="17" class="text-center">
                        <i class="fas fa-info-circle fa-2x text-muted mb-2"></i>
                        <p>No se encontraron pregoneros</p>
                        ${currentFilters.search || currentFilters.activo !== undefined || Object.keys(currentFilters).length > 2 ? 
                            '<button class="btn btn-sm btn-primary mt-2" onclick="clearAllFilters()">Limpiar filtros</button>' : 
                            ''}
                    </td>
                </tr>
            `);
            return;
        }
        
        let html = '';
        
        data.forEach(function(pregonero) {
            const estaActivo = (pregonero.activo === true || pregonero.activo === 't' || pregonero.activo == 1);
            const rowStyle = !estaActivo ? 'style="background-color: #f8f9fa; opacity: 0.8;"' : '';
            const nombreCompleto = escapeHtml(pregonero.nombres || '') + ' ' + escapeHtml(pregonero.apellidos || '');
            
            // Resaltar texto de búsqueda si existe
            const searchTerm = currentFilters.search ? currentFilters.search.toLowerCase() : '';
            const highlight = (text) => {
                if (!searchTerm || !text) return escapeHtml(text || '');
                const escapedText = escapeHtml(text || '');
                const regex = new RegExp(`(${searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
                return escapedText.replace(regex, '<mark class="bg-warning">$1</mark>');
            };
            
            // Determinar si quien reporta es el mismo pregonero
            const quienReporta = pregonero.quien_reporta || '';
            const esMismoPregonero = quienReporta && 
                (quienReporta.toLowerCase() === (pregonero.nombres + ' ' + pregonero.apellidos).toLowerCase() ||
                 quienReporta.toLowerCase() === (pregonero.nombres + ' ' + pregonero.apellidos).toLowerCase().trim());
            
            // Formatear quien reporta con badge si es el mismo
            let quienReportaHtml = '<span class="text-muted">-</span>';
            if (quienReporta) {
                quienReportaHtml = `<div class="quien-reporta-info">
                    ${esMismoPregonero ? 
                        '<span class="mismo-pregonero-badge" title="Es el mismo pregonero"><i class="fas fa-user-check"></i> Mismo</span>' : 
                        ''}
                    <span>${highlight(quienReporta)}</span>
                </div>`;
            }
            
            // Formatear referenciador
            let referenciadorHtml = '<span class="text-muted">-</span>';
            if (pregonero.referenciador_nombre) {
                referenciadorHtml = `<div class="referenciador-info">
                    <span class="referenciador-nombre">${highlight(pregonero.referenciador_nombre)}</span>
                    ${pregonero.referenciador_cedula ? 
                        `<span class="referenciador-cedula">${highlight(pregonero.referenciador_cedula)}</span>` : 
                        ''}
                </div>`;
            }
            
            html += `
            <tr ${rowStyle}>
                <td>
                    ${estaActivo ? 
                        '<span style="color: #27ae60; font-size: 0.8rem;"><i class="fas fa-check-circle"></i> Activo</span>' : 
                        '<span style="color: #e74c3c; font-size: 0.8rem;"><i class="fas fa-times-circle"></i> Inactivo</span>'}
                </td>
                <td>${highlight(pregonero.nombres)}</td>
                <td>${highlight(pregonero.apellidos)}</td>
                <td>${highlight(pregonero.identificacion)}</td>
                <td>${highlight(pregonero.telefono)}</td>
                <td>${highlight(pregonero.barrio_nombre || 'N/A')}</td>
                <td>${highlight(pregonero.corregimiento || 'N/A')}</td>
                <td>${highlight(pregonero.comuna || 'N/A')}</td>
                <td>${highlight(pregonero.zona_nombre || 'N/A')}</td>
                <td>${highlight(pregonero.sector_nombre || 'N/A')}</td>
                <td>${highlight(pregonero.puesto_nombre || 'N/A')}</td>
                <td>${highlight(pregonero.mesa || '')}</td>
                <td>${quienReportaHtml}</td>
                <td>${referenciadorHtml}</td>
                <td>${highlight(pregonero.usuario_registro_nombre || 'N/A')}</td>
                <td>${formatDate(pregonero.fecha_registro)}</td>
                <td>
                    <div class="action-buttons">
                        <button class="btn-action btn-view" 
                                title="Ver detalle del pregonero"
                                onclick="verDetalleConFiltros(${pregonero.id_pregonero})">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn-action btn-edit" 
                                title="Editar pregonero"
                                onclick="editarPregoneroConFiltros(${pregonero.id_pregonero})">
                            <i class="fas fa-edit"></i>
                        </button>
                        ${estaActivo ? 
                            `<button class="btn-action btn-deactivate" 
                                    title="Desactivar pregonero"
                                    onclick="desactivarPregonero(${pregonero.id_pregonero}, '${nombreCompleto.replace(/'/g, "\\'")}', this)">
                                <i class="fas fa-user-slash"></i>
                            </button>` :
                            `<button class="btn-action btn-activate" 
                                    title="Activar pregonero"
                                    onclick="reactivarPregonero(${pregonero.id_pregonero}, '${nombreCompleto.replace(/'/g, "\\'")}', this)">
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
        loadPregoneros(page);
        return false;
    };
    
    // Función para actualizar estadísticas
    function updateStats(stats) {
        $('.stat-total .stat-number').text(stats.total);
        $('.stat-activos .stat-number').text(stats.activos);
    }
    
    // Función para actualizar información total
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
        if (currentFilters.zona) {
            const zonaName = $('#filterZona option:selected').text();
            filterInfo += ` | Zona: ${zonaName}`;
        }
        if (currentFilters.barrio) {
            const barrioName = $('#filterBarrio option:selected').text();
            filterInfo += ` | Barrio: ${barrioName}`;
        }
        if (currentFilters.puesto) {
            const puestoName = $('#filterPuesto option:selected').text();
            filterInfo += ` | Puesto: ${puestoName}`;
        }
        if (currentFilters.comuna) {
            filterInfo += ` | Comuna: ${currentFilters.comuna}`;
        }
        if (currentFilters.corregimiento) {
            filterInfo += ` | Corregimiento: ${currentFilters.corregimiento}`;
        }
        if (currentFilters.quien_reporta) {
            filterInfo += ` | Quien reporta: ${currentFilters.quien_reporta}`;
        }
        if (currentFilters.id_referenciador) {
            const referenciadorName = $('#filterReferenciador option:selected').text();
            filterInfo += ` | Referenciador: ${referenciadorName}`;
        }
        if (currentFilters.usuario_registro) {
            const usuarioName = $('#filterUsuarioRegistro option:selected').text();
            filterInfo += ` | Registrado por: ${usuarioName}`;
        }
        if (currentFilters.fecha_desde) {
            filterInfo += ` | Desde: ${currentFilters.fecha_desde}`;
        }
        if (currentFilters.fecha_hasta) {
            filterInfo += ` | Hasta: ${currentFilters.fecha_hasta}`;
        }
        
        $('#infoFooter p').html(`
            <i class="fas fa-info-circle"></i> 
            Mostrando ${from} a ${to} de ${pagination.total} pregoneros 
            (${stats.activos} activos, ${stats.inactivos} inactivos)${filterInfo}
        `);
    }
    
    // Funciones helper
    function escapeHtml(text) {
        if (!text) return '';
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
            loadPregoneros(1);
        }, 500);
        
        // Si presiona Enter, buscar inmediatamente
        if (event.key === 'Enter') {
            if (searchTimeout) clearTimeout(searchTimeout);
            currentFilters.search = searchTerm;
            saveFilters();
            loadPregoneros(1);
        }
    };
    
    // Limpiar búsqueda
    window.clearSearch = function() {
        $('#searchInput').val('');
        delete currentFilters.search;
        saveFilters();
        loadPregoneros(1);
        showNotification('Búsqueda limpiada', 'info');
    };
    
    // Limpiar todos los filtros
    window.clearAllFilters = function() {
        clearAllFiltersAndStorage();
        loadPregoneros(1);
        showNotification('Todos los filtros limpiados', 'info');
    };
    
    // Filtro por estado - actualizar botones activos
    function updateFilterButtons() {
        const activeStatus = currentFilters.activo !== undefined ? currentFilters.activo : '';
        
        $('.btn-group .btn').each(function() {
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
        loadPregoneros(1);
        return false;
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
        // Aplicar filtros avanzados guardados después de cargar opciones
        setTimeout(() => {
            aplicarFiltrosAvanzadosGuardados();
        }, 300);
        loadPregoneros(currentPage, true);
    } else {
        loadPregoneros(1);
    }
    
    // Enfocar el input de búsqueda al cargar
    $('#searchInput').focus();
    
    // Guardar filtros antes de salir de la página
    $(window).on('beforeunload', function() {
        saveFilters();
    });
});

// ============================================
// FUNCIONES GLOBALES - DECLARADAS FUERA DE document.ready
// ============================================

// Función para ver detalle manteniendo filtros
function verDetalleConFiltros(id) {
    // Guardar filtros antes de navegar
    if (typeof saveFilters === 'function') {
        saveFilters();
    }
    window.location.href = 'ver_pregonero.php?id=' + id;
}

// Función para editar manteniendo filtros
function editarPregoneroConFiltros(id) {
    // Guardar filtros antes de navegar
    if (typeof saveFilters === 'function') {
        saveFilters();
    }
    window.location.href = 'editar_pregonero.php?id=' + id;
}

// Función para desactivar un pregonero
async function desactivarPregonero(idPregonero, nombrePregonero, button) {
    if (!confirm(`¿Está seguro de DESACTIVAR al pregonero "${nombrePregonero}"?\n\nEl pregonero será marcado como inactivo, pero se mantendrá en el sistema.`)) {
        return;
    }
    
    const originalIcon = button.innerHTML;
    const originalClass = button.className;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    button.disabled = true;
    
    try {
        const response = await fetch('../ajax/pregoneros_acciones.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `accion=desactivar&id_pregonero=${idPregonero}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            button.className = 'btn-action btn-activate';
            button.title = 'Activar pregonero';
            button.innerHTML = '<i class="fas fa-user-check"></i>';
            button.disabled = false;
            
            button.setAttribute('onclick', `reactivarPregonero(${idPregonero}, '${nombrePregonero.replace(/'/g, "\\'")}', this)`);
            
            const row = button.closest('tr');
            row.style.backgroundColor = '#f8f9fa';
            row.style.opacity = '0.8';
            row.cells[0].innerHTML = '<span style="color: #e74c3c; font-size: 0.8rem;"><i class="fas fa-times-circle"></i> Inactivo</span>';
            
            showNotification('Pregonero desactivado correctamente', 'success');
            
            setTimeout(() => {
                if (typeof loadPregoneros === 'function') {
                    loadPregoneros(currentPage || 1);
                }
            }, 100);
        } else {
            showNotification('Error: ' + (data.message || 'No se pudo desactivar el pregonero'), 'error');
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

// Función para reactivar un pregonero
async function reactivarPregonero(idPregonero, nombrePregonero, button) {
    if (!confirm(`¿Desea REACTIVAR al pregonero "${nombrePregonero}"?`)) {
        return;
    }
    
    const originalIcon = button.innerHTML;
    const originalClass = button.className;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    button.disabled = true;
    
    try {
        const response = await fetch('../ajax/pregoneros_acciones.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `accion=reactivar&id_pregonero=${idPregonero}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            button.className = 'btn-action btn-deactivate';
            button.title = 'Desactivar pregonero';
            button.innerHTML = '<i class="fas fa-user-slash"></i>';
            button.disabled = false;
            
            button.setAttribute('onclick', `desactivarPregonero(${idPregonero}, '${nombrePregonero.replace(/'/g, "\\'")}', this)`);
            
            const row = button.closest('tr');
            row.style.backgroundColor = '';
            row.style.opacity = '';
            row.cells[0].innerHTML = '<span style="color: #27ae60; font-size: 0.8rem;"><i class="fas fa-check-circle"></i> Activo</span>';
            
            showNotification('Pregonero reactivado correctamente', 'success');
            
            setTimeout(() => {
                if (typeof loadPregoneros === 'function') {
                    loadPregoneros(currentPage || 1);
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

// Función de exportación
function exportarPregoneros(formato) {
    const soloActivos = document.getElementById('exportSoloActivos')?.checked || false;
    
    // Construir URL con TODOS los filtros actuales
    let params = new URLSearchParams();
    
    // Filtro de búsqueda
    const searchTerm = document.getElementById('searchInput')?.value.trim();
    if (searchTerm) {
        params.append('search', searchTerm);
    }
    
    // Filtro por estado (si no está usando el checkbox de solo activos)
    if (!soloActivos) {
        const estadoFiltro = currentFilters.activo;
        if (estadoFiltro !== undefined && estadoFiltro !== '') {
            params.append('activo', estadoFiltro);
        }
    }
    
    // Checkbox de solo activos (tiene prioridad)
    if (soloActivos) {
        params.append('solo_activos', '1');
    }
    
    // Agregar TODOS los filtros avanzados
    if (currentFilters.zona) {
        params.append('zona', currentFilters.zona);
    }
    if (currentFilters.barrio) {
        params.append('barrio', currentFilters.barrio);
    }
    if (currentFilters.puesto) {
        params.append('puesto', currentFilters.puesto);
    }
    if (currentFilters.comuna) {
        params.append('comuna', currentFilters.comuna);
    }
    if (currentFilters.corregimiento) {
        params.append('corregimiento', currentFilters.corregimiento);
    }
    if (currentFilters.quien_reporta) {
        params.append('quien_reporta', currentFilters.quien_reporta);
    }
    if (currentFilters.id_referenciador) {
        params.append('id_referenciador', currentFilters.id_referenciador);
    }
    if (currentFilters.usuario_registro) {
        params.append('usuario_registro', currentFilters.usuario_registro);
    }
    if (currentFilters.fecha_desde) {
        params.append('fecha_desde', currentFilters.fecha_desde);
    }
    if (currentFilters.fecha_hasta) {
        params.append('fecha_hasta', currentFilters.fecha_hasta);
    }
    
    // Determinar URL según formato
    let url = '';
    switch(formato) {
        case 'excel':
            url = 'exportar_pregoneros_excel.php?' + params.toString();
            break;
        case 'pdf':
            url = 'exportar_pregoneros_pdf.php?' + params.toString();
            break;
        default:
            url = 'exportar_pregoneros_excel.php?' + params.toString();
            break;
    }
    
    // Cerrar modal
    const exportModal = bootstrap.Modal.getInstance(document.getElementById('exportModal'));
    if (exportModal) {
        exportModal.hide();
    }
    
    // Mostrar mensaje con los filtros aplicados
    let mensaje = 'Generando archivo ' + formato.toUpperCase();
    if (params.toString()) {
        mensaje += ' con los filtros aplicados';
    }
    showNotification(mensaje + '...', 'info');
    
    // Descargar archivo
    setTimeout(() => {
        const link = document.createElement('a');
        link.href = url;
        link.target = '_blank';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        showNotification('Archivo ' + formato.toUpperCase() + ' generado correctamente', 'success');
    }, 300);
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