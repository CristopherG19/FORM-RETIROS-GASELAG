<?php
/**
 * Script de Reinstalación Completa
 * Elimina completamente la base de datos y la recrea desde cero
 * Útil para cambiar entre diferentes PCs
 */

require_once 'config/database.php';

try {
    echo "<h2>🔄 Reinstalación Completa del Sistema</h2>";
    echo "<p><strong>⚠️ ADVERTENCIA:</strong> Este proceso eliminará TODOS los datos existentes.</p>";
    
    // Conectar a MySQL sin especificar base de datos
    $dsn = "mysql:host=localhost;port=3307;charset=utf8mb4";
    $pdo = new PDO($dsn, 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "<h3>🗑️ Paso 1: Eliminando base de datos existente</h3>";
    
    // Eliminar base de datos completamente
    $pdo->exec("DROP DATABASE IF EXISTS gaselag_retiros");
    echo "✅ Base de datos <code>gaselag_retiros</code> eliminada completamente<br>";
    
    echo "<h3>🏗️ Paso 2: Creando nueva base de datos</h3>";
    
    // Crear nueva base de datos
    $pdo->exec("CREATE DATABASE gaselag_retiros CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Base de datos <code>gaselag_retiros</code> creada<br>";
    
    // Usar la nueva base de datos
    $pdo->exec("USE gaselag_retiros");
    
    echo "<h3>📋 Paso 3: Creando tablas y datos iniciales</h3>";
    
    // Leer y ejecutar schema.sql
    $sql = file_get_contents('database/schema.sql');
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $tablas_creadas = [];
    $datos_insertados = 0;
    
    foreach ($statements as $statement) {
        if (!empty($statement) && stripos($statement, 'CREATE DATABASE') === false && stripos($statement, 'USE ') === false) {
            $pdo->exec($statement);
            
            // Contar tablas creadas
            if (stripos($statement, 'CREATE TABLE') !== false) {
                preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches);
                if (isset($matches[1])) {
                    $tablas_creadas[] = $matches[1];
                }
            }
            
            // Contar datos insertados
            if (stripos($statement, 'INSERT') !== false) {
                $datos_insertados++;
            }
        }
    }
    
    echo "<h3>✅ Paso 4: Verificación de instalación</h3>";
    
    // Verificar tablas creadas
    $stmt = $pdo->query("SHOW TABLES");
    $tablas_existentes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<strong>Tablas creadas:</strong><br>";
    foreach ($tablas_existentes as $tabla) {
        echo "✓ $tabla<br>";
    }
    
    // Verificar usuarios
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM usuarios");
    $count_usuarios = $stmt->fetch()['count'];
    echo "<br><strong>Usuarios creados:</strong> $count_usuarios<br>";
    
    // Verificar tipos de imposibilidad
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM tipos_imposibilidad");
    $count_tipos = $stmt->fetch()['count'];
    echo "<strong>Tipos de imposibilidad:</strong> $count_tipos<br>";
    
    echo "<h3>🎉 Reinstalación Completada Exitosamente</h3>";
    
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>📊 Resumen de la Instalación:</h4>";
    echo "<ul>";
    echo "<li><strong>Base de datos:</strong> gaselag_retiros</li>";
    echo "<li><strong>Tablas creadas:</strong> " . count($tablas_existentes) . "</li>";
    echo "<li><strong>Usuarios por defecto:</strong> $count_usuarios</li>";
    echo "<li><strong>Tipos de imposibilidad:</strong> $count_tipos</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>🔑 Credenciales por Defecto:</h4>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> usuario: admin, contraseña: password</li>";
    echo "<li><strong>Técnico 1:</strong> usuario: tecnico1, contraseña: password</li>";
    echo "<li><strong>Técnico 2:</strong> usuario: tecnico2, contraseña: password</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='text-align: center; margin: 30px 0;'>";
    echo "<a href='instalar.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 0 10px;'>Continuar con Instalador</a>";
    echo "<a href='index.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 0 10px;'>Ir al Sistema</a>";
    echo "<a href='verificar_instalacion.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 0 10px;'>Verificar Instalación</a>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<h3>❌ Error durante la reinstalación:</h3>";
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; color: #721c24;'>";
    echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>Código:</strong> " . $e->getCode() . "<br>";
    echo "</div>";
    echo "<p>Por favor, verifica que:</p>";
    echo "<ul>";
    echo "<li>XAMPP esté corriendo (Apache y MySQL)</li>";
    echo "<li>MySQL esté en el puerto 3307</li>";
    echo "<li>El archivo config/database.php esté configurado correctamente</li>";
    echo "</ul>";
}
?>
