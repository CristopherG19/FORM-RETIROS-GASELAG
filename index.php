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
    <style>
        /* Hero gradient header */
        .hero-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        /* Card hover effects - SIN MOVIMIENTO */
        .module-card {
            transition: box-shadow 0.3s ease;
            cursor: pointer;
            border: none;
        }
        .module-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1) !important;
        }
        .module-card .card-icon {
            transition: opacity 0.3s ease;
        }
        .module-card:hover .card-icon {
            opacity: 0.85;
        }
        
        /* Responsive adjustments */
        @media (max-width: 767.98px) {
            .hero-header {
                padding: 2rem 0;
            }
            .hero-header h1 {
                font-size: 1.75rem;
            }
        }
        
        /* Badge pulse animation */
        .badge-pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'includes/session_middleware.php'; ?>
    
    <!-- Barra de navegación simplificada -->
    <nav class="navbar navbar-dark bg-dark shadow-sm">
        <div class="container-fluid px-3 px-md-4">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="index.php">
                <i class="bi bi-speedometer2 me-2 fs-4"></i>
                <span class="d-none d-sm-inline">GASELAG</span>
            </a>

            <div class="d-flex align-items-center gap-2 gap-md-3">
                <!-- Info usuario -->
                <div class="dropdown">
                    <button class="btn btn-outline-light btn-sm dropdown-toggle d-flex align-items-center" 
                            type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle me-1 me-md-2"></i>
                        <span class="d-none d-md-inline"><?php echo htmlspecialchars($currentUser['nombre_completo']); ?></span>
                        <span class="d-md-none">Perfil</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li class="px-3 py-2 border-bottom">
                            <div class="fw-bold"><?php echo htmlspecialchars($currentUser['nombre_completo']); ?></div>
                            <small class="text-muted"><?php echo htmlspecialchars($currentUser['email']); ?></small>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Header -->
    <div class="hero-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="fw-bold mb-3">
                        <i class="bi bi-speedometer2 me-2"></i>
                        Panel Principal
                    </h1>
                    <p class="lead mb-3 opacity-90">Sistema de Retiro de Medidores de Agua</p>
                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        <span class="badge bg-light text-dark px-3 py-2">
                            <i class="bi bi-person-circle me-1"></i>
                            <?php echo htmlspecialchars($currentUser['nombre_completo']); ?>
                        </span>
                        <?php if (isAdmin()): ?>
                            <span class="badge bg-warning text-dark px-3 py-2 badge-pulse">
                                <i class="bi bi-shield-check me-1"></i>
                                Administrador
                            </span>
                        <?php else: ?>
                            <span class="badge bg-info px-3 py-2">
                                <i class="bi bi-wrench-adjustable me-1"></i>
                                Técnico
                            </span>
                        <?php endif; ?>
                        <?php if ($hasOCs): ?>
                            <span class="badge bg-success px-3 py-2 badge-pulse">
                                <i class="bi bi-check-circle me-1"></i>
                                OCs en Proceso
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container pb-5">

        <!-- Módulos Principales -->
        <div class="mb-4">
            <h5 class="text-muted mb-3">
                <i class="bi bi-grid-3x3-gap me-2"></i>
                Módulos Principales
            </h5>
        </div>
        
        <div class="row g-3 g-md-4 mb-4 mb-md-5">
            <?php if (isAdmin()): ?>
                <!-- Importar Datos (Solo Admin) -->
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="card module-card h-100 shadow-sm" onclick="location.href='pages/importar_datos.php'">
                        <div class="card-body text-center p-3 p-md-4">
                            <i class="bi bi-file-earmark-excel text-success card-icon" style="font-size: 2.5rem;"></i>
                            <h6 class="card-title mt-3 mb-2">Importar Datos</h6>
                            <p class="card-text text-muted small d-none d-lg-block">Cargar OCs desde Excel</p>
                        </div>
                    </div>
                </div>

                <!-- Registrar Retiro (Admin) -->
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="card module-card h-100 shadow-sm" onclick="location.href='pages/buscar_oc.php'">
                        <div class="card-body text-center p-3 p-md-4">
                            <i class="bi bi-clipboard-check text-primary card-icon" style="font-size: 2.5rem;"></i>
                            <h6 class="card-title mt-3 mb-2">Registrar Retiro</h6>
                            <p class="card-text text-muted small d-none d-lg-block">Buscar y registrar OCs</p>
                        </div>
                    </div>
                </div>

                <!-- Consultar Registros -->
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="card module-card h-100 shadow-sm" onclick="location.href='pages/consultar_retiros.php'">
                        <div class="card-body text-center p-3 p-md-4">
                            <i class="bi bi-search text-info card-icon" style="font-size: 2.5rem;"></i>
                            <h6 class="card-title mt-3 mb-2">Consultar Registros</h6>
                            <p class="card-text text-muted small d-none d-lg-block">Historial de retiros</p>
                        </div>
                    </div>
                </div>

                <!-- Exportar Excel -->
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="card module-card h-100 shadow-sm" onclick="location.href='pages/exportar_excel.php'">
                        <div class="card-body text-center p-3 p-md-4">
                            <i class="bi bi-download text-success card-icon" style="font-size: 2.5rem;"></i>
                            <h6 class="card-title mt-3 mb-2">Exportar Excel</h6>
                            <p class="card-text text-muted small d-none d-lg-block">Descargar reportes</p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Registrar Retiro (Técnico) -->
                <div class="col-6 col-md-6">
                    <div class="card module-card h-100 shadow-sm" onclick="location.href='pages/buscar_oc.php'">
                        <div class="card-body text-center p-4">
                            <i class="bi bi-clipboard-check text-primary card-icon" style="font-size: 3rem;"></i>
                            <h5 class="card-title mt-3 mb-2">Registrar Retiro</h5>
                            <p class="card-text text-muted">Buscar y registrar retiro de medidores</p>
                            <?php if ($hasOCs): ?>
                                <span class="badge bg-warning text-dark mt-2 badge-pulse">
                                    <i class="bi bi-hourglass-split"></i> En proceso
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Consultar Registros (Técnico) -->
                <div class="col-6 col-md-6">
                    <div class="card module-card h-100 shadow-sm" onclick="location.href='pages/consultar_retiros.php'">
                        <div class="card-body text-center p-4">
                            <i class="bi bi-search text-info card-icon" style="font-size: 3rem;"></i>
                            <h5 class="card-title mt-3 mb-2">Consultar Registros</h5>
                            <p class="card-text text-muted">Ver historial de retiros realizados</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php if (isAdmin()): ?>
        <!-- Gestión y Administración (Solo Admin) -->
        <div class="mb-4 mt-5">
            <h5 class="text-muted mb-3">
                <i class="bi bi-gear-fill me-2"></i>
                Gestión y Administración
            </h5>
        </div>
        
        <div class="row g-3 g-md-4 mb-4 mb-md-5">
            <!-- Ver Todas las OC -->
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card module-card h-100 shadow-sm" onclick="location.href='pages/listar_oc.php'">
                    <div class="card-body text-center p-3 p-md-4">
                        <i class="bi bi-list-ul text-info card-icon" style="font-size: 2.5rem;"></i>
                        <h6 class="card-title mt-3 mb-2">Ver Todas las OC</h6>
                        <p class="card-text text-muted small d-none d-lg-block">Listado completo</p>
                    </div>
                </div>
            </div>

            <!-- Casos Críticos -->
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card module-card h-100 shadow-sm" onclick="location.href='pages/reporte_imposibilidad.php'">
                    <div class="card-body text-center p-3 p-md-4">
                        <i class="bi bi-exclamation-triangle text-danger card-icon" style="font-size: 2.5rem;"></i>
                        <h6 class="card-title mt-3 mb-2">Casos Críticos</h6>
                        <p class="card-text text-muted small d-none d-lg-block">Sin evidencia</p>
                    </div>
                </div>
            </div>

            <!-- Gestión de Retiros -->
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card module-card h-100 shadow-sm" onclick="location.href='pages/gestion_retiros.php'">
                    <div class="card-body text-center p-3 p-md-4">
                        <i class="bi bi-gear text-primary card-icon" style="font-size: 2.5rem;"></i>
                        <h6 class="card-title mt-3 mb-2">Gestión de Retiros</h6>
                        <p class="card-text text-muted small d-none d-lg-block">Control y reasignación</p>
                    </div>
                </div>
            </div>

            <!-- Tipos de Imposibilidad -->
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card module-card h-100 shadow-sm" onclick="location.href='pages/gestion_imposibilidad.php'">
                    <div class="card-body text-center p-3 p-md-4">
                        <i class="bi bi-exclamation-triangle text-warning card-icon" style="font-size: 2.5rem;"></i>
                        <h6 class="card-title mt-3 mb-2">Tipos de Imposibilidad</h6>
                        <p class="card-text text-muted small d-none d-lg-block">Motivos de no retiro</p>
                    </div>
                </div>
            </div>

            <!-- Gestión de Evidencias -->
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card module-card h-100 shadow-sm" onclick="location.href='pages/gestion_evidencias.php'">
                    <div class="card-body text-center p-3 p-md-4">
                        <i class="bi bi-camera text-info card-icon" style="font-size: 2.5rem;"></i>
                        <h6 class="card-title mt-3 mb-2">Gestión de Evidencias</h6>
                        <p class="card-text text-muted small d-none d-lg-block">Fotos y sanciones</p>
                    </div>
                </div>
            </div>

            <!-- Gestión de Usuarios -->
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card module-card h-100 shadow-sm" onclick="location.href='pages/gestion_usuarios.php'">
                    <div class="card-body text-center p-3 p-md-4">
                        <i class="bi bi-people text-warning card-icon" style="font-size: 2.5rem;"></i>
                        <h6 class="card-title mt-3 mb-2">Gestión de Usuarios</h6>
                        <p class="card-text text-muted small d-none d-lg-block">Usuarios y permisos</p>
                    </div>
                </div>
            </div>

            <!-- Seguridad del Sistema -->
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card module-card h-100 shadow-sm" onclick="location.href='pages/admin_desbloquear_cuentas.php'">
                    <div class="card-body text-center p-3 p-md-4">
                        <i class="bi bi-shield-lock text-danger card-icon" style="font-size: 2.5rem;"></i>
                        <h6 class="card-title mt-3 mb-2">Seguridad del Sistema</h6>
                        <p class="card-text text-muted small d-none d-lg-block">Cuentas y auditoría</p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Guía Rápida (Colapsable) -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0">
                <button class="btn btn-link text-decoration-none text-dark w-100 text-start p-0 d-flex justify-content-between align-items-center" 
                        type="button" data-bs-toggle="collapse" data-bs-target="#guiaRapida">
                    <span>
                        <i class="bi bi-info-circle text-primary me-2"></i>
                        <?php if (isAdmin()): ?>
                            <strong>Guía Rápida - Administrador</strong>
                        <?php else: ?>
                            <strong>Guía Rápida - Técnico</strong>
                        <?php endif; ?>
                    </span>
                    <i class="bi bi-chevron-down"></i>
                </button>
            </div>
            <div class="collapse" id="guiaRapida">
                <div class="card-body pt-0">
                    <div class="row g-3">
                        <?php if (isAdmin()): ?>
                            <!-- Columna 1: Gestión de Datos -->
                            <div class="col-md-6">
                                <div class="bg-light rounded p-3">
                                    <h6 class="text-primary mb-3">
                                        <i class="bi bi-database-fill-gear me-2"></i>
                                        Gestión de Datos
                                    </h6>
                                    <ul class="list-unstyled small mb-0">
                                        <li class="mb-2">
                                            <i class="bi bi-arrow-right-circle text-success me-2"></i>
                                            <strong>Importar:</strong> Carga masiva de OCs desde Excel
                                        </li>
                                        <li class="mb-2">
                                            <i class="bi bi-arrow-right-circle text-success me-2"></i>
                                            <strong>Exportar:</strong> Descarga reportes y análisis
                                        </li>
                                        <li class="mb-2">
                                            <i class="bi bi-arrow-right-circle text-success me-2"></i>
                                            <strong>Ver OCs:</strong> Listado completo de órdenes
                                        </li>
                                        <li>
                                            <i class="bi bi-arrow-right-circle text-success me-2"></i>
                                            <strong>Consultar:</strong> Historial de retiros
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            
                            <!-- Columna 2: Administración -->
                            <div class="col-md-6">
                                <div class="bg-light rounded p-3">
                                    <h6 class="text-warning mb-3">
                                        <i class="bi bi-shield-lock-fill me-2"></i>
                                        Administración
                                    </h6>
                                    <ul class="list-unstyled small mb-0">
                                        <li class="mb-2">
                                            <i class="bi bi-arrow-right-circle text-warning me-2"></i>
                                            <strong>Usuarios:</strong> Gestión de permisos
                                        </li>
                                        <li class="mb-2">
                                            <i class="bi bi-arrow-right-circle text-warning me-2"></i>
                                            <strong>Retiros:</strong> Control y reasignación
                                        </li>
                                        <li class="mb-2">
                                            <i class="bi bi-arrow-right-circle text-warning me-2"></i>
                                            <strong>Evidencias:</strong> Revisión y sanciones
                                        </li>
                                        <li>
                                            <i class="bi bi-arrow-right-circle text-warning me-2"></i>
                                            <strong>Críticos:</strong> Seguimiento sin foto
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Vista Técnico -->
                            <div class="col-12">
                                <div class="bg-light rounded p-3">
                                    <h6 class="text-info mb-3">
                                        <i class="bi bi-clipboard-check-fill me-2"></i>
                                        Operaciones Principales
                                    </h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-start">
                                                <i class="bi bi-1-circle-fill text-primary me-3 fs-4"></i>
                                                <div>
                                                    <strong class="d-block mb-1">Registrar Retiro</strong>
                                                    <small class="text-muted">
                                                        Busca la OC por código, completa los datos del medidor 
                                                        y toma la foto de evidencia
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-start">
                                                <i class="bi bi-2-circle-fill text-info me-3 fs-4"></i>
                                                <div>
                                                    <strong class="d-block mb-1">Consultar Registros</strong>
                                                    <small class="text-muted">
                                                        Revisa el historial de tus retiros realizados 
                                                        y verifica el estado de cada uno
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Nota importante -->
                    <div class="alert alert-info mb-0 mt-3 d-flex align-items-center">
                        <i class="bi bi-lightbulb-fill me-2 fs-5"></i>
                        <small>
                            <strong>Recuerda:</strong> Todas las operaciones quedan registradas 
                            con fecha, hora y usuario para trazabilidad completa.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

