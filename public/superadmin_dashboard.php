<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../models/ReferenciadoModel.php';

// Verificar si el usuario está logueado y es SuperAdmin
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    header('Location: ../index.php');
    exit();
}

$pdo = Database::getConnection();
$usuarioModel = new UsuarioModel($pdo);
$referenciadoModel = new ReferenciadoModel($pdo);

// Obtener datos del usuario logueado
$usuario_logueado = $usuarioModel->getUsuarioById($_SESSION['id_usuario']);

// Obtener estadísticas reales
// 1. Total de referidos
$referenciados = $referenciadoModel->getAllReferenciados();
$total_referidos = count($referenciados);

// 2. Calcular tope total de usuarios (suma de topes de todos los usuarios activos)
$todos_usuarios = $usuarioModel->getAllUsuarios();
$tope_total = 0;

foreach ($todos_usuarios as $usuario) {
    // Sumar solo usuarios activos y que tengan tope definido
    $activo = $usuario['activo'] ?? true;
    $esta_activo = ($activo === true || $activo === 't' || $activo == 1);
    
    if ($esta_activo && isset($usuario['tope'])) {
        $tope_total += intval($usuario['tope']);
    }
}

// 3. Calcular estadísticas de usuarios por tipo
$estadisticas_usuarios = $usuarioModel->countTodosLosTipos();

// 4. Calcular porcentaje de utilización del tope
$porcentaje_uso = 0;
if ($tope_total > 0) {
    $porcentaje_uso = round(($total_referidos / $tope_total) * 100, 1);
}

// 5. Contar referidos activos vs inactivos
$referidos_activos = 0;
$referidos_inactivos = 0;

foreach ($referenciados as $referenciado) {
    $activo = $referenciado['activo'] ?? true;
    $esta_activo = ($activo === true || $activo === 't' || $activo == 1);
    
    if ($esta_activo) {
        $referidos_activos++;
    } else {
        $referidos_inactivos++;
    }
}

// 6. Obtener conteo por tipo de usuario
$referenciadores = $usuarioModel->countReferenciadores();
$descargadores = $usuarioModel->countDescargadores();
$administradores = $usuarioModel->countAdministradores();
$superadmins = $usuarioModel->countSuperAdmin();
$total_usuarios = $usuarioModel->countUsuarios();
$usuarios_activos = $usuarioModel->countUsuariosActivos();
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
        /* ... (todo el CSS se mantiene igual) ... */
        
        /* Agregar estas clases para el progreso */
        .progress-container {
            width: 100%;
            margin-top: 10px;
        }
        
        .progress-bar-bg {
            background-color: #e9ecef;
            border-radius: 10px;
            height: 10px;
            overflow: hidden;
            margin-bottom: 5px;
        }
        
        .progress-bar-fill {
            height: 100%;
            border-radius: 10px;
            background: linear-gradient(90deg, #3498db, #2980b9);
            transition: width 0.5s ease;
        }
        
        .progress-text {
            font-size: 0.8rem;
            color: #666;
            text-align: right;
        }
        
        .data-referidos .progress-bar-fill {
            background: linear-gradient(90deg, #3498db, #2980b9);
        }
        
        .data-descargadores .progress-bar-fill {
            background: linear-gradient(90deg, #27ae60, #219653);
        }
        
        /* Clases para indicadores de color */
        .usage-low { color: #27ae60; }
        .usage-medium { color: #f39c12; }
        .usage-high { color: #e74c3c; }
        
        /* Estilos adicionales para estadísticas */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-top: 30px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            border: 1px solid #eaeaea;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }
        
        .stat-card-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .stat-card-number {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .stat-card-label {
            font-size: 0.9rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .stat-card.referenciadores .stat-card-icon { color: #3498db; }
        .stat-card.descargadores .stat-card-icon { color: #27ae60; }
        .stat-card.administradores .stat-card-icon { color: #9b59b6; }
        .stat-card.superadmin .stat-card-icon { color: #e74c3c; }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
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
        
        <!-- Estadísticas de usuarios -->
        <div class="stats-grid">
            <div class="stat-card referenciadores">
                <div class="stat-card-icon">
                    <i class="fas fa-user-friends"></i>
                </div>
                <div class="stat-card-number"><?php echo $referenciadores; ?></div>
                <div class="stat-card-label">Referenciadores</div>
            </div>
            
            <div class="stat-card descargadores">
                <div class="stat-card-icon">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-card-number"><?php echo $descargadores; ?></div>
                <div class="stat-card-label">Descargadores</div>
            </div>
            
            <div class="stat-card administradores">
                <div class="stat-card-icon">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="stat-card-number"><?php echo $administradores; ?></div>
                <div class="stat-card-label">Administradores</div>
            </div>
            
            <div class="stat-card superadmin">
                <div class="stat-card-icon">
                    <i class="fas fa-user-crown"></i>
                </div>
                <div class="stat-card-number"><?php echo $superadmins; ?></div>
                <div class="stat-card-label">Super Admin</div>
            </div>
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
                <div class="data-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo number_format($total_referidos); ?></span>
                        <span class="stat-label">Total Referidos</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo number_format($tope_total); ?></span>
                        <span class="stat-label">Tope Total</span>
                    </div>
                </div>
                
                <!-- Barra de progreso para mostrar uso del tope -->
                <div class="progress-container">
                    <div class="progress-bar-bg">
                        <div class="progress-bar-fill" id="tope-progress" style="width: <?php echo min($porcentaje_uso, 100); ?>%"></div>
                    </div>
                    <div class="progress-text">
                        Uso del tope: 
                        <span class="<?php 
                            if ($porcentaje_uso < 70) echo 'usage-low';
                            elseif ($porcentaje_uso < 90) echo 'usage-medium';
                            else echo 'usage-high';
                        ?>">
                            <?php echo $porcentaje_uso; ?>%
                        </span>
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
                    Información detallada de quienes ya han votado. 
                    Control y seguimiento de la descarga de votos verificados.
                </div>
                <div class="data-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo number_format($referidos_activos); ?></span>
                        <span class="stat-label">Activos</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo number_format($referidos_inactivos); ?></span>
                        <span class="stat-label">Inactivos</span>
                    </div>
                </div>
                <div class="progress-container">
                    <div class="progress-bar-bg">
                        <div class="progress-bar-fill" style="width: <?php 
                            $porcentaje_activos = ($total_referidos > 0) ? round(($referidos_activos / $total_referidos) * 100, 1) : 0;
                            echo $porcentaje_activos;
                        ?>%"></div>
                    </div>
                    <div class="progress-text">
                        Activos: <?php echo $porcentaje_activos; ?>%
                    </div>
                </div>
            </a>
        </div>
        
        <!-- Estadísticas generales del sistema -->
        <div style="margin-top: 40px; text-align: center; color: #666;">
            <p><i class="fas fa-info-circle"></i> 
                Sistema cuenta con <strong><?php echo number_format($total_usuarios); ?></strong> usuarios registrados 
                (<strong><?php echo $usuarios_activos; ?></strong> activos) y 
                <strong><?php echo number_format($total_referidos); ?></strong> referidos en total.
            </p>
        </div>
    </div>

    <!-- Footer -->
    <footer class="system-footer">
        <div class="container">
            <p>© Derechos de autor Reservados. 
                Ing. Rubén Darío González García • 
                SISGONTech • Colombia © • <?php echo date('Y'); ?>
            </p>
            <p>Contacto: +57 3106310227 • 
                Email: sisgonnet@gmail.com
            </p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
            
            // Efecto hover para tarjetas de estadísticas
            $('.stat-card').hover(
                function() {
                    $(this).css('transform', 'translateY(-5px)');
                },
                function() {
                    $(this).css('transform', 'translateY(0)');
                }
            );
            
            // Actualizar estadísticas si es necesario (podrías hacer esto con AJAX para actualización en tiempo real)
            updateStats();
            
            function updateStats() {
                // Aquí podrías agregar AJAX para actualizar estadísticas periódicamente
                // Por ahora solo inicializamos
                console.log('Estadísticas cargadas:');
                console.log('- Total referidos: <?php echo $total_referidos; ?>');
                console.log('- Tope total: <?php echo $tope_total; ?>');
                console.log('- Uso: <?php echo $porcentaje_uso; ?>%');
                console.log('- Usuarios: <?php echo $total_usuarios; ?>');
            }
            
            // Animación de la barra de progreso
            setTimeout(function() {
                $('#tope-progress').css('transition', 'width 1.5s ease-in-out');
            }, 500);
        });
    </script>
</body>
</html>