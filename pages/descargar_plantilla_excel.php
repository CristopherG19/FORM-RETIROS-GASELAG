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

// Escribir fila de ejemplo
$exampleData = [
    '00001', 'OC-00001', '2024-12-13', '1', 'Reclamo',
    '2025-01-06', '08:00', '2025-01-07', '10:00', 'ABC123',
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