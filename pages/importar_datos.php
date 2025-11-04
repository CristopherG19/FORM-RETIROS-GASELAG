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
                
                // Convertir fechas
                $fecha_os = !empty($data[2]) ? date('Y-m-d', strtotime(str_replace('/', '-', $data[2]))) : null;
                $prog_dia_retiro = !empty($data[5]) ? date('Y-m-d', strtotime(str_replace('/', '-', $data[5]))) : null;
                $prog_dia_vp = !empty($data[7]) ? date('Y-m-d', strtotime(str_replace('/', '-', $data[7]))) : null;
                
                try {
                    $stmt->execute([
                        trim($data[0]),  // item
                        trim($data[1]),  // orden_servicio
                        $fecha_os,       // fecha_os
                        intval($data[3]), // cantidad_medidores
                        trim($data[4]),  // tipo_servicio
                        $prog_dia_retiro, // programacion_dia_retiro
                        trim($data[6]),  // programacion_hora_retiro
                        $prog_dia_vp,    // programacion_dia_vp
                        trim($data[8]),  // programacion_hora_vp
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
                <p class="text-muted mb-0">Carga masiva de datos desde Excel o CSV</p>
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
                        <div class="card border-0 shadow-sm bg-primary text-white stat-card">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center">
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
                        <div class="card border-0 shadow-sm bg-success text-white stat-card">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center">
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
                        <div class="card border-0 shadow-sm bg-info text-white stat-card">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center">
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

        // ========== HANDLE FILE UPLOAD ==========
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

            // Mostrar progreso
            progressContainer.style.display = 'block';
            progressBar.style.width = '0%';
            if (progressText) progressText.textContent = '0%';
            
            // Simular progreso
            let progress = 0;
            const progressInterval = setInterval(() => {
                progress += 5;
                if (progress <= 90) {
                    progressBar.style.width = progress + '%';
                    if (progressText) progressText.textContent = progress + '%';
                }
            }, 100);

            // Subir archivo
            const formData = new FormData();
            formData.append('excel_file', file);
            
            fetch('procesar_excel.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                clearInterval(progressInterval);
                progressBar.style.width = '100%';
                if (progressText) progressText.textContent = '100%';
                
                setTimeout(() => {
                    progressContainer.style.display = 'none';
                    
                    if (data.success) {
                        let message = '✅ ¡Importación completada!\n\n';
                        message += `📊 Registros importados: ${data.success_count}\n`;
                        message += `❌ Errores: ${data.error_count}\n\n`;
                        
                        if (data.debug_info) {
                            message += `ℹ️ Información adicional:\n`;
                            message += `  • Filas procesadas: ${data.debug_info.total_rows_processed}\n`;
                            message += `  • Columnas esperadas: ${data.debug_info.expected_columns}\n`;
                            message += `  • Columnas encontradas: ${data.debug_info.actual_columns_in_header}\n\n`;
                        }
                        
                        if (data.error_details && data.error_details.length > 0) {
                            message += `⚠️ Detalles de errores:\n`;
                            message += data.error_details.slice(0, 5).join('\n');
                            if (data.error_details.length > 5) {
                                message += `\n... y ${data.error_details.length - 5} errores más`;
                            }
                        }
                        
                        alert(message);
                        location.reload();
                    } else {
                        alert('❌ Error: ' + data.error);
                        filePreview.classList.remove('show');
                        uploadArea.classList.remove('has-file');
                    }
                }, 500);
            })
            .catch(error => {
                clearInterval(progressInterval);
                console.error('Error:', error);
                alert('❌ Error al procesar el archivo.\n\nPor favor, intenta nuevamente.');
                progressContainer.style.display = 'none';
                filePreview.classList.remove('show');
                uploadArea.classList.remove('has-file');
            });
        }
    </script>

<?php require_once '../includes/footer.php'; ?>