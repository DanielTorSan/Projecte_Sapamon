/**
 * JavaScript per gestionar les batalles Pokémon entre entrenadors
 */
document.addEventListener('DOMContentLoaded', function() {
    // Elements DOM
    const btnDesafiar = document.getElementById('btn-desafiar');
    const btnDesafios = document.getElementById('btn-desafios');
    const desafiarModal = document.getElementById('desafiar-modal');
    const closeDesafiarModal = document.getElementById('close-desafiar-modal');
    const desafiosModal = document.getElementById('desafios-modal');
    const closeDesafiosModal = document.getElementById('close-desafios-modal');
    const acceptarDesafiamentModal = document.getElementById('aceptar-desafio-modal');
    const closeAcceptarDesafiamentModal = document.getElementById('close-aceptar-desafio-modal');
    const entrenadorSearch = document.getElementById('entrenador-search');
    const entrenadorsList = document.getElementById('entrenadors-list');
    const btnEnviarDesafio = document.getElementById('btn-enviar-desafio');
    const btnAcceptarDesafiament = document.getElementById('btn-aceptar-desafio');
    const btnRebutjarDesafiament = document.getElementById('btn-rechazar-desafio');
    const comptadorDesafiaments = document.getElementById('contador-desafios');

    // Variables per emmagatzemar les seleccions
    let selectedEntrenadorId = null;
    let selectedEquipoId = null;
    let selectedDesafioId = null;
    let selectedDesafioEquipoId = null;

    // Estat per als desafiaments pendents
    let desafiamentsPendents = {
        rebuts: [],
        enviats: []
    };

    // ------------------- INICIALITZACIÓ I ESDEVENIMENTS -------------------

    // Inicialització
    if (document.body.dataset.usuariId) {
        verificarDesafiamentsPendents();
        // Comprovar desafiaments pendents més freqüentment (cada 15 segons)
        setInterval(verificarDesafiamentsPendents, 15000);
    }

    // Event Listeners
    if (btnDesafiar) {
        btnDesafiar.addEventListener('click', function() {
            obrirModalDesafiar();
        });
    }

    if (btnDesafios) {
        btnDesafios.addEventListener('click', function() {
            obrirModalDesafiaments();
        });
    }

    if (closeDesafiarModal) {
        closeDesafiarModal.addEventListener('click', function() {
            tancarModalDesafiar();
        });
    }

    if (closeDesafiosModal) {
        closeDesafiosModal.addEventListener('click', function() {
            tancarModalDesafiaments();
        });
    }

    if (closeAcceptarDesafiamentModal) {
        closeAcceptarDesafiamentModal.addEventListener('click', function() {
            tancarModalAcceptarDesafiament();
        });
    }

    // Botó per enviar desafiament
    if (btnEnviarDesafio) {
        btnEnviarDesafio.addEventListener('click', function() {
            enviarDesafiament();
        });
    }

    // Botó per acceptar desafiament
    if (btnAcceptarDesafiament) {
        btnAcceptarDesafiament.addEventListener('click', function() {
            acceptarDesafiament();
        });
    }

    // Botó per rebutjar desafiament
    if (btnRebutjarDesafiament) {
        btnRebutjarDesafiament.addEventListener('click', function() {
            rebutjarDesafiament();
        });
    }

    // Cerca d'entrenadors
    if (entrenadorSearch) {
        entrenadorSearch.addEventListener('input', function() {
            filtrarEntrenadors(this.value);
        });
    }

    // Esdeveniments per pestanyes de desafiaments
    document.querySelectorAll('.tab-btn').forEach(button => {
        button.addEventListener('click', function() {
            const tabType = this.dataset.tab;
            canviarTabDesafiaments(tabType);
        });
    });

    // Configuració de selecció d'equips per desafiament
    document.querySelectorAll('#desafio-equips-list .equip-item').forEach(item => {
        item.querySelector('.select-equip-btn').addEventListener('click', function() {
            seleccionarEquipDesafiament(this.dataset.equipId);
        });
    });

    // Configuració de selecció d'equips per acceptar desafiament
    document.querySelectorAll('#aceptar-equips-list .equip-item').forEach(item => {
        item.querySelector('.select-equip-btn').addEventListener('click', function() {
            seleccionarEquipAcceptar(this.dataset.equipId);
        });
    });

    // Verificar selecció inicial d'equip
    const equipsDesafiament = document.querySelectorAll('#desafio-equips-list .equip-item');
    if (equipsDesafiament.length > 0) {
        const primerEquip = equipsDesafiament[0];
        if (primerEquip && primerEquip.dataset.equipId) {
            selectedEquipoId = primerEquip.dataset.equipId;
            actualitzarBtnEnviarDesafiament();
        }
    }

    const equipsAcceptar = document.querySelectorAll('#aceptar-equips-list .equip-item');
    if (equipsAcceptar.length > 0) {
        const primerEquip = equipsAcceptar[0];
        if (primerEquip && primerEquip.dataset.equipId) {
            selectedDesafioEquipoId = primerEquip.dataset.equipId;
        }
    }

    // ------------------- FUNCIONS -------------------

    /**
     * Obrir modal per desafiar a un entrenador
     */
    function obrirModalDesafiar() {
        desafiarModal.classList.add('active');
        // Reset seleccions
        resetSeleccionEntrenador();
        resetSeleccionEquip();
        actualitzarBtnEnviarDesafiament();
    }

    /**
     * Tancar modal de desafiament
     */
    function tancarModalDesafiar() {
        desafiarModal.classList.remove('active');
    }

    /**
     * Obrir modal de desafiaments pendents
     */
    function obrirModalDesafiaments() {
        desafiosModal.classList.add('active');
        carregarDesafiamentsPendents();
    }

    /**
     * Tancar modal de desafiaments pendents
     */
    function tancarModalDesafiaments() {
        desafiosModal.classList.remove('active');
    }

    /**
     * Obrir modal per acceptar un desafiament
     */
    function obrirModalAcceptarDesafiament(desafioId) {
        selectedDesafioId = desafioId;
        
        // Buscar el desafiament a la llista de rebuts
        const desafiament = desafiamentsPendents.rebuts.find(d => d.id_batalla == desafioId);
        
        if (!desafiament) {
            console.error('No s\'ha trobat el desafiament amb ID: ' + desafioId);
            return;
        }
        
        // Actualitzar el modal amb la informació del desafiament
        const desafiamentInfo = document.getElementById('desafio-info');
        desafiamentInfo.innerHTML = `
            <div class="desafio-info-header">
                <h3>Desafiament de ${desafiament.retador_nombre}</h3>
            </div>
            <div class="desafio-vs">
                <div class="desafio-vs-avatar">
                    <img src="Vista/assets/img/avatars/${desafiament.retador_avatar}" 
                         alt="Avatar" 
                         onerror="this.src='Vista/assets/img/avatars/Youngster.png'">
                </div>
                <div class="desafio-vs-vs">VS</div>
                <div class="desafio-vs-avatar">
                    <img src="Vista/assets/img/avatars/${desafiament.retado_avatar}" 
                         alt="Avatar" 
                         onerror="this.src='Vista/assets/img/avatars/Youngster.png'">
                </div>
            </div>
            <div class="desafio-equipo">
                <div class="desafio-equipo-nombre">Equip retador: ${desafiament.equipo_retador_nombre}</div>
            </div>
        `;
        
        document.getElementById('desafio-id').value = desafioId;
        acceptarDesafiamentModal.classList.add('active');
    }

    /**
     * Tancar modal per acceptar desafiament
     */
    function tancarModalAcceptarDesafiament() {
        acceptarDesafiamentModal.classList.remove('active');
        selectedDesafioId = null;
    }

    /**
     * Filtrar entrenadors a la llista
     */
    function filtrarEntrenadors(searchTerm) {
        searchTerm = searchTerm.toLowerCase();
        const entrenadors = entrenadorsList.querySelectorAll('.entrenador-item');
        
        entrenadors.forEach(item => {
            const nombre = item.querySelector('.entrenador-info h4').textContent.toLowerCase();
            if (nombre.includes(searchTerm)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    }

    /**
     * Seleccionar un entrenador per desafiar
     */
    function seleccionarEntrenador(entrenadorId) {
        resetSeleccionEntrenador();
        selectedEntrenadorId = entrenadorId;
        
        // Actualitzar UI
        const btnSeleccionado = document.querySelector(`.select-entrenador-btn[data-entrenador-id="${entrenadorId}"]`);
        if (btnSeleccionado) {
            btnSeleccionado.classList.add('active');
            btnSeleccionado.textContent = 'Seleccionat';
        }
        
        actualitzarBtnEnviarDesafiament();
    }

    /**
     * Resetear la selecció d'entrenador
     */
    function resetSeleccionEntrenador() {
        selectedEntrenadorId = null;
        document.querySelectorAll('.select-entrenador-btn').forEach(btn => {
            btn.classList.remove('active');
            btn.textContent = 'Seleccionar';
        });
    }

    /**
     * Seleccionar un equip per al desafiament
     */
    function seleccionarEquipDesafiament(equipoId) {
        resetSeleccionEquip();
        selectedEquipoId = equipoId;
        
        // Actualitzar UI
        const btnSeleccionado = document.querySelector(`#desafio-equips-list .select-equip-btn[data-equip-id="${equipoId}"]`);
        if (btnSeleccionado) {
            btnSeleccionado.classList.add('active');
            btnSeleccionado.textContent = 'Seleccionat';
        }
        
        actualitzarBtnEnviarDesafiament();
    }

    /**
     * Seleccionar un equip per acceptar desafiament
     */
    function seleccionarEquipAcceptar(equipoId) {
        // Resetear selecció anterior
        document.querySelectorAll('#aceptar-equips-list .select-equip-btn').forEach(btn => {
            btn.classList.remove('active');
            btn.textContent = 'Seleccionar';
        });
        
        selectedDesafioEquipoId = equipoId;
        
        // Actualitzar UI
        const btnSeleccionado = document.querySelector(`#aceptar-equips-list .select-equip-btn[data-equip-id="${equipoId}"]`);
        if (btnSeleccionado) {
            btnSeleccionado.classList.add('active');
            btnSeleccionado.textContent = 'Seleccionat';
        }
    }

    /**
     * Resetear la selecció d'equip
     */
    function resetSeleccionEquip() {
        // No resetear el selectedEquipoId ja que pot haver-hi una selecció predeterminada
        document.querySelectorAll('#desafio-equips-list .select-equip-btn').forEach(btn => {
            btn.classList.remove('active');
            btn.textContent = 'Seleccionar';
        });
    }

    /**
     * Actualitzar l'estat del botó d'enviar desafiament
     */
    function actualitzarBtnEnviarDesafiament() {
        if (selectedEntrenadorId && selectedEquipoId) {
            btnEnviarDesafio.disabled = false;
        } else {
            btnEnviarDesafio.disabled = true;
        }
    }

    /**
     * Verificar desafiaments pendents
     */
    function verificarDesafiamentsPendents() {
        fetch('Controlador/batalla_api.php?action=desafiaments_pendents')
            .then(response => {
                // Primero verificar el tipo de contenido
                const contentType = response.headers.get('content-type');
                if (!response.ok) {
                    return response.text().then(text => {
                        // Mostrar el error completo en la consola
                        console.error('Error completo en la respuesta:', {
                            status: response.status,
                            statusText: response.statusText,
                            url: response.url,
                            text: text
                        });
                        throw new Error(`Error HTTP: ${response.status}. Resposta: ${text}`);
                    });
                }
                
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        console.error('El servidor no devolvió JSON:', text);
                        throw new Error(`Error de formato: La respuesta no es JSON válido`);
                    });
                }
                
                return response.json();
            })
            .then(data => {
                if (!data) {
                    throw new Error('La respuesta está vacía o no es JSON válido');
                }
                
                if (data.success) {
                    const rebuts = data.desafios.filter(d => d.tipo === 'recibido');
                    const enviats = data.desafios.filter(d => d.tipo === 'enviado');
                    
                    desafiamentsPendents.rebuts = rebuts;
                    desafiamentsPendents.enviats = enviats;
                    
                    actualitzarComptadorDesafiaments(rebuts.length);
                    
                    // Verificar si hay alguna batalla activa para este usuario
                    if (data.batalla_activa) {
                        // Redirigir automáticamente a la batalla si hay una activa
                        window.location.href = `Vista/Combat_Vista.php?id_batalla=${data.batalla_activa}`;
                    }
                } else {
                    console.warn('La API respondió con error:', data.message);
                }
            })
            .catch(error => {
                console.error('Error completo al verificar desafiaments pendents:', error);
                // No mostrar alerta al usuario para evitar interrupciones, solo registrar en consola
                // Si es necesario, mostrar algún indicador sutil en la interfaz
            });
    }

    /**
     * Actualitzar el comptador de desafiaments rebuts
     */
    function actualitzarComptadorDesafiaments(cantidad) {
        if (comptadorDesafiaments) {
            if (cantidad > 0) {
                comptadorDesafiaments.textContent = cantidad;
                comptadorDesafiaments.style.display = 'inline-block';
            } else {
                comptadorDesafiaments.style.display = 'none';
            }
        }
    }

    /**
     * Carregar desafiaments pendents al modal
     */
    function carregarDesafiamentsPendents() {
        const rebutsList = document.getElementById('desafios-recibidos-list');
        const enviatsList = document.getElementById('desafios-enviados-list');
        
        // Renderitzar desafiaments rebuts
        if (rebutsList) {
            if (desafiamentsPendents.rebuts.length === 0) {
                rebutsList.innerHTML = `
                    <div class="no-desafios-message">
                        <p>No tens desafiaments pendents per acceptar.</p>
                    </div>
                `;
            } else {
                rebutsList.innerHTML = '';
                desafiamentsPendents.rebuts.forEach(desafiament => {
                    const dataCreacio = new Date(desafiament.fecha_creacion);
                    const dataFormatejada = dataCreacio.toLocaleString();
                    
                    const desafiamentCard = document.createElement('div');
                    desafiamentCard.className = 'desafio-card';
                    desafiamentCard.innerHTML = `
                        <div class="desafio-header">
                            <div class="desafio-avatar">
                                <img src="Vista/assets/img/avatars/${desafiament.retador_avatar}" 
                                     alt="Avatar" 
                                     onerror="this.src='Vista/assets/img/avatars/Youngster.png'">
                            </div>
                            <div class="desafio-info">
                                <h4>${desafiament.retador_nombre} et desafia</h4>
                                <div class="desafio-fecha">Enviat: ${dataFormatejada}</div>
                            </div>
                        </div>
                        <div class="desafio-equipo">
                            <div class="desafio-equipo-nombre">Equip: ${desafiament.equipo_retador_nombre}</div>
                        </div>
                        <div class="desafio-actions">
                            <button class="btn-acceptar" data-desafio-id="${desafiament.id_batalla}">Acceptar</button>
                            <button class="btn-rebutjar" data-desafio-id="${desafiament.id_batalla}">Rebutjar</button>
                        </div>
                    `;
                    
                    rebutsList.appendChild(desafiamentCard);
                    
                    // Afegir event listeners als botons
                    desafiamentCard.querySelector('.btn-acceptar').addEventListener('click', function() {
                        obrirModalAcceptarDesafiament(this.dataset.desafioId);
                    });
                    
                    desafiamentCard.querySelector('.btn-rebutjar').addEventListener('click', function() {
                        const desafioId = this.dataset.desafioId;
                        if (confirm('Estàs segur que vols rebutjar aquest desafiament?')) {
                            rebutjarDesafiamentDirectament(desafioId);
                        }
                    });
                });
            }
        }
        
        // Renderitzar desafiaments enviats
        if (enviatsList) {
            if (desafiamentsPendents.enviats.length === 0) {
                enviatsList.innerHTML = `
                    <div class="no-desafios-message">
                        <p>No has enviat cap desafiament pendent.</p>
                    </div>
                `;
            } else {
                enviatsList.innerHTML = '';
                desafiamentsPendents.enviats.forEach(desafiament => {
                    const dataCreacio = new Date(desafiament.fecha_creacion);
                    const dataFormatejada = dataCreacio.toLocaleString();
                    
                    const desafiamentCard = document.createElement('div');
                    desafiamentCard.className = 'desafio-card';
                    desafiamentCard.innerHTML = `
                        <div class="desafio-header">
                            <div class="desafio-avatar">
                                <img src="Vista/assets/img/avatars/${desafiament.retado_avatar}" 
                                     alt="Avatar" 
                                     onerror="this.src='Vista/assets/img/avatars/Youngster.png'">
                            </div>
                            <div class="desafio-info">
                                <h4>Desafiament a ${desafiament.retado_nombre}</h4>
                                <div class="desafio-fecha">Enviat: ${dataFormatejada}</div>
                            </div>
                        </div>
                        <div class="desafio-equipo">
                            <div class="desafio-equipo-nombre">Equip: ${desafiament.equipo_retador_nombre}</div>
                        </div>
                        <div class="desafio-actions">
                            <button class="btn-cancelar" data-desafio-id="${desafiament.id_batalla}">Cancel·lar</button>
                        </div>
                    `;
                    
                    enviatsList.appendChild(desafiamentCard);
                    
                    // Afegir event listener al botó de cancel·lar
                    desafiamentCard.querySelector('.btn-cancelar').addEventListener('click', function() {
                        const desafioId = this.dataset.desafioId;
                        if (confirm('Estàs segur que vols cancel·lar aquest desafiament?')) {
                            cancellarDesafiament(desafioId);
                        }
                    });
                });
            }
        }
    }

    /**
     * Canviar de pestanya al modal de desafiaments
     */
    function canviarTabDesafiaments(tabType) {
        // Actualitzar les pestanyes actives
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`.tab-btn[data-tab="${tabType}"]`).classList.add('active');
        
        // Mostrar el contingut corresponent
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        document.getElementById(`tab-${tabType}`).classList.add('active');
    }

    /**
     * Enviar un desafiament a un altre entrenador
     */
    function enviarDesafiament() {
        if (!selectedEntrenadorId || !selectedEquipoId) {
            alert('Has de seleccionar un entrenador i un equip per al desafiament.');
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'crear_desafiament');
        formData.append('retado_id', selectedEntrenadorId);
        formData.append('equipo_id', selectedEquipoId);
        
        fetch('Controlador/batalla_api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error(`Error HTTP: ${response.status}. Resposta: ${text}`);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Cerrar el modal
                tancarModalDesafiar();
                
                // Redirigir al creador a la sala de batalla
                if (data.batalla_id) {
                    // Redirigir a Combat_Vista.php en lugar de Batalla_Vista.php
                    window.location.href = `Vista/Combat_Vista.php?id_batalla=${data.batalla_id}`;
                } else {
                    // Si no hay redirección disponible, mostrar mensaje i recargar
                    alert('Desafiament enviat correctament!');
                    verificarDesafiamentsPendents();
                }
            } else {
                alert('Error en enviar el desafiament: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error complet en enviar el desafiament:', error);
            alert('Error en processar la sol·licitud. Vegeu la consola per a més detalls.');
        });
    }

    /**
     * Acceptar un desafiament pendent
     */
    function acceptarDesafiament() {
        if (!selectedDesafioId || !selectedDesafioEquipoId) {
            alert('Has de seleccionar un equip per acceptar el desafiament.');
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'acceptar_desafiament');
        formData.append('batalla_id', selectedDesafioId);
        formData.append('equipo_id', selectedDesafioEquipoId);
        
        fetch('Controlador/batalla_api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error(`Error HTTP: ${response.status}. Resposta: ${text}`);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Cerrar los modales
                tancarModalAcceptarDesafiament();
                tancarModalDesafiaments();
                
                // Redirigir al usuario a la sala de combate
                if (data.batalla_id) {
                    // Usar una ruta absoluta desde la raíz del sitio web
                    const baseUrl = window.location.pathname.includes('/Vista/') 
                        ? '../' 
                        : '';
                    window.location.href = `${baseUrl}Vista/Combat_Vista.php?id_batalla=${data.batalla_id}`;
                } else {
                    // Si no hay redirección disponible, mostrar mensaje i recargar
                    alert('Desafiament acceptat correctament!');
                    window.location.reload();
                }
            } else {
                alert('Error en acceptar el desafiament: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error complet en acceptar el desafiament:', error);
            alert('Error en processar la sol·licitud. Vegeu la consola per a més detalls.');
        });
    }

    /**
     * Rebutjar un desafiament des del modal d'acceptació
     */
    function rebutjarDesafiament() {
        if (!selectedDesafioId) {
            alert('No s\'ha pogut identificar el desafiament a rebutjar.');
            return;
        }
        
        if (confirm('Estàs segur que vols rebutjar aquest desafiament?')) {
            rebutjarDesafiamentDirectament(selectedDesafioId);
            tancarModalAcceptarDesafiament();
        }
    }

    /**
     * Rebutjar un desafiament directament
     */
    function rebutjarDesafiamentDirectament(desafioId) {
        const formData = new FormData();
        formData.append('action', 'rebutjar_desafiament');
        formData.append('batalla_id', desafioId);
        
        fetch('Controlador/batalla_api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error(`Error HTTP: ${response.status}. Resposta: ${text}`);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert('Desafiament rebutjat.');
                verificarDesafiamentsPendents();
                carregarDesafiamentsPendents();
            } else {
                alert('Error en rebutjar el desafiament: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error complet en rebutjar el desafiament:', error);
            alert('Error en processar la sol·licitud. Vegeu la consola per a més detalls.');
        });
    }

    /**
     * Cancel·lar un desafiament enviat
     */
    function cancellarDesafiament(desafioId) {
        const formData = new FormData();
        formData.append('action', 'cancellar_desafiament');
        formData.append('batalla_id', desafioId);
        
        fetch('Controlador/batalla_api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error(`Error HTTP: ${response.status}. Resposta: ${text}`);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert('Desafiament cancel·lat.');
                verificarDesafiamentsPendents();
                carregarDesafiamentsPendents();
            } else {
                alert('Error en cancel·lar el desafiament: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error complet en cancel·lar el desafiament:', error);
            alert('Error en processar la sol·licitud. Vegeu la consola per a més detalls.');
        });
    }

    // Configurar event listeners per selecció d'entrenador
    document.querySelectorAll('.select-entrenador-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            seleccionarEntrenador(this.dataset.entrenadorId);
        });
    });
});