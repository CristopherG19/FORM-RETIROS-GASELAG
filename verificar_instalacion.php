<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Instalación - GASELAG</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .check {
            background: white;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 4px solid #ddd;
        }
        .check.success {
            border-color: #28a745;
        }
        .check.error {
            border-color: #dc3545;
        }
        .check.warning {
            border-color: #ffc107;
        }
        h1 {
            color: #333;
        }
        .icon {
            font-size: 20px;
            margin-right: 10px;
        }
        pre {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>🔍 Verificación de Instalación - GASELAG</h1>
    <p>Este script verifica que todo esté configurado correctamente.</p>
    <hr>

    <?php
    $errores = 0;
    $advertencias = 0;

    // 1. Verificar versión de PHP
    echo '<div class="check ' . (version_compare(PHP_VERSION, '7.4.0', '>=') ? 'success' : 'error') . '">';
    echo '<span class="icon">' . (version_compare(PHP_VERSION, '7.4.0', '>=') ? '✅' : '❌') . '</span>';
    echo '<strong>PHP Version:</strong> ' . PHP_VERSION;
    if (version_compare(PHP_VERSION, '7.4.0', '<')) {
        echo ' - <strong>ERROR:</strong> Se requiere PHP 7.4 o superior';
        $errores++;
    }
    echo '</div>';

    // 2. Verificar PDO
    echo '<div class="check ' . (extension_loaded('pdo') ? 'success' : 'error') . '">';
    echo '<span class="icon">' . (extension_loaded('pdo') ? '✅' : '❌') . '</span>';
    echo '<strong>Extensión PDO:</strong> ' . (extension_loaded('pdo') ? 'Instalada' : 'NO INSTALADA');
    if (!extension_loaded('pdo')) {
        echo ' - <strong>ERROR:</strong> PDO es requerido';
        $errores++;
    }
    echo '</div>';

    // 3. Verificar PDO MySQL
    echo '<div class="check ' . (extension_loaded('pdo_mysql') ? 'success' : 'error') . '">';
    echo '<span class="icon">' . (extension_loaded('pdo_mysql') ? '✅' : '❌') . '</span>';
    echo '<strong>Extensión PDO MySQL:</strong> ' . (extension_loaded('pdo_mysql') ? 'Instalada' : 'NO INSTALADA');
    if (!extension_loaded('pdo_mysql')) {
        echo ' - <strong>ERROR:</strong> PDO MySQL es requerido';
        $errores++;
    }
    echo '</div>';

    // 4. Verificar archivo de configuración
    $configExists = file_exists('config/database.php');
    echo '<div class="check ' . ($configExists ? 'success' : 'error') . '">';
    echo '<span class="icon">' . ($configExists ? '✅' : '❌') . '</span>';
    echo '<strong>Archivo de Configuración:</strong> ' . ($configExists ? 'Existe' : 'NO ENCONTRADO');
    if (!$configExists) {
        echo ' - <strong>ERROR:</strong> Falta config/database.php';
        $errores++;
    }
    echo '</div>';

    // 5. Verificar conexión a base de datos
    if ($configExists) {
        require_once 'config/database.php';
        try {
            $pdo = getConnection();
            echo '<div class="check success">';
            echo '<span class="icon">✅</span>';
            echo '<strong>Conexión a Base de Datos:</strong> Exitosa';
            echo '</div>';

            // 6. Verificar tablas
            $tablas_requeridas = ['ordenes_servicio', 'retiros_medidores', 'sesiones_oc'];
            $stmt = $pdo->query("SHOW TABLES");
            $tablas_existentes = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($tablas_requeridas as $tabla) {
                $existe = in_array($tabla, $tablas_existentes);
                echo '<div class="check ' . ($existe ? 'success' : 'error') . '">';
                echo '<span class="icon">' . ($existe ? '✅' : '❌') . '</span>';
                echo '<strong>Tabla "' . $tabla . '":</strong> ' . ($existe ? 'Existe' : 'NO EXISTE');
                if (!$existe) {
                    echo ' - <strong>ERROR:</strong> Falta esta tabla. Importa database/schema.sql';
                    $errores++;
                }
                echo '</div>';
            }

            // 7. Contar registros
            $stmt = $pdo->query("SELECT COUNT(*) FROM ordenes_servicio");
            $count = $stmt->fetchColumn();
            echo '<div class="check ' . ($count > 0 ? 'success' : 'warning') . '">';
            echo '<span class="icon">' . ($count > 0 ? '✅' : '⚠️') . '</span>';
            echo '<strong>Registros en BD:</strong> ' . $count . ' órdenes de servicio';
            if ($count == 0) {
                echo ' - <strong>ADVERTENCIA:</strong> No hay datos. Importa datos_ejemplo.txt';
                $advertencias++;
            }
            echo '</div>';

        } catch (Exception $e) {
            echo '<div class="check error">';
            echo '<span class="icon">❌</span>';
            echo '<strong>Conexión a Base de Datos:</strong> ERROR<br>';
            echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
            echo '<p><strong>Soluciones:</strong></p>';
            echo '<ul>';
            echo '<li>Verifica que MySQL esté corriendo en XAMPP</li>';
            echo '<li>Verifica las credenciales en config/database.php</li>';
            echo '<li>Asegúrate de haber creado la base de datos "gaselag_retiros"</li>';
            echo '</ul>';
            $errores++;
            echo '</div>';
        }
    }

    // 8. Verificar carpeta uploads
    $uploadsExists = is_dir('uploads');
    $uploadsWritable = $uploadsExists && is_writable('uploads');
    echo '<div class="check ' . ($uploadsWritable ? 'success' : ($uploadsExists ? 'warning' : 'error')) . '">';
    echo '<span class="icon">' . ($uploadsWritable ? '✅' : ($uploadsExists ? '⚠️' : '❌')) . '</span>';
    echo '<strong>Carpeta Uploads:</strong> ';
    if ($uploadsWritable) {
        echo 'Existe y tiene permisos de escritura';
    } elseif ($uploadsExists) {
        echo 'Existe pero NO tiene permisos de escritura';
        echo ' - <strong>ADVERTENCIA:</strong> No se podrán subir fotos';
        $advertencias++;
    } else {
        echo 'NO EXISTE';
        echo ' - <strong>ERROR:</strong> Crea la carpeta "uploads"';
        $errores++;
    }
    echo '</div>';

    // 9. Verificar configuración de upload
    $maxUpload = ini_get('upload_max_filesize');
    $postMax = ini_get('post_max_size');
    echo '<div class="check ' . (intval($maxUpload) >= 5 ? 'success' : 'warning') . '">';
    echo '<span class="icon">' . (intval($maxUpload) >= 5 ? '✅' : '⚠️') . '</span>';
    echo '<strong>Límite de Upload:</strong> ' . $maxUpload . ' (Post: ' . $postMax . ')';
    if (intval($maxUpload) < 5) {
        echo ' - <strong>ADVERTENCIA:</strong> Puede ser insuficiente para fotos grandes';
        $advertencias++;
    }
    echo '</div>';

    // Resumen
    echo '<hr>';
    echo '<h2>📊 Resumen</h2>';
    
    if ($errores == 0 && $advertencias == 0) {
        echo '<div class="check success">';
        echo '<h3>🎉 ¡TODO ESTÁ PERFECTO!</h3>';
        echo '<p>El sistema está listo para usar.</p>';
        echo '<p><a href="index.php" style="display: inline-block; background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Ir al Sistema</a></p>';
        echo '</div>';
    } else {
        if ($errores > 0) {
            echo '<div class="check error">';
            echo '<h3>❌ Se encontraron ' . $errores . ' error(es) crítico(s)</h3>';
            echo '<p>El sistema NO funcionará hasta que se corrijan.</p>';
            echo '</div>';
        }
        if ($advertencias > 0) {
            echo '<div class="check warning">';
            echo '<h3>⚠️ Se encontraron ' . $advertencias . ' advertencia(s)</h3>';
            echo '<p>El sistema puede funcionar, pero algunas características pueden no estar disponibles.</p>';
            echo '</div>';
        }
    }
    ?>

    <hr>
    <p><small>Versión 1.0.0 - Sistema GASELAG</small></p>
    <p><a href="?">🔄 Verificar Nuevamente</a> | <a href="index.php">🏠 Ir al Sistema</a></p>
</body>
</html>

