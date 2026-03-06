<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/PregoneroModel.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';

// Verificar si el usuario está logueado y es SuperAdmin
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    header('HTTP/1.1 403 Forbidden');
    exit('Acceso denegado');
}

$pdo = Database::getConnection();
$pregoneroModel = new PregoneroModel($pdo);
$usuarioModel = new UsuarioModel($pdo);

// ✅ CAPTURAR TODOS LOS FILTROS DE LA URL
$filtros = [];

// Filtro de búsqueda general
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $filtros['search'] = $_GET['search'];
}

// Filtro por estado (activo/inactivo)
if (isset($_GET['solo_activos']) && $_GET['solo_activos'] == '1') {
    $filtros['activo'] = true;
} elseif (isset($_GET['activo']) && $_GET['activo'] !== '') {
    $filtros['activo'] = ($_GET['activo'] == '1');
}

// Filtro por voto registrado
if (isset($_GET['voto_registrado']) && $_GET['voto_registrado'] !== '') {
    $filtros['voto_registrado'] = ($_GET['voto_registrado'] == '1' || $_GET['voto_registrado'] === 'true');
}

// Filtros avanzados
if (isset($_GET['zona']) && !empty($_GET['zona'])) {
    $filtros['zona'] = $_GET['zona'];
}
if (isset($_GET['barrio']) && !empty($_GET['barrio'])) {
    $filtros['barrio'] = $_GET['barrio'];
}
if (isset($_GET['puesto']) && !empty($_GET['puesto'])) {
    $filtros['puesto'] = $_GET['puesto'];
}
if (isset($_GET['comuna']) && !empty($_GET['comuna'])) {
    $filtros['comuna'] = $_GET['comuna'];
}
if (isset($_GET['corregimiento']) && !empty($_GET['corregimiento'])) {
    $filtros['corregimiento'] = $_GET['corregimiento'];
}
if (isset($_GET['quien_reporta']) && !empty($_GET['quien_reporta'])) {
    $filtros['quien_reporta'] = $_GET['quien_reporta'];
}
if (isset($_GET['id_referenciador']) && !empty($_GET['id_referenciador'])) {
    $filtros['id_referenciador'] = $_GET['id_referenciador'];
}
if (isset($_GET['usuario_registro']) && !empty($_GET['usuario_registro'])) {
    $filtros['usuario_registro'] = $_GET['usuario_registro'];
}
if (isset($_GET['fecha_desde']) && !empty($_GET['fecha_desde'])) {
    $filtros['fecha_desde'] = $_GET['fecha_desde'];
}
if (isset($_GET['fecha_hasta']) && !empty($_GET['fecha_hasta'])) {
    $filtros['fecha_hasta'] = $_GET['fecha_hasta'];
}

// ✅ OBTENER PREGONEROS CON LOS FILTROS APLICADOS
// Usar getPregonerosPaginados sin paginación (página grande)
$pregoneros = $pregoneroModel->getPregonerosPaginados(1, 10000, $filtros);

// Configurar headers para archivo Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="pregoneros_filtrados_' . date('Y-m-d_H-i-s') . '.xls"');
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
echo '<x:Name>Pregoneros</x:Name>';
echo '<x:WorksheetOptions>';
echo '<x:DisplayGridlines/>';
echo '</x:WorksheetOptions>';
echo '</x:ExcelWorksheet>';
echo '</x:ExcelWorksheets>';
echo '</x:ExcelWorkbook>';
echo '</xml>';
echo '<![endif]-->';
echo '<style>';
echo 'td { mso-number-format:\@; }'; // Forzar formato de texto para cédulas y teléfonos
echo '.header { background-color: #4e73df; color: white; font-weight: bold; }';
echo '.summary { background-color: #f8f9fa; }';
echo '.small-text { font-size: 10px; }';
echo '.filtros-aplicados { background-color: #fff3cd; color: #856404; }';
echo '.mismo-pregonero { background-color: #e3f2fd; font-style: italic; }';
echo '</style>';
echo '</head>';
echo '<body>';

// ✅ MOSTRAR FILTROS APLICADOS
if (!empty($filtros)) {
    echo '<table border="1" class="filtros-aplicados" style="margin-bottom: 20px;">';
    echo '<tr><th colspan="2" style="background-color: #ffeeba; text-align: center;">FILTROS APLICADOS EN LA EXPORTACIÓN</th></tr>';
    
    if (isset($filtros['search'])) {
        echo '<tr><td><strong>Búsqueda:</strong></td><td>' . htmlspecialchars($filtros['search']) . '</td></tr>';
    }
    if (isset($filtros['activo'])) {
        echo '<tr><td><strong>Estado:</strong></td><td>' . ($filtros['activo'] ? 'Activos' : 'Inactivos') . '</td></tr>';
    }
    if (isset($filtros['voto_registrado'])) {
        echo '<tr><td><strong>Voto registrado:</strong></td><td>' . ($filtros['voto_registrado'] ? 'Sí votaron' : 'No votaron') . '</td></tr>';
    }
    if (isset($filtros['zona'])) {
        $stmt = $pdo->prepare("SELECT nombre FROM zona WHERE id_zona = ?");
        $stmt->execute([$filtros['zona']]);
        $zona = $stmt->fetchColumn();
        echo '<tr><td><strong>Zona:</strong></td><td>' . htmlspecialchars($zona) . '</td></tr>';
    }
    if (isset($filtros['barrio'])) {
        $stmt = $pdo->prepare("SELECT nombre FROM barrio WHERE id_barrio = ?");
        $stmt->execute([$filtros['barrio']]);
        $barrio = $stmt->fetchColumn();
        echo '<tr><td><strong>Barrio:</strong></td><td>' . htmlspecialchars($barrio) . '</td></tr>';
    }
    if (isset($filtros['puesto'])) {
        $stmt = $pdo->prepare("SELECT nombre FROM puesto_votacion WHERE id_puesto = ?");
        $stmt->execute([$filtros['puesto']]);
        $puesto = $stmt->fetchColumn();
        echo '<tr><td><strong>Puesto de votación:</strong></td><td>' . htmlspecialchars($puesto) . '</td></tr>';
    }
    if (isset($filtros['comuna'])) {
        echo '<tr><td><strong>Comuna:</strong></td><td>' . htmlspecialchars($filtros['comuna']) . '</td></tr>';
    }
    if (isset($filtros['corregimiento'])) {
        echo '<tr><td><strong>Corregimiento:</strong></td><td>' . htmlspecialchars($filtros['corregimiento']) . '</td></tr>';
    }
    if (isset($filtros['quien_reporta'])) {
        echo '<tr><td><strong>Quien reporta:</strong></td><td>' . htmlspecialchars($filtros['quien_reporta']) . '</td></tr>';
    }
    if (isset($filtros['id_referenciador'])) {
        $stmt = $pdo->prepare("SELECT nombres, apellidos FROM usuario WHERE id_usuario = ?");
        $stmt->execute([$filtros['id_referenciador']]);
        $ref = $stmt->fetch(PDO::FETCH_ASSOC);
        echo '<tr><td><strong>Referenciador asignado:</strong></td><td>' . htmlspecialchars($ref['nombres'] . ' ' . $ref['apellidos']) . '</td></tr>';
    }
    if (isset($filtros['usuario_registro'])) {
        $stmt = $pdo->prepare("SELECT nombres, apellidos FROM usuario WHERE id_usuario = ?");
        $stmt->execute([$filtros['usuario_registro']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        echo '<tr><td><strong>Registrado por:</strong></td><td>' . htmlspecialchars($user['nombres'] . ' ' . $user['apellidos']) . '</td></tr>';
    }
    if (isset($filtros['fecha_desde'])) {
        echo '<tr><td><strong>Fecha desde:</strong></td><td>' . htmlspecialchars($filtros['fecha_desde']) . '</td></tr>';
    }
    if (isset($filtros['fecha_hasta'])) {
        echo '<tr><td><strong>Fecha hasta:</strong></td><td>' . htmlspecialchars($filtros['fecha_hasta']) . '</td></tr>';
    }
    
    echo '<tr><td><strong>Total registros:</strong></td><td>' . count($pregoneros) . '</td></tr>';
    echo '<tr><td><strong>Fecha exportación:</strong></td><td>' . date('d/m/Y H:i:s') . '</td></tr>';
    echo '</table>';
}

// Crear tabla con TODOS los campos
echo '<table border="1" class="small-text">';
echo '<tr class="header">';
echo '<th>ID</th>';
echo '<th>Estado</th>';
echo '<th>Nombres</th>';
echo '<th>Apellidos</th>';
echo '<th>Identificación</th>';
echo '<th>Teléfono</th>';
echo '<th>Quien Reporta</th>';
echo '<th>Es el mismo</th>';
echo '<th>Zona</th>';
echo '<th>Sector</th>';
echo '<th>Puesto Votación</th>';
echo '<th>Mesa</th>';
echo '<th>Barrio</th>';
echo '<th>Corregimiento</th>';
echo '<th>Comuna</th>';
echo '<th>Referenciador Asignado</th>';
echo '<th>Cédula Referenciador</th>';
echo '<th>Teléfono Referenciador</th>';
echo '<th>Registrado por</th>';
echo '<th>Fecha Registro</th>';
echo '<th>Voto Registrado</th>';
echo '<th>Fecha Voto</th>';
echo '<th>Usuario Registró Voto</th>';
echo '<th>Activo</th>';
echo '</tr>';

// ✅ LLENAR DATOS SOLO CON LOS REGISTROS FILTRADOS
foreach ($pregoneros as $pregonero) {
    // Determinar estado
    $activo = $pregonero['activo'] ?? true;
    $esta_activo = ($activo === true || $activo === 't' || $activo == 1);
    $estado = $esta_activo ? 'ACTIVO' : 'INACTIVO';
    $estado_booleano = $esta_activo ? 'SI' : 'NO';
    
    // Determinar voto registrado
    $voto_registrado = $pregonero['voto_registrado'] ?? false;
    $voto = ($voto_registrado === true || $voto_registrado === 't' || $voto_registrado == 1) ? 'SI' : 'NO';
    
    // Verificar si quien reporta es el mismo pregonero
    $nombreCompleto = trim(($pregonero['nombres'] ?? '') . ' ' . ($pregonero['apellidos'] ?? ''));
    $quienReporta = trim($pregonero['quien_reporta'] ?? '');
    $esMismo = (!empty($quienReporta) && strtolower($quienReporta) === strtolower($nombreCompleto)) ? 'SI' : 'NO';
    
    // Fechas formateadas
    $fechaRegistro = !empty($pregonero['fecha_registro']) ? date('d/m/Y H:i', strtotime($pregonero['fecha_registro'])) : '';
    $fechaVoto = !empty($pregonero['fecha_voto']) ? date('d/m/Y H:i', strtotime($pregonero['fecha_voto'])) : '';
    
    // Obtener información del referenciador asignado
    $referenciadorNombre = '';
    $referenciadorCedula = '';
    $referenciadorTelefono = '';
    
    if (!empty($pregonero['id_referenciador'])) {
        $referenciador = $usuarioModel->getUsuarioById($pregonero['id_referenciador']);
        if ($referenciador) {
            $referenciadorNombre = $referenciador['nombres'] . ' ' . $referenciador['apellidos'];
            $referenciadorCedula = $referenciador['cedula'] ?? '';
            $referenciadorTelefono = $referenciador['telefono'] ?? '';
        }
    }
    
    echo '<tr>';
    echo '<td>' . ($pregonero['id_pregonero'] ?? '') . '</td>';
    echo '<td>' . $estado . '</td>';
    echo '<td>' . htmlspecialchars($pregonero['nombres'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($pregonero['apellidos'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($pregonero['identificacion'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($pregonero['telefono'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($quienReporta) . '</td>';
    echo '<td>' . $esMismo . '</td>';
    echo '<td>' . htmlspecialchars($pregonero['zona_nombre'] ?? 'N/A') . '</td>';
    echo '<td>' . htmlspecialchars($pregonero['sector_nombre'] ?? 'N/A') . '</td>';
    echo '<td>' . htmlspecialchars($pregonero['puesto_nombre'] ?? 'N/A') . '</td>';
    echo '<td>' . htmlspecialchars($pregonero['mesa'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($pregonero['barrio_nombre'] ?? 'N/A') . '</td>';
    echo '<td>' . htmlspecialchars($pregonero['corregimiento'] ?? 'N/A') . '</td>';
    echo '<td>' . htmlspecialchars($pregonero['comuna'] ?? 'N/A') . '</td>';
    echo '<td>' . htmlspecialchars($referenciadorNombre ?: 'N/A') . '</td>';
    echo '<td>' . htmlspecialchars($referenciadorCedula ?: 'N/A') . '</td>';
    echo '<td>' . htmlspecialchars($referenciadorTelefono ?: 'N/A') . '</td>';
    echo '<td>' . htmlspecialchars($pregonero['usuario_registro_nombre'] ?? 'N/A') . '</td>';
    echo '<td>' . $fechaRegistro . '</td>';
    echo '<td>' . $voto . '</td>';
    echo '<td>' . $fechaVoto . '</td>';
    echo '<td>' . htmlspecialchars($pregonero['usuario_voto_nombre'] ?? 'N/A') . '</td>';
    echo '<td>' . $estado_booleano . '</td>';
    echo '</tr>';
}

echo '</table>';

// ✅ RESUMEN ESTADÍSTICO SOLO DE LOS DATOS FILTRADOS
echo '<br><br>';
echo '<table border="1" class="summary">';
echo '<tr><th colspan="2" class="header">RESUMEN DE PREGONEROS FILTRADOS</th></tr>';
echo '<tr><td><strong>Total Pregoneros (con filtros):</strong></td><td>' . count($pregoneros) . '</td></tr>';

// Contar estadísticas solo de los registros filtrados
$totalActivos = 0;
$totalInactivos = 0;
$totalVotaron = 0;
$totalNoVotaron = 0;
$mismoReportante = 0;
$referenciadoresCount = [];

foreach ($pregoneros as $pregonero) {
    // Estado
    $activo = $pregonero['activo'] ?? true;
    $esta_activo = ($activo === true || $activo === 't' || $activo == 1);
    if ($esta_activo) {
        $totalActivos++;
    } else {
        $totalInactivos++;
    }
    
    // Voto
    $voto_registrado = $pregonero['voto_registrado'] ?? false;
    $voto = ($voto_registrado === true || $voto_registrado === 't' || $voto_registrado == 1);
    if ($voto) {
        $totalVotaron++;
    } else {
        $totalNoVotaron++;
    }
    
    // Mismo reportante
    $nombreCompleto = trim(($pregonero['nombres'] ?? '') . ' ' . ($pregonero['apellidos'] ?? ''));
    $quienReporta = trim($pregonero['quien_reporta'] ?? '');
    if (!empty($quienReporta) && strtolower($quienReporta) === strtolower($nombreCompleto)) {
        $mismoReportante++;
    }
    
    // Contar por referenciador
    $referenciadorNombre = '';
    if (!empty($pregonero['id_referenciador'])) {
        $referenciador = $usuarioModel->getUsuarioById($pregonero['id_referenciador']);
        if ($referenciador) {
            $referenciadorNombre = $referenciador['nombres'] . ' ' . $referenciador['apellidos'];
            if (!isset($referenciadoresCount[$referenciadorNombre])) {
                $referenciadoresCount[$referenciadorNombre] = 0;
            }
            $referenciadoresCount[$referenciadorNombre]++;
        }
    }
}

echo '<tr><td><strong>Activos:</strong></td><td>' . $totalActivos . '</td></tr>';
echo '<tr><td><strong>Inactivos:</strong></td><td>' . $totalInactivos . '</td></tr>';

// Estadísticas de voto
echo '<tr><td colspan="2" style="background-color: #e9ecef; font-weight: bold; text-align: center;">Estado de Voto</td></tr>';
echo '<tr><td>Votaron:</td><td>' . $totalVotaron . ' (' . (count($pregoneros) > 0 ? round(($totalVotaron / count($pregoneros)) * 100, 2) : 0) . '%)</td></tr>';
echo '<tr><td>No votaron:</td><td>' . $totalNoVotaron . ' (' . (count($pregoneros) > 0 ? round(($totalNoVotaron / count($pregoneros)) * 100, 2) : 0) . '%)</td></tr>';

// Estadísticas de reporte
echo '<tr><td colspan="2" style="background-color: #e9ecef; font-weight: bold; text-align: center;">Información de Reporte</td></tr>';
echo '<tr><td>Reportante es el mismo pregonero:</td><td>' . $mismoReportante . ' (' . (count($pregoneros) > 0 ? round(($mismoReportante / count($pregoneros)) * 100, 2) : 0) . '%)</td></tr>';
echo '<tr><td>Reportante es otra persona:</td><td>' . (count($pregoneros) - $mismoReportante) . ' (' . (count($pregoneros) > 0 ? round(((count($pregoneros) - $mismoReportante) / count($pregoneros)) * 100, 2) : 0) . '%)</td></tr>';

// Agregar estadísticas de referenciadores si hay datos
if (!empty($referenciadoresCount)) {
    echo '<tr><td colspan="2" style="background-color: #e9ecef; font-weight: bold; text-align: center;">Top 5 Referenciadores con más Pregoneros (en esta exportación)</td></tr>';
    arsort($referenciadoresCount);
    $topReferenciadores = array_slice($referenciadoresCount, 0, 5, true);
    
    $counter = 1;
    foreach ($topReferenciadores as $refNombre => $count) {
        echo '<tr><td>' . $counter . '. ' . htmlspecialchars($refNombre) . ':</td><td>' . $count . ' pregoneros</td></tr>';
        $counter++;
    }
}

// Información de exportación
echo '<tr><td colspan="2" style="background-color: #e9ecef; font-weight: bold; text-align: center;">Información de Exportación</td></tr>';
echo '<tr><td><strong>Fecha de Exportación:</strong></td><td>' . date('d/m/Y H:i:s') . '</td></tr>';
echo '<tr><td><strong>Exportado por:</strong></td><td>' . htmlspecialchars($_SESSION['nombres'] ?? 'Usuario') . ' ' . htmlspecialchars($_SESSION['apellidos'] ?? '') . '</td></tr>';
echo '<tr><td><strong>Tipo de Exportación:</strong></td><td>' . (empty($filtros) ? 'Todos los pregoneros' : 'Pregoneros filtrados') . '</td></tr>';
echo '</table>';

echo '</body></html>';
exit;