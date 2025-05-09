<?php
require_once __DIR__ . '/../Model/AmicsModel.php';

class AmicsControlador {
    private $amicsModel;
    
    public function __construct($connexio) {
        $this->amicsModel = new AmicsModel($connexio);
    }
    
    /**
     * Get friends list for the current user
     */
    public function getAmics($usuariId) {
        return $this->amicsModel->getAmics($usuariId);
    }
    
    /**
     * Check if user has friends
     */
    public function hasAmics($usuariId) {
        return $this->amicsModel->hasAmics($usuariId);
    }
    
    /**
     * Get pending friend requests
     */
    public function getPendingRequests($usuariId) {
        return $this->amicsModel->getPendingRequests($usuariId);
    }
    
    /**
     * Get pending friend requests for display between sections
     */
    public function getSollicitudsPendents($usuariId) {
        return $this->amicsModel->getSollicitudsPendents($usuariId);
    }
    
    /**
     * Process friend request action (accept/reject)
     */
    public function gestionaAmistat($solicitudId, $usuariId, $accio) {
        // Debug log
        error_log("Controller - gestionaAmistat: Handling $accio for request ID $solicitudId by user $usuariId");
        
        try {
            if ($accio === 'acceptar') {
                // Call the model's acceptRequest method to update the status to 'acceptada'
                $result = $this->amicsModel->acceptRequest($solicitudId, $usuariId);
                
                // Debug the result
                error_log("Accept friendship result: " . ($result ? "Success" : "Failure"));
                return $result;
            } 
            else if ($accio === 'rebutjar') {
                // Call the model's rejectRequest method
                $result = $this->amicsModel->rejectRequest($solicitudId, $usuariId);
                
                // Debug the result
                error_log("Reject friendship result: " . ($result ? "Success" : "Failure"));
                return $result;
            }
            
            // If we get here, the action wasn't recognized
            error_log("Unknown friendship action: $accio");
            return false;
        } catch (Exception $e) {
            error_log("Error in gestionaAmistat: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send a friend request
     */
    public function sendFriendRequest($usuariId, $amicId) {
        return $this->amicsModel->sendFriendRequest($usuariId, $amicId);
    }
    
    /**
     * Search for potential friends
     */
    public function searchUsers($searchTerm, $usuariId) {
        if (empty($searchTerm) || strlen($searchTerm) < 2) {
            return [];
        }
        
        return $this->amicsModel->searchUsers($searchTerm, $usuariId);
    }
    
    /**
     * Accept a friend request directly
     */
    public function acceptRequest($solicitudId, $usuariId) {
        error_log("Controller: Accepting friend request ID: $solicitudId for user: $usuariId");
        return $this->amicsModel->acceptRequest($solicitudId, $usuariId);
    }
    
    /**
     * Reject a friend request directly
     */
    public function rejectRequest($solicitudId, $usuariId) {
        error_log("Controller: Rejecting friend request ID: $solicitudId for user: $usuariId");
        return $this->amicsModel->rejectRequest($solicitudId, $usuariId);
    }
    
    /**
     * Get online status label based on user's last connection
     */
    public function getEstatLabel($estat, $ultima_connexio) {
        if ($estat === 'inactiu') {
            return 'Offline';
        }
        
        if (empty($ultima_connexio)) {
            return 'Offline';
        }
        
        $last = strtotime($ultima_connexio);
        $now = time();
        $diff = $now - $last;
        
        if ($diff < 300) { // 5 minutes
            return 'Online';
        } else if ($diff < 1800) { // 30 minutes
            return 'Ocupat';
        } else {
            return 'Offline';
        }
    }
    
    /**
     * Eliminar amistad
     */
    public function eliminarAmistat($usuariId, $amicId) {
        error_log("Controller: Eliminando amistad entre usuario $usuariId y amigo $amicId");
        return $this->amicsModel->eliminarAmistat($usuariId, $amicId);
    }
}

// Endpoint para consultas AJAX
if (isset($_GET['action']) && $_GET['action'] === 'check_pending_requests') {
    session_start();
    require_once '../Model/configuracio.php';
    
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['usuari_id'])) {
        echo json_encode(['success' => false, 'message' => 'No has iniciado sesión']);
        exit;
    }
    
    $amicsControlador = new AmicsControlador($connexio);
    $solicitudes = $amicsControlador->getSollicitudsPendents($_SESSION['usuari_id']);
    
    echo json_encode([
        'success' => true,
        'solicitudes' => $solicitudes
    ]);
    exit;
}
// Endpoint para obtener la lista de amigos vía AJAX
else if (isset($_GET['action']) && $_GET['action'] === 'get_friends_list') {
    session_start();
    require_once '../Model/configuracio.php';
    
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['usuari_id'])) {
        echo json_encode(['success' => false, 'message' => 'No has iniciado sesión']);
        exit;
    }
    
    $amicsControlador = new AmicsControlador($connexio);
    $amigos = $amicsControlador->getAmics($_SESSION['usuari_id']);
    
    // Añadir información de estado a cada amigo
    foreach ($amigos as &$amigo) {
        $estatLabel = $amicsControlador->getEstatLabel($amigo['estat'], $amigo['ultima_connexio'] ?? null);
        $amigo['estat_label'] = $estatLabel;
        
        // Clase CSS basada en el estado
        switch ($estatLabel) {
            case 'Online':
                $amigo['estat_class'] = 'online';
                break;
            case 'Ocupat':
                $amigo['estat_class'] = 'busy';
                break;
            default:
                $amigo['estat_class'] = 'offline';
        }
    }
    
    echo json_encode([
        'success' => true,
        'amigos' => $amigos
    ]);
    exit;
}
?>
