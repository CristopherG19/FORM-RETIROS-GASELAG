<?php
require_once 'config/database.php';

// Verificar autenticación
requireRole(['admin', 'user']);

// Verificar si hay OCs en proceso
$hasOCs = isset($_SESSION['selected_ocs']) && count($_SESSION['selected_ocs']) > 0;

// Para técnicos: obtener cantidad de OCs asignadas pendientes
$ocsAsignadasPendientes = 0;
if (!isAdmin()) {
    $ocsAsignadasPendientes = countOCsPendientesTecnico($_SESSION['user_id']);
}

$pageTitle = 'Panel Principal - Sistema GASELAG';
require_once 'includes/header.php';
?>

<style>
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
</style>

<!-- Hero Section -->
<div class="bg-gradient py-5" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8 mx-auto text-center">
                <h1 class="fw-bold mb-3" style="color: rgba(33, 37, 41, 0.75);">
                    <i class="bi bi-speedometer2 me-2"></i>
                    Panel Principal
                </h1>
                <p class="lead mb-3" style="color: rgba(33, 37, 41, 0.75); font-weight: 700; font-size: 1.4rem;">
                    Sistema de Retiro de Medidores de Agua
                </p>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <?php if (isAdmin()): ?>
                        <span class="badge bg-warning text-dark px-3 py-2">
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
                        <span class="badge bg-success px-3 py-2">
                            <i class="bi bi-check-circle me-1"></i>
                            OCs en Proceso
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container py-4">

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'password_changed'): ?>
        <!-- Mensaje de éxito al cambiar contraseña -->
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <strong>¡Contraseña actualizada!</strong> Tu contraseña ha sido cambiada exitosamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['login']) && $_GET['login'] === 'success'): ?>
        <!-- Mensaje de bienvenida al iniciar sesión -->
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-person-check-fill me-2"></i>
            <strong>¡Bienvenido, <?php echo htmlspecialchars($currentUser['nombre_completo']); ?>!</strong> Has iniciado sesión exitosamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

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
                <!-- Mis OCs Asignadas (Técnico) -->
                <div class="col-6 col-md-4">
                    <div class="card module-card h-100 shadow-sm" onclick="location.href='pages/mis_ocs_asignadas.php'">
                        <div class="card-body text-center p-4">
                            <i class="bi bi-person-check text-success card-icon" style="font-size: 3rem;"></i>
                            <h5 class="card-title mt-3 mb-2">Mis OCs Asignadas</h5>
                            <p class="card-text text-muted">OCs asignadas por el administrador</p>
                            <?php if ($ocsAsignadasPendientes > 0): ?>
                                <span class="badge bg-warning text-dark mt-2" style="animation: pulse 2s infinite;">
                                    <i class="bi bi-exclamation-circle"></i> <?= $ocsAsignadasPendientes ?> pendiente<?= $ocsAsignadasPendientes > 1 ? 's' : '' ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary mt-2">
                                    <i class="bi bi-check-circle"></i> Al día
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            
                <!-- Registrar Retiro (Técnico) -->
                <div class="col-6 col-md-4">
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
                <div class="col-6 col-md-4">
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
                <div class="card module-card h-100 shadow-sm" onclick="location.href='pages/gestion_usuarios_mejorado.php'">
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
</div>

<?php require_once 'includes/footer.php'; ?>

