<?php
require_once __DIR__ . '/../../Model/UsuariModel.php';

class AutenticacioControlador {
    private $usuariModel;
    
    public function __construct($connexio) {
        $this->usuariModel = new UsuariModel($connexio);
    }
    
    // Método para procesar login
    public function processarLogin($nomUsuari, $contrasenya, $remember = false) {
        $usuari = $this->usuariModel->getUsuariPerNom($nomUsuari);
        
        if ($usuari && password_verify($contrasenya, $usuari['contrasenya'])) {
            // Verificar si la cuenta está activa
            if ($usuari['estat'] === 'desactivat') {
                return false; // Cuenta desactivada
            }
            
            // Iniciar sesión
            $_SESSION['usuari_id'] = $usuari['id_usuari'];
            $_SESSION['nom_usuari'] = $usuari['nom_usuari'];
            
            // Actualizar última conexión
            $this->usuariModel->actualitzarUltimaConnexio($usuari['id_usuari']);
            
            // Si la opción "Recuérdame" está activada, guardar cookie
            if ($remember) {
                $this->guardarCookieRecordar($usuari['id_usuari'], $nomUsuari);
            }
            
            return true;
        }
        
        return false;
    }
    
    private function guardarCookieRecordar($usuariId, $nomUsuari) {
        // Encriptar los datos para mayor seguridad
        $token = bin2hex(random_bytes(32));
        $expiracion = time() + (30 * 24 * 60 * 60); // 30 días
        
        // Guardar el token en la base de datos
        $this->usuariModel->guardarTokenRecordar($usuariId, $token, $expiracion);
        
        // Configurar las cookies
        setcookie('remember_user', $nomUsuari, $expiracion, '/');
        setcookie('remember_token', $token, $expiracion, '/');
    }
    
    public function verificarCookieRecordar() {
        if (isset($_COOKIE['remember_user']) && isset($_COOKIE['remember_token'])) {
            $nomUsuari = $_COOKIE['remember_user'];
            $token = $_COOKIE['remember_token'];
            
            $usuari = $this->usuariModel->getUsuariPerNomToken($nomUsuari, $token);
            
            if ($usuari) {
                // Iniciar sesión
                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                }
                
                $_SESSION['usuari_id'] = $usuari['id_usuari'];
                $_SESSION['nom_usuari'] = $usuari['nom_usuari'];
                
                // Actualizar última conexión
                $this->usuariModel->actualitzarUltimaConnexio($usuari['id_usuari']);
                
                return true;
            }
        }
        
        return false;
    }
    
    // Método para procesar registro
    public function processarRegistre($nomUsuari, $email, $contrasenya, $confirmarContrasenya) {
        // Validar datos
        if ($contrasenya !== $confirmarContrasenya) {
            return ['exit' => false, 'missatge' => 'Les contrasenyes no coincideixen'];
        }
        
        if ($this->usuariModel->existeixNomUsuari($nomUsuari)) {
            return ['exit' => false, 'missatge' => 'El nom d\'usuari ja està en ús'];
        }
        
        if ($this->usuariModel->existeixEmail($email)) {
            return ['exit' => false, 'missatge' => 'L\'email ja està registrat'];
        }
        
        // Crear usuario
        $usuariId = $this->usuariModel->crearUsuari($nomUsuari, $email, $contrasenya);
        
        if ($usuariId) {
            return ['exit' => true, 'missatge' => 'Registre exitós'];
        } else {
            return ['exit' => false, 'missatge' => 'Error al registrar l\'usuari'];
        }
    }
    
    // Método para cerrar sesión
    public function tancarSessio() {
        // Destruir todas las variables de sesión
        $_SESSION = array();
        
        // Si se desea destruir la sesión completamente, borrar también la cookie de sesión
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Finalmente, destruir la sesión
        session_destroy();
        
        return true;
    }
}

// Código para manejar las solicitudes HTTP
if (basename($_SERVER['PHP_SELF']) == 'AutenticacioControlador.php') {
    // Iniciar sesión
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Cargar la configuración para obtener la conexión a la base de datos
    require_once __DIR__ . '/../../Model/configuracio.php';
    
    // Crear una instancia del controlador
    $auth = new AutenticacioControlador($connexio);
    
    // Determinar la acción a realizar según el parámetro 'accio'
    $accio = isset($_GET['accio']) ? $_GET['accio'] : '';
    
    switch ($accio) {
        case 'login':
            // Procesar el inicio de sesión
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $nomUsuari = isset($_POST['nom_usuari']) ? $_POST['nom_usuari'] : '';
                $contrasenya = isset($_POST['contrasenya']) ? $_POST['contrasenya'] : '';
                $remember = isset($_POST['remember-me']);
                
                if ($auth->processarLogin($nomUsuari, $contrasenya, $remember)) {
                    // Éxito al iniciar sesión, redirigir a la página de inicio
                    header('Location: /');
                    exit;
                } else {
                    // Error al iniciar sesión
                    $_SESSION['error_login'] = 'Nom d\'usuari o contrasenya incorrectes';
                    header('Location: /Vista/Auth_Vista.php');
                    exit;
                }
            }
            break;
            
        case 'registre':
            // Procesar el registro
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $nomUsuari = isset($_POST['nom_usuari']) ? $_POST['nom_usuari'] : '';
                $email = isset($_POST['email']) ? $_POST['email'] : '';
                $contrasenya = isset($_POST['contrasenya']) ? $_POST['contrasenya'] : '';
                $confirmarContrasenya = isset($_POST['confirmar_contrasenya']) ? $_POST['confirmar_contrasenya'] : '';
                
                $resultat = $auth->processarRegistre($nomUsuari, $email, $contrasenya, $confirmarContrasenya);
                
                if ($resultat['exit']) {
                    // Éxito al registrar
                    $_SESSION['exit_register'] = $resultat['missatge'];
                    header('Location: /Vista/Auth_Vista.php');
                    exit;
                } else {
                    // Error al registrar
                    $_SESSION['error_register'] = $resultat['missatge'];
                    header('Location: /Vista/Auth_Vista.php?registre=true');
                    exit;
                }
            }
            break;
            
        default:
            // Acción no reconocida
            header('Location: /Vista/Auth_Vista.php');
            exit;
    }
}
?>
