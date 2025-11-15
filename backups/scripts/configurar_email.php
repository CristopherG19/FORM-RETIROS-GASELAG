<?php
/**
 * Script de Configuracion Rapida - Email para Notificaciones
 * Actualiza automaticamente el email en auto_backup.php
 */

echo "\n";
echo "========================================\n";
echo "  CONFIGURACION DE EMAIL\n";
echo "  Sistema de Backups GASELAG\n";
echo "========================================\n\n";

// Solicitar email
echo "Ingresa el email para recibir notificaciones de backups:\n";
echo "(Por ejemplo: admin@gaselag.com)\n\n";
echo "Email: ";

$handle = fopen ("php://stdin","r");
$email = trim(fgets($handle));

// Validar email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "\n❌ ERROR: Email invalido\n\n";
    exit(1);
}

// Leer auto_backup.php
$autoBackupFile = __DIR__ . '/auto_backup.php';

if (!file_exists($autoBackupFile)) {
    echo "\n❌ ERROR: No se encontro auto_backup.php\n\n";
    exit(1);
}

$content = file_get_contents($autoBackupFile);

// Reemplazar email
$pattern = "/'email_to'\s*=>\s*'[^']*'/";
$replacement = "'email_to' => '$email'";

$newContent = preg_replace($pattern, $replacement, $content);

if ($newContent === null || $newContent === $content) {
    echo "\n❌ ERROR: No se pudo actualizar el email\n\n";
    exit(1);
}

// Guardar archivo
if (file_put_contents($autoBackupFile, $newContent)) {
    echo "\n";
    echo "========================================\n";
    echo "  ✅ CONFIGURACION EXITOSA\n";
    echo "========================================\n\n";
    echo "Email configurado: $email\n";
    echo "Archivo actualizado: auto_backup.php\n\n";
    echo "Ahora puedes:\n";
    echo "1. Ejecutar el backup manual:\n";
    echo "   php auto_backup.php\n\n";
    echo "2. Configurar la tarea programada:\n";
    echo "   powershell -File crear_tarea_programada.ps1\n\n";
    echo "3. Configurar Google Drive Desktop\n";
    echo "   (Ver GUIA_BACKUPS_GOOGLE_DRIVE.md)\n\n";
} else {
    echo "\n❌ ERROR: No se pudo guardar el archivo\n\n";
    exit(1);
}

fclose($handle);
?>

