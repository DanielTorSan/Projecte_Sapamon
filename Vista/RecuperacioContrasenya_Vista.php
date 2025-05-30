<?php
// Just start session to access session variables
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contrasenya - Sapamon</title>
    <link rel="stylesheet" href="../Vista/assets/css/styles.css">
    <link rel="stylesheet" href="../Vista/assets/css/recovery.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="form-header">
            <h1 class="sapamon-logo">Sapamon</h1>
            <h2 class="pokemon-title">Recuperar Contrasenya</h2>
        </div>
        
        <div class="form-container">
            <?php if (isset($_SESSION['error_recuperacio'])): ?>
                <div class="alert alert-error">
                    <?php 
                    echo $_SESSION['error_recuperacio']; 
                    unset($_SESSION['error_recuperacio']);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['exit_recuperacio'])): ?>
                <div class="alert alert-success">
                    <?php 
                    echo $_SESSION['exit_recuperacio']; 
                    unset($_SESSION['exit_recuperacio']);
                    ?>
                </div>
            <?php endif; ?>
            
            <form action="../Controlador/Auth/enviarRecuperacio.php" method="POST">
                <div class="form-group">
                    <label for="email">Correu electrònic:</label>
                    <input type="email" id="email" name="email" required placeholder="Introdueix el teu correu electrònic">
                </div>
                
                <button type="submit" class="btn">Enviar instruccions</button>
            </form>
            
            <div class="info-box">
                <p><strong>Nota:</strong> T'enviarem un correu electrònic amb instruccions per restablir la teva contrasenya.</p>
            </div>
            
            <div class="form-footer">
                <a href="../index.php">Tornar a l'inici</a>
            </div>
        </div>
    </div>
</body>
</html>
