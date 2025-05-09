<?php
// Iniciar sesión si no está ya iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar si hay mensajes de error o éxito
$error_login = isset($_SESSION['error_login']) ? $_SESSION['error_login'] : '';
$error_register = isset($_SESSION['error_register']) ? $_SESSION['error_register'] : '';
$exit_register = isset($_SESSION['exit_register']) ? $_SESSION['exit_register'] : '';
$exit_login = isset($_SESSION['exit_login']) ? $_SESSION['exit_login'] : '';

// Limpiar las variables de sesión después de usarlas
unset($_SESSION['error_login'], $_SESSION['error_register'], $_SESSION['exit_register'], $_SESSION['exit_login']);
?>

<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autenticació - Sapamon</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" href="assets/img/favicon/Poké_Ball_icon.png" type="image/png">
</head>
<body>
    <div class="container">
        <header>
            <!-- Logo - Ensure correct path -->
            <div class="auth-logo-container">
                <img src="assets/img/Sapamon.png" alt="Sapamon" class="auth-logo-img">
            </div>
        </header>
        
        <div class="tabs">
            <button class="tab-btn active" data-tab="login">Iniciar Sessió</button>
            <button class="tab-btn" data-tab="register">Registrar-se</button>
        </div>

        <div class="tabs-container">
            <!-- Login Tab -->
            <div class="tab-content active" id="login-tab">
                <?php if ($error_login): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error_login); ?></div>
                <?php endif; ?>
                
                <?php if ($exit_login): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($exit_login); ?></div>
                <?php endif; ?>
                
                <!-- Ensure the form action points to the correct controller -->
                <form action="../Controlador/UsuariControlador.php?accio=login" method="post">
                    <div class="form-group">
                        <label for="login-username">Nom d'Usuari</label>
                        <input type="text" id="login-username" name="nom_usuari" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="login-password">Contrasenya</label>
                        <input type="password" id="login-password" name="contrasenya" required>
                    </div>
                    
                    <div class="form-options">
                        <div class="remember-me">
                            <input type="checkbox" id="remember-me" name="remember-me">
                            <label for="remember-me">Recorda'm</label>
                        </div>
                        <div class="forgot-password">
                            <a href="../Vista/RecuperacioContrasenya_Vista.php">Has oblidat la contrasenya?</a>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <a href="../Controlador/GoogleAuthControlador.php" class="google-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48">
                                <path fill="#FFC107" d="M43.611,20.083H42V20H24v8h11.303c-1.649,4.657-6.08,8-11.303,8c-6.627,0-12-5.373-12-12
                                    s5.373-12,12-12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C12.955,4,4,12.955,4,24
                                    s8.955,20,20,20s20-8.955,20-20C44,22.659,43.862,21.35,43.611,20.083z"/>
                                <path fill="#FF3D00" d="M6.306,14.691l6.571,4.819C14.655,15.108,18.961,12,24,12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657
                                    C34.046,6.053,29.268,4,24,4C16.318,4,9.656,8.337,6.306,14.691z"/>
                                <path fill="#4CAF50" d="M24,44c5.166,0,9.86-1.977,13.409-5.192l-6.19-5.238C29.211,35.091,26.715,36,24,36
                                    c-5.202,0-9.619-3.317-11.283-7.946l-6.522,5.025C9.505,39.556,16.227,44,24,44z"/>
                                <path fill="#1976D2" d="M43.611,20.083H42V20H24v8h11.303c-0.792,2.237-2.231,4.166-4.087,5.571
                                    c0.001-0.001,0.002-0.001,0.003-0.002l6.19,5.238C36.971,39.205,44,34,44,24C44,22.659,43.862,21.35,43.611,20.083z"/>
                            </svg>
                            Continuar amb Google
                        </a>
                        
                        <div class="or-separator">o</div>
                        
                        <button type="submit" class="btn">Iniciar Sessió</button>
                    </div>
                </form>
                
                <div class="form-footer">
                    No tens un compte? <a href="#" data-switch-tab="register">Registra't ara!</a>
                </div>
            </div>
            
            <!-- Register Tab -->
            <div class="tab-content" id="register-tab">
                <?php if ($error_register): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error_register); ?></div>
                <?php endif; ?>
                
                <?php if ($exit_register): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($exit_register); ?></div>
                <?php endif; ?>
                
                <!-- Ensure the form action points to the correct controller -->
                <form action="../Controlador/UsuariControlador.php?accio=registre" method="post">
                    <div class="form-group">
                        <label for="register-username">Nom d'Usuari</label>
                        <input type="text" id="register-username" name="nom_usuari" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="register-email">Correu Electrònic</label>
                        <input type="email" id="register-email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="register-password">Contrasenya</label>
                        <input type="password" id="register-password" name="contrasenya" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="register-confirm-password">Confirmar Contrasenya</label>
                        <input type="password" id="register-confirm-password" name="confirmar_contrasenya" required>
                    </div>
                    
                    <div class="form-actions">
                        <a href="../Controlador/GoogleAuthControlador.php" class="google-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48">
                                <!-- SVG content -->
                            </svg>
                            Continuar amb Google
                        </a>
                        
                        <div class="or-separator">o</div>
                        
                        <button type="submit" class="btn">Registrar-se</button>
                    </div>
                </form>
                
                <div class="form-footer">
                    Ja tens un compte? <a href="#" data-switch-tab="login">Iniciar sessió!</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab switching functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabBtns = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');
            const tabSwitchLinks = document.querySelectorAll('[data-switch-tab]');
            
            // Tab button click handler
            tabBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const targetTab = this.dataset.tab;
                    
                    // Update active state for buttons
                    tabBtns.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Show target tab content
                    tabContents.forEach(content => {
                        if (content.id === targetTab + '-tab') {
                            content.classList.add('active');
                        } else {
                            content.classList.remove('active');
                            content.classList.remove('from-left');
                            content.classList.remove('to-left');
                        }
                    });
                });
            });
            
            // Tab switch links handler
            tabSwitchLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetTab = this.dataset.switchTab;
                    document.querySelector(`.tab-btn[data-tab="${targetTab}"]`).click();
                });
            });
            
            // Password matching check
            const passwordInput = document.getElementById('register-password');
            const confirmPasswordInput = document.getElementById('register-confirm-password');
            
            if (passwordInput && confirmPasswordInput) {
                const checkPasswordMatch = function() {
                    if (confirmPasswordInput.value === '') {
                        // Password field is empty, don't show any message
                    } else if (passwordInput.value === confirmPasswordInput.value) {
                        confirmPasswordInput.setCustomValidity('');
                    } else {
                        confirmPasswordInput.setCustomValidity('Les contrasenyes no coincideixen');
                    }
                };
                
                passwordInput.addEventListener('input', checkPasswordMatch);
                confirmPasswordInput.addEventListener('input', checkPasswordMatch);
            }
        });
    </script>
</body>
</html>
