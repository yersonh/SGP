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
            r.id_grupo,  -- <-- AÑADIDO: id_grupo de referenciados
            
            -- Información del grupo parlamentario
            gp.nombre as grupo_nombre,  -- <-- AÑADIDO: nombre del grupo
            
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
        LEFT JOIN grupos_parlamentarios gp ON r.id_grupo = gp.id_grupo  -- <-- AÑADIDO: JOIN con grupos_parlamentarios
        WHERE r.activo = true
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
    
    // <-- AÑADIDO: Filtro por grupo parlamentario
    if (!empty($filtros['id_grupo'])) {
        $conditions[] = "r.id_grupo = :id_grupo";
        $params[':id_grupo'] = $filtros['id_grupo'];
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
    /**
 * Calcular eficiencia general de las llamadas realizadas
 * Combina: Rating, resultado exitoso, y completitud de datos
 * Retorna un porcentaje de eficiencia (0-100%)
 */
public function getEficienciaGeneralLlamadas() {
    $sql = "SELECT 
                -- 1. EFICIENCIA POR RATING (60% del peso)
                ROUND(
                    (AVG(
                        CASE 
                            WHEN rating >= 4 THEN 100  -- Excelente (4-5 estrellas)
                            WHEN rating = 3 THEN 75    -- Bueno (3 estrellas)
                            WHEN rating = 2 THEN 50    -- Regular (2 estrellas)
                            WHEN rating = 1 THEN 25    -- Malo (1 estrella)
                            ELSE 0                     -- Sin calificar
                        END
                    ) * 0.6), 2
                ) as eficiencia_rating,
                
                -- 2. EFICIENCIA POR RESULTADO (30% del peso)
                ROUND(
                    (AVG(
                        CASE 
                            -- Resultados exitosos (contacto efectivo)
                            WHEN id_resultado = 1 THEN 100   -- Contactado
                            WHEN id_resultado = 6 THEN 80    -- Dejó mensaje
                            -- Resultados parciales
                            WHEN id_resultado = 2 THEN 40    -- No contesta
                            WHEN id_resultado = 5 THEN 30    -- Ocupado
                            WHEN id_resultado = 7 THEN 20    -- Rechazó llamada
                            -- Resultados no exitosos
                            WHEN id_resultado = 3 THEN 10    -- Número equivocado
                            WHEN id_resultado = 4 THEN 5     -- Teléfono apagado
                            ELSE 0
                        END
                    ) * 0.3), 2
                ) as eficiencia_resultado,
                
                -- 3. EFICIENCIA POR COMPLETITUD DE DATOS (10% del peso)
                ROUND(
                    (AVG(
                        CASE 
                            -- Llamadas con observaciones detalladas y rating
                            WHEN observaciones IS NOT NULL 
                                 AND TRIM(observaciones) != '' 
                                 AND rating IS NOT NULL THEN 100
                            -- Llamadas con al menos rating
                            WHEN rating IS NOT NULL THEN 70
                            -- Llamadas con al menos observaciones
                            WHEN observaciones IS NOT NULL 
                                 AND TRIM(observaciones) != '' THEN 50
                            -- Llamadas básicas (solo resultado)
                            ELSE 30
                        END
                    ) * 0.1), 2
                ) as eficiencia_datos
                
            FROM llamadas_tracking
            WHERE id_resultado IS NOT NULL";
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        // Sumar las tres eficiencias ponderadas
        $eficienciaTotal = 
            ($result['eficiencia_rating'] ?? 0) +
            ($result['eficiencia_resultado'] ?? 0) +
            ($result['eficiencia_datos'] ?? 0);
        
        return round($eficienciaTotal, 2);
    }
    
    return 0;
}
/**
 * Calcular eficiencia general de llamadas basada SOLO en rating
 * Convierte rating 1-5 estrellas a porcentaje 0-100%
 * Retorna un solo número: porcentaje de eficiencia
 */
public function getEficienciaGeneralPorRating() {
    $sql = "SELECT 
                -- Convertir cada estrella a un valor porcentual
                ROUND(
                    AVG(
                        CASE 
                            WHEN rating = 5 THEN 100  -- ★★★★★ = 100%
                            WHEN rating = 4 THEN 80   -- ★★★★☆ = 80%
                            WHEN rating = 3 THEN 60   -- ★★★☆☆ = 60%
                            WHEN rating = 2 THEN 40   -- ★★☆☆☆ = 40%
                            WHEN rating = 1 THEN 20   -- ★☆☆☆☆ = 20%
                            ELSE 0                    -- Sin calificar = 0%
                        END
                    ), 
                    2
                ) as eficiencia_porcentaje
            FROM llamadas_tracking
            WHERE rating IS NOT NULL";
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result['eficiencia_porcentaje'] ?? 0;
}
/**
 * Calcular % de Tracking para un referenciador
 * Cuántos de sus referidos han recibido al menos una llamada
 * 
 * @param int $id_referenciador ID del usuario referenciador
 * @return array Con total referidos, referidos llamados y porcentaje
 */
public function getPorcentajeTrackingPorReferenciador($id_referenciador) {
    $sql = "SELECT 
                -- Total de referidos del referenciador
                COALESCE(
                    (SELECT COUNT(*) 
                     FROM referenciados 
                     WHERE id_referenciador = :id_referenciador 
                     AND activo = true), 
                    0
                ) as total_referidos,
                
                -- Referidos únicos de él que han recibido al menos una llamada
                COALESCE(
                    (SELECT COUNT(DISTINCT r.id_referenciado)
                     FROM referenciados r
                     INNER JOIN llamadas_tracking lt ON r.id_referenciado = lt.id_referenciado
                     WHERE r.id_referenciador = :id_referenciador
                     AND r.activo = true), 
                    0
                ) as referidos_llamados,
                
                -- Calcular porcentaje de tracking
                CASE 
                    WHEN (
                        SELECT COUNT(*) 
                        FROM referenciados 
                        WHERE id_referenciador = :id_referenciador 
                        AND activo = true
                    ) > 0 
                    THEN ROUND(
                        (
                            SELECT COUNT(DISTINCT r.id_referenciado)
                            FROM referenciados r
                            INNER JOIN llamadas_tracking lt ON r.id_referenciado = lt.id_referenciado
                            WHERE r.id_referenciador = :id_referenciador
                            AND r.activo = true
                        ) * 100.0 /
                        (
                            SELECT COUNT(*) 
                            FROM referenciados 
                            WHERE id_referenciador = :id_referenciador 
                            AND activo = true
                        ), 
                        2
                    )
                    ELSE 0 
                END as porcentaje_tracking";
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->bindValue(':id_referenciador', $id_referenciador, PDO::PARAM_INT);
    $stmt->execute();
    
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return [
        'total_referidos' => $resultado['total_referidos'] ?? 0,
        'referidos_llamados' => $resultado['referidos_llamados'] ?? 0,
        'porcentaje_tracking' => $resultado['porcentaje_tracking'] ?? 0,
        'pendientes' => ($resultado['total_referidos'] ?? 0) - ($resultado['referidos_llamados'] ?? 0)
    ];
}
/**
 * Obtener cantidad de referidos trackeados por referenciador
 * (Cuántos de sus referidos han recibido al menos una llamada)
 * 
 * @param int $id_referenciador ID del usuario referenciador
 * @return int Cantidad de referidos trackeados
 */
public function getCantidadTrackeados($id_referenciador) {
    $sql = "SELECT COUNT(DISTINCT r.id_referenciado) as trackeados
            FROM referenciados r
            INNER JOIN llamadas_tracking lt ON r.id_referenciado = lt.id_referenciado
            WHERE r.id_referenciador = :id_referenciador
            AND r.activo = true";
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->bindValue(':id_referenciador', $id_referenciador, PDO::PARAM_INT);
    $stmt->execute();
    
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $resultado['trackeados'] ?? 0;
}
/**
 * Calcular porcentaje de calidad basado en rating
 * Convierte rating 1-5 estrellas a porcentaje 0-100%
 * 
 * @param int $id_referenciador ID del usuario referenciador
 * @return float Porcentaje de calidad (0-100%)
 */
public function getPorcentajeCalidadPorRating($id_referenciador) {
    $sql = "SELECT 
                -- Convertir cada rating a porcentaje y promediar
                ROUND(
                    AVG(
                        CASE 
                            WHEN lt.rating = 5 THEN 100  -- ★★★★★ = 100%
                            WHEN lt.rating = 4 THEN 80   -- ★★★★☆ = 80%
                            WHEN lt.rating = 3 THEN 60   -- ★★★☆☆ = 60%
                            WHEN lt.rating = 2 THEN 40   -- ★★☆☆☆ = 40%
                            WHEN lt.rating = 1 THEN 20   -- ★☆☆☆☆ = 20%
                            ELSE 0                       -- Sin calificar = 0%
                        END
                    ), 
                    2
                ) as porcentaje_calidad
            FROM llamadas_tracking lt
            INNER JOIN referenciados r ON lt.id_referenciado = r.id_referenciado
            WHERE r.id_referenciador = :id_referenciador
            AND lt.rating IS NOT NULL";
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->bindValue(':id_referenciador', $id_referenciador, PDO::PARAM_INT);
    $stmt->execute();
    
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $resultado['porcentaje_calidad'] ?? 0;
}
/**
 * Obtener resumen completo de tracking con filtros
 */
public function getResumenTracking($filtros = []) {
    // Construir WHERE común primero
    $whereInfo = $this->construirWhereFiltros($filtros);
    $where = $whereInfo['where'];
    $params = $whereInfo['params'];
    
    // Consulta base para total de llamadas
    $sqlBase = "SELECT COUNT(*) as total_llamadas FROM llamadas_tracking lt {$where}";
    
    $stmt = $this->pdo->prepare($sqlBase);
    $stmt->execute($params);
    $totalLlamadas = $stmt->fetch(PDO::FETCH_ASSOC)['total_llamadas'];
    
    // Obtener contactos efectivos (resultado = 1: Contactado)
    $sqlContactos = str_replace("COUNT(*) as total_llamadas", "COUNT(*) as contactos_efectivos", $sqlBase);
    $sqlContactos .= " AND lt.id_resultado = 1";
    
    $stmt = $this->pdo->prepare($sqlContactos);
    $stmt->execute($params);
    $contactosEfectivos = $stmt->fetch(PDO::FETCH_ASSOC)['contactos_efectivos'];
    
    // Obtener rating promedio
    $sqlRating = str_replace("COUNT(*) as total_llamadas", "AVG(lt.rating) as rating_promedio", $sqlBase);
    $sqlRating .= " AND lt.rating IS NOT NULL";
    
    $stmt = $this->pdo->prepare($sqlRating);
    $stmt->execute($params);
    $ratingPromedio = $stmt->fetch(PDO::FETCH_ASSOC)['rating_promedio'] ?? 0;
    
    // Obtener llamadores activos - CORREGIDO
    $sqlLlamadores = "SELECT COUNT(DISTINCT id_usuario) as llamadores_activos FROM llamadas_tracking lt {$where}";
    
    $stmt = $this->pdo->prepare($sqlLlamadores);
    $stmt->execute($params);
    $llamadoresActivos = $stmt->fetch(PDO::FETCH_ASSOC)['llamadores_activos'] ?? 0;
    
    // Obtener distribución por hora
    $sqlHoras = "SELECT 
                    EXTRACT(HOUR FROM fecha_llamada) as hora,
                    COUNT(*) as cantidad
                FROM llamadas_tracking lt
                {$where}
                GROUP BY EXTRACT(HOUR FROM fecha_llamada) 
                ORDER BY hora";
    
    $stmt = $this->pdo->prepare($sqlHoras);
    $stmt->execute($params);
    $graficaHoras = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener distribución por resultado
    $sqlResultados = "SELECT 
                        tr.id_resultado,
                        tr.nombre as resultado,
                        COUNT(lt.id_llamada) as cantidad
                    FROM llamadas_tracking lt
                    LEFT JOIN tipos_resultado_llamada tr ON lt.id_resultado = tr.id_resultado
                    {$where}
                    GROUP BY tr.id_resultado, tr.nombre 
                    ORDER BY cantidad DESC";
    
    $stmt = $this->pdo->prepare($sqlResultados);
    $stmt->execute($params);
    $distribucionResultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener distribución por rating
    $sqlRatingDist = "SELECT 
                        COALESCE(lt.rating, 0) as rating,
                        COUNT(*) as cantidad
                    FROM llamadas_tracking lt
                    {$where}
                    GROUP BY COALESCE(lt.rating, 0) 
                    ORDER BY rating DESC";
    
    $stmt = $this->pdo->prepare($sqlRatingDist);
    $stmt->execute($params);
    $distribucionRatingRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convertir a formato de diccionario
    $distribucionRating = [];
    foreach ($distribucionRatingRaw as $item) {
        $distribucionRating[$item['rating']] = $item['cantidad'];
    }
    
    // Calcular porcentaje de contactos
    $porcentajeContactos = $totalLlamadas > 0 ? round(($contactosEfectivos / $totalLlamadas) * 100, 2) : 0;
    
    // Obtener hora pico (hora con más llamadas)
    $horaPico = null;
    if (!empty($graficaHoras)) {
        $max = max(array_column($graficaHoras, 'cantidad'));
        $horaMax = array_filter($graficaHoras, function($item) use ($max) {
            return $item['cantidad'] == $max;
        });
        $horaPico = reset($horaMax);
    }
    
    // Calcular eficiencia general
    $eficienciaGeneral = $this->getEficienciaGeneralPorRating();
    
    // Comparación con ayer
    $cambioVsAyer = 0;
    $porcentajeCambio = 0;
    
    if (!empty($filtros['fecha']) && $filtros['rango'] !== 'personalizado') {
        $fechaAyer = date('Y-m-d', strtotime($filtros['fecha'] . ' -1 day'));
        
        $sqlAyer = "SELECT COUNT(*) as total FROM llamadas_tracking WHERE DATE(fecha_llamada) = :fecha_ayer";
        $paramsAyer = [':fecha_ayer' => $fechaAyer];
        
        // Aplicar filtros de resultado y rating también al cálculo de ayer
        if (!empty($filtros['tipo_resultado']) && $filtros['tipo_resultado'] !== 'todos') {
            $sqlAyer .= " AND id_resultado = :tipo_resultado";
            $paramsAyer[':tipo_resultado'] = $filtros['tipo_resultado'];
        }
        
        if (!empty($filtros['rating']) && $filtros['rating'] !== 'todos') {
            if ($filtros['rating'] === '0') {
                $sqlAyer .= " AND rating IS NULL";
            } else {
                $sqlAyer .= " AND rating = :rating";
                $paramsAyer[':rating'] = $filtros['rating'];
            }
        }
        
        $stmt = $this->pdo->prepare($sqlAyer);
        $stmt->execute($paramsAyer);
        $totalAyer = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        $cambioVsAyer = $totalLlamadas - $totalAyer;
        $porcentajeCambio = $totalAyer > 0 ? round(($cambioVsAyer / $totalAyer) * 100, 2) : ($totalLlamadas > 0 ? 100 : 0);
    }
    
    return [
        'total_llamadas' => $totalLlamadas,
        'contactos_efectivos' => $contactosEfectivos,
        'porcentaje_contactos' => $porcentajeContactos,
        'rating_promedio' => (float) $ratingPromedio,
        'llamadores_activos' => $llamadoresActivos,
        'grafica_horas' => $graficaHoras,
        'distribucion_resultado' => $distribucionResultado,
        'distribucion_rating' => $distribucionRating,
        'hora_pico' => $horaPico,
        'eficiencia_general' => $eficienciaGeneral,
        'cambio_vs_ayer' => $cambioVsAyer,
        'porcentaje_cambio' => $porcentajeCambio
    ];
}

/**
 * Obtener datos para análisis de calidad
 */
public function getAnalisisCalidad($filtros = []) {
    // Construir WHERE común
    $whereInfo = $this->construirWhereFiltros($filtros);
    $where = $whereInfo['where'];
    $params = $whereInfo['params'];
    
    // Distribución por rating
    $sqlRating = "SELECT 
                    COALESCE(lt.rating, 0) as rating,
                    COUNT(*) as cantidad,
                    ROUND(COUNT(*) * 100.0 / (
                        SELECT COUNT(*) FROM llamadas_tracking lt2 {$where}
                    ), 2) as porcentaje
                 FROM llamadas_tracking lt
                 {$where}
                 GROUP BY COALESCE(lt.rating, 0)
                 ORDER BY rating DESC";
    
    $stmt = $this->pdo->prepare($sqlRating);
    $stmt->execute($params);
    $distribucionRating = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calidad por resultado (porcentaje exitoso por tipo de resultado)
    $sqlCalidadResultado = "SELECT 
                              tr.id_resultado,
                              tr.nombre as resultado,
                              COUNT(lt.id_llamada) as cantidad,
                              ROUND(AVG(
                                  CASE 
                                      WHEN lt.rating >= 4 THEN 100
                                      WHEN lt.rating = 3 THEN 75
                                      WHEN lt.rating = 2 THEN 50
                                      WHEN lt.rating = 1 THEN 25
                                      ELSE 0
                                  END
                              ), 2) as porcentaje_exitoso
                           FROM llamadas_tracking lt
                           LEFT JOIN tipos_resultado_llamada tr ON lt.id_resultado = tr.id_resultado
                           {$where}
                           GROUP BY tr.id_resultado, tr.nombre
                           ORDER BY cantidad DESC";
    
    $stmt = $this->pdo->prepare($sqlCalidadResultado);
    $stmt->execute($params);
    $calidadPorResultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Rating promedio por hora
    $sqlRatingHora = "SELECT 
                         EXTRACT(HOUR FROM fecha_llamada) as hora,
                         ROUND(AVG(lt.rating), 2) as rating_promedio
                      FROM llamadas_tracking lt
                      {$where}
                      AND lt.rating IS NOT NULL
                      GROUP BY EXTRACT(HOUR FROM fecha_llamada)
                      ORDER BY hora";
    
    $stmt = $this->pdo->prepare($sqlRatingHora);
    $stmt->execute($params);
    $ratingPorHora = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Estadísticas de observaciones
    $sqlObservaciones = "SELECT 
                            ROUND(
                                (COUNT(CASE WHEN lt.observaciones IS NOT NULL AND TRIM(lt.observaciones) != '' THEN 1 END) * 100.0 / 
                                COUNT(*)), 2
                            ) as porcentaje_con_observaciones,
                            ROUND(
                                AVG(LENGTH(lt.observaciones)), 0
                            ) as longitud_promedio_observaciones
                         FROM llamadas_tracking lt
                         {$where}";
    
    $stmt = $this->pdo->prepare($sqlObservaciones);
    $stmt->execute($params);
    $estadisticasObservaciones = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Últimas observaciones
    $sqlUltimasObs = "SELECT 
                         lt.observaciones,
                         lt.fecha_llamada,
                         CONCAT(u.nombres, ' ', u.apellidos) as llamador
                      FROM llamadas_tracking lt
                      INNER JOIN usuario u ON lt.id_usuario = u.id_usuario
                      {$where}
                      AND lt.observaciones IS NOT NULL
                      AND TRIM(lt.observaciones) != ''
                      ORDER BY lt.fecha_llamada DESC
                      LIMIT 5";
    
    $stmt = $this->pdo->prepare($sqlUltimasObs);
    $stmt->execute($params);
    $ultimasObservaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'distribucion_rating' => array_column($distribucionRating, null, 'rating'),
        'calidad_por_resultado' => $calidadPorResultado,
        'rating_por_hora' => $ratingPorHora,
        'porcentaje_con_observaciones' => $estadisticasObservaciones['porcentaje_con_observaciones'] ?? 0,
        'longitud_promedio_observaciones' => $estadisticasObservaciones['longitud_promedio_observaciones'] ?? 0,
        'ultimas_observaciones' => $ultimasObservaciones
    ];
}

/**
 * Construir cláusula WHERE basada en filtros
 */
private function construirWhereFiltros($filtros) {
    $where = "WHERE 1=1";
    $params = [];
    
    // Para rango "hoy" o fecha específica
    if (!empty($filtros['fecha']) && empty($filtros['fecha_desde'])) {
        $where .= " AND DATE(lt.fecha_llamada) = :fecha";
        $params[':fecha'] = $filtros['fecha'];
    }
    
    // Para rango personalizado
    if (!empty($filtros['fecha_desde']) && !empty($filtros['fecha_hasta'])) {
        $where .= " AND DATE(lt.fecha_llamada) BETWEEN :fecha_desde AND :fecha_hasta";
        $params[':fecha_desde'] = $filtros['fecha_desde'];
        $params[':fecha_hasta'] = $filtros['fecha_hasta'];
    }
    
    // Para rangos predefinidos
    if (!empty($filtros['rango'])) {
        switch ($filtros['rango']) {
            case 'ayer':
                $fechaAyer = date('Y-m-d', strtotime('-1 day'));
                $where .= " AND DATE(lt.fecha_llamada) = :fecha";
                $params[':fecha'] = $fechaAyer;
                break;
            case 'semana':
                $where .= " AND DATE(lt.fecha_llamada) >= DATE_TRUNC('week', CURRENT_DATE)";
                break;
            case 'mes':
                $where .= " AND DATE(lt.fecha_llamada) >= DATE_TRUNC('month', CURRENT_DATE)";
                break;
        }
    }
    
    if (!empty($filtros['tipo_resultado']) && $filtros['tipo_resultado'] !== 'todos') {
        $where .= " AND lt.id_resultado = :tipo_resultado";
        $params[':tipo_resultado'] = $filtros['tipo_resultado'];
    }
    
    if (!empty($filtros['rating']) && $filtros['rating'] !== 'todos') {
        if ($filtros['rating'] === '0') {
            $where .= " AND lt.rating IS NULL";
        } else {
            $where .= " AND lt.rating = :rating";
            $params[':rating'] = $filtros['rating'];
        }
    }
    
    return ['where' => $where, 'params' => $params];
}
/**
 * Obtener top llamadores con filtros
 */
public function getTopLlamadoresConFiltros($limite = 10, $filtros = []) {
    $where = $this->construirWhereFiltros($filtros);
    $params = $where['params'];
    
    $sql = "SELECT 
                u.id_usuario,
                CONCAT(u.nombres, ' ', u.apellidos) as nombre_completo,
                u.cedula,
                COUNT(lt.id_llamada) as total_llamadas,
                COUNT(DISTINCT lt.id_referenciado) as referenciados_unicos,
                ROUND(AVG(lt.rating), 2) as rating_promedio,
                COUNT(CASE WHEN lt.id_resultado = 1 THEN 1 END) as contactos_efectivos,
                ROUND(
                    AVG(
                        CASE 
                            WHEN lt.rating = 5 THEN 100
                            WHEN lt.rating = 4 THEN 80
                            WHEN lt.rating = 3 THEN 60
                            WHEN lt.rating = 2 THEN 40
                            WHEN lt.rating = 1 THEN 20
                            ELSE 0
                        END
                    ), 2
                ) as eficiencia
            FROM llamadas_tracking lt
            INNER JOIN usuario u ON lt.id_usuario = u.id_usuario
            {$where['where']}
            GROUP BY u.id_usuario, u.nombres, u.apellidos, u.cedula
            ORDER BY total_llamadas DESC, eficiencia DESC
            LIMIT :limite";
    
    $stmt = $this->pdo->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtener distribución por llamador
 */
public function getDistribucionPorLlamador($filtros = []) {
    $where = $this->construirWhereFiltros($filtros);
    $params = $where['params'];
    
    $sql = "SELECT 
                CONCAT(u.nombres, ' ', u.apellidos) as nombre,
                COUNT(lt.id_llamada) as total_llamadas
            FROM llamadas_tracking lt
            INNER JOIN usuario u ON lt.id_usuario = u.id_usuario
            {$where['where']}
            GROUP BY u.id_usuario, u.nombres, u.apellidos
            ORDER BY total_llamadas DESC
            LIMIT 7";
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtener eficiencia por llamador
 */
public function getEficienciaPorLlamador($filtros = []) {
    $where = $this->construirWhereFiltros($filtros);
    $params = $where['params'];
    
    $sql = "SELECT 
                CONCAT(u.nombres, ' ', u.apellidos) as nombre,
                ROUND(
                    AVG(
                        CASE 
                            WHEN lt.rating = 5 THEN 100
                            WHEN lt.rating = 4 THEN 80
                            WHEN lt.rating = 3 THEN 60
                            WHEN lt.rating = 2 THEN 40
                            WHEN lt.rating = 1 THEN 20
                            ELSE 0
                        END
                    ), 2
                ) as eficiencia
            FROM llamadas_tracking lt
            INNER JOIN usuario u ON lt.id_usuario = u.id_usuario
            {$where['where']}
            AND lt.rating IS NOT NULL
            GROUP BY u.id_usuario, u.nombres, u.apellidos
            HAVING COUNT(lt.id_llamada) >= 5
            ORDER BY eficiencia DESC
            LIMIT 7";
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtener tendencias de llamadas
 */
public function getTendenciasLlamadas($dias = 30, $filtros = []) {
    $sql = "SELECT 
                DATE(lt.fecha_llamada) as fecha,
                COUNT(*) as cantidad_llamadas,
                COUNT(DISTINCT lt.id_usuario) as llamadores_activos,
                ROUND(AVG(lt.rating), 2) as rating_promedio,
                COUNT(CASE WHEN lt.id_resultado = 1 THEN 1 END) as contactos_efectivos
            FROM llamadas_tracking lt
            WHERE DATE(lt.fecha_llamada) >= CURRENT_DATE - INTERVAL '{$dias} days'
            AND DATE(lt.fecha_llamada) <= CURRENT_DATE";
    
    $params = [];
    
    // Aplicar filtros adicionales
    if (!empty($filtros['tipo_resultado']) && $filtros['tipo_resultado'] !== 'todos') {
        $sql .= " AND lt.id_resultado = :tipo_resultado";
        $params[':tipo_resultado'] = $filtros['tipo_resultado'];
    }
    
    if (!empty($filtros['rating']) && $filtros['rating'] !== 'todos') {
        if ($filtros['rating'] === '0') {
            $sql .= " AND lt.rating IS NULL";
        } else {
            $sql .= " AND lt.rating = :rating";
            $params[':rating'] = $filtros['rating'];
        }
    }
    
    $sql .= " GROUP BY DATE(lt.fecha_llamada)
              ORDER BY fecha ASC";
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtener comparativa semanal
 */
public function getComparativaSemanal($filtros = []) {
    // Esta semana (lunes a domingo actual)
    $sqlEstaSemana = "SELECT 
                         EXTRACT(DOW FROM lt.fecha_llamada) as dia_semana,
                         COUNT(*) as cantidad
                     FROM llamadas_tracking lt
                     WHERE DATE(lt.fecha_llamada) >= DATE_TRUNC('week', CURRENT_DATE)
                     AND DATE(lt.fecha_llamada) <= CURRENT_DATE";
    
    $sqlSemanaPasada = "SELECT 
                           EXTRACT(DOW FROM lt.fecha_llamada) as dia_semana,
                           COUNT(*) as cantidad
                       FROM llamadas_tracking lt
                       WHERE DATE(lt.fecha_llamada) >= DATE_TRUNC('week', CURRENT_DATE - INTERVAL '7 days')
                       AND DATE(lt.fecha_llamada) < DATE_TRUNC('week', CURRENT_DATE)";
    
    $params = [];
    
    // Aplicar filtros
    if (!empty($filtros['tipo_resultado']) && $filtros['tipo_resultado'] !== 'todos') {
        $sqlEstaSemana .= " AND lt.id_resultado = :tipo_resultado";
        $sqlSemanaPasada .= " AND lt.id_resultado = :tipo_resultado";
        $params[':tipo_resultado'] = $filtros['tipo_resultado'];
    }
    
    if (!empty($filtros['rating']) && $filtros['rating'] !== 'todos') {
        if ($filtros['rating'] === '0') {
            $sqlEstaSemana .= " AND lt.rating IS NULL";
            $sqlSemanaPasada .= " AND lt.rating IS NULL";
        } else {
            $sqlEstaSemana .= " AND lt.rating = :rating";
            $sqlSemanaPasada .= " AND lt.rating = :rating";
            $params[':rating'] = $filtros['rating'];
        }
    }
    
    $sqlEstaSemana .= " GROUP BY EXTRACT(DOW FROM lt.fecha_llamada)
                        ORDER BY dia_semana";
    
    $sqlSemanaPasada .= " GROUP BY EXTRACT(DOW FROM lt.fecha_llamada)
                          ORDER BY dia_semana";
    
    $stmtEstaSemana = $this->pdo->prepare($sqlEstaSemana);
    $stmtSemanaPasada = $this->pdo->prepare($sqlSemanaPasada);
    
    $stmtEstaSemana->execute($params);
    $stmtSemanaPasada->execute($params);
    
    $estaSemanaRaw = $stmtEstaSemana->fetchAll(PDO::FETCH_ASSOC);
    $semanaPasadaRaw = $stmtSemanaPasada->fetchAll(PDO::FETCH_ASSOC);
    
    // Convertir a arrays indexados por día (0=Domingo, 1=Lunes...)
    $estaSemana = array_fill(0, 7, 0);
    $semanaPasada = array_fill(0, 7, 0);
    
    foreach ($estaSemanaRaw as $item) {
        $dia = (int)$item['dia_semana'];
        $estaSemana[$dia] = (int)$item['cantidad'];
    }
    
    foreach ($semanaPasadaRaw as $item) {
        $dia = (int)$item['dia_semana'];
        $semanaPasada[$dia] = (int)$item['cantidad'];
    }
    
    return [
        'esta_semana' => $estaSemana,
        'semana_pasada' => $semanaPasada
    ];
}

/**
 * Obtener proyección mensual
 */
public function getProyeccionMensual($filtros = []) {
    // Obtener promedio diario de los últimos 7 días
    $sql = "SELECT 
                ROUND(AVG(diario.cantidad), 2) as promedio_diario,
                ROUND(STDDEV(diario.cantidad), 2) as desviacion_estandar
            FROM (
                SELECT DATE(lt.fecha_llamada) as fecha,
                       COUNT(*) as cantidad
                FROM llamadas_tracking lt
                WHERE DATE(lt.fecha_llamada) >= CURRENT_DATE - INTERVAL '7 days'
                AND DATE(lt.fecha_llamada) < CURRENT_DATE";
    
    $params = [];
    
    if (!empty($filtros['tipo_resultado']) && $filtros['tipo_resultado'] !== 'todos') {
        $sql .= " AND lt.id_resultado = :tipo_resultado";
        $params[':tipo_resultado'] = $filtros['tipo_resultado'];
    }
    
    if (!empty($filtros['rating']) && $filtros['rating'] !== 'todos') {
        if ($filtros['rating'] === '0') {
            $sql .= " AND lt.rating IS NULL";
        } else {
            $sql .= " AND lt.rating = :rating";
            $params[':rating'] = $filtros['rating'];
        }
    }
    
    $sql .= " GROUP BY DATE(lt.fecha_llamada)
            ) diario";
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
    $estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $promedioDiario = $estadisticas['promedio_diario'] ?? 0;
    
    // Calcular proyección mensual (promedio diario * 30 días)
    $proyeccionMensual = round($promedioDiario * 30);
    
    // Calcular crecimiento vs mes pasado
    $sqlMesPasado = "SELECT COUNT(*) as total
                    FROM llamadas_tracking lt
                    WHERE DATE(lt.fecha_llamada) >= DATE_TRUNC('month', CURRENT_DATE - INTERVAL '1 month')
                    AND DATE(lt.fecha_llamada) < DATE_TRUNC('month', CURRENT_DATE)";
    
    $sqlEsteMes = "SELECT COUNT(*) as total
                  FROM llamadas_tracking lt
                  WHERE DATE(lt.fecha_llamada) >= DATE_TRUNC('month', CURRENT_DATE)";
    
    // Aplicar filtros a las consultas
    if (!empty($filtros['tipo_resultado']) && $filtros['tipo_resultado'] !== 'todos') {
        $sqlMesPasado .= " AND lt.id_resultado = :tipo_resultado";
        $sqlEsteMes .= " AND lt.id_resultado = :tipo_resultado";
    }
    
    if (!empty($filtros['rating']) && $filtros['rating'] !== 'todos') {
        if ($filtros['rating'] === '0') {
            $sqlMesPasado .= " AND lt.rating IS NULL";
            $sqlEsteMes .= " AND lt.rating IS NULL";
        } else {
            $sqlMesPasado .= " AND lt.rating = :rating";
            $sqlEsteMes .= " AND lt.rating = :rating";
        }
    }
    
    $stmtMesPasado = $this->pdo->prepare($sqlMesPasado);
    $stmtEsteMes = $this->pdo->prepare($sqlEsteMes);
    
    $stmtMesPasado->execute($params);
    $stmtEsteMes->execute($params);
    
    $mesPasado = $stmtMesPasado->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    $esteMes = $stmtEsteMes->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Calcular crecimiento porcentual
    $crecimiento = 0;
    if ($mesPasado > 0) {
        $crecimiento = round((($esteMes - $mesPasado) / $mesPasado) * 100, 2);
    } elseif ($esteMes > 0) {
        $crecimiento = 100; // Si no había datos el mes pasado
    }
    
    return [
        'promedio_diario' => $promedioDiario,
        'proyeccion' => $proyeccionMensual,
        'crecimiento' => $crecimiento,
        'mes_pasado' => $mesPasado,
        'este_mes' => $esteMes,
        'dias_restantes_mes' => date('t') - date('j')
    ];
}
}
?>