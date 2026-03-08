<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

$pdo = Database::getConnection();

try {
    // ============================================
    // AVANCE GENERAL DE VOTOS REFERIDOS POR DÍA (últimos 7 días)
    // ============================================
    $sqlAvanceVotosReferidos = "
        WITH fechas AS (
            SELECT generate_series(
                CURRENT_DATE - INTERVAL '6 days',
                CURRENT_DATE,
                '1 day'::interval
            )::date as fecha
        )
        SELECT 
            f.fecha,
            COUNT(r.id_referenciado) as votos_dia,
            SUM(COUNT(r.id_referenciado)) OVER (ORDER BY f.fecha) as votos_acumulados
        FROM fechas f
        LEFT JOIN referenciados r ON DATE(r.fecha_voto) = f.fecha 
            AND r.voto_registrado = TRUE 
            AND r.activo = true
        GROUP BY f.fecha
        ORDER BY f.fecha
    ";
    
    $stmt = $pdo->prepare($sqlAvanceVotosReferidos);
    $stmt->execute();
    $avanceVotosReferidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear fechas
    foreach ($avanceVotosReferidos as &$item) {
        $item['fecha'] = date('d/m', strtotime($item['fecha']));
        $item['votos_acumulados'] = (int)($item['votos_acumulados'] ?? 0);
        $item['votos_dia'] = (int)($item['votos_dia'] ?? 0);
    }
    
    // ============================================
    // AVANCE GENERAL DE VOTOS PREGONEROS POR DÍA (últimos 7 días)
    // ============================================
    $sqlAvanceVotosPregoneros = "
        WITH fechas AS (
            SELECT generate_series(
                CURRENT_DATE - INTERVAL '6 days',
                CURRENT_DATE,
                '1 day'::interval
            )::date as fecha
        )
        SELECT 
            f.fecha,
            COUNT(p.id_pregonero) as votos_dia,
            SUM(COUNT(p.id_pregonero)) OVER (ORDER BY f.fecha) as votos_acumulados
        FROM fechas f
        LEFT JOIN pregonero p ON DATE(p.fecha_voto) = f.fecha 
            AND p.voto_registrado = TRUE 
            AND p.activo = true
        GROUP BY f.fecha
        ORDER BY f.fecha
    ";
    
    $stmt = $pdo->prepare($sqlAvanceVotosPregoneros);
    $stmt->execute();
    $avanceVotosPregoneros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear fechas
    foreach ($avanceVotosPregoneros as &$item) {
        $item['fecha'] = date('d/m', strtotime($item['fecha']));
        $item['votos_acumulados'] = (int)($item['votos_acumulados'] ?? 0);
        $item['votos_dia'] = (int)($item['votos_dia'] ?? 0);
    }
    
    echo json_encode([
        'success' => true,
        'avanceVotosReferidos' => $avanceVotosReferidos,
        'avanceVotosPregoneros' => $avanceVotosPregoneros
    ]);
    
} catch (Exception $e) {
    error_log("Error en get_datos_graficas: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error al cargar datos para gráficas'
    ]);
}
?>