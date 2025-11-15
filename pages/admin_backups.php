<?php
require_once '../config/database.php';
requireRole(['admin']);

$pageTitle = 'Administracion de Backups - Sistema GASELAG';
require_once '../includes/header.php';

// Configuracion
$backupPath = dirname(__DIR__) . '/backups';
$scriptsPath = $backupPath . '/scripts';

// Funcion para obtener lista de backups
function getBackupList($type, $period) {
    global $backupPath;
    
    $dir = "$backupPath/$type/$period";
    
    if (!is_dir($dir)) {
        return [];
    }
    
    $files = glob($dir . "/*.{zip,sql}", GLOB_BRACE);
    $backups = [];
    
    foreach ($files as $file) {
        $backups[] = [
            'name' => basename($file),
            'path' => $file,
            'size' => filesize($file),
            'date' => filemtime($file),
            'type' => $type,
            'period' => $period
        ];
    }
    
    // Ordenar por fecha (mas recientes primero)
    usort($backups, function($a, $b) {
        return $b['date'] - $a['date'];
    });
    
    return $backups;
}

// Funcion para obtener estadisticas
function getBackupStats() {
    global $backupPath;
    
    $stats = [
        'total_files' => 0,
        'total_size' => 0,
        'database' => ['count' => 0, 'size' => 0],
        'uploads' => ['count' => 0, 'size' => 0],
        'last_backup' => null
    ];
    
    $types = ['database', 'uploads_backup'];
    $periods = ['daily', 'weekly', 'monthly'];
    
    $lastBackupTime = 0;
    
    foreach ($types as $type) {
        foreach ($periods as $period) {
            $dir = "$backupPath/$type/$period";
            
            if (is_dir($dir)) {
                $files = glob($dir . "/*.{zip,sql}", GLOB_BRACE);
                
                foreach ($files as $file) {
                    $size = filesize($file);
                    $time = filemtime($file);
                    
                    $stats['total_files']++;
                    $stats['total_size'] += $size;
                    
                    $key = $type == 'database' ? 'database' : 'uploads';
                    $stats[$key]['count']++;
                    $stats[$key]['size'] += $size;
                    
                    if ($time > $lastBackupTime) {
                        $lastBackupTime = $time;
                        $stats['last_backup'] = date('d/m/Y H:i:s', $time);
                    }
                }
            }
        }
    }
    
    return $stats;
}

// Obtener estadisticas
$stats = getBackupStats();

// Obtener backups por tipo
$databaseBackups = [];
$uploadsBackups = [];

foreach (['daily', 'weekly', 'monthly'] as $period) {
    $databaseBackups[$period] = getBackupList('database', $period);
    $uploadsBackups[$period] = getBackupList('uploads_backup', $period);
}

// Funcion auxiliar para formatear tamano
function formatSize($bytes) {
    if ($bytes >= 1073741824) {
        return round($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return round($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return round($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' B';
    }
}
?>


<div class="container-fluid mt-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-shield-check me-2"></i>Administracion de Backups</h2>
            <p class="text-muted">Gestion centralizada de backups automaticos</p>
        </div>
    </div>

    <!-- Informacion sobre Google Drive -->
    <div class="alert alert-info border-start border-info border-4 shadow-sm">
        <i class="bi bi-info-circle-fill me-2"></i>
        <strong>Google Drive Sync:</strong> Los backups se sincronizan automaticamente con Google Drive Desktop.
        Asegurate de que la carpeta <code>backups/</code> este dentro de tu carpeta de Google Drive.
    </div>

    <!-- Estadisticas -->
    <div class="row g-3 mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">BACKUPS TOTALES</h6>
                            <h2 class="mb-0"><?php echo $stats['total_files']; ?></h2>
                        </div>
                        <i class="bi bi-database text-primary" style="font-size: 2.5rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">ESPACIO USADO</h6>
                            <h2 class="mb-0"><?php echo formatSize($stats['total_size']); ?></h2>
                        </div>
                        <i class="bi bi-hdd text-primary" style="font-size: 2.5rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">ÚLTIMO BACKUP</h6>
                            <h2 class="mb-0 fs-6"><?php echo $stats['last_backup'] ?? 'N/A'; ?></h2>
                        </div>
                        <i class="bi bi-clock-history text-primary" style="font-size: 2.5rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">ESTADO</h6>
                            <h2 class="mb-0">ACTIVO</h2>
                        </div>
                        <i class="bi bi-check-circle text-success" style="font-size: 2.5rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Botones de accion -->
    <div class="d-flex flex-wrap gap-2 mb-4">
        <button class="btn btn-success shadow-sm" onclick="runBackup('all')">
            <i class="bi bi-play-circle-fill me-1"></i> Ejecutar Backup Completo
        </button>
        <button class="btn btn-primary shadow-sm" onclick="runBackup('database')">
            <i class="bi bi-database-fill me-1"></i> Solo Base de Datos
        </button>
        <button class="btn btn-info shadow-sm" onclick="runBackup('uploads')">
            <i class="bi bi-image-fill me-1"></i> Solo Archivos
        </button>
        <button class="btn btn-warning shadow-sm" onclick="cleanOldBackups()">
            <i class="bi bi-trash-fill me-1"></i> Limpiar Antiguos
        </button>
        <a href="../backups/logs/" class="btn btn-secondary shadow-sm" target="_blank">
            <i class="bi bi-file-text-fill me-1"></i> Ver Logs
        </a>
    </div>

    <!-- Tabs de backups -->
    <ul class="nav nav-tabs nav-fill shadow-sm bg-white rounded-top" role="tablist">
        <li class="nav-item">
            <a class="nav-link active fw-semibold" data-bs-toggle="tab" href="#database-tab">
                <i class="bi bi-database-fill me-2"></i> Base de Datos
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link fw-semibold" data-bs-toggle="tab" href="#uploads-tab">
                <i class="bi bi-image-fill me-2"></i> Archivos Subidos
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link fw-semibold" data-bs-toggle="tab" href="#config-tab">
                <i class="bi bi-gear-fill me-2"></i> Configuración
            </a>
        </li>
    </ul>

    <div class="tab-content bg-white shadow-sm rounded-bottom p-3">
        <!-- Tab Base de Datos -->
        <div id="database-tab" class="tab-pane fade show active">
            <?php 
            $periodColors = [
                'daily' => 'success',
                'weekly' => 'info', 
                'monthly' => 'primary'
            ];
            foreach (['daily' => 'Diarios', 'weekly' => 'Semanales', 'monthly' => 'Mensuales'] as $period => $label): 
            ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-<?php echo $periodColors[$period]; ?> bg-opacity-10 border-0">
                        <h5 class="mb-0 text-<?php echo $periodColors[$period]; ?>">
                            <i class="bi bi-calendar-check me-2"></i><?php echo $label; ?>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (count($databaseBackups[$period]) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th><i class="bi bi-file-earmark-zip me-1"></i> Archivo</th>
                                            <th><i class="bi bi-hdd me-1"></i> Tamaño</th>
                                            <th><i class="bi bi-clock me-1"></i> Fecha</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($databaseBackups[$period] as $backup): ?>
                                            <tr>
                                                <td>
                                                    <i class="bi bi-file-zip-fill text-warning me-2"></i>
                                                    <span class="font-monospace small"><?php echo htmlspecialchars($backup['name']); ?></span>
                                                </td>
                                                <td><span class="badge bg-secondary"><?php echo formatSize($backup['size']); ?></span></td>
                                                <td><?php echo date('d/m/Y H:i:s', $backup['date']); ?></td>
                                                <td class="text-center">
                                                    <button class="btn btn-sm btn-primary" onclick="downloadBackup('<?php echo htmlspecialchars($backup['name']); ?>', 'database', '<?php echo $period; ?>')">
                                                        <i class="bi bi-download"></i> Descargar
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="p-4 text-center text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                <p class="mb-0">No hay backups <?php echo strtolower($label); ?> disponibles.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Tab Archivos -->
        <div id="uploads-tab" class="tab-pane fade">
            <?php foreach (['daily' => 'Diarios', 'weekly' => 'Semanales', 'monthly' => 'Mensuales'] as $period => $label): ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-<?php echo $periodColors[$period]; ?> bg-opacity-10 border-0">
                        <h5 class="mb-0 text-<?php echo $periodColors[$period]; ?>">
                            <i class="bi bi-calendar-check me-2"></i><?php echo $label; ?>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (count($uploadsBackups[$period]) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th><i class="bi bi-file-earmark-zip me-1"></i> Archivo</th>
                                            <th><i class="bi bi-hdd me-1"></i> Tamaño</th>
                                            <th><i class="bi bi-clock me-1"></i> Fecha</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($uploadsBackups[$period] as $backup): ?>
                                            <tr>
                                                <td>
                                                    <i class="bi bi-file-zip-fill text-warning me-2"></i>
                                                    <span class="font-monospace small"><?php echo htmlspecialchars($backup['name']); ?></span>
                                                </td>
                                                <td><span class="badge bg-secondary"><?php echo formatSize($backup['size']); ?></span></td>
                                                <td><?php echo date('d/m/Y H:i:s', $backup['date']); ?></td>
                                                <td class="text-center">
                                                    <button class="btn btn-sm btn-primary" onclick="downloadBackup('<?php echo htmlspecialchars($backup['name']); ?>', 'uploads_backup', '<?php echo $period; ?>')">
                                                        <i class="bi bi-download"></i> Descargar
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="p-4 text-center text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                <p class="mb-0">No hay backups <?php echo strtolower($label); ?> disponibles.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Tab Configuracion -->
        <div id="config-tab" class="tab-pane fade">
            <!-- Política de Retención -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-warning bg-opacity-10 border-0">
                    <h5 class="mb-0 text-warning">
                        <i class="bi bi-hourglass-split me-2"></i>Política de Retención
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Periodo</th>
                                    <th>Mantener</th>
                                    <th>Descripción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="badge bg-success">DIARIOS</span></td>
                                    <td><strong>7 backups</strong></td>
                                    <td>Se ejecutan todos los días a las 2:00 AM</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-info">SEMANALES</span></td>
                                    <td><strong>4 backups</strong></td>
                                    <td>Se ejecutan los domingos</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-primary">MENSUALES</span></td>
                                    <td><strong>12 backups</strong></td>
                                    <td>Se ejecutan el día 1 de cada mes</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Sincronización con Google Drive -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary bg-opacity-10 border-0">
                    <h5 class="mb-0 text-primary">
                        <i class="bi bi-cloud-check-fill me-2"></i>Sincronización con Google Drive
                    </h5>
                </div>
                <div class="card-body">
                    <p class="mb-3">Los backups se sincronizan automáticamente con Google Drive Desktop.</p>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            Asegúrate de tener instalado Google Drive Desktop
                        </li>
                        <li class="list-group-item">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            Configura la carpeta <code class="bg-light px-2 py-1 rounded">backups/</code> dentro de Google Drive
                        </li>
                        <li class="list-group-item">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            La sincronización es automática y en tiempo real
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Tarea Programada -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-info bg-opacity-10 border-0">
                    <h5 class="mb-0 text-info">
                        <i class="bi bi-clock-fill me-2"></i>Tarea Programada
                    </h5>
                </div>
                <div class="card-body">
                    <p class="mb-3">El sistema ejecuta backups automáticamente mediante el Programador de Tareas de Windows:</p>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded">
                                <small class="text-muted d-block mb-1">Nombre</small>
                                <strong>Backup GASELAG Automático</strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded">
                                <small class="text-muted d-block mb-1">Frecuencia</small>
                                <strong>Diario a las 2:00 AM</strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded">
                                <small class="text-muted d-block mb-1">Script</small>
                                <code class="small">backups/scripts/auto_backup.php</code>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function runBackup(type) {
    if (!confirm('¿Deseas ejecutar el backup ahora? Esto puede tomar unos minutos.')) {
        return;
    }
    
    let script = '';
    let title = '';
    
    switch(type) {
        case 'all':
            script = 'auto_backup.php';
            title = 'Backup Completo';
            break;
        case 'database':
            script = 'backup_database.php';
            title = 'Backup de Base de Datos';
            break;
        case 'uploads':
            script = 'backup_uploads.php';
            title = 'Backup de Archivos';
            break;
    }
    
    alert('Ejecutando ' + title + '... Por favor espera.');
    
    // Aqui se podria implementar AJAX para ejecutar el backup
    window.open('../backups/scripts/' + script, '_blank');
}

function cleanOldBackups() {
    if (!confirm('¿Deseas eliminar los backups antiguos segun la politica de retencion?')) {
        return;
    }
    
    alert('Limpiando backups antiguos... Por favor espera.');
    window.open('../backups/scripts/cleanup_old_backups.php', '_blank');
}

function downloadBackup(filename, type, period) {
    window.location.href = 'descargar_backup.php?file=' + encodeURIComponent(filename) + '&type=' + type + '&period=' + period;
}
</script>

<?php require_once '../includes/footer.php'; ?>

