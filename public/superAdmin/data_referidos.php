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

// Verificar si el usuario está logueado y es SuperAdmin
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    header('Location: ../index.php');
    exit();
}

$pdo = Database::getConnection();
$usuarioModel = new UsuarioModel($pdo);
$referenciadoModel = new ReferenciadoModel($pdo);

// Obtener datos del usuario logueado
$usuario_logueado = $usuarioModel->getUsuarioById($_SESSION['id_usuario']);

// Obtener todos los referenciados
$referenciados = $referenciadoModel->getAllReferenciados();

// Inicializar modelos para obtener nombres de relaciones
$zonaModel = new ZonaModel($pdo);
$sectorModel = new SectorModel($pdo);
$puestoModel = new PuestoVotacionModel($pdo);
$departamentoModel = new DepartamentoModel($pdo);
$municipioModel = new MunicipioModel($pdo);
$ofertaModel = new OfertaApoyoModel($pdo);
$grupoModel = new GrupoPoblacionalModel($pdo);
$barrioModel = new BarrioModel($pdo);

// Obtener todos los datos de relaciones
$zonas = $zonaModel->getAll();
$sectores = $sectorModel->getAll();
$puestos = $puestoModel->getAll();
$departamentos = $departamentoModel->getAll();
$municipios = $municipioModel->getAll();
$ofertas = $ofertaModel->getAll();
$grupos = $grupoModel->getAll();
$barrios = $barrioModel->getAll();

// Crear arrays para búsqueda rápida
$zonasMap = [];
foreach ($zonas as $zona) {
    $zonasMap[$zona['id_zona']] = $zona['nombre'];
}

$sectoresMap = [];
foreach ($sectores as $sector) {
    $sectoresMap[$sector['id_sector']] = $sector['nombre'];
}

$puestosMap = [];
foreach ($puestos as $puesto) {
    $puestosMap[$puesto['id_puesto']] = $puesto['nombre'];
}

$departamentosMap = [];
foreach ($departamentos as $departamento) {
    $departamentosMap[$departamento['id_departamento']] = $departamento['nombre'];
}

$municipiosMap = [];
foreach ($municipios as $municipio) {
    $municipiosMap[$municipio['id_municipio']] = $municipio['nombre'];
}

$ofertasMap = [];
foreach ($ofertas as $oferta) {
    $ofertasMap[$oferta['id_oferta']] = $oferta['nombre'];
}

$gruposMap = [];
foreach ($grupos as $grupo) {
    $gruposMap[$grupo['id_grupo']] = $grupo['nombre'];
}

$barriosMap = [];
foreach ($barrios as $barrio) {
    $barriosMap[$barrio['id_barrio']] = $barrio['nombre'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Referidos - Super Admin - SGP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        /* Mismo estilo que la vista del referenciador */
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
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Header Styles (igual al referenciador) */
        .main-header {
            background: linear-gradient(135deg, #2c3e50, #1a252f);
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
        
        /* Breadcrumb Navigation */
        .breadcrumb-nav {
            max-width: 1400px;
            margin: 0 auto 20px;
            padding: 0 15px;
        }
        
        .breadcrumb {
            background: transparent;
            padding: 0;
            margin: 0;
            font-size: 0.9rem;
        }
        
        .breadcrumb-item a {
            color: #3498db;
            text-decoration: none;
        }
        
        .breadcrumb-item.active {
            color: #666;
        }
        
        /* Main Container */
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 15px 30px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        /* Dashboard Header */
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0 30px;
            padding: 0 20px;
        }
        
        .dashboard-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .stats-summary {
            display: flex;
            gap: 20px;
        }
        
        .stat-item {
            text-align: center;
            background: white;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            min-width: 120px;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #3498db;
            display: block;
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Table Container */
        .table-container {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f5f9;
        }
        
        .table-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .table-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-search {
            background: #3498db;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        
        .btn-export {
            background: #27ae60;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        
        /* DataTables Custom Styling */
        .dataTables_wrapper {
            width: 100% !important;
        }
        
        table.dataTable {
            border-collapse: collapse !important;
            width: 100% !important;
        }
        
        table.dataTable thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            padding: 12px 15px;
            font-weight: 600;
            color: #2c3e50;
            text-align: left;
        }
        
        table.dataTable tbody td {
            padding: 10px 15px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }
        
        table.dataTable tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        /* Badge for affinity */
        .badge-affinidad {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        .badge-affinidad-1 { background-color: #ff6b6b; color: white; }
        .badge-affinidad-2 { background-color: #ffa726; color: white; }
        .badge-affinidad-3 { background-color: #ffd166; color: #333; }
        .badge-affinidad-4 { background-color: #06d6a0; color: white; }
        .badge-affinidad-5 { background-color: #118ab2; color: white; }
        
        /* Text ellipsis for long content */
        .text-ellipsis {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px;
        }
        
        /* Footer */
        .system-footer {
            text-align: center;
            padding: 25px 0;
            background: white;
            color: black;
            font-size: 0.9rem;
            line-height: 1.6;
            border-top: 2px solid #eaeaea;
            width: 100%;
        }
        
        .system-footer p {
            margin: 8px 0;
            color: #333;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .dashboard-header {
                flex-direction: column;
                gap: 20px;
                align-items: flex-start;
            }
            
            .stats-summary {
                width: 100%;
                justify-content: space-between;
            }
            
            .table-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .table-actions {
                width: 100%;
                justify-content: flex-end;
            }
        }
        
        @media (max-width: 767px) {
            .header-top {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .user-info {
                order: 1;
            }
            
            .logout-btn {
                order: 2;
                align-self: flex-end;
            }
            
            .stats-summary {
                flex-wrap: wrap;
            }
            
            .stat-item {
                min-width: 100px;
                padding: 10px 15px;
            }
            
            .stat-number {
                font-size: 1.3rem;
            }
            
            .table-container {
                padding: 15px;
            }
            
            .table-title {
                font-size: 1.1rem;
            }
            
            .system-footer {
                padding: 20px 15px;
                font-size: 0.85rem;
            }
        }
        
        @media (max-width: 480px) {
            .dashboard-title {
                font-size: 1.4rem;
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .stats-summary {
                gap: 10px;
            }
            
            .stat-item {
                min-width: 80px;
                padding: 8px 12px;
            }
            
            .stat-number {
                font-size: 1.2rem;
            }
            
            .btn-search, .btn-export {
                padding: 6px 12px;
                font-size: 0.85rem;
            }
        }

        /* ESTILOS PARA LOS BOTONES DE ACCIONES */
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: nowrap;
            justify-content: center;
        }
        
        .btn-action {
            padding: 8px;
            border-radius: 6px;
            border: none;
            font-weight: 500;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            min-width: 40px;
        }
        
        .btn-view {
            background-color: rgba(155, 89, 182, 0.1);
            color: #9b59b6;
            border: 1px solid rgba(155, 89, 182, 0.2);
        }

        .btn-view:hover {
            background-color: rgba(155, 89, 182, 0.2);
            color: #9b59b6;
        }
        
        .btn-edit {
            background-color: rgba(52, 152, 219, 0.1);
            color: #3498db;
            border: 1px solid rgba(52, 152, 219, 0.2);
        }
        
        .btn-edit:hover {
            background-color: rgba(52, 152, 219, 0.2);
            color: #3498db;
        }
        
        .btn-deactivate {
            background-color: rgba(243, 156, 18, 0.1);
            color: #f39c12;
            border: 1px solid rgba(243, 156, 18, 0.2);
        }
        
        .btn-deactivate:hover {
            background-color: rgba(243, 156, 18, 0.2);
            color: #f39c12;
        }
        
        /* BOTÓN DE ACTIVAR (VERDE) */
        .btn-activate {
            background-color: rgba(39, 174, 96, 0.1);
            color: #27ae60;
            border: 1px solid rgba(39, 174, 96, 0.2);
        }
        
        .btn-activate:hover {
            background-color: rgba(39, 174, 96, 0.2);
            color: #27ae60;
        }

        /* ESTILOS PARA NOTIFICACIONES */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-width: 300px;
            max-width: 400px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
            animation: slideIn 0.3s ease-out;
        }
        
        .notification-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .notification-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .notification-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
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
            color: inherit;
            cursor: pointer;
            padding: 0;
            margin-left: 10px;
            opacity: 0.7;
            transition: opacity 0.2s;
        }
        
        .notification-close:hover {
            opacity: 1;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="header-container">
            <div class="header-top">
                <div class="header-title">
                    <h1><i class="fas fa-users"></i> Data Referidos - Super Admin</h1>
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($usuario_logueado['nombres'] . ' ' . $usuario_logueado['apellidos']); ?></span>
                    </div>
                </div>
                <a href="../logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
        </div>
    </header>

    <!-- Breadcrumb Navigation -->
    <div class="breadcrumb-nav">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../superadmin_dashboard.php"><i class="fas fa-home"></i> Panel Super Admin</a></li>
                <li class="breadcrumb-item"><a href="superadmin_datas.php"><i class="fas fa-database"></i> Datas</a></li>
                <li class="breadcrumb-item active"><i class="fas fa-users"></i> Data Referidos</li>
            </ol>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="dashboard-title">
                <i class="fas fa-users"></i>
                <span>Data de Referidos</span>
            </div>
            <div class="stats-summary">
                <div class="stat-item">
                    <span class="stat-number"><?php echo count($referenciados); ?></span>
                    <span class="stat-label">Total Referidos</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo date('d/m/Y'); ?></span>
                    <span class="stat-label">Fecha Actual</span>
                </div>
            </div>
        </div>
        
        <!-- Table Container -->
        <div class="table-container">
            <div class="table-header">
                <div class="table-title">
                    <i class="fas fa-table"></i>
                    <span>Listado de Referidos Registrados</span>
                </div>
                <div class="table-actions">
                    <button class="btn-search">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                    <button class="btn-export">
                        <i class="fas fa-download"></i> Exportar
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table id="referidosTable" class="table table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Apellido</th>
                            <th>Cédula</th>
                            <th>Dirección</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Afinidad</th>
                            <th>Zona</th>
                            <th>Sector</th>
                            <th>Puesto</th>
                            <th>Mesa</th>
                            <th>Departamento</th>
                            <th>Municipio</th>
                            <th>Oferta</th>
                            <th>Grupo</th>
                            <th>Barrio</th>
                            <th>Referenciador</th>
                            <th>Fecha Registro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($referenciados as $referenciado): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($referenciado['nombre'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($referenciado['apellido'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($referenciado['cedula'] ?? ''); ?></td>
                            <td class="text-ellipsis" title="<?php echo htmlspecialchars($referenciado['direccion'] ?? ''); ?>">
                                <?php echo htmlspecialchars($referenciado['direccion'] ?? ''); ?>
                            </td>
                            <td><?php echo htmlspecialchars($referenciado['email'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($referenciado['telefono'] ?? ''); ?></td>
                            <td>
                                <div class="badge-affinidad badge-affinidad-<?php echo $referenciado['afinidad'] ?? '1'; ?>">
                                    <?php echo $referenciado['afinidad'] ?? '0'; ?>
                                </div>
                            </td>
                            <td><?php echo isset($referenciado['id_zona']) && isset($zonasMap[$referenciado['id_zona']]) ? htmlspecialchars($zonasMap[$referenciado['id_zona']]) : 'N/A'; ?></td>
                            <td><?php echo isset($referenciado['id_sector']) && isset($sectoresMap[$referenciado['id_sector']]) ? htmlspecialchars($sectoresMap[$referenciado['id_sector']]) : 'N/A'; ?></td>
                            <td><?php echo isset($referenciado['id_puesto_votacion']) && isset($puestosMap[$referenciado['id_puesto_votacion']]) ? htmlspecialchars($puestosMap[$referenciado['id_puesto_votacion']]) : 'N/A'; ?></td>
                            <td><?php echo htmlspecialchars($referenciado['mesa'] ?? ''); ?></td>
                            <td><?php echo isset($referenciado['id_departamento']) && isset($departamentosMap[$referenciado['id_departamento']]) ? htmlspecialchars($departamentosMap[$referenciado['id_departamento']]) : 'N/A'; ?></td>
                            <td><?php echo isset($referenciado['id_municipio']) && isset($municipiosMap[$referenciado['id_municipio']]) ? htmlspecialchars($municipiosMap[$referenciado['id_municipio']]) : 'N/A'; ?></td>
                            <td><?php echo isset($referenciado['id_oferta_apoyo']) && isset($ofertasMap[$referenciado['id_oferta_apoyo']]) ? htmlspecialchars($ofertasMap[$referenciado['id_oferta_apoyo']]) : 'N/A'; ?></td>
                            <td><?php echo isset($referenciado['id_grupo_poblacional']) && isset($gruposMap[$referenciado['id_grupo_poblacional']]) ? htmlspecialchars($gruposMap[$referenciado['id_grupo_poblacional']]) : 'N/A'; ?></td>
                            <td><?php echo isset($referenciado['id_barrio']) && isset($barriosMap[$referenciado['id_barrio']]) ? htmlspecialchars($barriosMap[$referenciado['id_barrio']]) : 'N/A'; ?></td>
                            <td><?php echo htmlspecialchars($referenciado['referenciador_nombre'] ?? 'N/A'); ?></td>
                            <td><?php echo isset($referenciado['fecha_registro']) ? date('d/m/Y H:i', strtotime($referenciado['fecha_registro'])) : ''; ?></td>
                            <td>
                                <div class="action-buttons">
                                    <!-- BOTÓN DE VER DETALLE -->
                                    <button class="btn-action btn-view" 
                                            title="Ver detalle del referido">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <!-- BOTÓN DE EDITAR -->
                                    <button class="btn-action btn-edit" 
                                            title="Editar referido">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <!-- BOTÓN DE ACTIVAR/DESACTIVAR -->
                                    <?php 
                                    $activo = $referenciado['activo'] ?? true;
                                    $esta_activo = ($activo === true || $activo === 't' || $activo == 1);
                                    
                                    if ($esta_activo): ?>
                                        <button class="btn-action btn-deactivate" 
                                                title="Desactivar referido"
                                                onclick="desactivarReferenciado(
                                                    <?php echo $referenciado['id_referenciado']; ?>, 
                                                    '<?php echo htmlspecialchars($referenciado['nombre'] . ' ' . $referenciado['apellido']); ?>', 
                                                    this)">
                                            <i class="fas fa-user-slash"></i>
                                        </button>
                                    <?php else: ?>
                                        <button class="btn-action btn-activate" 
                                                title="Activar referido"
                                                onclick="reactivarReferenciado(
                                                    <?php echo $referenciado['id_referenciado']; ?>, 
                                                    '<?php echo htmlspecialchars($referenciado['nombre'] . ' ' . $referenciado['apellido']); ?>', 
                                                    this)">
                                            <i class="fas fa-user-check"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Info Footer -->
        <div style="text-align: center; color: #666; font-size: 0.9rem; margin-top: 20px;">
            <p><i class="fas fa-info-circle"></i> Esta tabla muestra todos los referidos registrados en el sistema. Use la barra de búsqueda para filtrar resultados.</p>
        </div>
    </div>

    <!-- Footer -->
    <footer class="system-footer">
        <div class="container">
            <p>© Derechos de autor Reservados. 
                Ing. Rubén Darío González García • 
                SISGONTech • Colombia © • <?php echo date('Y'); ?>
            </p>
            <p>Contacto: +57 3106310227 • 
                Email: sisgonnet@gmail.com
            </p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            // Inicializar DataTable
            $('#referidosTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                },
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
                order: [[0, 'asc']], // Ordenar por nombre por defecto
                responsive: true,
                scrollX: true, // Permitir scroll horizontal
                dom: '<"top"f>rt<"bottom"lip><"clear">',
                initComplete: function() {
                    // Ajustar columnas después de inicializar
                    this.api().columns.adjust();
                },
                columnDefs: [
                    {
                        targets: -1, // Última columna (Acciones)
                        orderable: false,
                        searchable: false,
                        width: '130px'
                    }
                ]
            });
            
            // Botón de búsqueda
            $('.btn-search').click(function() {
                $('#referidosTable').DataTable().search('').draw();
                $('#referidosTable_filter input').focus();
            });
            
            // Botón de exportar
            $('.btn-export').click(function() {
                alert('Funcionalidad de exportación en desarrollo...');
                // Aquí iría la lógica para exportar a Excel/PDF
            });
            
            // Ajustar tabla en redimensionamiento
            $(window).resize(function() {
                $('#referidosTable').DataTable().columns.adjust();
            });

            // Efecto hover en botones de acción
            $('.btn-action').hover(
                function() {
                    $(this).css('transform', 'translateY(-2px)');
                    $(this).css('box-shadow', '0 3px 6px rgba(0,0,0,0.1)');
                },
                function() {
                    $(this).css('transform', 'translateY(0)');
                    $(this).css('box-shadow', 'none');
                }
            );
        });

        // Función para desactivar un referenciado
        async function desactivarReferenciado(idReferenciado, nombreReferenciado, button) {
            if (!confirm(`¿Está seguro de DESACTIVAR al referenciado "${nombreReferenciado}"?\n\nEl referenciado será marcado como inactivo, pero se mantendrá en el sistema.`)) {
                return;
            }
            
            const originalIcon = button.innerHTML;
            const originalClass = button.className;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            button.disabled = true;
            
            try {
                const response = await fetch('../ajax/referenciados.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `accion=desactivar&id_referenciado=${idReferenciado}`
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Cambiar el botón a verde con icono de reactivar
                    button.className = 'btn-action btn-activate';
                    button.title = 'Activar referido';
                    button.innerHTML = '<i class="fas fa-user-check"></i>';
                    button.disabled = false;
                    
                    // Cambiar event listener para reactivar
                    button.setAttribute('onclick', `reactivarReferenciado(${idReferenciado}, '${nombreReferenciado.replace(/'/g, "\\'")}', this)`);
                    
                    // Mostrar notificación
                    showNotification('Referenciado desactivado correctamente', 'success');
                } else {
                    showNotification('Error: ' + (data.message || 'No se pudo desactivar el referenciado'), 'error');
                    button.innerHTML = originalIcon;
                    button.className = originalClass;
                    button.disabled = false;
                }
            } catch (error) {
                showNotification('Error de conexión: ' + error.message, 'error');
                button.innerHTML = originalIcon;
                button.className = originalClass;
                button.disabled = false;
            }
        }

        // Función para reactivar un referenciado
        async function reactivarReferenciado(idReferenciado, nombreReferenciado, button) {
            if (!confirm(`¿Desea REACTIVAR al referenciado "${nombreReferenciado}"?`)) {
                return;
            }
            
            const originalIcon = button.innerHTML;
            const originalClass = button.className;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            button.disabled = true;
            
            try {
                const response = await fetch('../ajax/referenciados.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `accion=reactivar&id_referenciado=${idReferenciado}`
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Cambiar el botón a amarillo con icono de desactivar
                    button.className = 'btn-action btn-deactivate';
                    button.title = 'Desactivar referido';
                    button.innerHTML = '<i class="fas fa-user-slash"></i>';
                    button.disabled = false;
                    
                    // Cambiar event listener para desactivar
                    button.setAttribute('onclick', `desactivarReferenciado(${idReferenciado}, '${nombreReferenciado.replace(/'/g, "\\'")}', this)`);
                    
                    // Mostrar notificación
                    showNotification('Referenciado reactivado correctamente', 'success');
                } else {
                    showNotification('Error: ' + data.message, 'error');
                    button.innerHTML = originalIcon;
                    button.className = originalClass;
                    button.disabled = false;
                }
            } catch (error) {
                showNotification('Error de conexión: ' + error.message, 'error');
                button.innerHTML = originalIcon;
                button.className = originalClass;
                button.disabled = false;
            }
        }

        // Función para mostrar notificaciones
        function showNotification(message, type = 'info') {
            // Eliminar notificación anterior si existe
            const oldNotification = document.querySelector('.notification');
            if (oldNotification) {
                oldNotification.remove();
            }
            
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <div class="notification-content">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                    <span>${message}</span>
                </div>
                <button class="notification-close">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            document.body.appendChild(notification);
            
            // Botón para cerrar
            notification.querySelector('.notification-close').addEventListener('click', () => {
                notification.remove();
            });
            
            // Auto-eliminar después de 5 segundos
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 5000);
        }
    </script>
</body>
</html>