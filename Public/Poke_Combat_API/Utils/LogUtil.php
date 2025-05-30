<?php
namespace Poke_Combat_API\Utils;

use Poke_Combat_API\Config\Configuracio;

/**
 * Classe d'utilitat per a registrar logs i errors
 */
class LogUtil {
    /**
     * Directori on es guardaran els logs
     * @var string
     */
    private static $logDir;
    
    /**
     * Fitxer de log per errors
     * @var string
     */
    private static $errorLogFile = 'combat_api_errors.log';
    
    /**
     * Fitxer de log per accions
     * @var string
     */
    private static $actionLogFile = 'combat_api.log';
    
    /**
     * Inicialitza les rutes dels fitxers de log
     */
    private static function init() {
        if (!isset(self::$logDir)) {
            self::$logDir = Configuracio::$logDir;
            
            // Assegurar que el directori existeix
            if (!file_exists(self::$logDir)) {
                mkdir(self::$logDir, 0755, true);
            }
            
            // Afegir data al fitxer de log d'accions
            self::$actionLogFile = 'combat_api_' . date('Y-m-d') . '.log';
        }
    }
    
    /**
     * Registra un missatge de log general
     * 
     * @param string $missatge Missatge a registrar
     * @param array $context Dades addicionals (opcional)
     */
    public static function registrar($missatge, array $context = []) {
        self::init();
        
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logMessage = "[$timestamp] INFO: $missatge$contextStr" . PHP_EOL;
        
        file_put_contents(
            self::$logDir . self::$actionLogFile, 
            $logMessage, 
            FILE_APPEND
        );
        
        // Si mode debug està activat, mostrar al navegador o consola
        if (Configuracio::$debug) {
            error_log("COMBAT API: $missatge$contextStr");
        }
    }
    
    /**
     * Registra un missatge d'error
     * 
     * @param string $missatge Missatge d'error
     * @param array $context Dades addicionals (opcional)
     */
    public static function registrarError($missatge, array $context = []) {
        self::init();
        
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logMessage = "[$timestamp] ERROR: $missatge$contextStr" . PHP_EOL;
        
        file_put_contents(
            self::$logDir . self::$errorLogFile, 
            $logMessage, 
            FILE_APPEND
        );
        
        // Si mode debug està activat, mostrar al navegador o consola
        if (Configuracio::$debug) {
            error_log("ERROR COMBAT API: $missatge$contextStr");
        }
    }
    
    /**
     * Registra informació de debug
     * 
     * @param string $missatge Missatge de debug
     * @param array $context Dades addicionals (opcional)
     */
    public static function debug($missatge, array $context = []) {
        if (!Configuracio::$debug) {
            return;
        }
        
        self::init();
        
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logMessage = "[$timestamp] DEBUG: $missatge$contextStr" . PHP_EOL;
        
        file_put_contents(
            self::$logDir . self::$actionLogFile, 
            $logMessage, 
            FILE_APPEND
        );
        
        error_log("DEBUG COMBAT API: $missatge$contextStr");
    }
}