<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../models/SistemaModel.php';

// Verificar si el usuario está logueado y es SuperAdmin
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    header('Location: ../index.php');
    exit();
}

$pdo = Database::getConnection();
$usuarioModel = new UsuarioModel($pdo);
$sistemaModel = new SistemaModel($pdo);

// Obtener datos del usuario logueado
$usuario_logueado = $usuarioModel->getUsuarioById($_SESSION['id_usuario']);

// Obtener estadísticas reales
// En tu primera vista (data_referidos.php), cambia:
try {
    // 1. Total de referidos ACTIVOS (solo activos)
    $queryTotalReferidos = "SELECT COUNT(*) as total_referidos FROM referenciados WHERE activo = true";
    $stmtTotal = $pdo->query($queryTotalReferidos);
    $resultTotal = $stmtTotal->fetch();
    $totalReferidos = $resultTotal['total_referidos'] ?? 0;

    // 2. Suma de todos los topes de usuarios ACTIVOS - ¡CAMBIAR AQUÍ!
    // Cambia para sumar solo topes de REFERENCIADORES activos
    $querySumaTopes = "SELECT SUM(tope) as suma_topes 
                       FROM usuario 
                       WHERE tope IS NOT NULL 
                         AND activo = true 
                         AND tipo_usuario = 'Referenciador'";  // <-- FILTRO CLAVE
    $stmtTopes = $pdo->query($querySumaTopes);
    $resultTopes = $stmtTopes->fetch();
    $sumaTopes = $resultTopes['suma_topes'] ?? 0;
    
    // 3. Contar usuarios con rol "Descargador" ACTIVOS
    // También filtra por tipo_usuario
    $queryDescargadores = "SELECT COUNT(*) as total_descargadores 
                           FROM usuario 
                           WHERE tipo_usuario = 'Descargador' 
                             AND activo = true";
    $stmtDescargadores = $pdo->query($queryDescargadores);
    $resultDescargadores = $stmtDescargadores->fetch();
    $totalDescargadores = $resultDescargadores['total_descargadores'] ?? 0;
    
    // 4. Contar referenciadores ACTIVOS
    $queryReferenciadores = "SELECT COUNT(*) as total_referenciadores 
                             FROM usuario 
                             WHERE tipo_usuario = 'Referenciador' 
                               AND activo = true";
    $stmtReferenciadores = $pdo->query($queryReferenciadores);
    $resultReferenciadores = $stmtReferenciadores->fetch();
    $totalReferenciadores = $resultReferenciadores['total_referenciadores'] ?? 0;
    
    // Calcular porcentaje de avance (Total Referidos ACTIVOS vs Tope Total de ACTIVOS)
    $porcentajeAvance = 0;
    if ($sumaTopes > 0) {
        $porcentajeAvance = round(($totalReferidos / $sumaTopes) * 100, 2);
        // Limitar al 100% si se supera
        $porcentajeAvance = min($porcentajeAvance, 100);
    }
    
} catch (Exception $e) {
    // En caso de error, usar valores por defecto
    $totalReferidos = 0;
    $sumaTopes = 0;
    $totalDescargadores = 0;
    $totalReferenciadores = 0;
    $porcentajeAvance = 0;
    error_log("Error al obtener estadísticas: " . $e->getMessage());
}

// 6. Obtener información del sistema
$infoSistema = $sistemaModel->getInformacionSistema();

// 7. Formatear fecha para mostrar
$fecha_formateada = date('d/m/Y H:i:s', strtotime($fecha_actual));

// 8. Obtener información completa de la licencia (MODIFICADO)
$licenciaInfo = $sistemaModel->getInfoCompletaLicencia();

// Extraer valores
$infoSistema = $licenciaInfo['info'];
$diasRestantes = $licenciaInfo['dias_restantes'];
$validaHastaFormatted = $licenciaInfo['valida_hasta_formatted'];
$fechaInstalacionFormatted = $licenciaInfo['fecha_instalacion_formatted'];

// PARA LA BARRA QUE DISMINUYE: Calcular porcentaje RESTANTE
$porcentajeRestante = $sistemaModel->getPorcentajeRestanteLicencia();

// Color de la barra basado en lo que RESTA (ahora es más simple)
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
    <title>Data Referidos - Super Admin - SGP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Mismo estilo que la vista del referenciador */
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
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Header Styles (igual al referenciador) */
        .main-header {
            background: linear-gradient(135deg, #2c3e50, #1a252f);
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
        
        /* Breadcrumb Navigation */
        .breadcrumb-nav {
            max-width: 1400px;
            margin: 0 auto 20px;
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
            margin: 0 auto;
            padding: 0 15px 30px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        /* Dashboard Header */
        .dashboard-header {
            text-align: center;
            margin: 20px 0 40px;
            padding: 0 20px;
        }
        
        .dashboard-title {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .dashboard-subtitle {
            font-size: 1.1rem;
            color: #666;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.5;
        }
        
        /* Grid de 2 columnas para los botones */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            max-width: 900px;
            margin: 0 auto;
            width: 100%;
        }
        
        /* Botones estilo tarjeta */
        .data-option {
            background: white;
            border-radius: 12px;
            padding: 40px 30px;
            text-align: center;
            text-decoration: none;
            color: #2c3e50;
            transition: all 0.3s ease;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #eaeaea;
            position: relative;
            overflow: hidden;
            min-height: 300px;
        }
        
        .data-option::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }
        
        /* Color para Data Referidos */
        .data-referidos::before {
            background: linear-gradient(90deg, #3498db, #2980b9);
        }
        
        /* Color para Data Descargadores */
        .data-descargadores::before {
            background: linear-gradient(90deg, #27ae60, #219653);
        }
        
        .data-option:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.15);
            text-decoration: none;
            color: #2c3e50;
        }
        
        .data-referidos:hover {
            border-color: #3498db;
        }
        
        .data-descargadores:hover {
            border-color: #27ae60;
        }
        
        .data-icon-wrapper {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 25px;
            transition: all 0.3s ease;
        }
        
        .data-referidos .data-icon-wrapper {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
        }
        
        .data-descargadores .data-icon-wrapper {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
        }
        
        .data-option:hover .data-icon-wrapper {
            transform: scale(1.1);
        }
        
        .data-referidos:hover .data-icon-wrapper {
            background: linear-gradient(135deg, #3498db, #2980b9);
        }
        
        .data-descargadores:hover .data-icon-wrapper {
            background: linear-gradient(135deg, #27ae60, #219653);
        }
        
        .data-icon {
            font-size: 2.5rem;
            transition: all 0.3s ease;
        }
        
        .data-referidos .data-icon {
            color: #3498db;
        }
        
        .data-descargadores .data-icon {
            color: #27ae60;
        }
        
        .data-option:hover .data-icon {
            color: white;
        }
        
        .data-title {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 15px;
            color: #2c3e50;
        }
        
        .data-description {
            font-size: 0.95rem;
            color: #666;
            line-height: 1.5;
            max-width: 90%;
            margin: 0 auto 20px;
        }
        
        /* Barra de progreso para Data Referidos */
        .progress-section {
            width: 100%;
            margin-top: 20px;
        }
        
        .progress-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .progress-label {
            font-size: 0.9rem;
            color: #666;
            font-weight: 500;
        }
        
        .progress-percentage {
            font-size: 0.9rem;
            font-weight: 700;
            color: #3498db;
        }
        
        .progress-container {
            width: 100%;
            height: 12px;
            background-color: #e9ecef;
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        
        .data-referidos .progress-container {
            border: 1px solid #e0e0e0;
        }
        
        .progress-bar {
            height: 100%;
            border-radius: 6px;
            /* REMOVEMOS LA TRANSICIÓN PARA QUE NO SE ANIME AL PASAR EL MOUSE */
            /* transition: width 0.5s ease-in-out; */
        }
        
        .data-referidos .progress-bar {
            background: linear-gradient(90deg, #3498db, #2980b9);
        }
        
        .progress-stats {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: #666;
            margin-top: 5px;
        }
        
        .progress-current {
            font-weight: 600;
            color: #3498db;
        }
        
        .progress-target {
            font-weight: 600;
            color: #666;
        }
        
        /* Nota sobre estadísticas */
        .stats-note {
            font-size: 0.75rem;
            color: #999;
            text-align: center;
            margin-top: 10px;
            font-style: italic;
        }
        
        /* Data Descargadores - mantener estadísticas originales */
        .data-stats {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 15px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            display: block;
        }
        
        .data-descargadores .stat-number {
            color: #27ae60;
        }
        
        .stat-label {
            font-size: 0.8rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Footer */
        .system-footer {
            text-align: center;
            padding: 25px 0;
            background: white;
            color: black;
            font-size: 0.9rem;
            line-height: 1.6;
            border-top: 2px solid #eaeaea;
            width: 100%;
            margin-top: 60px;
        }
        
        .system-footer p {
            margin: 8px 0;
            color: #333;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
                max-width: 600px;
                gap: 25px;
            }
            
            .data-option {
                padding: 35px 25px;
                min-height: 280px;
            }
            
            .dashboard-title {
                font-size: 1.8rem;
            }
        }
        
        @media (max-width: 767px) {
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
            
            .dashboard-header {
                margin: 15px 0 30px;
            }
            
            .dashboard-title {
                font-size: 1.6rem;
                flex-direction: column;
                gap: 10px;
            }
            
            .dashboard-subtitle {
                font-size: 1rem;
                padding: 0 10px;
            }
            
            .data-icon-wrapper {
                width: 70px;
                height: 70px;
                margin-bottom: 20px;
            }
            
            .data-icon {
                font-size: 2.2rem;
            }
            
            .data-title {
                font-size: 1.4rem;
            }
            
            .progress-container {
                height: 10px;
            }
            
            .stat-number {
                font-size: 1.6rem;
            }
            
            .system-footer {
                padding: 20px 15px;
                font-size: 0.85rem;
            }
        }
        
        @media (max-width: 480px) {
            .data-option {
                padding: 30px 20px;
                min-height: 260px;
            }
            
            .data-icon-wrapper {
                width: 65px;
                height: 65px;
                margin-bottom: 18px;
            }
            
            .data-icon {
                font-size: 2rem;
            }
            
            .data-title {
                font-size: 1.3rem;
            }
            
            .data-description {
                font-size: 0.9rem;
            }
            
            .progress-container {
                height: 8px;
            }
            
            .progress-info {
                flex-direction: column;
                align-items: center;
                gap: 5px;
                margin-bottom: 10px;
            }
            
            .data-stats {
                flex-direction: column;
                gap: 10px;
            }
        }
                .container.text-center.mb-3 img {
            max-width: 320px;
            height: auto;
            transition: max-width 0.3s ease;
        }

        /* Para dispositivos móviles */
        @media (max-width: 768px) {
            .container.text-center.mb-3 img {
                max-width: 220px;
            }
        }

        /* Para dispositivos muy pequeños */
        @media (max-width: 400px) {
            .container.text-center.mb-3 img {
                max-width: 200px;
            }
        }
         /* Estilos para el modal de información del sistema */
        .modal-system-info .modal-header {
            background: linear-gradient(135deg, #2c3e50, #1a252f);
            color: white;
        }
        
        .modal-system-info .modal-body {
            padding: 20px;
        }
        
        /* Logo centrado en el modal - IMAGEN AGRANDADA */
        .modal-logo-container {
            text-align: center;
            margin-bottom: 20px;
            padding: 15px;
        }
        
        .modal-logo {
            max-width: 300px; /* AGRANDADO de 200px a 300px */
            height: auto;
            margin: 0 auto;
            border-radius: 12px; /* Bordes más redondeados */
            box-shadow: 0 6px 20px rgba(0,0,0,0.15); /* Sombra más pronunciada */
            border: 3px solid #fff; /* Borde blanco */
            background: white;
        }
        
        /* Barra de progreso de licencia */
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
        
        .licencia-fecha {
            font-size: 0.85rem;
            color: #6c757d;
            text-align: center;
            margin-top: 5px;
        }
        
        /* Tarjetas de características */
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
        
        .feature-icon {
            opacity: 0.8;
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
        
        /* Footer del modal */
        .system-footer-modal {
            background: #f1f5f9;
            border-radius: 8px;
            padding: 20px;
            margin-top: 30px;
            border-top: 2px solid #e2e8f0;
        }
        
        .logo-clickable {
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .logo-clickable:hover {
            transform: scale(1.05);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .modal-system-info .modal-body {
                padding: 15px;
            }
            
            .modal-logo {
                max-width: 200px; /* AGRANDADO para móviles también */
            }
            
            .feature-card {
                padding: 15px;
                margin-bottom: 15px;
            }
            
            .modal-system-info h4 {
                font-size: 1.1rem;
            }
            
            .licencia-info {
                padding: 12px;
            }
            
            .licencia-title {
                font-size: 1rem;
            }
            
            .licencia-dias {
                font-size: 0.9rem;
                padding: 3px 10px;
            }
            
            .system-footer-modal {
                padding: 15px;
            }
            .feature-img-header {
            width: 140px;
            height: 140px;
            }
        }
        
        @media (max-width: 576px) {
            .modal-system-info .modal-dialog {
                margin: 10px;
            }
            
            .modal-system-info .modal-body {
                padding: 12px;
            }
            
            .modal-logo-container {
                padding: 10px;
            }
            
            .modal-logo {
                max-width: 180px; /* AGRANDADO para móviles pequeños */
            }
            
            .licencia-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            
            .licencia-dias {
                align-self: flex-start;
            }
            .feature-img-header {
            width: 140px;
            height: 140px;
            }
        }
        /* Contenedor para centrar la imagen */
        .feature-image-container {
            text-align: center;
            margin-bottom: 2rem; /* Espacio antes de las tarjetas */
        }

        /* Estilos de la imagen redonda */
        .feature-img-header {
            width: 190px;
            height: 190px;
            object-fit: cover;       /* Asegura que no se deforme */
            border-radius: 50%;      /* Círculo perfecto */
            border: 4px solid #ffffff; 
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1); /* Sombra elegante */
            transition: transform 0.3s ease; /* Para el efecto de hover */
        }

        /* Efecto opcional al pasar el mouse */
        .feature-img-header:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="header-container">
            <div class="header-top">
                <div class="header-title">
                    <h1><i class="fas fa-database"></i> Data Referidos - Super Admin</h1>
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($usuario_logueado['nombres'] . ' ' . $usuario_logueado['apellidos']); ?></span>
                    </div>
                </div>
                <a href="../logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
        </div>
    </header>

    <!-- Breadcrumb Navigation -->
    <div class="breadcrumb-nav">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../superadmin_dashboard.php"><i class="fas fa-home"></i> Panel Super Admin</a></li>
                <li class="breadcrumb-item active"><i class="fas fa-database"></i> Datas</li>
            </ol>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="dashboard-title">
                <i class="fas fa-database"></i>
                <span>Gestión de Datas del Sistema</span>
            </div>
            <p class="dashboard-subtitle">
                Seleccione el tipo de data que desea gestionar y consultar. 
                Acceda a toda la información de referenciación y descarga del sistema.
            </p>
        </div>
        
        <!-- Grid de 2 columnas -->
        <div class="dashboard-grid">
            <!-- Data Referidos -->
            <a href="data_referidos.php" class="data-option data-referidos">
                <div class="data-icon-wrapper">
                    <div class="data-icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="data-title">DATA REFERIDOS</div>
                <div class="data-description">
                    Gestión completa de todos los referidos registrados en el sistema. 
                    Consulta, edición y administración de información de referenciación.
                </div>
                
                <!-- Barra de progreso para Data Referidos -->
                <div class="progress-section">
                    <div class="progress-info">
                        <span class="progress-label">Avance de Referidos</span>
                        <span class="progress-percentage"><?php echo $porcentajeAvance; ?>%</span>
                    </div>
                    <div class="progress-container">
                        <div class="progress-bar" style="width: <?php echo $porcentajeAvance; ?>%"></div>
                    </div>
                    <div class="progress-stats">
                        <span class="progress-current"><?php echo number_format($totalReferidos, 0, ',', '.'); ?> referidos activos</span>
                        <span class="progress-target">Meta: <?php echo number_format($sumaTopes, 0, ',', '.'); ?></span>
                    </div>
                    <div class="stats-note">
                        <i class="fas fa-info-circle"></i> Solo se cuentan referidos y usuarios activos
                    </div>
                </div>
            </a>
            
            <!-- Data Descargadores -->
            <a href="data_descargadores.php" class="data-option data-descargadores">
                <div class="data-icon-wrapper">
                    <div class="data-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
                <div class="data-title">DATA DESCARGADORES</div>
                <div class="data-description">
                    Información detallada de usuarios con rol Descargador. 
                    Gestión de permisos y acceso a datos de descarga.
                </div>
                <div class="data-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo number_format($totalDescargadores, 0, ',', '.'); ?></span>
                        <span class="stat-label">Descargadores Activos</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo number_format($totalReferenciadores, 0, ',', '.'); ?></span>
                        <span class="stat-label">Referenciadores Activos</span>
                    </div>
                </div>
                <div class="stats-note">
                    <i class="fas fa-info-circle"></i> Solo se cuentan usuarios activos
                </div>
            </a>
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
                © Derechos de autor Reservados • <strong>Ing. Rubén Darío González García</strong> • Equipo de soporte • SISGONTech<br>
                Email: sisgonnet@gmail.com • Contacto: +57 3106310227 • Puerto Gaitán, Colombia • <?php echo date('Y'); ?>
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
                    <!-- Logo centrado AGRANDADO -->
                    <div class="modal-logo-container">
                        <img src="../imagenes/Logo-artguru.png" alt="Logo del Sistema" class="modal-logo">
                    </div>
                    
                    <!-- Título del Sistema - ELIMINADO "Sistema SGP" -->
                    <div class="text-center mb-4">
                        <!-- ELIMINADO: <h1 class="display-5 fw-bold text-primary mb-2">
                            <?php echo htmlspecialchars($infoSistema['nombre_sistema'] ?? 'Sistema SGP'); ?>
                        </h1> -->
                        <h4 class="text-secondary mb-4">
                            <strong>Gestión Política de Alta Precisión</strong>
                        </h4>
                        
<!-- Información de Licencia (MODIFICADO) -->
<div class="licencia-info">
    <div class="licencia-header">
        <h6 class="licencia-title">Licencia Runtime</h6>
        <span class="licencia-dias">
            <strong><?php echo $diasRestantes; ?> días restantes</strong>
        </span>
    </div>
    
    <div class="licencia-progress">
        <!-- BARRA QUE DISMINUYE: muestra el PORCENTAJE RESTANTE -->
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
                    </div>
                    <div class="feature-image-container">
                        <img src="../imagenes/ingeniero2.png" alt="Logo de Herramienta" class="feature-img-header">
                        <div class="profile-info mt-3">
                            <h4 class="profile-name"><strong>Rubén Darío Gonzáles García</strong></h4>
                            
                            <small class="profile-description">
                                Ingeniero de Sistemas, administrador de bases de datos, desarrollador de objeto OLE.<br>
                                Magister en Administración Pública.<br>
                                <span class="cio-tag"><strong>CIO de equipo soporte SISGONTECH</strong></span>
                            </small>
                        </div>
                    </div>
                    <!-- Sección de Características -->
                    <div class="row g-4 mb-4">
                        <!-- Efectividad de la Herramienta -->
                        <div class="col-md-6">
                            <div class="feature-card">
                                <div class="feature-icon text-primary mb-3">
                                    <i class="fas fa-bolt fa-2x"></i>
                                </div>
                                <h5 class="feature-title">Efectividad de la Herramienta</h5>
                                <h6 class="text-muted mb-2">Optimización de Tiempos</h6>
                                <p class="feature-text">
                                    Reducción del 70% en el procesamiento manual de datos y generación de reportes de adeptos.
                                </p>
                            </div>
                        </div>
                        
                        <!-- Integridad de Datos -->
                        <div class="col-md-6">
                            <div class="feature-card">
                                <div class="feature-icon text-success mb-3">
                                    <i class="fas fa-database fa-2x"></i>
                                </div>
                                <h5 class="feature-title">Integridad de Datos</h5>
                                <h6 class="text-muted mb-2">Validación Inteligente</h6>
                                <p class="feature-text">
                                    Validación en tiempo real para eliminar duplicados y errores de digitación en la base de datos política.
                                </p>
                            </div>
                        </div>
                        
                        <!-- Monitoreo de Metas -->
                        <div class="col-md-6">
                            <div class="feature-card">
                                <div class="feature-icon text-warning mb-3">
                                    <i class="fas fa-chart-line fa-2x"></i>
                                </div>
                                <h5 class="feature-title">Monitoreo de Metas</h5>
                                <h6 class="text-muted mb-2">Seguimiento Visual</h6>
                                <p class="feature-text">
                                    Seguimiento visual del cumplimiento de objetivos mediante barras de avance dinámicas.
                                </p>
                            </div>
                        </div>
                        
                        <!-- Seguridad Avanzada -->
                        <div class="col-md-6">
                            <div class="feature-card">
                                <div class="feature-icon text-danger mb-3">
                                    <i class="fas fa-shield-alt fa-2x"></i>
                                </div>
                                <h5 class="feature-title">Seguridad Avanzada</h5>
                                <h6 class="text-muted mb-2">Control Total</h6>
                                <p class="feature-text">
                                    Control de acceso jerarquizado y trazabilidad total de ingresos al sistema.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
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
        $(document).ready(function() {
            // Efecto hover mejorado
            $('.data-option').hover(
                function() {
                    $(this).css('transform', 'translateY(-8px)');
                },
                function() {
                    $(this).css('transform', 'translateY(0)');
                }
            );
            
            // Breadcrumb navigation
            $('.breadcrumb a').click(function(e) {
                if ($(this).attr('href') === '#') {
                    e.preventDefault();
                }
            });
        });
    </script>
    <script src="../js/modal-sistema.js"></script>
</body>
</html>