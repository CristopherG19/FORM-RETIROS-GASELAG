<?php
/**
 * Ping endpoint para actualizar sesión
 * Este archivo maneja las peticiones AJAX para mantener la sesión activa
 */

require_once __DIR__ . '/database.php';

header('Content-Type: application/json');

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Verificar que hay sesión activa
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'ping':
            // Actualizar última actividad
            updateLastActivity();
            echo json_encode([
                'success' => true,
                'remaining' => getSessionTimeRemaining(),
                'message' => 'Activity updated'
            ]);
            break;
            
        case 'extend':
            // Extender sesión explícitamente
            $extended = extendSession();
            echo json_encode([
                'success' => $extended,
                'remaining' => getSessionTimeRemaining(),
                'message' => $extended ? 'Session extended' : 'Failed to extend session'
            ]);
            break;
            
        case 'check':
            // Solo verificar estado
            echo json_encode([
                'success' => true,
                'remaining' => getSessionTimeRemaining(),
                'user' => $_SESSION['username'],
                'role' => $_SESSION['user_role']
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    error_log("Error en ping.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
