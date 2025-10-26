<?php
require_once '../config/database.php';

// Inicializar array de OCs seleccionadas si no existe
if (!isset($_SESSION['selected_ocs'])) {
    $_SESSION['selected_ocs'] = [];
}

$message = '';
$messageType = '';
$ocData = null;

// Buscar OC
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buscar_oc'])) {
    $oc_numero = trim($_POST['oc_code']);
    // Agregar prefijo OC- automáticamente
    $oc = 'OC-' . $oc_numero;
    
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT * FROM ordenes_servicio WHERE orden_servicio = ?");
        $stmt->execute([$oc]);
        $ocData = $stmt->fetch();
        
        if (!$ocData) {
            $message = "No se encontró la orden de servicio: $oc";
            $messageType = 'warning';
        }
    } catch (Exception $e) {
        $message = "Error al buscar: " . $e->getMessage();
        $messageType = 'danger';
    }
}

// Agregar OC a la selección
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_oc'])) {
    $oc = trim($_POST['oc_to_add']);
    
    // Verificar que la OC existe
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT orden_servicio FROM ordenes_servicio WHERE orden_servicio = ?");
        $stmt->execute([$oc]);
        
        if ($stmt->fetch()) {
            // Verificar que no esté ya agregada
            if (!in_array($oc, $_SESSION['selected_ocs'])) {
                $_SESSION['selected_ocs'][] = $oc;
                $message = "OC agregada correctamente";
                $messageType = 'success';
            } else {
                $message = "Esta OC ya fue agregada";
                $messageType = 'info';
            }
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = 'danger';
    }
}

// Eliminar OC de la selección
if (isset($_GET['eliminar']) && isset($_SESSION['selected_ocs'])) {
    $index = intval($_GET['eliminar']);
    if (isset($_SESSION['selected_ocs'][$index])) {
        unset($_SESSION['selected_ocs'][$index]);
        $_SESSION['selected_ocs'] = array_values($_SESSION['selected_ocs']); // Reindexar
        header('Location: buscar_oc.php');
        exit;
    }
}

// Limpiar todas las OCs
if (isset($_GET['limpiar'])) {
    $_SESSION['selected_ocs'] = [];
    header('Location: buscar_oc.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar Órdenes de Servicio - GASELAG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="mb-3">
            <a href="../index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Volver al Inicio
            </a>
        </div>

        <div class="row">
            <!-- Columna izquierda: Búsqueda -->
            <div class="col-lg-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0">
                            <i class="bi bi-search text-primary"></i>
                            Buscar Orden de Servicio
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                                <?= $message ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="oc_code" class="form-label">Número de OC</label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-light">OC-</span>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        id="oc_code" 
                                        name="oc_code" 
                                        placeholder="00001"
                                        required
                                        autofocus
                                    >
                                </div>
                                <small class="text-muted">Ingrese solo el número sin el prefijo OC-</small>
                            </div>
                            <button type="submit" name="buscar_oc" class="btn btn-primary w-100">
                                <i class="bi bi-search"></i> Buscar
                            </button>
                        </form>

                        <?php if ($ocData): ?>
                            <hr class="my-4">
                            <div class="alert alert-success">
                                <h5><i class="bi bi-check-circle"></i> OC Encontrada</h5>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <tr>
                                        <th>OC:</th>
                                        <td><strong><?= htmlspecialchars($ocData['orden_servicio']) ?></strong></td>
                                    </tr>
                                    <tr>
                                        <th>Cliente:</th>
                                        <td><?= htmlspecialchars($ocData['cliente']) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Usuario:</th>
                                        <td><?= htmlspecialchars($ocData['usuario_reclamante']) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Dirección:</th>
                                        <td><?= htmlspecialchars($ocData['direccion']) ?></td>
                                    </tr>
                                    <tr>
                                        <th>N° Serie:</th>
                                        <td><?= htmlspecialchars($ocData['num_serie_medidor']) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Marca:</th>
                                        <td><?= htmlspecialchars($ocData['marca_medidor']) ?></td>
                                    </tr>
                                </table>
                            </div>

                            <form method="POST" action="">
                                <input type="hidden" name="oc_to_add" value="<?= htmlspecialchars($ocData['orden_servicio']) ?>">
                                <button type="submit" name="agregar_oc" class="btn btn-success w-100">
                                    <i class="bi bi-plus-circle"></i> Agregar esta OC
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Columna derecha: OCs seleccionadas -->
            <div class="col-lg-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-list-check text-primary"></i>
                            OCs Seleccionadas
                        </h5>
                        <span class="badge bg-secondary">
                            <?= count($_SESSION['selected_ocs']) ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($_SESSION['selected_ocs'])): ?>
                            <div class="text-center text-muted py-5">
                                <i class="bi bi-inbox" style="font-size: 4rem;"></i>
                                <p class="mt-3">No hay OCs seleccionadas</p>
                                <p class="small">Busque y agregue órdenes de servicio para continuar</p>
                            </div>
                        <?php else: ?>
                            <div class="mb-3">
                                <?php foreach ($_SESSION['selected_ocs'] as $index => $oc): ?>
                                    <div class="d-flex justify-content-between align-items-center bg-light border rounded p-2 mb-2">
                                        <span class="fw-medium"><?= htmlspecialchars($oc) ?></span>
                                        <a href="?eliminar=<?= $index ?>" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                            <i class="bi bi-x"></i>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <hr>

                            <div class="d-grid gap-2">
                                <a href="vista_previa.php" class="btn btn-success btn-lg">
                                    <i class="bi bi-arrow-right-circle"></i>
                                    Continuar con Vista Previa
                                </a>
                                <a href="?limpiar=1" class="btn btn-outline-danger" onclick="return confirm('¿Está seguro de limpiar todas las OCs?')">
                                    <i class="bi bi-trash"></i>
                                    Limpiar Todo
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

