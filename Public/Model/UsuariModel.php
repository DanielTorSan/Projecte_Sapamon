<?php
class UsuariModel {
    private $connexio;
    
    public function __construct($connexio) {
        $this->connexio = $connexio;
    }
    
    public function getUsuariPerId($usuariId) {
        $query = "SELECT * FROM usuaris WHERE id_usuari = ?";
        $stmt = $this->connexio->prepare($query);
        $stmt->bind_param("i", $usuariId);
        $stmt->execute();
        $result = $stmt->get_result();
        $usuari = $result->fetch_assoc();
        $stmt->close();
        return $usuari;
    }
    
    public function getUsuariPerNom($nomUsuari) {
        $query = "SELECT * FROM usuaris WHERE nom_usuari = ?";
        $stmt = $this->connexio->prepare($query);
        $stmt->bind_param("s", $nomUsuari);
        $stmt->execute();
        $result = $stmt->get_result();
        $usuari = $result->fetch_assoc();
        $stmt->close();
        return $usuari;
    }
    
    public function crearUsuari($nomUsuari, $email, $contrasenya, $oauth_tipus = 'local', $oauth_id = null) {
        $hashContrasenya = password_hash($contrasenya, PASSWORD_DEFAULT);
        
        $query = "INSERT INTO usuaris (nom_usuari, correu, contrasenya, estat, rol, oauth_tipus, oauth_id) 
                  VALUES (?, ?, ?, 'actiu', 'usuari', ?, ?)";
        $stmt = $this->connexio->prepare($query);
        $stmt->bind_param("sssss", $nomUsuari, $email, $hashContrasenya, $oauth_tipus, $oauth_id);
        $exit = $stmt->execute();
        $usuariId = $exit ? $stmt->insert_id : false;
        $stmt->close();
        return $usuariId;
    }
    
    public function actualitzarUltimaConnexio($usuariId) {
        $query = "UPDATE usuaris SET ultima_connexio = NOW() WHERE id_usuari = ?";
        $stmt = $this->connexio->prepare($query);
        $stmt->bind_param("i", $usuariId);
        $exit = $stmt->execute();
        $stmt->close();
        return $exit;
    }
    
    public function existeixNomUsuari($nomUsuari) {
        $query = "SELECT COUNT(*) as total FROM usuaris WHERE nom_usuari = ?";
        $stmt = $this->connexio->prepare($query);
        $stmt->bind_param("s", $nomUsuari);
        $stmt->execute();
        $result = $stmt->get_result();
        $dades = $result->fetch_assoc();
        $stmt->close();
        return $dades['total'] > 0;
    }
    
    public function existeixEmail($email) {
        $query = "SELECT COUNT(*) as total FROM usuaris WHERE correu = ?";
        $stmt = $this->connexio->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $dades = $result->fetch_assoc();
        $stmt->close();
        return $dades['total'] > 0;
    }

    public function getUsuariPerOAuth($tipo, $id) {
        $query = "SELECT * FROM usuaris WHERE oauth_tipus = ? AND oauth_id = ?";
        $stmt = $this->connexio->prepare($query);
        $stmt->bind_param("ss", $tipo, $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $usuari = $result->fetch_assoc();
        $stmt->close();
        return $usuari;
    }

    public function guardarTokenRecordar($usuariId, $token, $expiracion) {
        $query = "UPDATE usuaris SET remember_token = ?, token_expiracio = FROM_UNIXTIME(?) WHERE id_usuari = ?";
        $stmt = $this->connexio->prepare($query);
        $stmt->bind_param("sii", $token, $expiracion, $usuariId);
        $exit = $stmt->execute();
        $stmt->close();
        return $exit;
    }
    
    public function getUsuariPerNomToken($nomUsuari, $token) {
        $query = "SELECT * FROM usuaris WHERE nom_usuari = ? AND remember_token = ? AND token_expiracio > NOW()";
        $stmt = $this->connexio->prepare($query);
        $stmt->bind_param("ss", $nomUsuari, $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $usuari = $result->fetch_assoc();
        $stmt->close();
        return $usuari;
    }

    public function getUsuariPerEmail($email) {
        $query = "SELECT * FROM usuaris WHERE correu = ?";
        $stmt = $this->connexio->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $usuari = $result->fetch_assoc();
        $stmt->close();
        return $usuari;
    }
    
    public function guardarTokenRecuperacio($usuariId, $token, $expiracio) {
        $query = "UPDATE usuaris SET reset_token = ?, reset_expiracio = ? WHERE id_usuari = ?";
        $stmt = $this->connexio->prepare($query);
        $stmt->bind_param("ssi", $token, $expiracio, $usuariId);
        $exit = $stmt->execute();
        $stmt->close();
        return $exit;
    }
    
    public function verificarTokenRecuperacio($token) {
        $query = "SELECT COUNT(*) as count FROM usuaris WHERE reset_token = ? AND reset_expiracio > NOW()";
        $stmt = $this->connexio->prepare($query);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row['count'] > 0;
    }
    
    public function getUsuariPerTokenRecuperacio($token) {
        $query = "SELECT * FROM usuaris WHERE reset_token = ? AND reset_expiracio > NOW()";
        $stmt = $this->connexio->prepare($query);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $usuari = $result->fetch_assoc();
        $stmt->close();
        return $usuari;
    }
    
    public function actualitzarContrasenya($usuariId, $novaContrasenya) {
        $query = "UPDATE usuaris SET contrasenya = ? WHERE id_usuari = ?";
        $stmt = $this->connexio->prepare($query);
        $stmt->bind_param("si", $novaContrasenya, $usuariId);
        $exit = $stmt->execute();
        $stmt->close();
        return $exit;
    }
    
    public function invalidarTokenRecuperacio($token) {
        $query = "UPDATE usuaris SET reset_token = NULL, reset_expiracio = NULL WHERE reset_token = ?";
        $stmt = $this->connexio->prepare($query);
        $stmt->bind_param("s", $token);
        $exit = $stmt->execute();
        $stmt->close();
        return $exit;
    }

    /**
     * Inicia sessió d'un usuari
     */
    public function iniciarSessio($nom_usuari, $contrasenya) {
        try {
            // Buscar l'usuari pel nom d'usuari en lloc de correu electrònic
            $stmt = $this->connexio->prepare("SELECT * FROM usuaris WHERE nom_usuari = ?");
            $stmt->bind_param("s", $nom_usuari);
            $stmt->execute();
            $result = $stmt->get_result();
            $usuari = $result->fetch_assoc();
            
            // Verificar si l'usuari existeix
            if (!$usuari) {
                return ['error' => 'Nom d\'usuari o contrasenya incorrectes.'];
            }
            
            // Verificar la contrasenya
            if (!password_verify($contrasenya, $usuari['contrasenya'])) {
                return ['error' => 'Nom d\'usuari o contrasenya incorrectes.'];
            }
            
            // Verificar l'estat de l'usuari
            if ($usuari['estat'] !== 'actiu') {
                return ['error' => 'El compte no està actiu. Contacta amb l\'administrador.'];
            }
            
            // Actualitzar l'última connexió
            $stmt = $this->connexio->prepare("UPDATE usuaris SET ultima_connexio = NOW() WHERE id_usuari = ?");
            $stmt->bind_param("i", $usuari['id_usuari']);
            $stmt->execute();
            
            return [
                'id_usuari' => $usuari['id_usuari'],
                'nom_usuari' => $usuari['nom_usuari'],
                'rol' => $usuari['rol'],
                'avatar' => $usuari['avatar'] // Include avatar in return data
            ];
        } catch (Exception $e) {
            return ['error' => 'Error al iniciar sessió: ' . $e->getMessage()];
        }
    }
    
    /**
     * Registra un nou usuari al sistema
     */
    public function registrarUsuari($nom_usuari, $correu, $contrasenya) {
        try {
            // Comprovar si el nom d'usuari ja existeix
            $stmt = $this->connexio->prepare("SELECT id_usuari FROM usuaris WHERE nom_usuari = ?");
            $stmt->bind_param("s", $nom_usuari);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                return ['error' => 'El nom d\'usuari ja existeix.'];
            }
            
            // Comprovar si el correu ja existeix
            $stmt = $this->connexio->prepare("SELECT id_usuari FROM usuaris WHERE correu = ?");
            $stmt->bind_param("s", $correu);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                return ['error' => 'El correu electrònic ja està registrat.'];
            }
            
            // Xifrar la contrasenya
            $hash_contrasenya = password_hash($contrasenya, PASSWORD_DEFAULT);
            
            // Establir avatar per defecte
            $avatar_defecte = "Youngster.png";
            
            // Inserir el nou usuari
            $stmt = $this->connexio->prepare("INSERT INTO usuaris (nom_usuari, correu, contrasenya, estat, rol, avatar, creat_el) VALUES (?, ?, ?, 'actiu', 'usuari', ?, NOW())");
            $stmt->bind_param("ssss", $nom_usuari, $correu, $hash_contrasenya, $avatar_defecte);
            
            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'id_usuari' => $this->connexio->insert_id,
                    'nom_usuari' => $nom_usuari,
                    'avatar' => $avatar_defecte
                ];
            } else {
                return ['error' => 'Error en registrar l\'usuari.'];
            }
        } catch (Exception $e) {
            return ['error' => 'Error al registrar: ' . $e->getMessage()];
        }
    }
    
    /**
     * Guarda el token de "Recorda'm"
     */
    public function guardarRememberToken($usuari_id, $token) {
        try {
            $stmt = $this->connexio->prepare("UPDATE usuaris SET remember_token = ? WHERE id_usuari = ?");
            $stmt->bind_param("si", $token, $usuari_id);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error al guardar el token de recordar: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verificar el token de "Recorda'm"
     */
    public function verificarRememberToken($token) {
        try {
            $stmt = $this->connexio->prepare("SELECT * FROM usuaris WHERE remember_token = ?");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_assoc();
        } catch (Exception $e) {
            error_log("Error al verificar el token de recordar: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Actualitza l'avatar d'un usuari
     */
    public function actualitzarAvatar($usuari_id, $avatar) {
        try {
            $stmt = $this->connexio->prepare("UPDATE usuaris SET avatar = ? WHERE id_usuari = ?");
            $stmt->bind_param("si", $avatar, $usuari_id);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error al actualitzar l'avatar: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualiza el equipo principal del usuario
     * 
     * @param int $usuariId ID del usuario
     * @param int $equipId ID del equipo seleccionado como principal
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function actualitzarEquipPrincipal($usuariId, $equipId) {
        try {
            // Verificar que el equipo pertenece al usuario
            $stmt = $this->connexio->prepare("
                SELECT id_equip FROM equips 
                WHERE id_equip = ? AND usuari_id = ? AND guardado = 1
            ");
            $stmt->bind_param("ii", $equipId, $usuariId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            // Si no existe el equipo o no pertenece al usuario, retornar falso
            if ($result->num_rows === 0) {
                return false;
            }
            
            // Actualizar el equipo principal del usuario
            $stmt = $this->connexio->prepare("
                UPDATE usuaris 
                SET equip_principal = ? 
                WHERE id_usuari = ?
            ");
            $stmt->bind_param("ii", $equipId, $usuariId);
            $success = $stmt->execute();
            $stmt->close();
            
            return $success;
        } catch (Exception $e) {
            error_log("Error al actualizar el equipo principal: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene el ID del equipo principal del usuario
     * 
     * @param int $usuariId ID del usuario
     * @return int|null ID del equipo principal o null si no tiene
     */
    public function getEquipPrincipal($usuariId) {
        try {
            $stmt = $this->connexio->prepare("
                SELECT equip_principal FROM usuaris 
                WHERE id_usuari = ?
            ");
            $stmt->bind_param("i", $usuariId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $stmt->close();
                return $row['equip_principal'];
            }
            
            $stmt->close();
            return null;
        } catch (Exception $e) {
            error_log("Error al obtener el equipo principal: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtener un usuario por su ID
     * 
     * @param int $usuarioId ID del usuario
     * @return array|null Datos del usuario o null si no existe
     */
    public function getUserById($usuarioId) {
        return $this->getUsuariPerId($usuarioId);
    }
}
?>
