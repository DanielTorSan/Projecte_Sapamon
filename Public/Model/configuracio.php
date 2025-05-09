<?php
// Configuración de la conexión a la base de datos Sapamon
// Configuración usando mysqli (actual)
$host = 'bbdd.danitorres.cat'; // Nota: usa bbdd.danitorres.cat en lugar de adminbbdd.dondominio.com
$usuari = 'ddb239237';
$contrasenya = 'P@ssw0rd'; 
$bbdd = 'ddb239237';

// Crear conexión
$connexio = new mysqli($host, $usuari, $contrasenya, $bbdd);

// Comprobar conexión
if ($connexio->connect_error) {
    die("Error de connexió: " . $connexio->connect_error);
}

// Configurar codificación de caracteres
$connexio->set_charset("utf8mb4");

// Definir constantes para rutas
define('RUTA_ARREL', dirname(__DIR__));
define('RUTA_CONTROLADORS', RUTA_ARREL . '/Controlador');
define('RUTA_MODELS', RUTA_ARREL . '/Model');
define('RUTA_VISTES', RUTA_ARREL . '/Vista');
define('RUTA_ASSETS', RUTA_ARREL . '/Vista/assets');
?>
