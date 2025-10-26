<?php
/**
 * Script para verificar casos problemáticos con la nueva lógica
 */

try {
    require_once 'config/database.php';
    $pdo = getConnection();

    echo "🔍 VERIFICACIÓN DE CASOS PROBLEMÁTICOS\n";
    echo "======================================\n\n";

    // Usar la función actualizada
    $casos = countRetirosImposibilidadSinFoto($pdo);
    echo "📊 Casos problemáticos detectados: {$casos}\n\n";

    $detalles = getRetirosImposibilidadSinFoto($pdo);
    echo "📋 Detalles de casos problemáticos:\n";
    echo "===================================\n";

    if (empty($detalles)) {
        echo "✅ No hay casos problemáticos\n";
    } else {
        foreach ($detalles as $caso) {
            echo "OC: {$caso['orden_servicio']}\n";
            echo "  Cliente: {$caso['cliente']}\n";
            echo "  Retirado: {$caso['medidor_retirado']}\n";
            echo "  Imposibilidad: " . ($caso['visor_imposibilidad_lectura'] ?? 'NO ESPECIFICADA') . "\n";
            echo "  Foto: " . ($caso['foto_imposibilidad'] ? 'SÍ' : 'NO') . "\n";
            echo "  Tipo: {$caso['tipo_caso']}\n";
            echo "\n";
        }
    }

    echo "🎯 RESUMEN:\n";
    echo "==========\n";
    $stmt = $pdo->query("
        SELECT
            COUNT(*) as total,
            SUM(CASE WHEN medidor_retirado = 'SI' THEN 1 ELSE 0 END) as retirados,
            SUM(CASE WHEN medidor_retirado = 'NO' THEN 1 ELSE 0 END) as no_retirados,
            SUM(CASE WHEN medidor_retirado = 'NO' AND (foto_imposibilidad IS NULL OR foto_imposibilidad = '') THEN 1 ELSE 0 END) as criticos,
            SUM(CASE WHEN medidor_retirado = 'NO' AND (foto_imposibilidad IS NOT NULL AND foto_imposibilidad != '') THEN 1 ELSE 0 END) as con_foto
        FROM retiros_medidores
    ");
    $resumen = $stmt->fetch();

    echo "Total registros: {$resumen['total']}\n";
    echo "✅ Medidores retirados: {$resumen['retirados']}\n";
    echo "❌ No retirados: {$resumen['no_retirados']}\n";
    echo "   🔴 Críticos (sin foto): {$resumen['criticos']}\n";
    echo "   🟢 Con foto: {$resumen['con_foto']}\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
