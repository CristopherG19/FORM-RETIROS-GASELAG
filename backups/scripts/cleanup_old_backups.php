<?php
/**
 * Script de Limpieza de Backups Antiguos
 * Sistema GASELAG - Backups en Google Drive
 * 
 * Este script elimina backups antiguos segun la politica de retencion
 * Mantiene: 7 dias diarios, 4 semanas semanales, 12 meses mensuales
 */

// Configurar zona horaria (Perú)
date_default_timezone_set('America/Lima');

// Configuracion
$config = [
    'backup_path' => dirname(__DIR__),
    'log_file' => dirname(__DIR__) . '/logs/cleanup.log',
    'retention_policy' => [
        'daily' => 7,      // dias
        'weekly' => 4,     // semanas
        'monthly' => 12    // meses
    ]
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

// Funcion para limpiar backups de un tipo
function cleanBackupType($type, $subtype, $keep) {
    global $config;
    
    $dir = $config['backup_path'] . "/{$type}/{$subtype}";
    
    if (!is_dir($dir)) {
        writeLog("Directorio no existe: $dir", "WARNING");
        return 0;
    }
    
    // Obtener archivos
    $files = glob($dir . "/*.{zip,sql}", GLOB_BRACE);
    
    if (count($files) <= $keep) {
        writeLog("No hay archivos para eliminar en $type/$subtype (Total: " . count($files) . ", Mantener: $keep)", "INFO");
        return 0;
    }
    
    // Ordenar por fecha (mas antiguos primero)
    usort($files, function($a, $b) {
        return filemtime($a) - filemtime($b);
    });
    
    // Archivos a eliminar
    $toDelete = array_slice($files, 0, count($files) - $keep);
    $deleted = 0;
    $freedSpace = 0;
    
    foreach ($toDelete as $file) {
        $filesize = filesize($file);
        
        if (unlink($file)) {
            $deleted++;
            $freedSpace += $filesize;
            writeLog("Eliminado: " . basename($file) . " (" . round($filesize / 1024 / 1024, 2) . " MB)", "INFO");
        } else {
            writeLog("ERROR al eliminar: " . basename($file), "ERROR");
        }
    }
    
    return [
        'deleted' => $deleted,
        'freed' => $freedSpace
    ];
}

// Funcion para limpiar logs antiguos (mas de 30 dias)
function cleanOldLogs() {
    global $config;
    
    $logDir = dirname($config['log_file']);
    
    if (!is_dir($logDir)) {
        return 0;
    }
    
    $files = glob($logDir . "/*.log");
    $deleted = 0;
    $cutoffDate = strtotime('-30 days');
    
    foreach ($files as $file) {
        if (filemtime($file) < $cutoffDate && basename($file) != 'cleanup.log') {
            if (unlink($file)) {
                $deleted++;
                writeLog("Log antiguo eliminado: " . basename($file), "INFO");
            }
        }
    }
    
    return $deleted;
}

// EJECUCION PRINCIPAL
writeLog("", "INFO");
writeLog("========================================", "INFO");
writeLog("  LIMPIEZA DE BACKUPS ANTIGUOS", "INFO");
writeLog("  GASELAG RETIROS", "INFO");
writeLog("========================================", "INFO");
writeLog("", "INFO");

$totalDeleted = 0;
$totalFreed = 0;

// Limpiar backups de base de datos
writeLog("Limpiando backups de base de datos...", "INFO");

foreach ($config['retention_policy'] as $period => $keep) {
    writeLog("Procesando: database/$period (mantener $keep)", "INFO");
    $result = cleanBackupType('database', $period, $keep);
    
    if (is_array($result)) {
        $totalDeleted += $result['deleted'];
        $totalFreed += $result['freed'];
    }
}

// Limpiar backups de uploads
writeLog("", "INFO");
writeLog("Limpiando backups de uploads...", "INFO");

foreach ($config['retention_policy'] as $period => $keep) {
    writeLog("Procesando: uploads_backup/$period (mantener $keep)", "INFO");
    $result = cleanBackupType('uploads_backup', $period, $keep);
    
    if (is_array($result)) {
        $totalDeleted += $result['deleted'];
        $totalFreed += $result['freed'];
    }
}

// Limpiar logs antiguos
writeLog("", "INFO");
writeLog("Limpiando logs antiguos (mas de 30 dias)...", "INFO");
$logsDeleted = cleanOldLogs();

// Resumen
writeLog("", "INFO");
writeLog("========================================", "INFO");
writeLog("  RESUMEN DE LIMPIEZA", "INFO");
writeLog("========================================", "INFO");
writeLog("Backups eliminados: $totalDeleted", "INFO");
writeLog("Espacio liberado: " . round($totalFreed / 1024 / 1024, 2) . " MB", "INFO");
writeLog("Logs eliminados: $logsDeleted", "INFO");
writeLog("", "INFO");
writeLog("========================================", "INFO");
writeLog("  LIMPIEZA COMPLETADA", "SUCCESS");
writeLog("========================================", "INFO");
writeLog("", "INFO");

exit(0);
?>

