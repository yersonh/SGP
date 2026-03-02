<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../models/ReferenciadoModel.php';
require_once __DIR__ . '/../../models/SistemaModel.php';
require_once __DIR__ . '/../../helpers/navigation_helper.php';
require_once __DIR__ . '/../../models/PregoneroModel.php';

// Verificar si el usuario está logueado y es SuperAdmin
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    header('Location: ../index.php');
    exit();
}

NavigationHelper::pushUrl();
$pdo = Database::getConnection();
$usuarioModel = new UsuarioModel($pdo);
$referenciadoModel = new ReferenciadoModel($pdo);
$pregoneroModel = new PregoneroModel($pdo);
$sistemaModel = new SistemaModel($pdo);

// Obtener datos del usuario logueado
$usuario_logueado = $usuarioModel->getUsuarioById($_SESSION['id_usuario']);

// Obtener referenciados activos
$referenciados_activos = $pregoneroModel->contarPregonerosActivos();

// Obtener información del sistema para el modal
$licenciaInfo = $sistemaModel->getInfoCompletaLicencia();
$infoSistema = $licenciaInfo['info'];
$diasRestantes = $licenciaInfo['dias_restantes'];
$validaHastaFormatted = $licenciaInfo['valida_hasta_formatted'];
$fechaInstalacionFormatted = $licenciaInfo['fecha_instalacion_formatted'];

// Calcular porcentaje restante de licencia
$porcentajeRestante = $sistemaModel->getPorcentajeRestanteLicencia();
if ($porcentajeRestante > 50) { 
    $barColor = 'bg-success';
} elseif ($porcentajeRestante > 25) {
    $barColor = 'bg-warning';
} else {
    $barColor = 'bg-danger';
}

$totalVotantes = $pregoneroModel->contarPregonerosVotaron();
$totalPendientes = $pregoneroModel->contarPregonerosPendientes();

$porcentajeMeta = $referenciados_activos > 0 ? number_format(($totalVotantes / $referenciados_activos) * 100, 2, '.', '') : 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Votantes - SuperAdmin - SGP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/descargador.css">
</head>
<body>
    <!-- Loading Spinner -->
    <div class="spinner-overlay" id="spinner">
        <div class="spinner"></div>
    </div>

    <!-- Header - SIN LA BARRA DE PROGRESO -->
    <header class="main-header">
        <div class="header-container">
            <div class="header-top">
                <div class="header-title">
                    <h1>
                        <i class="fas fa-vote-yea"></i> 
                        Registro de pregoneros - Elecciones 2026
                    </h1>
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($usuario_logueado['nombres'] . ' ' . $usuario_logueado['apellidos']); ?></span>
                        <span class="badge">SuperAdmin</span>
                    </div>
                </div>
                <a href="../logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-container">
        <!-- Stats Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value" id="metaVotantes"><?php echo number_format($referenciados_activos); ?></div>
                    <div class="stat-label">PREGONEROS</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value" id="totalVotantes"><?php echo number_format($totalVotantes); ?></div>
                    <div class="stat-label">Total Votantes</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value" id="porcentajeMeta"><?php echo number_format($porcentajeMeta, 2, '.', ''); ?>%</div>
                    <div class="stat-label">Cumplimiento</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value" id="pendientes"><?php echo number_format($totalPendientes); ?></div>
                    <div class="stat-label">Pendientes</div>
                </div>
            </div>
        </div>

        <!-- Form Card -->
        <div class="form-card">
            <div class="form-header">
                <h2>
                    <i class="fas fa-search"></i>
                    Buscar Pregonero
                </h2>
                <button type="button" class="btn-mesa-search" onclick="mostrarEstadisticas()">
                    <i class="fas fa-chart-bar"></i> Ver Estadísticas
                </button>
            </div>

            <form class="search-form" id="searchForm">
                <input type="text" 
                       placeholder="Ingrese el número de cédula del votante" 
                       id="cedulaInput"
                       pattern="[0-9]+" 
                       title="Solo números"
                       maxlength="20"
                       required>
                <button type="submit">
                    <i class="fas fa-search"></i>
                    Buscar
                </button>
            </form>
        </div>

        <!-- Empty State -->
        <div class="empty-state" id="emptyState">
            <i class="fas fa-vote-yea"></i>
            <h3>Busque un pregonero para comenzar</h3>
            <p>Ingrese el número de cédula para verificar si ya votó y registrar su voto</p>
        </div>

        <!-- Result Card -->
        <div class="result-card" id="resultCard">
            <div class="persona-header">
                <div class="persona-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="persona-info">
                    <h3 id="personaNombre">-</h3>
                    <div class="persona-badge">
                        <i class="fas fa-id-card"></i>
                        <span id="personaCedula">-</span>
                    </div>
                </div>
            </div>

            <div class="persona-details">
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div class="detail-content">
                        <div class="detail-label">Teléfono</div>
                        <div class="detail-value" id="personaTelefono">-</div>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="detail-content">
                        <div class="detail-label">Email</div>
                        <div class="detail-value" id="personaEmail">-</div>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="detail-content">
                        <div class="detail-label">Dirección</div>
                        <div class="detail-value" id="personaDireccion">-</div>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="detail-content">
                        <div class="detail-label">Referenciador</div>
                        <div class="detail-value" id="personaReferenciador">-</div>
                    </div>
                </div>
            </div>

            <!-- Voto Status -->
            <div class="voto-status" id="votoStatus">
                <div class="voto-status-info">
                    <div class="voto-status-icon no-votado" id="votoIcon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="voto-status-text">
                        <h4 id="votoEstado">No ha votado</h4>
                        <p id="votoMensaje">Este votante aún no ha registrado su voto</p>
                    </div>
                </div>
                <div class="voto-badge no-votado" id="votoBadge">
                    Pendiente
                </div>
            </div>

            <!-- Download Section -->
            <div class="download-section">
                <div class="download-header">
                    <h4>
                        <i class="fas fa-vote-yea"></i>
                        Registrar Voto
                    </h4>
                </div>

                <div class="download-actions">
                    <button class="download-btn" id="btnRegistrarVoto" onclick="registrarVoto()">
                        <i class="fas fa-check-circle"></i>
                        Confirmar Voto
                    </button>
                </div>
                <p class="text-muted mt-2 mb-0" style="font-size: 0.9rem; text-align: center;">
                    <i class="fas fa-info-circle"></i> 
                    Al hacer clic, se registrará que esta persona ya votó en las elecciones de hoy
                </p>
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
                    <!-- Logo centrado -->
                    <div class="modal-logo-container">
                        <img src="../imagenes/Logo-artguru.png" alt="Logo del Sistema" class="modal-logo">
                    </div>
                    
                    <!-- Licencia Info -->
                    <div class="licencia-info">
                        <div class="licencia-header">
                            <h6 class="licencia-title">Licencia Runtime</h6>
                            <span class="licencia-dias">
                                <?php echo $diasRestantes; ?> días restantes
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
                        <div class="col-md-6">
                            <div class="feature-card">
                                <div class="feature-icon text-primary mb-3">
                                    <i class="fas fa-bolt fa-2x"></i>
                                </div>
                                <h5 class="feature-title">Efectividad de la Herramienta</h5>
                                <h6 class="text-muted mb-2">Optimización de Tiempos</h6>
                                <p class="feature-text">
                                    Reducción del 70% en el procesamiento manual de datos.
                                </p>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="feature-card">
                                <div class="feature-icon text-success mb-3">
                                    <i class="fas fa-database fa-2x"></i>
                                </div>
                                <h5 class="feature-title">Integridad de Datos</h5>
                                <h6 class="text-muted mb-2">Validación Inteligente</h6>
                                <p class="feature-text">
                                    Validación en tiempo real para eliminar duplicados.
                                </p>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="feature-card">
                                <div class="feature-icon text-warning mb-3">
                                    <i class="fas fa-chart-line fa-2x"></i>
                                </div>
                                <h5 class="feature-title">Monitoreo de Metas</h5>
                                <h6 class="text-muted mb-2">Seguimiento Visual</h6>
                                <p class="feature-text">
                                    Seguimiento visual del cumplimiento de objetivos.
                                </p>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="feature-card">
                                <div class="feature-icon text-danger mb-3">
                                    <i class="fas fa-shield-alt fa-2x"></i>
                                </div>
                                <h5 class="feature-title">Seguridad Avanzada</h5>
                                <h6 class="text-muted mb-2">Control Total</h6>
                                <p class="feature-text">
                                    Control de acceso jerarquizado y trazabilidad total.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="https://sgp-sistema-de-gestion-politica.webnode.com.co/" 
                       target="_blank" 
                       class="btn btn-primary"
                       onclick="cerrarModalSistema();">
                        <i class="fas fa-external-link-alt me-1"></i> Uso SGP
                    </a>
                    
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
    
    <script>
    // Variables globales
    let currentVotante = null;
    let totalVotantes = <?php echo $totalVotantes; ?>;
    let metaVotantes = <?php echo $referenciados_activos; ?>;
    let votantesPendientes = <?php echo $totalPendientes; ?>;

    // Inicializar stats (valores iniciales)
    function actualizarStats() {
        const pendientes = metaVotantes - totalVotantes;
        const porcentaje = metaVotantes > 0 ? ((totalVotantes / metaVotantes) * 100).toFixed(2) : "0.00";
        
        document.getElementById('totalVotantes').textContent = totalVotantes.toLocaleString();
        document.getElementById('porcentajeMeta').textContent = porcentaje + '%';
        document.getElementById('pendientes').textContent = pendientes.toLocaleString();
    }

    // Mostrar/ocultar spinner
    function showSpinner() {
        document.getElementById('spinner').classList.add('show');
    }

    function hideSpinner() {
        document.getElementById('spinner').classList.remove('show');
    }

    // Manejar búsqueda
    document.getElementById('searchForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const cedula = document.getElementById('cedulaInput').value.trim();
        
        if (!cedula) {
            Swal.fire({
                icon: 'warning',
                title: 'Campo vacío',
                text: 'Por favor ingrese un número de cédula',
                timer: 2000,
                showConfirmButton: false
            });
            return;
        }
        
        if (!/^\d+$/.test(cedula)) {
            Swal.fire({
                icon: 'error',
                title: 'Cédula inválida',
                text: 'La cédula solo debe contener números',
                timer: 2000,
                showConfirmButton: false
            });
            return;
        }
        
        showSpinner();
        
        // Hacer la petición AJAX al archivo que creamos
        fetch(`../ajax/buscar_pregonero_por_identificacion.php?identificacion=${cedula}`)
            .then(response => response.json())
            .then(data => {
                hideSpinner();
                
                if (!data.success) {
                    // Mostrar mensaje de error (cédula no encontrada)
                    Swal.fire({
                        icon: 'error',
                        title: 'No encontrado',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    // Mostrar empty state y ocultar resultado
                    document.getElementById('emptyState').style.display = 'block';
                    document.getElementById('resultCard').classList.remove('show');
                    return;
                }
                
                // Guardar datos del votante
                currentVotante = data.data;
                
                // Ocultar empty state y mostrar resultado
                document.getElementById('emptyState').style.display = 'none';
                document.getElementById('resultCard').classList.add('show');
                
                // Llenar datos personales
                document.getElementById('personaNombre').textContent = data.data.nombre_completo;
                document.getElementById('personaCedula').textContent = data.data.identificacion; 
                document.getElementById('personaTelefono').textContent = data.data.telefono;
                document.getElementById('personaEmail').textContent = data.data.email;
                document.getElementById('personaDireccion').textContent = data.data.direccion;
                document.getElementById('personaReferenciador').textContent = data.data.referenciador;
                
                // Actualizar estado de votación basado en voto_registrado
                const votoIcon = document.getElementById('votoIcon');
                const votoEstado = document.getElementById('votoEstado');
                const votoMensaje = document.getElementById('votoMensaje');
                const votoBadge = document.getElementById('votoBadge');
                const btnRegistrar = document.getElementById('btnRegistrarVoto');
                
                if (data.data.voto_registrado) {
                    // YA VOTÓ
                    votoIcon.className = 'voto-status-icon votado';
                    votoIcon.innerHTML = '<i class="fas fa-check-circle"></i>';
                    votoEstado.textContent = '¡Ya votó!';
                    votoMensaje.textContent = 'Este votante ya registró su voto';
                    votoBadge.className = 'voto-badge votado';
                    votoBadge.textContent = 'Votó';
                    btnRegistrar.className = 'download-btn votado';
                    btnRegistrar.innerHTML = '<i class="fas fa-check-circle"></i> Voto Registrado';
                    btnRegistrar.disabled = true;
                } else {
                    // NO HA VOTADO
                    votoIcon.className = 'voto-status-icon no-votado';
                    votoIcon.innerHTML = '<i class="fas fa-times-circle"></i>';
                    votoEstado.textContent = 'No ha votado';
                    votoMensaje.textContent = 'Este votante aún no ha registrado su voto';
                    votoBadge.className = 'voto-badge no-votado';
                    votoBadge.textContent = 'Pendiente';
                    btnRegistrar.className = 'download-btn';
                    btnRegistrar.innerHTML = '<i class="fas fa-check-circle"></i> Confirmar Voto';
                    btnRegistrar.disabled = false;
                }
            })
            .catch(error => {
                hideSpinner();
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al conectar con el servidor',
                    timer: 2000,
                    showConfirmButton: false
                });
            });
    });

    function registrarVoto() {
        const btn = document.getElementById('btnRegistrarVoto');
        
        if (!currentVotante) {
            Swal.fire({
                icon: 'warning',
                title: 'Error',
                text: 'No hay un pregonero seleccionado',
                timer: 2000,
                showConfirmButton: false
            });
            return;
        }
        
        if (btn.disabled) {
            Swal.fire({
                icon: 'info',
                title: 'Voto ya registrado',
                text: 'Este pregonero ya registró su voto anteriormente',
                timer: 2000,
                showConfirmButton: false
            });
            return;
        }
        
        Swal.fire({
            title: '¿Confirmar voto?',
            text: 'Va a registrar que este pregonero ya votó en las elecciones de hoy',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, registrar voto',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#27ae60'
        }).then((result) => {
            if (result.isConfirmed) {
                showSpinner();
                
                // Enviar petición para registrar voto de pregonero
                fetch('../ajax/registrar_voto_pregonero.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'id_pregonero=' + currentVotante.id_pregonero
                })
                .then(response => response.json())
                .then(data => {
                    hideSpinner();
                    
                    if (data.success) {
                        // Actualizar estadísticas globales
                        if (data.stats) {
                            totalVotantes = data.stats.ya_votaron;
                            metaVotantes = data.stats.total_activos;
                        }
                        
                        // Actualizar estado visual
                        const votoIcon = document.getElementById('votoIcon');
                        const votoEstado = document.getElementById('votoEstado');
                        const votoMensaje = document.getElementById('votoMensaje');
                        const votoBadge = document.getElementById('votoBadge');
                        
                        votoIcon.className = 'voto-status-icon votado';
                        votoIcon.innerHTML = '<i class="fas fa-check-circle"></i>';
                        votoEstado.textContent = '¡Ya votó!';
                        votoMensaje.textContent = 'Voto registrado exitosamente';
                        votoBadge.className = 'voto-badge votado';
                        votoBadge.textContent = 'Votó';
                        
                        btn.className = 'download-btn votado';
                        btn.innerHTML = '<i class="fas fa-check-circle"></i> Voto Registrado';
                        btn.disabled = true;
                        
                        // Actualizar stats en la UI
                        actualizarStats();
                        
                        // Actualizar estado local del votante
                        currentVotante.voto_registrado = true;
                        
                        Swal.fire({
                            icon: 'success',
                            title: '¡Voto registrado!',
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                })
                .catch(error => {
                    hideSpinner();
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error al conectar con el servidor',
                        timer: 2000,
                        showConfirmButton: false
                    });
                });
            }
        });
    }

    function mostrarEstadisticas() {
        Swal.fire({
            title: 'Estadísticas del Día',
            html: `
                <div style="text-align: left">
                    <p><strong>Votantes que han votado:</strong> ${totalVotantes}</p>
                    <p><strong>Total de pregoneres activos:</strong> ${metaVotantes}</p>
                    <p><strong>Porcentaje de participación:</strong> ${metaVotantes > 0 ? ((totalVotantes/metaVotantes)*100).toFixed(2) : 0}%</p>
                    <p><strong>Pendientes por votar:</strong> ${metaVotantes - totalVotantes}</p>
                </div>
            `,
            icon: 'info',
            confirmButtonText: 'Cerrar'
        });
    }

    // Validar que solo se ingresen números en el campo de cédula
    document.getElementById('cedulaInput').addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    // Inicializar stats
    actualizarStats();

    function mostrarModalSistema() {
        const modal = new bootstrap.Modal(document.getElementById('modalSistema'));
        modal.show();
    }

    function cerrarModalSistema() {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalSistema'));
        if (modal) {
            modal.hide();
        }
    }
</script>
    <script src="../js/modal-sistema.js"></script>
</body>
</html>