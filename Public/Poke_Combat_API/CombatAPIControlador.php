<?php
/**
 * Controlador per a la integració de l'API de combat amb el sistema existent
 */

require_once __DIR__ . '/../Poke_Combat_API/autoload.php';

use Poke_Combat_API\Serveis\CombatService;
use Poke_Combat_API\Serveis\PokeAPIService;
use Poke_Combat_API\Config\Constants;
use Poke_Combat_API\Utils\LogUtil;

/**
 * Classe controladora que serveix de pont entre el sistema existent i l'API de combat
 */
class CombatAPIControlador {
    /**
     * Servei de combat
     * @var CombatService
     */
    private $combatService;
    
    /**
     * Servei de PokeAPI
     * @var PokeAPIService
     */
    private $pokeAPIService;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->combatService = new CombatService();
        $this->pokeAPIService = new PokeAPIService();
    }
    
    /**
     * Processa un torn de combat
     * 
     * @param int $idBatalla ID de la batalla
     * @param array $accioJugador1 Dades de l'acció del jugador 1
     * @param array $accioJugador2 Dades de l'acció del jugador 2
     * @return array Resultat del processament del torn
     */
    public function processarTorn($idBatalla, $accioJugador1, $accioJugador2) {
        try {
            $resultat = $this->combatService->processarTorn($idBatalla, $accioJugador1, $accioJugador2);
            return [
                'exit' => true,
                'resultat' => $resultat->toArray()
            ];
        } catch (\Exception $e) {
            LogUtil::registrarError("Error processant torn: " . $e->getMessage());
            return [
                'exit' => false,
                'error' => $e->getMessage(),
                'codi' => $e->getCode()
            ];
        }
    }
    
    /**
     * Obté l'estat actual d'una batalla
     * 
     * @param int $idBatalla ID de la batalla
     * @return array Estat de la batalla
     */
    public function obtenirEstatBatalla($idBatalla) {
        try {
            $estat = $this->combatService->obtenirEstatBatalla($idBatalla);
            return [
                'exit' => true,
                'estat' => $estat
            ];
        } catch (\Exception $e) {
            LogUtil::registrarError("Error obtenint estat batalla: " . $e->getMessage());
            return [
                'exit' => false,
                'error' => $e->getMessage(),
                'codi' => $e->getCode()
            ];
        }
    }
    
    /**
     * Processa una rendició
     * 
     * @param int $idBatalla ID de la batalla
     * @param int $usuariId ID de l'usuari que es rendeix
     * @return array Resultat del processament
     */
    public function processarRendicio($idBatalla, $usuariId) {
        try {
            $resultat = $this->combatService->processarRendicio($idBatalla, $usuariId);
            return [
                'exit' => $resultat,
                'missatge' => $resultat ? 'Rendició processada correctament' : 'No s\'ha pogut processar la rendició'
            ];
        } catch (\Exception $e) {
            LogUtil::registrarError("Error processant rendició: " . $e->getMessage());
            return [
                'exit' => false,
                'error' => $e->getMessage(),
                'codi' => $e->getCode()
            ];
        }
    }
    
    /**
     * Obté dades d'un Pokémon des de PokeAPI
     * 
     * @param int|string $idONom ID o nom del Pokémon
     * @return array Dades del Pokémon
     */
    public function obtenirDadesPokemon($idONom) {
        try {
            $dades = $this->pokeAPIService->obtenirPokemon($idONom);
            return [
                'exit' => true,
                'pokemon' => $dades
            ];
        } catch (\Exception $e) {
            LogUtil::registrarError("Error obtenint dades Pokémon: " . $e->getMessage());
            return [
                'exit' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}