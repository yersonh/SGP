<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/ReferenciadosModel.php'; // Asegúrate de que este modelo exista
require_once __DIR__ . '/../../models/UsuarioModel.php';

// Verificar si el usuario está logueado y es SuperAdmin
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    header('Location: ../index.php');
    exit();
}

$pdo = Database::getConnection();
$referenciadosModel = new ReferenciadosModel($pdo);
$usuarioModel = new UsuarioModel($pdo);

// Obtener datos del usuario logueado
$usuario_logueado = $usuarioModel->getUsuarioById($_SESSION['id_usuario']);

// Obtener todos los referidos con información del referenciador
$referidos = $referenciadosModel->getAllWithReferenciador();

// Obtener estadísticas
$total_referidos = $referenciadosModel->countAll();
$por_votar = $referenciadosModel->countByEstado('pendiente'); // Ajusta según tu implementación
$ya_votaron = $referenciadosModel->countByEstado('votado'); // Ajusta según tu implementación
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Referidos - Super Admin - SGP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Estilos generales */
        * {
            box-sizing: border-box;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
            font-size: 14px;
        }
        
        /* Header Styles */
        .main-header {
            background: linear-gradient(135deg, #2c3e50, #1a252f);
            color: white;
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .header-title h1 {
            font-size: 1.3rem;
            font-weight: 600;
            margin: 0;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255,255,255,0.1);
            padding: 5px 10px;
            border-radius: 20px;
        }
        
        .user-info i {
            color: #3498db;
        }
        
        .header-actions {
            display: flex;
            gap: 10px;
        }
        
        .header-btn {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 6px 12px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 0.8rem;
        }
        
        .header-btn:hover {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        /* Breadcrumb */
        .breadcrumb-nav {
            max-width: 1400px;
            margin: 15px auto;
            padding: 0 15px;
        }
        
        .breadcrumb {
            background: transparent;
            padding: 0;
            margin: 0;
            font-size: 0.9rem;
        }
        
        .breadcrumb-item a {
            color: #3498db;
            text-decoration: none;
        }
        
        .breadcrumb-item.active {
            color: #666;
        }
        
        /* Main Container */
        .main-container {
            max-width: 1400px;
            margin: 0 auto 30px;
            padding: 0 15px;
        }
        
        /* Dashboard Header */
        .dashboard-header {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin: 20px 0;
            box-shadow: 0 3px 15px rgba(0,0,0,0.05);
            border-left: 5px solid #3498db;
        }
        
        .dashboard-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        
        .dashboard-title h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .dashboard-title h2 i {
            color: #3498db;
        }
        
        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 25px 0;
        }
        
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-top: 4px solid #3498db;
        }
        
        .stat-card.total {
            border-top-color: #2c3e50;
        }
        
        .stat-card.pendientes {
            border-top-color: #f39c12;
        }
        
        .stat-card.votados {
            border-top-color: #27ae60;
        }
        
        .stat-number {
            font-size: 2.2rem;
            font-weight: 700;
            color: #2c3e50;
            line-height: 1;
            margin-bottom: 5px;
        }
        
        .stat-card.total .stat-number {
            color: #2c3e50;
        }
        
        .stat-card.pendientes .stat-number {
            color: #f39c12;
        }
        
        .stat-card.votados .stat-number {
            color: #27ae60;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        /* Table Container */
        .table-container {
            background: white;
            border-radius: 10px;
            padding: 0;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            overflow: hidden;
            margin-top: 20px;
        }
        
        .table-header {
            background: #f1f5f9;
            padding: 20px;
            border-bottom: 2px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .table-header h3 {
            color: #2c3e50;
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .table-header h3 i {
            color: #3498db;
        }
        
        /* Table Styles */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table thead {
            background-color: #f8fafc;
        }
        
        .data-table th {
            padding: 18px 20px;
            text-align: left;
            color: #2c3e50;
            font-weight: 600;
            font-size: 0.9rem;
            border-bottom: 2px solid #e2e8f0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .data-table tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: background-color 0.2s;
        }
        
        .data-table tbody tr:hover {
            background-color: #f8fafc;
        }
        
        .data-table td {
            padding: 20px;
            vertical-align: middle;
            color: #4a5568;
        }
        
        /* Referido Info */
        .referido-info {
            display: flex;
            flex-direction: column;
        }
        
        .referido-nombre {
            font-weight: 600;
            color: #2c3e50;
            font-size: 1rem;
            margin-bottom: 3px;
        }
        
        .referido-documento {
            color: #718096;
            font-size: 0.85rem;
        }
        
        /* Referenciador Info */
        .referenciador-info {
            display: flex;
            flex-direction: column;
        }
        
        .referenciador-nombre {
            font-weight: 600;
            color: #2c3e50;
            font-size: 1rem;
            margin-bottom: 3px;
        }
        
        .referenciador-tipo {
            color: #718096;
            font-size: 0.85rem;
        }
        
        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-pendiente {
            background-color: rgba(243, 156, 18, 0.1);
            color: #f39c12;
        }
        
        .status-votado {
            background-color: rgba(39, 174, 96, 0.1);
            color: #27ae60;
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .btn-action {
            padding: 6px 12px;
            border-radius: 6px;
            border: none;
            font-weight: 500;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
        }
        
        .btn-view {
            background-color: rgba(155, 89, 182, 0.1);
            color: #9b59b6;
            border: 1px solid rgba(155, 89, 182, 0.2);
        }
        
        .btn-view:hover {
            background-color: rgba(155, 89, 182, 0.2);
            color: #9b59b6;
        }
        
        .btn-edit {
            background-color: rgba(52, 152, 219, 0.1);
            color: #3498db;
            border: 1px solid rgba(52, 152, 219, 0.2);
        }
        
        .btn-edit:hover {
            background-color: rgba(52, 152, 219, 0.2);
            color: #3498db;
        }
        
        .btn-deactivate {
            background-color: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
            border: 1px solid rgba(231, 76, 60, 0.2);
        }
        
        .btn-deactivate:hover {
            background-color: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
        }
        
        /* No Data Message */
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
        }
        
        .no-data i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        /* Footer */
        .system-footer {
            text-align: center;
            padding: 20px 0;
            background: white;
            color: black;
            font-size: 0.9rem;
            line-height: 1.6;
            border-top: 2px solid #eaeaea;
        }
        
        .system-footer p {
            margin: 5px 0;
            color: #333;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .header-top {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .header-actions {
                align-self: flex-end;
            }
            
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .data-table {
                display: block;
                overflow-x: auto;
            }
        }
        
        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .data-table th,
            .data-table td {
                padding: 15px 10px;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 5px;
            }
            
            .btn-action {
                justify-content: center;
                width: 100%;
            }
        }
        
        @media (max-width: 480px) {
            .header-title h1 {
                font-size: 1.1rem;
            }
            
            .header-btn {
                padding: 5px 8px;
                font-size: 0.7rem;
            }
            
            .dashboard-title {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .stat-card {
                padding: 15px;
            }
            
            .stat-number {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="header-container">
            <div class="header-top">
                <div class="header-title">
                    <h1><i class="fas fa-users"></i> Data Referidos - Super Admin</h1>
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($usuario_logueado['nombres'] . ' ' . $usuario_logueado['apellidos']); ?></span>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="../superadmin_dashboard.php" class="header-btn">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    <a href="../logout.php" class="header-btn">
                        <i class="fas fa-sign-out-alt"></i> Salir
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Breadcrumb Navigation -->
    <div class="breadcrumb-nav">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../superadmin_dashboard.php"><i class="fas fa-home"></i> Panel Super Admin</a></li>
                <li class="breadcrumb-item"><a href="superadmin_datas.php"><i class="fas fa-database"></i> Datas</a></li>
                <li class="breadcrumb-item active"><i class="fas fa-users"></i> Data Referidos</li>
            </ol>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="dashboard-title">
                <h2><i class="fas fa-users"></i> Data Referidos del Sistema</h2>
                <div>
                    <a href="#" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nuevo Referido
                    </a>
                </div>
            </div>
            <p>Gestión completa de todos los referidos registrados en el sistema. Visualiza, edita y administra toda la información de referenciación.</p>
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="stat-card total">
                <div class="stat-number"><?php echo $total_referidos; ?></div>
                <div class="stat-label">Total Referidos</div>
            </div>
            <div class="stat-card pendientes">
                <div class="stat-number"><?php echo $por_votar; ?></div>
                <div class="stat-label">Pendientes por Votar</div>
            </div>
            <div class="stat-card votados">
                <div class="stat-number"><?php echo $ya_votaron; ?></div>
                <div class="stat-label">Ya Votaron</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php 
                    $porcentaje = $total_referidos > 0 ? round(($ya_votaron / $total_referidos) * 100, 1) : 0;
                    echo $porcentaje . '%';
                    ?>
                </div>
                <div class="stat-label">Tasa de Votación</div>
            </div>
        </div>

        <!-- Table Container -->
        <div class="table-container">
            <div class="table-header">
                <h3><i class="fas fa-list-alt"></i> Listado de Referidos</h3>
                <div class="table-actions">
                    <button class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-download"></i> Exportar
                    </button>
                </div>
            </div>
            
            <?php if ($total_referidos > 0): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>REFERIDO</th>
                            <th>DOCUMENTO</th>
                            <th>REFERENCIADOR</th>
                            <th>FECHA REGISTRO</th>
                            <th>ESTADO</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($referidos as $referido): ?>
                        <tr>
                            <td>
                                <div class="referido-info">
                                    <span class="referido-nombre"><?php echo htmlspecialchars($referido['nombres'] . ' ' . $referido['apellidos']); ?></span>
                                    <span class="referido-documento"><?php echo htmlspecialchars($referido['telefono'] ?? 'Sin teléfono'); ?></span>
                                </div>
                            </td>
                            
                            <td>
                                <div class="referido-documento">
                                    <?php echo htmlspecialchars($referido['cedula'] ?? 'Sin documento'); ?>
                                </div>
                            </td>
                            
                            <td>
                                <div class="referenciador-info">
                                    <span class="referenciador-nombre">
                                        <?php echo htmlspecialchars($referido['referenciador_nombre'] ?? 'Sin referenciador'); ?>
                                    </span>
                                    <span class="referenciador-tipo">
                                        <?php echo htmlspecialchars($referido['referenciador_tipo'] ?? ''); ?>
                                    </span>
                                </div>
                            </td>
                            
                            <td>
                                <?php 
                                $fecha_registro = !empty($referido['fecha_creacion']) ? 
                                    date('d/m/Y', strtotime($referido['fecha_creacion'])) : 
                                    'Sin fecha';
                                echo $fecha_registro;
                                ?>
                            </td>
                            
                            <td>
                                <?php 
                                $estado = $referido['estado'] ?? 'pendiente';
                                if ($estado === 'votado' || $estado === 'VOTADO'): ?>
                                    <span class="status-badge status-votado">
                                        <i class="fas fa-check-circle"></i> Votado
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge status-pendiente">
                                        <i class="fas fa-clock"></i> Pendiente
                                    </span>
                                <?php endif; ?>
                            </td>
                            
                            <td>
                                <div class="action-buttons">
                                    <!-- BOTÓN DE VER DETALLE -->
                                    <button class="btn-action btn-view"
                                            onclick="window.location.href='ver_referido.php?id=<?php echo $referido['id_referenciado']; ?>'"
                                            title="Ver detalle del referido">
                                        <i class="fas fa-eye"></i> VER
                                    </button>
                                    
                                    <!-- BOTÓN DE EDITAR -->
                                    <button class="btn-action btn-edit"
                                            onclick="window.location.href='editar_referido.php?id=<?php echo $referido['id_referenciado']; ?>'"
                                            title="Editar referido">
                                        <i class="fas fa-edit"></i> EDITAR
                                    </button>
                                    
                                    <!-- BOTÓN DE DAR DE BAJA -->
                                    <button class="btn-action btn-deactivate"
                                            onclick="darDeBajaReferido(<?php echo $referido['id_referenciado']; ?>, '<?php echo htmlspecialchars($referido['nombres'] . ' ' . $referido['apellidos']); ?>', this)"
                                            title="Dar de baja referido">
                                        <i class="fas fa-user-slash"></i> BAJA
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="no-data">
                <i class="fas fa-users"></i>
                <h4 class="mt-3 mb-2">No hay referidos registrados</h4>
                <p class="text-muted">El sistema no tiene referidos registrados actualmente.</p>
                <a href="#" class="btn btn-primary mt-3">
                    <i class="fas fa-plus-circle"></i> Agregar Primer Referido
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="system-footer">
        <div class="container">
            <p>© Derechos de autor Reservados. 
                Ing. Rubén Darío González García • 
                SISGONTech • Colombia © • <?php echo date('Y'); ?>
            </p>
            <p>Contacto: +57 3106310227 • 
                Email: sisgonnet@gmail.com
            </p>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Función para dar de baja un referido
        function darDeBajaReferido(idReferido, nombre, button) {
            if (!confirm(`¿Está seguro de dar de BAJA al referido "${nombre}"?\n\nEsta acción marcará al referido como inactivo.`)) {
                return;
            }
            
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
            button.disabled = true;
            
            // Aquí iría la llamada AJAX para dar de baja
            // Por ahora solo mostramos un mensaje
            setTimeout(() => {
                alert('Funcionalidad de baja pendiente de implementar');
                button.innerHTML = originalText;
                button.disabled = false;
            }, 1000);
        }
        
        // Efecto hover en filas de la tabla
        $(document).ready(function() {
            $('.data-table tbody tr').hover(
                function() {
                    $(this).css('backgroundColor', '#f8fafc');
                },
                function() {
                    $(this).css('backgroundColor', '');
                }
            );
        });
    </script>
</body>
</html>