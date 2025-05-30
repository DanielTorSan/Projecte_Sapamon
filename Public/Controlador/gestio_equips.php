<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Verificar si hay algún output antes de este punto
if (ob_get_length()) ob_clean();

try {
    session_start();
    
    // Verificar que el usuario está autenticado
    if (!isset($_SESSION['usuari_id'])) {
        throw new Exception('Acceso no autorizado: usuario no autenticado');
    }

    require_once "../Model/configuracio.php";
    require_once "../Controlador/EquipControlador.php";

    $equipControlador = new EquipControlador($connexio);
    $usuariId = $_SESSION['usuari_id'];

    // Procesar la acción solicitada
    $action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

    if (empty($action)) {
        throw new Exception('No se especificó ninguna acción');
    }

    switch ($action) {
        case 'crear':
            // Crear un nuevo equipo
            if (!isset($_POST['nom_equip'])) {
                throw new Exception('Nom d\'equip no proporcionat');
            }
            
            $nomEquip = trim($_POST['nom_equip']);
            $resultado = $equipControlador->crearEquipo($nomEquip, $usuariId);
            
            if ($resultado) {
                echo json_encode(['success' => true, 'id_equip' => $resultado]);
            } else {
                throw new Exception('Error al crear l\'equip');
            }
            break;
            
        case 'editar':
            // Editar un equipo existente
            if (!isset($_POST['id_equip']) || !isset($_POST['nom_equip'])) {
                throw new Exception('Paràmetres incorrectes');
            }
            
            $equipId = intval($_POST['id_equip']);
            $nomEquip = trim($_POST['nom_equip']);
            
            // Actualizar el equipo en la base de datos
            $stmt = $connexio->prepare("
                UPDATE equips 
                SET nom_equip = ? 
                WHERE id_equip = ? AND usuari_id = ?
            ");
            
            $stmt->bind_param("sii", $nomEquip, $equipId, $usuariId);
            $success = $stmt->execute();
            
            if ($success && $stmt->affected_rows > 0) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('No s\'ha pogut actualitzar l\'equip');
            }
            break;
            
        case 'eliminar_equipo':
            // Eliminar un equipo
            if (!isset($_POST['equipo_id'])) {
                throw new Exception('ID d\'equip no proporcionat');
            }
            
            $equipId = intval($_POST['equipo_id']);
            $success = $equipControlador->eliminarEquipo($equipId, $usuariId);
            
            if ($success) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('No s\'ha pogut eliminar l\'equip');
            }
            break;
            
        case 'listar_pokemons':
            // Obtener lista paginada de Pokémon desde la PokeAPI
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
            $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
            
            $result = $equipControlador->getPokemonList($limit, $offset);
            echo json_encode(['success' => true, 'data' => $result]);
            break;
            
        case 'buscar_pokemon':
            // Buscar Pokémon por nombre o tipo
            if (!isset($_GET['query'])) {
                throw new Exception('Términos de búsqueda no proporcionados');
            }
            
            $searchTerm = $_GET['query'];
            $pokemons = $equipControlador->buscarPokemon($searchTerm);
            
            echo json_encode(['success' => true, 'pokemons' => $pokemons]);
            break;
            
        case 'pokemon_details':
            // Obtener detalles completos de un Pokémon
            if (!isset($_GET['pokemon_id'])) {
                throw new Exception('ID de Pokémon no proporcionado');
            }
            
            $pokemonId = $_GET['pokemon_id'];
            $pokemonDetails = $equipControlador->getPokemonDetails($pokemonId);
            
            if ($pokemonDetails) {
                echo json_encode(['success' => true, 'pokemon' => $pokemonDetails]);
            } else {
                throw new Exception('No se pudo obtener información del Pokémon');
            }
            break;
            
        case 'agregar_pokemon':
            // Agregar un pokémon al equipo
            if (!isset($_POST['id_equip']) || !isset($_POST['pokemon_id']) || !isset($_POST['posicio'])) {
                throw new Exception('Paràmetres incorrectes');
            }
            
            $equipId = intval($_POST['id_equip']);
            $pokemonId = intval($_POST['pokemon_id']);
            $posicio = intval($_POST['posicio']);
            $malnom = isset($_POST['malnom']) ? $_POST['malnom'] : '';
            $nivell = isset($_POST['nivell']) ? intval($_POST['nivell']) : 50;
            
            // Verificar que el equipo pertenece al usuario
            $stmt = $connexio->prepare("
                SELECT id_equip FROM equips 
                WHERE id_equip = ? AND usuari_id = ?
            ");
            
            $stmt->bind_param("ii", $equipId, $usuariId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception('Equip no trobat o no pertany a l\'usuari');
            }
            
            $success = $equipControlador->agregarPokemon($equipId, $pokemonId, $posicio, $malnom, $nivell);
            
            if ($success) {
                echo json_encode(['success' => true, 'equip_pokemon_id' => $success]);
            } else {
                throw new Exception('No s\'ha pogut afegir el Pokémon a l\'equip');
            }
            break;
            
        case 'eliminar_pokemon':
            // Eliminar un pokémon del equipo
            if (!isset($_POST['id_equip']) || !isset($_POST['posicio'])) {
                throw new Exception('Paràmetres incorrectes');
            }
            
            $equipId = intval($_POST['id_equip']);
            $posicio = intval($_POST['posicio']);
            
            // Verificar que el equipo pertenece al usuario
            $stmt = $connexio->prepare("
                SELECT id_equip FROM equips 
                WHERE id_equip = ? AND usuari_id = ?
            ");
            
            $stmt->bind_param("ii", $equipId, $usuariId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception('Equip no trobat o no pertany a l\'usuari');
            }
            
            $success = $equipControlador->eliminarPokemon($equipId, $posicio);
            
            if ($success) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('No s\'ha pogut eliminar el Pokémon de l\'equip');
            }
            break;
            
        case 'listar_movimientos':
            // Listar movimientos disponibles para un Pokémon
            if (!isset($_GET['pokemon_id'])) {
                throw new Exception('ID de Pokémon no proporcionado');
            }
            
            $pokemonId = $_GET['pokemon_id'];
            $movimientos = $equipControlador->getMovimientosDisponibles($pokemonId);
            
            echo json_encode(['success' => true, 'movimientos' => $movimientos]);
            break;
            
        case 'agregar_movimiento':
            // Agregar un movimiento a un Pokémon
            if (!isset($_POST['equip_pokemon_id']) || !isset($_POST['move_id']) || !isset($_POST['move_name'])) {
                throw new Exception('Parámetros incorrectos');
            }
            
            $equipPokemonId = intval($_POST['equip_pokemon_id']);
            $moveId = intval($_POST['move_id']);
            $moveName = $_POST['move_name'];
            $moveType = isset($_POST['move_type']) ? $_POST['move_type'] : '';
            $moveCategory = isset($_POST['move_category']) ? $_POST['move_category'] : 'estat';
            $power = isset($_POST['power']) ? intval($_POST['power']) : null;
            $accuracy = isset($_POST['accuracy']) ? intval($_POST['accuracy']) : null;
            $pp = isset($_POST['pp']) ? intval($_POST['pp']) : 5;
            $priority = isset($_POST['priority']) ? intval($_POST['priority']) : 0;
            
            // Verificar que el Pokémon pertenece al usuario
            $stmt = $connexio->prepare("
                SELECT ep.id_equip_pokemon 
                FROM equip_pokemon ep
                JOIN equips e ON e.id_equip = ep.equip_id
                WHERE ep.id_equip_pokemon = ? AND e.usuari_id = ?
            ");
            
            $stmt->bind_param("ii", $equipPokemonId, $usuariId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception('Pokémon no trobat o no pertany a l\'usuari');
            }
            
            $result = $equipControlador->agregarMovimiento(
                $equipPokemonId, $moveId, $moveName, $moveType, 
                $moveCategory, $power, $accuracy, $pp, $priority
            );
            
            echo json_encode($result);
            break;
            
        case 'eliminar_movimiento':
            // Eliminar un movimiento
            if (!isset($_POST['movimiento_id'])) {
                throw new Exception('ID de movimiento no proporcionado');
            }
            
            $movimientoId = intval($_POST['movimiento_id']);
            
            // Verificar que el movimiento pertenece al usuario
            $stmt = $connexio->prepare("
                SELECT em.id_equip_moviment 
                FROM equips_moviments em
                JOIN equip_pokemon ep ON em.equip_pokemon_id = ep.id_equip_pokemon
                JOIN equips e ON ep.equip_id = e.id_equip
                WHERE em.id_equip_moviment = ? AND e.usuari_id = ?
            ");
            
            $stmt->bind_param("ii", $movimientoId, $usuariId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception('Moviment no trobat o no pertany a l\'usuari');
            }
            
            $success = $equipControlador->eliminarMovimiento($movimientoId);
            
            if ($success) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('No s\'ha pogut eliminar el moviment');
            }
            break;
            
        case 'guardar_equipo':
            // Obtener los datos completos del equipo
            if (!isset($_POST['datos_equipo']) || empty($_POST['datos_equipo'])) {
                throw new Exception('No se han proporcionado datos del equipo');
            }
            
            $datosEquipo = json_decode($_POST['datos_equipo'], true);
            
            // Verificar si hubo error al decodificar el JSON
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Error decodificando datos del equipo: ' . json_last_error_msg());
            }
            
            // Validar datos mínimos
            if (empty($datosEquipo['nombre'])) {
                throw new Exception('El equipo debe tener un nombre');
            }
            
            if (!isset($datosEquipo['pokemon']) || !is_array($datosEquipo['pokemon'])) {
                throw new Exception('Datos de Pokémon inválidos');
            }
            
            if (count($datosEquipo['pokemon']) === 0) {
                throw new Exception('El equipo debe tener al menos 1 Pokémon');
            }
            
            if (count($datosEquipo['pokemon']) > 6) {
                throw new Exception('El equipo no puede tener más de 6 Pokémon');
            }
            
            // El ID del equipo puede ser opcional (para nuevos equipos)
            $equipId = isset($_POST['equip_id']) ? intval($_POST['equip_id']) : null;
            $usuariId = $_SESSION['usuari_id'];
            
            // Si no hay ID de equipo, primero creamos uno nuevo
            if ($equipId === null) {
                $equipId = $equipControlador->crearEquipo($datosEquipo['nombre'], $usuariId);
                
                if (!$equipId) {
                    throw new Exception('No se pudo crear un nuevo equipo');
                }
                
                // Una vez creado, procesamos la información de Pokémon
                foreach ($datosEquipo['pokemon'] as $index => $pokemon) {
                    if (!isset($pokemon['id']) || !isset($pokemon['nombre'])) {
                        continue; // Skip invalid Pokémon data
                    }
                    
                    // Añadir el Pokémon al equipo
                    $posicion = $index;
                    $pokemonId = $pokemon['id'];
                    $equipPokemonId = $equipControlador->agregarPokemon(
                        $equipId, 
                        $pokemonId, 
                        $posicion, 
                        $pokemon['nombre'] ?? '', 
                        $pokemon['nivel'] ?? 50
                    );
                    
                    // Si hay movimientos, añadirlos
                    if (isset($pokemon['movimientos']) && is_array($pokemon['movimientos'])) {
                        foreach ($pokemon['movimientos'] as $movimiento) {
                            if (empty($movimiento['id']) || empty($movimiento['nombre'])) {
                                continue;
                            }
                            
                            $equipControlador->agregarMovimiento(
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
                        }
                    }
                }
                
                // Marcar el equipo como guardado
                $equipControlador->marcarEquipoGuardado($equipId);
                
                echo json_encode([
                    'success' => true,
                    'mensaje' => 'Equipo creado y guardado correctamente',
                    'id_equip' => $equipId
                ]);
            } else {
                // Si hay ID de equipo, actualizamos uno existente
                $result = $equipControlador->guardarEquipo($equipId, $usuariId, $datosEquipo);
                
                if (!isset($result['success']) || !$result['success']) {
                    // Proporcionar mensaje de error más claro si no hay uno específico
                    $errorMsg = isset($result['error']) ? $result['error'] : 
                             (isset($result['mensaje']) ? $result['mensaje'] : 'Error desconocido al guardar el equipo');
                    throw new Exception($errorMsg);
                }
                
                echo json_encode($result);
            }
            break;
            
        case 'obtener_equipo':
            // Obtener los datos completos de un equipo
            if (!isset($_POST['equipo_id'])) {
                throw new Exception('ID de equipo no proporcionado');
            }
            
            $equipoId = intval($_POST['equipo_id']);
            
            // Verificar que el equipo pertenece al usuario
            $stmt = $connexio->prepare("
                SELECT e.* FROM equips e
                WHERE e.id_equip = ? AND e.usuari_id = ?
            ");
            
            $stmt->bind_param("ii", $equipoId, $usuariId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception('Equipo no encontrado o no pertenece al usuario');
            }
            
            $equipo = $result->fetch_assoc();
            
            // Obtener los Pokémon del equipo con sus movimientos
            $stmt = $connexio->prepare("
                SELECT 
                    ep.*, 
                    ep.pokeapi_id
                FROM 
                    equip_pokemon ep
                WHERE 
                    ep.equip_id = ?
                ORDER BY 
                    ep.posicio ASC
            ");
            
            $stmt->bind_param("i", $equipoId);
            $stmt->execute();
            $resultPokemon = $stmt->get_result();
            
            $pokemons = [];
            
            // Crear un array mapeado por posición para asegurar el orden correcto
            $pokemonsByPosition = [];
            
            while ($pokemon = $resultPokemon->fetch_assoc()) {
                // Para cada Pokémon, obtener sus movimientos
                $stmtMovs = $connexio->prepare("
                    SELECT * FROM equips_moviments 
                    WHERE equip_pokemon_id = ?
                ");
                
                $stmtMovs->bind_param("i", $pokemon['id_equip_pokemon']);
                $stmtMovs->execute();
                $resultMovs = $stmtMovs->get_result();
                
                $movimientos = [];
                while ($mov = $resultMovs->fetch_assoc()) {
                    $movimientos[] = $mov;
                }
                
                // Obtener datos del Pokémon de la API
                $pokeApiData = $equipControlador->getPokemonDetails($pokemon['pokeapi_id']);
                
                // Añadir datos adicionales al objeto del Pokémon
                $pokemon['nombre'] = isset($pokeApiData['name']) ? ucfirst($pokeApiData['name']) : '';
                $pokemon['types'] = isset($pokeApiData['types']) ? array_map(function($type) {
                    return $type['type']['name'];
                }, $pokeApiData['types']) : [];
                
                // Si no está el sprite en la base de datos, obtenerlo de la API
                if (empty($pokemon['sprite']) && $pokeApiData) {
                    $pokemon['sprite'] = isset($pokeApiData['sprites']['front_default']) 
                        ? $pokeApiData['sprites']['front_default']
                        : '';
                }
                
                $pokemon['movimientos'] = $movimientos;
                
                // Asignar el Pokémon a su posición
                $posicion = intval($pokemon['posicio']);
                $pokemonsByPosition[$posicion] = $pokemon;
            }
            
            // Crear el array final asegurando el orden por posición
            for ($i = 0; $i < 6; $i++) {
                if (isset($pokemonsByPosition[$i])) {
                    $pokemons[] = $pokemonsByPosition[$i];
                }
            }
            
            // Log para debugging
            error_log("Cargados " . count($pokemons) . " pokemon para el equipo $equipoId");
            
            // Añadir la lista de Pokémon al equipo
            $equipo['pokemons'] = $pokemons;
            
            echo json_encode([
                'success' => true,
                'equipo' => $equipo
            ]);
            break;
            
        default:
            throw new Exception('Acción no válida: ' . $action);
    }
} catch (Exception $e) {
    // Registrar error
    error_log('Error en gestio_equips.php: ' . $e->getMessage());
    
    // Devolver respuesta de error
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'action' => $action ?? 'undefined',
            'post_data' => isset($_POST) ? array_map(function($item) {
                return is_string($item) && strlen($item) > 100 ? substr($item, 0, 100) . '...' : $item;
            }, $_POST) : []
        ]
    ]);
} catch (Error $e) {
    // Capturar errores de PHP y devolverlos como JSON
    error_log('Error PHP en gestio_equips.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error interno: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>