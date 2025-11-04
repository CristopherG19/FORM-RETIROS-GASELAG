<?php
require_once '../config/database.php';

// Verificar autenticación y rol de administrador
requireRole(['admin']);

try {
    $pdo = getConnection();

    // Obtener casos que requieren seguimiento
    $sql = "SELECT
                r.*,
                o.cliente,
                o.usuario_reclamante,
                o.num_serie_medidor,
                o.direccion,
                CASE
                    WHEN r.medidor_retirado = 'NO'
                         AND (r.foto_imposibilidad IS NULL OR r.foto_imposibilidad = '')
                    THEN 'CRÍTICO: Sin evidencia fotográfica'
                    WHEN r.medidor_retirado = 'NO'
                         AND (r.foto_imposibilidad IS NOT NULL AND r.foto_imposibilidad != '')
                    THEN 'OK: Con evidencia fotográfica'
                    ELSE 'NORMAL'
                END as tipo_caso,
                CASE
                    WHEN r.medidor_retirado = 'NO'
                         AND (r.foto_imposibilidad IS NULL OR r.foto_imposibilidad = '')
                    THEN 1
                    ELSE 0
                END as es_critico
            FROM retiros_medidores r
            INNER JOIN ordenes_servicio o ON r.orden_servicio_id = o.id
            WHERE r.medidor_retirado = 'NO'
            ORDER BY
                CASE
                    WHEN r.foto_imposibilidad IS NULL OR r.foto_imposibilidad = '' THEN 1
                    ELSE 2
                END,
                r.fecha_registro DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $casosSeguimiento = $stmt->fetchAll();

    // Estadísticas adicionales
    $totalCasos = count($casosSeguimiento);
    $casosCriticos = count(array_filter($casosSeguimiento, function($caso) {
        return $caso['es_critico'] == 1;
    }));

    // Estadísticas por técnico (solo casos no retirados)
    $sql = "SELECT
                tecnico_responsable,
                COUNT(*) as cantidad,
                COUNT(CASE WHEN r.foto_imposibilidad IS NULL OR r.foto_imposibilidad = '' THEN 1 END) as criticos,
                COUNT(CASE WHEN r.foto_imposibilidad IS NOT NULL AND r.foto_imposibilidad != '' THEN 1 END) as con_foto
            FROM retiros_medidores r
            WHERE r.medidor_retirado = 'NO'
            GROUP BY tecnico_responsable
            ORDER BY cantidad DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $statsPorTecnico = $stmt->fetchAll();

    // Estadísticas por fecha
    $sql = "SELECT
                DATE(fecha_registro) as fecha,
                COUNT(*) as cantidad
            FROM retiros_medidores r
            WHERE r.medidor_retirado = 'NO'
            AND r.visor_imposibilidad_lectura = 'SI'
            AND (r.foto_imposibilidad IS NULL OR r.foto_imposibilidad = '')
            GROUP BY DATE(fecha_registro)
            ORDER BY fecha DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $statsPorFecha = $stmt->fetchAll();

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

$pageTitle = 'Reporte de Casos Críticos - Sistema GASELAG';
require_once '../includes/header.php';
?>

<div class="container-fluid px-4 py-4">

        <!-- Encabezado -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-1">
                            <i class="bi bi-exclamation-triangle text-danger"></i>
                            Casos Críticos - Sin Evidencia
                        </h2>
                        <p class="text-muted mb-0">
                            Registros no retirados sin evidencia fotográfica (requieren seguimiento)
                        </p>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-success" onclick="exportarExcel()">
                            <i class="bi bi-file-earmark-excel"></i> Exportar Excel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size: 3rem;"></i>
                        <h3 class="mt-3 mb-1 text-danger"><?php echo $casosCriticos; ?></h3>
                        <p class="text-muted mb-0">Casos CRÍTICOS</p>
                        <small class="text-muted">Imposibilidad sin foto</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="bi bi-clipboard-check text-warning" style="font-size: 3rem;"></i>
                        <h3 class="mt-3 mb-1 text-warning"><?php echo $totalCasos; ?></h3>
                        <p class="text-muted mb-0">Total Seguimiento</p>
                        <small class="text-muted">Casos que requieren atención</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="bi bi-person text-primary" style="font-size: 3rem;"></i>
                        <h3 class="mt-3 mb-1 text-primary"><?php echo count($statsPorTecnico); ?></h3>
                        <p class="text-muted mb-0">Técnicos Involucrados</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas por Técnico -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-people text-primary"></i> Casos por Técnico
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($statsPorTecnico)): ?>
                    <p class="text-muted text-center py-3">No hay datos para mostrar</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Técnico</th>
                                    <th class="text-center">Total No Retirados</th>
                                    <th class="text-center">Críticos (Sin Foto)</th>
                                    <th class="text-center">OK (Con Foto)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($statsPorTecnico as $stat): ?>
                                    <tr class="<?php echo ($stat['criticos'] > 0) ? 'table-danger' : 'table-success'; ?>">
                                        <td>
                                            <strong><?php echo htmlspecialchars($stat['tecnico_responsable'] ?: 'Sin asignar'); ?></strong>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary"><?php echo $stat['cantidad']; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($stat['criticos'] > 0): ?>
                                                <span class="badge bg-danger"><?php echo $stat['criticos']; ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success"><?php echo $stat['con_foto']; ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Estadísticas por Fecha -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-calendar-event text-primary"></i> Casos por Fecha
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($statsPorFecha)): ?>
                    <p class="text-muted text-center py-3">No hay datos para mostrar</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha</th>
                                    <th class="text-center">Cantidad</th>
                                    <th class="text-center">Porcentaje</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($statsPorFecha as $stat): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo date('d/m/Y', strtotime($stat['fecha'])); ?></strong>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-warning text-dark"><?php echo $stat['cantidad']; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <?php
                                            $porcentaje = ($stat['cantidad'] / $totalCasos) * 100;
                                            echo round($porcentaje, 1) . '%';
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Lista Detallada de Casos -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-list-ul text-primary"></i> Lista de Registros No Retirados
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($casosSeguimiento)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
                        <p class="mt-3 text-muted">¡Excelente! Todos los registros no retirados tienen evidencia fotográfica</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-dark sticky-top">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Orden Servicio</th>
                                    <th>Cliente</th>
                                    <th>N° Serie</th>
                                    <th>Técnico</th>
                                    <th class="text-center">Tipo de Caso</th>
                                    <th>Dirección</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($casosSeguimiento as $caso): ?>
                                    <tr class="<?php echo ($caso['es_critico'] == 1) ? 'table-danger' : 'table-warning'; ?>">
                                        <td>
                                            <strong><?php echo date('d/m/Y', strtotime($caso['fecha_registro'])); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo date('H:i', strtotime($caso['fecha_registro'])); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($caso['orden_servicio']); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($caso['cliente']); ?></td>
                                        <td>
                                            <code><?php echo htmlspecialchars($caso['num_serie_medidor']); ?></code>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($caso['tecnico_responsable'] ?: 'Sin asignar'); ?></strong>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($caso['es_critico'] == 1): ?>
                                                <span class="badge bg-danger">
                                                    <i class="bi bi-exclamation-triangle-fill"></i> CRÍTICO
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-success">
                                                    <i class="bi bi-check-circle-fill"></i> OK
                                                </span>
                                            <?php endif; ?>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($caso['tipo_caso']); ?></small>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?php echo htmlspecialchars(substr($caso['direccion'], 0, 40)); ?>...</small>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info"
                                                    onclick="verDetalle(<?php echo $caso['id']; ?>)">
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
                        <i class="bi bi-info-circle text-primary"></i> Detalle del Caso Problemático
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
            window.location.href = 'exportar_excel.php?filtro_problematicos=SI';
        }
    </script>

<?php require_once '../includes/footer.php'; ?>
