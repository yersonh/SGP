<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/CandidatoModel.php';

header('Content-Type: application/json');

// Verificar permisos
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'Administrador') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

// Obtener datos del POST
$id_candidato = isset($_POST['id_candidato']) ? (int)$_POST['id_candidato'] : 0;
$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$apellido = isset($_POST['apellido']) ? trim($_POST['apellido']) : '';
$id_grupo = isset($_POST['id_grupo']) && !empty($_POST['id_grupo']) ? (int)$_POST['id_grupo'] : null;
$id_partido = isset($_POST['id_partido']) && !empty($_POST['id_partido']) ? (int)$_POST['id_partido'] : null;

// Validar campos obligatorios
if (empty($id_candidato) || empty($nombre) || empty($apellido)) {
    echo json_encode([
        'success' => false, 
        'message' => 'ID, nombre y apellido son obligatorios'
    ]);
    exit();
}

try {
    $pdo = Database::getConnection();
    $candidatoModel = new CandidatoModel($pdo);
    
    // Verificar si ya existe otro candidato con el mismo nombre y apellido (excluyendo el actual)
    if ($candidatoModel->existeCandidato($nombre, $apellido, $id_candidato)) {
        echo json_encode([
            'success' => false,
            'message' => 'Ya existe otro candidato con ese nombre y apellido'
        ]);
        exit();
    }
    
    // Preparar datos
    $datos = [
        'nombre' => $nombre,
        'apellido' => $apellido,
        'id_grupo' => $id_grupo,
        'id_partido' => $id_partido
    ];
    
    // Actualizar candidato
    $resultado = $candidatoModel->update($id_candidato, $datos);
    
    if ($resultado) {
        echo json_encode([
            'success' => true,
            'message' => 'Candidato actualizado correctamente'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al actualizar el candidato'
        ]);
    }
    
} catch (Exception $e) {
    error_log('Error en actualizar_candidato.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
?>