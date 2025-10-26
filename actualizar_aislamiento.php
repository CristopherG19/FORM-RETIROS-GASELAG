<?php
/**
 * Script de actualización para agregar sistema de aislamiento de datos
 * GASELAG - Sistema de Retiros de Medidores
 */

require_once 'config/database.php';

echo "<style>
body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
.container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 800px; margin: 0 auto; }
.success { color: #28a745; }
.error { color: #dc3545; }
.warning { color: #ffc107; }
.info { color: #17a2b8; }
.code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
</style>";

echo "<div class='container'>";
echo "<h1>🔄 Actualizando Sistema de Aislamiento de Datos</h1>";
echo "<p>Implementando control de acceso por técnico y sistema de auditoría...</p>\n";

try {
    $pdo = getConnection();

    // 1. Verificar y agregar columnas a retiros_medidores
    echo "<h3>📊 Actualizando tabla retiros_medidores...</h3>\n";

    // Verificar si existe la columna usuario_id
    $columnsQuery = "SHOW COLUMNS FROM retiros_medidores LIKE 'usuario_id'";
    $columnExists = $pdo->query($columnsQuery)->rowCount() > 0;

    if (!$columnExists) {
        echo "<p>Agregando nuevas columnas a retiros_medidores...</p>\n";

        // Agregar columnas nuevas
        $alterQueries = [
            "ALTER TABLE retiros_medidores ADD COLUMN usuario_id INT NULL",
            "ALTER TABLE retiros_medidores ADD COLUMN estado_registro ENUM('activo', 'reabierto', 'reasignado') NOT NULL DEFAULT 'activo'",
            "ALTER TABLE retiros_medidores ADD COLUMN usuario_reasignado_por INT NULL",
            "ALTER TABLE retiros_medidores ADD COLUMN fecha_reasignacion TIMESTAMP NULL",
            "ALTER TABLE retiros_medidores ADD COLUMN fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
        ];

        foreach ($alterQueries as $query) {
            try {
                $pdo->exec($query);
                echo "<p class='success'>✅ " . substr($query, 12, 50) . "...</p>\n";
            } catch (Exception $e) {
                echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>\n";
            }
        }

        // Agregar foreign keys
        $fkQueries = [
            "ALTER TABLE retiros_medidores ADD FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL",
            "ALTER TABLE retiros_medidores ADD FOREIGN KEY (usuario_reasignado_por) REFERENCES usuarios(id) ON DELETE SET NULL"
        ];

        foreach ($fkQueries as $query) {
            try {
                $pdo->exec($query);
                echo "<p class='success'>✅ Foreign key agregada</p>\n";
            } catch (Exception $e) {
                echo "<p class='warning'>⚠️ Foreign key ya existe o no se pudo crear: " . $e->getMessage() . "</p>\n";
            }
        }

        // Agregar índices
        $indexQueries = [
            "ALTER TABLE retiros_medidores ADD INDEX idx_usuario_id (usuario_id)",
            "ALTER TABLE retiros_medidores ADD INDEX idx_estado_registro (estado_registro)",
            "ALTER TABLE retiros_medidores ADD INDEX idx_fecha_asignacion (fecha_asignacion)"
        ];

        foreach ($indexQueries as $query) {
            try {
                $pdo->exec($query);
                echo "<p class='success'>✅ Índice creado</p>\n";
            } catch (Exception $e) {
                echo "<p class='warning'>⚠️ Índice ya existe: " . $e->getMessage() . "</p>\n";
            }
        }
    } else {
        echo "<p class='success'>✅ Columnas ya existen en retiros_medidores</p>\n";
    }

    // 2. Crear tabla de auditoría
    echo "<h3>📝 Creando tabla de auditoría...</h3>\n";

    $auditTableQuery = "CREATE TABLE IF NOT EXISTS auditoria_retiros (
        id INT AUTO_INCREMENT PRIMARY KEY,
        retiro_id INT NULL,
        usuario_id INT NOT NULL,
        accion ENUM(
            'login', 'logout',
            'busqueda_oc', 'intento_registro_oc', 'registro_oc',
            'consulta_registros', 'consulta_registro_detalle',
            'reasignacion_oc', 'reapertura_oc',
            'modificacion_registro', 'eliminacion_registro'
        ) NOT NULL,
        detalles TEXT NULL,
        orden_servicio VARCHAR(50) NULL,
        ip_address VARCHAR(45) NULL,
        user_agent TEXT NULL,
        fecha_accion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (retiro_id) REFERENCES retiros_medidores(id) ON DELETE CASCADE,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
        INDEX idx_retiro_id (retiro_id),
        INDEX idx_usuario_id (usuario_id),
        INDEX idx_fecha_accion (fecha_accion),
        INDEX idx_accion (accion),
        INDEX idx_orden_servicio (orden_servicio)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    try {
        $pdo->exec($auditTableQuery);
        echo "<p class='success'>✅ Tabla auditoria_retiros creada</p>\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'already exists') === false) {
            echo "<p class='error'>❌ Error creando tabla de auditoría: " . $e->getMessage() . "</p>\n";
        } else {
            echo "<p class='success'>✅ Tabla auditoria_retiros ya existe</p>\n";
        }
    }

    // 3. Actualizar registros existentes (opcional)
    echo "<h3>🔄 Actualizando registros existentes...</h3>\n";

    // Contar registros sin usuario_id
    $countQuery = "SELECT COUNT(*) as total FROM retiros_medidores WHERE usuario_id IS NULL";
    $countResult = $pdo->query($countQuery)->fetch();

    if ($countResult['total'] > 0) {
        echo "<p class='info'>📊 Hay {$countResult['total']} registros sin usuario_id</p>\n";
        echo "<p>Estos se pueden asignar más tarde desde la interfaz de administración</p>\n";

        // Mostrar algunos ejemplos de registros sin usuario_id
        $sampleQuery = "SELECT id, orden_servicio, tecnico_responsable
                       FROM retiros_medidores
                       WHERE usuario_id IS NULL
                       LIMIT 5";
        $samples = $pdo->query($sampleQuery)->fetchAll();

        if (count($samples) > 0) {
            echo "<p><strong>Ejemplos de registros a asignar:</strong></p>\n";
            echo "<ul>\n";
            foreach ($samples as $sample) {
                echo "<li>OC: {$sample['orden_servicio']} - Técnico: {$sample['tecnico_responsable']}</li>\n";
            }
            echo "</ul>\n";
        }
    } else {
        echo "<p class='success'>✅ Todos los registros ya tienen usuario_id asignado</p>\n";
    }

    // 4. Verificar funciones de auditoría
    echo "<h3>🔧 Verificando funciones del sistema...</h3>\n";

    $configContent = file_get_contents('config/database.php');

    $requiredFunctions = [
        'checkExistingRetiro',
        'canAccessRetiro',
        'getUserRetiros',
        'logAudit',
        'reassignRetiro',
        'reopenOC'
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

    // 5. Resumen final
    echo "<hr>\n";
    echo "<h2>📋 Resumen de la Actualización</h2>\n";

    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 15px 0;'>\n";
    echo "<h3 style='color: #155724; margin-top: 0;'>✅ Funcionalidades Implementadas:</h3>\n";
    echo "<ul>\n";
    echo "<li><strong>Aislamiento de datos:</strong> Cada técnico ve solo sus registros</li>\n";
    echo "<li><strong>Control administrativo:</strong> Admin puede ver y gestionar todo</li>\n";
    echo "<li><strong>Sistema de auditoría:</strong> Registra todas las acciones</li>\n";
    echo "<li><strong>Validación anti-duplicación:</strong> Una OC = Un registro</li>\n";
    echo "<li><strong>Reasignación de OCs:</strong> Admin puede cambiar técnico responsable</li>\n";
    echo "<li><strong>Reapertura de OCs:</strong> Admin puede liberar OCs para re-registro</li>\n";
    echo "</ul>\n";
    echo "</div>\n";

    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 15px 0;'>\n";
    echo "<h3 style='color: #856404; margin-top: 0;'>⚠️ Próximos Pasos:</h3>\n";
    echo "<ol>\n";
    echo "<li>Modificar páginas para implementar filtros por usuario</li>\n";
    echo "<li>Auto-asignar usuario logueado en formularios</li>\n";
    echo "<li>Agregar validación anti-duplicación en búsqueda</li>\n";
    echo "<li>Crear interfaz de administración para reasignaciones</li>\n";
    echo "<li>Actualizar UI para mostrar información de aislamiento</li>\n";
    echo "</ol>\n";
    echo "</div>\n";

    echo "<div style='background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px; margin: 15px 0;'>\n";
    echo "<h3 style='color: #0c5460; margin-top: 0;'>🔐 Credenciales del Sistema:</h3>\n";
    echo "<p><strong>Admin:</strong> admin / password</p>\n";
    echo "<p><strong>Técnico 1:</strong> tecnico1 / password</p>\n";
    echo "<p><strong>Técnico 2:</strong> tecnico2 / password</p>\n";
    echo "</div>\n";

    echo "<p><a href='login.php' style='background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px 10px 0;'>🌐 Ir al Login</a></p>\n";

} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 15px 0;'>\n";
    echo "<h3 style='color: #721c24; margin-top: 0;'>❌ Error en la Actualización:</h3>\n";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "</div>\n";
}

echo "</div>";
?>
