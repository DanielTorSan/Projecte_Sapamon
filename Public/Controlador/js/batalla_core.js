/**
 * Batalla Core - Lógica de combate Pokémon
 * Gestiona toda la mecánica de la batalla entre entrenadores
 */

// Variables globales
let batalla = null;       // Datos de la batalla actual
let equipPropio = null;   // Equipo del jugador
let equipRival = null;    // Equipo del rival
let usuarioId = null;     // ID del usuario actual
let esRetador = false;    // Si el usuario es el retador o el retado
let turnoActual = null;   // Información del turno actual
let pokemonActivo = null; // Pokémon activo del jugador
let pokemonRival = null;  // Pokémon activo del rival
let intervaloActualizacion = null; // Para actualizar el estado de la batalla

// Elementos DOM
const elementosMensajes = document.getElementById('mensajes');
const elementoTurnoIndicador = document.getElementById('turno-indicador');
const elementoPokemonJugador = document.getElementById('pokemon-jugador');
const elementoPokemonRival = document.getElementById('pokemon-rival');
const elementoNombrePokemonJugador = document.getElementById('nombre-pokemon-jugador');
const elementoNombrePokemonRival = document.getElementById('nombre-pokemon-rival');
const elementoNivelPokemonJugador = document.getElementById('nivel-pokemon-jugador');
const elementoNivelPokemonRival = document.getElementById('nivel-pokemon-rival');
const elementoHPActualJugador = document.getElementById('hp-actual-jugador');
const elementoHPActualRival = document.getElementById('hp-actual-rival');
const elementoHPTextoJugador = document.getElementById('hp-texto-jugador');
const elementoHPTextoRival = document.getElementById('hp-texto-rival');

// Menús
const menuAcciones = document.querySelector('.menu-acciones');
const menuAtaques = document.getElementById('menu-ataques');
const menuPokemon = document.getElementById('menu-pokemon');
const menuObjetos = document.getElementById('menu-objetos');
const ataquesContainer = document.getElementById('ataques-container');
const equipoContainer = document.getElementById('equipo-container');

// Templates
const ataqueTemplate = document.getElementById('ataque-template');
const pokemonTemplate = document.getElementById('pokemon-template');

// Botones
const btnAtaque = document.getElementById('btn-ataque');
const btnCambiar = document.getElementById('btn-cambiar');
const btnObjeto = document.getElementById('btn-objeto');
const btnHuir = document.getElementById('btn-huir');
const btnVolverAtaques = document.getElementById('btn-volver-ataques');
const btnVolverPokemon = document.getElementById('btn-volver-pokemon');
const btnVolverObjetos = document.getElementById('btn-volver-objetos');

/**
 * Inicializar la batalla con los datos recibidos
 */
function inicializarBatalla(datos) {
    batalla = datos.batalla;
    equipPropio = datos.equipPropi;
    equipRival = datos.equipRival;
    usuarioId = datos.usuarioId;
    esRetador = datos.esRetador;
    
    // Inicializar el primer Pokémon activo (el primero del equipo)
    pokemonActivo = equipPropio.pokemons[0];
    pokemonRival = equipRival.pokemons[0];
    
    // Actualizar la interfaz
    actualizarInterfazBatalla();
    
    // Configurar los eventos
    configurarEventosUI();
    
    // Iniciar ciclo de actualización
    iniciarActualizacionPeriodica();
    
    // Mostrar mensaje de inicio
    mostrarMensaje(`¡La batalla entre ${batalla.retador_nombre} y ${batalla.retado_nombre} ha comenzado!`);
}

/**
 * Actualizar toda la interfaz de la batalla
 */
function actualizarInterfazBatalla() {
    // Actualizar Pokémon del jugador
    actualizarVisualizacionPokemon(pokemonActivo, true);
    
    // Actualizar Pokémon rival
    actualizarVisualizacionPokemon(pokemonRival, false);
    
    // Actualizar indicador de turno
    actualizarIndicadorTurno();
}

/**
 * Actualizar la visualización de un Pokémon
 */
function actualizarVisualizacionPokemon(pokemon, esJugador) {
    if (!pokemon) return;
    
    const elementoPokemon = esJugador ? elementoPokemonJugador : elementoPokemonRival;
    const elementoNombre = esJugador ? elementoNombrePokemonJugador : elementoNombrePokemonRival;
    const elementoNivel = esJugador ? elementoNivelPokemonJugador : elementoNivelPokemonRival;
    const elementoHP = esJugador ? elementoHPActualJugador : elementoHPActualRival;
    const elementoHPTexto = esJugador ? elementoHPTextoJugador : elementoHPTextoRival;
    
    // Poner nombre y nivel
    elementoNombre.textContent = pokemon.nom;
    elementoNivel.textContent = `Nv ${pokemon.nivell}`;
    
    // Actualizar barra de vida
    const porcentajeHP = Math.max(0, Math.min(100, (pokemon.hp_actual / pokemon.hp) * 100));
    elementoHP.style.width = `${porcentajeHP}%`;
    elementoHPTexto.textContent = `${pokemon.hp_actual}/${pokemon.hp}`;
    
    // Actualizar el color de la barra de vida según su porcentaje
    if (porcentajeHP > 50) {
        elementoHP.style.backgroundColor = '#78C850'; // Verde
    } else if (porcentajeHP > 20) {
        elementoHP.style.backgroundColor = '#F8D030'; // Amarillo
    } else {
        elementoHP.style.backgroundColor = '#F08030'; // Rojo
    }
    
    // Actualizar imagen del Pokémon
    elementoPokemon.innerHTML = '';
    const imgPokemon = document.createElement('img');
    
    // Usar sprites diferentes según si es el jugador o el rival
    if (esJugador) {
        imgPokemon.src = pokemon.back_sprite || `https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/back/${pokemon.pokeapi_id}.png`;
    } else {
        imgPokemon.src = pokemon.front_sprite || `https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/${pokemon.pokeapi_id}.png`;
    }
    
    imgPokemon.alt = pokemon.nom;
    elementoPokemon.appendChild(imgPokemon);
    
    // Si el Pokémon tiene algún estado (paralizado, quemado, etc.) mostrarlo
    if (pokemon.estat) {
        const estadoEl = document.createElement('div');
        estadoEl.className = 'estado-pokemon';
        estadoEl.textContent = pokemon.estat;
        elementoPokemon.appendChild(estadoEl);
    }
}

/**
 * Actualizar el indicador de turno
 */
function actualizarIndicadorTurno() {
    // Verificar de quién es el turno
    const esMiTurno = (esRetador && turnoActual?.turno_de === 'retador') || 
                     (!esRetador && turnoActual?.turno_de === 'retado');
    
    if (esMiTurno) {
        elementoTurnoIndicador.textContent = '¡Es tu turno!';
        elementoTurnoIndicador.className = 'turno-indicador mi-turno';
        habilitarControles(true);
    } else {
        elementoTurnoIndicador.textContent = 'Esperando al rival...';
        elementoTurnoIndicador.className = 'turno-indicador turno-rival';
        habilitarControles(false);
    }
}

/**
 * Habilitar o deshabilitar los controles según de quién sea el turno
 */
function habilitarControles(habilitar) {
    const botones = [btnAtaque, btnCambiar, btnObjeto, btnHuir];
    botones.forEach(btn => {
        btn.disabled = !habilitar;
        if (habilitar) {
            btn.classList.remove('disabled');
        } else {
            btn.classList.add('disabled');
        }
    });
}

/**
 * Mostrar un mensaje en el área de mensajes
 */
function mostrarMensaje(mensaje) {
    const mensajeEl = document.createElement('div');
    mensajeEl.className = 'mensaje';
    mensajeEl.textContent = mensaje;
    
    elementosMensajes.appendChild(mensajeEl);
    elementosMensajes.scrollTop = elementosMensajes.scrollHeight;
    
    // Limitar el número de mensajes mostrados
    while (elementosMensajes.children.length > 10) {
        elementosMensajes.removeChild(elementosMensajes.children[0]);
    }
}

/**
 * Configurar los eventos de la interfaz
 */
function configurarEventosUI() {
    // Botón de ataques
    btnAtaque.addEventListener('click', () => {
        mostrarMenuAtaques();
    });
    
    // Botón de cambio de Pokémon
    btnCambiar.addEventListener('click', () => {
        mostrarMenuPokemon();
    });
    
    // Botón de objetos
    btnObjeto.addEventListener('click', () => {
        mostrarMenuObjetos();
    });
    
    // Botón de huir
    btnHuir.addEventListener('click', () => {
        intentarHuir();
    });
    
    // Botones para volver al menú principal
    btnVolverAtaques.addEventListener('click', () => {
        mostrarMenuPrincipal();
    });
    
    btnVolverPokemon.addEventListener('click', () => {
        mostrarMenuPrincipal();
    });
    
    btnVolverObjetos.addEventListener('click', () => {
        mostrarMenuPrincipal();
    });
}

/**
 * Mostrar el menú de ataques
 */
function mostrarMenuAtaques() {
    // Ocultar el menú principal y mostrar el de ataques
    menuAcciones.style.display = 'none';
    menuAtaques.style.display = 'block';
    
    // Limpiar el contenedor de ataques
    ataquesContainer.innerHTML = '';
    
    // Rellenar con los ataques disponibles
    pokemonActivo.moviments.forEach(movimiento => {
        const ataqueEl = ataqueTemplate.content.cloneNode(true);
        
        ataqueEl.querySelector('.ataque-nombre').textContent = movimiento.nom;
        ataqueEl.querySelector('.ataque-tipo').textContent = movimiento.tipo || 'Normal';
        ataqueEl.querySelector('.ataque-pp').textContent = `PP: ${movimiento.pp_restants}/${movimiento.pp}`;
        
        const ataqueItem = ataqueEl.querySelector('.ataque-item');
        
        // Deshabilitar si no hay PP
        if (movimiento.pp_restants <= 0) {
            ataqueItem.classList.add('disabled');
        } else {
            // Evento de click para usar el ataque
            ataqueItem.addEventListener('click', () => {
                realizarAtaque(movimiento.id_movimiento);
            });
        }
        
        ataquesContainer.appendChild(ataqueEl);
    });
}

/**
 * Mostrar el menú de cambio de Pokémon
 */
function mostrarMenuPokemon() {
    // Ocultar el menú principal y mostrar el de Pokémon
    menuAcciones.style.display = 'none';
    menuPokemon.style.display = 'block';
    
    // Limpiar el contenedor de Pokémon
    equipoContainer.innerHTML = '';
    
    // Rellenar con los Pokémon disponibles
    equipPropio.pokemons.forEach(pokemon => {
        const pokemonEl = pokemonTemplate.content.cloneNode(true);
        
        pokemonEl.querySelector('.pokemon-nombre').textContent = pokemon.nom;
        pokemonEl.querySelector('.pokemon-nivel').textContent = `Nv ${pokemon.nivell}`;
        
        // Imagen
        const imgEl = pokemonEl.querySelector('.pokemon-sprite');
        imgEl.src = pokemon.front_sprite || `https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/${pokemon.pokeapi_id}.png`;
        
        // HP
        const hpPercent = Math.max(0, Math.min(100, (pokemon.hp_actual / pokemon.hp) * 100));
        pokemonEl.querySelector('.hp-actual-mini').style.width = `${hpPercent}%`;
        pokemonEl.querySelector('.hp-texto-mini').textContent = `${pokemon.hp_actual}/${pokemon.hp}`;
        
        const pokemonItem = pokemonEl.querySelector('.pokemon-item');
        
        // Deshabilitar si está debilitado o es el activo
        if (pokemon.hp_actual <= 0) {
            pokemonItem.classList.add('debilitado');
        } else if (pokemon.id_equip_pokemon === pokemonActivo.id_equip_pokemon) {
            pokemonItem.classList.add('activo');
        } else {
            // Evento de click para cambiar
            pokemonItem.addEventListener('click', () => {
                cambiarPokemon(pokemon.id_equip_pokemon);
            });
        }
        
        equipoContainer.appendChild(pokemonEl);
    });
}

/**
 * Mostrar el menú de objetos
 */
function mostrarMenuObjetos() {
    // Ocultar el menú principal y mostrar el de objetos
    menuAcciones.style.display = 'none';
    menuObjetos.style.display = 'block';
    
    // En esta implementación no hay objetos disponibles
    // Pero la estructura está lista para añadirlos en el futuro
}

/**
 * Volver al menú principal
 */
function mostrarMenuPrincipal() {
    menuAtaques.style.display = 'none';
    menuPokemon.style.display = 'none';
    menuObjetos.style.display = 'none';
    menuAcciones.style.display = 'flex';
}

/**
 * Realizar un ataque
 */
function realizarAtaque(idAtaque) {
    // Deshabilitar controles mientras se procesa
    habilitarControles(false);
    mostrarMenuPrincipal();
    
    // Mostrar mensaje
    mostrarMensaje(`${pokemonActivo.nom} usa ${pokemonActivo.moviments.find(m => m.id_movimiento === idAtaque)?.nom || 'un ataque'}!`);
    
    // Enviar la acción al servidor
    enviarAccion('moviment', { moviment_id: idAtaque });
}

/**
 * Cambiar de Pokémon
 */
function cambiarPokemon(idPokemon) {
    // Deshabilitar controles mientras se procesa
    habilitarControles(false);
    mostrarMenuPrincipal();
    
    // Buscar el Pokémon seleccionado
    const pokemonSeleccionado = equipPropio.pokemons.find(p => p.id_equip_pokemon === idPokemon);
    if (!pokemonSeleccionado) return;
    
    // Mostrar mensaje
    mostrarMensaje(`¡Vuelve ${pokemonActivo.nom}! ¡Adelante ${pokemonSeleccionado.nom}!`);
    
    // Actualizar localmente (se sobrescribirá en la próxima actualización)
    pokemonActivo = pokemonSeleccionado;
    actualizarVisualizacionPokemon(pokemonActivo, true);
    
    // Enviar la acción al servidor
    enviarAccion('canvi_pokemon', { pokemon_id: idPokemon });
}

/**
 * Intentar huir de la batalla
 */
function intentarHuir() {
    // En una batalla entre entrenadores no se puede huir
    mostrarMensaje('¡No puedes huir de una batalla entre entrenadores!');
}

/**
 * Enviar una acción al servidor
 */
function enviarAccion(tipoAccion, datos) {
    const formData = new FormData();
    formData.append('action', 'realizar_accion_turno');
    formData.append('batalla_id', batalla.id_batalla);
    formData.append('tipus_accio', tipoAccion);
    
    // Añadir datos específicos según el tipo de acción
    if (tipoAccion === 'moviment' && datos.moviment_id) {
        formData.append('moviment_id', datos.moviment_id);
    } else if (tipoAccion === 'canvi_pokemon' && datos.pokemon_id) {
        formData.append('pokemon_id', datos.pokemon_id);
    }
    
    // Deshabilitar controles mientras se procesa la acción
    habilitarControles(false);
    
    fetch('../Controlador/batalla_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarMensaje(`Acción registrada correctamente. Esperando al oponente...`);
        } else {
            mostrarMensaje(`Error: ${data.message}`);
            habilitarControles(true);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarMensaje('Error de conexión al enviar la acción.');
        habilitarControles(true);
    });
}

/**
 * Actualizar el estado de la batalla desde el servidor
 */
function actualizarEstadoBatalla() {
    fetch(`../Controlador/batalla_api.php?action=estado_batalla&sala=${batalla.codi_sala}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const nuevaInfo = data.batalla;
            
            // Actualizar estado de la batalla
            batalla = nuevaInfo.batalla;
            turnoActual = nuevaInfo.estado_batalla.turno;
            
            // Determinar los equipos actualizados
            const equipoRetador = nuevaInfo.equip_retador;
            const equipoRetado = nuevaInfo.equip_retat;
            
            // Actualizar equipos según si somos retador o retado
            if (esRetador) {
                equipPropio = equipoRetador;
                equipRival = equipoRetado;
            } else {
                equipPropio = equipoRetado;
                equipRival = equipoRetador;
            }
            
            // Determinar Pokémon activos
            const estadoPokemon = nuevaInfo.estado_batalla.pokemon_activos;
            if (estadoPokemon) {
                for (let i = 0; i < estadoPokemon.length; i++) {
                    const estadoP = estadoPokemon[i];
                    if (estadoP.usuari_id == usuarioId) {
                        // Buscar el Pokémon activo del jugador
                        pokemonActivo = equipPropio.pokemons.find(
                            p => p.id_equip_pokemon == estadoP.equip_pokemon_id
                        );
                    } else {
                        // Buscar el Pokémon activo del rival
                        pokemonRival = equipRival.pokemons.find(
                            p => p.id_equip_pokemon == estadoP.equip_pokemon_id
                        );
                    }
                }
            }
            
            // Si la batalla terminó
            if (batalla.estat === 'acabada') {
                finalizarBatalla();
                return;
            }
            
            // Actualizar interfaz
            actualizarInterfazBatalla();
        } else {
            mostrarMensaje(`Error: ${data.message}`);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

/**
 * Iniciar la actualización periódica del estado de la batalla
 */
function iniciarActualizacionPeriodica() {
    // Actualizar inmediatamente
    actualizarEstadoBatalla();
    
    // Establecer intervalo de actualización (cada 3 segundos)
    intervaloActualizacion = setInterval(actualizarEstadoBatalla, 3000);
}

/**
 * Finalizar la batalla y mostrar el resultado
 */
function finalizarBatalla() {
    // Detener las actualizaciones
    clearInterval(intervaloActualizacion);
    
    // Determinar si ganamos o perdimos
    const ganador = batalla.guanyador_id;
    const hemosGanado = ganador === usuarioId;
    
    // Mostrar mensaje final
    const mensajeFinal = hemosGanado ? 
        '¡Has ganado la batalla!' : 
        '¡Has perdido la batalla!';
    
    mostrarMensaje(mensajeFinal);
    
    // Deshabilitar controles
    habilitarControles(false);
    
    // Eliminar la sala de batalla en el servidor
    const formData = new FormData();
    formData.append('action', 'finalizar_batalla');
    formData.append('batalla_id', batalla.id_batalla);
    formData.append('ganador_id', ganador);
    
    fetch('../Controlador/batalla_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.error('Error al finalizar la batalla:', data.message);
        } else {
            console.log('Batalla finalizada correctamente en el servidor');
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
    
    // Mostrar botón para regresar
    const btnRegresar = document.createElement('button');
    btnRegresar.className = 'btn-volver';
    btnRegresar.textContent = 'Volver al inicio';
    btnRegresar.addEventListener('click', () => {
        window.location.href = '../index.php';
    });
    
    // Reemplazar los controles con este botón
    menuAcciones.innerHTML = '';
    menuAcciones.appendChild(btnRegresar);
}

// Inicializar la batalla cuando el DOM esté cargado
document.addEventListener('DOMContentLoaded', () => {
    if (typeof datosIniciales !== 'undefined') {
        inicializarBatalla(datosIniciales);
    } else {
        console.error('No se encontraron los datos iniciales de la batalla');
    }
});