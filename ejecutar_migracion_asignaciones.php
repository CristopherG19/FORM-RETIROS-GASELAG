<?php
/**
 * Script para ejecutar la migración del sistema de asignaciones
 * Ejecutar una sola vez
 */

require_once 'config/database.php';

// Configuración de errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Migración - Sistema de Asignaciones</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { padding: 40px; background: #f8f9fa; }
        .resultado { margin: 20px 0; padding: 20px; border-radius: 8px; }
        .exito { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        pre { background: #fff; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class='container'>
        <h1 class='mb-4'>🔧 Migración: Sistema de Asignación de OCs</h1>
";

try {
    $pdo = getConnection();
    
    // Leer el archivo SQL
    $sqlFile = __DIR__ . '/database/migration_asignaciones_oc.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Archivo de migración no encontrado: {$sqlFile}");
    }
    
    echo "<div class='resultado info'>
            <h4>📁 Leyendo archivo de migración...</h4>
            <p><code>{$sqlFile}</code></p>
          </div>";
    
    $sql = file_get_contents($sqlFile);
    
    // Remover comentarios de SQL y líneas vacías
    $sql = preg_replace('/^--.*$/m', '', $sql);
    $sql = preg_replace('/^\s*$/m', '', $sql);
    
    // Separar por punto y coma (statements individuales)
    // Pero necesitamos ser cuidadosos con DELIMITER
    $statements = [];
    $currentStatement = '';
    $inDelimiter = false;
    $customDelimiter = ';';
    
    foreach (explode("\n", $sql) as $line) {
        $line = trim($line);
        
        // Detectar cambio de delimitador
        if (preg_match('/^DELIMITER\s+(.+)$/i', $line, $matches)) {
            $customDelimiter = trim($matches[1]);
            $inDelimiter = ($customDelimiter !== ';');
            continue;
        }
        
        if (empty($line)) continue;
        
        $currentStatement .= $line . "\n";
        
        // Verificar si terminó el statement
        if (substr(rtrim($line), -strlen($customDelimiter)) === $customDelimiter) {
            // Remover el delimitador del final
            $currentStatement = substr($currentStatement, 0, -strlen($customDelimiter));
            $currentStatement = trim($currentStatement);
            
            if (!empty($currentStatement) && !preg_match('/^USE\s+/i', $currentStatement)) {
                $statements[] = $currentStatement;
            }
            
            $currentStatement = '';
        }
    }
    
    // Agregar último statement si quedó algo
    if (!empty(trim($currentStatement))) {
        $statements[] = trim($currentStatement);
    }
    
    echo "<div class='resultado info'>
            <h4>📋 Statements encontrados: " . count($statements) . "</h4>
          </div>";
    
    // Ejecutar cada statement
    $ejecutados = 0;
    $errores = 0;
    
    foreach ($statements as $index => $statement) {
        try {
            // Mostrar qué se está ejecutando (primeras líneas)
            $preview = substr($statement, 0, 100);
            echo "<div class='resultado info'>
                    <strong>Ejecutando statement " . ($index + 1) . ":</strong><br>
                    <small><code>" . htmlspecialchars($preview) . "...</code></small>
                  </div>";
            
            $pdo->exec($statement);
            $ejecutados++;
            
            echo "<div class='resultado exito'>
                    ✅ Statement " . ($index + 1) . " ejecutado correctamente
                  </div>";
            
        } catch (PDOException $e) {
            $errores++;
            
            // Algunos errores son aceptables (como cuando algo ya existe)
            if (strpos($e->getMessage(), 'already exists') !== false || 
                strpos($e->getMessage(), 'Duplicate key name') !== false ||
                strpos($e->getMessage(), 'Cannot add') !== false) {
                
                echo "<div class='resultado info'>
                        ℹ️ Statement " . ($index + 1) . " (ya existe, omitiendo): " . htmlspecialchars($e->getMessage()) . "
                      </div>";
            } else {
                echo "<div class='resultado error'>
                        ❌ Error en statement " . ($index + 1) . ":<br>
                        <pre>" . htmlspecialchars($e->getMessage()) . "</pre>
                      </div>";
            }
        }
    }
    
    // Resumen final
    echo "<div class='resultado exito'>
            <h3>✅ Migración completada</h3>
            <ul>
                <li><strong>Statements ejecutados:</strong> {$ejecutados}</li>
                <li><strong>Errores:</strong> {$errores}</li>
            </ul>
          </div>";
    
    // Verificar que las tablas se crearon
    echo "<div class='resultado info'>
            <h4>🔍 Verificando tablas creadas...</h4>
          </div>";
    
    $tablas = $pdo->query("SHOW TABLES LIKE '%asignacion%'")->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tablas) > 0) {
        echo "<div class='resultado exito'>
                <h5>✅ Tablas encontradas:</h5>
                <ul>";
        foreach ($tablas as $tabla) {
            echo "<li><code>{$tabla}</code></li>";
        }
        echo "</ul></div>";
    } else {
        echo "<div class='resultado error'>
                <h5>⚠️ No se encontraron las tablas de asignaciones</h5>
              </div>";
    }
    
    // Verificar triggers
    echo "<div class='resultado info'>
            <h4>🔍 Verificando triggers...</h4>
          </div>";
    
    $triggers = $pdo->query("SHOW TRIGGERS WHERE `Trigger` LIKE '%retiro%'")->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($triggers) > 0) {
        echo "<div class='resultado exito'>
                <h5>✅ Triggers encontrados:</h5>
                <ul>";
        foreach ($triggers as $trigger) {
            echo "<li><code>{$trigger['Trigger']}</code> en tabla <code>{$trigger['Table']}</code></li>";
        }
        echo "</ul></div>";
    }
    
    echo "<div class='alert alert-success mt-4'>
            <h4>🎉 ¡Migración exitosa!</h4>
            <p>El sistema de asignaciones ya está listo para usar.</p>
            <a href='pages/listar_oc.php' class='btn btn-primary'>Ir a Listar OCs</a>
            <a href='pages/asignar_oc_masivo.php' class='btn btn-secondary'>Asignación Masiva</a>
          </div>";
    
} catch (Exception $e) {
    echo "<div class='resultado error'>
            <h4>❌ Error fatal en la migración</h4>
            <pre>" . htmlspecialchars($e->getMessage()) . "</pre>
            <p><strong>Traza completa:</strong></p>
            <pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>
          </div>";
}

echo "
    </div>
</body>
</html>
";
?>

