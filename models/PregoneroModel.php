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
                        quien_reporta,
                        id_referenciador,
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
                        :quien_reporta,
                        :id_referenciador,
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
            $stmt->bindParam(':quien_reporta', $datos['quien_reporta'], PDO::PARAM_STR);
            $stmt->bindParam(':id_referenciador', $datos['id_referenciador'], PDO::PARAM_INT);
            $stmt->bindParam(':id_usuario_registro', $datos['id_usuario_registro'], PDO::PARAM_INT);
            
            $stmt->execute();
            
            // Obtener el ID insertado
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['id_pregonero'] : false;
            
        } catch (PDOException $e) {
            error_log("Error al insertar pregonero: " . $e->getMessage());
            
            if ($e->getCode() == 23505) {
                throw new Exception("Ya existe un pregonero con esta identificación");
            } elseif ($e->getCode() == 23503) {
                throw new Exception("El barrio, puesto de votación o referenciador seleccionado no existe");
            } else {
                throw new Exception("Error al guardar el pregonero: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Registrar el voto de un pregonero (ACTUALIZADO con certificado_electoral)
     * 
     * @param int $id_pregonero ID del pregonero
     * @param int $id_usuario ID del usuario que registra el voto
     * @param string|null $foto_ruta Ruta de la foto del comprobante (opcional)
     * @param string|null $certificado_electoral Número de certificado electoral (opcional)
     * @return bool True si se registró correctamente
     */
    public function registrarVoto($id_pregonero, $id_usuario, $foto_ruta = null, $certificado_electoral = null) {
        try {
            $sql = "UPDATE public.pregonero SET 
                    voto_registrado = TRUE,
                    fecha_voto = NOW(),
                    id_usuario_registro_voto = :id_usuario,
                    foto_comprobante = :foto_ruta,
                    certificado_electoral = :certificado_electoral
                    WHERE id_pregonero = :id_pregonero";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id_pregonero', $id_pregonero, PDO::PARAM_INT);
            $stmt->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
            $stmt->bindParam(':foto_ruta', $foto_ruta, PDO::PARAM_STR);
            $stmt->bindParam(':certificado_electoral', $certificado_electoral, PDO::PARAM_STR);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al registrar voto de pregonero: " . $e->getMessage());
            return false;
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
     * Obtiene un pregonero por su ID (ACTUALIZADO con certificado_electoral)
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
                    u.apellidos as usuario_apellidos,
                    r.nombres as referenciador_nombres,
                    r.apellidos as referenciador_apellidos,
                    uv.nombres as usuario_voto_nombres,
                    uv.apellidos as usuario_voto_apellidos
                FROM public.pregonero p
                INNER JOIN public.barrio b ON p.id_barrio = b.id_barrio
                INNER JOIN public.puesto_votacion pv ON p.id_puesto = pv.id_puesto
                INNER JOIN public.sector s ON pv.id_sector = s.id_sector
                INNER JOIN public.zona z ON s.id_zona = z.id_zona
                INNER JOIN public.usuario u ON p.id_usuario_registro = u.id_usuario
                LEFT JOIN public.usuario r ON p.id_referenciador = r.id_usuario
                LEFT JOIN public.usuario uv ON p.id_usuario_registro_voto = uv.id_usuario
                WHERE p.id_pregonero = :id_pregonero AND p.activo = TRUE";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_pregonero', $id_pregonero, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene todos los pregoneros (con filtros opcionales) - ACTUALIZADO con certificado_electoral
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
                    u.apellidos as usuario_apellidos,
                    r.nombres as referenciador_nombres,
                    r.apellidos as referenciador_apellidos,
                    uv.nombres as usuario_voto_nombres,
                    uv.apellidos as usuario_voto_apellidos
                FROM public.pregonero p
                INNER JOIN public.barrio b ON p.id_barrio = b.id_barrio
                INNER JOIN public.puesto_votacion pv ON p.id_puesto = pv.id_puesto
                INNER JOIN public.sector s ON pv.id_sector = s.id_sector
                INNER JOIN public.zona z ON s.id_zona = z.id_zona
                INNER JOIN public.usuario u ON p.id_usuario_registro = u.id_usuario
                LEFT JOIN public.usuario r ON p.id_referenciador = r.id_usuario
                LEFT JOIN public.usuario uv ON p.id_usuario_registro_voto = uv.id_usuario
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
        
        if (!empty($filtros['id_referenciador'])) {
            $sql .= " AND p.id_referenciador = :id_referenciador";
            $params[':id_referenciador'] = $filtros['id_referenciador'];
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
     * Actualiza los datos de un pregonero (ACTUALIZADO con certificado_electoral)
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
                        mesa = :mesa,
                        quien_reporta = :quien_reporta,
                        id_referenciador = :id_referenciador,
                        certificado_electoral = :certificado_electoral
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
            $stmt->bindParam(':quien_reporta', $datos['quien_reporta'], PDO::PARAM_STR);
            $stmt->bindParam(':id_referenciador', $datos['id_referenciador'], PDO::PARAM_INT);
            $stmt->bindParam(':certificado_electoral', $datos['certificado_electoral'] ?? null, PDO::PARAM_STR);
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
     * Reactiva un pregonero (cambia activo a TRUE)
     * 
     * @param int $id_pregonero ID del pregonero
     * @return bool True si se reactivó correctamente
     */
    public function reactivar($id_pregonero) {
        try {
            $sql = "UPDATE public.pregonero SET activo = TRUE WHERE id_pregonero = :id_pregonero";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id_pregonero', $id_pregonero, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al reactivar pregonero: " . $e->getMessage());
            throw new Exception("Error al reactivar el pregonero");
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
        
        // Pregoneros que ya votaron
        $sql = "SELECT COUNT(*) as total FROM public.pregonero WHERE activo = TRUE AND voto_registrado = TRUE";
        $stmt = $this->pdo->query($sql);
        $stats['votaron'] = $stmt->fetchColumn();
        
        // Pregoneros pendientes
        $sql = "SELECT COUNT(*) as total FROM public.pregonero WHERE activo = TRUE AND voto_registrado = FALSE";
        $stmt = $this->pdo->query($sql);
        $stats['pendientes'] = $stmt->fetchColumn();
        
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
        
        // Pregoneros por referenciador
        $sql = "SELECT 
                    CONCAT(u.nombres, ' ', u.apellidos) as referenciador,
                    COUNT(*) as cantidad
                FROM public.pregonero p
                INNER JOIN public.usuario u ON p.id_referenciador = u.id_usuario
                WHERE p.activo = TRUE AND p.id_referenciador IS NOT NULL
                GROUP BY u.id_usuario
                ORDER BY cantidad DESC
                LIMIT 10";
        $stmt = $this->pdo->query($sql);
        $stats['por_referenciador'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
    }
    
    /**
     * Obtiene pregoneros paginados con filtros (ACTUALIZADO con certificado_electoral)
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
                    CONCAT(u.nombres, ' ', u.apellidos) as usuario_registro_nombre,
                    CONCAT(r.nombres, ' ', r.apellidos) as referenciador_nombre,
                    CONCAT(uv.nombres, ' ', uv.apellidos) as usuario_voto_nombre
                FROM public.pregonero p
                LEFT JOIN public.barrio b ON p.id_barrio = b.id_barrio
                LEFT JOIN public.puesto_votacion pv ON p.id_puesto = pv.id_puesto
                LEFT JOIN public.sector s ON pv.id_sector = s.id_sector
                LEFT JOIN public.zona z ON s.id_zona = z.id_zona
                LEFT JOIN public.usuario u ON p.id_usuario_registro = u.id_usuario
                LEFT JOIN public.usuario r ON p.id_referenciador = r.id_usuario
                LEFT JOIN public.usuario uv ON p.id_usuario_registro_voto = uv.id_usuario
                WHERE 1=1";
        
        $conditions = [];
        $params = [];
        $paramTypes = [];
        
        // ============================================
        // BÚSQUEDA GLOBAL (ACTUALIZADA con certificado_electoral)
        // ============================================
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            
            $conditions[] = "(
                p.nombres ILIKE ? OR 
                p.apellidos ILIKE ? OR 
                p.identificacion ILIKE ? OR 
                p.telefono ILIKE ? OR
                p.corregimiento ILIKE ? OR
                p.comuna ILIKE ? OR
                p.quien_reporta ILIKE ? OR
                p.certificado_electoral ILIKE ? OR  -- NUEVO CAMPO
                b.nombre ILIKE ? OR 
                pv.nombre ILIKE ? OR
                s.nombre ILIKE ? OR
                z.nombre ILIKE ? OR
                u.nombres ILIKE ? OR 
                u.apellidos ILIKE ? OR
                r.nombres ILIKE ? OR 
                r.apellidos ILIKE ? OR
                uv.nombres ILIKE ? OR 
                uv.apellidos ILIKE ? OR
                CAST(p.mesa AS TEXT) ILIKE ?
            )";
            
            $searchTerm = '%' . $search . '%';
            
            // 19 parámetros (agregamos certificado_electoral)
            for ($i = 0; $i < 19; $i++) {
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
        
        // Quien reporta (búsqueda parcial)
        if (!empty($filters['quien_reporta'])) {
            $conditions[] = "p.quien_reporta ILIKE ?";
            $params[] = '%' . $filters['quien_reporta'] . '%';
            $paramTypes[] = \PDO::PARAM_STR;
        }
        
        // Referenciador
        if (!empty($filters['id_referenciador'])) {
            $conditions[] = "p.id_referenciador = ?";
            $params[] = $filters['id_referenciador'];
            $paramTypes[] = \PDO::PARAM_INT;
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
        
        // Filtro por voto registrado
        if (isset($filters['voto_registrado'])) {
            $conditions[] = "p.voto_registrado = ?";
            $params[] = $filters['voto_registrado'];
            $paramTypes[] = \PDO::PARAM_BOOL;
        }
        
        // Filtro por certificado electoral (NUEVO)
        if (!empty($filters['certificado_electoral'])) {
            $conditions[] = "p.certificado_electoral ILIKE ?";
            $params[] = '%' . $filters['certificado_electoral'] . '%';
            $paramTypes[] = \PDO::PARAM_STR;
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
     * Obtiene el total de pregoneros según filtros (ACTUALIZADO con certificado_electoral)
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
        if (!empty($filters['search']) || !empty($filters['zona']) || !empty($filters['id_referenciador'])) {
            $sql .= " LEFT JOIN public.barrio b ON p.id_barrio = b.id_barrio
                      LEFT JOIN public.puesto_votacion pv ON p.id_puesto = pv.id_puesto
                      LEFT JOIN public.sector s ON pv.id_sector = s.id_sector
                      LEFT JOIN public.zona z ON s.id_zona = z.id_zona
                      LEFT JOIN public.usuario u ON p.id_usuario_registro = u.id_usuario
                      LEFT JOIN public.usuario r ON p.id_referenciador = r.id_usuario
                      LEFT JOIN public.usuario uv ON p.id_usuario_registro_voto = uv.id_usuario";
        }
        
        $sql .= " WHERE 1=1";
        
        // ============================================
        // BÚSQUEDA GLOBAL (ACTUALIZADA con certificado_electoral)
        // ============================================
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            
            $conditions[] = "(
                p.nombres ILIKE ? OR 
                p.apellidos ILIKE ? OR 
                p.identificacion ILIKE ? OR 
                p.telefono ILIKE ? OR
                p.corregimiento ILIKE ? OR
                p.comuna ILIKE ? OR
                p.quien_reporta ILIKE ? OR
                p.certificado_electoral ILIKE ? OR  -- NUEVO CAMPO
                b.nombre ILIKE ? OR 
                pv.nombre ILIKE ? OR
                s.nombre ILIKE ? OR
                z.nombre ILIKE ? OR
                u.nombres ILIKE ? OR 
                u.apellidos ILIKE ? OR
                r.nombres ILIKE ? OR 
                r.apellidos ILIKE ? OR
                uv.nombres ILIKE ? OR 
                uv.apellidos ILIKE ? OR
                CAST(p.mesa AS TEXT) ILIKE ?
            )";
            
            $searchTerm = '%' . $search . '%';
            
            // 19 parámetros (agregamos certificado_electoral)
            for ($i = 0; $i < 19; $i++) {
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
        
        // Quien reporta (búsqueda parcial)
        if (!empty($filters['quien_reporta'])) {
            $conditions[] = "p.quien_reporta ILIKE ?";
            $params[] = '%' . $filters['quien_reporta'] . '%';
            $paramTypes[] = \PDO::PARAM_STR;
        }
        
        // Referenciador
        if (!empty($filters['id_referenciador'])) {
            $conditions[] = "p.id_referenciador = ?";
            $params[] = $filters['id_referenciador'];
            $paramTypes[] = \PDO::PARAM_INT;
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
        
        // Filtro por voto registrado
        if (isset($filters['voto_registrado'])) {
            $conditions[] = "p.voto_registrado = ?";
            $params[] = $filters['voto_registrado'];
            $paramTypes[] = \PDO::PARAM_BOOL;
        }
        
        // Filtro por certificado electoral (NUEVO)
        if (!empty($filters['certificado_electoral'])) {
            $conditions[] = "p.certificado_electoral ILIKE ?";
            $params[] = '%' . $filters['certificado_electoral'] . '%';
            $paramTypes[] = \PDO::PARAM_STR;
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
     * Obtiene un pregonero por su número de identificación (ACTUALIZADO con certificado_electoral)
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
                        p.quien_reporta,
                        p.fecha_registro,
                        p.activo,
                        p.voto_registrado,
                        p.fecha_voto,
                        p.foto_comprobante,
                        p.certificado_electoral,  -- NUEVO CAMPO
                        b.nombre as barrio_nombre,
                        pv.nombre as puesto_nombre,
                        s.nombre as sector_nombre,
                        z.nombre as zona_nombre,
                        u.nombres as usuario_nombres,
                        u.apellidos as usuario_apellidos,
                        r.nombres as referenciador_nombres,
                        r.apellidos as referenciador_apellidos,
                        uv.nombres as usuario_voto_nombres,
                        uv.apellidos as usuario_voto_apellidos
                    FROM public.pregonero p
                    LEFT JOIN public.barrio b ON p.id_barrio = b.id_barrio
                    LEFT JOIN public.puesto_votacion pv ON p.id_puesto = pv.id_puesto
                    LEFT JOIN public.sector s ON pv.id_sector = s.id_sector
                    LEFT JOIN public.zona z ON s.id_zona = z.id_zona
                    LEFT JOIN public.usuario u ON p.id_usuario_registro = u.id_usuario
                    LEFT JOIN public.usuario r ON p.id_referenciador = r.id_usuario
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
    
    /**
     * Obtiene la lista de referenciadores activos (usuarios tipo 'Referenciador')
     * 
     * @return array Lista de referenciadores
     */
    public function getReferenciadores() {
        try {
            $sql = "SELECT 
                        id_usuario,
                        nombres,
                        apellidos,
                        cedula,
                        telefono,
                        correo
                    FROM public.usuario 
                    WHERE tipo_usuario = 'Referenciador' AND activo = TRUE
                    ORDER BY nombres, apellidos";
            
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo referenciadores: " . $e->getMessage());
            return [];
        }
    }
/**
 * Cuenta pregoneros por referenciador
 */
public function countByReferenciador($id_referenciador) {
    try {
        $sql = "SELECT COUNT(*) as total 
                FROM pregonero 
                WHERE id_referenciador = ? AND activo = true";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id_referenciador]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    } catch (PDOException $e) {
        error_log("Error en countByReferenciador: " . $e->getMessage());
        return 0;
    }
}

/**
 * Cuenta pregoneros que votaron por referenciador
 */
public function countVotaronByReferenciador($id_referenciador) {
    try {
        $sql = "SELECT COUNT(*) as total 
                FROM pregonero 
                WHERE id_referenciador = ? AND voto_registrado = true AND activo = true";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id_referenciador]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    } catch (PDOException $e) {
        error_log("Error en countVotaronByReferenciador: " . $e->getMessage());
        return 0;
    }
}
}
?>