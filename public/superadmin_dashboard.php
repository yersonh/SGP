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
        * {
            box-sizing: border-box;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
            font-size: 14px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Header Styles (igual al referenciador) */
        .main-header {
            background: linear-gradient(135deg, #2c3e50, #1a252f);
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
            color: #3498db;
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
            color: #2c3e50;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .dashboard-subtitle {
            font-size: 1.1rem;
            color: #666;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.5;
        }
        
        /* Grid de 2 columnas para los botones */
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
            background: white;
            border-radius: 12px;
            padding: 35px 25px;
            text-align: center;
            text-decoration: none;
            color: #2c3e50;
            transition: all 0.3s ease;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #eaeaea;
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
            background: linear-gradient(90deg, #3498db, #2ecc71);
        }
        
        .dashboard-option:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.15);
            border-color: #3498db;
            text-decoration: none;
            color: #2c3e50;
        }
        
        .option-icon-wrapper {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .dashboard-option:hover .option-icon-wrapper {
            background: linear-gradient(135deg, #3498db, #2980b9);
            transform: scale(1.1);
        }
        
        .option-icon {
            font-size: 2.2rem;
            color: #3498db;
            transition: all 0.3s ease;
        }
        
        .dashboard-option:hover .option-icon {
            color: white;
        }
        
        .option-title {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 12px;
            color: #2c3e50;
        }
        
        .option-description {
            font-size: 0.95rem;
            color: #666;
            line-height: 1.5;
            max-width: 90%;
            margin: 0 auto;
        }
        
        /* Indicador de acceso */
        .access-indicator {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #f8f9fa;
            border-radius: 20px;
            padding: 4px 10px;
            font-size: 0.75rem;
            color: #666;
            font-weight: 500;
        }
        
        /* Footer (igual al referenciador) */
        .system-footer {
            text-align: center;
            padding: 25px 0;
            background: white;
            color: black;
            font-size: 0.9rem;
            line-height: 1.6;
            border-top: 2px solid #eaeaea;
            width: 100%;
            margin-top: 60px;
        }
        
        .system-footer p {
            margin: 8px 0;
            color: #333;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
                max-width: 600px;
                gap: 25px;
            }
            
            .dashboard-option {
                padding: 30px 20px;
            }
            
            .dashboard-title {
                font-size: 1.8rem;
            }
        }
        
        @media (max-width: 767px) {
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
            
            .dashboard-header {
                margin: 20px 0 30px;
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
        }
        
        @media (max-width: 480px) {
            .dashboard-option {
                padding: 25px 15px;
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
        }
        /* Estilos para el logo en el footer */
        .container.text-center.mb-3 img {
            max-width: 320px;
            height: auto;
            transition: max-width 0.3s ease;
        }

        /* Para dispositivos móviles */
        @media (max-width: 768px) {
            .container.text-center.mb-3 img {
                max-width: 220px;
            }
        }

        /* Para dispositivos muy pequeños */
        @media (max-width: 400px) {
            .container.text-center.mb-3 img {
                max-width: 200px;
            }
        }
         /* Estilos para el modal de información del sistema */
        .modal-system-info .modal-header {
            background: linear-gradient(135deg, #2c3e50, #1a252f);
            color: white;
        }
        
        .modal-system-info .modal-body {
            padding: 20px;
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
        
        /* Responsive */
        @media (max-width: 768px) {
            .modal-system-info .modal-body {
                padding: 15px;
            }
            
            .modal-logo {
                max-width: 200px; /* AGRANDADO para móviles también */
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
        }
        
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
                max-width: 180px; /* AGRANDADO para móviles pequeños */
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

    <!-- Main Content -->
    <div class="main-container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="dashboard-title">
                <i class="fas fa-tachometer-alt"></i>
                <span>Panel de Control Super Admin</span>
            </div>
            <p class="dashboard-subtitle">
                Acceda a los módulos principales del sistema de gestión política. 
                Controle y supervise todas las operaciones desde un solo lugar.
            </p>
        </div>
        
        <!-- Grid de 2 columnas -->
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
            <a href="superadmin_georeferenciacion.php" class="dashboard-option">
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
            <a href="superadmin_reportes.php" class="dashboard-option">
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
        </div>
    </div>

    <!-- Footer -->
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
                    <!-- Logo centrado AGRANDADO -->
                    <div class="modal-logo-container">
                        <img src="imagenes/Logo-artguru.png" alt="Logo del Sistema" class="modal-logo">
                    </div>
                    
                    <!-- Título del Sistema - ELIMINADO "Sistema SGP" -->
                    <div class="text-center mb-4">
                        <!-- ELIMINADO: <h1 class="display-5 fw-bold text-primary mb-2">
                            <?php echo htmlspecialchars($infoSistema['nombre_sistema'] ?? 'Sistema SGP'); ?>
                        </h1> -->
                        <h4 class="text-secondary mb-4">
                            <strong>Gestión Política de Alta Precisión</strong>
                        </h4>
                        
<!-- Información de Licencia (MODIFICADO) -->
<div class="licencia-info">
    <div class="licencia-header">
        <h6 class="licencia-title">Licencia Runtime</h6>
        <span class="licencia-dias">
            <strong><?php echo $diasRestantes; ?> días restantes</strong>
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
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/modal-sistema.js"></script>
</body>
</html>