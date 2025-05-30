document.addEventListener('DOMContentLoaded', function() {
    // Cache de elementos del DOM
    const pokemonSearch = document.getElementById('pokemon-search');
    const btnBuscarPokemon = document.getElementById('btn-buscar-pokemon');
    const pokemonSearchResults = document.getElementById('pokemon-search-results');
    const equipoPokemonGrid = document.getElementById('equipo-pokemon-grid');
    const movimientosGrid = document.getElementById('movimientos-grid');
    const pokemonSeleccionadoInfo = document.getElementById('pokemon-seleccionado-info');
    const btnGuardarEquipo = document.getElementById('btn-guardar-equipo');
    const btnLimpiarEquipo = document.getElementById('btn-limpiar-equipo');
    const nombreEquipoInput = document.getElementById('nombre-equipo');
    
    // Elementos para el buscador de movimientos
    const movimientoSearch = document.getElementById('movimiento-search');
    const btnBuscarMovimiento = document.getElementById('btn-buscar-movimiento');
    const movimientosSearchResults = document.getElementById('movimientos-search-results');

    // Variables globales
    let equipoActual = {
        nombre: '',
        pokemon: Array(6).fill(null)
    };
    let pokemonSeleccionado = null;
    let pokemonSeleccionadoIndex = -1;
    let ultimaBusqueda = '';
    let ultimaBusquedaMovimiento = '';
    let timeoutId = null;

    // Inicializar
    inicializar();

    function inicializar() {
        // Configurar eventos
        btnBuscarPokemon.addEventListener('click', buscarPokemon);
        pokemonSearch.addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                buscarPokemon();
            } else {
                // Implementar búsqueda con retraso para evitar demasiadas peticiones
                clearTimeout(timeoutId);
                timeoutId = setTimeout(buscarPokemon, 500);
            }
        });

        // Configurar eventos para el buscador de movimientos
        btnBuscarMovimiento.addEventListener('click', buscarMovimientos);
        movimientoSearch.addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                buscarMovimientos();
            } else {
                // Implementar búsqueda con retraso
                clearTimeout(timeoutId);
                timeoutId = setTimeout(buscarMovimientos, 500);
            }
        });

        // Añadir evento para el input de nombre de equipo
        nombreEquipoInput.addEventListener('input', function() {
            guardarEquipoLocal();
        });

        btnGuardarEquipo.addEventListener('click', guardarEquipo);
        btnLimpiarEquipo.addEventListener('click', limpiarEquipo);
        
        // Configurar evento para el botón de limpiar movimientos
        const btnLimpiarMovimientos = document.getElementById('btn-limpiar-movimientos');
        if (btnLimpiarMovimientos) {
            btnLimpiarMovimientos.addEventListener('click', limpiarMovimientosPokemon);
        }
        
        // Configurar evento para el botón de deseleccionar Pokémon
        const btnDeseleccionarPokemon = document.getElementById('btn-deseleccionar-pokemon');
        if (btnDeseleccionarPokemon) {
            btnDeseleccionarPokemon.addEventListener('click', deseleccionarPokemon);
        }
        
        // Configurar evento para el botón de limpiar Pokémon del equipo
        const btnLimpiarPokemonEquipo = document.getElementById('btn-limpiar-pokemon-equipo');
        if (btnLimpiarPokemonEquipo) {
            btnLimpiarPokemonEquipo.addEventListener('click', limpiarPokemonEquipo);
        }

        // Configurar eventos para los slots del equipo
        configurarEventosSlots();

        // Configurar eventos para los botones de eliminar equipo
        configurarBotonesEliminarEquipo();

        // Configurar eventos para los botones de editar equipo
        configurarBotonesEditarEquipo();

        // Configurar eventos para los botones de exportar equipo
        configurarBotonesExportarEquipo();

        // Configurar el botón de importar equipo
        configurarBotonImportar();

        // Cargar equipos existentes si los hay
        cargarEquiposExistentes();

        // Intentar cargar equipo desde localStorage
        cargarEquipoLocal();
    }

    // Función para buscar Pokémon en la API
    function buscarPokemon() {
        const termino = pokemonSearch.value.trim().toLowerCase();
        
        if (!termino || termino === ultimaBusqueda) return;
        
        ultimaBusqueda = termino;
        
        // Mostrar indicador de carga
        pokemonSearchResults.style.display = 'block';
        pokemonSearchResults.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Buscando Pokémon...</div>';

        // Realizar petición a la API
        fetch(`https://pokeapi.co/api/v2/pokemon/${termino}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Pokémon no encontrado');
                }
                return response.json();
            })
            .then(pokemon => {
                mostrarResultadosPokemon([pokemon]);
            })
            .catch(error => {
                // Si no se encuentra por nombre exacto, intentar buscar en la lista
                fetch('https://pokeapi.co/api/v2/pokemon?limit=898')
                    .then(response => response.json())
                    .then(data => {
                        const resultados = data.results.filter(p => 
                            p.name.includes(termino)
                        ).slice(0, 10); // Limitar a 10 resultados
                        
                        if (resultados.length === 0) {
                            pokemonSearchResults.innerHTML = '<div class="no-results">No s\'ha trobat cap Pokémon amb aquest nom.</div>';
                            return;
                        }
                        
                        // Obtener datos detallados de cada resultado
                        Promise.all(
                            resultados.map(pokemon => 
                                fetch(pokemon.url).then(response => response.json())
                            )
                        ).then(mostrarResultadosPokemon);
                    });
            });
    }

    // Función para mostrar resultados de la búsqueda
    function mostrarResultadosPokemon(pokemons) {
        if (!pokemons || pokemons.length === 0) {
            pokemonSearchResults.innerHTML = '<div class="no-results">No s\'ha trobat cap Pokémon.</div>';
            return;
        }

        pokemonSearchResults.innerHTML = '';
        
        pokemons.forEach(pokemon => {
            const pokemonElement = document.createElement('div');
            pokemonElement.className = 'pokemon-result-item';
            pokemonElement.setAttribute('data-pokemon-id', pokemon.id);
            
            // Obtener información del Pokémon
            const nombre = pokemon.name.charAt(0).toUpperCase() + pokemon.name.slice(1);
            const sprite = pokemon.sprites.front_default || 'Vista/assets/img/Poké_Ball_icon.png';
            const tipos = pokemon.types.map(t => t.type.name).join(', ');
            
            // Crear elemento HTML
            pokemonElement.innerHTML = `
                <div class="pokemon-result-sprite">
                    <img src="${sprite}" alt="${nombre}">
                </div>
                <div class="pokemon-result-info">
                    <div class="pokemon-result-name">${nombre}</div>
                    <div class="pokemon-result-number">#${pokemon.id}</div>
                    <div class="pokemon-result-types">${tipos}</div>
                </div>
            `;
            
            // Evento para agregar al equipo
            pokemonElement.addEventListener('click', function() {
                seleccionarPokemonParaEquipo(pokemon);
            });
            
            pokemonSearchResults.appendChild(pokemonElement);
        });
        
        pokemonSearchResults.style.display = 'block';
    }

    // Función para seleccionar un Pokémon y mostrarlo en la primera ranura vacía
    function seleccionarPokemonParaEquipo(pokemon) {
        // Buscar primera ranura vacía
        let ranuraVacia = -1;
        for (let i = 0; i < equipoActual.pokemon.length; i++) {
            if (equipoActual.pokemon[i] === null) {
                ranuraVacia = i;
                break;
            }
        }

        if (ranuraVacia === -1) {
            mostrarNotificacion('El equipo ya está completo. Elimina un Pokémon antes de agregar otro.', 'error');
            return;
        }

        // Crear objeto de Pokémon para el equipo
        const pokemonEquipo = {
            id: pokemon.id,
            nombre: pokemon.name,
            sprite: pokemon.sprites.front_default,
            tipos: pokemon.types.map(t => t.type.name),
            movimientos: [] // Se llenarán más tarde
        };

        // Agregar a la ranura vacía
        equipoActual.pokemon[ranuraVacia] = pokemonEquipo;
        actualizarVistaEquipo();
        
        // Guardar en localStorage
        guardarEquipoLocal();
        
        // Cerrar resultados de búsqueda
        pokemonSearchResults.style.display = 'none';
        pokemonSearch.value = '';
        ultimaBusqueda = '';
        
        // Mostrar notificación
        mostrarNotificacion(`${pokemonEquipo.nombre.charAt(0).toUpperCase() + pokemonEquipo.nombre.slice(1)} añadido al equipo.`, 'success');
    }

    // Función para actualizar la vista del equipo
    function actualizarVistaEquipo() {
        const slots = equipoPokemonGrid.querySelectorAll('.equipo-slot');
        
        slots.forEach((slot, index) => {
            const pokemon = equipoActual.pokemon[index];
            const slotContent = slot.querySelector('.slot-content');
            
            // Limpiar todos los event listeners del slot clonándolo
            const newSlot = slot.cloneNode(false);
            newSlot.appendChild(slotContent.cloneNode(false));
            slot.parentNode.replaceChild(newSlot, slot);
            
            if (pokemon) {
                newSlot.classList.add('filled');
                newSlot.setAttribute('data-pokemon-id', pokemon.id);
                
                const nombrePokemon = pokemon.nombre.charAt(0).toUpperCase() + pokemon.nombre.slice(1);
                const slotContentNew = newSlot.querySelector('.slot-content');
                
                slotContentNew.innerHTML = `
                    <img src="${pokemon.sprite}" alt="${nombrePokemon}" 
                         onerror="this.src='Vista/assets/img/Poké_Ball_icon.png'">
                    <span class="pokemon-name">${nombrePokemon}</span>
                `;
                
                // Añadir botón para eliminar Pokémon
                const removeBtn = document.createElement('button');
                removeBtn.className = 'remove-pokemon-btn';
                removeBtn.innerHTML = '×';
                removeBtn.title = 'Eliminar Pokémon';
                removeBtn.addEventListener('click', function(e) {
                    e.stopPropagation(); // Evitar que el clic se propague al slot
                    eliminarPokemonDelEquipo(index);
                });
                newSlot.appendChild(removeBtn);
                
                // Configurar evento para seleccionar este Pokémon
                newSlot.addEventListener('click', () => seleccionarPokemonSlot(index, pokemon));
            } else {
                newSlot.classList.remove('filled');
                newSlot.removeAttribute('data-pokemon-id');
                
                const slotContentNew = newSlot.querySelector('.slot-content');
                slotContentNew.innerHTML = `
                    <img src="Vista/assets/img/Poké_Ball_icon.png" alt="Ranura buida">
                    <span class="pokemon-name">Ranura ${index + 1}</span>
                `;
            }
        });
    }

    // Función para seleccionar un Pokémon del equipo y mostrar/editar sus movimientos
    function seleccionarPokemonSlot(index, pokemon) {
        // Prevenir comportamiento por defecto para evitar desplazamiento
        if (event) {
            event.preventDefault();
        }
        
        // Limpiar la selección visual previa
        const slots = equipoPokemonGrid.querySelectorAll('.equipo-slot');
        slots.forEach(slot => {
            slot.classList.remove('selected');
        });
        
        // Seleccionar visualmente el slot actual
        slots[index].classList.add('selected');
        
        // Actualizar variables globales
        pokemonSeleccionado = pokemon;
        pokemonSeleccionadoIndex = index;
        
        // Mostrar información del Pokémon seleccionado
        const nombrePokemon = pokemon.nombre.charAt(0).toUpperCase() + pokemon.nombre.slice(1);
        pokemonSeleccionadoInfo.innerHTML = `
            <div class="pokemon-selected-header">
                <img src="${pokemon.sprite}" alt="${nombrePokemon}" class="pokemon-selected-sprite">
                <h4>${nombrePokemon}</h4>
                <div class="pokemon-types">
                    ${pokemon.tipos.map(tipo => `<span class="pokemon-type ${tipo}">${tipo}</span>`).join('')}
                </div>
            </div>
            <p>Selecciona fins a 4 moviments per a aquest Pokémon:</p>
        `;
        
        // Verificar y mostrar los movimientos del Pokémon seleccionado
        if (!pokemon.movimientos) {
            pokemon.movimientos = [];
        }
        
        // Mostrar inmediatamente los slots de movimientos vacíos para este Pokémon
        // para evitar que se muestren los movimientos del Pokémon anterior
        mostrarMovimientosAsignados(pokemon);
        
        // Cargar movimientos disponibles
        cargarMovimientosPokemon(pokemon);
        
        // Añadir depuración para verificar que los movimientos se están manteniendo
        debugPokemonMovimientos();
    }

    // Función para cargar los movimientos disponibles de un Pokémon
    function cargarMovimientosPokemon(pokemon) {
        // Si ya tenemos los movimientos asignados, mostrarlos
        if (pokemon.movimientos && pokemon.movimientos.length > 0) {
            mostrarMovimientosAsignados(pokemon);
        }
        
        // Mostrar indicador de carga
        const movimientosSlots = movimientosGrid.querySelectorAll('.movimiento-slot');
        movimientosSlots.forEach(slot => {
            if (!slot.classList.contains('filled')) {
                slot.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i></div>';
            }
        });
        
        // Mostrar indicador de carga en los resultados de búsqueda
        movimientosSearchResults.style.display = 'block';
        movimientosSearchResults.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Carregant moviments...</div>';
        
        // Iniciar búsqueda de movimientos automáticamente
        // Limpiamos el campo de búsqueda para mostrar todos los movimientos
        movimientoSearch.value = '';
        ultimaBusquedaMovimiento = '';
        buscarMovimientos();
    }

    // Función para mostrar el selector de movimientos
    function mostrarSelectorMovimientos(pokemon) {
        // Ya no usamos el modal, simplemente desplazamos al buscador de movimientos
        movimientoSearch.focus();
        
        // Asegurarnos de que los resultados de búsqueda sean visibles
        if (movimientosSearchResults.style.display === 'none') {
            movimientosSearchResults.style.display = 'block';
            buscarMovimientos(); // Mostrar todos los movimientos disponibles
        }
    }

    // Función para agregar un movimiento al Pokémon seleccionado
    function agregarMovimientoAPokemon(pokemon, moveName) {
        // Inicializar array de movimientos si no existe
        if (!pokemon.movimientos) {
            pokemon.movimientos = [];
        }
        
        // Si ya tenemos 4 movimientos, mostrar error
        if (pokemon.movimientos.length >= 4) {
            mostrarNotificacion('Este Pokémon ya tiene 4 movimientos asignados.', 'error');
            return;
        }
        
        // Hacemos una llamada a la API para obtener los detalles del movimiento por su nombre
        fetch(`https://pokeapi.co/api/v2/move/${moveName}`)
            .then(response => response.json())
            .then(moveData => {
                // Crear objeto de movimiento con datos relevantes
                const nuevoMovimiento = {
                    id: moveData.id,
                    nombre: moveData.name,
                    tipo: moveData.type.name,
                    categoria: moveData.damage_class.name,
                    poder: moveData.power,
                    precision: moveData.accuracy,
                    pp: moveData.pp
                };
                
                // Agregar a la lista de movimientos del Pokémon
                pokemon.movimientos.push(nuevoMovimiento);
                
                // Actualizar UI
                mostrarMovimientosAsignados(pokemon);
                
                // Guardar en localStorage
                guardarEquipoLocal();
                
                // Notificar
                mostrarNotificacion(`Movimiento ${nuevoMovimiento.nombre} añadido a ${pokemon.nombre}.`, 'success');
            })
            .catch(error => {
                console.error('Error al obtener detalles del movimiento:', error);
                mostrarNotificacion('Error al agregar el movimiento.', 'error');
            });
    }

    // Función para actualizar el estado del botón de limpiar movimientos
    function actualizarBotonLimpiarMovimientos() {
        const btnLimpiarMovimientos = document.getElementById('btn-limpiar-movimientos');
        if (btnLimpiarMovimientos) {
            if (pokemonSeleccionado && pokemonSeleccionado.movimientos && pokemonSeleccionado.movimientos.length > 0) {
                btnLimpiarMovimientos.disabled = false;
            } else {
                btnLimpiarMovimientos.disabled = true;
            }
        }
    }

    // Función para mostrar los movimientos asignados a un Pokémon
    function mostrarMovimientosAsignados(pokemon) {
        console.log(`Mostrando ${pokemon.movimientos ? pokemon.movimientos.length : 0} movimientos para ${pokemon.nombre}`);
        
        // Obtener el contenedor de los slots de movimientos
        const movimientosGrid = document.getElementById('movimientos-grid');
        
        // Limpiar completamente el contenedor
        movimientosGrid.innerHTML = '';
        
        // Crear los 4 slots desde cero
        for (let i = 0; i < 4; i++) {
            const slot = document.createElement('div');
            slot.className = 'movimiento-slot empty';
            slot.setAttribute('data-move-slot', i + 1);
            slot.id = `movimiento-slot-${i + 1}`;
            
            // Comprobar si hay un movimiento para este slot
            if (pokemon.movimientos && i < pokemon.movimientos.length) {
                const movimiento = pokemon.movimientos[i];
                
                // Configurar el slot como ocupado
                slot.className = 'movimiento-slot filled';
                slot.setAttribute('data-move-id', movimiento.id);
                
                slot.innerHTML = `
                    <div class="slot-content move-${movimiento.tipo}">
                        <div class="move-info">
                            <div class="move-name">${movimiento.nombre.replace('-', ' ')}</div>
                            <div class="move-type">${movimiento.tipo}</div>
                        </div>
                        <div class="move-power">Poder: ${movimiento.poder || '-'}</div>
                    </div>
                    <button class="remove-move-btn" title="Eliminar movimiento">×</button>
                `;
                
                // Configurar evento para eliminar movimiento después de crear el elemento
                slot.querySelector('.remove-move-btn').addEventListener('click', function(e) {
                    e.stopPropagation();
                    eliminarMovimiento(pokemon, i);
                });
            } else {
                // Slot vacío
                slot.innerHTML = `
                    <div class="slot-content">
                        <span class="add-move-text">+ Afegir moviment</span>
                    </div>
                `;
                
                // Configurar evento para mostrar selector de movimientos
                slot.addEventListener('click', function() {
                    mostrarSelectorMovimientos(pokemon);
                });
            }
            
            // Añadir el slot al grid
            movimientosGrid.appendChild(slot);
        }
        
        // Actualizar estado del botón de limpiar movimientos
        actualizarBotonLimpiarMovimientos();
        
        // Depuración para verificar el estado de los movimientos
        debugPokemonMovimientos();
    }

    // Función para eliminar un movimiento
    function eliminarMovimiento(pokemon, indiceMovimiento) {
        if (confirm('¿Seguro que deseas eliminar este movimiento?')) {
            // Eliminar el movimiento del array
            pokemon.movimientos.splice(indiceMovimiento, 1);
            
            // Actualizar UI
            mostrarMovimientosAsignados(pokemon);
            
            // Guardar en localStorage después de eliminar el movimiento
            guardarEquipoLocal();
            
            // Notificar
            mostrarNotificacion('Movimiento eliminado correctamente.', 'info');
        }
    }

    // Función para configurar eventos en los slots
    function configurarEventosSlots() {
        const slots = equipoPokemonGrid.querySelectorAll('.equipo-slot');
        
        slots.forEach((slot, index) => {
            slot.addEventListener('click', function() {
                const pokemon = equipoActual.pokemon[index];
                
                if (pokemon) {
                    // Si ya hay un Pokémon en este slot, seleccionarlo
                    seleccionarPokemonSlot(index, pokemon);
                } else {
                    // Si el slot está vacío, abrir buscador
                    pokemonSearch.focus();
                }
            });
            
            // Configurar evento para eliminar Pokémon con clic derecho
            slot.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                const pokemon = equipoActual.pokemon[index];
                
                if (pokemon) {
                    if (confirm(`¿Seguro que deseas eliminar a ${pokemon.nombre} del equipo?`)) {
                        equipoActual.pokemon[index] = null;
                        actualizarVistaEquipo();
                        
                        // Si era el Pokémon seleccionado actualmente, limpiar selección
                        if (pokemonSeleccionadoIndex === index) {
                            pokemonSeleccionado = null;
                            pokemonSeleccionadoIndex = -1;
                            pokemonSeleccionadoInfo.innerHTML = '<p>Selecciona un Pokémon de l\'equip per veure i configurar els seus moviments</p>';
                            mostrarMovimientosAsignados({ movimientos: [] });
                        }
                        
                        mostrarNotificacion('Pokémon eliminado del equipo.', 'info');
                    }
                }
            });
        });
    }

    // Función para guardar el equipo
    async function guardarEquipo() {
        const btnGuardar = document.getElementById('btn-guardar-equipo');
        let response = null;
        let textData = null;
        
        try {
            // Deshabilitar botón para evitar múltiples envíos
            if (btnGuardar) btnGuardar.disabled = true;
            
            // Mostrar estado de carga
            mostrarNotificacion('Guardando equipo...', 'info');
            
            // Validar nombre del equipo
            const nombreEquipo = nombreEquipoInput.value.trim();
            if (!nombreEquipo) {
                throw new Error('Debes asignar un nombre al equipo');
            }
            
            // Preparar datos del equipo
            const datosEquipo = {
                nombre: nombreEquipo,
                pokemon: equipoActual.pokemon.filter(p => p !== null).map(pokemon => ({
                    id: pokemon.id,
                    nombre: pokemon.nombre,
                    movimientos: pokemon.movimientos || []
                }))
            };

            // Verificar que hay al menos 1 Pokémon
            if (datosEquipo.pokemon.length === 0) {
                throw new Error('El equipo debe tener al menos 1 Pokémon');
            }

            // Verificar que todos los Pokemon tengan al menos un movimiento
            const pokemonSinMovimientos = datosEquipo.pokemon.filter(p => !p.movimientos || p.movimientos.length === 0);
            if (pokemonSinMovimientos.length > 0) {
                const nombres = pokemonSinMovimientos.map(p => p.nombre.charAt(0).toUpperCase() + p.nombre.slice(1)).join(', ');
                throw new Error(`Los siguientes Pokémon no tienen movimientos asignados: ${nombres}`);
            }

            console.log('Enviando datos de equipo:', JSON.stringify(datosEquipo));
            
            // Verificar si estamos editando un equipo existente
            const estaEditando = btnGuardar.dataset.editando === 'true';
            const equipoId = btnGuardar.dataset.equipoId;
            
            // Construir los parámetros de la petición
            const params = new URLSearchParams({
                action: 'guardar_equipo',
                datos_equipo: JSON.stringify(datosEquipo)
            });
            
            // Si estamos editando, añadir el ID del equipo
            if (estaEditando && equipoId) {
                params.append('equip_id', equipoId);
            }
            
            // Enviar datos al servidor
            response = await fetch('Controlador/gestio_equips.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: params
            });

            // Verificar si la respuesta HTTP es exitosa
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status} ${response.statusText}`);
            }

            // Manejar la respuesta
            textData = await response.text();
            
            // Verificar si la respuesta parece HTML en lugar de JSON (indica un error de PHP)
            if (textData.startsWith('<') || textData.includes('<b>Warning</b>') || textData.includes('<!DOCTYPE')) {
                console.error('Respuesta HTML (posible error de PHP):', textData.substring(0, 500));
                throw new Error('El servidor respondió con un error. Verifica los logs del servidor.');
            }

            // Intentar parsear como JSON
            let jsonData;
            try {
                jsonData = JSON.parse(textData);
            } catch (parseError) {
                console.error('Error al parsear JSON:', parseError);
                console.error('Respuesta recibida:', textData.substring(0, 500));
                throw new Error('La respuesta no es un JSON válido. Contacta con el administrador.');
            }
            
            if (!jsonData.success) {
                // Extraer mensaje de error detallado si existe
                const errorMsg = jsonData.error || 
                              jsonData.message || 
                              'Error desconocido del servidor';
                throw new Error(errorMsg);
            }

            // Éxito - mostrar mensaje y limpiar
            const mensajeExito = estaEditando ? 'Equipo actualizado correctamente' : 'Equipo guardado correctamente';
            mostrarNotificacion(
                jsonData.mensaje || mensajeExito, 
                'success'
            );
            
            limpiarEquipoLocal();
            
            // Recargar después de 1 segundo
            setTimeout(() => window.location.reload(), 1000);

        } catch (error) {
            console.error('Error al guardar equipo:', error);
            
            // Mostrar notificación con el error
            let mensajeError = error.message;
            
            // Simplificar mensajes de error de red
            if (error.message.includes('Failed to fetch')) {
                mensajeError = 'Error de conexión con el servidor';
            }
            
            mostrarNotificacion(`Error al guardar: ${mensajeError}`, 'error');
            
            // Mostrar detalles completos en consola para depuración
            console.group('Detalles del error al guardar equipo');
            console.log('Datos del equipo:', nombreEquipoInput.value, equipoActual.pokemon);
            if (response) {
                console.log('Respuesta HTTP:', response.status, response.statusText);
                console.log('Respuesta completa:', textData);
            }
            console.groupEnd();
            
        } finally {
            // Rehabilitar botón
            if (btnGuardar) btnGuardar.disabled = false;
        }
    }

    // Función para limpiar el equipo actual
    function limpiarEquipo() {
        if (confirm('¿Seguro que deseas limpiar todo el equipo?')) {
            nombreEquipoInput.value = '';
            equipoActual.nombre = '';
            equipoActual.pokemon = Array(6).fill(null);
            pokemonSeleccionado = null;
            pokemonSeleccionadoIndex = -1;
            
            actualizarVistaEquipo();
            
            pokemonSeleccionadoInfo.innerHTML = '<p>Selecciona un Pokémon de l\'equip per veure i configurar els seus moviments</p>';
            mostrarMovimientosAsignados({ movimientos: [] });
            
            mostrarNotificacion('Equipo limpiado correctamente.', 'info');
        }
    }

    // Función para cargar equipos existentes
    function cargarEquiposExistentes() {
        // Esta función se llamará desde el PHP con los datos ya cargados
    }

    // Función para mostrar notificaciones
    function mostrarNotificacion(mensaje, tipo) {
        // Crear elemento de notificación
        const notificacion = document.createElement('div');
        notificacion.className = `notification ${tipo}`;
        notificacion.textContent = mensaje;
        
        // Agregarlo al DOM
        document.body.appendChild(notificacion);
        
        // Mostrar con animación
        setTimeout(() => {
            notificacion.classList.add('show');
        }, 10);
        
        // Eliminar después de un tiempo
        setTimeout(() => {
            notificacion.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(notificacion);
            }, 300);
        }, 3000);
    }

    // Función para editar un equipo existente
    window.editarEquipo = function(equipoId, nombreEquipo) {
        // Pedir confirmación antes de cargar el equipo para editar
        if (!confirm(`¿Deseas cargar el equipo "${nombreEquipo}" para editar? Se sobreescribirán los datos que puedas tener en el formulario.`)) {
            return; // Si el usuario cancela, no hacemos nada
        }
        
        // Mostrar indicador de carga
        mostrarNotificacion('Cargando datos del equipo...', 'info');
        
        // Hacer solicitud para obtener los datos completos del equipo
        fetch('Controlador/gestio_equips.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'obtener_equipo',
                equipo_id: equipoId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                throw new Error(data.error || 'No se pudo cargar el equipo');
            }
            
            const equipo = data.equipo;
            
            // Limpiar el formulario actual
            limpiarEquipoSinConfirmar();
            
            // Cargar nombre del equipo
            nombreEquipoInput.value = equipo.nom_equip;
            equipoActual.nombre = equipo.nom_equip;
            
            // Guardar ID del equipo que estamos editando para saber si es una edición
            equipoActual.id_equipo = equipo.id_equip;
            
            // Cargar pokémon del equipo - Corregida la gestión de posiciones
            if (equipo.pokemons && equipo.pokemons.length > 0) {
                console.log('Cargando ' + equipo.pokemons.length + ' pokémon del equipo');
                
                equipo.pokemons.forEach(pokemon => {
                    // Crear objeto para el pokémon en el equipo
                    const pokemonEquipo = {
                        id: pokemon.pokeapi_id,
                        nombre: pokemon.nombre,
                        sprite: pokemon.sprite,
                        tipos: pokemon.types || [],
                        movimientos: []
                    };
                    
                    // Añadir movimientos si existen
                    if (pokemon.movimientos && pokemon.movimientos.length > 0) {
                        pokemon.movimientos.forEach(movimiento => {
                            pokemonEquipo.movimientos.push({
                                id: movimiento.pokeapi_move_id,
                                nombre: movimiento.nom_moviment,
                                tipo: movimiento.tipus_moviment,
                                categoria: movimiento.categoria,
                                poder: movimiento.poder,
                                precision: movimiento.precisio,
                                pp: movimiento.pp_maxims
                            });
                        });
                    }
                    
                    // Corregido: Usar directamente la posición del Pokémon como índice (0-5)
                    let posicion;
                    
                    // Comprobar si la posición es un índice válido en el array
                    if (pokemon.posicio !== undefined && pokemon.posicio !== null) {
                        // La posición viene desde 0 en la base de datos
                        posicion = parseInt(pokemon.posicio);
                        console.log(`Pokémon ${pokemon.nombre} en posición ${posicion}`);
                    } else {
                        // Si no hay posición definida, usar la primera ranura libre
                        posicion = equipoActual.pokemon.findIndex(p => p === null);
                        console.log(`Pokémon ${pokemon.nombre} sin posición, asignando a ${posicion}`);
                    }
                    
                    if (posicion >= 0 && posicion < equipoActual.pokemon.length) {
                        equipoActual.pokemon[posicion] = pokemonEquipo;
                    } else {
                        console.error(`Posición inválida ${posicion} para ${pokemon.nombre}`);
                    }
                });
            }
            
            // Actualizar la vista del equipo
            actualizarVistaEquipo();
            
            // Desplazamiento hacia el formulario de creación de equipo
            const creadorEquipo = document.querySelector('.creador-equipo');
            if (creadorEquipo) {
                creadorEquipo.scrollIntoView({ behavior: 'smooth' });
            }
            
            // Cambiar el texto del botón de guardar para indicar que estamos editando
            const btnGuardarEquipo = document.getElementById('btn-guardar-equipo');
            if (btnGuardarEquipo) {
                btnGuardarEquipo.textContent = 'Actualitzar Equip';
                btnGuardarEquipo.dataset.editando = 'true';
                btnGuardarEquipo.dataset.equipoId = equipoId;
            }
            
            // Actualizar título de la sección
            const tituloCreador = document.querySelector('.creador-equipo h2');
            if (tituloCreador) {
                tituloCreador.textContent = 'Editar equip';
            }
            
            mostrarNotificacion(`Equip "${equipo.nom_equip}" carregat per a edició. Pots modificar-lo i guardar els canvis.`, 'success');
        })
        .catch(error => {
            console.error('Error al cargar el equipo:', error);
            mostrarNotificacion(`Error al cargar el equipo: ${error.message}`, 'error');
        });
    };

    // Función para eliminar un equipo existente
    window.eliminarEquipo = function(equipoId) {
        if (confirm('¿Seguro que deseas eliminar este equipo?')) {
            fetch('Controlador/gestio_equips.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'eliminar_equipo',
                    equipo_id: equipoId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarNotificacion('Equipo eliminado correctamente.', 'success');
                    // Eliminar del DOM
                    const equipoElement = document.querySelector(`.equip-card[data-equip-id="${equipoId}"]`);
                    if (equipoElement) {
                        equipoElement.remove();
                    }
                    
                    // Si no hay más equipos, mostrar mensaje de no equipos
                    const equiposContainer = document.getElementById('equipos-container');
                    if (equiposContainer.children.length === 0) {
                        equiposContainer.innerHTML = `
                            <div class="no-equips-message">
                                <p>Encara no tens cap equip Pokémon!</p>
                                <p>Utilitza el creador d'equips a continuació per crear el teu primer equip.</p>
                                <img src="Vista/assets/img/Poké_Ball_icon.png" alt="Pokeball" style="width: 50px; margin-top: 10px;">
                            </div>
                        `;
                    }
                } else {
                    mostrarNotificacion(`Error al eliminar equipo: ${data.message || data.error || 'Error desconocido'}`, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarNotificacion('Error al eliminar equipo. Comprueba la consola para más detalles.', 'error');
            });
        }
    };

    // Función para buscar movimientos
    function buscarMovimientos() {
        // Verificar que hay un Pokémon seleccionado
        if (!pokemonSeleccionado) {
            mostrarNotificacion('Selecciona un Pokémon primero para buscar movimientos.', 'error');
            return;
        }
        
        const termino = movimientoSearch.value.trim().toLowerCase();
        
        // Si el término es igual al último buscado, no hacer nada
        if (termino === ultimaBusquedaMovimiento && termino !== '') return;
        
        ultimaBusquedaMovimiento = termino;
        
        // Mostrar indicador de carga
        movimientosSearchResults.style.display = 'block';
        movimientosSearchResults.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Cercant moviments...</div>';
        
        // Hacer llamada a la API para obtener los movimientos del Pokémon
        fetch(`https://pokeapi.co/api/v2/pokemon/${pokemonSeleccionado.id}`)
            .then(response => response.json())
            .then(data => {
                // Extraer los movimientos disponibles
                const movimientosDisponibles = data.moves.map(m => ({
                    nombre: m.move.name,
                    url: m.move.url
                }));
                
                // Si no hay término, mostrar todos los movimientos disponibles
                // Si hay término, filtrar movimientos disponibles según el término de búsqueda
                const movimientosFiltrados = termino === '' 
                    ? movimientosDisponibles.slice(0, 100) // Mostrar hasta 100 movimientos si no hay búsqueda
                    : movimientosDisponibles.filter(m => m.nombre.toLowerCase().includes(termino)).slice(0, 50);
                
                if (movimientosFiltrados.length === 0) {
                    movimientosSearchResults.innerHTML = '<div class="no-results">No s\'ha trobat cap moviment amb aquest nom.</div>';
                    return;
                }
                
                // Obtener detalles de cada movimiento
                Promise.all(
                    movimientosFiltrados.map(movimiento => 
                        fetch(movimiento.url)
                            .then(response => response.json())
                            .catch(() => null) // Si hay un error, devolver null
                    )
                )
                .then(movimientosData => {
                    // Filtrar los resultados nulos (errores)
                    const movimientosValidos = movimientosData.filter(m => m !== null);
                    mostrarResultadosMovimientos(movimientosValidos);
                })
                .catch(error => {
                    console.error('Error al buscar movimientos:', error);
                    movimientosSearchResults.innerHTML = '<div class="error">Error al buscar movimientos.</div>';
                });
            })
            .catch(error => {
                console.error('Error al cargar movimientos:', error);
                movimientosSearchResults.innerHTML = '<div class="error">Error al cargar movimientos del Pokémon.</div>';
            });
    }

    // Función para mostrar resultados de la búsqueda de movimientos
    function mostrarResultadosMovimientos(movimientos) {
        if (!movimientos || movimientos.length === 0) {
            movimientosSearchResults.innerHTML = '<div class="no-results">No s\'ha trobat cap moviment.</div>';
            return;
        }
        
        // Organizar movimientos en grupos de 5
        const grupos = [];
        for (let i = 0; i < movimientos.length; i += 5) {
            grupos.push(movimientos.slice(i, i + 5));
        }
        
        // Crear HTML para los resultados en filas horizontales
        let html = '<div class="movimientos-grupos-container">';
        
        grupos.forEach(grupo => {
            html += '<div class="movimientos-grupo">';
            
            grupo.forEach(movimiento => {
                const nombreMovimiento = movimiento.name.replace('-', ' ');
                const tipoMovimiento = movimiento.type.name;
                
                html += `
                    <div class="movimiento-item tipo-${tipoMovimiento}" data-move-id="${movimiento.id}">
                        <div class="movimiento-info">
                            <div class="movimiento-nombre">${nombreMovimiento}</div>
                            <div class="movimiento-tipo tipo-${tipoMovimiento}">${tipoMovimiento}</div>
                        </div>
                        <button class="add-movimiento-btn" title="Añadir movimiento" data-move-name="${movimiento.name}">+</button>
                    </div>
                `;
            });
            
            html += '</div>';
        });
        
        html += '</div>';
        
        // Mostrar resultados
        movimientosSearchResults.innerHTML = html;
        movimientosSearchResults.style.display = 'block';
        
        // Agregar eventos a los botones de añadir movimiento
        const addButtons = movimientosSearchResults.querySelectorAll('.add-movimiento-btn');
        addButtons.forEach(button => {
            button.addEventListener('click', () => {
                const moveName = button.getAttribute('data-move-name');
                agregarMovimientoAPokemon(pokemonSeleccionado, moveName);
            });
        });
    }

    // Función para eliminar un Pokémon del equipo
    function eliminarPokemonDelEquipo(index) {
        const pokemon = equipoActual.pokemon[index];
        
        if (pokemon) {
            if (confirm(`¿Seguro que deseas eliminar a ${pokemon.nombre} del equipo?`)) {
                // Eliminar el Pokémon del equipo
                equipoActual.pokemon[index] = null;
                
                // Actualizar la vista
                actualizarVistaEquipo();
                
                // Si era el Pokémon seleccionado actualmente, limpiar la selección
                if (pokemonSeleccionadoIndex === index) {
                    pokemonSeleccionado = null;
                    pokemonSeleccionadoIndex = -1;
                    pokemonSeleccionadoInfo.innerHTML = '<p>Selecciona un Pokémon de l\'equip per veure i configurar els seus moviments</p>';
                    mostrarMovimientosAsignados({ movimientos: [] });
                }
                
                // Guardar en localStorage después de eliminar el Pokémon
                guardarEquipoLocal();
                
                // Mostrar notificación
                mostrarNotificacion(`${pokemon.nombre.charAt(0).toUpperCase() + pokemon.nombre.slice(1)} eliminado del equipo.`, 'info');
            }
        }
    }

    // Función para depurar el estado de los movimientos
    function debugPokemonMovimientos() {
        console.log("==== DEBUG MOVIMIENTOS ====");
        equipoActual.pokemon.forEach((pokemon, index) => {
            if (pokemon) {
                console.log(`Pokémon ${index + 1}: ${pokemon.nombre} - Movimientos: ${pokemon.movimientos ? pokemon.movimientos.length : 0}`);
                if (pokemon.movimientos && pokemon.movimientos.length > 0) {
                    pokemon.movimientos.forEach((mov, i) => {
                        console.log(`- Mov ${i+1}: ${mov.nombre} (${mov.tipo})`);
                    });
                }
            }
        });
    }

    // Función para limpiar los movimientos del Pokémon seleccionado actualmente
    function limpiarMovimientosPokemon() {
        if (!pokemonSeleccionado) {
            mostrarNotificacion('Debes seleccionar un Pokémon primero.', 'error');
            return;
        }
        
        if (!pokemonSeleccionado.movimientos || pokemonSeleccionado.movimientos.length === 0) {
            mostrarNotificacion('Este Pokémon no tiene movimientos que limpiar.', 'info');
            return;
        }
        
        if (confirm(`¿Seguro que deseas eliminar todos los movimientos de ${pokemonSeleccionado.nombre}?`)) {
            // Limpiar los movimientos
            pokemonSeleccionado.movimientos = [];
            
            // Actualizar la UI
            mostrarMovimientosAsignados(pokemonSeleccionado);
            
            // Guardar cambios en localStorage
            guardarEquipoLocal();
            
            // Mostrar notificación
            mostrarNotificacion(`Se han eliminado todos los movimientos de ${pokemonSeleccionado.nombre}.`, 'success');
        }
    }

    // Función para limpiar todos los Pokémon del equipo
    function limpiarPokemonEquipo() {
        if (equipoActual.pokemon.every(p => p === null)) {
            mostrarNotificacion('El equipo ya está vacío.', 'info');
            return;
        }
        
        if (confirm('¿Seguro que deseas eliminar todos los Pokémon del equipo?')) {
            // Limpiar el equipo
            equipoActual.pokemon = Array(6).fill(null);
            
            // Resetear selección actual
            pokemonSeleccionado = null;
            pokemonSeleccionadoIndex = -1;
            
            // Actualizar la UI
            actualizarVistaEquipo();
            pokemonSeleccionadoInfo.innerHTML = '<p>Selecciona un Pokémon de l\'equip per veure i configurar els seus moviments</p>';
            mostrarMovimientosAsignados({ movimientos: [] });
            
            // Guardar cambios en localStorage
            guardarEquipoLocal();
            
            // Ocultar resultados de búsqueda de movimientos
            movimientosSearchResults.style.display = 'none';
            
            // Mostrar notificación
            mostrarNotificacion('Se han eliminado todos los Pokémon del equipo.', 'success');
        }
    }
    
    // Función para guardar el equipo en el localStorage
    function guardarEquipoLocal() {
        if (!equipoActual) return;
        
        try {
            const equipoLocalData = {
                nombre: nombreEquipoInput.value.trim(),
                pokemon: equipoActual.pokemon,
                ultimaModificacion: new Date().toISOString()
            };
            
            localStorage.setItem('equipoEnCreacion', JSON.stringify(equipoLocalData));
            console.log('Equipo guardado en localStorage:', equipoLocalData);
        } catch (error) {
            console.error('Error al guardar equipo en localStorage:', error);
        }
    }
    
    // Función para cargar el equipo desde localStorage
    function cargarEquipoLocal() {
        try {
            const equipoGuardado = localStorage.getItem('equipoEnCreacion');
            
            if (!equipoGuardado) {
                console.log('No hay equipo guardado en localStorage');
                return false;
            }
            
            const equipoLocalData = JSON.parse(equipoGuardado);
            console.log('Equipo cargado desde localStorage:', equipoLocalData);
            
            // Si el equipo guardado tiene más de 7 días, lo eliminamos
            const fechaGuardado = new Date(equipoLocalData.ultimaModificacion);
            const ahora = new Date();
            const diasDiferencia = (ahora - fechaGuardado) / (1000 * 60 * 60 * 24);
            
            if (diasDiferencia > 7) {
                console.log('El equipo guardado es muy antiguo, eliminando...');
                localStorage.removeItem('equipoEnCreacion');
                return false;
            }
            
            // Cargar datos del equipo
            nombreEquipoInput.value = equipoLocalData.nombre || '';
            equipoActual.nombre = equipoLocalData.nombre || '';
            
            // Si hay pokemon en el equipo guardado, los cargamos
            if (equipoLocalData.pokemon && Array.isArray(equipoLocalData.pokemon)) {
                equipoActual.pokemon = equipoLocalData.pokemon;
                actualizarVistaEquipo();
                
                // Mostrar notificación
                mostrarNotificacion('Se ha recuperado tu equipo en creación', 'info');
                return true;
            }
            
            return false;
        } catch (error) {
            console.error('Error al cargar equipo desde localStorage:', error);
            return false;
        }
    }
    
    // Función para limpiar el equipo guardado en localStorage después de guardar en la BD
    function limpiarEquipoLocal() {
        localStorage.removeItem('equipoEnCreacion');
        console.log('Equipo eliminado del localStorage');
    }

    // Función para deseleccionar el Pokémon actual
    function deseleccionarPokemon() {
        // Comprobar si hay un Pokémon seleccionado
        if (pokemonSeleccionado === null) {
            mostrarNotificacion('No hay ningún Pokémon seleccionado.', 'info');
            return;
        }
        
        // Eliminar la selección visual
        const slots = equipoPokemonGrid.querySelectorAll('.equipo-slot');
        slots.forEach(slot => {
            slot.classList.remove('selected');
        });
        
        // Limpiar variables globales
        pokemonSeleccionado = null;
        pokemonSeleccionadoIndex = -1;
        
        // Actualizar la UI
        pokemonSeleccionadoInfo.innerHTML = '<p>Selecciona un Pokémon de l\'equip per veure i configurar els seus moviments</p>';
        
        // Limpiar grid de movimientos
        mostrarMovimientosAsignados({ movimientos: [] });
        
        // Ocultar resultados de búsqueda de movimientos
        movimientosSearchResults.style.display = 'none';
        
        mostrarNotificacion('Pokémon deseleccionado.', 'info');
    }

    // Función para configurar eventos para los botones de eliminar equipo
    function configurarBotonesEliminarEquipo() {
        const botonesEliminarEquipo = document.querySelectorAll('.delete-equip-btn');
        
        botonesEliminarEquipo.forEach(boton => {
            boton.addEventListener('click', function(event) {
                // Evitar propagación del evento para que no se activen otros eventos
                event.stopPropagation();
                
                // Encontrar el ID del equipo desde el contenedor padre
                const equipoCard = this.closest('.equip-card');
                if (!equipoCard) {
                    console.error('No se pudo encontrar el contenedor del equipo');
                    return;
                }
                
                const equipoId = equipoCard.dataset.equipId;
                if (!equipoId) {
                    console.error('No se pudo encontrar el ID del equipo');
                    return;
                }
                
                // Llamar a la función de eliminarEquipo
                eliminarEquipo(equipoId);
            });
        });
        
        console.log(`Se han configurado ${botonesEliminarEquipo.length} botones de eliminar equipo`);
    }

    // Función para configurar eventos para los botones de editar equipo
    function configurarBotonesEditarEquipo() {
        const botonesEditarEquipo = document.querySelectorAll('.edit-equip-btn');
        
        botonesEditarEquipo.forEach(boton => {
            boton.addEventListener('click', function(event) {
                // Evitar propagación del evento para que no se activen otros eventos
                event.stopPropagation();
                
                // Encontrar el ID del equipo desde el contenedor padre
                const equipoCard = this.closest('.equip-card');
                if (!equipoCard) {
                    console.error('No se pudo encontrar el contenedor del equipo');
                    return;
                }
                
                const equipoId = equipoCard.dataset.equipId;
                if (!equipoId) {
                    console.error('No se pudo encontrar el ID del equipo');
                    return;
                }
                
                const nombreEquipo = equipoCard.querySelector('.equip-title').textContent;
                
                // Llamar a la función de editar equipo
                editarEquipo(equipoId, nombreEquipo);
            });
        });
        
        console.log(`Se han configurado ${botonesEditarEquipo.length} botones de editar equipo`);
    }

    // Configurar eventos para los botones de exportar equipo
    function configurarBotonesExportarEquipo() {
        const botonesExportarEquipo = document.querySelectorAll('.export-equip-btn');
        
        botonesExportarEquipo.forEach(boton => {
            boton.addEventListener('click', function(event) {
                // Evitar propagación del evento para que no se activen otros eventos
                event.stopPropagation();
                
                // Encontrar el ID del equipo desde el contenedor padre
                const equipoCard = this.closest('.equip-card');
                if (!equipoCard) {
                    console.error('No se pudo encontrar el contenedor del equipo');
                    return;
                }
                
                const equipoId = equipoCard.dataset.equipId;
                if (!equipoId) {
                    console.error('No se pudo encontrar el ID del equipo');
                    return;
                }
                
                // Mostrar el modal de exportación con los datos del equipo
                exportarEquipo(equipoId);
            });
        });
        
        console.log(`Se han configurado ${botonesExportarEquipo.length} botones de exportar equipo`);
    }
    
    // Función para exportar un equipo
    function exportarEquipo(equipoId) {
        // Mostrar indicador de carga
        mostrarNotificacion('Preparando datos para exportar...', 'info');
        
        // Hacer solicitud para obtener los datos completos del equipo
        fetch('Controlador/gestio_equips.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'obtener_equipo',
                equipo_id: equipoId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                throw new Error(data.error || 'No se pudo cargar el equipo');
            }
            
            const equipo = data.equipo;
            
            // Preparar datos para exportar
            const datosExportacion = {
                nombre: equipo.nom_equip,
                pokemons: []
            };
            
            // Añadir Pokémon al objeto de exportación
            if (equipo.pokemons && equipo.pokemons.length > 0) {
                equipo.pokemons.forEach(pokemon => {
                    const pokemonExport = {
                        id: pokemon.pokeapi_id,
                        nombre: pokemon.nombre,
                        posicion: pokemon.posicio,
                        sprite: pokemon.sprite,
                        types: pokemon.types || [],
                        movimientos: []
                    };
                    
                    // Añadir movimientos si existen
                    if (pokemon.movimientos && pokemon.movimientos.length > 0) {
                        pokemon.movimientos.forEach(movimiento => {
                            pokemonExport.movimientos.push({
                                id: movimiento.pokeapi_move_id,
                                nombre: movimiento.nom_moviment,
                                tipo: movimiento.tipus_moviment,
                                categoria: movimiento.categoria,
                                poder: movimiento.poder,
                                precision: movimiento.precisio,
                                pp: movimiento.pp_maxims
                            });
                        });
                    }
                    
                    datosExportacion.pokemons.push(pokemonExport);
                });
            }
            
            // Convertir a JSON para la exportación
            const datosJSON = JSON.stringify(datosExportacion, null, 2);
            
            // Mostrar el modal de exportación
            mostrarModalExportacion(datosExportacion, datosJSON);
        })
        .catch(error => {
            console.error('Error al exportar el equipo:', error);
            mostrarNotificacion(`Error al exportar el equipo: ${error.message}`, 'error');
        });
    }
    
    // Función para mostrar el modal de exportación
    function mostrarModalExportacion(datosEquipo, datosJSON) {
        const exportModal = document.getElementById('export-modal');
        const exportData = document.getElementById('export-data');
        const pokemonExportList = document.getElementById('pokemon-export-list');
        const copyFullTeamBtn = document.getElementById('copy-full-team');
        const closeExportModalBtn = document.getElementById('close-export-modal');
        
        if (!exportModal || !exportData || !pokemonExportList || !copyFullTeamBtn || !closeExportModalBtn) {
            mostrarNotificacion('Error al mostrar el modal de exportación', 'error');
            return;
        }
        
        // Limpiar datos anteriores
        exportData.value = datosJSON;
        pokemonExportList.innerHTML = '';
        
        // Añadir cada Pokémon a la lista para exportación individual
        datosEquipo.pokemons.forEach((pokemon, index) => {
            const pokemonItem = document.createElement('div');
            pokemonItem.className = 'pokemon-export-item';
            
            const pokemonData = JSON.stringify(pokemon, null, 2);
            
            pokemonItem.innerHTML = `
                <img src="${pokemon.sprite || 'Vista/assets/img/Poké_Ball_icon.png'}" 
                     alt="${pokemon.nombre}" 
                     onerror="this.src='Vista/assets/img/Poké_Ball_icon.png'">
                <span class="pokemon-export-name">${pokemon.nombre}</span>
                <button class="pokemon-copy-btn" title="Copiar datos de este Pokémon" data-pokemon-index="${index}">
                    <i class="fas fa-copy"></i>
                </button>
            `;
            
            pokemonExportList.appendChild(pokemonItem);
            
            // Añadir evento para copiar solo este Pokémon
            const copyBtn = pokemonItem.querySelector('.pokemon-copy-btn');
            copyBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                const pokemonIndex = parseInt(this.dataset.pokemonIndex);
                copiarDatosPokemon(datosEquipo.pokemons[pokemonIndex]);
            });
        });
        
        // Configurar eventos del modal
        copyFullTeamBtn.addEventListener('click', function() {
            copiarAlPortapapeles(datosJSON);
        });
        
        closeExportModalBtn.addEventListener('click', function() {
            exportModal.classList.remove('active');
        });
        
        // Mostrar modal
        exportModal.classList.add('active');
    }
    
    // Función para copiar los datos al portapapeles
    function copiarAlPortapapeles(texto) {
        // Mostrar feedback visual inmediato
        mostrarNotificacionCopia('Copiando...');
        
        // Usamos la API moderna de Clipboard
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(texto)
                .then(() => {
                    console.log('Texto copiado correctamente usando Clipboard API');
                    mostrarNotificacionCopia('¡Copiado!');
                    mostrarNotificacion('Equipo copiado al portapapeles', 'success');
                })
                .catch(err => {
                    console.error('Error al copiar con Clipboard API:', err);
                    usarMetodoAlternativo(texto);
                });
        } else {
            // Fallback para contextos no seguros o navegadores sin soporte
            usarMetodoAlternativo(texto);
        }
    }
    
    // Método alternativo para copiar al portapapeles
    function usarMetodoAlternativo(texto) {
        try {
            const textarea = document.createElement('textarea');
            textarea.value = texto;
            
            // Hacer visible pero no intrusivo
            textarea.style.position = 'fixed';
            textarea.style.left = '0';
            textarea.style.top = '0';
            textarea.style.width = '2em';
            textarea.style.height = '2em';
            textarea.style.padding = '0';
            textarea.style.border = 'none';
            textarea.style.outline = 'none';
            textarea.style.boxShadow = 'none';
            textarea.style.background = 'transparent';
            
            document.body.appendChild(textarea);
            textarea.focus();
            textarea.select();
            
            // Técnica específica para iOS
            if (navigator.userAgent.match(/ipad|ipod|iphone/i)) {
                textarea.contentEditable = true;
                textarea.readOnly = false;
                
                const range = document.createRange();
                range.selectNodeContents(textarea);
                
                const selection = window.getSelection();
                selection.removeAllRanges();
                selection.addRange(range);
                textarea.setSelectionRange(0, 999999);
            }
            
            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    console.log('Texto copiado correctamente usando execCommand');
                    mostrarNotificacionCopia('¡Copiado!');
                    mostrarNotificacion('Equipo copiado al portapapeles', 'success');
                } else {
                    console.error('Falló execCommand');
                    mostrarNotificacion('No se pudo copiar. Intenta seleccionar y copiar el texto manualmente.', 'warning');
                }
            } catch (err) {
                console.error('Error en execCommand:', err);
                mostrarNotificacion('Error al intentar copiar. Por favor, selecciona y copia el texto manualmente.', 'error');
            }
            
            document.body.removeChild(textarea);
        } catch (err) {
            console.error('Error en método alternativo de copia:', err);
            mostrarNotificacion('Error al copiar. Selecciona y copia el texto manualmente.', 'error');
        }
    }
    
    // Función para mostrar una notificación de copia
    function mostrarNotificacionCopia(mensaje) {
        // Ver si ya existe una notificación de copia
        let notificacion = document.querySelector('.copy-notification');
        
        if (!notificacion) {
            notificacion = document.createElement('div');
            notificacion.className = 'copy-notification';
            notificacion.style.position = 'fixed';
            notificacion.style.top = '50%';
            notificacion.style.left = '50%';
            notificacion.style.transform = 'translate(-50%, -50%)';
            notificacion.style.background = 'rgba(0, 0, 0, 0.8)';
            notificacion.style.color = 'white';
            notificacion.style.padding = '10px 20px';
            notificacion.style.borderRadius = '4px';
            notificacion.style.fontSize = '16px';
            notificacion.style.zIndex = '9999';
            document.body.appendChild(notificacion);
        }
        
        // Actualizar mensaje
        notificacion.textContent = mensaje;
        
        // Animación de fade in
        notificacion.style.opacity = '1';
        
        // Ocultar después de 1.5 segundos
        setTimeout(() => {
            notificacion.style.opacity = '0';
            setTimeout(() => {
                if (notificacion.parentNode) {
                    notificacion.parentNode.removeChild(notificacion);
                }
            }, 300);
        }, 1500);
    }
    
    // Función para copiar datos de un Pokémon específico
    function copiarDatosPokemon(pokemon) {
        const datosPokemonJSON = JSON.stringify(pokemon, null, 2);
        copiarAlPortapapeles(datosPokemonJSON);
    }
    
    // Función para configurar el botón de importar
    function configurarBotonImportar() {
        const btnImportarEquipo = document.getElementById('btn-importar-equipo');
        const importModal = document.getElementById('import-modal');
        const closeImportModalBtn = document.getElementById('close-import-modal');
        const btnImportData = document.getElementById('btn-import-data');
        const importData = document.getElementById('import-data');
        
        if (!btnImportarEquipo || !importModal || !closeImportModalBtn || !btnImportData || !importData) {
            console.error('No se encontraron elementos necesarios para la importación');
            return;
        }
        
        btnImportarEquipo.addEventListener('click', function() {
            // Limpiar textarea
            importData.value = '';
            // Mostrar modal
            importModal.classList.add('active');
        });
        
        closeImportModalBtn.addEventListener('click', function() {
            importModal.classList.remove('active');
        });
        
        btnImportData.addEventListener('click', function() {
            importarDatos(importData.value);
        });
    }
    
    // Función para importar datos
    function importarDatos(datosJSON) {
        try {
            // Limpiar el texto de posibles caracteres invisibles o espacios extra
            datosJSON = datosJSON.trim();
            
            // Detectar si hay errores comunes en el JSON
            if (!datosJSON.startsWith('{') && !datosJSON.startsWith('[')) {
                throw new Error('El texto no parece ser un formato JSON válido. Debe comenzar con { o [');
            }
            
            // Intentar parsear el JSON con manejo especial de errores
            let datos;
            try {
                datos = JSON.parse(datosJSON);
                console.log("Datos JSON parseados correctamente:", datos);
            } catch (error) {
                console.error("Error al parsear JSON:", error);
                console.error("Texto recibido:", datosJSON.substring(0, 100) + "...");
                
                // Intentar limpiar más el texto y volver a intentar
                try {
                    // Eliminar posibles BOM y otros caracteres invisibles
                    let cleanJSON = datosJSON.replace(/^\ufeff/g, '');
                    datos = JSON.parse(cleanJSON);
                    console.log("Éxito al parsear JSON después de limpieza adicional");
                } catch (secondError) {
                    throw new Error('Los datos no son un JSON válido. Verifica que no haya caracteres especiales o saltos de línea adicionales.');
                }
            }
            
            // Verificar si los datos son un equipo completo o un Pokémon individual
            if (datos.nombre && Array.isArray(datos.pokemons)) {
                // Es un equipo completo
                importarEquipoCompleto(datos);
            } else if (datos.id && datos.nombre) {
                // Es un Pokémon individual
                importarPokemonIndividual(datos);
            } else {
                throw new Error('Formato de datos no reconocido. Asegúrate de que el JSON contiene la estructura correcta.');
            }
            
            // Cerrar modal después de importar
            document.getElementById('import-modal').classList.remove('active');
            
        } catch (error) {
            mostrarNotificacion(`Error al importar: ${error.message}`, 'error');
        }
    }
    
    // Función para importar un equipo completo
    function importarEquipoCompleto(equipo) {
        // Confirmar antes de sobrescribir datos existentes
        if (equipoActual.pokemon.some(p => p !== null)) {
            if (!confirm('Hay datos en el equipo actual. ¿Deseas sobrescribirlos?')) {
                return;
            }
        }
        
        // Limpiar equipo actual
        limpiarEquipoSinConfirmar();
        
        // Establecer nombre del equipo
        nombreEquipoInput.value = equipo.nombre;
        equipoActual.nombre = equipo.nombre;
        
        // Añadir cada Pokémon al equipo
        equipo.pokemons.forEach(pokemon => {
            // Crear objeto Pokémon para el equipo
            const pokemonEquipo = {
                id: pokemon.id,
                nombre: pokemon.nombre,
                sprite: pokemon.sprite,
                tipos: pokemon.types || [],
                movimientos: []
            };
            
            // Añadir movimientos si existen
            if (pokemon.movimientos && pokemon.movimientos.length > 0) {
                pokemon.movimientos.forEach(movimiento => {
                    pokemonEquipo.movimientos.push({
                        id: movimiento.id,
                        nombre: movimiento.nombre,
                        tipo: movimiento.tipo,
                        categoria: movimiento.categoria,
                        poder: movimiento.poder,
                        precision: movimiento.precision,
                        pp: movimiento.pp
                    });
                });
            }
            
            // Ubicar el Pokémon en la posición correcta
            const posicion = pokemon.posicion !== undefined ? parseInt(pokemon.posicion) : equipoActual.pokemon.findIndex(p => p === null);
            
            if (posicion >= 0 && posicion < equipoActual.pokemon.length) {
                equipoActual.pokemon[posicion] = pokemonEquipo;
            }
        });
        
        // Actualizar la vista del equipo
        actualizarVistaEquipo();
        
        mostrarNotificacion('Equipo importado correctamente', 'success');
    }
    
    // Función para importar un Pokémon individual
    function importarPokemonIndividual(pokemon) {
        // Buscar primera ranura vacía
        let ranuraVacia = -1;
        for (let i = 0; i < equipoActual.pokemon.length; i++) {
            if (equipoActual.pokemon[i] === null) {
                ranuraVacia = i;
                break;
            }
        }
        
        if (ranuraVacia === -1) {
            mostrarNotificacion('El equipo ya está completo. Elimina un Pokémon antes de agregar otro.', 'error');
            return;
        }
        
        // Crear objeto de Pokémon para el equipo
        const pokemonEquipo = {
            id: pokemon.id,
            nombre: pokemon.nombre,
            sprite: pokemon.sprite,
            tipos: pokemon.types || [],
            movimientos: []
        };
        
        // Añadir movimientos si existen
        if (pokemon.movimientos && pokemon.movimientos.length > 0) {
            pokemon.movimientos.forEach(movimiento => {
                pokemonEquipo.movimientos.push({
                    id: movimiento.id,
                    nombre: movimiento.nombre,
                    tipo: movimiento.tipo,
                    categoria: movimiento.categoria,
                    poder: movimiento.poder,
                    precision: movimiento.precision,
                    pp: movimiento.pp
                });
            });
        }
        
        // Agregar a la ranura vacía
        equipoActual.pokemon[ranuraVacia] = pokemonEquipo;
        actualizarVistaEquipo();
        
        // Guardar en localStorage
        guardarEquipoLocal();
        
        // Mostrar notificación
        mostrarNotificacion(`${pokemonEquipo.nombre.charAt(0).toUpperCase() + pokemonEquipo.nombre.slice(1)} añadido al equipo.`, 'success');
    }
    
    // Función para limpiar el equipo sin solicitar confirmación (para uso interno)
    function limpiarEquipoSinConfirmar() {
        nombreEquipoInput.value = '';
        equipoActual.nombre = '';
        equipoActual.pokemon = Array(6).fill(null);
        pokemonSeleccionado = null;
        pokemonSeleccionadoIndex = -1;
        
        actualizarVistaEquipo();
        
        pokemonSeleccionadoInfo.innerHTML = '<p>Selecciona un Pokémon de l\'equip per veure i configurar els seus moviments</p>';
        mostrarMovimientosAsignados({ movimientos: [] });
    }
});