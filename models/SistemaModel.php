<?php
// models/SistemaModel.php (versión minimalista)
class SistemaModel {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Obtener información del sistema
     */
    public function getInformacionSistema() {
        $sql = "SELECT * FROM sistema_informacion ORDER BY id_sistema DESC LIMIT 1";
        $stmt = $this->pdo->query($sql);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Si no hay datos, devolver valores por defecto
        if (!$resultado) {
            $resultado = [
                'nombre_sistema' => 'Sistema SGP',
                'version_sistema' => '1.0.1',
                'tipo_licencia' => 'Runtime',
                'valida_hasta' => '2026-12-31',
                'desarrollador' => 'SISGONTech - Ing. Rubén Darío González García',
                'contacto_email' => 'sisgonnet@gmail.com',
                'contacto_telefono' => '+57 3106310227',
                'estado_licencia' => 'Activa'
            ];
        }
        
        return $resultado;
    }
    
    /**
     * Verificar estado de la licencia
     */
    public function verificarLicencia() {
        $sql = "SELECT 
                    tipo_licencia,
                    valida_hasta,
                    estado_licencia,
                    CASE 
                        WHEN valida_hasta < CURRENT_DATE THEN 'Expirada'
                        WHEN valida_hasta <= CURRENT_DATE + INTERVAL '30 days' THEN 'Por expirar'
                        ELSE 'Vigente'
                    END AS estado_validez
                FROM sistema_informacion
                ORDER BY id_sistema DESC LIMIT 1";
        
        $stmt = $this->pdo->query($sql);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$resultado) {
            return [
                'tipo_licencia' => 'Runtime',
                'valida_hasta' => '2026-12-31',
                'estado_licencia' => 'Activa',
                'estado_validez' => 'Vigente'
            ];
        }
        
        return $resultado;
    }
    
    /**
     * Verificar si la licencia está activa
     */
    public function licenciaActiva() {
        $sql = "SELECT 
                    CASE 
                        WHEN estado_licencia = 'Activa' 
                        AND valida_hasta >= CURRENT_DATE 
                        THEN true 
                        ELSE false 
                    END as activa
                FROM sistema_informacion
                ORDER BY id_sistema DESC LIMIT 1";
        
        $stmt = $this->pdo->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['activa'] ?? false;
    }
    
    /**
     * Obtener días restantes de la licencia - VERSIÓN CORREGIDA
     */
    public function getDiasRestantesLicencia() {
        $sql = "SELECT 
                    valida_hasta,
                    (valida_hasta - CURRENT_DATE) as dias_restantes
                FROM sistema_informacion
                ORDER BY id_sistema DESC LIMIT 1";
        
        $stmt = $this->pdo->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && isset($result['dias_restantes'])) {
            return max(0, intval($result['dias_restantes']));
        }
        
        return 0;
    }
}
?>