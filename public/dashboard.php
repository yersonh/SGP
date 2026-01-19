<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../models/SistemaModel.php';

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: index.php');
    exit();
}

$pdo = Database::getConnection();
$model = new UsuarioModel($pdo);
$sistemaModel = new SistemaModel($pdo);

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

// 6. Obtener información del sistema
$infoSistema = $sistemaModel->getInformacionSistema();

// 7. Formatear fecha para mostrar
$fecha_formateada = date('d/m/Y H:i:s', strtotime($fecha_actual));

// 8. Obtener días restantes de licencia
$diasRestantes = $sistemaModel->getDiasRestantesLicencia();
$validaHasta = $infoSistema['valida_hasta'] ?? null;
$validaHastaFormatted = $validaHasta ? date('d/m/Y', strtotime($validaHasta)) : 'No disponible';
$totalDias = 30; // Asumimos 30 días como total de la licencia
$porcentajeRestante = min(100, max(0, ($diasRestantes / $totalDias) * 100));
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios del Sistema - SGP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles/dashboard.css">
    <style>
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
    <!-- Header -->
    <header class="main-header">
        <div class="header-container">
            <div class="header-title">
                <h1>
                    <i class="fas fa-users"></i> Usuarios del Sistema
                    <span class="user-count"><?php echo $total_usuarios; ?> usuarios</span>
                </h1>
            </div>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
            </a>
        </div>
    </header>

    <div class="main-container">
        <!-- Información del usuario actual -->
        <div class="current-user-info">
            <div class="current-user-header">
                <h3><i class="fas fa-user-circle"></i> Sesión activa</h3>
                <span class="login-time" id="current-time"><?php echo $fecha_formateada; ?></span>
            </div>
            <div class="user-details">
                <div class="detail-item">
                    <span class="detail-label">Usuario Actual:</span>
                    <span class="detail-value">
                        <?php 
                        $nombre_completo = '';
                        if (!empty($usuario_logueado['nombres']) && !empty($usuario_logueado['apellidos'])) {
                            $nombre_completo = htmlspecialchars($usuario_logueado['nombres'] . ' ' . $usuario_logueado['apellidos']);
                        } else {
                            $nombre_completo = htmlspecialchars($usuario_logueado['nickname']);
                        }
                        echo $nombre_completo;
                        ?>
                    </span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Nickname:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($usuario_logueado['nickname']); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Tipo de usuario:</span>
                    <span class="detail-value user-type"><?php echo htmlspecialchars($usuario_logueado['tipo_usuario']); ?></span>
                </div>
            </div>
        </div>

        <!-- Top Bar con botón y estadísticas -->
        <div class="top-bar">
            <a href="agregar_usuario.php" class="btn-add-user">
                <i class="fas fa-plus-circle"></i> AGREGAR USUARIO
            </a>
            
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_usuarios; ?></div>
                    <div class="stat-label">Total Usuarios</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $usuarios_activos; ?></div>
                    <div class="stat-label">Usuarios Activos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $administradores; ?></div>
                    <div class="stat-label">Administradores</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $referenciadores; ?></div>
                    <div class="stat-label">Referenciadores</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $descargadores; ?></div>
                    <div class="stat-label">Descargadores</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $superadmin; ?></div>
                    <div class="stat-label">Super Admin</div>
                </div>
            </div>
        </div>
        
        <!-- Buscador -->
        <div class="search-container">
            <input type="text" 
                   class="search-input" 
                   id="search-input" 
                   placeholder="Buscar por nombre, apellido o nickname..."
                   onkeyup="buscarUsuarios()">
            <button class="search-btn" onclick="buscarUsuarios()">
                <i class="fas fa-search"></i> Buscar
            </button>
            <button class="clear-search-btn" onclick="limpiarBusqueda()">
                <i class="fas fa-times"></i> Limpiar
            </button>
        </div>
        
        <!-- Resultados de búsqueda -->
        <div class="search-results" id="search-results">
            Mostrando <?php echo $total_usuarios; ?> usuarios
        </div>

        <!-- Tabla de usuarios -->
        <div class="table-container">
            <div class="table-header">
                <h2><i class="fas fa-list-alt"></i> Listado de Usuarios</h2>
            </div>
            
            <?php if ($total_usuarios > 0): ?>
            <div class="table-responsive">
                <table class="users-table" id="users-table">
                    <thead>
                        <tr>
                            <th>NICKNAME</th>
                            <th>NOMBRE COMPLETO</th>
                            <th>CONTRASEÑA</th>
                            <th>TIPO</th>
                            <th>ESTADO</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody id="users-table-body">
                        <?php foreach ($usuarios as $usuario): ?>
                        <?php 
                        $activo = $usuario['activo'];
                        $esta_activo = ($activo === true || $activo === 't' || $activo == 1);
                        ?>
                        <tr>
                            <td>
                                <div class="user-info">
                                    <span class="user-nickname"><?php echo htmlspecialchars($usuario['nickname']); ?></span>
                                </div>
                            </td>
                            
                            <td>
                                <div class="user-info">
                                    <?php 
                                    if (!empty($usuario['nombres']) && !empty($usuario['apellidos'])) {
                                        echo '<span class="user-fullname">' . htmlspecialchars($usuario['nombres'] . ' ' . $usuario['apellidos']) . '</span>';
                                    } else {
                                        echo '<span class="text-muted fst-italic">Sin asignar</span>';
                                    }
                                    ?>
                                </div>
                            </td>
                            <td>
                                <div class="password-field d-flex align-items-center gap-2">
                                    <div class="input-group input-group-sm" style="width: 180px;">
                                        <input type="password" 
                                            value="<?php echo htmlspecialchars($usuario['password']); ?>" 
                                            class="form-control password-input" 
                                            readonly
                                            id="password-input-<?php echo $usuario['id_usuario']; ?>"
                                            style="background: #f8f9fa; border: 1px solid #dee2e6;">
                                        <button type="button" 
                                                class="btn btn-outline-secondary toggle-password-btn"
                                                data-user-id="<?php echo $usuario['id_usuario']; ?>"
                                                title="Mostrar/ocultar contraseña">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </td>
                            
                            <td>
                                <span class="user-type"><?php echo htmlspecialchars($usuario['tipo_usuario']); ?></span>
                            </td>
                            
                            <td>
                                <?php if ($esta_activo): ?>
                                    <span class="user-status status-active">
                                        <i class="fas fa-check-circle"></i> Activo
                                    </span>
                                <?php else: ?>
                                    <span class="user-status status-inactive">
                                        <i class="fas fa-times-circle"></i> Inactivo
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <!-- BOTÓN DE VER PERSONAS REFERENCIADAS - SOLO PARA REFERENCIADORES -->
                                    <?php if ($usuario['tipo_usuario'] === 'Referenciador'): ?>
                                        <button class="btn-action btn-view-referrals" 
                                                onclick="window.location.href='administrador/ver_referenciados.php?id=<?php echo $usuario['id_usuario']; ?>&nickname=<?php echo urlencode($usuario['nickname']); ?>'"
                                                title="Ver personas referenciadas por este usuario">
                                            <i class="fas fa-users"></i>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <!-- BOTÓN DE VER DETALLE -->
                                    <button class="btn-action btn-view" 
                                            onclick="window.location.href='administrador/ver_usuario.php?id=<?php echo $usuario['id_usuario']; ?>'"
                                            title="Ver detalle del usuario">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    
                                    <!-- BOTÓN DE EDITAR -->
                                    <button class="btn-action btn-edit" 
                                            onclick="window.location.href='administrador/editar_usuario.php?id=<?php echo $usuario['id_usuario']; ?>'"
                                            title="Editar usuario">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    
                                    <?php if ($esta_activo): ?>
                                        <button class="btn-action btn-deactivate" 
                                                title="Dar de baja al usuario"
                                                onclick="darDeBaja(<?php echo $usuario['id_usuario']; ?>, '<?php echo htmlspecialchars($usuario['nickname']); ?>', this)">
                                            <i class="fas fa-user-slash"></i>
                                        </button>
                                    <?php else: ?>
                                        <button class="btn-action btn-activate" 
                                                title="Reactivar usuario"
                                                onclick="reactivarUsuario(<?php echo $usuario['id_usuario']; ?>, '<?php echo htmlspecialchars($usuario['nickname']); ?>', this)">
                                            <i class="fas fa-user-check"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="no-data">
                <i class="fas fa-users"></i>
                <h4 class="mt-3 mb-2">No hay usuarios registrados</h4>
                <p class="text-muted">El sistema no tiene usuarios registrados actualmente.</p>
                <a href="agregar_usuario.php" class="btn-add-user mt-3">
                    <i class="fas fa-plus-circle"></i> Agregar Primer Usuario
                </a>
            </div>
            <?php endif; ?>
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
                    <div class="text-center mb-4">
                        <!-- ELIMINADO: <h1 class="display-5 fw-bold text-primary mb-2">
                            <?php echo htmlspecialchars($infoSistema['nombre_sistema'] ?? 'Sistema SGP'); ?>
                        </h1> -->
                        <h4 class="text-secondary mb-4">Gestión Política de Alta Precisión</h4>
                        
                        <!-- Información de Licencia -->
                        <div class="licencia-info">
                            <div class="licencia-header">
                                <h6 class="licencia-title">Licencia Runtime</h6>
                                <span class="licencia-dias"><?php echo $diasRestantes; ?> días restantes</span>
                            </div>
                            
                            <div class="licencia-progress">
                                <?php
                                $barColor = '';
                                if ($diasRestantes > 15) {
                                    $barColor = 'bg-success';
                                } elseif ($diasRestantes > 7) {
                                    $barColor = 'bg-warning';
                                } else {
                                    $barColor = 'bg-danger';
                                }
                                ?>
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Función para mostrar el modal del sistema
        function mostrarModalSistema() {
            const modal = new bootstrap.Modal(document.getElementById('modalSistema'));
            modal.show();
        }
        
        // Función para buscar usuarios
        function buscarUsuarios() {
            const searchTerm = document.getElementById('search-input').value.toLowerCase();
            const rows = document.querySelectorAll('#users-table-body tr');
            let visibleCount = 0;
            
            rows.forEach(row => {
                const nickname = row.querySelector('.user-nickname').textContent.toLowerCase();
                const fullname = row.querySelector('.user-fullname')?.textContent.toLowerCase() || '';
                const text = nickname + ' ' + fullname;
                
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            const resultsElement = document.getElementById('search-results');
            if (searchTerm.trim() === '') {
                resultsElement.textContent = `Mostrando ${rows.length} usuarios`;
            } else {
                resultsElement.textContent = `Mostrando ${visibleCount} de ${rows.length} usuarios (búsqueda: "${searchTerm}")`;
            }
        }
        
        // Función para limpiar búsqueda
        function limpiarBusqueda() {
            document.getElementById('search-input').value = '';
            buscarUsuarios();
            document.getElementById('search-input').focus();
        }
        
        // Actualizar hora en tiempo real
        function updateCurrentTime() {
            const now = new Date();
            const day = now.getDate().toString().padStart(2, '0');
            const month = (now.getMonth() + 1).toString().padStart(2, '0');
            const year = now.getFullYear();
            const hours = now.getHours().toString().padStart(2, '0');
            const minutes = now.getMinutes().toString().padStart(2, '0');
            const seconds = now.getSeconds().toString().padStart(2, '0');
            const timeString = `${day}/${month}/${year} ${hours}:${minutes}:${seconds}`;
            
            const currentTimeElement = document.getElementById('current-time');
            if (currentTimeElement) {
                currentTimeElement.textContent = timeString;
            }
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
        
        // Función para dar de baja un usuario
        async function darDeBaja(idUsuario, nickname, button) {
            if (!confirm(`¿Está seguro de dar de BAJA al usuario "${nickname}"?\n\nEl usuario no podrá acceder al sistema, pero se mantendrán sus registros.`)) {
                return;
            }
            
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
            button.disabled = true;
            
            try {
                const response = await fetch('ajax/usuario.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `accion=desactivar&id_usuario=${idUsuario}`
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const row = button.closest('tr');
                    const statusBadge = row.querySelector('.user-status');
                    if (statusBadge) {
                        statusBadge.className = 'user-status status-inactive';
                        statusBadge.innerHTML = '<i class="fas fa-times-circle"></i> Inactivo';
                    }
                    
                    const newButton = document.createElement('button');
                    newButton.className = 'btn-action btn-activate';
                    newButton.title = 'Reactivar usuario';
                    newButton.innerHTML = '<i class="fas fa-user-check"></i> REACTIVAR';
                    newButton.addEventListener('click', function() {
                        reactivarUsuario(idUsuario, nickname, newButton);
                    });
                    
                    const buttonContainer = button.parentNode;
                    buttonContainer.removeChild(button);
                    buttonContainer.appendChild(newButton);
                    
                    updateStats(-1, 0);
                    
                    showNotification('Usuario dado de baja correctamente', 'success');
                } else {
                    showNotification('Error: ' + (data.message || 'No se pudo dar de baja el usuario'), 'error');
                    button.innerHTML = originalText;
                    button.disabled = false;
                }
            } catch (error) {
                showNotification('Error de conexión: ' + error.message, 'error');
                button.innerHTML = originalText;
                button.disabled = false;
            }
        }
        
        // Función para reactivar un usuario
        async function reactivarUsuario(idUsuario, nickname, button) {
            if (!confirm(`¿Desea REACTIVAR al usuario "${nickname}"?`)) {
                return;
            }
            
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
            button.disabled = true;
            
            try {
                const response = await fetch('ajax/usuario.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `accion=reactivar&id_usuario=${idUsuario}`
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const row = button.closest('tr');
                    const statusBadge = row.querySelector('.user-status');
                    if (statusBadge) {
                        statusBadge.className = 'user-status status-active';
                        statusBadge.innerHTML = '<i class="fas fa-check-circle"></i> Activo';
                    }
                    
                    const newButton = document.createElement('button');
                    newButton.className = 'btn-action btn-deactivate';
                    newButton.title = 'Dar de baja al usuario';
                    newButton.innerHTML = '<i class="fas fa-user-slash"></i> DAR DE BAJA';
                    newButton.addEventListener('click', function() {
                        darDeBaja(idUsuario, nickname, newButton);
                    });
                    
                    const buttonContainer = button.parentNode;
                    buttonContainer.removeChild(button);
                    buttonContainer.appendChild(newButton);
                    
                    updateStats(1, 0);
                    
                    showNotification('Usuario reactivado correctamente', 'success');
                } else {
                    showNotification('Error: ' + data.message, 'error');
                    button.innerHTML = originalText;
                    button.disabled = false;
                }
            } catch (error) {
                showNotification('Error de conexión: ' + error.message, 'error');
                button.disabled = false;
            }
        }
        
        // Función para actualizar estadísticas
        function updateStats(changeActivos, changeTotal) {
            const statCards = document.querySelectorAll('.stat-card');
            if (statCards.length >= 2) {
                const totalElement = statCards[0].querySelector('.stat-number');
                const activosElement = statCards[1].querySelector('.stat-number');
                
                if (totalElement && changeTotal !== 0) {
                    const current = parseInt(totalElement.textContent);
                    totalElement.textContent = current + changeTotal;
                }
                
                if (activosElement && changeActivos !== 0) {
                    const current = parseInt(activosElement.textContent);
                    activosElement.textContent = current + changeActivos;
                }
                
                const userCountElement = document.querySelector('.user-count');
                if (userCountElement && changeTotal !== 0) {
                    const text = userCountElement.textContent;
                    const count = parseInt(text);
                    userCountElement.textContent = (count + changeTotal) + ' usuarios';
                }
            }
        }
        
        // Función para mostrar/ocultar contraseña
        function togglePassword(userId) {
            const passwordInput = document.getElementById(`password-input-${userId}`);
            const button = document.querySelector(`[data-user-id="${userId}"]`);
            const icon = button.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.className = 'fas fa-eye-slash';
                button.title = 'Ocultar contraseña';
                button.classList.add('btn-warning');
                button.classList.remove('btn-outline-secondary');
            } else {
                passwordInput.type = 'password';
                icon.className = 'fas fa-eye';
                button.title = 'Mostrar contraseña';
                button.classList.remove('btn-warning');
                button.classList.add('btn-outline-secondary');
            }
        }
        
        // Inicializar event listeners
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.toggle-password-btn').forEach(button => {
                const userId = button.getAttribute('data-user-id');
                button.addEventListener('click', () => togglePassword(userId));
            });
            
            document.querySelectorAll('.password-input[type="password"]').forEach(input => {
                input.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    this.blur();
                });
            });
        });
        
        // Efecto hover en filas de la tabla
        document.querySelectorAll('.users-table tbody tr').forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = '#f8fafc';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
            });
        });
        
        // Manejar parámetros de éxito/error en la URL
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            
            if (urlParams.has('success')) {
                const successType = urlParams.get('success');
                let message = '';
                
                switch(successType) {
                    case 'usuario_creado':
                        message = 'Usuario creado correctamente';
                        break;
                    case 'usuario_actualizado':
                        message = 'Usuario actualizado correctamente';
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
                    case 'usuario_no_encontrado':
                        message = 'Usuario no encontrado';
                        break;
                    default:
                        message = 'Ocurrió un error en la operación';
                }
                
                if (message) {
                    showNotification(message, 'error');
                    window.history.replaceState({}, document.title, window.location.pathname);
                }
            }
        });
        
    </script>
</body>
</html>