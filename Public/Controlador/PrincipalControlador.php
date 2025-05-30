<?php
require_once __DIR__ . '/../Model/UsuariModel.php';

class PrincipalControlador {
    private $usuariModel;
    
    public function __construct($connexio) {
        $this->usuariModel = new UsuariModel($connexio);
    }
    
    public function mostrarPaginaInicial() {
        // Verificar si la sesión ya está iniciada antes de llamar a session_start()
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['usuari_id'])) {
            header('Location: index.php?accio=tauler');
            exit;
        }
        
        // Cargar la vista de inicio
        include __DIR__ . '/../Vista/Inici_Vista.php';
    }
    
    public function mostrarTauler() {
        // Verificar si la sesión ya está iniciada antes de llamar a session_start()
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['usuari_id'])) {
            header('Location: index.php');
            exit;
        }
        
        // Obtener datos del usuario
        $usuari = $this->usuariModel->getUsuariPerId($_SESSION['usuari_id']);
        
        // Cargar la vista de dashboard
        include __DIR__ . '/../Vista/Tauler_Vista.php';
    }
    
    public function mostrarRecuperacioContrasenya() {
        // Verificar si la sesión ya está iniciada
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Cargar la vista de recuperación de contraseña
        include __DIR__ . '/../Vista/RecuperacioContrasenya_Vista.php';
    }
}
?>
