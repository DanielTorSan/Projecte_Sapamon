<?php
/**
 * Controlador para actualizar el equipo principal del usuario
 * Procesa solicitudes AJAX para cambiar el equipo principal
 */

// Iniciar sesión si no está iniciada
session_start();

// Verificar que el usuario está autenticado
if (!isset($_SESSION['usuari_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Usuario no autenticado'
    ]);
    exit;
}

// Comprobar que se ha recibido el ID del equipo
if (!isset($_POST['equip_id']) || !is_numeric($_POST['equip_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'ID de equipo no válido'
    ]);
    exit;
}

$usuariId = $_SESSION['usuari_id'];
$equipId = (int)$_POST['equip_id'];

// Cargar la configuración que ya incluye la conexión a la base de datos
require_once __DIR__ . '/../Model/configuracio.php';
// Ahora $connexio ya está disponible desde configuracio.php

// Cargar el controlador de usuario
require_once __DIR__ . '/UsuariControlador.php';
$usuariControlador = new UsuariControlador($connexio);

// Intentar actualizar el equipo principal
$result = $usuariControlador->actualitzarEquipPrincipal($usuariId, $equipId);

// Devolver respuesta
if ($result) {
    echo json_encode([
        'success' => true,
        'message' => 'Equip principal actualitzat correctament'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'No s\'ha pogut actualitzar l\'equip principal'
    ]);
}

// No es necesario cerrar la conexión aquí, ya que se gestiona en el script principal
?>