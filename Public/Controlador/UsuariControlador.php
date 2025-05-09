<?php
/**
 * Import necessary model
 * @see \UsuariModel
 */
require_once __DIR__ . '/../Model/UsuariModel.php';

/**
 * Class for managing user operations
 */
class UsuariControlador {
    /**
     * @var \UsuariModel User model instance
     */
    private $usuariModel;
    
    /**
     * @var \mysqli Database connection
     */
    private $connexio;
    
    /**
     * Constructor
     * 
     * @param \mysqli $connexio Database connection
     */
    public function __construct($connexio) {
        $this->connexio = $connexio;
        $this->usuariModel = new UsuariModel($connexio);
    }
    
    /**
     * Gestiona l'inici de sessió d'un usuari
     */
    public function login() {
        // Comprovar si s'ha enviat el formulari
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            // Obtenir i netejar les dades d'entrada
            $nom_usuari = trim($_POST['nom_usuari']); // Changed from email to username
            $contrasenya = trim($_POST['contrasenya']);
            $recordar = isset($_POST['remember-me']) ? true : false;
            
            // Intent d'inici de sessió
            $result = $this->usuariModel->iniciarSessio($nom_usuari, $contrasenya);
            
            if (isset($result['error'])) {
                // Si hi ha hagut un error, emmagatzemar el missatge i redirigir
                $_SESSION['error_login'] = $result['error'];
                header("Location: ../Vista/Auth_Vista.php");
                exit;
            }
            
            // Inici de sessió correcte, emmagatzemar dades de l'usuari a la sessió
            $_SESSION['usuari_id'] = $result['id_usuari'];
            $_SESSION['nom_usuari'] = $result['nom_usuari'];
            $_SESSION['avatar'] = $result['avatar']; // Store avatar in session
            
            // Si l'usuari ha seleccionat "Recordar-me", establir una cookie
            if ($recordar) {
                $tokenRemember = bin2hex(random_bytes(16));
                setcookie('remember_token', $tokenRemember, time() + 60*60*24*30, '/');
                
                // Guardar el token a la base de dades per validar-lo més tard
                $this->usuariModel->guardarRememberToken($result['id_usuari'], $tokenRemember);
            }
            
            // Redirigir a la pàgina principal
            header("Location: ../index.php");
            exit;
        }
        
        // Si s'ha accedit directament a aquest mètode sense enviar el formulari
        header("Location: ../Vista/Auth_Vista.php");
        exit;
    }
    
    /**
     * Gestiona el registre d'un nou usuari
     */
    public function registre() {
        // Comprovar si s'ha enviat el formulari
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            // Obtenir i netejar les dades d'entrada
            $nom_usuari = trim($_POST['nom_usuari']);
            $email = trim($_POST['email']);
            $contrasenya = trim($_POST['contrasenya']);
            $confirmar_contrasenya = trim($_POST['confirmar_contrasenya']);
            
            // Validar que les contrasenyes coincideixin
            if ($contrasenya !== $confirmar_contrasenya) {
                $_SESSION['error_register'] = "Les contrasenyes no coincideixen.";
                header("Location: ../Vista/Auth_Vista.php?registre=true");
                exit;
            }
            
            // Validar requisits de la contrasenya
            if (!$this->validarContrasenya($contrasenya)) {
                $_SESSION['error_register'] = "La contrasenya no compleix els requisits mínims.";
                header("Location: ../Vista/Auth_Vista.php?registre=true");
                exit;
            }
            
            // Intent de registre
            $result = $this->usuariModel->registrarUsuari($nom_usuari, $email, $contrasenya);
            
            if (isset($result['error'])) {
                // Si hi ha hagut un error, emmagatzemar el missatge i redirigir
                $_SESSION['error_register'] = $result['error'];
                header("Location: ../Vista/Auth_Vista.php?registre=true");
                exit;
            }
            
            // Registre correcte, mostrar missatge d'èxit i redirigir a l'inici de sessió
            $_SESSION['exit_register'] = "Registre realitzat correctament. Ja pots iniciar sessió.";
            header("Location: ../Vista/Auth_Vista.php");
            exit;
        }
        
        // Si s'ha accedit directament a aquest mètode sense enviar el formulari
        header("Location: ../Vista/Auth_Vista.php?registre=true");
        exit;
    }
    
    /**
     * Tanca la sessió d'un usuari
     */
    public function logout() {
        session_start();
        
        // Eliminar totes les variables de sessió
        session_unset();
        session_destroy();
        
        // Eliminar la cookie de "Recordar-me" si existeix
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        // Redirigir a la pàgina principal
        header("Location: ../index.php");
        exit;
    }
    
    /**
     * Comprova si la contrasenya compleix amb els requisits mínims
     */
    private function validarContrasenya($contrasenya) {
        // Mínim 6 caràcters
        if (strlen($contrasenya) < 6) {
            return false;
        }
        
        // Almenys una lletra minúscula, una majúscula i un número
        if (!preg_match('/[a-z]/', $contrasenya) || 
            !preg_match('/[A-Z]/', $contrasenya) || 
            !preg_match('/[0-9]/', $contrasenya)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Canvia l'avatar de l'usuari
     */
    public function canviarAvatar($usuariId, $nouAvatar) {
        // Validar que l'avatar existeix - usar ruta con minúsculas
        $avatarPath = "../Vista/assets/img/avatars/" . $nouAvatar;
        if (!file_exists($avatarPath)) {
            // Probar con mayúsculas como respaldo
            $avatarPathAlt = "../Vista/assets/img/Avatars/" . $nouAvatar;
            if (!file_exists($avatarPathAlt)) {
                return ['error' => 'L\'avatar seleccionat no existeix.'];
            }
        }
        
        // Actualitzar l'avatar a la base de dades
        $result = $this->usuariModel->actualitzarAvatar($usuariId, $nouAvatar);
        
        if ($result) {
            return ['success' => true, 'message' => 'Avatar actualitzat correctament.'];
        } else {
            return ['error' => 'No s\'ha pogut actualitzar l\'avatar.'];
        }
    }
    
    /**
     * Actualiza el avatar del usuario
     */
    public function updateAvatar($usuariId, $avatar) {
        $result = $this->canviarAvatar($usuariId, $avatar);
        return isset($result['success']) && $result['success'];
    }
    
    /**
     * Obtiene el equipo principal del usuario
     * 
     * @param int $usuariId ID del usuario
     * @return array|false Datos del equipo principal o false si no tiene
     */
    public function getEquipPrincipal($usuariId) {
        // Obtener el ID del equipo principal
        $equipId = $this->usuariModel->getEquipPrincipal($usuariId);
        
        // Si el usuario no tiene equipo principal, devolver false
        if (!$equipId) {
            return false;
        }
        
        // Cargar el modelo de equipos para obtener los datos completos
        require_once __DIR__ . '/../Model/EquipModel.php';
        /** @var EquipModel $equipModel */
        $equipModel = new EquipModel($this->connexio);
        
        // Obtener los datos del equipo
        return $equipModel->getEquipo($equipId);
    }
    
    /**
     * Actualiza el equipo principal del usuario
     * 
     * @param int $usuariId ID del usuario
     * @param int $equipId ID del equipo a establecer como principal
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function actualitzarEquipPrincipal($usuariId, $equipId) {
        return $this->usuariModel->actualitzarEquipPrincipal($usuariId, $equipId);
    }
    
    /**
     * Obtiene todos los equipos guardados del usuario para la selección de equipo principal
     * 
     * @param int $usuariId ID del usuario
     * @return array Lista de equipos guardados
     */
    public function getEquipsGuardats($usuariId) {
        require_once __DIR__ . '/../Model/EquipModel.php';
        /** @var EquipModel $equipModel */
        $equipModel = new EquipModel($this->connexio);
        return $equipModel->getEquiposGuardadosByUsuario($usuariId);
    }
    
    /**
     * Processa les diferents accions segons el paràmetre 'accio'
     */
    public function processarAccio() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_GET['accio'])) {
            switch ($_GET['accio']) {
                case 'login':
                    $this->login();
                    break;
                case 'registre':
                    $this->registre();
                    break;
                case 'logout':
                    $this->logout();
                    break;
                default:
                    header("Location: ../index.php");
                    exit;
            }
        } else {
            header("Location: ../index.php");
            exit;
        }
    }
}

// Si aquest arxiu s'executa directament (no s'inclou en un altre), processar l'acció
if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    require_once __DIR__ . '/../Model/configuracio.php';
    $controlador = new UsuariControlador($connexio);
    $controlador->processarAccio();
}
?>