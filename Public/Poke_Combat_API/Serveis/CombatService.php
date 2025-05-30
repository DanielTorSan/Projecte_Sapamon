<?php
namespace Poke_Combat_API\Serveis;

use Poke_Combat_API\Config\Constants;
use Poke_Combat_API\Core\MotorCombat;
use Poke_Combat_API\DTO\EntradaAccio;
use Poke_Combat_API\DTO\ResultatTorn;
use Poke_Combat_API\Repositoris\RepositoriBatalla;
use Poke_Combat_API\Repositoris\RepositoriAccioTorn;
use Poke_Combat_API\Utils\LogUtil;

/**
 * Servei principal per a gestionar el combat
 */
class CombatService {
    /**
     * Repositori de batalles
     * @var RepositoriBatalla
     */
    private $repositoriBatalla;
    
    /**
     * Repositori d'accions
     * @var RepositoriAccioTorn
     */
    private $repositoriAccioTorn;
    
    /**
     * Motor de combat
     * @var MotorCombat
     */
    private $motorCombat;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->repositoriBatalla = new RepositoriBatalla();
        $this->repositoriAccioTorn = new RepositoriAccioTorn();
        $this->motorCombat = new MotorCombat();
    }
    
    /**
     * Processa un torn de combat
     * 
     * @param int $idBatalla ID de la batalla
     * @param array $accioJugador1 Dades de l'acció del jugador 1
     * @param array $accioJugador2 Dades de l'acció del jugador 2
     * @return ResultatTorn Resultat del processament del torn
     * @throws \Exception Si hi ha un error en el processament
     */
    public function processarTorn($idBatalla, $accioJugador1, $accioJugador2) {
        LogUtil::registrar("Iniciant processament de torn per a batalla $idBatalla");
        
        // Verificar que la batalla existeix i està activa
        $batalla = $this->repositoriBatalla->obtenirBatalla($idBatalla);
        if (!$batalla) {
            throw new \Exception("Batalla no trobada", Constants::ERROR_BATALLA_NO_TROBADA);
        }
        
        if ($batalla['estat'] !== Constants::ESTAT_BATALLA_ACTIVA) {
            throw new \Exception("La batalla no està activa", Constants::ERROR_BATALLA_NO_ACTIVA);
        }
        
        // Convertir a DTOs per garantir la validesa de les dades
        $accioDTO1 = new EntradaAccio($accioJugador1);
        $accioDTO2 = new EntradaAccio($accioJugador2);
        
        // Processar el torn amb el motor de combat
        $resultat = $this->motorCombat->processarTorn($idBatalla, $accioDTO1, $accioDTO2);
        
        LogUtil::registrar("Torn processat amb èxit", ['batalla_id' => $idBatalla, 'torn' => $resultat->getTornActual()]);
        
        return $resultat;
    }
    
    /**
     * Obtenir l'estat actual d'una batalla
     * 
     * @param int $idBatalla ID de la batalla
     * @return array Estat actual de la batalla
     * @throws \Exception Si la batalla no existeix
     */
    public function obtenirEstatBatalla($idBatalla) {
        $batalla = $this->repositoriBatalla->obtenirBatalla($idBatalla);
        if (!$batalla) {
            throw new \Exception("Batalla no trobada", Constants::ERROR_BATALLA_NO_TROBADA);
        }
        
        // Obtenir les últimes accions (per mostrar missatges)
        $tornActual = $batalla['torn_actual'];
        $ultimesAccions = $this->repositoriAccioTorn->obtenirAccionsTorn($idBatalla, max(1, $tornActual - 1));
        
        return [
            'id_batalla' => $batalla['id_batalla'],
            'estat' => $batalla['estat'],
            'torn_actual' => $tornActual,
            'usuari1_id' => $batalla['usuari1_id'],
            'usuari2_id' => $batalla['usuari2_id'],
            'equip1_id' => $batalla['equip1_id'],
            'equip2_id' => $batalla['equip2_id'],
            'pokemon_actiu_1' => $batalla['pokemon_actiu_1'],
            'pokemon_actiu_2' => $batalla['pokemon_actiu_2'],
            'guanyador_id' => $batalla['guanyador_id'],
            'abandonada_id' => $batalla['abandonada_id'],
            'batalla_finalitzada' => $batalla['estat'] === Constants::ESTAT_BATALLA_ACABADA,
            'ultimesAccions' => $ultimesAccions
        ];
    }
    
    /**
     * Verificar si un usuari està autoritzat a participar en una batalla
     * 
     * @param int $idBatalla ID de la batalla
     * @param int $usuariId ID de l'usuari
     * @return bool True si l'usuari està autoritzat
     */
    public function usuariAutoritzat($idBatalla, $usuariId) {
        $batalla = $this->repositoriBatalla->obtenirBatalla($idBatalla);
        if (!$batalla) {
            return false;
        }
        
        return ($batalla['usuari1_id'] == $usuariId || $batalla['usuari2_id'] == $usuariId);
    }
    
    /**
     * Processar una rendició
     * 
     * @param int $idBatalla ID de la batalla
     * @param int $usuariId ID de l'usuari que es rendeix
     * @return bool True si s'ha processat correctament
     */
    public function processarRendicio($idBatalla, $usuariId) {
        $batalla = $this->repositoriBatalla->obtenirBatalla($idBatalla);
        if (!$batalla || $batalla['estat'] !== Constants::ESTAT_BATALLA_ACTIVA) {
            return false;
        }
        
        if ($batalla['usuari1_id'] != $usuariId && $batalla['usuari2_id'] != $usuariId) {
            return false;
        }
        
        // Determinar el guanyador (l'altre jugador)
        $guanyadorId = ($batalla['usuari1_id'] == $usuariId) ? $batalla['usuari2_id'] : $batalla['usuari1_id'];
        
        // Asegurar que el torn no es nulo
        $torn = $batalla['torn_actual'] ?? 1; // Si es nulo, usar 1 como valor por defecto
        
        // Registrar l'acció de rendició
        $this->repositoriAccioTorn->registrarAccio(
            $idBatalla,
            $usuariId,
            $torn,
            Constants::ACCIO_RENDICIO
        );
        
        // Finalitzar la batalla
        return $this->repositoriBatalla->finalitzarBatalla($idBatalla, $guanyadorId, $usuariId);
    }
}