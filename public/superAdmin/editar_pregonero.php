<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../models/PregoneroModel.php';
require_once __DIR__ . '/../../models/BarrioModel.php';
require_once __DIR__ . '/../../models/PuestoVotacionModel.php';
require_once __DIR__ . '/../../models/SectorModel.php';
require_once __DIR__ . '/../../models/ZonaModel.php';
require_once __DIR__ . '/../../helpers/navigation_helper.php';

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

// Guardar esta URL también
NavigationHelper::pushUrl();

// Verificar si el usuario está logueado y es SuperAdmin
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    header('Location: ../index.php');
    exit();
}

// Verificar que se haya proporcionado un ID de pregonero
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: data_pregoneros.php?error=pregonero_no_encontrado');
    exit();
}

$id_pregonero = intval($_GET['id']);

$pdo = Database::getConnection();
$usuarioModel = new UsuarioModel($pdo);
$pregoneroModel = new PregoneroModel($pdo);
$barrioModel = new BarrioModel($pdo);
$puestoModel = new PuestoVotacionModel($pdo);

// Obtener datos del usuario logueado
$usuario_logueado = $usuarioModel->getUsuarioById($_SESSION['id_usuario']);

// Obtener datos completos del pregonero
$pregonero = $pregoneroModel->getById($id_pregonero);

if (!$pregonero) {
    header('Location: data_pregoneros.php?error=pregonero_no_encontrado');
    exit();
}

// Obtener listas para los combos
$barrios = $barrioModel->getAll();
$puestos = $puestoModel->getAll();
$referenciadores = $pregoneroModel->getReferenciadores();

// Obtener información del referenciador asignado
$referenciador_asignado = null;
if (!empty($pregonero['id_referenciador'])) {
    $referenciador_asignado = $usuarioModel->getUsuarioById($pregonero['id_referenciador']);
}

// Función para obtener valor o cadena vacía
function getValue($value) {
    return htmlspecialchars($value ?? '');
}

// Función para verificar si un option debe estar seleccionado
function isSelected($value1, $value2) {
    return $value1 == $value2 ? 'selected' : '';
}

// Función para verificar si un checkbox debe estar marcado
function isChecked($value) {
    return $value ? 'checked' : '';
}
// Función para formatear fecha
function formatDate($date) {
    if (empty($date) || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
        return '<span class="na-text">N/A</span>';
    }
    return date('d/m/Y H:i', strtotime($date));
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Pregonero - SGP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-dark: #1a1a2e;
            --secondary-dark: #16213e;
            --accent-blue: #0f3460;
            --highlight-blue: #4fc3f7;
            --text-light: #e6e6e6;
            --text-muted: #b0bec5;
            --success-color: #27ae60;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --card-bg: rgba(30, 30, 40, 0.85);
            --card-border: rgba(255, 255, 255, 0.15);
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
                linear-gradient(rgba(0, 0, 0, 0.85), rgba(0, 0, 0, 0.85)),
                url('/imagenes/fondo.jpg') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            flex-direction: column;
        }
        
        .main-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(26, 26, 46, 0.95);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            padding: 15px 0;
            border-bottom: 1px solid rgba(79, 195, 247, 0.2);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
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
            background: linear-gradient(135deg, #4fc3f7, #29b6f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(79, 195, 247, 0.1);
            padding: 8px 15px;
            border-radius: 20px;
            border: 1px solid rgba(79, 195, 247, 0.2);
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
            background: rgba(79, 195, 247, 0.1);
            border-radius: 6px;
            transition: all 0.3s;
            font-size: 0.9rem;
            border: 1px solid rgba(79, 195, 247, 0.2);
        }
        
        .header-btn:hover {
            background: rgba(79, 195, 247, 0.2);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(79, 195, 247, 0.2);
            border-color: rgba(79, 195, 247, 0.4);
        }
        
        .main-container {
            flex: 1;
            max-width: 1400px;
            margin: 80px auto 40px;
            padding: 0 20px;
            width: 100%;
        }
        
        .card-container {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 20px;
            border: 1px solid var(--card-border);
            box-shadow: 
                0 20px 50px rgba(0, 0, 0, 0.5),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .card-header {
            background: linear-gradient(135deg, rgba(79, 195, 247, 0.15), rgba(41, 182, 246, 0.1));
            border-bottom: 1px solid rgba(79, 195, 247, 0.3);
            padding: 25px 30px;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .header-left h2 {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: white;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .header-left h2 i {
            color: #4fc3f7;
        }
        
        .header-left p {
            color: var(--text-muted);
            font-size: 0.95rem;
            margin-bottom: 0;
        }
        
        .form-sections {
            padding: 30px;
        }
        
        .section-title {
            color: #4fc3f7;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid rgba(79, 195, 247, 0.3);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title i {
            color: #4fc3f7;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 35px;
        }
        
        .form-group {
            margin-bottom: 0;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-muted);
            font-weight: 500;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-label i {
            color: #90a4ae;
            font-size: 0.9rem;
            width: 20px;
        }
        
        .form-control, .form-select {
            width: 100%;
            padding: 14px 16px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            font-size: 0.95rem;
            color: var(--text-light);
            transition: all 0.3s;
            backdrop-filter: blur(5px);
        }
        
        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: rgba(79, 195, 247, 0.5);
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 3px rgba(79, 195, 247, 0.1);
        }
        
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }
        
        .form-select option {
            background: var(--primary-dark);
            color: var(--text-light);
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-with-icon .form-control,
        .input-with-icon .form-select {
            padding-left: 45px;
        }
        
        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #90a4ae;
            z-index: 1;
            font-size: 1rem;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 12px 16px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #4fc3f7;
        }
        
        .checkbox-group label {
            color: var(--text-light);
            font-weight: 500;
            cursor: pointer;
            font-size: 0.95rem;
        }
        
        .readonly-field {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            padding: 14px 16px;
            border-radius: 10px;
            color: var(--text-muted);
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .readonly-field i {
            color: #4fc3f7;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 40px;
            padding-top: 25px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .save-btn {
            background: linear-gradient(135deg, rgba(79, 195, 247, 0.2), rgba(41, 182, 246, 0.1));
            color: #4fc3f7;
            border: 1px solid rgba(79, 195, 247, 0.3);
            padding: 14px 35px;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            backdrop-filter: blur(10px);
        }
        
        .save-btn:hover {
            background: linear-gradient(135deg, rgba(79, 195, 247, 0.3), rgba(41, 182, 246, 0.2));
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(79, 195, 247, 0.2);
            color: #4fc3f7;
            border-color: rgba(79, 195, 247, 0.5);
        }
        
        .back-btn {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-light);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 14px 35px;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            backdrop-filter: blur(10px);
        }
        
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.08);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            border-color: rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .error-message {
            color: var(--danger-color);
            font-size: 0.8rem;
            margin-top: 5px;
            display: none;
        }
        
        .error-message.show {
            display: block;
        }
        
        .form-control.error, .form-select.error {
            border-color: var(--danger-color);
            background: rgba(231, 76, 60, 0.1);
        }
        
        /* Notificaciones */
        .notification-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 350px;
        }
        
        .notification {
            background: var(--card-bg);
            backdrop-filter: blur(15px);
            border-radius: 12px;
            padding: 16px 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            gap: 15px;
            transform: translateX(400px);
            opacity: 0;
            animation: slideIn 0.3s ease forwards;
            border-left: 4px solid;
            border: 1px solid var(--card-border);
        }
        
        .notification.success {
            border-left-color: #27ae60;
        }
        
        .notification.error {
            border-left-color: #e74c3c;
        }
        
        .notification.warning {
            border-left-color: #f39c12;
        }
        
        .notification.info {
            border-left-color: #4fc3f7;
        }
        
        .notification-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .notification.success .notification-icon {
            background: rgba(39, 174, 96, 0.2);
            color: #27ae60;
        }
        
        .notification.error .notification-icon {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
        }
        
        .notification.warning .notification-icon {
            background: rgba(243, 156, 18, 0.2);
            color: #f39c12;
        }
        
        .notification.info .notification-icon {
            background: rgba(79, 195, 247, 0.2);
            color: #4fc3f7;
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-title {
            font-weight: 700;
            font-size: 0.95rem;
            margin-bottom: 4px;
            color: white;
        }
        
        .notification-message {
            font-size: 0.85rem;
            color: var(--text-muted);
            line-height: 1.4;
        }
        
        .notification-close {
            color: var(--text-muted);
            cursor: pointer;
            font-size: 1rem;
            transition: color 0.2s;
            padding: 5px;
        }
        
        .notification-close:hover {
            color: white;
        }
        
        @keyframes slideIn {
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOut {
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
        
        .notification.fade-out {
            animation: slideOut 0.3s ease forwards;
        }
        
        /* Loading overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }
        
        .loading-overlay.show {
            opacity: 1;
            visibility: visible;
        }
        
        .loading-spinner {
            background: var(--card-bg);
            backdrop-filter: blur(15px);
            padding: 30px;
            border-radius: 20px;
            text-align: center;
            border: 1px solid var(--card-border);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }
        
        .loading-spinner i {
            font-size: 3rem;
            color: #4fc3f7;
            margin-bottom: 15px;
        }
        
        .loading-spinner p {
            color: white;
            font-weight: 600;
            margin: 0;
        }
        
        /* Confirm dialog */
        .confirm-dialog {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.9);
            background: var(--card-bg);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
            z-index: 10001;
            max-width: 400px;
            width: 90%;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
            border: 1px solid var(--card-border);
        }
        
        .confirm-dialog.show {
            opacity: 1;
            visibility: visible;
            transform: translate(-50%, -50%) scale(1);
        }
        
        .confirm-dialog h4 {
            color: white;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.2rem;
        }
        
        .confirm-dialog p {
            color: var(--text-muted);
            margin-bottom: 25px;
            line-height: 1.5;
        }
        
        .confirm-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .confirm-btn {
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            font-size: 0.95rem;
        }
        
        .confirm-btn.cancel {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-muted);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .confirm-btn.cancel:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .confirm-btn.confirm {
            background: linear-gradient(135deg, rgba(79, 195, 247, 0.2), rgba(41, 182, 246, 0.1));
            color: #4fc3f7;
            border: 1px solid rgba(79, 195, 247, 0.3);
        }
        
        .confirm-btn.confirm:hover {
            background: linear-gradient(135deg, rgba(79, 195, 247, 0.3), rgba(41, 182, 246, 0.2));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(79, 195, 247, 0.2);
        }
        
        @media (max-width: 768px) {
            .main-container {
                padding: 0 15px;
                margin-top: 90px;
                margin-bottom: 20px;
            }
            
            .form-sections {
                padding: 20px;
            }
            
            .header-content {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .save-btn, .back-btn {
                width: 100%;
                padding: 12px;
            }
        }
        
        @media (max-width: 480px) {
            .card-header {
                padding: 20px;
            }
            
            .header-left h2 {
                font-size: 1.5rem;
            }
            
            .header-actions {
                width: 100%;
                justify-content: space-between;
            }
            
            .section-title {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Contenedor para notificaciones -->
    <div class="notification-container" id="notificationContainer"></div>
    
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Guardando cambios...</p>
        </div>
    </div>
    
    <!-- Header -->
    <header class="main-header">
        <div class="header-container">
            <div class="header-top">
                <div class="header-title">
                    <h1><i class="fas fa-edit"></i> Editar Pregonero</h1>
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span>Super Admin: <?php echo htmlspecialchars($usuario_logueado['nombres'] . ' ' . $usuario_logueado['apellidos']); ?></span>
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

    <!-- Main Content -->
    <div class="main-container">
        <div class="card-container">
            <!-- Card Header -->
            <div class="card-header">
                <div class="header-content">
                    <div class="header-left">
                        <h2><i class="fas fa-bullhorn"></i> Editar Pregonero</h2>
                        <p>Modifique la información del pregonero según sea necesario</p>
                    </div>
                </div>
            </div>
            
            <!-- Form Sections -->
            <form id="editar-pregonero-form" method="POST" action="ajax/actualizar_pregonero.php">
                <input type="hidden" name="id_pregonero" value="<?php echo $id_pregonero; ?>">
                
                <div class="form-sections">
                    <!-- Sección 1: Información Personal -->
                    <div class="section-title">
                        <i class="fas fa-id-card"></i> Información Personal
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="nombres">
                                <i class="fas fa-user"></i> Nombres
                            </label>
                            <div class="input-with-icon">
                                <i class="fas fa-user input-icon"></i>
                                <input type="text" 
                                       id="nombres" 
                                       name="nombres" 
                                       class="form-control" 
                                       value="<?php echo getValue($pregonero['nombres']); ?>"
                                       placeholder="Ingrese los nombres"
                                       required>
                            </div>
                            <div class="error-message" id="error-nombres">Este campo es obligatorio</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="apellidos">
                                <i class="fas fa-user"></i> Apellidos
                            </label>
                            <div class="input-with-icon">
                                <i class="fas fa-user input-icon"></i>
                                <input type="text" 
                                       id="apellidos" 
                                       name="apellidos" 
                                       class="form-control" 
                                       value="<?php echo getValue($pregonero['apellidos']); ?>"
                                       placeholder="Ingrese los apellidos"
                                       required>
                            </div>
                            <div class="error-message" id="error-apellidos">Este campo es obligatorio</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="identificacion">
                                <i class="fas fa-id-card"></i> Identificación
                            </label>
                            <div class="input-with-icon">
                                <i class="fas fa-id-card input-icon"></i>
                                <input type="text" 
                                       id="identificacion" 
                                       name="identificacion" 
                                       class="form-control" 
                                       value="<?php echo getValue($pregonero['identificacion']); ?>"
                                       placeholder="Ingrese el número de identificación"
                                       required
                                       maxlength="10"
                                       pattern="\d{6,10}"
                                       title="Ingrese un número de identificación válido (solo números)"
                                       data-tooltip="Número de cédula sin puntos ni espacios">
                            </div>
                            <div class="error-message" id="error-identificacion">Este campo es obligatorio y debe contener solo números (6-10 dígitos)</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="telefono">
                                <i class="fas fa-phone"></i> Teléfono
                            </label>
                            <div class="input-with-icon">
                                <i class="fas fa-phone input-icon"></i>
                                <input type="tel" 
                                       id="telefono" 
                                       name="telefono" 
                                       class="form-control" 
                                       value="<?php echo getValue($pregonero['telefono']); ?>"
                                       placeholder="Ingrese el número de teléfono"
                                       required
                                       pattern="[0-9]{7,10}"
                                       title="Ingrese un número de teléfono válido"
                                       maxlength="10"
                                       data-tooltip="Número de contacto sin indicativo">
                            </div>
                            <div class="error-message" id="error-telefono">Este campo es obligatorio y debe contener solo números (7-10 dígitos)</div>
                        </div>
                    </div>
                    
                    <!-- Sección 2: Quien Reporta -->
                    <div class="section-title">
                        <i class="fas fa-user-check"></i> Información de Reporte
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label class="form-label" for="quien_reporta">
                                <i class="fas fa-user-check"></i> ¿Quién reporta?
                            </label>
                            <div class="input-with-icon">
                                <i class="fas fa-user-check input-icon"></i>
                                <input type="text" 
                                       id="quien_reporta" 
                                       name="quien_reporta" 
                                       class="form-control" 
                                       value="<?php echo getValue($pregonero['quien_reporta']); ?>"
                                       placeholder="Nombre de quien reporta (opcional)"
                                       data-tooltip="Persona que reporta al pregonero (puede ser el mismo u otra persona)">
                            </div>
                            
                            <!-- Checkbox para indicar que es el mismo pregonero -->
                            <div class="checkbox-group">
                                <input type="checkbox" 
                                       id="mismo_pregonero" 
                                       name="mismo_pregonero"
                                       <?php 
                                       $nombreCompleto = trim(($pregonero['nombres'] ?? '') . ' ' . ($pregonero['apellidos'] ?? ''));
                                       $quienReporta = trim($pregonero['quien_reporta'] ?? '');
                                       if (!empty($quienReporta) && strtolower($quienReporta) === strtolower($nombreCompleto)) {
                                           echo 'checked';
                                       }
                                       ?>>
                                <label for="mismo_pregonero">El reportante es el mismo pregonero</label>
                            </div>
                            <div class="error-message" id="error-quien_reporta"></div>
                        </div>
                    </div>
                    
                    <!-- Sección 3: Información de Ubicación -->
                    <div class="section-title">
                        <i class="fas fa-map-marker-alt"></i> Información de Ubicación
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="barrio">
                                <i class="fas fa-map"></i> Barrio
                            </label>
                            <div class="input-with-icon">
                                <i class="fas fa-map input-icon"></i>
                                <select id="barrio" name="barrio" class="form-select" required>
                                    <option value="">Seleccione un barrio</option>
                                    <?php foreach ($barrios as $barrio): ?>
                                    <option value="<?php echo $barrio['id_barrio']; ?>" 
                                        <?php echo isSelected($barrio['id_barrio'], $pregonero['id_barrio'] ?? ''); ?>>
                                        <?php echo htmlspecialchars($barrio['nombre']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="error-message" id="error-barrio">Debe seleccionar un barrio</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="corregimiento">
                                <i class="fas fa-trees"></i> Corregimiento
                            </label>
                            <div class="input-with-icon">
                                <i class="fas fa-trees input-icon"></i>
                                <input type="text" 
                                       id="corregimiento" 
                                       name="corregimiento" 
                                       class="form-control" 
                                       value="<?php echo getValue($pregonero['corregimiento']); ?>"
                                       placeholder="Ingrese el corregimiento"
                                       required>
                            </div>
                            <div class="error-message" id="error-corregimiento">Este campo es obligatorio</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="comuna">
                                <i class="fas fa-city"></i> Comuna
                            </label>
                            <div class="input-with-icon">
                                <i class="fas fa-city input-icon"></i>
                                <input type="text" 
                                       id="comuna" 
                                       name="comuna" 
                                       class="form-control" 
                                       value="<?php echo getValue($pregonero['comuna']); ?>"
                                       placeholder="Ingrese la comuna"
                                       required>
                            </div>
                            <div class="error-message" id="error-comuna">Este campo es obligatorio</div>
                        </div>
                    </div>
                    
                    <!-- Sección 4: Información de Votación -->
                    <div class="section-title">
                        <i class="fas fa-vote-yea"></i> Información de Votación
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="puesto">
                                <i class="fas fa-vote-yea"></i> Puesto de votación
                            </label>
                            <div class="input-with-icon">
                                <i class="fas fa-vote-yea input-icon"></i>
                                <select id="puesto" name="puesto" class="form-select" required>
                                    <option value="">Seleccione un puesto</option>
                                    <?php foreach ($puestos as $puesto): ?>
                                    <option value="<?php echo $puesto['id_puesto']; ?>"
                                        <?php echo isSelected($puesto['id_puesto'], $pregonero['id_puesto'] ?? ''); ?>>
                                        <?php 
                                        echo htmlspecialchars(
                                            ($puesto['zona_nombre'] ?? '') . ' - ' . 
                                            ($puesto['sector_nombre'] ?? '') . ' - ' . 
                                            $puesto['nombre']
                                        ); 
                                        ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="error-message" id="error-puesto">Debe seleccionar un puesto</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="mesa">
                                <i class="fas fa-table"></i> Mesa
                            </label>
                            <div class="input-with-icon">
                                <i class="fas fa-table input-icon"></i>
                                <input type="number" 
                                       id="mesa" 
                                       name="mesa" 
                                       class="form-control" 
                                       value="<?php echo getValue($pregonero['mesa']); ?>"
                                       placeholder="Número de mesa"
                                       min="1"
                                       max="60"
                                       required
                                       data-tooltip="Mesa de votación (1-60)">
                            </div>
                            <div class="error-message" id="error-mesa">Este campo es obligatorio (1-60)</div>
                        </div>
                    </div>
                    
                    <!-- Sección 5: Información de Registro -->
                    <div class="section-title">
                        <i class="fas fa-user-tie"></i> Información de Registro
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="id_referenciador">
                                <i class="fas fa-user-tie"></i> Referenciador asignado
                            </label>
                            <div class="input-with-icon">
                                <i class="fas fa-user-tie input-icon"></i>
                                <select id="id_referenciador" name="id_referenciador" class="form-select" required>
                                    <option value="">Seleccione un referenciador</option>
                                    <?php if (!empty($referenciadores)): ?>
                                        <?php foreach ($referenciadores as $ref): ?>
                                        <option value="<?php echo $ref['id_usuario']; ?>"
                                            <?php echo isSelected($ref['id_usuario'], $pregonero['id_referenciador'] ?? ''); ?>>
                                            <?php echo htmlspecialchars($ref['nombres'] . ' ' . $ref['apellidos'] . ' - ' . $ref['cedula']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="error-message" id="error-id_referenciador">Debe seleccionar un referenciador</div>
                        </div>
                        
                        <!-- Campos de solo lectura -->
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-calendar-alt"></i> Fecha de registro
                            </label>
                            <div class="readonly-field">
                                <i class="fas fa-calendar-alt"></i>
                                <?php echo formatDate($pregonero['fecha_registro'] ?? ''); ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-user-tie"></i> Registrado por
                            </label>
                            <div class="readonly-field">
                                <i class="fas fa-user-tie"></i>
                                <?php echo htmlspecialchars($pregonero['usuario_registro_nombre'] ?? 'N/A'); ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Botones de Acción -->
                    <div class="form-actions">
                        <?php
                        // Obtener la URL anterior del historial
                        $return_url = NavigationHelper::getPreviousUrl();
                        
                        // Validar seguridad
                        $dominio_actual = $_SERVER['HTTP_HOST'];
                        if (strpos($return_url, $dominio_actual) === false) {
                            $return_url = 'data_pregoneros.php';
                        }
                        ?>
                        
                        <a href="<?php echo htmlspecialchars($return_url); ?>" class="back-btn">
                            <i class="fas fa-arrow-left"></i> Cancelar
                        </a>
                        
                        <button type="submit" class="save-btn" id="submit-btn">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
    // Variable para controlar el timeout de escritura
    let typingTimer;
    const typingDelay = 500;

    // Sistema de notificaciones modernas
    function showNotification(type, message, title = '') {
        const container = document.getElementById('notificationContainer');
        
        if (!container) {
            console.error('Contenedor de notificaciones no encontrado');
            alert(`${type.toUpperCase()}: ${message}`);
            return;
        }
        
        const icons = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        
        const titles = {
            success: '¡Éxito!',
            error: '¡Error!',
            warning: '¡Advertencia!',
            info: 'Información'
        };
        
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <div class="notification-icon">
                <i class="fas fa-${icons[type]}"></i>
            </div>
            <div class="notification-content">
                <div class="notification-title">${title || titles[type]}</div>
                <div class="notification-message">${message}</div>
            </div>
            <div class="notification-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </div>
        `;
        
        container.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.classList.add('fade-out');
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 300);
            }
        }, 5000);
    }

    // Loading overlay
    function showLoading(show = true) {
        const overlay = document.getElementById('loadingOverlay');
        
        if (!overlay) {
            console.error('Loading overlay no encontrado');
            return;
        }
        
        if (show) {
            overlay.classList.add('show');
        } else {
            overlay.classList.remove('show');
        }
    }

    // Función para mostrar modal de confirmación
    function showConfirm(options) {
        return new Promise((resolve) => {
            const existingDialog = document.querySelector('.confirm-dialog');
            if (existingDialog) {
                existingDialog.remove();
            }
            
            const dialog = document.createElement('div');
            dialog.className = 'confirm-dialog';
            dialog.innerHTML = `
                <h4><i class="fas fa-${options.icon || 'question-circle'}"></i> ${options.title || 'Confirmar'}</h4>
                <p>${options.message}</p>
                <div class="confirm-actions">
                    <button class="confirm-btn cancel" id="confirmCancel">${options.cancelText || 'Cancelar'}</button>
                    <button class="confirm-btn confirm" id="confirmOk">${options.confirmText || 'Aceptar'}</button>
                </div>
            `;
            
            document.body.appendChild(dialog);
            setTimeout(() => dialog.classList.add('show'), 10);
            
            const cancelBtn = document.getElementById('confirmCancel');
            const okBtn = document.getElementById('confirmOk');
            
            if (cancelBtn) {
                cancelBtn.addEventListener('click', () => {
                    dialog.classList.remove('show');
                    setTimeout(() => {
                        if (dialog.parentNode) {
                            dialog.remove();
                        }
                    }, 300);
                    resolve(false);
                });
            }
            
            if (okBtn) {
                okBtn.addEventListener('click', () => {
                    dialog.classList.remove('show');
                    setTimeout(() => {
                        if (dialog.parentNode) {
                            dialog.remove();
                        }
                    }, 300);
                    resolve(true);
                });
            }
        });
    }

    // Función para formatear números
    function formatPhoneNumber(value) {
        return value.replace(/\D/g, '');
    }

    // Función para validar identificación
    function validateIdentificacion(value) {
        const clean = value.replace(/\D/g, '');
        return clean.length >= 6 && clean.length <= 10;
    }

    // Función para manejar el checkbox de "mismo pregonero"
    function setupMismoPregonero() {
        const mismoCheckbox = document.getElementById('mismo_pregonero');
        const quienReportaInput = document.getElementById('quien_reporta');
        const nombresInput = document.getElementById('nombres');
        const apellidosInput = document.getElementById('apellidos');
        
        if (!mismoCheckbox || !quienReportaInput || !nombresInput || !apellidosInput) return;
        
        // Solo configurar el evento si no estamos en carga inicial con checkbox marcado
        if (!mismoCheckbox.checked) {
            quienReportaInput.disabled = false;
        }
        
        mismoCheckbox.addEventListener('change', function() {
            if (this.checked) {
                // Si está marcado, copiar nombres + apellidos al campo quien_reporta
                const nombreCompleto = `${nombresInput.value.trim()} ${apellidosInput.value.trim()}`.trim();
                quienReportaInput.value = nombreCompleto;
                quienReportaInput.disabled = true;
                quienReportaInput.classList.add('readonly-field');
            } else {
                // Si se desmarca, habilitar el campo y limpiar
                quienReportaInput.disabled = false;
                quienReportaInput.classList.remove('readonly-field');
                quienReportaInput.value = '';
            }
        });
        
        // Actualizar el campo si se cambian nombres/apellidos mientras el checkbox está marcado
        [nombresInput, apellidosInput].forEach(input => {
            input.addEventListener('input', function() {
                if (mismoCheckbox.checked) {
                    const nombreCompleto = `${nombresInput.value.trim()} ${apellidosInput.value.trim()}`.trim();
                    quienReportaInput.value = nombreCompleto;
                }
            });
        });
    }

    // Validación del formulario y envío por AJAX
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM cargado, inicializando formulario de edición...');
        
        const form = document.getElementById('editar-pregonero-form');
        
        if (!form) {
            console.error('Formulario no encontrado');
            return;
        }
        
        // Setup del checkbox "mismo pregonero"
        setupMismoPregonero();
        
        // Validación de teléfono
        const telefonoInput = document.getElementById('telefono');
        if (telefonoInput) {
            telefonoInput.addEventListener('input', function() {
                this.value = formatPhoneNumber(this.value);
            });
        }
        
        // Validación de identificación
        const identificacionInput = document.getElementById('identificacion');
        if (identificacionInput) {
            identificacionInput.addEventListener('input', function() {
                const value = this.value.replace(/\D/g, ''); // Solo números
                
                // Mostrar mensaje de error si tiene menos de 6 dígitos
                if (value.length > 0 && value.length < 6) {
                    this.style.borderColor = '#e74c3c';
                    const errorDiv = document.getElementById('error-identificacion');
                    if (errorDiv) {
                        errorDiv.textContent = 'La identificación debe tener al menos 6 dígitos';
                        errorDiv.style.display = 'block';
                    }
                } else {
                    this.style.borderColor = '';
                    const errorDiv = document.getElementById('error-identificacion');
                    if (errorDiv) {
                        errorDiv.style.display = 'none';
                    }
                }
                
                // Actualizar el valor del input (solo números)
                this.value = value;
            });
        }
        
        // Validación al enviar el formulario
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            let isValid = true;
            const errores = [];
            
            // Validar cada campo obligatorio
            const campos = [
                { id: 'nombres', mensaje: 'error-nombres', texto: 'Nombres' },
                { id: 'apellidos', mensaje: 'error-apellidos', texto: 'Apellidos' },
                { id: 'identificacion', mensaje: 'error-identificacion', texto: 'Identificación' },
                { id: 'telefono', mensaje: 'error-telefono', texto: 'Teléfono' },
                { id: 'barrio', mensaje: 'error-barrio', texto: 'Barrio' },
                { id: 'corregimiento', mensaje: 'error-corregimiento', texto: 'Corregimiento' },
                { id: 'comuna', mensaje: 'error-comuna', texto: 'Comuna' },
                { id: 'puesto', mensaje: 'error-puesto', texto: 'Puesto' },
                { id: 'mesa', mensaje: 'error-mesa', texto: 'Mesa' },
                { id: 'id_referenciador', mensaje: 'error-id_referenciador', texto: 'Referenciador' }
            ];
            
            campos.forEach(campo => {
                const input = document.getElementById(campo.id);
                const errorDiv = document.getElementById(campo.mensaje);
                
                if (input && errorDiv) {
                    if (!input.value || input.value.trim() === '') {
                        input.classList.add('error');
                        errorDiv.classList.add('show');
                        errorDiv.textContent = `El campo ${campo.texto} es obligatorio`;
                        isValid = false;
                        errores.push(campo.texto);
                    } else {
                        input.classList.remove('error');
                        errorDiv.classList.remove('show');
                    }
                }
            });
            
            // Validación específica para mesa
            const mesaInput = document.getElementById('mesa');
            const mesaError = document.getElementById('error-mesa');
            if (mesaInput && mesaError && mesaInput.value) {
                const mesaNum = parseInt(mesaInput.value);
                if (isNaN(mesaNum) || mesaNum < 1 || mesaNum > 60) {
                    mesaInput.classList.add('error');
                    mesaError.textContent = 'La mesa debe ser un número entre 1 y 60';
                    mesaError.classList.add('show');
                    isValid = false;
                }
            }
            
            // Validar formato de identificación
            const idInput = document.getElementById('identificacion');
            const idError = document.getElementById('error-identificacion');
            
            if (idInput && idError) {
                if (!idInput.value || idInput.value.trim() === '') {
                    idInput.classList.add('error');
                    idError.textContent = 'El campo Identificación es obligatorio';
                    idError.classList.add('show');
                    isValid = false;
                    if (!errores.includes('Identificación')) errores.push('Identificación');
                } else if (!validateIdentificacion(idInput.value)) {
                    idInput.classList.add('error');
                    idError.textContent = 'La identificación debe tener 6-10 dígitos';
                    idError.classList.add('show');
                    isValid = false;
                }
            }
            
            if (!isValid) {
                showNotification('error', 
                    `Campos requeridos: ${errores.join(', ')}`, 
                    'Complete el formulario'
                );
                return;
            }
            
            // Mostrar confirmación antes de enviar
            const confirmed = await showConfirm({
                title: '¿Guardar cambios?',
                message: '¿Está seguro de que desea guardar los cambios realizados?',
                icon: 'edit',
                confirmText: 'Sí, guardar',
                cancelText: 'Cancelar'
            });
            
            if (!confirmed) return;
            
            // Mostrar loading
            showLoading(true);
            
            // Deshabilitar botón
            const submitBtn = document.getElementById('submit-btn');
            let originalText = '';
            if (submitBtn) {
                originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
                submitBtn.disabled = true;
            }
            
            // Recopilar datos del formulario
            const formData = new FormData(form);
            
            // Si el checkbox "mismo_pregonero" está marcado, aseguramos que quien_reporta tenga el valor correcto
            const mismoCheckbox = document.getElementById('mismo_pregonero');
            if (mismoCheckbox && mismoCheckbox.checked) {
                const nombres = document.getElementById('nombres').value.trim();
                const apellidos = document.getElementById('apellidos').value.trim();
                formData.set('quien_reporta', `${nombres} ${apellidos}`.trim());
            }
            
            try {
                const response = await fetch('../ajax/actualizar_pregonero.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification('success', data.message, '¡Actualización exitosa!');
                    
                    // Redirigir después de 1 segundo
                    setTimeout(() => {
                        const returnUrl = '<?php echo htmlspecialchars($return_url); ?>';
                        window.location.href = returnUrl;
                    }, 1000);
                } else {
                    showNotification('error', data.message, 'Error al guardar');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('error', 'Error de conexión con el servidor', 'Error de red');
            } finally {
                showLoading(false);
                if (submitBtn) {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            }
        });
        
        // Quitar error cuando el usuario comienza a escribir
        const inputs = document.querySelectorAll('.form-control, .form-select');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('error');
                const errorId = 'error-' + this.id;
                const errorDiv = document.getElementById(errorId);
                if (errorDiv) {
                    errorDiv.classList.remove('show');
                    if (this.id === 'identificacion') {
                        errorDiv.textContent = 'Este campo es obligatorio y debe contener solo números (6-10 dígitos)';
                    }
                    if (this.id === 'mesa') {
                        errorDiv.textContent = 'Este campo es obligatorio (1-60)';
                    }
                }
            });
            
            input.addEventListener('change', function() {
                this.classList.remove('error');
                const errorId = 'error-' + this.id;
                const errorDiv = document.getElementById(errorId);
                if (errorDiv) {
                    errorDiv.classList.remove('show');
                }
            });
        });
    });
    </script>
</body>
</html>