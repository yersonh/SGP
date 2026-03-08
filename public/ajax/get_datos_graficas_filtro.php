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
    $id_referenciador = isset($_GET['id_referenciador']) ? (int)$_GET['id_referenciador'] : 0;
    
    // ============================================
    // 1. REFERIDOS QUE VOTARON POR HORA (con filtro de referenciador)
    // ============================================
    $sqlReferidos = "
        SELECT 
            EXTRACT(HOUR FROM r.fecha_voto) as hora,
            COUNT(*) as cantidad
        FROM referenciados r
        WHERE r.fecha_voto >= NOW() - INTERVAL '24 HOURS'
        AND r.voto_registrado = TRUE
        AND r.activo = true
    ";
    
    if ($id_referenciador > 0) {
        $sqlReferidos .= " AND r.id_referenciador = :id_referenciador";
    }
    
    $sqlReferidos .= " GROUP BY EXTRACT(HOUR FROM r.fecha_voto) ORDER BY hora";
    
    $stmt = $pdo->prepare($sqlReferidos);
    if ($id_referenciador > 0) {
        $stmt->bindParam(':id_referenciador', $id_referenciador, PDO::PARAM_INT);
    }
    $stmt->execute();
    $referidosVotaronHora = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Completar horas faltantes con 0
    $horasCompletas = [];
    for ($i = 0; $i < 24; $i++) {
        $encontrado = false;
        foreach ($referidosVotaronHora as $item) {
            if ((int)$item['hora'] === $i) {
                $horasCompletas[] = [
                    'hora' => str_pad($i, 2, '0', STR_PAD_LEFT),
                    'cantidad' => (int)$item['cantidad']
                ];
                $encontrado = true;
                break;
            }
        }
        if (!$encontrado) {
            $horasCompletas[] = [
                'hora' => str_pad($i, 2, '0', STR_PAD_LEFT),
                'cantidad' => 0
            ];
        }
    }
    
    // ============================================
    // 2. PREGONEROS QUE VOTARON POR HORA (con filtro de referenciador)
    // ============================================
    $sqlPregoneros = "
        SELECT 
            EXTRACT(HOUR FROM p.fecha_voto) as hora,
            COUNT(*) as cantidad
        FROM pregonero p
        WHERE p.fecha_voto >= NOW() - INTERVAL '24 HOURS'
        AND p.voto_registrado = TRUE
        AND p.activo = true
    ";
    
    if ($id_referenciador > 0) {
        $sqlPregoneros .= " AND p.id_referenciador = :id_referenciador";
    }
    
    $sqlPregoneros .= " GROUP BY EXTRACT(HOUR FROM p.fecha_voto) ORDER BY hora";
    
    $stmt = $pdo->prepare($sqlPregoneros);
    if ($id_referenciador > 0) {
        $stmt->bindParam(':id_referenciador', $id_referenciador, PDO::PARAM_INT);
    }
    $stmt->execute();
    $pregonerosVotaronHora = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Completar horas faltantes con 0
    $horasPregonerosCompletas = [];
    for ($i = 0; $i < 24; $i++) {
        $encontrado = false;
        foreach ($pregonerosVotaronHora as $item) {
            if ((int)$item['hora'] === $i) {
                $horasPregonerosCompletas[] = [
                    'hora' => str_pad($i, 2, '0', STR_PAD_LEFT),
                    'cantidad' => (int)$item['cantidad']
                ];
                $encontrado = true;
                break;
            }
        }
        if (!$encontrado) {
            $horasPregonerosCompletas[] = [
                'hora' => str_pad($i, 2, '0', STR_PAD_LEFT),
                'cantidad' => 0
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'referidosVotaronHora' => $horasCompletas,
        'pregonerosVotaronHora' => $horasPregonerosCompletas,
        'filtro' => $id_referenciador > 0 ? 'referenciador específico' : 'todos'
    ]);
    
} catch (Exception $e) {
    error_log("Error en get_datos_graficas_filtro: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error al cargar datos para gráficas'
    ]);
}
?>