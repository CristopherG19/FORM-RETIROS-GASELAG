<?php
/**
 * Optimización de Bcrypt para Desarrollo Local
 * Reduce el costo de bcrypt de 10 a 8 para mejor performance
 */

echo "<h1>⚡ Optimización de Performance - Bcrypt</h1>";
echo "<hr>";

// Definir el costo óptimo para desarrollo
define('BCRYPT_COST_DEV', 8);
define('BCRYPT_COST_PROD', 10);

echo "<h2>Configuración Actual vs Recomendada</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Parámetro</th><th>Actual</th><th>Recomendado (Dev)</th></tr>";
echo "<tr><td>Algoritmo</td><td>PASSWORD_DEFAULT (bcrypt)</td><td>PASSWORD_BCRYPT</td></tr>";
echo "<tr><td>Costo</td><td>10 (predeterminado)</td><td><strong>8</strong></td></tr>";
echo "<tr><td>Tiempo estimado</td><td>~200-400ms</td><td>~50-100ms</td></tr>";
echo "<tr><td>Mejora</td><td>-</td><td><strong style='color: green;'>3-4x más rápido ⚡</strong></td></tr>";
echo "</table>";

echo "<h2>🔍 ¿Qué hace esta optimización?</h2>";
echo "<p>Modifica los archivos que usan <code>password_hash()</code> para usar un costo menor durante desarrollo:</p>";
echo "<ul>";
echo "<li>✅ <code>pages/cambiar_password.php</code></li>";
echo "<li>✅ <code>pages/gestion_usuarios.php</code></li>";
echo "<li>✅ <code>config/QueryManager.php</code></li>";
echo "</ul>";

echo "<h2>⚠️ Importante</h2>";
echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>";
echo "<p><strong>Esta optimización es solo para desarrollo local.</strong></p>";
echo "<p>Para producción, se recomienda un costo de 10 o superior.</p>";
echo "</div>";

echo "<hr>";
echo "<h2>¿Deseas aplicar la optimización?</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    echo "<h3>🔧 Aplicando Optimización...</h3>";
    
    $archivosModificados = 0;
    $errores = [];
    
    // Archivo 1: pages/cambiar_password.php
    $file1 = '../pages/cambiar_password.php';
    if (file_exists($file1)) {
        $content = file_get_contents($file1);
        $original = '$newHash = password_hash($newPassword, PASSWORD_DEFAULT);';
        $optimized = '$newHash = password_hash($newPassword, PASSWORD_BCRYPT, [\'cost\' => 8]);';
        
        if (strpos($content, $original) !== false) {
            $content = str_replace($original, $optimized, $content);
            if (file_put_contents($file1, $content)) {
                echo "<p style='color: green;'>✅ Optimizado: pages/cambiar_password.php</p>";
                $archivosModificados++;
            } else {
                $errores[] = "No se pudo escribir: pages/cambiar_password.php";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ Ya optimizado o no encontrado: pages/cambiar_password.php</p>";
        }
    }
    
    // Archivo 2: pages/gestion_usuarios.php
    $file2 = '../pages/gestion_usuarios.php';
    if (file_exists($file2)) {
        $content = file_get_contents($file2);
        $original = '$hashedPassword = password_hash($password, PASSWORD_DEFAULT);';
        $optimized = '$hashedPassword = password_hash($password, PASSWORD_BCRYPT, [\'cost\' => 8]);';
        
        if (strpos($content, $original) !== false) {
            $content = str_replace($original, $optimized, $content);
            if (file_put_contents($file2, $content)) {
                echo "<p style='color: green;'>✅ Optimizado: pages/gestion_usuarios.php</p>";
                $archivosModificados++;
            } else {
                $errores[] = "No se pudo escribir: pages/gestion_usuarios.php";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ Ya optimizado o no encontrado: pages/gestion_usuarios.php</p>";
        }
    }
    
    // Archivo 3: config/QueryManager.php
    $file3 = '../config/QueryManager.php';
    if (file_exists($file3)) {
        $content = file_get_contents($file3);
        $original1 = 'password_hash($datos[\'password\'], PASSWORD_DEFAULT)';
        $optimized1 = 'password_hash($datos[\'password\'], PASSWORD_BCRYPT, [\'cost\' => 8])';
        
        $count = 0;
        $content = str_replace($original1, $optimized1, $content, $count);
        
        if ($count > 0) {
            if (file_put_contents($file3, $content)) {
                echo "<p style='color: green;'>✅ Optimizado: config/QueryManager.php ($count ocurrencias)</p>";
                $archivosModificados++;
            } else {
                $errores[] = "No se pudo escribir: config/QueryManager.php";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ Ya optimizado o no encontrado: config/QueryManager.php</p>";
        }
    }
    
    echo "<hr>";
    
    if (empty($errores)) {
        echo "<div style='background: #d4edda; padding: 20px; border-left: 4px solid #28a745;'>";
        echo "<h3>✅ Optimización Completada</h3>";
        echo "<p><strong>Archivos modificados:</strong> $archivosModificados</p>";
        echo "<p>El sistema ahora debería ser <strong>3-4x más rápido</strong> al cambiar contraseñas.</p>";
        echo "<p><a href='../login.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ir al Login</a></p>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 20px; border-left: 4px solid #dc3545;'>";
        echo "<h3>❌ Hubo algunos errores</h3>";
        echo "<ul>";
        foreach ($errores as $error) {
            echo "<li>$error</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
    
} else {
    // Mostrar formulario de confirmación
    echo "<form method='POST'>";
    echo "<button type='submit' name='confirm' value='1' style='background: #007bff; color: white; padding: 15px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;'>";
    echo "✅ Sí, Aplicar Optimización";
    echo "</button>";
    echo "</form>";
    echo "<br>";
    echo "<a href='index.php' style='color: #6c757d;'>← Cancelar y volver</a>";
}

echo "<hr>";
echo "<h3>🧪 Prueba de Velocidad</h3>";
echo "<p>Tiempo actual con costo 10:</p>";
$start = microtime(true);
password_hash("test123", PASSWORD_BCRYPT, ['cost' => 10]);
$end = microtime(true);
$time10 = round(($end - $start) * 1000, 2);
echo "<p><strong>{$time10}ms</strong></p>";

echo "<p>Tiempo optimizado con costo 8:</p>";
$start = microtime(true);
password_hash("test123", PASSWORD_BCRYPT, ['cost' => 8]);
$end = microtime(true);
$time8 = round(($end - $start) * 1000, 2);
echo "<p><strong>{$time8}ms</strong></p>";

$mejora = round(($time10 / $time8), 1);
echo "<p style='color: green; font-size: 18px;'><strong>Mejora: {$mejora}x más rápido 🚀</strong></p>";
?>
