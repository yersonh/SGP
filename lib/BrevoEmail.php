<?php
class BrevoEmail {
    private $apiKey;
    private $apiUrl = 'https://api.brevo.com/v3/smtp/email';
    
    public function __construct() {
        // Obtener API Key de las variables de entorno
        $this->apiKey = getenv('BREVO_API_KEY');
        
        // Verificar que la API Key esté configurada
        if (!$this->apiKey) {
            throw new Exception("API Key de Brevo no configurada");
        }
    }
    
    /**
     * Enviar correo de confirmación de registro (referenciado)
     */
    public function enviarConfirmacionRegistro($referido, $referenciador, $lider = null) {
    try {
        $fechaRegistro = $referido['fecha_registro'] ?? date('d/m/Y H:i:s');
        
        error_log("📅 Fecha a usar en correo: " . $fechaRegistro);
        
        // Determinar el nombre del líder
        $nombreLider = 'No asignado';
        if ($lider && isset($lider['nombres'])) {
            $nombreLider = $lider['nombres'] . ' ' . ($lider['apellidos'] ?? '');
        } elseif ($lider && isset($lider['nombre_completo'])) {
            $nombreLider = $lider['nombre_completo'];
        }
        
        // Preparar el correo
        $correoData = [
            'sender' => [
                'name' => getenv('SMTP_FROM_NAME') ?: 'Soporte - SGP',
                'email' => getenv('SMTP_FROM') ?: 'solanoalfonsoy@gmail.com'
            ],
            'to' => [
                [
                    'email' => $referido['email'],
                    'name' => $referido['nombre'] . ' ' . $referido['apellido']
                ]
            ],
            'subject' => 'Confirmación de Registro - Sistema de Gestión Política',
            'htmlContent' => $this->generarHTMLCorreo($referido, $referenciador, $lider, $fechaRegistro),
            'textContent' => $this->generarTextoCorreo($referido, $referenciador, $lider, $fechaRegistro),
            'params' => [
                'NOMBRE_COMPLETO' => $referido['nombre'] . ' ' . $referido['apellido'],
                'CEDULA' => $referido['cedula'],
                'EMAIL' => $referido['email'],
                'TELEFONO' => $referido['telefono'],
                'FECHA_REGISTRO' => $fechaRegistro,
                'REFERENCIADOR' => $referenciador['nombres'] . ' ' . $referenciador['apellidos'],
                'LIDER' => $nombreLider
            ]
        ];
        
        return $this->enviarAPI($correoData);
        
    } catch (Exception $e) {
        error_log("❌ Error Brevo: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}
    
    /**
     * Envía correo de confirmación de registro a un líder
     */
    public function enviarConfirmacionRegistroLider($lider, $administrador) {
    try {
        $fechaRegistro = $lider['fecha_registro'] ?? date('d/m/Y H:i:s');
        
        error_log("📅 Enviando correo de confirmación a líder: " . $lider['email']);
        
        $correoData = [
            'sender' => [
                'name' => getenv('SMTP_FROM_NAME') ?: 'Sistema de Gestión',
                'email' => getenv('SMTP_FROM') ?: 'solanoalfonsoy@gmail.com'
            ],
            'to' => [
                [
                    'email' => $lider['email'],
                    'name' => $lider['nombre'] . ' ' . $lider['apellido']
                ]
            ],
            'subject' => 'Has sido registrado como Líder - SGP', // Cambié el título para que sea menos "comercial"
            'htmlContent' => $this->generarHTMLCorreoLider($lider, $administrador, $fechaRegistro),
            'textContent' => $this->generarTextoCorreoLider($lider, $administrador, $fechaRegistro), // ¡AGREGADO!
            'replyTo' => [
                'email' => getenv('SMTP_FROM') ?: 'solanoalfonsoy@gmail.com',
                'name' => 'Soporte SGP'
            ],
            'params' => [
                'NOMBRE_COMPLETO' => $lider['nombre'] . ' ' . $lider['apellido'],
                'CEDULA' => $lider['cedula'],
                'EMAIL' => $lider['email'],
                'TELEFONO' => $lider['telefono'],
                'FECHA_REGISTRO' => $fechaRegistro,
                'COORDINADOR' => $lider['coordinador_nombre'] ?? 'No asignado',
                'COORDINADOR_EMAIL' => $lider['coordinador_email'] ?? 'No disponible',
                'COORDINADOR_TELEFONO' => $lider['coordinador_telefono'] ?? 'No disponible',
                'ADMINISTRADOR' => $administrador['nombres'] . ' ' . $administrador['apellidos']
            ]
        ];
        
        return $this->enviarAPI($correoData);
        
    } catch (Exception $e) {
        error_log('❌ Error en enviarConfirmacionRegistroLider: ' . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Agrega este método para generar texto plano
private function generarTextoCorreoLider($lider, $administrador, $fechaRegistro) {
    $nombreCompleto = $lider['nombre'] . ' ' . $lider['apellido'];
    $anio = date('Y');
    
    $texto = "Hola $nombreCompleto,\n\n";
    $texto .= "Has sido registrado exitosamente en el Sistema de Gestión de Proyectos (SGP) como LÍDER.\n\n";
    $texto .= "DATOS DE REGISTRO:\n";
    $texto .= "• Nombres: $nombreCompleto\n";
    $texto .= "• Cédula: " . $lider['cedula'] . "\n";
    $texto .= "• Teléfono: " . $lider['telefono'] . "\n";
    $texto .= "• Correo: " . $lider['email'] . "\n";
    $texto .= "• Fecha de registro: $fechaRegistro\n";
    
    if ($lider['coordinador_nombre'] !== 'No asignado') {
        $texto .= "• Coordinador asignado: " . $lider['coordinador_nombre'] . "\n";
        $texto .= "• Email coordinador: " . $lider['coordinador_email'] . "\n";
        $texto .= "• Tel. coordinador: " . $lider['coordinador_telefono'] . "\n";
    }
    
    $texto .= "\nCREDENCIALES DE ACCESO:\n";
    $texto .= "• Usuario: " . $lider['cedula'] . "\n";
    $texto .= "• Contraseña inicial: " . $lider['cedula'] . " (mismo número de cédula)\n\n";
    $texto .= "IMPORTANTE: Por seguridad, cambia tu contraseña en el primer inicio de sesión.\n\n";
    $texto .= "Accede al sistema: http://sgp-desarrollo-production.up.railway.app/login.php\n\n";
    $texto .= "Como líder podrás:\n";
    $texto .= "- Gestionar y hacer seguimiento de tus referenciados\n";
    $texto .= "- Generar reportes de gestión\n";
    $texto .= "- Coordinar actividades con tu equipo\n\n";
    $texto .= "Saludos cordiales,\n";
    $texto .= "Equipo SGP\n";
    $texto .= "© $anio Sistema de Gestión de Proyectos\n\n";
    $texto .= "-- \n";
    $texto .= "Este correo fue enviado por " . $administrador['nombres'] . " " . $administrador['apellidos'] . " (Administrador del sistema)";
    
    return $texto;
}
    
    /**
     * Envía notificación al referenciador sobre la asignación de un nuevo líder
     */
    public function enviarNotificacionAsignacionLider($lider, $referenciador, $administrador) {
        try {
            $fechaRegistro = $lider['fecha_registro'] ?? date('d/m/Y H:i:s');
            
            error_log("📅 Enviando notificación a coordinador: " . $referenciador['correo']);
            
            $correoData = [
                'sender' => [
                    'name' => getenv('SMTP_FROM_NAME') ?: 'Sistema de Gestión',
                    'email' => getenv('SMTP_FROM') ?: 'solanoalfonsoy@gmail.com'
                ],
                'to' => [
                    [
                        'email' => $referenciador['correo'],
                        'name' => $referenciador['nombres'] . ' ' . $referenciador['apellidos']
                    ]
                ],
                'subject' => 'Nuevo Líder asignado a tu equipo - SGP',
                'htmlContent' => $this->generarHTMLNotificacionCoordinador($lider, $referenciador, $administrador, $fechaRegistro),
                'params' => [
                    'LIDER_NOMBRE' => $lider['nombre'] . ' ' . $lider['apellido'],
                    'LIDER_CEDULA' => $lider['cedula'],
                    'LIDER_EMAIL' => $lider['email'],
                    'LIDER_TELEFONO' => $lider['telefono'],
                    'FECHA_ASIGNACION' => $fechaRegistro,
                    'COORDINADOR_NOMBRE' => $referenciador['nombres'] . ' ' . $referenciador['apellidos'],
                    'ADMINISTRADOR' => $administrador['nombres'] . ' ' . $administrador['apellidos']
                ]
            ];
            
            return $this->enviarAPI($correoData);
            
        } catch (Exception $e) {
            error_log('❌ Error en enviarNotificacionAsignacionLider: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
 * Generar contenido HTML del correo para referenciado
 */
private function generarHTMLCorreo($referido, $referenciador, $lider, $fechaRegistro) {
    // Determinar el nombre del líder
    $nombreLider = 'No asignado';
    if ($lider && isset($lider['nombres'])) {
        $nombreLider = $lider['nombres'] . ' ' . ($lider['apellidos'] ?? '');
    } elseif ($lider && isset($lider['nombre_completo'])) {
        $nombreLider = $lider['nombre_completo'];
    } elseif ($lider && isset($lider['id_lider'])) {
        // Si solo tenemos el ID pero no los datos, mostrar "Asignado"
        $nombreLider = 'Asignado (ID: ' . $lider['id_lider'] . ')';
    }
    
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Confirmación de Registro</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
            }
            .header {
                background: linear-gradient(135deg, #2c3e50, #3498db);
                color: white;
                padding: 20px;
                text-align: center;
                border-radius: 10px 10px 0 0;
            }
            .content {
                padding: 30px;
                background-color: #f9f9f9;
                border: 1px solid #ddd;
                border-top: none;
                border-radius: 0 0 10px 10px;
            }
            .info-box {
                background: white;
                border-left: 4px solid #3498db;
                padding: 15px;
                margin: 15px 0;
                border-radius: 4px;
            }
            .footer {
                text-align: center;
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #eee;
                color: #666;
                font-size: 12px;
            }
            .highlight {
                color: #2c3e50;
                font-weight: bold;
            }
            .logo {
                text-align: center;
                margin-bottom: 20px;
            }
            .logo-text {
                font-size: 24px;
                font-weight: bold;
                color: #2c3e50;
            }
            .badge-lider {
                display: inline-block;
                background-color: #27ae60;
                color: white;
                padding: 3px 8px;
                border-radius: 12px;
                font-size: 12px;
                font-weight: normal;
            }
        </style>
    </head>
    <body>
        <div class="logo">
            <div class="logo-text">SGP</div>
            <div style="color: #666; font-size: 14px;">Sistema de Gestión Política</div>
        </div>
        
        <div class="header">
            <h1>¡Registro Confirmado!</h1>
            <p>Gracias por ser parte de nuestro sistema</p>
        </div>
        
        <div class="content">
            <p>Estimado/a <span class="highlight">{{params.NOMBRE_COMPLETO}}</span>,</p>
            
            <p>Su registro en el Sistema de Gestión Política ha sido completado exitosamente.</p>
            
            <div class="info-box">
                <h3>Información de su registro:</h3>
                <p><strong>Cédula:</strong> {{params.CEDULA}}</p>
                <p><strong>Email:</strong> {{params.EMAIL}}</p>
                <p><strong>Teléfono:</strong> {{params.TELEFONO}}</p>
                <p><strong>Fecha de registro:</strong> {{params.FECHA_REGISTRO}}</p>
                <p><strong>Referenciador:</strong> {{params.REFERENCIADOR}}</p>
                <p><strong>Líder asignado:</strong> ' . htmlspecialchars($nombreLider) . '</p> <!-- NUEVO CAMPO -->
            </div>';
            
            // Si hay líder asignado, mostrar un mensaje adicional
            if ($lider && $nombreLider != 'No asignado') {
                $return .= '
                <div style="background-color: #e8f5e9; padding: 12px; border-radius: 6px; margin: 15px 0; border-left: 4px solid #27ae60;">
                    <p style="margin: 0;"><strong>📢 Información importante:</strong> Su líder asignado, <strong>' . htmlspecialchars($nombreLider) . '</strong>, estará a cargo de su acompañamiento y seguimiento en el proceso.</p>
                </div>';
            }
            
            $return .= '
            <p>Su información ha sido registrada en nuestra base de datos y será utilizada para mantenerle informado sobre actividades y eventos relevantes.</p>
            
            <p>Si tiene alguna pregunta o necesita actualizar sus datos, no dude en contactarnos.</p>
            
            <p>Saludos cordiales,<br>
            <strong>Equipo SGP</strong></p>
        </div>
        
        <div class="footer">
            <p>© ' . date('Y') . ' Sistema de Gestión Política - Todos los derechos reservados</p>
            <p>Este es un correo automático, por favor no responder a esta dirección.</p>
        </div>
    </body>
    </html>
    ';
}
    
    /**
     * Generar contenido HTML del correo para líder
     */
    private function generarHTMLCorreoLider($lider, $administrador, $fechaRegistro) {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Bienvenido Líder - SGP</title>
            <style>
                body {
                    font-family: "Segoe UI", Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    margin: 0;
                    padding: 0;
                }
                .container {
                    max-width: 600px;
                    margin: 20px auto;
                    background: #ffffff;
                    border-radius: 15px;
                    overflow: hidden;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                }
                .header {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 40px 30px;
                    text-align: center;
                }
                .header h1 {
                    margin: 0;
                    font-size: 28px;
                    font-weight: 600;
                }
                .header p {
                    margin: 10px 0 0;
                    opacity: 0.9;
                    font-size: 16px;
                }
                .content {
                    padding: 40px 30px;
                }
                .welcome-message {
                    font-size: 18px;
                    color: #2d3748;
                    margin-bottom: 30px;
                }
                .info-card {
                    background: #f7fafc;
                    border-radius: 12px;
                    padding: 25px;
                    margin: 20px 0;
                    border: 1px solid #e2e8f0;
                }
                .info-card h3 {
                    margin: 0 0 20px;
                    color: #4a5568;
                    font-size: 18px;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }
                .info-grid {
                    display: grid;
                    grid-template-columns: 1fr 2fr;
                    gap: 12px;
                }
                .info-label {
                    color: #718096;
                    font-weight: 500;
                }
                .info-value {
                    color: #2d3748;
                    font-weight: 600;
                }
                .coordinator-card {
                    background: #ebf4ff;
                    border-radius: 12px;
                    padding: 25px;
                    margin: 20px 0;
                    border-left: 4px solid #4299e1;
                }
                .warning-card {
                    background: #fffaf0;
                    border-radius: 12px;
                    padding: 25px;
                    margin: 20px 0;
                    border-left: 4px solid #ed8936;
                }
                .btn {
                    display: inline-block;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    text-decoration: none;
                    padding: 14px 35px;
                    border-radius: 30px;
                    font-weight: 600;
                    margin: 20px 0;
                    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
                }
                .footer {
                    background: #f8f9fa;
                    padding: 30px;
                    text-align: center;
                    color: #718096;
                    font-size: 14px;
                    border-top: 1px solid #e2e8f0;
                }
                .credentials-box {
                    background: #2d3748;
                    color: white;
                    padding: 20px;
                    border-radius: 8px;
                    margin: 20px 0;
                    font-family: monospace;
                    font-size: 16px;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🎉 ¡Bienvenido al Sistema de Gestión!</h1>
                    <p>Has sido registrado exitosamente como LÍDER</p>
                </div>
                
                <div class="content">
                    <div class="welcome-message">
                        Hola <strong>{{params.NOMBRE_COMPLETO}}</strong>,
                    </div>
                    
                    <p>Te informamos que has sido registrado en el Sistema de Gestión de Proyectos (SGP) con el rol de <strong>LÍDER</strong>.</p>
                    
                    <div class="info-card">
                        <h3>📋 Datos de tu registro</h3>
                        <div class="info-grid">
                            <span class="info-label">Nombres completos:</span>
                            <span class="info-value">{{params.NOMBRE_COMPLETO}}</span>
                            
                            <span class="info-label">Cédula:</span>
                            <span class="info-value">{{params.CEDULA}}</span>
                            
                            <span class="info-label">Teléfono:</span>
                            <span class="info-value">{{params.TELEFONO}}</span>
                            
                            <span class="info-label">Correo:</span>
                            <span class="info-value">{{params.EMAIL}}</span>
                            
                            <span class="info-label">Rol:</span>
                            <span class="info-value">Líder</span>
                            
                            <span class="info-label">Fecha registro:</span>
                            <span class="info-value">{{params.FECHA_REGISTRO}}</span>
                        </div>
                    </div>';
        
        if ($lider['coordinador_nombre'] !== 'No asignado') {
            $html .= '
                    <div class="coordinator-card">
                        <h3>👥 Tu Coordinador asignado</h3>
                        <div class="info-grid">
                            <span class="info-label">Nombre:</span>
                            <span class="info-value">{{params.COORDINADOR}}</span>
                            
                            <span class="info-label">Email:</span>
                            <span class="info-value">{{params.COORDINADOR_EMAIL}}</span>
                            
                            <span class="info-label">Teléfono:</span>
                            <span class="info-value">{{params.COORDINADOR_TELEFONO}}</span>
                        </div>
                        <p style="margin-top: 15px; color: #4a5568;">Este será tu coordinador de referencia para todas las actividades del sistema.</p>
                    </div>';
        }
        
        $html .= '
                    <div class="warning-card">
                        <h3>🔑 Credenciales de acceso</h3>
                        <div class="credentials-box">
                            <p><strong>Usuario:</strong> {{params.CEDULA}}</p>
                            <p><strong>Contraseña inicial:</strong> {{params.CEDULA}} (mismo número de cédula)</p>
                        </div>
                        <p style="color: #744210; margin-top: 10px;">
                            <strong>Importante:</strong> Por seguridad, cambia tu contraseña en el primer inicio de sesión.
                        </p>
                    </div>
                    
                    <p style="font-size: 16px; color: #2d3748;">Como líder podrás:</p>
                    <ul style="color: #4a5568; margin-bottom: 30px;">
                        <li>✓ Gestionar y hacer seguimiento de tus referenciados</li>
                        <li>✓ Generar reportes de gestión detallados</li>
                        <li>✓ Coordinar actividades con tu equipo</li>
                        <li>✓ Visualizar estadísticas de rendimiento</li>
                    </ul>
                    
                    <div style="text-align: center;">
                        <a href="http://tudominio.com/login.php" class="btn">Acceder al Sistema</a>
                    </div>
                </div>
                
                <div class="footer">
                    <p>Este correo fue enviado por <strong>{{params.ADMINISTRADOR}}</strong> (Administrador del sistema).</p>
                    <p>© ' . date('Y') . ' Sistema de Gestión de Proyectos - Todos los derechos reservados</p>
                    <p style="font-size: 12px; margin-top: 15px;">Si no esperabas este correo, por favor ignóralo o contacta al administrador.</p>
                </div>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    /**
     * Generar contenido HTML para notificación al coordinador
     */
    private function generarHTMLNotificacionCoordinador($lider, $referenciador, $administrador, $fechaRegistro) {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Nuevo Líder Asignado - SGP</title>
            <style>
                body {
                    font-family: "Segoe UI", Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    margin: 0;
                    padding: 0;
                }
                .container {
                    max-width: 600px;
                    margin: 20px auto;
                    background: #ffffff;
                    border-radius: 15px;
                    overflow: hidden;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                }
                .header {
                    background: linear-gradient(135deg, #38a169 0%, #2f855a 100%);
                    color: white;
                    padding: 40px 30px;
                    text-align: center;
                }
                .header h1 {
                    margin: 0;
                    font-size: 28px;
                    font-weight: 600;
                }
                .header p {
                    margin: 10px 0 0;
                    opacity: 0.9;
                    font-size: 16px;
                }
                .content {
                    padding: 40px 30px;
                }
                .info-card {
                    background: #f7fafc;
                    border-radius: 12px;
                    padding: 25px;
                    margin: 20px 0;
                    border: 1px solid #e2e8f0;
                }
                .info-card h3 {
                    margin: 0 0 20px;
                    color: #2f855a;
                    font-size: 18px;
                }
                .info-grid {
                    display: grid;
                    grid-template-columns: 1fr 2fr;
                    gap: 12px;
                }
                .info-label {
                    color: #718096;
                    font-weight: 500;
                }
                .info-value {
                    color: #2d3748;
                    font-weight: 600;
                }
                .highlight {
                    background: #ebf8ff;
                    padding: 20px;
                    border-radius: 8px;
                    border-left: 4px solid #4299e1;
                    margin: 20px 0;
                }
                .btn {
                    display: inline-block;
                    background: #38a169;
                    color: white;
                    text-decoration: none;
                    padding: 12px 30px;
                    border-radius: 30px;
                    font-weight: 600;
                    margin-top: 20px;
                }
                .footer {
                    background: #f8f9fa;
                    padding: 30px;
                    text-align: center;
                    color: #718096;
                    font-size: 14px;
                    border-top: 1px solid #e2e8f0;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>📢 Nuevo Líder en tu equipo</h1>
                    <p>Se ha asignado un nuevo líder a tu coordinación</p>
                </div>
                
                <div class="content">
                    <p>Hola <strong>{{params.COORDINADOR_NOMBRE}}</strong>,</p>
                    
                    <p>Te informamos que se ha registrado un nuevo líder en el sistema y ha sido asignado a tu equipo de trabajo.</p>
                    
                    <div class="info-card">
                        <h3>👤 Datos del nuevo líder</h3>
                        <div class="info-grid">
                            <span class="info-label">Nombres:</span>
                            <span class="info-value">{{params.LIDER_NOMBRE}}</span>
                            
                            <span class="info-label">Cédula:</span>
                            <span class="info-value">{{params.LIDER_CEDULA}}</span>
                            
                            <span class="info-label">Teléfono:</span>
                            <span class="info-value">{{params.LIDER_TELEFONO}}</span>
                            
                            <span class="info-label">Correo:</span>
                            <span class="info-value">{{params.LIDER_EMAIL}}</span>
                            
                            <span class="info-label">Fecha asignación:</span>
                            <span class="info-value">{{params.FECHA_ASIGNACION}}</span>
                        </div>
                    </div>
                    
                    <div class="highlight">
                        <p style="margin: 0; color: #2c3e50;">
                            <strong>Acciones recomendadas:</strong><br>
                            • Contacta al nuevo líder para darle la bienvenida<br>
                            • Explícale sus funciones y responsabilidades<br>
                            • Asegúrate de que tenga acceso al sistema<br>
                            • Programa una reunión inicial de coordinación
                        </p>
                    </div>
                </div>
                
                <div class="footer">
                    <p>Registro realizado por: <strong>{{params.ADMINISTRADOR}}</strong></p>
                    <p>© ' . date('Y') . ' Sistema de Gestión de Proyectos</p>
                </div>
            </div>
        </body>
        </html>';
    }
    
    /**
     * Generar contenido de texto plano (para referenciado)
     */
    private function generarTextoCorreo($referido, $referenciador, $lider, $fechaRegistro) {
    // Determinar el nombre del líder
    $nombreLider = 'No asignado';
    if ($lider && isset($lider['nombres'])) {
        $nombreLider = $lider['nombres'] . ' ' . ($lider['apellidos'] ?? '');
    } elseif ($lider && isset($lider['nombre_completo'])) {
        $nombreLider = $lider['nombre_completo'];
    }
    
    return "CONFIRMACIÓN DE REGISTRO - SISTEMA SGP\n\n" .
           "Estimado/a " . $referido['nombre'] . " " . $referido['apellido'] . ",\n\n" .
           "Su registro en el Sistema de Gestión Política ha sido completado exitosamente.\n\n" .
           "INFORMACIÓN DE REGISTRO:\n" .
           "Cédula: " . $referido['cedula'] . "\n" .
           "Email: " . $referido['email'] . "\n" .
           "Teléfono: " . $referido['telefono'] . "\n" .
           "Fecha: " . $fechaRegistro . "\n" .
           "Referenciador: " . $referenciador['nombres'] . " " . $referenciador['apellidos'] . "\n" .
           "Líder asignado: " . $nombreLider . "\n\n" .
           
           "Saludos cordiales,\n" .
           "Equipo SGP\n\n" .
           "© " . date('Y') . " Sistema de Gestión Política";
}
    
    /**
     * Enviar correo usando la API de Brevo
     */
    private function enviarAPI($data) {
        $headers = [
            'accept: application/json',
            'api-key: ' . $this->apiKey,
            'content-type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Error cURL: " . $error);
        }
        
        $responseData = json_decode($response, true);
        
        if ($httpCode === 201) {
            return [
                'success' => true,
                'message_id' => $responseData['messageId'] ?? null,
                'message' => 'Correo enviado exitosamente'
            ];
        } else {
            $errorMsg = $responseData['message'] ?? 'Error desconocido';
            throw new Exception("Brevo API Error ($httpCode): " . $errorMsg);
        }
    }
}
?>