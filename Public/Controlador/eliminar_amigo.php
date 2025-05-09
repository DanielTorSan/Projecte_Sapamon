<?php
session_start();
require_once '../Model/configuracio.php';
require_once 'AmicsControlador.php';

// Verificar que se ha iniciado sesión
if (!isset($_SESSION['usuari_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No has iniciado sesión']);
    exit;
}

// Verificar que se ha enviado la ID de amigo
if (!isset($_POST['amic_id']) || empty($_POST['amic_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No se ha especificado un amigo']);
    exit;
}

$usuariId = $_SESSION['usuari_id'];
$amicId = $_POST['amic_id'];

// Instanciar el controlador de amigos
$amicsControlador = new AmicsControlador($connexio);

// Intentar eliminar la amistad
$resultado = $amicsControlador->eliminarAmistat($usuariId, $amicId);

// Responder con el resultado
header('Content-Type: application/json');
if ($resultado) {
    echo json_encode(['success' => true, 'message' => 'Amigo eliminado correctamente']);
} else {
    echo json_encode(['success' => false, 'message' => 'No se ha podido eliminar al amigo']);
}
?>