<?php
namespace Poke_Combat_API\DTO;

use Poke_Combat_API\Config\Constants;

/**
 * Classe per representar l'entrada d'una acció en un torn de combat
 */
class EntradaAccio {
    /**
     * ID de l'usuari que realitza l'acció
     * @var int
     */
    private $usuariId;
    
    /**
     * Tipus d'acció (moviment, canvi_pokemon, rendicio)
     * @var string
     */
    private $tipusAccio;
    
    /**
     * ID del moviment seleccionat (si l'acció és un moviment)
     * @var int
     */
    private $movimentId;
    
    /**
     * ID del Pokémon seleccionat (si l'acció és un canvi de Pokémon)
     * @var int
     */
    private $pokemonId;
    
    /**
     * Constructor
     * 
     * @param array $dades Dades de l'acció
     */
    public function __construct(array $dades = []) {
        $this->usuariId = $dades['usuari_id'] ?? 0;
        $this->tipusAccio = $dades['tipus_accio'] ?? Constants::ACCIO_MOVIMENT;
        $this->movimentId = $dades['moviment_id'] ?? 0;
        $this->pokemonId = $dades['pokemon_id'] ?? 0;
    }
    
    /**
     * Obtenir l'ID de l'usuari
     * @return int
     */
    public function getUsuariId() {
        return $this->usuariId;
    }
    
    /**
     * Establir l'ID de l'usuari
     * @param int $usuariId
     * @return EntradaAccio
     */
    public function setUsuariId($usuariId) {
        $this->usuariId = $usuariId;
        return $this;
    }
    
    /**
     * Obtenir el tipus d'acció
     * @return string
     */
    public function getTipusAccio() {
        return $this->tipusAccio;
    }
    
    /**
     * Establir el tipus d'acció
     * @param string $tipusAccio
     * @return EntradaAccio
     */
    public function setTipusAccio($tipusAccio) {
        $this->tipusAccio = $tipusAccio;
        return $this;
    }
    
    /**
     * Obtenir l'ID del moviment
     * @return int
     */
    public function getMovimentId() {
        return $this->movimentId;
    }
    
    /**
     * Establir l'ID del moviment
     * @param int $movimentId
     * @return EntradaAccio
     */
    public function setMovimentId($movimentId) {
        $this->movimentId = $movimentId;
        return $this;
    }
    
    /**
     * Obtenir l'ID del Pokémon
     * @return int
     */
    public function getPokemonId() {
        return $this->pokemonId;
    }
    
    /**
     * Establir l'ID del Pokémon
     * @param int $pokemonId
     * @return EntradaAccio
     */
    public function setPokemonId($pokemonId) {
        $this->pokemonId = $pokemonId;
        return $this;
    }
    
    /**
     * Convertir a array
     * @return array
     */
    public function toArray() {
        return [
            'usuari_id' => $this->usuariId,
            'tipus_accio' => $this->tipusAccio,
            'moviment_id' => $this->movimentId,
            'pokemon_id' => $this->pokemonId
        ];
    }
}