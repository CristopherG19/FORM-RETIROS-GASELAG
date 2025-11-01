<?php
require_once '../config/database.php';

// Verificar autenticación
requireRole(['admin', 'user']);

$currentUser = getCurrentUser();
$isFirstLogin = isset($_GET['first_login']) && $_GET['first_login'] == 1;
$isAdmin = isAdmin();

// Si no es primer login obligatorio y no fuerza cambio, permitir cancelar
$canCancel = !$isFirstLogin && !$currentUser['force_password_change'];

$message = '';
$messageType = '';
$errors = [];
$strengthInfo = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    try {
        $pdo = getConnection();
        
        // Verificar contraseña actual
        $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if (!password_verify($currentPassword, $user['password'])) {
            $errors[] = "La contraseña actual es incorrecta";
        } else {
            // Validar nueva contraseña según el rol
            if ($isAdmin) {
                $validation = validatePassword($newPassword, $_SESSION['username'], $currentUser['email']);
                $strengthInfo = $validation;
            } else {
                $validation = validatePIN($newPassword, $_SESSION['username']);
            }
            
            if (!$validation['valid']) {
                $errors = array_merge($errors, $validation['errors']);
            } else {
                // Verificar confirmación
                if ($newPassword !== $confirmPassword) {
                    $errors[] = "La nueva contraseña y la confirmación no coinciden";
                } else {
                    // Verificar que no sea igual a la actual
                    if ($currentPassword === $newPassword) {
                        $errors[] = $isAdmin ? "La nueva contraseña debe ser diferente a la actual" : "El nuevo PIN debe ser diferente al actual";
                    } else {
                        // Verificar historial (solo admins)
                        if ($isAdmin && isPasswordReused($_SESSION['user_id'], $newPassword)) {
                            $errors[] = "Esta contraseña ya fue utilizada anteriormente. Por favor, elija una diferente";
                        } else {
                            // Todo OK, actualizar contraseña
                            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                            
                            $pdo->beginTransaction();
                            
                            $stmt = $pdo->prepare("
                                UPDATE usuarios 
                                SET password = ?,
                                    password_changed_at = NOW(),
                                    force_password_change = FALSE
                                WHERE id = ?
                            ");
                            $stmt->execute([$newHash, $_SESSION['user_id']]);
                            
                            // Guardar en historial
                            if ($isAdmin) {
                                savePasswordToHistory($_SESSION['user_id'], $newHash);
                            }
                            
                            // Registrar en auditoría
                            logAudit(null, $_SESSION['user_id'], 'login', 
                                    $isAdmin ? "Contraseña cambiada" : "PIN cambiado");
                            
                            $pdo->commit();
                            
                            $message = $isAdmin ? "Contraseña cambiada exitosamente" : "PIN cambiado exitosamente";
                            $messageType = 'success';
                            
                            // Redirigir después de 2 segundos
                            header("refresh:2;url=../index.php");
                        }
                    }
                }
            }
        }
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $errors[] = "Error al cambiar la contraseña: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar <?php echo $isAdmin ? 'Contraseña' : 'PIN'; ?> - GASELAG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f5f7fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .change-password-card {
            max-width: 500px;
            margin: 2rem auto;
        }
        .strength-meter {
            height: 5px;
            border-radius: 3px;
            margin-top: 0.5rem;
            background-color: #e9ecef;
        }
        .strength-bar {
            height: 100%;
            border-radius: 3px;
            transition: all 0.3s ease;
        }
        .requirement-item {
            font-size: 0.875rem;
            padding: 0.25rem 0;
        }
        .requirement-item.met {
            color: #198754;
        }
        .requirement-item.not-met {
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-10 col-lg-8 col-xl-6">
                <div class="card shadow-sm border-0 change-password-card">
                    <div class="card-header bg-primary text-white py-3">
                        <h5 class="mb-0">
                            <i class="bi bi-key me-2"></i>
                            Cambiar <?php echo $isAdmin ? 'Contraseña' : 'PIN'; ?>
                        </h5>
                    </div>
                    
                    <div class="card-body p-4">
                        <?php if ($isFirstLogin || $currentUser['force_password_change']): ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>Cambio obligatorio:</strong> Debe cambiar su <?php echo $isAdmin ? 'contraseña' : 'PIN'; ?> 
                                antes de continuar por seguridad.
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $messageType; ?>">
                                <i class="bi bi-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>Errores de validación:</strong>
                                <ul class="mb-0 mt-2">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" id="changePasswordForm">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">
                                    <?php echo $isAdmin ? 'Contraseña Actual' : 'PIN Actual'; ?>
                                </label>
                                <input type="password" 
                                       class="form-control <?php echo $isAdmin ? '' : 'text-center'; ?>" 
                                       id="current_password" 
                                       name="current_password"
                                       <?php echo $isAdmin ? '' : 'inputmode="numeric" pattern="[0-9]*"'; ?>
                                       required
                                       autocomplete="current-password">
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">
                                    <?php echo $isAdmin ? 'Nueva Contraseña' : 'Nuevo PIN'; ?>
                                </label>
                                <input type="password" 
                                       class="form-control <?php echo $isAdmin ? '' : 'text-center'; ?>" 
                                       id="new_password" 
                                       name="new_password"
                                       <?php echo $isAdmin ? '' : 'inputmode="numeric" pattern="[0-9]*" maxlength="6"'; ?>
                                       required
                                       autocomplete="new-password">
                                
                                <?php if ($isAdmin): ?>
                                    <!-- Barra de fortaleza para admins -->
                                    <div class="strength-meter">
                                        <div class="strength-bar" id="strengthBar" style="width: 0%; background-color: #dc3545;"></div>
                                    </div>
                                    <small class="text-muted" id="strengthText">Fortaleza: Sin evaluar</small>
                                <?php else: ?>
                                    <small class="text-muted d-block mt-1">
                                        Ingrese un PIN de <?php echo PIN_MIN_LENGTH_USER; ?>-<?php echo PIN_MAX_LENGTH_USER; ?> dígitos
                                    </small>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label">
                                    Confirmar <?php echo $isAdmin ? 'Contraseña' : 'PIN'; ?>
                                </label>
                                <input type="password" 
                                       class="form-control <?php echo $isAdmin ? '' : 'text-center'; ?>" 
                                       id="confirm_password" 
                                       name="confirm_password"
                                       <?php echo $isAdmin ? '' : 'inputmode="numeric" pattern="[0-9]*" maxlength="6"'; ?>
                                       required
                                       autocomplete="new-password">
                            </div>
                            
                            <!-- Requisitos -->
                            <div class="card bg-light border-0 mb-4">
                                <div class="card-body py-3">
                                    <h6 class="card-title mb-2">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Requisitos:
                                    </h6>
                                    <?php if ($isAdmin): ?>
                                        <ul class="small mb-0 ps-3">
                                            <li>Mínimo <?php echo PASSWORD_MIN_LENGTH_ADMIN; ?> caracteres</li>
                                            <li>Al menos 1 mayúscula y 1 minúscula</li>
                                            <li>Al menos <?php echo PASSWORD_REQUIRE_NUMBERS_ADMIN; ?> números</li>
                                            <li>Al menos <?php echo PASSWORD_REQUIRE_SYMBOLS_ADMIN; ?> símbolos (!@#$%^&*)</li>
                                            <li>No usar palabras comunes (password, admin, etc.)</li>
                                            <li>No reutilizar las últimas <?php echo PASSWORD_HISTORY_COUNT; ?> contraseñas</li>
                                        </ul>
                                    <?php else: ?>
                                        <ul class="small mb-0 ps-3">
                                            <li>Entre <?php echo PIN_MIN_LENGTH_USER; ?> y <?php echo PIN_MAX_LENGTH_USER; ?> dígitos numéricos</li>
                                            <li>No usar secuencias (1234, 4321)</li>
                                            <li>No usar todos los dígitos iguales (0000, 1111)</li>
                                            <li>No usar PINs comunes (1234, 4321, 0000)</li>
                                            <li>No usar tu DNI como PIN</li>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-2"></i>
                                    Cambiar <?php echo $isAdmin ? 'Contraseña' : 'PIN'; ?>
                                </button>
                                <?php if ($canCancel): ?>
                                    <a href="../index.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-x-circle me-2"></i>
                                        Cancelar
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php if ($isAdmin): ?>
    <script>
        // Validación de fortaleza en tiempo real para admins
        const newPasswordInput = document.getElementById('new_password');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        
        newPasswordInput.addEventListener('input', function() {
            const password = this.value;
            const strength = calculateStrength(password);
            
            updateStrengthDisplay(strength);
        });
        
        function calculateStrength(password) {
            let strength = 0;
            
            // Longitud
            if (password.length >= <?php echo PASSWORD_MIN_LENGTH_ADMIN; ?>) strength += 20;
            else if (password.length >= 8) strength += 10;
            
            // Mayúsculas
            if (/[A-Z]/.test(password)) strength += 15;
            
            // Minúsculas
            if (/[a-z]/.test(password)) strength += 15;
            
            // Números
            const numbers = (password.match(/[0-9]/g) || []).length;
            if (numbers >= <?php echo PASSWORD_REQUIRE_NUMBERS_ADMIN; ?>) strength += 20;
            else if (numbers >= 1) strength += 10;
            
            // Símbolos
            const symbols = (password.match(/[!@#$%^&*()_+\-=\[\]{}|;:,.<>?]/g) || []).length;
            if (symbols >= <?php echo PASSWORD_REQUIRE_SYMBOLS_ADMIN; ?>) strength += 30;
            else if (symbols >= 1) strength += 15;
            
            return Math.min(100, strength);
        }
        
        function updateStrengthDisplay(strength) {
            let color, text;
            
            if (strength < 30) {
                color = '#dc3545';
                text = 'Muy Débil';
            } else if (strength < 50) {
                color = '#fd7e14';
                text = 'Débil';
            } else if (strength < 70) {
                color = '#ffc107';
                text = 'Aceptable';
            } else if (strength < 90) {
                color = '#0dcaf0';
                text = 'Fuerte';
            } else {
                color = '#198754';
                text = 'Muy Fuerte';
            }
            
            strengthBar.style.width = strength + '%';
            strengthBar.style.backgroundColor = color;
            strengthText.textContent = 'Fortaleza: ' + text;
            strengthText.style.color = color;
        }
    </script>
    <?php endif; ?>
</body>
</html>
