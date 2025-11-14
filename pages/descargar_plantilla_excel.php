<?php
/**
 * Generador de plantilla CSV optimizada
 * GASELAG - Sistema de Retiros de Medidores
 * Solución simple y confiable que siempre funciona
 */

require_once '../config/database.php';

// Verificar autenticación y rol de administrador
requireRole(['admin']);

// Configurar headers para descarga de CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="plantilla_importacion_gaselag.csv"');
header('Cache-Control: max-age=0');

// BOM para UTF-8 (importante para Excel)
echo "\xEF\xBB\xBF";

// Escribir instrucciones (comentario en CSV)
echo "# INSTRUCCIONES:\n";
echo "# - Formato de fechas: DD/MM/YYYY o D/M/YYYY (ejemplo: 13/12/2024 o 6/01/2025)\n";
echo "# - Formato de horas: HH.MM con punto decimal (ejemplo: 10.3 = 10:30, 8.15 = 08:15)\n";
echo "# - Separador: punto y coma (;)\n";
echo "# - Codificación: UTF-8\n";
echo "# - NO BORRAR la fila de encabezados\n";
echo "# - La segunda fila contiene un ejemplo de referencia\n";
echo "#\n";

// Encabezados de columnas
$headers = [
    'Item', 'Orden de servicio', 'Fecha OS', 'Cantidad de medidores', 'Tipo de Servicio',
    'Programación Dia Retiro', 'Programación Hora Retiro', 'Programación dia VP', 
    'Programación Hora VP', 'CODIGO SEGURIDAD', 'Cliente', 'Centro de Servicio', 'Remesa',
    'Usuario - Reclamante', 'Dirección', 'CUS', 'CUP', 'N° de Suministro', 'N° de Serie del Medidor',
    'Marca del medidor', 'Modelo del medidor', 'Año de Fabricacion', 'Fabricante', 'Procedencia',
    'Tipo Medidor', 'Diámetro Nominal (mm)', 'Q3 (m3/h)', 'Alcance', 'PMA (bar)', 'TMA (°C)',
    'Clase de sensibilidad', 'Certificado de aprobación', 'N° de Certificado'
];

// Función para escapar CSV con punto y coma
function escapeCsv($value) {
    if (strpos($value, ';') !== false || strpos($value, '"') !== false || strpos($value, "\n") !== false) {
        return '"' . str_replace('"', '""', $value) . '"';
    }
    return $value;
}

// Escribir encabezados
echo implode(';', array_map('escapeCsv', $headers)) . "\n";

// Escribir fila de ejemplo (Fechas: D/M/YYYY, Horas: HH.MM con punto)
$exampleData = [
    '00001', 'OC-00001', '13/12/2024', '1', 'Reclamo',
    '6/01/2025', '10.3', '7/01/2025', '10.3', 'ABC123',
    'Cliente Ejemplo', 'Centro 001', 'REM001', 'Juan Pérez',
    'Calle Principal 123', 'CUS001', 'CUP001', '5367165',
    'EA22282911', 'Marca Ejemplo', 'Modelo 001', '2023',
    'Fabricante Ejemplo', 'Nacional', 'Residencial', '15',
    '2.5', 'R160', '10', '50', '1.5', 'Cert001', 'CERT001'
];

echo implode(';', array_map('escapeCsv', $exampleData)) . "\n";

// Escribir filas vacías para que el usuario complete
for ($i = 1; $i <= 10; $i++) {
    $emptyRow = array_fill(0, 33, '');
    echo implode(';', array_map('escapeCsv', $emptyRow)) . "\n";
}

exit;
?>