<?php
/**
 * Model per gestionar les batalles Pokémon entre entrenadors
 * @package Model
 */

// Protección contra inclusión múltiple
if (!class_exists('BatallaModel')) {

class BatallaModel {
    /** @var \mysqli Database connection */
    private $connexio;
    
    /**
     * Constructor del model de batalles
     * @param \mysqli $connexio Database connection
     */
    public function __construct($connexio) {
        $this->connexio = $connexio;
    }
    
    /**
     * Obtenir els desafiaments pendents d'un usuari
     * @param int $usuariId ID de l'usuari
     * @return array Desafiaments pendents (rebuts i enviats)
     */
    public function getDesafiosPendientes($usuariId) {
        try {
            // Consulta per obtenir tots els desafiaments pendents
            $query = "
                SELECT b.*, 
                    u1.nom_usuari as retador_nombre, u1.avatar as retador_avatar,
                    u2.nom_usuari as retado_nombre, u2.avatar as retado_avatar,
                    e1.nom_equip as equipo_retador_nombre,
                    e2.nom_equip as equipo_retado_nombre
                FROM invitacions_batalla b
                JOIN usuaris u1 ON u1.id_usuari = b.emissor_id
                JOIN usuaris u2 ON u2.id_usuari = b.receptor_id
                JOIN equips e1 ON e1.id_equip = b.equip_emissor_id
                LEFT JOIN equips e2 ON e2.id_equip = b.equip_receptor_id
                WHERE (b.emissor_id = ? OR b.receptor_id = ?)
                AND b.estat = 'pendent'
            ";
            
            $stmt = $this->connexio->prepare($query);
            $stmt->bind_param("ii", $usuariId, $usuariId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $desafios = [];
            while ($desafio = $result->fetch_assoc()) {
                // Determinar si l'usuari és el retador o el retat
                $tipo = ($desafio['emissor_id'] == $usuariId) ? 'enviado' : 'recibido';
                $desafio['tipo'] = $tipo;
                
                // Renombrar campos para mantener compatibilidad con el código JS existente
                $desafio['id_batalla'] = $desafio['id_invitacio'];
                $desafio['usuari1_id'] = $desafio['emissor_id'];
                $desafio['usuari2_id'] = $desafio['receptor_id'];
                $desafio['equip1_id'] = $desafio['equip_emissor_id'];
                $desafio['equip2_id'] = $desafio['equip_receptor_id'];
                $desafio['fecha_creacion'] = $desafio['data_enviament'];
                
                $desafios[] = $desafio;
            }
            
            return $desafios;
        } catch (Exception $e) {
            error_log("Error al obtenir desafiaments: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Crear un desafiament de batalla
     * @param int $retadorId ID de l'usuari retador
     * @param int $retadoId ID de l'usuari retat
     * @param int $equipoId ID de l'equip del retador
     * @return array Resultat de l'operació
     */
    public function crearDesafio($retadorId, $retadoId, $equipoId) {
        try {
            // Verificar que los usuarios son amigos
            $stmt = $this->connexio->prepare("
                SELECT COUNT(*) as total FROM amistats_usuaris 
                WHERE (usuari_id = ? AND amic_id = ? AND estat = 'acceptat')
                OR (usuari_id = ? AND amic_id = ? AND estat = 'acceptat')
            ");
            $stmt->bind_param("iiii", $retadorId, $retadoId, $retadoId, $retadorId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['total'] == 0) {
                return ['success' => false, 'message' => 'No pots desafiar a algú que no és el teu amic'];
            }
            
            // Verificar que no hay un desafío pendiente entre estos usuarios
            $stmt = $this->connexio->prepare("
                SELECT COUNT(*) as total FROM invitacions_batalla 
                WHERE ((emissor_id = ? AND receptor_id = ?) OR (emissor_id = ? AND receptor_id = ?))
                AND estat = 'pendent'
            ");
            $stmt->bind_param("iiii", $retadorId, $retadoId, $retadoId, $retadorId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['total'] > 0) {
                return ['success' => false, 'message' => 'Ja existeix un desafiament pendent entre vosaltres'];
            }
            
            // Verificar que el equipo pertenece al retador
            $stmt = $this->connexio->prepare("
                SELECT COUNT(*) as total FROM equips 
                WHERE id_equip = ? AND usuari_id = ? AND guardado = 1
            ");
            $stmt->bind_param("ii", $equipoId, $retadorId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['total'] == 0) {
                return ['success' => false, 'message' => 'L\'equip seleccionat no és vàlid'];
            }
            
            // Generar código de sala aleatorio
            $codiSala = $this->generarCodiSala();
            
            // Iniciar transacción para asegurar consistencia
            $this->connexio->begin_transaction();
            
            try {
                // 1. Crear el desafío
                $stmt = $this->connexio->prepare("
                    INSERT INTO invitacions_batalla (emissor_id, receptor_id, equip_emissor_id, estat, codi_sala)
                    VALUES (?, ?, ?, 'pendent', ?)
                ");
                $stmt->bind_param("iiis", $retadorId, $retadoId, $equipoId, $codiSala);
                $stmt->execute();
                
                $invitacioId = $this->connexio->insert_id;
                
                // 2. Generar tokens para la batalla
                $tokenPublic = md5(uniqid(rand(), true));
                $tokenEspectador = md5(uniqid(rand(), true));
                
                // 3. Crear entrada en la tabla de batalles (en estado preparación)
                $stmt = $this->connexio->prepare("
                    INSERT INTO batalles (
                        token_public, 
                        token_espectador, 
                        usuari1_id, 
                        usuari2_id, 
                        equip1_id, 
                        id_invitacio,
                        torn_actual_id,
                        estat,
                        modo_batalla
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'preparacio', 'singles')
                ");
                
                $stmt->bind_param(
                    "ssiiiis", 
                    $tokenPublic, 
                    $tokenEspectador, 
                    $retadorId, 
                    $retadoId, 
                    $equipoId,
                    $invitacioId,
                    $retadorId // El primer turno es para el retador
                );
                $stmt->execute();
                
                // 4. Obtener el ID de la batalla creada
                $nuevaBatallaId = $this->connexio->insert_id;
                
                // Confirmar transacción
                $this->connexio->commit();
                
                return [
                    'success' => true, 
                    'message' => 'Desafiament creat correctament',
                    'batalla_id' => $nuevaBatallaId,
                    'token_public' => $tokenPublic,
                    'url_batalla' => "Batalla_Vista.php?batalla_id=" . $nuevaBatallaId
                ];
            } catch (Exception $e) {
                // Revertir transacción en caso de error
                $this->connexio->rollback();
                throw $e;
            }
        } catch (Exception $e) {
            error_log("Error al crear desafiament: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Genera un codi aleatori per a la sala de batalla
     * @return string Codi de sala
     */
    private function generarCodiSala() {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $code;
    }
    
    /**
     * Acceptar un desafiament de batalla
     * @param int $batallaId ID de la batalla
     * @param int $usuariId ID de l'usuari que accepta
     * @param int $equipId ID de l'equip seleccionat
     * @return array Resultat de l'operació
     */
    public function aceptarDesafio($batallaId, $usuariId, $equipId) {
        try {
            // Verificar que la batalla existe y el usuario es el retado
            $stmt = $this->connexio->prepare("
                SELECT * FROM invitacions_batalla 
                WHERE id_invitacio = ? AND receptor_id = ? AND estat = 'pendent'
            ");
            $stmt->bind_param("ii", $batallaId, $usuariId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 0) {
                return ['success' => false, 'message' => 'No s\'ha trobat la batalla o no ets el destinatari'];
            }
            
            $invitacio = $result->fetch_assoc();
            
            // Verificar que el equipo pertenece al usuario
            $stmt = $this->connexio->prepare("
                SELECT COUNT(*) as total FROM equips 
                WHERE id_equip = ? AND usuari_id = ? AND guardado = 1
            ");
            $stmt->bind_param("ii", $equipId, $usuariId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['total'] == 0) {
                return ['success' => false, 'message' => 'L\'equip seleccionat no és vàlid'];
            }
            
            // Buscar la sala de batalla ya creada
            $stmt = $this->connexio->prepare("
                SELECT * FROM batalles 
                WHERE id_invitacio = ?
            ");
            $stmt->bind_param("i", $batallaId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 0) {
                return ['success' => false, 'message' => 'No s\'ha trobat la sala de batalla'];
            }
            
            $batalla = $result->fetch_assoc();
            $batallaId = $batalla['id_batalla'];
            
            // Iniciar transacción para asegurar consistencia
            $this->connexio->begin_transaction();
            
            try {
                // 1. Actualizar la invitación a batalla
                $stmt = $this->connexio->prepare("
                    UPDATE invitacions_batalla 
                    SET estat = 'acceptada', equip_receptor_id = ?
                    WHERE id_invitacio = ?
                ");
                $stmt->bind_param("ii", $equipId, $invitacio['id_invitacio']);
                $stmt->execute();
                
                // 2. Actualizar la batalla con el equipo del retado
                $stmt = $this->connexio->prepare("
                    UPDATE batalles 
                    SET equip2_id = ?, estat = 'activa'
                    WHERE id_batalla = ?
                ");
                $stmt->bind_param("ii", $equipId, $batallaId);
                $stmt->execute();
                
                // Confirmar transacción
                $this->connexio->commit();
                
                return [
                    'success' => true, 
                    'message' => 'Desafiament acceptat',
                    'batalla_id' => $batallaId,
                    'token_public' => $batalla['token_public'],
                    'url_batalla' => "Combat_Vista.php?id_batalla=" . $batallaId
                ];
            } catch (Exception $e) {
                // Revertir transacción en caso de error
                $this->connexio->rollback();
                throw $e;
            }
        } catch (Exception $e) {
            error_log("Error al acceptar desafiament: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Rebutjar un desafiament de batalla
     * @param int $batallaId ID de la batalla
     * @param int $usuariId ID de l'usuari que rebutja
     * @return array Resultat de l'operació
     */
    public function rechazarDesafio($batallaId, $usuariId) {
        try {
            // Verificar que la batalla existe y el usuario es el retado
            $stmt = $this->connexio->prepare("
                SELECT * FROM invitacions_batalla 
                WHERE id_invitacio = ? AND receptor_id = ? AND estat = 'pendent'
            ");
            $stmt->bind_param("ii", $batallaId, $usuariId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 0) {
                return ['success' => false, 'message' => 'No s\'ha trobat la batalla o no ets el destinatari'];
            }
            
            // Actualizar la batalla
            $stmt = $this->connexio->prepare("
                UPDATE invitacions_batalla 
                SET estat = 'rebutjada'
                WHERE id_invitacio = ?
            ");
            $stmt->bind_param("i", $batallaId);
            $stmt->execute();
            
            return [
                'success' => true, 
                'message' => 'Desafiament rebutjat'
            ];
        } catch (Exception $e) {
            error_log("Error al rebutjar desafiament: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Cancelar un desafiament de batalla
     * @param int $batallaId ID de la batalla
     * @param int $usuariId ID de l'usuari que cancel·la
     * @return array Resultat de l'operació
     */
    public function cancelarDesafio($batallaId, $usuariId) {
        try {
            // Verificar que la batalla existe y el usuario es el retador
            $stmt = $this->connexio->prepare("
                SELECT * FROM invitacions_batalla 
                WHERE id_invitacio = ? AND emissor_id = ? AND estat = 'pendent'
            ");
            $stmt->bind_param("ii", $batallaId, $usuariId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 0) {
                return ['success' => false, 'message' => 'No s\'ha trobat la batalla o no ets el retador'];
            }
            
            // Actualizar la batalla
            $stmt = $this->connexio->prepare("
                DELETE FROM invitacions_batalla 
                WHERE id_invitacio = ?
            ");
            $stmt->bind_param("i", $batallaId);
            $stmt->execute();
            
            return [
                'success' => true, 
                'message' => 'Desafiament cancel·lat'
            ];
        } catch (Exception $e) {
            error_log("Error al cancel·lar desafiament: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Obtenir l'historial de batalles d'un usuari
     * @param int $usuariId ID de l'usuari
     * @param int $limit Límit de registres a obtenir
     * @param int $offset Desplaçament per a paginació
     * @return array Historial de batalles
     */
    public function getHistorialBatallas($usuariId, $limit = 10, $offset = 0) {
        try {
            $query = "
                SELECT b.*, 
                    u1.nom_usuari as retador_nombre, u1.avatar as retador_avatar,
                    u2.nom_usuari as retado_nombre, u2.avatar as retado_avatar,
                    e1.nom_equip as equipo_retador_nombre,
                    e2.nom_equip as equipo_retado_nombre
                FROM invitacions_batalla b
                JOIN usuaris u1 ON u1.id_usuari = b.emissor_id
                JOIN usuaris u2 ON u2.id_usuari = b.receptor_id
                LEFT JOIN equips e1 ON e1.id_equip = b.equip_emissor_id
                LEFT JOIN equips e2 ON e2.id_equip = b.equip_receptor_id
                WHERE (b.emissor_id = ? OR b.receptor_id = ?)
                AND b.estat = 'acceptada'
                ORDER BY b.data_enviament DESC
                LIMIT ? OFFSET ?
            ";
            
            $stmt = $this->connexio->prepare($query);
            $stmt->bind_param("iiii", $usuariId, $usuariId, $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $batallas = [];
            while ($batalla = $result->fetch_assoc()) {
                // Renombrar campos para mantener compatibilidad con el código JS existente
                $batalla['id_batalla'] = $batalla['id_invitacio'];
                $batalla['usuari1_id'] = $batalla['emissor_id'];
                $batalla['usuari2_id'] = $batalla['receptor_id'];
                $batalla['equip1_id'] = $batalla['equip_emissor_id'];
                $batalla['equip2_id'] = $batalla['equip_receptor_id'];
                
                $batallas[] = $batalla;
            }
            
            return $batallas;
        } catch (Exception $e) {
            error_log("Error al obtenir historial de batalles: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Obtenir una batalla pel seu ID
     * @param int $batallaId ID de la batalla
     * @return array|null Dades de la batalla o null si no existeix
     */
    public function obtenerBatallaPorId($batallaId) {
        try {
            $stmt = $this->connexio->prepare("
                SELECT * FROM invitacions_batalla WHERE id_invitacio = ?
            ");
            $stmt->bind_param("i", $batallaId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 0) {
                return null;
            }
            
            $batalla = $result->fetch_assoc();
            
            // Renombrar campos para mantener compatibilidad con el código JS existente
            $batalla['id_batalla'] = $batalla['id_invitacio'];
            $batalla['usuari1_id'] = $batalla['emissor_id'];
            $batalla['usuari2_id'] = $batalla['receptor_id'];
            $batalla['equip1_id'] = $batalla['equip_emissor_id'];
            $batalla['equip2_id'] = $batalla['equip_receptor_id'];
            
            return $batalla;
        } catch (Exception $e) {
            error_log("Error al obtenir batalla per ID: " . $e->getMessage());
            throw $e;
        }
    }
}

} // Fin de la protección contra inclusión múltiple
?>