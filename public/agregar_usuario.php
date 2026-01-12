<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UsuarioModel.php';

// Verificar permisos (solo administradores pueden agregar usuarios)
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'Administrador') {
    header('Location: index.php');
    exit();
}

$pdo = Database::getConnection();
$usuarioModel = new UsuarioModel($pdo);

// Cargar datos para los combos desde la base de datos directamente
$zonas = [];
$sectores = [];
$puestos = [];

try {
    // Cargar zonas
    $stmt = $pdo->query("SELECT id_zona as id, nombre FROM zonas ORDER BY nombre");
    $zonas = $stmt->fetchAll();
    
    // Cargar sectores
    $stmt = $pdo->query("SELECT id_sector as id, nombre FROM sectores ORDER BY nombre");
    $sectores = $stmt->fetchAll();
    
    // Cargar puestos
    $stmt = $pdo->query("SELECT id_puesto as id, nombre FROM puestos_votacion ORDER BY nombre");
    $puestos = $stmt->fetchAll();
} catch (Exception $e) {
    // Si hay error, dejar arrays vacíos
    error_log("Error cargando datos para combos: " . $e->getMessage());
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
            max-width: 1200px;
            padding: 40px;
            animation: fadeIn 0.5s ease-out;
            margin-top: 80px;
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
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
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
        
        .form-control, .form-select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.3s;
            background: rgba(255, 255, 255, 0.05);
            color: #ffffff;
        }
        
        .form-control::placeholder {
            color: #90a4ae;
            opacity: 0.7;
        }
        
        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: #4fc3f7;
            background: rgba(255, 255, 255, 0.08);
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
        
        .photo-section {
            grid-column: 1 / -1;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .photo-upload-container {
            display: inline-block;
            text-align: center;
        }
        
        .photo-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 4px solid rgba(79, 195, 247, 0.3);
            margin: 0 auto 15px;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .photo-preview:hover {
            border-color: #4fc3f7;
            transform: scale(1.05);
            box-shadow: 0 0 20px rgba(79, 195, 247, 0.3);
        }
        
        .photo-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .photo-placeholder {
            color: #90a4ae;
            font-size: 3rem;
        }
        
        .photo-upload-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(79, 195, 247, 0.2);
            color: #4fc3f7;
            border: 1px solid rgba(79, 195, 247, 0.3);
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            backdrop-filter: blur(5px);
        }
        
        .photo-upload-btn:hover {
            background: rgba(79, 195, 247, 0.3);
            transform: translateY(-2px);
            border-color: #4fc3f7;
        }
        
        .phone-input {
            position: relative;
        }
        
        .phone-prefix {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #90a4ae;
            font-weight: 500;
            z-index: 2;
        }
        
        .phone-input .form-control {
            padding-left: 45px;
        }
        
        .password-strength {
            height: 4px;
            background: rgba(255, 255, 255, 0.1);
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
            background: #ff6b6b;
            width: 33%;
        }
        
        .password-strength.medium .password-strength-fill {
            background: #ffd166;
            width: 66%;
        }
        
        .password-strength.strong .password-strength-fill {
            background: #06d6a0;
            width: 100%;
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
        
        .system-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(30, 30, 40, 0.9);
            color: #78909c;
            text-align: center;
            padding: 15px;
            font-size: 0.8rem;
            backdrop-filter: blur(10px);
            z-index: 1000;
        }
        
        .system-footer p {
            margin: 3px 0;
            opacity: 0.8;
        }
        
        .password-match {
            font-size: 0.85rem;
            margin-top: 5px;
            display: block;
        }
        
        .password-match.valid {
            color: #06d6a0;
        }
        
        .password-match.invalid {
            color: #ff6b6b;
        }
        
        .field-hint {
            color: #90a4ae;
            font-size: 0.8rem;
            margin-top: 5px;
            display: block;
            opacity: 0.8;
        }
        
        @media (max-width: 768px) {
            .form-container {
                padding: 25px;
                margin-top: 100px;
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
            
            .photo-preview {
                width: 120px;
                height: 120px;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            
            .form-container {
                padding: 20px;
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
                    <h1><i class="fas fa-user-plus"></i> Agregar Nuevo Usuario</h1>
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span>Administrador</span>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="dashboard.php" class="header-btn">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                    <a href="logout.php" class="header-btn">
                        <i class="fas fa-sign-out-alt"></i> Salir
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Form -->
    <div class="form-container">
        <div class="form-header">
            <h2><i class="fas fa-user-cog"></i> Registro de Nuevo Usuario</h2>
            <p>Complete todos los campos para registrar un nuevo usuario en el sistema</p>
        </div>
        
        <form id="usuario-form">
            <!-- Foto de perfil -->
            <div class="form-group full-width photo-section">
                <div class="photo-upload-container">
                    <div class="photo-preview" id="photoPreview">
                        <div class="photo-placeholder">
                            <i class="fas fa-user"></i>
                        </div>
                        <img id="photoImage" src="" style="display: none;">
                    </div>
                    <input type="file" id="foto" name="foto" accept="image/*" style="display: none;">
                    <button type="button" class="photo-upload-btn" id="uploadPhotoBtn">
                        <i class="fas fa-camera"></i> Subir Foto de Perfil
                    </button>
                </div>
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
                </div>
                
                <!-- Nickname -->
                <div class="form-group">
                    <label class="form-label" for="nickname">
                        <i class="fas fa-at"></i> Nombre de Usuario *
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
                    <span class="field-hint">Debe ser único en el sistema (mínimo 4 caracteres)</span>
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
                    <span class="field-hint">Debe ser único en el sistema</span>
                </div>
                
                <!-- Teléfono -->
                <div class="form-group">
                    <label class="form-label" for="telefono">
                        <i class="fas fa-phone"></i> Teléfono *
                    </label>
                    <div class="phone-input input-with-icon">
                        <i class="fas fa-phone input-icon"></i>
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
                    <span class="field-hint">10 dígitos (ej: 3001234567)</span>
                </div>
                
                <!-- Zona -->
                <div class="form-group">
                    <label class="form-label" for="zona">
                        <i class="fas fa-map"></i> Zona
                    </label>
                    <select id="zona" name="zona" class="form-select">
                        <option value="">Seleccione una zona</option>
                        <?php foreach ($zonas as $zona): ?>
                        <option value="<?php echo htmlspecialchars($zona['id']); ?>">
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
                        <option value="<?php echo htmlspecialchars($sector['id']); ?>">
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
                        <option value="<?php echo htmlspecialchars($puesto['id']); ?>">
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
                    <span class="field-hint">Número máximo de referenciados permitidos</span>
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
                    <span class="field-hint">Mínimo 6 caracteres</span>
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
                    <span id="passwordMatch" class="password-match"></span>
                </div>
                
                <!-- Botones -->
                <div class="form-group full-width form-actions">
                    <a href="dashboard.php" class="cancel-btn">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                    <button type="submit" class="submit-btn" id="submit-btn">
                        <i class="fas fa-user-plus"></i> Registrar Usuario
                    </button>
                </div>
            </div>
            
            <div class="form-footer">
                <p><i class="fas fa-info-circle"></i> Los campos marcados con * son obligatorios</p>
                <p><i class="fas fa-shield-alt"></i> Todos los datos se almacenan de forma segura</p>
            </div>
        </form>
    </div>

    <!-- Footer -->
    <footer class="system-footer">
        <div class="container">
            <p>SGP - Sistema de Gestión de Política</p>
            <p>© Derechos de autor Reservados • Ing. Rubén Darío González García • SISGONTech • Colombia © • <?php echo date('Y'); ?></p>
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
            
            // Formato de cédula (solo números)
            const cedulaInput = document.getElementById('cedula');
            cedulaInput.addEventListener('input', function(e) {
                // Remover caracteres no numéricos
                e.target.value = e.target.value.replace(/\D/g, '');
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
                    passwordMatch.className = 'password-match';
                } else if (password === confirmPassword) {
                    passwordMatch.textContent = '✓ Las contraseñas coinciden';
                    passwordMatch.className = 'password-match valid';
                } else {
                    passwordMatch.textContent = '✗ Las contraseñas no coinciden';
                    passwordMatch.className = 'password-match invalid';
                }
            }
            
            passwordInput.addEventListener('input', validatePasswordMatch);
            confirmPasswordInput.addEventListener('input', validatePasswordMatch);
            
            // Validar formulario antes de enviar
            const usuarioForm = document.getElementById('usuario-form');
            const submitBtn = document.getElementById('submit-btn');
            
            usuarioForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Validar cédula
                const cedula = cedulaInput.value;
                if (cedula.length < 6 || cedula.length > 10) {
                    alert('La cédula debe tener entre 6 y 10 dígitos.');
                    cedulaInput.focus();
                    return;
                }
                
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
                    alert('✅ Usuario registrado exitosamente (simulación)');
                    usuarioForm.reset();
                    photoImage.src = '';
                    photoImage.style.display = 'none';
                    photoPreview.querySelector('.photo-placeholder').style.display = 'block';
                    passwordStrength.style.display = 'none';
                    passwordMatch.textContent = '';
                    passwordMatch.className = 'password-match';
                    
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