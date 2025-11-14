<?php
/**
 * Generador de Hashes Optimizados para schema.sql
 */

echo "<h1>🔐 Generador de Hashes Optimizados</h1>";
echo "<hr>";

// Contraseña por defecto para todos los usuarios
$defaultPassword = 'password';

echo "<h2>Generando hashes con costo 8...</h2>";
echo "<p>Contraseña por defecto: <code>$defaultPassword</code></p>";

// Generar hashes optimizados
$adminHash = password_hash($defaultPassword, PASSWORD_BCRYPT, ['cost' => 8]);
$tecnico1Hash = password_hash($defaultPassword, PASSWORD_BCRYPT, ['cost' => 8]);
$tecnico2Hash = password_hash($defaultPassword, PASSWORD_BCRYPT, ['cost' => 8]);

echo "<h3>✅ Hashes Generados:</h3>";
echo "<table border='1' cellpadding='10' style='font-family: monospace; font-size: 12px;'>";
echo "<tr><th>Usuario</th><th>Hash (Costo 8)</th></tr>";
echo "<tr><td>admin</td><td>$adminHash</td></tr>";
echo "<tr><td>12345678 (Técnico 1)</td><td>$tecnico1Hash</td></tr>";
echo "<tr><td>87654321 (Técnico 2)</td><td>$tecnico2Hash</td></tr>";
echo "</table>";

echo "<hr>";
echo "<h2>📝 SQL para schema.sql</h2>";
echo "<p>Copia este código y reemplaza la sección de INSERT IGNORE INTO usuarios en <code>database/schema.sql</code>:</p>";

$sql = "-- Insertar usuarios por defecto con session_timeout según rol\n";
$sql .= "INSERT IGNORE INTO usuarios (username, password, nombre_completo, email, rol, session_timeout, force_password_change) VALUES\n";
$sql .= "('admin', '$adminHash', 'Administrador del Sistema', 'admin@gaselag.com', 'admin', 1800, TRUE),\n";
$sql .= "('12345678', '$tecnico1Hash', 'Juan Pérez Técnico', 'tecnico1@gaselag.com', 'user', 7200, TRUE),\n";
$sql .= "('87654321', '$tecnico2Hash', 'María González Técnico', 'tecnico2@gaselag.com', 'user', 7200, TRUE);\n";
$sql .= "-- Nota: Password por defecto es 'password' para todos. Deben cambiarla en el primer login.\n";

echo "<pre style='background: #f5f5f5; padding: 15px; border: 1px solid #ddd; overflow-x: auto;'>";
echo htmlspecialchars($sql);
echo "</pre>";

echo "<hr>";
echo "<h2>⚡ Comparación de Performance</h2>";

echo "<h3>Hash con Costo 10 (Actual):</h3>";
$start = microtime(true);
password_hash($defaultPassword, PASSWORD_BCRYPT, ['cost' => 10]);
$end = microtime(true);
$time10 = round(($end - $start) * 1000, 2);
echo "<p><strong>{$time10}ms</strong></p>";

echo "<h3>Hash con Costo 8 (Optimizado):</h3>";
$start = microtime(true);
password_hash($defaultPassword, PASSWORD_BCRYPT, ['cost' => 8]);
$end = microtime(true);
$time8 = round(($end - $start) * 1000, 2);
echo "<p><strong>{$time8}ms</strong></p>";

$mejora = round(($time10 / $time8), 1);
echo "<p style='color: green; font-size: 20px;'><strong>Mejora: {$mejora}x más rápido 🚀</strong></p>";

echo "<hr>";
echo "<p><strong>💡 Instrucciones:</strong></p>";
echo "<ol>";
echo "<li>Copia el SQL generado arriba</li>";
echo "<li>Abre <code>database/schema.sql</code></li>";
echo "<li>Busca la línea <code>INSERT IGNORE INTO usuarios</code></li>";
echo "<li>Reemplaza desde esa línea hasta el comentario final</li>";
echo "<li>Guarda el archivo</li>";
echo "<li>Reinstala el sistema</li>";
echo "</ol>";

echo "<p><a href='index.php'>← Volver al inicio</a></p>";
?>
