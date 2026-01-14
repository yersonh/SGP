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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; /* FUENTE PRINCIPAL */
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255,255,255,0.1);
            padding: 5px 10px;
            border-radius: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        /* Breadcrumb Navigation (IGUAL AL DE DATA REFERIDOS) */
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .breadcrumb-item a {
            color: #3498db;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .breadcrumb-item a:hover {
            color: #2980b9;
            text-decoration: underline;
        }
        
        .breadcrumb-item.active {
            color: #666;
        }
        
        .breadcrumb-item + .breadcrumb-item::before {
            content: "›";
            color: #95a5a6;
            padding: 0 8px;
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
            margin: 20px 0 40px;
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .dashboard-subtitle {
            font-size: 1.1rem;
            color: #666;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* Grid de 2 columnas para los botones */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 40px;
            max-width: 900px;
            margin: 0 auto;
            width: 100%;
        }
        
        /* Botones estilo tarjeta mejorados */
        .dashboard-option {
            background: white;
            border-radius: 15px;
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
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
            height: 100%;
            min-height: 300px;
        }
        
        /* Diferentes gradientes para cada opción */
        .option-avance {
            border-color: #e3f2fd;
        }
        
        .option-avance::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #3498db, #2980b9);
        }
        
        .option-analisis {
            border-color: #f3e5f5;
        }
        
        .option-analisis::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #9b59b6, #8e44ad);
        }
        
        .dashboard-option:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
            text-decoration: none;
            color: #2c3e50;
        }
        
        .option-avance:hover {
            border-color: #3498db;
        }
        
        .option-analisis:hover {
            border-color: #9b59b6;
        }
        
        .option-icon-wrapper {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 25px;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .option-analisis .option-icon-wrapper {
            background: linear-gradient(135deg, #f3e5f5, #e1bee7);
        }
        
        .dashboard-option:hover .option-icon-wrapper {
            transform: scale(1.15) rotate(5deg);
        }
        
        .option-avance:hover .option-icon-wrapper {
            background: linear-gradient(135deg, #3498db, #2980b9);
        }
        
        .option-analisis:hover .option-icon-wrapper {
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
        }
        
        .option-icon {
            font-size: 2.8rem;
            color: #3498db;
            transition: all 0.3s ease;
        }
        
        .option-analisis .option-icon {
            color: #9b59b6;
        }
        
        .dashboard-option:hover .option-icon {
            color: white;
        }
        
        .option-title {
            font-size: 1.6rem;
            font-weight: 800;
            margin-bottom: 15px;
            color: #2c3e50;
            letter-spacing: 0.5px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .option-description {
            font-size: 1rem;
            color: #666;
            line-height: 1.6;
            max-width: 90%;
            margin: 0 auto;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* Indicador de acceso */
        .access-indicator {
            position: absolute;
            top: 20px;
            right: 20px;
            background: #f8f9fa;
            border-radius: 20px;
            padding: 6px 12px;
            font-size: 0.8rem;
            color: #666;
            font-weight: 500;
            transition: all 0.3s ease;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .dashboard-option:hover .access-indicator {
            background: #2c3e50;
            color: white;
        }
        
        /* Badge para análisis IA */
        .ai-badge {
            position: absolute;
            top: 20px;
            left: 20px;
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
            color: white;
            border-radius: 20px;
            padding: 6px 12px;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
            margin-top: 80px;
        }
        
        .system-footer p {
            margin: 8px 0;
            color: #333;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* Animación sutil al cargar */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .dashboard-option {
            animation: fadeInUp 0.5s ease-out;
        }
        
        .option-avance {
            animation-delay: 0.1s;
        }
        
        .option-analisis {
            animation-delay: 0.2s;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
                max-width: 600px;
                gap: 30px;
            }
            
            .dashboard-option {
                padding: 35px 25px;
                min-height: 280px;
            }
            
            .dashboard-title {
                font-size: 1.8rem;
            }
            
            .option-icon-wrapper {
                width: 80px;
                height: 80px;
            }
            
            .option-icon {
                font-size: 2.5rem;
            }
            
            .option-title {
                font-size: 1.4rem;
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
            
            /* Breadcrumb responsive */
            .breadcrumb-nav {
                padding: 0 10px;
                margin-bottom: 15px;
            }
            
            .breadcrumb {
                font-size: 0.85rem;
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
                width: 70px;
                height: 70px;
            }
            
            .option-icon {
                font-size: 2.2rem;
            }
            
            .option-title {
                font-size: 1.3rem;
            }
            
            .system-footer {
                padding: 20px 15px;
                font-size: 0.85rem;
                margin-top: 60px;
            }
        }
        
        @media (max-width: 480px) {
            .dashboard-option {
                padding: 30px 20px;
                min-height: 250px;
            }
            
            .option-icon-wrapper {
                width: 65px;
                height: 65px;
                margin-bottom: 20px;
            }
            
            .option-icon {
                font-size: 2rem;
            }
            
            .option-title {
                font-size: 1.2rem;
            }
            
            .option-description {
                font-size: 0.9rem;
            }
            
            .access-indicator, .ai-badge {
                top: 15px;
                right: 15px;
                left: 15px;
                font-size: 0.7rem;
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
                <a href="../logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
        </div>
    </header>

    <!-- Breadcrumb Navigation (IGUAL AL DE DATA REFERIDOS) -->
    <div class="breadcrumb-nav">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../superadmin_dashboard.php"><i class="fas fa-home"></i> Panel Super Admin</a></li>
                <li class="breadcrumb-item active"><i class="fas fa-database"></i> Monitores</li>
            </ol>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="dashboard-title">
                <i class="fas fa-tachometer-alt"></i>
                <span>Panel de Control Super Admin</span>
            </div>
            <p class="dashboard-subtitle">
                Acceda a los módulos especializados de análisis y seguimiento del sistema de gestión política.
                Supervise el avance y obtenga insights avanzados.
            </p>
        </div>
        
        <!-- Grid de 2 columnas - SOLO 2 BOTONES -->
        <div class="dashboard-grid">
            <!-- AVANCE REFERENCIADOS -->
            <a href="superadmin_avance.php" class="dashboard-option option-avance">
                <div class="access-indicator">
                    <i class="fas fa-chart-line"></i> VER DASHBOARD
                </div>
                <div class="option-icon-wrapper">
                    <div class="option-icon">
                        <i class="fas fa-users-line"></i>
                    </div>
                </div>
                <div class="option-title">AVANCE REFERENCIADOS</div>
                <div class="option-description">
                    Monitoreo en tiempo real del progreso de referenciadores, 
                    métricas de rendimiento, gráficas comparativas y análisis 
                    detallado de avances por zona, sector y puesto.
                </div>
            </a>
            
            <!-- ANÁLISIS IA -->
            <a href="superadmin_analisis_ia.php" class="dashboard-option option-analisis">
                <div class="access-indicator">
                    <i class="fas fa-brain"></i> INICIAR ANÁLISIS
                </div>
                <div class="ai-badge">
                    <i class="fas fa-microchip"></i> INTELIGENCIA ARTIFICIAL
                </div>
                <div class="option-icon-wrapper">
                    <div class="option-icon">
                        <i class="fas fa-robot"></i>
                    </div>
                </div>
                <div class="option-title">ANÁLISIS IA</div>
                <div class="option-description">
                    Análisis predictivo y prescriptivo utilizando inteligencia artificial. 
                    Detección de patrones, predicción de tendencias, recomendaciones 
                    automatizadas y visualización avanzada de datos.
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
    <script>
        // Efecto de carga suave
        document.addEventListener('DOMContentLoaded', function() {
            // Efecto hover mejorado
            const options = document.querySelectorAll('.dashboard-option');
            
            options.forEach(option => {
                option.addEventListener('mouseenter', function() {
                    this.style.zIndex = '10';
                });
                
                option.addEventListener('mouseleave', function() {
                    this.style.zIndex = '1';
                });
            });
            
            // Prevenir clics múltiples rápidos
            const links = document.querySelectorAll('a.dashboard-option');
            links.forEach(link => {
                link.addEventListener('click', function(e) {
                    // Solo aplicar si no está deshabilitado
                    if (!this.classList.contains('disabled')) {
                        const originalHTML = this.innerHTML;
                        this.innerHTML = `
                            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                <i class="fas fa-spinner fa-spin" style="font-size: 2.5rem; margin-bottom: 15px; color: #3498db;"></i>
                                <span>Cargando módulo...</span>
                            </div>
                        `;
                        this.classList.add('disabled');
                        this.style.pointerEvents = 'none';
                        
                        // Restaurar después de 3 segundos (por si falla la navegación)
                        setTimeout(() => {
                            this.innerHTML = originalHTML;
                            this.classList.remove('disabled');
                            this.style.pointerEvents = 'auto';
                        }, 3000);
                    }
                });
            });
            
            // Breadcrumb hover effect
            const breadcrumbLinks = document.querySelectorAll('.breadcrumb-item a');
            breadcrumbLinks.forEach(link => {
                link.addEventListener('mouseenter', function() {
                    this.style.textDecoration = 'underline';
                });
                link.addEventListener('mouseleave', function() {
                    this.style.textDecoration = 'none';
                });
            });
        });
    </script>
</body>
</html>