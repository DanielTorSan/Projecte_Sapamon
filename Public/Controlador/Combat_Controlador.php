<?php
/**
 * Combat_Controlador
 * 
 * Controlador para gestionar la lógica de combates Pokémon
 */
class Combat_Controlador {
    private $connexio;
    private $combatModel;
    
    /**
     * Constructor
     * 
     * @param mysqli $connexio Conexión a la base de datos
     */
    public function __construct($connexio) {
        $this->connexio = $connexio;
        require_once __DIR__ . '/../Model/Combat_Model.php';
        $this->combatModel = new Combat_Model($connexio);
    }
    
    /**
     * Verifica si el usuario está autenticado
     * 
     * @return bool True si el usuario está autenticado, false en caso contrario
     */
    public function verificarAutenticacion() {
        return isset($_SESSION['usuari_id']);
    }
    
    /**
     * Obtiene el ID de la batalla desde la URL
     * 
     * @return mixed ID de la batalla o null si no existe
     */
    public function obtenerIdBatalla() {
        return isset($_GET['id_batalla']) ? $_GET['id_batalla'] : null;
    }
    
    /**
     * Busca una batalla activa para el usuario
     * 
     * @param int $usuarioId ID del usuario
     * @return string|null ID de sala de la primera batalla activa o null si no hay ninguna
     */
    public function buscarBatallaActiva($usuarioId) {
        return $this->combatModel->buscarBatallaActiva($usuarioId);
    }
    
    /**
     * Procesa la solicitud de combate y determina la acción a realizar
     * 
     * @return array Información sobre la redirección a realizar
     */
    public function procesarSolicitudCombate() {
        // Verificar autenticación
        if (!$this->verificarAutenticacion()) {
            return [
                'redireccion' => 'Vista/Auth_Vista.php',
                'exito' => false
            ];
        }
        
        // Obtener ID de batalla
        $idBatalla = $this->obtenerIdBatalla();
        
        // Si hay ID en la URL, verificar si la batalla es válida
        if ($idBatalla && $idBatalla !== 'null') {
            $batalla = $this->obtenerDatosBatalla($idBatalla);
            
            if ($batalla && $this->esParticipante($idBatalla, $_SESSION['usuari_id'])) {
                return [
                    'redireccion' => "Vista/Combat_Vista.php?id_batalla={$idBatalla}",
                    'exito' => true
                ];
            }
        }
        
        // Buscar batalla activa si no se proporcionó ID o no es válida
        $idBatalla = $this->buscarBatallaActiva($_SESSION['usuari_id']);
        
        if ($idBatalla) {
            return [
                'redireccion' => "Vista/Combat_Vista.php?id_batalla={$idBatalla}",
                'exito' => true
            ];
        }
        
        // Si no hay batalla activa, verificar si hay invitaciones pendientes
        require_once __DIR__ . '/../Model/BatallaModel.php';
        $batallaModel = new BatallaModel($this->connexio);
        $desafiosPendientes = $batallaModel->getDesafiosPendientes($_SESSION['usuari_id']);
        
        if (!empty($desafiosPendientes)) {
            return [
                'redireccion' => "index.php",
                'exito' => false,
                'mensaje' => 'Tienes desafíos pendientes que debes aceptar primero'
            ];
        }
        
        // Último recurso: redirigir a index.php
        return [
            'redireccion' => 'index.php',
            'exito' => false,
            'mensaje' => 'No se encontraron batallas activas'
        ];
    }

    /**
     * Obtiene los datos completos de una batalla
     * 
     * @param int $idBatalla ID de la batalla
     * @return array|null Datos de la batalla o null si no existe
     */
    public function obtenerDatosBatalla($idBatalla) {
        return $this->combatModel->obtenerDatosBatalla($idBatalla);
    }

    /**
     * Obtiene los pokémon de un equipo específico para una batalla
     * 
     * @param int $equipId ID del equipo
     * @return array Array con los Pokémon del equipo
     */
    public function obtenerPokemonEquipo($equipId) {
        return $this->combatModel->obtenerPokemonEquipo($equipId);
    }

    /**
     * Obtiene los movimientos de un Pokémon en batalla
     * 
     * @param int $estatPokemonId ID del estado del Pokémon en batalla
     * @return array Array con los movimientos del Pokémon
     */
    public function obtenerMovimientosPokemon($estatPokemonId) {
        return $this->combatModel->obtenerMovimientosPokemon($estatPokemonId);
    }

    /**
     * Verifica si un usuario es participante en la batalla
     * 
     * @param int $batallaId ID de la batalla
     * @param int $usuarioId ID del usuario
     * @return bool True si el usuario es participante, false en caso contrario
     */
    public function esParticipante($batallaId, $usuarioId) {
        return $this->combatModel->esParticipante($batallaId, $usuarioId);
    }

    /**
     * Verifica si un token público es válido para una batalla
     * 
     * @param int $batallaId ID de la batalla
     * @param string $token Token público
     * @return bool True si el token es válido, false en caso contrario
     */
    public function esTokenValido($batallaId, $token) {
        return $this->combatModel->esTokenValido($batallaId, $token);
    }

    /**
     * Inicializa una batalla (cambia estado a 'activa')
     * 
     * @param int $batallaId ID de la batalla
     * @return bool True si se actualizó correctamente, false en caso contrario
     */
    public function inicializarBatalla($batallaId) {
        return $this->combatModel->inicializarBatalla($batallaId);
    }

    /**
     * Prepara todos los datos necesarios para la vista de combate
     * 
     * @return array Datos necesarios para la vista o array indicando redirección
     */
    public function prepararDatosVista() {
        // Verificar si hay ID de batalla
        $idBatalla = $this->obtenerIdBatalla();

        if (!$idBatalla) {
            return [
                'redireccion' => 'index.php',
                'exito' => false
            ];
        }

        // Verificar autenticación y participación
        $usuarioId = isset($_SESSION['usuari_id']) ? $_SESSION['usuari_id'] : null;
        $modoEspectador = false;
        $tokenPublico = isset($_GET['token']) ? $_GET['token'] : null;

        if ($usuarioId) {
            $esParticipante = $this->esParticipante($idBatalla, $usuarioId);
            
            if (!$esParticipante) {
                // Verificar si es un espectador con token válido
                if ($tokenPublico && $this->esTokenValido($idBatalla, $tokenPublico)) {
                    $modoEspectador = true;
                } else {
                    return [
                        'redireccion' => 'index.php',
                        'exito' => false
                    ];
                }
            }
        } else if ($tokenPublico && $this->esTokenValido($idBatalla, $tokenPublico)) {
            // Espectadores sin cuenta pero con token
            $modoEspectador = true;
        } else {
            return [
                'redireccion' => 'Auth_Vista.php',
                'exito' => false
            ];
        }

        // Obtener datos de la batalla
        $datosBatalla = $this->obtenerDatosBatalla($idBatalla);

        if (!$datosBatalla) {
            return [
                'redireccion' => 'index.php',
                'exito' => false
            ];
        }

        // Determinar si el usuario actual es el usuario 1 o 2
        $esUsuario1 = $usuarioId && $usuarioId == $datosBatalla['usuari1_id'];
        $esUsuario2 = $usuarioId && $usuarioId == $datosBatalla['usuari2_id'];

        // Inicializar la batalla si está pendiente y ambos participantes están presentes
        if ($datosBatalla['estat'] === 'pendent' && $datosBatalla['usuari1_id'] && $datosBatalla['usuari2_id'] && 
            $datosBatalla['equip1_id'] && $datosBatalla['equip2_id'] && ($esUsuario1 || $esUsuario2)) {
            $this->inicializarBatalla($idBatalla);
            
            // Recargar datos después de inicializar
            $datosBatalla = $this->obtenerDatosBatalla($idBatalla);
        }

        // Obtener los datos de los equipos
        $pokemonEquip1 = $this->obtenerPokemonEquipo($datosBatalla['equip1_id']);
        $pokemonEquip2 = null;
        if ($datosBatalla['equip2_id']) {
            $pokemonEquip2 = $this->obtenerPokemonEquipo($datosBatalla['equip2_id']);
        }

        // Determinar título de la página
        $titolPagina = "Batalla Pokémon";
        if ($datosBatalla['nombre_usuari1'] && $datosBatalla['nombre_usuari2']) {
            $titolPagina = "Batalla: {$datosBatalla['nombre_usuari1']} vs {$datosBatalla['nombre_usuari2']}";
        }
        
        // Preparar todos los datos necesarios para la vista
        return [
            'exito' => true,
            'idBatalla' => $idBatalla,
            'datosBatalla' => $datosBatalla,
            'usuarioId' => $usuarioId,
            'modoEspectador' => $modoEspectador,
            'tokenPublico' => $tokenPublico,
            'esUsuario1' => $esUsuario1,
            'esUsuario2' => $esUsuario2,
            'pokemonEquip1' => $pokemonEquip1,
            'pokemonEquip2' => $pokemonEquip2,
            'titolPagina' => $titolPagina
        ];
    }
}
?>