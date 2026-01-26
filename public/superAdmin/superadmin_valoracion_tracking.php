<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../models/ReferenciadoModel.php';
require_once __DIR__ . '/../../models/LlamadaModel.php';

// Verificar si el usuario está logueado y es SuperAdmin
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    header('Location: ../index.php');
    exit();
}

$pdo = Database::getConnection();
$usuarioModel = new UsuarioModel($pdo);
$referenciadoModel = new ReferenciadoModel($pdo);
$llamadaModel = new LlamadaModel($pdo);

// Obtener datos del usuario logueado
$usuario_logueado = $usuarioModel->getUsuarioById($_SESSION['id_usuario']);

// Obtener todos los referenciadores para filtros
$referenciadores = $usuarioModel->getReferenciadoresActivos();

// Obtener tipos de resultado para filtros
$tiposResultado = $llamadaModel->getTiposResultado();

// Procesar filtros
$filtros = [];
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-d', strtotime('-30 days'));
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d');
$id_resultado = $_GET['id_resultado'] ?? '';
$rating_min = $_GET['rating_min'] ?? '';
$rating_max = $_GET['rating_max'] ?? '';
$id_referenciador = $_GET['id_referenciador'] ?? '';

// Aplicar filtros
if (!empty($fecha_desde)) {
    $filtros['fecha_desde'] = $fecha_desde;
}
if (!empty($fecha_hasta)) {
    $filtros['fecha_hasta'] = $fecha_hasta;
}
if (!empty($id_resultado) && $id_resultado != 'todos') {
    $filtros['id_resultado'] = $id_resultado;
}
if (!empty($rating_min)) {
    $filtros['rating_min'] = $rating_min;
}
if (!empty($rating_max)) {
    $filtros['rating_max'] = $rating_max;
}
if (!empty($id_referenciador) && $id_referenciador != 'todos') {
    $filtros['id_referenciador'] = $id_referenciador;
}

// Obtener datos con filtros aplicados
$referenciadosConLlamadas = $llamadaModel->getReferenciadosConLlamadas($filtros);
$totalRegistros = count($referenciadosConLlamadas);

// Obtener estadísticas
$estadisticas = $llamadaModel->getEstadisticasLlamadas();
$distribucionResultados = $llamadaModel->getDistribucionPorResultado();
$topLlamadores = $llamadaModel->getTopLlamadores(5);

// Calcular algunos totales
$totalLlamadas = $estadisticas['total_llamadas'] ?? 0;
$ratingPromedio = isset($estadisticas['rating_promedio']) ? round($estadisticas['rating_promedio'], 2) : 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Valoración Tracking - Panel Super Admin - SGP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <style>
        /* Estilos del tema actual */
        :root {
            --color-fondo: #f5f7fa;
            --color-fondo-secundario: #ffffff;
            --color-texto: #333333;
            --color-texto-secundario: #666666;
            --color-borde: #eaeaea;
            --color-borde-secundario: #e3f2fd;
            --color-primario: #3498db;
            --color-secundario: #2c3e50;
            --color-terciario: #2ecc71;
            --color-badge: #f8f9fa;
            --color-sombra: rgba(0, 0, 0, 0.08);
            --color-sombra-fuerte: rgba(0, 0, 0, 0.15);
            --color-header: linear-gradient(135deg, #2c3e50, #1a252f);
            --accent-color: #3498db;
        }

        /* Variables para modo oscuro */
        @media (prefers-color-scheme: dark) {
            :root {
                --color-fondo: #121212;
                --color-fondo-secundario: #1e1e1e;
                --color-texto: #e0e0e0;
                --color-texto-secundario: #b0b0b0;
                --color-borde: #2d3748;
                --color-borde-secundario: #4a5568;
                --color-primario: #60a5fa;
                --color-secundario: #cbd5e0;
                --color-terciario: #48bb78;
                --color-badge: #2d3748;
                --color-sombra: rgba(0, 0, 0, 0.2);
                --color-sombra-fuerte: rgba(0, 0, 0, 0.3);
                --color-header: linear-gradient(135deg, #0d1117, #1a252f);
            }
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--color-fondo);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--color-texto);
            margin: 0;
            padding: 0;
            font-size: 14px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Header Styles */
        .main-header {
            background: var(--color-header);
            color: white;
            padding: 15px 0;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px var(--color-sombra-fuerte);
            transition: all 0.3s ease;
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 15px;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            backdrop-filter: blur(5px);
        }

        .user-info i {
            color: var(--color-primario);
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
            transform: translateY(-1px);
        }

        /* Main Container */
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 15px 30px;
            flex: 1;
        }

        /* Breadcrumb */
        .breadcrumb-nav {
            margin-bottom: 20px;
        }

        .breadcrumb {
            background: transparent;
            padding: 0;
            margin: 0;
            font-size: 0.9rem;
        }

        .breadcrumb-item a {
            color: var(--accent-color);
            text-decoration: none;
        }

        .breadcrumb-item.active {
            color: var(--color-texto-secundario);
        }

        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding: 20px 0;
            border-bottom: 2px solid var(--color-borde);
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-title i {
            font-size: 2rem;
            color: var(--color-primario);
        }

        .page-title h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--color-secundario);
            margin: 0;
        }

        .page-title .subtitle {
            font-size: 1rem;
            color: var(--color-texto-secundario);
            margin-top: 5px;
        }

        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--color-fondo-secundario);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 10px var(--color-sombra);
            border: 1px solid var(--color-borde);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px var(--color-sombra-fuerte);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 1.5rem;
            color: white;
        }

        .stat-card.total .stat-icon {
            background: linear-gradient(135deg, #3498db, #2980b9);
        }

        .stat-card.rating .stat-icon {
            background: linear-gradient(135deg, #f39c12, #e67e22);
        }

        .stat-card.contactados .stat-icon {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
        }

        .stat-card.llamadores .stat-icon {
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--color-texto);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--color-texto-secundario);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Filter Card */
        .filter-card {
            background: var(--color-fondo-secundario);
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 10px var(--color-sombra);
            border: 1px solid var(--color-borde);
        }

        .filter-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            color: var(--color-secundario);
            font-weight: 600;
        }

        .filter-title i {
            color: var(--color-primario);
        }

        .filter-form .row {
            margin-bottom: 15px;
        }

        .filter-form label {
            font-weight: 500;
            margin-bottom: 5px;
            color: var(--color-texto);
        }

        .filter-form .form-control,
        .filter-form .form-select {
            background-color: var(--color-fondo);
            border: 1px solid var(--color-borde);
            color: var(--color-texto);
            padding: 8px 12px;
            font-size: 0.9rem;
        }

        .filter-form .form-control:focus,
        .filter-form .form-select:focus {
            border-color: var(--color-primario);
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-filter {
            background: var(--color-primario);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }

        .btn-filter:hover {
            background: #2980b9;
            color: white;
            transform: translateY(-2px);
        }

        .btn-reset {
            background: var(--color-fondo);
            color: var(--color-texto);
            border: 1px solid var(--color-borde);
            padding: 8px 20px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }

        .btn-reset:hover {
            background: var(--color-borde);
            color: var(--color-texto);
        }

        /* Results Card */
        .results-card {
            background: var(--color-fondo-secundario);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px var(--color-sombra);
            border: 1px solid var(--color-borde);
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid var(--color-borde);
            background: var(--color-fondo);
        }

        .results-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--color-secundario);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .results-count {
            background: var(--color-primario);
            color: white;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        /* Table Styles */
        .table-container {
            overflow-x: auto;
            padding: 0 20px 20px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        .table thead {
            background: var(--color-fondo);
        }

        .table th {
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: var(--color-secundario);
            border-bottom: 2px solid var(--color-borde);
            white-space: nowrap;
        }

        .table td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--color-borde);
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }

        /* Status Badges */
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
            text-align: center;
            min-width: 80px;
        }

        .status-active {
            background: rgba(46, 204, 113, 0.15);
            color: #27ae60;
        }

        .status-inactive {
            background: rgba(231, 76, 60, 0.15);
            color: #c0392b;
        }

        /* Rating Stars */
        .rating-stars {
            color: #f39c12;
            font-size: 0.9rem;
        }

        .rating-stars .far {
            color: #bdc3c7;
        }

        /* Action Buttons in Table */
        .action-btn {
            padding: 4px 8px;
            border-radius: 4px;
            border: none;
            background: transparent;
            color: var(--color-texto);
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.8rem;
        }

        .action-btn:hover {
            background: var(--color-fondo);
        }

        .btn-view {
            color: var(--color-primario);
        }

        .btn-history {
            color: #9b59b6;
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
            margin-top: 60px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-top {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .table-container {
                padding: 0 10px 10px;
            }
            
            .table {
                font-size: 0.8rem;
            }
            
            .table th,
            .table td {
                padding: 8px 10px;
            }
        }

        /* DataTables Customization */
        .dataTables_wrapper {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .dataTables_length select,
        .dataTables_filter input {
            background-color: var(--color-fondo);
            border: 1px solid var(--color-borde);
            color: var(--color-texto);
            padding: 4px 8px;
            border-radius: 4px;
        }

        .dataTables_paginate .paginate_button {
            background-color: var(--color-fondo);
            border: 1px solid var(--color-borde);
            color: var(--color-texto) !important;
            margin: 0 2px;
            padding: 5px 10px;
            border-radius: 4px;
        }

        .dataTables_paginate .paginate_button.current {
            background: var(--color-primario) !important;
            color: white !important;
            border-color: var(--color-primario);
        }

        .dataTables_paginate .paginate_button:hover {
            background: var(--color-borde) !important;
            color: var(--color-texto) !important;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="header-container">
            <div class="header-top">
                <div class="header-title">
                    <h1><i class="fas fa-chart-pie"></i> Valoración Tracking - Super Admin</h1>
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
        <div class="header-container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../superadmin_dashboard.php"><i class="fas fa-home"></i> Panel Super Admin</a></li>
                    <li class="breadcrumb-item"><a href="superadmin_reportes.php"><i class="fas fa-database"></i> Reportes</a></li>
                    <li class="breadcrumb-item active"><i class="fas fa-chart-pie"></i> Valoración Tracking</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-title">
                <i class="fas fa-chart-pie fa-2x"></i>
                <div>
                    <h1>Valoración Tracking</h1>
                    <div class="subtitle">Control de calidad y seguimiento de referenciados contactados</div>
                </div>
            </div>
            <div class="action-buttons">
                <a href="superadmin_reportes.php" class="btn-reset">
                    <i class="fas fa-arrow-left"></i> Volver a Reportes
                </a>
                <button type="button" class="btn-filter" onclick="exportarExcel()">
                    <i class="fas fa-file-excel"></i> Exportar Excel
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-container">
            <div class="stat-card total">
                <div class="stat-icon">
                    <i class="fas fa-phone-alt"></i>
                </div>
                <div class="stat-value"><?php echo number_format($totalLlamadas); ?></div>
                <div class="stat-label">Total Llamadas</div>
            </div>
            
            <div class="stat-card rating">
                <div class="stat-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-value"><?php echo $ratingPromedio; ?>/5</div>
                <div class="stat-label">Rating Promedio</div>
            </div>
            
            <div class="stat-card contactados">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value"><?php echo number_format($totalRegistros); ?></div>
                <div class="stat-label">Referenciados Contactados</div>
            </div>
            
            <div class="stat-card llamadores">
                <div class="stat-icon">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="stat-value"><?php echo count($topLlamadores); ?></div>
                <div class="stat-label">Equipo de Seguimiento</div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="filter-card">
            <div class="filter-title">
                <i class="fas fa-filter"></i>
                <span>Filtros de Búsqueda</span>
            </div>
            
            <form method="GET" action="" class="filter-form">
                <div class="row">
                    <div class="col-md-3">
                        <label for="fecha_desde">Fecha Desde</label>
                        <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" 
                               value="<?php echo htmlspecialchars($fecha_desde); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="fecha_hasta">Fecha Hasta</label>
                        <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta" 
                               value="<?php echo htmlspecialchars($fecha_hasta); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="id_resultado">Resultado</label>
                        <select class="form-select" id="id_resultado" name="id_resultado">
                            <option value="todos">Todos los resultados</option>
                            <?php foreach ($tiposResultado as $resultado): ?>
                                <option value="<?php echo $resultado['id_resultado']; ?>" 
                                    <?php echo $id_resultado == $resultado['id_resultado'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($resultado['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="id_referenciador">Referenciador</label>
                        <select class="form-select" id="id_referenciador" name="id_referenciador">
                            <option value="todos">Todos los referenciadores</option>
                            <?php foreach ($referenciadores as $ref): ?>
                                <option value="<?php echo $ref['id_usuario']; ?>" 
                                    <?php echo $id_referenciador == $ref['id_usuario'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ref['nombres'] . ' ' . $ref['apellidos']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-3">
                        <label for="rating_min">Rating Mínimo</label>
                        <select class="form-select" id="rating_min" name="rating_min">
                            <option value="">Sin mínimo</option>
                            <option value="1" <?php echo $rating_min == '1' ? 'selected' : ''; ?>>1 estrella</option>
                            <option value="2" <?php echo $rating_min == '2' ? 'selected' : ''; ?>>2 estrellas</option>
                            <option value="3" <?php echo $rating_min == '3' ? 'selected' : ''; ?>>3 estrellas</option>
                            <option value="4" <?php echo $rating_min == '4' ? 'selected' : ''; ?>>4 estrellas</option>
                            <option value="5" <?php echo $rating_min == '5' ? 'selected' : ''; ?>>5 estrellas</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="rating_max">Rating Máximo</label>
                        <select class="form-select" id="rating_max" name="rating_max">
                            <option value="">Sin máximo</option>
                            <option value="1" <?php echo $rating_max == '1' ? 'selected' : ''; ?>>1 estrella</option>
                            <option value="2" <?php echo $rating_max == '2' ? 'selected' : ''; ?>>2 estrellas</option>
                            <option value="3" <?php echo $rating_max == '3' ? 'selected' : ''; ?>>3 estrellas</option>
                            <option value="4" <?php echo $rating_max == '4' ? 'selected' : ''; ?>>4 estrellas</option>
                            <option value="5" <?php echo $rating_max == '5' ? 'selected' : ''; ?>>5 estrellas</option>
                        </select>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <button type="submit" class="btn-filter">
                        <i class="fas fa-search"></i> Aplicar Filtros
                    </button>
                    <a href="superadmin_valoracion_tracking.php" class="btn-reset">
                        <i class="fas fa-redo"></i> Limpiar Filtros
                    </a>
                </div>
            </form>
        </div>

        <!-- Results Card -->
        <div class="results-card">
            <div class="results-header">
                <div class="results-title">
                    <i class="fas fa-list"></i>
                    <span>Referenciados con Seguimiento</span>
                    <span class="results-count"><?php echo number_format($totalRegistros); ?></span>
                </div>
                <div class="action-buttons">
                    <button type="button" class="btn-reset" onclick="refreshData()">
                        <i class="fas fa-sync-alt"></i> Actualizar
                    </button>
                </div>
            </div>

            <div class="table-container">
                <table id="trackingTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>Referenciado</th>
                            <th>Cédula</th>
                            <th>Teléfono</th>
                            <th>Referenciador</th>
                            <th>Última Llamada</th>
                            <th>Rating</th>
                            <th>Resultado</th>
                            <th>Total Llamadas</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($referenciadosConLlamadas)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-4">
                                    <i class="fas fa-inbox fa-2x mb-3" style="color: #bdc3c7;"></i>
                                    <p class="mb-0">No se encontraron registros con los filtros aplicados</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($referenciadosConLlamadas as $referenciado): ?>
                                <?php
                                // Formatear fecha
                                $fechaLlamada = !empty($referenciado['fecha_llamada']) 
                                    ? date('d/m/Y H:i', strtotime($referenciado['fecha_llamada']))
                                    : 'N/A';
                                    
                                // Determinar color del estado
                                $estadoClass = $referenciado['activo'] ? 'status-active' : 'status-inactive';
                                $estadoText = $referenciado['activo'] ? 'Activo' : 'Inactivo';
                                
                                // Mostrar rating con estrellas
                                $rating = $referenciado['rating'] ?? 0;
                                $ratingStars = '';
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $rating) {
                                        $ratingStars .= '<i class="fas fa-star"></i>';
                                    } else {
                                        $ratingStars .= '<i class="far fa-star"></i>';
                                    }
                                }
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($referenciado['nombre'] . ' ' . $referenciado['apellido']); ?></strong>
                                        <?php if (!empty($referenciado['email'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($referenciado['email']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($referenciado['cedula']); ?></td>
                                    <td><?php echo htmlspecialchars($referenciado['telefono']); ?></td>
                                    <td><?php echo htmlspecialchars($referenciado['referenciador_nombre'] ?? 'N/A'); ?></td>
                                    <td><?php echo $fechaLlamada; ?></td>
                                    <td>
                                        <div class="rating-stars">
                                            <?php echo $ratingStars; ?>
                                            <?php if ($rating > 0): ?>
                                                <span class="ms-1">(<?php echo $rating; ?>)</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $referenciado['id_resultado'] == 1 ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo htmlspecialchars($referenciado['resultado_nombre'] ?? 'No especificado'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary rounded-pill">
                                            <?php echo $referenciado['total_llamadas']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $estadoClass; ?>">
                                            <?php echo $estadoText; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="ver_referenciado.php?id=<?php echo $referenciado['id_referenciado']; ?>" 
                                               class="action-btn btn-view" 
                                               title="Ver referenciado">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button type="button" 
                                                    class="action-btn btn-history"
                                                    onclick="verHistorialLlamadas(<?php echo $referenciado['id_referenciado']; ?>)"
                                                    title="Ver historial de llamadas">
                                                <i class="fas fa-history"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Distribution Card -->
        <?php if (!empty($distribucionResultados)): ?>
        <div class="filter-card mt-4">
            <div class="filter-title">
                <i class="fas fa-chart-bar"></i>
                <span>Distribución por Resultado</span>
            </div>
            
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Resultado</th>
                            <th>Cantidad</th>
                            <th>Porcentaje</th>
                            <th>Barra</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($distribucionResultados as $distribucion): ?>
                            <?php
                            $porcentaje = $distribucion['porcentaje'] ?? 0;
                            $color = match($distribucion['id_resultado']) {
                                1 => 'bg-success',
                                2 => 'bg-warning',
                                3 => 'bg-danger',
                                4 => 'bg-info',
                                default => 'bg-secondary'
                            };
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($distribucion['resultado']); ?></td>
                                <td><?php echo $distribucion['cantidad']; ?></td>
                                <td><?php echo number_format($porcentaje, 2); ?>%</td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar <?php echo $color; ?>" 
                                             role="progressbar" 
                                             style="width: <?php echo $porcentaje; ?>%"
                                             aria-valuenow="<?php echo $porcentaje; ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                            <?php echo number_format($porcentaje, 1); ?>%
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="system-footer">
        <div class="container">
            <p>
                © Derechos de autor Reservados • <strong>Ing. Rubén Darío González García</strong> • Equipo de soporte • SISGONTech<br>
                Email: sisgonnet@gmail.com • Contacto: +57 3106310227 • Puerto Gaitán, Colombia • <?php echo date('Y'); ?>
            </p>
        </div>
    </footer>

    <!-- Modal para Historial de Llamadas -->
    <div class="modal fade" id="historialModal" tabindex="-1" aria-labelledby="historialModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="historialModalLabel">
                        <i class="fas fa-history me-2"></i>Historial de Llamadas
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="historialContent">
                    <!-- El contenido se cargará dinámicamente -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Inicializar DataTable
        $(document).ready(function() {
            $('#trackingTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
                },
                pageLength: 25,
                order: [[4, 'desc']], // Ordenar por última llamada descendente
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
                responsive: true
            });
        });

        // Función para ver historial de llamadas
        function verHistorialLlamadas(idReferenciado) {
            $.ajax({
                url: 'ajax/get_historial_llamadas.php',
                type: 'GET',
                data: { id_referenciado: idReferenciado },
                beforeSend: function() {
                    $('#historialContent').html(`
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <p class="mt-3">Cargando historial...</p>
                        </div>
                    `);
                },
                success: function(response) {
                    $('#historialContent').html(response);
                    $('#historialModal').modal('show');
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo cargar el historial de llamadas'
                    });
                }
            });
        }

        // Función para exportar a Excel
        function exportarExcel() {
            // Crear tabla de datos
            let datos = [];
            
            // Agregar encabezados
            datos.push([
                'Referenciado', 'Cédula', 'Teléfono', 'Email', 'Referenciador',
                'Última Llamada', 'Rating', 'Resultado', 'Total Llamadas', 'Estado'
            ]);
            
            // Agregar datos
            <?php foreach ($referenciadosConLlamadas as $ref): ?>
                datos.push([
                    '<?php echo addslashes($ref['nombre'] . ' ' . $ref['apellido']); ?>',
                    '<?php echo $ref['cedula']; ?>',
                    '<?php echo $ref['telefono']; ?>',
                    '<?php echo addslashes($ref['email'] ?? ''); ?>',
                    '<?php echo addslashes($ref['referenciador_nombre'] ?? ''); ?>',
                    '<?php echo !empty($ref['fecha_llamada']) ? date('d/m/Y H:i', strtotime($ref['fecha_llamada'])) : ''; ?>',
                    '<?php echo $ref['rating'] ?? 0; ?>',
                    '<?php echo addslashes($ref['resultado_nombre'] ?? ''); ?>',
                    '<?php echo $ref['total_llamadas']; ?>',
                    '<?php echo $ref['activo'] ? 'Activo' : 'Inactivo'; ?>'
                ]);
            <?php endforeach; ?>
            
            // Convertir a CSV
            let csvContent = "data:text/csv;charset=utf-8,";
            datos.forEach(function(rowArray) {
                let row = rowArray.map(function(cell) {
                    // Escapar comillas y agregar comillas si contiene coma
                    if (typeof cell === 'string' && (cell.includes(',') || cell.includes('"'))) {
                        return '"' + cell.replace(/"/g, '""') + '"';
                    }
                    return cell;
                }).join(",");
                csvContent += row + "\r\n";
            });
            
            // Crear y descargar archivo
            let encodedUri = encodeURI(csvContent);
            let link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", `valoracion_tracking_${new Date().toISOString().slice(0,10)}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            Swal.fire({
                icon: 'success',
                title: 'Exportación exitosa',
                text: 'El archivo se ha descargado correctamente'
            });
        }

        // Función para refrescar datos
        function refreshData() {
            window.location.reload();
        }

        // Validar fechas en filtros
        document.querySelector('form.filter-form').addEventListener('submit', function(e) {
            const fechaDesde = document.getElementById('fecha_desde').value;
            const fechaHasta = document.getElementById('fecha_hasta').value;
            
            if (fechaDesde && fechaHasta && fechaDesde > fechaHasta) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Fechas inválidas',
                    text: 'La fecha "Desde" no puede ser mayor que la fecha "Hasta"'
                });
            }
        });
    </script>
</body>
</html>