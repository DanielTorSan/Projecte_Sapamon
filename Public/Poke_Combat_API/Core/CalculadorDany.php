<?php
namespace Poke_Combat_API\Core;

use Poke_Combat_API\Config\Constants;
use Poke_Combat_API\Utils\TipusUtils;

/**
 * Classe per calcular el dany que causa un atac
 */
class CalculadorDany {
    /**
     * Guarda l'última efectivitat calculada per a un atac
     * @var string
     */
    private $ultimaEfectivitat = Constants::EFECTIVITAT_NORMAL;
    
    /**
     * Calcula el dany que un atac causarà
     * 
     * @param array $pokemonAtacant Dades del Pokémon atacant
     * @param array $pokemonDefensor Dades del Pokémon defensor
     * @param array $moviment Dades del moviment utilitzat
     * @param bool $esCritic Indica si l'atac és un cop crític
     * @return int Dany total que causarà l'atac
     */
    public function calcularDany($pokemonAtacant, $pokemonDefensor, $moviment, $esCritic) {
        // Si el moviment és d'estat, no causa dany
        if ($moviment['categoria'] === Constants::CATEGORIA_ESTAT) {
            $this->ultimaEfectivitat = Constants::EFECTIVITAT_NORMAL;
            return 0;
        }
        
        // Nivell del Pokémon atacant
        $nivell = $pokemonAtacant['nivell'];
        
        // Atac de l'atacant (físic o especial segons categoria del moviment)
        if ($moviment['categoria'] === Constants::CATEGORIA_FISIC) {
            $atac = $pokemonAtacant['stats'][Constants::STAT_ATAC];
        } else { // especial
            $atac = $pokemonAtacant['stats'][Constants::STAT_ATAC_ESPECIAL];
        }
        
        // Defensa del defensor (física o especial segons categoria del moviment)
        if ($moviment['categoria'] === Constants::CATEGORIA_FISIC) {
            $defensa = $pokemonDefensor['stats'][Constants::STAT_DEFENSA];
        } else { // especial
            $defensa = $pokemonDefensor['stats'][Constants::STAT_DEFENSA_ESPECIAL];
        }
        
        // Poder base del moviment
        $poder = $moviment['poder'];
        if (!$poder) {
            // Si el moviment no té poder (moviments d'estat), no causa dany
            $this->ultimaEfectivitat = Constants::EFECTIVITAT_NORMAL;
            return 0;
        }
        
        // Calcular modificador de tipus (efectivitat)
        $tipusMoviment = $moviment['tipus_moviment'];
        $tipusDefensor1 = $pokemonDefensor['tipus'][0];
        $tipusDefensor2 = isset($pokemonDefensor['tipus'][1]) ? $pokemonDefensor['tipus'][1] : null;
        
        $modificadorTipus = TipusUtils::calcularModificadorTipus($tipusMoviment, $tipusDefensor1, $tipusDefensor2);
        
        // Guardar l'efectivitat per a missatges
        if ($modificadorTipus > 1) {
            $this->ultimaEfectivitat = Constants::EFECTIVITAT_SUPER;
        } elseif ($modificadorTipus < 1 && $modificadorTipus > 0) {
            $this->ultimaEfectivitat = Constants::EFECTIVITAT_POC;
        } elseif ($modificadorTipus === 0) {
            $this->ultimaEfectivitat = Constants::EFECTIVITAT_IMMUNE;
        } else {
            $this->ultimaEfectivitat = Constants::EFECTIVITAT_NORMAL;
        }
        
        // STAB (Same Type Attack Bonus)
        $stab = 1.0;
        if (in_array($tipusMoviment, $pokemonAtacant['tipus'])) {
            $stab = 1.5;
        }
        
        // Crític
        $modificadorCritic = $esCritic ? 1.5 : 1.0;
        
        // Número aleatori entre 0.85 i 1.0
        $aleatori = mt_rand(85, 100) / 100;
        
        // Fórmula de dany de Pokémon
        // ((2 * Nivell / 5 + 2) * Poder * Atac / Defensa / 50 + 2) * Modificadors
        $danyBase = ((2 * $nivell / 5 + 2) * $poder * $atac / $defensa / 50 + 2);
        $danyTotal = $danyBase * $stab * $modificadorTipus * $modificadorCritic * $aleatori;
        
        // Si és immú, retornar 0
        if ($this->ultimaEfectivitat === Constants::EFECTIVITAT_IMMUNE) {
            return 0;
        }
        
        // Altrament, com a mínim 1 de dany
        return max(1, floor($danyTotal));
    }
    
    /**
     * Obtenir l'última efectivitat calculada
     * @return string
     */
    public function getUltimaEfectivitat() {
        return $this->ultimaEfectivitat;
    }
}