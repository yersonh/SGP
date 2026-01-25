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

// Verificar si es una petición AJAX y si el usuario es SuperAdmin
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit();
}

// Obtener los parámetros de filtro
$id_referenciador = isset($_POST['id_referenciador']) ? intval($_POST['id_referenciador']) : 0;
$tipo_filtro = isset($_POST['tipo_filtro']) ? $_POST['tipo_filtro'] : 'todos';

$pdo = Database::getConnection();
$referenciadoModel = new ReferenciadoModel($pdo);

// Construir la consulta SQL según los filtros
$sql = "SELECT 
            r.*,
            CONCAT(u.nombres, ' ', u.apellidos) as referenciador_nombre,
            z.nombre as zona_nombre,
            s.nombre as sector_nombre,
            pv.nombre as puesto_nombre,
            d.nombre as departamento_nombre,
            m.nombre as municipio_nombre,
            oa.nombre as oferta_nombre,
            gp.nombre as grupo_nombre,
            b.nombre as barrio_nombre
        FROM referenciados r
        LEFT JOIN usuario u ON r.id_referenciador = u.id_usuario
        LEFT JOIN zona z ON r.id_zona = z.id_zona
        LEFT JOIN sector s ON r.id_sector = s.id_sector
        LEFT JOIN puesto_votacion pv ON r.id_puesto_votacion = pv.id_puesto
        LEFT JOIN departamento d ON r.id_departamento = d.id_departamento
        LEFT JOIN municipio m ON r.id_municipio = m.id_municipio
        LEFT JOIN oferta_apoyo oa ON r.id_oferta_apoyo = oa.id_oferta
        LEFT JOIN grupo_poblacional gp ON r.id_grupo_poblacional = gp.id_grupo
        LEFT JOIN barrio b ON r.id_barrio = b.id_barrio
        WHERE 1=1";

// Aplicar filtro por referenciador si se especifica
if ($id_referenciador > 0) {
    $sql .= " AND r.id_referenciador = :id_referenciador";
}

// Aplicar filtro por tipo (inactivos, activos, todos)
if ($tipo_filtro === 'inactivos') {
    $sql .= " AND r.activo = false";
} elseif ($tipo_filtro === 'activos') {
    $sql .= " AND r.activo = true";
}

// Ordenar por fecha de registro descendente
$sql .= " ORDER BY r.fecha_registro DESC";

try {
    $stmt = $pdo->prepare($sql);
    
    // Bind de parámetros si existe filtro por referenciador
    if ($id_referenciador > 0) {
        $stmt->bindParam(':id_referenciador', $id_referenciador, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    $referenciados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular estadísticas
    $total = count($referenciados);
    $activos = 0;
    $inactivos = 0;
    
    foreach ($referenciados as $referenciado) {
        $activo = $referenciado['activo'] ?? true;
        $esta_activo = ($activo === true || $activo === 't' || $activo == 1);
        
        if ($esta_activo) {
            $activos++;
        } else {
            $inactivos++;
        }
    }
    
    // Preparar la respuesta
    $response = [
        'success' => true,
        'total' => $total,
        'estadisticas' => [
            'total' => $total,
            'activos' => $activos,
            'inactivos' => $inactivos
        ],
        'data' => $referenciados
    ];
    
    // Si no hay referenciadores, enviar mensaje apropiado
    if ($total === 0) {
        $response['message'] = 'No se encontraron referenciados con los filtros aplicados';
    }
    
} catch (Exception $e) {
    error_log("Error en filtrar_referenciados.php: " . $e->getMessage());
    $response = [
        'success' => false,
        'message' => 'Error al filtrar los referenciados: ' . $e->getMessage()
    ];
}

// Enviar respuesta en formato JSON
header('Content-Type: application/json');
echo json_encode($response);
exit();