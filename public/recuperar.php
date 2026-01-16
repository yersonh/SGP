<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UsuarioModel.php';

$pdo = Database::getConnection();
$usuarioModel = new UsuarioModel($pdo);

$error = '';
$success = '';
$limite_intentos = 3; // Máximo 3 intentos por hora

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nickname = trim($_POST['nickname'] ?? '');
    
    if (empty($nickname)) {
        $error = 'Por favor ingrese su nombre de usuario';
    } else {
        // Buscar usuario por nickname
        $usuario = $usuarioModel->getUsuarioByNickname($nickname);
        
        if ($usuario) {
            // Verificar límite de intentos
            $stmt = $pdo->prepare("SELECT intentos_recuperacion, ultimo_intento FROM usuario WHERE id_usuario = ?");
            $stmt->execute([$usuario['id_usuario']]);
            $datos = $stmt->fetch();
            
            $intentos = $datos['intentos_recuperacion'] ?? 0;
            $ultimo_intento = $datos['ultimo_intento'] ?? null;
            
            // Reiniciar intentos si han pasado más de 1 hora
            if ($ultimo_intento && strtotime($ultimo_intento) < strtotime('-1 hour')) {
                $intentos = 0;
            }
            
            if ($intentos >= $limite_intentos) {
                $error = 'Has excedido el límite de intentos. Espera 1 hora o contacta al administrador.';
            } elseif (empty($usuario['correo'])) {
                $error = 'Tu cuenta no tiene un correo electrónico registrado. Contacta al administrador.';
            } else {
                // Incrementar intentos
                $stmt = $pdo->prepare("UPDATE usuario SET intentos_recuperacion = intentos_recuperacion + 1, ultimo_intento = NOW() WHERE id_usuario = ?");
                $stmt->execute([$usuario['id_usuario']]);
                
                // GENERAR token único y seguro
                $token = bin2hex(random_bytes(32)); // Token de 64 caracteres
                $expiracion = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                
                // Guardar token en la base de datos
                $stmt = $pdo->prepare("UPDATE usuario SET codigo_recuperacion = ?, expiracion_codigo = ? WHERE id_usuario = ?");
                $stmt->execute([$token, $expiracion, $usuario['id_usuario']]);
                
                // Crear URL única y segura
                $protocolo = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
                $url_segura = $protocolo . "://" . $_SERVER['HTTP_HOST'] . "/reset_password.php?token=" . urlencode($token);
                
                // ENVIAR CORREO CON BREVO
                $email_enviado = enviarCorreoBrevo(
                    $usuario['correo'],
                    $usuario['nombres'] ?? $usuario['nickname'],
                    $usuario['nickname'],
                    $url_segura
                );
                
                if ($email_enviado) {
                    // Mostrar confirmación al usuario
                    $success = "✅ <strong>Correo enviado exitosamente</strong><br><br>";
                    $success .= "Se ha enviado un correo electrónico a:<br>";
                    $success .= "<strong>📧 Correo:</strong> " . htmlspecialchars($usuario['correo']) . "<br>";
                    $success .= "<strong>👤 Usuario:</strong> " . htmlspecialchars($usuario['nickname']) . "<br><br>";
                    
                    $success .= "🔍 <strong>Si no recibes el correo:</strong><br>";
                    $success .= "1. Revisa tu carpeta de spam o correo no deseado<br>";
                    $success .= "2. Verifica que el correo " . htmlspecialchars($usuario['correo']) . " es correcto<br>";
                    $success .= "3. Espera unos minutos y vuelve a intentar<br><br>";
                    
                    $success .= "⚠️ <strong>El enlace expira en 30 minutos</strong>";
                } else {
                    $error = 'Error al enviar el correo. Por favor, intenta nuevamente o contacta al administrador.';
                }
            }
        } else {
            $error = 'No se encontró un usuario con ese nombre';
        }
    }
}

/**
 * Función para enviar correo usando Brevo API
 */
function enviarCorreoBrevo($destinatario, $nombre, $usuario, $url_recuperacion) {
    // Obtener credenciales de Railway
    $brevo_api_key = getenv('BREVO_API_KEY');
    $remitente_email = getenv('SMTP_FROM') ?: 'no-reply@sgp-system.com';
    $remitente_nombre = getenv('SMTP_FROM_NAME') ?: 'Soporte SGP';
    
    if (!$brevo_api_key) {
        error_log("❌ Error: BREVO_API_KEY no configurada en Railway");
        return false;
    }
    
    // Contenido HTML del correo
    $html_content = '
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Recuperación de Contraseña</title>
        <style>
            body { 
                font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; 
                line-height: 1.6; 
                color: #333; 
                margin: 0; 
                padding: 0; 
                background: #f5f7fa;
            }
            .container { 
                max-width: 600px; 
                margin: 20px auto; 
                background: white; 
                border-radius: 10px; 
                overflow: hidden; 
                box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            }
            .header { 
                background: linear-gradient(135deg, #2c3e50, #1a252f); 
                color: white; 
                padding: 30px 20px; 
                text-align: center;
            }
            .header h1 { 
                margin: 0; 
                font-size: 24px; 
                display: flex; 
                align-items: center; 
                justify-content: center; 
                gap: 10px;
            }
            .content { 
                padding: 40px;
            }
            .greeting { 
                font-size: 18px; 
                margin-bottom: 20px; 
                color: #2c3e50;
            }
            .message { 
                margin-bottom: 30px; 
                color: #555;
            }
            .button-container { 
                text-align: center; 
                margin: 40px 0;
            }
            .recovery-button { 
                display: inline-block; 
                background: linear-gradient(135deg, #4fc3f7, #29b6f6); 
                color: white; 
                padding: 15px 30px; 
                text-decoration: none; 
                border-radius: 8px; 
                font-weight: 600; 
                font-size: 16px; 
                transition: all 0.3s; 
                box-shadow: 0 4px 15px rgba(79, 195, 247, 0.3);
            }
            .recovery-button:hover { 
                background: linear-gradient(135deg, #29b6f6, #0288d1);
            }
            .warning-box { 
                background: #fff8e1; 
                border-left: 4px solid #ffb300; 
                padding: 20px; 
                margin: 30px 0; 
                border-radius: 4px;
            }
            .url-box { 
                background: #f8f9fa; 
                padding: 15px; 
                border-radius: 6px; 
                margin: 20px 0; 
                font-family: "Courier New", monospace; 
                word-break: break-all; 
                font-size: 14px; 
                color: #2c3e50;
            }
            .details { 
                background: #f8fafc; 
                padding: 20px; 
                border-radius: 6px; 
                margin-top: 30px;
            }
            .footer { 
                text-align: center; 
                padding: 20px; 
                color: #666; 
                font-size: 0.9rem; 
                border-top: 1px solid #eee;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🔐 Recuperación de Contraseña</h1>
                <p>Sistema SGP - Gestión de Política</p>
            </div>
            
            <div class="content">
                <div class="greeting">
                    Hola <strong>' . htmlspecialchars($nombre) . '</strong>,
                </div>
                
                <div class="message">
                    Has solicitado recuperar tu contraseña en el Sistema SGP. 
                    Haz clic en el botón a continuación para crear una nueva contraseña.
                </div>
                
                <div class="button-container">
                    <a href="' . htmlspecialchars($url_recuperacion) . '" class="recovery-button">
                        🔗 Cambiar mi Contraseña
                    </a>
                </div>
                
                <div class="warning-box">
                    <strong>⚠️ Importante:</strong>
                    <ul>
                        <li>Este enlace es válido por <strong>30 minutos</strong></li>
                        <li>Solo se puede usar <strong>una vez</strong></li>
                        <li>Si no solicitaste este cambio, ignora este correo</li>
                        <li>Nadie del equipo de SGP te pedirá esta contraseña</li>
                    </ul>
                </div>
                
                <p>Si el botón no funciona, copia y pega este enlace en tu navegador:</p>
                <div class="url-box">' . htmlspecialchars($url_recuperacion) . '</div>
                
                <div class="details">
                    <p><strong>📋 Detalles de la solicitud:</strong></p>
                    <ul>
                        <li><strong>Usuario:</strong> ' . htmlspecialchars($usuario) . '</li>
                        <li><strong>Fecha y hora:</strong> ' . date('d/m/Y H:i:s') . '</li>
                        <li><strong>Dirección IP:</strong> ' . ($_SERVER['REMOTE_ADDR'] ?? 'No disponible') . '</li>
                    </ul>
                </div>
            </div>
            
            <div class="footer">
                <p>© ' . date('Y') . ' SGP - Sistema de Gestión de Política</p>
                <p><em>Este es un correo automático, por favor no responder.</em></p>
                <p>Si necesitas ayuda adicional, contacta al administrador del sistema.</p>
            </div>
        </div>
    </body>
    </html>
    ';
    
    // Contenido en texto plano (para clientes de email que no soportan HTML)
    $text_content = "RECUPERACIÓN DE CONTRASEÑA - SGP\n\n";
    $text_content .= "Hola " . $nombre . ",\n\n";
    $text_content .= "Has solicitado recuperar tu contraseña en el Sistema SGP.\n\n";
    $text_content .= "Para cambiar tu contraseña, haz clic en el siguiente enlace:\n";
    $text_content .= $url_recuperacion . "\n\n";
    $text_content .= "IMPORTANTE:\n";
    $text_content .= "- Este enlace es válido por 30 minutos\n";
    $text_content .= "- Solo se puede usar una vez\n";
    $text_content .= "- Si no solicitaste este cambio, ignora este correo\n\n";
    $text_content .= "Detalles de la solicitud:\n";
    $text_content .= "- Usuario: " . $usuario . "\n";
    $text_content .= "- Fecha: " . date('d/m/Y H:i:s') . "\n";
    $text_content .= "- IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'No disponible') . "\n\n";
    $text_content .= "© " . date('Y') . " SGP - Sistema de Gestión de Política\n";
    $text_content .= "Este es un correo automático, por favor no responder.\n";
    
    // Preparar datos para la API de Brevo
    $data = [
        'sender' => [
            'name' => $remitente_nombre,
            'email' => $remitente_email
        ],
        'to' => [
            [
                'email' => $destinatario,
                'name' => $nombre
            ]
        ],
        'subject' => 'Recuperación de Contraseña - SGP',
        'htmlContent' => $html_content,
        'textContent' => $text_content,
        'headers' => [
            'X-Mailin-custom' => 'custom_header_1:custom_value_1'
        ]
    ];
    
    // Configurar cURL para Brevo API
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.brevo.com/v3/smtp/email',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'accept: application/json',
            'api-key: ' . $brevo_api_key,
            'content-type: application/json'
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    
    // Ejecutar la petición
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    // Logging (útil para debugging en Railway)
    error_log("📧 Brevo API - Destinatario: " . $destinatario);
    error_log("📧 Brevo API - Código HTTP: " . $http_code);
    
    if ($error) {
        error_log("❌ Error Brevo: " . $error);
        return false;
    }
    
    // Verificar respuesta
    if ($http_code === 201) {
        error_log("✅ Correo enviado exitosamente a: " . $destinatario);
        return true;
    } else {
        $response_data = json_decode($response, true);
        $error_msg = $response_data['message'] ?? 'Error desconocido';
        error_log("❌ Error Brevo ($http_code): " . $error_msg);
        return false;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - SGP</title>
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
        
        .recovery-card {
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
            animation: fadeIn 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .email-icon {
            color: #4fc3f7;
            font-size: 3rem;
            text-align: center;
            margin-bottom: 20px;
            text-shadow: 0 0 10px rgba(79, 195, 247, 0.3);
        }
        
        h2 {
            color: #4fc3f7;
            text-align: center;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .subtitle {
            color: #b0bec5;
            text-align: center;
            margin-bottom: 30px;
            font-size: 0.9rem;
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
        
        .form-control::placeholder {
            color: #90a4ae;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4fc3f7, #29b6f6);
            border: none;
            border-radius: 10px;
            padding: 13px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
            color: #1a237e;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #29b6f6, #0288d1);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(79, 195, 247, 0.4);
        }
        
        .btn-email {
            background: #4fc3f7;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 10px;
            text-decoration: none;
            display: inline-block;
            margin-top: 10px;
            transition: all 0.3s;
            font-weight: 600;
        }
        
        .btn-email:hover {
            background: #29b6f6;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(79, 195, 247, 0.3);
        }
        
        .email-action {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link {
            color: #4fc3f7;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-top: 20px;
        }
        
        .back-link:hover {
            color: #81d4fa;
            text-decoration: none;
        }
        
        .security-info {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
            border-left: 3px solid #4fc3f7;
        }
        
        @media (max-width: 480px) {
            .recovery-card {
                padding: 25px 20px;
                backdrop-filter: blur(8px);
                -webkit-backdrop-filter: blur(8px);
            }
            
            .email-icon {
                font-size: 2.5rem;
            }
            
            h2 {
                font-size: 1.4rem;
            }
        }
    </style>
</head>
<body>
    <div class="recovery-card">
        <div class="email-icon">
            <i class="fas fa-envelope"></i>
        </div>
        
        <h2>Recuperar Contraseña</h2>
        <p class="subtitle">Recibirás un enlace seguro por correo electrónico</p>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger mb-4">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
                <hr class="my-3">
                <a href="index.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Volver al inicio de sesión
                </a>
            </div>
        <?php else: ?>
            <div class="security-info">
                <i class="fas fa-shield-alt"></i>
                <strong>Seguridad:</strong> El enlace se enviará al correo registrado en tu cuenta.
                Es válido por 30 minutos y solo se puede usar una vez.
            </div>
            
            <form method="POST" id="recoveryForm">
                <div class="mb-4">
                    <label for="nickname" class="form-label mb-2">Nombre de Usuario</label>
                    <input type="text" 
                           id="nickname" 
                           name="nickname" 
                           class="form-control" 
                           placeholder="Ingresa tu nombre de usuario"
                           required
                           autofocus
                           value="<?php echo isset($_POST['nickname']) ? htmlspecialchars($_POST['nickname']) : ''; ?>">
                    <div class="form-text mt-1" style="color: #90a4ae;">
                        Ingresa el mismo usuario que usas para iniciar sesión
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i class="fas fa-envelope"></i> Recibir Enlace por Correo
                </button>
                
                <div class="text-center mt-3">
                    <a href="index.php" class="back-link">
                        <i class="fas fa-arrow-left"></i> Volver al inicio de sesión
                    </a>
                </div>
            </form>
        <?php endif; ?>
    </div>
    
    <script>
        // Prevenir múltiples clics en el botón
        document.getElementById('recoveryForm')?.addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
                
                // Re-enable después de 30 segundos por si hay error
                setTimeout(() => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-envelope"></i> Recibir Enlace por Correo';
                }, 30000);
            }
        });
    </script>
</body>
</html>