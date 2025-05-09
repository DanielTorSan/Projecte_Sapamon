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

// Obtener los datos del usuario actual
require_once "../Model/configuracio.php";
require_once "../Model/AvatarModel.php";

$usuariId = $_SESSION['usuari_id'];
$stmt = $connexio->prepare("SELECT * FROM usuaris WHERE id_usuari = ?");
$stmt->bind_param("i", $usuariId);
$stmt->execute();
$result = $stmt->get_result();
$usuari = $result->fetch_assoc();

// Initialize the Avatar model
$avatarModel = new AvatarModel($connexio);

// Si no existe un avatar, establecer Youngster como default
if (empty($usuari['avatar'])) {
    $usuari['avatar'] = $avatarModel->getDefaultAvatar();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Sapamon</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .profile-container {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .avatar-container {
            margin-bottom: 20px;
            text-align: center;
            position: relative;
        }
        
        .avatar-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 5px solid #FFCB05;
            object-fit: contain;
            background-color: white;
        }
        
        .avatar-change-button {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background-color: #3466AF;
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .avatar-change-button:hover {
            transform: scale(1.1);
            background-color: #FFCB05;
            color: #3466AF;
        }
        
        .profile-info {
            width: 100%;
            max-width: 500px;
        }
        
        .profile-info h2 {
            color: #3466AF;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .info-group {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }
        
        .info-label {
            font-weight: bold;
            color: #3466AF;
        }
        
        .actions {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Mi Perfil</h1>
        
        <div class="profile-container">
            <div class="avatar-container">
                <img class="avatar-image" 
                     src="assets/img/Avatars/<?php echo htmlspecialchars($usuari['avatar']); ?>" 
                     alt="Avatar de usuario" 
                     onerror="this.src='assets/img/Avatars/Youngster.png'">
                <a href="avatar_selector.php" class="avatar-change-button" title="Cambiar avatar">
                    <i class="fas fa-camera"></i>
                </a>
            </div>
            
            <div class="profile-info">
                <h2><?php echo htmlspecialchars($usuari['nom_usuari']); ?></h2>
                
                <div class="info-group">
                    <div class="info-label">Correo:</div>
                    <div><?php echo htmlspecialchars($usuari['correu']); ?></div>
                </div>
                
                <div class="info-group">
                    <div class="info-label">Rol:</div>
                    <div><?php echo htmlspecialchars($usuari['rol']); ?></div>
                </div>
                
                <div class="info-group">
                    <div class="info-label">Fecha de registro:</div>
                    <div><?php echo htmlspecialchars($usuari['creat_el']); ?></div>
                </div>
                
                <?php if (!empty($usuari['ultima_connexio'])): ?>
                <div class="info-group">
                    <div class="info-label">Última conexión:</div>
                    <div><?php echo htmlspecialchars($usuari['ultima_connexio']); ?></div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="actions">
                <a href="../index.php" class="button">Volver al inicio</a>
            </div>
        </div>
    </div>
    
    <!-- Incluir FontAwesome para el icono de la cámara -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</body>
</html>
