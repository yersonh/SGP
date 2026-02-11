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
if (empty($filtros)) {
    // Si NO hay filtros, usar getAllReferenciados() que YA FUNCIONA
    $referenciados = $referenciadoModel->getAllReferenciados();
} else {
    // Si HAY filtros, usar el método con filtros
    $referenciados = $referenciadoModel->getReferenciadosFiltrados($filtros);
}

// Ordenar por ID descendente
usort($referenciados, function($a, $b) {
    return ($b['id_referenciado'] ?? 0) <=> ($a['id_referenciado'] ?? 0);
});

// Contar estadísticas
$totalReferidos = count($referenciados);
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
$pdf->SetTitle('Reporte de Referidos');
$pdf->SetSubject('Reporte de referidos del sistema');
$pdf->SetKeywords('referidos, reporte, sistema, política');

// Configurar márgenes
$pdf->SetMargins(10, 15, 10);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(10);
$pdf->SetAutoPageBreak(TRUE, 15);

// Configurar fuente por defecto
$pdf->SetFont('helvetica', '', 9);

// Agregar una página
$pdf->AddPage();

// ============================================
// ENCABEZADO DEL REPORTE
// ============================================
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'REPORTE DE REFERIDOS', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, 'Sistema de Gestión Política - SISGONTech', 0, 1, 'C');

// Línea separadora
$pdf->SetLineWidth(0.5);
$pdf->Line(10, $pdf->GetY(), 290, $pdf->GetY());
$pdf->Ln(5);

// ============================================
// INFORMACIÓN DEL REPORTE Y FILTROS APLICADOS
// ============================================
$pdf->SetFont('helvetica', '', 9);
$infoText = "Fecha de exportación: " . date('d/m/Y H:i:s') . "\n";
$infoText .= "Exportado por: " . ($_SESSION['nombres'] ?? 'Usuario') . ' ' . ($_SESSION['apellidos'] ?? '') . "\n";
$infoText .= "Total referidos: " . number_format($totalReferidos, 0, ',', '.') . 
             " (Activos: " . number_format($totalActivos, 0, ',', '.') . 
             ", Inactivos: " . number_format($totalInactivos, 0, ',', '.') . ")";

// ✅ AGREGAR FILTROS APLICADOS AL TEXTO INFORMATIVO
if (!empty($filtros)) {
    $infoText .= "\n\nFILTROS APLICADOS:";
    
    if (isset($filtros['search'])) {
        $infoText .= "\n• Búsqueda: " . $filtros['search'];
    }
    if (isset($filtros['activo'])) {
        $infoText .= "\n• Estado: " . ($filtros['activo'] ? 'Activos' : 'Inactivos');
    }
    if (isset($filtros['departamento'])) {
        // Obtener nombre del departamento
        $stmt = $pdo->prepare("SELECT nombre FROM departamento WHERE id_departamento = ?");
        $stmt->execute([$filtros['departamento']]);
        $depto = $stmt->fetchColumn();
        $infoText .= "\n• Departamento: " . $depto;
    }
    if (isset($filtros['municipio'])) {
        $stmt = $pdo->prepare("SELECT nombre FROM municipio WHERE id_municipio = ?");
        $stmt->execute([$filtros['municipio']]);
        $muni = $stmt->fetchColumn();
        $infoText .= "\n• Municipio: " . $muni;
    }
    if (isset($filtros['zona'])) {
        $stmt = $pdo->prepare("SELECT nombre FROM zona WHERE id_zona = ?");
        $stmt->execute([$filtros['zona']]);
        $zona = $stmt->fetchColumn();
        $infoText .= "\n• Zona: " . $zona;
    }
    if (isset($filtros['referenciador'])) {
        $stmt = $pdo->prepare("SELECT nombres, apellidos FROM usuario WHERE id_usuario = ?");
        $stmt->execute([$filtros['referenciador']]);
        $ref = $stmt->fetch(PDO::FETCH_ASSOC);
        $infoText .= "\n• Referenciador: " . $ref['nombres'] . ' ' . $ref['apellidos'];
    }
    if (isset($filtros['lider'])) {
        $stmt = $pdo->prepare("SELECT nombres, apellidos FROM lideres WHERE id_lider = ?");
        $stmt->execute([$filtros['lider']]);
        $lider = $stmt->fetch(PDO::FETCH_ASSOC);
        $infoText .= "\n• Líder: " . $lider['nombres'] . ' ' . $lider['apellidos'];
    }
}

$pdf->MultiCell(0, 5, $infoText, 0, 'L');
$pdf->Ln(5);

// ============================================
// TABLA DE REFERIDOS (CON COLUMNA DE LÍDER)
// ============================================
$pdf->SetFont('helvetica', 'B', 8);

// Encabezados de la tabla
$header = array('ID', 'Estado', 'Nombre', 'Apellido', 'Cédula', 'Teléfono', 'Afinidad', 'Zona', 'Sector', 'Referenciador', 'Líder', 'Fecha Reg.');

// Anchos de columna
$widths = array(10, 15, 25, 25, 25, 25, 15, 20, 20, 35, 35, 20);

// Dibujar encabezados
$pdf->SetFillColor(64, 115, 223);
$pdf->SetTextColor(255);
$pdf->SetDrawColor(64, 115, 223);
$pdf->SetLineWidth(0.3);

for ($i = 0; $i < count($header); $i++) {
    $pdf->Cell($widths[$i], 7, $header[$i], 1, 0, 'C', 1);
}
$pdf->Ln();

// Contenido de la tabla
$pdf->SetFont('helvetica', '', 7);
$pdf->SetTextColor(0);
$pdf->SetFillColor(255);
$pdf->SetDrawColor(200);

foreach ($referenciados as $referenciado) {
    // Verificar si necesitamos nueva página
    if ($pdf->GetY() > 180) {
        $pdf->AddPage();
        
        // Redibujar encabezados
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetFillColor(64, 115, 223);
        $pdf->SetTextColor(255);
        for ($i = 0; $i < count($header); $i++) {
            $pdf->Cell($widths[$i], 7, $header[$i], 1, 0, 'C', 1);
        }
        $pdf->Ln();
        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetTextColor(0);
        $pdf->SetFillColor(255);
    }
    
    $activo = $referenciado['activo'] ?? true;
    $esta_activo = ($activo === true || $activo === 't' || $activo == 1);
    $estado = $esta_activo ? 'ACTIVO' : 'INACTIVO';
    
    // Color para estado
    if ($esta_activo) {
        $pdf->SetTextColor(39, 174, 96);
    } else {
        $pdf->SetTextColor(231, 76, 60);
    }
    
    $pdf->Cell($widths[0], 6, $referenciado['id_referenciado'] ?? '', 'LR', 0, 'C', true);
    $pdf->Cell($widths[1], 6, $estado, 'LR', 0, 'C', true);
    
    // Restaurar color negro
    $pdf->SetTextColor(0);
    
    // Nombre
    $nombre = $referenciado['nombre'] ?? '';
    if (strlen($nombre) > 12) {
        $nombre = substr($nombre, 0, 12) . '...';
    }
    $pdf->Cell($widths[2], 6, $nombre, 'LR', 0, 'L', true);
    
    // Apellido
    $apellido = $referenciado['apellido'] ?? '';
    if (strlen($apellido) > 12) {
        $apellido = substr($apellido, 0, 12) . '...';
    }
    $pdf->Cell($widths[3], 6, $apellido, 'LR', 0, 'L', true);
    
    $pdf->Cell($widths[4], 6, $referenciado['cedula'] ?? '', 'LR', 0, 'C', true);
    $pdf->Cell($widths[5], 6, $referenciado['telefono'] ?? '', 'LR', 0, 'C', true);
    $pdf->Cell($widths[6], 6, $referenciado['afinidad'] ?? '0', 'LR', 0, 'C', true);
    
    // Zona
    $zona = $referenciado['zona_nombre'] ?? 'N/A';
    if (strlen($zona) > 10) {
        $zona = substr($zona, 0, 10) . '...';
    }
    $pdf->Cell($widths[7], 6, $zona, 'LR', 0, 'L', true);
    
    // Sector
    $sector = $referenciado['sector_nombre'] ?? 'N/A';
    if (strlen($sector) > 10) {
        $sector = substr($sector, 0, 10) . '...';
    }
    $pdf->Cell($widths[8], 6, $sector, 'LR', 0, 'L', true);
    
    // Referenciador
    $referenciador = $referenciado['referenciador_nombre'] ?? 'N/A';
    if (strlen($referenciador) > 15) {
        $referenciador = substr($referenciador, 0, 15) . '...';
    }
    $pdf->Cell($widths[9], 6, $referenciador, 'LR', 0, 'L', true);
    
    // LÍDER
    $lider = $referenciado['lider_nombre_completo'] ?? 'SIN LÍDER';
    if (strlen($lider) > 15) {
        $lider = substr($lider, 0, 15) . '...';
    }
    $pdf->Cell($widths[10], 6, $lider, 'LR', 0, 'L', true);
    
    // Fecha
    $fecha = isset($referenciado['fecha_registro']) ? date('d/m/Y', strtotime($referenciado['fecha_registro'])) : '';
    $pdf->Cell($widths[11], 6, $fecha, 'LR', 0, 'C', true);
    
    $pdf->Ln();
}

// Cerrar la tabla
$pdf->Cell(array_sum($widths), 0, '', 'T');
$pdf->Ln(8);

// ============================================
// RESUMEN ESTADÍSTICO
// ============================================
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, 'RESUMEN ESTADÍSTICO', 0, 1, 'C');
$pdf->Ln(2);

// Calcular porcentajes
$porcentajeActivos = $totalReferidos > 0 ? ($totalActivos / $totalReferidos) * 100 : 0;
$porcentajeInactivos = $totalReferidos > 0 ? ($totalInactivos / $totalReferidos) * 100 : 0;

// Contar líderes
$lideresCount = [];
$referidosSinLider = 0;

foreach ($referenciados as $referenciado) {
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

$totalConLider = $totalReferidos - $referidosSinLider;
$porcentajeConLider = $totalReferidos > 0 ? ($totalConLider / $totalReferidos) * 100 : 0;
$porcentajeSinLider = $totalReferidos > 0 ? ($referidosSinLider / $totalReferidos) * 100 : 0;

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
    array('Total Referidos', number_format($totalReferidos, 0, ',', '.'), '100%'),
    array('Referidos Activos', number_format($totalActivos, 0, ',', '.'), round($porcentajeActivos, 2) . '%'),
    array('Referidos Inactivos', number_format($totalInactivos, 0, ',', '.'), round($porcentajeInactivos, 2) . '%'),
    array('Con Líder Asignado', number_format($totalConLider, 0, ',', '.'), round($porcentajeConLider, 2) . '%'),
    array('Sin Líder Asignado', number_format($referidosSinLider, 0, ',', '.'), round($porcentajeSinLider, 2) . '%')
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

// Mostrar los 5 líderes con más referidos si hay datos
if (!empty($lideresCount)) {
    $pdf->Ln(3);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(0, 6, 'TOP 5 LÍDERES CON MÁS REFERIDOS:', 0, 1, 'L');
    
    arsort($lideresCount);
    $topLideres = array_slice($lideresCount, 0, 5, true);
    
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetFillColor(245, 245, 245);
    
    $counter = 1;
    foreach ($topLideres as $liderNombre => $count) {
        $liderDisplay = strlen($liderNombre) > 30 ? substr($liderNombre, 0, 30) . '...' : $liderNombre;
        
        $pdf->Cell(10, 6, $counter . '.', 1, 0, 'C', true);
        $pdf->Cell(170, 6, $liderDisplay, 1, 0, 'L', true);
        $pdf->Cell(30, 6, number_format($count, 0, ',', '.') . ' ref.', 1, 0, 'C', true);
        $pdf->Ln();
        $counter++;
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
$pdf->Output('referidos_' . date('Y-m-d_H-i-s') . '.pdf', 'D');
exit;