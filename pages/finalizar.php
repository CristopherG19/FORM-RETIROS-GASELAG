<?php
require_once '../config/database.php';

// Limpiar las OCs de la sesión
if (isset($_SESSION['selected_ocs'])) {
    $totalProcesadas = count($_SESSION['selected_ocs']);
    unset($_SESSION['selected_ocs']);
} else {
    $totalProcesadas = 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proceso Completado - GASELAG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light d-flex align-items-center" style="min-height: 100vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
                        <h2 class="mt-4 mb-3">¡Proceso Completado!</h2>
                        <p class="lead mb-4 text-muted">
                            Se han registrado exitosamente <strong class="text-success"><?= $totalProcesadas ?></strong> retiros de medidores.
                        </p>

                        <div class="alert alert-success border-0">
                            <i class="bi bi-info-circle"></i>
                            Los datos han sido guardados correctamente en la base de datos.
                        </div>

                        <hr class="my-4">

                        <div class="d-grid gap-2">
                            <a href="buscar_oc.php" class="btn btn-primary btn-lg">
                                <i class="bi bi-plus-circle"></i>
                                Registrar Nuevos Retiros
                            </a>
                            <a href="consultar_retiros.php" class="btn btn-outline-secondary btn-lg">
                                <i class="bi bi-search"></i>
                                Ver Registros
                            </a>
                            <a href="../index.php" class="btn btn-outline-secondary">
                                <i class="bi bi-house"></i>
                                Volver al Inicio
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

