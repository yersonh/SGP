<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../models/SistemaModel.php';
require_once __DIR__ . '/../models/BarrioModel.php';
require_once __DIR__ . '/../models/GrupoPoblacionalModel.php';
require_once __DIR__ . '/../models/DepartamentoModel.php';
require_once __DIR__ . '/../models/MunicipioModel.php';
require_once __DIR__ . '/../models/OfertaApoyoModel.php';
require_once __DIR__ . '/../models/PuestoVotacionModel.php';
require_once __DIR__ . '/../models/ZonaModel.php';
require_once __DIR__ . '/../models/SectorModel.php';

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Verificar si el usuario está logueado y es Administrador
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'Administrador') {
    header('location: index.php');
    exit();
}

$pdo = Database::getConnection();
$usuarioModel = new UsuarioModel($pdo);
$sistemaModel = new SistemaModel($pdo);
$barrioModel = new BarrioModel($pdo);
$grupoPoblacionalModel = new GrupoPoblacionalModel($pdo);
$departamentoModel = new DepartamentoModel($pdo);
$municipioModel = new MunicipioModel($pdo);
$ofertaApoyoModel = new OfertaApoyoModel($pdo);
$puestoVotacionModel = new PuestoVotacionModel($pdo);
$zonaModel = new ZonaModel($pdo);
$sectorModel = new SectorModel($pdo);

$id_usuario_logueado = $_SESSION['id_usuario'];

// Actualizar último registro
$fecha_actual = date('Y-m-d H:i:s');
$usuarioModel->actualizarUltimoRegistro($id_usuario_logueado, $fecha_actual);

// Obtener datos del usuario logueado
$usuario_logueado = $usuarioModel->getUsuarioById($id_usuario_logueado);

// Obtener todos los datos para cada sección
$barrios = $barrioModel->getAll();
$gruposPoblacionales = $grupoPoblacionalModel->getAll();
$departamentos = $departamentoModel->getAll();
$municipios = $municipioModel->getAll();
$ofertasApoyo = $ofertaApoyoModel->getAll();
$puestosVotacion = $puestoVotacionModel->getAll();
$zonas = $zonaModel->getAll();
$sectores = $sectorModel->getAll();

// Obtener información del sistema
$licenciaInfo = $sistemaModel->getInfoCompletaLicencia();
$diasRestantes = $licenciaInfo['dias_restantes'];
$validaHastaFormatted = $licenciaInfo['valida_hasta_formatted'];
$fechaInstalacionFormatted = $licenciaInfo['fecha_instalacion_formatted'];
$porcentajeRestante = $sistemaModel->getPorcentajeRestanteLicencia();

// Color de la barra de licencia
if ($porcentajeRestante > 50) {
    $barColor = 'bg-success';
} elseif ($porcentajeRestante > 25) {
    $barColor = 'bg-warning';
} else {
    $barColor = 'bg-danger';
}

// Determinar qué sección mostrar (por defecto barrios)
$seccion_activa = isset($_GET['seccion']) ? $_GET['seccion'] : 'barrios';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parametrización - Administrador - SGP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }

        .dashboard-container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header Styles */
        .main-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #34495e 100%);
            color: white;
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
            flex-wrap: wrap;
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .header-title h1 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-title h1 i {
            color: var(--secondary-color);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255,255,255,0.1);
            padding: 8px 15px;
            border-radius: 20px;
        }

        .user-info i {
            font-size: 1.5rem;
            color: var(--secondary-color);
        }

        .logout-btn {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 0.9rem;
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.2);
            color: white;
        }

        /* Sidebar Styles */
        .sidebar {
            background: white;
            width: 280px;
            min-height: calc(100vh - 80px);
            position: fixed;
            left: 0;
            top: 80px;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 999;
            overflow-y: auto;
            padding: 15px 0 20px 0;
        }

        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid #eee;
            margin-bottom: 20px;
        }

        .sidebar-header h3 {
            color: var(--primary-color);
            font-size: 1.3rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-header h3 i {
            color: var(--secondary-color);
        }

        .nav-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-item {
            margin-bottom: 5px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: var(--dark-color);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }

        .nav-link:hover {
            background-color: #f8f9fa;
            color: var(--secondary-color);
            border-left-color: var(--secondary-color);
        }

        .nav-link.active {
            background-color: #e3f2fd;
            color: var(--secondary-color);
            border-left-color: var(--secondary-color);
            font-weight: 600;
        }

        .nav-link i {
            width: 24px;
            margin-right: 12px;
            font-size: 1.2rem;
        }

        .nav-link .badge {
            margin-left: auto;
            background-color: var(--secondary-color);
            font-size: 0.7rem;
            padding: 3px 6px;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
            background-color: #f8f9fa;
            min-height: calc(100vh - 80px);
        }

        /* Dashboard Header */
        .dashboard-header {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .dashboard-title {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .dashboard-title i {
            font-size: 2rem;
            color: var(--secondary-color);
        }

        .dashboard-title h2 {
            margin: 0;
            color: var(--primary-color);
            font-weight: 600;
        }

        .dashboard-title p {
            margin: 5px 0 0 0;
            color: #666;
            font-size: 0.95rem;
        }

        /* Tabs de navegación */
        .param-tabs {
            background: white;
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .param-tab {
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            color: #666;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            border: 1px solid transparent;
        }

        .param-tab:hover {
            background-color: #f0f7ff;
            color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .param-tab.active {
            background: linear-gradient(135deg, var(--secondary-color) 0%, #2980b9 100%);
            color: white;
        }

        .param-tab i {
            font-size: 1.1rem;
        }

        .param-tab .badge {
            background: rgba(255,255,255,0.2);
            color: white;
            margin-left: 5px;
        }

        /* Cards de contenido */
        .param-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .param-header {
            padding: 20px 25px;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        }

        .param-header h3 {
            margin: 0;
            color: var(--primary-color);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .param-header h3 i {
            color: var(--secondary-color);
        }

        .btn-agregar {
            background: linear-gradient(135deg, var(--success-color) 0%, #229954 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn-agregar:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
            color: white;
        }

        .btn-agregar i {
            font-size: 1rem;
        }

        /* Tabla de datos */
        .table-container {
            padding: 20px 25px;
            overflow-x: auto;
        }

        .param-table {
            width: 100%;
            border-collapse: collapse;
        }

        .param-table th {
            background-color: #f8f9fa;
            color: var(--primary-color);
            font-weight: 600;
            padding: 15px 10px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
        }

        .param-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #eee;
            color: #444;
        }

        .param-table tbody tr:hover {
            background-color: #f5f9ff;
        }

        .acciones-cell {
            display: flex;
            gap: 8px;
        }

        .btn-accion {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }

        .btn-editar {
            background-color: #e3f2fd;
            color: var(--secondary-color);
        }

        .btn-editar:hover {
            background-color: var(--secondary-color);
            color: white;
        }

        .btn-eliminar {
            background-color: #ffebee;
            color: var(--danger-color);
        }

        .btn-eliminar:hover {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-ver {
            background-color: #e8f5e9;
            color: var(--success-color);
        }

        .btn-ver:hover {
            background-color: var(--success-color);
            color: white;
        }

        /* Badges de estado */
        .badge-activo {
            background-color: #d4edda;
            color: #155724;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-inactivo {
            background-color: #f8d7da;
            color: #721c24;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        /* Footer */
        .system-footer {
            text-align: center;
            padding: 25px 0;
            background: var(--color-fondo-secundario);
            color: var(--color-texto);
            font-size: 0.9rem;
            line-height: 1.6;
            border-top: 2px solid var(--color-borde);
            width: 100%;
            margin-left: 280px;
            width: calc(100% - 280px);
        }

        .container.text-center.mb-3 img {
            max-width: 320px;
            height: auto;
            transition: all 0.3s ease;
            cursor: pointer;
            filter: brightness(1);
        }

        .container.text-center.mb-3 img:hover {
            transform: scale(1.05);
            filter: brightness(1.1);
        }

        /* Modal styles */
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

        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                width: 100%;
                position: relative;
                top: 0;
                min-height: auto;
                margin-bottom: 20px;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .system-footer {
                margin-left: 0;
                width: 100%;
            }
            
            .param-tabs {
                justify-content: center;
            }
        }

        @media (max-width: 768px) {
            .header-top {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .header-title {
                flex-direction: column;
            }
            
            .param-header {
                flex-direction: column;
                text-align: center;
            }
            
            .param-table {
                font-size: 0.9rem;
            }
            
            .acciones-cell {
                flex-wrap: wrap;
                justify-content: center;
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
                    <h1><i class="fas fa-cogs"></i> Panel de Administración - SGP</h1>
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span>Administrador del Sistema</span>
                    </div>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
        </div>
    </header>

    <!-- Dashboard Container -->
    <div class="dashboard-container">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-bars"></i> Menú Principal</h3>
            </div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="administrador_dashboard.php" class="nav-link">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="gestion_usuarios.php" class="nav-link">
                        <i class="fas fa-users-cog"></i> Gestión de Usuarios
                    </a>
                </li>
                <li class="nav-item">
                    <a href="agregar_usuario.php" class="nav-link">
                        <i class="fas fa-user-plus"></i> Agregar Usuario
                    </a>
                </li>
                <li class="nav-item">
                    <a href="administrador/anadir_lider.php" class="nav-link">
                        <i class="fas fa-user-tie"></i> Agregar Líder
                    </a>
                </li>
                <li class="nav-item">
                    <a href="gestion_lideres.php" class="nav-link">
                        <i class="fas fa-people-carry"></i> Gestión de Líderes
                    </a>
                </li>
                <li class="nav-item">
                    <a href="parametrizacion.php" class="nav-link active">
                        <i class="fas fa-sliders-h"></i> Parametrización
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Dashboard Header -->
            <div class="dashboard-header">
                <div class="dashboard-title">
                    <i class="fas fa-sliders-h"></i>
                    <div>
                        <h2>Parametrización del Sistema</h2>
                        <p>Administración de catálogos y configuraciones del sistema</p>
                    </div>
                </div>
            </div>

            <!-- Tabs de navegación -->
            <div class="param-tabs">
                <a href="?seccion=barrios" class="param-tab <?php echo $seccion_activa == 'barrios' ? 'active' : ''; ?>">
                    <i class="fas fa-map-pin"></i> Barrios
                    <span class="badge bg-secondary"><?php echo count($barrios); ?></span>
                </a>
                <a href="?seccion=grupos" class="param-tab <?php echo $seccion_activa == 'grupos' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> Grupos Poblacionales
                    <span class="badge bg-secondary"><?php echo count($gruposPoblacionales); ?></span>
                </a>
                <a href="?seccion=departamentos" class="param-tab <?php echo $seccion_activa == 'departamentos' ? 'active' : ''; ?>">
                    <i class="fas fa-map-marked-alt"></i> Departamentos
                    <span class="badge bg-secondary"><?php echo count($departamentos); ?></span>
                </a>
                <a href="?seccion=municipios" class="param-tab <?php echo $seccion_activa == 'municipios' ? 'active' : ''; ?>">
                    <i class="fas fa-city"></i> Municipios
                    <span class="badge bg-secondary"><?php echo count($municipios); ?></span>
                </a>
                <a href="?seccion=ofertas" class="param-tab <?php echo $seccion_activa == 'ofertas' ? 'active' : ''; ?>">
                    <i class="fas fa-hand-holding-heart"></i> Oferta Apoyo
                    <span class="badge bg-secondary"><?php echo count($ofertasApoyo); ?></span>
                </a>
                <a href="?seccion=puestos" class="param-tab <?php echo $seccion_activa == 'puestos' ? 'active' : ''; ?>">
                    <i class="fas fa-vote-yea"></i> Puestos de Votación
                    <span class="badge bg-secondary"><?php echo count($puestosVotacion); ?></span>
                </a>
                <a href="?seccion=zonas" class="param-tab <?php echo $seccion_activa == 'zonas' ? 'active' : ''; ?>">
                    <i class="fas fa-map-marker-alt"></i> Zonas
                    <span class="badge bg-secondary"><?php echo count($zonas); ?></span>
                </a>
                <a href="?seccion=sectores" class="param-tab <?php echo $seccion_activa == 'sectores' ? 'active' : ''; ?>">
                    <i class="fas fa-th-large"></i> Sectores
                    <span class="badge bg-secondary"><?php echo count($sectores); ?></span>
                </a>
            </div>

            <!-- Contenido según sección activa -->
            <div class="param-card">
                <?php
                // BARRIOS
                if ($seccion_activa == 'barrios'): 
                ?>
                    <div class="param-header">
                        <h3><i class="fas fa-map-pin"></i> Administración de Barrios</h3>
                        <a href="#" class="btn-agregar" data-bs-toggle="modal" data-bs-target="#modalBarrio">
                            <i class="fas fa-plus"></i> Nuevo Barrio
                        </a>
                    </div>
                    <div class="table-container">
                        <table class="param-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre del Barrio</th>
                                    <th>Municipio</th>
                                    <th>Estado</th>
                                    <th>Fecha Creación</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($barrios as $barrio): ?>
                                <tr>
                                    <td>#<?php echo $barrio['id_barrio']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($barrio['nombre']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($barrio['municipio_nombre'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="<?php echo ($barrio['activo'] ?? 1) ? 'badge-activo' : 'badge-inactivo'; ?>">
                                            <?php echo ($barrio['activo'] ?? 1) ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($barrio['fecha_creacion'])); ?></td>
                                    <td>
                                        <div class="acciones-cell">
                                            <a href="#" class="btn-accion btn-editar" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="#" class="btn-accion btn-eliminar" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            <a href="#" class="btn-accion btn-ver" title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($barrios)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-info-circle me-2"></i> No hay barrios registrados
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                <?php
                // GRUPOS POBLACIONALES
                elseif ($seccion_activa == 'grupos'): 
                ?>
                    <div class="param-header">
                        <h3><i class="fas fa-users"></i> Administración de Grupos Poblacionales</h3>
                        <a href="#" class="btn-agregar" data-bs-toggle="modal" data-bs-target="#modalGrupo">
                            <i class="fas fa-plus"></i> Nuevo Grupo
                        </a>
                    </div>
                    <div class="table-container">
                        <table class="param-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre del Grupo</th>
                                    <th>Descripción</th>
                                    <th>Estado</th>
                                    <th>Fecha Creación</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($gruposPoblacionales as $grupo): ?>
                                <tr>
                                    <td>#<?php echo $grupo['id_grupo']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($grupo['nombre']); ?></strong></td>
                                    <td><?php echo htmlspecialchars(substr($grupo['descripcion'] ?? '', 0, 50)) . '...'; ?></td>
                                    <td>
                                        <span class="<?php echo ($grupo['activo'] ?? 1) ? 'badge-activo' : 'badge-inactivo'; ?>">
                                            <?php echo ($grupo['activo'] ?? 1) ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($grupo['fecha_creacion'])); ?></td>
                                    <td>
                                        <div class="acciones-cell">
                                            <a href="#" class="btn-accion btn-editar"><i class="fas fa-edit"></i></a>
                                            <a href="#" class="btn-accion btn-eliminar"><i class="fas fa-trash"></i></a>
                                            <a href="#" class="btn-accion btn-ver"><i class="fas fa-eye"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($gruposPoblacionales)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-info-circle me-2"></i> No hay grupos poblacionales registrados
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                <?php
                // DEPARTAMENTOS
                elseif ($seccion_activa == 'departamentos'): 
                ?>
                    <div class="param-header">
                        <h3><i class="fas fa-map-marked-alt"></i> Administración de Departamentos</h3>
                        <a href="#" class="btn-agregar" data-bs-toggle="modal" data-bs-target="#modalDepartamento">
                            <i class="fas fa-plus"></i> Nuevo Departamento
                        </a>
                    </div>
                    <div class="table-container">
                        <table class="param-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Código</th>
                                    <th>Nombre del Departamento</th>
                                    <th>Estado</th>
                                    <th>Fecha Creación</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($departamentos as $depto): ?>
                                <tr>
                                    <td>#<?php echo $depto['id_departamento']; ?></td>
                                    <td><code><?php echo htmlspecialchars($depto['codigo'] ?? ''); ?></code></td>
                                    <td><strong><?php echo htmlspecialchars($depto['nombre']); ?></strong></td>
                                    <td>
                                        <span class="<?php echo ($depto['activo'] ?? 1) ? 'badge-activo' : 'badge-inactivo'; ?>">
                                            <?php echo ($depto['activo'] ?? 1) ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($depto['fecha_creacion'])); ?></td>
                                    <td>
                                        <div class="acciones-cell">
                                            <a href="#" class="btn-accion btn-editar"><i class="fas fa-edit"></i></a>
                                            <a href="#" class="btn-accion btn-eliminar"><i class="fas fa-trash"></i></a>
                                            <a href="#" class="btn-accion btn-ver"><i class="fas fa-eye"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($departamentos)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-info-circle me-2"></i> No hay departamentos registrados
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                <?php
                // MUNICIPIOS
                elseif ($seccion_activa == 'municipios'): 
                ?>
                    <div class="param-header">
                        <h3><i class="fas fa-city"></i> Administración de Municipios</h3>
                        <a href="#" class="btn-agregar" data-bs-toggle="modal" data-bs-target="#modalMunicipio">
                            <i class="fas fa-plus"></i> Nuevo Municipio
                        </a>
                    </div>
                    <div class="table-container">
                        <table class="param-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Municipio</th>
                                    <th>Departamento</th>
                                    <th>Código DANE</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($municipios as $municipio): ?>
                                <tr>
                                    <td>#<?php echo $municipio['id_municipio']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($municipio['nombre']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($municipio['departamento_nombre'] ?? ''); ?></td>
                                    <td><code><?php echo htmlspecialchars($municipio['codigo_dane'] ?? ''); ?></code></td>
                                    <td>
                                        <span class="<?php echo ($municipio['activo'] ?? 1) ? 'badge-activo' : 'badge-inactivo'; ?>">
                                            <?php echo ($municipio['activo'] ?? 1) ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="acciones-cell">
                                            <a href="#" class="btn-accion btn-editar"><i class="fas fa-edit"></i></a>
                                            <a href="#" class="btn-accion btn-eliminar"><i class="fas fa-trash"></i></a>
                                            <a href="#" class="btn-accion btn-ver"><i class="fas fa-eye"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($municipios)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-info-circle me-2"></i> No hay municipios registrados
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                <?php
                // OFERTA APOYO
                elseif ($seccion_activa == 'ofertas'): 
                ?>
                    <div class="param-header">
                        <h3><i class="fas fa-hand-holding-heart"></i> Administración de Ofertas de Apoyo</h3>
                        <a href="#" class="btn-agregar" data-bs-toggle="modal" data-bs-target="#modalOferta">
                            <i class="fas fa-plus"></i> Nueva Oferta
                        </a>
                    </div>
                    <div class="table-container">
                        <table class="param-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre de la Oferta</th>
                                    <th>Descripción</th>
                                    <th>Tipo</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ofertasApoyo as $oferta): ?>
                                <tr>
                                    <td>#<?php echo $oferta['id_oferta']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($oferta['nombre']); ?></strong></td>
                                    <td><?php echo htmlspecialchars(substr($oferta['descripcion'] ?? '', 0, 50)) . '...'; ?></td>
                                    <td>
                                        <span class="badge bg-info text-white">
                                            <?php echo htmlspecialchars($oferta['tipo'] ?? 'General'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="<?php echo ($oferta['activo'] ?? 1) ? 'badge-activo' : 'badge-inactivo'; ?>">
                                            <?php echo ($oferta['activo'] ?? 1) ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="acciones-cell">
                                            <a href="#" class="btn-accion btn-editar"><i class="fas fa-edit"></i></a>
                                            <a href="#" class="btn-accion btn-eliminar"><i class="fas fa-trash"></i></a>
                                            <a href="#" class="btn-accion btn-ver"><i class="fas fa-eye"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($ofertasApoyo)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-info-circle me-2"></i> No hay ofertas de apoyo registradas
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                <?php
                // PUESTOS DE VOTACIÓN
                elseif ($seccion_activa == 'puestos'): 
                ?>
                    <div class="param-header">
                        <h3><i class="fas fa-vote-yea"></i> Administración de Puestos de Votación</h3>
                        <a href="#" class="btn-agregar" data-bs-toggle="modal" data-bs-target="#modalPuesto">
                            <i class="fas fa-plus"></i> Nuevo Puesto
                        </a>
                    </div>
                    <div class="table-container">
                        <table class="param-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre del Puesto</th>
                                    <th>Dirección</th>
                                    <th>Municipio</th>
                                    <th>Mesas</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($puestosVotacion as $puesto): ?>
                                <tr>
                                    <td>#<?php echo $puesto['id_puesto']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($puesto['nombre']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($puesto['direccion'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($puesto['municipio_nombre'] ?? ''); ?></td>
                                    <td><?php echo $puesto['cantidad_mesas'] ?? 0; ?></td>
                                    <td>
                                        <span class="<?php echo ($puesto['activo'] ?? 1) ? 'badge-activo' : 'badge-inactivo'; ?>">
                                            <?php echo ($puesto['activo'] ?? 1) ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="acciones-cell">
                                            <a href="#" class="btn-accion btn-editar"><i class="fas fa-edit"></i></a>
                                            <a href="#" class="btn-accion btn-eliminar"><i class="fas fa-trash"></i></a>
                                            <a href="#" class="btn-accion btn-ver"><i class="fas fa-eye"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($puestosVotacion)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="fas fa-info-circle me-2"></i> No hay puestos de votación registrados
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                <?php
                // ZONAS
                elseif ($seccion_activa == 'zonas'): 
                ?>
                    <div class="param-header">
                        <h3><i class="fas fa-map-marker-alt"></i> Administración de Zonas</h3>
                        <a href="#" class="btn-agregar" data-bs-toggle="modal" data-bs-target="#modalZona">
                            <i class="fas fa-plus"></i> Nueva Zona
                        </a>
                    </div>
                    <div class="table-container">
                        <table class="param-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre de la Zona</th>
                                    <th>Descripción</th>
                                    <th>Municipio</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($zonas as $zona): ?>
                                <tr>
                                    <td>#<?php echo $zona['id_zona']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($zona['nombre']); ?></strong></td>
                                    <td><?php echo htmlspecialchars(substr($zona['descripcion'] ?? '', 0, 50)) . '...'; ?></td>
                                    <td><?php echo htmlspecialchars($zona['municipio_nombre'] ?? ''); ?></td>
                                    <td>
                                        <span class="<?php echo ($zona['activo'] ?? 1) ? 'badge-activo' : 'badge-inactivo'; ?>">
                                            <?php echo ($zona['activo'] ?? 1) ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="acciones-cell">
                                            <a href="#" class="btn-accion btn-editar"><i class="fas fa-edit"></i></a>
                                            <a href="#" class="btn-accion btn-eliminar"><i class="fas fa-trash"></i></a>
                                            <a href="#" class="btn-accion btn-ver"><i class="fas fa-eye"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($zonas)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-info-circle me-2"></i> No hay zonas registradas
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                <?php
                // SECTORES
                elseif ($seccion_activa == 'sectores'): 
                ?>
                    <div class="param-header">
                        <h3><i class="fas fa-th-large"></i> Administración de Sectores</h3>
                        <a href="#" class="btn-agregar" data-bs-toggle="modal" data-bs-target="#modalSector">
                            <i class="fas fa-plus"></i> Nuevo Sector
                        </a>
                    </div>
                    <div class="table-container">
                        <table class="param-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre del Sector</th>
                                    <th>Descripción</th>
                                    <th>Zona</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sectores as $sector): ?>
                                <tr>
                                    <td>#<?php echo $sector['id_sector']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($sector['nombre']); ?></strong></td>
                                    <td><?php echo htmlspecialchars(substr($sector['descripcion'] ?? '', 0, 50)) . '...'; ?></td>
                                    <td><?php echo htmlspecialchars($sector['zona_nombre'] ?? ''); ?></td>
                                    <td>
                                        <span class="<?php echo ($sector['activo'] ?? 1) ? 'badge-activo' : 'badge-inactivo'; ?>">
                                            <?php echo ($sector['activo'] ?? 1) ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="acciones-cell">
                                            <a href="#" class="btn-accion btn-editar"><i class="fas fa-edit"></i></a>
                                            <a href="#" class="btn-accion btn-eliminar"><i class="fas fa-trash"></i></a>
                                            <a href="#" class="btn-accion btn-ver"><i class="fas fa-eye"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($sectores)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-info-circle me-2"></i> No hay sectores registrados
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Footer -->
    <footer class="system-footer">
        <div class="container text-center mb-3">
            <img id="footer-logo" 
                src="imagenes/Logo-artguru.png" 
                alt="Logo ARTGURU" 
                class="logo-clickable"
                onclick="mostrarModalSistema()"
                title="Haz clic para ver información del sistema"
                data-img-claro="imagenes/Logo-artguru.png"
                data-img-oscuro="imagenes/image_no_bg.png">
        </div>
        <div class="container text-center">
            <p>
                <strong>© 2026 Sistema de Gestión Política SGP.</strong> Puerto Gaitán - Meta<br>
                Módulo de SGA Sistema de Gestión Administrativa 2026 SGA Solución de Gestión Administrativa Enterprise Premium 1.0™ desarrollado por SISGONTech Technology®<br>
                Propietario software: Yerson Solano Alfonso - ☎️ (+57) 313 333 62 27 - Email: soportesgp@gmail.com
            </p>
        </div>
    </footer>

    <!-- Modal de Información del Sistema -->
    <div class="modal fade modal-system-info" id="modalSistema" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-info-circle me-2"></i>Información del Sistema
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="modal-logo-container">
                        <img src="imagenes/Logo-artguru.png" alt="Logo del Sistema" class="modal-logo">
                    </div>
                    
                    <div class="licencia-info">
                        <div class="licencia-header">
                            <h6 class="licencia-title">Licencia Runtime</h6>
                            <span class="licencia-dias"><?php echo $diasRestantes; ?> días restantes</span>
                        </div>
                        <div class="licencia-progress">
                            <div class="licencia-progress-bar <?php echo $barColor; ?>" 
                                style="width: <?php echo $porcentajeRestante; ?>%">
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
                </div>
                <div class="modal-footer">
                    <a href="https://sgp-sistema-de-gestion-politica.webnode.com.co/" 
                       target="_blank" 
                       class="btn btn-primary">
                        <i class="fas fa-external-link-alt me-1"></i> Uso SGP
                    </a>
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Función para mostrar el modal del sistema
        function mostrarModalSistema() {
            var modal = new bootstrap.Modal(document.getElementById('modalSistema'));
            modal.show();
        }

        // Cambiar logo según tema
        function actualizarLogoSegunTema() {
            const logo = document.getElementById('footer-logo');
            if (!logo) return;
            
            const isDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            
            if (isDarkMode) {
                logo.src = logo.getAttribute('data-img-oscuro');
            } else {
                logo.src = logo.getAttribute('data-img-claro');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            actualizarLogoSegunTema();
            
            // Manejar clics en botones de acción (placeholder)
            document.querySelectorAll('.btn-editar, .btn-eliminar, .btn-ver, .btn-agregar').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const accion = this.classList.contains('btn-editar') ? 'Editar' :
                                  this.classList.contains('btn-eliminar') ? 'Eliminar' :
                                  this.classList.contains('btn-ver') ? 'Ver detalles' : 'Agregar nuevo';
                    const seccion = '<?php echo $seccion_activa; ?>';
                    showNotification(`${accion} - Funcionalidad en desarrollo para ${seccion}`, 'info');
                });
            });
        });

        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function() {
            actualizarLogoSegunTema();
        });

        // Mostrar notificaciones
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} position-fixed`;
            notification.style.cssText = `
                position: fixed;
                top: 100px;
                right: 20px;
                z-index: 9999;
                min-width: 300px;
                max-width: 500px;
                animation: slideIn 0.3s ease;
            `;
            
            notification.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
                    <span>${message}</span>
                    <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 3000);
        }

        // Agregar estilos de animación
        const style = document.createElement('style');
        style.textContent = `
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
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>