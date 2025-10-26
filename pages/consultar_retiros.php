<?php
require_once '../config/database.php';

// Filtros
$filtro_oc = isset($_GET['filtro_oc']) ? trim($_GET['filtro_oc']) : '';
$filtro_fecha_desde = isset($_GET['filtro_fecha_desde']) ? $_GET['filtro_fecha_desde'] : '';
$filtro_fecha_hasta = isset($_GET['filtro_fecha_hasta']) ? $_GET['filtro_fecha_hasta'] : '';
$filtro_retirado = isset($_GET['filtro_retirado']) ? $_GET['filtro_retirado'] : '';
$filtro_problematicos = isset($_GET['filtro_problematicos']) ? $_GET['filtro_problematicos'] : '';

// Construir consulta
try {
    $pdo = getConnection();
    
    $sql = "SELECT
                r.*,
                o.cliente,
                o.usuario_reclamante,
                o.direccion,
                o.num_serie_medidor,
                o.marca_medidor,
                o.modelo_medidor,
                o.programacion_dia_retiro,
                CASE
                    WHEN r.medidor_retirado = 'NO'
                         AND (r.foto_imposibilidad IS NULL OR r.foto_imposibilidad = '')
                    THEN 1
                    WHEN r.medidor_retirado = 'NO'
                         AND (r.foto_imposibilidad IS NOT NULL AND r.foto_imposibilidad != '')
                    THEN 2
                    ELSE 0
                END as tipo_problema,
                CASE
                    WHEN r.medidor_retirado = 'NO'
                         AND (r.foto_imposibilidad IS NULL OR r.foto_imposibilidad = '')
                    THEN 1
                    ELSE 0
                END as es_caso_problematico
            FROM retiros_medidores r
            INNER JOIN ordenes_servicio o ON r.orden_servicio_id = o.id
            WHERE 1=1";
    
    $params = [];
    
    if (!empty($filtro_oc)) {
        $sql .= " AND r.orden_servicio LIKE ?";
        $params[] = "%$filtro_oc%";
    }
    
    if (!empty($filtro_fecha_desde)) {
        $sql .= " AND DATE(o.programacion_dia_retiro) >= ?";
        $params[] = $filtro_fecha_desde;
    }
    
    if (!empty($filtro_fecha_hasta)) {
        $sql .= " AND DATE(o.programacion_dia_retiro) <= ?";
        $params[] = $filtro_fecha_hasta;
    }
    
    if (!empty($filtro_retirado)) {
        $sql .= " AND r.medidor_retirado = ?";
        $params[] = $filtro_retirado;
    }

    if ($filtro_problematicos === 'SI') {
        $sql .= " AND r.medidor_retirado = 'NO'
                  AND (r.foto_imposibilidad IS NULL OR r.foto_imposibilidad = '')";
    }

    $sql .= " ORDER BY o.programacion_dia_retiro DESC, r.fecha_registro DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $retiros = $stmt->fetchAll();
    
    // Estadísticas
    $stmt = $pdo->query("SELECT
        COUNT(*) as total,
        SUM(CASE WHEN medidor_retirado = 'SI' THEN 1 ELSE 0 END) as retirados,
        SUM(CASE WHEN medidor_retirado = 'NO' THEN 1 ELSE 0 END) as no_retirados
        FROM retiros_medidores");
    $stats = $stmt->fetch();

    // Estadística de casos críticos (registros NO retirados SIN evidencia fotográfica)
    // NOTA: Cualquier registro "NO retirado" sin foto se considera crítico, independientemente del campo de imposibilidad
    $casosProblematicos = countRetirosImposibilidadSinFoto($pdo);


} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultar Retiros - GASELAG</title>
    <!-- Forzar recarga para evitar cache -->
    <meta name="version" content="2.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Cache buster para evitar problemas de cache -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
</head>
<body class="bg-light">
    <div class="container-fluid px-4 py-4">
        <div class="mb-3">
            <a href="../index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Volver al Inicio
            </a>
        </div>

        <!-- Estadísticas -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Total Registros</h6>
                                <h2 class="mb-0"><?= $stats['total'] ?></h2>
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
                                <h6 class="text-muted mb-1">Medidores Retirados</h6>
                                <h2 class="mb-0 text-success"><?= $stats['retirados'] ?></h2>
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
                                <h6 class="text-muted mb-1">No Retirados</h6>
                                <h2 class="mb-0 text-warning"><?= $stats['no_retirados'] ?></h2>
                            </div>
                            <i class="bi bi-x-circle text-warning" style="font-size: 2.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas de casos problemáticos -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">
                                    <i class="bi bi-exclamation-triangle text-danger"></i>
                                    Casos Críticos (Sin Evidencia)
                                </h6>
                                <h2 class="mb-0 text-danger">
                                    <?php if ($casosProblematicos > 0): ?>
                                        <a href="consultar_retiros.php?filtro_problematicos=SI"
                                           class="text-decoration-none">
                                            <?= $casosProblematicos ?>
                                        </a>
                                    <?php else: ?>
                                        <?= $casosProblematicos ?>
                                    <?php endif; ?>
                                </h2>
                                <small class="text-muted">
                                    Registros no retirados sin evidencia fotográfica (requieren atención)
                                </small>
                            </div>
                            <i class="bi bi-exclamation-triangle text-danger" style="font-size: 3rem;"></i>
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
                <form method="GET" action="">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="filtro_oc" class="form-label">Orden de Servicio</label>
                            <input type="text" class="form-control" id="filtro_oc" name="filtro_oc" 
                                   value="<?= htmlspecialchars($filtro_oc) ?>" placeholder="Ej: OC-00001">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="filtro_fecha_desde" class="form-label">Fecha Retiro Desde</label>
                            <input type="date" class="form-control" id="filtro_fecha_desde" 
                                   name="filtro_fecha_desde" value="<?= htmlspecialchars($filtro_fecha_desde) ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="filtro_fecha_hasta" class="form-label">Fecha Retiro Hasta</label>
                            <input type="date" class="form-control" id="filtro_fecha_hasta" 
                                   name="filtro_fecha_hasta" value="<?= htmlspecialchars($filtro_fecha_hasta) ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="filtro_retirado" class="form-label">Estado del Medidor</label>
                            <select class="form-select" id="filtro_retirado" name="filtro_retirado">
                                <option value="">Todos</option>
                                <option value="SI" <?= $filtro_retirado === 'SI' ? 'selected' : '' ?>>Medidor Retirado</option>
                                <option value="NO" <?= $filtro_retirado === 'NO' ? 'selected' : '' ?>>Medidor No Retirado</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="filtro_problematicos" class="form-label">Casos Críticos</label>
                            <select class="form-select" id="filtro_problematicos" name="filtro_problematicos">
                                <option value="">Todos</option>
                                <option value="SI" <?= $filtro_problematicos === 'SI' ? 'selected' : '' ?>>Solo sin evidencia</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Buscar
                            </button>
                            <a href="consultar_retiros.php" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle"></i> Limpiar
                            </a>
                        </div>
                        <div class="col-md-6 text-end">
                            <a href="reporte_imposibilidad.php" class="btn btn-danger me-2">
                                <i class="bi bi-exclamation-triangle"></i> Ver Críticos
                            </a>
                            <button type="button" class="btn btn-success" onclick="exportarExcel()">
                                <i class="bi bi-file-earmark-excel"></i> Exportar a Excel
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla de resultados -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-table text-primary"></i> Registros de Retiros (<?= count($retiros) ?>)
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($retiros)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-inbox" style="font-size: 4rem;"></i>
                        <p class="mt-3">No se encontraron registros</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-dark sticky-top">
                                <tr>
                                    <th>Fecha Retiro</th>
                                    <th>Orden Servicio</th>
                                    <th>Cliente</th>
                                    <th>N° Serie</th>
                                    <th>Medidor Retirado</th>
                                    <th>Técnico</th>
                                    <th class="text-center">
                                        <i class="bi bi-camera text-info" title="Registro Fotográfico"></i>
                                        <br>
                                        <small class="text-muted">REGISTRO FOTOGRÁFICO</small>
                                    </th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($retiros as $retiro): ?>
                                    <tr class="<?php
                                        if ($retiro['tipo_problema'] == 1) {
                                            echo 'table-danger'; // Imposibilidad sin foto - ROJO (crítico)
                                        } else {
                                            echo ''; // Normal
                                        }
                                        ?>">
                                        <td><?= !empty($retiro['programacion_dia_retiro']) ? date('d/m/Y', strtotime($retiro['programacion_dia_retiro'])) : 'N/A' ?></td>
                                        <td><strong><?= htmlspecialchars($retiro['orden_servicio']) ?></strong></td>
                                        <td><?= htmlspecialchars($retiro['cliente']) ?></td>
                                        <td><?= htmlspecialchars($retiro['num_serie_medidor']) ?></td>
                                        <td>
                                            <?php if ($retiro['medidor_retirado'] === 'SI'): ?>
                                                <span class="badge bg-success">SÍ</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">NO</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($retiro['tecnico_responsable']) ?></td>
                                        <td class="text-center">
                                            <?php if ($retiro['tipo_problema'] == 1): ?>
                                                <!-- Imposibilidad sin foto (PROBLEMA CRÍTICO) -->
                                                <span class="badge bg-danger" title="❌ IMPOSIBILIDAD sin foto de evidencia - REQUIERE ATENCIÓN INMEDIATA">
                                                    <i class="bi bi-exclamation-triangle-fill"></i> SIN FOTO
                                                </span>
                                            <?php elseif ($retiro['tipo_problema'] == 2): ?>
                                                <!-- Imposibilidad con foto (OK) -->
                                                <span class="badge bg-success" title="✅ Imposibilidad con foto de evidencia">
                                                    <i class="bi bi-check-circle-fill"></i> CON FOTO
                                                </span>
                                            <?php else: ?>
                                                <!-- Retirado correctamente o sin imposibilidad -->
                                                <span class="badge bg-light text-muted" title="Registro normal">
                                                    <i class="bi bi-check"></i> OK
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info"
                                                    onclick="verDetalle(<?= $retiro['id'] ?>)">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal para ver detalle -->
    <div class="modal fade" id="detalleModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-white border-bottom">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-info-circle text-primary"></i> Detalle del Retiro
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Debug para verificar que los datos se están cargando correctamente
        console.log('=== DASHBOARD DEBUG ===');
        console.log('Casos problemáticos detectados: <?= $casosProblematicos ?>');
        console.log('Total registros: <?= $stats['total'] ?>');
        console.log('Retirados: <?= $stats['retirados'] ?>');
        console.log('No retirados: <?= $stats['no_retirados'] ?>');
        console.log('======================');
        function verDetalle(id) {
            const modal = new bootstrap.Modal(document.getElementById('detalleModal'));
            modal.show();
            
            fetch('detalle_retiro.php?id=' + id)
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
            window.location.href = 'exportar_excel.php?' + params.toString();
        }
    </script>
</body>
</html>

