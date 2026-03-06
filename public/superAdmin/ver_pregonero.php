<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../models/PregoneroModel.php';
require_once __DIR__ . '/../../models/BarrioModel.php';
require_once __DIR__ . '/../../models/PuestoVotacionModel.php';
require_once __DIR__ . '/../../models/SectorModel.php';
require_once __DIR__ . '/../../models/ZonaModel.php';
require_once __DIR__ . '/../../helpers/navigation_helper.php';

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

// Guardar esta URL también
NavigationHelper::pushUrl();

// Verificar si el usuario está logueado y es SuperAdmin
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    header('Location: ../index.php');
    exit();
}

// Verificar que se haya proporcionado un ID de pregonero
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: data_pregoneros.php?error=pregonero_no_encontrado');
    exit();
}

$id_pregonero = intval($_GET['id']);

$pdo = Database::getConnection();
$usuarioModel = new UsuarioModel($pdo);
$pregoneroModel = new PregoneroModel($pdo);

// Obtener datos del usuario logueado
$usuario_logueado = $usuarioModel->getUsuarioById($_SESSION['id_usuario']);

// Obtener datos completos del pregonero
$pregonero = $pregoneroModel->getById($id_pregonero);

if (!$pregonero) {
    header('Location: data_pregoneros.php?error=pregonero_no_encontrado');
    exit();
}

// Obtener información del referenciador que registró al pregonero
$referenciador_registro = $usuarioModel->getUsuarioById($pregonero['id_usuario_registro'] ?? 0);

// Obtener información del referenciador asignado al pregonero
$referenciador_asignado = null;
if (!empty($pregonero['id_referenciador'])) {
    $referenciador_asignado = $usuarioModel->getUsuarioById($pregonero['id_referenciador']);
}

// Función para obtener nombre de campo o mostrar "N/A"
function getFieldValue($value) {
    return !empty($value) ? htmlspecialchars($value) : '<span class="na-text">N/A</span>';
}

// Función para formatear fecha
function formatDate($date) {
    if (empty($date) || $date === '0000-00-00') {
        return '<span class="na-text">N/A</span>';
    }
    return date('d/m/Y H:i', strtotime($date));
}

// Función para mostrar estado de actividad
function getEstadoActividad($activo) {
    if ($activo === true || $activo === 't' || $activo == 1) {
        return '<span class="status-badge status-active"><i class="fas fa-check-circle"></i> Activo</span>';
    } else {
        return '<span class="status-badge status-inactive"><i class="fas fa-times-circle"></i> Inactivo</span>';
    }
}

// Función para mostrar estado de voto
function getEstadoVoto($voto_registrado) {
    if ($voto_registrado === true || $voto_registrado === 't' || $voto_registrado == 1) {
        return '<span class="status-badge" style="background: rgba(39, 174, 96, 0.2); color: #27ae60; border: 1px solid rgba(39, 174, 96, 0.3);">
                    <i class="fas fa-check-circle"></i> Votó
                </span>';
    } else {
        return '<span class="status-badge" style="background: rgba(231, 76, 60, 0.2); color: #e74c3c; border: 1px solid rgba(231, 76, 60, 0.3);">
                    <i class="fas fa-times-circle"></i> No votó
                </span>';
    }
}

// Función para mostrar badge de mismo pregonero
function getMismoPregoneroBadge($quien_reporta, $nombres, $apellidos) {
    if (empty($quien_reporta)) return '';
    
    $nombreCompleto = trim($nombres . ' ' . $apellidos);
    $esMismo = strtolower(trim($quien_reporta)) === strtolower($nombreCompleto);
    
    if ($esMismo) {
        return '<span class="status-badge" style="background: rgba(79, 195, 247, 0.2); color: #4fc3f7; border: 1px solid rgba(79, 195, 247, 0.3); margin-left: 10px;">
                    <i class="fas fa-user-check"></i> Es el mismo pregonero
                </span>';
    }
    return '';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Pregonero - SGP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-dark: #1a1a2e;
            --secondary-dark: #16213e;
            --accent-blue: #0f3460;
            --highlight-blue: #4fc3f7;
            --text-light: #e6e6e6;
            --text-muted: #b0bec5;
            --success-color: #27ae60;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --card-bg: rgba(30, 30, 40, 0.85);
            --card-border: rgba(255, 255, 255, 0.15);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            min-height: 100vh;
            background: 
                linear-gradient(rgba(0, 0, 0, 0.85), rgba(0, 0, 0, 0.85)),
                url('/imagenes/fondo.jpg') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            flex-direction: column;
        }
        
        .main-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(26, 26, 46, 0.95);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            padding: 15px 0;
            border-bottom: 1px solid rgba(79, 195, 247, 0.2);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            z-index: 1000;
        }
        
        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header-title h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
            color: white;
            background: linear-gradient(135deg, #4fc3f7, #29b6f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(79, 195, 247, 0.1);
            padding: 8px 15px;
            border-radius: 20px;
            border: 1px solid rgba(79, 195, 247, 0.2);
            color: white;
        }
        
        .header-actions {
            display: flex;
            gap: 10px;
        }
        
        .header-btn {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            background: rgba(79, 195, 247, 0.1);
            border-radius: 6px;
            transition: all 0.3s;
            font-size: 0.9rem;
            border: 1px solid rgba(79, 195, 247, 0.2);
        }
        
        .header-btn:hover {
            background: rgba(79, 195, 247, 0.2);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(79, 195, 247, 0.2);
            border-color: rgba(79, 195, 247, 0.4);
        }
        
        .main-container {
            flex: 1;
            max-width: 1400px;
            margin: 80px auto 40px;
            padding: 0 20px;
            width: 100%;
        }
        
        .card-container {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 20px;
            border: 1px solid var(--card-border);
            box-shadow: 
                0 20px 50px rgba(0, 0, 0, 0.5),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .card-header {
            background: linear-gradient(135deg, rgba(79, 195, 247, 0.15), rgba(41, 182, 246, 0.1));
            border-bottom: 1px solid rgba(79, 195, 247, 0.3);
            padding: 25px 30px;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .header-left h2 {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: white;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .header-left h2 i {
            color: #4fc3f7;
        }
        
        .header-left p {
            color: var(--text-muted);
            font-size: 0.95rem;
            margin-bottom: 0;
        }
        
        .header-right {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            backdrop-filter: blur(10px);
        }
        
        .status-active {
            background: rgba(39, 174, 96, 0.2);
            color: #27ae60;
            border: 1px solid rgba(39, 174, 96, 0.3);
        }
        
        .status-inactive {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
            border: 1px solid rgba(231, 76, 60, 0.3);
        }
        
        .form-sections {
            padding: 30px;
        }
        
        .section-title {
            color: #4fc3f7;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid rgba(79, 195, 247, 0.3);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title i {
            color: #4fc3f7;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 35px;
        }
        
        .form-group {
            margin-bottom: 0;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-muted);
            font-weight: 500;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-label i {
            color: #90a4ae;
            font-size: 0.9rem;
            width: 20px;
        }
        
        .field-value {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 14px 16px;
            font-size: 0.95rem;
            color: var(--text-light);
            min-height: 46px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            backdrop-filter: blur(5px);
            transition: all 0.3s;
        }
        
        .field-value:hover {
            border-color: rgba(79, 195, 247, 0.3);
            background: rgba(255, 255, 255, 0.07);
        }
        
        .na-text {
            color: #90a4ae;
            font-style: italic;
        }
        
        .quien-reporta-container {
            display: flex;
            align-items: center;
            gap: 15px;
            width: 100%;
        }
        
        .badge-mismo {
            background: rgba(79, 195, 247, 0.2);
            color: #4fc3f7;
            border: 1px solid rgba(79, 195, 247, 0.3);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            white-space: nowrap;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 40px;
            padding-top: 25px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .edit-btn {
            background: linear-gradient(135deg, rgba(79, 195, 247, 0.2), rgba(41, 182, 246, 0.1));
            color: #4fc3f7;
            border: 1px solid rgba(79, 195, 247, 0.3);
            padding: 14px 35px;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            backdrop-filter: blur(10px);
        }
        
        .edit-btn:hover {
            background: linear-gradient(135deg, rgba(79, 195, 247, 0.3), rgba(41, 182, 246, 0.2));
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(79, 195, 247, 0.2);
            color: #4fc3f7;
            border-color: rgba(79, 195, 247, 0.5);
        }
        
        .back-btn {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-light);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 14px 35px;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            backdrop-filter: blur(10px);
        }
        
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.08);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            border-color: rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .referenciador-info {
            background: rgba(79, 195, 247, 0.1);
            border: 1px solid rgba(79, 195, 247, 0.2);
            border-radius: 10px;
            padding: 15px;
        }
        
        .referenciador-name {
            color: #4fc3f7;
            font-weight: 600;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .timestamp-info {
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        @media (max-width: 768px) {
            .main-container {
                padding: 0 15px;
                margin-top: 90px;
                margin-bottom: 20px;
            }
            
            .form-sections {
                padding: 20px;
            }
            
            .header-content {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .edit-btn, .back-btn {
                width: 100%;
                padding: 12px;
            }
        }
        
        @media (max-width: 480px) {
            .card-header {
                padding: 20px;
            }
            
            .header-left h2 {
                font-size: 1.5rem;
            }
            
            .header-actions {
                width: 100%;
                justify-content: space-between;
            }
            
            .section-title {
                font-size: 1.1rem;
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
                    <h1><i class="fas fa-bullhorn"></i> Ver Pregonero</h1>
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span>Super Admin: <?php echo htmlspecialchars($usuario_logueado['nombres'] . ' ' . $usuario_logueado['apellidos']); ?></span>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="../logout.php" class="header-btn">
                        <i class="fas fa-sign-out-alt"></i> Salir
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-container">
        <div class="card-container">
            <!-- Card Header -->
            <div class="card-header">
                <div class="header-content">
                    <div class="header-left">
                        <h2><i class="fas fa-bullhorn"></i> Detalle del Pregonero</h2>
                        <p>Información completa del pregonero registrado en el sistema</p>
                    </div>
                    <div class="header-right">
                        <?php echo getEstadoActividad($pregonero['activo'] ?? true); ?>
                        <?php echo getEstadoVoto($pregonero['voto_registrado'] ?? false); ?>
                    </div>
                </div>
            </div>
            
            <!-- Form Sections -->
            <div class="form-sections">
                <!-- Sección 1: Información Personal -->
                <div class="section-title">
                    <i class="fas fa-id-card"></i> Información Personal
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-user"></i> Nombres
                        </label>
                        <div class="field-value">
                            <?php echo getFieldValue($pregonero['nombres']); ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-user"></i> Apellidos
                        </label>
                        <div class="field-value">
                            <?php echo getFieldValue($pregonero['apellidos']); ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-id-card"></i> Identificación
                        </label>
                        <div class="field-value">
                            <?php echo getFieldValue($pregonero['identificacion']); ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-phone"></i> Teléfono
                        </label>
                        <div class="field-value">
                            <?php echo getFieldValue($pregonero['telefono']); ?>
                        </div>
                    </div>
                </div>
                
                <!-- Sección 2: Quien Reporta -->
                <div class="section-title">
                    <i class="fas fa-user-check"></i> Información de Reporte
                </div>
                
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label class="form-label">
                            <i class="fas fa-user-check"></i> ¿Quién reporta?
                        </label>
                        <div class="field-value">
                            <div class="quien-reporta-container">
                                <?php 
                                $quien_reporta = $pregonero['quien_reporta'] ?? '';
                                echo !empty($quien_reporta) ? htmlspecialchars($quien_reporta) : '<span class="na-text">N/A</span>';
                                
                                if (!empty($quien_reporta)) {
                                    echo getMismoPregoneroBadge($quien_reporta, $pregonero['nombres'], $pregonero['apellidos']);
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sección 3: Información de Ubicación -->
                <div class="section-title">
                    <i class="fas fa-map-marker-alt"></i> Información de Ubicación
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-map"></i> Barrio
                        </label>
                        <div class="field-value">
                            <?php echo getFieldValue($pregonero['barrio_nombre'] ?? ''); ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-trees"></i> Corregimiento
                        </label>
                        <div class="field-value">
                            <?php echo getFieldValue($pregonero['corregimiento'] ?? ''); ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-city"></i> Comuna
                        </label>
                        <div class="field-value">
                            <?php echo getFieldValue($pregonero['comuna'] ?? ''); ?>
                        </div>
                    </div>
                </div>
                
                <!-- Sección 4: Información de Votación -->
                <div class="section-title">
                    <i class="fas fa-vote-yea"></i> Información de Votación
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-compass"></i> Zona
                        </label>
                        <div class="field-value">
                            <?php echo getFieldValue($pregonero['zona_nombre'] ?? ''); ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-th-large"></i> Sector
                        </label>
                        <div class="field-value">
                            <?php echo getFieldValue($pregonero['sector_nombre'] ?? ''); ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-vote-yea"></i> Puesto de votación
                        </label>
                        <div class="field-value">
                            <?php echo getFieldValue($pregonero['puesto_nombre'] ?? ''); ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-table"></i> Mesa
                        </label>
                        <div class="field-value">
                            <?php echo getFieldValue($pregonero['mesa']); ?>
                        </div>
                    </div>
                </div>
                
                <!-- Sección 5: Información de Voto -->
                <div class="section-title">
                    <i class="fas fa-check-circle"></i> Información de Voto
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-calendar-check"></i> Fecha de voto
                        </label>
                        <div class="field-value">
                            <?php echo formatDate($pregonero['fecha_voto'] ?? ''); ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-user-tie"></i> Registró voto
                        </label>
                        <div class="field-value">
                            <?php 
                            if (!empty($pregonero['id_usuario_registro_voto'])) {
                                echo '<span class="status-badge" style="background: rgba(39, 174, 96, 0.2); color: #27ae60; border: 1px solid rgba(39, 174, 96, 0.3);">
                                        <i class="fas fa-check-circle"></i> Sí
                                    </span>';
                            } else {
                                echo '<span class="na-text">No registrado</span>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <!-- Sección 6: Información de Registro -->
                <div class="section-title">
                    <i class="fas fa-history"></i> Información de Registro
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-user-tie"></i> Referenciador asignado
                        </label>
                        <div class="field-value">
                            <?php if ($referenciador_asignado): ?>
                                <div class="referenciador-info" style="width: 100%;">
                                    <div class="referenciador-name">
                                        <i class="fas fa-user-tie"></i>
                                        <?php echo htmlspecialchars($referenciador_asignado['nombres'] . ' ' . $referenciador_asignado['apellidos']); ?>
                                    </div>
                                    <div class="timestamp-info">
                                        <i class="fas fa-id-card"></i>
                                        Cédula: <?php echo htmlspecialchars($referenciador_asignado['cedula'] ?? 'N/A'); ?>
                                    </div>
                                    <div class="timestamp-info">
                                        <i class="fas fa-phone"></i>
                                        Tel: <?php echo htmlspecialchars($referenciador_asignado['telefono'] ?? 'N/A'); ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <span class="na-text">N/A</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-user-tie"></i> Registrado por
                        </label>
                        <div class="field-value">
                            <?php if ($referenciador_registro): ?>
                                <div class="referenciador-info" style="width: 100%;">
                                    <div class="referenciador-name">
                                        <i class="fas fa-user-tie"></i>
                                        <?php echo htmlspecialchars($referenciador_registro['nombres'] . ' ' . $referenciador_registro['apellidos']); ?>
                                    </div>
                                    <div class="timestamp-info">
                                        <i class="fas fa-tag"></i>
                                        <?php echo htmlspecialchars($referenciador_registro['tipo_usuario'] ?? 'N/A'); ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <span class="na-text">N/A</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-calendar-alt"></i> Fecha de registro
                        </label>
                        <div class="field-value">
                            <?php echo formatDate($pregonero['fecha_registro'] ?? ''); ?>
                        </div>
                    </div>
                </div>
                
                <!-- Botones de Acción -->
                <div class="form-actions">
                    <?php
                    // Obtener la URL anterior del historial
                    $return_url = NavigationHelper::getPreviousUrl();
                    
                    // Validar seguridad
                    $dominio_actual = $_SERVER['HTTP_HOST'];
                    if (strpos($return_url, $dominio_actual) === false) {
                        $return_url = 'data_pregoneros.php';
                    }
                    ?>
                    
                    <a href="<?php echo htmlspecialchars($return_url); ?>" class="back-btn">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                    
                    <a href="editar_pregonero.php?id=<?php echo $id_pregonero; ?>" class="edit-btn">
                        <i class="fas fa-edit"></i> Editar Pregonero
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>