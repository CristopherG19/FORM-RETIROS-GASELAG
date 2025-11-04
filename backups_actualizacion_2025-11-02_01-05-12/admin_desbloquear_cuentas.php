<?php
require_once '../config/database.php';

// Solo administradores
requireRole(['admin']);

$message = '';
$messageType = '';

// Procesar desbloqueo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $username = $_POST['username'] ?? '';
    
    if ($action === 'unlock' && !empty($username)) {
        if (unlockAccount($username, $_SESSION['user_id'])) {
            $message = "Cuenta '$username' desbloqueada exitosamente";
            $messageType = 'success';
        } else {
            $message = "Error al desbloquear la cuenta '$username'";
            $messageType = 'danger';
        }
    } elseif ($action === 'cleanup') {
        $deleted = cleanupOldLoginAttempts();
        $message = "Limpieza completada: $deleted registros antiguos eliminados";
        $messageType = 'info';
    }
}

// Obtener cuentas bloqueadas
$blockedAccounts = getBlockedAccounts();

// Obtener estadísticas
$stats = getLoginStats(24);

$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Cuentas Bloqueadas - GASELAG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .stats-card {
            transition: box-shadow 0.2s;
        }
        .stats-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .account-card {
            transition: box-shadow 0.2s;
        }
        .account-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-light">
    <?php include '../includes/session_middleware.php'; ?>
    
    <!-- Navbar -->
    <nav class="navbar navbar-dark bg-dark shadow-sm">
        <div class="container-fluid px-3 px-md-4">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="../index.php">
                <i class="bi bi-speedometer2 me-2 fs-4"></i>
                <span class="d-none d-sm-inline">GASELAG</span>
            </a>
            <div class="d-flex align-items-center gap-2">
                <span class="text-white small d-none d-md-inline">
                    <i class="bi bi-person-circle me-1"></i>
                    <?php echo htmlspecialchars($currentUser['nombre_completo']); ?>
                </span>
                <a href="../index.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-arrow-left"></i>
                    <span class="d-none d-sm-inline"> Volver</span>
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class="bi bi-shield-lock me-2"></i>
                Gestión de Seguridad
            </h2>
            <form method="POST" class="d-inline">
                <input type="hidden" name="action" value="cleanup">
                <button type="submit" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-trash me-1"></i>
                    Limpiar Registros Antiguos
                </button>
            </form>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card stats-card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">Total Intentos (24h)</p>
                                <h3 class="mb-0"><?php echo $stats['attempts']['total'] ?? 0; ?></h3>
                            </div>
                            <div class="text-primary">
                                <i class="bi bi-graph-up" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stats-card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">Intentos Exitosos</p>
                                <h3 class="mb-0 text-success"><?php echo $stats['attempts']['successful'] ?? 0; ?></h3>
                            </div>
                            <div class="text-success">
                                <i class="bi bi-check-circle" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stats-card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">Intentos Fallidos</p>
                                <h3 class="mb-0 text-danger"><?php echo $stats['attempts']['failed'] ?? 0; ?></h3>
                            </div>
                            <div class="text-danger">
                                <i class="bi bi-x-circle" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stats-card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">Cuentas Bloqueadas</p>
                                <h3 class="mb-0 text-warning"><?php echo count($blockedAccounts); ?></h3>
                            </div>
                            <div class="text-warning">
                                <i class="bi bi-lock" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cuentas Bloqueadas -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-lock-fill me-2"></i>
                    Cuentas Bloqueadas
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($blockedAccounts)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-shield-check" style="font-size: 3rem;"></i>
                        <p class="mt-3">No hay cuentas bloqueadas actualmente</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Nombre</th>
                                    <th>Rol</th>
                                    <th>Intentos Fallidos</th>
                                    <th>Bloqueado Hasta</th>
                                    <th>Último Intento</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($blockedAccounts as $account): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($account['username']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($account['nombre_completo']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $account['rol'] === 'admin' ? 'danger' : 'info'; ?>">
                                                <?php echo $account['rol'] === 'admin' ? 'Admin' : 'Técnico'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning text-dark">
                                                <?php echo $account['intentos_fallidos']; ?> intentos
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($account['bloqueado_hasta']): ?>
                                                <?php 
                                                $blockedUntil = strtotime($account['bloqueado_hasta']);
                                                $now = time();
                                                if ($blockedUntil > $now):
                                                    $remaining = $blockedUntil - $now;
                                                ?>
                                                    <span class="text-danger">
                                                        <i class="bi bi-clock me-1"></i>
                                                        <?php echo getBlockTimeRemaining($remaining); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">Expirado</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-danger">
                                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                                    Permanente
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo $account['ultimo_intento'] ? date('d/m/Y H:i', strtotime($account['ultimo_intento'])) : 'N/A'; ?>
                                            </small>
                                        </td>
                                        <td>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('¿Está seguro de desbloquear esta cuenta?');">
                                                <input type="hidden" name="action" value="unlock">
                                                <input type="hidden" name="username" value="<?php echo htmlspecialchars($account['username']); ?>">
                                                <button type="submit" class="btn btn-success btn-sm">
                                                    <i class="bi bi-unlock me-1"></i>
                                                    Desbloquear
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- IPs con más intentos fallidos -->
        <?php if (!empty($stats['top_failed_ips'])): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-geo-alt-fill me-2"></i>
                    IPs con Más Intentos Fallidos (24h)
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>IP Address</th>
                                <th>Intentos Fallidos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['top_failed_ips'] as $ip): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($ip['ip_address']); ?></code></td>
                                    <td>
                                        <span class="badge bg-danger"><?php echo $ip['count']; ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Usuarios con más intentos fallidos -->
        <?php if (!empty($stats['top_failed_users'])): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-person-x-fill me-2"></i>
                    Usuarios con Más Intentos Fallidos (24h)
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Intentos Fallidos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['top_failed_users'] as $user): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                    <td>
                                        <span class="badge bg-warning text-dark"><?php echo $user['count']; ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
