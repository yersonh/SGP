<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../helpers/auth.php'; // Si creaste este archivo

verificarLogin();

// Verificar que sea Referenciador
if ($_SESSION['tipo_usuario'] !== 'Referenciador') {
    header('Location: acceso_denegado.php');
    exit();
}

$pdo = Database::getConnection();
$model = new UsuarioModel($pdo);
$id_usuario_logueado = $_SESSION['id_usuario'];

// Obtener datos del usuario logueado
$usuario_logueado = $model->getUsuarioById($id_usuario_logueado);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Referenciador - SGP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .header {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border: none;
        }
        
        .card-header {
            background-color: #2c3e50;
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        
        .btn-primary {
            background-color: #3498db;
            border-color: #3498db;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="fas fa-user-tie"></i> Panel de Referenciador</h1>
                    <p class="mb-0">Bienvenido, <?php echo htmlspecialchars($usuario_logueado['nombres'] . ' ' . $usuario_logueado['apellidos']); ?></p>
                </div>
                <a href="logout.php" class="btn btn-outline-light">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Mi Información</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Nickname:</strong> <?php echo htmlspecialchars($usuario_logueado['nickname']); ?></p>
                        <p><strong>Nombre:</strong> <?php echo htmlspecialchars($usuario_logueado['nombres'] . ' ' . $usuario_logueado['apellidos']); ?></p>
                        <p><strong>Rol:</strong> <span class="badge bg-primary"><?php echo htmlspecialchars($usuario_logueado['tipo_usuario']); ?></span></p>
                        <p><strong>Último acceso:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-tasks"></i> Funcionalidades</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <i class="fas fa-user-plus fa-3x text-primary mb-3"></i>
                                        <h5>Gestión de Referidos</h5>
                                        <p class="text-muted">Administra tus referidos y seguimientos</p>
                                        <a href="gestion_referidos.php" class="btn btn-primary">Acceder</a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <i class="fas fa-chart-line fa-3x text-success mb-3"></i>
                                        <h5>Reportes</h5>
                                        <p class="text-muted">Consulta tus estadísticas y reportes</p>
                                        <a href="reportes_referenciador.php" class="btn btn-success">Ver Reportes</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <footer class="mt-5 text-center text-muted">
            <p>© <?php echo date('Y'); ?> SGP - Sistema de Gestión de Política</p>
        </footer>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>