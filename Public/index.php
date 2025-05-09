<?php
// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
require_once "Model/configuracio.php";
require_once "Model/AvatarModel.php";

// Initialize the Avatar model
$avatarModel = new AvatarModel($connexio);
$defaultAvatar = $avatarModel->getDefaultAvatar(); // Changed from getFirstAvatar to getDefaultAvatar

// Determinar qué página cargar
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

switch ($page) {
    case 'equips':
        // Cargar el controlador de equipos
        require_once "Controlador/EquipControlador.php";
        $equipControlador = new EquipControlador($connexio);
        $equipControlador->mostrarGestorEquips();
        break;
    
    default:
        // Página principal (dashboard)
        include 'Vista/Inici_Vista.php';
        break;
}
?>
