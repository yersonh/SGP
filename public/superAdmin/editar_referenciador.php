<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';
require_once __DIR__ . '/../../models/ReferenciadoModel.php';
require_once __DIR__ . '/../../models/ZonaModel.php';
require_once __DIR__ . '/../../models/SectorModel.php';
require_once __DIR__ . '/../../models/PuestoVotacionModel.php';
require_once __DIR__ . '/../../models/DepartamentoModel.php';
require_once __DIR__ . '/../../models/MunicipioModel.php';
require_once __DIR__ . '/../../models/OfertaApoyoModel.php';
require_once __DIR__ . '/../../models/GrupoPoblacionalModel.php';
require_once __DIR__ . '/../../models/BarrioModel.php';
require_once __DIR__ . '/../../models/InsumoModel.php';
require_once __DIR__ . '/../../models/Grupos_ParlamentariosModel.php';

header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

// Verificar si el usuario está logueado y es SuperAdmin
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    header('Location: ../index.php');
    exit();
}

// Verificar que se haya proporcionado un ID de referenciado
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: data_referidos.php?error=referenciado_no_encontrado');
    exit();
}

$id_referenciado = intval($_GET['id']);

$pdo = Database::getConnection();
$usuarioModel = new UsuarioModel($pdo);
$referenciadoModel = new ReferenciadoModel($pdo);
$insumoModel = new InsumoModel($pdo);
$zonaModel = new ZonaModel($pdo);
$sectorModel = new SectorModel($pdo);
$puestoModel = new PuestoVotacionModel($pdo);
$departamentoModel = new DepartamentoModel($pdo);
$municipioModel = new MunicipioModel($pdo);
$ofertaModel = new OfertaApoyoModel($pdo);
$grupoModel = new GrupoPoblacionalModel($pdo);
$barrioModel = new BarrioModel($pdo);
$gruposParlamentariosModel = new Grupos_ParlamentariosModel($pdo);

// Obtener datos del usuario logueado
$usuario_logueado = $usuarioModel->getUsuarioById($_SESSION['id_usuario']);

// Obtener datos completos del referenciado
$referenciado = $referenciadoModel->getReferenciadoCompleto($id_referenciado);
$referenciado['activo'] = $referenciado['activo'] ?? true;

if (!$referenciado) {
    header('Location: data_referidos.php?error=referenciado_no_encontrado');
    exit();
}

// Obtener datos para los selects
$zonas = $zonaModel->getAll();
$sectores = $sectorModel->getAll();
$puestos = $puestoModel->getAll();
$departamentos = $departamentoModel->getAll();
$municipios = $municipioModel->getAll();
$ofertas = $ofertaModel->getAll();
$grupos = $grupoModel->getAll();
$barrios = $barrioModel->getAll();
$insumos_disponibles = $insumoModel->getAll();
$gruposParlamentarios = $gruposParlamentariosModel->getAll();

// Obtener insumos del referenciado
$insumos_referenciado = $insumoModel->getInsumosByReferenciado($id_referenciado);

// Obtener información del referenciador
$referenciador = $usuarioModel->getUsuarioById($referenciado['id_referenciador'] ?? 0);

// Función para marcar un campo como seleccionado en select
function isSelected($value, $compare) {
    return $value == $compare ? 'selected' : '';
}

// Función para marcar un checkbox como checked
function isChecked($insumo_id, $insumos_referenciado) {
    foreach ($insumos_referenciado as $insumo) {
        if ($insumo['id_insumo'] == $insumo_id) {
            return 'checked';
        }
    }
    return '';
}
// Función para mostrar estado de actividad (igual que en ver_referenciado.php)
function getEstadoActividad($activo) {
    if ($activo === true || $activo === 't' || $activo == 1) {
        return '<span class="status-active"><i class="fas fa-check-circle"></i> Activo</span>';
    } else {
        return '<span class="status-inactive"><i class="fas fa-times-circle"></i> Inactivo</span>';
    }
}
// Variables para mensajes
$error_message = '';
$success_message = '';

// Verificar si hay mensaje de éxito en la URL
if (isset($_GET['success'])) {
    $success_message = 'Referenciado actualizado correctamente.';
}
error_log("=== Intentando actualizar referenciado ID: $id_referenciado ===");
// Procesar el formulario cuando se envíe
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Recopilar datos del formulario
$datos_actualizar = [
    'nombre' => $_POST['nombre'] ?? '',
    'apellido' => $_POST['apellido'] ?? '',
    'cedula' => $_POST['cedula'] ?? '',
    'direccion' => $_POST['direccion'] ?? '',
    'email' => $_POST['email'] ?? '',
    'telefono' => $_POST['telefono'] ?? '',
    'sexo' => $_POST['sexo'] ?? '',
    'vota_fuera' => $_POST['vota_fuera'] ?? 'No',
    'id_grupo' => !empty($_POST['id_grupo']) ? $_POST['id_grupo'] : null,
    'afinidad' => $_POST['afinidad'] ?? 1,
    'id_zona' => !empty($_POST['id_zona']) ? $_POST['id_zona'] : null,
    'id_sector' => !empty($_POST['id_sector']) ? $_POST['id_sector'] : null,
    'id_puesto_votacion' => !empty($_POST['id_puesto_votacion']) ? $_POST['id_puesto_votacion'] : null,
    'mesa' => !empty($_POST['mesa']) ? $_POST['mesa'] : null,
    'id_departamento' => !empty($_POST['id_departamento']) ? $_POST['id_departamento'] : null,
    'id_municipio' => !empty($_POST['id_municipio']) ? $_POST['id_municipio'] : null,
    'id_barrio' => !empty($_POST['id_barrio']) ? $_POST['id_barrio'] : null,
    'id_oferta_apoyo' => !empty($_POST['id_oferta_apoyo']) ? $_POST['id_oferta_apoyo'] : null,
    'id_grupo_poblacional' => !empty($_POST['id_grupo_poblacional']) ? $_POST['id_grupo_poblacional'] : null,
    'compromiso' => $_POST['compromiso'] ?? '',
    'insumos_nuevos' => $_POST['insumos_nuevos'] ?? [],
    'insumos_eliminar' => $_POST['insumos_eliminar'] ?? [],
    'id_referenciador' => !empty($_POST['id_referenciador']) ? intval($_POST['id_referenciador']) : $referenciado['id_referenciador']
];
error_log("=== DEPURACIÓN: Datos que se enviarán ===");
error_log("id_referenciador: " . ($datos_actualizar['id_referenciador'] ?? 'NO DEFINIDO'));
error_log("afinidad: " . ($datos_actualizar['afinidad'] ?? 'NO DEFINIDO'));
error_log("vota_fuera: " . ($datos_actualizar['vota_fuera'] ?? 'NO DEFINIDO'));
        // Agregar campos de votación fuera si están presentes
        if (isset($_POST['puesto_votacion_fuera'])) {
            $datos_actualizar['puesto_votacion_fuera'] = $_POST['puesto_votacion_fuera'];
        }
        
        if (isset($_POST['mesa_fuera']) && !empty($_POST['mesa_fuera'])) {
            $datos_actualizar['mesa_fuera'] = $_POST['mesa_fuera'];
        }
        
        // Agregar campos de cantidad y observaciones para los insumos
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'cantidad_') === 0) {
                $insumo_id = str_replace('cantidad_', '', $key);
                $datos_actualizar["cantidad_$insumo_id"] = $value;
            }
            if (strpos($key, 'observaciones_') === 0) {
                $insumo_id = str_replace('observaciones_', '', $key);
                $datos_actualizar["observaciones_$insumo_id"] = $value;
            }
        }
        
        // Validaciones básicas
        if (empty($datos_actualizar['nombre'])) {
            throw new Exception('El nombre es requerido.');
        }
        
        if (empty($datos_actualizar['apellido'])) {
            throw new Exception('El apellido es requerido.');
        }
        
        if (empty($datos_actualizar['cedula'])) {
            throw new Exception('La cédula es requerida.');
        }
        
        // Validar formato de cédula (solo números)
        if (!preg_match('/^\d+$/', $datos_actualizar['cedula'])) {
            throw new Exception('La cédula debe contener solo números.');
        }
        
        // Validar que la cédula no esté duplicada (excluyendo el actual referenciado)
        if ($referenciadoModel->cedulaExiste($datos_actualizar['cedula'], $id_referenciado)) {
            throw new Exception('La cédula ya está registrada para otro referenciado.');
        }
        
        // Validar afinidad (1-5)
        $afinidad = intval($datos_actualizar['afinidad']);
        if ($afinidad < 1 || $afinidad > 5) {
            throw new Exception('La afinidad debe estar entre 1 y 5.');
        }
        
        // Validar sexo si está presente
        if (!empty($datos_actualizar['sexo']) && !in_array($datos_actualizar['sexo'], ['Masculino', 'Femenino', 'Otro'])) {
            throw new Exception('El sexo seleccionado no es válido.');
        }
        // Validar grupo parlamentario (OBLIGATORIO)
        if (empty($_POST['id_grupo'])) {
            throw new Exception('El Grupo Parlamentario es obligatorio.');
        }

        // También validar que sea un valor válido
        if (!empty($_POST['id_grupo']) && $_POST['id_grupo'] <= 0) {
            throw new Exception('El Grupo Parlamentario seleccionado no es válido.');
        }
        // Validar vota_fuera
        if (!in_array($datos_actualizar['vota_fuera'], ['Si', 'No'])) {
            throw new Exception('El campo "Vota Fuera" debe ser Si o No.');
        }
        
        // Validaciones condicionales según si vota fuera o no
        if ($datos_actualizar['vota_fuera'] === 'Si') {
            // Validar campos para cuando vota fuera
            if (empty($datos_actualizar['puesto_votacion_fuera'])) {
                throw new Exception('El puesto de votación fuera es obligatorio cuando el referido vota fuera.');
            }
            if (empty($datos_actualizar['mesa_fuera']) || $datos_actualizar['mesa_fuera'] < 1) {
                throw new Exception('El número de mesa fuera es obligatorio y debe ser mayor a 0.');
            }
            if ($datos_actualizar['mesa_fuera'] > 40) {
                throw new Exception('El número de mesa fuera no puede ser mayor a 40.');
            }
        } else {
            // Validar campos para cuando NO vota fuera
            if (empty($datos_actualizar['id_zona'])) {
                throw new Exception('La zona es obligatoria cuando el referido NO vota fuera.');
            }
        }
        
        // Actualizar el referenciado
        $resultado = $referenciadoModel->actualizarReferenciado($id_referenciado, $datos_actualizar);
        
        if ($resultado) {
            // Redirigir con mensaje de éxito
            header('Location: ver_referenciado.php?id=' . $id_referenciado . '&success=1');
            exit();
        } else {
            throw new Exception('No se pudo actualizar el referenciado.');
        }
        
    } catch (Exception $e) {
        $error_message = 'Error al actualizar: ' . $e->getMessage();
        // Mantener los datos del formulario en caso de error
        if (isset($datos_actualizar)) {
            $referenciado = array_merge($referenciado, $datos_actualizar);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Referenciado - SGP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/editar_referenciado.css">
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="header-container">
            <div class="header-top">
                <div class="header-title">
                    <h1><i class="fas fa-edit"></i> Editar Referenciado</h1>
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span>Super Admin: <?php echo htmlspecialchars($usuario_logueado['nombres'] . ' ' . $usuario_logueado['apellidos']); ?></span>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="../logout.php" class="header-btn">
                        <i class="fas fa-sign-out-alt"></i> Salir
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-container">
        <form method="POST" action="editar_referenciador.php?id=<?php echo $id_referenciado; ?>">
            <div class="card-container">
                <!-- Card Header -->
                <div class="card-header">
                    <div class="header-content">
                        <div class="header-left">
                            <h2><i class="fas fa-user-edit"></i> Editar Referenciado</h2>
                            <p>Modifique la información del referenciado en el sistema</p>
                        </div>
                        <div class="header-right">
                            <?php echo getEstadoActividad($referenciado['activo'] ?? true); ?>
                        </div>
                    </div>
                </div>
                
                <!-- Form Sections -->
                <div class="form-sections">
                    <!-- Sección 1: Información Personal -->
                    <div class="section-title">
                        <i class="fas fa-id-card"></i> Información Personal
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-user"></i> Nombres *
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   name="nombre" 
                                   value="<?php echo htmlspecialchars($referenciado['nombre'] ?? ''); ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-user"></i> Apellidos *
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   name="apellido" 
                                   value="<?php echo htmlspecialchars($referenciado['apellido'] ?? ''); ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-id-card"></i> Cédula *
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   name="cedula" 
                                   value="<?php echo htmlspecialchars($referenciado['cedula'] ?? ''); ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-envelope"></i> Email
                            </label>
                            <input type="email" 
                                   class="form-control" 
                                   name="email" 
                                   value="<?php echo htmlspecialchars($referenciado['email'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-phone"></i> Teléfono
                            </label>
                            <input type="tel" 
                                   class="form-control" 
                                   name="telefono" 
                                   value="<?php echo htmlspecialchars($referenciado['telefono'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-venus-mars"></i> Sexo
                            </label>
                            <select class="form-control" name="sexo">
                                <option value="">Seleccionar...</option>
                                <option value="Masculino" <?php echo isSelected('Masculino', $referenciado['sexo'] ?? ''); ?>>Masculino</option>
                                <option value="Femenino" <?php echo isSelected('Femenino', $referenciado['sexo'] ?? ''); ?>>Femenino</option>
                                <option value="Otro" <?php echo isSelected('Otro', $referenciado['sexo'] ?? ''); ?>>Otro</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-home"></i> Dirección
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   name="direccion" 
                                   value="<?php echo htmlspecialchars($referenciado['direccion'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <!-- Sección 2: Información de Ubicación -->
                    <div class="section-title">
                        <i class="fas fa-map-marker-alt"></i> Información de Ubicación
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-flag"></i> Departamento
                            </label>
                            <select class="form-control" name="id_departamento">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($departamentos as $departamento): ?>
                                    <option value="<?php echo $departamento['id_departamento']; ?>" 
                                        <?php echo isSelected($departamento['id_departamento'], $referenciado['id_departamento'] ?? ''); ?>>
                                        <?php echo htmlspecialchars($departamento['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-city"></i> Municipio
                            </label>
                            <select class="form-control" name="id_municipio">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($municipios as $municipio): ?>
                                    <option value="<?php echo $municipio['id_municipio']; ?>" 
                                        <?php echo isSelected($municipio['id_municipio'], $referenciado['id_municipio'] ?? ''); ?>>
                                        <?php echo htmlspecialchars($municipio['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-map"></i> Barrio
                            </label>
                            <select class="form-control" name="id_barrio">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($barrios as $barrio): ?>
                                    <option value="<?php echo $barrio['id_barrio']; ?>" 
                                        <?php echo isSelected($barrio['id_barrio'], $referenciado['id_barrio'] ?? ''); ?>>
                                        <?php echo htmlspecialchars($barrio['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Sección 3: Información de Votación -->
                    <div class="section-title">
                        <i class="fas fa-vote-yea"></i> Información de Votación
                    </div>
                    
                    <div class="form-grid">
                        <!-- CAMPO NUEVO: Grupo Parlamentario (AGREGARLO AQUÍ) -->
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-users-cog"></i> Grupo Parlamentario *
                            </label>
                            <select class="form-control" name="id_grupo" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($gruposParlamentarios as $grupo): ?>
                                    <option value="<?php echo $grupo['id_grupo']; ?>" 
                                        <?php echo isSelected($grupo['id_grupo'], $referenciado['id_grupo'] ?? ''); ?>>
                                        <?php echo htmlspecialchars($grupo['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <!-- Campo Vota Fuera -->
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-person-booth"></i> Vota Fuera
                            </label>
                            <div class="switch-container">
                                <input type="checkbox" 
                                       class="switch-checkbox" 
                                       id="vota_fuera_switch" 
                                       name="vota_fuera_switch" 
                                       <?php echo ($referenciado['vota_fuera'] ?? 'No') === 'Si' ? 'checked' : ''; ?>>
                                <label for="vota_fuera_switch" class="switch-label">
                                    <div class="switch-slider">
                                        <span class="switch-text-on">Sí</span>
                                        <span class="switch-text-off">No</span>
                                    </div>
                                </label>
                                <input type="hidden" id="vota_fuera" name="vota_fuera" value="<?php echo htmlspecialchars($referenciado['vota_fuera'] ?? 'No'); ?>">
                            </div>
                        </div>
                        
                        <!-- Campos para cuando vota fuera (inicialmente ocultos) -->
                        <div class="form-group campo-fuera <?php echo ($referenciado['vota_fuera'] ?? 'No') === 'Si' ? '' : 'hidden'; ?>">
                            <label class="form-label">
                                <i class="fas fa-vote-yea"></i> Puesto de votación fuera *
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="puesto_votacion_fuera" 
                                   name="puesto_votacion_fuera" 
                                   value="<?php echo htmlspecialchars($referenciado['puesto_votacion_fuera'] ?? ''); ?>"
                                   <?php echo ($referenciado['vota_fuera'] ?? 'No') === 'Si' ? 'required' : ''; ?>>
                        </div>
                        
                        <div class="form-group campo-fuera <?php echo ($referenciado['vota_fuera'] ?? 'No') === 'Si' ? '' : 'hidden'; ?>">
                            <label class="form-label">
                                <i class="fas fa-table"></i> Mesa fuera *
                            </label>
                            <input type="number" 
                                   class="form-control" 
                                   id="mesa_fuera" 
                                   name="mesa_fuera" 
                                   min="1" 
                                   max="40" 
                                   value="<?php echo htmlspecialchars($referenciado['mesa_fuera'] ?? ''); ?>"
                                   <?php echo ($referenciado['vota_fuera'] ?? 'No') === 'Si' ? 'required' : ''; ?>>
                        </div>
                        
                        <!-- Campos para cuando NO vota fuera (inicialmente visibles) -->
                        <div class="form-group campo-votacion <?php echo ($referenciado['vota_fuera'] ?? 'No') === 'Si' ? 'hidden' : ''; ?>">
                            <label class="form-label">
                                <i class="fas fa-compass"></i> Zona *
                            </label>
                            <select class="form-control" name="id_zona" id="id_zona">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($zonas as $zona): ?>
                                    <option value="<?php echo $zona['id_zona']; ?>" 
                                        <?php echo isSelected($zona['id_zona'], $referenciado['id_zona'] ?? ''); ?>>
                                        <?php echo htmlspecialchars($zona['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group campo-votacion <?php echo ($referenciado['vota_fuera'] ?? 'No') === 'Si' ? 'hidden' : ''; ?>">
                            <label class="form-label">
                                <i class="fas fa-th-large"></i> Sector
                            </label>
                            <select class="form-control" name="id_sector" id="id_sector" <?php echo empty($referenciado['id_zona']) ? 'disabled' : ''; ?>>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($sectores as $sector): ?>
                                    <option value="<?php echo $sector['id_sector']; ?>" 
                                        <?php echo isSelected($sector['id_sector'], $referenciado['id_sector'] ?? ''); ?>>
                                        <?php echo htmlspecialchars($sector['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group campo-votacion <?php echo ($referenciado['vota_fuera'] ?? 'No') === 'Si' ? 'hidden' : ''; ?>">
                            <label class="form-label">
                                <i class="fas fa-vote-yea"></i> Puesto de votación
                            </label>
                            <select class="form-control" name="id_puesto_votacion" id="id_puesto_votacion" <?php echo empty($referenciado['id_sector']) ? 'disabled' : ''; ?>>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($puestos as $puesto): ?>
                                    <option value="<?php echo $puesto['id_puesto']; ?>" 
                                        <?php echo isSelected($puesto['id_puesto'], $referenciado['id_puesto_votacion'] ?? ''); ?>>
                                        <?php echo htmlspecialchars($puesto['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group campo-votacion <?php echo ($referenciado['vota_fuera'] ?? 'No') === 'Si' ? 'hidden' : ''; ?>">
                            <label class="form-label">
                                <i class="fas fa-table"></i> Mesa
                            </label>
                            <input type="number" 
                                   class="form-control" 
                                   name="mesa" 
                                   min="1" 
                                   value="<?php echo htmlspecialchars($referenciado['mesa'] ?? ''); ?>"
                                   id="mesa">
                        </div>
                    </div>
                    
                    <!-- Sección 4: Información Adicional -->
                    <div class="section-title">
                        <i class="fas fa-info-circle"></i> Información Adicional
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-heart"></i> Afinidad
                            </label>
                            <div class="estrellas-afinidad" id="afinidad-estrellas">
                                <input type="hidden" name="afinidad" id="afinidad-valor" value="<?php echo $referenciado['afinidad'] ?? 1; ?>">
                                <i class="fas fa-star estrella" data-value="1"></i>
                                <i class="fas fa-star estrella" data-value="2"></i>
                                <i class="fas fa-star estrella" data-value="3"></i>
                                <i class="fas fa-star estrella" data-value="4"></i>
                                <i class="fas fa-star estrella" data-value="5"></i>
                                <span class="valor-afinidad" id="valor-afinidad"><?php echo ($referenciado['afinidad'] ?? 1); ?>/5</span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-users"></i> Grupo poblacional
                            </label>
                            <select class="form-control" name="id_grupo_poblacional">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($grupos as $grupo): ?>
                                    <option value="<?php echo $grupo['id_grupo']; ?>" 
                                        <?php echo isSelected($grupo['id_grupo'], $referenciado['id_grupo_poblacional'] ?? ''); ?>>
                                        <?php echo htmlspecialchars($grupo['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-hands-helping"></i> Oferta de apoyo
                            </label>
                            <select class="form-control" name="id_oferta_apoyo">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($ofertas as $oferta): ?>
                                    <option value="<?php echo $oferta['id_oferta']; ?>" 
                                        <?php echo isSelected($oferta['id_oferta'], $referenciado['id_oferta_apoyo'] ?? ''); ?>>
                                        <?php echo htmlspecialchars($oferta['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group full-width">
                            <label class="form-label">
                                <i class="fas fa-comment-alt"></i> Compromiso
                            </label>
                            <textarea class="form-control" 
                                      name="compromiso" 
                                      rows="4"><?php echo htmlspecialchars($referenciado['compromiso'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <!-- Sección 5: Insumos Asignados -->
                    <div class="section-title">
                        <i class="fas fa-box-open"></i> Insumos Asignados
                    </div>

                    <div class="insumos-section">
                        <!-- Subsección: Insumos Actuales -->
                        <div style="margin-bottom: 30px;">
                            <h4 style="color: #4fc3f7; margin-bottom: 15px; font-size: 1.1rem; display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-check-circle"></i> Insumos Actualmente Asignados
                                <span style="background: rgba(79, 195, 247, 0.2); color: #4fc3f7; padding: 2px 8px; border-radius: 12px; font-size: 0.8rem; margin-left: 10px;">
                                    <?php echo count($insumos_referenciado); ?> asignados
                                </span>
                            </h4>
                            
                            <?php if (!empty($insumos_referenciado)): ?>
                                <div class="insumos-grid">
                                    <?php foreach ($insumos_referenciado as $insumo): ?>
                                        <div class="insumo-card-asignado">
                                            <div class="insumo-icon">
                                                <i class="fas fa-<?php 
                                                    $iconos = [
                                                        'carro' => 'car',
                                                        'caballo' => 'horse',
                                                        'cicla' => 'bicycle',
                                                        'moto' => 'motorcycle',
                                                        'motocarro' => 'truck-pickup',
                                                        'publicidad' => 'bullhorn'
                                                    ];
                                                    echo $iconos[strtolower($insumo['nombre'])] ?? 'box';
                                                ?>"></i>
                                            </div>
                                            <div class="insumo-info">
                                                <div class="insumo-name"><?php echo htmlspecialchars($insumo['nombre']); ?></div>
                                                <div class="insumo-details">
                                                    <?php if (!empty($insumo['cantidad'])): ?>
                                                        <span style="color: #90a4ae; font-size: 0.85rem;">
                                                            <i class="fas fa-hashtag"></i> Cantidad: <?php echo htmlspecialchars($insumo['cantidad']); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($insumo['observaciones'])): ?>
                                                        <div style="color: #90a4ae; font-size: 0.85rem; margin-top: 4px;">
                                                            <i class="fas fa-sticky-note"></i> <?php echo htmlspecialchars($insumo['observaciones']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="insumo-actions">
                                                <button type="button" class="btn-remove-insumo" 
                                                        data-insumo-id="<?php echo $insumo['id_insumo']; ?>"
                                                        title="Quitar insumo">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <input type="hidden" name="insumos_asignados[]" value="<?php echo $insumo['id_insumo']; ?>">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="no-insumos" style="text-align: center; padding: 20px; background: rgba(255, 255, 255, 0.03); border-radius: 8px;">
                                    <i class="fas fa-inbox" style="font-size: 2rem; color: #90a4ae; margin-bottom: 10px;"></i>
                                    <p style="color: #90a4ae;">Este referenciado no tiene insumos asignados</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Subsección: Agregar Nuevos Insumos -->
                        <div>
                            <h4 style="color: #4fc3f7; margin-bottom: 15px; font-size: 1.1rem; display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-plus-circle"></i> Agregar Nuevos Insumos
                                <span style="background: rgba(39, 174, 96, 0.2); color: #27ae60; padding: 2px 8px; border-radius: 12px; font-size: 0.8rem; margin-left: 10px;">
                                    <?php echo count($insumos_disponibles); ?> disponibles
                                </span>
                            </h4>
                            
                            <div class="insumos-grid">
                                <?php 
                                // Filtrar insumos que ya están asignados
                                $insumos_asignados_ids = array_column($insumos_referenciado, 'id_insumo');
                                ?>
                                
                                <?php foreach ($insumos_disponibles as $insumo): ?>
                                    <?php if (!in_array($insumo['id_insumo'], $insumos_asignados_ids)): ?>
                                        <div class="insumo-item-nuevo">
                                            <input type="checkbox" 
                                                class="insumo-checkbox" 
                                                id="insumo_<?php echo $insumo['id_insumo']; ?>" 
                                                name="insumos_nuevos[]" 
                                                value="<?php echo $insumo['id_insumo']; ?>">
                                            <label class="insumo-label" for="insumo_<?php echo $insumo['id_insumo']; ?>">
                                                <div class="insumo-card">
                                                    <div class="insumo-icon">
                                                        <i class="fas fa-<?php 
                                                            $iconos = [
                                                                'carro' => 'car',
                                                                'caballo' => 'horse',
                                                                'cicla' => 'bicycle',
                                                                'moto' => 'motorcycle',
                                                                'motocarro' => 'truck-pickup',
                                                                'publicidad' => 'bullhorn'
                                                            ];
                                                            echo $iconos[strtolower($insumo['nombre'])] ?? 'box';
                                                        ?>"></i>
                                                    </div>
                                                    <div class="insumo-info">
                                                        <div class="insumo-name"><?php echo htmlspecialchars($insumo['nombre']); ?></div>
                                                    </div>
                                                    <div class="insumo-check-indicator">
                                                        <i class="fas fa-check-circle"></i>
                                                    </div>
                                                </div>
                                            </label>
                                            <!-- Campos adicionales para cantidad y observaciones -->
                                            <div class="insumo-details-form" style="display: none; margin-top: 10px; padding: 10px; background: rgba(255, 255, 255, 0.05); border-radius: 8px;">
                                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                                    <div>
                                                        <label style="font-size: 0.8rem; color: #90a4ae; display: block; margin-bottom: 5px;">
                                                            <i class="fas fa-hashtag"></i> Cantidad
                                                        </label>
                                                        <input type="number" 
                                                            name="cantidad_<?php echo $insumo['id_insumo']; ?>" 
                                                            min="1" 
                                                            value="1"
                                                            class="form-control" 
                                                            style="padding: 6px 10px; font-size: 0.9rem;">
                                                    </div>
                                                    <div>
                                                        <label style="font-size: 0.8rem; color: #90a4ae; display: block; margin-bottom: 5px;">
                                                            <i class="fas fa-sticky-note"></i> Observaciones
                                                        </label>
                                                        <input type="text" 
                                                            name="observaciones_<?php echo $insumo['id_insumo']; ?>" 
                                                            class="form-control" 
                                                            style="padding: 6px 10px; font-size: 0.9rem;"
                                                            placeholder="Opcional">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                
                                <?php if (count($insumos_disponibles) == count($insumos_asignados_ids)): ?>
                                    <div style="grid-column: 1 / -1; text-align: center; padding: 20px;">
                                        <i class="fas fa-check-circle" style="font-size: 2rem; color: #27ae60; margin-bottom: 10px;"></i>
                                        <p style="color: #90a4ae;">Todos los insumos disponibles ya están asignados</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <!-- Sección 6: Información de Registro -->
                    <div class="section-title">
                        <i class="fas fa-history"></i> Información de Registro
                    </div>

                    <div class="form-grid">
                        <!-- Información del referenciador actual -->
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-user-tie"></i> Referenciador Actual
                            </label>
                            <div class="field-value">
                                <?php if ($referenciador): ?>
                                    <div class="referenciador-info">
                                        <div class="referenciador-name">
                                            <i class="fas fa-user-tie"></i>
                                            <?php echo htmlspecialchars($referenciador['nombres'] . ' ' . $referenciador['apellidos']); ?>
                                        </div>
                                        <div class="timestamp-info">
                                            <i class="fas fa-id-card"></i> 
                                            Cédula: <?php echo htmlspecialchars($referenciador['cedula'] ?? 'N/A'); ?>
                                        </div>
                                        <div class="timestamp-info">
                                            <i class="fas fa-chart-bar"></i> 
                                            Referenciados: <?php echo htmlspecialchars($referenciador['total_referenciados'] ?? 0); ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="na-text">N/A</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Campo para cambiar referenciador -->
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-exchange-alt"></i> Cambiar Referenciador
                                <span style="color: #ff9800; font-size: 0.8em; margin-left: 5px;">(Opcional)</span>
                            </label>
                            <select class="form-control" name="id_referenciador" id="id_referenciador">
                                <option value="">-- Mantener referenciador actual --</option>
                                <?php 
                                // Obtener todos los referenciadores activos
                                $referenciadores = $usuarioModel->getReferenciadoresActivos();
                                foreach ($referenciadores as $ref): 
                                    // Excluir al referenciador actual de la lista
                                    if ($ref['id_usuario'] == ($referenciado['id_referenciador'] ?? 0)) continue;
                                ?>
                                    <option value="<?php echo $ref['id_usuario']; ?>" 
                                        data-nombre="<?php echo htmlspecialchars($ref['nombres'] . ' ' . $ref['apellidos']); ?>"
                                        data-cedula="<?php echo htmlspecialchars($ref['cedula'] ?? ''); ?>"
                                        data-tipo="<?php echo htmlspecialchars($ref['tipo_usuario']); ?>"
                                        data-tope="<?php echo htmlspecialchars($ref['tope'] ?? 0); ?>"
                                        data-referenciados="<?php echo htmlspecialchars($ref['total_referenciados'] ?? 0); ?>">
                                        <?php echo htmlspecialchars($ref['nombres'] . ' ' . $ref['apellidos']); ?> 
                                        (<?php echo htmlspecialchars($ref['cedula'] ?? 'N/A'); ?>)
                                        <?php if (isset($ref['total_referenciados'])): ?>
                                            - <?php echo htmlspecialchars($ref['total_referenciados']); ?> ref.
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small style="display: block; color: #90a4ae; font-size: 0.8em; margin-top: 5px;">
                                <i class="fas fa-info-circle"></i> Solo referenciadores activos
                            </small>
                            
                            <!-- Preview del nuevo referenciador -->
                            <div id="nuevo-referenciador-preview" style="display: none; margin-top: 10px; padding: 8px; background: rgba(39, 174, 96, 0.1); border-radius: 5px; border-left: 3px solid #27ae60;">
                                <div style="color: #27ae60; font-size: 0.85em; margin-bottom: 5px;">
                                    <i class="fas fa-check-circle"></i> <strong>Nuevo referenciador:</strong>
                                </div>
                                <div id="preview-info" style="color: #e2e8f0; font-size: 0.85em;"></div>
                            </div>
                        </div>
                    </div>
                    <!-- Botones de Acción -->
                    <div class="form-actions">
                        <a href="ver_referenciado.php?id=<?php echo $id_referenciado; ?>" class="cancel-btn">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                        <button type="submit" class="save-btn">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
   // Script para las estrellas de afinidad
document.addEventListener('DOMContentLoaded', function() {
    const estrellas = document.querySelectorAll('.estrellas-afinidad .estrella');
    const valorInput = document.getElementById('afinidad-valor');
    const valorSpan = document.getElementById('valor-afinidad');
    
    // Datos del referenciado actual (pasados desde PHP)
    const zonaActual = '<?php echo $referenciado["id_zona"] ?? ""; ?>';
    const sectorActual = '<?php echo $referenciado["id_sector"] ?? ""; ?>';
    const puestoActual = '<?php echo $referenciado["id_puesto_votacion"] ?? ""; ?>';
    const votaFueraActual = '<?php echo $referenciado["vota_fuera"] ?? "No"; ?>';
    
    // Referencias a los elementos del DOM
    const zonaSelect = document.getElementById('id_zona');
    const sectorSelect = document.getElementById('id_sector');
    const puestoSelect = document.getElementById('id_puesto_votacion');
    const votaFueraSwitch = document.getElementById('vota_fuera_switch');
    const votaFueraHidden = document.getElementById('vota_fuera');
    const camposFuera = document.querySelectorAll('.campo-fuera');
    const camposVotacion = document.querySelectorAll('.campo-votacion');
    
    // Establecer estrellas iniciales
    const valorInicial = parseInt(valorInput.value);
    actualizarEstrellas(valorInicial);
    
    // Agregar event listeners a las estrellas
    estrellas.forEach(estrella => {
        estrella.addEventListener('click', function() {
            const valor = parseInt(this.getAttribute('data-value'));
            valorInput.value = valor;
            actualizarEstrellas(valor);
        });
    });
    
    function actualizarEstrellas(valor) {
        estrellas.forEach(estrella => {
            const estrellaValor = parseInt(estrella.getAttribute('data-value'));
            if (estrellaValor <= valor) {
                estrella.classList.add('active');
                estrella.classList.remove('far');
                estrella.classList.add('fas');
            } else {
                estrella.classList.remove('active');
                estrella.classList.remove('fas');
                estrella.classList.add('far');
            }
        });
        valorSpan.textContent = valor + '/5';
    }
    
    // ============ FUNCIONES PARA DEPENDENCIAS DE ZONA-SECTOR-PUESTO ============
    
    // Función para cargar sectores según la zona seleccionada
    function cargarSectoresPorZona(zonaId, sectorSeleccionado = null) {
        if (!zonaId || zonaId === '') {
            sectorSelect.innerHTML = '<option value="">Seleccionar...</option>';
            sectorSelect.disabled = true;
            sectorSelect.required = false;
            puestoSelect.innerHTML = '<option value="">Seleccionar...</option>';
            puestoSelect.disabled = true;
            puestoSelect.required = false;
            return;
        }

        // Mostrar indicador de carga
        sectorSelect.innerHTML = '<option value="">Cargando sectores...</option>';
        sectorSelect.disabled = true;

        // Hacer petición AJAX a tu endpoint existente
        fetch(`../ajax/cargar_sectores.php?zona_id=${zonaId}`)
            .then(response => response.json())
            .then(data => {
                sectorSelect.innerHTML = '<option value="">Seleccionar...</option>';
                
                if (data.success && data.sectores && data.sectores.length > 0) {
                    data.sectores.forEach(sector => {
                        const option = document.createElement('option');
                        option.value = sector.id_sector;
                        option.textContent = sector.nombre;
                        
                        // Seleccionar si es el valor del referenciado o el pasado como parámetro
                        if ((sectorSeleccionado && sector.id_sector == sectorSeleccionado)) {
                            option.selected = true;
                        }
                        
                        sectorSelect.appendChild(option);
                    });
                    
                    sectorSelect.disabled = false;
                    sectorSelect.required = false;
                    
                    // Si se seleccionó un sector automáticamente, cargar sus puestos
                    if (sectorSeleccionado) {
                        cargarPuestosPorSector(sectorSeleccionado);
                    }
                } else {
                    sectorSelect.innerHTML += '<option value="" disabled>No hay sectores para esta zona</option>';
                    sectorSelect.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error cargando sectores:', error);
                sectorSelect.innerHTML = '<option value="">Error cargando sectores</option>';
                sectorSelect.disabled = true;
            });
    }

    // Función para cargar puestos según el sector seleccionado
    function cargarPuestosPorSector(sectorId, puestoSeleccionado = null) {
        if (!sectorId || sectorId === '') {
            puestoSelect.innerHTML = '<option value="">Seleccionar...</option>';
            puestoSelect.disabled = true;
            puestoSelect.required = false;
            return;
        }

        // Mostrar indicador de carga
        puestoSelect.innerHTML = '<option value="">Cargando puestos...</option>';
        puestoSelect.disabled = true;

        // Hacer petición AJAX a tu endpoint existente
        fetch(`../ajax/cargar_puestos.php?sector_id=${sectorId}`)
            .then(response => response.json())
            .then(data => {
                puestoSelect.innerHTML = '<option value="">Seleccionar...</option>';
                
                if (data.success && data.puestos && data.puestos.length > 0) {
                    data.puestos.forEach(puesto => {
                        const option = document.createElement('option');
                        option.value = puesto.id_puesto;
                        
                        // Formatear texto del puesto con número de mesas
                        let texto = puesto.nombre;
                        if (puesto.num_mesas !== undefined) {
                            if (puesto.num_mesas === 0) {
                                texto += ' (Sin mesas)';
                                option.style.color = '#e67e22';
                            } else {
                                texto += ` (${puesto.num_mesas} mesa${puesto.num_mesas !== 1 ? 's' : ''})`;
                                option.style.color = '#27ae60';
                            }
                        }
                        
                        option.textContent = texto;
                        option.setAttribute('data-mesas', puesto.num_mesas || 0);
                        
                        // Seleccionar si es el valor del referenciado
                        if (puestoSeleccionado && puesto.id_puesto == puestoSeleccionado) {
                            option.selected = true;
                        }
                        
                        puestoSelect.appendChild(option);
                    });
                    
                    puestoSelect.disabled = false;
                    puestoSelect.required = false;
                } else {
                    puestoSelect.innerHTML += '<option value="" disabled>No hay puestos para este sector</option>';
                    puestoSelect.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error cargando puestos:', error);
                puestoSelect.innerHTML = '<option value="">Error cargando puestos</option>';
                puestoSelect.disabled = true;
            });
    }

    // Función para inicializar las dependencias cuando la página carga
    function inicializarDependencias() {
        // Si el referenciado vota fuera, no cargar dependencias (los campos estarán ocultos)
        if (votaFueraActual === 'Si') {
            console.log('Referenciado vota fuera, dependencias no aplican');
            return;
        }
        
        // Si hay una zona seleccionada en el referenciado, cargar sus sectores
        if (zonaActual) {
            console.log('Inicializando con zona:', zonaActual, 'sector:', sectorActual);
            cargarSectoresPorZona(zonaActual, sectorActual);
        }
    }
    
    // ============ EVENT LISTENERS PARA DEPENDENCIAS ============
    
    // Cuando cambia la zona
    zonaSelect.addEventListener('change', function() {
        const zonaId = this.value;
        
        // Solo procesar si el campo está visible (NO vota fuera)
        const zonaContainer = this.closest('.campo-votacion');
        if (zonaContainer && zonaContainer.classList.contains('hidden')) {
            return;
        }
        
        cargarSectoresPorZona(zonaId);
        
        // Limpiar puesto si cambia la zona
        puestoSelect.innerHTML = '<option value="">Seleccionar...</option>';
        puestoSelect.disabled = true;
        puestoSelect.required = false;
    });
    
    // Cuando cambia el sector
    sectorSelect.addEventListener('change', function() {
        const sectorId = this.value;
        
        // Solo procesar si el campo está visible (NO vota fuera)
        const sectorContainer = this.closest('.campo-votacion');
        if (sectorContainer && sectorContainer.classList.contains('hidden')) {
            return;
        }
        
        cargarPuestosPorSector(sectorId);
    });
    
    // ============ MANEJO DEL SWITCH VOTA FUERA (EXISTENTE - NO MODIFICAR) ============
    
    function toggleCamposVotacion() {
        const votaFuera = votaFueraSwitch.checked ? 'Si' : 'No';
        votaFueraHidden.value = votaFuera;
        
        if (votaFuera === 'Si') {
            // Mostrar campos fuera, ocultar campos normales
            camposFuera.forEach(campo => {
                campo.classList.remove('hidden');
                const input = campo.querySelector('input');
                if (input) input.required = true;
            });
            
            camposVotacion.forEach(campo => {
                campo.classList.add('hidden');
                const input = campo.querySelector('input, select');
                if (input) {
                    input.required = false;
                    // NO deshabilitar el campo mesa
                    if (input.name !== 'mesa') {
                        input.disabled = true;
                    }
                }
            });
        } else {
            // Mostrar campos normales, ocultar campos fuera
            camposFuera.forEach(campo => {
                campo.classList.add('hidden');
                const input = campo.querySelector('input');
                if (input) input.required = false;
            });
            
            camposVotacion.forEach(campo => {
                campo.classList.remove('hidden');
                const input = campo.querySelector('input, select');
                if (input) {
                    // Solo el campo zona es requerido
                    if (input.name === 'id_zona') {
                        input.required = true;
                        input.disabled = false;
                        
                        // Si hay una zona seleccionada, cargar sus sectores
                        if (input.value && votaFuera === 'No') {
                            setTimeout(() => {
                                cargarSectoresPorZona(input.value, sectorActual);
                            }, 100);
                        }
                    } else {
                        input.required = false;
                        input.disabled = false; // IMPORTANTE: habilitar todos los campos
                    }
                }
            });
        }
    }
    
    // Inicializar estado del switch Vota Fuera
    toggleCamposVotacion();
    
    // Agregar evento change al switch (ya existente)
    votaFueraSwitch.addEventListener('change', toggleCamposVotacion);
    
    // Inicializar dependencias cuando el DOM esté listo
    setTimeout(() => {
        inicializarDependencias();
    }, 100);
    
    // ============ RESTO DEL CÓDIGO EXISTENTE (NO MODIFICAR) ============
    
    // Manejar botones de eliminar insumo
    document.querySelectorAll('.btn-remove-insumo').forEach(btn => {
        btn.addEventListener('click', function() {
            const insumoId = this.getAttribute('data-insumo-id');
            const insumoCard = this.closest('.insumo-card-asignado');
            
            if (confirm('¿Está seguro de quitar este insumo?')) {
                // Crear campo hidden para marcar el insumo como eliminado
                const deleteInput = document.createElement('input');
                deleteInput.type = 'hidden';
                deleteInput.name = 'insumos_eliminar[]';
                deleteInput.value = insumoId;
                document.querySelector('form').appendChild(deleteInput);
                
                // Eliminar visualmente el card
                insumoCard.style.opacity = '0.5';
                insumoCard.style.transform = 'translateX(-20px)';
                
                setTimeout(() => {
                    insumoCard.remove();
                    showNotification('Insumo marcado para eliminar. Guarde los cambios para aplicar.', 'warning');
                    
                    // Actualizar contador de insumos asignados
                    actualizarContadorInsumos();
                }, 300);
            }
        });
    });
    
    // Mostrar/ocultar campos de detalle al seleccionar insumos nuevos
    document.querySelectorAll('.insumo-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const detailsForm = this.parentElement.querySelector('.insumo-details-form');
            if (detailsForm) {
                if (this.checked) {
                    detailsForm.style.display = 'block';
                } else {
                    detailsForm.style.display = 'none';
                }
            }
        });
    });
    
    // Función para actualizar contador de insumos asignados
    function actualizarContadorInsumos() {
        const insumosAsignados = document.querySelectorAll('.insumo-card-asignado').length;
        const contadorElement = document.querySelector('.insumos-asignados-contador');
        if (contadorElement) {
            contadorElement.textContent = insumosAsignados + ' asignados';
        }
    }
    
    // Mostrar notificaciones
    function showNotification(message, type = 'info') {
        // Eliminar notificación anterior si existe
        const oldNotification = document.querySelector('.notification-temp');
        if (oldNotification) {
            oldNotification.remove();
        }
        
        const notification = document.createElement('div');
        notification.className = `notification-temp notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            </div>
            <button class="notification-close">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        // Estilos para la notificación
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-width: 300px;
            max-width: 400px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 2000;
            animation: slideIn 0.3s ease-out;
            background: ${type === 'warning' ? '#fff3cd' : 
                        type === 'error' ? '#f8d7da' : 
                        type === 'success' ? '#d4edda' : '#d1ecf1'};
            color: ${type === 'warning' ? '#856404' : 
                    type === 'error' ? '#721c24' : 
                    type === 'success' ? '#155724' : '#0c5460'};
            border: 1px solid ${type === 'warning' ? '#ffeaa7' : 
                          type === 'error' ? '#f5c6cb' : 
                          type === 'success' ? '#c3e6cb' : '#bee5eb'};
        `;
        
        document.body.appendChild(notification);
        
        // Botón para cerrar
        notification.querySelector('.notification-close').addEventListener('click', () => {
            notification.remove();
        });
        
        // Auto-eliminar después de 5 segundos
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
        
        // Animación slideIn
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
        `;
        document.head.appendChild(style);
    }
    
    // Validación del formulario
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        // Limpiar estilos de error previos
        document.querySelectorAll('.form-control').forEach(field => {
            field.style.borderColor = '';
        });
        
        // Validar cédula (solo números)
        const cedula = document.querySelector('input[name="cedula"]').value;
        if (!/^\d+$/.test(cedula)) {
            e.preventDefault();
            showNotification('La cédula debe contener solo números', 'warning');
            document.querySelector('input[name="cedula"]').style.borderColor = '#e74c3c';
            document.querySelector('input[name="cedula"]').focus();
            return false;
        }
        
        // Validar afinidad (1-5)
        const afinidad = parseInt(document.getElementById('afinidad-valor').value);
        if (afinidad < 1 || afinidad > 5) {
            e.preventDefault();
            showNotification('La afinidad debe estar entre 1 y 5', 'warning');
            return false;
        }
        
        // Validar email si está presente
        const email = document.querySelector('input[name="email"]').value;
        if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            e.preventDefault();
            showNotification('Por favor ingrese un email válido', 'warning');
            document.querySelector('input[name="email"]').style.borderColor = '#e74c3c';
            document.querySelector('input[name="email"]').focus();
            return false;
        }
        
        // Validar teléfono si está presente (solo números, mínimo 7 dígitos)
        const telefono = document.querySelector('input[name="telefono"]').value;
        if (telefono && !/^\d{7,15}$/.test(telefono)) {
            e.preventDefault();
            showNotification('El teléfono debe contener entre 7 y 15 dígitos numéricos', 'warning');
            document.querySelector('input[name="telefono"]').style.borderColor = '#e74c3c';
            document.querySelector('input[name="telefono"]').focus();
            return false;
        }
        
        // Validaciones condicionales según si vota fuera o no
        const votaFuera = document.getElementById('vota_fuera').value;
        
        if (votaFuera === 'Si') {
            // Validar campos de votación fuera
            const puestoFuera = document.getElementById('puesto_votacion_fuera');
            const mesaFuera = document.getElementById('mesa_fuera');
            
            if (!puestoFuera.value.trim()) {
                e.preventDefault();
                showNotification('El puesto de votación fuera es obligatorio cuando vota fuera', 'warning');
                puestoFuera.style.borderColor = '#e74c3c';
                puestoFuera.focus();
                return false;
            }
            
            if (!mesaFuera.value || parseInt(mesaFuera.value) < 1) {
                e.preventDefault();
                showNotification('La mesa fuera es obligatoria y debe ser mayor a 0', 'warning');
                mesaFuera.style.borderColor = '#e74c3c';
                mesaFuera.focus();
                return false;
            }
            
            if (parseInt(mesaFuera.value) > 40) {
                e.preventDefault();
                showNotification('La mesa fuera no puede ser mayor a 40', 'warning');
                mesaFuera.style.borderColor = '#e74c3c';
                mesaFuera.focus();
                return false;
            }
        } else {
            // Validar campos de votación normal
            const zona = document.getElementById('id_zona');
            if (!zona.value) {
                e.preventDefault();
                showNotification('La zona es obligatoria cuando NO vota fuera', 'warning');
                zona.style.borderColor = '#e74c3c';
                zona.focus();
                return false;
            }
            
            // Validar mesa normal si está presente
            const mesa = document.querySelector('input[name="mesa"]');
            if (mesa.value && (parseInt(mesa.value) < 1 || isNaN(parseInt(mesa.value)))) {
                e.preventDefault();
                showNotification('La mesa debe ser un número positivo', 'warning');
                mesa.style.borderColor = '#e74c3c';
                mesa.focus();
                return false;
            }
        }
        
        // Validar que los campos requeridos no estén vacíos
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;
        let firstInvalidField = null;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.style.borderColor = '#e74c3c';
                if (!firstInvalidField) {
                    firstInvalidField = field;
                }
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            showNotification('Por favor complete todos los campos requeridos (*)', 'warning');
            if (firstInvalidField) {
                firstInvalidField.focus();
            }
            return false;
        }
        
        // Validar que se haya seleccionado al menos una opción válida en selects si tienen valor
        const selects = form.querySelectorAll('select.form-control');
        selects.forEach(select => {
            if (select.value && select.value !== '' && select.options[select.selectedIndex].value === '') {
                e.preventDefault();
                showNotification('Por favor seleccione una opción válida para ' + select.previousElementSibling.textContent, 'warning');
                select.style.borderColor = '#e74c3c';
                select.focus();
                return false;
            }
        });
        // En la validación del formulario, agrega esto:
        const grupoParlamentario = document.querySelector('select[name="id_grupo"]');
        if (!grupoParlamentario.value) {
            e.preventDefault();
            showNotification('El Grupo Parlamentario es obligatorio', 'warning');
            grupoParlamentario.style.borderColor = '#e74c3c';
            grupoParlamentario.focus();
            return false;
        }
        
        // Mostrar mensaje de confirmación
        if (!confirm('¿Está seguro de guardar los cambios?')) {
            e.preventDefault();
            return false;
        }
        
        // Mostrar indicador de carga
        const saveBtn = form.querySelector('.save-btn');
        const originalText = saveBtn.innerHTML;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
        saveBtn.disabled = true;
        
        // Restaurar botón después de 3 segundos (por si hay error en el servidor)
        setTimeout(() => {
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
        }, 3000);
        
        return true;
    });
    
    // Mostrar mensaje de éxito si existe
    <?php if ($success_message): ?>
        setTimeout(() => {
            showNotification('<?php echo addslashes($success_message); ?>', 'success');
        }, 500);
    <?php endif; ?>
    
    // Mostrar mensaje de error si existe
    <?php if ($error_message): ?>
        setTimeout(() => {
            showNotification('<?php echo addslashes($error_message); ?>', 'error');
        }, 500);
    <?php endif; ?>
    
    // Mejorar experiencia de usuario en campos numéricos
    const numericFields = document.querySelectorAll('input[type="number"]');
    numericFields.forEach(field => {
        field.addEventListener('input', function() {
            if (this.value < 0) {
                this.value = Math.abs(this.value);
            }
        });
    });
    
    // Agregar efecto de hover a todos los cards de insumos
    document.querySelectorAll('.insumo-card, .insumo-card-asignado').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px)';
            this.style.transition = 'all 0.3s ease';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Inicializar contador de insumos
    actualizarContadorInsumos();
});
    </script>
</body>
</html>