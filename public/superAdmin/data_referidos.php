<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../models/ReferenciadoModel.php';
require_once __DIR__ . '/../../models/ZonaModel.php';
require_once __DIR__ . '/../../models/SectorModel.php';
require_once __DIR__ . '/../../models/PuestoVotacionModel.php';
require_once __DIR__ . '/../../models/DepartamentoModel.php';
require_once __DIR__ . '/../../models/MunicipioModel.php';
require_once __DIR__ . '/../../models/OfertaApoyoModel.php';
require_once __DIR__ . '/../../models/GrupoPoblacionalModel.php';
require_once __DIR__ . '/../../models/BarrioModel.php';
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

// Obtener todos los referenciados
$referenciados = $referenciadoModel->getAllReferenciados();

// Calcular estadísticas
$totalReferidos = count($referenciados);
$totalActivos = 0;
$totalInactivos = 0;

// Contar activos e inactivos
foreach ($referenciados as $referenciado) {
    $activo = $referenciado['activo'] ?? true;
    $esta_activo = ($activo === true || $activo === 't' || $activo == 1);
    
    if ($esta_activo) {
        $totalActivos++;
    } else {
        $totalInactivos++;
    }
}

// Inicializar modelos para obtener nombres de relaciones
$zonaModel = new ZonaModel($pdo);
$sectorModel = new SectorModel($pdo);
$puestoModel = new PuestoVotacionModel($pdo);
$departamentoModel = new DepartamentoModel($pdo);
$municipioModel = new MunicipioModel($pdo);
$ofertaModel = new OfertaApoyoModel($pdo);
$grupoModel = new GrupoPoblacionalModel($pdo);
$barrioModel = new BarrioModel($pdo);

// Obtener todos los datos de relaciones
$zonas = $zonaModel->getAll();
$sectores = $sectorModel->getAll();
$puestos = $puestoModel->getAll();
$departamentos = $departamentoModel->getAll();
$municipios = $municipioModel->getAll();
$ofertas = $ofertaModel->getAll();
$grupos = $grupoModel->getAll();
$barrios = $barrioModel->getAll();

// Crear arrays para búsqueda rápida
$zonasMap = [];
foreach ($zonas as $zona) {
    $zonasMap[$zona['id_zona']] = $zona['nombre'];
}

$sectoresMap = [];
foreach ($sectores as $sector) {
    $sectoresMap[$sector['id_sector']] = $sector['nombre'];
}

$puestosMap = [];
foreach ($puestos as $puesto) {
    $puestosMap[$puesto['id_puesto']] = $puesto['nombre'];
}

$departamentosMap = [];
foreach ($departamentos as $departamento) {
    $departamentosMap[$departamento['id_departamento']] = $departamento['nombre'];
}

$municipiosMap = [];
foreach ($municipios as $municipio) {
    $municipiosMap[$municipio['id_municipio']] = $municipio['nombre'];
}

$ofertasMap = [];
foreach ($ofertas as $oferta) {
    $ofertasMap[$oferta['id_oferta']] = $oferta['nombre'];
}

$gruposMap = [];
foreach ($grupos as $grupo) {
    $gruposMap[$grupo['id_grupo']] = $grupo['nombre'];
}

$barriosMap = [];
foreach ($barrios as $barrio) {
    $barriosMap[$barrio['id_barrio']] = $barrio['nombre'];
}
// 6. Obtener información del sistema
$infoSistema = $sistemaModel->getInformacionSistema();

// 7. Formatear fecha para mostrar
$fecha_formateada = date('d/m/Y H:i:s', strtotime($fecha_actual));

// 8. Obtener información completa de la licencia (MODIFICADO)
$licenciaInfo = $sistemaModel->getInfoCompletaLicencia();

// Extraer valores
$infoSistema = $licenciaInfo['info'];
$diasRestantes = $licenciaInfo['dias_restantes'];
$validaHastaFormatted = $licenciaInfo['valida_hasta_formatted'];
$fechaInstalacionFormatted = $licenciaInfo['fecha_instalacion_formatted'];

// PARA LA BARRA QUE DISMINUYE: Calcular porcentaje RESTANTE
$porcentajeRestante = $sistemaModel->getPorcentajeRestanteLicencia();

// Color de la barra basado en lo que RESTA (ahora es más simple)
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script> <!-- FALTA ESTA LÍNEA -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../styles/data_referidos.css">
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
                    <button class="btn-search">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                    <button class="btn-export" data-bs-toggle="modal" data-bs-target="#exportModal">
                        <i class="fas fa-download"></i> Exportar
                    </button>
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
                    <tbody>
                        <?php foreach ($referenciados as $referenciado): 
                            $activo = $referenciado['activo'] ?? true;
                            $esta_activo = ($activo === true || $activo === 't' || $activo == 1);
                        ?>
                        <tr <?php echo !$esta_activo ? 'style="background-color: #f8f9fa; opacity: 0.8;"' : ''; ?>>
                            <td>
                                <?php if ($esta_activo): ?>
                                    <span style="color: #27ae60; font-size: 0.8rem;">
                                        <i class="fas fa-check-circle"></i> Activo
                                    </span>
                                <?php else: ?>
                                    <span style="color: #e74c3c; font-size: 0.8rem;">
                                        <i class="fas fa-times-circle"></i> Inactivo
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($referenciado['nombre'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($referenciado['apellido'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($referenciado['cedula'] ?? ''); ?></td>
                            <td class="text-ellipsis" title="<?php echo htmlspecialchars($referenciado['direccion'] ?? ''); ?>">
                                <?php echo htmlspecialchars($referenciado['direccion'] ?? ''); ?>
                            </td>
                            <td><?php echo htmlspecialchars($referenciado['email'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($referenciado['telefono'] ?? ''); ?></td>
                            <td>
                                <div class="badge-affinidad badge-affinidad-<?php echo $referenciado['afinidad'] ?? '1'; ?>">
                                    <?php echo $referenciado['afinidad'] ?? '0'; ?>
                                </div>
                            </td>
                            <td><?php echo isset($referenciado['id_zona']) && isset($zonasMap[$referenciado['id_zona']]) ? htmlspecialchars($zonasMap[$referenciado['id_zona']]) : 'N/A'; ?></td>
                            <td><?php echo isset($referenciado['id_sector']) && isset($sectoresMap[$referenciado['id_sector']]) ? htmlspecialchars($sectoresMap[$referenciado['id_sector']]) : 'N/A'; ?></td>
                            <td><?php echo isset($referenciado['id_puesto_votacion']) && isset($puestosMap[$referenciado['id_puesto_votacion']]) ? htmlspecialchars($puestosMap[$referenciado['id_puesto_votacion']]) : 'N/A'; ?></td>
                            <td><?php echo htmlspecialchars($referenciado['mesa'] ?? ''); ?></td>
                            <td><?php echo isset($referenciado['id_departamento']) && isset($departamentosMap[$referenciado['id_departamento']]) ? htmlspecialchars($departamentosMap[$referenciado['id_departamento']]) : 'N/A'; ?></td>
                            <td><?php echo isset($referenciado['id_municipio']) && isset($municipiosMap[$referenciado['id_municipio']]) ? htmlspecialchars($municipiosMap[$referenciado['id_municipio']]) : 'N/A'; ?></td>
                            <td><?php echo isset($referenciado['id_oferta_apoyo']) && isset($ofertasMap[$referenciado['id_oferta_apoyo']]) ? htmlspecialchars($ofertasMap[$referenciado['id_oferta_apoyo']]) : 'N/A'; ?></td>
                            <td><?php echo isset($referenciado['id_grupo_poblacional']) && isset($gruposMap[$referenciado['id_grupo_poblacional']]) ? htmlspecialchars($gruposMap[$referenciado['id_grupo_poblacional']]) : 'N/A'; ?></td>
                            
                            <!-- NUEVA COLUMNA: Grupo Parlamentario -->
                            <!-- Como ya viene en la consulta como 'grupo_nombre', podemos usarlo directamente -->
                            <td>
                                <?php 
                                // Mostrar el grupo parlamentario desde la consulta
                                $grupoParlamentario = !empty($referenciado['grupo_nombre']) 
                                    ? htmlspecialchars($referenciado['grupo_nombre']) 
                                    : 'N/A';
                                echo $grupoParlamentario;
                                ?>
                            </td>
                            
                            <td><?php echo isset($referenciado['id_barrio']) && isset($barriosMap[$referenciado['id_barrio']]) ? htmlspecialchars($barriosMap[$referenciado['id_barrio']]) : 'N/A'; ?></td>
                            <td><?php echo htmlspecialchars($referenciado['referenciador_nombre'] ?? 'N/A'); ?></td>
                            <td><?php echo isset($referenciado['fecha_registro']) ? date('d/m/Y H:i', strtotime($referenciado['fecha_registro'])) : ''; ?></td>
                            <td>
                                <div class="action-buttons">
                                    <!-- BOTÓN DE VER DETALLE -->
                                    <button class="btn-action btn-view" 
                                            title="Ver detalle del referido"
                                            onclick="window.location.href='ver_referenciado.php?id=<?php echo $referenciado['id_referenciado']; ?>'">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <!-- BOTÓN DE EDITAR -->
                                    <button type="button" 
                                            class="btn-action btn-edit" 
                                            title="Editar referido"
                                            onclick="location.href='editar_referenciador.php?id=<?php echo (int)$referenciado['id_referenciado']; ?>'">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <!-- BOTÓN DE ACTIVAR/DESACTIVAR -->
                                    <?php if ($esta_activo): ?>
                                        <button class="btn-action btn-deactivate" 
                                                title="Desactivar referido"
                                                onclick="desactivarReferenciado(
                                                    <?php echo $referenciado['id_referenciado']; ?>, 
                                                    '<?php echo htmlspecialchars($referenciado['nombre'] . ' ' . $referenciado['apellido']); ?>', 
                                                    this)">
                                            <i class="fas fa-user-slash"></i>
                                        </button>
                                    <?php else: ?>
                                        <button class="btn-action btn-activate" 
                                                title="Activar referido"
                                                onclick="reactivarReferenciado(
                                                    <?php echo $referenciado['id_referenciado']; ?>, 
                                                    '<?php echo htmlspecialchars($referenciado['nombre'] . ' ' . $referenciado['apellido']); ?>', 
                                                    this)">
                                            <i class="fas fa-user-check"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Info Footer -->
        <div style="text-align: center; color: #666; font-size: 0.9rem; margin-top: 20px;">
            <p><i class="fas fa-info-circle"></i> Mostrando <?php echo $totalReferidos; ?> referidos (<?php echo $totalActivos; ?> activos, <?php echo $totalInactivos; ?> inactivos)</p>
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
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            // Inicializar DataTable
            $('#referidosTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                },
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
                order: [[18, 'desc']], // Ordenar por fecha de registro descendente por defecto
                responsive: true,
                scrollX: true, // Permitir scroll horizontal
                dom: '<"top"f>rt<"bottom"lip><"clear">',
                initComplete: function() {
                    // Ajustar columnas después de inicializar
                    this.api().columns.adjust();
                },
                columnDefs: [
                    {
                        targets: -1, // Última columna (Acciones)
                        orderable: false,
                        searchable: false,
                        width: '130px'
                    },
                    {
                        targets: 0, // Columna de Estado
                        width: '100px',
                        searchable: true
                    }
                ]
            });
            
            // Botón de búsqueda
            $('.btn-search').click(function() {
                $('#referidosTable').DataTable().search('').draw();
                $('#referidosTable_filter input').focus();
            });
            
            // Botón de exportar - SOLO ABRE EL MODAL (no hacer nada más)
            $('.btn-export').click(function(e) {
                // El modal se abre automáticamente por data-bs-toggle
                // No hacer nada aquí para evitar conflicto
            });
            
            // Ajustar tabla en redimensionamiento
            $(window).resize(function() {
                $('#referidosTable').DataTable().columns.adjust();
            });

            // Efecto hover en botones de acción
            $('.btn-action').hover(
                function() {
                    $(this).css('transform', 'translateY(-2px)');
                    $(this).css('box-shadow', '0 3px 6px rgba(0,0,0,0.1)');
                },
                function() {
                    $(this).css('transform', 'translateY(0)');
                    $(this).css('box-shadow', 'none');
                }
            );
        });

        // Función para exportar referidos
        function exportarReferidos(formato) {
            const soloActivos = document.getElementById('exportSoloActivos').checked;
            
            let url = '';
            
            // Asignar URL según formato
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
            
            // Agregar parámetro si es necesario
            if (soloActivos) {
                url += '?solo_activos=1';
            }
            
            // Cerrar modal
            const exportModal = bootstrap.Modal.getInstance(document.getElementById('exportModal'));
            if (exportModal) {
                exportModal.hide();
            }
            
            // Mostrar mensaje de procesamiento
            showNotification('Generando archivo ' + formato.toUpperCase() + '...', 'info');
            
            // Descargar archivo después de un pequeño delay
            setTimeout(() => {
                // Crear un link temporal para la descarga
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
                    // Cambiar el botón a verde con icono de reactivar
                    button.className = 'btn-action btn-activate';
                    button.title = 'Activar referido';
                    button.innerHTML = '<i class="fas fa-user-check"></i>';
                    button.disabled = false;
                    
                    // Cambiar event listener para reactivar
                    button.setAttribute('onclick', `reactivarReferenciado(${idReferenciado}, '${nombreReferenciado.replace(/'/g, "\\'")}', this)`);
                    
                    // Actualizar estado visual en la tabla
                    const row = button.closest('tr');
                    row.style.backgroundColor = '#f8f9fa';
                    row.style.opacity = '0.8';
                    row.cells[0].innerHTML = '<span style="color: #e74c3c; font-size: 0.8rem;"><i class="fas fa-times-circle"></i> Inactivo</span>';
                    
                    // Mostrar notificación
                    showNotification('Referenciado desactivado correctamente', 'success');
                    
                    // Actualizar contador
                    updateStats(-1, 1); // Disminuir activos, aumentar inactivos
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
                    // Cambiar el botón a amarillo con icono de desactivar
                    button.className = 'btn-action btn-deactivate';
                    button.title = 'Desactivar referido';
                    button.innerHTML = '<i class="fas fa-user-slash"></i>';
                    button.disabled = false;
                    
                    // Cambiar event listener para desactivar
                    button.setAttribute('onclick', `desactivarReferenciado(${idReferenciado}, '${nombreReferenciado.replace(/'/g, "\\'")}', this)`);
                    
                    // Actualizar estado visual en la tabla
                    const row = button.closest('tr');
                    row.style.backgroundColor = '';
                    row.style.opacity = '';
                    row.cells[0].innerHTML = '<span style="color: #27ae60; font-size: 0.8rem;"><i class="fas fa-check-circle"></i> Activo</span>';
                    
                    // Mostrar notificación
                    showNotification('Referenciado reactivado correctamente', 'success');
                    
                    // Actualizar contador
                    updateStats(1, -1); // Aumentar activos, disminuir inactivos
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

        // Función para actualizar estadísticas en tiempo real
        function updateStats(activosChange, inactivosChange) {
            // Actualizar los números en las estadísticas
            const totalElement = document.querySelector('.stat-total .stat-number');
            const activosElement = document.querySelector('.stat-activos .stat-number');
            
            if (activosElement) {
                let currentActivos = parseInt(activosElement.textContent);
                activosElement.textContent = currentActivos + activosChange;
            }
            
            // También podríamos actualizar el texto del pie de página
            const infoFooter = document.querySelector('.table-title small');
            if (infoFooter) {
                const currentInactivos = <?php echo $totalInactivos; ?> + inactivosChange;
                infoFooter.innerHTML = `<i class="fas fa-info-circle"></i> ${currentInactivos} referido(s) inactivo(s)`;
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

// Ejecutar al cargar y cuando cambie el tema
document.addEventListener('DOMContentLoaded', function() {
    actualizarLogoSegunTema();
});

// Escuchar cambios en el tema del sistema
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
    actualizarLogoSegunTema();
});
        // Función para mostrar notificaciones
        function showNotification(message, type = 'info') {
            // Eliminar notificación anterior si existe
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
            
            // Botón para cerrar
            notification.querySelector('.notification-close').addEventListener('click', () => {
                notification.remove();
            });
            
            // Auto-eliminar después de 5 segundos
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 5000);
        }
    </script>
    <script src="../js/modal-sistema.js"></script>
    <script src="../js/contador.js"></script>
</body>
</html>