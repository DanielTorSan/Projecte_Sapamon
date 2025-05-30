<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['usuari_id'])) {
    echo json_encode(['success' => false, 'message' => 'No has iniciat sessió']);
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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Error #5: Fixed parameter name to match what's sent from frontend
    $sollicitudId = isset($_POST['sollicitud_id']) ? (int)$_POST['sollicitud_id'] : 0;
    $accio = isset($_POST['accio']) ? $_POST['accio'] : '';
    
    // Debug log
    error_log("Processing friend request action: $accio for request ID: $sollicitudId by user: $usuariId");
    
    if ($sollicitudId > 0 && in_array($accio, ['acceptar', 'rebutjar'])) {
        // Error #6: Using the correct method gestionaAmistat instead of trying to call methods directly
        $result = $amicsControlador->gestionaAmistat($sollicitudId, $usuariId, $accio);
        
        // Debug log
        error_log("Action result: " . ($result ? 'success' : 'failure'));
        
        echo json_encode(['success' => $result]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Paràmetres invàlids']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Mètode no permès']);
}
?>
