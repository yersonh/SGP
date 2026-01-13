<?php
session_start();

// Agregar las mismas dependencias que en referenciador.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UsuarioModel.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    // Si no hay sesión, redirigir directamente al login
    header('Location: index.php');
    exit();
}

// CONEXIÓN A LA BASE DE DATOS Y OBTENCIÓN DEL USUARIO
$pdo = Database::getConnection();
$model = new UsuarioModel($pdo);
$id_usuario_logueado = $_SESSION['id_usuario'];

// Obtener datos del usuario logueado
$usuario_logueado = $model->getUsuarioById($id_usuario_logueado);

// Obtener datos del usuario para mostrar en el mensaje
$nombre_usuario = 'Usuario'; // Valor por defecto

if ($usuario_logueado && isset($usuario_logueado['nombres']) && isset($usuario_logueado['apellidos'])) {
    $nombre_usuario = $usuario_logueado['nombres'] . ' ' . $usuario_logueado['apellidos'];
} elseif (isset($_SESSION['nickname'])) {
    $nombre_usuario = $_SESSION['nickname'];
}

// Si se recibió confirmación de cerrar sesión
if (isset($_GET['confirm']) && $_GET['confirm'] == 'true') {
    // Registrar el cierre de sesión (opcional - para logs)
    $fecha = date('Y-m-d H:i:s');
    // Podrías guardar esto en una tabla de logs si quieres
    
    // Destruir completamente la sesión
    $_SESSION = array();
    
    // Borrar la cookie de sesión si existe
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Finalmente, destruir la sesión
    session_destroy();
    
    // Redirigir al login con mensaje de éxito
    header('Location: index.php?logout=success');
    exit();
}

// Si viene de cancelar, redirigir al referenciador
if (isset($_GET['cancel']) && $_GET['cancel'] == 'true') {
    header('Location: referenciador.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cerrar Sesión - SGP</title>
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
        
        .logout-container {
            background: rgba(30, 30, 40, 0.85); /* Fondo oscuro semi-transparente */
            backdrop-filter: blur(15px); /* Efecto de desenfoque tipo vidrio */
            -webkit-backdrop-filter: blur(15px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.5),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            width: 100%;
            max-width: 480px;
            padding: 40px 35px;
            animation: fadeIn 0.5s ease-out;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Efecto de brillo sutil en los bordes */
        .logout-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, 
                transparent, 
                rgba(255, 255, 255, 0.2), 
                transparent);
            border-radius: 20px 20px 0 0;
        }
        
        .logout-icon {
            font-size: 4.5rem;
            color: #ff6b6b; /* Rojo/anaranjado para cerrar sesión */
            margin-bottom: 20px;
            text-shadow: 0 0 15px rgba(255, 107, 107, 0.4);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.9; }
            100% { transform: scale(1); opacity: 1; }
        }
        
        .logout-title {
            color: #ffffff;
            font-size: 1.8rem;
            margin-bottom: 15px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        
        .user-info {
            background: rgba(79, 195, 247, 0.15); /* Azul con transparencia */
            color: #4fc3f7;
            padding: 12px 20px;
            border-radius: 10px;
            margin: 20px auto;
            max-width: 320px;
            font-weight: 600;
            border: 1px solid rgba(79, 195, 247, 0.3);
            backdrop-filter: blur(5px);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .user-info i {
            font-size: 1.1rem;
        }
        
        .logout-message {
            color: #b0bec5;
            line-height: 1.6;
            margin-bottom: 30px;
            font-size: 0.95rem;
            padding: 0 5px;
        }
        
        .logout-message strong {
            color: #ffffff;
            font-weight: 600;
        }
        
        .logout-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 10px;
        }
        
        .btn {
            padding: 14px 30px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            min-width: 160px;
            letter-spacing: 0.3px;
        }
        
        .btn-logout {
            background: linear-gradient(135deg, #ff6b6b, #ff8e53);
            color: #1a1a2e;
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.3);
            border: none;
        }
        
        .btn-logout:hover {
            background: linear-gradient(135deg, #ff8e53, #ff6b6b);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 107, 107, 0.4);
            color: #1a1a2e;
        }
        
        .btn-cancel {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(5px);
        }
        
        .btn-cancel:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
            border-color: rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }
        
        .security-note {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #90a4ae;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .security-note i {
            color: #4fc3f7;
        }
        
        .system-info {
            margin-top: 25px;
            text-align: center;
            font-size: 0.7rem;
            color: #78909c;
            line-height: 1.4;
        }
        
        .system-info p {
            margin-bottom: 5px;
            opacity: 0.8;
        }
        
        @media (max-width: 576px) {
            .logout-container {
                padding: 30px 25px;
                max-width: 380px;
            }
            
            .logout-icon {
                font-size: 4rem;
            }
            
            .logout-title {
                font-size: 1.5rem;
            }
            
            .logout-actions {
                flex-direction: column;
                gap: 12px;
            }
            
            .btn {
                width: 100%;
                min-width: auto;
                padding: 13px;
            }
            
            .user-info {
                max-width: 280px;
                padding: 10px 15px;
                font-size: 0.9rem;
            }
            
            .logout-message {
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 380px) {
            .logout-container {
                padding: 25px 20px;
                max-width: 320px;
            }
            
            .logout-icon {
                font-size: 3.5rem;
            }
            
            .logout-title {
                font-size: 1.3rem;
            }
            
            .logout-message {
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="logout-icon">
            <i class="fas fa-door-open"></i>
        </div>
        
        <h1 class="logout-title">¿Cerrar Sesión?</h1>
        
        <div class="user-info">
            <i class="fas fa-user-circle"></i>
            <span><?php echo htmlspecialchars($nombre_usuario); ?></span>
        </div>
        
        <p class="logout-message">
            Estás a punto de salir del <strong>Sistema de Gestión de Política</strong>. 
            Si cierras sesión, deberás ingresar tus credenciales nuevamente para acceder.
        </p>
        
        <div class="logout-actions">
            <a href="logout.php?confirm=true" class="btn btn-logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>Cerrar Sesión</span>
            </a>
            
            <button type="button" class="btn btn-cancel" id="btn-cancel">
                <i class="fas fa-arrow-left"></i>
                <span>Volver</span>
            </button>
        </div>
        
        <div class="security-note">
            <i class="fas fa-shield-alt"></i>
            <span>Tu sesión se cerrará de forma segura</span>
        </div>
        
        <div class="system-info">
            <p>SGP - Sistema de Gestión de Política</p>
            <p>© <?php echo date('Y'); ?> • Todos los derechos reservados</p>
        </div>
    </div>
    
    <script>
        // Efecto sutil al cargar
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.querySelector('.logout-container');
            container.style.opacity = '0';
            container.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                container.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                container.style.opacity = '1';
                container.style.transform = 'translateY(0)';
            }, 100);
            
            // Prevenir doble clic en botón de cerrar sesión
            const btnLogout = document.querySelector('.btn-logout');
            if (btnLogout) {
                btnLogout.addEventListener('click', function(e) {
                    const originalText = this.innerHTML;
                    
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cerrando sesión...';
                    this.style.pointerEvents = 'none';
                    this.style.opacity = '0.8';
                    
                    // Prevenir navegación rápida
                    e.preventDefault();
                    
                    setTimeout(() => {
                        window.location.href = this.href;
                    }, 1000);
                    
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.style.pointerEvents = 'auto';
                        this.style.opacity = '1';
                    }, 2000);
                });
            }
            
            // Botón para volver a la página anterior
            const btnCancel = document.getElementById('btn-cancel');
            if (btnCancel) {
                btnCancel.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Mostrar estado de carga
                    const originalHTML = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Regresando...';
                    this.disabled = true;
                    this.style.opacity = '0.7';
                    
                    // Verificar si hay historial de navegación
                    if (window.history.length > 1) {
                        // Usar history.back() para volver a la página anterior
                        setTimeout(() => {
                            window.history.back();
                        }, 300);
                    } else {
                        // Si no hay historial, redirigir según el tipo de usuario
                        setTimeout(() => {
                            // Por defecto redirigir al referenciador
                            window.location.href = 'referenciador.php';
                        }, 300);
                    }
                    
                    // Restaurar el botón después de 2 segundos (por si falla la redirección)
                    setTimeout(() => {
                        this.innerHTML = originalHTML;
                        this.disabled = false;
                        this.style.opacity = '1';
                    }, 2000);
                });
            }
        });
    </script>
</body>
</html>