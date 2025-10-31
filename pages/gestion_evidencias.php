<?php
require_once '../config/database.php';

// Verificar autenticación y rol de administrador
requireRole(['admin']);

$currentUser = getCurrentUser();

// Procesar sanciones
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aplicar_sancion'])) {
    $retiroId = $_POST['retiro_id'];
    $motivo = trim($_POST['motivo_sancion']);

    if (aplicarSancionEvidencia($retiroId, $motivo, $_SESSION['user_id'])) {
        $success = "Sanción aplicada correctamente";
    } else {
        $error = "Error al aplicar sanción";
    }
}

// Obtener registros pendientes de evidencia
$evidenciasPendientes = getRegistrosPendientesEvidencia();

// Obtener registros con sanciones aplicadas
$sancionesAplicadas = getRegistrosConSanciones();

// Obtener estadísticas de cumplimiento
$estadisticas = getEstadisticasCumplimientoEvidencia();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Evidencias - GASELAG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .status-badge {
            font-size: 0.75rem;
        }
        .time-remaining {
            font-weight: bold;
        }
        .vencida {
            color: #dc3545;
        }
        .proxima-vencer {
            color: #fd7e14;
        }
        .stats-card {
            transition: box-shadow 0.2s;
        }
        .stats-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
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
                            <li><a class="dropdown-item" href="gestion_retiros.php">
                                <i class="bi bi-clipboard-check me-2"></i>Retiros
                            </a></li>
                            <li><a class="dropdown-item" href="gestion_imposibilidad.php">
                                <i class="bi bi-exclamation-triangle me-2"></i>Tipos
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
                            <i class="bi bi-camera text-primary me-2"></i>
                            Gestión de Evidencias
                        </h2>
                        <p class="text-muted mb-0">Control de evidencia fotográfica y sanciones</p>
                    </div>
                    <button class="btn btn-outline-primary" onclick="location.reload()">
                        <i class="bi bi-arrow-clockwise me-1"></i>Actualizar
                    </button>
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

        <!-- Estadísticas de cumplimiento -->
        <?php if ($estadisticas): ?>
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stats-card text-center bg-primary text-white">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $estadisticas['total_no_retirados']; ?></h5>
                            <p class="card-text mb-0">Total No Retirados</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card text-center bg-warning text-dark">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $estadisticas['requiere_evidencia']; ?></h5>
                            <p class="card-text mb-0">Requieren Evidencia</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card text-center bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $estadisticas['evidencia_completa']; ?></h5>
                            <p class="card-text mb-0">Evidencia Completa</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card text-center bg-info text-white">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo number_format($estadisticas['porcentaje_cumplimiento'], 1); ?>%</h5>
                            <p class="card-text mb-0">Cumplimiento</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" id="evidenciaTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pendientes-tab" data-bs-toggle="tab" data-bs-target="#pendientes" type="button" role="tab">
                    <i class="bi bi-clock me-1"></i>Pendientes (<?php echo count($evidenciasPendientes); ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="sanciones-tab" data-bs-toggle="tab" data-bs-target="#sanciones" type="button" role="tab">
                    <i class="bi bi-exclamation-triangle me-1"></i>Sanciones (<?php echo count($sancionesAplicadas); ?>)
                </button>
            </li>
        </ul>

        <div class="tab-content" id="evidenciaTabsContent">
            <!-- Registros Pendientes -->
            <div class="tab-pane fade show active" id="pendientes" role="tabpanel">
                <?php if (empty($evidenciasPendientes)): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle me-2"></i>
                        <strong>¡Excelente!</strong> No hay registros pendientes de evidencia fotográfica.
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Atención:</strong> <?php echo count($evidenciasPendientes); ?> registros requieren evidencia fotográfica.
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Fecha Registro</th>
                                    <th>OC</th>
                                    <th>Cliente</th>
                                    <th>Técnico</th>
                                    <th>Tipo Imposibilidad</th>
                                    <th>Tiempo Restante</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($evidenciasPendientes as $evidencia): ?>
                                    <tr>
                                        <td>
                                            <small><?php echo date('d/m/Y H:i', strtotime($evidencia['fecha_registro'])); ?></small>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($evidencia['orden_servicio']); ?></strong></td>
                                        <td><?php echo htmlspecialchars(substr($evidencia['cliente'], 0, 30)); ?></td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($evidencia['tecnico_responsable']); ?></strong>
                                                <br><small class="text-muted">@<?php echo htmlspecialchars($evidencia['username_tecnico']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning text-dark">
                                                <?php echo htmlspecialchars($evidencia['tipo_imposibilidad'] ?: 'No especificado'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $tiempoRestante = getTiempoRestanteEvidencia($evidencia['id']);
                                            $tiempoClass = '';
                                            if ($tiempoRestante === 'vencida') {
                                                $tiempoClass = 'vencida';
                                                $tiempoText = 'VENCIDA';
                                            } else {
                                                $tiempoClass = 'proxima-vencer';
                                                $tiempoText = $tiempoRestante;
                                            }
                                            ?>
                                            <span class="time-remaining <?php echo $tiempoClass; ?>">
                                                <?php echo $tiempoText; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-danger"
                                                    onclick="aplicarSancion(<?php echo $evidencia['id']; ?>, '<?= htmlspecialchars($evidencia['tecnico_responsable']) ?>')">
                                                <i class="bi bi-exclamation-triangle"></i> Sancionar
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sanciones Aplicadas -->
            <div class="tab-pane fade" id="sanciones" role="tabpanel">
                <?php if (empty($sancionesAplicadas)): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle me-2"></i>
                        <strong>¡Excelente!</strong> No hay sanciones aplicadas.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Fecha Sanción</th>
                                    <th>OC</th>
                                    <th>Técnico</th>
                                    <th>Motivo Sanción</th>
                                    <th>Admin que Aplicó</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sancionesAplicadas as $sancion): ?>
                                    <tr>
                                        <td>
                                            <small><?php echo date('d/m/Y H:i', strtotime($sancion['fecha_sancion'])); ?></small>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($sancion['orden_servicio']); ?></strong></td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($sancion['tecnico_responsable']); ?></strong>
                                                <br><small class="text-muted">@<?php echo htmlspecialchars($sancion['username_tecnico']); ?></small>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($sancion['motivo_sancion']); ?></td>
                                        <td><?php echo htmlspecialchars($sancion['admin_sancion']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal para aplicar sanción -->
    <div class="modal fade" id="sancionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle me-2"></i>Aplicar Sanción
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" id="sancion_retiro_id" name="retiro_id">
                    <div class="modal-body">
                        <div class="alert alert-danger">
                            <strong>Atención:</strong> Está a punto de aplicar una sanción a un técnico.
                            Asegúrese de que el motivo sea válido.
                        </div>

                        <div class="mb-3">
                            <label for="motivo_sancion" class="form-label">Motivo de la Sanción *</label>
                            <textarea class="form-control" id="motivo_sancion" name="motivo_sancion"
                                      rows="3" placeholder="Explique detalladamente el motivo de la sanción..." required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Técnico a Sancionar:</label>
                            <div id="tecnico_info" class="alert alert-warning"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="aplicar_sancion" class="btn btn-danger">Aplicar Sanción</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function aplicarSancion(retiroId, tecnicoNombre) {
            document.getElementById('sancion_retiro_id').value = retiroId;
            document.getElementById('tecnico_info').innerHTML = '<strong>' + tecnicoNombre + '</strong>';
            document.getElementById('motivo_sancion').value = '';

            new bootstrap.Modal(document.getElementById('sancionModal')).show();
        }

        // Auto-recargar después de acciones exitosas
        <?php if (isset($success)): ?>
            setTimeout(function() {
                location.reload();
            }, 2000);
        <?php endif; ?>
    </script>
</body>
</html>
