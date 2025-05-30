<?php
/**
 * Modelo para procesar los turnos de combate Pokémon
 */
class ProcessarTornModel {
    private $connexio;
    
    /**
     * Constructor
     */
    public function __construct($connexio) {
        $this->connexio = $connexio;
    }
    
    /**
     * Procesa un turno completo cuando ambos jugadores han elegido sus acciones
     * 
     * @param int $batallaId ID de la batalla
     * @param int $tornActual Número del turno actual
     * @return array Resultado del procesamiento del turno
     */
    public function processarTorn($batallaId, $tornActual) {
        try {
            // Obtenemos la información básica de la batalla
            $sql = "SELECT usuari1_id, usuari2_id, pokemon_activo_1, pokemon_activo_2 FROM batalles WHERE id_batalla = ?";
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("i", $batallaId);
            $stmt->execute();
            $batalla = $stmt->get_result()->fetch_assoc();
            
            if (!$batalla) {
                return ['success' => false, 'message' => 'Batalla no encontrada'];
            }
            
            $usuari1Id = $batalla['usuari1_id'];
            $usuari2Id = $batalla['usuari2_id'];
            $pokemon1Id = $batalla['pokemon_activo_1'];
            $pokemon2Id = $batalla['pokemon_activo_2'];
            
            // Obtenemos las acciones elegidas por ambos jugadores para este turno
            $sql = "SELECT * FROM accions_torn WHERE batalla_id = ? AND torn = ? ORDER BY timestamp ASC";
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("ii", $batallaId, $tornActual);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $accions = [];
            while ($accio = $result->fetch_assoc()) {
                $accions[] = $accio;
            }
            
            if (count($accions) != 2) {
                return ['success' => false, 'message' => 'No se han registrado ambas acciones para este turno'];
            }
            
            // Determinar la prioridad de las acciones
            $accionsOrdenades = $this->determinarPrioritat($accions, $batalla);
            
            // Ejecutar las acciones en orden de prioridad
            $resultats = [];
            $batallaActualitzada = $batalla;
            
            foreach ($accionsOrdenades as $accio) {
                // Si es un cambio de Pokémon, lo procesamos
                if ($accio['tipus_accio'] === 'canvi_pokemon') {
                    require_once __DIR__ . '/canviar_pokemon_model.php';
                    $canviarPokemonModel = new CanviarPokemonModel($this->connexio);
                    $resultat = $canviarPokemonModel->executarCanviPokemon(
                        $batallaId, 
                        $tornActual, 
                        $accio['usuari_id'], 
                        $accio['pokemon_id']
                    );
                    $resultats[] = $resultat;
                    
                    // Actualizar datos de la batalla si el cambio fue exitoso
                    if ($resultat['success']) {
                        if ($accio['usuari_id'] == $usuari1Id) {
                            $batallaActualitzada['pokemon_activo_1'] = $resultat['pokemon_nuevo_id'];
                        } else if ($accio['usuari_id'] == $usuari2Id) {
                            $batallaActualitzada['pokemon_activo_2'] = $resultat['pokemon_nuevo_id'];
                        }
                    }
                }
                // Si es un ataque, lo procesamos
                else if ($accio['tipus_accio'] === 'moviment') {
                    require_once __DIR__ . '/atacar_model.php';
                    $atacarModel = new AtacarModel($this->connexio);
                    
                    // Determinar atacante y objetivo
                    $atacantId = ($accio['usuari_id'] == $usuari1Id) ? $batallaActualitzada['pokemon_activo_1'] : $batallaActualitzada['pokemon_activo_2'];
                    $objectiuId = ($accio['usuari_id'] == $usuari1Id) ? $batallaActualitzada['pokemon_activo_2'] : $batallaActualitzada['pokemon_activo_1'];
                    
                    $resultat = $atacarModel->executarAtaque(
                        $batallaId, 
                        $tornActual, 
                        $atacantId, 
                        $objectiuId, 
                        $accio['moviment_id']
                    );
                    $resultats[] = $resultat;
                }
            }
            
            // Una vez procesadas todas las acciones, avanzamos al siguiente turno
            require_once __DIR__ . '/../Combat_Model.php';
            $combatModel = new Combat_Model($this->connexio);
            
            // Alternar el turno entre los jugadores
            $siguienteTurnoId = ($batalla['torn_actual_id'] == $usuari1Id) ? $usuari2Id : $usuari1Id;
            
            $sql = "UPDATE batalles SET torn_actual = torn_actual + 1, torn_actual_id = ? WHERE id_batalla = ?";
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("ii", $siguienteTurnoId, $batallaId);
            $stmt->execute();
            
            // Registramos el fin del turno
            $combatModel->registrarAccionBatalla(
                $batallaId, 
                $tornActual, 
                'fin_turno', 
                "Turno " . $tornActual . " completado"
            );
            
            return [
                'success' => true,
                'message' => 'Turno procesado correctamente',
                'resultados' => $resultats,
                'siguiente_turno_id' => $siguienteTurnoId
            ];
            
        } catch (Exception $e) {
            error_log("Error al procesar turno: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al procesar el turno: ' . $e->getMessage()];
        }
    }
    
    /**
     * Determina el orden de ejecución de las acciones según prioridad
     * 
     * @param array $accions Acciones elegidas por los jugadores
     * @param array $batalla Información de la batalla
     * @return array Acciones ordenadas por prioridad
     */
    private function determinarPrioritat($accions, $batalla) {
        // Reglas de prioridad:
        // 1. Los cambios de Pokémon siempre van primero
        // 2. La prioridad de los movimientos se basa en su tipo/prioridad específica
        // 3. Si hay empate, se decide por la velocidad del Pokémon
        
        $accionsOrdenades = [];
        $canvis = [];
        $atacs = [];
        
        foreach ($accions as $accio) {
            if ($accio['tipus_accio'] === 'canvi_pokemon') {
                $canvis[] = $accio;
            } else {
                $atacs[] = $accio;
            }
        }
        
        // Primero los cambios de Pokémon
        foreach ($canvis as $canvi) {
            $accionsOrdenades[] = $canvi;
        }
        
        // Si hay ataques, determinar el orden por la velocidad de los Pokémon
        if (count($atacs) > 0) {
            if (count($atacs) == 2) {
                // Obtener la velocidad de los Pokémon
                $pokemon1 = $this->obtenirInfoPokemon($batalla['pokemon_activo_1']);
                $pokemon2 = $this->obtenirInfoPokemon($batalla['pokemon_activo_2']);
                
                // Determinar quién va primero (mayor velocidad)
                if ($pokemon1['velocidad'] > $pokemon2['velocidad']) {
                    // El jugador 1 va primero
                    foreach ($atacs as $atac) {
                        if ($atac['usuari_id'] == $batalla['usuari1_id']) {
                            $accionsOrdenades[] = $atac;
                            break;
                        }
                    }
                    foreach ($atacs as $atac) {
                        if ($atac['usuari_id'] == $batalla['usuari2_id']) {
                            $accionsOrdenades[] = $atac;
                            break;
                        }
                    }
                } else {
                    // El jugador 2 va primero o hay empate (en caso de empate, orden aleatorio)
                    foreach ($atacs as $atac) {
                        if ($atac['usuari_id'] == $batalla['usuari2_id']) {
                            $accionsOrdenades[] = $atac;
                            break;
                        }
                    }
                    foreach ($atacs as $atac) {
                        if ($atac['usuari_id'] == $batalla['usuari1_id']) {
                            $accionsOrdenades[] = $atac;
                            break;
                        }
                    }
                }
            } else {
                // Si solo hay un ataque, simplemente añadirlo
                $accionsOrdenades = array_merge($accionsOrdenades, $atacs);
            }
        }
        
        return $accionsOrdenades;
    }
    
    /**
     * Obtiene la información de un Pokémon para determinar prioridades
     * 
     * @param int $pokemonId ID del estado del Pokémon
     * @return array Información del Pokémon
     */
    private function obtenirInfoPokemon($pokemonId) {
        $sql = "SELECT * FROM estat_pokemon_batalla WHERE id = ?";
        $stmt = $this->connexio->prepare($sql);
        $stmt->bind_param("i", $pokemonId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}
?>