<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../models/ZonaModel.php';
require_once __DIR__ . '/../../models/SectorModel.php';
require_once __DIR__ . '/../../models/PuestoVotacionModel.php';
require_once __DIR__ . '/../../models/SistemaModel.php';

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
$sistemaModel = new SistemaModel($pdo);

// Obtener datos del usuario logueado
$usuario_logueado = $usuarioModel->getUsuarioById($_SESSION['id_usuario']);

// Obtener listas para los filtros
$zonas = $zonaModel->getAll();
$sectores = $sectorModel->getAll();
$puestosVotacion = $puestoVotacionModel->getAll();

// ============================================================================
// MANTENER: Obtener todos los referenciadores con sus estadísticas (INICIAL)
// ============================================================================
try {
    // Obtener todos los usuarios con estadísticas para cálculo global
    $todosReferenciadores = $usuarioModel->getAllUsuariosActivos();
    
    // Filtrar solo referenciadores activos para estadísticas globales
    $referenciadoresGlobales = array_filter($todosReferenciadores, function($usuario) {
        return $usuario['tipo_usuario'] === 'Referenciador' && $usuario['activo'] == true;
    });
    
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
    
    // Obtener TODOS los referenciadores para mostrar inicialmente (sin filtros)
    $referenciadoresIniciales = array_filter($todosReferenciadores, function($usuario) {
        return $usuario['tipo_usuario'] === 'Referenciador' && $usuario['activo'] == true;
    });
    
    // Ordenar inicialmente por fecha de creación (más reciente primero)
    usort($referenciadoresIniciales, function($a, $b) {
        $fecha_a = strtotime($a['fecha_creacion'] ?? '2000-01-01');
        $fecha_b = strtotime($b['fecha_creacion'] ?? '2000-01-01');
        return $fecha_b <=> $fecha_a; // Más recientes primero
    });
    
    // Calcular estadísticas para los referenciadores iniciales
    $totalIniciales = count($referenciadoresIniciales);
    
} catch (Exception $e) {
    // Manejo de error (mantener como estaba)
    $referenciadoresIniciales = [];
    $referenciadoresGlobales = [];
    $totalReferenciadores = 0;
    $totalReferidos = 0;
    $totalTope = 0;
    $porcentajeGlobal = 0;
    $totalIniciales = 0;
    error_log("Error al obtener referenciadores: " . $e->getMessage());
}

// Info del sistema (igual que antes)
$licenciaInfo = $sistemaModel->getInfoCompletaLicencia();
$infoSistema = $licenciaInfo['info'];
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
    <title>Avance Referenciadores - Super Admin - SGP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/superadmin_avance.css">
</head>
<body>
    <!-- Header (mantener igual) -->
    <header class="main-header">
        <!-- ... código del header igual ... -->
    </header>

    <!-- Breadcrumb Navigation -->
    <div class="breadcrumb-nav">
        <!-- ... breadcrumb igual ... -->
    </div>

    <!-- Main Content -->
    <div class="main-container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <!-- ... título igual ... -->
        </div>
        
        <!-- ====================================================================
             MANTENER: Estadísticas Globales - EXACTAMENTE IGUAL
        ===================================================================== -->
        <div class="global-stats">
            <div class="stats-title">
                <i class="fas fa-chart-bar"></i>
                <span>Resumen del Avance Global</span>
            </div>
            
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
        
        <!-- ====================================================================
             REEMPLAZAR: Filtros de Búsqueda - NUEVA VERSIÓN CON AJAX
        ===================================================================== -->
        <div class="filtros-container">
            <div class="filtros-title">
                <i class="fas fa-filter"></i>
                <span>Filtrar y Ordenar Referenciadores</span>
            </div>
            
            <!-- Formulario SIN action/method, solo para capturar datos -->
            <div class="filtros-form" id="filtrosForm">
                <!-- Nombre -->
                <div class="form-group">
                    <label for="nombre"><i class="fas fa-user"></i> Nombre</label>
                    <input type="text" 
                           id="nombre" 
                           class="form-control filtro-input" 
                           placeholder="Buscar por nombre..." 
                           data-filtro="nombre">
                </div>
                
                <!-- Zona -->
                <div class="form-group">
                    <label for="id_zona"><i class="fas fa-map-marker-alt"></i> Zona</label>
                    <select id="id_zona" class="form-select filtro-select" data-filtro="id_zona">
                        <option value="">Todas las zonas</option>
                        <?php foreach ($zonas as $zona): ?>
                            <option value="<?php echo $zona['id_zona']; ?>">
                                <?php echo htmlspecialchars($zona['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Sector -->
                <div class="form-group">
                    <label for="id_sector"><i class="fas fa-th-large"></i> Sector</label>
                    <select id="id_sector" class="form-select filtro-select" data-filtro="id_sector">
                        <option value="">Todos los sectores</option>
                        <?php foreach ($sectores as $sector): ?>
                            <option value="<?php echo $sector['id_sector']; ?>">
                                <?php echo htmlspecialchars($sector['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Puesto de Votación -->
                <div class="form-group">
                    <label for="id_puesto_votacion"><i class="fas fa-vote-yea"></i> Puesto de Votación</label>
                    <select id="id_puesto_votacion" class="form-select filtro-select" data-filtro="id_puesto_votacion">
                        <option value="">Todos los puestos</option>
                        <?php foreach ($puestosVotacion as $puesto): ?>
                            <option value="<?php echo $puesto['id_puesto']; ?>">
                                <?php echo htmlspecialchars($puesto['nombre'] . ' (' . $puesto['sector_nombre'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Porcentaje Mínimo -->
                <div class="form-group">
                    <label for="porcentaje_minimo"><i class="fas fa-percentage"></i> % Avance Mínimo</label>
                    <select id="porcentaje_minimo" class="form-select filtro-select" data-filtro="porcentaje_minimo">
                        <option value="">Todos los porcentajes</option>
                        <option value="100">100% (Completado)</option>
                        <option value="90">90% o más</option>
                        <option value="75">75% o más</option>
                        <option value="50">50% o más</option>
                        <option value="25">25% o más</option>
                        <option value="0">Con algún avance</option>
                    </select>
                </div>
                
                <!-- Ordenar por -->
                <div class="form-group filtro-orden">
                    <label for="ordenar_por"><i class="fas fa-sort-amount-down"></i> Ordenar por</label>
                    <select id="ordenar_por" class="form-select filtro-select" data-filtro="ordenar_por">
                        <option value="fecha_creacion">Fecha de creación</option>
                        <option value="porcentaje_desc">% Avance</option>
                        <option value="referidos_desc">Cantidad de Referidos</option>
                    </select>
                </div>
                
                <!-- Fecha de Acceso -->
                <div class="form-group">
                    <label for="fecha_acceso"><i class="fas fa-calendar-alt"></i> Último acceso</label>
                    <input type="date" 
                           id="fecha_acceso" 
                           class="form-control filtro-input" 
                           data-filtro="fecha_acceso">
                </div>
                
                <!-- Botones de acción -->
                <div class="form-group filtros-actions">
                    <button type="button" class="btn-buscar" id="btnBuscarAjax">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                    <button type="button" class="btn-limpiar" id="btnLimpiarAjax">
                        <i class="fas fa-times"></i> Limpiar
                    </button>
                </div>
            </div>
            
            <!-- Contenedor para filtros activos (se llena dinámicamente) -->
            <div class="active-filters" id="activeFiltersContainer" style="display: none;">
                <div class="filter-section-title">
                    <i class="fas fa-check-circle"></i> Filtros aplicados:
                </div>
                <div id="activeFiltersList"></div>
            </div>
        </div>
        
        <!-- Info de resultados (dinámica) -->
        <div class="resultados-info" id="resultadosInfo" style="display: none;">
            <div class="resultados-text">
                <i class="fas fa-info-circle"></i>
                <span id="resultadosText"></span>
            </div>
        </div>
        
        <!-- ====================================================================
             MODIFICAR: Lista de Referenciadores - Mostrar inicial + actualizar con AJAX
        ===================================================================== -->
        <div class="referenciadores-list" id="referenciadoresContainer">
            <div class="list-title">
                <i class="fas fa-list-ol"></i>
                <span>Progreso Individual por Referenciador</span>
                <span id="filtroIndicator" class="badge bg-info ms-2" style="display: none;">Filtrados</span>
            </div>
            
            <!-- Loading indicator (oculto inicialmente) -->
            <div id="loadingIndicator" class="text-center py-5" style="display: none;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2 text-muted">Buscando referenciadores...</p>
            </div>
            
            <!-- Contenedor para los resultados (inicialmente con datos PHP) -->
            <div id="referenciadoresList">
                <?php if (empty($referenciadoresIniciales)): ?>
                    <div class="no-data">
                        <i class="fas fa-users-slash"></i>
                        <p>No hay referenciadores activos registrados en el sistema.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($referenciadoresIniciales as $referenciador): ?>
                        <?php 
                        $porcentaje = $referenciador['porcentaje_tope'] ?? 0;
                        if (!$porcentaje && $referenciador['tope'] > 0) {
                            $porcentaje = round(($referenciador['total_referenciados'] / $referenciador['tope']) * 100, 2);
                        }
                        
                        // Determinar clase de color según porcentaje
                        $progressClass = 'progress-bajo';
                        if ($porcentaje >= 75) $progressClass = 'progress-excelente';
                        elseif ($porcentaje >= 50) $progressClass = 'progress-bueno';
                        elseif ($porcentaje >= 25) $progressClass = 'progress-medio';
                        ?>
                        
                        <div class="referenciador-card" data-id="<?php echo $referenciador['id_usuario']; ?>">
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
                                            <?php if ($porcentaje >= 100): ?>
                                                <span style="color: #4caf50; margin-left: 5px; font-size: 0.8rem;">
                                                    <i class="fas fa-check-circle"></i> Completado
                                                </span>
                                            <?php elseif ($porcentaje >= 75): ?>
                                                <span style="color: #2196f3; margin-left: 5px; font-size: 0.8rem;">
                                                    <i class="fas fa-trophy"></i> Avanzado
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="user-info-text">
                                            <span>Cédula: <?php echo htmlspecialchars($referenciador['cedula'] ?? 'N/A'); ?></span>
                                            <span>Usuario: <?php echo htmlspecialchars($referenciador['nickname'] ?? 'N/A'); ?></span>
                                            <span>Fecha registro: <?php echo date('d/m/Y', strtotime($referenciador['fecha_creacion'])); ?></span>
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
                                        <div class="stat-number <?php echo $porcentaje >= 100 ? 'text-success' : ($porcentaje >= 75 ? 'text-primary' : ''); ?>">
                                            <?php echo $porcentaje; ?>%
                                        </div>
                                        <div class="stat-desc">Avance</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Barra de Progreso Individual -->
                            <div class="individual-progress">
                                <div class="progress-label-small">
                                    Progreso individual: <?php echo $referenciador['total_referenciados'] ?? 0; ?> de <?php echo $referenciador['tope'] ?? 0; ?> referidos
                                    <span style="float: right; font-weight: bold; color: <?php echo $porcentaje >= 100 ? '#4caf50' : ($porcentaje >= 75 ? '#2196f3' : '#666'); ?>">
                                        <?php echo $porcentaje; ?>%
                                    </span>
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
            
            <!-- Paginación (se llena dinámicamente con AJAX) -->
            <div id="paginacionContainer" class="pagination-container mt-4" style="display: none;">
                <!-- Aquí se insertará la paginación desde AJAX -->
            </div>
        </div>
    </div>

    <!-- Footer (mantener igual) -->
    <footer class="system-footer">
        <!-- ... footer igual ... -->
    </footer>

    <!-- Modal de Información del Sistema -->
    <div class="modal fade modal-system-info" id="modalSistema" tabindex="-1" aria-labelledby="modalSistemaLabel" aria-hidden="true">
        <!-- ... modal igual ... -->
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Variable global para controlar las peticiones AJAX
        var filtrosActuales = {};
        var timerBusqueda;
        
        // ====================================================================
        // FUNCIONES PRINCIPALES PARA EL SISTEMA DE FILTRADO AJAX
        // ====================================================================
        
        // 1. Función para obtener valores de los filtros
        function obtenerFiltros() {
            return {
                nombre: $('#nombre').val().trim(),
                id_zona: $('#id_zona').val() || 0,
                id_sector: $('#id_sector').val() || 0,
                id_puesto_votacion: $('#id_puesto_votacion').val() || 0,
                fecha_acceso: $('#fecha_acceso').val() || '',
                porcentaje_minimo: $('#porcentaje_minimo').val() || 0,
                ordenar_por: $('#ordenar_por').val() || 'fecha_creacion',
                page: 1, // Siempre empezar en página 1 al filtrar
                limit: 50 // Puedes ajustar esto
            };
        }
        
        // 2. Función para mostrar/ocultar filtros activos
        function actualizarFiltrosActivos(filtros) {
            var container = $('#activeFiltersContainer');
            var list = $('#activeFiltersList');
            list.empty();
            
            var filtrosMostrar = [];
            
            // Nombre
            if (filtros.nombre) {
                filtrosMostrar.push({
                    key: 'nombre',
                    label: 'Nombre: ' + filtros.nombre
                });
            }
            
            // Zona
            if (filtros.id_zona > 0) {
                var zonaNombre = $('#id_zona option:selected').text();
                filtrosMostrar.push({
                    key: 'id_zona',
                    label: 'Zona: ' + zonaNombre
                });
            }
            
            // Sector
            if (filtros.id_sector > 0) {
                var sectorNombre = $('#id_sector option:selected').text();
                filtrosMostrar.push({
                    key: 'id_sector',
                    label: 'Sector: ' + sectorNombre
                });
            }
            
            // Puesto de votación
            if (filtros.id_puesto_votacion > 0) {
                var puestoNombre = $('#id_puesto_votacion option:selected').text();
                filtrosMostrar.push({
                    key: 'id_puesto_votacion',
                    label: 'Puesto: ' + puestoNombre
                });
            }
            
            // Porcentaje mínimo
            if (filtros.porcentaje_minimo > 0) {
                var porcentajeText = '';
                switch(filtros.porcentaje_minimo.toString()) {
                    case '100': porcentajeText = '100% (Completado)'; break;
                    case '90': porcentajeText = '90% o más'; break;
                    case '75': porcentajeText = '75% o más'; break;
                    case '50': porcentajeText = '50% o más'; break;
                    case '25': porcentajeText = '25% o más'; break;
                    case '0': porcentajeText = 'Con algún avance'; break;
                    default: porcentajeText = filtros.porcentaje_minimo + '% o más';
                }
                filtrosMostrar.push({
                    key: 'porcentaje_minimo',
                    label: 'Avance mínimo: ' + porcentajeText
                });
            }
            
            // Fecha acceso
            if (filtros.fecha_acceso) {
                filtrosMostrar.push({
                    key: 'fecha_acceso',
                    label: 'Último acceso: ' + filtros.fecha_acceso
                });
            }
            
            // Ordenar por (si no es el default)
            if (filtros.ordenar_por !== 'fecha_creacion') {
                var ordenText = '';
                switch(filtros.ordenar_por) {
                    case 'porcentaje_desc': ordenText = 'Orden: % Avance'; break;
                    case 'referidos_desc': ordenText = 'Orden: Cant. Referidos'; break;
                }
                filtrosMostrar.push({
                    key: 'ordenar_por',
                    label: ordenText
                });
            }
            
            // Mostrar u ocultar contenedor
            if (filtrosMostrar.length > 0) {
                container.show();
                $('#filtroIndicator').show();
                
                // Crear badges para cada filtro
                filtrosMostrar.forEach(function(filtro) {
                    var badge = $('<span class="filter-badge">')
                        .html(filtro.label + ' <span class="close" data-filtro="' + filtro.key + '">&times;</span>')
                        .appendTo(list);
                });
            } else {
                container.hide();
                $('#filtroIndicator').hide();
            }
        }
        
        // 3. Función principal para buscar con AJAX
        function buscarReferenciadores() {
            filtrosActuales = obtenerFiltros();
            
            // Mostrar loading
            $('#loadingIndicator').show();
            $('#referenciadoresList').hide();
            $('#paginacionContainer').hide();
            
            // Actualizar filtros activos
            actualizarFiltrosActivos(filtrosActuales);
            
            // Construir URL para AJAX
            var url = '../ajax/filtros_referenciadores.php?' + $.param(filtrosActuales);
            
            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        mostrarResultados(response.data);
                    } else {
                        mostrarError(response.error || 'Error en la búsqueda');
                    }
                },
                error: function(xhr, status, error) {
                    mostrarError('Error de conexión: ' + error);
                },
                complete: function() {
                    $('#loadingIndicator').hide();
                }
            });
        }
        
        // 4. Función para mostrar resultados
        function mostrarResultados(data) {
            var container = $('#referenciadoresList');
            container.empty();
            
            // Actualizar info de resultados
            if (data.filtrosActivos) {
                $('#resultadosInfo').show();
                $('#resultadosText').html(
                    'Mostrando <strong>' + data.paginacion.mostrandoDesde + '-' + data.paginacion.mostrandoHasta + 
                    '</strong> de <strong>' + data.paginacion.totalResultados + '</strong> referenciadores filtrados'
                );
            } else {
                $('#resultadosInfo').hide();
            }
            
            // Mostrar estadísticas filtradas (opcional, si quieres mostrarlas)
            console.log('Estadísticas filtradas:', data.estadisticas);
            
            // Mostrar referenciadores
            if (data.referenciadores.length === 0) {
                var noResults = $('<div class="no-results">')
                    .html('<i class="fas fa-search"></i><p>No se encontraron referenciadores con los filtros aplicados.</p>')
                    .appendTo(container);
                
                if (data.filtrosActivos) {
                    $('<button type="button" class="btn-reset-filters" onclick="limpiarFiltros()">')
                        .html('<i class="fas fa-times"></i> Limpiar filtros')
                        .appendTo(noResults);
                }
            } else {
                data.referenciadores.forEach(function(referenciador) {
                    var porcentaje = referenciador.porcentaje_tope || 0;
                    
                    // Determinar clase de progreso
                    var progressClass = 'progress-bajo';
                    if (porcentaje >= 75) progressClass = 'progress-excelente';
                    else if (porcentaje >= 50) progressClass = 'progress-bueno';
                    else if (porcentaje >= 25) progressClass = 'progress-medio';
                    
                    // Crear tarjeta
                    var card = $('<div class="referenciador-card">')
                        .attr('data-id', referenciador.id_usuario)
                        .appendTo(container);
                    
                    // Construir contenido (similar al PHP)
                    var html = `
                        <div class="referenciador-header">
                            <div class="user-info-section">
                                <div class="user-avatar">
                                    ${referenciador.foto_url ? 
                                        '<img src="' + referenciador.foto_url + '" alt="Foto">' : 
                                        '<div style="width: 100%; height: 100%; background: #eaeaea; display: flex; align-items: center; justify-content: center;">' +
                                        '<i class="fas fa-user" style="color: #95a5a6; font-size: 1.5rem;"></i></div>'}
                                </div>
                                <div class="user-details">
                                    <div class="user-name">
                                        ${referenciador.nombres} ${referenciador.apellidos}
                                        ${porcentaje >= 100 ? 
                                            '<span style="color: #4caf50; margin-left: 5px; font-size: 0.8rem;"><i class="fas fa-check-circle"></i> Completado</span>' : 
                                            (porcentaje >= 75 ? 
                                                '<span style="color: #2196f3; margin-left: 5px; font-size: 0.8rem;"><i class="fas fa-trophy"></i> Avanzado</span>' : '')}
                                    </div>
                                    <div class="user-info-text">
                                        <span>Cédula: ${referenciador.cedula || 'N/A'}</span>
                                        <span>Usuario: ${referenciador.nickname || 'N/A'}</span>
                                        <span>Fecha registro: ${new Date(referenciador.fecha_creacion).toLocaleDateString('es-ES')}</span>
                                        ${referenciador.ultimo_registro ? 
                                            '<span>Último acceso: ' + new Date(referenciador.ultimo_registro).toLocaleString('es-ES') + '</span>' : ''}
                                    </div>
                                </div>
                            </div>
                            
                            <div class="user-stats">
                                <div class="stat-item ${filtrosActuales.ordenar_por === 'referidos_desc' ? 'stat-destacado' : ''}">
                                    <div class="stat-number">${referenciador.total_referenciados || 0}</div>
                                    <div class="stat-desc">Referidos</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number">${referenciador.tope || 0}</div>
                                    <div class="stat-desc">Tope</div>
                                </div>
                                <div class="stat-item ${filtrosActuales.ordenar_por === 'porcentaje_desc' ? 'stat-destacado' : ''}">
                                    <div class="stat-number ${porcentaje >= 100 ? 'text-success' : (porcentaje >= 75 ? 'text-primary' : '')}">
                                        ${porcentaje}%
                                    </div>
                                    <div class="stat-desc">Avance</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="individual-progress">
                            <div class="progress-label-small">
                                Progreso individual: ${referenciador.total_referenciados || 0} de ${referenciador.tope || 0} referidos
                                <span style="float: right; font-weight: bold; color: ${porcentaje >= 100 ? '#4caf50' : (porcentaje >= 75 ? '#2196f3' : '#666')}">
                                    ${porcentaje}%
                                </span>
                            </div>
                            <div class="progress-container-small">
                                <div class="progress-bar-small ${progressClass}" 
                                     style="width: ${porcentaje}%">
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
                    `;
                    
                    card.html(html);
                });
            }
            
            // Mostrar paginación
            if (data.paginacion.totalPaginas > 1) {
                mostrarPaginacion(data.paginacion);
            } else {
                $('#paginacionContainer').hide();
            }
            
            // Mostrar contenedor
            container.show();
            
            // Animación de barras
            setTimeout(function() {
                $('.progress-bar-small').each(function() {
                    var width = $(this).css('width');
                    $(this).css('width', '0');
                    $(this).animate({ width: width }, 1000);
                });
            }, 300);
        }
        
        // 5. Función para mostrar paginación
        function mostrarPaginacion(paginacion) {
            var container = $('#paginacionContainer');
            container.empty().show();
            
            var paginacionHTML = `
                <nav aria-label="Paginación de referenciadores">
                    <ul class="pagination justify-content-center">
            `;
            
            // Botón anterior
            paginacionHTML += `
                <li class="page-item ${paginacion.paginaActual === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="cambiarPagina(${paginacion.paginaActual - 1})" aria-label="Anterior">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
            `;
            
            // Páginas
            for (var i = 1; i <= paginacion.totalPaginas; i++) {
                if (i === 1 || i === paginacion.totalPaginas || 
                    (i >= paginacion.paginaActual - 2 && i <= paginacion.paginaActual + 2)) {
                    
                    paginacionHTML += `
                        <li class="page-item ${i === paginacion.paginaActual ? 'active' : ''}">
                            <a class="page-link" href="#" onclick="cambiarPagina(${i})">${i}</a>
                        </li>
                    `;
                } else if (i === paginacion.paginaActual - 3 || i === paginacion.paginaActual + 3) {
                    paginacionHTML += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
            }
            
            // Botón siguiente
            paginacionHTML += `
                <li class="page-item ${paginacion.paginaActual === paginacion.totalPaginas ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="cambiarPagina(${paginacion.paginaActual + 1})" aria-label="Siguiente">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            `;
            
            paginacionHTML += `
                    </ul>
                </nav>
                <div class="text-center text-muted mt-2">
                    Mostrando ${paginacion.mostrandoDesde}-${paginacion.mostrandoHasta} de ${paginacion.totalResultados} resultados
                </div>
            `;
            
            container.html(paginacionHTML);
        }
        
        // 6. Función para cambiar de página
        function cambiarPagina(pagina) {
            filtrosActuales.page = pagina;
            
            // Actualizar URL de AJAX con nueva página
            var url = '../ajax/filtros_referenciadores.php?' + $.param(filtrosActuales);
            
            $('#loadingIndicator').show();
            
            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        mostrarResultados(response.data);
                        // Scroll suave hacia la lista
                        $('html, body').animate({
                            scrollTop: $('#referenciadoresContainer').offset().top - 20
                        }, 500);
                    }
                },
                complete: function() {
                    $('#loadingIndicator').hide();
                }
            });
        }
        
        // 7. Función para limpiar filtros
        function limpiarFiltros() {
            // Limpiar todos los campos
            $('.filtro-input').val('');
            $('.filtro-select').val('');
            $('#ordenar_por').val('fecha_creacion');
            
            // Ocultar contenedores
            $('#activeFiltersContainer').hide();
            $('#resultadosInfo').hide();
            $('#filtroIndicator').hide();
            
            // Resetear resultados a estado inicial
            $('#loadingIndicator').show();
            $('#referenciadoresList').hide();
            $('#paginacionContainer').hide();
            
            // Recargar la página para mostrar datos iniciales (o hacer AJAX sin filtros)
            setTimeout(function() {
                location.reload(); // Opción 1: Recargar página
                // Opción 2: Hacer AJAX sin filtros:
                // filtrosActuales = {page: 1, limit: 50, ordenar_por: 'fecha_creacion'};
                // buscarReferenciadores();
            }, 300);
        }
        
        // 8. Función para mostrar error
        function mostrarError(mensaje) {
            $('#referenciadoresList').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> ${mensaje}
                </div>
            `).show();
        }
        
        // ====================================================================
        // EVENT LISTENERS
        // ====================================================================
        
        $(document).ready(function() {
            // Buscar al hacer clic en botón
            $('#btnBuscarAjax').click(buscarReferenciadores);
            
            // Limpiar filtros
            $('#btnLimpiarAjax').click(limpiarFiltros);
            
            // Búsqueda en tiempo real en campo de nombre
            $('#nombre').on('keyup', function() {
                clearTimeout(timerBusqueda);
                timerBusqueda = setTimeout(function() {
                    if ($('#nombre').val().trim().length >= 3 || $('#nombre').val().trim().length === 0) {
                        buscarReferenciadores();
                    }
                }, 500);
            });
            
            // Cambios en selects (excepto ordenar que no dispara búsqueda automática)
            $('#id_zona, #id_sector, #id_puesto_votacion, #porcentaje_minimo, #fecha_acceso').change(function() {
                buscarReferenciadores();
            });
            
            // Ordenar por - búsqueda al cambiar
            $('#ordenar_por').change(function() {
                buscarReferenciadores();
            });
            
            // Eliminar filtro individual al hacer clic en X
            $(document).on('click', '.filter-badge .close', function() {
                var filtroKey = $(this).data('filtro');
                
                // Limpiar ese filtro específico
                $('#' + filtroKey).val('');
                if (filtroKey === 'ordenar_por') {
                    $('#' + filtroKey).val('fecha_creacion');
                }
                
                // Volver a buscar
                buscarReferenciadores();
            });
            
            // Efecto hover en tarjetas
            $(document).on('mouseenter', '.referenciador-card', function() {
                $(this).css('transform', 'translateY(-5px)');
                $(this).css('box-shadow', '0 5px 15px rgba(0,0,0,0.1)');
            }).on('mouseleave', '.referenciador-card', function() {
                $(this).css('transform', 'translateY(0)');
                $(this).css('box-shadow', '0 2px 5px rgba(0,0,0,0.05)');
            });
        });
    </script>
    <script src="../js/modal-sistema.js"></script>
</body>
</html>