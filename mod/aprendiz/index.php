<?php
// mod/aprendiz/index.php
session_start();
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/app.php';

if (!esAprendiz()) {
    redirect_to('login.php');
}

require_once __DIR__ . '/../../conexion/AprendizDAO.php';
require_once __DIR__ . '/../../conexion/AsistenciaDAO.php';

$aprendizDAO   = new AprendizDAO();
$asistenciaDAO = new AsistenciaDAO();

$aprendizId = (int)($_SESSION['usuario_ref_id'] ?? 0);
$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
$usuarioEmail = $_SESSION['usuario_email'] ?? '';
$aprendiz = $aprendizDAO->obtenerPorSesion($aprendizId, $usuarioId, $usuarioEmail);

$resumen  = $aprendiz ? $asistenciaDAO->obtenerResumenAprendiz($aprendiz['APRENDIZ_ID']) : [];
$pct      = ($resumen['total_dias'] ?? 0) > 0
    ? round(($resumen['dias_asistidos'] / $resumen['total_dias']) * 100, 1)
    : 0;
$promedio   = $aprendiz ? $aprendizDAO->obtenerPromedioEvidencias($aprendiz['APRENDIZ_ID'])   : 0;
$aprobadas  = $aprendiz ? $aprendizDAO->obtenerEvidenciasAprobadas($aprendiz['APRENDIZ_ID'])  : 0;
$pendientes = $aprendiz ? $aprendizDAO->obtenerEvidenciasPendientes($aprendiz['APRENDIZ_ID']) : 0;
$pctColor   = $pct >= 80 ? '#16a34a' : ($pct >= 60 ? '#f59e0b' : '#dc2626');

$nombre = $aprendiz
    ? htmlspecialchars($aprendiz['NOMBRES'] . ' ' . $aprendiz['APELLIDOS'])
    : htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Aprendiz');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Aprendiz — DTD SENA</title>
<link rel="stylesheet" href="<?= htmlspecialchars(asset_url('css/style.css')) ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
.pct-bar-bg   { height:10px; background:var(--color-gris-fondo); border-radius:20px; overflow:hidden; }
.pct-bar-fill { height:100%; border-radius:20px; transition:width .8s; }
.quick-links  { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:14px; margin-top:24px; }
.quick-btn {
    display:flex; flex-direction:column; align-items:center; gap:8px;
    padding:20px 12px; border-radius:16px; text-decoration:none;
    background:var(--color-blanco); border:1px solid var(--border-color);
    box-shadow:var(--shadow-card); transition:.25s; color:var(--color-texto);
    font-size:13px; font-weight:700; text-align:center;
}
.quick-btn i  { font-size:24px; }
.quick-btn:hover { transform:translateY(-4px); border-color:var(--color-verde-1); color:var(--color-verde-1); }
.dark-mode .quick-btn { background:var(--color-gris-cuerpo); }
</style>
</head>
<body>
<div id="loader"><img src="<?= htmlspecialchars(asset_url('img/logo_sena_verde.png')) ?>" alt="" id="loader-logo"></div>
<?php include __DIR__ . '/../../config/header.php'; ?>

<main class="container" id="contenido-principal" style="display:none;opacity:0;">

    <!-- Hero -->
    <div class="content-card" style="background:linear-gradient(135deg,#39a900,#2d8a00);margin-bottom:28px;color:#fff;border-radius:24px;padding:30px;">
        <h1 style="font-size:28px;font-weight:800;margin-bottom:6px;"><?= $nombre ?></h1>
        <?php if ($aprendiz): ?>
        <p style="opacity:.85;margin-bottom:14px;">
            <i class="fas fa-id-card"></i>
            <?= htmlspecialchars(($aprendiz['TIPO_DOCUMENTO'] ?? 'CC') . ' ' . $aprendiz['NUMERO_DOCUMENTO']) ?>
        </p>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <span class="badge"><i class="fas fa-layer-group"></i> <?= htmlspecialchars($aprendiz['CODIGO_FICHA'] ?? 'Sin ficha') ?></span>
            <span class="badge"><i class="fas fa-graduation-cap"></i> <?= htmlspecialchars($aprendiz['programa_nombre'] ?? '—') ?></span>
            <span class="badge"><i class="fas fa-check-circle" style="color:#4ade80;"></i> <?= htmlspecialchars($aprendiz['ESTADO_ACADEMICO'] ?? 'Activo') ?></span>
        </div>
        <?php else: ?>
        <p style="opacity:.7;margin-top:8px;"><i class="fas fa-info-circle"></i> Cuenta activa — contacta al administrador para vincular tu ficha.</p>
        <?php endif; ?>
    </div>

    <!-- Stats -->
    <div class="stats-grid" style="margin-bottom:28px;">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-star"></i></div>
            <div class="stat-content">
                <span class="stat-value"><?= number_format((float)$promedio, 1) ?></span>
                <span class="stat-label">Promedio</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
            <div class="stat-content">
                <span class="stat-value" style="color:<?= $pctColor ?>;"><?= $pct ?>%</span>
                <span class="stat-label">Asistencia</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-content">
                <span class="stat-value"><?= (int)$aprobadas ?></span>
                <span class="stat-label">Aprobadas</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-content">
                <span class="stat-value"><?= (int)$pendientes ?></span>
                <span class="stat-label">Pendientes</span>
            </div>
        </div>
    </div>

    <?php if ($aprendiz): ?>
    <!-- Barra asistencia -->
    <div style="background:var(--color-blanco);border:1px solid var(--border-color);border-radius:16px;padding:20px;margin-bottom:28px;box-shadow:var(--shadow-card);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
            <span style="font-weight:700;color:<?= $pctColor ?>;"><i class="fas fa-chart-pie"></i> Asistencia</span>
            <strong style="font-size:22px;color:<?= $pctColor ?>;"><?= $pct ?>%</strong>
        </div>
        <div class="pct-bar-bg">
            <div class="pct-bar-fill" style="width:<?= min($pct,100) ?>%;background:<?= $pctColor ?>;"></div>
        </div>
        <div style="display:flex;gap:14px;flex-wrap:wrap;margin-top:10px;font-size:12px;color:var(--color-texto-secundario);">
            <span><span style="color:#16a34a;font-weight:700;"><?= (int)($resumen['dias_asistidos'] ?? 0) ?></span> presentes</span>
            <span><span style="color:#f59e0b;font-weight:700;"><?= (int)($resumen['dias_retardo']   ?? 0) ?></span> retardos</span>
            <span><span style="color:#dc2626;font-weight:700;"><?= (int)($resumen['dias_falta']     ?? 0) ?></span> faltas</span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Accesos rápidos -->
    <h2 style="font-size:18px;font-weight:800;margin-bottom:4px;">
        <i class="fas fa-bolt" style="color:var(--color-verde-1);"></i> Accesos Rápidos
    </h2>
    <div class="quick-links">
    <a href="<?= htmlspecialchars(app_url('mod/aprendiz_perfil.php')) ?>" class="quick-btn">
        <i class="fas fa-user-circle" style="color:var(--color-verde-1);"></i> Mi Perfil
    </a>
    <a href="<?= htmlspecialchars(app_url('mod/aprendiz/horario.php')) ?>" class="quick-btn">
        <i class="fas fa-calendar-week" style="color:#8b5cf6;"></i> Mi Horario
    </a>
    <a href="<?= htmlspecialchars(app_url('mod/aprendiz/asistencia.php')) ?>" class="quick-btn">
        <i class="fas fa-calendar-check" style="color:#3b82f6;"></i> Asistencias
    </a>
    <a href="<?= htmlspecialchars(app_url('mod/aprendiz/evidencias.php')) ?>" class="quick-btn">
        <i class="fas fa-file-alt" style="color:#f59e0b;"></i> Evidencias
    </a>
    <a href="<?= htmlspecialchars(app_url('mod/aprendiz_faltas.php')) ?>" class="quick-btn">
        <i class="fas fa-calendar-times" style="color:#dc2626;"></i> Mis faltas
    </a>
</div>

</main>

<?php include __DIR__ . '/../../config/footer.php'; ?>
<script src="<?= htmlspecialchars(asset_url('js/tema.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/loader.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/panel_menu.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/dropdowns.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/profile_menu.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/menu.js')) ?>"></script>
</body>
</html>