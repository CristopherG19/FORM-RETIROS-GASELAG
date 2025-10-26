<?php
/**
 * Configuración de conexión a base de datos
 * GASELAG - Sistema de Retiros de Medidores
 */

// Configuración de base de datos
define('DB_HOST', 'localhost');
define('DB_PORT', '3307');  // Puerto personalizado de MySQL
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'gaselag_retiros');
define('DB_CHARSET', 'utf8mb4');

// Crear conexión
function getConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        die("Error de conexión: " . $e->getMessage());
    }
}

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generar ID de sesión único si no existe
if (!isset($_SESSION['session_id'])) {
    $_SESSION['session_id'] = uniqid('session_', true);
}

// Función para identificar retiros sin evidencia fotográfica
function getRetirosImposibilidadSinFoto($pdo) {
    try {
        $sql = "SELECT
                    r.*,
                    o.cliente,
                    o.usuario_reclamante,
                    o.num_serie_medidor,
                    o.direccion,
                    CASE
                        WHEN r.visor_imposibilidad_lectura = 'SI' THEN 'Imposibilidad declarada'
                        ELSE 'No retirado (sin especificar)'
                    END as tipo_caso
                FROM retiros_medidores r
                INNER JOIN ordenes_servicio o ON r.orden_servicio_id = o.id
                WHERE r.medidor_retirado = 'NO'
                AND (r.foto_imposibilidad IS NULL OR r.foto_imposibilidad = '')
                ORDER BY
                    CASE
                        WHEN r.visor_imposibilidad_lectura = 'SI' THEN 1
                        ELSE 2
                    END,
                    r.fecha_registro DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Exception $e) {
        die("Error al consultar retiros sin evidencia fotográfica: " . $e->getMessage());
    }
}

// Función para contar retiros sin evidencia fotográfica
function countRetirosImposibilidadSinFoto($pdo) {
    try {
        $sql = "SELECT COUNT(*) as total
                FROM retiros_medidores r
                WHERE r.medidor_retirado = 'NO'
                AND (r.foto_imposibilidad IS NULL OR r.foto_imposibilidad = '')";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['total'];
    } catch (Exception $e) {
        return 0;
    }
}

// ===== SISTEMA DE AUTENTICACIÓN =====

// Función para verificar si el usuario está logueado
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

// Función para verificar el rol del usuario
function getUserRole() {
    return isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null;
}

// Función para verificar si el usuario es administrador
function isAdmin() {
    return getUserRole() === 'admin';
}

// Función para verificar si el usuario es técnico
function isUser() {
    return getUserRole() === 'user';
}

// Función para hacer login
function login($username, $password) {
    $pdo = getConnection();

    try {
        $sql = "SELECT id, username, password, nombre_completo, email, rol, estado
                FROM usuarios
                WHERE username = :username AND estado = 'activo'";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Actualizar último login
            $updateSql = "UPDATE usuarios SET ultimo_login = NOW() WHERE id = :id";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->bindParam(':id', $user['id']);
            $updateStmt->execute();

            // Registrar login en auditoría
            logAudit(null, $user['id'], 'login', "Login exitoso desde IP: " . getClientIP());

            // Guardar en sesión
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['rol'];
            $_SESSION['nombre_completo'] = $user['nombre_completo'];

            return true;
        }

        // Registrar intento fallido en auditoría
        logAudit(null, null, 'login', "Intento fallido de login para usuario: $username desde IP: " . getClientIP());
        return false;
    } catch (PDOException $e) {
        error_log("Error en login: " . $e->getMessage());
        return false;
    }
}

// Función para hacer logout
function logout() {
    // Registrar logout en auditoría antes de destruir la sesión
    if (isset($_SESSION['user_id'])) {
        logAudit(null, $_SESSION['user_id'], 'logout', "Logout desde IP: " . getClientIP());
    }

    // Limpiar todas las variables de sesión
    $_SESSION = array();

    // Destruir la sesión
    if (session_id() != "" || isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    session_destroy();

    // Reiniciar sesión
    session_start();
    $_SESSION['session_id'] = uniqid('session_', true);
}

// Función para verificar acceso a una página según el rol
function requireRole($requiredRoles) {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }

    if (!in_array(getUserRole(), $requiredRoles)) {
        // Redirigir según el rol del usuario
        if (isAdmin()) {
            header('Location: index.php');
        } else {
            header('Location: pages/consultar_retiros.php');
        }
        exit;
    }
}

// Función para obtener información del usuario actual
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }

    $pdo = getConnection();

    try {
        $sql = "SELECT id, username, nombre_completo, email, rol, estado, ultimo_login
                FROM usuarios WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $_SESSION['user_id']);
        $stmt->execute();

        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error al obtener usuario: " . $e->getMessage());
        return null;
    }
}

// ===== SISTEMA DE AISLAMIENTO DE DATOS =====

// Función para verificar si una OC ya fue registrada
function checkExistingRetiro($ordenServicio) {
    $pdo = getConnection();

    try {
        $sql = "SELECT r.*, u.nombre_completo as tecnico_responsable
                FROM retiros_medidores r
                LEFT JOIN usuarios u ON r.usuario_id = u.id
                WHERE r.orden_servicio = :orden_servicio
                ORDER BY r.created_at DESC LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':orden_servicio', $ordenServicio);
        $stmt->execute();

        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error al verificar retiro existente: " . $e->getMessage());
        return null;
    }
}

// Función para verificar si el usuario puede acceder a un retiro específico
function canAccessRetiro($retiroId, $userId = null) {
    if (isAdmin()) {
        return true; // Admin puede acceder a todo
    }

    if (!$userId) {
        $userId = $_SESSION['user_id'];
    }

    $pdo = getConnection();

    try {
        $sql = "SELECT usuario_id FROM retiros_medidores WHERE id = :retiro_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':retiro_id', $retiroId);
        $stmt->execute();

        $result = $stmt->fetch();
        return $result && $result['usuario_id'] == $userId;
    } catch (PDOException $e) {
        error_log("Error al verificar acceso a retiro: " . $e->getMessage());
        return false;
    }
}

// Función para obtener retiros del usuario actual (o todos para admin)
function getUserRetiros($userId = null, $includeInactiveUsers = false) {
    $pdo = getConnection();

    try {
        if (isAdmin() || $includeInactiveUsers) {
            // Admin ve todos los registros
            $sql = "SELECT r.*, u.nombre_completo as tecnico_responsable,
                           o.cliente, o.direccion, o.num_serie_medidor
                    FROM retiros_medidores r
                    LEFT JOIN usuarios u ON r.usuario_id = u.id
                    INNER JOIN ordenes_servicio o ON r.orden_servicio_id = o.id";
        } else {
            // Técnico solo ve sus propios registros
            if (!$userId) {
                $userId = $_SESSION['user_id'];
            }
            $sql = "SELECT r.*, u.nombre_completo as tecnico_responsable,
                           o.cliente, o.direccion, o.num_serie_medidor
                    FROM retiros_medidores r
                    LEFT JOIN usuarios u ON r.usuario_id = u.id
                    INNER JOIN ordenes_servicio o ON r.orden_servicio_id = o.id
                    WHERE r.usuario_id = :user_id";
        }

        $sql .= " ORDER BY r.fecha_registro DESC";

        $stmt = $pdo->prepare($sql);

        if (!isAdmin() && !$includeInactiveUsers) {
            $stmt->bindParam(':user_id', $userId);
        }

        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error al obtener retiros del usuario: " . $e->getMessage());
        return [];
    }
}

// ===== SISTEMA DE AUDITORÍA =====

// Función para registrar acciones en auditoría
function logAudit($retiroId = null, $userId = null, $action, $details = '', $ordenServicio = null) {
    if (!$userId) {
        $userId = $_SESSION['user_id'] ?? null;
    }

    if (!$userId) {
        return false; // No log si no hay usuario
    }

    $pdo = getConnection();

    try {
        $sql = "INSERT INTO auditoria_retiros
                (retiro_id, usuario_id, accion, detalles, orden_servicio, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);

        // Obtener IP del cliente
        $ipAddress = getClientIP();

        // Obtener User Agent
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $stmt->execute([
            $retiroId,
            $userId,
            $action,
            $details,
            $ordenServicio,
            $ipAddress,
            $userAgent
        ]);

        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Error al registrar auditoría: " . $e->getMessage());
        return false;
    }
}

// Función para obtener IP del cliente
function getClientIP() {
    $ipSources = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_CF_CONNECTING_IP',
        'REMOTE_ADDR'
    ];

    foreach ($ipSources as $source) {
        if (!empty($_SERVER[$source])) {
            $ip = $_SERVER[$source];

            // Si hay múltiples IPs (proxy), tomar la primera
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }

            // Validar que sea una IP válida
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }

    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

// Función para obtener historial de auditoría
function getAuditHistory($filters = []) {
    $pdo = getConnection();

    try {
        $sql = "SELECT a.*, u.nombre_completo, u.username
                FROM auditoria_retiros a
                LEFT JOIN usuarios u ON a.usuario_id = u.id
                WHERE 1=1";

        $params = [];

        // Filtros opcionales
        if (isset($filters['usuario_id']) && !empty($filters['usuario_id'])) {
            $sql .= " AND a.usuario_id = ?";
            $params[] = $filters['usuario_id'];
        }

        if (isset($filters['accion']) && !empty($filters['accion'])) {
            $sql .= " AND a.accion = ?";
            $params[] = $filters['accion'];
        }

        if (isset($filters['orden_servicio']) && !empty($filters['orden_servicio'])) {
            $sql .= " AND a.orden_servicio LIKE ?";
            $params[] = "%" . $filters['orden_servicio'] . "%";
        }

        if (isset($filters['fecha_desde']) && !empty($filters['fecha_desde'])) {
            $sql .= " AND DATE(a.fecha_accion) >= ?";
            $params[] = $filters['fecha_desde'];
        }

        if (isset($filters['fecha_hasta']) && !empty($filters['fecha_hasta'])) {
            $sql .= " AND DATE(a.fecha_accion) <= ?";
            $params[] = $filters['fecha_hasta'];
        }

        $sql .= " ORDER BY a.fecha_accion DESC";

        // Límite opcional
        if (isset($filters['limit']) && is_numeric($filters['limit'])) {
            $sql .= " LIMIT ?";
            $params[] = $filters['limit'];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error al obtener historial de auditoría: " . $e->getMessage());
        return [];
    }
}

// ===== FUNCIONES DE ADMINISTRACIÓN =====

// Función para reasignar un retiro a otro técnico (solo admin)
function reassignRetiro($retiroId, $newUserId, $adminUserId, $reason = '') {
    if (!isAdmin()) {
        return false;
    }

    $pdo = getConnection();

    try {
        $pdo->beginTransaction();

        // Actualizar el retiro
        $sql = "UPDATE retiros_medidores
                SET usuario_id = ?, estado_registro = 'reasignado',
                    usuario_reasignado_por = ?, fecha_reasignacion = NOW()
                WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$newUserId, $adminUserId, $retiroId]);

        // Obtener información del retiro para auditoría
        $retiroInfo = getRetiroInfo($retiroId);
        $oldUserInfo = getUserInfo($retiroInfo['usuario_id']);
        $newUserInfo = getUserInfo($newUserId);

        // Registrar en auditoría
        logAudit($retiroId, $adminUserId, 'reasignacion_oc',
                "Reasignado de {$oldUserInfo['nombre_completo']} a {$newUserInfo['nombre_completo']}. Razón: $reason",
                $retiroInfo['orden_servicio']);

        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error al reasignar retiro: " . $e->getMessage());
        return false;
    }
}

// Función para "reabrir" una OC para nuevo registro (solo admin)
function reopenOC($retiroId, $adminUserId, $reason = '') {
    if (!isAdmin()) {
        return false;
    }

    $pdo = getConnection();

    try {
        $pdo->beginTransaction();

        // Obtener información antes de actualizar
        $retiroInfo = getRetiroInfo($retiroId);

        // Marcar como reabierto
        $sql = "UPDATE retiros_medidores
                SET estado_registro = 'reabierto', usuario_reasignado_por = ?,
                    fecha_reasignacion = NOW()
                WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$adminUserId, $retiroId]);

        // Registrar en auditoría
        logAudit($retiroId, $adminUserId, 'reapertura_oc',
                "OC reabierta para nuevo registro. Razón: $reason",
                $retiroInfo['orden_servicio']);

        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error al reabrir OC: " . $e->getMessage());
        return false;
    }
}

// Función auxiliar para obtener información de un retiro
function getRetiroInfo($retiroId) {
    $pdo = getConnection();

    try {
        $sql = "SELECT r.*, u.nombre_completo as tecnico_responsable
                FROM retiros_medidores r
                LEFT JOIN usuarios u ON r.usuario_id = u.id
                WHERE r.id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(1, $retiroId);
        $stmt->execute();

        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error al obtener información de retiro: " . $e->getMessage());
        return null;
    }
}

// Función auxiliar para obtener información de un usuario
function getUserInfo($userId) {
    $pdo = getConnection();

    try {
        $sql = "SELECT nombre_completo, username FROM usuarios WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(1, $userId);
        $stmt->execute();

        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error al obtener información de usuario: " . $e->getMessage());
        return null;
    }
}

// Función para registrar login/logout
function logUserAction($action, $details = '') {
    logAudit(null, $_SESSION['user_id'] ?? null, $action, $details);
}
?>

