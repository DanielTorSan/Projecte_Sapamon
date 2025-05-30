<?php
namespace Poke_Combat_API\Core;

use Poke_Combat_API\Utils\LogUtil;

/**
 * Classe per gestionar cops crítics en el combat
 */
class GestorCritic {
    /**
     * Moviments amb alta probabilitat de crítics
     * @var array
     */
    private $movimentsAltCritic = [
        'slash', 'aerialace', 'nightslash', 'shadowclaw', 
        'stoneedge', 'crosschop', 'psychocut', 'razorleaf',
        'karatechop', 'crabhammer', 'razorwind', 'skyattack'
    ];
    
    /**
     * Determina si un atac és crític
     * 
     * @param array $pokemonAtacant Dades del Pokémon atacant
     * @param array $moviment Dades del moviment utilitzat
     * @return bool True si l'atac és crític, false altrament
     */
    public function esCritic($pokemonAtacant, $moviment) {
        // Ràtio base de crític (1/24 en els jocs moderns)
        $ratioBase = 1/24;
        
        // Si el moviment té alta probabilitat de crític
        $idMoviment = $moviment['id_api'] ?? '';
        if (in_array(strtolower($idMoviment), $this->movimentsAltCritic)) {
            $ratioBase *= 2;
            LogUtil::registrar("Moviment {$moviment['nom_moviment']} té alta probabilitat de crític. Ràtio: $ratioBase");
        }
        
        // Número aleatori entre 0 i 1
        $random = mt_rand() / mt_getrandmax();
        
        // Comprovar si el número aleatori està per sota del ràtio de crític
        $esCritic = $random <= $ratioBase;
        
        if ($esCritic) {
            LogUtil::registrar("Atac crític! Random: $random, Ràtio: $ratioBase");
        }
        
        return $esCritic;
    }
}