<?php
require_once '../config/database.php';
requireRole(['admin', 'user']);

$pageTitle = 'Prueba Header y Footer - Sistema GASELAG';
require_once '../includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-12">
            <!-- Título -->
            <div class="text-center mb-5">
                <h1 class="display-4 fw-bold text-primary mb-3">
                    <i class="bi bi-check-circle me-3"></i>
                    ¡Header y Footer Funcionando!
                </h1>
                <p class="lead text-muted">
                    Este es un ejemplo de cómo se ve el nuevo header y footer responsive
                </p>
            </div>

            <!-- Alerta de éxito -->
            <div class="alert alert-success shadow-sm" role="alert">
                <h4 class="alert-heading">
                    <i class="bi bi-stars me-2"></i>
                    Sistema Actualizado Exitosamente
                </h4>
                <p class="mb-0">
                    El header y footer profesional están instalados y funcionando correctamente. 
                    Puedes ver cómo se adaptan a diferentes tamaños de pantalla.
                </p>
            </div>

            <!-- Tarjetas de características -->
            <div class="row g-4 my-4">
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <i class="bi bi-phone text-primary" style="font-size: 3rem;"></i>
                            <h5 class="card-title mt-3">Responsive Móvil</h5>
                            <p class="card-text text-muted">
                                Perfecto en smartphones. Menú hamburguesa y contenido adaptado.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <i class="bi bi-tablet text-success" style="font-size: 3rem;"></i>
                            <h5 class="card-title mt-3">Responsive Tablet</h5>
                            <p class="card-text text-muted">
                                Optimizado para tablets. Layout intermedio balanceado.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <i class="bi bi-laptop text-info" style="font-size: 3rem;"></i>
                            <h5 class="card-title mt-3">Responsive Desktop</h5>
                            <p class="card-text text-muted">
                                Vista completa en desktop. Todos los menús expandidos.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información del usuario -->
            <div class="row my-5">
                <div class="col-12 col-lg-8 mx-auto">
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-person-check me-2"></i>
                                Información de tu Sesión
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6 col-md-3 mb-3">
                                    <small class="text-muted">Usuario:</small>
                                    <div class="fw-bold"><?php echo htmlspecialchars($currentUser['username']); ?></div>
                                </div>
                                <div class="col-6 col-md-3 mb-3">
                                    <small class="text-muted">Nombre:</small>
                                    <div class="fw-bold"><?php echo htmlspecialchars($currentUser['nombre_completo']); ?></div>
                                </div>
                                <div class="col-6 col-md-3 mb-3">
                                    <small class="text-muted">Rol:</small>
                                    <div>
                                        <span class="badge bg-<?php echo $isAdmin ? 'danger' : 'primary'; ?>">
                                            <?php echo $isAdmin ? 'Administrador' : 'Técnico'; ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3 mb-3">
                                    <small class="text-muted">Estado:</small>
                                    <div>
                                        <span class="badge bg-success">Activo</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Instrucciones -->
            <div class="row my-5">
                <div class="col-12 col-lg-10 mx-auto">
                    <div class="card border-info shadow-sm">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-info-circle me-2"></i>
                                Prueba el Sistema Responsive
                            </h5>
                        </div>
                        <div class="card-body">
                            <h6 class="fw-bold mb-3">Instrucciones:</h6>
                            <ol class="mb-4">
                                <li class="mb-2">
                                    <strong>Móvil:</strong> Reduce el tamaño de la ventana o abre desde tu celular. 
                                    Verás el menú hamburguesa (☰) en la esquina superior derecha.
                                </li>
                                <li class="mb-2">
                                    <strong>Tablet:</strong> En tamaños medianos, el contenido se reorganiza en columnas.
                                </li>
                                <li class="mb-2">
                                    <strong>Desktop:</strong> En pantalla completa, todos los elementos se muestran expandidos.
                                </li>
                            </ol>

                            <h6 class="fw-bold mb-3">Elementos a Probar:</h6>
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    Haz clic en el menú <strong>"Administración"</strong> o <strong>"Operaciones"</strong>
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    Haz clic en tu nombre de usuario (arriba a la derecha)
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    Revisa el footer al final de la página con enlaces útiles
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    Redimensiona la ventana para ver cómo se adapta
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botones de navegación -->
            <div class="text-center my-5">
                <a href="../index.php" class="btn btn-primary btn-lg me-2 mb-2">
                    <i class="bi bi-house-door me-2"></i>
                    Volver al Inicio
                </a>
                <a href="gestion_usuarios_mejorado.php" class="btn btn-success btn-lg mb-2">
                    <i class="bi bi-people me-2"></i>
                    Gestión de Usuarios
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
