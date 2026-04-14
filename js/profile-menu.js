// Función para inicializar el menú de perfil
function initProfileMenuSimple() {
    console.log('Intentando inicializar menú de perfil...');
    
    // Buscar los elementos
    const profileBtn = document.getElementById('profile-btn');
    const profileMenu = document.getElementById('profile-menu');
    const profileBackdrop = document.getElementById('profile-backdrop');
    
    // Mostrar qué encontramos
    console.log('Botón de perfil:', profileBtn);
    console.log('Menú de perfil:', profileMenu);
    console.log('Backdrop:', profileBackdrop);
    
    // Si no encuentra los elementos, salir
    if (!profileBtn || !profileMenu || !profileBackdrop) {
        console.log('No se encontraron todos los elementos del perfil');
        return false;
    }
    
    console.log('Elementos encontrados, asignando eventos...');
    
    // Función para abrir menú
    function abrirMenu() {
        console.log(' Abriendo menú');
        profileMenu.style.display = 'block';
        profileBackdrop.style.display = 'block';
        
        // Forzar un pequeño retraso para la animación
        setTimeout(() => {
            profileMenu.classList.add('active');
            profileBackdrop.classList.add('active');
        }, 10);
    }
    
    // Función para cerrar menú
    function cerrarMenu() {
        console.log('Cerrando menú');
        profileMenu.classList.remove('active');
        profileBackdrop.classList.remove('active');
        
        // Ocultar después de la animación
        setTimeout(() => {
            if (!profileMenu.classList.contains('active')) {
                profileMenu.style.display = 'none';
                profileBackdrop.style.display = 'none';
            }
        }, 300);
    }
    
    // Asignar evento click al botón
    profileBtn.onclick = function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('Click en botón de perfil');
        
        if (profileMenu.classList.contains('active')) {
            cerrarMenu();
        } else {
            abrirMenu();
        }
    };
    
    // Asignar evento click al backdrop
    profileBackdrop.onclick = function(e) {
        e.preventDefault();
        console.log('Click en backdrop');
        cerrarMenu();
    };
    
    // Prevenir que clicks dentro del menú lo cierren
    profileMenu.onclick = function(e) {
        e.stopPropagation();
    };
    
    // Cerrar con tecla ESC
    document.onkeydown = function(e) {
        if (e.key === 'Escape' && profileMenu.classList.contains('active')) {
            console.log('ESC presionado');
            cerrarMenu();
        }
    };
    
    console.log('Eventos asignados correctamente');
    return true;
}

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded');
    
    // Intentar inmediatamente
    setTimeout(function() {
        if (!initProfileMenuSimple()) {
            console.log('Elementos no disponibles, esperando...');
            
            // Si no encuentra, esperar y reintentar cada 500ms
            const intervalo = setInterval(function() {
                if (initProfileMenuSimple()) {
                    clearInterval(intervalo);
                    console.log('Inicialización exitosa');
                }
            }, 500);
            
            // Detener después de 5 segundos
            setTimeout(function() {
                clearInterval(intervalo);
                console.log('Timeout: No se pudo inicializar');
            }, 5000);
        }
    }, 100);
});

// También intentar cuando la ventana cargue
window.addEventListener('load', function() {
    console.log('Window Load');
    initProfileMenuSimple();
});