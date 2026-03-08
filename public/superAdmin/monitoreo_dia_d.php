<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../models/ReferenciadoModel.php';
require_once __DIR__ . '/../../models/PregoneroModel.php';
require_once __DIR__ . '/../../models/LiderModel.php';
require_once __DIR__ . '/../../models/SistemaModel.php';

// Verificar si el usuario está logueado y es SuperAdmin
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    header('Location: ../index.php');
    exit();
}

$pdo = Database::getConnection();
$usuarioModel = new UsuarioModel($pdo);
$referenciadoModel = new ReferenciadoModel($pdo);
$pregoneroModel = new PregoneroModel($pdo);
$liderModel = new LiderModel($pdo);
$sistemaModel = new SistemaModel($pdo);

$id_usuario = $_SESSION['id_usuario'];

// Obtener datos del usuario logueado
$usuario_logueado = $usuarioModel->getUsuarioById($id_usuario);

// ============================================
// ESTADÍSTICAS GLOBALES
// ============================================
// Referenciados
$totalReferenciados = $referenciadoModel->countReferenciadosActivos();
$votaronReferenciados = $referenciadoModel->contarVotantesRegistrados();
$pendientesReferenciados = $totalReferenciados - $votaronReferenciados;
$porcentajeReferenciados = $totalReferenciados > 0 ? round(($votaronReferenciados / $totalReferenciados) * 100, 2) : 0;

// Pregoneros
$totalPregoneros = $pregoneroModel->contarPregonerosActivos();
$votaronPregoneros = $pregoneroModel->contarPregonerosVotaron();
$pendientesPregoneros = $totalPregoneros - $votaronPregoneros;
$porcentajePregoneros = $totalPregoneros > 0 ? round(($votaronPregoneros / $totalPregoneros) * 100, 2) : 0;

// ============================================
// LISTA DE REFERENCIADORES PARA FILTROS
// ============================================
$referenciadores = $usuarioModel->getReferenciadoresActivos();

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
    <title>Monitoreo Día D - SuperAdmin - SGP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* ========== ESTILOS GENERALES ========== */
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
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .header-title {
            display: flex;
            align-items: center;
            gap: 10px;
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
        
        .logout-btn {
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
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        /* Breadcrumb */
        .breadcrumb-nav {
            max-width: 1400px;
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
        
        /* Contador compacto */
        .countdown-compact-container {
            max-width: 1400px;
            margin: 0 auto 20px;
            padding: 0 15px;
        }
        
        .countdown-compact {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            border-radius: 10px;
            padding: 15px 20px;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 3px 10px rgba(0,0,0,0.15);
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .countdown-compact-title {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
        }
        
        .countdown-compact-title i {
            color: #f1c40f;
            font-size: 1.2rem;
        }
        
        .countdown-compact-title span {
            font-weight: 600;
            font-size: 1rem;
        }
        
        .countdown-compact-timer {
            flex: 2;
            text-align: center;
            font-family: 'Segoe UI', monospace;
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: 1px;
        }
        
        .countdown-compact-timer span {
            display: inline-block;
            min-width: 35px;
            text-align: center;
        }
        
        .countdown-compact-date {
            flex: 1;
            text-align: right;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            font-size: 0.9rem;
            color: rgba(255,255,255,0.9);
        }
        
        .countdown-compact-date i {
            color: #f1c40f;
        }
        
        /* Main container */
        .main-container {
            max-width: 1400px;
            margin: 0 auto 30px;
            padding: 0 15px;
        }
        
        /* Dashboard Header */
        .dashboard-header {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }
        
        .dashboard-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f5f9;
        }
        
        .dashboard-title i {
            color: var(--secondary-color);
        }
        
        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .stat-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: transform 0.3s;
            border: 1px solid #eaeaea;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
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
        
        .stat-icon.referenciados {
            background: linear-gradient(135deg, var(--secondary-color), #2980b9);
            color: white;
        }
        
        .stat-icon.pregoneros {
            background: linear-gradient(135deg, var(--warning-color), #e67e22);
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
        
        /* Progress Cards */
        .progress-section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title i {
            color: var(--secondary-color);
        }
        
        .progress-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
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
            height: 20px;
            background-color: #ecf0f1;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--secondary-color), #2980b9);
            border-radius: 10px;
            transition: width 0.5s ease;
        }
        
        .progress-stats {
            display: flex;
            justify-content: space-between;
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-top: 5px;
        }
        
        .stat-highlight {
            font-weight: 600;
            color: var(--primary-color);
        }
        
        /* Secciones de monitoreo */
        .monitoreo-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
        }
        
        .monitoreo-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f1f5f9;
        }
        
        .monitoreo-header h3 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .monitoreo-header h3 i {
            color: var(--secondary-color);
        }
        
        /* Botones de acción */
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-action {
            background: var(--secondary-color);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
        }
        
        .btn-action:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            color: white;
        }
        
        .btn-action.active {
            background: var(--success-color);
        }
        
        .btn-action.warning {
            background: var(--warning-color);
        }
        
        .btn-action.warning:hover {
            background: #e67e22;
        }
        
        .btn-action.danger {
            background: var(--danger-color);
        }
        
        .btn-action.danger:hover {
            background: #c0392b;
        }
        
        /* Filtros */
        .filter-container {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-end;
            gap: 15px;
            flex-wrap: wrap;
            border: 1px solid #eaeaea;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: var(--primary-color);
            font-size: 0.9rem;
        }
        
        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        
        /* Tablas */
        .table-container {
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
            padding: 12px;
            border-bottom: 1px solid #eaeaea;
            vertical-align: middle;
        }
        
        .referenciador-info {
            display: flex;
            flex-direction: column;
        }
        
        .referenciador-nombre {
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .referenciador-cedula {
            font-size: 0.8rem;
            color: #7f8c8d;
        }
        
        .mini-progress {
            width: 150px;
        }
        
        .mini-progress-bar {
            width: 100%;
            height: 8px;
            background-color: #ecf0f1;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 5px;
        }
        
        .mini-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--success-color), #2ecc71);
            border-radius: 4px;
            transition: width 0.3s;
        }
        
        .badge-votaron {
            background-color: var(--success-color);
            color: white;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-pendientes {
            background-color: var(--danger-color);
            color: white;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        /* Gráficas */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 20px;
        }
        
        .chart-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            border: 1px solid #eaeaea;
        }
        
        .chart-card h4 {
            margin: 0 0 15px 0;
            color: var(--primary-color);
            font-size: 1rem;
            font-weight: 600;
            text-align: center;
        }
        
        .chart-container {
            position: relative;
            height: 250px;
            width: 100%;
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
        
        .modal-logo {
            max-width: 300px;
            height: auto;
            margin: 0 auto;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
            border: 3px solid #fff;
            background: white;
        }
        
        .feature-img-header {
            width: 190px;
            height: 190px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #ffffff;
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .feature-img-header:hover {
            transform: scale(1.05);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header-top {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .user-info {
                order: 1;
            }
            
            .logout-btn {
                order: 2;
                align-self: flex-end;
            }
            
            .countdown-compact {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            
            .countdown-compact-timer {
                order: 2;
                width: 100%;
            }
            
            .countdown-compact-title {
                order: 1;
                justify-content: center;
                width: 100%;
            }
            
            .countdown-compact-date {
                order: 3;
                justify-content: center;
                width: 100%;
            }
            
            .stats-summary {
                grid-template-columns: 1fr;
            }
            
            .charts-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-container {
                flex-direction: column;
                align-items: stretch;
            }
            
            .monitoreo-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .action-buttons {
                width: 100%;
            }
            
            .btn-action {
                flex: 1;
                justify-content: center;
            }
            
            table {
                font-size: 0.8rem;
            }
            
            td, th {
                padding: 8px;
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
                    <h1><i class="fas fa-calendar-check"></i> Monitoreo Día D</h1>
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($usuario_logueado['nombres'] . ' ' . $usuario_logueado['apellidos']); ?></span>
                        <span class="badge" style="background: #f1c40f; color: #2c3e50; padding: 4px 8px; border-radius: 20px; font-size: 0.8rem; font-weight: 600;">SuperAdmin</span>
                    </div>
                </div>
                <div style="display: flex; gap: 10px;">
                    <a href="superadmin_auditoria.php" class="logout-btn" style="background: rgba(255,255,255,0.15);">
                        <i class="fas fa-arrow-left"></i> Volver a Auditoría
                    </a>
                    <a href="../logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Breadcrumb -->
    <div class="breadcrumb-nav">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../superadmin_dashboard.php"><i class="fas fa-home"></i> Panel Super Admin</a></li>
                <li class="breadcrumb-item"><a href="superadmin_auditoria.php"><i class="fas fa-clipboard-check"></i> Auditoría</a></li>
                <li class="breadcrumb-item active"><i class="fas fa-calendar-check"></i> Monitoreo Día D</li>
            </ol>
        </nav>
    </div>

    <!-- Contador compacto -->
    <div class="countdown-compact-container">
        <div class="countdown-compact">
            <div class="countdown-compact-title">
                <i class="fas fa-hourglass-half"></i>
                <span>Elecciones Legislativas 2026</span>
            </div>
            <div class="countdown-compact-timer">
                <span id="compact-days">00</span>d 
                <span id="compact-hours">00</span>h 
                <span id="compact-minutes">00</span>m 
                <span id="compact-seconds">00</span>s
            </div>
            <div class="countdown-compact-date">
                <i class="fas fa-calendar-alt"></i>
                8 Marzo 2026
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-container">
        <!-- Dashboard Header - Estadísticas Globales -->
        <div class="dashboard-header">
            <div class="dashboard-title">
                <i class="fas fa-chart-pie"></i>
                <span>Resumen Global de Votación</span>
            </div>
            <div class="stats-summary">
                <!-- Referenciados -->
                <div class="stat-card">
                    <div class="stat-icon referenciados">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($totalReferenciados); ?></h3>
                        <p>Total Referenciados</p>
                    </div>
                </div>
                
                <!-- Votaron Referenciados -->
                <div class="stat-card">
                    <div class="stat-icon votaron">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($votaronReferenciados); ?></h3>
                        <p>Ya Votaron (Referenciados)</p>
                    </div>
                </div>
                
                <!-- Pregoneros -->
                <div class="stat-card">
                    <div class="stat-icon pregoneros">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($totalPregoneros); ?></h3>
                        <p>Total Pregoneros</p>
                    </div>
                </div>
                
                <!-- Votaron Pregoneros -->
                <div class="stat-card">
                    <div class="stat-icon votaron">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($votaronPregoneros); ?></h3>
                        <p>Ya Votaron (Pregoneros)</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Barras de Progreso Globales -->
        <div class="progress-section">
            <div class="section-title">
                <i class="fas fa-chart-bar"></i>
                <span>Progreso Global de Votación</span>
            </div>
            
            <!-- Progreso de Referenciados -->
            <div class="progress-card" style="margin-bottom: 20px;">
                <div class="progress-header">
                    <span class="progress-title">Progreso de Referenciados</span>
                    <span class="progress-percentage"><?php echo $porcentajeReferenciados; ?>%</span>
                </div>
                <div class="progress-bar-container">
                    <div class="progress-bar-fill" style="width: <?php echo $porcentajeReferenciados; ?>%;"></div>
                </div>
                <div class="progress-stats">
                    <span><span class="stat-highlight"><?php echo number_format($votaronReferenciados); ?></span> ya votaron</span>
                    <span><span class="stat-highlight"><?php echo number_format($pendientesReferenciados); ?></span> pendientes</span>
                    <span>Total: <?php echo number_format($totalReferenciados); ?></span>
                </div>
            </div>
            
            <!-- Progreso de Pregoneros -->
            <div class="progress-card">
                <div class="progress-header">
                    <span class="progress-title">Progreso de Pregoneros</span>
                    <span class="progress-percentage" style="color: #f39c12;"><?php echo $porcentajePregoneros; ?>%</span>
                </div>
                <div class="progress-bar-container">
                    <div class="progress-bar-fill" style="width: <?php echo $porcentajePregoneros; ?>%; background: linear-gradient(90deg, #f39c12, #e67e22);"></div>
                </div>
                <div class="progress-stats">
                    <span><span class="stat-highlight"><?php echo number_format($votaronPregoneros); ?></span> ya votaron</span>
                    <span><span class="stat-highlight"><?php echo number_format($pendientesPregoneros); ?></span> pendientes</span>
                    <span>Total: <?php echo number_format($totalPregoneros); ?></span>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 1: MONITOREO GENERAL (Referenciadores y su avance) -->
        <div class="monitoreo-section" id="monitoreo-general">
            <div class="monitoreo-header">
                <h3>
                    <i class="fas fa-users-cog"></i>
                    Monitoreo General - Avance de Referenciadores
                </h3>
                <div class="action-buttons">
                    <button class="btn-action active" onclick="cargarMonitoreoGeneral()">
                        <i class="fas fa-sync-alt"></i> Actualizar
                    </button>
                </div>
            </div>
            
            <div class="table-container" id="tabla-referenciadores">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2">Cargando datos de referenciadores...</p>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 2: MONITOREO EN DETALLE (Filtro por referenciador y sus líderes) -->
        <div class="monitoreo-section" id="monitoreo-detalle">
            <div class="monitoreo-header">
                <h3>
                    <i class="fas fa-filter"></i>
                    Monitoreo en Detalle - Líderes por Referenciador
                </h3>
                <div class="action-buttons">
                    <button class="btn-action warning" onclick="cargarMonitoreoDetalle()">
                        <i class="fas fa-search"></i> Aplicar Filtro
                    </button>
                </div>
            </div>
            
            <div class="filter-container">
                <div class="filter-group">
                    <label for="selectReferenciador">Seleccionar Referenciador</label>
                    <select id="selectReferenciador" class="form-select">
                        <option value="">Todos los referenciadores</option>
                        <?php foreach ($referenciadores as $ref): ?>
                            <option value="<?php echo $ref['id_usuario']; ?>">
                                <?php echo htmlspecialchars($ref['nombres'] . ' ' . $ref['apellidos'] . ' - ' . ($ref['cedula'] ?? '')); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="table-container" id="tabla-lideres-detalle">
                <div class="text-center py-4">
                    <p class="text-muted">Seleccione un referenciador para ver el detalle de sus líderes</p>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 3: IA COMPORTAMIENTO (Gráficas) -->
        <div class="monitoreo-section" id="ia-comportamiento">
            <div class="monitoreo-header">
                <h3>
                    <i class="fas fa-robot"></i>
                    IA Comportamiento - Análisis de Votación
                </h3>
                <div class="action-buttons">
                    <button class="btn-action danger" onclick="actualizarGraficas()">
                        <i class="fas fa-chart-line"></i> Actualizar Gráficas
                    </button>
                </div>
            </div>
            
            <!-- FILTRO POR REFERENCIADOR PARA GRÁFICAS SUPERIORES -->
            <div class="filter-container" style="margin-bottom: 30px;">
                <div class="filter-group">
                    <label for="selectReferenciadorGraficas">
                        <i class="fas fa-user-tie"></i> Filtrar gráficas por hora por Referenciador
                    </label>
                    <select id="selectReferenciadorGraficas" class="form-select">
                        <option value="">Todos los referenciadores (vista general)</option>
                        <?php foreach ($referenciadores as $ref): ?>
                            <option value="<?php echo $ref['id_usuario']; ?>">
                                <?php echo htmlspecialchars($ref['nombres'] . ' ' . $ref['apellidos'] . ' - ' . ($ref['cedula'] ?? '')); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group" style="flex: 0 0 auto;">
                    <button class="btn-action" onclick="aplicarFiltroReferenciador()" style="margin-top: 24px;">
                        <i class="fas fa-filter"></i> Aplicar filtro
                    </button>
                </div>
            </div>
            
            <div class="charts-grid">
                <!-- Gráfica 1: Referidos que votaron por hora (filtrable por referenciador) -->
                <div class="chart-card">
                    <h4><i class="fas fa-clock"></i> Referidos que Votaron por Hora</h4>
                    <div class="chart-container">
                        <canvas id="chartReferidosHora"></canvas>
                    </div>
                </div>
                
                <!-- Gráfica 2: Pregoneros que votaron por hora (filtrable por referenciador) -->
                <div class="chart-card">
                    <h4><i class="fas fa-clock"></i> Pregoneros que Votaron por Hora</h4>
                    <div class="chart-container">
                        <canvas id="chartPregonerosHora"></canvas>
                    </div>
                </div>
                
                <!-- Gráfica 3: Avance GENERAL de Votos Referidos (SIN FILTRO) -->
                <div class="chart-card">
                    <h4><i class="fas fa-chart-line"></i> Avance GENERAL de Votos Referidos</h4>
                    <div class="chart-container">
                        <canvas id="chartAvanceReferidos"></canvas>
                    </div>
                </div>
                
                <!-- Gráfica 4: Avance GENERAL de Votos Pregoneros (SIN FILTRO) -->
                <div class="chart-card">
                    <h4><i class="fas fa-chart-line"></i> Avance GENERAL de Votos Pregoneros</h4>
                    <div class="chart-container">
                        <canvas id="chartAvancePregoneros"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="system-footer">
        <div class="container text-center mb-3">
            <img src="../imagenes/Logo-artguru.png" 
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
                        <img src="../imagenes/Logo-artguru.png" alt="Logo del Sistema" class="modal-logo">
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
                        <img src="../imagenes/ingeniero2.png" alt="Logo de Herramienta" class="feature-img-header">
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
    <script src="../js/contador.js"></script>
    <script src="../js/modal-sistema.js"></script>
    
    <script>
        // Variables globales para las gráficas
        let chartReferidosHora, chartPregonerosHora, chartAvanceReferidos, chartAvancePregoneros;
        
        $(document).ready(function() {
            // Cargar monitoreo general al iniciar
            cargarMonitoreoGeneral();
            
            // Inicializar gráficas
            cargarDatosIniciales();
        });
        
        // ============================================
        // FUNCIONES PARA MONITOREO GENERAL
        // ============================================
        function cargarMonitoreoGeneral() {
            $('#tabla-referenciadores').html(`
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2">Cargando datos de referenciadores...</p>
                </div>
            `);
            
            $.ajax({
                url: '../ajax/get_referenciadores_avance.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        renderTablaReferenciadores(response.data);
                    } else {
                        showNotification('Error al cargar datos: ' + (response.error || 'Error desconocido'), 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error AJAX:', error);
                    showNotification('Error de conexión al servidor', 'error');
                    $('#tabla-referenciadores').html('<p class="text-center text-danger">Error al cargar datos</p>');
                }
            });
        }
        
        function renderTablaReferenciadores(data) {
            if (!data || data.length === 0) {
                $('#tabla-referenciadores').html('<p class="text-center py-4 text-muted">No hay referenciadores registrados</p>');
                return;
            }
            
            let html = `
                <table>
                    <thead>
                        <tr>
                            <th>Referenciador</th>
                            <th>Contacto</th>
                            <th>Total Referidos</th>
                            <th>Votaron</th>
                            <th>Pendientes</th>
                            <th>Progreso</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            data.forEach(function(ref) {
                const porcentaje = ref.total > 0 ? ((ref.votaron / ref.total) * 100).toFixed(2) : 0;
                
                html += `
                    <tr>
                        <td>
                            <div class="referenciador-info">
                                <span class="referenciador-nombre">${escapeHtml(ref.nombres)} ${escapeHtml(ref.apellidos)}</span>
                                <span class="referenciador-cedula">CC: ${escapeHtml(ref.cedula || 'N/A')}</span>
                            </div>
                        </td>
                        <td>
                            <div><i class="fas fa-phone"></i> ${escapeHtml(ref.telefono || 'N/A')}</div>
                            <div><small>${escapeHtml(ref.correo || '')}</small></div>
                        </td>
                        <td class="text-center"><strong>${ref.total}</strong></td>
                        <td class="text-center"><span class="badge-votaron">${ref.votaron}</span></td>
                        <td class="text-center"><span class="badge-pendientes">${ref.pendientes}</span></td>
                        <td class="mini-progress">
                            <div class="mini-progress-bar">
                                <div class="mini-progress-fill" style="width: ${porcentaje}%;"></div>
                            </div>
                            <div class="mini-stats">${porcentaje}%</div>
                        </td>
                        <td>
                            <button class="btn-action" style="padding: 4px 8px; font-size: 0.8rem;" onclick="filtrarPorReferenciador(${ref.id_usuario})">
                                <i class="fas fa-eye"></i> Ver líderes
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            html += '</tbody></table>';
            $('#tabla-referenciadores').html(html);
        }
        
        // ============================================
        // FUNCIONES PARA MONITOREO EN DETALLE
        // ============================================
        function cargarMonitoreoDetalle() {
            const idReferenciador = $('#selectReferenciador').val();
            
            if (!idReferenciador) {
                showNotification('Por favor seleccione un referenciador', 'warning');
                return;
            }
            
            $('#tabla-lideres-detalle').html(`
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2">Cargando líderes del referenciador...</p>
                </div>
            `);
            
            $.ajax({
                url: '../ajax/get_lideres_avance.php',
                method: 'GET',
                data: { id_referenciador: idReferenciador },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        renderTablaLideres(response.data, response.referenciador);
                    } else {
                        showNotification('Error al cargar datos: ' + (response.error || 'Error desconocido'), 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error AJAX:', error);
                    showNotification('Error de conexión al servidor', 'error');
                    $('#tabla-lideres-detalle').html('<p class="text-center text-danger">Error al cargar datos</p>');
                }
            });
        }
        
        function renderTablaLideres(data, referenciador) {
            if (!data || data.length === 0) {
                $('#tabla-lideres-detalle').html(`
                    <p class="text-center py-4 text-muted">
                        El referenciador ${escapeHtml(referenciador.nombres)} ${escapeHtml(referenciador.apellidos)} no tiene líderes asignados
                    </p>
                `);
                return;
            }
            
            let html = `
                <p class="mb-3"><strong>Referenciador:</strong> ${escapeHtml(referenciador.nombres)} ${escapeHtml(referenciador.apellidos)} (${escapeHtml(referenciador.cedula || 'N/A')})</p>
                <table>
                    <thead>
                        <tr>
                            <th>Líder</th>
                            <th>Contacto</th>
                            <th>Total Referidos</th>
                            <th>Votaron</th>
                            <th>Pendientes</th>
                            <th>Progreso</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            data.forEach(function(lider) {
                const porcentaje = lider.total > 0 ? ((lider.votaron / lider.total) * 100).toFixed(2) : 0;
                
                html += `
                    <tr>
                        <td>
                            <div class="referenciador-info">
                                <span class="referenciador-nombre">${escapeHtml(lider.nombres)} ${escapeHtml(lider.apellidos)}</span>
                                <span class="referenciador-cedula">CC: ${escapeHtml(lider.cedula || 'N/A')}</span>
                            </div>
                        </td>
                        <td>
                            <div><i class="fas fa-phone"></i> ${escapeHtml(lider.telefono || 'N/A')}</div>
                        </td>
                        <td class="text-center"><strong>${lider.total}</strong></td>
                        <td class="text-center"><span class="badge-votaron">${lider.votaron}</span></td>
                        <td class="text-center"><span class="badge-pendientes">${lider.pendientes}</span></td>
                        <td class="mini-progress">
                            <div class="mini-progress-bar">
                                <div class="mini-progress-fill" style="width: ${porcentaje}%;"></div>
                            </div>
                            <div class="mini-stats">${porcentaje}%</div>
                        </td>
                    </tr>
                `;
            });
            
            html += '</tbody></table>';
            $('#tabla-lideres-detalle').html(html);
        }
        
        function filtrarPorReferenciador(id) {
            $('#selectReferenciador').val(id);
            cargarMonitoreoDetalle();
            
            // Hacer scroll a la sección de detalle
            $('html, body').animate({
                scrollTop: $('#monitoreo-detalle').offset().top - 100
            }, 500);
        }
        
        // ============================================
        // FUNCIONES PARA GRÁFICAS (IA COMPORTAMIENTO)
        // ============================================
        function cargarDatosIniciales() {
            // Cargar gráficas de hora (sin filtro inicial)
            cargarGraficasHora(0);
            
            // Cargar gráficas generales de avance
            cargarGraficasGenerales();
        }
        
        function cargarGraficasHora(idReferenciador = 0) {
            let url = '../ajax/get_datos_graficas_filtro.php';
            if (idReferenciador > 0) {
                url += '?id_referenciador=' + idReferenciador;
            }
            
            $.ajax({
                url: url,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        if (!chartReferidosHora) {
                            // Crear gráfica de referidos por hora
                            const ctx1 = document.getElementById('chartReferidosHora').getContext('2d');
                            chartReferidosHora = new Chart(ctx1, {
                                type: 'bar',
                                data: {
                                    labels: response.referidosVotaronHora.map(item => item.hora + ':00'),
                                    datasets: [{
                                        label: 'Referidos que votaron',
                                        data: response.referidosVotaronHora.map(item => item.cantidad),
                                        backgroundColor: 'rgba(52, 152, 219, 0.7)',
                                        borderColor: 'rgba(52, 152, 219, 1)',
                                        borderWidth: 1
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            ticks: {
                                                stepSize: 1,
                                                callback: function(value) {
                                                    if (Number.isInteger(value)) return value;
                                                }
                                            }
                                        }
                                    }
                                }
                            });
                            
                            // Crear gráfica de pregoneros por hora
                            const ctx2 = document.getElementById('chartPregonerosHora').getContext('2d');
                            chartPregonerosHora = new Chart(ctx2, {
                                type: 'bar',
                                data: {
                                    labels: response.pregonerosVotaronHora.map(item => item.hora + ':00'),
                                    datasets: [{
                                        label: 'Pregoneros que votaron',
                                        data: response.pregonerosVotaronHora.map(item => item.cantidad),
                                        backgroundColor: 'rgba(243, 156, 18, 0.7)',
                                        borderColor: 'rgba(243, 156, 18, 1)',
                                        borderWidth: 1
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            ticks: {
                                                stepSize: 1,
                                                callback: function(value) {
                                                    if (Number.isInteger(value)) return value;
                                                }
                                            }
                                        }
                                    }
                                }
                            });
                        } else {
                            // Actualizar gráficas existentes
                            chartReferidosHora.data.labels = response.referidosVotaronHora.map(item => item.hora + ':00');
                            chartReferidosHora.data.datasets[0].data = response.referidosVotaronHora.map(item => item.cantidad);
                            chartReferidosHora.update();
                            
                            chartPregonerosHora.data.labels = response.pregonerosVotaronHora.map(item => item.hora + ':00');
                            chartPregonerosHora.data.datasets[0].data = response.pregonerosVotaronHora.map(item => item.cantidad);
                            chartPregonerosHora.update();
                        }
                        
                        let mensaje = idReferenciador > 0 ? 'Gráficas filtradas por referenciador' : 'Gráficas sin filtro (todos los referenciadores)';
                        console.log(mensaje);
                    }
                },
                error: function(error) {
                    console.error('Error al cargar gráficas por hora:', error);
                }
            });
        }
        
        function cargarGraficasGenerales() {
            $.ajax({
                url: '../ajax/get_datos_graficas.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        if (!chartAvanceReferidos) {
                            // Crear gráfica de avance referidos
                            const ctx3 = document.getElementById('chartAvanceReferidos').getContext('2d');
                            chartAvanceReferidos = new Chart(ctx3, {
                                type: 'line',
                                data: {
                                    labels: response.avanceVotosReferidos.map(item => item.fecha),
                                    datasets: [
                                        {
                                            label: 'Votos del día',
                                            data: response.avanceVotosReferidos.map(item => item.votos_dia),
                                            borderColor: 'rgba(52, 152, 219, 0.5)',
                                            backgroundColor: 'rgba(52, 152, 219, 0.1)',
                                            type: 'bar',
                                            yAxisID: 'y'
                                        },
                                        {
                                            label: 'Votos acumulados',
                                            data: response.avanceVotosReferidos.map(item => item.votos_acumulados),
                                            borderColor: 'rgba(46, 204, 113, 1)',
                                            backgroundColor: 'rgba(46, 204, 113, 0.1)',
                                            type: 'line',
                                            tension: 0.4,
                                            fill: false,
                                            yAxisID: 'y1'
                                        }
                                    ]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            position: 'left',
                                            title: {
                                                display: true,
                                                text: 'Votos por día'
                                            }
                                        },
                                        y1: {
                                            beginAtZero: true,
                                            position: 'right',
                                            grid: {
                                                drawOnChartArea: false
                                            },
                                            title: {
                                                display: true,
                                                text: 'Votos acumulados'
                                            }
                                        }
                                    }
                                }
                            });
                            
                            // Crear gráfica de avance pregoneros
                            const ctx4 = document.getElementById('chartAvancePregoneros').getContext('2d');
                            chartAvancePregoneros = new Chart(ctx4, {
                                type: 'line',
                                data: {
                                    labels: response.avanceVotosPregoneros.map(item => item.fecha),
                                    datasets: [
                                        {
                                            label: 'Votos del día',
                                            data: response.avanceVotosPregoneros.map(item => item.votos_dia),
                                            borderColor: 'rgba(243, 156, 18, 0.5)',
                                            backgroundColor: 'rgba(243, 156, 18, 0.1)',
                                            type: 'bar',
                                            yAxisID: 'y'
                                        },
                                        {
                                            label: 'Votos acumulados',
                                            data: response.avanceVotosPregoneros.map(item => item.votos_acumulados),
                                            borderColor: 'rgba(46, 204, 113, 1)',
                                            backgroundColor: 'rgba(46, 204, 113, 0.1)',
                                            type: 'line',
                                            tension: 0.4,
                                            fill: false,
                                            yAxisID: 'y1'
                                        }
                                    ]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            position: 'left',
                                            title: {
                                                display: true,
                                                text: 'Votos por día'
                                            }
                                        },
                                        y1: {
                                            beginAtZero: true,
                                            position: 'right',
                                            grid: {
                                                drawOnChartArea: false
                                            },
                                            title: {
                                                display: true,
                                                text: 'Votos acumulados'
                                            }
                                        }
                                    }
                                }
                            });
                        } else {
                            // Actualizar gráficas existentes
                            chartAvanceReferidos.data.labels = response.avanceVotosReferidos.map(item => item.fecha);
                            chartAvanceReferidos.data.datasets[0].data = response.avanceVotosReferidos.map(item => item.votos_dia);
                            chartAvanceReferidos.data.datasets[1].data = response.avanceVotosReferidos.map(item => item.votos_acumulados);
                            chartAvanceReferidos.update();
                            
                            chartAvancePregoneros.data.labels = response.avanceVotosPregoneros.map(item => item.fecha);
                            chartAvancePregoneros.data.datasets[0].data = response.avanceVotosPregoneros.map(item => item.votos_dia);
                            chartAvancePregoneros.data.datasets[1].data = response.avanceVotosPregoneros.map(item => item.votos_acumulados);
                            chartAvancePregoneros.update();
                        }
                    }
                },
                error: function(error) {
                    console.error('Error al cargar gráficas generales:', error);
                }
            });
        }
        
        function aplicarFiltroReferenciador() {
            const idReferenciador = $('#selectReferenciadorGraficas').val();
            if (idReferenciador) {
                cargarGraficasHora(idReferenciador);
                showNotification('Gráficas filtradas por el referenciador seleccionado', 'success');
            } else {
                cargarGraficasHora(0);
                showNotification('Mostrando todos los referenciadores', 'info');
            }
        }
        
        function actualizarGraficas() {
            showNotification('Actualizando gráficas...', 'info');
            cargarGraficasGenerales();
            aplicarFiltroReferenciador(); // Esto recarga las gráficas de hora con el filtro actual
        }
        
        // ============================================
        // FUNCIONES HELPER
        // ============================================
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function showNotification(message, type) {
            // Puedes implementar un sistema de notificaciones aquí
            console.log(message, type);
        }
        
        function mostrarModalSistema() {
            const modal = new bootstrap.Modal(document.getElementById('modalSistema'));
            modal.show();
        }
    </script>
</body>
</html>