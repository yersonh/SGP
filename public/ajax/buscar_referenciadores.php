<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../models/ZonaModel.php';
require_once __DIR__ . '/../../models/SectorModel.php';
require_once __DIR__ . '/../../models/PuestoVotacionModel.php';

// Verificar si el usuario está logueado y es SuperAdmin
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado']);
    exit();
}

// Verificar si es una solicitud AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'error' => 'Solicitud no válida']);
    exit();
}

$pdo = Database::getConnection();
$usuarioModel = new UsuarioModel($pdo);
$zonaModel = new ZonaModel($pdo);
$sectorModel = new SectorModel($pdo);
$puestoVotacionModel = new PuestoVotacionModel($pdo);

// Obtener parámetros de filtro
$filtros = [
    'nombre' => $_GET['nombre'] ?? '',
    'id_zona' => $_GET['id_zona'] ?? '',
    'id_sector' => $_GET['id_sector'] ?? '',
    'id_puesto_votacion' => $_GET['id_puesto_votacion'] ?? '',
    'fecha_acceso' => $_GET['fecha_acceso'] ?? ''
];

// Obtener listas para los filtros (para los nombres en los badges)
$zonas = $zonaModel->getAll();
$sectores = $sectorModel->getAll();
$puestosVotacion = $puestoVotacionModel->getAll();

try {
    // Obtener todos los usuarios con estadísticas
    $todosReferenciadores = $usuarioModel->getAllUsuarios();
    
    // Aplicar filtros
    $referenciadores = array_filter($todosReferenciadores, function($usuario) use ($filtros, $zonaModel, $sectorModel, $puestoVotacionModel, $usuarioModel) {
        // Filtrar por tipo de usuario y activo
        if ($usuario['tipo_usuario'] !== 'Referenciador' || $usuario['activo'] != true) {
            return false;
        }
        
        // Filtrar por nombre (nombres o apellidos)
        if (!empty($filtros['nombre'])) {
            $nombreCompleto = $usuario['nombres'] . ' ' . $usuario['apellidos'];
            if (stripos($nombreCompleto, $filtros['nombre']) === false && 
                stripos($usuario['nombres'], $filtros['nombre']) === false &&
                stripos($usuario['apellidos'], $filtros['nombre']) === false) {
                return false;
            }
        }
        
        // Filtrar por zona
        if (!empty($filtros['id_zona']) && $usuario['id_zona'] != $filtros['id_zona']) {
            return false;
        }
        
        // Filtrar por sector
        if (!empty($filtros['id_sector']) && $usuario['id_sector'] != $filtros['id_sector']) {
            return false;
        }
        
        // Filtrar por puesto de votación
        if (!empty($filtros['id_puesto_votacion'])) {
            // Obtener el sector del puesto de votación
            $puesto = $puestoVotacionModel->getById($filtros['id_puesto_votacion']);
            if ($puesto && isset($puesto['id_sector'])) {
                // Obtener el usuario completo con sus relaciones
                $usuarioCompleto = $usuarioModel->getUsuarioById($usuario['id_usuario']);
                if ($usuarioCompleto && $usuarioCompleto['id_sector'] != $puesto['id_sector']) {
                    return false;
                }
            }
        }
        
        // Filtrar por fecha de último acceso (exacta)
        if (!empty($filtros['fecha_acceso']) && !empty($usuario['ultimo_registro'])) {
            $fechaUltimoAcceso = new DateTime($usuario['ultimo_registro']);
            $fechaFiltro = new DateTime($filtros['fecha_acceso']);
            
            // Comparar solo la fecha (sin horas/minutos)
            if ($fechaUltimoAcceso->format('Y-m-d') != $fechaFiltro->format('Y-m-d')) {
                return false;
            }
        }
        
        return true;
    });

    // Reindexar array después del filtro
    $referenciadores = array_values($referenciadores);
    
    // Calcular estadísticas para los filtrados
    $referenciadoresFiltradosCount = count($referenciadores);
    
    // Prepara los referenciadores para la vista
    $referenciadoresParaVista = [];
    foreach ($referenciadores as $referenciador) {
        $porcentaje = $referenciador['porcentaje_tope'] ?? 0;
        if (!isset($referenciador['porcentaje_tope']) && $referenciador['tope'] > 0) {
            $porcentaje = round(($referenciador['total_referenciados'] / $referenciador['tope']) * 100, 2);
        }
        
        // Limitar al 100%
        if ($porcentaje > 100) {
            $porcentaje = 100;
        }
        
        $referenciador['porcentaje_tope'] = $porcentaje;
        $referenciadoresParaVista[] = $referenciador;
    }
    
    // Generar HTML de los resultados
    ob_start();
    ?>
    <!-- Resultados de búsqueda -->
    <?php if (!empty($filtros['nombre']) || !empty($filtros['id_zona']) || !empty($filtros['id_sector']) || !empty($filtros['id_puesto_votacion']) || !empty($filtros['fecha_acceso'])): ?>
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
            <span>Progreso Individual por Referenciador <?php echo (!empty($filtros['nombre']) || !empty($filtros['id_zona']) || !empty($filtros['id_sector']) || !empty($filtros['id_puesto_votacion']) || !empty($filtros['fecha_acceso'])) ? '(Filtrados)' : ''; ?></span>
        </div>
        
        <?php if (empty($referenciadoresParaVista)): ?>
            <?php if (!empty($filtros['nombre']) || !empty($filtros['id_zona']) || !empty($filtros['id_sector']) || !empty($filtros['id_puesto_votacion']) || !empty($filtros['fecha_acceso'])): ?>
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
            <?php foreach ($referenciadoresParaVista as $referenciador): ?>
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
    <?php
    $html = ob_get_clean();
    
    // Preparar datos para los badges de filtros activos
    $filtrosActivos = [];
    if (!empty($filtros['nombre'])) {
        $filtrosActivos['nombre'] = [
            'valor' => $filtros['nombre'],
            'etiqueta' => "Nombre: " . htmlspecialchars($filtros['nombre'])
        ];
    }
    
    if (!empty($filtros['id_zona'])) {
        $zonaNombre = '';
        foreach ($zonas as $zona) {
            if ($zona['id_zona'] == $filtros['id_zona']) {
                $zonaNombre = $zona['nombre'];
                break;
            }
        }
        $filtrosActivos['id_zona'] = [
            'valor' => $filtros['id_zona'],
            'etiqueta' => "Zona: " . htmlspecialchars($zonaNombre)
        ];
    }
    
    if (!empty($filtros['id_sector'])) {
        $sectorNombre = '';
        foreach ($sectores as $sector) {
            if ($sector['id_sector'] == $filtros['id_sector']) {
                $sectorNombre = $sector['nombre'];
                break;
            }
        }
        $filtrosActivos['id_sector'] = [
            'valor' => $filtros['id_sector'],
            'etiqueta' => "Sector: " . htmlspecialchars($sectorNombre)
        ];
    }
    
    if (!empty($filtros['id_puesto_votacion'])) {
        $puestoNombre = '';
        foreach ($puestosVotacion as $puesto) {
            if ($puesto['id_puesto'] == $filtros['id_puesto_votacion']) {
                $puestoNombre = $puesto['nombre'] . ' (' . $puesto['sector_nombre'] . ')';
                break;
            }
        }
        $filtrosActivos['id_puesto_votacion'] = [
            'valor' => $filtros['id_puesto_votacion'],
            'etiqueta' => "Puesto: " . htmlspecialchars($puestoNombre)
        ];
    }
    
    if (!empty($filtros['fecha_acceso'])) {
        $filtrosActivos['fecha_acceso'] = [
            'valor' => $filtros['fecha_acceso'],
            'etiqueta' => "Último acceso: " . date('d/m/Y', strtotime($filtros['fecha_acceso']))
        ];
    }
    
    // Crear array de respuesta
    $response = [
        'success' => true,
        'html' => $html,
        'count' => $referenciadoresFiltradosCount,
        'filtros_activos' => $filtrosActivos,
        'tiene_filtros' => !empty($filtrosActivos)
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'success' => false,
        'error' => 'Error al procesar la búsqueda: ' . $e->getMessage()
    ]);
}
?>