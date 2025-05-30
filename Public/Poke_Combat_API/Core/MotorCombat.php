<?php
namespace Poke_Combat_API\Core;

use Poke_Combat_API\Config\Constants;
use Poke_Combat_API\Config\Configuracio;
use Poke_Combat_API\Repositoris\RepositoriBatalla;
use Poke_Combat_API\Repositoris\RepositoriPokemon;
use Poke_Combat_API\Repositoris\RepositoriMoviment;
use Poke_Combat_API\Repositoris\RepositoriAccioTorn;
use Poke_Combat_API\DTO\EntradaAccio;
use Poke_Combat_API\DTO\ResultatTorn;
use Poke_Combat_API\DTO\ResultatAtac;
use Poke_Combat_API\Utils\LogUtil;

/**
 * Motor principal del sistema de combat
 * Coordina tot el procés de combat entre Pokémon
 */
class MotorCombat {
    private $repositoriBatalla;
    private $repositoriPokemon;
    private $repositoriMoviment;
    private $repositoriAccioTorn;
    private $calculadorDany;
    private $determinadorOrdre;
    private $gestorCritic;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->repositoriBatalla = new RepositoriBatalla();
        $this->repositoriPokemon = new RepositoriPokemon();
        $this->repositoriMoviment = new RepositoriMoviment();
        $this->repositoriAccioTorn = new RepositoriAccioTorn();
        $this->calculadorDany = new CalculadorDany();
        $this->determinadorOrdre = new DeterminadorOrdre();
        $this->gestorCritic = new GestorCritic();
    }
    
    /**
     * Processa un torn complet amb les accions d'ambdós jugadors
     * 
     * @param int $batallaId ID de la batalla
     * @param array $accioJugador1 Acció del jugador 1
     * @param array $accioJugador2 Acció del jugador 2
     * @return ResultatTorn Resultat complet del torn
     */
    public function processarTorn($batallaId, $accioJugador1, $accioJugador2) {
        LogUtil::registrar("Iniciant processament del torn per a batalla $batallaId");
        
        // Obtenir dades de la batalla
        $batalla = $this->repositoriBatalla->obtenirBatalla($batallaId);
        if (!$batalla) {
            throw new \Exception("Batalla no trobada", Constants::ERROR_BATALLA_NO_TROBADA);
        }
        
        // Verificar que la batalla estigui activa
        if ($batalla['estat'] !== Constants::ESTAT_BATALLA_ACTIVA) {
            throw new \Exception("La batalla no està activa", Constants::ERROR_BATALLA_NO_ACTIVA);
        }
        
        // Obtenir dades dels Pokémon actius
        $pokemonActiu1 = $this->repositoriPokemon->obtenirPokemonBatalla($batalla['pokemon_actiu_1']);
        $pokemonActiu2 = $this->repositoriPokemon->obtenirPokemonBatalla($batalla['pokemon_actiu_2']);
        
        // Obtenir dades dels moviments
        $moviment1 = $this->repositoriMoviment->obtenirMoviment($accioJugador1['moviment_id']);
        $moviment2 = $this->repositoriMoviment->obtenirMoviment($accioJugador2['moviment_id']);
        
        // Crear objecte resultat
        $resultatTorn = new ResultatTorn();
        $resultatTorn->setIdBatalla($batallaId);
        $resultatTorn->setTornActual($batalla['torn_actual']);
        
        // Determinar ordre d'atac basat en prioritat i velocitat
        $ordreAtac = $this->determinadorOrdre->determinarOrdre(
            $pokemonActiu1,
            $pokemonActiu2,
            $moviment1,
            $moviment2
        );
        
        LogUtil::registrar("Ordre d'atac determinat: primer atacarà el jugador " . $ordreAtac['primer']);
        
        // Processar atacs en ordre
        if ($ordreAtac['primer'] === 1) {
            // Primer ataca jugador 1, després jugador 2
            $this->processarAtac($resultatTorn, $pokemonActiu1, $pokemonActiu2, $moviment1, 1);
            
            // Només processar el segon atac si el primer Pokémon no ha debilitat al segon
            if ($pokemonActiu2['ps_actuals'] > 0) {
                $this->processarAtac($resultatTorn, $pokemonActiu2, $pokemonActiu1, $moviment2, 2);
            }
        } else {
            // Primer ataca jugador 2, després jugador 1
            $this->processarAtac($resultatTorn, $pokemonActiu2, $pokemonActiu1, $moviment2, 2);
            
            // Només processar el segon atac si el primer Pokémon no ha debilitat al segon
            if ($pokemonActiu1['ps_actuals'] > 0) {
                $this->processarAtac($resultatTorn, $pokemonActiu1, $pokemonActiu2, $moviment1, 1);
            }
        }
        
        // Registrar les accions del torn a la base de dades
        $this->repositoriAccioTorn->registrarAccio(
            $batalla['id_batalla'],
            $batalla['usuari1_id'],
            $batalla['torn_actual'],
            Constants::ACCIO_MOVIMENT,
            $accioJugador1['moviment_id'],
            $batalla['pokemon_actiu_1']
        );
        
        $this->repositoriAccioTorn->registrarAccio(
            $batalla['id_batalla'],
            $batalla['usuari2_id'],
            $batalla['torn_actual'],
            Constants::ACCIO_MOVIMENT,
            $accioJugador2['moviment_id'],
            $batalla['pokemon_actiu_2']
        );
        
        // Actualitzar PS dels Pokémon a la base de dades
        $this->repositoriPokemon->actualitzarPS($batalla['pokemon_actiu_1'], $pokemonActiu1['ps_actuals']);
        $this->repositoriPokemon->actualitzarPS($batalla['pokemon_actiu_2'], $pokemonActiu2['ps_actuals']);
        
        // Verificar si algun Pokémon ha estat derrotat
        $batallaFinalitzada = $this->comprovarBatallaFinalitzada($batallaId);
        $resultatTorn->setBatallaFinalitzada($batallaFinalitzada);
        
        // Avançar al següent torn si la batalla no ha finalitzat
        if (!$batallaFinalitzada) {
            $this->repositoriBatalla->seguentTorn($batallaId);
        }
        
        LogUtil::registrar("Processament del torn completat per a batalla $batallaId");
        
        return $resultatTorn;
    }
    
    /**
     * Processa un atac individual
     */
    private function processarAtac($resultatTorn, $pokemonAtacant, $pokemonDefensor, $moviment, $indexJugador) {
        $resultatAtac = new ResultatAtac();
        $resultatAtac->setNomPokemonAtacant($pokemonAtacant['malnom'] ?: $pokemonAtacant['nombre']);
        $resultatAtac->setNomPokemonDefensor($pokemonDefensor['malnom'] ?: $pokemonDefensor['nombre']);
        $resultatAtac->setNomMoviment($moviment['nom_moviment']);
        $resultatAtac->setTipusMoviment($moviment['tipus_moviment']);
        
        // En aquesta fase inicial, tractem tots els moviments com atacs que causen dany
        // Per moviments no ofensius, simplement utilitzarem 0 de dany
        
        // Per moviments d'estat, no causarem dany per ara
        if ($moviment['categoria'] === Constants::CATEGORIA_ESTAT) {
            $resultatAtac->setDanyTotal(0);
            $resultatAtac->setEsCritic(false);
            $resultatAtac->setEfectivitat(Constants::EFECTIVITAT_NORMAL);
            $resultatAtac->addMissatge($pokemonAtacant['malnom'] . ' utilitza ' . $moviment['nom_moviment']);
        } else {
            // Determinar si és un cop crític
            $esCritic = $this->gestorCritic->esCritic($pokemonAtacant, $moviment);
            
            // Calcular dany
            $danyTotal = $this->calculadorDany->calcularDany(
                $pokemonAtacant,
                $pokemonDefensor,
                $moviment,
                $esCritic
            );
            
            // Actualitzar PS del defensor
            $psActuals = max(0, $pokemonDefensor['ps_actuals'] - $danyTotal);
            $pokemonDefensor['ps_actuals'] = $psActuals;
            
            // Actualitzar resultat
            $resultatAtac->setDanyTotal($danyTotal);
            $resultatAtac->setEsCritic($esCritic);
            $resultatAtac->setEfectivitat($this->calculadorDany->getUltimaEfectivitat());
            
            // Afegir missatges
            $resultatAtac->addMissatge($pokemonAtacant['malnom'] . ' utilitza ' . $moviment['nom_moviment']);
            
            if ($esCritic) {
                $resultatAtac->addMissatge('¡Cop crític!');
            }
            
            if ($this->calculadorDany->getUltimaEfectivitat() === Constants::EFECTIVITAT_SUPER) {
                $resultatAtac->addMissatge('¡És molt efectiu!');
            } elseif ($this->calculadorDany->getUltimaEfectivitat() === Constants::EFECTIVITAT_POC) {
                $resultatAtac->addMissatge('No és gaire efectiu...');
            } elseif ($this->calculadorDany->getUltimaEfectivitat() === Constants::EFECTIVITAT_IMMUNE) {
                $resultatAtac->addMissatge('No afecta a ' . $pokemonDefensor['malnom'] . '...');
            }
            
            if ($psActuals === 0) {
                $resultatAtac->addMissatge($pokemonDefensor['malnom'] . ' s\'ha debilitat');
            }
        }
        
        // Afegir el resultat de l'atac al resultat del torn
        if ($indexJugador === 1) {
            $resultatTorn->setResultatAtacJugador1($resultatAtac);
        } else {
            $resultatTorn->setResultatAtacJugador2($resultatAtac);
        }
    }
    
    /**
     * Comprova si la batalla ha finalitzat perquè tots els Pokémon d'un usuari estan debilitats
     */
    private function comprovarBatallaFinalitzada($batallaId) {
        $batalla = $this->repositoriBatalla->obtenirBatalla($batallaId);
        
        // Obtenir tots els Pokémon de l'equip 1
        $pokemonEquip1 = $this->repositoriPokemon->obtenirPokemonsEquip($batalla['equip1_id']);
        $totsDebilitatsEquip1 = true;
        
        foreach ($pokemonEquip1 as $pokemon) {
            $estatPokemon = $this->repositoriPokemon->obtenirEstatPokemonBatalla(
                $batallaId, 
                $batalla['usuari1_id'], 
                $pokemon['id_equip_pokemon']
            );
            
            if ($estatPokemon && $estatPokemon['estat_vital'] === Constants::ESTAT_VITAL_ACTIU) {
                $totsDebilitatsEquip1 = false;
                break;
            }
        }
        
        // Obtenir tots els Pokémon de l'equip 2
        $pokemonEquip2 = $this->repositoriPokemon->obtenirPokemonsEquip($batalla['equip2_id']);
        $totsDebilitatsEquip2 = true;
        
        foreach ($pokemonEquip2 as $pokemon) {
            $estatPokemon = $this->repositoriPokemon->obtenirEstatPokemonBatalla(
                $batallaId, 
                $batalla['usuari2_id'], 
                $pokemon['id_equip_pokemon']
            );
            
            if ($estatPokemon && $estatPokemon['estat_vital'] === Constants::ESTAT_VITAL_ACTIU) {
                $totsDebilitatsEquip2 = false;
                break;
            }
        }
        
        // Si tots els Pokémon d'un equip estan debilitats, la batalla ha finalitzat
        if ($totsDebilitatsEquip1 || $totsDebilitatsEquip2) {
            $guanyadorId = $totsDebilitatsEquip1 ? $batalla['usuari2_id'] : $batalla['usuari1_id'];
            $this->repositoriBatalla->finalitzarBatalla($batallaId, $guanyadorId);
            return true;
        }
        
        return false;
    }
}