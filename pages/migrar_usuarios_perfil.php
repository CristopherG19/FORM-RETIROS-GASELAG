<?php
/**
 * Ejecutar Migración: Agregar campos de perfil a usuarios
 */

require_once '../config/database.php';

// Verificar que sea admin
requireRole(['admin']);

echo "<h1>🔄 Migración: Campos de Perfil de Usuarios</h1>";
echo "<hr>";

try {
    $pdo = getConnection();
    
    echo "<h2>Paso 1: Agregar nuevos campos a la tabla usuarios</h2>";
    
    // Campo: foto_perfil
    try {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN foto_perfil VARCHAR(255) NULL COMMENT 'Ruta de la foto de perfil'");
        echo "<p style='color: green;'>✅ Campo 'foto_perfil' agregado</p>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<p style='color: orange;'>⚠️ Campo 'foto_perfil' ya existe</p>";
        } else {
            throw $e;
        }
    }
    
    // Campo: telefono
    try {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN telefono VARCHAR(20) NULL COMMENT 'Teléfono de contacto'");
        echo "<p style='color: green;'>✅ Campo 'telefono' agregado</p>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<p style='color: orange;'>⚠️ Campo 'telefono' ya existe</p>";
        } else {
            throw $e;
        }
    }
    
    // Campo: direccion
    try {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN direccion TEXT NULL COMMENT 'Dirección completa'");
        echo "<p style='color: green;'>✅ Campo 'direccion' agregado</p>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<p style='color: orange;'>⚠️ Campo 'direccion' ya existe</p>";
        } else {
            throw $e;
        }
    }
    
    // Campo: fecha_nacimiento
    try {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN fecha_nacimiento DATE NULL COMMENT 'Fecha de nacimiento'");
        echo "<p style='color: green;'>✅ Campo 'fecha_nacimiento' agregado</p>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<p style='color: orange;'>⚠️ Campo 'fecha_nacimiento' ya existe</p>";
        } else {
            throw $e;
        }
    }
    
    // Campo: documento_identidad
    try {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN documento_identidad VARCHAR(20) NULL COMMENT 'Número de documento'");
        echo "<p style='color: green;'>✅ Campo 'documento_identidad' agregado</p>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<p style='color: orange;'>⚠️ Campo 'documento_identidad' ya existe</p>";
        } else {
            throw $e;
        }
    }
    
    // Campo: cargo
    try {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN cargo VARCHAR(100) NULL COMMENT 'Cargo o puesto'");
        echo "<p style='color: green;'>✅ Campo 'cargo' agregado</p>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<p style='color: orange;'>⚠️ Campo 'cargo' ya existe</p>";
        } else {
            throw $e;
        }
    }
    
    // Campo: fecha_ingreso
    try {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN fecha_ingreso DATE NULL COMMENT 'Fecha de ingreso a la empresa'");
        echo "<p style='color: green;'>✅ Campo 'fecha_ingreso' agregado</p>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<p style='color: orange;'>⚠️ Campo 'fecha_ingreso' ya existe</p>";
        } else {
            throw $e;
        }
    }
    
    // Campo: notas
    try {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN notas TEXT NULL COMMENT 'Notas o comentarios adicionales'");
        echo "<p style='color: green;'>✅ Campo 'notas' agregado</p>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<p style='color: orange;'>⚠️ Campo 'notas' ya existe</p>";
        } else {
            throw $e;
        }
    }
    
    // Campo: estado_laboral
    try {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN estado_laboral ENUM('activo', 'vacaciones', 'licencia', 'inactivo') DEFAULT 'activo' COMMENT 'Estado laboral del empleado'");
        echo "<p style='color: green;'>✅ Campo 'estado_laboral' agregado</p>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<p style='color: orange;'>⚠️ Campo 'estado_laboral' ya existe</p>";
        } else {
            throw $e;
        }
    }
    
    echo "<hr>";
    echo "<h2>Paso 2: Crear carpeta para fotos de perfil</h2>";
    
    $uploadDir = '../uploads/perfiles';
    if (!file_exists($uploadDir)) {
        if (mkdir($uploadDir, 0755, true)) {
            echo "<p style='color: green;'>✅ Carpeta 'uploads/perfiles/' creada</p>";
        } else {
            echo "<p style='color: red;'>❌ No se pudo crear la carpeta 'uploads/perfiles/'</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ Carpeta 'uploads/perfiles/' ya existe</p>";
    }
    
    // Crear archivo index.php de seguridad
    $indexFile = $uploadDir . '/index.php';
    if (!file_exists($indexFile)) {
        file_put_contents($indexFile, '<?php header("HTTP/1.0 403 Forbidden"); ?>');
        echo "<p style='color: green;'>✅ Archivo de seguridad creado en uploads/perfiles/</p>";
    }
    
    echo "<hr>";
    echo "<h2>Paso 3: Verificar estructura de la tabla</h2>";
    
    $stmt = $pdo->query("DESCRIBE usuarios");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<div style='background: #d4edda; padding: 20px; border-left: 4px solid #28a745;'>";
    echo "<h2>✅ Migración Completada Exitosamente</h2>";
    echo "<p><strong>Nuevos campos agregados a la tabla usuarios:</strong></p>";
    echo "<ul>";
    echo "<li>foto_perfil</li>";
    echo "<li>telefono</li>";
    echo "<li>direccion</li>";
    echo "<li>fecha_nacimiento</li>";
    echo "<li>documento_identidad</li>";
    echo "<li>cargo</li>";
    echo "<li>fecha_ingreso</li>";
    echo "<li>notas</li>";
    echo "<li>estado_laboral</li>";
    echo "</ul>";
    echo "<p><a href='gestion_usuarios.php' class='btn btn-primary' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ir a Gestión de Usuarios</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border-left: 4px solid #dc3545;'>";
    echo "<h2>❌ Error en la Migración</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "</div>";
}
?>
