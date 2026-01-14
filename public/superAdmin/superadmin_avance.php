<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UsuarioModel.php';

// Verificar si el usuario está logueado y es SuperAdmin
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    header('Location: index.php');
    exit();
}

$pdo = Database::getConnection();
$usuarioModel = new UsuarioModel($pdo);

// Obtener datos del usuario logueado
$usuario_logueado = $usuarioModel->getUsuarioById($_SESSION['id_usuario']);

// Obtener todos los referenciadores con sus estadísticas
try {
    // Obtener solo usuarios referenciadores activos
    $referenciadores = $usuarioModel->buscarUsuarios([
        'tipo_usuario' => 'Referenciador',
        'activo' => true
    ]);
    
    // Calcular estadísticas globales
    $totalReferenciadores = count($referenciadores);
    $totalReferidos = 0;
    $totalTope = 0;
    
    foreach ($referenciadores as &$referenciador) {
        $totalReferidos += $referenciador['total_referenciados'] ?? 0;
        $totalTope += $referenciador['tope'] ?? 0;
        
        // Calcular porcentaje individual si no viene del modelo
        if (!isset($referenciador['porcentaje_tope']) && $referenciador['tope'] > 0) {
            $referenciador['porcentaje_tope'] = round(($referenciador['total_referenciados'] / $referenciador['tope']) * 100, 2);
        }
        
        // Limitar al 100%
        if ($referenciador['porcentaje_tope'] > 100) {
            $referenciador['porcentaje_tope'] = 100;
        }
    }
    
    // Calcular porcentaje global
    $porcentajeGlobal = 0;
    if ($totalTope > 0) {
        $porcentajeGlobal = round(($totalReferidos / $totalTope) * 100, 2);
        $porcentajeGlobal = min($porcentajeGlobal, 100);
    }
    
} catch (Exception $e) {
    $referenciadores = [];
    $totalReferenciadores = 0;
    $totalReferidos = 0;
    $totalTope = 0;
    $porcentajeGlobal = 0;
    error_log("Error al obtener referenciadores: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avance Referenciadores - Super Admin - SGP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
        
        /* Header Styles */
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
        
        .breadcrumb-item + .breadcrumb-item::before {
            content: "›";
            color: #95a5a6;
            padding: 0 8px;
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
            max-width: 800px;
            margin: 0 auto;
            line-height: 1.5;
        }
        
        /* Estadísticas Globales */
        .global-stats {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #eaeaea;
        }
        
        .stats-title {
            font-size: 1.2rem;
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .stat-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            border-left: 4px solid #3498db;
        }
        
        .stat-value {
            font-size: 2.2rem;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #666;
        }
        
        /* Barra de progreso global */
        .global-progress {
            margin-top: 20px;
        }
        
        .progress-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .progress-label {
            font-size: 0.9rem;
            color: #666;
        }
        
        .progress-percentage {
            font-size: 1rem;
            color: #3498db;
        }
        
        .progress-container {
            width: 100%;
            height: 14px;
            background-color: #e9ecef;
            border-radius: 7px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #3498db, #2980b9);
            border-radius: 7px;
        }
        
        .progress-stats {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            color: #666;
        }
        
        /* Lista de Referenciadores */
        .referenciadores-list {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #eaeaea;
        }
        
        .list-title {
            font-size: 1.4rem;
            color: #2c3e50;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .referenciador-card {
            border: 1px solid #eaeaea;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .referenciador-card:hover {
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            border-color: #3498db;
        }
        
        .referenciador-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        
        .user-info-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid #eaeaea;
        }
        
        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .user-details {
            flex: 1;
        }
        
        .user-name {
            font-size: 1.1rem;
            color: #2c3e50;
            margin-bottom: 3px;
        }
        
        .user-info-text {
            font-size: 0.85rem;
            color: #666;
            display: flex;
            gap: 15px;
        }
        
        .user-stats {
            display: flex;
            gap: 20px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 1.3rem;
            color: #3498db;
        }
        
        .stat-desc {
            font-size: 0.8rem;
            color: #666;
        }
        
        /* Barra de progreso individual */
        .individual-progress {
            margin-top: 15px;
        }
        
        .progress-label-small {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 5px;
        }
        
        .progress-container-small {
            width: 100%;
            height: 10px;
            background-color: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
            margin-bottom: 8px;
        }
        
        .progress-bar-small {
            height: 100%;
            border-radius: 5px;
        }
        
        /* Colores de la barra según porcentaje */
        .progress-excelente { background: linear-gradient(90deg, #27ae60, #219653); }
        .progress-bueno { background: linear-gradient(90deg, #3498db, #2980b9); }
        .progress-medio { background: linear-gradient(90deg, #f39c12, #e67e22); }
        .progress-bajo { background: linear-gradient(90deg, #e74c3c, #c0392b); }
        
        .progress-numbers {
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
            color: #666;
        }
        
        /* Sin datos */
        .no-data {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        
        .no-data i {
            font-size: 3rem;
            color: #bdc3c7;
            margin-bottom: 15px;
        }
        
        .no-data p {
            font-size: 1.1rem;
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
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .referenciador-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .user-stats {
                width: 100%;
                justify-content: space-around;
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
            
            .breadcrumb-nav {
                padding: 0 10px;
                margin-bottom: 15px;
            }
            
            .breadcrumb {
                font-size: 0.85rem;
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
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .stat-card {
                padding: 15px;
            }
            
            .stat-value {
                font-size: 1.8rem;
            }
            
            .referenciadores-list {
                padding: 20px;
            }
            
            .user-info-section {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            
            .user-info-text {
                flex-direction: column;
                gap: 5px;
            }
            
            .system-footer {
                padding: 20px 15px;
                font-size: 0.85rem;
            }
        }
        
        @media (max-width: 480px) {
            .global-stats, .referenciadores-list {
                padding: 15px;
            }
            
            .stat-value {
                font-size: 1.6rem;
            }
            
            .user-stats {
                flex-direction: column;
                gap: 10px;
            }
            
            .progress-numbers {
                flex-direction: column;
                gap: 5px;
                text-align: center;
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
                    <h1><i class="fas fa-chart-line"></i> Avance Referenciadores - Super Admin</h1>
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($usuario_logueado['nombres'] . ' ' . $usuario_logueado['apellidos']); ?></span>
                    </div>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
        </div>
    </header>

    <!-- Breadcrumb Navigation -->
    <div class="breadcrumb-nav">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="superadmin_dashboard.php"><i class="fas fa-home"></i> Panel Super Admin</a></li>
                <li class="breadcrumb-item active"><i class="fas fa-chart-line"></i> Avance Referenciadores</li>
            </ol>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="dashboard-title">
                <i class="fas fa-users-line"></i>
                <span>Monitoreo de Avance - Referenciadores</span>
            </div>
            <p class="dashboard-subtitle">
                Visualice el progreso de todos los referenciadores activos del sistema. 
                Compare el avance individual y global vs las metas establecidas.
            </p>
        </div>
        
        <!-- Estadísticas Globales -->
        <div class="global-stats">
            <div class="stats-title">
                <i class="fas fa-chart-bar"></i>
                <span>Estadísticas Globales</span>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $totalReferenciadores; ?></div>
                    <div class="stat-label">Referenciadores Activos</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($totalReferidos, 0, ',', '.'); ?></div>
                    <div class="stat-label">Total Referidos Registrados</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($totalTope, 0, ',', '.'); ?></div>
                    <div class="stat-label">Meta Total de Referidos</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?php echo $porcentajeGlobal; ?>%</div>
                    <div class="stat-label">Avance Global</div>
                </div>
            </div>
            
            <!-- Barra de Progreso Global -->
            <div class="global-progress">
                <div class="progress-info">
                    <span class="progress-label">Progreso Global del Sistema</span>
                    <span class="progress-percentage"><?php echo $porcentajeGlobal; ?>%</span>
                </div>
                <div class="progress-container">
                    <div class="progress-bar" style="width: <?php echo $porcentajeGlobal; ?>%"></div>
                </div>
                <div class="progress-stats">
                    <span><?php echo number_format($totalReferidos, 0, ',', '.'); ?> referidos</span>
                    <span>Meta: <?php echo number_format($totalTope, 0, ',', '.'); ?> referidos</span>
                </div>
            </div>
        </div>
        
        <!-- Lista de Referenciadores -->
        <div class="referenciadores-list">
            <div class="list-title">
                <i class="fas fa-list-ol"></i>
                <span>Progreso Individual por Referenciador</span>
            </div>
            
            <?php if (empty($referenciadores)): ?>
                <div class="no-data">
                    <i class="fas fa-users-slash"></i>
                    <p>No hay referenciadores activos registrados en el sistema.</p>
                </div>
            <?php else: ?>
                <?php foreach ($referenciadores as $referenciador): ?>
                    <?php 
                    $porcentaje = $referenciador['porcentaje_tope'] ?? 0;
                    
                    // Determinar clase de color según porcentaje
                    $progressClass = 'progress-bajo';
                    if ($porcentaje >= 75) $progressClass = 'progress-excelente';
                    elseif ($porcentaje >= 50) $progressClass = 'progress-bueno';
                    elseif ($porcentaje >= 25) $progressClass = 'progress-medio';
                    ?>
                    
                    <div class="referenciador-card">
                        <div class="referenciador-header">
                            <div class="user-info-section">
                                <div class="user-avatar">
                                    <?php if (!empty($referenciador['foto_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($referenciador['foto_url']); ?>" alt="Foto">
                                    <?php else: ?>
                                        <div style="width: 100%; height: 100%; background: #eaeaea; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-user" style="color: #95a5a6; font-size: 1.5rem;"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="user-details">
                                    <div class="user-name">
                                        <?php echo htmlspecialchars($referenciador['nombres'] . ' ' . $referenciador['apellidos']); ?>
                                    </div>
                                    <div class="user-info-text">
                                        <span>Cédula: <?php echo htmlspecialchars($referenciador['cedula'] ?? 'N/A'); ?></span>
                                        <span>Usuario: <?php echo htmlspecialchars($referenciador['nickname'] ?? 'N/A'); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="user-stats">
                                <div class="stat-item">
                                    <div class="stat-number"><?php echo $referenciador['total_referenciados'] ?? 0; ?></div>
                                    <div class="stat-desc">Referidos</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number"><?php echo $referenciador['tope'] ?? 0; ?></div>
                                    <div class="stat-desc">Tope</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number"><?php echo $porcentaje; ?>%</div>
                                    <div class="stat-desc">Avance</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Barra de Progreso Individual -->
                        <div class="individual-progress">
                            <div class="progress-label-small">
                                Progreso individual: <?php echo $referenciador['total_referenciados'] ?? 0; ?> de <?php echo $referenciador['tope'] ?? 0; ?> referidos
                            </div>
                            <div class="progress-container-small">
                                <div class="progress-bar-small <?php echo $progressClass; ?>" 
                                     style="width: <?php echo $porcentaje; ?>%">
                                </div>
                            </div>
                            <div class="progress-numbers">
                                <span>0%</span>
                                <span>25%</span>
                                <span>50%</span>
                                <span>75%</span>
                                <span>100%</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
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

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Efecto de animación para las barras de progreso al cargar
            $('.progress-bar-small').each(function() {
                var width = $(this).css('width');
                $(this).css('width', '0');
                
                setTimeout(() => {
                    $(this).animate({
                        width: width
                    }, 1000);
                }, 300);
            });
            
            // Efecto hover en tarjetas
            $('.referenciador-card').hover(
                function() {
                    $(this).css('transform', 'translateY(-5px)');
                },
                function() {
                    $(this).css('transform', 'translateY(0)');
                }
            );
            
            // Actualizar estadísticas cada 30 segundos (opcional)
            setInterval(function() {
                // Aquí podrías agregar una llamada AJAX para actualizar en tiempo real
                // si necesitas datos en vivo
            }, 30000);
        });
    </script>
</body>
</html>