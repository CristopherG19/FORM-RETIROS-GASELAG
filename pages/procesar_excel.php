<?php
/**
 * Procesador de archivos Excel para importación
 * GASELAG - Sistema de Retiros de Medidores
 */

require_once '../config/database.php';

// Verificar autenticación y rol de administrador
requireRole(['admin']);

function processExcelFile($filePath) {
    $pdo = getConnection();
    $success = 0;
    $errors = 0;
    $errorDetails = [];
    
    try {
        // Leer archivo CSV con punto y coma como delimitador
        $rows = [];
        if (($handle = fopen($filePath, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
                $rows[] = $data;
            }
            fclose($handle);
        }
        
        // Verificar que hay datos
        if (empty($rows)) {
            return [
                'success' => false,
                'error' => 'El archivo CSV está vacío o no se pudo leer',
                'success_count' => 0,
                'error_count' => 0,
                'error_details' => []
            ];
        }
        
        // Saltar la primera fila (encabezados) si existe
        $headerRow = array_shift($rows);
        $expectedColumns = 33;
        $actualColumns = count($headerRow);
        
        // Log para depuración
        error_log("CSV Debug - Columnas esperadas: $expectedColumns, Columnas encontradas: $actualColumns");
        
        $pdo->beginTransaction();
        
        foreach ($rows as $rowNumber => $row) {
            // Saltar filas vacías
            if (empty(array_filter($row))) continue;
            
            // Validar que tenga suficientes columnas (mínimo 5 para campos básicos)
            if (count($row) < 5) {
                $errors++;
                $errorDetails[] = "Fila " . ($rowNumber + 2) . ": Insuficientes columnas (" . count($row) . "/5 mínimo)";
                continue;
            }
            
            // Rellenar con columnas vacías si faltan
            while (count($row) < 33) {
                $row[] = '';
            }
            
            // Limpiar y validar datos
            $data = array_map('trim', $row);
            
            // Validar campos obligatorios
            if (empty($data[0]) || empty($data[1]) || empty($data[10]) || empty($data[17]) || empty($data[18])) {
                $errors++;
                $errorDetails[] = "Fila " . ($rowNumber + 2) . ": Campos obligatorios faltantes";
                continue;
            }
            
            // Validar formato de fecha
            if (!empty($data[2]) && !validateDate($data[2])) {
                $errors++;
                $errorDetails[] = "Fila " . ($rowNumber + 2) . ": Formato de fecha inválido en Fecha OS";
                continue;
            }
            
            // Preparar la consulta
            $sql = "INSERT INTO ordenes_servicio (
                item, orden_servicio, fecha_os, cantidad_medidores, tipo_servicio,
                programacion_dia_retiro, programacion_hora_retiro, programacion_dia_vp, 
                programacion_hora_vp, codigo_seguridad, cliente, centro_servicio, remesa,
                usuario_reclamante, direccion, cus, cup, num_suministro, num_serie_medidor,
                marca_medidor, modelo_medidor, anio_fabricacion, fabricante, procedencia,
                tipo_medidor, diametro_nominal, q3, alcance, pma, tma, clase_sensibilidad,
                certificado_aprobacion, num_certificado
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                item = VALUES(item),
                fecha_os = VALUES(fecha_os),
                cantidad_medidores = VALUES(cantidad_medidores),
                tipo_servicio = VALUES(tipo_servicio),
                updated_at = CURRENT_TIMESTAMP";
            
            $stmt = $pdo->prepare($sql);
            
            // Mapear datos a parámetros
            $params = [
                $data[0],  // item
                $data[1],  // orden_servicio
                !empty($data[2]) ? $data[2] : null,  // fecha_os
                !empty($data[3]) ? intval($data[3]) : null,  // cantidad_medidores
                $data[4],  // tipo_servicio
                !empty($data[5]) ? $data[5] : null,  // programacion_dia_retiro
                $data[6],  // programacion_hora_retiro
                !empty($data[7]) ? $data[7] : null,  // programacion_dia_vp
                $data[8],  // programacion_hora_vp
                $data[9],  // codigo_seguridad
                $data[10], // cliente
                $data[11], // centro_servicio
                $data[12], // remesa
                $data[13], // usuario_reclamante
                $data[14], // direccion
                $data[15], // cus
                $data[16], // cup
                $data[17], // num_suministro
                $data[18], // num_serie_medidor
                $data[19], // marca_medidor
                $data[20], // modelo_medidor
                !empty($data[21]) ? intval($data[21]) : null,  // anio_fabricacion
                $data[22], // fabricante
                $data[23], // procedencia
                $data[24], // tipo_medidor
                !empty($data[25]) ? intval($data[25]) : null,  // diametro_nominal
                !empty($data[26]) ? floatval($data[26]) : null,  // q3
                $data[27], // alcance
                !empty($data[28]) ? intval($data[28]) : null,  // pma
                !empty($data[29]) ? intval($data[29]) : null,  // tma
                $data[30], // clase_sensibilidad
                $data[31], // certificado_aprobacion
                $data[32]  // num_certificado
            ];
            
            if ($stmt->execute($params)) {
                $success++;
            } else {
                $errors++;
                $errorDetails[] = "Fila " . ($rowNumber + 2) . ": Error al insertar en base de datos";
            }
        }
        
        $pdo->commit();
        
        // Información adicional para depuración
        $debugInfo = [
            'total_rows_processed' => count($rows),
            'expected_columns' => $expectedColumns,
            'actual_columns_in_header' => $actualColumns
        ];
        
        return [
            'success' => true,
            'success_count' => $success,
            'error_count' => $errors,
            'error_details' => $errorDetails,
            'debug_info' => $debugInfo
        ];
        
    } catch (Exception $e) {
        $pdo->rollback();
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'success_count' => $success,
            'error_count' => $errors,
            'error_details' => $errorDetails
        ];
    }
}

function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

function parseSimpleExcel($content) {
    $rows = [];
    $pos = 0;
    
    // Buscar registros LABEL (0x0004)
    while ($pos < strlen($content) - 6) {
        $recordType = unpack('v', substr($content, $pos, 2))[1];
        $recordLength = unpack('v', substr($content, $pos + 2, 2))[1];
        
        if ($recordType == 0x0004) { // LABEL record
            $row = unpack('v', substr($content, $pos + 4, 2))[1];
            $col = unpack('v', substr($content, $pos + 6, 2))[1];
            $data = substr($content, $pos + 10, $recordLength);
            
            if (!isset($rows[$row])) {
                $rows[$row] = [];
            }
            $rows[$row][$col] = $data;
        }
        
        $pos += 4 + $recordLength;
    }
    
    // Convertir a array indexado
    $result = [];
    foreach ($rows as $rowIndex => $row) {
        $resultRow = [];
        for ($i = 0; $i < 33; $i++) {
            $resultRow[] = isset($row[$i]) ? $row[$i] : '';
        }
        $result[] = $resultRow;
    }
    
    return $result;
}

// Procesar archivo subido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file'];
    
    // Validar archivo
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode([
            'success' => false,
            'error' => 'Error al subir el archivo'
        ]);
        exit;
    }
    
    // Validar tipo de archivo (solo CSV)
    if (!preg_match('/\.csv$/i', $file['name'])) {
        echo json_encode([
            'success' => false,
            'error' => 'Tipo de archivo no válido. Use solo archivos CSV (.csv)'
        ]);
        exit;
    }
    
    // Validar tamaño (máximo 10MB)
    if ($file['size'] > 10 * 1024 * 1024) {
        echo json_encode([
            'success' => false,
            'error' => 'El archivo es demasiado grande. Máximo 10MB'
        ]);
        exit;
    }
    
    // Procesar archivo
    $result = processExcelFile($file['tmp_name']);
    
    // Registrar en auditoría
    if ($result['success']) {
        logUserAction('importacion_excel', 
            "Importación exitosa: {$result['success_count']} registros, {$result['error_count']} errores");
    } else {
        logUserAction('importacion_excel_error', 
            "Error en importación: " . $result['error']);
    }
    
    echo json_encode($result);
    exit;
}

// Si no es POST, devolver error
echo json_encode([
    'success' => false,
    'error' => 'Método no permitido'
]);
?>
