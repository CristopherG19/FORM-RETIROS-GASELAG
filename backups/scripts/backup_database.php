<?php
/**
 * Script de Backup Automatico de Base de Datos
 * Sistema GASELAG - Backups en Google Drive
 * 
 * Este script crea backups de la base de datos MySQL
 * y los organiza por fecha y periodo (diario/semanal/mensual)
 */

// Configuracion
$config = [
    'db_host' => 'localhost',
    'db_port' => '3307',
    'db_user' => 'root',
    'db_pass' => '',
    'db_name' => 'gaselag_retiros',
    'backup_path' => dirname(__DIR__), // backups/
    'mysqldump_path' => 'C:\\xampp\\mysql\\bin\\mysqldump.exe', // Ajustar segun instalacion
    'log_file' => dirname(__DIR__) . '/logs/backup_database.log'
];

// Funcion para escribir en el log
function writeLog($message, $type = 'INFO') {
    global $config;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$type] $message" . PHP_EOL;
    
    // Crear directorio de logs si no existe
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
    $dayOfWeek = date('w'); // 0=Domingo, 1=Lunes, etc
    
    // Si es dia 1 del mes -> mensual
    if ($day == 1) {
        return 'monthly';
    }
    
    // Si es domingo -> semanal
    if ($dayOfWeek == 0) {
        return 'weekly';
    }
    
    // Cualquier otro dia -> diario
    return 'daily';
}

// Funcion para crear backup de base de datos
function backupDatabase() {
    global $config;
    
    writeLog("=== INICIANDO BACKUP DE BASE DE DATOS ===", "INFO");
    
    try {
        // Obtener periodo del backup
        $period = getBackupPeriod();
        $timestamp = date('Y-m-d_H-i-s');
        $date = date('Y-m-d');
        
        // Crear directorio de destino
        $backupDir = $config['backup_path'] . "/database/$period";
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
            writeLog("Directorio creado: $backupDir", "INFO");
        }
        
        // Nombre del archivo de backup
        $filename = "gaselag_db_backup_{$period}_{$timestamp}.sql";
        $filepath = $backupDir . "/" . $filename;
        
        // Construir comando mysqldump
        $command = sprintf(
            '"%s" --host=%s --port=%s --user=%s --password=%s --single-transaction --routines --triggers %s > "%s"',
            $config['mysqldump_path'],
            $config['db_host'],
            $config['db_port'],
            $config['db_user'],
            $config['db_pass'],
            $config['db_name'],
            $filepath
        );
        
        writeLog("Ejecutando mysqldump...", "INFO");
        writeLog("Archivo: $filename", "INFO");
        writeLog("Periodo: $period", "INFO");
        
        // Ejecutar comando
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0 && file_exists($filepath)) {
            $filesize = filesize($filepath);
            $filesizeMB = round($filesize / 1024 / 1024, 2);
            
            writeLog("Backup completado exitosamente", "SUCCESS");
            writeLog("Tamano: {$filesizeMB} MB", "INFO");
            writeLog("Ubicacion: $filepath", "INFO");
            
            // Comprimir el archivo SQL
            compressBackup($filepath);
            
            return [
                'success' => true,
                'file' => $filepath,
                'size' => $filesizeMB,
                'period' => $period,
                'timestamp' => $timestamp
            ];
        } else {
            throw new Exception("Error al crear el backup. Codigo de retorno: $returnCode");
        }
        
    } catch (Exception $e) {
        writeLog("ERROR: " . $e->getMessage(), "ERROR");
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Funcion para comprimir el backup
function compressBackup($filepath) {
    writeLog("Comprimiendo backup...", "INFO");
    
    try {
        $zipfile = $filepath . '.zip';
        $zip = new ZipArchive();
        
        if ($zip->open($zipfile, ZipArchive::CREATE) === true) {
            $zip->addFile($filepath, basename($filepath));
            $zip->close();
            
            // Eliminar archivo SQL sin comprimir
            unlink($filepath);
            
            $zipsize = filesize($zipfile);
            $zipsizeMB = round($zipsize / 1024 / 1024, 2);
            
            writeLog("Backup comprimido: {$zipsizeMB} MB", "SUCCESS");
            writeLog("Archivo: " . basename($zipfile), "INFO");
            
            return true;
        }
    } catch (Exception $e) {
        writeLog("Error al comprimir: " . $e->getMessage(), "WARNING");
    }
    
    return false;
}

// Funcion para limpiar backups antiguos
function cleanOldBackups() {
    global $config;
    
    writeLog("Limpiando backups antiguos...", "INFO");
    
    $periods = [
        'daily' => 7,      // Mantener ultimos 7 dias
        'weekly' => 4,     // Mantener ultimas 4 semanas
        'monthly' => 12    // Mantener ultimos 12 meses
    ];
    
    $deleted = 0;
    
    foreach ($periods as $period => $keep) {
        $dir = $config['backup_path'] . "/database/$period";
        
        if (!is_dir($dir)) continue;
        
        // Obtener todos los archivos .zip en el directorio
        $files = glob($dir . "/*.zip");
        
        if (count($files) > $keep) {
            // Ordenar por fecha de modificacion (mas antiguos primero)
            usort($files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            // Eliminar archivos mas antiguos
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
writeLog("  SISTEMA DE BACKUP - BASE DE DATOS", "INFO");
writeLog("  GASELAG RETIROS", "INFO");
writeLog("========================================", "INFO");
writeLog("", "INFO");

// Verificar que mysqldump existe
if (!file_exists($config['mysqldump_path'])) {
    writeLog("ERROR: mysqldump no encontrado en: " . $config['mysqldump_path'], "ERROR");
    writeLog("Por favor, ajusta la ruta en la configuracion", "ERROR");
    exit(1);
}

// Crear backup
$result = backupDatabase();

if ($result['success']) {
    writeLog("", "INFO");
    writeLog("========================================", "INFO");
    writeLog("  BACKUP COMPLETADO EXITOSAMENTE", "SUCCESS");
    writeLog("========================================", "INFO");
    writeLog("Periodo: " . $result['period'], "INFO");
    writeLog("Tamano: " . $result['size'] . " MB", "INFO");
    writeLog("Timestamp: " . $result['timestamp'], "INFO");
    writeLog("", "INFO");
    
    // Limpiar backups antiguos
    cleanOldBackups();
    
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

