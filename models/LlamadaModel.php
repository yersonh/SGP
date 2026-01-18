<?php
class LlamadaModel {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Guardar una nueva valoración de llamada
     */
    public function guardarValoracionLlamada($datos) {
        $sql = "INSERT INTO llamadas_tracking (
                    id_referenciado, 
                    id_usuario, 
                    id_resultado,
                    telefono, 
                    rating, 
                    observaciones,
                    fecha_llamada
                ) VALUES (
                    :id_referenciado, 
                    :id_usuario, 
                    :id_resultado,
                    :telefono, 
                    :rating, 
                    :observaciones,
                    :fecha_llamada
                ) RETURNING id_llamada";
        
        $stmt = $this->pdo->prepare($sql);
        
        $stmt->bindValue(':id_referenciado', $datos['id_referenciado'], PDO::PARAM_INT);
        $stmt->bindValue(':id_usuario', $datos['id_usuario'], PDO::PARAM_INT);
        $stmt->bindValue(':id_resultado', $datos['id_resultado'] ?? 1, PDO::PARAM_INT); // Default: Contactado (id=1)
        $stmt->bindValue(':telefono', $datos['telefono']);
        $stmt->bindValue(':rating', $datos['rating'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':observaciones', $datos['observaciones'] ?? null);
        $stmt->bindValue(':fecha_llamada', $datos['fecha_llamada'] ?? date('Y-m-d H:i:s'));
        
        $stmt->execute();
        return $stmt->fetchColumn();
    }
    
    /**
     * Obtener tipos de resultado disponibles
     */
    public function getTiposResultado() {
        $sql = "SELECT * FROM tipos_resultado_llamada WHERE activo = TRUE ORDER BY nombre";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
     public function tieneLlamadaRegistrada($idReferenciado) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as total 
            FROM llamadas_tracking 
            WHERE id_referenciado = ?
        ");
        $stmt->execute([$idReferenciado]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] > 0;
    }
    

    public function obtenerUltimaLlamada($idReferenciado) {
        $stmt = $this->pdo->prepare("
            SELECT fecha_llamada, rating, observaciones, id_resultado 
            FROM llamada
            WHERE id_referenciado = ? 
            ORDER BY fecha_llamada DESC 
            LIMIT 1
        ");
        $stmt->execute([$idReferenciado]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>