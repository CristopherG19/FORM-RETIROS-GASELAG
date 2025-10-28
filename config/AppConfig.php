<?php
/**
 * Configuración del sistema optimizada
 * GASELAG - Sistema de Retiros de Medidores
 */

// ===== CONFIGURACIÓN GENERAL =====
define('APP_NAME', 'GASELAG - Sistema de Retiros');
define('APP_VERSION', '2.0.0');
define('APP_ENV', 'production'); // development, production

// ===== CONFIGURACIÓN DE ARCHIVOS =====
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/gif']);

// ===== CONFIGURACIÓN DE SESIONES =====
define('SESSION_LIFETIME', 3600); // 1 hora
define('SESSION_NAME', 'GASELAG_SESSION');

// ===== CONFIGURACIÓN DE AUDITORÍA =====
define('AUDIT_RETENTION_DAYS', 365); // 1 año
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR

// ===== CONFIGURACIÓN DE PAGINACIÓN =====
define('DEFAULT_PAGE_SIZE', 20);
define('MAX_PAGE_SIZE', 100);

// ===== CONFIGURACIÓN DE EXPORTACIÓN =====
define('EXPORT_MAX_RECORDS', 10000);
define('EXPORT_FORMAT', 'CSV');

// ===== CONFIGURACIÓN DE NOTIFICACIONES =====
define('NOTIFICATION_TIMEOUT', 6); // horas para evidencia fotográfica

// ===== FUNCIONES DE UTILIDAD =====

/**
 * Obtiene configuración de la aplicación
 */
function getAppConfig($key = null) {
    $config = [
        'name' => APP_NAME,
        'version' => APP_VERSION,
        'environment' => APP_ENV,
        'upload_dir' => UPLOAD_DIR,
        'max_file_size' => MAX_FILE_SIZE,
        'session_lifetime' => SESSION_LIFETIME,
        'default_page_size' => DEFAULT_PAGE_SIZE,
        'export_max_records' => EXPORT_MAX_RECORDS,
        'notification_timeout' => NOTIFICATION_TIMEOUT
    ];
    
    return $key ? ($config[$key] ?? null) : $config;
}

/**
 * Verifica si estamos en modo desarrollo
 */
function isDevelopment() {
    return APP_ENV === 'development';
}

/**
 * Genera URL completa para el sistema
 */
function getAppUrl($path = '') {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $basePath = dirname($_SERVER['SCRIPT_NAME']);
    
    return $protocol . '://' . $host . $basePath . '/' . ltrim($path, '/');
}

/**
 * Obtiene ruta completa de archivo
 */
function getFilePath($filename) {
    return UPLOAD_DIR . $filename;
}

/**
 * Verifica si el archivo existe y es válido
 */
function isValidFile($file) {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return false;
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return false;
    }
    
    if (!in_array($file['type'], ALLOWED_IMAGE_TYPES)) {
        return false;
    }
    
    return true;
}

/**
 * Genera respuesta JSON estandarizada
 */
function jsonResponse($data = null, $success = true, $message = '', $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    
    $response = [
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Registra mensaje de log
 */
function logMessage($level, $message, $context = []) {
    if (!isDevelopment() && $level === 'DEBUG') {
        return;
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
    $logMessage = "[$timestamp] [$level] $message$contextStr" . PHP_EOL;
    
    error_log($logMessage, 3, 'logs/app.log');
}

/**
 * Maneja errores de manera centralizada
 */
function handleError($message, $code = 500, $logContext = []) {
    logMessage('ERROR', $message, $logContext);
    
    if (isDevelopment()) {
        die("Error: $message");
    } else {
        die("Ha ocurrido un error. Contacte al administrador.");
    }
}

/**
 * Valida entrada de formulario
 */
function validateFormData($data, $rules) {
    $errors = [];
    
    foreach ($rules as $field => $rule) {
        $value = $data[$field] ?? null;
        
        // Campo requerido
        if (isset($rule['required']) && $rule['required'] && empty($value)) {
            $errors[] = "El campo {$rule['label']} es requerido";
            continue;
        }
        
        // Si el campo está vacío y no es requerido, continuar
        if (empty($value)) {
            continue;
        }
        
        // Validación de longitud mínima
        if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
            $errors[] = "El campo {$rule['label']} debe tener al menos {$rule['min_length']} caracteres";
        }
        
        // Validación de longitud máxima
        if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
            $errors[] = "El campo {$rule['label']} no puede tener más de {$rule['max_length']} caracteres";
        }
        
        // Validación de formato
        if (isset($rule['pattern']) && !preg_match($rule['pattern'], $value)) {
            $errors[] = "El campo {$rule['label']} tiene un formato inválido";
        }
        
        // Validación personalizada
        if (isset($rule['custom']) && is_callable($rule['custom'])) {
            $customError = $rule['custom']($value);
            if ($customError) {
                $errors[] = $customError;
            }
        }
    }
    
    return $errors;
}

/**
 * Sanitiza datos de entrada
 */
function sanitizeData($data) {
    if (is_array($data)) {
        return array_map('sanitizeData', $data);
    }
    
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Genera token CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica token CSRF
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Formatea fecha para mostrar
 */
function formatDate($date, $format = 'd/m/Y H:i') {
    if (empty($date)) {
        return '-';
    }
    
    try {
        $dateObj = new DateTime($date);
        return $dateObj->format($format);
    } catch (Exception $e) {
        return $date;
    }
}

/**
 * Calcula tiempo transcurrido
 */
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'hace un momento';
    if ($time < 3600) return 'hace ' . floor($time/60) . ' minutos';
    if ($time < 86400) return 'hace ' . floor($time/3600) . ' horas';
    if ($time < 2592000) return 'hace ' . floor($time/86400) . ' días';
    if ($time < 31536000) return 'hace ' . floor($time/2592000) . ' meses';
    
    return 'hace ' . floor($time/31536000) . ' años';
}

/**
 * Genera paginación
 */
function generatePagination($currentPage, $totalPages, $baseUrl) {
    $pagination = [];
    
    // Página anterior
    if ($currentPage > 1) {
        $pagination[] = [
            'page' => $currentPage - 1,
            'label' => 'Anterior',
            'url' => $baseUrl . '?page=' . ($currentPage - 1),
            'active' => false
        ];
    }
    
    // Páginas numeradas
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        $pagination[] = [
            'page' => $i,
            'label' => $i,
            'url' => $baseUrl . '?page=' . $i,
            'active' => $i === $currentPage
        ];
    }
    
    // Página siguiente
    if ($currentPage < $totalPages) {
        $pagination[] = [
            'page' => $currentPage + 1,
            'label' => 'Siguiente',
            'url' => $baseUrl . '?page=' . ($currentPage + 1),
            'active' => false
        ];
    }
    
    return $pagination;
}

/**
 * Obtiene estadísticas del sistema
 */
function getSystemStats() {
    $pdo = getConnection();
    
    try {
        $stats = [];
        
        // Total de retiros
        $sql = "SELECT COUNT(*) as total FROM retiros_medidores";
        $result = $pdo->query($sql)->fetch();
        $stats['total_retiros'] = $result['total'];
        
        // Retiros exitosos
        $sql = "SELECT COUNT(*) as total FROM retiros_medidores WHERE medidor_retirado = 'SI'";
        $result = $pdo->query($sql)->fetch();
        $stats['retiros_exitosos'] = $result['total'];
        
        // Retiros con imposibilidad
        $sql = "SELECT COUNT(*) as total FROM retiros_medidores WHERE medidor_retirado = 'NO'";
        $result = $pdo->query($sql)->fetch();
        $stats['retiros_imposibilidad'] = $result['total'];
        
        // Usuarios activos
        $sql = "SELECT COUNT(*) as total FROM usuarios WHERE estado = 'activo'";
        $result = $pdo->query($sql)->fetch();
        $stats['usuarios_activos'] = $result['total'];
        
        return $stats;
    } catch (Exception $e) {
        logMessage('ERROR', 'Error obteniendo estadísticas del sistema', ['error' => $e->getMessage()]);
        return [];
    }
}

?>
