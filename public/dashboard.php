<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../models/SistemaModel.php';
require_once __DIR__ . '/../models/LiderModel.php';

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
// Verificar si el usuario está logueado y es SuperAdmin
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'Administrador') {
    header('location: index.php');
    exit();
}
$pdo = Database::getConnection();
$model = new UsuarioModel($pdo);
$sistemaModel = new SistemaModel($pdo);
$liderModel = new LiderModel($pdo);

$id_usuario_logueado = $_SESSION['id_usuario'];

// 1. Capturar la fecha actual
$fecha_actual = date('Y-m-d H:i:s');

// 2. Actualizar último registro usando el modelo
$model->actualizarUltimoRegistro($id_usuario_logueado, $fecha_actual);

// 3. Obtener datos del usuario logueado usando el modelo
$usuario_logueado = $model->getUsuarioById($id_usuario_logueado);

// 4. Obtener todos los usuarios usando el modelo
$usuarios = $model->getAllUsuarios();

// 5. Obtener estadísticas usando el modelo
$total_usuarios = $model->countUsuarios();
$usuarios_activos = $model->countUsuariosActivos();
$administradores = $model->countAdministradores();
$referenciadores = $model->countReferenciadores();
$descargadores = $model->countDescargadores();
$superadmin = $model->countSuperAdmin();
$tracking = $model->countTracking();
$lideres = $liderModel->countLideres();

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
    <title>Panel Administrador - SGP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
:root {
    --primary-color: #2c3e50;
    --secondary-color: #3498db;
    --success-color: #27ae60;
    --warning-color: #f39c12;
    --danger-color: #e74c3c;
    --light-color: #ecf0f1;
    --dark-color: #2c3e50;
}

body {
    background-color: #f8f9fa;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin: 0;
    padding: 0;
}

.dashboard-container {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

/* Header Styles - CORREGIDO */
.main-header {
    background: linear-gradient(135deg, var(--primary-color) 0%, #34495e 100%);
    color: white;
    padding: 15px 0;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    position: sticky;
    top: 0;
    z-index: 1000;
    /* QUITAMOS height: 80px; */
}

.header-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px;
}

.header-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    /* QUITAMOS height: 100%; */
}

.header-title {
    display: flex;
    align-items: center;
    gap: 20px;
}

.header-title h1 {
    margin: 0;
    font-size: 1.8rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}

.header-title h1 i {
    color: var(--secondary-color);
}

.user-info {
    display: flex;
    align-items: center;
    gap: 10px;
    background: rgba(255,255,255,0.1);
    padding: 8px 15px;
    border-radius: 20px;
}

.user-info i {
    font-size: 1.5rem;
    color: var(--secondary-color);
}

.user-info span {
    font-weight: 500;
}

.logout-btn {
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.3);
    color: white;
    padding: 8px 15px;
    border-radius: 5px;
    display: flex;
    align-items: center;
    gap: 5px;
    text-decoration: none;
    transition: all 0.3s;
    font-size: 0.9rem;
}

.logout-btn:hover {
    background: rgba(255,255,255,0.2);
    color: white;
}

/* Sidebar Styles - CORREGIDO */
.sidebar {
    background: white;
    width: 280px;
    min-height: calc(100vh - 80px);
    position: fixed;
    left: 0;
    top: 80px; /* Ajustado para que quede justo debajo del header */
    box-shadow: 2px 0 10px rgba(0,0,0,0.1);
    z-index: 999;
    overflow-y: auto;
    padding: 15px 0 20px 0; /* REDUCIDO de 25px a 15px arriba */
}

.sidebar-header {
    padding: 0 20px 20px; /* REDUCIDO de 25px a 20px abajo */
    border-bottom: 1px solid #eee;
    margin-bottom: 20px;
}

.sidebar-header h3 {
    color: var(--primary-color);
    font-size: 1.3rem;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 5px 0; /* REDUCIDO de 8px a 5px */
}

.sidebar-header h3 i {
    color: var(--secondary-color);
}

.nav-menu {
    list-style: none;
    padding: 0;
    margin: 0;
}

.nav-item {
    margin-bottom: 5px;
}

.nav-link {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    color: var(--dark-color);
    text-decoration: none;
    transition: all 0.3s;
    border-left: 4px solid transparent;
}

.nav-link:hover {
    background-color: #f8f9fa;
    color: var(--secondary-color);
    border-left-color: var(--secondary-color);
}

.nav-link.active {
    background-color: #e3f2fd;
    color: var(--secondary-color);
    border-left-color: var(--secondary-color);
    font-weight: 600;
}

.nav-link i {
    width: 24px;
    margin-right: 12px;
    font-size: 1.2rem;
}

.nav-link .badge {
    margin-left: auto;
    background-color: var(--secondary-color);
    font-size: 0.7rem;
    padding: 3px 6px;
}

/* Main Content - CORREGIDO */
.main-content {
    flex: 1;
    margin-left: 280px;
    padding: 30px;
    background-color: #f8f9fa;
    min-height: calc(100vh - 80px);
    /* QUITAMOS margin-top: 80px; */
}

.dashboard-header {
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    margin-bottom: 30px;
}

.dashboard-title {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
}

.dashboard-title i {
    font-size: 2rem;
    color: var(--secondary-color);
}

.dashboard-title h2 {
    margin: 0;
    color: var(--primary-color);
    font-weight: 600;
}

.dashboard-title p {
    margin: 5px 0 0 0;
    color: #666;
    font-size: 0.95rem;
}

/* Stats Cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: transform 0.3s;
    border-top: 4px solid;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.stat-card.users {
    border-top-color: var(--secondary-color);
}

.stat-card.lideres {
    border-top-color: var(--success-color);
}

.stat-card.referenciadores {
    border-top-color: var(--warning-color);
}

.stat-card.activos {
    border-top-color: var(--danger-color);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
    font-size: 1.5rem;
}

.stat-card.users .stat-icon {
    background-color: rgba(52, 152, 219, 0.1);
    color: var(--secondary-color);
}

.stat-card.lideres .stat-icon {
    background-color: rgba(39, 174, 96, 0.1);
    color: var(--success-color);
}

.stat-card.referenciadores .stat-icon {
    background-color: rgba(243, 156, 18, 0.1);
    color: var(--warning-color);
}

.stat-card.activos .stat-icon {
    background-color: rgba(231, 76, 60, 0.1);
    color: var(--danger-color);
}

.stat-content h3 {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 10px 0;
    color: var(--primary-color);
}

.stat-content p {
    color: #666;
    margin: 0;
    font-weight: 500;
}

/* Quick Actions */
.quick-actions {
    background: white;
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    margin-bottom: 30px;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
}

.section-title h3 {
    margin: 0;
    color: var(--primary-color);
    font-weight: 600;
}

.section-title i {
    color: var(--secondary-color);
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 25px 20px;
    background: #f8f9fa;
    border-radius: 10px;
    text-decoration: none;
    color: var(--primary-color);
    transition: all 0.3s;
    border: 2px solid transparent;
}

.action-btn:hover {
    background: white;
    border-color: var(--secondary-color);
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.action-icon {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 15px;
    font-size: 1.8rem;
    background: linear-gradient(135deg, var(--secondary-color) 0%, #2980b9 100%);
    color: white;
}

.action-btn.users .action-icon {
    background: linear-gradient(135deg, var(--secondary-color) 0%, #2980b9 100%);
}

.action-btn.add-user .action-icon {
    background: linear-gradient(135deg, var(--success-color) 0%, #229954 100%);
}

.action-btn.add-lider .action-icon {
    background: linear-gradient(135deg, var(--warning-color) 0%, #d68910 100%);
}

.action-btn.manage-lideres .action-icon {
    background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
}

.action-btn.parametrizacion .action-icon {
    background: linear-gradient(135deg, #1abc9c 0%, #16a085 100%);
}

.action-btn h4 {
    margin: 0 0 5px 0;
    font-size: 1.1rem;
    font-weight: 600;
}

.action-btn p {
    margin: 0;
    color: #666;
    font-size: 0.9rem;
    text-align: center;
}

/* Recent Activity */
.recent-activity {
    background: white;
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.activity-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.activity-item {
    display: flex;
    align-items: center;
    padding: 15px;
    border-bottom: 1px solid #f0f0f0;
    transition: background-color 0.3s;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-item:hover {
    background-color: #f8f9fa;
}

.activity-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    color: white;
    font-size: 1rem;
}

.activity-icon.user-add {
    background-color: var(--success-color);
}

.activity-icon.user-edit {
    background-color: var(--secondary-color);
}

.activity-icon.lider-add {
    background-color: var(--warning-color);
}

.activity-icon.system {
    background-color: #9b59b6;
}

.activity-content {
    flex: 1;
}

.activity-content h5 {
    margin: 0 0 5px 0;
    font-size: 0.95rem;
    color: var(--primary-color);
}

.activity-content p {
    margin: 0;
    color: #666;
    font-size: 0.85rem;
}

.activity-time {
    color: #999;
    font-size: 0.8rem;
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
    background: linear-gradient(135deg, #2c3e50, #1a252f);
    color: white;
}

.modal-system-info .modal-body {
    padding: 30px;
}

/* Logo centrado en el modal - IMAGEN AGRANDADA */
.modal-logo-container {
    text-align: center;
    margin-bottom: 20px;
    padding: 15px;
}

.modal-logo {
    max-width: 300px; /* AGRANDADO de 200px a 300px */
    height: auto;
    margin: 0 auto;
    border-radius: 12px; /* Bordes más redondeados */
    box-shadow: 0 6px 20px rgba(0,0,0,0.15); /* Sombra más pronunciada */
    border: 3px solid #fff; /* Borde blanco */
    background: white;
}
.modal-logo:hover {
    transform: scale(1.05);
}
/* Contenedor para centrar la imagen */
.feature-image-container {
    text-align: center;
    margin-bottom: 2rem; /* Espacio antes de las tarjetas */
}

/* Estilos de la imagen redonda */
.feature-img-header {
    width: 190px;
    height: 190px;
    object-fit: cover;       /* Asegura que no se deforme */
    border-radius: 50%;      /* Círculo perfecto */
    border: 4px solid #ffffff; 
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1); /* Sombra elegante */
    transition: transform 0.3s ease; /* Para el efecto de hover */
}

/* Efecto opcional al pasar el mouse */
.feature-img-header:hover {
    transform: scale(1.05);
}

/* Barra de progreso de licencia */
.licencia-info {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 20px;
    border: 1px solid #dee2e6;
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
    color: #2c3e50;
    margin: 0;
}

.licencia-dias {
    font-size: 1rem;
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 20px;
    background: #3498db;
    color: white;
}

.licencia-progress {
    height: 12px;
    border-radius: 6px;
    margin-bottom: 8px;
    background-color: #e9ecef;
    overflow: hidden;
}

.licencia-progress-bar {
    height: 100%;
    border-radius: 6px;
    transition: width 0.6s ease;
}

.licencia-fecha {
    font-size: 0.85rem;
    color: #6c757d;
    text-align: center;
    margin-top: 5px;
}

/* Tarjetas de características */
.feature-card {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 20px;
    height: 100%;
    border-left: 4px solid #3498db;
    transition: transform 0.3s ease;
}

.feature-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.feature-icon {
    opacity: 0.8;
}

.feature-title {
    color: #2c3e50;
    font-weight: 600;
    margin-bottom: 5px;
}

.feature-text {
    font-size: 14px;
    color: #555;
    line-height: 1.5;
    margin-bottom: 0;
}

/* Footer del modal */
.system-footer-modal {
    background: #f1f5f9;
    border-radius: 8px;
    padding: 20px;
    margin-top: 30px;
    border-top: 2px solid #e2e8f0;
}

.logo-clickable {
    cursor: pointer;
    transition: transform 0.3s ease;
}

.logo-clickable:hover {
    transform: scale(1.05);
}

/* Modal de Sistema */
.modal-system-info .modal-logo-container {
    text-align: center;
    margin-bottom: 20px;
}

.modal-system-info .modal-logo {
    max-width: 350px;
}

.licencia-info {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.licencia-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.licencia-title {
    margin: 0;
    color: var(--primary-color);
    font-weight: 600;
}

.licencia-dias {
    background: var(--secondary-color);
    color: white;
    padding: 5px 15px;
    border-radius: 20px;
    font-weight: 600;
}

.licencia-progress {
    height: 10px;
    background: #e0e0e0;
    border-radius: 5px;
    overflow: hidden;
    margin-bottom: 10px;
}

.licencia-progress-bar {
    height: 100%;
    border-radius: 5px;
}

.licencia-fecha {
    color: #666;
    font-size: 0.9rem;
}

.feature-card {
    text-align: center;
    padding: 20px;
    border-radius: 10px;
    background: #f8f9fa;
    height: 100%;
}

.feature-icon {
    margin-bottom: 15px;
}

.feature-title {
    color: var(--primary-color);
    font-weight: 600;
    margin-bottom: 10px;
}

.feature-text {
    color: #666;
    font-size: 0.9rem;
    line-height: 1.5;
}

.profile-name {
    color: var(--primary-color);
    text-align: center;
    margin-bottom: 10px;
}

.profile-description {
    color: #666;
    text-align: center;
    line-height: 1.6;
}

.cio-tag {
    display: inline-block;
    background: var(--secondary-color);
    color: white;
    padding: 3px 10px;
    border-radius: 15px;
    font-size: 0.8rem;
    margin-top: 10px;
}

/* Responsive Design */
@media (max-width: 992px) {
    .sidebar {
        width: 100%;
        position: relative;
        top: 0;
        min-height: auto;
        margin-bottom: 20px;
        padding: 15px 0; /* Ajustado */
    }
    
    .main-content {
        margin-left: 0;
        margin-top: 0; /* Quitado */
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .header-top {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
    
    .header-title {
        flex-direction: column;
        gap: 10px;
    }
    
    .user-info {
        margin-left: 0;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .actions-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 576px) {
    .actions-grid {
        grid-template-columns: 1fr;
    }
    
    .main-content {
        padding: 15px;
    }
    
    .sidebar {
        padding: 10px 0; /* Reducido para móviles */
    }
    
    .sidebar-header {
        padding: 0 15px 15px;
    }
}
.stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-color);
            line-height: 1;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="header-container">
            <div class="header-top">
                <div class="header-title">
                    <h1><i class="fas fa-cogs"></i> Panel de Administración - SGP</h1>
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span>Administrador del Sistema</span>
                    </div>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
        </div>
    </header>

    <!-- Dashboard Container -->
    <div class="dashboard-container">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-bars"></i> Menú Principal</h3>
            </div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <!-- ENLACE ACTUALIZADO para Gestión de Usuarios -->
                    <a href="gestion_usuarios.php" class="nav-link">
                        <i class="fas fa-users-cog"></i> Gestión de Usuarios
                        <div class="badge"><?php echo $usuarios_activos; ?></div>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="agregar_usuario.php" class="nav-link">
                        <i class="fas fa-user-plus"></i> Agregar Usuario
                    </a>
                </li>
                <li class="nav-item">
                    <a href="administrador/anadir_lider.php" class="nav-link">
                        <i class="fas fa-user-tie"></i> Agregar Líder
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fas fa-people-carry"></i> Gestión de Líderes
                        <div class="badge"><?php echo $lideres; ?></div>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fas fa-sliders-h"></i> Parametrización
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Dashboard Header -->
            <div class="dashboard-header">
                <div class="dashboard-title">
                    <i class="fas fa-tachometer-alt"></i>
                    <div>
                        <h2>Bienvenido, <span class="detail-value">
                        <?php 
                        $nombre_completo = '';
                        if (!empty($usuario_logueado['nombres']) && !empty($usuario_logueado['apellidos'])) {
                            $nombre_completo = htmlspecialchars($usuario_logueado['nombres'] . ' ' . $usuario_logueado['apellidos']);
                        } else {
                            $nombre_completo = htmlspecialchars($usuario_logueado['nickname']);
                        }
                        echo $nombre_completo;
                        ?>
                    </span></h2>
                        <p>Panel de control del sistema de gestión política</p>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card users">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $total_usuarios; ?></div>
                        <div class="stat-label">Total Usuarios</div>
                    </div>
                </div>
                
                <div class="stat-card lideres">
                    <div class="stat-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $lideres; ?></div>
                        <div class="stat-label">Lideres</div>
                    </div>
                </div>
                
                <div class="stat-card referenciadores">
                    <div class="stat-icon">
                        <i class="fas fa-user-friends"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $referenciadores; ?></div>
                        <div class="stat-label">Referenciadores</div>
                    </div>
                </div>
                
                <div class="stat-card activos">
                    <div class="stat-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $usuarios_activos; ?></div>
                        <div class="stat-label">Usuarios Activos</div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <div class="section-title">
                    <i class="fas fa-bolt"></i>
                    <h3>Acciones Rápidas</h3>
                </div>
                <div class="actions-grid">
                    <!-- ENLACE ACTUALIZADO para Gestión de Usuarios en Acciones Rápidas -->
                    <a href="gestion_usuarios.php" class="action-btn users">
                        <div class="action-icon">
                            <i class="fas fa-users-cog"></i>
                        </div>
                        <h4>Gestión de Usuarios</h4>
                        <p>Administrar todos los usuarios del sistema</p>
                    </a>
                    
                    <a href="agregar_usuario.php" class="action-btn add-user">
                        <div class="action-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <h4>Agregar Usuario</h4>
                        <p>Crear nuevo usuario en el sistema</p>
                    </a>
                    
                    <a href="administrador/anadir_lider.php" class="action-btn add-lider">
                        <div class="action-icon">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <h4>Agregar Líder</h4>
                        <p>Registrar nuevo líder político</p>
                    </a>
                    
                    <a href="gestion_lideres.php" class="action-btn manage-lideres">
                        <div class="action-icon">
                            <i class="fas fa-people-carry"></i>
                        </div>
                        <h4>Gestión de Líderes</h4>
                        <p>Administrar líderes registrados</p>
                    </a>
                    
                    <a href="#" class="action-btn parametrizacion">
                        <div class="action-icon">
                            <i class="fas fa-sliders-h"></i>
                        </div>
                        <h4>Parametrización</h4>
                        <p>Configurar parámetros del sistema</p>
                    </a>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="recent-activity">
                <div class="section-title">
                    <i class="fas fa-history"></i>
                    <h3>Actividad Reciente</h3>
                </div>
                <ul class="activity-list">
                    <li class="activity-item">
                        <div class="activity-icon user-add">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div class="activity-content">
                            <h5>Nuevo usuario registrado</h5>
                            <p>Juan Pérez se registró como Referenciador</p>
                        </div>
                        <div class="activity-time">Hace 2 horas</div>
                    </li>
                    <li class="activity-item">
                        <div class="activity-icon user-edit">
                            <i class="fas fa-user-edit"></i>
                        </div>
                        <div class="activity-content">
                            <h5>Usuario actualizado</h5>
                            <p>María García actualizó su información</p>
                        </div>
                        <div class="activity-time">Hace 4 horas</div>
                    </li>
                    <li class="activity-item">
                        <div class="activity-icon lider-add">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="activity-content">
                            <h5>Nuevo líder agregado</h5>
                            <p>Carlos Rodríguez registrado como Líder</p>
                        </div>
                        <div class="activity-time">Ayer, 14:30</div>
                    </li>
                </ul>
            </div>
        </main>
    </div>

    <!-- Footer -->
    <footer class="system-footer">
        <div class="container text-center mb-3">
        <img id="footer-logo" 
            src="imagenes/Logo-artguru.png" 
            alt="Logo ARTGURU" 
            class="logo-clickable"
            onclick="mostrarModalSistema()"
            title="Haz clic para ver información del sistema"
            data-img-claro="imagenes/Logo-artguru.png"
            data-img-oscuro="imagenes/image_no_bg.png">
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
    <script>
// Función para mostrar el modal del sistema
function mostrarModalSistema() {
    var modal = new bootstrap.Modal(document.getElementById('modalSistema'));
    modal.show();
}

// Función para cerrar el modal del sistema
function cerrarModalSistema() {
    var modal = bootstrap.Modal.getInstance(document.getElementById('modalSistema'));
    if (modal) {
        modal.hide();
    }
}

// Activar enlaces del sidebar (SOLO los placeholders)
document.addEventListener('DOMContentLoaded', function() {
    // Solo manejar enlaces que son placeholders (#)
    const placeholderLinks = document.querySelectorAll('.nav-link[href="#"]');
    
    placeholderLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remover clase activa de todos los enlaces
            document.querySelectorAll('.nav-link').forEach(item => item.classList.remove('active'));
            
            // Agregar clase activa al enlace clickeado
            this.classList.add('active');
            
            // Mostrar mensaje de funcionalidad
            const text = this.querySelector('h4') ? this.querySelector('h4').textContent : this.textContent;
            showNotification(`Redirigiendo a: ${text}`, 'info');
        });
    });
    
    // Agregar interactividad a las tarjetas de estadísticas
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
        card.addEventListener('click', function() {
            const title = this.querySelector('p').textContent;
            showNotification(`Estadística: ${title}`, 'info');
        });
    });
});

// Mostrar notificaciones
function showNotification(message, type = 'info') {
    // Crear elemento de notificación
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} position-fixed`;
    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        max-width: 500px;
        animation: slideIn 0.3s ease;
    `;
    
    notification.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
            <span>${message}</span>
            <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Remover notificación después de 3 segundos
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 3000);
}

// Animación para notificaciones
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
`;
document.head.appendChild(style);
    </script>
</body>
</html>