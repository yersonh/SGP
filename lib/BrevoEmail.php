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
     * Enviar correo de confirmación de registro
     */
    public function enviarConfirmacionRegistro($referido, $referenciador) {
        try {
            // Usar la fecha que viene del archivo PHP (ya formateada correctamente)
            $fechaRegistro = $referido['fecha_registro'] ?? date('d/m/Y H:i:s');
            
            error_log("📅 Fecha a usar en correo: " . $fechaRegistro);
            
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
                'htmlContent' => $this->generarHTMLCorreo($referido, $referenciador, $fechaRegistro),
                'textContent' => $this->generarTextoCorreo($referido, $referenciador, $fechaRegistro),
                'params' => [
                    'NOMBRE_COMPLETO' => $referido['nombre'] . ' ' . $referido['apellido'],
                    'CEDULA' => $referido['cedula'],
                    'EMAIL' => $referido['email'],
                    'TELEFONO' => $referido['telefono'],
                    'FECHA_REGISTRO' => $fechaRegistro,  // ¡Esta es la fecha correcta!
                    'REFERENCIADOR' => $referenciador['nombres'] . ' ' . $referenciador['apellidos']
                ]
            ];
            
            error_log("📨 Datos del correo preparados");
            error_log("   - Para: " . $referido['email']);
            error_log("   - Fecha en correo: " . $fechaRegistro);
            
            // Enviar usando la API de Brevo
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
     * Generar contenido HTML del correo
     */
    private function generarHTMLCorreo($referido, $referenciador, $fechaRegistro) {
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
                </div>
                
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
     * Generar contenido de texto plano
     */
    private function generarTextoCorreo($referido, $referenciador, $fechaRegistro) {
        return "CONFIRMACIÓN DE REGISTRO - SISTEMA SGP\n\n" .
               "Estimado/a " . $referido['nombre'] . " " . $referido['apellido'] . ",\n\n" .
               "Su registro en el Sistema de Gestión Política ha sido completado exitosamente.\n\n" .
               "INFORMACIÓN DE REGISTRO:\n" .
               "Cédula: " . $referido['cedula'] . "\n" .
               "Email: " . $referido['email'] . "\n" .
               "Teléfono: " . $referido['telefono'] . "\n" .
               "Fecha: " . $fechaRegistro . "\n" .  // ¡Fecha correcta!
               "Referenciador: " . $referenciador['nombres'] . " " . $referenciador['apellidos'] . "\n\n" .
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