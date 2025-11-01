<?php
require_once '../config/database.php';

// Verificar autenticación y rol de administrador
requireRole(['admin']);

// Obtener información del usuario actual
$currentUser = getCurrentUser();

// Procesar acciones de admin
$action = $_GET['action'] ?? '';
$retiroId = $_GET['id'] ?? 0;

// Manejar mensaje de éxito desde redirección
$success = $_GET['success'] ?? null;

if ($action === 'reassign' && $retiroId && isset($_POST['new_user_id'])) {
    $newUserId = $_POST['new_user_id'];
    $reason = trim($_POST['reason'] ?? '');

    // Intentar reasignación con mejor manejo de errores
    try {
        if (reassignRetiro($retiroId, $newUserId, $_SESSION['user_id'], $reason)) {
            // Redirigir limpiamente para evitar re-ejecución automática
            header('Location: gestion_retiros.php?success=' . urlencode('Registro reasignado correctamente'));
            exit;
        } else {
            $error = "Error al reasignar el registro";
        }
    } catch (Exception $e) {
        $error = "Error en reasignación: " . $e->getMessage();
    }
}

if ($action === 'reopen' && $retiroId && isset($_POST['reason'])) {
    $reason = trim($_POST['reason']);

    if (reopenOC($retiroId, $_SESSION['user_id'], $reason)) {
        // Redirigir limpiamente para evitar re-ejecución automática
        header('Location: gestion_retiros.php?success=' . urlencode('OC reabierta correctamente para nuevo registro'));
        exit;
    } else {
        $error = "Error al reabrir la OC";
    }
}

// Obtener lista de usuarios para reasignación
$pdo = getConnection();
try {
    // Obtener información del retiro actual para excluir al técnico asignado
    $currentRetiroId = $_GET['id'] ?? 0;
    $currentUserId = null;

    if ($currentRetiroId) {
        $retiroSql = "SELECT usuario_id FROM retiros_medidores WHERE id = ?";
        $retiroStmt = $pdo->prepare($retiroSql);
        $retiroStmt->execute([$currentRetiroId]);
        $retiroData = $retiroStmt->fetch();
        $currentUserId = $retiroData ? $retiroData['usuario_id'] : null;
    }

    // Obtener usuarios disponibles, excluyendo al técnico actual si existe
    $usersSql = "SELECT id, username, nombre_completo, rol, estado
                 FROM usuarios
                 WHERE estado = 'activo' AND rol = 'user'";
    $params = [];

    if ($currentUserId) {
        $usersSql .= " AND id != ?";
        $params[] = $currentUserId;
    }

    $usersSql .= " ORDER BY nombre_completo";

    $usersStmt = $pdo->prepare($usersSql);
    $usersStmt->execute($params);
    $availableUsers = $usersStmt->fetchAll();
} catch (PDOException $e) {
    $availableUsers = [];
}

// Obtener filtros
$filtro_oc = isset($_GET['filtro_oc']) ? trim($_GET['filtro_oc']) : '';
$filtro_tecnico = isset($_GET['filtro_tecnico']) ? trim($_GET['filtro_tecnico']) : '';
$filtro_estado = isset($_GET['filtro_estado']) ? trim($_GET['filtro_estado']) : '';
$filtro_fecha_desde = isset($_GET['filtro_fecha_desde']) ? $_GET['filtro_fecha_desde'] : '';
$filtro_fecha_hasta = isset($_GET['filtro_fecha_hasta']) ? $_GET['filtro_fecha_hasta'] : '';

// Construir consulta de retiros
try {
    $sql = "SELECT
                r.*,
                o.cliente,
                o.usuario_reclamante,
                o.direccion,
                o.num_serie_medidor,
                o.marca_medidor,
                o.modelo_medidor,
                o.programacion_dia_retiro,
                u.nombre_completo as tecnico_responsable,
                u.username as username_tecnico,
                admin_u.nombre_completo as reasignado_por,
                r.estado_registro,
                CASE
                    WHEN r.medidor_retirado = 'NO'
                         AND (r.foto_imposibilidad IS NULL OR r.foto_imposibilidad = '')
                    THEN 1
                    ELSE 0
                END as es_caso_problematico
            FROM retiros_medidores r
            INNER JOIN ordenes_servicio o ON r.orden_servicio_id = o.id
            LEFT JOIN usuarios u ON r.usuario_id = u.id
            LEFT JOIN usuarios admin_u ON r.usuario_reasignado_por = admin_u.id
            WHERE 1=1";

    $params = [];

    if (!empty($filtro_oc)) {
        $sql .= " AND r.orden_servicio LIKE ?";
        $params[] = "%$filtro_oc%";
    }

    if (!empty($filtro_tecnico)) {
        $sql .= " AND (u.nombre_completo LIKE ? OR u.username LIKE ?)";
        $params[] = "%$filtro_tecnico%";
        $params[] = "%$filtro_tecnico%";
    }

    if (!empty($filtro_estado)) {
        $sql .= " AND r.estado_registro = ?";
        $params[] = $filtro_estado;
    }

    if (!empty($filtro_fecha_desde)) {
        $sql .= " AND DATE(r.fecha_registro) >= ?";
        $params[] = $filtro_fecha_desde;
    }

    if (!empty($filtro_fecha_hasta)) {
        $sql .= " AND DATE(r.fecha_registro) <= ?";
        $params[] = $filtro_fecha_hasta;
    }

    $sql .= " ORDER BY r.fecha_registro DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $retiros = $stmt->fetchAll();

    // Registrar consulta en auditoría
    logAudit(null, $_SESSION['user_id'], 'consulta_registros',
             'Consulta de gestión de retiros (admin) - ' . count($retiros) . ' registros encontrados');

} catch (PDOException $e) {
    $error = "Error al obtener retiros: " . $e->getMessage();
    $retiros = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Retiros - GASELAG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .retiro-card {
            transition: box-shadow 0.2s;
        }
        .retiro-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .status-badge {
            font-size: 0.75rem;
        }
        .action-buttons {
            white-space: nowrap;
        }
        .table-responsive {
            font-size: 0.875rem;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Barra de navegación -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="../index.php">
                <i class="bi bi-speedometer2 me-2"></i>
                GASELAG
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <span class="navbar-text">
                            <i class="bi bi-person-circle me-1"></i>
                            <?php echo htmlspecialchars($currentUser['nombre_completo']); ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <span class="navbar-text">
                            <i class="bi bi-shield-check ms-3 me-1"></i>
                            <span class="badge bg-warning text-dark">Administrador</span>
                        </span>
                    </li>
                </ul>

                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-gear me-1"></i>Gestión
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../index.php">
                                <i class="bi bi-house me-2"></i>Panel Principal
                            </a></li>
                            <li><a class="dropdown-item" href="gestion_usuarios.php">
                                <i class="bi bi-people me-2"></i>Usuarios
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-1">
                            <i class="bi bi-gear text-primary me-2"></i>
                            Gestión de Retiros
                        </h2>
                        <p class="text-muted mb-0">Control total de todos los registros del sistema</p>
                    </div>
                    <div class="text-end">
                        <a href="reporte_imposibilidad.php" class="btn btn-danger me-2">
                            <i class="bi bi-exclamation-triangle me-1"></i>Casos Críticos
                        </a>
                        <button class="btn btn-success" onclick="location.reload()">
                            <i class="bi bi-arrow-clockwise me-1"></i>Actualizar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alertas -->
        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Filtros -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-funnel text-primary me-2"></i>Filtros de Búsqueda
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" action="">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="filtro_oc" class="form-label">Orden de Servicio</label>
                            <input type="text" class="form-control" id="filtro_oc" name="filtro_oc"
                                   value="<?php echo htmlspecialchars($filtro_oc); ?>"
                                   placeholder="OC-12345">
                        </div>
                        <div class="col-md-3">
                            <label for="filtro_tecnico" class="form-label">Técnico</label>
                            <input type="text" class="form-control" id="filtro_tecnico" name="filtro_tecnico"
                                   value="<?php echo htmlspecialchars($filtro_tecnico); ?>"
                                   placeholder="Nombre o usuario">
                        </div>
                        <div class="col-md-2">
                            <label for="filtro_estado" class="form-label">Estado</label>
                            <select class="form-select" id="filtro_estado" name="filtro_estado">
                                <option value="">Todos</option>
                                <option value="activo" <?php echo $filtro_estado === 'activo' ? 'selected' : ''; ?>>Activo</option>
                                <option value="reabierto" <?php echo $filtro_estado === 'reabierto' ? 'selected' : ''; ?>>Reabierto</option>
                                <option value="reasignado" <?php echo $filtro_estado === 'reasignado' ? 'selected' : ''; ?>>Reasignado</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="filtro_fecha_desde" class="form-label">Desde</label>
                            <input type="date" class="form-control" id="filtro_fecha_desde" name="filtro_fecha_desde"
                                   value="<?php echo htmlspecialchars($filtro_fecha_desde); ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="filtro_fecha_hasta" class="form-label">Hasta</label>
                            <input type="date" class="form-control" id="filtro_fecha_hasta" name="filtro_fecha_hasta"
                                   value="<?php echo htmlspecialchars($filtro_fecha_hasta); ?>">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="bi bi-search me-1"></i>Filtrar
                            </button>
                            <a href="gestion_retiros.php" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle me-1"></i>Limpiar
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-primary"><?php echo count($retiros); ?></h5>
                        <p class="card-text mb-0">Total Registros</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-success">
                            <?php echo count(array_filter($retiros, function($r) { return $r['estado_registro'] === 'activo'; })); ?>
                        </h5>
                        <p class="card-text mb-0">Activos</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-warning">
                            <?php echo count(array_filter($retiros, function($r) { return $r['estado_registro'] === 'reabierto'; })); ?>
                        </h5>
                        <p class="card-text mb-0">Reabiertos</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-info">
                            <?php echo count(array_filter($retiros, function($r) { return $r['estado_registro'] === 'reasignado'; })); ?>
                        </h5>
                        <p class="card-text mb-0">Reasignados</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de retiros -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-list-ul text-primary me-2"></i>
                        Registros de Retiros (<?php echo count($retiros); ?>)
                    </h5>
                    <div class="btn-group">
                        <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-download me-1"></i>Exportar
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="exportar_excel.php">Excel</a></li>
                            <li><a class="dropdown-item" href="reporte_imposibilidad.php">Casos Críticos</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <?php if (empty($retiros)): ?>
                <div class="card-body text-center py-5">
                    <i class="bi bi-inbox" style="font-size: 4rem; color: #dee2e6;"></i>
                    <p class="mt-3 text-muted">No se encontraron registros con los filtros aplicados</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Fecha</th>
                                <th>OC</th>
                                <th>Cliente</th>
                                <th>N° Serie</th>
                                <th>Estado</th>
                                <th>Técnico</th>
                                <th>Estado Registro</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($retiros as $retiro): ?>
                                <tr class="<?php
                                    if ($retiro['es_caso_problematico']) {
                                        echo 'table-danger';
                                    } elseif ($retiro['estado_registro'] === 'reabierto') {
                                        echo 'table-warning';
                                    } elseif ($retiro['estado_registro'] === 'reasignado') {
                                        echo 'table-info';
                                    }
                                ?>">
                                    <td>
                                        <small><?php echo date('d/m/Y H:i', strtotime($retiro['fecha_registro'])); ?></small>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($retiro['orden_servicio']); ?></strong></td>
                                    <td><?php echo htmlspecialchars(substr($retiro['cliente'], 0, 30)); ?></td>
                                    <td><?php echo htmlspecialchars($retiro['num_serie_medidor']); ?></td>
                                    <td>
                                        <?php if ($retiro['medidor_retirado'] === 'SI'): ?>
                                            <span class="badge bg-success">Retirado</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">No Retirado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($retiro['tecnico_responsable']): ?>
                                            <div>
                                                <strong><?php echo htmlspecialchars($retiro['tecnico_responsable']); ?></strong>
                                                <br><small class="text-muted">@<?php echo htmlspecialchars($retiro['username_tecnico']); ?></small>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">No asignado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($retiro['estado_registro'] === 'activo'): ?>
                                            <span class="badge bg-success status-badge">Activo</span>
                                        <?php elseif ($retiro['estado_registro'] === 'reabierto'): ?>
                                            <span class="badge bg-warning text-dark status-badge">Reabierto</span>
                                        <?php elseif ($retiro['estado_registro'] === 'reasignado'): ?>
                                            <span class="badge bg-info status-badge">Reasignado</span>
                                        <?php endif; ?>

                                        <?php if ($retiro['fecha_reasignacion']): ?>
                                            <br><small class="text-muted">
                                                <?php echo date('d/m/Y H:i', strtotime($retiro['fecha_reasignacion'])); ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="action-buttons">
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" type="button"
                                                    onclick="viewDetails(<?php echo $retiro['id']; ?>, '<?php echo htmlspecialchars($retiro['orden_servicio']); ?>')"
                                                    title="Ver detalles">
                                                <i class="bi bi-eye"></i>
                                            </button>

                                            <?php if ($retiro['estado_registro'] !== 'reabierto'): ?>
                                                <button class="btn btn-outline-warning" type="button"
                                                        onclick="reopenOC(<?php echo $retiro['id']; ?>, '<?php echo htmlspecialchars($retiro['orden_servicio']); ?>')"
                                                        title="Reabrir para nuevo registro">
                                                    <i class="bi bi-arrow-clockwise"></i>
                                                </button>
                                            <?php endif; ?>

                                            <button class="btn btn-outline-info" type="button"
                                                    onclick="reassignOC(<?php echo $retiro['id']; ?>, '<?php echo htmlspecialchars($retiro['orden_servicio']); ?>')"
                                                    title="Reasignar a otro técnico">
                                                <i class="bi bi-person-plus"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal para ver detalles -->
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-info-circle me-2"></i>Detalles del Registro
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailsContent">
                    <!-- Contenido se cargará dinámicamente -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para reabrir OC -->
    <div class="modal fade" id="reopenModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-arrow-clockwise me-2"></i>Reabrir OC para Nuevo Registro
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="?action=reopen&id=" id="reopenForm">
                    <div class="modal-body">
                        <p>¿Está seguro que desea reabrir esta OC para que otro técnico pueda registrarla?</p>
                        <div class="alert alert-warning">
                            <strong>Importante:</strong> Esta acción permitirá que cualquier técnico registre nuevamente esta OC.
                        </div>
                        <div class="mb-3">
                            <label for="reopen_reason" class="form-label">Razón de reapertura *</label>
                            <textarea class="form-control" id="reopen_reason" name="reason" rows="3"
                                      placeholder="Explique por qué se reabre esta OC..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Reabrir OC</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para reasignar OC -->
    <div class="modal fade" id="reassignModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-person-plus me-2"></i>Reasignar OC a Otro Técnico
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="?action=reassign&id=" id="reassignForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="new_user_id" class="form-label">Seleccionar nuevo técnico *</label>
                            <select class="form-select" id="new_user_id" name="new_user_id" required>
                                <option value="">Elegir técnico...</option>
                                <?php foreach ($availableUsers as $user): ?>
                                    <option value="<?php echo $user['id']; ?>">
                                        <?php echo htmlspecialchars($user['nombre_completo']); ?>
                                        (@<?php echo htmlspecialchars($user['username']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="reassign_reason" class="form-label">Razón de reasignación *</label>
                            <textarea class="form-control" id="reassign_reason" name="reason" rows="3"
                                      placeholder="Explique por qué se reasigna esta OC..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-info">Reasignar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewDetails(retiroId, ordenServicio) {
            // Por simplicidad, redirigir a la página de detalle
            window.location.href = 'detalle_retiro.php?id=' + retiroId;
        }

        function reopenOC(retiroId, ordenServicio) {
            document.getElementById('reopenForm').action = '?action=reopen&id=' + retiroId;
            document.getElementById('reopen_reason').value = '';
            new bootstrap.Modal(document.getElementById('reopenModal')).show();
        }

        function reassignOC(retiroId, ordenServicio) {
            document.getElementById('reassignForm').action = '?action=reassign&id=' + retiroId;
            document.getElementById('reassign_reason').value = '';
            document.getElementById('new_user_id').value = '';
            new bootstrap.Modal(document.getElementById('reassignModal')).show();
        }

        // Prevenir múltiples submits en los formularios
        document.getElementById('reassignForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            console.log('Submit event triggered, button disabled:', submitBtn.disabled);

            // Si ya está deshabilitado, prevenir submit
            if (submitBtn.disabled) {
                e.preventDefault();
                alert('Ya se está procesando la reasignación...');
                console.log('Submit prevented - button was disabled');
                return false;
            }

            // Deshabilitar inmediatamente
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';
            console.log('Button disabled and form submitted');

            // El botón se rehabilitará cuando se recargue la página
        });

        document.getElementById('reopenForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn.disabled) {
                e.preventDefault();
                return false;
            }

            // Deshabilitar botón y mostrar loading
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Reabriendo...';

            // Re-habilitar después de 30 segundos por si acaso
            setTimeout(function() {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Reabrir OC';
            }, 30000);
        });

        // Ya no necesitamos auto-reload, usamos redirecciones limpias
    </script>
</body>
</html>
