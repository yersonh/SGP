<?php
class UsuarioModel {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Obtener todos los usuarios
    public function getAllUsuarios() {
        $query = "SELECT u.*, pe.nombres, pe.apellidos 
                  FROM usuario u 
                  LEFT JOIN personal_electoral pe ON u.id_usuario = pe.id_usuario 
                  ORDER BY u.fecha_creacion DESC";
        $stmt = $this->pdo->query($query);
        return $stmt->fetchAll();
    }
    
    // Obtener usuario por ID CON estadísticas de referenciados
    public function getUsuarioById($id_usuario) {
        $query = "SELECT 
                    u.*, 
                    pe.nombres, 
                    pe.apellidos,
                    COALESCE(r.total_referenciados, 0) as total_referenciados,
                    u.tope,
                    CASE 
                        WHEN u.tope > 0 THEN 
                            ROUND((COALESCE(r.total_referenciados, 0) * 100.0 / u.tope), 2)
                        ELSE 0 
                    END as porcentaje_tope
                  FROM usuario u 
                  LEFT JOIN personal_electoral pe ON u.id_usuario = pe.id_usuario 
                  LEFT JOIN (
                      SELECT id_referenciador, COUNT(*) as total_referenciados 
                      FROM referenciado 
                      WHERE id_referenciador = ?
                      GROUP BY id_referenciador
                  ) r ON u.id_usuario = r.id_referenciador
                  WHERE u.id_usuario = ?";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$id_usuario, $id_usuario]);
        return $stmt->fetch();
    }
    
    // Desactivar usuario (cambiar estado a false)
    public function desactivarUsuario($id_usuario) {
        $query = "UPDATE usuario SET activo = false WHERE id_usuario = ?";
        $stmt = $this->pdo->prepare($query);
        return $stmt->execute([$id_usuario]);
    }
    
    // Reactivar usuario (cambiar estado a true)
    public function reactivarUsuario($id_usuario) {
        $query = "UPDATE usuario SET activo = true WHERE id_usuario = ?";
        $stmt = $this->pdo->prepare($query);
        return $stmt->execute([$id_usuario]);
    }
    
    // Contar usuarios totales
    public function countUsuarios() {
        $query = "SELECT COUNT(*) as total FROM usuario";
        $stmt = $this->pdo->query($query);
        $result = $stmt->fetch();
        return $result['total'];
    }
    
    // Contar usuarios activos
    public function countUsuariosActivos() {
        $query = "SELECT COUNT(*) as activos FROM usuario WHERE activo = true";
        $stmt = $this->pdo->query($query);
        $result = $stmt->fetch();
        return $result['activos'];
    }
    
    // Contar administradores
    public function countAdministradores() {
        $query = "SELECT COUNT(*) as admins FROM usuario WHERE tipo_usuario = 'Administrador'";
        $stmt = $this->pdo->query($query);
        $result = $stmt->fetch();
        return $result['admins'];
    }
    
    // Actualizar último registro de acceso
    public function actualizarUltimoRegistro($id_usuario, $fecha) {
        $query = "UPDATE usuario SET ultimo_registro = ? WHERE id_usuario = ?";
        $stmt = $this->pdo->prepare($query);
        return $stmt->execute([$fecha, $id_usuario]);
    }
    
    // Verificar si usuario es administrador
    public function esAdministrador($id_usuario) {
        $query = "SELECT tipo_usuario FROM usuario WHERE id_usuario = ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$id_usuario]);
        $result = $stmt->fetch();
        return ($result && $result['tipo_usuario'] === 'Administrador');
    }
    
    // Verificar si usuario existe y está activo
    public function verificarCredenciales($nickname, $password) {
        $query = "SELECT * FROM usuario WHERE nickname = ? AND activo = true";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$nickname]);
        $usuario = $stmt->fetch();
        
        if ($usuario && $password === $usuario['password']) {
            return $usuario;
        }
        
        return false;
    }
    
    // Obtener estadísticas detalladas de referenciados (método nuevo)
    public function getEstadisticasReferenciados($id_usuario) {
        $query = "SELECT 
                    COUNT(*) as total,
                    COUNT(CASE WHEN fecha_creacion >= CURRENT_DATE - INTERVAL '7 days' THEN 1 END) as ultima_semana,
                    COUNT(CASE WHEN fecha_creacion >= CURRENT_DATE - INTERVAL '30 days' THEN 1 END) as ultimo_mes,
                    TO_CHAR(fecha_creacion, 'YYYY-MM') as mes,
                    COUNT(*) as por_mes
                  FROM referenciado 
                  WHERE id_referenciador = ?
                  GROUP BY TO_CHAR(fecha_creacion, 'YYYY-MM')
                  ORDER BY mes DESC
                  LIMIT 6";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$id_usuario]);
        return $stmt->fetchAll();
    }
}
?>