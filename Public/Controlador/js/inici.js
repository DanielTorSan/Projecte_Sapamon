// Archivo para gestionar la funcionalidad JavaScript de la página de inicio
document.addEventListener('DOMContentLoaded', function() {
    // Código para el botón del gestor de equips
    const btnEquips = document.getElementById('btn-equips');
    
    if (btnEquips) {
        btnEquips.addEventListener('click', function() {
            window.location.href = '?page=equips';
        });
    }
    
    // Gestión del modal de equipo principal
    const btnCambiarEquip = document.getElementById('btn-cambiar-equip');
    const equipPrincipalModal = document.getElementById('equip-principal-modal');
    const closeEquipModal = document.getElementById('close-equip-modal');
    const equipSearch = document.getElementById('equip-search');
    const equipsList = document.getElementById('equips-list');
    const btnGestorEquips = document.getElementById('btn-gestor-equips');
    
    // Abrir modal de selección de equipo principal
    if (btnCambiarEquip) {
        btnCambiarEquip.addEventListener('click', function() {
            equipPrincipalModal.classList.add('active');
            if (equipSearch) {
                setTimeout(() => equipSearch.focus(), 300);
            }
        });
    }
    
    // Cerrar modal de selección de equipo principal
    if (closeEquipModal) {
        closeEquipModal.addEventListener('click', function() {
            equipPrincipalModal.classList.remove('active');
        });
    }
    
    // Cerrar modal haciendo clic fuera del contenido
    if (equipPrincipalModal) {
        equipPrincipalModal.addEventListener('click', function(e) {
            if (e.target === equipPrincipalModal) {
                equipPrincipalModal.classList.remove('active');
            }
        });
    }
    
    // Buscar equipos por nombre
    if (equipSearch && equipsList) {
        equipSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            const equipItems = document.querySelectorAll('.equip-item');
            
            equipItems.forEach(item => {
                const equipNom = item.getAttribute('data-equip-nom').toLowerCase();
                if (equipNom.includes(searchTerm)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
    
    // Ir al gestor de equipos desde el modal si no tiene equipos
    if (btnGestorEquips) {
        btnGestorEquips.addEventListener('click', function() {
            window.location.href = '?page=equips';
        });
    }
    
    // Seleccionar equipo principal
    document.querySelectorAll('.select-equip-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const equipId = this.getAttribute('data-equip-id');
            selectEquipPrincipal(equipId);
        });
    });
    
    function selectEquipPrincipal(equipId) {
        const formData = new FormData();
        formData.append('equip_id', equipId);
        
        fetch('Controlador/Actualitzar_equip_principal.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Equip principal actualitzat correctament');
                equipPrincipalModal.classList.remove('active');
                // Recargar la página para mostrar el nuevo equipo principal
                window.location.reload();
            } else {
                alert('Error: ' + (data.error || 'No s\'ha pogut actualitzar l\'equip principal'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de connexió al actualitzar l\'equip principal');
        });
    }
});