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
        
        /* Main Container (igual al referenciador) */
        .main-container {
            max-width: 1400px;
            margin: 0 auto 30px;
            padding: 0 15px;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Dashboard Cards Container */
        .dashboard-cards-container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .dashboard-header {
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f5f9;
        }
        
        .dashboard-header h2 {
            color: #2c3e50;
            font-size: 1.5rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .dashboard-header h2 i {
            color: #3498db;
        }
        
        /* Grid de botones */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }
        
        /* Botones de opciones */
        .dashboard-option {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 30px 20px;
            text-align: center;
            text-decoration: none;
            color: #2c3e50;
            transition: all 0.3s ease;
            cursor: pointer;
            height: 200px;
        }
        
        .dashboard-option:hover {
            background: #e3f2fd;
            border-color: #3498db;
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(52, 152, 219, 0.2);
            text-decoration: none;
            color: #2c3e50;
        }
        
        .option-icon {
            font-size: 2.5rem;
            color: #3498db;
            margin-bottom: 15px;
        }
        
        .option-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .option-description {
            font-size: 0.85rem;
            color: #666;
            line-height: 1.4;
        }
        
        /* Footer (igual al referenciador) */
        .system-footer {
            text-align: center;
            padding: 20px 0;
            background: white;
            color: black;
            font-size: 0.85rem;
            line-height: 1.5;
            border-top: 2px solid #eaeaea;
            width: 100%;
            margin-top: auto;
        }
        
        .system-footer p {
            margin: 5px 0;
        }
        
        /* Responsive */
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
            
            .dashboard-cards-container {
                padding: 20px;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .dashboard-option {
                height: 180px;
                padding: 25px 15px;
            }
            
            .option-icon {
                font-size: 2rem;
            }
            
            .option-title {
                font-size: 1.1rem;
            }
        }
        
        @media (max-width: 480px) {
            .dashboard-header h2 {
                font-size: 1.3rem;
            }
            
            .dashboard-option {
                height: 160px;
                padding: 20px 15px;
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
        <div class="dashboard-cards-container">
            <div class="dashboard-header">
                <h2><i class="fas fa-tachometer-alt"></i> Módulos Super Admin</h2>
            </div>
            
            <div class="dashboard-grid">
                <!-- Monitoreos -->
                <a href="superadmin_monitoreos.php" class="dashboard-option">
                    <div class="option-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="option-title">MONITOREOS</div>
                    <div class="option-description">
                        Avance de referenciadores, gráficas comparativas y estadísticas en tiempo real
                    </div>
                </a>
                
                <!-- Georeferenciación -->
                <a href="superadmin_georeferenciacion.php" class="dashboard-option">
                    <div class="option-icon">
                        <i class="fas fa-map-marked-alt"></i>
                    </div>
                    <div class="option-title">GEOREFERENCIACIÓN</div>
                    <div class="option-description">
                        Visualización en Google Maps con filtros avanzados por ubicación
                    </div>
                </a>
                
                <!-- Reportes -->
                <a href="superadmin_reportes.php" class="dashboard-option">
                    <div class="option-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="option-title">REPORTES</div>
                    <div class="option-description">
                        Generación de reportes detallados y exportación de datos en múltiples formatos
                    </div>
                </a>
                
                <!-- Datas -->
                <a href="superadmin_datas.php" class="dashboard-option">
                    <div class="option-icon">
                        <i class="fas fa-database"></i>
                    </div>
                    <div class="option-title">DATAS</div>
                    <div class="option-description">
                        Gestión completa de datos: referidos y descargadores del sistema
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- Footer (igual al referenciador) -->
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