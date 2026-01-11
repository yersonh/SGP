<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/MunicipioModel.php';

$pdo = Database::getConnection();
$municipioModel = new MunicipioModel($pdo);

$departamento_id = $_GET['departamento_id'] ?? 0;

if ($departamento_id) {
    $municipios = $municipioModel->getByDepartamento($departamento_id);
    echo json_encode(['success' => true, 'municipios' => $municipios]);
} else {
    echo json_encode(['success' => false, 'message' => 'ID de departamento no proporcionado']);
}
?>