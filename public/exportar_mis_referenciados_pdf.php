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

// ============================================
// Obtener filtro de líder desde la URL
// ============================================
$filtro_lider = isset($_GET['filtro_lider']) ? $_GET['filtro_lider'] : 'todos';
$nombre_lider_filtro = isset($_GET['nombre_lider']) ? trim($_GET['nombre_lider']) : '';

// Obtener todos los referenciados del usuario
$referenciados = $referenciadoModel->getReferenciadosByUsuario($id_usuario_logueado);

// ============================================
// FUNCIÓN DE FILTRADO MEJORADA (igual que en Excel)
// ============================================
function filtrarPorLider($referenciados, $filtro_lider, $nombre_lider_filtro) {
    if ($filtro_lider === 'todos') {
        return $referenciados;
    }
    
    $resultado = [];
    
    foreach ($referenciados as $ref) {
        // Obtener nombre completo del líder de este referenciado
        $liderReferenciado = '';
        if (!empty($ref['lider_nombres']) || !empty($ref['lider_apellidos'])) {
            $liderReferenciado = trim(
                ($ref['lider_nombres'] ?? '') . ' ' . 
                ($ref['lider_apellidos'] ?? '')
            );
        }
        
        if ($filtro_lider === 'sin_lider') {
            // Mostrar solo los que NO tienen líder
            if (empty($liderReferenciado)) {
                $resultado[] = $ref;
            }
        } else {
            // Filtrar por líder específico
            $coincide = false;
            
            // Comparar con el nombre completo del líder
            if (!empty($liderReferenciado)) {
                // Intentar con el nombre del filtro
                if (!empty($nombre_lider_filtro)) {
                    // Comparación exacta (sin importar mayúsculas/minúsculas)
                    if (strcasecmp(trim($liderReferenciado), trim($nombre_lider_filtro)) === 0) {
                        $coincide = true;
                    }
                    // Comparación parcial (si el nombre contiene el filtro)
                    elseif (stripos($liderReferenciado, $nombre_lider_filtro) !== false) {
                        $coincide = true;
                    }
                    // Comparación inversa (si el filtro contiene el nombre)
                    elseif (stripos($nombre_lider_filtro, $liderReferenciado) !== false) {
                        $coincide = true;
                    }
                }
                
                // Si no hay nombre específico, usar el valor del select
                if (!$coincide && !empty($filtro_lider) && $filtro_lider !== 'sin_lider') {
                    if (stripos($liderReferenciado, $filtro_lider) !== false) {
                        $coincide = true;
                    }
                }
            }
            
            if ($coincide) {
                $resultado[] = $ref;
            }
        }
    }
    
    return $resultado;
}

// Aplicar filtro de líder
$referenciados = filtrarPorLider($referenciados, $filtro_lider, $nombre_lider_filtro);

// ============================================
// Filtrar por activos si se solicita
// ============================================
$soloActivos = isset($_GET['solo_activos']) && $_GET['solo_activos'] == 1;
if ($soloActivos) {
    $referenciados = array_filter($referenciados, function($referenciado) {
        $activo = $referenciado['activo'] ?? true;
        return ($activo === true || $activo === 't' || $activo == 1);
    });
}

// Re-indexar array después de los filtros
$referenciados = array_values($referenciados);

// Si no hay resultados, mostrar mensaje y salir
if (empty($referenciados)) {
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
    $pdf->Cell(0, 10, 'No se encontraron referenciados con el filtro aplicado:', 0, 1, 'C');
    $pdf->Ln(10);
    
    if ($filtro_lider === 'sin_lider') {
        $pdf->Cell(0, 10, '• Filtro: Sin líder asignado', 0, 1, 'C');
    } elseif ($filtro_lider !== 'todos') {
        $pdf->Cell(0, 10, '• Líder: ' . htmlspecialchars($nombre_lider_filtro ?: $filtro_lider), 0, 1, 'C');
    }
    if ($soloActivos) {
        $pdf->Cell(0, 10, '• Filtro adicional: Solo activos', 0, 1, 'C');
    }
    
    $pdf->Output('sin_resultados_' . date('Y-m-d_H-i-s') . '.pdf', 'D');
    exit;
}

// ORDENAR POR ID DE FORMA DESCENDENTE (últimos primero...)
usort($referenciados, function($a, $b) {
    return ($b['id_referenciado'] ?? 0) <=> ($a['id_referenciado'] ?? 0);
});

// Contar estadísticas (después de aplicar filtros)
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
    if (empty($referenciado['lider_nombres']) && empty($referenciado['lider_apellidos'])) {
        $referidosSinLider++;
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
// INFORMACIÓN DEL REPORTE (CON FILTROS)
// ============================================
$pdf->SetFont('helvetica', '', 9);

// Construir texto de filtros aplicados
$filtrosTexto = "Fecha de exportación: " . date('d/m/Y H:i:s') . "\n";
$filtrosTexto .= "Referenciador: " . $usuario['nombres'] . ' ' . $usuario['apellidos'] . "\n";

// Agregar información del filtro de líder
if ($filtro_lider !== 'todos') {
    if ($filtro_lider === 'sin_lider') {
        $filtrosTexto .= "Filtro por líder: SOLO SIN LÍDER ASIGNADO\n";
    } else {
        $filtrosTexto .= "Filtro por líder: " . htmlspecialchars($nombre_lider_filtro ?: $filtro_lider) . "\n";
    }
}

$filtrosTexto .= "Total referenciados: " . number_format($totalReferidos, 0, ',', '.') . 
                 " (Activos: " . number_format($totalActivos, 0, ',', '.') . 
                 ", Inactivos: " . number_format($totalInactivos, 0, ',', '.') . ")\n";
$filtrosTexto .= "Tope: " . ($usuario['total_referenciados'] ?? 0) . "/" . ($usuario['tope'] ?? 0) . 
                 " (" . ($usuario['porcentaje_tope'] ?? 0) . "% completado)";
                 
if ($soloActivos) {
    $filtrosTexto .= "\nFiltro adicional: Solo referidos activos";
}

$pdf->MultiCell(0, 5, $filtrosTexto, 0, 'L');
$pdf->Ln(5);

// ============================================
// TABLA DE REFERENCIADOS (CON COLUMNA DE LÍDER)
// ============================================
$pdf->SetFont('helvetica', 'B', 8);

// Encabezados de la tabla
$header = array('ID', 'Estado', 'Nombre', 'Apellido', 'Cédula', 'Teléfono', 'Afinidad', 'Vota', 'Puesto', 'Mesa', 'Líder', 'Fecha Reg.');

// Anchos de columna
$widths = array(10, 15, 25, 20, 20, 20, 15, 15, 25, 15, 30, 20);

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

foreach ($referenciados as $referenciado) {
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
    if (strlen($nombre) > 12) {
        $nombre = substr($nombre, 0, 12) . '...';
    }
    $pdf->Cell($widths[2], 6, $nombre, 'LR', 0, 'L', true);
    
    // Apellido
    $apellido = $referenciado['apellido'] ?? '';
    if (strlen($apellido) > 10) {
        $apellido = substr($apellido, 0, 10) . '...';
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
    if (strlen($puesto) > 12) {
        $puesto = substr($puesto, 0, 12) . '...';
    }
    $pdf->Cell($widths[8], 6, $puesto, 'LR', 0, 'L', true);
    
    // Mesa
    if ($vota_fuera) {
        $mesa = $referenciado['mesa_fuera'] ?? 'N/A';
    } else {
        $mesa = $referenciado['mesa'] ?? 'N/A';
    }
    $pdf->Cell($widths[9], 6, $mesa, 'LR', 0, 'C', true);
    
    // LÍDER - Obtener nombre completo
    $liderNombre = '';
    if (!empty($referenciado['lider_nombres']) || !empty($referenciado['lider_apellidos'])) {
        $liderNombre = trim(
            ($referenciado['lider_nombres'] ?? '') . ' ' . 
            ($referenciado['lider_apellidos'] ?? '')
        );
    }
    $lider = !empty($liderNombre) ? $liderNombre : 'SIN LÍDER';
    
    if (strlen($lider) > 15) {
        $lider = substr($lider, 0, 15) . '...';
    }
    $pdf->Cell($widths[10], 6, $lider, 'LR', 0, 'L', true);
    
    // Fecha
    $fecha = isset($referenciado['fecha_registro']) ? date('d/m/Y', strtotime($referenciado['fecha_registro'])) : '';
    $pdf->Cell($widths[11], 6, $fecha, 'LR', 0, 'C', true);
    
    $pdf->Ln();
    
    $rowNum++;
    $rowsInCurrentPage++;
}

// Cerrar la tabla
$pdf->Cell(array_sum($widths), 0, '', 'T');
$pdf->Ln(8);

// ============================================
// RESUMEN ESTADÍSTICO (INCLUYENDO LÍDERES)
// ============================================
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, 'RESUMEN ESTADÍSTICO', 0, 1, 'C');
$pdf->Ln(2);

// Contar estadísticas de líderes para los referenciados filtrados
$lideresCount = [];

foreach ($referenciados as $referenciado) {
    $liderNombre = '';
    if (!empty($referenciado['lider_nombres']) || !empty($referenciado['lider_apellidos'])) {
        $liderNombre = trim(
            ($referenciado['lider_nombres'] ?? '') . ' ' . 
            ($referenciado['lider_apellidos'] ?? '')
        );
    }
    if (!empty($liderNombre)) {
        if (!isset($lideresCount[$liderNombre])) {
            $lideresCount[$liderNombre] = 0;
        }
        $lideresCount[$liderNombre]++;
    }
}

$totalConLider = $totalReferidos - $referidosSinLider;
$porcentajeConLider = $totalReferidos > 0 ? round(($totalConLider / $totalReferidos) * 100, 1) : 0;
$porcentajeSinLider = $totalReferidos > 0 ? round(($referidosSinLider / $totalReferidos) * 100, 1) : 0;

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

$summaryData = array(
    array('Total Referenciados', number_format($totalReferidos, 0, ',', '.')),
    array('Referenciados Activos', number_format($totalActivos, 0, ',', '.')),
    array('Referenciados Inactivos', number_format($totalInactivos, 0, ',', '.')),
    array('Con Líder Asignado', number_format($totalConLider, 0, ',', '.') . ' (' . $porcentajeConLider . '%)'),
    array('Sin Líder Asignado', number_format($referidosSinLider, 0, ',', '.') . ' (' . $porcentajeSinLider . '%)'),
    array('Tope Completado', ($usuario['total_referenciados'] ?? 0) . '/' . ($usuario['tope'] ?? 0)),
    array('Porcentaje Completado', ($usuario['porcentaje_tope'] ?? 0) . '%')
);

// Agregar información del filtro aplicado al resumen
if ($filtro_lider !== 'todos') {
    $filtroTexto = "Filtro aplicado: ";
    if ($filtro_lider === 'sin_lider') {
        $filtroTexto .= "Solo sin líder";
    } else {
        $filtroTexto .= "Líder: " . ($nombre_lider_filtro ?: $filtro_lider);
    }
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

// Mostrar los líderes con más referidos (solo top 3 para referenciador)
if (!empty($lideresCount)) {
    $pdf->Ln(3);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(0, 6, 'MIS LÍDERES CON MÁS REFERIDOS:', 0, 1, 'L');
    
    arsort($lideresCount); // Ordenar de mayor a menor
    $topLideres = array_slice($lideresCount, 0, 3, true);
    
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetFillColor(245, 245, 245);
    
    $counter = 1;
    foreach ($topLideres as $liderNombre => $count) {
        // Acortar nombre si es muy largo
        $liderDisplay = strlen($liderNombre) > 30 ? substr($liderNombre, 0, 30) . '...' : $liderNombre;
        
        $pdf->Cell(10, 6, $counter . '.', 1, 0, 'C', true);
        $pdf->Cell(160, 6, $liderDisplay, 1, 0, 'L', true);
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

$footerText = "Sistema de Gestión Política - SISGONTech | Referenciador: " . $usuario['nombres'] . ' ' . $usuario['apellidos'];
$pdf->Cell(0, 5, $footerText, 0, 0, 'C');
$pdf->Ln();
$pdf->Cell(0, 5, 'Página ' . $pdf->getAliasNumPage() . ' de ' . $pdf->getAliasNbPages(), 0, 0, 'C');
$pdf->Ln();
$pdf->Cell(0, 5, '© ' . date('Y') . ' Derechos reservados - Generado automáticamente', 0, 0, 'C');

// ============================================
// SALIDA DEL PDF (CON NOMBRE DINÁMICO)
// ============================================
$nombre_archivo = 'mis_referenciados';
if ($filtro_lider !== 'todos') {
    if ($filtro_lider === 'sin_lider') {
        $nombre_archivo .= '_sin_lider';
    } else {
        $nombre_archivo .= '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $nombre_lider_filtro);
    }
}
if ($soloActivos) {
    $nombre_archivo .= '_activos';
}
$nombre_archivo .= '_' . date('Y-m-d_H-i-s') . '.pdf';

$pdf->Output($nombre_archivo, 'D');
exit;