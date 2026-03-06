<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/PregoneroModel.php';
require_once __DIR__ . '/../models/UsuarioModel.php';

// Verificar si el usuario está logueado y es referenciador
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'Referenciador') {
    header('HTTP/1.1 403 Forbidden');
    exit('Acceso denegado');
}

$id_usuario_logueado = $_SESSION['id_usuario'];
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
$filtro_referenciador = isset($_GET['id_referenciador']) ? $_GET['id_referenciador'] : '';
$fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
$fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// ============================================
// Obtener pregoneros del usuario logueado
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
if (!empty($filtro_referenciador)) {
    $filtros['id_referenciador'] = $filtro_referenciador;
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

// IMPORTANTE: Filtrar por el usuario logueado (solo sus pregoneros)
$filtros['id_usuario_registro'] = $id_usuario_logueado;

// Obtener todos los pregoneros con los filtros aplicados
$pregoneros = $pregoneroModel->getAll($filtros);

// Re-indexar array después de los filtros
$pregoneros = array_values($pregoneros);

// Si no hay resultados, mostrar mensaje y salir
if (empty($pregoneros)) {
    // Incluir TCPDF para mostrar mensaje
    $tcpdfPath = __DIR__ . '/../tcpdf/tcpdf.php';
    if (!file_exists($tcpdfPath)) {
        $tcpdfPath = __DIR__ . '/../tcpdf/tcpdf.php';
    }
    require_once($tcpdfPath);
    
    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Sistema de Gestión Política');
    $pdf->SetAuthor('SISGONTech');
    $pdf->SetTitle('Sin resultados');
    $pdf->AddPage();
    
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 20, 'NO HAY RESULTADOS', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'No se encontraron pregoneros con los filtros aplicados:', 0, 1, 'C');
    $pdf->Ln(10);
    
    if (!empty($search)) {
        $pdf->Cell(0, 10, '• Búsqueda: ' . htmlspecialchars($search), 0, 1, 'C');
    }
    if ($filtro_estado !== 'todos') {
        $pdf->Cell(0, 10, '• Estado: ' . $filtro_estado, 0, 1, 'C');
    }
    if (!empty($filtro_zona)) {
        $pdf->Cell(0, 10, '• Zona ID: ' . $filtro_zona, 0, 1, 'C');
    }
    if (!empty($filtro_voto_registrado)) {
        $pdf->Cell(0, 10, '• Voto: ' . ($filtro_voto_registrado == '1' ? 'Registrado' : 'No registrado'), 0, 1, 'C');
    }
    
    $pdf->Output('sin_resultados_pregoneros_' . date('Y-m-d_H-i-s') . '.pdf', 'D');
    exit;
}

// ORDENAR POR ID DE FORMA DESCENDENTE (últimos primero)
usort($pregoneros, function($a, $b) {
    return ($b['id_pregonero'] ?? 0) <=> ($a['id_pregonero'] ?? 0);
});

// Contar estadísticas (después de aplicar filtros)
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
    $voto_registrado = !empty($pregonero['voto_registrado']) && $pregonero['voto_registrado'] == true;
    if ($voto_registrado) {
        $totalVotaron++;
    } else {
        $totalPendientes++;
    }
}

// ============================================
// INCLUIR TCPDF
// ============================================
$tcpdfPath = __DIR__ . '/../tcpdf/tcpdf.php';
if (!file_exists($tcpdfPath)) {
    $tcpdfPath = __DIR__ . '/../tcpdf/tcpdf.php';
    if (!file_exists($tcpdfPath)) {
        die("Error: No se encontró TCPDF.");
    }
}

require_once($tcpdfPath);

// Crear nuevo documento PDF
$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

// Configurar información del documento
$pdf->SetCreator('Sistema de Gestión Política');
$pdf->SetAuthor('SISGONTech');
$pdf->SetTitle('Mis Pregoneros - ' . $usuario['nombres'] . ' ' . $usuario['apellidos']);
$pdf->SetSubject('Reporte de mis pregoneros');

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
$pdf->Cell(0, 10, 'MIS PREGONEROS', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, 'Referenciador: ' . $usuario['nombres'] . ' ' . $usuario['apellidos'], 0, 1, 'C');

// Línea separadora
$pdf->SetLineWidth(0.5);
$pdf->Line(10, $pdf->GetY(), 290, $pdf->GetY());
$pdf->Ln(5);

// ============================================
// INFORMACIÓN DEL REPORTE (CON FILTROS)
// ============================================
$pdf->SetFont('helvetica', '', 9);

// Construir texto de filtros aplicados
$filtrosTexto = "Fecha de exportación: " . date('d/m/Y H:i:s') . "\n";
$filtrosTexto .= "Referenciador: " . $usuario['nombres'] . ' ' . $usuario['apellidos'] . "\n";

if (!empty($search)) {
    $filtrosTexto .= "Búsqueda: " . htmlspecialchars($search) . "\n";
}
if ($filtro_estado !== 'todos') {
    $filtrosTexto .= "Estado: " . ($filtro_estado === 'activos' ? 'Solo activos' : 'Solo inactivos') . "\n";
}
if (!empty($filtro_zona)) {
    $filtrosTexto .= "Zona ID: " . $filtro_zona . "\n";
}
if (!empty($filtro_barrio)) {
    $filtrosTexto .= "Barrio ID: " . $filtro_barrio . "\n";
}
if (!empty($filtro_comuna)) {
    $filtrosTexto .= "Comuna: " . $filtro_comuna . "\n";
}
if (!empty($filtro_corregimiento)) {
    $filtrosTexto .= "Corregimiento: " . $filtro_corregimiento . "\n";
}
if (!empty($filtro_quien_reporta)) {
    $filtrosTexto .= "Quién reporta: " . $filtro_quien_reporta . "\n";
}
if ($filtro_voto_registrado !== '') {
    $filtrosTexto .= "Voto: " . ($filtro_voto_registrado == '1' ? 'Registrado' : 'No registrado') . "\n";
}
if (!empty($fecha_desde)) {
    $filtrosTexto .= "Fecha desde: " . $fecha_desde . "\n";
}
if (!empty($fecha_hasta)) {
    $filtrosTexto .= "Fecha hasta: " . $fecha_hasta . "\n";
}

$filtrosTexto .= "Total pregoneros: " . number_format($totalPregoneros, 0, ',', '.') . 
                 " (Activos: " . number_format($totalActivos, 0, ',', '.') . 
                 ", Inactivos: " . number_format($totalInactivos, 0, ',', '.') . ")\n";
$filtrosTexto .= "Ya votaron: " . number_format($totalVotaron, 0, ',', '.') . 
                 ", Pendientes: " . number_format($totalPendientes, 0, ',', '.');

$pdf->MultiCell(0, 5, $filtrosTexto, 0, 'L');
$pdf->Ln(5);

// ============================================
// TABLA DE PREGONEROS
// ============================================
$pdf->SetFont('helvetica', 'B', 8);

// Encabezados de la tabla
$header = array('ID', 'Estado', 'Voto', 'Nombres', 'Apellidos', 'Identificación', 'Teléfono', 'Barrio', 'Puesto', 'Mesa', 'Quién Reporta', 'Fecha Reg.');

// Anchos de columna
$widths = array(8, 12, 10, 20, 20, 20, 18, 18, 25, 12, 25, 18);

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

$rowNum = 0;
$rowsInCurrentPage = 0;

foreach ($pregoneros as $pregonero) {
    // Verificar si necesitamos nueva página
    if ($pdf->GetY() > 180) {
        $pdf->AddPage();
        $rowsInCurrentPage = 0;
        
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
    
    $activo = $pregonero['activo'] ?? true;
    $esta_activo = ($activo === true || $activo === 't' || $activo == 1);
    $voto_registrado = !empty($pregonero['voto_registrado']) && $pregonero['voto_registrado'] == true;
    
    // Color para estado
    if ($esta_activo) {
        $pdf->SetTextColor(39, 174, 96); // Verde
    } else {
        $pdf->SetTextColor(231, 76, 60); // Rojo
    }
    
    $pdf->Cell($widths[0], 6, $pregonero['id_pregonero'] ?? '', 'LR', 0, 'C', true);
    $pdf->Cell($widths[1], 6, $esta_activo ? 'ACTIVO' : 'INACTIVO', 'LR', 0, 'C', true);
    
    // Color para voto
    if ($voto_registrado) {
        $pdf->SetTextColor(39, 174, 96); // Verde
    } else {
        $pdf->SetTextColor(230, 126, 34); // Naranja
    }
    $pdf->Cell($widths[2], 6, $voto_registrado ? 'SÍ' : 'NO', 'LR', 0, 'C', true);
    
    // Restaurar color negro
    $pdf->SetTextColor(0);
    
    // Nombres
    $nombres = $pregonero['nombres'] ?? '';
    if (strlen($nombres) > 12) {
        $nombres = substr($nombres, 0, 12) . '...';
    }
    $pdf->Cell($widths[3], 6, $nombres, 'LR', 0, 'L', true);
    
    // Apellidos
    $apellidos = $pregonero['apellidos'] ?? '';
    if (strlen($apellidos) > 12) {
        $apellidos = substr($apellidos, 0, 12) . '...';
    }
    $pdf->Cell($widths[4], 6, $apellidos, 'LR', 0, 'L', true);
    
    $pdf->Cell($widths[5], 6, $pregonero['identificacion'] ?? '', 'LR', 0, 'C', true);
    $pdf->Cell($widths[6], 6, $pregonero['telefono'] ?? '', 'LR', 0, 'C', true);
    
    // Barrio
    $barrio = $pregonero['barrio_nombre'] ?? '';
    if (strlen($barrio) > 10) {
        $barrio = substr($barrio, 0, 10) . '...';
    }
    $pdf->Cell($widths[7], 6, $barrio, 'LR', 0, 'L', true);
    
    // Puesto
    $puesto = $pregonero['puesto_nombre'] ?? '';
    if (strlen($puesto) > 12) {
        $puesto = substr($puesto, 0, 12) . '...';
    }
    $pdf->Cell($widths[8], 6, $puesto, 'LR', 0, 'L', true);
    
    $pdf->Cell($widths[9], 6, $pregonero['mesa'] ?? '', 'LR', 0, 'C', true);
    
    // Quién reporta
    $quienReporta = $pregonero['quien_reporta'] ?? '';
    if (strlen($quienReporta) > 15) {
        $quienReporta = substr($quienReporta, 0, 15) . '...';
    }
    $pdf->Cell($widths[10], 6, $quienReporta, 'LR', 0, 'L', true);
    
    // Fecha
    $fecha = isset($pregonero['fecha_registro']) ? date('d/m/Y', strtotime($pregonero['fecha_registro'])) : '';
    $pdf->Cell($widths[11], 6, $fecha, 'LR', 0, 'C', true);
    
    $pdf->Ln();
    
    $rowNum++;
    $rowsInCurrentPage++;
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

// Crear tabla de resumen (con 2 columnas)
$summaryWidths = array(80, 30);
$pdf->SetFont('helvetica', '', 9);
$pdf->SetFillColor(240, 240, 240);
$pdf->SetDrawColor(200);

// Encabezado resumen
$pdf->Cell($summaryWidths[0], 8, 'ESTADÍSTICA', 1, 0, 'C', true);
$pdf->Cell($summaryWidths[1], 8, 'CANTIDAD', 1, 0, 'C', true);
$pdf->Ln();

// Datos del resumen
$pdf->SetFillColor(255, 255, 255);

$porcentajeVotaron = $totalPregoneros > 0 ? round(($totalVotaron / $totalPregoneros) * 100, 1) : 0;
$porcentajePendientes = $totalPregoneros > 0 ? round(($totalPendientes / $totalPregoneros) * 100, 1) : 0;

$summaryData = array(
    array('Total Pregoneros', number_format($totalPregoneros, 0, ',', '.')),
    array('Pregoneros Activos', number_format($totalActivos, 0, ',', '.')),
    array('Pregoneros Inactivos', number_format($totalInactivos, 0, ',', '.')),
    array('Ya Votaron', number_format($totalVotaron, 0, ',', '.') . ' (' . $porcentajeVotaron . '%)'),
    array('Pendientes de Voto', number_format($totalPendientes, 0, ',', '.') . ' (' . $porcentajePendientes . '%)'),
);

// Agregar información del filtro aplicado al resumen
if (!empty($search) || $filtro_estado !== 'todos' || !empty($filtro_zona) || $filtro_voto_registrado !== '') {
    $filtroTexto = "Filtros aplicados: ";
    $filtrosArray = [];
    if (!empty($search)) $filtrosArray[] = "Búsqueda";
    if ($filtro_estado !== 'todos') $filtrosArray[] = "Estado: $filtro_estado";
    if (!empty($filtro_zona)) $filtrosArray[] = "Zona";
    if ($filtro_voto_registrado !== '') $filtrosArray[] = "Voto: " . ($filtro_voto_registrado == '1' ? 'Sí' : 'No');
    $filtroTexto .= implode(', ', $filtrosArray);
    array_unshift($summaryData, array($filtroTexto, ''));
}

foreach ($summaryData as $row) {
    $pdf->Cell($summaryWidths[0], 7, $row[0], 'LR', 0, 'L', true);
    $pdf->Cell($summaryWidths[1], 7, $row[1], 'LR', 0, 'C', true);
    $pdf->Ln();
}

// Cerrar tabla resumen
$pdf->Cell(array_sum($summaryWidths), 0, '', 'T');
$pdf->Ln(5);

// ============================================
// PIE DE PÁGINA
// ============================================
$pdf->SetY(-20);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->SetTextColor(100);

$footerText = "Sistema de Gestión Política - SISGONTech | Referenciador: " . $usuario['nombres'] . ' ' . $usuario['apellidos'];
$pdf->Cell(0, 5, $footerText, 0, 0, 'C');
$pdf->Ln();
$pdf->Cell(0, 5, 'Página ' . $pdf->getAliasNumPage() . ' de ' . $pdf->getAliasNbPages(), 0, 0, 'C');
$pdf->Ln();
$pdf->Cell(0, 5, '© ' . date('Y') . ' Derechos reservados - Generado automáticamente', 0, 0, 'C');

// ============================================
// SALIDA DEL PDF (CON NOMBRE DINÁMICO)
// ============================================
$nombre_archivo = 'mis_pregoneros';
if (!empty($search)) {
    $nombre_archivo .= '_busqueda_' . preg_replace('/[^a-zA-Z0-9]/', '_', substr($search, 0, 20));
}
if ($filtro_estado !== 'todos') {
    $nombre_archivo .= '_' . $filtro_estado;
}
if (!empty($filtro_zona)) {
    $nombre_archivo .= '_zona_' . $filtro_zona;
}
if ($filtro_voto_registrado !== '') {
    $nombre_archivo .= '_voto_' . ($filtro_voto_registrado == '1' ? 'si' : 'no');
}
$nombre_archivo .= '_' . date('Y-m-d_H-i-s') . '.pdf';

$pdf->Output($nombre_archivo, 'D');
exit;