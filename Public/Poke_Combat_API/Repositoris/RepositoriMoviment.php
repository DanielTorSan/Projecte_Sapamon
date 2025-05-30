<?php
namespace Poke_Combat_API\Repositoris;

use Poke_Combat_API\Config\Configuracio;
use Poke_Combat_API\Utils\LogUtil;

/**
 * Repositori per accedir a les dades de moviments a la base de dades
 */
class RepositoriMoviment {
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
     * Obtenir dades d'un moviment
     * 
     * @param int $idMoviment ID del moviment
     * @return array|null Dades del moviment o null si no existeix
     */
    public function obtenirMoviment($idMoviment) {
        $sql = "SELECT * FROM equips_moviments WHERE id_equip_moviment = ?";
        $stmt = $this->connexio->prepare($sql);
        $stmt->bind_param("i", $idMoviment);
        
        if (!$stmt->execute()) {
            LogUtil::registrarError("Error al obtenir moviment: " . $stmt->error);
            return null;
        }
        
        $resultat = $stmt->get_result();
        if ($resultat->num_rows === 0) {
            return null;
        }
        
        return $resultat->fetch_assoc();
    }
    
    /**
     * Obtenir tots els moviments d'un Pokémon
     * 
     * @param int $idEquipPokemon ID del Pokémon en l'equip
     * @return array Array de moviments del Pokémon
     */
    public function obtenirMovimentsPokemon($idEquipPokemon) {
        $sql = "SELECT * FROM equips_moviments WHERE equip_pokemon_id = ?";
        $stmt = $this->connexio->prepare($sql);
        $stmt->bind_param("i", $idEquipPokemon);
        
        if (!$stmt->execute()) {
            LogUtil::registrarError("Error al obtenir moviments del Pokémon: " . $stmt->error);
            return [];
        }
        
        $resultat = $stmt->get_result();
        $moviments = [];
        
        while ($moviment = $resultat->fetch_assoc()) {
            $moviments[] = $moviment;
        }
        
        return $moviments;
    }
    
    /**
     * Comprovar si un Pokémon té un moviment específic
     * 
     * @param int $idEquipPokemon ID del Pokémon en l'equip
     * @param int $idMoviment ID del moviment
     * @return bool True si el Pokémon té el moviment
     */
    public function pokemonTeMoviment($idEquipPokemon, $idMoviment) {
        $sql = "SELECT COUNT(*) as total FROM equips_moviments 
                WHERE equip_pokemon_id = ? AND id_equip_moviment = ?";
        $stmt = $this->connexio->prepare($sql);
        $stmt->bind_param("ii", $idEquipPokemon, $idMoviment);
        
        if (!$stmt->execute()) {
            LogUtil::registrarError("Error al comprovar si el Pokémon té el moviment: " . $stmt->error);
            return false;
        }
        
        $resultat = $stmt->get_result();
        $fila = $resultat->fetch_assoc();
        
        return ($fila['total'] > 0);
    }
}