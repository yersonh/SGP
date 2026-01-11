<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UsuarioModel.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: index.php');
    exit();
}

$pdo = Database::getConnection();
$model = new UsuarioModel($pdo);
$id_usuario_logueado = $_SESSION['id_usuario'];

// 1. Capturar la fecha actual
$fecha_actual = date('Y-m-d H:i:s');

// 2. Actualizar último registro usando el modelo
$model->actualizarUltimoRegistro($id_usuario_logueado, $fecha_actual);

// 3. Obtener datos del usuario logueado usando el modelo
$usuario_logueado = $model->getUsuarioById($id_usuario_logueado);

// 4. Obtener todos los usuarios usando el modelo
$usuarios = $model->getAllUsuarios();

// 5. Obtener estadísticas usando el modelo
$total_usuarios = $model->countUsuarios();
$usuarios_activos = $model->countUsuariosActivos();
$administradores = $model->countAdministradores();

// 6. Formatear fecha para mostrar
$fecha_formateada = date('d/m/Y H:i:s', strtotime($fecha_actual));
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios del Sistema - SGP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
        
        /* Header Styles - Mobile First */
        .main-header {
            background: linear-gradient(135deg, var(--primary-color), #1a252f);
            color: white;
            padding: 15px 0;
            margin-bottom: 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .header-container {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }
        
        .header-title {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
        }
        
        .header-title h1 {
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0;
            line-height: 1.2;
        }
        
        .user-count {
            background: rgba(255,255,255,0.15);
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
            white-space: nowrap;
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
            white-space: nowrap;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        /* Main Content - Mobile First */
        .main-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 15px;
        }
        
        /* Current User Info - Mobile First */
        .current-user-info {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 15px;
        }
        
        .current-user-header {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .current-user-header h3 {
            color: var(--primary-color);
            font-size: 1rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .login-time {
            color: #718096;
            font-size: 0.8rem;
            background: #f8fafc;
            padding: 5px 10px;
            border-radius: 5px;
            text-align: center;
            word-break: break-word;
        }
        
        .user-details {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        
        .detail-label {
            color: #718096;
            font-size: 0.75rem;
            margin-bottom: 3px;
        }
        
        .detail-value {
            color: var(--primary-color);
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        /* Top Bar - Mobile First */
        .top-bar {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .btn-add-user {
            background: var(--secondary-color);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s;
            text-decoration: none;
            font-size: 0.9rem;
            box-shadow: 0 3px 10px rgba(52, 152, 219, 0.2);
            width: 100%;
        }
        
        .btn-add-user:hover {
            background: #2980b9;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            width: 100%;
        }
        
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            text-align: center;
        }
        
        .stat-number {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--primary-color);
            line-height: 1;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 0.7rem;
            margin-top: 5px;
            line-height: 1.2;
        }
        
        /* Table Container - Mobile First */
        .table-container {
            background: white;
            border-radius: 10px;
            padding: 0;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            overflow: hidden;
            margin-top: 20px;
        }
        
        .table-header {
            background: var(--light-gray);
            padding: 15px;
            border-bottom: 2px solid #eaeaea;
        }
        
        .table-header h2 {
            color: var(--primary-color);
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        /* Table Styles - Mobile Friendly */
        .users-table {
            width: 100%;
            border-collapse: collapse;
            display: block;
        }
        
        .users-table thead {
            display: none; /* Hide table headers on mobile */
        }
        
        .users-table tbody {
            display: block;
        }
        
        .users-table tr {
            display: flex;
            flex-direction: column;
            border-bottom: 1px solid #f1f5f9;
            padding: 15px;
            position: relative;
        }
        
        .users-table tr:last-child {
            border-bottom: none;
        }
        
        .users-table td {
            display: flex;
            padding: 8px 0;
            border: none;
            align-items: flex-start;
        }
        
        .users-table td:before {
            content: attr(data-label);
            font-weight: 600;
            color: var(--primary-color);
            width: 120px;
            min-width: 120px;
            font-size: 0.8rem;
            padding-right: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .user-info {
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        
        .user-nickname {
            font-weight: 600;
            color: var(--primary-color);
            font-size: 0.95rem;
            margin-bottom: 3px;
        }
        
        .user-fullname {
            color: #718096;
            font-size: 0.85rem;
        }
        
        /* User Type and Status */
        .user-type {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
            background-color: rgba(155, 89, 182, 0.1);
            color: #9b59b6;
        }
        
        .user-status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-active {
            background-color: rgba(39, 174, 96, 0.1);
            color: var(--success-color);
        }
        
        .status-inactive {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
        }
        
        /* Action Buttons - Mobile Friendly */
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 8px;
            width: 100%;
        }
        
        .btn-action {
            padding: 10px 15px;
            border-radius: 6px;
            border: none;
            font-weight: 500;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            width: 100%;
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
        
        /* No Data Message */
        .no-data {
            text-align: center;
            padding: 40px 15px;
            color: #7f8c8d;
        }
        
        .no-data i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        .no-data h4 {
            font-size: 1.1rem;
            margin: 15px 0 10px;
        }
        
        .no-data p {
            font-size: 0.9rem;
            margin-bottom: 20px;
        }
        
        /* Footer Styles */
        .system-footer {
            text-align: center;
            margin-top: 30px;
            padding: 15px 0;
            border-top: 1px solid #eaeaea;
            color: #7f8c8d;
            font-size: 0.75rem;
            line-height: 1.5;
        }
        
        .system-footer p {
            margin: 5px 0;
        }
        
        .system-footer strong {
            color: #2c3e50;
            font-weight: 600;
        }
        
        /* Notification Styles for Mobile */
        .notification {
            position: fixed;
            top: 10px;
            right: 10px;
            left: 10px;
            padding: 12px 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
            animation: slideIn 0.3s ease-out;
            max-width: 400px;
            margin: 0 auto;
        }
        
        .notification-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .notification-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .notification-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .notification-content {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
            font-size: 0.85rem;
        }
        
        .notification-close {
            background: none;
            border: none;
            color: inherit;
            cursor: pointer;
            padding: 0;
            margin-left: 8px;
            opacity: 0.7;
            transition: opacity 0.2s;
        }
        
        .notification-close:hover {
            opacity: 1;
        }
        
        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        /* Tablet Styles (min-width: 768px) */
        @media (min-width: 768px) {
            body {
                font-size: 16px;
            }
            
            .main-header {
                padding: 20px 0;
            }
            
            .header-container {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
                padding: 0 20px;
            }
            
            .header-top {
                width: auto;
                gap: 20px;
            }
            
            .header-title h1 {
                font-size: 1.5rem;
            }
            
            .user-count {
                font-size: 0.85rem;
                padding: 4px 12px;
            }
            
            .logout-btn {
                padding: 8px 15px;
                font-size: 0.9rem;
            }
            
            .main-container {
                padding: 0 20px;
                margin: 30px auto;
            }
            
            .current-user-info {
                padding: 20px;
            }
            
            .current-user-header {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }
            
            .current-user-header h3 {
                font-size: 1.1rem;
            }
            
            .login-time {
                font-size: 0.9rem;
                text-align: right;
            }
            
            .user-details {
                flex-direction: row;
                grid-template-columns: repeat(3, 1fr);
                gap: 20px;
            }
            
            .detail-label {
                font-size: 0.85rem;
            }
            
            .detail-value {
                font-size: 1rem;
            }
            
            .top-bar {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }
            
            .btn-add-user {
                width: auto;
                padding: 12px 25px;
            }
            
            .stats-container {
                grid-template-columns: repeat(3, auto);
                gap: 15px;
                width: auto;
            }
            
            .stat-card {
                padding: 15px 20px;
                min-width: 120px;
            }
            
            .stat-number {
                font-size: 1.8rem;
            }
            
            .stat-label {
                font-size: 0.85rem;
            }
            
            .table-header {
                padding: 20px;
            }
            
            .table-header h2 {
                font-size: 1.3rem;
            }
            
            /* Show table headers on tablet and desktop */
            .users-table {
                display: table;
            }
            
            .users-table thead {
                display: table-header-group;
            }
            
            .users-table tbody {
                display: table-row-group;
            }
            
            .users-table tr {
                display: table-row;
                padding: 0;
            }
            
            .users-table td {
                display: table-cell;
                padding: 15px 20px;
            }
            
            .users-table td:before {
                display: none;
            }
            
            .action-buttons {
                flex-direction: row;
                gap: 10px;
            }
            
            .btn-action {
                width: auto;
                padding: 8px 15px;
                font-size: 0.85rem;
                justify-content: flex-start;
            }
            
            .no-data {
                padding: 60px 20px;
            }
            
            .no-data i {
                font-size: 4rem;
            }
            
            .no-data h4 {
                font-size: 1.3rem;
            }
            
            .no-data p {
                font-size: 1rem;
            }
            
            .system-footer {
                font-size: 0.85rem;
                padding: 20px 0;
                margin-top: 40px;
            }
            
            .notification {
                top: 20px;
                right: 20px;
                left: auto;
                min-width: 300px;
                max-width: 400px;
            }
        }
        
        /* Desktop Styles (min-width: 992px) */
        @media (min-width: 992px) {
            .user-details {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
            
            .users-table td {
                padding: 20px;
            }
        }
        
        /* Large Desktop Styles (min-width: 1200px) */
        @media (min-width: 1200px) {
            .main-container,
            .header-container {
                max-width: 1200px;
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
                    <h1>
                        <i class="fas fa-users"></i> Usuarios del Sistema
                        <span class="user-count"><?php echo $total_usuarios; ?> usuarios</span>
                    </h1>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
        </div>
    </header>

    <div class="main-container">
        <!-- Información del usuario actual -->
        <div class="current-user-info">
            <div class="current-user-header">
                <h3><i class="fas fa-user-circle"></i> Sesión activa</h3>
                <span class="login-time" id="current-time"><?php echo $fecha_formateada; ?></span>
            </div>
            <div class="user-details">
                <div class="detail-item">
                    <span class="detail-label">Usuario:</span>
                    <span class="detail-value">
                        <?php 
                        $nombre_completo = '';
                        if (!empty($usuario_logueado['nombres']) && !empty($usuario_logueado['apellidos'])) {
                            $nombre_completo = htmlspecialchars($usuario_logueado['nombres'] . ' ' . $usuario_logueado['apellidos']);
                        } else {
                            $nombre_completo = htmlspecialchars($usuario_logueado['nickname']);
                        }
                        echo $nombre_completo;
                        ?>
                    </span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Nickname:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($usuario_logueado['nickname']); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Tipo de usuario:</span>
                    <span class="detail-value user-type"><?php echo htmlspecialchars($usuario_logueado['tipo_usuario']); ?></span>
                </div>
            </div>
        </div>

        <!-- Top Bar con botón y estadísticas -->
        <div class="top-bar">
            <a href="agregar_usuario.php" class="btn-add-user">
                <i class="fas fa-plus-circle"></i> AGREGAR USUARIO
            </a>
            
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_usuarios; ?></div>
                    <div class="stat-label">Usuarios totales</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $usuarios_activos; ?></div>
                    <div class="stat-label">Usuarios activos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $administradores; ?></div>
                    <div class="stat-label">Administradores</div>
                </div>
            </div>
        </div>

        <!-- Tabla de usuarios -->
        <div class="table-container">
            <div class="table-header">
                <h2><i class="fas fa-list-alt"></i> Listado de Usuarios</h2>
            </div>
            
            <?php if ($total_usuarios > 0): ?>
            <div class="table-responsive">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>NICKNAME</th>
                            <th>NOMBRE COMPLETO</th>
                            <th>TIPO</th>
                            <th>ESTADO</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                        <?php 
                        $activo = $usuario['activo'];
                        $esta_activo = ($activo === true || $activo === 't' || $activo == 1);
                        ?>
                        <tr>
                            <td data-label="NICKNAME">
                                <div class="user-info">
                                    <span class="user-nickname"><?php echo htmlspecialchars($usuario['nickname']); ?></span>
                                    <span class="text-muted" style="font-size: 0.8rem;">ID: #<?php echo $usuario['id_usuario']; ?></span>
                                </div>
                            </td>
                            
                            <td data-label="NOMBRE COMPLETO">
                                <div class="user-info">
                                    <?php 
                                    if (!empty($usuario['nombres']) && !empty($usuario['apellidos'])) {
                                        echo '<span class="user-fullname">' . htmlspecialchars($usuario['nombres'] . ' ' . $usuario['apellidos']) . '</span>';
                                    } else {
                                        echo '<span class="text-muted fst-italic">Sin asignar</span>';
                                    }
                                    ?>
                                </div>
                            </td>
                            
                            <td data-label="TIPO">
                                <span class="user-type"><?php echo htmlspecialchars($usuario['tipo_usuario']); ?></span>
                            </td>
                            
                            <td data-label="ESTADO">
                                <?php if ($esta_activo): ?>
                                    <span class="user-status status-active">
                                        <i class="fas fa-check-circle"></i> Activo
                                    </span>
                                <?php else: ?>
                                    <span class="user-status status-inactive">
                                        <i class="fas fa-times-circle"></i> Inactivo
                                    </span>
                                <?php endif; ?>
                            </td>
                            
                            <td data-label="ACCIONES">
                                <div class="action-buttons">
                                    <?php if ($esta_activo): ?>
                                        <button class="btn-action btn-deactivate" 
                                                title="Dar de baja al usuario"
                                                onclick="darDeBaja(<?php echo $usuario['id_usuario']; ?>, '<?php echo htmlspecialchars($usuario['nickname']); ?>', this)">
                                            <i class="fas fa-user-slash"></i> DAR DE BAJA
                                        </button>
                                    <?php else: ?>
                                        <button class="btn-action btn-activate" 
                                                title="Reactivar usuario"
                                                onclick="reactivarUsuario(<?php echo $usuario['id_usuario']; ?>, '<?php echo htmlspecialchars($usuario['nickname']); ?>', this)">
                                            <i class="fas fa-user-check"></i> REACTIVAR
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="no-data">
                <i class="fas fa-users"></i>
                <h4 class="mt-3 mb-2">No hay usuarios registrados</h4>
                <p class="text-muted">El sistema no tiene usuarios registrados actualmente.</p>
                <a href="agregar_usuario.php" class="btn-add-user mt-3">
                    <i class="fas fa-plus-circle"></i> Agregar Primer Usuario
                </a>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Footer del sistema -->
        <footer class="system-footer">
            <p>© Derechos de autor Reservados. 
                <strong>Ing. Rubén Darío González García</strong> • 
                SISGONTech • Colombia © • <?php echo date('Y'); ?>
            </p>
            <p>Contacto: <strong>+57 3106310227</strong> • 
                Email: <strong>sisgonnet@gmail.com</strong>
            </p>
        </footer>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Actualizar hora en tiempo real
        function updateCurrentTime() {
            const now = new Date();
            const day = now.getDate().toString().padStart(2, '0');
            const month = (now.getMonth() + 1).toString().padStart(2, '0');
            const year = now.getFullYear();
            const hours = now.getHours().toString().padStart(2, '0');
            const minutes = now.getMinutes().toString().padStart(2, '0');
            const seconds = now.getSeconds().toString().padStart(2, '0');
            const timeString = `${day}/${month}/${year} ${hours}:${minutes}:${seconds}`;
            
            const currentTimeElement = document.getElementById('current-time');
            if (currentTimeElement) {
                currentTimeElement.textContent = timeString;
            }
        }
        
        // Actualizar cada segundo
        updateCurrentTime();
        setInterval(updateCurrentTime, 1000);
        
        // Función para mostrar notificaciones
        function showNotification(message, type = 'info') {
            // Eliminar notificación anterior si existe
            const oldNotification = document.querySelector('.notification');
            if (oldNotification) {
                oldNotification.remove();
            }
            
            // Crear nueva notificación
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <div class="notification-content">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'}"></i>
                    <span>${message}</span>
                </div>
                <button class="notification-close">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            document.body.appendChild(notification);
            
            // Botón para cerrar
            notification.querySelector('.notification-close').addEventListener('click', () => {
                notification.remove();
            });
            
            // Auto-eliminar después de 5 segundos
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 5000);
        }
        
        // Función para dar de baja un usuario
        async function darDeBaja(idUsuario, nickname, button) {
            if (!confirm(`¿Está seguro de dar de BAJA al usuario "${nickname}"?\n\nEl usuario no podrá acceder al sistema, pero se mantendrán sus registros.`)) {
                return;
            }
            
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
            button.disabled = true;
            
            try {
                const response = await fetch('ajax/usuario.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `accion=desactivar&id_usuario=${idUsuario}`
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Cambiar el estado en la tabla
                    const row = button.closest('tr');
                    const statusBadge = row.querySelector('.user-status');
                    if (statusBadge) {
                        statusBadge.className = 'user-status status-inactive';
                        statusBadge.innerHTML = '<i class="fas fa-times-circle"></i> Inactivo';
                    }
                    
                    // Cambiar el botón a "REACTIVAR"
                    button.innerHTML = '<i class="fas fa-user-check"></i> REACTIVAR';
                    button.className = 'btn-action btn-activate';
                    button.onclick = function() {
                        reactivarUsuario(idUsuario, nickname, button);
                    };
                    
                    // Actualizar estadísticas
                    updateStats(-1, 0);
                    
                    showNotification('Usuario dado de baja correctamente', 'success');
                } else {
                    showNotification('Error: ' + (data.message || 'No se pudo dar de baja el usuario'), 'error');
                    button.innerHTML = originalText;
                    button.disabled = false;
                }
            } catch (error) {
                showNotification('Error de conexión: ' + error.message, 'error');
                button.innerHTML = originalText;
                button.disabled = false;
            }
        }
        
        // Función para reactivar un usuario
        async function reactivarUsuario(idUsuario, nickname, button) {
            if (!confirm(`¿Desea REACTIVAR al usuario "${nickname}"?`)) {
                return;
            }
            
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
            button.disabled = true;
            
            try {
                const response = await fetch('ajax/usuario.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `accion=reactivar&id_usuario=${idUsuario}`
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Cambiar el estado en la tabla
                    const row = button.closest('tr');
                    const statusBadge = row.querySelector('.user-status');
                    if (statusBadge) {
                        statusBadge.className = 'user-status status-active';
                        statusBadge.innerHTML = '<i class="fas fa-check-circle"></i> Activo';
                    }
                    
                    // Cambiar el botón a "DAR DE BAJA"
                    button.innerHTML = '<i class="fas fa-user-slash"></i> DAR DE BAJA';
                    button.className = 'btn-action btn-deactivate';
                    button.onclick = function() {
                        darDeBaja(idUsuario, nickname, button);
                    };
                    
                    // Actualizar estadísticas
                    updateStats(1, 0);
                    
                    showNotification('Usuario reactivado correctamente', 'success');
                } else {
                    showNotification('Error: ' + data.message, 'error');
                    button.innerHTML = originalText;
                    button.disabled = false;
                }
            } catch (error) {
                showNotification('Error de conexión: ' + error.message, 'error');
                button.disabled = false;
            }
        }
        
        // Función para actualizar estadísticas
        function updateStats(changeActivos, changeTotal) {
            const statCards = document.querySelectorAll('.stat-card');
            if (statCards.length >= 2) {
                const totalElement = statCards[0].querySelector('.stat-number');
                const activosElement = statCards[1].querySelector('.stat-number');
                
                if (totalElement && changeTotal !== 0) {
                    const current = parseInt(totalElement.textContent);
                    totalElement.textContent = current + changeTotal;
                }
                
                if (activosElement && changeActivos !== 0) {
                    const current = parseInt(activosElement.textContent);
                    activosElement.textContent = current + changeActivos;
                }
                
                // Actualizar contador en el header
                const userCountElement = document.querySelector('.user-count');
                if (userCountElement && changeTotal !== 0) {
                    const text = userCountElement.textContent;
                    const count = parseInt(text);
                    userCountElement.textContent = (count + changeTotal) + ' usuarios';
                }
            }
        }
        
        // Efecto hover en filas de la tabla (solo en desktop)
        if (window.innerWidth >= 768) {
            document.querySelectorAll('.users-table tbody tr').forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.backgroundColor = '#f8fafc';
                });
                
                row.addEventListener('mouseleave', function() {
                    this.style.backgroundColor = '';
                });
            });
        }
        
        // Manejar cambios de tamaño de ventana
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 768) {
                // Restaurar efecto hover en desktop
                document.querySelectorAll('.users-table tbody tr').forEach(row => {
                    row.addEventListener('mouseenter', function() {
                        this.style.backgroundColor = '#f8fafc';
                    });
                    
                    row.addEventListener('mouseleave', function() {
                        this.style.backgroundColor = '';
                    });
                });
            }
        });
    </script>
</body>
</html>