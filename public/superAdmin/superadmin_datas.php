<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';

// Verificar si el usuario está logueado y es SuperAdmin
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    header('Location: ../index.php');
    exit();
}

$pdo = Database::getConnection();
$usuarioModel = new UsuarioModel($pdo);

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
                <img src="../imagenes/Logo-artguru.png" alt="Logo">
            </div>

            <div class="container text-center">
                <p>
                    © Derechos de autor Reservados • <strong>Ing. Rubén Darío González García</strong> • Equipo de soporte • SISGONTech<br>
                    Email: sisgonnet@gmail.com • Contacto: +57 3106310227 • Puerto Gaitán, Colombia • <?php echo date('Y'); ?>
                </p>
            </div>
        </footer>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
</body>
</html>