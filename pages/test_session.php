<?php
require_once '../config/database.php';

echo "<h2>Prueba de Sesión</h2>";
echo "<pre>";
echo "Session Status: " . session_status() . "\n";
echo "Session ID: " . session_id() . "\n";
echo "Logged in: " . (isLoggedIn() ? 'YES' : 'NO') . "\n";
echo "User Role: " . (isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'NOT SET') . "\n";
echo "Username: " . (isset($_SESSION['username']) ? $_SESSION['username'] : 'NOT SET') . "\n";
echo "\nAll SESSION data:\n";
print_r($_SESSION);
echo "</pre>";
?>
