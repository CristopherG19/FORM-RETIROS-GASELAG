<?php
require_once '../config/database.php';

// Verificar autenticación y rol de administrador
requireRole(['admin']);

$currentUser = getCurrentUser();
$error = '';
$success = $_GET['success'] ?? '';

// Procesar subida de foto
function subirFotoPerfil($file) {
    $uploadDir = '../uploads/perfiles/';
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Error al subir el archivo");
    }
    
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception("Solo se permiten imágenes (JPG, PNG, GIF)");
    }
    
    if ($file['size'] > $maxSize) {
        throw new Exception("El archivo no debe superar 5MB");
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $nombreArchivo = uniqid('perfil_') . '.' . $extension;
    $rutaDestino = $uploadDir . $nombreArchivo;
    
    if (!move_uploaded_file($file['tmp_name'], $rutaDestino)) {
        throw new Exception("Error al guardar el archivo");
    }
    
    return 'uploads/perfiles/' . $nombreArchivo;
}

// CREAR NUEVO USUARIO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'crear') {
    try {
        $pdo = getConnection();
        
        // Datos básicos
        $username = trim($_POST['username']);
        $password = 'password'; // Contraseña inicial automática
        $nombre_completo = trim($_POST['nombre_completo']);
        $email = trim($_POST['email']);
        $rol = $_POST['rol'];
        
        // Datos adicionales
        $telefono = trim($_POST['telefono'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? null;
        $documento_identidad = trim($_POST['documento_identidad'] ?? '');
        $cargo = trim($_POST['cargo'] ?? '');
        $fecha_ingreso = $_POST['fecha_ingreso'] ?? null;
        $notas = trim($_POST['notas'] ?? '');
        $estado_laboral = $_POST['estado_laboral'] ?? 'activo';
        
        // Validar campos obligatorios
        if (empty($username) || empty($nombre_completo)) {
            throw new Exception("Complete los campos obligatorios: Usuario y Nombre Completo");
        }
        
        // Verificar si el usuario ya existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            throw new Exception("El nombre de usuario ya existe");
        }
        
        // Procesar foto si se subió
        $foto_perfil = null;
        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] !== UPLOAD_ERR_NO_FILE) {
            $foto_perfil = subirFotoPerfil($_FILES['foto_perfil']);
        }
        
        // Hash de contraseña
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 8]);
        
        // Insertar usuario
        $sql = "INSERT INTO usuarios (
            username, password, nombre_completo, email, rol, 
            telefono, direccion, fecha_nacimiento, documento_identidad, 
            cargo, fecha_ingreso, notas, foto_perfil, estado_laboral, 
            estado, force_password_change
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'activo', TRUE)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $username, $hashedPassword, $nombre_completo, $email, $rol,
            $telefono, $direccion, $fecha_nacimiento, $documento_identidad,
            $cargo, $fecha_ingreso, $notas, $foto_perfil, $estado_laboral
        ]);
        
        $tipoCredencial = $rol === 'admin' ? 'contraseña' : 'PIN';
        $mensaje = "Usuario creado exitosamente. Credenciales: Usuario: {$username} | Contraseña temporal: password (debe cambiarla a su {$tipoCredencial} personal en el primer login)";
        header('Location: gestion_usuarios_mejorado.php?success=' . urlencode($mensaje));
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// EDITAR USUARIO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'editar') {
    try {
        $pdo = getConnection();
        
        $id = (int)$_POST['id'];
        $username = trim($_POST['username']);
        $nombre_completo = trim($_POST['nombre_completo']);
        $email = trim($_POST['email']);
        $rol = $_POST['rol'];
        
        // Datos adicionales
        $telefono = trim($_POST['telefono'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $fecha_nacimiento = $_POST['fecha_nacimiento'] ?: null;
        $documento_identidad = trim($_POST['documento_identidad'] ?? '');
        $cargo = trim($_POST['cargo'] ?? '');
        $fecha_ingreso = $_POST['fecha_ingreso'] ?: null;
        $notas = trim($_POST['notas'] ?? '');
        $estado_laboral = $_POST['estado_laboral'] ?? 'activo';
        
        // Validar campos obligatorios
        if (empty($username) || empty($nombre_completo)) {
            throw new Exception("Complete los campos obligatorios: Usuario y Nombre Completo");
        }
        
        // Verificar si el usuario ya existe (excepto el actual)
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = ? AND id != ?");
        $stmt->execute([$username, $id]);
        if ($stmt->fetch()) {
            throw new Exception("El nombre de usuario ya existe");
        }
        
        // Obtener foto actual
        $stmt = $pdo->prepare("SELECT foto_perfil FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        $usuarioActual = $stmt->fetch();
        $foto_perfil = $usuarioActual['foto_perfil'];
        
        // Eliminar foto si se marcó
        if (isset($_POST['eliminar_foto']) && $_POST['eliminar_foto'] == '1' && $foto_perfil) {
            if (file_exists('../' . $foto_perfil)) {
                unlink('../' . $foto_perfil);
            }
            $foto_perfil = null;
        }
        
        // Procesar nueva foto si se subió
        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] !== UPLOAD_ERR_NO_FILE) {
            // Eliminar foto anterior si existe
            if ($foto_perfil && file_exists('../' . $foto_perfil)) {
                unlink('../' . $foto_perfil);
            }
            $foto_perfil = subirFotoPerfil($_FILES['foto_perfil']);
        }
        
        // Actualizar usuario
        $sql = "UPDATE usuarios SET 
            username = ?, nombre_completo = ?, email = ?, rol = ?,
            telefono = ?, direccion = ?, fecha_nacimiento = ?, documento_identidad = ?,
            cargo = ?, fecha_ingreso = ?, notas = ?, foto_perfil = ?, estado_laboral = ?
            WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $username, $nombre_completo, $email, $rol,
            $telefono, $direccion, $fecha_nacimiento, $documento_identidad,
            $cargo, $fecha_ingreso, $notas, $foto_perfil, $estado_laboral,
            $id
        ]);
        
        header('Location: gestion_usuarios_mejorado.php?success=' . urlencode('Usuario actualizado exitosamente'));
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// RESTABLECER CONTRASEÑA
if (isset($_GET['action']) && $_GET['action'] === 'reset_password' && isset($_GET['id'])) {
    try {
        $userId = (int)$_GET['id'];
        $pdo = getConnection();
        
        // Generar contraseña temporal
        $tempPassword = 'Temp' . rand(1000, 9999) . '!';
        $hashedPassword = password_hash($tempPassword, PASSWORD_BCRYPT, ['cost' => 8]);
        
        $stmt = $pdo->prepare("UPDATE usuarios SET password = ?, force_password_change = TRUE WHERE id = ?");
        $stmt->execute([$hashedPassword, $userId]);
        
        header('Location: gestion_usuarios_mejorado.php?success=' . urlencode('Contraseña restablecida a: ' . $tempPassword));
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// TOGGLE ESTADO
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    try {
        $userId = (int)$_GET['id'];
        $pdo = getConnection();
        
        $stmt = $pdo->prepare("SELECT estado FROM usuarios WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        $newStatus = $user['estado'] === 'activo' ? 'inactivo' : 'activo';
        
        $stmt = $pdo->prepare("UPDATE usuarios SET estado = ? WHERE id = ?");
        $stmt->execute([$newStatus, $userId]);
        
        header('Location: gestion_usuarios_mejorado.php?success=' . urlencode('Estado actualizado'));
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Obtener lista de usuarios
$pdo = getConnection();
$stmt = $pdo->query("SELECT * FROM usuarios ORDER BY created_at DESC");
$usuarios = $stmt->fetchAll();

$pageTitle = 'Gestión de Usuarios - Sistema GASELAG';
require_once '../includes/header.php';
?>

<style>
    .avatar {
        width: 80px;
        height: 80px;
        object-fit: cover;
    }
    .avatar-default {
        width: 80px;
        height: 80px;
        font-size: 2rem;
    }
</style>

<div class="container my-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="bi bi-people-fill me-2"></i>Gestión de Usuarios</h2>
            <p class="text-muted">Administra el personal del sistema</p>
        </div>
        <div class="col-md-4 text-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#crearUsuarioModal">
                <i class="bi bi-person-plus me-2"></i>Nuevo Usuario
            </button>
        </div>
    </div>

        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Lista de usuarios en tarjetas -->
        <div class="row g-4">
            <?php foreach ($usuarios as $usuario): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body text-center">
                        <!-- Foto de perfil -->
                        <?php if ($usuario['foto_perfil']): ?>
                            <img src="../<?php echo htmlspecialchars($usuario['foto_perfil']); ?>" 
                                 alt="Foto" class="avatar mb-3 rounded-circle border border-3 border-white shadow">
                        <?php else: ?>
                            <div class="avatar-default mb-3 mx-auto rounded-circle bg-gradient bg-primary text-white d-flex align-items-center justify-content-center fw-bold shadow">
                                <?php echo strtoupper(substr($usuario['nombre_completo'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>

                        <!-- Nombre y cargo -->
                        <h5 class="card-title mb-1"><?php echo htmlspecialchars($usuario['nombre_completo']); ?></h5>
                        <p class="text-muted small mb-2">
                            <?php echo htmlspecialchars($usuario['cargo'] ?: 'Sin cargo asignado'); ?>
                        </p>

                        <!-- Badges -->
                        <div class="mb-3">
                            <span class="badge bg-<?php echo $usuario['rol'] === 'admin' ? 'danger' : 'primary'; ?> me-1">
                                <i class="bi bi-<?php echo $usuario['rol'] === 'admin' ? 'shield-check' : 'wrench-adjustable'; ?>"></i>
                                <?php echo ucfirst($usuario['rol']); ?>
                            </span>
                            <span class="badge bg-<?php echo $usuario['estado'] === 'activo' ? 'success' : 'secondary'; ?>">
                                <?php echo ucfirst($usuario['estado']); ?>
                            </span>
                            <?php if ($usuario['estado_laboral'] && $usuario['estado_laboral'] !== 'activo'): ?>
                            <span class="badge bg-warning text-dark">
                                <?php echo ucfirst($usuario['estado_laboral']); ?>
                            </span>
                            <?php endif; ?>
                        </div>

                        <!-- Información adicional -->
                        <div class="text-start small text-muted mb-3">
                            <?php if ($usuario['telefono']): ?>
                            <div><i class="bi bi-telephone me-2"></i><?php echo htmlspecialchars($usuario['telefono']); ?></div>
                            <?php endif; ?>
                            <?php if ($usuario['email']): ?>
                            <div><i class="bi bi-envelope me-2"></i><?php echo htmlspecialchars($usuario['email']); ?></div>
                            <?php endif; ?>
                            <div><i class="bi bi-person-badge me-2"></i><?php echo htmlspecialchars($usuario['username']); ?></div>
                        </div>

                        <!-- Botones de acción -->
                        <div class="btn-group w-100" role="group">
                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#modalVerPerfil<?php echo $usuario['id']; ?>"
                                    title="Ver perfil completo">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-info"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalEditarUsuario<?php echo $usuario['id']; ?>"
                                    title="Editar información">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-warning"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalResetPassword<?php echo $usuario['id']; ?>"
                                    title="Restablecer contraseña">
                                <i class="bi bi-key"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-<?php echo $usuario['estado'] === 'activo' ? 'danger' : 'success'; ?>"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalToggleEstado<?php echo $usuario['id']; ?>"
                                    title="<?php echo $usuario['estado'] === 'activo' ? 'Desactivar' : 'Activar'; ?> usuario">
                                <i class="bi bi-<?php echo $usuario['estado'] === 'activo' ? 'lock' : 'unlock'; ?>"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modales para este usuario -->
            
            <!-- Modal: Ver Perfil -->
            <div class="modal fade" id="modalVerPerfil<?php echo $usuario['id']; ?>" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title"><i class="bi bi-person-circle me-2"></i>Perfil Completo</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <!-- Foto de perfil -->
                                <div class="col-md-4 text-center mb-3">
                                    <?php if ($usuario['foto_perfil']): ?>
                                        <img src="../<?php echo htmlspecialchars($usuario['foto_perfil']); ?>" 
                                             alt="Foto" class="img-fluid rounded-circle shadow" style="max-width: 200px;">
                                    <?php else: ?>
                                        <div class="bg-primary text-white rounded-circle shadow mx-auto d-flex align-items-center justify-content-center" 
                                             style="width: 200px; height: 200px; font-size: 4rem; font-weight: bold;">
                                            <?php echo strtoupper(substr($usuario['nombre_completo'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Información del usuario -->
                                <div class="col-md-8">
                                    <h4 class="mb-3"><?php echo htmlspecialchars($usuario['nombre_completo']); ?></h4>
                                    
                                    <div class="mb-3">
                                        <span class="badge bg-<?php echo $usuario['rol'] === 'admin' ? 'danger' : 'primary'; ?> me-1">
                                            <i class="bi bi-<?php echo $usuario['rol'] === 'admin' ? 'shield-check' : 'wrench-adjustable'; ?>"></i>
                                            <?php echo ucfirst($usuario['rol']); ?>
                                        </span>
                                        <span class="badge bg-<?php echo $usuario['estado'] === 'activo' ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($usuario['estado']); ?>
                                        </span>
                                        <?php if ($usuario['estado_laboral'] && $usuario['estado_laboral'] !== 'activo'): ?>
                                        <span class="badge bg-warning text-dark">
                                            <?php echo ucfirst($usuario['estado_laboral']); ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>

                                    <table class="table table-sm">
                                        <tr>
                                            <th width="40%"><i class="bi bi-person-badge me-2"></i>Usuario:</th>
                                            <td><?php echo htmlspecialchars($usuario['username']); ?></td>
                                        </tr>
                                        <?php if ($usuario['documento_identidad']): ?>
                                        <tr>
                                            <th><i class="bi bi-card-text me-2"></i>Documento:</th>
                                            <td><?php echo htmlspecialchars($usuario['documento_identidad']); ?></td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php if ($usuario['email']): ?>
                                        <tr>
                                            <th><i class="bi bi-envelope me-2"></i>Email:</th>
                                            <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php if ($usuario['telefono']): ?>
                                        <tr>
                                            <th><i class="bi bi-telephone me-2"></i>Teléfono:</th>
                                            <td><?php echo htmlspecialchars($usuario['telefono']); ?></td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php if ($usuario['fecha_nacimiento']): ?>
                                        <tr>
                                            <th><i class="bi bi-calendar me-2"></i>F. Nacimiento:</th>
                                            <td><?php echo date('d/m/Y', strtotime($usuario['fecha_nacimiento'])); ?></td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php if ($usuario['cargo']): ?>
                                        <tr>
                                            <th><i class="bi bi-briefcase me-2"></i>Cargo:</th>
                                            <td><?php echo htmlspecialchars($usuario['cargo']); ?></td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php if ($usuario['fecha_ingreso']): ?>
                                        <tr>
                                            <th><i class="bi bi-calendar-check me-2"></i>F. Ingreso:</th>
                                            <td><?php echo date('d/m/Y', strtotime($usuario['fecha_ingreso'])); ?></td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php if ($usuario['direccion']): ?>
                                        <tr>
                                            <th><i class="bi bi-geo-alt me-2"></i>Dirección:</th>
                                            <td><?php echo htmlspecialchars($usuario['direccion']); ?></td>
                                        </tr>
                                        <?php endif; ?>
                                    </table>
                                    
                                    <?php if ($usuario['notas']): ?>
                                    <div class="alert alert-info mb-0">
                                        <strong><i class="bi bi-sticky me-2"></i>Notas:</strong><br>
                                        <?php echo nl2br(htmlspecialchars($usuario['notas'])); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal: Editar Usuario -->
            <div class="modal fade" id="modalEditarUsuario<?php echo $usuario['id']; ?>" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="editar">
                            <input type="hidden" name="id" value="<?php echo $usuario['id']; ?>">
                            <div class="modal-header bg-info text-white">
                                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Editar Usuario</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3">
                                    <!-- Datos de Acceso -->
                                    <div class="col-12">
                                        <h6 class="border-bottom pb-2"><i class="bi bi-key me-2"></i>Datos de Acceso</h6>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Usuario / DNI *</label>
                                        <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($usuario['username']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Rol *</label>
                                        <select class="form-select" name="rol" required>
                                            <option value="user" <?php echo $usuario['rol'] === 'user' ? 'selected' : ''; ?>>Técnico</option>
                                            <option value="admin" <?php echo $usuario['rol'] === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                                        </select>
                                    </div>

                                    <!-- Datos Personales -->
                                    <div class="col-12 mt-4">
                                        <h6 class="border-bottom pb-2"><i class="bi bi-person me-2"></i>Datos Personales</h6>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label">Nombre Completo *</label>
                                        <input type="text" class="form-control" name="nombre_completo" value="<?php echo htmlspecialchars($usuario['nombre_completo']); ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Documento</label>
                                        <input type="text" class="form-control" name="documento_identidad" value="<?php echo htmlspecialchars($usuario['documento_identidad'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Fecha de Nacimiento</label>
                                        <input type="date" class="form-control" name="fecha_nacimiento" value="<?php echo htmlspecialchars($usuario['fecha_nacimiento'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Teléfono</label>
                                        <input type="tel" class="form-control" name="telefono" value="<?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Dirección</label>
                                        <textarea class="form-control" name="direccion" rows="2"><?php echo htmlspecialchars($usuario['direccion'] ?? ''); ?></textarea>
                                    </div>

                                    <!-- Datos Laborales -->
                                    <div class="col-12 mt-4">
                                        <h6 class="border-bottom pb-2"><i class="bi bi-briefcase me-2"></i>Datos Laborales</h6>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Cargo</label>
                                        <input type="text" class="form-control" name="cargo" value="<?php echo htmlspecialchars($usuario['cargo'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Fecha de Ingreso</label>
                                        <input type="date" class="form-control" name="fecha_ingreso" value="<?php echo htmlspecialchars($usuario['fecha_ingreso'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Estado Laboral</label>
                                        <select class="form-select" name="estado_laboral">
                                            <option value="activo" <?php echo ($usuario['estado_laboral'] ?? 'activo') === 'activo' ? 'selected' : ''; ?>>Activo</option>
                                            <option value="vacaciones" <?php echo ($usuario['estado_laboral'] ?? '') === 'vacaciones' ? 'selected' : ''; ?>>Vacaciones</option>
                                            <option value="licencia" <?php echo ($usuario['estado_laboral'] ?? '') === 'licencia' ? 'selected' : ''; ?>>Licencia</option>
                                            <option value="inactivo" <?php echo ($usuario['estado_laboral'] ?? '') === 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Notas</label>
                                        <textarea class="form-control" name="notas" rows="2"><?php echo htmlspecialchars($usuario['notas'] ?? ''); ?></textarea>
                                    </div>

                                    <!-- Foto de Perfil -->
                                    <div class="col-12 mt-4">
                                        <h6 class="border-bottom pb-2"><i class="bi bi-camera me-2"></i>Foto de Perfil</h6>
                                    </div>
                                    <?php if ($usuario['foto_perfil']): ?>
                                    <div class="col-12">
                                        <img src="../<?php echo htmlspecialchars($usuario['foto_perfil']); ?>" 
                                             alt="Foto actual" class="img-thumbnail" style="max-width: 150px;">
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" name="eliminar_foto" value="1" id="eliminarFoto<?php echo $usuario['id']; ?>">
                                            <label class="form-check-label" for="eliminarFoto<?php echo $usuario['id']; ?>">
                                                Eliminar foto actual
                                            </label>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    <div class="col-12">
                                        <input type="file" class="form-control" name="foto_perfil" accept="image/*">
                                        <small class="text-muted">Formatos: JPG, PNG, GIF. Tamaño máximo: 5MB</small>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-info">
                                    <i class="bi bi-save me-2"></i>Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal: Restablecer Contraseña -->
            <div class="modal fade" id="modalResetPassword<?php echo $usuario['id']; ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-warning text-dark">
                            <h5 class="modal-title"><i class="bi bi-key me-2"></i>Restablecer Contraseña</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>¿Está seguro?</strong>
                            </div>
                            <p>Se generará una contraseña temporal para:</p>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($usuario['nombre_completo']); ?></h6>
                                    <p class="text-muted mb-0"><small>Usuario: <?php echo htmlspecialchars($usuario['username']); ?></small></p>
                                </div>
                            </div>
                            <p class="mt-3 mb-0">El usuario deberá cambiar esta contraseña en su próximo inicio de sesión.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <a href="?action=reset_password&id=<?php echo $usuario['id']; ?>" class="btn btn-warning">
                                <i class="bi bi-key me-2"></i>Restablecer
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal: Cambiar Estado -->
            <div class="modal fade" id="modalToggleEstado<?php echo $usuario['id']; ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-<?php echo $usuario['estado'] === 'activo' ? 'danger' : 'success'; ?> text-white">
                            <h5 class="modal-title">
                                <i class="bi bi-<?php echo $usuario['estado'] === 'activo' ? 'lock' : 'unlock'; ?> me-2"></i>
                                <?php echo $usuario['estado'] === 'activo' ? 'Desactivar' : 'Activar'; ?> Usuario
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-<?php echo $usuario['estado'] === 'activo' ? 'danger' : 'success'; ?>">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>¿Está seguro?</strong>
                            </div>
                            <p>Va a <strong><?php echo $usuario['estado'] === 'activo' ? 'DESACTIVAR' : 'ACTIVAR'; ?></strong> la cuenta de:</p>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($usuario['nombre_completo']); ?></h6>
                                    <p class="text-muted mb-0"><small>Usuario: <?php echo htmlspecialchars($usuario['username']); ?></small></p>
                                </div>
                            </div>
                            <?php if ($usuario['estado'] === 'activo'): ?>
                            <p class="mt-3 mb-0 text-danger">
                                <i class="bi bi-exclamation-circle me-2"></i>
                                El usuario no podrá iniciar sesión hasta que se reactive su cuenta.
                            </p>
                            <?php else: ?>
                            <p class="mt-3 mb-0 text-success">
                                <i class="bi bi-check-circle me-2"></i>
                                El usuario podrá iniciar sesión nuevamente.
                            </p>
                            <?php endif; ?>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <a href="?action=toggle&id=<?php echo $usuario['id']; ?>" 
                               class="btn btn-<?php echo $usuario['estado'] === 'activo' ? 'danger' : 'success'; ?>">
                                <i class="bi bi-<?php echo $usuario['estado'] === 'activo' ? 'lock' : 'unlock'; ?> me-2"></i>
                                <?php echo $usuario['estado'] === 'activo' ? 'Desactivar' : 'Activar'; ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal: Crear Usuario -->
    <div class="modal fade" id="crearUsuarioModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="crear">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Crear Nuevo Usuario</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <!-- Sección: Datos de Acceso -->
                            <div class="col-12">
                                <h6 class="border-bottom pb-2"><i class="bi bi-key me-2"></i>Datos de Acceso</h6>
                            </div>
                            <div class="col-12">
                                <div class="alert alert-info mb-3">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <strong>Contraseña inicial automática:</strong> El sistema asignará la contraseña temporal <code>password</code> que el usuario deberá cambiar en su primer inicio de sesión.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Usuario / DNI *</label>
                                <input type="text" class="form-control" name="username" required>
                                <small class="text-muted">Este será el usuario para iniciar sesión</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Rol *</label>
                                <select class="form-select" name="rol" required>
                                    <option value="user">Técnico (usará PIN numérico)</option>
                                    <option value="admin">Administrador (usará contraseña compleja)</option>
                                </select>
                            </div>

                            <!-- Sección: Datos Personales -->
                            <div class="col-12 mt-4">
                                <h6 class="border-bottom pb-2"><i class="bi bi-person me-2"></i>Datos Personales</h6>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Nombre Completo *</label>
                                <input type="text" class="form-control" name="nombre_completo" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Documento</label>
                                <input type="text" class="form-control" name="documento_identidad">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fecha de Nacimiento</label>
                                <input type="date" class="form-control" name="fecha_nacimiento">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Teléfono</label>
                                <input type="tel" class="form-control" name="telefono">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Dirección</label>
                                <textarea class="form-control" name="direccion" rows="2"></textarea>
                            </div>

                            <!-- Sección: Datos Laborales -->
                            <div class="col-12 mt-4">
                                <h6 class="border-bottom pb-2"><i class="bi bi-briefcase me-2"></i>Datos Laborales</h6>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Cargo</label>
                                <input type="text" class="form-control" name="cargo" placeholder="Ej: Técnico de Campo">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fecha de Ingreso</label>
                                <input type="date" class="form-control" name="fecha_ingreso">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Estado Laboral</label>
                                <select class="form-select" name="estado_laboral">
                                    <option value="activo">Activo</option>
                                    <option value="vacaciones">Vacaciones</option>
                                    <option value="licencia">Licencia</option>
                                    <option value="inactivo">Inactivo</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notas</label>
                                <textarea class="form-control" name="notas" rows="2" placeholder="Comentarios adicionales..."></textarea>
                            </div>

                            <!-- Sección: Foto de Perfil -->
                            <div class="col-12 mt-4">
                                <h6 class="border-bottom pb-2"><i class="bi bi-camera me-2"></i>Foto de Perfil</h6>
                            </div>
                            <div class="col-12">
                                <input type="file" class="form-control" name="foto_perfil" accept="image/*">
                                <small class="text-muted">Formatos: JPG, PNG, GIF. Tamaño máximo: 5MB</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i>Crear Usuario
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
