<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/ReferenciadoModel.php';

// Verificar sesión
if (!isset($_SESSION['id_usuario'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

// Obtener conexión
$pdo = Database::getConnection();
$referenciadoModel = new ReferenciadoModel($pdo);

// Recoger y sanitizar datos del formulario
$data = [
    'nombre' => trim($_POST['nombre'] ?? ''),
    'apellido' => trim($_POST['apellido'] ?? ''),
    'cedula' => trim($_POST['cedula'] ?? ''),
    'direccion' => trim($_POST['direccion'] ?? ''),
    'email' => trim($_POST['email'] ?? ''),
    'telefono' => trim($_POST['telefono'] ?? ''),
    'sexo' => trim($_POST['sexo'] ?? ''), // NUEVO CAMPO
    'vota_fuera' => trim($_POST['vota_fuera'] ?? 'No'), // 'Si' o 'No'
    'puesto_votacion_fuera' => trim($_POST['puesto_votacion_fuera'] ?? ''), // NUEVO CAMPO
    'mesa_fuera' => !empty($_POST['mesa_fuera']) ? intval($_POST['mesa_fuera']) : null, // NUEVO CAMPO
    'id_grupo' => !empty($_POST['grupo_parlamentario']) ? intval($_POST['grupo_parlamentario']) : null, // NUEVO CAMPO: id_grupo (cambiado de 'campo' a 'grupo')
    'afinidad' => intval($_POST['afinidad'] ?? 0),
    'id_zona' => !empty($_POST['zona']) ? intval($_POST['zona']) : null,
    'id_sector' => !empty($_POST['sector']) ? intval($_POST['sector']) : null,
    'id_puesto_votacion' => !empty($_POST['puesto_votacion']) ? intval($_POST['puesto_votacion']) : null,
    'mesa' => !empty($_POST['mesa']) ? intval($_POST['mesa']) : null,
    'id_departamento' => !empty($_POST['departamento']) ? intval($_POST['departamento']) : null,
    'id_municipio' => !empty($_POST['municipio']) ? intval($_POST['municipio']) : null,
    'id_barrio' => !empty($_POST['barrio']) ? intval($_POST['barrio']) : null,
    'id_oferta_apoyo' => !empty($_POST['apoyo']) ? intval($_POST['apoyo']) : null,
    'id_grupo_poblacional' => !empty($_POST['grupo_poblacional']) ? intval($_POST['grupo_poblacional']) : null,
    'compromiso' => trim($_POST['compromiso'] ?? ''),
    'id_referenciador' => $_POST['id_referenciador'] ?? $_SESSION['id_usuario'],
    'insumos' => $_POST['insumos'] ?? [] // Array de insumos seleccionados
];

// Debug: Ver qué datos llegan
error_log("Datos recibidos: " . print_r($data, true));
error_log("Vota fuera: " . $data['vota_fuera']);
error_log("Puesto votación fuera: " . $data['puesto_votacion_fuera']);
error_log("Mesa fuera: " . $data['mesa_fuera']);
error_log("ID Grupo: " . $data['id_grupo']); // NUEVO: debug grupo (cambiado de campo)
error_log("Insumos recibidos: " . print_r($data['insumos'], true));

// Validaciones básicas
$errors = [];
if (empty($data['nombre'])) $errors[] = 'El nombre es obligatorio';
if (empty($data['apellido'])) $errors[] = 'El apellido es obligatorio';
if (empty($data['cedula'])) $errors[] = 'La cédula es obligatoria';
if (empty($data['direccion'])) $errors[] = 'La dirección es obligatoria';
if (empty($data['email'])) $errors[] = 'El email es obligatorio';
if (empty($data['telefono'])) $errors[] = 'El teléfono es obligatorio';

// Validar afinidad (DEBE ser entre 1 y 5 según la tabla)
if ($data['afinidad'] < 1 || $data['afinidad'] > 5) {
    $errors[] = 'La afinidad debe estar entre 1 y 5';
}

// Validar cédula (solo números)
if (!empty($data['cedula']) && !preg_match('/^\d+$/', $data['cedula'])) {
    $errors[] = 'La cédula solo debe contener números';
}

// Validar email
if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'El email no tiene un formato válido';
}

// Validar sexo (si se seleccionó)
if (!empty($data['sexo']) && !in_array($data['sexo'], ['Masculino', 'Femenino', 'Otro'])) {
    $errors[] = 'El sexo seleccionado no es válido';
}

// Validar vota_fuera (debe ser 'Si' o 'No')
if (!in_array($data['vota_fuera'], ['Si', 'No'])) {
    $errors[] = 'El campo "Vota Fuera" debe ser Si o No';
}

// Validar grupo (si se seleccionó) - NUEVO: cambiado de campo a grupo
if (empty($data['id_grupo'])) {
    $errors[] = 'El Grupo Parlamentario es obligatorio';
}

// Validaciones condicionales según si vota fuera o no
if ($data['vota_fuera'] === 'Si') {
    // Validar campos específicos cuando vota fuera
    if (empty($data['puesto_votacion_fuera'])) {
        $errors[] = 'El puesto de votación fuera es obligatorio cuando el referido vota fuera';
    }
    if (empty($data['mesa_fuera']) || $data['mesa_fuera'] < 1) {
        $errors[] = 'El número de mesa fuera es obligatorio y debe ser mayor a 0';
    }
    if ($data['mesa_fuera'] > 40) {
        $errors[] = 'El número de mesa fuera no puede ser mayor a 40';
    }
} else {
    // Validar campos específicos cuando NO vota fuera
    if (empty($data['id_zona'])) {
        $errors[] = 'La zona es obligatoria cuando el referido NO vota fuera';
    }
    // Validar mesa normal si se seleccionó puesto de votación
    if (!empty($data['id_puesto_votacion']) && empty($data['mesa'])) {
        $errors[] = 'El número de mesa es obligatorio cuando se selecciona un puesto de votación';
    }
}

// Si hay errores, retornarlos
if (!empty($errors)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit();
}

// Validar que el usuario referenciador existe
try {
    $stmt = $pdo->prepare("SELECT id_usuario, tipo_usuario FROM usuario WHERE id_usuario = ?");
    $stmt->execute([$data['id_referenciador']]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        echo json_encode(['success' => false, 'message' => 'Usuario referenciador no encontrado']);
        exit();
    }
    
    if ($usuario['tipo_usuario'] !== 'Referenciador') {
        echo json_encode(['success' => false, 'message' => 'Usuario no autorizado para referenciar']);
        exit();
    }
    
} catch (Exception $e) {
    error_log("Error verificando usuario: " . $e->getMessage());
}

// Verificar si barrio existe (si se seleccionó)
if (!empty($data['id_barrio'])) {
    try {
        $stmt = $pdo->prepare("SELECT id_barrio FROM barrio WHERE id_barrio = ? AND activo = true");
        $stmt->execute([$data['id_barrio']]);
        if (!$stmt->fetch()) {
            $data['id_barrio'] = null; // Si no existe, establecer como null
        }
    } catch (Exception $e) {
        error_log("Error verificando barrio: " . $e->getMessage());
        $data['id_barrio'] = null;
    }
}

// Verificar si grupo existe (si se seleccionó) - NUEVO: cambiado de campo a grupo
if (!empty($data['id_grupo'])) {
    try {
        $stmt = $pdo->prepare("SELECT id_grupo FROM grupos_parlamentarios WHERE id_grupo = ?");
        $stmt->execute([$data['id_grupo']]);
        if (!$stmt->fetch()) {
            $data['id_grupo'] = null; // Si no existe, establecer como null
            error_log("Grupo con ID " . $data['id_grupo'] . " no encontrado o inactivo");
        }
    } catch (Exception $e) {
        error_log("Error verificando grupo: " . $e->getMessage());
        $data['id_grupo'] = null;
    }
}

// Verificar insumos válidos
$insumosValidos = ['carro', 'caballo', 'cicla', 'moto', 'motocarro', 'publicidad'];
if (!empty($data['insumos']) && is_array($data['insumos'])) {
    // Filtrar solo insumos válidos
    $data['insumos'] = array_filter($data['insumos'], function($insumo) use ($insumosValidos) {
        return in_array($insumo, $insumosValidos);
    });
    
    // Si quedan insumos válidos, convertir a array numérico
    if (!empty($data['insumos'])) {
        $data['insumos'] = array_values($data['insumos']);
    }
}

// Debug: Mostrar datos que se enviarán al modelo
error_log("Datos a enviar al modelo: " . print_r($data, true));

try {
    // Guardar el referenciado (ahora retorna el ID en lugar de true/false)
    $id_referenciado = $referenciadoModel->guardarReferenciado($data);
    
    if ($id_referenciado) {
        $mensaje = 'Referenciado guardado exitosamente';
        
        // Agregar info sobre insumos guardados
        if (!empty($data['insumos']) && is_array($data['insumos'])) {
            $mensaje .= ' con ' . count($data['insumos']) . ' insumo(s)';
        }
        
        // Agregar info sobre tipo de votación
        if ($data['vota_fuera'] === 'Si') {
            $mensaje .= '. Referido que vota fuera.';
        } else {
            $mensaje .= '. Referido que vota en su lugar de residencia.';
        }
        
        // Agregar info sobre grupo si se seleccionó - NUEVO: cambiado de campo a grupo
        if (!empty($data['id_grupo'])) {
            try {
                $stmt = $pdo->prepare("SELECT nombre FROM grupos_parlamentarios WHERE id_grupo = ?");
                $stmt->execute([$data['id_grupo']]);
                $grupo = $stmt->fetch();
                if ($grupo) {
                    $mensaje .= ' Grupo: ' . $grupo['nombre'] . '.';
                }
            } catch (Exception $e) {
                error_log("Error obteniendo nombre del grupo: " . $e->getMessage());
            }
        }
        
        echo json_encode([
            'success' => true, 
            'message' => $mensaje,
            'id_referenciado' => $id_referenciado,
            'vota_fuera' => $data['vota_fuera']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar el referenciado']);
    }
} catch (Exception $e) {
    error_log("Error en guardar_referenciado: " . $e->getMessage());
    
    // Verificar si es error de duplicado de cédula
    $errorMessage = $e->getMessage();
    if (strpos($errorMessage, 'duplicate key') !== false || 
        strpos($errorMessage, 'Duplicate entry') !== false ||
        strpos($errorMessage, '23505') !== false ||
        strpos($errorMessage, 'cedula') !== false ||
        strpos(strtolower($errorMessage), 'unique constraint') !== false) {
        
        echo json_encode(['success' => false, 'message' => 'La cédula ya está registrada']);
    } else {
        // Mensaje más amigable para el usuario
        $mensajeError = 'Error al guardar el registro. ';
        
        // Intentar hacer el mensaje más específico
        if (strpos($errorMessage, 'puesto de votación fuera') !== false) {
            $mensajeError .= 'Verifique los datos de votación fuera.';
        } elseif (strpos($errorMessage, 'zona') !== false) {
            $mensajeError .= 'Verifique los datos de ubicación de votación.';
        } elseif (strpos($errorMessage, 'grupo') !== false) {
            $mensajeError .= 'Verifique los datos del grupo.'; // NUEVO: cambiado de campo a grupo
        } else {
            $mensajeError .= 'Por favor intente nuevamente.';
        }
        
        echo json_encode(['success' => false, 'message' => $mensajeError]);
    }
}

header('Content-Type: application/json');
?>