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
                    fecha_instalacion,
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
     /**
     * Obtener días transcurridos desde instalación
     */
    public function getDiasTranscurridosLicencia() {
        $sql = "SELECT 
                    fecha_instalacion,
                    (CURRENT_DATE - fecha_instalacion) as dias_transcurridos
                FROM sistema_informacion
                ORDER BY id_sistema DESC LIMIT 1";
        
        $stmt = $this->pdo->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && isset($result['dias_transcurridos'])) {
            return max(0, intval($result['dias_transcurridos']));
        }
        
        return 0;
    }
    public function getTotalDiasLicencia() {
        $sql = "SELECT 
                    fecha_instalacion,
                    valida_hasta,
                    (valida_hasta - fecha_instalacion) as total_dias
                FROM sistema_informacion
                ORDER BY id_sistema DESC LIMIT 1";
        
        $stmt = $this->pdo->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && isset($result['total_dias']) && $result['total_dias'] > 0) {
            return intval($result['total_dias']);
        }
        
        return 30; // Por defecto 30 días
    }
    
    /**
     * Obtener porcentaje de uso de la licencia
     */
    public function getPorcentajeUsoLicencia() {
        $diasTranscurridos = $this->getDiasTranscurridosLicencia();
        $totalDias = $this->getTotalDiasLicencia();
        
        if ($totalDias > 0) {
            $porcentaje = min(100, max(0, ($diasTranscurridos / $totalDias) * 100));
            return round($porcentaje, 1);
        }
        
        return 0;
    }
    public function getInfoCompletaLicencia() {
        $info = $this->getInformacionSistema();
        $diasRestantes = $this->getDiasRestantesLicencia();
        $diasTranscurridos = $this->getDiasTranscurridosLicencia();
        $totalDias = $this->getTotalDiasLicencia();
        $porcentajeUso = $this->getPorcentajeUsoLicencia();
        
        return [
            'info' => $info,
            'dias_restantes' => $diasRestantes,
            'dias_transcurridos' => $diasTranscurridos,
            'total_dias' => $totalDias,
            'porcentaje_uso' => $porcentajeUso,
            'porcentaje_restante' => 100 - $porcentajeUso,
            'valida_hasta_formatted' => isset($info['valida_hasta']) ? date('d/m/Y', strtotime($info['valida_hasta'])) : 'No disponible',
            'fecha_instalacion_formatted' => isset($info['fecha_instalacion']) ? date('d/m/Y', strtotime($info['fecha_instalacion'])) : 'No disponible'
        ];
    }
    public function getPorcentajeRestanteLicencia() {
        $diasRestantes = $this->getDiasRestantesLicencia();
        $totalDias = $this->getTotalDiasLicencia();
        
        if ($totalDias > 0) {
            $porcentaje = min(100, max(0, ($diasRestantes / $totalDias) * 100));
            return round($porcentaje, 1);
        }
        
        return 100; // Si no hay datos, mostrar 100%
    }
}
?>