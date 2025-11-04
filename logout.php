<?php
require_once 'config/database.php';

// Verificar si está logueado
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Obtener nombre del usuario antes de cerrar sesión
$userName = getCurrentUser()['nombre_completo'];

// Hacer logout
logout();

// Marcar que se cerró sesión exitosamente
$logoutSuccess = true;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cerrando Sesión - GASELAG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logout-card {
            background: white;
            border-radius: 15px;
            padding: 3rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 400px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="logout-card">
        <div class="mb-4">
            <i class="bi bi-box-arrow-right text-primary" style="font-size: 4rem;"></i>
        </div>
        <h3 class="mb-3">¡Hasta Pronto!</h3>
        <p class="text-muted mb-4">
            <strong><?php echo htmlspecialchars($userName); ?></strong>
        </p>
        <p class="text-muted mb-4">Tu sesión se ha cerrado correctamente</p>
        <div class="spinner-border text-primary mb-3" role="status">
            <span class="visually-hidden">Cargando...</span>
        </div>
        <p class="text-muted">
            <small>Redirigiendo al login...</small>
        </p>
    </div>
    
    <script>
        setTimeout(function() {
            window.location.href = 'login.php?logout=1';
        }, 1500);
    </script>
</body>
</html>
