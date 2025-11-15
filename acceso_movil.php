<?php
/**
 * Página de ayuda para acceso desde dispositivos móviles
 * Muestra la IP local y un QR code para acceso fácil
 */

// Obtener IP del servidor
function getLocalIP() {
    // Para Windows con XAMPP
    $localIP = gethostbyname(gethostname());
    
    // Verificar si es una IP válida
    if (filter_var($localIP, FILTER_VALIDATE_IP)) {
        return $localIP;
    }
    
    // Fallback
    return $_SERVER['SERVER_ADDR'] ?? 'localhost';
}

$serverIP = getLocalIP();
$projectPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$baseURL = "http://" . $serverIP . $projectPath;
$loginURL = $baseURL . "/login.php";

// Generar URL para QR code (usando API gratuita)
$qrCodeURL = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($loginURL);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Móvil - GASELAG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .qr-container {
            background: white;
            padding: 20px;
            border-radius: 15px;
            display: inline-block;
        }
        .url-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            border: 2px dashed #6c757d;
            word-break: break-all;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }
        .step-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        .step-number {
            background: rgba(255,255,255,0.3);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
        }
        .copy-btn {
            cursor: pointer;
        }
        .copy-btn:hover {
            background-color: #0056b3 !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10">
                
                <!-- Header -->
                <div class="text-center mb-4">
                    <h1 class="text-white mb-2">
                        <i class="bi bi-phone me-2"></i>
                        Acceso desde Celular
                    </h1>
                    <p class="text-white-50">Sistema GASELAG - Retiro de Medidores</p>
                </div>

                <!-- Tarjeta Principal -->
                <div class="card mb-4">
                    <div class="card-body p-5 text-center">
                        
                        <h3 class="mb-4">
                            <i class="bi bi-qr-code text-primary"></i>
                            Escanea el código QR
                        </h3>

                        <div class="qr-container mb-4">
                            <img src="<?php echo $qrCodeURL; ?>" alt="QR Code" class="img-fluid">
                        </div>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Escanea este código</strong> con la cámara de tu celular para acceder directamente
                        </div>

                        <hr class="my-4">

                        <h5 class="mb-3">O copia esta URL manualmente:</h5>
                        
                        <div class="url-box mb-3 position-relative">
                            <strong>URL Base:</strong><br>
                            <span id="baseURL"><?php echo $baseURL; ?></span>
                            <button class="btn btn-sm btn-primary copy-btn position-absolute top-0 end-0 m-2" onclick="copyToClipboard('baseURL')">
                                <i class="bi bi-clipboard"></i> Copiar
                            </button>
                        </div>

                        <div class="url-box mb-3 position-relative">
                            <strong>Login Directo:</strong><br>
                            <span id="loginURL"><?php echo $loginURL; ?></span>
                            <button class="btn btn-sm btn-success copy-btn position-absolute top-0 end-0 m-2" onclick="copyToClipboard('loginURL')">
                                <i class="bi bi-clipboard"></i> Copiar
                            </button>
                        </div>

                        <div class="alert alert-warning mt-3">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Tu IP actual: <strong><?php echo $serverIP; ?></strong>
                        </div>

                    </div>
                </div>

                <!-- Instrucciones -->
                <div class="card mb-4">
                    <div class="card-body p-4">
                        <h4 class="mb-4">
                            <i class="bi bi-list-check text-success me-2"></i>
                            Requisitos Previos
                        </h4>

                        <div class="step-card">
                            <div class="d-flex align-items-center">
                                <div class="step-number me-3">1</div>
                                <div>
                                    <h5 class="mb-1">Misma Red WiFi</h5>
                                    <p class="mb-0 small">Tu PC y celular deben estar conectados a la misma red WiFi</p>
                                </div>
                            </div>
                        </div>

                        <div class="step-card">
                            <div class="d-flex align-items-center">
                                <div class="step-number me-3">2</div>
                                <div>
                                    <h5 class="mb-1">XAMPP Corriendo</h5>
                                    <p class="mb-0 small">Apache y MySQL deben estar activos (botón verde en XAMPP)</p>
                                </div>
                            </div>
                        </div>

                        <div class="step-card">
                            <div class="d-flex align-items-center">
                                <div class="step-number me-3">3</div>
                                <div>
                                    <h5 class="mb-1">Firewall Configurado</h5>
                                    <p class="mb-0 small">Windows Firewall debe permitir conexiones en puerto 80</p>
                                </div>
                            </div>
                        </div>

                        <div class="step-card">
                            <div class="d-flex align-items-center">
                                <div class="step-number me-3">4</div>
                                <div>
                                    <h5 class="mb-1">Acceder desde el Celular</h5>
                                    <p class="mb-0 small">Abre el navegador de tu celular y escanea el QR o copia la URL</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Troubleshooting -->
                <div class="card mb-4">
                    <div class="card-body p-4">
                        <h4 class="mb-4">
                            <i class="bi bi-tools text-warning me-2"></i>
                            ¿No Funciona? Prueba esto:
                        </h4>

                        <div class="accordion" id="troubleshootingAccordion">
                            
                            <!-- Item 1 -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1">
                                        <i class="bi bi-wifi text-danger me-2"></i>
                                        No puedo conectar desde el celular
                                    </button>
                                </h2>
                                <div id="collapse1" class="accordion-collapse collapse" data-bs-parent="#troubleshootingAccordion">
                                    <div class="accordion-body">
                                        <ul>
                                            <li>Verifica que PC y celular estén en la <strong>misma red WiFi</strong></li>
                                            <li>Asegúrate de usar <code>http://</code> y NO <code>https://</code></li>
                                            <li>Verifica que Apache esté corriendo en XAMPP</li>
                                            <li>Prueba desactivar temporalmente el firewall de Windows</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Item 2 -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2">
                                        <i class="bi bi-shield-exclamation text-warning me-2"></i>
                                        Configurar Firewall de Windows
                                    </button>
                                </h2>
                                <div id="collapse2" class="accordion-collapse collapse" data-bs-parent="#troubleshootingAccordion">
                                    <div class="accordion-body">
                                        <ol>
                                            <li>Presiona <kbd>Windows + R</kbd></li>
                                            <li>Escribe: <code>firewall.cpl</code></li>
                                            <li>Click en "Permitir una aplicación..."</li>
                                            <li>Busca "Apache HTTP Server"</li>
                                            <li>Marca las casillas "Privada" y "Pública"</li>
                                            <li>Click "Aceptar"</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>

                            <!-- Item 3 -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3">
                                        <i class="bi bi-speedometer text-info me-2"></i>
                                        La página carga muy lento
                                    </button>
                                </h2>
                                <div id="collapse3" class="accordion-collapse collapse" data-bs-parent="#troubleshootingAccordion">
                                    <div class="accordion-body">
                                        <ul>
                                            <li>Normal si tu router WiFi es antiguo</li>
                                            <li>Acércate más al router</li>
                                            <li>Considera usar una red WiFi de 5GHz si está disponible</li>
                                            <li>Para acceso remoto, considera usar <strong>ngrok</strong> (ver documentación)</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="card">
                    <div class="card-body p-4 text-center">
                        <h5 class="mb-3">Enlaces Útiles</h5>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                            <a href="index.php" class="btn btn-primary">
                                <i class="bi bi-house-door me-2"></i>
                                Volver al Sistema
                            </a>
                            <a href="GUIA_ACCESO_MOVIL.md" class="btn btn-outline-secondary" download>
                                <i class="bi bi-file-earmark-text me-2"></i>
                                Descargar Guía Completa
                            </a>
                            <button onclick="location.reload()" class="btn btn-outline-info">
                                <i class="bi bi-arrow-clockwise me-2"></i>
                                Actualizar IP
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            const text = element.textContent;
            
            // Crear elemento temporal
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = 0;
            document.body.appendChild(textarea);
            
            // Seleccionar y copiar
            textarea.select();
            document.execCommand('copy');
            
            // Limpiar
            document.body.removeChild(textarea);
            
            // Mostrar feedback
            const button = event.target.closest('button');
            const originalHTML = button.innerHTML;
            button.innerHTML = '<i class="bi bi-check"></i> ¡Copiado!';
            button.classList.add('btn-success');
            button.classList.remove('btn-primary');
            
            setTimeout(() => {
                button.innerHTML = originalHTML;
                button.classList.remove('btn-success');
                button.classList.add('btn-primary');
            }, 2000);
        }
    </script>
</body>
</html>

