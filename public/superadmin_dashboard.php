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
        :root {
            --primary-dark: #1a237e;
            --primary: #283593;
            --primary-light: #5c6bc0;
            --accent: #00bcd4;
            --success: #4caf50;
            --warning: #ff9800;
            --danger: #f44336;
            --gray-dark: #212529;
            --gray: #6c757d;
            --gray-light: #f8f9fa;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
        }

        /* Header */
        .superadmin-header {
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            color: white;
            padding: 20px 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-logo {
            font-size: 1.8rem;
            color: var(--accent);
        }

        .header-title h1 {
            font-size: 1.5rem;
            margin: 0;
            font-weight: 600;
        }

        .header-title p {
            margin: 0;
            font-size: 0.9rem;
            color: #aaa;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.1);
            padding: 8px 15px;
            border-radius: 20px;
        }

        .user-info i {
            color: var(--accent);
        }

        .logout-btn {
            background: var(--danger);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            font-weight: 500;
        }

        .logout-btn:hover {
            background: #d32f2f;
            color: white;
            transform: translateY(-2px);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }

        /* Dashboard Cards */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            max-width: 1200px;
            width: 100%;
        }

        .dashboard-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 40px 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
            cursor: pointer;
            position: relative;
            overflow: hidden;
            text-decoration: none;
            display: block;
        }

        .dashboard-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
        }

        .dashboard-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        }

        .card-icon {
            font-size: 3.5rem;
            margin-bottom: 20px;
            color: var(--primary);
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--gray-dark);
        }

        .card-description {
            color: var(--gray);
            font-size: 0.95rem;
            line-height: 1.5;
        }

        /* Footer */
        .system-footer {
            text-align: center;
            padding: 20px 0;
            background: white;
            color: #333;
            font-size: 0.85rem;
            line-height: 1.5;
            border-top: 2px solid #eaeaea;
            width: 100%;
        }

        .system-footer p {
            margin: 5px 0;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
            }

            .header-left {
                flex-direction: column;
                text-align: center;
            }

            .dashboard-cards {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .dashboard-card {
                padding: 30px 20px;
            }

            .card-icon {
                font-size: 3rem;
            }

            .card-title {
                font-size: 1.3rem;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 20px 15px;
            }

            .dashboard-card {
                padding: 25px 15px;
            }

            .card-icon {
                font-size: 2.5rem;
            }

            .card-title {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="superadmin-header">
        <div class="header-content">
            <div class="header-left">
                <div class="header-logo">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="header-title">
                    <h1>Panel Super Admin - Sistema de Gestión Política</h1>
                    <p>Control total del sistema de referenciación</p>
                </div>
            </div>
            <div class="header-right">
                <div class="user-info">
                    <i class="fas fa-user-circle"></i>
                    <span><?php echo htmlspecialchars($usuario_logueado['nombres'] . ' ' . $usuario_logueado['apellidos']); ?></span>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-content">
        <div class="dashboard-cards">
            <!-- Monitoreos -->
            <a href="superadmin_monitoreos.php" class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="card-title">MONITOREOS</div>
                <div class="card-description">
                    Avance de referenciadores y gráficas comparativas. 
                    Monitoreo en tiempo real del progreso de referenciación.
                </div>
            </a>

            <!-- Georeferenciación -->
            <a href="superadmin_georeferenciacion.php" class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-map-marked-alt"></i>
                </div>
                <div class="card-title">GEOREFERENCIACIÓN</div>
                <div class="card-description">
                    Visualización en Google Maps de todos los referenciados.
                    Filtros avanzados por ubicación y características.
                </div>
            </a>

            <!-- Reportes -->
            <a href="superadmin_reportes.php" class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="card-title">REPORTES</div>
                <div class="card-description">
                    Generación de reportes detallados y exportación de datos.
                    Análisis estadístico completo del sistema.
                </div>
            </a>

            <!-- Datas -->
            <a href="superadmin_datas.php" class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-database"></i>
                </div>
                <div class="card-title">DATAS</div>
                <div class="card-description">
                    Gestión completa de datos: referidos y descargadores.
                    Administración de la información del sistema.
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
</body>
</html>