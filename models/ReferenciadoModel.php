<?php
// models/ReferenciadoModel.php
class ReferenciadoModel {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function guardarReferenciado($data) {
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
        
        // Asignar valores (todos opcionales excepto los básicos)
        $stmt->bindParam(':nombre', $data['nombre']);
        $stmt->bindParam(':apellido', $data['apellido']);
        $stmt->bindParam(':cedula', $data['cedula']);
        $stmt->bindParam(':direccion', $data['direccion']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':telefono', $data['telefono']);
        $stmt->bindParam(':afinidad', $data['afinidad']);
        
        // Campos opcionales - usar NULL si no están definidos
        $id_zona = !empty($data['id_zona']) ? $data['id_zona'] : null;
        $id_sector = !empty($data['id_sector']) ? $data['id_sector'] : null;
        $id_puesto_votacion = !empty($data['id_puesto_votacion']) ? $data['id_puesto_votacion'] : null;
        $mesa = !empty($data['mesa']) ? $data['mesa'] : null;
        $id_departamento = !empty($data['id_departamento']) ? $data['id_departamento'] : null;
        $id_municipio = !empty($data['id_municipio']) ? $data['id_municipio'] : null;
        $id_oferta_apoyo = !empty($data['id_oferta_apoyo']) ? $data['id_oferta_apoyo'] : null;
        $id_grupo_poblacional = !empty($data['id_grupo_poblacional']) ? $data['id_grupo_poblacional'] : null;
        $compromiso = !empty($data['compromiso']) ? $data['compromiso'] : null;
        $id_referenciador = !empty($data['id_referenciador']) ? $data['id_referenciador'] : null;
        
        $stmt->bindParam(':id_zona', $id_zona);
        $stmt->bindParam(':id_sector', $id_sector);
        $stmt->bindParam(':id_puesto_votacion', $id_puesto_votacion);
        $stmt->bindParam(':mesa', $mesa);
        $stmt->bindParam(':id_departamento', $id_departamento);
        $stmt->bindParam(':id_municipio', $id_municipio);
        $stmt->bindParam(':id_oferta_apoyo', $id_oferta_apoyo);
        $stmt->bindParam(':id_grupo_poblacional', $id_grupo_poblacional);
        $stmt->bindParam(':compromiso', $compromiso);
        $stmt->bindParam(':id_referenciador', $id_referenciador);
        
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
        $stmt->bindParam(':id_referenciador', $id_referenciador, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>