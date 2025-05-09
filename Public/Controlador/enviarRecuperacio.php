<?php
// Ensure no output before headers
ob_start();

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir la configuración y el controlador necesario
require_once __DIR__ . '/../Model/configuracio.php';
require_once __DIR__ . '/RecuperacioControlador.php';

// Verificar que se ha enviado un email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = $_POST['email'];
    
    // Instanciar el controlador de recuperación
    $recuperacioControlador = new RecuperacioControlador($connexio);
    
    // Enviar enlace de recuperación
    $result = $recuperacioControlador->enviarEnllacRecuperacio($email);
    
    // Log the result for debugging
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    file_put_contents(
        $logDir . '/recovery_attempt_' . time() . '.log',
        "Recovery attempt for {$email}: " . ($result ? 'SUCCESS' : 'FAILED') . " at " . date('Y-m-d H:i:s')
    );
}

// Clear any output buffered before sending headers
ob_end_clean();

// Redireccionar a la URL correcta en el dominio danitorres.cat para restablecer la contraseña
header('Location: http://www.danitorres.cat/Vista/RestablirContrasenya_Vista.php');
exit;
?>
