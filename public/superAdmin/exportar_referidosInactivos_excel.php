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

// Verificar si el usuario está logueado y es SuperAdmin
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    header('HTTP/1.1 403 Forbidden');
    exit('Acceso denegado');
}

$pdo = Database::getConnection();
$referenciadoModel = new ReferenciadoModel($pdo);

// Obtener todos los referenciados INACTIVOS - CAMBIO PRINCIPAL
$referenciados = $referenciadoModel->getAllReferenciadosInactivos(); // Cambiado de getAllReferenciados()

// Inicializar modelos para obtener nombres de relaciones
$zonaModel = new ZonaModel($pdo);
$sectorModel = new SectorModel($pdo);
$puestoModel = new PuestoVotacionModel($pdo);
$departamentoModel = new DepartamentoModel($pdo);
$municipioModel = new MunicipioModel($pdo);
$ofertaModel = new OfertaApoyoModel($pdo);
$grupoModel = new GrupoPoblacionalModel($pdo);
$barrioModel = new BarrioModel($pdo);

// Obtener todos los datos de relaciones
$zonas = $zonaModel->getAll();
$sectores = $sectorModel->getAll();
$puestos = $puestoModel->getAll();
$departamentos = $departamentoModel->getAll();
$municipios = $municipioModel->getAll();
$ofertas = $ofertaModel->getAll();
$grupos = $grupoModel->getAll();
$barrios = $barrioModel->getAll();

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

// Configurar headers para archivo Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="referidos_inactivos_' . date('Y-m-d_H-i-s') . '.xls"'); // CAMBIO: nombre del archivo
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
echo '<x:Name>Referidos Inactivos</x:Name>'; // CAMBIO: nombre de la hoja
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
echo '</style>';
echo '</head>';
echo '<body>';

// Título del reporte
echo '<h2>REPORTE DE REFERIDOS INACTIVOS</h2>';
echo '<p><strong>Fecha de generación:</strong> ' . date('d/m/Y H:i:s') . '</p>';

// Crear tabla
echo '<table border="1">';
echo '<tr style="background-color: #e74c3c; color: white; font-weight: bold;">'; // CAMBIO: color rojo para inactivos
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
echo '<th>Barrio</th>';
echo '<th>Referenciador</th>';
echo '<th>Fecha Registro</th>';
echo '<th>Última Actualización</th>'; // NUEVO: columna agregada
echo '</tr>';

// Llenar datos
foreach ($referenciados as $referenciado) {
    $activo = $referenciado['activo'] ?? true;
    $esta_activo = ($activo === true || $activo === 't' || $activo == 1);
    $estado = $esta_activo ? 'ACTIVO' : 'INACTIVO'; // Todos deberían ser INACTIVOS
    
    // Resaltar filas inactivas con color más claro
    $bgcolor = !$esta_activo ? 'background-color: #f8d7da;' : ''; // Rojo claro para inactivos
    
    echo '<tr style="' . $bgcolor . '">';
    echo '<td>' . ($referenciado['id_referenciado'] ?? '') . '</td>';
    echo '<td><strong>' . $estado . '</strong></td>'; // Resaltar estado
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
    echo '<td>' . (isset($referenciado['id_barrio']) && isset($barriosMap[$referenciado['id_barrio']]) ? htmlspecialchars($barriosMap[$referenciado['id_barrio']]) : 'N/A') . '</td>';
    echo '<td>' . htmlspecialchars($referenciado['referenciador_nombre'] ?? 'N/A') . '</td>';
    echo '<td>' . (isset($referenciado['fecha_registro']) ? date('d/m/Y H:i', strtotime($referenciado['fecha_registro'])) : '') . '</td>';
    echo '<td>' . (isset($referenciado['fecha_actualizacion']) ? date('d/m/Y H:i', strtotime($referenciado['fecha_actualizacion'])) : '') . '</td>'; // NUEVO
    echo '</tr>';
}

echo '</table>';

// Agregar resumen al final
echo '<br><br>';
echo '<table border="1" style="background-color: #f8f9fa;">';
echo '<tr><th colspan="2" style="background-color: #e74c3c; color: white;">RESUMEN DE REFERIDOS INACTIVOS</th></tr>'; // CAMBIO: color rojo
echo '<tr><td><strong>Total Referidos Inactivos:</strong></td><td>' . count($referenciados) . '</td></tr>'; // CAMBIO: texto

// Contar activos e inactivos (solo para verificación, todos deberían ser inactivos)
$totalActivos = 0;
$totalInactivos = 0;
foreach ($referenciados as $referenciado) {
    $activo = $referenciado['activo'] ?? true;
    $esta_activo = ($activo === true || $activo === 't' || $activo == 1);
    if ($esta_activo) {
        $totalActivos++;
    } else {
        $totalInactivos++;
    }
}

echo '<tr><td><strong>Activos (verificación):</strong></td><td>' . $totalActivos . '</td></tr>';
echo '<tr><td><strong>Inactivos:</strong></td><td><strong>' . $totalInactivos . '</strong></td></tr>';
echo '<tr><td><strong>Fecha de Exportación:</strong></td><td>' . date('d/m/Y H:i:s') . '</td></tr>';
echo '<tr><td><strong>Exportado por:</strong></td><td>' . htmlspecialchars($_SESSION['nombres'] ?? 'Usuario') . ' ' . htmlspecialchars($_SESSION['apellidos'] ?? '') . '</td></tr>';
echo '<tr><td><strong>Tipo de Reporte:</strong></td><td><strong>REFERIDOS INACTIVOS</strong></td></tr>'; // NUEVO: tipo de reporte
echo '</table>';

echo '</body></html>';
exit;