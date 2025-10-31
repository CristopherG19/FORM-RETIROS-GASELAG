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
                <div class="card mt-3 info-card">
                    <div class="card-body">
                        <h6 class="card-title text-center mb-3">Usuarios de Prueba</h6>
                        <div class="row g-3">
                            <div class="col-6 text-center">
                                <div class="border rounded p-2 h-100">
                                    <strong style="color: #2c3e50; font-size: 0.9rem;">Administrador</strong>
                                    <hr class="my-2">
                                    <small class="text-muted">admin / password</small>
                                </div>
                            </div>
                            <div class="col-6 text-center">
                                <div class="border rounded p-2 h-100">
                                    <strong style="color: #2c3e50; font-size: 0.9rem;">Técnico</strong>
                                    <hr class="my-2">
                                    <small class="text-muted">tecnico1 / password</small>
                                </div>
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
