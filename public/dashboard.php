<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: index.php');
    exit();
}

$pdo = Database::getConnection();
$id_usuario_logueado = $_SESSION['id_usuario'];

// 1. Capturar la fecha actual
$fecha_actual = date('Y-m-d H:i:s');

// 2. Actualizar último registro en la base de datos
$query_actualizar = "UPDATE usuario SET ultimo_registro = ? WHERE id_usuario = ?";
$stmt_actualizar = $pdo->prepare($query_actualizar);
$stmt_actualizar->execute([$fecha_actual, $id_usuario_logueado]);

// 3. Obtener datos del usuario logueado
$query_usuario = "SELECT u.*, pe.nombres, pe.apellidos 
                  FROM usuario u 
                  LEFT JOIN personal_electoral pe ON u.id_usuario = pe.id_usuario 
                  WHERE u.id_usuario = ?";
$stmt_usuario = $pdo->prepare($query_usuario);
$stmt_usuario->execute([$id_usuario_logueado]);
$usuario_logueado = $stmt_usuario->fetch();

// 4. Obtener todos los usuarios para la tabla
$query_usuarios = "SELECT u.*, pe.nombres, pe.apellidos 
                   FROM usuario u 
                   LEFT JOIN personal_electoral pe ON u.id_usuario = pe.id_usuario 
                   ORDER BY u.fecha_creacion DESC";
$stmt_usuarios = $pdo->query($query_usuarios);
$usuarios = $stmt_usuarios->fetchAll();

// 5. Contar número de usuarios
$total_usuarios = count($usuarios);

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
        
        .password-display {
            font-family: monospace;
            letter-spacing: 2px;
            color: #718096;
            font-size: 1rem;
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 10px;
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
        
        .btn-delete {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
            border: 1px solid rgba(231, 76, 60, 0.2);
        }
        
        .btn-delete:hover {
            background-color: rgba(231, 76, 60, 0.2);
            color: var(--danger-color);
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
            }
            
            .btn-action {
                justify-content: center;
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
                    <div class="stat-number">
                        <?php 
                        $usuarios_activos = array_filter($usuarios, function($u) {
                            $activo = $u['activo'];
                            return ($activo === true || $activo === 't' || $activo == 1);
                        });
                        echo count($usuarios_activos);
                        ?>
                    </div>
                    <div class="stat-label">Usuarios activos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?php 
                        $usuarios_admin = array_filter($usuarios, function($u) {
                            return $u['tipo_usuario'] == 'Administrador';
                        });
                        echo count($usuarios_admin);
                        ?>
                    </div>
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
                        <tr>
                            <td>
                                <div class="user-info">
                                    <span class="user-nickname"><?php echo htmlspecialchars($usuario['nickname']); ?></span>
                                    <span class="text-muted" style="font-size: 0.8rem;">ID: #<?php echo $usuario['id_usuario']; ?></span>
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
                                <?php 
                                $activo = $usuario['activo'];
                                $esta_activo = ($activo === true || $activo === 't' || $activo == 1);
                                ?>
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
                                    <button class="btn-action btn-edit" title="Editar usuario">
                                        <i class="fas fa-edit"></i> EDITAR
                                    </button>
                                    <button class="btn-action btn-delete" title="Eliminar usuario">
                                        <i class="fas fa-trash"></i> BORRAR
                                    </button>
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
        
        <!-- Información del sistema -->
        <div class="text-center text-muted mt-4 mb-4">
            <small>
                Sistema de Gestión Personal (SGP) &copy; <?php echo date('Y'); ?> | 
                Última actualización: <span id="last-update"><?php echo $fecha_formateada; ?></span>
            </small>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Actualizar hora en tiempo real
        function updateCurrentTime() {
            const now = new Date();
            const options = { 
                day: '2-digit', 
                month: '2-digit', 
                year: 'numeric',
                hour: '2-digit', 
                minute: '2-digit',
                second: '2-digit'
            };
            const timeString = now.toLocaleDateString('es-ES', options);
            
            // Actualizar tiempo en el header
            const currentTimeElement = document.getElementById('current-time');
            if (currentTimeElement) {
                currentTimeElement.textContent = timeString;
            }
            
            // Actualizar última actualización en el footer
            const lastUpdateElement = document.getElementById('last-update');
            if (lastUpdateElement) {
                lastUpdateElement.textContent = timeString;
            }
        }
        
        // Actualizar cada segundo
        updateCurrentTime();
        setInterval(updateCurrentTime, 1000);
        
        // Confirmación para eliminar
        document.querySelectorAll('.btn-delete').forEach(button => {
            button.addEventListener('click', function() {
                if (confirm('¿Está seguro de que desea eliminar este usuario?\nEsta acción no se puede deshacer.')) {
                    alert('Funcionalidad de eliminar en desarrollo');
                }
            });
        });
        
        // Acción para editar
        document.querySelectorAll('.btn-edit').forEach(button => {
            button.addEventListener('click', function() {
                alert('Funcionalidad de editar en desarrollo');
            });
        });
        
        // Efecto hover en filas de la tabla
        document.querySelectorAll('.users-table tbody tr').forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = '#f8fafc';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
            });
        });
    </script>
</body>
</html>