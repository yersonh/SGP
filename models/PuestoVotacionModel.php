<?php
// models/PuestoVotacionModel.php
class PuestoVotacionModel {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function getAll() {
        $sql = "SELECT pv.*, s.nombre as sector_nombre, z.nombre as zona_nombre 
                FROM puesto_votacion pv 
                INNER JOIN sector s ON pv.id_sector = s.id_sector 
                INNER JOIN zona z ON s.id_zona = z.id_zona 
                ORDER BY z.nombre, s.nombre, pv.nombre";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getBySector($id_sector) {
        $sql = "SELECT * FROM puesto_votacion WHERE id_sector = :id_sector ORDER BY nombre";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_sector', $id_sector, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getById($id_puesto) {
        $sql = "SELECT pv.*, s.nombre as sector_nombre, z.nombre as zona_nombre 
                FROM puesto_votacion pv 
                INNER JOIN sector s ON pv.id_sector = s.id_sector 
                INNER JOIN zona z ON s.id_zona = z.id_zona 
                WHERE pv.id_puesto = :id_puesto";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_puesto', $id_puesto, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>