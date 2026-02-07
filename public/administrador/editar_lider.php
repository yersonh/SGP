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

// Verificar permisos (solo administradores pueden editar líderes)
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'Administrador') {
    header('Location: ../index.php');
    exit();
}

// Obtener ID del líder a editar
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ../dashboard.php');
    exit();
}

$id_lider_editar = intval($_GET['id']);

$pdo = Database::getConnection();
$liderModel = new LiderModel($pdo);
$usuarioModel = new UsuarioModel($pdo);

// Obtener datos del líder a editar
$lider_editar = $liderModel->getById($id_lider_editar);

if (!$lider_editar) {
    header('Location: ../dashboard.php?error=lider_no_encontrado');
    exit();
}

// Obtener datos del usuario logueado
$usuario_logueado = $usuarioModel->getUsuarioById($_SESSION['id_usuario']);

// Obtener todos los referenciadores para el select
$referenciadores = $usuarioModel->getReferenciadoresActivos();

// Variables para mensajes de error/success
$mensaje_error = '';
$mensaje_success = '';

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar datos requeridos
        $nombres = trim($_POST['nombres'] ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $cc = trim($_POST['cc'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $estado = $_POST['estado'] ?? '1';
        $id_usuario = !empty($_POST['id_usuario']) ? intval($_POST['id_usuario']) : null;
        
        // Validaciones básicas
        if (empty($nombres)) {
            throw new Exception('El nombre es requerido');
        }
        
        if (empty($apellidos)) {
            throw new Exception('Los apellidos son requeridos');
        }
        
        if (empty($cc)) {
            throw new Exception('La cédula es requerida');
        }
        
        // Validar formato de cédula (solo números, mínimo 5 dígitos)
        if (!preg_match('/^[0-9]{5,20}$/', $cc)) {
            throw new Exception('La cédula debe contener solo números (5-20 dígitos)');
        }
        
        // Verificar si la cédula ya existe (excluyendo el líder actual)
        if ($liderModel->cedulaExists($cc, $id_lider_editar)) {
            throw new Exception('La cédula ya está registrada para otro líder');
        }
        
        // Validar email si se proporciona
        if (!empty($correo) && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('El correo electrónico no es válido');
        }
        
        // Validar teléfono (solo números, mínimo 7 dígitos)
        if (!empty($telefono) && !preg_match('/^[0-9]{7,15}$/', $telefono)) {
            throw new Exception('El teléfono debe contener solo números (7-15 dígitos)');
        }
        
        // Preparar datos para actualizar
        $datos_actualizar = [
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'cc' => $cc,
            'telefono' => $telefono,
            'correo' => $correo,
            'estado' => ($estado === '1'),
            'id_usuario' => $id_usuario
        ];
        
        // Actualizar el líder
        $resultado = $liderModel->update($id_lider_editar, $datos_actualizar);
        
        if ($resultado['success']) {
            // Actualizar datos locales para mostrar
            $lider_editar = array_merge($lider_editar, $datos_actualizar);
            
            // Obtener nuevamente el referenciador si fue cambiado
            if ($id_usuario) {
                $referenciador = $usuarioModel->getUsuarioById($id_usuario);
            }
            
            $mensaje_success = 'Líder actualizado correctamente';
            
            // Redirigir después de 2 segundos
            header('Refresh: 2; URL=ver_lider.php?id=' . $id_lider_editar);
        } else {
            throw new Exception($resultado['message']);
        }
        
    } catch (Exception $e) {
        $mensaje_error = $e->getMessage();
    }
}

// Obtener referenciador actual si existe
$referenciador = null;
if (!empty($lider_editar['id_usuario'])) {
    $referenciador = $usuarioModel->getUsuarioById($lider_editar['id_usuario']);
}

// Determinar si el líder está activo
$esta_activo = ($lider_editar['estado'] === true || $lider_editar['estado'] === 't' || $lider_editar['estado'] == 1);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Líder - SGP</title>
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
        
        .edit-container {
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
        
        .edit-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .edit-header h2 {
            color: #ffffff;
            font-size: 1.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .edit-header h2 i {
            color: #f39c12;
        }
        
        .edit-header p {
            color: #b0bec5;
            font-size: 0.95rem;
            line-height: 1.5;
        }
        
        .user-edit-info {
            background: rgba(243, 156, 18, 0.1);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            border-left: 4px solid #f39c12;
        }
        
        .user-edit-info i {
            color: #f39c12;
            font-size: 1.5rem;
        }
        
        .user-edit-info span {
            color: #ffffff;
            font-weight: 500;
        }
        
        .edit-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
        }
        
        .edit-group {
            margin-bottom: 0;
        }
        
        .edit-group.full-width {
            grid-column: 1 / -1;
        }
        
        .edit-label {
            display: block;
            margin-bottom: 8px;
            color: #cfd8dc;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .edit-label i {
            color: #90a4ae;
            font-size: 1rem;
        }
        
        .edit-input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.3s;
            background: rgba(30, 30, 40, 0.7);
            color: #ffffff;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        .edit-input:focus {
            outline: none;
            border-color: #f39c12;
            box-shadow: 0 0 0 2px rgba(243, 156, 18, 0.2);
        }
        
        .edit-select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.3s;
            background: rgba(30, 30, 40, 0.7);
            color: #ffffff;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2390a4ae' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 16px;
        }
        
        .edit-select:focus {
            outline: none;
            border-color: #f39c12;
            box-shadow: 0 0 0 2px rgba(243, 156, 18, 0.2);
        }
        
        .radio-group {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .radio-option {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        
        .radio-option input[type="radio"] {
            display: none;
        }
        
        .radio-custom {
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        
        .radio-custom::after {
            content: '';
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #f39c12;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .radio-option input[type="radio"]:checked + .radio-custom {
            border-color: #f39c12;
        }
        
        .radio-option input[type="radio"]:checked + .radio-custom::after {
            opacity: 1;
        }
        
        .radio-label {
            color: #ffffff;
            font-weight: 500;
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
        
        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .message-success {
            background: rgba(39, 174, 96, 0.1);
            border-left: 4px solid #27ae60;
            color: #27ae60;
        }
        
        .message-error {
            background: rgba(231, 76, 60, 0.1);
            border-left: 4px solid #e74c3c;
            color: #e74c3c;
        }
        
        .edit-actions {
            grid-column: 1 / -1;
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
        }
        
        .cancel-btn {
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
        
        .cancel-btn:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
            border-color: rgba(255, 255, 255, 0.3);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
            color: white;
        }
        
        .save-btn {
            background: linear-gradient(135deg, #27ae60, #219653);
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
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3);
        }
        
        .save-btn:hover {
            background: linear-gradient(135deg, #219653, #1e874b);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(39, 174, 96, 0.4);
            color: white;
        }
        
        .edit-footer {
            margin-top: 30px;
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #90a4ae;
            font-size: 0.85rem;
        }
        
        .edit-footer i {
            color: #f39c12;
            margin-right: 5px;
        }
        
        @media (max-width: 768px) {
            .edit-container {
                padding: 25px;
                margin-top: 100px;
                margin-bottom: 30px;
                max-width: 95%;
            }
            
            .edit-grid {
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
            
            .edit-actions {
                flex-direction: column;
            }
            
            .cancel-btn, .save-btn {
                width: 100%;
                min-width: auto;
                padding: 12px;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            
            .edit-container {
                padding: 20px;
                margin-top: 90px;
                margin-bottom: 20px;
            }
            
            .edit-header h2 {
                font-size: 1.5rem;
            }
            
            .radio-group {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
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
                    <h1><i class="fas fa-user-tie"></i> Editar Líder</h1>
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

    <!-- Main Edit Form -->
    <div class="edit-container">
        <div class="edit-header">
            <h2><i class="fas fa-edit"></i> Editar Información del Líder</h2>
            <p>Modifique los datos del líder según sea necesario</p>
        </div>
        
        <!-- Información del líder que se está editando -->
        <div class="user-edit-info">
            <i class="fas fa-edit"></i>
            <span>Editando información de: <strong><?php echo htmlspecialchars($lider_editar['nombres'] . ' ' . $lider_editar['apellidos']); ?></strong></span>
        </div>
        
        <!-- Mensajes de éxito/error -->
        <?php if ($mensaje_success): ?>
            <div class="message message-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($mensaje_success); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($mensaje_error): ?>
            <div class="message message-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($mensaje_error); ?></span>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="editForm">
            <div class="edit-grid">
                <!-- Nombres -->
                <div class="edit-group">
                    <label class="edit-label" for="nombres">
                        <i class="fas fa-user"></i> Nombres *
                    </label>
                    <input type="text" 
                           id="nombres" 
                           name="nombres" 
                           class="edit-input" 
                           value="<?php echo htmlspecialchars($lider_editar['nombres'] ?? ''); ?>"
                           required
                           maxlength="100">
                </div>
                
                <!-- Apellidos -->
                <div class="edit-group">
                    <label class="edit-label" for="apellidos">
                        <i class="fas fa-user"></i> Apellidos *
                    </label>
                    <input type="text" 
                           id="apellidos" 
                           name="apellidos" 
                           class="edit-input" 
                           value="<?php echo htmlspecialchars($lider_editar['apellidos'] ?? ''); ?>"
                           required
                           maxlength="100">
                </div>
                
                <!-- Cédula -->
                <div class="edit-group">
                    <label class="edit-label" for="cc">
                        <i class="fas fa-id-card"></i> Cédula *
                    </label>
                    <input type="text" 
                           id="cc" 
                           name="cc" 
                           class="edit-input" 
                           value="<?php echo htmlspecialchars($lider_editar['cc'] ?? ''); ?>"
                           required
                           pattern="[0-9]{5,20}"
                           title="La cédula debe contener solo números (5-20 dígitos)">
                </div>
                
                <!-- Teléfono -->
                <div class="edit-group">
                    <label class="edit-label" for="telefono">
                        <i class="fas fa-phone"></i> Teléfono
                    </label>
                    <input type="tel" 
                           id="telefono" 
                           name="telefono" 
                           class="edit-input" 
                           value="<?php echo htmlspecialchars($lider_editar['telefono'] ?? ''); ?>"
                           pattern="[0-9]{7,15}"
                           title="El teléfono debe contener solo números (7-15 dígitos)">
                </div>
                
                <!-- Correo -->
                <div class="edit-group">
                    <label class="edit-label" for="correo">
                        <i class="fas fa-envelope"></i> Correo Electrónico
                    </label>
                    <input type="email" 
                           id="correo" 
                           name="correo" 
                           class="edit-input" 
                           value="<?php echo htmlspecialchars($lider_editar['correo'] ?? ''); ?>">
                </div>
                
                <!-- Estado 
                <div class="edit-group">
                    <label class="edit-label">
                        <i class="fas fa-toggle-on"></i> Estado del Líder *
                    </label>
                    <div class="radio-group">
                        <label class="radio-option">
                            <input type="radio" 
                                   name="estado" 
                                   value="1" 
                                   <?php echo $esta_activo ? 'checked' : ''; ?>>
                            <span class="radio-custom"></span>
                            <span class="radio-label">
                                <span class="badge-status badge-active">
                                    <i class="fas fa-check-circle"></i> Activo
                                </span>
                            </span>
                        </label>
                        <label class="radio-option">
                            <input type="radio" 
                                   name="estado" 
                                   value="0" 
                                   <?php echo !$esta_activo ? 'checked' : ''; ?>>
                            <span class="radio-custom"></span>
                            <span class="radio-label">
                                <span class="badge-status badge-inactive">
                                    <i class="fas fa-times-circle"></i> Inactivo
                                </span>
                            </span>
                        </label>
                    </div>
                </div>-->
                
                <!-- Referenciador -->
                <div class="edit-group">
                    <label class="edit-label" for="id_usuario">
                        <i class="fas fa-user-friends"></i> Referenciador Asignado
                    </label>
                    <select id="id_usuario" name="id_usuario" class="edit-select">
                        <option value="">Sin referenciador</option>
                        <?php foreach ($referenciadores as $ref): ?>
                            <option value="<?php echo $ref['id_usuario']; ?>"
                                <?php echo ($lider_editar['id_usuario'] == $ref['id_usuario']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($ref['nombres'] . ' ' . $ref['apellidos'] . ' (' . $ref['nickname'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Botones de acción -->
                <div class="edit-group full-width edit-actions">
                    <a href="ver_lider.php?id=<?php echo $id_lider_editar; ?>" class="cancel-btn">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                    
                    <button type="submit" class="save-btn">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </div>
        </form>
        
        <div class="edit-footer">
            <p><i class="fas fa-info-circle"></i> Los campos marcados con * son obligatorios</p>
            <p><i class="fas fa-shield-alt"></i> La cédula debe ser única para cada líder</p>
        </div>
    </div>

    <script>
        // Validación del formulario
        document.getElementById('editForm').addEventListener('submit', function(event) {
            let isValid = true;
            let errorMessage = '';
            
            // Validar nombres
            const nombres = document.getElementById('nombres').value.trim();
            if (!nombres) {
                isValid = false;
                errorMessage += '• El nombre es requerido\n';
            }
            
            // Validar apellidos
            const apellidos = document.getElementById('apellidos').value.trim();
            if (!apellidos) {
                isValid = false;
                errorMessage += '• Los apellidos son requeridos\n';
            }
            
            // Validar cédula
            const cc = document.getElementById('cc').value.trim();
            if (!cc) {
                isValid = false;
                errorMessage += '• La cédula es requerida\n';
            } else if (!/^\d{5,20}$/.test(cc)) {
                isValid = false;
                errorMessage += '• La cédula debe contener solo números (5-20 dígitos)\n';
            }
            
            // Validar teléfono si se proporciona
            const telefono = document.getElementById('telefono').value.trim();
            if (telefono && !/^\d{7,15}$/.test(telefono)) {
                isValid = false;
                errorMessage += '• El teléfono debe contener solo números (7-15 dígitos)\n';
            }
            
            // Validar email si se proporciona
            const correo = document.getElementById('correo').value.trim();
            if (correo && !isValidEmail(correo)) {
                isValid = false;
                errorMessage += '• El correo electrónico no es válido\n';
            }
            
            if (!isValid) {
                event.preventDefault();
                alert('Por favor, corrija los siguientes errores:\n\n' + errorMessage);
            }
        });
        
        // Función para validar email
        function isValidEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }
        
        // Mostrar confirmación antes de salir sin guardar
        let formChanged = false;
        
        const formInputs = document.querySelectorAll('#editForm input, #editForm select, #editForm textarea');
        formInputs.forEach(input => {
            input.addEventListener('change', () => {
                formChanged = true;
            });
            input.addEventListener('input', () => {
                formChanged = true;
            });
        });
        
        window.addEventListener('beforeunload', function(event) {
            if (formChanged) {
                event.preventDefault();
                event.returnValue = 'Tiene cambios sin guardar. ¿Está seguro de que desea salir?';
            }
        });
        
        // Cancelar el beforeunload si se envía el formulario
        document.getElementById('editForm').addEventListener('submit', function() {
            formChanged = false;
        });
        
        // Cancelar el beforeunload si se hace clic en Cancelar
        document.querySelector('.cancel-btn').addEventListener('click', function() {
            formChanged = false;
        });
    </script>
</body>
</html>