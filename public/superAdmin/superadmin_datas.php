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

// Obtener estadísticas de usuarios
$total_usuarios = $usuarioModel->countUsuarios();
$usuarios_activos = $usuarioModel->countUsuariosActivos();
$administradores = $usuarioModel->countAdministradores();
$referenciadores = $usuarioModel->countReferenciadores();
$descargadores = $usuarioModel->countDescargadores();
$superadmin = $usuarioModel->countSuperAdmin();
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
        
        .data-referidos .stat-number {
            color: #3498db;
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
            
            .data-stats {
                flex-direction: column;
                gap: 10px;
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
        }

        /* ESTILOS PARA LOS BOTONES DE ACCIONES (COPIADOS DE LA VISTA DE USUARIOS) */
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-action {
            padding: 8px 15px;
            border-radius: 6px;
            border: none;
            font-weight: 500;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
            text-decoration: none;
        }
        
        .btn-view {
            background-color: rgba(155, 89, 182, 0.1);
            color: #9b59b6;
            border: 1px solid rgba(155, 89, 182, 0.2);
        }

        .btn-view:hover {
            background-color: rgba(155, 89, 182, 0.2);
            color: #9b59b6;
        }
        
        .btn-edit {
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--secondary-color);
            border: 1px solid rgba(52, 152, 219, 0.2);
        }
        
        .btn-edit:hover {
            background-color: rgba(52, 152, 219, 0.2);
            color: var(--secondary-color);
        }
        
        .btn-deactivate {
            background-color: rgba(243, 156, 18, 0.1);
            color: var(--warning-color);
            border: 1px solid rgba(243, 156, 18, 0.2);
        }
        
        .btn-deactivate:hover {
            background-color: rgba(243, 156, 18, 0.2);
            color: var(--warning-color);
        }
        
        .btn-activate {
            background-color: rgba(39, 174, 96, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(39, 174, 96, 0.2);
        }
        
        .btn-activate:hover {
            background-color: rgba(39, 174, 96, 0.2);
            color: var(--success-color);
        }

        /* SECCIÓN DE ESTADÍSTICAS */
        .stats-container {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            justify-content: center;
            margin: 20px 0;
        }
        
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 15px 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            text-align: center;
            min-width: 120px;
        }
        
        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2c3e50;
            line-height: 1;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 0.85rem;
            margin-top: 5px;
        }

        /* SECCIÓN DE ACCIONES RÁPIDAS */
        .quick-actions-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            margin-bottom: 40px;
        }

        .quick-actions-section h3 {
            color: #2c3e50;
            font-size: 1.3rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .quick-action-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            transition: all 0.3s;
        }

        .quick-action-item:hover {
            background: white;
            border-color: #3498db;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .quick-action-icon {
            font-size: 2rem;
            color: #3498db;
            margin-bottom: 10px;
        }

        .quick-action-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .quick-action-desc {
            color: #666;
            font-size: 0.85rem;
            line-height: 1.4;
        }

        /* Colores para iconos */
        .action-icon-users {
            color: #3498db;
        }

        .action-icon-edit {
            color: #9b59b6;
        }

        .action-icon-eye {
            color: #27ae60;
        }

        .action-icon-admin {
            color: #e74c3c;
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

        <!-- Sección de Estadísticas -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_usuarios; ?></div>
                <div class="stat-label">Total Usuarios</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $usuarios_activos; ?></div>
                <div class="stat-label">Usuarios Activos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $administradores; ?></div>
                <div class="stat-label">Administradores</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $referenciadores; ?></div>
                <div class="stat-label">Referenciadores</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $descargadores; ?></div>
                <div class="stat-label">Descargadores</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $superadmin; ?></div>
                <div class="stat-label">Super Admin</div>
            </div>
        </div>

        <!-- Sección de Acciones Rápidas -->
        <div class="quick-actions-section">
            <h3><i class="fas fa-bolt"></i> Acciones Rápidas</h3>
            <div class="action-buttons" style="display: flex; flex-wrap: wrap; gap: 15px; justify-content: center;">
                <!-- BOTÓN DE VER MI PERFIL -->
                <button class="btn-action btn-view" 
                        onclick="window.location.href='../administrador/ver_usuario.php?id=<?php echo $_SESSION['id_usuario']; ?>'"
                        title="Ver mi perfil">
                    <i class="fas fa-eye"></i> VER MI PERFIL
                </button>
                
                <!-- BOTÓN DE EDITAR MI PERFIL -->
                <button class="btn-action btn-edit" 
                        onclick="window.location.href='../administrador/editar_usuario.php?id=<?php echo $_SESSION['id_usuario']; ?>'"
                        title="Editar mi perfil">
                    <i class="fas fa-edit"></i> EDITAR PERFIL
                </button>
                
                <!-- BOTÓN DE GESTIÓN DE USUARIOS -->
                <button class="btn-action btn-edit" 
                        onclick="window.location.href='../administrador/usuarios.php'"
                        title="Gestionar todos los usuarios">
                    <i class="fas fa-users"></i> GESTIÓN USUARIOS
                </button>
                
                <!-- BOTÓN DE PANEL SUPER ADMIN -->
                <button class="btn-action btn-view" 
                        onclick="window.location.href='../superadmin_dashboard.php'"
                        title="Volver al panel principal">
                    <i class="fas fa-tachometer-alt"></i> PANEL PRINCIPAL
                </button>
                
                <!-- BOTÓN PARA IR A DATA REFERIDOS -->
                <button class="btn-action btn-activate" 
                        onclick="window.location.href='data_referidos.php'"
                        title="Ir a Data Referidos">
                    <i class="fas fa-users"></i> DATA REFERIDOS
                </button>
                
                <!-- BOTÓN PARA IR A DATA DESCARGADORES -->
                <button class="btn-action btn-activate" 
                        onclick="window.location.href='data_descargadores.php'"
                        title="Ir a Data Descargadores">
                    <i class="fas fa-user-check"></i> DATA DESCARGADORES
                </button>
            </div>
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
                <div class="data-stats">
                    <div class="stat-item">
                        <span class="stat-number">1,245</span>
                        <span class="stat-label">Total Referidos</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">832</span>
                        <span class="stat-label">Por Votar</span>
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
                    Información detallada de quienes ya han votado. 
                    Control y seguimiento de la descarga de votos verificados.
                </div>
                <div class="data-stats">
                    <div class="stat-item">
                        <span class="stat-number">413</span>
                        <span class="stat-label">Ya Votaron</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">67%</span>
                        <span class="stat-label">Efectividad</span>
                    </div>
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
        $(document).ready(function() {
            // Efecto hover mejorado en tarjetas
            $('.data-option').hover(
                function() {
                    $(this).css('transform', 'translateY(-8px)');
                },
                function() {
                    $(this).css('transform', 'translateY(0)');
                }
            );
            
            // Efecto hover en botones de acción
            $('.btn-action').hover(
                function() {
                    $(this).css('transform', 'translateY(-2px)');
                    $(this).css('box-shadow', '0 4px 8px rgba(0,0,0,0.1)');
                },
                function() {
                    $(this).css('transform', 'translateY(0)');
                    $(this).css('box-shadow', 'none');
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