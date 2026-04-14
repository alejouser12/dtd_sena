<?php
// config/notificaciones_panel.php
?>
<div id="panel-notificaciones" class="panel-notificaciones">
    <div class="panel-notificaciones-header">
        <h3><i class="fas fa-bell"></i> Notificaciones</h3>
        <?php if($totalAlertas > 0): ?>
            <button class="btn-marcar-leidas" onclick="marcarTodasLeidas()">
                <i class="fas fa-check-double"></i>
            </button>
        <?php endif; ?>
    </div>
    
    <div class="panel-notificaciones-body" id="panel-notificaciones-body">
        <div class="notificaciones-loading">
            <i class="fas fa-spinner fa-spin"></i> Cargando...
        </div>
    </div>
    
    <div class="panel-notificaciones-footer">
        <a href="mod/alertas.php">Ver todas las alertas</a>
    </div>
</div>



<script>
function cargarNotificaciones() {
    fetch('mod/cargar_notificaciones.php')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('panel-notificaciones-body');
            
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
        });
}

function abrirPanelNotificaciones() {
    const panel = document.getElementById('panel-notificaciones');
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
            actualizarBadge();
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
            actualizarBadge();
        }
    });
}

function actualizarBadge() {
    fetch('mod/contar_notificaciones.php')
        .then(response => response.json())
        .then(data => {
            const badge = document.querySelector('.notificaciones-badge');
            const btn = document.querySelector('.notificaciones-btn');
            
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

// Asignar evento al botón de notificaciones
document.getElementById('notificaciones-btn').addEventListener('click', abrirPanelNotificaciones);

// Cerrar panel al hacer clic fuera
document.addEventListener('click', function(event) {
    const panel = document.getElementById('panel-notificaciones');
    const btn = document.getElementById('notificaciones-btn');
    
    if (btn && panel && !btn.contains(event.target) && !panel.contains(event.target)) {
        panel.classList.remove('active');
    }
});
</script>