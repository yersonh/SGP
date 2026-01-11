<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UsuarioModel.php';

// Verificar si el usuario está logueado y es referenciador
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'Referenciador') {
    header('Location: index.php');
    exit();
}

$pdo = Database::getConnection();
$model = new UsuarioModel($pdo);
$id_usuario_logueado = $_SESSION['id_usuario'];

// Obtener datos del usuario logueado
$usuario_logueado = $model->getUsuarioById($id_usuario_logueado);

// Actualizar último registro
$fecha_actual = date('Y-m-d H:i:s');
$model->actualizarUltimoRegistro($id_usuario_logueado, $fecha_actual);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de Referenciación - SGP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/rateYo/2.3.2/jquery.rateyo.min.css">
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
        
        /* Progress Bar */
        .progress-container {
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 10px;
            margin-top: 10px;
        }
        
        .progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        
        .progress-bar {
            height: 8px;
            background: rgba(255,255,255,0.2);
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #3498db, #2ecc71);
            width: 0%;
            transition: width 0.5s ease;
            border-radius: 4px;
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
        
        /* Rating Stars */
        .rating-container {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .stars-container {
            display: flex;
            gap: 5px;
        }
        
        .star {
            font-size: 1.5rem;
            color: #ddd;
            cursor: pointer;
            transition: color 0.3s;
        }
        
        .star.selected {
            color: #f39c12;
        }
        
        .star:hover, .star.hover {
            color: #f39c12;
        }
        
        .rating-value {
            font-weight: 600;
            color: var(--primary-color);
            min-width: 40px;
        }
        
        /* Input with Icon */
        .input-with-icon {
            position: relative;
        }
        
        .input-with-icon .form-control {
            padding-left: 45px;
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #7f8c8d;
            font-size: 1.1rem;
        }
        
        .input-suffix {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary-color);
            cursor: pointer;
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
            background: var(--primary-color);
            color: white;
            font-size: 0.85rem;
            line-height: 1.5;
            margin-top: 30px;
        }
        
        .system-footer p {
            margin: 5px 0;
        }
        
        .system-footer strong {
            color: #3498db;
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
        
        /* Notification */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
            animation: slideIn 0.3s ease-out;
            max-width: 400px;
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
        
        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="header-container">
            <div class="header-top">
                <div class="header-title">
                    <h1><i class="fas fa-user-tie"></i> Formulario de Referenciación</h1>
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($usuario_logueado['nombres'] . ' ' . $usuario_logueado['apellidos']); ?></span>
                    </div>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
            
            <!-- Progress Bar -->
            <div class="progress-container">
                <div class="progress-header">
                    <span>Progreso del formulario</span>
                    <span id="progress-percentage">0%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" id="progress-fill"></div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Form -->
    <div class="main-container">
        <div class="form-card">
            <div class="form-header">
                <h2><i class="fas fa-edit"></i> Datos Personales del Referido</h2>
            </div>
            
            <form id="referenciacion-form">
                <div class="form-grid">
                    <!-- Nombre -->
                    <div class="form-group">
                        <label class="form-label" for="nombre">
                            <i class="fas fa-user"></i> Nombre *
                        </label>
                        <input type="text" 
                               id="nombre" 
                               name="nombre" 
                               class="form-control" 
                               placeholder="Ingrese el nombre"
                               required
                               data-progress="5">
                    </div>
                    
                    <!-- Apellido -->
                    <div class="form-group">
                        <label class="form-label" for="apellido">
                            <i class="fas fa-user"></i> Apellido *
                        </label>
                        <input type="text" 
                               id="apellido" 
                               name="apellido" 
                               class="form-control" 
                               placeholder="Ingrese el apellido"
                               required
                               data-progress="5">
                    </div>
                    
                    <!-- Cédula -->
                    <div class="form-group">
                        <label class="form-label" for="cedula">
                            <i class="fas fa-id-card"></i> Cédula (CC) *
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
                                   title="Ingrese un número de cédula válido"
                                   data-progress="5">
                        </div>
                    </div>
                    
                    <!-- Dirección -->
                    <div class="form-group full-width">
                        <label class="form-label" for="direccion">
                            <i class="fas fa-map-marker-alt"></i> Dirección *
                        </label>
                        <input type="text" 
                               id="direccion" 
                               name="direccion" 
                               class="form-control" 
                               placeholder="Ingrese la dirección completa"
                               required
                               data-progress="5">
                    </div>
                    
                    <!-- Email -->
                    <div class="form-group">
                        <label class="form-label" for="email">
                            <i class="fas fa-envelope"></i> Email *
                        </label>
                        <div class="input-with-icon">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   class="form-control" 
                                   placeholder="correo@ejemplo.com"
                                   required
                                   data-progress="5">
                        </div>
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
                                   placeholder="Ingrese el número de teléfono"
                                   required
                                   pattern="[0-9]{7,10}"
                                   title="Ingrese un número de teléfono válido"
                                   data-progress="5">
                        </div>
                    </div>
                    
                    <!-- Afinidad (Rating) -->
                    <div class="form-group full-width">
                        <label class="form-label">
                            <i class="fas fa-star"></i> Nivel de Afinidad *
                        </label>
                        <div class="rating-container">
                            <div class="stars-container" id="rating-stars">
                                <span class="star" data-value="1"><i class="far fa-star"></i></span>
                                <span class="star" data-value="2"><i class="far fa-star"></i></span>
                                <span class="star" data-value="3"><i class="far fa-star"></i></span>
                                <span class="star" data-value="4"><i class="far fa-star"></i></span>
                                <span class="star" data-value="5"><i class="far fa-star"></i></span>
                            </div>
                            <div class="rating-value" id="rating-value">0/5</div>
                            <input type="hidden" id="afinidad" name="afinidad" value="0" data-progress="5">
                        </div>
                    </div>
                    
                    <!-- Zona -->
                    <div class="form-group">
                        <label class="form-label" for="zona">
                            <i class="fas fa-map"></i> Zona
                        </label>
                        <select id="zona" name="zona" class="form-select" data-progress="3">
                            <option value="">Seleccione una zona</option>
                            <!-- Opciones vendrán de la base de datos -->
                        </select>
                    </div>
                    
                    <!-- Sector -->
                    <div class="form-group">
                        <label class="form-label" for="sector">
                            <i class="fas fa-th"></i> Sector
                        </label>
                        <select id="sector" name="sector" class="form-select" data-progress="3">
                            <option value="">Seleccione un sector</option>
                            <!-- Opciones vendrán de la base de datos -->
                        </select>
                    </div>
                    
                    <!-- Puesto de Votación -->
                    <div class="form-group">
                        <label class="form-label" for="puesto_votacion">
                            <i class="fas fa-vote-yea"></i> Puesto de Votación
                        </label>
                        <select id="puesto_votacion" name="puesto_votacion" class="form-select" data-progress="3">
                            <option value="">Seleccione un puesto</option>
                            <!-- Opciones vendrán de la base de datos -->
                        </select>
                    </div>
                    
                    <!-- Mesa -->
                    <div class="form-group">
                        <label class="form-label" for="mesa">
                            <i class="fas fa-users"></i> Mesa (Máx. 30)
                        </label>
                        <div class="input-with-icon">
                            <input type="number" 
                                   id="mesa" 
                                   name="mesa" 
                                   class="form-control" 
                                   placeholder="Número de mesa"
                                   min="1"
                                   max="30"
                                   data-progress="3">
                            <span class="input-suffix" onclick="abrirConsultaCenso()" title="Consultar censo electoral">
                                <i class="fas fa-search"></i>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Departamento -->
                    <div class="form-group">
                        <label class="form-label" for="departamento">
                            <i class="fas fa-landmark"></i> Departamento
                        </label>
                        <select id="departamento" name="departamento" class="form-select" data-progress="3">
                            <option value="">Seleccione un departamento</option>
                            <!-- Opciones vendrán de la base de datos -->
                        </select>
                    </div>
                    
                    <!-- Municipio -->
                    <div class="form-group">
                        <label class="form-label" for="municipio">
                            <i class="fas fa-city"></i> Municipio
                        </label>
                        <select id="municipio" name="municipio" class="form-select" data-progress="3">
                            <option value="">Seleccione un municipio</option>
                            <!-- Opciones vendrán de la base de datos -->
                        </select>
                    </div>
                    
                    <!-- Apoyo -->
                    <div class="form-group">
                        <label class="form-label" for="apoyo">
                            <i class="fas fa-handshake"></i> Nivel de Apoyo
                        </label>
                        <select id="apoyo" name="apoyo" class="form-select" data-progress="3">
                            <option value="">Seleccione nivel de apoyo</option>
                            <option value="Alto">Alto</option>
                            <option value="Medio">Medio</option>
                            <option value="Bajo">Bajo</option>
                            <option value="Ninguno">Ninguno</option>
                        </select>
                    </div>
                    
                    <!-- Grupo Poblacional -->
                    <div class="form-group">
                        <label class="form-label" for="grupo_poblacional">
                            <i class="fas fa-users"></i> Grupo Poblacional
                        </label>
                        <select id="grupo_poblacional" name="grupo_poblacional" class="form-select" data-progress="3">
                            <option value="">Seleccione grupo</option>
                            <option value="Jóvenes">Jóvenes</option>
                            <option value="Adultos">Adultos</option>
                            <option value="Adultos Mayores">Adultos Mayores</option>
                            <option value="Mujeres">Mujeres</option>
                            <option value="LGBTIQ+">LGBTIQ+</option>
                            <option value="Afrodescendientes">Afrodescendientes</option>
                            <option value="Indígenas">Indígenas</option>
                            <option value="Personas con discapacidad">Personas con discapacidad</option>
                        </select>
                    </div>
                    
                    <!-- Botón de Envío -->
                    <div class="form-group full-width">
                        <button type="submit" class="submit-btn" id="submit-btn">
                            <i class="fas fa-save"></i> Grabar Registro
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="system-footer">
        <div class="container">
            <p>© Derechos de autor Reservados. 
                <strong>Ing. Rubén Darío González García</strong> • 
                SISGONTech • Colombia © • <?php echo date('Y'); ?>
            </p>
            <p>Contacto: <strong>+57 3106310227</strong> • 
                Email: <strong>sisgonnet@gmail.com</strong>
            </p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/rateYo/2.3.2/jquery.rateyo.min.js"></script>
    
    <script>
        // Variables para el progreso
        let totalProgress = 0;
        const maxProgress = 100;
        const progressElements = document.querySelectorAll('[data-progress]');
        const progressFill = document.getElementById('progress-fill');
        const progressPercentage = document.getElementById('progress-percentage');
        
        // Sistema de rating
        const stars = document.querySelectorAll('.star');
        const ratingValue = document.getElementById('rating-value');
        const afinidadInput = document.getElementById('afinidad');
        let currentRating = 0;
        
        // Inicializar rating
        stars.forEach(star => {
            star.addEventListener('mouseover', function() {
                const value = parseInt(this.getAttribute('data-value'));
                highlightStars(value);
            });
            
            star.addEventListener('mouseout', function() {
                highlightStars(currentRating);
            });
            
            star.addEventListener('click', function() {
                const value = parseInt(this.getAttribute('data-value'));
                currentRating = value;
                afinidadInput.value = value;
                ratingValue.textContent = value + '/5';
                updateProgress();
                
                // Marcar estrellas seleccionadas
                stars.forEach((s, index) => {
                    if (index < value) {
                        s.innerHTML = '<i class="fas fa-star"></i>';
                        s.classList.add('selected');
                    } else {
                        s.innerHTML = '<i class="far fa-star"></i>';
                        s.classList.remove('selected');
                    }
                });
            });
        });
        
        function highlightStars(value) {
            stars.forEach((star, index) => {
                if (index < value) {
                    star.innerHTML = '<i class="fas fa-star"></i>';
                    star.classList.add('hover');
                } else {
                    star.innerHTML = '<i class="far fa-star"></i>';
                    star.classList.remove('hover');
                }
            });
        }
        
        // Actualizar progreso
        function updateProgress() {
            let filledProgress = 0;
            
            progressElements.forEach(element => {
                if (element.type === 'hidden' || element.tagName === 'SELECT' || element.tagName === 'INPUT') {
                    const progressValue = parseInt(element.getAttribute('data-progress'));
                    
                    if (element.type === 'hidden' && element.value !== '0') {
                        filledProgress += progressValue;
                    } else if (element.tagName === 'SELECT' && element.value !== '') {
                        filledProgress += progressValue;
                    } else if (element.value && element.value.trim() !== '') {
                        filledProgress += progressValue;
                    }
                }
            });
            
            totalProgress = Math.min(filledProgress, maxProgress);
            const percentage = Math.round((totalProgress / maxProgress) * 100);
            
            progressFill.style.width = percentage + '%';
            progressPercentage.textContent = percentage + '%';
        }
        
        // Escuchar cambios en los campos
        document.querySelectorAll('input, select').forEach(element => {
            element.addEventListener('input', updateProgress);
            element.addEventListener('change', updateProgress);
        });
        
        // Abrir consulta de censo
        function abrirConsultaCenso() {
            window.open('https://consultacenso.registraduria.gov.co/consultar/', '_blank');
        }
        
        // Manejar envío del formulario
        document.getElementById('referenciacion-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submit-btn');
            const originalText = submitBtn.innerHTML;
            
            // Validación básica
            const requiredFields = ['nombre', 'apellido', 'cedula', 'direccion', 'email', 'telefono'];
            let isValid = true;
            let errorMessage = '';
            
            requiredFields.forEach(field => {
                const element = document.getElementById(field);
                if (!element.value.trim()) {
                    isValid = false;
                    errorMessage = 'Por favor complete todos los campos obligatorios (*)';
                    element.focus();
                }
            });
            
            if (!isValid) {
                showNotification(errorMessage, 'error');
                return;
            }
            
            // Validar cédula (solo números)
            const cedula = document.getElementById('cedula').value;
            if (!/^\d+$/.test(cedula)) {
                showNotification('La cédula solo debe contener números', 'error');
                return;
            }
            
            // Validar email
            const email = document.getElementById('email').value;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showNotification('Por favor ingrese un email válido', 'error');
                return;
            }
            
            // Validar afinidad
            if (currentRating === 0) {
                showNotification('Por favor seleccione el nivel de afinidad', 'error');
                return;
            }
            
            // Cambiar estado del botón
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            submitBtn.disabled = true;
            
            try {
                // Aquí iría la llamada AJAX para guardar en la base de datos
                // Por ahora simulamos una respuesta exitosa
                
                // Simular tiempo de procesamiento
                await new Promise(resolve => setTimeout(resolve, 1500));
                
                // Éxito
                showNotification('Registro guardado exitosamente', 'success');
                
                // Resetear formulario
                this.reset();
                
                // Resetear rating
                currentRating = 0;
                afinidadInput.value = '0';
                ratingValue.textContent = '0/5';
                stars.forEach(star => {
                    star.innerHTML = '<i class="far fa-star"></i>';
                    star.classList.remove('selected', 'hover');
                });
                
                // Resetear progreso
                totalProgress = 0;
                progressFill.style.width = '0%';
                progressPercentage.textContent = '0%';
                
            } catch (error) {
                showNotification('Error al guardar el registro: ' + error.message, 'error');
            } finally {
                // Restaurar botón
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });
        
        // Mostrar notificaciones
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
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                <span>${message}</span>
                <button class="btn-close" onclick="this.parentElement.remove()"></button>
            `;
            
            document.body.appendChild(notification);
            
            // Auto-eliminar después de 5 segundos
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 5000);
        }
        
        // Cargar combos desde base de datos (simulación)
        document.addEventListener('DOMContentLoaded', function() {
            // Simular carga de datos para combos
            const comboSelectors = ['#zona', '#sector', '#puesto_votacion', '#departamento', '#municipio'];
            
            comboSelectors.forEach(selector => {
                const select = document.querySelector(selector);
                if (select) {
                    // Simular carga (en producción esto sería una llamada AJAX)
                    setTimeout(() => {
                        // Por ahora agregamos opciones de ejemplo
                        if (selector === '#zona') {
                            ['Zona Norte', 'Zona Sur', 'Zona Este', 'Zona Oeste', 'Zona Centro'].forEach(zona => {
                                const option = document.createElement('option');
                                option.value = zona;
                                option.textContent = zona;
                                select.appendChild(option);
                            });
                        } else if (selector === '#sector') {
                            ['Sector A', 'Sector B', 'Sector C', 'Sector D', 'Sector E'].forEach(sector => {
                                const option = document.createElement('option');
                                option.value = sector;
                                option.textContent = sector;
                                select.appendChild(option);
                            });
                        } else if (selector === '#departamento') {
                            ['Antioquia', 'Bogotá D.C.', 'Valle del Cauca', 'Cundinamarca', 'Santander'].forEach(depto => {
                                const option = document.createElement('option');
                                option.value = depto;
                                option.textContent = depto;
                                select.appendChild(option);
                            });
                        }
                    }, 500);
                }
            });
            
            // Validar número de mesa
            const mesaInput = document.getElementById('mesa');
            if (mesaInput) {
                mesaInput.addEventListener('change', function() {
                    const value = parseInt(this.value);
                    if (value < 1) this.value = 1;
                    if (value > 30) this.value = 30;
                });
            }
        });
    </script>
</body>
</html>