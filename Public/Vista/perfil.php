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
    <link rel="stylesheet" href="assets/css/perfil.css">
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
                <a href="Avatar_selector.php" class="avatar-change-button" title="Cambiar avatar">
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
