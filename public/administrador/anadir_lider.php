<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';

// Verificar permisos (solo administradores pueden agregar coordinadores)
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'Administrador') {
    header('Location: ../index.php');
    exit();
}

$pdo = Database::getConnection();
$usuarioModel = new UsuarioModel($pdo);

// Obtener referenciadores activos para el combobox usando tu función
$referenciadores = $usuarioModel->getAllReferenciadoresActivos();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Líder - SGP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ESTILOS EXACTAMENTE IGUALES A LOS QUE ME ENVIASTE */
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
        
        .form-container {
            background: rgba(30, 30, 40, 0.85);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.5),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            width: 100%;
            max-width: 1000px;
            padding: 40px;
            animation: fadeIn 0.5s ease-out;
            margin-top: 80px;
            margin-bottom: 40px;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .form-header h2 {
            color: #ffffff;
            font-size: 1.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .form-header h2 i {
            color: #4fc3f7;
        }
        
        .form-header p {
            color: #b0bec5;
            font-size: 0.95rem;
            line-height: 1.5;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
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
            color: #cfd8dc;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-label i {
            color: #90a4ae;
            font-size: 1rem;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.3s;
            background: rgba(30, 30, 40, 0.9);
            color: #ffffff;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        .form-control::placeholder {
            color: #90a4ae;
            opacity: 0.7;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #4fc3f7;
            background: rgba(40, 40, 50, 0.95);
            box-shadow: 
                0 0 0 3px rgba(79, 195, 247, 0.2),
                inset 0 1px 2px rgba(255, 255, 255, 0.1);
        }
        
        .form-select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.3s;
            background: rgba(30, 30, 40, 0.9);
            color: #ffffff;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%234fc3f7' width='18px' height='18px'%3e%3cpath d='M7 10l5 5 5-5z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 18px;
        }
        
        .form-select option {
            background: rgba(30, 30, 50, 0.95);
            color: #ffffff;
            padding: 12px;
            font-size: 0.95rem;
            border: none;
        }
        
        .form-select:focus {
            outline: none;
            border-color: #4fc3f7;
            background: rgba(40, 40, 50, 0.95);
            box-shadow: 
                0 0 0 3px rgba(79, 195, 247, 0.2),
                inset 0 1px 2px rgba(255, 255, 255, 0.1);
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #90a4ae;
            font-size: 1rem;
            z-index: 2;
        }
        
        .input-with-icon .form-control, 
        .input-with-icon .form-select {
            padding-left: 40px;
        }
        
        /* Estilos específicos para el estado de verificación de cédula */
        .cedula-status {
            margin-top: 5px;
            font-size: 0.85rem;
            padding: 8px 10px;
            border-radius: 6px;
            display: none;
            line-height: 1.4;
            border-left: 3px solid transparent;
        }
        
        .cedula-available {
            color: #27ae60;
            background: rgba(39, 174, 96, 0.1);
            border-left-color: #27ae60;
        }
        
        .cedula-taken {
            color: #e74c3c;
            background: rgba(231, 76, 60, 0.1);
            border-left-color: #e74c3c;
        }
        
        .cedula-loading {
            color: #f39c12;
            background: rgba(243, 156, 18, 0.1);
            border-left-color: #f39c12;
        }
        
        .cedula-status i {
            margin-right: 5px;
        }
        
        .cedula-status strong {
            color: inherit;
            opacity: 0.9;
        }
        
        .form-actions {
            grid-column: 1 / -1;
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
        }
        
        .submit-btn {
            background: linear-gradient(135deg, #06d6a0, #118ab2);
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
            box-shadow: 0 4px 15px rgba(6, 214, 160, 0.3);
        }
        
        .submit-btn:hover {
            background: linear-gradient(135deg, #05c593, #0d7a9c);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(6, 214, 160, 0.4);
        }
        
        .submit-btn:disabled {
            background: linear-gradient(135deg, #7f8c8d, #95a5a6);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
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
        
        .form-footer {
            margin-top: 30px;
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #90a4ae;
            font-size: 0.85rem;
        }
        
        .form-footer i {
            color: #4fc3f7;
            margin-right: 5px;
        }
        
        .field-hint {
            color: #90a4ae;
            font-size: 0.8rem;
            margin-top: 5px;
            display: block;
            opacity: 0.8;
        }
        
        /* Estilos para notificaciones */
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .notification {
            position: fixed;
            top: 100px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 10px;
            max-width: 400px;
            animation: slideIn 0.3s ease;
        }
        
        .notification-success {
            background: #27ae60;
            color: white;
        }
        
        .notification-error {
            background: #e74c3c;
            color: white;
        }
        
        .notification-warning {
            background: #f39c12;
            color: white;
        }
        
        .notification-info {
            background: #3498db;
            color: white;
        }
        
        .notification .btn-close {
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            padding: 0;
            margin-left: 10px;
            opacity: 0.8;
            transition: opacity 0.3s;
        }
        
        .notification .btn-close:hover {
            opacity: 1;
        }
        
        @media (max-width: 768px) {
            .form-container {
                padding: 25px;
                margin-top: 100px;
                margin-bottom: 30px;
                max-width: 95%;
            }
            
            .form-grid {
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
            
            .form-actions {
                flex-direction: column;
            }
            
            .submit-btn, .cancel-btn {
                width: 100%;
                min-width: auto;
                padding: 12px;
            }
            
            .notification {
                top: 80px;
                right: 10px;
                left: 10px;
                max-width: none;
            }
            
            .form-select, .form-control {
                padding: 10px 12px;
                font-size: 0.9rem;
            }
            
            .form-select {
                background-position: right 12px center;
                background-size: 16px;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            
            .form-container {
                padding: 20px;
                margin-top: 90px;
                margin-bottom: 20px;
            }
            
            .form-header h2 {
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
                    <h1><i class="fas fa-user-plus"></i> Agregar Nuevo Líder</h1>
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span>Administrador</span>
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

    <!-- Main Form -->
    <div class="form-container">
        <div class="form-header">
            <h2><i class="fas fa-user-cog"></i> Registro de Nuevo Líder</h2>
            <p>Complete todos los campos para registrar un nuevo líder en el sistema</p>
        </div>
        
        <!-- FORMULARIO PARA AGREGAR DATOS -->
        <form id="lider-form" method="POST">
            <div class="form-grid">
                <!-- Nombres -->
                <div class="form-group">
                    <label class="form-label" for="nombres">
                        <i class="fas fa-user"></i> Nombres *
                    </label>
                    <input type="text" 
                           id="nombres" 
                           name="nombres" 
                           class="form-control" 
                           placeholder="Ingrese los nombres"
                           required
                           autocomplete="off">
                </div>
                
                <!-- Apellidos -->
                <div class="form-group">
                    <label class="form-label" for="apellidos">
                        <i class="fas fa-user"></i> Apellidos *
                    </label>
                    <input type="text" 
                           id="apellidos" 
                           name="apellidos" 
                           class="form-control" 
                           placeholder="Ingrese los apellidos"
                           required
                           autocomplete="off">
                </div>
                
                <!-- Cédula -->
                <div class="form-group">
                    <label class="form-label" for="cedula">
                        <i class="fas fa-id-card"></i> Cédula *
                    </label>
                    <div class="input-with-icon">
                        <i class="fas fa-id-card input-icon"></i>
                        <input type="text" 
                               id="cedula" 
                               name="cedula" 
                               class="form-control" 
                               placeholder="Ingrese el número de cédula"
                               required
                               maxlength="10"
                               pattern="\d{6,10}"
                               title="Ingrese un número de cédula válido (6-10 dígitos)"
                               autocomplete="off">
                    </div>
                    <span class="field-hint">6-10 dígitos numéricos</span>
                    <!-- Mensaje de estado de verificación de cédula -->
                    <div id="cedula-status" class="cedula-status"></div>
                </div>
                
                <!-- Teléfono -->
                <div class="form-group">
                    <label class="form-label" for="telefono">
                        <i class="fas fa-phone"></i> Teléfono *
                    </label>
                    <div class="input-with-icon">
                        <i class="fas fa-phone input-icon"></i>
                        <input type="tel" 
                            id="telefono" 
                            name="telefono" 
                            class="form-control" 
                            placeholder="Ej: 3001234567"
                            required
                            maxlength="10"
                            pattern="\d{10}"
                            title="El teléfono debe tener exactamente 10 dígitos"
                            autocomplete="off">
                    </div>
                    <span class="field-hint">Debe tener exactamente 10 dígitos</span>
                </div>
                
                <!-- Correo -->
                <div class="form-group">
                    <label class="form-label" for="correo">
                        <i class="fas fa-envelope"></i> Correo Electrónico *
                    </label>
                    <div class="input-with-icon">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" 
                               id="correo" 
                               name="correo" 
                               class="form-control" 
                               placeholder="ejemplo@correo.com"
                               required
                               autocomplete="off">
                    </div>
                </div>
                
                <!-- Referenciador Asignado (Usando tu función getAllReferenciadoresActivos) -->
                <div class="form-group">
                    <label class="form-label" for="id_referenciador">
                        <i class="fas fa-user-tie"></i> Coordinador Asignado
                    </label>
                    <select id="id_referenciador" name="id_referenciador" class="form-select">
                        <option value="">Seleccione un referenciador</option>
                        <?php if (!empty($referenciadores)): ?>
                            <?php foreach ($referenciadores as $referenciador): ?>
                                <option value="<?php echo htmlspecialchars($referenciador['id_usuario']); ?>">
                                    <?php echo htmlspecialchars($referenciador['nombres'] . ' ' . $referenciador['apellidos']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="">No hay referenciadores activos</option>
                        <?php endif; ?>
                    </select>
                    <span class="field-hint">Seleccione el referenciador que supervisará a este líder</span>
                </div>
                
                <!-- Botones -->
                <div class="form-group full-width form-actions">
                    <a href="../dashboard.php" class="cancel-btn">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                    <button type="submit" class="submit-btn" id="submit-btn">
                        <i class="fas fa-user-plus"></i> Registrar Líder
                    </button>
                </div>
            </div>
            
            <div class="form-footer">
                <p><i class="fas fa-info-circle"></i> Los campos marcados con * son obligatorios</p>
                <p><i class="fas fa-shield-alt"></i> Todos los datos se almacenan de forma segura</p>
            </div>
        </form>
    </div>
    
    <script>
    // ==================== FUNCIÓN PARA MOSTRAR NOTIFICACIONES ====================
    function showNotification(message, type = 'info') {
        const oldNotification = document.querySelector('.notification');
        if (oldNotification) oldNotification.remove();
        
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        
        let icon = 'info-circle';
        if (type === 'success') icon = 'check-circle';
        if (type === 'error') icon = 'exclamation-circle';
        if (type === 'warning') icon = 'exclamation-triangle';
        
        notification.innerHTML = `
            <i class="fas fa-${icon}"></i>
            <span>${message}</span>
            <button class="btn-close" onclick="this.parentElement.remove()">×</button>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentNode) notification.remove();
        }, 5000);
    }
    
    // ==================== FUNCIÓN PARA VERIFICAR CÉDULA EN LÍDERES ====================
    async function verificarCedula(cedula) {
        const statusElement = document.getElementById('cedula-status');
        const submitBtn = document.getElementById('submit-btn');
        
        if (!cedula || cedula.length < 6) {
            statusElement.style.display = 'none';
            submitBtn.disabled = false;
            return false;
        }
        
        // Mostrar estado de carga
        statusElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando cédula en líderes...';
        statusElement.className = 'cedula-status cedula-loading';
        statusElement.style.display = 'block';
        submitBtn.disabled = true;
        
        try {
            const formData = new FormData();
            formData.append('cedula', cedula);
            
            const response = await fetch('../ajax/verificar_cedula_lider.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                if (data.exists) {
                    // Cédula ya existe en líderes
                    const fechaRegistro = data.fecha_registro || 'fecha desconocida';
                    const liderNombre = data.lider_nombre || 'nombre desconocido';
                    const referenciador = data.referenciador_nombre || 'sin coordinador asignado';
                    const estado = data.estado || 'estado desconocido';
                    
                    let mensaje = `<i class="fas fa-exclamation-triangle"></i> 
                        <strong>Esta cédula ya está registrada como líder.</strong><br>
                        <strong>Líder:</strong> ${liderNombre}<br>
                        <strong>Fecha registro:</strong> ${fechaRegistro}<br>
                        <strong>Estado:</strong> ${estado}<br>`;
                    
                    if (data.referenciador_nombre) {
                        mensaje += `<strong>Coordinador:</strong> ${referenciador}<br>`;
                    }
                    
                    if (data.tambien_en_referenciados) {
                        mensaje += `<strong>⚠ También está registrado como referenciado</strong><br>
                        <strong>Fecha registro referenciado:</strong> ${data.referenciado_info.fecha_registro}<br>
                        <strong>Referenciador:</strong> ${data.referenciado_info.referenciador}`;
                    }
                    
                    statusElement.innerHTML = mensaje;
                    statusElement.className = 'cedula-status cedula-taken';
                    submitBtn.disabled = true;
                    return false;
                } else {
                    // Cédula disponible
                    statusElement.innerHTML = '<i class="fas fa-check-circle"></i> Cédula disponible para registro como líder';
                    statusElement.className = 'cedula-status cedula-available';
                    submitBtn.disabled = false;
                    return true;
                }
            } else {
                // Error en la verificación
                statusElement.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${data.message || 'Error al verificar cédula'}`;
                statusElement.className = 'cedula-status cedula-taken';
                submitBtn.disabled = false; // Permitir enviar pero con advertencia
                return false;
            }
        } catch (error) {
            console.error('Error verificando cédula:', error);
            statusElement.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error de conexión al verificar cédula';
            statusElement.className = 'cedula-status cedula-taken';
            submitBtn.disabled = false; // Permitir enviar en caso de error
            return false;
        }
    }
    
    // ==================== INICIALIZACIÓN ====================
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Formulario de líder cargado');
        
        // Deshabilitar validación HTML5 nativa
        document.getElementById('lider-form').setAttribute('novalidate', 'novalidate');
        
        // Variables globales
        let cedulaVerificada = false;
        let timeoutVerificacion = null;
        
        // Formato de cédula (solo números)
        const cedulaInput = document.getElementById('cedula');
        cedulaInput.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '');
            
            // Limpiar timeout anterior
            if (timeoutVerificacion) {
                clearTimeout(timeoutVerificacion);
            }
            
            // Limpiar estado de verificación
            const statusElement = document.getElementById('cedula-status');
            statusElement.style.display = 'none';
            cedulaVerificada = false;
            
            // Habilitar botón mientras se verifica
            const submitBtn = document.getElementById('submit-btn');
            submitBtn.disabled = false;
            
            // Verificar después de 1 segundo sin cambios (debounce)
            timeoutVerificacion = setTimeout(async () => {
                const cedula = e.target.value;
                if (cedula.length >= 6) {
                    const resultado = await verificarCedula(cedula);
                    cedulaVerificada = resultado;
                }
            }, 1000);
        });
        
        // Cuando se pierde el foco de la cédula, verificar inmediatamente
        cedulaInput.addEventListener('blur', function() {
            const cedula = this.value.trim();
            if (cedula.length >= 6 && !cedulaVerificada) {
                verificarCedula(cedula);
            }
        });
        
        // Formato de teléfono (solo números)
        const telefonoInput = document.getElementById('telefono');
        telefonoInput.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '');
            if (e.target.value.length > 10) {
                e.target.value = e.target.value.substring(0, 10);
            }
        });
        
        // ==================== VALIDACIÓN Y ENVÍO DEL FORMULARIO ====================
        const liderForm = document.getElementById('lider-form');
        const submitBtn = document.getElementById('submit-btn');
        
        liderForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Verificar si la cédula fue verificada
            const cedula = cedulaInput.value.replace(/\D/g, '');
            if (cedula.length >= 6 && !cedulaVerificada) {
                // Forzar verificación antes de enviar
                const resultado = await verificarCedula(cedula);
                if (!resultado) {
                    showNotification('Debe verificar que la cédula esté disponible antes de registrar.', 'warning');
                    return;
                }
            }
            
            // Validar campos obligatorios
            const requiredFields = ['nombres', 'apellidos', 'cedula', 'telefono', 'correo'];
            let isValid = true;
            let errorField = null;
            
            requiredFields.forEach(field => {
                const element = document.getElementById(field);
                if (element && !element.value.trim()) {
                    isValid = false;
                    if (!errorField) errorField = element;
                }
            });
            
            if (!isValid) {
                showNotification('Por favor complete todos los campos obligatorios (*)', 'error');
                if (errorField) errorField.focus();
                return;
            }
            
            // Validar cédula
            if (cedula.length < 6) {
                showNotification('La cédula debe tener al menos 6 dígitos.', 'error');
                cedulaInput.focus();
                return;
            }
            
            // Validar teléfono
            const telefono = telefonoInput.value.trim();
            if (telefono.length !== 10) {
                showNotification('El teléfono debe tener exactamente 10 dígitos.', 'error');
                telefonoInput.focus();
                return;
            }
            
            // Validar correo
            const correo = document.getElementById('correo').value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(correo)) {
                showNotification('Por favor ingrese un correo electrónico válido.', 'error');
                document.getElementById('correo').focus();
                return;
            }
            
            // Mostrar estado de carga
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registrando...';
            submitBtn.disabled = true;
            
            // Crear FormData con los datos del formulario
            const formData = new FormData(liderForm);
            
            try {
                const response = await fetch('../ajax/guardar_lider.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor: ' + response.status);
                }
                
                const data = await response.json();
                console.log('Respuesta del servidor:', data);
                
                if (data.success) {
                    showNotification(data.message, 'success');
                    
                    // Resetear formulario después de éxito
                    setTimeout(() => {
                        liderForm.reset();
                        cedulaVerificada = false;
                        document.getElementById('cedula-status').style.display = 'none';
                        showNotification('Formulario listo para registrar otro líder', 'info');
                    }, 2000);
                    
                } else {
                    showNotification(data.message, 'error');
                }
            } catch (error) {
                console.error('Error de red:', error);
                showNotification('Error de conexión con el servidor. Verifica tu conexión a internet.', 'error');
            } finally {
                // Restaurar estado del botón
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });
        
        console.log('Formulario de líder inicializado correctamente');
    });
    </script>
</body>
</html>