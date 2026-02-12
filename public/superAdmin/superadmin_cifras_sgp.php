<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../models/ReferenciadoModel.php';
require_once __DIR__ . '/../../models/LlamadaModel.php';
require_once __DIR__ . '/../../models/Grupos_ParlamentariosModel.php';
require_once __DIR__ . '/../../helpers/navigation_helper.php';
require_once __DIR__ . '/../../models/SistemaModel.php';

// Verificar si el usuario está logueado y es SuperAdmin
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    header('Location: ../index.php');
    exit();
}

NavigationHelper::pushUrl();
$pdo = Database::getConnection();
$usuarioModel = new UsuarioModel($pdo);
$referenciadoModel = new ReferenciadoModel($pdo);
$llamadaModel = new LlamadaModel($pdo);
$gruposModel = new Grupos_ParlamentariosModel($pdo);
$sistemaModel = new SistemaModel($pdo);

// Obtener datos del usuario logueado
$usuario_logueado = $usuarioModel->getUsuarioById($_SESSION['id_usuario']);

// Obtener todas las estadísticas necesarias

// 1. ESTADÍSTICAS DE USUARIOS
$totalUsuarios = $usuarioModel->countUsuarios();
$usuariosActivos = $usuarioModel->countUsuariosActivos();
$administradores = $usuarioModel->countAdministradoresActivos();
$referenciadores = $usuarioModel->countReferenciadoresActivos();
$descargadores = $usuarioModel->countDescargadoresActivos();
$superAdmin = $usuarioModel->countSuperAdminActivos();
$trackeadores = $usuarioModel->countTrackingActivos();

// 2. ESTADÍSTICAS DE REFERENCIADOS
$totalTope = $usuarioModel->getTotalTope();
$referidosActivos = $referenciadoModel->countReferenciadosActivos();
$referidosInactivos = $referenciadoModel->countReferenciadosInactivos();
$totalReferidos = $referenciadoModel->countAllReferenciados();
$totalTrackeados = $referenciadoModel->getTotalTrackeados();
$porcentajeTracking = $usuarioModel->getPorcentajeTracking();
$porcentajeAfinidad = $referenciadoModel->getPorcentajeAfinidadPromedio();
$porcetajeEficienciaTracking = $llamadaModel->getEficienciaGeneralPorRating();

// 3. ESTADÍSTICAS DE ELECCIONES
$votosPorTipo = $referenciadoModel->getTotalPorTipoEleccion();
$totalCamara = $votosPorTipo['camara'];
$totalSenado = $votosPorTipo['senado'];
$totalAmbos = $votosPorTipo['ambos'];


// Calcular total Cámara (incluyendo ambos)
$totalCamaraConAmbos = $totalCamara + $totalAmbos;

// Calcular total Senado (incluyendo ambos)
$totalSenadoConAmbos = $totalSenado + $totalAmbos;

// Información del sistema
$licenciaInfo = $sistemaModel->getInfoCompletaLicencia();
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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cifras SGP - Panel Super Admin - SGP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/superadmin_cifras_sgp.css">
        <style>
        .cifra-header {
            background-color: #f8f9fa;
            font-weight: bold;
            padding: 15px;
            text-align: center;
            border-bottom: 2px solid #dee2e6;
            border-right: 1px solid #dee2e6;
        }
        
        .cifra-value {
            padding: 15px;
            text-align: center;
            border-bottom: 1px solid #dee2e6;
            border-right: 1px solid #dee2e6;
            font-size: 1.1em;
        }
        
        .cifra-value:last-child,
        .cifra-header:last-child {
            border-right: none;
        }
        
        .cifra-value.total {
            font-weight: bold;
            background-color: #e8f4fd;
        }
        
        .cifra-value.porcentaje {
            font-weight: bold;
            color: #198754;
        }
        
        .section-title {
            margin-top: 30px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #007bff;
            font-weight: bold;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        .footer-logo {
            max-height: 60px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .footer-logo:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="header-container">
            <div class="header-top">
                <div class="header-title">
                    <h1><i class="fas fa-calculator"></i> Cifras SGP - Super Admin</h1>
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
                <li class="breadcrumb-item active"><a href="superadmin_reportes.php"><i class="fas fa-database"></i> Reportes</a></li>
                <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-calculator"></i> Cifras SGP</li>
            </ol>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-title">
                <i class="fas fa-calculator fa-2x"></i>
                <div>
                    <h1>Cifras SGP</h1>
                    <div class="subtitle">Resumen cuantitativo de la estructura de datos operacional</div>
                </div>
            </div>
        </div>

                <!-- Estadísticas de Usuarios -->
        <h3 class="section-title">
            <i class="fas fa-users me-2"></i>Estadísticas de Usuarios
        </h3>
        
        <div class="table-container">
            <div class="cifras-grid-7">
                <!-- Headers -->
                <div class="cifra-header">Total Usuarios</div>
                <div class="cifra-header">Usuarios Activos</div>
                <div class="cifra-header">Administradores</div>
                <div class="cifra-header">Referenciadores</div>
                <div class="cifra-header">Descargadores</div>
                <div class="cifra-header">Super Admin</div>
                <div class="cifra-header">Trackeadores</div>
                
                <!-- Values -->
                <div class="cifra-value total"><?php echo number_format($totalUsuarios); ?></div>
                <div class="cifra-value"><?php echo number_format($usuariosActivos); ?></div>
                <div class="cifra-value"><?php echo number_format($administradores); ?></div>
                <div class="cifra-value"><?php echo number_format($referenciadores); ?></div>
                <div class="cifra-value"><?php echo number_format($descargadores); ?></div>
                <div class="cifra-value"><?php echo number_format($superAdmin); ?></div>
                <div class="cifra-value"><?php echo number_format($trackeadores); ?></div>
            </div>
        </div>

        <!-- Estadísticas de Referenciados -->
        <h3 class="section-title mt-5">
            <i class="fas fa-chart-line me-2"></i>Estadísticas de Referenciados
        </h3>
        
        <div class="table-container">
            <div class="cifras-grid-6">
                <!-- Headers -->
                <div class="cifra-header">Total tope</div>
                <div class="cifra-header">Referidos activos</div>
                <div class="cifra-header">Referidos de baja</div>
                <div class="cifra-header">Total referidos</div>
                <div class="cifra-header">Total trackeados</div>
                <div class="cifra-header">% tracking</div>
                
                <!-- Values -->
                <div class="cifra-value total"><?php echo number_format($totalTope); ?></div>
                <div class="cifra-value"><?php echo number_format($referidosActivos); ?></div>
                <div class="cifra-value"><?php echo number_format($referidosInactivos); ?></div>
                <div class="cifra-value total"><?php echo number_format($totalReferidos); ?></div>
                <div class="cifra-value"><?php echo number_format($totalTrackeados); ?></div>
                <div class="cifra-value porcentaje"><?php echo number_format($porcentajeTracking, 1); ?>%</div>
            </div>
        </div>

                <!-- Estadísticas de Elecciones -->
        <h3 class="section-title mt-5">
            <i class="fas fa-vote-yea me-2"></i>Estadísticas de Elecciones
        </h3>
        
        <div class="table-container">
            <div class="cifras-grid-5">
                <!-- Headers -->
                <div class="cifra-header">Total Cámara</div>
                <div class="cifra-header">Total Senado</div>
                <div class="cifra-header">Total Ambos</div>
                <div class="cifra-header">Cámara + Ambos</div>
                <div class="cifra-header">Senado + Ambos</div>
                
                <!-- Values -->
                <div class="cifra-value"><?php echo number_format($totalCamara); ?></div>
                <div class="cifra-value"><?php echo number_format($totalSenado); ?></div>
                <div class="cifra-value"><?php echo number_format($totalAmbos); ?></div>
                <div class="cifra-value total"><?php echo number_format($totalCamaraConAmbos); ?></div>
                <div class="cifra-value total"><?php echo number_format($totalSenadoConAmbos); ?></div>
            </div>
        </div>

        <!-- Resumen Ejecutivo -->
        <div class="card mt-5">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Resumen Ejecutivo</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5><i class="fas fa-chart-bar text-primary me-2"></i>Distribución de Usuarios</h5>
                        <ul class="list-group">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Referenciadores
                                <span class="badge bg-primary rounded-pill"><?php echo number_format($referenciadores); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Trackeadores
                                <span class="badge bg-success rounded-pill"><?php echo number_format($trackeadores); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Administradores
                                <span class="badge bg-warning rounded-pill"><?php echo number_format($administradores); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Usuarios Activos
                                <span class="badge bg-info rounded-pill"><?php echo number_format($usuariosActivos); ?> (<?php echo number_format(($usuariosActivos/$totalUsuarios)*100, 1); ?>%)</span>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h5><i class="fas fa-percentage text-success me-2"></i>Métricas Clave</h5>
                        <ul class="list-group">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Avance vs Tope
                                <span class="badge bg-primary rounded-pill"><?php echo number_format(($referidosActivos/$totalTope)*100, 1); ?>%</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Referidos Contactados
                                <span class="badge bg-success rounded-pill"><?php echo number_format($porcentajeTracking, 1); ?>%</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Afinidad Promedio
                                <span class="badge bg-warning rounded-pill"><?php echo number_format($porcentajeAfinidad, 1); ?>%</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Eficiencia Tracking
                                <span class="badge bg-info rounded-pill"><?php echo number_format($porcetajeEficienciaTracking, 1); ?>%</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información de Fecha -->
        <div class="alert alert-info mt-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-calendar-alt me-2"></i>
                    <strong>Última actualización:</strong> <?php echo date('d/m/Y H:i:s'); ?>
                </div>
                <div>
                    <strong>Total registros analizados:</strong> <?php echo number_format($totalReferidos); ?>
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
        <div class="container">
            <p>
                <strong>© 2026 Sistema de Gestión Política SGP.</strong> Puerto Gaitán - Meta<br>
                Módulo de SGA Sistema de Gestión Administrativa 2026 SGA Solución de Gestión Administrativa Enterprise Premium 1.0™ desarrollado por <strong>SISGONTech Technology®</strong>, Condominio Madeira Casa 19, Villavicencio, Colombia - Asesores e-Governance Solutions para Entidades Públicas 2026® SISGONTech<br>
                <strong>Propietario software:</strong> Ing. Rubén Darío González García - ☎️ (+57) 310 631 02 27 - Email: sisgonnet@gmail.com © Reservados todos los derechos de autor.
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../js/modal-sistema.js"></script>
    <script>
        // Función para actualizar logo según tema
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
        
        // Ejecutar al cargar y cuando cambie el tema
        document.addEventListener('DOMContentLoaded', function() {
            actualizarLogoSegunTema();
        });

        // Escuchar cambios en el tema del sistema
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
            actualizarLogoSegunTema();
        });

        // Función para mostrar modal del sistema
        function mostrarModalSistema() {
            const modal = new bootstrap.Modal(document.getElementById('modalSistema'));
            modal.show();
        }

        // Función para imprimir reporte
        function imprimirReporte() {
            window.print();
        }

        // Función para exportar a Excel
        function exportarExcel() {
            Swal.fire({
                title: 'Exportar a Excel',
                text: '¿Desea exportar las cifras a Excel?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, exportar',
                cancelButtonText: 'Cancelar',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    return new Promise((resolve) => {
                        // Simular descarga
                        setTimeout(() => {
                            resolve();
                        }, 1500);
                    });
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Exportación iniciada',
                        text: 'El archivo Excel se está descargando',
                        timer: 3000,
                        showConfirmButton: false
                    });
                }
            });
        }
    </script>
</body>
</html>