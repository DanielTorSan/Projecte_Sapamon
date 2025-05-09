<?php
/**
 * API centralizada para el sistema de combate Pokémon
 * Esta API sirve como punto de entrada unificado para todas las acciones de combate
 */

// Configurar para reportar errores pero no mostrarlos al usuario
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/../logs/combat_api_errors.log');

// Establecer cabecera JSON para todas las respuestas
header('Content-Type: application/json');

try {
    session_start();
    require_once __DIR__ . '/../Model/configuracio.php';
    
    // Función para enviar respuestas JSON
    function enviarResposta($resposta, $codi = 200) {
        http_response_code($codi);
        echo json_encode($resposta);
        exit;
    }
    
    // Verificar autenticación
    if (!isset($_SESSION['usuari_id'])) {
        enviarResposta(['success' => false, 'message' => 'No autenticat'], 401);
    }
    
    $usuariId = $_SESSION['usuari_id'];
    
    // Router básico para la API
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Obtener datos de la petición
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        
        // Combinar con datos de $_POST para soportar ambos formatos
        $data = array_merge($_POST, $data);
        
        if (!isset($data['action'])) {
            enviarResposta(['success' => false, 'message' => 'Se requiere especificar una acción'], 400);
        }
        
        $action = $data['action'];
        
        // Validar datos mínimos
        if (!isset($data['batalla_id'])) {
            enviarResposta(['success' => false, 'message' => 'Se requiere el ID de batalla'], 400);
        }
        
        $batallaId = (int)$data['batalla_id'];
        
        // Router de acciones
        switch ($action) {
            case 'atacar':
                if (!isset($data['moviment_id'])) {
                    enviarResposta(['success' => false, 'message' => 'Se requiere el ID del movimiento'], 400);
                }
                
                require_once __DIR__ . '/funcions_combat/atacar_model.php';
                $atacarModel = new AtacarModel($connexio);
                $resultat = $atacarModel->registrarAtaque($batallaId, $usuariId, $data['moviment_id']);
                
                enviarResposta([
                    'success' => $resultat,
                    'message' => $resultat ? 'Ataque registrado correctamente' : 'Error al registrar el ataque'
                ]);
                break;
                
            case 'canviar_pokemon':
                if (!isset($data['pokemon_id'])) {
                    enviarResposta(['success' => false, 'message' => 'Se requiere el ID del Pokémon'], 400);
                }
                
                require_once __DIR__ . '/../Model/funcions_combat/canviar_pokemon_model.php';
                $canviarModel = new CanviarPokemonModel($connexio);
                $resultat = $canviarModel->registrarCanviPokemon($batallaId, $usuariId, $data['pokemon_id']);
                
                enviarResposta([
                    'success' => $resultat,
                    'message' => $resultat ? 'Cambio de Pokémon registrado correctamente' : 'Error al registrar el cambio'
                ]);
                break;
                
            case 'processar_torn':
                if (!isset($data['torn'])) {
                    // Obtener el turno actual de la batalla
                    $sql = "SELECT torn_actual FROM batalles WHERE id_batalla = ?";
                    $stmt = $connexio->prepare($sql);
                    $stmt->bind_param("i", $batallaId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows === 0) {
                        enviarResposta(['success' => false, 'message' => 'Batalla no encontrada'], 404);
                    }
                    
                    $data['torn'] = $result->fetch_assoc()['torn_actual'];
                }
                
                require_once __DIR__ . '/../Model/funcions_combat/processar_torn_model.php';
                $processarModel = new ProcessarTornModel($connexio);
                $resultat = $processarModel->processarTorn($batallaId, $data['torn']);
                
                enviarResposta($resultat);
                break;
                
            case 'estat_batalla':
                require_once __DIR__ . '/../Model/Combat_Model.php';
                $combatModel = new Combat_Model($connexio);
                $batalla = $combatModel->obtenerDatosBatalla($batallaId);
                
                if (!$batalla) {
                    enviarResposta(['success' => false, 'message' => 'Batalla no encontrada'], 404);
                }
                
                // Verificar que el usuario sea participante o espectador con token
                $esParticipant = $combatModel->esParticipante($batallaId, $usuariId);
                $tokenPublic = isset($data['token']) ? $data['token'] : null;
                $teAcces = $esParticipant || ($tokenPublic && $combatModel->esTokenValido($batallaId, $tokenPublic));
                
                if (!$teAcces) {
                    enviarResposta(['success' => false, 'message' => 'No tienes permiso para esta batalla'], 403);
                }
                
                // Obtener datos completos del estado de la batalla
                $ultimaAccion = isset($data['ultima_accion']) ? (int)$data['ultima_accion'] : 0;
                $accions = $combatModel->obtenerActualizacionesBatalla($batallaId, $ultimaAccion);
                $pokemon1 = $combatModel->obtenerEstadoPokemonActivo($batallaId, $batalla['usuari1_id']);
                $pokemon2 = $combatModel->obtenerEstadoPokemonActivo($batallaId, $batalla['usuari2_id']);
                
                enviarResposta([
                    'success' => true,
                    'batalla' => $batalla,
                    'pokemon1' => $pokemon1,
                    'pokemon2' => $pokemon2,
                    'acciones' => $accions,
                    'ultima_accion_id' => count($accions) > 0 ? $accions[count($accions)-1]['id'] : $ultimaAccion
                ]);
                break;
                
            default:
                enviarResposta(['success' => false, 'message' => 'Acción no reconocida'], 400);
                break;
        }
    } else if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
        $action = $_GET['action'];
        $batallaId = isset($_GET['batalla_id']) ? (int)$_GET['batalla_id'] : null;
        
        if ($action === 'estat_batalla' && $batallaId) {
            require_once __DIR__ . '/../Model/Combat_Model.php';
            $combatModel = new Combat_Model($connexio);
            $batalla = $combatModel->obtenerDatosBatalla($batallaId);
            
            if (!$batalla) {
                enviarResposta(['success' => false, 'message' => 'Batalla no encontrada'], 404);
            }
            
            // Verificar que el usuario sea participante o espectador con token
            $esParticipant = $combatModel->esParticipante($batallaId, $usuariId);
            $tokenPublic = isset($_GET['token']) ? $_GET['token'] : null;
            $teAcces = $esParticipant || ($tokenPublic && $combatModel->esTokenValido($batallaId, $tokenPublic));
            
            if (!$teAcces) {
                enviarResposta(['success' => false, 'message' => 'No tienes permiso para esta batalla'], 403);
            }
            
            // Obtener datos completos del estado de la batalla
            $ultimaAccion = isset($_GET['ultima_accion']) ? (int)$_GET['ultima_accion'] : 0;
            $accions = $combatModel->obtenerActualizacionesBatalla($batallaId, $ultimaAccion);
            $pokemon1 = $combatModel->obtenerEstadoPokemonActivo($batallaId, $batalla['usuari1_id']);
            $pokemon2 = $combatModel->obtenerEstadoPokemonActivo($batallaId, $batalla['usuari2_id']);
            
            enviarResposta([
                'success' => true,
                'batalla' => $batalla,
                'pokemon1' => $pokemon1,
                'pokemon2' => $pokemon2,
                'acciones' => $accions,
                'ultima_accion_id' => count($accions) > 0 ? $accions[count($accions)-1]['id'] : $ultimaAccion
            ]);
        } else {
            enviarResposta(['success' => false, 'message' => 'Acción no reconocida o falta el ID de batalla'], 400);
        }
    } else {
        enviarResposta(['success' => false, 'message' => 'Método no permitido o acción no especificada'], 405);
    }
} catch (Exception $e) {
    error_log("Error en combat_api.php: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    enviarResposta(['success' => false, 'message' => 'Error interno del servidor: ' . $e->getMessage()], 500);
}
?>