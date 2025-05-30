<?php
namespace Poke_Combat_API\Utils;

/**
 * Classe d'utilitat per a gestionar els tipus de Pokémon i càlculs relacionats
 */
class TipusUtils {
    /**
     * Matriu d'efectivitat de tipus
     * Clau 1: tipus atacant
     * Clau 2: tipus defensor
     * Valor: multiplicador (0: immune, 0.5: poc efectiu, 1: normal, 2: super efectiu)
     * 
     * @var array
     */
    private static $matriuEfectivitat = [
        'normal' => [
            'normal' => 1, 'foc' => 1, 'aigua' => 1, 'planta' => 1, 'elèctric' => 1, 'gel' => 1,
            'lluita' => 1, 'verí' => 1, 'terra' => 1, 'vol' => 1, 'psíquic' => 1, 'insecte' => 1,
            'roca' => 0.5, 'fantasma' => 0, 'drac' => 1, 'fosc' => 1, 'acer' => 0.5, 'fada' => 1
        ],
        'foc' => [
            'normal' => 1, 'foc' => 0.5, 'aigua' => 0.5, 'planta' => 2, 'elèctric' => 1, 'gel' => 2,
            'lluita' => 1, 'verí' => 1, 'terra' => 1, 'vol' => 1, 'psíquic' => 1, 'insecte' => 2,
            'roca' => 0.5, 'fantasma' => 1, 'drac' => 0.5, 'fosc' => 1, 'acer' => 2, 'fada' => 1
        ],
        'aigua' => [
            'normal' => 1, 'foc' => 2, 'aigua' => 0.5, 'planta' => 0.5, 'elèctric' => 1, 'gel' => 1,
            'lluita' => 1, 'verí' => 1, 'terra' => 2, 'vol' => 1, 'psíquic' => 1, 'insecte' => 1,
            'roca' => 2, 'fantasma' => 1, 'drac' => 0.5, 'fosc' => 1, 'acer' => 1, 'fada' => 1
        ],
        'planta' => [
            'normal' => 1, 'foc' => 0.5, 'aigua' => 2, 'planta' => 0.5, 'elèctric' => 1, 'gel' => 1,
            'lluita' => 1, 'verí' => 0.5, 'terra' => 2, 'vol' => 0.5, 'psíquic' => 1, 'insecte' => 0.5,
            'roca' => 2, 'fantasma' => 1, 'drac' => 0.5, 'fosc' => 1, 'acer' => 0.5, 'fada' => 1
        ],
        'elèctric' => [
            'normal' => 1, 'foc' => 1, 'aigua' => 2, 'planta' => 0.5, 'elèctric' => 0.5, 'gel' => 1,
            'lluita' => 1, 'verí' => 1, 'terra' => 0, 'vol' => 2, 'psíquic' => 1, 'insecte' => 1,
            'roca' => 1, 'fantasma' => 1, 'drac' => 0.5, 'fosc' => 1, 'acer' => 1, 'fada' => 1
        ],
        'gel' => [
            'normal' => 1, 'foc' => 0.5, 'aigua' => 0.5, 'planta' => 2, 'elèctric' => 1, 'gel' => 0.5,
            'lluita' => 1, 'verí' => 1, 'terra' => 2, 'vol' => 2, 'psíquic' => 1, 'insecte' => 1,
            'roca' => 1, 'fantasma' => 1, 'drac' => 2, 'fosc' => 1, 'acer' => 0.5, 'fada' => 1
        ],
        'lluita' => [
            'normal' => 2, 'foc' => 1, 'aigua' => 1, 'planta' => 1, 'elèctric' => 1, 'gel' => 2,
            'lluita' => 1, 'verí' => 0.5, 'terra' => 1, 'vol' => 0.5, 'psíquic' => 0.5, 'insecte' => 0.5,
            'roca' => 2, 'fantasma' => 0, 'drac' => 1, 'fosc' => 2, 'acer' => 2, 'fada' => 0.5
        ],
        'verí' => [
            'normal' => 1, 'foc' => 1, 'aigua' => 1, 'planta' => 2, 'elèctric' => 1, 'gel' => 1,
            'lluita' => 1, 'verí' => 0.5, 'terra' => 0.5, 'vol' => 1, 'psíquic' => 1, 'insecte' => 1,
            'roca' => 0.5, 'fantasma' => 0.5, 'drac' => 1, 'fosc' => 1, 'acer' => 0, 'fada' => 2
        ],
        'terra' => [
            'normal' => 1, 'foc' => 2, 'aigua' => 1, 'planta' => 0.5, 'elèctric' => 2, 'gel' => 1,
            'lluita' => 1, 'verí' => 2, 'terra' => 1, 'vol' => 0, 'psíquic' => 1, 'insecte' => 0.5,
            'roca' => 2, 'fantasma' => 1, 'drac' => 1, 'fosc' => 1, 'acer' => 2, 'fada' => 1
        ],
        'vol' => [
            'normal' => 1, 'foc' => 1, 'aigua' => 1, 'planta' => 2, 'elèctric' => 0.5, 'gel' => 1,
            'lluita' => 2, 'verí' => 1, 'terra' => 1, 'vol' => 1, 'psíquic' => 1, 'insecte' => 2,
            'roca' => 0.5, 'fantasma' => 1, 'drac' => 1, 'fosc' => 1, 'acer' => 0.5, 'fada' => 1
        ],
        'psíquic' => [
            'normal' => 1, 'foc' => 1, 'aigua' => 1, 'planta' => 1, 'elèctric' => 1, 'gel' => 1,
            'lluita' => 2, 'verí' => 2, 'terra' => 1, 'vol' => 1, 'psíquic' => 0.5, 'insecte' => 1,
            'roca' => 1, 'fantasma' => 1, 'drac' => 1, 'fosc' => 0, 'acer' => 0.5, 'fada' => 1
        ],
        'insecte' => [
            'normal' => 1, 'foc' => 0.5, 'aigua' => 1, 'planta' => 2, 'elèctric' => 1, 'gel' => 1,
            'lluita' => 0.5, 'verí' => 0.5, 'terra' => 1, 'vol' => 0.5, 'psíquic' => 2, 'insecte' => 1,
            'roca' => 1, 'fantasma' => 0.5, 'drac' => 1, 'fosc' => 2, 'acer' => 0.5, 'fada' => 0.5
        ],
        'roca' => [
            'normal' => 1, 'foc' => 2, 'aigua' => 1, 'planta' => 1, 'elèctric' => 1, 'gel' => 2,
            'lluita' => 0.5, 'verí' => 1, 'terra' => 0.5, 'vol' => 2, 'psíquic' => 1, 'insecte' => 2,
            'roca' => 1, 'fantasma' => 1, 'drac' => 1, 'fosc' => 1, 'acer' => 0.5, 'fada' => 1
        ],
        'fantasma' => [
            'normal' => 0, 'foc' => 1, 'aigua' => 1, 'planta' => 1, 'elèctric' => 1, 'gel' => 1,
            'lluita' => 0, 'verí' => 1, 'terra' => 1, 'vol' => 1, 'psíquic' => 2, 'insecte' => 1,
            'roca' => 1, 'fantasma' => 2, 'drac' => 1, 'fosc' => 0.5, 'acer' => 1, 'fada' => 1
        ],
        'drac' => [
            'normal' => 1, 'foc' => 1, 'aigua' => 1, 'planta' => 1, 'elèctric' => 1, 'gel' => 1,
            'lluita' => 1, 'verí' => 1, 'terra' => 1, 'vol' => 1, 'psíquic' => 1, 'insecte' => 1,
            'roca' => 1, 'fantasma' => 1, 'drac' => 2, 'fosc' => 1, 'acer' => 0.5, 'fada' => 0
        ],
        'fosc' => [
            'normal' => 1, 'foc' => 1, 'aigua' => 1, 'planta' => 1, 'elèctric' => 1, 'gel' => 1,
            'lluita' => 0.5, 'verí' => 1, 'terra' => 1, 'vol' => 1, 'psíquic' => 2, 'insecte' => 1,
            'roca' => 1, 'fantasma' => 2, 'drac' => 1, 'fosc' => 0.5, 'acer' => 1, 'fada' => 0.5
        ],
        'acer' => [
            'normal' => 1, 'foc' => 0.5, 'aigua' => 0.5, 'planta' => 1, 'elèctric' => 0.5, 'gel' => 2,
            'lluita' => 1, 'verí' => 1, 'terra' => 1, 'vol' => 1, 'psíquic' => 1, 'insecte' => 1,
            'roca' => 2, 'fantasma' => 1, 'drac' => 1, 'fosc' => 1, 'acer' => 0.5, 'fada' => 2
        ],
        'fada' => [
            'normal' => 1, 'foc' => 0.5, 'aigua' => 1, 'planta' => 1, 'elèctric' => 1, 'gel' => 1,
            'lluita' => 2, 'verí' => 0.5, 'terra' => 1, 'vol' => 1, 'psíquic' => 1, 'insecte' => 1,
            'roca' => 1, 'fantasma' => 1, 'drac' => 2, 'fosc' => 2, 'acer' => 0.5, 'fada' => 1
        ]
    ];
    
    /**
     * Calcula el modificador de tipus entre un tipus atacant i un o dos tipus defensors
     * 
     * @param string $tipusAtac Tipus del moviment atacant
     * @param string $tipusDefensor1 Tipus primari del defensor
     * @param string $tipusDefensor2 Tipus secundari del defensor (opcional)
     * @return float Modificador d'efectivitat (0, 0.25, 0.5, 1, 2, 4)
     */
    public static function calcularModificadorTipus($tipusAtac, $tipusDefensor1, $tipusDefensor2 = null) {
        // Normalitzar tipus a minúscules
        $tipusAtac = strtolower($tipusAtac);
        $tipusDefensor1 = strtolower($tipusDefensor1);
        
        // Obtenir modificador per al primer tipus
        $modificador1 = self::getEfectivitat($tipusAtac, $tipusDefensor1);
        
        // Si hi ha segon tipus, calcular i combinar
        if ($tipusDefensor2) {
            $tipusDefensor2 = strtolower($tipusDefensor2);
            $modificador2 = self::getEfectivitat($tipusAtac, $tipusDefensor2);
            return $modificador1 * $modificador2;
        }
        
        return $modificador1;
    }
    
    /**
     * Obté l'efectivitat d'un tipus atacant contra un tipus defensor
     * 
     * @param string $tipusAtac Tipus atacant
     * @param string $tipusDefensor Tipus defensor
     * @return float Efectivitat (0, 0.5, 1, 2)
     */
    private static function getEfectivitat($tipusAtac, $tipusDefensor) {
        // Si el tipus no està a la matriu, retornar efectivitat normal
        if (!isset(self::$matriuEfectivitat[$tipusAtac]) || !isset(self::$matriuEfectivitat[$tipusAtac][$tipusDefensor])) {
            LogUtil::registrarError("Tipus no reconegut en càlcul d'efectivitat: $tipusAtac vs $tipusDefensor");
            return 1;
        }
        
        return self::$matriuEfectivitat[$tipusAtac][$tipusDefensor];
    }
    
    /**
     * Obté una descripció textual de l'efectivitat
     * 
     * @param float $modificador Modificador d'efectivitat
     * @return string Descripció ("super", "normal", "poc", "immune")
     */
    public static function getDescripcioEfectivitat($modificador) {
        if ($modificador >= 2) {
            return 'super';
        } elseif ($modificador < 1 && $modificador > 0) {
            return 'poc';
        } elseif ($modificador === 0) {
            return 'immune';
        } else {
            return 'normal';
        }
    }
    
    /**
     * Comprova si un tipus és vàlid
     * 
     * @param string $tipus Tipus a comprovar
     * @return bool True si el tipus és vàlid
     */
    public static function esTipusValid($tipus) {
        return isset(self::$matriuEfectivitat[strtolower($tipus)]);
    }
    
    /**
     * Obté tots els tipus disponibles
     * 
     * @return array Llista de tipus
     */
    public static function getTipusDisponibles() {
        return array_keys(self::$matriuEfectivitat);
    }
}