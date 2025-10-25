<?php
require_once '../config/database.php';

$message = '';
$messageType = '';

// Procesar importación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['csv_data']) && !empty($_POST['csv_data'])) {
        try {
            $pdo = getConnection();
            $lines = explode("\n", trim($_POST['csv_data']));
            
            $success = 0;
            $errors = 0;
            
            $pdo->beginTransaction();
            
            foreach ($lines as $lineNumber => $line) {
                if (empty(trim($line))) continue;
                
                // Separar por tabulador
                $data = explode("\t", $line);
                
                // Validar que tenga al menos 33 columnas
                if (count($data) < 33) {
                    $errors++;
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
                
                // Convertir fechas
                $fecha_os = !empty($data[2]) ? date('Y-m-d', strtotime(str_replace('/', '-', $data[2]))) : null;
                $prog_dia_retiro = !empty($data[5]) ? date('Y-m-d', strtotime(str_replace('/', '-', $data[5]))) : null;
                $prog_dia_vp = !empty($data[7]) ? date('Y-m-d', strtotime(str_replace('/', '-', $data[7]))) : null;
                
                try {
                    $stmt->execute([
                        trim($data[0]),  // item
                        trim($data[1]),  // orden_servicio
                        $fecha_os,       // fecha_os
                        intval($data[3]), // cantidad_medidores
                        trim($data[4]),  // tipo_servicio
                        $prog_dia_retiro, // programacion_dia_retiro
                        trim($data[6]),  // programacion_hora_retiro
                        $prog_dia_vp,    // programacion_dia_vp
                        trim($data[8]),  // programacion_hora_vp
                        trim($data[9]),  // codigo_seguridad
                        trim($data[10]), // cliente
                        trim($data[11]), // centro_servicio
                        trim($data[12]), // remesa
                        trim($data[13]), // usuario_reclamante
                        trim($data[14]), // direccion
                        trim($data[15]), // cus
                        trim($data[16]), // cup
                        trim($data[17]), // num_suministro
                        trim($data[18]), // num_serie_medidor
                        trim($data[19]), // marca_medidor
                        trim($data[20]), // modelo_medidor
                        !empty($data[21]) ? intval($data[21]) : null, // anio_fabricacion
                        trim($data[22]), // fabricante
                        trim($data[23]), // procedencia
                        trim($data[24]), // tipo_medidor
                        !empty($data[25]) ? intval($data[25]) : null, // diametro_nominal
                        !empty($data[26]) ? floatval($data[26]) : null, // q3
                        trim($data[27]), // alcance
                        !empty($data[28]) ? intval($data[28]) : null, // pma
                        !empty($data[29]) ? intval($data[29]) : null, // tma
                        trim($data[30]), // clase_sensibilidad
                        trim($data[31]), // certificado_aprobacion
                        trim($data[32])  // num_certificado
                    ]);
                    $success++;
                } catch (PDOException $e) {
                    $errors++;
                }
            }
            
            $pdo->commit();
            $message = "Importación completada: $success registros exitosos, $errors errores.";
            $messageType = 'success';
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Error al importar: " . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// Obtener estadísticas
try {
    $pdo = getConnection();
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM ordenes_servicio");
    $total_registros = $stmt->fetch()['total'];
} catch (Exception $e) {
    $total_registros = 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Datos - GASELAG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="mb-3">
                    <a href="../index.php" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Volver al Inicio
                    </a>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0">
                            <i class="bi bi-file-earmark-excel text-success"></i>
                            Importar Datos de Órdenes de Servicio
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                                <?= $message ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <div class="alert alert-info border-0">
                            <h6 class="fw-bold mb-3"><i class="bi bi-info-circle"></i> Instrucciones:</h6>
                            <ol class="mb-3">
                                <li>Abra su archivo Excel con los datos de las órdenes de servicio</li>
                                <li><strong class="text-danger">Seleccione SOLO las filas de datos</strong> (NO incluya la fila de encabezados)</li>
                                <li>Copie las filas seleccionadas (Ctrl+C o Cmd+C)</li>
                                <li>Pegue los datos en el área de texto a continuación</li>
                                <li>Haga clic en "Importar Datos"</li>
                            </ol>
                            <div class="alert alert-warning border-0 mb-3">
                                <small>
                                    <strong>⚠️ Importante:</strong> NO copie la primera fila (encabezados como "Item", "Orden de servicio", etc.).
                                    Solo copie las filas con los datos.
                                </small>
                            </div>
                            <p class="mb-0 text-muted small"><strong>Total de registros en base de datos:</strong> <?= $total_registros ?></p>
                        </div>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="csv_data" class="form-label">
                                    <strong>Pegue las filas de datos del Excel aquí:</strong>
                                </label>
                                <textarea 
                                    class="form-control font-monospace" 
                                    id="csv_data" 
                                    name="csv_data" 
                                    rows="15" 
                                    placeholder="Pegue aquí SOLO las filas de datos (SIN encabezados)&#13;&#10;Ejemplo:&#13;&#10;00001	OC-00001	2024-12-13	1	Reclamo	6/01/2025	..."
                                    required
                                ></textarea>
                                <div class="form-text">
                                    <i class="bi bi-lightbulb text-warning"></i> 
                                    <strong>Recuerda:</strong> Copiar directamente desde Excel sin la fila de encabezados. Los datos deben estar separados por tabuladores.
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-upload"></i>
                                    Importar Datos
                                </button>
                            </div>
                        </form>

                        <hr class="my-4">

                        <div class="alert alert-warning">
                            <strong><i class="bi bi-exclamation-triangle"></i> Nota:</strong>
                            Si una orden de servicio ya existe (mismo código OC), se actualizarán solo algunos campos básicos.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

