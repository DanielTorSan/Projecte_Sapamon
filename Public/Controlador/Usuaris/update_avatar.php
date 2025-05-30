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
require_once __DIR__ . "/../../Model/configuracio.php";
require_once __DIR__ . "/UsuariControlador.php";

// Obtener el ID del usuario actual
$usuariId = $_SESSION['usuari_id'];

// Procesar la solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener el avatar seleccionado
    $avatar = isset($_POST['avatar']) ? $_POST['avatar'] : '';
    
    if (empty($avatar)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'No s\'ha seleccionat cap avatar']);
        exit;
    }
    
    // Verificar que el archivo existe (usando path con minúsculas)
    $avatarPath = "../../Vista/assets/img/avatars/" . $avatar;
    if (!file_exists($avatarPath)) {
        // Intento alternativo con mayúsculas si no se encuentra
        $avatarPathAlt = "../../Vista/assets/img/Avatars/" . $avatar;
        if (!file_exists($avatarPathAlt)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'El fitxer d\'avatar no existeix']);
            exit;
        }
    }
    
    // Actualizar avatar en la base de datos
    $usuariControlador = new UsuariControlador($connexio);
    $result = $usuariControlador->updateAvatar($usuariId, $avatar);
    
    if ($result) {
        // Actualizar la sesión
        $_SESSION['avatar'] = $avatar;
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Error al actualitzar l\'avatar']);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Mètode no permès']);
}
?>