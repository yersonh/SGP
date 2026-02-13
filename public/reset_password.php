<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UsuarioModel.php';

$pdo = Database::getConnection();
$usuarioModel = new UsuarioModel($pdo);

$error = '';
$success = '';
$token_valido = false;
$usuario_id = null;

// Obtener token de la URL
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $error = 'Enlace inválido o expirado';
} else {
    // Verificar token en base de datos
    $stmt = $pdo->prepare("
        SELECT id_usuario 
        FROM usuario 
        WHERE codigo_recuperacion = ? 
        AND expiracion_codigo > NOW()
    ");
    
    $stmt->execute([$token]);
    $resultado = $stmt->fetch();
    
    if ($resultado) {
        $token_valido = true;
        $usuario_id = $resultado['id_usuario'];
    } else {
        $error = 'Enlace inválido o expirado. Solicita uno nuevo.';
    }
}

// Procesar cambio de contraseña - GUARDAR COMO TEXTO PLANO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valido) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($password) || strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres';
    } elseif ($password !== $confirm_password) {
        $error = 'Las contraseñas no coinciden';
    } else {
        // ACTUALIZACIÓN: Guardar como texto plano (SIN HASH)
        $stmt = $pdo->prepare("
            UPDATE usuario 
            SET password = ?, 
                codigo_recuperacion = NULL, 
                expiracion_codigo = NULL,
                intentos_recuperacion = 0,
                ultimo_registro = NOW()
            WHERE id_usuario = ?
        ");
        
        // Guardamos la contraseña en texto plano directamente
        if ($stmt->execute([$password, $usuario_id])) {
            $success = '✅ Contraseña cambiada exitosamente';
            
            // Redirigir después de 3 segundos
            header('Refresh: 3; URL=index.php');
        } else {
            $error = 'Error al cambiar la contraseña';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Contraseña - SGP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: 
                linear-gradient(rgba(0, 0, 0, 0.8), rgba(0, 0, 0, 0.8)),
                url('/imagenes/fondo.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .password-card {
            background: rgba(30, 30, 40, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 
                0 15px 35px rgba(0, 0, 0, 0.5),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            padding: 40px;
            max-width: 500px;
            width: 100%;
            color: #fff;
        }
        
        .key-icon {
            color: #4fc3f7;
            font-size: 3rem;
            text-align: center;
            margin-bottom: 20px;
            text-shadow: 0 0 10px rgba(79, 195, 247, 0.3);
        }
        
        h2 {
            color: #4fc3f7;
            text-align: center;
            margin-bottom: 30px;
            font-weight: 600;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            backdrop-filter: blur(5px);
        }
        
        .alert-danger {
            background: rgba(244, 67, 54, 0.15);
            color: #ff8a80;
            border-left: 3px solid #ff5252;
        }
        
        .alert-success {
            background: rgba(76, 175, 80, 0.15);
            color: #a5d6a7;
            border-left: 3px solid #4caf50;
        }
        
        .form-control {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: #4fc3f7;
            color: #fff;
            box-shadow: 0 0 0 3px rgba(79, 195, 247, 0.2);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #4CAF50, #2E7D32);
            border: none;
            border-radius: 10px;
            padding: 13px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
            color: white;
        }
        
        .btn-success:hover {
            background: linear-gradient(135deg, #2E7D32, #1B5E20);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
        }
        
        .password-match {
            font-size: 0.85rem;
            margin-top: 5px;
        }
        
        @media (max-width: 480px) {
            .password-card {
                padding: 25px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="password-card">
        <div class="key-icon">
            <i class="fas fa-key"></i>
        </div>
        
        <h2>Nueva Contraseña</h2>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger mb-4">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                <?php if (strpos($error, 'expirado') !== false): ?>
                    <div class="mt-2">
                        <a href="recuperar.php" class="text-decoration-none" style="color: #4fc3f7;">
                            <i class="fas fa-redo"></i> Solicitar nuevo enlace
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success text-center">
                <h4 class="mb-3"><?php echo $success; ?></h4>
                <p class="mb-0">Serás redirigido al inicio de sesión en 3 segundos...</p>
                <div class="spinner-border text-success mt-3" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
            </div>
        <?php elseif ($token_valido): ?>
            <form method="POST" id="passwordForm">
                <div class="mb-3">
                    <label for="password" class="form-label mb-2">Nueva Contraseña</label>
                    <div class="input-group">
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="form-control" 
                               minlength="6"
                               required
                               autocomplete="new-password">
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword" style="background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.2); color: #fff;">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="form-text mt-1" style="color: #90a4ae;">Mínimo 6 caracteres</div>
                </div>
                
                <div class="mb-4">
                    <label for="confirm_password" class="form-label mb-2">Confirmar Contraseña</label>
                    <input type="password" 
                           id="confirm_password" 
                           name="confirm_password" 
                           class="form-control" 
                           minlength="6"
                           required
                           autocomplete="new-password">
                    <div class="password-match mt-1" id="passwordMatch" style="color: #90a4ae;"></div>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Cambiar Contraseña
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary" style="border-color: rgba(255,255,255,0.2); color: #b0bec5;">
                        Cancelar
                    </a>
                </div>
            </form>
        <?php else: ?>
            <div class="text-center">
                <p class="text-muted mb-4">No se puede mostrar el formulario de cambio de contraseña.</p>
                <a href="recuperar.php" class="btn btn-primary" style="background: linear-gradient(135deg, #4fc3f7, #29b6f6); border: none;">
                    <i class="fas fa-redo"></i> Solicitar nuevo enlace
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Mostrar/ocultar contraseña
        document.getElementById('togglePassword')?.addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                icon.className = 'fas fa-eye';
            }
        });
        
        // Validar que las contraseñas coincidan
        const form = document.getElementById('passwordForm');
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const matchText = document.getElementById('passwordMatch');
        
        function validatePasswords() {
            if (password.value && confirmPassword.value) {
                if (password.value === confirmPassword.value) {
                    matchText.innerHTML = '<span style="color: #4CAF50;"><i class="fas fa-check"></i> Las contraseñas coinciden</span>';
                    return true;
                } else {
                    matchText.innerHTML = '<span style="color: #f44336;"><i class="fas fa-times"></i> Las contraseñas no coinciden</span>';
                    return false;
                }
            }
            return null;
        }
        
        password?.addEventListener('input', validatePasswords);
        confirmPassword?.addEventListener('input', validatePasswords);
        
        // Validar antes de enviar
        form?.addEventListener('submit', function(e) {
            if (!validatePasswords()) {
                e.preventDefault();
                alert('Las contraseñas no coinciden');
            }
        });
    </script>
</body>
</html>