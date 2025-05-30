<?php
class GoogleAuthModel {
    private $connexio;
    private $clientID;
    private $clientSecret;
    private $redirectUri;
    
    public function __construct($connexio) {
        $this->connexio = $connexio;
        
        // Configuración de Google OAuth con los datos proporcionados
        $this->clientID = '403185390895-ti6e6vpb0fisub7s76e99ik9vm0lgchd.apps.googleusercontent.com';
        $this->clientSecret = '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';
        $this->redirectUri = 'http://localhost/Projecte%20Pokemon/Controlador/GoogleCallback.php';
    }
    
    /**
     * Obtiene la URL de autenticación de Google
     */
    public function getAuthUrl() {
        $params = [
            'client_id' => $this->clientID,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'email profile',
            'access_type' => 'online',
        ];
        
        return 'https://accounts.google.com/o/oauth2/auth?' . http_build_query($params);
    }
    
    /**
     * Obtener el token de acceso usando el código de autorización
     */
    public function getAccessToken($code) {
        $url = 'https://oauth2.googleapis.com/token';
        
        $params = [
            'client_id' => $this->clientID,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code',
        ];
        
        // Preparar la solicitud cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log("Error en cURL al obtener token: " . $error);
            return [];
        }
        
        $token_info = json_decode($response, true);
        return $token_info ? $token_info : [];
    }
    
    /**
     * Obtener información del usuario usando el token de acceso
     */
    public function getUserInfo($accessToken) {
        $url = 'https://www.googleapis.com/oauth2/v2/userinfo';
        
        // Preparar la solicitud cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log("Error en cURL al obtener info de usuario: " . $error);
            return [];
        }
        
        $user_info = json_decode($response, true);
        return $user_info ? $user_info : [];
    }
    
    /**
     * Iniciar sesión o registrar un usuario de Google
     */
    public function loginOrRegisterGoogleUser($user_info) {
        // Verificar si el usuario ya existe en la base de datos
        // Usar método de mysqli en lugar de PDO
        $stmt = $this->connexio->prepare("SELECT * FROM usuaris WHERE correu = ?");
        $stmt->bind_param("s", $user_info['email']);
        $stmt->execute();
        $result = $stmt->get_result();
        $usuari = $result->fetch_assoc();
        
        if ($usuari) {
            // Si el usuario existe, actualizamos su información
            $stmt = $this->connexio->prepare("UPDATE usuaris SET ultima_connexio = NOW(), oauth_id = ? WHERE correu = ?");
            $stmt->bind_param("ss", $user_info['id'], $user_info['email']);
            $stmt->execute();
            return $usuari;
        } else {
            // Si el usuario no existe, lo registramos
            $username = $this->generateUniqueUsername($user_info['name'] ?? $user_info['email']);
            $randomPass = bin2hex(random_bytes(12));
            $hashedPassword = password_hash($randomPass, PASSWORD_DEFAULT);
            $estat = 'actiu';
            $rol = 'usuari';
            $oauth_tipus = 'google';
            $avatar = "Youngster.png"; // Default avatar (ID 6)
            
            // Ajustar columnas para que coincidan con la estructura real de la tabla
            $stmt = $this->connexio->prepare(
                "INSERT INTO usuaris (nom_usuari, correu, contrasenya, estat, rol, creat_el, ultima_connexio, oauth_tipus, oauth_id, avatar) 
                 VALUES (?, ?, ?, ?, ?, NOW(), NOW(), ?, ?, ?)"
            );
            $stmt->bind_param("ssssssss", $username, $user_info['email'], $hashedPassword, $estat, $rol, $oauth_tipus, $user_info['id'], $avatar);
            $stmt->execute();
            
            $id_usuari = $this->connexio->insert_id;
            
            return [
                'id_usuari' => $id_usuari,
                'nom_usuari' => $username,
                'correu' => $user_info['email']
            ];
        }
    }
    
    /**
     * Genera un nombre de usuario único basado en el nombre proporcionado
     */
    private function generateUniqueUsername($name) {
        // Eliminar caracteres especiales y espacios
        $username = preg_replace('/[^A-Za-z0-9]/', '', $name);
        $username = strtolower(substr($username, 0, 15)); // Limitar longitud
        $baseUsername = $username;
        
        // Verificar si el nombre de usuario ya existe usando mysqli
        $stmt = $this->connexio->prepare("SELECT COUNT(*) as count FROM usuaris WHERE nom_usuari = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $count = $row['count'];
        
        // Si el nombre de usuario ya existe, añadir un número al final
        $i = 1;
        while ($count > 0) {
            $username = $baseUsername . $i;
            $stmt = $this->connexio->prepare("SELECT COUNT(*) as count FROM usuaris WHERE nom_usuari = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $count = $row['count'];
            $i++;
        }
        
        return $username;
    }
}
?>
