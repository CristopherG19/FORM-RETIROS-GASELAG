<?php
/**
 * Script para probar el comportamiento del dashboard
 */

require_once 'config/database.php';

try {
    $pdo = getConnection();

    echo "🧪 TEST DEL DASHBOARD\n";
    echo "===================\n\n";

    // Simular exactamente lo que hace el dashboard
    $stmt = $pdo->query("SELECT
        COUNT(*) as total,
        SUM(CASE WHEN medidor_retirado = 'SI' THEN 1 ELSE 0 END) as retirados,
        SUM(CASE WHEN medidor_retirado = 'NO' THEN 1 ELSE 0 END) as no_retirados
        FROM retiros_medidores");
    $stats = $stmt->fetch();

    echo "📊 Estadísticas generales:\n";
    echo "   Total: {$stats['total']}\n";
    echo "   ✅ Retirados: {$stats['retirados']}\n";
    echo "   ❌ No retirados: {$stats['no_retirados']}\n\n";

    // Usar la función actualizada de casos problemáticos
    $casosProblematicos = countRetirosImposibilidadSinFoto($pdo);

    echo "🚨 Casos problemáticos (función):\n";
    echo "   🔴 Críticos: {$casosProblematicos}\n\n";

    // Verificar la consulta manualmente
    $stmt = $pdo->query("SELECT COUNT(*) as manual_count
                         FROM retiros_medidores r
                         WHERE r.medidor_retirado = 'NO'
                         AND (r.foto_imposibilidad IS NULL OR r.foto_imposibilidad = '')");
    $manual = $stmt->fetch();

    echo "🔍 Consulta manual:\n";
    echo "   🔴 Críticos (manual): {$manual['manual_count']}\n\n";

    // Mostrar detalles de cada registro
    echo "📋 Detalle de todos los registros:\n";
    echo "================================\n";

    $stmt = $pdo->query("
        SELECT
            orden_servicio,
            medidor_retirado,
            visor_imposibilidad_lectura,
            foto_imposibilidad,
            tiene_foto,
            CASE
                WHEN medidor_retirado = 'NO' AND (foto_imposibilidad IS NULL OR foto_imposibilidad = '')
                THEN '🔴 CRÍTICO'
                WHEN medidor_retirado = 'NO' AND (foto_imposibilidad IS NOT NULL AND foto_imposibilidad != '')
                THEN '🟢 OK'
                ELSE '⚪ NORMAL'
            END as estado_visual
        FROM retiros_medidores
        ORDER BY fecha_registro DESC
    ");

    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($registros as $reg) {
        echo "OC: {$reg['orden_servicio']} | ";
        echo "Retirado: {$reg['medidor_retirado']} | ";
        echo "Imposibilidad: " . ($reg['visor_imposibilidad_lectura'] ?? 'NULL') . " | ";
        echo "Foto: " . ($reg['foto_imposibilidad'] ? 'SÍ' : 'NO') . " | ";
        echo "Campo: {$reg['tiene_foto']} | ";
        echo "Estado: {$reg['estado_visual']}\n";
    }

    echo "\n✅ El dashboard debería mostrar: {$casosProblematicos} casos críticos\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
