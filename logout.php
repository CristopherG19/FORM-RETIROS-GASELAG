<?php
require_once 'config/database.php';

// Verificar si está logueado
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Hacer logout
logout();

// Redirigir al login con mensaje de éxito
header('Location: login.php?logout=1');
exit;
?>
