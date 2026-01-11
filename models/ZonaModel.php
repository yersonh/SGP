<?php
// models/ZonaModel.php
class ZonaModel {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function getAll() {
        $sql = "SELECT * FROM zona ORDER BY nombre";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getById($id_zona) {
        $sql = "SELECT * FROM zona WHERE id_zona = :id_zona";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_zona', $id_zona, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>