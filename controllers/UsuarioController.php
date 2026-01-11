<?php
// controllers/UsuarioController.php
require_once __DIR__ . '/../models/UsuarioModel.php';

class UsuarioController {
    private $model;
    
    public function __construct($pdo) {
        $this->model = new UsuarioModel($pdo);
    }
    
    // Desactivar usuario
    public function desactivar($id_usuario, $id_admin) {
        // Validaciones
        if ($id_usuario == $id_admin) {
            return ['success' => false, 'message' => 'No puede dar de baja su propio usuario'];
        }
        
        if (!$this->model->esAdministrador($id_admin)) {
            return ['success' => false, 'message' => 'No autorizado'];
        }
        
        $result = $this->model->desactivarUsuario($id_usuario);
        
        if ($result) {
            return ['success' => true, 'message' => 'Usuario dado de baja'];
        } else {
            return ['success' => false, 'message' => 'Error al dar de baja el usuario'];
        }
    }
    
    // Reactivar usuario
    public function reactivar($id_usuario, $id_admin) {
        // Validaciones
        if (!$this->model->esAdministrador($id_admin)) {
            return ['success' => false, 'message' => 'No autorizado'];
        }
        
        $result = $this->model->reactivarUsuario($id_usuario);
        
        if ($result) {
            return ['success' => true, 'message' => 'Usuario reactivado'];
        } else {
            return ['success' => false, 'message' => 'Error al reactivar el usuario'];
        }
    }
    
    // Obtener todos los usuarios
    public function obtenerTodos() {
        return $this->model->getAllUsuarios();
    }
    
    // Obtener estadísticas
    public function obtenerEstadisticas() {
        return [
            'total' => $this->model->countUsuarios(),
            'activos' => $this->model->countUsuariosActivos(),
            'administradores' => $this->model->countAdministradores()
        ];
    }
}
?>