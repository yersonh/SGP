<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../models/ReferenciadoModel.php';
require_once __DIR__ . '/../models/SistemaModel.php';

// Verificar si el usuario está logueado y es referenciador
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'Referenciador') {
    header('Location: index.php');
    exit();
}

$pdo = Database::getConnection();
$usuarioModel = new UsuarioModel($pdo);
$referenciadoModel = new ReferenciadoModel($pdo);
$sistemaModel = new SistemaModel($pdo);

$id_usuario_logueado = $_SESSION['id_usuario'];

// Obtener datos del usuario logueado
$usuario_logueado = $usuarioModel->getUsuarioById($id_usuario_logueado);

// Obtener referenciados del usuario
$referenciados = $referenciadoModel->getReferenciadosByUsuario($id_usuario_logueado);

// Contar referenciados activos e inactivos
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

// 6. Obtener información del sistema
$infoSistema = $sistemaModel->getInformacionSistema();

// 7. Formatear fecha para mostrar
$fecha_formateada = date('d/m/Y H:i:s', strtotime($fecha_actual));

// 8. Obtener información completa de la licencia (MODIFICADO)
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

// Calcular porcentaje del tope
$porcentaje_tope = $usuario_logueado['porcentaje_tope'] ?? 0;
$porcentaje_tope = min(100, $porcentaje_tope);
$restante_tope = max(0, ($usuario_logueado['tope'] ?? 0) - ($usuario_logueado['total_referenciados'] ?? 0));

// CALCULAR VOTOS POR CÁMARA Y SENADO
$total_camara = 0;
$total_senado = 0;
$total_activos_camara = 0;
$total_activos_senado = 0;
// Calcular porcentaje del tope (SOLO ACTIVOS)
$total_activos = $activos; // Ya tenemos esto calculado
$tope_asignado = $usuario_logueado['tope'] ?? 0;

// Calcular porcentaje basado solo en activos
$porcentaje_tope = ($tope_asignado > 0) ? min(100, ($total_activos * 100) / $tope_asignado) : 0;
$restante_tope = max(0, $tope_asignado - $total_activos);
foreach ($referenciados as $referenciado) {
    // Verificar si el referenciado está activo
    $esta_activo = ($referenciado['activo'] === true || $referenciado['activo'] === 't' || $referenciado['activo'] == 1);
    
    if (!$esta_activo) {
        continue; // Saltar referenciados inactivos
    }
    
    $id_grupo = $referenciado['id_grupo'] ?? null;
    
    if ($id_grupo == 1) {
        // Solo Cámara
        $total_camara++;
        $total_activos_camara++;
    } elseif ($id_grupo == 2) {
        // Solo Senado
        $total_senado++;
        $total_activos_senado++;
    } elseif ($id_grupo == 3) {
        // Ambos (Cámara y Senado)
        $total_camara++;
        $total_senado++;
        $total_activos_camara++;
        $total_activos_senado++;
    }
}

// Calcular porcentajes (usando solo activos)
$tope_asignado = $usuario_logueado['tope'] ?? 0;
$porcentaje_camara = ($tope_asignado > 0) ? min(100, ($total_camara * 100) / $tope_asignado) : 0;
$porcentaje_senado = ($tope_asignado > 0) ? min(100, ($total_senado * 100) / $tope_asignado) : 0;

// También necesitamos el total activos para la gráfica
$total_conteo = $total_camara + $total_senado;
$porc_camara = ($total_conteo > 0) ? round(($total_camara * 100) / $total_conteo, 1) : 0;
$porc_senado = ($total_conteo > 0) ? round(($total_senado * 100) / $total_conteo, 1) : 0;
$ambos_contados = min($total_camara, $total_senado);
$porc_ambos = ($total_conteo > 0) ? round(($ambos_contados * 100) / $total_conteo, 1) : 0;
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
                    <h4><i class="fas fa-chart-line me-2"></i>Progreso del Tope (Solo Activos)</h4>
                    <div class="tope-stats">
                        <span class="tope-stat">
                            <strong><?php echo $total_activos; ?></strong> / <?php echo $tope_asignado; ?> referenciados activos
                        </span>
                        <span class="tope-percentage">
                            <?php echo number_format($porcentaje_tope, 1) . '%'; ?>
                        </span>
                    </div>
                </div>
                
                <div class="tope-progress-bar">
                    <div class="tope-progress-fill" 
                         style="width: <?php echo $porcentaje_tope; ?>%; 
                                background: <?php 
                                    if ($porcentaje_tope >= 100) echo '#2ecc71';
                                    elseif ($porcentaje_tope >= 75) echo '#3498db';
                                    elseif ($porcentaje_tope >= 50) echo '#f39c12';
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
                                <?php echo $total_activos; ?> personas
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
                            </div>
                            <div class="voto-stats">
                                <span class="voto-count">
                                    <strong><?php echo $total_camara; ?></strong> / <?php echo $tope_asignado; ?>
                                </span>
                                <span class="voto-percentage">
                                    <?php echo number_format($porcentaje_camara, 1) . '%'; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="voto-progress-bar">
                            <div class="voto-progress-fill" 
                                 style="width: <?php echo $porcentaje_camara; ?>%; 
                                        background: <?php 
                                            if ($porcentaje_camara >= 100) echo '#2ecc71';
                                            elseif ($porcentaje_camara >= 75) echo '#3498db';
                                            elseif ($porcentaje_camara >= 50) echo '#f39c12';
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
                            </div>
                            <div class="voto-stats">
                                <span class="voto-count">
                                    <strong><?php echo $total_senado; ?></strong> / <?php echo $tope_asignado; ?>
                                </span>
                                <span class="voto-percentage">
                                    <?php echo number_format($porcentaje_senado, 1) . '%'; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="voto-progress-bar">
                            <div class="voto-progress-fill" 
                                 style="width: <?php echo $porcentaje_senado; ?>%; 
                                        background: <?php 
                                            if ($porcentaje_senado >= 100) echo '#2ecc71';
                                            elseif ($porcentaje_senado >= 75) echo '#9b59b6';
                                            elseif ($porcentaje_senado >= 50) echo '#f39c12';
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
                            <span class="grupo-value"><?php echo $total_camara - min($total_camara, $total_senado); ?> personas</span>
                        </div>
                        <div class="grupo-item">
                            <span class="grupo-label">Solo Senado:</span>
                            <span class="grupo-value"><?php echo $total_senado - min($total_camara, $total_senado); ?> personas</span>
                        </div>
                        <div class="grupo-item">
                            <span class="grupo-label">Ambos (PACHA):</span>
                            <span class="grupo-value">
                                <?php 
                                $ambos = min($total_camara, $total_senado);
                                echo $ambos . ' personas';
                                ?>
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
                                <th>Vota</th>
                                <th>Puesto/Mesa</th>
                                <th>Fecha Registro</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($referenciados as $referenciado): ?>
                            <?php 
                            $activo = $referenciado['activo'];
                            $esta_activo = ($activo === true || $activo === 't' || $activo == 1);
                            $vota_fuera = $referenciado['vota_fuera'] === 'Si';
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
                © Derechos de autor Reservados • <strong>Ing. Rubén Darío González García</strong> • Equipo de soporte • SISGONTech<br>
                Email: sisgonnet@gmail.com • Contacto: +57 3106310227 • Puerto Gaitán, Colombia • <?php echo date('Y'); ?>
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
                    <div class="grafica-modal-content">
                        <!-- Gráfica -->
                        <div class="grafica-canvas-modal">
                            <canvas id="graficaTortaModal1" width="500" height="500"></canvas>
                            <div class="grafica-note">
                                <i class="fas fa-info-circle me-1"></i>
                                <small>Gráfica basada solo en referenciados activos</small>
                            </div>
                        </div>
                        
                        <!-- Información y controles -->
                        <div class="grafica-info-modal">
                            <div class="grafica-header-modal">
                                <h4><i class="fas fa-chart-bar me-2"></i>Análisis de Distribución (Solo Activos)</h4>
                                <div class="grafica-controls">
                                    <button class="btn-grafica-control" onclick="toggleGraficaModal('modalSistema')" id="btnToggleGrafica1">
                                        <i class="fas fa-exchange-alt me-1"></i> Cambiar a torta sólida
                                    </button>
                                    <button class="btn-grafica-control" onclick="descargarGrafica('modalSistema')">
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
                                        <div class="estadistica-label">Referidos a Cámara (Activos)</div>
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
                                        <div class="estadistica-label">Referidos a Senado (Activos)</div>
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
                                        <div class="estadistica-label">Votan por ambos (Activos)</div>
                                        <div class="estadistica-value">
                                            <?php echo $ambos_contados; ?>
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
                                        <div class="estadistica-label">Total Referenciados Activos</div>
                                        <div class="estadistica-value"><?php echo $activos; ?></div>
                                        <div class="estadistica-porcentaje">100%</div>
                                    </div>
                                </div>
                            </div>
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
                    
                    <!-- Footer de información del sistema -->
                    <div class="system-footer-modal">
                        <div class="text-center">
                            <p class="text-muted mb-1">
                                © Derechos de autor Reservados • 
                                <strong><?php echo htmlspecialchars($infoSistema['desarrollador'] ?? 'SISGONTech - Ing. Rubén Darío González García'); ?></strong>
                            </p>
                            <p class="text-muted mb-1">
                                <strong>SISGONTech</strong> • Colombia • <?php echo date('Y'); ?>
                            </p>
                            <p class="text-muted mb-0">
                                Email: <?php echo htmlspecialchars($infoSistema['contacto_email'] ?? 'sisgonnet@gmail.com'); ?> • 
                                Contacto: <?php echo htmlspecialchars($infoSistema['contacto_telefono'] ?? '+57 3106310227'); ?>
                            </p>
                            <p class="small text-muted mt-2">
                                Versión <?php echo htmlspecialchars($infoSistema['version_sistema'] ?? '1.0.1'); ?> • 
                                Licencia <?php echo htmlspecialchars($infoSistema['tipo_licencia'] ?? 'Runtime'); ?>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
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
                                            <?php 
                                            $total_conteo = $total_camara + $total_senado;
                                            $porc_camara = ($total_conteo > 0) ? round(($total_camara * 100) / $total_conteo, 1) : 0;
                                            echo $porc_camara . '%';
                                            ?>
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
                                            <?php 
                                            $porc_senado = ($total_conteo > 0) ? round(($total_senado * 100) / $total_conteo, 1) : 0;
                                            echo $porc_senado . '%';
                                            ?>
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
                                            <?php echo min($total_camara, $total_senado); ?>
                                        </div>
                                        <div class="estadistica-porcentaje">
                                            <?php 
                                            $ambos_contados = min($total_camara, $total_senado);
                                            $porc_ambos = ($total_conteo > 0) ? round(($ambos_contados * 100) / $total_conteo, 1) : 0;
                                            echo $porc_ambos . '%';
                                            ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="estadistica-card total">
                                    <div class="estadistica-icon">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div class="estadistica-content">
                                        <div class="estadistica-label">Total Referenciados</div>
                                        <div class="estadistica-value"><?php echo $total_referenciados; ?></div>
                                        <div class="estadistica-porcentaje">100%</div>
                                    </div>
                                </div>
                            </div>
                            
                                                        <!-- Detalles -->
                            <div class="detalles-distribucion">
                                <h5><i class="fas fa-info-circle me-2"></i>Detalles de la Distribución (Solo Activos)</h5>
                                <div class="detalle-item">
                                    <span class="detalle-label">Solo votan por Cámara:</span>
                                    <span class="detalle-value"><?php echo $total_camara - min($total_camara, $total_senado); ?> personas</span>
                                </div>
                                <div class="detalle-item">
                                    <span class="detalle-label">Solo votan por Senado:</span>
                                    <span class="detalle-value"><?php echo $total_senado - min($total_camara, $total_senado); ?> personas</span>
                                </div>
                                <div class="detalle-item">
                                    <span class="detalle-label">Votan por ambos:</span>
                                    <span class="detalle-value"><?php echo min($total_camara, $total_senado); ?> personas</span>
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
</body>
</html>