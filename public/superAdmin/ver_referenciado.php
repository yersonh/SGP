<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../models/ReferenciadoModel.php';
require_once __DIR__ . '/../../models/ZonaModel.php';
require_once __DIR__ . '/../../models/SectorModel.php';
require_once __DIR__ . '/../../models/PuestoVotacionModel.php';
require_once __DIR__ . '/../../models/DepartamentoModel.php';
require_once __DIR__ . '/../../models/MunicipioModel.php';
require_once __DIR__ . '/../../models/OfertaApoyoModel.php';
require_once __DIR__ . '/../../models/GrupoPoblacionalModel.php';
require_once __DIR__ . '/../../models/BarrioModel.php';
require_once __DIR__ . '/../../models/InsumoModel.php';

// Verificar si el usuario está logueado y es SuperAdmin
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    header('Location: ../index.php');
    exit();
}

// Verificar que se haya proporcionado un ID de referenciado
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: data_referidos.php?error=referenciado_no_encontrado');
    exit();
}

$id_referenciado = intval($_GET['id']);

$pdo = Database::getConnection();
$usuarioModel = new UsuarioModel($pdo);
$referenciadoModel = new ReferenciadoModel($pdo);
$insumoModel = new InsumoModel($pdo);

// Obtener datos del usuario logueado
$usuario_logueado = $usuarioModel->getUsuarioById($_SESSION['id_usuario']);

// Obtener datos completos del referenciado
$referenciado = $referenciadoModel->getReferenciadoCompleto($id_referenciado);

if (!$referenciado) {
    header('Location: data_referidos.php?error=referenciado_no_encontrado');
    exit();
}

// Obtener insumos del referenciado
$insumos_referenciado = $insumoModel->getInsumosByReferenciado($id_referenciado);

// Obtener información del referenciador
$referenciador = $usuarioModel->getUsuarioById($referenciado['id_referenciador'] ?? 0);

// Función para obtener nombre de campo o mostrar "N/A"
function getFieldValue($value) {
    return !empty($value) ? htmlspecialchars($value) : '<span class="na-text">N/A</span>';
}

// Función para mostrar estado de actividad
function getEstadoActividad($activo) {
    if ($activo === true || $activo === 't' || $activo == 1) {
        return '<span class="status-active"><i class="fas fa-check-circle"></i> Activo</span>';
    } else {
        return '<span class="status-inactive"><i class="fas fa-times-circle"></i> Inactivo</span>';
    }
}

// Función para mostrar icono de afinidad
function getAfinidadIcon($afinidad) {
    $colors = [
        1 => '#ff6b6b', // Rojo
        2 => '#ffa726', // Naranja
        3 => '#ffd166', // Amarillo
        4 => '#06d6a0', // Verde
        5 => '#118ab2'  // Azul
    ];
    
    $color = $colors[$afinidad] ?? '#cccccc';
    return '<span class="afinidad-badge" style="background-color: ' . $color . '">' . $afinidad . '</span>';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Referenciado - SGP</title>
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
        
        .afinidad-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-weight: 600;
            font-size: 1rem;
            color: white;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.2);
        }
        
        .compromiso-text {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 16px;
            min-height: 120px;
            color: var(--text-light);
            line-height: 1.6;
            white-space: pre-wrap;
            overflow-y: auto;
            max-height: 200px;
            backdrop-filter: blur(5px);
            transition: all 0.3s;
        }
        
        .compromiso-text:hover {
            border-color: rgba(79, 195, 247, 0.3);
            background: rgba(255, 255, 255, 0.07);
        }
        
        .insumos-section {
            background: rgba(79, 195, 247, 0.05);
            border: 1px solid rgba(79, 195, 247, 0.1);
            border-radius: 12px;
            padding: 25px;
            margin-top: 20px;
        }
        
        .insumos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .insumo-card {
            background: rgba(30, 30, 40, 0.7);
            border: 1px solid rgba(79, 195, 247, 0.1);
            border-radius: 10px;
            padding: 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s;
            backdrop-filter: blur(10px);
        }
        
        .insumo-card:hover {
            border-color: rgba(79, 195, 247, 0.3);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(79, 195, 247, 0.1);
            background: rgba(30, 30, 40, 0.9);
        }
        
        .insumo-icon {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            background: rgba(79, 195, 247, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4fc3f7;
            font-size: 1.3rem;
            border: 1px solid rgba(79, 195, 247, 0.2);
        }
        
        .insumo-info {
            flex: 1;
        }
        
        .insumo-name {
            color: white;
            font-weight: 500;
            font-size: 0.95rem;
            margin-bottom: 4px;
        }
        
        .insumo-details {
            color: var(--text-muted);
            font-size: 0.85rem;
            line-height: 1.4;
        }
        
        .no-insumos {
            text-align: center;
            color: var(--text-muted);
            font-style: italic;
            padding: 30px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 10px;
            border: 2px dashed rgba(255, 255, 255, 0.1);
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
        
        .info-row {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 15px;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .info-label {
            color: var(--text-muted);
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .info-value {
            color: var(--text-light);
            font-size: 0.95rem;
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
            
            .insumos-grid {
                grid-template-columns: 1fr;
            }
            
            .info-row {
                grid-template-columns: 1fr;
                gap: 5px;
                margin-bottom: 20px;
            }
            
            .info-label {
                margin-bottom: 0;
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
                    <h1><i class="fas fa-eye"></i> Ver Referenciado</h1>
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span>Super Admin: <?php echo htmlspecialchars($usuario_logueado['nombres'] . ' ' . $usuario_logueado['apellidos']); ?></span>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="data_referidos.php" class="header-btn">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
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
                        <h2><i class="fas fa-user-circle"></i> Detalle del Referenciado</h2>
                        <p>Información completa del referenciado registrado en el sistema</p>
                    </div>
                    <div class="header-right">
                        <div class="status-badge status-active">
                            <?php echo getEstadoActividad($referenciado['activo'] ?? true); ?>
                        </div>
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
                            <?php echo getFieldValue($referenciado['nombre']); ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-user"></i> Apellidos
                        </label>
                        <div class="field-value">
                            <?php echo getFieldValue($referenciado['apellido']); ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-id-card"></i> Cédula
                        </label>
                        <div class="field-value">
                            <?php echo getFieldValue($referenciado['cedula']); ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-envelope"></i> Email
                        </label>
                        <div class="field-value">
                            <?php echo getFieldValue($referenciado['email']); ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-phone"></i> Teléfono
                        </label>
                        <div class="field-value">
                            <?php echo getFieldValue($referenciado['telefono']); ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-home"></i> Dirección
                        </label>
                        <div class="field-value">
                            <?php echo getFieldValue($referenciado['direccion']); ?>
                        </div>
                    </div>
                </div>
                
                <!-- Sección 2: Información de Ubicación -->
                <div class="section-title">
                    <i class="fas fa-map-marker-alt"></i> Información de Ubicación
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-flag"></i> Departamento
                        </label>
                        <div class="field-value">
                            <?php echo getFieldValue($referenciado['departamento_nombre'] ?? ''); ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-city"></i> Municipio
                        </label>
                        <div class="field-value">
                            <?php echo getFieldValue($referenciado['municipio_nombre'] ?? ''); ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-map"></i> Barrio
                        </label>
                        <div class="field-value">
                            <?php echo getFieldValue($referenciado['barrio_nombre'] ?? ''); ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-compass"></i> Zona
                        </label>
                        <div class="field-value">
                            <?php echo getFieldValue($referenciado['zona_nombre'] ?? ''); ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-th-large"></i> Sector
                        </label>
                        <div class="field-value">
                            <?php echo getFieldValue($referenciado['sector_nombre'] ?? ''); ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-vote-yea"></i> Puesto de votación
                        </label>
                        <div class="field-value">
                            <?php echo getFieldValue($referenciado['puesto_votacion_nombre'] ?? ''); ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-table"></i> Mesa
                        </label>
                        <div class="field-value">
                            <?php echo getFieldValue($referenciado['mesa']); ?>
                        </div>
                    </div>
                </div>
                
                <!-- Sección 3: Información Adicional -->
                <div class="section-title">
                    <i class="fas fa-info-circle"></i> Información Adicional
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-heart"></i> Afinidad
                        </label>
                        <div class="field-value">
                            <?php echo getAfinidadIcon($referenciado['afinidad'] ?? 1); ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-users"></i> Grupo poblacional
                        </label>
                        <div class="field-value">
                            <?php echo getFieldValue($referenciado['grupo_poblacional_nombre'] ?? ''); ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-hands-helping"></i> Oferta de apoyo
                        </label>
                        <div class="field-value">
                            <?php echo getFieldValue($referenciado['oferta_apoyo_nombre'] ?? ''); ?>
                        </div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label class="form-label">
                            <i class="fas fa-comment-alt"></i> Compromiso
                        </label>
                        <div class="compromiso-text">
                            <?php echo getFieldValue($referenciado['compromiso']); ?>
                        </div>
                    </div>
                </div>
                
                <!-- Sección 4: Insumos Asignados -->
                <div class="section-title">
                    <i class="fas fa-box-open"></i> Insumos Asignados
                </div>
                
                <div class="insumos-section">
                    <?php if (!empty($insumos_referenciado)): ?>
                        <div class="insumos-grid">
                            <?php foreach ($insumos_referenciado as $insumo): ?>
                                <div class="insumo-card">
                                    <div class="insumo-icon">
                                        <i class="fas fa-<?php 
                                            $iconos = [
                                                'carro' => 'car',
                                                'caballo' => 'horse',
                                                'cicla' => 'bicycle',
                                                'moto' => 'motorcycle',
                                                'motocarro' => 'truck-pickup',
                                                'publicidad' => 'bullhorn'
                                            ];
                                            echo $iconos[strtolower($insumo['nombre'])] ?? 'box';
                                        ?>"></i>
                                    </div>
                                    <div class="insumo-info">
                                        <div class="insumo-name"><?php echo htmlspecialchars($insumo['nombre']); ?></div>
                                        <div class="insumo-details">
                                            <?php if (!empty($insumo['cantidad'])): ?>
                                                Cantidad: <?php echo htmlspecialchars($insumo['cantidad']); ?>
                                            <?php endif; ?>
                                            <?php if (!empty($insumo['observaciones'])): ?>
                                                <br><?php echo htmlspecialchars($insumo['observaciones']); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-insumos">
                            <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 10px; opacity: 0.5;"></i>
                            <p>Este referenciado no tiene insumos asignados</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Sección 5: Información de Registro -->
                <div class="section-title">
                    <i class="fas fa-history"></i> Información de Registro
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-user-tie"></i> Referenciador
                        </label>
                        <div class="field-value">
                            <?php if ($referenciador): ?>
                                <div class="referenciador-info">
                                    <div class="referenciador-name">
                                        <i class="fas fa-user-tie"></i>
                                        <?php echo htmlspecialchars($referenciador['nombres'] . ' ' . $referenciador['apellidos']); ?>
                                    </div>
                                    <div class="timestamp-info">
                                        <i class="fas fa-user-tag"></i>
                                        <?php echo htmlspecialchars($referenciador['tipo_usuario']); ?>
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
                            <?php echo isset($referenciado['fecha_registro']) ? date('d/m/Y H:i:s', strtotime($referenciado['fecha_registro'])) : 'N/A'; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-clock"></i> Última actualización
                        </label>
                        <div class="field-value">
                            <?php echo isset($referenciado['fecha_registro']) ? date('d/m/Y H:i:s', strtotime($referenciado['fecha_registro'])) : 'N/A'; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Botones de Acción -->
                <div class="form-actions">
                    <a href="data_referidos.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i> Volver a la Lista
                    </a>
                    <a href="editar_referenciado.php?id=<?php echo $id_referenciado; ?>" class="edit-btn">
                        <i class="fas fa-edit"></i> Editar Referenciado
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>