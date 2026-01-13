<?php
class ReferenciadoModel {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function guardarReferenciado($data) {
        // Asegurar que afinidad sea válida (1-5)
        $afinidad = max(1, min(5, intval($data['afinidad'])));
        
        // Iniciar transacción para asegurar que todo se guarde o nada
        $this->pdo->beginTransaction();
        
        try {
            // SQL con el nuevo campo id_barrio
            $sql = "INSERT INTO referenciados (
                nombre, apellido, cedula, direccion, email, telefono, 
                afinidad, id_zona, id_sector, id_puesto_votacion, mesa,
                id_departamento, id_municipio, id_barrio, id_oferta_apoyo, id_grupo_poblacional,
                compromiso, id_referenciador, fecha_registro
            ) VALUES (
                :nombre, :apellido, :cedula, :direccion, :email, :telefono,
                :afinidad, :id_zona, :id_sector, :id_puesto_votacion, :mesa,
                :id_departamento, :id_municipio, :id_barrio, :id_oferta_apoyo, :id_grupo_poblacional,
                :compromiso, :id_referenciador, NOW()
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
            
            // Campos opcionales
            $stmt->bindValue(':id_zona', $data['id_zona'], PDO::PARAM_INT);
            $stmt->bindValue(':id_sector', $data['id_sector'], PDO::PARAM_INT);
            $stmt->bindValue(':id_puesto_votacion', $data['id_puesto_votacion'], PDO::PARAM_INT);
            $stmt->bindValue(':mesa', $data['mesa'], PDO::PARAM_INT);
            $stmt->bindValue(':id_departamento', $data['id_departamento'], PDO::PARAM_INT);
            $stmt->bindValue(':id_municipio', $data['id_municipio'], PDO::PARAM_INT);
            $stmt->bindValue(':id_barrio', $data['id_barrio'] ?? null, PDO::PARAM_INT); // NUEVO
            $stmt->bindValue(':id_oferta_apoyo', $data['id_oferta_apoyo'], PDO::PARAM_INT);
            $stmt->bindValue(':id_grupo_poblacional', $data['id_grupo_poblacional'], PDO::PARAM_INT);
            $stmt->bindValue(':compromiso', $data['compromiso']);
            $stmt->bindValue(':id_referenciador', $data['id_referenciador'], PDO::PARAM_INT);
            
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
                b.nombre as barrio_nombre, -- NUEVO
                gp.nombre as grupo_poblacional_nombre,
                oa.nombre as oferta_apoyo_nombre,
                z.nombre as zona_nombre,
                s.nombre as sector_nombre,
                pv.nombre as puesto_votacion_nombre
                FROM referenciados r
                LEFT JOIN departamento d ON r.id_departamento = d.id_departamento
                LEFT JOIN municipio m ON r.id_municipio = m.id_municipio
                LEFT JOIN barrio b ON r.id_barrio = b.id_barrio -- NUEVO
                LEFT JOIN grupo_poblacional gp ON r.id_grupo_poblacional = gp.id_grupo
                LEFT JOIN oferta_apoyo oa ON r.id_oferta_apoyo = oa.id_oferta
                LEFT JOIN zona z ON r.id_zona = z.id_zona
                LEFT JOIN sector s ON r.id_sector = s.id_sector
                LEFT JOIN puesto_votacion pv ON r.id_puesto_votacion = pv.id_puesto
                WHERE r.id_referenciador = :id_referenciador
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
                d.nombre as departamento_nombre,
                m.nombre as municipio_nombre,
                b.nombre as barrio_nombre,
                gp.nombre as grupo_poblacional_nombre,
                oa.nombre as oferta_apoyo_nombre,
                z.nombre as zona_nombre,
                s.nombre as sector_nombre,
                pv.nombre as puesto_votacion_nombre
                FROM referenciados r
                LEFT JOIN departamento d ON r.id_departamento = d.id_departamento
                LEFT JOIN municipio m ON r.id_municipio = m.id_municipio
                LEFT JOIN barrio b ON r.id_barrio = b.id_barrio
                LEFT JOIN grupo_poblacional gp ON r.id_grupo_poblacional = gp.id_grupo
                LEFT JOIN oferta_apoyo oa ON r.id_oferta_apoyo = oa.id_oferta
                LEFT JOIN zona z ON r.id_zona = z.id_zona
                LEFT JOIN sector s ON r.id_sector = s.id_sector
                LEFT JOIN puesto_votacion pv ON r.id_puesto_votacion = pv.id_puesto
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
     * Verifica si una cédula ya está registrada
     */
    public function cedulaExiste($cedula, $excluir_id = null) {
        $sql = "SELECT COUNT(*) FROM referenciados WHERE cedula = :cedula";
        
        if ($excluir_id) {
            $sql .= " AND id_referenciado != :excluir_id";
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':cedula', $cedula);
        
        if ($excluir_id) {
            $stmt->bindValue(':excluir_id', $excluir_id, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
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
            u.nombre as referenciador_nombre
            FROM referenciados r
            LEFT JOIN departamento d ON r.id_departamento = d.id_departamento
            LEFT JOIN municipio m ON r.id_municipio = m.id_municipio
            LEFT JOIN barrio b ON r.id_barrio = b.id_barrio
            LEFT JOIN grupo_poblacional gp ON r.id_grupo_poblacional = gp.id_grupo
            LEFT JOIN oferta_apoyo oa ON r.id_oferta_apoyo = oa.id_oferta
            LEFT JOIN zona z ON r.id_zona = z.id_zona
            LEFT JOIN sector s ON r.id_sector = s.id_sector
            LEFT JOIN puesto_votacion pv ON r.id_puesto_votacion = pv.id_puesto
            LEFT JOIN usuarios u ON r.id_referenciador = u.id_usuario
            ORDER BY r.fecha_registro DESC";
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
}
?>