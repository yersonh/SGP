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
$zonaModel = new ZonaModel($pdo);
$sectorModel = new SectorModel($pdo);
$puestoModel = new PuestoVotacionModel($pdo);
$departamentoModel = new DepartamentoModel($pdo);
$municipioModel = new MunicipioModel($pdo);
$ofertaModel = new OfertaApoyoModel($pdo);
$grupoModel = new GrupoPoblacionalModel($pdo);
$barrioModel = new BarrioModel($pdo);

// Obtener datos del usuario logueado
$usuario_logueado = $usuarioModel->getUsuarioById($_SESSION['id_usuario']);

// Obtener datos completos del referenciado
$referenciado = $referenciadoModel->getReferenciadoCompleto($id_referenciado);

if (!$referenciado) {
    header('Location: data_referidos.php?error=referenciado_no_encontrado');
    exit();
}

// Obtener datos para los selects
$zonas = $zonaModel->getAll();
$sectores = $sectorModel->getAll();
$puestos = $puestoModel->getAll();
$departamentos = $departamentoModel->getAll();
$municipios = $municipioModel->getAll();
$ofertas = $ofertaModel->getAll();
$grupos = $grupoModel->getAll();
$barrios = $barrioModel->getAll();
$insumos_disponibles = $insumoModel->getAll();

// Obtener insumos del referenciado
$insumos_referenciado = $insumoModel->getInsumosByReferenciado($id_referenciado);

// Obtener información del referenciador
$referenciador = $usuarioModel->getUsuarioById($referenciado['id_referenciador'] ?? 0);

// Función para marcar un campo como seleccionado en select
function isSelected($value, $compare) {
    return $value == $compare ? 'selected' : '';
}

// Función para marcar un checkbox como checked
function isChecked($insumo_id, $insumos_referenciado) {
    foreach ($insumos_referenciado as $insumo) {
        if ($insumo['id_insumo'] == $insumo_id) {
            return 'checked';
        }
    }
    return '';
}

// Procesar el formulario cuando se envíe
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Aquí iría el código para procesar la actualización
    // Por ahora solo redirigimos
    header('Location: ver_referenciado.php?id=' . $id_referenciado . '&success=1');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Referenciado - SGP</title>
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
                linear-gradient(rgba(0, 0, 0, 0.85), rgba(0, 0, 0, 0.85));
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
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.3s;
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-light);
            backdrop-filter: blur(5px);
        }
        
        .form-control:focus {
            outline: none;
            border-color: rgba(79, 195, 247, 0.5);
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 3px rgba(79, 195, 247, 0.1);
        }
        
        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2390a4ae' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            padding-right: 40px;
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
            line-height: 1.5;
        }
        
        .na-text {
            color: #90a4ae;
            font-style: italic;
        }
        
        .estrellas-afinidad {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .estrellas-afinidad .estrella {
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.2s;
            color: #666;
        }
        
        .estrellas-afinidad .estrella:hover,
        .estrellas-afinidad .estrella.active {
            color: #FFD700;
            transform: scale(1.2);
        }
        
        .valor-afinidad {
            margin-left: 10px;
            color: #FFD700;
            font-weight: 600;
            font-size: 0.9rem;
            background: rgba(255, 215, 0, 0.1);
            padding: 2px 8px;
            border-radius: 4px;
            border: 1px solid rgba(255, 215, 0, 0.2);
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
        
        .insumo-checkbox {
            display: none;
        }
        
        .insumo-label {
            display: block;
            cursor: pointer;
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
        
        .insumo-checkbox:checked + .insumo-label .insumo-card {
            border-color: rgba(79, 195, 247, 0.5);
            background: rgba(79, 195, 247, 0.1);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(79, 195, 247, 0.2);
        }
        
        .insumo-card:hover {
            border-color: rgba(79, 195, 247, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(79, 195, 247, 0.1);
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
        
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 40px;
            padding-top: 25px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .save-btn {
            background: linear-gradient(135deg, #27ae60, #219653);
            color: white;
            border: none;
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
        
        .save-btn:hover {
            background: linear-gradient(135deg, #219653, #1e874b);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(39, 174, 96, 0.3);
        }
        
        .cancel-btn {
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
        
        .cancel-btn:hover {
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
            
            .save-btn, .cancel-btn {
                width: 100%;
                padding: 12px;
            }
            
            .insumos-grid {
                grid-template-columns: 1fr;
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
        /* Estilos para insumos asignados */
.insumo-card-asignado {
    background: rgba(79, 195, 247, 0.1);
    border: 1px solid rgba(79, 195, 247, 0.3);
    border-radius: 10px;
    padding: 18px;
    display: flex;
    align-items: center;
    gap: 12px;
    transition: all 0.3s;
    backdrop-filter: blur(10px);
    position: relative;
}

.insumo-card-asignado:hover {
    background: rgba(79, 195, 247, 0.15);
    border-color: rgba(79, 195, 247, 0.5);
    transform: translateY(-2px);
}

.insumo-actions {
    margin-left: auto;
}

.btn-remove-insumo {
    background: rgba(231, 76, 60, 0.2);
    color: #e74c3c;
    border: 1px solid rgba(231, 76, 60, 0.3);
    border-radius: 6px;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-remove-insumo:hover {
    background: rgba(231, 76, 60, 0.3);
    transform: scale(1.1);
}

/* Estilos para insumos nuevos */
.insumo-item-nuevo {
    position: relative;
}

.insumo-check-indicator {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 20px;
    height: 20px;
    background: rgba(39, 174, 96, 0.2);
    border: 1px solid rgba(39, 174, 96, 0.3);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #27ae60;
    font-size: 0.7rem;
    opacity: 0;
    transition: all 0.3s;
}

.insumo-checkbox:checked + .insumo-label .insumo-check-indicator {
    opacity: 1;
}

.insumo-checkbox:checked + .insumo-label .insumo-card {
    border-color: rgba(39, 174, 96, 0.5);
    background: rgba(39, 174, 96, 0.1);
}

.insumo-checkbox:checked + .insumo-label + .insumo-details-form {
    display: block;
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
    </style>
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="header-container">
            <div class="header-top">
                <div class="header-title">
                    <h1><i class="fas fa-edit"></i> Editar Referenciado</h1>
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span>Super Admin: <?php echo htmlspecialchars($usuario_logueado['nombres'] . ' ' . $usuario_logueado['apellidos']); ?></span>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="ver_referenciado.php?id=<?php echo $id_referenciado; ?>" class="header-btn">
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
        <form method="POST" action="editar_referenciado.php?id=<?php echo $id_referenciado; ?>">
            <div class="card-container">
                <!-- Card Header -->
                <div class="card-header">
                    <div class="header-content">
                        <div class="header-left">
                            <h2><i class="fas fa-user-edit"></i> Editar Referenciado</h2>
                            <p>Modifique la información del referenciado en el sistema</p>
                        </div>
                        <div class="header-right">
                            <div class="status-badge status-active">
                                <i class="fas fa-check-circle"></i> Activo
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
                                <i class="fas fa-user"></i> Nombres *
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   name="nombre" 
                                   value="<?php echo htmlspecialchars($referenciado['nombre'] ?? ''); ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-user"></i> Apellidos *
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   name="apellido" 
                                   value="<?php echo htmlspecialchars($referenciado['apellido'] ?? ''); ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-id-card"></i> Cédula *
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   name="cedula" 
                                   value="<?php echo htmlspecialchars($referenciado['cedula'] ?? ''); ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-envelope"></i> Email
                            </label>
                            <input type="email" 
                                   class="form-control" 
                                   name="email" 
                                   value="<?php echo htmlspecialchars($referenciado['email'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-phone"></i> Teléfono
                            </label>
                            <input type="tel" 
                                   class="form-control" 
                                   name="telefono" 
                                   value="<?php echo htmlspecialchars($referenciado['telefono'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-home"></i> Dirección
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   name="direccion" 
                                   value="<?php echo htmlspecialchars($referenciado['direccion'] ?? ''); ?>">
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
                            <select class="form-control" name="id_departamento">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($departamentos as $departamento): ?>
                                    <option value="<?php echo $departamento['id_departamento']; ?>" 
                                        <?php echo isSelected($departamento['id_departamento'], $referenciado['id_departamento'] ?? ''); ?>>
                                        <?php echo htmlspecialchars($departamento['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-city"></i> Municipio
                            </label>
                            <select class="form-control" name="id_municipio">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($municipios as $municipio): ?>
                                    <option value="<?php echo $municipio['id_municipio']; ?>" 
                                        <?php echo isSelected($municipio['id_municipio'], $referenciado['id_municipio'] ?? ''); ?>>
                                        <?php echo htmlspecialchars($municipio['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-map"></i> Barrio
                            </label>
                            <select class="form-control" name="id_barrio">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($barrios as $barrio): ?>
                                    <option value="<?php echo $barrio['id_barrio']; ?>" 
                                        <?php echo isSelected($barrio['id_barrio'], $referenciado['id_barrio'] ?? ''); ?>>
                                        <?php echo htmlspecialchars($barrio['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-compass"></i> Zona
                            </label>
                            <select class="form-control" name="id_zona">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($zonas as $zona): ?>
                                    <option value="<?php echo $zona['id_zona']; ?>" 
                                        <?php echo isSelected($zona['id_zona'], $referenciado['id_zona'] ?? ''); ?>>
                                        <?php echo htmlspecialchars($zona['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-th-large"></i> Sector
                            </label>
                            <select class="form-control" name="id_sector">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($sectores as $sector): ?>
                                    <option value="<?php echo $sector['id_sector']; ?>" 
                                        <?php echo isSelected($sector['id_sector'], $referenciado['id_sector'] ?? ''); ?>>
                                        <?php echo htmlspecialchars($sector['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-vote-yea"></i> Puesto de votación
                            </label>
                            <select class="form-control" name="id_puesto_votacion">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($puestos as $puesto): ?>
                                    <option value="<?php echo $puesto['id_puesto']; ?>" 
                                        <?php echo isSelected($puesto['id_puesto'], $referenciado['id_puesto_votacion'] ?? ''); ?>>
                                        <?php echo htmlspecialchars($puesto['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-table"></i> Mesa
                            </label>
                            <input type="number" 
                                   class="form-control" 
                                   name="mesa" 
                                   min="1" 
                                   value="<?php echo htmlspecialchars($referenciado['mesa'] ?? ''); ?>">
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
                            <div class="estrellas-afinidad" id="afinidad-estrellas">
                                <input type="hidden" name="afinidad" id="afinidad-valor" value="<?php echo $referenciado['afinidad'] ?? 1; ?>">
                                <i class="fas fa-star estrella" data-value="1"></i>
                                <i class="fas fa-star estrella" data-value="2"></i>
                                <i class="fas fa-star estrella" data-value="3"></i>
                                <i class="fas fa-star estrella" data-value="4"></i>
                                <i class="fas fa-star estrella" data-value="5"></i>
                                <span class="valor-afinidad" id="valor-afinidad"><?php echo ($referenciado['afinidad'] ?? 1); ?>/5</span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-users"></i> Grupo poblacional
                            </label>
                            <select class="form-control" name="id_grupo_poblacional">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($grupos as $grupo): ?>
                                    <option value="<?php echo $grupo['id_grupo']; ?>" 
                                        <?php echo isSelected($grupo['id_grupo'], $referenciado['id_grupo_poblacional'] ?? ''); ?>>
                                        <?php echo htmlspecialchars($grupo['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-hands-helping"></i> Oferta de apoyo
                            </label>
                            <select class="form-control" name="id_oferta_apoyo">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($ofertas as $oferta): ?>
                                    <option value="<?php echo $oferta['id_oferta']; ?>" 
                                        <?php echo isSelected($oferta['id_oferta'], $referenciado['id_oferta_apoyo'] ?? ''); ?>>
                                        <?php echo htmlspecialchars($oferta['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group full-width">
                            <label class="form-label">
                                <i class="fas fa-comment-alt"></i> Compromiso
                            </label>
                            <textarea class="form-control" 
                                      name="compromiso" 
                                      rows="4"><?php echo htmlspecialchars($referenciado['compromiso'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <!-- Sección 4: Insumos Asignados -->
                    <div class="section-title">
                        <i class="fas fa-box-open"></i> Insumos Asignados
                    </div>

                    <div class="insumos-section">
                        <!-- Subsección: Insumos Actuales -->
                        <div style="margin-bottom: 30px;">
                            <h4 style="color: #4fc3f7; margin-bottom: 15px; font-size: 1.1rem; display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-check-circle"></i> Insumos Actualmente Asignados
                                <span style="background: rgba(79, 195, 247, 0.2); color: #4fc3f7; padding: 2px 8px; border-radius: 12px; font-size: 0.8rem; margin-left: 10px;">
                                    <?php echo count($insumos_referenciado); ?> asignados
                                </span>
                            </h4>
                            
                            <?php if (!empty($insumos_referenciado)): ?>
                                <div class="insumos-grid">
                                    <?php foreach ($insumos_referenciado as $insumo): ?>
                                        <div class="insumo-card-asignado">
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
                                                        <span style="color: #90a4ae; font-size: 0.85rem;">
                                                            <i class="fas fa-hashtag"></i> Cantidad: <?php echo htmlspecialchars($insumo['cantidad']); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($insumo['observaciones'])): ?>
                                                        <div style="color: #90a4ae; font-size: 0.85rem; margin-top: 4px;">
                                                            <i class="fas fa-sticky-note"></i> <?php echo htmlspecialchars($insumo['observaciones']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="insumo-actions">
                                                <button type="button" class="btn-remove-insumo" 
                                                        data-insumo-id="<?php echo $insumo['id_insumo']; ?>"
                                                        title="Quitar insumo">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <input type="hidden" name="insumos_asignados[]" value="<?php echo $insumo['id_insumo']; ?>">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="no-insumos" style="text-align: center; padding: 20px; background: rgba(255, 255, 255, 0.03); border-radius: 8px;">
                                    <i class="fas fa-inbox" style="font-size: 2rem; color: #90a4ae; margin-bottom: 10px;"></i>
                                    <p style="color: #90a4ae;">Este referenciado no tiene insumos asignados</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Subsección: Agregar Nuevos Insumos -->
                        <div>
                            <h4 style="color: #4fc3f7; margin-bottom: 15px; font-size: 1.1rem; display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-plus-circle"></i> Agregar Nuevos Insumos
                                <span style="background: rgba(39, 174, 96, 0.2); color: #27ae60; padding: 2px 8px; border-radius: 12px; font-size: 0.8rem; margin-left: 10px;">
                                    <?php echo count($insumos_disponibles); ?> disponibles
                                </span>
                            </h4>
                            
                            <div class="insumos-grid">
                                <?php 
                                // Filtrar insumos que ya están asignados
                                $insumos_asignados_ids = array_column($insumos_referenciado, 'id_insumo');
                                ?>
                                
                                <?php foreach ($insumos_disponibles as $insumo): ?>
                                    <?php if (!in_array($insumo['id_insumo'], $insumos_asignados_ids)): ?>
                                        <div class="insumo-item-nuevo">
                                            <input type="checkbox" 
                                                class="insumo-checkbox" 
                                                id="insumo_<?php echo $insumo['id_insumo']; ?>" 
                                                name="insumos_nuevos[]" 
                                                value="<?php echo $insumo['id_insumo']; ?>">
                                            <label class="insumo-label" for="insumo_<?php echo $insumo['id_insumo']; ?>">
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
                                                    </div>
                                                    <div class="insumo-check-indicator">
                                                        <i class="fas fa-check-circle"></i>
                                                    </div>
                                                </div>
                                            </label>
                                            <!-- Campos adicionales para cantidad y observaciones -->
                                            <div class="insumo-details-form" style="display: none; margin-top: 10px; padding: 10px; background: rgba(255, 255, 255, 0.05); border-radius: 8px;">
                                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                                    <div>
                                                        <label style="font-size: 0.8rem; color: #90a4ae; display: block; margin-bottom: 5px;">
                                                            <i class="fas fa-hashtag"></i> Cantidad
                                                        </label>
                                                        <input type="number" 
                                                            name="cantidad_<?php echo $insumo['id_insumo']; ?>" 
                                                            min="1" 
                                                            value="1"
                                                            class="form-control" 
                                                            style="padding: 6px 10px; font-size: 0.9rem;">
                                                    </div>
                                                    <div>
                                                        <label style="font-size: 0.8rem; color: #90a4ae; display: block; margin-bottom: 5px;">
                                                            <i class="fas fa-sticky-note"></i> Observaciones
                                                        </label>
                                                        <input type="text" 
                                                            name="observaciones_<?php echo $insumo['id_insumo']; ?>" 
                                                            class="form-control" 
                                                            style="padding: 6px 10px; font-size: 0.9rem;"
                                                            placeholder="Opcional">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                
                                <?php if (count($insumos_disponibles) == count($insumos_asignados_ids)): ?>
                                    <div style="grid-column: 1 / -1; text-align: center; padding: 20px;">
                                        <i class="fas fa-check-circle" style="font-size: 2rem; color: #27ae60; margin-bottom: 10px;"></i>
                                        <p style="color: #90a4ae;">Todos los insumos disponibles ya están asignados</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Botones de Acción -->
                    <div class="form-actions">
                        <a href="ver_referenciado.php?id=<?php echo $id_referenciado; ?>" class="cancel-btn">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                        <button type="submit" class="save-btn">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        // Script para las estrellas de afinidad
        document.addEventListener('DOMContentLoaded', function() {
            const estrellas = document.querySelectorAll('.estrellas-afinidad .estrella');
            const valorInput = document.getElementById('afinidad-valor');
            const valorSpan = document.getElementById('valor-afinidad');
            
            // Establecer estrellas iniciales
            const valorInicial = parseInt(valorInput.value);
            actualizarEstrellas(valorInicial);
            
            // Agregar event listeners a las estrellas
            estrellas.forEach(estrella => {
                estrella.addEventListener('click', function() {
                    const valor = parseInt(this.getAttribute('data-value'));
                    valorInput.value = valor;
                    actualizarEstrellas(valor);
                });
            });
            
            function actualizarEstrellas(valor) {
                estrellas.forEach(estrella => {
                    const estrellaValor = parseInt(estrella.getAttribute('data-value'));
                    if (estrellaValor <= valor) {
                        estrella.classList.add('active');
                        estrella.classList.remove('far');
                        estrella.classList.add('fas');
                    } else {
                        estrella.classList.remove('active');
                        estrella.classList.remove('fas');
                        estrella.classList.add('far');
                    }
                });
                valorSpan.textContent = valor + '/5';
            }
            
            // Validación del formulario
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                // Aquí podrías agregar validaciones adicionales
                console.log('Formulario enviado');
            });
        });
    </script>
</body>
</html>