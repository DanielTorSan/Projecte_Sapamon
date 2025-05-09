<?php
class AvatarModel {
    private $connexio;
    
    public function __construct($connexio) {
        $this->connexio = $connexio;
    }
    
    /**
     * Get default avatar filename (Youngster - ID 6)
     */
    public function getDefaultAvatar() {
        try {
            // Get Youngster avatar with ID 6
            $stmt = $this->connexio->prepare("SELECT file_name FROM avatars WHERE id = 6 LIMIT 1");
            $stmt->execute();
            $result = $stmt->get_result();
            $avatar = $result->fetch_assoc();
            
            return $avatar ? $avatar['file_name'] : 'Youngster.png';
        } catch (Exception $e) {
            error_log("Error getting default avatar: " . $e->getMessage());
            return 'Youngster.png'; // Hardcoded fallback
        }
    }
    
    /**
     * Get all avatars from the database
     */
    public function getAllAvatars() {
        try {
            $stmt = $this->connexio->prepare("SELECT * FROM avatars ORDER BY id ASC");
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting all avatars: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get avatar filename by id
     */
    public function getAvatarById($id) {
        try {
            $stmt = $this->connexio->prepare("SELECT file_name FROM avatars WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $avatar = $result->fetch_assoc();
            
            return $avatar ? $avatar['file_name'] : $this->getDefaultAvatar();
        } catch (Exception $e) {
            error_log("Error getting avatar by id: " . $e->getMessage());
            return $this->getDefaultAvatar();
        }
    }
}
?>
