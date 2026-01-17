<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../models/ReferenciadoModel.php';
require_once __DIR__ . '/../../models/LlamadaModel.php';

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../index.php');
    exit();
}

// Verificar que se haya proporcionado el ID del referenciador
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ../dashboard.php'); // Corregido: redirige a dashboard.php en la raíz
    exit();
}

$id_referenciador = intval($_GET['id']);
$nickname = isset($_GET['nickname']) ? htmlspecialchars($_GET['nickname']) : 'Usuario';

$pdo = Database::getConnection();
$usuarioModel = new UsuarioModel($pdo);
$referenciadoModel = new ReferenciadoModel($pdo);

// Obtener datos del referenciador
$referenciador = $usuarioModel->getUsuarioById($id_referenciador);
if (!$referenciador) {
    header('Location: ../dashboard.php?error=usuario_no_encontrado'); // Corregido
    exit();
}

// Verificar que el usuario sea un referenciador
if ($referenciador['tipo_usuario'] !== 'Referenciador') {
    header('Location: ../dashboard.php?error=usuario_no_es_referenciador'); // Corregido
    exit();
}

// Obtener los referenciados de este usuario
$referenciados = $referenciadoModel->getReferenciadosByUsuario($id_referenciador);

// Obtener estadísticas
$total_referenciados = count($referenciados);
$tope_usuario = $referenciador['tope'];
$porcentaje_completado = ($tope_usuario > 0) ? round(($total_referenciados * 100) / $tope_usuario, 2) : 0;

// Estadísticas de afinidad
$afinidad_stats = [];
foreach ($referenciados as $ref) {
    $nivel = $ref['afinidad'];
    if (!isset($afinidad_stats[$nivel])) {
        $afinidad_stats[$nivel] = 0;
    }
    $afinidad_stats[$nivel]++;
}

// Estadísticas de votación
$vota_fuera_count = 0;
foreach ($referenciados as $ref) {
    if ($ref['vota_fuera'] === 'Si') {
        $vota_fuera_count++;
    }
}
$vota_aqui_count = $total_referenciados - $vota_fuera_count;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personas Referenciadas - SGP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/ver_refereciados_admin.css">
    <style>
        /* Estilos adicionales para el sistema de rating */
        .rating-stars {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 20px 0;
        }
        
        .rating-star {
            font-size: 2.5rem;
            color: #ddd;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .rating-star:hover,
        .rating-star.hovered {
            color: #ffc107;
            transform: scale(1.2);
        }
        
        .rating-star.selected {
            color: #ffc107;
        }
        
        .rating-label {
            text-align: center;
            margin-top: 10px;
            font-weight: 500;
            color: #666;
            min-height: 24px;
        }
        
        .rating-value {
            font-weight: bold;
            color: #333;
        }
        
        .observaciones-textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .llamada-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .llamada-info-item {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .llamada-info-item i {
            width: 20px;
            margin-right: 10px;
            color: #6c757d;
        }
        
        /* Estilos para el modal de rating */
        .modal-rating .modal-header {
            background: linear-gradient(135deg, #4e54c8, #8f94fb);
            color: white;
        }
        
        .modal-rating .modal-footer {
            justify-content: space-between;
        }
        
        .skip-rating {
            background: #6c757d;
            color: white;
            border: none;
        }
        
        .skip-rating:hover {
            background: #5a6268;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="header-container">
            <div class="header-title">
                <h1>
                    <i class="fas fa-users"></i> Personas Referenciadas
                    <span class="user-count"><?php echo $total_referenciados; ?> personas</span>
                </h1>
            </div>
            <a href="../dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Volver a Usuarios
            </a>
        </div>
    </header>

    <div class="main-container">
        <!-- Información del Referenciador -->
        <div class="info-card">
            <div class="info-header">
                <h2><i class="fas fa-user-tie"></i> Referenciador: <?php echo htmlspecialchars($referenciador['nickname']); ?></h2>
                <div>
                    <?php if ($referenciador['tipo_usuario']): ?>
                        <span class="status-badge status-active">
                            <i class="fas fa-user-tag"></i> <?php echo htmlspecialchars($referenciador['tipo_usuario']); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="user-details">
                <div class="detail-item">
                    <span class="detail-label">Nombre Completo:</span>
                    <span class="detail-value">
                        <?php 
                        if (!empty($referenciador['nombres']) && !empty($referenciador['apellidos'])) {
                            echo htmlspecialchars($referenciador['nombres'] . ' ' . $referenciador['apellidos']);
                        } else {
                            echo 'No asignado';
                        }
                        ?>
                    </span>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">Cédula:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($referenciador['cedula'] ?? 'No registrada'); ?></span>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">Tope asignado:</span>
                    <span class="detail-value"><?php echo number_format($tope_usuario); ?> personas</span>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">Total referenciados:</span>
                    <span class="detail-value"><?php echo number_format($total_referenciados); ?> personas</span>
                </div>
            </div>
            
            <!-- Barra de progreso -->
            <div class="progress-container">
                <div class="progress-label">
                    <span>Progreso de referenciación</span>
                    <span><strong><?php echo $porcentaje_completado; ?>%</strong> (<?php echo $total_referenciados; ?>/<?php echo $tope_usuario; ?>)</span>
                </div>
                <div class="progress">
                    <div class="progress-bar" style="width: <?php echo min($porcentaje_completado, 100); ?>%;"></div>
                </div>
            </div>
        </div>

        <!-- Estadísticas rápidas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="color: #3498db;">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number"><?php echo number_format($total_referenciados); ?></div>
                <div class="stat-label">Total Referenciados</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="color: #27ae60;">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <div class="stat-number"><?php echo number_format($vota_aqui_count); ?></div>
                <div class="stat-label">Votan aquí</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="color: #e74c3c;">
                    <i class="fas fa-map-marked-alt"></i>
                </div>
                <div class="stat-number"><?php echo number_format($vota_fuera_count); ?></div>
                <div class="stat-label">Votan fuera</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="color: #f39c12;">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-number"><?php echo $porcentaje_completado; ?>%</div>
                <div class="stat-label">Avance del tope</div>
            </div>
        </div>

        <!-- Gráfico de afinidad -->
        <?php if (!empty($afinidad_stats)): ?>
        <div class="afinidad-chart">
            <h3 class="chart-header"><i class="fas fa-chart-bar"></i> Distribución por Nivel de Afinidad</h3>
            
            <?php 
            // Ordenar por nivel de afinidad
            ksort($afinidad_stats);
            $max_count = max($afinidad_stats);
            
            foreach ($afinidad_stats as $nivel => $count):
                $porcentaje = $total_referenciados > 0 ? round(($count * 100) / $total_referenciados, 1) : 0;
                $bar_width = $max_count > 0 ? ($count * 100) / $max_count : 0;
            ?>
            <div class="afinidad-item">
                <div class="afinidad-level">
                    <span class="afinidad-badge afinidad-<?php echo $nivel; ?>">
                        Nivel <?php echo $nivel; ?>
                    </span>
                </div>
                <div class="afinidad-bar-container">
                    <div class="afinidad-bar" style="width: <?php echo $bar_width; ?>%;"></div>
                </div>
                <div class="afinidad-count">
                    <?php echo $count; ?> (<?php echo $porcentaje; ?>%)
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Tabla de referenciados -->
        <div class="table-container">
            <div class="table-header">
                <h3><i class="fas fa-list-alt"></i> Listado de Personas Referenciadas</h3>
                
                <div class="search-container">
                    <input type="text" class="search-input" id="search-input" placeholder="Buscar por nombre, cédula, email...">
                    <button class="btn btn-primary" onclick="exportToExcel()">
                        <i class="fas fa-file-excel"></i> Exportar
                    </button>
                </div>
            </div>
            
            <?php if ($total_referenciados > 0): ?>
            <div class="table-responsive">
                <table class="referenciados-table" id="referenciados-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>NOMBRE COMPLETO</th>
                            <th>CÉDULA</th>
                            <th>TELÉFONO</th>
                            <th>EMAIL</th>
                            <th>AFINIDAD</th>
                            <th>VOTA</th>
                            <th>PUESTO/MESA</th>
                            <th>FECHA REGISTRO</th>
                            <th>ESTADO</th>
                            <th>TRACKING</th> <!-- NUEVA COLUMNA -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php $counter = 1; ?>
                        <?php foreach ($referenciados as $referenciado): ?>
                        <?php 
                        $esta_activo = ($referenciado['activo'] === true || $referenciado['activo'] === 't' || $referenciado['activo'] == 1);
                        $nombre_completo = htmlspecialchars($referenciado['nombre'] . ' ' . $referenciado['apellido']);
                        
                        // Determinar información de votación
                        $vota_info = '';
                        if ($referenciado['vota_fuera'] === 'Si') {
                            $vota_info = 'Fuera';
                            $puesto_mesa = htmlspecialchars($referenciado['puesto_votacion_fuera'] ?? '') . 
                                          ' - Mesa ' . ($referenciado['mesa_fuera'] ?? '');
                        } else {
                            $vota_info = 'Aquí';
                            $puesto_mesa = htmlspecialchars($referenciado['puesto_votacion_nombre'] ?? '') . 
                                          ' - Mesa ' . ($referenciado['mesa'] ?? '');
                        }
                        ?>
                        <tr>
                            <td><?php echo $counter++; ?></td>
                            
                            <td>
                                <strong><?php echo $nombre_completo; ?></strong>
                                <?php if (!empty($referenciado['direccion'])): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($referenciado['direccion']); ?></small>
                                <?php endif; ?>
                            </td>
                            
                            <td>
                                <code><?php echo htmlspecialchars($referenciado['cedula']); ?></code>
                            </td>
                            
                            <td>
                                <?php echo !empty($referenciado['telefono']) ? htmlspecialchars($referenciado['telefono']) : '<span class="text-muted">No registrado</span>'; ?>
                            </td>
                            
                            <td>
                                <?php if (!empty($referenciado['email'])): ?>
                                <a href="mailto:<?php echo htmlspecialchars($referenciado['email']); ?>">
                                    <?php echo htmlspecialchars($referenciado['email']); ?>
                                </a>
                                <?php else: ?>
                                <span class="text-muted">No registrado</span>
                                <?php endif; ?>
                            </td>
                            
                            <td>
                                <span class="afinidad-badge afinidad-<?php echo $referenciado['afinidad']; ?>">
                                    Nivel <?php echo $referenciado['afinidad']; ?>
                                </span>
                            </td>
                            
                            <td>
                                <?php if ($referenciado['vota_fuera'] === 'Si'): ?>
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-external-link-alt"></i> <?php echo $vota_info; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-home"></i> <?php echo $vota_info; ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            
                            <td>
                                <?php echo $puesto_mesa; ?>
                                <?php if (!empty($referenciado['zona_nombre'])): ?>
                                <br><small>Zona: <?php echo htmlspecialchars($referenciado['zona_nombre']); ?></small>
                                <?php endif; ?>
                            </td>
                            
                            <td>
                                <?php 
                                $fecha = new DateTime($referenciado['fecha_registro']);
                                echo $fecha->format('d/m/Y H:i');
                                ?>
                            </td>
                            
                            <td>
                                <?php if ($esta_activo): ?>
                                    <span class="status-badge status-active">
                                        <i class="fas fa-check-circle"></i> Activo
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge status-inactive">
                                        <i class="fas fa-times-circle"></i> Inactivo
                                    </span>
                                <?php endif; ?>
                            </td>
                            
                            <!-- COLUMNA TRACKING CON BOTÓN DE TELÉFONO ROJO -->
                            <td>
                                <button class="tracking-btn" 
                                        title="Llamar a <?php echo $nombre_completo; ?>"
                                        onclick="mostrarModalLlamada('<?php echo htmlspecialchars($referenciado['telefono'] ?? ''); ?>', '<?php echo addslashes($nombre_completo); ?>', '<?php echo $referenciado['id_referenciado']; ?>', this)"
                                        <?php echo empty($referenciado['telefono']) ? 'disabled' : ''; ?>>
                                    <i class="fas fa-phone-alt"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="no-data">
                <i class="fas fa-users-slash"></i>
                <h4 class="mt-3 mb-2">No hay personas referenciadas</h4>
                <p class="text-muted">Este usuario no ha referenciado a ninguna persona todavía.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal de Confirmación de Llamada -->
    <div class="modal fade" id="modalLlamada" tabindex="-1" aria-labelledby="modalLlamadaLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="modalLlamadaLabel">
                        <i class="fas fa-phone-alt me-2"></i>Confirmar Llamada
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <i class="fas fa-phone-volume fa-4x text-warning mb-3"></i>
                        <h4 id="nombrePersona" class="mb-3"></h4>
                        <div class="telefono-info">
                            <div class="d-flex align-items-center justify-content-center mb-2">
                                <i class="fas fa-phone me-2 text-success"></i>
                                <h3 id="numeroTelefono" class="mb-0"></h3>
                            </div>
                            <small class="text-muted">Número de contacto</small>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <span id="mensajeDispositivo"></span>
                    </div>
                    
                    <div class="text-muted small mt-3">
                        <p class="mb-1"><i class="fas fa-clock me-1"></i> Hora actual: <span id="horaActual"></span></p>
                        <p class="mb-0"><i class="fas fa-calendar me-1"></i> Fecha: <span id="fechaActual"></span></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-success" id="btnConfirmarLlamada">
                        <i class="fas fa-phone me-2"></i>Realizar Llamada
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Valoración de Llamada -->
    <div class="modal fade modal-rating" id="modalRating" tabindex="-1" aria-labelledby="modalRatingLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalRatingLabel">
                        <i class="fas fa-star me-2"></i>Valorar Llamada
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Información de la llamada -->
                    <div class="llamada-info">
                        <div class="llamada-info-item">
                            <i class="fas fa-user"></i>
                            <span id="ratingNombrePersona"></span>
                        </div>
                        <div class="llamada-info-item">
                            <i class="fas fa-phone"></i>
                            <span id="ratingTelefono"></span>
                        </div>
                        <div class="llamada-info-item">
                            <i class="fas fa-clock"></i>
                            <span id="ratingFechaHora"></span>
                        </div>
                    </div>
                    
                    <!-- Sistema de rating por estrellas -->
                    <div class="rating-container">
                        <h6 class="text-center mb-3">¿Cómo calificaría esta llamada?</h6>
                        
                        <div class="rating-stars">
                            <i class="fas fa-star rating-star" data-value="1"></i>
                            <i class="fas fa-star rating-star" data-value="2"></i>
                            <i class="fas fa-star rating-star" data-value="3"></i>
                            <i class="fas fa-star rating-star" data-value="4"></i>
                            <i class="fas fa-star rating-star" data-value="5"></i>
                        </div>
                        
                        <div class="rating-label">
                            <span id="ratingText">Seleccione una calificación</span>
                            <span class="rating-value" id="ratingValue"></span>
                        </div>
                        
                        <!-- Etiquetas descriptivas para cada valor -->
                        <div class="text-center small text-muted mt-2">
                            <div class="row">
                                <div class="col">1 = Mala</div>
                                <div class="col">2 = Regular</div>
                                <div class="col">3 = Buena</div>
                                <div class="col">4 = Muy Buena</div>
                                <div class="col">5 = Excelente</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Campo para observaciones -->
                    <div class="mt-4">
                        <label for="observaciones" class="form-label">
                            <i class="fas fa-edit me-1"></i>Observaciones acerca de la llamada
                        </label>
                        <textarea class="form-control observaciones-textarea" 
                                  id="observaciones" 
                                  placeholder="Escriba aquí sus comentarios sobre la llamada (opcional)..."></textarea>
                        <div class="form-text">Puede incluir detalles sobre el estado de ánimo, compromiso, dudas, etc.</div>
                    </div>
                    <!-- Campo para resultado de llamada -->
                <div class="mt-3">
                    <label for="resultadoLlamada" class="form-label">
                        <i class="fas fa-clipboard-check me-1"></i>Resultado de la llamada
                    </label>
                    <select class="form-select" id="resultadoLlamada">
                        <option value="1">Contactado</option>
                        <option value="2">No contesta</option>
                        <option value="3">Número equivocado</option>
                        <option value="4">Teléfono apagado</option>
                        <option value="5">Ocupado</option>
                        <option value="6">Dejó mensaje</option>
                        <option value="7">Rechazó llamada</option>
                    </select>
                    <div class="form-text">Seleccione el resultado de la llamada realizada.</div>
                </div>
                    <!-- Campos ocultos para enviar datos -->
                    <input type="hidden" id="ratingIdReferenciado" value="">
                    <input type="hidden" id="ratingValor" value="0">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary skip-rating" onclick="saltarValoracion()">
                        <i class="fas fa-forward me-1"></i>Saltar Valoración
                    </button>
                    <button type="button" class="btn btn-primary" id="btnGuardarValoracion" disabled onclick="guardarValoracion()">
                        <i class="fas fa-save me-1"></i>Guardar Valoración
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Variables globales para almacenar datos de la llamada
        let llamadaActual = {
            telefono: '',
            telefonoLimpio: '',
            nombre: '',
            idReferenciado: '',
            boton: null,
            horaInicio: null
        };

        // Función para mostrar el modal de confirmación
        function mostrarModalLlamada(telefono, nombre, idReferenciado, boton) {
            if (!telefono) {
                showNotification('Este referenciado no tiene número de teléfono registrado', 'error');
                return;
            }
            
            // Guardar datos en variable global
            llamadaActual.telefono = telefono;
            llamadaActual.telefonoLimpio = telefono.replace(/\D/g, '');
            llamadaActual.nombre = nombre;
            llamadaActual.idReferenciado = idReferenciado;
            llamadaActual.boton = boton;
            llamadaActual.horaInicio = new Date();
            
            // Actualizar contenido del modal
            document.getElementById('nombrePersona').textContent = `¿Desea llamar a ${nombre}?`;
            document.getElementById('numeroTelefono').textContent = telefono;
            
            // Determinar mensaje según dispositivo
            const esMovil = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
            const mensajeDispositivo = esMovil 
                ? 'Se abrirá la aplicación de teléfono de su dispositivo.'
                : 'Para llamar desde su computadora, marque manualmente este número.';
            
            document.getElementById('mensajeDispositivo').textContent = mensajeDispositivo;
            
            // Actualizar hora y fecha
            const ahora = new Date();
            const opcionesHora = { hour: '2-digit', minute: '2-digit', second: '2-digit' };
            const opcionesFecha = { day: '2-digit', month: '2-digit', year: 'numeric' };
            
            document.getElementById('horaActual').textContent = ahora.toLocaleTimeString('es-ES', opcionesHora);
            document.getElementById('fechaActual').textContent = ahora.toLocaleDateString('es-ES', opcionesFecha);
            
            // Mostrar modal
            const modal = new bootstrap.Modal(document.getElementById('modalLlamada'));
            modal.show();
        }

        // Función para realizar la llamada (llamada desde el botón del modal)
        function realizarLlamada() {
            const { telefono, telefonoLimpio, nombre, boton } = llamadaActual;
            
            // Guardar estado original del botón
            const textoOriginal = boton.innerHTML;
            boton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            boton.disabled = true;
            
            try {
                // Guardar hora de inicio de llamada
                llamadaActual.horaInicio = new Date();
                
                // Intentar abrir el enlace tel: para dispositivos móviles
                window.location.href = `tel:${telefonoLimpio}`;
                
                // Para computadoras, mostrar mensaje
                if (!/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
                    showNotification(`Para llamar a ${nombre}, marque manualmente: ${telefono}`, 'info');
                }
                
                // Cerrar modal de confirmación
                const modalConfirmacion = bootstrap.Modal.getInstance(document.getElementById('modalLlamada'));
                modalConfirmacion.hide();
                
                // Mostrar notificación de éxito
                showNotification(`Iniciando llamada a ${nombre}`, 'success');
                
                // Configurar timer para mostrar modal de valoración después de 3 segundos
                // (Simulando que la llamada terminó y volvió a la aplicación)
                setTimeout(() => {
                    mostrarModalValoracion();
                }, 3000);
                
                // Restaurar botón después de 5 segundos
                setTimeout(() => {
                    boton.innerHTML = textoOriginal;
                    boton.disabled = false;
                }, 5000);
                
            } catch (error) {
                showNotification('Error al intentar iniciar la llamada: ' + error.message, 'error');
                boton.innerHTML = textoOriginal;
                boton.disabled = false;
            }
        }

        // Función para mostrar el modal de valoración
        function mostrarModalValoracion() {
            const { nombre, telefono, idReferenciado, horaInicio } = llamadaActual;
            
            // Actualizar información en el modal
            document.getElementById('ratingNombrePersona').textContent = nombre;
            document.getElementById('ratingTelefono').textContent = telefono;
            document.getElementById('ratingIdReferenciado').value = idReferenciado;
            
            // Formatear fecha y hora
            const horaFin = new Date();
            const opcionesFechaHora = {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };
            const fechaHoraStr = horaFin.toLocaleDateString('es-ES', opcionesFechaHora);
            
            document.getElementById('ratingFechaHora').textContent = `Llamada realizada: ${fechaHoraStr}`;
            
            // Mostrar modal
            const modalRating = new bootstrap.Modal(document.getElementById('modalRating'));
            modalRating.show();
        }

        // Función para configurar el sistema de rating por estrellas
        function configurarRatingStars() {
            const stars = document.querySelectorAll('.rating-star');
            const ratingText = document.getElementById('ratingText');
            const ratingValue = document.getElementById('ratingValue');
            const btnGuardar = document.getElementById('btnGuardarValoracion');
            
            const ratingLabels = {
                0: "Seleccione una calificación",
                1: "Mala",
                2: "Regular",
                3: "Buena",
                4: "Muy Buena",
                5: "Excelente"
            };
            
            stars.forEach(star => {
                // Evento al pasar el mouse
                star.addEventListener('mouseenter', function() {
                    const value = parseInt(this.getAttribute('data-value'));
                    
                    // Reset all stars
                    stars.forEach(s => s.classList.remove('selected', 'hovered'));
                    
                    // Highlight stars up to the hovered one
                    for (let i = 0; i < value; i++) {
                        stars[i].classList.add('hovered');
                    }
                    
                    // Update text
                    ratingText.textContent = ratingLabels[value];
                    ratingValue.textContent = ` (${value}/5)`;
                });
                
                // Evento al quitar el mouse
                star.addEventListener('mouseleave', function() {
                    const currentValue = parseInt(document.getElementById('ratingValor').value);
                    
                    // Reset hover effect
                    stars.forEach(s => s.classList.remove('hovered'));
                    
                    // Restore selected stars if any
                    if (currentValue > 0) {
                        for (let i = 0; i < currentValue; i++) {
                            stars[i].classList.add('selected');
                        }
                        ratingText.textContent = ratingLabels[currentValue];
                        ratingValue.textContent = ` (${currentValue}/5)`;
                    } else {
                        ratingText.textContent = ratingLabels[0];
                        ratingValue.textContent = "";
                    }
                });
                
                // Evento al hacer clic
                star.addEventListener('click', function() {
                    const value = parseInt(this.getAttribute('data-value'));
                    
                    // Save rating value
                    document.getElementById('ratingValor').value = value;
                    
                    // Update stars display
                    stars.forEach(s => {
                        s.classList.remove('selected');
                        const starValue = parseInt(s.getAttribute('data-value'));
                        if (starValue <= value) {
                            s.classList.add('selected');
                        }
                    });
                    
                    // Update text
                    ratingText.textContent = ratingLabels[value];
                    ratingValue.textContent = ` (${value}/5)`;
                    
                    // Enable save button
                    btnGuardar.disabled = false;
                });
            });
        }

        // Función para saltar la valoración
        function saltarValoracion() {
            const modalRating = bootstrap.Modal.getInstance(document.getElementById('modalRating'));
            modalRating.hide();
            
            showNotification('Valoración omitida. Puede valorar en cualquier momento.', 'info');
            
            // Reset rating system
            resetRatingSystem();
        }

        // Función para guardar la valoración
async function guardarValoracion() {
    const rating = parseInt(document.getElementById('ratingValor').value);
    const observaciones = document.getElementById('observaciones').value.trim();
    const idReferenciado = document.getElementById('ratingIdReferenciado').value;
    const resultadoSelect = document.getElementById('resultadoLlamada');
    const idResultado = parseInt(resultadoSelect.value);
    const { nombre, telefono } = llamadaActual;
    
    if (rating === 0) {
        showNotification('Por favor, seleccione una calificación', 'error');
        return;
    }
    
    // Crear objeto con datos de la valoración
    const datosValoracion = {
        id_referenciado: idReferenciado,
        telefono: telefono,
        rating: rating,
        observaciones: observaciones,
        fecha_llamada: new Date().toISOString(),
        id_resultado: idResultado
    };
    
    try {
        // Mostrar loading
        const btnGuardar = document.getElementById('btnGuardarValoracion');
        const originalText = btnGuardar.innerHTML;
        btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
        btnGuardar.disabled = true;
        
        // Enviar datos al servidor
        const response = await fetch('../ajax/guardar_valoracion_llamada.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(datosValoracion)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Valoración guardada exitosamente', 'success');
            
            // Cerrar modal
            const modalRating = bootstrap.Modal.getInstance(document.getElementById('modalRating'));
            modalRating.hide();
            
            // Reset rating system
            resetRatingSystem();
            
            // Opcional: Actualizar la interfaz
            actualizarInterfazDespuesDeValoracion(idReferenciado, rating);
            
        } else {
            showNotification('Error: ' + result.message, 'error');
            btnGuardar.innerHTML = originalText;
            btnGuardar.disabled = false;
        }
        
    } catch (error) {
        showNotification('Error de conexión: ' + error.message, 'error');
        const btnGuardar = document.getElementById('btnGuardarValoracion');
        btnGuardar.innerHTML = '<i class="fas fa-save me-1"></i>Guardar Valoración';
        btnGuardar.disabled = false;
    }
}

// Función opcional para actualizar la interfaz
function actualizarInterfazDespuesDeValoracion(idReferenciado, rating) {
    // Puedes agregar un badge o cambiar el color del botón de tracking
    const button = llamadaActual.boton;
    if (button) {
        // Agregar tooltip con la valoración
        button.setAttribute('data-bs-title', `Llamada valorada: ${rating} estrellas`);
        
        // Actualizar tooltip si ya existe
        if (button._tooltip) {
            button._tooltip.dispose();
            new bootstrap.Tooltip(button);
        }
        
        // Opcional: Cambiar color o agregar badge
        button.classList.add('valorada');
        button.innerHTML = '<i class="fas fa-phone-alt"></i> ✓';
    }
}

        // Función para resetear el sistema de rating
        function resetRatingSystem() {
            // Reset stars
            const stars = document.querySelectorAll('.rating-star');
            stars.forEach(star => {
                star.classList.remove('selected', 'hovered');
            });
            
            // Reset fields
            document.getElementById('ratingValor').value = "0";
            document.getElementById('ratingText').textContent = "Seleccione una calificación";
            document.getElementById('ratingValue').textContent = "";
            document.getElementById('observaciones').value = "";
            document.getElementById('btnGuardarValoracion').disabled = true;
        }

        // Función para enviar valoración al servidor (opcional)
        async function enviarValoracionAlServidor(datos) {
            try {
                const response = await fetch('../ajax/guardar_valoracion.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(datos)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('Valoración guardada en la base de datos', 'success');
                } else {
                    showNotification('Error al guardar la valoración: ' + result.message, 'error');
                }
            } catch (error) {
                showNotification('Error de conexión: ' + error.message, 'error');
            }
        }

        // Configurar eventos al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            // Configurar botón de confirmar llamada
            document.getElementById('btnConfirmarLlamada').addEventListener('click', realizarLlamada);
            
            // Configurar sistema de rating
            configurarRatingStars();
            
            // Configurar eventos para el modal de rating
            const modalRating = document.getElementById('modalRating');
            modalRating.addEventListener('hidden.bs.modal', resetRatingSystem);
            
            // Animar barras de progreso
            const progressBar = document.querySelector('.progress-bar');
            if (progressBar) {
                const width = progressBar.style.width;
                progressBar.style.width = '0';
                setTimeout(() => {
                    progressBar.style.width = width;
                }, 300);
            }
            
            // Animar barras de afinidad
            document.querySelectorAll('.afinidad-bar').forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0';
                setTimeout(() => {
                    bar.style.width = width;
                }, 500);
            });
            
            // Agregar tooltips a los botones de tracking
            document.querySelectorAll('.tracking-btn').forEach(button => {
                if (!button.disabled) {
                    button.setAttribute('data-bs-toggle', 'tooltip');
                    button.setAttribute('data-bs-placement', 'top');
                    button.setAttribute('data-bs-title', 'Haga clic para llamar');
                }
            });
            
            // Inicializar tooltips de Bootstrap
            if (typeof bootstrap !== 'undefined') {
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }
        });

        // Función para buscar en la tabla
        document.getElementById('search-input').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#referenciados-table tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
        
        // Función para exportar a Excel
        function exportToExcel() {
            const table = document.getElementById('referenciados-table');
            let csv = [];
            
            // Obtener encabezados
            const headers = [];
            table.querySelectorAll('thead th').forEach(th => {
                headers.push(th.textContent.trim());
            });
            csv.push(headers.join(','));
            
            // Obtener datos
            table.querySelectorAll('tbody tr').forEach(row => {
                if (row.style.display !== 'none') {
                    const rowData = [];
                    row.querySelectorAll('td').forEach(td => {
                        // Limpiar el texto (quitar HTML, espacios extra)
                        let text = td.textContent.trim();
                        text = text.replace(/\s+/g, ' ');
                        // Escapar comas
                        if (text.includes(',')) {
                            text = `"${text}"`;
                        }
                        rowData.push(text);
                    });
                    csv.push(rowData.join(','));
                }
            });
            
            // Crear archivo
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            
            link.setAttribute('href', url);
            link.setAttribute('download', `referenciados_<?php echo $referenciador['nickname']; ?>_${new Date().toISOString().split('T')[0]}.csv`);
            link.style.visibility = 'hidden';
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            showNotification('Exportación completada exitosamente', 'success');
        }
        
        // Función para mostrar notificaciones
        function showNotification(message, type = 'info') {
            const oldNotification = document.querySelector('.notification');
            if (oldNotification) oldNotification.remove();
            
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <div class="notification-content">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
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
                if (notification.parentNode) notification.remove();
            }, 5000);
        }
    </script>
</body>
</html>