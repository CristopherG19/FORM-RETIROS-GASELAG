<?php
/**
 * Script Maestro de Backup Automatico
 * Sistema GASELAG - Backups en Google Drive
 * 
 * Este script ejecuta todos los backups y envia notificaciones
 * Se debe ejecutar diariamente a las 2:00 AM mediante tarea programada
 */

// Configuracion
$config = [
    'email_enabled' => true,
    'email_to' => 'gaselagvp@gmail.com', // Email configurado
    'email_from' => 'backup@gaselag.com',
    'email_subject' => 'Reporte de Backup - Sistema GASELAG',
    'backup_scripts' => [
        'database' => __DIR__ . '/backup_database.php',
        'uploads' => __DIR__ . '/backup_uploads.php'
    ],
    'log_file' => dirname(__DIR__) . '/logs/auto_backup.log',
    'php_path' => 'C:\\xampp\\php\\php.exe' // Ajustar segun instalacion
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

// Funcion para ejecutar un script de backup
function runBackupScript($scriptName, $scriptPath) {
    global $config;
    
    writeLog("Ejecutando backup: $scriptName", "INFO");
    
    if (!file_exists($scriptPath)) {
        writeLog("ERROR: Script no encontrado: $scriptPath", "ERROR");
        return [
            'success' => false,
            'script' => $scriptName,
            'error' => 'Script no encontrado'
        ];
    }
    
    $startTime = microtime(true);
    
    // Ejecutar el script
    $command = sprintf('"%s" "%s"', $config['php_path'], $scriptPath);
    $output = [];
    $returnCode = 0;
    
    exec($command, $output, $returnCode);
    
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);
    
    if ($returnCode === 0) {
        writeLog("Backup $scriptName completado en {$duration}s", "SUCCESS");
        return [
            'success' => true,
            'script' => $scriptName,
            'duration' => $duration,
            'output' => $output
        ];
    } else {
        writeLog("ERROR en backup $scriptName", "ERROR");
        return [
            'success' => false,
            'script' => $scriptName,
            'duration' => $duration,
            'error' => 'Codigo de retorno: ' . $returnCode,
            'output' => $output
        ];
    }
}

// Funcion para enviar notificacion por email
function sendEmailNotification($results, $overallSuccess) {
    global $config;
    
    if (!$config['email_enabled']) {
        writeLog("Notificaciones por email desactivadas", "INFO");
        return;
    }
    
    writeLog("Enviando notificacion por email...", "INFO");
    
    // Construir el cuerpo del email
    $emailBody = "<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .header { background: #667eea; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; }
        .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 10px 0; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 10px 0; }
        .info { background: #d1ecf1; border-left: 4px solid #17a2b8; padding: 15px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background: #667eea; color: white; }
        .footer { background: #f8f9fa; padding: 15px; text-align: center; margin-top: 20px; }
    </style>
</head>
<body>
    <div class='header'>
        <h1>Reporte de Backup Automatico</h1>
        <p>Sistema GASELAG - Gestion de Retiros</p>
    </div>
    
    <div class='content'>
        <h2>Resumen de Ejecucion</h2>
        <div class='" . ($overallSuccess ? 'success' : 'error') . "'>
            <strong>Estado General:</strong> " . ($overallSuccess ? 'EXITOSO' : 'CON ERRORES') . "<br>
            <strong>Fecha y Hora:</strong> " . date('d/m/Y H:i:s') . "
        </div>
        
        <h3>Detalles de Backups Ejecutados</h3>
        <table>
            <tr>
                <th>Backup</th>
                <th>Estado</th>
                <th>Duracion</th>
                <th>Detalles</th>
            </tr>";
    
    foreach ($results as $result) {
        $status = $result['success'] ? '<span style=\"color: green;\">✓ EXITOSO</span>' : '<span style=\"color: red;\">✗ ERROR</span>';
        $duration = isset($result['duration']) ? $result['duration'] . 's' : 'N/A';
        $details = $result['success'] ? 'Completado correctamente' : ($result['error'] ?? 'Error desconocido');
        
        $emailBody .= "
            <tr>
                <td><strong>" . strtoupper($result['script']) . "</strong></td>
                <td>$status</td>
                <td>$duration</td>
                <td>$details</td>
            </tr>";
    }
    
    $emailBody .= "
        </table>
        
        <div class='info'>
            <h3>Ubicacion de los Backups</h3>
            <p><strong>Backups locales:</strong> C:\\xampp\\htdocs\\form gaselag retiros\\backups\\</p>
            <p><strong>Sincronizacion:</strong> Los backups se sincronizaran automaticamente con Google Drive</p>
        </div>
        
        <div class='info'>
            <h3>Politica de Retencion</h3>
            <ul>
                <li><strong>Diarios:</strong> Ultimos 7 dias</li>
                <li><strong>Semanales:</strong> Ultimas 4 semanas</li>
                <li><strong>Mensuales:</strong> Ultimos 12 meses</li>
            </ul>
        </div>
    </div>
    
    <div class='footer'>
        <p>Este es un mensaje automatico del Sistema de Backups GASELAG</p>
        <p>No responder a este correo</p>
    </div>
</body>
</html>";
    
    // Headers del email
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . $config['email_from'] . "\r\n";
    
    // Enviar email
    if (mail($config['email_to'], $config['email_subject'], $emailBody, $headers)) {
        writeLog("Email enviado exitosamente a: " . $config['email_to'], "SUCCESS");
    } else {
        writeLog("ERROR: No se pudo enviar el email", "ERROR");
    }
}

// Funcion para obtener estadisticas de espacio
function getDiskStats() {
    $backupPath = dirname(__DIR__);
    
    $totalSize = 0;
    $fileCount = 0;
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($backupPath),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $totalSize += $file->getSize();
            $fileCount++;
        }
    }
    
    return [
        'total_size' => round($totalSize / 1024 / 1024, 2), // MB
        'file_count' => $fileCount
    ];
}

// EJECUCION PRINCIPAL
writeLog("", "INFO");
writeLog("========================================", "INFO");
writeLog("  SISTEMA DE BACKUP AUTOMATICO", "INFO");
writeLog("  GASELAG RETIROS", "INFO");
writeLog("========================================", "INFO");
writeLog("", "INFO");
writeLog("Fecha: " . date('d/m/Y H:i:s'), "INFO");
writeLog("", "INFO");

// Verificar que PHP existe
if (!file_exists($config['php_path'])) {
    writeLog("ERROR: PHP no encontrado en: " . $config['php_path'], "ERROR");
    writeLog("Por favor, ajusta la ruta en la configuracion", "ERROR");
    exit(1);
}

// Ejecutar cada script de backup
$results = [];
$overallSuccess = true;

foreach ($config['backup_scripts'] as $name => $script) {
    $result = runBackupScript($name, $script);
    $results[] = $result;
    
    if (!$result['success']) {
        $overallSuccess = false;
    }
}

// Obtener estadisticas
$stats = getDiskStats();

writeLog("", "INFO");
writeLog("========================================", "INFO");
writeLog("  ESTADISTICAS", "INFO");
writeLog("========================================", "INFO");
writeLog("Total de archivos: " . $stats['file_count'], "INFO");
writeLog("Espacio usado: " . $stats['total_size'] . " MB", "INFO");
writeLog("", "INFO");

// Enviar notificacion por email
sendEmailNotification($results, $overallSuccess);

// Resultado final
writeLog("", "INFO");
writeLog("========================================", "INFO");
if ($overallSuccess) {
    writeLog("  TODOS LOS BACKUPS COMPLETADOS", "SUCCESS");
} else {
    writeLog("  BACKUPS COMPLETADOS CON ERRORES", "WARNING");
}
writeLog("========================================", "INFO");
writeLog("", "INFO");

exit($overallSuccess ? 0 : 1);
?>

