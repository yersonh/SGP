<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../models/PuntoMapaModel.php';

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

// Verificar si el usuario está logueado y es SuperAdmin
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    header('Location: ../index.php');
    exit();
}

$pdo = Database::getConnection();
$usuarioModel = new UsuarioModel($pdo);
$puntoMapaModel = new PuntoMapaModel($pdo);
$usuario_logueado = $usuarioModel->getUsuarioById($_SESSION['id_usuario']);

// Tipos de puntos y colores
$tiposPuntos = $puntoMapaModel->obtenerTiposPuntos();
$coloresMarcadores = $puntoMapaModel->obtenerColoresMarcadores();

// Obtener puntos del usuario
$puntosUsuario = $puntoMapaModel->obtenerPuntosPorUsuario($_SESSION['id_usuario']);

// OBTENER REFERENCIADORES ACTIVOS - NUEVO
$referenciadoresActivos = $usuarioModel->getReferenciadoresActivos();

// Determinar si estamos en modo pantalla completa
$isFullscreen = isset($_GET['fullscreen']) && $_GET['fullscreen'] == '1';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isFullscreen ? 'Mapa Full Screen' : 'Mapa - Puerto Gaitán | SGP'; ?></title>
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
          crossorigin=""/>
    
    <!-- Leaflet JavaScript -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
            crossorigin=""></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Nuestro CSS -->
    <link rel="stylesheet" href="../styles/mapa.css">
    
    <!-- Solo los estilos condicionales mínimos en línea -->
    <style>
        body {
            <?php if(!$isFullscreen): ?>
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #e2e8f0;
            height: 100vh;
            overflow: hidden;
            <?php else: ?>
            background: #1a1a2e;
            margin: 0;
            padding: 0;
            height: 100vh;
            <?php endif; ?>
        }

        #map {
            <?php if(!$isFullscreen): ?>
            flex: 1;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid #4a5568;
            <?php else: ?>
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            height: 100vh !important;
            width: 100vw !important;
            border: none;
            <?php endif; ?>
        }
    </style>
</head>
<body class="<?php echo $isFullscreen ? 'fullscreen-mode' : 'normal-mode'; ?>">
    <?php if(!$isFullscreen): ?>
    <!-- Header - Solo en modo normal -->
    <header class="main-header">
        <div class="header-container">
            <div class="header-top">
                <div class="header-title">
                    <h1><i class="fas fa-map-marked-alt"></i> Mapa de Puesto de Votación</h1>
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span>Super Admin: <?php echo htmlspecialchars($usuario_logueado['nombres'] . ' ' . $usuario_logueado['apellidos']); ?></span>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="../superadmin_dashboard.php" class="header-btn back-btn">
                        <i class="fas fa-arrow-left"></i> Volver a Referenciados
                    </a>
                    <a href="../logout.php" class="header-btn">
                        <i class="fas fa-sign-out-alt"></i> Salir
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content - Solo en modo normal -->
    <div class="main-container">
        <div class="main-layout">
            <!-- Columna izquierda - Mapa -->
            <div class="map-column">
                <!-- Mapa -->
                <div class="map-container">
                    <div class="map-header">
                        <div class="map-title">
                            <h2><i class="fas fa-map"></i> Mapa Interactivo</h2>
                            <div class="location-badge">
                                <i class="fas fa-location-dot"></i> Puerto Gaitán, Meta
                            </div>
                        </div>
                        <div class="map-controls">                            
                            <!-- NUEVO: COMBO BOX DE REFERENCIADORES -->
                            <div class="referenciador-select-wrapper">
                                <select id="filterReferenciador" class="referenciador-select">
                                    <option value="">Todos los referenciadores</option>
                                    <?php if(!empty($referenciadoresActivos)): ?>
                                        <?php foreach($referenciadoresActivos as $referenciador): ?>
                                        <option value="<?php echo $referenciador['id_usuario']; ?>">
                                            <?php echo htmlspecialchars($referenciador['nombres'] . ' ' . $referenciador['apellidos'] ); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            
                            <button class="map-btn" id="locateUserBtn">
                                <i class="fas fa-location-crosshairs"></i> Mi Ubicación
                            </button>
                        </div>
                    </div>
                    
                    <!-- Contenedor del mapa -->
                    <div id="map"></div>
                </div>
            </div>
            
            <!-- Columna derecha - Panel de puntos -->
            <div class="points-column">
                <!-- Panel de puntos -->
                <div class="points-panel">
                    <div class="panel-header">
                        <div class="panel-title">
                            <i class="fas fa-map-pin"></i>
                            <h3>Mis Puntos</h3>
                        </div>
                        <button class="add-point-btn" id="openAddPointModal">
                            <i class="fas fa-plus"></i> Nuevo
                        </button>
                    </div>
                    
                    <!-- Lista de puntos -->
                    <div class="points-list" id="pointsList">
                        <?php if(empty($puntosUsuario)): ?>
                        <div style="text-align: center; color: #a0aec0; padding: 40px 20px;">
                            <i class="fas fa-map-marker-alt" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.3;"></i>
                            <p>No hay puntos guardados aún</p>
                            <p style="font-size: 0.9rem; margin-top: 10px;">Haz clic en "Agregar Punto" en el mapa para empezar</p>
                        </div>
                        <?php else: ?>
                            <?php foreach($puntosUsuario as $punto): ?>
                            <div class="point-item" data-id="<?php echo $punto['id_punto']; ?>" 
                                 data-lat="<?php echo $punto['latitud']; ?>" 
                                 data-lng="<?php echo $punto['longitud']; ?>">
                                <div class="point-item-header">
                                    <div class="point-name"><?php echo htmlspecialchars($punto['nombre']); ?></div>
                                    <div class="point-type"><?php echo htmlspecialchars($tiposPuntos[$punto['tipo_punto']] ?? $punto['tipo_punto']); ?></div>
                                </div>
                                <?php if(!empty($punto['descripcion'])): ?>
                                <div class="point-description"><?php echo htmlspecialchars($punto['descripcion']); ?></div>
                                <?php endif; ?>
                                <div class="point-coords">
                                    <?php echo number_format($punto['latitud'], 6); ?>, <?php echo number_format($punto['longitud'], 6); ?>
                                </div>
                                <div class="point-actions">
                                    <button class="action-btn edit-btn" onclick="editarPunto(<?php echo $punto['id_punto']; ?>)">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                    <button class="action-btn delete-btn" onclick="eliminarPunto(<?php echo $punto['id_punto']; ?>)">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Contador de puntos -->
                    <div class="points-counter">
                        <div class="counter-value"><?php echo count($puntosUsuario); ?></div>
                        <div class="counter-label">Puntos Guardados</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal para agregar/editar puntos -->
    <div class="modal-overlay" id="pointModal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title" id="modalTitle">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>Agregar Nuevo Punto</span>
                </div>
                <button class="close-modal" id="closeModal">&times;</button>
            </div>
            
            <form id="pointForm">
                <input type="hidden" id="puntoId" name="puntoId">
                
                <div class="form-group">
                    <label class="form-label" for="nombre">Nombre del Punto *</label>
                    <input type="text" id="nombre" name="nombre" class="form-input" required 
                           placeholder="Ej: Mi Casa, Oficina Central, etc.">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" class="form-textarea" 
                              placeholder="Descripción detallada del punto..."></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="tipo_punto">Tipo de Punto</label>
                    <select id="tipo_punto" name="tipo_punto" class="form-select">
                        <?php foreach($tiposPuntos as $valor => $texto): ?>
                        <option value="<?php echo $valor; ?>"><?php echo htmlspecialchars($texto); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="color_marcador">Color del Marcador</label>
                    <select id="color_marcador" name="color_marcador" class="form-select">
                        <?php foreach($coloresMarcadores as $valor => $texto): ?>
                        <option value="<?php echo $valor; ?>">
                            <span class="color-preview" style="background-color: <?php echo $valor; ?>"></span>
                            <?php echo htmlspecialchars($texto); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Coordenadas *</label>
                    <div class="coords-display" id="coordsDisplay">
                        Haz clic en el mapa para seleccionar ubicación
                    </div>
                    <div class="coords-input-group">
                        <input type="number" step="any" id="latitud" name="latitud" 
                               class="coords-input" placeholder="Latitud" 
                               required>
                        <input type="number" step="any" id="longitud" name="longitud" 
                               class="coords-input" placeholder="Longitud" 
                               required>
                    </div>
                    <div style="margin-top: 5px; font-size: 0.85rem; color: #a0aec0;">
                        <i class="fas fa-info-circle"></i> Puedes escribir las coordenadas manualmente o hacer clic en el mapa
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-secondary" id="cancelBtn">Cancelar</button>
                    <button type="submit" class="btn-primary" id="saveBtn">
                        <i class="fas fa-save"></i> Guardar Punto
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php else: ?>
    <!-- Modo Pantalla Completa -->
    <div id="map"></div>
    
    <!-- Botón para salir de pantalla completa -->
    <button class="fullscreen-exit-btn" id="exitFullscreenBtn">
        <i class="fas fa-times-circle"></i> Salir de Pantalla Completa
    </button>
    
    <!-- Indicador de tecla ESC -->
    <div class="esc-hint" id="escHint">
        <i class="fas fa-keyboard"></i> Presiona <kbd>ESC</kbd> para salir
    </div>
    <?php endif; ?>

    <!-- Variables PHP que se pasan al JavaScript -->
    <script>
        // Variables globales que necesita el JavaScript externo
        window.PHP_VARS = {
            isFullscreen: <?php echo $isFullscreen ? 'true' : 'false'; ?>,
            puntosUsuario: <?php echo json_encode($puntosUsuario); ?>,
            tiposPuntos: <?php echo json_encode($tiposPuntos); ?>,
            coloresMarcadores: <?php echo json_encode($coloresMarcadores); ?>,
            idUsuario: <?php echo $_SESSION['id_usuario']; ?>,
            referenciadoresActivos: <?php 
                // Limpiar datos para evitar problemas con JSON
                $refs_clean = array();
                if (!empty($referenciadoresActivos)) {
                    foreach ($referenciadoresActivos as $ref) {
                        $refs_clean[] = array(
                            'id_usuario' => $ref['id_usuario'] ?? '',
                            'nombres' => $ref['nombres'] ?? '',
                            'apellidos' => $ref['apellidos'] ?? '',
                            'cedula' => $ref['cedula'] ?? ''
                        );
                    }
                }
                echo json_encode($refs_clean, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
            ?>
        };
    </script>

    <!-- Nuestro JavaScript externo -->
    <script src="../js/mapa.js"></script>
</body>
</html>