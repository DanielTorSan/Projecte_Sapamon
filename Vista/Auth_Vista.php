<?php
// Asegurar que se cargan todos los archivos necesarios en el orden correcto
require_once __DIR__ . "/../Model/configuracio.php"; // Cargar configuración primero (define $connexio)
require_once __DIR__ . "/../Model/UsuariModel.php"; // Cargar explícitamente el modelo de usuario
require_once __DIR__ . "/../Controlador/Auth/AuthControlador.php"; // Ahora cargar el controlador

// Iniciar sesión si no está ya iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Crear el controlador
$authControlador = new AuthControlador($connexio);
$datos = $authControlador->prepararDatosVista();

// Extraer variables para su uso en la vista
extract($datos);

// Determinar qué tab debe estar activo inicialmente
$activeTab = $authControlador->getActiveTab();
?>

<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autenticació - Sapamon</title>
    <!-- Arreglar las rutas de los recursos para que sean relativas al servidor, no al archivo -->
    <link rel="stylesheet" href="/Vista/assets/css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" href="/Vista/assets/img/favicon/Poké_Ball_icon.png" type="image/png">
</head>
<body>
    <div class="container">
        <header>
            <!-- Logo con ruta corregida -->
            <div class="auth-logo-container">
                <img src="/Vista/assets/img/Sapamon.png" alt="Sapamon" class="auth-logo-img">
            </div>
        </header>
        
        <div class="tabs">
            <button class="tab-btn <?php echo $activeTab === 'login' ? 'active' : ''; ?>" data-tab="login">Iniciar Sessió</button>
            <button class="tab-btn <?php echo $activeTab === 'register' ? 'active' : ''; ?>" data-tab="register">Registrar-se</button>
        </div>

        <div class="tabs-container">
            <!-- Login Tab -->
            <div class="tab-content <?php echo $activeTab === 'login' ? 'active' : ''; ?>" id="login-tab">
                <?php if ($error_login): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error_login); ?></div>
                <?php endif; ?>
                
                <?php if ($exit_login): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($exit_login); ?></div>
                <?php endif; ?>
                
                <!-- Ensure the form action points to the correct controller -->
                <form action="/Controlador/Auth/AutenticacioControlador.php?accio=login" method="post">
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
                        <a href="../Controlador/Auth/AuthControlador.php?accio=googleAuth" class="google-btn">
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
            <div class="tab-content <?php echo $activeTab === 'register' ? 'active' : ''; ?>" id="register-tab">
                <?php if ($error_register): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error_register); ?></div>
                <?php endif; ?>
                
                <?php if ($exit_register): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($exit_register); ?></div>
                <?php endif; ?>
                
                <!-- Ensure the form action points to the correct controller -->
                <form action="/Controlador/Auth/AutenticacioControlador.php?accio=registre" method="post">
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
                        <a href="../Controlador/Auth/AuthControlador.php?accio=googleAuth" class="google-btn">
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

    <!-- Incluir el archivo JavaScript para la autenticación con ruta corregida -->
    <script src="/Vista/assets/js/auth.js"></script>
</body>
</html>
