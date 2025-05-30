<?php
namespace Poke_Combat_API\DTO;

use Poke_Combat_API\Config\Constants;

/**
 * Classe per representar el resultat d'un atac en un torn de combat
 */
class ResultatAtac {
    /**
     * Nom del Pokémon atacant
     * @var string
     */
    private $nomPokemonAtacant;
    
    /**
     * Nom del Pokémon defensor
     * @var string
     */
    private $nomPokemonDefensor;
    
    /**
     * Nom del moviment utilitzat
     * @var string
     */
    private $nomMoviment;
    
    /**
     * Tipus del moviment utilitzat
     * @var string
     */
    private $tipusMoviment;
    
    /**
     * Dany total causat
     * @var int
     */
    private $danyTotal;
    
    /**
     * Indica si l'atac ha estat crític
     * @var bool
     */
    private $esCritic;
    
    /**
     * Efectivitat de l'atac (super, normal, poc, immune)
     * @var string
     */
    private $efectivitat;
    
    /**
     * Missatges descriptius de l'atac
     * @var array
     */
    private $missatges = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->nomPokemonAtacant = '';
        $this->nomPokemonDefensor = '';
        $this->nomMoviment = '';
        $this->tipusMoviment = '';
        $this->danyTotal = 0;
        $this->esCritic = false;
        $this->efectivitat = Constants::EFECTIVITAT_NORMAL;
    }
    
    /**
     * Obtenir el nom del Pokémon atacant
     * @return string
     */
    public function getNomPokemonAtacant() {
        return $this->nomPokemonAtacant;
    }
    
    /**
     * Establir el nom del Pokémon atacant
     * @param string $nomPokemonAtacant
     * @return ResultatAtac
     */
    public function setNomPokemonAtacant($nomPokemonAtacant) {
        $this->nomPokemonAtacant = $nomPokemonAtacant;
        return $this;
    }
    
    /**
     * Obtenir el nom del Pokémon defensor
     * @return string
     */
    public function getNomPokemonDefensor() {
        return $this->nomPokemonDefensor;
    }
    
    /**
     * Establir el nom del Pokémon defensor
     * @param string $nomPokemonDefensor
     * @return ResultatAtac
     */
    public function setNomPokemonDefensor($nomPokemonDefensor) {
        $this->nomPokemonDefensor = $nomPokemonDefensor;
        return $this;
    }
    
    /**
     * Obtenir el nom del moviment
     * @return string
     */
    public function getNomMoviment() {
        return $this->nomMoviment;
    }
    
    /**
     * Establir el nom del moviment
     * @param string $nomMoviment
     * @return ResultatAtac
     */
    public function setNomMoviment($nomMoviment) {
        $this->nomMoviment = $nomMoviment;
        return $this;
    }
    
    /**
     * Obtenir el tipus del moviment
     * @return string
     */
    public function getTipusMoviment() {
        return $this->tipusMoviment;
    }
    
    /**
     * Establir el tipus del moviment
     * @param string $tipusMoviment
     * @return ResultatAtac
     */
    public function setTipusMoviment($tipusMoviment) {
        $this->tipusMoviment = $tipusMoviment;
        return $this;
    }
    
    /**
     * Obtenir el dany total
     * @return int
     */
    public function getDanyTotal() {
        return $this->danyTotal;
    }
    
    /**
     * Establir el dany total
     * @param int $danyTotal
     * @return ResultatAtac
     */
    public function setDanyTotal($danyTotal) {
        $this->danyTotal = $danyTotal;
        return $this;
    }
    
    /**
     * Comprovar si l'atac és crític
     * @return bool
     */
    public function esAtacCritic() {
        return $this->esCritic;
    }
    
    /**
     * Establir si l'atac és crític
     * @param bool $esCritic
     * @return ResultatAtac
     */
    public function setEsCritic($esCritic) {
        $this->esCritic = $esCritic;
        return $this;
    }
    
    /**
     * Obtenir l'efectivitat
     * @return string
     */
    public function getEfectivitat() {
        return $this->efectivitat;
    }
    
    /**
     * Establir l'efectivitat
     * @param string $efectivitat
     * @return ResultatAtac
     */
    public function setEfectivitat($efectivitat) {
        $this->efectivitat = $efectivitat;
        return $this;
    }
    
    /**
     * Obtenir els missatges
     * @return array
     */
    public function getMissatges() {
        return $this->missatges;
    }
    
    /**
     * Afegir un missatge
     * @param string $missatge
     * @return ResultatAtac
     */
    public function addMissatge($missatge) {
        $this->missatges[] = $missatge;
        return $this;
    }
    
    /**
     * Convertir a array
     * @return array
     */
    public function toArray() {
        return [
            'atacant' => $this->nomPokemonAtacant,
            'defensor' => $this->nomPokemonDefensor,
            'moviment' => $this->nomMoviment,
            'tipus' => $this->tipusMoviment,
            'dany' => $this->danyTotal,
            'critic' => $this->esCritic,
            'efectivitat' => $this->efectivitat,
            'missatges' => $this->missatges
        ];
    }
}