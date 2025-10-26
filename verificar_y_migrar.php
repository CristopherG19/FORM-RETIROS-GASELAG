<?php
/**
 * Script para verificar y aplicar migración de la base de datos
 * Ejecutar: php verificar_y_migrar.php
 */

echo "🔍 VERIFICACIÓN Y MIGRACIÓN DE BASE DE DATOS\n";
echo "============================================\n\n";

try {
    // Conectar a la base de datos
    $pdo = new PDO('mysql:host=localhost;port=3307;dbname=gaselag_retiros', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Conexión exitosa a la base de datos\n\n";

    // Verificar columnas actuales
    $stmt = $pdo->query('DESCRIBE retiros_medidores');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "📋 Estructura actual de la tabla retiros_medidores:\n";
    foreach ($columns as $col) {
        echo "   - {$col['Field']} ({$col['Type']})\n";
    }

    // Verificar si tiene_foto existe
    $hasTieneFoto = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'tiene_foto') {
            $hasTieneFoto = true;
            break;
        }
    }

    if (!$hasTieneFoto) {
        echo "\n⚠️  La columna 'tiene_foto' no existe. Aplicando migración...\n\n";

        // Agregar la columna
        echo "📝 Agregando columna 'tiene_foto'...\n";
        $pdo->exec('ALTER TABLE retiros_medidores ADD COLUMN tiene_foto ENUM("SI", "NO") NOT NULL DEFAULT "NO" AFTER foto_imposibilidad');
        echo "✅ Columna agregada exitosamente\n";

        // Actualizar registros existentes
        echo "📝 Actualizando registros existentes...\n";
        $pdo->exec('UPDATE retiros_medidores SET tiene_foto = "SI" WHERE foto_imposibilidad IS NOT NULL AND foto_imposibilidad != ""');
        echo "✅ Registros actualizados exitosamente\n";

        // Verificar estadísticas finales
        $stmt = $pdo->query('SELECT COUNT(*) as total, SUM(CASE WHEN tiene_foto = "SI" THEN 1 ELSE 0 END) as con_foto, SUM(CASE WHEN tiene_foto = "NO" THEN 1 ELSE 0 END) as sin_foto FROM retiros_medidores');
        $stats = $stmt->fetch();

        echo "\n📊 RESULTADO DE LA MIGRACIÓN:\n";
        echo "   Total registros: {$stats['total']}\n";
        echo "   Con foto: {$stats['con_foto']}\n";
        echo "   Sin foto: {$stats['sin_foto']}\n";

        echo "\n🎉 ¡MIGRACIÓN COMPLETADA!\n";
        echo "💡 El sistema ya puede funcionar con la nueva estructura.\n";

    } else {
        echo "\n✅ La columna 'tiene_foto' ya existe.\n";
        echo "💡 El sistema ya está actualizado.\n";

        // Mostrar estadísticas actuales
        $stmt = $pdo->query('SELECT COUNT(*) as total, SUM(CASE WHEN tiene_foto = "SI" THEN 1 ELSE 0 END) as con_foto, SUM(CASE WHEN tiene_foto = "NO" THEN 1 ELSE 0 END) as sin_foto FROM retiros_medidores');
        $stats = $stmt->fetch();

        echo "\n📊 Estadísticas actuales:\n";
        echo "   Total registros: {$stats['total']}\n";
        echo "   Con foto: {$stats['con_foto']}\n";
        echo "   Sin foto: {$stats['sin_foto']}\n";
    }

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n\n";
    echo "💡 POSIBLES SOLUCIONES:\n";
    echo "   1. Verificar que el servidor MySQL esté ejecutándose en el puerto 3307\n";
    echo "   2. Verificar que la base de datos 'gaselag_retiros' exista\n";
    echo "   3. Verificar las credenciales de conexión (usuario: root, sin contraseña)\n";
    echo "   4. Si usas XAMPP, asegurate de que MySQL esté iniciado\n\n";
    echo "🔧 Para aplicar la migración manualmente, ejecuta los comandos SQL en:\n";
    echo "   database/migracion_directa.sql\n";
}

echo "\n============================================\n";
echo "✅ PROCESO FINALIZADO\n";
?>
