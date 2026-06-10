<?php
session_start();
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../conexion/JustificacionDAO.php';
require_once __DIR__ . '/../conexion/InstructorDAO.php';

if (!esInstructor() && !esAdmin()) {
    header('Location: ' . app_url('login.php'));
    exit;
}

$instructorId = esInstructor() ? (int)($_SESSION['usuario_ref_id'] ?? 0) : 0;
$justificacionDAO = new JustificacionDAO();

if (esAdmin()) {
    $justificaciones = $justificacionDAO->obtenerTodasPendientes();
    $aprobadas = $justificacionDAO->obtenerPorEstado('aprobada');
    $rechazadas = $justificacionDAO->obtenerPorEstado('rechazada');
} else {
    if ($instructorId <= 0) {
        die('Instructor no identificado');
    }
    $justificaciones = $justificacionDAO->obtenerJustificacionesPendientesParaInstructor($instructorId);
    $aprobadas = $justificacionDAO->obtenerJustificacionesPorInstructorYEstado($instructorId, 'aprobada');
    $rechazadas = $justificacionDAO->obtenerJustificacionesPorInstructorYEstado($instructorId, 'rechazada');
}

$instructorDAO = new InstructorDAO();
$fichas = $instructorDAO->obtenerFichasIds($instructorId);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Justificaciones de Faltas</title>
    <link rel="stylesheet" href="<?= asset_url('css/style.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        /* ========== ESTILOS GENERALES (MEJORADOS PARA CLARO/OSCURO) ========== */
        .tabs-container {
            display: flex;
            gap: 0;
            margin-bottom: 25px;
            border-bottom: 2px solid var(--border-color);
        }
        .tab-btn {
            padding: 15px 25px;
            background: transparent;
            border: none;
            color: var(--color-texto-secundario);
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
            margin-bottom: -2px;
        }
        .tab-btn:hover {
            color: var(--color-verde-1);
        }
        .tab-btn.active {
            color: var(--color-verde-1);
            border-bottom-color: var(--color-verde-1);
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }

        /* Tarjetas de justificación */
        .justificacion-card {
            background: var(--color-blanco);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        .justificacion-card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        .card-info {
            flex: 1;
        }
        .aprendiz-nombre {
            font-size: 18px;
            font-weight: 700;
            color: var(--color-texto);
            margin-bottom: 5px;
        }
        .falta-fecha {
            font-size: 14px;
            color: var(--color-texto-secundario);
            margin-bottom: 8px;
        }

        /* Badges de ficha y programa - ahora usan el verde SENA institucional */
        .ficha-info {
            display: inline-block;
            background: var(--color-verde-1);
            color: white;
            padding: 5px 14px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            margin-right: 8px;
            margin-bottom: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        /* Modo oscuro: se mantiene el verde pero un poco más brillante */
        .dark-mode .ficha-info {
            background: #2ecc71; /* verde más brillante para oscuro */
            color: #1e2a1e;
        }

        /* Badges de estado */
        .estado-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            margin-right: 8px;
        }
        .estado-pendiente {
            background: #fff3cd;
            color: #856404;
        }
        .dark-mode .estado-pendiente {
            background: #f59e0b;
            color: #2d1b00;
        }
        .estado-aprobada {
            background: #d4edda;
            color: #155724;
        }
        .dark-mode .estado-aprobada {
            background: #28a745;
            color: #f0fff4;
        }
        .estado-rechazada {
            background: #f8d7da;
            color: #721c24;
        }
        .dark-mode .estado-rechazada {
            background: #dc3545;
            color: #fff5f5;
        }

        /* Caja de texto de justificación */
        .card-text {
            margin: 15px 0;
            padding: 12px;
            background: var(--color-gris-cuerpo);
            border-left: 3px solid var(--color-verde-1);
            border-radius: 4px;
            color: var(--color-texto);
            font-size: 14px;
            line-height: 1.6;
        }
        .dark-mode .card-text {
            background: #2a2a2a;
        }

        /* Enlace de archivo */
        .card-archivo {
            margin: 10px 0;
        }
        .archivo-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--color-verde-1);
            text-decoration: none;
            font-weight: 600;
            padding: 8px 12px;
            border: 1px solid var(--color-verde-1);
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        .archivo-link:hover {
            background: var(--color-verde-1);
            color: var(--color-blanco);
        }

        /* Botones de acción */
        .card-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        .btn-accion {
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-aprobar {
            background: #d4edda;
            color: #155724;
            border: 1px solid #155724;
        }
        .btn-aprobar:hover {
            background: #155724;
            color: #d4edda;
        }
        .dark-mode .btn-aprobar {
            background: #2c5e2e;
            color: #d4edda;
            border-color: #6fbf6f;
        }
        .btn-rechazar {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #721c24;
        }
        .btn-rechazar:hover {
            background: #721c24;
            color: #f8d7da;
        }
        .dark-mode .btn-rechazar {
            background: #8b3c3c;
            color: #ffe0e0;
            border-color: #e07a7a;
        }

        /* Comentario del instructor */
        .comentario-instructor {
            margin-top: 15px;
            padding: 12px;
            background: var(--color-gris-cuerpo);
            border-left: 3px solid var(--color-rojo);
            border-radius: 4px;
        }
        .comentario-title {
            font-weight: 700;
            color: var(--color-rojo);
            font-size: 13px;
            margin-bottom: 5px;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--color-texto-secundario);
        }
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        .dark-mode .justificacion-card {
            background: var(--color-gris-cuerpo);
            border-color: var(--border-color);
        }

        /* ========== ESTILOS PARA MODALES DE SWEETALERT2 (claro/oscuro) ========== */
        .swal2-popup {
            background: var(--color-blanco) !important;
            color: var(--color-texto) !important;
            border-radius: 20px !important;
            padding: 1.5rem !important;
        }
        .dark-mode .swal2-popup {
            background: var(--color-gris-cuerpo) !important;
            border: 1px solid var(--border-color) !important;
        }
        .swal2-textarea {
            background: var(--color-blanco) !important;
            color: var(--color-texto) !important;
            border: 2px solid var(--border-color) !important;
            border-radius: 12px !important;
            font-size: 14px !important;
            padding: 12px !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }
        .dark-mode .swal2-textarea {
            background: var(--color-blanco-opaco) !important;
            border-color: #444 !important;
            color: #eee !important;
        }
        .swal2-confirm, .swal2-cancel {
            border-radius: 30px !important;
            padding: 10px 20px !important;
            font-weight: bold !important;
        }
        .swal2-title {
            color: var(--color-texto) !important;
        }
        .swal2-html-container {
            color: var(--color-texto-secundario) !important;
        }
        .swal2-popup {
            width: 32em !important;
            max-width: 90% !important;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../config/header.php'; ?>

    <main class="container" style="max-width: 1000px;">
        <div style="margin: 30px 0;">
            <h1 style="display: flex; align-items: center; gap: 15px; margin-bottom: 10px;">
                <i class="fas fa-check-circle" style="color: var(--color-verde-1);"></i>
                Gestionar Justificaciones
            </h1>
            <p style="color: var(--color-texto-secundario); margin: 0;">Revisa y aprueba o rechaza las justificaciones de faltas enviadas por los aprendices</p>
        </div>

        <!-- Tabs -->
        <div class="tabs-container">
            <button class="tab-btn active" onclick="mostrarTab('pendientes')">
                <i class="fas fa-hourglass-half"></i> Pendientes <span id="count-pendientes" style="margin-left: 8px; background: var(--color-verde-1); color: white; padding: 2px 8px; border-radius: 12px; font-size: 12px;"><?= count($justificaciones) ?></span>
            </button>
            <button class="tab-btn" onclick="mostrarTab('aprobadas')">
                <i class="fas fa-check"></i> Aprobadas <span id="count-aprobadas" style="margin-left: 8px; background: #28a745; color: white; padding: 2px 8px; border-radius: 12px; font-size: 12px;"><?= count($aprobadas) ?></span>
            </button>
            <button class="tab-btn" onclick="mostrarTab('rechazadas')">
                <i class="fas fa-times"></i> Rechazadas <span id="count-rechazadas" style="margin-left: 8px; background: #dc3545; color: white; padding: 2px 8px; border-radius: 12px; font-size: 12px;"><?= count($rechazadas) ?></span>
            </button>
        </div>

        <!-- TAB: Pendientes -->
        <div id="tab-pendientes" class="tab-content active">
            <?php if (empty($justificaciones)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p style="font-size: 18px; margin: 0;">No hay justificaciones pendientes de revisión</p>
                </div>
            <?php else: ?>
                <?php foreach ($justificaciones as $just): ?>
                    <div class="justificacion-card">
                        <div class="card-header">
                            <div class="card-info">
                                <div class="aprendiz-nombre">
                                    <?= htmlspecialchars($just['aprendiz_nombres'] . ' ' . $just['aprendiz_apellidos']) ?>
                                </div>
                                <div class="falta-fecha">
                                    <i class="fas fa-calendar"></i> Falta del <?= date('d/m/Y', strtotime($just['fecha_falta'])) ?>
                                </div>
                                <div>
                                    <span class="ficha-info">
                                        <i class="fas fa-folder"></i> <?= htmlspecialchars($just['CODIGO_FICHA']) ?>
                                    </span>
                                    <span class="ficha-info">
                                        <i class="fas fa-book"></i> <?= htmlspecialchars($just['PROGRAMA_NOMBRE'] ?? 'N/A') ?>
                                    </span>
                                </div>
                            </div>
                            <div>
                                <span class="estado-badge estado-pendiente">
                                    <i class="fas fa-hourglass-half"></i> PENDIENTE
                                </span>
                            </div>
                        </div>

                        <?php if (!empty($just['TEXTO'])): ?>
                            <div class="card-text">
                                <strong>Justificación:</strong><br>
                                <?= nl2br(htmlspecialchars($just['TEXTO'])) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($just['ARCHIVO'])): ?>
                            <div class="card-archivo">
                                <a href="<?= htmlspecialchars(app_url($just['ARCHIVO'])) ?>" target="_blank" class="archivo-link">
                                    <i class="fas fa-file-download"></i> Ver anexo
                                </a>
                            </div>
                        <?php endif; ?>

                        <div class="card-actions">
                            <button class="btn-accion btn-aprobar" onclick="aprobarJustificacion(<?= $just['JUSTIFICACION_ID'] ?>)">
                                <i class="fas fa-check"></i> Aprobar
                            </button>
                            <button class="btn-accion btn-rechazar" onclick="abrirRechazar(<?= $just['JUSTIFICACION_ID'] ?>, '<?= htmlspecialchars($just['aprendiz_nombres'] . ' ' . $just['aprendiz_apellidos']) ?>')">
                                <i class="fas fa-times"></i> Rechazar
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- TAB: Aprobadas -->
        <div id="tab-aprobadas" class="tab-content">
            <?php if (empty($aprobadas)): ?>
                <div class="empty-state">
                    <i class="fas fa-check-circle"></i>
                    <p style="font-size: 18px; margin: 0;">No hay justificaciones aprobadas</p>
                </div>
            <?php else: ?>
                <?php foreach ($aprobadas as $just): ?>
                    <div class="justificacion-card">
                        <div class="card-header">
                            <div class="card-info">
                                <div class="aprendiz-nombre">
                                    <?= htmlspecialchars($just['aprendiz_nombres'] . ' ' . $just['aprendiz_apellidos']) ?>
                                </div>
                                <div class="falta-fecha">
                                    <i class="fas fa-calendar"></i> Falta del <?= date('d/m/Y', strtotime($just['fecha_falta'])) ?>
                                </div>
                                <div>
                                    <span class="ficha-info">
                                        <i class="fas fa-folder"></i> <?= htmlspecialchars($just['CODIGO_FICHA']) ?>
                                    </span>
                                </div>
                            </div>
                            <div>
                                <span class="estado-badge estado-aprobada">
                                    <i class="fas fa-check"></i> APROBADA
                                </span>
                            </div>
                        </div>
                        <?php if (!empty($just['TEXTO'])): ?>
                            <div class="card-text">
                                <strong>Justificación:</strong><br>
                                <?= nl2br(htmlspecialchars($just['TEXTO'])) ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($just['FECHA_RESPUESTA'])): ?>
                            <div style="font-size: 12px; color: var(--color-texto-secundario); margin-top: 10px;">
                                <i class="fas fa-user"></i> Aprobado el <?= date('d/m/Y H:i', strtotime($just['FECHA_RESPUESTA'])) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- TAB: Rechazadas -->
        <div id="tab-rechazadas" class="tab-content">
            <?php if (empty($rechazadas)): ?>
                <div class="empty-state">
                    <i class="fas fa-times-circle"></i>
                    <p style="font-size: 18px; margin: 0;">No hay justificaciones rechazadas</p>
                </div>
            <?php else: ?>
                <?php foreach ($rechazadas as $just): ?>
                    <div class="justificacion-card">
                        <div class="card-header">
                            <div class="card-info">
                                <div class="aprendiz-nombre">
                                    <?= htmlspecialchars($just['aprendiz_nombres'] . ' ' . $just['aprendiz_apellidos']) ?>
                                </div>
                                <div class="falta-fecha">
                                    <i class="fas fa-calendar"></i> Falta del <?= date('d/m/Y', strtotime($just['fecha_falta'])) ?>
                                </div>
                                <div>
                                    <span class="ficha-info">
                                        <i class="fas fa-folder"></i> <?= htmlspecialchars($just['CODIGO_FICHA']) ?>
                                    </span>
                                </div>
                            </div>
                            <div>
                                <span class="estado-badge estado-rechazada">
                                    <i class="fas fa-times"></i> RECHAZADA
                                </span>
                            </div>
                        </div>
                        <?php if (!empty($just['TEXTO'])): ?>
                            <div class="card-text">
                                <strong>Justificación:</strong><br>
                                <?= nl2br(htmlspecialchars($just['TEXTO'])) ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($just['COMENTARIO_INSTRUCTOR'])): ?>
                            <div class="comentario-instructor">
                                <div class="comentario-title">
                                    <i class="fas fa-comment"></i> Razón del rechazo:
                                </div>
                                <div class="comentario-text">
                                    <?= nl2br(htmlspecialchars($just['COMENTARIO_INSTRUCTOR'])) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($just['FECHA_RESPUESTA'])): ?>
                            <div style="font-size: 12px; color: var(--color-texto-secundario); margin-top: 10px;">
                                <i class="fas fa-user"></i> Rechazado el <?= date('d/m/Y H:i', strtotime($just['FECHA_RESPUESTA'])) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <?php include __DIR__ . '/../config/footer.php'; ?>

    <script src="<?= asset_url('js/tema.js') ?>"></script>
    <script src="<?= asset_url('js/loader.js') ?>"></script>
    <script src="<?= asset_url('js/menu.js') ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

    <script>
        function mostrarTab(tab) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            document.getElementById('tab-' + tab).classList.add('active');
            event.target.closest('.tab-btn').classList.add('active');
        }

        function aprobarJustificacion(justificacionId) {
            Swal.fire({
                title: '¿Aprobar justificación?',
                text: 'Al aprobar, la falta se marcará como excusa y el aprendiz será notificado.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, aprobar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#28a745'
            }).then((result) => {
                if (result.isConfirmed) {
                    enviarDecision(justificacionId, 'aprobada', null);
                }
            });
        }

        function abrirRechazar(justificacionId, nombreAprendiz) {
            Swal.fire({
                title: 'Rechazar justificación',
                html: `
                    <div style="text-align:left; margin-bottom:10px;">
                        <p><strong>Aprendiz:</strong> ${nombreAprendiz}</p>
                        <label for="comentario_rechazo" style="display:block; margin-bottom:5px; font-weight:600;">Motivo del rechazo:</label>
                        <textarea id="comentario_rechazo" class="swal2-textarea" 
                                  placeholder="Explica por qué rechazas esta justificación..." 
                                  rows="5"
                                  style="width:100%; border-radius:12px; border:2px solid var(--border-color); padding:12px; box-sizing:border-box; background:var(--color-blanco); color:var(--color-texto);"></textarea>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Rechazar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545',
                focusConfirm: false,
                preConfirm: () => {
                    const comentario = document.getElementById('comentario_rechazo').value;
                    if (!comentario.trim()) {
                        Swal.showValidationMessage('Debes proporcionar una razón para el rechazo');
                        return false;
                    }
                    return comentario.trim();
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    enviarDecision(justificacionId, 'rechazada', result.value);
                }
            });
        }

        function enviarDecision(justificacionId, estado, comentario) {
            const formData = new FormData();
            formData.append('justificacion_id', justificacionId);
            formData.append('estado', estado);
            if (comentario) {
                formData.append('comentario', comentario);
            }

            fetch('<?= htmlspecialchars(app_url('mod/actualizar_justificacion.php')) ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: estado === 'aprobada' ? 'Justificación aprobada' : 'Justificación rechazada',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message || 'No se pudo procesar la justificación', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Error al procesar la justificación', 'error');
            });
        }
    </script>
</body>
</html>