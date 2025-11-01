<?php
require_once 'config/database.php';

// Si ya está logueado, redirigir según el rol
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: index.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

// Generar token CSRF
$csrfToken = generateCSRFToken();

// Procesar login
$error = '';
$success = '';
$warning = '';
$blocked = false;
$remainingTime = 0;
$attemptsLeft = null;

if (isset($_GET['logout']) && $_GET['logout'] == 1) {
    $success = 'Sesión cerrada exitosamente';
}
if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    $warning = 'Su sesión expiró por inactividad. Por favor, ingrese nuevamente';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar CSRF token
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($submittedToken)) {
        $error = 'Token de seguridad inválido. Por favor, intente nuevamente';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($username) || empty($password)) {
            $error = 'Por favor ingrese usuario y contraseña';
        } else {
            $result = login($username, $password);
            
            if ($result['success']) {
                // Verificar si debe cambiar contraseña
                if ($result['force_password_change']) {
                    header('Location: pages/cambiar_password.php?first_login=1');
                    exit;
                }
                
                // Redirigir según el rol
                if ($result['user_role'] === 'admin') {
                    header('Location: index.php');
                } else {
                    header('Location: index.php');
                }
                exit;
            } else {
                // Manejar diferentes tipos de error
                if (isset($result['blocked']) && $result['blocked']) {
                    $blocked = true;
                    $remainingTime = $result['remaining'] ?? 0;
                    
                    if ($remainingTime === -1) {
                        $error = 'Cuenta bloqueada permanentemente. Contacte al administrador';
                    } else {
                        $error = $result['error'] . '. Tiempo restante: ' . getBlockTimeRemaining($remainingTime);
                    }
                } else {
                    $error = $result['error'];
                    $attemptsLeft = $result['attempts_left'] ?? null;
                }
            }
        }
    }
    
    // Regenerar token después del intento
    $csrfToken = generateCSRFToken();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Retiros GASELAG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f7fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        .login-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e8ecef;
            width: 100%;
            margin: 0 auto;
        }
        .login-header {
            background-color: #2c3e50;
            color: white;
            padding: 2.5rem 2rem 2rem;
            border-radius: 8px 8px 0 0;
            text-align: center;
            border-bottom: 3px solid #34495e;
        }
        .login-body {
            padding: 2.5rem 2rem;
        }
        .form-label {
            color: #2c3e50;
            font-weight: 500;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        .form-control {
            border-radius: 4px;
            border: 1px solid #dee2e6;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        .form-control:focus {
            border-color: #5a6c7d;
            box-shadow: 0 0 0 0.15rem rgba(44, 62, 80, 0.15);
        }
        .btn-login {
            background-color: #2c3e50;
            border: none;
            border-radius: 4px;
            padding: 0.85rem 2rem;
            font-weight: 500;
            width: 100%;
            color: white;
            transition: background-color 0.15s ease-in-out;
        }
        .btn-login:hover {
            background-color: #34495e;
            color: white;
        }
        .alert {
            border-radius: 4px;
            font-size: 0.9rem;
        }
        .login-footer {
            text-align: center;
            padding: 1.5rem 2rem 2rem;
            color: #6c757d;
            border-top: 1px solid #f0f0f0;
            font-size: 0.85rem;
        }
        .logo {
            font-size: 2.5rem;
            margin-bottom: 0.75rem;
            color: #ecf0f1;
        }
        .login-header h4 {
            font-weight: 600;
            margin-bottom: 0.25rem;
            font-size: 1.5rem;
        }
        .login-header p {
            color: #bdc3c7;
            font-size: 0.9rem;
        }
        .info-card {
            background: white;
            border: 1px solid #e8ecef;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
        }
        .info-card .card-title {
            color: #2c3e50;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .info-card small {
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">
                <div class="card login-card">
                    <div class="login-header">
                        <div class="logo">
                            <i class="bi bi-speedometer2"></i>
                        </div>
                        <h4 class="mb-0">Sistema de Retiros</h4>
                        <p class="mb-0">GASELAG</p>
                    </div>

                    <div class="login-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="bi bi-check-circle me-2"></i>
                                <?php echo htmlspecialchars($success); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($warning): ?>
                            <div class="alert alert-warning" role="alert">
                                <i class="bi bi-clock-history me-2"></i>
                                <?php echo htmlspecialchars($warning); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <?php echo htmlspecialchars($error); ?>
                                <?php if ($attemptsLeft !== null && $attemptsLeft > 0): ?>
                                    <br><small>Intentos restantes: <strong><?php echo $attemptsLeft; ?></strong></small>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($blocked && $remainingTime > 0): ?>
                            <div class="alert alert-warning" role="alert">
                                <i class="bi bi-lock me-2"></i>
                                Su cuenta está bloqueada temporalmente por seguridad.
                                <br><small>Podrá intentar nuevamente en: <strong id="countdown"><?php echo getBlockTimeRemaining($remainingTime); ?></strong></small>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" id="loginForm">
                            <!-- CSRF Token -->
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">
                                    <i class="bi bi-person me-2"></i>Usuario / DNI
                                </label>
                                <input type="text" 
                                       class="form-control text-center" 
                                       id="username" 
                                       name="username"
                                       inputmode="numeric"
                                       pattern="[0-9a-zA-Z]*"
                                       required 
                                       autocomplete="username" 
                                       placeholder="Ingrese DNI o usuario"
                                       style="font-size: 1.1rem; letter-spacing: 1px;"
                                       <?php echo $blocked ? 'disabled' : ''; ?>>
                                <small class="text-muted">Técnicos: usar DNI (ej: 12345678)</small>
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label">
                                    <i class="bi bi-key me-2"></i>Contraseña / PIN
                                </label>
                                <input type="password" 
                                       class="form-control text-center" 
                                       id="password" 
                                       name="password"
                                       inputmode="numeric"
                                       required 
                                       autocomplete="current-password" 
                                       placeholder="Ingrese PIN o contraseña"
                                       style="font-size: 1.1rem; letter-spacing: 2px;"
                                       <?php echo $blocked ? 'disabled' : ''; ?>>
                                <small class="text-muted">Técnicos: PIN de 4-6 dígitos</small>
                            </div>

                            <button type="submit" 
                                    class="btn btn-primary btn-login" 
                                    <?php echo $blocked ? 'disabled' : ''; ?>>
                                <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar Sesión
                            </button>
                        </form>
                        
                        <div class="mt-3 text-center">
                            <small class="text-muted">
                                <i class="bi bi-shield-check me-1"></i>
                                Conexión segura protegida
                            </small>
                        </div>
                    </div>

                    <div class="login-footer">
                        <p class="mb-1">¿Problemas para acceder?</p>
                        <small>Contacte al administrador del sistema</small>
                    </div>
                </div>

                <!-- Información de seguridad -->
                <div class="card mt-3 info-card">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                Máximo <strong><?php echo MAX_LOGIN_ATTEMPTS_USER; ?> intentos</strong> para técnicos
                            </small>
                            <small class="text-muted">
                                <i class="bi bi-clock me-1"></i>
                                Sesión: <strong>2 horas</strong>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Contador regresivo para bloqueos temporales
        <?php if ($blocked && $remainingTime > 0): ?>
        let remainingSeconds = <?php echo $remainingTime; ?>;
        const countdownElement = document.getElementById('countdown');
        const loginForm = document.getElementById('loginForm');
        
        const countdown = setInterval(function() {
            remainingSeconds--;
            
            if (remainingSeconds <= 0) {
                clearInterval(countdown);
                // Recargar página cuando expire el bloqueo
                window.location.reload();
            } else {
                // Actualizar display
                const minutes = Math.floor(remainingSeconds / 60);
                const seconds = remainingSeconds % 60;
                countdownElement.textContent = minutes + ' minuto' + (minutes !== 1 ? 's' : '') + 
                                              ' y ' + seconds + ' segundo' + (seconds !== 1 ? 's' : '');
            }
        }, 1000);
        <?php endif; ?>
        
        // Optimización para móviles: focus automático en username
        document.addEventListener('DOMContentLoaded', function() {
            const usernameInput = document.getElementById('username');
            if (usernameInput && !usernameInput.disabled) {
                usernameInput.focus();
            }
        });
        
        // Cambiar inputmode a numeric cuando detectamos que es solo números
        document.getElementById('username').addEventListener('input', function(e) {
            const value = e.target.value;
            if (/^\d+$/.test(value)) {
                // Solo números, es DNI
                e.target.setAttribute('inputmode', 'numeric');
            } else {
                // Contiene letras, es username
                e.target.setAttribute('inputmode', 'text');
            }
        });
    </script>
</body>
</html>
