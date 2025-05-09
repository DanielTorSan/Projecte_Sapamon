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

// Obtener los avatares de la base de datos
require_once "../Model/configuracio.php";
require_once "../Model/AvatarModel.php";

$avatarModel = new AvatarModel($connexio);
$avatars = $avatarModel->getAllAvatars();

// Manejar la selección de avatar
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['selectedAvatar'])) {
    $selectedAvatar = $_POST['selectedAvatar'];
    $usuariId = $_SESSION['usuari_id'];
    
    // Actualizar el avatar en la base de datos
    $stmt = $connexio->prepare("UPDATE usuaris SET avatar = ? WHERE id_usuari = ?");
    $stmt->bind_param("si", $selectedAvatar, $usuariId);
    
    if ($stmt->execute()) {
        $_SESSION['avatar'] = $selectedAvatar;
        $successMessage = "Avatar actualizado correctamente";
    } else {
        $errorMessage = "Error al actualizar el avatar";
    }
}

// Obtener el avatar actual del usuario
$usuariId = $_SESSION['usuari_id'];
$stmt = $connexio->prepare("SELECT avatar FROM usuaris WHERE id_usuari = ?");
$stmt->bind_param("i", $usuariId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$currentAvatar = $row['avatar'] ?? $avatarModel->getDefaultAvatar();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seleccionar Avatar - Sapamon</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .avatar-gallery {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-top: 20px;
        }
        
        .avatar-option {
            cursor: pointer;
            border: 3px solid transparent;
            border-radius: 10px;
            padding: 10px;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .avatar-option:hover {
            transform: scale(1.05);
            background-color: rgba(108, 145, 191, 0.1);
        }
        
        .avatar-option.selected {
            border-color: #FFCB05;
            background-color: rgba(52, 102, 175, 0.1);
        }
        
        .avatar-option img {
            width: 80px;
            height: 80px;
            object-fit: contain;
        }
        
        .avatar-name {
            margin-top: 5px;
            font-size: 12px;
            color: var(--color-text);
        }
        
        .current-avatar {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .current-avatar img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 4px solid #FFCB05;
            object-fit: contain;
            background-color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Selecciona tu Avatar</h1>
        
        <?php if (isset($successMessage)): ?>
            <div class="alert alert-success">
                <?php echo $successMessage; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($errorMessage)): ?>
            <div class="alert alert-error">
                <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>
        
        <div class="current-avatar">
            <h3>Avatar actual</h3>
            <img src="assets/img/avatars/<?php echo htmlspecialchars($currentAvatar); ?>" 
                 alt="Avatar actual"
                 onerror="this.src='assets/img/avatars/Youngster.png'">
            <p><?php echo htmlspecialchars($currentAvatar); ?></p>
        </div>
        
        <form method="post" action="">
            <div class="avatar-gallery">
                <?php 
                foreach ($avatars as $avatar) {
                    $avatarName = $avatar['file_name'];
                    $displayName = $avatar['name'];
                    $isSelected = ($avatarName === $currentAvatar) ? 'selected' : '';
                ?>
                    <div class="avatar-option <?php echo $isSelected; ?>" data-avatar="<?php echo $avatarName; ?>">
                        <img src="assets/img/avatars/<?php echo htmlspecialchars($avatarName); ?>" 
                             alt="<?= htmlspecialchars($avatarBasename) ?>"
                             onerror="this.src='assets/img/avatars/Youngster.png'">
                        <div class="avatar-name"><?php echo htmlspecialchars($displayName); ?></div>
                    </div>
                <?php } ?>
            </div>
            
            <input type="hidden" name="selectedAvatar" id="selectedAvatar" value="<?php echo htmlspecialchars($currentAvatar); ?>">
            
            <div class="form-actions" style="margin-top: 20px; flex-direction:row; justify-content:center;">
                <button type="submit" class="btn">Guardar selección</button>
                <a href="perfil.php" class="button" style="margin-left: 10px;">Volver al perfil</a>
            </div>
        </form>
    </div>
    
    <script>
        // JavaScript para manejar la selección de avatares
        document.addEventListener('DOMContentLoaded', function() {
            const avatarOptions = document.querySelectorAll('.avatar-option');
            const selectedAvatarInput = document.getElementById('selectedAvatar');
            
            avatarOptions.forEach(option => {
                option.addEventListener('click', function() {
                    // Quitar la clase 'selected' de todas las opciones
                    avatarOptions.forEach(opt => opt.classList.remove('selected'));
                    
                    // Añadir la clase 'selected' a la opción clickeada
                    this.classList.add('selected');
                    
                    // Actualizar el valor del input oculto con el nombre del avatar seleccionado
                    const avatarName = this.getAttribute('data-avatar');
                    selectedAvatarInput.value = avatarName;
                });
            });
        });
    </script>
</body>
</html>
