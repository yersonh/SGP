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
$referenciadores = $model->countReferenciadores();
$descargadores = $model->countDescargadores();
$superadmin = $model->countSuperAdmin();
$tipos_usuario_stats = $model->countTodosLosTipos();

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
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
        }
        
        /* Header Styles - Similar al login */
        .main-header {
            background: linear-gradient(135deg, var(--primary-color), #1a252f);
            color: white;
            padding: 20px 0;
            margin-bottom: 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .header-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .header-title h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }
        
        .user-count {
            background: rgba(255,255,255,0.15);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 0.9rem;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        /* Main Content */
        .main-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        /* Top Bar - Agregar Usuario y Estadísticas */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .btn-add-user {
            background: var(--secondary-color);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            text-decoration: none;
            font-size: 1rem;
            box-shadow: 0 3px 10px rgba(52, 152, 219, 0.2);
        }
        
        .btn-add-user:hover {
            background: #2980b9;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }
        
        .stats-container {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
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
            color: var(--primary-color);
            line-height: 1;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 0.85rem;
            margin-top: 5px;
        }
        
        /* Table Container */
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
            padding: 20px;
            border-bottom: 2px solid #eaeaea;
        }
        
        .table-header h2 {
            color: var(--primary-color);
            font-size: 1.3rem;
            font-weight: 600;
            margin: 0;
        }
        
        /* Table Styles - Similar a la imagen */
        .users-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .users-table thead {
            background-color: #f1f5f9;
        }
        
        .users-table th {
            padding: 18px 20px;
            text-align: left;
            color: var(--primary-color);
            font-weight: 600;
            font-size: 0.95rem;
            border-bottom: 2px solid #e2e8f0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .users-table tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: background-color 0.2s;
        }
        
        .users-table tbody tr:hover {
            background-color: #f8fafc;
        }
        
        .users-table td {
            padding: 20px;
            vertical-align: middle;
            color: #4a5568;
        }
        
        .user-info {
            display: flex;
            flex-direction: column;
        }
        
        .user-nickname {
            font-weight: 600;
            color: var(--primary-color);
            font-size: 1.05rem;
            margin-bottom: 3px;
        }
        
        .user-fullname {
            color: #718096;
            font-size: 0.9rem;
        }
        
        /* Action Buttons */
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
        
        /* User Status */
        .user-status {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
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
        
        /* User Type */
        .user-type {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            background-color: rgba(155, 89, 182, 0.1);
            color: #9b59b6;
        }
        
        /* No Data Message */
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
        }
        
        .no-data i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .header-container {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .logout-btn {
                align-self: flex-end;
            }
            
            .top-bar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .stats-container {
                justify-content: center;
            }
            
            .users-table {
                display: block;
                overflow-x: auto;
            }
        }
        
        @media (max-width: 768px) {
            .users-table th,
            .users-table td {
                padding: 15px 10px;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 5px;
            }
            
            .btn-action {
                justify-content: center;
                width: 100%;
            }
        }
        
        /* Current User Info */
        .current-user-info {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        
        .current-user-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .current-user-header h3 {
            color: var(--primary-color);
            font-size: 1.1rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .login-time {
            color: #718096;
            font-size: 0.9rem;
            background: #f8fafc;
            padding: 5px 10px;
            border-radius: 5px;
        }
        
        .user-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        
        .detail-label {
            color: #718096;
            font-size: 0.85rem;
            margin-bottom: 5px;
        }
        
        .detail-value {
            color: var(--primary-color);
            font-weight: 500;
        }
        
        /* Footer Styles */
        .system-footer {
            text-align: center;
            margin-top: 40px;
            padding: 20px 0;
            border-top: 1px solid #eaeaea;
            color: #7f8c8d;
            font-size: 0.85rem;
            line-height: 1.6;
        }
        
        .system-footer p {
            margin: 5px 0;
        }
        
        .system-footer strong {
            color: #2c3e50;
            font-weight: 600;
        }
        
        /* Notification Styles */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-width: 300px;
            max-width: 400px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
            animation: slideIn 0.3s ease-out;
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
            gap: 10px;
            flex: 1;
        }
        
        .notification-close {
            background: none;
            border: none;
            color: inherit;
            cursor: pointer;
            padding: 0;
            margin-left: 10px;
            opacity: 0.7;
            transition: opacity 0.2s;
        }
        
        .notification-close:hover {
            opacity: 1;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
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
    </style>
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="header-container">
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
                    <span class="detail-label">Usuario Actual:</span>
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
                            <td>
                                <div class="user-info">
                                    <span class="user-nickname"><?php echo htmlspecialchars($usuario['nickname']); ?></span>
                                </div>
                            </td>
                            
                            <td>
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
                            
                            <td>
                                <span class="user-type"><?php echo htmlspecialchars($usuario['tipo_usuario']); ?></span>
                            </td>
                            
                            <td>
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
                            
                            <td>

                                <div class="action-buttons">
                                    <!-- BOTÓN DE VER DETALLE -->
                                    <button class="btn-action btn-view" 
                                            onclick="window.location.href='administrador/ver_usuario.php?id=<?php echo $usuario['id_usuario']; ?>'"
                                            title="Ver detalle del usuario">
                                        <i class="fas fa-eye"></i> VER
                                    </button>
                                    <!-- BOTÓN DE EDITAR -->
                                    <button class="btn-action btn-edit" 
                                            onclick="window.location.href='administrador/editar_usuario.php?id=<?php echo $usuario['id_usuario']; ?>'"
                                            title="Editar usuario">
                                        <i class="fas fa-edit"></i> EDITAR
                                    </button>
                                    
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
                    
                    // Crear nuevo botón REACTIVAR con event listener
                    const newButton = document.createElement('button');
                    newButton.className = 'btn-action btn-activate';
                    newButton.title = 'Reactivar usuario';
                    newButton.innerHTML = '<i class="fas fa-user-check"></i> REACTIVAR';
                    newButton.addEventListener('click', function() {
                        reactivarUsuario(idUsuario, nickname, newButton);
                    });
                    
                    // Reemplazar el botón en el contenedor
                    const buttonContainer = button.parentNode;
                    buttonContainer.removeChild(button);
                    buttonContainer.appendChild(newButton);
                    
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
                    
                    // Crear nuevo botón DAR DE BAJA con event listener
                    const newButton = document.createElement('button');
                    newButton.className = 'btn-action btn-deactivate';
                    newButton.title = 'Dar de baja al usuario';
                    newButton.innerHTML = '<i class="fas fa-user-slash"></i> DAR DE BAJA';
                    newButton.addEventListener('click', function() {
                        darDeBaja(idUsuario, nickname, newButton);
                    });
                    
                    // Reemplazar el botón en el contenedor
                    const buttonContainer = button.parentNode;
                    buttonContainer.removeChild(button);
                    buttonContainer.appendChild(newButton);
                    
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
        
        // Efecto hover en filas de la tabla
        document.querySelectorAll('.users-table tbody tr').forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = '#f8fafc';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
            });
        });
        
        // Manejar parámetros de éxito/error en la URL
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            
            if (urlParams.has('success')) {
                const successType = urlParams.get('success');
                let message = '';
                
                switch(successType) {
                    case 'usuario_creado':
                        message = 'Usuario creado correctamente';
                        break;
                    case 'usuario_actualizado':
                        message = 'Usuario actualizado correctamente';
                        break;
                    default:
                        message = 'Operación realizada correctamente';
                }
                
                if (message) {
                    showNotification(message, 'success');
                    // Limpiar parámetro de la URL sin recargar la página
                    window.history.replaceState({}, document.title, window.location.pathname);
                }
            }
            
            if (urlParams.has('error')) {
                const errorType = urlParams.get('error');
                let message = '';
                
                switch(errorType) {
                    case 'usuario_no_encontrado':
                        message = 'Usuario no encontrado';
                        break;
                    default:
                        message = 'Ocurrió un error en la operación';
                }
                
                if (message) {
                    showNotification(message, 'error');
                    // Limpiar parámetro de la URL sin recargar la página
                    window.history.replaceState({}, document.title, window.location.pathname);
                }
            }
        });
    </script>
</body>
</html>