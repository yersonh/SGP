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
    
    <style>
       * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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

<?php if(!$isFullscreen): ?>
/* ============ HEADER - ESCRITORIO ============ */
.main-header {
    background: linear-gradient(135deg, #2d3748 0%, #4a5568 100%);
    padding: 15px 30px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
}

.header-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-title h1 {
    font-size: 1.8rem;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 10px;
}

.header-title h1 i {
    color: #4fc3f7;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.9rem;
    color: #cbd5e0;
}

.header-actions {
    display: flex;
    gap: 15px;
}

.header-btn {
    background: linear-gradient(135deg, #4fc3f7 0%, #2196f3 100%);
    color: white;
    padding: 8px 15px;
    border-radius: 6px;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.header-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(79, 195, 247, 0.3);
}

.back-btn {
    background: linear-gradient(135deg, #718096 0%, #4a5568 100%);
}

.back-btn:hover {
    box-shadow: 0 4px 12px rgba(113, 128, 150, 0.3);
}

/* ============ MAIN CONTENT - ESCRITORIO ============ */
.main-container {
    margin-top: 80px;
    height: calc(100vh - 80px);
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.main-layout {
    display: flex;
    gap: 20px;
    height: 100%;
}

/* Columna izquierda - Mapa */
.map-column {
    flex: 3;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* Columna derecha - Panel de puntos */
.points-column {
    flex: 1;
    min-width: 300px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* Mapa Container */
.map-container {
    flex: 1;
    background: #2d3748;
    border-radius: 12px;
    padding: 15px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
    display: flex;
    flex-direction: column;
}

.map-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 2px solid #4a5568;
}

.map-title {
    display: flex;
    align-items: center;
    gap: 10px;
}

.map-title h2 {
    font-size: 1.4rem;
    color: #fff;
}

.map-title h2 i {
    color: #4fc3f7;
}

.location-badge {
    background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 6px;
}

.map-controls {
    display: flex;
    gap: 10px;
}

.map-btn {
    background: #4a5568;
    color: #e2e8f0;
    border: none;
    padding: 8px 15px;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.map-btn:hover {
    background: #5a6578;
    transform: translateY(-2px);
}

.map-btn.active {
    background: #4fc3f7;
    color: white;
}

/* Botón de geolocalización específico */
.locate-btn {
    background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%) !important;
    color: white !important;
}

.locate-btn:hover {
    background: linear-gradient(135deg, #2E7D32 0%, #1B5E20 100%) !important;
}

.locate-btn.active {
    background: linear-gradient(135deg, #1B5E20 0%, #0D3B0D 100%) !important;
    box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.3) !important;
}

/* Panel de puntos */
.points-panel {
    background: #2d3748;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
    display: flex;
    flex-direction: column;
    height: 100%;
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #4a5568;
}

.panel-title {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #4fc3f7;
    font-size: 1.2rem;
}

.add-point-btn {
    background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
    color: white;
    border: none;
    padding: 8px 15px;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.add-point-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
}

/* Lista de puntos */
.points-list {
    flex: 1;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 10px;
    max-height: 500px;
}

.point-item {
    background: #4a5568;
    border-radius: 8px;
    padding: 15px;
    border-left: 4px solid #4fc3f7;
    transition: all 0.3s ease;
    cursor: pointer;
}

.point-item:hover {
    background: #5a6578;
    transform: translateX(5px);
}

.point-item-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 8px;
}

.point-name {
    font-weight: 600;
    color: white;
    font-size: 1rem;
}

.point-type {
    background: rgba(79, 195, 247, 0.2);
    color: #4fc3f7;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
}

.point-description {
    color: #cbd5e0;
    font-size: 0.85rem;
    margin-bottom: 8px;
    line-height: 1.4;
}

.point-coords {
    color: #a0aec0;
    font-size: 0.8rem;
    font-family: monospace;
}

.point-actions {
    display: flex;
    gap: 10px;
    margin-top: 10px;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.point-item:hover .point-actions {
    opacity: 1;
}

.action-btn {
    background: none;
    border: none;
    color: #cbd5e0;
    cursor: pointer;
    font-size: 0.8rem;
    padding: 4px 8px;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.action-btn:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

.edit-btn:hover {
    color: #4fc3f7;
}

.delete-btn:hover {
    color: #f44336;
}

/* Modal para agregar/editar puntos */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.8);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 2000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.modal-overlay.active {
    opacity: 1;
    visibility: visible;
}

.modal-content {
    background: #2d3748;
    border-radius: 12px;
    padding: 30px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
    transform: translateY(20px);
    transition: transform 0.3s ease;
}

.modal-overlay.active .modal-content {
    transform: translateY(0);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #4a5568;
}

.modal-title {
    color: #4fc3f7;
    font-size: 1.3rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.close-modal {
    background: none;
    border: none;
    color: #cbd5e0;
    font-size: 1.5rem;
    cursor: pointer;
    transition: color 0.3s ease;
}

.close-modal:hover {
    color: #f44336;
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    margin-bottom: 8px;
    color: #e2e8f0;
    font-weight: 500;
}

.form-input,
.form-select,
.form-textarea {
    width: 100%;
    padding: 12px 15px;
    background: #4a5568;
    border: 2px solid #5a6578;
    border-radius: 6px;
    color: white;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
    outline: none;
    border-color: #4fc3f7;
    box-shadow: 0 0 0 3px rgba(79, 195, 247, 0.1);
}

.form-textarea {
    min-height: 100px;
    resize: vertical;
}

.color-preview {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    border: 2px solid white;
    margin-right: 10px;
    display: inline-block;
}

.coords-input-group {
    display: flex;
    gap: 10px;
    margin-top: 8px;
}

.coords-input {
    flex: 1;
    padding: 12px 15px;
    background: #4a5568;
    border: 2px solid #5a6578;
    border-radius: 6px;
    color: white;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.coords-input:focus {
    outline: none;
    border-color: #4fc3f7;
    box-shadow: 0 0 0 3px rgba(79, 195, 247, 0.1);
}

.coords-display {
    background: #4a5568;
    padding: 10px;
    border-radius: 6px;
    font-family: monospace;
    margin-top: 8px;
    min-height: 44px;
    display: flex;
    align-items: center;
    color: #cbd5e0;
}

.form-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
}

.btn-primary {
    flex: 1;
    background: linear-gradient(135deg, #4fc3f7 0%, #2196f3 100%);
    color: white;
    border: none;
    padding: 12px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(79, 195, 247, 0.3);
}

.btn-secondary {
    flex: 1;
    background: #4a5568;
    color: white;
    border: none;
    padding: 12px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-secondary:hover {
    background: #5a6578;
}

/* Contador de puntos */
.points-counter {
    background: rgba(79, 195, 247, 0.1);
    border-radius: 8px;
    padding: 15px;
    margin-top: 20px;
}

.counter-value {
    font-size: 2rem;
    font-weight: bold;
    color: #4fc3f7;
    text-align: center;
    margin-bottom: 5px;
}

.counter-label {
    text-align: center;
    color: #cbd5e0;
    font-size: 0.9rem;
}
<?php endif; ?>

/* ============ MAPA - ESTILOS COMUNES ============ */
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

/* ============ LEAFLET PERSONALIZACIÓN ============ */
.leaflet-control-zoom {
    border: 2px solid #4a5568 !important;
    border-radius: 6px !important;
    overflow: hidden;
}

.leaflet-control-zoom a {
    background: #2d3748 !important;
    color: #e2e8f0 !important;
    border-bottom: 1px solid #4a5568 !important;
}

.leaflet-control-zoom a:hover {
    background: #4a5568 !important;
}

.leaflet-control-attribution {
    background: rgba(45, 55, 72, 0.9) !important;
    color: #cbd5e0 !important;
    padding: 5px 10px !important;
    border-radius: 4px !important;
    font-size: 0.8rem !important;
}

.leaflet-control-attribution a {
    color: #4fc3f7 !important;
}

/* Estilos para marcadores personalizados */
.custom-marker {
    transition: all 0.3s ease;
}

.custom-marker:hover {
    transform: scale(1.2);
    z-index: 1000 !important;
}

/* ============ GEOLOCALIZACIÓN ============ */
/* Estilos para el marcador de ubicación del usuario */
.user-location-marker {
    background-color: #2196F3 !important;
    border: 3px solid white !important;
    box-shadow: 0 0 10px rgba(33, 150, 243, 0.7) !important;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(33, 150, 243, 0.7);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(33, 150, 243, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(33, 150, 243, 0);
    }
}

/* Popup de ubicación del usuario */
.user-location-popup .leaflet-popup-content {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.user-location-popup .leaflet-popup-content-wrapper {
    background: #2196F3;
    color: white;
    border-radius: 8px;
}

.user-location-popup .leaflet-popup-tip {
    background: #2196F3;
}

/* Notificaciones de ubicación */
.location-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 20px;
    border-radius: 8px;
    color: white;
    font-weight: 500;
    z-index: 10000;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    animation: slideIn 0.3s ease;
}

.location-notification.success {
    background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
}

.location-notification.error {
    background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
}

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

@keyframes slideOut {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(100%);
        opacity: 0;
    }
}

/* ============ BOTÓN FULLSCREEN ============ */
<?php if($isFullscreen): ?>
.fullscreen-exit-btn {
    position: fixed;
    bottom: 20px;
    left: 20px;
    z-index: 1000;
    background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(0,0,0,0.4);
    opacity: 0.9;
}

.fullscreen-exit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(244, 67, 54, 0.4);
    opacity: 1;
}

/* Indicador de tecla ESC */
.esc-hint {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1000;
    background: rgba(45, 55, 72, 0.9);
    color: #cbd5e0;
    padding: 8px 15px;
    border-radius: 8px;
    font-size: 12px;
    border: 1px solid #4a5568;
    display: flex;
    align-items: center;
    gap: 8px;
}

.esc-hint kbd {
    background: #2d3748;
    border: 1px solid #4a5568;
    border-radius: 4px;
    padding: 2px 6px;
    font-family: monospace;
    font-size: 11px;
    color: #4fc3f7;
}
<?php endif; ?>

/* ============ SCROLLBAR PERSONALIZADO ============ */
.points-list::-webkit-scrollbar {
    width: 8px;
}

.points-list::-webkit-scrollbar-track {
    background: #4a5568;
    border-radius: 4px;
}

.points-list::-webkit-scrollbar-thumb {
    background: #718096;
    border-radius: 4px;
}

.points-list::-webkit-scrollbar-thumb:hover {
    background: #4fc3f7;
}

/* ============ RESPONSIVE - TABLET (1024px y menos) ============ */
@media (max-width: 1024px) {
    <?php if(!$isFullscreen): ?>
    .main-layout {
        flex-direction: column;
        height: auto;
        min-height: calc(100vh - 80px);
    }
    
    .map-column {
        flex: 1;
        min-height: 50vh;
    }
    
    .points-column {
        min-width: auto;
        max-height: 400px;
        flex: 0 0 auto;
    }
    
    .points-list {
        max-height: 300px;
    }
    
    .header-title h1 {
        font-size: 1.5rem;
    }
    
    .user-info {
        font-size: 0.8rem;
    }
    
    .header-btn {
        padding: 6px 12px;
        font-size: 0.85rem;
    }
    <?php endif; ?>
}

/* ============ RESPONSIVE - TABLET PEQUEÑA (768px y menos) ============ */
@media (max-width: 768px) {
    <?php if(!$isFullscreen): ?>
    /* Header optimizado para móvil */
    .main-header {
        padding: 10px 15px;
    }
    
    .header-top {
        flex-direction: column;
        gap: 10px;
        align-items: flex-start;
    }
    
    .header-title h1 {
        font-size: 1.3rem;
        flex-wrap: wrap;
    }
    
    .user-info {
        margin-top: 5px;
        font-size: 0.75rem;
    }
    
    .header-actions {
        width: 100%;
        justify-content: space-between;
        margin-top: 10px;
    }
    
    .header-btn {
        flex: 1;
        justify-content: center;
        padding: 8px 10px;
        font-size: 0.8rem;
    }
    
    .header-btn i {
        margin-right: 5px;
    }
    
    .header-btn span {
        display: inline-block;
    }
    
    /* Main content optimizado */
    .main-container {
        margin-top: 140px; /* Más espacio para header más grande */
        height: calc(100vh - 140px);
        padding: 10px;
        gap: 15px;
    }
    
    .map-container {
        padding: 10px;
        border-radius: 10px;
    }
    
    .map-header {
        flex-direction: column;
        gap: 12px;
        align-items: stretch;
        margin-bottom: 12px;
        padding-bottom: 12px;
    }
    
    .map-title {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .map-title h2 {
        font-size: 1.2rem;
    }
    
    .location-badge {
        align-self: flex-start;
        font-size: 0.8rem;
        padding: 5px 10px;
    }
    
    .map-controls {
        width: 100%;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }
    
    .map-btn {
        padding: 10px 12px;
        font-size: 0.85rem;
        justify-content: center;
    }
    
    .map-btn i {
        margin-right: 5px;
    }
    
    .map-btn span {
        display: inline-block;
    }
    
    /* Panel de puntos optimizado */
    .points-panel {
        padding: 15px;
        border-radius: 10px;
    }
    
    .panel-header {
        flex-direction: column;
        gap: 12px;
        align-items: stretch;
        margin-bottom: 15px;
    }
    
    .panel-title {
        font-size: 1.1rem;
    }
    
    .add-point-btn {
        width: 100%;
        justify-content: center;
        padding: 10px;
    }
    
    .point-item {
        padding: 12px;
    }
    
    .point-item-header {
        flex-direction: column;
        gap: 8px;
        align-items: flex-start;
    }
    
    .point-type {
        align-self: flex-start;
    }
    
    .point-actions {
        opacity: 1; /* Siempre visible en móvil */
        justify-content: space-between;
    }
    
    .action-btn {
        flex: 1;
        justify-content: center;
        padding: 8px;
        font-size: 0.75rem;
    }
    
    /* Modal optimizado para móvil */
    .modal-content {
        width: 95%;
        padding: 20px;
        margin: 10px;
        max-height: 85vh;
    }
    
    .modal-title {
        font-size: 1.2rem;
    }
    
    .form-group {
        margin-bottom: 15px;
    }
    
    .form-input,
    .form-select,
    .form-textarea {
        padding: 10px 12px;
        font-size: 0.95rem;
    }
    
    .coords-input-group {
        flex-direction: column;
        gap: 8px;
    }
    
    .coords-input {
        width: 100%;
    }
    
    .form-actions {
        flex-direction: column;
        gap: 10px;
    }
    
    .btn-primary,
    .btn-secondary {
        padding: 12px;
        width: 100%;
    }
    
    /* Contador de puntos */
    .points-counter {
        padding: 12px;
        margin-top: 15px;
    }
    
    .counter-value {
        font-size: 1.8rem;
    }
    <?php else: ?>
    /* Fullscreen en móvil */
    .fullscreen-exit-btn {
        bottom: 10px;
        left: 10px;
        padding: 10px 18px;
        font-size: 13px;
    }
    
    .esc-hint {
        bottom: 10px;
        right: 10px;
        font-size: 11px;
        padding: 6px 12px;
    }
    <?php endif; ?>
}

/* ============ RESPONSIVE - MÓVIL PEQUEÑO (480px y menos) ============ */
@media (max-width: 480px) {
    <?php if(!$isFullscreen): ?>
    .header-title h1 {
        font-size: 1.1rem;
    }
    
    .header-btn {
        font-size: 0.75rem;
        padding: 6px 8px;
    }
    
    .header-btn i {
        margin-right: 3px;
        font-size: 0.9rem;
    }
    
    .main-container {
        margin-top: 150px;
        height: calc(100vh - 150px);
        padding: 8px;
    }
    
    .map-container {
        padding: 8px;
        border-radius: 8px;
    }
    
    .map-title h2 {
        font-size: 1.1rem;
    }
    
    .location-badge {
        font-size: 0.75rem;
        padding: 4px 8px;
    }
    
    .map-controls {
        grid-template-columns: 1fr;
        gap: 6px;
    }
    
    .map-btn {
        padding: 8px 10px;
        font-size: 0.8rem;
    }
    
    .points-panel {
        padding: 12px;
    }
    
    .panel-title {
        font-size: 1rem;
    }
    
    .point-name {
        font-size: 0.9rem;
    }
    
    .point-description {
        font-size: 0.8rem;
    }
    
    .point-coords {
        font-size: 0.75rem;
    }
    
    .modal-content {
        padding: 15px;
        margin: 5px;
    }
    
    .modal-title {
        font-size: 1.1rem;
    }
    
    .form-label {
        font-size: 0.9rem;
    }
    
    .form-input,
    .form-select,
    .form-textarea {
        font-size: 0.9rem;
        padding: 8px 10px;
    }
    <?php endif; ?>
    
    <?php if($isFullscreen): ?>
    .fullscreen-exit-btn {
        padding: 8px 15px;
        font-size: 12px;
        bottom: 5px;
        left: 5px;
    }
    
    .esc-hint {
        padding: 5px 10px;
        font-size: 10px;
        bottom: 5px;
        right: 5px;
    }
    <?php endif; ?>
}

/* ============ RESPONSIVE - MÓVIL MUY PEQUEÑO (320px y menos) ============ */
@media (max-width: 320px) {
    <?php if(!$isFullscreen): ?>
    .header-title h1 {
        font-size: 1rem;
    }
    
    .header-btn span {
        display: none; /* Ocultar texto en botones muy pequeños */
    }
    
    .header-btn i {
        margin-right: 0;
        font-size: 1rem;
    }
    
    .map-btn span {
        display: none;
    }
    
    .map-btn i {
        margin-right: 0;
        font-size: 1rem;
    }
    
    .action-btn span {
        display: none;
    }
    
    .action-btn i {
        margin-right: 0;
        font-size: 1rem;
    }
    <?php endif; ?>
}

/* ============ ORIENTACIÓN HORIZONTAL EN MÓVIL ============ */
@media (max-height: 600px) and (orientation: landscape) {
    <?php if(!$isFullscreen): ?>
    .main-layout {
        flex-direction: row;
        height: 100%;
    }
    
    .map-column {
        min-height: auto;
    }
    
    .points-column {
        max-height: calc(100vh - 160px);
    }
    
    .main-container {
        margin-top: 90px;
        height: calc(100vh - 90px);
    }
    
    .map-controls {
        grid-template-columns: 1fr 1fr;
    }
    <?php endif; ?>
}

/* ============ ALTO CONTRASTE Y ACCESIBILIDAD ============ */
@media (prefers-contrast: high) {
    .map-btn {
        border: 2px solid white;
    }
    
    .point-item {
        border-left: 4px solid white;
    }
    
    .action-btn {
        border: 1px solid white;
    }
}

/* ============ REDUCCIÓN DE MOVIMIENTO ============ */
@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
    
    .user-location-marker {
        animation: none !important;
    }
    
    .map-btn:hover {
        transform: none;
    }
    
    .point-item:hover {
        transform: none;
    }
}

/* ============ DARK/LIGHT MODE SUPPORT ============ */
@media (prefers-color-scheme: light) {
    <?php if(!$isFullscreen): ?>
    body {
        background: linear-gradient(135deg, #f0f2f5 0%, #e4e6eb 100%);
        color: #2d3748;
    }
    
    .main-header {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    .header-title h1 {
        color: #2d3748;
    }
    
    .user-info {
        color: #4a5568;
    }
    
    .map-container,
    .points-panel {
        background: #ffffff;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    .map-title h2 {
        color: #2d3748;
    }
    
    .point-item {
        background: #f8f9fa;
        border-left: 4px solid #4fc3f7;
    }
    
    .point-name {
        color: #2d3748;
    }
    
    .point-description {
        color: #4a5568;
    }
    
    .point-coords {
        color: #718096;
    }
    
    .modal-content {
        background: #ffffff;
        color: #2d3748;
    }
    
    .form-label {
        color: #2d3748;
    }
    
    .form-input,
    .form-select,
    .form-textarea {
        background: #f8f9fa;
        border-color: #e2e8f0;
        color: #2d3748;
    }
    
    .form-input:focus,
    .form-select:focus,
    .form-textarea:focus {
        border-color: #4fc3f7;
    }
    <?php endif; ?>
}
    </style>
</head>
<body>
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
                            <button class="map-btn" id="addPointModeBtn">
                                <i class="fas fa-map-marker-alt"></i> Agregar Punto
                            </button>
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

    <script>
document.addEventListener('DOMContentLoaded', function() {
    // Coordenadas de Puerto Gaitán, Meta
    const PUERTO_GAITAN = [4.314, -72.082];
    const ZOOM_INICIAL = 12;
    
    // Determinar si estamos en modo fullscreen
    const isFullscreen = <?php echo $isFullscreen ? 'true' : 'false'; ?>;
    
    // Variables globales para el mapa
    let map;
    let capaCalles, capaTopografico;
    let currentLatLng = PUERTO_GAITAN;
    let currentZoom = ZOOM_INICIAL;
    
    // Variables para gestión de puntos
    let puntosLayerGroup = L.layerGroup();
    let puntosGuardados = <?php echo json_encode($puntosUsuario); ?>;
    let editandoPuntoId = null;
    let marcadorSeleccion = null;
    let modalAbierto = false;
    
    // Tipos de puntos desde PHP
    const tiposPuntos = <?php echo json_encode($tiposPuntos); ?>;
    const coloresMarcadores = <?php echo json_encode($coloresMarcadores); ?>;
    
    // Intentar recuperar la posición del mapa desde sessionStorage
    if (!isFullscreen) {
        const savedPosition = sessionStorage.getItem('mapPosition');
        const savedZoom = sessionStorage.getItem('mapZoom');
        
        if (savedPosition) {
            try {
                currentLatLng = JSON.parse(savedPosition);
                currentZoom = savedZoom ? parseInt(savedZoom) : ZOOM_INICIAL;
            } catch(e) {
                console.log('No se pudo recuperar la posición guardada');
            }
        }
    }
    
    // Inicializar mapa
    map = L.map('map').setView(currentLatLng, currentZoom);
    
    // Configurar capas
    capaCalles = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
    });
    
    capaTopografico = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
        attribution: 'Map data: © OpenStreetMap contributors, SRTM | Map style: © OpenTopoMap (CC-BY-SA)',
        maxZoom: 17
    });
    
    // Agregar capa inicial
    capaCalles.addTo(map);
    puntosLayerGroup.addTo(map);
    
    // Control de capas
    const capasBase = {
        "Mapa de Calles": capaCalles,
        "Mapa Topográfico": capaTopografico
    };
    
    const capasOverlay = {
        "Mis Puntos": puntosLayerGroup
    };
    
    L.control.layers(capasBase, capasOverlay).addTo(map);
    
    // Coordenadas al hacer clic
    const popupCoordenadas = L.popup();
    
    // Evento de clic en el mapa
    map.on('click', function(e) {
        console.log('Click en mapa:', e.latlng.lat, e.latlng.lng);
        
        // Mostrar coordenadas en popup
        popupCoordenadas
            .setLatLng(e.latlng)
            .setContent(`
                <div style="padding: 10px; min-width: 200px;">
                    <strong style="color: #4fc3f7;">Coordenadas seleccionadas</strong><br>
                    <div style="margin-top: 5px; font-family: monospace;">
                        Lat: ${e.latlng.lat.toFixed(6)}° N<br>
                        Lng: ${e.latlng.lng.toFixed(6)}° W
                    </div>
                </div>
            `)
            .openOn(map);
        
        // Si el modal está abierto, establecer las coordenadas
        if (modalAbierto) {
            establecerCoordenadasDesdeMapa(e.latlng.lat, e.latlng.lng);
            
            // Crear o mover marcador de selección
            if (marcadorSeleccion) {
                marcadorSeleccion.setLatLng(e.latlng);
            } else {
                marcadorSeleccion = L.marker(e.latlng, {
                    icon: crearIconoMarcador('#4fc3f7'),
                    draggable: true,
                    zIndexOffset: 1000
                }).addTo(map);
                
                // Evento de arrastre del marcador
                marcadorSeleccion.on('dragend', function() {
                    const pos = marcadorSeleccion.getLatLng();
                    establecerCoordenadasDesdeMapa(pos.lat, pos.lng);
                    console.log('Marcador arrastrado a:', pos.lat, pos.lng);
                });
            }
        }
    });
    
    // Guardar posición del mapa cuando se mueva
    map.on('moveend', function() {
        if (!isFullscreen) {
            const center = map.getCenter();
            const zoom = map.getZoom();
            
            // Guardar en sessionStorage
            sessionStorage.setItem('mapPosition', JSON.stringify([center.lat, center.lng]));
            sessionStorage.setItem('mapZoom', zoom.toString());
        }
    });
    
    // Escala
    L.control.scale({imperial: false}).addTo(map);
    
    // Agregar puntos de referencia
    const puntosReferencia = [
        {
            nombre: "Alcaldía Municipal",
            coords: [4.3135, -72.080],
            tipo: "gobierno"
        },
        {
            nombre: "Hospital Local",
            coords: [4.315, -72.085],
            tipo: "salud"
        },
        {
            nombre: "Plaza Principal",
            coords: [4.3145, -72.0825],
            tipo: "publico"
        }
    ];
    
    // Iconos personalizados para puntos de referencia
    const iconos = {
        gobierno: L.divIcon({
            html: '<i class="fas fa-landmark" style="color: #4CAF50; font-size: 20px;"></i>',
            iconSize: [20, 20],
            className: 'custom-icon'
        }),
        salud: L.divIcon({
            html: '<i class="fas fa-hospital" style="color: #f44336; font-size: 20px;"></i>',
            iconSize: [20, 20],
            className: 'custom-icon'
        }),
        publico: L.divIcon({
            html: '<i class="fas fa-map-marker-alt" style="color: #FF9800; font-size: 20px;"></i>',
            iconSize: [20, 20],
            className: 'custom-icon'
        })
    };
    
    // Agregar puntos de referencia
    puntosReferencia.forEach(punto => {
        L.marker(punto.coords, { icon: iconos[punto.tipo] })
            .addTo(map)
            .bindPopup(`<strong>${punto.nombre}</strong><br>Puerto Gaitán, Meta`);
    });
    
    // ============ FUNCIONALIDAD DE PUNTOS ============
    
    // Función para crear icono personalizado
    function crearIconoMarcador(color) {
        return L.divIcon({
            html: `<div style="
                background-color: ${color};
                border: 3px solid white;
                border-radius: 50%;
                width: 24px;
                height: 24px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-size: 12px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.3);
                cursor: pointer;
            ">
                <i class="fas fa-map-pin"></i>
            </div>`,
            iconSize: [24, 24],
            iconAnchor: [12, 12],
            popupAnchor: [0, -12],
            className: 'custom-marker'
        });
    }
    
    // Función para establecer coordenadas desde el mapa
    function establecerCoordenadasDesdeMapa(lat, lng) {
        console.log('Estableciendo coordenadas desde mapa:', lat, lng);
        
        document.getElementById('latitud').value = lat;
        document.getElementById('longitud').value = lng;
        document.getElementById('coordsDisplay').innerHTML = `
            <div style="color: #4fc3f7; font-weight: bold;">Coordenadas seleccionadas</div>
            <div style="font-size: 0.9rem; margin-top: 2px;">
                Lat: ${lat.toFixed(6)}° N<br>
                Lng: ${lng.toFixed(6)}° W
            </div>
        `;
    }
    
    // Función para establecer coordenadas desde los campos de entrada
    function actualizarCoordenadasDesdeInputs() {
        const lat = document.getElementById('latitud').value;
        const lng = document.getElementById('longitud').value;
        
        if (lat && lng) {
            document.getElementById('coordsDisplay').innerHTML = `
                <div style="color: #4fc3f7; font-weight: bold;">Coordenadas ingresadas</div>
                <div style="font-size: 0.9rem; margin-top: 2px;">
                    Lat: ${parseFloat(lat).toFixed(6)}° N<br>
                    Lng: ${parseFloat(lng).toFixed(6)}° W
                </div>
            `;
            
            // Mover el marcador si existe
            if (marcadorSeleccion) {
                marcadorSeleccion.setLatLng([lat, lng]);
                map.setView([lat, lng], 15);
            }
        }
    }
    
    // Función para cargar puntos en el mapa
    function cargarPuntosEnMapa() {
        puntosLayerGroup.clearLayers();
        
        puntosGuardados.forEach(punto => {
            const icono = crearIconoMarcador(punto.color_marcador || '#4fc3f7');
            const marcador = L.marker([punto.latitud, punto.longitud], { 
                icon: icono,
                title: punto.nombre
            }).addTo(puntosLayerGroup);
            
            // Contenido del popup
            const popupContent = `
                <div style="min-width: 250px; max-width: 300px;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                        <div style="
                            background-color: ${punto.color_marcador || '#4fc3f7'};
                            width: 30px;
                            height: 30px;
                            border-radius: 50%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            color: white;
                        ">
                            <i class="fas fa-map-pin"></i>
                        </div>
                        <div>
                            <h4 style="margin: 0; color: #2d3748; font-size: 16px;">${punto.nombre}</h4>
                            <small style="color: #718096;">${tiposPuntos[punto.tipo_punto] || punto.tipo_punto}</small>
                        </div>
                    </div>
                    
                    ${punto.descripcion ? `
                    <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #e2e8f0;">
                        <p style="margin: 0; color: #4a5568; line-height: 1.4; font-size: 14px;">
                            ${punto.descripcion}
                        </p>
                    </div>
                    ` : ''}
                    
                    <div style="margin-top: 12px; padding-top: 10px; border-top: 1px solid #e2e8f0;">
                        <p style="margin: 5px 0; font-size: 12px; color: #a0aec0;">
                            <i class="fas fa-calendar"></i>
                            ${new Date(punto.fecha_creacion).toLocaleDateString('es-ES', {
                                day: '2-digit',
                                month: '2-digit',
                                year: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            })}
                        </p>
                        <p style="margin: 5px 0; font-size: 12px; color: #a0aec0;">
                            <i class="fas fa-user"></i>
                            ${punto.nombres} ${punto.apellidos}
                        </p>
                    </div>
                    
                    <div style="margin-top: 15px;">
                        <button onclick="centrarEnPunto(${punto.latitud}, ${punto.longitud})" 
                                style="background: #4fc3f7; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; width: 100%;">
                            <i class="fas fa-search-location"></i> Centrar en este punto
                        </button>
                    </div>
                </div>
            `;
            
            marcador.bindPopup(popupContent);
            
            // Agregar tooltip
            marcador.bindTooltip(punto.nombre, {
                permanent: false,
                direction: 'top',
                className: 'referenciado-tooltip'
            });
        });
    }
    
    // Cargar puntos iniciales
    cargarPuntosEnMapa();
    
    // Función para centrar mapa en un punto
    window.centrarEnPunto = function(lat, lng) {
        map.setView([lat, lng], 15);
    };
    
    <?php if(!$isFullscreen): ?>
    // ============ MODAL Y FORMULARIO ============
    
    const pointModal = document.getElementById('pointModal');
    const openAddPointModalBtn = document.getElementById('openAddPointModal');
    const addPointModeBtn = document.getElementById('addPointModeBtn');
    const closeModalBtn = document.getElementById('closeModal');
    const cancelBtn = document.getElementById('cancelBtn');
    const pointForm = document.getElementById('pointForm');
    const modalTitle = document.getElementById('modalTitle');
    const pointsList = document.getElementById('pointsList');
    const latitudInput = document.getElementById('latitud');
    const longitudInput = document.getElementById('longitud');
    
    // Abrir modal desde botón "Nuevo"
    openAddPointModalBtn.addEventListener('click', function() {
        abrirModal('nuevo');
    });
    
    // Abrir modal desde botón "Agregar Punto"
    addPointModeBtn.addEventListener('click', function() {
        abrirModal('nuevo');
    });
    
    // Cerrar modal
    closeModalBtn.addEventListener('click', cerrarModal);
    cancelBtn.addEventListener('click', cerrarModal);
    
    // Cerrar modal al hacer clic fuera
    pointModal.addEventListener('click', function(e) {
        if (e.target === pointModal) {
            cerrarModal();
        }
    });
    
    // Eventos para actualizar coordenadas cuando se escriben manualmente
    latitudInput.addEventListener('input', actualizarCoordenadasDesdeInputs);
    longitudInput.addEventListener('input', actualizarCoordenadasDesdeInputs);
    
    // Función para abrir modal
    function abrirModal(modo, puntoId = null) {
        console.log('Abriendo modal, modo:', modo);
        modalAbierto = true;
        
        // Limpiar formulario
        pointForm.reset();
        document.getElementById('puntoId').value = '';
        latitudInput.value = '';
        longitudInput.value = '';
        document.getElementById('coordsDisplay').innerHTML = 'Haz clic en el mapa para seleccionar ubicación';
        
        // Establecer valores por defecto
        document.getElementById('tipo_punto').value = 'general';
        document.getElementById('color_marcador').value = '#4fc3f7';
        
        if (modo === 'editar' && puntoId) {
            // Buscar punto a editar
            const punto = puntosGuardados.find(p => p.id_punto == puntoId);
            if (punto) {
                editandoPuntoId = puntoId;
                
                // Llenar formulario
                document.getElementById('puntoId').value = punto.id_punto;
                document.getElementById('nombre').value = punto.nombre;
                document.getElementById('descripcion').value = punto.descripcion || '';
                document.getElementById('tipo_punto').value = punto.tipo_punto;
                document.getElementById('color_marcador').value = punto.color_marcador || '#4fc3f7';
                
                // Establecer coordenadas
                latitudInput.value = punto.latitud;
                longitudInput.value = punto.longitud;
                actualizarCoordenadasDesdeInputs();
                
                // Cambiar título del modal
                modalTitle.innerHTML = '<i class="fas fa-edit"></i><span>Editar Punto</span>';
                
                // Centrar mapa en el punto
                map.setView([punto.latitud, punto.longitud], 15);
                
                // Crear marcador de selección
                if (marcadorSeleccion) {
                    marcadorSeleccion.setLatLng([punto.latitud, punto.longitud]);
                } else {
                    marcadorSeleccion = L.marker([punto.latitud, punto.longitud], {
                        icon: crearIconoMarcador('#4fc3f7'),
                        draggable: true,
                        zIndexOffset: 1000
                    }).addTo(map);
                    
                    marcadorSeleccion.on('dragend', function() {
                        const pos = marcadorSeleccion.getLatLng();
                        establecerCoordenadasDesdeMapa(pos.lat, pos.lng);
                    });
                }
            }
        } else {
            editandoPuntoId = null;
            modalTitle.innerHTML = '<i class="fas fa-map-marker-alt"></i><span>Agregar Nuevo Punto</span>';
            
            // Si hay una posición guardada, usarla
            const savedPosition = sessionStorage.getItem('mapPosition');
            if (savedPosition) {
                try {
                    const pos = JSON.parse(savedPosition);
                    establecerCoordenadasDesdeMapa(pos[0], pos[1]);
                } catch(e) {
                    // Ignorar error
                }
            }
        }
        
        // Mostrar modal
        pointModal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    // Función para cerrar modal
    function cerrarModal() {
        console.log('Cerrando modal');
        pointModal.classList.remove('active');
        document.body.style.overflow = '';
        modalAbierto = false;
        editandoPuntoId = null;
        
        // Eliminar marcador de selección
        if (marcadorSeleccion) {
            map.removeLayer(marcadorSeleccion);
            marcadorSeleccion = null;
        }
    }
    
    // Manejar envío del formulario
    // Manejar envío del formulario
pointForm.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    console.log('Enviando formulario...');
    
    // Validar coordenadas
    const latitud = latitudInput.value;
    const longitud = longitudInput.value;
    
    if (!latitud || !longitud) {
        alert('Por favor, selecciona una ubicación en el mapa o escribe las coordenadas manualmente.');
        return;
    }
    
    // Validar nombre
    const nombre = document.getElementById('nombre').value.trim();
    if (!nombre) {
        alert('Por favor, ingresa un nombre para el punto.');
        return;
    }
    
    // Obtener datos del formulario
    const formData = new FormData(pointForm);
    const data = Object.fromEntries(formData.entries());
    
    // Convertir coordenadas a números
    data.latitud = parseFloat(data.latitud);
    data.longitud = parseFloat(data.longitud);
    
    // Agregar ID de usuario
    data.id_usuario = <?php echo $_SESSION['id_usuario']; ?>;
    
    // Guardar referencia al botón y su texto original
    const saveBtn = document.getElementById('saveBtn');
    const originalBtnText = saveBtn.innerHTML;
    
    try {
        // Mostrar carga
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
        saveBtn.disabled = true;
        
        console.log('Enviando datos:', data);
        
        // Enviar datos al servidor
        const response = await fetch('guardar_punto.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        console.log('Estado de respuesta:', response.status);
        
        // Verificar si la respuesta es JSON válido
        const responseText = await response.text();
        console.log('Respuesta cruda:', responseText);
        
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (jsonError) {
            console.error('Error parseando JSON:', jsonError);
            throw new Error('La respuesta del servidor no es válida. Por favor, intenta nuevamente.');
        }
        
        console.log('Resultado parseado:', result);
        
        if (result.success) {
            // Actualizar lista de puntos
            await actualizarListaPuntos();
            
            // Cerrar modal
            cerrarModal();
            
            // Mostrar mensaje de éxito
            alert(result.message || '✓ Punto guardado correctamente');
            
            // Centrar en el nuevo punto si se guardó
            if (result.puntoId) {
                // Buscar el punto recién guardado
                const nuevoPunto = puntosGuardados.find(p => p.id_punto == result.puntoId);
                if (nuevoPunto) {
                    centrarEnPunto(nuevoPunto.latitud, nuevoPunto.longitud);
                }
            }
            
        } else {
            throw new Error(result.message || 'Error al guardar el punto');
        }
        
    } catch (error) {
        console.error('Error completo:', error);
        alert('❌ Error al guardar el punto: ' + error.message);
    } finally {
        // Siempre restaurar el botón, incluso si hay errores
        saveBtn.innerHTML = originalBtnText;
        saveBtn.disabled = false;
    }
});
    
    // Función para actualizar lista de puntos
    async function actualizarListaPuntos() {
        console.log('Actualizando lista de puntos...');
        try {
            const response = await fetch('obtener_puntos.php');
            const result = await response.json();
            
            if (result.success) {
                puntosGuardados = result.puntos;
                console.log('Puntos actualizados:', puntosGuardados.length);
                cargarPuntosEnMapa();
                actualizarInterfazPuntos();
            }
        } catch (error) {
            console.error('Error al actualizar puntos:', error);
        }
    }
    
    // Función para actualizar interfaz de puntos
    function actualizarInterfazPuntos() {
        console.log('Actualizando interfaz de puntos...');
        if (puntosGuardados.length === 0) {
            pointsList.innerHTML = `
                <div style="text-align: center; color: #a0aec0; padding: 40px 20px;">
                    <i class="fas fa-map-marker-alt" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.3;"></i>
                    <p>No hay puntos guardados aún</p>
                    <p style="font-size: 0.9rem; margin-top: 10px;">Haz clic en "Agregar Punto" en el mapa para empezar</p>
                </div>
            `;
        } else {
            let html = '';
            puntosGuardados.forEach(punto => {
                html += `
                    <div class="point-item" data-id="${punto.id_punto}" 
                         data-lat="${punto.latitud}" 
                         data-lng="${punto.longitud}">
                        <div class="point-item-header">
                            <div class="point-name">${punto.nombre}</div>
                            <div class="point-type">${tiposPuntos[punto.tipo_punto] || punto.tipo_punto}</div>
                        </div>
                        ${punto.descripcion ? `
                        <div class="point-description">${punto.descripcion}</div>
                        ` : ''}
                        <div class="point-coords">
                            ${parseFloat(punto.latitud).toFixed(6)}, ${parseFloat(punto.longitud).toFixed(6)}
                        </div>
                        <div class="point-actions">
                            <button class="action-btn edit-btn" onclick="editarPunto(${punto.id_punto})">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button class="action-btn delete-btn" onclick="eliminarPunto(${punto.id_punto})">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                        </div>
                    </div>
                `;
            });
            pointsList.innerHTML = html;
            
            // Actualizar contador
            document.querySelector('.counter-value').textContent = puntosGuardados.length;
            
            // Agregar eventos a los items
            document.querySelectorAll('.point-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    if (!e.target.closest('.point-actions')) {
                        const lat = parseFloat(this.dataset.lat);
                        const lng = parseFloat(this.dataset.lng);
                        centrarEnPunto(lat, lng);
                    }
                });
            });
        }
    }
    
    // Función para editar punto (global)
    window.editarPunto = function(puntoId) {
        console.log('Editando punto:', puntoId);
        abrirModal('editar', puntoId);
    };
    
    // Función para eliminar punto (global)
    window.eliminarPunto = async function(puntoId) {
        if (!confirm('¿Estás seguro de que deseas eliminar este punto?')) {
            return;
        }
        
        console.log('Eliminando punto:', puntoId);
        
        try {
            const response = await fetch('eliminar_punto.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id_punto: puntoId })
            });
            
            const result = await response.json();
            
            if (result.success) {
                await actualizarListaPuntos();
                alert(result.message || '✓ Punto eliminado correctamente');
            } else {
                throw new Error(result.message || 'Error al eliminar el punto');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('❌ Error al eliminar el punto: ' + error.message);
        }
    };
    
    // ============ BOTÓN DE PANTALLA COMPLETA ============
    // Crear control personalizado para pantalla completa
    const FullscreenControl = L.Control.extend({
        options: {
            position: 'bottomleft'
        },
        
        onAdd: function(map) {
            const container = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
            container.style.backgroundColor = '#2d3748';
            container.style.border = '2px solid #4a5568';
            container.style.borderRadius = '6px';
            container.style.overflow = 'hidden';
            container.style.marginLeft = '10px';
            container.style.marginBottom = '20px';
            
            const button = L.DomUtil.create('a', '', container);
            button.href = '#';
            button.title = 'Pantalla completa';
            button.style.width = '36px';
            button.style.height = '36px';
            button.style.display = 'flex';
            button.style.alignItems = 'center';
            button.style.justifyContent = 'center';
            button.style.color = '#4a5568'; 
            button.style.textDecoration = 'none';
            button.innerHTML = '<i class="fas fa-expand"></i>';
            
            L.DomEvent.on(button, 'click', L.DomEvent.stopPropagation)
                      .on(button, 'click', L.DomEvent.preventDefault)
                      .on(button, 'click', function() {
                          abrirPantallaCompleta();
                      });
            
            L.DomEvent.on(button, 'mouseover', function() {
                button.style.backgroundColor = '#4a5568';
                button.style.color = 'white';
            });
            
            L.DomEvent.on(button, 'mouseout', function() {
                button.style.backgroundColor = '#2d3748';
                button.style.color = '#e2e8f0';
            });
            
            return container;
        }
    });

    // Agregar el control al mapa
    map.addControl(new FullscreenControl());
// Agregar estas variables globales al inicio de la sección JavaScript
let userLocationMarker = null;
let isTrackingUser = false;
let watchId = null;
let locateBtn = null;

// Después de inicializar el mapa, agregar esta función:
function initGeolocation() {
    locateBtn = document.getElementById('locateUserBtn');
    
    if (!locateBtn) {
        console.log('Botón de geolocalización no encontrado');
        return;
    }
    
    locateBtn.addEventListener('click', toggleUserTracking);
    
    // Verificar si el navegador soporta geolocalización
    if (!navigator.geolocation) {
        locateBtn.innerHTML = '<i class="fas fa-location-slash"></i> No soportado';
        locateBtn.disabled = true;
        alert('La geolocalización no es soportada por tu navegador.');
        return;
    }
    
    // Solicitar permisos al cargar la página
    requestLocationPermission();
}

// Función para solicitar permiso de ubicación
function requestLocationPermission() {
    if (navigator.permissions && navigator.permissions.query) {
        navigator.permissions.query({ name: 'geolocation' })
            .then(function(permissionStatus) {
                console.log('Estado del permiso de ubicación:', permissionStatus.state);
                
                permissionStatus.onchange = function() {
                    console.log('Permiso de ubicación cambiado:', this.state);
                    updateLocateButtonState(this.state);
                };
                
                updateLocateButtonState(permissionStatus.state);
            })
            .catch(function(error) {
                console.error('Error al consultar permisos:', error);
            });
    } else {
        // Navegadores antiguos - solicitar permiso al intentar usar
        console.log('API de permisos no disponible');
    }
}

// Función para actualizar el estado del botón según los permisos
function updateLocateButtonState(permissionState) {
    if (!locateBtn) return;
    
    switch(permissionState) {
        case 'granted':
            locateBtn.innerHTML = '<i class="fas fa-location-crosshairs"></i> Mi Ubicación';
            locateBtn.disabled = false;
            locateBtn.title = 'Mostrar mi ubicación en el mapa';
            break;
        case 'prompt':
            locateBtn.innerHTML = '<i class="fas fa-location-dot"></i> Permitir Ubicación';
            locateBtn.disabled = false;
            locateBtn.title = 'Haz clic para permitir acceso a tu ubicación';
            break;
        case 'denied':
            locateBtn.innerHTML = '<i class="fas fa-location-slash"></i> Ubicación Bloqueada';
            locateBtn.disabled = true;
            locateBtn.title = 'El acceso a la ubicación está bloqueado. Permítelo en la configuración del navegador.';
            break;
    }
}

// Función para alternar el seguimiento de ubicación
function toggleUserTracking() {
    if (isTrackingUser) {
        stopTrackingUser();
    } else {
        startTrackingUser();
    }
}

// Función para iniciar el seguimiento de ubicación
function startTrackingUser() {
    if (!navigator.geolocation) return;
    
    locateBtn.classList.add('active');
    locateBtn.innerHTML = '<i class="fas fa-location-crosshairs"></i> Siguiendo...';
    isTrackingUser = true;
    
    // Opciones para geolocalización
    const options = {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0
    };
    
    // Obtener ubicación actual
    navigator.geolocation.getCurrentPosition(
        function(position) {
            showUserLocation(position);
            
            // Iniciar seguimiento continuo
            watchId = navigator.geolocation.watchPosition(
                updateUserLocation,
                handleLocationError,
                options
            );
        },
        handleLocationError,
        options
    );
}

// Función para detener el seguimiento de ubicación
function stopTrackingUser() {
    if (watchId !== null) {
        navigator.geolocation.clearWatch(watchId);
        watchId = null;
    }
    
    locateBtn.classList.remove('active');
    locateBtn.innerHTML = '<i class="fas fa-location-crosshairs"></i> Mi Ubicación';
    isTrackingUser = false;
    
    // No eliminar el marcador, solo dejar de actualizarlo
    // El usuario puede seguir viendo su última ubicación conocida
}

// Función para mostrar la ubicación del usuario
function showUserLocation(position) {
    const lat = position.coords.latitude;
    const lng = position.coords.longitude;
    const accuracy = position.coords.accuracy;
    
    console.log('Ubicación del usuario:', lat, lng, 'Precisión:', accuracy + 'm');
    
    // Crear o actualizar marcador
    if (!userLocationMarker) {
        // Crear icono personalizado para ubicación del usuario
        const userIcon = L.divIcon({
            html: `
                <div style="
                    background-color: #2196F3;
                    border: 3px solid white;
                    border-radius: 50%;
                    width: 24px;
                    height: 24px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    box-shadow: 0 0 10px rgba(33, 150, 243, 0.7);
                    animation: pulse 2s infinite;
                ">
                    <i class="fas fa-user" style="color: white; font-size: 12px;"></i>
                </div>
            `,
            iconSize: [24, 24],
            iconAnchor: [12, 12],
            className: 'user-location-marker'
        });
        
        userLocationMarker = L.marker([lat, lng], {
            icon: userIcon,
            zIndexOffset: 2000
        }).addTo(map);
        
        // Crear círculo de precisión
        const accuracyCircle = L.circle([lat, lng], {
            color: '#2196F3',
            fillColor: '#2196F3',
            fillOpacity: 0.2,
            radius: accuracy
        }).addTo(map);
        
        // Vincular el círculo al marcador
        userLocationMarker.accuracyCircle = accuracyCircle;
    } else {
        userLocationMarker.setLatLng([lat, lng]);
        if (userLocationMarker.accuracyCircle) {
            userLocationMarker.accuracyCircle.setLatLng([lat, lng]);
            userLocationMarker.accuracyCircle.setRadius(accuracy);
        }
    }
    
    // Actualizar popup
    const popupContent = `
        <div style="padding: 10px; min-width: 200px;">
            <strong style="color: white; font-size: 14px;">
                <i class="fas fa-user"></i> Tu Ubicación
            </strong>
            <div style="margin-top: 8px;">
                <div style="font-size: 12px; margin-bottom: 4px;">
                    <strong>Coordenadas:</strong>
                </div>
                <div style="font-family: monospace; font-size: 11px; background: rgba(255,255,255,0.1); padding: 5px; border-radius: 4px;">
                    Lat: ${lat.toFixed(6)}° N<br>
                    Lng: ${lng.toFixed(6)}° W
                </div>
                <div style="margin-top: 8px; font-size: 11px;">
                    <i class="fas fa-crosshairs"></i> Precisión: ${Math.round(accuracy)} metros
                </div>
                <div style="margin-top: 8px; font-size: 11px;">
                    <i class="fas fa-clock"></i> Actualizado: ${new Date().toLocaleTimeString()}
                </div>
            </div>
            <div style="margin-top: 10px;">
                <button onclick="centerOnUserLocation()" 
                        style="background: white; color: #2196F3; border: none; padding: 6px 12px; 
                               border-radius: 4px; cursor: pointer; font-size: 12px; width: 100%;
                               font-weight: bold;">
                    <i class="fas fa-search-location"></i> Centrar aquí
                </button>
            </div>
        </div>
    `;
    
    userLocationMarker.bindPopup(popupContent, {
        className: 'user-location-popup',
        closeButton: false,
        autoClose: false,
        closeOnClick: false
    }).openPopup();
    
    // Centrar el mapa en la ubicación del usuario con zoom apropiado
    const zoomLevel = accuracy < 100 ? 16 : accuracy < 500 ? 15 : 14;
    map.setView([lat, lng], zoomLevel);
    
    // Mostrar notificación
    showLocationNotification('Ubicación encontrada', 'success');
}

// Función para actualizar la ubicación del usuario
function updateUserLocation(position) {
    const lat = position.coords.latitude;
    const lng = position.coords.longitude;
    const accuracy = position.coords.accuracy;
    
    if (userLocationMarker) {
        userLocationMarker.setLatLng([lat, lng]);
        
        if (userLocationMarker.accuracyCircle) {
            userLocationMarker.accuracyCircle.setLatLng([lat, lng]);
            userLocationMarker.accuracyCircle.setRadius(accuracy);
        }
        
        // Actualizar popup
        const popupContent = `
            <div style="padding: 10px; min-width: 200px;">
                <strong style="color: white; font-size: 14px;">
                    <i class="fas fa-user"></i> Tu Ubicación
                </strong>
                <div style="margin-top: 8px;">
                    <div style="font-size: 12px; margin-bottom: 4px;">
                        <strong>Coordenadas:</strong>
                    </div>
                    <div style="font-family: monospace; font-size: 11px; background: rgba(255,255,255,0.1); padding: 5px; border-radius: 4px;">
                        Lat: ${lat.toFixed(6)}° N<br>
                        Lng: ${lng.toFixed(6)}° W
                    </div>
                    <div style="margin-top: 8px; font-size: 11px;">
                        <i class="fas fa-crosshairs"></i> Precisión: ${Math.round(accuracy)} metros
                    </div>
                    <div style="margin-top: 8px; font-size: 11px;">
                        <i class="fas fa-clock"></i> Actualizado: ${new Date().toLocaleTimeString()}
                    </div>
                </div>
                <div style="margin-top: 10px;">
                    <button onclick="centerOnUserLocation()" 
                            style="background: white; color: #2196F3; border: none; padding: 6px 12px; 
                                   border-radius: 4px; cursor: pointer; font-size: 12px; width: 100%;
                                   font-weight: bold;">
                        <i class="fas fa-search-location"></i> Centrar aquí
                    </button>
                </div>
            </div>
        `;
        
        userLocationMarker.getPopup().setContent(popupContent);
    }
}

// Función para manejar errores de geolocalización
function handleLocationError(error) {
    console.error('Error de geolocalización:', error);
    
    stopTrackingUser();
    
    let errorMessage = 'No se pudo obtener tu ubicación. ';
    
    switch(error.code) {
        case error.PERMISSION_DENIED:
            errorMessage += 'Permiso denegado. Por favor, permite el acceso a la ubicación en la configuración de tu navegador.';
            showLocationNotification('Permiso de ubicación denegado', 'error');
            break;
        case error.POSITION_UNAVAILABLE:
            errorMessage += 'La información de ubicación no está disponible.';
            showLocationNotification('Ubicación no disponible', 'error');
            break;
        case error.TIMEOUT:
            errorMessage += 'La solicitud de ubicación ha expirado.';
            showLocationNotification('Tiempo de espera agotado', 'error');
            break;
        case error.UNKNOWN_ERROR:
            errorMessage += 'Ocurrió un error desconocido.';
            showLocationNotification('Error desconocido', 'error');
            break;
    }
    
    alert(errorMessage);
}

// Función para centrar en la ubicación del usuario
window.centerOnUserLocation = function() {
    if (userLocationMarker) {
        const latLng = userLocationMarker.getLatLng();
        map.setView(latLng, 16);
        userLocationMarker.openPopup();
    }
};

// Función para mostrar notificaciones de ubicación
function showLocationNotification(message, type) {
    // Crear elemento de notificación
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        z-index: 10000;
        display: flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        animation: slideIn 0.3s ease;
    `;
    
    if (type === 'success') {
        notification.style.background = 'linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%)';
        notification.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
    } else {
        notification.style.background = 'linear-gradient(135deg, #f44336 0%, #d32f2f 100%)';
        notification.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
    }
    
    document.body.appendChild(notification);
    
    // Remover después de 5 segundos
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 5000);
}

// Agregar estilos de animación para notificaciones
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
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Inicializar geolocalización después de cargar el mapa
setTimeout(() => {
    initGeolocation();
}, 1000);

// También agregar en modo fullscreen
<?php if($isFullscreen): ?>
// En modo fullscreen, crear botón de ubicación
const locateControl = L.Control.extend({
    options: {
        position: 'bottomright'
    },
    
    onAdd: function(map) {
        const container = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
        container.style.backgroundColor = '#2d3748';
        container.style.border = '2px solid #4a5568';
        container.style.borderRadius = '6px';
        container.style.overflow = 'hidden';
        container.style.marginRight = '10px';
        container.style.marginBottom = '80px';
        
        const button = L.DomUtil.create('a', '', container);
        button.href = '#';
        button.title = 'Mi Ubicación';
        button.style.width = '36px';
        button.style.height = '36px';
        button.style.display = 'flex';
        button.style.alignItems = 'center';
        button.style.justifyContent = 'center';
        button.style.color = '#4a5568';
        button.style.textDecoration = 'none';
        button.innerHTML = '<i class="fas fa-location-crosshairs"></i>';
        
        L.DomEvent.on(button, 'click', L.DomEvent.stopPropagation)
                  .on(button, 'click', L.DomEvent.preventDefault)
                  .on(button, 'click', function() {
                      if (!navigator.geolocation) {
                          alert('La geolocalización no es soportada por tu navegador.');
                          return;
                      }
                      
                      navigator.geolocation.getCurrentPosition(function(position) {
                          const lat = position.coords.latitude;
                          const lng = position.coords.longitude;
                          
                          // Crear marcador de ubicación del usuario
                          const userIcon = L.divIcon({
                              html: `<div style="
                                  background-color: #2196F3;
                                  border: 3px solid white;
                                  border-radius: 50%;
                                  width: 24px;
                                  height: 24px;
                                  display: flex;
                                  align-items: center;
                                  justify-content: center;
                                  box-shadow: 0 0 10px rgba(33, 150, 243, 0.7);
                              "><i class="fas fa-user" style="color: white; font-size: 12px;"></i></div>`,
                              iconSize: [24, 24],
                              iconAnchor: [12, 12]
                          });
                          
                          const marker = L.marker([lat, lng], { icon: userIcon }).addTo(map);
                          marker.bindPopup(`<b>Tu ubicación</b><br>Lat: ${lat.toFixed(6)}<br>Lng: ${lng.toFixed(6)}`).openPopup();
                          
                          map.setView([lat, lng], 16);
                      }, function(error) {
                          alert('Error al obtener ubicación: ' + error.message);
                      });
                  });
        
        L.DomEvent.on(button, 'mouseover', function() {
            button.style.backgroundColor = '#4a5568';
            button.style.color = 'white';
        });
        
        L.DomEvent.on(button, 'mouseout', function() {
            button.style.backgroundColor = '#2d3748';
            button.style.color = '#e2e8f0';
        });
        
        return container;
    }
});

// Agregar control de ubicación en modo fullscreen
map.addControl(new locateControl());
<?php endif; ?>
    // Función para abrir pantalla completa
    function abrirPantallaCompleta() {
        // Obtener la posición y zoom actual del mapa
        const center = map.getCenter();
        const zoom = map.getZoom();
        
        // Guardar en sessionStorage para que el mapa fullscreen lo use
        sessionStorage.setItem('mapPosition', JSON.stringify([center.lat, center.lng]));
        sessionStorage.setItem('mapZoom', zoom.toString());
        
        // Abrir este mismo archivo en modo fullscreen
        const ancho = window.screen.width;
        const alto = window.screen.height;
        
        const features = `
            width=${ancho},
            height=${alto},
            left=0,
            top=0,
            scrollbars=no,
            resizable=yes,
            fullscreen=yes,
            location=no,
            menubar=no,
            toolbar=no,
            status=no
        `;
        
        // Abrir este mismo archivo con parámetro fullscreen
        window.open('?fullscreen=1', 'MapaFullScreen', features);
    }
    // ============ FIN BOTÓN PANTALLA COMPLETA ============
    <?php else: ?>
    // ============ FUNCIONALIDAD PARA MODO FULLSCREEN ============
    
    // Función para salir de pantalla completa
    function salirPantallaCompleta() {
        window.close();
    }
    
    // Agregar evento al botón de salir
    document.getElementById('exitFullscreenBtn').addEventListener('click', salirPantallaCompleta);
    
    // Agregar evento para tecla ESC
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' || event.key === 'Esc' || event.keyCode === 27) {
            salirPantallaCompleta();
        }
    });
    
    // Ocultar/mostrar indicador ESC al pasar el mouse
    const escHint = document.getElementById('escHint');
    let hideTimeout;
    
    function hideEscHint() {
        escHint.style.opacity = '0';
        escHint.style.transform = 'translateY(10px)';
    }
    
    function showEscHint() {
        escHint.style.opacity = '1';
        escHint.style.transform = 'translateY(0)';
        
        // Configurar para que se oculte después de 3 segundos
        clearTimeout(hideTimeout);
        hideTimeout = setTimeout(hideEscHint, 3000);
    }
    
    // Inicialmente mostrar el hint y luego ocultarlo después de 5 segundos
    setTimeout(hideEscHint, 5000);
    
    // Mostrar cuando el usuario mueve el mouse
    document.addEventListener('mousemove', function() {
        showEscHint();
    });
    
    // Mostrar cuando el usuario toca la pantalla (móviles)
    document.addEventListener('touchstart', function() {
        showEscHint();
    });
    
    // En modo fullscreen, ajustar el mapa al redimensionar la ventana
    window.addEventListener('resize', function() {
        map.invalidateSize();
    });
    
    // Forzar redimensionamiento inicial
    setTimeout(() => map.invalidateSize(), 100);
    
    // Log para depuración
    console.log('Evento ESC configurado para salir de pantalla completa');
    <?php endif; ?>
    
    // Log para depuración
    console.log('Mapa de Puerto Gaitán cargado correctamente');
    console.log('Modo fullscreen:', isFullscreen);
    console.log('Puntos cargados:', puntosGuardados.length);
    console.log('Posición inicial:', currentLatLng);
    console.log('Zoom inicial:', currentZoom);
    console.log('Modal abierto inicialmente:', modalAbierto);
    
    <?php if(!$isFullscreen): ?>
    console.log('Sistema de puntos habilitado');
    console.log('Botón de pantalla completa añadido');
    <?php endif; ?>
});
    </script>
</body>
</html>