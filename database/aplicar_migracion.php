<?php
/**
 * Script para aplicar la migración del campo tiene_foto
 * Ejecutar: php database/aplicar_migracion.php
 */

try {
    $pdo = new PDO('mysql:host=localhost;port=3307;dbname=gaselag_retiros', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "🔍 Verificando estructura de la base de datos...\n\n";

    // Verificar si la columna existe
    $stmt = $pdo->query('DESCRIBE retiros_medidores');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "📋 Columnas actuales en retiros_medidores:\n";
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
        echo "\n⚡ Aplicando migración...\n";

        // Agregar la columna
        $pdo->exec('ALTER TABLE retiros_medidores ADD COLUMN tiene_foto ENUM("SI", "NO") NOT NULL DEFAULT "NO" AFTER foto_imposibilidad');
        echo "✅ Columna 'tiene_foto' agregada\n";

        // Actualizar registros existentes
        $pdo->exec('UPDATE retiros_medidores SET tiene_foto = "SI" WHERE foto_imposibilidad IS NOT NULL AND foto_imposibilidad != ""');
        echo "✅ Registros existentes actualizados\n";

        // Verificar estadísticas finales
        $stmt = $pdo->query('SELECT COUNT(*) as total, SUM(CASE WHEN tiene_foto = "SI" THEN 1 ELSE 0 END) as con_foto, SUM(CASE WHEN tiene_foto = "NO" THEN 1 ELSE 0 END) as sin_foto FROM retiros_medidores');
        $stats = $stmt->fetch();

        echo "\n📊 Estadísticas finales:\n";
        echo "   Total registros: {$stats['total']}\n";
        echo "   Con foto: {$stats['con_foto']}\n";
        echo "   Sin foto: {$stats['sin_foto']}\n";

        echo "\n🎉 ¡Migración completada exitosamente!\n";
        echo "💡 Ahora puedes usar el sistema normalmente.\n";

    } else {
        echo "\n✅ La columna 'tiene_foto' ya existe.\n";
        echo "💡 El sistema ya está actualizado.\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "💡 Verifica que:\n";
    echo "   - La base de datos 'gaselag_retiros' existe\n";
    echo "   - Las credenciales de conexión son correctas\n";
    echo "   - El servidor MySQL está ejecutándose en el puerto 3307\n";
}
?>
