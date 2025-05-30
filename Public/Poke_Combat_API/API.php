<?php
/**
 * Punt d'entrada principal per a l'API de Combat Pokémon
 */

// Carregar autoloader
require_once __DIR__ . '/autoload.php';

use Poke_Combat_API\Utils\ResponseBuilder;
use Poke_Combat_API\Utils\LogUtil;
use Poke_Combat_API\Config\Constants;
use Poke_Combat_API\Serveis\CombatService;
use Poke_Combat_API\Serveis\PokeAPIService;
use Poke_Combat_API\DTO\EntradaAccio;

// Configurar capçalera per a API
header('Content-Type: application/json');

// Iniciar sessió per a obtenir dades d'usuari
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Comprovar si l'usuari està autenticat
if (!isset($_SESSION['usuari_id'])) {
    ResponseBuilder::outputError('Usuari no autenticat', Constants::ERROR_USUARI_NO_AUTORITZAT, null, 401);
    exit;
}

// Obtenir acció i mètode de la petició
$accio = isset($_GET['accio']) ? $_GET['accio'] : '';
$metode = $_SERVER['REQUEST_METHOD'];

try {
    // Processar petició segons mètode HTTP
    if ($metode === 'GET') {
        procesarPeticioGET($accio);
    } else if ($metode === 'POST') {
        procesarPeticioPOST($accio);
    } else {
        ResponseBuilder::outputError('Mètode no permès', null, null, 405);
    }
} catch (\Exception $e) {
    LogUtil::registrarError('Error processant petició API: ' . $e->getMessage());
    ResponseBuilder::outputError($e->getMessage(), $e->getCode());
}

/**
 * Processa peticions GET
 */
function procesarPeticioGET($accio) {
    $usuariId = $_SESSION['usuari_id'];
    
    switch ($accio) {
        case 'estat_batalla':
            // Obtenir ID de la batalla
            $idBatalla = isset($_GET['id_batalla']) ? intval($_GET['id_batalla']) : 0;
            if (!$idBatalla) {
                ResponseBuilder::outputError('ID de batalla no vàlid');
            }
            
            // Inicialitzar servei
            $combatService = new CombatService();
            
            // Comprovar si l'usuari està autoritzat (és participant o espectador)
            if (!$combatService->usuariAutoritzat($idBatalla, $usuariId)) {
                ResponseBuilder::outputError('No autoritzat a veure aquesta batalla', Constants::ERROR_USUARI_NO_AUTORITZAT, null, 403);
            }
            
            // Obtenir i retornar l'estat
            $estat = $combatService->obtenirEstatBatalla($idBatalla);
            ResponseBuilder::outputSuccess($estat);
            break;
            
        case 'dades_pokemon':
            // Obtenir ID del Pokémon
            $pokemonId = isset($_GET['pokemon_id']) ? intval($_GET['pokemon_id']) : 0;
            if (!$pokemonId) {
                ResponseBuilder::outputError('ID de Pokémon no vàlid');
            }
            
            // Inicialitzar servei
            $pokeAPIService = new PokeAPIService();
            
            // Obtenir i retornar dades
            $dadesPokemon = $pokeAPIService->obtenirPokemon($pokemonId);
            ResponseBuilder::outputSuccess($dadesPokemon);
            break;
            
        default:
            ResponseBuilder::outputError('Acció no reconeguda');
    }
}

/**
 * Processa peticions POST
 */
function procesarPeticioPOST($accio) {
    // Obtenir dades JSON del cos de la petició
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        ResponseBuilder::outputError('Format de dades invàlid');
    }
    
    $usuariId = $_SESSION['usuari_id'];
    
    switch ($accio) {
        case 'processar_torn':
            // Validar dades necessàries
            if (!isset($data['id_batalla']) || !isset($data['accio_jugador1']) || !isset($data['accio_jugador2'])) {
                ResponseBuilder::outputError('Falten paràmetres obligatoris');
            }
            
            $idBatalla = intval($data['id_batalla']);
            $accioJugador1 = $data['accio_jugador1'];
            $accioJugador2 = $data['accio_jugador2'];
            
            // Inicialitzar servei
            $combatService = new CombatService();
            
            // Comprovar autorització
            if (!$combatService->usuariAutoritzat($idBatalla, $usuariId)) {
                ResponseBuilder::outputError('No autoritzat a modificar aquesta batalla', Constants::ERROR_USUARI_NO_AUTORITZAT, null, 403);
            }
            
            // Processar torn i retornar resultat
            $resultat = $combatService->processarTorn($idBatalla, $accioJugador1, $accioJugador2);
            ResponseBuilder::outputSuccess($resultat->toArray());
            break;
            
        case 'rendicio':
            // Validar dades necessàries
            if (!isset($data['id_batalla'])) {
                ResponseBuilder::outputError('Falta ID de batalla');
            }
            
            $idBatalla = intval($data['id_batalla']);
            
            // Inicialitzar servei
            $combatService = new CombatService();
            
            // Processar rendició
            $resultat = $combatService->processarRendicio($idBatalla, $usuariId);
            if ($resultat) {
                ResponseBuilder::outputSuccess(null, 'Rendició processada correctament');
            } else {
                ResponseBuilder::outputError('No s\'ha pogut processar la rendició');
            }
            break;
            
        default:
            ResponseBuilder::outputError('Acció no reconeguda');
    }
}