<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../models/ZonaModel.php';
require_once __DIR__ . '/../models/SectorModel.php';
require_once __DIR__ . '/../models/PuestoVotacionModel.php';

// Verificar permisos (solo administradores pueden agregar usuarios)
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'Administrador') {
    header('Location: index.php');
    exit();
}

$pdo = Database::getConnection();
$usuarioModel = new UsuarioModel($pdo);

// Cargar datos para los combos (si existen los modelos)
$zonas = [];
$sectores = [];
$puestos = [];

try {
    $zonaModel = new ZonaModel($pdo);
    $zonas = $zonaModel->getAll();
    
    $sectorModel = new SectorModel($pdo);
    $sectores = $sectorModel->getAll();
    
    $puestoModel = new PuestoModel($pdo);
    $puestos = $puestoModel->getAll();
} catch (Exception $e) {
    // Si los modelos no existen, dejar arrays vacíos
}

// Tipos de usuario permitidos
$tipos_usuario = ['Administrador', 'Referenciador', 'Descargador', 'SuperAdmin'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Usuario - SGP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            min-height: 100vh;
            color: #333;
        }
        
        .main-header {
            background: linear-gradient(135deg, #2c3e50, #1a252f);
            color: white;
            padding: 15px 0;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }
        
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
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
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.1);
            padding: 8px 15px;
            border-radius: 20px;
            backdrop-filter: blur(5px);
        }
        
        .logout-btn {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            transition: all 0.3s;
        }
        
        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .main-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .form-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            animation: fadeIn 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .form-header h2 {
            color: #2c3e50;
            font-size: 1.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .form-header h2 i {
            color: #3498db;
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
            color: #2c3e50;
            font-weight: 600;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-label i {
            color: #7f8c8d;
            font-size: 1rem;
        }
        
        .form-control, .form-select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s;
            background-color: white;
        }
        
        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #7f8c8d;
            font-size: 1rem;
        }
        
        .input-with-icon .form-control {
            padding-left: 40px;
        }
        
        .photo-upload-container {
            grid-column: 1 / -1;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .photo-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 4px solid #e0e0e0;
            margin: 0 auto 15px;
            overflow: hidden;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .photo-preview:hover {
            border-color: #3498db;
            transform: scale(1.05);
        }
        
        .photo-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .photo-placeholder {
            color: #95a5a6;
            font-size: 3rem;
        }
        
        .photo-upload-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
        }
        
        .photo-upload-btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        
        .phone-input {
            position: relative;
        }
        
        .phone-prefix {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #7f8c8d;
            font-weight: 500;
            pointer-events: none;
        }
        
        .phone-input .form-control {
            padding-left: 45px;
        }
        
        .password-strength {
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
        }
        
        .password-strength-fill {
            height: 100%;
            width: 0%;
            transition: all 0.3s;
            border-radius: 2px;
        }
        
        .password-strength.weak .password-strength-fill {
            background: #e74c3c;
            width: 33%;
        }
        
        .password-strength.medium .password-strength-fill {
            background: #f39c12;
            width: 66%;
        }
        
        .password-strength.strong .password-strength-fill {
            background: #27ae60;
            width: 100%;
        }
        
        .submit-btn {
            background: linear-gradient(135deg, #27ae60, #219653);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
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
        
        .form-footer {
            margin-top: 30px;
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        .system-footer {
            background: #2c3e50;
            color: white;
            text-align: center;
            padding: 20px;
            margin-top: 40px;
        }
        
        .system-footer .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .system-footer p {
            margin: 5px 0;
            opacity: 0.9;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .form-card {
                padding: 20px;
            }
            
            .header-title h1 {
                font-size: 1.3rem;
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
                    <h1><i class="fas fa-user-plus"></i> Agregar Nuevo Usuario</h1>
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span>Administrador</span>
                    </div>
                </div>
                <div>
                    <a href="dashboard.php" class="logout-btn" style="margin-right: 10px;">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
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
                <h2><i class="fas fa-user-cog"></i> Información del Nuevo Usuario</h2>
                <p style="color: #7f8c8d; margin-top: 10px;">Complete todos los campos obligatorios (*) para registrar un nuevo usuario</p>
            </div>
            
            <form id="usuario-form">
                <!-- Foto de perfil -->
                <div class="photo-upload-container">
                    <div class="photo-preview" id="photoPreview">
                        <div class="photo-placeholder">
                            <i class="fas fa-user"></i>
                        </div>
                        <img id="photoImage" src="" style="display: none;">
                    </div>
                    <input type="file" id="foto" name="foto" accept="image/*" style="display: none;">
                    <button type="button" class="photo-upload-btn" id="uploadPhotoBtn">
                        <i class="fas fa-camera"></i> Subir Foto
                    </button>
                </div>
                
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
                    
                    <!-- Nickname -->
                    <div class="form-group">
                        <label class="form-label" for="nickname">
                            <i class="fas fa-at"></i> Nickname *
                        </label>
                        <div class="input-with-icon">
                            <i class="fas fa-at input-icon"></i>
                            <input type="text" 
                                   id="nickname" 
                                   name="nickname" 
                                   class="form-control" 
                                   placeholder="Ingrese el nombre de usuario"
                                   required
                                   autocomplete="off"
                                   minlength="4">
                        </div>
                        <small style="color: #7f8c8d; font-size: 0.85rem; display: block; margin-top: 5px;">
                            Debe ser único en el sistema
                        </small>
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
                    
                    <!-- Teléfono -->
                    <div class="form-group">
                        <label class="form-label" for="telefono">
                            <i class="fas fa-phone"></i> Teléfono *
                        </label>
                        <div class="phone-input">
                            <span class="phone-prefix">+57</span>
                            <input type="tel" 
                                   id="telefono" 
                                   name="telefono" 
                                   class="form-control" 
                                   placeholder="300 1234567"
                                   required
                                   maxlength="10"
                                   pattern="[0-9]{10}"
                                   title="Ingrese un número de teléfono válido (10 dígitos)"
                                   autocomplete="off">
                        </div>
                        <small style="color: #7f8c8d; font-size: 0.85rem; display: block; margin-top: 5px;">
                            Formato: 10 dígitos (ej: 3001234567)
                        </small>
                    </div>
                    
                    <!-- Zona -->
                    <div class="form-group">
                        <label class="form-label" for="zona">
                            <i class="fas fa-map"></i> Zona
                        </label>
                        <select id="zona" name="zona" class="form-select">
                            <option value="">Seleccione una zona</option>
                            <?php foreach ($zonas as $zona): ?>
                            <option value="<?php echo htmlspecialchars($zona['id_zona'] ?? $zona['id']); ?>">
                                <?php echo htmlspecialchars($zona['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Sector -->
                    <div class="form-group">
                        <label class="form-label" for="sector">
                            <i class="fas fa-th"></i> Sector
                        </label>
                        <select id="sector" name="sector" class="form-select">
                            <option value="">Seleccione un sector</option>
                            <?php foreach ($sectores as $sector): ?>
                            <option value="<?php echo htmlspecialchars($sector['id_sector'] ?? $sector['id']); ?>">
                                <?php echo htmlspecialchars($sector['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Puesto -->
                    <div class="form-group">
                        <label class="form-label" for="puesto">
                            <i class="fas fa-building"></i> Puesto
                        </label>
                        <select id="puesto" name="puesto" class="form-select">
                            <option value="">Seleccione un puesto</option>
                            <?php foreach ($puestos as $puesto): ?>
                            <option value="<?php echo htmlspecialchars($puesto['id_puesto'] ?? $puesto['id']); ?>">
                                <?php echo htmlspecialchars($puesto['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Tope -->
                    <div class="form-group">
                        <label class="form-label" for="tope">
                            <i class="fas fa-chart-line"></i> Tope
                        </label>
                        <div class="input-with-icon">
                            <i class="fas fa-chart-line input-icon"></i>
                            <input type="number" 
                                   id="tope" 
                                   name="tope" 
                                   class="form-control" 
                                   placeholder="Ej: 100"
                                   min="0"
                                   step="1"
                                   autocomplete="off">
                        </div>
                        <small style="color: #7f8c8d; font-size: 0.85rem; display: block; margin-top: 5px;">
                            Número máximo de referenciados permitidos
                        </small>
                    </div>
                    
                    <!-- Tipo de Usuario -->
                    <div class="form-group">
                        <label class="form-label" for="tipo_usuario">
                            <i class="fas fa-user-tag"></i> Tipo de Usuario *
                        </label>
                        <select id="tipo_usuario" name="tipo_usuario" class="form-select" required>
                            <option value="">Seleccione un tipo</option>
                            <?php foreach ($tipos_usuario as $tipo): ?>
                            <option value="<?php echo htmlspecialchars($tipo); ?>">
                                <?php echo htmlspecialchars($tipo); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Contraseña -->
                    <div class="form-group">
                        <label class="form-label" for="password">
                            <i class="fas fa-lock"></i> Contraseña *
                        </label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   class="form-control" 
                                   placeholder="Ingrese la contraseña"
                                   required
                                   minlength="6"
                                   autocomplete="new-password">
                        </div>
                        <div class="password-strength" id="passwordStrength">
                            <div class="password-strength-fill"></div>
                        </div>
                        <small style="color: #7f8c8d; font-size: 0.85rem; display: block; margin-top: 5px;">
                            Mínimo 6 caracteres
                        </small>
                    </div>
                    
                    <!-- Confirmar Contraseña -->
                    <div class="form-group">
                        <label class="form-label" for="confirm_password">
                            <i class="fas fa-lock"></i> Confirmar Contraseña *
                        </label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" 
                                   id="confirm_password" 
                                   name="confirm_password" 
                                   class="form-control" 
                                   placeholder="Confirme la contraseña"
                                   required
                                   autocomplete="new-password">
                        </div>
                        <small id="passwordMatch" style="font-size: 0.85rem; display: block; margin-top: 5px;"></small>
                    </div>
                    
                    <!-- Botón de Envío -->
                    <div class="form-group full-width">
                        <button type="submit" class="submit-btn" id="submit-btn">
                            <i class="fas fa-user-plus"></i> Registrar Usuario
                        </button>
                    </div>
                </div>
                
                <div class="form-footer">
                    <p><i class="fas fa-info-circle"></i> Los campos marcados con * son obligatorios</p>
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

    <!-- JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Foto de perfil
            const photoPreview = document.getElementById('photoPreview');
            const photoInput = document.getElementById('foto');
            const photoImage = document.getElementById('photoImage');
            const uploadPhotoBtn = document.getElementById('uploadPhotoBtn');
            
            // Evento para abrir el selector de archivos
            photoPreview.addEventListener('click', function() {
                photoInput.click();
            });
            
            uploadPhotoBtn.addEventListener('click', function() {
                photoInput.click();
            });
            
            // Mostrar imagen seleccionada
            photoInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        photoImage.src = e.target.result;
                        photoImage.style.display = 'block';
                        photoPreview.querySelector('.photo-placeholder').style.display = 'none';
                    };
                    reader.readAsDataURL(file);
                }
            });
            
            // Formato de teléfono (Colombia)
            const telefonoInput = document.getElementById('telefono');
            telefonoInput.addEventListener('input', function(e) {
                // Remover caracteres no numéricos
                let value = e.target.value.replace(/\D/g, '');
                
                // Limitar a 10 dígitos
                if (value.length > 10) {
                    value = value.substring(0, 10);
                }
                
                // Formatear: 300 1234567
                if (value.length > 3) {
                    value = value.substring(0, 3) + ' ' + value.substring(3);
                }
                
                e.target.value = value;
            });
            
            // Validar fortaleza de contraseña
            const passwordInput = document.getElementById('password');
            const passwordStrength = document.getElementById('passwordStrength');
            
            passwordInput.addEventListener('input', function(e) {
                const password = e.target.value;
                let strength = 0;
                
                // Longitud
                if (password.length >= 6) strength++;
                if (password.length >= 8) strength++;
                
                // Caracteres mixtos
                if (/[a-z]/.test(password)) strength++;
                if (/[A-Z]/.test(password)) strength++;
                if (/[0-9]/.test(password)) strength++;
                if (/[^A-Za-z0-9]/.test(password)) strength++;
                
                // Actualizar indicador visual
                passwordStrength.className = 'password-strength';
                if (password.length === 0) {
                    passwordStrength.style.display = 'none';
                } else {
                    passwordStrength.style.display = 'block';
                    if (strength <= 2) {
                        passwordStrength.classList.add('weak');
                    } else if (strength <= 4) {
                        passwordStrength.classList.add('medium');
                    } else {
                        passwordStrength.classList.add('strong');
                    }
                }
            });
            
            // Validar coincidencia de contraseñas
            const confirmPasswordInput = document.getElementById('confirm_password');
            const passwordMatch = document.getElementById('passwordMatch');
            
            function validatePasswordMatch() {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                
                if (confirmPassword.length === 0) {
                    passwordMatch.textContent = '';
                    passwordMatch.style.color = '';
                } else if (password === confirmPassword) {
                    passwordMatch.textContent = '✓ Las contraseñas coinciden';
                    passwordMatch.style.color = '#27ae60';
                } else {
                    passwordMatch.textContent = '✗ Las contraseñas no coinciden';
                    passwordMatch.style.color = '#e74c3c';
                }
            }
            
            passwordInput.addEventListener('input', validatePasswordMatch);
            confirmPasswordInput.addEventListener('input', validatePasswordMatch);
            
            // Validar formulario antes de enviar
            const usuarioForm = document.getElementById('usuario-form');
            const submitBtn = document.getElementById('submit-btn');
            
            usuarioForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Validar contraseñas
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                
                if (password !== confirmPassword) {
                    alert('Las contraseñas no coinciden. Por favor verifique.');
                    confirmPasswordInput.focus();
                    return;
                }
                
                // Validar fortaleza de contraseña
                if (password.length < 6) {
                    alert('La contraseña debe tener al menos 6 caracteres.');
                    passwordInput.focus();
                    return;
                }
                
                // Validar teléfono
                const telefono = telefonoInput.value.replace(/\s/g, '');
                if (telefono.length !== 10) {
                    alert('El teléfono debe tener 10 dígitos.');
                    telefonoInput.focus();
                    return;
                }
                
                // Mostrar estado de carga
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registrando...';
                submitBtn.disabled = true;
                
                // Simular envío (por ahora solo diseño)
                setTimeout(() => {
                    alert('Usuario registrado exitosamente (simulación)');
                    usuarioForm.reset();
                    photoImage.src = '';
                    photoImage.style.display = 'none';
                    photoPreview.querySelector('.photo-placeholder').style.display = 'block';
                    passwordStrength.style.display = 'none';
                    passwordMatch.textContent = '';
                    
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 1500);
            });
            
            // Inicializar
            passwordStrength.style.display = 'none';
        });
    </script>
</body>
</html>