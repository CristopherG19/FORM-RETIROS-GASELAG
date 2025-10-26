<?php
require_once 'config/database.php';

// Si ya está logueado, redirigir según el rol
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: index.php');
    } else {
        header('Location: pages/consultar_retiros.php');
    }
    exit;
}

// Procesar login
$error = '';
$success = '';
if (isset($_GET['logout']) && $_GET['logout'] == 1) {
    $success = 'Sesión cerrada exitosamente';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Por favor ingrese usuario y contraseña';
    } else {
        if (login($username, $password)) {
            // Redirigir según el rol
            if (isAdmin()) {
                header('Location: index.php');
            } else {
                header('Location: pages/consultar_retiros.php');
            }
            exit;
        } else {
            $error = 'Usuario o contraseña incorrectos';
        }
    }
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border: none;
            max-width: 400px;
            width: 100%;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px 15px 0 0;
            text-align: center;
        }
        .login-body {
            padding: 2rem;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e1e5e9;
            padding: 0.75rem 1rem;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            width: 100%;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
        .login-footer {
            text-align: center;
            padding: 1rem 2rem 2rem;
            color: #6c757d;
        }
        .logo {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
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

                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="username" class="form-label">
                                    <i class="bi bi-person me-2"></i>Usuario
                                </label>
                                <input type="text" class="form-control" id="username" name="username"
                                       required autocomplete="username" placeholder="Ingrese su usuario">
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label">
                                    <i class="bi bi-key me-2"></i>Contraseña
                                </label>
                                <input type="password" class="form-control" id="password" name="password"
                                       required autocomplete="current-password" placeholder="Ingrese su contraseña">
                            </div>

                            <button type="submit" class="btn btn-primary btn-login">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar Sesión
                            </button>
                        </form>
                    </div>

                    <div class="login-footer">
                        <p class="mb-1">¿Problemas para acceder?</p>
                        <small>Contacte al administrador del sistema</small>
                    </div>
                </div>

                <!-- Información de usuarios de prueba -->
                <div class="card mt-3" style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2);">
                    <div class="card-body text-center">
                        <h6 class="card-title text-white mb-2">Usuarios de Prueba</h6>
                        <div class="row text-white">
                            <div class="col-6">
                                <strong>Administrador:</strong><br>
                                <small>admin / password</small>
                            </div>
                            <div class="col-6">
                                <strong>Técnico:</strong><br>
                                <small>tecnico1 / password</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
