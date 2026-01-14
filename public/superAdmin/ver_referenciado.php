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

// Obtener todos los insumos disponibles
$insumos_disponibles = $insumoModel->getAll();

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
            background: 
                linear-gradient(rgba(0, 0, 0, 0.8), rgba(0, 0, 0, 0.8)),
                url('/imagenes/fondo.jpg') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .main-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(30, 30, 40, 0.9);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            padding: 15px 0;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.3);
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
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.1);
            padding: 8px 15px;
            border-radius: 20px;
            backdrop-filter: blur(5px);
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
            background: rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            transition: all 0.3s;
            font-size: 0.9rem;
        }
        
        .header-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            transform: translateY(-2px);
        }
        
        .form-container {
            background: rgba(30, 30, 40, 0.85);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.5),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            width: 100%;
            max-width: 1200px;
            padding: 40px;
            animation: fadeIn 0.5s ease-out;
            margin-top: 80px;
            margin-bottom: 40px;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .form-header h2 {
            color: #ffffff;
            font-size: 1.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .form-header h2 i {
            color: #4fc3f7;
        }
        
        .form-header p {
            color: #b0bec5;
            font-size: 0.95rem;
            line-height: 1.5;
        }
        
        .user-view-info {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .user-view-info i {
            color: #4fc3f7;
            font-size: 1.5rem;
        }
        
        .user-view-info span {
            color: #ffffff;
            font-weight: 500;
        }
        
        .status-active {
            background-color: rgba(39, 174, 96, 0.2);
            color: #27ae60;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-inactive {
            background-color: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
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
            color: #cfd8dc;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-label i {
            color: #90a4ae;
            font-size: 1rem;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.3s;
            background: rgba(30, 30, 40, 0.9);
            color: #ffffff;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        .form-control:disabled {
            background-color: rgba(30, 30, 40, 0.5);
            color: #b0bec5;
            cursor: not-allowed;
            border-color: rgba(255, 255, 255, 0.05);
        }
        
        .field-value {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 0.95rem;
            color: #ffffff;
            min-height: 46px;
            display: flex;
            align-items: center;
        }
        
        .na-text {
            color: #90a4ae;
            font-style: italic;
        }
        
        .afinidad-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            font-weight: 600;
            font-size: 0.85rem;
            color: white;
        }
        
        .insumos-section {
            background: rgba(255, 255, 255, 0.05);
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
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s;
        }
        
        .insumo-card:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(79, 195, 247, 0.3);
            transform: translateY(-2px);
        }
        
        .insumo-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: rgba(79, 195, 247, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4fc3f7;
            font-size: 1.2rem;
        }
        
        .insumo-info {
            flex: 1;
        }
        
        .insumo-name {
            color: #ffffff;
            font-weight: 500;
            font-size: 0.95rem;
            margin-bottom: 3px;
        }
        
        .insumo-details {
            color: #b0bec5;
            font-size: 0.85rem;
        }
        
        .no-insumos {
            text-align: center;
            color: #90a4ae;
            font-style: italic;
            padding: 30px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 8px;
            border: 1px dashed rgba(255, 255, 255, 0.1);
        }
        
        .form-actions {
            grid-column: 1 / -1;
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .edit-btn {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-width: 200px;
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
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 15px 40px;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-width: 200px;
            text-decoration: none;
            backdrop-filter: blur(5px);
        }
        
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
            border-color: rgba(255, 255, 255, 0.3);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
            color: white;
        }
        
        .info-group {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .info-row {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .info-label {
            min-width: 120px;
            color: #cfd8dc;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .info-value {
            flex: 1;
            color: #ffffff;
            font-size: 0.95rem;
        }
        
        .compromiso-text {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 15px;
            min-height: 100px;
            color: #ffffff;
            line-height: 1.5;
            white-space: pre-wrap;
        }
        
        .referenciador-info {
            background: rgba(79, 195, 247, 0.1);
            border: 1px solid rgba(79, 195, 247, 0.2);
            border-radius: 10px;
            padding: 15px;
            margin-top: 10px;
        }
        
        .referenciador-name {
            color: #4fc3f7;
            font-weight: 600;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .timestamp-info {
            color: #90a4ae;
            font-size: 0.85rem;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        @media (max-width: 768px) {
            .form-container {
                padding: 25px;
                margin-top: 100px;
                margin-bottom: 30px;
                max-width: 95%;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .header-title h1 {
                font-size: 1.2rem;
            }
            
            .header-actions {
                flex-direction: column;
                gap: 5px;
            }
            
            .header-btn {
                padding: 6px 10px;
                font-size: 0.8rem;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .edit-btn, .back-btn {
                width: 100%;
                min-width: auto;
                padding: 12px;
            }
            
            .insumos-grid {
                grid-template-columns: 1fr;
            }
            
            .info-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
            
            .info-label {
                min-width: auto;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            
            .form-container {
                padding: 20px;
                margin-top: 90px;
                margin-bottom: 20px;
            }
            
            .form-header h2 {
                font-size: 1.5rem;
            }
            
            .user-view-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
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

    <!-- Main Form -->
    <div class="form-container">
        <div class="form-header">
            <h2><i class="fas fa-eye"></i> Detalle del Referenciado</h2>
            <p>Información completa del referenciado registrado en el sistema</p>
        </div>
        
        <!-- Información del referenciado -->
        <div class="user-view-info">
            <i class="fas fa-user"></i>
            <span>Referenciado: <strong><?php echo htmlspecialchars($referenciado['nombre'] . ' ' . $referenciado['apellido']); ?></strong></span>
            <span>Cédula: <strong><?php echo htmlspecialchars($referenciado['cedula']); ?></strong></span>
            <span><?php echo getEstadoActividad($referenciado['activo'] ?? true); ?></span>
        </div>
        
        <div class="form-grid">
            <!-- Información Personal -->
            <div class="form-group full-width">
                <div class="info-group">
                    <h3 style="color: #4fc3f7; margin-bottom: 15px; font-size: 1.2rem; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-id-card"></i> Información Personal
                    </h3>
                    
                    <div class="info-row">
                        <span class="info-label">Nombres:</span>
                        <span class="info-value"><?php echo getFieldValue($referenciado['nombre']); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Apellidos:</span>
                        <span class="info-value"><?php echo getFieldValue($referenciado['apellido']); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Cédula:</span>
                        <span class="info-value"><?php echo getFieldValue($referenciado['cedula']); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo getFieldValue($referenciado['email']); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Teléfono:</span>
                        <span class="info-value"><?php echo getFieldValue($referenciado['telefono']); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Dirección:</span>
                        <span class="info-value"><?php echo getFieldValue($referenciado['direccion']); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Información de Ubicación -->
            <div class="form-group full-width">
                <div class="info-group">
                    <h3 style="color: #4fc3f7; margin-bottom: 15px; font-size: 1.2rem; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-map-marker-alt"></i> Información de Ubicación
                    </h3>
                    
                    <div class="info-row">
                        <span class="info-label">Departamento:</span>
                        <span class="info-value"><?php echo getFieldValue($referenciado['departamento_nombre'] ?? ''); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Municipio:</span>
                        <span class="info-value"><?php echo getFieldValue($referenciado['municipio_nombre'] ?? ''); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Barrio:</span>
                        <span class="info-value"><?php echo getFieldValue($referenciado['barrio_nombre'] ?? ''); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Zona:</span>
                        <span class="info-value"><?php echo getFieldValue($referenciado['zona_nombre'] ?? ''); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Sector:</span>
                        <span class="info-value"><?php echo getFieldValue($referenciado['sector_nombre'] ?? ''); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Puesto de votación:</span>
                        <span class="info-value"><?php echo getFieldValue($referenciado['puesto_votacion_nombre'] ?? ''); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Mesa:</span>
                        <span class="info-value"><?php echo getFieldValue($referenciado['mesa']); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Información Adicional -->
            <div class="form-group full-width">
                <div class="info-group">
                    <h3 style="color: #4fc3f7; margin-bottom: 15px; font-size: 1.2rem; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-info-circle"></i> Información Adicional
                    </h3>
                    
                    <div class="info-row">
                        <span class="info-label">Afinidad:</span>
                        <span class="info-value"><?php echo getAfinidadIcon($referenciado['afinidad'] ?? 1); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Grupo poblacional:</span>
                        <span class="info-value"><?php echo getFieldValue($referenciado['grupo_poblacional_nombre'] ?? ''); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Oferta de apoyo:</span>
                        <span class="info-value"><?php echo getFieldValue($referenciado['oferta_apoyo_nombre'] ?? ''); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Compromiso:</span>
                        <div class="info-value" style="flex: 1;">
                            <div class="compromiso-text">
                                <?php echo getFieldValue($referenciado['compromiso']); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Insumos Asignados -->
            <div class="form-group full-width insumos-section">
                <h3 style="color: #4fc3f7; margin-bottom: 15px; font-size: 1.2rem; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-box-open"></i> Insumos Asignados
                </h3>
                
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
            
            <!-- Información de Registro -->
            <div class="form-group full-width">
                <div class="info-group">
                    <h3 style="color: #4fc3f7; margin-bottom: 15px; font-size: 1.2rem; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-history"></i> Información de Registro
                    </h3>
                    
                    <div class="info-row">
                        <span class="info-label">Referenciador:</span>
                        <div class="info-value">
                            <?php 
                            // Necesitarías obtener el nombre del referenciador
                            $referenciador = $usuarioModel->getUsuarioById($referenciado['id_referenciador'] ?? 0);
                            if ($referenciador): ?>
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
                    
                    <div class="info-row">
                        <span class="info-label">Fecha de registro:</span>
                        <div class="info-value">
                            <div class="timestamp-info">
                                <i class="fas fa-calendar-alt"></i>
                                <?php echo isset($referenciado['fecha_registro']) ? date('d/m/Y H:i:s', strtotime($referenciado['fecha_registro'])) : 'N/A'; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Última actualización:</span>
                        <div class="info-value">
                            <div class="timestamp-info">
                                <i class="fas fa-clock"></i>
                                <?php 
                                $fecha_actualizacion = $referenciado['fecha_registro']; // Podrías tener un campo fecha_actualizacion
                                echo date('d/m/Y H:i:s', strtotime($fecha_actualizacion)); 
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Botones de Acción -->
            <div class="form-group full-width form-actions">
                <a href="data_referidos.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Volver a la Lista
                </a>
                <a href="editar_referenciado.php?id=<?php echo $id_referenciado; ?>" class="edit-btn">
                    <i class="fas fa-edit"></i> Editar Referenciado
                </a>
            </div>
        </div>
    </div>
</body>
</html>