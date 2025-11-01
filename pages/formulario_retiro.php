<?php
require_once '../config/database.php';
require_once '../config/AppConfig.php';

// Verificar autenticación (todos los usuarios)
requireRole(['admin', 'user']);

// Verificar que haya OCs seleccionadas
if (!isset($_SESSION['selected_ocs']) || empty($_SESSION['selected_ocs'])) {
    header('Location: buscar_oc.php');
    exit;
}

$currentIndex = isset($_GET['index']) ? intval($_GET['index']) : 0;
$totalOCs = count($_SESSION['selected_ocs']);

// Verificar índice válido
if ($currentIndex < 0 || $currentIndex >= $totalOCs) {
    header('Location: buscar_oc.php');
    exit;
}

$currentOC = $_SESSION['selected_ocs'][$currentIndex];

// Obtener datos de la OC actual
try {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM ordenes_servicio WHERE orden_servicio = ?");
    $stmt->execute([$currentOC]);
    $orden = $stmt->fetch();
    
    if (!$orden) {
        die("Orden de servicio no encontrada");
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// ===== VALIDACIÓN ANTI-DUPLICACIÓN =====
// Verificar si esta OC ya fue registrada por alguien
$existingRetiro = checkExistingRetiro($currentOC);
if ($existingRetiro) {
    // Registrar intento en auditoría
    logAudit(null, $_SESSION['user_id'], 'intento_registro_oc',
             "Intento de registro de OC ya procesada: $currentOC por {$existingRetiro['tecnico_responsable']}",
             $currentOC);

    // Si es admin, permitir continuar con reapertura
    if (isAdmin()) {
        $message = "⚠️ <strong>Esta OC ya fue registrada</strong> por: <strong>{$existingRetiro['tecnico_responsable']}</strong> el " .
                  date('d/m/Y H:i', strtotime($existingRetiro['fecha_registro'])) .
                  ". Como administrador, puede <strong>reabrir</strong> esta OC para nuevo registro.";
        $messageType = 'warning';
    } else {
        // Si es técnico, bloquear completamente
        $message = "❌ <strong>Esta OC ya fue registrada</strong> por: <strong>{$existingRetiro['tecnico_responsable']}</strong> el " .
                  date('d/m/Y H:i', strtotime($existingRetiro['fecha_registro'])) .
                  ". No se puede registrar nuevamente.";
        $messageType = 'danger';

        // Mostrar información adicional del registro existente
        if ($existingRetiro['medidor_retirado'] === 'SI') {
            $message .= " <em>(Medidor SÍ fue retirado)</em>";
        } else {
            $message .= " <em>(Medidor NO fue retirado)</em>";
        }
    }
}

// Obtener usuario actual para auto-asignación
$currentUser = getCurrentUser();

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getConnection();

        // Debug: mostrar datos recibidos
        error_log("=== DEBUG FORMULARIO RETIRO ===");
        error_log("POST data: " . print_r($_POST, true));
        error_log("FILES data: " . print_r($_FILES, true));
        error_log("=================================");
        
        // Crear directorio para fotos si no existe
        $uploadDir = '../uploads/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Procesar foto de imposibilidad
        $fotoPath = null;
        if (isset($_FILES['foto_imposibilidad']) && $_FILES['foto_imposibilidad']['error'] === UPLOAD_ERR_OK) {
            // SEGURIDAD: Validar tipo MIME real del archivo
            $allowedMimeTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $maxFileSize = 10 * 1024 * 1024; // 10MB
            
            // Verificar tamaño
            if ($_FILES['foto_imposibilidad']['size'] > $maxFileSize) {
                throw new Exception("El archivo es demasiado grande (máximo 10MB)");
            }
            
            // Verificar tipo MIME real usando finfo
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $_FILES['foto_imposibilidad']['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedMimeTypes)) {
                throw new Exception("Tipo de archivo no permitido. Solo se aceptan imágenes (JPEG, PNG, GIF, WebP)");
            }
            
            // Obtener extensión segura basada en MIME type
            $extensionMap = [
                'image/jpeg' => 'jpg',
                'image/jpg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp'
            ];
            $extension = $extensionMap[$mimeType] ?? 'jpg';
            
            // Formato: OC-xxx_NumSuministro_NumSerie_FechaHora.extension
            $numSuministro = !empty($orden['num_suministro']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $orden['num_suministro']) : 'SIN_SUMINISTRO';
            $numSerie = !empty($orden['num_serie_medidor']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $orden['num_serie_medidor']) : 'SIN_SERIE';
            $fechaHora = date('Ymd_His'); // Formato: 20251025_143022
            
            $fileName = $currentOC . '_' . $numSuministro . '_' . $numSerie . '_' . $fechaHora . '.' . $extension;
            $fotoPath = $uploadDir . $fileName;
            
            if (!move_uploaded_file($_FILES['foto_imposibilidad']['tmp_name'], $fotoPath)) {
                throw new Exception("Error al guardar el archivo");
            }
            
            $fotoPath = 'uploads/' . $fileName; // Ruta relativa para BD
        }
        
        // Preparar datos
        $medidor_retirado = $_POST['medidor_retirado'];
        error_log("Medidor retirado: " . $medidor_retirado);

        // Si no se retiró, los campos específicos serán NULL
        $lectura_m3 = ($medidor_retirado === 'SI' && !empty($_POST['lectura_m3'])) ? $_POST['lectura_m3'] : null;
        $puntero_girando = ($medidor_retirado === 'SI') ? $_POST['puntero_girando'] : null;
        $medidor_con_precinto = ($medidor_retirado === 'SI') ? $_POST['medidor_con_precinto'] : null;
        $medidor_tiene_filtro = ($medidor_retirado === 'SI') ? $_POST['medidor_tiene_filtro'] : null;
        $filtro_buen_estado = ($medidor_retirado === 'SI' && isset($_POST['filtro_buen_estado'])) ? $_POST['filtro_buen_estado'] : null;
        $solidos_retenidos = ($medidor_retirado === 'SI' && isset($_POST['solidos_retenidos'])) ? $_POST['solidos_retenidos'] : null;
        $info_caja = ($medidor_retirado === 'SI' && !empty($_POST['info_caja'])) ? $_POST['info_caja'] : null;

        // Información de imposibilidad (nuevo sistema)
        $tipo_imposibilidad_id = null;
        $detalles_imposibilidad = null;
        if ($medidor_retirado === 'NO') {
            $tipo_imposibilidad_id = $_POST['tipo_imposibilidad'] ?? null;
            $detalles_imposibilidad = $_POST['detalles_imposibilidad'] ?? null;

            // Mantener compatibilidad con el sistema anterior
            $visor_imposibilidad = null;
            if ($tipo_imposibilidad_id) {
                // Si se seleccionó un tipo de imposibilidad relacionado con lectura, marcar como SI
                $tipoInfo = getTipoImposibilidad($tipo_imposibilidad_id);
                if ($tipoInfo && strpos(strtolower($tipoInfo['descripcion']), 'lectura') !== false) {
                    $visor_imposibilidad = 'SI';
                } else {
                    $visor_imposibilidad = 'NO';
                }
            }
        }
        
        // Determinar si tiene foto
        $tiene_foto = (!empty($fotoPath)) ? 'SI' : 'NO';

        // Verificar si la columna tiene_foto existe (para compatibilidad)
        $checkColumnQuery = "SHOW COLUMNS FROM retiros_medidores LIKE 'tiene_foto'";
        $columnExists = $pdo->query($checkColumnQuery)->rowCount() > 0;

        // Obtener técnico del campo oculto
        $tecnicoResponsable = isset($_POST['tecnico_hidden']) ? $_POST['tecnico_hidden'] :
                              (isset($_POST['tecnico']) ? $_POST['tecnico'] : null);

        if ($columnExists) {
            // Con la nueva estructura (incluyendo usuario_id y tipos de imposibilidad)
            $sql = "INSERT INTO retiros_medidores (
                orden_servicio_id, orden_servicio, medidor_retirado, lectura_m3,
                puntero_girando, medidor_con_precinto, visor_imposibilidad_lectura,
                medidor_tiene_filtro, filtro_buen_estado, solidos_retenidos_filtro,
                info_caja_medidor, observacion, foto_imposibilidad, tiene_foto,
                tecnico_responsable, usuario_id, tipo_imposibilidad_id, detalles_imposibilidad
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $orden['id'],
            $currentOC,
            $medidor_retirado,
            $lectura_m3,
            $puntero_girando,
            $medidor_con_precinto,
            $visor_imposibilidad,
            $medidor_tiene_filtro,
            $filtro_buen_estado,
            $solidos_retenidos,
            $info_caja,
            $_POST['observacion'],
            $fotoPath,
            $tiene_foto,
            $tecnicoResponsable,
            $_SESSION['user_id'],
            $tipo_imposibilidad_id,
            $detalles_imposibilidad
        ]);

        // Obtener ID del registro insertado
        $registroId = $pdo->lastInsertId();

        // Marcar evidencia como obligatoria si corresponde y establecer fecha límite
        if ($tipo_imposibilidad_id) {
            marcarEvidenciaObligatoria($registroId, $tipo_imposibilidad_id);
        }

        // Registrar en auditoría
        $tipoInfo = $tipo_imposibilidad_id ? getTipoImposibilidad($tipo_imposibilidad_id) : null;
        $tipoDescripcion = $tipoInfo ? $tipoInfo['descripcion'] : 'Sin especificar';
        logAudit($registroId, $_SESSION['user_id'], 'registro_oc',
                "Registro exitoso de OC: $currentOC - Retirado: $medidor_retirado - Tipo: $tipoDescripcion",
                $currentOC);

        error_log("Registro guardado exitosamente. ID: " . $registroId);
        } else {
            // Verificar si existe la columna usuario_id para agregarla
            $checkUserColumnQuery = "SHOW COLUMNS FROM retiros_medidores LIKE 'usuario_id'";
            $userColumnExists = $pdo->query($checkUserColumnQuery)->rowCount() > 0;

            if ($userColumnExists) {
                // Verificar si existe la columna tipo_imposibilidad_id
                $checkImposibilidadColumnQuery = "SHOW COLUMNS FROM retiros_medidores LIKE 'tipo_imposibilidad_id'";
                $imposibilidadColumnExists = $pdo->query($checkImposibilidadColumnQuery)->rowCount() > 0;

                if ($imposibilidadColumnExists) {
                    // Con usuario_id y tipos de imposibilidad
                    $sql = "INSERT INTO retiros_medidores (
                        orden_servicio_id, orden_servicio, medidor_retirado, lectura_m3,
                        puntero_girando, medidor_con_precinto, visor_imposibilidad_lectura,
                        medidor_tiene_filtro, filtro_buen_estado, solidos_retenidos_filtro,
                        info_caja_medidor, observacion, foto_imposibilidad,
                        tecnico_responsable, usuario_id, tipo_imposibilidad_id, detalles_imposibilidad
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $orden['id'],
                        $currentOC,
                        $medidor_retirado,
                        $lectura_m3,
                        $puntero_girando,
                        $medidor_con_precinto,
                        $visor_imposibilidad,
                        $medidor_tiene_filtro,
                        $filtro_buen_estado,
                        $solidos_retenidos,
                        $info_caja,
                        $_POST['observacion'],
                        $fotoPath,
                        $tecnicoResponsable,
                        $_SESSION['user_id'],
                        $tipo_imposibilidad_id,
                        $detalles_imposibilidad
                    ]);
                } else {
                    // Con usuario_id pero sin tipos de imposibilidad
                    $sql = "INSERT INTO retiros_medidores (
                        orden_servicio_id, orden_servicio, medidor_retirado, lectura_m3,
                        puntero_girando, medidor_con_precinto, visor_imposibilidad_lectura,
                        medidor_tiene_filtro, filtro_buen_estado, solidos_retenidos_filtro,
                        info_caja_medidor, observacion, foto_imposibilidad,
                        tecnico_responsable, usuario_id
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $orden['id'],
                        $currentOC,
                        $medidor_retirado,
                        $lectura_m3,
                        $puntero_girando,
                        $medidor_con_precinto,
                        $visor_imposibilidad,
                        $medidor_tiene_filtro,
                        $filtro_buen_estado,
                        $solidos_retenidos,
                        $info_caja,
                        $_POST['observacion'],
                        $fotoPath,
                        $tecnicoResponsable,
                        $_SESSION['user_id']
                    ]);
                }
            } else {
                // Estructura anterior completa (sin usuario_id ni tipos de imposibilidad)
                $sql = "INSERT INTO retiros_medidores (
                    orden_servicio_id, orden_servicio, medidor_retirado, lectura_m3,
                    puntero_girando, medidor_con_precinto, visor_imposibilidad_lectura,
                    medidor_tiene_filtro, filtro_buen_estado, solidos_retenidos_filtro,
                    info_caja_medidor, observacion, foto_imposibilidad, tecnico_responsable
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $orden['id'],
                    $currentOC,
                    $medidor_retirado,
                    $lectura_m3,
                    $puntero_girando,
                    $medidor_con_precinto,
                    $visor_imposibilidad,
                    $medidor_tiene_filtro,
                    $filtro_buen_estado,
                    $solidos_retenidos,
                    $info_caja,
                    $_POST['observacion'],
                    $fotoPath,
                    $tecnicoResponsable
                ]);
            }

            // Obtener ID del registro insertado
            $registroId = $pdo->lastInsertId();

            // Registrar en auditoría
            $tipoInfo = $tipo_imposibilidad_id ? getTipoImposibilidad($tipo_imposibilidad_id) : null;
            $tipoDescripcion = $tipoInfo ? $tipoInfo['descripcion'] : 'Sin especificar';
            logAudit($registroId, $_SESSION['user_id'], 'registro_oc',
                    "Registro exitoso de OC: $currentOC - Retirado: $medidor_retirado - Tipo: $tipoDescripcion",
                    $currentOC);

            error_log("Registro guardado exitosamente (estructura anterior). ID: " . $registroId);
        }

        // Redirigir al siguiente formulario o a la página de finalización
        $nextIndex = $currentIndex + 1;
        if ($nextIndex < $totalOCs) {
            header("Location: formulario_retiro.php?index=$nextIndex");
        } else {
            header("Location: finalizar.php");
        }
        exit;
        
    } catch (Exception $e) {
        $message = "Error al guardar: " . $e->getMessage();
        $messageType = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de Retiro - <?= htmlspecialchars($currentOC) ?></title>
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
        
        /* Section titles ULTRA SUAVES */
        .section-title {
            background: linear-gradient(to right, #f8f9fa, #ffffff);
            padding: 12px 20px;
            border-left: 4px solid #a8b3d9;
            margin: 25px 0 20px 0;
            font-weight: 600;
            border-radius: 0 8px 8px 0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
            transition: all 0.3s ease;
        }
        .section-title:hover {
            box-shadow: 0 2px 4px rgba(102, 126, 234, 0.08);
        }
        
        /* Card hover effects SIN MOVIMIENTO */
        .info-card, .form-card {
            transition: box-shadow 0.3s ease;
        }
        .info-card:hover, .form-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1) !important;
        }
        
        /* Progress card SIN MOVIMIENTO */
        .progress-card {
            transition: box-shadow 0.3s ease;
        }
        .progress-card:hover {
            box-shadow: 0 2px 6px rgba(0,0,0,0.08) !important;
        }
        
        /* Radio buttons visibles y funcionales */
        .form-check-input {
            opacity: 1 !important;
            visibility: visible !important;
            pointer-events: auto !important;
            cursor: pointer !important;
            width: 1.2em;
            height: 1.2em;
            margin-top: 0.15em;
        }
        .form-check-input:checked {
            background-color: #667eea !important;
            border-color: #667eea !important;
        }
        .form-check-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .form-check-label {
            cursor: pointer !important;
            user-select: none;
            padding-left: 0.5rem;
        }
        
        /* Form inputs con efecto MÁS SUAVE */
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
        }
        
        /* File input mejorado */
        .form-control[type="file"] {
            cursor: pointer;
            padding: 0.6rem 0.75rem;
        }
        
        /* Badge pulse */
        .badge-pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        /* Botones SIN MOVIMIENTO */
        .btn-submit {
            transition: box-shadow 0.3s ease;
        }
        .btn-submit:hover {
            box-shadow: 0 3px 8px rgba(102, 126, 234, 0.2);
        }
        
        /* Acordeones con colores suaves */
        .accordion-button {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: 500;
        }
        .accordion-button:not(.collapsed) {
            background-color: #e9ecef;
            color: #495057;
            box-shadow: none;
        }
        .accordion-button:focus {
            border-color: #a8b3d9;
            box-shadow: 0 0 0 0.2rem rgba(168, 179, 217, 0.15);
        }
        
        /* Alert con bordes suaves */
        .alert {
            border-radius: 8px;
            border: none;
        }
        
        /* Asegurar campos visibles */
        #campos_retiro {
            min-height: 200px;
        }
        
        /* Progress bar animado */
        .progress {
            border-radius: 10px;
            overflow: hidden;
        }
        .progress-bar {
            transition: width 0.6s ease;
        }
        
        /* Form groups con spacing */
        .form-group-spacing {
            margin-bottom: 1.5rem;
        }
        
        /* Responsive adjustments */
        @media (max-width: 767.98px) {
            .hero-header {
                padding: 1.5rem 0;
            }
            .hero-header h1 {
                font-size: 1.5rem;
            }
            .section-title {
                font-size: 0.95rem;
                padding: 10px 15px;
            }
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-dark bg-dark shadow-sm">
        <div class="container-fluid px-3 px-md-4">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="../index.php">
                <i class="bi bi-speedometer2 me-2 fs-4"></i>
                <span class="d-none d-sm-inline">GASELAG</span>
            </a>
            <a href="vista_previa.php" class="btn btn-outline-light btn-sm">
                <i class="bi bi-arrow-left"></i>
                <span class="d-none d-sm-inline"> Vista Previa</span>
            </a>
        </div>
    </nav>

    <!-- Hero Header -->
    <div class="hero-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-10 mx-auto text-center">
                    <h1 class="fw-bold mb-2">
                        <i class="bi bi-clipboard-data me-2"></i>
                        Formulario de Retiro
                    </h1>
                    <p class="mb-3 opacity-90">Orden de Servicio: <strong><?= htmlspecialchars($currentOC) ?></strong></p>
                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        <span class="badge bg-light text-dark px-3 py-2">
                            <i class="bi bi-list-ol me-1"></i>
                            OC <?= $currentIndex + 1 ?> de <?= $totalOCs ?>
                        </span>
                        <span class="badge bg-warning text-dark px-3 py-2 badge-pulse">
                            <i class="bi bi-hourglass-split me-1"></i>
                            <?= round((($currentIndex + 1) / $totalOCs) * 100) ?>% Completado
                        </span>
                        <?php if ($totalOCs - $currentIndex > 1): ?>
                            <span class="badge bg-info px-3 py-2">
                                <i class="bi bi-arrow-right me-1"></i>
                                <?= $totalOCs - $currentIndex - 1 ?> pendiente<?= ($totalOCs - $currentIndex - 1) > 1 ? 's' : '' ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container pb-4">
        <!-- Barra de progreso visual -->
        <div class="card border-0 shadow-sm mb-4 progress-card">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <small class="text-muted fw-bold">
                        <i class="bi bi-speedometer2"></i> Progreso General
                    </small>
                    <span class="badge bg-primary"><?= round((($currentIndex + 1) / $totalOCs) * 100) ?>%</span>
                </div>
                <div class="progress" style="height: 25px;">
                    <div class="progress-bar bg-gradient" role="progressbar" 
                         style="width: <?= (($currentIndex + 1) / $totalOCs) * 100 ?>%; background: linear-gradient(to right, #667eea, #764ba2);">
                        <span class="fw-bold"><?= $currentIndex + 1 ?> / <?= $totalOCs ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información de la OC -->
        <div class="card border-0 shadow-sm mb-4 info-card">
            <div class="card-header bg-primary text-white">
                <div class="d-flex align-items-center">
                    <i class="bi bi-info-circle-fill fs-4 me-2"></i>
                    <h5 class="mb-0">Información de la Orden de Servicio</h5>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block mb-1">
                                <i class="bi bi-tag-fill text-primary"></i> Orden de Servicio
                            </small>
                            <strong class="fs-5 text-primary"><?= htmlspecialchars($currentOC) ?></strong>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block mb-1">
                                <i class="bi bi-hash text-info"></i> N° Suministro
                            </small>
                            <strong><?= htmlspecialchars($orden['num_suministro']) ?></strong>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block mb-1">
                                <i class="bi bi-person-badge text-success"></i> Usuario Reclamante
                            </small>
                            <strong><?= htmlspecialchars($orden['usuario_reclamante']) ?></strong>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block mb-1">
                                <i class="bi bi-geo-alt-fill text-danger"></i> Dirección
                            </small>
                            <strong><?= htmlspecialchars($orden['direccion']) ?></strong>
                        </div>
                    </div>
                </div>
                <hr class="my-3">
                <div class="row g-3">
                    <div class="col-md-4">
                        <small class="text-muted d-block">
                            <i class="bi bi-upc-scan text-warning"></i> N° Serie Medidor
                        </small>
                        <strong><?= htmlspecialchars($orden['num_serie_medidor']) ?></strong>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">
                            <i class="bi bi-bookmark-star text-info"></i> Marca
                        </small>
                        <strong><?= htmlspecialchars($orden['marca_medidor']) ?></strong>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">
                            <i class="bi bi-box-seam text-secondary"></i> Modelo
                        </small>
                        <strong><?= htmlspecialchars($orden['modelo_medidor']) ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulario de Retiro -->
        <form method="POST" enctype="multipart/form-data" id="retiroForm">
            <div class="card border-0 shadow-sm form-card">
                <div class="card-header bg-success text-white">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-clipboard-check-fill fs-4 me-2"></i>
                        <h5 class="mb-0">Formulario de Registro de Retiro</h5>
                    </div>
                </div>
                <div class="card-body p-4">
                    <?php if ($message): ?>
                        <div class="alert alert-<?= e($messageType) ?> alert-dismissible fade show border-0 shadow-sm">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-<?= $messageType === 'danger' ? 'exclamation-triangle' : ($messageType === 'warning' ? 'exclamation-circle' : 'info-circle') ?>-fill fs-4 me-3"></i>
                                <div class="flex-grow-1">
                                    <?= eSafe($message) ?>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Técnico Responsable (Auto-asignado) -->
                    <div class="mb-4">
                        <label for="tecnico" class="form-label">
                            <i class="bi bi-person-check"></i> Técnico Responsable *
                        </label>
                        <input type="text" class="form-control" id="tecnico" name="tecnico"
                               value="<?php echo htmlspecialchars($currentUser['nombre_completo']); ?>"
                               readonly style="background-color: #e9ecef; cursor: not-allowed;">
                        <input type="hidden" id="tecnico_hidden" name="tecnico"
                               value="<?php echo htmlspecialchars($currentUser['nombre_completo']); ?>">
                        <div class="form-text">
                            <i class="bi bi-info-circle"></i>
                            Auto-asignado: <?php echo htmlspecialchars($currentUser['nombre_completo']); ?>
                            (<?php echo htmlspecialchars($currentUser['username']); ?>)
                        </div>
                    </div>

                    <!-- Pregunta Principal -->
                    <div class="section-title">
                        <i class="bi bi-question-circle"></i> ¿SE RETIRÓ EL MEDIDOR?
                    </div>
                    <div class="mb-4">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="medidor_retirado" 
                                   id="retirado_si" value="SI" required onchange="toggleFields()">
                            <label class="form-check-label" for="retirado_si">
                                <strong>SÍ</strong>
                            </label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="medidor_retirado" 
                                   id="retirado_no" value="NO" required onchange="toggleFields()">
                            <label class="form-check-label" for="retirado_no">
                                <strong>NO</strong>
                            </label>
                        </div>
                    </div>

                    <div id="campos_retiro">
                        <!-- INFORMACIÓN SOBRE MEDIDOR -->
                        <div class="section-title">
                            <i class="bi bi-speedometer"></i> INFORMACIÓN SOBRE MEDIDOR
                        </div>
                        <div class="mb-4">
                            <label for="lectura_m3" class="form-label">Lectura de m³ del medidor de agua retirado</label>
                            <input type="number" class="form-control" id="lectura_m3" 
                                   name="lectura_m3" placeholder="Ej: 125">
                        </div>

                        <!-- REPORTE VISUAL DEL MEDIDOR -->
                        <div class="section-title">
                            <i class="bi bi-eye"></i> REPORTE VISUAL DEL MEDIDOR
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Puntero del medidor girando</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="puntero_girando" 
                                           id="puntero_si" value="SI">
                                    <label class="form-check-label" for="puntero_si">SÍ</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="puntero_girando" 
                                           id="puntero_no" value="NO">
                                    <label class="form-check-label" for="puntero_no">NO</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Medidor con precinto de seguridad</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="medidor_con_precinto" 
                                           id="precinto_si" value="SI">
                                    <label class="form-check-label" for="precinto_si">SÍ</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="medidor_con_precinto" 
                                           id="precinto_no" value="NO">
                                    <label class="form-check-label" for="precinto_no">NO</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Visor con imposibilidad de lectura</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="visor_imposibilidad" 
                                           id="visor_si" value="SI">
                                    <label class="form-check-label" for="visor_si">SÍ</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="visor_imposibilidad" 
                                           id="visor_no" value="NO">
                                    <label class="form-check-label" for="visor_no">NO</label>
                                </div>
                            </div>
                        </div>

                        <!-- REPORTE VISUAL DEL FILTRO -->
                        <div class="section-title">
                            <i class="bi bi-funnel"></i> REPORTE VISUAL DEL FILTRO
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Medidor tiene filtro</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="medidor_tiene_filtro" 
                                           id="filtro_si" value="SI" onchange="toggleFiltroFields()">
                                    <label class="form-check-label" for="filtro_si">SÍ</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="medidor_tiene_filtro" 
                                           id="filtro_no" value="NO" onchange="toggleFiltroFields()">
                                    <label class="form-check-label" for="filtro_no">NO</label>
                                </div>
                            </div>
                        </div>

                        <div id="campos_filtro" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">El filtro está en buen estado de conservación</label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="filtro_buen_estado" 
                                               id="estado_si" value="SI">
                                        <label class="form-check-label" for="estado_si">SÍ</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="filtro_buen_estado" 
                                               id="estado_no" value="NO">
                                        <label class="form-check-label" for="estado_no">NO</label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Existen sólidos retenidos en el filtro</label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="solidos_retenidos" 
                                               id="solidos_si" value="SI">
                                        <label class="form-check-label" for="solidos_si">SÍ</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="solidos_retenidos" 
                                               id="solidos_no" value="NO">
                                        <label class="form-check-label" for="solidos_no">NO</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- INFORMACIÓN DE CAJA Y MEDIDOR -->
                        <div class="section-title">
                            <i class="bi bi-inbox"></i> INFORMACIÓN DE CAJA Y MEDIDOR DE AGUA
                        </div>
                        <div class="mb-4">
                            <textarea class="form-control" id="info_caja" name="info_caja" rows="3" 
                                      placeholder="Describa el estado de la caja y medidor..."></textarea>
                        </div>
                    </div>

                    <!-- PREGUNTA SOBRE IMPOSIBILIDAD (solo si NO se retiró) -->
                    <div id="pregunta_imposibilidad" style="display: none;">
                        <div class="section-title">
                            <i class="bi bi-exclamation-triangle"></i> MOTIVO DE IMPOSIBILIDAD
                        </div>
                        <!-- TIPO DE IMPOSIBILIDAD (solo si NO se retiró) -->
                        <div class="mb-4">
                            <label for="tipo_imposibilidad" class="form-label">
                                <i class="bi bi-exclamation-triangle"></i> Tipo de Imposibilidad *
                            </label>
                            <select class="form-select" id="tipo_imposibilidad" name="tipo_imposibilidad" required>
                                <option value="">Seleccionar motivo...</option>
                                <?php
                                $tiposImposibilidad = getTiposImposibilidad();
                                foreach ($tiposImposibilidad as $tipo) {
                                    $categoriaIcon = match($tipo['categoria']) {
                                        'acceso' => '🚪',
                                        'medidor' => '⚡',
                                        'cliente' => '👤',
                                        'seguridad' => '⚠️',
                                        'otros' => '📋',
                                        default => '❓'
                                    };
                                    echo "<option value=\"{$tipo['id']}\">{$categoriaIcon} {$tipo['descripcion']}</option>\n";
                                }
                                ?>
                            </select>
                            <div class="form-text">
                                <strong class="text-info">Importante:</strong> Seleccione el motivo principal por el cual no se pudo realizar el retiro
                            </div>
                        </div>

                        <!-- DETALLES ADICIONALES (solo para "Otros") -->
                        <div class="mb-4" id="campo_detalles_imposibilidad" style="display: none;">
                            <label for="detalles_imposibilidad" class="form-label">
                                <i class="bi bi-chat-left-text"></i> Motivo Personalizado <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" id="detalles_imposibilidad" name="detalles_imposibilidad"
                                      rows="2" placeholder="Describa específicamente el motivo de la imposibilidad..."></textarea>
                            <div class="form-text">
                                <strong class="text-danger">Requerido:</strong> Especifique el motivo detallado por el cual no se pudo retirar el medidor
                            </div>
                        </div>
                    </div>

                    <!-- OBSERVACIÓN (siempre visible) -->
                    <div class="section-title">
                        <i class="bi bi-chat-left-text"></i> OBSERVACIONES ADICIONALES
                    </div>
                    <div class="mb-4">
                        <textarea class="form-control" id="observacion" name="observacion" rows="3"
                                  placeholder="Información adicional opcional que complemente el registro..."></textarea>
                        <div class="form-text">
                            Opcional: Puede agregar observaciones adicionales sobre la situación si lo considera necesario
                        </div>
                    </div>

                    <!-- EVIDENCIA FOTOGRÁFICA (solo si NO se retiró) -->
                    <div id="campo_foto" style="display: none;">
                        <div class="section-title">
                            <i class="bi bi-camera"></i> EVIDENCIA FOTOGRÁFICA
                        </div>
                        <div class="mb-4">
                            <input type="file" class="form-control" id="foto_imposibilidad"
                                   name="foto_imposibilidad" accept="image/*">
                            <div class="form-text">Adjunte foto de evidencia (opcional)</div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Botones de navegación -->
                    <div class="row g-2 g-md-3">
                        <div class="col-12 col-md-4">
                            <?php if ($currentIndex > 0): ?>
                                <a href="?index=<?= $currentIndex - 1 ?>" class="btn btn-outline-secondary w-100">
                                    <i class="bi bi-arrow-left-circle"></i>
                                    <span class="d-none d-sm-inline">OC Anterior</span>
                                    <span class="d-sm-none">Anterior</span>
                                </a>
                            <?php else: ?>
                                <a href="vista_previa.php" class="btn btn-outline-secondary w-100">
                                    <i class="bi bi-arrow-left-circle"></i>
                                    <span class="d-none d-sm-inline">Vista Previa</span>
                                    <span class="d-sm-none">Volver</span>
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="col-12 col-md-8">
                            <button type="submit" class="btn btn-success w-100 btn-lg btn-submit shadow">
                                <?php if ($currentIndex < $totalOCs - 1): ?>
                                    <i class="bi bi-save me-2"></i>
                                    Guardar y Continuar
                                    <i class="bi bi-arrow-right-circle ms-2"></i>
                                <?php else: ?>
                                    <i class="bi bi-check-circle-fill me-2"></i>
                                    Guardar y Finalizar
                                <?php endif; ?>
                            </button>
                        </div>
                    </div>

                    <!-- Botón debug (solo visible en desarrollo) -->
                    <?php if (isset($_GET['debug'])): ?>
                        <div class="mt-3">
                            <button type="button" class="btn btn-sm btn-outline-info w-100" onclick="debugFormulario()">
                                <i class="bi bi-bug"></i> Modo Debug
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </form>

        <!-- Ayuda rápida colapsable -->
        <div class="accordion mt-4" id="accordionAyuda">
            <div class="accordion-item border-0 shadow-sm">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                            data-bs-target="#collapseAyuda" aria-expanded="false">
                        <i class="bi bi-lightbulb me-2 text-muted"></i>
                        <span class="text-muted">Ayuda Rápida - ¿Cómo completar el formulario?</span>
                    </button>
                </h2>
                <div id="collapseAyuda" class="accordion-collapse collapse" data-bs-parent="#accordionAyuda">
                    <div class="accordion-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="d-flex align-items-start">
                                    <i class="bi bi-check-circle text-success me-3 fs-5 opacity-75"></i>
                                    <div>
                                        <strong class="d-block mb-1">¿Se retiró el medidor?</strong>
                                        <small class="text-muted">
                                            Selecciona "SÍ" si lograste retirar el medidor y completa todos los datos técnicos.
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-start">
                                    <i class="bi bi-x-circle text-danger me-3 fs-5 opacity-75"></i>
                                    <div>
                                        <strong class="d-block mb-1">¿No se pudo retirar?</strong>
                                        <small class="text-muted">
                                            Selecciona "NO", indica el motivo de imposibilidad y adjunta foto de evidencia.
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-start">
                                    <i class="bi bi-camera text-info me-3 fs-5 opacity-75"></i>
                                    <div>
                                        <strong class="d-block mb-1">Evidencia Fotográfica</strong>
                                        <small class="text-muted">
                                            Toma fotos claras y nítidas. La evidencia es fundamental para el proceso.
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-start">
                                    <i class="bi bi-save text-warning me-3 fs-5 opacity-75"></i>
                                    <div>
                                        <strong class="d-block mb-1">Guardar Progreso</strong>
                                        <small class="text-muted">
                                            Al guardar, avanzarás a la siguiente OC o finalizarás el proceso.
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
    <script>
        function toggleFields() {
            const retirado = document.querySelector('input[name="medidor_retirado"]:checked');
            const camposRetiro = document.getElementById('campos_retiro');
            const campoFoto = document.getElementById('campo_foto');
            const preguntaImposibilidad = document.getElementById('pregunta_imposibilidad');
            const obsHelp = document.getElementById('obs_help');

            if (retirado) {
                if (retirado.value === 'SI') {
                    camposRetiro.style.display = 'block';
                    campoFoto.style.display = 'none';
                    preguntaImposibilidad.style.display = 'none';
                    obsHelp.style.display = 'none';

                    // Limpiar validaciones de NO retiro
                    document.getElementById('tipo_imposibilidad').removeAttribute('required');
                    document.getElementById('detalles_imposibilidad').removeAttribute('required');
                    // observacion es opcional, no necesita required
                } else {
                    camposRetiro.style.display = 'none';
                    campoFoto.style.display = 'block';
                    preguntaImposibilidad.style.display = 'block';
                    obsHelp.style.display = 'block';

                    // Hacer obligatorios los campos de NO retiro
                    document.getElementById('tipo_imposibilidad').setAttribute('required', 'required');
                    // detalles_imposibilidad se maneja dinámicamente según el tipo
                    // observacion es opcional

                    // Inicializar validación de detalles
                    setTimeout(toggleDetallesRequeridos, 10);
                }
            }
        }

        // Función para debuggear
        function debugFormulario() {
            const radioButtons = document.querySelectorAll('input[name="medidor_retirado"]');
            const camposRetiro = document.getElementById('campos_retiro');
            const campoFoto = document.getElementById('campo_foto');
            const preguntaImposibilidad = document.getElementById('pregunta_imposibilidad');

            console.log('=== DEBUG FORMULARIO ===');
            console.log('Radio buttons encontrados:', radioButtons.length);
            radioButtons.forEach(function(el, index) {
                console.log(`Radio ${index} (${el.id}):`, el.value, 'checked:', el.checked);
            });
            console.log('Campos retiro visible:', camposRetiro ? camposRetiro.style.display : 'NO ENCONTRADO');
            console.log('Campo foto visible:', campoFoto ? campoFoto.style.display : 'NO ENCONTRADO');
            console.log('Pregunta imposibilidad visible:', preguntaImposibilidad ? preguntaImposibilidad.style.display : 'NO ENCONTRADO');
            console.log('=======================');
        }

        function toggleFields() {
            const retirado = document.querySelector('input[name="medidor_retirado"]:checked');
            const camposRetiro = document.getElementById('campos_retiro');
            const campoFoto = document.getElementById('campo_foto');
            const preguntaImposibilidad = document.getElementById('pregunta_imposibilidad');
            const obsHelp = document.getElementById('obs_help');

            console.log('toggleFields llamado, valor seleccionado:', retirado ? retirado.value : 'NINGUNO');

            if (retirado) {
                if (retirado.value === 'SI') {
                    console.log('Mostrando campos de medidor retirado');
                    if (camposRetiro) camposRetiro.style.display = 'block';
                    if (campoFoto) campoFoto.style.display = 'none';
                    if (preguntaImposibilidad) preguntaImposibilidad.style.display = 'none';
                    if (obsHelp) obsHelp.style.display = 'none';

                    // Limpiar validaciones de NO retiro
                    const tipoImposibilidad = document.getElementById('tipo_imposibilidad');
                    if (tipoImposibilidad) tipoImposibilidad.removeAttribute('required');
                    const detallesImposibilidad = document.getElementById('detalles_imposibilidad');
                    if (detallesImposibilidad) detallesImposibilidad.removeAttribute('required');
                    // observacion es opcional
                } else {
                    console.log('Mostrando campos de medidor NO retirado');
                    if (camposRetiro) camposRetiro.style.display = 'none';
                    if (campoFoto) campoFoto.style.display = 'block';
                    if (preguntaImposibilidad) preguntaImposibilidad.style.display = 'block';
                    if (obsHelp) obsHelp.style.display = 'block';

                    // Hacer obligatorios los campos de NO retiro
                    const tipoImposibilidad = document.getElementById('tipo_imposibilidad');
                    if (tipoImposibilidad) tipoImposibilidad.setAttribute('required', 'required');
                    // detalles_imposibilidad es opcional
                    // observacion es opcional
                }
            }

            debugFormulario();
        }

        // Inicializar cuando se carga la página
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM cargado, inicializando formulario...');
            debugFormulario();

            // Pequeño delay para asegurar que todos los elementos estén cargados
            setTimeout(function() {
                toggleFields();
                debugFormulario();
            }, 100);

            // Agregar event listeners a todos los radio buttons
            document.querySelectorAll('input[name="medidor_retirado"]').forEach(function(el) {
                el.addEventListener('change', function() {
                    console.log('Cambio detectado en:', this.id, 'valor:', this.value);
                    toggleFields();
                });

                // También agregar event listener para click en el label
                const labelId = el.id;
                const label = document.querySelector(`label[for="${labelId}"]`);
                if (label) {
                    label.addEventListener('click', function() {
                        console.log('Click en label para:', labelId);
                        setTimeout(toggleFields, 10);
                    });
                }
            });

            // Agregar event listener para cambios en tipo de imposibilidad
            document.getElementById('tipo_imposibilidad').addEventListener('change', function() {
                toggleDetallesRequeridos();
            });
        });

        function toggleDetallesRequeridos() {
            const tipoSelect = document.getElementById('tipo_imposibilidad');
            const campoDetalles = document.getElementById('campo_detalles_imposibilidad');
            const detallesField = document.getElementById('detalles_imposibilidad');

            if (tipoSelect.selectedIndex > 0) {
                const tipoSeleccionado = tipoSelect.options[tipoSelect.selectedIndex];
                const tipoTexto = tipoSeleccionado ? tipoSeleccionado.text.toLowerCase() : '';

                if (tipoTexto.includes('otros')) {
                    // Mostrar campo requerido para "Otros"
                    campoDetalles.style.display = 'block';
                    detallesField.setAttribute('required', 'required');
                } else {
                    // Ocultar campo para tipos predefinidos - el tipo seleccionado ya es suficiente
                    campoDetalles.style.display = 'none';
                    detallesField.removeAttribute('required');
                    detallesField.value = ''; // Limpiar si había algo
                }
            } else {
                // Sin tipo seleccionado, ocultar campo
                campoDetalles.style.display = 'none';
                detallesField.removeAttribute('required');
                detallesField.value = ''; // Limpiar si había algo
            }
        }

        function toggleFiltroFields() {
            const tieneFiltro = document.querySelector('input[name="medidor_tiene_filtro"]:checked');
            const camposFiltro = document.getElementById('campos_filtro');
            
            if (tieneFiltro && tieneFiltro.value === 'SI') {
                camposFiltro.style.display = 'block';
            } else {
                camposFiltro.style.display = 'none';
            }
        }

        // Validación del formulario
        document.getElementById('retiroForm').addEventListener('submit', function(e) {
            const retirado = document.querySelector('input[name="medidor_retirado"]:checked');

            if (!retirado) {
                e.preventDefault();
                alert('Debe indicar si el medidor fue retirado o no');
                return false;
            }

            if (retirado.value === 'NO') {
                // Validar tipo de imposibilidad
                const tipoImposibilidad = document.getElementById('tipo_imposibilidad');
                if (!tipoImposibilidad.value) {
                    e.preventDefault();
                    alert('Debe seleccionar el tipo de imposibilidad por el cual no se retiró el medidor');
                    return false;
                }

                // Si se seleccionó "Otros", verificar que haya detalles personalizados
                const tipoSeleccionado = tipoImposibilidad.options[tipoImposibilidad.selectedIndex];
                const tipoTexto = tipoSeleccionado ? tipoSeleccionado.text.toLowerCase() : '';

                if (tipoTexto.includes('otros')) {
                    const detalles = document.getElementById('detalles_imposibilidad').value.trim();
                    if (detalles === '') {
                        e.preventDefault();
                        alert('Cuando selecciona "Otros Motivos", debe especificar el motivo personalizado en el campo "Motivo Personalizado"');
                        document.getElementById('detalles_imposibilidad').focus();
                        return false;
                    }
                }

                // La foto NO es obligatoria en el momento del registro
                // Se puede adjuntar posteriormente dentro de las 6 horas
                const fotoInput = document.getElementById('foto_imposibilidad');
                if (fotoInput.files && fotoInput.files.length > 0) {
                    const tipoInfo = tipoImposibilidad.options[tipoImposibilidad.selectedIndex];
                    const descripcionTipo = tipoInfo.text.toLowerCase();
                    console.log('Foto adjuntada para tipo:', descripcionTipo);
                }
            }

            // Si se retiró el medidor, validar campos específicos del medidor
            if (retirado.value === 'SI') {
                console.log('Validando formulario para medidor RETIRADO');

                // Validar que el técnico esté especificado
                const tecnico = document.getElementById('tecnico').value.trim();
                if (tecnico === '') {
                    e.preventDefault();
                    alert('Debe especificar el nombre del técnico responsable');
                    document.getElementById('tecnico').focus();
                    return false;
                }

                return true; // Permitir envío si el técnico está especificado
            }
        });
    </script>
</body>
</html>

