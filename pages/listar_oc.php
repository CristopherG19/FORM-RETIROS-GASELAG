<?php
require_once '../config/database.php';

// Verificar autenticación (todos los usuarios)
requireRole(['admin', 'user']);

// Filtros
$filtro_oc = isset($_GET['filtro_oc']) ? trim($_GET['filtro_oc']) : '';
$filtro_cliente = isset($_GET['filtro_cliente']) ? trim($_GET['filtro_cliente']) : '';
$filtro_estado = isset($_GET['filtro_estado']) ? $_GET['filtro_estado'] : '';
$filtro_fecha_desde = isset($_GET['filtro_fecha_desde']) ? $_GET['filtro_fecha_desde'] : '';
$filtro_fecha_hasta = isset($_GET['filtro_fecha_hasta']) ? $_GET['filtro_fecha_hasta'] : '';

// Paginación
$itemsPorPagina = 20;
$pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$offset = ($pagina - 1) * $itemsPorPagina;

try {
    $pdo = getConnection();

    // Construir la consulta base para el conteo total
    $sqlCount = "SELECT COUNT(*) as total
                FROM ordenes_servicio o
                LEFT JOIN retiros_medidores r ON o.id = r.orden_servicio_id AND r.estado_registro = 'activo'
                WHERE 1=1";
    
    $params = [];

    if (!empty($filtro_oc)) {
        $sqlCount .= " AND o.orden_servicio LIKE ?";
        $params[] = "%$filtro_oc%";
    }

    if (!empty($filtro_cliente)) {
        $sqlCount .= " AND o.cliente LIKE ?";
        $params[] = "%$filtro_cliente%";
    }

    if (!empty($filtro_estado)) {
        if ($filtro_estado === 'registrado') {
            $sqlCount .= " AND r.id IS NOT NULL";
        } elseif ($filtro_estado === 'pendiente') {
            $sqlCount .= " AND r.id IS NULL";
        }
    }

    if (!empty($filtro_fecha_desde)) {
        $sqlCount .= " AND o.programacion_dia_retiro >= ?";
        $params[] = $filtro_fecha_desde;
    }

    if (!empty($filtro_fecha_hasta)) {
        $sqlCount .= " AND o.programacion_dia_retiro <= ?";
        $params[] = $filtro_fecha_hasta;
    }

    // Obtener total de registros
    $stmtCount = $pdo->prepare($sqlCount);
    $stmtCount->execute($params);
    $totalRegistros = $stmtCount->fetch()['total'];
    $totalPaginas = ceil($totalRegistros / $itemsPorPagina);

    // Consultar todas las OC y verificar si tienen retiro registrado (con paginación)
    $sql = "SELECT 
                o.*,
                CASE 
                    WHEN r.id IS NOT NULL THEN 'Registrado'
                    ELSE 'Pendiente'
                END as estado_registro,
                u.nombre_completo as tecnico_asignado,
                r.medidor_retirado,
                r.fecha_registro as fecha_retiro,
                TIMESTAMPDIFF(DAY, o.programacion_dia_retiro, CURDATE()) as dias_vencido
            FROM ordenes_servicio o
            LEFT JOIN retiros_medidores r ON o.id = r.orden_servicio_id AND r.estado_registro = 'activo'
            LEFT JOIN usuarios u ON r.usuario_id = u.id
            WHERE 1=1";

    // Aplicar los mismos filtros
    if (!empty($filtro_oc)) {
        $sql .= " AND o.orden_servicio LIKE ?";
    }

    if (!empty($filtro_cliente)) {
        $sql .= " AND o.cliente LIKE ?";
    }

    if (!empty($filtro_estado)) {
        if ($filtro_estado === 'registrado') {
            $sql .= " AND r.id IS NOT NULL";
        } elseif ($filtro_estado === 'pendiente') {
            $sql .= " AND r.id IS NULL";
        }
    }

    if (!empty($filtro_fecha_desde)) {
        $sql .= " AND o.programacion_dia_retiro >= ?";
    }

    if (!empty($filtro_fecha_hasta)) {
        $sql .= " AND o.programacion_dia_retiro <= ?";
    }

    $sql .= " ORDER BY o.programacion_dia_retiro DESC, o.created_at DESC LIMIT ? OFFSET ?";

    $paramsPaginacion = $params;
    $paramsPaginacion[] = $itemsPorPagina;
    $paramsPaginacion[] = $offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($paramsPaginacion);
    $ocs = $stmt->fetchAll();

    // Obtener estadísticas (sin filtros de paginación)
    $statsSql = "SELECT 
        COUNT(*) as total_oc,
        SUM(CASE WHEN r.id IS NOT NULL THEN 1 ELSE 0 END) as registrados,
        SUM(CASE WHEN r.id IS NULL THEN 1 ELSE 0 END) as pendientes
        FROM ordenes_servicio o
        LEFT JOIN retiros_medidores r ON o.id = r.orden_servicio_id AND r.estado_registro = 'activo'";
    
    $statsStmt = $pdo->prepare($statsSql);
    $statsStmt->execute();
    $stats = $statsStmt->fetch();

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// Función para generar URL de paginación
function getPaginationUrl($pagina) {
    global $filtro_oc, $filtro_cliente, $filtro_estado, $filtro_fecha_desde, $filtro_fecha_hasta;
    $params = [];
    if (!empty($filtro_oc)) $params[] = "filtro_oc=" . urlencode($filtro_oc);
    if (!empty($filtro_cliente)) $params[] = "filtro_cliente=" . urlencode($filtro_cliente);
    if (!empty($filtro_estado)) $params[] = "filtro_estado=" . urlencode($filtro_estado);
    if (!empty($filtro_fecha_desde)) $params[] = "filtro_fecha_desde=" . urlencode($filtro_fecha_desde);
    if (!empty($filtro_fecha_hasta)) $params[] = "filtro_fecha_hasta=" . urlencode($filtro_fecha_hasta);
    $params[] = "pagina=" . $pagina;
    return "listar_oc.php?" . implode("&", $params);
}

$pageTitle = 'Listar OCs - Sistema GASELAG';
require_once '../includes/header.php';
?>

<style>
        .status-badge {
            font-weight: 600;
            padding: 0.5rem 1rem;
        }
        .status-pendiente {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-registrado {
            background-color: #d4edda;
            color: #155724;
        }
        .vencido {
            background-color: #f8d7da;
        }
        .table-hover tbody tr:hover {
            cursor: pointer;
        }
</style>

<div class="container-fluid px-4 py-4">

        <!-- Título y descripción -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h2 class="card-title">
                    <i class="bi bi-list-ul text-primary"></i>
                    Todas las Órdenes de Servicio
                </h2>
                <p class="text-muted mb-0">
                    Visualización completa de todas las OC cargadas en el sistema, 
                    independientemente de si fueron registradas por un técnico o no.
                </p>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Total de OC</h6>
                                <h2 class="mb-0"><?= number_format($stats['total_oc']) ?></h2>
                            </div>
                            <i class="bi bi-file-earmark-text text-primary" style="font-size: 2.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Con Retiro Registrado</h6>
                                <h2 class="mb-0 text-success"><?= number_format($stats['registrados']) ?></h2>
                            </div>
                            <i class="bi bi-check-circle text-success" style="font-size: 2.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Pendientes de Registro</h6>
                                <h2 class="mb-0 text-warning"><?= number_format($stats['pendientes']) ?></h2>
                            </div>
                            <i class="bi bi-clock-history text-warning" style="font-size: 2.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-funnel text-primary"></i> Filtros de Búsqueda
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" action="listar_oc.php">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="filtro_oc" class="form-label">Orden de Servicio</label>
                            <input type="text" class="form-control" id="filtro_oc" name="filtro_oc" 
                                   value="<?= htmlspecialchars($filtro_oc) ?>" placeholder="Ej: OC-00001">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="filtro_cliente" class="form-label">Cliente</label>
                            <input type="text" class="form-control" id="filtro_cliente" name="filtro_cliente" 
                                   value="<?= htmlspecialchars($filtro_cliente) ?>" placeholder="Nombre del cliente">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="filtro_estado" class="form-label">Estado</label>
                            <select class="form-select" id="filtro_estado" name="filtro_estado">
                                <option value="">Todos</option>
                                <option value="registrado" <?= $filtro_estado === 'registrado' ? 'selected' : '' ?>>Con Registro</option>
                                <option value="pendiente" <?= $filtro_estado === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="filtro_fecha_desde" class="form-label">Fecha Desde</label>
                            <input type="date" class="form-control" id="filtro_fecha_desde" 
                                   name="filtro_fecha_desde" value="<?= htmlspecialchars($filtro_fecha_desde) ?>">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="filtro_fecha_hasta" class="form-label">Fecha Hasta</label>
                            <input type="date" class="form-control" id="filtro_fecha_hasta" 
                                   name="filtro_fecha_hasta" value="<?= htmlspecialchars($filtro_fecha_hasta) ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Buscar
                            </button>
                            <a href="listar_oc.php" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle"></i> Limpiar Filtros
                            </a>
                            <?php if (isAdmin()): ?>
                                <button type="button" class="btn btn-success float-end" onclick="exportarExcel()">
                                    <i class="bi bi-file-earmark-excel"></i> Exportar a Excel
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla de resultados -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-table text-primary"></i> Resultados (<?= $totalRegistros ?> OC totales)
                </h5>
                <div class="text-muted small">
                    Mostrando <?= count($ocs) ?> de <?= $totalRegistros ?> registros
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($ocs)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-inbox" style="font-size: 4rem;"></i>
                        <p class="mt-3">No se encontraron órdenes de servicio</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-dark sticky-top">
                                <tr>
                                    <th>Fecha Prog.</th>
                                    <th>Orden Servicio</th>
                                    <th>Cliente</th>
                                    <th>N° Suministro</th>
                                    <th>N° Serie Medidor</th>
                                    <th>Dirección</th>
                                    <th>Estado</th>
                                    <th>Técnico</th>
                                    <th>Medidor</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ocs as $oc): ?>
                                    <tr class="<?= $oc['dias_vencido'] > 0 && $oc['estado_registro'] === 'Pendiente' ? 'vencido' : '' ?>">
                                        <td>
                                            <?php if (!empty($oc['programacion_dia_retiro'])): ?>
                                                <?= date('d/m/Y', strtotime($oc['programacion_dia_retiro'])) ?>
                                                <?php if ($oc['programacion_hora_retiro']): ?>
                                                    <br><small class="text-muted"><?= $oc['programacion_hora_retiro'] ?></small>
                                                <?php endif; ?>
                                                <?php if ($oc['dias_vencido'] > 0 && $oc['estado_registro'] === 'Pendiente'): ?>
                                                    <br><span class="badge bg-danger">Vencido <?= $oc['dias_vencido'] ?> días</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?= htmlspecialchars($oc['orden_servicio']) ?></strong></td>
                                        <td><?= htmlspecialchars($oc['cliente']) ?></td>
                                        <td><?= htmlspecialchars($oc['num_suministro']) ?></td>
                                        <td><?= htmlspecialchars($oc['num_serie_medidor']) ?></td>
                                        <td>
                                            <small><?= htmlspecialchars(substr($oc['direccion'], 0, 50)) ?><?= strlen($oc['direccion']) > 50 ? '...' : '' ?></small>
                                        </td>
                                        <td>
                                            <?php if ($oc['estado_registro'] === 'Pendiente'): ?>
                                                <span class="badge status-badge status-pendiente">
                                                    <i class="bi bi-clock-history"></i> Pendiente
                                                </span>
                                            <?php else: ?>
                                                <span class="badge status-badge status-registrado">
                                                    <i class="bi bi-check-circle"></i> Registrado
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($oc['tecnico_asignado'])): ?>
                                                <small><?= htmlspecialchars($oc['tecnico_asignado']) ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($oc['medidor_retirado'])): ?>
                                                <?php if ($oc['medidor_retirado'] === 'SI'): ?>
                                                    <span class="badge bg-success">Retirado</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">No Retirado</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-info" onclick="verDetalle('<?= $oc['orden_servicio'] ?>')" title="Ver detalle">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <?php if ($oc['estado_registro'] === 'Pendiente'): ?>
                                                    <a href="buscar_oc.php?oc=<?= urlencode($oc['orden_servicio']) ?>" class="btn btn-primary" title="Registrar retiro">
                                                        <i class="bi bi-clipboard-check"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Paginación Bootstrap -->
            <?php if ($totalPaginas > 1): ?>
            <div class="card-footer bg-white border-top">
                <nav aria-label="Navegación de páginas">
                    <ul class="pagination justify-content-center mb-0">
                        <!-- Primera página y Anterior -->
                        <li class="page-item <?= $pagina == 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= getPaginationUrl(1) ?>" aria-label="Primera">
                                <span aria-hidden="true">&laquo;&laquo;</span>
                            </a>
                        </li>
                        <li class="page-item <?= $pagina == 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= getPaginationUrl($pagina - 1) ?>" aria-label="Anterior">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>

                        <!-- Números de página -->
                        <?php
                        $inicio = max(1, $pagina - 2);
                        $fin = min($totalPaginas, $pagina + 2);
                        
                        if ($inicio > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= getPaginationUrl(1) ?>">1</a>
                            </li>
                            <?php if ($inicio > 2): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $inicio; $i <= $fin; $i++): ?>
                            <li class="page-item <?= $i == $pagina ? 'active' : '' ?>">
                                <a class="page-link" href="<?= getPaginationUrl($i) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($fin < $totalPaginas): ?>
                            <?php if ($fin < $totalPaginas - 1): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= getPaginationUrl($totalPaginas) ?>"><?= $totalPaginas ?></a>
                            </li>
                        <?php endif; ?>

                        <!-- Siguiente y Última página -->
                        <li class="page-item <?= $pagina == $totalPaginas ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= getPaginationUrl($pagina + 1) ?>" aria-label="Siguiente">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                        <li class="page-item <?= $pagina == $totalPaginas ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= getPaginationUrl($totalPaginas) ?>" aria-label="Última">
                                <span aria-hidden="true">&raquo;&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
                
                <div class="text-center mt-2 text-muted small">
                    Página <?= $pagina ?> de <?= $totalPaginas ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal para ver detalle -->
    <div class="modal fade" id="detalleModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-white border-bottom">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-info-circle text-primary"></i> Detalle de la OC
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4" id="detalleContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="text-muted mt-3 mb-0">Cargando información...</p>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function verDetalle(ordenServicio) {
            const modal = new bootstrap.Modal(document.getElementById('detalleModal'));
            modal.show();
            
            fetch('detalle_oc.php?oc=' + encodeURIComponent(ordenServicio))
                .then(response => response.text())
                .then(data => {
                    document.getElementById('detalleContent').innerHTML = data;
                })
                .catch(error => {
                    document.getElementById('detalleContent').innerHTML = 
                        '<div class="alert alert-danger">Error al cargar los datos</div>';
                });
        }

        function exportarExcel() {
            const params = new URLSearchParams(window.location.search);
            window.location.href = 'exportar_excel.php?tipo=todas&' + params.toString();
        }
    </script>

<?php require_once '../includes/footer.php'; ?>
