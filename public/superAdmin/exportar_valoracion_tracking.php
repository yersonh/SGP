<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../models/LlamadaModel.php';

// Verificar si el usuario está logueado y es SuperAdmin
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    header('HTTP/1.1 403 Forbidden');
    exit('Acceso denegado');
}

$pdo = Database::getConnection();
$llamadaModel = new LlamadaModel($pdo);
$usuarioModel = new UsuarioModel($pdo);

// Obtener datos del usuario logueado
$usuario_logueado = $usuarioModel->getUsuarioById($_SESSION['id_usuario']);

// Obtener filtros de la URL (mismos que en la vista)
$filtros = [];
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-d', strtotime('-30 days'));
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d');
$id_resultado = $_GET['id_resultado'] ?? '';
$rating_min = $_GET['rating_min'] ?? '';
$rating_max = $_GET['rating_max'] ?? '';
$id_referenciador_filtro = $_GET['id_referenciador'] ?? '';

// Aplicar filtros (misma lógica que en la vista)
if (!empty($fecha_desde)) {
    $filtros['fecha_desde'] = $fecha_desde;
}
if (!empty($fecha_hasta)) {
    $filtros['fecha_hasta'] = $fecha_hasta;
}
if (!empty($id_resultado) && $id_resultado != 'todos') {
    $filtros['id_resultado'] = $id_resultado;
}
if (!empty($rating_min)) {
    $filtros['rating_min'] = $rating_min;
}
if (!empty($rating_max)) {
    $filtros['rating_max'] = $rating_max;
}
if (!empty($id_referenciador_filtro) && $id_referenciador_filtro != 'todos') {
    $filtros['id_referenciador'] = $id_referenciador_filtro;
}

// Obtener datos con filtros aplicados
$referenciadosConLlamadas = $llamadaModel->getReferenciadosConLlamadas($filtros);

// Configurar headers para archivo Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="valoracion_tracking_' . date('Y-m-d_H-i-s') . '.xls"');
header('Cache-Control: max-age=0');

// Crear contenido HTML para Excel
echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
echo '<head>';
echo '<meta charset="UTF-8">';
echo '<style>';
echo 'td { mso-number-format:\@; }'; // Forzar formato de texto para cédulas y teléfonos
echo 'th { background-color: #4e73df; color: white; font-weight: bold; padding: 8px; }';
echo '.resumen { background-color: #f8f9fa; border: 1px solid #dee2e6; padding: 10px; margin-bottom: 20px; }';
echo '.filtros { background-color: #e9ecef; border: 1px solid #ced4da; padding: 10px; margin-bottom: 20px; }';
echo '</style>';
echo '</head>';
echo '<body>';

// Título del reporte
echo '<h2>REPORTE DE VALORACIÓN TRACKING</h2>';
echo '<p><strong>Fecha de generación:</strong> ' . date('d/m/Y H:i:s') . '</p>';
echo '<p><strong>Exportado por:</strong> ' . htmlspecialchars($usuario_logueado['nombres'] . ' ' . $usuario_logueado['apellidos']) . '</p>';

// Mostrar filtros aplicados
echo '<div class="filtros">';
echo '<h3>Filtros Aplicados</h3>';
echo '<table>';
echo '<tr><td><strong>Fecha Desde:</strong></td><td>' . htmlspecialchars($fecha_desde) . '</td></tr>';
echo '<tr><td><strong>Fecha Hasta:</strong></td><td>' . htmlspecialchars($fecha_hasta) . '</td></tr>';
if (!empty($id_resultado) && $id_resultado != 'todos') {
    echo '<tr><td><strong>Resultado:</strong></td><td>' . htmlspecialchars($id_resultado) . '</td></tr>';
}
if (!empty($rating_min)) {
    echo '<tr><td><strong>Rating Mínimo:</strong></td><td>' . htmlspecialchars($rating_min) . ' estrellas</td></tr>';
}
if (!empty($rating_max)) {
    echo '<tr><td><strong>Rating Máximo:</strong></td><td>' . htmlspecialchars($rating_max) . ' estrellas</td></tr>';
}
if (!empty($id_referenciador_filtro) && $id_referenciador_filtro != 'todos') {
    echo '<tr><td><strong>Referenciador:</strong></td><td>' . htmlspecialchars($id_referenciador_filtro) . '</td></tr>';
}
echo '</table>';
echo '</div>';

// Crear tabla principal
echo '<table border="1" style="border-collapse: collapse; width: 100%;">';
echo '<tr>';
echo '<th>#</th>';
echo '<th>Referenciado</th>';
echo '<th>Cédula</th>';
echo '<th>Teléfono</th>';
echo '<th>Email</th>';
echo '<th>Referenciador</th>';
echo '<th>Última Llamada</th>';
echo '<th>Rating</th>';
echo '<th>Resultado</th>';
echo '<th>Total Llamadas</th>';
echo '<th>Estado</th>';
echo '</tr>';

// Llenar datos
$contador = 1;
foreach ($referenciadosConLlamadas as $referenciado) {
    // Formatear fecha
    $fechaLlamada = !empty($referenciado['fecha_llamada']) 
        ? date('d/m/Y H:i', strtotime($referenciado['fecha_llamada']))
        : 'N/A';
    
    // Determinar estado
    $estado = $referenciado['activo'] ? 'Activo' : 'Inactivo';
    $estadoColor = $referenciado['activo'] ? '#28a745' : '#dc3545';
    
    echo '<tr>';
    echo '<td>' . $contador++ . '</td>';
    echo '<td>' . htmlspecialchars($referenciado['nombre'] . ' ' . $referenciado['apellido']) . '</td>';
    echo '<td>' . htmlspecialchars($referenciado['cedula']) . '</td>';
    echo '<td>' . htmlspecialchars($referenciado['telefono']) . '</td>';
    echo '<td>' . htmlspecialchars($referenciado['email'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($referenciado['referenciador_nombre'] ?? 'N/A') . '</td>';
    echo '<td>' . $fechaLlamada . '</td>';
    echo '<td>' . ($referenciado['rating'] ?? '0') . ' / 5</td>';
    echo '<td>' . htmlspecialchars($referenciado['resultado_nombre'] ?? 'No especificado') . '</td>';
    echo '<td>' . ($referenciado['total_llamadas'] ?? '0') . '</td>';
    echo '<td style="color: ' . $estadoColor . '; font-weight: bold;">' . $estado . '</td>';
    echo '</tr>';
}

echo '</table>';
// Mostrar distribución por resultado
if (!empty($distribucionResultados)) {
    echo '<br><br>';
    echo '<div class="resumen">';
    echo '<h3>DISTRIBUCIÓN POR RESULTADO</h3>';
    echo '<table border="1">';
    echo '<tr><th>Resultado</th><th>Cantidad</th><th>Porcentaje</th></tr>';
    
    foreach ($distribucionResultados as $distribucion) {
        $porcentaje = $distribucion['porcentaje'] ?? 0;
        echo '<tr>';
        echo '<td>' . htmlspecialchars($distribucion['resultado'] ?? '') . '</td>';
        echo '<td>' . ($distribucion['cantidad'] ?? 0) . '</td>';
        echo '<td>' . number_format($porcentaje, 2) . '%</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    echo '</div>';
}
// Agregar resumen al final
echo '<br><br>';
echo '<div class="resumen">';
echo '<h3>RESUMEN ESTADÍSTICO</h3>';
echo '<table border="1">';
echo '<tr><th>Indicador</th><th>Valor</th></tr>';

// Calcular estadísticas
$totalRegistros = count($referenciadosConLlamadas);
$totalRating = 0;
$totalLlamadas = 0;
$referenciadosConRating = 0;

foreach ($referenciadosConLlamadas as $ref) {
    $totalLlamadas += $ref['total_llamadas'] ?? 0;
    if (isset($ref['rating']) && $ref['rating'] > 0) {
        $totalRating += $ref['rating'];
        $referenciadosConRating++;
    }
}

$ratingPromedio = $referenciadosConRating > 0 ? round($totalRating / $referenciadosConRating, 2) : 0;

echo '<tr><td>Total Referenciados Contactados</td><td>' . $totalRegistros . '</td></tr>';
echo '<tr><td>Total Llamadas Realizadas</td><td>' . $totalLlamadas . '</td></tr>';
echo '<tr><td>Promedio de Rating</td><td>' . $ratingPromedio . ' / 5</td></tr>';
echo '<tr><td>Referenciados con Calificación</td><td>' . $referenciadosConRating . '</td></tr>';
echo '<tr><td>Fecha de Exportación</td><td>' . date('d/m/Y H:i:s') . '</td></tr>';
echo '</table>';
echo '</div>';

// Pie de página con información del sistema
echo '<br><br>';
echo '<div style="font-size: 11px; color: #666; border-top: 1px solid #ddd; padding-top: 10px;">';
echo '<p><strong>Sistema de Gestión Política - Valoración Tracking</strong></p>';
echo '<p>Exportado desde: ' . $_SERVER['HTTP_HOST'] . ' | Usuario: ' . htmlspecialchars($usuario_logueado['nombres'] . ' ' . $usuario_logueado['apellidos']) . '</p>';
echo '<p>© ' . date('Y') . ' SISGONTech - Todos los derechos reservados</p>';
echo '</div>';

echo '</body></html>';
exit;