<?php
// Habilitar errores para debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/LiderModel.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

// Verificar permisos (solo administradores pueden ver detalles de líderes)
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'Administrador') {
    header('Location: ../index.php');
    exit();
}

// Obtener ID del líder a ver
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ../dashboard.php');
    exit();
}

$id_lider_ver = intval($_GET['id']);

$pdo = Database::getConnection();
$liderModel = new LiderModel($pdo);
$usuarioModel = new UsuarioModel($pdo);

// Obtener datos del líder a ver
$lider_ver = $liderModel->getById($id_lider_ver);

if (!$lider_ver) {
    header('Location: ../dashboard.php?error=lider_no_encontrado');
    exit();
}

// Obtener datos del usuario logueado
$usuario_logueado = $usuarioModel->getUsuarioById($_SESSION['id_usuario']);

// Obtener referenciador si existe
$referenciador = null;
if (!empty($lider_ver['id_usuario'])) {
    $referenciador = $usuarioModel->getUsuarioById($lider_ver['id_usuario']);
}

// Formatear fechas
$fecha_creacion_formateada = !empty($lider_ver['fecha_creacion']) ? 
    date('d/m/Y H:i:s', strtotime($lider_ver['fecha_creacion'])) : 'No registrada';
    
$fecha_actualizacion_formateada = !empty($lider_ver['fecha_actualizacion']) ? 
    date('d/m/Y H:i:s', strtotime($lider_ver['fecha_actualizacion'])) : 'No actualizada';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Detalle de Líder - SGP</title>
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
            --lider-color: #9b59b6;
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
            color: #9b59b6;
        }
        
        .view-header p {
            color: #b0bec5;
            font-size: 0.95rem;
            line-height: 1.5;
        }
        
        .user-view-info {
            background: rgba(155, 89, 182, 0.1);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            border-left: 4px solid #9b59b6;
        }
        
        .user-view-info i {
            color: #9b59b6;
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
        
        .badge-referenciador {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            background: rgba(52, 152, 219, 0.2);
            color: #3498db;
            border: 1px solid rgba(52, 152, 219, 0.3);
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
            border: 4px solid rgba(155, 89, 182, 0.3);
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
        
        .referenciador-info {
            background: rgba(52, 152, 219, 0.05);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
        }
        
        .referenciador-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        
        .detail-label {
            color: #b0bec5;
            font-size: 0.85rem;
            margin-bottom: 5px;
        }
        
        .detail-value {
            color: #ffffff;
            font-weight: 500;
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
            color: #9b59b6;
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
                    <h1><i class="fas fa-user-tie"></i> Detalle de Líder</h1>
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span>Usuario: <?php echo htmlspecialchars($usuario_logueado['nombres'] . ' ' . $usuario_logueado['apellidos']); ?></span>
                    </div>
                </div>
                <div class="header-actions">
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
            <h2><i class="fas fa-user-tie"></i> Información Detallada del Líder</h2>
            <p>Detalles completos del líder seleccionado</p>
        </div>
        
        <!-- Información del líder que se está viendo -->
        <div class="user-view-info">
            <i class="fas fa-eye"></i>
            <span>Visualizando información de: <strong><?php echo htmlspecialchars($lider_ver['nombres'] . ' ' . $lider_ver['apellidos']); ?></strong></span>
        </div>
        
        <div class="view-grid">
            <!-- Foto de perfil (si existe en futuras versiones) 
            <div class="view-group full-width photo-section">
                <div class="photo-display-container">
                    <div class="photo-preview">
                        <div class="photo-placeholder">
                            <i class="fas fa-user-tie"></i>
                        </div>
                    </div>
                    <div class="photo-label">Representación del Líder</div>
                </div>
            </div>-->
            
            <!-- Nombres -->
            <div class="view-group">
                <label class="view-label">
                    <i class="fas fa-user"></i> Nombres
                </label>
                <div class="view-value">
                    <?php echo htmlspecialchars($lider_ver['nombres'] ?? 'No especificado'); ?>
                </div>
            </div>
            
            <!-- Apellidos -->
            <div class="view-group">
                <label class="view-label">
                    <i class="fas fa-user"></i> Apellidos
                </label>
                <div class="view-value">
                    <?php echo htmlspecialchars($lider_ver['apellidos'] ?? 'No especificado'); ?>
                </div>
            </div>
            
            <!-- Cédula -->
            <div class="view-group">
                <label class="view-label">
                    <i class="fas fa-id-card"></i> Cédula
                </label>
                <div class="view-value">
                    <?php echo htmlspecialchars($lider_ver['cc'] ?? 'No especificada'); ?>
                </div>
            </div>
            
            <!-- Teléfono -->
            <div class="view-group">
                <label class="view-label">
                    <i class="fas fa-phone"></i> Teléfono
                </label>
                <div class="view-value">
                    <?php echo htmlspecialchars($lider_ver['telefono'] ?? 'No especificado'); ?>
                </div>
            </div>
            
            <!-- Correo -->
            <div class="view-group">
                <label class="view-label">
                    <i class="fas fa-envelope"></i> Correo Electrónico
                </label>
                <div class="view-value">
                    <?php echo htmlspecialchars($lider_ver['correo'] ?? 'No especificado'); ?>
                </div>
            </div>
            
            <!-- Estado -->
            <div class="view-group">
                <label class="view-label">
                    <i class="fas fa-toggle-on"></i> Estado del Líder
                </label>
                <div class="view-value-display">
                    <?php if ($lider_ver['estado'] == true || $lider_ver['estado'] === 't' || $lider_ver['estado'] == 1): ?>
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
            
            <!-- Información del Referenciador -->
            <div class="view-group full-width referenciador-info">
                <label class="view-label">
                    <i class="fas fa-user-friends"></i> Información del Referenciador
                </label>
                <div class="referenciador-details">
                    <?php if ($referenciador): ?>
                        <div class="detail-item">
                            <div class="detail-label">Referenciador Asignado</div>
                            <div class="detail-value">
                                <span class="badge-referenciador">
                                    <i class="fas fa-user-check"></i> Asignado
                                </span>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Nombre</div>
                            <div class="detail-value"><?php echo htmlspecialchars($referenciador['nombres'] . ' ' . $referenciador['apellidos']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Nickname</div>
                            <div class="detail-value"><?php echo htmlspecialchars($referenciador['nickname']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Teléfono</div>
                            <div class="detail-value"><?php echo htmlspecialchars($referenciador['telefono'] ?? 'No especificado'); ?></div>
                        </div>
                    <?php else: ?>
                        <div class="detail-item">
                            <div class="detail-label">Referenciador Asignado</div>
                            <div class="detail-value" style="color: #e74c3c;">
                                <i class="fas fa-exclamation-circle"></i> Sin asignar
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Estado</div>
                            <div class="detail-value" style="color: #f39c12;">
                                <i class="fas fa-info-circle"></i> Este líder no tiene referenciador asignado
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Fecha de Registro -->
            <div class="view-group">
                <label class="view-label">
                    <i class="fas fa-calendar-plus"></i> Fecha de Registro
                </label>
                <div class="view-value">
                    <?php echo $fecha_creacion_formateada; ?>
                </div>
            </div>
            
            <!-- Última Actualización -->
            <div class="view-group">
                <label class="view-label">
                    <i class="fas fa-history"></i> Última Actualización
                </label>
                <div class="view-value">
                    <?php echo $fecha_actualizacion_formateada; ?>
                </div>
            </div>
            
            <!-- ID del Líder 
            <div class="view-group">
                <label class="view-label">
                    <i class="fas fa-hashtag"></i> ID del Líder
                </label>
                <div class="view-value">
                    <?php echo htmlspecialchars($lider_ver['id_lider']); ?>
                </div>
            </div>-->
            
            <!-- Botones de acción -->
            <div class="view-group full-width view-actions">
                <a href="../gestion_lideres.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
                
                <a href="editar_lider.php?id=<?php echo $id_lider_ver; ?>" class="edit-btn">
                    <i class="fas fa-edit"></i> Editar Líder
                </a>
            </div>
        </div>
        
        <div class="view-footer">
            <p><i class="fas fa-info-circle"></i> Esta vista muestra información de solo lectura del líder</p>
            <p><i class="fas fa-shield-alt"></i> Para modificar los datos, use la opción "Editar Líder"</p>
        </div>
    </div>
</body>
</html>