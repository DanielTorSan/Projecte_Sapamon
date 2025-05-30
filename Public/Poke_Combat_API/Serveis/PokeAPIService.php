<?php
namespace Poke_Combat_API\Serveis;

use Poke_Combat_API\Config\Configuracio;
use Poke_Combat_API\Utils\LogUtil;

/**
 * Servei per a interactuar amb la PokeAPI
 * Gestiona les peticions i el caching de dades
 */
class PokeAPIService {
    /**
     * URL base de la PokeAPI
     * @var string
     */
    private $apiUrl;
    
    /**
     * Temps en segons per a la caducitat de la caché
     * 1 setmana = 604800 segons
     * @var int
     */
    private $tempsCache = 604800;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->apiUrl = Configuracio::getPokeAPIUrl();
    }
    
    /**
     * Obté les dades d'un Pokémon per ID o nom
     * 
     * @param int|string $idONom ID o nom del Pokémon
     * @return array|null Dades del Pokémon o null si hi ha error
     */
    public function obtenirPokemon($idONom) {
        return $this->fetchAPI('pokemon/' . strtolower($idONom));
    }
    
    /**
     * Obté les dades d'un moviment per ID o nom
     * 
     * @param int|string $idONom ID o nom del moviment
     * @return array|null Dades del moviment o null si hi ha error
     */
    public function obtenirMoviment($idONom) {
        return $this->fetchAPI('move/' . strtolower($idONom));
    }
    
    /**
     * Obté les dades d'un tipus per ID o nom
     * 
     * @param int|string $idONom ID o nom del tipus
     * @return array|null Dades del tipus o null si hi ha error
     */
    public function obtenirTipus($idONom) {
        return $this->fetchAPI('type/' . strtolower($idONom));
    }
    
    /**
     * Obté informació de tipos d'un Pokémon
     * 
     * @param int|string $idONom ID o nom del Pokémon
     * @return array Array amb els tipus del Pokémon
     */
    public function obtenirTipusPokemon($idONom) {
        $pokemon = $this->obtenirPokemon($idONom);
        if (!$pokemon || !isset($pokemon['types'])) {
            return [];
        }
        
        $tipus = [];
        foreach ($pokemon['types'] as $typeInfo) {
            if (isset($typeInfo['type']['name'])) {
                $tipus[] = $this->traducirTipus($typeInfo['type']['name']);
            }
        }
        
        return $tipus;
    }
    
    /**
     * Obté les estadístiques base d'un Pokémon
     * 
     * @param int|string $idONom ID o nom del Pokémon
     * @return array Array amb les estadístiques base
     */
    public function obtenirEstadistiquesPokemon($idONom) {
        $pokemon = $this->obtenirPokemon($idONom);
        if (!$pokemon || !isset($pokemon['stats'])) {
            return $this->getEstadistiquesPerDefecte();
        }
        
        $stats = $this->getEstadistiquesPerDefecte();
        
        foreach ($pokemon['stats'] as $statInfo) {
            if (isset($statInfo['stat']['name']) && isset($statInfo['base_stat'])) {
                $nomStat = $this->traducirStat($statInfo['stat']['name']);
                if (isset($stats[$nomStat])) {
                    $stats[$nomStat] = $statInfo['base_stat'];
                }
            }
        }
        
        return $stats;
    }
    
    /**
     * Consulta la PokeAPI amb caching
     * 
     * @param string $endpoint Endpoint a consultar
     * @return array|null Dades de la resposta o null si hi ha error
     */
    private function fetchAPI($endpoint) {
        $clauCache = 'pokeapi_' . md5($endpoint);
        
        // Intentar obtenir de la caché
        $dadesCache = $this->obtenirCache($clauCache);
        if ($dadesCache !== null) {
            return $dadesCache;
        }
        
        // Si no està en caché, consultar l'API
        $url = $this->apiUrl . $endpoint;
        
        LogUtil::registrar("Consultant PokeAPI: $url");
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $resposta = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch) || $httpCode !== 200) {
            LogUtil::registrarError("Error consultant PokeAPI: " . curl_error($ch) . " ($httpCode)");
            curl_close($ch);
            return null;
        }
        
        curl_close($ch);
        
        $dades = json_decode($resposta, true);
        if (!$dades) {
            LogUtil::registrarError("Error decodificant resposta de PokeAPI: " . json_last_error_msg());
            return null;
        }
        
        // Guardar a la caché
        $this->guardarCache($clauCache, $dades);
        
        return $dades;
    }
    
    /**
     * Obtenir dades de la caché
     * 
     * @param string $clau Clau de caché
     * @return array|null Dades obtingudes o null si no existeix o ha caducat
     */
    private function obtenirCache($clau) {
        $connexio = Configuracio::getConnexio();
        
        $sql = "SELECT valor_cache FROM memoria_cache WHERE clau_cache = ? AND caducitat > NOW()";
        $stmt = $connexio->prepare($sql);
        $stmt->bind_param("s", $clau);
        
        if (!$stmt->execute()) {
            LogUtil::registrarError("Error consultant caché: " . $stmt->error);
            return null;
        }
        
        $resultat = $stmt->get_result();
        if ($resultat->num_rows === 0) {
            return null;
        }
        
        $fila = $resultat->fetch_assoc();
        return json_decode($fila['valor_cache'], true);
    }
    
    /**
     * Guardar dades a la caché
     * 
     * @param string $clau Clau de caché
     * @param array $dades Dades a guardar
     * @return bool True si s'ha guardat correctament
     */
    private function guardarCache($clau, $dades) {
        $connexio = Configuracio::getConnexio();
        
        // Eliminar caché antiga si existeix
        $sql = "DELETE FROM memoria_cache WHERE clau_cache = ?";
        $stmt = $connexio->prepare($sql);
        $stmt->bind_param("s", $clau);
        $stmt->execute();
        
        // Inserir nova caché
        $sql = "INSERT INTO memoria_cache (clau_cache, valor_cache, caducitat) 
                VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND))";
        $stmt = $connexio->prepare($sql);
        $valorJson = json_encode($dades);
        $stmt->bind_param("ssi", $clau, $valorJson, $this->tempsCache);
        
        if (!$stmt->execute()) {
            LogUtil::registrarError("Error guardant caché: " . $stmt->error);
            return false;
        }
        
        return true;
    }
    
    /**
     * Obtenir estadístiques per defecte
     * 
     * @return array Estadístiques per defecte
     */
    private function getEstadistiquesPerDefecte() {
        return [
            'ps' => 50,
            'atac' => 50,
            'defensa' => 50,
            'atac_especial' => 50,
            'defensa_especial' => 50,
            'velocitat' => 50
        ];
    }
    
    /**
     * Tradueix els noms de les estadístiques de l'anglès al català
     * 
     * @param string $nomStatEnglish Nom de l'estadística en anglès
     * @return string Nom de l'estadística en català
     */
    private function traducirStat($nomStatEnglish) {
        $traduccions = [
            'hp' => 'ps',
            'attack' => 'atac',
            'defense' => 'defensa',
            'special-attack' => 'atac_especial',
            'special-defense' => 'defensa_especial',
            'speed' => 'velocitat'
        ];
        
        return $traduccions[$nomStatEnglish] ?? $nomStatEnglish;
    }
    
    /**
     * Tradueix els noms dels tipus de l'anglès al català
     * 
     * @param string $nomTipusEnglish Nom del tipus en anglès
     * @return string Nom del tipus en català
     */
    private function traducirTipus($nomTipusEnglish) {
        $traduccions = [
            'normal' => 'normal',
            'fire' => 'foc',
            'water' => 'aigua',
            'grass' => 'planta',
            'electric' => 'elèctric',
            'ice' => 'gel',
            'fighting' => 'lluita',
            'poison' => 'verí',
            'ground' => 'terra',
            'flying' => 'vol',
            'psychic' => 'psíquic',
            'bug' => 'insecte',
            'rock' => 'roca',
            'ghost' => 'fantasma',
            'dragon' => 'drac',
            'dark' => 'fosc',
            'steel' => 'acer',
            'fairy' => 'fada'
        ];
        
        return $traduccions[$nomTipusEnglish] ?? $nomTipusEnglish;
    }
}