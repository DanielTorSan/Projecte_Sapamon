<?php
// Iniciar sesión
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Comprobar si el usuario está autenticado
if (!isset($_SESSION['usuari_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'No has iniciat sessió']);
    exit;
}

// Incluir archivos necesarios
require_once __DIR__ . "/../../../Model/configuracio.php";
require_once __DIR__ . "/ChatControlador.php";

// Inicializar controlador
$chatControlador = new ChatControlador($connexio);

// Obtener el ID del usuario actual
$usuariId = $_SESSION['usuari_id'];

// Procesar la solicitud
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Obtener ID del amigo
    $amicId = isset($_GET['amic_id']) ? (int)$_GET['amic_id'] : 0;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    
    if ($amicId <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'ID d\'amic no vàlid']);
        exit;
    }
    
    // Obtener mensajes
    $missatges = $chatControlador->getHistorialMissatges($usuariId, $amicId, $limit, $offset);
    
    // Marcar mensajes como leídos
    $chatControlador->marcarComoLlegits($usuariId, $amicId);
    
    // Devolver respuesta
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'missatges' => $missatges]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Mètode no permès']);
}
?>