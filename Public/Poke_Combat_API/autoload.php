<?php
/**
 * Autoloader per a carregar classes automàticament
 * 
 * Aquest fitxer permet carregar classes de forma automàtica segons
 * la seva ubicació en l'estructura de carpetes 'Poke_Combat_API'.
 */

spl_autoload_register(function ($class) {
    // Comprovar si la classe pertany al namespace 'Poke_Combat_API'
    if (strpos($class, 'Poke_Combat_API\\') !== 0) {
        return;
    }
    
    // Convertir namespace a ruta de fitxer
    $classPath = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    $classPath = str_replace('Poke_Combat_API' . DIRECTORY_SEPARATOR, '', $classPath);
    
    // Construir ruta completa
    $filePath = __DIR__ . DIRECTORY_SEPARATOR . $classPath . '.php';
    
    // Carregar fitxer si existeix
    if (file_exists($filePath)) {
        require_once $filePath;
    }
});

// Fitxers d'utilitat que no segueixen l'estructura de namespaces
require_once __DIR__ . '/Config/Constants.php';