<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/SectorModel.php';

$pdo = Database::getConnection();
$sectorModel = new SectorModel($pdo);

$zona_id = $_GET['zona_id'] ?? 0;

if ($zona_id) {
    $sectores = $sectorModel->getByZona($zona_id);
    echo json_encode(['success' => true, 'sectores' => $sectores]);
} else {
    echo json_encode(['success' => false, 'message' => 'ID de zona no proporcionado']);
}
?>