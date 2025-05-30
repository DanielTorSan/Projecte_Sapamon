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

// Incluir archivos necesarios - Corregir rutas para la nueva ubicación
require_once __DIR__ . "/../../../Model/configuracio.php";
require_once __DIR__ . "/../../../Model/ChatModel.php";

// Inicializar modelo
$chatModel = new ChatModel($connexio);

// Obtener el ID del usuario actual
$usuariId = $_SESSION['usuari_id'];

// Procesar la solicitud
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Obtener mensajes no leídos para el usuario
        $noLlegits = $chatModel->getMissatgesNoLlegits($usuariId);
        
        // Obtener los chat recientes si se solicitan
        $includeChats = isset($_GET['include_chats']) && $_GET['include_chats'] === 'true';
        $chatsRecents = $includeChats ? $chatModel->getChatsRecents($usuariId) : [];
        
        // Devolver respuesta
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'no_llegits' => $noLlegits,
            'total' => array_sum($noLlegits),
            'chats_recents' => $chatsRecents
        ]);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'error' => 'Error al obtener mensajes: ' . $e->getMessage()
        ]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Mètode no permès']);
}
?>