<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../models/SistemaModel.php';

// Verificar si el usuario está logueado y es SuperAdmin
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    header('Location: ../index.php');
    exit();
}

$pdo = Database::getConnection();
$usuarioModel = new UsuarioModel($pdo);
$sistemaModel = new SistemaModel($pdo);

// Obtener datos del usuario logueado
$usuario_logueado = $usuarioModel->getUsuarioById($_SESSION['id_usuario']);

// Obtener información completa de la licencia
$licenciaInfo = $sistemaModel->getInfoCompletaLicencia();

// Extraer valores
$infoSistema = $licenciaInfo['info'];
$diasRestantes = $licenciaInfo['dias_restantes'];
$validaHastaFormatted = $licenciaInfo['valida_hasta_formatted'];
$fechaInstalacionFormatted = $licenciaInfo['fecha_instalacion_formatted'];

// Calcular porcentaje RESTANTE
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
    <title>Rendimiento Diario de Votos - Super Admin - SGP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/superadmin_analisis_ia.css">
    <style>
        /* Reducir tamaño de los botones */
        .dashboard-grid-two {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .dashboard-option {
            padding: 20px;
            min-height: 250px;
        }
        
        .option-icon-wrapper {
            width: 70px;
            height: 70px;
            margin: 0 auto 15px;
        }
        
        .option-icon {
            width: 70px;
            height: 70px;
            font-size: 30px;
        }
        
        .option-title {
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .option-description {
            font-size: 13px;
            margin-bottom: 15px;
            min-height: 70px;
        }
        
        .option-features {
            margin-top: 15px;
        }
        
        .feature-badge {
            padding: 4px 8px;
            font-size: 11px;
            margin: 0 3px 6px 0;
        }
        
        .access-indicator {
            font-size: 12px;
            padding: 6px 12px;
            margin-bottom: 15px;
        }
        
        @media (max-width: 768px) {
            .dashboard-grid-two {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .dashboard-option {
                min-height: 230px;
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
                    <h1><i class="fas fa-chart-line"></i> Rendimiento Diario de Votos</h1>
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
                <li class="breadcrumb-item active"><i class="fas fa-chart-bar"></i> Rendimiento Votos</li>
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
                <i class="fas fa-calendar-day"></i>
                <span>Análisis de Rendimiento Diario</span>
            </div>
            <p class="dashboard-subtitle">
                Visualice el rendimiento diario de votos registrados en el sistema. 
                Análisis detallado por fecha, zona y tipo de elección.
            </p>
        </div>
        
        <!-- BOTONES EN DOS COLUMNAS - TAMAÑO REDUCIDO -->
        <div class="dashboard-grid-two">
            <!-- RENDIMIENTO DIARIO DE VOTOS -->
            <a href="superadmin_reporte_votos.php" class="dashboard-option option-rendimiento">
                <div class="access-indicator">
                    <i class="fas fa-chart-line"></i> VER REPORTE
                </div>
                <div class="option-icon-wrapper">
                    <div class="option-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
                <div class="option-title">ANÁLISIS DIARIO DE CARGUE</div>
                <div class="option-description">
                    Análisis detallado del rendimiento diario de votos registrados en el sistema.
                </div>
                <div class="option-features">
                    <div class="feature-badge">
                        <i class="fas fa-chart-bar"></i> Gráficas
                    </div>
                    <div class="feature-badge">
                        <i class="fas fa-calendar-alt"></i> Fechas
                    </div>
                    <div class="feature-badge">
                        <i class="fas fa-trend-up"></i> Tendencias
                    </div>
                </div>
            </a>
            
            <!-- TRACKING DIARIO - NUEVO BOTÓN -->
            <a href="superadmin_reporte_tracking.php" class="dashboard-option option-rendimiento">
                <div class="access-indicator">
                    <i class="fas fa-map-marked-alt"></i> VER TRACKING
                </div>
                <div class="option-icon-wrapper">
                    <div class="option-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                </div>
                <div class="option-title">TRACKING DIARIO</div>
                <div class="option-description">
                    Sistema de seguimiento y monitoreo en tiempo real de llamadas diarias.
                </div>
                <div class="option-features">
                    <div class="feature-badge">
                        <i class="fas fa-map-marker-alt"></i> Llamadas
                    </div>
                    <div class="feature-badge">
                        <i class="fas fa-clock"></i> Tiempo real
                    </div>
                </div>
            </a>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const options = document.querySelectorAll('.dashboard-option');
            
            options.forEach(option => {
                option.addEventListener('mouseenter', function() {
                    this.style.zIndex = '10';
                });
                
                option.addEventListener('mouseleave', function() {
                    this.style.zIndex = '1';
                });
                
                option.addEventListener('click', function(e) {
                    if (!this.classList.contains('disabled')) {
                        const originalHTML = this.innerHTML;
                        this.innerHTML = `
                            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 10px; color: #9b59b6;"></i>
                                <span>Cargando...</span>
                            </div>
                        `;
                        this.classList.add('disabled');
                        this.style.pointerEvents = 'none';
                        
                        setTimeout(() => {
                            this.innerHTML = originalHTML;
                            this.classList.remove('disabled');
                            this.style.pointerEvents = 'auto';
                        }, 3000);
                    }
                });
            });
            
            const breadcrumbLinks = document.querySelectorAll('.breadcrumb-item a');
            breadcrumbLinks.forEach(link => {
                link.addEventListener('mouseenter', function() {
                    this.style.textDecoration = 'underline';
                });
                link.addEventListener('mouseleave', function() {
                    this.style.textDecoration = 'none';
                });
            });
        });
        
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
    </script>
    <script src="../js/modal-sistema.js"></script>
    <script src="../js/contador.js"></script>
</body>
</html>