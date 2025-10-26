<?php
require_once '../config/database.php';

// Verificar autenticación (todos los usuarios)
requireRole(['admin', 'user']);

// Verificar que haya OCs seleccionadas
if (!isset($_SESSION['selected_ocs']) || empty($_SESSION['selected_ocs'])) {
    header('Location: buscar_oc.php');
    exit;
}

// Obtener datos de las OCs seleccionadas
try {
    $pdo = getConnection();
    $placeholders = str_repeat('?,', count($_SESSION['selected_ocs']) - 1) . '?';
    $sql = "SELECT * FROM ordenes_servicio WHERE orden_servicio IN ($placeholders) ORDER BY orden_servicio";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($_SESSION['selected_ocs']);
    $ordenes = $stmt->fetchAll();
} catch (Exception $e) {
    die("Error al cargar datos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vista Previa - GASELAG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="mb-3">
            <a href="buscar_oc.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-eye text-primary"></i>
                    Vista Previa de Órdenes Seleccionadas
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    <strong>Verifique los datos antes de continuar.</strong> Se mostrarán <?= count($ordenes) ?> formularios para completar.
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark sticky-top">
                            <tr>
                                <th>#</th>
                                <th>OC</th>
                                <th>N° Serie Medidor</th>
                                <th>N° Suministro</th>
                                <th>Dirección</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ordenes as $index => $orden): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><strong><?= htmlspecialchars($orden['orden_servicio']) ?></strong></td>
                                    <td><?= htmlspecialchars($orden['num_serie_medidor']) ?></td>
                                    <td><?= htmlspecialchars($orden['num_suministro']) ?></td>
                                    <td><?= htmlspecialchars($orden['direccion']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <hr class="my-4">

                <div class="row">
                    <div class="col-md-6">
                        <a href="buscar_oc.php" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-arrow-left"></i>
                            Modificar Selección
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="formulario_retiro.php?index=0" class="btn btn-success w-100 btn-lg">
                            <i class="bi bi-arrow-right-circle"></i>
                            Comenzar Registro de Retiros
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

