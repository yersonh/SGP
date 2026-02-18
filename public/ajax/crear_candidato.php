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
$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$apellido = isset($_POST['apellido']) ? trim($_POST['apellido']) : '';
$id_grupo = isset($_POST['id_grupo']) && !empty($_POST['id_grupo']) ? (int)$_POST['id_grupo'] : null;
$id_partido = isset($_POST['id_partido']) && !empty($_POST['id_partido']) ? (int)$_POST['id_partido'] : null;

// Validar campos obligatorios
if (empty($nombre) || empty($apellido)) {
    echo json_encode([
        'success' => false, 
        'message' => 'El nombre y apellido son obligatorios'
    ]);
    exit();
}

try {
    $pdo = Database::getConnection();
    $candidatoModel = new CandidatoModel($pdo);
    
    // Verificar si ya existe un candidato con el mismo nombre y apellido
    if ($candidatoModel->existeCandidato($nombre, $apellido)) {
        echo json_encode([
            'success' => false,
            'message' => 'Ya existe un candidato con ese nombre y apellido'
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
    
    // Insertar candidato
    $id_candidato = $candidatoModel->create($datos);
    
    if ($id_candidato) {
        echo json_encode([
            'success' => true,
            'message' => 'Candidato creado correctamente',
            'id_candidato' => $id_candidato
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al crear el candidato'
        ]);
    }
    
} catch (Exception $e) {
    error_log('Error en crear_candidato.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
?>