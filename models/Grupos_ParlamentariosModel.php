<?php
//models/Grupos_ParlamentariosModel.php
class Grupos_ParlamentariosModel {  
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    public function getByIdGrupoParalamentario($id_grupo) {
        $sql = "SELECT * FROM grupos_parlamentarios WHERE id_grupo = :id_grupo order BY nombre"; ;
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_grupo', $id_grupo, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function getAll() {
    $sql = "SELECT * FROM grupos_parlamentarios ORDER BY id_grupo ASC";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
}
?>