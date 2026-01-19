<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../models/ReferenciadoModel.php';

// Verificar si el usuario está logueado y es referenciador
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'Referenciador') {
    header('Location: index.php');
    exit();
}

$pdo = Database::getConnection();
$usuarioModel = new UsuarioModel($pdo);
$referenciadoModel = new ReferenciadoModel($pdo);

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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Referenciados - SGP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles/referenciador.css">
    <style>
        /* Estilos específicos para la tabla de referenciados */
        .referenciados-container {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            border-top: 4px solid #3498db;
        }
        
        .stat-card.activos {
            border-top-color: #2ecc71;
        }
        
        .stat-card.inactivos {
            border-top-color: #e74c3c;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .referenciados-table {
            width: 100%;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        
        .table-header {
            background: linear-gradient(135deg, #2c3e50, #1a252f);
            color: white;
            padding: 20px;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #dee2e6;
            padding: 15px;
            white-space: nowrap;
        }
        
        .table td {
            padding: 15px;
            vertical-align: middle;
            border-bottom: 1px solid #e9ecef;
        }
        
        .table tbody tr:hover {
            background-color: #f8fafc;
        }
        
        .badge-estado {
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
        }
        
        .badge-activo {
            background-color: #d4edda;
            color: #155724;
        }
        
        .badge-inactivo {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .badge-afinidad {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.8rem;
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        .afinidad-stars {
            color: #ffc107;
            font-size: 0.9rem;
        }
        
        .vota-badge {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .vota-aqui {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .vota-fuera {
            background-color: #fff3e0;
            color: #ef6c00;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            color: #6c757d;
            margin-bottom: 10px;
        }
        
        .empty-state p {
            color: #adb5bd;
            max-width: 500px;
            margin: 0 auto 20px;
        }
        
        .btn-volver {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-volver:hover {
            background: linear-gradient(135deg, #495057, #343a40);
            color: white;
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .stats-cards {
                grid-template-columns: 1fr;
            }
            
            .table th, .table td {
                padding: 10px;
                font-size: 0.9rem;
            }
            
            .referenciados-container {
                padding: 15px;
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
                    <h1><i class="fas fa-users"></i> Mis Referenciados</h1>
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($usuario_logueado['nombres'] . ' ' . $usuario_logueado['apellidos']); ?></span>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="referenciador.php" class="btn-volver">
                        <i class="fas fa-arrow-left"></i> Volver al Formulario
                    </a>
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                    </a>
                </div>
            </div>
            
            <!-- Barra de Progreso del Tope -->
            <div class="progress-container">
                <div class="progress-header">
                    <span>Progreso del Tope: <?php echo $usuario_logueado['total_referenciados'] ?? 0; ?>/<?php echo $usuario_logueado['tope'] ?? 0; ?></span>
                    <span id="tope-percentage"><?php echo $usuario_logueado['porcentaje_tope'] ?? 0; ?>%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" id="tope-progress-fill" 
                         style="width: <?php echo min(100, $usuario_logueado['porcentaje_tope'] ?? 0); ?>%">
                    </div>
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

            <!-- Tabla de Referenciados -->
            <div class="referenciados-table">
                <div class="table-header">
                    <h3><i class="fas fa-list-alt"></i> Lista de Referenciados</h3>
                    <p class="mb-0">Fecha y hora actual: <?php echo $fecha_formateada; ?></p>
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

    <!-- Footer -->
    <footer class="system-footer">
        <div class="container text-center mb-3">
            <img src="../imagenes/Logo-artguru.png" alt="Logo">
        </div>

        <div class="container text-center">
            <p>
                © Derechos de autor Reservados • <strong>Ing. Rubén Darío González García</strong> • Equipo de soporte • SISGONTech<br>
                Email: sisgonnet@gmail.com • Contacto: +57 3106310227 • Puerto Gaitán, Colombia • <?php echo date('Y'); ?>
            </p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
            document.querySelectorAll('.table-header p').forEach(element => {
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
        });
    </script>
</body>
</html>