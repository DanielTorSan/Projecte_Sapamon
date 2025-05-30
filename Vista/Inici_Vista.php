<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sapamon - Batalles</title>
    <link rel="stylesheet" href="Vista/assets/css/styles.css">
    <link rel="stylesheet" href="Vista/assets/css/home.css">
    <link rel="stylesheet" href="Vista/assets/css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" href="Vista/assets/img/favicon/Poké_Ball_icon.png" type="image/png">
</head>
<body <?php if(isset($_SESSION['usuari_id'])): ?>data-usuari-id="<?php echo $_SESSION['usuari_id']; ?>"<?php endif; ?>>
    <?php
    // Load controllers if user is logged in
    $amics = [];
    $hasAmics = false;
    $pendingRequests = [];
    $sollicitudsPendents = [];
    if (isset($_SESSION['usuari_id'])) {
        require_once "Controlador/Usuaris/Amics/AmicsControlador.php";
        require_once "Controlador/Usuaris/UsuariControlador.php";
        $amicsControlador = new AmicsControlador($connexio);
        $amics = $amicsControlador->getAmics($_SESSION['usuari_id']);
        $hasAmics = !empty($amics);
        $pendingRequests = $amicsControlador->getPendingRequests($_SESSION['usuari_id']);
        $sollicitudsPendents = $amicsControlador->getSollicitudsPendents($_SESSION['usuari_id']);
    }
    ?>
    
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
                        <!-- El botón del gestor de equips ha sido eliminado de aquí -->
                        
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
                        <a href="Controlador/Auth/logout.php" class="logout-btn" title="Tancar sessió">
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

    <!-- Contenido principal vacío por ahora -->
    <div class="main-content" id="main-content">
        <?php if (isset($_SESSION['usuari_id'])): ?>
            <?php 
            // Obtener el equipo principal si existe
            $usuariControlador = new UsuariControlador($connexio);
            $equipPrincipal = $usuariControlador->getEquipPrincipal($_SESSION['usuari_id']);
            ?>
            
            <!-- Sección para mostrar el equipo principal del usuario -->
            <div class="dashboard-section">
                <h2>El Meu Equip Principal</h2>
                <?php if ($equipPrincipal): ?>
                    <div class="equip-principal">
                        <h3><?php echo htmlspecialchars($equipPrincipal['nom_equip']); ?></h3>
                        <div class="pokemon-team">
                            <?php foreach ($equipPrincipal['pokemons'] as $pokemon): ?>
                                <div class="pokemon-card" title="<?php echo $pokemon['malnom'] ? htmlspecialchars($pokemon['malnom']) : htmlspecialchars($pokemon['nombre']); ?>">
                                    <img src="<?php echo htmlspecialchars($pokemon['sprite']); ?>" alt="<?php echo htmlspecialchars($pokemon['nombre']); ?>" 
                                        onerror="this.src='Vista/assets/img/pokemon-placeholder.png'">
                                    <div class="pokemon-info">
                                        <p><?php echo $pokemon['malnom'] ? htmlspecialchars($pokemon['malnom']) : htmlspecialchars($pokemon['nombre']); ?></p>
                                        <span class="pokemon-level">Nivell <?php echo $pokemon['nivell']; ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button id="btn-cambiar-equip" class="pokemon-button">
                            <i class="fas fa-exchange-alt"></i> Canviar Equip Principal
                        </button>
                    </div>
                <?php else: ?>
                    <p>Encara no tens cap equip principal seleccionat.</p>
                    <button id="btn-cambiar-equip" class="pokemon-button">
                        <i class="fas fa-plus"></i> Seleccionar Equip Principal
                    </button>
                <?php endif; ?>
            </div>
            
            <!-- Botón para gestor de equipos ahora en el contenido principal -->
            <div class="dashboard-section">
                <h2>Gestiona els teus Pokémon</h2>
                <p>Com a entrenador Pokémon, pots crear i gestionar els teus equips amb els teus Pokémon preferits.</p>
                <div class="dashboard-actions">
                    <button id="btn-equips" class="pokemon-button">
                        <img src="Vista/assets/img/Poké_Ball_icon.png" alt="Pokeball" class="button-icon">
                        Gestor d'Equips Pokémon
                    </button>
                </div>
            </div>
            
            <!-- Nueva sección para desafíos Pokémon -->
            <div class="dashboard-section">
                <h2>Batalla Pokémon</h2>
                <p>Desafia a altres entrenadors a una batalla Pokémon i demostra qui és el millor!</p>
                <div class="dashboard-actions">
                    <button id="btn-desafiar" class="pokemon-button">
                        Desafiar a un Entrenador
                    </button>
                    <button id="btn-desafios" class="pokemon-button">
                        Desafiaments Pendents <span id="contador-desafios" class="contador-badge" style="display:none;">0</span>
                    </button>
                </div>
            </div>
        <?php else: ?>
            <!-- Mensaje para usuarios no autenticados -->
            <div class="dashboard-section">
                <h2>Benvingut a Sapamon!</h2>
                <p>Inicia sessió per accedir a totes les funcionalitats d'entrenador Pokémon.</p>
            </div>
        <?php endif; ?>
    </div>

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
                                <button class="btn friend-action-btn delete-btn" title="Eliminar amic">
                                    <i class="fas fa-user-slash"></i>
                                </button>
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

    <!-- Modal de Selección de Equipo Principal -->
    <div id="equip-principal-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Selecciona el teu Equip Principal</h2>
                <span class="close-modal" id="close-equip-modal">&times;</span>
            </div>
            <div class="modal-body">
                <div class="search-container">
                    <input type="text" id="equip-search" placeholder="Buscar per nom d'equip...">
                </div>
                
                <div class="equips-container" id="equips-list">
                    <?php if (isset($_SESSION['usuari_id'])): ?>
                        <?php 
                        // Obtener todos los equipos guardados del usuario
                        $equipsGuardats = $usuariControlador->getEquipsGuardats($_SESSION['usuari_id']);
                        
                        if (!empty($equipsGuardats)): ?>
                            <?php foreach ($equipsGuardats as $equip): ?>
                                <div class="equip-item" data-equip-id="<?php echo $equip['id_equip']; ?>" data-equip-nom="<?php echo htmlspecialchars($equip['nom_equip']); ?>">
                                    <div class="equip-info">
                                        <h3><?php echo htmlspecialchars($equip['nom_equip']); ?></h3>
                                        <div class="equip-pokemon-list">
                                            <?php foreach ($equip['pokemons'] as $pokemon): ?>
                                                <div class="equip-pokemon">
                                                    <img src="<?php echo htmlspecialchars($pokemon['sprite']); ?>" 
                                                        alt="<?php echo htmlspecialchars($pokemon['nombre']); ?>"
                                                        title="<?php echo $pokemon['malnom'] ? htmlspecialchars($pokemon['malnom']) : htmlspecialchars($pokemon['nombre']); ?>"
                                                        onerror="this.src='Vista/assets/img/pokemon-placeholder.png'">
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <button class="btn select-equip-btn" data-equip-id="<?php echo $equip['id_equip']; ?>">
                                        Seleccionar
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-equips-message">
                                <p>No tens cap equip guardat.</p>
                                <p>Ves al Gestor d'Equips Pokémon per crear i guardar els teus equips!</p>
                                <button id="btn-gestor-equips" class="pokemon-button">
                                    <img src="Vista/assets/img/Poké_Ball_icon.png" alt="Pokeball" class="button-icon">
                                    Gestor d'Equips Pokémon
                                </button>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal per desafiar a un altre entrenador -->
    <div id="desafiar-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Desafiar a un Entrenador</h2>
                <span class="close-modal" id="close-desafiar-modal">&times;</span>
            </div>
            <div class="modal-body">
                <!-- Secció per seleccionar l'entrenador -->
                <div class="desafio-section">
                    <h3>Seleccionar Entrenador</h3>
                    <div class="search-container">
                        <input type="text" id="entrenador-search" placeholder="Buscar entrenador...">
                    </div>
                    
                    <div class="entrenadors-list" id="entrenadors-list">
                        <?php if (isset($_SESSION['usuari_id']) && $hasAmics): ?>
                            <?php foreach ($amics as $amic): ?>
                                <div class="entrenador-item" data-entrenador-id="<?php echo $amic['id_usuari']; ?>">
                                    <div class="entrenador-avatar">
                                        <img src="Vista/assets/img/avatars/<?php echo htmlspecialchars($amic['avatar']); ?>" 
                                             alt="Avatar" 
                                             onerror="this.src='Vista/assets/img/avatars/Youngster.png'">
                                    </div>
                                    <div class="entrenador-info">
                                        <h4><?php echo htmlspecialchars($amic['nom_usuari']); ?></h4>
                                        <p class="friend-status <?php echo strtolower($amicsControlador->getEstatLabel($amic['estat'], $amic['ultima_connexio'])); ?>">
                                            <?php echo $amicsControlador->getEstatLabel($amic['estat'], $amic['ultima_connexio']); ?>
                                        </p>
                                    </div>
                                    <button class="btn select-entrenador-btn" data-entrenador-id="<?php echo $amic['id_usuari']; ?>" data-entrenador-nom="<?php echo htmlspecialchars($amic['nom_usuari']); ?>">
                                        Seleccionar
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-amics-message">
                                <p>No tens amics per desafiar.</p>
                                <p>Afegeix entrenadors a la teva llista d'amics per poder desafiar-los!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Secció per seleccionar l'equip -->
                <div class="desafio-section">
                    <h3>Seleccionar Equip</h3>
                    
                    <div class="equips-selector" id="desafio-equips-list">
                        <?php if (isset($_SESSION['usuari_id'])): ?>
                            <?php 
                            // Mostrar primer l'equip principal si existeix
                            if ($equipPrincipal): ?>
                                <div class="equip-item selected" data-equip-id="<?php echo $equipPrincipal['id_equip']; ?>" data-equip-nom="<?php echo htmlspecialchars($equipPrincipal['nom_equip']); ?>">
                                    <div class="equip-info">
                                        <h3><?php echo htmlspecialchars($equipPrincipal['nom_equip']); ?> (Principal)</h3>
                                        <div class="equip-pokemon-list">
                                            <?php foreach ($equipPrincipal['pokemons'] as $pokemon): ?>
                                                <div class="equip-pokemon">
                                                    <img src="<?php echo htmlspecialchars($pokemon['sprite']); ?>" 
                                                        alt="<?php echo htmlspecialchars($pokemon['nombre']); ?>"
                                                        title="<?php echo $pokemon['malnom'] ? htmlspecialchars($pokemon['malnom']) : htmlspecialchars($pokemon['nombre']); ?>"
                                                        onerror="this.src='Vista/assets/img/pokemon-placeholder.png'">
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <button class="btn select-equip-btn active" data-equip-id="<?php echo $equipPrincipal['id_equip']; ?>">
                                        Seleccionat
                                    </button>
                                </div>
                            <?php endif; ?>
                            
                            <?php 
                            // Mostrar altres equips guardats que no siguin el principal
                            if (!empty($equipsGuardats)): ?>
                                <?php foreach ($equipsGuardats as $equip): ?>
                                    <?php if ($equipPrincipal && $equip['id_equip'] == $equipPrincipal['id_equip']) continue; // Saltar l'equip principal ?>
                                    <div class="equip-item" data-equip-id="<?php echo $equip['id_equip']; ?>" data-equip-nom="<?php echo htmlspecialchars($equip['nom_equip']); ?>">
                                        <div class="equip-info">
                                            <h3><?php echo htmlspecialchars($equip['nom_equip']); ?></h3>
                                            <div class="equip-pokemon-list">
                                                <?php foreach ($equip['pokemons'] as $pokemon): ?>
                                                    <div class="equip-pokemon">
                                                        <img src="<?php echo htmlspecialchars($pokemon['sprite']); ?>" 
                                                            alt="<?php echo htmlspecialchars($pokemon['nombre']); ?>"
                                                            title="<?php echo $pokemon['malnom'] ? htmlspecialchars($pokemon['malnom']) : htmlspecialchars($pokemon['nombre']); ?>"
                                                            onerror="this.src='Vista/assets/img/pokemon-placeholder.png'">
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <button class="btn select-equip-btn" data-equip-id="<?php echo $equip['id_equip']; ?>">
                                            Seleccionar
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-equips-message">
                                    <p>No tens cap equip guardat.</p>
                                    <p>Ves al Gestor d'Equips Pokémon per crear i guardar els teus equips!</p>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Botó per enviar el desafiament -->
                <div class="desafio-actions">
                    <button id="btn-enviar-desafio" class="pokemon-button" disabled>
                        Enviar Desafiament
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de desafiaments pendents -->
    <div id="desafios-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Desafiaments Pendents</h2>
                <span class="close-modal" id="close-desafios-modal">&times;</span>
            </div>
            <div class="modal-body">
                <div class="desafios-tabs">
                    <button class="tab-btn active" data-tab="recibidos">Rebuts</button>
                    <button class="tab-btn" data-tab="enviados">Enviats</button>
                </div>
                
                <div class="desafios-content">
                    <!-- Tab de desafiaments rebuts -->
                    <div class="tab-content active" id="tab-recibidos">
                        <div class="desafios-list" id="desafios-recibidos-list">
                            <div class="loading-spinner">
                                <img src="Vista/assets/img/Poké_Ball_icon.png" alt="Loading..." class="spinning-pokeball">
                                <p>Carregant desafiaments rebuts...</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab de desafiaments enviats -->
                    <div class="tab-content" id="tab-enviados">
                        <div class="desafios-list" id="desafios-enviats-list">
                            <div class="loading-spinner">
                                <img src="Vista/assets/img/Poké_Ball_icon.png" alt="Loading..." class="spinning-pokeball">
                                <p>Carregant desafiaments enviats...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de selecció d'equip per acceptar desafiament -->
    <div id="aceptar-desafio-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Seleccionar Equip per al Desafiament</h2>
                <span class="close-modal" id="close-aceptar-desafio-modal">&times;</span>
            </div>
            <div class="modal-body">
                <div class="desafio-info" id="desafio-info">
                    <!-- Aquí es mostrarà la informació del desafiament -->
                </div>
                
                <h3>Selecciona el teu equip:</h3>
                
                <div class="equips-selector" id="aceptar-equips-list">
                    <?php if (isset($_SESSION['usuari_id'])): ?>
                        <?php 
                        // Mostrar primer l'equip principal si existeix
                        if ($equipPrincipal): ?>
                            <div class="equip-item selected" data-equip-id="<?php echo $equipPrincipal['id_equip']; ?>" data-equip-nom="<?php echo htmlspecialchars($equipPrincipal['nom_equip']); ?>">
                                <div class="equip-info">
                                    <h3><?php echo htmlspecialchars($equipPrincipal['nom_equip']); ?> (Principal)</h3>
                                    <div class="equip-pokemon-list">
                                        <?php foreach ($equipPrincipal['pokemons'] as $pokemon): ?>
                                            <div class="equip-pokemon">
                                                <img src="<?php echo htmlspecialchars($pokemon['sprite']); ?>" 
                                                    alt="<?php echo htmlspecialchars($pokemon['nombre']); ?>"
                                                    title="<?php echo $pokemon['malnom'] ? htmlspecialchars($pokemon['malnom']) : htmlspecialchars($pokemon['nombre']); ?>"
                                                    onerror="this.src='Vista/assets/img/pokemon-placeholder.png'">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <button class="btn select-equip-btn active" data-equip-id="<?php echo $equipPrincipal['id_equip']; ?>">
                                    Seleccionat
                                </button>
                            </div>
                        <?php endif; ?>
                        
                        <?php 
                        // Mostrar altres equips guardats que no siguin el principal
                        if (!empty($equipsGuardats)): ?>
                            <?php foreach ($equipsGuardats as $equip): ?>
                                <?php if ($equipPrincipal && $equip['id_equip'] == $equipPrincipal['id_equip']) continue; // Saltar l'equip principal ?>
                                <div class="equip-item" data-equip-id="<?php echo $equip['id_equip']; ?>" data-equip-nom="<?php echo htmlspecialchars($equip['nom_equip']); ?>">
                                    <div class="equip-info">
                                        <h3><?php echo htmlspecialchars($equip['nom_equip']); ?></h3>
                                        <div class="equip-pokemon-list">
                                            <?php foreach ($equip['pokemons'] as $pokemon): ?>
                                                <div class="equip-pokemon">
                                                    <img src="<?php echo htmlspecialchars($pokemon['sprite']); ?>" 
                                                        alt="<?php echo htmlspecialchars($pokemon['nombre']); ?>"
                                                        title="<?php echo $pokemon['malnom'] ? htmlspecialchars($pokemon['malnom']) : htmlspecialchars($pokemon['nombre']); ?>"
                                                        onerror="this.src='Vista/assets/img/pokemon-placeholder.png'">
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <button class="btn select-equip-btn" data-equip-id="<?php echo $equip['id_equip']; ?>">
                                        Seleccionar
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-equips-message">
                                <p>No tens cap equip guardat.</p>
                                <p>Ves al Gestor d'Equips Pokémon per crear i guardar els teus equips!</p>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                
                <div class="desafio-actions">
                    <input type="hidden" id="desafio-id" value="">
                    <button id="btn-aceptar-desafio" class="pokemon-button">
                        Acceptar Desafiament
                    </button>
                    <button id="btn-rechazar-desafio" class="pokemon-button secondary">
                        Rebutjar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Incluir los archivos JavaScript -->
    <script src="Controlador/js/inici.js"></script>
    <script src="Controlador/js/sidebar.js"></script>
    <script src="Controlador/js/chat.js"></script>
    <script src="Controlador/js/batalla.js"></script>
</body>
</html>
