<?php
require_once __DIR__ . '/../../../Model/ChatModel.php';

class ChatControlador {
    private $chatModel;
    
    public function __construct($connexio) {
        $this->chatModel = new ChatModel($connexio);
    }
    
    /**
     * Enviar un mensaje de chat
     */
    public function enviarMissatge($emissorId, $receptorId, $contingut) {
        // Validar que el contenido no esté vacío
        if (empty(trim($contingut))) {
            return ['success' => false, 'error' => 'El mensaje no puede estar vacío'];
        }
        
        // Validar que el emisor y receptor sean diferentes
        if ($emissorId === $receptorId) {
            return ['success' => false, 'error' => 'No puedes enviarte mensajes a ti mismo'];
        }
        
        return $this->chatModel->enviarMissatge($emissorId, $receptorId, $contingut);
    }
    
    /**
     * Obtener historial de mensajes entre dos usuarios
     */
    public function getHistorialMissatges($usuariId, $amicId, $limit = 50, $offset = 0) {
        return $this->chatModel->getMissatges($usuariId, $amicId, $limit, $offset);
    }
    
    /**
     * Marcar mensajes como leídos
     */
    public function marcarComoLlegits($usuariId, $amicId) {
        return $this->chatModel->marcarMissatgesLlegits($usuariId, $amicId);
    }
    
    /**
     * Obtener número de mensajes no leídos
     */
    public function getNoLlegitsCount($usuariId) {
        return $this->chatModel->getMissatgesNoLlegits($usuariId);
    }
    
    /**
     * Obtener lista de chats recientes
     */
    public function getChatsRecents($usuariId, $limit = 10) {
        return $this->chatModel->getChatsRecents($usuariId, $limit);
    }
    
    /**
     * Formatear el tiempo del mensaje para mostrar al usuario
     */
    public function formatearTiempoMensaje($fechaHora) {
        $timestamp = strtotime($fechaHora);
        $ahora = time();
        $diff = $ahora - $timestamp;
        
        if ($diff < 60) {
            return "Ahora mismo";
        } elseif ($diff < 3600) {
            $min = floor($diff / 60);
            return "Hace " . $min . " " . ($min == 1 ? "minuto" : "minutos");
        } elseif ($diff < 86400) {
            $horas = floor($diff / 3600);
            return "Hace " . $horas . " " . ($horas == 1 ? "hora" : "horas");
        } elseif (date('Y-m-d', $timestamp) === date('Y-m-d', strtotime('yesterday'))) {
            return "Ayer a las " . date('H:i', $timestamp);
        } else {
            return date('d/m/Y H:i', $timestamp);
        }
    }
}
?>