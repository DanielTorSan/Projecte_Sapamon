<?php
class AuthControlador {
    private $connexio;
    private $usuariModel;
    
    public function __construct($connexio) {
        $this->connexio = $connexio;
        $this->usuariModel = new UsuariModel($connexio);
    }
    
    // Login functionality
    public function procesarLogin($nomUsuari, $contrasenya, $recordar = false) {
        // Login logic here
    }
    
    // Register functionality
    public function procesarRegistre($nomUsuari, $email, $contrasenya, $confirmarContrasenya) {
        // Registration logic here
    }
    
    // Logout functionality
    public function procesarLogout() {
        // Logout logic here
    }
    
    /**
     * Prepara los datos necesarios para la vista de autenticación
     * 
     * @return array Datos formateados para la vista
     */
    public function prepararDatosVista() {
        // Iniciar sesión si no está ya iniciada
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Verificar si hay mensajes de error o éxito
        $datos = [
            'error_login' => isset($_SESSION['error_login']) ? $_SESSION['error_login'] : '',
            'error_register' => isset($_SESSION['error_register']) ? $_SESSION['error_register'] : '',
            'exit_register' => isset($_SESSION['exit_register']) ? $_SESSION['exit_register'] : '',
            'exit_login' => isset($_SESSION['exit_login']) ? $_SESSION['exit_login'] : ''
        ];
        
        // Limpiar las variables de sesión después de usarlas
        unset($_SESSION['error_login'], $_SESSION['error_register'], $_SESSION['exit_register'], $_SESSION['exit_login']);
        
        return $datos;
    }
    
    /**
     * Determina qué tab debe estar activo inicialmente
     * 
     * @return string Tab que debe estar activo ('login' o 'register')
     */
    public function getActiveTab() {
        // Si se pasa un parámetro de registro en la URL, activar esa pestaña
        return isset($_GET['registre']) && $_GET['registre'] === 'true' ? 'register' : 'login';
    }
}
?>
