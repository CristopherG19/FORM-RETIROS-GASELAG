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

// Verificar si debe cambiar contraseña
$mustChangePass = mustChangePassword();
$currentPage = basename($_SERVER['PHP_SELF']);
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
        
        /* Estilos para bloqueo de navegación cuando debe cambiar contraseña */
        <?php if ($mustChangePass && $currentPage !== 'cambiar_password.php'): ?>
        /* BLOQUEO TOTAL: Todos los enlaces excepto los permitidos */
        a:not(.allowed-link),
        .navbar-brand,
        .nav-link,
        .dropdown-item:not(.allowed-link),
        footer a:not(.allowed-link) {
            pointer-events: none !important;
            opacity: 0.4 !important;
            cursor: not-allowed !important;
            text-decoration: none !important;
        }
        
        /* Permitir solo enlaces de cambiar contraseña y logout */
        a.allowed-link,
        a[href*="cambiar_password.php"],
        a[href*="logout.php"] {
            pointer-events: auto !important;
            opacity: 1 !important;
            cursor: pointer !important;
        }
        
        .password-change-banner {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 1rem;
            text-align: center;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
            animation: pulse 2s ease-in-out infinite;
            z-index: 1029;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.9; }
        }
        
        .password-change-banner .btn {
            animation: none;
        }
        <?php endif; ?>
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
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="<?php echo $basePath; ?>pages/admin_backups.php">
                                    <i class="bi bi-shield-check text-success me-2"></i>Sistema de Backups
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
                            <?php if (!isAdmin()): ?>
                            <!-- Mis OCs Asignadas (Solo técnicos) -->
                            <li>
                                <a class="dropdown-item" href="<?php echo $basePath; ?>pages/mis_ocs_asignadas.php">
                                    <i class="bi bi-person-check text-success me-2"></i>Mis OCs Asignadas
                                    <?php
                                    $pendientesCount = countOCsPendientesTecnico($_SESSION['user_id']);
                                    if ($pendientesCount > 0): ?>
                                        <span class="badge bg-warning text-dark ms-1"><?= $pendientesCount ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
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
                            <?php if (isAdmin()): ?>
                            <li>
                                <a class="dropdown-item" href="<?php echo $basePath; ?>pages/asignar_oc_masivo.php">
                                    <i class="bi bi-people-fill text-success me-2"></i>Asignación Masiva
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo $basePath; ?>pages/importar_asignaciones.php">
                                    <i class="bi bi-file-earmark-excel text-success me-2"></i>Importar Asignaciones (Excel)
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li>
                                <a class="dropdown-item" href="<?php echo $basePath; ?>pages/importar_datos.php">
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
                            
                            <?php if ($mustChangePass && $currentPage !== 'cambiar_password.php'): ?>
                            <!-- Solo mostrar opciones esenciales si debe cambiar contraseña -->
                            <li>
                                <a class="dropdown-item allowed-link bg-warning bg-opacity-10" href="<?php echo $basePath; ?>pages/cambiar_password.php">
                                    <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                                    <strong>¡Cambiar <?php echo $isAdmin ? 'Contraseña' : 'PIN'; ?> Ahora!</strong>
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item allowed-link text-danger" href="<?php echo $basePath; ?>logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                                </a>
                            </li>
                            <?php else: ?>
                            <!-- Menú normal -->
                            <li>
                                <a class="dropdown-item allowed-link" href="<?php echo $basePath; ?>pages/cambiar_password.php">
                                    <i class="bi bi-key me-2"></i>Cambiar <?php echo $isAdmin ? 'Contraseña' : 'PIN'; ?>
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item allowed-link text-danger" href="<?php echo $basePath; ?>logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <?php if ($mustChangePass && $currentPage !== 'cambiar_password.php'): ?>
    <!-- Banner de Advertencia: Debe Cambiar Contraseña -->
    <div class="password-change-banner sticky-top">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-12 col-md-8 mb-2 mb-md-0">
                    <i class="bi bi-shield-exclamation fs-4 me-2"></i>
                    <strong>¡ATENCIÓN!</strong> Debe cambiar su <?php echo $isAdmin ? 'contraseña' : 'PIN'; ?> antes de continuar.
                    <span class="d-none d-md-inline">La navegación está bloqueada por seguridad.</span>
                </div>
                <div class="col-12 col-md-4 text-md-end">
                    <a href="<?php echo $basePath; ?>pages/cambiar_password.php" class="btn btn-light btn-sm fw-bold">
                        <i class="bi bi-key-fill me-1"></i>
                        Cambiar Ahora
                    </a>
                    <a href="<?php echo $basePath; ?>logout.php" class="btn btn-outline-light btn-sm ms-2">
                        <i class="bi bi-box-arrow-right me-1"></i>
                        Salir
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript para bloqueo adicional -->
    <script>
    (function() {
        'use strict';
        
        console.warn('🔒 MODO SEGURO: Navegación bloqueada hasta cambiar contraseña');
        
        // Función para verificar si un enlace está permitido
        function isAllowedLink(link) {
            if (!link || !link.href) return false;
            const href = link.href.toLowerCase();
            return href.includes('cambiar_password.php') || 
                   href.includes('logout.php') ||
                   link.classList.contains('allowed-link');
        }
        
        // Función para bloquear click
        function blockClick(e) {
            const target = e.target.closest('a');
            if (target && !isAllowedLink(target)) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                alert('⚠️ ACCESO BLOQUEADO\n\nDebe cambiar su <?php echo $isAdmin ? "contraseña" : "PIN"; ?> antes de acceder a otras secciones del sistema.\n\nEsto es obligatorio por seguridad.');
                return false;
            }
        }
        
        // Bloquear en fase de captura (antes que cualquier otro evento)
        document.addEventListener('click', blockClick, true);
        document.addEventListener('mousedown', blockClick, true);
        document.addEventListener('mouseup', blockClick, true);
        document.addEventListener('touchstart', blockClick, true);
        document.addEventListener('touchend', blockClick, true);
        
        // Bloquear cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', function() {
            // Bloquear TODOS los enlaces
            const allLinks = document.querySelectorAll('a');
            allLinks.forEach(function(link) {
                if (!isAllowedLink(link)) {
                    // Remover todos los event listeners existentes
                    const newLink = link.cloneNode(true);
                    link.parentNode.replaceChild(newLink, link);
                    
                    // Agregar nuestro bloqueador
                    newLink.addEventListener('click', blockClick, true);
                    newLink.style.pointerEvents = 'none';
                    newLink.style.opacity = '0.4';
                    newLink.style.cursor = 'not-allowed';
                }
            });
            
            console.warn('🔒 ' + allLinks.length + ' enlaces bloqueados');
            
            // Bloquear navegación con teclado
            document.addEventListener('keydown', function(e) {
                // Bloquear F5 (Refresh)
                if (e.key === 'F5' || (e.ctrlKey && e.key === 'r')) {
                    e.preventDefault();
                    alert('⚠️ Debe cambiar su <?php echo $isAdmin ? "contraseña" : "PIN"; ?> antes de refrescar la página.');
                }
                
                // Bloquear Ctrl/Cmd + Click
                if ((e.ctrlKey || e.metaKey) && e.target.tagName === 'A') {
                    if (!isAllowedLink(e.target)) {
                        e.preventDefault();
                        e.stopPropagation();
                        alert('⚠️ Navegación bloqueada por seguridad.');
                    }
                }
            });
            
            // Recordatorio cada 20 segundos
            let reminderCount = 0;
            setInterval(function() {
                if (reminderCount < 5) {
                    console.warn('⚠️ RECORDATORIO ' + (reminderCount + 1) + ': Debe cambiar su <?php echo $isAdmin ? "contraseña" : "PIN"; ?>');
                    reminderCount++;
                }
            }, 20000);
        });
        
        // Prevenir navegación con botón atrás
        window.addEventListener('popstate', function(e) {
            history.pushState(null, null, window.location.href);
            alert('⚠️ No puede usar el botón "Atrás".\nDebe cambiar su <?php echo $isAdmin ? "contraseña" : "PIN"; ?> primero.');
        });
        
        // Bloquear historial
        history.pushState(null, null, window.location.href);
        
        // Advertencia al intentar cerrar la pestaña
        window.addEventListener('beforeunload', function(e) {
            const message = '⚠️ Debe cambiar su contraseña antes de salir';
            e.returnValue = message;
            return message;
        });
        
        // Deshabilitar menú contextual en enlaces
        document.addEventListener('contextmenu', function(e) {
            const target = e.target.closest('a');
            if (target && !isAllowedLink(target)) {
                e.preventDefault();
                alert('⚠️ Acción bloqueada por seguridad');
            }
        });
        
    })();
    </script>
    <?php endif; ?>
    
    <!-- Contenido principal -->
    <main class="flex-fill">
