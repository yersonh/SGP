<?php
session_start();
require_once __DIR__ . '/../config/database.php';
header('Content-Type: text/html; charset=utf-8');

$pdo = Database::getConnection();
$error = '';

// Procesar login si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nickname = trim($_POST['nickname'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (!empty($nickname) && !empty($password)) {
        try {
            // Buscar usuario por nickname
            $stmt = $pdo->prepare("SELECT * FROM usuario WHERE nickname = ? AND activo = 1");
            $stmt->execute([$nickname]);
            $usuario = $stmt->fetch();
            
            if ($usuario && password_verify($password, $usuario['password'])) {
                // Login exitoso
                $_SESSION['id_usuario'] = $usuario['id_usuario'];
                $_SESSION['nickname'] = $usuario['nickname'];
                $_SESSION['tipo_usuario'] = $usuario['tipo_usuario'];
                $_SESSION['login_time'] = time();
                
                // Actualizar último registro
                $fecha_actual = date('Y-m-d H:i:s');
                $stmt_update = $pdo->prepare("UPDATE usuario SET ultimo_registro = ? WHERE id_usuario = ?");
                $stmt_update->execute([$fecha_actual, $usuario['id_usuario']]);
                
                // Redirigir a dashboard o vista de usuarios
                header('Location: dashboard.php');
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
    <title>SGP - Sistema de Gestión Personal</title>
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
            max-width: 450px;
            padding: 40px;
            animation: fadeIn 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .logo-section {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo {
            font-size: 3rem;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .logo-section h1 {
            color: #2c3e50;
            font-size: 1.8rem;
            margin-bottom: 5px;
        }
        
        .logo-section p {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-with-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #7f8c8d;
            font-size: 1.1rem;
        }
        
        .input-with-icon input {
            width: 100%;
            padding: 14px 14px 14px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
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
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            border-left: 4px solid #c0392b;
            display: <?php echo $error ? 'block' : 'none'; ?>;
        }
        
        .login-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
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
            font-size: 1.2rem;
        }
        
        .footer-links {
            margin-top: 25px;
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .footer-links a {
            color: #3498db;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: #2980b9;
            text-decoration: underline;
        }
        
        .system-info {
            margin-top: 25px;
            text-align: center;
            font-size: 0.8rem;
            color: #95a5a6;
            line-height: 1.4;
        }
        
        @media (max-width: 480px) {
            .login-container {
                padding: 25px;
            }
            
            .logo {
                font-size: 2.5rem;
            }
            
            .logo-section h1 {
                font-size: 1.5rem;
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
            <h1>Sistema de Gestión Personal</h1>
            <p>Acceso restringido al personal autorizado</p>
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
            <p>© <?php echo date('Y'); ?> Sistema de Gestión Personal - Versión 1.0</p>
            <p>Use credenciales válidas para acceder al sistema</p>
        </div>
    </div>

    <script>
        // Efecto de enfoque en campos
        document.addEventListener('DOMContentLoaded', function() {
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
            
            // Mostrar/ocultar contraseña (opcional, puedes agregar un icono)
            // Podrías agregar un icono de ojo para mostrar/ocultar contraseña
        });
    </script>
</body>
</html>