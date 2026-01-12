<?php
session_start();

// 🔥 VERIFICACIÓN DE SESIÓN OBLIGATORIA
if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['tipo_usuario'])) {
    $_SESSION['login_required'] = 'Debe iniciar sesión para acceder a esta página';
    header('Location: index.php');
    exit();
}

// 🔥 VERIFICAR EXPIRACIÓN DE SESIÓN (1 hora)
$session_timeout = 3600;
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > $session_timeout)) {
    session_unset();
    session_destroy();
    $_SESSION['session_expired'] = 'Sesión expirada por inactividad';
    header('Location: index.php');
    exit();
}

// 🔥 ACTUALIZAR ACTIVIDAD
$_SESSION['last_activity'] = time();

// 🔥 VERIFICAR PERMISOS (solo Administrador y SuperAdmin)
$allowed_types = ['Administrador', 'SuperAdmin'];
if (!in_array($_SESSION['tipo_usuario'], $allowed_types)) {
    // Redirigir según tipo de usuario
    switch ($_SESSION['tipo_usuario']) {
        case 'Referenciador':
            header('Location: referenciador.php');
            break;
        case 'Descargador':
            header('Location: descargador.php');
            break;
        default:
            // Si no tiene permiso para ningún dashboard
            session_unset();
            session_destroy();
            $_SESSION['login_required'] = 'No tiene permisos para acceder a esta sección';
            header('Location: index.php');
            break;
    }
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../models/ReferenciadoModel.php';

$pdo = Database::getConnection();
$usuarioModel = new UsuarioModel($pdo);
$referenciadoModel = new ReferenciadoModel($pdo);

// Obtener datos del usuario actual
$usuarioActual = $usuarioModel->getUsuarioById($_SESSION['id_usuario']);

// Obtener estadísticas
$totalUsuarios = $usuarioModel->countUsuarios();
$usuariosActivos = $usuarioModel->countUsuariosActivos();
$totalAdministradores = $usuarioModel->countAdministradores();

// Obtener últimos usuarios registrados
$ultimosUsuarios = $usuarioModel->getAllUsuarios();
$ultimosUsuarios = array_slice($ultimosUsuarios, 0, 5); // Solo 5 más recientes

// Obtener estadísticas generales de referenciados
$totalReferenciados = $referenciadoModel->countTotalReferenciados();
$referenciadosHoy = $referenciadoModel->countReferenciadosHoy();
$referenciadosSemana = $referenciadoModel->countReferenciadosSemana();

// Mensaje de éxito si se creó un usuario
$success_message = '';
if (isset($_GET['success']) && $_GET['success'] == 'usuario_creado') {
    $success_message = "Usuario creado exitosamente";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SGP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            min-height: 100vh;
            background: 
                linear-gradient(rgba(0, 0, 0, 0.85), rgba(0, 0, 0, 0.85)),
                url('/imagenes/fondo.jpg') no-repeat center center fixed;
            background-size: cover;
            color: #ffffff;
        }
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 250px;
            background: rgba(30, 30, 40, 0.9);
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            z-index: 100;
            transition: all 0.3s;
        }
        
        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid #4fc3f7;
        }
        
        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .user-details h3 {
            font-size: 1rem;
            margin-bottom: 5px;
            color: #ffffff;
        }
        
        .user-details p {
            font-size: 0.8rem;
            color: #b0bec5;
            background: rgba(79, 195, 247, 0.2);
            padding: 3px 8px;
            border-radius: 12px;
            display: inline-block;
        }
        
        .nav-menu {
            list-style: none;
        }
        
        .nav-item {
            margin-bottom: 5px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: #cfd8dc;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(79, 195, 247, 0.1);
            color: #4fc3f7;
            border-left-color: #4fc3f7;
        }
        
        .nav-link i {
            width: 20px;
            text-align: center;
        }
        
        .logout-link {
            margin-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 20px;
        }
        
        .logout-link .nav-link {
            color: #ff6b6b;
        }
        
        .logout-link .nav-link:hover {
            background: rgba(255, 107, 107, 0.1);
            color: #ff6b6b;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .header h1 {
            font-size: 1.8rem;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .header-actions {
            display: flex;
            gap: 15px;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4fc3f7, #29b6f6);
            color: white;
            box-shadow: 0 4px 15px rgba(79, 195, 247, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(79, 195, 247, 0.4);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(30, 30, 40, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            border-color: rgba(79, 195, 247, 0.3);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 15px;
        }
        
        .stat-card.users .stat-icon {
            background: rgba(79, 195, 247, 0.2);
            color: #4fc3f7;
        }
        
        .stat-card.active .stat-icon {
            background: rgba(102, 187, 106, 0.2);
            color: #66bb6a;
        }
        
        .stat-card.admins .stat-icon {
            background: rgba(156, 39, 176, 0.2);
            color: #9c27b0;
        }
        
        .stat-card.referrals .stat-icon {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: #ffffff;
        }
        
        .stat-label {
            color: #b0bec5;
            font-size: 0.9rem;
        }
        
        /* Tables */
        .content-section {
            background: rgba(30, 30, 40, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 30px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .section-header h2 {
            font-size: 1.3rem;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: rgba(79, 195, 247, 0.1);
        }
        
        th {
            padding: 15px;
            text-align: left;
            color: #4fc3f7;
            font-weight: 600;
            font-size: 0.9rem;
            border-bottom: 2px solid rgba(79, 195, 247, 0.3);
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: #cfd8dc;
        }
        
        tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .user-avatar-small {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
        }
        
        .user-avatar-small img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .status-active {
            background: rgba(102, 187, 106, 0.2);
            color: #66bb6a;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-inactive {
            background: rgba(244, 67, 54, 0.2);
            color: #f44336;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn-icon {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-edit {
            background: rgba(33, 150, 243, 0.2);
            color: #2196f3;
        }
        
        .btn-edit:hover {
            background: rgba(33, 150, 243, 0.3);
        }
        
        .btn-delete {
            background: rgba(244, 67, 54, 0.2);
            color: #f44336;
        }
        
        .btn-delete:hover {
            background: rgba(244, 67, 54, 0.3);
        }
        
        /* Messages */
        .success-message, .error-message, .info-message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            backdrop-filter: blur(5px);
        }
        
        .success-message {
            background: rgba(76, 175, 80, 0.15);
            color: #a5d6a7;
            border-left: 4px solid #4caf50;
        }
        
        .error-message {
            background: rgba(244, 67, 54, 0.15);
            color: #ff8a80;
            border-left: 4px solid #f44336;
        }
        
        .info-message {
            background: rgba(33, 150, 243, 0.15);
            color: #81d4fa;
            border-left: 4px solid #2196f3;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .header-actions {
                width: 100%;
                justify-content: space-between;
            }
            
            .mobile-menu-toggle {
                display: block;
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 101;
                background: rgba(30, 30, 40, 0.9);
                border: 1px solid rgba(255, 255, 255, 0.1);
                width: 50px;
                height: 50px;
                border-radius: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-size: 1.2rem;
                cursor: pointer;
            }
        }
        
        @media (min-width: 769px) {
            .mobile-menu-toggle {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="user-info">
                    <div class="user-avatar">
                        <img src="<?php echo $_SESSION['foto_url'] ?? '/imagenes/imagendefault.png'; ?>" 
                             alt="<?php echo htmlspecialchars($_SESSION['nombres'] ?? 'Usuario'); ?>"
                             onerror="this.src='/imagenes/imagendefault.png'">
                    </div>
                    <div class="user-details">
                        <h3><?php echo htmlspecialchars($_SESSION['nombres'] ?? '') . ' ' . htmlspecialchars($_SESSION['apellidos'] ?? ''); ?></h3>
                        <p><?php echo htmlspecialchars($_SESSION['tipo_usuario'] ?? 'Usuario'); ?></p>
                    </div>
                </div>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link active">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="usuarios.php" class="nav-link">
                        <i class="fas fa-users"></i>
                        <span>Usuarios</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="agregar_usuario.php" class="nav-link">
                        <i class="fas fa-user-plus"></i>
                        <span>Agregar Usuario</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="referenciados.php" class="nav-link">
                        <i class="fas fa-address-book"></i>
                        <span>Referenciados</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="reportes.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reportes</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="configuracion.php" class="nav-link">
                        <i class="fas fa-cog"></i>
                        <span>Configuración</span>
                    </a>
                </li>
                <li class="nav-item logout-link">
                    <a href="logout.php" class="nav-link" onclick="return confirm('¿Está seguro de cerrar sesión?');">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Cerrar Sesión</span>
                    </a>
                </li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <div class="header">
                <h1><i class="fas fa-tachometer-alt"></i> Dashboard Administrativo</h1>
                <div class="header-actions">
                    <a href="agregar_usuario.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Agregar Usuario
                    </a>
                    <a href="reportes.php" class="btn btn-secondary">
                        <i class="fas fa-chart-bar"></i> Ver Reportes
                    </a>
                </div>
            </div>
            
            <!-- Messages -->
            <?php if ($success_message): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($success_message); ?></span>
            </div>
            <?php endif; ?>
            
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card users">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-value"><?php echo $totalUsuarios; ?></div>
                    <div class="stat-label">Usuarios Totales</div>
                </div>
                
                <div class="stat-card active">
                    <div class="stat-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-value"><?php echo $usuariosActivos; ?></div>
                    <div class="stat-label">Usuarios Activos</div>
                </div>
                
                <div class="stat-card admins">
                    <div class="stat-icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="stat-value"><?php echo $totalAdministradores; ?></div>
                    <div class="stat-label">Administradores</div>
                </div>
                
                <div class="stat-card referrals">
                    <div class="stat-icon">
                        <i class="fas fa-address-book"></i>
                    </div>
                    <div class="stat-value"><?php echo $totalReferenciados; ?></div>
                    <div class="stat-label">Referenciados Totales</div>
                </div>
            </div>
            
            <!-- Recent Users -->
            <div class="content-section">
                <div class="section-header">
                    <h2><i class="fas fa-clock"></i> Últimos Usuarios Registrados</h2>
                    <a href="usuarios.php" class="btn btn-secondary">Ver Todos</a>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Foto</th>
                                <th>Nombre</th>
                                <th>Usuario</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimosUsuarios as $usuario): ?>
                            <tr>
                                <td>
                                    <div class="user-avatar-small">
                                        <img src="<?php echo $usuario['foto_url'] ?? '/imagenes/imagendefault.png'; ?>" 
                                             alt="<?php echo htmlspecialchars($usuario['nombres'] ?? ''); ?>"
                                             onerror="this.src='/imagenes/imagendefault.png'">
                                    </div>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($usuario['nombres'] ?? '') . ' ' . htmlspecialchars($usuario['apellidos'] ?? ''); ?></strong><br>
                                    <small><?php echo htmlspecialchars($usuario['cedula'] ?? ''); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($usuario['nickname'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($usuario['tipo_usuario'] ?? ''); ?></td>
                                <td>
                                    <?php if (($usuario['activo'] ?? false)): ?>
                                        <span class="status-active">Activo</span>
                                    <?php else: ?>
                                        <span class="status-inactive">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="editar_usuario.php?id=<?php echo $usuario['id_usuario']; ?>" 
                                           class="btn-icon btn-edit" 
                                           title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($usuario['id_usuario'] != $_SESSION['id_usuario']): ?>
                                        <a href="eliminar_usuario.php?id=<?php echo $usuario['id_usuario']; ?>" 
                                           class="btn-icon btn-delete" 
                                           title="Eliminar"
                                           onclick="return confirm('¿Está seguro de eliminar este usuario?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Referral Stats -->
            <div class="content-section">
                <div class="section-header">
                    <h2><i class="fas fa-chart-line"></i> Estadísticas de Referenciados</h2>
                    <a href="reportes.php" class="btn btn-secondary">Ver Detalles</a>
                </div>
                <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(255, 193, 7, 0.2); color: #ffc107;">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                        <div class="stat-value"><?php echo $referenciadosHoy; ?></div>
                        <div class="stat-label">Hoy</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(156, 39, 176, 0.2); color: #9c27b0;">
                            <i class="fas fa-calendar-week"></i>
                        </div>
                        <div class="stat-value"><?php echo $referenciadosSemana; ?></div>
                        <div class="stat-label">Esta Semana</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(76, 175, 80, 0.2); color: #4caf50;">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="stat-value"><?php echo $totalReferenciados; ?></div>
                        <div class="stat-label">Totales</div>
                    </div>
                </div>
            </div>
        </main>
        
        <!-- Mobile Menu Toggle -->
        <div class="mobile-menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu toggle
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.getElementById('sidebar');
            
            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                this.innerHTML = sidebar.classList.contains('active') 
                    ? '<i class="fas fa-times"></i>' 
                    : '<i class="fas fa-bars"></i>';
            });
            
            // Auto-hide messages
            setTimeout(() => {
                const messages = document.querySelectorAll('.success-message, .error-message, .info-message');
                messages.forEach(msg => {
                    msg.style.opacity = '0';
                    msg.style.transition = 'opacity 0.5s';
                    setTimeout(() => msg.remove(), 500);
                });
            }, 5000);
            
            // Session timeout warning (10 minutos antes)
            const sessionTimeout = <?php echo $session_timeout; ?>;
            const warningTime = 600; // 10 minutos en segundos
            
            setTimeout(() => {
                if (confirm('Su sesión está a punto de expirar. ¿Desea extenderla?')) {
                    // Hacer una petición para renovar la sesión
                    fetch('renew_session.php')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                console.log('Sesión renovada');
                            }
                        });
                }
            }, (sessionTimeout - warningTime) * 1000);
            
            // Actualizar actividad del usuario
            function updateActivity() {
                fetch('update_activity.php', { method: 'POST' });
            }
            
            // Actualizar actividad en eventos
            ['click', 'mousemove', 'keypress'].forEach(event => {
                document.addEventListener(event, updateActivity, { passive: true });
            });
            
            // Actualizar cada 5 minutos
            setInterval(updateActivity, 5 * 60 * 1000);
        });
    </script>
</body>
</html>