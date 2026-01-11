<?php
// models/BarrioModel.php
class BarrioModel {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function getAll() {
        $sql = "SELECT * FROM barrio WHERE activo = true ORDER BY nombre";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getById($id) {
        $sql = "SELECT * FROM barrio WHERE id_barrio = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}
?>