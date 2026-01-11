<?php
class ReferenciadoModel {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function guardarReferenciado($data) {
        // Asegurar que afinidad sea válida (1-5)
        $afinidad = max(1, min(5, intval($data['afinidad'])));
        
        $sql = "INSERT INTO referenciados (
            nombre, apellido, cedula, direccion, email, telefono, 
            afinidad, id_zona, id_sector, id_puesto_votacion, mesa,
            id_departamento, id_municipio, id_oferta_apoyo, id_grupo_poblacional,
            compromiso, id_referenciador, fecha_registro
        ) VALUES (
            :nombre, :apellido, :cedula, :direccion, :email, :telefono,
            :afinidad, :id_zona, :id_sector, :id_puesto_votacion, :mesa,
            :id_departamento, :id_municipio, :id_oferta_apoyo, :id_grupo_poblacional,
            :compromiso, :id_referenciador, NOW()
        )";
        
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
        $stmt->bindValue(':id_oferta_apoyo', $data['id_oferta_apoyo'], PDO::PARAM_INT);
        $stmt->bindValue(':id_grupo_poblacional', $data['id_grupo_poblacional'], PDO::PARAM_INT);
        $stmt->bindValue(':compromiso', $data['compromiso']);
        $stmt->bindValue(':id_referenciador', $data['id_referenciador'], PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    public function getReferenciadosByUsuario($id_referenciador) {
        $sql = "SELECT r.*, 
                d.nombre as departamento_nombre,
                m.nombre as municipio_nombre,
                gp.nombre as grupo_poblacional_nombre,
                oa.nombre as oferta_apoyo_nombre,
                z.nombre as zona_nombre,
                s.nombre as sector_nombre,
                pv.nombre as puesto_votacion_nombre
                FROM referenciados r
                LEFT JOIN departamento d ON r.id_departamento = d.id_departamento
                LEFT JOIN municipio m ON r.id_municipio = m.id_municipio
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
}
?>