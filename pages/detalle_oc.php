<?php
require_once '../config/database.php';

// Verificar autenticación
requireRole(['admin', 'user']);

$ordenServicio = isset($_GET['oc']) ? trim($_GET['oc']) : '';

if (empty($ordenServicio)) {
    echo '<div class="alert alert-danger">No se especificó una orden de servicio</div>';
    exit;
}

try {
    $pdo = getConnection();
    
    // Obtener datos de la OC
    $stmt = $pdo->prepare("SELECT * FROM ordenes_servicio WHERE orden_servicio = ?");
    $stmt->execute([$ordenServicio]);
    $oc = $stmt->fetch();
    
    if (!$oc) {
        echo '<div class="alert alert-warning">No se encontró la orden de servicio</div>';
        exit;
    }
    
    // Obtener información del retiro si existe
    $stmtRetiro = $pdo->prepare("
        SELECT r.*, u.nombre_completo as tecnico_responsable
        FROM retiros_medidores r
        LEFT JOIN usuarios u ON r.usuario_id = u.id
        WHERE r.orden_servicio_id = ? AND r.estado_registro = 'activo'
        ORDER BY r.fecha_registro DESC
        LIMIT 1
    ");
    $stmtRetiro->execute([$oc['id']]);
    $retiro = $stmtRetiro->fetch();
    
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    exit;
}
?>

<div class="row">
    <!-- Información de la OC -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0">
                    <i class="bi bi-file-earmark-text"></i> Información de la Orden de Servicio
                </h6>
            </div>
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-5"><strong>Orden de Servicio:</strong></div>
                    <div class="col-7"><?= htmlspecialchars($oc['orden_servicio']) ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5"><strong>Item:</strong></div>
                    <div class="col-7"><?= htmlspecialchars($oc['item']) ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5"><strong>Fecha OS:</strong></div>
                    <div class="col-7"><?= !empty($oc['fecha_os']) ? date('d/m/Y', strtotime($oc['fecha_os'])) : 'N/A' ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5"><strong>Tipo de Servicio:</strong></div>
                    <div class="col-7"><?= htmlspecialchars($oc['tipo_servicio']) ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5"><strong>Cantidad de Medidores:</strong></div>
                    <div class="col-7"><?= htmlspecialchars($oc['cantidad_medidores']) ?></div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0">
                    <i class="bi bi-calendar-check"></i> Programación
                </h6>
            </div>
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-5"><strong>Día Retiro:</strong></div>
                    <div class="col-7"><?= !empty($oc['programacion_dia_retiro']) ? date('d/m/Y', strtotime($oc['programacion_dia_retiro'])) : 'N/A' ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5"><strong>Hora Retiro:</strong></div>
                    <div class="col-7"><?= htmlspecialchars($oc['programacion_hora_retiro']) ?: 'N/A' ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5"><strong>Día VP:</strong></div>
                    <div class="col-7"><?= !empty($oc['programacion_dia_vp']) ? date('d/m/Y', strtotime($oc['programacion_dia_vp'])) : 'N/A' ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5"><strong>Hora VP:</strong></div>
                    <div class="col-7"><?= htmlspecialchars($oc['programacion_hora_vp']) ?: 'N/A' ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5"><strong>Código Seguridad:</strong></div>
                    <div class="col-7"><?= htmlspecialchars($oc['codigo_seguridad']) ?: 'N/A' ?></div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0">
                    <i class="bi bi-person"></i> Información del Cliente
                </h6>
            </div>
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-5"><strong>Cliente:</strong></div>
                    <div class="col-7"><?= htmlspecialchars($oc['cliente']) ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5"><strong>Centro de Servicio:</strong></div>
                    <div class="col-7"><?= htmlspecialchars($oc['centro_servicio']) ?: 'N/A' ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5"><strong>Usuario Reclamante:</strong></div>
                    <div class="col-7"><?= htmlspecialchars($oc['usuario_reclamante']) ?: 'N/A' ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5"><strong>Dirección:</strong></div>
                    <div class="col-7"><?= htmlspecialchars($oc['direccion']) ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5"><strong>N° Suministro:</strong></div>
                    <div class="col-7"><?= htmlspecialchars($oc['num_suministro']) ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5"><strong>CUS:</strong></div>
                    <div class="col-7"><?= htmlspecialchars($oc['cus']) ?: 'N/A' ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5"><strong>CUP:</strong></div>
                    <div class="col-7"><?= htmlspecialchars($oc['cup']) ?: 'N/A' ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5"><strong>Remesa:</strong></div>
                    <div class="col-7"><?= htmlspecialchars($oc['remesa']) ?: 'N/A' ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Información del Medidor -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-warning text-dark">
                <h6 class="mb-0">
                    <i class="bi bi-speedometer2"></i> Información del Medidor
                </h6>
            </div>
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-5"><strong>N° Serie Medidor:</strong></div>
                    <div class="col-7"><?= htmlspecialchars($oc['num_serie_medidor']) ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5"><strong>Marca:</strong></div>
                    <div class="col-7"><?= htmlspecialchars($oc['marca_medidor']) ?: 'N/A' ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5"><strong>Modelo:</strong></div>
                    <div class="col-7"><?= htmlspecialchars($oc['modelo_medidor']) ?: 'N/A' ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5"><strong>Año Fabricación:</strong></div>
                    <div class="col-7"><?= htmlspecialchars($oc['anio_fabricacion']) ?: 'N/A' ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5"><strong>Fabricante:</strong></div>
                    <div class="col-7"><?= htmlspecialchars($oc['fabricante']) ?: 'N/A' ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5"><strong>Procedencia:</strong></div>
                    <div class="col-7"><?= htmlspecialchars($oc['procedencia']) ?: 'N/A' ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5"><strong>Tipo Medidor:</strong></div>
                    <div class="col-7"><?= htmlspecialchars($oc['tipo_medidor']) ?: 'N/A' ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5"><strong>Diámetro Nominal:</strong></div>
                    <div class="col-7"><?= htmlspecialchars($oc['diametro_nominal']) ?: 'N/A' ?> mm</div>
                </div>
                <div class="row mb-2">
                    <div class="col-5"><strong>Q3:</strong></div>
                    <div class="col-7"><?= htmlspecialchars($oc['q3']) ?: 'N/A' ?> m³/h</div>
                </div>
                <div class="row mb-2">
                    <div class="col-5"><strong>Alcance:</strong></div>
                    <div class="col-7"><?= htmlspecialchars($oc['alcance']) ?: 'N/A' ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5"><strong>PMA:</strong></div>
                    <div class="col-7"><?= htmlspecialchars($oc['pma']) ?: 'N/A' ?> bar</div>
                </div>
                <div class="row mb-2">
                    <div class="col-5"><strong>TMA:</strong></div>
                    <div class="col-7"><?= htmlspecialchars($oc['tma']) ?: 'N/A' ?> °C</div>
                </div>
                <div class="row mb-2">
                    <div class="col-5"><strong>Clase Sensibilidad:</strong></div>
                    <div class="col-7"><?= htmlspecialchars($oc['clase_sensibilidad']) ?: 'N/A' ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5"><strong>Certificado Aprobación:</strong></div>
                    <div class="col-7"><?= htmlspecialchars($oc['certificado_aprobacion']) ?: 'N/A' ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5"><strong>N° Certificado:</strong></div>
                    <div class="col-7"><?= htmlspecialchars($oc['num_certificado']) ?: 'N/A' ?></div>
                </div>
            </div>
        </div>

        <?php if ($retiro): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-danger text-white">
                    <h6 class="mb-0">
                        <i class="bi bi-clipboard-data"></i> Estado del Retiro
                    </h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-3">
                        <i class="bi bi-info-circle"></i> 
                        <strong>Esta OC ya tiene un registro de retiro</strong>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5"><strong>Medidor Retirado:</strong></div>
                        <div class="col-7">
                            <?php if ($retiro['medidor_retirado'] === 'SI'): ?>
                                <span class="badge bg-success">SÍ</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">NO</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($retiro['tecnico_responsable']): ?>
                        <div class="row mb-2">
                            <div class="col-5"><strong>Técnico Responsable:</strong></div>
                            <div class="col-7"><?= htmlspecialchars($retiro['tecnico_responsable']) ?></div>
                        </div>
                    <?php endif; ?>
                    <div class="row mb-2">
                        <div class="col-5"><strong>Fecha de Registro:</strong></div>
                        <div class="col-7"><?= date('d/m/Y H:i', strtotime($retiro['fecha_registro'])) ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5"><strong>Lectura m³:</strong></div>
                        <div class="col-7"><?= htmlspecialchars($retiro['lectura_m3']) ?: 'N/A' ?></div>
                    </div>
                    <?php if ($retiro['observacion']): ?>
                        <div class="row mb-2">
                            <div class="col-5"><strong>Observaciones:</strong></div>
                            <div class="col-7"><?= htmlspecialchars($retiro['observacion']) ?></div>
                        </div>
                    <?php endif; ?>
                    <div class="mt-3">
                        <a href="detalle_retiro.php?id=<?= $retiro['id'] ?>" class="btn btn-primary btn-sm" target="_blank">
                            <i class="bi bi-eye"></i> Ver Detalle Completo del Retiro
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-clock-history text-muted" style="font-size: 3rem;"></i>
                    <h6 class="mt-3 mb-2">Pendiente de Registro</h6>
                    <p class="text-muted small">Esta OC aún no ha sido procesada por un técnico</p>
                    <a href="buscar_oc.php?oc=<?= urlencode($oc['orden_servicio']) ?>" class="btn btn-primary btn-sm">
                        <i class="bi bi-clipboard-check"></i> Registrar Retiro
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-secondary text-white">
                <h6 class="mb-0">
                    <i class="bi bi-clock"></i> Información de Creación
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <small class="text-muted"><strong>Creado:</strong></small><br>
                        <?= date('d/m/Y H:i', strtotime($oc['created_at'])) ?>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted"><strong>Última Actualización:</strong></small><br>
                        <?= date('d/m/Y H:i', strtotime($oc['updated_at'])) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

