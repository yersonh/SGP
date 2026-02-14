<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/ReferenciadoModel.php';
require_once __DIR__ . '/../models/UsuarioModel.php';

// Verificar si el usuario está logueado y es referenciador
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'Referenciador') {
    header('HTTP/1.1 403 Forbidden');
    exit('Acceso denegado');
}

$id_usuario_logueado = $_SESSION['id_usuario'];
$pdo = Database::getConnection();
$referenciadoModel = new ReferenciadoModel($pdo);
$usuarioModel = new UsuarioModel($pdo);

// Obtener datos del usuario
$usuario = $usuarioModel->getUsuarioById($id_usuario_logueado);

// ============================================
// Obtener filtro de líder desde la URL
// ============================================
$filtro_lider = isset($_GET['filtro_lider']) ? $_GET['filtro_lider'] : 'todos';
$nombre_lider_filtro = isset($_GET['nombre_lider']) ? trim($_GET['nombre_lider']) : '';

// ============================================
// IMPORTANTE: Obtener TODOS los referenciados primero
// ============================================
$referenciados = $referenciadoModel->getReferenciadosByUsuario($id_usuario_logueado);

// ============================================
// CORREGIDO: Función de filtrado mejorada
// ============================================
function filtrarPorLider($referenciados, $filtro_lider, $nombre_lider_filtro) {
    if ($filtro_lider === 'todos') {
        return $referenciados;
    }
    
    $resultado = [];
    
    foreach ($referenciados as $ref) {
        // Obtener nombre completo del líder de este referenciado
        $liderReferenciado = '';
        if (!empty($ref['lider_nombres']) || !empty($ref['lider_apellidos'])) {
            $liderReferenciado = trim(
                ($ref['lider_nombres'] ?? '') . ' ' . 
                ($ref['lider_apellidos'] ?? '')
            );
        }
        
        // DEBUG: Podemos ver qué está pasando (opcional)
        error_log("Referenciado ID: {$ref['id_referenciado']} - Líder: '$liderReferenciado' - Filtro: '$filtro_lider'");
        
        if ($filtro_lider === 'sin_lider') {
            // Mostrar solo los que NO tienen líder
            if (empty($liderReferenciado)) {
                $resultado[] = $ref;
            }
        } else {
            // Filtrar por líder específico
            $coincide = false;
            
            // Comparar con el nombre completo del líder
            if (!empty($liderReferenciado)) {
                // Intentar con el nombre del filtro
                if (!empty($nombre_lider_filtro)) {
                    // Comparación exacta (sin importar mayúsculas/minúsculas)
                    if (strcasecmp(trim($liderReferenciado), trim($nombre_lider_filtro)) === 0) {
                        $coincide = true;
                    }
                    // Comparación parcial (si el nombre contiene el filtro)
                    elseif (stripos($liderReferenciado, $nombre_lider_filtro) !== false) {
                        $coincide = true;
                    }
                    // Comparación inversa (si el filtro contiene el nombre)
                    elseif (stripos($nombre_lider_filtro, $liderReferenciado) !== false) {
                        $coincide = true;
                    }
                }
                
                // Si no hay nombre específico, usar el valor del select
                if (!$coincide && !empty($filtro_lider) && $filtro_lider !== 'sin_lider') {
                    if (stripos($liderReferenciado, $filtro_lider) !== false) {
                        $coincide = true;
                    }
                }
            }
            
            if ($coincide) {
                $resultado[] = $ref;
            }
        }
    }
    
    return $resultado;
}

// Aplicar filtro de líder
$referenciados = filtrarPorLider($referenciados, $filtro_lider, $nombre_lider_filtro);

// ============================================
// Filtrar por activos si se solicita
// ============================================
$soloActivos = isset($_GET['solo_activos']) && $_GET['solo_activos'] == 1;
if ($soloActivos) {
    $referenciados = array_filter($referenciados, function($referenciado) {
        $activo = $referenciado['activo'] ?? true;
        return ($activo === true || $activo === 't' || $activo == 1);
    });
}

// Re-indexar array después de los filtros
$referenciados = array_values($referenciados);

// Ordenar por ID ascendente
usort($referenciados, function($a, $b) {
    return ($a['id_referenciado'] ?? 0) <=> ($b['id_referenciado'] ?? 0);
});

// DEBUG: Ver cuántos registros quedaron después del filtro
error_log("Total después de filtrar por líder: " . count($referenciados));

// Si no hay resultados, mostrar mensaje
if (empty($referenciados)) {
    // Configurar headers para archivo Excel
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="sin_resultados_' . date('Y-m-d_H-i-s') . '.xls"');
    
    echo '<html>';
    echo '<head><meta charset="UTF-8"></head>';
    echo '<body>';
    echo '<h3>No se encontraron resultados</h3>';
    echo '<p>No hay referenciados que coincidan con el filtro aplicado:</p>';
    echo '<ul>';
    if ($filtro_lider === 'sin_lider') {
        echo '<li><strong>Filtro:</strong> Sin líder asignado</li>';
    } elseif ($filtro_lider !== 'todos') {
        echo '<li><strong>Líder:</strong> ' . htmlspecialchars($nombre_lider_filtro ?: $filtro_lider) . '</li>';
    }
    if ($soloActivos) {
        echo '<li><strong>Filtro adicional:</strong> Solo activos</li>';
    }
    echo '</ul>';
    echo '</body>';
    echo '</html>';
    exit;
}

// ============================================
// Contar estadísticas (después de aplicar filtros)
// ============================================
$totalReferidos = count($referenciados);
$totalActivos = 0;
$totalInactivos = 0;
$referidosSinLider = 0;

foreach ($referenciados as $referenciado) {
    $activo = $referenciado['activo'] ?? true;
    $esta_activo = ($activo === true || $activo === 't' || $activo == 1);
    if ($esta_activo) {
        $totalActivos++;
    } else {
        $totalInactivos++;
    }
    
    // Contar referidos sin líder
    if (empty($referenciado['lider_nombres']) && empty($referenciado['lider_apellidos'])) {
        $referidosSinLider++;
    }
}

// Configurar headers para archivo Excel
$nombre_archivo = 'mis_referenciados';
if ($filtro_lider !== 'todos') {
    if ($filtro_lider === 'sin_lider') {
        $nombre_archivo .= '_sin_lider';
    } else {
        $nombre_archivo .= '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $nombre_lider_filtro);
    }
}
if ($soloActivos) {
    $nombre_archivo .= '_activos';
}
$nombre_archivo .= '_' . date('Y-m-d_H-i-s') . '.xls';

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
header('Cache-Control: max-age=0');

// Crear contenido HTML para Excel
echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
echo '<head>';
echo '<meta charset="UTF-8">';
echo '<!--[if gte mso 9]>';
echo '<xml>';
echo '<x:ExcelWorkbook>';
echo '<x:ExcelWorksheets>';
echo '<x:ExcelWorksheet>';
echo '<x:Name>Mis Referenciados</x:Name>';
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
echo '.header { background-color: #4e73df; color: white; font-weight: bold; }';
echo '.summary { background-color: #f8f9fa; }';
echo '.filtro-info { background-color: #e3f2fd; font-style: italic; }';
echo '</style>';
echo '</head>';
echo '<body>';

// Mostrar información del filtro aplicado
echo '<table border="1" style="margin-bottom: 10px;">';
echo '<tr class="filtro-info">';
echo '<td colspan="14">';
echo '<strong>Filtro aplicado:</strong> ';
if ($filtro_lider === 'todos') {
    echo 'Todos los líderes';
} elseif ($filtro_lider === 'sin_lider') {
    echo 'Solo referenciados SIN líder asignado';
} else {
    echo 'Líder: ' . htmlspecialchars($nombre_lider_filtro ?: $filtro_lider);
}
if ($soloActivos) {
    echo ' (Solo activos)';
}
echo ' - <strong>Total registros exportados:</strong> ' . $totalReferidos;
echo '</td>';
echo '</tr>';
echo '</table>';

// Crear tabla principal
echo '<table border="1">';
echo '<tr class="header">';
echo '<th>ID</th>';
echo '<th>Estado</th>';
echo '<th>Nombre</th>';
echo '<th>Apellido</th>';
echo '<th>Cédula</th>';
echo '<th>Teléfono</th>';
echo '<th>Email</th>';
echo '<th>Afinidad</th>';
echo '<th>Grupo Parlamentario</th>';
echo '<th>Líder</th>';
echo '<th>Vota Aquí/Fuera</th>';
echo '<th>Puesto Votación</th>';
echo '<th>Mesa</th>';
echo '<th>Fecha Registro</th>';
echo '</tr>';

// Llenar datos
foreach ($referenciados as $referenciado) {
    $activo = $referenciado['activo'] ?? true;
    $esta_activo = ($activo === true || $activo === 't' || $activo == 1);
    $estado = $esta_activo ? 'ACTIVO' : 'INACTIVO';
    $vota_fuera = $referenciado['vota_fuera'] === 'Si';
    
    // Obtener nombre completo del líder
    $liderNombre = '';
    if (!empty($referenciado['lider_nombres']) || !empty($referenciado['lider_apellidos'])) {
        $liderNombre = trim(
            ($referenciado['lider_nombres'] ?? '') . ' ' . 
            ($referenciado['lider_apellidos'] ?? '')
        );
    }
    $liderNombre = !empty($liderNombre) ? $liderNombre : 'SIN LÍDER';
    
    echo '<tr>';
    echo '<td>' . ($referenciado['id_referenciado'] ?? '') . '</td>';
    echo '<td>' . $estado . '</td>';
    echo '<td>' . htmlspecialchars($referenciado['nombre'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($referenciado['apellido'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($referenciado['cedula'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($referenciado['telefono'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($referenciado['email'] ?? '') . '</td>';
    echo '<td>' . ($referenciado['afinidad'] ?? '0') . '/5</td>';
    echo '<td>' . (!empty($referenciado['grupo_nombre']) ? htmlspecialchars($referenciado['grupo_nombre']) : 'Sin asignar') . '</td>';
    echo '<td>' . htmlspecialchars($liderNombre) . '</td>';
    
    echo '<td>' . ($vota_fuera ? 'FUERA' : 'AQUÍ') . '</td>';
    
    if ($vota_fuera) {
        echo '<td>' . htmlspecialchars($referenciado['puesto_votacion_fuera'] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($referenciado['mesa_fuera'] ?? 'N/A') . '</td>';
    } else {
        echo '<td>' . htmlspecialchars($referenciado['puesto_votacion_nombre'] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($referenciado['mesa'] ?? 'N/A') . '</td>';
    }
    
    echo '<td>' . (isset($referenciado['fecha_registro']) ? date('d/m/Y H:i', strtotime($referenciado['fecha_registro'])) : '') . '</td>';
    echo '</tr>';
}

echo '</table>';

// Agregar resumen al final
echo '<br><br>';
echo '<table border="1" class="summary">';
echo '<tr><th colspan="2" class="header">RESUMEN - ' . strtoupper($nombre_archivo) . '</th></tr>';
echo '<tr><td><strong>Total Referidos:</strong></td><td>' . $totalReferidos . '</td></tr>';
echo '<tr><td><strong>Activos:</strong></td><td>' . $totalActivos . '</td></tr>';
echo '<tr><td><strong>Inactivos:</strong></td><td>' . $totalInactivos . '</td></tr>';
echo '<tr><td><strong>Sin Líder Asignado:</strong></td><td>' . $referidosSinLider . '</td></tr>';

// Contar distribución por líder (solo para los referidos filtrados)
$lideresCount = [];
foreach ($referenciados as $referenciado) {
    $liderNombre = '';
    if (!empty($referenciado['lider_nombres']) || !empty($referenciado['lider_apellidos'])) {
        $liderNombre = trim(
            ($referenciado['lider_nombres'] ?? '') . ' ' . 
            ($referenciado['lider_apellidos'] ?? '')
        );
    }
    if (!empty($liderNombre)) {
        if (!isset($lideresCount[$liderNombre])) {
            $lideresCount[$liderNombre] = 0;
        }
        $lideresCount[$liderNombre]++;
    }
}

if (!empty($lideresCount)) {
    echo '<tr><td colspan="2" style="background-color: #e9ecef; font-weight: bold; text-align: center;">Distribución por Líder</td></tr>';
    arsort($lideresCount);
    foreach ($lideresCount as $liderNombre => $count) {
        echo '<tr><td>' . htmlspecialchars($liderNombre) . ':</td><td>' . $count . ' referidos</td></tr>';
    }
}

echo '<tr><td><strong>Fecha de Exportación:</strong></td><td>' . date('d/m/Y H:i:s') . '</td></tr>';
echo '<tr><td><strong>Exportado por:</strong></td><td>' . htmlspecialchars($usuario['nombres'] ?? '') . ' ' . htmlspecialchars($usuario['apellidos'] ?? '') . '</td></tr>';
echo '<tr><td><strong>Filtro aplicado:</strong></td><td>';
if ($filtro_lider === 'todos') {
    echo 'Todos los líderes';
} elseif ($filtro_lider === 'sin_lider') {
    echo 'Sin líder';
} else {
    echo 'Líder: ' . htmlspecialchars($nombre_lider_filtro ?: $filtro_lider);
}
if ($soloActivos) {
    echo ' (Solo activos)';
}
echo '</td></tr>';
echo '</table>';

echo '</body></html>';
exit;