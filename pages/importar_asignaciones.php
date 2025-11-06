<?php
require_once '../config/database.php';

// Solo administradores
requireRole(['admin']);

$pageTitle = 'Importar Asignaciones desde Excel - Sistema GASELAG';
require_once '../includes/header.php';

$message = '';
$messageType = '';
$preview = null;
$errores = [];

// Procesar archivo subido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_asignaciones'])) {
    $archivo = $_FILES['archivo_asignaciones'];
    
    if ($archivo['error'] === UPLOAD_ERR_OK) {
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        
        if ($extension === 'csv') {
            // Leer archivo CSV
            $contenido = [];
            if (($handle = fopen($archivo['tmp_name'], "r")) !== FALSE) {
                // Saltar primera fila (encabezados)
                $primeraFila = fgetcsv($handle, 1000, ";");
                if (!$primeraFila) {
                    $primeraFila = fgetcsv($handle, 1000, ",");
                    rewind($handle);
                    fgetcsv($handle, 1000, ",");
                }
                
                $lineNumber = 1;
                while (($data = fgetcsv($handle, 1000, ";")) !== FALSE || ($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    if (count($data) >= 2 && !empty($data[0])) {
                        $oc = trim($data[0]);
                        $username = trim($data[1]);
                        $notas = isset($data[2]) ? trim($data[2]) : '';
                        
                        // Normalizar OC (agregar prefijo si no tiene)
                        if (!str_starts_with(strtoupper($oc), 'OC-')) {
                            $oc = 'OC-' . $oc;
                        }
                        
                        $contenido[] = [
                            'linea' => $lineNumber,
                            'oc' => $oc,
                            'username' => $username,
                            'notas' => $notas
                        ];
                        $lineNumber++;
                    }
                }
                fclose($handle);
            }
            
            // Validar contenido
            if (empty($contenido)) {
                $message = 'El archivo está vacío o no tiene el formato correcto';
                $messageType = 'danger';
            } else {
                // Validar cada línea
                $pdo = getConnection();
                $validadas = [];
                $errores = [];
                
                foreach ($contenido as $item) {
                    $error = null;
                    
                    // Validar que la OC existe
                    $stmtOC = $pdo->prepare("SELECT id, orden_servicio, cliente, direccion FROM ordenes_servicio WHERE orden_servicio = ?");
                    $stmtOC->execute([$item['oc']]);
                    $ocData = $stmtOC->fetch();
                    
                    if (!$ocData) {
                        $error = "OC no existe en el sistema";
                    } else {
                        // Validar que no esté registrada
                        $stmtRetiro = $pdo->prepare("SELECT id FROM retiros_medidores WHERE orden_servicio = ? AND estado_registro = 'activo'");
                        $stmtRetiro->execute([$item['oc']]);
                        if ($stmtRetiro->fetch()) {
                            $error = "OC ya tiene retiro registrado";
                        }
                        
                        // Validar que no esté asignada
                        if (!$error) {
                            $stmtAsig = $pdo->prepare("SELECT id FROM asignaciones_oc WHERE orden_servicio = ? AND estado IN ('pendiente', 'en_proceso')");
                            $stmtAsig->execute([$item['oc']]);
                            if ($stmtAsig->fetch()) {
                                $error = "OC ya está asignada";
                            }
                        }
                        
                        // Validar técnico
                        if (!$error) {
                            $stmtTec = $pdo->prepare("SELECT id, nombre_completo FROM usuarios WHERE username = ? AND rol = 'user' AND estado = 'activo'");
                            $stmtTec->execute([$item['username']]);
                            $tecnicoData = $stmtTec->fetch();
                            
                            if (!$tecnicoData) {
                                $error = "Técnico no existe o no está activo";
                            } else {
                                $item['tecnico_id'] = $tecnicoData['id'];
                                $item['tecnico_nombre'] = $tecnicoData['nombre_completo'];
                                $item['oc_data'] = $ocData;
                            }
                        }
                    }
                    
                    if ($error) {
                        $errores[] = [
                            'linea' => $item['linea'],
                            'oc' => $item['oc'],
                            'username' => $item['username'],
                            'error' => $error
                        ];
                    } else {
                        $validadas[] = $item;
                    }
                }
                
                if (!empty($validadas)) {
                    $preview = $validadas;
                    $_SESSION['preview_asignaciones'] = $validadas;
                    $message = count($validadas) . " asignaciones válidas listas para procesar";
                    if (!empty($errores)) {
                        $message .= " (" . count($errores) . " con errores)";
                    }
                    $messageType = empty($errores) ? 'success' : 'warning';
                } else {
                    $message = 'No se encontraron asignaciones válidas';
                    $messageType = 'danger';
                }
            }
        } else {
            $message = 'Solo se aceptan archivos CSV';
            $messageType = 'danger';
        }
    } else {
        $message = 'Error al subir el archivo';
        $messageType = 'danger';
    }
}

// Confirmar asignaciones
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_asignaciones'])) {
    if (isset($_SESSION['preview_asignaciones'])) {
        $asignaciones = $_SESSION['preview_asignaciones'];
        $exitosas = 0;
        $fallidas = 0;
        
        foreach ($asignaciones as $item) {
            $resultado = asignarOCATecnico(
                $item['oc'],
                $item['tecnico_id'],
                $_SESSION['user_id'],
                $item['notas']
            );
            
            if ($resultado['success']) {
                $exitosas++;
            } else {
                $fallidas++;
            }
        }
        
        unset($_SESSION['preview_asignaciones']);
        
        $message = "✅ Asignación completada: {$exitosas} OCs asignadas exitosamente";
        if ($fallidas > 0) {
            $message .= ". {$fallidas} OCs fallaron";
        }
        $messageType = $fallidas > 0 ? 'warning' : 'success';
        $preview = null;
    }
}
?>

<style>
.upload-zone {
    border: 3px dashed #0d6efd;
    border-radius: 15px;
    padding: 40px;
    text-align: center;
    background: #f8f9fa;
    transition: all 0.3s ease;
    cursor: pointer;
}
.upload-zone:hover {
    border-color: #0a58ca;
    background: #e7f1ff;
}
.upload-zone.dragover {
    border-color: #198754;
    background: #d1e7dd;
}
.preview-table {
    font-size: 0.9rem;
}
.error-row {
    background-color: #f8d7da;
}
.success-row {
    background-color: #d1e7dd;
}
</style>

<div class="container-fluid px-4 py-4">
    
    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
            <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : ($messageType === 'warning' ? 'exclamation-triangle' : 'x-circle') ?>"></i>
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Título -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="card-title">
                <i class="bi bi-file-earmark-excel text-success"></i>
                Importar Asignaciones desde Excel
            </h2>
            <p class="text-muted mb-0">
                Sube un archivo CSV con las OCs y los técnicos para asignarlas masivamente
            </p>
        </div>
    </div>

    <div class="row">
        <!-- Columna izquierda: Instrucciones y upload -->
        <div class="col-lg-6 mb-4">
            
            <!-- Instrucciones -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-info-circle-fill"></i> Instrucciones
                    </h5>
                </div>
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Formato del archivo CSV:</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Columna A</th>
                                    <th>Columna B</th>
                                    <th>Columna C (opcional)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>Número OC</code></td>
                                    <td><code>Username Técnico</code></td>
                                    <td><code>Notas</code></td>
                                </tr>
                                <tr>
                                    <td>OC-73772</td>
                                    <td>12345678</td>
                                    <td>Urgente</td>
                                </tr>
                                <tr>
                                    <td>73773</td>
                                    <td>87654321</td>
                                    <td>Coordinar visita</td>
                                </tr>
                                <tr>
                                    <td>OC-73774</td>
                                    <td>12345678</td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="alert alert-warning border-0 mt-3">
                        <small>
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <strong>Importante:</strong>
                            <ul class="mb-0 mt-2">
                                <li>La primera fila debe contener los encabezados</li>
                                <li>El prefijo "OC-" es opcional (se agrega automáticamente)</li>
                                <li>Usa punto y coma (;) o coma (,) como separador</li>
                                <li>Guarda el archivo como CSV desde Excel</li>
                            </ul>
                        </small>
                    </div>
                    
                    <div class="d-grid mt-3">
                        <a href="descargar_plantilla_asignaciones.php" class="btn btn-outline-success">
                            <i class="bi bi-download"></i> Descargar Plantilla CSV
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna derecha: Upload y preview -->
        <div class="col-lg-6 mb-4">
            
            <?php if (!$preview): ?>
            <!-- Zona de carga -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-cloud-upload-fill"></i> Subir Archivo
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" id="uploadForm">
                        <div class="upload-zone" id="uploadZone">
                            <i class="bi bi-cloud-arrow-up text-primary" style="font-size: 4rem;"></i>
                            <h5 class="mt-3">Arrastra tu archivo CSV aquí</h5>
                            <p class="text-muted">o haz click para seleccionar</p>
                            <input type="file" name="archivo_asignaciones" id="archivo_asignaciones" 
                                   accept=".csv" class="d-none" required>
                            <button type="button" class="btn btn-primary mt-2" onclick="document.getElementById('archivo_asignaciones').click()">
                                <i class="bi bi-folder2-open"></i> Seleccionar Archivo
                            </button>
                        </div>
                        
                        <div id="fileInfo" class="mt-3 d-none">
                            <div class="alert alert-info border-0">
                                <i class="bi bi-file-earmark-text"></i>
                                <strong>Archivo seleccionado:</strong>
                                <span id="fileName"></span>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 mt-3">
                            <button type="submit" class="btn btn-success btn-lg" id="btnProcesar" disabled>
                                <i class="bi bi-check-circle-fill"></i> Procesar Archivo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Vista previa de asignaciones -->
    <?php if ($preview): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">
                <i class="bi bi-eye-fill"></i> Vista Previa de Asignaciones
            </h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info border-0 mb-3">
                <i class="bi bi-info-circle-fill me-2"></i>
                Se asignarán <strong><?= count($preview) ?> OCs</strong> a los técnicos correspondientes
            </div>
            
            <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                <table class="table table-sm table-hover preview-table">
                    <thead class="table-dark sticky-top">
                        <tr>
                            <th>#</th>
                            <th>OC</th>
                            <th>Cliente</th>
                            <th>Técnico</th>
                            <th>Notas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($preview as $index => $item): ?>
                        <tr class="success-row">
                            <td><?= $index + 1 ?></td>
                            <td><strong><?= htmlspecialchars($item['oc']) ?></strong></td>
                            <td><small><?= htmlspecialchars($item['oc_data']['cliente']) ?></small></td>
                            <td>
                                <span class="badge bg-primary">
                                    <?= htmlspecialchars($item['tecnico_nombre']) ?>
                                </span>
                            </td>
                            <td><small><?= htmlspecialchars($item['notas']) ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <form method="POST" action="" class="mt-3">
                <div class="d-grid gap-2">
                    <button type="submit" name="confirmar_asignaciones" class="btn btn-success btn-lg">
                        <i class="bi bi-check-circle-fill"></i> Confirmar y Asignar Todas
                    </button>
                    <a href="importar_asignaciones.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Cancelar y Subir Otro Archivo
                    </a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Errores encontrados -->
    <?php if (!empty($errores)): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0">
                <i class="bi bi-exclamation-triangle-fill"></i> Errores Encontrados (<?= count($errores) ?>)
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                <table class="table table-sm preview-table">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th>Línea</th>
                            <th>OC</th>
                            <th>Username</th>
                            <th>Error</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($errores as $error): ?>
                        <tr class="error-row">
                            <td><?= $error['linea'] ?></td>
                            <td><?= htmlspecialchars($error['oc']) ?></td>
                            <td><?= htmlspecialchars($error['username']) ?></td>
                            <td><small><?= htmlspecialchars($error['error']) ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Manejo de drag & drop
const uploadZone = document.getElementById('uploadZone');
const fileInput = document.getElementById('archivo_asignaciones');
const fileInfo = document.getElementById('fileInfo');
const fileName = document.getElementById('fileName');
const btnProcesar = document.getElementById('btnProcesar');

// Prevenir comportamiento por defecto
['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    uploadZone.addEventListener(eventName, preventDefaults, false);
});

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

// Highlight en drag over
['dragenter', 'dragover'].forEach(eventName => {
    uploadZone.addEventListener(eventName, () => {
        uploadZone.classList.add('dragover');
    }, false);
});

['dragleave', 'drop'].forEach(eventName => {
    uploadZone.addEventListener(eventName, () => {
        uploadZone.classList.remove('dragover');
    }, false);
});

// Manejar drop
uploadZone.addEventListener('drop', (e) => {
    const dt = e.dataTransfer;
    const files = dt.files;
    
    if (files.length > 0) {
        fileInput.files = files;
        mostrarInfoArchivo(files[0]);
    }
}, false);

// Manejar selección manual
fileInput.addEventListener('change', (e) => {
    if (e.target.files.length > 0) {
        mostrarInfoArchivo(e.target.files[0]);
    }
});

function mostrarInfoArchivo(file) {
    fileName.textContent = file.name;
    fileInfo.classList.remove('d-none');
    btnProcesar.disabled = false;
}
</script>

<?php require_once '../includes/footer.php'; ?>

