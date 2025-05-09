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
}
?>
