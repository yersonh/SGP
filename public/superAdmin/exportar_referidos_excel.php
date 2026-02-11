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

// Filtros avanzados
if (isset($_GET['departamento']) && !empty($_GET['departamento'])) {
    $filtros['departamento'] = $_GET['departamento'];
}
if (isset($_GET['municipio']) && !empty($_GET['municipio'])) {
    $filtros['municipio'] = $_GET['municipio'];
}
if (isset($_GET['zona']) && !empty($_GET['zona'])) {
    $filtros['zona'] = $_GET['zona'];
}
if (isset($_GET['referenciador']) && !empty($_GET['referenciador'])) {
    $filtros['referenciador'] = $_GET['referenciador'];
}
if (isset($_GET['lider']) && !empty($_GET['lider'])) {
    $filtros['lider'] = $_GET['lider'];
}

// ✅ OBTENER REFERENCIADOS CON LOS FILTROS APLICADOS
// Usar el mismo método que get_referenciados.php pero sin paginación
$referenciados = $referenciadoModel->getReferenciadosFiltrados($filtros);

// Configurar headers para archivo Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="referidos_filtrados_' . date('Y-m-d_H-i-s') . '.xls"');
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
echo '.filtros-aplicados { background-color: #fff3cd; color: #856404; }';
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
    if (isset($filtros['departamento'])) {
        // Obtener nombre del departamento
        $stmt = $pdo->prepare("SELECT nombre FROM departamento WHERE id_departamento = ?");
        $stmt->execute([$filtros['departamento']]);
        $depto = $stmt->fetchColumn();
        echo '<tr><td><strong>Departamento:</strong></td><td>' . htmlspecialchars($depto) . '</td></tr>';
    }
    if (isset($filtros['municipio'])) {
        $stmt = $pdo->prepare("SELECT nombre FROM municipio WHERE id_municipio = ?");
        $stmt->execute([$filtros['municipio']]);
        $muni = $stmt->fetchColumn();
        echo '<tr><td><strong>Municipio:</strong></td><td>' . htmlspecialchars($muni) . '</td></tr>';
    }
    if (isset($filtros['zona'])) {
        $stmt = $pdo->prepare("SELECT nombre FROM zona WHERE id_zona = ?");
        $stmt->execute([$filtros['zona']]);
        $zona = $stmt->fetchColumn();
        echo '<tr><td><strong>Zona:</strong></td><td>' . htmlspecialchars($zona) . '</td></tr>';
    }
    if (isset($filtros['referenciador'])) {
        $stmt = $pdo->prepare("SELECT nombres, apellidos FROM usuario WHERE id_usuario = ?");
        $stmt->execute([$filtros['referenciador']]);
        $ref = $stmt->fetch(PDO::FETCH_ASSOC);
        echo '<tr><td><strong>Referenciador:</strong></td><td>' . htmlspecialchars($ref['nombres'] . ' ' . $ref['apellidos']) . '</td></tr>';
    }
    if (isset($filtros['lider'])) {
        $stmt = $pdo->prepare("SELECT nombres, apellidos FROM usuario WHERE id_usuario = ?");
        $stmt->execute([$filtros['lider']]);
        $lider = $stmt->fetch(PDO::FETCH_ASSOC);
        echo '<tr><td><strong>Líder:</strong></td><td>' . htmlspecialchars($lider['nombres'] . ' ' . $lider['apellidos']) . '</td></tr>';
    }
    
    echo '<tr><td><strong>Total registros:</strong></td><td>' . count($referenciados) . '</td></tr>';
    echo '<tr><td><strong>Fecha exportación:</strong></td><td>' . date('d/m/Y H:i:s') . '</td></tr>';
    echo '</table>';
}

// Crear tabla con TODOS los campos
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

// ✅ LLENAR DATOS SOLO CON LOS REGISTROS FILTRADOS
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
    
    // Puesto y mesa
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

// ✅ RESUMEN ESTADÍSTICO SOLO DE LOS DATOS FILTRADOS
echo '<br><br>';
echo '<table border="1" class="summary">';
echo '<tr><th colspan="2" class="header">RESUMEN DE REFERIDOS FILTRADOS</th></tr>';
echo '<tr><td><strong>Total Referidos (con filtros):</strong></td><td>' . count($referenciados) . '</td></tr>';

// Contar estadísticas solo de los registros filtrados
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
    $totalConSexo = $porSexo['M'] + $porSexo['F'];
    echo '<tr><td>Masculino:</td><td>' . $porSexo['M'] . ' (' . ($totalConSexo > 0 ? round(($porSexo['M'] / $totalConSexo) * 100, 2) : 0) . '%)</td></tr>';
    echo '<tr><td>Femenino:</td><td>' . $porSexo['F'] . ' (' . ($totalConSexo > 0 ? round(($porSexo['F'] / $totalConSexo) * 100, 2) : 0) . '%)</td></tr>';
}

// Estadísticas por vota fuera
$totalVotaFuera = $porVotaFuera['Si'] + $porVotaFuera['No'];
if ($totalVotaFuera > 0) {
    echo '<tr><td colspan="2" style="background-color: #e9ecef; font-weight: bold; text-align: center;">Vota Fuera del Municipio</td></tr>';
    echo '<tr><td>Sí:</td><td>' . $porVotaFuera['Si'] . ' (' . round(($porVotaFuera['Si'] / $totalVotaFuera) * 100, 2) . '%)</td></tr>';
    echo '<tr><td>No:</td><td>' . $porVotaFuera['No'] . ' (' . round(($porVotaFuera['No'] / $totalVotaFuera) * 100, 2) . '%)</td></tr>';
}

// Estadísticas de líderes
echo '<tr><td><strong>Con Líder Asignado:</strong></td><td>' . (count($referenciados) - $referidosSinLider) . '</td></tr>';
echo '<tr><td><strong>Sin Líder Asignado:</strong></td><td>' . $referidosSinLider . '</td></tr>';

// Agregar estadísticas de líderes si hay datos
if (!empty($lideresCount)) {
    echo '<tr><td colspan="2" style="background-color: #e9ecef; font-weight: bold; text-align: center;">Top 5 Líderes con más Referidos (en esta exportación)</td></tr>';
    arsort($lideresCount);
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
echo '<tr><td><strong>Tipo de Exportación:</strong></td><td>' . (empty($filtros) ? 'Todos los referidos' : 'Referidos filtrados') . '</td></tr>';
echo '</table>';

echo '</body></html>';
exit;