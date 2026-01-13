<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';

// Verificar permisos (solo administradores pueden editar usuarios)
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'Administrador') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit();
}

// Configurar cabeceras para JSON
header('Content-Type: application/json');

try {
    $pdo = Database::getConnection();
    $usuarioModel = new UsuarioModel($pdo);
    
    // Obtener datos del usuario logueado
    $usuario_logueado = $usuarioModel->getUsuarioById($_SESSION['id_usuario']);
    
    // Validar datos recibidos
    if (!isset($_POST['id_usuario']) || !is_numeric($_POST['id_usuario'])) {
        echo json_encode(['success' => false, 'message' => 'ID de usuario inválido']);
        exit();
    }
    
    $id_usuario = intval($_POST['id_usuario']);
    
    // Verificar que el usuario a editar existe
    $usuario_a_editar = $usuarioModel->getUsuarioById($id_usuario);
    if (!$usuario_a_editar) {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        exit();
    }
    
    // Determinar si se está editando a sí mismo
    $editando_a_si_mismo = ($usuario_logueado['id_usuario'] == $id_usuario);
    
    // Validar campos obligatorios
    $campos_obligatorios = ['nombres', 'apellidos', 'cedula', 'nickname', 'correo', 'telefono', 'tipo_usuario'];
    $errores = [];
    
    foreach ($campos_obligatorios as $campo) {
        if (!isset($_POST[$campo]) || trim($_POST[$campo]) === '') {
            $errores[] = "El campo '$campo' es obligatorio";
        }
    }
    
    if (!empty($errores)) {
        echo json_encode(['success' => false, 'message' => 'Errores de validación', 'errors' => $errores]);
        exit();
    }
    
    // Validar formato de cédula (6-10 dígitos)
    $cedula = preg_replace('/\D/', '', $_POST['cedula']);
    if (strlen($cedula) < 6 || strlen($cedula) > 10) {
        echo json_encode(['success' => false, 'message' => 'La cédula debe tener entre 6 y 10 dígitos']);
        exit();
    }
    
    // Validar formato de teléfono (exactamente 10 dígitos)
    $telefono = preg_replace('/\D/', '', $_POST['telefono']);
    if (strlen($telefono) !== 10) {
        echo json_encode(['success' => false, 'message' => 'El teléfono debe tener exactamente 10 dígitos']);
        exit();
    }
    
    // Validar formato de email
    if (!filter_var($_POST['correo'], FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'El correo electrónico no es válido']);
        exit();
    }
    
    // Validar nickname (mínimo 4 caracteres)
    if (strlen($_POST['nickname']) < 4) {
        echo json_encode(['success' => false, 'message' => 'El nombre de usuario debe tener al menos 4 caracteres']);
        exit();
    }
    
    // Verificar si el nickname ya está en uso por otro usuario
    if ($usuarioModel->nicknameExiste($_POST['nickname'], $id_usuario)) {
        echo json_encode(['success' => false, 'message' => 'El nombre de usuario ya está en uso']);
        exit();
    }
    
    // Verificar si la cédula ya está en uso por otro usuario
    if ($usuarioModel->cedulaExiste($cedula, $id_usuario)) {
        echo json_encode(['success' => false, 'message' => 'La cédula ya está registrada por otro usuario']);
        exit();
    }
    
    // Validar contraseña si se proporcionó
    $cambiar_password = false;
    $nueva_password = null;
    
    if (!empty($_POST['nueva_password'])) {
        if (strlen($_POST['nueva_password']) < 6) {
            echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres']);
            exit();
        }
        
        if ($_POST['nueva_password'] !== $_POST['confirmar_password']) {
            echo json_encode(['success' => false, 'message' => 'Las contraseñas no coinciden']);
            exit();
        }
        
        $cambiar_password = true;
        $nueva_password = password_hash($_POST['nueva_password'], PASSWORD_DEFAULT);
    }
    
    // Preparar datos para actualizar
    $datos_actualizar = [
        'nombres' => trim($_POST['nombres']),
        'apellidos' => trim($_POST['apellidos']),
        'cedula' => $cedula,
        'nickname' => trim($_POST['nickname']),
        'correo' => trim($_POST['correo']),
        'telefono' => $telefono,
        'tipo_usuario' => $_POST['tipo_usuario'],
        'id_zona' => !empty($_POST['zona']) ? intval($_POST['zona']) : null,
        'id_sector' => !empty($_POST['sector']) ? intval($_POST['sector']) : null,
        'id_puesto' => !empty($_POST['puesto']) ? intval($_POST['puesto']) : null,
        'tope' => !empty($_POST['tope']) ? intval($_POST['tope']) : 0,
        'activo' => isset($_POST['activo']) ? ($_POST['activo'] == '1' ? true : false) : true
    ];
    
    // Si se está editando a sí mismo, no permitir desactivar la cuenta
    if ($editando_a_si_mismo && !$datos_actualizar['activo']) {
        echo json_encode(['success' => false, 'message' => 'No puede desactivar su propia cuenta']);
        exit();
    }
    
    // Si se está editando a sí mismo, no permitir cambiar el tipo de usuario
    if ($editando_a_si_mismo && $_POST['tipo_usuario'] !== $usuario_logueado['tipo_usuario']) {
        echo json_encode(['success' => false, 'message' => 'No puede cambiar su propio tipo de usuario']);
        exit();
    }
    
    // Manejar la foto de perfil
    $foto_perfil = null;
    $eliminar_foto = isset($_POST['eliminar_foto']) && $_POST['eliminar_foto'] == '1';
    
    if ($eliminar_foto) {
        // Eliminar foto existente si existe
        if (!empty($usuario_a_editar['foto'])) {
            $ruta_foto = __DIR__ . '/../' . $usuario_a_editar['foto'];
            if (file_exists($ruta_foto) && is_file($ruta_foto)) {
                @unlink($ruta_foto);
            }
        }
        $foto_perfil = null;
    } elseif (isset($_FILES['foto']) && $_FILES['foto']['error'] == UPLOAD_ERR_OK) {
        // Subir nueva foto
        $directorio_fotos = __DIR__ . '/../uploads/usuarios/';
        
        // Crear directorio si no existe
        if (!file_exists($directorio_fotos)) {
            mkdir($directorio_fotos, 0777, true);
        }
        
        // Validar tipo de archivo
        $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $tipo_archivo = mime_content_type($_FILES['foto']['tmp_name']);
        
        if (!in_array($tipo_archivo, $tipos_permitidos)) {
            echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido. Solo se permiten imágenes JPEG, PNG, GIF o WebP']);
            exit();
        }
        
        // Validar tamaño (máximo 5MB)
        $tamano_maximo = 5 * 1024 * 1024; // 5MB
        if ($_FILES['foto']['size'] > $tamano_maximo) {
            echo json_encode(['success' => false, 'message' => 'La imagen es demasiado grande. El tamaño máximo es 5MB']);
            exit();
        }
        
        // Eliminar foto anterior si existe
        if (!empty($usuario_a_editar['foto'])) {
            $ruta_foto_anterior = __DIR__ . '/../' . $usuario_a_editar['foto'];
            if (file_exists($ruta_foto_anterior) && is_file($ruta_foto_anterior)) {
                @unlink($ruta_foto_anterior);
            }
        }
        
        // Generar nombre único para la foto
        $extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $nombre_archivo = 'usuario_' . $id_usuario . '_' . time() . '.' . $extension;
        $ruta_destino = $directorio_fotos . $nombre_archivo;
        
        // Mover archivo
        if (move_uploaded_file($_FILES['foto']['tmp_name'], $ruta_destino)) {
            $foto_perfil = 'uploads/usuarios/' . $nombre_archivo;
            
            // Redimensionar imagen si es muy grande (opcional)
            $this->redimensionarImagen($ruta_destino, 500, 500);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al subir la imagen']);
            exit();
        }
    } elseif (isset($_POST['foto_actual']) && !empty($_POST['foto_actual'])) {
        // Mantener la foto actual
        $foto_perfil = $_POST['foto_actual'];
    }
    
    // Agregar foto a los datos si se subió una nueva o se mantiene la actual
    if ($foto_perfil !== null) {
        $datos_actualizar['foto'] = $foto_perfil;
    }
    
    // Agregar contraseña si se cambió
    if ($cambiar_password) {
        $datos_actualizar['password'] = $nueva_password;
    }
    
    // Actualizar usuario
    $actualizado = $usuarioModel->actualizarUsuario($id_usuario, $datos_actualizar);
    
    if ($actualizado) {
        // Registrar en bitácora (si tienes sistema de bitácora)
        // $this->registrarBitacora('USUARIO_ACTUALIZADO', "Usuario actualizado: {$datos_actualizar['nickname']}");
        
        echo json_encode([
            'success' => true,
            'message' => 'Usuario actualizado correctamente',
            'redirect' => '../dashboard.php?success=usuario_actualizado'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No se pudieron guardar los cambios. Verifique que haya realizado algún cambio.'
        ]);
    }
    
} catch (Exception $e) {
    error_log('Error en procesar_editar_usuario.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor. Por favor, intente nuevamente.'
    ]);
}

/**
 * Función para redimensionar imágenes (opcional)
 */
function redimensionarImagen($ruta_imagen, $ancho_maximo, $alto_maximo) {
    // Obtener información de la imagen
    $info = getimagesize($ruta_imagen);
    if (!$info) return false;
    
    list($ancho_original, $alto_original, $tipo) = $info;
    
    // Calcular nuevas dimensiones manteniendo proporción
    $ratio = $ancho_original / $alto_original;
    
    if ($ancho_maximo / $alto_maximo > $ratio) {
        $ancho_maximo = $alto_maximo * $ratio;
    } else {
        $alto_maximo = $ancho_maximo / $ratio;
    }
    
    // Crear imagen según el tipo
    switch ($tipo) {
        case IMAGETYPE_JPEG:
            $imagen_original = imagecreatefromjpeg($ruta_imagen);
            break;
        case IMAGETYPE_PNG:
            $imagen_original = imagecreatefrompng($ruta_imagen);
            break;
        case IMAGETYPE_GIF:
            $imagen_original = imagecreatefromgif($ruta_imagen);
            break;
        case IMAGETYPE_WEBP:
            $imagen_original = imagecreatefromwebp($ruta_imagen);
            break;
        default:
            return false;
    }
    
    if (!$imagen_original) return false;
    
    // Crear nueva imagen redimensionada
    $imagen_redimensionada = imagecreatetruecolor($ancho_maximo, $alto_maximo);
    
    // Mantener transparencia para PNG y GIF
    if ($tipo == IMAGETYPE_PNG || $tipo == IMAGETYPE_GIF) {
        imagecolortransparent($imagen_redimensionada, imagecolorallocatealpha($imagen_redimensionada, 0, 0, 0, 127));
        imagealphablending($imagen_redimensionada, false);
        imagesavealpha($imagen_redimensionada, true);
    }
    
    // Redimensionar
    imagecopyresampled(
        $imagen_redimensionada, $imagen_original,
        0, 0, 0, 0,
        $ancho_maximo, $alto_maximo,
        $ancho_original, $alto_original
    );
    
    // Guardar imagen
    switch ($tipo) {
        case IMAGETYPE_JPEG:
            imagejpeg($imagen_redimensionada, $ruta_imagen, 90);
            break;
        case IMAGETYPE_PNG:
            imagepng($imagen_redimensionada, $ruta_imagen, 9);
            break;
        case IMAGETYPE_GIF:
            imagegif($imagen_redimensionada, $ruta_imagen);
            break;
        case IMAGETYPE_WEBP:
            imagewebp($imagen_redimensionada, $ruta_imagen, 90);
            break;
    }
    
    // Liberar memoria
    imagedestroy($imagen_original);
    imagedestroy($imagen_redimensionada);
    
    return true;
}