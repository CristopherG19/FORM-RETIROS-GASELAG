<?php
/**
 * Script para limpiar bloqueos de base de datos
 * Ejecutar cuando el sistema se quede lento
 */

try {
    $dsn = "mysql:host=localhost;port=3307;dbname=gaselag_retiros;charset=utf8mb4";
    $pdo = new PDO($dsn, 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "<h2>🔧 Limpieza de Bloqueos - GASELAG</h2>";
    
    // 1. Ver procesos activos
    echo "<h3>Procesos Activos:</h3>";
    $stmt = $pdo->query("SHOW PROCESSLIST");
    $processes = $stmt->fetchAll();
    echo "<pre>";
    print_r($processes);
    echo "</pre>";
    
    // 2. Ver transacciones bloqueadas
    echo "<h3>Transacciones Bloqueadas:</h3>";
    try {
        $stmt = $pdo->query("SELECT * FROM information_schema.INNODB_TRX");
        $transactions = $stmt->fetchAll();
        if (empty($transactions)) {
            echo "<p style='color: green;'>✅ No hay transacciones bloqueadas</p>";
        } else {
            echo "<pre>";
            print_r($transactions);
            echo "</pre>";
        }
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ No se puede acceder a información de transacciones InnoDB</p>";
    }
    
    // 3. Ver bloqueos de tablas
    echo "<h3>Tablas Bloqueadas:</h3>";
    try {
        $stmt = $pdo->query("SHOW OPEN TABLES WHERE In_use > 0");
        $locks = $stmt->fetchAll();
        if (empty($locks)) {
            echo "<p style='color: green;'>✅ No hay tablas bloqueadas</p>";
        } else {
            echo "<pre>";
            print_r($locks);
            echo "</pre>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    }
    
    // 4. Optimizar tablas
    echo "<h3>Optimizando Tablas:</h3>";
    $tables = ['usuarios', 'ordenes_servicio', 'retiros_medidores', 'login_attempts', 
               'auditoria', 'sesiones_oc', 'password_history', 'imposibilidad_tipos'];
    
    foreach ($tables as $table) {
        try {
            $pdo->exec("OPTIMIZE TABLE $table");
            echo "<p style='color: green;'>✅ Tabla '$table' optimizada</p>";
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠️ No se pudo optimizar '$table': " . $e->getMessage() . "</p>";
        }
    }
    
    // 5. Limpiar registros antiguos de login_attempts
    echo "<h3>Limpiando Registros Antiguos:</h3>";
    $stmt = $pdo->exec("DELETE FROM login_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    echo "<p style='color: green;'>✅ Eliminados $stmt registros antiguos de login_attempts</p>";
    
    // 6. Resetear auto-increment si hay gaps grandes
    echo "<h3>Optimizando Auto-Increment:</h3>";
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT MAX(id) as max_id FROM $table");
            $result = $stmt->fetch();
            if ($result && $result['max_id']) {
                $newId = $result['max_id'] + 1;
                $pdo->exec("ALTER TABLE $table AUTO_INCREMENT = $newId");
                echo "<p style='color: green;'>✅ Auto-increment de '$table' ajustado a $newId</p>";
            }
        } catch (Exception $e) {
            // Ignorar tablas sin columna id
        }
    }
    
    echo "<hr>";
    echo "<h2 style='color: green;'>✅ Limpieza Completada</h2>";
    echo "<p><a href='index.php'>← Volver al inicio</a></p>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Error de Conexión</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    echo "<p><strong>Solución:</strong> Reinicia MySQL desde XAMPP Control Panel</p>";
}
?>
