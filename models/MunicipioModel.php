<?php
// models/MunicipioModel.php
class MunicipioModel {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function getByDepartamento($id_departamento) {
        $sql = "SELECT * FROM municipio WHERE id_departamento = :id_departamento ORDER BY nombre";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_departamento', $id_departamento, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getAll() {
        $sql = "SELECT * FROM municipio ORDER BY nombre";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>