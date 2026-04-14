// js/notificaciones.js
let intervaloNotificaciones = null;

// Cargar notificaciones desde el servidor
function cargarNotificaciones() {
    fetch('../mod/cargar_notificaciones.php')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('panel-notificaciones-body');
            
            if (!container) return;
            
            if (data.length === 0) {
                container.innerHTML = `
                    <div class="notificaciones-vacio">
                        <i class="fas fa-bell-slash"></i>
                        <p>No hay notificaciones</p>
                    </div>
                `;
            } else {
                let html = '';
                data.forEach(alerta => {
                    let claseNivel = alerta.NIVEL.toLowerCase();
                    let fecha = new Date(alerta.FECHA_GENERACION);
                    let fechaFormateada = fecha.toLocaleDateString() + ' ' + fecha.toLocaleTimeString();
                    
                    html += `
                        <div class="notificacion-item ${claseNivel}" onclick="marcarLeida(${alerta.ALERTA_ID})">
                            <div class="notificacion-header">
                                <span class="notificacion-titulo">
                                    ${alerta.aprendiz_nombres} ${alerta.aprendiz_apellidos}
                                </span>
                                <span class="notificacion-nivel ${claseNivel}">${alerta.NIVEL}</span>
                            </div>
                            <div class="notificacion-descripcion">${alerta.DESCRIPCION}</div>
                            <div class="notificacion-meta">
                                <span><i class="fas fa-user-tie"></i> ${alerta.instructor_nombres ? alerta.instructor_nombres + ' ' + alerta.instructor_apellidos : 'Sistema'}</span>
                                <span><i class="fas fa-clock"></i> ${fechaFormateada}</span>
                            </div>
                        </div>
                    `;
                });
                container.innerHTML = html;
            }
            
            // Actualizar badge después de cargar
            actualizarBadgeNotificaciones();
        })
        .catch(error => {
            console.error('Error cargando notificaciones:', error);
        });
}

// Abrir y cerrar panel de notificaciones
function abrirPanelNotificaciones() {
    const panel = document.getElementById('panel-notificaciones');
    if (!panel) return;
    
    panel.classList.toggle('active');
    
    if (panel.classList.contains('active')) {
        cargarNotificaciones();
    }
}

// Marcar una notificación como leída
function marcarLeida(id) {
    fetch('../mod/marcar_notificacion.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            cargarNotificaciones();
            actualizarBadgeNotificaciones();
            
            // Mostrar mensaje de que se marcó como leída
            const toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true
            });
            
            toast.fire({
                icon: 'success',
                title: 'Notificación marcada como leída'
            });
        }
    })
    .catch(error => console.error('Error al marcar como leída:', error));
}

// Marcar todas las notificaciones como leídas
function marcarTodasLeidas() {
    fetch('../mod/marcar_todas_notificaciones.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            cargarNotificaciones();
            actualizarBadgeNotificaciones();
            
            Swal.fire({
                icon: 'success',
                title: '¡Todas las notificaciones han sido marcadas como leídas!',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000
            });
        }
    })
    .catch(error => console.error('Error al marcar todas como leídas:', error));
}

// Actualizar el badge de notificaciones
function actualizarBadgeNotificaciones() {
    fetch('../mod/contar_notificaciones.php')
        .then(response => response.json())
        .then(data => {
            const badge = document.querySelector('.notificaciones-badge');
            const btn = document.querySelector('.notificaciones-btn');
            
            if (!btn) return;
            
            if (data.total > 0) {
                if (badge) {
                    badge.textContent = data.total;
                    badge.style.display = 'flex';
                    // Animación de pulso cuando hay nuevas
                    badge.style.animation = 'none';
                    setTimeout(() => {
                        badge.style.animation = 'notiPulse 2s infinite';
                    }, 10);
                } else {
                    const nuevoBadge = document.createElement('span');
                    nuevoBadge.className = 'notificaciones-badge';
                    nuevoBadge.textContent = data.total;
                    btn.appendChild(nuevoBadge);
                }
                
                // Cambiar título del botón
                btn.title = `${data.total} notificaciones nuevas`;
            } else {
                if (badge) {
                    badge.style.display = 'none';
                    badge.remove();
                }
                btn.title = 'No hay notificaciones';
            }
        })
        .catch(error => console.error('Error actualizando badge:', error));
}

// Función para verificar nuevas notificaciones periódicamente
function iniciarVerificadorNotificaciones() {
    // Limpiar intervalo anterior si existe
    if (intervaloNotificaciones) {
        clearInterval(intervaloNotificaciones);
    }
    
    // Verificar cada 30 segundos
    intervaloNotificaciones = setInterval(() => {
        console.log('Verificando nuevas notificaciones...');
        actualizarBadgeNotificaciones();
    }, 30000); // 30 segundos
}

// Detener el verificador
function detenerVerificadorNotificaciones() {
    if (intervaloNotificaciones) {
        clearInterval(intervaloNotificaciones);
        intervaloNotificaciones = null;
    }
}

// Funciones del perfil
function toggleProfileMenu() {
    const menu = document.getElementById('profile-menu');
    const backdrop = document.getElementById('profile-backdrop');
    if (menu && backdrop) {
        menu.classList.toggle('active');
        backdrop.classList.toggle('active');
    }
}

function closeProfileMenu() {
    const menu = document.getElementById('profile-menu');
    const backdrop = document.getElementById('profile-backdrop');
    if (menu && backdrop) {
        menu.classList.remove('active');
        backdrop.classList.remove('active');
    }
}

// Inicializar eventos cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Botón de notificaciones
    const notiBtn = document.getElementById('notificaciones-btn');
    if (notiBtn) {
        notiBtn.addEventListener('click', abrirPanelNotificaciones);
    }
    
    // Botón de perfil
    const profileBtn = document.getElementById('profile-btn');
    if (profileBtn) {
        profileBtn.addEventListener('click', toggleProfileMenu);
    }
    
    // Actualizar badge al cargar la página
    actualizarBadgeNotificaciones();
    
    // Iniciar verificador periódico
    iniciarVerificadorNotificaciones();
    
    // Cerrar perfil con tecla ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeProfileMenu();
            const panel = document.getElementById('panel-notificaciones');
            if (panel) panel.classList.remove('active');
        }
    });
});

// Cerrar paneles al hacer clic fuera
document.addEventListener('click', function(event) {
    // Panel de notificaciones
    const panel = document.getElementById('panel-notificaciones');
    const notiBtn = document.getElementById('notificaciones-btn');
    
    if (notiBtn && panel && !notiBtn.contains(event.target) && !panel.contains(event.target)) {
        panel.classList.remove('active');
    }
    
    // Menú de perfil
    const profileMenu = document.getElementById('profile-menu');
    const profileBtn = document.getElementById('profile-btn');
    const backdrop = document.getElementById('profile-backdrop');
    
    if (profileBtn && profileMenu && backdrop && 
        !profileBtn.contains(event.target) && 
        !profileMenu.contains(event.target)) {
        closeProfileMenu();
    }
});

// Detener verificador cuando la página se cierra (opcional)
window.addEventListener('beforeunload', function() {
    detenerVerificadorNotificaciones();
});