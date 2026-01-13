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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Super Admin - SGP</title>
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
        
        /* Main Container - CENTRADO Y MÁS AMPLIO */
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 15px 30px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        /* Dashboard Header con título destacado */
        .dashboard-header {
            text-align: center;
            margin: 30px 0 40px;
            padding: 0 20px;
        }
        
        .dashboard-title {
            font-size: 2.2rem;
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
            max-width: 1000px;
            margin: 0 auto;
            width: 100%;
        }
        
        /* Botones estilo tarjeta mejorados */
        .dashboard-option {
            background: white;
            border-radius: 12px;
            padding: 35px 25px;
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
        }
        
        .dashboard-option::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #3498db, #2ecc71);
        }
        
        .dashboard-option:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.15);
            border-color: #3498db;
            text-decoration: none;
            color: #2c3e50;
        }
        
        .option-icon-wrapper {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .dashboard-option:hover .option-icon-wrapper {
            background: linear-gradient(135deg, #3498db, #2980b9);
            transform: scale(1.1);
        }
        
        .option-icon {
            font-size: 2.2rem;
            color: #3498db;
            transition: all 0.3s ease;
        }
        
        .dashboard-option:hover .option-icon {
            color: white;
        }
        
        .option-title {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 12px;
            color: #2c3e50;
        }
        
        .option-description {
            font-size: 0.95rem;
            color: #666;
            line-height: 1.5;
            max-width: 90%;
            margin: 0 auto;
        }
        
        /* Indicador de acceso */
        .access-indicator {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #f8f9fa;
            border-radius: 20px;
            padding: 4px 10px;
            font-size: 0.75rem;
            color: #666;
            font-weight: 500;
        }
        
        /* Footer (igual al referenciador) */
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
            
            .dashboard-option {
                padding: 30px 20px;
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
                margin: 20px 0 30px;
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
            
            .option-icon-wrapper {
                width: 60px;
                height: 60px;
            }
            
            .option-icon {
                font-size: 1.8rem;
            }
            
            .option-title {
                font-size: 1.2rem;
            }
            
            .system-footer {
                padding: 20px 15px;
                font-size: 0.85rem;
            }
        }
        
        @media (max-width: 480px) {
            .dashboard-option {
                padding: 25px 15px;
            }
            
            .option-icon-wrapper {
                width: 55px;
                height: 55px;
                margin-bottom: 15px;
            }
            
            .option-icon {
                font-size: 1.6rem;
            }
            
            .option-title {
                font-size: 1.1rem;
            }
            
            .option-description {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header (igual al referenciador) -->
    <header class="main-header">
        <div class="header-container">
            <div class="header-top">
                <div class="header-title">
                    <h1><i class="fas fa-user-shield"></i> Panel Super Admin</h1>
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

    <!-- Main Content -->
    <div class="main-container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="dashboard-title">
                <i class="fas fa-tachometer-alt"></i>
                <span>Panel de Control Super Admin</span>
            </div>
            <p class="dashboard-subtitle">
                Acceda a los módulos principales del sistema de gestión política. 
                Controle y supervise todas las operaciones desde un solo lugar.
            </p>
        </div>
        
        <!-- Grid de 2 columnas -->
        <div class="dashboard-grid">
            <!-- Monitoreos -->
            <a href="superadmin_monitoreos.php" class="dashboard-option">
                <div class="access-indicator">
                    <i class="fas fa-arrow-right"></i>
                </div>
                <div class="option-icon-wrapper">
                    <div class="option-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
                <div class="option-title">MONITOREOS</div>
                <div class="option-description">
                    Gráficas de avance, estadísticas en tiempo real, 
                    comparativas entre referenciadores y análisis detallados
                </div>
            </a>
            
            <!-- Georeferenciación -->
            <a href="superadmin_georeferenciacion.php" class="dashboard-option">
                <div class="access-indicator">
                    <i class="fas fa-arrow-right"></i>
                </div>
                <div class="option-icon-wrapper">
                    <div class="option-icon">
                        <i class="fas fa-map-marked-alt"></i>
                    </div>
                </div>
                <div class="option-title">GEOREFERENCIACIÓN</div>
                <div class="option-description">
                    Visualización geográfica de referenciados, 
                    filtros por ubicación y consulta avanzada en mapas interactivos
                </div>
            </a>
            
            <!-- Reportes -->
            <a href="superadmin_reportes.php" class="dashboard-option">
                <div class="access-indicator">
                    <i class="fas fa-arrow-right"></i>
                </div>
                <div class="option-icon-wrapper">
                    <div class="option-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                </div>
                <div class="option-title">REPORTES</div>
                <div class="option-description">
                    Generación de informes detallados, 
                    exportación en múltiples formatos y análisis estadístico completo
                </div>
            </a>
            
            <!-- Datas -->
            <a href="superadmin_datas.php" class="dashboard-option">
                <div class="access-indicator">
                    <i class="fas fa-arrow-right"></i>
                </div>
                <div class="option-icon-wrapper">
                    <div class="option-icon">
                        <i class="fas fa-database"></i>
                    </div>
                </div>
                <div class="option-title">DATAS</div>
                <div class="option-description">
                    Gestión integral de bases de datos, 
                    administración de referidos y descargadores del sistema
                </div>
            </a>
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
</body>
</html>