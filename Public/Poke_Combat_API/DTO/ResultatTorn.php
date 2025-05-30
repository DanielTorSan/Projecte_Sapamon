<?php
namespace Poke_Combat_API\DTO;

/**
 * Classe per representar el resultat complet d'un torn de combat
 */
class ResultatTorn {
    /**
     * ID de la batalla
     * @var int
     */
    private $idBatalla;
    
    /**
     * Número del torn actual
     * @var int
     */
    private $tornActual;
    
    /**
     * Resultat de l'atac del jugador 1
     * @var ResultatAtac
     */
    private $resultatAtacJugador1;
    
    /**
     * Resultat de l'atac del jugador 2
     * @var ResultatAtac
     */
    private $resultatAtacJugador2;
    
    /**
     * Indica si la batalla ha finalitzat després d'aquest torn
     * @var bool
     */
    private $batallaFinalitzada;
    
    /**
     * ID del guanyador si la batalla ha finalitzat
     * @var int
     */
    private $guanyadorId;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->idBatalla = 0;
        $this->tornActual = 0;
        $this->batallaFinalitzada = false;
        $this->guanyadorId = 0;
    }
    
    /**
     * Obtenir l'ID de la batalla
     * @return int
     */
    public function getIdBatalla() {
        return $this->idBatalla;
    }
    
    /**
     * Establir l'ID de la batalla
     * @param int $idBatalla
     * @return ResultatTorn
     */
    public function setIdBatalla($idBatalla) {
        $this->idBatalla = $idBatalla;
        return $this;
    }
    
    /**
     * Obtenir el torn actual
     * @return int
     */
    public function getTornActual() {
        return $this->tornActual;
    }
    
    /**
     * Establir el torn actual
     * @param int $tornActual
     * @return ResultatTorn
     */
    public function setTornActual($tornActual) {
        $this->tornActual = $tornActual;
        return $this;
    }
    
    /**
     * Obtenir el resultat de l'atac del jugador 1
     * @return ResultatAtac
     */
    public function getResultatAtacJugador1() {
        return $this->resultatAtacJugador1;
    }
    
    /**
     * Establir el resultat de l'atac del jugador 1
     * @param ResultatAtac $resultatAtacJugador1
     * @return ResultatTorn
     */
    public function setResultatAtacJugador1($resultatAtacJugador1) {
        $this->resultatAtacJugador1 = $resultatAtacJugador1;
        return $this;
    }
    
    /**
     * Obtenir el resultat de l'atac del jugador 2
     * @return ResultatAtac
     */
    public function getResultatAtacJugador2() {
        return $this->resultatAtacJugador2;
    }
    
    /**
     * Establir el resultat de l'atac del jugador 2
     * @param ResultatAtac $resultatAtacJugador2
     * @return ResultatTorn
     */
    public function setResultatAtacJugador2($resultatAtacJugador2) {
        $this->resultatAtacJugador2 = $resultatAtacJugador2;
        return $this;
    }
    
    /**
     * Comprovar si la batalla ha finalitzat
     * @return bool
     */
    public function isBatallaFinalitzada() {
        return $this->batallaFinalitzada;
    }
    
    /**
     * Establir si la batalla ha finalitzat
     * @param bool $batallaFinalitzada
     * @return ResultatTorn
     */
    public function setBatallaFinalitzada($batallaFinalitzada) {
        $this->batallaFinalitzada = $batallaFinalitzada;
        return $this;
    }
    
    /**
     * Obtenir l'ID del guanyador
     * @return int
     */
    public function getGuanyadorId() {
        return $this->guanyadorId;
    }
    
    /**
     * Establir l'ID del guanyador
     * @param int $guanyadorId
     * @return ResultatTorn
     */
    public function setGuanyadorId($guanyadorId) {
        $this->guanyadorId = $guanyadorId;
        return $this;
    }
    
    /**
     * Convertir a array
     * @return array
     */
    public function toArray() {
        $resultat = [
            'id_batalla' => $this->idBatalla,
            'torn_actual' => $this->tornActual,
            'batalla_finalitzada' => $this->batallaFinalitzada,
            'accions' => []
        ];
        
        if ($this->resultatAtacJugador1) {
            $resultat['accions']['jugador1'] = $this->resultatAtacJugador1->toArray();
        }
        
        if ($this->resultatAtacJugador2) {
            $resultat['accions']['jugador2'] = $this->resultatAtacJugador2->toArray();
        }
        
        if ($this->batallaFinalitzada) {
            $resultat['guanyador_id'] = $this->guanyadorId;
        }
        
        return $resultat;
    }
}