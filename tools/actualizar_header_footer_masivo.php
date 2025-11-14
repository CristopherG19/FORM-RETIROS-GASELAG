<?php
/**
 * Script de Actualización Masiva de Header y Footer
 * Actualiza todas las páginas del sistema para usar el nuevo header/footer unificado
 */

// Colores para consola
function colorText($text, $color = 'green') {
    $colors = [
        'green' => "\033[32m",
        'red' => "\033[31m",
        'yellow' => "\033[33m",
        'blue' => "\033[34m",
        'reset' => "\033[0m"
    ];
    return $colors[$color] . $text . $colors['reset'];
}

echo "\n" . colorText("═══════════════════════════════════════════════════", 'blue') . "\n";
echo colorText("  ACTUALIZACIÓN MASIVA DE HEADER Y FOOTER", 'blue') . "\n";
echo colorText("  Sistema GASELAG - Uniformidad de Diseño", 'blue') . "\n";
echo colorText("═══════════════════════════════════════════════════", 'blue') . "\n\n";

// Páginas a actualizar con sus títulos
$paginas = [
    // Páginas en /pages/
    'pages/consultar_retiros.php' => 'Consultar Retiros - Sistema GASELAG',
    'pages/formulario_retiro.php' => 'Registrar Retiro - Sistema GASELAG',
    'pages/listar_oc.php' => 'Seleccionar OCs - Sistema GASELAG',
    'pages/importar_datos_mejorado.php' => 'Importar Datos - Sistema GASELAG',
    'pages/gestion_retiros.php' => 'Gestión de Retiros - Sistema GASELAG',
    'pages/gestion_imposibilidad.php' => 'Tipos de Imposibilidad - Sistema GASELAG',
    'pages/gestion_evidencias.php' => 'Gestión de Evidencias - Sistema GASELAG',
];

$exitosas = 0;
$fallidas = 0;
$detalles = [];

foreach ($paginas as $archivo => $titulo) {
    echo "Procesando: " . colorText($archivo, 'yellow') . "... ";
    
    if (!file_exists($archivo)) {
        echo colorText("✗ No encontrado\n", 'red');
        $fallidas++;
        $detalles[] = ['archivo' => $archivo, 'estado' => 'error', 'mensaje' => 'Archivo no encontrado'];
        continue;
    }
    
    $contenido = file_get_contents($archivo);
    $contenidoOriginal = $contenido;
    
    // Detectar si ya tiene el nuevo header
    if (strpos($contenido, "require_once '../includes/header.php'") !== false ||
        strpos($contenido, "require_once 'includes/header.php'") !== false) {
        echo colorText("✓ Ya actualizado\n", 'green');
        $exitosas++;
        $detalles[] = ['archivo' => $archivo, 'estado' => 'ya_actualizado', 'mensaje' => 'Ya tiene el nuevo header'];
        continue;
    }
    
    // Buscar el inicio del HTML
    $patronInicio = '/<!DOCTYPE\s+html>.*?<body[^>]*>/is';
    $patronFin = '/<\/body>\s*<\/html>/is';
    
    // Determinar si está en raíz o en /pages/
    $enPages = strpos($archivo, 'pages/') === 0;
    $rutaRelativa = $enPages ? '../' : '';
    
    // Preparar el nuevo header
    $nuevoHeader = "<?php\n";
    // Buscar el require de database.php y verificación de rol
    if (preg_match('/require_once\s+[\'"]([^\'\"]+database\.php)[\'\"];/i', $contenido, $matches)) {
        $nuevoHeader .= "require_once '{$rutaRelativa}config/database.php';\n";
    }
    if (preg_match('/requireRole\s*\(\s*\[[^\]]+\]\s*\);/i', $contenido, $matches)) {
        $nuevoHeader .= $matches[0] . "\n";
    }
    $nuevoHeader .= "\n\$pageTitle = '{$titulo}';\n";
    $nuevoHeader .= "require_once '{$rutaRelativa}includes/header.php';\n";
    $nuevoHeader .= "?>\n\n";
    
    // Preparar el nuevo footer
    $nuevoFooter = "\n<?php require_once '{$rutaRelativa}includes/footer.php'; ?>";
    
    // Intentar reemplazar
    $contenidoModificado = false;
    
    // Reemplazar header
    if (preg_match($patronInicio, $contenido)) {
        // Extraer todo hasta el body
        $contenido = preg_replace($patronInicio, '', $contenido, 1);
        $contenidoModificado = true;
    }
    
    // Reemplazar footer
    if (preg_match($patronFin, $contenido)) {
        $contenido = preg_replace($patronFin, '', $contenido, 1);
        $contenidoModificado = true;
    }
    
    if ($contenidoModificado) {
        // Construir el nuevo contenido
        $contenidoNuevo = $nuevoHeader . trim($contenido) . $nuevoFooter;
        
        // Guardar backup
        $backupDir = 'backups_header_footer/';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        $nombreBackup = $backupDir . basename($archivo) . '.backup.' . date('YmdHis');
        file_put_contents($nombreBackup, $contenidoOriginal);
        
        // Guardar archivo actualizado
        file_put_contents($archivo, $contenidoNuevo);
        
        echo colorText("✓ Actualizado\n", 'green');
        $exitosas++;
        $detalles[] = ['archivo' => $archivo, 'estado' => 'actualizado', 'mensaje' => 'Backup en ' . $nombreBackup];
    } else {
        echo colorText("✗ No se pudo modificar\n", 'red');
        $fallidas++;
        $detalles[] = ['archivo' => $archivo, 'estado' => 'error', 'mensaje' => 'No se encontraron patrones HTML para reemplazar'];
    }
}

// Resumen
echo "\n" . colorText("═══════════════════════════════════════════════════", 'blue') . "\n";
echo colorText("  RESUMEN DE ACTUALIZACIÓN", 'blue') . "\n";
echo colorText("═══════════════════════════════════════════════════", 'blue') . "\n";
echo colorText("✓ Exitosas: ", 'green') . $exitosas . " páginas\n";
echo colorText("✗ Fallidas: ", 'red') . $fallidas . " páginas\n";
echo colorText("━ Total procesadas: ", 'yellow') . ($exitosas + $fallidas) . " páginas\n";

if (!empty($detalles)) {
    echo "\n" . colorText("Detalles:", 'blue') . "\n";
    foreach ($detalles as $detalle) {
        $color = $detalle['estado'] === 'actualizado' || $detalle['estado'] === 'ya_actualizado' ? 'green' : 'red';
        echo "  " . colorText($detalle['archivo'], $color) . " - " . $detalle['mensaje'] . "\n";
    }
}

echo "\n" . colorText("═══════════════════════════════════════════════════", 'blue') . "\n";
echo colorText("  NOTAS IMPORTANTES", 'blue') . "\n";
echo colorText("═══════════════════════════════════════════════════", 'blue') . "\n";
echo "1. Los backups se guardaron en: " . realpath($backupDir) . "\n";
echo "2. Verifica manualmente cada página actualizada\n";
echo "3. Algunas páginas pueden requerir ajustes menores de CSS\n";
echo "4. El header y footer se adaptarán automáticamente a cada ubicación\n\n";

echo colorText("¡Actualización completada!\n\n", 'green');
?>
