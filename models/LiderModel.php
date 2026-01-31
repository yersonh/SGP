<?php
require_once __DIR__ . '/../config/database.php';

class LiderModel {
    private $pdo;
    
    public function __construct($pdo = null) {
        $this->pdo = $pdo ?: Database::getConnection();
    }
    
    /**
     * Crear un nuevo líder
     */
    public function create($data) {
    try {
        // Si el campo 'estado' es BOOLEAN, usar true/false en lugar de strings
        $estado = isset($data['estado']) ? (bool)$data['estado'] : true;
        // O si viene como string 'Activo'/'Inactivo'
        if (is_string($data['estado'] ?? '')) {
            $estado = strtolower($data['estado']) === 'activo' || strtolower($data['estado']) === 'true';
        }
        
        $id_usuario = !empty($data['id_usuario']) ? $data['id_usuario'] : null;
        
        // MODIFICA LA CONSULTA - elimina el campo 'estado' si tiene DEFAULT
        if (isset($data['estado'])) {
            $query = "INSERT INTO public.lideres 
                      (nombres, apellidos, cc, telefono, correo, id_usuario, estado, fecha_creacion, fecha_actualizacion) 
                      VALUES (:nombres, :apellidos, :cc, :telefono, :correo, :id_usuario, :estado, NOW(), NOW())";
        } else {
            // Si no se especifica estado, dejar que use el DEFAULT
            $query = "INSERT INTO public.lideres 
                      (nombres, apellidos, cc, telefono, correo, id_usuario, fecha_creacion, fecha_actualizacion) 
                      VALUES (:nombres, :apellidos, :cc, :telefono, :correo, :id_usuario, NOW(), NOW())";
        }
        
        $stmt = $this->pdo->prepare($query);
        
        $params = [
            ':nombres' => $data['nombres'],
            ':apellidos' => $data['apellidos'],
            ':cc' => $data['cc'],
            ':telefono' => $data['telefono'],
            ':correo' => $data['correo'],
            ':id_usuario' => $id_usuario
        ];
        
        if (isset($data['estado'])) {
            $params[':estado'] = $estado;
        }
        
        $stmt->execute($params);
        
        return [
            'success' => true,
            'id_lider' => $this->pdo->lastInsertId(),
            'message' => 'Líder creado exitosamente'
        ];
        
    } catch (PDOException $e) {
        error_log("Error en create LiderModel: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error al crear el líder: ' . $e->getMessage()
        ];
    }
}
    
    /**
     * Obtener todos los líderes
     */
    public function getAll($filters = []) {
        try {
            $whereConditions = [];
            $params = [];
            
            // Filtros opcionales
            if (!empty($filters['estado'])) {
                $whereConditions[] = "estado = :estado";
                $params[':estado'] = $filters['estado'];
            }
            
            if (!empty($filters['id_usuario'])) {
                $whereConditions[] = "id_usuario = :id_usuario";
                $params[':id_usuario'] = $filters['id_usuario'];
            }
            
            if (!empty($filters['search'])) {
                $whereConditions[] = "(nombres ILIKE :search OR apellidos ILIKE :search OR cc ILIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }
            
            $whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
            
            $query = "SELECT l.*, 
                             u.nombres as usuario_nombres, 
                             u.apellidos as usuario_apellidos,
                             CONCAT(u.nombres, ' ', u.apellidos) as referenciador_nombre
                      FROM public.lideres l
                      LEFT JOIN public.usuario u ON l.id_usuario = u.id_usuario
                      $whereClause
                      ORDER BY l.nombres, l.apellidos";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error en getAll LiderModel: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener líder por ID
     */
    public function getById($id_lider) {
        try {
            $query = "SELECT l.*, 
                             u.nombres as usuario_nombres, 
                             u.apellidos as usuario_apellidos,
                             CONCAT(u.nombres, ' ', u.apellidos) as referenciador_nombre
                      FROM public.lideres l
                      LEFT JOIN public.usuario u ON l.id_usuario = u.id_usuario
                      WHERE l.id_lider = :id_lider";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([':id_lider' => $id_lider]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error en getById LiderModel: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtener líder por cédula
     */
    public function getByCedula($cedula) {
        try {
            $query = "SELECT * FROM public.lideres WHERE cc = :cc";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([':cc' => $cedula]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error en getByCedula LiderModel: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Actualizar líder
     */
    public function update($id_lider, $data) {
        try {
            $fields = [];
            $params = [':id_lider' => $id_lider];
            
            // Construir campos a actualizar
            if (isset($data['nombres'])) {
                $fields[] = "nombres = :nombres";
                $params[':nombres'] = $data['nombres'];
            }
            
            if (isset($data['apellidos'])) {
                $fields[] = "apellidos = :apellidos";
                $params[':apellidos'] = $data['apellidos'];
            }
            
            if (isset($data['cc'])) {
                $fields[] = "cc = :cc";
                $params[':cc'] = $data['cc'];
            }
            
            if (isset($data['telefono'])) {
                $fields[] = "telefono = :telefono";
                $params[':telefono'] = $data['telefono'];
            }
            
            if (isset($data['correo'])) {
                $fields[] = "correo = :correo";
                $params[':correo'] = $data['correo'];
            }
            
            if (isset($data['id_usuario'])) {
                $fields[] = "id_usuario = :id_usuario";
                $params[':id_usuario'] = $data['id_usuario'];
            }
            
            if (isset($data['estado'])) {
                $fields[] = "estado = :estado";
                $params[':estado'] = $data['estado'];
            }
            
            // Siempre actualizar fecha_actualizacion
            $fields[] = "fecha_actualizacion = NOW()";
            
            if (empty($fields)) {
                return [
                    'success' => false,
                    'message' => 'No hay datos para actualizar'
                ];
            }
            
            $query = "UPDATE public.lideres SET " . implode(', ', $fields) . " WHERE id_lider = :id_lider";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            
            return [
                'success' => true,
                'affected_rows' => $stmt->rowCount(),
                'message' => 'Líder actualizado exitosamente'
            ];
            
        } catch (PDOException $e) {
            error_log("Error en update LiderModel: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al actualizar el líder: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Cambiar estado del líder
     */
    public function changeStatus($id_lider, $estado) {
    try {
        // Asegurar que estado sea booleano
        $estado_bool = (bool)$estado;
        
        $query = "UPDATE public.lideres 
                  SET estado = :estado, fecha_actualizacion = NOW() 
                  WHERE id_lider = :id_lider";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([
            ':id_lider' => $id_lider,
            ':estado' => $estado_bool
        ]);
        
        return [
            'success' => true,
            'affected_rows' => $stmt->rowCount(),
            'message' => 'Estado del líder actualizado exitosamente'
        ];
        
    } catch (PDOException $e) {
        error_log("Error en changeStatus LiderModel: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error al cambiar estado del líder: ' . $e->getMessage()
        ];
    }
}
    
    /**
     * Eliminar líder (cambiar estado a Inactivo)
     */
    public function delete($id_lider) {
        return $this->changeStatus($id_lider, false);
    }
    
    /**
     * Obtener líderes por referenciador (id_usuario)
     */
    public function getByReferenciador($id_usuario) {
        try {
            $query = "SELECT * FROM public.lideres 
                      WHERE id_usuario = :id_usuario 
                      AND estado = true
                      ORDER BY nombres, apellidos";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([':id_usuario' => $id_usuario]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error en getByReferenciador LiderModel: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener estadísticas de líderes
     */
    public function getStats() {
    try {
        $query = "SELECT 
                    COUNT(*) as total_lideres,
                    COUNT(CASE WHEN estado = true THEN 1 END) as activos,
                    COUNT(CASE WHEN estado = false THEN 1 END) as inactivos,
                    COUNT(DISTINCT id_usuario) as referenciadores_asignados
                  FROM public.lideres";
        
        $stmt = $this->pdo->query($query);
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error en getStats LiderModel: " . $e->getMessage());
        return [
            'total_lideres' => 0,
            'activos' => 0,
            'inactivos' => 0,
            'referenciadores_asignados' => 0
        ];
    }
}
    
    /**
     * Verificar si la cédula ya existe (para validaciones)
     */
    public function cedulaExists($cedula, $exclude_id = null) {
        try {
            $query = "SELECT COUNT(*) as count FROM public.lideres WHERE cc = :cc";
            $params = [':cc' => $cedula];
            
            if ($exclude_id) {
                $query .= " AND id_lider != :exclude_id";
                $params[':exclude_id'] = $exclude_id;
            }
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
            
        } catch (PDOException $e) {
            error_log("Error en cedulaExists LiderModel: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener conteo de líderes por referenciador
     */
    public function countByReferenciador($id_usuario) {
        try {
            $query = "SELECT COUNT(*) as count 
                      FROM public.lideres 
                      WHERE id_usuario = :id_usuario 
                      AND estado = 'Activo'";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([':id_usuario' => $id_usuario]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'];
            
        } catch (PDOException $e) {
            error_log("Error en countByReferenciador LiderModel: " . $e->getMessage());
            return 0;
        }
    }
    /**
 * Obtener todos los líderes activos (estado = true)
 */
public function getActivos() {
    try {
        $query = "SELECT id_lider, nombres, apellidos, cc, telefono, correo 
                  FROM public.lideres 
                  WHERE estado = true 
                  ORDER BY nombres, apellidos";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error en getActivos LiderModel: " . $e->getMessage());
        return [];
    }
}
}