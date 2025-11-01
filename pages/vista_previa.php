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
    <style>
        /* Hero gradient header */
        .hero-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        /* Table enhancements SIN MOVIMIENTO */
        .preview-table {
            border-radius: 8px;
            overflow: hidden;
        }
        .preview-table tbody tr {
            transition: background-color 0.3s ease;
        }
        .preview-table tbody tr:hover {
            background-color: #f8f9fa !important;
        }
        
        /* Acordeones suaves */
        .accordion-button {
            background-color: #f8f9fa;
            color: #495057;
        }
        .accordion-button:not(.collapsed) {
            background-color: #e9ecef;
            color: #495057;
        }
        
        /* Card hover effect SIN MOVIMIENTO */
        .preview-card {
            transition: box-shadow 0.3s ease;
        }
        .preview-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1) !important;
        }
        
        /* Badge pulse */
        .badge-pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        /* Progress indicator */
        .progress-step {
            position: relative;
            padding-left: 40px;
        }
        .progress-step::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 30px;
            bottom: -30px;
            width: 2px;
            background: #dee2e6;
        }
        .progress-step:last-child::before {
            display: none;
        }
        .step-icon {
            position: absolute;
            left: 0;
            top: 0;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9rem;
        }
        
        /* Responsive adjustments */
        @media (max-width: 767.98px) {
            .hero-header {
                padding: 1.5rem 0;
            }
            .hero-header h1 {
                font-size: 1.5rem;
            }
            .table-responsive {
                font-size: 0.875rem;
            }
        }
    </style>
</head>
<body class="bg-light">
    <?php include '../includes/session_middleware.php'; ?>
    
    <!-- Navbar -->
    <nav class="navbar navbar-dark bg-dark shadow-sm">
        <div class="container-fluid px-3 px-md-4">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="../index.php">
                <i class="bi bi-speedometer2 me-2 fs-4"></i>
                <span class="d-none d-sm-inline">GASELAG</span>
            </a>
            <a href="buscar_oc.php" class="btn btn-outline-light btn-sm">
                <i class="bi bi-arrow-left"></i>
                <span class="d-none d-sm-inline"> Volver</span>
            </a>
        </div>
    </nav>

    <!-- Hero Header -->
    <div class="hero-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="fw-bold mb-2">
                        <i class="bi bi-eye-fill me-2"></i>
                        Vista Previa de Órdenes
                    </h1>
                    <p class="mb-3 opacity-90">Revisa y confirma las OCs seleccionadas antes de continuar</p>
                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        <span class="badge bg-light text-dark px-3 py-2 badge-pulse">
                            <i class="bi bi-list-check me-1"></i>
                            <?= count($ordenes) ?> Orden<?= count($ordenes) > 1 ? 'es' : '' ?> de Servicio
                        </span>
                        <span class="badge bg-success px-3 py-2">
                            <i class="bi bi-check-circle me-1"></i>
                            Listas para procesar
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container pb-4">
        <!-- Pasos del proceso -->
        <div class="row mb-4">
            <div class="col-lg-10 mx-auto">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h6 class="text-primary mb-3">
                            <i class="bi bi-diagram-3-fill me-2"></i>
                            Proceso de Registro
                        </h6>
                        <div class="row g-0">
                            <div class="col-md-4">
                                <div class="progress-step">
                                    <span class="step-icon bg-success text-white">
                                        <i class="bi bi-check"></i>
                                    </span>
                                    <strong class="d-block mb-1">1. Búsqueda</strong>
                                    <small class="text-muted">OCs encontradas y agregadas</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="progress-step">
                                    <span class="step-icon bg-primary text-white">2</span>
                                    <strong class="d-block mb-1">2. Vista Previa</strong>
                                    <small class="text-success">Estás aquí - Verificando datos</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="progress-step">
                                    <span class="step-icon bg-secondary text-white">3</span>
                                    <strong class="d-block mb-1">3. Registro</strong>
                                    <small class="text-muted">Completar formularios de retiro</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información importante -->
        <div class="alert alert-info border-0 shadow-sm">
            <div class="d-flex align-items-start">
                <i class="bi bi-info-circle-fill fs-3 me-3"></i>
                <div>
                    <strong class="d-block mb-2">Antes de continuar:</strong>
                    <ul class="mb-0 small">
                        <li>Verifica que todas las OCs sean correctas</li>
                        <li>Se crearán <?= count($ordenes) ?> formulario<?= count($ordenes) > 1 ? 's' : '' ?> de registro</li>
                        <li>Deberás completar la información de cada medidor y tomar las fotos correspondientes</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Tabla de órdenes -->
        <div class="card preview-card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-table me-2"></i>
                        Detalle de Órdenes Seleccionadas
                    </h5>
                    <span class="badge bg-light text-primary">
                        <?= count($ordenes) ?> OCs
                    </span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover preview-table mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width: 60px;">#</th>
                                <th>
                                    <i class="bi bi-tag-fill me-1"></i>
                                    Orden de Servicio
                                </th>
                                <th>
                                    <i class="bi bi-upc-scan me-1"></i>
                                    N° Serie Medidor
                                </th>
                                <th class="d-none d-md-table-cell">
                                    <i class="bi bi-hash me-1"></i>
                                    N° Suministro
                                </th>
                                <th class="d-none d-lg-table-cell">
                                    <i class="bi bi-geo-alt-fill me-1"></i>
                                    Dirección
                                </th>
                                <th class="text-center" style="width: 80px;">
                                    <i class="bi bi-clipboard-check"></i>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ordenes as $index => $orden): ?>
                                <tr>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark"><?= $index + 1 ?></span>
                                    </td>
                                    <td>
                                        <strong class="text-primary"><?= htmlspecialchars($orden['orden_servicio']) ?></strong>
                                        <br>
                                        <small class="text-muted d-md-none">
                                            <i class="bi bi-hash"></i>
                                            <?= htmlspecialchars($orden['num_suministro']) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info text-dark">
                                            <?= htmlspecialchars($orden['num_serie_medidor']) ?>
                                        </span>
                                    </td>
                                    <td class="d-none d-md-table-cell">
                                        <small><?= htmlspecialchars($orden['num_suministro']) ?></small>
                                    </td>
                                    <td class="d-none d-lg-table-cell">
                                        <small class="text-muted">
                                            <?= htmlspecialchars($orden['direccion']) ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <i class="bi bi-circle text-warning" title="Pendiente"></i>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-light border-0">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <small class="text-muted">
                        <i class="bi bi-info-circle"></i>
                        Total: <strong><?= count($ordenes) ?></strong> orden<?= count($ordenes) > 1 ? 'es' : '' ?> para procesar
                    </small>
                    <small class="text-muted d-none d-md-block">
                        <i class="bi bi-circle text-warning"></i> Pendiente
                    </small>
                </div>
            </div>
        </div>

        <!-- Información adicional responsive -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-clipboard-check text-primary" style="font-size: 2.5rem;"></i>
                        <h6 class="mt-3 mb-2">Registro Individual</h6>
                        <p class="text-muted small mb-0">Cada OC tendrá su formulario de retiro independiente</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-camera text-success" style="font-size: 2.5rem;"></i>
                        <h6 class="mt-3 mb-2">Evidencia Fotográfica</h6>
                        <p class="text-muted small mb-0">Deberás tomar foto de cada medidor retirado</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-save text-info" style="font-size: 2.5rem;"></i>
                        <h6 class="mt-3 mb-2">Guardado Automático</h6>
                        <p class="text-muted small mb-0">Tu progreso se guarda automáticamente</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botones de acción -->
        <div class="row g-3">
            <div class="col-md-4">
                <a href="buscar_oc.php" class="btn btn-outline-secondary w-100 btn-lg">
                    <i class="bi bi-arrow-left-circle"></i>
                    Modificar Selección
                </a>
            </div>
            <div class="col-md-8">
                <a href="formulario_retiro.php?index=0" class="btn btn-success w-100 btn-lg shadow">
                    <i class="bi bi-play-circle-fill me-2"></i>
                    Comenzar Registro de Retiros
                    <i class="bi bi-arrow-right ms-2"></i>
                </a>
            </div>
        </div>

        <!-- Ayuda colapsable -->
        <div class="accordion mt-4" id="accordionAyuda">
            <div class="accordion-item border-0 shadow-sm">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                            data-bs-target="#collapseAyuda" aria-expanded="false">
                        <i class="bi bi-question-circle me-2 text-muted"></i>
                        <span class="text-muted">¿Qué sucederá al continuar?</span>
                    </button>
                </h2>
                <div id="collapseAyuda" class="accordion-collapse collapse" data-bs-parent="#accordionAyuda">
                    <div class="accordion-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="d-flex align-items-start">
                                    <span class="badge rounded-circle bg-light text-dark me-3" 
                                          style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; font-weight: 600;">1</span>
                                    <div>
                                        <strong class="d-block mb-1">Formulario por OC</strong>
                                        <small class="text-muted">
                                            Verás un formulario para cada orden, donde ingresarás los datos del retiro
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-start">
                                    <span class="badge rounded-circle bg-light text-dark me-3" 
                                          style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; font-weight: 600;">2</span>
                                    <div>
                                        <strong class="d-block mb-1">Navegación Secuencial</strong>
                                        <small class="text-muted">
                                            Podrás avanzar y retroceder entre los formularios de las OCs
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-start">
                                    <span class="badge rounded-circle bg-light text-dark me-3" 
                                          style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; font-weight: 600;">3</span>
                                    <div>
                                        <strong class="d-block mb-1">Captura de Fotos</strong>
                                        <small class="text-muted">
                                            Desde tu cámara o galería, adjunta la evidencia fotográfica requerida
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-start">
                                    <span class="badge rounded-circle bg-light text-dark me-3" 
                                          style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; font-weight: 600;">4</span>
                                    <div>
                                        <strong class="d-block mb-1">Finalización</strong>
                                        <small class="text-muted">
                                            Al completar todas las OCs, verás un resumen final de los registros
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

