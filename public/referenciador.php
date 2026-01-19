<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../models/GrupoPoblacionalModel.php';
require_once __DIR__ . '/../models/OfertaApoyoModel.php';
require_once __DIR__ . '/../models/DepartamentoModel.php';
require_once __DIR__ . '/../models/ZonaModel.php';
require_once __DIR__ . '/../models/BarrioModel.php';
require_once __DIR__ . '/../models/Grupos_ParlamentariosModel.php';
require_once __DIR__ . '/../models/SistemaModel.php';

// Verificar si el usuario está logueado y es referenciador
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'Referenciador') {
    header('Location: index.php');
    exit();
}

$pdo = Database::getConnection();
$model = new UsuarioModel($pdo);
$id_usuario_logueado = $_SESSION['id_usuario'];

// Obtener información del sistema
$sistemaModel = new SistemaModel($pdo);
$infoSistema = $sistemaModel->getInformacionSistema();

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
$gruposParlamentariosModel = new Grupos_ParlamentariosModel($pdo);
// Obtener datos para los combos
$gruposPoblacionales = $grupoPoblacionalModel->getAll();
$ofertasApoyo = $ofertaApoyoModel->getAll();
$departamentos = $departamentoModel->getAll();
$zonas = $zonaModel->getAll();
$barrios = $barrioModel->getAll();
$gruposParlamentarios = $gruposParlamentariosModel->getAll();

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
    <title>Formulario de Referenciación - SGP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles/referenciador.css">
    <style>
        /* Estilos para el botón de ver referenciados */
        .view-referrals-btn {
            background: linear-gradient(135deg, #17a2b8, #138496);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(23, 162, 184, 0.3);
        }
        
        .view-referrals-btn:hover {
            background: linear-gradient(135deg, #138496, #117a8b);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(23, 162, 184, 0.4);
            color: white;
            text-decoration: none;
        }
        
        .view-referrals-btn i {
            font-size: 1.1rem;
        }
        
        /* Ajustes para el header-top */
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            margin-bottom: 10px;
        }
        
        .header-title {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        @media (max-width: 768px) {
            .header-top {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .header-actions {
                width: 100%;
                justify-content: space-between;
            }
            
            .view-referrals-btn {
                padding: 8px 16px;
                font-size: 0.9rem;
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
                <h1><i class="fas fa-user-tie"></i> Formulario de Referenciación</h1>
                <div class="user-info">
                    <i class="fas fa-user-circle"></i>
                    <span><?php echo htmlspecialchars($usuario_logueado['nombres'] . ' ' . $usuario_logueado['apellidos']); ?></span>
                </div>
            </div>
            <div class="header-actions">
                <!-- BOTÓN PARA VER REFERENCIADOS -->
                <a href="ver_referenciados.php" class="view-referrals-btn">
                    <i class="fas fa-users"></i> Ver Referenciados
                </a>
                
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
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
                    
                    <!-- Sexo (OBLIGATORIO) -->
                    <div class="form-group">
                        <label class="form-label" for="sexo">
                            <i class="fas fa-venus-mars"></i> Sexo *
                        </label>
                        <select id="sexo" name="sexo" class="form-select" data-progress="3" required>
                            <option value="">Seleccione sexo</option>
                            <option value="Masculino">Masculino</option>
                            <option value="Femenino">Femenino</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    
                    <!-- Barrio (OBLIGATORIO) -->
                    <div class="form-group">
                        <label class="form-label" for="barrio">
                            <i class="fas fa-map-signs"></i> Barrio *
                        </label>
                        <select id="barrio" name="barrio" class="form-select" data-progress="3" required>
                            <option value="">Seleccione un barrio</option>
                            <?php foreach ($barrios as $barrio): ?>
                            <option value="<?php echo $barrio['id_barrio']; ?>">
                                <?php echo htmlspecialchars($barrio['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Departamento (OBLIGATORIO) -->
                    <div class="form-group">
                        <label class="form-label" for="departamento">
                            <i class="fas fa-landmark"></i> Departamento *
                        </label>
                        <select id="departamento" name="departamento" class="form-select" data-progress="3" required>
                            <option value="">Seleccione un departamento</option>
                            <?php foreach ($departamentos as $departamento): ?>
                            <option value="<?php echo $departamento['id_departamento']; ?>">
                                <?php echo htmlspecialchars($departamento['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Municipio (OBLIGATORIO) -->
                    <div class="form-group">
                        <label class="form-label" for="municipio">
                            <i class="fas fa-city"></i> Municipio *
                        </label>
                        <select id="municipio" name="municipio" class="form-select" data-progress="3" disabled required>
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
                            <input type="hidden" id="afinidad" name="afinidad" value="0" data-progress="5" required>
                        </div>
                    </div>
                    <!--Grupo Parlamentario (OBLIGATORIO) -->
                    <div class="form-group">
                        <label class="form-label" for="grupo_parlamentario">
                            <i class="fas fa-users-cog"></i> Grupo Parlamentario *
                        </label>
                        <select id="grupo_parlamentario" name="grupo_parlamentario" class="form-select" data-progress="3" required>
                            <option value="">Seleccione un Grupo Parlamentario</option>
                            <?php foreach ($gruposParlamentarios as $grupo): ?>
                            <option value="<?php echo $grupo['id_grupo']; ?>">
                                <?php echo htmlspecialchars($grupo['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Vota Fuera -->
                    <div class="form-group">
                        <label class="form-label" for="vota_fuera_switch">
                            <i class="fas fa-person-booth"></i> Vota Fuera *
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
                            <input type="hidden" id="vota_fuera" name="vota_fuera" value="No" required>
                        </div>
                    </div>

                    <!-- Campos para cuando Vota Fuera es SÍ -->
                    <div class="form-group campo-fuera" style="display: none;">
                        <label class="form-label" for="puesto_votacion_fuera">
                            <i class="fas fa-vote-yea"></i> Puesto de Votación Fuera *
                        </label>
                        <input type="text" 
                               id="puesto_votacion_fuera" 
                               name="puesto_votacion_fuera" 
                               class="form-control" 
                               placeholder="Ingrese el puesto de votación fuera"
                               maxlength="100"
                               data-progress="3">
                    </div>

                    <div class="form-group campo-fuera" style="display: none;">
                        <label class="form-label" for="mesa_fuera">
                            <i class="fas fa-users"></i> Mesa Fuera *
                        </label>
                        <input type="number" 
                               id="mesa_fuera" 
                               name="mesa_fuera" 
                               class="form-control" 
                               placeholder="Número de mesa (1-40)"
                               min="1"
                               max="40"
                               data-progress="3">
                        <div class="mesa-info" style="font-size: 12px; color: #666; margin-top: 5px;">
                            Máximo: 40 mesas
                        </div>
                    </div>

                    <!-- Zona (sin required inicial) -->
                    <div class="form-group campo-votacion">
                        <label class="form-label" for="zona">
                            <i class="fas fa-map"></i> Zona <span class="obligatorio-campo-local">*</span>
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
                    
                    <!-- Sector (sin required inicial) -->
                    <div class="form-group campo-votacion">
                        <label class="form-label" for="sector">
                            <i class="fas fa-th"></i> Sector <span class="obligatorio-campo-local">*</span>
                        </label>
                        <select id="sector" name="sector" class="form-select" data-progress="3" disabled>
                            <option value="">Primero seleccione una zona</option>
                        </select>
                    </div>

                    <!-- Puesto de Votación (sin required inicial) -->
                    <div class="form-group campo-votacion">
                        <label class="form-label" for="puesto_votacion">
                            <i class="fas fa-vote-yea"></i> Puesto de Votación <span class="obligatorio-campo-local">*</span>
                        </label>
                        <select id="puesto_votacion" name="puesto_votacion" class="form-select" data-progress="3" disabled>
                            <option value="">Primero seleccione un sector</option>
                        </select>
                    </div>

                    <!-- Mesa (sin required inicial) -->
                    <div class="form-group campo-votacion">
                        <label class="form-label" for="mesa">
                            <i class="fas fa-users"></i> Mesa <span class="obligatorio-campo-local">*</span>
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
                    
                    <!-- Apoyo (OBLIGATORIO) -->
                    <div class="form-group">
                        <label class="form-label" for="apoyo">
                            <i class="fas fa-handshake"></i> Oferta de Apoyo *
                        </label>
                        <select id="apoyo" name="apoyo" class="form-select" data-progress="3" required>
                            <option value="">Seleccione Oferta de apoyo</option>
                            <?php foreach ($ofertasApoyo as $oferta): ?>
                            <option value="<?php echo $oferta['id_oferta']; ?>">
                                <?php echo htmlspecialchars($oferta['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Grupo Poblacional (OBLIGATORIO) -->
                    <div class="form-group">
                        <label class="form-label" for="grupo_poblacional">
                            <i class="fas fa-users"></i> Grupo Poblacional *
                        </label>
                        <select id="grupo_poblacional" name="grupo_poblacional" class="form-select" data-progress="3" required>
                            <option value="">Seleccione grupo</option>
                            <?php foreach ($gruposPoblacionales as $grupo): ?>
                            <option value="<?php echo $grupo['id_grupo']; ?>">
                                <?php echo htmlspecialchars($grupo['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Compromiso (NO obligatorio) -->
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
                    
                    <!-- Insumos Disponibles (NO obligatorio) -->
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
                    
                    <!-- Barra de Progreso del Formulario -->
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
    <!-- JavaScript separado -->
    <script src="js/referenciador.js"></script>
    <script src="js/modal-sistema.js"></script>
    
    <!-- Script para manejar la lógica de campos obligatorios condicionales -->
    <script>
    window.camposCondicionalesConfigurados = false;
    </script>
</body>
</html>