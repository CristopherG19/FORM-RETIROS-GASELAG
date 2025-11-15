<?php
/**
 * Script de Backup Automatico de Archivos Uploads
 * Sistema GASELAG - Backups en Google Drive
 * 
 * Este script crea backups de la carpeta uploads/
 * (fotos de evidencias, imposibilidades, perfiles)
 */

// Configuracion
$config = [
    'uploads_path' => dirname(dirname(__DIR__)) . '/uploads', // Carpeta uploads del sistema
    'backup_path' => dirname(__DIR__), // backups/
    'log_file' => dirname(__DIR__) . '/logs/backup_uploads.log'
];

// Funcion para escribir en el log
function writeLog($message, $type = 'INFO') {
    global $config;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$type] $message" . PHP_EOL;
    
    $logDir = dirname($config['log_file']);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    file_put_contents($config['log_file'], $logMessage, FILE_APPEND);
    echo $logMessage;
}

// Funcion para obtener el periodo del backup
function getBackupPeriod() {
    $day = date('j');
    $dayOfWeek = date('w');
    
    if ($day == 1) {
        return 'monthly';
    }
    
    if ($dayOfWeek == 0) {
        return 'weekly';
    }
    
    return 'daily';
}

// Funcion para copiar directorio recursivamente
function copyDirectory($source, $dest) {
    if (!is_dir($source)) {
        return false;
    }
    
    if (!is_dir($dest)) {
        mkdir($dest, 0755, true);
    }
    
    $dir = opendir($source);
    $fileCount = 0;
    $totalSize = 0;
    
    while (($file = readdir($dir)) !== false) {
        if ($file != '.' && $file != '..') {
            $srcPath = $source . '/' . $file;
            $destPath = $dest . '/' . $file;
            
            if (is_dir($srcPath)) {
                $result = copyDirectory($srcPath, $destPath);
                $fileCount += $result['count'];
                $totalSize += $result['size'];
            } else {
                copy($srcPath, $destPath);
                $fileCount++;
                $totalSize += filesize($srcPath);
            }
        }
    }
    
    closedir($dir);
    
    return [
        'count' => $fileCount,
        'size' => $totalSize
    ];
}

// Funcion para crear archivo ZIP de un directorio
function createZipFromDirectory($source, $zipFile) {
    if (!extension_loaded('zip')) {
        throw new Exception('Extension ZIP no disponible');
    }
    
    $zip = new ZipArchive();
    
    if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new Exception("No se pudo crear el archivo ZIP: $zipFile");
    }
    
    $source = realpath($source);
    
    if (is_dir($source)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        $fileCount = 0;
        
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($source) + 1);
                
                $zip->addFile($filePath, $relativePath);
                $fileCount++;
            }
        }
        
        writeLog("Archivos agregados al ZIP: $fileCount", "INFO");
    }
    
    $zip->close();
    
    return file_exists($zipFile);
}

// Funcion principal de backup
function backupUploads() {
    global $config;
    
    writeLog("=== INICIANDO BACKUP DE UPLOADS ===", "INFO");
    
    try {
        // Verificar que existe la carpeta uploads
        if (!is_dir($config['uploads_path'])) {
            throw new Exception("Carpeta uploads no encontrada: " . $config['uploads_path']);
        }
        
        // Contar archivos en uploads
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($config['uploads_path'])
        );
        
        $totalFiles = 0;
        $totalSize = 0;
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getFilename() != 'index.php') {
                $totalFiles++;
                $totalSize += $file->getSize();
            }
        }
        
        writeLog("Archivos encontrados: $totalFiles", "INFO");
        writeLog("Tamano total: " . round($totalSize / 1024 / 1024, 2) . " MB", "INFO");
        
        if ($totalFiles == 0) {
            writeLog("No hay archivos para respaldar", "WARNING");
            return [
                'success' => true,
                'files' => 0,
                'message' => 'No hay archivos para respaldar'
            ];
        }
        
        // Obtener periodo y crear directorio
        $period = getBackupPeriod();
        $timestamp = date('Y-m-d_H-i-s');
        
        $backupDir = $config['backup_path'] . "/uploads_backup/$period";
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        // Nombre del archivo ZIP
        $zipFilename = "uploads_backup_{$period}_{$timestamp}.zip";
        $zipPath = $backupDir . "/" . $zipFilename;
        
        writeLog("Creando archivo ZIP...", "INFO");
        writeLog("Archivo: $zipFilename", "INFO");
        writeLog("Periodo: $period", "INFO");
        
        // Crear ZIP
        if (createZipFromDirectory($config['uploads_path'], $zipPath)) {
            $zipSize = filesize($zipPath);
            $zipSizeMB = round($zipSize / 1024 / 1024, 2);
            
            writeLog("Backup completado exitosamente", "SUCCESS");
            writeLog("Tamano comprimido: {$zipSizeMB} MB", "INFO");
            writeLog("Ubicacion: $zipPath", "INFO");
            
            return [
                'success' => true,
                'file' => $zipPath,
                'files' => $totalFiles,
                'size' => $zipSizeMB,
                'period' => $period,
                'timestamp' => $timestamp
            ];
        } else {
            throw new Exception("Error al crear el archivo ZIP");
        }
        
    } catch (Exception $e) {
        writeLog("ERROR: " . $e->getMessage(), "ERROR");
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Funcion para limpiar backups antiguos
function cleanOldBackups() {
    global $config;
    
    writeLog("Limpiando backups antiguos de uploads...", "INFO");
    
    $periods = [
        'daily' => 7,
        'weekly' => 4,
        'monthly' => 12
    ];
    
    $deleted = 0;
    
    foreach ($periods as $period => $keep) {
        $dir = $config['backup_path'] . "/uploads_backup/$period";
        
        if (!is_dir($dir)) continue;
        
        $files = glob($dir . "/*.zip");
        
        if (count($files) > $keep) {
            usort($files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            $toDelete = array_slice($files, 0, count($files) - $keep);
            
            foreach ($toDelete as $file) {
                if (unlink($file)) {
                    $deleted++;
                    writeLog("Eliminado: " . basename($file), "INFO");
                }
            }
        }
    }
    
    if ($deleted > 0) {
        writeLog("Backups antiguos eliminados: $deleted", "INFO");
    } else {
        writeLog("No hay backups antiguos para eliminar", "INFO");
    }
}

// Ejecucion principal
writeLog("", "INFO");
writeLog("========================================", "INFO");
writeLog("  SISTEMA DE BACKUP - UPLOADS", "INFO");
writeLog("  GASELAG RETIROS", "INFO");
writeLog("========================================", "INFO");
writeLog("", "INFO");

// Crear backup
$result = backupUploads();

if ($result['success']) {
    writeLog("", "INFO");
    writeLog("========================================", "INFO");
    writeLog("  BACKUP COMPLETADO EXITOSAMENTE", "SUCCESS");
    writeLog("========================================", "INFO");
    
    if (isset($result['files']) && $result['files'] > 0) {
        writeLog("Archivos respaldados: " . $result['files'], "INFO");
        writeLog("Periodo: " . $result['period'], "INFO");
        writeLog("Tamano: " . $result['size'] . " MB", "INFO");
        writeLog("Timestamp: " . $result['timestamp'], "INFO");
        
        // Limpiar backups antiguos
        cleanOldBackups();
    } else {
        writeLog($result['message'], "INFO");
    }
    
    writeLog("", "INFO");
    writeLog("========================================", "INFO");
    writeLog("  PROCESO FINALIZADO", "INFO");
    writeLog("========================================", "INFO");
    
    exit(0);
} else {
    writeLog("", "INFO");
    writeLog("========================================", "ERROR");
    writeLog("  ERROR EN EL BACKUP", "ERROR");
    writeLog("========================================", "ERROR");
    writeLog("Error: " . $result['error'], "ERROR");
    writeLog("", "INFO");
    
    exit(1);
}
?>

