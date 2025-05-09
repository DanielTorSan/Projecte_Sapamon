// Variable para almacenar la pestaña activa actual
let activeTab = 'login';

// Función para manejar el cambio entre pestañas con animación de deslizamiento lateral
function showTab(tabName) {
    // Si es la misma pestaña, no hacer nada
    if (tabName === activeTab) return;
    
    const tabs = document.querySelectorAll('.tab-btn');
    const contents = document.querySelectorAll('.tab-content');
    
    // Determinar la dirección de la animación
    const isMovingRight = (activeTab === 'login' && tabName === 'register');
    
    // Actualizar botones de pestañas
    tabs.forEach(tab => {
        tab.classList.remove('active');
        if (tab.dataset.tab === tabName) {
            tab.classList.add('active');
        }
    });

    // Obtener los elementos de contenido
    const oldContent = document.querySelector(`.tab-content[data-tab="${activeTab}"]`);
    const newContent = document.querySelector(`.tab-content[data-tab="${tabName}"]`);
    
    // Preparar la nueva pestaña para la animación
    if (isMovingRight) {
        // Si vamos hacia la derecha (login -> register)
        newContent.classList.remove('to-left', 'from-left');
        oldContent.classList.add('to-left');
    } else {
        // Si vamos hacia la izquierda (register -> login)
        newContent.classList.add('from-left');
        oldContent.classList.remove('to-left');
    }
    
    // Eliminar la clase active de la pestaña actual
    oldContent.classList.remove('active');
    
    // Pequeña demora para que las clases CSS se apliquen correctamente
    setTimeout(() => {
        // Activar la nueva pestaña
        newContent.classList.add('active');
        
        // Actualizar la pestaña activa
        activeTab = tabName;
    }, 50);
}

// Función para obtener el valor de una cookie por su nombre
function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}

// Evento para inicializar las pestañas y autocompletar campos si existen cookies
document.addEventListener('DOMContentLoaded', () => {
    const tabs = document.querySelectorAll('.tab-btn');
    
    // Configurar los event listeners para los botones de pestañas
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            showTab(tab.dataset.tab);
        });
    });

    // Mostrar la primera pestaña por defecto
    if (tabs.length > 0) {
        activeTab = tabs[0].dataset.tab;
        showTab(activeTab);
    }
    
    // Verificar si hay mensajes de error para mostrar la pestaña correspondiente
    const hasRegisterError = document.querySelector('.error_registre') || 
                             (document.querySelector('.alert-error') && 
                             document.querySelector('.alert-error').textContent.includes('registrat'));
    
    if (hasRegisterError || document.querySelector('.exit_registre')) {
        showTab('register');
    }

    // Autocompletar el formulario si existen cookies
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        const rememberedUser = getCookie('remember_user');
        if (rememberedUser) {
            // Autocompletar nombre de usuario
            document.getElementById('login-username').value = rememberedUser;
            // Marcar checkbox
            document.getElementById('remember').checked = true;
            // Enfocar campo de contraseña
            document.getElementById('login-password').focus();
        }
    }
});