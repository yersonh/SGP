<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../models/GrupoPoblacionalModel.php';
require_once __DIR__ . '/../models/OfertaApoyoModel.php';
require_once __DIR__ . '/../models/DepartamentoModel.php';
require_once __DIR__ . '/../models/ZonaModel.php';
require_once __DIR__ . '/../models/BarrioModel.php';

// Verificar si el usuario está logueado y es referenciador
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'Referenciador') {
    header('Location: index.php');
    exit();
}

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
$barrioModel = new BarrioModel($pdo);

// Obtener datos para los combos
$gruposPoblacionales = $grupoPoblacionalModel->getAll();
$ofertasApoyo = $ofertaApoyoModel->getAll();
$departamentos = $departamentoModel->getAll();
$zonas = $zonaModel->getAll();
$barrios = $barrioModel->getAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de Referenciación - SGP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles/referenciador.css">
</head>
<body>
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
        
        <!-- Barra de Progreso del Tope (NUEVA EN EL HEADER) -->
        <div class="progress-container">
            <div class="progress-header">
                <span>Progreso del Tope: <?php echo $usuario_logueado['total_referenciados'] ?? 0; ?>/<?php echo $usuario_logueado['tope'] ?? 0; ?></span>
                <span id="tope-percentage"><?php echo $usuario_logueado['porcentaje_tope'] ?? 0; ?>%</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" id="tope-progress-fill"></div>
            </div>
        </div>
    </div>
</header>

    <!-- Main Form -->
    <div class="main-container">
        <div class="form-card">
            <!-- BOTÓN DE BUSCAR MESA AHORA ESTÁ AQUÍ, AL LADO DEL TÍTULO -->
            <div class="form-header">
                <h2><i class="fas fa-edit"></i> Datos Personales del Referido</h2>
                <button type="button" class="btn-mesa-search" onclick="abrirConsultaCenso()">
                    <i class="fas fa-search"></i> Buscar Mesa
                </button>
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
                                   title="Ingrese un número de cédula válido (solo números)"
                                   data-progress="5">
                        </div>
                        <!-- Mensaje de validación para cédula duplicada -->
                        <div id="cedula-validation-message" class="validation-message" style="display: none;">
                            <i class="fas fa-exclamation-circle"></i>
                            <span>Esta cédula ya está registrada en el sistema</span>
                        </div>
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
                                   maxlength="10"
                                   data-progress="5">
                        </div>
                    </div>
                    
                    <!-- Dirección -->
                    <div class="form-group">
                        <label class="form-label" for="direccion">
                            <i class="fas fa-map-marker-alt"></i> Dirección *
                        </label>
                        <div class="input-with-icon">
                            <i class="fas fa-map-marker-alt input-icon"></i>
                            <input type="text" 
                                   id="direccion" 
                                   name="direccion" 
                                   class="form-control" 
                                   placeholder="Ingrese la dirección"
                                   required
                                   data-progress="5">
                        </div>
                    </div>
                    <!-- Sexo (NUEVO CAMPO) -->
                    <div class="form-group">
                        <label class="form-label" for="sexo">
                            <i class="fas fa-venus-mars"></i> Sexo
                        </label>
                        <select id="sexo" name="sexo" class="form-select" data-progress="3">
                            <option value="">Seleccione sexo</option>
                            <option value="Masculino">Masculino</option>
                            <option value="Femenino">Femenino</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    
                    <!-- Vota Fuera (NUEVO CAMPO) -->
                    <div class="form-group">
                        <label class="form-label" for="vota_fuera_switch">
                            <i class="fas fa-person-booth"></i> Vota Fuera
                        </label>
                        <div class="switch-container">
                            <input type="checkbox" 
                                   id="vota_fuera_switch" 
                                   name="vota_fuera_switch" 
                                   class="switch-checkbox"
                                   data-progress="3">
                            <label for="vota_fuera_switch" class="switch-label">
                                <div class="switch-slider">
                                    <span class="switch-text-off">No</span>
                                    <span class="switch-text-on">Sí</span>
                                </div>
                            </label>
                            <input type="hidden" id="vota_fuera" name="vota_fuera" value="No">
                        </div>
                    </div>
                    <!-- Barrio -->
                    <div class="form-group">
                        <label class="form-label" for="barrio">
                            <i class="fas fa-map-signs"></i> Barrio
                        </label>
                        <select id="barrio" name="barrio" class="form-select" data-progress="3">
                            <option value="">Seleccione un barrio</option>
                            <?php foreach ($barrios as $barrio): ?>
                            <option value="<?php echo $barrio['id_barrio']; ?>">
                                <?php echo htmlspecialchars($barrio['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
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
                    
                    <!-- Mesa (SIMPLIFICADO - sin lupa) -->
                    <div class="form-group">
                        <label class="form-label" for="mesa">
                            <i class="fas fa-users"></i> Mesa
                        </label>
                        <input type="number" 
                               id="mesa" 
                               name="mesa" 
                               class="form-control" 
                               placeholder="Número de mesa"
                               min="1"
                               data-progress="3"
                               disabled>
                        <div class="mesa-info" id="mesa-info" style="font-size: 12px; color: #666; margin-top: 5px;"></div>
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
                    
                    <!-- Insumos Disponibles -->
                    <div class="form-group full-width">
                        <label class="form-label">
                            <i class="fas fa-tools"></i> Insumos Disponibles
                        </label>
                        <div class="insumos-container">
                            <div class="insumos-grid">
                                <!-- Carro -->
                                <div class="insumo-item">
                                    <input type="checkbox" id="insumo_carro" name="insumos[]" value="carro" class="insumo-checkbox">
                                    <label for="insumo_carro" class="insumo-label">
                                        <div class="insumo-icon">
                                            <i class="fas fa-car"></i>
                                        </div>
                                        <span class="insumo-text">Carro</span>
                                        <div class="insumo-switch">
                                            <div class="switch-slider"></div>
                                        </div>
                                    </label>
                                </div>
                                
                                <!-- Caballo -->
                                <div class="insumo-item">
                                    <input type="checkbox" id="insumo_caballo" name="insumos[]" value="caballo" class="insumo-checkbox">
                                    <label for="insumo_caballo" class="insumo-label">
                                        <div class="insumo-icon">
                                            <i class="fas fa-horse"></i>
                                        </div>
                                        <span class="insumo-text">Caballo</span>
                                        <div class="insumo-switch">
                                            <div class="switch-slider"></div>
                                        </div>
                                    </label>
                                </div>
                                
                                <!-- Cicla -->
                                <div class="insumo-item">
                                    <input type="checkbox" id="insumo_cicla" name="insumos[]" value="cicla" class="insumo-checkbox">
                                    <label for="insumo_cicla" class="insumo-label">
                                        <div class="insumo-icon">
                                            <i class="fas fa-bicycle"></i>
                                        </div>
                                        <span class="insumo-text">Cicla</span>
                                        <div class="insumo-switch">
                                            <div class="switch-slider"></div>
                                        </div>
                                    </label>
                                </div>
                                
                                <!-- Moto -->
                                <div class="insumo-item">
                                    <input type="checkbox" id="insumo_moto" name="insumos[]" value="moto" class="insumo-checkbox">
                                    <label for="insumo_moto" class="insumo-label">
                                        <div class="insumo-icon">
                                            <i class="fas fa-motorcycle"></i>
                                        </div>
                                        <span class="insumo-text">Moto</span>
                                        <div class="insumo-switch">
                                            <div class="switch-slider"></div>
                                        </div>
                                    </label>
                                </div>
                                
                                <!-- Motocarro -->
                                <div class="insumo-item">
                                    <input type="checkbox" id="insumo_motocarro" name="insumos[]" value="motocarro" class="insumo-checkbox">
                                    <label for="insumo_motocarro" class="insumo-label">
                                        <div class="insumo-icon">
                                            <i class="fas fa-truck-pickup"></i>
                                        </div>
                                        <span class="insumo-text">Motocarro</span>
                                        <div class="insumo-switch">
                                            <div class="switch-slider"></div>
                                        </div>
                                    </label>
                                </div>
                                
                                <!-- Publicidad -->
                                <div class="insumo-item">
                                    <input type="checkbox" id="insumo_publicidad" name="insumos[]" value="publicidad" class="insumo-checkbox">
                                    <label for="insumo_publicidad" class="insumo-label">
                                        <div class="insumo-icon">
                                            <i class="fas fa-bullhorn"></i>
                                        </div>
                                        <span class="insumo-text">Publicidad</span>
                                        <div class="insumo-switch">
                                            <div class="switch-slider"></div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <div class="insumos-selected" id="insumos-selected">
                                Ningún insumo seleccionado
                            </div>
                        </div>
                    </div>
                    <!-- Barra de Progreso del Formulario (MOVIDA AQUÍ, ANTES DEL BOTÓN) -->
                    <div class="progress-container" style="margin: 30px 0 20px 0;">
                        <div class="progress-header">
                            <span>Progreso del formulario</span>
                            <span id="progress-percentage">0%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" id="progress-fill"></div>
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
        <!-- Logo centrado -->
        <div class="container text-center mb-3">
            <img src="imagenes/Logo-artguru.png" alt="Logo">
        </div>
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
    <!-- JavaScript separado -->
    <script src="js/referenciador.js"></script>
</body>
</html>