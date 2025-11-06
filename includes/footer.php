    </main>
    
    <!-- Footer Profesional -->
    <footer class="bg-dark text-white mt-5">
        <div class="container-fluid">
            <!-- Sección principal del footer -->
            <div class="row py-4">
                <!-- Columna 1: Información de la empresa -->
                <div class="col-12 col-md-4 mb-3 mb-md-0">
                    <h5 class="fw-bold mb-3">
                        <i class="bi bi-speedometer2 me-2"></i>GASELAG
                    </h5>
                    <p class="text-white-50 small">
                        Sistema de Gestión de Retiro de Medidores de Gas Natural
                    </p>
                    <p class="text-white-50 small mb-0">
                        <i class="bi bi-geo-alt me-2"></i>San Luis, Lima
                    </p>
                </div>
                
                <!-- Columna 2: Enlaces rápidos -->
                <div class="col-6 col-md-4 mb-3 mb-md-0">
                    <h6 class="fw-bold mb-3">Enlaces Rápidos</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="<?php echo $basePath; ?>index.php" class="text-white-50 text-decoration-none">
                                <i class="bi bi-house-door me-2"></i>Inicio
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo $basePath; ?>pages/consultar_retiros.php" class="text-white-50 text-decoration-none">
                                <i class="bi bi-search me-2"></i>Consultar Retiros
                            </a>
                        </li>
                        <?php if ($isAdmin): ?>
                        <li class="mb-2">
                            <a href="<?php echo $basePath; ?>pages/gestion_usuarios_mejorado.php" class="text-white-50 text-decoration-none">
                                <i class="bi bi-people me-2"></i>Gestión de Usuarios
                            </a>
                        </li>
                        <?php endif; ?>
                        <li class="mb-2">
                            <a href="<?php echo $basePath; ?>pages/cambiar_password.php" class="text-white-50 text-decoration-none">
                                <i class="bi bi-key me-2"></i>Cambiar <?php echo $isAdmin ? 'Contraseña' : 'PIN'; ?>
                            </a>
                        </li>
                    </ul>
                </div>
                
                <!-- Columna 3: Información de contacto y ayuda -->
                <div class="col-6 col-md-4">
                    <h6 class="fw-bold mb-3">Soporte</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2 text-white-50 small">
                            <i class="bi bi-telephone me-2"></i>
                            <span class="d-none d-sm-inline">Soporte Técnico</span>
                            <span class="d-sm-none">Tel.</span>
                        </li>
                        <li class="mb-2 text-white-50 small">
                            <i class="bi bi-envelope me-2"></i>
                            <span class="d-none d-sm-inline">soporte@gaselag.com</span>
                            <span class="d-sm-none">Email</span>
                        </li>
                        <li class="mb-2 text-white-50 small">
                            <i class="bi bi-clock me-2"></i>
                            <span class="d-none d-lg-inline">Lun - Vie: 8:00 AM - 5:00 PM</span>
                            <span class="d-lg-none">L-V: 8AM-5PM</span>
                        </li>
                    </ul>
                    
                    <!-- Información del usuario actual -->
                    <div class="mt-3 p-2 bg-dark bg-opacity-50 rounded d-none d-md-block">
                        <small class="text-white-50">
                            <i class="bi bi-person-check me-2"></i>
                            Sesión: <span class="text-white"><?php echo htmlspecialchars($currentUser['username']); ?></span>
                        </small>
                    </div>
                </div>
            </div>
            
            <!-- Separador -->
            <hr class="border-secondary my-0">
            
            <!-- Sección de copyright y versión -->
            <div class="row py-3">
                <div class="col-12 col-md-6 text-center text-md-start mb-2 mb-md-0">
                    <small class="text-white-50">
                        © <?php echo date('Y'); ?> GASELAG. Todos los derechos reservados.
                    </small>
                </div>
                <div class="col-12 col-md-6 text-center text-md-end">
                    <small class="text-white-50">
                        <i class="bi bi-code-square me-1"></i>
                        Versión 2.0 
                        <span class="d-none d-sm-inline">| Sistema de Retiros</span>
                    </small>
                </div>
            </div>
            
            <!-- Información adicional en móvil -->
            <div class="row pb-3 d-md-none">
                <div class="col-12 text-center">
                    <small class="text-white-50">
                        <i class="bi bi-person-circle me-1"></i>
                        <?php echo htmlspecialchars($userName); ?> 
                        <span class="badge bg-<?php echo $isAdmin ? 'danger' : 'primary'; ?> ms-2" style="font-size: 0.65rem;">
                            <?php echo $userRole; ?>
                        </span>
                    </small>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Scripts del Sistema -->
    <script>
        // Cerrar navbar en móvil después de hacer clic en un enlace
        document.addEventListener('DOMContentLoaded', function() {
            // Verificar que Bootstrap está cargado
            if (typeof bootstrap === 'undefined') {
                console.error('⚠️ Bootstrap JavaScript NO está cargado');
                return;
            }
            console.log('✅ Bootstrap JS disponible');
            
            const navLinks = document.querySelectorAll('.navbar-nav .nav-link, .navbar-nav .dropdown-item');
            const navbarCollapse = document.getElementById('navbarContent');
            
            if (navbarCollapse) {
                navLinks.forEach(link => {
                    link.addEventListener('click', function(e) {
                        // Solo cerrar el navbar si es un link real (no dropdown toggle)
                        if (!link.classList.contains('dropdown-toggle')) {
                            if (window.innerWidth < 992) { // Solo en móvil/tablet
                                const bsCollapse = bootstrap.Collapse.getInstance(navbarCollapse);
                                if (bsCollapse) {
                                    bsCollapse.hide();
                                }
                            }
                        }
                    });
                });
            }
            
            console.log('✅ Scripts del navbar inicializados');
        });
        
        // Highlight del enlace activo
        document.addEventListener('DOMContentLoaded', function() {
            const currentPath = window.location.pathname;
            const navLinks = document.querySelectorAll('.navbar-nav .nav-link, .navbar-nav .dropdown-item');
            
            navLinks.forEach(link => {
                if (link.getAttribute('href') && currentPath.includes(link.getAttribute('href'))) {
                    link.classList.add('active');
                    if (link.classList.contains('dropdown-item')) {
                        const dropdown = link.closest('.dropdown');
                        if (dropdown) {
                            dropdown.querySelector('.nav-link').classList.add('active');
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>
