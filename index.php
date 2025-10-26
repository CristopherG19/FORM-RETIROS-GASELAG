<?php
require_once 'config/database.php';

// Verificar autenticación
requireRole(['admin', 'user']);

// Verificar si hay OCs en proceso
$hasOCs = isset($_SESSION['selected_ocs']) && count($_SESSION['selected_ocs']) > 0;

// Obtener información del usuario actual
$currentUser = getCurrentUser();
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
    <!-- Barra de navegación -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="index.php">
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
                    <?php if (isAdmin()): ?>
                        <li class="nav-item">
                            <span class="navbar-text">
                                <i class="bi bi-shield-check ms-3 me-1"></i>
                                <span class="badge bg-warning text-dark">Administrador</span>
                            </span>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <span class="navbar-text">
                                <i class="bi bi-wrench-adjustable ms-3 me-1"></i>
                                <span class="badge bg-info">Técnico</span>
                            </span>
                        </li>
                    <?php endif; ?>
                </ul>

                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-gear me-1"></i>Opciones
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="text-center mb-5">
            <h1 class="fw-bold mb-2">
                <i class="bi bi-speedometer2 text-primary"></i>
                Panel Principal
            </h1>
            <p class="text-muted">Sistema de Retiro de Medidores de Agua</p>
            <p class="text-muted">
                Bienvenido, <strong><?php echo htmlspecialchars($currentUser['nombre_completo']); ?></strong>
                <?php if (isAdmin()): ?>
                    <span class="badge bg-warning text-dark ms-2">Modo Administrador</span>
                <?php else: ?>
                    <span class="badge bg-info ms-2">Modo Técnico</span>
                <?php endif; ?>
            </p>
        </div>

        <div class="row g-4 mb-5">
            <?php if (isAdmin()): ?>
                <!-- Opción 1: Importar Datos (Solo Admin) -->
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm" style="cursor: pointer;" onclick="location.href='pages/importar_datos.php'">
                        <div class="card-body text-center p-4">
                            <i class="bi bi-file-earmark-excel text-success" style="font-size: 3rem;"></i>
                            <h5 class="card-title mt-3 mb-2">Importar Datos</h5>
                            <p class="card-text text-muted small">Cargar información de órdenes de servicio desde Excel</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Opción 2: Registrar Retiro (Todos los usuarios) -->
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

            <!-- Opción 3: Consultar Registros (Todos los usuarios) -->
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

        <?php if (isAdmin()): ?>
        <!-- Segunda fila de opciones (Solo Admin) -->
        <div class="row g-4 mb-5">
            <!-- Opción 4: Reporte de Casos Críticos (Solo Admin) -->
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm" style="cursor: pointer;" onclick="location.href='pages/reporte_imposibilidad.php'">
                    <div class="card-body text-center p-4">
                        <i class="bi bi-exclamation-triangle text-danger" style="font-size: 3rem;"></i>
                        <h5 class="card-title mt-3 mb-2">Casos Críticos</h5>
                        <p class="card-text text-muted small">Registros sin evidencia fotográfica</p>
                    </div>
                </div>
            </div>

            <!-- Opción 5: Gestión de Usuarios (Solo Admin) -->
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm" style="cursor: pointer;" onclick="location.href='pages/gestion_usuarios.php'">
                    <div class="card-body text-center p-4">
                        <i class="bi bi-people text-warning" style="font-size: 3rem;"></i>
                        <h5 class="card-title mt-3 mb-2">Gestión de Usuarios</h5>
                        <p class="card-text text-muted small">Administrar usuarios y permisos del sistema</p>
                    </div>
                </div>
            </div>

            <!-- Opción 6: Exportar Excel (Solo Admin) -->
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm" style="cursor: pointer;" onclick="location.href='pages/exportar_excel.php'">
                    <div class="card-body text-center p-4">
                        <i class="bi bi-file-earmark-spreadsheet text-success" style="font-size: 3rem;"></i>
                        <h5 class="card-title mt-3 mb-2">Exportar Datos</h5>
                        <p class="card-text text-muted small">Descargar reportes en formato Excel</p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Información adicional -->
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="card-title mb-3">
                    <i class="bi bi-info-circle text-primary"></i>
                    <?php if (isAdmin()): ?>
                        Instrucciones para Administrador
                    <?php else: ?>
                        Instrucciones para Técnico
                    <?php endif; ?>
                </h6>
                <ol class="mb-0 small">
                    <?php if (isAdmin()): ?>
                        <li class="mb-2"><strong>Importar Datos:</strong> Primero debe cargar la información de las órdenes de servicio desde el archivo Excel.</li>
                        <li class="mb-2"><strong>Gestión de Usuarios:</strong> Administre usuarios y permisos del sistema.</li>
                        <li class="mb-2"><strong>Exportar Datos:</strong> Descargue reportes en formato Excel.</li>
                        <li class="mb-2"><strong>Casos Críticos:</strong> Identifique registros no retirados sin evidencia fotográfica.</li>
                    <?php endif; ?>
                    <li class="mb-2"><strong>Registrar Retiro:</strong> Busque las OCs por código y complete el formulario de retiro para cada medidor.</li>
                    <li><strong>Consultar Registros:</strong> Revise el historial de todos los retiros realizados.</li>
                </ol>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

