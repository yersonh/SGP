<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../models/SistemaModel.php';

// Verificar si el usuario está logueado y es SuperAdmin
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    header('Location: ../index.php');
    exit();
}

$pdo = Database::getConnection();
$usuarioModel = new UsuarioModel($pdo);
$sistemaModel = new SistemaModel($pdo);

// Obtener datos del usuario logueado
$usuario_logueado = $usuarioModel->getUsuarioById($_SESSION['id_usuario']);

// Obtener estadísticas reales
// En tu primera vista (data_referidos.php), cambia:
try {
    // 1. Total de referidos ACTIVOS (solo activos)
    $queryTotalReferidos = "SELECT COUNT(*) as total_referidos FROM referenciados WHERE activo = true";
    $stmtTotal = $pdo->query($queryTotalReferidos);
    $resultTotal = $stmtTotal->fetch();
    $totalReferidos = $resultTotal['total_referidos'] ?? 0;

    // 2. Suma de todos los topes de usuarios ACTIVOS - ¡CAMBIAR AQUÍ!
    // Cambia para sumar solo topes de REFERENCIADORES activos
    $querySumaTopes = "SELECT SUM(tope) as suma_topes 
                       FROM usuario 
                       WHERE tope IS NOT NULL 
                         AND activo = true 
                         AND tipo_usuario = 'Referenciador'";  // <-- FILTRO CLAVE
    $stmtTopes = $pdo->query($querySumaTopes);
    $resultTopes = $stmtTopes->fetch();
    $sumaTopes = $resultTopes['suma_topes'] ?? 0;
    
    // 3. Contar usuarios con rol "Descargador" ACTIVOS
    // También filtra por tipo_usuario
    $queryDescargadores = "SELECT COUNT(*) as total_descargadores 
                           FROM usuario 
                           WHERE tipo_usuario = 'Descargador' 
                             AND activo = true";
    $stmtDescargadores = $pdo->query($queryDescargadores);
    $resultDescargadores = $stmtDescargadores->fetch();
    $totalDescargadores = $resultDescargadores['total_descargadores'] ?? 0;
    
    // 4. Contar referenciadores ACTIVOS
    $queryReferenciadores = "SELECT COUNT(*) as total_referenciadores 
                             FROM usuario 
                             WHERE tipo_usuario = 'Referenciador' 
                               AND activo = true";
    $stmtReferenciadores = $pdo->query($queryReferenciadores);
    $resultReferenciadores = $stmtReferenciadores->fetch();
    $totalReferenciadores = $resultReferenciadores['total_referenciadores'] ?? 0;
    
    // Calcular porcentaje de avance (Total Referidos ACTIVOS vs Tope Total de ACTIVOS)
    $porcentajeAvance = 0;
    if ($sumaTopes > 0) {
        $porcentajeAvance = round(($totalReferidos / $sumaTopes) * 100, 2);
        // Limitar al 100% si se supera
        $porcentajeAvance = min($porcentajeAvance, 100);
    }
    
} catch (Exception $e) {
    // En caso de error, usar valores por defecto
    $totalReferidos = 0;
    $sumaTopes = 0;
    $totalDescargadores = 0;
    $totalReferenciadores = 0;
    $porcentajeAvance = 0;
    error_log("Error al obtener estadísticas: " . $e->getMessage());
}

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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Referidos - Super Admin - SGP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
       /* ==========================================================================
   VARIABLES PARA TEMA CLARO/OSCURO
   ========================================================================== */
:root {
    /* Tema claro (valores por defecto) */
    --bg-primary: #f5f7fa;
    --bg-secondary: #ffffff;
    --bg-tertiary: #f8f9fa;
    --bg-header: linear-gradient(135deg, #2c3e50, #1a252f);
    --text-primary: #333333;
    --text-secondary: #666666;
    --text-light: #888888;
    --text-dark: #2c3e50;
    --border-color: #eaeaea;
    --border-light: #dee2e6;
    --accent-color: #3498db;
    --accent-dark: #2980b9;
    --success-color: #27ae60;
    --success-dark: #219653;
    --shadow-color: rgba(0, 0, 0, 0.08);
    --shadow-medium: rgba(0, 0, 0, 0.15);
    --card-bg: #ffffff;
    --progress-bg: #e9ecef;
    --footer-bg: white;
    --footer-border: #eaeaea;
    --modal-bg: #f1f5f9;
    --modal-border: #e2e8f0;
    --gradient-blue-light: linear-gradient(135deg, #e3f2fd, #bbdefb);
    --gradient-blue-dark: linear-gradient(135deg, #3498db, #2980b9);
    --gradient-green-light: linear-gradient(135deg, #d4edda, #c3e6cb);
    --gradient-green-dark: linear-gradient(135deg, #27ae60, #219653);
}

@media (prefers-color-scheme: dark) {
    :root {
        /* Tema oscuro */
        --bg-primary: #121212;
        --bg-secondary: #1e1e1e;
        --bg-tertiary: #2d2d2d;
        --bg-header: linear-gradient(135deg, #0d1117, #161b22);
        --text-primary: #e0e0e0;
        --text-secondary: #b0b0b0;
        --text-light: #888888;
        --text-dark: #ffffff;
        --border-color: #333333;
        --border-light: #444444;
        --accent-color: #58a6ff;
        --accent-dark: #1f6feb;
        --success-color: #2ea043;
        --success-dark: #1e7a34;
        --shadow-color: rgba(0, 0, 0, 0.3);
        --shadow-medium: rgba(0, 0, 0, 0.4);
        --card-bg: #1e1e1e;
        --progress-bg: #333333;
        --footer-bg: #1a1a1a;
        --footer-border: #333333;
        --modal-bg: #252525;
        --modal-border: #333333;
        --gradient-blue-light: linear-gradient(135deg, #1a365d, #2d3748);
        --gradient-blue-dark: linear-gradient(135deg, #2b6cb0, #2c5282);
        --gradient-green-light: linear-gradient(135deg, #22543d, #276749);
        --gradient-green-dark: linear-gradient(135deg, #2f855a, #38a169);
    }
}

/* ==========================================================================
   ESTILOS GENERALES
   ========================================================================== */
* {
    box-sizing: border-box;
    transition: background-color 0.3s ease, border-color 0.3s ease;
}

body {
    background-color: var(--bg-primary);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    color: var(--text-primary);
    margin: 0;
    padding: 0;
    font-size: 14px;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

/* ==========================================================================
   HEADER
   ========================================================================== */
.main-header {
    background: var(--bg-header);
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
    color: var(--accent-color);
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

/* ==========================================================================
   BREADCRUMB
   ========================================================================== */
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
    color: var(--accent-color);
    text-decoration: none;
}

.breadcrumb-item.active {
    color: var(--text-secondary);
}

.breadcrumb-item + .breadcrumb-item::before {
    content: "›";
    color: var(--text-light);
    padding: 0 8px;
}

/* ==========================================================================
   CONTADOR COMPACTO - SIN CAMBIOS (IGUAL PARA AMBOS TEMAS)
   ========================================================================== */
.countdown-compact-container {
    max-width: 1400px;
    margin: 0 auto 20px;
    padding: 0 15px;
}

.countdown-compact {
    background: linear-gradient(135deg, #2c3e50, #3498db);
    border-radius: 10px;
    padding: 15px 20px;
    color: white;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 3px 10px rgba(0,0,0,0.15);
    border: 1px solid rgba(255,255,255,0.1);
}

.countdown-compact-title {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1;
}

.countdown-compact-title i {
    color: #f1c40f;
    font-size: 1.2rem;
}

.countdown-compact-title span {
    font-weight: 600;
    font-size: 1rem;
}

.countdown-compact-timer {
    flex: 2;
    text-align: center;
    font-family: 'Segoe UI', monospace;
    font-size: 1.5rem;
    font-weight: 700;
    letter-spacing: 1px;
}

.countdown-compact-timer span {
    display: inline-block;
    min-width: 35px;
    text-align: center;
}

.countdown-compact-date {
    flex: 1;
    text-align: right;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    font-size: 0.9rem;
    color: rgba(255,255,255,0.9);
}

.countdown-compact-date i {
    color: #f1c40f;
}

/* ==========================================================================
   MAIN CONTAINER
   ========================================================================== */
.main-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 15px 30px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

/* ==========================================================================
   DASHBOARD HEADER
   ========================================================================== */
.dashboard-header {
    text-align: center;
    margin: 20px 0 40px;
    padding: 0 20px;
}

.dashboard-title {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
}

.dashboard-subtitle {
    font-size: 1.1rem;
    color: var(--text-secondary);
    max-width: 600px;
    margin: 0 auto;
    line-height: 1.5;
}

/* ==========================================================================
   GRID DE OPCIONES
   ========================================================================== */
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 30px;
    max-width: 900px;
    margin: 0 auto;
    width: 100%;
}

/* ==========================================================================
   BOTONES ESTILO TARJETA
   ========================================================================== */
.data-option {
    background: var(--card-bg);
    border-radius: 12px;
    padding: 40px 30px;
    text-align: center;
    text-decoration: none;
    color: var(--text-dark);
    transition: all 0.3s ease;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 15px var(--shadow-color);
    border: 1px solid var(--border-color);
    position: relative;
    overflow: hidden;
    min-height: 300px;
}

.data-option::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
}

/* Color para Data Referidos */
.data-referidos::before {
    background: linear-gradient(90deg, var(--accent-color), var(--accent-dark));
}

/* Color para Data Descargadores */
.data-descargadores::before {
    background: linear-gradient(90deg, var(--success-color), var(--success-dark));
}

.data-option:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 25px var(--shadow-medium);
    text-decoration: none;
    color: var(--text-dark);
}

.data-referidos:hover {
    border-color: var(--accent-color);
}

.data-descargadores:hover {
    border-color: var(--success-color);
}

.data-icon-wrapper {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 25px;
    transition: all 0.3s ease;
}

.data-referidos .data-icon-wrapper {
    background: var(--gradient-blue-light);
}

.data-descargadores .data-icon-wrapper {
    background: var(--gradient-green-light);
}

.data-option:hover .data-icon-wrapper {
    transform: scale(1.1);
}

.data-referidos:hover .data-icon-wrapper {
    background: var(--gradient-blue-dark);
}

.data-descargadores:hover .data-icon-wrapper {
    background: var(--gradient-green-dark);
}

.data-icon {
    font-size: 2.5rem;
    transition: all 0.3s ease;
}

.data-referidos .data-icon {
    color: var(--accent-color);
}

.data-descargadores .data-icon {
    color: var(--success-color);
}

.data-option:hover .data-icon {
    color: white;
}

.data-title {
    font-size: 1.6rem;
    font-weight: 700;
    margin-bottom: 15px;
    color: var(--text-dark);
}

.data-description {
    font-size: 0.95rem;
    color: var(--text-secondary);
    line-height: 1.5;
    max-width: 90%;
    margin: 0 auto 20px;
}

/* ==========================================================================
   BARRA DE PROGRESO PARA DATA REFERIDOS
   ========================================================================== */
.progress-section {
    width: 100%;
    margin-top: 20px;
}

.progress-info {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
}

.progress-label {
    font-size: 0.9rem;
    color: var(--text-secondary);
    font-weight: 500;
}

.progress-percentage {
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--accent-color);
}

.progress-container {
    width: 100%;
    height: 12px;
    background-color: var(--progress-bg);
    border-radius: 6px;
    overflow: hidden;
    margin-bottom: 10px;
}

.data-referidos .progress-container {
    border: 1px solid var(--border-color);
}

.progress-bar {
    height: 100%;
    border-radius: 6px;
    /* REMOVEMOS LA TRANSICIÓN PARA QUE NO SE ANIME AL PASAR EL MOUSE */
    /* transition: width 0.5s ease-in-out; */
}

.data-referidos .progress-bar {
    background: linear-gradient(90deg, var(--accent-color), var(--accent-dark));
}

.progress-stats {
    display: flex;
    justify-content: space-between;
    font-size: 0.85rem;
    color: var(--text-secondary);
    margin-top: 5px;
}

.progress-current {
    font-weight: 600;
    color: var(--accent-color);
}

.progress-target {
    font-weight: 600;
    color: var(--text-secondary);
}

/* Nota sobre estadísticas */
.stats-note {
    font-size: 0.75rem;
    color: var(--text-light);
    text-align: center;
    margin-top: 10px;
    font-style: italic;
}

/* ==========================================================================
   DATA DESCARGADORES - ESTADÍSTICAS
   ========================================================================== */
.data-stats {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-top: 15px;
}

.stat-item {
    text-align: center;
}

.stat-number {
    font-size: 1.8rem;
    font-weight: 700;
    display: block;
}

.data-descargadores .stat-number {
    color: var(--success-color);
}

.stat-label {
    font-size: 0.8rem;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* ==========================================================================
   FOOTER
   ========================================================================== */
.system-footer {
    text-align: center;
    padding: 25px 0;
    background: var(--footer-bg);
    color: var(--text-primary);
    font-size: 0.9rem;
    line-height: 1.6;
    border-top: 2px solid var(--footer-border);
    width: 100%;
    margin-top: 60px;
}

.system-footer p {
    margin: 8px 0;
    color: var(--text-primary);
}

/* ==========================================================================
   LOGO Y ELEMENTOS ESPECIALES
   ========================================================================== */
.container.text-center.mb-3 img {
    max-width: 320px;
    height: auto;
    transition: max-width 0.3s ease;
}

/* ==========================================================================
   MODAL DE INFORMACIÓN DEL SISTEMA
   ========================================================================== */
.modal-system-info .modal-header {
    background: var(--bg-header);
    color: white;
}

.modal-system-info .modal-body {
    padding: 20px;
    background-color: var(--bg-primary);
    color: var(--text-primary);
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

/* Barra de progreso de licencia */
.licencia-info {
    background: linear-gradient(135deg, var(--bg-tertiary) 0%, var(--progress-bg) 100%);
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 20px;
    border: 1px solid var(--border-light);
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
    color: var(--text-dark);
    margin: 0;
}

.licencia-dias {
    font-size: 1rem;
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 20px;
    background: var(--accent-color);
    color: white;
}

.licencia-progress {
    height: 12px;
    border-radius: 6px;
    margin-bottom: 8px;
    background-color: var(--progress-bg);
    overflow: hidden;
}

.licencia-progress-bar {
    height: 100%;
    border-radius: 6px;
    transition: width 0.6s ease;
}

.licencia-fecha {
    font-size: 0.85rem;
    color: var(--text-secondary);
    text-align: center;
    margin-top: 5px;
}

/* Tarjetas de características */
.feature-card {
    background: var(--bg-tertiary);
    border-radius: 10px;
    padding: 20px;
    height: 100%;
    border-left: 4px solid var(--accent-color);
    transition: transform 0.3s ease;
}

.feature-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px var(--shadow-color);
}

.feature-icon {
    opacity: 0.8;
}

.feature-title {
    color: var(--text-dark);
    font-weight: 600;
    margin-bottom: 5px;
}

.feature-text {
    font-size: 14px;
    color: var(--text-secondary);
    line-height: 1.5;
    margin-bottom: 0;
}

/* Footer del modal */
.system-footer-modal {
    background: var(--modal-bg);
    border-radius: 8px;
    padding: 20px;
    margin-top: 30px;
    border-top: 2px solid var(--modal-border);
}

.logo-clickable {
    cursor: pointer;
    transition: transform 0.3s ease;
}

.logo-clickable:hover {
    transform: scale(1.05);
}

/* ==========================================================================
   IMAGEN CIRCULAR
   ========================================================================== */
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

.feature-img-header:hover {
    transform: scale(1.05);
}

/* ==========================================================================
   RESPONSIVE DESIGN - TABLET (992px y menos)
   ========================================================================== */
@media (max-width: 992px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
        max-width: 600px;
        gap: 25px;
    }
    
    .data-option {
        padding: 35px 25px;
        min-height: 280px;
    }
    
    .dashboard-title {
        font-size: 1.8rem;
    }
}

/* ==========================================================================
   RESPONSIVE DESIGN - MÓVIL (767px y menos)
   ========================================================================== */
@media (max-width: 767px) {
    /* Header */
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
    
    /* Contador compacto */
    .countdown-compact {
        flex-direction: column;
        gap: 10px;
        text-align: center;
        padding: 15px;
    }
    
    .countdown-compact-timer {
        order: 2;
        width: 100%;
    }
    
    .countdown-compact-title {
        order: 1;
        justify-content: center;
        width: 100%;
    }
    
    .countdown-compact-date {
        order: 3;
        justify-content: center;
        width: 100%;
    }
    
    /* Dashboard */
    .dashboard-header {
        margin: 15px 0 30px;
    }
    
    .dashboard-title {
        font-size: 1.6rem;
        flex-direction: column;
        gap: 10px;
    }
    
    .dashboard-subtitle {
        font-size: 1rem;
        padding: 0 10px;
    }
    
    /* Opciones */
    .data-icon-wrapper {
        width: 70px;
        height: 70px;
        margin-bottom: 20px;
    }
    
    .data-icon {
        font-size: 2.2rem;
    }
    
    .data-title {
        font-size: 1.4rem;
    }
    
    .progress-container {
        height: 10px;
    }
    
    .stat-number {
        font-size: 1.6rem;
    }
    
    /* Logo principal */
    .container.text-center.mb-3 img {
        max-width: 220px;
    }
    
    /* Imagen circular */
    .feature-img-header {
        width: 140px;
        height: 140px;
    }
    
    /* Modal */
    .modal-system-info .modal-body {
        padding: 15px;
    }
    
    .modal-logo {
        max-width: 200px;
    }
    
    .feature-card {
        padding: 15px;
        margin-bottom: 15px;
    }
    
    .modal-system-info h4 {
        font-size: 1.1rem;
    }
    
    .licencia-info {
        padding: 12px;
    }
    
    .licencia-title {
        font-size: 1rem;
    }
    
    .licencia-dias {
        font-size: 0.9rem;
        padding: 3px 10px;
    }
    
    .system-footer-modal {
        padding: 15px;
    }
    
    /* Footer */
    .system-footer {
        padding: 20px 15px;
        font-size: 0.85rem;
    }
}

/* ==========================================================================
   RESPONSIVE DESIGN - MÓVIL PEQUEÑO (480px y menos)
   ========================================================================== */
@media (max-width: 480px) {
    .data-option {
        padding: 30px 20px;
        min-height: 260px;
    }
    
    .data-icon-wrapper {
        width: 65px;
        height: 65px;
        margin-bottom: 18px;
    }
    
    .data-icon {
        font-size: 2rem;
    }
    
    .data-title {
        font-size: 1.3rem;
    }
    
    .data-description {
        font-size: 0.9rem;
    }
    
    .progress-container {
        height: 8px;
    }
    
    .progress-info {
        flex-direction: column;
        align-items: center;
        gap: 5px;
        margin-bottom: 10px;
    }
    
    .data-stats {
        flex-direction: column;
        gap: 10px;
    }
    
    .container.text-center.mb-3 img {
        max-width: 200px;
    }
    
    /* Contador compacto */
    .countdown-compact-timer {
        font-size: 1.3rem;
    }
    
    .countdown-compact-title span {
        font-size: 0.9rem;
    }
}

/* ==========================================================================
   RESPONSIVE DESIGN - MÓVIL MUY PEQUEÑO (576px y menos para modal)
   ========================================================================== */
@media (max-width: 576px) {
    .modal-system-info .modal-dialog {
        margin: 10px;
    }
    
    .modal-system-info .modal-body {
        padding: 12px;
    }
    
    .modal-logo-container {
        padding: 10px;
    }
    
    .modal-logo {
        max-width: 180px;
    }
    
    .licencia-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .licencia-dias {
        align-self: flex-start;
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
                    <h1><i class="fas fa-database"></i> Data Referidos - Super Admin</h1>
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
                <li class="breadcrumb-item active"><i class="fas fa-database"></i> Datas</li>
            </ol>
        </nav>
    </div>
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
    <!-- Main Content -->
    <div class="main-container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="dashboard-title">
                <i class="fas fa-database"></i>
                <span>Gestión de Datas del Sistema</span>
            </div>
            <p class="dashboard-subtitle">
                Seleccione el tipo de data que desea gestionar y consultar. 
                Acceda a toda la información de referenciación y descarga del sistema.
            </p>
        </div>
        
        <!-- Grid de 2 columnas -->
        <div class="dashboard-grid">
            <!-- Data Referidos -->
            <a href="data_referidos.php" class="data-option data-referidos">
                <div class="data-icon-wrapper">
                    <div class="data-icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="data-title">DATA REFERIDOS</div>
                <div class="data-description">
                    Gestión completa de todos los referidos registrados en el sistema. 
                    Consulta, edición y administración de información de referenciación.
                </div>
                
                <!-- Barra de progreso para Data Referidos -->
                <div class="progress-section">
                    <div class="progress-info">
                        <span class="progress-label">Avance de Referidos</span>
                        <span class="progress-percentage"><?php echo $porcentajeAvance; ?>%</span>
                    </div>
                    <div class="progress-container">
                        <div class="progress-bar" style="width: <?php echo $porcentajeAvance; ?>%"></div>
                    </div>
                    <div class="progress-stats">
                        <span class="progress-current"><?php echo number_format($totalReferidos, 0, ',', '.'); ?> referidos activos</span>
                        <span class="progress-target">Meta: <?php echo number_format($sumaTopes, 0, ',', '.'); ?></span>
                    </div>
                    <div class="stats-note">
                        <i class="fas fa-info-circle"></i> Solo se cuentan referidos y usuarios activos
                    </div>
                </div>
            </a>
            
            <!-- Data Descargadores -->
            <a href="data_descargadores.php" class="data-option data-descargadores">
                <div class="data-icon-wrapper">
                    <div class="data-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
                <div class="data-title">DATA DESCARGADORES</div>
                <div class="data-description">
                    Información detallada de usuarios con rol Descargador. 
                    Gestión de permisos y acceso a datos de descarga.
                </div>
                <div class="data-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo number_format($totalDescargadores, 0, ',', '.'); ?></span>
                        <span class="stat-label">Descargadores Activos</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo number_format($totalReferenciadores, 0, ',', '.'); ?></span>
                        <span class="stat-label">Referenciadores Activos</span>
                    </div>
                </div>
                <div class="stats-note">
                    <i class="fas fa-info-circle"></i> Solo se cuentan usuarios activos
                </div>
            </a>
        </div>
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
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Efecto hover mejorado
            $('.data-option').hover(
                function() {
                    $(this).css('transform', 'translateY(-8px)');
                },
                function() {
                    $(this).css('transform', 'translateY(0)');
                }
            );
            
            // Breadcrumb navigation
            $('.breadcrumb a').click(function(e) {
                if ($(this).attr('href') === '#') {
                    e.preventDefault();
                }
            });
        });
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
    </script>
    <script src="../js/modal-sistema.js"></script>
    <script src="../js/contador.js"></script>
</body>
</html>