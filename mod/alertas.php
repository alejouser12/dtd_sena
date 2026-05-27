<?php
// mod/alertas.php
session_start();
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../conexion/AlertaDAO.php';
require_once __DIR__ . '/../conexion/AprendizDAO.php';
require_once __DIR__ . '/../conexion/RegionalDAO.php';
require_once __DIR__ . '/../conexion/CentroDAO.php';

$alertaDAO = new AlertaDAO();
$aprendizDAO = new AprendizDAO();
$regionalDAO = new RegionalDAO();
$centroDAO = new CentroDAO();

$rol = $_SESSION['rol'] ?? 'aprendiz';
$usuarioId = $_SESSION['usuario_id'] ?? null;

// Filtros (solo admin)
$regionalId = isset($_GET['regional_id']) ? (int)$_GET['regional_id'] : null;
$centroId   = isset($_GET['centro_id']) ? (int)$_GET['centro_id'] : null;

// Pestaña actual
$tab = $_GET['tab'] ?? 'activas';

if ($tab === 'historial') {
    $alertas = $alertaDAO->obtenerHistorialAlertas($rol, $usuarioId, $regionalId, $centroId);
    $titulo = 'Historial de Alertas';
    $iconoTitulo = 'fa-archive';
    $conteo = $alertaDAO->obtenerConteoAlertasPorEstado('Inactiva');
} else {
    $alertas = $alertaDAO->obtenerAlertasConFiltros($rol, $usuarioId, $regionalId, $centroId, 'Activa');
    $titulo = 'Alertas Activas';
    $iconoTitulo = 'fa-bell';
    $conteo = $alertaDAO->obtenerConteoAlertas();
}

// Asegurar que todas las claves existan (evita warnings)
$conteo = [
    'total' => $conteo['total'] ?? 0,
    'altas' => $conteo['altas'] ?? 0,
    'medias' => $conteo['medias'] ?? 0,
    'bajas' => $conteo['bajas'] ?? 0
];

// Datos para filtros (admin)
$regionales = [];
$centros = [];
if (esAdmin()) {
    $regionales = $regionalDAO->obtenerTodas();
    if ($regionalId) {
        $centros = $centroDAO->obtenerPorRegional($regionalId);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?> - DTD SENA</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* ===== ESTILOS (igual que antes) ===== */
        .tabs {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 10px;
        }
        .tab-link {
            padding: 8px 20px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            color: var(--color-texto-secundario);
            transition: all 0.3s ease;
        }
        .tab-link.active {
            background: var(--color-verde-1);
            color: white;
        }
        .tab-link i {
            margin-right: 6px;
        }
        .filtros {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        .alertas-lista {
            display: grid;
            gap: 15px;
            margin-top: 20px;
            animation: fadeIn 0.4s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .alertas-resumen {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .resumen-card {
            background: var(--color-blanco);
            border-radius: var(--border-radius-card);
            padding: 25px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: var(--shadow-card);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }
        .resumen-card.total { border-left: 4px solid var(--color-verde-1); }
        .resumen-card.alta { border-left: 4px solid #dc2626; }
        .resumen-card.media { border-left: 4px solid #f59e0b; }
        .resumen-card.baja { border-left: 4px solid var(--color-verde-1); }
        .resumen-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .total .resumen-icon { background: rgba(57,169,0,0.1); color: var(--color-verde-1); }
        .alta .resumen-icon { background: rgba(220,38,38,0.1); color: #dc2626; }
        .media .resumen-icon { background: rgba(245,158,11,0.1); color: #f59e0b; }
        .baja .resumen-icon { background: rgba(57,169,0,0.1); color: var(--color-verde-1); }
        .resumen-valor { font-size: 28px; font-weight: 700; color: var(--color-texto); line-height: 1.2; }
        .resumen-label { font-size: 13px; color: var(--color-texto-secundario); }

        .alerta-item {
            background: var(--color-blanco);
            border-radius: var(--border-radius-card);
            padding: 20px;
            display: flex;
            gap: 20px;
            box-shadow: var(--shadow-card);
            border: 1px solid var(--border-color);
            border-left: 4px solid transparent;
            transition: all 0.3s ease;
        }
        .alerta-item.alta { border-left-color: #dc2626; }
        .alerta-item.media { border-left-color: #f59e0b; }
        .alerta-item.baja { border-left-color: var(--color-verde-1); }
        .alerta-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .alta .alerta-icon { background: rgba(220,38,38,0.1); color: #dc2626; }
        .media .alerta-icon { background: rgba(245,158,11,0.1); color: #f59e0b; }
        .baja .alerta-icon { background: rgba(57,169,0,0.1); color: var(--color-verde-1); }
        .alerta-content { flex: 1; }
        .alerta-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 10px; }
        .alerta-header h3 { font-size: 18px; margin: 0; }
        .alerta-header h3 a {
            color: var(--color-verde-1);
            text-decoration: none;
            font-weight: 600;
        }
        .alerta-header h3 a:hover {
            color: var(--color-verde-2);
            text-decoration: underline;
        }
        .alerta-nivel {
            font-size: 12px;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 20px;
        }
        .alta .alerta-nivel { background: rgba(220,38,38,0.1); color: #dc2626; }
        .media .alerta-nivel { background: rgba(245,158,11,0.1); color: #f59e0b; }
        .baja .alerta-nivel { background: rgba(57,169,0,0.1); color: var(--color-verde-1); }
        .alerta-descripcion { color: var(--color-texto); margin-bottom: 10px; line-height: 1.5; }
        .alerta-meta { display: flex; gap: 20px; font-size: 13px; color: var(--color-texto-secundario); flex-wrap: wrap; }
        .alerta-meta i { color: var(--color-verde-1); }
        .alerta-accion { display: flex; align-items: center; }
        .btn-marcar-leida {
            background: transparent;
            border: 2px solid var(--color-verde-1);
            color: var(--color-verde-1);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-marcar-leida:hover { background: var(--color-verde-1); color: white; transform: scale(1.1); }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: var(--color-blanco);
            border-radius: var(--border-radius-card);
            border: 2px dashed var(--border-color);
            grid-column: 1/-1;
        }
        .empty-state i { font-size: 60px; color: var(--color-verde-1); opacity: 0.3; margin-bottom: 15px; }

        .btn-nueva-alerta {
            background: linear-gradient(135deg, var(--color-verde-1), var(--color-verde-2));
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 40px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(57,169,0,0.3);
        }
        .btn-nueva-alerta:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(57,169,0,0.4);
        }
        .alertas-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .modal {
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
            display: none;
            animation: fadeIn 0.3s ease;
        }
        .modal-content {
            background: var(--color-blanco);
            margin: 50px auto;
            padding: 30px;
            border-radius: var(--border-radius-card);
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            border: 1px solid var(--color-verde-1);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border-color);
        }
        .modal-header h2 { font-size: 24px; color: var(--color-verde-1); }
        .close { font-size: 28px; cursor: pointer; }
        .buscador-aprendices input { width: 100%; padding: 10px; margin-bottom: 15px; border: 2px solid var(--border-color); border-radius: 10px; }
        .lista-aprendices { max-height: 300px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: 10px; margin-bottom: 20px; }
        .aprendiz-item { padding: 12px 15px; border-bottom: 1px solid var(--border-color); cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
        .aprendiz-item.seleccionado { background: var(--color-verde-4); border-left: 4px solid var(--color-verde-1); }
        .form-actions { display: flex; gap: 15px; justify-content: flex-end; margin-top: 20px; }
    </style>
</head>
<body>
    <div id="loader"><img src="../img/logo_sena_verde.png" alt="Logo SENA" id="loader-logo"></div>
    <?php include '../config/header.php'; ?>

    <main class="container" id="contenido-principal" style="display:none; opacity:0;">
        <div class="alertas-header">
            <div class="page-header">
                <h1 class="page-title"><i class="fas <?= $iconoTitulo ?>"></i> <?= $titulo ?></h1>
                <p class="page-subtitle">Monitoreo y gestión de aprendices en riesgo</p>
            </div>
            <?php if (esAdmin() && $tab !== 'historial'): ?>
                <button class="btn-nueva-alerta" onclick="abrirModalNuevaAlerta()">
                    <i class="fas fa-plus-circle"></i> Nueva Alerta
                </button>
            <?php endif; ?>
        </div>

        <!-- Pestañas -->
        <div class="tabs">
            <a href="?tab=activas<?= ($regionalId ? '&regional_id='.$regionalId : '') . ($centroId ? '&centro_id='.$centroId : '') ?>" class="tab-link <?= $tab === 'activas' ? 'active' : '' ?>">
                <i class="fas fa-bell"></i> Activas
            </a>
            <a href="?tab=historial<?= ($regionalId ? '&regional_id='.$regionalId : '') . ($centroId ? '&centro_id='.$centroId : '') ?>" class="tab-link <?= $tab === 'historial' ? 'active' : '' ?>">
                <i class="fas fa-history"></i> Historial
            </a>
        </div>

        <!-- Filtros solo para admin -->
        <?php if (esAdmin()): ?>
        <div class="filtros">
            <div class="form-group">
                <label>Regional</label>
                <select id="regional_id" class="form-control">
                    <option value="">Todas</option>
                    <?php foreach ($regionales as $r): ?>
                        <option value="<?= $r['REGIONAL_ID'] ?>" <?= ($regionalId == $r['REGIONAL_ID']) ? 'selected' : '' ?>><?= htmlspecialchars($r['NOMBRE']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Centro</label>
                <select id="centro_id" class="form-control">
                    <option value="">Todos</option>
                    <?php foreach ($centros as $c): ?>
                        <option value="<?= $c['CENTRO_ID'] ?>" <?= ($centroId == $c['CENTRO_ID']) ? 'selected' : '' ?>><?= htmlspecialchars($c['NOMBRE']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <button id="btn-filtrar" class="btn-action">Filtrar</button>
                <button id="btn-limpiar" class="btn-cancel">Limpiar</button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tarjetas de resumen (se muestran en ambas pestañas) -->
        <div class="alertas-resumen">
            <div class="resumen-card total">
                <div class="resumen-icon"><i class="fas fa-bell"></i></div>
                <div><span class="resumen-valor"><?= $conteo['total'] ?></span><span class="resumen-label"> Total <?= $tab === 'activas' ? 'Alertas' : 'Registros' ?></span></div>
            </div>
            <div class="resumen-card alta">
                <div class="resumen-icon"><i class="fas fa-exclamation-circle"></i></div>
                <div><span class="resumen-valor"><?= $conteo['altas'] ?></span><span class="resumen-label"> Riesgo Alto</span></div>
            </div>
            <div class="resumen-card media">
                <div class="resumen-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div><span class="resumen-valor"><?= $conteo['medias'] ?></span><span class="resumen-label"> Riesgo Medio</span></div>
            </div>
            <div class="resumen-card baja">
                <div class="resumen-icon"><i class="fas fa-info-circle"></i></div>
                <div><span class="resumen-valor"><?= $conteo['bajas'] ?></span><span class="resumen-label"> Riesgo Bajo</span></div>
            </div>
        </div>

        <!-- Lista de alertas con animación -->
        <div class="alertas-lista" id="alertas-lista">
            <?php if (empty($alertas)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No hay <?= $tab === 'activas' ? 'alertas activas' : 'alertas en el historial' ?></h3>
                    <p><?= $tab === 'activas' ? 'No se encontraron alertas pendientes.' : 'No hay registros de alertas leídas o cerradas.' ?></p>
                </div>
            <?php else: ?>
                <?php foreach ($alertas as $a): 
                    $claseNivel = strtolower($a['NIVEL']);
                    $icono = $claseNivel === 'alto' ? 'fa-exclamation-circle' : ($claseNivel === 'medio' ? 'fa-exclamation-triangle' : 'fa-info-circle');
                ?>
                <div class="alerta-item <?= $claseNivel ?>" id="alerta-<?= $a['ALERTA_ID'] ?>">
                    <div class="alerta-icon"><i class="fas <?= $icono ?>"></i></div>
                    <div class="alerta-content">
                        <div class="alerta-header">
                            <h3>
                                <a href="aprendiz_detalle.php?id=<?= $a['APRENDIZ_ID'] ?>">
                                    <?= htmlspecialchars($a['aprendiz_nombres'] . ' ' . $a['aprendiz_apellidos']) ?>
                                </a>
                            </h3>
                            <span class="alerta-nivel"><?= $a['NIVEL'] ?></span>
                        </div>
                        <div class="alerta-descripcion"><?= nl2br(htmlspecialchars($a['DESCRIPCION'])) ?></div>
                        <div class="alerta-meta">
                            <span><i class="fas fa-tag"></i> <?= htmlspecialchars($a['TIPO_OBSERVACION'] ?? $a['TIPO_REGLA'] ?? 'General') ?></span>
                            <span><i class="fas fa-user-tie"></i> <?= trim(($a['instructor_nombres'] ?? 'Sistema') . ' ' . ($a['instructor_apellidos'] ?? '')) ?></span>
                            <span><i class="fas fa-clock"></i> <?= date('d/m/Y H:i', strtotime($a['FECHA_GENERACION'])) ?></span>
                            <?php if (!empty($a['CODIGO_FICHA'])): ?>
                            <span><i class="fas fa-layer-group"></i> Ficha <?= $a['CODIGO_FICHA'] ?></span>
                            <?php endif; ?>
                            <?php if ($tab === 'historial'): ?>
                            <span><i class="fas fa-check-circle"></i> Finalizada</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($tab === 'activas'): ?>
                    <div class="alerta-accion">
                        <button class="btn-marcar-leida" onclick="marcarLeida(<?= $a['ALERTA_ID'] ?>)">
                            <i class="fas fa-check"></i>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal Nueva Alerta -->
    <div id="modalNuevaAlerta" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-exclamation-triangle"></i> Nueva Alerta</h2>
                <span class="close" onclick="cerrarModalNuevaAlerta()">&times;</span>
            </div>
            <div class="buscador-aprendices">
                <input type="text" id="buscadorAprendiz" placeholder="Buscar aprendiz por nombre, documento o ficha..." onkeyup="filtrarAprendices()">
            </div>
            <div class="lista-aprendices" id="listaAprendices">
                <?php
                $aprendices = $aprendizDAO->obtenerAprendicesPaginados(0, 100);
                foreach ($aprendices as $ap): ?>
                <div class="aprendiz-item" data-id="<?= $ap['APRENDIZ_ID'] ?>" data-nombre="<?= strtolower($ap['NOMBRES'] . ' ' . $ap['APELLIDOS']) ?>" data-documento="<?= $ap['NUMERO_DOCUMENTO'] ?>" data-ficha="<?= $ap['CODIGO_FICHA'] ?? '' ?>" onclick="seleccionarAprendiz(this, <?= $ap['APRENDIZ_ID'] ?>, '<?= htmlspecialchars($ap['NOMBRES'] . ' ' . $ap['APELLIDOS']) ?>')">
                    <div>
                        <div class="aprendiz-nombre"><?= htmlspecialchars($ap['NOMBRES'] . ' ' . $ap['APELLIDOS']) ?></div>
                        <div class="aprendiz-info"><small><?= $ap['TIPO_DOCUMENTO'] . ' ' . $ap['NUMERO_DOCUMENTO'] ?></small><?php if(!empty($ap['CODIGO_FICHA'])): ?><span class="aprendiz-ficha">Ficha: <?= $ap['CODIGO_FICHA'] ?></span><?php endif; ?></div>
                    </div>
                    <i class="fas fa-chevron-right" style="color: var(--color-verde-1);"></i>
                </div>
                <?php endforeach; ?>
            </div>
            <form id="formNuevaAlerta" onsubmit="guardarAlerta(event)">
                <input type="hidden" name="estudiante_id" id="estudiante_id" value="">
                <input type="hidden" name="instructor_id" value="<?= (int)($_SESSION['usuario_ref_id'] ?? 0) ?>">
                <div class="form-group">
                    <label>Tipo de Alerta *</label>
                    <select name="tipo" class="form-control" required>
                        <option value="">Seleccione tipo</option>
                        <option>Académica</option><option>Disciplinaria</option><option>Actitudinal</option>
                        <option>Asistencia</option><option>Familiar</option><option>Salud</option><option>Otro</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Nivel de Riesgo *</label>
                    <select name="nivel_riesgo" class="form-control" required>
                        <option value="">Seleccione nivel</option>
                        <option value="Bajo">Bajo</option><option value="Medio">Medio</option><option value="Alto">Alto</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Descripción *</label>
                    <textarea name="descripcion" rows="4" class="form-control" required></textarea>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="cerrarModalNuevaAlerta()">Cancelar</button>
                    <button type="submit" class="btn-action">Guardar Alerta</button>
                </div>
            </form>
        </div>
    </div>

    <?php include '../config/footer.php'; ?>

    <script src="../js/tema.js"></script>
    <script src="../js/loader.js"></script>
    <script src="../js/panel_menu.js"></script>
    <script src="../js/dropdowns.js"></script>
    <script src="../js/profile_menu.js"></script>
    <script src="../js/sweetalerts.js"></script>
    <script src="../js/menu.js"></script>
    <script src="../js/notificaciones.js"></script>

    <script>
        let aprendizSeleccionadoId = null;

        function abrirModalNuevaAlerta() {
            document.getElementById('modalNuevaAlerta').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        function cerrarModalNuevaAlerta() {
            document.getElementById('modalNuevaAlerta').style.display = 'none';
            document.body.style.overflow = '';
            document.getElementById('formNuevaAlerta').reset();
            aprendizSeleccionadoId = null;
            document.querySelectorAll('.aprendiz-item').forEach(i => i.classList.remove('seleccionado'));
        }
        function filtrarAprendices() {
            let filtro = document.getElementById('buscadorAprendiz').value.toLowerCase();
            document.querySelectorAll('.aprendiz-item').forEach(item => {
                let nombre = item.dataset.nombre, doc = item.dataset.documento, ficha = item.dataset.ficha;
                item.style.display = (nombre.includes(filtro) || doc.includes(filtro) || ficha.includes(filtro)) ? 'flex' : 'none';
            });
        }
        function seleccionarAprendiz(el, id, nombre) {
            document.querySelectorAll('.aprendiz-item').forEach(i => i.classList.remove('seleccionado'));
            el.classList.add('seleccionado');
            document.getElementById('estudiante_id').value = id;
            aprendizSeleccionadoId = id;
        }
        function guardarAlerta(e) {
            e.preventDefault();
            if (!aprendizSeleccionadoId) {
                Swal.fire({ icon: 'warning', title: 'Seleccione un aprendiz', text: 'Debe seleccionar un aprendiz para crear la alerta' });
                return;
            }
            let formData = new FormData(document.getElementById('formNuevaAlerta'));
            fetch('guardar_alerta.php', { method: 'POST', body: formData })
                .then(r => r.json()).then(data => {
                    if (data.success) {
                        cerrarModalNuevaAlerta();
                        Swal.fire({ icon: 'success', title: 'Alerta creada', timer: 1500, showConfirmButton: false }).then(() => location.reload());
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Error al guardar' });
                    }
                });
        }
        function marcarLeida(id) {
            fetch('marcar_notificacion.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'id=' + id })
                .then(r => r.json()).then(data => { if (data.success) location.reload(); });
        }
        <?php if (esAdmin()): ?>
        document.getElementById('btn-filtrar')?.addEventListener('click', () => {
            let regional = document.getElementById('regional_id').value, centro = document.getElementById('centro_id').value;
            let url = '?tab=<?= $tab ?>';
            if (regional) url += '&regional_id=' + regional;
            if (centro) url += '&centro_id=' + centro;
            window.location.href = url;
        });
        document.getElementById('btn-limpiar')?.addEventListener('click', () => window.location.href = '?tab=<?= $tab ?>');
        <?php endif; ?>
        if (typeof initThemeToggle === 'function') setTimeout(initThemeToggle, 100);
    </script>
</body>
</html>