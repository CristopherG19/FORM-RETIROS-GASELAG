<?php
require_once '../config/database.php';

// Verificar autenticación y rol de administrador
requireRole(['admin']);

// Obtener información del usuario actual
$currentUser = getCurrentUser();

// Procesar acciones
$action = $_GET['action'] ?? '';
$userId = $_GET['id'] ?? 0;

// Procesar creación de nuevo usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $nombre_completo = trim($_POST['nombre_completo']);
    $email = trim($_POST['email']);
    $rol = trim($_POST['rol']);

    if (empty($username) || empty($password) || empty($nombre_completo)) {
        $error = "Por favor complete todos los campos obligatorios";
    } else {
        $pdo = getConnection();
        try {
            // Verificar si el usuario ya existe
            $checkSql = "SELECT id FROM usuarios WHERE username = :username";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->bindParam(':username', $username);
            $checkStmt->execute();

            if ($checkStmt->fetch()) {
                $error = "El nombre de usuario ya existe";
            } else {
                // Crear nuevo usuario
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $insertSql = "INSERT INTO usuarios (username, password, nombre_completo, email, rol, estado)
                             VALUES (:username, :password, :nombre_completo, :email, :rol, 'activo')";
                $insertStmt = $pdo->prepare($insertSql);
                $insertStmt->bindParam(':username', $username);
                $insertStmt->bindParam(':password', $hashedPassword);
                $insertStmt->bindParam(':nombre_completo', $nombre_completo);
                $insertStmt->bindParam(':email', $email);
                $insertStmt->bindParam(':rol', $rol);
                $insertStmt->execute();

                $success = "Usuario creado correctamente";
            }
        } catch (PDOException $e) {
            $error = "Error al crear usuario: " . $e->getMessage();
        }
    }
}

if ($action === 'toggle_status' && $userId) {
    $pdo = getConnection();
    try {
        // Obtener estado actual del usuario
        $sql = "SELECT estado FROM usuarios WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
        $user = $stmt->fetch();

        if ($user) {
            // Cambiar estado
            $newStatus = $user['estado'] === 'activo' ? 'inactivo' : 'activo';
            $updateSql = "UPDATE usuarios SET estado = :status, updated_at = NOW() WHERE id = :id";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->bindParam(':status', $newStatus);
            $updateStmt->bindParam(':id', $userId);
            $updateStmt->execute();

            $success = "Estado del usuario actualizado correctamente";
        }
    } catch (PDOException $e) {
        $error = "Error al actualizar usuario: " . $e->getMessage();
    }
}

if ($action === 'delete' && $userId && $userId != $currentUser['id']) {
    $pdo = getConnection();
    try {
        // Eliminar usuario
        $deleteSql = "DELETE FROM usuarios WHERE id = :id";
        $deleteStmt = $pdo->prepare($deleteSql);
        $deleteStmt->bindParam(':id', $userId);
        $deleteStmt->execute();

        $success = "Usuario eliminado correctamente";
    } catch (PDOException $e) {
        $error = "Error al eliminar usuario: " . $e->getMessage();
    }
}

// Obtener lista de usuarios
$pdo = getConnection();
try {
    $sql = "SELECT id, username, nombre_completo, email, rol, estado, ultimo_login, created_at
            FROM usuarios ORDER BY nombre_completo";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $usuarios = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error al obtener usuarios: " . $e->getMessage();
    $usuarios = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - GASELAG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .user-card {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .user-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .status-badge {
            font-size: 0.75rem;
        }
        .role-badge {
            font-size: 0.75rem;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Barra de navegación -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="../index.php">
                <i class="bi bi-speedometer2 me-2"></i>
                GASELAG
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <span class="navbar-text">
                            <i class="bi bi-person-circle me-1"></i>
                            <?php echo htmlspecialchars($currentUser['nombre_completo']); ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <span class="navbar-text">
                            <i class="bi bi-shield-check ms-3 me-1"></i>
                            <span class="badge bg-warning text-dark">Administrador</span>
                        </span>
                    </li>
                </ul>

                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-gear me-1"></i>Opciones
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../index.php">
                                <i class="bi bi-house me-2"></i>Panel Principal
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-1">
                            <i class="bi bi-people text-primary me-2"></i>
                            Gestión de Usuarios
                        </h2>
                        <p class="text-muted mb-0">Administrar usuarios y permisos del sistema</p>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="bi bi-person-plus me-2"></i>Agregar Usuario
                    </button>
                </div>
            </div>
        </div>

        <!-- Alertas -->
        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Lista de usuarios -->
        <div class="row">
            <?php foreach ($usuarios as $usuario): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card user-card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-start justify-content-between mb-3">
                                <div class="flex-grow-1">
                                    <h6 class="card-title mb-1">
                                        <i class="bi bi-person-circle text-primary me-2"></i>
                                        <?php echo htmlspecialchars($usuario['nombre_completo']); ?>
                                    </h6>
                                    <small class="text-muted">@<?php echo htmlspecialchars($usuario['username']); ?></small>
                                </div>
                                <div class="text-end">
                                    <?php if ($usuario['estado'] === 'activo'): ?>
                                        <span class="badge bg-success status-badge">
                                            <i class="bi bi-check-circle me-1"></i>Activo
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary status-badge">
                                            <i class="bi bi-x-circle me-1"></i>Inactivo
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <?php if ($usuario['rol'] === 'admin'): ?>
                                    <span class="badge bg-warning text-dark role-badge me-1">
                                        <i class="bi bi-shield-check me-1"></i>Administrador
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-info role-badge me-1">
                                        <i class="bi bi-wrench-adjustable me-1"></i>Técnico
                                    </span>
                                <?php endif; ?>
                            </div>

                            <?php if ($usuario['email']): ?>
                                <div class="mb-2">
                                    <small class="text-muted">
                                        <i class="bi bi-envelope me-1"></i>
                                        <?php echo htmlspecialchars($usuario['email']); ?>
                                    </small>
                                </div>
                            <?php endif; ?>

                            <div class="mb-2">
                                <small class="text-muted">
                                    <i class="bi bi-calendar me-1"></i>
                                    Creado: <?php echo date('d/m/Y', strtotime($usuario['created_at'])); ?>
                                </small>
                            </div>

                            <?php if ($usuario['ultimo_login']): ?>
                                <div class="mb-3">
                                    <small class="text-muted">
                                        <i class="bi bi-clock me-1"></i>
                                        Último acceso: <?php echo date('d/m/Y H:i', strtotime($usuario['ultimo_login'])); ?>
                                    </small>
                                </div>
                            <?php endif; ?>

                            <div class="d-flex gap-2 mt-3">
                                <?php if ($usuario['id'] !== $currentUser['id']): ?>
                                    <button class="btn btn-sm btn-outline-secondary flex-grow-1"
                                            onclick="toggleUserStatus(<?php echo $usuario['id']; ?>, '<?php echo $usuario['estado']; ?>')">
                                        <?php if ($usuario['estado'] === 'activo'): ?>
                                            <i class="bi bi-pause me-1"></i>Desactivar
                                        <?php else: ?>
                                            <i class="bi bi-play me-1"></i>Activar
                                        <?php endif; ?>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger"
                                            onclick="deleteUser(<?php echo $usuario['id']; ?>, '<?php echo htmlspecialchars($usuario['nombre_completo']); ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-success flex-grow-1" disabled>
                                        <i class="bi bi-person-check me-1"></i>Usuario Actual
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Modal para agregar usuario -->
        <div class="modal fade" id="addUserModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-person-plus me-2"></i>Agregar Usuario
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" action="" id="addUserForm">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="username" class="form-label">Usuario *</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña *</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="mb-3">
                                <label for="nombre_completo" class="form-label">Nombre Completo *</label>
                                <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>
                            <div class="mb-3">
                                <label for="rol" class="form-label">Rol *</label>
                                <select class="form-select" id="rol" name="rol" required>
                                    <option value="user">Técnico</option>
                                    <option value="admin">Administrador</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Agregar Usuario</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleUserStatus(userId, currentStatus) {
            const action = currentStatus === 'activo' ? 'desactivar' : 'activar';
            if (confirm(`¿Está seguro que desea ${action} este usuario?`)) {
                window.location.href = `?action=toggle_status&id=${userId}`;
            }
        }

        function deleteUser(userId, userName) {
            if (confirm(`¿Está seguro que desea eliminar al usuario "${userName}"?\n\nEsta acción no se puede deshacer.`)) {
                window.location.href = `?action=delete&id=${userId}`;
            }
        }

        // Limpiar formulario cuando se cierre el modal
        document.getElementById('addUserModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('addUserForm').reset();
        });

        // Recargar página después de mostrar mensaje de éxito
        <?php if (isset($success)): ?>
            setTimeout(function() {
                location.reload();
            }, 2000);
        <?php endif; ?>
    </script>
</body>
</html>
