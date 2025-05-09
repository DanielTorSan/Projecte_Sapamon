/**
 * combat.js
 * Script para gestionar las funciones del sistema de combate Pokémon
 */

// Variables globales que serán inicializadas desde la vista
let BATALLA_ID;
let ES_USUARIO1;
let ES_USUARIO2;
let MODO_ESPECTADOR;
let TOKEN_PUBLICO;
let MI_TURNO;
let USUARIO1_ID;
let USUARIO2_ID;
let battleData;
let ultimaAccionId = 0; // Se actualizará con cada respuesta
let intervaloActualizacion = null;

// Inicializar el sistema de combate con los datos de la vista
function inicializarCombate(datos) {
    // Configurar variables globales
    BATALLA_ID = datos.idBatalla;
    ES_USUARIO1 = datos.esUsuario1;
    ES_USUARIO2 = datos.esUsuario2;
    MODO_ESPECTADOR = datos.modoEspectador;
    TOKEN_PUBLICO = datos.tokenPublico;
    MI_TURNO = datos.esMiTurno;
    USUARIO1_ID = datos.usuari1Id;
    USUARIO2_ID = datos.usuari2Id;
    battleData = {
        idBatalla: datos.idBatalla,
        estado: datos.estat,
        esUsuario1: datos.esUsuario1,
        esUsuario2: datos.esUsuario2,
        esMiTurno: datos.esMiTurno,
        modoEspectador: datos.modoEspectador,
        equipo1: datos.pokemonEquip1,
        equipo2: datos.pokemonEquip2
    };

    // Iniciar cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Sistema de combate cargado');
        
        // Cargar datos iniciales
        cargarDatosPokemon();
        
        // Configurar eventos y otros elementos
        configurarElementosUI();
        
        // Primera actualización inmediata
        actualizarCombate();
        
        // Configurar intervalo de actualización (cada 3 segundos)
        intervaloActualizacion = setInterval(actualizarCombate, 3000);
        
        // Detener actualizaciones si la página pierde el foco
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'hidden') {
                clearInterval(intervaloActualizacion);
                console.log('Actualizaciones pausadas');
            } else {
                // Actualizar inmediatamente al volver
                actualizarCombate();
                intervaloActualizacion = setInterval(actualizarCombate, 3000);
                console.log('Actualizaciones reanudadas');
            }
        });
    });
}

// Función para actualizar la interfaz de combate
function actualizarCombate() {
    try {
        // Construir URL para la API
        let url = '../Controlador/combat_api.php?action=estat_batalla&batalla_id=' + BATALLA_ID + '&ultima_accion=' + ultimaAccionId;
        
        // Añadir token público si estamos en modo espectador
        if (MODO_ESPECTADOR && TOKEN_PUBLICO) {
            url += '&token=' + TOKEN_PUBLICO;
        }
        
        console.log('Actualizando combate, URL:', url);
        
        // Realizar la solicitud a la API
        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    procesarActualizaciones(data);
                } else {
                    console.error('Error al obtener actualizaciones:', data.message);
                }
            })
            .catch(error => {
                console.error('Error en la solicitud de actualizaciones:', error);
            });
    } catch (error) {
        console.error('Error general en actualizarCombate:', error);
        // Si hay un error grave, intentamos volver a habilitar la UI
        document.getElementById('loading-overlay').style.display = 'none';
    }
}

// Procesar las actualizaciones recibidas
function procesarActualizaciones(data) {
    console.log("Procesando actualizaciones:", data);
    
    // Actualizar ID de última acción para próximas consultas
    if (data.ultima_accion_id > ultimaAccionId) {
        ultimaAccionId = data.ultima_accion_id;
    }
    
    try {
        // Actualizar estado de la batalla
        if (data.batalla) {
            actualizarEstadoBatalla(data.batalla);
        }
        
        // Actualizar Pokémon
        if (data.pokemon1) {
            actualizarPokemon(data.pokemon1, 'opponent');
        }
        if (data.pokemon2) {
            actualizarPokemon(data.pokemon2, 'player');
        }
        
        // Mostrar nuevas acciones en el historial
        if (data.acciones && data.acciones.length > 0) {
            mostrarAccionesHistorial(data.acciones);
        }
        
        // Si la batalla ha terminado, detener actualizaciones
        if (data.batalla && data.batalla.estado === 'finalizada') {
            clearInterval(intervaloActualizacion);
            mostrarResultadoBatalla(data.batalla);
        }
    } catch (error) {
        console.error('Error al procesar actualizaciones:', error);
    }
}

// Función para configurar elementos de la interfaz
function configurarElementosUI() {
    // Configurar botón de salir
    const btnSalir = document.getElementById('btn-salir');
    if (btnSalir) {
        btnSalir.addEventListener('click', function() {
            if (confirm('¿Estás seguro que deseas abandonar la batalla?')) {
                // Redirigir a la página principal (index.php)
                window.location.href = '../index.php';
            }
        });
        console.log('Evento de botón salir configurado');
    } else {
        console.error('No se encontró el botón de salir');
    }
}

// Función para cargar los datos de los Pokémon en la interfaz
function cargarDatosPokemon() {
    console.log("Cargando datos de Pokémon:", battleData);
    
    // Mostrar equipos en la interfaz
    mostrarEquipoPokemon(battleData.equipo1, document.querySelector('.opponent-area'), 'opponent');
    mostrarEquipoPokemon(battleData.equipo2, document.querySelector('.player-area'), 'player');
    
    // Cargar selector de equipo y movimientos
    cargarSelectorEquipo();
    cargarMovimientosPokemon();
    
    // Ocultar el overlay de carga una vez que todo esté cargado
    document.getElementById('loading-overlay').style.display = 'none';
}

// Función para mostrar un Pokémon en la interfaz
function mostrarEquipoPokemon(equipo, areaElement, tipo) {
    if (!areaElement || !equipo || equipo.length === 0) {
        console.log(`No se puede mostrar equipo ${tipo}:`, equipo);
        return;
    }
    
    console.log(`Mostrando equipo ${tipo}:`, equipo);
    
    // Mostrar el primer Pokémon del equipo (el activo)
    const pokemon = equipo[0];
    
    // Crear estructura HTML para el Pokémon
    areaElement.innerHTML = `
        <div class="pokemon-sprite">
            <img src="${pokemon.sprite || 'assets/img/pokemon/unknown.png'}" 
                 alt="${pokemon.malnom || 'Pokémon #' + pokemon.pokeapi_id}">
        </div>
        <div class="pokemon-info">
            <h3>${pokemon.malnom || 'Pokémon #' + pokemon.pokeapi_id}</h3>
            <div class="pokemon-stats">
                <p>Nivel: ${pokemon.nivell || '??'}</p>
                <p>HP: ${pokemon.hp_actual || '??'}/${pokemon.hp_max || '??'}</p>
                <div class="hp-bar" style="width: ${(pokemon.hp_actual / pokemon.hp_max * 100) || 100}%"></div>
            </div>
        </div>
    `;
}

// Función para cargar el selector de equipo
function cargarSelectorEquipo() {
    const selectorEquipo = document.querySelector('.team-selector');
    if (!selectorEquipo) return;
    
    // Limpiar selector
    selectorEquipo.innerHTML = '';
    
    // Determinar qué equipo corresponde al jugador
    const equipoJugador = battleData.esUsuario1 ? battleData.equipo1 : 
                        (battleData.esUsuario2 ? battleData.equipo2 : 
                        (battleData.modoEspectador ? battleData.equipo1 : null));
    
    if (!equipoJugador) return;
    
    // Añadir cada Pokémon al selector de equipo
    equipoJugador.forEach((pokemon, index) => {
        const pokemonElement = document.createElement('div');
        pokemonElement.className = `team-pokemon${index === 0 ? ' active' : ''}`;
        pokemonElement.dataset.index = index;
        
        pokemonElement.innerHTML = `
            <img src="${pokemon.sprite || 'assets/img/pokemon/unknown.png'}" 
                 alt="${pokemon.malnom || 'Pokémon #' + pokemon.pokeapi_id}" />
        `;
        
        // Añadir evento de clic si no estamos en modo espectador y es nuestro turno
        if (!battleData.modoEspectador && battleData.esMiTurno) {
            pokemonElement.addEventListener('click', function() {
                // Implementar cambio de Pokémon
                cambiarPokemon(pokemon.id_equip_pokemon);
            });
        }
        
        selectorEquipo.appendChild(pokemonElement);
    });
}

// Función para cargar los movimientos del Pokémon activo
function cargarMovimientosPokemon() {
    const movimientosContainer = document.querySelector('.moves-container');
    if (!movimientosContainer) return;
    
    // Limpiar contenedor
    movimientosContainer.innerHTML = '';
    
    // Determinar qué equipo corresponde al jugador
    const equipoJugador = battleData.esUsuario1 ? battleData.equipo1 : 
                        (battleData.esUsuario2 ? battleData.equipo2 : null);
    
    if (!equipoJugador || equipoJugador.length === 0) return;
    
    // Obtener el primer Pokémon (el activo) y sus movimientos
    const pokemon = equipoJugador[0];
    const movimientos = pokemon.movimientos || [];
    
    // Añadir cada movimiento al contenedor
    movimientos.forEach(movimiento => {
        const movimientoElement = document.createElement('button');
        movimientoElement.className = `btn-action btn-move`;
        movimientoElement.dataset.id = movimiento.id_equip_moviment;
        movimientoElement.dataset.tipo = movimiento.tipus_moviment || 'normal';
        
        movimientoElement.textContent = movimiento.nom_moviment || 'Movimiento';
        
        // Añadir evento de clic si no estamos en modo espectador y es nuestro turno
        if (!battleData.modoEspectador && battleData.esMiTurno) {
            movimientoElement.addEventListener('click', function() {
                // Implementar uso de movimiento
                usarMovimiento(movimiento.id_equip_moviment);
            });
        } else {
            movimientoElement.disabled = true;
        }
        
        movimientosContainer.appendChild(movimientoElement);
    });
    
    // Si no hay movimientos, mostrar mensaje
    if (movimientos.length === 0) {
        const mensajeElement = document.createElement('div');
        mensajeElement.className = 'no-moves-message';
        mensajeElement.textContent = 'Este Pokémon no tiene movimientos registrados.';
        movimientosContainer.appendChild(mensajeElement);
    }
}

// Función para usar un movimiento
function usarMovimiento(movimientoId) {
    console.log(`Usando movimiento ID: ${movimientoId}`);
    ejecutarMovimiento(movimientoId);
}

// Función para cambiar de Pokémon
function cambiarPokemon(pokemonId) {
    console.log(`Cambiando al Pokémon ID: ${pokemonId}`);
    // Implementar la lógica para cambiar de Pokémon
}

// Actualizar la información general de la batalla
function actualizarEstadoBatalla(dataBatalla) {
    // Actualizar indicador de turno
    const esMiTurno = (ES_USUARIO1 && dataBatalla.turno_usuario_id == USUARIO1_ID) || 
                      (ES_USUARIO2 && dataBatalla.turno_usuario_id == USUARIO2_ID);
    
    // Actualizar visualmente el indicador de turno (usando la clase turn-indicator que ya existe)
    const turnoIndicador = document.querySelector('.turn-indicator');
    if (turnoIndicador) {
        turnoIndicador.textContent = esMiTurno ? 'Tu turno' : 'Turno del oponente';
        turnoIndicador.className = esMiTurno ? 'turn-indicator turno-activo' : 'turn-indicator turno-inactivo';
    }
    
    // Habilitar/deshabilitar botones según el turno, excepto el botón de salir
    const botones = document.querySelectorAll('.btn-action:not(#btn-salir)');
    botones.forEach(btn => {
        btn.disabled = !esMiTurno && !MODO_ESPECTADOR;
    });
    
    // Asegurar que el botón de salir esté siempre habilitado
    const btnSalir = document.getElementById('btn-salir');
    if (btnSalir) {
        btnSalir.disabled = false;
    }
    
    // Actualizar número de ronda si existe el elemento
    const numeroRonda = document.querySelector('#numero-ronda');
    if (numeroRonda) {
        numeroRonda.textContent = 'Ronda ' + dataBatalla.ronda_actual;
    }
}

// Actualizar la información de un Pokémon
function actualizarPokemon(dataPokemon, tipo) {
    try {
        // Actualiza los pokemon directamente en las áreas
        const areaSelector = tipo === 'player' ? '.player-area' : '.opponent-area';
        const pokemonArea = document.querySelector(areaSelector);
        
        if (!pokemonArea || !dataPokemon) {
            console.error(`No se puede actualizar el Pokémon ${tipo}:`, dataPokemon);
            return;
        }
        
        // Crear estructura HTML para el Pokémon actualizado
        pokemonArea.innerHTML = `
            <div class="pokemon-sprite">
                <img src="${dataPokemon.sprite || 'assets/img/pokemon/unknown.png'}" 
                     alt="${dataPokemon.malnom || 'Pokémon #' + dataPokemon.pokeapi_id}">
            </div>
            <div class="pokemon-info">
                <h3>${dataPokemon.malnom || 'Pokémon #' + dataPokemon.pokeapi_id}</h3>
                <div class="pokemon-stats">
                    <p>Nivel: ${dataPokemon.nivell || '??'}</p>
                    <p>HP: ${dataPokemon.hp_actual || '??'}/${dataPokemon.hp_max || '??'}</p>
                    <div class="hp-bar" style="width: ${(dataPokemon.hp_actual / dataPokemon.hp_max * 100) || 100}%"></div>
                </div>
            </div>
        `;
        
        // Si es el jugador y no es espectador, actualizar botones de movimientos
        if (tipo === 'player' && !MODO_ESPECTADOR) {
            const movimientosContainer = document.querySelector('.moves-container');
            if (movimientosContainer && dataPokemon.movimientos) {
                // Actualizar los movimientos disponibles
                cargarMovimientosPokemon();
            }
        }
    } catch (error) {
        console.error("Error al actualizar Pokémon:", error);
    }
}

// Mostrar nuevas acciones en el historial
function mostrarAccionesHistorial(acciones) {
    const historialContainer = document.querySelector('.messages-panel');
    
    if (!historialContainer) {
        console.error('No se encontró el contenedor del historial de acciones');
        return;
    }
    
    // Añadir cada nueva acción al historial
    acciones.forEach(accion => {
        const elemento = document.createElement('div');
        elemento.className = 'combat-message ' + obtenerClaseAccion(accion.tipo);
        elemento.textContent = accion.mensaje || accion.texto || 'Acción de batalla';
        
        // Añadir el elemento al inicio o al final según preferencia
        historialContainer.appendChild(elemento);
        
        // Desplazar al final si el historial está al final
        if (historialContainer.scrollHeight - historialContainer.scrollTop <= historialContainer.clientHeight + 50) {
            historialContainer.scrollTop = historialContainer.scrollHeight;
        }
    });
}

// Obtener clase CSS según tipo de acción
function obtenerClaseAccion(tipo) {
    switch (tipo) {
        case 'ataque': return 'attack';
        case 'efecto': return 'effect';
        case 'cambio': return 'switch';
        case 'fainted': return 'fainted';
        default: return 'info';
    }
}

// Mostrar resultado de la batalla cuando finaliza
function mostrarResultadoBatalla(dataBatalla) {
    // Crear overlay para resultado
    const overlay = document.createElement('div');
    overlay.className = 'batalla-resultado-overlay';
    overlay.innerHTML = `
        <div class="batalla-resultado-container">
            <h2>La batalla ha finalizado</h2>
            <p id="batalla-resultado-mensaje"></p>
            <button id="volver-inicio" class="btn-action">Volver al inicio</button>
        </div>
    `;
    
    document.body.appendChild(overlay);
    
    // Configurar mensaje según resultado
    // Aquí necesitaríamos más datos sobre el ganador que deberían venir en dataBatalla
    
    // Añadir evento al botón de volver
    document.getElementById('volver-inicio').addEventListener('click', function() {
        window.location.href = '../index.php';
    });
}

// Función para ejecutar un movimiento (para cuando es tu turno)
function ejecutarMovimiento(idMovimiento) {
    // Desactivar botones mientras se procesa
    const botones = document.querySelectorAll('.btn-action');
    botones.forEach(btn => {
        btn.disabled = true;
    });
    
    // Enviar la acción al servidor
    fetch('../Controlador/Combat_Controlador.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `accion=ejecutar_movimiento&movimiento_id=${idMovimiento}&batalla_id=${BATALLA_ID}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Movimiento ejecutado correctamente');
            // La próxima actualización mostrará el resultado
        } else {
            console.error('Error al ejecutar movimiento:', data.message);
            // Reactivar botones si hubo error
            botones.forEach(btn => {
                btn.disabled = !MI_TURNO;
            });
        }
    })
    .catch(error => {
        console.error('Error en la solicitud:', error);
        // Reactivar botones si hubo error
        botones.forEach(btn => {
            btn.disabled = !MI_TURNO;
        });
    });
}