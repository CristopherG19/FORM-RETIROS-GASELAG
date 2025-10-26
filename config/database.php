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

            // Guardar en sesión
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['rol'];
            $_SESSION['nombre_completo'] = $user['nombre_completo'];

            return true;
        }

        return false;
    } catch (PDOException $e) {
        error_log("Error en login: " . $e->getMessage());
        return false;
    }
}

// Función para hacer logout
function logout() {
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
?>

