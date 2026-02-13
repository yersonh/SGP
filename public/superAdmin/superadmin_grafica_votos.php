<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../models/ReferenciadoModel.php';
require_once __DIR__ . '/../../models/SistemaModel.php';

// Verificar si el usuario está logueado y es SuperAdmin
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    header('Location: ../index.php');
    exit();
}

$pdo = Database::getConnection();
$usuarioModel = new UsuarioModel($pdo);
$referenciadoModel = new ReferenciadoModel($pdo);
$sistemaModel = new SistemaModel($pdo);

// Obtener datos del usuario logueado
$usuario_logueado = $usuarioModel->getUsuarioById($_SESSION['id_usuario']);

// Obtener TODOS los referenciados para estadísticas globales
$todos_referenciados = $referenciadoModel->getAllReferenciados();

// Contar referenciados activos e inactivos GLOBALES
$total_referenciados_global = count($todos_referenciados);
$activos_global = 0;
$inactivos_global = 0;

// Calcular votos por Cámara y Senado GLOBALES
$total_camara_global = 0;
$total_senado_global = 0;
$total_ambos_global = 0; 

foreach ($todos_referenciados as $referenciado) {
    // Contar activos/inactivos
    if ($referenciado['activo'] === true || $referenciado['activo'] === 't' || $referenciado['activo'] == 1) {
        $activos_global++;
    } else {
        $inactivos_global++;
    }
    
    // Solo contar referenciados activos para la gráfica
    $esta_activo = ($referenciado['activo'] === true || $referenciado['activo'] === 't' || $referenciado['activo'] == 1);
    
    if (!$esta_activo) {
        continue;
    }
    
    $id_grupo = $referenciado['id_grupo'] ?? null;
    
    if ($id_grupo == 1) {
        // Solo Cámara
        $total_camara_global++;
    } elseif ($id_grupo == 2) {
        // Solo Senado
        $total_senado_global++;
    } elseif ($id_grupo == 3) {
        // Ambos (Cámara y Senado)
        $total_camara_global++;
        $total_senado_global++;
        $total_ambos_global++; // NUEVO: contar los que votan por ambos
    }
}

// Calcular porcentajes para la gráfica GLOBAL
$total_conteo_global = $total_camara_global + $total_senado_global;
$porc_camara_global = ($total_conteo_global > 0) ? round(($total_camara_global * 100) / $total_conteo_global, 1) : 0;
$porc_senado_global = ($total_conteo_global > 0) ? round(($total_senado_global * 100) / $total_conteo_global, 1) : 0;
$ambos_contados_global = min($total_camara_global, $total_senado_global);
$porc_ambos_global = ($total_conteo_global > 0) ? round(($ambos_contados_global * 100) / $total_conteo_global, 1) : 0;
$ambos_contados_global = $total_ambos_global; // NUEVO: usar el contador real
$porc_ambos_global = ($total_conteo_global > 0) ? round(($ambos_contados_global * 100) / $total_conteo_global, 1) : 0;
// Información del sistema
$infoSistema = $sistemaModel->getInformacionSistema();
$licenciaInfo = $sistemaModel->getInfoCompletaLicencia();
$infoSistema = $licenciaInfo['info'];
$diasRestantes = $licenciaInfo['dias_restantes'];
$validaHastaFormatted = $licenciaInfo['valida_hasta_formatted'];
$fechaInstalacionFormatted = $licenciaInfo['fecha_instalacion_formatted'];

$porcentajeRestante = $sistemaModel->getPorcentajeRestanteLicencia();
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
    <title>Gráfica de Votos - Super Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/superadmin_grafica_votos.css">
    <!-- Cargar Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Definir datos de la gráfica GLOBAL
        const graficaDataGlobal = {
            camara: <?php echo $total_camara_global; ?>,
            senado: <?php echo $total_senado_global; ?>,
            total: <?php echo $activos_global; ?>,
            porcentajes: {
                camara: <?php echo $porc_camara_global; ?>,
                senado: <?php echo $porc_senado_global; ?>,
                ambos: <?php echo $porc_ambos_global; ?>
            }
        };
    </script>
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="header-container">
            <div class="header-top">
                <div class="header-title">
                    <h1><i class="fas fa-chart-pie"></i> Gráfica de Votos Global</h1>
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($usuario_logueado['nombres'] . ' ' . $usuario_logueado['apellidos']); ?></span>
                    </div>
                </div>
                <div>
                    <a href="../logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Breadcrumb Navigation -->
    <div class="breadcrumb-nav">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center">
                <li class="breadcrumb-item"><a href="../superadmin_dashboard.php"><i class="fas fa-home"></i> Panel Super Admin</a></li>
                <li class="breadcrumb-item"><a href="superadmin_monitoreos.php"><i class="fas fa-database"></i> Monitores</a></li>
                <li class="breadcrumb-item active"><i class="fas fa-chart-pie"></i> Gráfica de Votos</li>
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
        <div class="grafica-container">
            <!-- Tarjetas de Estadísticas Globales -->
            <div class="stats-cards">
                <div class="stat-card total">
                    <div class="stat-number"><?php echo $total_referenciados_global; ?></div>
                    <div class="stat-label">Total Referenciados</div>
                </div>
                
                <div class="stat-card activos">
                    <div class="stat-number"><?php echo $activos_global; ?></div>
                    <div class="stat-label">Activos Globales</div>
                </div>
                
                <div class="stat-card inactivos">
                    <div class="stat-number"><?php echo $inactivos_global; ?></div>
                    <div class="stat-label">Inactivos Globales</div>
                </div>
            </div>

            <!-- Contenedor de la Gráfica -->
            <div class="grafica-principal">
                <div class="grafica-header">
                    <h3><i class="fas fa-chart-pie me-2"></i>Distribución Global de Votos - Cámara vs Senado</h3>
                    <p class="grafica-subtitle">Análisis de todos los referenciados activos en el sistema</p>
                </div>
                
                <div class="grafica-content">
                    <!-- Gráfica -->
                    <div class="grafica-canvas-container">
                        <div class="grafica-title-container">
                            <h4 class="text-center mb-3">
                                <i class="fas fa-chart-pie me-2"></i>Cámara vs Senado (Global)
                            </h4>
                        </div>
                        <!-- CAMBIO AQUÍ: Remover width y height fijos, usar contenedor responsive -->
                        <div class="grafica-wrapper">
                            <canvas id="graficaTortaGlobal"></canvas>
                        </div>
                    </div>
                    
                    <!-- Controles de la Gráfica -->
                    <div class="grafica-controls">
                        <div class="controls-header">
                            <h5><i class="fas fa-sliders-h me-2"></i>Controles de la Gráfica</h5>
                        </div>
                        <div class="controls-buttons">
                            <button class="btn-control" onclick="toggleGraficaTipo()" id="btnToggleGrafica">
                                <i class="fas fa-exchange-alt me-2"></i> Cambiar a torta sólida
                            </button>
                            <button class="btn-control" onclick="descargarGrafica()">
                                <i class="fas fa-download me-2"></i> Descargar Gráfica
                            </button>
                            <button class="btn-control" onclick="actualizarGrafica()">
                                <i class="fas fa-sync-alt me-2"></i> Actualizar Datos
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Estadísticas Detalladas -->
                <div class="estadisticas-detalladas">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="estadistica-card camara-detalle">
                                <div class="estadistica-header">
                                    <i class="fas fa-landmark"></i>
                                    <h5>Votos para Cámara</h5>
                                </div>
                                <div class="estadistica-body">
                                    <div class="estadistica-valor"><?php echo $total_camara_global; ?></div>
                                    <div class="estadistica-porcentaje"><?php echo $porc_camara_global; ?>%</div>
                                    <div class="estadistica-desc">de los referenciados activos</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="estadistica-card senado-detalle">
                                <div class="estadistica-header">
                                    <i class="fas fa-balance-scale"></i>
                                    <h5>Votos para Senado</h5>
                                </div>
                                <div class="estadistica-body">
                                    <div class="estadistica-valor"><?php echo $total_senado_global; ?></div>
                                    <div class="estadistica-porcentaje"><?php echo $porc_senado_global; ?>%</div>
                                    <div class="estadistica-desc">de los referenciados activos</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Información Adicional -->
                <div class="info-adicional">
                    <div class="info-header">
                        <h5><i class="fas fa-info-circle me-2"></i>Información Adicional</h5>
                    </div>
                    <div class="info-content">
                        <div class="info-item">
                            <span class="info-label">Referenciados activos totales:</span>
                            <span class="info-value"><?php echo $activos_global; ?> personas</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Votan solo por Cámara:</span>
                            <span class="info-value"><?php echo $total_camara_global - $total_ambos_global; ?> personas</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Votan solo por Senado:</span>
                            <span class="info-value"><?php echo $total_senado_global - $total_ambos_global; ?> personas</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Votan por ambos:</span>
                            <span class="info-value"><?php echo $total_ambos_global; ?> personas</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Total referenciados (activos + inactivos):</span>
                            <span class="info-value"><?php echo $total_referenciados_global; ?> personas</span>
                        </div>
                    </div>
                    
                    <div class="info-analisis">
                        <div class="analisis-icon">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <div class="analisis-text">
                            <strong>Análisis Global:</strong> 
                            <?php
                            if ($total_camara_global > $total_senado_global) {
                                echo "Hay más referenciados activos para Cámara (" . $total_camara_global . ") que para Senado (" . $total_senado_global . ").";
                            } elseif ($total_senado_global > $total_camara_global) {
                                echo "Hay más referenciados activos para Senado (" . $total_senado_global . ") que para Cámara (" . $total_camara_global . ").";
                            } else {
                                echo "Existe una distribución balanceada entre Cámara y Senado en referenciados activos.";
                            }
                            ?>
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
        // Variables para la gráfica
        let chartGlobal = null;
        let chartTypeGlobal = 'doughnut';
        
       // Función para inicializar la gráfica global
function inicializarGraficaGlobal() {
    const canvas = document.getElementById('graficaTortaGlobal');
    const ctx = canvas.getContext('2d');
    
    // Detectar si es móvil
    const isMobile = window.innerWidth < 768;
    
    if (isMobile) {
        // Para móviles: usar tamaño reducido
        const container = canvas.parentElement;
        const containerWidth = container.clientWidth;
        const aspectRatio = 1; // Cuadrado para móviles
        const canvasSize = Math.min(containerWidth - 40, 400); // Máximo 400px en móviles
        
        canvas.width = canvasSize;
        canvas.height = canvasSize / aspectRatio;
    } else {
        // Para PC/tablet: mantener tamaño fijo (650px)
        canvas.width = 650;
        canvas.height = 400;
    }
    
    // Destruir gráfica anterior si existe
    if (chartGlobal) {
        chartGlobal.destroy();
    }
    
    // Configurar datos (mantener igual)
    const data = {
        labels: ['Cámara', 'Senado'],
        datasets: [{
            data: [graficaDataGlobal.camara, graficaDataGlobal.senado],
            backgroundColor: [
                'rgba(52, 152, 219, 0.9)',
                'rgba(155, 89, 182, 0.9)'
            ],
            borderColor: [
                'rgba(52, 152, 219, 1)',
                'rgba(155, 89, 182, 1)'
            ],
            borderWidth: isMobile ? 2 : 3,
            hoverBackgroundColor: [
                'rgba(52, 152, 219, 1)',
                'rgba(155, 89, 182, 1)'
            ],
            hoverOffset: isMobile ? 10 : 15
        }]
    };
    
    // Configurar opciones responsive
    const options = {
        responsive: true,
        maintainAspectRatio: false, // IMPORTANTE: desactivar aspect ratio automático
        plugins: {
            legend: {
                position: isMobile ? 'top' : 'bottom',
                labels: {
                    padding: isMobile ? 10 : 20,
                    font: {
                        size: isMobile ? 12 : 14,
                        weight: 'bold'
                    },
                    generateLabels: function(chart) {
                        const data = chart.data;
                        if (data.labels.length && data.datasets.length) {
                            return data.labels.map((label, i) => {
                                const value = data.datasets[0].data[i];
                                const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value * 100) / total);
                                
                                let displayText;
                                if (window.innerWidth < 480) {
                                    displayText = `${label}: ${value} (${percentage}%)`;
                                } else if (window.innerWidth < 768) {
                                    displayText = `${label}: ${value} votos (${percentage}%)`;
                                } else {
                                    displayText = `${label}: ${value} votos (${percentage}%)`;
                                }
                                
                                return {
                                    text: displayText,
                                    fillStyle: data.datasets[0].backgroundColor[i],
                                    strokeStyle: data.datasets[0].borderColor[i],
                                    lineWidth: 2,
                                    hidden: false,
                                    index: i
                                };
                            });
                        }
                        return [];
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.raw || 0;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = Math.round((value * 100) / total);
                        return `${label}: ${value} personas (${percentage}%)`;
                    }
                },
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleFont: {
                    size: isMobile ? 12 : 14,
                    weight: 'bold'
                },
                bodyFont: {
                    size: isMobile ? 12 : 14
                },
                padding: isMobile ? 10 : 15,
                cornerRadius: 8,
                displayColors: false
            }
        },
        cutout: chartTypeGlobal === 'doughnut' ? '50%' : '0%',
        animation: {
            animateRotate: true,
            animateScale: true,
            duration: 1500,
            easing: 'easeOutQuart'
        }
    };
    
    // Crear gráfica
    chartGlobal = new Chart(ctx, {
        type: chartTypeGlobal,
        data: data,
        options: options
    });
    
    // Redibujar gráfica cuando cambie el tamaño de la ventana
    window.addEventListener('resize', function() {
        if (chartGlobal) {
            // Solo redimensionar si es móvil
            if (window.innerWidth < 768) {
                chartGlobal.resize();
            }
        }
    });
}
        
        // Función para cambiar tipo de gráfica
        function toggleGraficaTipo() {
            chartTypeGlobal = chartTypeGlobal === 'doughnut' ? 'pie' : 'doughnut';
            inicializarGraficaGlobal();
            
            // Actualizar texto del botón
            const btn = document.getElementById('btnToggleGrafica');
            const text = chartTypeGlobal === 'doughnut' ? 'Cambiar a torta sólida' : 'Cambiar a torta hueca';
            btn.innerHTML = `<i class="fas fa-exchange-alt me-2"></i> ${text}`;
        }
        
        // Función para descargar gráfica
        function descargarGrafica() {
            const canvas = document.getElementById('graficaTortaGlobal');
            const link = document.createElement('a');
            link.download = `grafica-votos-global-${new Date().toISOString().split('T')[0]}.png`;
            link.href = canvas.toDataURL('image/png');
            link.click();
            
            // Mostrar notificación
            mostrarNotificacion('Gráfica descargada correctamente', 'success');
        }
        
        // Función para actualizar gráfica
        function actualizarGrafica() {
            const btn = document.querySelector('.btn-control:nth-child(3)');
            const originalHTML = btn.innerHTML;
            
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Actualizando...';
            btn.disabled = true;
            
            // Simular actualización
            setTimeout(() => {
                inicializarGraficaGlobal();
                btn.innerHTML = originalHTML;
                btn.disabled = false;
                mostrarNotificacion('Datos actualizados correctamente', 'success');
            }, 1000);
        }
        
        // Función para mostrar notificaciones
        function mostrarNotificacion(mensaje, tipo = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${tipo}`;
            notification.innerHTML = `
                <div class="notification-content">
                    <i class="fas fa-${tipo === 'success' ? 'check-circle' : tipo === 'error' ? 'exclamation-circle' : tipo === 'warning' ? 'exclamation-triangle' : 'info-circle'}"></i>
                    <span>${mensaje}</span>
                </div>
                <button class="notification-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            document.body.appendChild(notification);
            
            // Posicionar notificación para móviles
            if (window.innerWidth < 768) {
                notification.style.top = '70px';
                notification.style.left = '12px';
                notification.style.right = '12px';
                notification.style.maxWidth = 'none';
            }
            
            // Auto-eliminar después de 5 segundos
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 5000);
        }
        
        // Inicializar gráfica cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar gráfica
            setTimeout(() => {
                inicializarGraficaGlobal();
            }, 300);
            
            // Efectos hover solo en desktop
            if (window.innerWidth >= 768) {
                const cards = document.querySelectorAll('.estadistica-card');
                cards.forEach(card => {
                    card.addEventListener('mouseenter', function() {
                        this.style.transform = 'translateY(-5px)';
                    });
                    card.addEventListener('mouseleave', function() {
                        this.style.transform = 'translateY(0)';
                    });
                });
                
                // Botones de control
                const buttons = document.querySelectorAll('.btn-control');
                buttons.forEach(button => {
                    button.addEventListener('mouseenter', function() {
                        this.style.transform = 'scale(1.05)';
                    });
                    button.addEventListener('mouseleave', function() {
                        this.style.transform = 'scale(1)';
                    });
                });
            }
            
            // Ajustar gráfica cuando cambie la orientación del dispositivo
            window.addEventListener('orientationchange', function() {
                setTimeout(() => {
                    if (chartGlobal) {
                        chartGlobal.resize();
                        chartGlobal.update();
                    }
                }, 300);
            });
        });
        
        // Función para mostrar modal del sistema
        function mostrarModalSistema() {
            const modal = new bootstrap.Modal(document.getElementById('modalSistema'));
            modal.show();
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

        // Ejecutar al cargar y cuando cambie el tema
        document.addEventListener('DOMContentLoaded', function() {
            actualizarLogoSegunTema();
        });

        // Escuchar cambios en el tema del sistema
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
            actualizarLogoSegunTema();
        });
    </script>
    <script src="../js/contador.js"></script>
</body>
</html>