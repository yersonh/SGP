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

// POR DEFECTO: Solo mostrar los que ya votaron
$filtros['voto_registrado'] = true;

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

// Permitir sobrescribir el filtro de voto_registrado si se envía explícitamente
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
$pregoneros = $pregoneroModel->getPregonerosPaginados(1, 10000, $filtros);

// Ordenar por fecha de voto descendente (los más recientes primero)
usort($pregoneros, function($a, $b) {
    $fechaA = $a['fecha_voto'] ?? '0000-00-00';
    $fechaB = $b['fecha_voto'] ?? '0000-00-00';
    return strcmp($fechaB, $fechaA);
});

// Contar estadísticas
$totalPregoneros = count($pregoneros);
$totalActivos = 0;
$totalInactivos = 0;
$mismoReportante = 0;
$porReferenciador = [];

foreach ($pregoneros as $pregonero) {
    // Estado
    $activo = $pregonero['activo'] ?? true;
    $esta_activo = ($activo === true || $activo === 't' || $activo == 1);
    if ($esta_activo) {
        $totalActivos++;
    } else {
        $totalInactivos++;
    }
    
    // Mismo reportante
    $nombreCompleto = trim(($pregonero['nombres'] ?? '') . ' ' . ($pregonero['apellidos'] ?? ''));
    $quienReporta = trim($pregonero['quien_reporta'] ?? '');
    if (!empty($quienReporta) && strtolower($quienReporta) === strtolower($nombreCompleto)) {
        $mismoReportante++;
    }
    
    // Contar por referenciador
    if (!empty($pregonero['id_referenciador'])) {
        $referenciador = $usuarioModel->getUsuarioById($pregonero['id_referenciador']);
        if ($referenciador) {
            $refNombre = $referenciador['nombres'] . ' ' . $referenciador['apellidos'];
            if (!isset($porReferenciador[$refNombre])) {
                $porReferenciador[$refNombre] = 0;
            }
            $porReferenciador[$refNombre]++;
        }
    }
}

// ============================================
// INCLUIR TCPDF - Ajusta la ruta según tu estructura
// ============================================
$tcpdfPath = __DIR__ . '/../../tcpdf/tcpdf.php';
if (!file_exists($tcpdfPath)) {
    // Si no está ahí, prueba otras ubicaciones comunes
    $tcpdfPath = __DIR__ . '/../../tcpdf/tcpdf.php';
    if (!file_exists($tcpdfPath)) {
        die("Error: No se encontró TCPDF. Asegúrate de que la carpeta 'tcpdf' esté en la raíz del proyecto.");
    }
}

require_once($tcpdfPath);

// Crear nuevo documento PDF
$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

// Configurar información del documento
$pdf->SetCreator('Sistema de Gestión Política');
$pdf->SetAuthor('SISGONTech');
$pdf->SetTitle('Reporte de Pregoneros Descargados');
$pdf->SetSubject('Reporte de pregoneros que ya votaron');
$pdf->SetKeywords('pregoneros, votaron, descargados, reporte, sistema, política');

// Configurar márgenes
$pdf->SetMargins(10, 15, 10);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(10);
$pdf->SetAutoPageBreak(TRUE, 15);

// Configurar fuente por defecto
$pdf->SetFont('helvetica', '', 8);

// Agregar una página
$pdf->AddPage();

// ============================================
// ENCABEZADO DEL REPORTE
// ============================================
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetTextColor(39, 174, 96); // Verde para destacar que votaron
$pdf->Cell(0, 10, 'REPORTE DE PREGONEROS DESCARGADOS', 0, 1, 'C');
$pdf->SetTextColor(0);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, 'Sistema de Gestión Política - SISGONTech', 0, 1, 'C');
$pdf->Cell(0, 5, 'Pregoneros que ya registraron su voto', 0, 1, 'C');

// Línea separadora
$pdf->SetLineWidth(0.5);
$pdf->SetDrawColor(39, 174, 96);
$pdf->Line(10, $pdf->GetY(), 290, $pdf->GetY());
$pdf->SetDrawColor(0);
$pdf->Ln(5);

// ============================================
// INFORMACIÓN DEL REPORTE Y FILTROS APLICADOS
// ============================================
$pdf->SetFont('helvetica', '', 9);
$infoText = "Fecha de exportación: " . date('d/m/Y H:i:s') . "\n";
$infoText .= "Exportado por: " . ($_SESSION['nombres'] ?? 'Usuario') . ' ' . ($_SESSION['apellidos'] ?? '') . "\n";
$infoText .= "Total pregoneros que votaron: " . number_format($totalPregoneros, 0, ',', '.') . 
             " (Activos: " . number_format($totalActivos, 0, ',', '.') . 
             ", Inactivos: " . number_format($totalInactivos, 0, ',', '.') . ")\n";
$infoText .= "Mismo reportante: " . number_format($mismoReportante, 0, ',', '.') . 
             " | Otro reportante: " . number_format($totalPregoneros - $mismoReportante, 0, ',', '.');

// ✅ AGREGAR FILTROS APLICADOS AL TEXTO INFORMATIVO
if (!empty($filtros)) {
    $infoText .= "\n\nFILTROS APLICADOS:";
    $infoText .= "\n• Voto registrado: Sí (filtro principal)";
    
    if (isset($filtros['search'])) {
        $infoText .= "\n• Búsqueda: " . $filtros['search'];
    }
    if (isset($filtros['activo'])) {
        $infoText .= "\n• Estado: " . ($filtros['activo'] ? 'Activos' : 'Inactivos');
    }
    if (isset($filtros['zona'])) {
        $stmt = $pdo->prepare("SELECT nombre FROM zona WHERE id_zona = ?");
        $stmt->execute([$filtros['zona']]);
        $zona = $stmt->fetchColumn();
        $infoText .= "\n• Zona: " . $zona;
    }
    if (isset($filtros['barrio'])) {
        $stmt = $pdo->prepare("SELECT nombre FROM barrio WHERE id_barrio = ?");
        $stmt->execute([$filtros['barrio']]);
        $barrio = $stmt->fetchColumn();
        $infoText .= "\n• Barrio: " . $barrio;
    }
    if (isset($filtros['puesto'])) {
        $stmt = $pdo->prepare("SELECT nombre FROM puesto_votacion WHERE id_puesto = ?");
        $stmt->execute([$filtros['puesto']]);
        $puesto = $stmt->fetchColumn();
        $infoText .= "\n• Puesto de votación: " . $puesto;
    }
    if (isset($filtros['comuna'])) {
        $infoText .= "\n• Comuna: " . $filtros['comuna'];
    }
    if (isset($filtros['corregimiento'])) {
        $infoText .= "\n• Corregimiento: " . $filtros['corregimiento'];
    }
    if (isset($filtros['quien_reporta'])) {
        $infoText .= "\n• Quien reporta: " . $filtros['quien_reporta'];
    }
    if (isset($filtros['id_referenciador'])) {
        $stmt = $pdo->prepare("SELECT nombres, apellidos FROM usuario WHERE id_usuario = ?");
        $stmt->execute([$filtros['id_referenciador']]);
        $ref = $stmt->fetch(PDO::FETCH_ASSOC);
        $infoText .= "\n• Referenciador asignado: " . $ref['nombres'] . ' ' . $ref['apellidos'];
    }
    if (isset($filtros['usuario_registro'])) {
        $stmt = $pdo->prepare("SELECT nombres, apellidos FROM usuario WHERE id_usuario = ?");
        $stmt->execute([$filtros['usuario_registro']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $infoText .= "\n• Registrado por: " . $user['nombres'] . ' ' . $user['apellidos'];
    }
    if (isset($filtros['fecha_desde'])) {
        $infoText .= "\n• Fecha desde: " . $filtros['fecha_desde'];
    }
    if (isset($filtros['fecha_hasta'])) {
        $infoText .= "\n• Fecha hasta: " . $filtros['fecha_hasta'];
    }
}

$pdf->MultiCell(0, 5, $infoText, 0, 'L');
$pdf->Ln(5);

// ============================================
// TABLA DE PREGONEROS DESCARGADOS
// ============================================
$pdf->SetFont('helvetica', 'B', 7);

// Encabezados de la tabla (incluyendo fecha de voto)
$header = array('ID', 'Estado', 'Nombres', 'Apellidos', 'Identif.', 'Teléfono', 'Quien Reporta', 'Mismo', 'Zona', 'Puesto', 'Mesa', 'Referenciador', 'Fecha Voto', 'Registró Voto');

// Anchos de columna ajustados para landscape
$widths = array(8, 12, 22, 22, 18, 18, 25, 10, 15, 25, 10, 30, 18, 18);

// Dibujar encabezados
$pdf->SetFillColor(39, 174, 96); // Verde para el header
$pdf->SetTextColor(255);
$pdf->SetDrawColor(39, 174, 96);
$pdf->SetLineWidth(0.3);

for ($i = 0; $i < count($header); $i++) {
    $pdf->Cell($widths[$i], 7, $header[$i], 1, 0, 'C', 1);
}
$pdf->Ln();

// Contenido de la tabla
$pdf->SetFont('helvetica', '', 6);
$pdf->SetTextColor(0);
$pdf->SetFillColor(255);
$pdf->SetDrawColor(200);

foreach ($pregoneros as $pregonero) {
    // Verificar si necesitamos nueva página
    if ($pdf->GetY() > 180) {
        $pdf->AddPage();
        
        // Redibujar encabezados
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetFillColor(39, 174, 96);
        $pdf->SetTextColor(255);
        for ($i = 0; $i < count($header); $i++) {
            $pdf->Cell($widths[$i], 7, $header[$i], 1, 0, 'C', 1);
        }
        $pdf->Ln();
        $pdf->SetFont('helvetica', '', 6);
        $pdf->SetTextColor(0);
        $pdf->SetFillColor(255);
    }
    
    $activo = $pregonero['activo'] ?? true;
    $esta_activo = ($activo === true || $activo === 't' || $activo == 1);
    $estado = $esta_activo ? 'ACTIVO' : 'INACTIVO';
    
    // Verificar si quien reporta es el mismo pregonero
    $nombreCompleto = trim(($pregonero['nombres'] ?? '') . ' ' . ($pregonero['apellidos'] ?? ''));
    $quienReporta = trim($pregonero['quien_reporta'] ?? '');
    $esMismo = (!empty($quienReporta) && strtolower($quienReporta) === strtolower($nombreCompleto)) ? 'SI' : 'NO';
    
    // Color para estado
    if ($esta_activo) {
        $pdf->SetTextColor(39, 174, 96);
    } else {
        $pdf->SetTextColor(231, 76, 60);
    }
    
    $pdf->Cell($widths[0], 5, $pregonero['id_pregonero'] ?? '', 'LR', 0, 'C', true);
    $pdf->Cell($widths[1], 5, $estado, 'LR', 0, 'C', true);
    
    // Restaurar color negro
    $pdf->SetTextColor(0);
    
    // Nombres
    $nombres = $pregonero['nombres'] ?? '';
    if (strlen($nombres) > 10) {
        $nombres = substr($nombres, 0, 10) . '...';
    }
    $pdf->Cell($widths[2], 5, $nombres, 'LR', 0, 'L', true);
    
    // Apellidos
    $apellidos = $pregonero['apellidos'] ?? '';
    if (strlen($apellidos) > 10) {
        $apellidos = substr($apellidos, 0, 10) . '...';
    }
    $pdf->Cell($widths[3], 5, $apellidos, 'LR', 0, 'L', true);
    
    // Identificación
    $identificacion = $pregonero['identificacion'] ?? '';
    if (strlen($identificacion) > 8) {
        $identificacion = substr($identificacion, 0, 8) . '...';
    }
    $pdf->Cell($widths[4], 5, $identificacion, 'LR', 0, 'C', true);
    
    // Teléfono
    $pdf->Cell($widths[5], 5, $pregonero['telefono'] ?? '', 'LR', 0, 'C', true);
    
    // Quien reporta
    $quienReportaDisplay = $quienReporta ?: 'N/A';
    if (strlen($quienReportaDisplay) > 12) {
        $quienReportaDisplay = substr($quienReportaDisplay, 0, 12) . '...';
    }
    $pdf->Cell($widths[6], 5, $quienReportaDisplay, 'LR', 0, 'L', true);
    
    // Mismo reportante
    if ($esMismo === 'SI') {
        $pdf->SetTextColor(79, 195, 247);
    }
    $pdf->Cell($widths[7], 5, $esMismo, 'LR', 0, 'C', true);
    $pdf->SetTextColor(0);
    
    // Zona
    $zona = $pregonero['zona_nombre'] ?? 'N/A';
    if (strlen($zona) > 8) {
        $zona = substr($zona, 0, 8) . '...';
    }
    $pdf->Cell($widths[8], 5, $zona, 'LR', 0, 'L', true);
    
    // Puesto
    $puesto = $pregonero['puesto_nombre'] ?? 'N/A';
    if (strlen($puesto) > 12) {
        $puesto = substr($puesto, 0, 12) . '...';
    }
    $pdf->Cell($widths[9], 5, $puesto, 'LR', 0, 'L', true);
    
    // Mesa
    $pdf->Cell($widths[10], 5, $pregonero['mesa'] ?? '', 'LR', 0, 'C', true);
    
    // Referenciador
    $referenciadorNombre = 'N/A';
    if (!empty($pregonero['id_referenciador'])) {
        $referenciador = $usuarioModel->getUsuarioById($pregonero['id_referenciador']);
        if ($referenciador) {
            $referenciadorNombre = $referenciador['nombres'] . ' ' . $referenciador['apellidos'];
        }
    }
    if (strlen($referenciadorNombre) > 15) {
        $referenciadorNombre = substr($referenciadorNombre, 0, 15) . '...';
    }
    $pdf->Cell($widths[11], 5, $referenciadorNombre, 'LR', 0, 'L', true);
    
    // Fecha de voto
    $fechaVoto = isset($pregonero['fecha_voto']) ? date('d/m/Y', strtotime($pregonero['fecha_voto'])) : 'N/A';
    $pdf->Cell($widths[12], 5, $fechaVoto, 'LR', 0, 'C', true);
    
    // Usuario que registró voto
    $usuarioVoto = $pregonero['usuario_voto_nombre'] ?? 'N/A';
    if (strlen($usuarioVoto) > 10) {
        $usuarioVoto = substr($usuarioVoto, 0, 10) . '...';
    }
    $pdf->Cell($widths[13], 5, $usuarioVoto, 'LR', 0, 'L', true);
    
    $pdf->Ln();
}

// Cerrar la tabla
$pdf->Cell(array_sum($widths), 0, '', 'T');
$pdf->Ln(8);

// ============================================
// RESUMEN ESTADÍSTICO
// ============================================
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor(39, 174, 96);
$pdf->Cell(0, 6, 'RESUMEN DE PREGONEROS DESCARGADOS', 0, 1, 'C');
$pdf->SetTextColor(0);
$pdf->SetFont('helvetica', '', 9);
$pdf->Ln(2);

// Calcular porcentajes
$porcentajeActivos = $totalPregoneros > 0 ? ($totalActivos / $totalPregoneros) * 100 : 0;
$porcentajeInactivos = $totalPregoneros > 0 ? ($totalInactivos / $totalPregoneros) * 100 : 0;
$porcentajeMismoReportante = $totalPregoneros > 0 ? ($mismoReportante / $totalPregoneros) * 100 : 0;
$porcentajeOtroReportante = $totalPregoneros > 0 ? (($totalPregoneros - $mismoReportante) / $totalPregoneros) * 100 : 0;

// Crear tabla de resumen
$summaryWidths = array(70, 30, 30);
$pdf->SetFont('helvetica', '', 9);
$pdf->SetFillColor(240, 240, 240);
$pdf->SetDrawColor(200);

// Encabezado resumen
$pdf->Cell($summaryWidths[0], 8, 'ESTADÍSTICA', 1, 0, 'C', true);
$pdf->Cell($summaryWidths[1], 8, 'CANTIDAD', 1, 0, 'C', true);
$pdf->Cell($summaryWidths[2], 8, 'PORCENTAJE', 1, 0, 'C', true);
$pdf->Ln();

// Datos del resumen
$pdf->SetFillColor(255, 255, 255);

$summaryData = array(
    array('Total Pregoneros que votaron', number_format($totalPregoneros, 0, ',', '.'), '100%'),
    array('Activos', number_format($totalActivos, 0, ',', '.'), round($porcentajeActivos, 2) . '%'),
    array('Inactivos', number_format($totalInactivos, 0, ',', '.'), round($porcentajeInactivos, 2) . '%'),
    array('Mismo Reportante', number_format($mismoReportante, 0, ',', '.'), round($porcentajeMismoReportante, 2) . '%'),
    array('Otro Reportante', number_format($totalPregoneros - $mismoReportante, 0, ',', '.'), round($porcentajeOtroReportante, 2) . '%')
);

foreach ($summaryData as $row) {
    $pdf->Cell($summaryWidths[0], 7, $row[0], 'LR', 0, 'L', true);
    $pdf->Cell($summaryWidths[1], 7, $row[1], 'LR', 0, 'C', true);
    $pdf->Cell($summaryWidths[2], 7, $row[2], 'LR', 0, 'C', true);
    $pdf->Ln();
}

// Cerrar tabla resumen
$pdf->Cell(array_sum($summaryWidths), 0, '', 'T');
$pdf->Ln(5);

// Mostrar los 5 referenciadores con más pregoneros descargados si hay datos
if (!empty($porReferenciador)) {
    $pdf->Ln(3);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(0, 6, 'TOP 5 REFERENCIADORES CON MÁS PREGONEROS DESCARGADOS:', 0, 1, 'L');
    
    arsort($porReferenciador);
    $topReferenciadores = array_slice($porReferenciador, 0, 5, true);
    
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetFillColor(245, 245, 245);
    
    $counter = 1;
    foreach ($topReferenciadores as $refNombre => $count) {
        $refDisplay = strlen($refNombre) > 30 ? substr($refNombre, 0, 30) . '...' : $refNombre;
        
        $pdf->Cell(10, 6, $counter . '.', 1, 0, 'C', true);
        $pdf->Cell(170, 6, $refDisplay, 1, 0, 'L', true);
        $pdf->Cell(30, 6, number_format($count, 0, ',', '.') . ' preg.', 1, 0, 'C', true);
        $pdf->Ln();
        $counter++;
    }
}

// Agregar estadísticas por mes (opcional)
$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(0, 6, 'DISTRIBUCIÓN POR FECHA DE VOTO:', 0, 1, 'L');

// Agrupar por mes
$votosPorMes = [];
foreach ($pregoneros as $pregonero) {
    if (!empty($pregonero['fecha_voto'])) {
        $mes = date('Y-m', strtotime($pregonero['fecha_voto']));
        if (!isset($votosPorMes[$mes])) {
            $votosPorMes[$mes] = 0;
        }
        $votosPorMes[$mes]++;
    }
}

if (!empty($votosPorMes)) {
    krsort($votosPorMes); // Ordenar descendente (más recientes primero)
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetFillColor(245, 245, 245);
    
    $counter = 1;
    foreach ($votosPorMes as $mes => $count) {
        $mesNombre = date('F Y', strtotime($mes . '-01'));
        setlocale(LC_TIME, 'es_ES.UTF-8', 'Spanish_Spain', 'Spanish');
        $mesEspanol = strftime('%B %Y', strtotime($mes . '-01'));
        
        $pdf->Cell(10, 6, $counter . '.', 1, 0, 'C', true);
        $pdf->Cell(170, 6, ucfirst($mesEspanol), 1, 0, 'L', true);
        $pdf->Cell(30, 6, number_format($count, 0, ',', '.') . ' votos', 1, 0, 'C', true);
        $pdf->Ln();
        $counter++;
        if ($counter > 6) break; // Mostrar solo últimos 6 meses
    }
}

$pdf->Ln(10);

// ============================================
// PIE DE PÁGINA
// ============================================
$pdf->SetY(-20);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->SetTextColor(100);

$footerText = "Sistema de Gestión Política - SISGONTech | ";
$footerText .= "Email: sisgonnet@gmail.com | Contacto: +57 3106310227 | ";
$footerText .= "Página " . $pdf->getAliasNumPage() . " de " . $pdf->getAliasNbPages();

$pdf->Cell(0, 5, $footerText, 0, 0, 'C');
$pdf->Ln();
$pdf->Cell(0, 5, '© ' . date('Y') . ' Derechos reservados - Ing. Rubén Darío González García', 0, 0, 'C');

// ============================================
// SALIDA DEL PDF
// ============================================
$pdf->Output('pregoneros_descargados_' . date('Y-m-d_H-i-s') . '.pdf', 'D');
exit;