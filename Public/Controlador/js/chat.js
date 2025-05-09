// Archivo para gestionar la funcionalidad de chat y avatares
document.addEventListener('DOMContentLoaded', function() {
    // Chat variables
    let currentChatFriendId = null;
    let lastMessageTime = null;
    let chatPollingInterval = null;
    const POLLING_INTERVAL = 5000; // Intervalo de actualización en ms
    
    // Avatar selector functionality
    const avatarTrigger = document.getElementById('avatar-trigger');
    const avatarModal = document.getElementById('avatar-modal');
    const closeModal = document.querySelector('#avatar-modal .close-modal');
    const avatarItems = document.querySelectorAll('.avatar-item');
    const currentAvatar = document.getElementById('current-avatar');
    
    // Open the avatar modal when clicking on the user avatar
    if (avatarTrigger && avatarModal) {
        avatarTrigger.addEventListener('click', function() {
            avatarModal.classList.add('active');
        });
    }
    
    // Close the avatar modal when clicking on the X
    if (closeModal) {
        closeModal.addEventListener('click', function() {
            avatarModal.classList.remove('active');
        });
    }
    
    // Close the avatar modal when clicking outside the content
    if (avatarModal) {
        avatarModal.addEventListener('click', function(e) {
            if (e.target === avatarModal) {
                avatarModal.classList.remove('active');
            }
        });
    }
    
    // Handle avatar selection
    avatarItems.forEach(item => {
        item.addEventListener('click', function() {
            const selectedAvatar = this.dataset.avatar;
            
            // Remove selected class from all items
            avatarItems.forEach(avatar => avatar.classList.remove('selected'));
            
            // Add selected class to the chosen option
            this.classList.add('selected');
            
            // Update avatar image in header
            if (currentAvatar) {
                currentAvatar.src = `Vista/assets/img/avatars/${selectedAvatar}`;
            }
            
            // Save avatar selection to the server
            updateUserAvatar(selectedAvatar);
            
            // Close modal with a small delay to show the selection
            setTimeout(() => {
                avatarModal.classList.remove('active');
            }, 300);
        });
    });
    
    // Function to save avatar selection
    function updateUserAvatar(avatarName) {
        const formData = new FormData();
        formData.append('avatar', avatarName);
        
        fetch('Controlador/update_avatar.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Avatar updated successfully');
            } else {
                console.error('Error updating avatar:', data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }

    // Chat functionality
    setupChatEvents();

    function setupChatEvents() {
        const chatModal = document.getElementById('chat-modal');
        const closeChat = document.getElementById('close-chat');
        const chatMessages = document.getElementById('chat-messages');
        const chatInput = document.getElementById('chat-input');
        const sendMessage = document.getElementById('send-message');
        
        document.querySelectorAll('.friend-action-btn').forEach(button => {
            button.addEventListener('click', function() {
                const friendItem = this.closest('.friend-item');
                const friendId = friendItem.dataset.friendId;
                if (!friendId) {
                    console.error('No se pudo encontrar el ID del amigo');
                    return;
                }

                openChatWithFriend(friendId);
            });
        });

        if (closeChat) {
            closeChat.addEventListener('click', closeCurrentChat);
        }

        if (chatModal) {
            chatModal.addEventListener('click', function(e) {
                if (e.target === chatModal) {
                    closeCurrentChat();
                }
            });
        }

        if (sendMessage) {
            sendMessage.addEventListener('click', sendChatMessage);
        }

        if (chatInput) {
            chatInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    sendChatMessage();
                }
            });
        }

        startCheckingForNewMessages();
    }

    function openChatWithFriend(friendId) {
        const chatModal = document.getElementById('chat-modal');
        const chatMessages = document.getElementById('chat-messages');
        const chatInput = document.getElementById('chat-input');
        const friendItem = document.querySelector(`.friend-item[data-friend-id="${friendId}"]`);
        
        if (!friendItem) {
            console.error('No se pudo encontrar el elemento del amigo');
            return;
        }

        const friendName = friendItem.querySelector('.friend-name').textContent;
        const friendAvatar = friendItem.querySelector('.friend-avatar img').src;
        
        document.getElementById('chat-username').textContent = friendName;
        document.getElementById('chat-user-avatar').src = friendAvatar;
        document.getElementById('chat-user-avatar').onerror = function() {
            this.src = 'Vista/assets/img/avatars/Youngster.png';
        };
        
        currentChatFriendId = friendId;
        chatModal.dataset.friendId = friendId;
        
        chatMessages.innerHTML = '<div class="pokemon-loader"><img src="Vista/assets/img/Poké_Ball_icon.png" alt="Loading..."><p>Carregant missatges...</p></div>';
        
        chatModal.classList.add('active');
        
        loadChatMessages(friendId);
        
        setTimeout(() => chatInput.focus(), 300);
    }

    function loadChatMessages(friendId) {
        fetch(`Controlador/cargar_missatges.php?amic_id=${friendId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayMessages(data.missatges);
                    if (data.missatges.length > 0) {
                        lastMessageTime = data.missatges[data.missatges.length - 1].data_enviament;
                    }
                } else {
                    console.error('Error al cargar mensajes:', data.error);
                    document.getElementById('chat-messages').innerHTML = 
                        '<div class="error-message">Error al cargar los mensajes</div>';
                }
            })
            .catch(error => {
                console.error('Error fetching messages:', error);
                document.getElementById('chat-messages').innerHTML = 
                    '<div class="error-message">Error de conexión al cargar mensajes</div>';
            });
    }

    function displayMessages(messages) {
        const chatMessages = document.getElementById('chat-messages');
        const usuariId = parseInt(document.body.dataset.usuariId) || null;
        
        chatMessages.innerHTML = '';
        
        if (messages.length === 0) {
            chatMessages.innerHTML = '<div class="no-messages">No hay mensajes. ¡Se el primero en escribir!</div>';
            return;
        }
        
        messages.forEach(msg => {
            const isOwnMessage = parseInt(msg.emissor_id) === usuariId;
            const messageElement = document.createElement('div');
            messageElement.className = `message ${isOwnMessage ? 'message-sent' : 'message-received'}`;
            
            const time = new Date(msg.data_enviament).toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit'
            });
            
            messageElement.innerHTML = `
                <div class="message-content">${msg.contingut}</div>
                <div class="message-time">${time}</div>
            `;
            
            chatMessages.appendChild(messageElement);
        });
        
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function sendChatMessage() {
        const chatInput = document.getElementById('chat-input');
        const message = chatInput.value.trim();
        if (!message || !currentChatFriendId) return;
        
        chatInput.disabled = true;
        
        const formData = new FormData();
        formData.append('receptor_id', currentChatFriendId);
        formData.append('contingut', message);
        
        fetch('Controlador/enviar_missatge.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                chatInput.value = '';
                loadChatMessages(currentChatFriendId);
            } else {
                console.error('Error al enviar mensaje:', data.error);
                alert('Error al enviar el mensaje: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión al enviar el mensaje');
        })
        .finally(() => {
            chatInput.disabled = false;
            chatInput.focus();
        });
    }

    function closeCurrentChat() {
        const chatModal = document.getElementById('chat-modal');
        chatModal.classList.remove('active');
        currentChatFriendId = null;
    }

    function startCheckingForNewMessages() {
        checkNewMessages();
        chatPollingInterval = setInterval(checkNewMessages, POLLING_INTERVAL);
    }

    function checkNewMessages() {
        if (currentChatFriendId) {
            let url = `Controlador/cargar_missatges.php?amic_id=${currentChatFriendId}`;
            if (lastMessageTime) {
                url += `&since=${encodeURIComponent(lastMessageTime)}`;
            }
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.missatges.length > 0) {
                        loadChatMessages(currentChatFriendId);
                    }
                })
                .catch(error => console.error('Error checking new messages:', error));
        }
        
        updateUnreadMessagesIndicators();
    }

    function updateUnreadMessagesIndicators() {
        fetch('Controlador/comprovar_missatges.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.querySelectorAll('.friend-item').forEach(item => {
                        const friendId = item.dataset.friendId;
                        const unreadCount = data.no_llegits[friendId] || 0;
                        
                        let countBadge = item.querySelector('.unread-count');
                        if (!countBadge && unreadCount > 0) {
                            countBadge = document.createElement('span');
                            countBadge.className = 'unread-count';
                            item.querySelector('.friend-actions').prepend(countBadge);
                        }
                        
                        if (countBadge) {
                            if (unreadCount > 0) {
                                countBadge.textContent = unreadCount;
                                countBadge.style.display = 'flex';
                            } else {
                                countBadge.style.display = 'none';
                            }
                        }
                    });
                    
                    const globalCounter = document.getElementById('global-unread-counter');
                    if (globalCounter && data.total > 0) {
                        globalCounter.textContent = data.total;
                        globalCounter.style.display = 'flex';
                    } else if (globalCounter) {
                        globalCounter.style.display = 'none';
                    }
                }
            })
            .catch(error => console.error('Error updating unread indicators:', error));
    }
});