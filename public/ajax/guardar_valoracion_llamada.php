<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/LlamadaModel.php';
require_once __DIR__ . '/../../models/ReferenciadoModel.php';

header('Content-Type: application/json');

// Verificar autenticación
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode([
        'success' => false,
        'message' => 'No autorizado. Por favor, inicie sesión.'
    ]);
    exit();
}

// Verificar que sea una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit();
}

// Obtener datos JSON
$input = json_decode(file_get_contents('php://input'), true);

if ($input === null) {
    $input = $_POST;
}

// Validar datos mínimos
if (!isset($input['id_referenciado']) || !isset($input['rating']) || !isset($input['id_resultado'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Datos incompletos. Se requiere id_referenciado, rating y resultado.'
    ]);
    exit();
}

try {
    $pdo = Database::getConnection();
    $llamadaModel = new LlamadaModel($pdo);
    $referenciadoModel = new ReferenciadoModel($pdo);
    
    // Preparar datos para guardar
    $datosLlamada = [
        'id_referenciado' => intval($input['id_referenciado']),
        'id_usuario' => $_SESSION['id_usuario'],
        'id_resultado' => intval($input['id_resultado']),
        'telefono' => $input['telefono'] ?? '',
        'rating' => intval($input['rating']),
        'observaciones' => $input['observaciones'] ?? '',
        'fecha_llamada' => $input['fecha_llamada'] ?? date('Y-m-d H:i:s')
    ];
    
    // Validaciones básicas
    if ($datosLlamada['rating'] < 1 || $datosLlamada['rating'] > 5) {
        echo json_encode([
            'success' => false,
            'message' => 'El rating debe estar entre 1 y 5.'
        ]);
        exit();
    }
    
    if ($datosLlamada['id_resultado'] < 1 || $datosLlamada['id_resultado'] > 7) {
        echo json_encode([
            'success' => false,
            'message' => 'El resultado seleccionado no es válido.'
        ]);
        exit();
    }
    
    // Guardar la valoración
    $id_llamada = $llamadaModel->guardarValoracionLlamada($datosLlamada);
    
    if ($id_llamada) {
        // =============================================
        // LÓGICA DE DECISIÓN - BASADA SOLO EN LA ÚLTIMA LLAMADA
        // =============================================
        
        // Obtener SOLO la última llamada (la que acabamos de guardar)
        $ultimaLlamada = $llamadaModel->obtenerUltimaLlamada($datosLlamada['id_referenciado']);
        
        // Variables para la respuesta
        $nuevoEstado = null; // null = mantener estado actual
        $decision = '';
        $estadoActualizado = false;
        
        if ($ultimaLlamada) {
            // Forzar tipos a entero para comparación segura
            $rating = intval($ultimaLlamada['rating']);
            $resultado = intval($ultimaLlamada['id_resultado']);
            
            // MATRIZ DE DECISIÓN
            switch ($resultado) {
                case 1: // Contactado
                    if ($rating === 1) {
                        $nuevoEstado = false; // INACTIVO
                        $decision = 'Contacto con rating 1 (mala experiencia)';
                    } elseif ($rating === 2) {
                        $nuevoEstado = null; // MANTENER
                        $decision = 'Contacto con rating 2 - requiere revisión';
                    } else {
                        $nuevoEstado = true; // ACTIVO
                        $decision = 'Contacto exitoso con rating ' . $rating;
                    }
                    break;
                    
                case 2: // No contesta
                case 4: // Teléfono apagado
                case 5: // Ocupado
                    $nuevoEstado = null; // MANTENER
                    $decision = 'Sin contacto - mantener estado actual';
                    break;
                    
                case 3: // Número equivocado
                    $nuevoEstado = false; // INACTIVO
                    $decision = 'Número equivocado - referido no válido';
                    break;
                    
                case 6: // Dejó mensaje
                    if ($rating >= 3) {
                        $nuevoEstado = true; // ACTIVO
                        $decision = 'Mensaje dejado con interés (rating ' . $rating . ')';
                    } else {
                        $nuevoEstado = null; // MANTENER
                        $decision = 'Mensaje dejado pero con bajo interés (rating ' . $rating . ')';
                    }
                    break;
                    
                case 7: // Rechazó llamada
                    if ($rating <= 2) {
                        $nuevoEstado = false; // INACTIVO
                        $decision = 'Rechazo de llamada con rating bajo';
                    } else {
                        $nuevoEstado = null; // MANTENER
                        $decision = 'Rechazó llamada pero con buen rating - requiere revisión';
                    }
                    break;
                    
                default:
                    $nuevoEstado = null;
                    $decision = 'Resultado desconocido - mantener estado';
                    break;
            }
            
            // Construir motivo del cambio
            $motivoCambio = sprintf(
                "Decisión basada en última llamada (ID: %d): %s. Rating: %d, Resultado: %d",
                $ultimaLlamada['id_llamada'],
                $decision,
                $rating,
                $resultado
            );
            
            if (!empty($datosLlamada['observaciones'])) {
                $motivoCambio .= ' - Obs: ' . $datosLlamada['observaciones'];
            }
            
            // Aplicar cambio de estado SOLO si hay decisión definida (no null)
            if ($nuevoEstado !== null) {
                $estadoActualizado = $referenciadoModel->actualizarEstadoReferenciado(
                    $datosLlamada['id_referenciado'],
                    $nuevoEstado,
                    $motivoCambio,
                    $_SESSION['id_usuario']
                );
            }
        }
        
        // Obtener el historial completo SOLO para enviar en la respuesta (opcional)
        // Esto es útil si el frontend necesita actualizar algo
        $historialCompleto = $llamadaModel->obtenerLlamadasPorReferenciado($datosLlamada['id_referenciado']);
        
        // Preparar respuesta detallada
        $respuesta = [
            'success' => true,
            'message' => 'Valoración guardada exitosamente.',
            'id_llamada' => $id_llamada,
            'data' => $datosLlamada,
            'analisis' => [
                'decision' => $decision,
                'nuevo_estado' => $nuevoEstado === null ? 'mantener' : ($nuevoEstado ? 'activo' : 'inactivo'),
                'estado_actualizado' => $estadoActualizado,
                'ultimo_rating' => $ultimaLlamada ? intval($ultimaLlamada['rating']) : null,
                'ultimo_resultado' => $ultimaLlamada ? intval($ultimaLlamada['id_resultado']) : null,
                'total_llamadas' => count($historialCompleto)
            ]
        ];
        
        // Personalizar mensaje según la acción tomada
        if ($estadoActualizado) {
            if ($nuevoEstado) {
                $respuesta['message'] = 'Valoración guardada. Referenciado REACTIVADO - ' . $decision;
            } else {
                $respuesta['message'] = 'Valoración guardada. Referenciado DESACTIVADO - ' . $decision;
            }
        } elseif ($nuevoEstado === null) {
            $respuesta['message'] = 'Valoración guardada. Estado mantenido - ' . $decision;
        }
        
        echo json_encode($respuesta);
        
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al guardar la valoración.'
        ]);
    }
    
} catch (Exception $e) {
    error_log('Error en guardar_valoracion_llamada.php: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
?>