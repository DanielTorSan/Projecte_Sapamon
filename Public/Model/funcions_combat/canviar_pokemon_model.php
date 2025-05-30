<?php
/**
 * Modelo para gestionar el cambio de Pokémon en combate
 */
class CanviarPokemonModel {
    private $connexio;
    
    /**
     * Constructor
     */
    public function __construct($connexio) {
        $this->connexio = $connexio;
    }
    
    /**
     * Registra la elección de cambio de Pokémon para el turno actual
     * 
     * @param int $batallaId ID de la batalla
     * @param int $usuariId ID del usuario que realiza el cambio
     * @param int $pokemonId ID del Pokémon seleccionado para entrar al combate
     * @return bool Éxito de la operación
     */
    public function registrarCanviPokemon($batallaId, $usuariId, $pokemonId) {
        try {
            // Verificamos que el Pokémon pertenece al usuario y está en esta batalla
            $sql = "SELECT id FROM estat_pokemon_batalla 
                    WHERE batalla_id = ? AND usuario_id = ? AND equip_pokemon_id = ? AND esta_debilitado = 0";
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("iii", $batallaId, $usuariId, $pokemonId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                // El Pokémon no pertenece al usuario, no está en la batalla, o está debilitado
                return false;
            }
            
            // Si el Pokémon es válido, registramos la acción en la tabla accions_torn
            require_once __DIR__ . '/../Combat_Model.php';
            $combatModel = new Combat_Model($this->connexio);
            
            return $combatModel->registrarAccioTorn($batallaId, $usuariId, 'canvi_pokemon', null, $pokemonId);
            
        } catch (Exception $e) {
            error_log("Error al registrar cambio de Pokémon: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ejecuta el cambio de Pokémon en la batalla
     * 
     * @param int $batallaId ID de la batalla
     * @param int $torn Número del turno
     * @param int $usuariId ID del usuario que realiza el cambio
     * @param int $pokemonId ID del Pokémon que entrará al combate
     * @return array Resultado de la ejecución del cambio
     */
    public function executarCanviPokemon($batallaId, $torn, $usuariId, $pokemonId) {
        try {
            // Obtener el ID del estado del Pokémon seleccionado
            $sql = "SELECT id, equip_pokemon_id FROM estat_pokemon_batalla 
                    WHERE batalla_id = ? AND usuario_id = ? AND equip_pokemon_id = ?";
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("iii", $batallaId, $usuariId, $pokemonId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return ['success' => false, 'message' => 'Pokémon no encontrado en la batalla'];
            }
            
            $pokemonEntrada = $result->fetch_assoc();
            $nouPokemonId = $pokemonEntrada['id'];
            
            // Determinar cuál es el campo en la tabla de batalla según el usuario
            $campoPokemonActivo = "pokemon_activo_1";
            $sqlUsuariCondition = "usuari1_id = ?";
            
            $sql = "SELECT usuari1_id, usuari2_id, pokemon_activo_1, pokemon_activo_2 FROM batalles WHERE id_batalla = ?";
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("i", $batallaId);
            $stmt->execute();
            $batalla = $stmt->get_result()->fetch_assoc();
            
            if ($batalla['usuari2_id'] == $usuariId) {
                $campoPokemonActivo = "pokemon_activo_2";
                $sqlUsuariCondition = "usuari2_id = ?";
            }
            
            // Obtener el ID del Pokémon actual para el registro
            $pokemonActualId = $batalla[$campoPokemonActivo];
            
            // Actualizar el Pokémon activo en la tabla de batalla
            $sql = "UPDATE batalles SET $campoPokemonActivo = ? WHERE id_batalla = ? AND $sqlUsuariCondition";
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("iii", $nouPokemonId, $batallaId, $usuariId);
            $stmt->execute();
            
            if ($stmt->affected_rows === 0) {
                return ['success' => false, 'message' => 'Error al cambiar el Pokémon activo'];
            }
            
            // Obtener información del Pokémon para el mensaje
            $sql = "SELECT ep.malnom FROM estat_pokemon_batalla epb 
                    JOIN equip_pokemon ep ON epb.equip_pokemon_id = ep.id_equip_pokemon 
                    WHERE epb.id = ?";
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("i", $nouPokemonId);
            $stmt->execute();
            $pokemonNom = $stmt->get_result()->fetch_assoc()['malnom'];
            
            // Registrar la acción en el log de batalla
            $sql = "INSERT INTO registres_batalla (batalla_id, turno, usuario_id, tipo_accion, pokemon_origen_id, pokemon_objetivo_id, texto)
                   VALUES (?, ?, ?, 'cambio', ?, ?, ?)";
            $stmt = $this->connexio->prepare($sql);
            $texto = "El entrenador ha cambiado a " . $pokemonNom;
            $stmt->bind_param("iiiiss", $batallaId, $torn, $usuariId, $pokemonActualId, $nouPokemonId, $texto);
            $stmt->execute();
            
            return [
                'success' => true,
                'message' => 'Cambio de Pokémon realizado',
                'pokemon_anterior_id' => $pokemonActualId,
                'pokemon_nuevo_id' => $nouPokemonId,
                'pokemon_nombre' => $pokemonNom
            ];
            
        } catch (Exception $e) {
            error_log("Error al ejecutar cambio de Pokémon: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al cambiar Pokémon: ' . $e->getMessage()];
        }
    }
}
?>