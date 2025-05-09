<?php
// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir la configuración y el controlador necesario
require_once __DIR__ . '/../Model/configuracio.php';
require_once __DIR__ . '/RecuperacioControlador.php';

// Verificar si hay datos POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $novaContrasenya = $_POST['nova_contrasenya'] ?? '';
    $confirmarContrasenya = $_POST['confirmar_contrasenya'] ?? '';
    
    // Verificar que los campos no estén vacíos
    if (empty($token) || empty($novaContrasenya) || empty($confirmarContrasenya)) {
        $_SESSION['error_restablir'] = "Tots els camps són obligatoris.";
        header("Location: ../Vista/RestablirContrasenya_Vista.php?token=$token");
        exit;
    }
    
    // Instanciar el controlador
    $recuperacioControlador = new RecuperacioControlador($connexio);
    
    // Verificar si el token es válido
    if (!$recuperacioControlador->verificarToken($token)) {
        $_SESSION['error_recuperacio'] = "L'enllaç no és vàlid o ha expirat. Si us plau, sol·licita un nou enllaç.";
        header('Location: ../Vista/RecuperacioContrasenya_Vista.php');
        exit;
    }
    
    // Procesar el restablecimiento de contraseña
    if ($recuperacioControlador->restablirContrasenya($token, $novaContrasenya, $confirmarContrasenya)) {
        $_SESSION['exit_login'] = "La teva contrasenya s'ha restablert correctament. Ja pots iniciar sessió.";
        header('Location: ../index.php');
        exit;
    } else {
        // El error ya se establece dentro del método restablirContrasenya
        header("Location: ../Vista/RestablirContrasenya_Vista.php?token=$token");
        exit;
    }
} else {
    // Si no hay datos POST, redirigir al inicio
    header('Location: ../index.php');
    exit;
}
?>
