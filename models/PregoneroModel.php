<?php
// models/PregoneroModel.php

class PregoneroModel {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Inserta un nuevo pregonero en la base de datos
     * 
     * @param array $datos Datos del pregonero
     * @return int|false ID del pregonero insertado o false si falla
     */
    public function insertar($datos) {
        try {
            $sql = "INSERT INTO public.pregonero (
                        nombres,
                        apellidos,
                        identificacion,
                        telefono,
                        id_barrio,
                        corregimiento,
                        comuna,
                        id_puesto,
                        mesa,
                        id_usuario_registro,
                        fecha_registro,
                        activo
                    ) VALUES (
                        :nombres,
                        :apellidos,
                        :identificacion,
                        :telefono,
                        :id_barrio,
                        :corregimiento,
                        :comuna,
                        :id_puesto,
                        :mesa,
                        :id_usuario_registro,
                        CURRENT_TIMESTAMP,
                        TRUE
                    ) RETURNING id_pregonero";
            
            $stmt = $this->pdo->prepare($sql);
            
            // Sanitizar y asignar parámetros
            $stmt->bindParam(':nombres', $datos['nombres'], PDO::PARAM_STR);
            $stmt->bindParam(':apellidos', $datos['apellidos'], PDO::PARAM_STR);
            $stmt->bindParam(':identificacion', $datos['identificacion'], PDO::PARAM_STR);
            $stmt->bindParam(':telefono', $datos['telefono'], PDO::PARAM_STR);
            $stmt->bindParam(':id_barrio', $datos['id_barrio'], PDO::PARAM_INT);
            $stmt->bindParam(':corregimiento', $datos['corregimiento'], PDO::PARAM_STR);
            $stmt->bindParam(':comuna', $datos['comuna'], PDO::PARAM_STR);
            $stmt->bindParam(':id_puesto', $datos['id_puesto'], PDO::PARAM_INT);
            $stmt->bindParam(':mesa', $datos['mesa'], PDO::PARAM_INT);
            $stmt->bindParam(':id_usuario_registro', $datos['id_usuario_registro'], PDO::PARAM_INT);
            
            $stmt->execute();
            
            // Obtener el ID insertado
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['id_pregonero'] : false;
            
        } catch (PDOException $e) {
            // Aquí puedes registrar el error en un log
            error_log("Error al insertar pregonero: " . $e->getMessage());
            
            // Relanzar la excepción con un mensaje más amigable
            if ($e->getCode() == 23505) { // Código de error de unique violation
                throw new Exception("Ya existe un pregonero con esta identificación");
            } elseif ($e->getCode() == 23503) { // Foreign key violation
                throw new Exception("El barrio o puesto de votación seleccionado no existe");
            } else {
                throw new Exception("Error al guardar el pregonero: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Verifica si ya existe un pregonero con la misma identificación
     * 
     * @param string $identificacion Número de identificación
     * @return bool True si ya existe
     */
    public function existeIdentificacion($identificacion) {
        $sql = "SELECT COUNT(*) FROM public.pregonero WHERE identificacion = :identificacion AND activo = TRUE";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':identificacion', $identificacion, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }
    /**
 * Verifica si una identificación existe y devuelve información detallada
 * 
 * @param string $identificacion Número de identificación
 * @return array|false Datos del pregonero si existe, false si no existe
 */
public function getInfoPorIdentificacion($identificacion) {
    try {
        $sql = "SELECT 
                    p.id_pregonero,
                    p.nombres,
                    p.apellidos,
                    p.identificacion,
                    p.fecha_registro,
                    u.nombres as usuario_nombres,
                    u.apellidos as usuario_apellidos
                FROM public.pregonero p
                INNER JOIN public.usuario u ON p.id_usuario_registro = u.id_usuario
                WHERE p.identificacion = :identificacion AND p.activo = TRUE";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':identificacion', $identificacion, PDO::PARAM_STR);
        $stmt->execute();
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($resultado) {
            return [
                'id_pregonero' => $resultado['id_pregonero'],
                'nombres' => $resultado['nombres'],
                'apellidos' => $resultado['apellidos'],
                'identificacion' => $resultado['identificacion'],
                'fecha_registro' => $resultado['fecha_registro'],
                'usuario_registro' => $resultado['usuario_nombres'] . ' ' . $resultado['usuario_apellidos']
            ];
        }
        
        return false;
        
    } catch (PDOException $e) {
        error_log("Error obteniendo información por identificación: " . $e->getMessage());
        throw new Exception("Error al verificar la identificación");
    }
}
    /**
     * Obtiene un pregonero por su ID
     * 
     * @param int $id_pregonero ID del pregonero
     * @return array|false Datos del pregonero o false si no existe
     */
    public function getById($id_pregonero) {
        $sql = "SELECT 
                    p.*,
                    b.nombre as barrio_nombre,
                    pv.nombre as puesto_nombre,
                    pv.id_sector,
                    s.nombre as sector_nombre,
                    z.nombre as zona_nombre,
                    u.nombres as usuario_nombres,
                    u.apellidos as usuario_apellidos
                FROM public.pregonero p
                INNER JOIN public.barrio b ON p.id_barrio = b.id_barrio
                INNER JOIN public.puesto_votacion pv ON p.id_puesto = pv.id_puesto
                INNER JOIN public.sector s ON pv.id_sector = s.id_sector
                INNER JOIN public.zona z ON s.id_zona = z.id_zona
                INNER JOIN public.usuario u ON p.id_usuario_registro = u.id_usuario
                WHERE p.id_pregonero = :id_pregonero AND p.activo = TRUE";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_pregonero', $id_pregonero, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene todos los pregoneros (con filtros opcionales)
     * 
     * @param array $filtros Filtros para la búsqueda
     * @return array Lista de pregoneros
     */
    public function getAll($filtros = []) {
        $sql = "SELECT 
                    p.*,
                    b.nombre as barrio_nombre,
                    pv.nombre as puesto_nombre,
                    pv.id_sector,
                    s.nombre as sector_nombre,
                    z.nombre as zona_nombre,
                    u.nombres as usuario_nombres,
                    u.apellidos as usuario_apellidos
                FROM public.pregonero p
                INNER JOIN public.barrio b ON p.id_barrio = b.id_barrio
                INNER JOIN public.puesto_votacion pv ON p.id_puesto = pv.id_puesto
                INNER JOIN public.sector s ON pv.id_sector = s.id_sector
                INNER JOIN public.zona z ON s.id_zona = z.id_zona
                INNER JOIN public.usuario u ON p.id_usuario_registro = u.id_usuario
                WHERE p.activo = TRUE";
        
        $params = [];
        
        // Aplicar filtros si existen
        if (!empty($filtros['id_barrio'])) {
            $sql .= " AND p.id_barrio = :id_barrio";
            $params[':id_barrio'] = $filtros['id_barrio'];
        }
        
        if (!empty($filtros['id_puesto'])) {
            $sql .= " AND p.id_puesto = :id_puesto";
            $params[':id_puesto'] = $filtros['id_puesto'];
        }
        
        if (!empty($filtros['id_usuario_registro'])) {
            $sql .= " AND p.id_usuario_registro = :id_usuario_registro";
            $params[':id_usuario_registro'] = $filtros['id_usuario_registro'];
        }
        
        if (!empty($filtros['fecha_desde'])) {
            $sql .= " AND DATE(p.fecha_registro) >= :fecha_desde";
            $params[':fecha_desde'] = $filtros['fecha_desde'];
        }
        
        if (!empty($filtros['fecha_hasta'])) {
            $sql .= " AND DATE(p.fecha_registro) <= :fecha_hasta";
            $params[':fecha_hasta'] = $filtros['fecha_hasta'];
        }
        
        $sql .= " ORDER BY p.fecha_registro DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Actualiza los datos de un pregonero
     * 
     * @param int $id_pregonero ID del pregonero
     * @param array $datos Nuevos datos
     * @return bool True si se actualizó correctamente
     */
    public function actualizar($id_pregonero, $datos) {
        try {
            $sql = "UPDATE public.pregonero SET
                        nombres = :nombres,
                        apellidos = :apellidos,
                        identificacion = :identificacion,
                        telefono = :telefono,
                        id_barrio = :id_barrio,
                        corregimiento = :corregimiento,
                        comuna = :comuna,
                        id_puesto = :id_puesto,
                        mesa = :mesa
                    WHERE id_pregonero = :id_pregonero AND activo = TRUE";
            
            $stmt = $this->pdo->prepare($sql);
            
            $stmt->bindParam(':nombres', $datos['nombres'], PDO::PARAM_STR);
            $stmt->bindParam(':apellidos', $datos['apellidos'], PDO::PARAM_STR);
            $stmt->bindParam(':identificacion', $datos['identificacion'], PDO::PARAM_STR);
            $stmt->bindParam(':telefono', $datos['telefono'], PDO::PARAM_STR);
            $stmt->bindParam(':id_barrio', $datos['id_barrio'], PDO::PARAM_INT);
            $stmt->bindParam(':corregimiento', $datos['corregimiento'], PDO::PARAM_STR);
            $stmt->bindParam(':comuna', $datos['comuna'], PDO::PARAM_STR);
            $stmt->bindParam(':id_puesto', $datos['id_puesto'], PDO::PARAM_INT);
            $stmt->bindParam(':mesa', $datos['mesa'], PDO::PARAM_INT);
            $stmt->bindParam(':id_pregonero', $id_pregonero, PDO::PARAM_INT);
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            error_log("Error al actualizar pregonero: " . $e->getMessage());
            throw new Exception("Error al actualizar el pregonero");
        }
    }
    
    /**
     * Elimina lógicamente un pregonero (cambia activo a FALSE)
     * 
     * @param int $id_pregonero ID del pregonero
     * @return bool True si se eliminó correctamente
     */
    public function eliminar($id_pregonero) {
        try {
            $sql = "UPDATE public.pregonero SET activo = FALSE WHERE id_pregonero = :id_pregonero";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id_pregonero', $id_pregonero, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al eliminar pregonero: " . $e->getMessage());
            throw new Exception("Error al eliminar el pregonero");
        }
    }
    
    /**
     * Obtiene estadísticas de pregoneros
     * 
     * @return array Estadísticas
     */
    public function getEstadisticas() {
        $stats = [];
        
        // Total de pregoneros activos
        $sql = "SELECT COUNT(*) as total FROM public.pregonero WHERE activo = TRUE";
        $stmt = $this->pdo->query($sql);
        $stats['total'] = $stmt->fetchColumn();
        
        // Pregoneros por barrio
        $sql = "SELECT 
                    b.nombre as barrio,
                    COUNT(*) as cantidad
                FROM public.pregonero p
                INNER JOIN public.barrio b ON p.id_barrio = b.id_barrio
                WHERE p.activo = TRUE
                GROUP BY b.nombre
                ORDER BY cantidad DESC
                LIMIT 10";
        $stmt = $this->pdo->query($sql);
        $stats['por_barrio'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Pregoneros por zona
        $sql = "SELECT 
                    z.nombre as zona,
                    COUNT(*) as cantidad
                FROM public.pregonero p
                INNER JOIN public.puesto_votacion pv ON p.id_puesto = pv.id_puesto
                INNER JOIN public.sector s ON pv.id_sector = s.id_sector
                INNER JOIN public.zona z ON s.id_zona = z.id_zona
                WHERE p.activo = TRUE
                GROUP BY z.nombre
                ORDER BY cantidad DESC";
        $stmt = $this->pdo->query($sql);
        $stats['por_zona'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
    }
    /**
 * Obtiene pregoneros paginados con filtros
 * 
 * @param int $page Número de página
 * @param int $perPage Registros por página
 * @param array $filters Filtros a aplicar
 * @return array Lista de pregoneros
 */
public function getPregonerosPaginados($page = 1, $perPage = 50, $filters = []) {
    $offset = ($page - 1) * $perPage;
    
    $sql = "SELECT 
                p.*,
                b.nombre as barrio_nombre,
                pv.nombre as puesto_nombre,
                pv.id_sector,
                s.nombre as sector_nombre,
                z.nombre as zona_nombre,
                CONCAT(u.nombres, ' ', u.apellidos) as usuario_registro_nombre
            FROM public.pregonero p
            LEFT JOIN public.barrio b ON p.id_barrio = b.id_barrio
            LEFT JOIN public.puesto_votacion pv ON p.id_puesto = pv.id_puesto
            LEFT JOIN public.sector s ON pv.id_sector = s.id_sector
            LEFT JOIN public.zona z ON s.id_zona = z.id_zona
            LEFT JOIN public.usuario u ON p.id_usuario_registro = u.id_usuario
            WHERE 1=1";
    
    $conditions = [];
    $params = [];
    $paramTypes = [];
    
    // ============================================
    // BÚSQUEDA GLOBAL
    // ============================================
    if (!empty($filters['search'])) {
        $search = $filters['search'];
        
        $conditions[] = "(
            -- Campos principales de pregoneros (6)
            p.nombres ILIKE ? OR 
            p.apellidos ILIKE ? OR 
            p.identificacion ILIKE ? OR 
            p.telefono ILIKE ? OR
            p.corregimiento ILIKE ? OR
            p.comuna ILIKE ? OR
            
            -- Campos de tablas relacionadas (4)
            b.nombre ILIKE ? OR 
            pv.nombre ILIKE ? OR
            s.nombre ILIKE ? OR
            z.nombre ILIKE ? OR
            
            -- Usuario que registró (2)
            u.nombres ILIKE ? OR 
            u.apellidos ILIKE ? OR
            
            -- Número de mesa (convertido a texto) (1)
            CAST(p.mesa AS TEXT) ILIKE ?
        )";
        
        $searchTerm = '%' . $search . '%';
        
        // Total de campos: 13 (6+4+2+1 = 13)
        for ($i = 0; $i < 13; $i++) {
            $params[] = $searchTerm;
            $paramTypes[] = \PDO::PARAM_STR;
        }
    }
    
    // ============================================
    // FILTROS AVANZADOS
    // ============================================
    
    // Filtro por estado activo/inactivo
    if (isset($filters['activo']) && $filters['activo'] !== '') {
        $conditions[] = "p.activo = ?";
        $params[] = $filters['activo'];
        $paramTypes[] = \PDO::PARAM_BOOL;
    }
    
    // Zona
    if (!empty($filters['zona'])) {
        $conditions[] = "z.id_zona = ?";
        $params[] = $filters['zona'];
        $paramTypes[] = \PDO::PARAM_INT;
    }
    
    // Barrio
    if (!empty($filters['barrio'])) {
        $conditions[] = "p.id_barrio = ?";
        $params[] = $filters['barrio'];
        $paramTypes[] = \PDO::PARAM_INT;
    }
    
    // Puesto de votación
    if (!empty($filters['puesto'])) {
        $conditions[] = "p.id_puesto = ?";
        $params[] = $filters['puesto'];
        $paramTypes[] = \PDO::PARAM_INT;
    }
    
    // Comuna (búsqueda parcial)
    if (!empty($filters['comuna'])) {
        $conditions[] = "p.comuna ILIKE ?";
        $params[] = '%' . $filters['comuna'] . '%';
        $paramTypes[] = \PDO::PARAM_STR;
    }
    
    // Corregimiento (búsqueda parcial)
    if (!empty($filters['corregimiento'])) {
        $conditions[] = "p.corregimiento ILIKE ?";
        $params[] = '%' . $filters['corregimiento'] . '%';
        $paramTypes[] = \PDO::PARAM_STR;
    }
    
    // Usuario que registró
    if (!empty($filters['usuario_registro'])) {
        $conditions[] = "p.id_usuario_registro = ?";
        $params[] = $filters['usuario_registro'];
        $paramTypes[] = \PDO::PARAM_INT;
    }
    
    // ============================================
    // FILTROS DE FECHA DE REGISTRO
    // ============================================
    if (!empty($filters['fecha_desde'])) {
        $conditions[] = "DATE(p.fecha_registro) >= ?";
        $params[] = $filters['fecha_desde'];
        $paramTypes[] = \PDO::PARAM_STR;
    }
    
    if (!empty($filters['fecha_hasta'])) {
        $conditions[] = "DATE(p.fecha_registro) <= ?";
        $params[] = $filters['fecha_hasta'];
        $paramTypes[] = \PDO::PARAM_STR;
    }
    // Filtro por voto registrado (NUEVO)
    if (isset($filters['voto_registrado'])) {
        $conditions[] = "p.voto_registrado = ?";
        $params[] = $filters['voto_registrado'];
        $paramTypes[] = \PDO::PARAM_BOOL;
    }
    // ============================================
    // CONSTRUIR CONSULTA FINAL
    // ============================================
    if (!empty($conditions)) {
        $sql .= " AND " . implode(' AND ', $conditions);
    }
    
    $sql .= " ORDER BY p.fecha_registro DESC LIMIT ? OFFSET ?";
    
    // Agregar LIMIT y OFFSET a los parámetros
    $params[] = $perPage;
    $params[] = $offset;
    $paramTypes[] = \PDO::PARAM_INT;
    $paramTypes[] = \PDO::PARAM_INT;
    
    try {
        $stmt = $this->pdo->prepare($sql);
        
        // Bind parameters con tipos
        foreach ($params as $key => $value) {
            $stmt->bindValue($key + 1, $value, $paramTypes[$key] ?? \PDO::PARAM_STR);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error en getPregonerosPaginados: " . $e->getMessage());
        error_log("SQL: " . $sql);
        error_log("Params: " . print_r($params, true));
        return [];
    }
}

/**
 * Obtiene el total de pregoneros según filtros
 * 
 * @param array $filters Filtros a aplicar
 * @return int Total de registros
 */
public function getTotalPregoneros($filters = []) {
    $sql = "SELECT COUNT(*) as total FROM public.pregonero p";
    
    $conditions = [];
    $params = [];
    $paramTypes = [];
    
    // ============================================
    // JOINS SOLO SI HAY BÚSQUEDA EN CAMPOS RELACIONADOS
    // ============================================
    if (!empty($filters['search']) || !empty($filters['zona'])) {
        $sql .= " LEFT JOIN public.barrio b ON p.id_barrio = b.id_barrio
                  LEFT JOIN public.puesto_votacion pv ON p.id_puesto = pv.id_puesto
                  LEFT JOIN public.sector s ON pv.id_sector = s.id_sector
                  LEFT JOIN public.zona z ON s.id_zona = z.id_zona
                  LEFT JOIN public.usuario u ON p.id_usuario_registro = u.id_usuario";
    }
    
    $sql .= " WHERE 1=1";
    
    // ============================================
    // BÚSQUEDA GLOBAL
    // ============================================
    if (!empty($filters['search'])) {
        $search = $filters['search'];
        
        $conditions[] = "(
            -- Campos principales de pregoneros (6)
            p.nombres ILIKE ? OR 
            p.apellidos ILIKE ? OR 
            p.identificacion ILIKE ? OR 
            p.telefono ILIKE ? OR
            p.corregimiento ILIKE ? OR
            p.comuna ILIKE ? OR
            
            -- Campos de tablas relacionadas (4)
            b.nombre ILIKE ? OR 
            pv.nombre ILIKE ? OR
            s.nombre ILIKE ? OR
            z.nombre ILIKE ? OR
            
            -- Usuario que registró (2)
            u.nombres ILIKE ? OR 
            u.apellidos ILIKE ? OR
            
            -- Número de mesa (convertido a texto) (1)
            CAST(p.mesa AS TEXT) ILIKE ?
        )";
        
        $searchTerm = '%' . $search . '%';
        
        // 13 parámetros (6+4+2+1 = 13)
        for ($i = 0; $i < 13; $i++) {
            $params[] = $searchTerm;
            $paramTypes[] = \PDO::PARAM_STR;
        }
    }
    
    // ============================================
    // FILTROS AVANZADOS
    // ============================================
    
    // Filtro por estado activo/inactivo
    if (isset($filters['activo']) && $filters['activo'] !== '') {
        $conditions[] = "p.activo = ?";
        $params[] = $filters['activo'];
        $paramTypes[] = \PDO::PARAM_BOOL;
    }
    
    // Zona
    if (!empty($filters['zona'])) {
        $conditions[] = "z.id_zona = ?";
        $params[] = $filters['zona'];
        $paramTypes[] = \PDO::PARAM_INT;
    }
    
    // Barrio
    if (!empty($filters['barrio'])) {
        $conditions[] = "p.id_barrio = ?";
        $params[] = $filters['barrio'];
        $paramTypes[] = \PDO::PARAM_INT;
    }
    
    // Puesto de votación
    if (!empty($filters['puesto'])) {
        $conditions[] = "p.id_puesto = ?";
        $params[] = $filters['puesto'];
        $paramTypes[] = \PDO::PARAM_INT;
    }
    
    // Comuna (búsqueda parcial)
    if (!empty($filters['comuna'])) {
        $conditions[] = "p.comuna ILIKE ?";
        $params[] = '%' . $filters['comuna'] . '%';
        $paramTypes[] = \PDO::PARAM_STR;
    }
    
    // Corregimiento (búsqueda parcial)
    if (!empty($filters['corregimiento'])) {
        $conditions[] = "p.corregimiento ILIKE ?";
        $params[] = '%' . $filters['corregimiento'] . '%';
        $paramTypes[] = \PDO::PARAM_STR;
    }
    
    // Usuario que registró
    if (!empty($filters['usuario_registro'])) {
        $conditions[] = "p.id_usuario_registro = ?";
        $params[] = $filters['usuario_registro'];
        $paramTypes[] = \PDO::PARAM_INT;
    }
    
    // ============================================
    // FILTROS DE FECHA DE REGISTRO
    // ============================================
    if (!empty($filters['fecha_desde'])) {
        $conditions[] = "DATE(p.fecha_registro) >= ?";
        $params[] = $filters['fecha_desde'];
        $paramTypes[] = \PDO::PARAM_STR;
    }
    
    if (!empty($filters['fecha_hasta'])) {
        $conditions[] = "DATE(p.fecha_registro) <= ?";
        $params[] = $filters['fecha_hasta'];
        $paramTypes[] = \PDO::PARAM_STR;
    }
    // Filtro por voto registrado (NUEVO)
    if (isset($filters['voto_registrado'])) {
        $conditions[] = "p.voto_registrado = ?";
        $params[] = $filters['voto_registrado'];
        $paramTypes[] = \PDO::PARAM_BOOL;
    }
    
    // ============================================
    // CONSTRUIR WHERE
    // ============================================
    if (!empty($conditions)) {
        $sql .= " AND " . implode(' AND ', $conditions);
    }
    
    try {
        $stmt = $this->pdo->prepare($sql);
        
        // Bind parameters con tipos
        foreach ($params as $key => $value) {
            $stmt->bindValue($key + 1, $value, $paramTypes[$key] ?? \PDO::PARAM_STR);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    } catch (PDOException $e) {
        error_log("Error en getTotalPregoneros: " . $e->getMessage());
        error_log("SQL: " . $sql);
        error_log("Params: " . print_r($params, true));
        return 0;
    }
}
/**
 * Obtiene un pregonero por su número de identificación
 * 
 * @param string $identificacion Número de identificación
 * @return array|false Datos del pregonero o false si no existe
 */
public function getPregoneroPorIdentificacion($identificacion) {
    try {
        $sql = "SELECT 
                    p.id_pregonero,
                    p.nombres,
                    p.apellidos,
                    p.identificacion,
                    p.telefono,
                    p.corregimiento,
                    p.comuna,
                    p.mesa,
                    p.fecha_registro,
                    p.activo,
                    p.voto_registrado,  -- <-- ESTE CAMPO DEBE ESTAR
                    p.fecha_voto,
                    b.nombre as barrio_nombre,
                    pv.nombre as puesto_nombre,
                    s.nombre as sector_nombre,
                    z.nombre as zona_nombre,
                    u.nombres as usuario_nombres,
                    u.apellidos as usuario_apellidos,
                    uv.nombres as usuario_voto_nombres,
                    uv.apellidos as usuario_voto_apellidos
                FROM public.pregonero p
                LEFT JOIN public.barrio b ON p.id_barrio = b.id_barrio
                LEFT JOIN public.puesto_votacion pv ON p.id_puesto = pv.id_puesto
                LEFT JOIN public.sector s ON pv.id_sector = s.id_sector
                LEFT JOIN public.zona z ON s.id_zona = z.id_zona
                LEFT JOIN public.usuario u ON p.id_usuario_registro = u.id_usuario
                LEFT JOIN public.usuario uv ON p.id_usuario_registro_voto = uv.id_usuario
                WHERE p.identificacion = :identificacion";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['identificacion' => $identificacion]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error en getPregoneroPorIdentificacion: " . $e->getMessage());
        return false;
    }
}
/**
 * Cuenta el total de pregoneros activos
 * 
 * @return int Total de pregoneros activos
 */
public function contarPregonerosActivos() {
    try {
        $sql = "SELECT COUNT(*) as total FROM public.pregonero WHERE activo = TRUE";
        $stmt = $this->pdo->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    } catch (PDOException $e) {
        error_log("Error contando pregoneros activos: " . $e->getMessage());
        return 0;
    }
}

/**
 * Cuenta el total de pregoneros que ya votaron
 * 
 * @return int Total de pregoneros que votaron
 */
public function contarPregonerosVotaron() {
    try {
        $sql = "SELECT COUNT(*) as total FROM public.pregonero WHERE voto_registrado = TRUE";
        $stmt = $this->pdo->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    } catch (PDOException $e) {
        error_log("Error contando pregoneros que votaron: " . $e->getMessage());
        return 0;
    }
}

/**
 * Cuenta el total de pregoneros que aún no votan (activos y sin voto registrado)
 * 
 * @return int Total de pregoneros pendientes
 */
public function contarPregonerosPendientes() {
    try {
        $sql = "SELECT COUNT(*) as total FROM public.pregonero WHERE activo = TRUE AND voto_registrado = FALSE";
        $stmt = $this->pdo->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    } catch (PDOException $e) {
        error_log("Error contando pregoneros pendientes: " . $e->getMessage());
        return 0;
    }
}
}
?>