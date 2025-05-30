<?php
/**
 * Model per gestionar els equips Pokémon a la base de dades
 * @package Model
 */
class EquipModel {
    /** @var \mysqli Database connection */
    private $connexio;
    /** @var \PokeAPIService API service for Pokemon data */
    private $pokeAPIService;
    
    /**
     * Constructor del model d'equips
     * @param \mysqli $connexio Database connection
     */
    public function __construct($connexio) {
        $this->connexio = $connexio;
        
        // Verificar primero que el archivo existe
        $pokeAPIServicePath = __DIR__ . '/PokeAPIService.php';
        if (!file_exists($pokeAPIServicePath)) {
            throw new Exception("No se encontró PokeAPIService.php en: " . $pokeAPIServicePath);
        }
        
        // Cargar el servei PokeAPI
        require_once $pokeAPIServicePath;
        $this->pokeAPIService = new PokeAPIService($connexio);
    }
    
    /**
     * Obtenir tots els equips d'un usuari
     */
    public function getEquiposByUsuario($usuariId) {
        $equips = [];
        
        // Consulta per obtenir els equips de l'usuari
        $stmt = $this->connexio->prepare("
            SELECT * FROM equips 
            WHERE usuari_id = ? 
            ORDER BY nom_equip
        ");
        
        $stmt->bind_param("i", $usuariId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($equip = $result->fetch_assoc()) {
            // Obtenir els pokemon de cada equip
            $equip['pokemons'] = $this->getPokemonsByEquipo($equip['id_equip']);
            $equips[] = $equip;
        }
        
        return $equips;
    }
    
    /**
     * Obtenir els pokémon d'un equip específic prioritzant dades de la BDD
     */
    private function getPokemonsByEquipo($equipId) {
        $pokemons = [];
        
        $stmt = $this->connexio->prepare("
            SELECT ep.* 
            FROM equip_pokemon ep 
            WHERE ep.equip_id = ?
            ORDER BY ep.posicio
        ");
        
        $stmt->bind_param("i", $equipId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($pokemon = $result->fetch_assoc()) {
            try {
                // Preparar datos básicos del Pokémon desde la BDD
                $pokemonData = [
                    'id_equip_pokemon' => $pokemon['id_equip_pokemon'],
                    'pokeapi_id' => $pokemon['pokeapi_id'],
                    'malnom' => $pokemon['malnom'],
                    'nivell' => $pokemon['nivell'],
                    'posicio' => $pokemon['posicio'],
                    'sprite' => $pokemon['sprite'],
                    'nombre' => ucfirst($pokemon['malnom']) // Por defecto, usar el malnom como nombre
                ];
                
                // Verificar si tenemos un sprite válido en la BDD
                $tieneSprite = !empty($pokemon['sprite']);
                
                // Obtener movimientos asignados desde la BDD
                $pokemonData['movimientos'] = $this->getMovimientosPokemon($pokemon['id_equip_pokemon']);
                
                // Si falta el sprite o necesitamos datos adicionales, consultar la PokeAPI
                if (!$tieneSprite || true) { // Siempre consultamos para obtener tipos y stats
                    $pokeApiData = $this->pokeAPIService->getPokemon($pokemon['pokeapi_id']);
                    
                    if ($pokeApiData) {
                        // Añadir nombre oficial desde la API si no hay malnom personalizado
                        if (empty($pokemonData['malnom']) || $pokemonData['malnom'] == ucfirst($pokeApiData['name'])) {
                            $pokemonData['nombre'] = ucfirst($pokeApiData['name']);
                        }
                        
                        // Añadir tipos
                        $pokemonData['types'] = array_map(function($type) {
                            return $type['type']['name'];
                        }, $pokeApiData['types']);
                        
                        // Añadir estadísticas
                        $pokemonData['stats'] = $pokeApiData['stats'];
                        
                        // Actualizar sprite si es necesario
                        if (!$tieneSprite) {
                            $nuevoSprite = $this->pokeAPIService->getPokemonSprite($pokeApiData);
                            if (!empty($nuevoSprite)) {
                                $pokemonData['sprite'] = $nuevoSprite;
                                // Actualizar el sprite en la base de datos para futuras consultas
                                $this->updatePokemonSprite($pokemon['id_equip_pokemon'], $nuevoSprite);
                            }
                        }
                    }
                }
                
                // Si después de todo sigue sin tener sprite, usar URL genérica
                if (empty($pokemonData['sprite'])) {
                    $pokemonData['sprite'] = "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/{$pokemon['pokeapi_id']}.png";
                }
                
                $pokemons[] = $pokemonData;
                
            } catch (Exception $e) {
                error_log("Error al procesar pokémon ID " . $pokemon['pokeapi_id'] . ": " . $e->getMessage());
                
                // Aún así, incluir el Pokémon con los datos básicos disponibles de la BDD
                $basicPokemon = [
                    'id_equip_pokemon' => $pokemon['id_equip_pokemon'],
                    'pokeapi_id' => $pokemon['pokeapi_id'],
                    'malnom' => $pokemon['malnom'],
                    'nivell' => $pokemon['nivell'],
                    'posicio' => $pokemon['posicio'],
                    'nombre' => !empty($pokemon['malnom']) ? $pokemon['malnom'] : "Pokémon #" . $pokemon['pokeapi_id'],
                    'types' => [],
                    'stats' => [],
                    'movimientos' => $this->getMovimientosPokemon($pokemon['id_equip_pokemon'])
                ];
                
                // Si hay sprite en la BDD, usarlo; si no, crear URL genérica
                $basicPokemon['sprite'] = !empty($pokemon['sprite']) 
                    ? $pokemon['sprite'] 
                    : "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/{$pokemon['pokeapi_id']}.png";
                
                $pokemons[] = $basicPokemon;
            }
        }
        
        return $pokemons;
    }
    
    /**
     * Actualiza la URL del sprite de un Pokémon en la base de datos
     * @param int $equipPokemonId ID del Pokémon en el equipo
     * @param string $spriteUrl URL del sprite
     * @return bool Resultado de la operación
     */
    private function updatePokemonSprite($equipPokemonId, $spriteUrl) {
        try {
            $stmt = $this->connexio->prepare("
                UPDATE equip_pokemon
                SET sprite = ?
                WHERE id_equip_pokemon = ?
            ");
            
            $stmt->bind_param("si", $spriteUrl, $equipPokemonId);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error al actualizar sprite: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener los movimientos asignados a un Pokémon del equipo
     */
    private function getMovimientosPokemon($equipPokemonId) {
        $movimientos = [];
        
        $stmt = $this->connexio->prepare("
            SELECT * FROM equips_moviments 
            WHERE equip_pokemon_id = ? 
            ORDER BY prioritat
        ");
        
        $stmt->bind_param("i", $equipPokemonId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($movimiento = $result->fetch_assoc()) {
            $movimientos[] = $movimiento;
        }
        
        return $movimientos;
    }
    
    /**
     * Crear un nou equip
     */
    public function crearEquipo($nombre, $usuariId) {
        $stmt = $this->connexio->prepare("
            INSERT INTO equips (nom_equip, usuari_id, creat_el) 
            VALUES (?, ?, NOW())
        ");
        
        $stmt->bind_param("si", $nombre, $usuariId);
        $exit = $stmt->execute();
        
        if ($exit) {
            return $this->connexio->insert_id;
        }
        
        return false;
    }
    
    /**
     * Eliminar un equip (i els seus pokémon associats)
     */
    public function eliminarEquipo($equipoId, $usuariId) {
        // Primer verifiquem que l'equip pertany a l'usuari
        $stmt = $this->connexio->prepare("
            SELECT id_equip FROM equips 
            WHERE id_equip = ? AND usuari_id = ?
        ");
        
        $stmt->bind_param("ii", $equipoId, $usuariId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return false; // L'equip no pertany a l'usuari
        }
        
        // Eliminar movimientos de los Pokémon del equipo
        $this->eliminarMovimientosPorEquipo($equipoId);
        
        // Esborrar els pokémon de l'equip
        $stmt = $this->connexio->prepare("
            DELETE FROM equip_pokemon 
            WHERE equip_id = ?
        ");
        
        $stmt->bind_param("i", $equipoId);
        $stmt->execute();
        
        // Després esborrar l'equip
        $stmt = $this->connexio->prepare("
            DELETE FROM equips 
            WHERE id_equip = ?
        ");
        
        $stmt->bind_param("i", $equipoId);
        return $stmt->execute();
    }
    
    /**
     * Eliminar todos los movimientos asociados a un equipo
     */
    private function eliminarMovimientosPorEquipo($equipoId) {
        $stmt = $this->connexio->prepare("
            DELETE em FROM equips_moviments em
            JOIN equip_pokemon ep ON em.equip_pokemon_id = ep.id_equip_pokemon
            WHERE ep.equip_id = ?
        ");
        
        $stmt->bind_param("i", $equipoId);
        return $stmt->execute();
    }
    
    /**
     * Afegir un pokémon a l'equip utilitzant la PokeAPI
     */
    public function agregarPokemon($equipoId, $pokemonId, $position, $malnom = '', $nivell = 50) {
        // Verificar el número màxim de Pokémon per equip (6)
        $stmt = $this->connexio->prepare("
            SELECT COUNT(*) as total FROM equip_pokemon 
            WHERE equip_id = ?
        ");
        
        $stmt->bind_param("i", $equipoId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['total'] >= 6) {
            return false; // Ja té el màxim de Pokémon
        }
        
        // Obtener datos del Pokémon para guardar su sprite
        $pokeApiData = $this->pokeAPIService->getPokemon($pokemonId);
        if (!$pokeApiData) {
            return false; // No se pudo obtener la información del Pokémon
        }
        
        // Obtener la URL del sprite
        $spriteUrl = $this->pokeAPIService->getPokemonSprite($pokeApiData);
        
        // Verificar si existe un Pokémon en esta posición
        $stmt = $this->connexio->prepare("
            SELECT id_equip_pokemon FROM equip_pokemon 
            WHERE equip_id = ? AND posicio = ?
        ");
        
        $stmt->bind_param("ii", $equipoId, $position);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Ja hi ha un Pokémon en aquesta posició, actualitzar-lo
            $row = $result->fetch_assoc();
            $equipPokemonId = $row['id_equip_pokemon'];
            
            $stmt = $this->connexio->prepare("
                UPDATE equip_pokemon 
                SET pokeapi_id = ?, malnom = ?, nivell = ?, sprite = ?
                WHERE id_equip_pokemon = ?
            ");
            
            $stmt->bind_param("isisi", $pokemonId, $malnom, $nivell, $spriteUrl, $equipPokemonId);
        } else {
            // Insertar nou Pokémon
            $stmt = $this->connexio->prepare("
                INSERT INTO equip_pokemon (equip_id, pokeapi_id, malnom, nivell, posicio, sprite) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->bind_param("iisiis", $equipoId, $pokemonId, $malnom, $nivell, $position, $spriteUrl);
        }
        
        return $stmt->execute() ? $this->connexio->insert_id : false;
    }
    
    /**
     * Eliminar un pokémon de l'equip por posición
     */
    public function eliminarPokemon($equipoId, $position) {
        // Primero identificar el id_equip_pokemon para eliminar sus movimientos
        $stmt = $this->connexio->prepare("
            SELECT id_equip_pokemon FROM equip_pokemon 
            WHERE equip_id = ? AND posicio = ?
        ");
        
        $stmt->bind_param("ii", $equipoId, $position);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $equipPokemonId = $row['id_equip_pokemon'];
            
            // Eliminar movimientos asociados
            $this->eliminarMovimientosPorPokemon($equipPokemonId);
        }
        
        // Eliminar el Pokémon
        $stmt = $this->connexio->prepare("
            DELETE FROM equip_pokemon 
            WHERE equip_id = ? AND posicio = ?
        ");
        
        $stmt->bind_param("ii", $equipoId, $position);
        return $stmt->execute();
    }
    
    /**
     * Eliminar movimientos de un Pokémon específico
     */
    private function eliminarMovimientosPorPokemon($equipPokemonId) {
        $stmt = $this->connexio->prepare("
            DELETE FROM equips_moviments 
            WHERE equip_pokemon_id = ?
        ");
        
        $stmt->bind_param("i", $equipPokemonId);
        return $stmt->execute();
    }
    
    /**
     * Obtener lista de Pokémon paginada de la PokeAPI
     */
    public function getPokemonListFromAPI($limit = 20, $offset = 0) {
        $pokemons = [];
        $pokemonList = $this->pokeAPIService->getPokemonList($limit, $offset);
        
        if ($pokemonList && isset($pokemonList['results'])) {
            foreach ($pokemonList['results'] as $pokemon) {
                $pokemonData = $this->pokeAPIService->getPokemon($pokemon['name']);
                
                if ($pokemonData) {
                    $pokemonInfo = [
                        'id' => $pokemonData['id'],
                        'name' => ucfirst($pokemonData['name']),
                        'types' => array_map(function($type) {
                            return $type['type']['name'];
                        }, $pokemonData['types']),
                        'sprite' => $this->pokeAPIService->getPokemonSprite($pokemonData)
                    ];
                    
                    $pokemons[] = $pokemonInfo;
                }
            }
        }
        
        return [
            'pokemons' => $pokemons,
            'count' => $pokemonList['count'] ?? 0,
            'next' => $pokemonList['next'] ? $offset + $limit : null,
            'previous' => $offset > 0 ? max(0, $offset - $limit) : null
        ];
    }
    
    /**
     * Buscar Pokémon en la PokeAPI
     */
    public function searchPokemonInAPI($searchTerm) {
        $results = $this->pokeAPIService->searchPokemonByName($searchTerm);
        $pokemons = [];
        
        foreach ($results as $pokemon) {
            $pokemonData = $this->pokeAPIService->getPokemon($pokemon['name']);
            
            if ($pokemonData) {
                $pokemonInfo = [
                    'id' => $pokemonData['id'],
                    'name' => ucfirst($pokemonData['name']),
                    'types' => array_map(function($type) {
                        return $type['type']['name'];
                    }, $pokemonData['types']),
                    'sprite' => $this->pokeAPIService->getPokemonSprite($pokemonData)
                ];
                
                $pokemons[] = $pokemonInfo;
            }
        }
        
        return $pokemons;
    }
    
    /**
     * Agregar un movimiento a un Pokémon del equipo
     */
    public function agregarMovimiento($equipPokemonId, $moveId, $moveName, $moveType, $moveCategory, $power, $accuracy, $pp, $priority = 0) {
        // Verificar que no tenga ya 4 movimientos
        $stmt = $this->connexio->prepare("
            SELECT COUNT(*) as total FROM equips_moviments 
            WHERE equip_pokemon_id = ?
        ");
        
        $stmt->bind_param("i", $equipPokemonId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['total'] >= 4) {
            return ['success' => false, 'message' => 'Aquest Pokémon ja té 4 moviments assignats'];
        }
        
        // Insertar el movimiento
        $stmt = $this->connexio->prepare("
            INSERT INTO equips_moviments (
                equip_pokemon_id, pokeapi_move_id, nom_moviment, 
                tipus_moviment, categoria, poder, precisio, 
                pp_maxims, prioritat, afegit_el
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->bind_param(
            "iisssiiii", 
            $equipPokemonId, $moveId, $moveName, 
            $moveType, $moveCategory, $power, $accuracy, 
            $pp, $priority
        );
        
        $result = $stmt->execute();
        
        return [
            'success' => $result,
            'id' => $result ? $this->connexio->insert_id : null
        ];
    }
    
    /**
     * Eliminar un movimiento
     */
    public function eliminarMovimiento($movimientoId) {
        $stmt = $this->connexio->prepare("
            DELETE FROM equips_moviments 
            WHERE id_equip_moviment = ?
        ");
        
        $stmt->bind_param("i", $movimientoId);
        return $stmt->execute();
    }
    
    /**
     * Obtener movimientos disponibles para un Pokémon específico
     */
    public function getMovimientosDisponibles($pokemonId) {
        $pokemonData = $this->pokeAPIService->getPokemon($pokemonId);
        
        if (!$pokemonData || !isset($pokemonData['moves'])) {
            return [];
        }
        
        $moves = [];
        $limit = min(count($pokemonData['moves']), 20); // Limitar para no hacer demasiadas peticiones
        
        for ($i = 0; $i < $limit; $i++) {
            $moveUrl = $pokemonData['moves'][$i]['move']['url'];
            $moveId = basename(parse_url($moveUrl, PHP_URL_PATH));
            
            $moveData = $this->pokeAPIService->getMove($moveId);
            
            if ($moveData) {
                // Obtener el tipo de movimiento en español si existe, en inglés si no
                $moveType = '';
                if (isset($moveData['type']['name'])) {
                    $moveType = $moveData['type']['name'];
                }
                
                // Determinar la categoría del movimiento
                $category = 'estat'; // Por defecto es de estado
                if (isset($moveData['damage_class']['name'])) {
                    if ($moveData['damage_class']['name'] === 'physical') {
                        $category = 'físic';
                    } else if ($moveData['damage_class']['name'] === 'special') {
                        $category = 'especial';
                    }
                }
                
                $move = [
                    'id' => $moveData['id'],
                    'name' => ucfirst($moveData['name']),
                    'type' => $moveType,
                    'power' => $moveData['power'] ?? null,
                    'accuracy' => $moveData['accuracy'] ?? null,
                    'pp' => $moveData['pp'] ?? 5,
                    'category' => $category,
                    'priority' => $moveData['priority'] ?? 0
                ];
                
                $moves[] = $move;
            }
        }
        
        return $moves;
    }

    /**
     * Verifica si un equipo está completo y cumple los requisitos mínimos para ser guardado
     * @param int $equipId ID del equipo a verificar
     * @return array Resultado de la verificación
     */
    public function verificarEquipoCompleto($equipId) {
        $result = [
            'completo' => false,
            'mensaje' => ''
        ];
        
        // Verificar que el equipo existe
        $stmt = $this->connexio->prepare("SELECT id_equip, nom_equip FROM equips WHERE id_equip = ?");
        $stmt->bind_param("i", $equipId);
        $stmt->execute();
        $equipResult = $stmt->get_result();
        
        if ($equipResult->num_rows === 0) {
            $result['mensaje'] = 'El equipo no existe';
            return $result;
        }
        
        $equip = $equipResult->fetch_assoc();
        
        // Verificar que tiene un nombre válido
        if (empty(trim($equip['nom_equip']))) {
            $result['mensaje'] = 'El equipo debe tener un nombre válido';
            return $result;
        }
        
        // Verificar que tiene al menos un pokémon
        $stmt = $this->connexio->prepare("SELECT COUNT(*) as total FROM equip_pokemon WHERE equip_id = ?");
        $stmt->bind_param("i", $equipId);
        $stmt->execute();
        $pokemonCount = $stmt->get_result()->fetch_assoc()['total'];
        
        if ($pokemonCount === 0) {
            $result['mensaje'] = 'El equipo debe tener al menos un Pokémon';
            return $result;
        }
        
        // Verificar que cada pokémon tiene al menos un movimiento
        $stmt = $this->connexio->prepare("
            SELECT ep.id_equip_pokemon, ep.pokeapi_id,
                   (SELECT COUNT(*) FROM equips_moviments em WHERE em.equip_pokemon_id = ep.id_equip_pokemon) as movimientos
            FROM equip_pokemon ep
            WHERE ep.equip_id = ?
        ");
        
        $stmt->bind_param("i", $equipId);
        $stmt->execute();
        $pokemonResult = $stmt->get_result();
        
        while ($pokemon = $pokemonResult->fetch_assoc()) {
            if ($pokemon['movimientos'] === 0) {
                $pokemonName = $this->getPokemonName($pokemon['pokeapi_id']);
                $result['mensaje'] = "El Pokémon $pokemonName debe tener al menos un movimiento asignado";
                return $result;
            }
        }
        
        // Si llegamos aquí, el equipo está completo
        $result['completo'] = true;
        return $result;
    }
    
    /**
     * Obtener el nombre de un Pokémon por su ID de PokeAPI
     */
    private function getPokemonName($pokeapiId) {
        $pokemon = $this->pokeAPIService->getPokemon($pokeapiId);
        return $pokemon ? ucfirst($pokemon['name']) : "Pokémon #$pokeapiId";
    }
    
    /**
     * Verifica si un equipo es duplicado de otro existente del mismo usuario
     * @param int $equipId ID del equipo a verificar
     * @param int $usuariId ID del usuario propietario
     * @return array Resultado de la verificación
     */
    public function verificarEquipoDuplicado($equipId, $usuariId) {
        $result = [
            'duplicado' => false,
            'mensaje' => ''
        ];
        
        // Esta verificación se puede implementar en futuras versiones
        // Por ahora simplemente devolvemos que no es duplicado
        return $result;
    }
    
    /**
     * Marca un equipo como guardado y actualiza sus datos
     * @param int $equipId ID del equipo a guardar
     * @param array $datosEquipo Datos adicionales del equipo
     * @return bool True si se guardó correctamente, False en caso contrario
     */
    public function guardarEquipo($equipId, $datosEquipo = []) {
        try {
            // Iniciar una transacción para asegurar la consistencia de los datos
            $this->connexio->begin_transaction();
            
            // Actualizar el equipo como guardado
            $stmt = $this->connexio->prepare("
                UPDATE equips 
                SET guardado = 1, actualitzat_el = NOW()
                WHERE id_equip = ?
            ");
            
            $stmt->bind_param("i", $equipId);
            $stmt->execute();
            
            // Si hay un nombre nuevo para el equipo, actualizarlo
            if (isset($datosEquipo['nom_equip']) && !empty($datosEquipo['nom_equip'])) {
                $stmt = $this->connexio->prepare("
                    UPDATE equips 
                    SET nom_equip = ?
                    WHERE id_equip = ?
                ");
                $stmt->bind_param("si", $datosEquipo['nom_equip'], $equipId);
                $stmt->execute();
            }
            
            // Confirmar todos los cambios
            $this->connexio->commit();
            return true;
        } catch (Exception $e) {
            // Deshacer cambios si hay error
            $this->connexio->rollback();
            error_log("Error al guardar equipo: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra una acción relacionada con un equipo en el log del sistema
     * 
     * @param int $equipoId ID del equipo
     * @param string $accion Acción realizada (crear, editar, eliminar, guardar)
     * @return void
     */
    private function registrarLogEquipo($equipoId, $accion) {
        // Solo registrar si el usuario está en sesión
        if (!isset($_SESSION['usuari_id'])) {
            return;
        }
        
        $usuarioId = $_SESSION['usuari_id'];
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'desconocida';
        
        // Obtener datos del equipo para el detalle
        $stmt = $this->connexio->prepare("
            SELECT nom_equip FROM equips 
            WHERE id_equip = ?
        ");
        
        $stmt->bind_param("i", $equipoId);
        $stmt->execute();
        $result = $stmt->get_result();
        $equipo = $result->fetch_assoc();
        
        $detalles = json_encode([
            'equipo_id' => $equipoId,
            'nombre_equipo' => $equipo['nom_equip'] ?? 'Desconocido',
            'accion' => $accion
        ]);
        
        // Insertar en el log
        $stmt = $this->connexio->prepare("
            INSERT INTO logs_sistema (
                usuari_id, accio, detalls, ip, data_hora
            ) VALUES (
                ?, 'equipo_" . $accion . "', ?, ?, NOW()
            )
        ");
        
        $stmt->bind_param("iss", $usuarioId, $detalles, $ip);
        $stmt->execute();
    }

    /**
     * Obtener un equipo específico con todos sus datos
     *
     * @param int $equipId ID del equipo a obtener
     * @return array|false Datos del equipo o false si no se encuentra
     */
    public function getEquipo($equipId) {
        try {
            // Consulta para obtener datos básicos del equipo
            $stmt = $this->connexio->prepare("
                SELECT * FROM equips 
                WHERE id_equip = ?
            ");
            
            $stmt->bind_param("i", $equipId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return false;
            }
            
            $equip = $result->fetch_assoc();
            
            // Obtener los pokémon del equipo
            $equip['pokemons'] = $this->getPokemonsByEquipo($equipId);
            
            return $equip;
        } catch (Exception $e) {
            error_log("Error al obtener equipo: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener todos los equipos guardados de un usuario (para selección de equipo principal)
     *
     * @param int $usuariId ID del usuario
     * @return array Lista de equipos guardados con sus Pokémon
     */
    public function getEquiposGuardadosByUsuario($usuariId) {
        $equips = [];
        
        // Consulta para obtener equipos guardados del usuario
        $stmt = $this->connexio->prepare("
            SELECT * FROM equips 
            WHERE usuari_id = ? AND guardado = 1
            ORDER BY nom_equip
        ");
        
        $stmt->bind_param("i", $usuariId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($equip = $result->fetch_assoc()) {
            // Obtener los pokémon de cada equipo
            $equip['pokemons'] = $this->getPokemonsByEquipo($equip['id_equip']);
            $equips[] = $equip;
        }
        
        return $equips;
    }

    /**
     * Obtener un equipo por su ID
     *
     * @param int $equipId ID del equipo a buscar
     * @return array|false Datos del equipo o false si no se encuentra
     */
    public function getEquipById($equipId) {
        return $this->getEquipo($equipId);
    }
}
?>