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

header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

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

// Función para mostrar icono de sexo
function getSexoIcon($sexo) {
    $iconos = [
        'Masculino' => '<i class="fas fa-mars text-primary"></i> Masculino',
        'Femenino' => '<i class="fas fa-venus text-danger"></i> Femenino',
        'Otro' => '<i class="fas fa-transgender-alt text-info"></i> Otro'
    ];
    
    return isset($iconos[$sexo]) ? $iconos[$sexo] : '<span class="na-text">N/A</span>';
}

// Función para mostrar switch de Vota Fuera
function getVotaFueraSwitch($vota_fuera) {
    if ($vota_fuera === 'Si') {
        return '
        <div class="switch-display">
            <div class="switch-slider active">
                <span class="switch-text-on">Sí</span>
            </div>
        </div>';
    } else {
        return '
        <div class="switch-display">
            <div class="switch-slider">
                <span class="switch-text-off">No</span>
            </div>
        </div>';
    }
}

// Función para mostrar icono de afinidad con estrellas
function getAfinidadIcon($afinidad) {
    $afinidad = intval($afinidad);
    $afinidad = max(1, min(5, $afinidad)); // Asegurar que esté entre 1 y 5
    
    $html = '<div class="estrellas-afinidad">';
    
    // Estrellas llenas
    for ($i = 1; $i <= $afinidad; $i++) {
        $html .= '<i class="fas fa-star estrella-llena"></i>';
    }
    
    // Estrellas vacías
    for ($i = $afinidad + 1; $i <= 5; $i++) {
        $html .= '<i class="far fa-star estrella-vacia"></i>';
    }
    
    $html .= '<span class="valor-afinidad">' . $afinidad . '/5</span>';
    $html .= '</div>';
    
    return $html;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Referenciado - SGP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/ver_referenciado.css">
    <style>
        .switch-display {
            display: inline-block;
            position: relative;
            width: 80px;
            height: 34px;
        }
        
        .switch-slider {
            position: absolute;
            cursor: default;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #e0e0e0;
            border-radius: 34px;
            transition: .4s;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 8px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .switch-slider.active {
            background-color: #4CAF50;
        }
        
        .switch-text-on {
            color: white;
            display: none;
        }
        
        .switch-text-off {
            color: #757575;
        }
        
        .switch-slider.active .switch-text-on {
            display: inline;
        }
        
        .switch-slider.active .switch-text-off {
            display: none;
        }
        
        .switch-slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            border-radius: 50%;
            transition: .4s;
        }
        
        .switch-slider.active:before {
            transform: translateX(46px);
        }
        
        .text-primary { color: #3b82f6; }
        .text-danger { color: #ef4444; }
        .text-info { color: #06b6d4; }
        
        .voting-info-section {
            margin-bottom: 20px;
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
                        <div class="status-badges">
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
                            <i class="fas fa-venus-mars"></i> Sexo
                        </label>
                        <div class="field-value">
                            <?php echo isset($referenciado['sexo']) ? getSexoIcon($referenciado['sexo']) : '<span class="na-text">N/A</span>'; ?>
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
                </div>
                
                <!-- Sección 3: Información de Votación -->
                <div class="section-title">
                    <i class="fas fa-vote-yea"></i> Información de Votación
                </div>
                
                <!-- Mostrar información condicional según si vota fuera o no -->
                <?php if (isset($referenciado['vota_fuera']) && $referenciado['vota_fuera'] === 'Si'): ?>
                    <!-- Cuando VOTA FUERA -->
                     <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-person-booth"></i> Vota Fuera
                        </label>
                        <div class="field-value">
                            <?php echo isset($referenciado['vota_fuera']) ? getVotaFueraSwitch($referenciado['vota_fuera']) : '<span class="na-text">N/A</span>'; ?>
                        </div>
                    </div>
                    <div class="voting-info-section">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-vote-yea"></i> Puesto de votación fuera
                                </label>
                                <div class="field-value">
                                    <?php echo getFieldValue($referenciado['puesto_votacion_fuera'] ?? ''); ?>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-table"></i> Mesa fuera
                                </label>
                                <div class="field-value">
                                    <?php echo getFieldValue($referenciado['mesa_fuera'] ?? ''); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Cuando NO vota fuera -->
                    <div class="voting-info-section">
                        <div class="form-grid">
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
                    </div>
                <?php endif; ?>
                
                <!-- Sección 4: Información Adicional -->
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
                
                <!-- Sección 5: Insumos Asignados -->
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
                
                <!-- Sección 6: Información de Registro -->
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
                            <?php 
                            if (isset($referenciado['fecha_actualizacion']) && !empty($referenciado['fecha_actualizacion'])) {
                                echo date('d/m/Y H:i:s', strtotime($referenciado['fecha_actualizacion']));
                            } else if (isset($referenciado['fecha_registro'])) {
                                echo date('d/m/Y H:i:s', strtotime($referenciado['fecha_registro']));
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <!-- Botones de Acción -->
                <div class="form-actions">
                    <a href="data_referidos.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i> Volver a la Lista
                    </a>
                    <a href="editar_referenciador.php?id=<?php echo $id_referenciado; ?>" class="edit-btn">
                        <i class="fas fa-edit"></i> Editar Referenciado
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>