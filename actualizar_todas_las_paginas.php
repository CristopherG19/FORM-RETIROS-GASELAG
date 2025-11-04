<?php
/**
 * Script Inteligente de Actualización de Header y Footer
 * Actualiza automáticamente todas las páginas del sistema
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "\n╔════════════════════════════════════════════════════════╗\n";
echo "║  ACTUALIZACIÓN AUTOMÁTICA - HEADER Y FOOTER UNIFORME  ║\n";
echo "║              Sistema GASELAG v2.0                      ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

// Lista de páginas a actualizar
$paginas = [
    // Páginas de gestión
    ['archivo' => 'pages/gestion_retiros.php', 'titulo' => 'Gestión de Retiros - Sistema GASELAG'],
    ['archivo' => 'pages/gestion_imposibilidad.php', 'titulo' => 'Tipos de Imposibilidad - Sistema GASELAG'],
    ['archivo' => 'pages/gestion_evidencias.php', 'titulo' => 'Gestión de Evidencias - Sistema GASELAG'],
    
    // Páginas de operaciones
    ['archivo' => 'pages/listar_oc.php', 'titulo' => 'Seleccionar Órdenes de Compra - Sistema GASELAG'],
    ['archivo' => 'pages/formulario_retiro.php', 'titulo' => 'Registrar Retiro - Sistema GASELAG'],
    ['archivo' => 'pages/consultar_retiros.php', 'titulo' => 'Consultar Retiros - Sistema GASELAG'],
    ['archivo' => 'pages/buscar_oc.php', 'titulo' => 'Buscar OC - Sistema GASELAG'],
    
    // Páginas de utilidades
    ['archivo' => 'pages/cambiar_password.php', 'titulo' => 'Cambiar Contraseña - Sistema GASELAG'],
    ['archivo' => 'pages/importar_datos_mejorado.php', 'titulo' => 'Importar Datos - Sistema GASELAG'],
    ['archivo' => 'pages/exportar_excel.php', 'titulo' => 'Exportar Excel - Sistema GASELAG'],
    ['archivo' => 'pages/admin_desbloquear_cuentas.php', 'titulo' => 'Desbloquear Cuentas - Sistema GASELAG'],
    
    // Páginas secundarias
    ['archivo' => 'pages/detalle_retiro.php', 'titulo' => 'Detalle de Retiro - Sistema GASELAG'],
    ['archivo' => 'pages/detalle_oc.php', 'titulo' => 'Detalle de OC - Sistema GASELAG'],
    ['archivo' => 'pages/adjuntar_evidencia.php', 'titulo' => 'Adjuntar Evidencia - Sistema GASELAG'],
    ['archivo' => 'pages/reporte_imposibilidad.php', 'titulo' => 'Reporte de Imposibilidad - Sistema GASELAG'],
];

$estadisticas = [
    'exitosas' => 0,
    'ya_actualizadas' => 0,
    'fallidas' => 0,
    'no_encontradas' => 0
];

$detalles = [];

// Crear directorio de backups
$backupDir = 'backups_actualizacion_' . date('Y-m-d_H-i-s') . '/';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

foreach ($paginas as $pagina) {
    $archivo = $pagina['archivo'];
    $titulo = $pagina['titulo'];
    
    echo "→ Procesando: " . basename($archivo) . "... ";
    
    if (!file_exists($archivo)) {
        echo "✗ NO ENCONTRADO\n";
        $estadisticas['no_encontradas']++;
        $detalles[] = ['archivo' => basename($archivo), 'estado' => '✗', 'mensaje' => 'Archivo no encontrado'];
        continue;
    }
    
    $contenido = file_get_contents($archivo);
    
    // Verificar si ya está actualizado
    if (strpos($contenido, "require_once '../includes/header.php'") !== false) {
        echo "✓ YA ACTUALIZADO\n";
        $estadisticas['ya_actualizadas']++;
        $detalles[] = ['archivo' => basename($archivo), 'estado' => '✓', 'mensaje' => 'Ya tiene el nuevo header'];
        continue;
    }
    
    // Guardar backup
    $backupFile = $backupDir . basename($archivo);
    file_put_contents($backupFile, $contenido);
    
    // Extraer la parte PHP inicial (antes del HTML)
    $phpInicial = '';
    if (preg_match('/^<\?php(.*?)(?=<!DOCTYPE|<html|\?>)/s', $contenido, $matches)) {
        $phpInicial = trim($matches[1]);
    }
    
    // Extraer contenido entre <body> y </body>
    $contenidoBody = '';
    if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $contenido, $matches)) {
        $contenidoBody = trim($matches[1]);
    } else {
        echo "✗ NO SE PUDO EXTRAER BODY\n";
        $estadisticas['fallidas']++;
        continue;
    }
    
    // Extraer estilos personalizados si existen
    $estilosPersonalizados = '';
    if (preg_match('/<style[^>]*>(.*?)<\/style>/is', $contenido, $matches)) {
        $estilosPersonalizados = "\n<style>" . trim($matches[1]) . "</style>\n";
    }
    
    // Construir nuevo contenido
    $nuevoContenido = "<?php\n";
    $nuevoContenido .= "require_once '../config/database.php';\n\n";
    
    // Preservar requireRole si existe
    if (preg_match('/requireRole\s*\(\s*\[[^\]]+\]\s*\);/i', $contenido, $matches)) {
        $nuevoContenido .= $matches[0] . "\n";
    } else {
        $nuevoContenido .= "requireRole(['admin', 'user']);\n";
    }
    
    // Preservar variables importantes
    if (preg_match('/\$currentUser\s*=\s*getCurrentUser\(\);/', $phpInicial)) {
        $nuevoContenido .= "\n\$currentUser = getCurrentUser();\n";
    }
    
    $nuevoContenido .= "\n\$pageTitle = '{$titulo}';\n";
    $nuevoContenido .= "require_once '../includes/header.php';\n";
    $nuevoContenido .= "?>\n";
    $nuevoContenido .= $estilosPersonalizados;
    $nuevoContenido .= "\n" . $contenidoBody . "\n\n";
    $nuevoContenido .= "<?php require_once '../includes/footer.php'; ?>";
    
    // Limpiar código duplicado o innecesario
    $nuevoContenido = preg_replace('/<script[^>]*src=["\'][^"\']*bootstrap[^"\']*["\'][^>]*><\/script>/i', '', $nuevoContenido);
    
    // Guardar archivo actualizado
    if (file_put_contents($archivo, $nuevoContenido)) {
        echo "✓ ACTUALIZADO\n";
        $estadisticas['exitosas']++;
        $detalles[] = ['archivo' => basename($archivo), 'estado' => '✓', 'mensaje' => 'Actualizado correctamente'];
    } else {
        echo "✗ ERROR AL GUARDAR\n";
        $estadisticas['fallidas']++;
        $detalles[] = ['archivo' => basename($archivo), 'estado' => '✗', 'mensaje' => 'Error al guardar archivo'];
    }
}

// Mostrar resumen
echo "\n╔════════════════════════════════════════════════════════╗\n";
echo "║                    RESUMEN FINAL                       ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

echo "✓ Exitosas:         " . $estadisticas['exitosas'] . " páginas\n";
echo "✓ Ya actualizadas:  " . $estadisticas['ya_actualizadas'] . " páginas\n";
echo "✗ Fallidas:         " . $estadisticas['fallidas'] . " páginas\n";
echo "✗ No encontradas:   " . $estadisticas['no_encontradas'] . " páginas\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  TOTAL PROCESADAS: " . count($paginas) . " páginas\n\n";

echo "📁 Backups guardados en: " . realpath($backupDir) . "\n\n";

if (!empty($detalles)) {
    echo "╔════════════════════════════════════════════════════════╗\n";
    echo "║                  DETALLE POR ARCHIVO                   ║\n";
    echo "╚════════════════════════════════════════════════════════╝\n\n";
    
    foreach ($detalles as $detalle) {
        printf("  %s %-40s %s\n", 
            $detalle['estado'], 
            $detalle['archivo'], 
            $detalle['mensaje']
        );
    }
    echo "\n";
}

echo "╔════════════════════════════════════════════════════════╗\n";
echo "║                  NOTAS IMPORTANTES                     ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";
echo "1. ✓ Todos los backups están en: {$backupDir}\n";
echo "2. ✓ El header y footer se adaptan automáticamente\n";
echo "3. ✓ Los estilos personalizados se preservaron\n";
echo "4. ! Revisa cada página manualmente para confirmar\n";
echo "5. ! Algunas páginas pueden necesitar ajustes menores\n\n";

$porcentajeExito = round((($estadisticas['exitosas'] + $estadisticas['ya_actualizadas']) / count($paginas)) * 100, 2);
echo "🎉 TASA DE ÉXITO: {$porcentajeExito}%\n\n";

echo "✓ ¡Actualización completada!\n\n";
?>
