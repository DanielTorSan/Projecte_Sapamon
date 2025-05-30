<?php
class AmicsModel {
    private $connexio;
    
    public function __construct($connexio) {
        $this->connexio = $connexio;
    }
    
    /**
     * Get friends list for a user - modified to use 'acceptat' instead of 'acceptada'
     */
    public function getAmics($usuariId) {
        try {
            // Debug log
            error_log("Getting friends for user ID: $usuariId");
            
            $sql = "SELECT u.id_usuari, u.nom_usuari, u.avatar, u.ultima_connexio, u.estat, a.estat AS amistat_estat
                    FROM amistats_usuaris a
                    JOIN usuaris u ON u.id_usuari = a.amic_id
                    WHERE a.usuari_id = ? AND a.estat = 'acceptat'
                    
                    UNION
                    
                    SELECT u.id_usuari, u.nom_usuari, u.avatar, u.ultima_connexio, u.estat, a.estat AS amistat_estat
                    FROM amistats_usuaris a
                    JOIN usuaris u ON u.id_usuari = a.usuari_id
                    WHERE a.amic_id = ? AND a.estat = 'acceptat'";
            
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("ii", $usuariId, $usuariId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            // Debug log the result count
            error_log("Found " . $result->num_rows . " friends for user ID: " . $usuariId);
            
            // Debug log the actual friendship states
            $friends = $result->fetch_all(MYSQLI_ASSOC);
            foreach ($friends as $friend) {
                error_log("Friend ID: {$friend['id_usuari']}, State: {$friend['amistat_estat']}");
            }
            
            return $friends;
        } catch (Exception $e) {
            error_log("Error getting friends: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get pending friend requests for a user
     */
    public function getPendingRequests($usuariId) {
        try {
            $sql = "SELECT a.id_amistat as solicitud_id, u.id_usuari, u.nom_usuari, u.avatar 
                    FROM amistats_usuaris a
                    JOIN usuaris u ON u.id_usuari = a.usuari_id
                    WHERE a.amic_id = ? AND a.estat = 'pendent'";
            
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("i", $usuariId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            // Log the results for debugging
            error_log("Found " . $result->num_rows . " pending requests for user ID: " . $usuariId);
            
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting pending requests: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get pending friend requests for a user to display between sections
     */
    public function getSollicitudsPendents($usuariId) {
        try {
            $sql = "SELECT a.id_amistat as solicitud_id, u.id_usuari, u.nom_usuari, u.avatar 
                    FROM amistats_usuaris a
                    JOIN usuaris u ON u.id_usuari = a.usuari_id
                    WHERE a.amic_id = ? AND a.estat = 'pendent'";
            
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("i", $usuariId);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting pending requests: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Accept a friend request
     */
    public function acceptRequest($solicitudId, $usuariId) {
        try {
            // First verify that the request is actually for this user
            $checkSql = "SELECT * FROM amistats_usuaris WHERE id_amistat = ? AND amic_id = ?";
            $checkStmt = $this->connexio->prepare($checkSql);
            $checkStmt->bind_param("ii", $solicitudId, $usuariId);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows === 0) {
                error_log("Friend request not found or not for this user: Request ID $solicitudId, User ID $usuariId");
                return false; // Request not found or not for this user
            }
            
            $requestDetails = $result->fetch_assoc();
            error_log("Found request: " . json_encode($requestDetails));
            
            // Debug log the request details
            error_log("Accepting friend request ID: $solicitudId for user ID: $usuariId");
            
            // Update the request status to 'acceptat' - using exactly the value from the DB enum
            $sql = "UPDATE amistats_usuaris SET estat = 'acceptat', actualitzat_el = NOW() WHERE id_amistat = ?";
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("i", $solicitudId);
            
            $success = $stmt->execute();
            
            // Debug log the result
            error_log("Friend request update result: " . ($success ? "Success" : "Failed") . " - SQL Error: " . $this->connexio->error);
            
            return $success;
        } catch (Exception $e) {
            error_log("Error accepting request: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Reject a friend request
     */
    public function rejectRequest($solicitudId, $usuariId) {
        try {
            // First verify that the request is actually for this user
            $checkSql = "SELECT * FROM amistats_usuaris WHERE id_amistat = ? AND amic_id = ?";
            $checkStmt = $this->connexio->prepare($checkSql);
            $checkStmt->bind_param("ii", $solicitudId, $usuariId);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows === 0) {
                return false; // Request not found or not for this user
            }
            
            // Delete the friendship request instead of setting it to rejected state
            // since the table only supports 'pendent' and 'acceptat' states
            $sql = "DELETE FROM amistats_usuaris WHERE id_amistat = ?";
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("i", $solicitudId);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error rejecting request: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send a friend request - using correct column names
     */
    public function sendFriendRequest($usuariId, $amicId) {
        try {
            // Verify the user IDs are different
            if ($usuariId == $amicId) {
                return ['success' => false, 'error' => 'No puedes enviar solicitud a ti mismo.'];
            }
            
            // Check if both users exist
            $userCheck = $this->connexio->prepare("SELECT id_usuari FROM usuaris WHERE id_usuari IN (?, ?)");
            $userCheck->bind_param("ii", $usuariId, $amicId);
            $userCheck->execute();
            $result = $userCheck->get_result();
            
            if ($result->num_rows < 2) {
                return ['success' => false, 'error' => 'Uno o ambos usuarios no existen.'];
            }
            
            // Check if there's already a relationship between these users
            $checkSql = "SELECT * FROM amistats_usuaris 
                        WHERE (usuari_id = ? AND amic_id = ?) 
                        OR (usuari_id = ? AND amic_id = ?)";
            $checkStmt = $this->connexio->prepare($checkSql);
            $checkStmt->bind_param("iiii", $usuariId, $amicId, $amicId, $usuariId);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                if ($row['estat'] === 'pendent') {
                    if ($row['usuari_id'] == $usuariId) {
                        return ['success' => false, 'error' => 'Ya has enviado una solicitud a este usuario.'];
                    } else {
                        return ['success' => false, 'error' => 'Este usuario ya te ha enviado una solicitud. Revisa tus solicitudes pendientes.'];
                    }
                } elseif ($row['estat'] === 'acceptat') {
                    return ['success' => false, 'error' => 'Ya sois amigos.'];
                }
                return ['success' => false, 'error' => 'Ya existe una relación con este usuario.'];
            }
            
            // Create the friend request using correct column names
            $sql = "INSERT INTO amistats_usuaris (usuari_id, amic_id, estat, creat_el) 
                    VALUES (?, ?, 'pendent', NOW())";
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("ii", $usuariId, $amicId);
            
            if ($stmt->execute()) {
                return ['success' => true];
            } else {
                return ['success' => false, 'error' => 'Error al enviar la solicitud.'];
            }
        } catch (Exception $e) {
            error_log("Error sending friend request: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error del sistema: ' . $e->getMessage()];
        }
    }
    
    /**
     * Search for users to add as friends - improved search query
     */
    public function searchUsers($search, $usuariId) {
        try {
            $search = '%' . $search . '%';
            
            // Updated query to exclude users with pending or accepted relationships
            // Note: 'bloquejada' doesn't exist in the table schema, so it's removed
            $sql = "SELECT id_usuari, nom_usuari, avatar
                    FROM usuaris 
                    WHERE nom_usuari LIKE ? 
                    AND id_usuari != ?
                    AND id_usuari NOT IN (
                        SELECT amic_id FROM amistats_usuaris 
                        WHERE usuari_id = ? AND (estat = 'acceptat' OR estat = 'pendent')
                        UNION
                        SELECT usuari_id FROM amistats_usuaris 
                        WHERE amic_id = ? AND (estat = 'acceptat' OR estat = 'pendent')
                    )
                    LIMIT 10";
            
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("siii", $search, $usuariId, $usuariId, $usuariId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            // Debug logging
            error_log("Search for term '$search' returned " . $result->num_rows . " results");
            
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error searching users: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check if the user has any friends
     */
    public function hasAmics($usuariId) {
        try {
            $sql = "SELECT COUNT(*) as count FROM (
                        SELECT amic_id FROM amistats_usuaris WHERE usuari_id = ? AND estat = 'acceptat'
                        UNION
                        SELECT usuari_id FROM amistats_usuaris WHERE amic_id = ? AND estat = 'acceptat'
                    ) AS amics";
            
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("ii", $usuariId, $usuariId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            return $row['count'] > 0;
        } catch (Exception $e) {
            error_log("Error checking if user has friends: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Eliminar amistad entre dos usuarios
     */
    public function eliminarAmistat($usuariId, $amicId) {
        try {
            // Eliminar la amistad en cualquiera de las dos direcciones (usuario1->usuario2 o usuario2->usuario1)
            $sql = "DELETE FROM amistats_usuaris 
                    WHERE (usuari_id = ? AND amic_id = ? AND estat = 'acceptat')
                    OR (usuari_id = ? AND amic_id = ? AND estat = 'acceptat')";
            
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("iiii", $usuariId, $amicId, $amicId, $usuariId);
            $result = $stmt->execute();
            
            error_log("Eliminar amistad result: " . ($result ? "Success" : "Failed") . " - SQL Error: " . $this->connexio->error);
            
            return $result;
        } catch (Exception $e) {
            error_log("Error eliminando amistad: " . $e->getMessage());
            return false;
        }
    }
}
?>
