<?php
require_once __DIR__ . '/../config/database.php';

class PuntoMapaModel {
    private $pdo;
    
    public function __construct($pdo = null) {
        if ($pdo === null) {
            $this->pdo = Database::getConnection();
        } else {
            $this->pdo = $pdo;
        }
    }
    
    // Crear nuevo punto
    public function crearPunto($data) {
        $sql = "INSERT INTO puntos_mapa (nombre, descripcion, latitud, longitud, tipo_punto, color_marcador, id_usuario) 
                VALUES (:nombre, :descripcion, :latitud, :longitud, :tipo_punto, :color_marcador, :id_usuario) 
                RETURNING id_punto";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':nombre' => $data['nombre'],
            ':descripcion' => $data['descripcion'],
            ':latitud' => $data['latitud'],
            ':longitud' => $data['longitud'],
            ':tipo_punto' => $data['tipo_punto'],
            ':color_marcador' => $data['color_marcador'],
            ':id_usuario' => $data['id_usuario']
        ]);
        
        return $stmt->fetchColumn();
    }
    
    // Obtener todos los puntos activos
    public function obtenerPuntos($activo = true) {
        $sql = "SELECT pm.*, u.nombres, u.apellidos 
                FROM puntos_mapa pm
                INNER JOIN usuario u ON pm.id_usuario = u.id_usuario
                WHERE pm.activo = :activo
                ORDER BY pm.fecha_creacion DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':activo' => $activo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Obtener puntos por usuario
    public function obtenerPuntosPorUsuario($id_usuario, $activo = true) {
        $sql = "SELECT pm.*, u.nombres, u.apellidos 
                FROM puntos_mapa pm
                INNER JOIN usuario u ON pm.id_usuario = u.id_usuario
                WHERE pm.id_usuario = :id_usuario AND pm.activo = :activo
                ORDER BY pm.fecha_creacion DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id_usuario' => $id_usuario,
            ':activo' => $activo
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Obtener punto por ID
    public function obtenerPuntoPorId($id_punto) {
        $sql = "SELECT pm.*, u.nombres, u.apellidos 
                FROM puntos_mapa pm
                INNER JOIN usuario u ON pm.id_usuario = u.id_usuario
                WHERE pm.id_punto = :id_punto";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_punto' => $id_punto]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Actualizar punto
    public function actualizarPunto($id_punto, $data) {
        $sql = "UPDATE puntos_mapa 
                SET nombre = :nombre, 
                    descripcion = :descripcion, 
                    tipo_punto = :tipo_punto,
                    color_marcador = :color_marcador
                WHERE id_punto = :id_punto AND id_usuario = :id_usuario
                RETURNING id_punto";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':nombre' => $data['nombre'],
            ':descripcion' => $data['descripcion'],
            ':tipo_punto' => $data['tipo_punto'],
            ':color_marcador' => $data['color_marcador'],
            ':id_punto' => $id_punto,
            ':id_usuario' => $data['id_usuario']
        ]);
        
        return $stmt->fetchColumn() !== false;
    }
    
    // Eliminar punto (soft delete)
    public function eliminarPunto($id_punto, $id_usuario) {
        $sql = "UPDATE puntos_mapa SET activo = FALSE 
                WHERE id_punto = :id_punto AND id_usuario = :id_usuario
                RETURNING id_punto";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id_punto' => $id_punto,
            ':id_usuario' => $id_usuario
        ]);
        
        return $stmt->fetchColumn() !== false;
    }
    
    // Tipos de puntos disponibles
    public function obtenerTiposPuntos() {
        return [
            'casa' => 'Casa/Hogar',
            'trabajo' => 'Lugar de Trabajo',
            'comercio' => 'Comercio/Negocio',
            'publico' => 'Espacio Público',
            'salud' => 'Centro de Salud',
            'educacion' => 'Centro Educativo',
            'recreacion' => 'Área Recreativa',
            'referencia' => 'Punto de Referencia',
            'puesto' => 'Puesto Votación'
        ];
    }
    
    // Colores disponibles para marcadores
    public function obtenerColoresMarcadores() {
        return [
            '#4fc3f7' => 'Azul Claro',
            '#2196f3' => 'Azul',
            '#3f51b5' => 'Azul Oscuro',
            '#9c27b0' => 'Morado',
            '#e91e63' => 'Rosa',
            '#f44336' => 'Rojo',
            '#ff9800' => 'Naranja',
            '#ffeb3b' => 'Amarillo',
            '#8bc34a' => 'Verde Claro',
            '#4caf50' => 'Verde',
            '#009688' => 'Turquesa',
            '#795548' => 'Marrón'
        ];
    }
}