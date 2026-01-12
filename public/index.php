<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
header('Content-Type: text/html; charset=utf-8');

$pdo = Database::getConnection();
$model = new UsuarioModel($pdo);
$error = '';
if (isset($_GET['logout']) && $_GET['logout'] == 'success') {
    $logout_message = "Sesión cerrada exitosamente";
}
// Procesar login si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nickname = trim($_POST['nickname'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (!empty($nickname) && !empty($password)) {
        try {
            // Usar el modelo para verificar credenciales
            $usuario = $model->verificarCredenciales($nickname, $password);
            
            if ($usuario) {
                // Login exitoso
                $_SESSION['id_usuario'] = $usuario['id_usuario'];
                $_SESSION['nickname'] = $usuario['nickname'];
                $_SESSION['tipo_usuario'] = $usuario['tipo_usuario'];
                $_SESSION['login_time'] = time();
                
                // Actualizar último registro usando el modelo
                $fecha_actual = date('Y-m-d H:i:s');
                $model->actualizarUltimoRegistro($usuario['id_usuario'], $fecha_actual);
                
                // REDIRIGIR SEGÚN EL TIPO DE USUARIO
                switch ($usuario['tipo_usuario']) {
                    case 'Administrador':
                        header('Location: dashboard.php');
                        break;
                    case 'Referenciador':
                        header('Location: referenciador.php');
                        break;
                    default:
                        // Redirigir a una vista por defecto o mostrar error
                        header('Location: dashboard.php');
                        break;
                }
                exit();
            } else {
                $error = 'Credenciales incorrectas o usuario inactivo';
            }
        } catch (Exception $e) {
            $error = 'Error al procesar la solicitud';
            error_log("Error login: " . $e->getMessage());
        }
    } else {
        $error = 'Por favor complete todos los campos';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGP</title>
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
                linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)),
                url('/imagenes/fondo.jpg') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
            width: 100%;
            max-width: 420px;
            padding: 35px 30px;
            animation: fadeIn 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .logo-section {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .logo {
            font-size: 2.8rem;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        
        .logo-section h1 {
            color: #2c3e50;
            font-size: 1.6rem;
            margin-bottom: 8px;
            line-height: 1.2;
        }
        
        .logo-section p {
            color: #7f8c8d;
            font-size: 0.85rem;
            line-height: 1.4;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 6px;
            color: #2c3e50;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-with-icon i.fa-user,
        .input-with-icon i.fa-lock {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #7f8c8d;
            font-size: 1rem;
            z-index: 2;
        }
        
        .input-with-icon .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #7f8c8d;
            cursor: pointer;
            padding: 5px;
            font-size: 0.9rem;
            z-index: 2;
        }
        
        .input-with-icon input {
            width: 100%;
            padding: 12px 40px 12px 35px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s;
            background-color: #f9f9f9;
        }
        
        .input-with-icon input:focus {
            outline: none;
            border-color: #3498db;
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        
        .error-message {
            background-color: #fee;
            color: #c0392b;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 0.85rem;
            border-left: 4px solid #c0392b;
            display: <?php echo $error ? 'block' : 'none'; ?>;
        }
        
        .login-btn {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }
        
        .login-btn:hover {
            background: linear-gradient(135deg, #2980b9, #1c5a80);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }
        
        .login-btn:active {
            transform: translateY(0);
        }
        
        .login-btn i {
            font-size: 1.1rem;
        }
        
        .footer-links {
            margin-top: 20px;
            text-align: center;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .footer-links a {
            color: #3498db;
            text-decoration: none;
            font-size: 0.85rem;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: #2980b9;
            text-decoration: underline;
        }
        
        .system-info {
            margin-top: 20px;
            text-align: center;
            font-size: 0.75rem;
            color: #95a5a6;
            line-height: 1.4;
        }
        
        .system-info p {
            margin-bottom: 5px;
        }
        
        @media (max-width: 480px) {
            .login-container {
                padding: 25px 20px;
                max-width: 350px;
            }
            
            .logo {
                font-size: 2.5rem;
            }
            
            .logo-section h1 {
                font-size: 1.4rem;
            }
            
            .logo-section p {
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-section">
            <div class="logo">
                <i class="fas fa-user-shield"></i>
            </div>
            <h1>SGP-Sistema de Gestión de Política</h1>
            <p>Plataforma de confirmación de usuario autorizado para acceso al software SGP.</p>
        </div>
        
        <?php if ($error): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="nickname">Usuario</label>
                <div class="input-with-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" 
                           id="nickname" 
                           name="nickname" 
                           placeholder="Ingrese su nombre de usuario" 
                           required
                           autocomplete="username"
                           value="<?php echo isset($_POST['nickname']) ? htmlspecialchars($_POST['nickname']) : ''; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña</label>
                <div class="input-with-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           placeholder="Ingrese su contraseña" 
                           required
                           autocomplete="current-password">
                    <button type="button" class="toggle-password" id="togglePassword">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <button type="submit" class="login-btn">
                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
            </button>
        </form>
        
        <div class="footer-links">
            <a href="#"><i class="fas fa-question-circle"></i> ¿Olvidó su contraseña?</a>
        </div>
        
        <div class="system-info">
            <p>© Derechos de autor Reservados. 
            Ing. Rubén Darío González García • 
            SISGONTech • Colombia © • <?php echo date('Y'); ?>
            </p>
            <p>Contacto: +57 3106310227 • 
            Email: sisgonnet@gmail.com
            </p>
        </div>
    </div>

    <script>
        // Mostrar/ocultar contraseña
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const togglePasswordBtn = document.getElementById('togglePassword');
            
            togglePasswordBtn.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
            });
            
            // Efecto de enfoque en campos
            const inputs = document.querySelectorAll('input');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                
                input.addEventListener('blur', function() {
                    if (!this.value) {
                        this.parentElement.classList.remove('focused');
                    }
                });
            });
        });
    </script>
</body>
</html>