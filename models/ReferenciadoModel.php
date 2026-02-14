<?php
class ReferenciadoModel {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
   public function guardarReferenciado($data) {
    // Asegurar que afinidad sea válida (1-5)
    $afinidad = max(1, min(5, intval($data['afinidad'])));
    
    // Determinar valores según si vota fuera o no
    $votaFuera = $data['vota_fuera'] ?? 'No';
    
    if ($votaFuera === 'Si') {
        // Cuando vota fuera, los campos normales de votación deben ser nulos
        $id_zona = null;
        $id_sector = null;
        $id_puesto_votacion = null;
        $mesa = null;
        $puesto_votacion_fuera = $data['puesto_votacion_fuera'] ?? '';
        $mesa_fuera = $data['mesa_fuera'] ?? null;
        
        // Validar que los campos fuera estén presentes
        if (empty($puesto_votacion_fuera)) {
            throw new Exception('El puesto de votación fuera es requerido cuando vota fuera');
        }
        if (empty($mesa_fuera) || $mesa_fuera < 1) {
            throw new Exception('El número de mesa fuera es requerido y debe ser mayor a 0');
        }
    } else {
        // Cuando NO vota fuera, los campos fuera deben ser nulos
        $id_zona = $data['id_zona'] ?? null;
        $id_sector = $data['id_sector'] ?? null;
        $id_puesto_votacion = $data['id_puesto_votacion'] ?? null;
        $mesa = $data['mesa'] ?? null;
        $puesto_votacion_fuera = null;
        $mesa_fuera = null;
        
        // Validar que los campos normales estén presentes
        if (empty($id_zona)) {
            throw new Exception('La zona es requerida cuando no vota fuera');
        }
    }
    
    // Procesar fechas - convertir string vacío a null
    $fecha_nacimiento = !empty($data['fecha_nacimiento']) ? $data['fecha_nacimiento'] : null;
    
    // Fecha cumplimiento: si está vacía, convertir a null
    $fecha_cumplimiento = !empty($data['fecha_cumplimiento']) ? $data['fecha_cumplimiento'] : null;
    
    // Iniciar transacción para asegurar que todo se guarde o nada
    $this->pdo->beginTransaction();
    
    try {
        // SQL actualizado con todos los campos nuevos
        $sql = "INSERT INTO referenciados (
            nombre, apellido, cedula, direccion, email, telefono, 
            afinidad, id_zona, id_sector, id_puesto_votacion, mesa,
            id_departamento, id_municipio, id_barrio, id_oferta_apoyo, id_grupo_poblacional,
            compromiso, id_referenciador, fecha_registro,
            sexo, vota_fuera, puesto_votacion_fuera, mesa_fuera,
            id_grupo, fecha_nacimiento, fecha_cumplimiento, id_lider  -- CAMPOS NUEVOS
        ) VALUES (
            :nombre, :apellido, :cedula, :direccion, :email, :telefono,
            :afinidad, :id_zona, :id_sector, :id_puesto_votacion, :mesa,
            :id_departamento, :id_municipio, :id_barrio, :id_oferta_apoyo, :id_grupo_poblacional,
            :compromiso, :id_referenciador, NOW(),
            :sexo, :vota_fuera, :puesto_votacion_fuera, :mesa_fuera,
            :id_grupo, :fecha_nacimiento, :fecha_cumplimiento, :id_lider  -- CAMPOS NUEVOS
        ) RETURNING id_referenciado";
        
        $stmt = $this->pdo->prepare($sql);
        
        // Asignar valores
        $stmt->bindValue(':nombre', $data['nombre']);
        $stmt->bindValue(':apellido', $data['apellido']);
        $stmt->bindValue(':cedula', $data['cedula']);
        $stmt->bindValue(':direccion', $data['direccion']);
        $stmt->bindValue(':email', $data['email']);
        $stmt->bindValue(':telefono', $data['telefono']);
        $stmt->bindValue(':afinidad', $afinidad, PDO::PARAM_INT);
        
        // Campos de votación normales
        $stmt->bindValue(':id_zona', $id_zona, PDO::PARAM_INT);
        $stmt->bindValue(':id_sector', $id_sector, PDO::PARAM_INT);
        $stmt->bindValue(':id_puesto_votacion', $id_puesto_votacion, PDO::PARAM_INT);
        $stmt->bindValue(':mesa', $mesa, PDO::PARAM_INT);
        
        // Campos de ubicación
        $stmt->bindValue(':id_departamento', $data['id_departamento'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':id_municipio', $data['id_municipio'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':id_barrio', $data['id_barrio'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':id_oferta_apoyo', $data['id_oferta_apoyo'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':id_grupo_poblacional', $data['id_grupo_poblacional'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':compromiso', $data['compromiso'] ?? '');
        $stmt->bindValue(':id_referenciador', $data['id_referenciador'], PDO::PARAM_INT);
        
        // Campos de información personal
        $stmt->bindValue(':sexo', $data['sexo'] ?? null);
        $stmt->bindValue(':vota_fuera', $votaFuera);
        
        // Campos de votación fuera
        $stmt->bindValue(':puesto_votacion_fuera', $puesto_votacion_fuera);
        $stmt->bindValue(':mesa_fuera', $mesa_fuera, PDO::PARAM_INT);
        
        // Campo grupo parlamentario
        $stmt->bindValue(':id_grupo', $data['id_grupo'] ?? null, PDO::PARAM_INT);
        
        // CAMPOS NUEVOS - IMPORTANTE: fecha_cumplimiento como null si está vacío
        $stmt->bindValue(':fecha_nacimiento', $fecha_nacimiento);
        $stmt->bindValue(':fecha_cumplimiento', $fecha_cumplimiento); // Esto será null si está vacío
        $stmt->bindValue(':id_lider', $data['id_lider'] ?? null, PDO::PARAM_INT);
        
        $stmt->execute();
        
        // Obtener el ID del referenciado recién insertado
        $id_referenciado = $stmt->fetchColumn();
        
        // Guardar los insumos si existen
        if (!empty($data['insumos']) && is_array($data['insumos'])) {
            $this->guardarInsumosReferenciado($id_referenciado, $data['insumos']);
        }
        
        // Confirmar la transacción
        $this->pdo->commit();
        
        return $id_referenciado;
        
    } catch (Exception $e) {
        // Revertir la transacción en caso de error
        $this->pdo->rollBack();
        throw $e;
    }
}
    
    /**
     * Guarda los insumos del referenciado en la tabla pivote
     */
    private function guardarInsumosReferenciado($id_referenciado, $insumos) {
        // Mapeo de nombres de insumos a IDs (podrías hacer una consulta a la tabla insumo)
        $insumosMap = [
            'carro' => 1,
            'caballo' => 2,
            'cicla' => 3,
            'moto' => 4,
            'motocarro' => 5,
            'publicidad' => 6
        ];
        
        $sql = "INSERT INTO referenciado_insumo (id_referenciado, id_insumo, fecha_registro) 
                VALUES (:id_referenciado, :id_insumo, NOW())";
        
        $stmt = $this->pdo->prepare($sql);
        
        foreach ($insumos as $insumo_nombre) {
            if (isset($insumosMap[$insumo_nombre])) {
                $stmt->bindValue(':id_referenciado', $id_referenciado, PDO::PARAM_INT);
                $stmt->bindValue(':id_insumo', $insumosMap[$insumo_nombre], PDO::PARAM_INT);
                $stmt->execute();
            }
        }
    }
    
    /**
     * Obtiene todos los referenciados de un usuario con sus relaciones
     */
    public function getReferenciadosByUsuario($id_referenciador) {
    $sql = "SELECT r.*, 
            d.nombre as departamento_nombre,
            m.nombre as municipio_nombre,
            b.nombre as barrio_nombre,
            gp.nombre as grupo_poblacional_nombre,
            oa.nombre as oferta_apoyo_nombre,
            z.nombre as zona_nombre,
            s.nombre as sector_nombre,
            pv.nombre as puesto_votacion_nombre,
            gr.nombre as grupo_nombre,
            l.nombres as lider_nombres,
            l.apellidos as lider_apellidos,
            l.cc as lider_cc,
            -- AGREGAR ESTA LÍNEA: nombre completo del líder
            CONCAT(l.nombres, ' ', l.apellidos) as lider_nombre_completo,
            CASE 
                WHEN r.vota_fuera = 'Si' THEN r.puesto_votacion_fuera
                ELSE pv.nombre
            END as puesto_votacion_display,
            CASE 
                WHEN r.vota_fuera = 'Si' THEN r.mesa_fuera
                ELSE r.mesa
            END as mesa_display
            FROM referenciados r
            LEFT JOIN departamento d ON r.id_departamento = d.id_departamento
            LEFT JOIN municipio m ON r.id_municipio = m.id_municipio
            LEFT JOIN barrio b ON r.id_barrio = b.id_barrio
            LEFT JOIN grupo_poblacional gp ON r.id_grupo_poblacional = gp.id_grupo
            LEFT JOIN oferta_apoyo oa ON r.id_oferta_apoyo = oa.id_oferta
            LEFT JOIN zona z ON r.id_zona = z.id_zona
            LEFT JOIN sector s ON r.id_sector = s.id_sector
            LEFT JOIN puesto_votacion pv ON r.id_puesto_votacion = pv.id_puesto
            LEFT JOIN grupos_parlamentarios gr ON r.id_grupo = gr.id_grupo
            LEFT JOIN lideres l ON r.id_lider = l.id_lider
            WHERE r.id_referenciador = :id_referenciador
            ORDER BY r.fecha_registro DESC";
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->bindValue(':id_referenciador', $id_referenciador, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
    public function getReferenciadosByUsuarioActivo($id_referenciador) {
    $sql = "SELECT r.*, 
            d.nombre as departamento_nombre,
            m.nombre as municipio_nombre,
            b.nombre as barrio_nombre,
            gp.nombre as grupo_poblacional_nombre,
            oa.nombre as oferta_apoyo_nombre,
            z.nombre as zona_nombre,
            s.nombre as sector_nombre,
            pv.nombre as puesto_votacion_nombre,
            gr.nombre as grupo_nombre,
            l.nombres as lider_nombres,  -- NUEVO
            l.apellidos as lider_apellidos,  -- NUEVO
            l.cc as lider_cc,  -- NUEVO
            CASE 
                WHEN r.vota_fuera = 'Si' THEN r.puesto_votacion_fuera
                ELSE pv.nombre
            END as puesto_votacion_display,
            CASE 
                WHEN r.vota_fuera = 'Si' THEN r.mesa_fuera
                ELSE r.mesa
            END as mesa_display
            FROM referenciados r
            LEFT JOIN departamento d ON r.id_departamento = d.id_departamento
            LEFT JOIN municipio m ON r.id_municipio = m.id_municipio
            LEFT JOIN barrio b ON r.id_barrio = b.id_barrio
            LEFT JOIN grupo_poblacional gp ON r.id_grupo_poblacional = gp.id_grupo
            LEFT JOIN oferta_apoyo oa ON r.id_oferta_apoyo = oa.id_oferta
            LEFT JOIN zona z ON r.id_zona = z.id_zona
            LEFT JOIN sector s ON r.id_sector = s.id_sector
            LEFT JOIN puesto_votacion pv ON r.id_puesto_votacion = pv.id_puesto
            LEFT JOIN grupos_parlamentarios gr ON r.id_grupo = gr.id_grupo
            LEFT JOIN lideres l ON r.id_lider = l.id_lider  -- NUEVO: LEFT JOIN con líderes
            WHERE r.id_referenciador = :id_referenciador
            AND r.activo = true
            ORDER BY r.fecha_registro DESC";
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->bindValue(':id_referenciador', $id_referenciador, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
    /**
     * Obtiene los insumos de un referenciado específico
     */
    public function getInsumosByReferenciado($id_referenciado) {
        $sql = "SELECT i.* 
                FROM insumo i
                JOIN referenciado_insumo ri ON i.id_insumo = ri.id_insumo
                WHERE ri.id_referenciado = :id_referenciado
                ORDER BY i.nombre";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id_referenciado', $id_referenciado, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene un referenciado con todos sus datos (incluyendo insumos)
     */
public function getReferenciadoCompleto($id_referenciado) {
    $sql = "SELECT r.*, 
            r.id_puesto_votacion,
            d.nombre as departamento_nombre,
            m.nombre as municipio_nombre,
            b.nombre as barrio_nombre,
            gp.nombre as grupo_poblacional_nombre,
            oa.nombre as oferta_apoyo_nombre,
            z.nombre as zona_nombre,
            s.nombre as sector_nombre,
            pv.nombre as puesto_votacion_nombre,
            gr.nombre as grupo_nombre,
            l.nombres as lider_nombres,
            l.apellidos as lider_apellidos,
            l.cc as lider_cc,
            CASE 
                WHEN r.vota_fuera = 'Si' THEN r.puesto_votacion_fuera
                ELSE pv.nombre
            END as puesto_votacion_display,
            CASE 
                WHEN r.vota_fuera = 'Si' THEN r.mesa_fuera
                ELSE r.mesa
            END as mesa_display
            FROM referenciados r
            LEFT JOIN departamento d ON r.id_departamento = d.id_departamento
            LEFT JOIN municipio m ON r.id_municipio = m.id_municipio
            LEFT JOIN barrio b ON r.id_barrio = b.id_barrio
            LEFT JOIN grupo_poblacional gp ON r.id_grupo_poblacional = gp.id_grupo
            LEFT JOIN oferta_apoyo oa ON r.id_oferta_apoyo = oa.id_oferta
            LEFT JOIN zona z ON r.id_zona = z.id_zona
            LEFT JOIN sector s ON r.id_sector = s.id_sector
            LEFT JOIN puesto_votacion pv ON r.id_puesto_votacion = pv.id_puesto
            LEFT JOIN grupos_parlamentarios gr ON r.id_grupo = gr.id_grupo
            LEFT JOIN lideres l ON r.id_lider = l.id_lider
            WHERE r.id_referenciado = :id_referenciado";
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->bindValue(':id_referenciado', $id_referenciado, PDO::PARAM_INT);
    $stmt->execute();
    
    $referenciado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($referenciado) {
        $referenciado['insumos'] = $this->getInsumosByReferenciado($id_referenciado);
    }
    
    return $referenciado;
}
/**
 * Verifica si una cédula ya está registrada y retorna información adicional
 */
public function cedulaExiste($cedula, $excluir_id = null) {
    $sql = "SELECT 
                r.id_referenciado,
                r.fecha_registro,
                CONCAT(u.nombres, ' ', u.apellidos) as referenciador_nombre,
                u.id_usuario as referenciador_id
            FROM referenciados r
            LEFT JOIN usuario u ON r.id_referenciador = u.id_usuario
            WHERE r.cedula = :cedula";
    
    if ($excluir_id) {
        $sql .= " AND r.id_referenciado != :excluir_id";
    }
    
    $sql .= " ORDER BY r.fecha_registro DESC LIMIT 1";
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->bindValue(':cedula', $cedula);
    
    if ($excluir_id) {
        $stmt->bindValue(':excluir_id', $excluir_id, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Si no existe, retornamos false
    if (!$resultado) {
        return false;
    }
    
    // Si existe, retornamos array con información
    return [
        'existe' => true,
        'id_referenciado' => $resultado['id_referenciado'],
        'fecha_registro' => $resultado['fecha_registro'],
        'referenciador_nombre' => $resultado['referenciador_nombre'] ?? 'Sin referenciador',
        'referenciador_id' => $resultado['referenciador_id']
    ];
}
    
public function getAllReferenciados() {
    $sql = "SELECT r.*, 
            d.nombre as departamento_nombre,
            m.nombre as municipio_nombre,
            b.nombre as barrio_nombre,
            gp.nombre as grupo_poblacional_nombre,
            oa.nombre as oferta_apoyo_nombre,
            z.nombre as zona_nombre,
            s.nombre as sector_nombre,
            pv.nombre as puesto_votacion_nombre,
            gr.nombre as grupo_nombre,
            -- INFORMACIÓN DEL LÍDER (AGREGADO)
            l.nombres as lider_nombres,
            l.apellidos as lider_apellidos,
            l.cc as lider_cedula,
            l.telefono as lider_telefono,
            CONCAT(l.nombres, ' ', l.apellidos) as lider_nombre_completo,
            -- Fin información del líder
            CONCAT(u.nombres, ' ', u.apellidos) as referenciador_nombre,
            CASE 
                WHEN r.vota_fuera = 'Si' THEN r.puesto_votacion_fuera
                ELSE pv.nombre
            END as puesto_votacion_display,
            CASE 
                WHEN r.vota_fuera = 'Si' THEN r.mesa_fuera
                ELSE r.mesa
            END as mesa_display
            FROM referenciados r
            LEFT JOIN departamento d ON r.id_departamento = d.id_departamento
            LEFT JOIN municipio m ON r.id_municipio = m.id_municipio
            LEFT JOIN barrio b ON r.id_barrio = b.id_barrio
            LEFT JOIN grupo_poblacional gp ON r.id_grupo_poblacional = gp.id_grupo
            LEFT JOIN oferta_apoyo oa ON r.id_oferta_apoyo = oa.id_oferta
            LEFT JOIN zona z ON r.id_zona = z.id_zona
            LEFT JOIN sector s ON r.id_sector = s.id_sector
            LEFT JOIN puesto_votacion pv ON r.id_puesto_votacion = pv.id_puesto
            LEFT JOIN grupos_parlamentarios gr ON r.id_grupo = gr.id_grupo
            -- AGREGADO: JOIN con la tabla lideres
            LEFT JOIN lideres l ON r.id_lider = l.id_lider
            LEFT JOIN usuario u ON r.id_referenciador = u.id_usuario
            ORDER BY r.fecha_registro DESC";
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
public function getAllReferenciadosInactivos() {
    $sql = "SELECT r.*, 
            d.nombre as departamento_nombre,
            m.nombre as municipio_nombre,
            b.nombre as barrio_nombre,
            gp.nombre as grupo_poblacional_nombre,
            oa.nombre as oferta_apoyo_nombre,
            z.nombre as zona_nombre,
            s.nombre as sector_nombre,
            pv.nombre as puesto_votacion_nombre,
            gr.nombre as grupo_nombre,
            CONCAT(u.nombres, ' ', u.apellidos) as referenciador_nombre,
            CASE 
                WHEN r.vota_fuera = 'Si' THEN r.puesto_votacion_fuera
                ELSE pv.nombre
            END as puesto_votacion_display,
            CASE 
                WHEN r.vota_fuera = 'Si' THEN r.mesa_fuera
                ELSE r.mesa
            END as mesa_display
            FROM referenciados r
            LEFT JOIN departamento d ON r.id_departamento = d.id_departamento
            LEFT JOIN municipio m ON r.id_municipio = m.id_municipio
            LEFT JOIN barrio b ON r.id_barrio = b.id_barrio
            LEFT JOIN grupo_poblacional gp ON r.id_grupo_poblacional = gp.id_grupo
            LEFT JOIN oferta_apoyo oa ON r.id_oferta_apoyo = oa.id_oferta
            LEFT JOIN zona z ON r.id_zona = z.id_zona
            LEFT JOIN sector s ON r.id_sector = s.id_sector
            LEFT JOIN puesto_votacion pv ON r.id_puesto_votacion = pv.id_puesto
            LEFT JOIN grupos_parlamentarios gr ON r.id_grupo = gr.id_grupo
            LEFT JOIN usuario u ON r.id_referenciador = u.id_usuario
            WHERE r.activo = false  -- <--- ESTA ES LA CLAVE
            ORDER BY r.fecha_registro DESC";
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
    
    /**
     * Obtiene todos los referenciados con cálculo de porcentaje de avance del referenciador
     */
    public function getAllReferenciadosConAvance() {
        $sql = "SELECT 
                r.*, 
                d.nombre as departamento_nombre,
                m.nombre as municipio_nombre,
                b.nombre as barrio_nombre,
                gp.nombre as grupo_poblacional_nombre,
                oa.nombre as oferta_apoyo_nombre,
                z.nombre as zona_nombre,
                s.nombre as sector_nombre,
                pv.nombre as puesto_votacion_nombre,
                gr.nombre as grupo_nombre,  -- NUEVO: información del grupo
                CONCAT(u.nombres, ' ', u.apellidos) as referenciador_nombre,
                u.tope as referenciador_tope,
                CASE 
                    WHEN r.vota_fuera = 'Si' THEN r.puesto_votacion_fuera
                    ELSE pv.nombre
                END as puesto_votacion_display,
                CASE 
                    WHEN r.vota_fuera = 'Si' THEN r.mesa_fuera
                    ELSE r.mesa
                END as mesa_display,
                -- Calcular conteo de referenciados por usuario
                COUNT(*) OVER (PARTITION BY r.id_referenciador) as total_referidos_usuario,
                -- Calcular porcentaje de avance
                CASE 
                    WHEN u.tope > 0 THEN 
                        ROUND((COUNT(*) OVER (PARTITION BY r.id_referenciador) * 100.0) / u.tope, 2)
                    ELSE 0 
                END as porcentaje_avance
            FROM referenciados r
            LEFT JOIN departamento d ON r.id_departamento = d.id_departamento
            LEFT JOIN municipio m ON r.id_municipio = m.id_municipio
            LEFT JOIN barrio b ON r.id_barrio = b.id_barrio
            LEFT JOIN grupo_poblacional gp ON r.id_grupo_poblacional = gp.id_grupo
            LEFT JOIN oferta_apoyo oa ON r.id_oferta_apoyo = oa.id_oferta
            LEFT JOIN zona z ON r.id_zona = z.id_zona
            LEFT JOIN sector s ON r.id_sector = s.id_sector
            LEFT JOIN puesto_votacion pv ON r.id_puesto_votacion = pv.id_puesto
            LEFT JOIN grupos_parlamentarios gr ON r.id_grupo = gr.id_grupo  -- NUEVO: join con tabla grupos
            LEFT JOIN usuario u ON r.id_referenciador = u.id_usuario
            ORDER BY r.fecha_registro DESC";
    
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Método para contar todos los referenciados
    public function countAllReferenciados() {
        $sql = "SELECT COUNT(*) as total FROM referenciados";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
    
    // Método para contar referenciados activos
    public function countReferenciadosActivos() {
        $sql = "SELECT COUNT(*) as activos FROM referenciados WHERE activo = true";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['activos'];
    }
    
    // Método para contar referenciados inactivos
    public function countReferenciadosInactivos() {
        $sql = "SELECT COUNT(*) as inactivos FROM referenciados WHERE activo = false";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['inactivos'];
    }
    
    public function desactivarReferenciado($id_referenciado) {
        $query = "UPDATE referenciados SET activo = false WHERE id_referenciado = ?";
        $stmt = $this->pdo->prepare($query);
        return $stmt->execute([$id_referenciado]);
    }
    
    /**
     * Reactivar un referenciado (cambiar estado a true/activo)
     */
    public function reactivarReferenciado($id_referenciado) {
        $query = "UPDATE referenciados SET activo = true WHERE id_referenciado = ?";
        $stmt = $this->pdo->prepare($query);
        return $stmt->execute([$id_referenciado]);
    }
    
    /**
     * Obtener estado de un referenciado
     */
    public function getEstadoReferenciado($id_referenciado) {
        $query = "SELECT activo FROM referenciados WHERE id_referenciado = ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$id_referenciado]);
        $result = $stmt->fetch();
        return $result ? $result['activo'] : null;
    }
    
    /**
     * Actualizar un referenciado existente
     */
public function actualizarReferenciado($id_referenciado, $data) {
    // **DEPURACIÓN INICIAL**
    error_log("=== actualizarReferenciado INICIADO ===");
    error_log("ID referenciado: $id_referenciado");
    error_log("Datos recibidos: " . json_encode($data));
    
    try {
        // Asegurar que afinidad sea válida (1-5)
        $afinidad = isset($data['afinidad']) ? max(1, min(5, intval($data['afinidad']))) : 1;
        error_log("Afinidad procesada: $afinidad");
        
        // Determinar valores según si vota fuera o no
        $votaFuera = $data['vota_fuera'] ?? 'No';
        error_log("Vota fuera: $votaFuera");
        
        if ($votaFuera === 'Si') {
            error_log("MODO: Vota fuera");
            // Cuando vota fuera
            $id_zona = null;
            $id_sector = null;
            $id_puesto_votacion = null;
            $mesa = null;
            $puesto_votacion_fuera = $data['puesto_votacion_fuera'] ?? '';
            $mesa_fuera = $data['mesa_fuera'] ?? null;
            
            // Validaciones simplificadas
            if (empty($puesto_votacion_fuera)) {
                throw new Exception('El puesto de votación fuera es requerido cuando vota fuera');
            }
        } else {
            error_log("MODO: No vota fuera");
            // Cuando NO vota fuera
            $id_zona = $data['id_zona'] ?? null;
            $id_sector = $data['id_sector'] ?? null;
            $id_puesto_votacion = $data['id_puesto_votacion'] ?? null;
            $mesa = $data['mesa'] ?? null;
            $puesto_votacion_fuera = null;
            $mesa_fuera = null;
            
            if (empty($id_zona)) {
                throw new Exception('La zona es requerida cuando no vota fuera');
            }
        }
        
        // **LÓGICA SIMPLIFICADA PARA 'activo'**
        error_log("=== Procesando lógica de 'activo' ===");
        
        // Por defecto: mantener activo = true
        $activo = true;
        
        // Solo si tenemos id_referenciador en los datos
        if (isset($data['id_referenciador'])) {
            error_log("id_referenciador recibido: " . $data['id_referenciador']);
            
            // 1. Obtener estado actual ANTES de cualquier cambio
            $sqlActual = "SELECT id_referenciador, activo FROM referenciados WHERE id_referenciado = :id";
            $stmtActual = $this->pdo->prepare($sqlActual);
            $stmtActual->bindValue(':id', $id_referenciado, PDO::PARAM_INT);
            $stmtActual->execute();
            $referenciadoActual = $stmtActual->fetch(PDO::FETCH_ASSOC);
            
            if ($referenciadoActual) {
                error_log("Estado actual encontrado: " . json_encode($referenciadoActual));
                
                $actualReferenciador = $referenciadoActual['id_referenciador'] ?? null;
                $nuevoReferenciador = $data['id_referenciador'];
                
                error_log("Referenciador actual: $actualReferenciador");
                error_log("Nuevo referenciador: $nuevoReferenciador");
                
                // Verificar si hay cambio
                if ($actualReferenciador != $nuevoReferenciador) {
                    error_log("¡HAY CAMBIO DE REFERENCIADOR!");
                    
                    // Si estaba INACTIVO → ACTIVAR
                    if (!$referenciadoActual['activo'] || 
                        $referenciadoActual['activo'] == 0 || 
                        $referenciadoActual['activo'] == 'f') {
                        error_log("Referenciado estaba INACTIVO → Se ACTIVA");
                        $activo = true;
                    } else {
                        error_log("Referenciado ya estaba ACTIVO → Mantiene activo");
                        $activo = true; // Siempre activo cuando hay cambio
                    }
                } else {
                    error_log("NO hay cambio de referenciador → Mantiene estado actual");
                    $activo = $referenciadoActual['activo'] ?? true;
                }
            } else {
                error_log("ADVERTENCIA: No se encontró el referenciado ID: $id_referenciado");
            }
        } else {
            error_log("id_referenciador NO recibido en datos");
        }
        
        error_log("Valor final de 'activo': " . ($activo ? 'true' : 'false'));
        
        // Procesar fechas nuevas
        $fechaNacimiento = null;
        if (!empty($data['fecha_nacimiento'])) {
            $fechaNacimiento = $data['fecha_nacimiento'];
            error_log("Fecha nacimiento: $fechaNacimiento");
        }
        
        $fechaCumplimiento = null;
        if (!empty($data['fecha_cumplimiento'])) {
            $fechaCumplimiento = $data['fecha_cumplimiento'];
            error_log("Fecha cumplimiento: $fechaCumplimiento");
        }
        
        // Procesar id_lider
        $idLider = null;
        if (!empty($data['id_lider'])) {
            $idLider = $data['id_lider'];
            error_log("ID Líder: $idLider");
        }
        
        // Iniciar transacción
        $this->pdo->beginTransaction();
        error_log("Transacción iniciada");
        
        // **SQL ACTUALIZADO: Incluye nuevos campos**
        $sql = "UPDATE referenciados SET
                nombre = :nombre,
                apellido = :apellido,
                cedula = :cedula,
                direccion = :direccion,
                email = :email,
                telefono = :telefono,
                afinidad = :afinidad,
                id_zona = :id_zona,
                id_sector = :id_sector,
                id_puesto_votacion = :id_puesto_votacion,
                mesa = :mesa,
                id_departamento = :id_departamento,
                id_municipio = :id_municipio,
                id_barrio = :id_barrio,
                id_oferta_apoyo = :id_oferta_apoyo,
                id_grupo_poblacional = :id_grupo_poblacional,
                compromiso = :compromiso,
                fecha_actualizacion = NOW(),
                sexo = :sexo,
                vota_fuera = :vota_fuera,
                puesto_votacion_fuera = :puesto_votacion_fuera,
                mesa_fuera = :mesa_fuera,
                id_grupo = :id_grupo,
                id_referenciador = :id_referenciador,
                activo = :activo,
                fecha_nacimiento = :fecha_nacimiento,
                fecha_cumplimiento = :fecha_cumplimiento,
                id_lider = :id_lider
                WHERE id_referenciado = :id_referenciado";
        
        error_log("SQL preparado");
        $stmt = $this->pdo->prepare($sql);
        
        // **Bind de valores con DEPURACIÓN**
        error_log("=== Bind de valores ===");
        
        // Campos básicos
        $stmt->bindValue(':nombre', $data['nombre'] ?? '');
        $stmt->bindValue(':apellido', $data['apellido'] ?? '');
        $stmt->bindValue(':cedula', $data['cedula'] ?? '');
        $stmt->bindValue(':direccion', $data['direccion'] ?? '');
        $stmt->bindValue(':email', $data['email'] ?? '');
        $stmt->bindValue(':telefono', $data['telefono'] ?? '');
        $stmt->bindValue(':afinidad', $afinidad, PDO::PARAM_INT);
        
        // Campos de votación
        $stmt->bindValue(':id_zona', $id_zona, PDO::PARAM_INT);
        $stmt->bindValue(':id_sector', $id_sector, PDO::PARAM_INT);
        $stmt->bindValue(':id_puesto_votacion', $id_puesto_votacion, PDO::PARAM_INT);
        $stmt->bindValue(':mesa', $mesa, PDO::PARAM_INT);
        
        // Campos de ubicación
        $stmt->bindValue(':id_departamento', $data['id_departamento'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':id_municipio', $data['id_municipio'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':id_barrio', $data['id_barrio'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':id_oferta_apoyo', $data['id_oferta_apoyo'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':id_grupo_poblacional', $data['id_grupo_poblacional'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':compromiso', $data['compromiso'] ?? '');
        
        // Campos personales
        $stmt->bindValue(':sexo', $data['sexo'] ?? null);
        $stmt->bindValue(':vota_fuera', $votaFuera);
        
        // Campos de votación fuera
        $stmt->bindValue(':puesto_votacion_fuera', $puesto_votacion_fuera);
        $stmt->bindValue(':mesa_fuera', $mesa_fuera, PDO::PARAM_INT);
        
        // Campo grupo parlamentario
        $stmt->bindValue(':id_grupo', $data['id_grupo'] ?? null, PDO::PARAM_INT);
        
        // Campo id_referenciador (IMPORTANTE)
        $idReferenciador = $data['id_referenciador'] ?? null;
        error_log("Bind id_referenciador: " . ($idReferenciador ?? 'NULL'));
        $stmt->bindValue(':id_referenciador', $idReferenciador, PDO::PARAM_INT);
        
        // Campo activo
        error_log("Bind activo: " . ($activo ? 'true' : 'false'));
        $stmt->bindValue(':activo', $activo, PDO::PARAM_BOOL);
        
        // **NUEVOS CAMPOS**
        // Fecha nacimiento
        error_log("Bind fecha_nacimiento: " . ($fechaNacimiento ?? 'NULL'));
        $stmt->bindValue(':fecha_nacimiento', $fechaNacimiento);
        
        // Fecha cumplimiento
        error_log("Bind fecha_cumplimiento: " . ($fechaCumplimiento ?? 'NULL'));
        $stmt->bindValue(':fecha_cumplimiento', $fechaCumplimiento);
        
        // ID Líder
        error_log("Bind id_lider: " . ($idLider ?? 'NULL'));
        $stmt->bindValue(':id_lider', $idLider, PDO::PARAM_INT);
        
        // ID del referenciado
        $stmt->bindValue(':id_referenciado', $id_referenciado, PDO::PARAM_INT);
        
        // **EJECUTAR**
        error_log("Ejecutando UPDATE...");
        $resultado = $stmt->execute();
        $filasAfectadas = $stmt->rowCount();
        
        error_log("Resultado execute: " . ($resultado ? 'true' : 'false'));
        error_log("Filas afectadas: $filasAfectadas");
        
        if ($filasAfectadas === 0) {
            error_log("ADVERTENCIA: No se actualizó ninguna fila. ¿Existe el ID $id_referenciado?");
        }
        
        // Si hay insumos nuevos, agregarlos
        if (!empty($data['insumos_nuevos'])) {
            error_log("Agregando " . count($data['insumos_nuevos']) . " insumos nuevos");
            $this->agregarInsumosReferenciado($id_referenciado, $data['insumos_nuevos'], $data);
        }
        
        // Si hay insumos a eliminar, removerlos
        if (!empty($data['insumos_eliminar'])) {
            error_log("Eliminando " . count($data['insumos_eliminar']) . " insumos");
            $this->eliminarInsumosReferenciado($id_referenciado, $data['insumos_eliminar']);
        }
        
        // Confirmar la transacción
        $this->pdo->commit();
        error_log("Transacción confirmada - ÉXITO");
        
        return true;
        
    } catch (Exception $e) {
        error_log("ERROR en actualizarReferenciado: " . $e->getMessage());
        error_log("Trace: " . $e->getTraceAsString());
        
        // Revertir la transacción en caso de error
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
            error_log("Transacción revertida");
        }
        
        throw $e;
    }
}
    
    /**
     * Agregar nuevos insumos al referenciado
     */
    private function agregarInsumosReferenciado($id_referenciado, $insumos_nuevos, $data) {
        $sql = "INSERT INTO referenciado_insumo (id_referenciado, id_insumo, cantidad, observaciones, fecha_registro) 
                VALUES (:id_referenciado, :id_insumo, :cantidad, :observaciones, NOW())";
        
        $stmt = $this->pdo->prepare($sql);
        
        foreach ($insumos_nuevos as $id_insumo) {
            $cantidad = isset($data["cantidad_$id_insumo"]) ? $data["cantidad_$id_insumo"] : 1;
            $observaciones = isset($data["observaciones_$id_insumo"]) ? $data["observaciones_$id_insumo"] : null;
            
            $stmt->bindValue(':id_referenciado', $id_referenciado, PDO::PARAM_INT);
            $stmt->bindValue(':id_insumo', $id_insumo, PDO::PARAM_INT);
            $stmt->bindValue(':cantidad', $cantidad, PDO::PARAM_INT);
            $stmt->bindValue(':observaciones', $observaciones);
            $stmt->execute();
        }
    }
    
    /**
     * Eliminar insumos del referenciado
     */
    private function eliminarInsumosReferenciado($id_referenciado, $insumos_eliminar) {
        $placeholders = implode(',', array_fill(0, count($insumos_eliminar), '?'));
        $sql = "DELETE FROM referenciado_insumo WHERE id_referenciado = ? AND id_insumo IN ($placeholders)";
        
        $stmt = $this->pdo->prepare($sql);
        
        // El primer parámetro es el id_referenciado
        $params = array_merge([$id_referenciado], $insumos_eliminar);
        $stmt->execute($params);
    }
    
    /**
     * Método para obtener estadísticas de votación
     */
    public function getEstadisticasVotacion() {
        $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN vota_fuera = 'Si' THEN 1 ELSE 0 END) as total_vota_fuera,
                SUM(CASE WHEN vota_fuera = 'No' THEN 1 ELSE 0 END) as total_vota_aqui,
                ROUND(AVG(CASE WHEN vota_fuera = 'Si' THEN mesa_fuera ELSE mesa END)::numeric, 1) as promedio_mesas
                FROM referenciados
                WHERE activo = true";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener todos los grupos disponibles
     */
    public function getGrupos() {
        $sql = "SELECT * FROM grupos_parlamentarios ORDER BY nombre";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener un grupo específico por ID
     */
    public function getGrupoById($id_grupo) {
        $sql = "SELECT * FROM grupos_parlamentarios WHERE id_grupo = :id_grupo";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id_grupo', $id_grupo, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener estadísticas por grupo
     */
    public function getEstadisticasPorGrupo() {
        $sql = "SELECT 
                g.id_grupo,
                g.nombre as grupo_nombre,
                COUNT(r.id_referenciado) as total_referenciados,
                COUNT(CASE WHEN r.activo = true THEN 1 END) as activos,
                COUNT(CASE WHEN r.vota_fuera = 'Si' THEN 1 END) as vota_fuera,
                ROUND(AVG(r.afinidad)::numeric, 2) as afinidad_promedio
                FROM grupos_parlamentarios g
                LEFT JOIN referenciados r ON g.id_grupo = r.id_grupo
                GROUP BY g.id_grupo, g.nombre
                ORDER BY g.nombre";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // En ReferenciadoModel.php
public function getReferenciadosPorFecha($fecha) {
    $sql = "SELECT r.*, 
                   u.nombres as referenciador_nombre,
                   u.apellidos as referenciador_apellido,
                   z.nombre as zona_nombre,
                   s.nombre as sector_nombre
            FROM referenciados r
            JOIN usuario u ON r.id_referenciador = u.id_usuario
            LEFT JOIN zona z ON r.id_zona = z.id_zona
            LEFT JOIN sector s ON r.id_sector = s.id_sector
            WHERE DATE(r.fecha_registro) = :fecha
            AND r.activo = true
            ORDER BY r.fecha_registro DESC";
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute(['fecha' => $fecha]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
public function getComparativaPorFechas($fecha_inicio, $fecha_fin) {
    $sql = "SELECT 
                DATE(r.fecha_registro) as fecha,
                COUNT(*) as total,
                SUM(CASE WHEN r.afinidad = 'camara' THEN 1 ELSE 0 END) as camara,
                SUM(CASE WHEN r.afinidad = 'senado' THEN 1 ELSE 0 END) as senado,
                COUNT(DISTINCT r.id_referenciador) as referenciadores_activos,
                COUNT(DISTINCT r.id_zona) as zonas_activas,
                COUNT(DISTINCT r.id_sector) as sectores_activos
            FROM referenciados r
            WHERE DATE(r.fecha_registro) BETWEEN :fecha_inicio AND :fecha_fin
            GROUP BY DATE(r.fecha_registro)
            ORDER BY fecha";
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([
        'fecha_inicio' => $fecha_inicio,
        'fecha_fin' => $fecha_fin
    ]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Método adicional para obtener estadísticas detalladas por fecha
public function getEstadisticasPorFecha($fecha, $tipoEleccion = null, $idZona = null) {
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN r.afinidad >= 3 THEN 1 ELSE 0 END) as alta_afinidad,
                SUM(CASE WHEN r.afinidad <= 2 THEN 1 ELSE 0 END) as baja_afinidad,
                COUNT(DISTINCT r.id_referenciador) as referenciadores_activos,
                COUNT(DISTINCT r.id_zona) as zonas_activas,
                COUNT(DISTINCT r.id_sector) as sectores_activos,
                COUNT(DISTINCT r.id_puesto_votacion) as puestos_activos,
                
                -- Distribución por grupos parlamentarios (Cámara/Senado/Ambos)
                SUM(CASE WHEN r.id_grupo = 1 THEN 1 ELSE 0 END) as camara,
                SUM(CASE WHEN r.id_grupo = 2 THEN 1 ELSE 0 END) as senado,
                SUM(CASE WHEN r.id_grupo = 3 THEN 1 ELSE 0 END) as ambos,
                
                -- Para el KPI de Distribución: Cámara + Ambos / Senado + Ambos
                (SUM(CASE WHEN r.id_grupo = 1 THEN 1 ELSE 0 END) + 
                 SUM(CASE WHEN r.id_grupo = 3 THEN 1 ELSE 0 END)) as total_camara_con_ambos,
                (SUM(CASE WHEN r.id_grupo = 2 THEN 1 ELSE 0 END) + 
                 SUM(CASE WHEN r.id_grupo = 3 THEN 1 ELSE 0 END)) as total_senado_con_ambos,
                
                -- CORREGIR: compromiso es texto, verificar varios valores posibles
                SUM(CASE WHEN TRIM(r.compromiso) = 'Si' THEN 1 
                         WHEN TRIM(r.compromiso) = 'SI' THEN 1
                         WHEN TRIM(r.compromiso) = 'si' THEN 1
                         WHEN TRIM(r.compromiso) = '1' THEN 1
                         WHEN TRIM(r.compromiso) = 'true' THEN 1
                         WHEN TRIM(r.compromiso) = 'TRUE' THEN 1
                         WHEN r.compromiso IS NOT NULL AND TRIM(r.compromiso) != '' THEN 1
                         ELSE 0 
                    END) as con_compromiso,
                
                -- CORREGIR: vota_fuera también es texto 'Si'/'No'
                SUM(CASE WHEN TRIM(r.vota_fuera) = 'Si' THEN 1 
                         WHEN TRIM(r.vota_fuera) = 'SI' THEN 1
                         WHEN TRIM(r.vota_fuera) = 'si' THEN 1
                         WHEN TRIM(r.vota_fuera) = '1' THEN 1
                         WHEN TRIM(r.vota_fuera) = 'true' THEN 1
                         ELSE 0 
                    END) as vota_fuera,
                
                ROUND(AVG(r.afinidad)::numeric, 2) as afinidad_promedio
            FROM referenciados r
            INNER JOIN usuario u ON r.id_referenciador = u.id_usuario
            WHERE DATE(r.fecha_registro) = :fecha
            AND r.activo = true
            AND u.tipo_usuario = 'Referenciador'
            AND u.activo = true";
    
    $params = [':fecha' => $fecha];
    
    // Agregar filtros si se proporcionan
    if ($tipoEleccion && $tipoEleccion !== 'todos') {
        if ($tipoEleccion === 'camara') {
            $sql .= " AND (r.id_grupo = 1 OR r.id_grupo = 3)";
        } elseif ($tipoEleccion === 'senado') {
            $sql .= " AND (r.id_grupo = 2 OR r.id_grupo = 3)";
        }
    }
    
    if ($idZona && $idZona !== 'todas') {
        $sql .= " AND r.id_zona = :id_zona";
        $params[':id_zona'] = $idZona;
    }
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Método para obtener referenciados por hora
public function getReferenciadosPorHora($fecha, $tipoEleccion = null, $idZona = null) {
    $sql = "SELECT 
                EXTRACT(HOUR FROM r.fecha_registro) as hora,
                COUNT(*) as cantidad
            FROM referenciados r
            INNER JOIN usuario u ON r.id_referenciador = u.id_usuario
            WHERE DATE(r.fecha_registro) = :fecha
            AND r.activo = true
            AND u.tipo_usuario = 'Referenciador'
            AND u.activo = true";
    
    $params = [':fecha' => $fecha];
    
    // Agregar filtros
    if ($tipoEleccion && $tipoEleccion !== 'todos') {
        if ($tipoEleccion === 'camara') {
            $sql .= " AND (r.id_grupo = 1 OR r.id_grupo = 3)";
        } elseif ($tipoEleccion === 'senado') {
            $sql .= " AND (r.id_grupo = 2 OR r.id_grupo = 3)";
        }
    }
    
    if ($idZona && $idZona !== 'todas') {
        $sql .= " AND r.id_zona = :id_zona";
        $params[':id_zona'] = $idZona;
    }
    
    $sql .= " GROUP BY EXTRACT(HOUR FROM r.fecha_registro)
              ORDER BY hora";
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Método para obtener top referenciadores por fecha
public function getTopReferenciadoresPorFecha($fecha, $limite = 10, $tipoEleccion = null, $idZona = null) {
    $sql = "SELECT 
                u.id_usuario,
                CONCAT(u.nombres, ' ', u.apellidos) as nombre,
                COUNT(r.id_referenciado) as cantidad,
                u.id_zona,
                z.nombre as zona_nombre
            FROM referenciados r
            JOIN usuario u ON r.id_referenciador = u.id_usuario
            LEFT JOIN zona z ON u.id_zona = z.id_zona
            WHERE DATE(r.fecha_registro) = :fecha
            AND r.activo = true
            AND u.tipo_usuario = 'Referenciador'
            AND u.activo = true";
    
    $params = [':fecha' => $fecha];
    
    // Agregar filtros
    if ($tipoEleccion && $tipoEleccion !== 'todos') {
        if ($tipoEleccion === 'camara') {
            $sql .= " AND (r.id_grupo = 1 OR r.id_grupo = 3)";
        } elseif ($tipoEleccion === 'senado') {
            $sql .= " AND (r.id_grupo = 2 OR r.id_grupo = 3)";
        }
    }
    
    if ($idZona && $idZona !== 'todas') {
        $sql .= " AND r.id_zona = :id_zona";
        $params[':id_zona'] = $idZona;
    }
    
    $sql .= " GROUP BY u.id_usuario, u.nombres, u.apellidos, u.id_zona, z.nombre
              ORDER BY cantidad DESC
              LIMIT :limite";
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->bindValue(':fecha', $fecha);
    $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
    
    if ($idZona && $idZona !== 'todas') {
        $stmt->bindValue(':id_zona', $idZona);
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Método para obtener distribución por zona
public function getDistribucionPorZona($fecha, $tipoEleccion = null, $idZona = null) {
    $sql = "SELECT 
                z.id_zona,
                z.nombre as zona,
                COUNT(r.id_referenciado) as cantidad,
                -- Distribución por grupo parlamentario por zona
                SUM(CASE WHEN r.id_grupo = 1 THEN 1 ELSE 0 END) as camara,
                SUM(CASE WHEN r.id_grupo = 2 THEN 1 ELSE 0 END) as senado,
                SUM(CASE WHEN r.id_grupo = 3 THEN 1 ELSE 0 END) as ambos
            FROM referenciados r
            INNER JOIN usuario u ON r.id_referenciador = u.id_usuario
            JOIN zona z ON r.id_zona = z.id_zona
            WHERE DATE(r.fecha_registro) = :fecha
            AND r.activo = true
            AND u.tipo_usuario = 'Referenciador'
            AND u.activo = true";
    
    $params = [':fecha' => $fecha];
    
    // Agregar filtros
    if ($tipoEleccion && $tipoEleccion !== 'todos') {
        if ($tipoEleccion === 'camara') {
            $sql .= " AND (r.id_grupo = 1 OR r.id_grupo = 3)";
        } elseif ($tipoEleccion === 'senado') {
            $sql .= " AND (r.id_grupo = 2 OR r.id_grupo = 3)";
        }
    }
    
    if ($idZona && $idZona !== 'todas') {
        $sql .= " AND r.id_zona = :id_zona";
        $params[':id_zona'] = $idZona;
    }
    
    $sql .= " GROUP BY z.id_zona, z.nombre
              ORDER BY cantidad DESC";
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Método para obtener detalle de referenciados por fecha
public function getDetalleReferenciadosPorFecha($fecha, $limite = 1000) {
    $sql = "SELECT 
                r.id_referenciado,
                r.nombre,
                r.apellido,
                r.cedula,
                r.telefono,
                r.afinidad,
                r.compromiso,  -- Esto es texto 'Si'/'No' o similar
                r.vota_fuera,  -- Esto es texto 'Si'/'No'
                r.fecha_registro,
                CONCAT(u.nombres, ' ', u.apellidos) as referenciador_nombre,
                z.nombre as zona_nombre,
                s.nombre as sector_nombre,
                pv.nombre as puesto_votacion_nombre
            FROM referenciados r
            JOIN usuario u ON r.id_referenciador = u.id_usuario
            LEFT JOIN zona z ON r.id_zona = z.id_zona
            LEFT JOIN sector s ON r.id_sector = s.id_sector
            LEFT JOIN puesto_votacion pv ON r.id_puesto_votacion = pv.id_puesto
            WHERE DATE(r.fecha_registro) = :fecha
            AND r.activo = true
            ORDER BY r.fecha_registro DESC
            LIMIT :limite";
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->bindValue(':fecha', $fecha);
    $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
/**
 * Obtener fecha de registro de un referenciado por cédula
 */
public function getFechaRegistroByCedula($cedula) {
    try {
        $sql = "SELECT fecha_registro FROM referenciados 
                WHERE cedula = ? 
                ORDER BY fecha_registro DESC 
                LIMIT 1";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$cedula]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($resultado && !empty($resultado['fecha_registro'])) {
            // La fecha ya está en la zona horaria correcta en PostgreSQL
            // Solo la formateamos
            $fecha = new DateTime($resultado['fecha_registro']);
            return $fecha->format('d/m/Y H:i:s');
        }
        
        return null;
    } catch (Exception $e) {
        error_log("Error en getFechaRegistroByCedula: " . $e->getMessage());
        return null;
    }
}
// Obtener total de referenciados trackeados (con al menos una llamada)
public function getTotalTrackeados() {
    $sql = "SELECT COUNT(DISTINCT lt.id_referenciado) as total_trackeados
            FROM llamadas_tracking lt
            INNER JOIN referenciados r ON lt.id_referenciado = r.id_referenciado
            WHERE r.activo = true";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total_trackeados'] ?? 0;
}

// Obtener total de votos por tipo (Cámara, Senado, Ambos)
public function getTotalPorTipoEleccion() {
    $sql = "SELECT 
                COUNT(CASE WHEN id_grupo = 1 THEN 1 END) as total_camara,
                COUNT(CASE WHEN id_grupo = 2 THEN 1 END) as total_senado,
                COUNT(CASE WHEN id_grupo = 3 THEN 1 END) as total_ambos
            FROM referenciados 
            WHERE activo = true";
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return [
        'camara' => $result['total_camara'] ?? 0,
        'senado' => $result['total_senado'] ?? 0,
        'ambos' => $result['total_ambos'] ?? 0,
        'total_camara_con_ambos' => ($result['total_camara'] ?? 0) + ($result['total_ambos'] ?? 0),
        'total_senado_con_ambos' => ($result['total_senado'] ?? 0) + ($result['total_ambos'] ?? 0)
    ];
}
/**
 * Calcular porcentaje de afinidad promedio (convertir escala 1-5 a porcentaje 0-100%)
 */
public function getPorcentajeAfinidadPromedio() {
    $sql = "SELECT 
                ROUND((AVG(afinidad) - 1) * 25, 2) as porcentaje_afinidad
            FROM referenciados 
            WHERE activo = true 
            AND afinidad IS NOT NULL";
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['porcentaje_afinidad'] ?? 0;
}
/**
 * Obtener estadísticas de votación para un referenciador
 * 
 * @param int $id_referenciador ID del referenciador
 * @return array Conteo de votantes
 */
public function getEstadisticasVotacionPorReferenciador($id_referenciador) {
    $sql = "SELECT 
                COUNT(CASE WHEN vota_fuera = 'Si' OR vota_fuera = 'SI' OR vota_fuera = 'si' THEN 1 END) as vota_fuera,
                COUNT(CASE WHEN vota_fuera = 'No' OR vota_fuera = 'NO' OR vota_fuera = 'no' THEN 1 END) as vota_aqui,
                COUNT(*) as total
            FROM referenciados 
            WHERE id_referenciador = :id_referenciador 
            AND activo = true";
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->bindValue(':id_referenciador', $id_referenciador, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
/**
 * Cuenta votos desglosados por Cámara, Senado y PACHA
 * 
 * @param int $id_referenciador ID del referenciador
 * @return array [solo_camara, solo_senado, pacha, total_camara, total_senado, total_activos]
 */
public function contarVotosDesglosados($id_referenciador) {
    $sql = "SELECT 
                COUNT(CASE WHEN id_grupo = 1 THEN 1 END) as solo_camara,
                COUNT(CASE WHEN id_grupo = 2 THEN 1 END) as solo_senado,
                COUNT(CASE WHEN id_grupo = 3 THEN 1 END) as pacha,
                COUNT(CASE WHEN id_grupo IN (1, 3) THEN 1 END) as total_camara,
                COUNT(CASE WHEN id_grupo IN (2, 3) THEN 1 END) as total_senado,
                COUNT(*) as total_activos
            FROM referenciados 
            WHERE id_referenciador = :id_referenciador 
            AND activo = true";
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->bindValue(':id_referenciador', $id_referenciador, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
/**
 * Contar referidos por líder
 */
public function countByLider($id_lider) {
    try {
        $query = "SELECT COUNT(*) as cantidad 
                  FROM public.referenciados 
                  WHERE id_lider = :id_lider 
                  AND activo = true";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':id_lider' => $id_lider]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['cantidad'] ?? 0;
        
    } catch (PDOException $e) {
        error_log("Error en countByLider ReferenciadoModel: " . $e->getMessage());
        return 0;
    }
}
// Método optimizado con paginación CORREGIDO
public function getReferenciadosPaginados($page = 1, $perPage = 50, $filters = []) {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT r.*, 
                d.nombre as departamento_nombre,
                m.nombre as municipio_nombre,
                b.nombre as barrio_nombre,
                gp.nombre as grupo_poblacional_nombre,
                oa.nombre as oferta_apoyo_nombre,
                z.nombre as zona_nombre,
                s.nombre as sector_nombre,
                pv.nombre as puesto_votacion_nombre,
                gr.nombre as grupo_nombre,
                CONCAT(u.nombres, ' ', u.apellidos) as referenciador_nombre,
                l.id_lider,
                l.nombres as lider_nombres,
                l.apellidos as lider_apellidos,
                CONCAT(l.nombres, ' ', l.apellidos) as lider_nombre
                FROM referenciados r
                LEFT JOIN departamento d ON r.id_departamento = d.id_departamento
                LEFT JOIN municipio m ON r.id_municipio = m.id_municipio
                LEFT JOIN barrio b ON r.id_barrio = b.id_barrio
                LEFT JOIN grupo_poblacional gp ON r.id_grupo_poblacional = gp.id_grupo
                LEFT JOIN oferta_apoyo oa ON r.id_oferta_apoyo = oa.id_oferta
                LEFT JOIN zona z ON r.id_zona = z.id_zona
                LEFT JOIN sector s ON r.id_sector = s.id_sector
                LEFT JOIN puesto_votacion pv ON r.id_puesto_votacion = pv.id_puesto
                LEFT JOIN grupos_parlamentarios gr ON r.id_grupo = gr.id_grupo
                LEFT JOIN usuario u ON r.id_referenciador = u.id_usuario
                LEFT JOIN lideres l ON r.id_lider = l.id_lider";
        
        $conditions = [];
        $params = [];
        $paramTypes = [];
        
        // Búsqueda global en múltiples campos
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            
            // Verificar si la búsqueda es numérica
            $isNumericSearch = is_numeric($search);
            
            if ($isNumericSearch) {
                // Si es numérico, buscar en campos numéricos con comparación exacta
                $conditionParts = [];
                
                // Campos exactos
                $conditionParts[] = "r.cedula = ?";
                $conditionParts[] = "r.telefono ILIKE ?";
                
                // Solo agregar r.mesa si la tabla tiene ese campo
                if (isset($filters['include_mesa'])) {
                    $conditionParts[] = "CAST(r.mesa AS TEXT) = ?";
                }
                
                // Campos de texto con ILIKE
                $conditionParts[] = "r.nombre ILIKE ?";
                $conditionParts[] = "r.apellido ILIKE ?";
                $conditionParts[] = "r.direccion ILIKE ?";
                $conditionParts[] = "r.email ILIKE ?";
                $conditionParts[] = "d.nombre ILIKE ?";
                $conditionParts[] = "m.nombre ILIKE ?";
                $conditionParts[] = "b.nombre ILIKE ?";
                $conditionParts[] = "gp.nombre ILIKE ?";
                $conditionParts[] = "oa.nombre ILIKE ?";
                $conditionParts[] = "z.nombre ILIKE ?";
                $conditionParts[] = "s.nombre ILIKE ?";
                $conditionParts[] = "pv.nombre ILIKE ?";
                $conditionParts[] = "gr.nombre ILIKE ?";
                $conditionParts[] = "u.nombres ILIKE ?";
                $conditionParts[] = "u.apellidos ILIKE ?";
                $conditionParts[] = "l.nombres ILIKE ?";
                $conditionParts[] = "l.apellidos ILIKE ?";
                
                $conditions[] = "(" . implode(" OR ", $conditionParts) . ")";
                
                // Parámetros para campos exactos
                $params[] = $search; // Para cedula exacta
                $params[] = '%' . $search . '%'; // Para teléfono (con ILIKE)
                $paramTypes[] = \PDO::PARAM_STR;
                $paramTypes[] = \PDO::PARAM_STR;
                
                // Parámetro para mesa si existe
                if (isset($filters['include_mesa'])) {
                    $params[] = $search; // Para mesa
                    $paramTypes[] = \PDO::PARAM_STR;
                }
                
                // Parámetros para campos de texto
                $totalCamposTexto = isset($filters['include_mesa']) ? 17 : 16; // 15 originales + 2 de líder
                for ($i = 0; $i < $totalCamposTexto; $i++) {
                    $params[] = '%' . $search . '%';
                    $paramTypes[] = \PDO::PARAM_STR;
                }
                
            } else {
                // Si es texto, buscar normalmente
                $conditions[] = "(r.nombre ILIKE ? OR r.apellido ILIKE ? OR r.cedula ILIKE ? 
                                 OR r.direccion ILIKE ? OR r.email ILIKE ? OR r.telefono ILIKE ?
                                 OR d.nombre ILIKE ? OR m.nombre ILIKE ? OR b.nombre ILIKE ?
                                 OR gp.nombre ILIKE ? OR oa.nombre ILIKE ? OR z.nombre ILIKE ?
                                 OR s.nombre ILIKE ? OR pv.nombre ILIKE ? OR gr.nombre ILIKE ?
                                 OR u.nombres ILIKE ? OR u.apellidos ILIKE ?
                                 /* ✅ BÚSQUEDA EN CAMPOS DE LÍDERES - SOLO nombres y apellidos */
                                 OR l.nombres ILIKE ? OR l.apellidos ILIKE ?)";
                
                // 17 campos originales + 2 de líder = 19 parámetros
                for ($i = 0; $i < 19; $i++) {
                    $params[] = '%' . $search . '%';
                    $paramTypes[] = \PDO::PARAM_STR;
                }
            }
        }
        
        // Filtro por estado activo/inactivo
        if (isset($filters['activo']) && $filters['activo'] !== '') {
            $conditions[] = "r.activo = ?";
            
            // Convertir a booleano PostgreSQL
            if ($filters['activo'] === '1' || $filters['activo'] === true || $filters['activo'] === 't') {
                $params[] = true;
            } else {
                $params[] = false;
            }
            $paramTypes[] = \PDO::PARAM_BOOL;
        }
        
        // Filtros avanzados
        if (!empty($filters['departamento'])) {
            $conditions[] = "r.id_departamento = ?";
            $params[] = $filters['departamento'];
            $paramTypes[] = \PDO::PARAM_INT;
        }
        
        if (!empty($filters['municipio'])) {
            $conditions[] = "r.id_municipio = ?";
            $params[] = $filters['municipio'];
            $paramTypes[] = \PDO::PARAM_INT;
        }
        
        if (!empty($filters['zona'])) {
            $conditions[] = "r.id_zona = ?";
            $params[] = $filters['zona'];
            $paramTypes[] = \PDO::PARAM_INT;
        }
        
        if (!empty($filters['referenciador'])) {
            $conditions[] = "r.id_referenciador = ?";
            $params[] = $filters['referenciador'];
            $paramTypes[] = \PDO::PARAM_INT;
        }
        
        if (!empty($filters['oferta_apoyo'])) {
            $conditions[] = "r.id_oferta_apoyo = ?";
            $params[] = $filters['oferta_apoyo'];
            $paramTypes[] = \PDO::PARAM_INT;
        }
        
        if (!empty($filters['grupo_poblacional'])) {
            $conditions[] = "r.id_grupo_poblacional = ?";
            $params[] = $filters['grupo_poblacional'];
            $paramTypes[] = \PDO::PARAM_INT;
        }
        
        if (!empty($filters['grupo_parlamentario'])) {
            $conditions[] = "r.id_grupo = ?";
            $params[] = $filters['grupo_parlamentario'];
            $paramTypes[] = \PDO::PARAM_INT;
        }

        if (!empty($filters['lider'])) {
            $conditions[] = "r.id_lider = ?";
            $params[] = $filters['lider'];
            $paramTypes[] = \PDO::PARAM_INT;
        }
        
        // Construir WHERE
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        
        $sql .= " ORDER BY r.fecha_registro DESC LIMIT ? OFFSET ?";
        
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
            error_log("Error en getReferenciadosPaginados: " . $e->getMessage());
            error_log("SQL: " . $sql);
            error_log("Params: " . print_r($params, true));
            return [];
        }
    }

// Método para contar total (separado, más eficiente) CORREGIDO
 public function getTotalReferenciados($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM referenciados r";
        
        $conditions = [];
        $params = [];
        $paramTypes = [];
        
        // Solo necesitamos joins si hay búsqueda en campos relacionados
        if (!empty($filters['search'])) {
            $sql .= " LEFT JOIN departamento d ON r.id_departamento = d.id_departamento
                      LEFT JOIN municipio m ON r.id_municipio = m.id_municipio
                      LEFT JOIN barrio b ON r.id_barrio = b.id_barrio
                      LEFT JOIN grupo_poblacional gp ON r.id_grupo_poblacional = gp.id_grupo
                      LEFT JOIN oferta_apoyo oa ON r.id_oferta_apoyo = oa.id_oferta
                      LEFT JOIN zona z ON r.id_zona = z.id_zona
                      LEFT JOIN sector s ON r.id_sector = s.id_sector
                      LEFT JOIN puesto_votacion pv ON r.id_puesto_votacion = pv.id_puesto
                      LEFT JOIN grupos_parlamentarios gr ON r.id_grupo = gr.id_grupo
                      LEFT JOIN usuario u ON r.id_referenciador = u.id_usuario
                      LEFT JOIN lideres l ON r.id_lider = l.id_lider";
        }
        
        // Búsqueda global
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            
            // Verificar si la búsqueda es numérica
            $isNumericSearch = is_numeric($search);
            
            if ($isNumericSearch) {
                // Si es numérico, buscar en campos numéricos con comparación exacta
                $conditionParts = [];
                
                // Campos exactos
                $conditionParts[] = "r.cedula = ?";
                $conditionParts[] = "r.telefono ILIKE ?";
                
                // Solo agregar r.mesa si la tabla tiene ese campo
                if (isset($filters['include_mesa'])) {
                    $conditionParts[] = "CAST(r.mesa AS TEXT) = ?";
                }
                
                // Campos de texto con ILIKE
                $conditionParts[] = "r.nombre ILIKE ?";
                $conditionParts[] = "r.apellido ILIKE ?";
                $conditionParts[] = "r.direccion ILIKE ?";
                $conditionParts[] = "r.email ILIKE ?";
                $conditionParts[] = "d.nombre ILIKE ?";
                $conditionParts[] = "m.nombre ILIKE ?";
                $conditionParts[] = "b.nombre ILIKE ?";
                $conditionParts[] = "gp.nombre ILIKE ?";
                $conditionParts[] = "oa.nombre ILIKE ?";
                $conditionParts[] = "z.nombre ILIKE ?";
                $conditionParts[] = "s.nombre ILIKE ?";
                $conditionParts[] = "pv.nombre ILIKE ?";
                $conditionParts[] = "gr.nombre ILIKE ?";
                $conditionParts[] = "u.nombres ILIKE ?";
                $conditionParts[] = "u.apellidos ILIKE ?";
                $conditionParts[] = "l.nombres ILIKE ?";
                $conditionParts[] = "l.apellidos ILIKE ?";
                
                $conditions[] = "(" . implode(" OR ", $conditionParts) . ")";
                
                // Parámetros para campos exactos
                $params[] = $search; // Para cedula exacta
                $params[] = '%' . $search . '%'; // Para teléfono (con ILIKE)
                $paramTypes[] = \PDO::PARAM_STR;
                $paramTypes[] = \PDO::PARAM_STR;
                
                // Parámetro para mesa si existe
                if (isset($filters['include_mesa'])) {
                    $params[] = $search; // Para mesa
                    $paramTypes[] = \PDO::PARAM_STR;
                }
                
                // Parámetros para campos de texto
                $totalCamposTexto = isset($filters['include_mesa']) ? 17 : 16;
                for ($i = 0; $i < $totalCamposTexto; $i++) {
                    $params[] = '%' . $search . '%';
                    $paramTypes[] = \PDO::PARAM_STR;
                }
                
            } else {
                // Si es texto, buscar normalmente
                $conditions[] = "(r.nombre ILIKE ? OR r.apellido ILIKE ? OR r.cedula ILIKE ? 
                                 OR r.direccion ILIKE ? OR r.email ILIKE ? OR r.telefono ILIKE ?
                                 OR d.nombre ILIKE ? OR m.nombre ILIKE ? OR b.nombre ILIKE ?
                                 OR gp.nombre ILIKE ? OR oa.nombre ILIKE ? OR z.nombre ILIKE ?
                                 OR s.nombre ILIKE ? OR pv.nombre ILIKE ? OR gr.nombre ILIKE ?
                                 OR u.nombres ILIKE ? OR u.apellidos ILIKE ?
                                 OR l.nombres ILIKE ? OR l.apellidos ILIKE ?)";
                
                // 17 campos originales + 2 de líder = 19 parámetros
                for ($i = 0; $i < 19; $i++) {
                    $params[] = '%' . $search . '%';
                    $paramTypes[] = \PDO::PARAM_STR;
                }
            }
        }
        
        // Filtro por estado
        if (isset($filters['activo']) && $filters['activo'] !== '') {
            $conditions[] = "r.activo = ?";
            
            if ($filters['activo'] === '1' || $filters['activo'] === true || $filters['activo'] === 't') {
                $params[] = true;
            } else {
                $params[] = false;
            }
            $paramTypes[] = \PDO::PARAM_BOOL;
        }
        
        // Filtros avanzados
        if (!empty($filters['departamento'])) {
            $conditions[] = "r.id_departamento = ?";
            $params[] = $filters['departamento'];
            $paramTypes[] = \PDO::PARAM_INT;
        }
        
        if (!empty($filters['municipio'])) {
            $conditions[] = "r.id_municipio = ?";
            $params[] = $filters['municipio'];
            $paramTypes[] = \PDO::PARAM_INT;
        }
        
        if (!empty($filters['zona'])) {
            $conditions[] = "r.id_zona = ?";
            $params[] = $filters['zona'];
            $paramTypes[] = \PDO::PARAM_INT;
        }
        
        if (!empty($filters['referenciador'])) {
            $conditions[] = "r.id_referenciador = ?";
            $params[] = $filters['referenciador'];
            $paramTypes[] = \PDO::PARAM_INT;
        }
        
        if (!empty($filters['oferta_apoyo'])) {
            $conditions[] = "r.id_oferta_apoyo = ?";
            $params[] = $filters['oferta_apoyo'];
            $paramTypes[] = \PDO::PARAM_INT;
        }
        
        if (!empty($filters['grupo_poblacional'])) {
            $conditions[] = "r.id_grupo_poblacional = ?";
            $params[] = $filters['grupo_poblacional'];
            $paramTypes[] = \PDO::PARAM_INT;
        }
        
        if (!empty($filters['grupo_parlamentario'])) {
            $conditions[] = "r.id_grupo = ?";
            $params[] = $filters['grupo_parlamentario'];
            $paramTypes[] = \PDO::PARAM_INT;
        }
        if (!empty($filters['lider'])) {
            $conditions[] = "r.id_lider = ?";
            $params[] = $filters['lider'];
            $paramTypes[] = \PDO::PARAM_INT;
        }
        
        // Construir WHERE
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
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
            error_log("Error en getTotalReferenciados: " . $e->getMessage());
            error_log("SQL: " . $sql);
            error_log("Params: " . print_r($params, true));
            return 0;
        }
    }
/**
 * Obtener referenciados con filtros aplicados (basado en getAllReferenciados)
 * @param array $filtros Filtros a aplicar
 * @return array Lista de referenciados filtrados
 */
public function getReferenciadosFiltrados($filtros = []) {
    try {
        // Asegurar conexión
        if ($this->pdo === null) {
            require_once __DIR__ . '/../config/database.php';
            $this->pdo = Database::getConnection();
        }
        
        // Consulta base (la misma de getAllReferenciados)
        $sql = "SELECT r.*, 
                d.nombre as departamento_nombre,
                m.nombre as municipio_nombre,
                b.nombre as barrio_nombre,
                gp.nombre as grupo_poblacional_nombre,
                oa.nombre as oferta_apoyo_nombre,
                z.nombre as zona_nombre,
                s.nombre as sector_nombre,
                pv.nombre as puesto_votacion_nombre,
                gr.nombre as grupo_nombre,
                -- INFORMACIÓN DEL LÍDER
                l.nombres as lider_nombres,
                l.apellidos as lider_apellidos,
                l.cc as lider_cedula,
                l.telefono as lider_telefono,
                CONCAT(l.nombres, ' ', l.apellidos) as lider_nombre_completo,
                -- Información del referenciador
                CONCAT(u.nombres, ' ', u.apellidos) as referenciador_nombre,
                -- Campos calculados
                CASE 
                    WHEN r.vota_fuera = 'Si' THEN r.puesto_votacion_fuera
                    ELSE pv.nombre
                END as puesto_votacion_display,
                CASE 
                    WHEN r.vota_fuera = 'Si' THEN r.mesa_fuera
                    ELSE r.mesa
                END as mesa_display
                FROM referenciados r
                LEFT JOIN departamento d ON r.id_departamento = d.id_departamento
                LEFT JOIN municipio m ON r.id_municipio = m.id_municipio
                LEFT JOIN barrio b ON r.id_barrio = b.id_barrio
                LEFT JOIN grupo_poblacional gp ON r.id_grupo_poblacional = gp.id_grupo
                LEFT JOIN oferta_apoyo oa ON r.id_oferta_apoyo = oa.id_oferta
                LEFT JOIN zona z ON r.id_zona = z.id_zona
                LEFT JOIN sector s ON r.id_sector = s.id_sector
                LEFT JOIN puesto_votacion pv ON r.id_puesto_votacion = pv.id_puesto
                LEFT JOIN grupos_parlamentarios gr ON r.id_grupo = gr.id_grupo
                LEFT JOIN lideres l ON r.id_lider = l.id_lider
                LEFT JOIN usuario u ON r.id_referenciador = u.id_usuario
                WHERE 1=1";
        
        $params = [];
        
        // FILTRO DE BÚSQUEDA GENERAL
        if (!empty($filtros['search'])) {
            $sql .= " AND (r.nombre ILIKE ? 
                        OR r.apellido ILIKE ? 
                        OR r.cedula ILIKE ? 
                        OR r.telefono ILIKE ? 
                        OR r.email ILIKE ?
                        OR CONCAT(r.nombre, ' ', r.apellido) ILIKE ?)";
            $searchTerm = '%' . $filtros['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // FILTRO POR ESTADO (activo/inactivo)
        if (isset($filtros['activo']) && $filtros['activo'] !== '') {
            $sql .= " AND r.activo = ?";
            // Convertir a boolean
            $params[] = ($filtros['activo'] === '1' || $filtros['activo'] === 1 || $filtros['activo'] === true) ? true : false;
        }
        
        // FILTRO POR DEPARTAMENTO
        if (!empty($filtros['departamento'])) {
            $sql .= " AND r.id_departamento = ?";
            $params[] = $filtros['departamento'];
        }
        
        // FILTRO POR MUNICIPIO
        if (!empty($filtros['municipio'])) {
            $sql .= " AND r.id_municipio = ?";
            $params[] = $filtros['municipio'];
        }
        
        // FILTRO POR ZONA
        if (!empty($filtros['zona'])) {
            $sql .= " AND r.id_zona = ?";
            $params[] = $filtros['zona'];
        }
        
        // FILTRO POR REFERENCIADOR
        if (!empty($filtros['referenciador'])) {
            $sql .= " AND r.id_referenciador = ?";
            $params[] = $filtros['referenciador'];
        }
        
        // FILTRO POR LÍDER
        if (!empty($filtros['lider'])) {
            $sql .= " AND r.id_lider = ?";
            $params[] = $filtros['lider'];
        }
        
        // ORDENAR
        $sql .= " ORDER BY r.fecha_registro DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error en getReferenciadosFiltrados: " . $e->getMessage());
        return [];
    }
}
/**
 * Construye la consulta base para obtener referenciados con todas las relaciones
 * @return string SQL base
 */
private function buildConsultaBase() {
    return "SELECT 
                r.id_referenciado,
                r.nombre,
                r.apellido,
                r.cedula,
                r.direccion,
                r.email,
                r.telefono,
                r.afinidad,
                r.sexo,
                r.fecha_nacimiento,
                r.compromiso,
                r.fecha_cumplimiento,
                r.mesa,
                r.vota_fuera,
                r.puesto_votacion_fuera,
                r.mesa_fuera,
                r.activo,
                r.fecha_registro,
                r.fecha_actualizacion,
                
                -- Zona
                z.id_zona,
                z.nombre as zona_nombre,
                
                -- Sector
                s.id_sector,
                s.nombre as sector_nombre,
                
                -- Puesto de votación
                pv.id_puesto,
                pv.nombre as puesto_votacion_nombre,
                
                -- Departamento
                d.id_departamento,
                d.nombre as departamento_nombre,
                
                -- Municipio
                m.id_municipio,
                m.nombre as municipio_nombre,
                
                -- Barrio
                b.id_barrio,
                b.nombre as barrio_nombre,
                
                -- Oferta de apoyo
                oa.id_oferta,
                oa.nombre as oferta_apoyo_nombre,
                
                -- Grupo poblacional
                gp.id_grupo,
                gp.nombre as grupo_poblacional_nombre,
                
                -- Grupo parlamentario
                g.id_grupo,
                g.nombre as grupo_nombre,
                
                -- Referenciador (usuario)
                ru.id_usuario as id_referenciador,
                CONCAT(ru.nombres, ' ', ru.apellidos) as referenciador_nombre,
                
                -- Líder (tabla lideres)
                l.id_lider,
                CONCAT(l.nombres, ' ', l.apellidos) as lider_nombre,
                l.cc as lider_cedula,
                l.telefono as lider_telefono,
                CONCAT(l.nombres, ' ', l.apellidos, ' - ', l.cc) as lider_nombre_completo,
                
                -- Campos calculados para mostrar
                CASE 
                    WHEN r.vota_fuera = 'Si' OR r.vota_fuera = true OR r.vota_fuera = 'true' THEN r.puesto_votacion_fuera
                    ELSE pv.nombre
                END as puesto_votacion_display,
                CASE 
                    WHEN r.vota_fuera = 'Si' OR r.vota_fuera = true OR r.vota_fuera = 'true' THEN r.mesa_fuera
                    ELSE r.mesa
                END as mesa_display
                
            FROM public.referenciados r
            
            -- Zona (LEFT JOIN porque puede ser null)
            LEFT JOIN public.zona z ON r.id_zona = z.id_zona
            
            -- Sector (LEFT JOIN)
            LEFT JOIN public.sector s ON r.id_sector = s.id_sector
            
            -- Puesto de votación (LEFT JOIN)
            LEFT JOIN public.puesto_votacion pv ON r.id_puesto_votacion = pv.id_puesto
            
            -- Municipio (LEFT JOIN)
            LEFT JOIN public.municipio m ON r.id_municipio = m.id_municipio
            
            -- Departamento (LEFT JOIN)
            LEFT JOIN public.departamento d ON m.id_departamento = d.id_departamento
            
            -- Barrio (LEFT JOIN)
            LEFT JOIN public.barrio b ON r.id_barrio = b.id_barrio
            
            -- Oferta de apoyo (LEFT JOIN)
            LEFT JOIN public.oferta_apoyo oa ON r.id_oferta_apoyo = oa.id_oferta
            
            -- Grupo poblacional (LEFT JOIN)
            LEFT JOIN public.grupo_poblacional gp ON r.id_grupo_poblacional = gp.id_grupo
            
            -- Grupo parlamentario (LEFT JOIN)
            LEFT JOIN public.grupos_parlamentarios g ON r.id_grupo = g.id_grupo
            
            -- Referenciador (LEFT JOIN con usuario)
            LEFT JOIN public.usuario ru ON r.id_referenciador = ru.id_usuario
            
            -- Líder (LEFT JOIN con tabla lideres)
            LEFT JOIN public.lideres l ON r.id_lider = l.id_lider";
}
/**
 * Actualizar el estado activo/inactivo de un referenciado
 */
public function actualizarEstadoReferenciado($id_referenciado, $activo, $motivo = null, $id_usuario = null) {
    try {
        // LOG: Ver qué está llegando
        error_log("=== DEBUG ACTUALIZAR ESTADO ===");
        error_log("ID Referenciado: " . $id_referenciado);
        error_log("Valor activo recibido: " . var_export($activo, true));
        error_log("Tipo de dato: " . gettype($activo));
        
        // Asegurar que sea boolean verdadero
        $activoBool = $activo === true || $activo === 1 || $activo === '1' || $activo === 't' ? true : false;
        error_log("Valor convertido: " . var_export($activoBool, true));
        
        $sql = "UPDATE referenciados SET activo = :activo WHERE id_referenciado = :id_referenciado";
        $stmt = $this->pdo->prepare($sql);
        
        $stmt->bindValue(':activo', $activoBool, PDO::PARAM_BOOL);
        $stmt->bindValue(':id_referenciado', $id_referenciado, PDO::PARAM_INT);
        
        $resultado = $stmt->execute();
        error_log("Resultado UPDATE: " . ($resultado ? 'ÉXITO' : 'FALLÓ'));
        
        return $resultado;
        
    } catch (PDOException $e) {
        error_log("Error al actualizar estado del referenciado: " . $e->getMessage());
        return false;
    }
}
}
?>