<?php
class InsumoModel {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Obtiene todos los insumos
     */
    public function getAll() {
        $sql = "SELECT * FROM insumo WHERE activo = true ORDER BY nombre";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene los insumos de un referenciado específico
     */
    public function getInsumosByReferenciado($id_referenciado) {
        $sql = "SELECT i.*, ri.cantidad, ri.observaciones 
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
     * Obtiene un insumo por ID
     */
    public function getById($id_insumo) {
        $sql = "SELECT * FROM insumo WHERE id_insumo = :id_insumo";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id_insumo', $id_insumo, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>