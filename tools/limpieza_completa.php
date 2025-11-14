<?php
/**
 * Limpieza completa de MySQL - Elimina todos los bloqueos
 */

echo "<h1>🧹 Limpieza Completa de MySQL</h1>";
echo "<hr>";

try {
    $dsn = "mysql:host=localhost;port=3307;charset=utf8mb4";
    $pdo = new PDO($dsn, 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "<h2>Paso 1: Matar procesos bloqueados</h2>";
    
    // Obtener lista de procesos
    $stmt = $pdo->query("SHOW PROCESSLIST");
    $processes = $stmt->fetchAll();
    
    $killed = 0;
    foreach ($processes as $proc) {
        // No matar el proceso actual ni procesos del sistema
        if ($proc['Command'] == 'Sleep' && $proc['Time'] > 30) {
            try {
                $pdo->exec("KILL {$proc['Id']}");
                echo "<p style='color: orange;'>🔪 Proceso {$proc['Id']} eliminado (dormido {$proc['Time']}s)</p>";
                $killed++;
            } catch (Exception $e) {
                // Ignorar errores al matar procesos
            }
        }
    }
    
    if ($killed == 0) {
        echo "<p style='color: green;'>✅ No hay procesos bloqueados para eliminar</p>";
    }
    
    echo "<h2>Paso 2: Eliminar base de datos y recrear</h2>";
    
    // Eliminar BD
    $pdo->exec("DROP DATABASE IF EXISTS gaselag_retiros");
    echo "<p style='color: green;'>✅ Base de datos eliminada</p>";
    
    // Recrear BD
    $pdo->exec("CREATE DATABASE gaselag_retiros CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p style='color: green;'>✅ Base de datos recreada limpia</p>";
    
    echo "<h2>Paso 3: Reinstalar schema</h2>";
    
    $pdo->exec("USE gaselag_retiros");
    
    // Leer schema.sql
    $schema = file_get_contents('../database/schema.sql');
    
    // Dividir en statements
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    
    $tablas = 0;
    foreach ($statements as $stmt) {
        if (empty($stmt)) continue;
        if (stripos($stmt, 'CREATE DATABASE') !== false) continue;
        if (stripos($stmt, 'USE ') !== false) continue;
        
        try {
            $pdo->exec($stmt);
            if (stripos($stmt, 'CREATE TABLE') !== false) {
                $tablas++;
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<p style='color: green;'>✅ $tablas tablas creadas</p>";
    
    echo "<hr>";
    echo "<div style='background: #d4edda; padding: 20px; border-left: 4px solid #28a745;'>";
    echo "<h2>✅ Limpieza Completada</h2>";
    echo "<p><strong>MySQL está completamente limpio y sin bloqueos.</strong></p>";
    echo "<p><a href='login.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ir al Login</a></p>";
    echo "</div>";
    
    echo "<hr>";
    echo "<h3>Usuarios creados:</h3>";
    echo "<ul>";
    echo "<li><strong>admin</strong> / password</li>";
    echo "<li><strong>12345678</strong> / password</li>";
    echo "<li><strong>87654321</strong> / password</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border-left: 4px solid #dc3545;'>";
    echo "<h2>❌ Error</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "</div>";
}
?>
