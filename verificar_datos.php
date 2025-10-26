<?php
/**
 * Script para verificar los datos en la base de datos
 */

try {
    $pdo = new PDO('mysql:host=localhost;port=3307;dbname=gaselag_retiros', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "🔍 VERIFICACIÓN DE DATOS EN BASE DE DATOS\n";
    echo "==========================================\n\n";

    $stmt = $pdo->query('SELECT orden_servicio, medidor_retirado, visor_imposibilidad_lectura, foto_imposibilidad, tiene_foto FROM retiros_medidores ORDER BY fecha_registro DESC LIMIT 10');
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "📋 Últimos registros:\n";
    echo "========================================\n";
    foreach ($registros as $reg) {
        echo "OC: {$reg['orden_servicio']}\n";
        echo "  Retirado: {$reg['medidor_retirado']}\n";
        echo "  Imposibilidad: " . ($reg['visor_imposibilidad_lectura'] ?? 'NULL') . "\n";
        echo "  Foto: " . ($reg['foto_imposibilidad'] ? 'SÍ' : 'NO') . "\n";
        echo "  Tiene Foto: " . ($reg['tiene_foto'] ?? 'NULL') . "\n";
        echo "\n";
    }

    echo "📊 Resumen general:\n";
    echo "========================================\n";
    $stmt = $pdo->query("
        SELECT
            COUNT(*) as total,
            SUM(CASE WHEN medidor_retirado = 'SI' THEN 1 ELSE 0 END) as retirados,
            SUM(CASE WHEN medidor_retirado = 'NO' THEN 1 ELSE 0 END) as no_retirados,
            SUM(CASE WHEN medidor_retirado = 'NO' AND visor_imposibilidad_lectura = 'SI' THEN 1 ELSE 0 END) as imposibilidades,
            SUM(CASE WHEN foto_imposibilidad IS NOT NULL AND foto_imposibilidad != '' THEN 1 ELSE 0 END) as con_foto,
            SUM(CASE WHEN tiene_foto = 'SI' THEN 1 ELSE 0 END) as tiene_foto_si,
            SUM(CASE WHEN tiene_foto = 'NO' THEN 1 ELSE 0 END) as tiene_foto_no
        FROM retiros_medidores
    ");
    $resumen = $stmt->fetch();

    echo "Total registros: {$resumen['total']}\n";
    echo "Retirados: {$resumen['retirados']}\n";
    echo "No retirados: {$resumen['no_retirados']}\n";
    echo "Con imposibilidad: {$resumen['imposibilidades']}\n";
    echo "Con foto: {$resumen['con_foto']}\n";
    echo "Tiene foto (campo): {$resumen['tiene_foto_si']}\n";
    echo "Sin foto (campo): {$resumen['tiene_foto_no']}\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
