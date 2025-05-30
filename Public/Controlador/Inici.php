<?php
class Inici {
    private $connexio;
    
    public function __construct($connexio) {
        $this->connexio = $connexio;
    }
    
    public function procesarAccio() {
        // Determinar la acción solicitada
        $accio = isset($_GET['accio']) ? $_GET['accio'] : 'inici';
        
        // Procesar la acción
        switch ($accio) {
            case 'inici':
                $this->mostrarPaginaInicial();
                break;
            case 'login':
                $this->procesarLogin();
                break;
            case 'registre':
                $this->procesarRegistre();
                break;
            case 'tauler':
                $this->mostrarTauler();
                break;
            case 'logout':
                $this->procesarLogout();
                break;
            default:
                $this->mostrarPaginaInicial();
                break;
        }
    }
    
    // Mostrar la página principal
    public function mostrarPaginaInicial() {
        // Puedes cargar datos necesarios para la vista aquí
        include 'Vista/Inici_Vista.php';
    }
    
    // Processar login
    public function procesarLogin() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nomUsuari = $_POST['nom_usuari'] ?? '';
            $contrasenya = $_POST['contrasenya'] ?? '';
            $recordar = isset($_POST['remember']);
            
            // Aquí iría la lógica de autenticación
            
            $_SESSION['error_login'] = "Funcionalidad de login no implementada aún";
            header('Location: index.php');
            exit;
        } else {
            include 'Vista/Auth_Vista.php';
        }
    }
    
    // Processar registre
    public function procesarRegistre() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nomUsuari = $_POST['nom_usuari'] ?? '';
            $email = $_POST['email'] ?? '';
            $contrasenya = $_POST['contrasenya'] ?? '';
            $confirmarContrasenya = $_POST['confirmar_contrasenya'] ?? '';
            
            // Aquí iría la lógica de registro
            
            $_SESSION['error_registre'] = "Funcionalidad de registro no implementada aún";
            header('Location: index.php');
            exit;
        } else {
            $_GET['registre'] = true; // Para activar la pestaña de registro
            include 'Vista/Auth_Vista.php';
        }
    }
    
    // Mostrar panel de control de usuario
    public function mostrarTauler() {
        // Verificar si el usuario está autenticado
        if (!isset($_SESSION['usuari_id'])) {
            header('Location: index.php');
            exit;
        }
        
        // Cargar datos necesarios para el panel
        include 'Vista/Tauler_Vista.php';
    }
    
    // Processar cierre de sesión
    public function procesarLogout() {
        // Destruir todas las variables de sesión
        $_SESSION = array();
        
        // Borrar la cookie de sesión
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destruir la sesión
        session_destroy();
        
        // Redirigir a la página principal
        header('Location: index.php');
        exit;
    }
}
?>
