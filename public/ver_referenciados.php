<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../models/ReferenciadoModel.php';
require_once __DIR__ . '/../models/LlamadaModel.php';  // NUEVO: Agregado para historial de llamadas
require_once __DIR__ . '/../models/SistemaModel.php';

// Verificar si el usuario está logueado y es referenciador
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'Referenciador') {
    header('Location: index.php');
    exit();
}

$pdo = Database::getConnection();
$usuarioModel = new UsuarioModel($pdo);
$referenciadoModel = new ReferenciadoModel($pdo);
$llamadaModel = new LlamadaModel($pdo);  // NUEVO: Instancia para historial de llamadas
$sistemaModel = new SistemaModel($pdo);

$id_usuario_logueado = $_SESSION['id_usuario'];

// Obtener datos del usuario logueado
$usuario_logueado = $usuarioModel->getUsuarioById($id_usuario_logueado);

// Obtener referenciados del usuario
$referenciados = $referenciadoModel->getReferenciadosByUsuario($id_usuario_logueado);
$referenciadosActivos = $referenciadoModel->getReferenciadosByUsuarioActivo($id_usuario_logueado);

// Contar referenciados activos e inactivos
$total_referenciadosActivos = count($referenciadosActivos);
$total_referenciados = count($referenciados);
$activos = 0;
$inactivos = 0;
foreach ($referenciados as $ref) {
    if ($ref['activo'] === true || $ref['activo'] === 't' || $ref['activo'] == 1) {
        $activos++;
    } else {
        $inactivos++;
    }
}

// Actualizar último registro
$fecha_actual = date('Y-m-d H:i:s');
$usuarioModel->actualizarUltimoRegistro($id_usuario_logueado, $fecha_actual);
$fecha_formateada = date('d/m/Y H:i:s', strtotime($fecha_actual));

// Obtener información completa de la licencia
$licenciaInfo = $sistemaModel->getInfoCompletaLicencia();

// Extraer valores
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

// Tope asignado
$tope_asignado = $usuario_logueado['tope'] ?? 0;

// Calcular porcentaje REAL del tope (puede ser más de 100%)
$porcentaje_tope_real = ($tope_asignado > 0) ? ($activos * 100) / $tope_asignado : 0;
// Calcular porcentaje para la barra (no más de 100%)
$porcentaje_tope_barra = ($tope_asignado > 0) ? min(100, ($activos * 100) / $tope_asignado) : 0;
$restante_tope = max(0, $tope_asignado - $activos);

// Verificar si se alcanzó o superó el 100% para mostrar el ícono de trofeo
$mostrar_trofeo_tope = $porcentaje_tope_real >= 100;

// ============================================================================
// ¡¡¡AQUÍ ESTÁ LA PARTE IMPORTANTE!!! - CONSULTA SQL PARA CONTAR VOTOS
// ============================================================================
$votos = $referenciadoModel->contarVotosDesglosados($id_usuario_logueado);

// Extraer todos los valores que necesitamos
$solo_camara = $votos['solo_camara'] ?? 0;
$solo_senado = $votos['solo_senado'] ?? 0;
$pacha = $votos['pacha'] ?? 0;
$total_camara = $votos['total_camara'] ?? 0;  // (solo_camara + pacha)
$total_senado = $votos['total_senado'] ?? 0;  // (solo_senado + pacha)
$total_activos = $votos['total_activos'] ?? 0;

// Calcular porcentajes REALES sobre el tope asignado (pueden ser más de 100%)
$porcentaje_camara_real = ($tope_asignado > 0) ? ($total_camara * 100) / $tope_asignado : 0;
$porcentaje_senado_real = ($tope_asignado > 0) ? ($total_senado * 100) / $tope_asignado : 0;

// Calcular porcentajes para las barras (no más de 100%)
$porcentaje_camara_barra = ($tope_asignado > 0) ? min(100, ($total_camara * 100) / $tope_asignado) : 0;
$porcentaje_senado_barra = ($tope_asignado > 0) ? min(100, ($total_senado * 100) / $tope_asignado) : 0;

// Verificar si se alcanzó o superó el 100% para mostrar íconos de trofeo
$mostrar_trofeo_camara = $porcentaje_camara_real >= 100;
$mostrar_trofeo_senado = $porcentaje_senado_real >= 100;

// Calcular porcentajes para la gráfica (sobre total activos)
$porc_camara = ($total_activos > 0) ? round(($total_camara * 100) / $total_activos, 1) : 0;
$porc_senado = ($total_activos > 0) ? round(($total_senado * 100) / $total_activos, 1) : 0;
$porc_ambos = ($total_activos > 0) ? round(($pacha * 100) / $total_activos, 1) : 0;
// ============================================================================

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Referenciados - SGP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles/referenciador.css">
    <link rel="stylesheet" href="styles/ver_referenciados_referenciador.css">
    <style>
        /* Estilos para el ícono de trofeo */
        .trofeo-icon {
            color: #FFD700; /* Color dorado */
            margin-left: 8px;
            font-size: 1.2em;
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .tropeo-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .trofeo-badge {
            background-color: #FFD700;
            color: #8B4513;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
            margin-left: 10px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        /* Estilos para el botón de historial de llamadas */
        .btn-historial-llamadas {
            position: relative;
        }
        
        .btn-historial-llamadas .badge {
            position: absolute;
            top: -5px;
            right: -5px;
            font-size: 0.6em;
            padding: 2px 5px;
        }
        
        /* Estilos para el modal de historial */
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
    </style>
    <!-- Cargar Chart.js ANTES de que se use -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Definir datos de la gráfica ANTES de que se usen
        const graficaData = {
            camara: <?php echo $total_camara; ?>,
            senado: <?php echo $total_senado; ?>,
            total: <?php echo $activos; ?>,
            porcentajes: {
                camara: <?php echo $porc_camara; ?>,
                senado: <?php echo $porc_senado; ?>,
                ambos: <?php echo $porc_ambos; ?>
            }
        };
    </script>
</head>
<body>
   <!-- En la sección del header -->
<header class="main-header">
    <div class="header-container">
        <div class="header-top">
            <div class="header-title">
                <h1><i class="fas fa-users"></i> Mis Referenciados</h1>
                <div class="user-info">
                    <i class="fas fa-user-circle"></i>
                    <span><?php echo htmlspecialchars($usuario_logueado['nombres'] . ' ' . $usuario_logueado['apellidos']); ?></span>
                </div>
            </div>
            <div class="header-actions">
                <!-- Botón de Volver al Formulario ahora está primero (a la izquierda) -->
                <a href="referenciador.php" class="btn-volver">
                    <i class="fas fa-arrow-left"></i> Volver al Formulario
                </a>
                
                <!-- Botón de Cerrar Sesión ahora está segundo (a la derecha) -->
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
        </div>
    </div>
</header>
<!-- CONTADOR COMPACTO -->
    <div class="countdown-compact-container">
        <div class="countdown-compact">
            <div class="countdown-compact-title">
                <i class="fas fa-hourglass-half"></i>
                <span>Elecciones Legislativas 2026</span>
            </div>
            <div class="countdown-compact-timer">
                <span id="compact-days">00</span>d 
                <span id="compact-hours">00</span>h 
                <span id="compact-minutes">00</span>m 
                <span id="compact-seconds">00</span>s
            </div>
            <div class="countdown-compact-date">
                <i class="fas fa-calendar-alt"></i>
                8 Marzo 2026
            </div>
        </div>
    </div>
    <!-- Contenido Principal -->
    <div class="main-container">
        <div class="referenciados-container">
            <!-- Tarjetas de Estadísticas -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_referenciados; ?></div>
                    <div class="stat-label">Total Referenciados</div>
                </div>
                
                <div class="stat-card activos">
                    <div class="stat-number"><?php echo $activos; ?></div>
                    <div class="stat-label">Referenciados Activos</div>
                </div>
                
                <div class="stat-card inactivos">
                    <div class="stat-number"><?php echo $inactivos; ?></div>
                    <div class="stat-label">Referenciados Inactivos</div>
                </div>
            </div>
            
            <!-- NUEVA BARRA DE PROGRESO DEL TOPE -->
            <div class="tope-progress-container">
                <div class="tope-progress-header">
                    <div class="tropeo-container">
                        <h4><i class="fas fa-chart-line me-2"></i>Progreso del Tope (Solo Activos)</h4>
                        <?php if ($mostrar_trofeo_tope): ?>
                        <div class="trofeo-badge">
                            <i class="fas fa-trophy"></i> ¡Meta Superada!
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="tope-stats">
                        <span class="tope-stat">
                            <strong><?php echo $activos; ?></strong> / <?php echo $tope_asignado; ?> referenciados activos
                        </span>
                        <span class="tope-percentage">
                            <?php echo number_format($porcentaje_tope_real, 1) . '%'; ?>
                            <?php if ($mostrar_trofeo_tope): ?>
                                <i class="fas fa-trophy trofeo-icon"></i>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
                
                <div class="tope-progress-bar">
                    <div class="tope-progress-fill" 
                         style="width: <?php echo $porcentaje_tope_barra; ?>%; 
                                background: <?php 
                                    if ($porcentaje_tope_real >= 100) echo '#2ecc71';
                                    elseif ($porcentaje_tope_real >= 75) echo '#3498db';
                                    elseif ($porcentaje_tope_real >= 50) echo '#f39c12';
                                    else echo '#e74c3c';
                                ?>;">
                    </div>
                </div>

                
                <div class="tope-info">
                    <div class="tope-info-item">
                        <span class="tope-info-label">Tope asignado:</span>
                        <span class="tope-info-value"><?php echo $tope_asignado; ?> personas</span>
                    </div>
                    <div class="tope-info-item">
                        <span class="tope-info-label">Activos actuales:</span>
                        <span class="tope-info-value">
                            <span class="text-success">
                                <?php echo $activos; ?> personas
                            </span>
                        </span>
                    </div>
                    <div class="tope-info-item">
                        <span class="tope-info-label">Inactivos:</span>
                        <span class="tope-info-value">
                            <span class="text-danger">
                                <?php echo $inactivos; ?> personas
                            </span>
                        </span>
                    </div>
                    <div class="tope-info-item">
                        <span class="tope-info-label">Restante:</span>
                        <span class="tope-info-value">
                            <?php echo $restante_tope . ' personas'; ?>
                        </span>
                    </div>
                </div>
            </div>
             <!-- BARRAS DE VOTOS POR CÁMARA Y SENADO -->
            <div class="votos-container">
                <div class="votos-grid">
                    <!-- Barra para Cámara -->
                    <div class="voto-card">
                        <div class="voto-header">
                            <div class="voto-title">
                                <i class="fas fa-landmark me-2" style="color: #3498db;"></i>
                                <h5>Votos Cámara</h5>
                                <?php if ($mostrar_trofeo_camara): ?>
                                <i class="fas fa-trophy trofeo-icon" title="¡Meta de Cámara alcanzada o superada!"></i>
                                <?php endif; ?>
                            </div>
                            <div class="voto-stats">
                                <span class="voto-count">
                                    <strong><?php echo $total_camara; ?></strong> / <?php echo $tope_asignado; ?>
                                </span>
                                <span class="voto-percentage">
                                    <?php echo number_format($porcentaje_camara_real, 1) . '%'; ?>
                                    <?php if ($mostrar_trofeo_camara): ?>
                                        <i class="fas fa-trophy trofeo-icon"></i>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="voto-progress-bar">
                            <div class="voto-progress-fill" 
                                 style="width: <?php echo $porcentaje_camara_barra; ?>%; 
                                        background: <?php 
                                            if ($porcentaje_camara_real >= 100) echo '#2ecc71';
                                            elseif ($porcentaje_camara_real >= 75) echo '#3498db';
                                            elseif ($porcentaje_camara_real >= 50) echo '#f39c12';
                                            else echo '#e74c3c';
                                        ?>;">
                            </div>
                        </div>
                        
                        <div class="voto-info">
                            <div class="voto-info-item">
                                <i class="fas fa-users me-1"></i>
                                <span>Votos obtenidos: <strong><?php echo $total_camara; ?></strong></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Barra para Senado -->
                    <div class="voto-card">
                        <div class="voto-header">
                            <div class="voto-title">
                                <i class="fas fa-balance-scale me-2" style="color: #9b59b6;"></i>
                                <h5>Votos Senado</h5>
                                <?php if ($mostrar_trofeo_senado): ?>
                                <i class="fas fa-trophy trofeo-icon" title="¡Meta de Senado alcanzada o superada!"></i>
                                <?php endif; ?>
                            </div>
                            <div class="voto-stats">
                                <span class="voto-count">
                                    <strong><?php echo $total_senado; ?></strong> / <?php echo $tope_asignado; ?>
                                </span>
                                <span class="voto-percentage">
                                    <?php echo number_format($porcentaje_senado_real, 1) . '%'; ?>
                                    <?php if ($mostrar_trofeo_senado): ?>
                                        <i class="fas fa-trophy trofeo-icon"></i>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="voto-progress-bar">
                            <div class="voto-progress-fill" 
                                 style="width: <?php echo $porcentaje_senado_barra; ?>%; 
                                        background: <?php 
                                            if ($porcentaje_senado_real >= 100) echo '#2ecc71';
                                            elseif ($porcentaje_senado_real >= 75) echo '#9b59b6';
                                            elseif ($porcentaje_senado_real >= 50) echo '#f39c12';
                                            else echo '#e74c3c';
                                        ?>;">
                            </div>
                        </div>
                        
                        <div class="voto-info">
                            <div class="voto-info-item">
                                <i class="fas fa-users me-1"></i>
                                <span>Votos obtenidos: <strong><?php echo $total_senado; ?></strong></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Resumen de grupos parlamentarios -->
                 <div class="grupos-resumen">
                    <h6><i class="fas fa-chart-pie me-2"></i>Distribución por Grupo Parlamentario</h6>
                    <div class="grupos-stats">
                        <div class="grupo-item">
                            <span class="grupo-label">Solo Cámara:</span>
                            <span class="grupo-value"><?php echo $solo_camara; ?> personas</span>
                        </div>
                        <div class="grupo-item">
                            <span class="grupo-label">Solo Senado:</span>
                            <span class="grupo-value"><?php echo $solo_senado; ?> personas</span>
                        </div>
                        <div class="grupo-item">
                            <span class="grupo-label">Ambos (PACHA):</span>
                            <span class="grupo-value">
                                <?php echo $pacha; ?> personas
                            </span>
                        </div>
                    </div>
                    
                    <!-- Botón para abrir gráfica -->
                    <div class="text-center mt-3">
                        <button class="btn-ver-grafica" data-bs-toggle="modal" data-bs-target="#modalGrafica">
                            <i class="fas fa-chart-pie me-2"></i> Ver Gráfica de Distribución
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Tabla de Referenciados -->
<div class="referenciados-table">
    <div class="table-header">
        <h3><i class="fas fa-list-alt"></i> Lista de Referenciados</h3>
        <div class="table-header-actions">
            <span>Fecha y hora actual: <?php echo $fecha_formateada; ?></span>
            <?php if ($total_referenciados > 0): ?>
            <button class="btn-export" data-bs-toggle="modal" data-bs-target="#exportModal">
                <i class="fas fa-download"></i> Exportar
            </button>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($total_referenciados > 0): ?>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Nombre Completo</th>
                    <th>Cédula</th>
                    <th>Teléfono</th>
                    <th>Email</th>
                    <th>Afinidad</th>
                    <th>Grupo Parlamentario</th>
                    <th>Líder</th> 
                    <th>Vota</th>
                    <th>Puesto/Mesa</th>
                    <th>Fecha Registro</th>
                    <th>Estado</th>
                    <th>Acciones</th> <!-- NUEVA COLUMNA VACÍA -->
                </tr>
            </thead>
            <tbody>
                <?php foreach ($referenciados as $referenciado): ?>
                <?php 
                $activo = $referenciado['activo'];
                $esta_activo = ($activo === true || $activo === 't' || $activo == 1);
                $vota_fuera = $referenciado['vota_fuera'] === 'Si';
                
                // Obtener número de llamadas para este referenciado
                $totalLlamadasReferenciado = $llamadaModel->contarLlamadasPorReferenciado($referenciado['id_referenciado']);
                ?>
                <tr>
                    <!-- Nombre Completo -->
                    <td>
                        <strong><?php echo htmlspecialchars($referenciado['nombre'] . ' ' . $referenciado['apellido']); ?></strong>
                    </td>
                    
                    <!-- Cédula -->
                    <td>
                        <?php echo htmlspecialchars($referenciado['cedula']); ?>
                    </td>
                    
                    <!-- Teléfono -->
                    <td>
                        <?php echo htmlspecialchars($referenciado['telefono']); ?>
                    </td>
                    
                    <!-- Email -->
                    <td>
                        <?php echo htmlspecialchars($referenciado['email']); ?>
                    </td>
                    
                    <!-- Afinidad -->
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge-afinidad">
                                <?php echo $referenciado['afinidad']; ?>/5
                            </span>
                            <span class="afinidad-stars">
                                <?php 
                                $afinidad = intval($referenciado['afinidad']);
                                echo str_repeat('<i class="fas fa-star"></i>', $afinidad) . 
                                     str_repeat('<i class="far fa-star"></i>', 5 - $afinidad);
                                ?>
                            </span>
                        </div>
                    </td>
                    
                    <!-- Grupo Parlamentario -->
                    <td>
                        <?php 
                        $grupo_nombre = !empty($referenciado['grupo_nombre']) 
                            ? htmlspecialchars($referenciado['grupo_nombre']) 
                            : 'Sin asignar';
                        
                        if ($grupo_nombre === 'Sin asignar') {
                            echo '<span class="badge bg-secondary">' . $grupo_nombre . '</span>';
                        } else {
                            echo '<span class="badge bg-primary">' . $grupo_nombre . '</span>';
                        }
                        ?>
                    </td>
                    <!-- NUEVA COLUMNA: Líder -->
                    <td>
                        <?php 
                        if (!empty($referenciado['lider_nombres']) || !empty($referenciado['lider_apellidos'])) {
                            $lider_completo = trim(
                                ($referenciado['lider_nombres'] ?? '') . ' ' . 
                                ($referenciado['lider_apellidos'] ?? '')
                            );
                            echo '<span class="badge bg-info">' . htmlspecialchars($lider_completo) . '</span>';
                            
                            // Opcional: mostrar también la cédula del líder
                            if (!empty($referenciado['lider_cc'])) {
                                echo '<br><small class="text-muted">CC: ' . htmlspecialchars($referenciado['lider_cc']) . '</small>';
                            }
                        } else {
                            echo '<span class="badge bg-secondary">Sin líder</span>';
                        }
                        ?>
                    </td>
                    
                    <!-- Vota -->
                    <td>
                        <?php if ($vota_fuera): ?>
                            <span class="vota-badge vota-fuera">
                                <i class="fas fa-external-link-alt me-1"></i> Fuera
                            </span>
                        <?php else: ?>
                            <span class="vota-badge vota-aqui">
                                <i class="fas fa-home me-1"></i> Aquí
                            </span>
                        <?php endif; ?>
                    </td>
                    
                    <!-- Puesto/Mesa -->
                    <td>
                        <?php if ($vota_fuera): ?>
                            <div>
                                <small class="text-muted">Puesto:</small><br>
                                <strong><?php echo htmlspecialchars($referenciado['puesto_votacion_fuera'] ?? 'No especificado'); ?></strong>
                            </div>
                            <div class="mt-1">
                                <small class="text-muted">Mesa:</small><br>
                                <strong><?php echo htmlspecialchars($referenciado['mesa_fuera'] ?? 'No especificado'); ?></strong>
                            </div>
                        <?php else: ?>
                            <div>
                                <small class="text-muted">Puesto:</small><br>
                                <strong><?php echo htmlspecialchars($referenciado['puesto_votacion_nombre'] ?? 'No especificado'); ?></strong>
                            </div>
                            <div class="mt-1">
                                <small class="text-muted">Mesa:</small><br>
                                <strong><?php echo htmlspecialchars($referenciado['mesa'] ?? 'No especificado'); ?></strong>
                            </div>
                        <?php endif; ?>
                    </td>
                    
                    <!-- Fecha Registro -->
                    <td>
                        <?php 
                        $fecha_registro = date('d/m/Y H:i', strtotime($referenciado['fecha_registro']));
                        echo htmlspecialchars($fecha_registro);
                        ?>
                    </td>
                    
                    <!-- Estado -->
                    <td>
                        <?php if ($esta_activo): ?>
                            <span class="badge-estado badge-activo">
                                <i class="fas fa-check-circle me-1"></i> Activo
                            </span>
                        <?php else: ?>
                            <span class="badge-estado badge-inactivo">
                                <i class="fas fa-times-circle me-1"></i> Inactivo
                            </span>
                        <?php endif; ?>
                    </td>
                    
                    <!-- NUEVA COLUMNA: Acciones (con botón de historial de llamadas) -->
                    <td>
                        <button type="button" 
                                class="btn btn-sm btn-outline-primary btn-historial-llamadas"
                                onclick="mostrarHistorialLlamadas(
                                    <?php echo $referenciado['id_referenciado']; ?>, 
                                    '<?php echo addslashes($referenciado['nombre'] . ' ' . $referenciado['apellido']); ?>',
                                    '<?php echo addslashes($referenciado['telefono'] ?? ''); ?>',
                                    '<?php echo addslashes($referenciado['email'] ?? ''); ?>'
                                )"
                                title="Ver historial de llamadas">
                            <i class="fas fa-history"></i>
                            <?php if ($totalLlamadasReferenciado > 0): ?>
                                <span class="badge bg-danger ms-1"><?php echo $totalLlamadasReferenciado; ?></span>
                            <?php endif; ?>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-users"></i>
        <h3>No hay referenciados registrados</h3>
        <p>No has registrado ninguna persona referenciada aún. Comienza agregando tu primer referenciado.</p>
        <a href="referenciador.php" class="btn-volver">
            <i class="fas fa-plus-circle"></i> Agregar Primer Referenciado
        </a>
    </div>
    <?php endif; ?>
</div>
        </div>
    </div>

     <!-- Footer del sistema -->
    <footer class="system-footer">
        <div class="container text-center mb-3">
            <img src="imagenes/Logo-artguru.png" 
                alt="Logo" 
                class="logo-clickable"
                onclick="mostrarModalSistema()"
                title="Haz clic para ver información del sistema">
        </div>

        <div class="container text-center">
            <p>
                © Derechos de autor Reservados • Equipo de soporte • <strong>SISGONTech</strong><br>
                Email: soportesgp@gmail.com • Contacto: +57 3138486960 • Puerto Gaitán, Colombia • <?php echo date('Y'); ?>
            </p>
        </div>
    </footer>

    <!-- Modal de Historial de Llamadas -->
    <div class="modal fade" id="modalHistorialLlamadas" tabindex="-1" aria-labelledby="modalHistorialLlamadasLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="modalHistorialLlamadasLabel">
                        <i class="fas fa-history me-2"></i>Historial de Llamadas
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <h4 id="nombreReferenciado"></h4>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="info-item">
                                    <i class="fas fa-phone me-2"></i>
                                    <strong>Teléfono:</strong> <span id="telefonoReferenciado"></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <i class="fas fa-envelope me-2"></i>
                                    <strong>Email:</strong> <span id="emailReferenciado"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-phone-alt me-2"></i> Historial de Contacto</h6>
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
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Resumen</h6>
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
                    <img src="imagenes/Logo-artguru.png" alt="Logo del Sistema" class="modal-logo">
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

    <!-- Modal de Gráfica de Distribución -->
    <div class="modal fade" id="modalGrafica" tabindex="-1" aria-labelledby="modalGraficaLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content modal-grafica">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title" id="modalGraficaLabel">
                        <i class="fas fa-chart-pie me-2"></i>Gráfica de Distribución - Cámara vs Senado
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="grafica-modal-content">
                        <!-- Gráfica -->
                        <div class="grafica-canvas-modal">
                            <canvas id="graficaTortaModal2" width="500" height="500"></canvas>
                        </div>
                        
                        <!-- Información y controles -->
                        <div class="grafica-info-modal">
                            <div class="grafica-header-modal">
                                <h4><i class="fas fa-chart-bar me-2"></i>Análisis de Distribución</h4>
                                <div class="grafica-controls">
                                    <button class="btn-grafica-control" onclick="toggleGraficaModal('modalGrafica')" id="btnToggleGrafica2">
                                        <i class="fas fa-exchange-alt me-1"></i> Cambiar a torta sólida
                                    </button>
                                    <button class="btn-grafica-control" onclick="descargarGrafica('modalGrafica')">
                                        <i class="fas fa-download me-1"></i> Descargar
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Estadísticas -->
                            <div class="estadisticas-grid">
                                <div class="estadistica-card camara">
                                    <div class="estadistica-icon">
                                        <i class="fas fa-landmark"></i>
                                    </div>
                                    <div class="estadistica-content">
                                        <div class="estadistica-label">Referidos a Cámara</div>
                                        <div class="estadistica-value"><?php echo $total_camara; ?></div>
                                        <div class="estadistica-porcentaje">
                                            <?php echo $porc_camara . '%'; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="estadistica-card senado">
                                    <div class="estadistica-icon">
                                        <i class="fas fa-balance-scale"></i>
                                    </div>
                                    <div class="estadistica-content">
                                        <div class="estadistica-label">Referidos a Senado</div>
                                        <div class="estadistica-value"><?php echo $total_senado; ?></div>
                                        <div class="estadistica-porcentaje">
                                            <?php echo $porc_senado . '%'; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="estadistica-card ambos">
                                    <div class="estadistica-icon">
                                        <i class="fas fa-handshake"></i>
                                    </div>
                                    <div class="estadistica-content">
                                        <div class="estadistica-label">Votan por ambos</div>
                                        <div class="estadistica-value">
                                            <?php echo $pacha; ?>
                                        </div>
                                        <div class="estadistica-porcentaje">
                                            <?php echo $porc_ambos . '%'; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="estadistica-card total">
                                    <div class="estadistica-icon">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div class="estadistica-content">
                                        <div class="estadistica-label">Total Referenciados</div>
                                        <div class="estadistica-value"><?php echo $total_referenciadosActivos; ?></div>
                                        <div class="estadistica-porcentaje">100%</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Detalles -->
                            <div class="detalles-distribucion">
                                <h5><i class="fas fa-info-circle me-2"></i>Detalles de la Distribución (Solo Activos)</h5>
                                <div class="detalle-item">
                                    <span class="detalle-label">Solo votan por Cámara:</span>
                                    <span class="detalle-value"><?php echo $solo_camara; ?> personas</span>
                                </div>
                                <div class="detalle-item">
                                    <span class="detalle-label">Solo votan por Senado:</span>
                                    <span class="detalle-value"><?php echo $solo_senado; ?> personas</span>
                                </div>
                                <div class="detalle-item">
                                    <span class="detalle-label">Votan por ambos:</span>
                                    <span class="detalle-value"><?php echo $pacha; ?> personas</span>
                                </div>
                                <div class="detalle-item">
                                    <span class="detalle-label">Total referenciados activos:</span>
                                    <span class="detalle-value"><?php echo $activos; ?> personas</span>
                                </div>
                                <div class="detalle-item">
                                    <span class="detalle-label">Tope asignado:</span>
                                    <span class="detalle-value"><?php echo $tope_asignado; ?> personas</span>
                                </div>
                            </div>
                            
                            <!-- Resumen -->
                            <div class="resumen-final">
                                <div class="resumen-text">
                                    <i class="fas fa-lightbulb text-warning me-2"></i>
                                    <span>
                                        <strong>Análisis:</strong> 
                                        <?php
                                        if ($total_camara > $total_senado) {
                                            echo "Tienes más referidos activos para Cámara (" . $total_camara . ") que para Senado (" . $total_senado . ").";
                                        } elseif ($total_senado > $total_camara) {
                                            echo "Tienes más referidos activos para Senado (" . $total_senado . ") que para Cámara (" . $total_camara . ").";
                                        } else {
                                            echo "Tienes una distribución balanceada entre Cámara y Senado en referidos activos.";
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Exportación -->
    <?php if ($total_referenciados > 0): ?>
    <div class="modal fade" id="exportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-download me-2"></i> Exportar Mis Referenciados</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Seleccione el formato de exportación:</p>
                    <div class="d-grid gap-3">
                        <button class="btn btn-success btn-lg py-3" onclick="exportarMisReferenciados('excel')">
                            <i class="fas fa-file-excel fa-lg me-2"></i> Exportar a Excel (.xls)
                        </button>
                        <button class="btn btn-primary btn-lg py-3" onclick="exportarMisReferenciados('pdf')">
                            <i class="fas fa-file-pdf fa-lg me-2"></i> Exportar a PDF
                        </button>
                    </div>
                    <hr class="my-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="exportSoloActivos" style="transform: scale(1.3);">
                        <label class="form-check-label ms-2" for="exportSoloActivos">
                            <i class="fas fa-filter me-1"></i> Exportar solo referidos activos
                        </label>
                    </div>
                    <div class="mt-3 text-muted small">
                        <i class="fas fa-info-circle me-1"></i> Solo se exportarán tus referenciados
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Variables para gráficas
        let chartModal1 = null;
        let chartModal2 = null;
        let chartTypeModal1 = 'doughnut';
        let chartTypeModal2 = 'doughnut';
        
        // Función para actualizar la hora en tiempo real
        function updateCurrentTime() {
            const now = new Date();
            const day = now.getDate().toString().padStart(2, '0');
            const month = (now.getMonth() + 1).toString().padStart(2, '0');
            const year = now.getFullYear();
            const hours = now.getHours().toString().padStart(2, '0');
            const minutes = now.getMinutes().toString().padStart(2, '0');
            const seconds = now.getSeconds().toString().padStart(2, '0');
            const timeString = `${day}/${month}/${year} ${hours}:${minutes}:${seconds}`;

            
            // Actualizar el texto en la tabla
            document.querySelectorAll('.table-header-actions span').forEach(element => {
                if (element.textContent.includes('Fecha y hora actual:')) {
                    element.textContent = `Fecha y hora actual: ${timeString}`;
                }
            });
        }
        
        // Actualizar cada segundo
        updateCurrentTime();
        setInterval(updateCurrentTime, 1000);
        
        // Función para mostrar notificaciones
        function showNotification(message, type = 'info') {
            const oldNotification = document.querySelector('.notification');
            if (oldNotification) {
                oldNotification.remove();
            }
            
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <div class="notification-content">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'}"></i>
                    <span>${message}</span>
                </div>
                <button class="notification-close">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            document.body.appendChild(notification);
            
            notification.querySelector('.notification-close').addEventListener('click', () => {
                notification.remove();
            });
            
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 5000);
        }
        
        // Función para exportar mis referenciados
        function exportarMisReferenciados(formato) {
            const soloActivos = document.getElementById('exportSoloActivos').checked;
            
            let url = 'exportar_mis_referenciados_excel.php';
            
            // Cambiar URL según formato
            if (formato === 'pdf') {
                url = 'exportar_mis_referenciados_pdf.php';
            }
            
            // Agregar parámetro si es necesario
            if (soloActivos) {
                url += '?solo_activos=1';
            }
            
            // Cerrar modal
            const exportModal = bootstrap.Modal.getInstance(document.getElementById('exportModal'));
            if (exportModal) {
                exportModal.hide();
            }
            
            // Mostrar mensaje de procesamiento
            showNotification('Generando archivo ' + formato.toUpperCase() + '...', 'info');
            
            // Descargar archivo después de un pequeño delay
            setTimeout(() => {
                // Crear un link temporal para la descarga
                const link = document.createElement('a');
                link.href = url;
                link.target = '_blank';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }, 300);
        }
        
        // Función para mostrar historial de llamadas (MODIFICADA)
        async function mostrarHistorialLlamadas(idReferenciado, nombre, telefono, email) {
            try {
                // Mostrar loading en el modal
                const modal = document.getElementById('modalHistorialLlamadas');
                const cuerpo = document.getElementById('cuerpoHistorialLlamadas');
                cuerpo.innerHTML = '<tr><td colspan="5" class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando historial...</td></tr>';
                
                // ¡¡¡IMPORTANTE!!! - ACTUALIZAR INFORMACIÓN PERSONAL CON LOS DATOS DE LA TABLA
                document.getElementById('nombreReferenciado').textContent = nombre;
                document.getElementById('telefonoReferenciado').textContent = telefono || 'No registrado';
                document.getElementById('emailReferenciado').textContent = email || 'No registrado';
                
                // Actualizar título del modal
                document.getElementById('modalHistorialLlamadasLabel').innerHTML = 
                    `<i class="fas fa-history me-2"></i>Historial de Llamadas - ${nombre}`;
                
                // Mostrar modal
                const bsModal = new bootstrap.Modal(modal);
                bsModal.show();
                
                // Obtener datos del historial
                const response = await fetch(`../ajax/obtener_historial_llamadas.php?id_referenciado=${idReferenciado}`);
                const data = await response.json();
                
                if (data.success) {
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
                            
                            // Obtener color del resultado
                            const colorResultado = getColorResultado(llamada.id_resultado);
                            
                            historialHTML += `
                                <tr>
                                    <td>${fechaStr}</td>
                                    <td><span class="badge ${colorResultado}">${llamada.resultado_nombre || 'Sin resultado'}</span></td>
                                    <td>${estrellasHTML}</td>
                                    <td>${llamada.observaciones || '<span class="text-muted">Sin observaciones</span>'}</td>
                                    <td>${llamada.usuario_nombre || 'Sistema'}</td>
                                </tr>
                            `;
                        });
                        
                        // Actualizar resumen
                        document.getElementById('totalLlamadas').textContent = data.historial.length;
                        
                        if (totalRating > 0) {
                            const totalConRating = data.historial.filter(l => l.rating).length;
                            const promedio = (totalRating / totalConRating).toFixed(1);
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
                        
                    } else {
                        historialHTML = '<tr><td colspan="5" class="text-center text-muted">No hay historial de llamadas registradas</td></tr>';
                        document.getElementById('totalLlamadas').textContent = '0';
                        document.getElementById('promedioRating').textContent = 'N/A';
                        document.getElementById('ultimaLlamada').textContent = 'Nunca';
                    }
                    
                    cuerpo.innerHTML = historialHTML;
                    
                } else {
                    showNotification('Error al cargar el historial: ' + (data.message || 'Error desconocido'), 'error');
                    cuerpo.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error al cargar el historial</td></tr>';
                }
                
            } catch (error) {
                console.error('Error:', error);
                showNotification('Error de conexión: ' + error.message, 'error');
                document.getElementById('cuerpoHistorialLlamadas').innerHTML = 
                    '<tr><td colspan="5" class="text-center text-danger">Error de conexión</td></tr>';
            }
        }
        
        // Función auxiliar para obtener color según resultado
        function getColorResultado(idResultado) {
            const colors = {
                1: 'bg-success',   // Exitoso
                2: 'bg-warning',   // Pendiente
                3: 'bg-danger',    // Fallido
                4: 'bg-info',      // Información
                5: 'bg-primary'    // Otro
            };
            return colors[idResultado] || 'bg-secondary';
        }
        
        // Manejar parámetros de éxito/error en la URL
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            
            if (urlParams.has('success')) {
                const successType = urlParams.get('success');
                let message = '';
                
                switch(successType) {
                    case 'referenciado_creado':
                        message = 'Referenciado creado correctamente';
                        break;
                    default:
                        message = 'Operación realizada correctamente';
                }
                
                if (message) {
                    showNotification(message, 'success');
                    window.history.replaceState({}, document.title, window.location.pathname);
                }
            }
            
            if (urlParams.has('error')) {
                const errorType = urlParams.get('error');
                let message = '';
                
                switch(errorType) {
                    default:
                        message = 'Ocurrió un error en la operación';
                }
                
                if (message) {
                    showNotification(message, 'error');
                    window.history.replaceState({}, document.title, window.location.pathname);
                }
            }
            
            // Inicializar eventos para los modales de gráficas
            const modalSistema = document.getElementById('modalSistema');
            if (modalSistema) {
                modalSistema.addEventListener('shown.bs.modal', function() {
                    setTimeout(() => {
                        inicializarGraficaModal('modalSistema');
                    }, 100);
                });
            }
            
            const modalGrafica = document.getElementById('modalGrafica');
            if (modalGrafica) {
                modalGrafica.addEventListener('shown.bs.modal', function() {
                    setTimeout(() => {
                        inicializarGraficaModal('modalGrafica');
                    }, 100);
                });
            }
        });
        
        // Función para inicializar gráfica del modal
        function inicializarGraficaModal(modalId) {
            let canvasId, currentChart, chartType, buttonId;
            
            if (modalId === 'modalSistema') {
                canvasId = 'graficaTortaModal1';
                currentChart = chartModal1;
                chartType = chartTypeModal1;
                buttonId = 'btnToggleGrafica1';
            } else {
                canvasId = 'graficaTortaModal2';
                currentChart = chartModal2;
                chartType = chartTypeModal2;
                buttonId = 'btnToggleGrafica2';
            }
            
            const ctx = document.getElementById(canvasId).getContext('2d');
            
            // Destruir gráfica anterior si existe
            if (currentChart) {
                currentChart.destroy();
            }
            
            // Configurar datos
            const data = {
                labels: ['Cámara', 'Senado'],
                datasets: [{
                    data: [graficaData.camara, graficaData.senado],
                    backgroundColor: [
                        'rgba(52, 152, 219, 0.9)',
                        'rgba(155, 89, 182, 0.9)'
                    ],
                    borderColor: [
                        'rgba(52, 152, 219, 1)',
                        'rgba(155, 89, 182, 1)'
                    ],
                    borderWidth: 3,
                    hoverBackgroundColor: [
                        'rgba(52, 152, 219, 1)',
                        'rgba(155, 89, 182, 1)'
                    ]
                }]
            };
            
            // Configurar opciones
            const options = {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value * 100) / total);
                                return `${label}: ${value} personas (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: chartType === 'doughnut' ? '55%' : '0%',
                animation: {
                    animateRotate: true,
                    animateScale: true,
                    duration: 1500,
                    easing: 'easeOutQuart'
                }
            };
            
            // Crear gráfica
            const newChart = new Chart(ctx, {
                type: chartType,
                data: data,
                options: options,
                plugins: [{
                    id: 'centerTextModal',
                    afterDraw: function(chart) {
                        const width = chart.width;
                        const height = chart.height;
                        const ctx = chart.ctx;
                        
                        ctx.restore();
                        ctx.save();
                    }
                }]
            });
            
            // Guardar referencia
            if (modalId === 'modalSistema') {
                chartModal1 = newChart;
            } else {
                chartModal2 = newChart;
            }
        }
        
        // Función para cambiar tipo de gráfica en el modal
        function toggleGraficaModal(modalId) {
            if (modalId === 'modalSistema') {
                chartTypeModal1 = chartTypeModal1 === 'doughnut' ? 'pie' : 'doughnut';
                const btn = document.getElementById('btnToggleGrafica1');
                if (btn) {
                    const text = chartTypeModal1 === 'doughnut' ? 'Cambiar a torta sólida' : 'Cambiar a torta hueca';
                    btn.innerHTML = `<i class="fas fa-exchange-alt me-1"></i> ${text}`;
                }
                inicializarGraficaModal('modalSistema');
            } else {
                chartTypeModal2 = chartTypeModal2 === 'doughnut' ? 'pie' : 'doughnut';
                const btn = document.getElementById('btnToggleGrafica2');
                if (btn) {
                    const text = chartTypeModal2 === 'doughnut' ? 'Cambiar a torta sólida' : 'Cambiar a torta hueca';
                    btn.innerHTML = `<i class="fas fa-exchange-alt me-1"></i> ${text}`;
                }
                inicializarGraficaModal('modalGrafica');
            }
        }
        
        // Función para descargar gráfica
        function descargarGrafica(modalId) {
            let canvasId;
            if (modalId === 'modalSistema') {
                canvasId = 'graficaTortaModal1';
            } else {
                canvasId = 'graficaTortaModal2';
            }
            
            const canvas = document.getElementById(canvasId);
            if (!canvas) {
                console.error('Canvas no encontrado');
                return;
            }
            
            const link = document.createElement('a');
            link.download = `grafica-distribucion-${new Date().toISOString().split('T')[0]}.png`;
            link.href = canvas.toDataURL('image/png');
            link.click();
            
            showNotification('Gráfica descargada correctamente', 'success');
        }
        
        // Función para mostrar modal del sistema
        function mostrarModalSistema() {
            const modal = new bootstrap.Modal(document.getElementById('modalSistema'));
            modal.show();
        }
    </script>

    <script src="js/modal-sistema.js"></script> 
    <script src="js/contador.js"></script>
</body>
</html>