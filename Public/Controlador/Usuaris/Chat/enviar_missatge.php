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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del mensaje
    $receptorId = isset($_POST['receptor_id']) ? (int)$_POST['receptor_id'] : 0;
    $contingut = isset($_POST['contingut']) ? trim($_POST['contingut']) : '';
    
    if ($receptorId <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Destinatari no vàlid']);
        exit;
    }
    
    // Enviar el mensaje
    $resultat = $chatControlador->enviarMissatge($usuariId, $receptorId, $contingut);
    
    // Devolver respuesta
    header('Content-Type: application/json');
    echo json_encode($resultat);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Mètode no permès']);
}
?>