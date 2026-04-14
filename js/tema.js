//Sistema modo oscuro
// Función para aplicar el tema
function setTheme(mode) {
    console.log('Aplicando tema:', mode); // Para depuración
    
    if (mode === 'dark') {
        document.body.classList.add('dark-mode');
        document.documentElement.classList.add('dark-mode');
        localStorage.setItem('theme', 'dark');
    } else {
        document.body.classList.remove('dark-mode');
        document.documentElement.classList.remove('dark-mode');
        localStorage.setItem('theme', 'light');
    }
}

// Obtener tema del sistema
function getSystemTheme() {
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

// Cargar tema guardado o del sistema
function loadTheme() {
    const saved = localStorage.getItem('theme');
    return saved ? saved : getSystemTheme();
}

// Inicializar tema
function initTheme() {
    const theme = loadTheme();
    setTheme(theme);
    
    // Escuchar cambios en el sistema
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
        if (!localStorage.getItem('theme')) {
            setTheme(e.matches ? 'dark' : 'light');
        }
    });
}

// Inicializar inmediatamente
initTheme();
window.cambiarTema = function() {
    const esOscuro = document.body.classList.contains('dark-mode');
    setTheme(esOscuro ? 'light' : 'dark');
    
    // Mostrar notificación si existe SweetAlert
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: `Modo ${!esOscuro ? 'Oscuro' : 'Claro'}`,
            text: `Tema cambiado`,
            icon: 'success',
            timer: 1000,
            showConfirmButton: false,
            position: 'top-end',
            toast: true
        });
    }
};

// Configurar botón de tema SOLO si existe en la página
document.addEventListener('DOMContentLoaded', function() {
    const themeBtn = document.getElementById('theme-toggle');
    if (themeBtn) {
        // Remover eventos anteriores
        const newBtn = themeBtn.cloneNode(true);
        themeBtn.parentNode.replaceChild(newBtn, themeBtn);
        
        // Agregar evento nuevo
        newBtn.addEventListener('click', function(e) {
            e.preventDefault();
            window.cambiarTema();
        });
    }
    
    // Configurar toggles de contraseña si existen
    document.querySelectorAll('.toggle-password').forEach(btn => {
        btn.addEventListener('click', function() {
            const input = this.previousElementSibling;
            if (input && input.type) {
                input.type = input.type === 'password' ? 'text' : 'password';
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            }
        });
    });

// Funciones para notificaciones
function cargarNotificaciones() {
    fetch('mod/cargar_notificaciones.php')
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
                                <span><i class="fas fa-tag"></i> ${alerta.TIPO_REGLA}</span>
                                <span><i class="fas fa-clock"></i> ${new Date(alerta.FECHA_GENERACION).toLocaleDateString()}</span>
                            </div>
                        </div>
                    `;
                });
                container.innerHTML = html;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const container = document.getElementById('panel-notificaciones-body');
            if (container) {
                container.innerHTML = `
                    <div class="notificaciones-vacio">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>Error al cargar</p>
                    </div>
                `;
            }
        });
}

function abrirPanelNotificaciones() {
    const panel = document.getElementById('panel-notificaciones');
    if (!panel) return;
    
    panel.classList.toggle('active');
    
    if (panel.classList.contains('active')) {
        cargarNotificaciones();
    }
}

function marcarLeida(id) {
    fetch('mod/marcar_notificacion.php', {
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
        }
    });
}

function marcarTodasLeidas() {
    fetch('mod/marcar_todas_notificaciones.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            cargarNotificaciones();
            actualizarBadgeNotificaciones();
        }
    });
}

function actualizarBadgeNotificaciones() {
    fetch('mod/contar_notificaciones.php')
        .then(response => response.json())
        .then(data => {
            const badge = document.querySelector('.notificaciones-badge');
            const btn = document.querySelector('.notificaciones-btn');
            
            if (!btn) return;
            
            if (data.total > 0) {
                if (badge) {
                    badge.textContent = data.total;
                } else {
                    const nuevoBadge = document.createElement('span');
                    nuevoBadge.className = 'notificaciones-badge';
                    nuevoBadge.textContent = data.total;
                    btn.appendChild(nuevoBadge);
                }
            } else {
                if (badge) badge.remove();
            }
        });
}

//Funciones para notificaciones
function cargarNotificaciones() {
    fetch('mod/cargar_notificaciones.php')
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
                                <span><i class="fas fa-tag"></i> ${alerta.TIPO_REGLA}</span>
                                <span><i class="fas fa-clock"></i> ${new Date(alerta.FECHA_GENERACION).toLocaleDateString()}</span>
                            </div>
                        </div>
                    `;
                });
                container.innerHTML = html;
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function abrirPanelNotificaciones() {
    const panel = document.getElementById('panel-notificaciones');
    if (!panel) return;
    
    panel.classList.toggle('active');
    
    if (panel.classList.contains('active')) {
        cargarNotificaciones();
    }
}

function marcarLeida(id) {
    fetch('mod/marcar_notificacion.php', {
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
        }
    });
}

function marcarTodasLeidas() {
    fetch('mod/marcar_todas_notificaciones.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            cargarNotificaciones();
            actualizarBadgeNotificaciones();
        }
    });
}

function actualizarBadgeNotificaciones() {
    fetch('mod/contar_notificaciones.php')
        .then(response => response.json())
        .then(data => {
            const badge = document.querySelector('.notificaciones-badge');
            const btn = document.querySelector('.notificaciones-btn');
            
            if (!btn) return;
            
            if (data.total > 0) {
                if (badge) {
                    badge.textContent = data.total;
                } else {
                    const nuevoBadge = document.createElement('span');
                    nuevoBadge.className = 'notificaciones-badge';
                    nuevoBadge.textContent = data.total;
                    btn.appendChild(nuevoBadge);
                }
            } else {
                if (badge) badge.remove();
            }
        });
}

// Asignar eventos cuando el DOM cargue
document.addEventListener('DOMContentLoaded', function() {
    // Botón de notificaciones
    const notiBtn = document.getElementById('notificaciones-btn');
    if (notiBtn) {
        notiBtn.addEventListener('click', abrirPanelNotificaciones);
    }
    
    // Botón de perfil
    const profileBtn = document.getElementById('profile-btn');
    if (profileBtn && typeof toggleProfileMenu === 'function') {
        profileBtn.addEventListener('click', toggleProfileMenu);
    }
});

// Cerrar panel al hacer clic fuera
document.addEventListener('click', function(event) {
    const panel = document.getElementById('panel-notificaciones');
    const btn = document.getElementById('notificaciones-btn');
    
    if (btn && panel && !btn.contains(event.target) && !panel.contains(event.target)) {
        panel.classList.remove('active');
    }
});

// Función para el perfil
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

// Cerrar perfil con ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeProfileMenu();
    }
});
});