document.addEventListener('DOMContentLoaded', function() {
    // Obtener todos los botones de pestaña
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    // Función para cambiar entre pestañas
    function changeTab(event) {
        // Obtener el atributo data-tab del botón clicado
        const targetTab = event.target.getAttribute('data-tab');
        
        // Quitar la clase 'active' de todos los botones
        tabButtons.forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Añadir la clase 'active' al botón clicado
        event.target.classList.add('active');
        
        // Ocultar todos los contenidos de pestaña
        tabContents.forEach(content => {
            content.classList.remove('active');
            
            // Añadir clases para la animación
            if (content.getAttribute('data-tab') === targetTab) {
                content.classList.remove('to-left', 'from-left');
            } else {
                if (content.classList.contains('active')) {
                    content.classList.add('to-left');
                } else {
                    content.classList.add('from-left');
                }
            }
        });
        
        // Mostrar el contenido de la pestaña seleccionada
        document.querySelector(`.tab-content[data-tab="${targetTab}"]`).classList.add('active');
    }
    
    // Añadir evento de clic a todos los botones de pestaña
    tabButtons.forEach(button => {
        button.addEventListener('click', changeTab);
    });
    
    // Password validation for registration form
    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            const password = document.getElementById('register-password').value;
            const confirmPassword = document.getElementById('register-confirm').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Les contrasenyes no coincideixen');
            }
        });
    }
});
