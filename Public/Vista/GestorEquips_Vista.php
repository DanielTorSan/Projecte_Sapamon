<?php
// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuari_id'])) {
    header("Location: Auth_Vista.php");
    exit;
}

// Cargamos los amigos para el sidebar
$amics = [];
$hasAmics = false;
$pendingRequests = [];
$sollicitudsPendents = [];

if (isset($_SESSION['usuari_id'])) {
    require_once "Controlador/AmicsControlador.php";
    $amicsControlador = new AmicsControlador($connexio);
    $amics = $amicsControlador->getAmics($_SESSION['usuari_id']);
    $hasAmics = !empty($amics);
    $pendingRequests = $amicsControlador->getPendingRequests($_SESSION['usuari_id']);
    $sollicitudsPendents = $amicsControlador->getSollicitudsPendents($_SESSION['usuari_id']);
}
?>

<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor d'Equips - Sapamon</title>
    <link rel="stylesheet" href="Vista/assets/css/styles.css">
    <link rel="stylesheet" href="Vista/assets/css/home.css">
    <link rel="stylesheet" href="Vista/assets/css/dashboard.css">
    <link rel="stylesheet" href="Vista/assets/css/equip_gestor.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" href="Vista/assets/img/favicon/Poké_Ball_icon.png" type="image/png">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="header-container">
            <!-- Logo a la izquierda - replacing text with image -->
            <div class="logo-container">
                <img src="Vista/assets/img/Sapamon.png" alt="Sapamon" class="sapamon-logo-img">
            </div>
            
            <!-- Autenticación a la derecha -->
            <div class="auth-container">
                <?php if (isset($_SESSION['usuari_id']) && isset($_SESSION['nom_usuari'])): ?>
                    <!-- Usuario autenticado -->
                    <div class="user-profile">
                        <!-- Botón para gestor de equipos - ya no es necesario aquí porque estamos en esa página -->
                        <div class="user-avatar" id="avatar-trigger">
                            <?php 
                            // Get user avatar - if not set, use default
                            $userAvatar = isset($_SESSION['avatar']) ? $_SESSION['avatar'] : 'Youngster.png'; 
                            ?>
                            <img src="Vista/assets/img/avatars/<?php echo htmlspecialchars($userAvatar); ?>" 
                                 alt="Avatar"
                                 id="current-avatar"
                                 onerror="this.src='Vista/assets/img/avatars/Youngster.png'">
                            
                            <!-- Avatar selector dropdown -->
                            <div class="avatar-dropdown" id="avatar-dropdown">
                                <div class="avatar-dropdown-title">Selecciona un avatar</div>
                                <div class="avatar-grid">
                                    <?php
                                    // Get all avatars from the avatars directory
                                    $avatarDir = 'Vista/assets/img/avatars/';
                                    $avatarFiles = glob($avatarDir . '*.png');
                                    
                                    foreach ($avatarFiles as $avatar) {
                                        $avatarName = basename($avatar);
                                        $isSelected = ($avatarName === $userAvatar) ? 'selected' : '';
                                        echo '<div class="avatar-option ' . $isSelected . '" data-avatar="' . $avatarName . '">';
                                        echo '<img src="' . $avatarDir . $avatarName . '" alt="' . $avatarName . '">';
                                        echo '</div>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['nom_usuari']); ?></span>
                        <a href="Controlador/logout.php" class="logout-btn" title="Tancar sessió">
                            <i class="fa fa-sign-out-alt"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Usuario no autenticado -->
                    <div class="auth-buttons">
                        <a href="Vista/Auth_Vista.php" class="btn login-btn">Iniciar sessió</a>
                        <a href="Vista/Auth_Vista.php?registre=true" class="btn register-btn">Registrar-se</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Friends sidebar toggle button -->
    <div class="friends-toggle" id="friends-toggle">
        <i class="fas fa-chevron-left"></i>
    </div>

    <!-- Friends sidebar on the right -->
    <div class="friends-sidebar" id="friends-sidebar">
        <div class="friends-header">
            <h2>Amics</h2>
        </div>
        
        <?php if (isset($_SESSION['usuari_id'])): ?>
            <!-- User is logged in - show friends content-->
            <div class="add-friend">
                <input type="text" id="friend-search" placeholder="Afegir entrenador per nom">
                <button class="add-friend-btn" id="search-friend-btn" title="Buscar entrenador"></button>
            </div>
            
            <!-- Search results section (hidden by default) -->
            <div id="search-results" style="display: none;">
                <div class="search-results-header">Resultats de la cerca</div>
                <!-- Make sure this element exists with the correct ID -->
                <div id="search-results-list"></div>
            </div>
            
            <!-- Friend Requests Received - between search and friends list -->
            <div class="friend-requests-section" id="friend-requests-section">
                <div class="friend-requests-header">
                    Sol·licituds pendents
                    <?php if (!empty($sollicitudsPendents)): ?>
                        <span class="friend-request-count"><?php echo count($sollicitudsPendents); ?></span>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($sollicitudsPendents)): ?>
                    <?php foreach ($sollicitudsPendents as $usuari): ?>
                        <div class="friend-request" id="sollicitud-<?php echo $usuari['solicitud_id']; ?>">
                            <div class="friend-request-avatar">
                                <img src="Vista/assets/img/avatars/<?php echo htmlspecialchars($usuari['avatar']); ?>" 
                                     alt="Avatar" 
                                     onerror="this.src='Vista/assets/img/avatars/Youngster.png'">
                            </div>
                            <div class="friend-request-info">
                                <div class="friend-request-name"><?php echo htmlspecialchars($usuari['nom_usuari']); ?></div>
                            </div>
                            <div class="friend-request-actions">
                                <button class="accept-btn" onclick="gestionaAmistat(<?php echo $usuari['solicitud_id']; ?>, 'acceptar')" title="Acceptar">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button class="reject-btn" onclick="gestionaAmistat(<?php echo $usuari['solicitud_id']; ?>, 'rebutjar')" title="Rebutjar">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-requests-message">Cap sol·licitud pendent</p>
                <?php endif; ?>
            </div>
            
            <!-- User is logged in - show friends list or message -->
            <?php if ($hasAmics): ?>
                <ul class="friends-list">
                    <?php foreach ($amics as $amic): ?>
                        <?php 
                            $estatLabel = $amicsControlador->getEstatLabel($amic['estat'], $amic['ultima_connexio']);
                            $estatClass = strtolower($estatLabel);
                        ?>
                        <li class="friend-item" data-friend-id="<?php echo $amic['id_usuari']; ?>">
                            <div class="friend-avatar">
                                <img src="Vista/assets/img/avatars/<?php echo htmlspecialchars($amic['avatar']); ?>" 
                                     alt="Amic" 
                                     onerror="this.src='Vista/assets/img/avatars/Youngster.png'">
                            </div>
                            <div class="friend-info">
                                <p class="friend-name"><?php echo htmlspecialchars($amic['nom_usuari']); ?></p>
                                <p class="friend-status <?php echo $estatClass; ?>"><?php echo $estatLabel; ?></p>
                            </div>
                            <div class="friend-actions">
                                <button class="btn friend-action-btn chat-btn">Xat</button>
                                <button class="btn friend-action-btn delete-friend-btn" title="Eliminar amic"><i class="fas fa-user-slash"></i></button>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <!-- No friends yet - show Pokemon-style message -->
                <div class="no-friends-message">
                    <span class="no-friends-pokemon">┗(°O°)┛</span>
                    <p>No hi ha entrenadors a la vista!</p>
                    <p>Surt a explorar i desafia nous entrenadors per augmentar la teva xarxa de batalles Pokémon!</p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <!-- User is not logged in - show message -->
            <div class="not-logged-message">
                <p>Necessites iniciar sessió per veure els teus amics i xatejar amb ells.</p>
                <div class="auth-buttons-sidebar">
                    <a href="Vista/Auth_Vista.php" class="btn login-btn">Iniciar sessió</a>
                    <a href="Vista/Auth_Vista.php?registre=true" class="btn register-btn">Registrar-se</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Contenido principal -->
    <div class="main-content" id="main-content">
        <a href="./" class="back-button">
            <i class="fas fa-arrow-left"></i> Tornar a l'inici
        </a>
        <h1>Gestor d'Equips Pokémon</h1>

        <!-- Sección para visualizar equipos existentes -->
        <div class="section equipos-existents">
            <h2>Els meus equips</h2>
            
            <div class="equipos-container" id="equipos-container">
                <?php if (empty($equipos)): ?>
                    <div class="no-equips-message">
                        <p>Encara no tens cap equip Pokémon!</p>
                        <p>Utilitza el creador d'equips a continuació per crear el teu primer equip.</p>
                        <img src="Vista/assets/img/Poké_Ball_icon.png" alt="Pokeball" style="width: 50px; margin-top: 10px;">
                    </div>
                <?php else: ?>
                    <!-- Aquí se mostrarán los equipos -->
                    <?php foreach ($equipos as $equip): ?>
                        <div class="equip-card" data-equip-id="<?php echo $equip['id_equip']; ?>">
                            <div class="equip-header">
                                <h3 class="equip-title"><?php echo htmlspecialchars($equip['nom_equip']); ?></h3>
                                <div class="equip-actions">
                                    <button class="equip-action-btn edit-equip-btn" title="Editar nom">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="equip-action-btn export-equip-btn" title="Exportar equip">
                                        <i class="fas fa-file-export"></i>
                                    </button>
                                    <button class="equip-action-btn delete-equip-btn" title="Eliminar equip">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="equip-pokemon-list">
                                <?php
                                // Crear 6 slots para pokémon
                                for ($i = 0; $i < 6; $i++) {
                                    $pokemonEnSlot = null;
                                    foreach ($equip['pokemons'] as $pokemon) {
                                        if (isset($pokemon['posicio']) && $pokemon['posicio'] == $i) {
                                            $pokemonEnSlot = $pokemon;
                                            break;
                                        }
                                    }
                                    
                                    $filledClass = $pokemonEnSlot ? 'filled' : '';
                                    ?>
                                    <div class="pokemon-slot <?php echo $filledClass; ?>" 
                                         data-slot="<?php echo $i + 1; ?>" 
                                         data-equip-id="<?php echo $equip['id_equip']; ?>"
                                         <?php if ($pokemonEnSlot): ?>
                                         data-pokemon-id="<?php echo $pokemonEnSlot['pokeapi_id']; ?>"
                                         <?php endif; ?>>
                                        <?php if ($pokemonEnSlot): ?>
                                            <img src="<?php echo htmlspecialchars($pokemonEnSlot['sprite']); ?>" 
                                                 alt="<?php echo htmlspecialchars($pokemonEnSlot['nombre']); ?>"
                                                 onerror="this.src='Vista/assets/img/Poké_Ball_icon.png'">
                                            <span class="pokemon-name">
                                                <?php 
                                                    // Mostrar solo un nombre, con preferencia para el malnombre si existe
                                                    if (!empty($pokemonEnSlot['malnom'])) {
                                                        echo htmlspecialchars($pokemonEnSlot['malnom']);
                                                    } else {
                                                        echo htmlspecialchars($pokemonEnSlot['nombre']);
                                                    }
                                                ?>
                                            </span>
                                        <?php else: ?>
                                            <i class="fas fa-plus"></i>
                                            <span class="pokemon-name">Afegir Pokémon</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Sección para crear nuevo equipo -->
        <div class="section creador-equipo">
            <h2>Crear nou equip</h2>
            
            <div class="creador-container">
                <!-- Formulario para el nombre del equipo -->
                <div class="form-group">
                    <label for="nombre-equipo">Nom de l'equip:</label>
                    <input type="text" id="nombre-equipo" placeholder="Introdueix un nom per a l'equip">
                </div>
                
                <!-- Buscador de Pokémon -->
                <div class="buscador-pokemon">
                    <h3>Cercador de Pokémon</h3>
                    <div class="search-container">
                        <input type="text" id="pokemon-search" placeholder="Cerca Pokémon per nom...">
                        <button id="btn-buscar-pokemon" class="btn">Cercar</button>
                    </div>
                    
                    <!-- Resultados de búsqueda -->
                    <div class="pokemon-search-results" id="pokemon-search-results">
                        <!-- Aquí se mostrarán los resultados de búsqueda -->
                    </div>
                </div>
                
                <!-- Ranuras del equipo -->
                <div class="equipo-slots">
                    <h3>Equip actual</h3>
                    
                    <!-- Botón para limpiar todos los Pokémon del equipo -->
                    <div class="actions-team">
                        <button id="btn-limpiar-pokemon-equipo" class="btn secondary-btn">
                            <i class="fas fa-trash-alt"></i> Netejar Pokémons
                        </button>
                    </div>
                    
                    <div class="equipo-pokemon-grid" id="equipo-pokemon-grid">
                        <?php for ($i = 1; $i <= 6; $i++): ?>
                            <div class="equipo-slot empty" data-slot="<?php echo $i; ?>">
                                <div class="slot-content">
                                    <img src="Vista/assets/img/Poké_Ball_icon.png" alt="Ranura buida">
                                    <span>Ranura <?php echo $i; ?></span>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <!-- Movimientos del Pokémon seleccionado -->
                <div class="movimientos-pokemon">
                    <h3>Moviments del Pokémon</h3>
                    <div id="pokemon-seleccionado-info">
                        <p>Selecciona un Pokémon de l'equip per veure i configurar els seus moviments</p>
                    </div>
                    
                    <!-- Buscador de movimientos -->
                    <div class="movimientos-search">
                        <input type="text" id="movimiento-search" placeholder="Cerca moviments per nom...">
                        <button id="btn-buscar-movimiento" class="btn">Cercar</button>
                    </div>
                    
                    <!-- Botones para acciones en movimientos -->
                    <div class="actions-moves">
                        <button id="btn-limpiar-movimientos" class="btn secondary-btn" disabled>
                            <i class="fas fa-trash-alt"></i> Netejar moviments
                        </button>
                        <button id="btn-deseleccionar-pokemon" class="btn secondary-btn">
                            <i class="fas fa-times"></i> Deseleccionar Pokémon
                        </button>
                    </div>
                    
                    <!-- Resultados de búsqueda de movimientos -->
                    <div class="movimientos-search-results" id="movimientos-search-results">
                        <!-- Aquí se mostrarán los resultados de búsqueda de movimientos -->
                    </div>
                    
                    <!-- Grid para mostrar los 4 movimientos -->
                    <div class="movimientos-grid" id="movimientos-grid">
                        <div class="movimiento-slot empty" data-move-slot="1" id="movimiento-slot-1">
                            <div class="slot-content">
                                <span class="add-move-text">+ Afegir moviment</span>
                            </div>
                        </div>
                        <div class="movimiento-slot empty" data-move-slot="2" id="movimiento-slot-2">
                            <div class="slot-content">
                                <span class="add-move-text">+ Afegir moviment</span>
                            </div>
                        </div>
                        <div class="movimiento-slot empty" data-move-slot="3" id="movimiento-slot-3">
                            <div class="slot-content">
                                <span class="add-move-text">+ Afegir moviment</span>
                            </div>
                        </div>
                        <div class="movimiento-slot empty" data-move-slot="4" id="movimiento-slot-4">
                            <div class="slot-content">
                                <span class="add-move-text">+ Afegir moviment</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Botones de acción -->
                <div class="acciones-equipo">
                    <button id="btn-importar-equipo" class="btn import-btn">
                        <i class="fas fa-file-import"></i> Importar
                    </button>
                    <button id="btn-guardar-equipo" class="btn primary-btn">Guardar Equip</button>
                    <button id="btn-limpiar-equipo" class="btn">Netejar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Chat Modal -->
    <div id="chat-modal" class="chat-modal">
        <div class="chat-container">
            <div class="chat-header">
                <div class="chat-user">
                    <div class="chat-user-avatar">
                        <img id="chat-user-avatar" src="Vista/assets/img/avatars/Youngster.png" alt="Avatar">
                    </div>
                    <h3 id="chat-username">Carregant...</h3>
                </div>
                <div class="close-chat" id="close-chat">&times;</div>
            </div>
            <div class="chat-messages" id="chat-messages">
                <div class="pokemon-loader">
                    <img src="Vista/assets/img/Poké_Ball_icon.png" alt="Loading...">
                    <p>Carregant missatges...</p>
                </div>
            </div>
            <div class="chat-input-area">
                <input type="text" class="chat-input" id="chat-input" placeholder="Escriu un missatge...">
                <div class="send-button" id="send-message">
                    <i class="fas fa-paper-plane"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Avatar Selector Modal -->
    <div id="avatar-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Selecciona un Avatar</h2>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <div class="avatar-grid">
                    <?php
                    // Get all avatars from the avatars directory
                    $avatarDir = 'Vista/assets/img/avatars/';
                    $avatarFiles = glob($avatarDir . '*.png');
                    
                    foreach ($avatarFiles as $avatar) {
                        $avatarName = basename($avatar);
                        $nameWithoutExt = pathinfo($avatarName, PATHINFO_FILENAME);
                        $isSelected = (isset($_SESSION['avatar']) && $_SESSION['avatar'] === $avatarName) ? 'selected' : '';
                        
                        echo '<div class="avatar-item ' . $isSelected . '" data-avatar="' . $avatarName . '">';
                        echo '<img src="' . $avatarDir . $avatarName . '" alt="' . $nameWithoutExt . '">';
                        echo '<span class="avatar-name">' . $nameWithoutExt . '</span>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Exportar Equipo -->
    <div id="export-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Exportar Equip</h2>
                <span class="close-modal" id="close-export-modal">&times;</span>
            </div>
            <div class="modal-body">
                <p>Aquí está la información de tu equipo. Puedes copiar todo el equipo o Pokémon individuales.</p>
                
                <div class="export-options">
                    <button id="copy-full-team" class="btn secondary-btn">
                        <i class="fas fa-copy"></i> Copiar Equip Complet
                    </button>
                </div>
                
                <div class="export-data-container">
                    <textarea id="export-data" readonly></textarea>
                </div>
                
                <div class="pokemon-export-list" id="pokemon-export-list">
                    <!-- Aquí se mostrarán los Pokémon para exportar individualmente -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Importar Equipo -->
    <div id="import-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Importar Equip</h2>
                <span class="close-modal" id="close-import-modal">&times;</span>
            </div>
            <div class="modal-body">
                <p>Pega los datos del equipo o Pokémon que deseas importar:</p>
                
                <div class="import-data-container">
                    <textarea id="import-data" placeholder="Pega aquí los datos a importar..."></textarea>
                </div>
                
                <div class="import-options">
                    <button id="btn-import-data" class="btn primary-btn">
                        <i class="fas fa-file-import"></i> Importar Dades
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Script para gestionar equipos -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="Controlador/js/equip_gestor.js"></script>
    
    <!-- Script para controlar el sidebar de amigos -->
    <script>
        // Friends sidebar toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const friendsToggle = document.getElementById('friends-toggle');
            const friendsSidebar = document.getElementById('friends-sidebar');
            const mainContent = document.getElementById('main-content');
            
            // Check if elements exist
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
            
            // Function to handle friendship actions (accept/reject)
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
                    return response.json();
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
                                requestsSection.innerHTML += '<p class="no-requests-message">Cap sol·licitud pendent</p>';
                            }
                        }
                        
                        // If the request was accepted, reload the page to show the new friend
                        if (accio === 'acceptar') {
                            alert('Amistat acceptada correctament! La pàgina es recarregarà per mostrar la teva nova amistat.');
                            window.location.reload();
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
            
            // Chat functionality
            let currentChatFriendId = null;
            let lastMessageTime = null;
            let chatPollingInterval = null;
            const POLLING_INTERVAL = 5000; // Intervalo de actualización en ms

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
                const usuariId = <?php echo isset($_SESSION['usuari_id']) ? $_SESSION['usuari_id'] : 'null'; ?>;
                
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

            // Friend search functionality
            const searchInput = document.getElementById('friend-search');
            const searchButton = document.getElementById('search-friend-btn');
            const resultsContainer = document.getElementById('search-results');
            const resultsList = document.getElementById('search-results-list');
            
            // Debug to check if elements are found
            console.log("Search input element:", searchInput);
            console.log("Search button element:", searchButton);
            console.log("Results container element:", resultsContainer);
            console.log("Results list element:", resultsList);
            
            // Check if search elements exist before adding event listeners
            if (searchInput && searchButton) {
                searchButton.addEventListener('click', function() {
                    console.log("Search button clicked");
                    // Create results list element if it doesn't exist
                    if (!document.getElementById('search-results-list')) {
                        console.log("Creating missing search-results-list element");
                        const newResultsList = document.createElement('div');
                        newResultsList.id = 'search-results-list';
                        resultsContainer.appendChild(newResultsList);
                    }
                    searchFriends();
                });
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        console.log("Enter key pressed in search input");
                        // Create results list element if it doesn't exist
                        if (!document.getElementById('search-results-list')) {
                            console.log("Creating missing search-results-list element");
                            const newResultsList = document.createElement('div');
                            newResultsList.id = 'search-results-list';
                            resultsContainer.appendChild(newResultsList);
                        }
                        searchFriends();
                    }
                });
            } else {
                console.error('Search input or button not found');
            }
            
            // Friend search functionality with better error handling
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
                    console.log("Creating search-results-list element on the fly");
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
                
                fetch(`Controlador/search_users.php?term=${encodeURIComponent(searchTerm)}&_=${cacheBuster}`)
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
                        } else {
                            console.error('Cannot display error, resultsList element not found');
                        }
                    });
            }

            // Send friend request - With improved error handling and UI refresh
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

            // Functionality to delete friends
            document.querySelectorAll('.delete-friend-btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.stopPropagation(); // Prevent triggering the chat modal
                    const friendItem = this.closest('.friend-item');
                    const friendId = friendItem.dataset.friendId;
                    const friendName = friendItem.querySelector('.friend-name').textContent;
                    
                    if (!friendId) {
                        console.error('No se pudo encontrar el ID del amigo');
                        return;
                    }
                    
                    if (confirm(`¿Estás seguro de que quieres eliminar a ${friendName} de tu lista de amigos?`)) {
                        eliminarAmigo(friendId, friendItem);
                    }
                });
            });
            
            // Function to delete a friend
            function eliminarAmigo(amicId, friendElement) {
                const formData = new FormData();
                formData.append('amic_id', amicId);
                
                fetch('Controlador/eliminar_amigo.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error de red');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Remove friend from UI with animation
                        friendElement.style.opacity = '0';
                        friendElement.style.height = '0';
                        friendElement.style.overflow = 'hidden';
                        setTimeout(() => {
                            friendElement.remove();
                            
                            // Check if that was the last friend
                            const remainingFriends = document.querySelectorAll('.friend-item');
                            if (remainingFriends.length === 0) {
                                const friendsList = document.querySelector('.friends-list');
                                if (friendsList) {
                                    // Replace friends list with no-friends message
                                    const noFriendsDiv = document.createElement('div');
                                    noFriendsDiv.className = 'no-friends-message';
                                    noFriendsDiv.innerHTML = `
                                        <span class="no-friends-pokemon">┗(°O°)┛</span>
                                        <p>No hi ha entrenadors a la vista!</p>
                                        <p>Surt a explorar i desafia nous entrenadors per augmentar la teva xarxa de batalles Pokémon!</p>
                                    `;
                                    
                                    // Replace the ul with our div
                                    friendsList.parentNode.replaceChild(noFriendsDiv, friendsList);
                                }
                            }
                            
                            alert('Amigo eliminado correctamente');
                        }, 300);
                    } else {
                        alert('Error: ' + (data.message || 'No se ha podido eliminar al amigo'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al eliminar amigo: ' + error.message);
                });
            }
        });
    </script>
</body>
</html>