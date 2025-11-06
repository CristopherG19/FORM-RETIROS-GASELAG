<?php
require_once '../config/database.php';

// Solo técnicos pueden acceder
requireRole(['user']);

$pageTitle = 'Mis OCs Asignadas - Sistema GASELAG';
require_once '../includes/header.php';

// Obtener OCs asignadas al técnico actual
$tecnicoId = $_SESSION['user_id'];
$ocsAsignadas = getOCsAsignadasTecnico($tecnicoId, 'todas');

// Separar por estado
$pendientes = array_filter($ocsAsignadas, fn($oc) => $oc['estado'] === 'pendiente');
$enProceso = array_filter($ocsAsignadas, fn($oc) => $oc['estado'] === 'en_proceso');
$completadas = array_filter($ocsAsignadas, fn($oc) => $oc['estado'] === 'completada');

// Procesar inicio de trabajo
if (isset($_GET['iniciar']) && isset($_GET['id'])) {
    $asignacionId = intval($_GET['id']);
    if (iniciarTrabajoAsignacion($asignacionId, $tecnicoId)) {
        header('Location: mis_ocs_asignadas.php?success=iniciado');
        exit;
    }
}

$message = '';
$messageType = '';

if (isset($_GET['success'])) {
    if ($_GET['success'] === 'iniciado') {
        $message = 'OC marcada como "En Proceso". ¡Puedes comenzar a trabajar!';
        $messageType = 'success';
    }
}
?>

<style>
.oc-card {
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
}
.oc-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transform: translateY(-2px);
}
.oc-card.pendiente {
    border-left-color: #ffc107;
}
.oc-card.en-proceso {
    border-left-color: #0d6efd;
}
.oc-card.completada {
    border-left-color: #198754;
}
.estado-badge {
    font-size: 0.85rem;
    padding: 0.4rem 0.8rem;
}

/* Estilos para vista de tabla */
.vista-tabla {
    display: none;
}
.vista-grid {
    display: block;
}

/* Responsive para tabla en móviles */
@media (max-width: 768px) {
    .vista-tabla .table-responsive {
        font-size: 0.85rem;
    }
    .vista-tabla .btn-sm {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
}

/* Animación de transición */
.vista-grid, .vista-tabla {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Tabla responsive con scroll horizontal */
.table-oc-responsive {
    min-width: 800px;
}

/* Mejora visual de la tabla */
.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
    cursor: pointer;
}

/* Estilos del cuadro de búsqueda */
#busqueda-ocs {
    font-size: 1rem;
    transition: all 0.3s ease;
}

#busqueda-ocs:focus {
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
    border-color: #86b7fe;
}

.input-group:focus-within .input-group-text {
    border-color: #86b7fe;
}

/* Animación para el botón de limpiar */
#btn-limpiar-busqueda {
    transition: all 0.2s ease;
}

#btn-limpiar-busqueda:hover {
    background-color: #dc3545;
    color: white;
    border-color: #dc3545;
}

/* Destacar resultados de búsqueda */
.oc-item {
    transition: opacity 0.3s ease, transform 0.3s ease;
}
</style>

<div class="container-fluid px-4 py-4">
    
    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
            <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : 'info-circle' ?>"></i>
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Título -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="card-title">
                <i class="bi bi-clipboard-check text-primary"></i>
                Mis Órdenes de Servicio Asignadas
            </h2>
            <p class="text-muted mb-0">
                OCs que te fueron asignadas por el administrador para su registro
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
                            <h6 class="text-muted mb-1">Total Asignadas</h6>
                            <h2 class="mb-0"><?= count($ocsAsignadas) ?></h2>
                        </div>
                        <i class="bi bi-clipboard2-data text-primary" style="font-size: 2.5rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Pendientes</h6>
                            <h2 class="mb-0 text-warning"><?= count($pendientes) ?></h2>
                        </div>
                        <i class="bi bi-hourglass-split text-warning" style="font-size: 2.5rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Completadas</h6>
                            <h2 class="mb-0 text-success"><?= count($completadas) ?></h2>
                        </div>
                        <i class="bi bi-check-circle text-success" style="font-size: 2.5rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cuadro de búsqueda -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8 mb-3 mb-md-0">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" 
                               class="form-control border-start-0 ps-0" 
                               id="busqueda-ocs" 
                               placeholder="Buscar por OC, Cliente, Dirección o N° Suministro..."
                               autocomplete="off">
                        <button class="btn btn-outline-secondary" type="button" id="btn-limpiar-busqueda" style="display: none;">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <small class="text-muted ms-2">
                        <i class="bi bi-info-circle"></i> Escribe para filtrar en tiempo real
                    </small>
                </div>
                <div class="col-md-4">
                    <div class="text-md-end">
                        <span class="badge bg-light text-dark border px-3 py-2">
                            <i class="bi bi-funnel"></i> 
                            <span id="resultados-busqueda">Mostrando todas las OCs</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs y controles de vista -->
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center mb-4 gap-3">
        <!-- Tabs para diferentes estados -->
        <ul class="nav nav-pills flex-nowrap overflow-auto w-100 w-lg-auto" id="estadoTabs" role="tablist" style="white-space: nowrap;">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pendientes-tab" data-bs-toggle="pill" data-bs-target="#pendientes" type="button">
                    <i class="bi bi-clock-history"></i> <span class="d-none d-sm-inline">Pendientes</span> <span class="badge bg-warning text-dark ms-2" id="count-pendientes"><?= count($pendientes) ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="proceso-tab" data-bs-toggle="pill" data-bs-target="#proceso" type="button">
                    <i class="bi bi-gear-fill"></i> <span class="d-none d-sm-inline">En Proceso</span> <span class="badge bg-primary ms-2" id="count-proceso"><?= count($enProceso) ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="completadas-tab" data-bs-toggle="pill" data-bs-target="#completadas" type="button">
                    <i class="bi bi-check-circle-fill"></i> <span class="d-none d-sm-inline">Completadas</span> <span class="badge bg-success ms-2" id="count-completadas"><?= count($completadas) ?></span>
                </button>
            </li>
        </ul>

        <!-- Controles de vista -->
        <div class="btn-group shadow-sm" role="group" aria-label="Tipo de vista">
            <button type="button" class="btn btn-outline-secondary active" id="btn-vista-grid" onclick="cambiarVista('grid')">
                <i class="bi bi-grid-3x3-gap-fill"></i> <span class="d-none d-sm-inline">Cuadrícula</span>
            </button>
            <button type="button" class="btn btn-outline-secondary" id="btn-vista-tabla" onclick="cambiarVista('tabla')">
                <i class="bi bi-list-ul"></i> <span class="d-none d-sm-inline">Tabla</span>
            </button>
        </div>
    </div>

    <!-- Contenido de tabs -->
    <div class="tab-content" id="estadoTabsContent">
        
        <!-- OCs Pendientes -->
        <div class="tab-pane fade show active" id="pendientes" role="tabpanel">
            <?php if (empty($pendientes)): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
                        <h5 class="text-muted mt-3">No tienes OCs pendientes</h5>
                        <p class="text-muted">Todas tus OCs han sido procesadas</p>
                    </div>
                </div>
            <?php else: ?>
                
                <!-- Vista de Cuadrícula -->
                <div class="vista-grid pendientes-grid">
                    <div class="row">
                        <?php foreach ($pendientes as $oc): ?>
                            <div class="col-md-6 col-lg-4 mb-3 oc-item" 
                                 data-oc="<?= strtolower(htmlspecialchars($oc['orden_servicio'])) ?>"
                                 data-cliente="<?= strtolower(htmlspecialchars($oc['cliente'])) ?>"
                                 data-direccion="<?= strtolower(htmlspecialchars($oc['direccion'])) ?>"
                                 data-suministro="<?= strtolower(htmlspecialchars($oc['num_suministro'])) ?>">
                                <div class="card oc-card pendiente border-0 shadow-sm h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <h5 class="card-title mb-0">
                                                <i class="bi bi-file-earmark-text text-primary"></i>
                                                <?= htmlspecialchars($oc['orden_servicio']) ?>
                                            </h5>
                                            <span class="badge estado-badge bg-warning text-dark">
                                                <i class="bi bi-clock"></i> Pendiente
                                            </span>
                                        </div>
                                        
                                        <div class="mb-2">
                                            <small class="text-muted d-block"><i class="bi bi-person"></i> Cliente:</small>
                                            <strong><?= htmlspecialchars($oc['cliente']) ?></strong>
                                        </div>
                                        
                                        <div class="mb-2">
                                            <small class="text-muted d-block"><i class="bi bi-geo-alt"></i> Dirección:</small>
                                            <small><?= htmlspecialchars(substr($oc['direccion'], 0, 60)) ?><?= strlen($oc['direccion']) > 60 ? '...' : '' ?></small>
                                        </div>
                                        
                                        <div class="mb-2">
                                            <small class="text-muted d-block"><i class="bi bi-hash"></i> N° Suministro:</small>
                                            <small><?= htmlspecialchars($oc['num_suministro']) ?></small>
                                        </div>
                                        
                                        <?php if ($oc['notas_admin']): ?>
                                            <div class="alert alert-info border-0 p-2 mb-2 mt-3">
                                                <small><i class="bi bi-chat-left-quote"></i> <strong>Nota del admin:</strong><br><?= nl2br(htmlspecialchars($oc['notas_admin'])) ?></small>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="text-muted small mt-2">
                                            <i class="bi bi-calendar"></i> Asignado hace <?= $oc['dias_desde_asignacion'] ?> día(s)
                                        </div>
                                        
                                        <hr>
                                        
                                        <div class="d-grid gap-2">
                                            <a href="buscar_oc.php?oc_asignada=<?= urlencode($oc['orden_servicio']) ?>" class="btn btn-primary">
                                                <i class="bi bi-clipboard-check-fill"></i> Registrar Retiro
                                            </a>
                                            <a href="?iniciar=1&id=<?= $oc['id'] ?>" class="btn btn-outline-primary btn-sm">
                                                <i class="bi bi-play-circle"></i> Marcar como "En Proceso"
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Vista de Tabla -->
                <div class="vista-tabla pendientes-tabla">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-oc-responsive mb-0">
                                    <thead class="table-warning">
                                        <tr>
                                            <th>OC</th>
                                            <th>Cliente</th>
                                            <th>Dirección</th>
                                            <th>N° Suministro</th>
                                            <th>Nota Admin</th>
                                            <th>Asignado</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pendientes as $oc): ?>
                                            <tr class="oc-item"
                                                data-oc="<?= strtolower(htmlspecialchars($oc['orden_servicio'])) ?>"
                                                data-cliente="<?= strtolower(htmlspecialchars($oc['cliente'])) ?>"
                                                data-direccion="<?= strtolower(htmlspecialchars($oc['direccion'])) ?>"
                                                data-suministro="<?= strtolower(htmlspecialchars($oc['num_suministro'])) ?>">
                                                <td>
                                                    <strong class="text-primary">
                                                        <i class="bi bi-file-earmark-text"></i>
                                                        <?= htmlspecialchars($oc['orden_servicio']) ?>
                                                    </strong>
                                                </td>
                                                <td><?= htmlspecialchars($oc['cliente']) ?></td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?= htmlspecialchars(substr($oc['direccion'], 0, 40)) ?><?= strlen($oc['direccion']) > 40 ? '...' : '' ?>
                                                    </small>
                                                </td>
                                                <td><small><?= htmlspecialchars($oc['num_suministro']) ?></small></td>
                                                <td>
                                                    <?php if ($oc['notas_admin']): ?>
                                                        <span class="badge bg-info" data-bs-toggle="tooltip" title="<?= htmlspecialchars($oc['notas_admin']) ?>">
                                                            <i class="bi bi-chat-left-quote"></i> Ver
                                                        </span>
                                                    <?php else: ?>
                                                        <small class="text-muted">-</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <i class="bi bi-calendar"></i> <?= $oc['dias_desde_asignacion'] ?> día(s)
                                                    </small>
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group" role="group">
                                                        <a href="buscar_oc.php?oc_asignada=<?= urlencode($oc['orden_servicio']) ?>" 
                                                           class="btn btn-sm btn-primary" 
                                                           data-bs-toggle="tooltip" 
                                                           title="Registrar Retiro">
                                                            <i class="bi bi-clipboard-check-fill"></i>
                                                        </a>
                                                        <a href="?iniciar=1&id=<?= $oc['id'] ?>" 
                                                           class="btn btn-sm btn-outline-primary" 
                                                           data-bs-toggle="tooltip" 
                                                           title="Marcar En Proceso">
                                                            <i class="bi bi-play-circle"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php endif; ?>
        </div>
        
        <!-- OCs En Proceso -->
        <div class="tab-pane fade" id="proceso" role="tabpanel">
            <?php if (empty($enProceso)): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
                        <h5 class="text-muted mt-3">No tienes OCs en proceso</h5>
                    </div>
                </div>
            <?php else: ?>
                
                <!-- Vista de Cuadrícula -->
                <div class="vista-grid proceso-grid">
                    <div class="row">
                        <?php foreach ($enProceso as $oc): ?>
                            <div class="col-md-6 col-lg-4 mb-3 oc-item"
                                 data-oc="<?= strtolower(htmlspecialchars($oc['orden_servicio'])) ?>"
                                 data-cliente="<?= strtolower(htmlspecialchars($oc['cliente'])) ?>"
                                 data-direccion="<?= strtolower(htmlspecialchars($oc['direccion'])) ?>"
                                 data-suministro="<?= strtolower(htmlspecialchars($oc['num_suministro'])) ?>">
                                <div class="card oc-card en-proceso border-0 shadow-sm h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <h5 class="card-title mb-0">
                                                <i class="bi bi-file-earmark-text text-primary"></i>
                                                <?= htmlspecialchars($oc['orden_servicio']) ?>
                                            </h5>
                                            <span class="badge estado-badge bg-primary">
                                                <i class="bi bi-gear-fill"></i> En Proceso
                                            </span>
                                        </div>
                                        
                                        <div class="mb-2">
                                            <small class="text-muted d-block"><i class="bi bi-person"></i> Cliente:</small>
                                            <strong><?= htmlspecialchars($oc['cliente']) ?></strong>
                                        </div>
                                        
                                        <div class="mb-2">
                                            <small class="text-muted d-block"><i class="bi bi-geo-alt"></i> Dirección:</small>
                                            <small><?= htmlspecialchars(substr($oc['direccion'], 0, 60)) ?><?= strlen($oc['direccion']) > 60 ? '...' : '' ?></small>
                                        </div>
                                        
                                        <div class="mb-2">
                                            <small class="text-muted d-block"><i class="bi bi-hash"></i> N° Suministro:</small>
                                            <small><?= htmlspecialchars($oc['num_suministro']) ?></small>
                                        </div>
                                        
                                        <?php if ($oc['notas_admin']): ?>
                                            <div class="alert alert-info border-0 p-2 mb-2 mt-3">
                                                <small><i class="bi bi-chat-left-quote"></i> <strong>Nota del admin:</strong><br><?= nl2br(htmlspecialchars($oc['notas_admin'])) ?></small>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="text-muted small mt-2">
                                            <i class="bi bi-calendar"></i> Asignado hace <?= $oc['dias_desde_asignacion'] ?> día(s)
                                        </div>
                                        
                                        <hr>
                                        
                                        <div class="d-grid">
                                            <a href="buscar_oc.php?oc_asignada=<?= urlencode($oc['orden_servicio']) ?>" class="btn btn-success">
                                                <i class="bi bi-check-circle-fill"></i> Completar Retiro
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Vista de Tabla -->
                <div class="vista-tabla proceso-tabla">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-oc-responsive mb-0">
                                    <thead class="table-primary">
                                        <tr>
                                            <th>OC</th>
                                            <th>Cliente</th>
                                            <th>Dirección</th>
                                            <th>N° Suministro</th>
                                            <th>Nota Admin</th>
                                            <th>Asignado</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($enProceso as $oc): ?>
                                            <tr class="oc-item"
                                                data-oc="<?= strtolower(htmlspecialchars($oc['orden_servicio'])) ?>"
                                                data-cliente="<?= strtolower(htmlspecialchars($oc['cliente'])) ?>"
                                                data-direccion="<?= strtolower(htmlspecialchars($oc['direccion'])) ?>"
                                                data-suministro="<?= strtolower(htmlspecialchars($oc['num_suministro'])) ?>">
                                                <td>
                                                    <strong class="text-primary">
                                                        <i class="bi bi-file-earmark-text"></i>
                                                        <?= htmlspecialchars($oc['orden_servicio']) ?>
                                                    </strong>
                                                    <br>
                                                    <span class="badge bg-primary" style="font-size: 0.7rem;">
                                                        <i class="bi bi-gear-fill"></i> En Proceso
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($oc['cliente']) ?></td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?= htmlspecialchars(substr($oc['direccion'], 0, 40)) ?><?= strlen($oc['direccion']) > 40 ? '...' : '' ?>
                                                    </small>
                                                </td>
                                                <td><small><?= htmlspecialchars($oc['num_suministro']) ?></small></td>
                                                <td>
                                                    <?php if ($oc['notas_admin']): ?>
                                                        <span class="badge bg-info" data-bs-toggle="tooltip" title="<?= htmlspecialchars($oc['notas_admin']) ?>">
                                                            <i class="bi bi-chat-left-quote"></i> Ver
                                                        </span>
                                                    <?php else: ?>
                                                        <small class="text-muted">-</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <i class="bi bi-calendar"></i> <?= $oc['dias_desde_asignacion'] ?> día(s)
                                                    </small>
                                                </td>
                                                <td class="text-center">
                                                    <a href="buscar_oc.php?oc_asignada=<?= urlencode($oc['orden_servicio']) ?>" 
                                                       class="btn btn-sm btn-success" 
                                                       data-bs-toggle="tooltip" 
                                                       title="Completar Retiro">
                                                        <i class="bi bi-check-circle-fill"></i> Completar
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php endif; ?>
        </div>
        
        <!-- OCs Completadas -->
        <div class="tab-pane fade" id="completadas" role="tabpanel">
            <?php if (empty($completadas)): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
                        <h5 class="text-muted mt-3">Aún no has completado OCs</h5>
                    </div>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-success">
                            <tr>
                                <th>OC</th>
                                <th>Cliente</th>
                                <th>Dirección</th>
                                <th>Estado Retiro</th>
                                <th>Fecha Completada</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($completadas as $oc): ?>
                                <tr class="oc-item"
                                    data-oc="<?= strtolower(htmlspecialchars($oc['orden_servicio'])) ?>"
                                    data-cliente="<?= strtolower(htmlspecialchars($oc['cliente'])) ?>"
                                    data-direccion="<?= strtolower(htmlspecialchars($oc['direccion'])) ?>"
                                    data-suministro="<?= strtolower(htmlspecialchars($oc['num_suministro'] ?? '')) ?>">
                                    <td><strong><?= htmlspecialchars($oc['orden_servicio']) ?></strong></td>
                                    <td><?= htmlspecialchars($oc['cliente']) ?></td>
                                    <td><small><?= htmlspecialchars(substr($oc['direccion'], 0, 40)) ?>...</small></td>
                                    <td>
                                        <span class="badge bg-success"><?= $oc['estado_retiro'] ?></span>
                                    </td>
                                    <td>
                                        <small><?= date('d/m/Y H:i', strtotime($oc['fecha_completada'] ?? $oc['fecha_asignacion'])) ?></small>
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

<script>
// Función para cambiar entre vistas
function cambiarVista(tipo) {
    // Guardar preferencia en localStorage
    localStorage.setItem('vistaOCs', tipo);
    
    // Actualizar botones
    const btnGrid = document.getElementById('btn-vista-grid');
    const btnTabla = document.getElementById('btn-vista-tabla');
    
    if (tipo === 'grid') {
        btnGrid.classList.add('active');
        btnTabla.classList.remove('active');
        
        // Mostrar vistas grid y ocultar tablas
        document.querySelectorAll('.vista-grid').forEach(el => el.style.display = 'block');
        document.querySelectorAll('.vista-tabla').forEach(el => el.style.display = 'none');
    } else {
        btnTabla.classList.add('active');
        btnGrid.classList.remove('active');
        
        // Mostrar vistas tabla y ocultar grids
        document.querySelectorAll('.vista-grid').forEach(el => el.style.display = 'none');
        document.querySelectorAll('.vista-tabla').forEach(el => el.style.display = 'block');
        
        // Inicializar tooltips para la vista tabla
        inicializarTooltips();
    }
}

// Función para inicializar tooltips de Bootstrap
function inicializarTooltips() {
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
}

// Función de búsqueda en tiempo real
function buscarOCs(termino) {
    termino = termino.toLowerCase().trim();
    const btnLimpiar = document.getElementById('btn-limpiar-busqueda');
    const resultadosSpan = document.getElementById('resultados-busqueda');
    
    // Mostrar/ocultar botón de limpiar
    if (termino) {
        btnLimpiar.style.display = 'block';
    } else {
        btnLimpiar.style.display = 'none';
    }
    
    // Obtener todas las OCs
    const todasLasOCs = document.querySelectorAll('.oc-item');
    let contadorVisible = {
        pendientes: 0,
        proceso: 0,
        completadas: 0,
        total: 0
    };
    
    // Filtrar OCs
    todasLasOCs.forEach(item => {
        const oc = item.dataset.oc || '';
        const cliente = item.dataset.cliente || '';
        const direccion = item.dataset.direccion || '';
        const suministro = item.dataset.suministro || '';
        
        // Buscar en todos los campos
        const coincide = !termino || 
                        oc.includes(termino) || 
                        cliente.includes(termino) || 
                        direccion.includes(termino) || 
                        suministro.includes(termino);
        
        if (coincide) {
            item.style.display = '';
            contadorVisible.total++;
            
            // Contar por sección
            const seccionPendientes = item.closest('#pendientes');
            const seccionProceso = item.closest('#proceso');
            const seccionCompletadas = item.closest('#completadas');
            
            if (seccionPendientes) contadorVisible.pendientes++;
            else if (seccionProceso) contadorVisible.proceso++;
            else if (seccionCompletadas) contadorVisible.completadas++;
        } else {
            item.style.display = 'none';
        }
    });
    
    // Actualizar contador de resultados
    if (termino) {
        resultadosSpan.textContent = `${contadorVisible.total} resultado(s) encontrado(s)`;
        
        // Actualizar badges de los tabs
        document.getElementById('count-pendientes').textContent = contadorVisible.pendientes;
        document.getElementById('count-proceso').textContent = contadorVisible.proceso;
        document.getElementById('count-completadas').textContent = contadorVisible.completadas;
    } else {
        resultadosSpan.textContent = 'Mostrando todas las OCs';
        
        // Restaurar contadores originales
        document.getElementById('count-pendientes').textContent = '<?= count($pendientes) ?>';
        document.getElementById('count-proceso').textContent = '<?= count($enProceso) ?>';
        document.getElementById('count-completadas').textContent = '<?= count($completadas) ?>';
    }
    
    // Mostrar mensaje si no hay resultados en la tab activa
    mostrarMensajesVacios(contadorVisible);
}

// Función para mostrar mensajes cuando no hay resultados
function mostrarMensajesVacios(contadores) {
    // Pendientes
    const pendientesGrid = document.querySelector('.pendientes-grid .row');
    const pendientesTabla = document.querySelector('.pendientes-tabla .table tbody');
    if (contadores.pendientes === 0 && pendientesGrid) {
        // Las cards ya están ocultas por display:none, eso es suficiente
    }
    
    // Similar para proceso y completadas
}

// Función para limpiar búsqueda
function limpiarBusqueda() {
    document.getElementById('busqueda-ocs').value = '';
    buscarOCs('');
}

// Aplicar preferencia guardada al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    const vistaGuardada = localStorage.getItem('vistaOCs');
    
    // Si hay una preferencia guardada, aplicarla
    if (vistaGuardada) {
        cambiarVista(vistaGuardada);
    } else {
        // Por defecto, mostrar vista grid
        cambiarVista('grid');
    }
    
    // Inicializar tooltips
    inicializarTooltips();
    
    // Event listener para búsqueda
    const inputBusqueda = document.getElementById('busqueda-ocs');
    inputBusqueda.addEventListener('input', function(e) {
        buscarOCs(e.target.value);
    });
    
    // Event listener para botón limpiar
    document.getElementById('btn-limpiar-busqueda').addEventListener('click', limpiarBusqueda);
    
    // Limpiar búsqueda con Escape
    inputBusqueda.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            limpiarBusqueda();
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>

