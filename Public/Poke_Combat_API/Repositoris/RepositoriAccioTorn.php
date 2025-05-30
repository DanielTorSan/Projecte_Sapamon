<?php
namespace Poke_Combat_API\Repositoris;

use Poke_Combat_API\Config\Configuracio;
use Poke_Combat_API\Utils\LogUtil;

/**
 * Repositori per accedir a les dades d'accions en un torn a la base de dades
 */
class RepositoriAccioTorn {
    /**
     * Connexió a la base de dades
     * @var mysqli
     */
    private $connexio;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->connexio = Configuracio::getConnexio();
    }
    
    /**
     * Registrar una acció en un torn
     * 
     * @param int $idBatalla ID de la batalla
     * @param int $usuariId ID de l'usuari
     * @param int $torn Número de torn
     * @param string $tipusAccio Tipus d'acció (moviment, canvi_pokemon, rendicio)
     * @param int $movimentId ID del moviment (si aplica)
     * @param int $pokemonId ID del Pokémon (si aplica)
     * @return bool True si s'ha registrat correctament
     */
    public function registrarAccio($idBatalla, $usuariId, $torn, $tipusAccio, $movimentId = null, $pokemonId = null) {
        $sql = "INSERT INTO accions_torn 
                (batalla_id, usuari_id, torn, tipus_accio, moviment_id, pokemon_id, timestamp) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $this->connexio->prepare($sql);
        $stmt->bind_param("iiisii", $idBatalla, $usuariId, $torn, $tipusAccio, $movimentId, $pokemonId);
        
        if (!$stmt->execute()) {
            LogUtil::registrarError("Error al registrar acció: " . $stmt->error);
            return false;
        }
        
        return $stmt->affected_rows > 0;
    }
    
    /**
     * Obtenir les accions d'un torn específic
     * 
     * @param int $idBatalla ID de la batalla
     * @param int $torn Número de torn
     * @return array Array d'accions del torn
     */
    public function obtenirAccionsTorn($idBatalla, $torn) {
        $sql = "SELECT * FROM accions_torn 
                WHERE batalla_id = ? AND torn = ? 
                ORDER BY timestamp ASC";
        $stmt = $this->connexio->prepare($sql);
        $stmt->bind_param("ii", $idBatalla, $torn);
        
        if (!$stmt->execute()) {
            LogUtil::registrarError("Error al obtenir accions del torn: " . $stmt->error);
            return [];
        }
        
        $resultat = $stmt->get_result();
        $accions = [];
        
        while ($accio = $resultat->fetch_assoc()) {
            $accions[] = $accio;
        }
        
        return $accions;
    }
    
    /**
     * Obtenir l'última acció d'un usuari en una batalla
     * 
     * @param int $idBatalla ID de la batalla
     * @param int $usuariId ID de l'usuari
     * @return array|null Última acció o null si no existeix
     */
    public function obtenirUltimaAccioUsuari($idBatalla, $usuariId) {
        $sql = "SELECT * FROM accions_torn 
                WHERE batalla_id = ? AND usuari_id = ? 
                ORDER BY torn DESC, timestamp DESC 
                LIMIT 1";
        $stmt = $this->connexio->prepare($sql);
        $stmt->bind_param("ii", $idBatalla, $usuariId);
        
        if (!$stmt->execute()) {
            LogUtil::registrarError("Error al obtenir última acció: " . $stmt->error);
            return null;
        }
        
        $resultat = $stmt->get_result();
        if ($resultat->num_rows === 0) {
            return null;
        }
        
        return $resultat->fetch_assoc();
    }
    
    /**
     * Verificar si un usuari ja ha realitzat una acció en el torn actual
     * 
     * @param int $idBatalla ID de la batalla
     * @param int $usuariId ID de l'usuari
     * @param int $torn Número de torn
     * @return bool True si l'usuari ja ha realitzat una acció
     */
    public function usuariHaRealitzatAccio($idBatalla, $usuariId, $torn) {
        $sql = "SELECT COUNT(*) as total FROM accions_torn 
                WHERE batalla_id = ? AND usuari_id = ? AND torn = ?";
        $stmt = $this->connexio->prepare($sql);
        $stmt->bind_param("iii", $idBatalla, $usuariId, $torn);
        
        if (!$stmt->execute()) {
            LogUtil::registrarError("Error al verificar si l'usuari ha realitzat acció: " . $stmt->error);
            return false;
        }
        
        $resultat = $stmt->get_result();
        $fila = $resultat->fetch_assoc();
        
        return ($fila['total'] > 0);
    }
}