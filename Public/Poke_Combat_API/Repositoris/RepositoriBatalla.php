<?php
namespace Poke_Combat_API\Repositoris;

use Poke_Combat_API\Config\Configuracio;
use Poke_Combat_API\Config\Constants;
use Poke_Combat_API\Utils\LogUtil;

/**
 * Repositori per accedir a les dades de batalles a la base de dades
 */
class RepositoriBatalla {
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
     * Obtenir les dades d'una batalla per ID
     * 
     * @param int $idBatalla ID de la batalla
     * @return array|null Dades de la batalla o null si no existeix
     */
    public function obtenirBatalla($idBatalla) {
        $sql = "SELECT * FROM batalles WHERE id_batalla = ?";
        $stmt = $this->connexio->prepare($sql);
        $stmt->bind_param("i", $idBatalla);
        
        if (!$stmt->execute()) {
            LogUtil::registrarError("Error al obtenir batalla: " . $stmt->error);
            return null;
        }
        
        $resultat = $stmt->get_result();
        if ($resultat->num_rows === 0) {
            return null;
        }
        
        return $resultat->fetch_assoc();
    }
    
    /**
     * Actualitzar l'estat d'una batalla
     * 
     * @param int $idBatalla ID de la batalla
     * @param string $nouEstat Nou estat de la batalla
     * @return bool True si s'ha actualitzat correctament
     */
    public function actualitzarEstatBatalla($idBatalla, $nouEstat) {
        $sql = "UPDATE batalles SET estat = ? WHERE id_batalla = ?";
        $stmt = $this->connexio->prepare($sql);
        $stmt->bind_param("si", $nouEstat, $idBatalla);
        
        if (!$stmt->execute()) {
            LogUtil::registrarError("Error al actualitzar estat de la batalla: " . $stmt->error);
            return false;
        }
        
        return $stmt->affected_rows > 0;
    }
    
    /**
     * Actualitzar els Pokémon actius d'una batalla
     * 
     * @param int $idBatalla ID de la batalla
     * @param int $pokemonActiu1 ID del Pokémon actiu del jugador 1
     * @param int $pokemonActiu2 ID del Pokémon actiu del jugador 2
     * @return bool True si s'ha actualitzat correctament
     */
    public function actualitzarPokemonActius($idBatalla, $pokemonActiu1, $pokemonActiu2) {
        $sql = "UPDATE batalles SET pokemon_actiu_1 = ?, pokemon_actiu_2 = ? WHERE id_batalla = ?";
        $stmt = $this->connexio->prepare($sql);
        $stmt->bind_param("iii", $pokemonActiu1, $pokemonActiu2, $idBatalla);
        
        if (!$stmt->execute()) {
            LogUtil::registrarError("Error al actualitzar Pokémon actius: " . $stmt->error);
            return false;
        }
        
        return $stmt->affected_rows > 0;
    }
    
    /**
     * Obtenir el torn actual d'una batalla
     * 
     * @param int $idBatalla ID de la batalla
     * @return int Número del torn actual
     */
    public function obtenirTornActual($idBatalla) {
        $sql = "SELECT torn_actual FROM batalles WHERE id_batalla = ?";
        $stmt = $this->connexio->prepare($sql);
        $stmt->bind_param("i", $idBatalla);
        
        if (!$stmt->execute()) {
            LogUtil::registrarError("Error al obtenir torn actual: " . $stmt->error);
            return 1; // Valor per defecte
        }
        
        $resultat = $stmt->get_result();
        if ($resultat->num_rows === 0) {
            return 1; // Valor per defecte
        }
        
        $fila = $resultat->fetch_assoc();
        return $fila['torn_actual'] ?? 1;
    }
    
    /**
     * Incrementar el torn actual d'una batalla
     * 
     * @param int $idBatalla ID de la batalla
     * @return bool True si s'ha actualitzat correctament
     */
    public function seguentTorn($idBatalla) {
        $sql = "UPDATE batalles SET torn_actual = torn_actual + 1 WHERE id_batalla = ?";
        $stmt = $this->connexio->prepare($sql);
        $stmt->bind_param("i", $idBatalla);
        
        if (!$stmt->execute()) {
            LogUtil::registrarError("Error al incrementar torn: " . $stmt->error);
            return false;
        }
        
        return $stmt->affected_rows > 0;
    }
    
    /**
     * Finalitzar una batalla amb guanyador
     * 
     * @param int $idBatalla ID de la batalla
     * @param int $guanyadorId ID de l'usuari guanyador
     * @return bool True si s'ha finalitzat correctament
     */
    public function finalitzarBatalla($idBatalla, $guanyadorId) {
        $sql = "UPDATE batalles SET estat = ?, guanyador_id = ?, acabada_el = NOW() 
                WHERE id_batalla = ?";
        $stmt = $this->connexio->prepare($sql);
        $estat = Constants::ESTAT_BATALLA_ACABADA;
        $stmt->bind_param("sii", $estat, $guanyadorId, $idBatalla);
        
        if (!$stmt->execute()) {
            LogUtil::registrarError("Error al finalitzar batalla: " . $stmt->error);
            return false;
        }
        
        // Actualitzar estadístiques dels usuaris
        $this->actualitzarEstadistiquesUsuaris($idBatalla, $guanyadorId);
        
        return $stmt->affected_rows > 0;
    }
    
    /**
     * Actualitzar les estadístiques dels usuaris després d'una batalla
     * 
     * @param int $idBatalla ID de la batalla
     * @param int $guanyadorId ID de l'usuari guanyador
     */
    private function actualitzarEstadistiquesUsuaris($idBatalla, $guanyadorId) {
        $batalla = $this->obtenirBatalla($idBatalla);
        if (!$batalla) {
            return;
        }
        
        $usuari1Id = $batalla['usuari1_id'];
        $usuari2Id = $batalla['usuari2_id'];
        
        // Actualitzar estadístiques del guanyador
        $this->actualitzarEstadistiquesVictoria($guanyadorId);
        
        // Actualitzar estadístiques del perdedor
        $perdedorId = ($guanyadorId == $usuari1Id) ? $usuari2Id : $usuari1Id;
        $this->actualitzarEstadistiquesDerrota($perdedorId);
        
        // També podríem actualitzar el Pokémon més utilitzat, però deixem això per a una versió futura
    }
    
    /**
     * Actualitzar estadístiques d'un usuari quan guanya
     * 
     * @param int $usuariId ID de l'usuari
     */
    private function actualitzarEstadistiquesVictoria($usuariId) {
        $sql = "UPDATE historial_usuaris 
                SET batalles_totals = batalles_totals + 1, 
                    batalles_guanyades = batalles_guanyades + 1,
                    ultima_victoria = NOW()
                WHERE usuari_id = ?";
        $stmt = $this->connexio->prepare($sql);
        $stmt->bind_param("i", $usuariId);
        
        if (!$stmt->execute()) {
            LogUtil::registrarError("Error al actualitzar estadístiques de victòria: " . $stmt->error);
        }
        
        // Si no existeix l'historial, el creem
        if ($stmt->affected_rows == 0) {
            $this->crearHistorialUsuari($usuariId, true);
        }
    }
    
    /**
     * Actualitzar estadístiques d'un usuari quan perd
     * 
     * @param int $usuariId ID de l'usuari
     */
    private function actualitzarEstadistiquesDerrota($usuariId) {
        $sql = "UPDATE historial_usuaris 
                SET batalles_totals = batalles_totals + 1, 
                    batalles_perdudes = batalles_perdudes + 1
                WHERE usuari_id = ?";
        $stmt = $this->connexio->prepare($sql);
        $stmt->bind_param("i", $usuariId);
        
        if (!$stmt->execute()) {
            LogUtil::registrarError("Error al actualitzar estadístiques de derrota: " . $stmt->error);
        }
        
        // Si no existeix l'historial, el creem
        if ($stmt->affected_rows == 0) {
            $this->crearHistorialUsuari($usuariId, false);
        }
    }
    
    /**
     * Crear un historial d'usuari si no existeix
     * 
     * @param int $usuariId ID de l'usuari
     * @param bool $esVictoria Indica si és una victòria
     */
    private function crearHistorialUsuari($usuariId, $esVictoria) {
        $batallesGuanyades = $esVictoria ? 1 : 0;
        $batallesPerdudes = $esVictoria ? 0 : 1;
        $ultimaVictoria = $esVictoria ? 'NOW()' : 'NULL';
        
        $sql = "INSERT INTO historial_usuaris 
                (usuari_id, batalles_totals, batalles_guanyades, batalles_perdudes, ultima_victoria) 
                VALUES (?, 1, ?, ?, " . $ultimaVictoria . ")";
        $stmt = $this->connexio->prepare($sql);
        $stmt->bind_param("iii", $usuariId, $batallesGuanyades, $batallesPerdudes);
        
        if (!$stmt->execute()) {
            LogUtil::registrarError("Error al crear historial d'usuari: " . $stmt->error);
        }
    }
}