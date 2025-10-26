<?php
require_once '../config/database.php';

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

$message = '';
$messageType = '';

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
            $extension = pathinfo($_FILES['foto_imposibilidad']['name'], PATHINFO_EXTENSION);
            
            // Formato: OC-xxx_NumSuministro_NumSerie_FechaHora.extension
            $numSuministro = !empty($orden['num_suministro']) ? $orden['num_suministro'] : 'SIN_SUMINISTRO';
            $numSerie = !empty($orden['num_serie_medidor']) ? $orden['num_serie_medidor'] : 'SIN_SERIE';
            $fechaHora = date('Ymd_His'); // Formato: 20251025_143022
            
            $fileName = $currentOC . '_' . $numSuministro . '_' . $numSerie . '_' . $fechaHora . '.' . $extension;
            $fotoPath = $uploadDir . $fileName;
            move_uploaded_file($_FILES['foto_imposibilidad']['tmp_name'], $fotoPath);
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

        // Información de imposibilidad
        $visor_imposibilidad = null;
        if ($medidor_retirado === 'NO') {
            $visor_imposibilidad_no_retirado = $_POST['visor_imposibilidad_no_retirado'] ?? null;
            if ($visor_imposibilidad_no_retirado === 'SI') {
                $visor_imposibilidad = 'SI';
            } elseif ($visor_imposibilidad_no_retirado === 'NO') {
                $visor_imposibilidad = 'NO';
            }
        }
        
        // Determinar si tiene foto
        $tiene_foto = (!empty($fotoPath)) ? 'SI' : 'NO';

        // Verificar si la columna tiene_foto existe (para compatibilidad)
        $checkColumnQuery = "SHOW COLUMNS FROM retiros_medidores LIKE 'tiene_foto'";
        $columnExists = $pdo->query($checkColumnQuery)->rowCount() > 0;

        if ($columnExists) {
            // Con la nueva estructura
            $sql = "INSERT INTO retiros_medidores (
                orden_servicio_id, orden_servicio, medidor_retirado, lectura_m3,
                puntero_girando, medidor_con_precinto, visor_imposibilidad_lectura,
                medidor_tiene_filtro, filtro_buen_estado, solidos_retenidos_filtro,
                info_caja_medidor, observacion, foto_imposibilidad, tiene_foto, tecnico_responsable
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
            $tiene_foto,
            isset($_POST['tecnico']) ? $_POST['tecnico'] : null
        ]);

        error_log("Registro guardado exitosamente. ID: " . $pdo->lastInsertId());
        } else {
            // Sin la nueva columna (estructura anterior)
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
                isset($_POST['tecnico']) ? $_POST['tecnico'] : null
            ]);

            error_log("Registro guardado exitosamente (estructura anterior). ID: " . $pdo->lastInsertId());
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
        .section-title {
            background-color: #f8f9fa;
            padding: 10px 15px;
            border-left: 4px solid #0d6efd;
            margin: 20px 0 15px 0;
            font-weight: bold;
        }

        /* Asegurar que los radio buttons sean visibles y funcionales */
        .form-check-input {
            opacity: 1 !important;
            visibility: visible !important;
            pointer-events: auto !important;
            cursor: pointer !important;
        }

        .form-check-input:checked {
            background-color: #0d6efd !important;
            border-color: #0d6efd !important;
        }

        .form-check-label {
            cursor: pointer !important;
            user-select: none;
        }

        /* Debug para radio buttons */
        input[name="medidor_retirado"] {
            cursor: pointer !important;
            opacity: 1 !important;
        }

        /* Asegurar que el div de campos sea visible */
        #campos_retiro {
            min-height: 200px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="mb-3">
            <a href="vista_previa.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Volver a Vista Previa
            </a>
        </div>

        <!-- Progreso -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Progreso: OC <?= $currentIndex + 1 ?> de <?= $totalOCs ?></h6>
                    <span class="badge bg-secondary"><?= round((($currentIndex + 1) / $totalOCs) * 100) ?>%</span>
                </div>
                <div class="progress" style="height: 20px;">
                    <div class="progress-bar" role="progressbar" 
                         style="width: <?= (($currentIndex + 1) / $totalOCs) * 100 ?>%">
                    </div>
                </div>
            </div>
        </div>

        <!-- Información de la OC -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-info-circle text-primary"></i>
                    Información de la Orden: <?= htmlspecialchars($currentOC) ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>N° Suministro:</strong> <?= htmlspecialchars($orden['num_suministro']) ?></p>
                        <p><strong>Usuario:</strong> <?= htmlspecialchars($orden['usuario_reclamante']) ?></p>
                        <p><strong>Dirección:</strong> <?= htmlspecialchars($orden['direccion']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>N° Serie Medidor:</strong> <?= htmlspecialchars($orden['num_serie_medidor']) ?></p>
                        <p><strong>Marca:</strong> <?= htmlspecialchars($orden['marca_medidor']) ?></p>
                        <p><strong>Modelo:</strong> <?= htmlspecialchars($orden['modelo_medidor']) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulario de Retiro -->
        <form method="POST" enctype="multipart/form-data" id="retiroForm">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="bi bi-clipboard-check text-primary"></i>
                        Formulario de Retiro de Medidor
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                            <?= $message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Técnico Responsable -->
                    <div class="mb-4">
                        <label for="tecnico" class="form-label">
                            <i class="bi bi-person"></i> Técnico Responsable *
                        </label>
                        <input type="text" class="form-control" id="tecnico" name="tecnico"
                               placeholder="Nombre del técnico" required>
                        <div class="form-text">* Campo obligatorio</div>
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
                            <i class="bi bi-eye-slash"></i> IMPOSIBILIDAD DE LECTURA
                        </div>
                        <div class="mb-4">
                            <label class="form-label">¿El medidor presenta imposibilidad de lectura?</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="visor_imposibilidad_no_retirado"
                                           id="visor_imposibilidad_no_retirado_si" value="SI" required>
                                    <label class="form-check-label" for="visor_imposibilidad_no_retirado_si">SÍ</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="visor_imposibilidad_no_retirado"
                                           id="visor_imposibilidad_no_retirado_no" value="NO" required>
                                    <label class="form-check-label" for="visor_imposibilidad_no_retirado_no">NO</label>
                                </div>
                            </div>
                            <div class="form-text">
                                <strong class="text-info">Importante:</strong> Si marca "SÍ", se requiere evidencia fotográfica
                            </div>
                        </div>
                    </div>

                    <!-- OBSERVACIÓN (siempre visible) -->
                    <div class="section-title">
                        <i class="bi bi-chat-left-text"></i> OBSERVACIÓN
                    </div>
                    <div class="mb-4">
                        <textarea class="form-control" id="observacion" name="observacion" rows="3"
                                  placeholder="Observaciones adicionales..." required></textarea>
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
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <?php if ($currentIndex > 0): ?>
                                <a href="?index=<?= $currentIndex - 1 ?>" class="btn btn-outline-secondary w-100">
                                    <i class="bi bi-arrow-left"></i> OC Anterior
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-3 mb-2">
                            <button type="button" class="btn btn-info w-100" onclick="debugFormulario()">
                                <i class="bi bi-bug"></i> Debug
                            </button>
                        </div>
                        <div class="col-md-3 mb-2">
                            <button type="submit" class="btn btn-success w-100 btn-lg">
                                <?php if ($currentIndex < $totalOCs - 1): ?>
                                    Guardar y Continuar <i class="bi bi-arrow-right"></i>
                                <?php else: ?>
                                    Guardar y Finalizar <i class="bi bi-check-circle"></i>
                                <?php endif; ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
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
                    document.getElementById('observacion').removeAttribute('required');
                    document.querySelectorAll('input[name="visor_imposibilidad_no_retirado"]').forEach(function(el) {
                        el.removeAttribute('required');
                    });
                } else {
                    camposRetiro.style.display = 'none';
                    campoFoto.style.display = 'block';
                    preguntaImposibilidad.style.display = 'block';
                    obsHelp.style.display = 'block';

                    // Hacer obligatorios los campos de NO retiro
                    document.getElementById('observacion').setAttribute('required', 'required');
                    document.querySelectorAll('input[name="visor_imposibilidad_no_retirado"]').forEach(function(el) {
                        el.setAttribute('required', 'required');
                    });
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
                    const observacion = document.getElementById('observacion');
                    if (observacion) observacion.removeAttribute('required');
                    document.querySelectorAll('input[name="visor_imposibilidad_no_retirado"]').forEach(function(el) {
                        el.removeAttribute('required');
                    });
                } else {
                    console.log('Mostrando campos de medidor NO retirado');
                    if (camposRetiro) camposRetiro.style.display = 'none';
                    if (campoFoto) campoFoto.style.display = 'block';
                    if (preguntaImposibilidad) preguntaImposibilidad.style.display = 'block';
                    if (obsHelp) obsHelp.style.display = 'block';

                    // Hacer obligatorios los campos de NO retiro
                    const observacion = document.getElementById('observacion');
                    if (observacion) observacion.setAttribute('required', 'required');
                    document.querySelectorAll('input[name="visor_imposibilidad_no_retirado"]').forEach(function(el) {
                        el.setAttribute('required', 'required');
                    });
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
        });

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
                const observacion = document.getElementById('observacion').value.trim();
                if (observacion === '') {
                    e.preventDefault();
                    alert('Debe indicar el motivo por el cual no se retiró el medidor en el campo de Observación');
                    return false;
                }

                // Validar imposibilidad de lectura
                const imposibilidad = document.querySelector('input[name="visor_imposibilidad_no_retirado"]:checked');
                if (!imposibilidad) {
                    e.preventDefault();
                    alert('Debe indicar si el medidor presenta imposibilidad de lectura');
                    return false;
                }

                // Si hay imposibilidad de lectura, la foto es obligatoria
                if (imposibilidad.value === 'SI') {
                    const fotoInput = document.getElementById('foto_imposibilidad');
                    if (!fotoInput.files || fotoInput.files.length === 0) {
                        e.preventDefault();
                        alert('Cuando hay imposibilidad de lectura, es obligatorio adjuntar foto de evidencia');
                        return false;
                    }
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

