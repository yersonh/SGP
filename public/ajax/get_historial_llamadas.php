<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/LlamadaModel.php';
require_once __DIR__ . '/../../models/ReferenciadoModel.php';

// Verificar autenticación
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    echo '<div class="alert alert-danger">No autorizado</div>';
    exit();
}

$id_referenciado = $_GET['id_referenciado'] ?? 0;

if (!$id_referenciado) {
    echo '<div class="alert alert-warning">ID de referenciado no especificado</div>';
    exit();
}

$pdo = Database::getConnection();
$llamadaModel = new LlamadaModel($pdo);
$referenciadoModel = new ReferenciadoModel($pdo);

// Obtener referenciado
$referenciado = $referenciadoModel->getReferenciadoCompleto($id_referenciado);

// Obtener historial de llamadas
$llamadas = $llamadaModel->obtenerLlamadasPorReferenciado($id_referenciado);
?>

<div class="container-fluid">
    <!-- Información del referenciado -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-user me-2"></i>Información del Referenciado</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Nombre:</strong> <?php echo htmlspecialchars($referenciado['nombre'] . ' ' . $referenciado['apellido']); ?></p>
                            <p><strong>Cédula:</strong> <?php echo htmlspecialchars($referenciado['cedula']); ?></p>
                            <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($referenciado['telefono']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($referenciado['email'] ?? 'N/A'); ?></p>
                            <p><strong>Afinidad:</strong> <?php echo $referenciado['afinidad'] ?? 'N/A'; ?>/5</p>
                            <p><strong>Estado:</strong> 
                                <span class="badge <?php echo $referenciado['activo'] ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo $referenciado['activo'] ? 'Activo' : 'Inactivo'; ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Historial de llamadas -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Historial de Llamadas (<?php echo count($llamadas); ?> registros)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($llamadas)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>No hay registro de llamadas para este referenciado.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Fecha y Hora</th>
                                        <th>Realizada por</th>
                                        <th>Teléfono Contactado</th>
                                        <th>Resultado</th>
                                        <th>Rating</th>
                                        <th>Observaciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($llamadas as $llamada): ?>
                                        <tr>
                                            <td>
                                                <?php echo date('d/m/Y H:i', strtotime($llamada['fecha_llamada'])); ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($llamada['usuario_nombres'] . ' ' . $llamada['usuario_apellidos']); ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($llamada['telefono']); ?>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $llamada['id_resultado'] == 1 ? 'bg-success' : 'bg-warning'; ?>">
                                                    <?php echo htmlspecialchars($llamada['resultado_nombre']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($llamada['rating']): ?>
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <?php if ($i <= $llamada['rating']): ?>
                                                            <i class="fas fa-star text-warning"></i>
                                                        <?php else: ?>
                                                            <i class="far fa-star text-secondary"></i>
                                                        <?php endif; ?>
                                                    <?php endfor; ?>
                                                    <span class="ms-1">(<?php echo $llamada['rating']; ?>)</span>
                                                <?php else: ?>
                                                    <span class="text-muted">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo !empty($llamada['observaciones']) ? htmlspecialchars($llamada['observaciones']) : '<span class="text-muted">Sin observaciones</span>'; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>