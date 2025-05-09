<?php
/**
 * API para procesar un turno completo de combate cuando ambos jugadores han elegido acción
 */

// Configurar para reportar errores pero no mostrarlos al usuario
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/../../logs/combat_errors.log');

try {
    session_start();
    require_once __DIR__ . '/../../Model/configuracio.php';
    require_once __DIR__ . '/../../Model/funcions_combat/processar_torn_model.php';
    
    // Función para enviar respuestas JSON
    function enviarResposta($resposta, $codi = 200) {
        http_response_code($codi);
        header('Content-Type: application/json');
        echo json_encode($resposta);
        exit;
    }
    
    // Verificar autenticación
    if (!isset($_SESSION['usuari_id'])) {
        enviarResposta(['success' => false, 'message' => 'No autenticat'], 401);
    }
    
    $usuariId = $_SESSION['usuari_id'];
    
    // Verificar método de petición
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        enviarResposta(['success' => false, 'message' => 'Método no permitido'], 405);
    }
    
    // Validar datos de la petición
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['batalla_id']) || !isset($data['torn'])) {
        enviarResposta(['success' => false, 'message' => 'Faltan datos obligatorios'], 400);
    }
    
    $batallaId = (int)$data['batalla_id'];
    $tornActual = (int)$data['torn'];
    
    // Verificar que el usuario es participante de la batalla
    require_once __DIR__ . '/../../Model/Combat_Model.php';
    $combatModel = new Combat_Model($connexio);
    
    if (!$combatModel->esParticipante($batallaId, $usuariId)) {
        enviarResposta(['success' => false, 'message' => 'No tienes permiso para esta batalla'], 403);
    }
    
    // Crear instancia del modelo y procesar el turno
    $processarTornModel = new ProcessarTornModel($connexio);
    $resultat = $processarTornModel->processarTorn($batallaId, $tornActual);
    
    // Devolver resultado
    enviarResposta($resultat);
    
} catch (Exception $e) {
    error_log("Error en processar_torn.php: " . $e->getMessage());
    enviarResposta(['success' => false, 'message' => 'Error interno del servidor'], 500);
}
?>