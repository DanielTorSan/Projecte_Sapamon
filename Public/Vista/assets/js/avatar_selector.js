/**
 * Gestiona la selección de avatares en la interfaz
 * - Permite seleccionar un avatar de la galería
 * - Actualiza el valor del input oculto para el envío del formulario
 */
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