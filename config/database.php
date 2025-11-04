<?php
/**
 * Configuración de conexión a base de datos
 * GASELAG - Sistema de Retiros de Medidores
 */

// Incluir clases de seguridad
require_once __DIR__ . '/SecurityConfig.php';
require_once __DIR__ . '/PasswordPolicy.php';
require_once __DIR__ . '/RateLimiter.php';

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

// Cargar módulos de seguridad
require_once __DIR__ . '/SecurityConfig.php';
require_once __DIR__ . '/PasswordPolicy.php';
require_once __DIR__ . '/RateLimiter.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generar ID de sesión único si no existe
if (!isset($_SESSION['session_id'])) {
    $_SESSION['session_id'] = uniqid('session_', true);
}

// Inicializar last_activity para control de timeout
if (!isset($_SESSION['last_activity'])) {
    $_SESSION['last_activity'] = time();
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

// Función para contar retiros sin evidencia fotográfica (con filtro por usuario)
function countRetirosImposibilidadSinFoto($pdo, $userId = null) {
    try {
        // Verificar si existe la columna usuario_id
        $checkColumnQuery = "SHOW COLUMNS FROM retiros_medidores LIKE 'usuario_id'";
        $userColumnExists = $pdo->query($checkColumnQuery)->rowCount() > 0;

        $sql = "SELECT COUNT(*) as total
                FROM retiros_medidores r
                WHERE r.medidor_retirado = 'NO'
                AND (r.foto_imposibilidad IS NULL OR r.foto_imposibilidad = '')";

        $params = [];

        // SEGURIDAD: Usar prepared statements consistentemente
        // Aplicar filtro por usuario si es técnico y existe la columna
        if (isUser() && $userColumnExists) {
            if (!$userId) {
                $userId = $_SESSION['user_id'];
            }
            $sql .= " AND r.usuario_id = ?";
            $params[] = $userId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
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

// Función para hacer login con seguridad mejorada
function login($username, $password) {
    $pdo = getConnection();
    $ip = getClientIP();
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    try {
        // PASO 1: Verificar bloqueo de cuenta
        $blockStatus = isAccountBlocked($username, $ip);
        if ($blockStatus['blocked']) {
            recordLoginAttempt($username, $ip, false, $userAgent);
            return [
                'success' => false,
                'error' => $blockStatus['reason'],
                'blocked' => true,
                'remaining' => $blockStatus['remaining']
            ];
        }

        // PASO 2: Buscar usuario
        $sql = "SELECT id, username, password, nombre_completo, email, rol, estado, 
                       force_password_change, session_timeout
                FROM usuarios
                WHERE username = :username AND estado = 'activo'";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        $user = $stmt->fetch();

        // PASO 3: Verificar credenciales
        if ($user && password_verify($password, $user['password'])) {
            // Login exitoso
            
            // Actualizar datos del usuario
            $updateSql = "UPDATE usuarios 
                         SET ultimo_login = NOW(), 
                             last_activity = NOW(),
                             intentos_fallidos = 0,
                             bloqueado_hasta = NULL
                         WHERE id = :id";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->bindParam(':id', $user['id']);
            $updateStmt->execute();

            // Registrar intento exitoso
            recordLoginAttempt($username, $ip, true, $userAgent);

            // Registrar en auditoría
            logAudit(null, $user['id'], 'login', "Login exitoso desde IP: $ip");

            // SEGURIDAD: Regenerar ID de sesión para prevenir session fixation
            session_regenerate_id(true);

            // Guardar en sesión
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['rol'];
            $_SESSION['nombre_completo'] = $user['nombre_completo'];
            $_SESSION['session_timeout'] = $user['session_timeout'];
            $_SESSION['last_activity'] = time();
            $_SESSION['login_time'] = time();
            $_SESSION['device_fingerprint'] = getDeviceFingerprint();

            // Registrar dispositivo
            registerDevice($user['id'], getDeviceFingerprint(), $userAgent, $ip);

            return [
                'success' => true,
                'force_password_change' => (bool)$user['force_password_change'],
                'user_role' => $user['rol']
            ];
        }

        // Login fallido
        recordLoginAttempt($username, $ip, false, $userAgent);
        logAudit(null, null, 'login', "Intento fallido de login para usuario: $username desde IP: $ip");
        
        // Verificar intentos restantes
        $blockStatus = isAccountBlocked($username, $ip);
        
        return [
            'success' => false,
            'error' => 'Usuario o contraseña incorrectos',
            'attempts_left' => $blockStatus['attempts_left'] ?? null
        ];
        
    } catch (PDOException $e) {
        error_log("Error en login: " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Error del sistema. Intente nuevamente'
        ];
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
        // Determinar la ruta correcta al login según la ubicación actual
        $loginPath = (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? '../login.php' : 'login.php';
        header('Location: ' . $loginPath);
        exit;
    }

    if (!in_array(getUserRole(), $requiredRoles)) {
        // Redirigir según el rol del usuario y ubicación actual
        $isInPages = (strpos($_SERVER['PHP_SELF'], '/pages/') !== false);
        
        if (isAdmin()) {
            $indexPath = $isInPages ? '../index.php' : 'index.php';
            header('Location: ' . $indexPath);
        } else {
            $consultaPath = $isInPages ? 'consultar_retiros.php' : 'pages/consultar_retiros.php';
            header('Location: ' . $consultaPath);
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
        // Buscar el último registro de esta OC
        $sql = "SELECT r.*, u.nombre_completo as tecnico_responsable
                FROM retiros_medidores r
                LEFT JOIN usuarios u ON r.usuario_id = u.id
                WHERE r.orden_servicio = :orden_servicio
                ORDER BY r.created_at DESC LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':orden_servicio', $ordenServicio);
        $stmt->execute();

        $retiro = $stmt->fetch();

        // Si existe un registro, verificar su estado
        if ($retiro) {
            // Si el estado es 'reabierto', significa que fue limpiada la asignación
            // y cualquier técnico puede registrarla nuevamente
            if ($retiro['estado_registro'] === 'reabierto') {
                return null; // Tratar como si no existiera asignación
            }

            // Si hay usuario_id asignado y el estado no es 'reabierto',
            // entonces está bloqueada para otros técnicos
            if ($retiro['usuario_id']) {
                return $retiro;
            }

            // Si no hay usuario_id asignado pero existe el registro,
            // permitir registro (caso antiguo)
            return $retiro;
        }

        // No existe registro, OC disponible
        return null;
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
function logAudit($retiroId, $userId, $action, $details = '', $ordenServicio = null) {
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
        throw new Exception("No tienes permisos para reasignar");
    }

    $pdo = getConnection();

    try {
        // SIN TRANSACCIÓN PARA EVITAR BLOQUEOS
        // PRIMERO: Verificar que el nuevo usuario existe y es válido
        $userSql = "SELECT nombre_completo, estado, rol FROM usuarios WHERE id = ?";
        $userStmt = $pdo->prepare($userSql);
        $userStmt->execute([$newUserId]);
        $newUser = $userStmt->fetch();

        if (!$newUser) {
            throw new Exception("Usuario destino no encontrado");
        }
        if ($newUser['estado'] !== 'activo') {
            throw new Exception("El usuario destino no está activo");
        }
        if ($newUser['rol'] !== 'user') {
            throw new Exception("Solo se puede reasignar a técnicos");
        }

        // SEGUNDO: Obtener info actual del retiro
        $retiroSql = "SELECT usuario_id, orden_servicio FROM retiros_medidores WHERE id = ?";
        $retiroStmt = $pdo->prepare($retiroSql);
        $retiroStmt->execute([$retiroId]);
        $retiro = $retiroStmt->fetch();

        if (!$retiro) {
            throw new Exception("Retiro no encontrado");
        }

        // TERCERO: Verificar que no sea el mismo técnico
        if ($retiro['usuario_id'] == $newUserId) {
            throw new Exception("No se puede reasignar al mismo técnico actual");
        }

        // CUARTO: HACER LA REASIGNACIÓN DIRECTA
        $updateSql = "UPDATE retiros_medidores
                     SET usuario_id = ?, tecnico_responsable = ?, estado_registro = 'reasignado',
                         usuario_reasignado_por = ?, fecha_reasignacion = NOW()
                     WHERE id = ?";

        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([$newUserId, $newUser['nombre_completo'], $adminUserId, $retiroId]);

        // AUDITORÍA
        logAudit($retiroId, $adminUserId, 'reasignacion_oc',
                "Reasignado a técnico ID {$newUserId}. Razón: $reason",
                $retiro['orden_servicio']);

        return true;

    } catch (PDOException $e) {
        error_log("Error al reasignar retiro: " . $e->getMessage());
        throw new Exception("Error de base de datos al reasignar");
    } catch (Exception $e) {
        error_log("Validación fallida en reasignación: " . $e->getMessage());
        throw $e;
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

        // VALIDACIONES PREVIAS

        // 1. Verificar que el retiro existe
        $retiroInfo = getRetiroInfo($retiroId);
        if (!$retiroInfo) {
            throw new Exception("Retiro no encontrado");
        }

        // 2. Verificar que no esté ya reabierto recientemente
        if ($retiroInfo['estado_registro'] === 'reabierto') {
            // Verificar si fue reabierto en los últimos 5 minutos
            if ($retiroInfo['fecha_reasignacion'] &&
                strtotime($retiroInfo['fecha_reasignacion']) > (time() - 300)) {
                throw new Exception("Esta OC fue reabierta recientemente. Espere unos minutos antes de reabrir nuevamente.");
            }
        }

        // 3. No permitir reabrir OCs que ya fueron retiradas exitosamente
        if ($retiroInfo['medidor_retirado'] === 'SI') {
            throw new Exception("No se puede reabrir una OC que ya fue retirada exitosamente");
        }

        // Marcar como reabierto y LIMPIAR asignación de usuario
        // Esto permite que cualquier técnico pueda registrar esta OC
        $sql = "UPDATE retiros_medidores
                SET estado_registro = 'reabierto', usuario_reasignado_por = ?,
                    fecha_reasignacion = NOW(), usuario_id = NULL, tecnico_responsable = NULL
                WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$adminUserId, $retiroId]);

        // Registrar en auditoría
        logAudit($retiroId, $adminUserId, 'reapertura_oc',
                "OC reabierta para nuevo registro (usuario_id limpiado). Razón: $reason",
                $retiroInfo['orden_servicio']);

        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error al reabrir OC: " . $e->getMessage());
        return false;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Validación fallida en reapertura: " . $e->getMessage());
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
        $sql = "SELECT nombre_completo, username, estado, rol FROM usuarios WHERE id = ?";
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

// ===== GESTIÓN DE TIPOS DE IMPOSIBILIDAD =====

// Función para obtener todos los tipos de imposibilidad activos
function getTiposImposibilidad($categoria = null) {
    $pdo = getConnection();

    try {
        $sql = "SELECT id, codigo, descripcion, categoria
                FROM tipos_imposibilidad
                WHERE activo = 'SI'";

        $params = [];

        if ($categoria) {
            $sql .= " AND categoria = ?";
            $params[] = $categoria;
        }

        $sql .= " ORDER BY categoria, descripcion";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error al obtener tipos de imposibilidad: " . $e->getMessage());
        return [];
    }
}

// Función para obtener un tipo de imposibilidad por ID
function getTipoImposibilidad($id) {
    $pdo = getConnection();

    try {
        $sql = "SELECT * FROM tipos_imposibilidad WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(1, $id);
        $stmt->execute();

        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error al obtener tipo de imposibilidad: " . $e->getMessage());
        return null;
    }
}

// Función para crear nuevo tipo de imposibilidad (solo admin)
function createTipoImposibilidad($codigo, $descripcion, $categoria, $adminUserId) {
    if (!isAdmin()) {
        return false;
    }

    $pdo = getConnection();

    try {
        $sql = "INSERT INTO tipos_imposibilidad (codigo, descripcion, categoria, activo)
                VALUES (?, ?, ?, 'SI')";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$codigo, $descripcion, $categoria]);

        $newId = $pdo->lastInsertId();

        // Registrar en auditoría
        logAudit(null, $adminUserId, 'creacion_tipo_imposibilidad',
                "Nuevo tipo creado: $codigo - $descripcion (Categoría: $categoria)",
                null);

        return $newId;
    } catch (PDOException $e) {
        error_log("Error al crear tipo de imposibilidad: " . $e->getMessage());
        return false;
    }
}

// Función para actualizar tipo de imposibilidad (solo admin)
function updateTipoImposibilidad($id, $codigo, $descripcion, $categoria, $activo, $adminUserId) {
    if (!isAdmin()) {
        return false;
    }

    $pdo = getConnection();

    try {
        $sql = "UPDATE tipos_imposibilidad
                SET codigo = ?, descripcion = ?, categoria = ?, activo = ?, updated_at = NOW()
                WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$codigo, $descripcion, $categoria, $activo, $id]);

        // Obtener información anterior para auditoría
        $oldInfo = getTipoImposibilidad($id);

        // Registrar en auditoría
        logAudit(null, $adminUserId, 'modificacion_tipo_imposibilidad',
                "Tipo actualizado: {$oldInfo['codigo']} → $codigo, {$oldInfo['descripcion']} → $descripcion",
                null);

        return true;
    } catch (PDOException $e) {
        error_log("Error al actualizar tipo de imposibilidad: " . $e->getMessage());
        return false;
    }
}

// Función para eliminar tipo de imposibilidad (solo admin)
function deleteTipoImposibilidad($id, $adminUserId) {
    if (!isAdmin()) {
        return false;
    }

    $pdo = getConnection();

    try {
        $pdo->beginTransaction();

        // Obtener información antes de eliminar
        $tipoInfo = getTipoImposibilidad($id);

        // Marcar como inactivo en lugar de eliminar
        $sql = "UPDATE tipos_imposibilidad SET activo = 'NO', updated_at = NOW() WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);

        // Registrar en auditoría
        logAudit(null, $adminUserId, 'eliminacion_tipo_imposibilidad',
                "Tipo desactivado: {$tipoInfo['codigo']} - {$tipoInfo['descripcion']}",
                null);

        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error al eliminar tipo de imposibilidad: " . $e->getMessage());
        return false;
    }
}

// Función para obtener estadísticas por tipo de imposibilidad
function getEstadisticasImposibilidad($userId = null) {
    $pdo = getConnection();

    try {
        // Verificar si existe la columna usuario_id para filtrado
        $userColumnExists = false;
        try {
            $checkUserColumnQuery = "SHOW COLUMNS FROM retiros_medidores LIKE 'usuario_id'";
            $userColumnExists = $pdo->query($checkUserColumnQuery)->rowCount() > 0;
        } catch (Exception $e) {
            $userColumnExists = false;
        }

        $sql = "SELECT
                    COALESCE(ti.descripcion, 'Sin tipo especificado') as tipo_imposibilidad,
                    COALESCE(ti.categoria, 'sin_categoria') as categoria,
                    COUNT(*) as cantidad,
                    COUNT(CASE WHEN r.medidor_retirado = 'NO' THEN 1 END) as no_retirados,
                    COUNT(CASE WHEN r.medidor_retirado = 'SI' THEN 1 END) as retirados
                FROM retiros_medidores r
                LEFT JOIN tipos_imposibilidad ti ON r.tipo_imposibilidad_id = ti.id
                WHERE r.medidor_retirado = 'NO'";

        $params = [];

        // Aplicar filtro por usuario si es técnico y existe la columna
        if (isUser() && $userColumnExists) {
            $sql .= " AND r.usuario_id = ?";
            $params[] = $userId ?: $_SESSION['user_id'];
        }

        $sql .= " GROUP BY ti.id, ti.descripcion, ti.categoria
                  ORDER BY cantidad DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error al obtener estadísticas de imposibilidad: " . $e->getMessage());
        return [];
    }
}

// Función para obtener estadísticas analizando texto de observaciones (para compatibilidad)
function getEstadisticasImposibilidadFromText($userId = null) {
    $pdo = getConnection();

    try {
        // Verificar si existe la columna usuario_id para filtrado
        $userColumnExists = false;
        try {
            $checkUserColumnQuery = "SHOW COLUMNS FROM retiros_medidores LIKE 'usuario_id'";
            $userColumnExists = $pdo->query($checkUserColumnQuery)->rowCount() > 0;
        } catch (Exception $e) {
            $userColumnExists = false;
        }

        $sql = "SELECT
                    CASE
                        WHEN LOWER(r.observacion) LIKE '%niple%' THEN 'Se encontró conexión con niple'
                        WHEN LOWER(r.observacion) LIKE '%opuso%' OR LOWER(r.observacion) LIKE '%oposición%' THEN 'Usuario se opuso al retiro'
                        WHEN LOWER(r.observacion) LIKE '%interior%' THEN 'Servicio en interior de la propiedad'
                        WHEN LOWER(r.observacion) LIKE '%peligro%' OR LOWER(r.observacion) LIKE '%riesgo%' THEN 'Zona peligrosa'
                        WHEN LOWER(r.observacion) LIKE '%no coincide%' THEN 'Medidor no coincide con la orden'
                        WHEN LOWER(r.observacion) LIKE '%contómetro%' THEN 'Sin contómetro'
                        WHEN LOWER(r.observacion) LIKE '%obra%' OR LOWER(r.observacion) LIKE '%construcción%' THEN 'Obra en progreso'
                        WHEN LOWER(r.observacion) LIKE '%ausente%' OR LOWER(r.observacion) LIKE '%nadie%' THEN 'Cliente ausente'
                        WHEN LOWER(r.observacion) LIKE '%dañado%' OR LOWER(r.observacion) LIKE '%averiado%' THEN 'Medidor dañado'
                        WHEN LOWER(r.observacion) LIKE '%no localizado%' OR LOWER(r.observacion) LIKE '%no encontrado%' THEN 'Medidor no localizado'
                        ELSE 'Otros motivos'
                    END as tipo_imposibilidad,
                    COUNT(*) as cantidad
                FROM retiros_medidores r
                WHERE r.medidor_retirado = 'NO'
                AND r.observacion IS NOT NULL
                AND r.observacion != ''";

        $params = [];

        // Aplicar filtro por usuario si es técnico
        if (isUser() && $userColumnExists) {
            $sql .= " AND r.usuario_id = ?";
            $params[] = $userId ?: $_SESSION['user_id'];
        }

        $sql .= " GROUP BY tipo_imposibilidad
                  ORDER BY cantidad DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error al obtener estadísticas de imposibilidad desde texto: " . $e->getMessage());
        return [];
    }
}

// ===== SISTEMA DE CONTROL DE EVIDENCIA FOTOGRÁFICA =====

// Función para verificar si un tipo de imposibilidad requiere evidencia obligatoria
function requiereEvidenciaFotografica($tipoImposibilidadId) {
    if (!$tipoImposibilidadId) {
        return false;
    }

    $pdo = getConnection();

    try {
        $sql = "SELECT categoria FROM tipos_imposibilidad WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(1, $tipoImposibilidadId);
        $stmt->execute();

        $tipo = $stmt->fetch();
        return $tipo && in_array($tipo['categoria'], ['medidor', 'seguridad']);
    } catch (PDOException $e) {
        error_log("Error al verificar evidencia requerida: " . $e->getMessage());
        return false;
    }
}

// Función para marcar evidencia como obligatoria (al registrar)
function marcarEvidenciaObligatoria($retiroId, $tipoImposibilidadId) {
    $pdo = getConnection();

    try {
        $requiereEvidencia = requiereEvidenciaFotografica($tipoImposibilidadId) ? 'SI' : 'NO';

        // Si requiere evidencia, establecer fecha límite (6 horas desde ahora)
        $fechaLimite = null;
        if ($requiereEvidencia === 'SI') {
            $fechaLimite = date('Y-m-d H:i:s', strtotime('+6 hours'));
        }

        $sql = "UPDATE retiros_medidores
                SET evidencia_obligatoria = ?, fecha_limite_evidencia = ?, evidencia_completa = 'NO'
                WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$requiereEvidencia, $fechaLimite, $retiroId]);

        // Registrar en auditoría
        logAudit($retiroId, $_SESSION['user_id'], 'evidencia_requerida',
                "Evidencia marcada como $requiereEvidencia - Límite: " . ($fechaLimite ?: 'No aplica'),
                null);

        return true;
    } catch (PDOException $e) {
        error_log("Error al marcar evidencia obligatoria: " . $e->getMessage());
        return false;
    }
}

// Función para verificar si aún está dentro del tiempo límite para adjuntar evidencia
function puedeAdjuntarEvidencia($retiroId) {
    $pdo = getConnection();

    try {
        $sql = "SELECT evidencia_obligatoria, fecha_limite_evidencia, evidencia_completa
                FROM retiros_medidores
                WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(1, $retiroId);
        $stmt->execute();

        $evidencia = $stmt->fetch();

        if (!$evidencia || $evidencia['evidencia_obligatoria'] === 'NO') {
            return true; // No requiere evidencia
        }

        if ($evidencia['evidencia_completa'] === 'SI') {
            return false; // Ya completada
        }

        if (!$evidencia['fecha_limite_evidencia']) {
            return true; // Sin límite de tiempo
        }

        $fechaLimite = strtotime($evidencia['fecha_limite_evidencia']);
        $ahora = time();

        return $ahora <= $fechaLimite;
    } catch (PDOException $e) {
        error_log("Error al verificar tiempo para evidencia: " . $e->getMessage());
        return false;
    }
}

// Función para calcular tiempo restante para adjuntar evidencia
function getTiempoRestanteEvidencia($retiroId) {
    $pdo = getConnection();

    try {
        $sql = "SELECT fecha_limite_evidencia, evidencia_completa
                FROM retiros_medidores
                WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(1, $retiroId);
        $stmt->execute();

        $evidencia = $stmt->fetch();

        if (!$evidencia || !$evidencia['fecha_limite_evidencia']) {
            return null;
        }

        if ($evidencia['evidencia_completa'] === 'SI') {
            return 'completada';
        }

        $fechaLimite = strtotime($evidencia['fecha_limite_evidencia']);
        $ahora = time();
        $tiempoRestante = $fechaLimite - $ahora;

        if ($tiempoRestante <= 0) {
            return 'vencida';
        }

        $horas = floor($tiempoRestante / 3600);
        $minutos = floor(($tiempoRestante % 3600) / 60);

        return "{$horas}h {$minutos}m";
    } catch (PDOException $e) {
        error_log("Error al calcular tiempo restante: " . $e->getMessage());
        return null;
    }
}

// Función para adjuntar evidencia fotográfica a un registro existente
function adjuntarEvidenciaFotografica($retiroId, $fotoPath, $userId) {
    if (!puedeAdjuntarEvidencia($retiroId)) {
        return ['success' => false, 'message' => 'Tiempo límite para adjuntar evidencia ha expirado'];
    }

    $pdo = getConnection();

    try {
        $pdo->beginTransaction();

        // Actualizar el registro con la evidencia
        $sql = "UPDATE retiros_medidores
                SET foto_imposibilidad = ?, tiene_foto = 'SI', evidencia_completa = 'SI'
                WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$fotoPath, $retiroId]);

        // Registrar en auditoría
        logAudit($retiroId, $userId, 'evidencia_adjuntada',
                "Evidencia fotográfica adjuntada: $fotoPath",
                null);

        $pdo->commit();
        return ['success' => true, 'message' => 'Evidencia adjuntada correctamente'];
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error al adjuntar evidencia: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al adjuntar evidencia'];
    }
}

// Función para obtener registros que requieren evidencia (para admin)
function getRegistrosPendientesEvidencia($userId = null) {
    $pdo = getConnection();

    try {
        $sql = "SELECT r.*, o.cliente, o.direccion, u.nombre_completo as tecnico_responsable,
                       u.username as username_tecnico,
                       ti.descripcion as tipo_imposibilidad,
                       DATEDIFF(NOW(), r.fecha_registro) as dias_transcurridos
                FROM retiros_medidores r
                INNER JOIN ordenes_servicio o ON r.orden_servicio_id = o.id
                LEFT JOIN usuarios u ON r.usuario_id = u.id
                LEFT JOIN tipos_imposibilidad ti ON r.tipo_imposibilidad_id = ti.id
                WHERE r.evidencia_obligatoria = 'SI'
                AND r.evidencia_completa = 'NO'
                AND r.fecha_limite_evidencia < NOW()";

        $params = [];

        // Si es técnico, solo ver sus propios registros
        if (isUser()) {
            $sql .= " AND r.usuario_id = ?";
            $params[] = $_SESSION['user_id'];
        }

        $sql .= " ORDER BY r.fecha_limite_evidencia ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error al obtener registros pendientes de evidencia: " . $e->getMessage());
        return [];
    }
}

// Función para aplicar sanción por no completar evidencia (solo admin)
function aplicarSancionEvidencia($retiroId, $motivo, $adminUserId) {
    if (!isAdmin()) {
        return false;
    }

    $pdo = getConnection();

    try {
        $pdo->beginTransaction();

        // Aplicar sanción
        $sql = "UPDATE retiros_medidores
                SET sancion_aplicada = 'SI', motivo_sancion = ?, fecha_sancion = NOW(),
                    admin_sancion_id = ?
                WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$motivo, $adminUserId, $retiroId]);

        // Obtener información del registro para auditoría
        $retiroInfo = getRetiroInfo($retiroId);

        // Registrar en auditoría
        logAudit($retiroId, $adminUserId, 'sancion_aplicada',
                "Sanción aplicada: $motivo - Técnico: {$retiroInfo['tecnico_responsable']}",
                $retiroInfo['orden_servicio']);

        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error al aplicar sanción: " . $e->getMessage());
        return false;
    }
}

// Función para obtener registros con sanciones aplicadas
function getRegistrosConSanciones($userId = null, $fechaDesde = null, $fechaHasta = null) {
    $pdo = getConnection();

    try {
        $sql = "SELECT r.*, o.cliente, o.direccion,
                       u.nombre_completo as tecnico_responsable,
                       u.username as username_tecnico,
                       admin_u.nombre_completo as admin_sancion,
                       r.motivo_sancion,
                       r.fecha_sancion
                FROM retiros_medidores r
                INNER JOIN ordenes_servicio o ON r.orden_servicio_id = o.id
                LEFT JOIN usuarios u ON r.usuario_id = u.id
                LEFT JOIN usuarios admin_u ON r.admin_sancion_id = admin_u.id
                WHERE r.sancion_aplicada = 'SI'";

        $params = [];

        if ($userId) {
            $sql .= " AND r.usuario_id = ?";
            $params[] = $userId;
        }

        if ($fechaDesde) {
            $sql .= " AND DATE(r.fecha_sancion) >= ?";
            $params[] = $fechaDesde;
        }

        if ($fechaHasta) {
            $sql .= " AND DATE(r.fecha_sancion) <= ?";
            $params[] = $fechaHasta;
        }

        $sql .= " ORDER BY r.fecha_sancion DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error al obtener registros con sanciones: " . $e->getMessage());
        return [];
    }
}

// Función para obtener estadísticas de cumplimiento de evidencia
function getEstadisticasCumplimientoEvidencia($userId = null) {
    $pdo = getConnection();

    try {
        $sql = "SELECT
                    COUNT(*) as total_no_retirados,
                    COUNT(CASE WHEN evidencia_obligatoria = 'SI' THEN 1 END) as requiere_evidencia,
                    COUNT(CASE WHEN evidencia_completa = 'SI' THEN 1 END) as evidencia_completa,
                    COUNT(CASE WHEN evidencia_obligatoria = 'SI' AND evidencia_completa = 'NO' AND fecha_limite_evidencia < NOW() THEN 1 END) as evidencia_vencida,
                    COUNT(CASE WHEN sancion_aplicada = 'SI' THEN 1 END) as sanciones_aplicadas,
                    ROUND(
                        (COUNT(CASE WHEN evidencia_completa = 'SI' THEN 1 END) /
                         NULLIF(COUNT(CASE WHEN evidencia_obligatoria = 'SI' THEN 1 END), 0)) * 100, 2
                    ) as porcentaje_cumplimiento
                FROM retiros_medidores
                WHERE medidor_retirado = 'NO'";

        $params = [];

        if (isUser()) {
            $sql .= " AND usuario_id = ?";
            $params[] = $_SESSION['user_id'];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error al obtener estadísticas de cumplimiento: " . $e->getMessage());
        return null;
    }
}

// ===== FUNCIONES DE SEGURIDAD ADICIONALES =====

/**
 * Generar fingerprint único del dispositivo
 * @return string
 */
function getDeviceFingerprint() {
    $components = [
        $_SERVER['HTTP_USER_AGENT'] ?? '',
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
        $_SERVER['HTTP_ACCEPT_ENCODING'] ?? ''
    ];
    
    return hash('sha256', implode('|', $components));
}

/**
 * Registrar dispositivo autorizado
 * @param int $userId
 * @param string $fingerprint
 * @param string $userAgent
 * @param string $ip
 */
function registerDevice($userId, $fingerprint, $userAgent, $ip) {
    try {
        $pdo = getConnection();
        
        // Verificar si ya existe
        $stmt = $pdo->prepare("
            SELECT id FROM dispositivos_autorizados 
            WHERE usuario_id = ? AND device_fingerprint = ?
        ");
        $stmt->execute([$userId, $fingerprint]);
        
        if ($stmt->fetch()) {
            // Actualizar último uso
            $pdo->prepare("
                UPDATE dispositivos_autorizados 
                SET ultimo_uso = NOW(), ip_address = ? 
                WHERE usuario_id = ? AND device_fingerprint = ?
            ")->execute([$ip, $userId, $fingerprint]);
        } else {
            // Detectar tipo de dispositivo
            $deviceType = detectDeviceType($userAgent);
            $deviceName = getDeviceName($userAgent);
            
            // Registrar nuevo dispositivo
            $pdo->prepare("
                INSERT INTO dispositivos_autorizados 
                (usuario_id, device_fingerprint, device_name, device_type, user_agent, ip_address) 
                VALUES (?, ?, ?, ?, ?, ?)
            ")->execute([$userId, $fingerprint, $deviceName, $deviceType, $userAgent, $ip]);
        }
        
    } catch (Exception $e) {
        error_log("Error registrando dispositivo: " . $e->getMessage());
    }
}

/**
 * Detectar tipo de dispositivo
 */
function detectDeviceType($userAgent) {
    if (preg_match('/mobile|android|iphone|ipod|blackberry|opera mini|iemobile/i', $userAgent)) {
        return 'mobile';
    }
    if (preg_match('/tablet|ipad|playbook|silk/i', $userAgent)) {
        return 'tablet';
    }
    return 'desktop';
}

/**
 * Obtener nombre del dispositivo
 */
function getDeviceName($userAgent) {
    if (preg_match('/iPhone/', $userAgent)) return 'iPhone';
    if (preg_match('/iPad/', $userAgent)) return 'iPad';
    if (preg_match('/Android/', $userAgent)) {
        if (preg_match('/Mobile/', $userAgent)) return 'Android Phone';
        return 'Android Tablet';
    }
    if (preg_match('/Windows/', $userAgent)) return 'Windows PC';
    if (preg_match('/Macintosh/', $userAgent)) return 'Mac';
    if (preg_match('/Linux/', $userAgent)) return 'Linux PC';
    
    return 'Dispositivo Desconocido';
}

/**
 * Verificar timeout de sesión
 * @return bool true si la sesión expiró
 */
function checkSessionTimeout() {
    if (!isLoggedIn()) {
        return false;
    }
    
    $lastActivity = $_SESSION['last_activity'] ?? 0;
    $timeout = $_SESSION['session_timeout'] ?? SESSION_TIMEOUT_USER;
    $now = time();
    
    if (($now - $lastActivity) > $timeout) {
        // Sesión expirada
        logAudit(null, $_SESSION['user_id'], 'logout', "Sesión expirada por inactividad");
        logout();
        return true;
    }
    
    return false;
}

/**
 * Actualizar última actividad
 */
function updateLastActivity() {
    if (isLoggedIn()) {
        $_SESSION['last_activity'] = time();
        
        // Actualizar también en base de datos cada 5 minutos
        $lastDbUpdate = $_SESSION['last_db_activity_update'] ?? 0;
        if ((time() - $lastDbUpdate) > 300) { // 5 minutos
            try {
                $pdo = getConnection();
                $pdo->prepare("UPDATE usuarios SET last_activity = NOW() WHERE id = ?")
                    ->execute([$_SESSION['user_id']]);
                $_SESSION['last_db_activity_update'] = time();
            } catch (Exception $e) {
                error_log("Error actualizando last_activity: " . $e->getMessage());
            }
        }
    }
}

/**
 * Obtener tiempo restante de sesión en segundos
 * @return int
 */
function getSessionTimeRemaining() {
    if (!isLoggedIn()) {
        return 0;
    }
    
    $lastActivity = $_SESSION['last_activity'] ?? 0;
    $timeout = $_SESSION['session_timeout'] ?? SESSION_TIMEOUT_USER;
    $elapsed = time() - $lastActivity;
    
    return max(0, $timeout - $elapsed);
}

/**
 * Extender sesión
 */
function extendSession() {
    if (isLoggedIn()) {
        $_SESSION['last_activity'] = time();
        updateLastActivity();
        return true;
    }
    return false;
}

/**
 * Generar token CSRF
 * @return string
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    
    // Regenerar si es muy antiguo
    if ((time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_EXPIRY) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Validar token CSRF
 * @param string $token
 * @return bool
 */
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}
?>

