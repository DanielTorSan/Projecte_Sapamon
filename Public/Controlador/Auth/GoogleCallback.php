<?php
// Incluir el controlador de autenticación de Google
require_once 'GoogleAuthControlador.php';

// Incluir la configuración de la base de datos
require_once __DIR__ . '/../Model/configuracio.php';

// Iniciar el controlador de autenticación de Google
$googleAuth = new GoogleAuthControlador($connexio);

// Procesar el callback de Google
$googleAuth->processarCallback();
?>
