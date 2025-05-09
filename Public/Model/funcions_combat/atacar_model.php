<?php
/**
 * Modelo para gestionar los ataques en combates Pokémon
 */
class AtacarModel {
    private $connexio;
    
    /**
     * Constructor
     */
    public function __construct($connexio) {
        $this->connexio = $connexio;
    }
    
    /**
     * Registra un ataque elegido por el jugador para el turno actual
     * 
     * @param int $batallaId ID de la batalla
     * @param int $usuariId ID del usuario que realiza el ataque
     * @param int $movimentId ID del movimiento seleccionado
     * @return bool Éxito de la operación
     */
    public function registrarAtaque($batallaId, $usuariId, $movimentId) {
        try {
            // Primero verificamos que el movimiento pertenece a un Pokémon activo del usuario
            $sql = "SELECT m.id 
                    FROM moviments_pokemon_batalla m
                    JOIN estat_pokemon_batalla e ON m.estat_pokemon_id = e.id
                    WHERE e.batalla_id = ? AND e.usuario_id = ? AND m.movimiento_id = ?
                    AND (e.id = (SELECT pokemon_activo_1 FROM batalles WHERE id_batalla = ? AND usuari1_id = ?)
                         OR e.id = (SELECT pokemon_activo_2 FROM batalles WHERE id_batalla = ? AND usuari2_id = ?))";
            
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("iiiiiii", $batallaId, $usuariId, $movimentId, $batallaId, $usuariId, $batallaId, $usuariId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                // El movimiento no pertenece a un Pokémon activo del usuario
                return false;
            }
            
            // Si el movimiento es válido, registramos la acción en la tabla accions_torn
            require_once __DIR__ . '/../Combat_Model.php';
            $combatModel = new Combat_Model($this->connexio);
            
            return $combatModel->registrarAccioTorn($batallaId, $usuariId, 'moviment', $movimentId);
            
        } catch (Exception $e) {
            error_log("Error al registrar ataque: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ejecuta un ataque en la batalla
     * 
     * @param int $batallaId ID de la batalla
     * @param int $torn Número del turno
     * @param int $atacantId ID del estado del Pokémon atacante
     * @param int $objectiuId ID del estado del Pokémon objetivo
     * @param int $movimentId ID del movimiento
     * @return array Resultado de la ejecución del ataque
     */
    public function executarAtaque($batallaId, $torn, $atacantId, $objectiuId, $movimentId) {
        try {
            // Obtener información del movimiento
            $sql = "SELECT * FROM moviments_pokemon_batalla WHERE id = ?";
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("i", $movimentId);
            $stmt->execute();
            $moviment = $stmt->get_result()->fetch_assoc();
            
            if (!$moviment) {
                return ['success' => false, 'message' => 'Movimiento no encontrado'];
            }
            
            // Verificar PP restantes
            if ($moviment['pp_actuales'] <= 0) {
                return ['success' => false, 'message' => 'No quedan PP para este movimiento'];
            }
            
            // Obtener información del atacante
            $sql = "SELECT * FROM estat_pokemon_batalla WHERE id = ?";
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("i", $atacantId);
            $stmt->execute();
            $atacant = $stmt->get_result()->fetch_assoc();
            
            // Obtener información del objetivo
            $sql = "SELECT * FROM estat_pokemon_batalla WHERE id = ?";
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("i", $objectiuId);
            $stmt->execute();
            $objectiu = $stmt->get_result()->fetch_assoc();
            
            // Por ahora, solo registraremos que se ha realizado el ataque sin aplicar daño
            // La implementación completa del cálculo de daño se hará en una versión posterior
            
            // Restar 1 PP al movimiento usado
            $sql = "UPDATE moviments_pokemon_batalla SET pp_actuales = pp_actuales - 1 WHERE id = ?";
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("i", $movimentId);
            $stmt->execute();
            
            // Registrar la acción en el log de batalla
            $sql = "INSERT INTO registres_batalla (batalla_id, turno, usuario_id, tipo_accion, pokemon_origen_id, pokemon_objetivo_id, movimiento_id, texto)
                   VALUES (?, ?, ?, 'ataque', ?, ?, ?, ?)";
            $stmt = $this->connexio->prepare($sql);
            $texto = "El Pokémon ha usado " . $moviment['nombre'];
            $stmt->bind_param("iiiiiss", $batallaId, $torn, $atacant['usuario_id'], $atacantId, $objectiuId, $movimentId, $texto);
            $stmt->execute();
            
            return [
                'success' => true,
                'message' => 'Ataque registrado',
                'movimiento' => $moviment['nombre'],
                'atacante_id' => $atacantId,
                'objetivo_id' => $objectiuId
            ];
            
        } catch (Exception $e) {
            error_log("Error al ejecutar ataque: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al ejecutar el ataque: ' . $e->getMessage()];
        }
    }
}
?>