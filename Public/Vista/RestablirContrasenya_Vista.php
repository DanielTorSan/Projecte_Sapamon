<?php
// Just start session to access session variables
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get the token from the URL
$token = isset($_GET['token']) ? $_GET['token'] : '';
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablir Contrasenya - Sapamon</title>
    <link rel="stylesheet" href="../Vista/assets/css/styles.css">
    <link rel="stylesheet" href="../Vista/assets/css/recovery.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="form-header">
            <h1 class="sapamon-logo">Sapamon</h1>
            <h2 class="pokemon-title">Restablir Contrasenya</h2>
        </div>
        
        <div class="form-container">
            <?php if (isset($_SESSION['error_restablir'])): ?>
                <div class="alert alert-error">
                    <?php 
                    echo $_SESSION['error_restablir']; 
                    unset($_SESSION['error_restablir']);
                    ?>
                </div>
            <?php endif; ?>
            
            <form action="../Controlador/processarRestabliment.php" method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <div class="form-group">
                    <label for="nova_contrasenya">Nova Contrasenya:</label>
                    <input type="password" id="nova_contrasenya" name="nova_contrasenya" required minlength="8">
                </div>
                
                <div class="form-group">
                    <label for="confirmar_contrasenya">Confirmar Contrasenya:</label>
                    <input type="password" id="confirmar_contrasenya" name="confirmar_contrasenya" required minlength="8">
                    <div id="password-match" class="password-match-indicator"></div>
                </div>
                
                <div class="password-requirements">
                    <p><strong>La contrasenya ha de complir els següents requisits:</strong></p>
                    <ul>
                        <li>Mínim 8 caràcters</li>
                        <li>Almenys una lletra majúscula</li>
                        <li>Almenys una lletra minúscula</li>
                        <li>Almenys un número</li>
                    </ul>
                </div>
                
                <button type="submit" class="btn">Restablir Contrasenya</button>
            </form>
            
            <div class="form-footer">
                <a href="../index.php">Tornar a l'inici</a>
            </div>
        </div>
    </div>
</body>
</html>
