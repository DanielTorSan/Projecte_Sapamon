<?php
/**
 * Controlador para gestionar los equipos de Pokémon
 */
class EquipControlador {
    private $connexio;
    private $equipModel;
    
    /**
     * Constructor del controlador de equipos
     */
    public function __construct($connexio) {
        $this->connexio = $connexio;
        
        require_once __DIR__ . "/../Model/EquipModel.php";
        $this->equipModel = new EquipModel($connexio);
    }
    
    /**
     * Mostrar la vista del gestor de equipos
     */
    public function mostrarGestorEquips() {
        // Verificar que el usuario está autenticado
        if (!isset($_SESSION['usuari_id'])) {
            header("Location: Vista/Auth_Vista.php");
            exit;
        }
        
        // Obtener los equipos del usuario
        $usuariId = $_SESSION['usuari_id'];
        $equipos = $this->equipModel->getEquiposByUsuario($usuariId);
        
        // Incluir la vista del gestor de equipos
        // Pasamos la variable $connexio para que esté disponible en la vista
        $connexio = $this->connexio;
        require_once "Vista/GestorEquips_Vista.php";
    }
    
    /**
     * Crear un nuevo equipo
     */
    public function crearEquipo($nombre, $usuariId) {
        return $this->equipModel->crearEquipo($nombre, $usuariId);
    }
    
    /**
     * Eliminar un equipo
     */
    public function eliminarEquipo($equipoId, $usuariId) {
        return $this->equipModel->eliminarEquipo($equipoId, $usuariId);
    }
    
    /**
     * Guardar un equipo completo verificando requisitos
     * 
     * @param int $equipoId ID del equipo a guardar
     * @param int $usuarioId ID del usuario propietario del equipo
     * @param array $datosEquipo Datos completos del equipo
     * @return array Resultado de la operación
     */
    public function guardarEquipo($equipId, $usuariId, $datosEquipo) {
        try {
            // Iniciar transacción
            $this->connexio->begin_transaction();
            
            // 1. Actualizar el nombre del equipo
            $stmt = $this->connexio->prepare("
                UPDATE equips 
                SET nom_equip = ? 
                WHERE id_equip = ? AND usuari_id = ?
            ");
            
            $stmt->bind_param("sii", $datosEquipo['nombre'], $equipId, $usuariId);
            $stmt->execute();
            
            if ($stmt->affected_rows === 0) {
                // Verificar si el equipo existe pero no se actualizó el nombre
                $stmtCheck = $this->connexio->prepare("
                    SELECT id_equip FROM equips 
                    WHERE id_equip = ? AND usuari_id = ?
                ");
                
                $stmtCheck->bind_param("ii", $equipId, $usuariId);
                $stmtCheck->execute();
                $result = $stmtCheck->get_result();
                
                if ($result->num_rows === 0) {
                    $this->connexio->rollback();
                    return ['success' => false, 'error' => 'Equipo no encontrado o no pertenece al usuario'];
                }
            }
            
            // 2. Limpiar el equipo actual
            $this->limpiarEquipo($equipId);
            $this->connexio->commit(); // Commit para asegurarse de que el equipo está limpio
            $this->connexio->begin_transaction(); // Iniciar nueva transacción
            
            // 3. Añadir los Pokémon y sus movimientos
            foreach ($datosEquipo['pokemon'] as $index => $pokemon) {
                if (!isset($pokemon['id']) || !isset($pokemon['nombre'])) {
                    continue; // Saltar datos de Pokémon inválidos
                }
                
                // Añadir el Pokémon al equipo
                $posicion = $index;
                $pokemonId = $pokemon['id'];
                $equipPokemonId = $this->agregarPokemon(
                    $equipId, 
                    $pokemonId, 
                    $posicion, 
                    $pokemon['nombre'] ?? '', 
                    $pokemon['nivel'] ?? 50
                );
                
                // Si falla la adición del Pokémon, continuar con el siguiente
                if (!$equipPokemonId) {
                    error_log("Error al añadir Pokémon {$pokemon['nombre']} (ID: {$pokemon['id']}) al equipo.");
                    continue;
                }
                
                // Hacer commit para asegurar que el pokemon existe en la base de datos
                $this->connexio->commit();
                $this->connexio->begin_transaction();
                
                // Si hay movimientos, añadirlos
                if (isset($pokemon['movimientos']) && is_array($pokemon['movimientos'])) {
                    foreach ($pokemon['movimientos'] as $movimiento) {
                        if (empty($movimiento['id']) || empty($movimiento['nombre'])) {
                            continue;
                        }
                        
                        $result = $this->agregarMovimiento(
                            $equipPokemonId,
                            $movimiento['id'],
                            $movimiento['nombre'],
                            $movimiento['tipo'] ?? '',
                            $movimiento['categoria'] ?? 'physical',
                            $movimiento['poder'] ?? null,
                            $movimiento['precision'] ?? null,
                            $movimiento['pp'] ?? 20,
                            $movimiento['prioridad'] ?? 0
                        );
                        
                        if (!$result['success']) {
                            error_log("Error al añadir movimiento {$movimiento['nombre']} al Pokémon ID: $equipPokemonId. Error: " . ($result['error'] ?? 'Desconocido'));
                        }
                    }
                }
                
                // Commit tras añadir los movimientos de cada Pokémon
                $this->connexio->commit();
                $this->connexio->begin_transaction();
            }
            
            // 4. Marcar el equipo como guardado
            $this->marcarEquipoGuardado($equipId);
            
            // Confirmar todos los cambios
            $this->connexio->commit();
            
            return [
                'success' => true,
                'mensaje' => 'Equipo actualizado correctamente',
                'id_equip' => $equipId
            ];
            
        } catch (Exception $e) {
            // En caso de error, deshacer los cambios
            $this->connexio->rollback();
            error_log("Error al guardar equipo ID $equipId: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error al guardar equipo: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Limpiar un equipo (eliminar todos los Pokémon y sus movimientos)
     * 
     * @param int $equipId ID del equipo a limpiar
     * @return bool Resultado de la operación
     */
    public function limpiarEquipo($equipId) {
        try {
            // Primero obtenemos todos los id_equip_pokemon asociados al equipo
            $stmt = $this->connexio->prepare("
                SELECT id_equip_pokemon 
                FROM equip_pokemon 
                WHERE equip_id = ?
            ");
            
            $stmt->bind_param("i", $equipId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            // Para cada equip_pokemon, eliminamos sus movimientos primero
            while ($row = $result->fetch_assoc()) {
                $equipPokemonId = $row['id_equip_pokemon'];
                
                // Eliminar todos los movimientos asociados
                $stmtDeleteMoves = $this->connexio->prepare("
                    DELETE FROM equips_moviments 
                    WHERE equip_pokemon_id = ?
                ");
                
                $stmtDeleteMoves->bind_param("i", $equipPokemonId);
                $stmtDeleteMoves->execute();
            }
            
            // Luego eliminamos todos los Pokémon del equipo
            $stmtDeletePokemons = $this->connexio->prepare("
                DELETE FROM equip_pokemon 
                WHERE equip_id = ?
            ");
            
            $stmtDeletePokemons->bind_param("i", $equipId);
            $stmtDeletePokemons->execute();
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error al limpiar equipo ID $equipId: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Añadir un Pokémon al equipo
     */
    public function agregarPokemon($equipId, $pokemonId, $posicion, $malnom = '', $nivell = 50) {
        try {
            // Verificar que la posición es válida (0-5)
            if ($posicion < 0 || $posicion > 5) {
                error_log("Error: Posición inválida para Pokémon: $posicion");
                return false;
            }

            // Obtener el sprite y nombre del Pokémon desde la API
            $pokemonDetails = $this->getPokemonDetails($pokemonId);
            
            // Registrar resultados detallados para depuración
            error_log("Agregando Pokémon ID: $pokemonId, Nombre provisto: $malnom, Posición: $posicion");
            
            if (!$pokemonDetails) {
                error_log("Error: No se pudieron obtener detalles para el Pokémon ID $pokemonId");
            }
            
            // Obtener sprite y nombre de la API
            $sprite = '';
            if (isset($pokemonDetails['sprites'])) {
                if (isset($pokemonDetails['sprites']['front_default']) && !empty($pokemonDetails['sprites']['front_default'])) {
                    $sprite = $pokemonDetails['sprites']['front_default'];
                } elseif (isset($pokemonDetails['sprites']['other']['official-artwork']['front_default'])) {
                    $sprite = $pokemonDetails['sprites']['other']['official-artwork']['front_default'];
                } elseif (isset($pokemonDetails['sprites']['other']['home']['front_default'])) {
                    $sprite = $pokemonDetails['sprites']['other']['home']['front_default'];
                }
            }
            
            // Asegurarse de que tenemos un sprite válido
            if (empty($sprite)) {
                error_log("Advertencia: No se pudo obtener sprite para el Pokémon ID $pokemonId, usando URL por defecto");
                $sprite = "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/$pokemonId.png";
            }
            
            $nombreApi = isset($pokemonDetails['name']) ? ucfirst($pokemonDetails['name']) : '';
            
            // Si no hay malnom pero tenemos nombre de la API, usarlo
            if (empty($malnom) && !empty($nombreApi)) {
                $malnom = $nombreApi;
                error_log("Usando nombre de API para Pokémon $pokemonId: $nombreApi");
            }
            
            error_log("Detalles finales - ID: $pokemonId, Nombre: $malnom, Sprite: $sprite");

            // Insertar en la tabla equip_pokemon
            $stmt = $this->connexio->prepare("
                INSERT INTO equip_pokemon (equip_id, pokeapi_id, malnom, nivell, posicio, sprite) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param("iisiis", $equipId, $pokemonId, $malnom, $nivell, $posicion, $sprite);
            $stmt->execute();
            
            // Verificar si se insertó correctamente y obtener el ID
            if ($stmt->affected_rows > 0) {
                $equipPokemonId = $stmt->insert_id;
                // Asegurarse de que el ID se ha obtenido correctamente y es un número válido
                if ($equipPokemonId > 0) {
                    error_log("Pokémon agregado correctamente con ID: $equipPokemonId");
                    return $equipPokemonId;
                }
            }
            
            error_log("Error al agregar Pokémon, no se insertó ninguna fila o no se obtuvo ID");
            return false;
        } catch (Exception $e) {
            error_log("Excepción al agregar Pokémon a equipo: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Agrega un movimiento a un Pokémon del equipo
     */
    public function agregarMovimiento($equipPokemonId, $moveId, $moveName, $moveType = '', 
                                    $moveCategory = 'estat', $power = null, $accuracy = null, 
                                    $pp = 5, $priority = 0) {
        try {
            // Log detailed information about the request
            error_log("Agregando movimiento - Pokemon ID: $equipPokemonId, Move ID: $moveId, Move: $moveName, Type: $moveType");
            
            // Asegurarse de que equipPokemonId sea un entero válido
            $equipPokemonId = intval($equipPokemonId);
            
            if ($equipPokemonId <= 0) {
                error_log("ERROR: ID del Pokémon inválido: $equipPokemonId");
                return ['success' => false, 'error' => 'ID de Pokémon inválido: ' . $equipPokemonId];
            }
            
            // Verificar que el Pokémon existe
            $stmtCheck = $this->connexio->prepare("
                SELECT id_equip_pokemon FROM equip_pokemon 
                WHERE id_equip_pokemon = ?
            ");
            $stmtCheck->bind_param("i", $equipPokemonId);
            $stmtCheck->execute();
            $stmtCheck->store_result();
            
            if ($stmtCheck->num_rows === 0) {
                error_log("ERROR: Pokemon no encontrado con ID: $equipPokemonId");
                return ['success' => false, 'error' => 'Pokemon no encontrado con ID: ' . $equipPokemonId];
            }
            
            // Verificar si ya hay 4 movimientos
            $stmtCount = $this->connexio->prepare("
                SELECT COUNT(*) as total FROM equips_moviments 
                WHERE equip_pokemon_id = ?
            ");
            $stmtCount->bind_param("i", $equipPokemonId);
            $stmtCount->execute();
            $countResult = $stmtCount->get_result();
            $row = $countResult->fetch_assoc();
            
            if ($row['total'] >= 4) {
                error_log("ERROR: El Pokémon con ID $equipPokemonId ya tiene 4 movimientos");
                return ['success' => false, 'error' => 'El Pokémon ya tiene 4 movimientos'];
            }

            // En lugar de diferentes tipos de bind, usaremos el enfoque más simple y eficiente:
            // Insertamos todos los valores directamente en la consulta
            // Para los valores NULL, las variables PHP ya se convierten correctamente a NULL SQL

            // Construir consulta base
            $query = "INSERT INTO equips_moviments 
                    (equip_pokemon_id, pokeapi_move_id, nom_moviment, tipus_moviment, categoria, poder, precisio, pp_maxims, prioritat) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->connexio->prepare($query);
            
            // Preparar los valores adecuadamente (convertir a NULL o enteros según corresponda)
            $poderValue = ($power === null || $power === '') ? null : (int)$power;
            $precisionValue = ($accuracy === null || $accuracy === '') ? null : (int)$accuracy;
            $ppValue = (int)$pp;
            $priorityValue = (int)$priority;
            
            // Validar category
            if (empty($moveCategory) || !in_array($moveCategory, ['físic', 'especial', 'estat'])) {
                $moveCategory = 'estat';
            }
            
            // Usar referencias para los valores NULL
            $stmt->bind_param("iisssiiis", 
                $equipPokemonId, 
                $moveId, 
                $moveName, 
                $moveType, 
                $moveCategory, 
                $poderValue, 
                $precisionValue, 
                $ppValue, 
                $priorityValue
            );
            
            if ($stmt->execute()) {
                $movimientoId = $stmt->insert_id;
                error_log("Movimiento '$moveName' agregado correctamente al Pokemon ID $equipPokemonId con ID de movimiento: $movimientoId");
                return ['success' => true, 'id_movimiento' => $movimientoId];
            } else {
                $error = $stmt->error;
                error_log("ERROR SQL al agregar movimiento: $error. Pokemon ID: $equipPokemonId, Move: $moveName");
                return ['success' => false, 'error' => 'Error SQL: ' . $error];
            }
        } catch (Exception $e) {
            $errorMsg = $e->getMessage();
            error_log("Excepción al agregar movimiento: $errorMsg. Pokemon ID: $equipPokemonId, Move: $moveName");
            return ['success' => false, 'error' => 'Error: ' . $errorMsg];
        }
    }
    
    /**
     * Eliminar un Pokémon del equipo
     */
    public function eliminarPokemon($equipoId, $posicion) {
        return $this->equipModel->eliminarPokemon($equipoId, $posicion);
    }
    
    /**
     * Obtener lista de Pokémon desde la PokeAPI
     */
    public function getPokemonList($limit = 20, $offset = 0) {
        return $this->equipModel->getPokemonListFromAPI($limit, $offset);
    }
    
    /**
     * Buscar Pokémon por nombre o tipo
     */
    public function buscarPokemon($searchTerm) {
        return $this->equipModel->searchPokemonInAPI($searchTerm);
    }
    
    /**
     * Obtener detalles de un Pokémon específico
     */
    public function getPokemonDetails($pokemonId) {
        // Cargar el servicio PokeAPI directamente para operaciones simples
        require_once __DIR__ . "/../Model/PokeAPIService.php";
        $pokeAPIService = new PokeAPIService($this->connexio);
        return $pokeAPIService->getPokemon($pokemonId);
    }
    
    /**
     * Eliminar un movimiento
     */
    public function eliminarMovimiento($movimientoId) {
        return $this->equipModel->eliminarMovimiento($movimientoId);
    }
    
    /**
     * Obtener movimientos disponibles para un Pokémon
     */
    public function getMovimientosDisponibles($pokemonId) {
        return $this->equipModel->getMovimientosDisponibles($pokemonId);
    }
    
    /**
     * Marcar un equipo como guardado
     * 
     * @param int $equipoId ID del equipo a marcar como guardado
     * @return bool Resultado de la operación
     */
    public function marcarEquipoGuardado($equipoId) {
        $stmt = $this->connexio->prepare("
            UPDATE equips SET guardado = 1, actualitzat_el = NOW()
            WHERE id_equip = ?
        ");
        
        $stmt->bind_param("i", $equipoId);
        return $stmt->execute() && $stmt->affected_rows > 0;
    }
}