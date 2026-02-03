<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../models/SistemaModel.php';

// Verificar si el usuario está logueado y es SuperAdmin
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    header('Location: index.php');
    exit();
}

$pdo = Database::getConnection();
$usuarioModel = new UsuarioModel($pdo);
$sistemaModel = new SistemaModel($pdo);

// Obtener datos del usuario logueado
$usuario_logueado = $usuarioModel->getUsuarioById($_SESSION['id_usuario']);

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
    <title>Panel Super Admin - SGP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Mismo estilo que la vista del referenciador */
       /* ESTILOS ESPECÍFICOS PARA VISTA DASHBOARD CON DETECCIÓN DE TEMA */
:root {
    /* Variables modo claro (por defecto) */
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
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 10px;
    background: rgba(255,255,255,0.1);
    padding: 5px 10px;
    border-radius: 20px;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.logout-btn:hover {
    background: rgba(255,255,255,0.2);
    color: white;
    transform: translateY(-1px);
}

/* Contenedor para centrar la imagen */
.feature-image-container {
    text-align: center;
    margin-bottom: 2rem;
}

/* Estilos de la imagen redonda */
.feature-img-header {
    width: 190px;
    height: 190px;
    object-fit: cover;
    border-radius: 50%;
    border: 4px solid var(--color-fondo-secundario);
    box-shadow: 0 8px 15px var(--color-sombra-fuerte);
    transition: transform 0.3s ease;
    background-color: var(--color-fondo-secundario);
}

/* Efecto opcional al pasar el mouse */
.feature-img-header:hover {
    transform: scale(1.05);
}

/* Main Container - CENTRADO Y MÁS AMPLIO */
.main-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 15px 30px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

/* Dashboard Header con título destacado */
.dashboard-header {
    text-align: center;
    margin: 30px 0 40px;
    padding: 0 20px;
}

.dashboard-title {
    font-size: 2.2rem;
    font-weight: 700;
    color: var(--color-secundario);
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.dashboard-subtitle {
    font-size: 1.1rem;
    color: var(--color-texto-secundario);
    max-width: 600px;
    margin: 0 auto;
    line-height: 1.5;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Grid de 2 columnas para los botones - MANTENIDO COMO ORIGINAL */
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 30px;
    max-width: 1000px;
    margin: 0 auto;
    width: 100%;
}

/* Botones estilo tarjeta mejorados */
.dashboard-option {
    background: var(--color-fondo-secundario);
    border-radius: 12px;
    padding: 35px 25px;
    text-align: center;
    text-decoration: none;
    color: var(--color-secundario);
    transition: all 0.3s ease;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 15px var(--color-sombra);
    border: 1px solid var(--color-borde);
    position: relative;
    overflow: hidden;
}

.dashboard-option::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--color-primario), var(--color-terciario));
}

.dashboard-option:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 25px var(--color-sombra-fuerte);
    border-color: var(--color-primario);
    text-decoration: none;
    color: var(--color-secundario);
}

.option-icon-wrapper {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, var(--color-borde-secundario), #bbdefb);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
    transition: all 0.3s ease;
}

.dashboard-option:hover .option-icon-wrapper {
    background: linear-gradient(135deg, var(--color-primario), #2980b9);
    transform: scale(1.1);
}

.option-icon {
    font-size: 2.2rem;
    color: var(--color-primario);
    transition: all 0.3s ease;
}

.dashboard-option:hover .option-icon {
    color: white;
}

.option-title {
    font-size: 1.4rem;
    font-weight: 700;
    margin-bottom: 12px;
    color: var(--color-secundario);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.option-description {
    font-size: 0.95rem;
    color: var(--color-texto-secundario);
    line-height: 1.5;
    max-width: 90%;
    margin: 0 auto;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Indicador de acceso */
.access-indicator {
    position: absolute;
    top: 15px;
    right: 15px;
    background: var(--color-badge);
    border-radius: 20px;
    padding: 4px 10px;
    font-size: 0.75rem;
    color: var(--color-texto-secundario);
    font-weight: 500;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    transition: all 0.3s ease;
}

.dashboard-option:hover .access-indicator {
    background: var(--color-secundario);
    color: white;
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

.system-footer p {
    margin: 8px 0;
    color: var(--color-texto);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Estilos para el logo en el footer */
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

/* Estilos para el modal de información del sistema */
.modal-system-info .modal-header {
    background: var(--color-header);
    color: white;
    border-bottom: 1px solid var(--color-borde);
}

.modal-system-info .modal-body {
    padding: 20px;
    background-color: var(--color-fondo-secundario);
    color: var(--color-texto);
}

.modal-system-info .modal-content {
    background-color: var(--color-fondo-secundario);
    border: 1px solid var(--color-borde);
}

.modal-system-info .modal-footer {
    border-top: 1px solid var(--color-borde);
    background-color: var(--color-fondo);
}

.modal-system-info .btn-close {
    filter: invert(1) grayscale(100%) brightness(200%);
}

/* Logo centrado en el modal */
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
    box-shadow: 0 6px 20px var(--color-sombra-fuerte);
    border: 3px solid var(--color-borde);
    background: var(--color-fondo-secundario);
}

/* Barra de progreso de licencia */
.licencia-info {
    background: linear-gradient(135deg, var(--color-fondo), var(--color-borde));
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 20px;
    border: 1px solid var(--color-borde);
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
    color: var(--color-texto);
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.licencia-dias {
    font-size: 1rem;
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 20px;
    background: var(--color-primario);
    color: white;
}

.licencia-progress {
    height: 12px;
    border-radius: 6px;
    margin-bottom: 8px;
    background-color: var(--color-borde);
    overflow: hidden;
}

.licencia-progress-bar {
    height: 100%;
    border-radius: 6px;
    transition: width 0.6s ease;
}

.licencia-fecha {
    font-size: 0.85rem;
    color: var(--color-texto-secundario);
    text-align: center;
    margin-top: 5px;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Tarjetas de características */
.feature-card {
    background: var(--color-fondo);
    border-radius: 10px;
    padding: 20px;
    height: 100%;
    border-left: 4px solid var(--color-primario);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    color: var(--color-texto);
}

.feature-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px var(--color-sombra);
}

.feature-icon {
    opacity: 0.8;
}

.feature-title {
    color: var(--color-texto);
    font-weight: 600;
    margin-bottom: 5px;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.feature-text {
    font-size: 14px;
    color: var(--color-texto-secundario);
    line-height: 1.5;
    margin-bottom: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Footer del modal */
.system-footer-modal {
    background: var(--color-fondo);
    border-radius: 8px;
    padding: 20px;
    margin-top: 30px;
    border-top: 2px solid var(--color-borde);
}

.logo-clickable {
    cursor: pointer;
    transition: transform 0.3s ease;
}

.logo-clickable:hover {
    transform: scale(1.05);
}

/* =========================================== */
/* CONTADOR COMPACTO - COLORES FIJOS (NO CAMBIAN) */
/* =========================================== */
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
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.countdown-compact-date i {
    color: #f1c40f;
}

/* =========================================== */
/* RESPONSIVE DESIGN */
/* =========================================== */

/* Tablets y laptops pequeñas (992px o menos) */
@media (max-width: 992px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
        max-width: 600px;
        gap: 25px;
    }
    
    .feature-img-header {
        width: 140px;
        height: 140px;
    }
    
    .dashboard-option {
        padding: 30px 20px;
    }
    
    .dashboard-title {
        font-size: 1.8rem;
    }
    
    .modal-logo {
        max-width: 250px;
    }
}

/* Tablets (767px o menos) */
@media (max-width: 767px) {
    .header-top {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .user-info {
        order: 1;
        width: 100%;
        justify-content: center;
        margin-top: 5px;
    }
    
    .logout-btn {
        order: 2;
        align-self: flex-end;
    }
    
    .dashboard-header {
        margin: 20px 0 30px;
        padding: 0 15px;
    }
    
    .dashboard-title {
        font-size: 1.6rem;
        flex-direction: column;
        gap: 10px;
        text-align: center;
    }
    
    .dashboard-subtitle {
        font-size: 1rem;
        padding: 0 10px;
    }
    
    .feature-img-header {
        width: 140px;
        height: 140px;
    }
    
    .option-icon-wrapper {
        width: 60px;
        height: 60px;
    }
    
    .option-icon {
        font-size: 1.8rem;
    }
    
    .option-title {
        font-size: 1.2rem;
    }
    
    .system-footer {
        padding: 20px 15px;
        font-size: 0.85rem;
    }
    
    .container.text-center.mb-3 img {
        max-width: 220px;
    }
    
    /* Modal responsive */
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
    
    /* Contador compacto responsive - MANTENIENDO COLORES FIJOS */
    .countdown-compact {
        flex-direction: column;
        gap: 10px;
        text-align: center;
        padding: 15px;
        background: linear-gradient(135deg, #2c3e50, #3498db); /* COLOR FIJO */
    }
    
    .countdown-compact-timer {
        order: 2;
        width: 100%;
        font-size: 1.3rem;
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
        text-align: center;
    }
}

/* Teléfonos grandes (480px o menos) */
@media (max-width: 480px) {
    .header-title h1 {
        font-size: 1rem;
        max-width: 200px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .dashboard-option {
        padding: 25px 15px;
        border-radius: 10px;
    }
    
    .feature-img-header {
        width: 140px;
        height: 140px;
    }
    
    .option-icon-wrapper {
        width: 55px;
        height: 55px;
        margin-bottom: 15px;
    }
    
    .option-icon {
        font-size: 1.6rem;
    }
    
    .option-title {
        font-size: 1.1rem;
    }
    
    .option-description {
        font-size: 0.9rem;
    }
    
    .container.text-center.mb-3 img {
        max-width: 200px;
    }
    
    /* Modal responsive */
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
    
    .feature-img-header {
        width: 120px;
        height: 120px;
    }
    
    /* Contador compacto - MANTENIENDO COLORES FIJOS */
    .countdown-compact-timer {
        font-size: 1.3rem;
    }
    
    .countdown-compact-title span {
        font-size: 0.9rem;
    }
    
    .countdown-compact-date {
        font-size: 0.8rem;
    }
}

/* Teléfonos muy pequeños (400px o menos) */
@media (max-width: 400px) {
    .dashboard-option {
        padding: 25px 15px;
        margin: 0 10px;
    }
    
    .feature-img-header {
        width: 140px;
        height: 140px;
    }
    
    .option-icon-wrapper {
        width: 50px;
        height: 50px;
    }
    
    .option-icon {
        font-size: 1.5rem;
    }
    
    .option-title {
        font-size: 1.1rem;
    }
    
    .option-description {
        font-size: 0.85rem;
    }
    
    .container.text-center.mb-3 img {
        max-width: 180px;
    }
    
    .modal-logo {
        max-width: 150px;
    }
    
    .feature-img-header {
        width: 100px;
        height: 100px;
    }
}

/* Orientación horizontal en móviles */
@media (max-height: 500px) and (orientation: landscape) {
    .dashboard-grid {
        grid-template-columns: repeat(2, 1fr);
        max-width: 800px;
        gap: 20px;
    }
    
    .dashboard-option {
        padding: 25px 20px;
        min-height: 200px;
    }
    
    .option-icon-wrapper {
        width: 50px;
        height: 50px;
        margin-bottom: 15px;
    }
    
    .option-icon {
        font-size: 1.8rem;
    }
    
    .option-title {
        font-size: 1.2rem;
        margin-bottom: 10px;
    }
    
    .option-description {
        font-size: 0.85rem;
    }
}

/* Pantallas muy grandes (1400px o más) */
@media (min-width: 1400px) {
    .header-container,
    .main-container,
    .countdown-compact-container {
        max-width: 1400px;
    }
}

/* Ajustes para alta resolución (retina) */
@media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
    .dashboard-option {
        border-width: 1.5px;
    }
    
    .feature-card {
        border-left-width: 3px;
    }
    
    .countdown-compact {
        border-width: 0.5px;
    }
}

/* Ajustes específicos para modo oscuro (el contador NO cambia) */
@media (prefers-color-scheme: dark) {
    .container.text-center.mb-3 img {
        filter: brightness(0.9);
    }
    
    .container.text-center.mb-3 img:hover {
        filter: brightness(1.1);
    }
    
    .modal-system-info .btn-close {
        filter: brightness(0.8);
    }
    
    .feature-img-header {
        border-color: var(--color-borde);
    }
    
    .option-icon-wrapper {
        background: linear-gradient(135deg, var(--color-borde), #2d3748);
    }
    
    .dashboard-option:hover .option-icon-wrapper {
        background: linear-gradient(135deg, var(--color-primario), #1e40af);
    }
}

/* Ajustes para reducir movimiento (preferencias de accesibilidad) */
@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
    
    .dashboard-option:hover {
        transform: translateY(-3px);
    }
    
    .dashboard-option:hover .option-icon-wrapper {
        transform: scale(1.05);
    }
    
    .feature-img-header:hover {
        transform: scale(1.02);
    }
    
    .logo-clickable:hover {
        transform: scale(1.02);
    }
}

/* Ajustes para contraste alto */
@media (prefers-contrast: high) {
    .dashboard-option {
        border: 2px solid var(--color-borde);
    }
    
    .dashboard-option::before {
        height: 5px;
    }
    
    .feature-card {
        border-left: 5px solid var(--color-primario);
    }
    
    .countdown-compact {
        border: 2px solid rgba(255,255,255,0.2);
    }
}

/* Ajustes para impresión */
@media print {
    .main-header,
    .logout-btn,
    .access-indicator,
    .system-footer,
    .dashboard-option::before,
    .countdown-compact-container {
        display: none !important;
    }
    
    .dashboard-option {
        box-shadow: none !important;
        border: 1px solid #ccc !important;
        transform: none !important;
        min-height: auto !important;
        padding: 15px !important;
        break-inside: avoid;
    }
    
    body {
        background: white !important;
        color: black !important;
        font-size: 12px !important;
    }
    
    .dashboard-grid {
        grid-template-columns: 1fr !important;
        gap: 15px !important;
    }
}
    </style>
</head>
<body>
    <!-- Header (igual al referenciador) -->
    <header class="main-header">
        <div class="header-container">
            <div class="header-top">
                <div class="header-title">
                    <h1><i class="fas fa-user-shield"></i> Panel Super Admin</h1>
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($usuario_logueado['nombres'] . ' ' . $usuario_logueado['apellidos']); ?></span>
                    </div>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
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
    <!-- Main Content -->
    <div class="main-container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="dashboard-title">
                <i class="fas fa-tachometer-alt"></i>
                <span>Panel de Control</span>
            </div>
            <p class="dashboard-subtitle">
                Acceda a los módulos principales del sistema de gestión política. 
                Controle y supervise todas las operaciones desde un solo lugar.
            </p>
        </div>
        
        <!-- Grid de 2 columnas (mantenido como original) -->
        <div class="dashboard-grid">
            <!-- Monitoreos -->
            <a href="superAdmin/superadmin_monitoreos.php" class="dashboard-option">
                <div class="access-indicator">
                    <i class="fas fa-arrow-right"></i>
                </div>
                <div class="option-icon-wrapper">
                    <div class="option-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
                <div class="option-title">MONITOREOS</div>
                <div class="option-description">
                    Gráficas de avance, estadísticas en tiempo real, 
                    comparativas entre referenciadores y análisis detallados
                </div>
            </a>
            
            <!-- Georeferenciación -->
            <a href="/superAdmin/superadmin_georeferenciacion.php" class="dashboard-option">
                <div class="access-indicator">
                    <i class="fas fa-arrow-right"></i>
                </div>
                <div class="option-icon-wrapper">
                    <div class="option-icon">
                        <i class="fas fa-map-marked-alt"></i>
                    </div>
                </div>
                <div class="option-title">GEOREFERENCIACIÓN</div>
                <div class="option-description">
                    Visualización geográfica de referenciados, 
                    filtros por ubicación y consulta avanzada en mapas interactivos
                </div>
            </a>
            
            <!-- Reportes -->
            <a href="/superAdmin/superadmin_reportes.php" class="dashboard-option">
                <div class="access-indicator">
                    <i class="fas fa-arrow-right"></i>
                </div>
                <div class="option-icon-wrapper">
                    <div class="option-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                </div>
                <div class="option-title">REPORTES</div>
                <div class="option-description">
                    Generación de informes detallados, 
                    exportación en múltiples formatos y análisis estadístico completo
                </div>
            </a>
            
            <!-- Datas -->
            <a href="superAdmin/superadmin_datas.php" class="dashboard-option">
                <div class="access-indicator">
                    <i class="fas fa-arrow-right"></i>
                </div>
                <div class="option-icon-wrapper">
                    <div class="option-icon">
                        <i class="fas fa-database"></i>
                    </div>
                </div>
                <div class="option-title">DATAS</div>
                <div class="option-description">
                    Gestión integral de bases de datos, 
                    administración de referidos y descargadores del sistema
                </div>
            </a>
            
            <!-- NUEVO: COMUNICACIONES -->
            <a href="superAdmin/superadmin_comunicaciones.php" class="dashboard-option">
                <div class="access-indicator">
                    <i class="fas fa-arrow-right"></i>
                </div>
                <div class="option-icon-wrapper">
                    <div class="option-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                </div>
                <div class="option-title">COMUNICACIONES</div>
                <div class="option-description">
                    Gestión de mensajes, notificaciones, 
                    envío de alertas y comunicación con referenciadores y usuarios del sistema
                </div>
            </a>
            
            <!-- NUEVO: AUDITORIA -->
            <a href="superAdmin/superadmin_auditoria.php" class="dashboard-option">
                <div class="access-indicator">
                    <i class="fas fa-arrow-right"></i>
                </div>
                <div class="option-icon-wrapper">
                    <div class="option-icon">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                </div>
                <div class="option-title">AUDITORIA</div>
                <div class="option-description">
                    Registro de actividades del sistema, 
                    trazabilidad de operaciones y control de accesos para seguridad y transparencia
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
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/modal-sistema.js"></script>
    <script src="js/contador.js"></script>
    <script>
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
</body>
</html>