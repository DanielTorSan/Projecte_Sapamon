<?php
class ChatModel {
    private $connexio;
    
    public function __construct($connexio) {
        $this->connexio = $connexio;
    }
    
    /**
     * Enviar un mensaje de chat
     */
    public function enviarMissatge($emissorId, $receptorId, $contingut) {
        try {
            $sql = "INSERT INTO missatges_chat (emissor_id, receptor_id, contingut) 
                    VALUES (?, ?, ?)";
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("iis", $emissorId, $receptorId, $contingut);
            
            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'id_missatge' => $stmt->insert_id,
                    'data_enviament' => date('Y-m-d H:i:s')
                ];
            } else {
                return ['success' => false, 'error' => 'Error al enviar el mensaje'];
            }
        } catch (Exception $e) {
            error_log("Error al enviar mensaje: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error del sistema: ' . $e->getMessage()];
        }
    }
    
    /**
     * Obtener los mensajes de chat entre dos usuarios
     */
    public function getMissatges($usuariId, $amicId, $limit = 50, $offset = 0) {
        try {
            $sql = "SELECT m.*, 
                    u1.nom_usuari as emissor_nom, u1.avatar as emissor_avatar,
                    u2.nom_usuari as receptor_nom, u2.avatar as receptor_avatar
                    FROM missatges_chat m
                    JOIN usuaris u1 ON m.emissor_id = u1.id_usuari
                    JOIN usuaris u2 ON m.receptor_id = u2.id_usuari
                    WHERE (m.emissor_id = ? AND m.receptor_id = ?) 
                       OR (m.emissor_id = ? AND m.receptor_id = ?)
                    ORDER BY m.data_enviament DESC
                    LIMIT ? OFFSET ?";
            
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("iiiiii", $usuariId, $amicId, $amicId, $usuariId, $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $messages = $result->fetch_all(MYSQLI_ASSOC);
            
            // Marcar como leídos los mensajes recibidos
            $this->marcarMissatgesLlegits($usuariId, $amicId);
            
            return array_reverse($messages); // Para mostrarlos en orden cronológico (de más antiguos a más recientes)
        } catch (Exception $e) {
            error_log("Error obteniendo mensajes: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Marcar mensajes como leídos
     */
    public function marcarMissatgesLlegits($receptorId, $emissorId) {
        try {
            $sql = "UPDATE missatges_chat SET llegit = 1
                    WHERE receptor_id = ? AND emissor_id = ? AND llegit = 0";
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("ii", $receptorId, $emissorId);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error al marcar mensajes como leídos: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener el número de mensajes no leídos para un usuario
     */
    public function getMissatgesNoLlegits($usuariId) {
        try {
            $sql = "SELECT emissor_id, COUNT(*) as total 
                    FROM missatges_chat 
                    WHERE receptor_id = ? AND llegit = 0 
                    GROUP BY emissor_id";
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("i", $usuariId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $unreadMessages = [];
            while ($row = $result->fetch_assoc()) {
                $unreadMessages[$row['emissor_id']] = $row['total'];
            }
            
            return $unreadMessages;
        } catch (Exception $e) {
            error_log("Error contando mensajes no leídos: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener chat recientes de un usuario
     */
    public function getChatsRecents($usuariId, $limit = 10) {
        try {
            $sql = "SELECT 
                      u.id_usuari, u.nom_usuari, u.avatar, u.estat, 
                      m.contingut as ultim_missatge, 
                      m.data_enviament as data_ultim_missatge,
                      m.emissor_id as emissor_ultim_missatge,
                      COUNT(CASE WHEN m.receptor_id = ? AND m.llegit = 0 THEN 1 END) as no_llegits
                    FROM 
                      (SELECT 
                        CASE WHEN emissor_id = ? THEN receptor_id ELSE emissor_id END as id_usuari,
                        MAX(data_enviament) as max_data
                      FROM missatges_chat
                      WHERE emissor_id = ? OR receptor_id = ?
                      GROUP BY id_usuari) as chats
                    JOIN usuaris u ON u.id_usuari = chats.id_usuari
                    JOIN missatges_chat m ON 
                      ((m.emissor_id = ? AND m.receptor_id = u.id_usuari) OR 
                       (m.emissor_id = u.id_usuari AND m.receptor_id = ?)) AND
                      m.data_enviament = chats.max_data
                    GROUP BY u.id_usuari
                    ORDER BY m.data_enviament DESC
                    LIMIT ?";
            
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("iiiiiii", $usuariId, $usuariId, $usuariId, $usuariId, $usuariId, $usuariId, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error obteniendo chats recientes: " . $e->getMessage());
            return [];
        }
    }
}
?>