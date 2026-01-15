<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../models/ZonaModel.php';
require_once __DIR__ . '/../../models/SectorModel.php';
require_once __DIR__ . '/../../models/PuestoVotacionModel.php';

// Verificar si el usuario está logueado y es SuperAdmin
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    header('Location: ../index.php');
    exit();
}

$pdo = Database::getConnection();
$usuarioModel = new UsuarioModel($pdo);
$zonaModel = new ZonaModel($pdo);
$sectorModel = new SectorModel($pdo);
$puestoVotacionModel = new PuestoVotacionModel($pdo);

// Obtener datos del usuario logueado
$usuario_logueado = $usuarioModel->getUsuarioById($_SESSION['id_usuario']);

// Obtener listas para los filtros
$zonas = $zonaModel->getAll();
$sectores = $sectorModel->getAll();
$puestosVotacion = $puestoVotacionModel->getAll();

// Procesar filtros del formulario
$filtros = [
    'nombre' => $_GET['nombre'] ?? '',
    'id_zona' => $_GET['id_zona'] ?? '',
    'id_sector' => $_GET['id_sector'] ?? '',
    'id_puesto_votacion' => $_GET['id_puesto_votacion'] ?? '',
    'fecha_acceso' => $_GET['fecha_acceso'] ?? ''
];

// Obtener todos los referenciadores con sus estadísticas (SIN FILTROS para estadísticas globales)
try {
    // Obtener todos los usuarios con estadísticas para cálculo global
    $todosUsuarios = $usuarioModel->getAllUsuarios();
    
    // Filtrar solo referenciadores activos para estadísticas globales
    $referenciadoresGlobales = [];
    foreach ($todosUsuarios as $usuario) {
        if ($usuario['tipo_usuario'] === 'Referenciador' && $usuario['activo'] == true) {
            $referenciadoresGlobales[] = $usuario;
        }
    }
    
    // Calcular estadísticas globales (SIEMPRE con todos los referenciadores)
    $totalReferenciadores = count($referenciadoresGlobales);
    $totalReferidos = 0;
    $totalTope = 0;
    
    foreach ($referenciadoresGlobales as &$referenciadorGlobal) {
        $totalReferidos += $referenciadorGlobal['total_referenciados'] ?? 0;
        $totalTope += $referenciadorGlobal['tope'] ?? 0;
        
        // Calcular porcentaje individual si no viene del modelo
        if (!isset($referenciadorGlobal['porcentaje_tope']) && $referenciadorGlobal['tope'] > 0) {
            $referenciadorGlobal['porcentaje_tope'] = round(($referenciadorGlobal['total_referenciados'] / $referenciadorGlobal['tope']) * 100, 2);
        }
        
        // Limitar al 100%
        if ($referenciadorGlobal['porcentaje_tope'] > 100) {
            $referenciadorGlobal['porcentaje_tope'] = 100;
        }
    }
    
    // Calcular porcentaje global
    $porcentajeGlobal = 0;
    if ($totalTope > 0) {
        $porcentajeGlobal = round(($totalReferidos / $totalTope) * 100, 2);
        $porcentajeGlobal = min($porcentajeGlobal, 100);
    }
    
    // Ahora aplicar filtros solo para la lista de referenciadores
    $referenciadores = [];
    $idsProcesados = []; // Para evitar duplicados
    
    foreach ($todosUsuarios as $usuario) {
        // Verificar si ya procesamos este ID
        if (in_array($usuario['id_usuario'], $idsProcesados)) {
            continue;
        }
        
        // Filtrar por tipo de usuario y activo
        if ($usuario['tipo_usuario'] !== 'Referenciador' || $usuario['activo'] != true) {
            continue;
        }
        
        $idsProcesados[] = $usuario['id_usuario'];
        
        // Filtrar por nombre (nombres o apellidos) - búsqueda en tiempo real
        if (!empty($filtros['nombre'])) {
            $nombreCompleto = $usuario['nombres'] . ' ' . $usuario['apellidos'];
            if (stripos($nombreCompleto, $filtros['nombre']) === false && 
                stripos($usuario['nombres'], $filtros['nombre']) === false &&
                stripos($usuario['apellidos'], $filtros['nombre']) === false) {
                continue;
            }
        }
        
        // Filtrar por zona
        if (!empty($filtros['id_zona']) && $usuario['id_zona'] != $filtros['id_zona']) {
            continue;
        }
        
        // Filtrar por sector
        if (!empty($filtros['id_sector']) && $usuario['id_sector'] != $filtros['id_sector']) {
            continue;
        }
        
        // Filtrar por puesto de votación
        if (!empty($filtros['id_puesto_votacion'])) {
            // Obtener el sector del puesto de votación
            $puesto = $puestoVotacionModel->getById($filtros['id_puesto_votacion']);
            if ($puesto && isset($puesto['id_sector'])) {
                // Obtener el usuario completo con sus relaciones
                $usuarioCompleto = $usuarioModel->getUsuarioById($usuario['id_usuario']);
                if ($usuarioCompleto && $usuarioCompleto['id_sector'] != $puesto['id_sector']) {
                    continue;
                }
            }
        }
        
        // Filtrar por fecha de último acceso (exacta o posterior)
        if (!empty($filtros['fecha_acceso']) && !empty($usuario['ultimo_registro'])) {
            $fechaUltimoAcceso = new DateTime($usuario['ultimo_registro']);
            $fechaFiltro = new DateTime($filtros['fecha_acceso']);
            
            // Comparar solo la fecha (sin horas/minutos)
            if ($fechaUltimoAcceso->format('Y-m-d') != $fechaFiltro->format('Y-m-d')) {
                continue;
            }
        }
        
        // Si pasa todos los filtros, agregar al array
        $referenciadores[] = $usuario;
    }
    
    // Calcular estadísticas solo para los filtrados (para mostrar en resultados)
    $referenciadoresFiltradosCount = count($referenciadores);
    $totalReferidosFiltrados = 0;
    $totalTopeFiltrados = 0;
    
    foreach ($referenciadores as &$referenciador) {
        $totalReferidosFiltrados += $referenciador['total_referenciados'] ?? 0;
        $totalTopeFiltrados += $referenciador['tope'] ?? 0;
        
        // Calcular porcentaje individual si no viene del modelo
        if (!isset($referenciador['porcentaje_tope']) && $referenciador['tope'] > 0) {
            $referenciador['porcentaje_tope'] = round(($referenciador['total_referenciados'] / $referenciador['tope']) * 100, 2);
        }
        
        // Limitar al 100%
        if ($referenciador['porcentaje_tope'] > 100) {
            $referenciador['porcentaje_tope'] = 100;
        }
    }
    
    // Calcular porcentaje filtrado
    $porcentajeFiltrado = 0;
    if ($totalTopeFiltrados > 0) {
        $porcentajeFiltrado = round(($totalReferidosFiltrados / $totalTopeFiltrados) * 100, 2);
        $porcentajeFiltrado = min($porcentajeFiltrado, 100);
    }
    
} catch (Exception $e) {
    $referenciadores = [];
    $referenciadoresGlobales = [];
    $totalReferenciadores = 0;
    $totalReferidos = 0;
    $totalTope = 0;
    $porcentajeGlobal = 0;
    $referenciadoresFiltradosCount = 0;
    error_log("Error al obtener referenciadores: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avance Referenciadores - Super Admin - SGP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/superadmin_avance.css">
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="header-container">
            <div class="header-top">
                <div class="header-title">
                    <h1><i class="fas fa-chart-line"></i> Avance Referenciadores - Super Admin</h1>
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
                <li class="breadcrumb-item">
                    <a href="superadmin_avance.php" class="text-decoration-none">
                        <i class="fas fa-chart-line"></i> Avance Referenciadores
                    </a>
                </li>
            </ol>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="dashboard-title">
                <i class="fas fa-users-line"></i>
                <span>Monitoreo de Avance - Referenciadores</span>
            </div>
            <p class="dashboard-subtitle">
                Visualice el progreso de todos los referenciadores activos del sistema. 
                Compare el avance individual y global vs las metas establecidas.
            </p>
        </div>
        
        <!-- Estadísticas Globales - MEJORADA (SIEMPRE muestra todos) -->
        <div class="global-stats">
            <div class="stats-title">
                <i class="fas fa-chart-bar"></i>
                <span>Resumen del Avance Global</span>
            </div>
            
            <!-- Grid de 2 filas: Estadísticas principales arriba, barra abajo -->
            <div class="stats-main-container">
                <!-- Primera fila: 4 estadísticas en línea -->
                <div class="stats-row">
                    <div class="stats-box">
                        <div class="stats-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stats-content">
                            <div class="stats-value"><?php echo $totalReferenciadores; ?></div>
                            <div class="stats-label">Referenciadores Activos</div>
                        </div>
                    </div>
                    
                    <div class="stats-box">
                        <div class="stats-icon">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="stats-content">
                            <div class="stats-value"><?php echo number_format($totalReferidos, 0, ',', '.'); ?></div>
                            <div class="stats-label">Referidos Registrados</div>
                        </div>
                    </div>
                    
                    <div class="stats-box">
                        <div class="stats-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <div class="stats-content">
                            <div class="stats-value"><?php echo number_format($totalTope, 0, ',', '.'); ?></div>
                            <div class="stats-label">Meta Total</div>
                        </div>
                    </div>
                    
                    <div class="stats-box">
                        <div class="stats-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stats-content">
                            <div class="stats-value"><?php echo $porcentajeGlobal; ?>%</div>
                            <div class="stats-label">Avance Global</div>
                        </div>
                    </div>
                </div>
                
                <!-- Segunda fila: Barra de progreso completa -->
                <div class="progress-row">
                    <div class="progress-header">
                        <span class="progress-title">Progreso Global del Sistema</span>
                        <span class="progress-percentage"><?php echo $porcentajeGlobal; ?>%</span>
                    </div>
                    <div class="progress-bar-container">
                        <div class="progress-track">
                            <div class="progress-fill" style="width: <?php echo $porcentajeGlobal; ?>%"></div>
                        </div>
                        <div class="progress-markers">
                            <span class="marker">0%</span>
                            <span class="marker">25%</span>
                            <span class="marker">50%</span>
                            <span class="marker">75%</span>
                            <span class="marker">100%</span>
                        </div>
                    </div>
                    <div class="progress-footer">
                        <span class="progress-current"><?php echo number_format($totalReferidos, 0, ',', '.'); ?> referidos registrados</span>
                        <span class="progress-target">Meta: <?php echo number_format($totalTope, 0, ',', '.'); ?> referidos</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- NUEVO: Filtros de Búsqueda (debajo de estadísticas globales) -->
        <div class="filtros-container">
            <div class="filtros-title">
                <i class="fas fa-filter"></i>
                <span>Filtrar Referenciadores</span>
            </div>
            
            <form method="GET" action="" class="filtros-form" id="filtrosForm">
                <!-- Nombre (con búsqueda en tiempo real) -->
                <div class="form-group">
                    <label for="nombre"><i class="fas fa-user"></i> Nombre</label>
                    <input type="text" 
                           id="nombre" 
                           name="nombre" 
                           class="form-control" 
                           placeholder="Buscar por nombre..." 
                           value="<?php echo htmlspecialchars($filtros['nombre']); ?>"
                           onkeyup="actualizarFiltros()">
                </div>
                
                <!-- Zona -->
                <div class="form-group">
                    <label for="id_zona"><i class="fas fa-map-marker-alt"></i> Zona</label>
                    <select id="id_zona" name="id_zona" class="form-select" onchange="actualizarFiltros()">
                        <option value="">Todas las zonas</option>
                        <?php foreach ($zonas as $zona): ?>
                            <option value="<?php echo $zona['id_zona']; ?>" 
                                <?php echo $filtros['id_zona'] == $zona['id_zona'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($zona['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Sector -->
                <div class="form-group">
                    <label for="id_sector"><i class="fas fa-th-large"></i> Sector</label>
                    <select id="id_sector" name="id_sector" class="form-select" onchange="actualizarFiltros()">
                        <option value="">Todos los sectores</option>
                        <?php foreach ($sectores as $sector): ?>
                            <option value="<?php echo $sector['id_sector']; ?>" 
                                <?php echo $filtros['id_sector'] == $sector['id_sector'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sector['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Puesto de Votación -->
                <div class="form-group">
                    <label for="id_puesto_votacion"><i class="fas fa-vote-yea"></i> Puesto de Votación</label>
                    <select id="id_puesto_votacion" name="id_puesto_votacion" class="form-select" onchange="actualizarFiltros()">
                        <option value="">Todos los puestos</option>
                        <?php foreach ($puestosVotacion as $puesto): ?>
                            <option value="<?php echo $puesto['id_puesto']; ?>" 
                                <?php echo $filtros['id_puesto_votacion'] == $puesto['id_puesto'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($puesto['nombre'] . ' (' . $puesto['sector_nombre'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Última Fecha de Acceso (solo una fecha) -->
                <div class="form-group">
                    <label for="fecha_acceso"><i class="fas fa-calendar-alt"></i> Último acceso</label>
                    <input type="date" 
                           id="fecha_acceso" 
                           name="fecha_acceso" 
                           class="form-control" 
                           value="<?php echo htmlspecialchars($filtros['fecha_acceso']); ?>"
                           onchange="actualizarFiltros()">
                </div>
                
                <!-- Botones de acción -->
                <div class="form-group filtros-actions">
                    <button type="button" class="btn-buscar" onclick="actualizarFiltros()">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                    <button type="button" class="btn-limpiar" onclick="limpiarFiltros()">
                        <i class="fas fa-times"></i> Limpiar
                    </button>
                </div>
            </form>
            
            <!-- Filtros activos -->
            <?php 
            $filtrosActivos = array_filter($filtros, function($valor) {
                return !empty($valor);
            });
            
            if (!empty($filtrosActivos)): ?>
            <div class="active-filters">
                <div class="filter-section-title">
                    <i class="fas fa-check-circle"></i> Filtros aplicados:
                </div>
                <div>
                    <?php foreach ($filtrosActivos as $clave => $valor): 
                        $etiqueta = '';
                        $valorMostrar = htmlspecialchars($valor);
                        
                        switch($clave) {
                            case 'nombre':
                                $etiqueta = "Nombre: $valorMostrar";
                                break;
                            case 'id_zona':
                                $zonaNombre = '';
                                foreach ($zonas as $zona) {
                                    if ($zona['id_zona'] == $valor) {
                                        $zonaNombre = $zona['nombre'];
                                        break;
                                    }
                                }
                                $etiqueta = "Zona: " . htmlspecialchars($zonaNombre);
                                break;
                            case 'id_sector':
                                $sectorNombre = '';
                                foreach ($sectores as $sector) {
                                    if ($sector['id_sector'] == $valor) {
                                        $sectorNombre = $sector['nombre'];
                                        break;
                                    }
                                }
                                $etiqueta = "Sector: " . htmlspecialchars($sectorNombre);
                                break;
                            case 'id_puesto_votacion':
                                $puestoNombre = '';
                                foreach ($puestosVotacion as $puesto) {
                                    if ($puesto['id_puesto'] == $valor) {
                                        $puestoNombre = $puesto['nombre'] . ' (' . $puesto['sector_nombre'] . ')';
                                        break;
                                    }
                                }
                                $etiqueta = "Puesto: " . htmlspecialchars($puestoNombre);
                                break;
                            case 'fecha_acceso':
                                $etiqueta = "Último acceso: " . date('d/m/Y', strtotime($valor));
                                break;
                        }
                        
                        if (!empty($etiqueta)):
                    ?>
                        <span class="filter-badge" id="filtro-<?php echo $clave; ?>">
                            <?php echo $etiqueta; ?>
                            <span class="close" onclick="eliminarFiltro('<?php echo $clave; ?>')">&times;</span>
                        </span>
                    <?php 
                        endif;
                    endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- NUEVO: Resultados de búsqueda -->
        <?php if (!empty($filtrosActivos)): ?>
        <div class="resultados-info">
            <div class="resultados-text">
                <i class="fas fa-info-circle"></i>
                <span>
                    Mostrando <span class="resultados-count"><?php echo $referenciadoresFiltradosCount; ?></span> 
                    referenciador(es) que coinciden con los filtros aplicados
                </span>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Lista de Referenciadores -->
        <div class="referenciadores-list">
            <div class="list-title">
                <i class="fas fa-list-ol"></i>
                <span>Progreso Individual por Referenciador <?php echo !empty($filtrosActivos) ? '(Filtrados)' : ''; ?></span>
            </div>
            
            <?php if (empty($referenciadores)): ?>
                <?php if (!empty($filtrosActivos)): ?>
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <p>No se encontraron referenciadores con los filtros aplicados.</p>
                    <button type="button" class="btn-reset-filters" onclick="limpiarFiltros()">
                        <i class="fas fa-times"></i> Limpiar filtros
                    </button>
                </div>
                <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-users-slash"></i>
                    <p>No hay referenciadores activos registrados en el sistema.</p>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <?php foreach ($referenciadores as $referenciador): ?>
                    <?php 
                    $porcentaje = $referenciador['porcentaje_tope'] ?? 0;
                    
                    // Determinar clase de color según porcentaje
                    $progressClass = 'progress-bajo';
                    if ($porcentaje >= 75) $progressClass = 'progress-excelente';
                    elseif ($porcentaje >= 50) $progressClass = 'progress-bueno';
                    elseif ($porcentaje >= 25) $progressClass = 'progress-medio';
                    ?>
                    
                    <div class="referenciador-card">
                        <div class="referenciador-header">
                            <div class="user-info-section">
                                <div class="user-avatar">
                                    <?php if (!empty($referenciador['foto_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($referenciador['foto_url']); ?>" alt="Foto">
                                    <?php else: ?>
                                        <div style="width: 100%; height: 100%; background: #eaeaea; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-user" style="color: #95a5a6; font-size: 1.5rem;"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="user-details">
                                    <div class="user-name">
                                        <?php echo htmlspecialchars($referenciador['nombres'] . ' ' . $referenciador['apellidos']); ?>
                                    </div>
                                    <div class="user-info-text">
                                        <span>Cédula: <?php echo htmlspecialchars($referenciador['cedula'] ?? 'N/A'); ?></span>
                                        <span>Usuario: <?php echo htmlspecialchars($referenciador['nickname'] ?? 'N/A'); ?></span>
                                        <?php if (!empty($referenciador['ultimo_registro'])): ?>
                                        <span>Último acceso: <?php echo date('d/m/Y H:i', strtotime($referenciador['ultimo_registro'])); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="user-stats">
                                <div class="stat-item">
                                    <div class="stat-number"><?php echo $referenciador['total_referenciados'] ?? 0; ?></div>
                                    <div class="stat-desc">Referidos</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number"><?php echo $referenciador['tope'] ?? 0; ?></div>
                                    <div class="stat-desc">Tope</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number"><?php echo $porcentaje; ?>%</div>
                                    <div class="stat-desc">Avance</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Barra de Progreso Individual -->
                        <div class="individual-progress">
                            <div class="progress-label-small">
                                Progreso individual: <?php echo $referenciador['total_referenciados'] ?? 0; ?> de <?php echo $referenciador['tope'] ?? 0; ?> referidos
                            </div>
                            <div class="progress-container-small">
                                <div class="progress-bar-small <?php echo $progressClass; ?>" 
                                     style="width: <?php echo $porcentaje; ?>%">
                                </div>
                            </div>
                            <div class="progress-numbers">
                                <span>0%</span>
                                <span>25%</span>
                                <span>50%</span>
                                <span>75%</span>
                                <span>100%</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="system-footer">
        <div class="container">
            <p>© Derechos de autor Reservados. 
                Ing. Rubén Darío González García • 
                SISGONTech • Colombia © • <?php echo date('Y'); ?>
            </p>
            <p>Contacto: +57 3106310227 • 
                Email: sisgonnet@gmail.com
            </p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Timer para búsqueda en tiempo real
        var timer;
        
        // Función para actualizar filtros (búsqueda en tiempo real)
        function actualizarFiltros() {
            clearTimeout(timer);
            timer = setTimeout(function() {
                // Obtener valores del formulario
                var nombre = $('#nombre').val();
                var id_zona = $('#id_zona').val();
                var id_sector = $('#id_sector').val();
                var id_puesto_votacion = $('#id_puesto_votacion').val();
                var fecha_acceso = $('#fecha_acceso').val();
                
                // Construir query string
                var params = [];
                if (nombre) params.push('nombre=' + encodeURIComponent(nombre));
                if (id_zona) params.push('id_zona=' + encodeURIComponent(id_zona));
                if (id_sector) params.push('id_sector=' + encodeURIComponent(id_sector));
                if (id_puesto_votacion) params.push('id_puesto_votacion=' + encodeURIComponent(id_puesto_votacion));
                if (fecha_acceso) params.push('fecha_acceso=' + encodeURIComponent(fecha_acceso));
                
                // Recargar la página con los nuevos parámetros
                var url = 'superadmin_avance.php';
                if (params.length > 0) {
                    url += '?' + params.join('&');
                }
                
                window.location.href = url;
            }, 800); // 800ms de delay para evitar múltiples peticiones
        }
        
        // Función para limpiar todos los filtros
        function limpiarFiltros() {
            window.location.href = 'superadmin_avance.php';
        }
        
        // Función para eliminar un filtro específico
        function eliminarFiltro(filtro) {
            // Obtener parámetros actuales
            var urlParams = new URLSearchParams(window.location.search);
            
            // Eliminar el filtro específico
            urlParams.delete(filtro);
            
            // Construir nueva URL
            var newUrl = 'superadmin_avance.php';
            if (urlParams.toString()) {
                newUrl += '?' + urlParams.toString();
            }
            
            window.location.href = newUrl;
        }
        
        $(document).ready(function() {
            // Efecto de animación para las barras de progreso al cargar
            $('.progress-bar-small').each(function() {
                var width = $(this).css('width');
                $(this).css('width', '0');
                
                setTimeout(() => {
                    $(this).animate({
                        width: width
                    }, 1000);
                }, 300);
            });
            
            // Efecto hover en tarjetas
            $('.referenciador-card').hover(
                function() {
                    $(this).css('transform', 'translateY(-5px)');
                },
                function() {
                    $(this).css('transform', 'translateY(0)');
                }
            );
            
            // Mejorar UX: Auto-focus en el campo de búsqueda
            $('#nombre').focus();
            
            // Actualizar estadísticas cada 30 segundos (opcional)
            setInterval(function() {
                // Aquí podrías agregar una llamada AJAX para actualizar en tiempo real
                // si necesitas datos en vivo
            }, 30000);
        });
    </script>
</body>
</html>