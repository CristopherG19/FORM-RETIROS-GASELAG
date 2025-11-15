<?php
require_once '../config/database.php';
require_once '../config/RateLimiter.php';

requireRole(['admin']);

$message = '';
$messageType = '';

// Obtener filtro de tiempo
$timeFilter = $_GET['time_filter'] ?? '24';
$timeFilterHours = (int)$timeFilter;

// Obtener filtro de búsqueda
$searchTerm = $_GET['search'] ?? '';

// Procesar acciones
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
    } elseif ($action === 'export_excel') {
        // Exportar a Excel (implementado más adelante)
        exportToExcel($timeFilterHours);
        exit;
    }
}

// Obtener datos según filtros
$blockedAccounts = getBlockedAccounts();
$stats = getLoginStats($timeFilterHours);
$successfulLogins = getRecentSuccessfulLogins($timeFilterHours, 50);
$unlockHistory = getUnlockHistory(168, 50); // Últimos 7 días
$securityAlerts = detectSecurityAlerts($timeFilterHours);

$currentUser = getCurrentUser();

$pageTitle = 'Desbloquear Cuentas - Sistema GASELAG';
require_once '../includes/header.php';
?>

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

<div class="container py-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class="bi bi-shield-lock me-2"></i>
                Gestión de Seguridad
            </h2>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-success btn-sm" onclick="exportData('excel')">
                    <i class="bi bi-file-earmark-excel me-1"></i>
                    Exportar Excel
                </button>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="action" value="cleanup">
                    <button type="submit" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-trash me-1"></i>
                        Limpiar Registros
                    </button>
                </form>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Alertas de Seguridad -->
        <?php if (!empty($securityAlerts)): ?>
        <div class="mb-4">
            <?php foreach ($securityAlerts as $alert): ?>
                <div class="alert alert-<?php echo $alert['type']; ?> border-start border-<?php echo $alert['type']; ?> border-4 alert-dismissible fade show" role="alert">
                    <i class="<?php echo $alert['icon']; ?> me-2"></i>
                    <strong>Alerta de Seguridad:</strong> <?php echo htmlspecialchars($alert['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Filtros y Búsqueda -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" id="filterForm" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label mb-1 small text-muted">
                            <i class="bi bi-calendar-range me-1"></i>
                            Período de Tiempo
                        </label>
                        <select name="time_filter" class="form-select" onchange="document.getElementById('filterForm').submit()">
                            <option value="24" <?php echo $timeFilter == '24' ? 'selected' : ''; ?>>Últimas 24 horas</option>
                            <option value="168" <?php echo $timeFilter == '168' ? 'selected' : ''; ?>>Últimos 7 días</option>
                            <option value="720" <?php echo $timeFilter == '720' ? 'selected' : ''; ?>>Últimos 30 días</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label mb-1 small text-muted">
                            <i class="bi bi-search me-1"></i>
                            Buscar por Usuario o IP
                        </label>
                        <input type="text" name="search" class="form-control" placeholder="Ingrese usuario o dirección IP..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-funnel me-1"></i>
                            Aplicar Filtros
                        </button>
                    </div>
                </form>
            </div>
        </div>

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
                    IPs con Más Intentos Fallidos (<?php echo $timeFilterHours; ?>h)
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
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-person-x-fill me-2"></i>
                    Usuarios con Más Intentos Fallidos (<?php echo $timeFilterHours; ?>h)
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover" id="tableFailed Users">
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

        <!-- Logins Exitosos Recientes -->
        <?php if (!empty($successfulLogins)): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                    Logins Exitosos Recientes (<?php echo $timeFilterHours; ?>h)
                </h5>
                <span class="badge bg-success"><?php echo count($successfulLogins); ?> registros</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover" id="tableSuccessful">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Nombre</th>
                                <th>Rol</th>
                                <th>Fecha y Hora</th>
                                <th>IP Address</th>
                                <th>Navegador</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $filteredLogins = $successfulLogins;
                            if (!empty($searchTerm)) {
                                $filteredLogins = array_filter($successfulLogins, function($login) use ($searchTerm) {
                                    return stripos($login['username'], $searchTerm) !== false || 
                                           stripos($login['ip_address'], $searchTerm) !== false;
                                });
                            }
                            foreach ($filteredLogins as $login): 
                            ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($login['username']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($login['nombre_completo'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $login['rol'] === 'admin' ? 'danger' : 'info'; ?>">
                                            <?php echo $login['rol'] === 'admin' ? 'Admin' : 'Técnico'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small><?php echo date('d/m/Y H:i:s', strtotime($login['attempt_time'])); ?></small>
                                    </td>
                                    <td><code><?php echo htmlspecialchars($login['ip_address']); ?></code></td>
                                    <td>
                                        <small class="text-muted">
                                            <?php 
                                            $ua = $login['user_agent'] ?? '';
                                            if (stripos($ua, 'Chrome') !== false) echo '🌐 Chrome';
                                            elseif (stripos($ua, 'Firefox') !== false) echo '🦊 Firefox';
                                            elseif (stripos($ua, 'Safari') !== false) echo '🧭 Safari';
                                            elseif (stripos($ua, 'Edge') !== false) echo '🔷 Edge';
                                            else echo '🌐 Otro';
                                            ?>
                                        </small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Historial de Desbloqueos Manuales -->
        <?php if (!empty($unlockHistory)): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-unlock-fill text-primary me-2"></i>
                    Historial de Desbloqueos Manuales (Últimos 7 días)
                </h5>
                <span class="badge bg-primary"><?php echo count($unlockHistory); ?> registros</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover" id="tableUnlocks">
                        <thead>
                            <tr>
                                <th>Cuenta Desbloqueada</th>
                                <th>Nombre</th>
                                <th>Desbloqueada por</th>
                                <th>Fecha y Hora</th>
                                <th>IP del Admin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($unlockHistory as $unlock): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($unlock['cuenta_desbloqueada'] ?? 'N/A'); ?></strong></td>
                                    <td><?php echo htmlspecialchars($unlock['nombre_cuenta'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge bg-danger">
                                            <?php echo htmlspecialchars($unlock['admin_nombre'] ?? $unlock['admin_username'] ?? 'Sistema'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small><?php echo date('d/m/Y H:i:s', strtotime($unlock['fecha'])); ?></small>
                                    </td>
                                    <td><code><?php echo htmlspecialchars($unlock['ip_address'] ?? 'N/A'); ?></code></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

<script>
// Función para exportar datos a Excel
function exportData(format) {
    if (format === 'excel') {
        // Crear contenido CSV
        let csv = 'data:text/csv;charset=utf-8,';
        
        // Agregar estadísticas
        csv += '=== ESTADÍSTICAS DE SEGURIDAD ===\n';
        csv += 'Período,Total Intentos,Exitosos,Fallidos,Cuentas Bloqueadas\n';
        csv += '<?php echo $timeFilterHours; ?>h,<?php echo $stats["attempts"]["total"] ?? 0; ?>,<?php echo $stats["attempts"]["successful"] ?? 0; ?>,<?php echo $stats["attempts"]["failed"] ?? 0; ?>,<?php echo count($blockedAccounts); ?>\n\n';
        
        // Agregar cuentas bloqueadas
        csv += '=== CUENTAS BLOQUEADAS ===\n';
        csv += 'Usuario,Nombre,Rol,Intentos Fallidos,Bloqueado Hasta,Último Intento\n';
        <?php foreach ($blockedAccounts as $account): ?>
        csv += '<?php echo addslashes($account["username"]); ?>,<?php echo addslashes($account["nombre_completo"]); ?>,<?php echo $account["rol"]; ?>,<?php echo $account["intentos_fallidos"]; ?>,<?php echo $account["bloqueado_hasta"] ?? "N/A"; ?>,<?php echo $account["ultimo_intento"] ? date("d/m/Y H:i", strtotime($account["ultimo_intento"])) : "N/A"; ?>\n';
        <?php endforeach; ?>
        csv += '\n';
        
        // Agregar logins exitosos
        csv += '=== LOGINS EXITOSOS RECIENTES ===\n';
        csv += 'Usuario,Nombre,Rol,Fecha y Hora,IP Address\n';
        <?php foreach ($successfulLogins as $login): ?>
        csv += '<?php echo addslashes($login["username"]); ?>,<?php echo addslashes($login["nombre_completo"] ?? "N/A"); ?>,<?php echo $login["rol"] ?? "N/A"; ?>,<?php echo date("d/m/Y H:i:s", strtotime($login["attempt_time"])); ?>,<?php echo $login["ip_address"]; ?>\n';
        <?php endforeach; ?>
        csv += '\n';
        
        // Agregar historial de desbloqueos
        csv += '=== HISTORIAL DE DESBLOQUEOS ===\n';
        csv += 'Cuenta,Nombre,Desbloqueada por,Fecha y Hora,IP del Admin\n';
        <?php foreach ($unlockHistory as $unlock): ?>
        csv += '<?php echo addslashes($unlock["cuenta_desbloqueada"] ?? "N/A"); ?>,<?php echo addslashes($unlock["nombre_cuenta"] ?? "N/A"); ?>,<?php echo addslashes($unlock["admin_nombre"] ?? "N/A"); ?>,<?php echo date("d/m/Y H:i:s", strtotime($unlock["fecha"])); ?>,<?php echo $unlock["ip_address"] ?? "N/A"; ?>\n';
        <?php endforeach; ?>
        
        // Descargar archivo
        const encodedUri = encodeURI(csv);
        const link = document.createElement('a');
        link.setAttribute('href', encodedUri);
        link.setAttribute('download', 'seguridad_gaselag_<?php echo date("Y-m-d_His"); ?>.csv');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        // Mostrar notificación
        alert('✅ Exportación completada exitosamente');
    }
}

// Agregar capacidad de búsqueda en tablas (opcional)
function filterTable(tableId, searchText) {
    const table = document.getElementById(tableId);
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    
    for (let row of rows) {
        const text = row.textContent.toLowerCase();
        if (text.includes(searchText.toLowerCase())) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>