<?php
namespace Poke_Combat_API\Repositoris;

use Poke_Combat_API\Config\Configuracio;
use Poke_Combat_API\Config\Constants;
use Poke_Combat_API\Utils\LogUtil;

/**
 * Repositori per accedir a les dades de Pokémon a la base de dades
 */
class RepositoriPokemon {
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
     * Obtenir dades d'un Pokémon en batalla
     * 
     * @param int $idEstatPokemon ID de l'estat del Pokémon en batalla
     * @return array|null Dades del Pokémon o null si no existeix
     */
    public function obtenirPokemonBatalla($idEstatPokemon) {
        $sql = "SELECT ep.*, p.pokeapi_id, p.malnom, p.nivell, p.sprite, 
                       p.id_equip_pokemon
                FROM estat_pokemon_batalla ep
                JOIN equip_pokemon p ON ep.equip_pokemon_id = p.id_equip_pokemon
                WHERE ep.id_estat = ?";
        $stmt = $this->connexio->prepare($sql);
        $stmt->bind_param("i", $idEstatPokemon);
        
        if (!$stmt->execute()) {
            LogUtil::registrarError("Error al obtenir Pokémon en batalla: " . $stmt->error);
            return null;
        }
        
        $resultat = $stmt->get_result();
        if ($resultat->num_rows === 0) {
            return null;
        }
        
        $pokemon = $resultat->fetch_assoc();
        
        // Obtenir estadístiques del Pokémon
        $pokemon['stats'] = $this->obtenirEstadistiquesPokemon($pokemon['id_equip_pokemon']);
        
        // Obtenir tipus del Pokémon des del servei de PokeAPI
        $pokemon['tipus'] = $this->obtenirTipusPokemon($pokemon['pokeapi_id']);
        
        return $pokemon;
    }
    
    /**
     * Obtenir estadístiques d'un Pokémon
     * 
     * @param int $idEquipPokemon ID del Pokémon en l'equip
     * @return array Estadístiques del Pokémon
     */
    public function obtenirEstadistiquesPokemon($idEquipPokemon) {
        // En una versió més avançada, podríem calcular les estadístiques basades en formules del joc real
        // Per ara, utilitzarem valors simplificats basats en el nivell
        
        $sql = "SELECT nivell FROM equip_pokemon WHERE id_equip_pokemon = ?";
        $stmt = $this->connexio->prepare($sql);
        $stmt->bind_param("i", $idEquipPokemon);
        
        if (!$stmt->execute()) {
            LogUtil::registrarError("Error al obtenir nivell del Pokémon: " . $stmt->error);
            return [
                'ps' => 100,
                'atac' => 50,
                'defensa' => 50,
                'atac_especial' => 50,
                'defensa_especial' => 50,
                'velocitat' => 50
            ];
        }
        
        $resultat = $stmt->get_result();
        if ($resultat->num_rows === 0) {
            return [
                'ps' => 100,
                'atac' => 50,
                'defensa' => 50,
                'atac_especial' => 50,
                'defensa_especial' => 50,
                'velocitat' => 50
            ];
        }
        
        $fila = $resultat->fetch_assoc();
        $nivell = $fila['nivell'];
        
        // Valors simplificats basats en el nivell
        return [
            'ps' => 50 + ($nivell * 3),
            'atac' => 30 + ($nivell * 2),
            'defensa' => 30 + ($nivell * 2),
            'atac_especial' => 30 + ($nivell * 2),
            'defensa_especial' => 30 + ($nivell * 2),
            'velocitat' => 30 + ($nivell * 2)
        ];
    }
    
    /**
     * Obtenir tipus d'un Pokémon
     * 
     * @param int $pokeapiId ID del Pokémon a PokeAPI
     * @return array Array de tipus del Pokémon
     */
    public function obtenirTipusPokemon($pokeapiId) {
        // En una implementació real, utilitzaríem el servei de PokeAPI o una caché local
        // Per ara, retornarem tipus per defecte
        
        // Tipus per defecte basats en el ID (simplificat per aquesta fase)
        $tipusPerDefecte = [
            'normal', 'foc', 'aigua', 'planta', 'elèctric', 'gel', 
            'lluita', 'verí', 'terra', 'vol', 'psíquic', 'insecte', 
            'roca', 'fantasma', 'drac', 'fosc', 'acer', 'fada'
        ];
        
        // Per simplificar, assignem el tipus basat en el mòdul del ID
        $tipusPrincipal = $tipusPerDefecte[$pokeapiId % count($tipusPerDefecte)];
        
        // Alguns Pokémon tindran dos tipus
        if ($pokeapiId % 3 == 0) {
            $tipusSecundari = $tipusPerDefecte[($pokeapiId + 1) % count($tipusPerDefecte)];
            return [$tipusPrincipal, $tipusSecundari];
        }
        
        return [$tipusPrincipal];
    }
    
    /**
     * Obtenir tots els Pokémon d'un equip
     * 
     * @param int $idEquip ID de l'equip
     * @return array Array de Pokémon de l'equip
     */
    public function obtenirPokemonsEquip($idEquip) {
        $sql = "SELECT * FROM equip_pokemon WHERE equip_id = ? ORDER BY posicio";
        $stmt = $this->connexio->prepare($sql);
        $stmt->bind_param("i", $idEquip);
        
        if (!$stmt->execute()) {
            LogUtil::registrarError("Error al obtenir Pokémon de l'equip: " . $stmt->error);
            return [];
        }
        
        $resultat = $stmt->get_result();
        $pokemons = [];
        
        while ($pokemon = $resultat->fetch_assoc()) {
            $pokemons[] = $pokemon;
        }
        
        return $pokemons;
    }
    
    /**
     * Obtenir l'estat d'un Pokémon en batalla
     * 
     * @param int $idBatalla ID de la batalla
     * @param int $usuariId ID de l'usuari
     * @param int $equipPokemonId ID del Pokémon en l'equip
     * @return array|null Estat del Pokémon o null si no existeix
     */
    public function obtenirEstatPokemonBatalla($idBatalla, $usuariId, $equipPokemonId) {
        $sql = "SELECT * FROM estat_pokemon_batalla 
                WHERE batalla_id = ? AND usuari_id = ? AND equip_pokemon_id = ?";
        $stmt = $this->connexio->prepare($sql);
        $stmt->bind_param("iii", $idBatalla, $usuariId, $equipPokemonId);
        
        if (!$stmt->execute()) {
            LogUtil::registrarError("Error al obtenir estat del Pokémon: " . $stmt->error);
            return null;
        }
        
        $resultat = $stmt->get_result();
        if ($resultat->num_rows === 0) {
            return null;
        }
        
        return $resultat->fetch_assoc();
    }
    
    /**
     * Actualitzar els PS d'un Pokémon en batalla
     * 
     * @param int $idEstatPokemon ID de l'estat del Pokémon en batalla
     * @param int $nouPS Nous punts de salut
     * @return bool True si s'ha actualitzat correctament
     */
    public function actualitzarPS($idEstatPokemon, $nouPS) {
        $sql = "UPDATE estat_pokemon_batalla 
                SET ps_actuals = ?, 
                    estat_vital = ? 
                WHERE id_estat = ?";
        
        $estatVital = ($nouPS > 0) ? Constants::ESTAT_VITAL_ACTIU : Constants::ESTAT_VITAL_DEBILITAT;
        
        $stmt = $this->connexio->prepare($sql);
        $stmt->bind_param("isi", $nouPS, $estatVital, $idEstatPokemon);
        
        if (!$stmt->execute()) {
            LogUtil::registrarError("Error al actualitzar PS: " . $stmt->error);
            return false;
        }
        
        return $stmt->affected_rows > 0;
    }
}