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
let lastTimestamp = 0; // Para controlar actualizaciones
let intervaloActualizacion = null;

// Inicializar el sistema de combate con los datos de la vista
function inicializarCombate(datos) {
    console.log('Iniciando carga del combate con datos:', datos);
    
    try {
        // Configurar variables globales
        BATALLA_ID = datos.idBatalla;
        ES_USUARIO1 = datos.esUsuario1;
        ES_USUARIO2 = datos.esUsuario2;
        MODO_ESPECTADOR = datos.modoEspectador;
        TOKEN_PUBLICO = datos.tokenPublico;
        MI_TURNO = datos.esMiTurno;
        USUARIO1_ID = datos.usuari1Id;
        USUARIO2_ID = datos.usuari2Id;
        
        // Inicializar la estructura de datos de batalla
        battleData = {
            idBatalla: datos.idBatalla,
            estado: datos.estat,
            esUsuario1: datos.esUsuario1,
            esUsuario2: datos.esUsuario2,
            esMiTurno: datos.esMiTurno,
            modoEspectador: datos.modoEspectador,
            equipo1: datos.pokemonEquip1 || [],
            equipo2: datos.pokemonEquip2 || [],
            pokemonActivo1: null,  // Se inicializará más tarde
            pokemonActivo2: null,  // Se inicializará más tarde
            tornActual: datos.tornActual || 1
        };

        console.log('Variables globales configuradas');

        // Iniciar cuando el DOM esté listo
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', iniciarSistemaCombate);
        } else {
            // Si el DOM ya está cargado, iniciar inmediatamente
            iniciarSistemaCombate();
        }
    } catch (error) {
        console.error('Error en inicializarCombate:', error);
        document.getElementById('loading-overlay').style.display = 'none';
        mostrarMensaje('Error al inicializar el combate: ' + error.message, 'error');
    }
}

// Función para iniciar el sistema de combate una vez que el DOM está listo
function iniciarSistemaCombate() {
    console.log('DOM cargado, iniciando sistema de combate');
    
    try {
        // Primera actualización inmediata para obtener datos actualizados
        console.log('Realizando primera actualización del combate');
        actualizarCombate(true);  // true indica carga inicial
        
        // Configurar eventos y otros elementos
        console.log('Configurando elementos de UI');
        configurarElementosUI();
        
        // Verificar si necesitamos mostrar el selector de Pokémon inicial
        const batallaActiva = battleData.estado === 'activa';
        const pokemonActivo = battleData.esUsuario1 ? 
            Boolean(battleData.pokemonActivo1) : 
            Boolean(battleData.pokemonActivo2);
        
        // Si la batalla está activa pero no hay Pokémon activo seleccionado, mostrar selector
        if (batallaActiva && !pokemonActivo && !MODO_ESPECTADOR) {
            console.log('Mostrando selector de Pokémon inicial');
            mostrarSelectorPokemonInicial();
        } else {
            console.log('No es necesario mostrar selector de Pokémon inicial');
        }
        
        // Configurar intervalo de actualización (cada 3 segundos)
        console.log('Configurando intervalo de actualización');
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
        
        console.log('Sistema de combate inicializado correctamente');
    } catch (error) {
        console.error('Error durante la inicialización del combate:', error);
        // Ocultar el overlay de carga en caso de error para no dejar la pantalla bloqueada
        document.getElementById('loading-overlay').style.display = 'none';
        mostrarMensaje('Error al inicializar el combate: ' + error.message, 'error');
    }
}

// Función para actualizar la interfaz de combate
function actualizarCombate(esCargaInicial = false) {
    try {
        // Construir URL para la API
        let url = '../Controlador/batalla_api.php?action=estado_batalla&id_batalla=' + BATALLA_ID;
        
        // Añadir timestamp para control de cambios
        if (lastTimestamp > 0) {
            url += '&timestamp=' + lastTimestamp;
        }
        
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
                    // Actualizar timestamp para próximas consultas
                    if (data.timestamp) {
                        lastTimestamp = data.timestamp;
                    }
                    
                    // Procesar los datos recibidos
                    procesarActualizaciones(data, esCargaInicial);
                } else {
                    console.error('Error al obtener actualizaciones:', data.message);
                    // Ocultar overlay de carga en caso de error
                    document.getElementById('loading-overlay').style.display = 'none';
                    mostrarMensaje('Error: ' + (data.message || 'Error al actualizar'), 'error');
                }
            })
            .catch(error => {
                console.error('Error en la solicitud de actualizaciones:', error);
                // Asegurar que el overlay de carga se oculte
                document.getElementById('loading-overlay').style.display = 'none';
                // Mostrar mensaje de error al usuario
                mostrarMensaje('Error de conexión. Intentando reconectar...', 'error');
            });
    } catch (error) {
        console.error('Error general en actualizarCombate:', error);
        // Si hay un error grave, intentamos volver a habilitar la UI
        document.getElementById('loading-overlay').style.display = 'none';
        mostrarMensaje('Error al actualizar el combate: ' + error.message, 'error');
    }
}

// Procesar las actualizaciones recibidas
function procesarActualizaciones(data, esCargaInicial = false) {
    console.log("Procesando actualizaciones:", data);
    
    // Si no hay cambios y no es la carga inicial, no hacer nada
    if (data.hay_cambios === false && !esCargaInicial) {
        return;
    }
    
    try {
        // Actualizar estado de la batalla
        if (data.batalla) {
            // Si es la primera carga, establecer los Pokémon activos
            if (esCargaInicial) {
                if (data.batalla.pokemon_actiu_1) {
                    battleData.pokemonActivo1 = data.batalla.pokemon_actiu_1;
                }
                if (data.batalla.pokemon_actiu_2) {
                    battleData.pokemonActivo2 = data.batalla.pokemon_actiu_2;
                }
                
                // Cargar datos de Pokémon solo después de establecer los activos
                cargarDatosPokemon();
            }

            actualizarEstadoBatalla(data.batalla);
            
            // Actualizar Pokémon activos si han cambiado
            if (data.batalla.pokemon_actiu_1 && 
                (!battleData.pokemonActivo1 || battleData.pokemonActivo1 !== data.batalla.pokemon_actiu_1)) {
                battleData.pokemonActivo1 = data.batalla.pokemon_actiu_1;
                actualizarPokemon(data.batalla.pokemon_actiu_1, ES_USUARIO1 ? 'player' : 'opponent');
            }
            
            if (data.batalla.pokemon_actiu_2 && 
                (!battleData.pokemonActivo2 || battleData.pokemonActivo2 !== data.batalla.pokemon_actiu_2)) {
                battleData.pokemonActivo2 = data.batalla.pokemon_actiu_2;
                actualizarPokemon(data.batalla.pokemon_actiu_2, ES_USUARIO2 ? 'player' : 'opponent');
            }
            
            // Mostrar nuevas acciones en el historial
            if (data.batalla.ultimesAccions && data.batalla.ultimesAccions.length > 0) {
                mostrarAccionesHistorial(data.batalla.ultimesAccions);
            }
            
            // Si la batalla ha terminado, detener actualizaciones
            if (data.batalla.batalla_finalitzada) {
                clearInterval(intervaloActualizacion);
                mostrarResultadoBatalla(data.batalla);
            }
            
            // Ocultar el overlay de carga una vez procesado todo
            if (esCargaInicial) {
                document.getElementById('loading-overlay').style.display = 'none';
            }
        }
    } catch (error) {
        console.error('Error al procesar actualizaciones:', error);
        document.getElementById('loading-overlay').style.display = 'none';
    }
}

// Función para configurar elementos de la interfaz
function configurarElementosUI() {
    // Configurar botón de salir
    const btnSalir = document.getElementById('btn-salir');
    if (btnSalir) {
        btnSalir.addEventListener('click', function() {
            if (confirm('¿Estás seguro que deseas abandonar la batalla?')) {
                // Si es participante y la batalla está activa, preguntar por rendición
                if ((ES_USUARIO1 || ES_USUARIO2) && battleData.estado === 'activa') {
                    if (confirm('¿Deseas rendirte? Esto dará la victoria a tu oponente.')) {
                        procesarRendicion();
                        return;
                    }
                }
                
                // Redirigir a la página principal (index.php)
                window.location.href = '../index.php';
            }
        });
        console.log('Evento de botón salir configurado');
    } else {
        console.error('No se encontró el botón de salir');
    }
}

// Función para procesar una rendición
function procesarRendicion() {
    // Mostrar overlay de carga durante el proceso
    document.getElementById('loading-overlay').style.display = 'flex';
    
    // Enviar solicitud de rendición
    fetch('../Controlador/batalla_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=rendicion&batalla_id=${BATALLA_ID}`
    })
    .then(response => response.json())
    .then(data => {
        // Ocultar overlay de carga
        document.getElementById('loading-overlay').style.display = 'none';
        
        if (data.success) {
            alert('Te has rendido. Redirigiendo a la página principal...');
            window.location.href = '../index.php';
        } else {
            alert('Error al procesar la rendición: ' + (data.message || 'Error desconocido'));
            console.error('Error en rendición:', data);
        }
    })
    .catch(error => {
        document.getElementById('loading-overlay').style.display = 'none';
        console.error('Error al procesar rendición:', error);
        alert('Error de conexión al intentar rendirse');
    });
}

// Función para cargar los datos de los Pokémon en la interfaz
function cargarDatosPokemon() {
    console.log('Cargando datos de Pokémon: ', battleData);
    
    // Determinar el equipo del jugador y el del oponente
    let equipoJugador, equipoOponente;
    
    if (ES_USUARIO1) {
        equipoJugador = battleData.equipo1;
        equipoOponente = battleData.equipo2;
    } else if (ES_USUARIO2) {
        equipoJugador = battleData.equipo2;
        equipoOponente = battleData.equipo1;
    } else if (MODO_ESPECTADOR) {
        // En modo espectador mostramos los datos completos de ambos equipos
        equipoJugador = battleData.equipo2;  // Por convenio mostramos el equipo 2 abajo
        equipoOponente = battleData.equipo1; // Y el equipo 1 arriba
    }
    
    if (!equipoJugador || !equipoOponente) {
        console.error('No se han podido determinar los equipos');
        return;
    }
    
    // Mostrar el Pokémon activo del jugador en el área del jugador
    const pokemonActivoJugadorId = ES_USUARIO1 ? battleData.pokemonActivo1 : 
                                 (ES_USUARIO2 ? battleData.pokemonActivo2 : battleData.pokemonActivo2);
    mostrarPokemonActivo(equipoJugador, pokemonActivoJugadorId, 'player-area');
    
    // Mostrar el Pokémon activo del oponente en el área del oponente
    const pokemonActivoOponenteId = ES_USUARIO1 ? battleData.pokemonActivo2 : 
                                  (ES_USUARIO2 ? battleData.pokemonActivo1 : battleData.pokemonActivo1);
    mostrarPokemonActivo(equipoOponente, pokemonActivoOponenteId, 'opponent-area');
    
    // Cargar el selector de equipo
    cargarSelectorEquipo(equipoJugador);
    
    // Cargar los movimientos del Pokémon activo
    cargarMovimientosPokemon(equipoJugador, pokemonActivoJugadorId);
    
    // Ocultar el overlay de carga una vez que todo esté cargado
    document.getElementById('loading-overlay').style.display = 'none';
}

// Función para mostrar el Pokémon activo en el área correspondiente
function mostrarPokemonActivo(equipo, pokemonActivoId, areaSelector) {
    const areaElement = document.querySelector(`.${areaSelector}`);
    if (!areaElement) {
        console.error(`No se encontró el elemento con selector ${areaSelector}`);
        return;
    }
    
    // Si no hay ID de Pokémon activo, mostrar mensaje de selección
    if (!pokemonActivoId) {
        areaElement.innerHTML = `
            <div class="pokemon-pending">
                <div class="pokemon-pending-message">Esperando selección de Pokémon...</div>
            </div>
        `;
        return;
    }
    
    // Buscar el Pokémon activo en el equipo
    const pokemonActivo = equipo.find(p => p.id_equip_pokemon == pokemonActivoId);
    if (!pokemonActivo) {
        console.error(`No se encontró un Pokémon con ID ${pokemonActivoId} en el equipo`);
        return;
    }
    
    // Calcular el porcentaje de HP
    const hpPorcentaje = (pokemonActivo.hp_actual / pokemonActivo.hp_max) * 100;
    
    // Determinar el color de la barra de HP según el porcentaje
    let hpColor = '#78C850'; // Verde para HP alto
    if (hpPorcentaje < 50) {
        hpColor = '#F8D030'; // Amarillo para HP medio
    }
    if (hpPorcentaje < 25) {
        hpColor = '#F08030'; // Rojo para HP bajo
    }
    
    // Preparar la vista según si es área del jugador o del oponente
    const esAreaJugador = areaSelector === 'player-area';
    const spriteClass = esAreaJugador ? 'pokemon-sprite back' : 'pokemon-sprite front';
    
    // Crear el HTML del Pokémon activo
    areaElement.innerHTML = `
        <div class="pokemon-active">
            <img src="${pokemonActivo.sprite}" alt="${pokemonActivo.malnom}" class="${spriteClass}">
            <div class="pokemon-info">
                <div class="pokemon-name">${pokemonActivo.malnom}</div>
                <div class="pokemon-level">Nv. ${pokemonActivo.nivell}</div>
                <div class="pokemon-hp-bar">
                    <div class="hp-bar" style="width: ${hpPorcentaje}%; background-color: ${hpColor};"></div>
                </div>
                <div class="pokemon-hp-text">${pokemonActivo.hp_actual}/${pokemonActivo.hp_max} PS</div>
            </div>
        </div>
    `;
}

// Función para cargar el selector de equipo
function cargarSelectorEquipo(equipoJugador) {
    const selectorEquipo = document.querySelector('.team-selector');
    if (!selectorEquipo) {
        console.error('No se encontró el selector de equipo');
        return;
    }
    
    // Limpiar el selector
    selectorEquipo.innerHTML = '';
    
    // Si estamos en modo espectador o no es nuestro turno, deshabilitar interacción
    const puedeSeleccionar = !MODO_ESPECTADOR && battleData.esMiTurno;
    
    // Añadir cada Pokémon al selector
    equipoJugador.forEach((pokemon, index) => {
        const pokemonElement = document.createElement('div');
        
        // Determinar si este Pokémon es el activo
        const esActivo = (ES_USUARIO1 && pokemon.id_equip_pokemon == battleData.pokemonActivo1) || 
                         (ES_USUARIO2 && pokemon.id_equip_pokemon == battleData.pokemonActivo2);
        
        // Determinar si el Pokémon está debilitado (sin HP)
        const estaDebilitado = pokemon.hp_actual <= 0;
        
        // Asignar clases según el estado
        pokemonElement.className = `team-pokemon${esActivo ? ' active' : ''}${estaDebilitado ? ' fainted' : ''}`;
        pokemonElement.dataset.id = pokemon.id_equip_pokemon;
        pokemonElement.dataset.index = index;
        
        // Calcular el porcentaje de HP
        const hpPorcentaje = (pokemon.hp_actual / pokemon.hp_max) * 100;
        
        // Determinar el color de la barra de HP
        let hpColor = '#78C850'; // Verde para HP alto
        if (hpPorcentaje < 50) {
            hpColor = '#F8D030'; // Amarillo para HP medio
        }
        if (hpPorcentaje < 25) {
            hpColor = '#F08030'; // Rojo para HP bajo
        }
        
        // Crear el contenido HTML
        pokemonElement.innerHTML = `
            <img src="${pokemon.sprite}" alt="${pokemon.malnom}">
            <div class="pokemon-mini-info">
                <span class="mini-name">${pokemon.malnom}</span>
                <div class="mini-hp-bar">
                    <div class="hp-bar-inner" style="width: ${hpPorcentaje}%; background-color: ${hpColor};"></div>
                </div>
                <span class="mini-hp">${pokemon.hp_actual}/${pokemon.hp_max}</span>
            </div>
        `;
        
        // Añadir evento click solo si puede seleccionar y el Pokémon no está debilitado
        if (puedeSeleccionar && !estaDebilitado && !esActivo) {
            pokemonElement.addEventListener('click', () => cambiarPokemon(pokemon.id_equip_pokemon));
        }
        
        // Añadir al selector
        selectorEquipo.appendChild(pokemonElement);
    });
}

// Función para cargar los movimientos del Pokémon activo
function cargarMovimientosPokemon(equipoJugador, pokemonActivoId) {
    const movimientosContainer = document.querySelector('.moves-container');
    if (!movimientosContainer) {
        console.error('No se encontró el contenedor de movimientos');
        return;
    }
    
    // Limpiar el contenedor
    movimientosContainer.innerHTML = '';
    
    // Si no hay ID de Pokémon activo, mostrar mensaje
    if (!pokemonActivoId) {
        movimientosContainer.innerHTML = '<div class="no-moves">Selecciona un Pokémon primero</div>';
        return;
    }
    
    // Buscar el Pokémon activo en el equipo
    const pokemonActivo = equipoJugador.find(p => p.id_equip_pokemon == pokemonActivoId);
    if (!pokemonActivo) {
        console.error(`No se encontró un Pokémon con ID ${pokemonActivoId} en el equipo`);
        return;
    }
    
    // Si estamos en modo espectador o no es nuestro turno, deshabilitar interacción
    const puedeSeleccionar = !MODO_ESPECTADOR && battleData.esMiTurno;
    
    // Verificar si el Pokémon tiene movimientos
    if (!pokemonActivo.movimientos || pokemonActivo.movimientos.length === 0) {
        movimientosContainer.innerHTML = '<div class="no-moves">Este Pokémon no tiene movimientos</div>';
        return;
    }
    
    // Añadir cada movimiento al contenedor
    pokemonActivo.movimientos.forEach(movimiento => {
        const movimientoElement = document.createElement('button');
        movimientoElement.className = `btn-action btn-move tipo-${movimiento.tipus_moviment.toLowerCase()}`;
        movimientoElement.dataset.id = movimiento.id_equip_moviment;
        
        // Crear el contenido HTML
        movimientoElement.innerHTML = `
            <span class="move-name">${movimiento.nom_moviment}</span>
            <span class="move-type">${movimiento.tipus_moviment}</span>
            <span class="move-pp">${movimiento.pp_maxims}/${movimiento.pp_maxims} PP</span>
        `;
        
        // Añadir evento click solo si puede seleccionar
        if (puedeSeleccionar) {
            movimientoElement.addEventListener('click', () => usarMovimiento(movimiento.id_equip_moviment));
        } else {
            movimientoElement.disabled = true;
        }
        
        // Añadir al contenedor
        movimientosContainer.appendChild(movimientoElement);
    });
}

// Función para iniciar el sistema de combate una vez que el DOM está listo
function iniciarSistemaCombate() {
    console.log('DOM cargado, iniciando sistema de combate');
    
    try {
        // Primera actualización inmediata para obtener datos actualizados
        console.log('Realizando primera actualización del combate');
        actualizarCombate(true);  // true indica carga inicial
        
        // Configurar eventos y otros elementos
        console.log('Configurando elementos de UI');
        configurarElementosUI();
        
        // Verificar si necesitamos mostrar el selector de Pokémon inicial
        const batallaActiva = battleData.estado === 'activa';
        const pokemonActivo = battleData.esUsuario1 ? 
            Boolean(battleData.pokemonActivo1) : 
            Boolean(battleData.pokemonActivo2);
        
        // Si la batalla está activa pero no hay Pokémon activo seleccionado, mostrar selector
        if (batallaActiva && !pokemonActivo && !MODO_ESPECTADOR) {
            console.log('Mostrando selector de Pokémon inicial');
            mostrarSelectorPokemonInicial();
        } else {
            console.log('No es necesario mostrar selector de Pokémon inicial');
        }
        
        // Configurar intervalo de actualización (cada 3 segundos)
        console.log('Configurando intervalo de actualización');
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
        
        console.log('Sistema de combate inicializado correctamente');
    } catch (error) {
        console.error('Error durante la inicialización del combate:', error);
        // Ocultar el overlay de carga en caso de error para no dejar la pantalla bloqueada
        document.getElementById('loading-overlay').style.display = 'none';
        mostrarMensaje('Error al inicializar el combate: ' + error.message, 'error');
    }
}

// Función para actualizar la interfaz de combate
function actualizarCombate(esCargaInicial = false) {
    try {
        // Construir URL para la API
        let url = '../Controlador/batalla_api.php?action=estado_batalla&id_batalla=' + BATALLA_ID;
        
        // Añadir timestamp para control de cambios
        if (lastTimestamp > 0) {
            url += '&timestamp=' + lastTimestamp;
        }
        
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
                    // Actualizar timestamp para próximas consultas
                    if (data.timestamp) {
                        lastTimestamp = data.timestamp;
                    }
                    
                    // Procesar los datos recibidos
                    procesarActualizaciones(data, esCargaInicial);
                } else {
                    console.error('Error al obtener actualizaciones:', data.message);
                    // Ocultar overlay de carga en caso de error
                    document.getElementById('loading-overlay').style.display = 'none';
                    mostrarMensaje('Error: ' + (data.message || 'Error al actualizar'), 'error');
                }
            })
            .catch(error => {
                console.error('Error en la solicitud de actualizaciones:', error);
                // Asegurar que el overlay de carga se oculte
                document.getElementById('loading-overlay').style.display = 'none';
                // Mostrar mensaje de error al usuario
                mostrarMensaje('Error de conexión. Intentando reconectar...', 'error');
            });
    } catch (error) {
        console.error('Error general en actualizarCombate:', error);
        // Si hay un error grave, intentamos volver a habilitar la UI
        document.getElementById('loading-overlay').style.display = 'none';
        mostrarMensaje('Error al actualizar el combate: ' + error.message, 'error');
    }
}

// Procesar las actualizaciones recibidas
function procesarActualizaciones(data, esCargaInicial = false) {
    console.log("Procesando actualizaciones:", data);
    
    // Si no hay cambios y no es la carga inicial, no hacer nada
    if (data.hay_cambios === false && !esCargaInicial) {
        return;
    }
    
    try {
        // Actualizar estado de la batalla
        if (data.batalla) {
            // Si es la primera carga, establecer los Pokémon activos
            if (esCargaInicial) {
                if (data.batalla.pokemon_actiu_1) {
                    battleData.pokemonActivo1 = data.batalla.pokemon_actiu_1;
                }
                if (data.batalla.pokemon_actiu_2) {
                    battleData.pokemonActivo2 = data.batalla.pokemon_actiu_2;
                }
                
                // Cargar datos de Pokémon solo después de establecer los activos
                cargarDatosPokemon();
            }

            actualizarEstadoBatalla(data.batalla);
            
            // Actualizar Pokémon activos si han cambiado
            if (data.batalla.pokemon_actiu_1 && 
                (!battleData.pokemonActivo1 || battleData.pokemonActivo1 !== data.batalla.pokemon_actiu_1)) {
                battleData.pokemonActivo1 = data.batalla.pokemon_actiu_1;
                actualizarPokemon(data.batalla.pokemon_actiu_1, ES_USUARIO1 ? 'player' : 'opponent');
            }
            
            if (data.batalla.pokemon_actiu_2 && 
                (!battleData.pokemonActivo2 || battleData.pokemonActivo2 !== data.batalla.pokemon_actiu_2)) {
                battleData.pokemonActivo2 = data.batalla.pokemon_actiu_2;
                actualizarPokemon(data.batalla.pokemon_actiu_2, ES_USUARIO2 ? 'player' : 'opponent');
            }
            
            // Mostrar nuevas acciones en el historial
            if (data.batalla.ultimesAccions && data.batalla.ultimesAccions.length > 0) {
                mostrarAccionesHistorial(data.batalla.ultimesAccions);
            }
            
            // Si la batalla ha terminado, detener actualizaciones
            if (data.batalla.batalla_finalitzada) {
                clearInterval(intervaloActualizacion);
                mostrarResultadoBatalla(data.batalla);
            }
            
            // Ocultar el overlay de carga una vez procesado todo
            if (esCargaInicial) {
                document.getElementById('loading-overlay').style.display = 'none';
            }
        }
    } catch (error) {
        console.error('Error al procesar actualizaciones:', error);
        document.getElementById('loading-overlay').style.display = 'none';
    }
}

// Función para configurar elementos de la interfaz
function configurarElementosUI() {
    // Configurar botón de salir
    const btnSalir = document.getElementById('btn-salir');
    if (btnSalir) {
        btnSalir.addEventListener('click', function() {
            if (confirm('¿Estás seguro que deseas abandonar la batalla?')) {
                // Si es participante y la batalla está activa, preguntar por rendición
                if ((ES_USUARIO1 || ES_USUARIO2) && battleData.estado === 'activa') {
                    if (confirm('¿Deseas rendirte? Esto dará la victoria a tu oponente.')) {
                        procesarRendicion();
                        return;
                    }
                }
                
                // Redirigir a la página principal (index.php)
                window.location.href = '../index.php';
            }
        });
        console.log('Evento de botón salir configurado');
    } else {
        console.error('No se encontró el botón de salir');
    }
}

// Función para procesar una rendición
function procesarRendicion() {
    // Mostrar overlay de carga durante el proceso
    document.getElementById('loading-overlay').style.display = 'flex';
    
    // Enviar solicitud de rendición
    fetch('../Controlador/batalla_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=rendicion&batalla_id=${BATALLA_ID}`
    })
    .then(response => response.json())
    .then(data => {
        // Ocultar overlay de carga
        document.getElementById('loading-overlay').style.display = 'none';
        
        if (data.success) {
            alert('Te has rendido. Redirigiendo a la página principal...');
            window.location.href = '../index.php';
        } else {
            alert('Error al procesar la rendición: ' + (data.message || 'Error desconocido'));
            console.error('Error en rendición:', data);
        }
    })
    .catch(error => {
        document.getElementById('loading-overlay').style.display = 'none';
        console.error('Error al procesar rendición:', error);
        alert('Error de conexión al intentar rendirse');
    });
}

// Función para cargar los datos de los Pokémon en la interfaz
function cargarDatosPokemon() {
    console.log('Cargando datos de Pokémon: ', battleData);
    
    // Determinar el equipo del jugador y el del oponente
    let equipoJugador, equipoOponente;
    
    if (ES_USUARIO1) {
        equipoJugador = battleData.equipo1;
        equipoOponente = battleData.equipo2;
    } else if (ES_USUARIO2) {
        equipoJugador = battleData.equipo2;
        equipoOponente = battleData.equipo1;
    } else if (MODO_ESPECTADOR) {
        // En modo espectador mostramos los datos completos de ambos equipos
        equipoJugador = battleData.equipo2;  // Por convenio mostramos el equipo 2 abajo
        equipoOponente = battleData.equipo1; // Y el equipo 1 arriba
    }
    
    if (!equipoJugador || !equipoOponente) {
        console.error('No se han podido determinar los equipos');
        return;
    }
    
    // Mostrar el Pokémon activo del jugador en el área del jugador
    const pokemonActivoJugadorId = ES_USUARIO1 ? battleData.pokemonActivo1 : 
                                 (ES_USUARIO2 ? battleData.pokemonActivo2 : battleData.pokemonActivo2);
    mostrarPokemonActivo(equipoJugador, pokemonActivoJugadorId, 'player-area');
    
    // Mostrar el Pokémon activo del oponente en el área del oponente
    const pokemonActivoOponenteId = ES_USUARIO1 ? battleData.pokemonActivo2 : 
                                  (ES_USUARIO2 ? battleData.pokemonActivo1 : battleData.pokemonActivo1);
    mostrarPokemonActivo(equipoOponente, pokemonActivoOponenteId, 'opponent-area');
    
    // Cargar el selector de equipo
    cargarSelectorEquipo(equipoJugador);
    
    // Cargar los movimientos del Pokémon activo
    cargarMovimientosPokemon(equipoJugador, pokemonActivoJugadorId);
    
    // Ocultar el overlay de carga una vez que todo esté cargado
    document.getElementById('loading-overlay').style.display = 'none';
}

// Función para mostrar el Pokémon activo en el área correspondiente
function mostrarPokemonActivo(equipo, pokemonActivoId, areaSelector) {
    const areaElement = document.querySelector(`.${areaSelector}`);
    if (!areaElement) {
        console.error(`No se encontró el elemento con selector ${areaSelector}`);
        return;
    }
    
    // Si no hay ID de Pokémon activo, mostrar mensaje de selección
    if (!pokemonActivoId) {
        areaElement.innerHTML = `
            <div class="pokemon-pending">
                <div class="pokemon-pending-message">Esperando selección de Pokémon...</div>
            </div>
        `;
        return;
    }
    
    // Buscar el Pokémon activo en el equipo
    const pokemonActivo = equipo.find(p => p.id_equip_pokemon == pokemonActivoId);
    if (!pokemonActivo) {
        console.error(`No se encontró un Pokémon con ID ${pokemonActivoId} en el equipo`);
        return;
    }
    
    // Calcular el porcentaje de HP
    const hpPorcentaje = (pokemonActivo.hp_actual / pokemonActivo.hp_max) * 100;
    
    // Determinar el color de la barra de HP según el porcentaje
    let hpColor = '#78C850'; // Verde para HP alto
    if (hpPorcentaje < 50) {
        hpColor = '#F8D030'; // Amarillo para HP medio
    }
    if (hpPorcentaje < 25) {
        hpColor = '#F08030'; // Rojo para HP bajo
    }
    
    // Preparar la vista según si es área del jugador o del oponente
    const esAreaJugador = areaSelector === 'player-area';
    const spriteClass = esAreaJugador ? 'pokemon-sprite back' : 'pokemon-sprite front';
    
    // Crear el HTML del Pokémon activo
    areaElement.innerHTML = `
        <div class="pokemon-active">
            <img src="${pokemonActivo.sprite}" alt="${pokemonActivo.malnom}" class="${spriteClass}">
            <div class="pokemon-info">
                <div class="pokemon-name">${pokemonActivo.malnom}</div>
                <div class="pokemon-level">Nv. ${pokemonActivo.nivell}</div>
                <div class="pokemon-hp-bar">
                    <div class="hp-bar" style="width: ${hpPorcentaje}%; background-color: ${hpColor};"></div>
                </div>
                <div class="pokemon-hp-text">${pokemonActivo.hp_actual}/${pokemonActivo.hp_max} PS</div>
            </div>
        </div>
    `;
}

// Función para cargar el selector de equipo
function cargarSelectorEquipo(equipoJugador) {
    const selectorEquipo = document.querySelector('.team-selector');
    if (!selectorEquipo) {
        console.error('No se encontró el selector de equipo');
        return;
    }
    
    // Limpiar el selector
    selectorEquipo.innerHTML = '';
    
    // Si estamos en modo espectador o no es nuestro turno, deshabilitar interacción
    const puedeSeleccionar = !MODO_ESPECTADOR && battleData.esMiTurno;
    
    // Añadir cada Pokémon al selector
    equipoJugador.forEach((pokemon, index) => {
        const pokemonElement = document.createElement('div');
        
        // Determinar si este Pokémon es el activo
        const esActivo = (ES_USUARIO1 && pokemon.id_equip_pokemon == battleData.pokemonActivo1) || 
                         (ES_USUARIO2 && pokemon.id_equip_pokemon == battleData.pokemonActivo2);
        
        // Determinar si el Pokémon está debilitado (sin HP)
        const estaDebilitado = pokemon.hp_actual <= 0;
        
        // Asignar clases según el estado
        pokemonElement.className = `team-pokemon${esActivo ? ' active' : ''}${estaDebilitado ? ' fainted' : ''}`;
        pokemonElement.dataset.id = pokemon.id_equip_pokemon;
        pokemonElement.dataset.index = index;
        
        // Calcular el porcentaje de HP
        const hpPorcentaje = (pokemon.hp_actual / pokemon.hp_max) * 100;
        
        // Determinar el color de la barra de HP
        let hpColor = '#78C850'; // Verde para HP alto
        if (hpPorcentaje < 50) {
            hpColor = '#F8D030'; // Amarillo para HP medio
        }
        if (hpPorcentaje < 25) {
            hpColor = '#F08030'; // Rojo para HP bajo
        }
        
        // Crear el contenido HTML
        pokemonElement.innerHTML = `
            <img src="${pokemon.sprite}" alt="${pokemon.malnom}">
            <div class="pokemon-mini-info">
                <span class="mini-name">${pokemon.malnom}</span>
                <div class="mini-hp-bar">
                    <div class="hp-bar-inner" style="width: ${hpPorcentaje}%; background-color: ${hpColor};"></div>
                </div>
                <span class="mini-hp">${pokemon.hp_actual}/${pokemon.hp_max}</span>
            </div>
        `;
        
        // Añadir evento click solo si puede seleccionar y el Pokémon no está debilitado
        if (puedeSeleccionar && !estaDebilitado && !esActivo) {
            pokemonElement.addEventListener('click', () => cambiarPokemon(pokemon.id_equip_pokemon));
        }
        
        // Añadir al selector
        selectorEquipo.appendChild(pokemonElement);
    });
}

// Función para cargar los movimientos del Pokémon activo
function cargarMovimientosPokemon(equipoJugador, pokemonActivoId) {
    const movimientosContainer = document.querySelector('.moves-container');
    if (!movimientosContainer) {
        console.error('No se encontró el contenedor de movimientos');
        return;
    }
    
    // Limpiar el contenedor
    movimientosContainer.innerHTML = '';
    
    // Si no hay ID de Pokémon activo, mostrar mensaje
    if (!pokemonActivoId) {
        movimientosContainer.innerHTML = '<div class="no-moves">Selecciona un Pokémon primero</div>';
        return;
    }
    
    // Buscar el Pokémon activo en el equipo
    const pokemonActivo = equipoJugador.find(p => p.id_equip_pokemon == pokemonActivoId);
    if (!pokemonActivo) {
        console.error(`No se encontró un Pokémon con ID ${pokemonActivoId} en el equipo`);
        return;
    }
    
    // Si estamos en modo espectador o no es nuestro turno, deshabilitar interacción
    const puedeSeleccionar = !MODO_ESPECTADOR && battleData.esMiTurno;
    
    // Verificar si el Pokémon tiene movimientos
    if (!pokemonActivo.movimientos || pokemonActivo.movimientos.length === 0) {
        movimientosContainer.innerHTML = '<div class="no-moves">Este Pokémon no tiene movimientos</div>';
        return;
    }
    
    // Añadir cada movimiento al contenedor
    pokemonActivo.movimientos.forEach(movimiento => {
        const movimientoElement = document.createElement('button');
        movimientoElement.className = `btn-action btn-move tipo-${movimiento.tipus_moviment.toLowerCase()}`;
        movimientoElement.dataset.id = movimiento.id_equip_moviment;
        
        // Crear el contenido HTML
        movimientoElement.innerHTML = `
            <span class="move-name">${movimiento.nom_moviment}</span>
            <span class="move-type">${movimiento.tipus_moviment}</span>
            <span class="move-pp">${movimiento.pp_maxims}/${movimiento.pp_maxims} PP</span>
        `;
        
        // Añadir evento click solo si puede seleccionar
        if (puedeSeleccionar) {
            movimientoElement.addEventListener('click', () => usarMovimiento(movimiento.id_equip_moviment));
        } else {
            movimientoElement.disabled = true;
        }
        
        // Añadir al contenedor
        movimientosContainer.appendChild(movimientoElement);
    });
}

// Función para iniciar el sistema de combate una vez que el DOM está listo
function iniciarSistemaCombate() {
    console.log('DOM cargado, iniciando sistema de combate');
    
    try {
        // Primera actualización inmediata para obtener datos actualizados
        console.log('Realizando primera actualización del combate');
        actualizarCombate(true);  // true indica carga inicial
        
        // Configurar eventos y otros elementos
        console.log('Configurando elementos de UI');
        configurarElementosUI();
        
        // Verificar si necesitamos mostrar el selector de Pokémon inicial
        const batallaActiva = battleData.estado === 'activa';
        const pokemonActivo = battleData.esUsuario1 ? 
            Boolean(battleData.pokemonActivo1) : 
            Boolean(battleData.pokemonActivo2);
        
        // Si la batalla está activa pero no hay Pokémon activo seleccionado, mostrar selector
        if (batallaActiva && !pokemonActivo && !MODO_ESPECTADOR) {
            console.log('Mostrando selector de Pokémon inicial');
            mostrarSelectorPokemonInicial();
        } else {
            console.log('No es necesario mostrar selector de Pokémon inicial');
        }
        
        // Configurar intervalo de actualización (cada 3 segundos)
        console.log('Configurando intervalo de actualización');
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
        
        console.log('Sistema de combate inicializado correctamente');
    } catch (error) {
        console.error('Error durante la inicialización del combate:', error);
        // Ocultar el overlay de carga en caso de error para no dejar la pantalla bloqueada
        document.getElementById('loading-overlay').style.display = 'none';
        mostrarMensaje('Error al inicializar el combate: ' + error.message, 'error');
    }
}

// Función para actualizar la interfaz de combate
function actualizarCombate(esCargaInicial = false) {
    try {
        // Construir URL para la API
        let url = '../Controlador/batalla_api.php?action=estado_batalla&id_batalla=' + BATALLA_ID;
        
        // Añadir timestamp para control de cambios
        if (lastTimestamp > 0) {
            url += '&timestamp=' + lastTimestamp;
        }
        
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
                    // Actualizar timestamp para próximas consultas
                    if (data.timestamp) {
                        lastTimestamp = data.timestamp;
                    }
                    
                    // Procesar los datos recibidos
                    procesarActualizaciones(data, esCargaInicial);
                } else {
                    console.error('Error al obtener actualizaciones:', data.message);
                    // Ocultar overlay de carga en caso de error
                    document.getElementById('loading-overlay').style.display = 'none';
                    mostrarMensaje('Error: ' + (data.message || 'Error al actualizar'), 'error');
                }
            })
            .catch(error => {
                console.error('Error en la solicitud de actualizaciones:', error);
                // Asegurar que el overlay de carga se oculte
                document.getElementById('loading-overlay').style.display = 'none';
                // Mostrar mensaje de error al usuario
                mostrarMensaje('Error de conexión. Intentando reconectar...', 'error');
            });
    } catch (error) {
        console.error('Error general en actualizarCombate:', error);
        // Si hay un error grave, intentamos volver a habilitar la UI
        document.getElementById('loading-overlay').style.display = 'none';
        mostrarMensaje('Error al actualizar el combate: ' + error.message, 'error');
    }
}

// Procesar las actualizaciones recibidas
function procesarActualizaciones(data, esCargaInicial = false) {
    console.log("Procesando actualizaciones:", data);
    
    // Si no hay cambios y no es la carga inicial, no hacer nada
    if (data.hay_cambios === false && !esCargaInicial) {
        return;
    }
    
    try {
        // Actualizar estado de la batalla
        if (data.batalla) {
            // Si es la primera carga, establecer los Pokémon activos
            if (esCargaInicial) {
                if (data.batalla.pokemon_actiu_1) {
                    battleData.pokemonActivo1 = data.batalla.pokemon_actiu_1;
                }
                if (data.batalla.pokemon_actiu_2) {
                    battleData.pokemonActivo2 = data.batalla.pokemon_actiu_2;
                }
                
                // Cargar datos de Pokémon solo después de establecer los activos
                cargarDatosPokemon();
            }

            actualizarEstadoBatalla(data.batalla);
            
            // Actualizar Pokémon activos si han cambiado
            if (data.batalla.pokemon_actiu_1 && 
                (!battleData.pokemonActivo1 || battleData.pokemonActivo1 !== data.batalla.pokemon_actiu_1)) {
                battleData.pokemonActivo1 = data.batalla.pokemon_actiu_1;
                actualizarPokemon(data.batalla.pokemon_actiu_1, ES_USUARIO1 ? 'player' : 'opponent');
            }
            
            if (data.batalla.pokemon_actiu_2 && 
                (!battleData.pokemonActivo2 || battleData.pokemonActivo2 !== data.batalla.pokemon_actiu_2)) {
                battleData.pokemonActivo2 = data.batalla.pokemon_actiu_2;
                actualizarPokemon(data.batalla.pokemon_actiu_2, ES_USUARIO2 ? 'player' : 'opponent');
            }
            
            // Mostrar nuevas acciones en el historial
            if (data.batalla.ultimesAccions && data.batalla.ultimesAccions.length > 0) {
                mostrarAccionesHistorial(data.batalla.ultimesAccions);
            }
            
            // Si la batalla ha terminado, detener actualizaciones
            if (data.batalla.batalla_finalitzada) {
                clearInterval(intervaloActualizacion);
                mostrarResultadoBatalla(data.batalla);
            }
            
            // Ocultar el overlay de carga una vez procesado todo
            if (esCargaInicial) {
                document.getElementById('loading-overlay').style.display = 'none';
            }
        }
    } catch (error) {
        console.error('Error al procesar actualizaciones:', error);
        document.getElementById('loading-overlay').style.display = 'none';
    }
}

// Función para configurar elementos de la interfaz
function configurarElementosUI() {
    // Configurar botón de salir
    const btnSalir = document.getElementById('btn-salir');
    if (btnSalir) {
        btnSalir.addEventListener('click', function() {
            if (confirm('¿Estás seguro que deseas abandonar la batalla?')) {
                // Si es participante y la batalla está activa, preguntar por rendición
                if ((ES_USUARIO1 || ES_USUARIO2) && battleData.estado === 'activa') {
                    if (confirm('¿Deseas rendirte? Esto dará la victoria a tu oponente.')) {
                        procesarRendicion();
                        return;
                    }
                }
                
                // Redirigir a la página principal (index.php)
                window.location.href = '../index.php';
            }
        });
        console.log('Evento de botón salir configurado');
    } else {
        console.error('No se encontró el botón de salir');
    }
}

// Función para procesar una rendición
function procesarRendicion() {
    // Mostrar overlay de carga durante el proceso
    document.getElementById('loading-overlay').style.display = 'flex';
    
    // Enviar solicitud de rendición
    fetch('../Controlador/batalla_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=rendicion&batalla_id=${BATALLA_ID}`
    })
    .then(response => response.json())
    .then(data => {
        // Ocultar overlay de carga
        document.getElementById('loading-overlay').style.display = 'none';
        
        if (data.success) {
            alert('Te has rendido. Redirigiendo a la página principal...');
            window.location.href = '../index.php';
        } else {
            alert('Error al procesar la rendición: ' + (data.message || 'Error desconocido'));
            console.error('Error en rendición:', data);
        }
    })
    .catch(error => {
        document.getElementById('loading-overlay').style.display = 'none';
        console.error('Error al procesar rendición:', error);
        alert('Error de conexión al intentar rendirse');
    });
}

// Función para cargar los datos de los Pokémon en la interfaz
function cargarDatosPokemon() {
    console.log('Cargando datos de Pokémon: ', battleData);
    
    // Determinar el equipo del jugador y el del oponente
    let equipoJugador, equipoOponente;
    
    if (ES_USUARIO1) {
        equipoJugador = battleData.equipo1;
        equipoOponente = battleData.equipo2;
    } else if (ES_USUARIO2) {
        equipoJugador = battleData.equipo2;
        equipoOponente = battleData.equipo1;
    } else if (MODO_ESPECTADOR) {
        // En modo espectador mostramos los datos completos de ambos equipos
        equipoJugador = battleData.equipo2;  // Por convenio mostramos el equipo 2 abajo
        equipoOponente = battleData.equipo1; // Y el equipo 1 arriba
    }
    
    if (!equipoJugador || !equipoOponente) {
        console.error('No se han podido determinar los equipos');
        return;
    }
    
    // Mostrar el Pokémon activo del jugador en el área del jugador
    const pokemonActivoJugadorId = ES_USUARIO1 ? battleData.pokemonActivo1 : 
                                 (ES_USUARIO2 ? battleData.pokemonActivo2 : battleData.pokemonActivo2);
    mostrarPokemonActivo(equipoJugador, pokemonActivoJugadorId, 'player-area');
    
    // Mostrar el Pokémon activo del oponente en el área del oponente
    const pokemonActivoOponenteId = ES_USUARIO1 ? battleData.pokemonActivo2 : 
                                  (ES_USUARIO2 ? battleData.pokemonActivo1 : battleData.pokemonActivo1);
    mostrarPokemonActivo(equipoOponente, pokemonActivoOponenteId, 'opponent-area');
    
    // Cargar el selector de equipo
    cargarSelectorEquipo(equipoJugador);
    
    // Cargar los movimientos del Pokémon activo
    cargarMovimientosPokemon(equipoJugador, pokemonActivoJugadorId);
    
    // Ocultar el overlay de carga una vez que todo esté cargado
    document.getElementById('loading-overlay').style.display = 'none';
}

// Función para mostrar el Pokémon activo en el área correspondiente
function mostrarPokemonActivo(equipo, pokemonActivoId, areaSelector) {
    const areaElement = document.querySelector(`.${areaSelector}`);
    if (!areaElement) {
        console.error(`No se encontró el elemento con selector ${areaSelector}`);
        return;
    }
    
    // Si no hay ID de Pokémon activo, mostrar mensaje de selección
    if (!pokemonActivoId) {
        areaElement.innerHTML = `
            <div class="pokemon-pending">
                <div class="pokemon-pending-message">Esperando selección de Pokémon...</div>
            </div>
        `;
        return;
    }
    
    // Buscar el Pokémon activo en el equipo
    const pokemonActivo = equipo.find(p => p.id_equip_pokemon == pokemonActivoId);
    if (!pokemonActivo) {
        console.error(`No se encontró un Pokémon con ID ${pokemonActivoId} en el equipo`);
        return;
    }
    
    // Calcular el porcentaje de HP
    const hpPorcentaje = (pokemonActivo.hp_actual / pokemonActivo.hp_max) * 100;
    
    // Determinar el color de la barra de HP según el porcentaje
    let hpColor = '#78C850'; // Verde para HP alto
    if (hpPorcentaje < 50) {
        hpColor = '#F8D030'; // Amarillo para HP medio
    }
    if (hpPorcentaje < 25) {
        hpColor = '#F08030'; // Rojo para HP bajo
    }
    
    // Preparar la vista según si es área del jugador o del oponente
    const esAreaJugador = areaSelector === 'player-area';
    const spriteClass = esAreaJugador ? 'pokemon-sprite back' : 'pokemon-sprite front';
    
    // Crear el HTML del Pokémon activo
    areaElement.innerHTML = `
        <div class="pokemon-active">
            <img src="${pokemonActivo.sprite}" alt="${pokemonActivo.malnom}" class="${spriteClass}">
            <div class="pokemon-info">
                <div class="pokemon-name">${pokemonActivo.malnom}</div>
                <div class="pokemon-level">Nv. ${pokemonActivo.nivell}</div>
                <div class="pokemon-hp-bar">
                    <div class="hp-bar" style="width: ${hpPorcentaje}%; background-color: ${hpColor};"></div>
                </div>
                <div class="pokemon-hp-text">${pokemonActivo.hp_actual}/${pokemonActivo.hp_max} PS</div>
            </div>
        </div>
    `;
}

// Función para cargar el selector de equipo
function cargarSelectorEquipo(equipoJugador) {
    const selectorEquipo = document.querySelector('.team-selector');
    if (!selectorEquipo) {
        console.error('No se encontró el selector de equipo');
        return;
    }
    
    // Limpiar el selector
    selectorEquipo.innerHTML = '';
    
    // Si estamos en modo espectador o no es nuestro turno, deshabilitar interacción
    const puedeSeleccionar = !MODO_ESPECTADOR && battleData.esMiTurno;
    
    // Añadir cada Pokémon al selector
    equipoJugador.forEach((pokemon, index) => {
        const pokemonElement = document.createElement('div');
        
        // Determinar si este Pokémon es el activo
        const esActivo = (ES_USUARIO1 && pokemon.id_equip_pokemon == battleData.pokemonActivo1) || 
                         (ES_USUARIO2 && pokemon.id_equip_pokemon == battleData.pokemonActivo2);
        
        // Determinar si el Pokémon está debilitado (sin HP)
        const estaDebilitado = pokemon.hp_actual <= 0;
        
        // Asignar clases según el estado
        pokemonElement.className = `team-pokemon${esActivo ? ' active' : ''}${estaDebilitado ? ' fainted' : ''}`;
        pokemonElement.dataset.id = pokemon.id_equip_pokemon;
        pokemonElement.dataset.index = index;
        
        // Calcular el porcentaje de HP
        const hpPorcentaje = (pokemon.hp_actual / pokemon.hp_max) * 100;
        
        // Determinar el color de la barra de HP
        let hpColor = '#78C850'; // Verde para HP alto
        if (hpPorcentaje < 50) {
            hpColor = '#F8D030'; // Amarillo para HP medio
        }
        if (hpPorcentaje < 25) {
            hpColor = '#F08030'; // Rojo para HP bajo
        }
        
        // Crear el contenido HTML
        pokemonElement.innerHTML = `
            <img src="${pokemon.sprite}" alt="${pokemon.malnom}">
            <div class="pokemon-mini-info">
                <span class="mini-name">${pokemon.malnom}</span>
                <div class="mini-hp-bar">
                    <div class="hp-bar-inner" style="width: ${hpPorcentaje}%; background-color: ${hpColor};"></div>
                </div>
                <span class="mini-hp">${pokemon.hp_actual}/${pokemon.hp_max}</span>
            </div>
        `;
        
        // Añadir evento click solo si puede seleccionar y el Pokémon no está debilitado
        if (puedeSeleccionar && !estaDebilitado && !esActivo) {
            pokemonElement.addEventListener('click', () => cambiarPokemon(pokemon.id_equip_pokemon));
        }
        
        // Añadir al selector
        selectorEquipo.appendChild(pokemonElement);
    });
}

// Función para cargar los movimientos del Pokémon activo
function cargarMovimientosPokemon(equipoJugador, pokemonActivoId) {
    const movimientosContainer = document.querySelector('.moves-container');
    if (!movimientosContainer) {
        console.error('No se encontró el contenedor de movimientos');
        return;
    }
    
    // Limpiar el contenedor
    movimientosContainer.innerHTML = '';
    
    // Si no hay ID de Pokémon activo, mostrar mensaje
    if (!pokemonActivoId) {
        movimientosContainer.innerHTML = '<div class="no-moves">Selecciona un Pokémon primero</div>';
        return;
    }
    
    // Buscar el Pokémon activo en el equipo
    const pokemonActivo = equipoJugador.find(p => p.id_equip_pokemon == pokemonActivoId);
    if (!pokemonActivo) {
        console.error(`No se encontró un Pokémon con ID ${pokemonActivoId} en el equipo`);
        return;
    }
    
    // Si estamos en modo espectador o no es nuestro turno, deshabilitar interacción
    const puedeSeleccionar = !MODO_ESPECTADOR && battleData.esMiTurno;
    
    // Verificar si el Pokémon tiene movimientos
    if (!pokemonActivo.movimientos || pokemonActivo.movimientos.length === 0) {
        movimientosContainer.innerHTML = '<div class="no-moves">Este Pokémon no tiene movimientos</div>';
        return;
    }
    
    // Añadir cada movimiento al contenedor
    pokemonActivo.movimientos.forEach(movimiento => {
        const movimientoElement = document.createElement('button');
        movimientoElement.className = `btn-action btn-move tipo-${movimiento.tipus_moviment.toLowerCase()}`;
        movimientoElement.dataset.id = movimiento.id_equip_moviment;
        
        // Crear el contenido HTML
        movimientoElement.innerHTML = `
            <span class="move-name">${movimiento.nom_moviment}</span>
            <span class="move-type">${movimiento.tipus_moviment}</span>
            <span class="move-pp">${movimiento.pp_maxims}/${movimiento.pp_maxims} PP</span>
        `;
        
        // Añadir evento click solo si puede seleccionar
        if (puedeSeleccionar) {
            movimientoElement.addEventListener('click', () => usarMovimiento(movimiento.id_equip_moviment));
        } else {
            movimientoElement.disabled = true;
        }
        
        // Añadir al contenedor
        movimientosContainer.appendChild(movimientoElement);
    });
}

// Función para iniciar el sistema de combate una vez que el DOM está listo
function iniciarSistemaCombate() {
    console.log('DOM cargado, iniciando sistema de combate');
    
    try {
        // Primera actualización inmediata para obtener datos actualizados
        console.log('Realizando primera actualización del combate');
        actualizarCombate(true);  // true indica carga inicial
        
        // Configurar eventos y otros elementos
        console.log('Configurando elementos de UI');
        configurarElementosUI();
        
        // Verificar si necesitamos mostrar el selector de Pokémon inicial
        const batallaActiva = battleData.estado === 'activa';
        const pokemonActivo = battleData.esUsuario1 ? 
            Boolean(battleData.pokemonActivo1) : 
            Boolean(battleData.pokemonActivo2);
        
        // Si la batalla está activa pero no hay Pokémon activo seleccionado, mostrar selector
        if (batallaActiva && !pokemonActivo && !MODO_ESPECTADOR) {
            console.log('Mostrando selector de Pokémon inicial');
            mostrarSelectorPokemonInicial();
        } else {
            console.log('No es necesario mostrar selector de Pokémon inicial');
        }
        
        // Configurar intervalo de actualización (cada 3 segundos)
        console.log('Configurando intervalo de actualización');
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
        
        console.log('Sistema de combate inicializado correctamente');
    } catch (error) {
        console.error('Error durante la inicialización del combate:', error);
        // Ocultar el overlay de carga en caso de error para no dejar la pantalla bloqueada
        document.getElementById('loading-overlay').style.display = 'none';
        mostrarMensaje('Error al inicializar el combate: ' + error.message, 'error');
    }
}

// Función para actualizar la interfaz de combate
function actualizarCombate(esCargaInicial = false) {
    try {
        // Construir URL para la API
        let url = '../Controlador/batalla_api.php?action=estado_batalla&id_batalla=' + BATALLA_ID;
        
        // Añadir timestamp para control de cambios
        if (lastTimestamp > 0) {
            url += '&timestamp=' + lastTimestamp;
        }
        
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
                    // Actualizar timestamp para próximas consultas
                    if (data.timestamp) {
                        lastTimestamp = data.timestamp;
                    }
                    
                    // Procesar los datos recibidos
                    procesarActualizaciones(data, esCargaInicial);
                } else {
                    console.error('Error al obtener actualizaciones:', data.message);
                    // Ocultar overlay de carga en caso de error
                    document.getElementById('loading-overlay').style.display = 'none';
                    mostrarMensaje('Error: ' + (data.message || 'Error al actualizar'), 'error');
                }
            })
            .catch(error => {
                console.error('Error en la solicitud de actualizaciones:', error);
                // Asegurar que el overlay de carga se oculte
                document.getElementById('loading-overlay').style.display = 'none';
                // Mostrar mensaje de error al usuario
                mostrarMensaje('Error de conexión. Intentando reconectar...', 'error');
            });
    } catch (error) {
        console.error('Error general en actualizarCombate:', error);
        // Si hay un error grave, intentamos volver a habilitar la UI
        document.getElementById('loading-overlay').style.display = 'none';
        mostrarMensaje('Error al actualizar el combate: ' + error.message, 'error');
    }
}

// Procesar las actualizaciones recibidas
function procesarActualizaciones(data, esCargaInicial = false) {
    console.log("Procesando actualizaciones:", data);
    
    // Si no hay cambios y no es la carga inicial, no hacer nada
    if (data.hay_cambios === false && !esCargaInicial) {
        return;
    }
    
    try {
        // Actualizar estado de la batalla
        if (data.batalla) {
            // Si es la primera carga, establecer los Pokémon activos
            if (esCargaInicial) {
                if (data.batalla.pokemon_actiu_1) {
                    battleData.pokemonActivo1 = data.batalla.pokemon_actiu_1;
                }
                if (data.batalla.pokemon_actiu_2) {
                    battleData.pokemonActivo2 = data.batalla.pokemon_actiu_2;
                }
                
                // Cargar datos de Pokémon solo después de establecer los activos
                cargarDatosPokemon();
            }

            actualizarEstadoBatalla(data.batalla);
            
            // Actualizar Pokémon activos si han cambiado
            if (data.batalla.pokemon_actiu_1 && 
                (!battleData.pokemonActivo1 || battleData.pokemonActivo1 !== data.batalla.pokemon_actiu_1)) {
                battleData.pokemonActivo1 = data.batalla.pokemon_actiu_1;
                actualizarPokemon(data.batalla.pokemon_actiu_1, ES_USUARIO1 ? 'player' : 'opponent');
            }
            
            if (data.batalla.pokemon_actiu_2 && 
                (!battleData.pokemonActivo2 || battleData.pokemonActivo2 !== data.batalla.pokemon_actiu_2)) {
                battleData.pokemonActivo2 = data.batalla.pokemon_actiu_2;
                actualizarPokemon(data.batalla.pokemon_actiu_2, ES_USUARIO2 ? 'player' : 'opponent');
            }
            
            // Mostrar nuevas acciones en el historial
            if (data.batalla.ultimesAccions && data.batalla.ultimesAccions.length > 0) {
                mostrarAccionesHistorial(data.batalla.ultimesAccions);
            }
            
            // Si la batalla ha terminado, detener actualizaciones
            if (data.batalla.batalla_finalitzada) {
                clearInterval(intervaloActualizacion);
                mostrarResultadoBatalla(data.batalla);
            }
            
            // Ocultar el overlay de carga una vez procesado todo
            if (esCargaInicial) {
                document.getElementById('loading-overlay').style.display = 'none';
            }
        }
    } catch (error) {
        console.error('Error al procesar actualizaciones:', error);
        document.getElementById('loading-overlay').style.display = 'none';
    }
}

// Función para configurar elementos de la interfaz
function configurarElementosUI() {
    // Configurar botón de salir
    const btnSalir = document.getElementById('btn-salir');
    if (btnSalir) {
        btnSalir.addEventListener('click', function() {
            if (confirm('¿Estás seguro que deseas abandonar la batalla?')) {
                // Si es participante y la batalla está activa, preguntar por rendición
                if ((ES_USUARIO1 || ES_USUARIO2) && battleData.estado === 'activa') {
                    if (confirm('¿Deseas rendirte? Esto dará la victoria a tu oponente.')) {
                        procesarRendicion();
                        return;
                    }
                }
                
                // Redirigir a la página principal (index.php)
                window.location.href = '../index.php';
            }
        });
        console.log('Evento de botón salir configurado');
    } else {
        console.error('No se encontró el botón de salir');
    }
}

// Función para procesar una rendición
function procesarRendicion() {
    // Mostrar overlay de carga durante el proceso
    document.getElementById('loading-overlay').style.display = 'flex';
    
    // Enviar solicitud de rendición
    fetch('../Controlador/batalla_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=rendicion&batalla_id=${BATALLA_ID}`
    })
    .then(response => response.json())
    .then(data => {
        // Ocultar overlay de carga
        document.getElementById('loading-overlay').style.display = 'none';
        
        if (data.success) {
            alert('Te has rendido. Redirigiendo a la página principal...');
            window.location.href = '../index.php';
        } else {
            alert('Error al procesar la rendición: ' + (data.message || 'Error desconocido'));
            console.error('Error en rendición:', data);
        }
    })
    .catch(error => {
        document.getElementById('loading-overlay').style.display = 'none';
        console.error('Error al procesar rendición:', error);
        alert('Error de conexión al intentar rendirse');
    });
}

// Función para cargar los datos de los Pokémon en la interfaz
function cargarDatosPokemon() {
    console.log('Cargando datos de Pokémon: ', battleData);
    
    // Determinar el equipo del jugador y el del oponente
    let equipoJugador, equipoOponente;
    
    if (ES_USUARIO1) {
        equipoJugador = battleData.equipo1;
        equipoOponente = battleData.equipo2;
    } else if (ES_USUARIO2) {
        equipoJugador = battleData.equipo2;
        equipoOponente = battleData.equipo1;
    } else if (MODO_ESPECTADOR) {
        // En modo espectador mostramos los datos completos de ambos equipos
        equipoJugador = battleData.equipo2;  // Por convenio mostramos el equipo 2 abajo
        equipoOponente = battleData.equipo1; // Y el equipo 1 arriba
    }
    
    if (!equipoJugador || !equipoOponente) {
        console.error('No se han podido determinar los equipos');
        return;
    }
    
    // Mostrar el Pokémon activo del jugador en el área del jugador
    const pokemonActivoJugadorId = ES_USUARIO1 ? battleData.pokemonActivo1 : 
                                 (ES_USUARIO2 ? battleData.pokemonActivo2 : battleData.pokemonActivo2);
    mostrarPokemonActivo(equipoJugador, pokemonActivoJugadorId, 'player-area');
    
    // Mostrar el Pokémon activo del oponente en el área del oponente
    const pokemonActivoOponenteId = ES_USUARIO1 ? battleData.pokemonActivo2 : 
                                  (ES_USUARIO2 ? battleData.pokemonActivo1 : battleData.pokemonActivo1);
    mostrarPokemonActivo(equipoOponente, pokemonActivoOponenteId, 'opponent-area');
    
    // Cargar el selector de equipo
    cargarSelectorEquipo(equipoJugador);
    
    // Cargar los movimientos del Pokémon activo
    cargarMovimientosPokemon(equipoJugador, pokemonActivoJugadorId);
    
    // Ocultar el overlay de carga una vez que todo esté cargado
    document.getElementById('loading-overlay').style.display = 'none';
}

// Función para mostrar el Pokémon activo en el área correspondiente
function mostrarPokemonActivo(equipo, pokemonActivoId, areaSelector) {
    const areaElement = document.querySelector(`.${areaSelector}`);
    if (!areaElement) {
        console.error(`No se encontró el elemento con selector ${areaSelector}`);
        return;
    }
    
    // Si no hay ID de Pokémon activo, mostrar mensaje de selección
    if (!pokemonActivoId) {
        areaElement.innerHTML = `
            <div class="pokemon-pending">
                <div class="pokemon-pending-message">Esperando selección de Pokémon...</div>
            </div>
        `;
        return;
    }
    
    // Buscar el Pokémon activo en el equipo
    const pokemonActivo = equipo.find(p => p.id_equip_pokemon == pokemonActivoId);
    if (!pokemonActivo) {
        console.error(`No se encontró un Pokémon con ID ${pokemonActivoId} en el equipo`);
        return;
    }
    
    // Calcular el porcentaje de HP
    const hpPorcentaje = (pokemonActivo.hp_actual / pokemonActivo.hp_max) * 100;
    
    // Determinar el color de la barra de HP según el porcentaje
    let hpColor = '#78C850'; // Verde para HP alto
    if (hpPorcentaje < 50) {
        hpColor = '#F8D030'; // Amarillo para HP medio
    }
    if (hpPorcentaje < 25) {
        hpColor = '#F08030'; // Rojo para HP bajo
    }
    
    // Preparar la vista según si es área del jugador o del oponente
    const esAreaJugador = areaSelector === 'player-area';
    const spriteClass = esAreaJugador ? 'pokemon-sprite back' : 'pokemon-sprite front';
    
    // Crear el HTML del Pokémon activo
    areaElement.innerHTML = `
        <div class="pokemon-active">
            <img src="${pokemonActivo.sprite}" alt="${pokemonActivo.malnom}" class="${spriteClass}">
            <div class="pokemon-info">
                <div class="pokemon-name">${pokemonActivo.malnom}</div>
                <div class="pokemon-level">Nv. ${pokemonActivo.nivell}</div>
                <div class="pokemon-hp-bar">
                    <div class="hp-bar" style="width: ${hpPorcentaje}%; background-color: ${hpColor};"></div>
                </div>
                <div class="pokemon-hp-text">${pokemonActivo.hp_actual}/${pokemonActivo.hp_max} PS</div>
            </div>
        </div>
    `;
}

// Función para cargar el selector de equipo
function cargarSelectorEquipo(equipoJugador) {
    const selectorEquipo = document.querySelector('.team-selector');
    if (!selectorEquipo) {
        console.error('No se encontró el selector de equipo');
        return;
    }
    
    // Limpiar el selector
    selectorEquipo.innerHTML = '';
    
    // Si estamos en modo espectador o no es nuestro turno, deshabilitar interacción
    const puedeSeleccionar = !MODO_ESPECTADOR && battleData.esMiTurno;
    
    // Añadir cada Pokémon al selector
    equipoJugador.forEach((pokemon, index) => {
        const pokemonElement = document.createElement('div');
        
        // Determinar si este Pokémon es el activo
        const esActivo = (ES_USUARIO1 && pokemon.id_equip_pokemon == battleData.pokemonActivo1) || 
                         (ES_USUARIO2 && pokemon.id_equip_pokemon == battleData.pokemonActivo2);
        
        // Determinar si el Pokémon está debilitado (sin HP)
        const estaDebilitado = pokemon.hp_actual <= 0;
        
        // Asignar clases según el estado
        pokemonElement.className = `team-pokemon${esActivo ? ' active' : ''}${estaDebilitado ? ' fainted' : ''}`;
        pokemonElement.dataset.id = pokemon.id_equip_pokemon;
        pokemonElement.dataset.index = index;
        
        // Calcular el porcentaje de HP
        const hpPorcentaje = (pokemon.hp_actual / pokemon.hp_max) * 100;
        
        // Determinar el color de la barra de HP
        let hpColor = '#78C850'; // Verde para HP alto
        if (hpPorcentaje < 50) {
            hpColor = '#F8D030'; // Amarillo para HP medio
        }
        if (hpPorcentaje < 25) {
            hpColor = '#F08030'; // Rojo para HP bajo
        }
        
        // Crear el contenido HTML
        pokemonElement.innerHTML = `
            <img src="${pokemon.sprite}" alt="${pokemon.malnom}">
            <div class="pokemon-mini-info">
                <span class="mini-name">${pokemon.malnom}</span>
                <div class="mini-hp-bar">
                    <div class="hp-bar-inner" style="width: ${hpPorcentaje}%; background-color: ${hpColor};"></div>
                </div>
                <span class="mini-hp">${pokemon.hp_actual}/${pokemon.hp_max}</span>
            </div>
        `;
        
        // Añadir evento click solo si puede seleccionar y el Pokémon no está debilitado
        if (puedeSeleccionar && !estaDebilitado && !esActivo) {
            pokemonElement.addEventListener('click', () => cambiarPokemon(pokemon.id_equip_pokemon));
        }
        
        // Añadir al selector
        selectorEquipo.appendChild(pokemonElement);
    });
}

// Función para cargar los movimientos del Pokémon activo
function cargarMovimientosPokemon(equipoJugador, pokemonActivoId) {
    const movimientosContainer = document.querySelector('.moves-container');
    if (!movimientosContainer) {
        console.error('No se encontró el contenedor de movimientos');
        return;
    }
    
    // Limpiar el contenedor
    movimientosContainer.innerHTML = '';
    
    // Si no hay ID de Pokémon activo, mostrar mensaje
    if (!pokemonActivoId) {
        movimientosContainer.innerHTML = '<div class="no-moves">Selecciona un Pokémon primero</div>';
        return;
    }
    
    // Buscar el Pokémon activo en el equipo
    const pokemonActivo = equipoJugador.find(p => p.id_equip_pokemon == pokemonActivoId);
    if (!pokemonActivo) {
        console.error(`No se encontró un Pokémon con ID ${pokemonActivoId} en el equipo`);
        return;
    }
    
    // Si estamos en modo espectador o no es nuestro turno, deshabilitar interacción
    const puedeSeleccionar = !MODO_ESPECTADOR && battleData.esMiTurno;
    
    // Verificar si el Pokémon tiene movimientos
    if (!pokemonActivo.movimientos || pokemonActivo.movimientos.length === 0) {
        movimientosContainer.innerHTML = '<div class="no-moves">Este Pokémon no tiene movimientos</div>';
        return;
    }
    
    // Añadir cada movimiento al contenedor
    pokemonActivo.movimientos.forEach(movimiento => {
        const movimientoElement = document.createElement('button');
        movimientoElement.className = `btn-action btn-move tipo-${movimiento.tipus_moviment.toLowerCase()}`;
        movimientoElement.dataset.id = movimiento.id_equip_moviment;
        
        // Crear el contenido HTML
        movimientoElement.innerHTML = `
            <span class="move-name">${movimiento.nom_moviment}</span>
            <span class="move-type">${movimiento.tipus_moviment}</span>
            <span class="move-pp">${movimiento.pp_maxims}/${movimiento.pp_maxims} PP</span>
        `;
        
        // Añadir evento click solo si puede seleccionar
        if (puedeSeleccionar) {
            movimientoElement.addEventListener('click', () => usarMovimiento(movimiento.id_equip_moviment));
        } else {
            movimientoElement.disabled = true;
        }
        
        // Añadir al contenedor
        movimientosContainer.appendChild(movimientoElement);
    });
}

// Función para iniciar el sistema de combate una vez que el DOM está listo
function iniciarSistemaCombate() {
    console.log('DOM cargado, iniciando sistema de combate');
    
    try {
        // Primera actualización inmediata para obtener datos actualizados
        console.log('Realizando primera actualización del combate');
        actualizarCombate(true);  // true indica carga inicial
        
        // Configurar eventos y otros elementos
        console.log('Configurando elementos de UI');
        configurarElementosUI();
        
        // Verificar si necesitamos mostrar el selector de Pokémon inicial
        const batallaActiva = battleData.estado === 'activa';
        const pokemonActivo = battleData.esUsuario1 ? 
            Boolean(battleData.pokemonActivo1) : 
            Boolean(battleData.pokemonActivo2);
        
        // Si la batalla está activa pero no hay Pokémon activo seleccionado, mostrar selector
        if (batallaActiva && !pokemonActivo && !MODO_ESPECTADOR) {
            console.log('Mostrando selector de Pokémon inicial');
            mostrarSelectorPokemonInicial();
        } else {
            console.log('No es necesario mostrar selector de Pokémon inicial');
        }
        
        // Configurar intervalo de actualización (cada 3 segundos)
        console.log('Configurando intervalo de actualización');
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
        
        console.log('Sistema de combate inicializado correctamente');
    } catch (error) {
        console.error('Error durante la inicialización del combate:', error);
        // Ocultar el overlay de carga en caso de error para no dejar la pantalla bloqueada
        document.getElementById('loading-overlay').style.display = 'none';
        mostrarMensaje('Error al inicializar el combate: ' + error.message, 'error');
    }
}

// Función para actualizar la interfaz de combate
function actualizarCombate(esCargaInicial = false) {
    try {
        // Construir URL para la API
        let url = '../Controlador/batalla_api.php?action=estado_batalla&id_batalla=' + BATALLA_ID;
        
        // Añadir timestamp para control de cambios
        if (lastTimestamp > 0) {
            url += '&timestamp=' + lastTimestamp;
        }
        
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
                    // Actualizar timestamp para próximas consultas
                    if (data.timestamp) {
                        lastTimestamp = data.timestamp;
                    }
                    
                    // Procesar los datos recibidos
                    procesarActualizaciones(data, esCargaInicial);
                } else {
                    console.error('Error al obtener actualizaciones:', data.message);
                    // Ocultar overlay de carga en caso de error
                    document.getElementById('loading-overlay').style.display = 'none';
                    mostrarMensaje('Error: ' + (data.message || 'Error al actualizar'), 'error');
                }
            })
            .catch(error => {
                console.error('Error en la solicitud de actualizaciones:', error);
                // Asegurar que el overlay de carga se oculte
                document.getElementById('loading-overlay').style.display = 'none';
                // Mostrar mensaje de error al usuario
                mostrarMensaje('Error de conexión. Intentando reconectar...', 'error');
            });
    } catch (error) {
        console.error('Error general en actualizarCombate:', error);
        // Si hay un error grave, intentamos volver a habilitar la UI
        document.getElementById('loading-overlay').style.display = 'none';
        mostrarMensaje('Error al actualizar el combate: ' + error.message, 'error');
    }
}

// Procesar las actualizaciones recibidas
function procesarActualizaciones(data, esCargaInicial = false) {
    console.log("Procesando actualizaciones:", data);
    
    // Si no hay cambios y no es la carga inicial, no hacer nada
    if (data.hay_cambios === false && !esCargaInicial) {
        return;
    }
    
    try {
        // Actualizar estado de la batalla
        if (data.batalla) {
            // Si es la primera carga, establecer los Pokémon activos
            if (esCargaInicial) {
                if (data.batalla.pokemon_actiu_1) {
                    battleData.pokemonActivo1 = data.batalla.pokemon_actiu_1;
                }
                if (data.batalla.pokemon_actiu_2) {
                    battleData.pokemonActivo2 = data.batalla.pokemon_actiu_2;
                }
                
                // Cargar datos de Pokémon solo después de establecer los activos
                cargarDatosPokemon();
            }

            actualizarEstadoBatalla(data.batalla);
            
            // Actualizar Pokémon activos si han cambiado
            if (data.batalla.pokemon_actiu_1 && 
                (!battleData.pokemonActivo1 || battleData.pokemonActivo1 !== data.batalla.pokemon_actiu_1)) {
                battleData.pokemonActivo1 = data.batalla.pokemon_actiu_1;
                actualizarPokemon(data.batalla.pokemon_actiu_1, ES_USUARIO1 ? 'player' : 'opponent');
            }
            
            if (data.batalla.pokemon_actiu_2 && 
                (!battleData.pokemonActivo2 || battleData.pokemonActivo2 !== data.batalla.pokemon_actiu_2)) {
                battleData.pokemonActivo2 = data.batalla.pokemon_actiu_2;
                actualizarPokemon(data.batalla.pokemon_actiu_2, ES_USUARIO2 ? 'player' : 'opponent');
            }
            
            // Mostrar nuevas acciones en el historial
            if (data.batalla.ultimesAccions && data.batalla.ultimesAccions.length > 0) {
                mostrarAccionesHistorial(data.batalla.ultimesAccions);
            }
            
            // Si la batalla ha terminado, detener actualizaciones
            if (data.batalla.batalla_finalitzada) {
                clearInterval(intervaloActualizacion);
                mostrarResultadoBatalla(data.batalla);
            }
            
            // Ocultar el overlay de carga una vez procesado todo
            if (esCargaInicial) {
                document.getElementById('loading-overlay').style.display = 'none';
            }
        }
    } catch (error) {
        console.error('Error al procesar actualizaciones:', error);
        document.getElementById('loading-overlay').style.display = 'none';
    }
}

// Función para configurar elementos de la interfaz
function configurarElementosUI() {
    // Configurar botón de salir
    const btnSalir = document.getElementById('btn-salir');
    if (btnSalir) {
        btnSalir.addEventListener('click', function() {
            if (confirm('¿Estás seguro que deseas abandonar la batalla?')) {
                // Si es participante y la batalla está activa, preguntar por rendición
                if ((ES_USUARIO1 || ES_USUARIO2) && battleData.estado === 'activa') {
                    if (confirm('¿Deseas rendirte? Esto dará la victoria a tu oponente.')) {
                        procesarRendicion();
                        return;
                    }
                }
                
                // Redirigir a la página principal (index.php)
                window.location.href = '../index.php';
            }
        });
        console.log('Evento de botón salir configurado');
    } else {
        console.error('No se encontró el botón de salir');
    }
}

// Función para procesar una rendición
function procesarRendicion() {
    // Mostrar overlay de carga durante el proceso
    document.getElementById('loading-overlay').style.display = 'flex';
    
    // Enviar solicitud de rendición
    fetch('../Controlador/batalla_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=rendicion&batalla_id=${BATALLA_ID}`
    })
    .then(response => response.json())
    .then(data => {
        // Ocultar overlay de carga
        document.getElementById('loading-overlay').style.display = 'none';
        
        if (data.success) {
            alert('Te has rendido. Redirigiendo a la página principal...');
            window.location.href = '../index.php';
        } else {
            alert('Error al procesar la rendición: ' + (data.message || 'Error desconocido'));
            console.error('Error en rendición:', data);
        }
    })
    .catch(error => {
        document.getElementById('loading-overlay').style.display = 'none';
        console.error('Error al procesar rendición:', error);
        alert('Error de conexión al intentar rendirse');
    });
}

// Función para cargar los datos de los Pokémon en la interfaz
function cargarDatosPokemon() {
    console.log('Cargando datos de Pokémon: ', battleData);
    
    // Determinar el equipo del jugador y el del oponente
    let equipoJugador, equipoOponente;
    
    if (ES_USUARIO1) {
        equipoJugador = battleData.equipo1;
        equipoOponente = battleData.equipo2;
    } else if (ES_USUARIO2) {
        equipoJugador = battleData.equipo2;
        equipoOponente = battleData.equipo1;
    } else if (MODO_ESPECTADOR) {
        // En modo espectador mostramos los datos completos de ambos equipos
        equipoJugador = battleData.equipo2;  // Por convenio mostramos el equipo 2 abajo
        equipoOponente = battleData.equipo1; // Y el equipo 1 arriba
    }
    
    if (!equipoJugador || !equipoOponente) {
        console.error('No se han podido determinar los equipos');
        return;
    }
    
    // Mostrar el Pokémon activo del jugador en el área del jugador
    const pokemonActivoJugadorId = ES_USUARIO1 ? battleData.pokemonActivo1 : 
                                 (ES_USUARIO2 ? battleData.pokemonActivo2 : battleData.pokemonActivo2);
    mostrarPokemonActivo(equipoJugador, pokemonActivoJugadorId, 'player-area');
    
    // Mostrar el Pokémon activo del oponente en el área del oponente
    const pokemonActivoOponenteId = ES_USUARIO1 ? battleData.pokemonActivo2 : 
                                  (ES_USUARIO2 ? battleData.pokemonActivo1 : battleData.pokemonActivo1);
    mostrarPokemonActivo(equipoOponente, pokemonActivoOponenteId, 'opponent-area');
    
    // Cargar el selector de equipo
    cargarSelectorEquipo(equipoJugador);
    
    // Cargar los movimientos del Pokémon activo
    cargarMovimientosPokemon(equipoJugador, pokemonActivoJugadorId);
    
    // Ocultar el overlay de carga una vez que todo esté cargado
    document.getElementById('loading-overlay').style.display = 'none';
}

// Función para mostrar el Pokémon activo en el área correspondiente
function mostrarPokemonActivo(equipo, pokemonActivoId, areaSelector) {
    const areaElement = document.querySelector(`.${areaSelector}`);
    if (!areaElement) {
        console.error(`No se encontró el elemento con selector ${areaSelector}`);
        return;
    }
    
    // Si no hay ID de Pokémon activo, mostrar mensaje de selección
    if (!pokemonActivoId) {
        areaElement.innerHTML = `
            <div class="pokemon-pending">
                <div class="pokemon-pending-message">Esperando selección de Pokémon...</div>
            </div>
        `;
        return;
    }
    
    // Buscar el Pokémon activo en el equipo
    const pokemonActivo = equipo.find(p => p.id_equip_pokemon == pokemonActivoId);
    if (!pokemonActivo) {
        console.error(`No se encontró un Pokémon con ID ${pokemonActivoId} en el equipo`);
        return;
    }
    
    // Calcular el porcentaje de HP
    const hpPorcentaje = (pokemonActivo.hp_actual / pokemonActivo.hp_max) * 100;
    
    // Determinar el color de la barra de HP según el porcentaje
    let hpColor = '#78C850'; // Verde para HP alto
    if (hpPorcentaje < 50) {
        hpColor = '#F8D030'; // Amarillo para HP medio
    }
    if (hpPorcentaje < 25) {
        hpColor = '#F08030'; // Rojo para HP bajo
    }
    
    // Preparar la vista según si es área del jugador o del oponente
    const esAreaJugador = areaSelector === 'player-area';
    const spriteClass = esAreaJugador ? 'pokemon-sprite back' : 'pokemon-sprite front';
    
    // Crear el HTML del Pokémon activo
    areaElement.innerHTML = `
        <div class="pokemon-active">
            <img src="${pokemonActivo.sprite}" alt="${pokemonActivo.malnom}" class="${spriteClass}">
            <div class="pokemon-info">
                <div class="pokemon-name">${pokemonActivo.malnom}</div>
                <div class="pokemon-level">Nv. ${pokemonActivo.nivell}</div>
                <div class="pokemon-hp-bar">
                    <div class="hp-bar" style="width: ${hpPorcentaje}%; background-color: ${hpColor};"></div>
                </div>
                <div class="pokemon-hp-text">${pokemonActivo.hp_actual}/${pokemonActivo.hp_max} PS</div>
            </div>
        </div>
    `;
}

// Función para mostrar el selector de Pokémon inicial
function mostrarSelectorPokemonInicial() {
    // Determinar el equipo del jugador
    let equipoJugador;
    
    if (ES_USUARIO1) {
        equipoJugador = battleData.equipo1;
    } else if (ES_USUARIO2) {
        equipoJugador = battleData.equipo2;
    } else {
        console.error('No se puede mostrar selector de Pokémon inicial en modo espectador');
        return;
    }
    
    if (!equipoJugador || equipoJugador.length === 0) {
        console.error('No hay Pokémon disponibles para seleccionar');
        return;
    }
    
    // Crear el modal para seleccionar el Pokémon inicial
    const modalOverlay = document.createElement('div');
    modalOverlay.className = 'modal-overlay';
    modalOverlay.id = 'pokemon-inicial-modal';
    
    const modalContent = document.createElement('div');
    modalContent.className = 'modal-content';
    
    const titulo = document.createElement('h2');
    titulo.textContent = '¡Selecciona tu Pokémon inicial!';
    
    const mensaje = document.createElement('p');
    mensaje.textContent = 'Elige el Pokémon con el que iniciarás el combate:';
    
    const pokemonList = document.createElement('div');
    pokemonList.className = 'pokemon-initial-selector';
    
    // Añadir cada Pokémon al selector
    equipoJugador.forEach(pokemon => {
        if (pokemon.hp_actual > 0) {  // Solo mostrar Pokémon con HP
            const pokemonItem = document.createElement('div');
            pokemonItem.className = 'pokemon-initial-item';
            pokemonItem.dataset.id = pokemon.id_equip_pokemon;
            
            pokemonItem.innerHTML = `
                <img src="${pokemon.sprite}" alt="${pokemon.malnom}" class="pokemon-initial-sprite">
                <div class="pokemon-initial-info">
                    <span class="pokemon-initial-name">${pokemon.malnom}</span>
                    <span class="pokemon-initial-level">Nv. ${pokemon.nivell}</span>
                    <div class="pokemon-initial-hp-bar">
                        <div class="hp-bar-inner" style="width: ${(pokemon.hp_actual / pokemon.hp_max) * 100}%;"></div>
                    </div>
                    <span class="pokemon-initial-hp">${pokemon.hp_actual}/${pokemon.hp_max} PS</span>
                </div>
            `;
            
            // Añadir evento para seleccionar este Pokémon
            pokemonItem.addEventListener('click', () => {
                seleccionarPokemonInicial(pokemon.id_equip_pokemon);
            });
            
            pokemonList.appendChild(pokemonItem);
        }
    });
    
    // Ensamblar el modal
    modalContent.appendChild(titulo);
    modalContent.appendChild(mensaje);
    modalContent.appendChild(pokemonList);
    modalOverlay.appendChild(modalContent);
    
    // Añadir el modal al DOM
    document.body.appendChild(modalOverlay);
}

// Función para seleccionar un Pokémon inicial
function seleccionarPokemonInicial(pokemonId) {
    console.log('Seleccionando Pokémon inicial:', pokemonId);
    
    // Mostrar el overlay de carga
    document.getElementById('loading-overlay').style.display = 'flex';
    document.getElementById('loading-overlay').querySelector('p').textContent = 'Enviando selección...';
    
    // Enviar la selección al servidor
    fetch('../Controlador/batalla_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=seleccionar_pokemon_inicial&batalla_id=${BATALLA_ID}&pokemon_id=${pokemonId}`
    })
    .then(response => response.json())
    .then(data => {
        // Ocultar el overlay de carga
        document.getElementById('loading-overlay').style.display = 'none';
        
        if (data.success) {
            // Ocultar el modal de selección
            const modal = document.getElementById('pokemon-inicial-modal');
            if (modal) {
                document.body.removeChild(modal);
            }
            
            // Actualizar el estado de la batalla en el cliente
            if (ES_USUARIO1) {
                battleData.pokemonActivo1 = pokemonId;
            } else if (ES_USUARIO2) {
                battleData.pokemonActivo2 = pokemonId;
            }
            
            // Mostrar mensaje de éxito
            mostrarMensaje('Has seleccionado tu Pokémon inicial. ¡Comienza la batalla!', 'success');
            
            // Actualizar inmediatamente para reflejar los cambios
            actualizarCombate();
        } else {
            console.error('Error al seleccionar Pokémon inicial:', data.message);
            mostrarMensaje(`Error: ${data.message || 'No se pudo seleccionar el Pokémon'}`, 'error');
        }
    })
    .catch(error => {
        document.getElementById('loading-overlay').style.display = 'none';
        console.error('Error en la solicitud de selección de Pokémon inicial:', error);
        mostrarMensaje('Error de conexión al seleccionar Pokémon inicial', 'error');
    });
}

// Función para mostrar mensajes al usuario
function mostrarMensaje(mensaje, tipo = 'info') {
    // Obtener el contenedor de mensajes, o crearlo si no existe
    let mensajesPanel = document.querySelector('.messages-panel');
    if (!mensajesPanel) {
        mensajesPanel = document.createElement('div');
        mensajesPanel.className = 'messages-panel';
        
        // Buscar dónde insertar el panel de mensajes
        const mainContainer = document.querySelector('.combat-main-container');
        if (mainContainer) {
            mainContainer.appendChild(mensajesPanel);
        } else {
            // Si no hay un contenedor principal definido, añadirlo al cuerpo del documento
            document.body.appendChild(mensajesPanel);
        }
    }
    
    // Crear el elemento de mensaje
    const mensajeElement = document.createElement('div');
    mensajeElement.className = `combat-message ${tipo}`;
    mensajeElement.textContent = mensaje;
    
    // Añadir al panel de mensajes
    mensajesPanel.appendChild(mensajeElement);
    
    // Scroll hacia el mensaje más reciente
    mensajesPanel.scrollTop = mensajesPanel.scrollHeight;
    
    // Configurar eliminación automática después de 5 segundos para mensajes de éxito
    if (tipo === 'success') {
        setTimeout(() => {
            mensajesPanel.removeChild(mensajeElement);
        }, 5000);
    }
}

// Función para actualizar un Pokémon en la batalla
function actualizarPokemon(pokemonId, posicion) {
    console.log(`Actualizando Pokémon ID ${pokemonId} en posición ${posicion}`);
    
    // Determinar el equipo al que pertenece el Pokémon
    let equipo;
    if ((posicion === 'player' && ES_USUARIO1) || (posicion === 'opponent' && ES_USUARIO2)) {
        equipo = battleData.equipo1;
    } else {
        equipo = battleData.equipo2;
    }
    
    // Buscar el Pokémon en el equipo
    const pokemon = equipo.find(p => p.id_equip_pokemon == pokemonId);
    if (!pokemon) {
        console.error(`No se encontró el Pokémon con ID ${pokemonId} en el equipo`);
        return;
    }
    
    // Actualizar la interfaz según la posición
    const selector = posicion === 'player' ? '.player-area' : '.opponent-area';
    mostrarPokemonActivo(equipo, pokemonId, selector);
    
    // Si es el Pokémon del jugador, actualizar también los movimientos
    if (posicion === 'player') {
        cargarMovimientosPokemon(equipo, pokemonId);
    }
}

// Función para mostrar las acciones en el historial de la batalla
function mostrarAccionesHistorial(acciones) {
    // Obtener el panel de mensajes
    const mensajesPanel = document.querySelector('.messages-panel');
    if (!mensajesPanel) {
        console.error('No se encontró el panel de mensajes');
        return;
    }
    
    // Procesar cada acción nueva
    acciones.forEach(accion => {
        // Crear elemento de mensaje
        const mensajeElement = document.createElement('div');
        mensajeElement.className = `combat-message ${accion.tipo || 'info'}`;
        mensajeElement.textContent = accion.mensaje || accion.texto || 'Acción sin descripción';
        
        // Añadir al panel
        mensajesPanel.appendChild(mensajeElement);
    });
    
    // Scroll hacia el último mensaje
    mensajesPanel.scrollTop = mensajesPanel.scrollHeight;
}

// Función para mostrar el resultado final de la batalla
function mostrarResultadoBatalla(dataBatalla) {
    // Crear mensaje según el resultado
    let mensaje = 'La batalla ha terminado. ';
    let tipo = 'info';
    
    // Determinar si ganamos, perdimos o fue un empate
    if (dataBatalla.guanyador_id) {
        if ((ES_USUARIO1 && dataBatalla.guanyador_id == USUARIO1_ID) ||
            (ES_USUARIO2 && dataBatalla.guanyador_id == USUARIO2_ID)) {
            mensaje += '¡Has ganado! Felicidades.';
            tipo = 'success';
        } else if ((ES_USUARIO1 && dataBatalla.guanyador_id == USUARIO2_ID) ||
                  (ES_USUARIO2 && dataBatalla.guanyador_id == USUARIO1_ID)) {
            mensaje += 'Has perdido. ¡Mejor suerte la próxima vez!';
            tipo = 'error';
        } else {
            // Caso para espectadores
            const nombreGanador = dataBatalla.guanyador_id == USUARIO1_ID ? 
                dataBatalla.nombre_usuari1 : dataBatalla.nombre_usuari2;
            mensaje += `${nombreGanador} ha ganado la batalla.`;
        }
    } else {
        mensaje += 'La batalla ha finalizado en empate.';
    }
    
    // Crear modal con resultado
    const modalOverlay = document.createElement('div');
    modalOverlay.className = 'modal-overlay';
    modalOverlay.id = 'resultado-batalla-modal';
    
    const modalContent = document.createElement('div');
    modalContent.className = `modal-content ${tipo}`;
    
    const titulo = document.createElement('h2');
    titulo.textContent = '¡Combate finalizado!';
    
    const mensajeElement = document.createElement('p');
    mensajeElement.textContent = mensaje;
    
    const btnVolver = document.createElement('button');
    btnVolver.className = 'btn btn-primary';
    btnVolver.textContent = 'Volver a la página principal';
    btnVolver.addEventListener('click', () => {
        window.location.href = '../index.php';
    });
    
    // Ensamblar modal
    modalContent.appendChild(titulo);
    modalContent.appendChild(mensajeElement);
    modalContent.appendChild(btnVolver);
    modalOverlay.appendChild(modalContent);
    
    // Mostrar modal
    document.body.appendChild(modalOverlay);
    
    // También añadir el mensaje al panel de mensajes
    mostrarMensaje(mensaje, tipo);
}

// Función para mostrar el selector de Pokémon inicial
function mostrarSelectorPokemonInicial() {
    // Determinar el equipo del jugador
    let equipoJugador;
    
    if (ES_USUARIO1) {
        equipoJugador = battleData.equipo1;
    } else if (ES_USUARIO2) {
        equipoJugador = battleData.equipo2;
    } else {
        console.error('No se puede mostrar selector de Pokémon inicial en modo espectador');
        return;
    }
    
    if (!equipoJugador || equipoJugador.length === 0) {
        console.error('No hay Pokémon disponibles para seleccionar');
        return;
    }
    
    // Crear el modal para seleccionar el Pokémon inicial
    const modalOverlay = document.createElement('div');
    modalOverlay.className = 'modal-overlay';
    modalOverlay.id = 'pokemon-inicial-modal';
    
    const modalContent = document.createElement('div');
    modalContent.className = 'modal-content';
    
    const titulo = document.createElement('h2');
    titulo.textContent = '¡Selecciona tu Pokémon inicial!';
    
    const mensaje = document.createElement('p');
    mensaje.textContent = 'Elige el Pokémon con el que iniciarás el combate:';
    
    const pokemonList = document.createElement('div');
    pokemonList.className = 'pokemon-initial-selector';
    
    // Añadir cada Pokémon al selector
    equipoJugador.forEach(pokemon => {
        if (pokemon.hp_actual > 0) {  // Solo mostrar Pokémon con HP
            const pokemonItem = document.createElement('div');
            pokemonItem.className = 'pokemon-initial-item';
            pokemonItem.dataset.id = pokemon.id_equip_pokemon;
            
            pokemonItem.innerHTML = `
                <img src="${pokemon.sprite}" alt="${pokemon.malnom}" class="pokemon-initial-sprite">
                <div class="pokemon-initial-info">
                    <span class="pokemon-initial-name">${pokemon.malnom}</span>
                    <span class="pokemon-initial-level">Nv. ${pokemon.nivell}</span>
                    <div class="pokemon-initial-hp-bar">
                        <div class="hp-bar-inner" style="width: ${(pokemon.hp_actual / pokemon.hp_max) * 100}%;"></div>
                    </div>
                    <span class="pokemon-initial-hp">${pokemon.hp_actual}/${pokemon.hp_max} PS</span>
                </div>
            `;
            
            // Añadir evento para seleccionar este Pokémon
            pokemonItem.addEventListener('click', () => {
                seleccionarPokemonInicial(pokemon.id_equip_pokemon);
            });
            
            pokemonList.appendChild(pokemonItem);
        }
    });
    
    // Ensamblar el modal
    modalContent.appendChild(titulo);
    modalContent.appendChild(mensaje);
    modalContent.appendChild(pokemonList);
    modalOverlay.appendChild(modalContent);
    
    // Añadir el modal al DOM
    document.body.appendChild(modalOverlay);
}

// Función para seleccionar un Pokémon inicial
function seleccionarPokemonInicial(pokemonId) {
    console.log('Seleccionando Pokémon inicial:', pokemonId);
    
    // Mostrar el overlay de carga
    document.getElementById('loading-overlay').style.display = 'flex';
    document.getElementById('loading-overlay').querySelector('p').textContent = 'Enviando selección...';
    
    // Enviar la selección al servidor
    fetch('../Controlador/batalla_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=seleccionar_pokemon_inicial&batalla_id=${BATALLA_ID}&pokemon_id=${pokemonId}`
    })
    .then(response => response.json())
    .then(data => {
        // Ocultar el overlay de carga
        document.getElementById('loading-overlay').style.display = 'none';
        
        if (data.success) {
            // Ocultar el modal de selección
            const modal = document.getElementById('pokemon-inicial-modal');
            if (modal) {
                document.body.removeChild(modal);
            }
            
            // Actualizar el estado de la batalla en el cliente
            if (ES_USUARIO1) {
                battleData.pokemonActivo1 = pokemonId;
            } else if (ES_USUARIO2) {
                battleData.pokemonActivo2 = pokemonId;
            }
            
            // Mostrar mensaje de éxito
            mostrarMensaje('Has seleccionado tu Pokémon inicial. ¡Comienza la batalla!', 'success');
            
            // Actualizar inmediatamente para reflejar los cambios
            actualizarCombate();
        } else {
            console.error('Error al seleccionar Pokémon inicial:', data.message);
            mostrarMensaje(`Error: ${data.message || 'No se pudo seleccionar el Pokémon'}`, 'error');
        }
    })
    .catch(error => {
        document.getElementById('loading-overlay').style.display = 'none';
        console.error('Error en la solicitud de selección de Pokémon inicial:', error);
        mostrarMensaje('Error de conexión al seleccionar Pokémon inicial', 'error');
    });
}

// Función para mostrar mensajes al usuario
function mostrarMensaje(mensaje, tipo = 'info') {
    // Obtener el contenedor de mensajes, o crearlo si no existe
    let mensajesPanel = document.querySelector('.messages-panel');
    if (!mensajesPanel) {
        mensajesPanel = document.createElement('div');
        mensajesPanel.className = 'messages-panel';
        
        // Buscar dónde insertar el panel de mensajes
        const mainContainer = document.querySelector('.combat-main-container');
        if (mainContainer) {
            mainContainer.appendChild(mensajesPanel);
        } else {
            // Si no hay un contenedor principal definido, añadirlo al cuerpo del documento
            document.body.appendChild(mensajesPanel);
        }
    }
    
    // Crear el elemento de mensaje
    const mensajeElement = document.createElement('div');
    mensajeElement.className = `combat-message ${tipo}`;
    mensajeElement.textContent = mensaje;
    
    // Añadir al panel de mensajes
    mensajesPanel.appendChild(mensajeElement);
    
    // Scroll hacia el mensaje más reciente
    mensajesPanel.scrollTop = mensajesPanel.scrollHeight;
    
    // Configurar eliminación automática después de 5 segundos para mensajes de éxito
    if (tipo === 'success') {
        setTimeout(() => {
            mensajesPanel.removeChild(mensajeElement);
        }, 5000);
    }
}

// Función para actualizar un Pokémon en la batalla
function actualizarPokemon(pokemonId, posicion) {
    console.log(`Actualizando Pokémon ID ${pokemonId} en posición ${posicion}`);
    
    // Determinar el equipo al que pertenece el Pokémon
    let equipo;
    if ((posicion === 'player' && ES_USUARIO1) || (posicion === 'opponent' && ES_USUARIO2)) {
        equipo = battleData.equipo1;
    } else {
        equipo = battleData.equipo2;
    }
    
    // Buscar el Pokémon en el equipo
    const pokemon = equipo.find(p => p.id_equip_pokemon == pokemonId);
    if (!pokemon) {
        console.error(`No se encontró el Pokémon con ID ${pokemonId} en el equipo`);
        return;
    }
    
    // Actualizar la interfaz según la posición
    const selector = posicion === 'player' ? '.player-area' : '.opponent-area';
    mostrarPokemonActivo(equipo, pokemonId, selector);
    
    // Si es el Pokémon del jugador, actualizar también los movimientos
    if (posicion === 'player') {
        cargarMovimientosPokemon(equipo, pokemonId);
    }
}

// Función para mostrar las acciones en el historial de la batalla
function mostrarAccionesHistorial(acciones) {
    // Obtener el panel de mensajes
    const mensajesPanel = document.querySelector('.messages-panel');
    if (!mensajesPanel) {
        console.error('No se encontró el panel de mensajes');
        return;
    }
    
    // Procesar cada acción nueva
    acciones.forEach(accion => {
        // Crear elemento de mensaje
        const mensajeElement = document.createElement('div');
        mensajeElement.className = `combat-message ${accion.tipo || 'info'}`;
        mensajeElement.textContent = accion.mensaje || accion.texto || 'Acción sin descripción';
        
        // Añadir al panel
        mensajesPanel.appendChild(mensajeElement);
    });
    
    // Scroll hacia el último mensaje
    mensajesPanel.scrollTop = mensajesPanel.scrollHeight;
}

// Función para mostrar el resultado final de la batalla
function mostrarResultadoBatalla(dataBatalla) {
    // Crear mensaje según el resultado
    let mensaje = 'La batalla ha terminado. ';
    let tipo = 'info';
    
    // Determinar si ganamos, perdimos o fue un empate
    if (dataBatalla.guanyador_id) {
        if ((ES_USUARIO1 && dataBatalla.guanyador_id == USUARIO1_ID) ||
            (ES_USUARIO2 && dataBatalla.guanyador_id == USUARIO2_ID)) {
            mensaje += '¡Has ganado! Felicidades.';
            tipo = 'success';
        } else if ((ES_USUARIO1 && dataBatalla.guanyador_id == USUARIO2_ID) ||
                  (ES_USUARIO2 && dataBatalla.guanyador_id == USUARIO1_ID)) {
            mensaje += 'Has perdido. ¡Mejor suerte la próxima vez!';
            tipo = 'error';
        } else {
            // Caso para espectadores
            const nombreGanador = dataBatalla.guanyador_id == USUARIO1_ID ? 
                dataBatalla.nombre_usuari1 : dataBatalla.nombre_usuari2;
            mensaje += `${nombreGanador} ha ganado la batalla.`;
        }
    } else {
        mensaje += 'La batalla ha finalizado en empate.';
    }
    
    // Crear modal con resultado
    const modalOverlay = document.createElement('div');
    modalOverlay.className = 'modal-overlay';
    modalOverlay.id = 'resultado-batalla-modal';
    
    const modalContent = document.createElement('div');
    modalContent.className = `modal-content ${tipo}`;
    
    const titulo = document.createElement('h2');
    titulo.textContent = '¡Combate finalizado!';
    
    const mensajeElement = document.createElement('p');
    mensajeElement.textContent = mensaje;
    
    const btnVolver = document.createElement('button');
    btnVolver.className = 'btn btn-primary';
    btnVolver.textContent = 'Volver a la página principal';
    btnVolver.addEventListener('click', () => {
        window.location.href = '../index.php';
    });
    
    // Ensamblar modal
    modalContent.appendChild(titulo);
    modalContent.appendChild(mensajeElement);
    modalContent.appendChild(btnVolver);
    modalOverlay.appendChild(modalContent);
    
    // Mostrar modal
    document.body.appendChild(modalOverlay);
    
    // También añadir el mensaje al panel de mensajes
    mostrarMensaje(mensaje, tipo);
}

// Nueva función para actualizar el panel que muestra la acción seleccionada
function actualizarPanelAccionSeleccionada() {
    const accionPanel = document.getElementById('accion-seleccionada');
    const btnConfirmar = document.getElementById('btn-confirmar-accion');
    
    if (!accionPanel || !btnConfirmar) return;
    
    if (battleData.accionSeleccionada) {
        // Si hay una acción seleccionada, mostrar información
        let mensaje = '';
        
        switch (battleData.accionSeleccionada.tipo_accion) {
            case 'moviment':
                // Buscar el nombre del movimiento en el Pokémon activo
                let nombreMovimiento = 'Desconocido';
                let pokemonActivo = null;
                
                if (ES_USUARIO1 && battleData.pokemonActivo1) {
                    pokemonActivo = battleData.equipo1.find(p => p.id_pokemon === battleData.pokemonActivo1);
                } else if (ES_USUARIO2 && battleData.pokemonActivo2) {
                    pokemonActivo = battleData.equipo2.find(p => p.id_pokemon === battleData.pokemonActivo2);
                }
                
                if (pokemonActivo && pokemonActivo.moviments) {
                    const movimiento = pokemonActivo.moviments.find(m => m.id === battleData.accionSeleccionada.movimiento_id);
                    if (movimiento) {
                        nombreMovimiento = movimiento.nombre;
                    }
                }
                
                mensaje = `Has seleccionado usar el movimiento: ${nombreMovimiento}`;
                break;
                
            case 'canvi_pokemon':
                // Buscar el nombre del Pokémon seleccionado
                let nombrePokemon = 'Desconocido';
                const equipoPropio = ES_USUARIO1 ? battleData.equipo1 : battleData.equipo2;
                const pokemon = equipoPropio.find(p => p.id_pokemon === battleData.accionSeleccionada.pokemon_id);
                
                if (pokemon) {
                    nombrePokemon = pokemon.nombre;
                }
                
                mensaje = `Has seleccionado cambiar a: ${nombrePokemon}`;
                break;
                
            default:
                mensaje = 'Acción seleccionada';
        }
        
        accionPanel.textContent = mensaje;
        accionPanel.style.display = 'block';
        btnConfirmar.style.display = 'block';
    } else {
        // Si no hay acción seleccionada, ocultar panel y botón
        accionPanel.style.display = 'none';
        btnConfirmar.style.display = 'none';
    }
}

// Actualiza el estado de los elementos de la interfaz según si es nuestro turno o no
function actualizarEstadoUI(esMiTurno) {
    // Actualizar la visibilidad y estado de los selectores de movimientos y Pokémon
    const botonesMovimientos = document.querySelectorAll('.move-button');
    const botonesPokemon = document.querySelectorAll('.team-pokemon');
    const btnConfirmar = document.getElementById('btn-confirmar-accion');
    
    // Habilitar/deshabilitar botones según si es nuestro turno
    botonesMovimientos.forEach(boton => {
        boton.disabled = !esMiTurno;
        if (esMiTurno) {
            boton.classList.remove('disabled');
        } else {
            boton.classList.add('disabled');
        }
    });
    
    botonesPokemon.forEach(boton => {
        boton.disabled = !esMiTurno;
        if (esMiTurno) {
            boton.classList.remove('disabled');
        } else {
            boton.classList.add('disabled');
        }
    });
    
    // Actualizar el estado del botón de confirmación
    if (btnConfirmar) {
        btnConfirmar.disabled = !esMiTurno || !battleData.accionSeleccionada;
    }
    
    console.log('Actualizando UI, es mi turno: ' + esMiTurno + 
               ' modo espectador: ' + MODO_ESPECTADOR + 
               ' estado batalla: ' + battleData.estado);
}

// Carga los datos de los Pokémon en la interfaz
function cargarDatosPokemon() {
    console.log('Cargando datos de Pokémon: ', battleData);
    
    // Determinar el equipo del jugador y el del oponente
    let equipoJugador, equipoOponente;
    
    if (ES_USUARIO1) {
        equipoJugador = battleData.equipo1;
        equipoOponente = battleData.equipo2;
    } else if (ES_USUARIO2) {
        equipoJugador = battleData.equipo2;
        equipoOponente = battleData.equipo1;
    } else if (MODO_ESPECTADOR) {
        // En modo espectador mostramos los datos completos de ambos equipos
        equipoJugador = battleData.equipo2;  // Por convenio mostramos el equipo 2 abajo
        equipoOponente = battleData.equipo1; // Y el equipo 1 arriba
    }
    
    if (!equipoJugador || !equipoOponente) {
        console.error('No se han podido determinar los equipos');
        return;
    }
    
    // Mostrar el Pokémon activo del jugador en el área del jugador
    const pokemonActivoJugadorId = ES_USUARIO1 ? battleData.pokemonActivo1 : 
                                 (ES_USUARIO2 ? battleData.pokemonActivo2 : battleData.pokemonActivo2);
    mostrarPokemonActivo(equipoJugador, pokemonActivoJugadorId, 'player-area');
    
    // Mostrar el Pokémon activo del oponente en el área del oponente
    const pokemonActivoOponenteId = ES_USUARIO1 ? battleData.pokemonActivo2 : 
                                  (ES_USUARIO2 ? battleData.pokemonActivo1 : battleData.pokemonActivo1);
    mostrarPokemonActivo(equipoOponente, pokemonActivoOponenteId, 'opponent-area');
    
    // Cargar el selector de equipo
    cargarSelectorEquipo(equipoJugador);
    
    // Cargar los movimientos del Pokémon activo
    cargarMovimientosPokemon(equipoJugador, pokemonActivoJugadorId);
    
    // Ocultar el overlay de carga una vez que todo esté cargado
    document.getElementById('loading-overlay').style.display = 'none';
}

// Muestra los datos de los Pokémon del jugador
function mostrarDatosPokemonJugador(equipoPokemon, tipo) {
    if (!equipoPokemon || equipoPokemon.length === 0) {
        console.log('No hay datos de equipo para mostrar');
        return;
    }
    
    console.log('Mostrando equipo ' + tipo + ': ', equipoPokemon);
    
    // Mostrar el Pokémon activo del jugador
    const pokemonActivo = equipoPokemon.find(p => 
        p.id_pokemon === (ES_USUARIO1 ? battleData.pokemonActivo1 : battleData.pokemonActivo2));
    
    if (pokemonActivo) {
        // Actualizar el Pokémon activo en el campo
        const pokemonActivoElement = document.querySelector('.player-active-pokemon');
        if (pokemonActivoElement) {
            pokemonActivoElement.innerHTML = `
                <img src="${pokemonActivo.imagen_trasera || pokemonActivo.imagen}" alt="${pokemonActivo.nombre}" class="pokemon-sprite back">
                <div class="pokemon-info">
                    <div class="pokemon-name">${pokemonActivo.nombre}</div>
                    <div class="pokemon-level">Nv. ${pokemonActivo.nivel}</div>
                    <div class="pokemon-hp-bar">
                        <div class="hp-bar" style="width: ${(pokemonActivo.ps_actuales / pokemonActivo.ps_max) * 100}%"></div>
                    </div>
                    <div class="pokemon-hp-text">${pokemonActivo.ps_actuales}/${pokemonActivo.ps_max} PS</div>
                </div>
            `;
        }
        
        // Actualizar los movimientos disponibles
        cargarMovimientosPokemon();
    }
    
    // Actualizar el selector de equipo
    cargarSelectorEquipo();
}

// Muestra los datos de los Pokémon del rival
function mostrarDatosPokemonRival(equipoPokemon, tipo) {
    if (!equipoPokemon || equipoPokemon.length === 0) {
        console.log('No hay datos de equipo rival para mostrar');
        return;
    }
    
    console.log('Mostrando equipo ' + tipo + ': ', equipoPokemon);
    
    // Mostrar el Pokémon activo del rival
    const pokemonActivo = equipoPokemon.find(p => 
        p.id_pokemon === (ES_USUARIO1 ? battleData.pokemonActivo2 : battleData.pokemonActivo1));
    
    if (pokemonActivo) {
        // Actualizar el Pokémon activo en el campo
        const pokemonActivoElement = document.querySelector('.opponent-active-pokemon');
        if (pokemonActivoElement) {
            pokemonActivoElement.innerHTML = `
                <img src="${pokemonActivo.imagen}" alt="${pokemonActivo.nombre}" class="pokemon-sprite front">
                <div class="pokemon-info">
                    <div class="pokemon-name">${pokemonActivo.nombre}</div>
                    <div class="pokemon-level">Nv. ${pokemonActivo.nivel}</div>
                    <div class="pokemon-hp-bar">
                        <div class="hp-bar" style="width: ${(pokemonActivo.ps_actuales / pokemonActivo.ps_max) * 100}%"></div>
                    </div>
                    <div class="pokemon-hp-text">${pokemonActivo.ps_actuales}/${pokemonActivo.ps_max} PS</div>
                </div>
            `;
        }
    }
}

// Modo espectador: muestra los datos de ambos equipos
function mostrarDatosPokemonEspectador() {
    // Mostrar el equipo 1 como oponente
    mostrarDatosPokemonRival(battleData.equipo1, 'player1');
    
    // Mostrar el equipo 2 como jugador
    mostrarDatosPokemonJugador(battleData.equipo2, 'player2');
}

// Carga los movimientos del Pokémon activo
function cargarMovimientosPokemon() {
    // Determinar cuál es nuestro Pokémon activo
    let pokemonActivo = null;
    
    if (ES_USUARIO1 && battleData.pokemonActivo1) {
        pokemonActivo = battleData.equipo1.find(p => p.id_pokemon === battleData.pokemonActivo1);
    } else if (ES_USUARIO2 && battleData.pokemonActivo2) {
        pokemonActivo = battleData.equipo2.find(p => p.id_pokemon === battleData.pokemonActivo2);
    }
    
    if (!pokemonActivo) {
        console.log('No hay Pokémon activo para mostrar movimientos');
        return;
    }
    
    // Obtener el contenedor de movimientos
    const movesContainer = document.querySelector('.moves-container');
    if (!movesContainer) return;
    
    // Limpiar el contenedor
    movesContainer.innerHTML = '';
    
    // Añadir cada movimiento disponible
    if (pokemonActivo.moviments && pokemonActivo.moviments.length > 0) {
        pokemonActivo.moviments.forEach(movimiento => {
            const moveButton = document.createElement('button');
            moveButton.className = 'move-button';
            moveButton.dataset.moveId = movimiento.id;
            moveButton.innerHTML = `
                <span class="move-name">${movimiento.nombre}</span>
                <span class="move-type ${movimiento.tipo.toLowerCase()}">${movimiento.tipo}</span>
                <span class="move-pp">${movimiento.pp_actuales}/${movimiento.pp_max} PP</span>
            `;
            
            // Añadir el evento al botón para seleccionar este movimiento
            moveButton.addEventListener('click', function() {
                seleccionarMovimiento(movimiento.id);
            });
            
            // Añadir el botón al contenedor
            movesContainer.appendChild(moveButton);
        });
    } else {
        // Si no hay movimientos, mostrar un mensaje
        movesContainer.innerHTML = '<div class="no-moves">Este Pokémon no tiene movimientos disponibles</div>';
    }
}

// Carga el selector de equipo con los Pokémon disponibles
function cargarSelectorEquipo() {
    // Determinar cuál es nuestro equipo
    let equipoPropio = [];
    
    if (ES_USUARIO1) {
        equipoPropio = battleData.equipo1;
    } else if (ES_USUARIO2) {
        equipoPropio = battleData.equipo2;
    }
    
    if (!equipoPropio || equipoPropio.length === 0) {
        console.log('No hay equipo propio para mostrar');
        return;
    }
    
    // Obtener el contenedor del equipo
    const teamContainer = document.querySelector('.team-container');
    if (!teamContainer) return;
    
    // Limpiar el contenedor
    teamContainer.innerHTML = '';
    
    // Añadir cada Pokémon del equipo
    equipoPropio.forEach(pokemon => {
        const pokemonButton = document.createElement('button');
        pokemonButton.className = 'team-pokemon';
        pokemonButton.dataset.pokemonId = pokemon.id_pokemon;
        
        // Si el Pokémon está debilitado, añadir la clase correspondiente
        if (pokemon.ps_actuales <= 0) {
            pokemonButton.classList.add('fainted');
            pokemonButton.disabled = true;
        }
        
        // Si es el Pokémon activo, marcarlo como seleccionado
        if ((ES_USUARIO1 && pokemon.id_pokemon === battleData.pokemonActivo1) || 
            (ES_USUARIO2 && pokemon.id_pokemon === battleData.pokemonActivo2)) {
            pokemonButton.classList.add('active');
        }
        
        // Estructura del botón de Pokémon
        pokemonButton.innerHTML = `
            <img src="${pokemon.imagen_mini || pokemon.imagen}" alt="${pokemon.nombre}" class="pokemon-mini">
            <div class="pokemon-team-info">
                <span class="team-pokemon-name">${pokemon.nombre}</span>
                <div class="team-pokemon-hp-bar">
                    <div class="hp-bar" style="width: ${(pokemon.ps_actuales / pokemon.ps_max) * 100}%"></div>
                </div>
                <span class="team-pokemon-hp">${pokemon.ps_actuales}/${pokemon.ps_max}</span>
            </div>
        `;
        
        // Añadir el evento al botón para seleccionar este Pokémon
        pokemonButton.addEventListener('click', function() {
            seleccionarPokemonCambio(pokemon.id_pokemon);
        });
        
        // Añadir el botón al contenedor
        teamContainer.appendChild(pokemonButton);
    });
}

// Función para configurar eventos en los botones de movimientos
function configurarEventosMovimientos() {
    console.log('Configurando eventos de movimientos');
    const botonesMovimiento = document.querySelectorAll('.moves-container .btn-move');
    botonesMovimiento.forEach(function(boton) {
        // Eliminar eventos previos si existen
        const clonedBtn = boton.cloneNode(true);
        boton.parentNode.replaceChild(clonedBtn, boton);
        
        // Añadir el evento de clic
        clonedBtn.addEventListener('click', function() {
            if (!this.disabled) {
                const movimientoId = this.getAttribute('data-id');
                console.log('Clic en movimiento:', movimientoId);
                usarMovimiento(movimientoId);
            }
        });
    });
}

// Función para configurar eventos en los botones de selección de Pokémon
function configurarEventosPokemon() {
    console.log('Configurando eventos de selección de Pokémon');
    const botonesPokemon = document.querySelectorAll('.team-selector .team-pokemon');
    botonesPokemon.forEach(function(boton) {
        // Eliminar eventos previos si existen
        const clonedBtn = boton.cloneNode(true);
        boton.parentNode.replaceChild(clonedBtn, boton);
        
        // Añadir el evento de clic
        clonedBtn.addEventListener('click', function() {
            if (!this.classList.contains('active')) { // No cambiar si ya está activo
                const pokemonIndex = this.getAttribute('data-index');
                console.log('Clic en Pokémon:', pokemonIndex);
                
                // Obtener el equipo correcto según el usuario
                const equipoJugador = battleData.esUsuario1 ? battleData.equipo1 : 
                                    (battleData.esUsuario2 ? battleData.equipo2 : null);
                
                if (equipoJugador && equipoJugador[pokemonIndex]) {
                    const pokemonId = equipoJugador[pokemonIndex].id_equip_pokemon;
                    cambiarPokemon(pokemonId);
                }
            }
        });
    });
}

// Seleccionar un movimiento como acción para este turno
function seleccionarMovimiento(movimientoId) {
    console.log('Movimiento seleccionado: ' + movimientoId);
    
    // Solo se puede seleccionar si es nuestro turno
    if (!battleData.esMiTurno || MODO_ESPECTADOR) {
        console.log('No es tu turno o estás en modo espectador');
        return;
    }
    
    // Guardar la acción seleccionada
    battleData.accionSeleccionada = {
        tipo_accion: 'moviment',
        movimiento_id: movimientoId,
        pokemon_id: null
    };
    
    // Actualizar la interfaz para mostrar la acción seleccionada
    actualizarPanelAccionSeleccionada();
}

// Seleccionar un Pokémon para cambiarlo por el activo
function seleccionarPokemonCambio(pokemonId) {
    console.log('Pokémon seleccionado para cambio: ' + pokemonId);
    
    // Solo se puede seleccionar si es nuestro turno
    if (!battleData.esMiTurno || MODO_ESPECTADOR) {
        console.log('No es tu turno o estás en modo espectador');
        return;
    }
    
    // No se puede seleccionar el Pokémon que ya está activo
    if ((ES_USUARIO1 && pokemonId === battleData.pokemonActivo1) || 
        (ES_USUARIO2 && pokemonId === battleData.pokemonActivo2)) {
        console.log('Este Pokémon ya está activo');
        return;
    }
    
    // Guardar la acción seleccionada
    battleData.accionSeleccionada = {
        tipo_accion: 'canvi_pokemon',
        movimiento_id: null,
        pokemon_id: pokemonId
    };
    
    // Actualizar la interfaz para mostrar la acción seleccionada
    actualizarPanelAccionSeleccionada();
}

// Confirmar la acción seleccionada y enviarla al servidor
function confirmarAccion() {
    console.log('Confirmando acción');
    
    // Verificar que sea nuestro turno
    if (!battleData.esMiTurno || MODO_ESPECTADOR) {
        mostrarMensaje('No es tu turno o estás en modo espectador', 'error');
        return;
    }
    
    // Mostrar el overlay de carga
    document.getElementById('loading-overlay').style.display = 'flex';
    document.getElementById('loading-overlay').querySelector('p').textContent = 'Registrando acción...';
    
    // Determinar qué tipo de acción enviar basado en la última interacción del usuario
    const accionSeleccionada = document.querySelector('.team-selector .active') ? 
        'canvi_pokemon' : 'moviment';
    
    // Obtener el ID del movimiento o Pokémon seleccionado
    let movimientoId = null;
    let pokemonId = null;
    
    if (accionSeleccionada === 'moviment') {
        const botonMovimiento = document.querySelector('.btn-move:not([disabled])');
        if (botonMovimiento) {
            movimientoId = botonMovimiento.dataset.id;
        }
    } else {
        const pokemonSeleccionado = document.querySelector('.team-selector .team-pokemon.active');
        if (pokemonSeleccionado) {
            const index = pokemonSeleccionado.dataset.index;
            const equipoJugador = battleData.esUsuario1 ? battleData.equipo1 : battleData.equipo2;
            if (equipoJugador && equipoJugador[index]) {
                pokemonId = equipoJugador[index].id_equip_pokemon;
            }
        }
    }
    
    // Preparar los datos para la petición
    const formData = new FormData();
    formData.append('action', 'realizar_accion_turno');
    formData.append('batalla_id', BATALLA_ID);
    formData.append('tipus_accio', accionSeleccionada);
    
    if (movimientoId) {
        formData.append('moviment_id', movimientoId);
    }
    
    if (pokemonId) {
        formData.append('pokemon_id', pokemonId);
    }
    
    // Enviar la acción al servidor
    fetch('../Controlador/batalla_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then (data => {
        // Ocultar el overlay de carga
        document.getElementById('loading-overlay').style.display = 'none';
        
        if (data.success) {
            // Mostrar mensaje de éxito
            mostrarMensaje(data.message || 'Acción registrada correctamente', 'success');
            
            // Si el turno se ha completado, actualizar el combate inmediatamente
            if (data.turno_completado) {
                actualizarCombate();
            }
            
            // Actualizar estado del botón según el turno
            battleData.esMiTurno = false;
            MI_TURNO = false;
            actualizarEstadoUI(false);
        } else {
            console.error('Error al registrar acción:', data.message);
            mostrarMensaje('Error: ' + (data.message || 'No se pudo registrar la acción'), 'error');
        }
    })
    .catch(error => {
        document.getElementById('loading-overlay').style.display = 'none';
        console.error('Error en la solicitud:', error);
        mostrarMensaje('Error de conexión al registrar acción', 'error');
    });
}

// Preparar una acción (seleccionarla pero sin enviarla todavía)
function prepararAccion(tipoAccion, movimientoId, pokemonId) {
    console.log(`Preparando acción: ${tipoAccion}, movimiento: ${movimientoId}, pokemon: ${pokemonId}`);
    
    // Verificar que sea nuestro turno
    if (!battleData.esMiTurno) {
        mostrarMensaje('No es tu turno', 'error');
        return;
    }
    
    // Preparar los datos para enviar
    const formData = new FormData();
    formData.append('action', 'preparar_accion');
    formData.append('batalla_id', battleData.idBatalla);
    formData.append('tipus_accio', tipoAccion);
    
    // Añadir los datos específicos según el tipo de acción
    if (tipoAccion === 'moviment' && movimientoId) {
        formData.append('moviment_id', movimientoId);
    }
    
    if (tipoAccion === 'canvi_pokemon' && pokemonId) {
        formData.append('pokemon_id', pokemonId);
    }
    
    // Hacer la petición AJAX para preparar la acción
    fetch('../Controlador/batalla_api.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // La acción se ha preparado correctamente
                console.log('Acción preparada correctamente: ', data);
                
                // Actualizar la interfaz
                actualizarPanelAccionSeleccionada();
                
                // Mostrar mensaje de confirmación
                mostrarMensaje(data.message, 'info');
            } else {
                // Error al preparar la acción
                console.error('Error al preparar acción: ', data.message);
                mostrarMensaje('Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error en la petición AJAX: ', error);
            mostrarMensaje('Error al preparar la acción', 'error');
        });
}

// Realiza la acción de rendición del jugador
function rendirse() {
    const formData = new FormData();
    formData.append('action', 'rendicion');
    formData.append('batalla_id', battleData.idBatalla);
    
    fetch('../Controlador/batalla_api.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarMensaje('Te has rendido. Redirigiendo...', 'info');
                
                // Redirigir al usuario a la página de inicio
                setTimeout(() => {
                    window.location.href = '../index.php';
                }, 2000);
            } else {
                mostrarMensaje('Error al rendirse: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error en la petición AJAX: ', error);
            mostrarMensaje('Error al procesar la rendición', 'error');
        });
}

// Función para seleccionar Pokémon inicial al comenzar la batalla
function seleccionarPokemonInicial(pokemonId) {
    console.log('Seleccionando Pokémon inicial:', pokemonId);
    
    // Mostrar el overlay de carga
    document.getElementById('loading-overlay').style.display = 'flex';
    document.getElementById('loading-overlay').querySelector('p').textContent = 'Enviando selección...';
    
    // Enviar la selección al servidor
    fetch('../Controlador/batalla_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=seleccionar_pokemon_inicial&batalla_id=${BATALLA_ID}&pokemon_id=${pokemonId}`
    })
    .then(response => response.json())
    .then(data => {
        // Ocultar el overlay de carga
        document.getElementById('loading-overlay').style.display = 'none';
        
        if (data.success) {
            // Ocultar el modal de selección
            const modal = document.getElementById('pokemon-inicial-modal');
            if (modal) {
                document.body.removeChild(modal);
            }
            
            // Actualizar el estado de la batalla en el cliente
            if (ES_USUARIO1) {
                battleData.pokemonActivo1 = pokemonId;
            } else if (ES_USUARIO2) {
                battleData.pokemonActivo2 = pokemonId;
            }
            
            // Mostrar mensaje de éxito
            mostrarMensaje('Has seleccionado tu Pokémon inicial. ¡Comienza la batalla!', 'success');
            
            // Actualizar inmediatamente para reflejar los cambios
            actualizarCombate();
        } else {
            console.error('Error al seleccionar Pokémon inicial:', data.message);
            mostrarMensaje(`Error: ${data.message || 'No se pudo seleccionar el Pokémon'}`, 'error');
        }
    })
    .catch(error => {
        document.getElementById('loading-overlay').style.display = 'none';
        console.error('Error en la solicitud de selección de Pokémon inicial:', error);
        mostrarMensaje('Error de conexión al seleccionar Pokémon inicial', 'error');
    });
}

// Función para mostrar mensajes al usuario
function mostrarMensaje(mensaje, tipo = 'info') {
    // Obtener el contenedor de mensajes, o crearlo si no existe
    let mensajesPanel = document.querySelector('.messages-panel');
    if (!mensajesPanel) {
        mensajesPanel = document.createElement('div');
        mensajesPanel.className = 'messages-panel';
        
        // Buscar dónde insertar el panel de mensajes
        const mainContainer = document.querySelector('.combat-main-container');
        if (mainContainer) {
            mainContainer.appendChild(mensajesPanel);
        } else {
            // Si no hay un contenedor principal definido, añadirlo al cuerpo del documento
            document.body.appendChild(mensajesPanel);
        }
    }
    
    // Crear el elemento de mensaje
    const mensajeElement = document.createElement('div');
    mensajeElement.className = `combat-message ${tipo}`;
    mensajeElement.textContent = mensaje;
    
    // Añadir al panel de mensajes
    mensajesPanel.appendChild(mensajeElement);
    
    // Scroll hacia el mensaje más reciente
    mensajesPanel.scrollTop = mensajesPanel.scrollHeight;
    
    // Configurar eliminación automática después de 5 segundos para mensajes de éxito
    if (tipo === 'success') {
        setTimeout(() => {
            mensajesPanel.removeChild(mensajeElement);
        }, 5000);
    }
}

// Función para actualizar un Pokémon en la batalla
function actualizarPokemon(pokemonId, posicion) {
    console.log(`Actualizando Pokémon ID ${pokemonId} en posición ${posicion}`);
    
    // Determinar el equipo al que pertenece el Pokémon
    let equipo;
    if ((posicion === 'player' && ES_USUARIO1) || (posicion === 'opponent' && ES_USUARIO2)) {
        equipo = battleData.equipo1;
    } else {
        equipo = battleData.equipo2;
    }
    
    // Buscar el Pokémon en el equipo
    const pokemon = equipo.find(p => p.id_equip_pokemon == pokemonId);
    if (!pokemon) {
        console.error(`No se encontró el Pokémon con ID ${pokemonId} en el equipo`);
        return;
    }
    
    // Actualizar la interfaz según la posición
    const selector = posicion === 'player' ? '.player-area' : '.opponent-area';
    mostrarPokemonActivo(equipo, pokemonId, selector);
    
    // Si es el Pokémon del jugador, actualizar también los movimientos
    if (posicion === 'player') {
        cargarMovimientosPokemon(equipo, pokemonId);
    }
}

// Función para mostrar las acciones en el historial de la batalla
function mostrarAccionesHistorial(acciones) {
    // Obtener el panel de mensajes
    const mensajesPanel = document.querySelector('.messages-panel');
    if (!mensajesPanel) {
        console.error('No se encontró el panel de mensajes');
        return;
    }
    
    // Procesar cada acción nueva
    acciones.forEach(accion => {
        // Crear elemento de mensaje
        const mensajeElement = document.createElement('div');
        mensajeElement.className = `combat-message ${accion.tipo || 'info'}`;
        mensajeElement.textContent = accion.mensaje || accion.texto || 'Acción sin descripción';
        
        // Añadir al panel
        mensajesPanel.appendChild(mensajeElement);
    });
    
    // Scroll hacia el último mensaje
    mensajesPanel.scrollTop = mensajesPanel.scrollHeight;
}

// Función para mostrar el resultado final de la batalla
function mostrarResultadoBatalla(dataBatalla) {
    // Crear mensaje según el resultado
    let mensaje = 'La batalla ha terminado. ';
    let tipo = 'info';
    
    // Determinar si ganamos, perdimos o fue un empate
    if (dataBatalla.guanyador_id) {
        if ((ES_USUARIO1 && dataBatalla.guanyador_id == USUARIO1_ID) ||
            (ES_USUARIO2 && dataBatalla.guanyador_id == USUARIO2_ID)) {
            mensaje += '¡Has ganado! Felicidades.';
            tipo = 'success';
        } else if ((ES_USUARIO1 && dataBatalla.guanyador_id == USUARIO2_ID) ||
                  (ES_USUARIO2 && dataBatalla.guanyador_id == USUARIO1_ID)) {
            mensaje += 'Has perdido. ¡Mejor suerte la próxima vez!';
            tipo = 'error';
        } else {
            // Caso para espectadores
            const nombreGanador = dataBatalla.guanyador_id == USUARIO1_ID ? 
                dataBatalla.nombre_usuari1 : dataBatalla.nombre_usuari2;
            mensaje += `${nombreGanador} ha ganado la batalla.`;
        }
    } else {
        mensaje += 'La batalla ha finalizado en empate.';
    }
    
    // Crear modal con resultado
    const modalOverlay = document.createElement('div');
    modalOverlay.className = 'modal-overlay';
    modalOverlay.id = 'resultado-batalla-modal';
    
    const modalContent = document.createElement('div');
    modalContent.className = `modal-content ${tipo}`;
    
    const titulo = document.createElement('h2');
    titulo.textContent = '¡Combate finalizado!';
    
    const mensajeElement = document.createElement('p');
    mensajeElement.textContent = mensaje;
    
    const btnVolver = document.createElement('button');
    btnVolver.className = 'btn btn-primary';
    btnVolver.textContent = 'Volver a la página principal';
    btnVolver.addEventListener('click', () => {
        window.location.href = '../index.php';
    });
    
    // Ensamblar modal
    modalContent.appendChild(titulo);
    modalContent.appendChild(mensajeElement);
    modalContent.appendChild(btnVolver);
    modalOverlay.appendChild(modalContent);
    
    // Mostrar modal
    document.body.appendChild(modalOverlay);
    
    // También añadir el mensaje al panel de mensajes
    mostrarMensaje(mensaje, tipo);
}

// Nueva función para actualizar el panel que muestra la acción seleccionada
function actualizarPanelAccionSeleccionada() {
    const accionPanel = document.getElementById('accion-seleccionada');
    const btnConfirmar = document.getElementById('btn-confirmar-accion');
    
    if (!accionPanel || !btnConfirmar) return;
    
    if (battleData.accionSeleccionada) {
        // Si hay una acción seleccionada, mostrar información
        let mensaje = '';
        
        switch (battleData.accionSeleccionada.tipo_accion) {
            case 'moviment':
                // Buscar el nombre del movimiento en el Pokémon activo
                let nombreMovimiento = 'Desconocido';
                let pokemonActivo = null;
                
                if (ES_USUARIO1 && battleData.pokemonActivo1) {
                    pokemonActivo = battleData.equipo1.find(p => p.id_pokemon === battleData.pokemonActivo1);
                } else if (ES_USUARIO2 && battleData.pokemonActivo2) {
                    pokemonActivo = battleData.equipo2.find(p => p.id_pokemon === battleData.pokemonActivo2);
                }
                
                if (pokemonActivo && pokemonActivo.moviments) {
                    const movimiento = pokemonActivo.moviments.find(m => m.id === battleData.accionSeleccionada.movimiento_id);
                    if (movimiento) {
                        nombreMovimiento = movimiento.nombre;
                    }
                }
                
                mensaje = `Has seleccionado usar el movimiento: ${nombreMovimiento}`;
                break;
                
            case 'canvi_pokemon':
                // Buscar el nombre del Pokémon seleccionado
                let nombrePokemon = 'Desconocido';
                const equipoPropio = ES_USUARIO1 ? battleData.equipo1 : battleData.equipo2;
                const pokemon = equipoPropio.find(p => p.id_pokemon === battleData.accionSeleccionada.pokemon_id);
                
                if (pokemon) {
                    nombrePokemon = pokemon.nombre;
                }
                
                mensaje = `Has seleccionado cambiar a: ${nombrePokemon}`;
                break;
                
            default:
                mensaje = 'Acción seleccionada';
        }
        
        accionPanel.textContent = mensaje;
        accionPanel.style.display = 'block';
        btnConfirmar.style.display = 'block';
    } else {
        // Si no hay acción seleccionada, ocultar panel y botón
        accionPanel.style.display = 'none';
        btnConfirmar.style.display = 'none';
    }
}

// Actualiza el estado de los elementos de la interfaz según si es nuestro turno o no
function actualizarEstadoUI(esMiTurno) {
    // Actualizar la visibilidad y estado de los selectores de movimientos y Pokémon
    const botonesMovimientos = document.querySelectorAll('.move-button');
    const botonesPokemon = document.querySelectorAll('.team-pokemon');
    const btnConfirmar = document.getElementById('btn-confirmar-accion');
    
    // Habilitar/deshabilitar botones según si es nuestro turno
    botonesMovimientos.forEach(boton => {
        boton.disabled = !esMiTurno;
        if (esMiTurno) {
            boton.classList.remove('disabled');
        } else {
            boton.classList.add('disabled');
        }
    });
    
    botonesPokemon.forEach(boton => {
        boton.disabled = !esMiTurno;
        if (esMiTurno) {
            boton.classList.remove('disabled');
        } else {
            boton.classList.add('disabled');
        }
    });
    
    // Actualizar el estado del botón de confirmación
    if (btnConfirmar) {
        btnConfirmar.disabled = !esMiTurno || !battleData.accionSeleccionada;
    }
    
    console.log('Actualizando UI, es mi turno: ' + esMiTurno + 
               ' modo espectador: ' + MODO_ESPECTADOR + 
               ' estado batalla: ' + battleData.estado);
}

// Carga los datos de los Pokémon en la interfaz
function cargarDatosPokemon() {
    console.log('Cargando datos de Pokémon: ', battleData);
    
    // Determinar el equipo del jugador y el del oponente
    let equipoJugador, equipoOponente;
    
    if (ES_USUARIO1) {
        equipoJugador = battleData.equipo1;
        equipoOponente = battleData.equipo2;
    } else if (ES_USUARIO2) {
        equipoJugador = battleData.equipo2;
        equipoOponente = battleData.equipo1;
    } else if (MODO_ESPECTADOR) {
        // En modo espectador mostramos los datos completos de ambos equipos
        equipoJugador = battleData.equipo2;  // Por convenio mostramos el equipo 2 abajo
        equipoOponente = battleData.equipo1; // Y el equipo 1 arriba
    }
    
    if (!equipoJugador || !equipoOponente) {
        console.error('No se han podido determinar los equipos');
        return;
    }
    
    // Mostrar el Pokémon activo del jugador en el área del jugador
    const pokemonActivoJugadorId = ES_USUARIO1 ? battleData.pokemonActivo1 : 
                                 (ES_USUARIO2 ? battleData.pokemonActivo2 : battleData.pokemonActivo2);
    mostrarPokemonActivo(equipoJugador, pokemonActivoJugadorId, 'player-area');
    
    // Mostrar el Pokémon activo del oponente en el área del oponente
    const pokemonActivoOponenteId = ES_USUARIO1 ? battleData.pokemonActivo2 : 
                                  (ES_USUARIO2 ? battleData.pokemonActivo1 : battleData.pokemonActivo1);
    mostrarPokemonActivo(equipoOponente, pokemonActivoOponenteId, 'opponent-area');
    
    // Cargar el selector de equipo
    cargarSelectorEquipo(equipoJugador);
    
    // Cargar los movimientos del Pokémon activo
    cargarMovimientosPokemon(equipoJugador, pokemonActivoJugadorId);
    
    // Ocultar el overlay de carga una vez que todo esté cargado
    document.getElementById('loading-overlay').style.display = 'none';
}

// Muestra los datos de los Pokémon del jugador
function mostrarDatosPokemonJugador(equipoPokemon, tipo) {
    if (!equipoPokemon || equipoPokemon.length === 0) {
        console.log('No hay datos de equipo para mostrar');
        return;
    }
    
    console.log('Mostrando equipo ' + tipo + ': ', equipoPokemon);
    
    // Mostrar el Pokémon activo del jugador
    const pokemonActivo = equipoPokemon.find(p => 
        p.id_pokemon === (ES_USUARIO1 ? battleData.pokemonActivo1 : battleData.pokemonActivo2));
    
    if (pokemonActivo) {
        // Actualizar el Pokémon activo en el campo
        const pokemonActivoElement = document.querySelector('.player-active-pokemon');
        if (pokemonActivoElement) {
            pokemonActivoElement.innerHTML = `
                <img src="${pokemonActivo.imagen_trasera || pokemonActivo.imagen}" alt="${pokemonActivo.nombre}" class="pokemon-sprite back">
                <div class="pokemon-info">
                    <div class="pokemon-name">${pokemonActivo.nombre}</div>
                    <div class="pokemon-level">Nv. ${pokemonActivo.nivel}</div>
                    <div class="pokemon-hp-bar">
                        <div class="hp-bar" style="width: ${(pokemonActivo.ps_actuales / pokemonActivo.ps_max) * 100}%"></div>
                    </div>
                    <div class="pokemon-hp-text">${pokemonActivo.ps_actuales}/${pokemonActivo.ps_max} PS</div>
                </div>
            `;
        }
        
        // Actualizar los movimientos disponibles
        cargarMovimientosPokemon();
    }
    
    // Actualizar el selector de equipo
    cargarSelectorEquipo();
}

// Muestra los datos de los Pokémon del rival
function mostrarDatosPokemonRival(equipoPokemon, tipo) {
    if (!equipoPokemon || equipoPokemon.length === 0) {
        console.log('No hay datos de equipo rival para mostrar');
        return;
    }
    
    console.log('Mostrando equipo ' + tipo + ': ', equipoPokemon);
    
    // Mostrar el Pokémon activo del rival
    const pokemonActivo = equipoPokemon.find(p => 
        p.id_pokemon === (ES_USUARIO1 ? battleData.pokemonActivo2 : battleData.pokemonActivo1));
    
    if (pokemonActivo) {
        // Actualizar el Pokémon activo en el campo
        const pokemonActivoElement = document.querySelector('.opponent-active-pokemon');
        if (pokemonActivoElement) {
            pokemonActivoElement.innerHTML = `
                <img src="${pokemonActivo.imagen}" alt="${pokemonActivo.nombre}" class="pokemon-sprite front">
                <div class="pokemon-info">
                    <div class="pokemon-name">${pokemonActivo.nombre}</div>
                    <div class="pokemon-level">Nv. ${pokemonActivo.nivel}</div>
                    <div class="pokemon-hp-bar">
                        <div class="hp-bar" style="width: ${(pokemonActivo.ps_actuales / pokemonActivo.ps_max) * 100}%"></div>
                    </div>
                    <div class="pokemon-hp-text">${pokemonActivo.ps_actuales}/${pokemonActivo.ps_max} PS</div>
                </div>
            `;
        }
    }
}

// Modo espectador: muestra los datos de ambos equipos
function mostrarDatosPokemonEspectador() {
    // Mostrar el equipo 1 como oponente
    mostrarDatosPokemonRival(battleData.equipo1, 'player1');
    
    // Mostrar el equipo 2 como jugador
    mostrarDatosPokemonJugador(battleData.equipo2, 'player2');
}

// Carga los movimientos del Pokémon activo
function cargarMovimientosPokemon() {
    // Determinar cuál es nuestro Pokémon activo
    let pokemonActivo = null;
    
    if (ES_USUARIO1 && battleData.pokemonActivo1) {
        pokemonActivo = battleData.equipo1.find(p => p.id_pokemon === battleData.pokemonActivo1);
    } else if (ES_USUARIO2 && battleData.pokemonActivo2) {
        pokemonActivo = battleData.equipo2.find(p => p.id_pokemon === battleData.pokemonActivo2);
    }
    
    if (!pokemonActivo) {
        console.log('No hay Pokémon activo para mostrar movimientos');
        return;
    }
    
    // Obtener el contenedor de movimientos
    const movesContainer = document.querySelector('.moves-container');
    if (!movesContainer) return;
    
    // Limpiar el contenedor
    movesContainer.innerHTML = '';
    
    // Añadir cada movimiento disponible
    if (pokemonActivo.moviments && pokemonActivo.moviments.length > 0) {
        pokemonActivo.moviments.forEach(movimiento => {
            const moveButton = document.createElement('button');
            moveButton.className = 'move-button';
            moveButton.dataset.moveId = movimiento.id;
            moveButton.innerHTML = `
                <span class="move-name">${movimiento.nombre}</span>
                <span class="move-type ${movimiento.tipo.toLowerCase()}">${movimiento.tipo}</span>
                <span class="move-pp">${movimiento.pp_actuales}/${movimiento.pp_max} PP</span>
            `;
            
            // Añadir el evento al botón para seleccionar este movimiento
            moveButton.addEventListener('click', function() {
                seleccionarMovimiento(movimiento.id);
            });
            
            // Añadir el botón al contenedor
            movesContainer.appendChild(moveButton);
        });
    } else {
        // Si no hay movimientos, mostrar un mensaje
        movesContainer.innerHTML = '<div class="no-moves">Este Pokémon no tiene movimientos disponibles</div>';
    }
}

// Carga el selector de equipo con los Pokémon disponibles
function cargarSelectorEquipo() {
    // Determinar cuál es nuestro equipo
    let equipoPropio = [];
    
    if (ES_USUARIO1) {
        equipoPropio = battleData.equipo1;
    } else if (ES_USUARIO2) {
        equipoPropio = battleData.equipo2;
    }
    
    if (!equipoPropio || equipoPropio.length === 0) {
        console.log('No hay equipo propio para mostrar');
        return;
    }
    
    // Obtener el contenedor del equipo
    const teamContainer = document.querySelector('.team-container');
    if (!teamContainer) return;
    
    // Limpiar el contenedor
    teamContainer.innerHTML = '';
    
    // Añadir cada Pokémon del equipo
    equipoPropio.forEach(pokemon => {
        const pokemonButton = document.createElement('button');
        pokemonButton.className = 'team-pokemon';
        pokemonButton.dataset.pokemonId = pokemon.id_pokemon;
        
        // Si el Pokémon está debilitado, añadir la clase correspondiente
        if (pokemon.ps_actuales <= 0) {
            pokemonButton.classList.add('fainted');
            pokemonButton.disabled = true;
        }
        
        // Si es el Pokémon activo, marcarlo como seleccionado
        if ((ES_USUARIO1 && pokemon.id_pokemon === battleData.pokemonActivo1) || 
            (ES_USUARIO2 && pokemon.id_pokemon === battleData.pokemonActivo2)) {
            pokemonButton.classList.add('active');
        }
        
        // Estructura del botón de Pokémon
        pokemonButton.innerHTML = `
            <img src="${pokemon.imagen_mini || pokemon.imagen}" alt="${pokemon.nombre}" class="pokemon-mini">
            <div class="pokemon-team-info">
                <span class="team-pokemon-name">${pokemon.nombre}</span>
                <div class="team-pokemon-hp-bar">
                    <div class="hp-bar" style="width: ${(pokemon.ps_actuales / pokemon.ps_max) * 100}%"></div>
                </div>
                <span class="team-pokemon-hp">${pokemon.ps_actuales}/${pokemon.ps_max}</span>
            </div>
        `;
        
        // Añadir el evento al botón para seleccionar este Pokémon
        pokemonButton.addEventListener('click', function() {
            seleccionarPokemonCambio(pokemon.id_pokemon);
        });
        
        // Añadir el botón al contenedor
        teamContainer.appendChild(pokemonButton);
    });
}

// Función para configurar eventos en los botones de movimientos
function configurarEventosMovimientos() {
    console.log('Configurando eventos de movimientos');
    const botonesMovimiento = document.querySelectorAll('.moves-container .btn-move');
    botonesMovimiento.forEach(function(boton) {
        // Eliminar eventos previos si existen
        const clonedBtn = boton.cloneNode(true);
        boton.parentNode.replaceChild(clonedBtn, boton);
        
        // Añadir el evento de clic
        clonedBtn.addEventListener('click', function() {
            if (!this.disabled) {
                const movimientoId = this.getAttribute('data-id');
                console.log('Clic en movimiento:', movimientoId);
                usarMovimiento(movimientoId);
            }
        });
    });
}

// Función para configurar eventos en los botones de selección de Pokémon
function configurarEventosPokemon() {
    console.log('Configurando eventos de selección de Pokémon');
    const botonesPokemon = document.querySelectorAll('.team-selector .team-pokemon');
    botonesPokemon.forEach(function(boton) {
        // Eliminar eventos previos si existen
        const clonedBtn = boton.cloneNode(true);
        boton.parentNode.replaceChild(clonedBtn, boton);
        
        // Añadir el evento de clic
        clonedBtn.addEventListener('click', function() {
            if (!this.classList.contains('active')) { // No cambiar si ya está activo
                const pokemonIndex = this.getAttribute('data-index');
                console.log('Clic en Pokémon:', pokemonIndex);
                
                // Obtener el equipo correcto según el usuario
                const equipoJugador = battleData.esUsuario1 ? battleData.equipo1 : 
                                    (battleData.esUsuario2 ? battleData.equipo2 : null);
                
                if (equipoJugador && equipoJugador[pokemonIndex]) {
                    const pokemonId = equipoJugador[pokemonIndex].id_equip_pokemon;
                    cambiarPokemon(pokemonId);
                }
            }
        });
    });
}

// Seleccionar un movimiento como acción para este turno
function seleccionarMovimiento(movimientoId) {
    console.log('Movimiento seleccionado: ' + movimientoId);
    
    // Solo se puede seleccionar si es nuestro turno
    if (!battleData.esMiTurno || MODO_ESPECTADOR) {
        console.log('No es tu turno o estás en modo espectador');
        return;
    }
    
    // Guardar la acción seleccionada
    battleData.accionSeleccionada = {
        tipo_accion: 'moviment',
        movimiento_id: movimientoId,
        pokemon_id: null
    };
    
    // Actualizar la interfaz para mostrar la acción seleccionada
    actualizarPanelAccionSeleccionada();
}

// Seleccionar un Pokémon para cambiarlo por el activo
function seleccionarPokemonCambio(pokemonId) {
    console.log('Pokémon seleccionado para cambio: ' + pokemonId);
    
    // Solo se puede seleccionar si es nuestro turno
    if (!battleData.esMiTurno || MODO_ESPECTADOR) {
        console.log('No es tu turno o estás en modo espectador');
        return;
    }
    
    // No se puede seleccionar el Pokémon que ya está activo
    if ((ES_USUARIO1 && pokemonId === battleData.pokemonActivo1) || 
        (ES_USUARIO2 && pokemonId === battleData.pokemonActivo2)) {
        console.log('Este Pokémon ya está activo');
        return;
    }
    
    // Guardar la acción seleccionada
    battleData.accionSeleccionada = {
        tipo_accion: 'canvi_pokemon',
        movimiento_id: null,
        pokemon_id: pokemonId
    };
    
    // Actualizar la interfaz para mostrar la acción seleccionada
    actualizarPanelAccionSeleccionada();
}

// Confirmar la acción seleccionada y enviarla al servidor
function confirmarAccion() {
    console.log('Confirmando acción');
    
    // Verificar que sea nuestro turno
    if (!battleData.esMiTurno || MODO_ESPECTADOR) {
        mostrarMensaje('No es tu turno o estás en modo espectador', 'error');
        return;
    }
    
    // Mostrar el overlay de carga
    document.getElementById('loading-overlay').style.display = 'flex';
    document.getElementById('loading-overlay').querySelector('p').textContent = 'Registrando acción...';
    
    // Determinar qué tipo de acción enviar basado en la última interacción del usuario
    const accionSeleccionada = document.querySelector('.team-selector .active') ? 
        'canvi_pokemon' : 'moviment';
    
    // Obtener el ID del movimiento o Pokémon seleccionado
    let movimientoId = null;
    let pokemonId = null;
    
    if (accionSeleccionada === 'moviment') {
        const botonMovimiento = document.querySelector('.btn-move:not([disabled])');
        if (botonMovimiento) {
            movimientoId = botonMovimiento.dataset.id;
        }
    } else {
        const pokemonSeleccionado = document.querySelector('.team-selector .team-pokemon.active');
        if (pokemonSeleccionado) {
            const index = pokemonSeleccionado.dataset.index;
            const equipoJugador = battleData.esUsuario1 ? battleData.equipo1 : battleData.equipo2;
            if (equipoJugador && equipoJugador[index]) {
                pokemonId = equipoJugador[index].id_equip_pokemon;
            }
        }
    }
    
    // Preparar los datos para la petición
    const formData = new FormData();
    formData.append('action', 'realizar_accion_turno');
    formData.append('batalla_id', BATALLA_ID);
    formData.append('tipus_accio', accionSeleccionada);
    
    if (movimientoId) {
        formData.append('moviment_id', movimientoId);
    }
    
    if (pokemonId) {
        formData.append('pokemon_id', pokemonId);
    }
    
    // Enviar la acción al servidor
    fetch('../Controlador/batalla_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then (data => {
        // Ocultar el overlay de carga
        document.getElementById('loading-overlay').style.display = 'none';
        
        if (data.success) {
            // Mostrar mensaje de éxito
            mostrarMensaje(data.message || 'Acción registrada correctamente', 'success');
            
            // Si el turno se ha completado, actualizar el combate inmediatamente
            if (data.turno_completado) {
                actualizarCombate();
            }
            
            // Actualizar estado del botón según el turno
            battleData.esMiTurno = false;
            MI_TURNO = false;
            actualizarEstadoUI(false);
        } else {
            console.error('Error al registrar acción:', data.message);
            mostrarMensaje('Error: ' + (data.message || 'No se pudo registrar la acción'), 'error');
        }
    })
    .catch(error => {
        document.getElementById('loading-overlay').style.display = 'none';
        console.error('Error en la solicitud:', error);
        mostrarMensaje('Error de conexión al registrar acción', 'error');
    });
}

// Preparar una acción (seleccionarla pero sin enviarla todavía)
function prepararAccion(tipoAccion, movimientoId, pokemonId) {
    console.log(`Preparando acción: ${tipoAccion}, movimiento: ${movimientoId}, pokemon: ${pokemonId}`);
    
    // Verificar que sea nuestro turno
    if (!battleData.esMiTurno) {
        mostrarMensaje('No es tu turno', 'error');
        return;
    }
    
    // Preparar los datos para enviar
    const formData = new FormData();
    formData.append('action', 'preparar_accion');
    formData.append('batalla_id', battleData.idBatalla);
    formData.append('tipus_accio', tipoAccion);
    
    // Añadir los datos específicos según el tipo de acción
    if (tipoAccion === 'moviment' && movimientoId) {
        formData.append('moviment_id', movimientoId);
    }
    
    if (tipoAccion === 'canvi_pokemon' && pokemonId) {
        formData.append('pokemon_id', pokemonId);
    }
    
    // Hacer la petición AJAX para preparar la acción
    fetch('../Controlador/batalla_api.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // La acción se ha preparado correctamente
                console.log('Acción preparada correctamente: ', data);
                
                // Actualizar la interfaz
                actualizarPanelAccionSeleccionada();
                
                // Mostrar mensaje de confirmación
                mostrarMensaje(data.message, 'info');
            } else {
                // Error al preparar la acción
                console.error('Error al preparar acción: ', data.message);
                mostrarMensaje('Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error en la petición AJAX: ', error);
            mostrarMensaje('Error al preparar la acción', 'error');
        });
}

// Realiza la acción de rendición del jugador
function rendirse() {
    const formData = new FormData();
    formData.append('action', 'rendicion');
    formData.append('batalla_id', battleData.idBatalla);
    
    fetch('../Controlador/batalla_api.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarMensaje('Te has rendido. Redirigiendo...', 'info');
                
                // Redirigir al usuario a la página de inicio
                setTimeout(() => {
                    window.location.href = '../index.php';
                }, 2000);
            } else {
                mostrarMensaje('Error al rendirse: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error en la petición AJAX: ', error);
            mostrarMensaje('Error al procesar la rendición', 'error');
        });
}

// Función para seleccionar Pokémon inicial al comenzar la batalla
function seleccionarPokemonInicial(pokemonId) {
    console.log('Seleccionando Pokémon inicial:', pokemonId);
    
    // Mostrar el overlay de carga
    document.getElementById('loading-overlay').style.display = 'flex';
    document.getElementById('loading-overlay').querySelector('p').textContent = 'Enviando selección...';
    
    // Enviar la selección al servidor
    fetch('../Controlador/batalla_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=seleccionar_pokemon_inicial&batalla_id=${BATALLA_ID}&pokemon_id=${pokemonId}`
    })
    .then(response => response.json())
    .then(data => {
        // Ocultar el overlay de carga
        document.getElementById('loading-overlay').style.display = 'none';
        
        if (data.success) {
            // Ocultar el modal de selección
            const modal = document.getElementById('pokemon-inicial-modal');
            if (modal) {
                document.body.removeChild(modal);
            }
            
            // Actualizar el estado de la batalla en el cliente
            if (ES_USUARIO1) {
                battleData.pokemonActivo1 = pokemonId;
            } else if (ES_USUARIO2) {
                battleData.pokemonActivo2 = pokemonId;
            }
            
            // Mostrar mensaje de éxito
            mostrarMensaje('Has seleccionado tu Pokémon inicial. ¡Comienza la batalla!', 'success');
            
            // Actualizar inmediatamente para reflejar los cambios
            actualizarCombate();
        } else {
            console.error('Error al seleccionar Pokémon inicial:', data.message);
            mostrarMensaje(`Error: ${data.message || 'No se pudo seleccionar el Pokémon'}`, 'error');
        }
    })
    .catch(error => {
        document.getElementById('loading-overlay').style.display = 'none';
        console.error('Error en la solicitud de selección de Pokémon inicial:', error);
        mostrarMensaje('Error de conexión al seleccionar Pokémon inicial', 'error');
    });
}

// Función para mostrar mensajes al usuario
function mostrarMensaje(mensaje, tipo = 'info') {
    // Obtener el contenedor de mensajes, o crearlo si no existe
    let mensajesPanel = document.querySelector('.messages-panel');
    if (!mensajesPanel) {
        mensajesPanel = document.createElement('div');
        mensajesPanel.className = 'messages-panel';
        
        // Buscar dónde insertar el panel de mensajes
        const mainContainer = document.querySelector('.combat-main-container');
        if (mainContainer) {
            mainContainer.appendChild(mensajesPanel);
        } else {
            // Si no hay un contenedor principal definido, añadirlo al cuerpo del documento
            document.body.appendChild(mensajesPanel);
        }
    }
    
    // Crear el elemento de mensaje
    const mensajeElement = document.createElement('div');
    mensajeElement.className = `combat-message ${tipo}`;
    mensajeElement.textContent = mensaje;
    
    // Añadir al panel de mensajes
    mensajesPanel.appendChild(mensajeElement);
    
    // Scroll hacia el mensaje más reciente
    mensajesPanel.scrollTop = mensajesPanel.scrollHeight;
    
    // Configurar eliminación automática después de 5 segundos para mensajes de éxito
    if (tipo === 'success') {
        setTimeout(() => {
            mensajesPanel.removeChild(mensajeElement);
        }, 5000);
    }
}

// Función para actualizar un Pokémon en la batalla
function actualizarPokemon(pokemonId, posicion) {
    console.log(`Actualizando Pokémon ID ${pokemonId} en posición ${posicion}`);
    
    // Determinar el equipo al que pertenece el Pokémon
    let equipo;
    if ((posicion === 'player' && ES_USUARIO1) || (posicion === 'opponent' && ES_USUARIO2)) {
        equipo = battleData.equipo1;
    } else {
        equipo = battleData.equipo2;
    }
    
    // Buscar el Pokémon en el equipo
    const pokemon = equipo.find(p => p.id_equip_pokemon == pokemonId);
    if (!pokemon) {
        console.error(`No se encontró el Pokémon con ID ${pokemonId} en el equipo`);
        return;
    }
    
    // Actualizar la interfaz según la posición
    const selector = posicion === 'player' ? '.player-area' : '.opponent-area';
    mostrarPokemonActivo(equipo, pokemonId, selector);
    
    // Si es el Pokémon del jugador, actualizar también los movimientos
    if (posicion === 'player') {
        cargarMovimientosPokemon(equipo, pokemonId);
    }
}

// Función para mostrar las acciones en el historial de la batalla
function mostrarAccionesHistorial(acciones) {
    // Obtener el panel de mensajes
    const mensajesPanel = document.querySelector('.messages-panel');
    if (!mensajesPanel) {
        console.error('No se encontró el panel de mensajes');
        return;
    }
    
    // Procesar cada acción nueva
    acciones.forEach(accion => {
        // Crear elemento de mensaje
        const mensajeElement = document.createElement('div');
        mensajeElement.className = `combat-message ${accion.tipo || 'info'}`;
        mensajeElement.textContent = accion.mensaje || accion.texto || 'Acción sin descripción';
        
        // Añadir al panel
        mensajesPanel.appendChild(mensajeElement);
    });
    
    // Scroll hacia el último mensaje
    mensajesPanel.scrollTop = mensajesPanel.scrollHeight;
}

// Función para mostrar el resultado final de la batalla
function mostrarResultadoBatalla(dataBatalla) {
    // Crear mensaje según el resultado
    let mensaje = 'La batalla ha terminado. ';
    let tipo = 'info';
    
    // Determinar si ganamos, perdimos o fue un empate
    if (dataBatalla.guanyador_id) {
        if ((ES_USUARIO1 && dataBatalla.guanyador_id == USUARIO1_ID) ||
            (ES_USUARIO2 && dataBatalla.guanyador_id == USUARIO2_ID)) {
            mensaje += '¡Has ganado! Felicidades.';
            tipo = 'success';
        } else if ((ES_USUARIO1 && dataBatalla.guanyador_id == USUARIO2_ID) ||
                  (ES_USUARIO2 && dataBatalla.guanyador_id == USUARIO1_ID)) {
            mensaje += 'Has perdido. ¡Mejor suerte la próxima vez!';
            tipo = 'error';
        } else {
            // Caso para espectadores
            const nombreGanador = dataBatalla.guanyador_id == USUARIO1_ID ? 
                dataBatalla.nombre_usuari1 : dataBatalla.nombre_usuari2;
            mensaje += `${nombreGanador} ha ganado la batalla.`;
        }
    } else {
        mensaje += 'La batalla ha finalizado en empate.';
    }
    
    // Crear modal con resultado
    const modalOverlay = document.createElement('div');
    modalOverlay.className = 'modal-overlay';
    modalOverlay.id = 'resultado-batalla-modal';
    
    const modalContent = document.createElement('div');
    modalContent.className = `modal-content ${tipo}`;
    
    const titulo = document.createElement('h2');
    titulo.textContent = '¡Combate finalizado!';
    
    const mensajeElement = document.createElement('p');
    mensajeElement.textContent = mensaje;
    
    const btnVolver = document.createElement('button');
    btnVolver.className = 'btn btn-primary';
    btnVolver.textContent = 'Volver a la página principal';
    btnVolver.addEventListener('click', () => {
        window.location.href = '../index.php';
    });
    
    // Ensamblar modal
    modalContent.appendChild(titulo);
    modalContent.appendChild(mensajeElement);
    modalContent.appendChild(btnVolver);
    modalOverlay.appendChild(modalContent);
    
    // Mostrar modal
    document.body.appendChild(modalOverlay);
    
    // También añadir el mensaje al panel de mensajes
    mostrarMensaje(mensaje, tipo);
}

// Función para mostrar el selector de Pokémon inicial
function mostrarSelectorPokemonInicial() {
    // Determinar el equipo del jugador
    let equipoJugador;
    
    if (ES_USUARIO1) {
        equipoJugador = battleData.equipo1;
    } else if (ES_USUARIO2) {
        equipoJugador = battleData.equipo2;
    } else {
        console.error('No se puede mostrar selector de Pokémon inicial en modo espectador');
        return;
    }
    
    if (!equipoJugador || equipoJugador.length === 0) {
        console.error('No hay Pokémon disponibles para seleccionar');
        return;
    }
    
    // Crear el modal para seleccionar el Pokémon inicial
    const modalOverlay = document.createElement('div');
    modalOverlay.className = 'modal-overlay';
    modalOverlay.id = 'pokemon-inicial-modal';
    
    const modalContent = document.createElement('div');
    modalContent.className = 'modal-content';
    
    const titulo = document.createElement('h2');
    titulo.textContent = '¡Selecciona tu Pokémon inicial!';
    
    const mensaje = document.createElement('p');
    mensaje.textContent = 'Elige el Pokémon con el que iniciarás el combate:';
    
    const pokemonList = document.createElement('div');
    pokemonList.className = 'pokemon-initial-selector';
    
    // Añadir cada Pokémon al selector
    equipoJugador.forEach(pokemon => {
        if (pokemon.hp_actual > 0) {  // Solo mostrar Pokémon con HP
            const pokemonItem = document.createElement('div');
            pokemonItem.className = 'pokemon-initial-item';
            pokemonItem.dataset.id = pokemon.id_equip_pokemon;
            
            pokemonItem.innerHTML = `
                <img src="${pokemon.sprite}" alt="${pokemon.malnom}" class="pokemon-initial-sprite">
                <div class="pokemon-initial-info">
                    <span class="pokemon-initial-name">${pokemon.malnom}</span>
                    <span class="pokemon-initial-level">Nv. ${pokemon.nivell}</span>
                    <div class="pokemon-initial-hp-bar">
                        <div class="hp-bar-inner" style="width: ${(pokemon.hp_actual / pokemon.hp_max) * 100}%;"></div>
                    </div>
                    <span class="pokemon-initial-hp">${pokemon.hp_actual}/${pokemon.hp_max} PS</span>
                </div>
            `;
            
            // Añadir evento para seleccionar este Pokémon
            pokemonItem.addEventListener('click', () => {
                seleccionarPokemonInicial(pokemon.id_equip_pokemon);
            });
            
            pokemonList.appendChild(pokemonItem);
        }
    });
    
    // Ensamblar el modal
    modalContent.appendChild(titulo);
    modalContent.appendChild(mensaje);
    modalContent.appendChild(pokemonList);
    modalOverlay.appendChild(modalContent);
    
    // Añadir el modal al DOM
    document.body.appendChild(modalOverlay);
}

// Función para seleccionar un Pokémon inicial
function seleccionarPokemonInicial(pokemonId) {
    console.log('Seleccionando Pokémon inicial:', pokemonId);
    
    // Mostrar el overlay de carga
    document.getElementById('loading-overlay').style.display = 'flex';
    document.getElementById('loading-overlay').querySelector('p').textContent = 'Enviando selección...';
    
    // Enviar la selección al servidor
    fetch('../Controlador/batalla_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=seleccionar_pokemon_inicial&batalla_id=${BATALLA_ID}&pokemon_id=${pokemonId}`
    })
    .then(response => response.json())
    .then(data => {
        // Ocultar el overlay de carga
        document.getElementById('loading-overlay').style.display = 'none';
        
        if (data.success) {
            // Ocultar el modal de selección
            const modal = document.getElementById('pokemon-inicial-modal');
            if (modal) {
                document.body.removeChild(modal);
            }
            
            // Actualizar el estado de la batalla en el cliente
            if (ES_USUARIO1) {
                battleData.pokemonActivo1 = pokemonId;
            } else if (ES_USUARIO2) {
                battleData.pokemonActivo2 = pokemonId;
            }
            
            // Mostrar mensaje de éxito
            mostrarMensaje('Has seleccionado tu Pokémon inicial. ¡Comienza la batalla!', 'success');
            
            // Actualizar inmediatamente para reflejar los cambios
            actualizarCombate();
        } else {
            console.error('Error al seleccionar Pokémon inicial:', data.message);
            mostrarMensaje(`Error: ${data.message || 'No se pudo seleccionar el Pokémon'}`, 'error');
        }
    })
    .catch(error => {
        document.getElementById('loading-overlay').style.display = 'none';
        console.error('Error en la solicitud de selección de Pokémon inicial:', error);
        mostrarMensaje('Error de conexión al seleccionar Pokémon inicial', 'error');
    });
}

// Función para mostrar mensajes al usuario
function mostrarMensaje(mensaje, tipo = 'info') {
    // Obtener el contenedor de mensajes, o crearlo si no existe
    let mensajesPanel = document.querySelector('.messages-panel');
    if (!mensajesPanel) {
        mensajesPanel = document.createElement('div');
        mensajesPanel.className = 'messages-panel';
        
        // Buscar dónde insertar el panel de mensajes
        const mainContainer = document.querySelector('.combat-main-container');
        if (mainContainer) {
            mainContainer.appendChild(mensajesPanel);
        } else {
            // Si no hay un contenedor principal definido, añadirlo al cuerpo del documento
            document.body.appendChild(mensajesPanel);
        }
    }
    
    // Crear el elemento de mensaje
    const mensajeElement = document.createElement('div');
    mensajeElement.className = `combat-message ${tipo}`;
    mensajeElement.textContent = mensaje;
    
    // Añadir al panel de mensajes
    mensajesPanel.appendChild(mensajeElement);
    
    // Scroll hacia el mensaje más reciente
    mensajesPanel.scrollTop = mensajesPanel.scrollHeight;
    
    // Configurar eliminación automática después de 5 segundos para mensajes de éxito
    if (tipo === 'success') {
        setTimeout(() => {
            mensajesPanel.removeChild(mensajeElement);
        }, 5000);
    }
}

// Función para actualizar un Pokémon en la batalla
function actualizarPokemon(pokemonId, posicion) {
    console.log(`Actualizando Pokémon ID ${pokemonId} en posición ${posicion}`);
    
    // Determinar el equipo al que pertenece el Pokémon
    let equipo;
    if ((posicion === 'player' && ES_USUARIO1) || (posicion === 'opponent' && ES_USUARIO2)) {
        equipo = battleData.equipo1;
    } else {
        equipo = battleData.equipo2;
    }
    
    // Buscar el Pokémon en el equipo
    const pokemon = equipo.find(p => p.id_equip_pokemon == pokemonId);
    if (!pokemon) {
        console.error(`No se encontró el Pokémon con ID ${pokemonId} en el equipo`);
        return;
    }
    
    // Actualizar la interfaz según la posición
    const selector = posicion === 'player' ? '.player-area' : '.opponent-area';
    mostrarPokemonActivo(equipo, pokemonId, selector);
    
    // Si es el Pokémon del jugador, actualizar también los movimientos
    if (posicion === 'player') {
        cargarMovimientosPokemon(equipo, pokemonId);
    }
}

// Función para mostrar las acciones en el historial de la batalla
function mostrarAccionesHistorial(acciones) {
    // Obtener el panel de mensajes
    const mensajesPanel = document.querySelector('.messages-panel');
    if (!mensajesPanel) {
        console.error('No se encontró el panel de mensajes');
        return;
    }
    
    // Procesar cada acción nueva
    acciones.forEach(accion => {
        // Crear elemento de mensaje
        const mensajeElement = document.createElement('div');
        mensajeElement.className = `combat-message ${accion.tipo || 'info'}`;
        mensajeElement.textContent = accion.mensaje || accion.texto || 'Acción sin descripción';
        
        // Añadir al panel
        mensajesPanel.appendChild(mensajeElement);
    });
    
    // Scroll hacia el último mensaje
    mensajesPanel.scrollTop = mensajesPanel.scrollHeight;
}

// Función para mostrar el resultado final de la batalla
function mostrarResultadoBatalla(dataBatalla) {
    // Crear mensaje según el resultado
    let mensaje = 'La batalla ha terminado. ';
    let tipo = 'info';
    
    // Determinar si ganamos, perdimos o fue un empate
    if (dataBatalla.guanyador_id) {
        if ((ES_USUARIO1 && dataBatalla.guanyador_id == USUARIO1_ID) ||
            (ES_USUARIO2 && dataBatalla.guanyador_id == USUARIO2_ID)) {
            mensaje += '¡Has ganado! Felicidades.';
            tipo = 'success';
        } else if ((ES_USUARIO1 && dataBatalla.guanyador_id == USUARIO2_ID) ||
                  (ES_USUARIO2 && dataBatalla.guanyador_id == USUARIO1_ID)) {
            mensaje += 'Has perdido. ¡Mejor suerte la próxima vez!';
            tipo = 'error';
        } else {
            // Caso para espectadores
            const nombreGanador = dataBatalla.guanyador_id == USUARIO1_ID ? 
                dataBatalla.nombre_usuari1 : dataBatalla.nombre_usuari2;
            mensaje += `${nombreGanador} ha ganado la batalla.`;
        }
    } else {
        mensaje += 'La batalla ha finalizado en empate.';
    }
    
    // Crear modal con resultado
    const modalOverlay = document.createElement('div');
    modalOverlay.className = 'modal-overlay';
    modalOverlay.id = 'resultado-batalla-modal';
    
    const modalContent = document.createElement('div');
    modalContent.className = `modal-content ${tipo}`;
    
    const titulo = document.createElement('h2');
    titulo.textContent = '¡Combate finalizado!';
    
    const mensajeElement = document.createElement('p');
    mensajeElement.textContent = mensaje;
    
    const btnVolver = document.createElement('button');
    btnVolver.className = 'btn btn-primary';
    btnVolver.textContent = 'Volver a la página principal';
    btnVolver.addEventListener('click', () => {
        window.location.href = '../index.php';
    });
    
    // Ensamblar modal
    modalContent.appendChild(titulo);
    modalContent.appendChild(mensajeElement);
    modalContent.appendChild(btnVolver);
    modalOverlay.appendChild(modalContent);
    
    // Mostrar modal
    document.body.appendChild(modalOverlay);
    
    // También añadir el mensaje al panel de mensajes
    mostrarMensaje(mensaje, tipo);
}

// Nueva función para actualizar el panel que muestra la acción seleccionada
function actualizarPanelAccionSeleccionada() {
    const accionPanel = document.getElementById('accion-seleccionada');
    const btnConfirmar = document.getElementById('btn-confirmar-accion');
    
    if (!accionPanel || !btnConfirmar) return;
    
    if (battleData.accionSeleccionada) {
        // Si hay una acción seleccionada, mostrar información
        let mensaje = '';
        
        switch (battleData.accionSeleccionada.tipo_accion) {
            case 'moviment':
                // Buscar el nombre del movimiento en el Pokémon activo
                let nombreMovimiento = 'Desconocido';
                let pokemonActivo = null;
                
                if (ES_USUARIO1 && battleData.pokemonActivo1) {
                    pokemonActivo = battleData.equipo1.find(p => p.id_pokemon === battleData.pokemonActivo1);
                } else if (ES_USUARIO2 && battleData.pokemonActivo2) {
                    pokemonActivo = battleData.equipo2.find(p => p.id_pokemon === battleData.pokemonActivo2);
                }
                
                if (pokemonActivo && pokemonActivo.moviments) {
                    const movimiento = pokemonActivo.moviments.find(m => m.id === battleData.accionSeleccionada.movimiento_id);
                    if (movimiento) {
                        nombreMovimiento = movimiento.nombre;
                    }
                }
                
                mensaje = `Has seleccionado usar el movimiento: ${nombreMovimiento}`;
                break;
                
            case 'canvi_pokemon':
                // Buscar el nombre del Pokémon seleccionado
                let nombrePokemon = 'Desconocido';
                const equipoPropio = ES_USUARIO1 ? battleData.equipo1 : battleData.equipo2;
                const pokemon = equipoPropio.find(p => p.id_pokemon === battleData.accionSeleccionada.pokemon_id);
                
                if (pokemon) {
                    nombrePokemon = pokemon.nombre;
                }
                
                mensaje = `Has seleccionado cambiar a: ${nombrePokemon}`;
                break;
                
            default:
                mensaje = 'Acción seleccionada';
        }
        
        accionPanel.textContent = mensaje;
        accionPanel.style.display = 'block';
        btnConfirmar.style.display = 'block';
    } else {
        // Si no hay acción seleccionada, ocultar panel y botón
        accionPanel.style.display = 'none';
        btnConfirmar.style.display = 'none';
    }
}

// Actualiza el estado de los elementos de la interfaz según si es nuestro turno o no
function actualizarEstadoUI(esMiTurno) {
    // Actualizar la visibilidad y estado de los selectores de movimientos y Pokémon
    const botonesMovimientos = document.querySelectorAll('.move-button');
    const botonesPokemon = document.querySelectorAll('.team-pokemon');
    const btnConfirmar = document.getElementById('btn-confirmar-accion');
    
    // Habilitar/deshabilitar botones según si es nuestro turno
    botonesMovimientos.forEach(boton => {
        boton.disabled = !esMiTurno;
        if (esMiTurno) {
            boton.classList.remove('disabled');
        } else {
            boton.classList.add('disabled');
        }
    });
    
    botonesPokemon.forEach(boton => {
        boton.disabled = !esMiTurno;
        if (esMiTurno) {
            boton.classList.remove('disabled');
        } else {
            boton.classList.add('disabled');
        }
    });
    
    // Actualizar el estado del botón de confirmación
    if (btnConfirmar) {
        btnConfirmar.disabled = !esMiTurno || !battleData.accionSeleccionada;
    }
    
    console.log('Actualizando UI, es mi turno: ' + esMiTurno + 
               ' modo espectador: ' + MODO_ESPECTADOR + 
               ' estado batalla: ' + battleData.estado);
}

// Carga los datos de los Pokémon en la interfaz
function cargarDatosPokemon() {
    console.log('Cargando datos de Pokémon: ', battleData);
    
    // Determinar el equipo del jugador y el del oponente
    let equipoJugador, equipoOponente;
    
    if (ES_USUARIO1) {
        equipoJugador = battleData.equipo1;
        equipoOponente = battleData.equipo2;
    } else if (ES_USUARIO2) {
        equipoJugador = battleData.equipo2;
        equipoOponente = battleData.equipo1;
    } else if (MODO_ESPECTADOR) {
        // En modo espectador mostramos los datos completos de ambos equipos
        equipoJugador = battleData.equipo2;  // Por convenio mostramos el equipo 2 abajo
        equipoOponente = battleData.equipo1; // Y el equipo 1 arriba
    }
    
    if (!equipoJugador || !equipoOponente) {
        console.error('No se han podido determinar los equipos');
        return;
    }
    
    // Mostrar el Pokémon activo del jugador en el área del jugador
    const pokemonActivoJugadorId = ES_USUARIO1 ? battleData.pokemonActivo1 : 
                                 (ES_USUARIO2 ? battleData.pokemonActivo2 : battleData.pokemonActivo2);
    mostrarPokemonActivo(equipoJugador, pokemonActivoJugadorId, 'player-area');
    
    // Mostrar el Pokémon activo del oponente en el área del oponente
    const pokemonActivoOponenteId = ES_USUARIO1 ? battleData.pokemonActivo2 : 
                                  (ES_USUARIO2 ? battleData.pokemonActivo1 : battleData.pokemonActivo1);
    mostrarPokemonActivo(equipoOponente, pokemonActivoOponenteId, 'opponent-area');
    
    // Cargar el selector de equipo
    cargarSelectorEquipo(equipoJugador);
    
    // Cargar los movimientos del Pokémon activo
    cargarMovimientosPokemon(equipoJugador, pokemonActivoJugadorId);
    
    // Ocultar el overlay de carga una vez que todo esté cargado
    document.getElementById('loading-overlay').style.display = 'none';
}

// Muestra los datos de los Pokémon del jugador
function mostrarDatosPokemonJugador(equipoPokemon, tipo) {
    if (!equipoPokemon || equipoPokemon.length === 0) {
        console.log('No hay datos de equipo para mostrar');
        return;
    }
    
    console.log('Mostrando equipo ' + tipo + ': ', equipoPokemon);
    
    // Mostrar el Pokémon activo del jugador
    const pokemonActivo = equipoPokemon.find(p => 
        p.id_pokemon === (ES_USUARIO1 ? battleData.pokemonActivo1 : battleData.pokemonActivo2));
    
    if (pokemonActivo) {
        // Actualizar el Pokémon activo en el campo
        const pokemonActivoElement = document.querySelector('.player-active-pokemon');
        if (pokemonActivoElement) {
            pokemonActivoElement.innerHTML = `
                <img src="${pokemonActivo.imagen_trasera || pokemonActivo.imagen}" alt="${pokemonActivo.nombre}" class="pokemon-sprite back">
                <div class="pokemon-info">
                    <div class="pokemon-name">${pokemonActivo.nombre}</div>
                    <div class="pokemon-level">Nv. ${pokemonActivo.nivel}</div>
                    <div class="pokemon-hp-bar">
                        <div class="hp-bar" style="width: ${(pokemonActivo.ps_actuales / pokemonActivo.ps_max) * 100}%"></div>
                    </div>
                    <div class="pokemon-hp-text">${pokemonActivo.ps_actuales}/${pokemonActivo.ps_max} PS</div>
                </div>
            `;
        }
        
        // Actualizar los movimientos disponibles
        cargarMovimientosPokemon();
    }
    
    // Actualizar el selector de equipo
    cargarSelectorEquipo();
}

// Muestra los datos de los Pokémon del rival
function mostrarDatosPokemonRival(equipoPokemon, tipo) {
    if (!equipoPokemon || equipoPokemon.length === 0) {
        console.log('No hay datos de equipo rival para mostrar');
        return;
    }
    
    console.log('Mostrando equipo ' + tipo + ': ', equipoPokemon);
    
    // Mostrar el Pokémon activo del rival
    const pokemonActivo = equipoPokemon.find(p => 
        p.id_pokemon === (ES_USUARIO1 ? battleData.pokemonActivo2 : battleData.pokemonActivo1));
    
    if (pokemonActivo) {
        // Actualizar el Pokémon activo en el campo
        const pokemonActivoElement = document.querySelector('.opponent-active-pokemon');
        if (pokemonActivoElement) {
            pokemonActivoElement.innerHTML = `
                <img src="${pokemonActivo.imagen}" alt="${pokemonActivo.nombre}" class="pokemon-sprite front">
                <div class="pokemon-info">
                    <div class="pokemon-name">${pokemonActivo.nombre}</div>
                    <div class="pokemon-level">Nv. ${pokemonActivo.nivel}</div>
                    <div class="pokemon-hp-bar">
                        <div class="hp-bar" style="width: ${(pokemonActivo.ps_actuales / pokemonActivo.ps_max) * 100}%"></div>
                    </div>
                    <div class="pokemon-hp-text">${pokemonActivo.ps_actuales}/${pokemonActivo.ps_max} PS</div>
                </div>
            `;
        }
    }
}

// Modo espectador: muestra los datos de ambos equipos
function mostrarDatosPokemonEspectador() {
    // Mostrar el equipo 1 como oponente
    mostrarDatosPokemonRival(battleData.equipo1, 'player1');
    
    // Mostrar el equipo 2 como jugador
    mostrarDatosPokemonJugador(battleData.equipo2, 'player2');
}

// Carga los movimientos del Pokémon activo
function cargarMovimientosPokemon() {
    // Determinar cuál es nuestro Pokémon activo
    let pokemonActivo = null;
    
    if (ES_USUARIO1 && battleData.pokemonActivo1) {
        pokemonActivo = battleData.equipo1.find(p => p.id_pokemon === battleData.pokemonActivo1);
    } else if (ES_USUARIO2 && battleData.pokemonActivo2) {
        pokemonActivo = battleData.equipo2.find(p => p.id_pokemon === battleData.pokemonActivo2);
    }
    
    if (!pokemonActivo) {
        console.log('No hay Pokémon activo para mostrar movimientos');
        return;
    }
    
    // Obtener el contenedor de movimientos
    const movesContainer = document.querySelector('.moves-container');
    if (!movesContainer) return;
    
    // Limpiar el contenedor
    movesContainer.innerHTML = '';
    
    // Añadir cada movimiento disponible
    if (pokemonActivo.moviments && pokemonActivo.moviments.length > 0) {
        pokemonActivo.moviments.forEach(movimiento => {
            const moveButton = document.createElement('button');
            moveButton.className = 'move-button';
            moveButton.dataset.moveId = movimiento.id;
            moveButton.innerHTML = `
                <span class="move-name">${movimiento.nombre}</span>
                <span class="move-type ${movimiento.tipo.toLowerCase()}">${movimiento.tipo}</span>
                <span class="move-pp">${movimiento.pp_actuales}/${movimiento.pp_max} PP</span>
            `;
            
            // Añadir el evento al botón para seleccionar este movimiento
            moveButton.addEventListener('click', function() {
                seleccionarMovimiento(movimiento.id);
            });
            
            // Añadir el botón al contenedor
            movesContainer.appendChild(moveButton);
        });
    } else {
        // Si no hay movimientos, mostrar un mensaje
        movesContainer.innerHTML = '<div class="no-moves">Este Pokémon no tiene movimientos disponibles</div>';
    }
}

// Carga el selector de equipo con los Pokémon disponibles
function cargarSelectorEquipo() {
    // Determinar cuál es nuestro equipo
    let equipoPropio = [];
    
    if (ES_USUARIO1) {
        equipoPropio = battleData.equipo1;
    } else if (ES_USUARIO2) {
        equipoPropio = battleData.equipo2;
    }
    
    if (!equipoPropio || equipoPropio.length === 0) {
        console.log('No hay equipo propio para mostrar');
        return;
    }
    
    // Obtener el contenedor del equipo
    const teamContainer = document.querySelector('.team-container');
    if (!teamContainer) return;
    
    // Limpiar el contenedor
    teamContainer.innerHTML = '';
    
    // Añadir cada Pokémon del equipo
    equipoPropio.forEach(pokemon => {
        const pokemonButton = document.createElement('button');
        pokemonButton.className = 'team-pokemon';
        pokemonButton.dataset.pokemonId = pokemon.id_pokemon;
        
        // Si el Pokémon está debilitado, añadir la clase correspondiente
        if (pokemon.ps_actuales <= 0) {
            pokemonButton.classList.add('fainted');
            pokemonButton.disabled = true;
        }
        
        // Si es el Pokémon activo, marcarlo como seleccionado
        if ((ES_USUARIO1 && pokemon.id_pokemon === battleData.pokemonActivo1) || 
            (ES_USUARIO2 && pokemon.id_pokemon === battleData.pokemonActivo2)) {
            pokemonButton.classList.add('active');
        }
        
        // Estructura del botón de Pokémon
        pokemonButton.innerHTML = `
            <img src="${pokemon.imagen_mini || pokemon.imagen}" alt="${pokemon.nombre}" class="pokemon-mini">
            <div class="pokemon-team-info">
                <span class="team-pokemon-name">${pokemon.nombre}</span>
                <div class="team-pokemon-hp-bar">
                    <div class="hp-bar" style="width: ${(pokemon.ps_actuales / pokemon.ps_max) * 100}%"></div>
                </div>
                <span class="team-pokemon-hp">${pokemon.ps_actuales}/${pokemon.ps_max}</span>
            </div>
        `;
        
        // Añadir el evento al botón para seleccionar este Pokémon
        pokemonButton.addEventListener('click', function() {
            seleccionarPokemonCambio(pokemon.id_pokemon);
        });
        
        // Añadir el botón al contenedor
        teamContainer.appendChild(pokemonButton);
    });
}

// Función para configurar eventos en los botones de movimientos
function configurarEventosMovimientos() {
    console.log('Configurando eventos de movimientos');
    const botonesMovimiento = document.querySelectorAll('.moves-container .btn-move');
    botonesMovimiento.forEach(function(boton) {
        // Eliminar eventos previos si existen
        const clonedBtn = boton.cloneNode(true);
        boton.parentNode.replaceChild(clonedBtn, boton);
        
        // Añadir el evento de clic
        clonedBtn.addEventListener('click', function() {
            if (!this.disabled) {
                const movimientoId = this.getAttribute('data-id');
                console.log('Clic en movimiento:', movimientoId);
                usarMovimiento(movimientoId);
            }
        });
    });
}

// Función para configurar eventos en los botones de selección de Pokémon
function configurarEventosPokemon() {
    console.log('Configurando eventos de selección de Pokémon');
    const botonesPokemon = document.querySelectorAll('.team-selector .team-pokemon');
    botonesPokemon.forEach(function(boton) {
        // Eliminar eventos previos si existen
        const clonedBtn = boton.cloneNode(true);
        boton.parentNode.replaceChild(clonedBtn, boton);
        
        // Añadir el evento de clic
        clonedBtn.addEventListener('click', function() {
            if (!this.classList.contains('active')) { // No cambiar si ya está activo
                const pokemonIndex = this.getAttribute('data-index');
                console.log('Clic en Pokémon:', pokemonIndex);
                
                // Obtener el equipo correcto según el usuario
                const equipoJugador = battleData.esUsuario1 ? battleData.equipo1 : 
                                    (battleData.esUsuario2 ? battleData.equipo2 : null);
                
                if (equipoJugador && equipoJugador[pokemonIndex]) {
                    const pokemonId = equipoJugador[pokemonIndex].id_equip_pokemon;
                    cambiarPokemon(pokemonId);
                }
            }
        });
    });
}

// Seleccionar un movimiento como acción para este turno
function seleccionarMovimiento(movimientoId) {
    console.log('Movimiento seleccionado: ' + movimientoId);
    
    // Solo se puede seleccionar si es nuestro turno
    if (!battleData.esMiTurno || MODO_ESPECTADOR) {
        console.log('No es tu turno o estás en modo espectador');
        return;
    }
    
    // Guardar la acción seleccionada
    battleData.accionSeleccionada = {
        tipo_accion: 'moviment',
        movimiento_id: movimientoId,
        pokemon_id: null
    };
    
    // Actualizar la interfaz para mostrar la acción seleccionada
    actualizarPanelAccionSeleccionada();
}

// Seleccionar un Pokémon para cambiarlo por el activo
function seleccionarPokemonCambio(pokemonId) {
    console.log('Pokémon seleccionado para cambio: ' + pokemonId);
    
    // Solo se puede seleccionar si es nuestro turno
    if (!battleData.esMiTurno || MODO_ESPECTADOR) {
        console.log('No es tu turno o estás en modo espectador');
        return;
    }
    
    // No se puede seleccionar el Pokémon que ya está activo
    if ((ES_USUARIO1 && pokemonId === battleData.pokemonActivo1) || 
        (ES_USUARIO2 && pokemonId === battleData.pokemonActivo2)) {
        console.log('Este Pokémon ya está activo');
        return;
    }
    
    // Guardar la acción seleccionada
    battleData.accionSeleccionada = {
        tipo_accion: 'canvi_pokemon',
        movimiento_id: null,
        pokemon_id: pokemonId
    };
    
    // Actualizar la interfaz para mostrar la acción seleccionada
    actualizarPanelAccionSeleccionada();
}

// Confirmar la acción seleccionada y enviarla al servidor
function confirmarAccion() {
    console.log('Confirmando acción');
    
    // Verificar que sea nuestro turno
    if (!battleData.esMiTurno || MODO_ESPECTADOR) {
        mostrarMensaje('No es tu turno o estás en modo espectador', 'error');
        return;
    }
    
    // Mostrar el overlay de carga
    document.getElementById('loading-overlay').style.display = 'flex';
    document.getElementById('loading-overlay').querySelector('p').textContent = 'Registrando acción...';
    
    // Determinar qué tipo de acción enviar basado en la última interacción del usuario
    const accionSeleccionada = document.querySelector('.team-selector .active') ? 
        'canvi_pokemon' : 'moviment';
    
    // Obtener el ID del movimiento o Pokémon seleccionado
    let movimientoId = null;
    let pokemonId = null;
    
    if (accionSeleccionada === 'moviment') {
        const botonMovimiento = document.querySelector('.btn-move:not([disabled])');
        if (botonMovimiento) {
            movimientoId = botonMovimiento.dataset.id;
        }
    } else {
        const pokemonSeleccionado = document.querySelector('.team-selector .team-pokemon.active');
        if (pokemonSeleccionado) {
            const index = pokemonSeleccionado.dataset.index;
            const equipoJugador = battleData.esUsuario1 ? battleData.equipo1 : battleData.equipo2;
            if (equipoJugador && equipoJugador[index]) {
                pokemonId = equipoJugador[index].id_equip_pokemon;
            }
        }
    }
    
    // Preparar los datos para la petición
    const formData = new FormData();
    formData.append('action', 'realizar_accion_turno');
    formData.append('batalla_id', BATALLA_ID);
    formData.append('tipus_accio', accionSeleccionada);
    
    if (movimientoId) {
        formData.append('moviment_id', movimientoId);
    }
    
    if (pokemonId) {
        formData.append('pokemon_id', pokemonId);
    }
    
    // Enviar la acción al servidor
    fetch('../Controlador/batalla_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then (data => {
        // Ocultar el overlay de carga
        document.getElementById('loading-overlay').style.display = 'none';
        
        if (data.success) {
            // Mostrar mensaje de éxito
            mostrarMensaje(data.message || 'Acción registrada correctamente', 'success');
            
            // Si el turno se ha completado, actualizar el combate inmediatamente
            if (data.turno_completado) {
                actualizarCombate();
            }
            
            // Actualizar estado del botón según el turno
            battleData.esMiTurno = false;
            MI_TURNO = false;
            actualizarEstadoUI(false);
        } else {
            console.error('Error al registrar acción:', data.message);
            mostrarMensaje('Error: ' + (data.message || 'No se pudo registrar la acción'), 'error');
        }
    })
    .catch(error => {
        document.getElementById('loading-overlay').style.display = 'none';
        console.error('Error en la solicitud:', error);
        mostrarMensaje('Error de conexión al registrar acción', 'error');
    });
}

// Preparar una acción (seleccionarla pero sin enviarla todavía)
function prepararAccion(tipoAccion, movimientoId, pokemonId) {
    console.log(`Preparando acción: ${tipoAccion}, movimiento: ${movimientoId}, pokemon: ${pokemonId}`);
    
    // Verificar que sea nuestro turno
    if (!battleData.esMiTurno) {
        mostrarMensaje('No es tu turno', 'error');
        return;
    }
    
    // Preparar los datos para enviar
    const formData = new FormData();
    formData.append('action', 'preparar_accion');
    formData.append('batalla_id', battleData.idBatalla);
    formData.append('tipus_accio', tipoAccion);
    
    // Añadir los datos específicos según el tipo de acción
    if (tipoAccion === 'moviment' && movimientoId) {
        formData.append('moviment_id', movimientoId);
    }
    
    if (tipoAccion === 'canvi_pokemon' && pokemonId) {
        formData.append('pokemon_id', pokemonId);
    }
    
    // Hacer la petición AJAX para preparar la acción
    fetch('../Controlador/batalla_api.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // La acción se ha preparado correctamente
                console.log('Acción preparada correctamente: ', data);
                
                // Actualizar la interfaz
                actualizarPanelAccionSeleccionada();
                
                // Mostrar mensaje de confirmación
                mostrarMensaje(data.message, 'info');
            } else {
                // Error al preparar la acción
                console.error('Error al preparar acción: ', data.message);
                mostrarMensaje('Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error en la petición AJAX: ', error);
            mostrarMensaje('Error al preparar la acción', 'error');
        });
}

// Realiza la acción de rendición del jugador
function rendirse() {
    const formData = new FormData();
    formData.append('action', 'rendicion');
    formData.append('batalla_id', battleData.idBatalla);
    
    fetch('../Controlador/batalla_api.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarMensaje('Te has rendido. Redirigiendo...', 'info');
                
                // Redirigir al usuario a la página de inicio
                setTimeout(() => {
                    window.location.href = '../index.php';
                }, 2000);
            } else {
                mostrarMensaje('Error al rendirse: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error en la petición AJAX: ', error);
            mostrarMensaje('Error al procesar la rendición', 'error');
        });
}

// Función para seleccionar Pokémon inicial al comenzar la batalla
function seleccionarPokemonInicial(pokemonId) {
    console.log('Seleccionando Pokémon inicial:', pokemonId);
    
    // Mostrar el overlay de carga
    document.getElementById('loading-overlay').style.display = 'flex';
    document.getElementById('loading-overlay').querySelector('p').textContent = 'Enviando selección...';
    
    // Enviar la selección al servidor
    fetch('../Controlador/batalla_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=seleccionar_pokemon_inicial&batalla_id=${BATALLA_ID}&pokemon_id=${pokemonId}`
    })
    .then(response => response.json())
    .then(data => {
        // Ocultar el overlay de carga
        document.getElementById('loading-overlay').style.display = 'none';
        
        if (data.success) {
            // Ocultar el modal de selección
            const modal = document.getElementById('pokemon-inicial-modal');
            if (modal) {
                document.body.removeChild(modal);
            }
            
            // Actualizar el estado de la batalla en el cliente
            if (ES_USUARIO1) {
                battleData.pokemonActivo1 = pokemonId;
            } else if (ES_USUARIO2) {
                battleData.pokemonActivo2 = pokemonId;
            }
            
            // Mostrar mensaje de éxito
            mostrarMensaje('Has seleccionado tu Pokémon inicial. ¡Comienza la batalla!', 'success');
            
            // Actualizar inmediatamente para reflejar los cambios
            actualizarCombate();
        } else {
            console.error('Error al seleccionar Pokémon inicial:', data.message);
            mostrarMensaje(`Error: ${data.message || 'No se pudo seleccionar el Pokémon'}`, 'error');
        }
    })
    .catch(error => {
        document.getElementById('loading-overlay').style.display = 'none';
        console.error('Error en la solicitud de selección de Pokémon inicial:', error);
        mostrarMensaje('Error de conexión al seleccionar Pokémon inicial', 'error');
    });
}

// Función para mostrar mensajes al usuario
function mostrarMensaje(mensaje, tipo = 'info') {
    // Obtener el contenedor de mensajes, o crearlo si no existe
    let mensajesPanel = document.querySelector('.messages-panel');
    if (!mensajesPanel) {
        mensajesPanel = document.createElement('div');
        mensajesPanel.className = 'messages-panel';
        
        // Buscar dónde insertar el panel de mensajes
        const mainContainer = document.querySelector('.combat-main-container');
        if (mainContainer) {
            mainContainer.appendChild(mensajesPanel);
        } else {
            // Si no hay un contenedor principal definido, añadirlo al cuerpo del documento
            document.body.appendChild(mensajesPanel);
        }
    }
    
    // Crear el elemento de mensaje
    const mensajeElement = document.createElement('div');
    mensajeElement.className = `combat-message ${tipo}`;
    mensajeElement.textContent = mensaje;
    
    // Añadir al panel de mensajes
    mensajesPanel.appendChild(mensajeElement);
    
    // Scroll hacia el mensaje más reciente
    mensajesPanel.scrollTop = mensajesPanel.scrollHeight;
    
    // Configurar eliminación automática después de 5 segundos para mensajes de éxito
    if (tipo === 'success') {
        setTimeout(() => {
            mensajesPanel.removeChild(mensajeElement);
        }, 5000);
    }
}

// Función para actualizar un Pokémon en la batalla
function actualizarPokemon(pokemonId, posicion) {
    console.log(`Actualizando Pokémon ID ${pokemonId} en posición ${posicion}`);
    
    // Determinar el equipo al que pertenece el Pokémon
    let equipo;
    if ((posicion === 'player' && ES_USUARIO1) || (posicion === 'opponent' && ES_USUARIO2)) {
        equipo = battleData.equipo1;
    } else {
        equipo = battleData.equipo2;
    }
    
    // Buscar el Pokémon en el equipo
    const pokemon = equipo.find(p => p.id_equip_pokemon == pokemonId);
    if (!pokemon) {
        console.error(`No se encontró el Pokémon con ID ${pokemonId} en el equipo`);
        return;
    }
    
    // Actualizar la interfaz según la posición
    const selector = posicion === 'player' ? '.player-area' : '.opponent-area';
    mostrarPokemonActivo(equipo, pokemonId, selector);
    
    // Si es el Pokémon del jugador, actualizar también los movimientos
    if (posicion === 'player') {
        cargarMovimientosPokemon(equipo, pokemonId);
    }
}

// Función para mostrar las acciones en el historial de la batalla
function mostrarAccionesHistorial(acciones) {
    // Obtener el panel de mensajes
    const mensajesPanel = document.querySelector('.messages-panel');
    if (!mensajesPanel) {
        console.error('No se encontró el panel de mensajes');
        return;
    }
    
    // Procesar cada acción nueva
    acciones.forEach(accion => {
        // Crear elemento de mensaje
        const mensajeElement = document.createElement('div');
        mensajeElement.className = `combat-message ${accion.tipo || 'info'}`;
        mensajeElement.textContent = accion.mensaje || accion.texto || 'Acción sin descripción';
        
        // Añadir al panel
        mensajesPanel.appendChild(mensajeElement);
    });
    
    // Scroll hacia el último mensaje
    mensajesPanel.scrollTop = mensajesPanel.scrollHeight;
}

// Función para mostrar el resultado final de la batalla
function mostrarResultadoBatalla(dataBatalla) {
    // Crear mensaje según el resultado
    let mensaje = 'La batalla ha terminado. ';
    let tipo = 'info';
    
    // Determinar si ganamos, perdimos o fue un empate
    if (dataBatalla.guanyador_id) {
        if ((ES_USUARIO1 && dataBatalla.guanyador_id == USUARIO1_ID) ||
            (ES_USUARIO2 && dataBatalla.guanyador_id == USUARIO2_ID)) {
            mensaje += '¡Has ganado! Felicidades.';
            tipo = 'success';
        } else if ((ES_USUARIO1 && dataBatalla.guanyador_id == USUARIO2_ID) ||
                  (ES_USUARIO2 && dataBatalla.guanyador_id == USUARIO1_ID)) {
            mensaje += 'Has perdido. ¡Mejor suerte la próxima vez!';
            tipo = 'error';
        } else {
            // Caso para espectadores
            const nombreGanador = dataBatalla.guanyador_id == USUARIO1_ID ? 
                dataBatalla.nombre_usuari1 : dataBatalla.nombre_usuari2;
            mensaje += `${nombreGanador} ha ganado la batalla.`;
        }
    } else {
        mensaje += 'La batalla ha finalizado en empate.';
    }
    
    // Crear modal con resultado
    const modalOverlay = document.createElement('div');
    modalOverlay.className = 'modal-overlay';
    modalOverlay.id = 'resultado-batalla-modal';
    
    const modalContent = document.createElement('div');
    modalContent.className = `modal-content ${tipo}`;
    
    const titulo = document.createElement('h2');
    titulo.textContent = '¡Combate finalizado!';
    
    const mensajeElement = document.createElement('p');
    mensajeElement.textContent = mensaje;
    
    const btnVolver = document.createElement('button');
    btnVolver.className = 'btn btn-primary';
    btnVolver.textContent = 'Volver a la página principal';
    btnVolver.addEventListener('click', () => {
        window.location.href = '../index.php';
    });
    
    // Ensamblar modal
    modalContent.appendChild(titulo);
    modalContent.appendChild(mensajeElement);
    modalContent.appendChild(btnVolver);
    modalOverlay.appendChild(modalContent);
    
    // Mostrar modal
    document.body.appendChild(modalOverlay);
    
    // También añadir el mensaje al panel de mensajes
    mostrarMensaje(mensaje, tipo);
}

// Nueva función para actualizar el panel que muestra la acción seleccionada
function actualizarPanelAccionSeleccionada() {
    const accionPanel = document.getElementById('accion-seleccionada');
    const btnConfirmar = document.getElementById('btn-confirmar-accion');
    
    if (!accionPanel || !btnConfirmar) return;
    
    if (battleData.accionSeleccionada) {
        // Si hay una acción seleccionada, mostrar información
        let mensaje = '';
        
        switch (battleData.accionSeleccionada.tipo_accion) {
            case 'moviment':
                // Buscar el nombre del movimiento en el Pokémon activo
                let nombreMovimiento = 'Desconocido';
                let pokemonActivo = null;
                
                if (ES_USUARIO1 && battleData.pokemonActivo1) {
                    pokemonActivo = battleData.equipo1.find(p => p.id_pokemon === battleData.pokemonActivo1);
                } else if (ES_USUARIO2 && battleData.pokemonActivo2) {
                    pokemonActivo = battleData.equipo2.find(p => p.id_pokemon === battleData.pokemonActivo2);
                }
                
                if (pokemonActivo && pokemonActivo.moviments) {
                    const movimiento = pokemonActivo.moviments.find(m => m.id === battleData.accionSeleccionada.movimiento_id);
                    if (movimiento) {
                        nombreMovimiento = movimiento.nombre;
                    }
                }
                
                mensaje = `Has seleccionado usar el movimiento: ${nombreMovimiento}`;
                break;
                
            case 'canvi_pokemon':
                // Buscar el nombre del Pokémon seleccionado
                let nombrePokemon = 'Desconocido';
                const equipoPropio = ES_USUARIO1 ? battleData.equipo1 : battleData.equipo2;
                const pokemon = equipoPropio.find(p => p.id_pokemon === battleData.accionSeleccionada.pokemon_id);
                
                if (pokemon) {
                    nombrePokemon = pokemon.nombre;
                }
                
                mensaje = `Has seleccionado cambiar a: ${nombrePokemon}`;
                break;
                
            default:
                mensaje = 'Acción seleccionada';
        }
        
        accionPanel.textContent = mensaje;
        accionPanel.style.display = 'block';
        btnConfirmar.style.display = 'block';
    } else {
        // Si no hay acción seleccionada, ocultar panel y botón
        accionPanel.style.display = 'none';
        btnConfirmar.style.display = 'none';
    }
}

// Actualiza el estado de los elementos de la interfaz según si es nuestro turno o no
function actualizarEstadoUI(esMiTurno) {
    // Actualizar la visibilidad y estado de los selectores de movimientos y Pokémon
    const botonesMovimientos = document.querySelectorAll('.move-button');
    const botonesPokemon = document.querySelectorAll('.team-pokemon');
    const btnConfirmar = document.getElementById('btn-confirmar-accion');
    
    // Habilitar/deshabilitar botones según si es nuestro turno
    botonesMovimientos.forEach(boton => {
        boton.disabled = !esMiTurno;
        if (esMiTurno) {
            boton.classList.remove('disabled');
        } else {
            boton.classList.add('disabled');
        }
    });
    
    botonesPokemon.forEach(boton => {
        boton.disabled = !esMiTurno;
        if (esMiTurno) {
            boton.classList.remove('disabled');
        } else {
            boton.classList.add('disabled');
        }
    });
    
    // Actualizar el estado del botón de confirmación
    if (btnConfirmar) {
        btnConfirmar.disabled = !esMiTurno || !battleData.accionSeleccionada;
    }
    
    console.log('Actualizando UI, es mi turno: ' + esMiTurno + 
               ' modo espectador: ' + MODO_ESPECTADOR + 
               ' estado batalla: ' + battleData.estado);
}

// Carga los datos de los Pokémon en la interfaz
function cargarDatosPokemon() {
    console.log('Cargando datos de Pokémon: ', battleData);
    
    // Determinar el equipo del jugador y el del oponente
    let equipoJugador, equipoOponente;
    
    if (ES_USUARIO1) {
        equipoJugador = battleData.equipo1;
        equipoOponente = battleData.equipo2;
    } else if (ES_USUARIO2) {
        equipoJugador = battleData.equipo2;
        equipoOponente = battleData.equipo1;
    } else if (MODO_ESPECTADOR) {
        // En modo espectador mostramos los datos completos de ambos equipos
        equipoJugador = battleData.equipo2;  // Por convenio mostramos el equipo 2 abajo
        equipoOponente = battleData.equipo1; // Y el equipo 1 arriba
    }
    
    if (!equipoJugador || !equipoOponente) {
        console.error('No se han podido determinar los equipos');
        return;
    }
    
    // Mostrar el Pokémon activo del jugador en el área del jugador
    const pokemonActivoJugadorId = ES_USUARIO1 ? battleData.pokemonActivo1 : 
                                 (ES_USUARIO2 ? battleData.pokemonActivo2 : battleData.pokemonActivo2);
    mostrarPokemonActivo(equipoJugador, pokemonActivoJugadorId, 'player-area');
    
    // Mostrar el Pokémon activo del oponente en el área del oponente
    const pokemonActivoOponenteId = ES_USUARIO1 ? battleData.pokemonActivo2 : 
                                  (ES_USUARIO2 ? battleData.pokemonActivo1 : battleData.pokemonActivo1);
    mostrarPokemonActivo(equipoOponente, pokemonActivoOponenteId, 'opponent-area');
    
    // Cargar el selector de equipo
    cargarSelectorEquipo(equipoJugador);
    
    // Cargar los movimientos del Pokémon activo
    cargarMovimientosPokemon(equipoJugador, pokemonActivoJugadorId);
    
    // Ocultar el overlay de carga una vez que todo esté cargado
    document.getElementById('loading-overlay').style.display = 'none';
}

// Muestra los datos de los Pokémon del jugador
function mostrarDatosPokemonJugador(equipoPokemon, tipo) {
    if (!equipoPokemon || equipoPokemon.length === 0) {
        console.log('No hay datos de equipo para mostrar');
        return;
    }
    
    console.log('Mostrando equipo ' + tipo + ': ', equipoPokemon);
    
    // Mostrar el Pokémon activo del jugador
    const pokemonActivo = equipoPokemon.find(p => 
        p.id_pokemon === (ES_USUARIO1 ? battleData.pokemonActivo1 : battleData.pokemonActivo2));
    
    if (pokemonActivo) {
        // Actualizar el Pokémon activo en el campo
        const pokemonActivoElement = document.querySelector('.player-active-pokemon');
        if (pokemonActivoElement) {
            pokemonActivoElement.innerHTML = `
                <img src="${pokemonActivo.imagen_trasera || pokemonActivo.imagen}" alt="${pokemonActivo.nombre}" class="pokemon-sprite back">
                <div class="pokemon-info">
                    <div class="pokemon-name">${pokemonActivo.nombre}</div>
                    <div class="pokemon-level">Nv. ${pokemonActivo.nivel}</div>
                    <div class="pokemon-hp-bar">
                        <div class="hp-bar" style="width: ${(pokemonActivo.ps_actuales / pokemonActivo.ps_max) * 100}%"></div>
                    </div>
                    <div class="pokemon-hp-text">${pokemonActivo.ps_actuales}/${pokemonActivo.ps_max} PS</div>
                </div>
            `;
        }
        
        // Actualizar los movimientos disponibles
        cargarMovimientosPokemon();
    }
    
    // Actualizar el selector de equipo
    cargarSelectorEquipo();
}

// Muestra los datos de los Pokémon del rival
function mostrarDatosPokemonRival(equipoPokemon, tipo) {
    if (!equipoPokemon || equipoPokemon.length === 0) {
        console.log('No hay datos de equipo rival para mostrar');
        return;
    }
    
    console.log('Mostrando equipo ' + tipo + ': ', equipoPokemon);
    
    // Mostrar el Pokémon activo del rival
    const pokemonActivo = equipoPokemon.find(p => 
        p.id_pokemon === (ES_USUARIO1 ? battleData.pokemonActivo2 : battleData.pokemonActivo1));
    
    if (pokemonActivo) {
        // Actualizar el Pokémon activo en el campo
        const pokemonActivoElement = document.querySelector('.opponent-active-pokemon');
        if (pokemonActivoElement) {
            pokemonActivoElement.innerHTML = `
                <img src="${pokemonActivo.imagen}" alt="${pokemonActivo.nombre}" class="pokemon-sprite front">
                <div class="pokemon-info">
                    <div class="pokemon-name">${pokemonActivo.nombre}</div>
                    <div class="pokemon-level">Nv. ${pokemonActivo.nivel}</div>
                    <div class="pokemon-hp-bar">
                        <div class="hp-bar" style="width: ${(pokemonActivo.ps_actuales / pokemonActivo.ps_max) * 100}%"></div>
                    </div>
                    <div class="pokemon-hp-text">${pokemonActivo.ps_actuales}/${pokemonActivo.ps_max} PS</div>
                </div>
            `;
        }
    }
}

// Modo espectador: muestra los datos de ambos equipos
function mostrarDatosPokemonEspectador() {
    // Mostrar el equipo 1 como oponente
    mostrarDatosPokemonRival(battleData.equipo1, 'player1');
    
    // Mostrar el equipo 2 como jugador
    mostrarDatosPokemonJugador(battleData.equipo2, 'player2');
}

// Carga los movimientos del Pokémon activo
function cargarMovimientosPokemon() {
    // Determinar cuál es nuestro Pokémon activo
    let pokemonActivo = null;
    
    if (ES_USUARIO1 && battleData.pokemonActivo1) {
        pokemonActivo = battleData.equipo1.find(p => p.id_pokemon === battleData.pokemonActivo1);
    } else if (ES_USUARIO2 && battleData.pokemonActivo2) {
        pokemonActivo = battleData.equipo2.find(p => p.id_pokemon === battleData.pokemonActivo2);
    }
    
    if (!pokemonActivo) {
        console.log('No hay Pokémon activo para mostrar movimientos');
        return;
    }
    
    // Obtener el contenedor de movimientos
    const movesContainer = document.querySelector('.moves-container');
    if (!movesContainer) return;
    
    // Limpiar el contenedor
    movesContainer.innerHTML = '';
    
    // Añadir cada movimiento disponible
    if (pokemonActivo.moviments && pokemonActivo.moviments.length > 0) {
        pokemonActivo.moviments.forEach(movimiento => {
            const moveButton = document.createElement('button');
            moveButton.className = 'move-button';
            moveButton.dataset.moveId = movimiento.id;
            moveButton.innerHTML = `
                <span class="move-name">${movimiento.nombre}</span>
                <span class="move-type ${movimiento.tipo.toLowerCase()}">${movimiento.tipo}</span>
                <span class="move-pp">${movimiento.pp_actuales}/${movimiento.pp_max} PP</span>
            `;
            
            // Añadir el evento al botón para seleccionar este movimiento
            moveButton.addEventListener('click', function() {
                seleccionarMovimiento(movimiento.id);
            });
            
            // Añadir el botón al contenedor
            movesContainer.appendChild(moveButton);
        });
    } else {
        // Si no hay movimientos, mostrar un mensaje
        movesContainer.innerHTML = '<div class="no-moves">Este Pokémon no tiene movimientos disponibles</div>';
    }
}

// Carga el selector de equipo con los Pokémon disponibles
function cargarSelectorEquipo() {
    // Determinar cuál es nuestro equipo
    let equipoPropio = [];
    
    if (ES_USUARIO1) {
        equipoPropio = battleData.equipo1;
    } else if (ES_USUARIO2) {
        equipoPropio = battleData.equipo2;
    }
    
    if (!equipoPropio || equipoPropio.length === 0) {
        console.log('No hay equipo propio para mostrar');
        return;
    }
    
    // Obtener el contenedor del equipo
    const teamContainer = document.querySelector('.team-container');
    if (!teamContainer) return;
    
    // Limpiar el contenedor
    teamContainer.innerHTML = '';
    
    // Añadir cada Pokémon del equipo
    equipoPropio.forEach(pokemon => {
        const pokemonButton = document.createElement('button');
        pokemonButton.className = 'team-pokemon';
        pokemonButton.dataset.pokemonId = pokemon.id_pokemon;
        
        // Si el Pokémon está debilitado, añadir la clase correspondiente
        if (pokemon.ps_actuales <= 0) {
            pokemonButton.classList.add('fainted');
            pokemonButton.disabled = true;
        }
        
        // Si es el Pokémon activo, marcarlo como seleccionado
        if ((ES_USUARIO1 && pokemon.id_pokemon === battleData.pokemonActivo1) || 
            (ES_USUARIO2 && pokemon.id_pokemon === battleData.pokemonActivo2)) {
            pokemonButton.classList.add('active');
        }
        
        // Estructura del botón de Pokémon
        pokemonButton.innerHTML = `
            <img src="${pokemon.imagen_mini || pokemon.imagen}" alt="${pokemon.nombre}" class="pokemon-mini">
            <div class="pokemon-team-info">
                <span class="team-pokemon-name">${pokemon.nombre}</span>
                <div class="team-pokemon-hp-bar">
                    <div class="hp-bar" style="width: ${(pokemon.ps_actuales / pokemon.ps_max) * 100}%"></div>
                </div>
                <span class="team-pokemon-hp">${pokemon.ps_actuales}/${pokemon.ps_max}</span>
            </div>
        `;
        
        // Añadir el evento al botón para seleccionar este Pokémon
        pokemonButton.addEventListener('click', function() {
            seleccionarPokemonCambio(pokemon.id_pokemon);
        });
        
        // Añadir el botón al contenedor
        teamContainer.appendChild(pokemonButton);
    });
}

// Función para configurar eventos en los botones de movimientos
function configurarEventosMovimientos() {
    console.log('Configurando eventos de movimientos');
    const botonesMovimiento = document.querySelectorAll('.moves-container .btn-move');
    botonesMovimiento.forEach(function(boton) {
        // Eliminar eventos previos si existen
        const clonedBtn = boton.cloneNode(true);
        boton.parentNode.replaceChild(clonedBtn, boton);
        
        // Añadir el evento de clic
        clonedBtn.addEventListener('click', function() {
            if (!this.disabled) {
                const movimientoId = this.getAttribute('data-id');
                console.log('Clic en movimiento:', movimientoId);
                usarMovimiento(movimientoId);
            }
        });
    });
}

// Función para configurar eventos en los botones de selección de Pokémon
function configurarEventosPokemon() {
    console.log('Configurando eventos de selección de Pokémon');
    const botonesPokemon = document.querySelectorAll('.team-selector .team-pokemon');
    botonesPokemon.forEach(function(boton) {
        // Eliminar eventos previos si existen
        const clonedBtn = boton.cloneNode(true);
        boton.parentNode.replaceChild(clonedBtn, boton);
        
        // Añadir el evento de clic
        clonedBtn.addEventListener('click', function() {
            if (!this.classList.contains('active')) { // No cambiar si ya está activo
                const pokemonIndex = this.getAttribute('data-index');
                console.log('Clic en Pokémon:', pokemonIndex);
                
                // Obtener el equipo correcto según el usuario
                const equipoJugador = battleData.esUsuario1 ? battleData.equipo1 : 
                                    (battleData.esUsuario2 ? battleData.equipo2 : null);
                
                if (equipoJugador && equipoJugador[pokemonIndex]) {
                    const pokemonId = equipoJugador[pokemonIndex].id_equip_pokemon;
                    cambiarPokemon(pokemonId);
                }
            }
        });
    });
}

// Seleccionar un movimiento como acción para este turno
function seleccionarMovimiento(movimientoId) {
    console.log('Movimiento seleccionado: ' + movimientoId);
    
    // Solo se puede seleccionar si es nuestro turno
    if (!battleData.esMiTurno || MODO_ESPECTADOR) {
        console.log('No es tu turno o estás en modo espectador');
        return;
    }
    
    // Guardar la acción seleccionada
    battleData.accionSeleccionada = {
        tipo_accion: 'moviment',
        movimiento_id: movimientoId,
        pokemon_id: null
    };
    
    // Actualizar la interfaz para mostrar la acción seleccionada
    actualizarPanelAccionSeleccionada();
}

// Seleccionar un Pokémon para cambiarlo por el activo
function seleccionarPokemonCambio(pokemonId) {
    console.log('Pokémon seleccionado para cambio: ' + pokemonId);
    
    // Solo se puede seleccionar si es nuestro turno
    if (!battleData.esMiTurno || MODO_ESPECTADOR) {
        console.log('No es tu turno o estás en modo espectador');
        return;
    }
    
    // No se puede seleccionar el Pokémon que ya está activo
    if ((ES_USUARIO1 && pokemonId === battleData.pokemonActivo1) || 
        (ES_USUARIO2 && pokemonId === battleData.pokemonActivo2)) {
        console.log('Este Pokémon ya está activo');
        return;
    }
    
    // Guardar la acción seleccionada
    battleData.accionSeleccionada = {
        tipo_accion: 'canvi_pokemon',
        movimiento_id: null,
        pokemon_id: pokemonId
    };
    
    // Actualizar la interfaz para mostrar la acción seleccionada
    actualizarPanelAccionSeleccionada();
}

// Confirmar la acción seleccionada y enviarla al servidor
function confirmarAccion() {
    console.log('Confirmando acción');
    
    // Verificar que sea nuestro turno
    if (!battleData.esMiTurno || MODO_ESPECTADOR) {
        mostrarMensaje('No es tu turno o estás en modo espectador', 'error');
        return;
    }
    
    // Mostrar el overlay de carga
    document.getElementById('loading-overlay').style.display = 'flex';
    document.getElementById('loading-overlay').querySelector('p').textContent = 'Registrando acción...';
    
    // Determinar qué tipo de acción enviar basado en la última interacción del usuario
    const accionSeleccionada = document.querySelector('.team-selector .active') ? 
        'canvi_pokemon' : 'moviment';
    
    // Obtener el ID del movimiento o Pokémon seleccionado
    let movimientoId = null;
    let pokemonId = null;
    
    if (accionSeleccionada === 'moviment') {
        const botonMovimiento = document.querySelector('.btn-move:not([disabled])');
        if (botonMovimiento) {
            movimientoId = botonMovimiento.dataset.id;
        }
    } else {
        const pokemonSeleccionado = document.querySelector('.team-selector .team-pokemon.active');
        if (pokemonSeleccionado) {
            const index = pokemonSeleccionado.dataset.index;
            const equipoJugador = battleData.esUsuario1 ? battleData.equipo1 : battleData.equipo2;
            if (equipoJugador && equipoJugador[index]) {
                pokemonId = equipoJugador[index].id_equip_pokemon;
            }
        }
    }
    
    // Preparar los datos para la petición
    const formData = new FormData();
    formData.append('action', 'realizar_accion_turno');
    formData.append('batalla_id', BATALLA_ID);
    formData.append('tipus_accio', accionSeleccionada);
    
    if (movimientoId) {
        formData.append('moviment_id', movimientoId);
    }
    
    if (pokemonId) {
        formData.append('pokemon_id', pokemonId);
    }
    
    // Enviar la acción al servidor
    fetch('../Controlador/batalla_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then (data => {
        // Ocultar el overlay de carga
        document.getElementById('loading-overlay').style.display = 'none';
        
        if (data.success) {
            // Mostrar mensaje de éxito
            mostrarMensaje(data.message || 'Acción registrada correctamente', 'success');
            
            // Si el turno se ha completado, actualizar el combate inmediatamente
            if (data.turno_completado) {
                actualizarCombate();
            }
            
            // Actualizar estado del botón según el turno
            battleData.esMiTurno = false;
            MI_TURNO = false;
            actualizarEstadoUI(false);
        } else {
            console.error('Error al registrar acción:', data.message);
            mostrarMensaje('Error: ' + (data.message || 'No se pudo registrar la acción'), 'error');
        }
    })
    .catch(error => {
        document.getElementById('loading-overlay').style.display = 'none';
        console.error('Error en la solicitud:', error);
        mostrarMensaje('Error de conexión al registrar acción', 'error');
    });
}

// Preparar una acción (seleccionarla pero sin enviarla todavía)
function prepararAccion(tipoAccion, movimientoId, pokemonId) {
    console.log(`Preparando acción: ${tipoAccion}, movimiento: ${movimientoId}, pokemon: ${pokemonId}`);
    
    // Verificar que sea nuestro turno
    if (!battleData.esMiTurno) {
        mostrarMensaje('No es tu turno', 'error');
        return;
    }
    
    // Preparar los datos para enviar
    const formData = new FormData();
    formData.append('action', 'preparar_accion');
    formData.append('batalla_id', battleData.idBatalla);
    formData.append('tipus_accio', tipoAccion);
    
    // Añadir los datos específicos según el tipo de acción
    if (tipoAccion === 'moviment' && movimientoId) {
        formData.append('moviment_id', movimientoId);
    }
    
    if (tipoAccion === 'canvi_pokemon' && pokemonId) {
        formData.append('pokemon_id', pokemonId);
    }
    
    // Hacer la petición AJAX para preparar la acción
    fetch('../Controlador/batalla_api.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // La acción se ha preparado correctamente
                console.log('Acción preparada correctamente: ', data);
                
                // Actualizar la interfaz
                actualizarPanelAccionSeleccionada();
                
                // Mostrar mensaje de confirmación
                mostrarMensaje(data.message, 'info');
            } else {
                // Error al preparar la acción
                console.error('Error al preparar acción: ', data.message);
                mostrarMensaje('Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error en la petición AJAX: ', error);
            mostrarMensaje('Error al preparar la acción', 'error');
        });
}

// Realiza la acción de rendición del jugador
function rendirse() {
    const formData = new FormData();
    formData.append('action', 'rendicion');
    formData.append('batalla_id', battleData.idBatalla);
    
    fetch('../Controlador/batalla_api.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarMensaje('Te has rendido. Redirigiendo...', 'info');
                
                // Redirigir al usuario a la página de inicio
                setTimeout(() => {
                    window.location.href = '../index.php';
                }, 2000);
            } else {
                mostrarMensaje('Error al rendirse: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error en la petición AJAX: ', error);
            mostrarMensaje('Error al procesar la rendición', 'error');
        });
}

// Función para seleccionar Pokémon inicial al comenzar la batalla
function seleccionarPokemonInicial(pokemonId) {
    console.log('Seleccionando Pokémon inicial:', pokemonId);
    
    // Mostrar el overlay de carga
    document.getElementById('loading-overlay').style.display = 'flex';
    document.getElementById('loading-overlay').querySelector('p').textContent = 'Enviando selección...';
    
    // Enviar la selección al servidor
    fetch('../Controlador/batalla_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=seleccionar_pokemon_inicial&batalla_id=${BATALLA_ID}&pokemon_id=${pokemonId}`
    })
    .then(response => response.json())
    .then(data => {
        // Ocultar el overlay de carga
        document.getElementById('loading-overlay').style.display = 'none';
        
        if (data.success) {
            // Ocultar el modal de selección
            const modal = document.getElementById('pokemon-inicial-modal');
            if (modal) {
                document.body.removeChild(modal);
            }
            
            // Actualizar el estado de la batalla en el cliente
            if (ES_USUARIO1) {
                battleData.pokemonActivo1 = pokemonId;
            } else if (ES_USUARIO2) {
                battleData.pokemonActivo2 = pokemonId;
            }
            
            // Mostrar mensaje de éxito
            mostrarMensaje('Has seleccionado tu Pokémon inicial. ¡Comienza la batalla!', 'success');
            
            // Actualizar inmediatamente para reflejar los cambios
            actualizarCombate();
        } else {
            console.error('Error al seleccionar Pokémon inicial:', data.message);
            mostrarMensaje(`Error: ${data.message || 'No se pudo seleccionar el Pokémon'}`, 'error');
        }
    })
    .catch(error => {
        document.getElementById('loading-overlay').style.display = 'none';
        console.error('Error en la solicitud de selección de Pokémon inicial:', error);
        mostrarMensaje('Error de conexión al seleccionar Pokémon inicial', 'error');
    });
}

// Función para mostrar el selector de Pokémon inicial
function mostrarSelectorPokemonInicial() {
    // Determinar el equipo del jugador
    let equipoJugador;
    
    if (ES_USUARIO1) {
        equipoJugador = battleData.equipo1;
    } else if (ES_USUARIO2) {
        equipoJugador = battleData.equipo2;
    } else {
        console.error('No se puede mostrar selector de Pokémon inicial en modo espectador');
        return;
    }
    
    if (!equipoJugador || equipoJugador.length === 0) {
        console.error('No hay Pokémon disponibles para seleccionar');
        return;
    }
    
    // Crear el modal para seleccionar el Pokémon inicial
    const modalOverlay = document.createElement('div');
    modalOverlay.className = 'modal-overlay';
    modalOverlay.id = 'pokemon-inicial-modal';
    
    const modalContent = document.createElement('div');
    modalContent.className = 'modal-content';
    
    const titulo = document.createElement('h2');
    titulo.textContent = '¡Selecciona tu Pokémon inicial!';
    
    const mensaje = document.createElement('p');
    mensaje.textContent = 'Elige el Pokémon con el que iniciarás el combate:';
    
    const pokemonList = document.createElement('div');
    pokemonList.className = 'pokemon-initial-selector';
    
    // Añadir cada Pokémon al selector
    equipoJugador.forEach(pokemon => {
        if (pokemon.hp_actual > 0) {  // Solo mostrar Pokémon con HP
            const pokemonItem = document.createElement('div');
            pokemonItem.className = 'pokemon-initial-item';
            pokemonItem.dataset.id = pokemon.id_equip_pokemon;
            
            pokemonItem.innerHTML = `
                <img src="${pokemon.sprite}" alt="${pokemon.malnom}" class="pokemon-initial-sprite">
                <div class="pokemon-initial-info">
                    <span class="pokemon-initial-name">${pokemon.malnom}</span>
                    <span class="pokemon-initial-level">Nv. ${pokemon.nivell}</span>
                    <div class="pokemon-initial-hp-bar">
                        <div class="hp-bar-inner" style="width: ${(pokemon.hp_actual / pokemon.hp_max) * 100}%;"></div>
                    </div>
                    <span class="pokemon-initial-hp">${pokemon.hp_actual}/${pokemon.hp_max} PS</span>
                </div>
            `;
            
            // Añadir evento para seleccionar este Pokémon
            pokemonItem.addEventListener('click', () => {
                seleccionarPokemonInicial(pokemon.id_equip_pokemon);
            });
            
            pokemonList.appendChild(pokemonItem);
        }
    });
    
    // Ensamblar el modal
    modalContent.appendChild(titulo);
    modalContent.appendChild(mensaje);
    modalContent.appendChild(pokemonList);
    modalOverlay.appendChild(modalContent);
    
    // Añadir el modal al DOM
    document.body.appendChild(modalOverlay);
}