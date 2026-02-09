<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/LiderModel.php';
require_once __DIR__ . '/../../models/ReferenciadoModel.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';

// Verificar autenticación
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$pdo = Database::getConnection();
$liderModel = new LiderModel($pdo);
$referenciadoModel = new ReferenciadoModel($pdo);
$usuarioModel = new UsuarioModel($pdo);

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'get_referidos_lider':
        getReferidosLider($pdo, $usuarioModel);
        break;
    
    case 'get_detalle_lider':
        getDetalleLider($liderModel, $referenciadoModel, $usuarioModel);
        break;
    
    case 'asignar_referenciador':
        asignarReferenciador($liderModel);
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

function getReferidosLider($pdo, $usuarioModel) {
    $id_lider = intval($_POST['id_lider'] ?? 0);
    
    if ($id_lider <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de líder no válido']);
        return;
    }
    
    try {
        // Consulta directa para obtener referidos del líder
        $sql = "SELECT r.*, 
                       CONCAT(u.nombres, ' ', u.apellidos) as referenciador_nombre
                FROM referenciados r
                LEFT JOIN usuario u ON r.id_referenciador = u.id_usuario
                WHERE r.id_lider = :id_lider 
                AND r.activo = true
                ORDER BY r.fecha_registro DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id_lider' => $id_lider]);
        $referidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($referidos)) {
            echo json_encode(['success' => true, 'referidos' => []]);
            return;
        }
        
        // Formatear datos
        $referidosEnriquecidos = [];
        foreach ($referidos as $referido) {
            // Formatear fecha
            if (!empty($referido['fecha_registro'])) {
                $fecha = new DateTime($referido['fecha_registro']);
                $referido['fecha_registro'] = $fecha->format('d/m/Y H:i');
            }
            
            $referidosEnriquecidos[] = $referido;
        }
        
        echo json_encode([
            'success' => true,
            'referidos' => $referidosEnriquecidos
        ]);
        
    } catch (Exception $e) {
        error_log("Error en getReferidosLider: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener referidos'
        ]);
    }
}

function getDetalleLider($liderModel, $referenciadoModel, $usuarioModel) {
    $id_lider = intval($_POST['id_lider'] ?? 0);
    
    if ($id_lider <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de líder no válido']);
        return;
    }
    
    // Obtener líder
    $lider = $liderModel->getById($id_lider);
    
    if (!$lider) {
        echo json_encode(['success' => false, 'message' => 'Líder no encontrado']);
        return;
    }
    
    // Contar referidos del líder
    $cantidadReferidos = $referenciadoModel->countByLider($id_lider);
    
    // Calcular porcentaje del total
    $totalReferidos = $referenciadoModel->countReferenciadosActivos();
    $porcentajeContribucion = $totalReferidos > 0 ? 
        round(($cantidadReferidos * 100) / $totalReferidos, 2) : 0;
    
    // Obtener información del referenciador
    $referenciadorNombre = 'No asignado';
    if ($lider['id_usuario']) {
        $referenciador = $usuarioModel->getUsuarioById($lider['id_usuario']);
        if ($referenciador) {
            $referenciadorNombre = $referenciador['nombres'] . ' ' . $referenciador['apellidos'];
        }
    }
    
    // Formatear fechas
    if (!empty($lider['fecha_creacion'])) {
        $fechaCreacion = new DateTime($lider['fecha_creacion']);
        $lider['fecha_creacion'] = $fechaCreacion->format('d/m/Y H:i');
    }
    
    if (!empty($lider['fecha_actualizacion'])) {
        $fechaActualizacion = new DateTime($lider['fecha_actualizacion']);
        $lider['fecha_actualizacion'] = $fechaActualizacion->format('d/m/Y H:i');
    }
    
    // Agregar datos adicionales
    $lider['referenciador_nombre'] = $referenciadorNombre;
    $lider['cantidad_referidos'] = $cantidadReferidos;
    $lider['porcentaje_contribucion'] = $porcentajeContribucion;
    
    echo json_encode([
        'success' => true,
        'lider' => $lider
    ]);
}

function asignarReferenciador($liderModel) {
    $id_lider = intval($_POST['id_lider'] ?? 0);
    $id_usuario = !empty($_POST['id_usuario']) ? intval($_POST['id_usuario']) : null;
    
    if ($id_lider <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de líder no válido']);
        return;
    }
    
    // Actualizar referenciador
    $resultado = $liderModel->update($id_lider, [
        'id_usuario' => $id_usuario
    ]);
    
    if ($resultado['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Referenciador asignado correctamente'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $resultado['message']
        ]);
    }
}
?>