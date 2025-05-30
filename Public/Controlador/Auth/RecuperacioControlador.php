<?php
require_once __DIR__ . '/../../Model/UsuariModel.php';

// Correct paths to PHPMailer files in the libs directory
require_once __DIR__ . '/../../libs/PHPMailer/Exception.php';
require_once __DIR__ . '/../../libs/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../../libs/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class RecuperacioControlador {
    private $usuariModel;
    private $connexio;
    
    public function __construct($connexio) {
        $this->connexio = $connexio;
        $this->usuariModel = new UsuariModel($connexio);
    }
    
    // Nuevo método para procesar todas las acciones relacionadas con recuperación
    public function procesarAccio($accio) {
        switch ($accio) {
            case 'recuperarContrasenya':
                $this->mostrarFormulariRecuperacio();
                break;
                
            case 'enviarRecuperacio':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $email = $_POST['email'] ?? '';
                    $this->enviarEnllacRecuperacio($email);
                }
                header('Location: index.php?accio=recuperarContrasenya');
                exit;
                break;
                
            case 'restablirContrasenya':
                if (isset($_GET['token'])) {
                    $token = $_GET['token'];
                    $this->mostrarFormulariRestabliment($token);
                } else {
                    header('Location: index.php');
                    exit;
                }
                break;
                
            case 'processarRestabliment':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $token = $_POST['token'];
                    $novaContrasenya = $_POST['nova_contrasenya'];
                    $confirmarContrasenya = $_POST['confirmar_contrasenya'];
                    $this->restablirContrasenya($token, $novaContrasenya, $confirmarContrasenya);
                } else {
                    header('Location: index.php');
                    exit;
                }
                break;
                
            default:
                header('Location: index.php');
                exit;
                break;
        }
    }
    
    // Método para mostrar el formulario de recuperación
    public function mostrarFormulariRecuperacio() {
        include __DIR__ . '/../../Vista/RecuperacioContrasenya_Vista.php';
    }
    
    public function enviarEnllacRecuperacio($email) {
        // Verificar si la sesión ya está iniciada
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Verificar si existe un usuario con ese email
        $usuari = $this->usuariModel->getUsuariPerEmail($email);
        
        // Check if the user exists
        if (!$usuari) {
            $_SESSION['error_recuperacio'] = "No existeix cap compte amb aquest correu electrònic.";
            return false;
        }
        
        // Generar token único
        $token = bin2hex(random_bytes(32));
        $expiracio = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Guardar token en la base de datos (usando los nombres de columna correctos)
        $stmt = $this->connexio->prepare("UPDATE usuaris SET reset_token = ?, reset_expiracio = ? WHERE id_usuari = ?");
        $guardat = $stmt->execute([$token, $expiracio, $usuari['id_usuari']]);
        
        if (!$guardat) {
            $_SESSION['error_recuperacio'] = "Ha ocorregut un error. Si us plau, intenta-ho de nou més tard.";
            return false;
        }
        
        // Construir el enlace de recuperación con URL correcta para danitorres.cat
        $enlace = "http://www.danitorres.cat/Vista/RestablirContrasenya_Vista.php?token=$token";
        
        // Use the working email function from Practiques
        $resultado = $this->enviarCorreu($usuari['nom_usuari'], $email, $enlace);
        
        if ($resultado) {
            $_SESSION['exit_recuperacio'] = "S'ha enviat un correu amb les instruccions per restablir la teva contrasenya.";
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Función para enviar correo, usando una configuración más confiable
     */
    private function enviarCorreu($nomC, $emailC, $resetLink) {
        // En lugar de usar SMTP, usaremos la función mail() de PHP
        // Esto suele funcionar mejor en muchos entornos de hosting compartido
        
        $subject = 'Recuperar Contrasenya - Sapamon';
        
        // Creamos un mensaje HTML sencillo pero efectivo
        $message = "
        <html>
        <head>
            <title>Recuperar la teva contrasenya</title>
        </head>
        <body>
            <h2>Hola, $nomC!</h2>
            <p>Has sol·licitat restablir la teva contrasenya per al compte de Sapamon.</p>
            <p>Si us plau, fes clic en el següent enllaç per restablir la teva contrasenya:</p>
            <p><a href='$resetLink'>$resetLink</a></p>
            <p>Si no has sol·licitat restablir la contrasenya, simplement ignora aquest correu.</p>
            <p>Aquest enllaç caducarà en 24 hores.</p>
            <p>Gràcies,<br>L'equip de Sapamon</p>
        </body>
        </html>
        ";
        
        // Cabeceras para correo HTML
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: Sapamon <no-reply@danitorres.cat>\r\n";
        
        // Intentamos enviar el correo usando la función nativa de PHP
        $success = mail($emailC, $subject, $message, $headers);
        
        if ($success) {
            // Log success
            $logDir = __DIR__ . '/../../logs';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0777, true);
            }
            file_put_contents(
                $logDir . '/email_success_' . time() . '.log',
                "Email sent successfully to {$emailC} at " . date('Y-m-d H:i:s')
            );
            
            return true;
        } else {
            // Si mail() falla, intentamos con PHPMailer pero con una configuración alternativa
            return $this->enviarCorreoAlternativo($nomC, $emailC, $resetLink);
        }
    }
    
    /**
     * Método alternativo para enviar correo usando PHPMailer con configuración más básica
     */
    private function enviarCorreoAlternativo($nomC, $emailC, $resetLink) {
        $mail = new PHPMailer(true);
        try {
            // Configuración básica sin SMTP
            $mail->isMail(); // Usar la función mail() de PHP
            $mail->CharSet = 'UTF-8';
            $mail->setFrom('no-reply@danitorres.cat', 'Sapamon');
            $mail->addAddress($emailC);
            
            // Contenido del correo
            $mail->isHTML(true);
            $mail->Subject = 'Recuperar Contrasenya - Sapamon';
            
            // Contenido HTML simple
            $mail->Body = "
            <html>
            <head>
                <title>Recuperar la teva contrasenya</title>
            </head>
            <body>
                <h2>Hola, $nomC!</h2>
                <p>Has sol·licitat restablir la teva contrasenya per al compte de Sapamon.</p>
                <p>Si us plau, fes clic en el següent enllaç per restablir la teva contrasenya:</p>
                <p><a href='$resetLink'>$resetLink</a></p>
                <p>Si no has sol·licitat restablir la contrasenya, simplement ignora aquest correu.</p>
                <p>Aquest enllaç caducarà en 24 hores.</p>
                <p>Gràcies,<br>L'equip de Sapamon</p>
            </body>
            </html>
            ";
            $mail->AltBody = "Hola {$nomC}, fes clic aquí per restablir la teva contrasenya: {$resetLink}";
            
            // Enviar el correo
            $mail->send();
            
            // Log success
            $logDir = __DIR__ . '/../../logs';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0777, true);
            }
            file_put_contents(
                $logDir . '/email_success_' . time() . '.log',
                "Email sent successfully to {$emailC} using alternative method at " . date('Y-m-d H:i:s')
            );
            
            return true;
        } catch (Exception $e) {
            // Create detailed error log
            $logDir = __DIR__ . '/../../logs';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0777, true);
            }
            
            $errorDetails = "Error sending email to {$emailC} using alternative method:\n";
            $errorDetails .= "Message: " . $e->getMessage() . "\n";
            $errorDetails .= "PHPMailer Error: " . $mail->ErrorInfo . "\n";
            $errorDetails .= "Time: " . date('Y-m-d H:i:s') . "\n";
            
            file_put_contents(
                $logDir . '/email_error_' . time() . '.log',
                $errorDetails
            );
            
            // Establecemos el mensaje de error pero devolvemos true para evitar el error 500
            // y permitir al usuario recibir un mensaje más amigable
            $_SESSION['error_recuperacio'] = "S'ha produït un error en enviar el correu. Contacta amb l'administrador.";
            
            return false;
        }
    }
    
    /**
     * Verifica si un token de recuperació és vàlid i no ha expirat
     * @param string $token El token a verificar
     * @return array|bool Les dades de l'usuari si el token és vàlid, false en cas contrari
     */
    public function verificarToken($token) {
        // Verificar si el token és vàlid i no ha expirat
        $stmt = $this->connexio->prepare("SELECT * FROM usuaris WHERE reset_token = ? AND reset_expiracio > NOW()");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        return $user ? $user : false;
    }
    
    /**
     * Restableix la contrasenya d'un usuari amb el token de recuperació
     * @param string $token El token de recuperació
     * @param string $novaContrasenya La nova contrasenya
     * @param string $confirmarContrasenya La confirmació de la contrasenya
     * @return bool True si s'ha pogut restablir, False en cas contrari
     */
    public function restablirContrasenya($token, $novaContrasenya, $confirmarContrasenya = null) {
        // Si es proporciona la confirmació, verifica que les contrasenyes coincideixen
        if ($confirmarContrasenya !== null && $novaContrasenya !== $confirmarContrasenya) {
            $_SESSION['error_restablir'] = "Les contrasenyes no coincideixen.";
            return false;
        }
        
        // Verificar que el token és vàlid
        $usuari = $this->verificarToken($token);
        
        if (!$usuari) {
            $_SESSION['error_restablir'] = "L'enllaç no és vàlid o ha expirat.";
            return false;
        }
        
        // Actualitzar la contrasenya i eliminar el token
        $hashContrasenya = password_hash($novaContrasenya, PASSWORD_DEFAULT);
        $stmt = $this->connexio->prepare("UPDATE usuaris SET contrasenya = ?, reset_token = NULL, reset_expiracio = NULL WHERE id_usuari = ?");
        $actualitzat = $stmt->execute([$hashContrasenya, $usuari['id_usuari']]);
        
        return $actualitzat;
    }
    
    public function mostrarFormulariRestabliment($token) {
        // Verificar si la sesión ya está iniciada
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Verificar si el token es válido
        $tokenValid = $this->usuariModel->verificarTokenRecuperacio($token);
        
        if (!$tokenValid) {
            $_SESSION['error_recuperacio'] = "L'enllaç no és vàlid o ha expirat. Si us plau, sol·licita un nou enllaç.";
            header('Location: index.php?accio=recuperarContrasenya');
            exit;
        }
        
        // Enviar el token a la vista
        include __DIR__ . '/../../Vista/RestablirContrasenya_Vista.php';
    }
}
?>
