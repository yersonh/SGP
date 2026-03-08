<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../models/ReferenciadoModel.php';
require_once __DIR__ . '/../models/LiderModel.php';
require_once __DIR__ . '/../models/SistemaModel.php';

// Verificar si el usuario está logueado y es Referenciador
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'Referenciador') {
    header('Location: index.php');
    exit();
}

// Verificar que se recibió el ID del líder
if (!isset($_GET['id_lider']) || empty($_GET['id_lider'])) {
    header('Location: monitoreo.php');
    exit();
}

$id_lider = (int)$_GET['id_lider'];
$id_referenciador = $_SESSION['id_usuario'];

$pdo = Database::getConnection();
$usuarioModel = new UsuarioModel($pdo);
$referenciadoModel = new ReferenciadoModel($pdo);
$liderModel = new LiderModel($pdo);
$sistemaModel = new SistemaModel($pdo);

// Obtener datos del usuario logueado
$usuario_logueado = $usuarioModel->getUsuarioByIdActivo($id_referenciador);

// Verificar que el líder pertenezca al referenciador (por id_usuario)
$lider = $liderModel->getById($id_lider);
if (!$lider || $lider['id_usuario'] != $id_referenciador) {
    header('Location: monitoreo.php');
    exit();
}

// Obtener referenciados de este líder
$referenciados = $referenciadoModel->getByLider($id_lider);

// Obtener estadísticas
$totalReferenciados = count($referenciados);
$votaronReferenciados = 0;
foreach ($referenciados as $ref) {
    if ($ref['voto_registrado']) {
        $votaronReferenciados++;
    }
}
$pendientesReferenciados = $totalReferenciados - $votaronReferenciados;
$porcentajeReferenciados = $totalReferenciados > 0 ? round(($votaronReferenciados / $totalReferenciados) * 100, 2) : 0;

// Obtener información del sistema para el modal
$licenciaInfo = $sistemaModel->getInfoCompletaLicencia();
$infoSistema = $licenciaInfo['info'];
$diasRestantes = $licenciaInfo['dias_restantes'];
$validaHastaFormatted = $licenciaInfo['valida_hasta_formatted'];
$fechaInstalacionFormatted = $licenciaInfo['fecha_instalacion_formatted'];

// Calcular porcentaje restante de licencia
$porcentajeRestante = $sistemaModel->getPorcentajeRestanteLicencia();
if ($porcentajeRestante > 50) { 
    $barColor = 'bg-success';
} elseif ($porcentajeRestante > 25) {
    $barColor = 'bg-warning';
} else {
    $barColor = 'bg-danger';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Referenciados por Líder - SGP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ========== ESTILOS DEL FORMULARIO ========== */
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-gray: #f8f9fa;
            --dark-gray: #343a40;
            --success-color: #27ae60;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
        }
        
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
            background: linear-gradient(135deg, var(--primary-color), #1a252f);
            color: white;
            padding: 15px 0;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-container {
            display: flex;
            flex-direction: column;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            margin-bottom: 10px;
        }
        
        .header-title {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .header-title h1 {
            font-size: 1.2rem;
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
            align-items: center;
            gap: 15px;
        }
        
        /* Estilos para los botones de acción */
        .action-buttons {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .view-referrals-btn {
            background: linear-gradient(135deg, #17a2b8, #138496);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(23, 162, 184, 0.3);
            font-size: 0.9rem;
        }
        
        .monitoring-btn {
            background: linear-gradient(135deg, #28a745, #218838);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
            font-size: 0.9rem;
        }
        
        .monitoring-btn:hover {
            background: linear-gradient(135deg, #218838, #1e7e34);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
            color: white;
            text-decoration: none;
        }
        
        .criers-btn {
            background: linear-gradient(135deg, #fd7e14, #dc3545);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(253, 126, 20, 0.3);
            font-size: 0.9rem;
        }
        
        .criers-btn:hover {
            background: linear-gradient(135deg, #dc3545, #c82333);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
            color: white;
            text-decoration: none;
        }
        
        .view-referrals-btn:hover {
            background: linear-gradient(135deg, #138496, #117a8b);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(23, 162, 184, 0.4);
            color: white;
            text-decoration: none;
        }
        
        .view-referrals-btn i,
        .monitoring-btn i,
        .criers-btn i {
            font-size: 1.1rem;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.2);
            color: white;
            transform: translateY(-2px);
        }
        
        /* Breadcrumb */
        .breadcrumb-nav {
            max-width: 1200px;
            margin: 0 auto 20px;
            padding: 0 15px;
        }
        
        .breadcrumb {
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #eaeaea;
        }
        
        .breadcrumb a {
            color: var(--secondary-color);
            text-decoration: none;
        }
        
        /* Main container */
        .main-container {
            max-width: 1200px;
            margin: 0 auto 30px;
            padding: 0 15px;
        }
        
        /* Líder Info Card */
        .lider-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            gap: 30px;
            flex-wrap: wrap;
        }
        
        .lider-avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--secondary-color), #2980b9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
        }
        
        .lider-datos {
            flex: 1;
        }
        
        .lider-nombre {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .lider-detalles {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .detalle-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #7f8c8d;
        }
        
        .detalle-item i {
            color: var(--secondary-color);
            width: 20px;
        }
        
        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            border: 1px solid #eaeaea;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .stat-icon.total {
            background: linear-gradient(135deg, var(--secondary-color), #2980b9);
            color: white;
        }
        
        .stat-icon.votaron {
            background: linear-gradient(135deg, var(--success-color), #219653);
            color: white;
        }
        
        .stat-icon.pendientes {
            background: linear-gradient(135deg, var(--danger-color), #c0392b);
            color: white;
        }
        
        .stat-content h3 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .stat-content p {
            margin: 0;
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        /* Progress Bar */
        .progress-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
        }
        
        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .progress-title {
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .progress-percentage {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--secondary-color);
        }
        
        .progress-bar-container {
            width: 100%;
            height: 12px;
            background-color: #ecf0f1;
            border-radius: 6px;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--secondary-color), #2980b9);
            border-radius: 6px;
            transition: width 0.5s ease;
        }
        
        .progress-stats {
            display: flex;
            justify-content: space-between;
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        /* Table */
        .table-container {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
        }
        
        .table-header {
            background: linear-gradient(135deg, var(--primary-color), #1a252f);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .table-title {
            font-size: 1.1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .table-title i {
            color: var(--secondary-color);
        }
        
        .search-box {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255,255,255,0.1);
            padding: 8px 15px;
            border-radius: 50px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .search-box input {
            background: transparent;
            border: none;
            color: white;
            outline: none;
            min-width: 250px;
        }
        
        .search-box input::placeholder {
            color: rgba(255,255,255,0.7);
        }
        
        .search-box i {
            color: rgba(255,255,255,0.7);
        }
        
        .table-responsive {
            padding: 20px;
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            text-align: left;
            padding: 12px;
            background-color: #f8f9fa;
            color: var(--primary-color);
            font-weight: 600;
            font-size: 0.9rem;
            border-bottom: 2px solid #eaeaea;
        }
        
        td {
            padding: 15px 12px;
            border-bottom: 1px solid #eaeaea;
            vertical-align: middle;
        }
        
        .voto-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .voto-badge.votado {
            background-color: var(--success-color);
            color: white;
        }
        
        .voto-badge.pendiente {
            background-color: var(--danger-color);
            color: white;
        }
        
        .afinidad-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            background-color: #f1c40f;
            color: #2c3e50;
        }
        
        .certificado-badge {
            background-color: var(--success-color);
            color: white;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 0.7rem;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }
        
        .btn-volver {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 0.8rem;
        }
        
        .btn-volver:hover {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        /* Footer */
        .system-footer {
            text-align: center;
            padding: 20px 0;
            background: white;
            color: black;
            font-size: 0.85rem;
            line-height: 1.5;
            margin-top: 30px;
            border-top: 2px solid #eaeaea;
        }
        
        .system-footer p {
            margin: 5px 0;
        }
        
        .system-footer strong {
            color: #000000;
            font-weight: 600;
        }
        
        .logo-clickable {
            max-width: 320px;
            height: auto;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .logo-clickable:hover {
            transform: scale(1.05);
        }
        
        /* Modal */
        .modal-system-info .modal-header {
            background: linear-gradient(135deg, #2c3e50, #1a252f);
            color: white;
        }
        
        .modal-system-info .modal-body {
            padding: 20px;
        }
        
        .modal-logo-container {
            text-align: center;
            margin-bottom: 20px;
            padding: 15px;
        }
        
        .modal-logo {
            max-width: 300px;
            height: auto;
            margin: 0 auto;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
            border: 3px solid #fff;
            background: white;
        }
        
        .licencia-info {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }
        
        .licencia-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .licencia-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }
        
        .licencia-dias {
            font-size: 1rem;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 20px;
            background: #3498db;
            color: white;
        }
        
        .licencia-progress {
            height: 12px;
            border-radius: 6px;
            margin-bottom: 8px;
            background-color: #e9ecef;
            overflow: hidden;
        }
        
        .licencia-progress-bar {
            height: 100%;
            border-radius: 6px;
            transition: width 0.6s ease;
        }
        
        .bg-success { background-color: #27ae60; }
        .bg-warning { background-color: #f39c12; }
        .bg-danger { background-color: #e74c3c; }
        
        .licencia-fecha {
            font-size: 0.85rem;
            color: #6c757d;
            text-align: center;
            margin-top: 5px;
        }
        
        .feature-image-container {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .feature-img-header {
            width: 190px;
            height: 190px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #ffffff;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .feature-img-header:hover {
            transform: scale(1.05);
        }
        
        .profile-name {
            color: #2c3e50;
            margin: 10px 0 5px;
        }
        
        .profile-description {
            color: #7f8c8d;
        }
        
        .cio-tag {
            color: #3498db;
            font-weight: 600;
        }
        
        .feature-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            height: 100%;
            border-left: 4px solid #3498db;
            transition: transform 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .feature-title {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .feature-text {
            font-size: 14px;
            color: #555;
            line-height: 1.5;
            margin-bottom: 0;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header-top {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .header-actions {
                width: 100%;
                flex-direction: column;
                align-items: stretch;
            }
            
            .action-buttons {
                flex-direction: column;
                width: 100%;
            }
            
            .view-referrals-btn,
            .monitoring-btn,
            .criers-btn,
            .logout-btn {
                width: 100%;
                justify-content: center;
                padding: 8px 16px;
                font-size: 0.9rem;
            }
            
            .lider-card {
                flex-direction: column;
                text-align: center;
            }
            
            .lider-detalles {
                grid-template-columns: 1fr;
            }
            
            .detalle-item {
                justify-content: center;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .table-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box {
                width: 100%;
            }
            
            .search-box input {
                width: 100%;
            }
            
            table {
                font-size: 0.8rem;
            }
            
            td, th {
                padding: 8px;
            }
            
            .logo-clickable {
                max-width: 220px;
            }
            
            .modal-logo {
                max-width: 200px;
            }
        }
        
        @media (max-width: 480px) {
            .logo-clickable {
                max-width: 200px;
            }
            
            .modal-logo {
                max-width: 180px;
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
                    <h1><i class="fas fa-users"></i> Referenciados por Líder</h1>
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($usuario_logueado['nombres'] . ' ' . $usuario_logueado['apellidos']); ?></span>
                        <span class="badge" style="background: #f1c40f; color: #2c3e50; padding: 4px 8px; border-radius: 20px; font-size: 0.8rem; font-weight: 600;">Referenciador</span>
                    </div>
                </div>
                <div class="header-actions">
                    <div class="action-buttons">
                        <a href="monitoreo.php" class="view-referrals-btn">
                            <i class="fas fa-arrow-left"></i> Volver a Monitoreo
                        </a>
                        <a href="referenciador.php" class="monitoring-btn">
                            <i class="fas fa-home"></i> Inicio
                        </a>
                        <a href="logout.php" class="logout-btn">
                            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Breadcrumb -->
    <div class="breadcrumb-nav">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="referenciador.php"><i class="fas fa-home"></i> Inicio</a></li>
                <li class="breadcrumb-item"><a href="monitoreo.php"><i class="fas fa-chart-line"></i> Monitoreo</a></li>
                <li class="breadcrumb-item active"><i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($lider['nombres'] . ' ' . $lider['apellidos']); ?></li>
            </ol>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-container">
        <!-- Información del Líder -->
        <div class="lider-card">
            <div class="lider-avatar">
                <i class="fas fa-user-tie"></i>
            </div>
            <div class="lider-datos">
                <div class="lider-nombre">
                    <?php echo htmlspecialchars($lider['nombres'] . ' ' . $lider['apellidos']); ?>
                </div>
                <div class="lider-detalles">
                    <div class="detalle-item">
                        <i class="fas fa-id-card"></i>
                        <span>CC: <?php echo htmlspecialchars($lider['cc'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="detalle-item">
                        <i class="fas fa-phone"></i>
                        <span>Tel: <?php echo htmlspecialchars($lider['telefono'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="detalle-item">
                        <i class="fas fa-envelope"></i>
                        <span>Email: <?php echo htmlspecialchars($lider['correo'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="detalle-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Registro: <?php echo date('d/m/Y', strtotime($lider['fecha_creacion'])); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas Rápidas -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($totalReferenciados); ?></h3>
                    <p>Total Referenciados</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon votaron">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($votaronReferenciados); ?></h3>
                    <p>Ya Votaron</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon pendientes">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($pendientesReferenciados); ?></h3>
                    <p>Pendientes</p>
                </div>
            </div>
        </div>

        <!-- Barra de Progreso -->
        <div class="progress-section">
            <div class="progress-header">
                <span class="progress-title">Progreso de votación de los referenciados de este líder</span>
                <span class="progress-percentage"><?php echo $porcentajeReferenciados; ?>%</span>
            </div>
            <div class="progress-bar-container">
                <div class="progress-bar-fill" style="width: <?php echo $porcentajeReferenciados; ?>%;"></div>
            </div>
            <div class="progress-stats">
                <span><strong><?php echo number_format($votaronReferenciados); ?></strong> ya votaron</span>
                <span><strong><?php echo number_format($pendientesReferenciados); ?></strong> pendientes</span>
                <span>Total: <?php echo number_format($totalReferenciados); ?></span>
            </div>
        </div>

        <!-- Tabla de Referenciados -->
        <div class="table-container">
            <div class="table-header">
                <div class="table-title">
                    <i class="fas fa-list"></i>
                    <span>Listado de Referenciados del Líder</span>
                </div>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Buscar por nombre, cédula, teléfono..." onkeyup="filtrarTabla()">
                </div>
            </div>
            <div class="table-responsive">
                <?php if (empty($referenciados)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Este líder no tiene referenciados asignados</p>
                    </div>
                <?php else: ?>
                    <table id="tablaReferenciados">
                        <thead>
                            <tr>
                                <th>Nombre Completo</th>
                                <th>Cédula</th>
                                <th>Teléfono</th>
                                <th>Afinidad</th>
                                <th>Estado Voto</th>
                                <th>Fecha Registro</th>
                                <th>Fecha Voto</th>
                                <th>Certificado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($referenciados as $ref): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($ref['nombre'] . ' ' . $ref['apellido']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($ref['cedula']); ?></td>
                                <td><?php echo htmlspecialchars($ref['telefono'] ?: 'N/A'); ?></td>
                                <td>
                                    <span class="afinidad-badge">
                                        <?php echo $ref['afinidad']; ?>/5
                                    </span>
                                </td>
                                <td>
                                    <?php if ($ref['voto_registrado']): ?>
                                        <span class="voto-badge votado">
                                            <i class="fas fa-check-circle"></i> Votó
                                        </span>
                                    <?php else: ?>
                                        <span class="voto-badge pendiente">
                                            <i class="fas fa-clock"></i> Pendiente
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($ref['fecha_registro'])); ?></td>
                                <td>
                                    <?php if ($ref['fecha_voto']): ?>
                                        <?php echo date('d/m/Y H:i', strtotime($ref['fecha_voto'])); ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($ref['certificado_electoral'])): ?>
                                        <span class="certificado-badge" title="Número de certificado">
                                            <i class="fas fa-certificate"></i> 
                                            <?php echo htmlspecialchars($ref['certificado_electoral']); ?>
                                        </span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="system-footer">
        <div class="container text-center mb-3">
            <img src="imagenes/Logo-artguru.png" 
                alt="Logo" 
                class="logo-clickable"
                onclick="mostrarModalSistema()"
                title="Haz clic para ver información del sistema">
        </div>
        <div class="container text-center">
            <p>
                <strong>© 2026 Sistema de Gestión Política SGP.</strong> Puerto Gaitán - Meta<br>
                Módulo de SGA Sistema de Gestión Administrativa 2026 SGA Solución de Gestión Administrativa Enterprise Premium 1.0™
            </p>
        </div>
    </footer>

    <!-- Modal de Información del Sistema -->
    <div class="modal fade modal-system-info" id="modalSistema" tabindex="-1" aria-labelledby="modalSistemaLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalSistemaLabel">
                        <i class="fas fa-info-circle me-2"></i>Información del Sistema
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="modal-logo-container">
                        <img src="imagenes/Logo-artguru.png" alt="Logo del Sistema" class="modal-logo">
                    </div>
                    
                    <div class="licencia-info">
                        <div class="licencia-header">
                            <h6 class="licencia-title">Licencia Runtime</h6>
                            <span class="licencia-dias">
                                <?php echo $diasRestantes; ?> días restantes
                            </span>
                        </div>
                        
                        <div class="licencia-progress">
                            <div class="licencia-progress-bar <?php echo $barColor; ?>" 
                                style="width: <?php echo $porcentajeRestante; ?>%"
                                role="progressbar" 
                                aria-valuenow="<?php echo $porcentajeRestante; ?>" 
                                aria-valuemin="0" 
                                aria-valuemax="100">
                            </div>
                        </div>
                        
                        <div class="licencia-fecha">
                            <i class="fas fa-calendar-alt me-1"></i>
                            Instalado: <?php echo $fechaInstalacionFormatted; ?> | 
                            Válida hasta: <?php echo $validaHastaFormatted; ?>
                        </div>
                    </div>
                    
                    <div class="feature-image-container">
                        <img src="imagenes/ingeniero2.png" alt="Logo de Herramienta" class="feature-img-header">
                        <div class="profile-info mt-3">
                            <h4 class="profile-name"><strong>Rubén Darío González García</strong></h4>
                            <small class="profile-description">
                                Ingeniero de Sistemas, administrador de bases de datos, desarrollador de objeto OLE.<br>
                                Magister en Administración Pública.<br>
                                <span class="cio-tag"><strong>CIO de equipo soporte SISGONTECH</strong></span>
                            </small>
                        </div>
                    </div>
                    
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div class="feature-card">
                                <div class="feature-icon text-primary mb-3">
                                    <i class="fas fa-bolt fa-2x"></i>
                                </div>
                                <h5 class="feature-title">Efectividad de la Herramienta</h5>
                                <p class="feature-text">
                                    Reducción del 70% en el procesamiento manual de datos.
                                </p>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="feature-card">
                                <div class="feature-icon text-success mb-3">
                                    <i class="fas fa-database fa-2x"></i>
                                </div>
                                <h5 class="feature-title">Integridad de Datos</h5>
                                <p class="feature-text">
                                    Validación en tiempo real para eliminar duplicados.
                                </p>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="feature-card">
                                <div class="feature-icon text-warning mb-3">
                                    <i class="fas fa-chart-line fa-2x"></i>
                                </div>
                                <h5 class="feature-title">Monitoreo de Metas</h5>
                                <p class="feature-text">
                                    Seguimiento visual del cumplimiento de objetivos.
                                </p>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="feature-card">
                                <div class="feature-icon text-danger mb-3">
                                    <i class="fas fa-shield-alt fa-2x"></i>
                                </div>
                                <h5 class="feature-title">Seguridad Avanzada</h5>
                                <p class="feature-text">
                                    Control de acceso jerarquizado y trazabilidad total.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="https://sgp-sistema-de-gestion-politica.webnode.com.co/" 
                       target="_blank" 
                       class="btn btn-primary">
                        <i class="fas fa-external-link-alt me-1"></i> Uso SGP
                    </a>
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    function mostrarModalSistema() {
        const modal = new bootstrap.Modal(document.getElementById('modalSistema'));
        modal.show();
    }

    function cerrarModalSistema() {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalSistema'));
        if (modal) {
            modal.hide();
        }
    }

    // Función para filtrar la tabla
    function filtrarTabla() {
        const input = document.getElementById('searchInput');
        const filter = input.value.toLowerCase();
        const table = document.getElementById('tablaReferenciados');
        const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

        for (let i = 0; i < rows.length; i++) {
            const row = rows[i];
            const nombre = row.cells[0].textContent.toLowerCase();
            const cedula = row.cells[1].textContent.toLowerCase();
            const telefono = row.cells[2].textContent.toLowerCase();
            
            if (nombre.includes(filter) || cedula.includes(filter) || telefono.includes(filter)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    }
    </script>
</body>
</html>