<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/PuestoVotacionModel.php';

$pdo = Database::getConnection();
$puestoModel = new PuestoVotacionModel($pdo);

$sector_id = $_GET['sector_id'] ?? 0;

if ($sector_id) {
    $puestos = $puestoModel->getBySector($sector_id);
    echo json_encode(['success' => true, 'puestos' => $puestos]);
} else {
    echo json_encode(['success' => false, 'message' => 'ID de sector no proporcionado']);
}
?>