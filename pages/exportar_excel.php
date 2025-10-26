<?php
require_once '../config/database.php';

// Verificar autenticación y rol de administrador
requireRole(['admin']);

// Filtros (igual que en consultar_retiros.php)
$filtro_oc = isset($_GET['filtro_oc']) ? trim($_GET['filtro_oc']) : '';
$filtro_fecha_desde = isset($_GET['filtro_fecha_desde']) ? $_GET['filtro_fecha_desde'] : '';
$filtro_fecha_hasta = isset($_GET['filtro_fecha_hasta']) ? $_GET['filtro_fecha_hasta'] : '';
$filtro_retirado = isset($_GET['filtro_retirado']) ? $_GET['filtro_retirado'] : '';

try {
    $pdo = getConnection();
    
    $sql = "SELECT 
                r.*,
                o.cliente,
                o.usuario_reclamante,
                o.direccion,
                o.num_serie_medidor,
                o.marca_medidor,
                o.modelo_medidor,
                o.centro_servicio,
                o.remesa,
                o.programacion_dia_retiro
            FROM retiros_medidores r
            INNER JOIN ordenes_servicio o ON r.orden_servicio_id = o.id
            WHERE 1=1";
    
    $params = [];
    
    if (!empty($filtro_oc)) {
        $sql .= " AND r.orden_servicio LIKE ?";
        $params[] = "%$filtro_oc%";
    }
    
    if (!empty($filtro_fecha_desde)) {
        $sql .= " AND DATE(o.programacion_dia_retiro) >= ?";
        $params[] = $filtro_fecha_desde;
    }
    
    if (!empty($filtro_fecha_hasta)) {
        $sql .= " AND DATE(o.programacion_dia_retiro) <= ?";
        $params[] = $filtro_fecha_hasta;
    }
    
    if (!empty($filtro_retirado)) {
        $sql .= " AND r.medidor_retirado = ?";
        $params[] = $filtro_retirado;
    }
    
    $sql .= " ORDER BY o.programacion_dia_retiro DESC, r.fecha_registro DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $retiros = $stmt->fetchAll();
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// Configurar headers para descarga de CSV (compatible con Excel)
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="retiros_medidores_' . date('Y-m-d_His') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// Agregar BOM UTF-8 para que Excel reconozca correctamente los caracteres especiales
echo "\xEF\xBB\xBF";

// Abrir el output como archivo
$output = fopen('php://output', 'w');

// Escribir encabezados (usando coma como delimitador estándar)
fputcsv($output, [
    'Fecha Retiro Programada',
    'Fecha Registro Sistema',
    'Orden Servicio',
    'Cliente',
    'Usuario Reclamante',
    'Direccion',
    'Centro Servicio',
    'Remesa',
    'Nro Serie Medidor',
    'Marca',
    'Modelo',
    'Medidor Retirado',
    'Lectura m3',
    'Puntero Girando',
    'Con Precinto',
    'Visor Imposibilidad',
    'Tiene Filtro',
    'Filtro Buen Estado',
    'Solidos Retenidos',
    'Info Caja y Medidor',
    'Observacion',
    'Tecnico Responsable',
    'Tiene Foto'
]); // Usar coma como delimitador estándar

// Escribir datos
foreach ($retiros as $retiro) {
    fputcsv($output, [
        !empty($retiro['programacion_dia_retiro']) ? date('d/m/Y', strtotime($retiro['programacion_dia_retiro'])) : 'N/A',
        date('d/m/Y H:i', strtotime($retiro['fecha_registro'])),
        $retiro['orden_servicio'],
        $retiro['cliente'],
        $retiro['usuario_reclamante'],
        $retiro['direccion'],
        $retiro['centro_servicio'],
        $retiro['remesa'],
        $retiro['num_serie_medidor'],
        $retiro['marca_medidor'],
        $retiro['modelo_medidor'],
        $retiro['medidor_retirado'],
        $retiro['lectura_m3'] ?? '',
        $retiro['puntero_girando'] ?? '',
        $retiro['medidor_con_precinto'] ?? '',
        $retiro['visor_imposibilidad_lectura'] ?? '',
        $retiro['medidor_tiene_filtro'] ?? '',
        $retiro['filtro_buen_estado'] ?? '',
        $retiro['solidos_retenidos_filtro'] ?? '',
        $retiro['info_caja_medidor'] ?? '',
        $retiro['observacion'],
        $retiro['tecnico_responsable'],
        (!empty($retiro['foto_imposibilidad'])) ? 'SÍ' : 'NO'
    ]); // Usar coma como delimitador estándar
}

fclose($output);
exit;
?>
