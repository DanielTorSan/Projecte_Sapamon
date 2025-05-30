<?php
/**
 * Combat_Vista.php
 * 
 * Vista para el sistema de combates Pokémon
 */
session_start();
require_once '../Model/configuracio.php';
require_once '../Controlador/Combat_Controlador.php';

// Inicializar controlador
$combatControlador = new Combat_Controlador($connexio);

// Usar el controlador para preparar los datos de la vista
$datosVista = $combatControlador->prepararDatosVista();

// Verificar si hay redirección
if (!$datosVista['exito']) {
    header('Location: ' . $datosVista['redireccion']);
    exit();
}

// Extraer variables de los datos de la vista para usar en el template
extract($datosVista);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titolPagina; ?></title>
    <link rel="stylesheet" href="../Vista/assets/css/styles.css">
    <link rel="stylesheet" href="../Vista/assets/css/batalla.css">
    <!-- Incluir archivo JavaScript de combate -->
    <script src="../Vista/assets/js/combat.js"></script>
    <link rel="icon" href="../Vista/assets/img/favicon/Poké_Ball_icon.png" type="image/png">
</head>
<body>
    <!-- Overlay de carga -->
    <div id="loading-overlay">
        <div class="loading-spinner"></div>
        <p style="color: white; margin-top: 20px;">Cargando batalla...</p>
    </div>
    
    <!-- Modal para selección de Pokémon inicial -->
    <div id="pokemon-selector-modal" style="display: none;">
        <div class="modal-content">
            <h2>Selecciona tu primer Pokémon para comenzar</h2>
            <div class="pokemon-starter-grid">
                <!-- Aquí se cargarán dinámicamente los Pokémon disponibles -->
            </div>
        </div>
    </div>
    
    <!-- Arena de combate -->
    <div id="combat-arena">
        <div class="combat-header">
            <div class="battle-info">
                <h1>Batalla #<?php echo $idBatalla; ?></h1>
                <div class="battle-state">Estado: <?php echo ucfirst($datosBatalla['estat']); ?></div>
            </div>
            <div class="battle-players">
                <div class="player player1">
                    <img src="../Vista/assets/img/avatars/<?php echo $datosBatalla['avatar_usuari1'] ?: 'Youngster.png'; ?>" alt="Avatar" class="player-avatar">
                    <span><?php echo $datosBatalla['nombre_usuari1']; ?></span>
                    <span>(<?php echo $datosBatalla['nombre_equip1']; ?>)</span>
                </div>
                <span class="vs">VS</span>
                <div class="player player2">
                    <?php if ($datosBatalla['nombre_usuari2']): ?>
                        <img src="../Vista/assets/img/avatars/<?php echo $datosBatalla['avatar_usuari2'] ?: 'Youngster.png'; ?>" alt="Avatar" class="player-avatar">
                        <span><?php echo $datosBatalla['nombre_usuari2']; ?></span>
                        <span>(<?php echo $datosBatalla['nombre_equip2']; ?>)</span>
                    <?php else: ?>
                        <span>Esperando oponente...</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="battle-status">
                <?php if ($datosBatalla['estat'] === 'activa'): ?>
                    <?php if ($datosBatalla['torn_actual_id'] == $datosBatalla['usuari1_id']): ?>
                        <div class="turn-indicator">Turno de <?php echo $datosBatalla['nombre_usuari1']; ?></div>
                    <?php else: ?>
                        <div class="turn-indicator">Turno de <?php echo $datosBatalla['nombre_usuari2']; ?></div>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ($modoEspectador): ?>
                    <div class="spectator-badge">Modo Espectador</div>
                <?php endif; ?>
                
                <button id="btn-salir" class="btn-action">Salir</button>
            </div>
        </div>
        
        <div class="combat-field">
            <!-- Área del oponente -->
            <div class="opponent-area">
                <!-- Datos del Pokémon oponente -->
            </div>
            
            <!-- Área del jugador -->
            <div class="player-area">
                <!-- Datos del Pokémon del jugador -->
            </div>
            
            <?php if ($datosBatalla['estat'] === 'acabada'): ?>
                <div class="batalla-finalizada">
                    <?php if ($datosBatalla['guanyador_id']): ?>
                        <?php 
                        $nombreGanador = $datosBatalla['guanyador_id'] == $datosBatalla['usuari1_id'] 
                            ? $datosBatalla['nombre_usuari1'] 
                            : $datosBatalla['nombre_usuari2']; 
                        ?>
                        <div class="resultado-batalla">¡<?php echo $nombreGanador; ?> es el ganador!</div>
                    <?php endif; ?>
                    <p>La batalla ha terminado</p>
                    <button class="btn-action" onclick="window.location.href='../index.php'">Volver al inicio</button>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="combat-controls">
            <div class="actions-panel">
                <div class="moves-container">
                    <!-- Aquí se cargarán los movimientos -->
                </div>
                <div class="team-selector">
                    <!-- Aquí se cargarán los Pokémon del equipo -->
                </div>
                
                <!-- Nuevo panel para mostrar la acción seleccionada -->
                <div id="accion-seleccionada" style="display: none; margin-top: 10px; padding: 8px; background: #f0f0f0; border-radius: 4px;"></div>
                <button id="btn-confirmar-accion" style="display: none; margin-top: 10px;" class="btn-action">Confirmar Acción</button>
            </div>
            <div class="messages-panel">
                <!-- Aquí se mostrarán los mensajes de combate -->
                <div class="combat-message info">¡Bienvenido al combate!</div>
            </div>
        </div>
    </div>

    <!-- Inicializar el sistema de combate con los datos -->
    <script>
        // Pasar los datos del PHP al JavaScript
        const combatData = {
            idBatalla: <?php echo json_encode($idBatalla); ?>,
            esUsuario1: <?php echo json_encode($esUsuario1); ?>,
            esUsuario2: <?php echo json_encode($esUsuario2); ?>,
            usuari1Id: <?php echo json_encode($datosBatalla['usuari1_id']); ?>,
            usuari2Id: <?php echo json_encode($datosBatalla['usuari2_id']); ?>,
            usuariId: <?php echo json_encode($usuarioId); ?>,
            modoEspectador: <?php echo json_encode($modoEspectador); ?>,
            tokenPublico: <?php echo $tokenPublico ? json_encode($tokenPublico) : 'null'; ?>,
            tornActualId: <?php echo json_encode($datosBatalla['torn_actual_id']); ?>,
            estat: <?php echo json_encode($datosBatalla['estat']); ?>,
            esMiTurno: <?php echo json_encode(($esUsuario1 && $datosBatalla['torn_actual_id'] == $datosBatalla['usuari1_id']) || 
                                              ($esUsuario2 && $datosBatalla['torn_actual_id'] == $datosBatalla['usuari2_id'])); ?>,
            pokemonEquip1: <?php echo json_encode($pokemonEquip1); ?>,
            pokemonEquip2: <?php echo json_encode($pokemonEquip2); ?>
        };
        
        // Inicializar el sistema de combate
        inicializarCombate(combatData);
        
        // Configurar eventos para los botones de movimientos y selección de Pokémon
        document.addEventListener('DOMContentLoaded', function() {
            // Configurar evento para botón de confirmar acción
            const btnConfirmar = document.getElementById('btn-confirmar-accion');
            if (btnConfirmar) {
                btnConfirmar.addEventListener('click', confirmarAccion);
            }
            
            // Configurar eventos para los botones de movimientos
            const botonesMovimiento = document.querySelectorAll('.moves-container .btn-action');
            botonesMovimiento.forEach(function(boton) {
                boton.addEventListener('click', function() {
                    const movimientoId = this.dataset.id;
                    usarMovimiento(movimientoId);
                });
            });
            
            // Configurar eventos para los botones de selección de Pokémon
            const botonesPokemon = document.querySelectorAll('.team-selector .team-pokemon');
            botonesPokemon.forEach(function(boton) {
                boton.addEventListener('click', function() {
                    const pokemonIndex = this.dataset.index;
                    
                    // Obtener el equipo correcto según el usuario
                    const equipo = combatData.esUsuario1 ? combatData.pokemonEquip1 : combatData.pokemonEquip2;
                    
                    if (equipo && equipo[pokemonIndex]) {
                        const pokemonId = equipo[pokemonIndex].id_equip_pokemon;
                        cambiarPokemon(pokemonId);
                    }
                });
            });
        });
    </script>
</body>
</html>