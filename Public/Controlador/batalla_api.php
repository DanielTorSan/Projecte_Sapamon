<?php
header('Content-Type: application/json');
/**
 * batalla_api.php
 * 
 * Punto de entrada para las peticiones AJAX relacionadas con el sistema de combate Pokémon.
 * Este archivo sirve como interfaz entre el frontend y la API de combate.
 */

// Iniciar sesión para acceder a las variables de sesión
session_start();

// Cargar dependencias
require_once __DIR__ . '/../Model/configuracio.php';
require_once __DIR__ . '/../Model/Combat_Model.php';
require_once __DIR__ . '/../Model/BatallaModel.php';
require_once __DIR__ . '/../Poke_Combat_API/CombatAPIControlador.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuari_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'No autorizado'
    ]);
    exit;
}

$usuarioId = $_SESSION['usuari_id'];
$combatModel = new Combat_Model($connexio);
$batallaModel = new BatallaModel($connexio);
$combatAPI = new CombatAPIControlador();

// Determinar la acción a realizar
$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

// Procesar según la acción solicitada
switch ($action) {
    case 'estado_batalla':
        // Obtener el estado actual de una batalla
        $batallaId = isset($_GET['id_batalla']) ? intval($_GET['id_batalla']) : 0;
        $timestamp = isset($_GET['timestamp']) ? $_GET['timestamp'] : 0;
        
        if (!$batallaId) {
            echo json_encode([
                'success' => false,
                'message' => 'ID de batalla no válido'
            ]);
            break;
        }
        
        // Verificar si el usuario puede ver esta batalla
        if (!$combatModel->esParticipante($batallaId, $usuarioId) && 
            !isset($_GET['token']) && 
            !$combatModel->esTokenValido($batallaId, $_GET['token'])) {
            echo json_encode([
                'success' => false,
                'message' => 'No tienes permiso para ver esta batalla'
            ]);
            break;
        }
        
        // Usar directamente la API para obtener el estado actualizado
        $resultado = $combatAPI->obtenirEstatBatalla($batallaId);
        
        echo json_encode([
            'success' => $resultado['exit'],
            'hay_cambios' => true,
            'batalla' => $resultado['estat'] ?? null,
            'timestamp' => time() * 1000, // Convertir a milisegundos para JavaScript
            'message' => $resultado['error'] ?? null
        ]);
        break;
        
    case 'preparar_accion':
        // Almacenar temporalmente la acción seleccionada para este usuario (solo en sesión, no BD)
        $batallaId = isset($_POST['batalla_id']) ? intval($_POST['batalla_id']) : 0;
        $tipusAccio = isset($_POST['tipus_accio']) ? $_POST['tipus_accio'] : '';
        $movimentId = isset($_POST['moviment_id']) ? intval($_POST['moviment_id']) : null;
        $pokemonId = isset($_POST['pokemon_id']) ? intval($_POST['pokemon_id']) : null;
        
        if (!$batallaId || !$tipusAccio) {
            echo json_encode([
                'success' => false,
                'message' => 'Parámetros incompletos'
            ]);
            break;
        }
        
        // Verificar que el usuario sea participante de la batalla
        if (!$combatModel->esParticipante($batallaId, $usuarioId)) {
            echo json_encode([
                'success' => false,
                'message' => 'No eres participante de esta batalla'
            ]);
            break;
        }
        
        // Almacenar en sesión para evitar múltiples llamadas a la base de datos
        if (!isset($_SESSION['acciones_preparadas'])) {
            $_SESSION['acciones_preparadas'] = [];
        }
        
        $_SESSION['acciones_preparadas'][$batallaId] = [
            'tipo_accion' => $tipusAccio,
            'movimiento_id' => $movimentId,
            'pokemon_id' => $pokemonId,
            'seleccionada' => true
        ];
        
        echo json_encode([
            'success' => true,
            'accion_preparada' => true,
            'message' => 'Acción seleccionada. Confirma para enviar.'
        ]);
        break;
        
    case 'confirmar_accion':
        // Confirmar y enviar la acción completa (movimiento + Pokémon) que fue previamente seleccionada
        $batallaId = isset($_POST['batalla_id']) ? intval($_POST['batalla_id']) : 0;
        
        if (!$batallaId) {
            echo json_encode([
                'success' => false,
                'message' => 'ID de batalla no válido'
            ]);
            break;
        }
        
        // Verificar que el usuario sea participante de la batalla
        if (!$combatModel->esParticipante($batallaId, $usuarioId)) {
            echo json_encode([
                'success' => false,
                'message' => 'No eres participante de esta batalla'
            ]);
            break;
        }
        
        // Verificar que haya una acción preparada
        if (!isset($_SESSION['acciones_preparadas'][$batallaId]) || 
            !$_SESSION['acciones_preparadas'][$batallaId]['seleccionada']) {
            echo json_encode([
                'success' => false,
                'message' => 'No hay acción preparada para confirmar'
            ]);
            break;
        }
        
        $accionPreparada = $_SESSION['acciones_preparadas'][$batallaId];
        
        // Registrar la acción en la base de datos
        $accionRegistrada = $combatModel->registrarAccioTorn(
            $batallaId, 
            $usuarioId, 
            $accionPreparada['tipo_accion'], 
            $accionPreparada['movimiento_id'], 
            $accionPreparada['pokemon_id']
        );
        
        if (!$accionRegistrada) {
            echo json_encode([
                'success' => false,
                'message' => 'No se pudo registrar la acción'
            ]);
            break;
        }
        
        // Limpiar la acción preparada
        $_SESSION['acciones_preparadas'][$batallaId]['seleccionada'] = false;
        
        // Verificar si ambos jugadores han registrado sus acciones
        $batalla = $combatModel->obtenerDatosBatalla($batallaId);
        $tornActual = $batalla['torn_actual'] ?? 1;
        
        // Obtener todas las acciones del turno actual
        $acciones = $combatModel->obtenirAccionsTorn($batallaId, $tornActual);
        
        // Si ambos jugadores han registrado sus acciones, procesar el turno con la API
        if (count($acciones) >= 2) {
            // Preparar acciones para la API
            $accionJugador1 = null;
            $accionJugador2 = null;
            
            foreach ($acciones as $accion) {
                if ($accion['usuari_id'] == $batalla['usuari1_id']) {
                    // Formatear la acción para la API
                    $accionJugador1 = [
                        'tipus_accio' => $accion['tipus_accio'],
                        'moviment_id' => $accion['moviment_id'],
                        'pokemon_id' => $accion['pokemon_id'],
                        'usuari_id' => $accion['usuari_id']
                    ];
                } else if ($accion['usuari_id'] == $batalla['usuari2_id']) {
                    // Formatear la acción para la API
                    $accionJugador2 = [
                        'tipus_accio' => $accion['tipus_accio'],
                        'moviment_id' => $accion['moviment_id'],
                        'pokemon_id' => $accion['pokemon_id'],
                        'usuari_id' => $accion['usuari_id']
                    ];
                }
            }
            
            // Si tenemos acciones de ambos jugadores, procesar el turno
            if ($accionJugador1 && $accionJugador2) {
                // Procesar el turno con la API de combate
                $resultado = $combatAPI->processarTorn($batallaId, $accionJugador1, $accionJugador2);
                
                echo json_encode([
                    'success' => $resultado['exit'],
                    'turno_completado' => true,
                    'resultado' => $resultado['resultat'] ?? [],
                    'message' => $resultado['error'] ?? 'Turno procesado correctamente'
                ]);
                break;
            }
        }
        
        // Si no se procesó el turno completo, indicar que se registró la acción
        echo json_encode([
            'success' => true,
            'turno_completado' => false,
            'message' => 'Acción registrada. Esperando al oponente.'
        ]);
        break;
        
    case 'realizar_accion_turno':
        // Procesar una acción de turno (ataque, cambio de pokémon, etc.)
        $batallaId = isset($_POST['batalla_id']) ? intval($_POST['batalla_id']) : 0;
        $tipusAccio = isset($_POST['tipus_accio']) ? $_POST['tipus_accio'] : '';
        $movimentId = isset($_POST['moviment_id']) ? intval($_POST['moviment_id']) : null;
        $pokemonId = isset($_POST['pokemon_id']) ? intval($_POST['pokemon_id']) : null;
        
        if (!$batallaId || !$tipusAccio) {
            echo json_encode([
                'success' => false,
                'message' => 'Parámetros incompletos'
            ]);
            break;
        }
        
        // Verificar que el usuario sea participante de la batalla
        if (!$combatModel->esParticipante($batallaId, $usuarioId)) {
            echo json_encode([
                'success' => false,
                'message' => 'No eres participante de esta batalla'
            ]);
            break;
        }
        
        // Registrar la acción en la base de datos
        $accionRegistrada = $combatModel->registrarAccioTorn(
            $batallaId, 
            $usuarioId, 
            $tipusAccio, 
            $movimentId, 
            $pokemonId
        );
        
        if (!$accionRegistrada) {
            echo json_encode([
                'success' => false,
                'message' => 'No se pudo registrar la acción'
            ]);
            break;
        }
        
        // Verificar si ambos jugadores han registrado sus acciones
        $batalla = $combatModel->obtenerDatosBatalla($batallaId);
        $tornActual = $batalla['torn_actual'] ?? 1;
        
        // Obtener todas las acciones del turno actual
        $acciones = $combatModel->obtenirAccionsTorn($batallaId, $tornActual);
        
        // Si ambos jugadores han registrado sus acciones, procesar el turno con la API
        if (count($acciones) >= 2) {
            // Preparar acciones para la API
            $accionJugador1 = null;
            $accionJugador2 = null;
            
            foreach ($acciones as $accion) {
                if ($accion['usuari_id'] == $batalla['usuari1_id']) {
                    // Formatear la acción para la API
                    $accionJugador1 = [
                        'tipus_accio' => $accion['tipus_accio'],
                        'moviment_id' => $accion['moviment_id'],
                        'pokemon_id' => $accion['pokemon_id'],
                        'usuari_id' => $accion['usuari_id']
                    ];
                } else if ($accion['usuari_id'] == $batalla['usuari2_id']) {
                    // Formatear la acción para la API
                    $accionJugador2 = [
                        'tipus_accio' => $accion['tipus_accio'],
                        'moviment_id' => $accion['moviment_id'],
                        'pokemon_id' => $accion['pokemon_id'],
                        'usuari_id' => $accion['usuari_id']
                    ];
                }
            }
            
            // Si tenemos acciones de ambos jugadores, procesar el turno
            if ($accionJugador1 && $accionJugador2) {
                // Procesar el turno con la API de combate
                $resultado = $combatAPI->processarTorn($batallaId, $accionJugador1, $accionJugador2);
                
                echo json_encode([
                    'success' => $resultado['exit'],
                    'turno_completado' => true,
                    'resultado' => $resultado['resultat'] ?? [],
                    'message' => $resultado['error'] ?? 'Turno procesado correctamente'
                ]);
                break;
            }
        }
        
        // Si no se procesó el turno completo, indicar que se registró la acción
        echo json_encode([
            'success' => true,
            'turno_completado' => false,
            'message' => 'Acción registrada. Esperando al oponente.'
        ]);
        break;
        
    case 'rendicion':
        // Procesar una rendición
        $batallaId = isset($_POST['batalla_id']) ? intval($_POST['batalla_id']) : 0;
        
        if (!$batallaId) {
            echo json_encode([
                'success' => false,
                'message' => 'ID de batalla no válido'
            ]);
            break;
        }
        
        // Verificar que el usuario sea participante
        if (!$combatModel->esParticipante($batallaId, $usuarioId)) {
            echo json_encode([
                'success' => false,
                'message' => 'No eres participante de esta batalla'
            ]);
            break;
        }
        
        // Usar la API para procesar la rendición
        $resultado = $combatAPI->processarRendicio($batallaId, $usuarioId);
        
        echo json_encode([
            'success' => $resultado['exit'],
            'message' => $resultado['missatge'] ?? ($resultado['error'] ?? 'Error procesando rendición')
        ]);
        break;
    
    case 'desafiaments_pendents':
        // Obtener los desafíos pendientes para el usuario
        try {
            // Usar BatallaModel para obtener los desafíos pendientes
            $desafios = $batallaModel->getDesafiosPendientes($usuarioId);
            
            // Verificar si hay una batalla activa para redirección
            $batalla = null;
            $batallaActiva = null;
            
            // En caso de que haya desafíos, buscar si alguno ya está activo
            foreach ($desafios as $desafio) {
                if ($desafio['estat'] === 'acceptada') {
                    $batalla = $batallaModel->obtenerBatallaPorId($desafio['id_batalla']);
                    if ($batalla) {
                        $batallaActiva = $batalla['id_batalla'];
                        break;
                    }
                }
            }
            
            echo json_encode([
                'success' => true,
                'desafios' => $desafios,
                'batalla_activa' => $batallaActiva
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener desafíos: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'crear_desafiament':
        // Procesar la creación de un nuevo desafío
        $retadoId = isset($_POST['retado_id']) ? intval($_POST['retado_id']) : 0;
        $equipoId = isset($_POST['equipo_id']) ? intval($_POST['equipo_id']) : 0;
        
        if (!$retadoId || !$equipoId) {
            echo json_encode([
                'success' => false,
                'message' => 'Faltan parámetros requeridos'
            ]);
            break;
        }
        
        try {
            // Usar BatallaModel para crear el desafío
            $resultado = $batallaModel->crearDesafio($usuarioId, $retadoId, $equipoId);
            
            echo json_encode($resultado);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al crear desafío: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'acceptar_desafiament':
        // Procesar la aceptación de un desafío
        $batallaId = isset($_POST['batalla_id']) ? intval($_POST['batalla_id']) : 0;
        $equipoId = isset($_POST['equipo_id']) ? intval($_POST['equipo_id']) : 0;
        
        if (!$batallaId || !$equipoId) {
            echo json_encode([
                'success' => false,
                'message' => 'Faltan parámetros requeridos'
            ]);
            break;
        }
        
        try {
            // Usar BatallaModel para aceptar el desafío
            $resultado = $batallaModel->aceptarDesafio($batallaId, $usuarioId, $equipoId);
            
            echo json_encode($resultado);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al aceptar desafío: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'rebutjar_desafiament':
        // Rechazar un desafío pendiente
        $batallaId = isset($_POST['batalla_id']) ? intval($_POST['batalla_id']) : 0;
        
        if (!$batallaId) {
            echo json_encode([
                'success' => false,
                'message' => 'ID de batalla no válido'
            ]);
            break;
        }
        
        try {
            // Usar BatallaModel para rechazar el desafío
            $resultado = $batallaModel->rechazarDesafio($batallaId, $usuarioId);
            
            echo json_encode($resultado);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al rechazar desafío: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'cancellar_desafiament':
        // Cancelar un desafío enviado
        $batallaId = isset($_POST['batalla_id']) ? intval($_POST['batalla_id']) : 0;
        
        if (!$batallaId) {
            echo json_encode([
                'success' => false,
                'message' => 'ID de batalla no válido'
            ]);
            break;
        }
        
        try {
            // Usar BatallaModel para cancelar el desafío
            $resultado = $batallaModel->cancelarDesafio($batallaId, $usuarioId);
            
            echo json_encode($resultado);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al cancelar desafío: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'obtener_datos_pokemon':
        // Obtener datos detallados de un Pokémon de la PokeAPI
        $pokemonId = isset($_GET['pokemon_id']) ? intval($_GET['pokemon_id']) : 0;
        
        if (!$pokemonId) {
            echo json_encode([
                'success' => false,
                'message' => 'ID de Pokémon no válido'
            ]);
            break;
        }
        
        // Usar la API para obtener datos del Pokémon
        $resultado = $combatAPI->obtenirDadesPokemon($pokemonId);
        
        echo json_encode([
            'success' => $resultado['exit'],
            'pokemon' => $resultado['pokemon'] ?? null,
            'message' => $resultado['error'] ?? null
        ]);
        break;
        
    case 'seleccionar_pokemon_inicial':
        // Procesar la selección del Pokémon inicial para la batalla
        $batallaId = isset($_POST['batalla_id']) ? intval($_POST['batalla_id']) : 0;
        $pokemonId = isset($_POST['pokemon_id']) ? intval($_POST['pokemon_id']) : 0;
        
        if (!$batallaId || !$pokemonId) {
            echo json_encode([
                'success' => false,
                'message' => 'Parámetros incompletos'
            ]);
            break;
        }
        
        // Verificar que el usuario sea participante de la batalla
        if (!$combatModel->esParticipante($batallaId, $usuarioId)) {
            echo json_encode([
                'success' => false,
                'message' => 'No eres participante de esta batalla'
            ]);
            break;
        }
        
        // Establecer el Pokémon activo para este usuario
        $resultado = $combatModel->establecerPokemonActivo($batallaId, $usuarioId, $pokemonId);
        
        if ($resultado) {
            // Obtener los datos actualizados de la batalla
            $batalla = $combatModel->obtenerDatosBatalla($batallaId);
            $pokemonsInicializados = !empty($batalla['pokemon_actiu_1']) && !empty($batalla['pokemon_actiu_2']);
            
            echo json_encode([
                'success' => true,
                'message' => 'Pokémon seleccionado correctamente',
                'pokemon_id' => $pokemonId,
                'batalla_inicializada' => $pokemonsInicializados
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'No se pudo seleccionar el Pokémon'
            ]);
        }
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Acción no reconocida'
        ]);
        break;
}