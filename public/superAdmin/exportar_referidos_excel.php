<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/ReferenciadoModel.php';
require_once __DIR__ . '/../../models/ZonaModel.php';
require_once __DIR__ . '/../../models/SectorModel.php';
require_once __DIR__ . '/../../models/PuestoVotacionModel.php';
require_once __DIR__ . '/../../models/DepartamentoModel.php';
require_once __DIR__ . '/../../models/MunicipioModel.php';
require_once __DIR__ . '/../../models/OfertaApoyoModel.php';
require_once __DIR__ . '/../../models/GrupoPoblacionalModel.php';
require_once __DIR__ . '/../../models/BarrioModel.php';
require_once __DIR__ . '/../../models/LiderModel.php'; // IMPORTANTE: Agregar modelo de Líder

// Verificar si el usuario está logueado y es SuperAdmin
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    header('HTTP/1.1 403 Forbidden');
    exit('Acceso denegado');
}

$pdo = Database::getConnection();
$referenciadoModel = new ReferenciadoModel($pdo);

// Obtener todos los referenciados
$referenciados = $referenciadoModel->getAllReferenciados();

// Inicializar modelos para obtener nombres de relaciones
$zonaModel = new ZonaModel($pdo);
$sectorModel = new SectorModel($pdo);
$puestoModel = new PuestoVotacionModel($pdo);
$departamentoModel = new DepartamentoModel($pdo);
$municipioModel = new MunicipioModel($pdo);
$ofertaModel = new OfertaApoyoModel($pdo);
$grupoModel = new GrupoPoblacionalModel($pdo);
$barrioModel = new BarrioModel($pdo);
$liderModel = new LiderModel($pdo); // Nuevo modelo para líderes

// Obtener todos los datos de relaciones
$zonas = $zonaModel->getAll();
$sectores = $sectorModel->getAll();
$puestos = $puestoModel->getAll();
$departamentos = $departamentoModel->getAll();
$municipios = $municipioModel->getAll();
$ofertas = $ofertaModel->getAll();
$grupos = $grupoModel->getAll();
$barrios = $barrioModel->getAll();
$lideres = $liderModel->getAll(); // Obtener todos los líderes

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

// Mapa para líderes - CORREGIDO: usar id_lider como clave
$lideresMap = [];
foreach ($lideres as $lider) {
    $nombreCompleto = $lider['nombres'] . ' ' . $lider['apellidos'];
    if (!empty($lider['cc'])) {
        $nombreCompleto .= ' (' . $lider['cc'] . ')';
    }
    $lideresMap[$lider['id_lider']] = $nombreCompleto;
}

// Configurar headers para archivo Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="referidos_' . date('Y-m-d_H-i-s') . '.xls"');
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
echo '<x:Name>Referidos</x:Name>';
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
echo '</style>';
echo '</head>';
echo '<body>';

// Crear tabla
echo '<table border="1">';
echo '<tr class="header">';
echo '<th>ID</th>';
echo '<th>Estado</th>';
echo '<th>Nombre</th>';
echo '<th>Apellido</th>';
echo '<th>Cédula</th>';
echo '<th>Dirección</th>';
echo '<th>Email</th>';
echo '<th>Teléfono</th>';
echo '<th>Afinidad</th>';
echo '<th>Zona</th>';
echo '<th>Sector</th>';
echo '<th>Puesto Votación</th>';
echo '<th>Mesa</th>';
echo '<th>Departamento</th>';
echo '<th>Municipio</th>';
echo '<th>Oferta Apoyo</th>';
echo '<th>Grupo Poblacional</th>';
echo '<th>Grupo Parlamentario</th>';
echo '<th>Barrio</th>';
echo '<th>Referenciador</th>';
echo '<th>Líder</th>'; // NUEVA COLUMNA AÑADIDA AQUÍ
echo '<th>Fecha Registro</th>';
echo '</tr>';

// Llenar datos
foreach ($referenciados as $referenciado) {
    $activo = $referenciado['activo'] ?? true;
    $esta_activo = ($activo === true || $activo === 't' || $activo == 1);
    $estado = $esta_activo ? 'ACTIVO' : 'INACTIVO';
    
    // Obtener nombre del líder - CORREGIDO: usar id_lider_referenciado
    $nombreLider = 'SIN LÍDER';
    if (!empty($referenciado['id_lider']) && isset($lideresMap[$referenciado['id_lider']])) {
        $nombreLider = $lideresMap[$referenciado['id_lider']];
    } elseif (!empty($referenciado['lider_nombre'])) {
        // Si viene el nombre del líder directamente desde la consulta
        $nombreLider = $referenciado['lider_nombre'];
    }
    
    echo '<tr>';
    echo '<td>' . ($referenciado['id_referenciado'] ?? '') . '</td>';
    echo '<td>' . $estado . '</td>';
    echo '<td>' . htmlspecialchars($referenciado['nombre'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($referenciado['apellido'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($referenciado['cedula'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($referenciado['direccion'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($referenciado['email'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($referenciado['telefono'] ?? '') . '</td>';
    echo '<td>' . ($referenciado['afinidad'] ?? '0') . '</td>';
    echo '<td>' . (isset($referenciado['id_zona']) && isset($zonasMap[$referenciado['id_zona']]) ? htmlspecialchars($zonasMap[$referenciado['id_zona']]) : 'N/A') . '</td>';
    echo '<td>' . (isset($referenciado['id_sector']) && isset($sectoresMap[$referenciado['id_sector']]) ? htmlspecialchars($sectoresMap[$referenciado['id_sector']]) : 'N/A') . '</td>';
    echo '<td>' . (isset($referenciado['id_puesto_votacion']) && isset($puestosMap[$referenciado['id_puesto_votacion']]) ? htmlspecialchars($puestosMap[$referenciado['id_puesto_votacion']]) : 'N/A') . '</td>';
    echo '<td>' . htmlspecialchars($referenciado['mesa'] ?? '') . '</td>';
    echo '<td>' . (isset($referenciado['id_departamento']) && isset($departamentosMap[$referenciado['id_departamento']]) ? htmlspecialchars($departamentosMap[$referenciado['id_departamento']]) : 'N/A') . '</td>';
    echo '<td>' . (isset($referenciado['id_municipio']) && isset($municipiosMap[$referenciado['id_municipio']]) ? htmlspecialchars($municipiosMap[$referenciado['id_municipio']]) : 'N/A') . '</td>';
    echo '<td>' . (isset($referenciado['id_oferta_apoyo']) && isset($ofertasMap[$referenciado['id_oferta_apoyo']]) ? htmlspecialchars($ofertasMap[$referenciado['id_oferta_apoyo']]) : 'N/A') . '</td>';
    echo '<td>' . (isset($referenciado['id_grupo_poblacional']) && isset($gruposMap[$referenciado['id_grupo_poblacional']]) ? htmlspecialchars($gruposMap[$referenciado['id_grupo_poblacional']]) : 'N/A') . '</td>';
    echo '<td>' . (!empty($referenciado['grupo_nombre']) ? htmlspecialchars($referenciado['grupo_nombre']) : 'N/A') . '</td>';
    echo '<td>' . (isset($referenciado['id_barrio']) && isset($barriosMap[$referenciado['id_barrio']]) ? htmlspecialchars($barriosMap[$referenciado['id_barrio']]) : 'N/A') . '</td>';
    echo '<td>' . htmlspecialchars($referenciado['referenciador_nombre'] ?? 'N/A') . '</td>';
    echo '<td>' . htmlspecialchars($nombreLider) . '</td>'; // NUEVA COLUMNA DE DATOS
    echo '<td>' . (isset($referenciado['fecha_registro']) ? date('d/m/Y H:i', strtotime($referenciado['fecha_registro'])) : '') . '</td>';
    echo '</tr>';
}

echo '</table>';

// Agregar resumen al final con estadísticas de líderes
echo '<br><br>';
echo '<table border="1" class="summary">';
echo '<tr><th colspan="2" class="header">RESUMEN DE REFERIDOS</th></tr>';
echo '<tr><td><strong>Total Referidos:</strong></td><td>' . count($referenciados) . '</td></tr>';

// Contar activos e inactivos
$totalActivos = 0;
$totalInactivos = 0;
$lideresCount = [];

foreach ($referenciados as $referenciado) {
    $activo = $referenciado['activo'] ?? true;
    $esta_activo = ($activo === true || $activo === 't' || $activo == 1);
    if ($esta_activo) {
        $totalActivos++;
    } else {
        $totalInactivos++;
    }
    
    // Contar por líder
    $liderId = $referenciado['id_lider'] ?? null;
    if ($liderId && isset($lideresMap[$liderId])) {
        $liderNombre = $lideresMap[$liderId];
        if (!isset($lideresCount[$liderNombre])) {
            $lideresCount[$liderNombre] = 0;
        }
        $lideresCount[$liderNombre]++;
    }
}

echo '<tr><td><strong>Activos:</strong></td><td>' . $totalActivos . '</td></tr>';
echo '<tr><td><strong>Inactivos:</strong></td><td>' . $totalInactivos . '</td></tr>';

// Agregar estadísticas de líderes si hay datos
if (!empty($lideresCount)) {
    echo '<tr><td colspan="2" style="background-color: #e9ecef; font-weight: bold; text-align: center;">Distribución por Líder</td></tr>';
    arsort($lideresCount); // Ordenar de mayor a menor
    foreach ($lideresCount as $liderNombre => $count) {
        echo '<tr><td>' . htmlspecialchars($liderNombre) . ':</td><td>' . $count . ' referidos</td></tr>';
    }
}

echo '<tr><td><strong>Fecha de Exportación:</strong></td><td>' . date('d/m/Y H:i:s') . '</td></tr>';
echo '<tr><td><strong>Exportado por:</strong></td><td>' . htmlspecialchars($_SESSION['nombres'] ?? 'Usuario') . ' ' . htmlspecialchars($_SESSION['apellidos'] ?? '') . '</td></tr>';
echo '</table>';

echo '</body></html>';
exit;