<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../models/ReferenciadoModel.php';
require_once __DIR__ . '/../models/PregoneroModel.php';
require_once __DIR__ . '/../models/LiderModel.php';
require_once __DIR__ . '/../models/SistemaModel.php';

// Verificar si el usuario está logueado y es Referenciador
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'Referenciador') {
    header('Location: index.php');
    exit();
}

$pdo = Database::getConnection();
$usuarioModel = new UsuarioModel($pdo);
$referenciadoModel = new ReferenciadoModel($pdo);
$pregoneroModel = new PregoneroModel($pdo);
$liderModel = new LiderModel($pdo);
$sistemaModel = new SistemaModel($pdo);

$id_referenciador = $_SESSION['id_usuario'];

// Obtener datos del usuario logueado
$usuario_logueado = $usuarioModel->getUsuarioByIdActivo($id_referenciador);

// ============================================
// ESTADÍSTICAS DE REFERENCIADOS
// ============================================
$totalReferenciados = $referenciadoModel->countByReferenciador($id_referenciador);
$votaronReferenciados = $referenciadoModel->countVotaronByReferenciador($id_referenciador);
$pendientesReferenciados = $totalReferenciados - $votaronReferenciados;
$porcentajeReferenciados = $totalReferenciados > 0 ? round(($votaronReferenciados / $totalReferenciados) * 100, 2) : 0;

// ============================================
// ESTADÍSTICAS DE PREGONEROS
// ============================================
$totalPregoneros = $pregoneroModel->countByReferenciador($id_referenciador);
$votaronPregoneros = $pregoneroModel->countVotaronByReferenciador($id_referenciador);
$pendientesPregoneros = $totalPregoneros - $votaronPregoneros;
$porcentajePregoneros = $totalPregoneros > 0 ? round(($votaronPregoneros / $totalPregoneros) * 100, 2) : 0;

// ============================================
// ESTADÍSTICAS POR LÍDER (SOLO REFERENCIADOS)
// ============================================
$lideres = $liderModel->getActivosByReferenciador($id_referenciador);
$estadisticasLideres = [];

foreach ($lideres as $lider) {
    $id_lider = $lider['id_lider'];
    
    // Estadísticas de referenciados por este líder
    $totalRefLider = $referenciadoModel->countByLider($id_lider);
    $votaronRefLider = $referenciadoModel->countVotaronByLider($id_lider);
    $pendientesRefLider = $totalRefLider - $votaronRefLider;
    $porcentajeRefLider = $totalRefLider > 0 ? round(($votaronRefLider / $totalRefLider) * 100, 2) : 0;
    
    $estadisticasLideres[$id_lider] = [
        'nombre' => $lider['nombres'] . ' ' . $lider['apellidos'],
        'cedula' => $lider['cc'] ?? '',
        'telefono' => $lider['telefono'] ?? '',
        'referenciados' => [
            'total' => $totalRefLider,
            'votaron' => $votaronRefLider,
            'pendientes' => $pendientesRefLider,
            'porcentaje' => $porcentajeRefLider
        ]
    ];
}

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
    <title>Monitoreo de Votación - Referenciador - SGP</title>
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
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .view-referrals-btn:hover {
            background: linear-gradient(135deg, #495057, #343a40);
            color: white;
            transform: translateY(-2px);
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
            transform: translateY(-2px);
        }
        
        /* Badge para mostrar líderes asignados */
        .lideres-badge {
            background: linear-gradient(135deg, #6f42c1, #5a379c);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-left: 10px;
            box-shadow: 0 2px 5px rgba(111, 66, 193, 0.3);
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
        
        /* CONTADOR COMPACTO */
        .countdown-compact-container {
            max-width: 1200px;
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
            max-width: 1200px;
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
            margin-top: 5px;
        }
        
        .stat-highlight {
            font-weight: 600;
            color: var(--primary-color);
        }
        
        /* Líderes Table */
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
        
        .lider-info {
            display: flex;
            flex-direction: column;
        }
        
        .lider-nombre {
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .lider-detalle {
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
        
        .mini-stats {
            font-size: 0.8rem;
            color: #7f8c8d;
        }
        
        .badge-votaron {
            background-color: var(--success-color);
            color: white;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .badge-pendientes {
            background-color: var(--danger-color);
            color: white;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }
        
        /* Botón de ver detalles */
        .btn-ver {
            background: var(--secondary-color);
            color: white;
            padding: 5px 12px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }
        
        .btn-ver:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
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
        
        /* Modal de Información del Sistema */
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
            
            .lideres-badge {
                margin-left: 5px;
                font-size: 0.8rem;
                padding: 4px 10px;
            }
            
            .stats-summary {
                grid-template-columns: 1fr;
            }
            
            .countdown-compact {
                flex-direction: column;
                gap: 10px;
                text-align: center;
                padding: 15px;
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
            
            table {
                font-size: 0.8rem;
            }
            
            td, th {
                padding: 8px;
            }
            
            .mini-progress {
                width: 100px;
            }
            
            .logo-clickable {
                max-width: 220px;
            }
            
            .modal-logo {
                max-width: 200px;
            }
        }
        
        @media (max-width: 480px) {
            .countdown-compact-timer {
                font-size: 1.3rem;
            }
            
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
                    <h1><i class="fas fa-chart-line"></i> Monitoreo de Votación</h1>
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($usuario_logueado['nombres'] . ' ' . $usuario_logueado['apellidos']); ?></span>
                        <span class="lideres-badge">
                            <i class="fas fa-user-tie"></i> <?php echo count($lideres); ?> Líder(es)
                        </span>
                    </div>
                </div>
                <div class="header-actions">
                    <div class="action-buttons">
                        <a href="referenciador.php" class="view-referrals-btn">
                            <i class="fas fa-arrow-left"></i> Volver al Formulario
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
                <li class="breadcrumb-item active"><i class="fas fa-chart-line"></i> Monitoreo de Votación</li>
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
        <!-- Dashboard Header - Resumen General -->
        <div class="dashboard-header">
            <div class="dashboard-title">
                <i class="fas fa-chart-pie"></i>
                <span>Resumen General de Votación</span>
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

        <!-- Progreso de Referenciados -->
        <div class="progress-section">
            <div class="section-title">
                <i class="fas fa-chart-bar"></i>
                <span>Progreso de Referenciados</span>
            </div>
            <div class="progress-card">
                <div class="progress-header">
                    <span class="progress-title">Total de referenciados que han votado</span>
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
        </div>

        <!-- Progreso de Pregoneros -->
        <div class="progress-section">
            <div class="section-title">
                <i class="fas fa-chart-bar" style="color: #f39c12;"></i>
                <span>Progreso de Pregoneros</span>
            </div>
            <div class="progress-card">
                <div class="progress-header">
                    <span class="progress-title">Total de pregoneros que han votado</span>
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

        <!-- Listado de Líderes - SOLO REFERENCIADOS -->
        <div class="table-container">
            <div class="table-header">
                <div class="table-title">
                    <i class="fas fa-user-tie"></i>
                    <span>Líderes Asignados - Progreso de Referenciados</span>
                </div>
            </div>
            <div class="table-responsive">
                <?php if (empty($lideres)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-user-tie fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No tiene líderes asignados actualmente</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Líder</th>
                                <th>Contacto</th>
                                <th colspan="2">Referenciados</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($estadisticasLideres as $id_lider => $stats): ?>
                            <tr>
                                <td>
                                    <div class="lider-info">
                                        <span class="lider-nombre"><?php echo htmlspecialchars($stats['nombre']); ?></span>
                                        <span class="lider-detalle">CC: <?php echo htmlspecialchars($stats['cedula'] ?: 'N/A'); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="lider-info">
                                        <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($stats['telefono'] ?: 'N/A'); ?></span>
                                    </div>
                                </td>
                                
                                <!-- Referenciados del líder -->
                                <td class="mini-progress">
                                    <div class="mini-progress-bar">
                                        <div class="mini-progress-fill" style="width: <?php echo $stats['referenciados']['porcentaje']; ?>%;"></div>
                                    </div>
                                    <div class="mini-stats">
                                        <?php echo $stats['referenciados']['votaron']; ?>/<?php echo $stats['referenciados']['total']; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge-votaron"><?php echo $stats['referenciados']['votaron']; ?> votaron</span>
                                    <?php if ($stats['referenciados']['pendientes'] > 0): ?>
                                        <span class="badge-pendientes ms-1"><?php echo $stats['referenciados']['pendientes']; ?> pend.</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <a href="ver_referenciados_por_lider.php?id_lider=<?php echo $id_lider; ?>" class="btn-ver">
                                        <i class="fas fa-eye"></i> Ver detalles
                                    </a>
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
    <script src="js/contador.js"></script>
    <script src="js/modal-sistema.js"></script>
    
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
    </script>
</body>
</html>