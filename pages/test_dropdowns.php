<?php
require_once '../config/database.php';
requireRole(['admin', 'user']);

$pageTitle = 'Test Dropdowns - Sistema GASELAG';
require_once '../includes/header.php';
?>

<style>
.test-card {
    background: white;
    border-radius: 8px;
    padding: 2rem;
    margin: 2rem 0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
</style>

<div class="container py-5">
    <div class="test-card">
        <h2 class="mb-4">🔍 Prueba de Funcionalidad de Dropdowns</h2>
        
        <div class="alert alert-info">
            <h5><i class="bi bi-info-circle me-2"></i>Instrucciones de Prueba</h5>
            <ol>
                <li>Abre la <strong>consola del navegador</strong> (F12 → Console)</li>
                <li>Busca mensajes de Bootstrap JS</li>
                <li>Intenta hacer clic en los menús del encabezado:
                    <ul>
                        <li><strong>Administración</strong> (si eres admin)</li>
                        <li><strong>Operaciones</strong></li>
                        <li><strong>Usuario</strong> (tu nombre arriba a la derecha)</li>
                    </ul>
                </li>
                <li>Verifica si los dropdowns se abren</li>
            </ol>
        </div>
        
        <div class="card mt-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-check2-square me-2"></i>Checklist de Diagnóstico</h5>
            </div>
            <div class="card-body">
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="check1">
                    <label class="form-check-label" for="check1">
                        ✅ Veo el mensaje "Bootstrap JS cargado correctamente" en la consola
                    </label>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="check2">
                    <label class="form-check-label" for="check2">
                        ✅ Veo el mensaje "Dropdowns inicializados: X" en la consola
                    </label>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="check3">
                    <label class="form-check-label" for="check3">
                        ✅ Al hacer clic en "Administración" u "Operaciones", se abre el menú
                    </label>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="check4">
                    <label class="form-check-label" for="check4">
                        ✅ Al hacer clic en mi nombre (arriba derecha), se abre el menú de usuario
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="check5">
                    <label class="form-check-label" for="check5">
                        ✅ Los enlaces dentro de los menús funcionan correctamente
                    </label>
                </div>
            </div>
        </div>
        
        <div class="mt-4">
            <h5><i class="bi bi-bug me-2"></i>Información Técnica</h5>
            <div class="bg-light p-3 rounded">
                <p><strong>Versión Bootstrap:</strong> 5.3.0</p>
                <p><strong>jQuery:</strong> No requerido (Bootstrap 5 usa JavaScript vanilla)</p>
                <p><strong>Archivos cargados:</strong></p>
                <ul>
                    <li>bootstrap.min.css (desde CDN)</li>
                    <li>bootstrap-icons.css (desde CDN)</li>
                    <li>bootstrap.bundle.min.js (desde CDN en footer)</li>
                </ul>
            </div>
        </div>
        
        <!-- Test de dropdown local -->
        <div class="mt-4">
            <h5><i class="bi bi-test-tube me-2"></i>Test Local de Dropdown</h5>
            <p>Este es un dropdown de prueba <strong>dentro de la página</strong>:</p>
            
            <div class="dropdown">
                <button class="btn btn-success dropdown-toggle" type="button" id="dropdownTest" 
                        data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-emoji-smile me-2"></i>Test Dropdown Local
                </button>
                <ul class="dropdown-menu" aria-labelledby="dropdownTest">
                    <li><a class="dropdown-item" href="#">Opción 1</a></li>
                    <li><a class="dropdown-item" href="#">Opción 2</a></li>
                    <li><a class="dropdown-item" href="#">Opción 3</a></li>
                </ul>
            </div>
            
            <div class="alert alert-warning mt-3">
                <strong>Si este dropdown local funciona pero los del navbar NO:</strong><br>
                → El problema es específico del navbar (z-index, CSS conflicto, etc.)<br><br>
                <strong>Si NINGÚN dropdown funciona (ni navbar ni este):</strong><br>
                → Bootstrap JS no está cargando correctamente
            </div>
        </div>
    </div>
</div>

<script>
// Script adicional de diagnóstico
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== DIAGNÓSTICO DE DROPDOWNS ===');
    console.log('1. Bootstrap disponible:', typeof bootstrap !== 'undefined');
    console.log('2. jQuery disponible:', typeof jQuery !== 'undefined');
    
    // Verificar dropdowns del navbar
    const navbarDropdowns = document.querySelectorAll('.navbar .dropdown-toggle');
    console.log('3. Dropdowns en navbar encontrados:', navbarDropdowns.length);
    
    navbarDropdowns.forEach((dropdown, index) => {
        console.log(`   - Dropdown ${index + 1}:`, dropdown.textContent.trim());
    });
    
    // Verificar que los dropdowns tengan el atributo correcto
    navbarDropdowns.forEach((dropdown) => {
        if (!dropdown.hasAttribute('data-bs-toggle')) {
            console.error('⚠️ Dropdown sin data-bs-toggle:', dropdown);
        }
    });
    
    // Test de click en dropdown
    setTimeout(() => {
        console.log('4. Intentando activar primer dropdown del navbar...');
        if (navbarDropdowns.length > 0) {
            const firstDropdown = navbarDropdowns[0];
            console.log('   Click en:', firstDropdown.textContent.trim());
            firstDropdown.click();
        }
    }, 2000);
    
    console.log('=== FIN DIAGNÓSTICO ===');
});
</script>

<?php require_once '../includes/footer.php'; ?>
