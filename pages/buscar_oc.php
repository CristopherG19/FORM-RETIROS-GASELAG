<?php
require_once '../config/database.php';
require_once '../config/AppConfig.php';

// Verificar autenticación (todos los usuarios)
requireRole(['admin', 'user']);

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
            // ===== VALIDACIÓN ANTI-DUPLICACIÓN =====
            // Verificar si esta OC ya fue registrada por alguien
            $existingRetiro = checkExistingRetiro($oc);

            if ($existingRetiro) {
                // Registrar intento en auditoría
                logAudit(null, $_SESSION['user_id'], 'busqueda_oc',
                         "Intento de búsqueda de OC ya procesada: $oc por {$existingRetiro['tecnico_responsable']}",
                         $oc);

                if (isAdmin()) {
                    // Admin puede ver y decidir qué hacer
                    $message = "⚠️ <strong>Esta OC ya fue registrada</strong> por: <strong>{$existingRetiro['tecnico_responsable']}</strong> el " .
                              date('d/m/Y H:i', strtotime($existingRetiro['fecha_registro'])) .
                              ". Como administrador, puede reabrir esta OC si es necesario.";
                    $messageType = 'warning';

                    // Mostrar información adicional del registro
                    $estadoInfo = "";
                    if ($existingRetiro['medidor_retirado'] === 'SI') {
                        $estadoInfo = " <em>(Medidor SÍ fue retirado)</em>";
                    } else {
                        $estadoInfo = " <em>(Medidor NO fue retirado)</em>";
                    }
                    $message .= $estadoInfo;

                } else {
                    // Técnico no puede procesar OC ya registrada
                    $message = "❌ <strong>Esta OC ya fue registrada</strong> por: <strong>{$existingRetiro['tecnico_responsable']}</strong> el " .
                              date('d/m/Y H:i', strtotime($existingRetiro['fecha_registro'])) .
                              ". No se puede procesar nuevamente.";
                    $messageType = 'danger';

                    // Mostrar información adicional
                    $estadoInfo = "";
                    if ($existingRetiro['medidor_retirado'] === 'SI') {
                        $estadoInfo = " <em>(Medidor SÍ fue retirado)</em>";
                    } else {
                        $estadoInfo = " <em>(Medidor NO fue retirado)</em>";
                    }
                    $message .= $estadoInfo;
                }
            } else {
                // OC no registrada, verificar que no esté ya en la sesión
                if (!in_array($oc, $_SESSION['selected_ocs'])) {
                    $_SESSION['selected_ocs'][] = $oc;

                    // Registrar búsqueda exitosa en auditoría
                    logAudit(null, $_SESSION['user_id'], 'busqueda_oc',
                             "OC agregada a la sesión: $oc",
                             $oc);

                    $message = "✅ OC agregada correctamente a la sesión";
                    $messageType = 'success';
                } else {
                    $message = "ℹ️ Esta OC ya fue agregada a la sesión actual";
                    $messageType = 'info';
                }
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
    <style>
        /* Hero gradient header */
        .hero-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        /* Card hover effects SIN MOVIMIENTO */
        .search-card, .selected-card {
            transition: box-shadow 0.3s ease;
            border: none;
        }
        .search-card:hover, .selected-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1) !important;
        }
        
        /* OC Item hover SIN MOVIMIENTO */
        .oc-item {
            transition: background-color 0.2s ease;
        }
        .oc-item:hover {
            background-color: #e9ecef !important;
        }
        
        /* Input focus effect */
        .form-control:focus, .input-group-text {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        /* Empty state */
        .empty-state {
            opacity: 0.6;
        }
        
        /* Pulse animation for badge */
        .badge-pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        /* Button hover effects SIN MOVIMIENTO */
        .btn-search:hover {
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        /* Responsive adjustments */
        @media (max-width: 767.98px) {
            .hero-header {
                padding: 1.5rem 0;
            }
            .hero-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body class="bg-light">
    <?php include '../includes/session_middleware.php'; ?>
    
    <!-- Navbar -->
    <nav class="navbar navbar-dark bg-dark shadow-sm">
        <div class="container-fluid px-3 px-md-4">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="../index.php">
                <i class="bi bi-speedometer2 me-2 fs-4"></i>
                <span class="d-none d-sm-inline">GASELAG</span>
            </a>
            <a href="../index.php" class="btn btn-outline-light btn-sm">
                <i class="bi bi-arrow-left"></i>
                <span class="d-none d-sm-inline"> Volver</span>
            </a>
        </div>
    </nav>

    <!-- Hero Header -->
    <div class="hero-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="fw-bold mb-2">
                        <i class="bi bi-search me-2"></i>
                        Buscar Órdenes de Servicio
                    </h1>
                    <p class="mb-3 opacity-90">Encuentra y selecciona las OCs para registrar retiros</p>
                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        <?php if (!empty($_SESSION['selected_ocs'])): ?>
                            <span class="badge bg-success px-3 py-2 badge-pulse">
                                <i class="bi bi-check-circle me-1"></i>
                                <?= count($_SESSION['selected_ocs']) ?> OC<?= count($_SESSION['selected_ocs']) > 1 ? 's' : '' ?> seleccionada<?= count($_SESSION['selected_ocs']) > 1 ? 's' : '' ?>
                            </span>
                        <?php else: ?>
                            <span class="badge bg-light text-dark px-3 py-2">
                                <i class="bi bi-inbox me-1"></i>
                                Ninguna OC seleccionada
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container pb-4">

        <!-- Mensajes globales -->
        <?php if ($message): ?>
            <div class="alert alert-<?= e($messageType) ?> alert-dismissible fade show shadow-sm">
                <div class="d-flex align-items-start">
                    <i class="bi bi-<?= $messageType === 'danger' ? 'exclamation-triangle' : ($messageType === 'warning' ? 'exclamation-circle' : ($messageType === 'info' ? 'info-circle' : 'check-circle')) ?> fs-4 me-3"></i>
                    <div class="flex-grow-1">
                        <?= eSafe($message) ?>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-3 g-md-4">
            <!-- Columna izquierda: Búsqueda -->
            <div class="col-lg-6 mb-3">
                <div class="card search-card shadow-sm h-100">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-search-heart fs-4 me-2"></i>
                            <h5 class="mb-0">Buscar Orden de Servicio</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-4">
                                <label for="oc_code" class="form-label fw-bold">
                                    <i class="bi bi-hash"></i> Número de OC
                                </label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-light fw-bold text-primary">
                                        <i class="bi bi-tag"></i> OC-
                                    </span>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        id="oc_code" 
                                        name="oc_code" 
                                        placeholder="00001"
                                        pattern="[0-9]+"
                                        required
                                        autofocus
                                    >
                                </div>
                                <div class="form-text">
                                    <i class="bi bi-info-circle"></i>
                                    Ingrese solo el número, el prefijo OC- se agrega automáticamente
                                </div>
                            </div>
                            <div class="d-grid">
                                <button type="submit" name="buscar_oc" class="btn btn-primary btn-lg btn-search">
                                    <i class="bi bi-search me-2"></i>Buscar OC
                                </button>
                            </div>
                        </form>

                        <?php if ($ocData): ?>
                            <hr class="my-4">
                            
                            <!-- Resultado de búsqueda -->
                            <div class="alert alert-success border-0 shadow-sm">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-check-circle-fill fs-3 me-3"></i>
                                    <div>
                                        <h5 class="mb-0">¡OC Encontrada!</h5>
                                        <small class="text-muted">Información de la orden de servicio</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Información de la OC -->
                            <div class="card border-success mb-3">
                                <div class="card-body">
                                    <div class="row g-2">
                                        <div class="col-12">
                                            <div class="d-flex justify-content-between align-items-center bg-light p-2 rounded mb-2">
                                                <span class="text-muted small">
                                                    <i class="bi bi-tag-fill"></i> Orden de Servicio:
                                                </span>
                                                <span class="badge bg-primary fs-6">
                                                    <?= htmlspecialchars($ocData['orden_servicio']) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="p-2 border-bottom">
                                                <small class="text-muted d-block">
                                                    <i class="bi bi-person-fill text-primary"></i> Cliente
                                                </small>
                                                <strong><?= htmlspecialchars($ocData['cliente']) ?></strong>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="p-2 border-bottom">
                                                <small class="text-muted d-block">
                                                    <i class="bi bi-person-badge text-info"></i> Usuario Reclamante
                                                </small>
                                                <strong><?= htmlspecialchars($ocData['usuario_reclamante']) ?></strong>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="p-2 border-bottom">
                                                <small class="text-muted d-block">
                                                    <i class="bi bi-geo-alt-fill text-danger"></i> Dirección
                                                </small>
                                                <strong><?= htmlspecialchars($ocData['direccion']) ?></strong>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="p-2">
                                                <small class="text-muted d-block">
                                                    <i class="bi bi-upc-scan text-warning"></i> N° Serie
                                                </small>
                                                <strong><?= htmlspecialchars($ocData['num_serie_medidor']) ?></strong>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="p-2">
                                                <small class="text-muted d-block">
                                                    <i class="bi bi-bookmark-star text-success"></i> Marca
                                                </small>
                                                <strong><?= htmlspecialchars($ocData['marca_medidor']) ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Botón agregar -->
                            <form method="POST" action="">
                                <input type="hidden" name="oc_to_add" value="<?= htmlspecialchars($ocData['orden_servicio']) ?>">
                                <div class="d-grid">
                                    <button type="submit" name="agregar_oc" class="btn btn-success btn-lg">
                                        <i class="bi bi-plus-circle-fill me-2"></i>Agregar esta OC
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <!-- Empty state cuando no hay búsqueda -->
                            <div class="text-center text-muted py-5 empty-state">
                                <i class="bi bi-search" style="font-size: 4rem;"></i>
                                <p class="mt-3 mb-0">Ingresa el número de OC para buscar</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Columna derecha: OCs seleccionadas -->
            <div class="col-lg-6 mb-3">
                <div class="card selected-card shadow-sm h-100">
                    <div class="card-header bg-success text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-check2-square fs-4 me-2"></i>
                                <h5 class="mb-0">OCs Seleccionadas</h5>
                            </div>
                            <span class="badge bg-light text-success fs-6 badge-pulse">
                                <?= count($_SESSION['selected_ocs']) ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($_SESSION['selected_ocs'])): ?>
                            <!-- Empty state -->
                            <div class="text-center text-muted py-5 empty-state">
                                <i class="bi bi-inbox" style="font-size: 4rem;"></i>
                                <p class="mt-3 fw-bold">No hay OCs seleccionadas</p>
                                <p class="small">Busque y agregue órdenes de servicio para continuar con el registro de retiros</p>
                                <div class="mt-4">
                                    <span class="badge bg-light text-dark p-2">
                                        <i class="bi bi-arrow-left"></i>
                                        Use el buscador de la izquierda
                                    </span>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Lista de OCs -->
                            <div class="mb-3">
                                <small class="text-muted d-block mb-2">
                                    <i class="bi bi-info-circle"></i>
                                    Órdenes listas para procesar (<?= count($_SESSION['selected_ocs']) ?>)
                                </small>
                                
                                <div class="list-group">
                                    <?php foreach ($_SESSION['selected_ocs'] as $index => $oc): ?>
                                        <div class="list-group-item oc-item d-flex justify-content-between align-items-center p-3 border">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-check-circle-fill text-success me-3 fs-5"></i>
                                                <div>
                                                    <span class="fw-bold text-dark"><?= htmlspecialchars($oc) ?></span>
                                                    <br>
                                                    <small class="text-muted">
                                                        <i class="bi bi-clock"></i>
                                                        Agregada a la sesión
                                                    </small>
                                                </div>
                                            </div>
                                            <a href="?eliminar=<?= $index ?>" 
                                               class="btn btn-sm btn-outline-danger" 
                                               title="Eliminar de la lista"
                                               onclick="return confirm('¿Eliminar <?= htmlspecialchars($oc) ?> de la lista?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <hr class="my-4">

                            <!-- Acciones -->
                            <div class="d-grid gap-2">
                                <a href="vista_previa.php" class="btn btn-success btn-lg shadow">
                                    <i class="bi bi-arrow-right-circle-fill me-2"></i>
                                    Continuar con Vista Previa
                                </a>
                                <a href="?limpiar=1" 
                                   class="btn btn-outline-danger" 
                                   onclick="return confirm('¿Está seguro de eliminar todas las OCs seleccionadas?\n\nEsta acción no se puede deshacer.')">
                                    <i class="bi bi-trash3"></i>
                                    Limpiar Todo
                                </a>
                            </div>

                            <!-- Info adicional -->
                            <div class="alert alert-info border-0 mt-3 mb-0">
                                <small>
                                    <i class="bi bi-info-circle-fill me-1"></i>
                                    <strong>Siguiente paso:</strong> Haz clic en "Continuar" para revisar las OCs y proceder al registro de retiros.
                                </small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Instrucciones rápidas -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-primary mb-3">
                            <i class="bi bi-lightbulb-fill me-2"></i>
                            ¿Cómo funciona?
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="d-flex align-items-start">
                                    <span class="badge bg-primary rounded-circle me-3" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">1</span>
                                    <div>
                                        <strong class="d-block mb-1">Buscar OC</strong>
                                        <small class="text-muted">Ingresa el número de la orden de servicio para buscarla en el sistema</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-start">
                                    <span class="badge bg-success rounded-circle me-3" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">2</span>
                                    <div>
                                        <strong class="d-block mb-1">Agregar a la lista</strong>
                                        <small class="text-muted">Verifica la información y agrégala a tu lista de OCs a procesar</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-start">
                                    <span class="badge bg-info rounded-circle me-3" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">3</span>
                                    <div>
                                        <strong class="d-block mb-1">Continuar</strong>
                                        <small class="text-muted">Cuando tengas todas las OCs, continúa para registrar los retiros</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

