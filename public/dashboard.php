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
            --primary-blue: #2c3e50;
            --secondary-blue: #3498db;
            --light-gray: #f8f9fa;
            --dark-gray: #343a40;
            --success-green: #28a745;
            --danger-red: #dc3545;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }
        
        /* Header Styles */
        .main-header {
            background: linear-gradient(135deg, var(--primary-blue), #1a252f);
            color: white;
            padding: 25px 0;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .header-left h1 {
            font-size: 1.8rem;
            font-weight: 600;
            margin: 0;
        }
        
        .user-count {
            display: inline-block;
            background: rgba(255,255,255,0.15);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            margin-left: 10px;
            font-weight: 500;
        }
        
        /* User Info Card */
        .user-info-sidebar {
            background: white;
            border-radius: 10px;
            padding: 0;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            border: 1px solid #eaeaea;
            overflow: hidden;
        }
        
        .user-info-header {
            background: var(--secondary-blue);
            color: white;
            padding: 15px;
            text-align: center;
        }
        
        .user-info-header h5 {
            margin: 0;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .user-info-body {
            padding: 20px;
        }
        
        .user-field {
            margin-bottom: 20px;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 15px;
        }
        
        .user-field:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .field-label {
            color: #7f8c8d;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 5px;
            display: block;
        }
        
        .field-value {
            color: var(--primary-blue);
            font-size: 1rem;
            font-weight: 600;
        }
        
        .field-value.nickname {
            color: var(--secondary-blue);
        }
        
        .field-value.date {
            color: #e74c3c;
            font-weight: 500;
        }
        
        /* Add User Button */
        .btn-add-user {
            background: var(--secondary-blue);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .btn-add-user:hover {
            background: #2980b9;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }
        
        /* Main Content */
        .main-content {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            margin-top: 20px;
        }
        
        .section-title {
            color: var(--primary-blue);
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        /* Table Styles */
        .table-custom {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }
        
        .table-custom thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            color: var(--primary-blue);
            font-weight: 600;
            padding: 15px;
            vertical-align: middle;
        }
        
        .table-custom tbody td {
            padding: 15px;
            vertical-align: middle;
            border-bottom: 1px solid #eee;
        }
        
        .table-custom tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }
        
        /* Status Badges */
        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .badge-active {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success-green);
        }
        
        .badge-inactive {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger-red);
        }
        
        /* User Type Badges */
        .badge-admin {
            background-color: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
        }
        
        .badge-user {
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--secondary-blue);
        }
        
        /* Action Buttons */
        .btn-action {
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            border: 1px solid #dee2e6;
            background: white;
            color: #6c757d;
            transition: all 0.2s;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
        }
        
        .btn-action.edit:hover {
            color: var(--secondary-blue);
            border-color: var(--secondary-blue);
        }
        
        .btn-action.delete:hover {
            color: var(--danger-red);
            border-color: var(--danger-red);
        }
        
        /* No Data Message */
        .no-data {
            text-align: center;
            padding: 50px 20px;
            color: #7f8c8d;
        }
        
        .no-data i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .header-right {
                width: 100%;
            }
            
            .user-info-sidebar {
                margin-bottom: 20px;
            }
        }
        
        /* Logout Button */
        .btn-logout {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-logout:hover {
            background: rgba(255,255,255,0.2);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="container">
            <div class="header-content">
                <div class="header-left">
                    <h1>
                        <i class="fas fa-users me-2"></i>Usuarios del Sistema
                        <span class="user-count">
                            <i class="fas fa-user-friends me-1"></i><?php echo $total_usuarios; ?> usuarios
                        </span>
                    </h1>
                </div>
                
                <div class="header-right">
                    <a href="logout.php" class="btn-logout">
                        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="row">
            <!-- Columna izquierda: Información del usuario logueado -->
            <div class="col-lg-4">
                <div class="user-info-sidebar">
                    <div class="user-info-header">
                        <h5><i class="fas fa-user-circle me-2"></i>Información de Sesión</h5>
                    </div>
                    
                    <div class="user-info-body">
                        <div class="user-field">
                            <span class="field-label">Usuario:</span>
                            <span class="field-value">
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
                        
                        <div class="user-field">
                            <span class="field-label">Nickname:</span>
                            <span class="field-value nickname">
                                <?php echo htmlspecialchars($usuario_logueado['nickname']); ?>
                            </span>
                        </div>
                        
                        <div class="user-field">
                            <span class="field-label">Fecha de ingreso:</span>
                            <span class="field-value date">
                                <i class="fas fa-calendar-alt me-1"></i><?php echo $fecha_formateada; ?>
                            </span>
                        </div>
                        
                        <div class="user-field">
                            <span class="field-label">Tipo de usuario:</span>
                            <span class="field-value">
                                <?php 
                                $tipo_usuario = htmlspecialchars($usuario_logueado['tipo_usuario']);
                                $badge_class = ($tipo_usuario == 'Administrador') ? 'badge-admin' : 'badge-user';
                                ?>
                                <span class="badge-status <?php echo $badge_class; ?>">
                                    <?php echo $tipo_usuario; ?>
                                </span>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Botón de Agregar Usuario -->
                <div class="mt-3">
                    <a href="agregar_usuario.php" class="btn-add-user">
                        <i class="fas fa-plus-circle"></i> Agregar Nuevo Usuario
                    </a>
                </div>
            </div>
            
            <!-- Columna derecha: Tabla de usuarios -->
            <div class="col-lg-8">
                <div class="main-content">
                    <h2 class="section-title">
                        <i class="fas fa-list-alt me-2"></i>Listado de Usuarios
                    </h2>
                    
                    <?php if ($total_usuarios > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nickname</th>
                                    <th>Nombre Completo</th>
                                    <th>Tipo Usuario</th>
                                    <th>Fecha Creación</th>
                                    <th>Último Registro</th>
                                    <th>Tope</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td class="fw-bold">#<?php echo $usuario['id_usuario']; ?></td>
                                    
                                    <td>
                                        <div class="fw-bold"><?php echo htmlspecialchars($usuario['nickname']); ?></div>
                                    </td>
                                    
                                    <td>
                                        <?php 
                                        if (!empty($usuario['nombres']) && !empty($usuario['apellidos'])) {
                                            echo htmlspecialchars($usuario['nombres'] . ' ' . $usuario['apellidos']);
                                        } else {
                                            echo '<span class="text-muted fst-italic">Sin asignar</span>';
                                        }
                                        ?>
                                    </td>
                                    
                                    <td>
                                        <?php 
                                        $tipo = htmlspecialchars($usuario['tipo_usuario']);
                                        $badge_class_tipo = ($tipo == 'Administrador') ? 'badge-admin' : 'badge-user';
                                        ?>
                                        <span class="badge-status <?php echo $badge_class_tipo; ?>">
                                            <?php echo $tipo; ?>
                                        </span>
                                    </td>
                                    
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($usuario['fecha_creacion'])); ?>
                                    </td>
                                    
                                    <td>
                                        <?php 
                                        if ($usuario['ultimo_registro']) {
                                            echo date('d/m/Y H:i', strtotime($usuario['ultimo_registro']));
                                        } else {
                                            echo '<span class="text-muted fst-italic">Nunca</span>';
                                        }
                                        ?>
                                    </td>
                                    
                                    <td>
                                        <?php echo htmlspecialchars($usuario['tope']); ?>
                                    </td>
                                    
                                    <td>
                                        <?php if ($usuario['activo'] == true || $usuario['activo'] === 't' || $usuario['activo'] == 1): ?>
                                            <span class="badge-status badge-active">
                                                <i class="fas fa-check-circle me-1"></i>Activo
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-status badge-inactive">
                                                <i class="fas fa-times-circle me-1"></i>Inactivo
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td>
                                        <div class="d-flex gap-2">
                                            <button class="btn-action edit" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-action delete" title="Eliminar">
                                                <i class="fas fa-trash"></i>
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
            </div>
        </div>
    </div>

    <!-- Footer (opcional) -->
    <footer class="mt-5 py-4 text-center text-muted border-top">
        <div class="container">
            <p class="mb-0">
                <small>
                    Sistema de Gestión Personal (SGP) &copy; <?php echo date('Y'); ?> 
                    | Última actualización: <?php echo $fecha_formateada; ?>
                </small>
            </p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Actualizar la hora en tiempo real
        function updateTime() {
            const now = new Date();
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit', 
                minute: '2-digit',
                second: '2-digit'
            };
            const dateString = now.toLocaleDateString('es-ES', options);
            
            // Podemos mostrar esto en algún lugar si queremos
            console.log('Hora actualizada:', dateString);
        }
        
        // Actualizar cada segundo
        setInterval(updateTime, 1000);
        
        // Confirmación para eliminar
        document.querySelectorAll('.btn-action.delete').forEach(button => {
            button.addEventListener('click', function() {
                if (confirm('¿Está seguro de que desea eliminar este usuario?\nEsta acción no se puede deshacer.')) {
                    // Aquí iría la lógica para eliminar el usuario
                    alert('Funcionalidad de eliminar en desarrollo');
                }
            });
        });
        
        // Acción para editar
        document.querySelectorAll('.btn-action.edit').forEach(button => {
            button.addEventListener('click', function() {
                // Aquí iría la lógica para editar el usuario
                alert('Funcionalidad de editar en desarrollo');
            });
        });
    </script>
</body>
</html>