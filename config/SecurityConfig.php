<?php
/**
 * Configuración de Seguridad - GASELAG
 * Sistema diferenciado por roles: Técnicos vs Administradores
 */

// ===== CONFIGURACIÓN DE SESIÓN =====
define('SESSION_TIMEOUT_ADMIN', 1800);     // 30 minutos para admins
define('SESSION_TIMEOUT_USER', 7200);      // 2 horas para técnicos
define('SESSION_WARNING_TIME', 300);       // Advertir 5 min antes de expirar
define('SESSION_EXTEND_TIME', 1800);       // Extender sesión 30 min adicionales

// ===== POLÍTICA DE CONTRASEÑAS - ADMINISTRADORES =====
define('PASSWORD_MIN_LENGTH_ADMIN', 12);
define('PASSWORD_REQUIRE_UPPERCASE_ADMIN', true);
define('PASSWORD_REQUIRE_LOWERCASE_ADMIN', true);
define('PASSWORD_REQUIRE_NUMBERS_ADMIN', 2);
define('PASSWORD_REQUIRE_SYMBOLS_ADMIN', 2);
define('PASSWORD_EXPIRY_DAYS_ADMIN', 90);  // Cambio cada 90 días

// ===== POLÍTICA DE PIN - TÉCNICOS =====
define('PIN_MIN_LENGTH_USER', 4);
define('PIN_MAX_LENGTH_USER', 6);
define('PIN_ONLY_NUMBERS_USER', true);
define('PIN_EXPIRY_DAYS_USER', 0);         // Sin expiración para técnicos

// ===== HISTORIAL DE CONTRASEÑAS =====
define('PASSWORD_HISTORY_COUNT', 5);       // No reutilizar últimas 5 contraseñas

// ===== BLOQUEO POR INTENTOS FALLIDOS =====
// Técnicos (más permisivo)
define('MAX_LOGIN_ATTEMPTS_USER', 10);
define('LOGIN_BLOCK_TIME_USER', 600);      // 10 minutos

// Administradores (más estricto)
define('MAX_LOGIN_ATTEMPTS_ADMIN', 5);
define('LOGIN_BLOCK_TIME_ADMIN', 300);     // 5 minutos

// Bloqueo permanente (requiere admin)
define('MAX_LOGIN_ATTEMPTS_PERMANENT', 15);

// ===== RATE LIMITING (por IP) =====
define('RATE_LIMIT_WINDOW', 300);          // 5 minutos
define('RATE_LIMIT_MAX_REQUESTS', 5);      // 5 intentos por IP en 5 min

// ===== CONFIGURACIÓN DE DISPOSITIVOS =====
define('DEVICE_REMEMBER_DAYS', 30);        // Recordar dispositivo 30 días
define('DEVICE_MAX_PER_USER', 3);          // Máximo 3 dispositivos por técnico

// ===== TOKENS CSRF =====
define('CSRF_TOKEN_EXPIRY', 3600);         // 1 hora

// ===== PINES PROHIBIDOS (comunes y débiles) =====
define('FORBIDDEN_PINS', [
    '0000', '1111', '2222', '3333', '4444', '5555', '6666', '7777', '8888', '9999',
    '1234', '4321', '1122', '2211', '1212', '2121',
    '0123', '3210', '9876', '6789'
]);

// ===== PALABRAS PROHIBIDAS EN CONTRASEÑAS =====
define('FORBIDDEN_PASSWORDS', [
    'password', 'contraseña', 'admin', 'administrador',
    'gaselag', 'retiro', 'medidor', 'tecnico',
    '12345678', '123456789', 'qwerty', 'abc123'
]);

// ===== LIMPIEZA AUTOMÁTICA =====
define('CLEANUP_LOGIN_ATTEMPTS_HOURS', 24); // Limpiar intentos >24h
define('CLEANUP_INACTIVE_SESSIONS_HOURS', 48); // Limpiar sesiones >48h

/**
 * Obtener timeout de sesión según rol
 */
function getSessionTimeout($role = null) {
    if ($role === null && isset($_SESSION['user_role'])) {
        $role = $_SESSION['user_role'];
    }
    
    return ($role === 'admin') ? SESSION_TIMEOUT_ADMIN : SESSION_TIMEOUT_USER;
}

/**
 * Obtener máximo de intentos de login según rol
 */
function getMaxLoginAttempts($role) {
    return ($role === 'admin') ? MAX_LOGIN_ATTEMPTS_ADMIN : MAX_LOGIN_ATTEMPTS_USER;
}

/**
 * Obtener tiempo de bloqueo según rol
 */
function getLoginBlockTime($role) {
    return ($role === 'admin') ? LOGIN_BLOCK_TIME_ADMIN : LOGIN_BLOCK_TIME_USER;
}

/**
 * Verificar si es un PIN válido (solo para técnicos)
 */
function isValidPINFormat($pin) {
    $length = strlen($pin);
    return $length >= PIN_MIN_LENGTH_USER 
        && $length <= PIN_MAX_LENGTH_USER 
        && ctype_digit($pin);
}

/**
 * Verificar si el PIN está en la lista prohibida
 */
function isForbiddenPIN($pin) {
    return in_array($pin, FORBIDDEN_PINS);
}

/**
 * Verificar si la contraseña contiene palabras prohibidas
 */
function containsForbiddenWords($password) {
    $passwordLower = strtolower($password);
    foreach (FORBIDDEN_PASSWORDS as $forbidden) {
        if (strpos($passwordLower, $forbidden) !== false) {
            return true;
        }
    }
    return false;
}
