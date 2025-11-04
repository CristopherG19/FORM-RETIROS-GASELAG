<?php
require_once '../config/database.php';

// Verificar autenticación y rol de administrador
requireRole(['admin']);

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
            $params = array_map('trim', $data);
            
            if ($stmt->execute($params)) {
                $success++;
            } else {
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

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Datos - GASELAG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .upload-area {
            border: 2px dashed #007bff;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            background: #f8f9fa;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .upload-area:hover {
            border-color: #0056b3;
            background: #e3f2fd;
        }
        .upload-area.dragover {
            border-color: #28a745;
            background: #d4edda;
        }
        .method-card {
            transition: box-shadow 0.2s ease;
        }
        .method-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .progress-container {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="../index.php">
                                <i class="bi bi-house"></i> Inicio
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="buscar_oc.php">
                                <i class="bi bi-search"></i> Buscar OC
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="consultar_retiros.php">
                                <i class="bi bi-list-ul"></i> Consultar Retiros
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="importar_datos.php">
                                <i class="bi bi-upload"></i> Importar Datos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="exportar_excel.php">
                                <i class="bi bi-download"></i> Exportar Excel
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../logout.php">
                                <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="bi bi-upload"></i> Importar Datos de Órdenes de Servicio
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="../index.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Volver al Inicio
                        </a>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType === 'error' ? 'danger' : ($messageType === 'warning' ? 'warning' : 'success'); ?> alert-dismissible fade show">
                        <i class="bi bi-<?php echo $messageType === 'error' ? 'exclamation-triangle' : ($messageType === 'warning' ? 'exclamation-circle' : 'check-circle'); ?>"></i>
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Total de Registros</h5>
                                        <h2 class="mb-0"><?php echo number_format($totalRegistros); ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-database fs-1"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Sistema Actualizado</h5>
                                        <p class="mb-0">Importación mejorada disponible</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-check-circle fs-1"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Métodos de Importación -->
                <div class="row">
                    <!-- Método 1: Plantilla Excel (Recomendado) -->
                    <div class="col-md-6 mb-4">
                        <div class="card method-card h-100">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-file-earmark-excel"></i> Método Recomendado: Plantilla Excel
                                </h5>
                            </div>
                            <div class="card-body">
                                <p class="card-text">Descarga una plantilla Excel preconfigurada, completa los datos y súbela al sistema.</p>
                                
                                <div class="mb-3">
                                    <h6>Pasos:</h6>
                                    <ol class="small">
                                        <li>Descarga la plantilla Excel</li>
                                        <li>Completa los datos en la plantilla</li>
                                        <li>Sube el archivo completado</li>
                                        <li>Revisa los resultados</li>
                                    </ol>
                                </div>

                                <div class="d-grid gap-2">
                                    <a href="descargar_plantilla.php" class="btn btn-success">
                                        <i class="bi bi-download"></i> Descargar Plantilla Excel
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Método 2: Subir Archivo Excel -->
                    <div class="col-md-6 mb-4">
                        <div class="card method-card h-100">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-cloud-upload"></i> Subir Archivo Excel
                                </h5>
                            </div>
                            <div class="card-body">
                                <p class="card-text">Sube directamente un archivo Excel (.xlsx) o CSV (.csv) con los datos.</p>
                                
                                <form id="excelUploadForm" enctype="multipart/form-data">
                                    <div class="upload-area" id="uploadArea">
                                        <i class="bi bi-cloud-upload fs-1 text-muted"></i>
                                        <h5 class="mt-3">Arrastra tu archivo aquí</h5>
                                        <p class="text-muted">o haz clic para seleccionar</p>
                                        <input type="file" id="excelFile" name="excel_file" accept=".xlsx,.csv" style="display: none;">
                                        <div class="mt-2">
                                            <small class="text-muted">Formatos soportados: .xlsx, .csv (máx. 10MB)</small>
                                        </div>
                                    </div>
                                    
                                    <div class="progress-container mt-3">
                                        <div class="progress">
                                            <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                        </div>
                                        <small class="text-muted">Procesando archivo...</small>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Método 3: Copiar y Pegar (Método Anterior) -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0">
                                    <i class="bi bi-clipboard"></i> Método Alternativo: Copiar y Pegar
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <h6><i class="bi bi-info-circle"></i> Instrucciones:</h6>
                                    <ol>
                                        <li>Abra su archivo Excel con los datos de las órdenes de servicio</li>
                                        <li><strong class="text-danger">Seleccione SOLO las filas de datos (NO incluya la fila de encabezados)</strong></li>
                                        <li>Copie las filas seleccionadas (Ctrl+C o Cmd+C)</li>
                                        <li>Pegue los datos en el área de texto a continuación</li>
                                        <li>Haga clic en "Importar Datos"</li>
                                    </ol>
                                    
                                    <div class="alert alert-warning mt-3">
                                        <strong><i class="bi bi-exclamation-triangle"></i> Importante:</strong>
                                        NO copie la primera fila (encabezados como "Item", "Orden de servicio", etc.). 
                                        Solo copie las filas con los datos.
                                    </div>
                                    
                                    <p><strong>Total de registros en base de datos: <?php echo number_format($totalRegistros); ?></strong></p>
                                </div>

                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="csv_data" class="form-label">Pegue las filas de datos del Excel aquí:</label>
                                        <textarea class="form-control" id="csv_data" name="csv_data" rows="10" 
                                                  placeholder="Pegue aquí SOLO las filas de datos (SIN encabezados)&#10;&#10;Ejemplo:&#10;00001	OC-00001	2024-12-13	1	Reclamo	6/01/2025	08:00	7/01/2025	10:00	ABC123	Cliente Ejemplo	Centro 001	REM001	Juan Pérez	Calle Principal 123	CUS001	CUP001	5367165	EA22282911	Marca Ejemplo	Modelo 001	2023	Fabricante Ejemplo	Nacional	Residencial	15	2.5	R160	10	50	1.5	Cert001	CERT001"></textarea>
                                    </div>
                                    
                                    <div class="alert alert-light">
                                        <i class="bi bi-lightbulb"></i> <strong>Recuerda:</strong>
                                        Copiar directamente desde Excel sin la fila de encabezados. 
                                        Los datos deben estar separados por tabuladores.
                                    </div>
                                    
                                    <button type="submit" class="btn btn-warning btn-lg w-100">
                                        <i class="bi bi-upload"></i> Importar Datos
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Funcionalidad de subida de archivos
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('excelFile');
        const form = document.getElementById('excelUploadForm');
        const progressContainer = document.querySelector('.progress-container');
        const progressBar = document.querySelector('.progress-bar');

        // Click en área de subida
        uploadArea.addEventListener('click', () => fileInput.click());

        // Drag and drop
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
                handleFileUpload();
            }
        });

        // Cambio de archivo
        fileInput.addEventListener('change', handleFileUpload);

        function handleFileUpload() {
            const file = fileInput.files[0];
            if (!file) return;

            // Validar tipo de archivo
            const allowedTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv'];
            if (!allowedTypes.includes(file.type) && !file.name.match(/\.(xlsx|csv)$/i)) {
                alert('Tipo de archivo no válido. Use Excel (.xlsx) o CSV (.csv)');
                return;
            }

            // Validar tamaño (10MB)
            if (file.size > 10 * 1024 * 1024) {
                alert('El archivo es demasiado grande. Máximo 10MB');
                return;
            }

            // Mostrar progreso
            progressContainer.style.display = 'block';
            progressBar.style.width = '0%';

            // Subir archivo
            const formData = new FormData();
            formData.append('excel_file', file);

            fetch('procesar_excel.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                progressBar.style.width = '100%';
                
                setTimeout(() => {
                    if (data.success) {
                        alert(`Importación exitosa!\n\nRegistros importados: ${data.success_count}\nErrores: ${data.error_count}`);
                        location.reload();
                    } else {
                        alert(`Error en la importación:\n\n${data.error}`);
                    }
                    progressContainer.style.display = 'none';
                }, 500);
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al procesar el archivo');
                progressContainer.style.display = 'none';
            });
        }
    </script>
</body>
</html>

