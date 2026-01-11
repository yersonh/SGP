<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    // Si no hay sesión, redirigir directamente al login
    header('Location: index.php');
    exit();
}

// Obtener datos del usuario para mostrar en el mensaje
$nombre_usuario = isset($_SESSION['nombres']) ? $_SESSION['nombres'] . ' ' . $_SESSION['apellidos'] : 'Usuario';

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
    <title>Cerrar Sesión - Sistema de Gestión de Referenciación</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #333;
        }
        
        .logout-container {
            background: linear-gradient(145deg, #ffffff, #f8f9fa);
            border-radius: 20px;
            padding: 50px 40px;
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.2),
                0 0 0 1px rgba(255, 255, 255, 0.1);
            max-width: 500px;
            width: 100%;
            text-align: center;
            position: relative;
            overflow: hidden;
            animation: fadeIn 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .logout-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #ff6b6b, #ff8e53, #ffd166);
        }
        
        .logout-icon {
            font-size: 80px;
            color: #ff6b6b;
            margin-bottom: 25px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .logout-title {
            color: #2c3e50;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 15px;
            letter-spacing: -0.5px;
        }
        
        .user-info {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 12px 20px;
            border-radius: 12px;
            margin: 20px auto;
            max-width: 300px;
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }
        
        .user-info i {
            margin-right: 10px;
            color: #a3e4d7;
        }
        
        .logout-message {
            color: #5d6d7e;
            line-height: 1.6;
            margin-bottom: 35px;
            font-size: 16px;
            padding: 0 10px;
        }
        
        .logout-actions {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 30px;
        }
        
        .btn {
            padding: 15px 35px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 16px;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            min-width: 180px;
        }
        
        .btn-logout {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
            box-shadow: 0 8px 20px rgba(255, 107, 107, 0.3);
        }
        
        .btn-logout:hover {
            background: linear-gradient(135deg, #ee5a52, #d64541);
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(255, 107, 107, 0.4);
            color: white;
        }
        
        .btn-cancel {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
            box-shadow: 0 8px 20px rgba(46, 204, 113, 0.3);
        }
        
        .btn-cancel:hover {
            background: linear-gradient(135deg, #27ae60, #219653);
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(46, 204, 113, 0.4);
            color: white;
        }
        
        .security-note {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eaeaea;
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .security-note i {
            color: #3498db;
            margin-right: 8px;
        }
        
        @media (max-width: 576px) {
            .logout-container {
                padding: 30px 20px;
            }
            
            .logout-actions {
                flex-direction: column;
                gap: 15px;
            }
            
            .btn {
                width: 100%;
                min-width: auto;
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
            Estás a punto de salir del <strong>Sistema de Gestión de Referenciación</strong>. 
            Si cierras sesión, deberás ingresar tus credenciales nuevamente para acceder.
        </p>
        
        <div class="logout-actions">
            <a href="logout.php?confirm=true" class="btn btn-logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>Sí, Cerrar Sesión</span>
            </a>
            
            <a href="referenciador.php" class="btn btn-cancel">
                <i class="fas fa-times"></i>
                <span>Cancelar y Volver</span>
            </a>
        </div>
        
        <div class="security-note">
            <i class="fas fa-shield-alt"></i>
            <span>Tu sesión se cerrará de forma segura en todos los dispositivos</span>
        </div>
    </div>
    
    <script>
        // Efecto sutil al cargar
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.querySelector('.logout-container');
            container.style.opacity = '0';
            container.style.transform = 'translateY(30px)';
            
            setTimeout(() => {
                container.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                container.style.opacity = '1';
                container.style.transform = 'translateY(0)';
            }, 100);
            
            // Prevenir doble clic en botones
            document.querySelectorAll('.btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
                    this.style.pointerEvents = 'none';
                    
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.style.pointerEvents = 'auto';
                    }, 2000);
                });
            });
        });
    </script>
</body>
</html>