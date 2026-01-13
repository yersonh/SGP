<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../models/ZonaModel.php';
require_once __DIR__ . '/../../models/SectorModel.php';
require_once __DIR__ . '/../../models/PuestoVotacionModel.php';

// Verificar permisos (todos los usuarios pueden ver detalles, pero solo administradores pueden editar)
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../index.php');
    exit();
}

// Obtener ID del usuario a ver
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ../dashboard.php');
    exit();
}

$id_usuario_ver = intval($_GET['id']);

$pdo = Database::getConnection();
$usuarioModel = new UsuarioModel($pdo);
$zonaModel = new ZonaModel($pdo);
$sectorModel = new SectorModel($pdo);
$puestoModel = new PuestoVotacionModel($pdo);

// Obtener datos del usuario a ver
$usuario_ver = $usuarioModel->getUsuarioById($id_usuario_ver);

if (!$usuario_ver) {
    header('Location: ../dashboard.php?error=usuario_no_encontrado');
    exit();
}

// Obtener datos del usuario logueado
$usuario_logueado = $usuarioModel->getUsuarioById($_SESSION['id_usuario']);

// Verificar si el usuario logueado es administrador
$es_administrador = ($usuario_logueado['tipo_usuario'] === 'Administrador');

// Cargar datos de zona, sector y puesto si existen
$zona_usuario = null;
$sector_usuario = null;
$puesto_usuario = null;

if ($usuario_ver['id_zona']) {
    $zona_usuario = $zonaModel->getUsuarioById($usuario_ver['id_zona']);
}

if ($usuario_ver['id_sector']) {
    $sector_usuario = $sectorModel->getUsuarioById($usuario_ver['id_sector']);
}

if ($usuario_ver['id_puesto']) {
    $puesto_usuario = $puestoModel->getUsuarioById($usuario_ver['id_puesto']);
}

// Formatear fecha de creación
$fecha_creacion_formateada = date('d/m/Y H:i:s', strtotime($usuario_ver['fecha_creacion']));
$ultimo_registro_formateada = !empty($usuario_ver['ultimo_registro']) ? 
    date('d/m/Y H:i:s', strtotime($usuario_ver['ultimo_registro'])) : 'Nunca';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Detalle de Usuario - SGP</title>
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
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            min-height: 100vh;
            background: 
                linear-gradient(rgba(0, 0, 0, 0.8), rgba(0, 0, 0, 0.8)),
                url('/imagenes/fondo.jpg') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .main-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(30, 30, 40, 0.9);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            padding: 15px 0;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.3);
            z-index: 1000;
        }
        
        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header-title h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
            color: white;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.1);
            padding: 8px 15px;
            border-radius: 20px;
            backdrop-filter: blur(5px);
            color: white;
        }
        
        .header-actions {
            display: flex;
            gap: 10px;
        }
        
        .header-btn {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            transition: all 0.3s;
            font-size: 0.9rem;
        }
        
        .header-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            transform: translateY(-2px);
        }
        
        .view-container {
            background: rgba(30, 30, 40, 0.85);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.5),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            width: 100%;
            max-width: 1200px;
            padding: 40px;
            animation: fadeIn 0.5s ease-out;
            margin-top: 80px;
            margin-bottom: 40px;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .view-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .view-header h2 {
            color: #ffffff;
            font-size: 1.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .view-header h2 i {
            color: #4fc3f7;
        }
        
        .view-header p {
            color: #b0bec5;
            font-size: 0.95rem;
            line-height: 1.5;
        }
        
        .user-view-info {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-view-info i {
            color: #4fc3f7;
            font-size: 1.5rem;
        }
        
        .user-view-info span {
            color: #ffffff;
            font-weight: 500;
        }
        
        .view-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
        }
        
        .view-group {
            margin-bottom: 0;
        }
        
        .view-group.full-width {
            grid-column: 1 / -1;
        }
        
        .view-label {
            display: block;
            margin-bottom: 8px;
            color: #cfd8dc;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .view-label i {
            color: #90a4ae;
            font-size: 1rem;
        }
        
        .view-value {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.3s;
            background: rgba(30, 30, 40, 0.7);
            color: #ffffff;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            min-height: 46px;
            display: flex;
            align-items: center;
        }
        
        .view-value-display {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            font-size: 0.95rem;
            background: rgba(30, 30, 40, 0.7);
            color: #ffffff;
            min-height: 46px;
            display: flex;
            align-items: center;
        }
        
        .badge-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .badge-active {
            background: rgba(39, 174, 96, 0.2);
            color: #27ae60;
            border: 1px solid rgba(39, 174, 96, 0.3);
        }
        
        .badge-inactive {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
            border: 1px solid rgba(231, 76, 60, 0.3);
        }
        
        .badge-type {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            background: rgba(155, 89, 182, 0.2);
            color: #9b59b6;
            border: 1px solid rgba(155, 89, 182, 0.3);
        }
        
        .photo-section {
            grid-column: 1 / -1;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .photo-display-container {
            display: inline-block;
            text-align: center;
        }
        
        .photo-preview {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            border: 4px solid rgba(79, 195, 247, 0.3);
            margin: 0 auto 15px;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        .photo-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .photo-placeholder {
            color: #90a4ae;
            font-size: 3.5rem;
        }
        
        .photo-label {
            color: #cfd8dc;
            font-weight: 500;
            margin-top: 10px;
        }
        
        .referenciados-stats {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #4fc3f7;
            line-height: 1;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #b0bec5;
            font-size: 0.9rem;
        }
        
        .progress-container {
            margin-top: 10px;
        }
        
        .progress-bar {
            height: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #4fc3f7, #2196f3);
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        
        .progress-text {
            color: #b0bec5;
            font-size: 0.85rem;
            margin-top: 5px;
            text-align: center;
        }
        
        .view-actions {
            grid-column: 1 / -1;
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
        }
        
        .back-btn {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 15px 40px;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-width: 200px;
            text-decoration: none;
            backdrop-filter: blur(5px);
        }
        
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
            border-color: rgba(255, 255, 255, 0.3);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
            color: white;
        }
        
        .edit-btn {
            background: linear-gradient(135deg, #f39c12, #d35400);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-width: 200px;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(243, 156, 18, 0.3);
        }
        
        .edit-btn:hover {
            background: linear-gradient(135deg, #e67e22, #c0392b);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(243, 156, 18, 0.4);
            color: white;
        }
        
        .view-footer {
            margin-top: 30px;
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #90a4ae;
            font-size: 0.85rem;
        }
        
        .view-footer i {
            color: #4fc3f7;
            margin-right: 5px;
        }
        
        @media (max-width: 768px) {
            .view-container {
                padding: 25px;
                margin-top: 100px;
                margin-bottom: 30px;
                max-width: 95%;
            }
            
            .view-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .header-title h1 {
                font-size: 1.2rem;
            }
            
            .header-actions {
                flex-direction: column;
                gap: 5px;
            }
            
            .header-btn {
                padding: 6px 10px;
                font-size: 0.8rem;
            }
            
            .view-actions {
                flex-direction: column;
            }
            
            .back-btn, .edit-btn {
                width: 100%;
                min-width: auto;
                padding: 12px;
            }
            
            .photo-preview {
                width: 150px;
                height: 150px;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            
            .view-container {
                padding: 20px;
                margin-top: 90px;
                margin-bottom: 20px;
            }
            
            .view-header h2 {
                font-size: 1.5rem;
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
                    <h1><i class="fas fa-user-circle"></i> Detalle de Usuario</h1>
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span>Usuario: <?php echo htmlspecialchars($usuario_logueado['nombres'] . ' ' . $usuario_logueado['apellidos']); ?></span>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="../dashboard.php" class="header-btn">
                        <i class="fas fa-arrow-left"></i> Volver al Dashboard
                    </a>
                    <a href="../logout.php" class="header-btn">
                        <i class="fas fa-sign-out-alt"></i> Salir
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main View -->
    <div class="view-container">
        <div class="view-header">
            <h2><i class="fas fa-user-circle"></i> Información Detallada del Usuario</h2>
            <p>Detalles completos del usuario seleccionado</p>
        </div>
        
        <!-- Información del usuario que se está viendo -->
        <div class="user-view-info">
            <i class="fas fa-eye"></i>
            <span>Visualizando información de: <strong><?php echo htmlspecialchars($usuario_ver['nombres'] . ' ' . $usuario_ver['apellidos']); ?></strong></span>
        </div>
        
        <div class="view-grid">
            <!-- Foto de perfil -->
            <div class="view-group full-width photo-section">
                <div class="photo-display-container">
                    <div class="photo-preview">
                        <?php if (!empty($usuario_ver['foto'])): ?>
                            <img src="<?php echo htmlspecialchars($usuario_ver['foto']); ?>" alt="Foto de perfil">
                        <?php else: ?>
                            <div class="photo-placeholder">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="photo-label">Foto de perfil</div>
                </div>
            </div>
            
            <!-- Nombres -->
            <div class="view-group">
                <label class="view-label">
                    <i class="fas fa-user"></i> Nombres
                </label>
                <div class="view-value">
                    <?php echo htmlspecialchars($usuario_ver['nombres'] ?? 'No especificado'); ?>
                </div>
            </div>
            
            <!-- Apellidos -->
            <div class="view-group">
                <label class="view-label">
                    <i class="fas fa-user"></i> Apellidos
                </label>
                <div class="view-value">
                    <?php echo htmlspecialchars($usuario_ver['apellidos'] ?? 'No especificado'); ?>
                </div>
            </div>
            
            <!-- Cédula -->
            <div class="view-group">
                <label class="view-label">
                    <i class="fas fa-id-card"></i> Cédula
                </label>
                <div class="view-value">
                    <?php echo htmlspecialchars($usuario_ver['cedula'] ?? 'No especificada'); ?>
                </div>
            </div>
            
            <!-- Nickname -->
            <div class="view-group">
                <label class="view-label">
                    <i class="fas fa-at"></i> Nombre de Usuario
                </label>
                <div class="view-value">
                    <?php echo htmlspecialchars($usuario_ver['nickname'] ?? 'No especificado'); ?>
                </div>
            </div>
            
            <!-- Correo -->
            <div class="view-group">
                <label class="view-label">
                    <i class="fas fa-envelope"></i> Correo Electrónico
                </label>
                <div class="view-value">
                    <?php echo htmlspecialchars($usuario_ver['correo'] ?? 'No especificado'); ?>
                </div>
            </div>
            
            <!-- Teléfono -->
            <div class="view-group">
                <label class="view-label">
                    <i class="fas fa-phone"></i> Teléfono
                </label>
                <div class="view-value">
                    <?php echo htmlspecialchars($usuario_ver['telefono'] ?? 'No especificado'); ?>
                </div>
            </div>
            
            <!-- Tipo de Usuario -->
            <div class="view-group">
                <label class="view-label">
                    <i class="fas fa-user-tag"></i> Tipo de Usuario
                </label>
                <div class="view-value-display">
                    <span class="badge-type"><?php echo htmlspecialchars($usuario_ver['tipo_usuario']); ?></span>
                </div>
            </div>
            
            <!-- Estado -->
            <div class="view-group">
                <label class="view-label">
                    <i class="fas fa-toggle-on"></i> Estado del Usuario
                </label>
                <div class="view-value-display">
                    <?php if ($usuario_ver['activo'] == true || $usuario_ver['activo'] == 't' || $usuario_ver['activo'] == 1): ?>
                        <span class="badge-status badge-active">
                            <i class="fas fa-check-circle"></i> Activo
                        </span>
                    <?php else: ?>
                        <span class="badge-status badge-inactive">
                            <i class="fas fa-times-circle"></i> Inactivo
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Zona -->
            <div class="view-group">
                <label class="view-label">
                    <i class="fas fa-map"></i> Zona
                </label>
                <div class="view-value">
                    <?php echo htmlspecialchars($zona_usuario['nombre'] ?? 'No asignada'); ?>
                </div>
            </div>
            
            <!-- Sector -->
            <div class="view-group">
                <label class="view-label">
                    <i class="fas fa-th"></i> Sector
                </label>
                <div class="view-value">
                    <?php echo htmlspecialchars($sector_usuario['nombre'] ?? 'No asignado'); ?>
                </div>
            </div>
            
            <!-- Puesto -->
            <div class="view-group">
                <label class="view-label">
                    <i class="fas fa-building"></i> Puesto de Votación
                </label>
                <div class="view-value">
                    <?php echo htmlspecialchars($puesto_usuario['nombre'] ?? 'No asignado'); ?>
                </div>
            </div>
            
            <!-- Tope -->
            <div class="view-group">
                <label class="view-label">
                    <i class="fas fa-chart-line"></i> Tope de Referenciados
                </label>
                <div class="view-value">
                    <?php echo htmlspecialchars($usuario_ver['tope'] ?? '0'); ?> referenciados
                </div>
            </div>
            
            <!-- Estadísticas de Referenciados -->
            <?php if ($usuario_ver['total_referenciados'] > 0 || $usuario_ver['tope'] > 0): ?>
            <div class="view-group full-width referenciados-stats">
                <label class="view-label">
                    <i class="fas fa-chart-bar"></i> Estadísticas de Referenciados
                </label>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $usuario_ver['total_referenciados']; ?></div>
                        <div class="stat-label">Total Referenciados</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $usuario_ver['tope']; ?></div>
                        <div class="stat-label">Tope Máximo</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $usuario_ver['porcentaje_tope']; ?>%</div>
                        <div class="stat-label">Porcentaje del Tope</div>
                    </div>
                </div>
                <?php if ($usuario_ver['tope'] > 0): ?>
                <div class="progress-container">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo min($usuario_ver['porcentaje_tope'], 100); ?>%"></div>
                    </div>
                    <div class="progress-text">
                        Progreso: <?php echo $usuario_ver['total_referenciados']; ?> de <?php echo $usuario_ver['tope']; ?> referenciados
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Fecha de Creación -->
            <div class="view-group">
                <label class="view-label">
                    <i class="fas fa-calendar-plus"></i> Fecha de Creación
                </label>
                <div class="view-value">
                    <?php echo $fecha_creacion_formateada; ?>
                </div>
            </div>
            
            <!-- Último Registro -->
            <div class="view-group">
                <label class="view-label">
                    <i class="fas fa-clock"></i> Último Acceso
                </label>
                <div class="view-value">
                    <?php echo $ultimo_registro_formateada; ?>
                </div>
            </div>
            
            <!-- Botones de acción -->
            <div class="view-group full-width view-actions">
                <a href="../dashboard.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Volver al Listado
                </a>
                
                <?php if ($es_administrador): ?>
                <a href="editar_usuario.php?id=<?php echo $id_usuario_ver; ?>" class="edit-btn">
                    <i class="fas fa-edit"></i> Editar Usuario
                </a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="view-footer">
            <p><i class="fas fa-info-circle"></i> Esta vista muestra información de solo lectura</p>
            <p><i class="fas fa-shield-alt"></i> Para modificar los datos, use la opción "Editar Usuario"</p>
        </div>
    </div>
</body>
</html>