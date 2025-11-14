<?php
require_once '../config/database.php';

// Verificar autenticación
requireRole(['admin', 'user']);

// Asegurar que es una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Verificar que hay OCs temporales
if (!isset($_SESSION['ocs_temporales']) || empty($_SESSION['ocs_temporales'])) {
    echo json_encode(['success' => false, 'error' => 'No hay OCs temporales para sincronizar']);
    exit;
}

try {
    $pdo = getConnection();
    $pdo->beginTransaction();
    
    $sincronizados = 0;
    $errores = [];
    
    foreach ($_SESSION['ocs_temporales'] as $orden_servicio => $datos) {
        try {
            // Verificar que la OC no haya sido registrada mientras tanto
            $stmt = $pdo->prepare("SELECT id FROM retiros_medidores WHERE orden_servicio = ?");
            $stmt->execute([$orden_servicio]);
            
            if ($stmt->fetch()) {
                $errores[] = "$orden_servicio: Ya fue registrada previamente";
                continue;
            }
            
            // Insertar el retiro en la base de datos (columnas correctas de retiros_medidores)
            $sql = "INSERT INTO retiros_medidores (
                orden_servicio_id, orden_servicio, medidor_retirado, lectura_m3,
                puntero_girando, medidor_con_precinto, visor_imposibilidad_lectura,
                medidor_tiene_filtro, filtro_buen_estado, solidos_retenidos_filtro,
                info_caja_medidor, observacion, foto_imposibilidad, tiene_foto,
                tecnico_responsable, usuario_id, tipo_imposibilidad_id, detalles_imposibilidad
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $datos['orden_id'],
                $datos['orden_servicio'],
                $datos['medidor_retirado'],
                $datos['lectura_retiro'],
                $datos['puntero_girando'],
                $datos['medidor_con_precinto'],
                $datos['visor_imposibilidad'],
                $datos['medidor_tiene_filtro'],
                $datos['filtro_buen_estado'],
                $datos['solidos_retenidos'],
                $datos['info_caja'],
                $datos['observaciones'],
                $datos['foto_medidor_retirado'],
                $datos['tiene_foto'],
                $datos['tecnico_responsable'],
                $_SESSION['user_id'],
                $datos['tipo_imposibilidad_id'] ?? null,
                $datos['detalles_imposibilidad'] ?? null
            ]);
            
            $registroId = $pdo->lastInsertId();
            
            // Registrar en auditoría
            logAudit(
                $registroId,
                $_SESSION['user_id'],
                'registro_retiro_sync',
                "Retiro sincronizado desde sesión temporal: $orden_servicio",
                $orden_servicio
            );
            
            $sincronizados++;
            
        } catch (Exception $e) {
            $errores[] = "$orden_servicio: " . $e->getMessage();
            error_log("Error sincronizando $orden_servicio: " . $e->getMessage());
        }
    }
    
    // Si hubo algún error, revertir todo
    if (!empty($errores) && $sincronizados == 0) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'error' => "No se pudo sincronizar ninguna OC:\n" . implode("\n", $errores)
        ]);
        exit;
    }
    
    // Commit de la transacción
    $pdo->commit();
    
    // Limpiar OCs temporales sincronizadas exitosamente
    foreach ($_SESSION['ocs_temporales'] as $orden_servicio => $datos) {
        if (!in_array($orden_servicio . ": ", array_map(fn($e) => substr($e, 0, strlen($orden_servicio) + 2), $errores))) {
            unset($_SESSION['ocs_temporales'][$orden_servicio]);
        }
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'sincronizados' => $sincronizados,
        'errores' => $errores
    ]);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Error en sincronización: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error al sincronizar: ' . $e->getMessage()
    ]);
}

