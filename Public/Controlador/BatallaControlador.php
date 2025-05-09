<?php
require_once __DIR__ . '/../Model/BatallaModel.php';
require_once __DIR__ . '/../Model/EquipModel.php';
require_once __DIR__ . '/../Model/UsuariModel.php';

/**
 * Controlador para gestionar los desafíos y batallas entre entrenadores
 */
class BatallaControlador {
    /** @var BatallaModel */
    private $batallaModel;
    
    /** @var EquipModel */
    private $equipModel;
    
    /** @var UsuariModel */
    private $usuariModel;
    
    /** @var mysqli */
    private $connexio;
    
    /**
     * Constructor
     * @param mysqli $connexio Conexión a la base de datos
     */
    public function __construct($connexio) {
        $this->connexio = $connexio;
        $this->batallaModel = new BatallaModel($connexio);
        $this->equipModel = new EquipModel($connexio);
        $this->usuariModel = new UsuariModel($connexio);
    }
    
    /**
     * Crear un desafío a otro entrenador
     * 
     * @param int $retadorId ID del usuario retador
     * @param int $retadoId ID del usuario retado
     * @param int $equipoId ID del equipo con el que se desafía
     * @return array Resultado de la operación
     */
    public function crearDesafio($retadorId, $retadoId, $equipoId) {
        // Verificar que el usuario retador no desafíe a sí mismo
        if ($retadorId == $retadoId) {
            return [
                'success' => false,
                'message' => 'No puedes desafiarte a ti mismo'
            ];
        }
        
        // Verificar que el equipo pertenezca al retador
        $equipos = $this->equipModel->getEquiposGuardadosByUsuario($retadorId);
        $equipoValido = false;
        foreach ($equipos as $equipo) {
            if ($equipo['id_equip'] == $equipoId) {
                $equipoValido = true;
                break;
            }
        }
        
        if (!$equipoValido) {
            return [
                'success' => false,
                'message' => 'El equipo seleccionado no te pertenece'
            ];
        }
        
        // Crear el desafío
        return $this->batallaModel->crearDesafio($retadorId, $retadoId, $equipoId);
    }
    
    /**
     * Aceptar un desafío
     * 
     * @param int $batallaId ID de la batalla
     * @param int $usuarioId ID del usuario que acepta
     * @param int $equipoId ID del equipo con el que acepta
     * @return array Resultado de la operación
     */
    public function aceptarDesafio($batallaId, $usuarioId, $equipoId) {
        // Verificar que el equipo pertenezca al usuario
        $equipos = $this->equipModel->getEquiposGuardadosByUsuario($usuarioId);
        $equipoValido = false;
        foreach ($equipos as $equipo) {
            if ($equipo['id_equip'] == $equipoId) {
                $equipoValido = true;
                break;
            }
        }
        
        if (!$equipoValido) {
            return [
                'success' => false,
                'message' => 'El equipo seleccionado no te pertenece'
            ];
        }
        
        // Aceptar el desafío
        return $this->batallaModel->aceptarDesafio($batallaId, $usuarioId, $equipoId);
    }
    
    /**
     * Rechazar un desafío
     * 
     * @param int $batallaId ID de la batalla
     * @param int $usuarioId ID del usuario que rechaza
     * @return array Resultado de la operación
     */
    public function rechazarDesafio($batallaId, $usuarioId) {
        return $this->batallaModel->rechazarDesafio($batallaId, $usuarioId);
    }
    
    /**
     * Cancelar un desafío que ha enviado un usuario
     * 
     * @param int $batallaId ID de la batalla
     * @param int $usuarioId ID del usuario que cancela
     * @return array Resultado de la operación
     */
    public function cancelarDesafio($batallaId, $usuarioId) {
        return $this->batallaModel->cancelarDesafio($batallaId, $usuarioId);
    }
    
    /**
     * Obtener los desafíos pendientes de un usuario
     * 
     * @param int $usuarioId ID del usuario
     * @return array Desafíos pendientes
     */
    public function getDesafiosPendientes($usuarioId) {
        return $this->batallaModel->getDesafiosPendientes($usuarioId);
    }
    
    /**
     * Obtener el historial de batallas de un usuario
     * 
     * @param int $usuarioId ID del usuario
     * @param int $limit Límite de resultados
     * @param int $offset Offset para paginación
     * @return array Historial de batallas
     */
    public function getHistorialBatallas($usuarioId, $limit = 10, $offset = 0) {
        return $this->batallaModel->getHistorialBatallas($usuarioId, $limit, $offset);
    }
    
    /**
     * Obtener el equipo principal de un usuario para pre-seleccionarlo en las batallas
     *
     * @param int $usuarioId ID del usuario
     * @return array|null Equipo principal o null si no tiene
     */
    public function getEquipoPrincipal($usuarioId) {
        $equipId = $this->usuariModel->getEquipPrincipal($usuarioId);
        if (!$equipId) {
            return null;
        }
        
        return $this->equipModel->getEquipo($equipId);
    }
    
    /**
     * Obtener todos los equipos guardados de un usuario para selección
     *
     * @param int $usuarioId ID del usuario
     * @return array Lista de equipos
     */
    public function getEquiposDisponibles($usuarioId) {
        return $this->equipModel->getEquiposGuardadosByUsuario($usuarioId);
    }
    
    /**
     * Obtener los desafíos pendientes de un usuario
     * 
     * @param int $usuarioId ID del usuario
     * @return array Array con los desafíos pendientes (enviados y recibidos)
     */
    public function getDesafiamentosPendientes($usuarioId) {
        // Obtener desafíos pendientes del modelo
        $desafios = $this->batallaModel->getDesafiosPendientesByUsuario($usuarioId);
        
        // Enriquecer los datos de los desafíos con información adicional
        foreach ($desafios as $key => $desafio) {
            // Determinar tipo de desafío (enviado o recibido)
            $desafios[$key]['tipo'] = ($desafio['retador_id'] == $usuarioId) ? 'enviado' : 'recibido';
            
            // Obtener información del retador
            $retador = $this->usuariModel->getUserById($desafio['retador_id']);
            if ($retador) {
                $desafios[$key]['retador_nombre'] = $retador['username'];
                $desafios[$key]['retador_avatar'] = $retador['avatar_url'] ?? 'Youngster.png';
            }
            
            // Obtener información del retado
            $retado = $this->usuariModel->getUserById($desafio['retado_id']);
            if ($retado) {
                $desafios[$key]['retado_nombre'] = $retado['username'];
                $desafios[$key]['retado_avatar'] = $retado['avatar_url'] ?? 'Youngster.png';
            }
            
            // Obtener información del equipo del retador
            $equipoRetador = $this->equipModel->getEquipById($desafio['equip_retador_id']);
            if ($equipoRetador) {
                $desafios[$key]['equipo_retador_nombre'] = $equipoRetador['nom_equip'];
            }
        }
        
        return $desafios;
    }
    
    /**
     * Obtener información detallada de una batalla específica
     * 
     * @param int $batallaId ID de la batalla/invitación
     * @return array|null Información de la batalla o null si no existe
     */
    public function obtenerInfoBatalla($batallaId) {
        // Obtener la información básica de la invitación/batalla
        $sql = "SELECT i.*, 
                ue.nom_usuari AS retador_nombre, ue.avatar AS retador_avatar,
                ur.nom_usuari AS retado_nombre, ur.avatar AS retado_avatar,
                e.nom_equip AS equipo_retador_nombre,
                er.nom_equip AS equipo_retado_nombre
                FROM invitacions_batalla i
                INNER JOIN usuaris ue ON i.emissor_id = ue.id_usuari
                INNER JOIN usuaris ur ON i.receptor_id = ur.id_usuari
                LEFT JOIN equips e ON i.equip_emissor_id = e.id_equip
                LEFT JOIN equips er ON i.equip_receptor_id = er.id_equip
                WHERE i.id_invitacio = ?";
        
        $stmt = $this->connexio->prepare($sql);
        $stmt->bind_param("i", $batallaId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return null;
        }
        
        $batalla = $result->fetch_assoc();
        
        // Formatear para mantener coherencia con el resto de la aplicación
        $batalla['id_batalla'] = $batalla['id_invitacio'];
        $batalla['retador_id'] = $batalla['emissor_id'];
        $batalla['retado_id'] = $batalla['receptor_id'];
        $batalla['equipo_retador_id'] = $batalla['equip_emissor_id'];
        $batalla['equipo_retado_id'] = $batalla['equip_receptor_id'];
        $batalla['estado'] = $batalla['estat'];
        $batalla['fecha_creacion'] = $batalla['data_enviament'];
        
        // Crear la estructura de retorno esperada por la vista
        $infoBatalla = [
            'batalla' => $batalla,
            'equip_retador' => null,
            'equip_retat' => null
        ];
        
        // Obtener detalles de los equipos si la batalla ya fue aceptada
        if ($batalla['estat'] === 'acceptada' && $batalla['equip_emissor_id']) {
            $infoBatalla['equip_retador'] = $this->equipModel->getEquipo($batalla['equip_emissor_id']);
        }
        
        if ($batalla['estat'] === 'acceptada' && $batalla['equip_receptor_id']) {
            $infoBatalla['equip_retat'] = $this->equipModel->getEquipo($batalla['equip_receptor_id']);
        }
        
        return $infoBatalla;
    }
    
    /**
     * Obtiene las batallas activas donde participa un usuario
     * 
     * @param int $usuarioId ID del usuario
     * @return array Lista de batallas activas
     */
    public function obtenerBatallasActivas($usuarioId) {
        return $this->batallaModel->obtenerBatallasActivas($usuarioId);
    }
}
?>