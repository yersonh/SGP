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

// ORDENAR POR ID DE FORMA DESCENDENTE (últimos primero...)
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
// INCLUIR TCPDF
// ============================================
$tcpdfPath = __DIR__ . '/../../tcpdf/tcpdf.php';
if (!file_exists($tcpdfPath)) {
    $tcpdfPath = __DIR__ . '/../../tcpdf/tcpdf.php';
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
$pdf->SetTitle('Mis Referenciados - ' . $usuario['nombres'] . ' ' . $usuario['apellidos']);
$pdf->SetSubject('Reporte de mis referenciados');

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
$pdf->Cell(0, 10, 'MIS REFERENCIADOS', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, 'Referenciador: ' . $usuario['nombres'] . ' ' . $usuario['apellidos'], 0, 1, 'C');

// Línea separadora
$pdf->SetLineWidth(0.5);
$pdf->Line(10, $pdf->GetY(), 290, $pdf->GetY());
$pdf->Ln(5);

// ============================================
// INFORMACIÓN DEL REPORTE
// ============================================
$pdf->SetFont('helvetica', '', 9);
$infoText = "Fecha de exportación: " . date('d/m/Y H:i:s') . "\n";
$infoText .= "Referenciador: " . $usuario['nombres'] . ' ' . $usuario['apellidos'] . "\n";
$infoText .= "Total referenciados: " . number_format($totalReferidos, 0, ',', '.') . 
             " (Activos: " . number_format($totalActivos, 0, ',', '.') . 
             ", Inactivos: " . number_format($totalInactivos, 0, ',', '.') . ")\n";
$infoText .= "Tope: " . ($usuario['total_referenciados'] ?? 0) . "/" . ($usuario['tope'] ?? 0) . 
             " (" . ($usuario['porcentaje_tope'] ?? 0) . "% completado)";
             
if ($soloActivos) {
    $infoText .= "\nFiltro aplicado: Solo referidos activos";
}

$pdf->MultiCell(0, 5, $infoText, 0, 'L');
$pdf->Ln(5);

// ============================================
// TABLA DE REFERENCIADOS
// ============================================
$pdf->SetFont('helvetica', 'B', 8);

// Encabezados de la tabla
$header = array('ID', 'Estado', 'Nombre', 'Apellido', 'Cédula', 'Teléfono', 'Afinidad', 'Vota', 'Puesto', 'Mesa', 'Fecha Reg.');

// Anchos de columna
$widths = array(10, 15, 30, 25, 25, 25, 15, 15, 30, 15, 25);

// Dibujar encabezados
$pdf->SetFillColor(64, 115, 223);
$pdf->SetTextColor(255);
$pdf->SetDrawColor(64, 115, 223);
$pdf->SetLineWidth(0.3);

for ($i = 0; $i < count($header); $i++) {
    $pdf->Cell($widths[$i], 7, $header[$i], 1, 0, 'C', 1);
}
$pdf->Ln();

// Contenido de la tabla - TODAS LAS FILAS EN BLANCO
$pdf->SetFont('helvetica', '', 7);
$pdf->SetTextColor(0);
$pdf->SetFillColor(255); // FONDO BLANCO FIJO
$pdf->SetDrawColor(200);

$rowNum = 0;
$rowsInCurrentPage = 0;

foreach ($referenciados as $referenciado) {
    // Verificar si necesitamos nueva página
    if ($pdf->GetY() > 180) {
        $pdf->AddPage();
        
        // REINICIAR CONTADOR DE FILAS PARA NUEVA PÁGINA
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
    
    $activo = $referenciado['activo'] ?? true;
    $esta_activo = ($activo === true || $activo === 't' || $activo == 1);
    $estado = $esta_activo ? 'ACTIVO' : 'INACTIVO';
    $vota_fuera = $referenciado['vota_fuera'] === 'Si';
    
    // Color para estado
    if ($esta_activo) {
        $pdf->SetTextColor(39, 174, 96); // Verde
    } else {
        $pdf->SetTextColor(231, 76, 60); // Rojo
    }
    
    $pdf->Cell($widths[0], 6, $referenciado['id_referenciado'] ?? '', 'LR', 0, 'C', true);
    $pdf->Cell($widths[1], 6, $estado, 'LR', 0, 'C', true);
    
    // Restaurar color negro
    $pdf->SetTextColor(0);
    
    // Nombre
    $nombre = $referenciado['nombre'] ?? '';
    if (strlen($nombre) > 15) {
        $nombre = substr($nombre, 0, 15) . '...';
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
    $pdf->Cell($widths[7], 6, $vota_fuera ? 'FUERA' : 'AQUÍ', 'LR', 0, 'C', true);
    
    // Puesto
    if ($vota_fuera) {
        $puesto = $referenciado['puesto_votacion_fuera'] ?? 'N/A';
    } else {
        $puesto = $referenciado['puesto_votacion_nombre'] ?? 'N/A';
    }
    if (strlen($puesto) > 15) {
        $puesto = substr($puesto, 0, 15) . '...';
    }
    $pdf->Cell($widths[8], 6, $puesto, 'LR', 0, 'L', true);
    
    // Mesa
    if ($vota_fuera) {
        $mesa = $referenciado['mesa_fuera'] ?? 'N/A';
    } else {
        $mesa = $referenciado['mesa'] ?? 'N/A';
    }
    $pdf->Cell($widths[9], 6, $mesa, 'LR', 0, 'C', true);
    
    // Fecha
    $fecha = isset($referenciado['fecha_registro']) ? date('d/m/Y', strtotime($referenciado['fecha_registro'])) : '';
    $pdf->Cell($widths[10], 6, $fecha, 'LR', 0, 'C', true);
    
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

// Crear tabla de resumen
$summaryWidths = array(70, 30);
$pdf->SetFont('helvetica', '', 9);
$pdf->SetFillColor(240, 240, 240);
$pdf->SetDrawColor(200);

// Encabezado resumen
$pdf->Cell($summaryWidths[0], 8, 'ESTADÍSTICA', 1, 0, 'C', true);
$pdf->Cell($summaryWidths[1], 8, 'CANTIDAD', 1, 0, 'C', true);
$pdf->Ln();

// Datos del resumen
$pdf->SetFillColor(255, 255, 255);

$summaryData = array(
    array('Total Referenciados', number_format($totalReferidos, 0, ',', '.')),
    array('Referenciados Activos', number_format($totalActivos, 0, ',', '.')),
    array('Referenciados Inactivos', number_format($totalInactivos, 0, ',', '.')),
    array('Tope Completado', ($usuario['total_referenciados'] ?? 0) . '/' . ($usuario['tope'] ?? 0)),
    array('Porcentaje Completado', ($usuario['porcentaje_tope'] ?? 0) . '%')
);

foreach ($summaryData as $row) {
    $pdf->Cell($summaryWidths[0], 7, $row[0], 'LR', 0, 'L', true);
    $pdf->Cell($summaryWidths[1], 7, $row[1], 'LR', 0, 'C', true);
    $pdf->Ln();
}

// Cerrar tabla resumen
$pdf->Cell(array_sum($summaryWidths), 0, '', 'T');
$pdf->Ln(10);

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
// SALIDA DEL PDF
// ============================================
$pdf->Output('mis_referenciados_' . date('Y-m-d_H-i-s') . '.pdf', 'D');
exit;