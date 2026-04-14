// js/menu.js
document.addEventListener('DOMContentLoaded', function() {
    console.log('Menu.js cargado');
    
    // Función para inicializar menú
    function initMenu() {
        const menuBtn = document.getElementById('menu-btn');
        const menuDropdown = document.getElementById('menu-dropdown');
        
        if (menuBtn && menuDropdown) {
            console.log('Menú encontrado');
            
            menuBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                menuDropdown.classList.toggle('active');
                
                // Cerrar perfil si está abierto
                const profileMenu = document.getElementById('profile-menu');
                const backdrop = document.getElementById('profile-backdrop');
                if (profileMenu) profileMenu.classList.remove('active');
                if (backdrop) backdrop.classList.remove('active');
            });
        } else {
            console.log('⏳ Esperando menú...');
            setTimeout(initMenu, 100);
        }
    }
    
    initMenu();
});