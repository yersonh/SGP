<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/ReferenciadoModel.php';

// Verificar si el usuario está logueado y es SuperAdmin
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    header('HTTP/1.1 403 Forbidden');
    exit('Acceso denegado');
}

$pdo = Database::getConnection();
$referenciadoModel = new ReferenciadoModel($pdo);

// Obtener todos los referenciados (con la información del líder incluida)
$referenciados = $referenciadoModel->getAllReferenciados();

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
echo '.small-text { font-size: 10px; }';
echo '</style>';
echo '</head>';
echo '<body>';

// Crear tabla con TODOS los campos (simplificada)
echo '<table border="1" class="small-text">';
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
echo '<th>Sexo</th>';
echo '<th>Fecha Nacimiento</th>';
echo '<th>Compromiso</th>';
echo '<th>Fecha Cumplimiento</th>';
echo '<th>Zona</th>';
echo '<th>Sector</th>';
echo '<th>Puesto Votación</th>';
echo '<th>Mesa</th>';
echo '<th>Vota Fuera</th>';
echo '<th>Puesto Votación Fuera</th>';
echo '<th>Mesa Fuera</th>';
echo '<th>Departamento</th>';
echo '<th>Municipio</th>';
echo '<th>Barrio</th>';
echo '<th>Oferta Apoyo</th>';
echo '<th>Grupo Poblacional</th>';
echo '<th>Grupo Parlamentario</th>';
echo '<th>Referenciador</th>';
echo '<th>Líder</th>';
echo '<th>Cédula Líder</th>';
echo '<th>Teléfono Líder</th>';
echo '<th>Fecha Registro</th>';
echo '<th>Fecha Actualización</th>';
echo '<th>Activo</th>';
echo '</tr>';

// Llenar datos con TODOS los campos
foreach ($referenciados as $referenciado) {
    // Determinar estado
    $activo = $referenciado['activo'] ?? true;
    $esta_activo = ($activo === true || $activo === 't' || $activo == 1);
    $estado = $esta_activo ? 'ACTIVO' : 'INACTIVO';
    $estado_booleano = $esta_activo ? 'SI' : 'NO';
    
    // Información del líder
    $liderNombre = $referenciado['lider_nombre_completo'] ?? 'SIN LÍDER';
    $liderCedula = $referenciado['lider_cedula'] ?? '';
    $liderTelefono = $referenciado['lider_telefono'] ?? '';
    
    // Fechas formateadas
    $fechaNacimiento = !empty($referenciado['fecha_nacimiento']) ? date('d/m/Y', strtotime($referenciado['fecha_nacimiento'])) : '';
    $fechaCumplimiento = !empty($referenciado['fecha_cumplimiento']) ? date('d/m/Y', strtotime($referenciado['fecha_cumplimiento'])) : '';
    $fechaRegistro = !empty($referenciado['fecha_registro']) ? date('d/m/Y H:i', strtotime($referenciado['fecha_registro'])) : '';
    $fechaActualizacion = !empty($referenciado['fecha_actualizacion']) ? date('d/m/Y H:i', strtotime($referenciado['fecha_actualizacion'])) : '';
    
    // Campos de vota fuera
    $votaFuera = $referenciado['vota_fuera'] ?? 'No';
    $puestoFuera = $referenciado['puesto_votacion_fuera'] ?? '';
    $mesaFuera = $referenciado['mesa_fuera'] ?? '';
    
    // Puesto y mesa (usando los campos display que ya manejan vota_fuera)
    $puestoVotacion = $referenciado['puesto_votacion_display'] ?? ($referenciado['puesto_votacion_nombre'] ?? 'N/A');
    $mesa = $referenciado['mesa_display'] ?? ($referenciado['mesa'] ?? '');
    
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
    echo '<td>' . htmlspecialchars($referenciado['sexo'] ?? '') . '</td>';
    echo '<td>' . $fechaNacimiento . '</td>';
    echo '<td>' . htmlspecialchars($referenciado['compromiso'] ?? '') . '</td>';
    echo '<td>' . $fechaCumplimiento . '</td>';
    echo '<td>' . htmlspecialchars($referenciado['zona_nombre'] ?? 'N/A') . '</td>';
    echo '<td>' . htmlspecialchars($referenciado['sector_nombre'] ?? 'N/A') . '</td>';
    echo '<td>' . htmlspecialchars($puestoVotacion) . '</td>';
    echo '<td>' . htmlspecialchars($mesa) . '</td>';
    echo '<td>' . htmlspecialchars($votaFuera) . '</td>';
    echo '<td>' . htmlspecialchars($puestoFuera) . '</td>';
    echo '<td>' . htmlspecialchars($mesaFuera) . '</td>';
    echo '<td>' . htmlspecialchars($referenciado['departamento_nombre'] ?? 'N/A') . '</td>';
    echo '<td>' . htmlspecialchars($referenciado['municipio_nombre'] ?? 'N/A') . '</td>';
    echo '<td>' . htmlspecialchars($referenciado['barrio_nombre'] ?? 'N/A') . '</td>';
    echo '<td>' . htmlspecialchars($referenciado['oferta_apoyo_nombre'] ?? 'N/A') . '</td>';
    echo '<td>' . htmlspecialchars($referenciado['grupo_poblacional_nombre'] ?? 'N/A') . '</td>';
    echo '<td>' . htmlspecialchars($referenciado['grupo_nombre'] ?? 'N/A') . '</td>';
    echo '<td>' . htmlspecialchars($referenciado['referenciador_nombre'] ?? 'N/A') . '</td>';
    echo '<td>' . htmlspecialchars($liderNombre) . '</td>';
    echo '<td>' . htmlspecialchars($liderCedula) . '</td>';
    echo '<td>' . htmlspecialchars($liderTelefono) . '</td>';
    echo '<td>' . $fechaRegistro . '</td>';
    echo '<td>' . $fechaActualizacion . '</td>';
    echo '<td>' . $estado_booleano . '</td>';
    echo '</tr>';
}

echo '</table>';

// Agregar resumen al final con estadísticas completas
echo '<br><br>';
echo '<table border="1" class="summary">';
echo '<tr><th colspan="2" class="header">RESUMEN COMPLETO DE REFERIDOS</th></tr>';
echo '<tr><td><strong>Total Referidos:</strong></td><td>' . count($referenciados) . '</td></tr>';

// Contar activos, inactivos y otras estadísticas
$totalActivos = 0;
$totalInactivos = 0;
$lideresCount = [];
$referidosSinLider = 0;
$porSexo = ['M' => 0, 'F' => 0];
$porVotaFuera = ['Si' => 0, 'No' => 0];

foreach ($referenciados as $referenciado) {
    // Estado
    $activo = $referenciado['activo'] ?? true;
    $esta_activo = ($activo === true || $activo === 't' || $activo == 1);
    if ($esta_activo) {
        $totalActivos++;
    } else {
        $totalInactivos++;
    }
    
    // Por sexo
    $sexo = $referenciado['sexo'] ?? '';
    if ($sexo && isset($porSexo[$sexo])) {
        $porSexo[$sexo]++;
    }
    
    // Por vota fuera
    $votaFuera = $referenciado['vota_fuera'] ?? 'No';
    if (isset($porVotaFuera[$votaFuera])) {
        $porVotaFuera[$votaFuera]++;
    }
    
    // Contar por líder
    $liderNombre = $referenciado['lider_nombre_completo'] ?? '';
    if (!empty($liderNombre)) {
        if (!isset($lideresCount[$liderNombre])) {
            $lideresCount[$liderNombre] = 0;
        }
        $lideresCount[$liderNombre]++;
    } else {
        $referidosSinLider++;
    }
}

echo '<tr><td><strong>Activos:</strong></td><td>' . $totalActivos . '</td></tr>';
echo '<tr><td><strong>Inactivos:</strong></td><td>' . $totalInactivos . '</td></tr>';

// Estadísticas por sexo
if (($porSexo['M'] + $porSexo['F']) > 0) {
    echo '<tr><td colspan="2" style="background-color: #e9ecef; font-weight: bold; text-align: center;">Distribución por Sexo</td></tr>';
    echo '<tr><td>Masculino:</td><td>' . $porSexo['M'] . ' (' . round(($porSexo['M'] / count($referenciados)) * 100, 2) . '%)</td></tr>';
    echo '<tr><td>Femenino:</td><td>' . $porSexo['F'] . ' (' . round(($porSexo['F'] / count($referenciados)) * 100, 2) . '%)</td></tr>';
}

// Estadísticas por vota fuera
echo '<tr><td colspan="2" style="background-color: #e9ecef; font-weight: bold; text-align: center;">Vota Fuera del Municipio</td></tr>';
echo '<tr><td>Sí:</td><td>' . $porVotaFuera['Si'] . ' (' . round(($porVotaFuera['Si'] / count($referenciados)) * 100, 2) . '%)</td></tr>';
echo '<tr><td>No:</td><td>' . $porVotaFuera['No'] . ' (' . round(($porVotaFuera['No'] / count($referenciados)) * 100, 2) . '%)</td></tr>';

// Estadísticas de líderes
echo '<tr><td><strong>Con Líder Asignado:</strong></td><td>' . (count($referenciados) - $referidosSinLider) . '</td></tr>';
echo '<tr><td><strong>Sin Líder Asignado:</strong></td><td>' . $referidosSinLider . '</td></tr>';

// Agregar estadísticas de líderes si hay datos
if (!empty($lideresCount)) {
    echo '<tr><td colspan="2" style="background-color: #e9ecef; font-weight: bold; text-align: center;">Top 5 Líderes con más Referidos</td></tr>';
    arsort($lideresCount); // Ordenar de mayor a menor
    $topLideres = array_slice($lideresCount, 0, 5, true);
    
    $counter = 1;
    foreach ($topLideres as $liderNombre => $count) {
        echo '<tr><td>' . $counter . '. ' . htmlspecialchars($liderNombre) . ':</td><td>' . $count . ' referidos</td></tr>';
        $counter++;
    }
}

// Información de exportación
echo '<tr><td colspan="2" style="background-color: #e9ecef; font-weight: bold; text-align: center;">Información de Exportación</td></tr>';
echo '<tr><td><strong>Fecha de Exportación:</strong></td><td>' . date('d/m/Y H:i:s') . '</td></tr>';
echo '<tr><td><strong>Exportado por:</strong></td><td>' . htmlspecialchars($_SESSION['nombres'] ?? 'Usuario') . ' ' . htmlspecialchars($_SESSION['apellidos'] ?? '') . '</td></tr>';
echo '<tr><td><strong>Sistema:</strong></td><td>Sistema de Gestión Política - SISGONTech</td></tr>';
echo '</table>';

// Información adicional
echo '<br>';
echo '<div style="font-size: 11px; color: #666; padding: 10px; border: 1px solid #ddd; background-color: #f9f9f9;">';
echo '<strong>Notas:</strong><br>';
echo '1. El campo "Puesto Votación" muestra el puesto real de votación (si "Vota Fuera" es "No") o el puesto fuera (si "Vota Fuera" es "Sí").<br>';
echo '2. El campo "Mesa" muestra la mesa real de votación o la mesa fuera según corresponda.<br>';
echo '3. El campo "Activo" indica SI/NO si el referido está activo en el sistema.<br>';
echo '4. "Vota Fuera" indica si el referido vota fuera de su municipio de residencia.<br>';
echo '5. Cuando "Vota Fuera" es "Si", se usan los campos "Puesto Votación Fuera" y "Mesa Fuera".<br>';
echo '6. El campo "Afinidad" indica el nivel de apoyo político (0-10).<br>';
echo '</div>';

echo '</body></html>';
exit;