<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== TEST DE CARGA DE PÁGINAS ===\n\n";

// Simular sesión
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = '12345678';
$_SESSION['user_role'] = 'user';
$_SESSION['nombre_completo'] = 'Técnico de Prueba 1';
$_SESSION['last_activity'] = time();
$_SESSION['login_time'] = time();
$_SESSION['session_timeout'] = 7200;

echo "✓ Sesión iniciada\n";
echo "✓ Usuario: " . $_SESSION['username'] . "\n";
echo "✓ Rol: " . $_SESSION['user_role'] . "\n\n";

// Test 1: Cargar database.php
echo "TEST 1: Cargando database.php...\n";
try {
    require_once 'config/database.php';
    echo "✓ database.php cargado correctamente\n\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n\n";
    exit(1);
}

// Test 2: Verificar conexión
echo "TEST 2: Verificando conexión a BD...\n";
try {
    $pdo = getConnection();
    echo "✓ Conexión establecida\n\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 3: Verificar función login
echo "TEST 3: Verificando función login()...\n";
if (function_exists('login')) {
    echo "✓ Función login() existe\n\n";
} else {
    echo "✗ ERROR: Función login() no existe\n\n";
}

// Test 4: Verificar funciones de seguridad
echo "TEST 4: Verificando funciones de seguridad...\n";
$functionsToCheck = [
    'isAccountBlocked',
    'recordLoginAttempt',
    'getDeviceFingerprint',
    'registerDevice',
    'checkSessionTimeout',
    'updateLastActivity'
];

foreach ($functionsToCheck as $func) {
    if (function_exists($func)) {
        echo "✓ Función $func() existe\n";
    } else {
        echo "✗ ERROR: Función $func() no existe\n";
    }
}

echo "\n=== TODOS LOS TESTS COMPLETADOS ===\n";
