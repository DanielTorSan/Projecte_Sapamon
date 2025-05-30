<?php
/**
 * Modelo para gestionar las batallas entre entrenadores
 * y el sistema de combate Pokémon
 */

// Protección contra inclusión múltiple
if (!class_exists('Combat_Model')) {

class Combat_Model {
    private $connexio;

    /**
     * Constructor
     */
    public function __construct($connexio) {
        $this->connexio = $connexio;
    }

    /**
     * Obtiene los datos completos de una batalla
     * 
     * @param int $idBatalla ID de la batalla
     * @return array|null Datos de la batalla o null si no existe
     */
    public function obtenerDatosBatalla($idBatalla) {
        try {
            // Verificamos primero si la batalla existe y obtenemos sus datos básicos
            $sql = "SELECT b.*, 
                    u1.nom_usuari AS nombre_usuari1, u1.avatar AS avatar_usuari1,
                    u2.nom_usuari AS nombre_usuari2, u2.avatar AS avatar_usuari2,
                    e1.nom_equip AS nombre_equip1, e2.nom_equip AS nombre_equip2
                    FROM batalles b 
                    LEFT JOIN usuaris u1 ON b.usuari1_id = u1.id_usuari
                    LEFT JOIN usuaris u2 ON b.usuari2_id = u2.id_usuari
                    LEFT JOIN equips e1 ON b.equip1_id = e1.id_equip
                    LEFT JOIN equips e2 ON b.equip2_id = e2.id_equip
                    WHERE b.id_batalla = ?";
            
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("i", $idBatalla);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                // Si no encontramos la batalla por id_batalla, verificar si es un ID de invitación
                $sql2 = "SELECT id_batalla FROM batalles WHERE id_invitacio = ?";
                $stmt2 = $this->connexio->prepare($sql2);
                $stmt2->bind_param("i", $idBatalla);
                $stmt2->execute();
                $result2 = $stmt2->get_result();
                
                if ($result2->num_rows > 0) {
                    // Si encontramos una batalla con esa invitación, recursivamente obtenemos sus datos
                    $idBatallaReal = $result2->fetch_assoc()['id_batalla'];
                    return $this->obtenerDatosBatalla($idBatallaReal);
                }
                
                return null;
            }
            
            $batalla = $result->fetch_assoc();
            
            // Si la batalla está activa pero no tiene fecha de inicio, la actualizamos
            if ($batalla['estat'] === 'activa' && $batalla['iniciada_el'] === NULL) {
                $sqlUpdate = "UPDATE batalles SET iniciada_el = CURRENT_TIMESTAMP WHERE id_batalla = ?";
                $stmtUpdate = $this->connexio->prepare($sqlUpdate);
                $stmtUpdate->bind_param("i", $idBatalla);
                $stmtUpdate->execute();
                
                // Actualizamos el valor en la respuesta también
                $batalla['iniciada_el'] = date('Y-m-d H:i:s');
            }
            
            return $batalla;
        } catch (Exception $e) {
            error_log("Error al obtener datos de batalla: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtiene los pokémon de un equipo específico para una batalla
     * 
     * @param int $equipId ID del equipo
     * @return array Array con los Pokémon del equipo
     */
    public function obtenerPokemonEquipo($equipId) {
        try {
            // Obtenemos primero los datos básicos de los Pokémon del equipo
            $sql = "SELECT ep.*, em.id_equip_moviment, em.nom_moviment, em.tipus_moviment, em.categoria, 
                    em.poder, em.precisio, em.pp_maxims 
                    FROM equip_pokemon ep
                    LEFT JOIN equips_moviments em ON ep.id_equip_pokemon = em.equip_pokemon_id
                    WHERE ep.equip_id = ?
                    ORDER BY ep.posicio ASC, em.id_equip_moviment ASC";
            
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("i", $equipId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            // Organizamos los resultados agrupando los movimientos por Pokémon
            $pokemons = [];
            $currentPokemonId = null;
            $pokemonIndex = -1;
            
            while ($row = $result->fetch_assoc()) {
                if ($currentPokemonId != $row['id_equip_pokemon']) {
                    $pokemonIndex++;
                    $currentPokemonId = $row['id_equip_pokemon'];
                    
                    // Datos básicos del Pokémon
                    $pokemons[$pokemonIndex] = [
                        'id_equip_pokemon' => $row['id_equip_pokemon'],
                        'equip_id' => $row['equip_id'],
                        'pokeapi_id' => $row['pokeapi_id'],
                        'malnom' => $row['malnom'],
                        'nivell' => $row['nivell'],
                        'posicio' => $row['posicio'],
                        'sprite' => $row['sprite'],
                        'hp_actual' => 100, // Valor predeterminado o calcular según nivel
                        'hp_max' => 100, // Valor predeterminado o calcular según nivel
                        'movimientos' => []
                    ];
                }
                
                // Añadir movimiento si existe
                if (!empty($row['id_equip_moviment'])) {
                    $pokemons[$pokemonIndex]['movimientos'][] = [
                        'id_equip_moviment' => $row['id_equip_moviment'],
                        'nom_moviment' => $row['nom_moviment'],
                        'tipus_moviment' => $row['tipus_moviment'],
                        'categoria' => $row['categoria'],
                        'poder' => $row['poder'],
                        'precisio' => $row['precisio'],
                        'pp_maxims' => $row['pp_maxims']
                    ];
                }
            }
            
            return $pokemons;
        } catch (Exception $e) {
            error_log("Error al obtener Pokémon del equipo: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene los movimientos de un Pokémon en batalla
     * 
     * @param int $estatPokemonId ID del estado del Pokémon en batalla
     * @return array Array con los movimientos del Pokémon
     */
    public function obtenerMovimientosPokemon($estatPokemonId) {
        try {
            $sql = "SELECT * FROM moviments_pokemon_batalla
                    WHERE estat_pokemon_id = ?";
            
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("i", $estatPokemonId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error al obtener movimientos de Pokémon: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Verifica si un usuario es participante en la batalla
     * 
     * @param int $batallaId ID de la batalla
     * @param int $usuarioId ID del usuario
     * @return bool True si el usuario es participante, false en caso contrario
     */
    public function esParticipante($batallaId, $usuarioId) {
        try {
            $sql = "SELECT COUNT(*) FROM batalles 
                    WHERE id_batalla = ? 
                    AND (usuari1_id = ? OR usuari2_id = ?)";
            
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("iii", $batallaId, $usuarioId, $usuarioId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            return $result->fetch_row()[0] > 0;
        } catch (Exception $e) {
            error_log("Error al verificar participación: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica si un token público es válido para una batalla
     * 
     * @param int $batallaId ID de la batalla
     * @param string $token Token público
     * @return bool True si el token es válido, false en caso contrario
     */
    public function esTokenValido($batallaId, $token) {
        try {
            $sql = "SELECT COUNT(*) FROM batalles 
                    WHERE id_batalla = ? 
                    AND token_public = ?";
            
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("is", $batallaId, $token);
            $stmt->execute();
            $result = $stmt->get_result();
            
            return $result->fetch_row()[0] > 0;
        } catch (Exception $e) {
            error_log("Error al verificar token: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Inicializa una batalla (cambia estado a 'activa')
     * 
     * @param int $batallaId ID de la batalla
     * @return bool True si se actualizó correctamente, false en caso contrario
     */
    public function inicializarBatalla($batallaId) {
        try {
            $sql = "UPDATE batalles 
                    SET estat = 'activa', iniciada_el = CURRENT_TIMESTAMP, 
                    torn_actual_id = usuari1_id 
                    WHERE id_batalla = ? AND estat = 'pendent'";
            
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("i", $batallaId);
            $stmt->execute();
            
            return $stmt->affected_rows > 0;
        } catch (Exception $e) {
            error_log("Error al inicializar batalla: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Busca una batalla activa para el usuario
     * 
     * @param int $usuarioId ID del usuario
     * @return string|null ID de sala de la primera batalla activa o null si no hay ninguna
     */
    public function buscarBatallaActiva($usuarioId) {
        try {
            $sql = "SELECT * FROM batalles 
                    WHERE (usuari1_id = ? OR usuari2_id = ?)
                    AND estat = 'activa'
                    ORDER BY iniciada_el DESC";
            
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("ii", $usuarioId, $usuarioId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $batalla = $result->fetch_assoc();
                return $batalla['id_batalla'];
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Error al buscar batalla activa: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener actualizaciones de una batalla desde cierta acción
     * 
     * @param int $batallaId ID de la batalla
     * @param int $ultimaAccion ID de la última acción conocida
     * @return array Lista de acciones nuevas
     */
    public function obtenerActualizacionesBatalla($batallaId, $ultimaAccion = 0) {
        try {
            $sql = "SELECT * FROM acciones_batalla 
                    WHERE batalla_id = ? AND id > ?
                    ORDER BY id ASC";
            
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("ii", $batallaId, $ultimaAccion);
            $stmt->execute();
            $result = $stmt->get_result();
            
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error al obtener actualizaciones: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener estado actual del Pokémon activo de un usuario en batalla
     * 
     * @param int $batallaId ID de la batalla
     * @param int $usuarioId ID del usuario
     * @return array|null Estado del Pokémon activo o null si no hay
     */
    public function obtenerEstadoPokemonActivo($batallaId, $usuarioId) {
        try {
            $sql = "SELECT ep.*, p.* FROM estado_pokemon_batalla ep
                    JOIN pokemon_batalla p ON ep.pokemon_id = p.id
                    WHERE ep.batalla_id = ? AND p.usuario_id = ? AND ep.activo = 1";
            
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("ii", $batallaId, $usuarioId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return null;
            }
            
            $pokemon = $result->fetch_assoc();
            
            // Obtener movimientos
            $pokemon['movimientos'] = $this->obtenerMovimientosPokemon($pokemon['id']);
            
            return $pokemon;
        } catch (Exception $e) {
            error_log("Error al obtener estado del Pokémon activo: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obté informació completa de l'estat d'un Pokémon en batalla
     * 
     * @param int $estatPokemonId ID de l'estat del Pokémon en batalla
     * @return array|null Informació completa del Pokémon o null si no existeix
     */
    public function obtenerInfoEstatPokemon($estatPokemonId) {
        try {
            $sql = "SELECT ep.*, epb.malnom, epb.pokeapi_id, epb.sprite, epb.equip_id
                    FROM estat_pokemon_batalla ep
                    JOIN equip_pokemon epb ON ep.equip_pokemon_id = epb.id_equip_pokemon
                    WHERE ep.id = ?";
            
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("i", $estatPokemonId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return null;
            }
            
            $pokemon = $result->fetch_assoc();
            
            // Obtenir els moviments del Pokémon
            $pokemon['moviments'] = $this->obtenerMovimientosPokemon($estatPokemonId);
            
            // Obtenir les condicions del Pokémon (paralitzat, enverinat, etc.)
            $sql = "SELECT * FROM condicions_pokemon WHERE estat_pokemon_id = ?";
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("i", $estatPokemonId);
            $stmt->execute();
            $result = $stmt->get_result();
            $pokemon['condicions'] = $result->fetch_all(MYSQLI_ASSOC);
            
            // Obtenir els modificadors d'estadístiques
            $sql = "SELECT * FROM estat_estadistiques WHERE estat_pokemon_id = ?";
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("i", $estatPokemonId);
            $stmt->execute();
            $result = $stmt->get_result();
            $modificadors = [];
            
            while ($row = $result->fetch_assoc()) {
                $modificadors[$row['tipo_stat']] = $row['modificador'];
            }
            
            $pokemon['modificadors'] = $modificadors;
            
            return $pokemon;
        } catch (Exception $e) {
            error_log("Error al obtenir informació de l'estat del Pokémon: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Registra una acción de un jugador para el turno actual
     * 
     * @param int $batallaId ID de la batalla
     * @param int $usuariId ID del usuario que realiza la acción
     * @param string $tipusAccio Tipo de acción ('moviment', 'canvi_pokemon', 'rendicio')
     * @param int|null $movimentId ID del movimiento (si es una acción de movimiento)
     * @param int|null $pokemonId ID del pokemon (si es una acción de cambio)
     * @return bool True si la acción se registró correctamente
     */
    public function registrarAccioTorn($batallaId, $usuariId, $tipusAccio, $movimentId = null, $pokemonId = null) {
        try {
            // Obtener datos completos de la batalla para validaciones
            $sql = "SELECT torn_actual, torn_actual_id, usuari1_id, usuari2_id, estat FROM batalles WHERE id_batalla = ?";
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("i", $batallaId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                error_log("Batalla no encontrada al registrar acción: {$batallaId}");
                return false;
            }
            
            $batalla = $result->fetch_assoc();
            $tornActual = $batalla['torn_actual'] ?? 1;
            
            // Verificar si la batalla está activa
            if ($batalla['estat'] !== 'activa') {
                error_log("Intento de acción en batalla no activa: {$batallaId}, estado: {$batalla['estat']}");
                return false;
            }
            
            // Verificar que el usuario sea participante de la batalla
            if ($usuariId != $batalla['usuari1_id'] && $usuariId != $batalla['usuari2_id']) {
                error_log("Usuario {$usuariId} no es participante de la batalla {$batallaId}");
                return false;
            }
            
            // Validar que el tipo de acción sea válido
            if (!in_array($tipusAccio, ['moviment', 'canvi_pokemon', 'rendicio'])) {
                error_log("Tipo de acción inválido: {$tipusAccio}");
                return false;
            }
            
            // Validar datos adicionales según el tipo de acción
            if ($tipusAccio === 'moviment' && empty($movimentId)) {
                error_log("Falta ID de movimiento para acción de tipo movimiento");
                return false;
            }
            
            if ($tipusAccio === 'canvi_pokemon' && empty($pokemonId)) {
                error_log("Falta ID de pokemon para acción de tipo cambio");
                return false;
            }
            
            // Verificar si el usuario ya ha registrado una acción para este turno
            $sql = "SELECT id FROM accions_torn WHERE batalla_id = ? AND usuari_id = ? AND torn = ?";
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("iii", $batallaId, $usuariId, $tornActual);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Actualizar la acción existente
                $accioId = $result->fetch_assoc()['id'];
                $sql = "UPDATE accions_torn SET tipus_accio = ?, moviment_id = ?, pokemon_id = ?, timestamp = CURRENT_TIMESTAMP 
                        WHERE id = ?";
                $stmt = $this->connexio->prepare($sql);
                $stmt->bind_param("siii", $tipusAccio, $movimentId, $pokemonId, $accioId);
                $ejecutado = $stmt->execute();
                
                if (!$ejecutado) {
                    error_log("Error al actualizar acción existente: " . $stmt->error);
                    return false;
                }
            } else {
                // Insertar una nueva acción
                $sql = "INSERT INTO accions_torn (batalla_id, usuari_id, torn, tipus_accio, moviment_id, pokemon_id) 
                        VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $this->connexio->prepare($sql);
                $stmt->bind_param("iiisii", $batallaId, $usuariId, $tornActual, $tipusAccio, $movimentId, $pokemonId);
                $ejecutado = $stmt->execute();
                
                if (!$ejecutado) {
                    error_log("Error al insertar nueva acción: " . $stmt->error . " - SQL: " . $sql);
                    return false;
                }
            }
            
            // Si la acción es una rendición, procesarla inmediatamente
            if ($tipusAccio === 'rendicio') {
                // Determinar el ganador (el oponente del que se rinde)
                $ganadorId = ($usuariId == $batalla['usuari1_id']) ? $batalla['usuari2_id'] : $batalla['usuari1_id'];
                
                // Actualizar la batalla como finalizada
                $sqlFin = "UPDATE batalles SET estat = 'acabada', guanyador_id = ?, acabada_el = CURRENT_TIMESTAMP WHERE id_batalla = ?";
                $stmtFin = $this->connexio->prepare($sqlFin);
                $stmtFin->bind_param("ii", $ganadorId, $batallaId);
                $stmtFin->execute();
            }
            
            // En el caso de movimientos y cambios de pokémon, verificar si todos los jugadores han registrado su acción
            return $this->verificarAvanzarTurno($batallaId, $tornActual);
            
        } catch (Exception $e) {
            error_log("Error al registrar acción de turno: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Verifica si todos los jugadores han registrado sus acciones y avanza al siguiente turno
     * 
     * @param int $batallaId ID de la batalla
     * @param int $tornActual Turno actual
     * @return bool True si el turno avanzó correctamente
     */
    private function verificarAvanzarTurno($batallaId, $tornActual) {
        try {
            // Obtener información de la batalla
            $sql = "SELECT usuari1_id, usuari2_id, torn_actual_id FROM batalles WHERE id_batalla = ?";
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("i", $batallaId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return false;
            }
            
            $batalla = $result->fetch_assoc();
            $usuari1Id = $batalla['usuari1_id'];
            $usuari2Id = $batalla['usuari2_id'];
            
            // Contar acciones registradas para este turno
            $sql = "SELECT COUNT(*) as total FROM accions_torn 
                    WHERE batalla_id = ? AND torn = ? AND usuari_id IN (?, ?)";
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("iiii", $batallaId, $tornActual, $usuari1Id, $usuari2Id);
            $stmt->execute();
            $result = $stmt->get_result();
            $accionesRegistradas = $result->fetch_assoc()['total'];
            
            // Si ambos jugadores han registrado su acción, avanzar al siguiente turno
            if ($accionesRegistradas == 2) {
                // Cambia el turno al otro jugador
                $nuevoTornActualId = ($batalla['torn_actual_id'] == $usuari1Id) ? $usuari2Id : $usuari1Id;
                
                // Incrementa el contador de turno y actualiza el usuario de turno
                $sql = "UPDATE batalles SET torn_actual = torn_actual + 1, torn_actual_id = ? WHERE id_batalla = ?";
                $stmt = $this->connexio->prepare($sql);
                $stmt->bind_param("ii", $nuevoTornActualId, $batallaId);
                $stmt->execute();
                
                // Registrar el cambio de turno en el log de la batalla
                $this->registrarAccionBatalla($batallaId, $tornActual, 'cambio_turno', 
                    "Turno completado. Ahora es el turno del entrenador " . $nuevoTornActualId);
                
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Error al verificar/avanzar turno: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra una acción en el log de la batalla
     * 
     * @param int $batallaId ID de la batalla
     * @param int $turno Número de turno
     * @param string $tipoAccion Tipo de acción
     * @param string $texto Descripción de la acción
     * @return bool True si se registró correctamente
     */
    public function registrarAccionBatalla($batallaId, $turno, $tipoAccion, $texto) {
        try {
            $sql = "INSERT INTO registres_batalla (batalla_id, turno, usuario_id, tipo_accion, texto) 
                    VALUES (?, ?, 0, ?, ?)";
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("iiss", $batallaId, $turno, $tipoAccion, $texto);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error al registrar acción en batalla: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Comprueba si ha habido actualizaciones en una batalla desde una fecha determinada
     * 
     * @param int $batallaId ID de la batalla
     * @param int $timestamp Timestamp de la última actualización conocida
     * @return bool True si hay actualizaciones, false en caso contrario
     */
    public function hayActualizacionesDesdeFecha($batallaId, $timestamp) {
        try {
            // Convertir timestamp a formato datetime
            $fecha = date('Y-m-d H:i:s', intval($timestamp/1000));
            
            // Verificar si hay acciones nuevas
            $sql = "SELECT COUNT(*) as total FROM accions_torn 
                    WHERE batalla_id = ? AND timestamp > ?";
            
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("is", $batallaId, $fecha);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['total'] > 0) {
                return true;
            }
            
            // Verificar si hay cambios en el estado de la batalla
            $sql = "SELECT estat, torn_actual, pokemon_actiu_1, pokemon_actiu_2, guanyador_id 
                    FROM batalles 
                    WHERE id_batalla = ? AND (
                        estat = 'acabada' OR 
                        actualitzat_el > ? OR
                        torn_actual_id IS NOT NULL
                    )";
            
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("is", $batallaId, $fecha);
            $stmt->execute();
            $result = $stmt->get_result();
            
            // Si hay resultados y los estados han cambiado, hay actualizaciones
            if ($result->num_rows > 0) {
                // Aquí podríamos comparar con estados anteriores guardados en caché/sesión
                // para ser aún más precisos, pero por ahora simplemente verificamos si hay cambios
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error al comprobar actualizaciones de batalla: " . $e->getMessage());
            // En caso de error, asumimos que hay actualizaciones para forzar una recarga
            return true;
        }
    }
    
    /**
     * Obtiene las acciones de un turno específico
     * 
     * @param int $batallaId ID de la batalla
     * @param int $tornActual Número del turno
     * @return array Lista de acciones
     */
    public function obtenirAccionsTorn($batallaId, $tornActual) {
        try {
            $sql = "SELECT * FROM accions_torn 
                    WHERE batalla_id = ? AND torn = ?";
            
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("ii", $batallaId, $tornActual);
            $stmt->execute();
            
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error al obtener acciones del turno: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Inicializa los Pokémon activos para ambos jugadores al comenzar la batalla
     * 
     * @param int $batallaId ID de la batalla
     * @param int $pokemon1Id ID del primer Pokémon del equipo 1 (opcional, si no se proporciona se usa el primero)
     * @param int $pokemon2Id ID del primer Pokémon del equipo 2 (opcional, si no se proporciona se usa el primero)
     * @return bool True si se inicializaron correctamente
     */
    public function inicializarPokemonActivos($batallaId, $pokemon1Id = null, $pokemon2Id = null) {
        try {
            // Obtener información de la batalla
            $batalla = $this->obtenerDatosBatalla($batallaId);
            if (!$batalla || $batalla['estat'] !== 'activa') {
                error_log("No se puede inicializar Pokémon activos: Batalla no activa o no existe");
                return false;
            }
            
            // Obtener los equipos de ambos jugadores
            $equipo1 = $this->obtenerPokemonEquipo($batalla['equip1_id']);
            $equipo2 = $this->obtenerPokemonEquipo($batalla['equip2_id']);
            
            if (empty($equipo1) || empty($equipo2)) {
                error_log("No se puede inicializar Pokémon activos: Equipos vacíos");
                return false;
            }
            
            // Si no se especificaron IDs, usar el primer Pokémon de cada equipo
            $pokemon1Id = $pokemon1Id ?: $equipo1[0]['id_equip_pokemon'];
            $pokemon2Id = $pokemon2Id ?: $equipo2[0]['id_equip_pokemon'];
            
            // Actualizar la batalla con los Pokémon activos
            $sql = "UPDATE batalles 
                    SET pokemon_actiu_1 = ?, pokemon_actiu_2 = ?, actualitzat_el = CURRENT_TIMESTAMP 
                    WHERE id_batalla = ?";
            
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("iii", $pokemon1Id, $pokemon2Id, $batallaId);
            $ejecutado = $stmt->execute();
            
            if (!$ejecutado) {
                error_log("Error al inicializar Pokémon activos: " . $stmt->error);
                return false;
            }
            
            // Registrar la acción en el historial
            $this->registrarAccionBatalla(
                $batallaId, 
                $batalla['torn_actual'] ?: 1, 
                'inicio_batalla', 
                "Batalla iniciada. Pokémon activos seleccionados."
            );
            
            return true;
        } catch (Exception $e) {
            error_log("Error al inicializar Pokémon activos: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Establece un Pokémon como activo para un usuario en una batalla
     * 
     * @param int $batallaId ID de la batalla
     * @param int $usuarioId ID del usuario
     * @param int $pokemonId ID del Pokémon (de equip_pokemon)
     * @return bool True si se estableció correctamente, false en caso contrario
     */
    public function establecerPokemonActivo($batallaId, $usuarioId, $pokemonId) {
        try {
            // Verificar que la batalla exista y esté activa
            $sql = "SELECT * FROM batalles WHERE id_batalla = ?";
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("i", $batallaId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                error_log("La batalla no existe: $batallaId");
                return false;
            }
            
            $batalla = $result->fetch_assoc();
            
            // Verificar que el usuario sea participante de la batalla
            if ($batalla['usuari1_id'] != $usuarioId && $batalla['usuari2_id'] != $usuarioId) {
                error_log("El usuario $usuarioId no es participante de la batalla $batallaId");
                return false;
            }
            
            // Verificar que el Pokémon pertenezca al equipo del usuario
            $equipoId = ($batalla['usuari1_id'] == $usuarioId) ? $batalla['equip1_id'] : $batalla['equip2_id'];
            
            $sql = "SELECT * FROM equip_pokemon WHERE id_equip_pokemon = ? AND equip_id = ?";
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("ii", $pokemonId, $equipoId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                error_log("El Pokémon $pokemonId no pertenece al equipo $equipoId del usuario $usuarioId");
                return false;
            }
            
            // Determinar si es usuario1 o usuario2 y actualizar el campo correspondiente
            $campoActualizar = ($batalla['usuari1_id'] == $usuarioId) ? 'pokemon_actiu_1' : 'pokemon_actiu_2';
            
            $sql = "UPDATE batalles SET $campoActualizar = ? WHERE id_batalla = ?";
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("ii", $pokemonId, $batallaId);
            $stmt->execute();
            
            return $stmt->affected_rows > 0;
        } catch (Exception $e) {
            error_log("Error al establecer Pokémon activo: " . $e->getMessage());
            return false;
        }
    }
}

} // Fin de la protección contra inclusión múltiple
?>