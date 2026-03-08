<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../models/PregoneroModel.php';
require_once __DIR__ . '/../models/SistemaModel.php';

// Verificar si el usuario está logueado y es referenciador
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'Referenciador') {
    header('Location: index.php');
    exit();
}

$pdo = Database::getConnection();
$usuarioModel = new UsuarioModel($pdo);
$pregoneroModel = new PregoneroModel($pdo);
$sistemaModel = new SistemaModel($pdo);

$id_usuario_logueado = $_SESSION['id_usuario'];

// Obtener datos del usuario logueado
$usuario_logueado = $usuarioModel->getUsuarioById($id_usuario_logueado);

// Obtener pregoneros del usuario (con el nuevo método que debemos crear)
// Por ahora usamos getAll con filtro por usuario registro
$filtros = ['id_usuario_registro' => $id_usuario_logueado];
$pregoneros = $pregoneroModel->getAll($filtros);

// Contar pregoneros activos e inactivos
$total_pregoneros = count($pregoneros);
$activos = 0;
$inactivos = 0;
$votaron = 0;

foreach ($pregoneros as $preg) {
    if ($preg['activo'] === true || $preg['activo'] === 't' || $preg['activo'] == 1) {
        $activos++;
    } else {
        $inactivos++;
    }
    
    if ($preg['voto_registrado'] === true || $preg['voto_registrado'] === 't' || $preg['voto_registrado'] == 1) {
        $votaron++;
    }
}

// Actualizar último registro
$fecha_actual = date('Y-m-d H:i:s');
$usuarioModel->actualizarUltimoRegistro($id_usuario_logueado, $fecha_actual);
$fecha_formateada = date('d/m/Y H:i:s', strtotime($fecha_actual));

// Obtener información completa de la licencia
$licenciaInfo = $sistemaModel->getInfoCompletaLicencia();

// Extraer valores
$infoSistema = $licenciaInfo['info'];
$diasRestantes = $licenciaInfo['dias_restantes'];
$validaHastaFormatted = $licenciaInfo['valida_hasta_formatted'];
$fechaInstalacionFormatted = $licenciaInfo['fecha_instalacion_formatted'];

// PARA LA BARRA QUE DISMINUYE: Calcular porcentaje RESTANTE
$porcentajeRestante = $sistemaModel->getPorcentajeRestanteLicencia();

// Color de la barra basado en lo que RESTA
if ($porcentajeRestante > 50) {
    $barColor = 'bg-success';
} elseif ($porcentajeRestante > 25) {
    $barColor = 'bg-warning';
} else {
    $barColor = 'bg-danger';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pregoneros - SGP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Estilos base copiados de ver_referenciados_referenciador.css */
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-gray: #f8f9fa;
            --dark-gray: #343a40;
            --success-color: #27ae60;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
            font-size: 14px;
        }
        
        /* Header Styles */
        .main-header {
            background: linear-gradient(135deg, var(--primary-color), #1a252f);
            color: white;
            padding: 15px 0;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-container {
            display: flex;
            flex-direction: column;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .header-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .header-title h1 {
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255,255,255,0.1);
            padding: 5px 10px;
            border-radius: 20px;
        }
        
        .user-info i {
            color: #3498db;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 6px 12px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 0.8rem;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        .header-actions {
            display: flex;
            gap: 15px;
        }
        
        .btn-volver {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-volver:hover {
            background: linear-gradient(135deg, #495057, #343a40);
            color: white;
            transform: translateY(-2px);
        }
        
        /* CONTADOR COMPACTO */
        .countdown-compact-container {
            max-width: 1400px;
            margin: 0 auto 20px;
            padding: 0 15px;
        }
        
        .countdown-compact {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            border-radius: 10px;
            padding: 15px 20px;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 3px 10px rgba(0,0,0,0.15);
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .countdown-compact-title {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
        }
        
        .countdown-compact-title i {
            color: #f1c40f;
            font-size: 1.2rem;
        }
        
        .countdown-compact-title span {
            font-weight: 600;
            font-size: 1rem;
        }
        
        .countdown-compact-timer {
            flex: 2;
            text-align: center;
            font-family: 'Segoe UI', monospace;
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: 1px;
        }
        
        .countdown-compact-timer span {
            display: inline-block;
            min-width: 35px;
            text-align: center;
        }
        
        .countdown-compact-date {
            flex: 1;
            text-align: right;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            font-size: 0.9rem;
            color: rgba(255,255,255,0.9);
        }
        
        .countdown-compact-date i {
            color: #f1c40f;
        }
        
        /* Contenido Principal */
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 15px 30px;
        }
        
        .pregoneros-container {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Stats Cards */
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            border-top: 4px solid #3498db;
        }
        
        .stat-card.activos {
            border-top-color: #2ecc71;
        }
        
        .stat-card.inactivos {
            border-top-color: #e74c3c;
        }
        
        .stat-card.votaron {
            border-top-color: #9b59b6;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Tabla de Pregoneros */
        .pregoneros-table {
            width: 100%;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        
        .table-header {
            background: linear-gradient(135deg, #2c3e50, #1a252f);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .table-header h3 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .table-header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-export {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-export:hover {
            background: linear-gradient(135deg, #20c997, #28a745);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #dee2e6;
            padding: 15px;
            white-space: nowrap;
        }
        
        .table td {
            padding: 15px;
            vertical-align: middle;
            border-bottom: 1px solid #e9ecef;
        }
        
        .table tbody tr:hover {
            background-color: #f8fafc;
        }
        
        /* Badges */
        .badge-estado {
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
        }
        
        .badge-activo {
            background-color: #d4edda;
            color: #155724;
        }
        
        .badge-inactivo {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .badge-voto {
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
        }
        
        .badge-voto-si {
            background-color: #d4edda;
            color: #155724;
        }
        
        .badge-voto-no {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        /* Estado vacío */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            color: #6c757d;
            margin-bottom: 10px;
        }
        
        .empty-state p {
            color: #adb5bd;
            max-width: 500px;
            margin: 0 auto 20px;
        }
        
        /* Footer */
        .system-footer {
            text-align: center;
            padding: 20px 0;
            background: white;
            color: black;
            font-size: 0.85rem;
            line-height: 1.5;
            margin-top: 30px;
            border-top: 2px solid #eaeaea;
        }
        
        .container.text-center.mb-3 img {
            max-width: 320px;
            height: auto;
            transition: max-width 0.3s ease;
            cursor: pointer;
        }
        
        /* Modal */
        .modal-system-info .modal-header {
            background: linear-gradient(135deg, #2c3e50, #1a252f);
            color: white;
        }
        
        .modal-logo-container {
            text-align: center;
            margin-bottom: 20px;
            padding: 15px;
        }
        
        .modal-logo {
            max-width: 300px;
            height: auto;
            margin: 0 auto;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
            border: 3px solid #fff;
            background: white;
        }
        
        .licencia-info {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }
        
        .licencia-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .licencia-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }
        
        .licencia-dias {
            font-size: 1rem;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 20px;
            background: #3498db;
            color: white;
        }
        
        .licencia-progress {
            height: 12px;
            border-radius: 6px;
            margin-bottom: 8px;
            background-color: #e9ecef;
            overflow: hidden;
        }
        
        .licencia-progress-bar {
            height: 100%;
            border-radius: 6px;
            transition: width 0.6s ease;
        }
        
        .licencia-fecha {
            font-size: 0.85rem;
            color: #6c757d;
            text-align: center;
            margin-top: 5px;
        }
        
        .feature-image-container {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .feature-img-header {
            width: 190px;
            height: 190px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #ffffff;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .feature-img-header:hover {
            transform: scale(1.05);
        }
        
        .feature-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            height: 100%;
            border-left: 4px solid #3498db;
            transition: transform 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .logo-clickable {
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .logo-clickable:hover {
            transform: scale(1.05);
        }
        
        /* Notificaciones */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border-radius: 10px;
            padding: 15px 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 15px;
            z-index: 9999;
            animation: slideIn 0.3s ease;
            max-width: 400px;
        }
        
        .notification-success {
            border-left: 5px solid #28a745;
        }
        
        .notification-error {
            border-left: 5px solid #dc3545;
        }
        
        .notification-info {
            border-left: 5px solid #17a2b8;
        }
        
        .notification-content {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
        }
        
        .notification-close {
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            padding: 0;
            font-size: 1rem;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .stats-cards {
                grid-template-columns: 1fr;
            }
            
            .table th, .table td {
                padding: 10px;
                font-size: 0.9rem;
            }
            
            .pregoneros-container {
                padding: 15px;
            }
            
            .table-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .table-header-actions {
                width: 100%;
                justify-content: space-between;
            }
            
            .header-top {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .header-actions {
                width: 100%;
                justify-content: space-between;
            }
            
            .countdown-compact {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            
            .countdown-compact-timer {
                order: 2;
                width: 100%;
            }
            
            .countdown-compact-title {
                order: 1;
                justify-content: center;
                width: 100%;
            }
            
            .countdown-compact-date {
                order: 3;
                justify-content: center;
                width: 100%;
            }
            
            .container.text-center.mb-3 img {
                max-width: 220px;
            }
            
            .feature-img-header {
                width: 140px;
                height: 140px;
            }
            
            .modal-logo {
                max-width: 200px;
            }
        }
        
        @media (max-width: 480px) {
            .countdown-compact-timer {
                font-size: 1.3rem;
            }
            
            .container.text-center.mb-3 img {
                max-width: 200px;
            }
            
            .feature-img-header {
                width: 120px;
                height: 120px;
            }
            
            .modal-logo {
                max-width: 180px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="header-container">
            <div class="header-top">
                <div class="header-title">
                    <h1><i class="fas fa-bullhorn"></i> Mis Pregoneros</h1>
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($usuario_logueado['nombres'] . ' ' . $usuario_logueado['apellidos']); ?></span>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="referenciador.php" class="btn-volver">
                        <i class="fas fa-arrow-left"></i> Volver al Formulario
                    </a>
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- CONTADOR COMPACTO -->
    <div class="countdown-compact-container">
        <div class="countdown-compact">
            <div class="countdown-compact-title">
                <i class="fas fa-hourglass-half"></i>
                <span>Elecciones Legislativas 2026</span>
            </div>
            <div class="countdown-compact-timer">
                <span id="compact-days">00</span>d 
                <span id="compact-hours">00</span>h 
                <span id="compact-minutes">00</span>m 
                <span id="compact-seconds">00</span>s
            </div>
            <div class="countdown-compact-date">
                <i class="fas fa-calendar-alt"></i>
                8 Marzo 2026
            </div>
        </div>
    </div>

    <!-- Contenido Principal -->
    <div class="main-container">
        <div class="pregoneros-container">
            <!-- Tarjetas de Estadísticas -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_pregoneros; ?></div>
                    <div class="stat-label">Total Pregoneros</div>
                </div>
                
                <div class="stat-card activos">
                    <div class="stat-number"><?php echo $activos; ?></div>
                    <div class="stat-label">Pregoneros Activos</div>
                </div>
                
                <div class="stat-card inactivos">
                    <div class="stat-number"><?php echo $inactivos; ?></div>
                    <div class="stat-label">Pregoneros Inactivos</div>
                </div>
                
                <div class="stat-card votaron">
                    <div class="stat-number"><?php echo $votaron; ?></div>
                    <div class="stat-label">Ya Votaron</div>
                </div>
            </div>

            <!-- Tabla de Pregoneros -->
            <div class="pregoneros-table">
                <div class="table-header">
                    <h3><i class="fas fa-list-alt"></i> Lista de Pregoneros Registrados</h3>
                    <div class="table-header-actions">
                        <span>Fecha y hora actual: <?php echo $fecha_formateada; ?></span>
                        <?php if ($total_pregoneros > 0): ?>
                        <button class="btn-export" data-bs-toggle="modal" data-bs-target="#exportModal">
                            <i class="fas fa-download"></i> Exportar
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($total_pregoneros > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nombre Completo</th>
                                <th>Identificación</th>
                                <th>Teléfono</th>
                                <th>Barrio</th>
                                <th>Puesto/Mesa</th>
                                <th>¿Quién reporta?</th>
                                <th>Referenciador</th>
                                <th>Votó</th>
                                <th>Fecha Registro</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pregoneros as $pregonero): ?>
                            <?php 
                            $activo = $pregonero['activo'];
                            $esta_activo = ($activo === true || $activo === 't' || $activo == 1);
                            $voto_registrado = $pregonero['voto_registrado'] ?? false;
                            $voto = ($voto_registrado === true || $voto_registrado === 't' || $voto_registrado == 1);
                            
                            // Verificar si quien reporta es el mismo pregonero
                            $nombreCompleto = trim(($pregonero['nombres'] ?? '') . ' ' . ($pregonero['apellidos'] ?? ''));
                            $quienReporta = trim($pregonero['quien_reporta'] ?? '');
                            $esMismo = (!empty($quienReporta) && strtolower($quienReporta) === strtolower($nombreCompleto));
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($pregonero['nombres'] . ' ' . $pregonero['apellidos']); ?></strong>
                                </td>
                                
                                <td>
                                    <?php echo htmlspecialchars($pregonero['identificacion']); ?>
                                </td>
                                
                                <td>
                                    <?php echo htmlspecialchars($pregonero['telefono']); ?>
                                </td>
                                
                                <td>
                                    <?php echo htmlspecialchars($pregonero['barrio_nombre'] ?? 'N/A'); ?>
                                </td>
                                
                                <td>
                                    <div>
                                        <small class="text-muted">Puesto:</small><br>
                                        <strong><?php echo htmlspecialchars($pregonero['puesto_nombre'] ?? 'N/A'); ?></strong>
                                    </div>
                                    <div class="mt-1">
                                        <small class="text-muted">Mesa:</small><br>
                                        <strong><?php echo htmlspecialchars($pregonero['mesa'] ?? 'N/A'); ?></strong>
                                    </div>
                                </td>
                                
                                <td>
                                    <?php if (!empty($quienReporta)): ?>
                                        <div>
                                            <?php echo htmlspecialchars($quienReporta); ?>
                                            <?php if ($esMismo): ?>
                                                <br><small class="text-info"><i class="fas fa-user-check"></i> (mismo pregonero)</small>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <?php 
                                    $referenciadorNombre = $pregonero['referenciador_nombre'] ?? 'N/A';
                                    echo htmlspecialchars($referenciadorNombre);
                                    ?>
                                </td>
                                
                                <td>
                                    <?php if ($voto): ?>
                                        <span class="badge-voto badge-voto-si">
                                            <i class="fas fa-check-circle me-1"></i> Sí
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-voto badge-voto-no">
                                            <i class="fas fa-times-circle me-1"></i> No
                                        </span>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <?php 
                                    $fecha_registro = date('d/m/Y H:i', strtotime($pregonero['fecha_registro']));
                                    echo htmlspecialchars($fecha_registro);
                                    ?>
                                </td>
                                
                                <td>
                                    <?php if ($esta_activo): ?>
                                        <span class="badge-estado badge-activo">
                                            <i class="fas fa-check-circle me-1"></i> Activo
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-estado badge-inactivo">
                                            <i class="fas fa-times-circle me-1"></i> Inactivo
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-bullhorn"></i>
                    <h3>No hay pregoneros registrados</h3>
                    <p>No has registrado ningún pregonero aún. Comienza agregando tu primer pregonero.</p>
                    <a href="pregonero.php" class="btn-volver">
                        <i class="fas fa-plus-circle"></i> Agregar Primer Pregonero
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer del sistema -->
    <footer class="system-footer">
        <div class="container text-center mb-3">
            <img src="imagenes/Logo-artguru.png" 
                alt="Logo" 
                class="logo-clickable"
                onclick="mostrarModalSistema()"
                title="Haz clic para ver información del sistema">
        </div>

        <div class="container text-center">
            <p>
                <strong>© 2026 Sistema de Gestión Política SGP.</strong> Puerto Gaitán - Meta
                Módulo de SGA Sistema de Gestión Administrativa 2026 SGA Solución de Gestión Administrativa Enterprise Premium 1.0™ desarrollado por SISGONTech Technology®, Conjunto Residencial Portal del Llano, Casa 104, Villavicencio, Meta. - Asesores e-Governance Solutions para Entidades Públicas 2026® SISGONTech
                Propietario software: Yerson Solano Alfonso - ☎️ (+57) 313 333 62 27 - Email: soportesgp@gmail.com © Reservados todos los derechos de autor.
            </p>
        </div>
    </footer>

    <!-- Modal de Información del Sistema -->
    <div class="modal fade modal-system-info" id="modalSistema" tabindex="-1" aria-labelledby="modalSistemaLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalSistemaLabel">
                        <i class="fas fa-info-circle me-2"></i>Información del Sistema
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Logo centrado -->
                    <div class="modal-logo-container">
                        <img src="imagenes/Logo-artguru.png" alt="Logo del Sistema" class="modal-logo">
                    </div>
                    
                    <!-- Título del Sistema -->
                    <div class="licencia-info">
                        <div class="licencia-header">
                            <h6 class="licencia-title">Licencia Runtime</h6>
                            <span class="licencia-dias">
                                <?php echo $diasRestantes; ?> días restantes
                            </span>
                        </div>
                        
                        <div class="licencia-progress">
                            <div class="licencia-progress-bar <?php echo $barColor; ?>" 
                                style="width: <?php echo $porcentajeRestante; ?>%"
                                role="progressbar" 
                                aria-valuenow="<?php echo $porcentajeRestante; ?>" 
                                aria-valuemin="0" 
                                aria-valuemax="100">
                            </div>
                        </div>
                        
                        <div class="licencia-fecha">
                            <i class="fas fa-calendar-alt me-1"></i>
                            Instalado: <?php echo $fechaInstalacionFormatted; ?> | 
                            Válida hasta: <?php echo $validaHastaFormatted; ?>
                        </div>
                    </div>
                    <div class="feature-image-container">
                        <img src="imagenes/ingeniero2.png" alt="Logo de Herramienta" class="feature-img-header">
                        <div class="profile-info mt-3">
                            <h4 class="profile-name"><strong>Rubén Darío González García</strong></h4>
                            <small class="profile-description">
                                Ingeniero de Sistemas, administrador de bases de datos, desarrollador de objeto OLE.<br>
                                Magister en Administración Pública.<br>
                                <span class="cio-tag"><strong>CIO de equipo soporte SISGONTECH</strong></span>
                            </small>
                        </div>
                    </div>
                    <!-- Sección de Características -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div class="feature-card">
                                <div class="feature-icon text-primary mb-3">
                                    <i class="fas fa-bolt fa-2x"></i>
                                </div>
                                <h5 class="feature-title">Efectividad de la Herramienta</h5>
                                <h6 class="text-muted mb-2">Optimización de Tiempos</h6>
                                <p class="feature-text">
                                    Reducción del 70% en el procesamiento manual de datos y generación de reportes de adeptos.
                                </p>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="feature-card">
                                <div class="feature-icon text-success mb-3">
                                    <i class="fas fa-database fa-2x"></i>
                                </div>
                                <h5 class="feature-title">Integridad de Datos</h5>
                                <h6 class="text-muted mb-2">Validación Inteligente</h6>
                                <p class="feature-text">
                                    Validación en tiempo real para eliminar duplicados y errores de digitación en la base de datos política.
                                </p>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="feature-card">
                                <div class="feature-icon text-warning mb-3">
                                    <i class="fas fa-chart-line fa-2x"></i>
                                </div>
                                <h5 class="feature-title">Monitoreo de Metas</h5>
                                <h6 class="text-muted mb-2">Seguimiento Visual</h6>
                                <p class="feature-text">
                                    Seguimiento visual del cumplimiento de objetivos mediante barras de avance dinámicas.
                                </p>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="feature-card">
                                <div class="feature-icon text-danger mb-3">
                                    <i class="fas fa-shield-alt fa-2x"></i>
                                </div>
                                <h5 class="feature-title">Seguridad Avanzada</h5>
                                <h6 class="text-muted mb-2">Control Total</h6>
                                <p class="feature-text">
                                    Control de acceso jerarquizado y trazabilidad total de ingresos al sistema.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="https://sgp-sistema-de-gestion-politica.webnode.com.co/" 
                       target="_blank" 
                       class="btn btn-primary"
                       onclick="cerrarModalSistema();">
                        <i class="fas fa-external-link-alt me-1"></i> Uso SGP
                    </a>
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Exportación -->
    <?php if ($total_pregoneros > 0): ?>
    <div class="modal fade" id="exportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-download me-2"></i> Exportar Mis Pregoneros</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Seleccione el formato de exportación:</p>
                    <div class="d-grid gap-3">
                        <button class="btn btn-success btn-lg py-3" onclick="exportarMisPregoneros('excel')">
                            <i class="fas fa-file-excel fa-lg me-2"></i> Exportar a Excel (.xls)
                        </button>
                        <button class="btn btn-primary btn-lg py-3" onclick="exportarMisPregoneros('pdf')">
                            <i class="fas fa-file-pdf fa-lg me-2"></i> Exportar a PDF
                        </button>
                    </div>
                    <hr class="my-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="exportSoloActivos" style="transform: scale(1.3);">
                        <label class="form-check-label ms-2" for="exportSoloActivos">
                            <i class="fas fa-filter me-1"></i> Exportar solo pregoneros activos
                        </label>
                    </div>
                    <div class="mt-3 text-muted small">
                        <i class="fas fa-info-circle me-1"></i> Solo se exportarán tus pregoneros registrados
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/contador.js"></script>
    
    <script>
        // Función para actualizar la hora en tiempo real
        function updateCurrentTime() {
            const now = new Date();
            const day = now.getDate().toString().padStart(2, '0');
            const month = (now.getMonth() + 1).toString().padStart(2, '0');
            const year = now.getFullYear();
            const hours = now.getHours().toString().padStart(2, '0');
            const minutes = now.getMinutes().toString().padStart(2, '0');
            const seconds = now.getSeconds().toString().padStart(2, '0');
            const timeString = `${day}/${month}/${year} ${hours}:${minutes}:${seconds}`;
            
            document.querySelectorAll('.table-header-actions span').forEach(element => {
                if (element.textContent.includes('Fecha y hora actual:')) {
                    element.textContent = `Fecha y hora actual: ${timeString}`;
                }
            });
        }
        
        // Actualizar cada segundo
        updateCurrentTime();
        setInterval(updateCurrentTime, 1000);
        
        // Función para mostrar notificaciones
        function showNotification(message, type = 'info') {
            const oldNotification = document.querySelector('.notification');
            if (oldNotification) {
                oldNotification.remove();
            }
            
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <div class="notification-content">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'}"></i>
                    <span>${message}</span>
                </div>
                <button class="notification-close">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            document.body.appendChild(notification);
            
            notification.querySelector('.notification-close').addEventListener('click', () => {
                notification.remove();
            });
            
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 5000);
        }
        
        // Función para exportar mis pregoneros
        function exportarMisPregoneros(formato) {
            const soloActivos = document.getElementById('exportSoloActivos')?.checked || false;
            
            let url = 'exportar_mis_pregoneros_excel.php';
            
            // Cambiar URL según formato
            if (formato === 'pdf') {
                url = 'exportar_mis_pregoneros_pdf.php';
            }
            
            // Construir parámetros de la URL
            const params = new URLSearchParams();
            
            if (soloActivos) {
                params.append('solo_activos', '1');
            }
            
            // Convertir parámetros a string
            const queryString = params.toString();
            if (queryString) {
                url += '?' + queryString;
            }
            
            // Cerrar modal
            const exportModal = bootstrap.Modal.getInstance(document.getElementById('exportModal'));
            if (exportModal) {
                exportModal.hide();
            }
            
            // Mostrar mensaje de procesamiento
            let mensaje = 'Generando archivo ' + formato.toUpperCase();
            if (soloActivos) {
                mensaje += ' (solo activos)';
            }
            mensaje += '...';
            
            showNotification(mensaje, 'info');
            
            // Descargar archivo después de un pequeño delay
            setTimeout(() => {
                const link = document.createElement('a');
                link.href = url;
                link.target = '_blank';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                setTimeout(() => {
                    showNotification('Archivo generado correctamente', 'success');
                }, 1000);
            }, 300);
        }
        
        // Función para mostrar modal del sistema
        function mostrarModalSistema() {
            const modalElement = document.getElementById('modalSistema');
            if (modalElement) {
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            }
        }
        
        function cerrarModalSistema() {
            const modalElement = document.getElementById('modalSistema');
            if (modalElement) {
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) {
                    modal.hide();
                }
            }
        }
        
        // Manejar parámetros de éxito/error en la URL
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            
            if (urlParams.has('success')) {
                const successType = urlParams.get('success');
                let message = '';
                
                switch(successType) {
                    case 'pregonero_creado':
                        message = 'Pregonero creado correctamente';
                        break;
                    default:
                        message = 'Operación realizada correctamente';
                }
                
                if (message) {
                    showNotification(message, 'success');
                    window.history.replaceState({}, document.title, window.location.pathname);
                }
            }
            
            if (urlParams.has('error')) {
                const errorType = urlParams.get('error');
                let message = '';
                
                switch(errorType) {
                    default:
                        message = 'Ocurrió un error en la operación';
                }
                
                if (message) {
                    showNotification(message, 'error');
                    window.history.replaceState({}, document.title, window.location.pathname);
                }
            }
        });
    </script>
</body>
</html>