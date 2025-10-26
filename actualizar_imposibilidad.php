<?php
/**
 * Script de actualización para agregar sistema de tipos de imposibilidad
 * GASELAG - Sistema de Retiros de Medidores
 */

require_once 'config/database.php';

echo "<style>
body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
.container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 900px; margin: 0 auto; }
.success { color: #28a745; }
.error { color: #dc3545; }
.warning { color: #ffc107; }
.info { color: #17a2b8; }
</style>";

echo "<div class='container'>";
echo "<h1>🔄 Actualizando Sistema de Tipos de Imposibilidad</h1>";
echo "<p>Implementando catálogo estructurado de motivos de no retiro...</p>\n";

try {
    $pdo = getConnection();

    // 1. Verificar y crear tabla tipos_imposibilidad
    echo "<h3>📊 Creando tabla de tipos de imposibilidad...</h3>\n";

    $createTableSql = "CREATE TABLE IF NOT EXISTS tipos_imposibilidad (
        id INT AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(20) NOT NULL UNIQUE,
        descripcion VARCHAR(100) NOT NULL,
        categoria ENUM('acceso', 'medidor', 'cliente', 'seguridad', 'otros') NOT NULL,
        activo ENUM('SI', 'NO') NOT NULL DEFAULT 'SI',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_categoria (categoria),
        INDEX idx_activo (activo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    try {
        $pdo->exec($createTableSql);
        echo "<p class='success'>✅ Tabla tipos_imposibilidad creada</p>\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'already exists') === false) {
            echo "<p class='error'>❌ Error creando tabla: " . $e->getMessage() . "</p>\n";
        } else {
            echo "<p class='success'>✅ Tabla tipos_imposibilidad ya existe</p>\n";
        }
    }

    // 2. Insertar tipos predefinidos
    echo "<h3>📝 Insertando tipos de imposibilidad predefinidos...</h3>\n";

    $tiposPredefinidos = [
        ['NIPLE', 'Se encontró conexión con niple', 'medidor'],
        ['OPOSICION', 'Usuario se opuso al retiro', 'cliente'],
        ['INTERIOR', 'Servicio en interior de la propiedad', 'acceso'],
        ['PELIGROSA', 'Zona peligrosa o de difícil acceso', 'seguridad'],
        ['NO_COINCIDE', 'Medidor no coincide con la orden', 'medidor'],
        ['SIN_CONTOMETRO', 'Sin contómetro o dispositivo de medición', 'medidor'],
        ['OBRA', 'Obra en progreso en la propiedad', 'acceso'],
        ['AUSENTE', 'Cliente/usuario ausente', 'cliente'],
        ['DANADO', 'Medidor dañado o averiado', 'medidor'],
        ['NO_LOCALIZADO', 'Medidor no localizado en la dirección', 'acceso'],
        ['OTROS', 'Otros motivos', 'otros']
    ];

    foreach ($tiposPredefinidos as $tipo) {
        try {
            $sql = "INSERT IGNORE INTO tipos_imposibilidad (codigo, descripcion, categoria)
                    VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($tipo);
            echo "<p class='success'>✅ Tipo: {$tipo[1]}</p>\n";
        } catch (Exception $e) {
            echo "<p class='warning'>⚠️ Error con tipo {$tipo[1]}: " . $e->getMessage() . "</p>\n";
        }
    }

    // 3. Agregar columnas nuevas a retiros_medidores
    echo "<h3>🔧 Actualizando tabla retiros_medidores...</h3>\n";

    $columnsToAdd = [
        'tipo_imposibilidad_id' => 'INT NULL',
        'detalles_imposibilidad' => 'TEXT NULL'
    ];

    foreach ($columnsToAdd as $column => $type) {
        try {
            $sql = "ALTER TABLE retiros_medidores ADD COLUMN $column $type";
            $pdo->exec($sql);
            echo "<p class='success'>✅ Columna $column agregada</p>\n";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate column') === false) {
                echo "<p class='error'>❌ Error agregando columna $column: " . $e->getMessage() . "</p>\n";
            } else {
                echo "<p class='success'>✅ Columna $column ya existe</p>\n";
            }
        }
    }

    // 4. Agregar foreign key
    echo "<h3>🔗 Configurando relaciones...</h3>\n";

    try {
        $sql = "ALTER TABLE retiros_medidores
                ADD FOREIGN KEY (tipo_imposibilidad_id) REFERENCES tipos_imposibilidad(id),
                ADD INDEX idx_tipo_imposibilidad (tipo_imposibilidad_id)";
        $pdo->exec($sql);
        echo "<p class='success'>✅ Foreign key y índice agregados</p>\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate key') === false && strpos($e->getMessage(), 'already exists') === false) {
            echo "<p class='error'>❌ Error configurando foreign key: " . $e->getMessage() . "</p>\n";
        } else {
            echo "<p class='success'>✅ Foreign key ya existe</p>\n";
        }
    }

    // 5. Verificar tipos existentes
    echo "<h3>📋 Verificando tipos de imposibilidad...</h3>\n";

    $sql = "SELECT COUNT(*) as total FROM tipos_imposibilidad WHERE activo = 'SI'";
    $result = $pdo->query($sql)->fetch();
    echo "<p class='info'>📊 Tipos de imposibilidad activos: {$result['total']}</p>\n";

    $sql = "SELECT categoria, COUNT(*) as cantidad
            FROM tipos_imposibilidad
            WHERE activo = 'SI'
            GROUP BY categoria
            ORDER BY categoria";
    $result = $pdo->query($sql)->fetchAll();

    echo "<p><strong>Distribución por categorías:</strong></p>\n";
    echo "<ul>\n";
    foreach ($result as $categoria) {
        echo "<li><strong>{$categoria['categoria']}:</strong> {$categoria['cantidad']} tipos</li>\n";
    }
    echo "</ul>\n";

    // 6. Verificar funciones del sistema
    echo "<h3>🔧 Verificando funciones del sistema...</h3>\n";

    $configContent = file_get_contents('config/database.php');

    $requiredFunctions = [
        'getTiposImposibilidad',
        'getTipoImposibilidad',
        'createTipoImposibilidad',
        'updateTipoImposibilidad',
        'deleteTipoImposibilidad',
        'getEstadisticasImposibilidad'
    ];

    $functionsFound = 0;
    foreach ($requiredFunctions as $function) {
        if (strpos($configContent, "function $function(") !== false) {
            echo "<p class='success'>✅ Función $function detectada</p>\n";
            $functionsFound++;
        } else {
            echo "<p class='error'>❌ Función $function no encontrada</p>\n";
        }
    }

    echo "<p class='info'>Funciones encontradas: $functionsFound/" . count($requiredFunctions) . "</p>\n";

    // 7. Verificar páginas actualizadas
    echo "<h3>📁 Verificando páginas del sistema...</h3>\n";

    $pages = [
        'pages/formulario_retiro.php' => 'Formulario actualizado con tipos',
        'pages/detalle_retiro.php' => 'Detalle actualizado con tipos',
        'pages/gestion_imposibilidad.php' => 'Gestión de tipos (admin)',
        'index.php' => 'Menú actualizado'
    ];

    foreach ($pages as $page => $description) {
        if (file_exists($page)) {
            echo "<p class='success'>✅ $description</p>\n";
        } else {
            echo "<p class='error'>❌ $description - Archivo no encontrado</p>\n";
        }
    }

    // 8. Resumen final
    echo "<hr>\n";
    echo "<h2>📋 Resumen de la Actualización</h2>\n";

    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 15px 0;'>\n";
    echo "<h3 style='color: #155724; margin-top: 0;'>✅ Funcionalidades Implementadas:</h3>\n";
    echo "<ul>\n";
    echo "<li><strong>Catálogo de tipos:</strong> 11 tipos de imposibilidad predefinidos</li>\n";
    echo "<li><strong>Gestión por admin:</strong> Crear, editar y desactivar tipos</li>\n";
    echo "<li><strong>Formulario mejorado:</strong> Lista desplegable en lugar de texto libre</li>\n";
    echo "<li><strong>Detalles específicos:</strong> Campo adicional para información complementaria</li>\n";
    echo "<li><strong>Compatibilidad:</strong> Mantiene funcionalidad con registros existentes</li>\n";
    echo "<li><strong>Estadísticas:</strong> Reportes por tipo de imposibilidad</li>\n";
    echo "</ul>\n";
    echo "</div>\n";

    echo "<div style='background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px; margin: 15px 0;'>\n";
    echo "<h3 style='color: #0c5460; margin-top: 0;'>📊 Tipos de Imposibilidad Disponibles:</h3>\n";
    echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 0.9em;'>\n";

    $sql = "SELECT descripcion, categoria FROM tipos_imposibilidad WHERE activo = 'SI' ORDER BY categoria, descripcion";
    $tipos = $pdo->query($sql)->fetchAll();

    foreach ($tipos as $tipo) {
        $icon = match($tipo['categoria']) {
            'acceso' => '🚪',
            'medidor' => '⚡',
            'cliente' => '👤',
            'seguridad' => '⚠️',
            'otros' => '📋',
            default => '❓'
        };
        echo "<div><strong>{$icon} {$tipo['categoria']}:</strong> {$tipo['descripcion']}</div>\n";
    }
    echo "</div>\n";
    echo "</div>\n";

    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 15px 0;'>\n";
    echo "<h3 style='color: #856404; margin-top: 0;'>⚠️ Registros Existentes:</h3>\n";
    echo "<p>Los registros anteriores al nuevo sistema seguirán funcionando normalmente.</p>\n";
    echo "<p>El campo de observaciones se mantendrá como respaldo y complemento.</p>\n";
    echo "</div>\n";

    echo "<p><a href='login.php' style='background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px 10px 0;'>🌐 Ir al Login</a></p>\n";
    echo "<p><a href='INICIAR_AQUI.html' style='background: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px 10px 0;'>🚀 Página de Inicio</a></p>\n";

} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 15px 0;'>\n";
    echo "<h3 style='color: #721c24; margin-top: 0;'>❌ Error en la Actualización:</h3>\n";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "</div>\n";
}

echo "</div>";
?>
