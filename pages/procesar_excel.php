<?php
/**
 * Procesador de archivos Excel para importación
 * GASELAG - Sistema de Retiros de Medidores
 */

require_once '../config/database.php';

// Verificar autenticación y rol de administrador
requireRole(['admin']);

function processExcelFile($filePath, $previewMode = false, $skipDuplicates = false) {
    $pdo = getConnection();
    $success = 0;
    $errors = 0;
    $errorDetails = [];
    $newInserts = 0;
    $updates = 0;
    $duplicatesWithChanges = [];
    $duplicatesFound = [];
    $skippedDuplicates = 0;
    
    try {
        // Leer archivo CSV con punto y coma como delimitador
        $rows = [];
        if (($handle = fopen($filePath, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
                // Ignorar líneas de comentario (que empiezan con #)
                if (!empty($data[0]) && substr(trim($data[0]), 0, 1) === '#') {
                    continue;
                }
                // Ignorar líneas completamente vacías
                if (empty(array_filter($data))) {
                    continue;
                }
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
            
            // Validar campos obligatorios con mensajes específicos
            $camposFaltantes = [];
            if (empty($data[0])) $camposFaltantes[] = "'Item'";
            if (empty($data[1])) $camposFaltantes[] = "'Orden de servicio'";
            if (empty($data[10])) $camposFaltantes[] = "'Cliente'";
            if (empty($data[17])) $camposFaltantes[] = "'N° de Suministro'";
            if (empty($data[18])) $camposFaltantes[] = "'N° de Serie del Medidor'";
            
            if (!empty($camposFaltantes)) {
                $errors++;
                $errorDetails[] = "Fila " . ($rowNumber + 2) . ": Campos obligatorios faltantes: " . implode(', ', $camposFaltantes);
                continue;
            }
            
            // Validar y convertir fechas (acepta DD/MM/YYYY)
            $fecha_os_converted = convertExcelDateToMySQL($data[2]);
            if (!empty($data[2]) && $fecha_os_converted === null) {
                $errors++;
                $errorDetails[] = "Fila " . ($rowNumber + 2) . ": Formato de fecha inválido en 'Fecha OS' (use DD/MM/YYYY, ejemplo: 13/12/2024)";
                continue;
            }
            
            // ========== DETECTAR SI LA OC YA EXISTE ==========
            $checkStmt = $pdo->prepare("SELECT * FROM ordenes_servicio WHERE orden_servicio = ? LIMIT 1");
            $checkStmt->execute([trim($data[1])]);
            $existingOC = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            $isUpdate = ($existingOC !== false);
            
            // Si es modo preview o skipDuplicates, registrar duplicados y continuar
            if ($isUpdate) {
                // Construir params temporales para detectar cambios
                $tempParams = [
                    $data[0],  // item
                    $data[1],  // orden_servicio
                    convertExcelDateToMySQL($data[2]),  // fecha_os
                    !empty($data[3]) ? intval($data[3]) : null,  // cantidad_medidores
                    $data[4],  // tipo_servicio
                    convertExcelDateToMySQL($data[5]),  // programacion_dia_retiro
                    convertDecimalTimeToStandard($data[6]),  // programacion_hora_retiro
                    convertExcelDateToMySQL($data[7]),  // programacion_dia_vp
                    convertDecimalTimeToStandard($data[8]),  // programacion_hora_vp
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
                
                $changes = detectChanges($existingOC, $tempParams, $data);
                
                $duplicatesFound[] = [
                    'orden_servicio' => trim($data[1]),
                    'cliente' => trim($data[10]),
                    'changes' => $changes,
                    'change_count' => count($changes),
                    'has_changes' => !empty($changes)
                ];
                
                if ($previewMode) {
                    // En modo preview, solo detectar, no guardar
                    continue;
                }
                
                if ($skipDuplicates) {
                    // Si se configuró para ignorar duplicados, saltarlos
                    $skippedDuplicates++;
                    continue;
                }
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
                programacion_dia_retiro = VALUES(programacion_dia_retiro),
                programacion_hora_retiro = VALUES(programacion_hora_retiro),
                programacion_dia_vp = VALUES(programacion_dia_vp),
                programacion_hora_vp = VALUES(programacion_hora_vp),
                codigo_seguridad = VALUES(codigo_seguridad),
                cliente = VALUES(cliente),
                centro_servicio = VALUES(centro_servicio),
                remesa = VALUES(remesa),
                usuario_reclamante = VALUES(usuario_reclamante),
                direccion = VALUES(direccion),
                cus = VALUES(cus),
                cup = VALUES(cup),
                num_suministro = VALUES(num_suministro),
                num_serie_medidor = VALUES(num_serie_medidor),
                marca_medidor = VALUES(marca_medidor),
                modelo_medidor = VALUES(modelo_medidor),
                anio_fabricacion = VALUES(anio_fabricacion),
                fabricante = VALUES(fabricante),
                procedencia = VALUES(procedencia),
                tipo_medidor = VALUES(tipo_medidor),
                diametro_nominal = VALUES(diametro_nominal),
                q3 = VALUES(q3),
                alcance = VALUES(alcance),
                pma = VALUES(pma),
                tma = VALUES(tma),
                clase_sensibilidad = VALUES(clase_sensibilidad),
                certificado_aprobacion = VALUES(certificado_aprobacion),
                num_certificado = VALUES(num_certificado),
                updated_at = CURRENT_TIMESTAMP";
            
            $stmt = $pdo->prepare($sql);
            
            // Mapear datos a parámetros (convertir fechas y horas)
            $params = [
                $data[0],  // item
                $data[1],  // orden_servicio
                convertExcelDateToMySQL($data[2]),  // fecha_os
                !empty($data[3]) ? intval($data[3]) : null,  // cantidad_medidores
                $data[4],  // tipo_servicio
                convertExcelDateToMySQL($data[5]),  // programacion_dia_retiro
                convertDecimalTimeToStandard($data[6]),  // programacion_hora_retiro (10.3 → 10:30)
                convertExcelDateToMySQL($data[7]),  // programacion_dia_vp
                convertDecimalTimeToStandard($data[8]),  // programacion_hora_vp (10.3 → 10:30)
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
                
                // ========== DETECTAR CAMBIOS SI ES ACTUALIZACIÓN ==========
                if ($isUpdate) {
                    $updates++;
                    $changes = detectChanges($existingOC, $params, $data);
                    
                    if (!empty($changes)) {
                        $duplicatesWithChanges[] = [
                            'orden_servicio' => trim($data[1]),
                            'cliente' => trim($data[10]),
                            'changes' => $changes,
                            'change_count' => count($changes)
                        ];
                    }
                } else {
                    $newInserts++;
                }
            } else {
                $errors++;
                $errorDetails[] = "Fila " . ($rowNumber + 2) . ": Error al insertar en base de datos";
            }
        }
        
        // Si es modo preview, NO hacer commit, solo retornar la información
        if ($previewMode) {
            $pdo->rollback();
            
            return [
                'success' => true,
                'preview_mode' => true,
                'total_rows' => count($rows),
                'new_count' => count($rows) - count($duplicatesFound),
                'duplicates_count' => count($duplicatesFound),
                'duplicates_with_changes' => array_filter($duplicatesFound, function($dup) {
                    return $dup['has_changes'];
                }),
                'duplicates_without_changes' => array_filter($duplicatesFound, function($dup) {
                    return !$dup['has_changes'];
                }),
                'all_duplicates' => $duplicatesFound,
                'error_count' => $errors,
                'error_details' => $errorDetails
            ];
        }
        
        $pdo->commit();
        
        // Obtener las últimas OCs importadas (para mostrar en el modal)
        $lastImportedOCs = [];
        if ($success > 0) {
            try {
                $limit = min(5, $success); // Máximo 5 OCs para mostrar
                $stmt = $pdo->query("SELECT orden_servicio, cliente, created_at 
                                     FROM ordenes_servicio 
                                     ORDER BY created_at DESC 
                                     LIMIT $limit");
                $lastImportedOCs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                // Si falla, no es crítico
                error_log("Error al obtener últimas OCs: " . $e->getMessage());
            }
        }
        
        // Información adicional para depuración
        $debugInfo = [
            'total_rows_processed' => count($rows),
            'expected_columns' => $expectedColumns,
            'actual_columns_in_header' => $actualColumns
        ];
        
        return [
            'success' => true,
            'success_count' => $success,
            'new_inserts' => $newInserts,
            'updates' => $updates,
            'skipped_duplicates' => $skippedDuplicates,
            'error_count' => $errors,
            'error_details' => $errorDetails,
            'debug_info' => $debugInfo,
            'last_imported_ocs' => $lastImportedOCs,
            'duplicates_with_changes' => $duplicatesWithChanges,
            'mode' => $skipDuplicates ? 'skip_duplicates' : 'update_all'
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

/**
 * Detecta cambios entre el registro existente y los nuevos datos
 * @param array $existingOC Registro existente en la BD
 * @param array $params Nuevos parámetros preparados para INSERT
 * @param array $data Datos crudos del CSV
 * @return array Array de cambios detectados con formato: ['campo' => ['old' => 'valor_viejo', 'new' => 'valor_nuevo']]
 */
function detectChanges($existingOC, $params, $data) {
    $changes = [];
    
    // Mapeo de índices de params/data a nombres de columnas legibles
    $fieldMap = [
        0 => ['db' => 'item', 'name' => 'Item'],
        2 => ['db' => 'fecha_os', 'name' => 'Fecha OS'],
        3 => ['db' => 'cantidad_medidores', 'name' => 'Cantidad Medidores'],
        4 => ['db' => 'tipo_servicio', 'name' => 'Tipo Servicio'],
        5 => ['db' => 'programacion_dia_retiro', 'name' => 'Programación Día Retiro'],
        6 => ['db' => 'programacion_hora_retiro', 'name' => 'Programación Hora Retiro'],
        7 => ['db' => 'programacion_dia_vp', 'name' => 'Programación Día VP'],
        8 => ['db' => 'programacion_hora_vp', 'name' => 'Programación Hora VP'],
        9 => ['db' => 'codigo_seguridad', 'name' => 'Código Seguridad'],
        10 => ['db' => 'cliente', 'name' => 'Cliente'],
        11 => ['db' => 'centro_servicio', 'name' => 'Centro Servicio'],
        12 => ['db' => 'remesa', 'name' => 'Remesa'],
        13 => ['db' => 'usuario_reclamante', 'name' => 'Usuario Reclamante'],
        14 => ['db' => 'direccion', 'name' => 'Dirección'],
        15 => ['db' => 'cus', 'name' => 'CUS'],
        16 => ['db' => 'cup', 'name' => 'CUP'],
        17 => ['db' => 'num_suministro', 'name' => 'N° Suministro'],
        18 => ['db' => 'num_serie_medidor', 'name' => 'N° Serie Medidor'],
        19 => ['db' => 'marca_medidor', 'name' => 'Marca Medidor'],
        20 => ['db' => 'modelo_medidor', 'name' => 'Modelo Medidor'],
        21 => ['db' => 'anio_fabricacion', 'name' => 'Año Fabricación'],
        22 => ['db' => 'fabricante', 'name' => 'Fabricante'],
        23 => ['db' => 'procedencia', 'name' => 'Procedencia'],
        24 => ['db' => 'tipo_medidor', 'name' => 'Tipo Medidor'],
        25 => ['db' => 'diametro_nominal', 'name' => 'Diámetro Nominal'],
        26 => ['db' => 'q3', 'name' => 'Q3'],
        27 => ['db' => 'alcance', 'name' => 'Alcance'],
        28 => ['db' => 'pma', 'name' => 'PMA'],
        29 => ['db' => 'tma', 'name' => 'TMA'],
        30 => ['db' => 'clase_sensibilidad', 'name' => 'Clase Sensibilidad'],
        31 => ['db' => 'certificado_aprobacion', 'name' => 'Certificado Aprobación'],
        32 => ['db' => 'num_certificado', 'name' => 'N° Certificado']
    ];
    
    // Comparar cada campo (empezando desde índice 2 de params, que corresponde a fecha_os)
    $paramsIndex = 2; // Saltamos item (0) y orden_servicio (1)
    for ($i = 2; $i <= 32; $i++) {
        if (!isset($fieldMap[$i])) continue;
        
        $dbField = $fieldMap[$i]['db'];
        $fieldName = $fieldMap[$i]['name'];
        
        $oldValue = $existingOC[$dbField] ?? '';
        $newValue = $params[$paramsIndex] ?? '';
        
        // Normalizar valores para comparación
        $oldValueNorm = trim((string)$oldValue);
        $newValueNorm = trim((string)$newValue);
        
        // Solo registrar si hay cambio real
        if ($oldValueNorm !== $newValueNorm) {
            $changes[] = [
                'field' => $fieldName,
                'old' => $oldValueNorm ?: '(vacío)',
                'new' => $newValueNorm ?: '(vacío)'
            ];
        }
        
        $paramsIndex++;
    }
    
    return $changes;
}

/**
 * Convierte hora de formato decimal (10.3, 8.15, 14) a formato HH:MM
 * El decimal representa decenas de minutos: 10.3 = 10:30, 8.15 = 08:15
 * @param string $time Hora en formato decimal
 * @return string Hora en formato HH:MM
 */
function convertDecimalTimeToStandard($time) {
    if (empty($time)) {
        return '';
    }
    
    // Limpiar espacios
    $time = trim($time);
    
    // Si ya está en formato HH:MM, retornar tal cual
    if (preg_match('/^\d{1,2}:\d{2}$/', $time)) {
        return $time;
    }
    
    // Separar por punto
    $parts = explode('.', $time);
    $hours = intval($parts[0]);
    
    // Si no hay parte decimal, los minutos son 00
    if (count($parts) == 1) {
        $minutes = '00';
    } else {
        // La parte decimal son los minutos (10.3 = 10:30, 8.15 = 08:15)
        $minutesPart = $parts[1];
        // Asegurar que tenga 2 dígitos (8.5 = 08:50, 10.3 = 10:30)
        $minutes = str_pad($minutesPart, 2, '0', STR_PAD_RIGHT);
    }
    
    // Formatear con ceros iniciales
    return sprintf('%02d:%s', $hours, $minutes);
}

/**
 * Convierte fecha de formato DD/MM/YYYY (Excel) a YYYY-MM-DD (MySQL)
 * Acepta fechas con o sin ceros iniciales: 6/01/2025 o 06/01/2025
 * @param string $date Fecha en formato DD/MM/YYYY o D/M/YYYY
 * @return string|null Fecha en formato YYYY-MM-DD o null si es inválida
 */
function convertExcelDateToMySQL($date) {
    if (empty($date)) {
        return null;
    }
    
    // Limpiar espacios
    $date = trim($date);
    
    // Si ya está en formato YYYY-MM-DD, retornar tal cual
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return $date;
    }
    
    // Intentar convertir DD/MM/YYYY a YYYY-MM-DD (con ceros)
    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $date, $matches)) {
        $day = $matches[1];
        $month = $matches[2];
        $year = $matches[3];
        
        // Validar que sea una fecha válida
        if (checkdate($month, $day, $year)) {
            return "$year-$month-$day";
        }
    }
    
    // Intentar convertir D/M/YYYY a YYYY-MM-DD (sin ceros iniciales)
    // Acepta: 6/01/2025, 06/1/2025, 6/1/2025, etc.
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $date, $matches)) {
        $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
        $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
        $year = $matches[3];
        
        // Validar que sea una fecha válida
        if (checkdate($month, $day, $year)) {
            return "$year-$month-$day";
        }
    }
    
    // Si no se pudo convertir, retornar null
    return null;
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
    
    // Verificar si es modo preview (solo detectar duplicados) o procesamiento final
    $previewMode = isset($_POST['preview_mode']) && $_POST['preview_mode'] === 'true';
    $skipDuplicates = isset($_POST['skip_duplicates']) && $_POST['skip_duplicates'] === 'true';
    
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
    $result = processExcelFile($file['tmp_name'], $previewMode, $skipDuplicates);
    
    // Registrar en auditoría (solo si no es preview)
    if (!$previewMode && $result['success']) {
        logUserAction('importacion_excel', 
            "Importación exitosa: {$result['success_count']} registros, {$result['error_count']} errores");
    } else if (!$previewMode && !$result['success']) {
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
