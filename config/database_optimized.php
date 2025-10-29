<?php
/**
 * Configuración optimizada de base de datos
 * GASELAG - Sistema de Retiros de Medidores
 * Versión: 2.0 (Refactorizada)
 */

// ===== CONFIGURACIÓN DE BASE DE DATOS =====
define('DB_HOST', 'localhost');
define('DB_PORT', '3307');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'gaselag_retiros');
define('DB_CHARSET', 'utf8mb4');

// Configuración de PDO optimizada
define('PDO_OPTIONS', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_PERSISTENT => false, // Deshabilitado para mejor control
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
]);

// ===== GESTIÓN DE SESIONES =====
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['session_id'])) {
    $_SESSION['session_id'] = uniqid('session_', true);
}

// ===== CONEXIÓN A BASE DE DATOS =====
class DatabaseConnection {
    private static $instance = null;
    private $pdo = null;
    
    private function __construct() {
        $this->connect();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function connect() {
        try {
            $dsn = sprintf(
                "mysql:host=%s;port=%s;dbname=%s;charset=%s",
                DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
            );
            
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, PDO_OPTIONS);
        } catch (PDOException $e) {
            error_log("Error de conexión a BD: " . $e->getMessage());
            die("Error de conexión a la base de datos. Contacte al administrador.");
        }
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    public function __destruct() {
        $this->pdo = null;
    }
}

// Función de compatibilidad para mantener funcionalidad existente
function getConnection() {
    return DatabaseConnection::getInstance()->getConnection();
}

// ===== UTILIDADES GENERALES =====

/**
 * Obtiene la IP del cliente
 */
function getClientIP() {
    $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Sanitiza entrada de usuario
 */
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Valida formato de fecha
 */
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Genera nombre único para archivos
 */
function generateUniqueFilename($prefix, $extension) {
    $timestamp = date('Ymd_His');
    $random = substr(md5(uniqid()), 0, 8);
    return $prefix . '_' . $timestamp . '_' . $random . '.' . $extension;
}

// ===== SISTEMA DE AUTENTICACIÓN =====

/**
 * Verifica si el usuario está logueado
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

/**
 * Obtiene el rol del usuario
 */
function getUserRole() {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Verifica si el usuario es administrador
 */
function isAdmin() {
    return getUserRole() === 'admin';
}

/**
 * Verifica si el usuario es técnico
 */
function isUser() {
    return getUserRole() === 'user';
}

/**
 * Procesa login de usuario
 */
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
        
        // Registrar intento fallido
        logAudit(null, null, 'login', "Intento fallido para usuario: $username desde IP: " . getClientIP());
        return false;
        
    } catch (PDOException $e) {
        error_log("Error en login: " . $e->getMessage());
        return false;
    }
}

/**
 * Procesa logout de usuario
 */
function logout() {
    // Registrar logout en auditoría
    if (isset($_SESSION['user_id'])) {
        logAudit(null, $_SESSION['user_id'], 'logout', "Logout desde IP: " . getClientIP());
    }
    
    // Limpiar sesión
    $_SESSION = array();
    
    if (session_id() != "" || isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    session_destroy();
    
    // Reiniciar sesión
    session_start();
    $_SESSION['session_id'] = uniqid('session_', true);
}

/**
 * Verifica acceso según rol requerido
 */
function requireRole($requiredRoles) {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
    
    if (!in_array(getUserRole(), $requiredRoles)) {
        $redirectUrl = isAdmin() ? 'index.php' : 'pages/consultar_retiros.php';
        header("Location: $redirectUrl");
        exit;
    }
}

/**
 * Obtiene información del usuario actual
 */
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

// ===== SISTEMA DE AUDITORÍA =====

/**
 * Registra acciones en auditoría
 */
function logAudit($retiroId = null, $userId = null, $action, $details = '', $ordenServicio = null) {
    if (!$userId) {
        $userId = $_SESSION['user_id'] ?? null;
    }
    
    if (!$userId) {
        return false;
    }
    
    $pdo = getConnection();
    
    try {
        $sql = "INSERT INTO auditoria_retiros 
                (retiro_id, usuario_id, accion, detalles, orden_servicio, ip_address, user_agent)
                VALUES (:retiro_id, :usuario_id, :accion, :detalles, :orden_servicio, :ip_address, :user_agent)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':retiro_id', $retiroId);
        $stmt->bindParam(':usuario_id', $userId);
        $stmt->bindParam(':accion', $action);
        $stmt->bindParam(':detalles', $details);
        $stmt->bindParam(':orden_servicio', $ordenServicio);
        $stmt->bindParam(':ip_address', getClientIP());
        $stmt->bindParam(':user_agent', $_SERVER['HTTP_USER_AGENT'] ?? '');
        
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error en auditoría: " . $e->getMessage());
        return false;
    }
}

/**
 * Registra acción de usuario
 */
function logUserAction($action, $details = '') {
    return logAudit(null, $_SESSION['user_id'] ?? null, $action, $details);
}

// ===== SISTEMA DE AISLAMIENTO DE DATOS =====

/**
 * Verifica si una OC ya fue registrada
 */
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
        
        $retiro = $stmt->fetch();
        
        if ($retiro) {
            // Si está reabierto, permitir nuevo registro
            if ($retiro['estado_registro'] === 'reabierto') {
                return null;
            }
            
            // Si tiene usuario asignado, está bloqueada
            if ($retiro['usuario_id']) {
                return $retiro;
            }
            
            // Si no tiene usuario pero existe registro, está disponible
            return null;
        }
        
        return null;
    } catch (PDOException $e) {
        error_log("Error verificando retiro existente: " . $e->getMessage());
        return null;
    }
}

/**
 * Verifica si el usuario puede acceder a un retiro
 */
function canAccessRetiro($retiroId, $userId = null) {
    if (isAdmin()) {
        return true;
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
        
        $retiro = $stmt->fetch();
        return $retiro && $retiro['usuario_id'] == $userId;
    } catch (PDOException $e) {
        error_log("Error verificando acceso a retiro: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene retiros del usuario con filtros optimizados
 */
function getUserRetiros($userId = null, $includeInactiveUsers = false) {
    $pdo = getConnection();
    
    try {
        if (isAdmin() || $includeInactiveUsers) {
            $sql = "SELECT r.*, u.nombre_completo as tecnico_responsable,
                           o.cliente, o.direccion, o.num_serie_medidor
                    FROM retiros_medidores r
                    LEFT JOIN usuarios u ON r.usuario_id = u.id
                    INNER JOIN ordenes_servicio o ON r.orden_servicio_id = o.id
                    ORDER BY r.fecha_registro DESC";
            
            $stmt = $pdo->prepare($sql);
        } else {
            if (!$userId) {
                $userId = $_SESSION['user_id'];
            }
            
            $sql = "SELECT r.*, u.nombre_completo as tecnico_responsable,
                           o.cliente, o.direccion, o.num_serie_medidor
                    FROM retiros_medidores r
                    LEFT JOIN usuarios u ON r.usuario_id = u.id
                    INNER JOIN ordenes_servicio o ON r.orden_servicio_id = o.id
                    WHERE r.usuario_id = :user_id
                    ORDER BY r.fecha_registro DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error al obtener retiros del usuario: " . $e->getMessage());
        return [];
    }
}

// ===== GESTIÓN DE TIPOS DE IMPOSIBILIDAD =====

/**
 * Obtiene tipos de imposibilidad con filtro opcional
 */
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

/**
 * Obtiene un tipo de imposibilidad por ID
 */
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

/**
 * Crea nuevo tipo de imposibilidad (solo admin)
 */
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
                "Nuevo tipo: $codigo - $descripcion ($categoria)");
        
        return $newId;
    } catch (PDOException $e) {
        error_log("Error creando tipo de imposibilidad: " . $e->getMessage());
        return false;
    }
}

/**
 * Actualiza tipo de imposibilidad (solo admin)
 */
function updateTipoImposibilidad($id, $codigo, $descripcion, $categoria, $adminUserId) {
    if (!isAdmin()) {
        return false;
    }
    
    $pdo = getConnection();
    
    try {
        $sql = "UPDATE tipos_imposibilidad 
                SET codigo = ?, descripcion = ?, categoria = ?
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$codigo, $descripcion, $categoria, $id]);
        
        if ($result) {
            logAudit(null, $adminUserId, 'modificacion_tipo_imposibilidad',
                    "Tipo actualizado: $codigo - $descripcion ($categoria)");
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log("Error actualizando tipo de imposibilidad: " . $e->getMessage());
        return false;
    }
}

/**
 * Desactiva tipo de imposibilidad (solo admin)
 */
function deleteTipoImposibilidad($id, $adminUserId) {
    if (!isAdmin()) {
        return false;
    }
    
    $pdo = getConnection();
    
    try {
        $sql = "UPDATE tipos_imposibilidad SET activo = 'NO' WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$id]);
        
        if ($result) {
            logAudit(null, $adminUserId, 'eliminacion_tipo_imposibilidad',
                    "Tipo desactivado ID: $id");
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log("Error desactivando tipo de imposibilidad: " . $e->getMessage());
        return false;
    }
}

// ===== FUNCIONES DE REASIGNACIÓN =====

/**
 * Reasigna un retiro a otro técnico (solo admin)
 */
function reassignRetiro($retiroId, $newUserId, $adminUserId) {
    if (!isAdmin()) {
        return false;
    }
    
    $pdo = getConnection();
    
    try {
        $sql = "UPDATE retiros_medidores 
                SET usuario_id = :new_user_id, 
                    usuario_reasignado_por = :admin_id,
                    fecha_reasignacion = NOW(),
                    estado_registro = 'reasignado'
                WHERE id = :retiro_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':new_user_id', $newUserId);
        $stmt->bindParam(':admin_id', $adminUserId);
        $stmt->bindParam(':retiro_id', $retiroId);
        
        $result = $stmt->execute();
        
        if ($result) {
            logAudit($retiroId, $adminUserId, 'reasignacion_oc',
                    "Retiro reasignado a usuario ID: $newUserId");
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log("Error reasignando retiro: " . $e->getMessage());
        return false;
    }
}

/**
 * Reabre una OC para nuevo registro (solo admin)
 */
function reopenOC($retiroId, $adminUserId) {
    if (!isAdmin()) {
        return false;
    }
    
    $pdo = getConnection();
    
    try {
        $sql = "UPDATE retiros_medidores 
                SET usuario_id = NULL, 
                    estado_registro = 'reabierto',
                    fecha_reasignacion = NOW(),
                    usuario_reasignado_por = :admin_id
                WHERE id = :retiro_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':admin_id', $adminUserId);
        $stmt->bindParam(':retiro_id', $retiroId);
        
        $result = $stmt->execute();
        
        if ($result) {
            logAudit($retiroId, $adminUserId, 'reapertura_oc',
                    "OC reabierta para nuevo registro");
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log("Error reabriendo OC: " . $e->getMessage());
        return false;
    }
}

// ===== FUNCIONES DE ESTADÍSTICAS =====

/**
 * Obtiene estadísticas de imposibilidad
 */
function getEstadisticasImposibilidad($fechaInicio = null, $fechaFin = null) {
    $pdo = getConnection();
    
    try {
        $sql = "SELECT 
                    ti.categoria,
                    ti.descripcion,
                    COUNT(r.id) as cantidad,
                    COUNT(CASE WHEN r.foto_imposibilidad IS NOT NULL AND r.foto_imposibilidad != '' THEN 1 END) as con_foto
                FROM tipos_imposibilidad ti
                LEFT JOIN retiros_medidores r ON ti.id = r.tipo_imposibilidad_id
                WHERE ti.activo = 'SI'";
        
        $params = [];
        
        if ($fechaInicio && $fechaFin) {
            $sql .= " AND DATE(r.fecha_registro) BETWEEN ? AND ?";
            $params[] = $fechaInicio;
            $params[] = $fechaFin;
        }
        
        $sql .= " GROUP BY ti.id, ti.categoria, ti.descripcion
                  ORDER BY ti.categoria, cantidad DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error obteniendo estadísticas: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene retiros sin evidencia fotográfica
 */
function getRetirosImposibilidadSinFoto($pdo) {
    try {
        // Verificar si existe la columna usuario_id
        $columnCheck = $pdo->query("SHOW COLUMNS FROM retiros_medidores LIKE 'usuario_id'");
        $userColumnExists = $columnCheck->rowCount() > 0;
        
        $userId = null;
        if (isUser() && $userColumnExists) {
            $userId = $_SESSION['user_id'];
        }
        
        $sql = "SELECT
                    r.*,
                    o.cliente,
                    o.usuario_reclamante,
                    o.num_serie_medidor,
                    o.direccion,
                    o.programacion_dia_retiro,
                    ti.descripcion as tipo_imposibilidad_desc
                FROM retiros_medidores r
                INNER JOIN ordenes_servicio o ON r.orden_servicio_id = o.id
                LEFT JOIN tipos_imposibilidad ti ON r.tipo_imposibilidad_id = ti.id
                WHERE r.medidor_retirado = 'NO'
                AND (r.foto_imposibilidad IS NULL OR r.foto_imposibilidad = '')";
        
        // Aplicar filtro por usuario si es técnico
        if (isUser() && $userColumnExists && $userId) {
            $sql .= " AND r.usuario_id = " . intval($userId);
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error obteniendo retiros sin foto: " . $e->getMessage());
        return [];
    }
}

/**
 * Cuenta retiros sin evidencia fotográfica
 */
function countRetirosImposibilidadSinFoto($pdo, $userId = null) {
    try {
        // Verificar si existe la columna usuario_id
        $columnCheck = $pdo->query("SHOW COLUMNS FROM retiros_medidores LIKE 'usuario_id'");
        $userColumnExists = $columnCheck->rowCount() > 0;
        
        $sql = "SELECT COUNT(*) as total
                FROM retiros_medidores r
                WHERE r.medidor_retirado = 'NO'
                AND (r.foto_imposibilidad IS NULL OR r.foto_imposibilidad = '')";
        
        // Aplicar filtro por usuario si es técnico
        if (isUser() && $userColumnExists) {
            if (!$userId) {
                $userId = $_SESSION['user_id'];
            }
            $sql .= " AND r.usuario_id = " . intval($userId);
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        
        return $result['total'];
    } catch (Exception $e) {
        error_log("Error contando retiros sin foto: " . $e->getMessage());
        return 0;
    }
}

// ===== FUNCIONES DE VALIDACIÓN =====

/**
 * Valida formato de OC
 */
function validateOC($oc) {
    return preg_match('/^[A-Z0-9-]+$/', $oc) && strlen($oc) >= 5;
}

/**
 * Valida archivo de imagen
 */
function validateImageFile($file) {
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        return false;
    }
    
    if ($file['size'] > $maxSize) {
        return false;
    }
    
    return true;
}

/**
 * Valida datos de retiro
 */
function validateRetiroData($data) {
    $errors = [];
    
    if (empty($data['orden_servicio'])) {
        $errors[] = 'Orden de servicio es requerida';
    }
    
    if (empty($data['medidor_retirado'])) {
        $errors[] = 'Estado del medidor es requerido';
    }
    
    if ($data['medidor_retirado'] === 'NO' && empty($data['tipo_imposibilidad_id'])) {
        $errors[] = 'Tipo de imposibilidad es requerido cuando no se retira el medidor';
    }
    
    return $errors;
}

?>
