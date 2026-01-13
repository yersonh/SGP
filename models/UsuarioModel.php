<?php
require_once __DIR__ . '/../helpers/file_helper.php';

class UsuarioModel {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Obtener todos los usuarios con datos completos
    public function getAllUsuarios() {
        $query = "SELECT 
                    u.*,
                    COALESCE(r.total_referenciados, 0) as total_referenciados,
                    CASE 
                        WHEN u.tope > 0 THEN 
                            ROUND((COALESCE(r.total_referenciados, 0) * 100.0 / u.tope), 2)
                        ELSE 0 
                    END as porcentaje_tope
                  FROM usuario u 
                  LEFT JOIN (
                      SELECT id_referenciador, COUNT(*) as total_referenciados 
                      FROM referenciados 
                      GROUP BY id_referenciador
                  ) r ON u.id_usuario = r.id_referenciador
                  ORDER BY u.fecha_creacion DESC";
        $stmt = $this->pdo->query($query);
        $usuarios = $stmt->fetchAll();
        
        // Agregar URLs de fotos
        foreach ($usuarios as &$usuario) {
            $usuario['foto_url'] = FileHelper::getPhotoUrl($usuario['foto']);
        }
        
        return $usuarios;
    }
    
    // Obtener usuario por ID CON estadísticas de referenciados y foto
    public function getUsuarioById($id_usuario) {
        $query = "SELECT 
                    u.*,
                    COALESCE(r.total_referenciados, 0) as total_referenciados,
                    CASE 
                        WHEN u.tope > 0 THEN 
                            ROUND((COALESCE(r.total_referenciados, 0) * 100.0 / u.tope), 2)
                        ELSE 0 
                    END as porcentaje_tope
                  FROM usuario u 
                  LEFT JOIN (
                      SELECT id_referenciador, COUNT(*) as total_referenciados 
                      FROM referenciados 
                      WHERE id_referenciador = ?
                      GROUP BY id_referenciador
                  ) r ON u.id_usuario = r.id_referenciador
                  WHERE u.id_usuario = ?";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$id_usuario, $id_usuario]);
        $usuario = $stmt->fetch();
        
        if ($usuario) {
            $usuario['foto_url'] = FileHelper::getPhotoUrl($usuario['foto']);
        }
        
        return $usuario;
    }
    
    // Crear nuevo usuario con foto
    public function crearUsuario($datos, $foto = null) {
        try {
            $this->pdo->beginTransaction();
            
            // Subir foto si existe
            $nombreFoto = null;
            if ($foto && isset($foto['tmp_name']) && $foto['tmp_name'] !== '') {
                $fileInfo = FileHelper::uploadProfilePhoto($foto, uniqid());
                $nombreFoto = basename($fileInfo['filename']);
            }
            
            $query = "INSERT INTO usuario (
                        nickname, password, tope, tipo_usuario, 
                        telefono, correo, cedula, apellidos, nombres,
                        id_puesto, id_sector, id_zona, foto
                      ) VALUES (
                        :nickname, :password, :tope, :tipo_usuario,
                        :telefono, :correo, :cedula, :apellidos, :nombres,
                        :id_puesto, :id_sector, :id_zona, :foto
                      ) RETURNING id_usuario";
            
            $stmt = $this->pdo->prepare($query);
            $params = [
                ':nickname' => $datos['nickname'],
                ':password' => $datos['password'], // Considera usar password_hash() aquí
                ':tope' => $datos['tope'] ?? 0,
                ':tipo_usuario' => $datos['tipo_usuario'] ?? 'Referenciador',
                ':telefono' => $datos['telefono'] ?? null,
                ':correo' => $datos['correo'] ?? null,
                ':cedula' => $datos['cedula'] ?? null,
                ':apellidos' => $datos['apellidos'] ?? null,
                ':nombres' => $datos['nombres'] ?? null,
                ':id_puesto' => $datos['id_puesto'] ?? null,
                ':id_sector' => $datos['id_sector'] ?? null,
                ':id_zona' => $datos['id_zona'] ?? null,
                ':foto' => $nombreFoto
            ];
            
            $stmt->execute($params);
            $id_usuario = $stmt->fetchColumn();
            
            $this->pdo->commit();
            return $id_usuario;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            
            // Eliminar foto si se subió pero falló la transacción
            if (isset($nombreFoto)) {
                FileHelper::deleteFile($nombreFoto, 'profiles');
            }
            
            throw $e;
        }
    }
    
    // Actualizar usuario con posibilidad de cambiar foto
    public function actualizarUsuario($id_usuario, $datos, $foto = null) {
    try {
        $this->pdo->beginTransaction();
        
        // Obtener foto actual
        $usuarioActual = $this->getUsuarioById($id_usuario);
        $fotoActual = $usuarioActual['foto'] ?? null;
        $nuevaFoto = $fotoActual;
        
        // Procesar nueva foto si se proporciona
        if ($foto && isset($foto['tmp_name']) && $foto['tmp_name'] !== '') {
            // Subir nueva foto
            $fileInfo = FileHelper::uploadProfilePhoto($foto, $id_usuario);
            $nuevaFoto = basename($fileInfo['filename']);
            
            // Eliminar foto anterior si existe y no es la por defecto
            if ($fotoActual && $fotoActual !== 'default.png') {
                FileHelper::deleteFile($fotoActual, 'profiles');
            }
        }
        
        // Construir la consulta base
        $campos = [
            "nickname = :nickname",
            "tope = :tope",
            "tipo_usuario = :tipo_usuario",
            "telefono = :telefono",
            "correo = :correo",
            "cedula = :cedula",
            "apellidos = :apellidos",
            "nombres = :nombres",
            "foto = :foto",
            "activo = :activo",
            "ultimo_registro = NOW()" // Actualizar último registro
        ];
        
        // Agregar campos opcionales solo si están presentes en $datos
        if (isset($datos['id_puesto'])) {
            $campos[] = "id_puesto = :id_puesto";
        }
        
        if (isset($datos['id_sector'])) {
            $campos[] = "id_sector = :id_sector";
        }
        
        if (isset($datos['id_zona'])) {
            $campos[] = "id_zona = :id_zona";
        }
        
        // Preparar parámetros base
        $params = [
            ':nickname' => $datos['nickname'] ?? '',
            ':tope' => $datos['tope'] ?? 0,
            ':tipo_usuario' => $datos['tipo_usuario'] ?? 'Referenciador',
            ':telefono' => $datos['telefono'] ?? null,
            ':correo' => $datos['correo'] ?? null,
            ':cedula' => $datos['cedula'] ?? null,
            ':apellidos' => $datos['apellidos'] ?? null,
            ':nombres' => $datos['nombres'] ?? null,
            ':foto' => $nuevaFoto,
            ':activo' => isset($datos['activo']) ? (bool)$datos['activo'] : true,
            ':id_usuario' => $id_usuario
        ];
        
        // Agregar parámetros opcionales
        if (isset($datos['id_puesto'])) {
            $params[':id_puesto'] = $datos['id_puesto'];
        }
        
        if (isset($datos['id_sector'])) {
            $params[':id_sector'] = $datos['id_sector'];
        }
        
        if (isset($datos['id_zona'])) {
            $params[':id_zona'] = $datos['id_zona'];
        }
        
        // Manejar contraseña si se proporciona
        if (!empty($datos['password'])) {
            $campos[] = "password = :password";
            $params[':password'] = password_hash($datos['password'], PASSWORD_DEFAULT);
        }
        
        // Construir la consulta final
        $campos_str = implode(', ', $campos);
        $query = "UPDATE usuario SET {$campos_str} WHERE id_usuario = :id_usuario";
        
        $stmt = $this->pdo->prepare($query);
        
        // Enlazar parámetros con tipos correctos
        foreach ($params as $key => $value) {
            $tipo = PDO::PARAM_STR;
            
            if ($value === null) {
                $tipo = PDO::PARAM_NULL;
            } elseif (is_bool($value)) {
                $tipo = PDO::PARAM_BOOL;
            } elseif (is_int($value)) {
                $tipo = PDO::PARAM_INT;
            }
            
            $stmt->bindValue($key, $value, $tipo);
        }
        
        $resultado = $stmt->execute();
        $this->pdo->commit();
        
        return $resultado;
        
    } catch (Exception $e) {
        $this->pdo->rollBack();
        error_log("Error al actualizar usuario: " . $e->getMessage());
        throw $e;
    }
}
    
    // Eliminar usuario (y su foto)
    public function eliminarUsuario($id_usuario) {
        try {
            $this->pdo->beginTransaction();
            
            // Obtener foto del usuario
            $usuario = $this->getUsuarioById($id_usuario);
            $foto = $usuario['foto'] ?? null;
            
            // Eliminar usuario
            $query = "DELETE FROM usuario WHERE id_usuario = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$id_usuario]);
            
            // Eliminar foto si existe y no es la por defecto
            if ($foto && $foto !== 'default.png') {
                FileHelper::deleteFile($foto, 'profiles');
            }
            
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
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
            // Agregar URL de foto
            $usuario['foto_url'] = FileHelper::getPhotoUrl($usuario['foto']);
            return $usuario;
        }
        
        return false;
    }
    
    // Obtener estadísticas detalladas de referenciados
    public function getEstadisticasReferenciados($id_usuario) {
        $query = "SELECT 
                    COUNT(*) as total,
                    COUNT(CASE WHEN fecha_creacion >= CURRENT_DATE - INTERVAL '7 days' THEN 1 END) as ultima_semana,
                    COUNT(CASE WHEN fecha_creacion >= CURRENT_DATE - INTERVAL '30 days' THEN 1 END) as ultimo_mes,
                    TO_CHAR(fecha_creacion, 'YYYY-MM') as mes,
                    COUNT(*) as por_mes
                  FROM referenciados
                  WHERE id_referenciador = ?
                  GROUP BY TO_CHAR(fecha_creacion, 'YYYY-MM')
                  ORDER BY mes DESC
                  LIMIT 6";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$id_usuario]);
        return $stmt->fetchAll();
    }
    
    // Buscar usuarios por diferentes criterios
    public function buscarUsuarios($filtros) {
        $where = ['1=1'];
        $params = [];
        
        if (!empty($filtros['nombres'])) {
            $where[] = "(nombres ILIKE ? OR apellidos ILIKE ?)";
            $params[] = "%{$filtros['nombres']}%";
            $params[] = "%{$filtros['nombres']}%";
        }
        
        if (!empty($filtros['cedula'])) {
            $where[] = "cedula ILIKE ?";
            $params[] = "%{$filtros['cedula']}%";
        }
        
        if (!empty($filtros['correo'])) {
            $where[] = "correo ILIKE ?";
            $params[] = "%{$filtros['correo']}%";
        }
        
        if (!empty($filtros['tipo_usuario'])) {
            $where[] = "tipo_usuario = ?";
            $params[] = $filtros['tipo_usuario'];
        }
        
        if (isset($filtros['activo'])) {
            $where[] = "activo = ?";
            $params[] = $filtros['activo'];
        }
        
        $whereClause = implode(' AND ', $where);
        $query = "SELECT * FROM usuario WHERE {$whereClause} ORDER BY fecha_creacion DESC";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $usuarios = $stmt->fetchAll();
        
        // Agregar URLs de fotos
        foreach ($usuarios as &$usuario) {
            $usuario['foto_url'] = FileHelper::getPhotoUrl($usuario['foto']);
        }
        
        return $usuarios;
    }
    
    // Verificar si un nickname o cédula ya existen
    public function nicknameExiste($nickname, $excluir_id = null) {
    $sql = "SELECT COUNT(*) FROM usuario WHERE nickname = :nickname";
    
    if ($excluir_id) {
        $sql .= " AND id_usuario != :excluir_id";
    }
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->bindValue(':nickname', $nickname);
    
    if ($excluir_id) {
        $stmt->bindValue(':excluir_id', $excluir_id, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    return $stmt->fetchColumn() > 0;
}

/**
 * Verifica si una cédula ya existe (excluyendo un usuario específico)
 */
public function cedulaExiste($cedula, $excluir_id = null) {
    $sql = "SELECT COUNT(*) FROM usuario WHERE cedula = :cedula";
    
    if ($excluir_id) {
        $sql .= " AND id_usuario != :excluir_id";
    }
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->bindValue(':cedula', $cedula);
    
    if ($excluir_id) {
        $stmt->bindValue(':excluir_id', $excluir_id, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    return $stmt->fetchColumn() > 0;
}
    // Contar Referenciadores
public function countReferenciadores() {
    $query = "SELECT COUNT(*) as referenciadores FROM usuario WHERE tipo_usuario = 'Referenciador'";
    $stmt = $this->pdo->query($query);
    $result = $stmt->fetch();
    return $result['referenciadores'];
}

// Contar Descargadores
public function countDescargadores() {
    $query = "SELECT COUNT(*) as descargadores FROM usuario WHERE tipo_usuario = 'Descargador'";
    $stmt = $this->pdo->query($query);
    $result = $stmt->fetch();
    return $result['descargadores'];
}

// Contar SuperAdmin
public function countSuperAdmin() {
    $query = "SELECT COUNT(*) as superadmin FROM usuario WHERE tipo_usuario = 'SuperAdmin'";
    $stmt = $this->pdo->query($query);
    $result = $stmt->fetch();
    return $result['superadmin'];
}

// Contar todos los tipos de usuario en un solo método
public function countTodosLosTipos() {
    $query = "SELECT 
                tipo_usuario,
                COUNT(*) as cantidad,
                COUNT(CASE WHEN activo = true THEN 1 END) as activos,
                COUNT(CASE WHEN activo = false THEN 1 END) as inactivos
              FROM usuario 
              GROUP BY tipo_usuario 
              ORDER BY tipo_usuario";
    $stmt = $this->pdo->query($query);
    return $stmt->fetchAll();
}
}
?>