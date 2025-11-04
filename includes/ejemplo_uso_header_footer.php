<?php
/**
 * EJEMPLO DE USO DEL HEADER Y FOOTER
 * 
 * Este archivo muestra cómo implementar el header y footer profesional
 * en cualquier página del sistema GASELAG
 */

// 1. Requerir la configuración de base de datos (SIEMPRE PRIMERO)
require_once '../config/database.php';

// 2. Verificar autenticación y permisos
requireRole(['admin', 'user']); // O solo ['admin'] si es página de admin

// 3. OPCIONAL: Definir título de la página antes del header
$pageTitle = 'Mi Página - Sistema GASELAG';

// 4. Incluir el header
require_once '../includes/header.php';
?>

<!-- AQUÍ VA EL CONTENIDO DE TU PÁGINA -->
<div class="container my-4">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">
                <i class="bi bi-stars me-2"></i>
                Título de tu Página
            </h1>
            
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Contenido de Ejemplo</h5>
                    <p class="card-text">
                        Este es el contenido de tu página. Puedes agregar cualquier HTML, 
                        formularios, tablas, etc.
                    </p>
                    
                    <!-- Ejemplo de contenido responsive -->
                    <div class="row g-3">
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card">
                                <div class="card-body">
                                    <h6>Tarjeta 1</h6>
                                    <p class="small text-muted mb-0">Contenido responsive</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card">
                                <div class="card-body">
                                    <h6>Tarjeta 2</h6>
                                    <p class="small text-muted mb-0">Se adapta a móvil</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card">
                                <div class="card-body">
                                    <h6>Tarjeta 3</h6>
                                    <p class="small text-muted mb-0">Y tablet también</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// 5. Incluir el footer (SIEMPRE AL FINAL)
require_once '../includes/footer.php';
?>
