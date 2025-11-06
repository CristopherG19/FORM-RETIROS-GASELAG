<?php
require_once '../config/database.php';

// Solo administradores
requireRole(['admin']);

$pageTitle = 'Asignación Masiva de OCs - Sistema GASELAG';
require_once '../includes/header.php';

// Obtener lista de técnicos activos
$tecnicos = getTecnicosActivos();

// Obtener OCs pendientes (sin retiro registrado y sin asignación activa)
try {
    $pdo = getConnection();
    $stmt = $pdo->query("
        SELECT DISTINCT o.orden_servicio, o.cliente, o.direccion, o.num_suministro
        FROM ordenes_servicio o
        LEFT JOIN retiros_medidores r ON o.id = r.orden_servicio_id AND r.estado_registro = 'activo'
        LEFT JOIN asignaciones_oc a ON o.orden_servicio = a.orden_servicio AND a.estado IN ('pendiente', 'en_proceso')
        WHERE r.id IS NULL AND a.id IS NULL
        ORDER BY o.orden_servicio ASC
    ");
    $ocsPendientes = $stmt->fetchAll();
} catch (Exception $e) {
    die("Error al obtener OCs pendientes: " . $e->getMessage());
}

$message = '';
$messageType = '';

// Procesar asignación masiva
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asignar_masivo'])) {
    $ocsSeleccionadas = $_POST['ocs_seleccionadas'] ?? [];
    $tecnicoId = intval($_POST['tecnico_id']);
    $notas = trim($_POST['notas_admin'] ?? '');
    
    if (empty($ocsSeleccionadas)) {
        $message = 'Debe seleccionar al menos una OC';
        $messageType = 'danger';
    } elseif ($tecnicoId <= 0) {
        $message = 'Debe seleccionar un técnico';
        $messageType = 'danger';
    } else {
        $resultado = asignarOCsMasivamente($ocsSeleccionadas, $tecnicoId, $_SESSION['user_id'], $notas);
        
        if ($resultado['success']) {
            $message = "✅ Asignación masiva completada: {$resultado['exitosas']} OCs asignadas exitosamente";
            if ($resultado['fallidas'] > 0) {
                $message .= ". {$resultado['fallidas']} OCs no pudieron ser asignadas";
            }
            $messageType = $resultado['fallidas'] > 0 ? 'warning' : 'success';
        } else {
            $message = 'Error en la asignación masiva';
            $messageType = 'danger';
        }
    }
}
?>

<style>
.oc-checkbox-item {
    transition: background-color 0.2s ease;
    cursor: pointer;
    padding: 10px;
    border-radius: 5px;
}
.oc-checkbox-item:hover {
    background-color: #f8f9fa;
}
.oc-checkbox-item input[type="checkbox"] {
    width: 1.2rem;
    height: 1.2rem;
    cursor: pointer;
}
.selected-count {
    font-size: 1.1rem;
    font-weight: bold;
}
</style>

<div class="container-fluid px-4 py-4">
    
    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
            <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : ($messageType === 'warning' ? 'exclamation-triangle' : 'x-circle') ?>"></i>
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Título -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="card-title">
                <i class="bi bi-people-fill text-primary"></i>
                Asignación Masiva de OCs a Técnicos
            </h2>
            <p class="text-muted mb-0">
                Selecciona múltiples OCs y asígnalas a un técnico de una sola vez
            </p>
        </div>
    </div>

    <?php if (empty($ocsPendientes)): ?>
        <div class="alert alert-info border-0 shadow-sm">
            <i class="bi bi-info-circle-fill me-2"></i>
            <strong>No hay OCs disponibles para asignar</strong>
            <p class="mb-0 mt-2">Todas las OCs ya fueron registradas o están asignadas a técnicos.</p>
        </div>
        <div class="text-center">
            <a href="../index.php" class="btn btn-outline-primary">
                <i class="bi bi-house"></i> Volver al Inicio
            </a>
        </div>
    <?php else: ?>
        <form method="POST" action="" id="asignacionMasivaForm">
            <input type="hidden" name="asignar_masivo" value="1">
            
            <div class="row">
                <!-- Panel izquierdo: Selección de OCs -->
                <div class="col-lg-8 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="bi bi-list-check"></i> Seleccionar OCs
                                </h5>
                                <div>
                                    <button type="button" class="btn btn-sm btn-light" onclick="seleccionarTodas()">
                                        <i class="bi bi-check-all"></i> Todas
                                    </button>
                                    <button type="button" class="btn btn-sm btn-light ms-1" onclick="deseleccionarTodas()">
                                        <i class="bi bi-x-lg"></i> Ninguna
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                            <div class="alert alert-info border-0 mb-3">
                                <i class="bi bi-info-circle-fill me-2"></i>
                                <strong><?= count($ocsPendientes) ?> OCs</strong> disponibles para asignación
                            </div>
                            
                            <?php foreach ($ocsPendientes as $oc): ?>
                                <div class="oc-checkbox-item border-bottom">
                                    <div class="form-check">
                                        <input class="form-check-input oc-checkbox" type="checkbox" 
                                               name="ocs_seleccionadas[]" value="<?= htmlspecialchars($oc['orden_servicio']) ?>" 
                                               id="oc_<?= htmlspecialchars($oc['orden_servicio']) ?>"
                                               onchange="actualizarContador()">
                                        <label class="form-check-label w-100" for="oc_<?= htmlspecialchars($oc['orden_servicio']) ?>">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <strong class="text-primary"><?= htmlspecialchars($oc['orden_servicio']) ?></strong>
                                                    <div class="small text-muted">
                                                        <i class="bi bi-person"></i> <?= htmlspecialchars($oc['cliente']) ?>
                                                    </div>
                                                    <div class="small text-muted">
                                                        <i class="bi bi-hash"></i> <?= htmlspecialchars($oc['num_suministro']) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Panel derecho: Asignación -->
                <div class="col-lg-4 mb-4">
                    <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-person-plus-fill"></i> Asignar a Técnico
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Contador de OCs seleccionadas -->
                            <div class="alert alert-warning border-0 text-center mb-4">
                                <div class="selected-count">
                                    <i class="bi bi-check-circle-fill"></i>
                                    <span id="contador">0</span> OCs seleccionadas
                                </div>
                            </div>
                            
                            <!-- Selección de técnico -->
                            <div class="mb-4">
                                <label for="tecnico_id" class="form-label fw-bold">
                                    <i class="bi bi-person-check"></i> Técnico *
                                </label>
                                <select class="form-select form-select-lg" id="tecnico_id" name="tecnico_id" required>
                                    <option value="">Seleccione un técnico...</option>
                                    <?php foreach ($tecnicos as $tecnico): ?>
                                        <option value="<?= $tecnico['id'] ?>">
                                            <?= htmlspecialchars($tecnico['nombre_completo']) ?>
                                            (@<?= htmlspecialchars($tecnico['username']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Notas -->
                            <div class="mb-4">
                                <label for="notas_admin" class="form-label fw-bold">
                                    <i class="bi bi-chat-left-text"></i> Notas (opcional)
                                </label>
                                <textarea class="form-control" id="notas_admin" name="notas_admin" rows="3" 
                                          placeholder="Instrucciones especiales para el técnico..."></textarea>
                            </div>
                            
                            <!-- Botón de asignación -->
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success btn-lg" id="btnAsignar" disabled>
                                    <i class="bi bi-check-circle-fill"></i> Asignar OCs Seleccionadas
                                </button>
                                <a href="../index.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle"></i> Cancelar
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
function actualizarContador() {
    const checkboxes = document.querySelectorAll('.oc-checkbox:checked');
    const count = checkboxes.length;
    document.getElementById('contador').textContent = count;
    
    // Habilitar/deshabilitar botón de asignar
    const btnAsignar = document.getElementById('btnAsignar');
    btnAsignar.disabled = count === 0;
}

function seleccionarTodas() {
    const checkboxes = document.querySelectorAll('.oc-checkbox');
    checkboxes.forEach(cb => cb.checked = true);
    actualizarContador();
}

function deseleccionarTodas() {
    const checkboxes = document.querySelectorAll('.oc-checkbox');
    checkboxes.forEach(cb => cb.checked = false);
    actualizarContador();
}

// Validación antes de enviar
document.getElementById('asignacionMasivaForm').addEventListener('submit', function(e) {
    const count = document.querySelectorAll('.oc-checkbox:checked').length;
    const tecnicoId = document.getElementById('tecnico_id').value;
    
    if (count === 0) {
        e.preventDefault();
        alert('Debe seleccionar al menos una OC');
        return false;
    }
    
    if (!tecnicoId) {
        e.preventDefault();
        alert('Debe seleccionar un técnico');
        return false;
    }
    
    // Confirmar asignación
    const confirmacion = confirm(`¿Está seguro de asignar ${count} OC(s) al técnico seleccionado?`);
    if (!confirmacion) {
        e.preventDefault();
        return false;
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>

