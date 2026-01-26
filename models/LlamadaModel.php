<?php
class LlamadaModel {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Guardar una nueva valoración de llamada
     */
    public function guardarValoracionLlamada($datos) {
        $sql = "INSERT INTO llamadas_tracking (
                    id_referenciado, 
                    id_usuario, 
                    id_resultado,
                    telefono, 
                    rating, 
                    observaciones,
                    fecha_llamada
                ) VALUES (
                    :id_referenciado, 
                    :id_usuario, 
                    :id_resultado,
                    :telefono, 
                    :rating, 
                    :observaciones,
                    :fecha_llamada
                ) RETURNING id_llamada";
        
        $stmt = $this->pdo->prepare($sql);
        
        $stmt->bindValue(':id_referenciado', $datos['id_referenciado'], PDO::PARAM_INT);
        $stmt->bindValue(':id_usuario', $datos['id_usuario'], PDO::PARAM_INT);
        $stmt->bindValue(':id_resultado', $datos['id_resultado'] ?? 1, PDO::PARAM_INT); // Default: Contactado (id=1)
        $stmt->bindValue(':telefono', $datos['telefono']);
        $stmt->bindValue(':rating', $datos['rating'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':observaciones', $datos['observaciones'] ?? null);
        $stmt->bindValue(':fecha_llamada', $datos['fecha_llamada'] ?? date('Y-m-d H:i:s'));
        
        $stmt->execute();
        return $stmt->fetchColumn();
    }
    
    /**
     * Obtener tipos de resultado disponibles
     */
    public function getTiposResultado() {
        $sql = "SELECT * FROM tipos_resultado_llamada WHERE activo = TRUE ORDER BY nombre";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Verificar si un referenciado tiene llamadas registradas
     */
    public function tieneLlamadaRegistrada($idReferenciado) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as total 
            FROM llamadas_tracking 
            WHERE id_referenciado = ?
        ");
        $stmt->execute([$idReferenciado]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] > 0;
    }
    
    /**
     * Obtener la última llamada de un referenciado
     */
    public function obtenerUltimaLlamada($idReferenciado) {
        $stmt = $this->pdo->prepare("
            SELECT lt.*, tr.nombre as resultado_nombre
            FROM llamadas_tracking lt
            LEFT JOIN tipos_resultado_llamada tr ON lt.id_resultado = tr.id_resultado
            WHERE lt.id_referenciado = ? 
            ORDER BY lt.fecha_llamada DESC 
            LIMIT 1
        ");
        $stmt->execute([$idReferenciado]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener todas las llamadas de un referenciado
     */
    public function obtenerLlamadasPorReferenciado($idReferenciado) {
        $stmt = $this->pdo->prepare("
            SELECT lt.*, tr.nombre as resultado_nombre,
                   u.nombres as usuario_nombres, u.apellidos as usuario_apellidos
            FROM llamadas_tracking lt
            LEFT JOIN tipos_resultado_llamada tr ON lt.id_resultado = tr.id_resultado
            LEFT JOIN usuario u ON lt.id_usuario = u.id_usuario
            WHERE lt.id_referenciado = ? 
            ORDER BY lt.fecha_llamada DESC
        ");
        $stmt->execute([$idReferenciado]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener estadísticas de llamadas
     */
    public function getEstadisticasLlamadas() {
        $sql = "
            SELECT 
                COUNT(*) as total_llamadas,
                COUNT(DISTINCT id_referenciado) as referenciados_contactados,
                COUNT(DISTINCT id_usuario) as usuarios_activos,
                AVG(rating) as rating_promedio,
                MIN(fecha_llamada) as primera_llamada,
                MAX(fecha_llamada) as ultima_llamada
            FROM llamadas_tracking
            WHERE rating IS NOT NULL
        ";
        
        $stmt = $this->pdo->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener distribución por resultado de llamada
     */
    public function getDistribucionPorResultado() {
        $sql = "
            SELECT 
                tr.nombre as resultado,
                tr.id_resultado,
                COUNT(lt.id_llamada) as cantidad,
                ROUND(COUNT(lt.id_llamada) * 100.0 / (SELECT COUNT(*) FROM llamadas_tracking), 2) as porcentaje
            FROM llamadas_tracking lt
            LEFT JOIN tipos_resultado_llamada tr ON lt.id_resultado = tr.id_resultado
            GROUP BY tr.id_resultado, tr.nombre
            ORDER BY cantidad DESC
        ";
        
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener referenciados con llamadas (para reporte tracking)
     * Con información completa del referenciado y última llamada
     */
    public function getReferenciadosConLlamadas($filtros = []) {
        // Construir la consulta base
        $sql = "
            SELECT DISTINCT ON (r.id_referenciado)
                r.id_referenciado,
                r.nombre,
                r.apellido,
                r.cedula,
                r.telefono,
                r.email,
                r.afinidad,
                r.activo,
                r.fecha_registro,
                
                -- Información del referenciador
                CONCAT(ur.nombres, ' ', ur.apellidos) as referenciador_nombre,
                ur.cedula as referenciador_cedula,
                
                -- Última llamada
                lt.id_llamada,
                lt.fecha_llamada,
                lt.rating,
                lt.observaciones,
                lt.id_resultado,
                tr.nombre as resultado_nombre,
                
                -- Información del usuario que hizo la llamada
                CONCAT(ul.nombres, ' ', ul.apellidos) as llamador_nombre,
                
                -- Conteo de llamadas totales
                (SELECT COUNT(*) FROM llamadas_tracking lt2 WHERE lt2.id_referenciado = r.id_referenciado) as total_llamadas
                
            FROM referenciados r
            INNER JOIN llamadas_tracking lt ON r.id_referenciado = lt.id_referenciado
            LEFT JOIN usuario ur ON r.id_referenciador = ur.id_usuario
            LEFT JOIN usuario ul ON lt.id_usuario = ul.id_usuario
            LEFT JOIN tipos_resultado_llamada tr ON lt.id_resultado = tr.id_resultado
            WHERE 1=1
        ";
        
        $params = [];
        $conditions = [];
        
        // Aplicar filtros
        if (!empty($filtros['fecha_desde'])) {
            $conditions[] = "DATE(lt.fecha_llamada) >= :fecha_desde";
            $params[':fecha_desde'] = $filtros['fecha_desde'];
        }
        
        if (!empty($filtros['fecha_hasta'])) {
            $conditions[] = "DATE(lt.fecha_llamada) <= :fecha_hasta";
            $params[':fecha_hasta'] = $filtros['fecha_hasta'];
        }
        
        if (!empty($filtros['id_resultado'])) {
            $conditions[] = "lt.id_resultado = :id_resultado";
            $params[':id_resultado'] = $filtros['id_resultado'];
        }
        
        if (!empty($filtros['rating_min'])) {
            $conditions[] = "lt.rating >= :rating_min";
            $params[':rating_min'] = $filtros['rating_min'];
        }
        
        if (!empty($filtros['rating_max'])) {
            $conditions[] = "lt.rating <= :rating_max";
            $params[':rating_max'] = $filtros['rating_max'];
        }
        
        if (!empty($filtros['id_referenciador'])) {
            $conditions[] = "r.id_referenciador = :id_referenciador";
            $params[':id_referenciador'] = $filtros['id_referenciador'];
        }
        
        // Agregar condiciones a la consulta
        if (!empty($conditions)) {
            $sql .= " AND " . implode(" AND ", $conditions);
        }
        
        // Ordenar por última llamada (más reciente primero)
        $sql .= " ORDER BY r.id_referenciado, lt.fecha_llamada DESC";
        
        // Preparar y ejecutar
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Si queremos asegurarnos de obtener solo la última llamada por referenciado
        $referenciadosUnicos = [];
        foreach ($resultados as $row) {
            if (!isset($referenciadosUnicos[$row['id_referenciado']])) {
                $referenciadosUnicos[$row['id_referenciado']] = $row;
            }
        }
        
        return array_values($referenciadosUnicos);
    }
    
    /**
     * Obtener conteo de llamadas por día (para gráficos)
     */
    public function getLlamadasPorDia($dias = 30) {
        $sql = "
            SELECT 
                DATE(fecha_llamada) as fecha,
                COUNT(*) as cantidad_llamadas,
                COUNT(DISTINCT id_referenciado) as referenciados_unicos,
                AVG(rating) as rating_promedio
            FROM llamadas_tracking
            WHERE fecha_llamada >= CURRENT_DATE - INTERVAL '{$dias} days'
            GROUP BY DATE(fecha_llamada)
            ORDER BY fecha DESC
        ";
        
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener top llamadores (usuarios que más llamadas hacen)
     */
    public function getTopLlamadores($limite = 10) {
        $sql = "
            SELECT 
                u.id_usuario,
                CONCAT(u.nombres, ' ', u.apellidos) as nombre_completo,
                u.cedula,
                COUNT(lt.id_llamada) as total_llamadas,
                COUNT(DISTINCT lt.id_referenciado) as referenciados_unicos,
                AVG(lt.rating) as rating_promedio
            FROM llamadas_tracking lt
            INNER JOIN usuario u ON lt.id_usuario = u.id_usuario
            GROUP BY u.id_usuario, u.nombres, u.apellidos, u.cedula
            ORDER BY total_llamadas DESC
            LIMIT :limite
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener llamadas por rango de fechas
     */
    public function getLlamadasPorRangoFechas($fecha_inicio, $fecha_fin) {
        $sql = "
            SELECT 
                lt.*,
                r.nombre as referenciado_nombre,
                r.apellido as referenciado_apellido,
                r.cedula as referenciado_cedula,
                CONCAT(u.nombres, ' ', u.apellidos) as llamador_nombre,
                tr.nombre as resultado_nombre
            FROM llamadas_tracking lt
            INNER JOIN referenciados r ON lt.id_referenciado = r.id_referenciado
            INNER JOIN usuario u ON lt.id_usuario = u.id_usuario
            LEFT JOIN tipos_resultado_llamada tr ON lt.id_resultado = tr.id_resultado
            WHERE DATE(lt.fecha_llamada) BETWEEN :fecha_inicio AND :fecha_fin
            ORDER BY lt.fecha_llamada DESC
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':fecha_inicio', $fecha_inicio);
        $stmt->bindValue(':fecha_fin', $fecha_fin);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>