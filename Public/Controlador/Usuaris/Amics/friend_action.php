<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['usuari_id'])) {
    echo json_encode(['success' => false, 'message' => 'No has iniciado sesión']);
    exit;
}

// Include needed files
require_once __DIR__ . "/../../../Model/configuracio.php";
require_once __DIR__ . "/AmicsControlador.php";

// Initialize controller
$amicsControlador = new AmicsControlador($connexio);

// Get current user ID
$usuariId = $_SESSION['usuari_id'];

// Process the action
$action = $_POST['action'] ?? '';
$result = ['success' => false, 'message' => 'Acción no reconocida'];

switch ($action) {
    case 'accept':
        $requestId = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
        if ($requestId > 0) {
            $result = $amicsControlador->acceptRequest($requestId, $usuariId);
            $result = ['success' => $result];
        } else {
            $result = ['success' => false, 'message' => 'ID de solicitud inválido'];
        }
        break;
    
    case 'reject':
        $requestId = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
        if ($requestId > 0) {
            $result = $amicsControlador->rejectRequest($requestId, $usuariId);
            $result = ['success' => $result];
        } else {
            $result = ['success' => false, 'message' => 'ID de solicitud inválido'];
        }
        break;
    
    case 'send_request':
        $friendId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        if ($friendId > 0) {
            $result = $amicsControlador->sendFriendRequest($usuariId, $friendId);
        } else {
            $result = ['success' => false, 'error' => 'ID de usuario inválido'];
        }
        break;
}

// Return JSON response
echo json_encode($result);
?>
