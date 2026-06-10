<?php
// mod/instructor_dashboard.php — v2 con manejo robusto de errores
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/app.php';

if (!esInstructor() && !esAdmin()) {
    redirect_to('index.php');
}

require_once __DIR__ . '/../conexion/instructorDAO.php';
require_once __DIR__ . '/../conexion/FichaDAO.php';
require_once __DIR__ . '/../conexion/AlertaDAO.php';
require_once __DIR__ . '/../conexion/JustificacionDAO.php';  // <-- NUEVO para justificaciones

$instId = 0;
// Preferir instructor_id pasado por GET (útil para administradores viendo otro instructor)
if (isset($_GET['instructor_id']) && is_numeric($_GET['instructor_id'])) {
    $instId = (int)$_GET['instructor_id'];
} else {
    $instId = (int)($_SESSION['usuario_ref_id'] ?? 0);
}

$instructor = null;
$fichas     = [];
$alertas    = [];

if ($instId > 0) {
    try {
        $instDAO    = new InstructorDAO();
        $fichaDAO   = new FichaDAO();
        $alertaDAO  = new AlertaDAO();

        $instructor = $instDAO->obtenerPorId($instId);
        $fichas     = $instDAO->obtenerFichas($instId);
        $alertas    = $alertaDAO->obtenerAlertasConFiltros('instructor', $instId, null, null, 'Activa');
    } catch (Exception $e) {
        error_log('instructor_dashboard error: ' . $e->getMessage());
    }
}

// Si no se obtuvo instId desde session o GET, intentar resolver por email de sesión
if (esInstructor() && $instId <= 0) {
    try {
        $instDAO = new InstructorDAO();
        $email = $_SESSION['usuario_email'] ?? '';
        if (!empty($email)) {
            $c = $instDAO->buscarPorColumna('EMAIL', $email);
            if (!empty($c) && isset($c[0]['INSTRUCTOR_ID'])) {
                $instId = (int)$c[0]['INSTRUCTOR_ID'];
                // volver a cargar datos
                $instructor = $instDAO->obtenerPorId($instId);
                $fichas     = $instDAO->obtenerFichas($instId);
                $alertaDAO  = $alertaDAO ?? new AlertaDAO();
                $alertas    = $alertaDAO->obtenerAlertasConFiltros('instructor', $instId, null, null, 'Activa');
            }
        }
    } catch (Exception $e) {
        error_log('instructor_dashboard resolver por email error: ' . $e->getMessage());
    }
}

// Contar justificaciones pendientes para el instructor (solo sus fichas)
$justificacionesPendientes = 0;
if ($instId > 0 && esInstructor()) {
    try {
        $justDAO = new JustificacionDAO();
        $justificacionesPendientes = $justDAO->contarPendientesPorInstructor($instId);
    } catch (Exception $e) {
        error_log('Error contando justificaciones: ' . $e->getMessage());
    }
}

// Log simple para depuración cuando un instructor no ve datos
if (esInstructor()) {
    error_log(sprintf("instructor_dashboard: instId=%s, instructor_found=%s, fichas_count=%d, alertas_count=%d, just_pend=%d", var_export($instId, true), $instructor ? '1' : '0', is_array($fichas) ? count($fichas) : 0, is_array($alertas) ? count($alertas) : 0, $justificacionesPendientes));
}

$regionalNombre = '—';
$centroNombre   = '—';

if (!empty($fichas)) {
    try {
        $fichaDAO  = $fichaDAO ?? new FichaDAO();
        $detalle   = $fichaDAO->obtenerPorId((int)$fichas[0]['FICHA_ID']);
        if ($detalle) {
            $centroNombre   = $detalle['centro_nombre']   ?? '—';
            $regionalNombre = $detalle['regional_nombre'] ?? '—';
        }
    } catch (Exception $e) { }
}

$totalFichas     = count($fichas);
$fichasActivas   = count(array_filter($fichas, fn($f) => ($f['ESTADO'] ?? '') === 'Activa'));
$totalAprendices = (int)array_sum(array_column($fichas, 'total_aprendices'));
$totalAlertas    = count($alertas);
$alertasAltas    = count(array_filter($alertas, fn($a) => strtolower($a['NIVEL'] ?? '') === 'alto'));

$n = $instructor['NOMBRES']   ?? ($_SESSION['usuario_nombre'] ?? 'Instructor');
$a = $instructor['APELLIDOS'] ?? '';
$iniciales = strtoupper(substr($n, 0, 1) . substr($a, 0, 1));
if ($iniciales === '') $iniciales = 'IN';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Instructor — DTD SENA</title>
<link rel="stylesheet" href="<?= htmlspecialchars(asset_url('css/style.css')) ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
.inst-hero{background:linear-gradient(135deg,#39a900,#2d8a00);border-radius:24px;padding:36px;color:#fff;display:flex;align-items:center;gap:30px;flex-wrap:wrap;margin-bottom:32px;position:relative;overflow:hidden;box-shadow:0 20px 40px rgba(57,169,0,.35);}
.inst-hero::after{content:'\f19d';font-family:'Font Awesome 6 Free';font-weight:900;position:absolute;right:-10px;bottom:-20px;font-size:160px;opacity:.07;color:#fff;}
.inst-avatar{width:90px;height:90px;background:rgba(255,255,255,.2);border:3px solid rgba(255,255,255,.4);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:36px;font-weight:800;color:#fff;flex-shrink:0;}
.inst-hero h1{font-size:26px;font-weight:800;margin-bottom:4px;}
.inst-hero p{font-size:13px;opacity:.85;margin-bottom:12px;}
.inst-badge{background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.3);padding:6px 14px;border-radius:20px;font-size:12px;font-weight:600;display:inline-flex;align-items:center;gap:6px;margin:3px;}
.inst-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:18px;margin-bottom:32px;}
.inst-stat{background:var(--color-blanco);border:1px solid var(--border-color);border-radius:16px;padding:22px 18px;display:flex;align-items:center;gap:16px;box-shadow:var(--shadow-card);transition:.25s;text-decoration:none;color:var(--color-texto);}
.inst-stat:hover{transform:translateY(-4px);border-color:var(--color-verde-1);}
.inst-stat-icon{width:52px;height:52px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:22px;color:#fff;flex-shrink:0;}
.inst-stat-val{font-size:28px;font-weight:800;color:var(--color-texto);line-height:1;}
.inst-stat-lbl{font-size:11px;color:var(--color-texto-secundario);margin-top:3px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;}
.sec-hdr{display:flex;justify-content:space-between;align-items:center;margin:28px 0 16px;flex-wrap:wrap;gap:10px;}
.sec-hdr h2{font-size:20px;font-weight:800;display:flex;align-items:center;gap:8px;}
.sec-hdr h2 i{color:var(--color-verde-1);}
.fichas-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px;margin-bottom:32px;}
.ficha-card{background:var(--color-blanco);border:1px solid var(--border-color);border-radius:18px;overflow:hidden;box-shadow:var(--shadow-card);transition:.3s;}
.ficha-card:hover{transform:translateY(-5px);border-color:var(--color-verde-1);}
.ficha-top{background:linear-gradient(135deg,var(--color-verde-1),var(--color-verde-2));padding:14px 18px;display:flex;justify-content:space-between;align-items:center;color:#fff;}
.ficha-code{font-size:18px;font-weight:800;}
.ficha-body{padding:16px 18px;}
.ficha-prog{font-weight:700;font-size:13px;margin-bottom:8px;color:var(--color-texto);}
.ficha-meta{font-size:12px;color:var(--color-texto-secundario);margin-bottom:4px;display:flex;align-items:center;gap:5px;}
.ficha-meta i{color:var(--color-verde-1);width:12px;}
.ficha-actions{display:flex;gap:7px;margin-top:12px;flex-wrap:wrap;}
.btn-sm{display:inline-flex;align-items:center;gap:5px;padding:7px 13px;border-radius:9px;font-size:11px;font-weight:700;cursor:pointer;text-decoration:none;border:none;transition:.2s;}
.btn-sm-green{background:var(--color-verde-1);color:#fff;} .btn-sm-green:hover{background:var(--color-verde-2);color:#fff;}
.btn-sm-outline{background:transparent;border:1.5px solid var(--color-verde-1);color:var(--color-verde-1);} .btn-sm-outline:hover{background:rgba(57,169,0,.1);}
.btn-sm-blue{background:rgba(59,130,246,.1);border:1.5px solid #3b82f6;color:#3b82f6;} .btn-sm-blue:hover{background:#3b82f6;color:#fff;}
.alerta-mini{background:var(--color-blanco);border:1px solid var(--border-color);border-radius:12px;padding:13px 16px;display:flex;gap:12px;align-items:flex-start;margin-bottom:9px;border-left:4px solid transparent;transition:.2s;}
.alerta-mini.alto{border-left-color:#dc2626;}.alerta-mini.medio{border-left-color:#f59e0b;}.alerta-mini.bajo{border-left-color:var(--color-verde-1);}
.alerta-mini-icon{font-size:18px;margin-top:1px;flex-shrink:0;}
.alerta-mini.alto .alerta-mini-icon{color:#dc2626;}.alerta-mini.medio .alerta-mini-icon{color:#f59e0b;}.alerta-mini.bajo .alerta-mini-icon{color:var(--color-verde-1);}
.alerta-mini-nombre{font-weight:700;font-size:13px;color:var(--color-texto);}
.alerta-mini-desc{font-size:12px;color:var(--color-texto-secundario);margin-top:2px;}
.alerta-mini-fecha{font-size:11px;color:var(--color-texto-secundario);margin-top:3px;}
.empty-box{text-align:center;padding:36px 20px;color:var(--color-texto-secundario);background:var(--color-blanco);border-radius:16px;border:2px dashed var(--border-color);}
.empty-box i{font-size:38px;opacity:.2;display:block;margin-bottom:10px;}
.dark-mode .inst-stat,.dark-mode .ficha-card,.dark-mode .alerta-mini{background:var(--color-gris-cuerpo);}
</style>
</head>
<body>
<div id="loader">
    <img src="<?= htmlspecialchars(asset_url('img/logo_sena_verde.png')) ?>" alt="" id="loader-logo">
</div>
<?php include '../config/header.php'; ?>

<main class="container" id="contenido-principal" style="display:none;opacity:0;">

    <div class="inst-hero">
        <div class="inst-avatar"><?= htmlspecialchars($iniciales) ?></div>
        <div>
            <h1><?= htmlspecialchars(trim("$n $a")) ?: 'Instructor' ?></h1>
            <p><i class="fas fa-chalkboard-teacher"></i> Instructor SENA
                <?php if (!empty($instructor['ESPECIALIDAD'])): ?>
                    · <?= htmlspecialchars($instructor['ESPECIALIDAD']) ?>
                <?php endif; ?>
            </p>
            <div>
                <?php if ($regionalNombre !== '—'): ?>
                    <span class="inst-badge"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($regionalNombre) ?></span>
                <?php endif; ?>
                <?php if ($centroNombre !== '—'): ?>
                    <span class="inst-badge"><i class="fas fa-building"></i> <?= htmlspecialchars($centroNombre) ?></span>
                <?php endif; ?>
                <?php if (!empty($instructor['EMAIL'])): ?>
                    <span class="inst-badge"><i class="fas fa-envelope"></i> <?= htmlspecialchars($instructor['EMAIL']) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="inst-stats">
        <div class="inst-stat">
            <div class="inst-stat-icon" style="background:linear-gradient(135deg,#39a900,#2d8a00);"><i class="fas fa-layer-group"></i></div>
            <div><div class="inst-stat-val"><?= $totalFichas ?></div><div class="inst-stat-lbl">Fichas a cargo</div></div>
        </div>
        <div class="inst-stat">
            <div class="inst-stat-icon" style="background:linear-gradient(135deg,#16a34a,#15803d);"><i class="fas fa-check-circle"></i></div>
            <div><div class="inst-stat-val"><?= $fichasActivas ?></div><div class="inst-stat-lbl">Fichas activas</div></div>
        </div>
        <div class="inst-stat">
            <div class="inst-stat-icon" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8);"><i class="fas fa-user-graduate"></i></div>
            <div><div class="inst-stat-val"><?= $totalAprendices ?></div><div class="inst-stat-lbl">Aprendices</div></div>
        </div>
        <div class="inst-stat">
            <div class="inst-stat-icon" style="background:linear-gradient(135deg,#dc2626,#b91c1c);"><i class="fas fa-bell"></i></div>
            <div><div class="inst-stat-val"><?= $totalAlertas ?></div><div class="inst-stat-lbl">Alertas activas</div></div>
        </div>
    </div>

    <div class="sec-hdr">
        <h2><i class="fas fa-layer-group"></i> Mis Fichas</h2>
        <?php if ($totalFichas > 0): ?>
        <a href="<?= htmlspecialchars(app_url('mod/aprendices.php?instructor_id='.$instId)) ?>" class="btn-sm btn-sm-outline">
            <i class="fas fa-users"></i> Ver todos mis aprendices
        </a>
        <?php endif; ?>
    </div>

    <?php if (empty($fichas)): ?>
        <div class="empty-box">
            <i class="fas fa-folder-open"></i>
            <p>No tienes fichas asignadas aún.<br>Pídele al administrador que te asigne fichas.</p>
        </div>
    <?php else: ?>
    <div class="fichas-grid">
        <?php foreach ($fichas as $f): ?>
        <div class="ficha-card">
            <div class="ficha-top">
                <span class="ficha-code"><?= htmlspecialchars($f['CODIGO_FICHA'] ?? '—') ?></span>
                <span style="background:rgba(255,255,255,.2);padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;">
                    <?= htmlspecialchars($f['ESTADO'] ?? '—') ?>
                </span>
            </div>
            <div class="ficha-body">
                <div class="ficha-prog"><?= htmlspecialchars($f['PROGRAMA_NOMBRE'] ?? '—') ?></div>
                <div class="ficha-meta"><i class="fas fa-users"></i><?= (int)($f['total_aprendices'] ?? 0) ?> aprendices</div>
                <div class="ficha-meta"><i class="fas fa-calendar-alt"></i>Inicio: <?= !empty($f['FECHA_INICIO']) ? date('d/m/Y', strtotime($f['FECHA_INICIO'])) : '—' ?></div>
                <div class="ficha-meta"><i class="fas fa-calendar-check"></i>Fin: <?= !empty($f['FECHA_FIN']) ? date('d/m/Y', strtotime($f['FECHA_FIN'])) : '—' ?></div>
                <div class="ficha-actions">
                    <a href="<?= htmlspecialchars(app_url('mod/asistencias_detalle.php?id='.(int)($f['FICHA_ID'] ?? 0))) ?>" class="btn-sm btn-sm-green">
                        <i class="fas fa-calendar-check"></i> Asistencia
                    </a>
                    <a href="<?= htmlspecialchars(app_url('mod/ficha_detalle.php?id='.(int)($f['FICHA_ID'] ?? 0))) ?>" class="btn-sm btn-sm-outline">
                        <i class="fas fa-eye"></i> Ver ficha
                    </a>
                    <a href="<?= htmlspecialchars(app_url('mod/gestionar_horario.php?ficha_id='.(int)($f['FICHA_ID'] ?? 0))) ?>" class="btn-sm btn-sm-blue">
                        <i class="fas fa-calendar-week"></i> Horario
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="sec-hdr">
        <h2><i class="fas fa-bell"></i> Alertas de mis Aprendices</h2>
        <?php if ($totalAlertas > 0): ?>
        <a href="<?= htmlspecialchars(app_url('mod/alertas.php'.($instId?('?instructor_id='.$instId):''))) ?>" class="btn-sm btn-sm-outline">
            Ver todas (<?= $totalAlertas ?>)
        </a>
        <?php endif; ?>
    </div>

    <?php if (empty($alertas)): ?>
        <div class="empty-box">
            <i class="fas fa-bell-slash"></i>
            <p>No hay alertas activas en tus fichas. ¡Todo tranquilo!</p>
        </div>
    <?php else: ?>
        <?php foreach (array_slice($alertas, 0, 5) as $al):
            $nivel  = strtolower($al['NIVEL'] ?? 'bajo');
            $icono  = $nivel === 'alto' ? 'fa-exclamation-circle' : ($nivel === 'medio' ? 'fa-exclamation-triangle' : 'fa-info-circle');
            $nombre = trim(($al['aprendiz_nombres'] ?? '') . ' ' . ($al['aprendiz_apellidos'] ?? ''));
        ?>
        <div class="alerta-mini <?= $nivel ?>">
            <div class="alerta-mini-icon"><i class="fas <?= $icono ?>"></i></div>
            <div style="flex:1;">
                <div class="alerta-mini-nombre">
                    <?php if (!empty($al['APRENDIZ_ID'])): ?>
                    <a href="<?= htmlspecialchars(app_url('mod/aprendiz_detalle.php?id='.(int)$al['APRENDIZ_ID'])) ?>" style="color:inherit;text-decoration:none;">
                        <?= htmlspecialchars($nombre) ?>
                    </a>
                    <?php else: ?>
                        <?= htmlspecialchars($nombre ?: 'Aprendiz') ?>
                    <?php endif; ?>
                </div>
                <div class="alerta-mini-desc"><?= htmlspecialchars(substr($al['DESCRIPCION'] ?? '', 0, 90)) ?><?= strlen($al['DESCRIPCION'] ?? '') > 90 ? '…' : '' ?></div>
                <div class="alerta-mini-fecha">
                    <i class="fas fa-clock"></i>
                    <?= !empty($al['FECHA_GENERACION']) ? date('d/m/Y H:i', strtotime($al['FECHA_GENERACION'])) : '—' ?>
                    <?php if (!empty($al['CODIGO_FICHA'])): ?> · Ficha <?= htmlspecialchars($al['CODIGO_FICHA']) ?><?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if ($totalAlertas > 5): ?>
        <div style="text-align:center;margin-top:10px;">
            <a href="<?= htmlspecialchars(app_url('mod/alertas.php')) ?>" class="btn-sm btn-sm-outline">
                <i class="fas fa-list"></i> Ver <?= $totalAlertas - 5 ?> más
            </a>
        </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="sec-hdr" style="margin-top:32px;">
        <h2><i class="fas fa-bolt"></i> Accesos Rápidos</h2>
    </div>
    <div class="inst-stats" style="margin-bottom:0;">
        <a href="<?= htmlspecialchars(app_url('mod/aprendices.php?instructor_id='.$instId)) ?>" class="inst-stat">
            <div class="inst-stat-icon" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8);"><i class="fas fa-user-graduate"></i></div>
            <div><div class="inst-stat-val" style="font-size:15px;">Aprendices</div><div class="inst-stat-lbl">Ver listado</div></div>
        </a>
        <a href="<?= htmlspecialchars(app_url('mod/asistencias.php?instructor_id='.$instId)) ?>" class="inst-stat">
            <div class="inst-stat-icon" style="background:linear-gradient(135deg,#39a900,#2d8a00);"><i class="fas fa-calendar-check"></i></div>
            <div><div class="inst-stat-val" style="font-size:15px;">Asistencias</div><div class="inst-stat-lbl">Registrar</div></div>
        </a>
        <a href="<?= htmlspecialchars(app_url('mod/evidencias.php')) ?>" class="inst-stat">
            <div class="inst-stat-icon" style="background:linear-gradient(135deg,#8b5cf6,#7c3aed);"><i class="fas fa-file-alt"></i></div>
            <div><div class="inst-stat-val" style="font-size:15px;">Evidencias</div><div class="inst-stat-lbl">Calificar</div></div>
        </a>
        <!-- BOTÓN NUEVO: Justificaciones con contador de pendientes -->
        <a href="<?= htmlspecialchars(app_url('mod/instructor_justificaciones.php')) ?>" class="inst-stat">
            <div class="inst-stat-icon" style="background:linear-gradient(135deg,#f59e0b,#ea580c);"><i class="fas fa-file-signature"></i></div>
            <div>
                <div class="inst-stat-val" style="font-size:15px;">Justificaciones</div>
                <div class="inst-stat-lbl">
                    Pendientes: 
                    <?php if ($justificacionesPendientes > 0): ?>
                        <span style="background:#dc2626; color:white; padding:2px 8px; border-radius:20px; font-size:11px;"><?= $justificacionesPendientes ?></span>
                    <?php else: ?>
                        <span>0</span>
                    <?php endif; ?>
                </div>
            </div>
        </a>
        <!-- Fin botón -->
        <a href="<?= htmlspecialchars(app_url('mod/alertas.php'.($instId?('?instructor_id='.$instId):''))) ?>" class="inst-stat">
            <div class="inst-stat-icon" style="background:linear-gradient(135deg,#dc2626,#b91c1c);"><i class="fas fa-bell"></i></div>
            <div><div class="inst-stat-val" style="font-size:15px;">Alertas</div><div class="inst-stat-lbl">Gestionar</div></div>
        </a>
    </div>

</main>

<?php include '../config/footer.php'; ?>
<script src="<?= htmlspecialchars(asset_url('js/tema.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/loader.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/panel_menu.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/dropdowns.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/profile_menu.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/sweetalerts.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/menu.js')) ?>"></script>
</body>
</html>