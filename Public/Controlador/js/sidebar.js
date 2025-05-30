// Archivo para gestionar la funcionalidad de la barra lateral de amigos
document.addEventListener('DOMContentLoaded', function() {
    // Elementos del DOM
    const friendsToggle = document.getElementById('friends-toggle');
    const friendsSidebar = document.getElementById('friends-sidebar');
    const mainContent = document.getElementById('main-content');
    const searchInput = document.getElementById('friend-search');
    const searchButton = document.getElementById('search-friend-btn');
    const resultsContainer = document.getElementById('search-results');
    const friendRequestsSection = document.getElementById('friend-requests-section');
    const friendsList = document.querySelector('.friends-list');
            
    // Toggle para la barra lateral
    if (friendsToggle && friendsSidebar && mainContent) {
        // Function to toggle sidebar visibility
        function toggleSidebar() {
            friendsSidebar.classList.toggle('hidden');
            friendsToggle.classList.toggle('expanded');
            mainContent.classList.toggle('expanded');
            
            // Update the icon based on sidebar state
            const icon = friendsToggle.querySelector('i');
            if (friendsSidebar.classList.contains('hidden')) {
                // Sidebar is hidden - show left chevron (to open sidebar)
                icon.className = 'fas fa-chevron-left';
            } else {
                // Sidebar is visible - show right chevron (to hide sidebar)
                icon.className = 'fas fa-chevron-right';
            }
            
            // Save preference in local storage
            localStorage.setItem('sidebar-hidden', friendsSidebar.classList.contains('hidden'));
        }
        
        // Add click event
        friendsToggle.addEventListener('click', toggleSidebar);
        
        // Check for saved preference
        const sidebarHidden = localStorage.getItem('sidebar-hidden') === 'true';
        if (sidebarHidden) {
            // If it was hidden before, update both the sidebar and the icon
            toggleSidebar();
        } else {
            // Initialize with the correct arrow (right facing when sidebar is visible)
            friendsToggle.querySelector('i').className = 'fas fa-chevron-right';
        }
    }

    // Iniciar verificación periódica de solicitudes pendientes y lista de amigos
    if (document.body.dataset.usuariId) {
        verificarSolicitudesPendientes();
        verificarListaAmigos();
        // Verificar cada 20 segundos
        setInterval(verificarSolicitudesPendientes, 20000);
        setInterval(verificarListaAmigos, 30000);
    }

    // Asignar eventos a los botones existentes de la lista de amigos al cargar la página
    if (friendsList) {
        // Añadir eventos a los botones de chat y eliminar amigos que ya existen al cargar la página
        friendsList.querySelectorAll('.friend-item').forEach(friendItem => {
            const friendId = friendItem.dataset.friendId;
            const friendName = friendItem.querySelector('.friend-name').textContent;
            const friendAvatar = friendItem.querySelector('.friend-avatar img').getAttribute('src').split('/').pop();
            
            // Chat button
            const chatBtn = friendItem.querySelector('.chat-btn');
            if (chatBtn) {
                chatBtn.addEventListener('click', function() {
                    abrirChat(friendId, friendName, friendAvatar);
                });
            }
            
            // Delete button
            const deleteBtn = friendItem.querySelector('.delete-btn');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', function() {
                    eliminarAmigo(friendId, friendName);
                });
            }
        });
    }

    /**
     * Función para verificar solicitudes de amistad pendientes
     */
    function verificarSolicitudesPendientes() {
        fetch('Controlador/AmicsControlador.php?action=check_pending_requests', {
            method: 'GET',
            headers: {
                'Cache-Control': 'no-cache'
            }
        })
            .then(response => {
                // First check if the response is OK
                if (!response.ok) {
                    throw new Error(`HTTP error ${response.status}`);
                }
                
                // Check content type
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        console.error('Server returned non-JSON response:', text.substring(0, 100));
                        throw new Error('Server did not return JSON');
                    });
                }
                
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    actualizarSolicitudesPendientes(data.solicitudes);
                }
            })
            .catch(error => {
                console.error('Error al verificar solicitudes pendientes:', error);
                // Don't show alerts to users, just log to console
            });
    }

    /**
     * Función para verificar la lista de amigos y actualizarla
     */
    function verificarListaAmigos() {
        if (!friendsList) return;

        fetch('Controlador/AmicsControlador.php?action=get_friends_list', {
            method: 'GET',
            headers: {
                'Cache-Control': 'no-cache'
            }
        })
            .then(response => {
                // First check if the response is OK
                if (!response.ok) {
                    throw new Error(`HTTP error ${response.status}`);
                }
                
                // Check content type
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        console.error('Server returned non-JSON response:', text.substring(0, 100));
                        throw new Error('Server did not return JSON');
                    });
                }
                
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    actualizarListaAmigos(data.amigos);
                }
            })
            .catch(error => {
                console.error('Error al verificar la lista de amigos:', error);
                // Don't show alerts to users, just log to console
            });
    }

    /**
     * Actualiza la UI con la lista de amigos actual
     */
    function actualizarListaAmigos(amigos) {
        if (!friendsList) return;
        const noFriendsMessage = document.querySelector('.no-friends-message');
        
        // Si no hay amigos, mostrar mensaje
        if (amigos.length === 0) {
            if (friendsList.children.length > 0) {
                // Eliminar todos los amigos actuales
                friendsList.innerHTML = '';
            }
            if (!noFriendsMessage) {
                // Mostrar mensaje de no hay amigos
                const noFriendsElement = document.createElement('div');
                noFriendsElement.className = 'no-friends-message';
                noFriendsElement.innerHTML = `
                    <span class="no-friends-pokemon">┗(°O°)┛</span>
                    <p>No hi ha entrenadors a la vista!</p>
                    <p>Surt a explorar i desafia nous entrenadors per augmentar la teva xarxa de batalles Pokémon!</p>
                `;
                // Añadir mensaje después de eliminar la lista de amigos
                friendsList.parentNode.appendChild(noFriendsElement);
                // Ocultar la lista vacía
                friendsList.style.display = 'none';
            }
            return;
        }
        
        // Si hay amigos, asegurarse de que la lista sea visible
        friendsList.style.display = 'block';
        
        // Si había un mensaje de no hay amigos, eliminarlo
        if (noFriendsMessage) {
            noFriendsMessage.remove();
        }

        // Obtener IDs de amigos actuales en la UI
        const amigosActualesMap = new Map();
        friendsList.querySelectorAll('.friend-item').forEach(el => {
            const id = el.dataset.friendId;
            amigosActualesMap.set(id, el);
        });

        // Procesar la lista de amigos
        amigos.forEach(amigo => {
            const amigoId = amigo.id_usuari.toString();
            const elementoExistente = amigosActualesMap.get(amigoId);
            const estatLabel = amigo.estat_label;
            const estatClass = amigo.estat_class.toLowerCase();

            if (elementoExistente) {
                // Actualizar estado si es necesario
                const statusElement = elementoExistente.querySelector('.friend-status');
                if (statusElement) {
                    statusElement.textContent = estatLabel;
                    statusElement.className = `friend-status ${estatClass}`;
                }
                // Marcar como procesado
                amigosActualesMap.delete(amigoId);
            } else {
                // Crear nuevo elemento para el amigo
                const nuevoAmigo = document.createElement('li');
                nuevoAmigo.className = 'friend-item';
                nuevoAmigo.dataset.friendId = amigoId;
                nuevoAmigo.innerHTML = `
                    <div class="friend-avatar">
                        <img src="Vista/assets/img/avatars/${amigo.avatar}" 
                             alt="Amic" 
                             onerror="this.src='Vista/assets/img/avatars/Youngster.png'">
                    </div>
                    <div class="friend-info">
                        <p class="friend-name">${amigo.nom_usuari}</p>
                        <p class="friend-status ${estatClass}">${estatLabel}</p>
                    </div>
                    <div class="friend-actions">
                        <button class="btn friend-action-btn chat-btn">Xat</button>
                        <button class="btn friend-action-btn delete-btn" title="Eliminar amic">
                            <i class="fas fa-user-slash"></i>
                        </button>
                    </div>
                `;
                friendsList.appendChild(nuevoAmigo);
                
                // Añadir listener para el chat
                const chatBtn = nuevoAmigo.querySelector('.chat-btn');
                if (chatBtn) {
                    chatBtn.addEventListener('click', function() {
                        abrirChat(amigoId, amigo.nom_usuari, amigo.avatar);
                    });
                }
                
                // Añadir listener para eliminar amigo (corregido)
                const deleteBtn = nuevoAmigo.querySelector('.delete-btn');
                if (deleteBtn) {
                    deleteBtn.addEventListener('click', function() {
                        eliminarAmigo(amigoId, amigo.nom_usuari);
                    });
                }
            }
        });

        // Eliminar amigos que ya no están en la lista
        amigosActualesMap.forEach((elemento, id) => {
            elemento.remove();
        });

        // Actualizar eventos de click para los botones - asegurándonos de que los botones de borrar funcionen correctamente
        friendsList.querySelectorAll('.friend-item').forEach(friendItem => {
            const friendId = friendItem.dataset.friendId;
            const friendName = friendItem.querySelector('.friend-name').textContent;
            const friendAvatar = friendItem.querySelector('.friend-avatar img').getAttribute('src').split('/').pop();
            
            // Chat button
            const chatBtn = friendItem.querySelector('.chat-btn');
            if (chatBtn) {
                // Eliminar eventos anteriores para evitar duplicados
                chatBtn.replaceWith(chatBtn.cloneNode(true));
                // Añadir nuevo evento
                friendItem.querySelector('.chat-btn').addEventListener('click', function() {
                    abrirChat(friendId, friendName, friendAvatar);
                });
            }
            
            // Delete button - corregir aquí para que funcione correctamente
            const deleteBtn = friendItem.querySelector('.delete-btn');
            if (deleteBtn) {
                // Eliminar eventos anteriores para evitar duplicados
                deleteBtn.replaceWith(deleteBtn.cloneNode(true));
                // Añadir nuevo evento
                friendItem.querySelector('.delete-btn').addEventListener('click', function() {
                    eliminarAmigo(friendId, friendName);
                });
            }
        });
    }

    /**
     * Función para abrir chat con un amigo
     */
    function abrirChat(friendId, friendName, friendAvatar) {
        // Delegamos esta acción al script de chat.js que ya debe estar implementado
        if (window.openChatModal) {
            window.openChatModal(friendId, friendName, friendAvatar);
        } else {
            console.error('La función openChatModal no está disponible');
        }
    }

    /**
     * Función para eliminar un amigo
     */
    function eliminarAmigo(friendId, friendName) {
        if (!confirm(`Estàs segur que vols eliminar a ${friendName} de la teva llista d'amics?`)) {
            return;
        }

        const formData = new FormData();
        formData.append('amic_id', friendId);

        fetch('Controlador/eliminar_amigo.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Eliminar amigo de la UI inmediatamente
                const friendElement = document.querySelector(`.friend-item[data-friend-id="${friendId}"]`);
                if (friendElement) {
                    friendElement.remove();
                }
                
                // Verificar si quedan amigos
                const remainingFriends = document.querySelectorAll('.friend-item');
                if (remainingFriends.length === 0) {
                    // No quedan amigos, mostrar mensaje
                    const friendsContainer = document.querySelector('.friends-list').parentNode;
                    const noFriendsMessage = document.createElement('div');
                    noFriendsMessage.className = 'no-friends-message';
                    noFriendsMessage.innerHTML = `
                        <span class="no-friends-pokemon">┗(°O°)┛</span>
                        <p>No hi ha entrenadors a la vista!</p>
                        <p>Surt a explorar i desafia nous entrenadors per augmentar la teva xarxa de batalles Pokémon!</p>
                    `;
                    friendsContainer.appendChild(noFriendsMessage);
                    document.querySelector('.friends-list').style.display = 'none';
                }
                
                alert(`${friendName} ha estat eliminat de la teva llista d'amics.`);
            } else {
                alert('Error: ' + (data.message || 'No s\'ha pogut eliminar l\'amic'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Hi ha hagut un problema en eliminar l\'amic.');
        });
    }

    /**
     * Actualiza la UI con las solicitudes pendientes
     */
    function actualizarSolicitudesPendientes(solicitudes) {
        if (!friendRequestsSection) return;

        const header = friendRequestsSection.querySelector('.friend-requests-header');
        
        // Actualizar contador de solicitudes
        if (header) {
            header.innerHTML = 'Sol·licituds pendents';
            if (solicitudes.length > 0) {
                header.innerHTML += `<span class="friend-request-count">${solicitudes.length}</span>`;
            }
        }

        // Si no hay contenedor para las solicitudes, no continuamos
        const contenidoActual = friendRequestsSection.querySelector('.friend-request, .no-requests-message');
        if (!contenidoActual) return;

        // Si no hay solicitudes nuevas
        if (solicitudes.length === 0) {
            // Si hay un mensaje de "no hay solicitudes", lo dejamos
            if (friendRequestsSection.querySelector('.no-requests-message')) {
                return;
            }
            
            // Si hay solicitudes en la UI, las eliminamos y ponemos mensaje
            const solicitudesUI = friendRequestsSection.querySelectorAll('.friend-request');
            if (solicitudesUI.length > 0) {
                solicitudesUI.forEach(el => el.remove());
                friendRequestsSection.appendChild(crearElementoNoSolicitudes());
            }
            return;
        }

        // Si hay un mensaje de "no hay solicitudes", lo eliminamos
        const noSolicitudesMsg = friendRequestsSection.querySelector('.no-requests-message');
        if (noSolicitudesMsg) {
            noSolicitudesMsg.remove();
        }

        // Obtener IDs de las solicitudes actuales en la UI
        const solicitudesActuales = new Set();
        friendRequestsSection.querySelectorAll('.friend-request').forEach(el => {
            const id = el.id.replace('sollicitud-', '');
            solicitudesActuales.add(id);
        });

        // Añadir nuevas solicitudes que no están en la UI
        solicitudes.forEach(solicitud => {
            if (!solicitudesActuales.has(solicitud.solicitud_id.toString())) {
                const nuevaSolicitud = crearElementoSolicitud(solicitud);
                if (friendRequestsSection.querySelector('.friend-request')) {
                    // Si ya hay solicitudes, añadir al final
                    friendRequestsSection.appendChild(nuevaSolicitud);
                } else {
                    // Si no hay solicitudes, añadir después del encabezado
                    if (header) {
                        header.insertAdjacentElement('afterend', nuevaSolicitud);
                    } else {
                        friendRequestsSection.appendChild(nuevaSolicitud);
                    }
                }
                // Mostrar notificación de nueva solicitud
                mostrarNotificacionNuevaSolicitud(solicitud.nom_usuari);
            }
            // Marcar como procesada
            solicitudesActuales.delete(solicitud.solicitud_id.toString());
        });

        // Eliminar solicitudes que ya no están pendientes
        solicitudesActuales.forEach(id => {
            const elementoSolicitud = document.getElementById(`sollicitud-${id}`);
            if (elementoSolicitud) {
                elementoSolicitud.remove();
            }
        });
    }

    /**
     * Muestra una notificación cuando hay una nueva solicitud de amistad
     */
    function mostrarNotificacionNuevaSolicitud(nombreUsuario) {
        // Comprobar si el navegador soporta notificaciones
        if (!("Notification" in window)) {
            console.log("Este navegador no soporta notificaciones");
            return;
        }

        // Si ya tenemos permiso, mostrar notificación
        if (Notification.permission === "granted") {
            const notification = new Notification("Nova sol·licitud d'amistat", {
                body: `${nombreUsuario} t'ha enviat una sol·licitud d'amistat`,
                icon: "Vista/assets/img/Poké_Ball_icon.png"
            });
            
            notification.onclick = function() {
                window.focus();
                this.close();
            };
        } 
        // Si no hemos pedido permiso, pedirlo
        else if (Notification.permission !== "denied") {
            Notification.requestPermission().then(function(permission) {
                if (permission === "granted") {
                    const notification = new Notification("Nova sol·licitud d'amistat", {
                        body: `${nombreUsuario} t'ha enviat una sol·licitud d'amistat`,
                        icon: "Vista/assets/img/Poké_Ball_icon.png"
                    });
                    
                    notification.onclick = function() {
                        window.focus();
                        this.close();
                    };
                }
            });
        }
    }

    /**
     * Crear elemento HTML para una solicitud de amistad
     */
    function crearElementoSolicitud(solicitud) {
        const solicitudElement = document.createElement('div');
        solicitudElement.className = 'friend-request';
        solicitudElement.id = `sollicitud-${solicitud.solicitud_id}`;
        solicitudElement.innerHTML = `
            <div class="friend-request-avatar">
                <img src="Vista/assets/img/avatars/${solicitud.avatar}" 
                     alt="Avatar" 
                     onerror="this.src='Vista/assets/img/avatars/Youngster.png'">
            </div>
            <div class="friend-request-info">
                <div class="friend-request-name">${solicitud.nom_usuari}</div>
            </div>
            <div class="friend-request-actions">
                <button class="accept-btn" onclick="gestionaAmistat(${solicitud.solicitud_id}, 'acceptar')" title="Acceptar">
                    <i class="fas fa-check"></i>
                </button>
                <button class="reject-btn" onclick="gestionaAmistat(${solicitud.solicitud_id}, 'rebutjar')" title="Rebutjar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        return solicitudElement;
    }

    /**
     * Crear elemento HTML para mensaje de no solicitudes
     */
    function crearElementoNoSolicitudes() {
        const noSolicitudesElement = document.createElement('p');
        noSolicitudesElement.className = 'no-requests-message';
        noSolicitudesElement.textContent = 'Cap sol·licitud pendent';
        return noSolicitudesElement;
    }
    
    // Búsqueda de amigos
    if (searchInput && searchButton) {
        searchButton.addEventListener('click', function() {
            // Create results list element if it doesn't exist
            if (!document.getElementById('search-results-list')) {
                const newResultsList = document.createElement('div');
                newResultsList.id = 'search-results-list';
                resultsContainer.appendChild(newResultsList);
            }
            searchFriends();
        });
        
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                // Create results list element if it doesn't exist
                if (!document.getElementById('search-results-list')) {
                    const newResultsList = document.createElement('div');
                    newResultsList.id = 'search-results-list';
                    resultsContainer.appendChild(newResultsList);
                }
                searchFriends();
            }
        });
    }
    
    // Función para buscar amigos
    function searchFriends() {
        const searchTerm = searchInput.value.trim();
        
        if (searchTerm.length < 2) {
            alert('Por favor, introduce al menos 2 caracteres para buscar.');
            return;
        }
        
        // Get the most up-to-date reference to the results list
        let resultsList = document.getElementById('search-results-list');
        
        // If results list doesn't exist, create it
        if (!resultsList) {
            const newResultsList = document.createElement('div');
            newResultsList.id = 'search-results-list';
            resultsContainer.appendChild(newResultsList);
            // Update reference
            resultsList = newResultsList;
        }
        
        // Show loading state
        resultsList.innerHTML = '<div class="loading">Buscando usuarios...</div>';
        resultsContainer.style.display = 'block';
        
        // Add cache-busting parameter to avoid caching issues
        const cacheBuster = new Date().getTime();
        
        fetch(`Controlador/Usuaris/search_users.php?term=${encodeURIComponent(searchTerm)}&_=${cacheBuster}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Error de red: ${response.status}`);
                }
                return response.text().then(text => {
                    try {
                        // Try to parse as JSON
                        const data = JSON.parse(text);
                        return data;
                    } catch (e) {
                        // If parsing fails, log the raw response and throw an error
                        console.error('Invalid JSON response:', text);
                        throw new Error('Respuesta inválida del servidor');
                    }
                });
            })
            .then(data => {
                if (!resultsList) {
                    console.error('Results list element no longer exists');
                    return;
                }
                
                resultsList.innerHTML = '';
                
                if (!Array.isArray(data)) {
                    console.error('Expected array but got:', data);
                    resultsList.innerHTML = '<div class="no-results">Error en el formato de datos</div>';
                    return;
                }
                
                console.log(`Search returned ${data.length} results`);
                
                if (data.length === 0) {
                    resultsList.innerHTML = '<div class="no-results">No se encontraron usuarios</div>';
                } else {
                    data.forEach(user => {
                        const userItem = document.createElement('div');
                        userItem.className = 'friend-request';
                        userItem.innerHTML = `
                            <div class="friend-request-avatar">
                                <img src="Vista/assets/img/avatars/${user.avatar || 'Youngster.png'}" 
                                     alt="Avatar"
                                     onerror="this.src='Vista/assets/img/avatars/Youngster.png'">
                            </div>
                            <div class="friend-request-info">
                                <div class="friend-request-name">${user.nom_usuari || 'Usuario sin nombre'}</div>
                            </div>
                            <div class="friend-request-actions">
                                <button class="accept-btn" onclick="sendFriendRequest(${user.id_usuari})">
                                    <i class="fas fa-user-plus"></i>
                                </button>
                            </div>
                        `;
                        resultsList.appendChild(userItem);
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (resultsList) {
                    resultsList.innerHTML = `<div class="no-results">Error al buscar: ${error.message}</div>`;
                }
            });
    }

    // Funciones globales para gestión de amigos
    window.sendFriendRequest = function(userId) {
        fetch('Controlador/friend_action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=send_request&user_id=${userId}`
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Error de red');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert('Solicitud de amistad enviada correctamente');
                // Hide the user from search results
                searchFriends(); // Refresh results
                
                // Clear the search input
                document.getElementById('friend-search').value = '';
                
                // Hide search results
                document.getElementById('search-results').style.display = 'none';
            } else {
                alert('Error: ' + (data.error || 'Ha ocurrido un problema desconocido'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al enviar la solicitud: ' + error.message);
        });
    };

    // Función para gestionar solicitudes de amistad (aceptar/rechazar)
    window.gestionaAmistat = function(sollicitudId, accio) {
        console.log(`Processing ${accio} for request ID: ${sollicitudId}`);
        
        fetch('Controlador/gestiona_amistat.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `sollicitud_id=${sollicitudId}&accio=${accio}`
        })
        .then(response => {
            console.log("Response status:", response.status);
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.text().then(text => {
                try {
                    // Intentamos parsear el texto como JSON
                    return JSON.parse(text);
                } catch (e) {
                    console.error("Error al parsear JSON:", text);
                    throw new Error("Respuesta no válida del servidor");
                }
            });
        })
        .then(data => {
            console.log("Response data:", data);
            if (data.success) {
                // Remove the request from UI
                const requestElement = document.getElementById(`sollicitud-${sollicitudId}`);
                if (requestElement) {
                    requestElement.remove();
                }
                
                // Check if that was the last request
                const remainingRequests = document.querySelectorAll('.friend-request');
                if (remainingRequests.length === 0) {
                    const requestsSection = document.querySelector('.friend-requests-section');
                    if (requestsSection) {
                        const header = requestsSection.querySelector('.friend-requests-header');
                        if (header) {
                            header.innerHTML = 'Sol·licituds pendents';
                        }
                        requestsSection.appendChild(crearElementoNoSolicitudes());
                    }
                }
                
                // If the request was accepted, update friends list immediately
                if (accio === 'acceptar') {
                    // En lugar de recargar la página, solicitamos la lista actualizada de amigos
                    verificarListaAmigos();
                    alert('Amistat acceptada correctament!');
                } else {
                    alert('Sol·licitud rebutjada correctament');
                }
            } else {
                alert('Error: ' + (data.message || 'Hi ha hagut un problema'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error en gestionar la sol·licitud: ' + error);
        });
    };
});