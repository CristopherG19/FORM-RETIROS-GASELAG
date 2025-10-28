<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador Automático - GASELAG</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 800px;
            width: 100%;
        }
        h1 {
            color: #667eea;
            margin-top: 0;
            text-align: center;
        }
        .step {
            background: #f8f9fa;
            padding: 15px;
            margin: 15px 0;
            border-radius: 8px;
            border-left: 4px solid #ddd;
        }
        .step.success {
            border-color: #28a745;
            background: #d4edda;
        }
        .step.error {
            border-color: #dc3545;
            background: #f8d7da;
        }
        .step.info {
            border-color: #17a2b8;
            background: #d1ecf1;
        }
        .icon {
            font-size: 24px;
            margin-right: 10px;
        }
        pre {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 12px;
        }
        .button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 10px 5px;
        }
        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        .button.secondary {
            background: #6c757d;
        }
        .button.success {
            background: #28a745;
        }
        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Instalador Automático GASELAG</h1>
        <p class="text-center">Sistema de Retiro de Medidores</p>
        <hr>

        <?php
        $paso = isset($_GET['paso']) ? $_GET['paso'] : 0;
        
        if ($paso == 0) {
            ?>
            <div class="step info">
                <span class="icon">ℹ️</span>
                <strong>Bienvenido al Instalador</strong>
                <p>Este asistente configurará automáticamente:</p>
                <ul>
                    <li>✅ Base de datos <code>gaselag_retiros</code></li>
                    <li>✅ Tablas necesarias (ordenes_servicio, retiros_medidores, sesiones_oc)</li>
                    <li>✅ Datos de ejemplo (opcional)</li>
                </ul>
                <p><strong>Configuración actual:</strong></p>
                <ul>
                    <li><strong>Host:</strong> localhost</li>
                    <li><strong>Puerto:</strong> 3307</li>
                    <li><strong>Usuario:</strong> root</li>
                    <li><strong>Contraseña:</strong> (vacía)</li>
                </ul>
            </div>
            
            <div class="text-center">
                <a href="?paso=1" class="button">Comenzar Instalación</a>
                <a href="verificar_instalacion.php" class="button secondary">Verificar Sistema</a>
            </div>
            <?php
        } elseif ($paso == 1) {
            ?>
            <h2>📋 Paso 1: Verificación de Requisitos</h2>
            
            <?php
            $errores = 0;
            
            // Verificar PHP
            $phpOk = version_compare(PHP_VERSION, '7.4.0', '>=');
            echo '<div class="step ' . ($phpOk ? 'success' : 'error') . '">';
            echo '<span class="icon">' . ($phpOk ? '✅' : '❌') . '</span>';
            echo '<strong>PHP ' . PHP_VERSION . '</strong> - ';
            echo $phpOk ? 'OK' : 'ERROR: Se requiere PHP 7.4+';
            echo '</div>';
            if (!$phpOk) $errores++;
            
            // Verificar PDO
            $pdoOk = extension_loaded('pdo') && extension_loaded('pdo_mysql');
            echo '<div class="step ' . ($pdoOk ? 'success' : 'error') . '">';
            echo '<span class="icon">' . ($pdoOk ? '✅' : '❌') . '</span>';
            echo '<strong>PDO MySQL</strong> - ';
            echo $pdoOk ? 'OK' : 'ERROR: Extensión no disponible';
            echo '</div>';
            if (!$pdoOk) $errores++;
            
            // Verificar conexión
            try {
                $dsn = "mysql:host=localhost;port=3307;charset=utf8mb4";
                $pdo = new PDO($dsn, 'root', '', [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
                echo '<div class="step success">';
                echo '<span class="icon">✅</span>';
                echo '<strong>Conexión MySQL (Puerto 3307)</strong> - OK';
                echo '</div>';
            } catch (PDOException $e) {
                echo '<div class="step error">';
                echo '<span class="icon">❌</span>';
                echo '<strong>Conexión MySQL</strong> - ERROR<br>';
                echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
                echo '<p><strong>Solución:</strong> Asegúrate de que MySQL esté corriendo en XAMPP Control Panel</p>';
                echo '</div>';
                $errores++;
            }
            
            // Verificar carpeta uploads
            $uploadsOk = is_dir('uploads') && is_writable('uploads');
            echo '<div class="step ' . ($uploadsOk ? 'success' : 'error') . '">';
            echo '<span class="icon">' . ($uploadsOk ? '✅' : '⚠️') . '</span>';
            echo '<strong>Carpeta uploads/</strong> - ';
            echo $uploadsOk ? 'OK' : 'Advertencia: Sin permisos de escritura';
            echo '</div>';
            
            if ($errores == 0) {
                echo '<div class="text-center">';
                echo '<a href="?paso=2" class="button success">Continuar con la Instalación</a>';
                echo '</div>';
            } else {
                echo '<div class="step error">';
                echo '<strong>⚠️ Corrige los errores antes de continuar</strong>';
                echo '</div>';
                echo '<div class="text-center">';
                echo '<a href="?paso=1" class="button secondary">Reintentar</a>';
                echo '</div>';
            }
            ?>
            
            <?php
        } elseif ($paso == 2) {
            ?>
            <h2>🗄️ Paso 2: Creación de Base de Datos</h2>
            
            <?php
            try {
                // Conectar sin especificar base de datos
                $dsn = "mysql:host=localhost;port=3307;charset=utf8mb4";
                $pdo = new PDO($dsn, 'root', '', [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
                
                // Eliminar base de datos existente (si existe)
                echo '<div class="step info">';
                echo '<span class="icon">🗑️</span>';
                echo 'Eliminando base de datos existente (si existe)...';
                echo '</div>';
                
                $pdo->exec("DROP DATABASE IF EXISTS gaselag_retiros");
                
                echo '<div class="step success">';
                echo '<span class="icon">✅</span>';
                echo '<strong>Base de datos anterior eliminada</strong>';
                echo '</div>';
                
                // Crear base de datos nueva
                echo '<div class="step info">';
                echo '<span class="icon">⏳</span>';
                echo 'Creando base de datos <code>gaselag_retiros</code>...';
                echo '</div>';
                
                $pdo->exec("CREATE DATABASE gaselag_retiros CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                
                echo '<div class="step success">';
                echo '<span class="icon">✅</span>';
                echo '<strong>Base de datos creada exitosamente</strong>';
                echo '</div>';
                
                // Usar la base de datos
                $pdo->exec("USE gaselag_retiros");
                
                // Leer y ejecutar schema.sql
                echo '<div class="step info">';
                echo '<span class="icon">⏳</span>';
                echo 'Creando tablas y datos iniciales...';
                echo '</div>';
                
                $sql = file_get_contents('database/schema.sql');
                
                // Dividir por punto y coma y ejecutar cada statement
                $statements = array_filter(array_map('trim', explode(';', $sql)));
                $tablas_creadas = [];
                $datos_insertados = 0;
                
                foreach ($statements as $statement) {
                    if (!empty($statement) && stripos($statement, 'CREATE DATABASE') === false && stripos($statement, 'USE ') === false) {
                        try {
                            $pdo->exec($statement);
                            
                            // Contar tablas creadas
                            if (stripos($statement, 'CREATE TABLE') !== false) {
                                preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches);
                                if (isset($matches[1])) {
                                    $tablas_creadas[] = $matches[1];
                                }
                            }
                            
                            // Contar datos insertados
                            if (stripos($statement, 'INSERT') !== false) {
                                $datos_insertados++;
                            }
                            
                        } catch (PDOException $e) {
                            // Si es un error de duplicado, lo ignoramos
                            if (strpos($e->getMessage(), 'Duplicate entry') === false && 
                                strpos($e->getMessage(), 'already exists') === false) {
                                throw $e; // Re-lanzar si no es un error de duplicado
                            }
                        }
                    }
                }
                
                echo '<div class="step success">';
                echo '<span class="icon">✅</span>';
                echo '<strong>Base de datos instalada exitosamente</strong>';
                echo '<ul>';
                foreach ($tablas_creadas as $tabla) {
                    echo '<li>✓ ' . $tabla . '</li>';
                }
                echo '<li>✓ ' . $datos_insertados . ' conjuntos de datos iniciales</li>';
                echo '</ul>';
                echo '</div>';
                
                echo '<div class="text-center">';
                echo '<a href="?paso=3" class="button">Cargar Datos de Ejemplo</a>';
                echo '<a href="index.php" class="button secondary">Omitir e Ir al Sistema</a>';
                echo '</div>';
                
            } catch (Exception $e) {
                echo '<div class="step error">';
                echo '<span class="icon">❌</span>';
                echo '<strong>Error durante la instalación:</strong><br>';
                echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
                echo '</div>';
                
                echo '<div class="text-center">';
                echo '<a href="?paso=1" class="button secondary">Volver a Intentar</a>';
                echo '</div>';
            }
            ?>
            
            <?php
        } elseif ($paso == 3) {
            ?>
            <h2>📊 Paso 3: Cargar Datos de Ejemplo</h2>
            
            <?php
            try {
                require_once 'config/database.php';
                $pdo = getConnection();
                
                // Leer datos de ejemplo
                $data = file_get_contents('datos_ejemplo.txt');
                $lines = explode("\n", trim($data));
                
                // Saltar encabezados
                array_shift($lines);
                
                $success = 0;
                
                echo '<div class="step info">';
                echo '<span class="icon">⏳</span>';
                echo 'Importando datos de ejemplo...';
                echo '</div>';
                
                $pdo->beginTransaction();
                
                foreach ($lines as $line) {
                    if (empty(trim($line))) continue;
                    
                    $data = explode("\t", $line);
                    
                    if (count($data) < 33) continue;
                    
                    $sql = "INSERT INTO ordenes_servicio (
                        item, orden_servicio, fecha_os, cantidad_medidores, tipo_servicio,
                        programacion_dia_retiro, programacion_hora_retiro, programacion_dia_vp, 
                        programacion_hora_vp, codigo_seguridad, cliente, centro_servicio, remesa,
                        usuario_reclamante, direccion, cus, cup, num_suministro, num_serie_medidor,
                        marca_medidor, modelo_medidor, anio_fabricacion, fabricante, procedencia,
                        tipo_medidor, diametro_nominal, q3, alcance, pma, tma, clase_sensibilidad,
                        certificado_aprobacion, num_certificado
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    $stmt = $pdo->prepare($sql);
                    
                    $fecha_os = !empty($data[2]) ? date('Y-m-d', strtotime(str_replace('/', '-', $data[2]))) : null;
                    $prog_dia_retiro = !empty($data[5]) ? date('Y-m-d', strtotime(str_replace('/', '-', $data[5]))) : null;
                    $prog_dia_vp = !empty($data[7]) ? date('Y-m-d', strtotime(str_replace('/', '-', $data[7]))) : null;
                    
                    $stmt->execute([
                        trim($data[0]), trim($data[1]), $fecha_os, intval($data[3]), trim($data[4]),
                        $prog_dia_retiro, trim($data[6]), $prog_dia_vp, trim($data[8]), trim($data[9]),
                        trim($data[10]), trim($data[11]), trim($data[12]), trim($data[13]), trim($data[14]),
                        trim($data[15]), trim($data[16]), trim($data[17]), trim($data[18]), trim($data[19]),
                        trim($data[20]), !empty($data[21]) ? intval($data[21]) : null, trim($data[22]),
                        trim($data[23]), trim($data[24]), !empty($data[25]) ? intval($data[25]) : null,
                        !empty($data[26]) ? floatval($data[26]) : null, trim($data[27]),
                        !empty($data[28]) ? intval($data[28]) : null, !empty($data[29]) ? intval($data[29]) : null,
                        trim($data[30]), trim($data[31]), trim($data[32])
                    ]);
                    
                    $success++;
                }
                
                $pdo->commit();
                
                echo '<div class="step success">';
                echo '<span class="icon">✅</span>';
                echo '<strong>Datos importados exitosamente</strong><br>';
                echo '📊 Total: ' . $success . ' órdenes de servicio';
                echo '</div>';
                
                echo '<div class="step info">';
                echo '<strong>🎉 ¡Instalación Completada!</strong><br>';
                echo '<p>El sistema está listo para usar. Ahora puedes:</p>';
                echo '<ul>';
                echo '<li>✓ Registrar retiros de medidores</li>';
                echo '<li>✓ Consultar registros existentes</li>';
                echo '<li>✓ Importar más datos desde Excel</li>';
                echo '</ul>';
                echo '</div>';
                
                echo '<div class="text-center">';
                echo '<a href="index.php" class="button success">🏠 Ir al Sistema</a>';
                echo '<a href="verificar_instalacion.php" class="button secondary">🔍 Verificar Instalación</a>';
                echo '</div>';
                
            } catch (Exception $e) {
                echo '<div class="step error">';
                echo '<span class="icon">❌</span>';
                echo '<strong>Error al cargar datos:</strong><br>';
                echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
                echo '</div>';
                
                echo '<div class="text-center">';
                echo '<p>Puedes continuar sin datos de ejemplo e importarlos manualmente después.</p>';
                echo '<a href="index.php" class="button secondary">Ir al Sistema</a>';
                echo '</div>';
            }
            ?>
            
            <?php
        }
        ?>
    </div>
</body>
</html>

