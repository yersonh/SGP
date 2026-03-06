<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../models/BarrioModel.php';
require_once __DIR__ . '/../models/SistemaModel.php';
require_once __DIR__ . '/../models/PuestoVotacionModel.php';
require_once __DIR__ . '/../models/PregoneroModel.php';

// Verificar si el usuario está logueado y es referenciador
if (!isset($_SESSION['id_usuario']) || 
    !in_array($_SESSION['tipo_usuario'], ['Referenciador', 'CarguePregoneros'])) {
    header('Location: index.php');
    exit();
}

$pdo = Database::getConnection();
$model = new UsuarioModel($pdo);
$id_usuario_logueado = $_SESSION['id_usuario'];

// Obtener información del sistema
$sistemaModel = new SistemaModel($pdo);
$pregoneroModel = new PregoneroModel($pdo);
$infoSistema = $sistemaModel->getInformacionSistema();

// Obtener datos del usuario logueado
$usuario_logueado = $model->getUsuarioById($id_usuario_logueado);

// Actualizar último registro
$fecha_actual = date('Y-m-d H:i:s');
$model->actualizarUltimoRegistro($id_usuario_logueado, $fecha_actual);

// Inicializar modelos
$barrioModel = new BarrioModel($pdo);
$puestoModel = new PuestoVotacionModel($pdo);

// Obtener datos para los combos
$barrios = $barrioModel->getAll();
$puestos = $puestoModel->getAll();

// Obtener lista de referenciadores (usuarios tipo 'Referenciador')
$referenciadores = $pregoneroModel->getReferenciadores();

// 6. Obtener información del sistema
$infoSistema = $sistemaModel->getInformacionSistema();

// 7. Formatear fecha para mostrar
$fecha_formateada = date('d/m/Y H:i:s', strtotime($fecha_actual));

// 8. Obtener información completa de la licencia
$licenciaInfo = $sistemaModel->getInfoCompletaLicencia();

// Extraer valores
$infoSistema = $licenciaInfo['info'];
$diasRestantes = $licenciaInfo['dias_restantes'];
$validaHastaFormatted = $licenciaInfo['valida_hasta_formatted'];
$fechaInstalacionFormatted = $licenciaInfo['fecha_instalacion_formatted'];

// PARA LA BARRA QUE DISMINUYE: Calcular porcentaje RESTANTE
$porcentajeRestante = $sistemaModel->getPorcentajeRestanteLicencia();

// Color de la barra basado en lo que RESTA
if ($porcentajeRestante > 50) {
    $barColor = 'bg-success';
} elseif ($porcentajeRestante > 25) {
    $barColor = 'bg-warning';
} else {
    $barColor = 'bg-danger';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Pregoneros - SGP</title>
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
        
        /* Header Styles */
        .main-header {
            background: linear-gradient(135deg, var(--primary-color), #1a252f);
            color: white;
            padding: 15px 0;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-container {
            display: flex;
            flex-direction: column;
            max-width: 1200px;
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
        
        /* Main Container */
        .main-container {
            max-width: 1200px;
            margin: 0 auto 30px;
            padding: 0 15px;
        }
        
        /* Form Card */
        .form-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }
        
        .form-header {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f5f9;
        }
        
        .form-header h2 {
            color: var(--primary-color);
            font-size: 1.5rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-header h2 i {
            color: var(--secondary-color);
        }
        
        /* Form Grid */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }
        
        @media (min-width: 768px) {
            .form-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .full-width {
                grid-column: 1 / -1;
            }
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            color: var(--primary-color);
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .form-control, .form-select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s;
            background-color: #f9f9f9;
        }
        
        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: var(--secondary-color);
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        
        /* Input with Icon */
        .input-with-icon {
            position: relative;
        }
        
        .input-with-icon .form-control, 
        .input-with-icon .form-select {
            padding-left: 40px;
        }
        
        .input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            z-index: 1;
        }
        
        /* Submit Button */
        .submit-btn {
            background: linear-gradient(135deg, var(--success-color), #219653);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            margin-top: 10px;
        }
        
        .submit-btn:hover {
            background: linear-gradient(135deg, #219653, #1e8449);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }
        
        .submit-btn:active {
            transform: translateY(0);
        }
        
        /* Footer */
        .system-footer {
            text-align: center;
            padding: 20px 0;
            background: white;
            color: black;
            font-size: 0.85rem;
            line-height: 1.5;
            margin-top: 30px;
            border-top: 2px solid #eaeaea;
        }
        
        .system-footer p {
            margin: 5px 0;
        }
        
        .system-footer strong {
            color: #000000;
            font-weight: 600;
        }
        
        /* Responsive */
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
            
            .form-card {
                padding: 15px;
            }
            
            .form-header h2 {
                font-size: 1.3rem;
            }
        }
        
        /* Estilos para el logo en el footer */
        .container.text-center.mb-3 img {
            max-width: 320px;
            height: auto;
            transition: max-width 0.3s ease;
            cursor: pointer;
        }
        
        @media (max-width: 768px) {
            .container.text-center.mb-3 img {
                max-width: 220px;
            }
        }
        
        @media (max-width: 400px) {
            .container.text-center.mb-3 img {
                max-width: 200px;
            }
        }
        
        /* Estilos para el modal de información del sistema */
        .modal-system-info .modal-header {
            background: linear-gradient(135deg, #2c3e50, #1a252f);
            color: white;
        }
        
        .modal-system-info .modal-body {
            padding: 20px;
        }
        
        .modal-logo-container {
            text-align: center;
            margin-bottom: 20px;
            padding: 15px;
        }
        
        .modal-logo {
            max-width: 300px;
            height: auto;
            margin: 0 auto;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
            border: 3px solid #fff;
            background: white;
        }
        
        .licencia-info {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }
        
        .licencia-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .licencia-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }
        
        .licencia-dias {
            font-size: 1rem;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 20px;
            background: #3498db;
            color: white;
        }
        
        .licencia-progress {
            height: 12px;
            border-radius: 6px;
            margin-bottom: 8px;
            background-color: #e9ecef;
            overflow: hidden;
        }
        
        .licencia-progress-bar {
            height: 100%;
            border-radius: 6px;
            transition: width 0.6s ease;
        }
        
        .licencia-fecha {
            font-size: 0.85rem;
            color: #6c757d;
            text-align: center;
            margin-top: 5px;
        }
        
        .feature-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            height: 100%;
            border-left: 4px solid #3498db;
            transition: transform 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .feature-icon {
            opacity: 0.8;
        }
        
        .feature-title {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .feature-text {
            font-size: 14px;
            color: #555;
            line-height: 1.5;
            margin-bottom: 0;
        }
        
        .feature-image-container {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .feature-img-header {
            width: 190px;
            height: 190px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #ffffff;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .feature-img-header:hover {
            transform: scale(1.05);
        }
        
        .logo-clickable {
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .logo-clickable:hover {
            transform: scale(1.05);
        }

        /* Estilo para campos requeridos */
        .form-label.required::after {
            content: " *";
            color: #e74c3c;
            font-weight: bold;
        }
        
        /* Estilo para campos no requeridos */
        .form-label.optional::after {
            content: " (opcional)";
            color: #6c757d;
            font-weight: normal;
            font-size: 0.8rem;
        }
        
        /* Estilo para mensajes de error */
        .error-message {
            color: #e74c3c;
            font-size: 0.8rem;
            margin-top: 5px;
            display: none;
        }
        
        .error-message.show {
            display: block;
        }
        
        .form-control.error, .form-select.error {
            border-color: #e74c3c;
            background-color: #fff5f5;
        }

        /* ========== NOTIFICACIONES MODERNAS ========== */
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
            background: white;
            border-radius: 12px;
            padding: 16px 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 15px;
            transform: translateX(400px);
            opacity: 0;
            animation: slideIn 0.3s ease forwards;
            border-left: 4px solid;
            backdrop-filter: blur(10px);
        }

        .notification.success {
            border-left-color: #10b981;
            background: rgba(239, 253, 244, 0.95);
        }

        .notification.error {
            border-left-color: #ef4444;
            background: rgba(254, 242, 242, 0.95);
        }

        .notification.warning {
            border-left-color: #f59e0b;
            background: rgba(255, 247, 237, 0.95);
        }

        .notification.info {
            border-left-color: #3b82f6;
            background: rgba(239, 246, 255, 0.95);
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
            background: #10b981;
            color: white;
        }

        .notification.error .notification-icon {
            background: #ef4444;
            color: white;
        }

        .notification.warning .notification-icon {
            background: #f59e0b;
            color: white;
        }

        .notification.info .notification-icon {
            background: #3b82f6;
            color: white;
        }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-weight: 700;
            font-size: 0.95rem;
            margin-bottom: 4px;
            color: #1e293b;
        }

        .notification-message {
            font-size: 0.85rem;
            color: #475569;
            line-height: 1.4;
        }

        .notification-close {
            color: #94a3b8;
            cursor: pointer;
            font-size: 1rem;
            transition: color 0.2s;
            padding: 5px;
        }

        .notification-close:hover {
            color: #475569;
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
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(3px);
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
            background: white;
            padding: 25px;
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .loading-spinner i {
            font-size: 3rem;
            color: #3498db;
            margin-bottom: 10px;
        }

        .loading-spinner p {
            color: #2c3e50;
            font-weight: 600;
            margin: 0;
        }

        /* Confirm dialog */
        .confirm-dialog {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.9);
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
            z-index: 10001;
            max-width: 400px;
            width: 90%;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }

        .confirm-dialog.show {
            opacity: 1;
            visibility: visible;
            transform: translate(-50%, -50%) scale(1);
        }

        .confirm-dialog h4 {
            color: #2c3e50;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .confirm-dialog p {
            color: #64748b;
            margin-bottom: 25px;
            line-height: 1.5;
        }

        .confirm-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .confirm-btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
        }

        .confirm-btn.cancel {
            background: #f1f5f9;
            color: #475569;
        }

        .confirm-btn.cancel:hover {
            background: #e2e8f0;
        }

        .confirm-btn.confirm {
            background: #ef4444;
            color: white;
        }

        .confirm-btn.confirm:hover {
            background: #dc2626;
        }

        /* Tooltip personalizado */
        .tooltip-modern {
            position: relative;
            cursor: help;
        }

        .tooltip-modern:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: #1e293b;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.75rem;
            white-space: nowrap;
            margin-bottom: 5px;
            z-index: 1000;
        }

        /* Efecto de éxito en el formulario */
        .success-glow {
            animation: glow 1s ease;
        }

        @keyframes glow {
            0% { box-shadow: 0 0 0 0 rgba(39, 174, 96, 0.7); }
            50% { box-shadow: 0 0 30px 10px rgba(39, 174, 96, 0.3); }
            100% { box-shadow: 0 0 0 0 rgba(39, 174, 96, 0); }
        }

        /* Estilos para verificación de identificación */
        #identificacion.verifying {
            background-image: url('data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'20\' height=\'20\' viewBox=\'0 0 50 50\'%3E%3Cpath fill=\'%233498db\' d=\'M25,5A20,20,0,0,1,45,25H40A15,15,0,0,0,25,10Z\'%3E%3CanimateTransform attributeName=\'transform\' type=\'rotate\' from=\'0 25 25\' to=\'360 25 25\' dur=\'1s\' repeatCount=\'indefinite\'/%3E%3C/path%3E%3C/svg%3E');
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 20px;
            padding-right: 40px !important;
        }

        /* Estilos para validación de identificación */
        #identificacion.success {
            border-color: #28a745 !important;
            background-color: #f0fff0 !important;
        }

        #identificacion.error {
            border-color: #dc3545 !important;
            background-color: #fff0f0 !important;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Estilo para el checkbox de mismo reportante */
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .checkbox-group label {
            color: var(--primary-color);
            font-weight: 500;
            cursor: pointer;
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
            <p>Guardando datos...</p>
        </div>
    </div>
    
    <!-- Header -->
    <header class="main-header">
        <div class="header-container">
            <div class="header-top">
                <div class="header-title">
                    <h1><i class="fas fa-bullhorn"></i> Registro de Pregoneros</h1>
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($usuario_logueado['nombres'] . ' ' . $usuario_logueado['apellidos']); ?></span>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Main Form -->
    <div class="main-container">
        <div class="form-card">
            <div class="form-header">
                <h2><i class="fas fa-user-plus"></i> Datos del Pregonero</h2>
            </div>
            
            <form id="registro-pregonero-form" novalidate>
                <div class="form-grid">
                    <!-- Nombres -->
                    <div class="form-group">
                        <label class="form-label required" for="nombres">
                            <i class="fas fa-user"></i> Nombres
                        </label>
                        <div class="input-with-icon">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" 
                                   id="nombres" 
                                   name="nombres" 
                                   class="form-control" 
                                   placeholder="Ingrese los nombres"
                                   required>
                        </div>
                        <div class="error-message" id="error-nombres">Este campo es obligatorio</div>
                    </div>
                    
                    <!-- Apellidos -->
                    <div class="form-group">
                        <label class="form-label required" for="apellidos">
                            <i class="fas fa-user"></i> Apellidos
                        </label>
                        <div class="input-with-icon">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" 
                                   id="apellidos" 
                                   name="apellidos" 
                                   class="form-control" 
                                   placeholder="Ingrese los apellidos"
                                   required>
                        </div>
                        <div class="error-message" id="error-apellidos">Este campo es obligatorio</div>
                    </div>
                    
                    <!-- Identificación -->
                    <div class="form-group">
                        <label class="form-label required" for="identificacion">
                            <i class="fas fa-id-card"></i> Identificación
                        </label>
                        <div class="input-with-icon">
                            <i class="fas fa-id-card input-icon"></i>
                            <input type="text" 
                                   id="identificacion" 
                                   name="identificacion" 
                                   class="form-control" 
                                   placeholder="Ingrese el número de identificación"
                                   required
                                   maxlength="10"
                                   pattern="\d{6,10}"
                                   title="Ingrese un número de identificación válido (solo números)"
                                   data-tooltip="Número de cédula sin puntos ni espacios">
                        </div>
                        <div class="error-message" id="error-identificacion" style="display: none;">Este campo es obligatorio y debe contener solo números (6-10 dígitos)</div>
                    </div>
                    
                    <!-- Teléfono -->
                    <div class="form-group">
                        <label class="form-label required" for="telefono">
                            <i class="fas fa-phone"></i> Teléfono
                        </label>
                        <div class="input-with-icon">
                            <i class="fas fa-phone input-icon"></i>
                            <input type="tel" 
                                   id="telefono" 
                                   name="telefono" 
                                   class="form-control" 
                                   placeholder="Ingrese el número de teléfono"
                                   required
                                   pattern="[0-9]{7,10}"
                                   title="Ingrese un número de teléfono válido"
                                   maxlength="10"
                                   data-tooltip="Número de contacto sin indicativo">
                        </div>
                        <div class="error-message" id="error-telefono">Este campo es obligatorio y debe contener solo números (7-10 dígitos)</div>
                    </div>
                    
                    <!-- Barrio -->
                    <div class="form-group">
                        <label class="form-label required" for="barrio">
                            <i class="fas fa-map-signs"></i> Barrio
                        </label>
                        <div class="input-with-icon">
                            <i class="fas fa-map-signs input-icon"></i>
                            <select id="barrio" name="barrio" class="form-select" required>
                                <option value="">Seleccione un barrio</option>
                                <?php foreach ($barrios as $barrio): ?>
                                <option value="<?php echo $barrio['id_barrio']; ?>">
                                    <?php echo htmlspecialchars($barrio['nombre']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="error-message" id="error-barrio">Debe seleccionar un barrio</div>
                    </div>
                    
                    <!-- Corregimiento -->
                    <div class="form-group">
                        <label class="form-label required" for="corregimiento">
                            <i class="fas fa-map"></i> Corregimiento
                        </label>
                        <div class="input-with-icon">
                            <i class="fas fa-map input-icon"></i>
                            <input type="text" 
                                   id="corregimiento" 
                                   name="corregimiento" 
                                   class="form-control" 
                                   placeholder="Ingrese el corregimiento"
                                   required>
                        </div>
                        <div class="error-message" id="error-corregimiento">Este campo es obligatorio</div>
                    </div>
                    
                    <!-- Comuna -->
                    <div class="form-group">
                        <label class="form-label required" for="comuna">
                            <i class="fas fa-city"></i> Comuna
                        </label>
                        <div class="input-with-icon">
                            <i class="fas fa-city input-icon"></i>
                            <input type="text" 
                                   id="comuna" 
                                   name="comuna" 
                                   class="form-control" 
                                   placeholder="Ingrese la comuna"
                                   required>
                        </div>
                        <div class="error-message" id="error-comuna">Este campo es obligatorio</div>
                    </div>
                    
                    <!-- Puesto -->
                    <div class="form-group">
                        <label class="form-label required" for="puesto">
                            <i class="fas fa-vote-yea"></i> Puesto
                        </label>
                        <div class="input-with-icon">
                            <i class="fas fa-vote-yea input-icon"></i>
                            <select id="puesto" name="puesto" class="form-select" required>
                                <option value="">Seleccione un puesto</option>
                                <?php foreach ($puestos as $puesto): ?>
                                <option value="<?php echo $puesto['id_puesto']; ?>">
                                    <?php 
                                    echo htmlspecialchars(
                                        $puesto['zona_nombre'] . ' - ' . 
                                        $puesto['sector_nombre'] . ' - ' . 
                                        $puesto['nombre']
                                    ); 
                                    ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="error-message" id="error-puesto">Debe seleccionar un puesto</div>
                    </div>
                    
                    <!-- Mesa -->
                    <div class="form-group">
                        <label class="form-label required" for="mesa">
                            <i class="fas fa-users"></i> Mesa
                        </label>
                        <div class="input-with-icon">
                            <i class="fas fa-users input-icon"></i>
                            <input type="number" 
                                   id="mesa" 
                                   name="mesa" 
                                   class="form-control" 
                                   placeholder="Número de mesa"
                                   min="1"
                                   max="60"
                                   required
                                   data-tooltip="Mesa de votación (1-60)">
                        </div>
                        <div class="error-message" id="error-mesa">Este campo es obligatorio (1-60)</div>
                    </div>
                    
                    <!-- ========== NUEVOS CAMPOS ========== -->
                    
                    <!-- Quien Reporta -->
                    <div class="form-group">
                        <label class="form-label" for="quien_reporta">
                            <i class="fas fa-user-check"></i> ¿Quién reporta?
                        </label>
                        <div class="input-with-icon">
                            <i class="fas fa-user-check input-icon"></i>
                            <input type="text" 
                                   id="quien_reporta" 
                                   name="quien_reporta" 
                                   class="form-control" 
                                   placeholder="Nombre de quien reporta (opcional)"
                                   data-tooltip="Persona que reporta al pregonero (puede ser el mismo u otra persona)">
                        </div>
                        
                        <!-- Checkbox para indicar que es el mismo pregonero -->
                        <div class="checkbox-group">
                            <input type="checkbox" id="mismo_pregonero" name="mismo_pregonero">
                            <label for="mismo_pregonero">El reportante es el mismo pregonero</label>
                        </div>
                        <div class="error-message" id="error-quien_reporta"></div>
                    </div>
                    
                    <!-- Referenciador -->
                    <div class="form-group">
                        <label class="form-label required" for="id_referenciador">
                            <i class="fas fa-user-tie"></i> Referenciador
                        </label>
                        <div class="input-with-icon">
                            <i class="fas fa-user-tie input-icon"></i>
                            <select id="id_referenciador" name="id_referenciador" class="form-select" required>
                                <option value="">Seleccione un referenciador</option>
                                <?php if (!empty($referenciadores)): ?>
                                    <?php foreach ($referenciadores as $ref): ?>
                                    <option value="<?php echo $ref['id_usuario']; ?>">
                                        <?php echo htmlspecialchars($ref['nombres'] . ' ' . $ref['apellidos'] . ' - ' . $ref['cedula']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled>No hay referenciadores disponibles</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="error-message" id="error-id_referenciador">Debe seleccionar un referenciador</div>
                    </div>
                    
                    <!-- Botón de Envío -->
                    <div class="form-group full-width">
                        <button type="submit" class="submit-btn" id="submit-btn">
                            <i class="fas fa-save"></i> Guardar Registro
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer del sistema -->
    <footer class="system-footer">
        <div class="container text-center mb-3">
            <img src="imagenes/Logo-artguru.png" 
                alt="Logo" 
                class="logo-clickable"
                onclick="mostrarModalSistema()"
                title="Haz clic para ver información del sistema">
        </div>

        <div class="container text-center">
            <p>
                <strong>© 2026 Sistema de Gestión Política SGP.</strong> Puerto Gaitán - Meta
                Módulo de SGA Sistema de Gestión Administrativa 2026 SGA Solución de Gestión Administrativa Enterprise Premium 1.0™ desarrollado por SISGONTech Technology®, Conjunto Residencial Portal del Llano, Casa 104, Villavicencio, Meta. - Asesores e-Governance Solutions para Entidades Públicas 2026® SISGONTech
                Propietario software: Yerson Solano Alfonso - ☎️ (+57) 313 333 62 27 - Email: soportesgp@gmail.com © Reservados todos los derechos de autor.
            </p>
        </div>
    </footer>
    
    <!-- Modal de Información del Sistema -->
    <div class="modal fade modal-system-info" id="modalSistema" tabindex="-1" aria-labelledby="modalSistemaLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalSistemaLabel">
                        <i class="fas fa-info-circle me-2"></i>Información del Sistema
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Logo centrado -->
                    <div class="modal-logo-container">
                        <img src="imagenes/Logo-artguru.png" alt="Logo del Sistema" class="modal-logo">
                    </div>
                    
                    <!-- Título del Sistema -->
                    <div class="licencia-info">
                        <div class="licencia-header">
                            <h6 class="licencia-title">Licencia Runtime</h6>
                            <span class="licencia-dias">
                                <?php echo $diasRestantes; ?> días restantes
                            </span>
                        </div>
                        
                        <div class="licencia-progress">
                            <div class="licencia-progress-bar <?php echo $barColor; ?>" 
                                style="width: <?php echo $porcentajeRestante; ?>%"
                                role="progressbar" 
                                aria-valuenow="<?php echo $porcentajeRestante; ?>" 
                                aria-valuemin="0" 
                                aria-valuemax="100">
                            </div>
                        </div>
                        
                        <div class="licencia-fecha">
                            <i class="fas fa-calendar-alt me-1"></i>
                            Instalado: <?php echo $fechaInstalacionFormatted; ?> | 
                            Válida hasta: <?php echo $validaHastaFormatted; ?>
                        </div>
                    </div>
                    <div class="feature-image-container">
                        <img src="imagenes/ingeniero2.png" alt="Logo de Herramienta" class="feature-img-header">
                        <div class="profile-info mt-3">
                            <h4 class="profile-name"><strong>Rubén Darío González García</strong></h4>
                            <small class="profile-description">
                                Ingeniero de Sistemas, administrador de bases de datos, desarrollador de objeto OLE.<br>
                                Magister en Administración Pública.<br>
                                <span class="cio-tag"><strong>CIO de equipo soporte SISGONTECH</strong></span>
                            </small>
                        </div>
                    </div>
                    <!-- Sección de Características -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div class="feature-card">
                                <div class="feature-icon text-primary mb-3">
                                    <i class="fas fa-bolt fa-2x"></i>
                                </div>
                                <h5 class="feature-title">Efectividad de la Herramienta</h5>
                                <h6 class="text-muted mb-2">Optimización de Tiempos</h6>
                                <p class="feature-text">
                                    Reducción del 70% en el procesamiento manual de datos y generación de reportes de adeptos.
                                </p>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="feature-card">
                                <div class="feature-icon text-success mb-3">
                                    <i class="fas fa-database fa-2x"></i>
                                </div>
                                <h5 class="feature-title">Integridad de Datos</h5>
                                <h6 class="text-muted mb-2">Validación Inteligente</h6>
                                <p class="feature-text">
                                    Validación en tiempo real para eliminar duplicados y errores de digitación en la base de datos política.
                                </p>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="feature-card">
                                <div class="feature-icon text-warning mb-3">
                                    <i class="fas fa-chart-line fa-2x"></i>
                                </div>
                                <h5 class="feature-title">Monitoreo de Metas</h5>
                                <h6 class="text-muted mb-2">Seguimiento Visual</h6>
                                <p class="feature-text">
                                    Seguimiento visual del cumplimiento de objetivos mediante barras de avance dinámicas.
                                </p>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="feature-card">
                                <div class="feature-icon text-danger mb-3">
                                    <i class="fas fa-shield-alt fa-2x"></i>
                                </div>
                                <h5 class="feature-title">Seguridad Avanzada</h5>
                                <h6 class="text-muted mb-2">Control Total</h6>
                                <p class="feature-text">
                                    Control de acceso jerarquizado y trazabilidad total de ingresos al sistema.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="https://sgp-sistema-de-gestion-politica.webnode.com.co/" 
                       target="_blank" 
                       class="btn btn-primary"
                       onclick="cerrarModalSistema();">
                        <i class="fas fa-external-link-alt me-1"></i> Uso SGP
                    </a>
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
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

    // Función para mostrar el modal del sistema
    function mostrarModalSistema() {
        const modalElement = document.getElementById('modalSistema');
        if (modalElement) {
            var modal = new bootstrap.Modal(modalElement);
            modal.show();
        }
    }

    function cerrarModalSistema() {
        const modalElement = document.getElementById('modalSistema');
        if (modalElement) {
            var modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) {
                modal.hide();
            }
        }
    }

    // Función para mostrar mensaje de validación (igual que el ejemplo)
    function showValidationMessage(mensaje, tipo) {
        // Eliminar mensaje anterior si existe
        const mensajeAnterior = document.getElementById('mensaje-verificacion');
        if (mensajeAnterior) {
            mensajeAnterior.remove();
        }
        
        // Crear elemento para el mensaje
        const mensajeDiv = document.createElement('div');
        mensajeDiv.id = 'mensaje-verificacion';
        mensajeDiv.style.marginTop = '5px';
        mensajeDiv.style.padding = '8px 12px';
        mensajeDiv.style.borderRadius = '4px';
        mensajeDiv.style.fontSize = '0.9rem';
        
        if (tipo === 'success') {
            mensajeDiv.style.backgroundColor = '#d4edda';
            mensajeDiv.style.color = '#155724';
            mensajeDiv.style.border = '1px solid #c3e6cb';
        } else if (tipo === 'error') {
            mensajeDiv.style.backgroundColor = '#f8d7da';
            mensajeDiv.style.color = '#721c24';
            mensajeDiv.style.border = '1px solid #f5c6cb';
        } else if (tipo === 'loading') {
            mensajeDiv.style.backgroundColor = '#e2e3e5';
            mensajeDiv.style.color = '#383d41';
            mensajeDiv.style.border = '1px solid #d6d8db';
            mensajeDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + mensaje;
        }
        
        if (tipo !== 'loading') {
            mensajeDiv.innerHTML = mensaje;
        }
        
        // Insertar el mensaje después del grupo del input
        const input = document.getElementById('identificacion');
        const formGroup = input.closest('.form-group');
        if (formGroup) {
            formGroup.appendChild(mensajeDiv);
        } else {
            input.parentNode.appendChild(mensajeDiv);
        }
    }

    // Función para verificar identificación en la base de datos
    function verificarIdentificacion(identificacion) {
        if (!identificacion || identificacion.length < 6) return;
        
        const input = document.getElementById('identificacion');
        
        // Mostrar estado de carga
        input.classList.add('verifying');
        showValidationMessage('Validando cédula...', 'loading');
        
        // Hacer petición AJAX al servidor
        fetch('ajax/verificar_identificacion_pregonero.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `identificacion=${encodeURIComponent(identificacion)}`
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la conexión');
            }
            return response.json();
        })
        .then(data => {
            // Quitar indicador de carga
            input.classList.remove('verifying');
            
            if (data.success) {
                if (data.exists) {
                    // Identificación YA EXISTE
                    input.classList.add('error');
                    input.classList.remove('success');
                    
                    // Construir mensaje con información adicional
                    let mensaje = 'Esta cédula ya está registrada en el sistema';
                    if (data.fecha_registro && data.usuario_registro) {
                        mensaje += `<br><small style="font-size: 0.85em; color: #666; display: block; margin-top: 3px;">
                            Fue ingresado el día ${data.fecha_registro} por el referenciador ${data.usuario_registro}
                        </small>`;
                    }
                    
                    showValidationMessage(mensaje, 'error');
                    
                } else {
                    // Identificación DISPONIBLE
                    input.classList.remove('error');
                    input.classList.add('success');
                    showValidationMessage('Cédula disponible', 'success');
                }
            } else {
                // Error en la validación
                input.classList.remove('error', 'success');
                showValidationMessage('❌ Error al validar la cédula', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            input.classList.remove('verifying', 'error', 'success');
            showValidationMessage('❌ Error de conexión al validar', 'error');
        });
    }

    // Función para manejar el checkbox de "mismo pregonero"
    function setupMismoPregonero() {
        const mismoCheckbox = document.getElementById('mismo_pregonero');
        const quienReportaInput = document.getElementById('quien_reporta');
        const nombresInput = document.getElementById('nombres');
        const apellidosInput = document.getElementById('apellidos');
        
        if (!mismoCheckbox || !quienReportaInput || !nombresInput || !apellidosInput) return;
        
        mismoCheckbox.addEventListener('change', function() {
            if (this.checked) {
                // Si está marcado, copiar nombres + apellidos al campo quien_reporta
                const nombreCompleto = `${nombresInput.value.trim()} ${apellidosInput.value.trim()}`.trim();
                quienReportaInput.value = nombreCompleto;
                quienReportaInput.disabled = true;
                quienReportaInput.classList.add('bg-light');
            } else {
                // Si se desmarca, habilitar el campo y limpiar
                quienReportaInput.disabled = false;
                quienReportaInput.classList.remove('bg-light');
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
        console.log('DOM cargado, inicializando formulario...');
        
        const form = document.getElementById('registro-pregonero-form');
        
        if (!form) {
            console.error('Formulario no encontrado');
            return;
        }
        
        // Aplicar clase tooltip-modern a campos con data-tooltip
        document.querySelectorAll('[data-tooltip]').forEach(el => {
            el.classList.add('tooltip-modern');
        });
        
        // Setup del checkbox "mismo pregonero"
        setupMismoPregonero();
        
        // Validación de teléfono
        const telefonoInput = document.getElementById('telefono');
        if (telefonoInput) {
            telefonoInput.addEventListener('input', function() {
                this.value = formatPhoneNumber(this.value);
            });
        }
        
        // Validación de identificación y verificación en tiempo real
        const identificacionInput = document.getElementById('identificacion');
        if (identificacionInput) {
            identificacionInput.addEventListener('input', function() {
                clearTimeout(typingTimer);
                
                const value = this.value.replace(/\D/g, ''); // Solo números
                
                // Quitar clases de validación
                this.classList.remove('success', 'error');
                
                // Eliminar mensaje de validación mientras escribe
                const mensajeAnterior = document.getElementById('mensaje-verificacion');
                if (mensajeAnterior) {
                    mensajeAnterior.remove();
                }
                
                // Mostrar mensaje de error si tiene menos de 6 dígitos
                if (value.length > 0 && value.length < 6) {
                    this.style.borderColor = '#e74c3c';
                    this.style.backgroundColor = '#fff5f5';
                    
                    const errorDiv = document.getElementById('error-identificacion');
                    if (errorDiv) {
                        errorDiv.textContent = 'La identificación debe tener al menos 6 dígitos';
                        errorDiv.style.display = 'block';
                    }
                } else {
                    // Restaurar estilos por defecto
                    this.style.borderColor = '#e0e0e0';
                    this.style.backgroundColor = '#f9f9f9';
                    
                    const errorDiv = document.getElementById('error-identificacion');
                    if (errorDiv) {
                        errorDiv.style.display = 'none';
                    }
                }
                
                // Actualizar el valor del input (solo números)
                this.value = value;
                
                // Verificar si tiene 6 dígitos o más
                if (value.length >= 6) {
                    typingTimer = setTimeout(() => {
                        verificarIdentificacion(value);
                    }, typingDelay);
                }
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
                if (typeof showNotification === 'function') {
                    showNotification('error', 
                        `Campos requeridos: ${errores.join(', ')}`, 
                        'Complete el formulario'
                    );
                } else {
                    alert('❌ Complete todos los campos obligatorios');
                }
                return;
            }
            
            // Mostrar confirmación antes de enviar
            let confirmed = true;
            if (typeof showConfirm === 'function') {
                confirmed = await showConfirm({
                    title: '¿Guardar registro?',
                    message: 'Verifique que los datos sean correctos antes de continuar.',
                    icon: 'clipboard-check',
                    confirmText: 'Sí, guardar',
                    cancelText: 'Revisar'
                });
            } else {
                confirmed = confirm('¿Guardar registro?');
            }
            
            if (!confirmed) return;
            
            // Mostrar loading
            if (typeof showLoading === 'function') {
                showLoading(true);
            }
            
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
                const response = await fetch('ajax/guardar_pregonero.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    if (typeof showNotification === 'function') {
                        showNotification('success', 
                            `${data.message}\nID de registro: ${data.id_pregonero}`,
                            '¡Registro exitoso!'
                        );
                    } else {
                        alert('✅ ' + data.message + '\nID: ' + data.id_pregonero);
                    }
                    
                    form.reset();
                    
                    form.classList.add('success-glow');
                    setTimeout(() => form.classList.remove('success-glow'), 1000);
                    
                    // Limpiar mensajes de verificación
                    const mensajeVerificacion = document.getElementById('mensaje-verificacion');
                    if (mensajeVerificacion) {
                        mensajeVerificacion.remove();
                    }
                    
                    // Restaurar estilos del input
                    if (idInput) {
                        idInput.style.borderColor = '#e0e0e0';
                        idInput.style.backgroundColor = '#f9f9f9';
                        idInput.classList.remove('success', 'error');
                    }
                    
                    // Resetear checkbox y habilitar campo quien_reporta
                    if (mismoCheckbox) {
                        mismoCheckbox.checked = false;
                        const quienReportaInput = document.getElementById('quien_reporta');
                        if (quienReportaInput) {
                            quienReportaInput.disabled = false;
                            quienReportaInput.classList.remove('bg-light');
                        }
                    }
                } else {
                    if (typeof showNotification === 'function') {
                        showNotification('error', data.message, 'Error al guardar');
                    } else {
                        alert('❌ ' + data.message);
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                if (typeof showNotification === 'function') {
                    showNotification('error', 'Error de conexión con el servidor', 'Error de red');
                } else {
                    alert('❌ Error de conexión');
                }
            } finally {
                if (typeof showLoading === 'function') {
                    showLoading(false);
                }
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