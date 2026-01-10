<?php
// public/index.php - Versión SEGURA
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Votaciones Gubernamentales - SGP</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #1a237e 0%, #283593 100%);
            color: #fff;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            max-width: 800px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .logo {
            font-size: 3.5rem;
            margin-bottom: 20px;
            color: #4fc3f7;
        }
        
        h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 700;
            background: linear-gradient(90deg, #4fc3f7, #29b6f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 30px;
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .status-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 25px;
            border-left: 4px solid #4fc3f7;
            transition: transform 0.3s, background 0.3s;
        }
        
        .status-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.1);
        }
        
        .status-card h3 {
            font-size: 1.1rem;
            margin-bottom: 15px;
            color: #bbdefb;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .status-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: #4fc3f7;
        }
        
        .status-desc {
            font-size: 0.9rem;
            opacity: 0.8;
            margin-top: 8px;
        }
        
        .actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 30px;
        }
        
        .btn {
            padding: 15px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            font-size: 1rem;
        }
        
        .btn-primary {
            background: linear-gradient(90deg, #2196f3, #1976d2);
            color: white;
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.9rem;
            opacity: 0.7;
        }
        
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .badge-success {
            background: #4caf50;
            color: white;
        }
        
        .badge-warning {
            background: #ff9800;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🗳️</div>
            <h1>Sistema de Votaciones Gubernamentales</h1>
            <p class="subtitle">Sistema Seguro de Gestión Electoral - Entorno de Producción</p>
        </div>
        
        <div class="status-grid">
            <div class="status-card">
                <h3>🚀 Estado del Sistema</h3>
                <div class="status-value">OPERATIVO</div>
                <div class="status-desc">Todos los servicios funcionando correctamente</div>
            </div>
            
            <div class="status-card">
                <h3>🐘 PHP</h3>
                <div class="status-value"><?php echo PHP_VERSION; ?></div>
                <div class="status-desc">Versión del servidor PHP</div>
            </div>
            
            <div class="status-card">
                <h3>🗄️ Base de Datos</h3>
                <div class="status-value">
                    <?php
                    try {
                        $pdo = new PDO(
                            "pgsql:host=" . getenv('PGHOST') . ";dbname=" . getenv('PGDATABASE'),
                            getenv('PGUSER'),
                            getenv('PGPASSWORD'),
                            [PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT]
                        );
                        echo 'CONECTADA';
                        $pdo = null;
                    } catch (Exception $e) {
                        echo 'PENDIENTE';
                    }
                    ?>
                </div>
                <div class="status-desc">PostgreSQL en Railway</div>
            </div>
            
            <div class="status-card">
                <h3>🔒 Seguridad</h3>
                <div class="status-value">ACTIVADA</div>
                <div class="status-desc">SSL/TLS + Headers de seguridad</div>
            </div>
        </div>
        
        <div class="actions">
            <a href="/admin" class="btn btn-primary">
                🔐 Panel de Administración
            </a>
            <a href="/health" class="btn btn-secondary">
                📊 Health Check
            </a>
            <a href="/docs" class="btn btn-secondary">
                📚 Documentación
            </a>
        </div>
        
        <div class="footer">
            <p>© <?php echo date('Y'); ?> Sistema de Votaciones Gubernamentales - SGP</p>
            <p>Desplegado en Railway • Entorno: <span class="badge badge-success">PRODUCCIÓN</span></p>
            <p style="margin-top: 10px; font-size: 0.8rem; color: #ff9800;">
                ⚠️ phpinfo() desactivado por seguridad
            </p>
        </div>
    </div>
</body>
</html>