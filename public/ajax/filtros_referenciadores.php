<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../models/ZonaModel.php';
require_once __DIR__ . '/../../models/SectorModel.php';
require_once __DIR__ . '/../../models/PuestoVotacionModel.php';

// Validar petición AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    exit(json_encode(['error' => 'Acceso denegado']));
}

// Verificar autorización
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    http_response_code(401);
    exit(json_encode(['error' => 'No autorizado']));
}

// Validar y sanitizar parámetros
$filtros = [
    'nombre' => trim(filter_input(INPUT_GET, 'nombre', FILTER_SANITIZE_STRING) ?? ''),
    'id_zona' => filter_input(INPUT_GET, 'id_zona', FILTER_VALIDATE_INT) ?: 0,
    'id_sector' => filter_input(INPUT_GET, 'id_sector', FILTER_VALIDATE_INT) ?: 0,
    'id_puesto_votacion' => filter_input(INPUT_GET, 'id_puesto_votacion', FILTER_VALIDATE_INT) ?: 0,
    'fecha_acceso' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['fecha_acceso'] ?? '') ? $_GET['fecha_acceso'] : '',
    'porcentaje_minimo' => filter_input(INPUT_GET, 'porcentaje_minimo', FILTER_VALIDATE_FLOAT) ?: 0.0,
    'ordenar_por' => in_array($_GET['ordenar_por'] ?? '', ['fecha_creacion', 'porcentaje_desc', 'referidos_desc']) 
        ? $_GET['ordenar_por'] 
        : 'fecha_creacion',
    'page' => max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1),
    'limit' => min(100, max(10, filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 50))
];

try {
    $pdo = Database::getConnection();
    $usuarioModel = new UsuarioModel($pdo);
    $zonaModel = new ZonaModel($pdo);
    $sectorModel = new SectorModel($pdo);
    $puestoVotacionModel = new PuestoVotacionModel($pdo);
    
    // Cache para datos estáticos (5 minutos)
    $cacheKey = 'cache_filtros_' . md5(serialize([$filtros['id_zona'], $filtros['id_sector']]));
    if (!isset($_SESSION[$cacheKey]) || $_SESSION[$cacheKey . '_time'] < time() - 300) {
        $zonas = $zonaModel->getAll();
        $sectores = $sectorModel->getAll();
        $puestosVotacion = $puestoVotacionModel->getAll();
        
        $_SESSION[$cacheKey] = [
            'zonas' => $zonas,
            'sectores' => $sectores,
            'puestosVotacion' => $puestosVotacion
        ];
        $_SESSION[$cacheKey . '_time'] = time();
    } else {
        extract($_SESSION[$cacheKey]);
    }
    
    // Obtener todos los referenciadores con sus estadísticas
    $todosReferenciadores = $usuarioModel->getAllUsuariosActivos();
    
    // Filtrar solo referenciadores activos para estadísticas globales
    $referenciadoresGlobales = array_filter($todosReferenciadores, function($usuario) {
        return $usuario['tipo_usuario'] === 'Referenciador' && $usuario['activo'] == true;
    });
    
    // Calcular estadísticas globales
    $totalReferenciadores = count($referenciadoresGlobales);
    $totalReferidos = 0;
    $totalTope = 0;
    
    foreach ($referenciadoresGlobales as &$referenciadorGlobal) {
        $totalReferidos += $referenciadorGlobal['total_referenciados'] ?? 0;
        $totalTope += $referenciadorGlobal['tope'] ?? 0;
        
        if (!isset($referenciadorGlobal['porcentaje_tope']) && $referenciadorGlobal['tope'] > 0) {
            $referenciadorGlobal['porcentaje_tope'] = round(($referenciadorGlobal['total_referenciados'] / $referenciadorGlobal['tope']) * 100, 2);
        }
        
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
    
    // Aplicar filtros para la lista de referenciadores
    $referenciadores = array_filter($todosReferenciadores, function($usuario) use ($filtros, $usuarioModel) {
        if ($usuario['tipo_usuario'] !== 'Referenciador' || $usuario['activo'] != true) {
            return false;
        }
        
        // Filtrar por nombre
        if (!empty($filtros['nombre'])) {
            $nombreCompleto = $usuario['nombres'] . ' ' . $usuario['apellidos'];
            if (stripos($nombreCompleto, $filtros['nombre']) === false && 
                stripos($usuario['nombres'], $filtros['nombre']) === false &&
                stripos($usuario['apellidos'], $filtros['nombre']) === false) {
                return false;
            }
        }
        
        // Filtrar por zona
        if ($filtros['id_zona'] > 0 && $usuario['id_zona'] != $filtros['id_zona']) {
            return false;
        }
        
        // Filtrar por sector
        if ($filtros['id_sector'] > 0 && $usuario['id_sector'] != $filtros['id_sector']) {
            return false;
        }
        
        // Filtrar por puesto de votación
        if ($filtros['id_puesto_votacion'] > 0) {
            $usuarioCompleto = $usuarioModel->getUsuarioByIdActivo($usuario['id_usuario']);
            if ($usuarioCompleto && $usuarioCompleto['id_puesto_votacion'] != $filtros['id_puesto_votacion']) {
                return false;
            }
        }
        
        // Filtrar por fecha de último acceso
        if (!empty($filtros['fecha_acceso']) && !empty($usuario['ultimo_registro'])) {
            try {
                $fechaUltimoAcceso = new DateTime($usuario['ultimo_registro']);
                $fechaFiltro = new DateTime($filtros['fecha_acceso']);
                if ($fechaUltimoAcceso->format('Y-m-d') != $fechaFiltro->format('Y-m-d')) {
                    return false;
                }
            } catch (Exception $e) {
                // Si hay error en el formato de fecha, no filtrar por esta condición
                error_log("Error en formato de fecha: " . $e->getMessage());
            }
        }
        
        // Filtrar por porcentaje mínimo
        if ($filtros['porcentaje_minimo'] > 0) {
            $porcentaje_usuario = 0;
            if (isset($usuario['porcentaje_tope'])) {
                $porcentaje_usuario = $usuario['porcentaje_tope'];
            } elseif ($usuario['tope'] > 0) {
                $porcentaje_usuario = round(($usuario['total_referenciados'] / $usuario['tope']) * 100, 2);
            }
            
            if ($porcentaje_usuario < $filtros['porcentaje_minimo']) {
                return false;
            }
        }
        
        return true;
    });

    $referenciadores = array_values($referenciadores);
    
    // Ordenar según el filtro seleccionado
    usort($referenciadores, function($a, $b) use ($filtros) {
        switch ($filtros['ordenar_por']) {
            case 'porcentaje_desc':
                $porcentaje_a = $a['porcentaje_tope'] ?? 0;
                $porcentaje_b = $b['porcentaje_tope'] ?? 0;
                if (!$porcentaje_a && $a['tope'] > 0) {
                    $porcentaje_a = round(($a['total_referenciados'] / $a['tope']) * 100, 2);
                }
                if (!$porcentaje_b && $b['tope'] > 0) {
                    $porcentaje_b = round(($b['total_referenciados'] / $b['tope']) * 100, 2);
                }
                return $porcentaje_b <=> $porcentaje_a;
                
            case 'referidos_desc':
                $referidos_a = $a['total_referenciados'] ?? 0;
                $referidos_b = $b['total_referenciados'] ?? 0;
                return $referidos_b <=> $referidos_a;
                
            case 'fecha_creacion':
            default:
                $fecha_a = strtotime($a['fecha_creacion'] ?? '2000-01-01');
                $fecha_b = strtotime($b['fecha_creacion'] ?? '2000-01-01');
                return $fecha_b <=> $fecha_a; // Más recientes primero
        }
    });
    
    // Calcular estadísticas filtradas
    $referenciadoresFiltradosCount = count($referenciadores);
    $totalReferidosFiltrados = 0;
    $totalTopeFiltrados = 0;
    
    foreach ($referenciadores as &$referenciador) {
        $totalReferidosFiltrados += $referenciador['total_referenciados'] ?? 0;
        $totalTopeFiltrados += $referenciador['tope'] ?? 0;
        
        if (!isset($referenciador['porcentaje_tope']) && $referenciador['tope'] > 0) {
            $referenciador['porcentaje_tope'] = round(($referenciador['total_referenciados'] / $referenciador['tope']) * 100, 2);
        }
        
        if ($referenciador['porcentaje_tope'] > 100) {
            $referenciador['porcentaje_tope'] = 100;
        }
        
        // Asegurar que todos los campos necesarios existan
        $referenciador['id_zona'] = $referenciador['id_zona'] ?? 0;
        $referenciador['id_sector'] = $referenciador['id_sector'] ?? 0;
        $referenciador['id_puesto_votacion'] = $referenciador['id_puesto_votacion'] ?? 0;
        $referenciador['ultimo_registro'] = $referenciador['ultimo_registro'] ?? null;
        $referenciador['fecha_creacion'] = $referenciador['fecha_creacion'] ?? date('Y-m-d H:i:s');
    }
    
    $porcentajeFiltrado = 0;
    if ($totalTopeFiltrados > 0) {
        $porcentajeFiltrado = round(($totalReferidosFiltrados / $totalTopeFiltrados) * 100, 2);
        $porcentajeFiltrado = min($porcentajeFiltrado, 100);
    }
    
    // Aplicar paginación
    $totalPaginas = ceil($referenciadoresFiltradosCount / $filtros['limit']);
    $offset = ($filtros['page'] - 1) * $filtros['limit'];
    $referenciadoresPaginados = array_slice($referenciadores, $offset, $filtros['limit']);
    
    // Determinar si hay filtros activos (excluyendo paginación y ordenación por defecto)
    $filtrosActivos = false;
    foreach ($filtros as $clave => $valor) {
        if (in_array($clave, ['page', 'limit', 'ordenar_por'])) {
            continue;
        }
        
        if (!empty($valor) && ($clave != 'ordenar_por' || $valor != 'fecha_creacion')) {
            $filtrosActivos = true;
            break;
        }
    }
    
    // Preparar datos para JSON
    $datosRespuesta = [
        'success' => true,
        'data' => [
            'estadisticas' => [
                'totalReferenciadores' => $totalReferenciadores,
                'totalReferidos' => $totalReferidos,
                'totalTope' => $totalTope,
                'porcentajeGlobal' => $porcentajeGlobal,
                'referenciadoresFiltradosCount' => $referenciadoresFiltradosCount,
                'totalReferidosFiltrados' => $totalReferidosFiltrados,
                'totalTopeFiltrados' => $totalTopeFiltrados,
                'porcentajeFiltrado' => $porcentajeFiltrado
            ],
            'referenciadores' => $referenciadoresPaginados,
            'paginacion' => [
                'paginaActual' => $filtros['page'],
                'totalPaginas' => $totalPaginas,
                'totalResultados' => $referenciadoresFiltradosCount,
                'resultadosPorPagina' => $filtros['limit'],
                'mostrandoDesde' => min($offset + 1, $referenciadoresFiltradosCount),
                'mostrandoHasta' => min($offset + $filtros['limit'], $referenciadoresFiltradosCount)
            ],
            'filtrosActivos' => $filtrosActivos,
            'totalResultados' => $referenciadoresFiltradosCount,
            'filtrosAplicados' => array_filter($filtros, function($v, $k) {
                return !empty($v) && !in_array($k, ['page', 'limit']);
            }, ARRAY_FILTER_USE_BOTH)
        ],
        'metadata' => [
            'timestamp' => date('Y-m-d H:i:s'),
            'cache' => isset($_SESSION[$cacheKey . '_time']) ? 
                (time() - $_SESSION[$cacheKey . '_time']) . ' segundos' : 
                'no cache'
        ]
    ];
    
    // Devolver JSON con los datos procesados
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($datosRespuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Throwable $e) {
    error_log("Error en filtros_referenciadores: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor',
        'debug' => (ini_get('display_errors') ? [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ] : '')
    ], JSON_UNESCAPED_UNICODE);
}
?>