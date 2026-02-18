<?php
// models/CandidatoModel.php

class CandidatoModel {
    
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Obtener todos los candidatos con sus relaciones
     * @return array Lista de candidatos
     */
    public function getAll() {
        try {
            $sql = "SELECT c.*, 
                    gp.nombre as grupo_nombre,
                    pp.nombre as partido_nombre
                    FROM candidatos c
                    LEFT JOIN grupos_parlamentarios gp ON c.id_grupo = gp.id_grupo
                    LEFT JOIN partidos_politicos pp ON c.id_partido = pp.id_partido
                    ORDER BY c.nombre, c.apellido";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error en CandidatoModel::getAll: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener candidato por ID
     * @param int $id ID del candidato
     * @return array|null Datos del candidato o null si no existe
     */
    public function getById($id) {
        try {
            $sql = "SELECT c.*, 
                    gp.nombre as grupo_nombre,
                    pp.nombre as partido_nombre
                    FROM candidatos c
                    LEFT JOIN grupos_parlamentarios gp ON c.id_grupo = gp.id_grupo
                    LEFT JOIN partidos_politicos pp ON c.id_partido = pp.id_partido
                    WHERE c.id_candidato = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error en CandidatoModel::getById: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Crear un nuevo candidato
     * @param array $datos Datos del candidato
     * @return int|false ID del nuevo candidato o false si falla
     */
    public function create($datos) {
        try {
            $sql = "INSERT INTO candidatos 
                    (nombre, apellido, id_grupo, id_partido) 
                    VALUES (?, ?, ?, ?)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $datos['nombre'],
                $datos['apellido'],
                !empty($datos['id_grupo']) ? $datos['id_grupo'] : null,
                !empty($datos['id_partido']) ? $datos['id_partido'] : null
            ]);
            
            return $this->pdo->lastInsertId();
            
        } catch (PDOException $e) {
            error_log("Error en CandidatoModel::create: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualizar un candidato existente
     * @param int $id ID del candidato
     * @param array $datos Datos a actualizar
     * @return bool True si se actualizó correctamente
     */
    public function update($id, $datos) {
        try {
            $sql = "UPDATE candidatos SET 
                    nombre = ?,
                    apellido = ?,
                    id_grupo = ?,
                    id_partido = ?
                    WHERE id_candidato = ?";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                $datos['nombre'],
                $datos['apellido'],
                !empty($datos['id_grupo']) ? $datos['id_grupo'] : null,
                !empty($datos['id_partido']) ? $datos['id_partido'] : null,
                $id
            ]);
            
        } catch (PDOException $e) {
            error_log("Error en CandidatoModel::update: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Eliminar un candidato
     * @param int $id ID del candidato
     * @return bool True si se eliminó correctamente
     */
    public function delete($id) {
        try {
            $sql = "DELETE FROM candidatos WHERE id_candidato = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$id]);
            
        } catch (PDOException $e) {
            error_log("Error en CandidatoModel::delete: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener candidatos por grupo parlamentario
     * @param int $id_grupo ID del grupo
     * @return array Lista de candidatos
     */
    public function getByGrupo($id_grupo) {
        try {
            $sql = "SELECT c.*, 
                    gp.nombre as grupo_nombre,
                    pp.nombre as partido_nombre
                    FROM candidatos c
                    LEFT JOIN grupos_parlamentarios gp ON c.id_grupo = gp.id_grupo
                    LEFT JOIN partidos_politicos pp ON c.id_partido = pp.id_partido
                    WHERE c.id_grupo = ?
                    ORDER BY c.nombre, c.apellido";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id_grupo]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error en CandidatoModel::getByGrupo: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener candidatos por partido político
     * @param int $id_partido ID del partido
     * @return array Lista de candidatos
     */
    public function getByPartido($id_partido) {
        try {
            $sql = "SELECT c.*, 
                    gp.nombre as grupo_nombre,
                    pp.nombre as partido_nombre
                    FROM candidatos c
                    LEFT JOIN grupos_parlamentarios gp ON c.id_grupo = gp.id_grupo
                    LEFT JOIN partidos_politicos pp ON c.id_partido = pp.id_partido
                    WHERE c.id_partido = ?
                    ORDER BY c.nombre, c.apellido";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id_partido]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error en CandidatoModel::getByPartido: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Buscar candidatos por nombre o apellido
     * @param string $termino Término de búsqueda
     * @return array Lista de candidatos
     */
    public function search($termino) {
        try {
            $sql = "SELECT c.*, 
                    gp.nombre as grupo_nombre,
                    pp.nombre as partido_nombre
                    FROM candidatos c
                    LEFT JOIN grupos_parlamentarios gp ON c.id_grupo = gp.id_grupo
                    LEFT JOIN partidos_politicos pp ON c.id_partido = pp.id_partido
                    WHERE c.nombre ILIKE ? 
                       OR c.apellido ILIKE ?
                       OR CONCAT(c.nombre, ' ', c.apellido) ILIKE ?
                    ORDER BY c.nombre, c.apellido";
            
            $searchTerm = '%' . $termino . '%';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error en CandidatoModel::search: " . $e->getMessage());
            return [];
        }
    }
    
    /**
 * Obtener todos los grupos parlamentarios para combos (excluyendo Pacha ID=3)
 * @return array Lista de grupos
 */
public function getGruposParaCombo() {
    try {
        // Excluir el grupo con ID = 3 (Pacha)
        $sql = "SELECT id_grupo, nombre 
                FROM grupos_parlamentarios 
                WHERE id_grupo != 3 
                ORDER BY nombre";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error en CandidatoModel::getGruposParaCombo: " . $e->getMessage());
        return [];
    }
}
    
    /**
     * Obtener todos los partidos políticos para combos
     * @return array Lista de partidos
     */
    public function getPartidosParaCombo() {
        try {
            $sql = "SELECT id_partido, nombre FROM partidos_politicos ORDER BY nombre";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en CandidatoModel::getPartidosParaCombo: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Verificar si un candidato existe (por nombre y apellido)
     * @param string $nombre
     * @param string $apellido
     * @param int $excluir_id ID a excluir (para edición)
     * @return bool True si existe
     */
    public function existeCandidato($nombre, $apellido, $excluir_id = null) {
        try {
            $sql = "SELECT COUNT(*) FROM candidatos 
                    WHERE nombre = ? AND apellido = ?";
            $params = [$nombre, $apellido];
            
            if ($excluir_id) {
                $sql .= " AND id_candidato != ?";
                $params[] = $excluir_id;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchColumn() > 0;
            
        } catch (PDOException $e) {
            error_log("Error en CandidatoModel::existeCandidato: " . $e->getMessage());
            return false;
        }
    }
}
?>