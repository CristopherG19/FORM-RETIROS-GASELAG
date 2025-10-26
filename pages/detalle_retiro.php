<?php
require_once '../config/database.php';

// Verificar autenticación (todos los usuarios)
requireRole(['admin', 'user']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

try {
    $pdo = getConnection();
    $stmt = $pdo->prepare("
        SELECT
            r.*,
            o.*,
            r.id as retiro_id,
            r.observacion as retiro_observacion,
            ti.descripcion as tipo_imposibilidad_descripcion,
            ti.categoria as tipo_imposibilidad_categoria,
            ti.codigo as tipo_imposibilidad_codigo,
            u.nombre_completo as registrado_por,
            u.username as username_registrado
        FROM retiros_medidores r
        INNER JOIN ordenes_servicio o ON r.orden_servicio_id = o.id
        LEFT JOIN tipos_imposibilidad ti ON r.tipo_imposibilidad_id = ti.id
        LEFT JOIN usuarios u ON r.usuario_id = u.id
        WHERE r.id = ?
    ");
    $stmt->execute([$id]);
    $data = $stmt->fetch();
    
    if (!$data) {
        echo '<div class="alert alert-danger border-0">Registro no encontrado</div>';
        exit;
    }
} catch (Exception $e) {
    echo '<div class="alert alert-danger border-0">Error: ' . $e->getMessage() . '</div>';
    exit;
}
?>

<!-- Información de la Orden -->
<div class="card border-0 bg-light mb-3">
    <div class="card-body">
        <h6 class="fw-bold mb-3">
            <i class="bi bi-file-text text-primary"></i> Información de la Orden
        </h6>
        <div class="row g-3">
            <div class="col-md-6">
                <small class="text-muted d-block">Orden Servicio</small>
                <strong><?= htmlspecialchars($data['orden_servicio']) ?></strong>
            </div>
            <div class="col-md-6">
                <small class="text-muted d-block">Suministro</small>
                <strong><?= htmlspecialchars($data['num_suministro']) ?></strong>
            </div>
            <div class="col-md-6">
                <small class="text-muted d-block">Usuario</small>
                <span><?= htmlspecialchars($data['usuario_reclamante']) ?></span>
            </div>
            <div class="col-md-6">
                <small class="text-muted d-block">Dirección</small>
                <span><?= htmlspecialchars($data['direccion']) ?></span>
            </div>
            <div class="col-md-4">
                <small class="text-muted d-block">N° Serie Medidor</small>
                <span class="font-monospace"><?= htmlspecialchars($data['num_serie_medidor']) ?></span>
            </div>
            <div class="col-md-4">
                <small class="text-muted d-block">Marca</small>
                <span><?= htmlspecialchars($data['marca_medidor']) ?></span>
            </div>
            <div class="col-md-4">
                <small class="text-muted d-block">Modelo</small>
                <span><?= htmlspecialchars($data['modelo_medidor']) ?></span>
            </div>
        </div>
    </div>
</div>

<!-- ALERTA PARA REGISTROS NO RETIRADOS -->
<?php if ($data['medidor_retirado'] === 'NO' && (empty($data['foto_imposibilidad']))): ?>
    <div class="alert alert-danger border-0 shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-center">
            <div class="flex-shrink-0">
                <i class="bi bi-exclamation-triangle-fill" style="font-size: 2rem;"></i>
            </div>
            <div class="flex-grow-1 ms-3">
                <h5 class="alert-heading mb-2">
                    <i class="bi bi-camera"></i> REQUIERE EVIDENCIA FOTOGRÁFICA
                </h5>
                <p class="mb-2">
                    <strong>❌ REGISTRO CRÍTICO - SIN EVIDENCIA FOTOGRÁFICA</strong>
                </p>
                <?php if ($data['visor_imposibilidad_lectura'] === 'SI'): ?>
                    <p class="mb-2">
                        Este registro indica imposibilidad de lectura pero no tiene foto de sustento.
                        Es necesario contactar al técnico para que adjunte la evidencia fotográfica.
                    </p>
                <?php else: ?>
                    <p class="mb-2">
                        Este registro no especifica el motivo del no retiro y no tiene evidencia fotográfica.
                        Es necesario contactar al técnico para que proporcione la evidencia correspondiente.
                    </p>
                <?php endif; ?>
                <hr>
                <p class="mb-0">
                    <strong>Acción requerida:</strong> Solicitar al técnico
                    <span class="badge bg-dark"> <?= htmlspecialchars($data['tecnico_responsable']) ?> </span>
                    que proporcione evidencia fotográfica del estado del medidor.
                </p>
            </div>
        </div>
    </div>
<?php elseif ($data['medidor_retirado'] === 'NO' && !empty($data['foto_imposibilidad'])): ?>
    <div class="alert alert-success border-0 shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-center">
            <div class="flex-shrink-0">
                <i class="bi bi-check-circle-fill" style="font-size: 2rem;"></i>
            </div>
            <div class="flex-grow-1 ms-3">
                <h5 class="alert-heading mb-2">
                    <i class="bi bi-check-circle"></i> REGISTRO COMPLETO
                </h5>
                <p class="mb-0">
                    <?php if ($data['visor_imposibilidad_lectura'] === 'SI'): ?>
                        <strong>✅ Imposibilidad de lectura con evidencia fotográfica</strong><br>
                        Este registro está correctamente documentado.
                    <?php else: ?>
                        <strong>✅ No retiro con evidencia fotográfica</strong><br>
                        Este registro está correctamente documentado.
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Información del Retiro -->
<div class="card border-0 bg-light mb-3">
    <div class="card-body">
        <h6 class="fw-bold mb-3">
            <i class="bi bi-clipboard-data text-primary"></i> Información del Retiro
        </h6>
        <div class="row g-3">
            <div class="col-md-6">
                <small class="text-muted d-block">Fecha de Registro</small>
                <span><?= date('d/m/Y H:i', strtotime($data['fecha_registro'])) ?></span>
            </div>
            <div class="col-md-6">
                <small class="text-muted d-block">Registrado por</small>
                <?php if ($data['registrado_por']): ?>
                    <div>
                        <strong><?= htmlspecialchars($data['registrado_por']) ?></strong>
                        <?php if ($data['username_registrado']): ?>
                            <br><small class="text-muted">@<?= htmlspecialchars($data['username_registrado']) ?></small>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <strong><?= htmlspecialchars($data['tecnico_responsable']) ?></strong>
                    <br><small class="text-muted">*Registro anterior al nuevo sistema</small>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <small class="text-muted d-block">Estado del Retiro</small>
                <?php if ($data['medidor_retirado'] === 'SI'): ?>
                    <span class="badge bg-success">✓ Medidor Retirado</span>
                <?php else: ?>
                    <span class="badge bg-warning text-dark">✗ No Retirado</span>
                <?php endif; ?>
            </div>
            <?php if ($data['medidor_retirado'] === 'NO'): ?>
                <div class="col-md-6">
                    <small class="text-muted d-block">Tipo de Imposibilidad</small>
                    <?php if ($data['tipo_imposibilidad_descripcion']): ?>
                        <?php
                        $categoriaIcon = match($data['tipo_imposibilidad_categoria']) {
                            'acceso' => '🚪',
                            'medidor' => '⚡',
                            'cliente' => '👤',
                            'seguridad' => '⚠️',
                            'otros' => '📋',
                            default => '❓'
                        };
                        $badgeColor = match($data['tipo_imposibilidad_categoria']) {
                            'acceso' => 'info',
                            'medidor' => 'warning',
                            'cliente' => 'secondary',
                            'seguridad' => 'danger',
                            'otros' => 'dark',
                            default => 'secondary'
                        };
                        ?>
                        <span class="badge bg-<?= $badgeColor ?>">
                            <?= $categoriaIcon ?> <?= htmlspecialchars($data['tipo_imposibilidad_descripcion']) ?>
                        </span>
                        <?php if ($data['detalles_imposibilidad']): ?>
                            <br><small class="text-muted">
                                <i class="bi bi-chat-left-text"></i> <?= htmlspecialchars($data['detalles_imposibilidad']) ?>
                            </small>
                        <?php endif; ?>
                    <?php elseif ($data['visor_imposibilidad_lectura'] === 'SI'): ?>
                        <span class="badge bg-info">📷 Imposibilidad de Lectura</span>
                        <br><small class="text-muted">*Registro anterior al nuevo sistema</small>
                    <?php else: ?>
                        <span class="badge bg-secondary">📋 No Especificado</span>
                        <br><small class="text-muted">*Registro anterior al nuevo sistema</small>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <?php if ($data['medidor_retirado'] === 'SI' && !empty($data['lectura_m3'])): ?>
                <div class="col-md-6">
                    <small class="text-muted d-block">Lectura m³</small>
                    <strong class="text-primary"><?= $data['lectura_m3'] ?></strong>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($data['medidor_retirado'] === 'SI'): ?>
    <!-- Reporte Visual del Medidor -->
    <div class="card border-0 bg-light mb-3">
        <div class="card-body">
            <h6 class="fw-bold mb-3">
                <i class="bi bi-eye text-primary"></i> Reporte Visual del Medidor
            </h6>
            <div class="row g-3">
                <div class="col-md-4">
                    <small class="text-muted d-block mb-1">Puntero Girando</small>
                    <?php if ($data['puntero_girando'] === 'SI'): ?>
                        <span class="badge bg-success-subtle text-success border border-success">✓ SÍ</span>
                    <?php else: ?>
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary">✗ NO</span>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block mb-1">Con Precinto de Seguridad</small>
                    <?php if ($data['medidor_con_precinto'] === 'SI'): ?>
                        <span class="badge bg-success-subtle text-success border border-success">✓ SÍ</span>
                    <?php else: ?>
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary">✗ NO</span>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block mb-1">Visor con Imposibilidad</small>
                    <?php if ($data['visor_imposibilidad_lectura'] === 'SI'): ?>
                        <span class="badge bg-warning-subtle text-warning border border-warning">⚠ SÍ</span>
                    <?php else: ?>
                        <span class="badge bg-success-subtle text-success border border-success">✓ NO</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Reporte del Filtro -->
    <div class="card border-0 bg-light mb-3">
        <div class="card-body">
            <h6 class="fw-bold mb-3">
                <i class="bi bi-funnel text-primary"></i> Reporte del Filtro
            </h6>
            <div class="row g-3">
                <div class="col-md-4">
                    <small class="text-muted d-block mb-1">Tiene Filtro</small>
                    <?php if ($data['medidor_tiene_filtro'] === 'SI'): ?>
                        <span class="badge bg-info-subtle text-info border border-info">✓ SÍ</span>
                    <?php else: ?>
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary">✗ NO</span>
                    <?php endif; ?>
                </div>
                <?php if ($data['medidor_tiene_filtro'] === 'SI'): ?>
                    <div class="col-md-4">
                        <small class="text-muted d-block mb-1">Estado del Filtro</small>
                        <?php if ($data['filtro_buen_estado'] === 'SI'): ?>
                            <span class="badge bg-success-subtle text-success border border-success">✓ Buen Estado</span>
                        <?php else: ?>
                            <span class="badge bg-warning-subtle text-warning border border-warning">⚠ Mal Estado</span>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block mb-1">Sólidos Retenidos</small>
                        <?php if ($data['solidos_retenidos_filtro'] === 'SI'): ?>
                            <span class="badge bg-warning-subtle text-warning border border-warning">⚠ SÍ</span>
                        <?php else: ?>
                            <span class="badge bg-success-subtle text-success border border-success">✓ NO</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!empty($data['info_caja_medidor'])): ?>
        <!-- Información de Caja y Medidor -->
        <div class="card border-0 bg-light mb-3">
            <div class="card-body">
                <h6 class="fw-bold mb-2">
                    <i class="bi bi-inbox text-primary"></i> Información de Caja y Medidor
                </h6>
                <p class="mb-0 text-muted"><?= nl2br(htmlspecialchars($data['info_caja_medidor'])) ?></p>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Observaciones -->
<div class="card border-0 bg-light mb-3">
    <div class="card-body">
        <h6 class="fw-bold mb-2">
            <i class="bi bi-chat-left-text text-primary"></i> Observaciones
        </h6>
        <p class="mb-0"><?= nl2br(htmlspecialchars($data['retiro_observacion'])) ?></p>
    </div>
</div>

<?php if (!empty($data['foto_imposibilidad'])): ?>
    <!-- Foto de Imposibilidad -->
    <div class="card border-0 bg-light">
        <div class="card-body">
            <h6 class="fw-bold mb-3">
                <i class="bi bi-camera text-primary"></i> Foto de Imposibilidad
            </h6>
            <div class="text-center">
                <img src="../<?= htmlspecialchars($data['foto_imposibilidad']) ?>" 
                     class="img-fluid rounded shadow-sm border" 
                     alt="Foto de imposibilidad"
                     style="max-height: 400px; max-width: 100%;">
            </div>
        </div>
    </div>
<?php endif; ?>
