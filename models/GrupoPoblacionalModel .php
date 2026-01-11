<?php
// models/GrupoPoblacionalModel.php
class GrupoPoblacionalModel {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function getAll() {
        $sql = "SELECT * FROM grupo_poblacional ORDER BY nombre";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>