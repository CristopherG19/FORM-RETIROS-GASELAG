<?php
require_once '../config/database.php';

// Solo administradores
requireRole(['admin']);

// Crear contenido CSV
$csv_content = "Numero_OC;Username_Tecnico;Notas_Opcionales\n";
$csv_content .= "OC-73772;12345678;Prioridad alta\n";
$csv_content .= "73773;87654321;Coordinar con cliente\n";
$csv_content .= "OC-73774;12345678;\n";
$csv_content .= "73775;12345678;Revisar medidor\n";

// Configurar headers para descarga
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="plantilla_asignaciones_' . date('Y-m-d') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// BOM UTF-8 para Excel
echo "\xEF\xBB\xBF";

// Enviar contenido
echo $csv_content;
exit;

