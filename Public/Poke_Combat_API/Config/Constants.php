<?php
namespace Poke_Combat_API\Config;

/**
 * Constants utilitzades en l'API de combat Pokémon
 */
class Constants {
    /**
     * Versions de l'API
     */
    const API_VERSION = '1.0.0';
    
    /**
     * Estats possibles d'una batalla
     */
    const ESTAT_BATALLA_PENDENT = 'pendent';
    const ESTAT_BATALLA_PREPARACIO = 'preparacio';
    const ESTAT_BATALLA_ACTIVA = 'activa';
    const ESTAT_BATALLA_ACABADA = 'acabada';
    
    /**
     * Tipus d'accions en un torn
     */
    const ACCIO_MOVIMENT = 'moviment';
    const ACCIO_CANVI_POKEMON = 'canvi_pokemon';
    const ACCIO_RENDICIO = 'rendicio';
    
    /**
     * Categories de moviments
     */
    const CATEGORIA_FISIC = 'físic';
    const CATEGORIA_ESPECIAL = 'especial';
    const CATEGORIA_ESTAT = 'estat';
    
    /**
     * Efectivitat dels atacs
     */
    const EFECTIVITAT_SUPER = 'super';     // Molt efectiu
    const EFECTIVITAT_NORMAL = 'normal';   // Efectivitat normal
    const EFECTIVITAT_POC = 'poc';         // Poc efectiu
    const EFECTIVITAT_IMMUNE = 'immune';   // No afecta
    
    /**
     * Estats d'un Pokémon en batalla
     */
    const ESTAT_POKEMON_ACTIU = 'actiu';
    const ESTAT_POKEMON_DEBILITAT = 'debilitat';
    
    /**
     * Estat vital d'un Pokémon
     */
    const ESTAT_VITAL_ACTIU = 'actiu';
    const ESTAT_VITAL_DEBILITAT = 'debilitat';
    
    /**
     * Condicions d'estat possibles
     */
    const CONDICIO_CREMAT = 'cremat';
    const CONDICIO_PARALITZAT = 'paralitzat';
    const CONDICIO_ENVERINAT = 'enverinat';
    const CONDICIO_ADORMIT = 'adormit';
    const CONDICIO_CONFOS = 'confos';
    
    /**
     * Estadístiques dels Pokémon
     */
    const STAT_PS = 'ps';
    const STAT_ATAC = 'atac';
    const STAT_DEFENSA = 'defensa';
    const STAT_ATAC_ESPECIAL = 'atac_especial';
    const STAT_DEFENSA_ESPECIAL = 'defensa_especial';
    const STAT_VELOCITAT = 'velocitat';
    
    /**
     * Nivell màxim i mínim dels Pokémon
     */
    const NIVELL_MINIM = 1;
    const NIVELL_MAXIM = 100;
    
    /**
     * Nombre màxim de Pokémon per equip
     */
    const MAX_POKEMON_EQUIP = 6;
    
    /**
     * Nombre màxim de moviments per Pokémon
     */
    const MAX_MOVIMENTS_POKEMON = 4;
    
    /**
     * Codis d'error
     */
    const ERROR_POKEMON_DEBILITAT = 101;
    const ERROR_MOVIMENT_NO_DISPONIBLE = 102;
    const ERROR_TORN_INVALID = 103;
    const ERROR_BATALLA_NO_TROBADA = 104;
    const ERROR_BATALLA_NO_ACTIVA = 105;
    const ERROR_USUARI_NO_AUTORITZAT = 106;
}