<?php
require_once '../config/database.php';

// Verificar autenticación
requireRole(['admin', 'user']);

// Limpiar todas las sesiones relacionadas con OCs
unset($_SESSION['selected_ocs']);
unset($_SESSION['ocs_temporales']);

// Registrar en auditoría
logAudit(
    null,
    $_SESSION['user_id'],
    'busqueda_oc',
    "Sesión de OCs limpiada por el usuario",
    null
);

// Redirigir a búsqueda de OCs
header('Location: buscar_oc.php?limpiado=1');
exit;

