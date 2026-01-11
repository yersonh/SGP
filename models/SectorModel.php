<?php
// models/SectorModel.php
class SectorModel {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function getAll() {
        $sql = "SELECT s.*, z.nombre as zona_nombre 
                FROM sector s 
                INNER JOIN zona z ON s.id_zona = z.id_zona 
                ORDER BY z.nombre, s.nombre";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getByZona($id_zona) {
        $sql = "SELECT * FROM sector WHERE id_zona = :id_zona ORDER BY nombre";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_zona', $id_zona, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getById($id_sector) {
        $sql = "SELECT s.*, z.nombre as zona_nombre 
                FROM sector s 
                INNER JOIN zona z ON s.id_zona = z.id_zona 
                WHERE s.id_sector = :id_sector";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_sector', $id_sector, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>