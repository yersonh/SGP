<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../models/ReferenciadoModel.php';
require_once __DIR__ . '/../../models/LlamadaModel.php';
require_once __DIR__ . '/../../helpers/navigation_helper.php';
require_once __DIR__ . '/../../models/SistemaModel.php';

// Verificar si el usuario está logueado y es SuperAdmin
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    header('Location: ../index.php');
    exit();
}

NavigationHelper::pushUrl();
$pdo = Database::getConnection();
$usuarioModel = new UsuarioModel($pdo);
$referenciadoModel = new ReferenciadoModel($pdo);
$llamadaModel = new LlamadaModel($pdo);
$sistemaModel = new SistemaModel($pdo);

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
$id_referenciador_filtro = $_GET['id_referenciador'] ?? '';

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
if (!empty($id_referenciador_filtro) && $id_referenciador_filtro != 'todos') {
    $filtros['id_referenciador'] = $id_referenciador_filtro;
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

// Información del sistema
$infoSistema = $sistemaModel->getInformacionSistema();
$licenciaInfo = $sistemaModel->getInfoCompletaLicencia();
$infoSistema = $licenciaInfo['info'];
$diasRestantes = $licenciaInfo['dias_restantes'];
$validaHastaFormatted = $licenciaInfo['valida_hasta_formatted'];
$fechaInstalacionFormatted = $licenciaInfo['fecha_instalacion_formatted'];

// PARA LA BARRA QUE DISMINUYE: Calcular porcentaje RESTANTE
$porcentajeRestante = $sistemaModel->getPorcentajeRestanteLicencia();
// Color de la barra basado en lo que RESTA (ahora es más simple)
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
    <title>Valoración Tracking - Panel Super Admin - SGP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../styles/superadmin_valoracion_tracking.css">
    <style>
        /* Estilos adicionales para el nuevo modal */
        .persona-info {
            background-color: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid #0dcaf0;
        }
        
        .info-item {
            margin-bottom: 0.5rem;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .summary-item:last-child {
            border-bottom: none;
        }
        
        .summary-label {
            font-weight: 500;
            color: #666;
        }
        
        .summary-value {
            font-weight: bold;
            color: #333;
        }
        
        .rating-distribution {
            margin-top: 0.5rem;
        }
        
        .rating-bar {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .rating-label {
            width: 80px;
            font-size: 0.9rem;
        }
        
        .rating-progress {
            flex: 1;
            margin: 0 1rem;
        }
        
        .rating-count {
            width: 40px;
            text-align: right;
            font-weight: 500;
        }
        
        .modal-header.bg-info {
            background: linear-gradient(135deg, #0dcaf0 0%, #0a8ea8 100%);
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
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../superadmin_dashboard.php"><i class="fas fa-home"></i> Panel Super Admin</a></li>
                <li class="breadcrumb-item"><a href="superadmin_reportes.php"><i class="fas fa-database"></i> Reportes</a></li>
                <li class="breadcrumb-item active"><i class="fas fa-chart-pie"></i> Valoración Tracking</li>
            </ol>
        </nav>
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
                                    <?php echo $id_referenciador_filtro == $ref['id_usuario'] ? 'selected' : ''; ?>>
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
                            <th>N°</th>
                            <th>Referenciado</th>
                            <th>Cédula</th>
                            <th>Teléfono</th>
                            <th>Referenciador</th>
                            <th>Última Llamada</th>
                            <th>Rating</th>
                             <th>Grupo Parlamentario</th>
                            <th>Resultado</th>
                            <th>Total Llamadas</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($referenciadosConLlamadas)): ?>
                            <tr>
                                <td colspan="12" class="text-center py-4"> <!-- Cambia colspan de 11 a 12 -->
                                    <i class="fas fa-inbox fa-2x mb-3" style="color: #bdc3c7;"></i>
                                    <p class="mb-0">No se encontraron registros con los filtros aplicados</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            // Calcular el total de registros
                            $totalRegistros = count($referenciadosConLlamadas);
                            $contador = $totalRegistros; // Comenzar desde el total
                            foreach ($referenciadosConLlamadas as $referenciado): 
                            ?>
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
                                
                                // Obtener nombre completo para el botón
                                $nombreCompleto = htmlspecialchars($referenciado['nombre'] . ' ' . $referenciado['apellido']);
                                $referenciadorNombre = htmlspecialchars($referenciado['referenciador_nombre'] ?? 'N/A');
                                ?>
                                <tr data-id-referenciado="<?php echo $referenciado['id_referenciado']; ?>"
                                    data-referenciador-nombre="<?php echo $referenciadorNombre; ?>">
                                    <td class="text-center fw-bold"><?php echo $contador; ?></td> <!-- Columna del consecutivo descendente -->
                                    <td>
                                        <strong><?php echo $nombreCompleto; ?></strong>
                                        <?php if (!empty($referenciado['email'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($referenciado['email']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($referenciado['cedula']); ?></td>
                                    <td><?php echo htmlspecialchars($referenciado['telefono']); ?></td>
                                    <td><?php echo $referenciadorNombre; ?></td>
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
                                        <?php if (!empty($referenciado['grupo_nombre'])): ?>
                                            <?php echo htmlspecialchars($referenciado['grupo_nombre']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Sin grupo</span>
                                        <?php endif; ?>
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
                                                    onclick="mostrarDetalleLlamada(<?php echo $referenciado['id_referenciado']; ?>, '<?php echo addslashes($nombreCompleto); ?>')"
                                                    title="Ver historial de llamadas">
                                                <i class="fas fa-history"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php 
                                $contador--; // DECREMENTAR contador para la siguiente fila
                                ?>
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
        <div class="container text-center mb-3">
            <img id="footer-logo" 
                src="../imagenes/Logo-artguru.png" 
                alt="Logo ARTGURU" 
                class="logo-clickable"
                onclick="mostrarModalSistema()"
                title="Haz clic para ver información del sistema"
                data-img-claro="../imagenes/Logo-artguru.png"
                data-img-oscuro="../imagenes/image_no_bg.png">
        </div>
        <div class="container">
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
                <!-- Logo centrado AGRANDADO -->
                <div class="modal-logo-container">
                    <img src="../imagenes/Logo-artguru.png" alt="Logo del Sistema" class="modal-logo">
                </div>
                
                <!-- Título del Sistema - ELIMINADO "Sistema SGP" -->
                <div class="licencia-info">
                    <div class="licencia-header">
                        <h6 class="licencia-title">Licencia Runtime</h6>
                        <span class="licencia-dias">
                            <?php echo $diasRestantes; ?> días restantes
                        </span>
                    </div>
                    
                    <div class="licencia-progress">
                        <!-- BARRA QUE DISMINUYE: muestra el PORCENTAJE RESTANTE -->
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
                    <img src="../imagenes/ingeniero2.png" alt="Logo de Herramienta" class="feature-img-header">
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
                    <!-- Efectividad de la Herramienta -->
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
                    
                    <!-- Integridad de Datos -->
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
                    
                    <!-- Monitoreo de Metas -->
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
                    
                    <!-- Seguridad Avanzada -->
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
                <!-- Botón Uso SGP - Abre enlace en nueva pestaña -->
                <a href="https://sgp-sistema-de-gestion-politica.webnode.com.co/" 
                   target="_blank" 
                   class="btn btn-primary"
                   onclick="cerrarModalSistema();">
                    <i class="fas fa-external-link-alt me-1"></i> Uso SGP
                </a>
                
                <!-- Botón Cerrar - Solo cierra el modal -->
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

    <!-- Modal de Detalles de Llamada -->
    <div class="modal fade" id="modalDetalleLlamada" tabindex="-1" aria-labelledby="modalDetalleLlamadaLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="modalDetalleLlamadaLabel">
                        <i class="fas fa-clipboard-list me-2"></i>Detalles de Llamada
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="persona-info mb-4">
                        <h4 id="detalleNombrePersona" class="mb-3"></h4>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-item">
                                    <i class="fas fa-phone me-2 text-success"></i>
                                    <strong>Teléfono:</strong> <span id="detalleTelefono"></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <i class="fas fa-user-tie me-2 text-primary"></i>
                                    <strong>Referenciador:</strong> <span id="detalleReferenciador">Cargando...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-history me-2"></i>Historial de Llamadas</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm" id="tablaHistorialLlamadas">
                                    <thead>
                                        <tr>
                                            <th>Fecha y Hora</th>
                                            <th>Resultado</th>
                                            <th>Calificación</th>
                                            <th>Observaciones</th>
                                            <th>Usuario</th>
                                        </tr>
                                    </thead>
                                    <tbody id="cuerpoHistorialLlamadas">
                                        <!-- Los datos se cargarán aquí -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Resumen</h6>
                                </div>
                                <div class="card-body">
                                    <div class="summary-item">
                                        <span class="summary-label">Total de llamadas:</span>
                                        <span class="summary-value" id="totalLlamadas">0</span>
                                    </div>
                                    <div class="summary-item">
                                        <span class="summary-label">Promedio de calificación:</span>
                                        <span class="summary-value" id="promedioRating">0</span>
                                    </div>
                                    <div class="summary-item">
                                        <span class="summary-label">Última llamada:</span>
                                        <span class="summary-value" id="ultimaLlamada">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fas fa-star me-2"></i>Distribución de Calificaciones</h6>
                                </div>
                                <div class="card-body">
                                    <div id="distribucionRating" class="rating-distribution">
                                        <!-- Se generarán las barras de distribución aquí -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cerrar
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
    <script src="../js/modal-sistema.js"></script>
    <script>
// En la función de DataTables, actualizar el orden
$(document).ready(function() {
    const table = $('#trackingTable');
    
    // Contar cuántas filas con datos reales hay (filas con 12 columnas ahora)
    const dataRows = table.find('tbody tr').filter(function() {
        return $(this).find('td').length === 12; // Cambiado de 11 a 12
    });
    
    // Solo inicializar DataTables si hay al menos 1 fila con datos
    if (dataRows.length > 0) {
        table.DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
            },
            pageLength: 25,
            order: [[0, 'desc']], // Ordenar por la primera columna (consecutivo) en forma descendente
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
            responsive: true,
            columnDefs: [
                {
                    orderable: false,
                    targets: 0 // Hacer que la columna del consecutivo no sea ordenable
                }
            ]
        });
    }
});
        
        // Función para actualizar logo según tema
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
         // Ejecutar al cargar y cuando cambie el tema
        document.addEventListener('DOMContentLoaded', function() {
            actualizarLogoSegunTema();
        });

        // Escuchar cambios en el tema del sistema
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
            actualizarLogoSegunTema();
        });

        // Función para mostrar notificación
        function showNotification(message, type = 'success') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: type,
                title: message,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        }

        // Función para obtener color según resultado
        function getColorResultado(idResultado) {
            const colors = {
                1: 'bg-success',
                2: 'bg-warning',
                3: 'bg-danger',
                4: 'bg-info',
                5: 'bg-primary'
            };
            return colors[idResultado] || 'bg-secondary';
        }

        // Función para generar distribución de ratings
        function generarDistribucionRating(conteoRating, totalConRating) {
            const container = document.getElementById('distribucionRating');
            if (totalConRating === 0) {
                container.innerHTML = '<p class="text-muted mb-0">No hay datos de calificación</p>';
                return;
            }
            
            let html = '';
            for (let i = 5; i >= 1; i--) {
                const count = conteoRating[i] || 0;
                const percentage = totalConRating > 0 ? (count / totalConRating * 100).toFixed(1) : 0;
                
                let stars = '';
                for (let j = 1; j <= 5; j++) {
                    if (j <= i) {
                        stars += '<i class="fas fa-star text-warning"></i>';
                    }
                }
                
                html += `
                    <div class="rating-bar">
                        <div class="rating-label">${stars}</div>
                        <div class="rating-progress">
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-warning" role="progressbar" 
                                     style="width: ${percentage}%" 
                                     aria-valuenow="${percentage}" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100"></div>
                            </div>
                        </div>
                        <div class="rating-count">${percentage}%</div>
                    </div>
                `;
            }
            container.innerHTML = html;
        }

        // Función principal para mostrar detalles de llamada
        async function mostrarDetalleLlamada(idReferenciado, nombre) {
            try {
                // Encontrar la fila correspondiente en la tabla para obtener el referenciador
                const fila = document.querySelector(`tr[data-id-referenciado="${idReferenciado}"]`);
                let referenciadorNombre = 'N/A';
                
                if (fila) {
                    referenciadorNombre = fila.getAttribute('data-referenciador-nombre') || 'N/A';
                }
                
                // Mostrar loading
                const modalDetalle = document.getElementById('modalDetalleLlamada');
                const cuerpo = document.getElementById('cuerpoHistorialLlamadas');
                cuerpo.innerHTML = '<tr><td colspan="5" class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando historial...</td></tr>';
                
                // Actualizar título e información del referenciador INMEDIATAMENTE
                document.getElementById('detalleNombrePersona').textContent = nombre;
                document.getElementById('detalleReferenciador').textContent = referenciadorNombre;
                
                // Mostrar modal
                const modal = new bootstrap.Modal(modalDetalle);
                modal.show();
                
                // Obtener datos del historial
                const response = await fetch(`../ajax/obtener_historial_llamadas.php?id_referenciado=${idReferenciado}`);
                const data = await response.json();
                
                if (data.success) {
                    // Actualizar información personal
                    if (data.referenciado) {
                        document.getElementById('detalleTelefono').textContent = data.referenciado.telefono || 'No registrado';
                        // Si la API devuelve el referenciador, usarlo (sobrescribe el de la tabla si es diferente)
                        if (data.referenciado.referenciador_nombre) {
                            document.getElementById('detalleReferenciador').textContent = data.referenciado.referenciador_nombre;
                        }
                    }
                    
                    // Actualizar historial
                    let historialHTML = '';
                    let totalRating = 0;
                    let conteoRating = {1: 0, 2: 0, 3: 0, 4: 0, 5: 0};
                    
                    if (data.historial && data.historial.length > 0) {
                        data.historial.forEach(llamada => {
                            // Formatear fecha
                            const fecha = new Date(llamada.fecha_llamada);
                            const fechaStr = fecha.toLocaleDateString('es-ES', {
                                day: '2-digit',
                                month: '2-digit',
                                year: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            });
                            
                            // Calcular rating
                            if (llamada.rating) {
                                totalRating += llamada.rating;
                                conteoRating[llamada.rating]++;
                            }
                            
                            // Generar estrellas para rating
                            let estrellasHTML = '';
                            if (llamada.rating) {
                                for (let i = 1; i <= 5; i++) {
                                    if (i <= llamada.rating) {
                                        estrellasHTML += '<i class="fas fa-star text-warning"></i>';
                                    } else {
                                        estrellasHTML += '<i class="far fa-star text-muted"></i>';
                                    }
                                }
                            } else {
                                estrellasHTML = '<span class="text-muted">Sin calificar</span>';
                            }
                            
                            historialHTML += `
                                <tr>
                                    <td>${fechaStr}</td>
                                    <td><span class="badge ${getColorResultado(llamada.id_resultado)}">${llamada.resultado_nombre || 'Sin resultado'}</span></td>
                                    <td>${estrellasHTML}</td>
                                    <td>${llamada.observaciones || '<span class="text-muted">Sin observaciones</span>'}</td>
                                    <td>${llamada.usuario_nombre || 'Sistema'}</td>
                                </tr>
                            `;
                        });
                        
                        // Actualizar resumen
                        document.getElementById('totalLlamadas').textContent = data.historial.length;
                        
                        if (totalRating > 0) {
                            const promedio = (totalRating / data.historial.filter(l => l.rating).length).toFixed(1);
                            document.getElementById('promedioRating').textContent = promedio;
                            document.getElementById('promedioRating').innerHTML += ` <i class="fas fa-star text-warning"></i>`;
                        }
                        
                        // Última llamada
                        const ultima = new Date(data.historial[0].fecha_llamada);
                        document.getElementById('ultimaLlamada').textContent = ultima.toLocaleDateString('es-ES', {
                            day: '2-digit',
                            month: 'short',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                        
                        // Generar distribución de ratings
                        generarDistribucionRating(conteoRating, data.historial.filter(l => l.rating).length);
                        
                    } else {
                        historialHTML = '<tr><td colspan="5" class="text-center text-muted">No hay historial de llamadas registradas</td></tr>';
                        document.getElementById('totalLlamadas').textContent = '0';
                        document.getElementById('promedioRating').textContent = 'N/A';
                        document.getElementById('ultimaLlamada').textContent = 'Nunca';
                        document.getElementById('distribucionRating').innerHTML = '<p class="text-muted mb-0">No hay datos de calificación</p>';
                    }
                    
                    cuerpo.innerHTML = historialHTML;
                    
                } else {
                    showNotification('Error al cargar el historial: ' + (data.message || 'Error desconocido'), 'error');
                    cuerpo.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error al cargar el historial</td></tr>';
                }
                
            } catch (error) {
                showNotification('Error de conexión: ' + error.message, 'error');
                document.getElementById('cuerpoHistorialLlamadas').innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error de conexión</td></tr>';
            }
        }

        // Función para exportar a Excel
function exportarExcel() {
    // Obtener todos los filtros actuales
    const params = new URLSearchParams(window.location.search);
    
    // Agregar parámetros de exportación
    params.append('export', 'excel');
    
    // Crear URL con todos los filtros
    const exportUrl = 'exportar_valoracion_tracking.php?' + params.toString();
    
    // Mostrar confirmación
    Swal.fire({
        title: 'Exportar a Excel',
        text: '¿Desea exportar los datos filtrados a Excel?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, exportar',
        cancelButtonText: 'Cancelar',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            // Descargar el archivo
            return new Promise((resolve) => {
                // Crear enlace invisible para descarga
                const link = document.createElement('a');
                link.href = exportUrl;
                link.target = '_blank';
                link.style.display = 'none';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                // Pequeño retraso para asegurar la descarga
                setTimeout(() => {
                    resolve();
                }, 1000);
            });
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: 'Exportación iniciada',
                text: 'El archivo Excel se está descargando',
                timer: 3000,
                showConfirmButton: false
            });
        }
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