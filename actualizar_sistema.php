<?php
/**
 * Script de actualización para agregar el sistema de autenticación
 * GASELAG - Sistema de Retiros de Medidores
 */

require_once 'config/database.php';

try {
    $pdo = getConnection();

    echo "<h1>Actualizando Sistema de Autenticación</h1>\n";

    // Verificar si la tabla usuarios ya existe
    $sql = "SHOW TABLES LIKE 'usuarios'";
    $stmt = $pdo->query($sql);
    $tableExists = $stmt->fetch();

    if ($tableExists) {
        echo "<p style='color: orange;'>✓ La tabla 'usuarios' ya existe</p>\n";
    } else {
        echo "<p>Creando tabla 'usuarios'...</p>\n";

        // Crear tabla usuarios
        $createTableSql = "
            CREATE TABLE IF NOT EXISTS usuarios (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                nombre_completo VARCHAR(100) NOT NULL,
                email VARCHAR(100),
                rol ENUM('admin', 'user') NOT NULL DEFAULT 'user',
                estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
                ultimo_login TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_username (username),
                INDEX idx_rol (rol),
                INDEX idx_estado (estado)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            INSERT INTO usuarios (username, password, nombre_completo, email, rol) VALUES
            ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador del Sistema', 'admin@gaselag.com', 'admin'),
            ('tecnico1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Técnico de Retiro 1', 'tecnico1@gaselag.com', 'user'),
            ('tecnico2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Técnico de Retiro 2', 'tecnico2@gaselag.com', 'user');
        ";

        $pdo->exec($createTableSql);
        echo "<p style='color: green;'>✓ Tabla 'usuarios' creada exitosamente</p>\n";
        echo "<p style='color: green;'>✓ Usuarios por defecto insertados</p>\n";
    }

    // Verificar si las funciones de autenticación están en config/database.php
    $configContent = file_get_contents('config/database.php');
    if (strpos($configContent, 'function isLoggedIn()') === false) {
        echo "<p style='color: orange;'>⚠ Las funciones de autenticación no están en config/database.php</p>\n";
        echo "<p>Por favor, actualice manualmente el archivo config/database.php con las funciones de autenticación.</p>\n";
    } else {
        echo "<p style='color: green;'>✓ Funciones de autenticación detectadas en config/database.php</p>\n";
    }

    echo "<hr>\n";
    echo "<h2>Usuarios por defecto creados:</h2>\n";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
    echo "<strong>Administrador:</strong><br>\n";
    echo "Usuario: <code>admin</code><br>\n";
    echo "Contraseña: <code>password</code><br><br>\n";
    echo "<strong>Técnicos:</strong><br>\n";
    echo "Usuario: <code>tecnico1</code> / Contraseña: <code>password</code><br>\n";
    echo "Usuario: <code>tecnico2</code> / Contraseña: <code>password</code><br>\n";
    echo "</div>\n";

    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
    echo "<h3 style='color: #856404; margin-top: 0;'>⚠ Importante - Acción requerida:</h3>\n";
    echo "<p style='margin-bottom: 0;'>Ahora debe proteger todas las páginas del sistema agregando la verificación de autenticación al inicio de cada archivo PHP en la carpeta <code>pages/</code>.</p>\n";
    echo "</div>\n";

    echo "<p><a href='login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ir al Login</a></p>\n";

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>\n";
}
?>
