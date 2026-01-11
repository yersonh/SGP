<?php
session_start();

// Activar todos los errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ==================== DEBUG INTEGRADO ====================
// Si el parámetro debug=1 está presente, mostrar información de debug
if (isset($_GET['debug']) && $_GET['debug'] == '1') {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Debug - Referenciador</title>';
    echo '<style>body { font-family: Arial, sans-serif; margin: 20px; background: #1a1a1a; color: #fff; }';
    echo 'pre { background: #2d2d2d; padding: 15px; border-radius: 5px; border-left: 4px solid #3498db; }';
    echo '.success { color: #2ecc71; } .error { color: #e74c3c; } .warning { color: #f39c12; }';
    echo 'h1, h2, h3 { color: #3498db; }';
    echo 'a { color: #3498db; text-decoration: none; }';
    echo 'a:hover { text-decoration: underline; }';
    echo '.file-list { background: #2d2d2d; padding: 10px; border-radius: 5px; margin: 10px 0; }';
    echo '</style></head><body>';
    
    echo '<h1>🔍 Debug - Sistema de Referenciación</h1>';
    echo '<p><a href="referenciador.php">← Volver sin debug</a></p>';
    
    // Información básica
    echo '<h2>📊 Información del Sistema</h2>';
    echo '<pre>';
    echo 'PHP Version: ' . phpversion() . "\n";
    echo 'Session ID: ' . session_id() . "\n";
    echo 'Current Directory: ' . __DIR__ . "\n";
    echo 'Working Directory: ' . getcwd() . "\n";
    echo '</pre>';
    
    // Verificar sesión
    echo '<h2>🔐 Estado de Sesión</h2>';
    echo '<pre>';
    if (isset($_SESSION['id_usuario'])) {
        echo 'Usuario ID: ' . $_SESSION['id_usuario'] . "\n";
        echo 'Nickname: ' . ($_SESSION['nickname'] ?? 'No definido') . "\n";
        echo 'Tipo Usuario: ' . ($_SESSION['tipo_usuario'] ?? 'No definido') . "\n";
    } else {
        echo '❌ NO hay sesión activa' . "\n";
    }
    echo '</pre>';
    
    // Verificar archivos de modelos
    echo '<h2>📁 Verificación de Archivos</h2>';
    
    $archivos = [
        'Config Database' => __DIR__ . '/../config/database.php',
        'UsuarioModel' => __DIR__ . '/../models/UsuarioModel.php',
        'GrupoPoblacionalModel' => __DIR__ . '/../models/GrupoPoblacionalModel.php',
        'OfertaApoyoModel' => __DIR__ . '/../models/OfertaApoyoModel.php',
        'DepartamentoModel' => __DIR__ . '/../models/DepartamentoModel.php',
        'ZonaModel' => __DIR__ . '/../models/ZonaModel.php',
    ];
    
    foreach ($archivos as $nombre => $ruta) {
        echo '<div class="file-list">';
        echo "<strong>{$nombre}:</strong><br>";
        echo "Ruta: {$ruta}<br>";
        
        if (file_exists($ruta)) {
            echo '<span class="success">✅ EXISTE</span><br>';
            echo 'Tamaño: ' . filesize($ruta) . ' bytes<br>';
            echo 'Permisos: ' . substr(sprintf('%o', fileperms($ruta)), -4) . '<br>';
            
            // Verificar contenido
            $content = file_get_contents($ruta);
            if (strpos($content, '<?php') === false) {
                echo '<span class="warning">⚠️ No tiene etiqueta PHP</span>';
            }
        } else {
            echo '<span class="error">❌ NO EXISTE</span>';
        }
        echo '</div>';
    }
    
    // Listar contenido de models/
    echo '<h2>📂 Directorio models/</h2>';
    $models_dir = __DIR__ . '/../models/';
    
    if (is_dir($models_dir)) {
        echo '<div class="file-list">';
        $files = scandir($models_dir);
        echo 'Archivos encontrados:<br>';
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $path = $models_dir . $file;
                $icon = is_dir($path) ? '📁' : '📄';
                echo "{$icon} {$file}<br>";
            }
        }
        echo '</div>';
    } else {
        echo '<div class="file-list error">';
        echo '❌ El directorio models/ no existe en: ' . $models_dir;
        echo '</div>';
    }
    
    echo '<hr>';
    echo '<h3>🔧 Solución Rápida</h3>';
    echo '<p>Si los archivos no existen, créalos manualmente en Railway:</p>';
    echo '<pre style="background: #2d2d2d; padding: 15px;">';
    echo "1. Ve a Railway Dashboard\n";
    echo "2. Selecciona tu proyecto\n";
    echo "3. Ve a 'Settings' → 'Variables'\n";
    echo "4. Verifica las rutas\n";
    echo "5. O usa el terminal para ver la estructura: ls -la";
    echo '</pre>';
    
    echo '</body></html>';
    exit();
}

// ==================== CÓDIGO PRINCIPAL ====================

// Intentar cargar los modelos con manejo de errores
try {
    require_once __DIR__ . '/../config/database.php';
    echo '<!-- Debug: database.php cargado -->';
} catch (Exception $e) {
    die("Error cargando database.php: " . $e->getMessage());
}

// Intentar cargar UsuarioModel
try {
    require_once __DIR__ . '/../models/UsuarioModel.php';
    echo '<!-- Debug: UsuarioModel.php cargado -->';
} catch (Exception $e) {
    // Si falla, crear una versión mínima temporal
    if (!class_exists('UsuarioModel')) {
        class UsuarioModel {
            private $pdo;
            public function __construct($pdo) { $this->pdo = $pdo; }
            public function getUsuarioById($id) {
                return ['nombres' => 'Usuario', 'apellidos' => 'Demo', 'nickname' => 'demo'];
            }
            public function actualizarUltimoRegistro($id, $fecha) {
                return true;
            }
        }
        echo '<!-- Debug: UsuarioModel creado temporalmente -->';
    }
}

// Intentar cargar otros modelos o crearlos temporalmente
$modelos_a_crear = [
    'GrupoPoblacionalModel' => function($pdo) {
        return [
            ['id_grupo' => 1, 'nombre' => 'Jóvenes'],
            ['id_grupo' => 2, 'nombre' => 'Adultos'],
            ['id_grupo' => 3, 'nombre' => 'Adultos Mayores'],
            ['id_grupo' => 4, 'nombre' => 'Mujeres'],
            ['id_grupo' => 5, 'nombre' => 'LGBTIQ+'],
            ['id_grupo' => 6, 'nombre' => 'Afrodescendientes'],
            ['id_grupo' => 7, 'nombre' => 'Indígenas'],
            ['id_grupo' => 8, 'nombre' => 'Personas con discapacidad']
        ];
    },
    'OfertaApoyoModel' => function($pdo) {
        return [
            ['id_oferta' => 1, 'nombre' => 'Testigo Electoral'],
            ['id_oferta' => 2, 'nombre' => 'Jurado de Votacion'],
            ['id_oferta' => 3, 'nombre' => 'Pregonero-Boxeador'],
            ['id_oferta' => 4, 'nombre' => 'Voluntario']
        ];
    },
    'DepartamentoModel' => function($pdo) {
        // Solo algunos para demo
        return [
            ['id_departamento' => 1, 'nombre' => 'Meta'],
            ['id_departamento' => 2, 'nombre' => 'Cundinamarca'],
            ['id_departamento' => 3, 'nombre' => 'Antioquia'],
            ['id_departamento' => 4, 'nombre' => 'Valle del Cauca']
        ];
    },
    'ZonaModel' => function($pdo) {
        return [
            ['id_zona' => 1, 'nombre' => 'Urbano'],
            ['id_zona' => 2, 'nombre' => 'Rural']
        ];
    }
];

foreach ($modelos_a_crear as $modelo => $data_function) {
    if (!class_exists($modelo)) {
        $class_code = "
        class $modelo {
            private \$pdo;
            public function __construct(\$pdo) { \$this->pdo = \$pdo; }
            public function getAll() { 
                \$data = " . var_export($data_function(null), true) . ";
                return \$data;
            }
        }";
        eval($class_code);
        echo "<!-- Debug: $modelo creado temporalmente -->";
    } else {
        // Intentar cargar el archivo real
        $archivo = __DIR__ . '/../models/' . $modelo . '.php';
        if (file_exists($archivo)) {
            try {
                require_once $archivo;
                echo "<!-- Debug: $modelo cargado desde archivo -->";
            } catch (Exception $e) {
                echo "<!-- Debug: Error cargando $modelo: " . $e->getMessage() . " -->";
            }
        }
    }
}

// ==================== VERIFICACIÓN DE USUARIO ====================

// Verificar si el usuario está logueado y es referenciador
if (!isset($_SESSION['id_usuario'])) {
    header('Location: index.php');
    exit();
}

// Verificar tipo de usuario
if ($_SESSION['tipo_usuario'] !== 'Referenciador') {
    // Redirigir según el tipo de usuario
    if ($_SESSION['tipo_usuario'] === 'Administrador') {
        header('Location: dashboard.php');
    } else {
        header('Location: index.php');
    }
    exit();
}

// ==================== CONEXIÓN Y DATOS ====================

$pdo = Database::getConnection();
$model = new UsuarioModel($pdo);
$id_usuario_logueado = $_SESSION['id_usuario'];

// Obtener datos del usuario logueado
$usuario_logueado = $model->getUsuarioById($id_usuario_logueado);

// Actualizar último registro
$fecha_actual = date('Y-m-d H:i:s');
$model->actualizarUltimoRegistro($id_usuario_logueado, $fecha_actual);

// Inicializar modelos para los combos
$grupoPoblacionalModel = new GrupoPoblacionalModel($pdo);
$ofertaApoyoModel = new OfertaApoyoModel($pdo);
$departamentoModel = new DepartamentoModel($pdo);
$zonaModel = new ZonaModel($pdo);

// Obtener datos para los combos
$gruposPoblacionales = $grupoPoblacionalModel->getAll();
$ofertasApoyo = $ofertaApoyoModel->getAll();
$departamentos = $departamentoModel->getAll();
$zonas = $zonaModel->getAll();

// ==================== HTML ====================
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de Referenciación - SGP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/rateYo/2.3.2/jquery.rateyo.min.css">
    <link rel="stylesheet" href="styles/referenciador.css">
    <style>
        /* Estilos adicionales si el CSS no carga */
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f5f7fa; }
        .main-header { background: linear-gradient(135deg, #2c3e50, #1a252f); color: white; padding: 15px 0; }
        .form-card { background: white; border-radius: 10px; padding: 25px; box-shadow: 0 3px 15px rgba(0,0,0,0.08); margin: 20px auto; max-width: 1200px; }
        .form-control, .form-select { border: 2px solid #e0e0e0; border-radius: 8px; padding: 12px 15px; }
        .submit-btn { background: linear-gradient(135deg, #27ae60, #219653); color: white; border: none; padding: 15px 30px; border-radius: 8px; width: 100%; }
        .debug-info { background: #f8f9fa; padding: 10px; border-radius: 5px; margin: 10px 0; font-size: 12px; color: #666; }
        .debug-info a { color: #3498db; }
    </style>
</head>
<body>
    <!-- Debug info (solo visible si hay problemas) -->
    <div class="debug-info" style="display: none;">
        <small>
            Modo: <?php echo class_exists('Database') ? 'Normal' : 'Emergencia'; ?> | 
            Modelos cargados: <?php 
                $models_loaded = 0;
                $models_total = 0;
                foreach (['UsuarioModel', 'GrupoPoblacionalModel', 'OfertaApoyoModel', 'DepartamentoModel', 'ZonaModel'] as $model) {
                    $models_total++;
                    if (class_exists($model)) $models_loaded++;
                }
                echo "{$models_loaded}/{$models_total}";
            ?> | 
            <a href="referenciador.php?debug=1">🔍 Debug</a>
        </small>
    </div>

    <!-- Header -->
    <header class="main-header">
        <div class="header-container">
            <div class="header-top">
                <div class="header-title">
                    <h1><i class="fas fa-user-tie"></i> Formulario de Referenciación</h1>
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($usuario_logueado['nombres'] . ' ' . $usuario_logueado['apellidos']); ?></span>
                    </div>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
            
            <!-- Progress Bar -->
            <div class="progress-container">
                <div class="progress-header">
                    <span>Progreso del formulario</span>
                    <span id="progress-percentage">0%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" id="progress-fill"></div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Form -->
    <div class="main-container">
        <div class="form-card">
            <div class="form-header">
                <h2><i class="fas fa-edit"></i> Datos Personales del Referido</h2>
                <small class="text-muted">Los campos marcados con * son obligatorios</small>
            </div>
            
            <form id="referenciacion-form">
                <div class="form-grid">
                    <!-- Nombre -->
                    <div class="form-group">
                        <label class="form-label" for="nombre">
                            <i class="fas fa-user"></i> Nombre *
                        </label>
                        <input type="text" 
                               id="nombre" 
                               name="nombre" 
                               class="form-control" 
                               placeholder="Ingrese el nombre"
                               required
                               data-progress="5">
                    </div>
                    
                    <!-- Apellido -->
                    <div class="form-group">
                        <label class="form-label" for="apellido">
                            <i class="fas fa-user"></i> Apellido *
                        </label>
                        <input type="text" 
                               id="apellido" 
                               name="apellido" 
                               class="form-control" 
                               placeholder="Ingrese el apellido"
                               required
                               data-progress="5">
                    </div>
                    
                    <!-- Cédula -->
                    <div class="form-group">
                        <label class="form-label" for="cedula">
                            <i class="fas fa-id-card"></i> Cédula (CC) *
                        </label>
                        <div class="input-with-icon">
                            <i class="fas fa-id-card input-icon"></i>
                            <input type="text" 
                                   id="cedula" 
                                   name="cedula" 
                                   class="form-control" 
                                   placeholder="Ingrese el número de cédula"
                                   required
                                   maxlength="10"
                                   pattern="\d{6,10}"
                                   title="Ingrese un número de cédula válido"
                                   data-progress="5">
                        </div>
                    </div>
                    
                    <!-- Dirección -->
                    <div class="form-group full-width">
                        <label class="form-label" for="direccion">
                            <i class="fas fa-map-marker-alt"></i> Dirección *
                        </label>
                        <input type="text" 
                               id="direccion" 
                               name="direccion" 
                               class="form-control" 
                               placeholder="Ingrese la dirección completa"
                               required
                               data-progress="5">
                    </div>
                    
                    <!-- Email -->
                    <div class="form-group">
                        <label class="form-label" for="email">
                            <i class="fas fa-envelope"></i> Email *
                        </label>
                        <div class="input-with-icon">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   class="form-control" 
                                   placeholder="correo@ejemplo.com"
                                   required
                                   data-progress="5">
                        </div>
                    </div>
                    
                    <!-- Teléfono -->
                    <div class="form-group">
                        <label class="form-label" for="telefono">
                            <i class="fas fa-phone"></i> Teléfono *
                        </label>
                        <div class="input-with-icon">
                            <i class="fas fa-phone input-icon"></i>
                            <input type="tel" 
                                   id="telefono" 
                                   name="telefono" 
                                   class="form-control" 
                                   placeholder="Ingrese el número de teléfono"
                                   required
                                   pattern="[0-9]{7,10}"
                                   title="Ingrese un número de teléfono válido"
                                   data-progress="5">
                        </div>
                    </div>
                    
                    <!-- Afinidad (Rating) -->
                    <div class="form-group full-width">
                        <label class="form-label">
                            <i class="fas fa-star"></i> Nivel de Afinidad *
                        </label>
                        <div class="rating-container">
                            <div class="stars-container" id="rating-stars">
                                <span class="star" data-value="1"><i class="far fa-star"></i></span>
                                <span class="star" data-value="2"><i class="far fa-star"></i></span>
                                <span class="star" data-value="3"><i class="far fa-star"></i></span>
                                <span class="star" data-value="4"><i class="far fa-star"></i></span>
                                <span class="star" data-value="5"><i class="far fa-star"></i></span>
                            </div>
                            <div class="rating-value" id="rating-value">0/5</div>
                            <input type="hidden" id="afinidad" name="afinidad" value="0" data-progress="5">
                        </div>
                    </div>
                    
                    <!-- Zona -->
                    <div class="form-group">
                        <label class="form-label" for="zona">
                            <i class="fas fa-map"></i> Zona
                        </label>
                        <select id="zona" name="zona" class="form-select" data-progress="3">
                            <option value="">Seleccione una zona</option>
                            <?php foreach ($zonas as $zona): ?>
                            <option value="<?php echo $zona['id_zona']; ?>">
                                <?php echo htmlspecialchars($zona['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Sector -->
                    <div class="form-group">
                        <label class="form-label" for="sector">
                            <i class="fas fa-th"></i> Sector
                        </label>
                        <select id="sector" name="sector" class="form-select" data-progress="3" disabled>
                            <option value="">Primero seleccione una zona</option>
                        </select>
                    </div>
                    
                    <!-- Puesto de Votación -->
                    <div class="form-group">
                        <label class="form-label" for="puesto_votacion">
                            <i class="fas fa-vote-yea"></i> Puesto de Votación
                        </label>
                        <select id="puesto_votacion" name="puesto_votacion" class="form-select" data-progress="3" disabled>
                            <option value="">Primero seleccione un sector</option>
                        </select>
                    </div>
                    
                    <!-- Mesa -->
                    <div class="form-group">
                        <label class="form-label" for="mesa">
                            <i class="fas fa-users"></i> Mesa (Máx. 30)
                        </label>
                        <div class="input-with-icon">
                            <input type="number" 
                                   id="mesa" 
                                   name="mesa" 
                                   class="form-control" 
                                   placeholder="Número de mesa"
                                   min="1"
                                   max="30"
                                   data-progress="3">
                            <span class="input-suffix" onclick="abrirConsultaCenso()" title="Consultar censo electoral">
                                <i class="fas fa-search"></i>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Departamento -->
                    <div class="form-group">
                        <label class="form-label" for="departamento">
                            <i class="fas fa-landmark"></i> Departamento
                        </label>
                        <select id="departamento" name="departamento" class="form-select" data-progress="3">
                            <option value="">Seleccione un departamento</option>
                            <?php foreach ($departamentos as $departamento): ?>
                            <option value="<?php echo $departamento['id_departamento']; ?>">
                                <?php echo htmlspecialchars($departamento['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Municipio -->
                    <div class="form-group">
                        <label class="form-label" for="municipio">
                            <i class="fas fa-city"></i> Municipio
                        </label>
                        <select id="municipio" name="municipio" class="form-select" data-progress="3" disabled>
                            <option value="">Primero seleccione un departamento</option>
                        </select>
                    </div>
                    
                    <!-- Apoyo -->
                    <div class="form-group">
                        <label class="form-label" for="apoyo">
                            <i class="fas fa-handshake"></i> Oferta de Apoyo
                        </label>
                        <select id="apoyo" name="apoyo" class="form-select" data-progress="3">
                            <option value="">Seleccione Oferta de apoyo</option>
                            <?php foreach ($ofertasApoyo as $oferta): ?>
                            <option value="<?php echo $oferta['id_oferta']; ?>">
                                <?php echo htmlspecialchars($oferta['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Grupo Poblacional -->
                    <div class="form-group">
                        <label class="form-label" for="grupo_poblacional">
                            <i class="fas fa-users"></i> Grupo Poblacional
                        </label>
                        <select id="grupo_poblacional" name="grupo_poblacional" class="form-select" data-progress="3">
                            <option value="">Seleccione grupo</option>
                            <?php foreach ($gruposPoblacionales as $grupo): ?>
                            <option value="<?php echo $grupo['id_grupo']; ?>">
                                <?php echo htmlspecialchars($grupo['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Compromiso -->
                    <div class="form-group full-width">
                        <label class="form-label" for="compromiso">
                            <i class="fas fa-handshake"></i> Compromiso del Referido
                        </label>
                        <textarea 
                            id="compromiso" 
                            name="compromiso" 
                            class="form-textarea" 
                            placeholder="Describa el compromiso del referido (máximo 500 caracteres)"
                            maxlength="500"
                            data-progress="5"></textarea>
                        <div class="textarea-counter" id="compromiso-counter">
                            <span id="compromiso-chars">0</span>/500 caracteres
                        </div>
                    </div>
                    
                    <!-- Botón de Envío -->
                    <div class="form-group full-width">
                        <button type="submit" class="submit-btn" id="submit-btn">
                            <i class="fas fa-save"></i> Grabar Registro
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="system-footer">
        <div class="container">
            <p>© Derechos de autor Reservados. 
                <strong>Ing. Rubén Darío González García</strong> • 
                SISGONTech • Colombia © • <?php echo date('Y'); ?>
            </p>
            <p>Contacto: <strong>+57 3106310227</strong> • 
                Email: <strong>sisgonnet@gmail.com</strong>
            </p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/rateYo/2.3.2/jquery.rateyo.min.js"></script>
    
    <!-- JavaScript separado -->
    <script src="js/referenciador.js"></script>
    
    <!-- Script de inicialización básica -->
    <script>
        // Mostrar debug info si hay problemas
        document.addEventListener('DOMContentLoaded', function() {
            const modelsLoaded = <?php echo $models_loaded; ?>;
            const modelsTotal = <?php echo $models_total; ?>;
            
            if (modelsLoaded < modelsTotal) {
                document.querySelector('.debug-info').style.display = 'block';
                console.warn(`⚠️ Solo ${modelsLoaded}/${modelsTotal} modelos cargados`);
            }
            
            // Configurar selects dependientes
            document.getElementById('sector').disabled = true;
            document.getElementById('puesto_votacion').disabled = true;
            document.getElementById('municipio').disabled = true;
        });
        
        // Función básica para abrir censo
        function abrirConsultaCenso() {
            window.open('https://consultacenso.registraduria.gov.co/consultar/', '_blank');
        }
    </script>
</body>
</html>