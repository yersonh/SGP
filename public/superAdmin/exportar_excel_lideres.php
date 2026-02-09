<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../models/LiderModel.php';
require_once __DIR__ . '/../../models/ReferenciadoModel.php';

// Verificar si el usuario está logueado y es SuperAdmin
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

// Obtener filtros del POST
$filtros = $_POST['filtros'] ?? [];
$filtro_nombre = $filtros['nombre'] ?? '';
$filtro_cc = $filtros['cc'] ?? '';
$filtro_referenciador = $filtros['referenciador'] ?? '';
$filtro_estado = $filtros['estado'] ?? '';
$filtro_min_referidos = $filtros['min_referidos'] ?? '';

$pdo = Database::getConnection();
$liderModel = new LiderModel($pdo);
$usuarioModel = new UsuarioModel($pdo);
$referenciadoModel = new ReferenciadoModel($pdo);

// Configurar filtros para el modelo
$filters = [];
if (!empty($filtro_nombre)) {
    $filters['search'] = $filtro_nombre;
}
if (!empty($filtro_estado)) {
    $filters['estado'] = $filtro_estado === 'activo' ? true : false;
}
if (!empty($filtro_referenciador)) {
    $filters['id_usuario'] = $filtro_referenciador;
}

// Obtener TODOS los líderes con estadísticas (sin paginación para exportar todo)
try {
    // Primero obtener total para saber si podemos exportar todo
    $totalLideres = $liderModel->countAll($filters);
    
    // Límite para exportación (evitar timeout/memoria)
    $limiteExportacion = 10000; // Máximo 10,000 registros
    
    if ($totalLideres > $limiteExportacion) {
        echo json_encode([
            'success' => false,
            'message' => 'Demasiados registros para exportar (' . $totalLideres . '). Máximo permitido: ' . $limiteExportacion
        ]);
        exit();
    }
    
    // Obtener todos los líderes con estadísticas
    // Necesitamos modificar el método getPaginatedWithStats para que pueda obtener todos sin paginación
    $lideres = $liderModel->getAllLideresConEstadisticas($filters);
    
    // Aplicar filtros en memoria si es necesario
    if (!empty($filtro_min_referidos) && is_numeric($filtro_min_referidos)) {
        $minReferidos = intval($filtro_min_referidos);
        $lideres = array_filter($lideres, 
            function($lider) use ($minReferidos) {
                return ($lider['cantidad_referidos'] ?? 0) >= $minReferidos;
            }
        );
    }
    
    if (!empty($filtro_cc)) {
        $lideres = array_filter($lideres, 
            function($lider) use ($filtro_cc) {
                return stripos($lider['cc'] ?? '', $filtro_cc) !== false;
            }
        );
    }
    
    // Crear archivo Excel
    $archivo = exportarAExcel($lideres, $filtros);
    
    if ($archivo) {
        echo json_encode([
            'success' => true,
            'file_url' => $archivo,
            'message' => 'Archivo exportado correctamente',
            'registros' => count($lideres)
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al crear el archivo Excel'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error en exportar_excel_lideres.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al exportar: ' . $e->getMessage()
    ]);
}

// ==============================================
// FUNCIONES DE EXPORTACIÓN
// ==============================================

/**
 * Exportar a Excel usando PhpSpreadsheet (si está instalado)
 * Si no, generar CSV
 */
function exportarAExcel($lideres, $filtros) {
    // Intentar usar PhpSpreadsheet si está disponible
    if (class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        return exportarConPhpSpreadsheet($lideres, $filtros);
    } 
    // Si no, generar CSV simple
    else {
        return exportarComoCSV($lideres, $filtros);
    }
}

/**
 * Exportar usando PhpSpreadsheet (Excel real)
 */
function exportarConPhpSpreadsheet($lideres, $filtros) {
    try {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Título
        $sheet->setCellValue('A1', 'Reporte de Líderes');
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');
        
        // Filtros aplicados
        $fila = 3;
        if (!empty($filtros)) {
            $sheet->setCellValue('A' . $fila, 'Filtros aplicados:');
            $sheet->getStyle('A' . $fila)->getFont()->setBold(true);
            $fila++;
            
            $filtrosTexto = [];
            if (!empty($filtros['nombre'])) $filtrosTexto[] = "Nombre: " . $filtros['nombre'];
            if (!empty($filtros['cc'])) $filtrosTexto[] = "Cédula: " . $filtros['cc'];
            if (!empty($filtros['estado'])) $filtrosTexto[] = "Estado: " . $filtros['estado'];
            if (!empty($filtros['min_referidos'])) $filtrosTexto[] = "Mín. referidos: " . $filtros['min_referidos'];
            
            $sheet->setCellValue('A' . $fila, implode(' | ', $filtrosTexto));
            $fila += 2;
        }
        
        // Encabezados de tabla
        $encabezados = [
            'ID', 'Nombres', 'Apellidos', 'Cédula', 'Teléfono', 'Correo',
            'Referidos', 'Referenciador', 'Estado', 'Fecha Registro', 'Porcentaje Contribución'
        ];
        
        $columna = 'A';
        foreach ($encabezados as $encabezado) {
            $sheet->setCellValue($columna . $fila, $encabezado);
            $sheet->getStyle($columna . $fila)->getFont()->setBold(true);
            $sheet->getStyle($columna . $fila)->getFill()
                  ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                  ->getStartColor()->setARGB('FFE0E0E0');
            $columna++;
        }
        $fila++;
        
        // Datos
        $totalReferidos = 0;
        foreach ($lideres as $lider) {
            $cantidadReferidos = $lider['cantidad_referidos'] ?? 0;
            $totalReferidos += $cantidadReferidos;
            
            $sheet->setCellValue('A' . $fila, $lider['id_lider'] ?? '');
            $sheet->setCellValue('B' . $fila, $lider['nombres'] ?? '');
            $sheet->setCellValue('C' . $fila, $lider['apellidos'] ?? '');
            $sheet->setCellValue('D' . $fila, $lider['cc'] ?? '');
            $sheet->setCellValue('E' . $fila, $lider['telefono'] ?? '');
            $sheet->setCellValue('F' . $fila, $lider['correo'] ?? '');
            $sheet->setCellValue('G' . $fila, $cantidadReferidos);
            $sheet->setCellValue('H' . $fila, $lider['referenciador_nombre'] ?? 'No asignado');
            $sheet->setCellValue('I' . $fila, ($lider['estado'] ? 'Activo' : 'Inactivo'));
            $sheet->setCellValue('J' . $fila, !empty($lider['fecha_creacion']) ? 
                date('d/m/Y', strtotime($lider['fecha_creacion'])) : '');
            $sheet->setCellValue('K' . $fila, ($lider['porcentaje_contribucion'] ?? 0) . '%');
            
            $fila++;
        }
        
        // Totales
        $fila++;
        $sheet->setCellValue('F' . $fila, 'TOTAL LÍDERES:');
        $sheet->setCellValue('G' . $fila, count($lideres));
        $sheet->getStyle('F' . $fila . ':G' . $fila)->getFont()->setBold(true);
        
        $fila++;
        $sheet->setCellValue('F' . $fila, 'TOTAL REFERIDOS:');
        $sheet->setCellValue('G' . $fila, $totalReferidos);
        $sheet->getStyle('F' . $fila . ':G' . $fila)->getFont()->setBold(true);
        
        // Autoajustar columnas
        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Crear directorio de exportaciones si no existe
        $directorioExport = __DIR__ . '/../exportaciones/';
        if (!file_exists($directorioExport)) {
            mkdir($directorioExport, 0777, true);
        }
        
        // Generar nombre de archivo
        $nombreArchivo = 'lideres_' . date('Ymd_His') . '.xlsx';
        $rutaCompleta = $directorioExport . $nombreArchivo;
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($rutaCompleta);
        
        return '/exportaciones/' . $nombreArchivo;
        
    } catch (Exception $e) {
        error_log("Error en exportarConPhpSpreadsheet: " . $e->getMessage());
        // Fallback a CSV si hay error con PhpSpreadsheet
        return exportarComoCSV($lideres, $filtros);
    }
}

/**
 * Exportar como CSV (fallback si no hay PhpSpreadsheet)
 */
function exportarComoCSV($lideres, $filtros) {
    try {
        // Crear directorio de exportaciones si no existe
        $directorioExport = __DIR__ . '/../exportaciones/';
        if (!file_exists($directorioExport)) {
            mkdir($directorioExport, 0777, true);
        }
        
        // Generar nombre de archivo
        $nombreArchivo = 'lideres_' . date('Ymd_His') . '.csv';
        $rutaCompleta = $directorioExport . $nombreArchivo;
        
        // Abrir archivo para escritura
        $archivo = fopen($rutaCompleta, 'w');
        
        // BOM para UTF-8 (Excel)
        fwrite($archivo, "\xEF\xBB\xBF");
        
        // Encabezados
        $encabezados = [
            'ID', 'Nombres', 'Apellidos', 'Cédula', 'Teléfono', 'Correo',
            'Referidos', 'Referenciador', 'Estado', 'Fecha Registro', 'Porcentaje Contribución'
        ];
        fputcsv($archivo, $encabezados, ';');
        
        // Datos
        $totalReferidos = 0;
        foreach ($lideres as $lider) {
            $cantidadReferidos = $lider['cantidad_referidos'] ?? 0;
            $totalReferidos += $cantidadReferidos;
            
            $fila = [
                $lider['id_lider'] ?? '',
                $lider['nombres'] ?? '',
                $lider['apellidos'] ?? '',
                $lider['cc'] ?? '',
                $lider['telefono'] ?? '',
                $lider['correo'] ?? '',
                $cantidadReferidos,
                $lider['referenciador_nombre'] ?? 'No asignado',
                $lider['estado'] ? 'Activo' : 'Inactivo',
                !empty($lider['fecha_creacion']) ? date('d/m/Y', strtotime($lider['fecha_creacion'])) : '',
                ($lider['porcentaje_contribucion'] ?? 0) . '%'
            ];
            fputcsv($archivo, $fila, ';');
        }
        
        // Totales
        fputcsv($archivo, [], ';');
        fputcsv($archivo, ['TOTAL LÍDERES:', '', '', '', '', '', count($lideres)], ';');
        fputcsv($archivo, ['TOTAL REFERIDOS:', '', '', '', '', '', $totalReferidos], ';');
        
        // Filtros aplicados (en comentario)
        if (!empty($filtros)) {
            fputcsv($archivo, [], ';');
            fputcsv($archivo, ['FILTROS APLICADOS:'], ';');
            
            $filtrosTexto = [];
            if (!empty($filtros['nombre'])) $filtrosTexto[] = "Nombre: " . $filtros['nombre'];
            if (!empty($filtros['cc'])) $filtrosTexto[] = "Cédula: " . $filtros['cc'];
            if (!empty($filtros['estado'])) $filtrosTexto[] = "Estado: " . $filtros['estado'];
            if (!empty($filtros['min_referidos'])) $filtrosTexto[] = "Mín. referidos: " . $filtros['min_referidos'];
            
            fputcsv($archivo, [implode(' | ', $filtrosTexto)], ';');
        }
        
        fclose($archivo);
        
        return '/exportaciones/' . $nombreArchivo;
        
    } catch (Exception $e) {
        error_log("Error en exportarComoCSV: " . $e->getMessage());
        return false;
    }
}