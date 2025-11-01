<?php
/**
 * Política de Contraseñas y PINs - GASELAG
 * Validación diferenciada por rol
 */

require_once __DIR__ . '/SecurityConfig.php';

/**
 * Validar PIN para técnicos (usuarios)
 * @param string $pin
 * @param string $username DNI del técnico
 * @return array ['valid' => bool, 'errors' => array]
 */
function validatePIN($pin, $username = '') {
    $errors = [];
    
    // Verificar formato básico
    if (!isValidPINFormat($pin)) {
        $errors[] = "El PIN debe tener entre " . PIN_MIN_LENGTH_USER . " y " . PIN_MAX_LENGTH_USER . " dígitos numéricos";
        return ['valid' => false, 'errors' => $errors];
    }
    
    // Verificar que no sea un PIN prohibido
    if (isForbiddenPIN($pin)) {
        $errors[] = "Este PIN es demasiado común y no está permitido (ej: 0000, 1234, 4321)";
    }
    
    // Verificar que no sea igual al username (DNI)
    if (!empty($username) && $pin === $username) {
        $errors[] = "El PIN no puede ser igual a tu DNI";
    }
    
    // Verificar que no sea una secuencia simple
    if (isSequentialPIN($pin)) {
        $errors[] = "El PIN no puede ser una secuencia simple (ej: 1234, 5678)";
    }
    
    // Verificar que no tenga todos los dígitos iguales
    if (strlen(count_chars($pin, 3)) === 1) {
        $errors[] = "El PIN no puede tener todos los dígitos iguales";
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Validar contraseña compleja para administradores
 * @param string $password
 * @param string $username
 * @param string $email
 * @return array ['valid' => bool, 'errors' => array, 'strength' => int]
 */
function validatePassword($password, $username = '', $email = '') {
    $errors = [];
    $strength = 0;
    
    // Longitud mínima
    if (strlen($password) < PASSWORD_MIN_LENGTH_ADMIN) {
        $errors[] = "La contraseña debe tener al menos " . PASSWORD_MIN_LENGTH_ADMIN . " caracteres";
    } else {
        $strength += 20;
    }
    
    // Mayúsculas
    if (PASSWORD_REQUIRE_UPPERCASE_ADMIN && !preg_match('/[A-Z]/', $password)) {
        $errors[] = "Debe contener al menos una letra mayúscula";
    } else {
        $strength += 15;
    }
    
    // Minúsculas
    if (PASSWORD_REQUIRE_LOWERCASE_ADMIN && !preg_match('/[a-z]/', $password)) {
        $errors[] = "Debe contener al menos una letra minúscula";
    } else {
        $strength += 15;
    }
    
    // Números
    $numberCount = preg_match_all('/[0-9]/', $password);
    if ($numberCount < PASSWORD_REQUIRE_NUMBERS_ADMIN) {
        $errors[] = "Debe contener al menos " . PASSWORD_REQUIRE_NUMBERS_ADMIN . " números";
    } else {
        $strength += 20;
    }
    
    // Símbolos
    $symbolCount = preg_match_all('/[!@#$%^&*()_+\-=\[\]{}|;:,.<>?]/', $password);
    if ($symbolCount < PASSWORD_REQUIRE_SYMBOLS_ADMIN) {
        $errors[] = "Debe contener al menos " . PASSWORD_REQUIRE_SYMBOLS_ADMIN . " símbolos especiales (!@#$%^&*)";
    } else {
        $strength += 30;
    }
    
    // Verificar palabras prohibidas
    if (containsForbiddenWords($password)) {
        $errors[] = "La contraseña contiene palabras comunes no permitidas (password, admin, gaselag, etc.)";
        $strength -= 20;
    }
    
    // Verificar que no contenga el username
    if (!empty($username) && stripos($password, $username) !== false) {
        $errors[] = "La contraseña no puede contener tu nombre de usuario";
        $strength -= 20;
    }
    
    // Verificar que no contenga el email
    if (!empty($email)) {
        $emailParts = explode('@', $email);
        if (stripos($password, $emailParts[0]) !== false) {
            $errors[] = "La contraseña no puede contener tu email";
            $strength -= 20;
        }
    }
    
    // Bonus por longitud extra
    if (strlen($password) >= 16) {
        $strength += 10;
    }
    
    // Bonus por variedad de caracteres
    $uniqueChars = count(array_unique(str_split($password)));
    if ($uniqueChars >= 10) {
        $strength += 10;
    }
    
    // Limitar strength entre 0 y 100
    $strength = max(0, min(100, $strength));
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'strength' => $strength
    ];
}

/**
 * Verificar si ya se usó esta contraseña antes
 * @param int $userId
 * @param string $newPasswordHash
 * @return bool true si ya fue usada
 */
function isPasswordReused($userId, $newPassword) {
    try {
        $pdo = getConnection();
        
        // Obtener últimas PASSWORD_HISTORY_COUNT contraseñas
        $stmt = $pdo->prepare("
            SELECT password_hash 
            FROM password_history 
            WHERE usuario_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$userId, PASSWORD_HISTORY_COUNT]);
        
        $oldPasswords = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Verificar si la nueva contraseña coincide con alguna anterior
        foreach ($oldPasswords as $oldHash) {
            if (password_verify($newPassword, $oldHash)) {
                return true;
            }
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Error verificando historial de contraseñas: " . $e->getMessage());
        return false;
    }
}

/**
 * Guardar contraseña en el historial
 * @param int $userId
 * @param string $passwordHash
 */
function savePasswordToHistory($userId, $passwordHash) {
    try {
        $pdo = getConnection();
        
        // Insertar nueva contraseña
        $stmt = $pdo->prepare("
            INSERT INTO password_history (usuario_id, password_hash) 
            VALUES (?, ?)
        ");
        $stmt->execute([$userId, $passwordHash]);
        
        // Mantener solo las últimas PASSWORD_HISTORY_COUNT
        $pdo->prepare("
            DELETE FROM password_history 
            WHERE usuario_id = ? 
            AND id NOT IN (
                SELECT id FROM (
                    SELECT id FROM password_history 
                    WHERE usuario_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT ?
                ) tmp
            )
        ")->execute([$userId, $userId, PASSWORD_HISTORY_COUNT]);
        
    } catch (Exception $e) {
        error_log("Error guardando contraseña en historial: " . $e->getMessage());
    }
}

/**
 * Verificar si el PIN es secuencial
 */
function isSequentialPIN($pin) {
    $length = strlen($pin);
    
    // Verificar secuencia ascendente (1234, 5678)
    $isAscending = true;
    for ($i = 0; $i < $length - 1; $i++) {
        if ((int)$pin[$i] + 1 !== (int)$pin[$i + 1]) {
            $isAscending = false;
            break;
        }
    }
    
    // Verificar secuencia descendente (4321, 8765)
    $isDescending = true;
    for ($i = 0; $i < $length - 1; $i++) {
        if ((int)$pin[$i] - 1 !== (int)$pin[$i + 1]) {
            $isDescending = false;
            break;
        }
    }
    
    return $isAscending || $isDescending;
}

/**
 * Obtener fortaleza de contraseña como texto
 */
function getPasswordStrengthText($strength) {
    if ($strength < 30) return 'Muy Débil';
    if ($strength < 50) return 'Débil';
    if ($strength < 70) return 'Aceptable';
    if ($strength < 90) return 'Fuerte';
    return 'Muy Fuerte';
}

/**
 * Obtener color para barra de fortaleza
 */
function getPasswordStrengthColor($strength) {
    if ($strength < 30) return 'danger';
    if ($strength < 50) return 'warning';
    if ($strength < 70) return 'info';
    return 'success';
}
