<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/LiderModel.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../models/SistemaModel.php';

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'Administrador') {
    header('location: index.php');
    exit();
}

$pdo = Database::getConnection();
$model = new LiderModel($pdo);
$usuarioModel = new UsuarioModel($pdo);
$sistemaModel = new SistemaModel($pdo);

$id_usuario_logueado = $_SESSION['id_usuario'];

// 1. Capturar la fecha actual
$fecha_actual = date('Y-m-d H:i:s');

// 2. Obtener datos del usuario logueado
$usuario_logueado = $usuarioModel->getUsuarioById($id_usuario_logueado);

// 3. Obtener todos los líderes usando el modelo
$lideres = $model->getAll();

// 4. Obtener estadísticas usando el modelo
$stats = $model->getStats();
$total_lideres = $stats['total_lideres'] ?? 0;
$lideres_activos = $stats['activos'] ?? 0;
$lideres_inactivos = $stats['inactivos'] ?? 0;
$referenciadores_asignados = $stats['referenciadores_asignados'] ?? 0;

// 5. Obtener todos los usuarios referenciadores para el filtro
$referenciadores = $usuarioModel->getReferenciadoresActivos();

// 6. Obtener información del sistema
$infoSistema = $sistemaModel->getInformacionSistema();

// 7. Formatear fecha para mostrar
$fecha_formateada = date('d/m/Y H:i:s', strtotime($fecha_actual));

// 8. Obtener información completa de la licencia
$licenciaInfo = $sistemaModel->getInfoCompletaLicencia();

// Extraer valores
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
    <title>Gestión de Líderes - SGP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../styles/dashboard.css">
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="header-container">
            <div class="header-title">
                <h1>
                    <i class="fas fa-user-tie"></i> Gestión de Líderes
                    <span class="user-count"><?php echo $lideres_activos; ?> líderes activos</span>
                </h1>
            </div>
            <a href="../logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
            </a>
        </div>
    </header>

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

    <div class="main-container">
        <!-- Información del usuario actual -->
        <div class="current-user-info">
            <div class="current-user-header">
                <h3><i class="fas fa-user-circle"></i> Sesión activa</h3>
                <span class="login-time" id="current-time"><?php echo $fecha_formateada; ?></span>
            </div>
            <div class="user-details">
                <div class="detail-item">
                    <span class="detail-label">Usuario Actual:</span>
                    <span class="detail-value">
                        <?php 
                        $nombre_completo = '';
                        if (!empty($usuario_logueado['nombres']) && !empty($usuario_logueado['apellidos'])) {
                            $nombre_completo = htmlspecialchars($usuario_logueado['nombres'] . ' ' . $usuario_logueado['apellidos']);
                        } else {
                            $nombre_completo = htmlspecialchars($usuario_logueado['nickname']);
                        }
                        echo $nombre_completo;
                        ?>
                    </span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Nickname:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($usuario_logueado['nickname']); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Tipo de usuario:</span>
                    <span class="detail-value user-type"><?php echo htmlspecialchars($usuario_logueado['tipo_usuario']); ?></span>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters-container mb-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Estado</label>
                    <select class="form-select" id="filter-estado" onchange="filtrarLideres()">
                        <option value="">Todos los estados</option>
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Referenciador</label>
                    <select class="form-select" id="filter-referenciador" onchange="filtrarLideres()">
                        <option value="">Todos los referenciadores</option>
                        <?php foreach ($referenciadores as $ref): ?>
                            <option value="<?php echo $ref['id_usuario']; ?>">
                                <?php echo htmlspecialchars($ref['nombres'] . ' ' . $ref['apellidos'] . ' (' . $ref['nickname'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">&nbsp;</label>
                    <div class="input-group">
                        <input type="text" 
                               class="form-control" 
                               id="search-input" 
                               placeholder="Buscar por nombre, apellido, cédula..."
                               onkeyup="filtrarLideres()">
                        <button class="btn btn-outline-secondary" onclick="filtrarLideres()">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                        <button class="btn btn-outline-secondary" onclick="limpiarFiltros()">
                            <i class="fas fa-times"></i> Limpiar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Bar con botón y estadísticas -->
        <div class="top-bar">
            <a href="administrador/anadir_lider.php" class="btn-add-user">
                <i class="fas fa-user-plus"></i> AGREGAR LÍDER
            </a>
            <a href="dashboard.php" class="btn-add-user">
                <i class="fas fa-arrow-left"></i> VOLVER A USUARIOS
            </a>
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_lideres; ?></div>
                    <div class="stat-label">Total Líderes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $lideres_activos; ?></div>
                    <div class="stat-label">Líderes Activos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $lideres_inactivos; ?></div>
                    <div class="stat-label">Líderes Inactivos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $referenciadores_asignados; ?></div>
                    <div class="stat-label">Referenciadores Asignados</div>
                </div>
            </div>
        </div>
        
        <!-- Resultados de búsqueda -->
        <div class="search-results" id="search-results">
            Mostrando <?php echo count($lideres); ?> líderes
        </div>

        <!-- Tabla de líderes -->
        <div class="table-container">
            <div class="table-header">
                <h2><i class="fas fa-list-alt"></i> Listado de Líderes</h2>
            </div>
            
            <?php if (count($lideres) > 0): ?>
            <div class="table-responsive">
                <table class="users-table" id="lideres-table">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>NOMBRES</th>
                            <th>APELLIDOS</th>
                            <th>CÉDULA</th>
                            <th>TELÉFONO</th>
                            <th>CORREO</th>
                            <th>REFERENCIADOR</th>
                            <th>ESTADO</th>
                            <th>FECHA REGISTRO</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody id="lideres-table-body">
                        <?php $consecutivo = 1; ?>
                        <?php foreach ($lideres as $lider): ?>
                        <?php 
                        $esta_activo = ($lider['estado'] === true || $lider['estado'] === 't' || $lider['estado'] == 1);
                        $fecha_registro = !empty($lider['fecha_creacion']) ? date('d/m/Y', strtotime($lider['fecha_creacion'])) : 'N/A';
                        ?>
                        <tr>
                            <td class="text-center"><?php echo $consecutivo++; ?></td>
                            <td>
                                <div class="user-info">
                                    <span class="user-fullname"><?php echo htmlspecialchars($lider['nombres']); ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="user-info">
                                    <span class="user-fullname"><?php echo htmlspecialchars($lider['apellidos']); ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="cedula"><?php echo htmlspecialchars($lider['cc']); ?></span>
                            </td>
                            <td>
                                <span class="telefono"><?php echo htmlspecialchars($lider['telefono']); ?></span>
                            </td>
                            <td>
                                <span class="correo"><?php echo htmlspecialchars($lider['correo']); ?></span>
                            </td>
                            <td>
                                <?php if (!empty($lider['referenciador_nombre'])): ?>
                                    <span class="referenciador"><?php echo htmlspecialchars($lider['referenciador_nombre']); ?></span>
                                <?php else: ?>
                                    <span class="text-muted fst-italic">Sin asignar</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($esta_activo): ?>
                                    <span class="user-status status-active">
                                        <i class="fas fa-check-circle"></i> Activo
                                    </span>
                                <?php else: ?>
                                    <span class="user-status status-inactive">
                                        <i class="fas fa-times-circle"></i> Inactivo
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="fecha-registro"><?php echo $fecha_registro; ?></span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <!-- BOTÓN DE VER DETALLE -->
                                    <button class="btn-action btn-view" 
                                            onclick="window.location.href='ver_lider.php?id=<?php echo $lider['id_lider']; ?>'"
                                            title="Ver detalle del líder">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    
                                    <!-- BOTÓN DE EDITAR -->
                                    <button class="btn-action btn-edit" 
                                            onclick="window.location.href='editar_lider.php?id=<?php echo $lider['id_lider']; ?>'"
                                            title="Editar líder">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    
                                    <?php if ($esta_activo): ?>
                                        <button class="btn-action btn-deactivate" 
                                                title="Dar de baja al líder"
                                                onclick="darDeBajaLider(<?php echo $lider['id_lider']; ?>, '<?php echo htmlspecialchars($lider['nombres'] . ' ' . $lider['apellidos']); ?>', this)">
                                            <i class="fas fa-user-slash"></i>
                                        </button>
                                    <?php else: ?>
                                        <button class="btn-action btn-activate" 
                                                title="Reactivar líder"
                                                onclick="reactivarLider(<?php echo $lider['id_lider']; ?>, '<?php echo htmlspecialchars($lider['nombres'] . ' ' . $lider['apellidos']); ?>', this)">
                                            <i class="fas fa-user-check"></i>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <!-- BOTÓN DE ELIMINAR (opcional, depende de tus necesidades) -->
                                    <button class="btn-action btn-delete" 
                                            title="Eliminar líder permanentemente"
                                            onclick="eliminarLider(<?php echo $lider['id_lider']; ?>, '<?php echo htmlspecialchars($lider['nombres'] . ' ' . $lider['apellidos']); ?>', this)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="no-data">
                <i class="fas fa-user-tie"></i>
                <h4 class="mt-3 mb-2">No hay líderes registrados</h4>
                <p class="text-muted">El sistema no tiene líderes registrados actualmente.</p>
                <a href="agregar_lider.php" class="btn-add-user mt-3">
                    <i class="fas fa-user-plus"></i> Agregar Primer Líder
                </a>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Footer del sistema -->
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
    </div>

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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        // Inicializar DataTable
        $(document).ready(function() {
            window.table = $('#lideres-table').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                },
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
                order: [[0, 'asc']],
                responsive: true,
                dom: '<"top"f>rt<"bottom"lip><"clear">',
                columnDefs: [
                    {
                        targets: 9, // Columna de Acciones
                        orderable: false,
                        searchable: false,
                        width: '160px'
                    },
                    {
                        targets: 0, // Columna N°
                        width: '60px',
                        searchable: false
                    },
                    {
                        targets: [3, 4, 8], // Cédula, Teléfono, Fecha Registro
                        width: '120px'
                    }
                ]
            });
            
            // Inicializar hover en filas
            $('#lideres-table tbody').on('mouseenter', 'tr', function() {
                $(this).css('backgroundColor', '#f8fafc');
            }).on('mouseleave', 'tr', function() {
                $(this).css('backgroundColor', '');
            });
        });
        
        // Función para mostrar el modal del sistema
        function mostrarModalSistema() {
            const modal = new bootstrap.Modal(document.getElementById('modalSistema'));
            modal.show();
        }
        
        // Función para filtrar líderes
        function filtrarLideres() {
            const estado = $('#filter-estado').val();
            const referenciador = $('#filter-referenciador').val();
            const searchTerm = $('#search-input').val();
            
            // Construir filtro combinado para DataTables
            let filter = '';
            if (searchTerm) filter += searchTerm + ' ';
            if (estado !== '') {
                const estadoText = estado === '1' ? 'Activo' : 'Inactivo';
                filter += estadoText + ' ';
            }
            
            // Aplicar búsqueda general
            table.search(filter).draw();
            
            // Actualizar mensaje de resultados
            const resultsElement = document.getElementById('search-results');
            const filteredCount = table.rows({ search: 'applied' }).count();
            const totalCount = table.rows().count();
            
            if (filter.trim() === '') {
                resultsElement.textContent = `Mostrando ${totalCount} líderes`;
            } else {
                let filtrosText = [];
                if (searchTerm) filtrosText.push(`"${searchTerm}"`);
                if (estado !== '') {
                    filtrosText.push(estado === '1' ? 'Activo' : 'Inactivo');
                }
                
                resultsElement.textContent = `Mostrando ${filteredCount} de ${totalCount} líderes (${filtrosText.join(', ')})`;
            }
        }
        
        // Función para limpiar filtros
        function limpiarFiltros() {
            $('#filter-estado').val('');
            $('#filter-referenciador').val('');
            $('#search-input').val('');
            table.search('').draw();
            $('#search-input').focus();
            document.getElementById('search-results').textContent = `Mostrando ${table.rows().count()} líderes`;
        }
        
        // Actualizar hora en tiempo real
        function updateCurrentTime() {
            const now = new Date();
            const day = now.getDate().toString().padStart(2, '0');
            const month = (now.getMonth() + 1).toString().padStart(2, '0');
            const year = now.getFullYear();
            const hours = now.getHours().toString().padStart(2, '0');
            const minutes = now.getMinutes().toString().padStart(2, '0');
            const seconds = now.getSeconds().toString().padStart(2, '0');
            const timeString = `${day}/${month}/${year} ${hours}:${minutes}:${seconds}`;
            
            const currentTimeElement = document.getElementById('current-time');
            if (currentTimeElement) {
                currentTimeElement.textContent = timeString;
            }
        }
        
        // Actualizar cada segundo
        updateCurrentTime();
        setInterval(updateCurrentTime, 1000);
        
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
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'}"></i>
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
        
        // Función para dar de baja un líder
        async function darDeBajaLider(idLider, nombre, button) {
            if (!confirm(`¿Está seguro de dar de BAJA al líder "${nombre}"?\n\nEl líder será marcado como inactivo, pero se mantendrán sus registros.`)) {
                return;
            }
            
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
            button.disabled = true;
            
            try {
                const response = await fetch('ajax/lider.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `accion=desactivar&id_lider=${idLider}`
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const row = button.closest('tr');
                    const statusBadge = row.querySelector('.user-status');
                    if (statusBadge) {
                        statusBadge.className = 'user-status status-inactive';
                        statusBadge.innerHTML = '<i class="fas fa-times-circle"></i> Inactivo';
                    }
                    
                    const newButton = document.createElement('button');
                    newButton.className = 'btn-action btn-activate';
                    newButton.title = 'Reactivar líder';
                    newButton.innerHTML = '<i class="fas fa-user-check"></i>';
                    newButton.addEventListener('click', function() {
                        reactivarLider(idLider, nombre, newButton);
                    });
                    
                    const buttonContainer = button.parentNode;
                    buttonContainer.removeChild(button);
                    buttonContainer.appendChild(newButton);
                    
                    // Actualizar estadísticas
                    updateStatsLideres(-1, 0);
                    
                    showNotification('Líder dado de baja correctamente', 'success');
                } else {
                    showNotification('Error: ' + (data.message || 'No se pudo dar de baja el líder'), 'error');
                    button.innerHTML = originalText;
                    button.disabled = false;
                }
            } catch (error) {
                showNotification('Error de conexión: ' + error.message, 'error');
                button.innerHTML = originalText;
                button.disabled = false;
            }
        }
        
        // Función para reactivar un líder
        async function reactivarLider(idLider, nombre, button) {
            if (!confirm(`¿Desea REACTIVAR al líder "${nombre}"?`)) {
                return;
            }
            
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
            button.disabled = true;
            
            try {
                const response = await fetch('ajax/lider.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `accion=reactivar&id_lider=${idLider}`
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const row = button.closest('tr');
                    const statusBadge = row.querySelector('.user-status');
                    if (statusBadge) {
                        statusBadge.className = 'user-status status-active';
                        statusBadge.innerHTML = '<i class="fas fa-check-circle"></i> Activo';
                    }
                    
                    const newButton = document.createElement('button');
                    newButton.className = 'btn-action btn-deactivate';
                    newButton.title = 'Dar de baja al líder';
                    newButton.innerHTML = '<i class="fas fa-user-slash"></i>';
                    newButton.addEventListener('click', function() {
                        darDeBajaLider(idLider, nombre, newButton);
                    });
                    
                    const buttonContainer = button.parentNode;
                    buttonContainer.removeChild(button);
                    buttonContainer.appendChild(newButton);
                    
                    // Actualizar estadísticas
                    updateStatsLideres(1, 0);
                    
                    showNotification('Líder reactivado correctamente', 'success');
                } else {
                    showNotification('Error: ' + data.message, 'error');
                    button.innerHTML = originalText;
                    button.disabled = false;
                }
            } catch (error) {
                showNotification('Error de conexión: ' + error.message, 'error');
                button.disabled = false;
            }
        }
        
        // Función para eliminar un líder permanentemente
        async function eliminarLider(idLider, nombre, button) {
            if (!confirm(`¿Está SEGURO de ELIMINAR PERMANENTEMENTE al líder "${nombre}"?\n\nEsta acción NO se puede deshacer y eliminará todos los datos asociados.`)) {
                return;
            }
            
            if (!confirm(`CONFIRMACIÓN FINAL:\n¿Realmente desea eliminar definitivamente al líder "${nombre}"?`)) {
                return;
            }
            
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Eliminando...';
            button.disabled = true;
            
            try {
                const response = await fetch('ajax/lider.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `accion=eliminar&id_lider=${idLider}`
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Eliminar la fila de la tabla
                    const row = button.closest('tr');
                    row.remove();
                    
                    // Actualizar DataTable
                    table.draw();
                    
                    // Actualizar estadísticas
                    updateStatsLideres(0, -1);
                    
                    showNotification('Líder eliminado permanentemente', 'success');
                } else {
                    showNotification('Error: ' + data.message, 'error');
                    button.innerHTML = originalText;
                    button.disabled = false;
                }
            } catch (error) {
                showNotification('Error de conexión: ' + error.message, 'error');
                button.innerHTML = originalText;
                button.disabled = false;
            }
        }
        
        // Función para actualizar estadísticas de líderes
        function updateStatsLideres(changeActivos, changeTotal) {
            const statCards = document.querySelectorAll('.stat-card');
            if (statCards.length >= 4) {
                const totalElement = statCards[0].querySelector('.stat-number');
                const activosElement = statCards[1].querySelector('.stat-number');
                const inactivosElement = statCards[2].querySelector('.stat-number');
                
                if (totalElement && changeTotal !== 0) {
                    const current = parseInt(totalElement.textContent);
                    totalElement.textContent = current + changeTotal;
                }
                
                if (activosElement && changeActivos !== 0) {
                    const current = parseInt(activosElement.textContent);
                    activosElement.textContent = current + changeActivos;
                    
                    // Actualizar contador en el header
                    const userCountElement = document.querySelector('.user-count');
                    if (userCountElement) {
                        userCountElement.textContent = (current + changeActivos) + ' líderes activos';
                    }
                }
                
                if (inactivosElement && changeActivos !== 0) {
                    const current = parseInt(inactivosElement.textContent);
                    inactivosElement.textContent = current - changeActivos;
                }
            }
        }
        
        // Manejar parámetros de éxito/error en la URL
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            
            if (urlParams.has('success')) {
                const successType = urlParams.get('success');
                let message = '';
                
                switch(successType) {
                    case 'lider_creado':
                        message = 'Líder creado correctamente';
                        break;
                    case 'lider_actualizado':
                        message = 'Líder actualizado correctamente';
                        break;
                    default:
                        message = 'Operación realizada correctamente';
                }
                
                if (message) {
                    showNotification(message, 'success');
                    window.history.replaceState({}, document.title, window.location.pathname);
                }
            }
            
            if (urlParams.has('error')) {
                const errorType = urlParams.get('error');
                let message = '';
                
                switch(errorType) {
                    case 'lider_no_encontrado':
                        message = 'Líder no encontrado';
                        break;
                    default:
                        message = 'Ocurrió un error en la operación';
                }
                
                if (message) {
                    showNotification(message, 'error');
                    window.history.replaceState({}, document.title, window.location.pathname);
                }
            }
        });
        
    </script>
    <script src="../js/contador.js"></script>
</body>
</html>