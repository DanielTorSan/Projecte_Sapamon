<?php
namespace Poke_Combat_API\Config;

/**
 * Configuració general per a l'API de combat Pokémon
 */
class Configuracio {
    /**
     * Connexió a la base de dades
     * @var mysqli
     */
    private static $connexio = null;
    
    /**
     * Ruta base del projecte
     * @var string
     */
    private static $rutaBase = '';
    
    /**
     * Obtenir la connexió a la base de dades
     * @return mysqli
     */
    public static function getConnexio() {
        if (self::$connexio === null) {
            // Si no tenim connexió, intentar obtenir-la del Model/configuracio.php principal
            if (isset($GLOBALS['connexio'])) {
                self::$connexio = $GLOBALS['connexio'];
            } else {
                // Si no està disponible, crear-la
                require_once __DIR__ . '/../../Model/configuracio.php';
                if (isset($connexio)) {
                    self::$connexio = $connexio;
                }
            }
        }
        
        return self::$connexio;
    }
    
    /**
     * Establir la connexió a la base de dades
     * @param mysqli $connexio
     */
    public static function setConnexio($connexio) {
        self::$connexio = $connexio;
    }
    
    /**
     * Obtenir la ruta base del projecte
     * @return string
     */
    public static function getRutaBase() {
        if (empty(self::$rutaBase)) {
            self::$rutaBase = dirname(__FILE__, 3); // 3 nivells amunt des d'aquest fitxer
        }
        
        return self::$rutaBase;
    }
    
    /**
     * Obtenir la URL de la PokeAPI
     * @return string
     */
    public static function getPokeAPIUrl() {
        return 'https://pokeapi.co/api/v2/';
    }
    
    /**
     * Activar o desactivar el mode de depuració
     * @var bool
     */
    public static $debug = true;
    
    /**
     * Directori de logs
     * @var string
     */
    public static $logDir = '../../logs/combat/';
}