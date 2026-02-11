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

// Filtrar por activos si se solicita
$soloActivos = isset($_GET['solo_activos']) && $_GET['solo_activos'] == 1;
$referenciados = $referenciadoModel->getReferenciadosByUsuario($id_usuario_logueado);

// Si solo activos, filtrar
if ($soloActivos) {
    $referenciados = array_filter($referenciados, function($referenciado) {
        $activo = $referenciado['activo'] ?? true;
        return ($activo === true || $activo === 't' || $activo == 1);
    });
}

// Ordenar por ID ascendente
usort($referenciados, function($a, $b) {
    return ($a['id_referenciado'] ?? 0) <=> ($b['id_referenciado'] ?? 0);
});

// Contar estadísticas
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
    if (empty($referenciado['lider_nombre_completo'])) {
        $referidosSinLider++;
    }
}

// Configurar headers para archivo Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="mis_referenciados_' . date('Y-m-d_H-i-s') . '.xls"');
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
echo '<th>Teléfono</th>';
echo '<th>Email</th>';
echo '<th>Afinidad</th>';
echo '<th>Grupo Parlamentario</th>';
echo '<th>Líder</th>'; // NUEVA COLUMNA
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
    
    // Obtener nombre del líder
    $liderNombre = !empty($referenciado['lider_nombre_completo']) ? 
                   $referenciado['lider_nombre_completo'] : 'SIN LÍDER';
    
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
    echo '<td>' . htmlspecialchars($liderNombre) . '</td>'; // NUEVA COLUMNA DE DATOS
    
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

// Agregar resumen al final (con estadísticas de líderes)
echo '<br><br>';
echo '<table border="1" class="summary">';
echo '<tr><th colspan="2" class="header">RESUMEN DE MIS REFERENCIADOS</th></tr>';
echo '<tr><td><strong>Total Referidos:</strong></td><td>' . $totalReferidos . '</td></tr>';
echo '<tr><td><strong>Activos:</strong></td><td>' . $totalActivos . '</td></tr>';
echo '<tr><td><strong>Inactivos:</strong></td><td>' . $totalInactivos . '</td></tr>';
echo '<tr><td><strong>Sin Líder Asignado:</strong></td><td>' . $referidosSinLider . '</td></tr>';

// Contar distribución por líder (solo para los referidos de este referenciador)
$lideresCount = [];
foreach ($referenciados as $referenciado) {
    $liderNombre = $referenciado['lider_nombre_completo'] ?? '';
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
echo '<tr><td><strong>Tope del Referenciador:</strong></td><td>' . ($usuario['total_referenciados'] ?? 0) . '/' . ($usuario['tope'] ?? 0) . '</td></tr>';
echo '<tr><td><strong>Porcentaje completado:</strong></td><td>' . ($usuario['porcentaje_tope'] ?? 0) . '%</td></tr>';
echo '</table>';

echo '</body></html>';
exit;