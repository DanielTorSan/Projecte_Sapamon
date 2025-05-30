<?php
require_once __DIR__ . '/../../Model/GoogleAuthModel.php';

class GoogleAuthControlador {
    private $googleAuthModel;
    private $connexio;
    
    public function __construct($connexio) {
        $this->connexio = $connexio;
        $this->googleAuthModel = new GoogleAuthModel($connexio);
    }
    
    /**
     * Redirecciona al usuario a la página de autenticación de Google
     * Este método se llama desde GoogleAuth.php
     */
    public function iniciarGoogle() {
        // Verificar si la sesión ya está iniciada
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Obtener la URL de autenticación de Google
        $auth_url = $this->googleAuthModel->getAuthUrl();
        
        // Redireccionar al usuario
        header('Location: ' . $auth_url);
        exit;
    }
    
    /**
     * Procesa el callback de Google después de la autenticación
     * Este método se llama desde GoogleCallback.php
     */
    public function processarCallback() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_GET['code'])) {
            $_SESSION['error_login'] = "Error d'autenticació amb Google";
            header('Location: ../../Vista/Auth_Vista.php');
            exit;
        }
        
        // Obtener token de acceso
        $token_info = $this->googleAuthModel->getAccessToken($_GET['code']);
        
        if (!isset($token_info['access_token'])) {
            $_SESSION['error_login'] = "Error en obtenir el token d'accés";
            header('Location: ../../Vista/Auth_Vista.php');
            exit;
        }
        
        // Obtener información del usuario
        $user_info = $this->googleAuthModel->getUserInfo($token_info['access_token']);
        
        if (!isset($user_info['email'])) {
            $_SESSION['error_login'] = "No s'ha pogut obtenir la informació de l'usuari";
            header('Location: ../../Vista/Auth_Vista.php');
            exit;
        }
        
        // Login o registro del usuario
        $usuari = $this->googleAuthModel->loginOrRegisterGoogleUser($user_info);
        
        if (!$usuari) {
            $_SESSION['error_login'] = "No s'ha pogut crear o actualitzar l'usuari";
            header('Location: ../../Vista/Auth_Vista.php');
            exit;
        }
        
        // Iniciar sesión
        $_SESSION['usuari_id'] = $usuari['id_usuari'];
        $_SESSION['nom_usuari'] = $usuari['nom_usuari'];
        $_SESSION['exit_login'] = "Has iniciat sessió correctament amb Google.";
        
        // Redireccionar a la página principal
        header('Location: ../../index.php');
        exit;
    }
    
    /**
     * Método para manejar directamente la solicitud de callback de Google
     * Este método puede ser llamado directamente desde la URL
     */
    public function handleCallback() {
        // Incluir la configuración
        require_once __DIR__ . '/../../Model/configuracio.php';
        
        // Procesar el callback
        $this->processarCallback();
    }
    
    /**
     * Manejador principal para cuando este controlador es llamado directamente
     */
    public function handleRequest() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Iniciar el flujo de autenticación de Google
        $this->iniciarGoogle();
    }
}

// Si este archivo se llama directamente, iniciar el flujo de autenticación
if (basename($_SERVER['SCRIPT_FILENAME']) == basename(__FILE__)) {
    require_once __DIR__ . '/../../Model/configuracio.php';
    $controller = new GoogleAuthControlador($connexio);
    $controller->handleRequest();
}
?>
