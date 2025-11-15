<?php
/**
 * Rate Limiter y Control de Intentos de Login - GASELAG
 * Sistema de bloqueo por intentos fallidos
 */

require_once __DIR__ . '/SecurityConfig.php';

/**
 * Registrar intento de login
 * @param string $username
 * @param string $ip
 * @param bool $success
 * @param string $userAgent
 */
function recordLoginAttempt($username, $ip, $success, $userAgent = '') {
    try {
        $pdo = getConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO login_attempts (username, ip_address, success, user_agent, attempt_time) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$username, $ip, $success ? 1 : 0, $userAgent]);
        
        // Si fue fallido, actualizar contador en usuarios
        if (!$success) {
            $pdo->prepare("
                UPDATE usuarios 
                SET intentos_fallidos = intentos_fallidos + 1,
                    ultimo_intento = NOW()
                WHERE username = ?
            ")->execute([$username]);
        } else {
            // Si fue exitoso, resetear contador
            $pdo->prepare("
                UPDATE usuarios 
                SET intentos_fallidos = 0,
                    bloqueado_hasta = NULL,
                    ultimo_intento = NOW()
                WHERE username = ?
            ")->execute([$username]);
        }
        
    } catch (Exception $e) {
        error_log("Error registrando intento de login: " . $e->getMessage());
    }
}

/**
 * Verificar si una cuenta está bloqueada
 * @param string $username
 * @param string $ip
 * @return array ['blocked' => bool, 'reason' => string, 'until' => timestamp, 'remaining' => seconds]
 */
function isAccountBlocked($username, $ip) {
    try {
        $pdo = getConnection();
        
        // Verificar bloqueo por usuario
        $stmt = $pdo->prepare("
            SELECT intentos_fallidos, bloqueado_hasta, rol 
            FROM usuarios 
            WHERE username = ? AND estado = 'activo'
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return [
                'blocked' => false,
                'reason' => '',
                'until' => null,
                'remaining' => 0
            ];
        }
        
        $now = time();
        
        // Verificar bloqueo temporal existente
        if ($user['bloqueado_hasta'] !== null) {
            $blockedUntil = strtotime($user['bloqueado_hasta']);
            if ($blockedUntil > $now) {
                $remaining = $blockedUntil - $now;
                return [
                    'blocked' => true,
                    'reason' => 'Cuenta bloqueada temporalmente por múltiples intentos fallidos',
                    'until' => $user['bloqueado_hasta'],
                    'remaining' => $remaining
                ];
            } else {
                // El bloqueo expiró, limpiar
                $pdo->prepare("
                    UPDATE usuarios 
                    SET bloqueado_hasta = NULL, intentos_fallidos = 0 
                    WHERE username = ?
                ")->execute([$username]);
            }
        }
        
        // Verificar si se alcanzó el máximo de intentos
        $maxAttempts = getMaxLoginAttempts($user['rol']);
        if ($user['intentos_fallidos'] >= MAX_LOGIN_ATTEMPTS_PERMANENT) {
            // Bloqueo permanente
            return [
                'blocked' => true,
                'reason' => 'Cuenta bloqueada permanentemente. Contacte al administrador',
                'until' => null,
                'remaining' => -1 // Indica bloqueo permanente
            ];
        } elseif ($user['intentos_fallidos'] >= $maxAttempts) {
            // Aplicar bloqueo temporal
            $blockTime = getLoginBlockTime($user['rol']);
            $blockedUntil = date('Y-m-d H:i:s', $now + $blockTime);
            
            $pdo->prepare("
                UPDATE usuarios 
                SET bloqueado_hasta = ? 
                WHERE username = ?
            ")->execute([$blockedUntil, $username]);
            
            return [
                'blocked' => true,
                'reason' => 'Cuenta bloqueada temporalmente por múltiples intentos fallidos',
                'until' => $blockedUntil,
                'remaining' => $blockTime
            ];
        }
        
        // Verificar rate limiting por IP
        $ipBlocked = checkIPRateLimit($ip);
        if ($ipBlocked['blocked']) {
            return $ipBlocked;
        }
        
        return [
            'blocked' => false,
            'reason' => '',
            'until' => null,
            'remaining' => 0,
            'attempts_left' => $maxAttempts - $user['intentos_fallidos']
        ];
        
    } catch (Exception $e) {
        error_log("Error verificando bloqueo de cuenta: " . $e->getMessage());
        return ['blocked' => false, 'reason' => '', 'until' => null, 'remaining' => 0];
    }
}

/**
 * Verificar rate limiting por IP
 * @param string $ip
 * @return array
 */
function checkIPRateLimit($ip) {
    try {
        $pdo = getConnection();
        
        $windowStart = date('Y-m-d H:i:s', time() - RATE_LIMIT_WINDOW);
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as attempts 
            FROM login_attempts 
            WHERE ip_address = ? 
            AND attempt_time > ? 
            AND success = 0
        ");
        $stmt->execute([$ip, $windowStart]);
        $result = $stmt->fetch();
        
        if ($result['attempts'] >= RATE_LIMIT_MAX_REQUESTS) {
            return [
                'blocked' => true,
                'reason' => 'Demasiados intentos desde esta IP. Intente más tarde',
                'until' => null,
                'remaining' => RATE_LIMIT_WINDOW
            ];
        }
        
        return ['blocked' => false];
        
    } catch (Exception $e) {
        error_log("Error verificando rate limit de IP: " . $e->getMessage());
        return ['blocked' => false];
    }
}

/**
 * Desbloquear cuenta manualmente (solo admin)
 * @param string $username
 * @param int $adminId
 * @return bool
 */
function unlockAccount($username, $adminId) {
    try {
        $pdo = getConnection();
        
        $stmt = $pdo->prepare("
            UPDATE usuarios 
            SET intentos_fallidos = 0, 
                bloqueado_hasta = NULL 
            WHERE username = ?
        ");
        $stmt->execute([$username]);
        
        // Registrar en auditoría
        logAudit(null, $adminId, 'login', "Admin desbloqueó cuenta: $username");
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error desbloqueando cuenta: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtener tiempo restante de bloqueo formateado
 * @param int $seconds
 * @return string
 */
function getBlockTimeRemaining($seconds) {
    if ($seconds <= 0) {
        return 'Bloqueo expirado';
    }
    
    if ($seconds < 60) {
        return $seconds . ' segundos';
    }
    
    $minutes = floor($seconds / 60);
    $remainingSeconds = $seconds % 60;
    
    if ($minutes < 60) {
        return $minutes . ' minuto' . ($minutes > 1 ? 's' : '') . 
               ($remainingSeconds > 0 ? ' y ' . $remainingSeconds . ' segundos' : '');
    }
    
    $hours = floor($minutes / 60);
    $remainingMinutes = $minutes % 60;
    
    return $hours . ' hora' . ($hours > 1 ? 's' : '') . 
           ($remainingMinutes > 0 ? ' y ' . $remainingMinutes . ' minutos' : '');
}

/**
 * Obtener cuentas bloqueadas (para panel admin)
 * @return array
 */
function getBlockedAccounts() {
    try {
        $pdo = getConnection();
        
        $stmt = $pdo->query("
            SELECT username, nombre_completo, rol, intentos_fallidos, 
                   bloqueado_hasta, ultimo_intento, ultimo_login
            FROM usuarios 
            WHERE (intentos_fallidos >= " . MAX_LOGIN_ATTEMPTS_USER . " 
                   OR bloqueado_hasta IS NOT NULL)
            AND estado = 'activo'
            ORDER BY ultimo_intento DESC
        ");
        
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Error obteniendo cuentas bloqueadas: " . $e->getMessage());
        return [];
    }
}

/**
 * Limpiar intentos antiguos de login (>24 horas)
 * Ejecutar periódicamente por cron o manualmente
 */
function cleanupOldLoginAttempts() {
    try {
        $pdo = getConnection();
        
        $cutoffTime = date('Y-m-d H:i:s', time() - (CLEANUP_LOGIN_ATTEMPTS_HOURS * 3600));
        
        $stmt = $pdo->prepare("
            DELETE FROM login_attempts 
            WHERE attempt_time < ?
        ");
        $stmt->execute([$cutoffTime]);
        
        $deleted = $stmt->rowCount();
        error_log("Limpieza de intentos de login: $deleted registros eliminados");
        
        return $deleted;
        
    } catch (Exception $e) {
        error_log("Error limpiando intentos antiguos: " . $e->getMessage());
        return 0;
    }
}

/**
 * Obtener estadísticas de intentos de login
 * @param int $hours Últimas N horas
 * @return array
 */
function getLoginStats($hours = 24) {
    try {
        $pdo = getConnection();
        
        $since = date('Y-m-d H:i:s', time() - ($hours * 3600));
        
        $stats = [];
        
        // Total de intentos
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total, 
                   SUM(success) as successful, 
                   COUNT(*) - SUM(success) as failed
            FROM login_attempts 
            WHERE attempt_time > ?
        ");
        $stmt->execute([$since]);
        $stats['attempts'] = $stmt->fetch();
        
        // Top IPs con intentos fallidos
        $stmt = $pdo->prepare("
            SELECT ip_address, COUNT(*) as count
            FROM login_attempts 
            WHERE attempt_time > ? AND success = 0
            GROUP BY ip_address 
            ORDER BY count DESC 
            LIMIT 10
        ");
        $stmt->execute([$since]);
        $stats['top_failed_ips'] = $stmt->fetchAll();
        
        // Top usernames con intentos fallidos
        $stmt = $pdo->prepare("
            SELECT username, COUNT(*) as count
            FROM login_attempts 
            WHERE attempt_time > ? AND success = 0
            GROUP BY username 
            ORDER BY count DESC 
            LIMIT 10
        ");
        $stmt->execute([$since]);
        $stats['top_failed_users'] = $stmt->fetchAll();
        
        return $stats;
        
    } catch (Exception $e) {
        error_log("Error obteniendo estadísticas de login: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtener logins exitosos recientes
 */
function getRecentSuccessfulLogins($hours = 24, $limit = 50) {
    try {
        $pdo = getConnection();
        $since = date('Y-m-d H:i:s', time() - ($hours * 3600));
        
        $stmt = $pdo->prepare("
            SELECT 
                la.username, 
                la.ip_address, 
                la.attempt_time,
                la.user_agent,
                u.nombre_completo,
                u.rol
            FROM login_attempts la
            LEFT JOIN usuarios u ON la.username = u.username
            WHERE la.attempt_time > ? AND la.success = 1
            ORDER BY la.attempt_time DESC
            LIMIT ?
        ");
        $stmt->execute([$since, $limit]);
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Error obteniendo logins exitosos: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtener historial de desbloqueos manuales
 */
function getUnlockHistory($hours = 168, $limit = 50) {
    try {
        $pdo = getConnection();
        $since = date('Y-m-d H:i:s', time() - ($hours * 3600));
        
        $stmt = $pdo->prepare("
            SELECT 
                ar.accion,
                ar.descripcion,
                ar.fecha,
                ar.ip_address,
                u_target.username as cuenta_desbloqueada,
                u_target.nombre_completo as nombre_cuenta,
                u_admin.username as admin_username,
                u_admin.nombre_completo as admin_nombre
            FROM auditoria_retiros ar
            LEFT JOIN usuarios u_target ON ar.usuario_id = u_target.id
            LEFT JOIN usuarios u_admin ON ar.usuario_id = u_admin.id
            WHERE ar.accion = 'cuenta_desbloqueada' 
                AND ar.fecha > ?
            ORDER BY ar.fecha DESC
            LIMIT ?
        ");
        $stmt->execute([$since, $limit]);
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Error obteniendo historial de desbloqueos: " . $e->getMessage());
        return [];
    }
}

/**
 * Detectar actividades sospechosas y generar alertas
 */
function detectSecurityAlerts($hours = 24) {
    try {
        $pdo = getConnection();
        $since = date('Y-m-d H:i:s', time() - ($hours * 3600));
        $alerts = [];
        
        // Alerta 1: IPs con muchos intentos fallidos (>10)
        $stmt = $pdo->prepare("
            SELECT ip_address, COUNT(*) as count
            FROM login_attempts 
            WHERE attempt_time > ? AND success = 0
            GROUP BY ip_address 
            HAVING count > 10
        ");
        $stmt->execute([$since]);
        $suspiciousIPs = $stmt->fetchAll();
        
        foreach ($suspiciousIPs as $ip) {
            $alerts[] = [
                'type' => 'danger',
                'icon' => 'bi-exclamation-triangle-fill',
                'message' => "IP {$ip['ip_address']} tiene {$ip['count']} intentos fallidos (posible ataque de fuerza bruta)"
            ];
        }
        
        // Alerta 2: Cuentas de admin bloqueadas
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM usuarios
            WHERE rol = 'admin' AND bloqueado = 1
        ");
        $stmt->execute();
        $blockedAdmins = $stmt->fetch()['count'];
        
        if ($blockedAdmins > 0) {
            $alerts[] = [
                'type' => 'danger',
                'icon' => 'bi-shield-exclamation',
                'message' => "{$blockedAdmins} cuenta(s) de administrador bloqueadas - Revisar inmediatamente"
            ];
        }
        
        // Alerta 3: Múltiples usuarios bloqueados en corto tiempo
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM usuarios
            WHERE bloqueado = 1 
                AND bloqueado_hasta > NOW()
        ");
        $stmt->execute();
        $totalBlocked = $stmt->fetch()['count'];
        
        if ($totalBlocked >= 5) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'bi-exclamation-circle-fill',
                'message' => "{$totalBlocked} cuentas bloqueadas simultáneamente - Posible ataque coordinado"
            ];
        }
        
        // Alerta 4: Logins exitosos desde IPs con intentos fallidos previos
        $stmt = $pdo->prepare("
            SELECT DISTINCT la1.username, la1.ip_address
            FROM login_attempts la1
            WHERE la1.success = 1 
                AND la1.attempt_time > ?
                AND EXISTS (
                    SELECT 1 FROM login_attempts la2
                    WHERE la2.ip_address = la1.ip_address
                        AND la2.username = la1.username
                        AND la2.success = 0
                        AND la2.attempt_time < la1.attempt_time
                        AND la2.attempt_time > DATE_SUB(la1.attempt_time, INTERVAL 1 HOUR)
                )
            LIMIT 5
        ");
        $stmt->execute([$since]);
        $suspiciousLogins = $stmt->fetchAll();
        
        foreach ($suspiciousLogins as $login) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'bi-shield-fill-exclamation',
                'message' => "Usuario '{$login['username']}' ingresó exitosamente desde IP {$login['ip_address']} después de varios intentos fallidos"
            ];
        }
        
        return $alerts;
        
    } catch (Exception $e) {
        error_log("Error detectando alertas de seguridad: " . $e->getMessage());
        return [];
    }
}