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

// ============================================
usort($referenciados, function($a, $b) {
    return ($b['id_referenciado'] ?? 0) <=> ($a['id_referenciado'] ?? 0);
});

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

// Contar estadísticas - TODOS DEBERÍAN SER INACTIVOS
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
$pdf->SetTitle('Reporte de Referidos Inactivos'); // CAMBIO: título
$pdf->SetSubject('Reporte de referidos inactivos del sistema'); // CAMBIO: subject

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
$pdf->Cell(0, 10, 'REPORTE DE REFERIDOS INACTIVOS', 0, 1, 'C'); // CAMBIO: título
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, 'Sistema de Gestión Política - SISGONTech', 0, 1, 'C');

// Línea separadora con color rojo para inactivos
$pdf->SetLineWidth(0.5);
$pdf->SetDrawColor(231, 76, 60); // Color rojo (#e74c3c)
$pdf->Line(10, $pdf->GetY(), 290, $pdf->GetY());
$pdf->SetDrawColor(0, 0, 0); // Restaurar color negro
$pdf->Ln(5);

// ============================================
// INFORMACIÓN DEL REPORTE
// ============================================
$pdf->SetFont('helvetica', '', 9);
$infoText = "Fecha de exportación: " . date('d/m/Y H:i:s') . "\n";
$infoText .= "Exportado por: " . ($_SESSION['nombres'] ?? 'Usuario') . ' ' . ($_SESSION['apellidos'] ?? '') . "\n";
$infoText .= "Tipo de reporte: REFERIDOS INACTIVOS\n"; // NUEVO: tipo de reporte
$infoText .= "Total referidos inactivos: " . number_format($totalReferidos, 0, ',', '.') . "\n"; // CAMBIO: texto

// Agregar nota sobre verificación si hay activos
if ($totalActivos > 0) {
    $infoText .= "Nota: Se encontraron " . $totalActivos . " referidos activos en la verificación\n";
}

$pdf->MultiCell(0, 5, $infoText, 0, 'L');
$pdf->Ln(5);

// ============================================
// TABLA DE REFERIDOS INACTIVOS
// ============================================
$pdf->SetFont('helvetica', 'B', 8);

// Encabezados de la tabla (columnas reducidas para formato horizontal)
$header = array('ID', 'Estado', 'Nombre', 'Apellido', 'Cédula', 'Teléfono', 'Afinidad', 'Zona', 'Sector', 'Referenciador', 'Fecha Reg.', 'Última Act.'); // CAMBIO: nueva columna

// Anchos de columna (ajustados para formato horizontal A4 landscape)
$widths = array(10, 15, 25, 25, 22, 22, 12, 22, 22, 38, 18, 18); // Ajustado para nueva columna

// Dibujar encabezados - COLOR ROJO PARA INACTIVOS
$pdf->SetFillColor(231, 76, 60); // Color rojo (#e74c3c) en lugar de azul
$pdf->SetTextColor(255);
$pdf->SetDrawColor(231, 76, 60);
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

foreach ($referenciados as $referenciado) {
    // Verificar si necesitamos nueva página
    if ($pdf->GetY() > 180) {
        $pdf->AddPage();
        
        // Reiniciar contador de filas para nueva página
        $rowsInCurrentPage = 0;
        
        // Redibujar encabezados
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetFillColor(231, 76, 60); // Color rojo
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
    
    // Color para estado - TODOS DEBERÍAN SER ROJOS
    if ($esta_activo) {
        $pdf->SetTextColor(39, 174, 96); // Verde (solo para verificación)
    } else {
        $pdf->SetTextColor(231, 76, 60); // Rojo (para inactivos)
    }
    
    $pdf->Cell($widths[0], 6, $referenciado['id_referenciado'] ?? '', 'LR', 0, 'C', true);
    $pdf->Cell($widths[1], 6, $estado, 'LR', 0, 'C', true);
    
    // Restaurar color negro
    $pdf->SetTextColor(0);
    
    // Nombre (acortado si es muy largo)
    $nombre = $referenciado['nombre'] ?? '';
    if (strlen($nombre) > 12) {
        $nombre = substr($nombre, 0, 12) . '...';
    }
    $pdf->Cell($widths[2], 6, $nombre, 'LR', 0, 'L', true);
    
    // Apellido (acortado si es muy largo)
    $apellido = $referenciado['apellido'] ?? '';
    if (strlen($apellido) > 12) {
        $apellido = substr($apellido, 0, 12) . '...';
    }
    $pdf->Cell($widths[3], 6, $apellido, 'LR', 0, 'L', true);
    
    $pdf->Cell($widths[4], 6, $referenciado['cedula'] ?? '', 'LR', 0, 'C', true);
    $pdf->Cell($widths[5], 6, $referenciado['telefono'] ?? '', 'LR', 0, 'C', true);
    $pdf->Cell($widths[6], 6, $referenciado['afinidad'] ?? '0', 'LR', 0, 'C', true);
    
    // Zona (acortada)
    $zona = isset($referenciado['id_zona']) && isset($zonasMap[$referenciado['id_zona']]) ? $zonasMap[$referenciado['id_zona']] : 'N/A';
    if (strlen($zona) > 10) {
        $zona = substr($zona, 0, 10) . '...';
    }
    $pdf->Cell($widths[7], 6, $zona, 'LR', 0, 'L', true);
    
    // Sector (acortado)
    $sector = isset($referenciado['id_sector']) && isset($sectoresMap[$referenciado['id_sector']]) ? $sectoresMap[$referenciado['id_sector']] : 'N/A';
    if (strlen($sector) > 10) {
        $sector = substr($sector, 0, 10) . '...';
    }
    $pdf->Cell($widths[8], 6, $sector, 'LR', 0, 'L', true);
    
    // Referenciador (acortado)
    $referenciador = $referenciado['referenciador_nombre'] ?? 'N/A';
    if (strlen($referenciador) > 18) {
        $referenciador = substr($referenciador, 0, 18) . '...';
    }
    $pdf->Cell($widths[9], 6, $referenciador, 'LR', 0, 'L', true);
    
    // Fecha de registro (solo fecha)
    $fechaReg = isset($referenciado['fecha_registro']) ? date('d/m/Y', strtotime($referenciado['fecha_registro'])) : '';
    $pdf->Cell($widths[10], 6, $fechaReg, 'LR', 0, 'C', true);
    
    // Última actualización (nueva columna)
    $fechaAct = isset($referenciado['fecha_actualizacion']) ? date('d/m/Y', strtotime($referenciado['fecha_actualizacion'])) : '';
    $pdf->Cell($widths[11], 6, $fechaAct, 'LR', 0, 'C', true);
    
    $pdf->Ln();
    
    // Alternar color de fondo para mejor lectura
    if ($rowNum % 2 == 0) {
        $pdf->SetFillColor(255, 255, 255); // Blanco
    } else {
        $pdf->SetFillColor(248, 215, 218); // Rojo claro (#f8d7da) para inactivos
    }
    
    $rowNum++;
    $rowsInCurrentPage++;
}

// Cerrar la tabla
$pdf->Cell(array_sum($widths), 0, '', 'T');
$pdf->Ln(8);

// ============================================
// RESUMEN ESTADÍSTICO PARA INACTIVOS
// ============================================
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetTextColor(231, 76, 60); // Color rojo para el título
$pdf->Cell(0, 6, 'RESUMEN DE REFERIDOS INACTIVOS', 0, 1, 'C');
$pdf->SetTextColor(0, 0, 0); // Restaurar color negro
$pdf->Ln(2);

// Calcular porcentajes
$porcentajeActivos = $totalReferidos > 0 ? ($totalActivos / $totalReferidos) * 100 : 0;
$porcentajeInactivos = $totalReferidos > 0 ? ($totalInactivos / $totalReferidos) * 100 : 0;

// Crear tabla de resumen
$summaryWidths = array(70, 30, 30);
$pdf->SetFont('helvetica', '', 9);
$pdf->SetFillColor(248, 215, 218); // Fondo rojo claro
$pdf->SetDrawColor(231, 76, 60); // Borde rojo

// Encabezado resumen
$pdf->Cell($summaryWidths[0], 8, 'ESTADÍSTICA', 1, 0, 'C', true);
$pdf->Cell($summaryWidths[1], 8, 'CANTIDAD', 1, 0, 'C', true);
$pdf->Cell($summaryWidths[2], 8, 'PORCENTAJE', 1, 0, 'C', true);
$pdf->Ln();

// Datos del resumen
$pdf->SetFillColor(255, 255, 255);

$summaryData = array(
    array('Total Referidos Inactivos', number_format($totalReferidos, 0, ',', '.'), '100%'), // CAMBIO: texto
    array('Activos (verificación)', number_format($totalActivos, 0, ',', '.'), round($porcentajeActivos, 2) . '%'), // CAMBIO: texto
    array('Inactivos', number_format($totalInactivos, 0, ',', '.'), round($porcentajeInactivos, 2) . '%')
);

foreach ($summaryData as $row) {
    // Resaltar fila de inactivos
    if ($row[0] == 'Inactivos') {
        $pdf->SetFillColor(248, 215, 218); // Fondo rojo claro para inactivos
        $pdf->SetFont('helvetica', 'B', 9); // Negrita
    } else {
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('helvetica', '', 9);
    }
    
    $pdf->Cell($summaryWidths[0], 7, $row[0], 'LR', 0, 'L', true);
    $pdf->Cell($summaryWidths[1], 7, $row[1], 'LR', 0, 'C', true);
    $pdf->Cell($summaryWidths[2], 7, $row[2], 'LR', 0, 'C', true);
    $pdf->Ln();
}

// Cerrar tabla resumen
$pdf->SetFillColor(255, 255, 255);
$pdf->Cell(array_sum($summaryWidths), 0, '', 'T');
$pdf->Ln(10);

// ============================================
// NOTA IMPORTANTE
// ============================================
$pdf->SetFont('helvetica', 'I', 8);
$pdf->SetTextColor(231, 76, 60); // Rojo
$pdf->Cell(0, 5, 'NOTA: Este reporte contiene únicamente referidos marcados como INACTIVOS en el sistema.', 0, 1, 'C');
$pdf->Ln(5);

// ============================================
// PIE DE PÁGINA
// ============================================
$pdf->SetY(-20);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->SetTextColor(100);

// Información del sistema
$footerText = "Reporte de Referidos Inactivos - Sistema de Gestión Política | "; // CAMBIO: texto
$footerText .= "SISGONTech | Email: sisgonnet@gmail.com | Contacto: +57 3106310227 | ";
$footerText .= "Página " . $pdf->getAliasNumPage() . " de " . $pdf->getAliasNbPages();

$pdf->Cell(0, 5, $footerText, 0, 0, 'C');
$pdf->Ln();
$pdf->Cell(0, 5, '© ' . date('Y') . ' Derechos reservados - Ing. Rubén Darío González García', 0, 0, 'C');

// ============================================
// SALIDA DEL PDF
// ============================================
$pdf->Output('referidos_inactivos_' . date('Y-m-d_H-i-s') . '.pdf', 'D'); // CAMBIO: nombre del archivo
exit;