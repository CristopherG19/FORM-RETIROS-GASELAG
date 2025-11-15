<?php
/**
 * Script para Descargar Backups
 * Solo accesible por administradores
 */

require_once '../config/database.php';
requireRole(['admin']);

// Obtener parametros
$filename = $_GET['file'] ?? '';
$type = $_GET['type'] ?? '';
$period = $_GET['period'] ?? '';

// Validar parametros
if (empty($filename) || empty($type) || empty($period)) {
    die('Parametros invalidos');
}

// Construir ruta del archivo
$backupPath = dirname(__DIR__) . '/backups';
$filepath = "$backupPath/$type/$period/$filename";

// Verificar que el archivo existe y esta dentro de la carpeta de backups
$realBackupPath = realpath($backupPath);
$realFilePath = realpath($filepath);

if (!$realFilePath || strpos($realFilePath, $realBackupPath) !== 0) {
    die('Archivo no valido o no encontrado');
}

if (!file_exists($filepath)) {
    die('Archivo no encontrado');
}

// Enviar archivo para descarga
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: must-revalidate');
header('Pragma: public');

readfile($filepath);
exit();
?>

