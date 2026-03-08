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
    // Obtener la hora actual del cliente (enviada desde JavaScript)
    $hora_cliente = isset($_GET['hora_cliente']) ? (int)$_GET['hora_cliente'] : (int)date('H');
    
    // ============================================
    // 1. REFERIDOS - ACUMULADO POR HORA (SOLO HASTA HORA ACTUAL)
    // ============================================
    $sqlReferidos = "
        SELECT 
            EXTRACT(HOUR FROM r.fecha_voto) as hora,
            COUNT(*) as cantidad
        FROM referenciados r
        WHERE DATE(r.fecha_voto) = CURRENT_DATE
        AND EXTRACT(HOUR FROM r.fecha_voto) <= :hora_cliente
        AND r.voto_registrado = TRUE
        AND r.activo = true
        GROUP BY EXTRACT(HOUR FROM r.fecha_voto)
        ORDER BY hora
    ";
    
    $stmt = $pdo->prepare($sqlReferidos);
    $stmt->bindParam(':hora_cliente', $hora_cliente, PDO::PARAM_INT);
    $stmt->execute();
    $referidosVotaronHora = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Crear array SOLO desde 0 hasta la hora del cliente
    $horas = [];
    $acumuladoReferidos = [];
    $acumulado = 0;
    $hora_actual_mostrada = false;
    
    for ($i = 0; $i <= $hora_cliente; $i++) {
        $horaFormateada = str_pad($i, 2, '0', STR_PAD_LEFT) . ':00';
        $horas[] = $horaFormateada;
        
        // Buscar si hay votos en esta hora
        $votosHora = 0;
        foreach ($referidosVotaronHora as $item) {
            if ((int)$item['hora'] === $i) {
                $votosHora = (int)$item['cantidad'];
                break;
            }
        }
        
        $acumulado += $votosHora;
        $acumuladoReferidos[] = $acumulado;
    }
    
    // ============================================
    // 2. PREGONEROS - ACUMULADO POR HORA (SOLO HASTA HORA ACTUAL)
    // ============================================
    $sqlPregoneros = "
        SELECT 
            EXTRACT(HOUR FROM p.fecha_voto) as hora,
            COUNT(*) as cantidad
        FROM pregonero p
        WHERE DATE(p.fecha_voto) = CURRENT_DATE
        AND EXTRACT(HOUR FROM p.fecha_voto) <= :hora_cliente
        AND p.voto_registrado = TRUE
        AND p.activo = true
        GROUP BY EXTRACT(HOUR FROM p.fecha_voto)
        ORDER BY hora
    ";
    
    $stmt = $pdo->prepare($sqlPregoneros);
    $stmt->bindParam(':hora_cliente', $hora_cliente, PDO::PARAM_INT);
    $stmt->execute();
    $pregonerosVotaronHora = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular acumulados para pregoneros
    $acumulado = 0;
    $acumuladoPregoneros = [];
    
    for ($i = 0; $i <= $hora_cliente; $i++) {
        // Buscar si hay votos en esta hora
        $votosHora = 0;
        foreach ($pregonerosVotaronHora as $item) {
            if ((int)$item['hora'] === $i) {
                $votosHora = (int)$item['cantidad'];
                break;
            }
        }
        
        $acumulado += $votosHora;
        $acumuladoPregoneros[] = $acumulado;
    }
    
    echo json_encode([
        'success' => true,
        'horas' => $horas,
        'acumuladoReferidos' => $acumuladoReferidos,
        'acumuladoPregoneros' => $acumuladoPregoneros
    ]);
    
} catch (Exception $e) {
    error_log("Error en get_datos_graficas_generales: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error al cargar datos para gráficas generales'
    ]);
}
?>