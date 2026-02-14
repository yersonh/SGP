<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/LlamadaModel.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../models/ReferenciadoModel.php';

// Verificar si el usuario está logueado y es SuperAdmin
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    header('HTTP/1.1 403 Forbidden');
    exit('Acceso denegado');
}

$pdo = Database::getConnection();
$llamadaModel = new LlamadaModel($pdo);
$usuarioModel = new UsuarioModel($pdo);
$referenciadoModel = new ReferenciadoModel($pdo);

// Obtener parámetros de filtro
$filtros = [
    'fecha' => $_GET['fecha'] ?? date('Y-m-d'),
    'tipo_resultado' => $_GET['tipo_resultado'] ?? 'todos',
    'rating' => $_GET['rating'] ?? 'todos',
    'rango' => $_GET['rango'] ?? 'hoy'
];

// Manejar rango personalizado
if ($filtros['rango'] === 'personalizado') {
    if (!empty($_GET['fecha_desde'])) {
        $filtros['fecha_desde'] = $_GET['fecha_desde'];
    }
    if (!empty($_GET['fecha_hasta'])) {
        $filtros['fecha_hasta'] = $_GET['fecha_hasta'];
    }
}

// Obtener datos del reporte
try {
    // 1. Datos para el resumen
    $datosResumen = obtenerDatosResumen($llamadaModel, $filtros);
    
    // 2. Detalle de llamadas
    $detalleLlamadas = obtenerDetalleLlamadas($llamadaModel, $filtros);
    
    // 3. Top llamadores
    $topLlamadores = $llamadaModel->getTopLlamadoresConFiltros(10, $filtros);
    
    // 4. Distribución por hora
    $llamadasPorHora = obtenerLlamadasPorHora($llamadaModel, $filtros);
    
    // Configurar headers para archivo Excel
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="reporte_tracking_' . date('Y-m-d_H-i-s') . '.xls"');
    header('Cache-Control: max-age=0');
    
    // Crear contenido HTML para Excel
    generarExcel($datosResumen, $detalleLlamadas, $topLlamadores, $llamadasPorHora, $filtros);
    
} catch (Exception $e) {
    die('Error al generar reporte: ' . $e->getMessage());
}

// ==================================================
// FUNCIONES AUXILIARES
// ==================================================

/**
 * Obtener datos para el resumen - VERSIÓN CORREGIDA PARA POSTGRESQL
 */
function obtenerDatosResumen($llamadaModel, $filtros) {
    $whereInfo = $llamadaModel->construirWhereFiltros($filtros);
    $where = $whereInfo['where'];
    $params = $whereInfo['params'];
    
    $pdo = $llamadaModel->getConnection();
    
    $sql = "SELECT 
                COUNT(*) as total_llamadas,
                COUNT(CASE WHEN lt.id_resultado = 1 THEN 1 END) as contactos_efectivos,
                CASE 
                    WHEN COUNT(*) > 0 THEN 
                        ROUND((COUNT(CASE WHEN lt.id_resultado = 1 THEN 1 END)::numeric / COUNT(*)::numeric) * 100, 2)
                    ELSE 0 
                END as porcentaje_contactos,
                COALESCE(ROUND(AVG(lt.rating)::numeric, 2), 0) as rating_promedio,
                COUNT(DISTINCT lt.id_usuario) as llamadores_activos,
                TO_CHAR(MODE() WITHIN GROUP (ORDER BY EXTRACT(HOUR FROM lt.fecha_llamada)), 'FM00') || ':00' as hora_pico,
                COUNT(CASE WHEN lt.rating = 5 THEN 1 END) as rating_5,
                COUNT(CASE WHEN lt.rating = 4 THEN 1 END) as rating_4,
                COUNT(CASE WHEN lt.rating = 3 THEN 1 END) as rating_3,
                COUNT(CASE WHEN lt.rating = 2 THEN 1 END) as rating_2,
                COUNT(CASE WHEN lt.rating = 1 THEN 1 END) as rating_1,
                COUNT(CASE WHEN lt.rating IS NULL OR lt.rating = 0 THEN 1 END) as sin_rating
            FROM llamadas_tracking lt
            {$where}";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Obtener detalle de llamadas - VERSIÓN CORREGIDA
 */
function obtenerDetalleLlamadas($llamadaModel, $filtros) {
    $whereInfo = $llamadaModel->construirWhereFiltros($filtros);
    $where = $whereInfo['where'];
    $params = $whereInfo['params'];
    
    $pdo = $llamadaModel->getConnection();
    
    $sql = "SELECT 
                TO_CHAR(lt.fecha_llamada, 'DD/MM/YYYY HH24:MI') as fecha_hora,
                CONCAT(u.nombres, ' ', u.apellidos) as llamador,
                u.cedula as cedula_llamador,
                CONCAT(r.nombre, ' ', r.apellido) as referenciado,
                r.cedula as cedula_referenciado,
                lt.telefono,
                trl.nombre as resultado,
                CASE 
                    WHEN lt.rating = 5 THEN '★★★★★'
                    WHEN lt.rating = 4 THEN '★★★★☆'
                    WHEN lt.rating = 3 THEN '★★★☆☆'
                    WHEN lt.rating = 2 THEN '★★☆☆☆'
                    WHEN lt.rating = 1 THEN '★☆☆☆☆'
                    ELSE 'Sin rating'
                END as rating_estrellas,
                lt.rating as rating_numero,
                lt.observaciones
            FROM llamadas_tracking lt
            INNER JOIN usuario u ON lt.id_usuario = u.id_usuario
            INNER JOIN referenciados r ON lt.id_referenciado = r.id_referenciado
            LEFT JOIN tipos_resultado_llamada trl ON lt.id_resultado = trl.id_resultado
            {$where}
            ORDER BY lt.fecha_llamada DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtener llamadas por hora - VERSIÓN CORREGIDA
 */
function obtenerLlamadasPorHora($llamadaModel, $filtros) {
    $whereInfo = $llamadaModel->construirWhereFiltros($filtros);
    $where = $whereInfo['where'];
    $params = $whereInfo['params'];
    
    $pdo = $llamadaModel->getConnection();
    
    $sql = "SELECT 
                EXTRACT(HOUR FROM lt.fecha_llamada) as hora,
                COUNT(*) as cantidad,
                COALESCE(ROUND(AVG(lt.rating)::numeric, 2), 0) as rating_promedio
            FROM llamadas_tracking lt
            {$where}
            GROUP BY EXTRACT(HOUR FROM lt.fecha_llamada)
            ORDER BY hora ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtener descripción del rango
 */
function getDescripcionRango($filtros) {
    if (!empty($filtros['fecha_desde']) && !empty($filtros['fecha_hasta'])) {
        return "Personalizado: " . date('d/m/Y', strtotime($filtros['fecha_desde'])) . 
               " al " . date('d/m/Y', strtotime($filtros['fecha_hasta']));
    }
    
    switch ($filtros['rango']) {
        case 'hoy': 
            return "Hoy: " . date('d/m/Y');
        case 'ayer': 
            $ayer = date('d/m/Y', strtotime('-1 day'));
            return "Ayer: " . $ayer;
        case 'semana': 
            return "Esta semana";
        case 'mes': 
            return "Este mes";
        default: 
            return "Hoy: " . date('d/m/Y');
    }
}

/**
 * Obtener nombre del resultado por ID
 */
function obtenerNombreResultado($pdo, $idResultado) {
    if (!$idResultado) return 'Sin resultado';
    
    $sql = "SELECT nombre FROM tipos_resultado_llamada WHERE id_resultado = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $idResultado]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result['nombre'] ?? 'Desconocido';
}

/**
 * Generar archivo Excel en HTML - UNA SOLA HOJA CON TODO Y TÍTULOS
 */
function generarExcel($resumen, $detalle, $topLlamadores, $llamadasPorHora, $filtros) {
    // Iniciar HTML
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<!--[if gte mso 9]>';
    echo '<xml>';
    echo '<x:ExcelWorkbook>';
    echo '<x:ExcelWorksheets>';
    
    // ============================================
    // UNA SOLA HOJA: Reporte Tracking (con todo)
    // ============================================
    echo '<x:ExcelWorksheet>';
    echo '<x:Name>Reporte Tracking</x:Name>';
    echo '<x:WorksheetOptions>';
    echo '<x:DisplayGridlines/>';
    echo '</x:WorksheetOptions>';
    echo '</x:ExcelWorksheet>';
    
    echo '</x:ExcelWorksheets>';
    echo '</x:ExcelWorkbook>';
    echo '</xml>';
    echo '<![endif]-->';
    echo '<style>';
    echo 'td { mso-number-format:\@; }';
    echo '.titulo { font-size: 18px; font-weight: bold; color: #2c3e50; }';
    echo '.subtitulo { font-size: 14px; color: #7f8c8d; }';
    echo '.resaltado { background-color: #ecf0f1; font-weight: bold; }';
    echo '.verde { background-color: #d5f4e6; }';
    echo '.amarillo { background-color: #fefbd8; }';
    echo '.rojo { background-color: #fadbd8; }';
    echo '.centrado { text-align: center; }';
    echo '.seccion-titulo { background-color: #3498db; color: white; font-weight: bold; font-size: 16px; padding: 10px; margin-top: 30px; margin-bottom: 10px; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    // ==================================================
    // ENCABEZADO DEL REPORTE
    // ==================================================
    echo '<h1 class="titulo">REPORTE DE TRACKING DE LLAMADAS</h1>';
    echo '<p class="subtitulo">Fecha de generación: ' . date('d/m/Y H:i:s') . '</p>';
    echo '<p class="subtitulo">Rango: ' . getDescripcionRango($filtros) . '</p>';
    echo '<br>';
    
    // ==================================================
    // SECCIÓN 1: RESUMEN GENERAL
    // ==================================================
    echo '<div class="seccion-titulo">📊 RESUMEN GENERAL</div>';
    echo '<br>';
    
    echo '<table border="1" cellpadding="5" cellspacing="0">';
    echo '<tr style="background-color: #3498db; color: white; font-weight: bold;">';
    echo '<th colspan="2">MÉTRICAS PRINCIPALES</th>';
    echo '</tr>';
    
    $metricas = [
        'Total de Llamadas' => $resumen['total_llamadas'] ?? 0,
        'Contactos Efectivos' => $resumen['contactos_efectivos'] ?? 0,
        'Tasa de Contactos' => ($resumen['porcentaje_contactos'] ?? 0) . '%',
        'Rating Promedio' => number_format($resumen['rating_promedio'] ?? 0, 2),
        'Llamadores Activos' => $resumen['llamadores_activos'] ?? 0,
        'Hora Pico' => $resumen['hora_pico'] ?? 'N/A'
    ];
    
    foreach ($metricas as $nombre => $valor) {
        echo '<tr>';
        echo '<td><strong>' . $nombre . '</strong></td>';
        echo '<td>' . $valor . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    
    echo '<br><br>';
    
    // ==================================================
    // SECCIÓN 2: DISTRIBUCIÓN DE RATINGS
    // ==================================================
    echo '<div class="seccion-titulo">⭐ DISTRIBUCIÓN DE RATINGS</div>';
    echo '<br>';
    
    echo '<table border="1" cellpadding="5" cellspacing="0">';
    echo '<tr style="background-color: #2ecc71; color: white; font-weight: bold;">';
    echo '<th>Rating</th>';
    echo '<th>Estrellas</th>';
    echo '<th>Cantidad</th>';
    echo '</tr>';
    
    $ratings = [
        '5' => ['estrellas' => '★★★★★', 'cantidad' => $resumen['rating_5'] ?? 0],
        '4' => ['estrellas' => '★★★★☆', 'cantidad' => $resumen['rating_4'] ?? 0],
        '3' => ['estrellas' => '★★★☆☆', 'cantidad' => $resumen['rating_3'] ?? 0],
        '2' => ['estrellas' => '★★☆☆☆', 'cantidad' => $resumen['rating_2'] ?? 0],
        '1' => ['estrellas' => '★☆☆☆☆', 'cantidad' => $resumen['rating_1'] ?? 0],
        '0' => ['estrellas' => 'Sin rating', 'cantidad' => $resumen['sin_rating'] ?? 0]
    ];
    
    foreach ($ratings as $rating) {
        echo '<tr>';
        echo '<td class="centrado">' . $rating['estrellas'] . '</td>';
        echo '<td class="centrado">' . $rating['estrellas'] . '</td>';
        echo '<td class="centrado">' . $rating['cantidad'] . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    
    echo '<br><br>';
    
    // ==================================================
    // SECCIÓN 3: TOP 10 LLAMADORES
    // ==================================================
    echo '<div class="seccion-titulo">🏆 TOP 10 LLAMADORES</div>';
    echo '<br>';
    
    if (count($topLlamadores) > 0) {
        echo '<table border="1" cellpadding="5" cellspacing="0">';
        echo '<tr style="background-color: #9b59b6; color: white; font-weight: bold;">';
        echo '<th>Posición</th>';
        echo '<th>Llamador</th>';
        echo '<th>Cédula</th>';
        echo '<th>Total Llamadas</th>';
        echo '<th>Rating Promedio</th>';
        echo '<th>Contactos Efectivos</th>';
        echo '<th>Eficiencia</th>';
        echo '</tr>';
        
        foreach ($topLlamadores as $index => $llamador) {
            $bgcolor = '';
            if ($index == 0) $bgcolor = 'background-color: #ffd700;';
            elseif ($index == 1) $bgcolor = 'background-color: #c0c0c0;';
            elseif ($index == 2) $bgcolor = 'background-color: #cd7f32;';
            elseif ($index % 2 == 0) $bgcolor = 'background-color: #f8f9fa;';
            
            $eficiencia = $llamador['eficiencia'] ?? 0;
            $colorEficiencia = '';
            if ($eficiencia >= 80) {
                $colorEficiencia = 'class="verde"';
            } elseif ($eficiencia >= 60) {
                $colorEficiencia = 'class="amarillo"';
            } else {
                $colorEficiencia = 'class="rojo"';
            }
            
            echo '<tr style="' . $bgcolor . '">';
            echo '<td class="centrado">' . ($index + 1) . '</td>';
            echo '<td>' . htmlspecialchars($llamador['nombre_completo'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($llamador['cedula'] ?? '') . '</td>';
            echo '<td class="centrado">' . ($llamador['total_llamadas'] ?? 0) . '</td>';
            echo '<td class="centrado">' . number_format($llamador['rating_promedio'] ?? 0, 2) . '</td>';
            echo '<td class="centrado">' . ($llamador['contactos_efectivos'] ?? 0) . '</td>';
            echo '<td class="centrado" ' . $colorEficiencia . '>' . $eficiencia . '%</td>';
            echo '</tr>';
        }
        
        echo '</table>';
    } else {
        echo '<p style="color: #e74c3c; font-weight: bold;">No hay datos de llamadores para los filtros seleccionados.</p>';
    }
    
    echo '<br><br>';
    
    // ==================================================
    // SECCIÓN 4: DISTRIBUCIÓN POR HORA
    // ==================================================
    echo '<div class="seccion-titulo">⏰ DISTRIBUCIÓN DE LLAMADAS POR HORA</div>';
    echo '<br>';
    
    if (count($llamadasPorHora) > 0) {
        echo '<table border="1" cellpadding="5" cellspacing="0">';
        echo '<tr style="background-color: #e67e22; color: white; font-weight: bold;">';
        echo '<th>Hora</th>';
        echo '<th>Cantidad de Llamadas</th>';
        echo '<th>Rating Promedio</th>';
        echo '<th>Gráfico</th>';
        echo '</tr>';
        
        $maxCantidad = 0;
        foreach ($llamadasPorHora as $hora) {
            if ($hora['cantidad'] > $maxCantidad) {
                $maxCantidad = $hora['cantidad'];
            }
        }
        
        foreach ($llamadasPorHora as $hora) {
            $porcentaje = $maxCantidad > 0 ? ($hora['cantidad'] / $maxCantidad * 100) : 0;
            $barraAncho = min(100, $porcentaje * 2);
            
            $barraColor = '';
            if ($hora['cantidad'] >= 10) {
                $barraColor = 'background-color: #2ecc71;';
            } elseif ($hora['cantidad'] >= 5) {
                $barraColor = 'background-color: #f39c12;';
            } else {
                $barraColor = 'background-color: #e74c3c;';
            }
            
            echo '<tr>';
            echo '<td class="centrado">' . sprintf('%02d:00', $hora['hora']) . '</td>';
            echo '<td class="centrado">' . $hora['cantidad'] . '</td>';
            echo '<td class="centrado">' . number_format($hora['rating_promedio'] ?? 0, 2) . '</td>';
            echo '<td>';
            echo '<div style="width: 200px; height: 20px; background-color: #ecf0f1; border-radius: 3px; overflow: hidden;">';
            echo '<div style="width: ' . $barraAncho . 'px; height: 100%; ' . $barraColor . '"></div>';
            echo '</div>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
    } else {
        echo '<p style="color: #e74c3c; font-weight: bold;">No hay datos de distribución horaria.</p>';
    }
    
    echo '<br><br>';
    
    // ==================================================
    // SECCIÓN 5: DETALLE DE LLAMADAS
    // ==================================================
    echo '<div class="seccion-titulo">📋 DETALLE DE LLAMADAS</div>';
    echo '<p>Total de registros: ' . count($detalle) . '</p>';
    echo '<br>';
    
    if (count($detalle) > 0) {
        echo '<table border="1" cellpadding="5" cellspacing="0" width="100%">';
        echo '<tr style="background-color: #2c3e50; color: white; font-weight: bold;">';
        echo '<th>Fecha/Hora</th>';
        echo '<th>Llamador</th>';
        echo '<th>Cédula</th>';
        echo '<th>Referenciado</th>';
        echo '<th>Cédula Ref.</th>';
        echo '<th>Teléfono</th>';
        echo '<th>Resultado</th>';
        echo '<th>Rating</th>';
        echo '<th>Observaciones</th>';
        echo '</tr>';
        
        $contador = 0;
        foreach ($detalle as $llamada) {
            $bgcolor = $contador % 2 == 0 ? 'background-color: #f8f9fa;' : '';
            echo '<tr style="' . $bgcolor . '">';
            echo '<td>' . htmlspecialchars($llamada['fecha_hora']) . '</td>';
            echo '<td>' . htmlspecialchars($llamada['llamador']) . '</td>';
            echo '<td>' . htmlspecialchars($llamada['cedula_llamador']) . '</td>';
            echo '<td>' . htmlspecialchars($llamada['referenciado']) . '</td>';
            echo '<td>' . htmlspecialchars($llamada['cedula_referenciado']) . '</td>';
            echo '<td>' . htmlspecialchars($llamada['telefono']) . '</td>';
            
            $colorResultado = '';
            $resultado = $llamada['resultado'] ?? '';
            if (stripos($resultado, 'contactado') !== false) {
                $colorResultado = 'class="verde"';
            } elseif (stripos($resultado, 'rechaz') !== false) {
                $colorResultado = 'class="rojo"';
            } elseif (stripos($resultado, 'no contesta') !== false) {
                $colorResultado = 'class="amarillo"';
            }
            
            echo '<td ' . $colorResultado . '>' . htmlspecialchars($resultado) . '</td>';
            echo '<td class="centrado">' . htmlspecialchars($llamada['rating_estrellas'] ?? 'Sin rating') . '</td>';
            echo '<td>' . htmlspecialchars(substr($llamada['observaciones'] ?? '', 0, 100)) . '</td>';
            echo '</tr>';
            $contador++;
        }
        
        echo '</table>';
    } else {
        echo '<p style="color: #e74c3c; font-weight: bold;">No hay registros de llamadas para los filtros seleccionados.</p>';
    }
    
    echo '<br><br>';
    
    // ==================================================
    // SECCIÓN 6: INFORMACIÓN DEL REPORTE
    // ==================================================
    echo '<div class="seccion-titulo">ℹ️ INFORMACIÓN DEL REPORTE</div>';
    echo '<br>';
    
    echo '<table border="1" cellpadding="5" cellspacing="0" style="background-color: #f8f9fa;">';
    echo '<tr><th colspan="2" style="background-color: #34495e; color: white;">DETALLES DE LA EXPORTACIÓN</th></tr>';
    echo '<tr><td><strong>Exportado por:</strong></td><td>' . htmlspecialchars($_SESSION['nombres'] ?? '') . ' ' . htmlspecialchars($_SESSION['apellidos'] ?? '') . '</td></tr>';
    echo '<tr><td><strong>Fecha de exportación:</strong></td><td>' . date('d/m/Y H:i:s') . '</td></tr>';
    echo '<tr><td><strong>Tipo de reporte:</strong></td><td>Tracking de Llamadas</td></tr>';
    echo '<tr><td><strong>Filtros aplicados:</strong></td><td>' . getDescripcionRango($filtros) . '</td></tr>';
    
    if (!empty($filtros['tipo_resultado']) && $filtros['tipo_resultado'] !== 'todos') {
        $pdo = $GLOBALS['pdo'] ?? null;
        if ($pdo) {
            $resultadoNombre = obtenerNombreResultado($pdo, $filtros['tipo_resultado']);
            echo '<tr><td><strong>Resultado filtrado:</strong></td><td>' . htmlspecialchars($resultadoNombre) . '</td></tr>';
        }
    }
    
    if (!empty($filtros['rating']) && $filtros['rating'] !== 'todos') {
        $ratingText = $filtros['rating'] === '0' ? 'Sin rating' : $filtros['rating'] . ' estrellas';
        echo '<tr><td><strong>Rating filtrado:</strong></td><td>' . $ratingText . '</td></tr>';
    }
    
    echo '</table>';
    
    echo '</body></html>';
    exit;
}
?>