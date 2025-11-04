<?php
/**
 * Header Profesional - Sistema GASELAG
 * Responsive para Desktop, Tablet y Móvil
 * Bootstrap 5 puro
 */

// Asegurar que hay sesión iniciada
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . (strpos($_SERVER['PHP_SELF'], '/pages/') !== false ? '../' : '') . 'login.php');
    exit;
}

$currentUser = getCurrentUser();
$isAdmin = isAdmin();
$userName = $currentUser['nombre_completo'] ?? 'Usuario';
$userRole = $isAdmin ? 'Administrador' : 'Técnico';

// Determinar ruta base según ubicación del archivo
$basePath = (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? '../' : '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema de Gestión de Retiro de Medidores - GASELAG">
    <meta name="author" content="GASELAG">
    <title><?php echo $pageTitle ?? 'Sistema GASELAG'; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Bootstrap 5 JS Bundle (ANTES para asegurar que esté disponible) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Estilos personalizados mínimos -->
    <style>
        :root {
            --gaselag-primary: #667eea;
            --gaselag-secondary: #764ba2;
            --gaselag-dark: #2c3e50;
        }
        
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-color: #f8f9fa;
        }
        
        main {
            flex: 1;
        }
        
        /* Navbar personalizada */
        .navbar-gaselag {
            background: linear-gradient(135deg, var(--gaselag-primary) 0%, var(--gaselag-secondary) 100%);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            z-index: 1030;
        }
        
        /* Asegurar que los dropdowns se muestren correctamente */
        .navbar .dropdown-menu {
            z-index: 1031;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .nav-link {
            transition: opacity 0.2s;
        }
        
        .nav-link:hover {
            opacity: 0.8;
        }
        
        /* Badge personalizado */
        .user-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        
        /* Responsive logo */
        .logo-text {
            display: inline;
        }
        
        @media (max-width: 575.98px) {
            .navbar-brand {
                font-size: 1.2rem;
            }
            .logo-text {
                display: none;
            }
            .user-info-text {
                display: none;
            }
        }
        
        @media (min-width: 576px) and (max-width: 767.98px) {
            .navbar-brand {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar Principal -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-gaselag sticky-top">
        <div class="container-fluid">
            <!-- Logo y Nombre -->
            <a class="navbar-brand d-flex align-items-center" href="<?php echo $basePath; ?>index.php">
                <i class="bi bi-speedometer2 me-2 fs-4"></i>
                <span class="logo-text">GASELAG</span>
            </a>
            
            <!-- Botón de menú móvil -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" 
                    aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Contenido del navbar -->
            <div class="collapse navbar-collapse" id="navbarContent">
                <!-- Menú principal (izquierda) -->
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $basePath; ?>index.php">
                            <i class="bi bi-house-door me-1"></i>
                            <span class="d-lg-inline">Inicio</span>
                        </a>
                    </li>
                    
                    <?php if ($isAdmin): ?>
                    <!-- Menú Admin -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarAdminDropdown" role="button" 
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-gear me-1"></i>
                            <span class="d-lg-inline">Administración</span>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarAdminDropdown">
                            <li>
                                <a class="dropdown-item" href="<?php echo $basePath; ?>pages/gestion_usuarios_mejorado.php">
                                    <i class="bi bi-people me-2"></i>Gestión de Usuarios
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo $basePath; ?>pages/gestion_retiros.php">
                                    <i class="bi bi-clipboard-check me-2"></i>Gestión de Retiros
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo $basePath; ?>pages/gestion_imposibilidad.php">
                                    <i class="bi bi-exclamation-triangle me-2"></i>Tipos de Imposibilidad
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo $basePath; ?>pages/gestion_evidencias.php">
                                    <i class="bi bi-camera me-2"></i>Gestión de Evidencias
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    
                    <!-- Menú Operaciones -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarOperationsDropdown" role="button" 
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-clipboard-data me-1"></i>
                            <span class="d-lg-inline">Operaciones</span>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarOperationsDropdown">
                            <li>
                                <a class="dropdown-item" href="<?php echo $basePath; ?>pages/listar_oc.php">
                                    <i class="bi bi-list-ul me-2"></i>Seleccionar OCs
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo $basePath; ?>pages/formulario_retiro.php">
                                    <i class="bi bi-pencil-square me-2"></i>Registrar Retiro
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo $basePath; ?>pages/consultar_retiros.php">
                                    <i class="bi bi-search me-2"></i>Consultar Retiros
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="<?php echo $basePath; ?>pages/importar_datos_mejorado.php">
                                    <i class="bi bi-file-earmark-arrow-up me-2"></i>Importar Datos
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
                
                <!-- Usuario y opciones (derecha) -->
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarUserDropdown" 
                           role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-2 fs-5"></i>
                            <span class="user-info-text">
                                <span class="d-block fw-bold"><?php echo htmlspecialchars($userName); ?></span>
                                <small class="text-white-50"><?php echo $userRole; ?></small>
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarUserDropdown">
                            <li class="px-3 py-2 border-bottom">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-person-circle me-2 fs-3 text-primary"></i>
                                    <div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($userName); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($currentUser['username']); ?></small>
                                        <br>
                                        <span class="badge bg-<?php echo $isAdmin ? 'danger' : 'primary'; ?> user-badge">
                                            <?php echo $userRole; ?>
                                        </span>
                                    </div>
                                </div>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo $basePath; ?>pages/cambiar_password.php">
                                    <i class="bi bi-key me-2"></i>Cambiar <?php echo $isAdmin ? 'Contraseña' : 'PIN'; ?>
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="<?php echo $basePath; ?>logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Contenido principal -->
    <main class="flex-fill">
