<?php
/**
 * Session Middleware - GASELAG
 * Verificación automática de timeout de sesión
 * Incluir este archivo al inicio de cada página protegida
 */

// Solo ejecutar si hay una sesión activa
if (isLoggedIn()) {
    // Verificar si la sesión expiró
    if (checkSessionTimeout()) {
        // Redirigir a login con mensaje de timeout
        header('Location: ' . getLoginUrl() . '?timeout=1');
        exit;
    }
    
    // Actualizar última actividad
    updateLastActivity();
    
    // Obtener tiempo restante para JavaScript
    $sessionTimeRemaining = getSessionTimeRemaining();
    $showWarning = ($sessionTimeRemaining <= SESSION_WARNING_TIME && $sessionTimeRemaining > 0);
}

/**
 * Obtener URL de login relativa a la página actual
 */
function getLoginUrl() {
    $currentPath = $_SERVER['SCRIPT_NAME'];
    $depth = substr_count($currentPath, '/') - 2; // Restar 2 por el root y el archivo
    
    return str_repeat('../', max(0, $depth)) . 'login.php';
}

// Calcular profundidad para rutas relativas
$currentPath = $_SERVER['SCRIPT_NAME'];
// Si estamos en /pages/, depth = 1; si estamos en raíz, depth = 0
$depth = (strpos($currentPath, '/pages/') !== false) ? 1 : 0;
?>

<?php if (isLoggedIn()): ?>
<!-- Modal de advertencia de sesión por expirar -->
<div class="modal fade" id="sessionWarningModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-warning">
            <div class="modal-header bg-warning bg-opacity-10 border-warning">
                <h5 class="modal-title">
                    <i class="bi bi-clock-history me-2"></i>
                    Su sesión está por expirar
                </h5>
            </div>
            <div class="modal-body text-center py-4">
                <div class="mb-3">
                    <i class="bi bi-hourglass-split text-warning" style="font-size: 3rem;"></i>
                </div>
                <p class="mb-3">Su sesión expirará en:</p>
                <h2 class="text-warning mb-4">
                    <span id="sessionCountdown">5:00</span>
                </h2>
                <p class="text-muted small">¿Desea continuar trabajando?</p>
            </div>
            <div class="modal-footer justify-content-center border-0">
                <button type="button" class="btn btn-warning" id="extendSessionBtn">
                    <i class="bi bi-arrow-clockwise me-2"></i>
                    Continuar Trabajando
                </button>
                <button type="button" class="btn btn-outline-secondary" id="logoutNowBtn">
                    <i class="bi bi-box-arrow-right me-2"></i>
                    Cerrar Sesión
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    // Configuración de sesión
    const SESSION_TIMEOUT = <?php echo $_SESSION['session_timeout'] ?? SESSION_TIMEOUT_USER; ?>;
    const WARNING_TIME = <?php echo SESSION_WARNING_TIME; ?>;
    let sessionTimeRemaining = <?php echo $sessionTimeRemaining; ?>;
    let warningShown = <?php echo $showWarning ? 'true' : 'false'; ?>;
    let warningModal = null;
    
    // Inicializar modal
    document.addEventListener('DOMContentLoaded', function() {
        const modalElement = document.getElementById('sessionWarningModal');
        if (modalElement) {
            warningModal = new bootstrap.Modal(modalElement);
            
            // Botón extender sesión
            document.getElementById('extendSessionBtn')?.addEventListener('click', function() {
                extendSession();
            });
            
            // Botón cerrar sesión
            document.getElementById('logoutNowBtn')?.addEventListener('click', function() {
                window.location.href = '<?php echo getLoginUrl(); ?>?action=logout';
            });
        }
        
        // Mostrar modal si ya debe advertir
        if (warningShown && warningModal) {
            warningModal.show();
            startWarningCountdown();
        }
    });
    
    // Contador principal de sesión
    setInterval(function() {
        sessionTimeRemaining--;
        
        // Si llegamos al tiempo de advertencia
        if (sessionTimeRemaining === WARNING_TIME && !warningShown) {
            warningShown = true;
            if (warningModal) {
                warningModal.show();
                startWarningCountdown();
            }
        }
        
        // Si la sesión expiró
        if (sessionTimeRemaining <= 0) {
            window.location.href = '<?php echo getLoginUrl(); ?>?timeout=1';
        }
    }, 1000);
    
    // Actualizar actividad en cada interacción del usuario
    let activityTimeout;
    const activityEvents = ['mousedown', 'keypress', 'scroll', 'touchstart', 'click'];
    
    activityEvents.forEach(function(event) {
        document.addEventListener(event, function() {
            clearTimeout(activityTimeout);
            activityTimeout = setTimeout(function() {
                // Enviar ping al servidor para actualizar actividad
                fetch('<?php echo str_repeat("../", max(0, $depth)); ?>config/ping.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=ping'
                }).then(response => response.json())
                  .then(data => {
                      if (data.success) {
                          // Resetear contador local
                          sessionTimeRemaining = SESSION_TIMEOUT;
                          
                          // Cerrar modal de advertencia si está abierto
                          if (warningModal && warningShown) {
                              warningModal.hide();
                              warningShown = false;
                          }
                      }
                  })
                  .catch(err => console.error('Error actualizando sesión:', err));
            }, 2000); // Esperar 2 segundos de inactividad antes de enviar ping
        }, {passive: true});
    });
    
    // Contador regresivo del modal de advertencia
    function startWarningCountdown() {
        let warningSeconds = WARNING_TIME;
        const countdownElement = document.getElementById('sessionCountdown');
        
        const warningInterval = setInterval(function() {
            warningSeconds--;
            
            if (warningSeconds <= 0 || !warningShown) {
                clearInterval(warningInterval);
                return;
            }
            
            const minutes = Math.floor(warningSeconds / 60);
            const seconds = warningSeconds % 60;
            countdownElement.textContent = minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
        }, 1000);
    }
    
    // Función para extender sesión
    function extendSession() {
        fetch('<?php echo str_repeat("../", max(0, $depth)); ?>config/ping.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=extend'
        }).then(response => response.json())
          .then(data => {
              if (data.success) {
                  sessionTimeRemaining = SESSION_TIMEOUT;
                  warningShown = false;
                  if (warningModal) {
                      warningModal.hide();
                  }
                  
                  // Mostrar notificación de éxito
                  showToast('Sesión extendida exitosamente', 'success');
              }
          })
          .catch(err => console.error('Error extendiendo sesión:', err));
    }
    
    // Función helper para mostrar notificaciones
    function showToast(message, type = 'info') {
        // Crear toast si no existe
        let toastContainer = document.getElementById('toastContainer');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toastContainer';
            toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            toastContainer.style.zIndex = '9999';
            document.body.appendChild(toastContainer);
        }
        
        const toastHTML = `
            <div class="toast align-items-center text-bg-${type} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        
        toastContainer.insertAdjacentHTML('beforeend', toastHTML);
        const toastElement = toastContainer.lastElementChild;
        const toast = new bootstrap.Toast(toastElement);
        toast.show();
        
        // Eliminar del DOM después de ocultarse
        toastElement.addEventListener('hidden.bs.toast', function() {
            toastElement.remove();
        });
    }
})();
</script>
<?php endif; ?>
