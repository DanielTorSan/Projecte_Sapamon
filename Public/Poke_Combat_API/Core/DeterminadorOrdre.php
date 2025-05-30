<?php
namespace Poke_Combat_API\Core;

use Poke_Combat_API\Utils\LogUtil;

/**
 * Classe per determinar l'ordre d'atac entre dos Pokémon
 */
class DeterminadorOrdre {
    /**
     * Determina l'ordre d'atac entre dos Pokémon
     * Retorna array amb ['primer' => 1|2] indicant quin jugador va primer
     * 
     * @param array $pokemon1 Dades del Pokémon del jugador 1
     * @param array $pokemon2 Dades del Pokémon del jugador 2
     * @param array $moviment1 Dades del moviment escollit pel jugador 1
     * @param array $moviment2 Dades del moviment escollit pel jugador 2
     * @return array Informació sobre quin Pokémon ataca primer
     */
    public function determinarOrdre($pokemon1, $pokemon2, $moviment1, $moviment2) {
        LogUtil::registrar("Determinant ordre d'atac");
        
        // Primer revisar prioritat del moviment
        $prioritat1 = $moviment1['prioritat'] ?? 0;
        $prioritat2 = $moviment2['prioritat'] ?? 0;
        
        if ($prioritat1 > $prioritat2) {
            LogUtil::registrar("El jugador 1 té prioritat més alta: $prioritat1 vs $prioritat2");
            return ['primer' => 1];
        } elseif ($prioritat1 < $prioritat2) {
            LogUtil::registrar("El jugador 2 té prioritat més alta: $prioritat2 vs $prioritat1");
            return ['primer' => 2];
        }
        
        // Si tenen la mateixa prioritat, comparar velocitat
        $velocitat1 = $pokemon1['stats']['velocitat'] ?? 0;
        $velocitat2 = $pokemon2['stats']['velocitat'] ?? 0;
        
        LogUtil::registrar("Comparant velocitats: $velocitat1 vs $velocitat2");
        
        if ($velocitat1 > $velocitat2) {
            return ['primer' => 1];
        } elseif ($velocitat1 < $velocitat2) {
            return ['primer' => 2];
        }
        
        // Si tenen la mateixa velocitat, escollir aleatòriament
        $primer = (mt_rand(0, 1) === 0) ? 1 : 2;
        LogUtil::registrar("Mateixa velocitat, elecció aleatòria: jugador $primer");
        
        return ['primer' => $primer];
    }
}