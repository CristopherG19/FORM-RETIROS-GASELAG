<?php
require_once '../config/database.php';

// Verificar autenticación (todos los usuarios)
requireRole(['admin', 'user']);

// Verificar que haya OCs seleccionadas
if (!isset($_SESSION['selected_ocs']) || empty($_SESSION['selected_ocs'])) {
    header('Location: buscar_oc.php');
    exit;
}

// Inicializar array de OCs temporales si no existe
if (!isset($_SESSION['ocs_temporales'])) {
    $_SESSION['ocs_temporales'] = [];
}

// Limpiar OCs temporales que ya están en BD (sincronizadas previamente)
if (!empty($_SESSION['ocs_temporales'])) {
    $pdo_temp = getConnection();
    foreach ($_SESSION['ocs_temporales'] as $oc => $datos) {
        $stmt_check = $pdo_temp->prepare("SELECT id FROM retiros_medidores WHERE orden_servicio = ?");
        $stmt_check->execute([$oc]);
        if ($stmt_check->fetch()) {
            // Ya está en BD, eliminar de temporales
            unset($_SESSION['ocs_temporales'][$oc]);
        }
    }
}

// Obtener datos de las OCs seleccionadas y su estado de registro
try {
    $pdo = getConnection();
    $placeholders = str_repeat('?,', count($_SESSION['selected_ocs']) - 1) . '?';
    $sql = "SELECT o.*, 
                   CASE WHEN r.id IS NOT NULL THEN 1 ELSE 0 END as tiene_retiro
            FROM ordenes_servicio o
            LEFT JOIN retiros_medidores r ON o.orden_servicio = r.orden_servicio
            WHERE o.orden_servicio IN ($placeholders) 
            ORDER BY o.orden_servicio";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($_SESSION['selected_ocs']);
    $ordenes = $stmt->fetchAll();
    
    // Determinar estado de cada OC y contadores
    $completadas = 0;
    $temporales = 0;
    foreach ($ordenes as &$orden) {
        $oc = $orden['orden_servicio'];
        
        if ($orden['tiene_retiro']) {
            // Ya está en BD
            $orden['estado'] = 'completado';
            $completadas++;
        } elseif (isset($_SESSION['ocs_temporales'][$oc])) {
            // Guardado temporal en sesión
            $orden['estado'] = 'temporal';
            $temporales++;
        } else {
            // Pendiente
            $orden['estado'] = 'pendiente';
        }
    }
    unset($orden); // Romper referencia
    
    $todasCompletadas = ($completadas == count($ordenes));
    $hayTemporales = ($temporales > 0);
} catch (Exception $e) {
    die("Error al cargar datos: " . $e->getMessage());
}

$pageTitle = 'Vista Previa - Sistema GASELAG';
require_once '../includes/header.php';
?>

<?php if (isset($_GET['temporal']) && $_GET['temporal'] == 1): ?>
    <div class="container mt-3">
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="bi bi-save-fill me-2"></i>
            <strong>¡Datos guardados temporalmente!</strong> 
            La OC <strong><?= htmlspecialchars($_GET['oc'] ?? '') ?></strong> ha sido guardada en sesión. 
            <strong>No olvides sincronizar para guardar definitivamente.</strong>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
<?php endif; ?>

<style>
        /* Hero gradient header */
        .hero-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        /* Table enhancements SIN MOVIMIENTO */
        .preview-table {
            border-radius: 8px;
            overflow: hidden;
        }
        .preview-table tbody tr {
            transition: background-color 0.3s ease;
        }
        .preview-table tbody tr:hover {
            background-color: #f8f9fa !important;
        }
        
        /* Acordeones suaves */
        .accordion-button {
            background-color: #f8f9fa;
            color: #495057;
        }
        .accordion-button:not(.collapsed) {
            background-color: #e9ecef;
            color: #495057;
        }
        
        /* Card hover effect SIN MOVIMIENTO */
        .preview-card {
            transition: box-shadow 0.3s ease;
        }
        .preview-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1) !important;
        }
        
        /* Badge pulse */
        .badge-pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        
        /* Responsive adjustments */
        @media (max-width: 767.98px) {
            .hero-header {
                padding: 1.5rem 0;
            }
            .hero-header h1 {
                font-size: 1.5rem;
            }
            .table-responsive {
                font-size: 0.875rem;
            }
        }
</style>

<!-- Hero Header -->
    <div class="hero-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="fw-bold mb-2">
                        <i class="bi bi-eye-fill me-2"></i>
                        Vista Previa de Órdenes
                    </h1>
                    <p class="mb-3 opacity-90">Revisa y confirma las OCs seleccionadas antes de continuar</p>
                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        <span class="badge bg-light text-dark px-3 py-2 badge-pulse">
                            <i class="bi bi-list-check me-1"></i>
                            <?= count($ordenes) ?> Orden<?= count($ordenes) > 1 ? 'es' : '' ?> de Servicio
                        </span>
                        <?php if ($todasCompletadas): ?>
                            <span class="badge bg-success px-3 py-2">
                                <i class="bi bi-check-circle-fill me-1"></i>
                                Todas sincronizadas
                            </span>
                        <?php elseif ($hayTemporales): ?>
                            <span class="badge bg-warning text-dark px-3 py-2">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                <?= $temporales ?> sin sincronizar
                            </span>
                        <?php elseif ($completadas > 0): ?>
                            <span class="badge bg-info px-3 py-2">
                                <i class="bi bi-hourglass-split me-1"></i>
                                <?= $completadas ?> de <?= count($ordenes) ?> sincronizadas
                            </span>
                        <?php else: ?>
                            <span class="badge bg-secondary px-3 py-2">
                                <i class="bi bi-clock me-1"></i>
                                Listas para registrar
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container pb-4">
        <!-- Pasos del proceso -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3">
                        <div class="row g-0 align-items-center">
                            <div class="col-auto me-3">
                                <i class="bi bi-diagram-3-fill text-primary fs-5"></i>
                            </div>
                            <div class="col">
                                <div class="row g-0">
                                    <div class="col-md-4">
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-success rounded-circle me-2" style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;">
                                                <i class="bi bi-check"></i>
                                            </span>
                                            <div>
                                                <strong class="d-block small">1. Búsqueda</strong>
                                                <small class="text-muted" style="font-size: 0.75rem;">OCs agregadas</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-primary rounded-circle me-2" style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;">2</span>
                                            <div>
                                                <strong class="d-block small">2. Vista Previa</strong>
                                                <small class="text-success" style="font-size: 0.75rem;">Estás aquí</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-secondary rounded-circle me-2" style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;">3</span>
                                            <div>
                                                <strong class="d-block small">3. Registro</strong>
                                                <small class="text-muted" style="font-size: 0.75rem;">A tu ritmo</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información importante -->
        <div class="alert alert-info border-0 shadow-sm">
            <div class="d-flex align-items-start">
                <i class="bi bi-info-circle-fill fs-3 me-3"></i>
                <div>
                    <strong class="d-block mb-2">Antes de continuar:</strong>
                    <ul class="mb-0 small">
                        <li>Verifica que todas las OCs sean correctas</li>
                        <li>Se crearán <?= count($ordenes) ?> formulario<?= count($ordenes) > 1 ? 's' : '' ?> de registro</li>
                        <li>Deberás completar la información de cada medidor y tomar las fotos correspondientes</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Tabla de órdenes -->
        <div class="card preview-card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-table me-2"></i>
                        Detalle de Órdenes Seleccionadas
                    </h5>
                    <span class="badge bg-light text-primary">
                        <?= count($ordenes) ?> OCs
                    </span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover preview-table mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width: 60px;">#</th>
                                <th>
                                    <i class="bi bi-tag-fill me-1"></i>
                                    Orden de Servicio
                                </th>
                                <th>
                                    <i class="bi bi-upc-scan me-1"></i>
                                    N° Serie Medidor
                                </th>
                                <th class="d-none d-md-table-cell">
                                    <i class="bi bi-hash me-1"></i>
                                    N° Suministro
                                </th>
                                <th class="d-none d-lg-table-cell">
                                    <i class="bi bi-geo-alt-fill me-1"></i>
                                    Dirección
                                </th>
                                <th class="text-center" style="width: 80px;">
                                    <i class="bi bi-clipboard-check"></i>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ordenes as $index => $orden): ?>
                                <tr>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark"><?= $index + 1 ?></span>
                                    </td>
                                    <td>
                                        <strong class="text-primary"><?= htmlspecialchars($orden['orden_servicio']) ?></strong>
                                        <br>
                                        <small class="text-muted d-md-none">
                                            <i class="bi bi-hash"></i>
                                            <?= htmlspecialchars($orden['num_suministro']) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info text-dark">
                                            <?= htmlspecialchars($orden['num_serie_medidor']) ?>
                                        </span>
                                    </td>
                                    <td class="d-none d-md-table-cell">
                                        <small><?= htmlspecialchars($orden['num_suministro']) ?></small>
                                    </td>
                                    <td class="d-none d-lg-table-cell">
                                        <small class="text-muted">
                                            <?= htmlspecialchars($orden['direccion']) ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($orden['estado'] == 'completado'): ?>
                                            <i class="bi bi-check-circle-fill text-success fs-5" title="Sincronizado en BD"></i>
                                        <?php elseif ($orden['estado'] == 'temporal'): ?>
                                            <a href="formulario_retiro.php?orden_servicio=<?= urlencode($orden['orden_servicio']) ?>" 
                                               class="btn btn-sm btn-warning" 
                                               title="Editar (guardado temporal)">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="formulario_retiro.php?orden_servicio=<?= urlencode($orden['orden_servicio']) ?>" 
                                               class="btn btn-sm btn-primary" 
                                               title="Registrar retiro">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-light border-0">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <small class="text-muted">
                        <i class="bi bi-info-circle"></i>
                        Total: <strong><?= count($ordenes) ?></strong> orden<?= count($ordenes) > 1 ? 'es' : '' ?> | 
                        <span class="text-success">✓ <?= $completadas ?></span> | 
                        <span class="text-warning">⚠ <?= $temporales ?></span> | 
                        <span class="text-primary">○ <?= count($ordenes) - $completadas - $temporales ?></span>
                    </small>
                    <div class="d-flex gap-3 d-none d-md-flex">
                        <small class="text-muted">
                            <i class="bi bi-pencil-square text-primary"></i> Pendiente
                        </small>
                        <small class="text-muted">
                            <i class="bi bi-pencil-square text-warning"></i> Temporal
                        </small>
                        <small class="text-muted">
                            <i class="bi bi-check-circle-fill text-success"></i> Sincronizado
                        </small>
                    </div>
                </div>
            </div>
        </div>

        

        <!-- Botones de acción -->
        <?php if ($hayTemporales): ?>
            <div class="alert alert-warning shadow-sm">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill fs-2 me-3"></i>
                    <div class="flex-grow-1">
                        <strong class="d-block">¡Atención! Tienes <?= $temporales ?> OC<?= $temporales > 1 ? 's' : '' ?> sin sincronizar</strong>
                        <small>Los datos están guardados temporalmente. Debes sincronizarlos para guardarlos definitivamente en la base de datos.</small>
                    </div>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-6 mx-auto">
                    <button type="button" class="btn btn-success w-100 btn-lg shadow" id="btnSincronizar">
                        <i class="bi bi-cloud-arrow-up me-2"></i>
                        Sincronizar Todo a Base de Datos
                        <i class="bi bi-check-circle ms-2"></i>
                    </button>
                </div>
            </div>
        <?php elseif ($todasCompletadas): ?>
            <div class="alert alert-success shadow-sm">
                <div class="d-flex align-items-center">
                    <i class="bi bi-check-circle-fill fs-2 me-3"></i>
                    <div class="flex-grow-1">
                        <strong class="d-block">¡Todas las OCs han sido sincronizadas!</strong>
                        <small>Has completado el registro de todos los retiros en esta sesión.</small>
                    </div>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <a href="limpiar_sesion.php" class="btn btn-outline-secondary w-100 btn-lg">
                        <i class="bi bi-trash me-2"></i>
                        Limpiar Sesión y Buscar Nuevas OCs
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="finalizar.php" class="btn btn-success w-100 btn-lg shadow">
                        <i class="bi bi-check2-all me-2"></i>
                        Ver Resumen y Finalizar
                        <i class="bi bi-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-primary text-center">
                <i class="bi bi-info-circle me-2"></i>
                Haz clic en el ícono <i class="bi bi-pencil-square mx-1"></i> de cada OC para registrar su retiro en el orden que prefieras
            </div>
        <?php endif; ?>

        <!-- Ayuda colapsable -->
         
        <div class="accordion mt-4" id="accordionAyuda">
            <div class="accordion-item border-0 shadow-sm">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                            data-bs-target="#collapseAyuda" aria-expanded="false">
                        <i class="bi bi-question-circle me-2 text-muted"></i>
                        <span class="text-muted">¿Qué sucederá al continuar?</span>
                    </button>
                </h2>
                <div id="collapseAyuda" class="accordion-collapse collapse" data-bs-parent="#accordionAyuda">
                    <div class="accordion-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="d-flex align-items-start">
                                    <span class="badge rounded-circle bg-light text-dark me-3" 
                                          style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; font-weight: 600;">1</span>
                                    <div>
                                        <strong class="d-block mb-1">Registro Flexible</strong>
                                        <small class="text-muted">
                                            Elige cualquier OC de la lista para registrar su retiro, sin orden específico
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-start">
                                    <span class="badge rounded-circle bg-light text-dark me-3" 
                                          style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; font-weight: 600;">2</span>
                                    <div>
                                        <strong class="d-block mb-1">Click en el Ícono</strong>
                                        <small class="text-muted">
                                            Haz clic en <i class="bi bi-pencil-square text-primary"></i> para abrir el formulario de esa OC
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-start">
                                    <span class="badge rounded-circle bg-light text-dark me-3" 
                                          style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; font-weight: 600;">3</span>
                                    <div>
                                        <strong class="d-block mb-1">Completa y Regresa</strong>
                                        <small class="text-muted">
                                            Llena el formulario y toma fotos. Al guardar, regresarás aquí automáticamente
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-start">
                                    <span class="badge rounded-circle bg-light text-dark me-3" 
                                          style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; font-weight: 600;">4</span>
                                    <div>
                                        <strong class="d-block mb-1">Marca de Completado</strong>
                                        <small class="text-muted">
                                            Las OCs registradas mostrarán <i class="bi bi-check-circle-fill text-success"></i> para indicar que están listas
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- JavaScript para Sincronización y Advertencias -->
<script>
// ========== ADVERTENCIA AL SALIR SIN SINCRONIZAR ==========
<?php if ($hayTemporales): ?>
window.addEventListener('beforeunload', function(e) {
    e.preventDefault();
    e.returnValue = '¡Tienes <?= $temporales ?> OC(s) sin sincronizar! Si sales ahora, perderás los datos temporales.';
    return e.returnValue;
});
<?php endif; ?>

// ========== BOTÓN DE SINCRONIZACIÓN ==========
<?php if ($hayTemporales): ?>
document.getElementById('btnSincronizar').addEventListener('click', function() {
    const btn = this;
    const originalHTML = btn.innerHTML;
    
    // Confirmar antes de sincronizar
    if (!confirm('¿Estás seguro de que deseas sincronizar <?= $temporales ?> OC(s) a la base de datos?\n\nEsta acción guardará todos los retiros temporales de forma permanente.')) {
        return;
    }
    
    // Deshabilitar botón y mostrar progreso
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sincronizando...';
    
    // Enviar solicitud AJAX
    fetch('sincronizar_retiros.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mostrar mensaje de éxito
            alert('✅ ¡Sincronización exitosa!\n\n' + 
                  data.sincronizados + ' OC(s) guardadas en la base de datos.');
            
            // Recargar página para actualizar estados
            window.location.reload();
        } else {
            // Error
            alert('❌ Error al sincronizar:\n\n' + data.error);
            btn.disabled = false;
            btn.innerHTML = originalHTML;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Error de conexión al sincronizar.\n\nPor favor, intenta nuevamente.');
        btn.disabled = false;
        btn.innerHTML = originalHTML;
    });
});
<?php endif; ?>
</script>

<?php require_once '../includes/footer.php'; ?>

