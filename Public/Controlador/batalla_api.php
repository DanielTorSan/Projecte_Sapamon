<?php
/**
 * API para gestionar las batallas entre entrenadores
 */

// Configurar para capturar errores fatales y convertirlos en JSON
function fatal_error_handler() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_COMPILE_ERROR)) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Error fatal en el servidor: ' . $error['message'],
            'error_type' => 'fatal_error'
        ]);
        
        // Registrar el error en el log
        $log_file = dirname(__FILE__) . '/../logs/batalla_api_' . date('Y-m-d') . '.log';
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - ERROR FATAL: " . $error['message'] . 
                         " en " . $error['file'] . " línea " . $error['line'] . "\n", FILE_APPEND);
    }
}
register_shutdown_function('fatal_error_handler');

// Establecer el encabezado de contenido JSON desde el inicio
header('Content-Type: application/json');

// Mejorar el reporte de errores - mostrar en logs pero no al usuario
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores al usuario
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/../logs/batalla_api_errors.log');

// Log de todas las peticiones
$log_file = dirname(__FILE__) . '/../logs/batalla_api_' . date('Y-m-d') . '.log';
file_put_contents($log_file, date('Y-m-d H:i:s') . " - " . $_SERVER['REQUEST_URI'] . " - " . 
    (isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : 'none')) . "\n", FILE_APPEND);

try {
    session_start();
    require_once __DIR__ . '/../Model/configuracio.php';
    
    // Importar todos los modelos con rutas absolutas
    require_once __DIR__ . '/../Model/BatallaModel.php';
    require_once __DIR__ . '/../Model/EquipModel.php';
    require_once __DIR__ . '/../Model/UsuariModel.php';
    require_once __DIR__ . '/../Model/AmicsModel.php';
    require_once __DIR__ . '/../Model/Combat_Model.php';

    // Función para enviar respuesta de error
    function enviarError($mensaje, $codigo = 400) {
        global $log_file;
        // Log del error
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - ERROR: " . $mensaje . "\n", FILE_APPEND);
        
        http_response_code($codigo);
        echo json_encode([
            'success' => false,
            'message' => $mensaje
        ]);
        exit;
    }

    // Verificar autenticación
    if (!isset($_SESSION['usuari_id'])) {
        enviarError('No autenticat', 401);
    }

    $userId = $_SESSION['usuari_id'];
    // Log del usuario autenticado
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Usuario ID: " . $userId . "\n", FILE_APPEND);

    // Control de errores para la conexión a la base de datos
    if (!isset($connexio) || $connexio->connect_error) {
        enviarError('Error de connexió a la base de dades', 500);
    }

    // Instanciar modelos
    $batallaModel = new BatallaModel($connexio);
    $equipModel = new EquipModel($connexio);
    $usuariModel = new UsuariModel($connexio);
    $amicsModel = new AmicsModel($connexio);

    // Router básico para la API
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'desafios_pendientes':
                case 'desafiaments_pendents':
                    getDesafiosPendientes($userId, $batallaModel);
                    break;
                    
                case 'equipos_disponibles':
                case 'equips_disponibles':
                    getEquiposDisponibles($userId, $equipModel);
                    break;
                    
                case 'amigos_disponibles':
                case 'amics_disponibles':
                    getAmigosDisponibles($userId, $usuariModel, $amicsModel);
                    break;
                    
                case 'historial_batallas':
                case 'historial_batalles':
                    getHistorialBatallas($userId, $batallaModel);
                    break;
                    
                case 'obtener_actualizaciones':
                case 'obtenir_actualitzacions':
                    obtenerActualizacionesBatalla($userId, $batallaModel);
                    break;
                    
                default:
                    enviarError('Acció no reconeguda');
                    break;
            }
        } else {
            enviarError('Es requereix una acció');
        }
    } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'crear_desafio':
                case 'crear_desafiament':
                    crearDesafio($userId, $batallaModel);
                    break;
                    
                case 'aceptar_desafio':
                case 'acceptar_desafiament':
                    aceptarDesafio($userId, $batallaModel);
                    break;
                    
                case 'rechazar_desafio':
                case 'rebutjar_desafiament':
                    rechazarDesafio($userId, $batallaModel);
                    break;
                    
                case 'cancelar_desafio':
                case 'cancel·lar_desafiament':
                    cancelarDesafio($userId, $batallaModel);
                    break;
                
                case 'finalizar_batalla':
                    finalizarBatalla($userId, $batallaModel);
                    break;
                
                case 'realizar_accion_turno':
                    realizarAccionTurno($userId);
                    break;
                    
                default:
                    enviarError('Acció no reconeguda');
                    break;
            }
        } else {
            enviarError('Es requereix una acció');
        }
    } else {
        enviarError('Mètode no permès', 405);
    }
} catch (Exception $e) {
    // Capturar cualquier excepción y devolver un error formateado
    // Log del error completo
    error_log("Exception en batalla_api.php: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error en el servidor: ' . $e->getMessage(),
        'error_type' => 'exception'
    ]);
}

// ------------------------------- FUNCIONES API -------------------------------

/**
 * Obtener desafíos pendientes para un usuario
 */
function getDesafiosPendientes($userId, $batallaModel) {
    try {
        $desafios = $batallaModel->getDesafiosPendientes($userId);
        
        // Comprobar si el usuario tiene alguna batalla activa
        $combatModel = new Combat_Model($GLOBALS['connexio']);
        $batallaActiva = $combatModel->buscarBatallaActiva($userId);
        
        echo json_encode([
            'success' => true,
            'desafios' => $desafios,
            'batalla_activa' => $batallaActiva
        ]);
    } catch (Exception $e) {
        enviarError('Error al obtenir desafiaments: ' . $e->getMessage(), 500);
    }
}

/**
 * Obtener equipos disponibles para batallas
 */
function getEquiposDisponibles($userId, $equipModel) {
    try {
        $equipos = $equipModel->obtenirEquipsUsuari($userId);
        $equipoPrincipal = $equipModel->obtenirEquipPrincipal($userId);
        
        echo json_encode([
            'success' => true,
            'equipos' => $equipos,
            'equipo_principal' => $equipoPrincipal
        ]);
    } catch (Exception $e) {
        enviarError('Error al obtenir equips: ' . $e->getMessage(), 500);
    }
}

/**
 * Obtener lista de amigos disponibles para desafiar
 */
function getAmigosDisponibles($userId, $usuariModel, $amicsModel) {
    try {
        $amigos = $amicsModel->getAmicsUsuario($userId);

        $amigosInfo = [];
        foreach ($amigos as $amigo) {
            $amigoId = $amigo['id_usuari'];
            $infoAmigo = $usuariModel->obtenirUsuariPerId($amigoId);
            
            if ($infoAmigo) {
                $amigosInfo[] = [
                    'id_usuari' => $infoAmigo['id_usuari'],
                    'nom_usuari' => $infoAmigo['nom_usuari'],
                    'avatar' => $infoAmigo['avatar'],
                    'email' => $infoAmigo['email']
                ];
            }
        }
        
        echo json_encode([
            'success' => true,
            'amigos' => $amigosInfo
        ]);
    } catch (Exception $e) {
        enviarError('Error al obtenir amics: ' . $e->getMessage(), 500);
    }
}

/**
 * Obtener historial de batallas
 */
function getHistorialBatallas($userId, $batallaModel) {
    try {
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
        
        $batallas = $batallaModel->getHistorialBatallas($userId, $limit, $offset);
        
        echo json_encode([
            'success' => true,
            'batallas' => $batallas
        ]);
    } catch (Exception $e) {
        enviarError('Error al obtenir historial: ' . $e->getMessage(), 500);
    }
}

/**
 * Crear un desafío de batalla
 */
function crearDesafio($userId, $batallaModel) {
    try {
        if (!isset($_POST['retado_id']) || !isset($_POST['equipo_id'])) {
            enviarError('Es requereixen el ID del desafiat i el ID de l\'equip');
            return;
        }
        
        $retadoId = $_POST['retado_id'];
        $equipoId = $_POST['equipo_id'];
        
        $resultado = $batallaModel->crearDesafio($userId, $retadoId, $equipoId);
        
        echo json_encode($resultado);
    } catch (Exception $e) {
        enviarError('Error al crear desafiament: ' . $e->getMessage(), 500);
    }
}

/**
 * Aceptar un desafío de batalla
 */
function aceptarDesafio($userId, $batallaModel) {
    try {
        if (!isset($_POST['batalla_id']) || !isset($_POST['equipo_id'])) {
            enviarError('Es requereixen el ID de la batalla i el ID de l\'equip');
            return;
        }
        
        $batallaId = $_POST['batalla_id'];
        $equipoId = $_POST['equipo_id'];
        
        $resultado = $batallaModel->aceptarDesafio($batallaId, $userId, $equipoId);
        
        echo json_encode($resultado);
    } catch (Exception $e) {
        enviarError('Error al acceptar desafiament: ' . $e->getMessage(), 500);
    }
}

/**
 * Rechazar un desafío de batalla
 */
function rechazarDesafio($userId, $batallaModel) {
    try {
        if (!isset($_POST['batalla_id'])) {
            enviarError('Es requereix el ID de la batalla');
            return;
        }
        
        $batallaId = $_POST['batalla_id'];
        
        $resultado = $batallaModel->rechazarDesafio($batallaId, $userId);
        
        echo json_encode($resultado);
    } catch (Exception $e) {
        enviarError('Error al rebutjar desafiament: ' . $e->getMessage(), 500);
    }
}

/**
 * Cancelar un desafío de batalla
 */
function cancelarDesafio($userId, $batallaModel) {
    try {
        if (!isset($_POST['batalla_id'])) {
            enviarError('Es requereix el ID de la batalla');
            return;
        }
        
        $batallaId = $_POST['batalla_id'];
        
        $resultado = $batallaModel->cancelarDesafio($batallaId, $userId);
        
        echo json_encode($resultado);
    } catch (Exception $e) {
        enviarError('Error al cancel·lar desafiament: ' . $e->getMessage(), 500);
    }
}

/**
 * Finalizar una batalla
 */
function finalizarBatalla($userId, $batallaModel) {
    try {
        if (!isset($_POST['batalla_id'])) {
            enviarError('Es requereix ID de la batalla');
            return;
        }
        
        $batallaId = $_POST['batalla_id'];
        $ganadorId = isset($_POST['ganador_id']) ? $_POST['ganador_id'] : null;
        
        // Verificar que el usuario sea participante de la batalla
        $batalla = $batallaModel->obtenerBatallaPorId($batallaId);
        
        if (!$batalla) {
            enviarError('No s\'ha trobat la batalla');
            return;
        }
        
        // Verificar que el usuario es participante de la batalla
        if ($batalla['usuari1_id'] != $userId && $batalla['usuari2_id'] != $userId) {
            enviarError('No tens permís per finalitzar aquesta batalla', 403);
            return;
        }
        
        $resultado = $batallaModel->finalizarBatalla($batallaId, $ganadorId);
        
        if ($resultado['success']) {
            echo json_encode([
                'success' => true,
                'message' => 'Batalla finalitzada correctament'
            ]);
        } else {
            enviarError($resultado['message']);
        }
    } catch (Exception $e) {
        enviarError('Error al finalitzar la batalla: ' . $e->getMessage(), 500);
    }
}

/**
 * Obtener actualizaciones de una batalla en curso
 */
function obtenerActualizacionesBatalla($userId, $batallaModel) {
    try {
        if (!isset($_GET['batalla_id'])) {
            enviarError('Es requereix el ID de la batalla');
            return;
        }
        
        $batallaId = $_GET['batalla_id'];
        $ultimaAccion = isset($_GET['ultima_accion']) ? intval($_GET['ultima_accion']) : 0;
        
        // Cargar Combat_Model para obtener datos de la batalla
        $combatModel = new Combat_Model($GLOBALS['connexio']);
        
        // Verificar que la batalla existe
        $datosBatalla = $combatModel->obtenerDatosBatalla($batallaId);
        
        if (!$datosBatalla) {
            enviarError('No s\'ha trobat la batalla');
            return;
        }
        
        // Si es un participante o tiene token válido, obtener actualizaciones
        $esParticipante = $combatModel->esParticipante($batallaId, $userId);
        $tokenPublico = isset($_GET['token']) ? $_GET['token'] : null;
        $tieneAcceso = $esParticipante || ($tokenPublico && $combatModel->esTokenValido($batallaId, $tokenPublico));
        
        if (!$tieneAcceso) {
            enviarError('No tens permís per veure aquesta batalla', 403);
            return;
        }
        
        // Obtener actualizaciones de la batalla desde la última acción conocida
        $actualizaciones = $combatModel->obtenerActualizacionesBatalla($batallaId, $ultimaAccion);
        
        // Obtener el estado actual de los Pokémon en batalla
        $estadoPokemon1 = $combatModel->obtenerEstadoPokemonActivo($batallaId, $datosBatalla['usuari1_id']);
        $estadoPokemon2 = $combatModel->obtenerEstadoPokemonActivo($batallaId, $datosBatalla['usuari2_id']);
        
        // Retornar toda la información actualizada
        echo json_encode([
            'success' => true,
            'batalla' => [
                'id' => $batallaId,
                'estado' => $datosBatalla['estat'],
                'turno_usuario_id' => $datosBatalla['turno_usuario_id'],
                'ultima_actualizacion' => $datosBatalla['ultima_actualizacion'],
                'ronda_actual' => $datosBatalla['ronda_actual']
            ],
            'pokemon1' => $estadoPokemon1,
            'pokemon2' => $estadoPokemon2,
            'acciones' => $actualizaciones,
            'ultima_accion_id' => isset($actualizaciones[count($actualizaciones)-1]['id']) ? 
                                 $actualizaciones[count($actualizaciones)-1]['id'] : $ultimaAccion
        ]);
    } catch (Exception $e) {
        enviarError('Error al obtenir actualitzacions: ' . $e->getMessage(), 500);
    }
}

/**
 * Realizar una acción en el turno actual de la batalla
 */
function realizarAccionTurno($userId) {
    try {
        if (!isset($_POST['batalla_id'])) {
            enviarError('Es requereix el ID de la batalla');
            return;
        }
        
        if (!isset($_POST['tipus_accio'])) {
            enviarError('Es requereix el tipus d\'acció');
            return;
        }
        
        $batallaId = $_POST['batalla_id'];
        $tipusAccio = $_POST['tipus_accio'];
        $movimentId = isset($_POST['moviment_id']) ? $_POST['moviment_id'] : null;
        $pokemonId = isset($_POST['pokemon_id']) ? $_POST['pokemon_id'] : null;
        
        // Cargar Combat_Model para registrar la acción
        $combatModel = new Combat_Model($GLOBALS['connexio']);
        
        // Verificar que la batalla existe
        $datosBatalla = $combatModel->obtenerDatosBatalla($batallaId);
        
        if (!$datosBatalla) {
            enviarError('No s\'ha trobat la batalla');
            return;
        }
        
        // Verificar que el usuario es participante de la batalla
        if ($datosBatalla['usuari1_id'] != $userId && $datosBatalla['usuari2_id'] != $userId) {
            enviarError('No ets participant d\'aquesta batalla', 403);
            return;
        }
        
        // Verificar que es el turno del usuario
        if ($datosBatalla['torn_actual_id'] != $userId) {
            enviarError('No és el teu torn', 403);
            return;
        }
        
        // Validar que el tipo de acción es válido
        if (!in_array($tipusAccio, ['moviment', 'canvi_pokemon', 'rendicio'])) {
            enviarError('Tipus d\'acció no vàlid');
            return;
        }
        
        // Validar que se proporciona la información necesaria según el tipo de acción
        if ($tipusAccio === 'moviment' && !$movimentId) {
            enviarError('Es requereix el ID del moviment');
            return;
        } else if ($tipusAccio === 'canvi_pokemon' && !$pokemonId) {
            enviarError('Es requereix el ID del pokemon');
            return;
        }
        
        // Registrar la acción del turno
        $resultado = $combatModel->registrarAccioTorn($batallaId, $userId, $tipusAccio, $movimentId, $pokemonId);
        
        if ($resultado) {
            echo json_encode([
                'success' => true,
                'message' => 'Acció registrada correctament'
            ]);
        } else {
            enviarError('Error al registrar l\'acció');
        }
    } catch (Exception $e) {
        enviarError('Error al processar l\'acció: ' . $e->getMessage(), 500);
    }
}
?>