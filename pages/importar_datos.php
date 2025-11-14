<?php
require_once '../config/database.php';

// Verificar autenticación y rol de administrador
requireRole(['admin']);

$pageTitle = 'Importar Datos - Sistema GASELAG';
require_once '../includes/header.php';

$message = '';
$messageType = '';

// Obtener estadísticas actuales
$pdo = getConnection();
$totalRegistros = $pdo->query("SELECT COUNT(*) as total FROM ordenes_servicio")->fetch()['total'];

// Procesar importación (método anterior para compatibilidad)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csv_data'])) {
    try {
            $lines = explode("\n", trim($_POST['csv_data']));
            $success = 0;
            $errors = 0;
            
            $pdo->beginTransaction();
            
            foreach ($lines as $lineNumber => $line) {
                if (empty(trim($line))) continue;
                
                $data = explode("\t", $line);
                
                if (count($data) < 33) {
                    $errors++;
                    continue;
                }
                
                $sql = "INSERT INTO ordenes_servicio (
                    item, orden_servicio, fecha_os, cantidad_medidores, tipo_servicio,
                    programacion_dia_retiro, programacion_hora_retiro, programacion_dia_vp, 
                    programacion_hora_vp, codigo_seguridad, cliente, centro_servicio, remesa,
                    usuario_reclamante, direccion, cus, cup, num_suministro, num_serie_medidor,
                    marca_medidor, modelo_medidor, anio_fabricacion, fabricante, procedencia,
                    tipo_medidor, diametro_nominal, q3, alcance, pma, tma, clase_sensibilidad,
                    certificado_aprobacion, num_certificado
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    item = VALUES(item),
                    fecha_os = VALUES(fecha_os),
                    cantidad_medidores = VALUES(cantidad_medidores),
                    tipo_servicio = VALUES(tipo_servicio),
                    updated_at = CURRENT_TIMESTAMP";
                
                $stmt = $pdo->prepare($sql);
                
                // Convertir fechas de DD/MM/YYYY a YYYY-MM-DD y horas de formato decimal a HH:MM
                $fecha_os = convertExcelDateToMySQL($data[2]);
                $prog_dia_retiro = convertExcelDateToMySQL($data[5]);
                $prog_dia_vp = convertExcelDateToMySQL($data[7]);
                $prog_hora_retiro = convertDecimalTimeToStandard($data[6]);
                $prog_hora_vp = convertDecimalTimeToStandard($data[8]);
                
                try {
                    $stmt->execute([
                        trim($data[0]),  // item
                        trim($data[1]),  // orden_servicio
                        $fecha_os,       // fecha_os
                        intval($data[3]), // cantidad_medidores
                        trim($data[4]),  // tipo_servicio
                        $prog_dia_retiro, // programacion_dia_retiro
                        $prog_hora_retiro, // programacion_hora_retiro (10.3 → 10:30)
                        $prog_dia_vp,    // programacion_dia_vp
                        $prog_hora_vp,  // programacion_hora_vp (10.3 → 10:30)
                        trim($data[9]),  // codigo_seguridad
                        trim($data[10]), // cliente
                        trim($data[11]), // centro_servicio
                        trim($data[12]), // remesa
                        trim($data[13]), // usuario_reclamante
                        trim($data[14]), // direccion
                        trim($data[15]), // cus
                        trim($data[16]), // cup
                        trim($data[17]), // num_suministro
                        trim($data[18]), // num_serie_medidor
                        trim($data[19]), // marca_medidor
                        trim($data[20]), // modelo_medidor
                        !empty($data[21]) ? intval($data[21]) : null, // anio_fabricacion
                        trim($data[22]), // fabricante
                        trim($data[23]), // procedencia
                        trim($data[24]), // tipo_medidor
                        !empty($data[25]) ? intval($data[25]) : null, // diametro_nominal
                        !empty($data[26]) ? floatval($data[26]) : null, // q3
                        trim($data[27]), // alcance
                        !empty($data[28]) ? intval($data[28]) : null, // pma
                        !empty($data[29]) ? intval($data[29]) : null, // tma
                        trim($data[30]), // clase_sensibilidad
                        trim($data[31]), // certificado_aprobacion
                        trim($data[32])  // num_certificado
                    ]);
                    $success++;
                } catch (PDOException $e) {
                    $errors++;
                }
            }
            
            $pdo->commit();
        
        $message = "Importación completada: $success registros importados exitosamente";
        if ($errors > 0) {
            $message .= ", $errors registros con errores";
        }
        $messageType = $errors > 0 ? 'warning' : 'success';
        
        // Actualizar total
        $totalRegistros = $pdo->query("SELECT COUNT(*) as total FROM ordenes_servicio")->fetch()['total'];
        
    } catch (Exception $e) {
        $pdo->rollback();
        $message = "Error en la importación: " . $e->getMessage();
        $messageType = 'error';
    }
}

/**
 * Convierte hora de formato decimal (10.3, 8.15, 14) a formato HH:MM
 * El decimal representa decenas de minutos: 10.3 = 10:30, 8.15 = 08:15
 * @param string $time Hora en formato decimal
 * @return string Hora en formato HH:MM
 */
function convertDecimalTimeToStandard($time) {
    if (empty($time)) {
        return '';
    }
    
    // Limpiar espacios
    $time = trim($time);
    
    // Si ya está en formato HH:MM, retornar tal cual
    if (preg_match('/^\d{1,2}:\d{2}$/', $time)) {
        return $time;
    }
    
    // Separar por punto
    $parts = explode('.', $time);
    $hours = intval($parts[0]);
    
    // Si no hay parte decimal, los minutos son 00
    if (count($parts) == 1) {
        $minutes = '00';
    } else {
        // La parte decimal son los minutos (10.3 = 10:30, 8.15 = 08:15)
        $minutesPart = $parts[1];
        // Asegurar que tenga 2 dígitos (8.5 = 08:50, 10.3 = 10:30)
        $minutes = str_pad($minutesPart, 2, '0', STR_PAD_RIGHT);
    }
    
    // Formatear con ceros iniciales
    return sprintf('%02d:%s', $hours, $minutes);
}

/**
 * Convierte fecha de formato DD/MM/YYYY (Excel) a YYYY-MM-DD (MySQL)
 * Acepta fechas con o sin ceros iniciales: 6/01/2025 o 06/01/2025
 * @param string $date Fecha en formato DD/MM/YYYY o D/M/YYYY
 * @return string|null Fecha en formato YYYY-MM-DD o null si es inválida
 */
function convertExcelDateToMySQL($date) {
    if (empty($date)) {
        return null;
    }
    
    // Limpiar espacios
    $date = trim($date);
    
    // Si ya está en formato YYYY-MM-DD, retornar tal cual
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return $date;
    }
    
    // Intentar convertir DD/MM/YYYY a YYYY-MM-DD (con ceros)
    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $date, $matches)) {
        $day = $matches[1];
        $month = $matches[2];
        $year = $matches[3];
        
        // Validar que sea una fecha válida
        if (checkdate($month, $day, $year)) {
            return "$year-$month-$day";
        }
    }
    
    // Intentar convertir D/M/YYYY a YYYY-MM-DD (sin ceros iniciales)
    // Acepta: 6/01/2025, 06/1/2025, 6/1/2025, etc.
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $date, $matches)) {
        $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
        $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
        $year = $matches[3];
        
        // Validar que sea una fecha válida
        if (checkdate($month, $day, $year)) {
            return "$year-$month-$day";
        }
    }
    
    // Si no se pudo convertir, retornar null
    return null;
}
?>

<style>
/* Upload area mejorada */
.upload-area {
    border: 3px dashed #dee2e6;
    cursor: pointer;
    transition: all 0.3s ease;
    min-height: 200px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}
.upload-area:hover {
    border-color: #0d6efd;
    background-color: #f8f9fa;
}
.upload-area.dragover {
    border-color: #198754;
    background-color: #d1e7dd;
}
.upload-area.has-file {
    border-color: #198754;
    background-color: #d1e7dd;
}

/* Progress bar container */
.progress-container {
    display: none;
}

/* Stats cards responsive */
.stat-card {
    transition: box-shadow 0.2s ease;
}
.stat-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

/* File preview */
.file-preview {
    display: none;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    margin-top: 15px;
}
.file-preview.show {
    display: block;
}
</style>

<div class="container py-4">
    <!-- Header -->
    <div class="bg-white rounded shadow-sm p-3 p-md-4 mb-3 mb-md-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-1">
                    <i class="bi bi-cloud-upload text-primary"></i> 
                    Importar Órdenes de Servicio
                </h2>
                <p class="text-muted mb-0">
                    Carga masiva de datos desde Excel o CSV
                    <a href="../GUIA_IMPORTACION_CSV.md" target="_blank" class="ms-2 text-decoration-none">
                        <i class="bi bi-question-circle"></i> Ver guía completa
                    </a>
                </p>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType === 'error' ? 'danger' : ($messageType === 'warning' ? 'warning' : 'success'); ?> alert-dismissible fade show">
            <i class="bi bi-<?php echo $messageType === 'error' ? 'exclamation-triangle' : ($messageType === 'warning' ? 'exclamation-circle' : 'check-circle'); ?>"></i>
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Estadísticas responsive -->
    <div class="row g-2 g-md-3 mb-3 mb-md-4">
                    <div class="col-12 col-sm-6 col-lg-4">
                        <div class="card border-0 shadow-sm bg-primary text-white stat-card h-100">
                            <div class="card-body p-3 d-flex align-items-center">
                                <div class="d-flex justify-content-between align-items-center w-100">
                                    <div class="flex-grow-1">
                                        <p class="mb-1 small opacity-75">Total de Registros</p>
                                        <h3 class="mb-0 fw-bold"><?php echo number_format($totalRegistros); ?></h3>
                                    </div>
                                    <div>
                                        <i class="bi bi-database fs-1 opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-4">
                        <div class="card border-0 shadow-sm bg-success text-white stat-card h-100">
                            <div class="card-body p-3 d-flex align-items-center">
                                <div class="d-flex justify-content-between align-items-center w-100">
                                    <div class="flex-grow-1">
                                        <p class="mb-1 small opacity-75">Sistema</p>
                                        <h5 class="mb-0 fw-bold">Actualizado</h5>
                                        <small class="opacity-75">Listo para importar</small>
                                    </div>
                                    <div>
                                        <i class="bi bi-check-circle fs-1 opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-12 col-lg-4">
                        <div class="card border-0 shadow-sm bg-info text-white stat-card h-100">
                            <div class="card-body p-3 d-flex align-items-center">
                                <div class="d-flex justify-content-between align-items-center w-100">
                                    <div class="flex-grow-1">
                                        <p class="mb-1 small opacity-75">Formato</p>
                                        <h5 class="mb-0 fw-bold">CSV / Excel</h5>
                                        <small class="opacity-75">Separador: punto y coma</small>
                                    </div>
                                    <div>
                                        <i class="bi bi-file-earmark-spreadsheet fs-1 opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Métodos de Importación con Tabs -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0">
                            <i class="bi bi-folder-plus"></i> Métodos de Importación
                        </h5>
                    </div>
                    <div class="card-body p-0 p-md-3">
                        <!-- Tabs de navegación -->
                        <ul class="nav nav-tabs nav-fill border-bottom mb-3" id="importTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="plantilla-tab" data-bs-toggle="tab" data-bs-target="#plantilla" type="button" role="tab">
                                    <i class="bi bi-file-earmark-arrow-down d-none d-md-inline"></i>
                                    <span class="small">Plantilla</span>
                                    <span class="badge bg-success ms-1 d-none d-lg-inline">Recomendado</span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="subir-tab" data-bs-toggle="tab" data-bs-target="#subir" type="button" role="tab">
                                    <i class="bi bi-cloud-upload d-none d-md-inline"></i>
                                    <span class="small">Subir CSV</span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="copiar-tab" data-bs-toggle="tab" data-bs-target="#copiar" type="button" role="tab">
                                    <i class="bi bi-clipboard-data d-none d-md-inline"></i>
                                    <span class="small">Copiar/Pegar</span>
                                </button>
                            </li>
                        </ul>

                        <!-- Contenido de los tabs -->
                        <div class="tab-content" id="importTabContent">
                            <!-- TAB 1: Descargar Plantilla -->
                            <div class="tab-pane fade show active p-3" id="plantilla" role="tabpanel">
                                <div class="row g-3">
                                    <div class="col-lg-6 mb-3">
                                        <div class="border rounded p-3 bg-light">
                                            <h6 class="fw-bold text-success mb-3">
                                                <i class="bi bi-list-check"></i> Pasos a seguir:
                                            </h6>
                                            <ol class="mb-0">
                                                <li class="mb-2">Descarga la plantilla CSV</li>
                                                <li class="mb-2">Abre con Excel o Google Sheets</li>
                                                <li class="mb-2">Completa los datos en las columnas</li>
                                                <li class="mb-2">Guarda el archivo como CSV</li>
                                                <li>Sube el archivo en la pestaña "Subir CSV"</li>
                                            </ol>
                                        </div>
                                    </div>
                                    
                                    <div class="col-lg-6">
                                        <div class="text-center p-4 bg-success bg-opacity-10 rounded">
                                            <i class="bi bi-file-earmark-arrow-down text-success" style="font-size: 5rem;"></i>
                                            <h5 class="mt-3 mb-3">Plantilla Preconfigurada</h5>
                                            <p class="text-muted small mb-4">
                                                CSV con todos los campos necesarios y formato correcto
                                            </p>
                                            
                                            <div class="d-grid gap-2">
                                                <a href="descargar_plantilla_excel.php" class="btn btn-success btn-lg">
                                                    <i class="bi bi-download me-2"></i>Descargar Plantilla
                                                </a>
                                                <div class="alert alert-info mb-0 text-start">
                                                    <small>
                                                        <i class="bi bi-info-circle"></i>
                                                        <strong>Formato:</strong> CSV con separador punto y coma (;)
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB 2: Subir Archivo CSV -->
                            <div class="tab-pane fade p-3" id="subir" role="tabpanel">
                                <div class="alert alert-warning mb-3">
                                    <h6 class="alert-heading mb-2">
                                        <i class="bi bi-exclamation-triangle"></i> <strong>Formatos Importantes</strong>
                                    </h6>
                                    
                                    <div class="mb-2">
                                        <strong>📅 Fechas:</strong> <code>DD/MM/YYYY</code> o <code>D/M/YYYY</code>
                                        <br>
                                        <small>
                                            ✅ Válido: <code>13/12/2024</code>, <code>6/01/2025</code>, <code>06/01/2025</code><br>
                                            ❌ Incorrecto: <code>2024-12-13</code>, <code>13/12/24</code>
                                        </small>
                                    </div>
                                    
                                    <div>
                                        <strong>🕐 Horas:</strong> <code>HH.MM</code> (punto decimal, no dos puntos)
                                        <br>
                                        <small>
                                            ✅ Válido: <code>10.3</code> = 10:30, <code>8.15</code> = 08:15, <code>14</code> = 14:00<br>
                                            ℹ️ El decimal representa decenas de minutos
                                        </small>
                                    </div>
                                </div>
                                <div class="row justify-content-center">
                                    <div class="col-lg-8">
                                        <form id="excelUploadForm" enctype="multipart/form-data">
                                            <div class="upload-area p-4 p-md-5 rounded text-center bg-light" id="uploadArea">
                                                <i class="bi bi-cloud-arrow-up text-primary" style="font-size: 5rem;"></i>
                                                <h5 class="mt-3">Arrastra tu archivo aquí</h5>
                                                <p class="text-muted mb-3">o haz clic para seleccionar</p>
                                                <input type="file" id="excelFile" name="excel_file" accept=".csv" style="display: none;">
                                                <div class="d-flex flex-wrap justify-content-center gap-2 mt-3">
                                                    <span class="badge bg-secondary p-2">
                                                        <i class="bi bi-file-earmark-spreadsheet"></i> CSV separador ";"
                                                    </span>
                                                    <span class="badge bg-secondary p-2">
                                                        <i class="bi bi-hdd"></i> Máx. 10MB
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <!-- File preview -->
                                            <div class="file-preview" id="filePreview" style="display: none;">
                                                <div class="card border-success mt-3">
                                                    <div class="card-body">
                                                        <div class="d-flex align-items-center justify-content-between mb-3">
                                                            <div class="d-flex align-items-center">
                                                                <i class="bi bi-file-earmark-check text-success fs-2 me-3"></i>
                                                                <div>
                                                                    <strong id="fileName">archivo.csv</strong>
                                                                    <br>
                                                                    <small class="text-muted" id="fileSize">0 KB</small>
                                                                </div>
                                                            </div>
                                                            <button type="button" class="btn btn-sm btn-outline-danger" id="removeFile">
                                                                <i class="bi bi-x-lg"></i> Quitar
                                                            </button>
                                                        </div>
                                                        
                                                        <!-- Botón para procesar el archivo -->
                                                        <div class="d-grid gap-2">
                                                            <button type="button" class="btn btn-success btn-lg" id="processFileBtn">
                                                                <i class="bi bi-upload"></i> Importar Archivo al Sistema
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="progress-container mt-3" id="progressContainer" style="display: none;">
                                                <div class="progress mb-2" style="height: 30px;">
                                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                                                         role="progressbar" style="width: 0%" id="progressBar">
                                                        <span class="fw-bold" id="progressText">0%</span>
                                                    </div>
                                                </div>
                                                <div class="text-center">
                                                    <small class="text-muted">
                                                        <i class="bi bi-hourglass-split"></i> Procesando archivo...
                                                    </small>
                                                </div>
                                            </div>
                                        </form>
                                        
                                        <div class="alert alert-info mt-3">
                                            <h6 class="alert-heading"><i class="bi bi-lightbulb"></i> Consejo</h6>
                                            <p class="mb-0 small">
                                                Usa la plantilla CSV de la primera pestaña para asegurar que tu archivo 
                                                tenga el formato correcto.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB 3: Copiar y Pegar -->

                            <div class="tab-pane fade p-3" id="copiar" role="tabpanel">
                                <div class="row">
                                    <div class="col-lg-8 mx-auto">
                                        <div class="alert alert-warning mb-3">
                                            <h6 class="alert-heading">
                                                <i class="bi bi-exclamation-triangle"></i> Importante
                                            </h6>
                                            <p class="mb-0 small">
                                                NO copies la primera fila con encabezados. Solo copia las filas 
                                                que contienen los datos reales de las órdenes.
                                            </p>
                                        </div>

                                        <div class="accordion mb-3" id="instructionsAccordion">
                                            <div class="accordion-item">
                                                <h2 class="accordion-header">
                                                    <button class="accordion-button collapsed" type="button" 
                                                            data-bs-toggle="collapse" data-bs-target="#instructions">
                                                        <i class="bi bi-info-circle me-2"></i>
                                                        Ver instrucciones paso a paso
                                                    </button>
                                                </h2>
                                                <div id="instructions" class="accordion-collapse collapse" 
                                                     data-bs-parent="#instructionsAccordion">
                                                    <div class="accordion-body">
                                                        <ol class="mb-0">
                                                            <li class="mb-2">Abre tu archivo Excel con los datos</li>
                                                            <li class="mb-2">
                                                                <strong class="text-danger">Selecciona SOLO las filas de datos</strong> 
                                                                (sin encabezados)
                                                            </li>
                                                            <li class="mb-2">Copia las filas (Ctrl+C o Cmd+C)</li>
                                                            <li class="mb-2">Pega en el área de texto de abajo</li>
                                                            <li>Haz clic en "Importar Datos"</li>
                                                        </ol>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <form method="POST">
                                            <div class="mb-3">
                                                <label for="csv_data" class="form-label fw-bold">
                                                    <i class="bi bi-clipboard-data"></i> Área de Datos
                                                </label>
                                                <textarea class="form-control font-monospace" id="csv_data" 
                                                          name="csv_data" rows="12" 
                                                          placeholder="Pega aquí las filas copiadas desde Excel (sin encabezados)"></textarea>
                                                <div class="form-text">
                                                    <i class="bi bi-info-circle"></i> 
                                                    Los datos se separan automáticamente por tabuladores (Tab)
                                                </div>
                                            </div>
                                            
                                            <div class="alert alert-light">
                                                <i class="bi bi-lightbulb text-warning"></i>
                                                <strong>Consejo:</strong>
                                                Al copiar desde Excel, los datos se separan automáticamente 
                                                con el formato correcto.
                                            </div>
                                            
                                            <div class="d-grid">
                                                <button type="submit" class="btn btn-warning btn-lg">
                                                    <i class="bi bi-upload me-2"></i>Importar Datos
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
</div>

<!-- Modal de Confirmación de Duplicados (ANTES de guardar) -->
<div class="modal fade" id="duplicateConfirmModal" tabindex="-1" aria-labelledby="duplicateConfirmModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="duplicateConfirmModalLabel">
                    <i class="bi bi-exclamation-triangle-fill"></i> OCs Duplicadas Detectadas
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-3">
                    <div class="d-flex align-items-start">
                        <i class="bi bi-info-circle-fill fs-4 me-3"></i>
                        <div>
                            <h6 class="alert-heading mb-1">Se detectaron OCs que ya existen en el sistema</h6>
                            <p class="mb-0 small">
                                El archivo contiene <span id="duplicateCount" class="fw-bold">0</span> órdenes de servicio que ya están registradas.
                                <span id="duplicateChangesInfo"></span>
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Estadísticas -->
                <div class="row text-center mb-3">
                    <div class="col-4">
                        <div class="card border-success">
                            <div class="card-body py-2">
                                <h3 class="mb-0 text-success" id="confirmNewCount">0</h3>
                                <small class="text-muted">Nuevas OCs</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="card border-warning">
                            <div class="card-body py-2">
                                <h3 class="mb-0 text-warning" id="confirmDuplicateCount">0</h3>
                                <small class="text-muted">Duplicadas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="card border-info">
                            <div class="card-body py-2">
                                <h3 class="mb-0 text-info" id="confirmChangesCount">0</h3>
                                <small class="text-muted">Con Cambios</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Vista previa de cambios -->
                <div id="previewChangesContainer" style="max-height: 300px; overflow-y: auto; display: none;">
                    <!-- Se llenará dinámicamente -->
                </div>
                
                <div class="alert alert-light border mt-3">
                    <h6 class="mb-2"><i class="bi bi-question-circle"></i> ¿Qué deseas hacer?</h6>
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary btn-sm" id="btnUpdateAll">
                            <i class="bi bi-arrow-repeat"></i> Actualizar Todo
                            <small class="d-block">Guardar nuevas OCs y actualizar las duplicadas</small>
                        </button>
                        <button class="btn btn-secondary btn-sm" id="btnOnlyNew">
                            <i class="bi bi-plus-circle"></i> Solo Importar Nuevas
                            <small class="d-block">Guardar solo las nuevas, ignorar duplicadas</small>
                        </button>
                        <button class="btn btn-outline-danger btn-sm" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmación de Importación -->
<div class="modal fade" id="importSuccessModal" tabindex="-1" aria-labelledby="importSuccessModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="importSuccessModalLabel">
                    <i class="bi bi-check-circle-fill"></i> Importación Completada
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <div class="display-1 text-success mb-2">✅</div>
                    <h4 id="modalSuccessCount" class="text-success mb-1">0 registros importados</h4>
                    <p id="modalErrorCount" class="text-muted small mb-0"></p>
                    
                    <!-- Desglose: Nuevos vs Actualizados -->
                    <div id="statsBreakdown" class="mt-2" style="display: none;">
                        <span class="badge bg-success me-2">
                            <i class="bi bi-plus-circle"></i> <span id="newCount">0</span> nuevos
                        </span>
                        <span class="badge bg-info">
                            <i class="bi bi-arrow-repeat"></i> <span id="updateCount">0</span> actualizados
                        </span>
                    </div>
                </div>
                
                <!-- Lista de OCs importadas (muestra) -->
                <div id="importedOCsList" class="mb-3" style="display: none;">
                    <h6 class="text-muted mb-2">Últimas OCs importadas:</h6>
                    <div class="list-group list-group-flush" id="ocsListContainer">
                        <!-- Se llenará dinámicamente -->
                    </div>
                </div>
                
                <!-- Cambios detectados en duplicados -->
                <div id="duplicateChanges" class="mb-3" style="display: none;">
                    <div class="alert alert-info">
                        <h6 class="alert-heading mb-2">
                            <i class="bi bi-arrow-repeat"></i> Cambios Detectados en OCs Duplicadas
                        </h6>
                        <div id="changesContainer" style="max-height: 300px; overflow-y: auto;">
                            <!-- Se llenará dinámicamente -->
                        </div>
                    </div>
                </div>
                
                <!-- Detalles de errores (si hay) -->
                <div id="importErrorDetails" class="alert alert-warning small" style="display: none;">
                    <h6 class="alert-heading mb-2">⚠️ Detalles de errores:</h6>
                    <div id="errorDetailsContainer" style="max-height: 150px; overflow-y: auto;">
                        <!-- Se llenará dinámicamente -->
                    </div>
                </div>
                
                <p class="text-center text-muted small mb-0">
                    <i class="bi bi-info-circle"></i> Los datos han sido guardados en el sistema
                </p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-primary" id="btnVerOCs">
                    <i class="bi bi-list-ul"></i> Ver OCs Importadas
                </button>
                <button type="button" class="btn btn-secondary" id="btnImportarMas">
                    <i class="bi bi-cloud-upload"></i> Importar Más Datos
                </button>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Variables globales
    const fileInput = document.getElementById('excelFile'); // Corregido: debe coincidir con el ID del input en el HTML
    const uploadArea = document.getElementById('uploadArea');
    const filePreview = document.getElementById('filePreview');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const removeFile = document.getElementById('removeFile');
    const processFileBtn = document.getElementById('processFileBtn');
    const progressContainer = document.getElementById('progressContainer');
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');

    // Click en área de subida
    if (uploadArea) {
        uploadArea.addEventListener('click', () => fileInput.click());
    }

        // Drag and drop
        if (uploadArea) {
            uploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadArea.classList.add('dragover');
            });

            uploadArea.addEventListener('dragleave', () => {
                uploadArea.classList.remove('dragover');
            });

            uploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    fileInput.files = files;
                    showFilePreview(files[0]);
                }
            });
        }

        // Cambio de archivo
        if (fileInput) {
            fileInput.addEventListener('change', (e) => {
                if (e.target.files.length > 0) {
                    showFilePreview(e.target.files[0]);
                }
            });
        }

        // Remover archivo
        if (removeFile) {
            removeFile.addEventListener('click', () => {
                fileInput.value = '';
                filePreview.style.display = 'none';
                uploadArea.classList.remove('has-file');
            });
        }

        // Botón para procesar el archivo
        if (processFileBtn) {
            processFileBtn.addEventListener('click', () => {
                handleFileUpload();
            });
        }

        // Mostrar preview del archivo (SIN auto-upload)
        function showFilePreview(file) {
            fileName.textContent = file.name;
            fileSize.textContent = formatFileSize(file.size);
            filePreview.style.display = 'block';
            uploadArea.classList.add('has-file');
            
            // Ya NO hay auto-upload - el usuario debe hacer clic en el botón "Importar Archivo al Sistema"
        }

        // Formatear tamaño de archivo
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }

        // Variable global para guardar el archivo temporalmente
        let currentFile = null;
        
        // ========== HANDLE FILE UPLOAD (PASO 1: PREVIEW) ==========
        function handleFileUpload() {
            const file = fileInput.files[0];
            if (!file) return;

            // Validar tipo de archivo (solo CSV)
            if (!file.name.match(/\.csv$/i)) {
                alert('⚠️ Tipo de archivo no válido.\n\nPor favor, usa solo archivos CSV (.csv)');
                fileInput.value = '';
                filePreview.classList.remove('show');
                uploadArea.classList.remove('has-file');
                return;
            }

            // Validar tamaño (10MB)
            if (file.size > 10 * 1024 * 1024) {
                alert('⚠️ El archivo es demasiado grande.\n\nTamaño máximo: 10MB');
                fileInput.value = '';
                filePreview.classList.remove('show');
                uploadArea.classList.remove('has-file');
                return;
            }

            // Guardar archivo para usarlo después
            currentFile = file;
            
            // Mostrar progreso
            progressContainer.style.display = 'block';
            progressBar.style.width = '0%';
            if (progressText) progressText.textContent = '0%';
            
            // Actualizar mensaje de progreso
            const progressMessage = document.querySelector('#progressContainer small');
            if (progressMessage) {
                progressMessage.innerHTML = '<i class="bi bi-search"></i> Detectando duplicados...';
            }
            
            // Simular progreso
            let progress = 0;
            const progressInterval = setInterval(() => {
                progress += 5;
                if (progress <= 90) {
                    progressBar.style.width = progress + '%';
                    if (progressText) progressText.textContent = progress + '%';
                }
            }, 100);

            // Subir archivo en modo PREVIEW (solo detectar duplicados)
            const formData = new FormData();
            formData.append('excel_file', file);
            formData.append('preview_mode', 'true');
            
            fetch('procesar_excel.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                // Intentar parsear como JSON
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Response is not JSON:', text);
                        throw new Error('El servidor no devolvió una respuesta válida. Revisa la consola para más detalles.');
                    }
                });
            })
            .then(data => {
                console.log('Preview data received:', data);
                clearInterval(progressInterval);
                progressBar.style.width = '100%';
                if (progressText) progressText.textContent = '100%';
                
                setTimeout(() => {
                    progressContainer.style.display = 'none';
                    
                    if (data.success && data.preview_mode) {
                        // Verificar si hay duplicados
                        if (data.duplicates_count > 0) {
                            console.log(`Found ${data.duplicates_count} duplicates`);
                            // Mostrar modal de confirmación
                            showDuplicateConfirmModal(data);
                        } else {
                            console.log('No duplicates found, processing directly');
                            // No hay duplicados, procesar directamente
                            processFile(false); // false = no skip duplicates
                        }
                    } else {
                        console.error('Error in preview:', data);
                        alert('❌ Error: ' + (data.error || 'Error desconocido'));
                        filePreview.style.display = 'none';
                        uploadArea.classList.remove('has-file');
                    }
                }, 500);
            })
            .catch(error => {
                clearInterval(progressInterval);
                console.error('Fetch error:', error);
                alert('❌ Error al analizar el archivo.\n\nDetalles: ' + error.message + '\n\nRevisa la consola para más información.');
                progressContainer.style.display = 'none';
                filePreview.style.display = 'none';
                uploadArea.classList.remove('has-file');
            });
        }
        
        // ========== PROCESAR ARCHIVO (PASO 2: GUARDADO FINAL) ==========
        function processFile(skipDuplicates) {
            if (!currentFile) return;
            
            // Mostrar progreso
            progressContainer.style.display = 'block';
            progressBar.style.width = '0%';
            if (progressText) progressText.textContent = '0%';
            
            // Actualizar mensaje de progreso
            const progressMessage = document.querySelector('#progressContainer small');
            if (progressMessage) {
                progressMessage.innerHTML = '<i class="bi bi-hourglass-split"></i> Guardando datos...';
            }
            
            // Simular progreso
            let progress = 0;
            const progressInterval = setInterval(() => {
                progress += 5;
                if (progress <= 90) {
                    progressBar.style.width = progress + '%';
                    if (progressText) progressText.textContent = progress + '%';
                }
            }, 100);

            // Subir archivo en modo FINAL (guardar realmente)
            const formData = new FormData();
            formData.append('excel_file', currentFile);
            formData.append('preview_mode', 'false');
            formData.append('skip_duplicates', skipDuplicates ? 'true' : 'false');
            
            fetch('procesar_excel.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Process response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                // Intentar parsear como JSON
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Response is not JSON:', text);
                        throw new Error('El servidor no devolvió una respuesta válida. Revisa la consola para más detalles.');
                    }
                });
            })
            .then(data => {
                console.log('Process data received:', data);
                clearInterval(progressInterval);
                progressBar.style.width = '100%';
                if (progressText) progressText.textContent = '100%';
                
                setTimeout(() => {
                    progressContainer.style.display = 'none';
                    
                    if (data.success) {
                        console.log('Import successful:', data.success_count, 'records');
                        // Mostrar modal de éxito
                        showImportSuccessModal(data);
                        
                        // Limpiar el formulario
                        fileInput.value = '';
                        filePreview.style.display = 'none';
                        uploadArea.classList.remove('has-file');
                        currentFile = null;
                    } else {
                        console.error('Import error:', data);
                        alert('❌ Error: ' + data.error);
                        filePreview.style.display = 'none';
                        uploadArea.classList.remove('has-file');
                    }
                }, 500);
            })
            .catch(error => {
                clearInterval(progressInterval);
                console.error('Process fetch error:', error);
                alert('❌ Error al procesar el archivo.\n\nDetalles: ' + error.message + '\n\nRevisa la consola para más información.');
                progressContainer.style.display = 'none';
                filePreview.style.display = 'none';
                uploadArea.classList.remove('has-file');
            });
        }
        
        // ========== MODAL DE IMPORTACIÓN EXITOSA ==========
        function showImportSuccessModal(data) {
            // Actualizar contadores
            document.getElementById('modalSuccessCount').textContent = 
                `${data.success_count} registro${data.success_count !== 1 ? 's' : ''} procesado${data.success_count !== 1 ? 's' : ''} exitosamente`;
            
            // Mostrar desglose de nuevos vs actualizados vs omitidos
            const statsBreakdown = document.getElementById('statsBreakdown');
            if (data.new_inserts > 0 || data.updates > 0 || data.skipped_duplicates > 0) {
                document.getElementById('newCount').textContent = data.new_inserts;
                document.getElementById('updateCount').textContent = data.updates + (data.skipped_duplicates > 0 ? ` (${data.skipped_duplicates} omitidos)` : '');
                statsBreakdown.style.display = 'block';
            } else {
                statsBreakdown.style.display = 'none';
            }
            
            // Mostrar/ocultar contador de errores
            const errorCountElement = document.getElementById('modalErrorCount');
            if (data.error_count > 0) {
                errorCountElement.textContent = `❌ ${data.error_count} registro${data.error_count !== 1 ? 's' : ''} con errores`;
                errorCountElement.style.display = 'block';
            } else {
                errorCountElement.style.display = 'none';
            }
            
            // Mostrar lista de OCs importadas
            const ocsListContainer = document.getElementById('ocsListContainer');
            const importedOCsList = document.getElementById('importedOCsList');
            
            if (data.last_imported_ocs && data.last_imported_ocs.length > 0) {
                ocsListContainer.innerHTML = '';
                data.last_imported_ocs.forEach(oc => {
                    const item = document.createElement('div');
                    item.className = 'list-group-item d-flex justify-content-between align-items-start py-2';
                    item.innerHTML = `
                        <div>
                            <strong>${oc.orden_servicio}</strong>
                            <br>
                            <small class="text-muted">${oc.cliente}</small>
                        </div>
                        <span class="badge bg-success rounded-pill">✓</span>
                    `;
                    ocsListContainer.appendChild(item);
                });
                
                // Mostrar contador de más registros si hay
                if (data.success_count > data.last_imported_ocs.length) {
                    const moreItem = document.createElement('div');
                    moreItem.className = 'list-group-item text-center text-muted small py-2';
                    moreItem.textContent = `... y ${data.success_count - data.last_imported_ocs.length} más`;
                    ocsListContainer.appendChild(moreItem);
                }
                
                importedOCsList.style.display = 'block';
            } else {
                importedOCsList.style.display = 'none';
            }
            
            // Mostrar detalles de errores si hay
            const importErrorDetails = document.getElementById('importErrorDetails');
            const errorDetailsContainer = document.getElementById('errorDetailsContainer');
            
            if (data.error_details && data.error_details.length > 0) {
                errorDetailsContainer.innerHTML = '';
                data.error_details.slice(0, 10).forEach(error => {
                    const errorItem = document.createElement('div');
                    errorItem.className = 'mb-1';
                    errorItem.textContent = error;
                    errorDetailsContainer.appendChild(errorItem);
                });
                
                if (data.error_details.length > 10) {
                    const moreErrors = document.createElement('div');
                    moreErrors.className = 'text-muted small mt-2';
                    moreErrors.textContent = `... y ${data.error_details.length - 10} errores más`;
                    errorDetailsContainer.appendChild(moreErrors);
                }
                
                importErrorDetails.style.display = 'block';
            } else {
                importErrorDetails.style.display = 'none';
            }
            
            // Mostrar cambios detectados en duplicados
            const duplicateChanges = document.getElementById('duplicateChanges');
            const changesContainer = document.getElementById('changesContainer');
            
            if (data.duplicates_with_changes && data.duplicates_with_changes.length > 0) {
                changesContainer.innerHTML = '';
                
                data.duplicates_with_changes.forEach(oc => {
                    const ocCard = document.createElement('div');
                    ocCard.className = 'card mb-2';
                    ocCard.innerHTML = `
                        <div class="card-header bg-light py-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>${oc.orden_servicio}</strong>
                                    <small class="text-muted ms-2">${oc.cliente}</small>
                                </div>
                                <span class="badge bg-info">${oc.change_count} cambio${oc.change_count !== 1 ? 's' : ''}</span>
                            </div>
                        </div>
                        <div class="card-body p-2">
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless mb-0">
                                    <thead>
                                        <tr class="small text-muted">
                                            <th width="30%">Campo</th>
                                            <th width="35%">Valor Anterior</th>
                                            <th width="35%">Valor Nuevo</th>
                                        </tr>
                                    </thead>
                                    <tbody class="small">
                                        ${oc.changes.map(change => `
                                            <tr>
                                                <td><strong>${change.field}</strong></td>
                                                <td class="text-danger">
                                                    <del>${truncateText(change.old, 30)}</del>
                                                </td>
                                                <td class="text-success">
                                                    ${truncateText(change.new, 30)}
                                                </td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    `;
                    changesContainer.appendChild(ocCard);
                });
                
                duplicateChanges.style.display = 'block';
            } else {
                duplicateChanges.style.display = 'none';
            }
            
            // Mostrar el modal
            const modal = new bootstrap.Modal(document.getElementById('importSuccessModal'));
            modal.show();
        }
        
        // Función helper para truncar texto largo
        function truncateText(text, maxLength) {
            if (text.length <= maxLength) return text;
            return text.substring(0, maxLength) + '...';
        }
        
        // Event listeners para los botones del modal
        document.getElementById('btnVerOCs').addEventListener('click', function() {
            // Redirigir a listar_oc.php con filtro de fecha actual
            const today = new Date().toISOString().split('T')[0];
            window.location.href = 'listar_oc.php?fecha_desde=' + today;
        });
        
        document.getElementById('btnImportarMas').addEventListener('click', function() {
            // Recargar la página para importar más
            location.reload();
        });
        
        // ========== MODAL DE CONFIRMACIÓN DE DUPLICADOS ==========
        function showDuplicateConfirmModal(data) {
            // Actualizar contadores
            document.getElementById('duplicateCount').textContent = data.duplicates_count;
            document.getElementById('confirmNewCount').textContent = data.new_count;
            document.getElementById('confirmDuplicateCount').textContent = data.duplicates_count;
            
            const withChanges = data.duplicates_with_changes ? Object.keys(data.duplicates_with_changes).length : 0;
            document.getElementById('confirmChangesCount').textContent = withChanges;
            
            // Mostrar información sobre cambios
            const changesInfo = document.getElementById('duplicateChangesInfo');
            if (withChanges > 0) {
                changesInfo.innerHTML = `<br><strong>${withChanges} de ellas tienen cambios</strong> que se aplicarán si decides actualizar.`;
            } else {
                changesInfo.innerHTML = `<br>Ninguna tiene cambios (son idénticas a las existentes).`;
            }
            
            // Mostrar vista previa de cambios si hay
            const previewContainer = document.getElementById('previewChangesContainer');
            if (withChanges > 0 && data.duplicates_with_changes) {
                previewContainer.innerHTML = '<h6 class="text-muted mb-2">Vista previa de cambios:</h6>';
                
                const duplicatesArray = Object.values(data.duplicates_with_changes);
                duplicatesArray.slice(0, 5).forEach(oc => {
                    if (oc.has_changes) {
                        const card = document.createElement('div');
                        card.className = 'card mb-2';
                        card.innerHTML = `
                            <div class="card-header bg-light py-1 px-2">
                                <small>
                                    <strong>${oc.orden_servicio}</strong> - ${oc.cliente}
                                    <span class="badge bg-info ms-2">${oc.change_count} cambio${oc.change_count !== 1 ? 's' : ''}</span>
                                </small>
                            </div>
                            <div class="card-body p-2">
                                <small>
                                    ${oc.changes.slice(0, 3).map(c => `
                                        <div class="mb-1">
                                            <strong>${c.field}:</strong> 
                                            <span class="text-danger"><del>${truncateText(c.old, 20)}</del></span> 
                                            → 
                                            <span class="text-success">${truncateText(c.new, 20)}</span>
                                        </div>
                                    `).join('')}
                                    ${oc.changes.length > 3 ? `<div class="text-muted">... y ${oc.changes.length - 3} más</div>` : ''}
                                </small>
                            </div>
                        `;
                        previewContainer.appendChild(card);
                    }
                });
                
                if (duplicatesArray.length > 5) {
                    const moreInfo = document.createElement('div');
                    moreInfo.className = 'text-center text-muted small mt-2';
                    moreInfo.textContent = `... y ${duplicatesArray.length - 5} OCs duplicadas más`;
                    previewContainer.appendChild(moreInfo);
                }
                
                previewContainer.style.display = 'block';
            } else {
                previewContainer.style.display = 'none';
            }
            
            // Mostrar el modal
            const modal = new bootstrap.Modal(document.getElementById('duplicateConfirmModal'));
            modal.show();
        }
        
        // Event listeners para los botones del modal de confirmación
        document.getElementById('btnUpdateAll').addEventListener('click', function() {
            // Cerrar modal y procesar con actualización
            bootstrap.Modal.getInstance(document.getElementById('duplicateConfirmModal')).hide();
            processFile(false); // false = actualizar duplicados
        });
        
        document.getElementById('btnOnlyNew').addEventListener('click', function() {
            // Cerrar modal y procesar solo nuevos
            bootstrap.Modal.getInstance(document.getElementById('duplicateConfirmModal')).hide();
            processFile(true); // true = skip duplicados
        });
    </script>

<?php require_once '../includes/footer.php'; ?>