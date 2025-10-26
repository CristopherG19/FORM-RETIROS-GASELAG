<?php
require_once 'config/database.php';

// Verificar si hay OCs en proceso
$hasOCs = isset($_SESSION['selected_ocs']) && count($_SESSION['selected_ocs']) > 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GASELAG - Sistema de Retiro de Medidores</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="text-center mb-5">
            <h1 class="fw-bold mb-2">
                <i class="bi bi-speedometer2 text-primary"></i>
                GASELAG
            </h1>
            <p class="text-muted">Sistema de Retiro de Medidores de Agua</p>
        </div>

        <div class="row g-4 mb-5">
            <!-- Opción 1: Importar Datos -->
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm" style="cursor: pointer;" onclick="location.href='pages/importar_datos.php'">
                    <div class="card-body text-center p-4">
                        <i class="bi bi-file-earmark-excel text-success" style="font-size: 3rem;"></i>
                        <h5 class="card-title mt-3 mb-2">Importar Datos</h5>
                        <p class="card-text text-muted small">Cargar información de órdenes de servicio desde Excel</p>
                    </div>
                </div>
            </div>

            <!-- Opción 2: Registrar Retiro -->
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm" style="cursor: pointer;" onclick="location.href='pages/buscar_oc.php'">
                    <div class="card-body text-center p-4">
                        <i class="bi bi-clipboard-check text-primary" style="font-size: 3rem;"></i>
                        <h5 class="card-title mt-3 mb-2">Registrar Retiro</h5>
                        <p class="card-text text-muted small">Buscar y registrar retiro de medidores</p>
                        <?php if ($hasOCs): ?>
                            <span class="badge bg-warning text-dark mt-2">En proceso</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Opción 3: Consultar Registros -->
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm" style="cursor: pointer;" onclick="location.href='pages/consultar_retiros.php'">
                    <div class="card-body text-center p-4">
                        <i class="bi bi-search text-info" style="font-size: 3rem;"></i>
                        <h5 class="card-title mt-3 mb-2">Consultar Registros</h5>
                        <p class="card-text text-muted small">Ver historial de retiros realizados</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Segunda fila de opciones -->
        <div class="row g-4">
            <!-- Opción 4: Reporte de Casos Críticos -->
            <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm" style="cursor: pointer;" onclick="location.href='pages/reporte_imposibilidad.php'">
                    <div class="card-body text-center p-4">
                        <i class="bi bi-exclamation-triangle text-danger" style="font-size: 3rem;"></i>
                        <h5 class="card-title mt-3 mb-2">Casos Críticos</h5>
                        <p class="card-text text-muted small">Registros sin evidencia fotográfica</p>
                    </div>
                </div>
            </div>

            <!-- Opción 5: Espacio vacío para mantener el diseño -->
            <div class="col-md-6">
                <!-- Espacio reservado para futuras opciones -->
            </div>
        </div>

        <!-- Información adicional -->
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="card-title mb-3"><i class="bi bi-info-circle text-primary"></i> Instrucciones de Uso</h6>
                <ol class="mb-0 small">
                    <li class="mb-2"><strong>Importar Datos:</strong> Primero debe cargar la información de las órdenes de servicio desde el archivo Excel.</li>
                    <li class="mb-2"><strong>Registrar Retiro:</strong> Busque las OCs por código y complete el formulario de retiro para cada medidor.</li>
                    <li class="mb-2"><strong>Consultar Registros:</strong> Revise el historial de todos los retiros realizados.</li>
                    <li><strong>Casos Críticos:</strong> Identifique registros no retirados sin evidencia fotográfica.</li>
                </ol>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

