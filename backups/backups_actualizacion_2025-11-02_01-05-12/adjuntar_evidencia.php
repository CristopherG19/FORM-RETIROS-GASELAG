<?php
require_once '../config/database.php';

// Verificar autenticación (todos los usuarios)
requireRole(['admin', 'user']);

$currentUser = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$retiroId = $_POST['retiro_id'] ?? 0;
$fotoFile = $_FILES['foto_evidencia'] ?? null;

if (!$retiroId || !$fotoFile || $fotoFile['error'] !== UPLOAD_ERR_OK) {
    echo '<div class="alert alert-danger">Error: Datos inválidos o archivo no seleccionado</div>';
    exit;
}

// Verificar que el usuario puede adjuntar evidencia a este retiro
if (!canAccessRetiro($retiroId) && !isAdmin()) {
    echo '<div class="alert alert-danger">Error: No tiene permisos para editar este registro</div>';
    exit;
}

// SEGURIDAD: Procesar y validar la imagen
$uploadDir = '../uploads/';
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$maxFileSize = 10 * 1024 * 1024; // 10MB

// Verificar tamaño primero
if ($fotoFile['size'] > $maxFileSize) {
    echo '<div class="alert alert-danger">Error: El archivo es demasiado grande (máximo 10MB)</div>';
    exit;
}

// Validar tipo MIME real del archivo
$fileInfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($fileInfo, $fotoFile['tmp_name']);
finfo_close($fileInfo);

if (!in_array($mimeType, $allowedTypes)) {
    echo '<div class="alert alert-danger">Error: Solo se permiten archivos de imagen (JPEG, PNG, GIF, WebP)</div>';
    exit;
}

// Obtener extensión segura basada en MIME type
$extensionMap = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp'
];
$extension = $extensionMap[$mimeType] ?? 'jpg';

// Generar nombre único para el archivo (sanitizado)
$timestamp = date('Ymd_His');
$randomStr = substr(md5(uniqid()), 0, 8);
$newFileName = "evidencia_{$retiroId}_{$timestamp}_{$randomStr}.{$extension}";
$targetPath = $uploadDir . $newFileName;

if (!move_uploaded_file($fotoFile['tmp_name'], $targetPath)) {
    echo '<div class="alert alert-danger">Error: No se pudo guardar el archivo</div>';
    exit;
}

// Adjuntar evidencia usando la función del sistema
$result = adjuntarEvidenciaFotografica($retiroId, $newFileName, $_SESSION['user_id']);

if ($result['success']) {
    // Redirigir al detalle del retiro con mensaje de éxito
    header("Location: detalle_retiro.php?id=$retiroId&success=evidencia_adjuntada");
} else {
    // Eliminar archivo si no se pudo procesar
    if (file_exists($targetPath)) {
        unlink($targetPath);
    }
    header("Location: detalle_retiro.php?id=$retiroId&error=" . urlencode($result['message']));
}

exit;
?>
