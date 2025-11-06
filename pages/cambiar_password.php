<?php
require_once '../config/database.php';

requireRole(['admin', 'user']);

$currentUser = getCurrentUser();
$isAdmin = ($currentUser['rol'] === 'admin');
$isFirstLogin = ($_GET['first_login'] ?? 0) == 1 || $currentUser['primer_login'] == 1;
$canCancel = !$isFirstLogin && !$currentUser['force_password_change'];

// Variables para mensajes
$message = '';
$messageType = 'info';
$errors = [];
$redirecting = false;

// Procesar formulario de cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validaciones básicas
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $errors[] = 'Todos los campos son obligatorios';
    }
    
    if ($newPassword !== $confirmPassword) {
        $errors[] = 'La nueva contraseña y la confirmación no coinciden';
    }
    
    if (empty($errors)) {
        try {
            $pdo = getConnection();
            
            // Verificar contraseña actual
            $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE id = ?");
            $stmt->execute([$currentUser['id']]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($currentPassword, $user['password'])) {
                $errors[] = 'La contraseña actual es incorrecta';
            } else {
                // Validar nueva contraseña según el rol
                if ($isAdmin) {
                    // Validación para admin (contraseña compleja)
                    if (strlen($newPassword) < PASSWORD_MIN_LENGTH_ADMIN) {
                        $errors[] = 'La contraseña debe tener al menos ' . PASSWORD_MIN_LENGTH_ADMIN . ' caracteres';
                    }
                    if (!preg_match('/[A-Z]/', $newPassword)) {
                        $errors[] = 'La contraseña debe contener al menos una letra mayúscula';
                    }
                    if (!preg_match('/[a-z]/', $newPassword)) {
                        $errors[] = 'La contraseña debe contener al menos una letra minúscula';
                    }
                    if (preg_match_all('/[0-9]/', $newPassword) < PASSWORD_REQUIRE_NUMBERS_ADMIN) {
                        $errors[] = 'La contraseña debe contener al menos ' . PASSWORD_REQUIRE_NUMBERS_ADMIN . ' números';
                    }
                    if (preg_match_all('/[!@#$%^&*()_+\-=\[\]{}|;:,.<>?]/', $newPassword) < PASSWORD_REQUIRE_SYMBOLS_ADMIN) {
                        $errors[] = 'La contraseña debe contener al menos ' . PASSWORD_REQUIRE_SYMBOLS_ADMIN . ' símbolos especiales';
                    }
                    // Palabras comunes prohibidas
                    $commonPasswords = ['password', 'admin', 'administrador', 'gaselag', '123456', 'qwerty'];
                    foreach ($commonPasswords as $common) {
                        if (stripos($newPassword, $common) !== false) {
                            $errors[] = 'La contraseña no debe contener palabras comunes';
                            break;
                        }
                    }
                } else {
                    // Validación para usuario (PIN numérico)
                    if (!ctype_digit($newPassword)) {
                        $errors[] = 'El PIN debe contener solo números';
                    }
                    $pinLength = strlen($newPassword);
                    if ($pinLength < PIN_MIN_LENGTH_USER || $pinLength > PIN_MAX_LENGTH_USER) {
                        $errors[] = 'El PIN debe tener entre ' . PIN_MIN_LENGTH_USER . ' y ' . PIN_MAX_LENGTH_USER . ' dígitos';
                    }
                    // PINs comunes prohibidos
                    $commonPins = ['1234', '4321', '0000', '1111', '2222', '5555', '1212', '0123'];
                    if (in_array($newPassword, $commonPins)) {
                        $errors[] = 'El PIN es demasiado común, elija uno más seguro';
                    }
                    // Verificar secuencias
                    if (preg_match('/(?:0123|1234|2345|3456|4567|5678|6789|9876|8765|7654|6543|5432|4321|3210)/', $newPassword)) {
                        $errors[] = 'El PIN no debe ser una secuencia numérica';
                    }
                    // Verificar todos los dígitos iguales
                    if (preg_match('/^(\d)\1+$/', $newPassword)) {
                        $errors[] = 'El PIN no debe tener todos los dígitos iguales';
                    }
                }
                
                // Si no hay errores, actualizar contraseña
                if (empty($errors)) {
                    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
                    
                    // Actualizar contraseña y resetear flags de primer login
                    $updateStmt = $pdo->prepare("
                        UPDATE usuarios 
                        SET password = ?,
                            force_password_change = 0,
                            primer_login = 0,
                            ultimo_cambio_password = NOW()
                        WHERE id = ?
                    ");
                    
                    if ($updateStmt->execute([$hashedPassword, $currentUser['id']])) {
                        // Registrar en auditoría
                        logAudit(null, $currentUser['id'], 'cambio_password', 
                                'Cambio de ' . ($isAdmin ? 'contraseña' : 'PIN') . ' exitoso');
                        
                        $message = $isAdmin ? 
                            'Contraseña actualizada exitosamente' : 
                            'PIN actualizado exitosamente';
                        $messageType = 'success';
                        $redirecting = true;
                        
                        // Actualizar sesión para reflejar cambios
                        $_SESSION['password_changed'] = true;
                        
                        // Redirigir después de 2 segundos
                        header("refresh:2;url=../index.php");
                    } else {
                        $errors[] = 'Error al actualizar la contraseña. Intente nuevamente';
                    }
                }
            }
        } catch (PDOException $e) {
            error_log("Error cambiando contraseña: " . $e->getMessage());
            $errors[] = 'Error del sistema. Por favor, contacte al administrador';
        }
    }
}

$pageTitle = 'Cambiar Contraseña - Sistema GASELAG';
require_once '../includes/header.php';
?>

<style>
        body {
            background-color: #f5f7fa;
            min-height: 100vh;
        }
        .change-password-container {
            padding-top: 3rem;
            padding-bottom: 3rem;
        }
        .change-password-card {
            max-width: 600px;
            margin: 0 auto;
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

<div class="container change-password-container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-10 col-lg-8">
                <div class="card shadow-sm border-0 change-password-card">
                    <div class="card-header bg-primary text-white py-3">
                        <h5 class="mb-0">
                            <i class="bi bi-key me-2"></i>
                            Cambiar <?php echo $isAdmin ? 'Contraseña' : 'PIN'; ?>
                        </h5>
                    </div>
                    
                    <div class="card-body p-4">
                        <?php if (isset($redirecting) && $redirecting): ?>
                            <!-- Mensaje de éxito con animación -->
                            <div class="text-center py-5">
                                <div class="mb-4">
                                    <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                                </div>
                                <h4 class="text-success mb-3">
                                    <?php echo $isAdmin ? '¡Contraseña Cambiada!' : '¡PIN Cambiado!'; ?>
                                </h4>
                                <p class="text-muted mb-4">
                                    <?php echo $message; ?>
                                </p>
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                                <p class="text-muted mt-3">
                                    <small>Redirigiendo al panel principal...</small>
                                </p>
                            </div>
                        <?php else: ?>
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
                                       <?php 
                                       // Solo validar como numérico si NO es primer login (ya cambió su password/PIN)
                                       if (!$isAdmin && !$isFirstLogin && !$currentUser['primer_login']) {
                                           echo 'inputmode="numeric" pattern="[0-9]*"';
                                       }
                                       ?>
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
                        <?php endif; ?> <!-- Cierre del else de redirecting -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    
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

<?php require_once '../includes/footer.php'; ?>