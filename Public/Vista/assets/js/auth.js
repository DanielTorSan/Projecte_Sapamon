/**
 * Gestiona la funcionalidad de autenticación en el frontend
 * - Switching between login/register tabs
 * - Password validation
 */
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