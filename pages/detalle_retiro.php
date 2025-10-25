<?php
require_once '../config/database.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

try {
    $pdo = getConnection();
    $stmt = $pdo->prepare("
        SELECT 
            r.*,
            o.*,
            r.id as retiro_id,
            r.observacion as retiro_observacion
        FROM retiros_medidores r
        INNER JOIN ordenes_servicio o ON r.orden_servicio_id = o.id
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
                <small class="text-muted d-block">Cliente</small>
                <strong><?= htmlspecialchars($data['cliente']) ?></strong>
            </div>
            <div class="col-md-6">
                <small class="text-muted d-block">Usuario Reclamante</small>
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
                <small class="text-muted d-block">Técnico Responsable</small>
                <strong><?= htmlspecialchars($data['tecnico_responsable']) ?></strong>
            </div>
            <div class="col-md-6">
                <small class="text-muted d-block">Estado del Retiro</small>
                <?php if ($data['medidor_retirado'] === 'SI'): ?>
                    <span class="badge bg-success">✓ Medidor Retirado</span>
                <?php else: ?>
                    <span class="badge bg-warning text-dark">✗ No Retirado</span>
                <?php endif; ?>
            </div>
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
