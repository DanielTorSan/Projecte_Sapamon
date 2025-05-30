<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['usuari_id'])) {
    echo json_encode([]);
    exit;
}

// Include needed files
require_once __DIR__ . "/../../Model/configuracio.php";
require_once __DIR__ . "/Amics/AmicsControlador.php";

// Initialize controller
$amicsControlador = new AmicsControlador($connexio);

// Get current user ID
$usuariId = $_SESSION['usuari_id'];

// Get search term
$search = isset($_GET['term']) ? trim($_GET['term']) : '';

// Search users using the controller
$results = $amicsControlador->searchUsers($search, $usuariId);

// Return JSON response
header('Content-Type: application/json');
echo json_encode($results);
?>
