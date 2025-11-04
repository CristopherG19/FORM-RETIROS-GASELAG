<?php
/**
 * Diagnóstico de Performance - GASELAG
 * Identifica cuellos de botella
 */

echo "<h1>🔍 Diagnóstico de Performance</h1>";
echo "<hr>";

// 1. Configuración de PHP
echo "<h2>⚙️ Configuración PHP</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Configuración</th><th>Valor</th><th>Recomendado</th><th>Estado</th></tr>";

$configs = [
    'max_execution_time' => ['current' => ini_get('max_execution_time'), 'recommended' => '30'],
    'memory_limit' => ['current' => ini_get('memory_limit'), 'recommended' => '128M'],
    'post_max_size' => ['current' => ini_get('post_max_size'), 'recommended' => '8M'],
    'upload_max_filesize' => ['current' => ini_get('upload_max_filesize'), 'recommended' => '2M'],
];

foreach ($configs as $name => $values) {
    echo "<tr><td>$name</td><td>{$values['current']}</td><td>{$values['recommended']}</td><td>✅</td></tr>";
}
echo "</table>";

// 2. Tiempo de conexión a BD
echo "<h2>🗄️ Conexión a Base de Datos</h2>";
$start = microtime(true);
try {
    $dsn = "mysql:host=localhost;port=3307;dbname=gaselag_retiros;charset=utf8mb4";
    $pdo = new PDO($dsn, 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $end = microtime(true);
    $time = round(($end - $start) * 1000, 2);
    
    $status = $time < 100 ? '✅' : ($time < 500 ? '⚠️' : '❌');
    echo "<p>$status Tiempo de conexión: <strong>{$time}ms</strong></p>";
    
    if ($time > 100) {
        echo "<p style='color: orange;'>⚠️ La conexión es lenta. Posibles causas:</p>";
        echo "<ul><li>MySQL sobrecargado</li><li>Demasiadas conexiones activas</li></ul>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    exit;
}

// 3. Tiempo de queries básicas
echo "<h2>⚡ Velocidad de Queries</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Query</th><th>Tiempo (ms)</th><th>Estado</th></tr>";

$queries = [
    "SELECT COUNT(*) FROM usuarios" => "Contar usuarios",
    "SELECT * FROM usuarios LIMIT 1" => "Obtener un usuario",
    "SELECT COUNT(*) FROM auditoria_retiros" => "Contar auditorías",
    "SELECT COUNT(*) FROM login_attempts" => "Contar intentos de login",
];

foreach ($queries as $sql => $description) {
    $start = microtime(true);
    $pdo->query($sql);
    $end = microtime(true);
    $time = round(($end - $start) * 1000, 2);
    
    $status = $time < 50 ? '✅' : ($time < 200 ? '⚠️' : '❌');
    echo "<tr><td>$description</td><td>{$time}ms</td><td>$status</td></tr>";
}
echo "</table>";

// 4. Tiempo de password_hash()
echo "<h2>🔐 Velocidad de Hashing (password_hash)</h2>";
$start = microtime(true);
$hash = password_hash("test12345", PASSWORD_DEFAULT);
$end = microtime(true);
$hashTime = round(($end - $start) * 1000, 2);

$status = $hashTime < 100 ? '✅' : ($hashTime < 300 ? '⚠️' : '❌');
echo "<p>$status Tiempo de hash: <strong>{$hashTime}ms</strong></p>";

if ($hashTime > 200) {
    echo "<p style='color: orange;'>⚠️ El hashing de contraseñas es LENTO. Este es probablemente el problema principal.</p>";
    echo "<p><strong>Solución:</strong> El costo de bcrypt está muy alto para tu CPU.</p>";
}

// 5. Verificar el costo de bcrypt
$info = password_get_info($hash);
echo "<p>Algoritmo: {$info['algo']} | Costo actual: <strong>" . ($info['options']['cost'] ?? 'default (10)') . "</strong></p>";
echo "<p>💡 <strong>Recomendación:</strong> Para desarrollo local, un costo de 8 es suficiente y más rápido.</p>";

// 6. Probar diferentes costos
echo "<h3>🧪 Comparación de Costos de Bcrypt</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Costo</th><th>Tiempo (ms)</th><th>Recomendado para</th></tr>";

foreach ([8, 10, 12] as $cost) {
    $start = microtime(true);
    password_hash("test12345", PASSWORD_BCRYPT, ['cost' => $cost]);
    $end = microtime(true);
    $time = round(($end - $start) * 1000, 2);
    
    $recommended = '';
    if ($cost == 8) $recommended = "✅ Desarrollo local";
    if ($cost == 10) $recommended = "⚙️ Producción normal";
    if ($cost == 12) $recommended = "🔒 Alta seguridad";
    
    echo "<tr><td>$cost</td><td>{$time}ms</td><td>$recommended</td></tr>";
}
echo "</table>";

// 7. Procesos MySQL activos
echo "<h2>📊 Procesos MySQL</h2>";
try {
    $stmt = $pdo->query("SHOW PROCESSLIST");
    $processes = $stmt->fetchAll();
    echo "<p>Procesos activos: <strong>" . count($processes) . "</strong></p>";
    
    if (count($processes) > 5) {
        echo "<p style='color: orange;'>⚠️ Hay muchos procesos activos</p>";
    }
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>User</th><th>Host</th><th>DB</th><th>Command</th><th>Time</th><th>State</th></tr>";
    
    foreach ($processes as $proc) {
        $timeWarning = $proc['Time'] > 10 ? "style='background-color: #ffcccc;'" : "";
        echo "<tr $timeWarning>";
        echo "<td>{$proc['Id']}</td>";
        echo "<td>{$proc['User']}</td>";
        echo "<td>{$proc['Host']}</td>";
        echo "<td>{$proc['db']}</td>";
        echo "<td>{$proc['Command']}</td>";
        echo "<td>{$proc['Time']}s</td>";
        echo "<td>{$proc['State']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// 8. Verificar extensiones PHP
echo "<h2>🔌 Extensiones PHP</h2>";
$extensions = ['pdo', 'pdo_mysql', 'openssl', 'mbstring', 'gd'];
echo "<ul>";
foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext);
    $status = $loaded ? '✅' : '❌';
    echo "<li>$status $ext</li>";
}
echo "</ul>";

// Resumen y recomendaciones
echo "<hr>";
echo "<h2>📋 Resumen y Recomendaciones</h2>";

if ($hashTime > 200) {
    echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>";
    echo "<h3>⚠️ Problema Principal Detectado: Hashing Lento</h3>";
    echo "<p><strong>El password_hash() tarda {$hashTime}ms</strong> - esto es muy lento para desarrollo.</p>";
    echo "<p><strong>Solución:</strong></p>";
    echo "<ol>";
    echo "<li>Reducir el costo de bcrypt de 10 a 8 para desarrollo</li>";
    echo "<li>Esto hará que el cambio de contraseña sea <strong>3-4x más rápido</strong></li>";
    echo "</ol>";
    echo "<p><a href='optimizar_bcrypt.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Aplicar Optimización</a></p>";
    echo "</div>";
} else {
    echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745;'>";
    echo "<h3>✅ Performance Aceptable</h3>";
    echo "<p>El sistema está funcionando dentro de los parámetros normales.</p>";
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='index.php'>← Volver al inicio</a></p>";
?>
