// JavaScript for password recovery functionality
document.addEventListener('DOMContentLoaded', function() {
    const password = document.getElementById('nova_contrasenya');
    const confirmPassword = document.getElementById('confirmar_contrasenya');
    const indicator = document.getElementById('password-match');
    
    function checkPasswords() {
        if (!password || !confirmPassword || !indicator) return;
        
        if (confirmPassword.value === '') {
            indicator.textContent = '';
            indicator.className = '';
        } else if (password.value === confirmPassword.value) {
            indicator.textContent = 'Les contrasenyes coincideixen';
            indicator.className = 'match';
        } else {
            indicator.textContent = 'Les contrasenyes no coincideixen';
            indicator.className = 'no-match';
        }
    }
    
    if (password && confirmPassword) {
        password.addEventListener('keyup', checkPasswords);
        confirmPassword.addEventListener('keyup', checkPasswords);
    }
});
