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
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            flex-direction: column;
        }
        
        .main-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 15px 0;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
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
            color: #2c3e50;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f8f9fa;
            padding: 8px 15px;
            border-radius: 20px;
            color: #495057;
        }
        
        .header-actions {
            display: flex;
            gap: 10px;
        }
        
        .header-btn {
            color: #495057;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            background: #f8f9fa;
            border-radius: 6px;
            transition: all 0.3s;
            font-size: 0.9rem;
            border: 1px solid #dee2e6;
        }
        
        .header-btn:hover {
            background: #e9ecef;
            color: #212529;
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }
        
        .main-container {
            flex: 1;
            max-width: 1400px;
            margin: 20px auto;
            padding: 0 20px;
            width: 100%;
        }
        
        .card-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .card-header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
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
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .header-left p {
            color: rgba(255, 255, 255, 0.9);
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
        }
        
        .status-active {
            background: rgba(39, 174, 96, 0.2);
            color: #27ae60;
        }
        
        .status-inactive {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
        }
        
        .form-sections {
            padding: 30px;
        }
        
        .section-title {
            color: #2c3e50;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title i {
            color: #3498db;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
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
            color: #495057;
            font-weight: 500;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-label i {
            color: #6c757d;
            font-size: 0.9rem;
            width: 20px;
        }
        
        .field-value {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 0.95rem;
            color: #212529;
            min-height: 46px;
            display: flex;
            align-items: center;
        }
        
        .na-text {
            color: #6c757d;
            font-style: italic;
        }
        
        .afinidad-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            font-weight: 600;
            font-size: 1rem;
            color: white;
        }
        
        .compromiso-text {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            min-height: 120px;
            color: #212529;
            line-height: 1.6;
            white-space: pre-wrap;
            overflow-y: auto;
            max-height: 200px;
        }
        
        .insumos-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin-top: 20px;
        }
        
        .insumos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .insumo-card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .insumo-card:hover {
            border-color: #3498db;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.1);
        }
        
        .insumo-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: #e3f2fd;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #3498db;
            font-size: 1.2rem;
        }
        
        .insumo-info {
            flex: 1;
        }
        
        .insumo-name {
            color: #2c3e50;
            font-weight: 500;
            font-size: 0.95rem;
            margin-bottom: 3px;
        }
        
        .insumo-details {
            color: #6c757d;
            font-size: 0.85rem;
        }
        
        .no-insumos {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            padding: 30px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 8px;
            border: 1px dashed #dee2e6;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }
        
        .edit-btn {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }
        
        .edit-btn:hover {
            background: linear-gradient(135deg, #2980b9, #1f618d);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
            color: white;
        }
        
        .back-btn {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
        }
        
        .back-btn:hover {
            background: #545b62;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            color: white;
        }
        
        .referenciador-info {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 8px;
            padding: 15px;
        }
        
        .referenciador-name {
            color: #1565c0;
            font-weight: 600;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .timestamp-info {
            color: #6c757d;
            font-size: 0.85rem;
            margin-top: 5px;
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
            color: #495057;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .info-value {
            color: #212529;
            font-size: 0.95rem;
        }
        
        @media (max-width: 768px) {
            .main-container {
                padding: 0 15px;
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
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="header-container">
            <div class="header-top">
                <div class="header-title">
                    <h1><i class="fas fa-user-circle"></i> Ver Referenciado</h1>
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
                        <h2><i class="fas fa-eye"></i> Detalle del Referenciado</h2>
                        <p>Información completa del referenciado registrado en el sistema</p>
                    </div>
                    <div class="header-right">
                        <div class="status-badge">
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
                    
                    <div class="form-group full-width">
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