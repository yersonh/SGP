<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/PregoneroModel.php';
require_once __DIR__ . '/../models/UsuarioModel.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Acceso denegado');
}

// Tipos de usuario permitidos
$tipos_permitidos = ['CarguePregoneros', 'Referenciador', 'Administrador', 'Coordinador'];
if (!in_array($_SESSION['tipo_usuario'], $tipos_permitidos)) {
    header('HTTP/1.1 403 Forbidden');
    exit('Acceso denegado');
}

$id_usuario_logueado = $_SESSION['id_usuario'];
$tipo_usuario = $_SESSION['tipo_usuario'];
$pdo = Database::getConnection();
$pregoneroModel = new PregoneroModel($pdo);
$usuarioModel = new UsuarioModel($pdo);

// Obtener datos del usuario
$usuario = $usuarioModel->getUsuarioById($id_usuario_logueado);

// ============================================
// Obtener filtros desde la URL
// ============================================
$filtro_estado = isset($_GET['filtro_estado']) ? $_GET['filtro_estado'] : 'todos';
$filtro_zona = isset($_GET['zona']) ? $_GET['zona'] : '';
$filtro_barrio = isset($_GET['barrio']) ? $_GET['barrio'] : '';
$filtro_puesto = isset($_GET['puesto']) ? $_GET['puesto'] : '';
$filtro_comuna = isset($_GET['comuna']) ? $_GET['comuna'] : '';
$filtro_corregimiento = isset($_GET['corregimiento']) ? $_GET['corregimiento'] : '';
$filtro_quien_reporta = isset($_GET['quien_reporta']) ? $_GET['quien_reporta'] : '';
$filtro_voto_registrado = isset($_GET['voto_registrado']) ? $_GET['voto_registrado'] : '';
$fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
$fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// ============================================
// Obtener pregoneros según el tipo de usuario
// ============================================
$filtros = [];

// Aplicar filtros comunes
if (!empty($filtro_zona)) {
    $filtros['zona'] = $filtro_zona;
}
if (!empty($filtro_barrio)) {
    $filtros['barrio'] = $filtro_barrio;
}
if (!empty($filtro_puesto)) {
    $filtros['puesto'] = $filtro_puesto;
}
if (!empty($filtro_comuna)) {
    $filtros['comuna'] = $filtro_comuna;
}
if (!empty($filtro_corregimiento)) {
    $filtros['corregimiento'] = $filtro_corregimiento;
}
if (!empty($filtro_quien_reporta)) {
    $filtros['quien_reporta'] = $filtro_quien_reporta;
}
if (!empty($fecha_desde)) {
    $filtros['fecha_desde'] = $fecha_desde;
}
if (!empty($fecha_hasta)) {
    $filtros['fecha_hasta'] = $fecha_hasta;
}
if (!empty($search)) {
    $filtros['search'] = $search;
}

// Filtro por voto registrado
if ($filtro_voto_registrado !== '') {
    $filtros['voto_registrado'] = ($filtro_voto_registrado == '1' || $filtro_voto_registrado == 'true');
}

// Filtro por estado (activo/inactivo)
if ($filtro_estado === 'activos') {
    $filtros['activo'] = true;
} elseif ($filtro_estado === 'inactivos') {
    $filtros['activo'] = false;
}

// Si es CarguePregoneros o Referenciador, filtrar por el usuario actual
if ($tipo_usuario === 'CarguePregoneros' || $tipo_usuario === 'Referenciador') {
    $filtros['id_usuario_registro'] = $id_usuario_logueado;
}

// Obtener todos los pregoneros con los filtros aplicados (sin paginación)
$pregoneros = $pregoneroModel->getAll($filtros);

// ============================================
// Contar estadísticas (después de aplicar filtros)
// ============================================
$totalPregoneros = count($pregoneros);
$totalActivos = 0;
$totalInactivos = 0;
$totalVotaron = 0;
$totalPendientes = 0;

foreach ($pregoneros as $pregonero) {
    $activo = $pregonero['activo'] ?? true;
    $esta_activo = ($activo === true || $activo === 't' || $activo == 1);
    if ($esta_activo) {
        $totalActivos++;
    } else {
        $totalInactivos++;
    }
    
    // Contar los que ya votaron
    if (!empty($pregonero['voto_registrado']) && $pregonero['voto_registrado'] == true) {
        $totalVotaron++;
    } else {
        $totalPendientes++;
    }
}

// Si no hay resultados, mostrar mensaje
if (empty($pregoneros)) {
    // Configurar headers para archivo Excel
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="sin_resultados_pregoneros_' . date('Y-m-d_H-i-s') . '.xls"');
    
    echo '<html>';
    echo '<head><meta charset="UTF-8"></head>';
    echo '<body>';
    echo '<h3>No se encontraron resultados</h3>';
    echo '<p>No hay pregoneros que coincidan con los filtros aplicados.</p>';
    echo '</body>';
    echo '</html>';
    exit;
}

// Configurar headers para archivo Excel
$nombre_archivo = 'pregoneros';
if (!empty($search)) {
    $nombre_archivo .= '_busqueda_' . preg_replace('/[^a-zA-Z0-9]/', '_', substr($search, 0, 20));
}
if ($filtro_estado !== 'todos') {
    $nombre_archivo .= '_' . $filtro_estado;
}
if (!empty($filtro_zona)) {
    $nombre_archivo .= '_zona_' . $filtro_zona;
}
$nombre_archivo .= '_' . date('Y-m-d_H-i-s') . '.xls';

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
header('Cache-Control: max-age=0');

// Crear contenido HTML para Excel
echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/1999/xhtml">';
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
echo 'td { mso-number-format:\@; }';
echo '.header { background-color: #4e73df; color: white; font-weight: bold; text-align: center; }';
echo '.summary { background-color: #f8f9fa; }';
echo '.filtro-info { background-color: #e3f2fd; font-style: italic; }';
echo '.estado-activo { color: green; font-weight: bold; }';
echo '.estado-inactivo { color: red; font-weight: bold; }';
echo '.voto-si { color: green; font-weight: bold; }';
echo '.voto-no { color: orange; font-weight: bold; }';
echo '</style>';
echo '</head>';
echo '<body>';

// Mostrar información de los filtros aplicados
echo '<table border="1" style="margin-bottom: 10px; border-collapse: collapse;" cellpadding="5">';
echo '<tr class="filtro-info">';
echo '<td colspan="20">';
echo '<strong>Filtros aplicados:</strong> ';
$filtros_aplicados = [];
if (!empty($search)) {
    $filtros_aplicados[] = "Búsqueda: '$search'";
}
if ($filtro_estado === 'activos') {
    $filtros_aplicados[] = "Solo activos";
} elseif ($filtro_estado === 'inactivos') {
    $filtros_aplicados[] = "Solo inactivos";
}
if (!empty($filtro_zona)) {
    $filtros_aplicados[] = "Zona ID: $filtro_zona";
}
if (!empty($filtro_barrio)) {
    $filtros_aplicados[] = "Barrio ID: $filtro_barrio";
}
if (!empty($filtro_puesto)) {
    $filtros_aplicados[] = "Puesto ID: $filtro_puesto";
}
if (!empty($filtro_comuna)) {
    $filtros_aplicados[] = "Comuna: $filtro_comuna";
}
if (!empty($filtro_corregimiento)) {
    $filtros_aplicados[] = "Corregimiento: $filtro_corregimiento";
}
if (!empty($filtro_quien_reporta)) {
    $filtros_aplicados[] = "Quién reporta: $filtro_quien_reporta";
}
if ($filtro_voto_registrado !== '') {
    $filtros_aplicados[] = "Voto: " . ($filtro_voto_registrado == '1' ? 'Registrado' : 'No registrado');
}
if (!empty($fecha_desde)) {
    $filtros_aplicados[] = "Desde: $fecha_desde";
}
if (!empty($fecha_hasta)) {
    $filtros_aplicados[] = "Hasta: $fecha_hasta";
}

if (empty($filtros_aplicados)) {
    echo 'Todos los pregoneros';
} else {
    echo implode(' | ', $filtros_aplicados);
}
echo ' - <strong>Total registros exportados:</strong> ' . $totalPregoneros;
echo '</td>';
echo '</tr>';
echo '</table>';

// Crear tabla principal
echo '<table border="1" style="border-collapse: collapse;" cellpadding="5">';
echo '<tr class="header">';
echo '<th>ID</th>';
echo '<th>Estado</th>';
echo '<th>Voto</th>';
echo '<th>Nombres</th>';
echo '<th>Apellidos</th>';
echo '<th>Identificación</th>';
echo '<th>Teléfono</th>';
echo '<th>Barrio</th>';
echo '<th>Corregimiento</th>';
echo '<th>Comuna</th>';
echo '<th>Puesto Votación</th>';
echo '<th>Mesa</th>';
echo '<th>Sector</th>';
echo '<th>Zona</th>';
echo '<th>Quién Reporta</th>';
echo '<th>Referenciador</th>';
echo '<th>Registrado por</th>';
echo '<th>Fecha Registro</th>';
echo '<th>Fecha Voto</th>';
echo '<th>Usuario Registró Voto</th>';
echo '</tr>';

// Llenar datos
foreach ($pregoneros as $pregonero) {
    $activo = $pregonero['activo'] ?? true;
    $esta_activo = ($activo === true || $activo === 't' || $activo == 1);
    $estado = $esta_activo ? 'ACTIVO' : 'INACTIVO';
    $estado_class = $esta_activo ? 'estado-activo' : 'estado-inactivo';
    
    $voto_registrado = !empty($pregonero['voto_registrado']) && $pregonero['voto_registrado'] == true;
    $voto_texto = $voto_registrado ? 'SÍ' : 'NO';
    $voto_class = $voto_registrado ? 'voto-si' : 'voto-no';
    
    $referenciador_nombre = '';
    if (!empty($pregonero['referenciador_nombres']) || !empty($pregonero['referenciador_apellidos'])) {
        $referenciador_nombre = trim(
            ($pregonero['referenciador_nombres'] ?? '') . ' ' . 
            ($pregonero['referenciador_apellidos'] ?? '')
        );
    }
    
    $usuario_registro = trim(
        ($pregonero['usuario_nombres'] ?? '') . ' ' . 
        ($pregonero['usuario_apellidos'] ?? '')
    );
    
    echo '<tr>';
    echo '<td>' . ($pregonero['id_pregonero'] ?? '') . '</td>';
    echo '<td class="' . $estado_class . '">' . $estado . '</td>';
    echo '<td class="' . $voto_class . '">' . $voto_texto . '</td>';
    echo '<td>' . htmlspecialchars($pregonero['nombres'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($pregonero['apellidos'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($pregonero['identificacion'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($pregonero['telefono'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($pregonero['barrio_nombre'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($pregonero['corregimiento'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($pregonero['comuna'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($pregonero['puesto_nombre'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($pregonero['mesa'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($pregonero['sector_nombre'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($pregonero['zona_nombre'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($pregonero['quien_reporta'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($referenciador_nombre) . '</td>';
    echo '<td>' . htmlspecialchars($usuario_registro) . '</td>';
    echo '<td>' . (isset($pregonero['fecha_registro']) ? date('d/m/Y H:i', strtotime($pregonero['fecha_registro'])) : '') . '</td>';
    echo '<td>' . (isset($pregonero['fecha_voto']) ? date('d/m/Y H:i', strtotime($pregonero['fecha_voto'])) : '') . '</td>';
    echo '<td>' . htmlspecialchars($pregonero['usuario_voto_nombres'] ?? '') . ' ' . htmlspecialchars($pregonero['usuario_voto_apellidos'] ?? '') . '</td>';
    echo '</tr>';
}

echo '</table>';

// Agregar resumen al final
echo '<br><br>';
echo '<table border="1" style="border-collapse: collapse;" cellpadding="5" class="summary">';
echo '<tr><th colspan="2" class="header">RESUMEN - ' . strtoupper(str_replace('_', ' ', pathinfo($nombre_archivo, PATHINFO_FILENAME))) . '</th></tr>';
echo '<tr><td><strong>Total Pregoneros:</strong></td><td>' . $totalPregoneros . '</td></tr>';
echo '<tr><td><strong>Activos:</strong></td><td>' . $totalActivos . '</td></tr>';
echo '<tr><td><strong>Inactivos:</strong></td><td>' . $totalInactivos . '</td></tr>';
echo '<tr><td><strong>Ya votaron:</strong></td><td>' . $totalVotaron . '</td></tr>';
echo '<tr><td><strong>Pendientes de voto:</strong></td><td>' . $totalPendientes . '</td></tr>';

// Agregar información del usuario que exporta
echo '<tr><td><strong>Fecha de Exportación:</strong></td><td>' . date('d/m/Y H:i:s') . '</td></tr>';
echo '<tr><td><strong>Exportado por:</strong></td><td>' . htmlspecialchars($usuario['nombres'] ?? '') . ' ' . htmlspecialchars($usuario['apellidos'] ?? '') . '</td></tr>';
echo '<tr><td><strong>Tipo de Usuario:</strong></td><td>' . htmlspecialchars($tipo_usuario) . '</td></tr>';

// Información de los filtros aplicados
echo '<tr><td><strong>Filtros aplicados:</strong></td><td>';
if (empty($filtros_aplicados)) {
    echo 'Ninguno (todos los pregoneros)';
} else {
    echo '<ul style="margin: 0; padding-left: 20px;">';
    foreach ($filtros_aplicados as $filtro) {
        echo '<li>' . htmlspecialchars($filtro) . '</li>';
    }
    echo '</ul>';
}
echo '</td></tr>';
echo '</table>';

echo '</body></html>';
exit;