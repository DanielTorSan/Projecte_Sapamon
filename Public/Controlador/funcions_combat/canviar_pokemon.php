<?php
/**
 * API para ejecutar cambios de Pokémon en combates
 */

// Configurar para reportar errores pero no mostrarlos al usuario
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/../../logs/combat_errors.log');

try {
    session_start();
    require_once __DIR__ . '/../../Model/configuracio.php';
    require_once __DIR__ . '/../../Model/funcions_combat/canviar_pokemon_model.php';
    
    // Función para enviar respuestas JSON
    function enviarResposta($resposta, $codi = 200) {
        http_response_code($codi);
        header('Content-Type: application/json');
        echo json_encode($resposta);
        exit;
    }
    
    // Verificar autenticación
    if (!isset($_SESSION['usuari_id'])) {
        enviarResposta(['success' => false, 'message' => 'No autenticat'], 401);
    }
    
    $usuariId = $_SESSION['usuari_id'];
    
    // Verificar método de petición
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        enviarResposta(['success' => false, 'message' => 'Método no permitido'], 405);
    }
    
    // Validar datos de la petición
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['batalla_id']) || !isset($data['pokemon_id'])) {
        enviarResposta(['success' => false, 'message' => 'Faltan datos obligatorios'], 400);
    }
    
    $batallaId = (int)$data['batalla_id'];
    $pokemonId = (int)$data['pokemon_id'];
    
    // Crear instancia del modelo y registrar el cambio de Pokémon
    $canviarPokemonModel = new CanviarPokemonModel($connexio);
    $resultat = $canviarPokemonModel->registrarCanviPokemon($batallaId, $usuariId, $pokemonId);
    
    if ($resultat) {
        enviarResposta(['success' => true, 'message' => 'Cambio de Pokémon registrado correctamente']);
    } else {
        enviarResposta(['success' => false, 'message' => 'Error al registrar el cambio de Pokémon'], 400);
    }
    
} catch (Exception $e) {
    error_log("Error en canviar_pokemon.php: " . $e->getMessage());
    enviarResposta(['success' => false, 'message' => 'Error interno del servidor'], 500);
}
?>