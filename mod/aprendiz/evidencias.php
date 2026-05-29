<?php
// mod/aprendiz/evidencias.php — Vista de evidencias del aprendiz
session_start();
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../conexion/conexion.php';

if (!esAprendiz()) redirect_to('login.php');

class EvidenciasAprendizDB extends BaseDatos {
    protected function consultar(){}protected function insertar(){}
    protected function actualizar(){}protected function eliminar(){}
    public function uno($sql,$p=[]){ $s=$this->ejecutarPreparado($sql,$p); return $s?$s->fetch(PDO::FETCH_ASSOC):[]; }
    public function varios($sql,$p=[]){ $s=$this->ejecutarPreparado($sql,$p); return $s?$s->fetchAll(PDO::FETCH_ASSOC):[]; }
}
$db  = new EvidenciasAprendizDB();
$ref = (int)($_SESSION['usuario_ref_id'] ?? 0);
$uid = (int)($_SESSION['usuario_id']     ?? 0);

// Obtener aprendiz
$aprendiz = $ref ? $db->uno("SELECT APRENDIZ_ID, NOMBRES, APELLIDOS, FICHA_ID FROM aprendiz WHERE APRENDIZ_ID=:id",[':id'=>$ref]) : null;
if (!$aprendiz && $uid) $aprendiz = $db->uno("SELECT APRENDIZ_ID, NOMBRES, APELLIDOS, FICHA_ID FROM aprendiz WHERE usuario_id=:id",[':id'=>$uid]);

$aid     = $aprendiz ? (int)$aprendiz['APRENDIZ_ID'] : 0;
$filtro  = $_GET['estado'] ?? 'todos';

// Cargar evidencias
$evidencias = $aid ? $db->varios(
    "SELECT e.*, i.NOMBRES AS inst_n, i.APELLIDOS AS inst_a
     FROM evidencia e
     LEFT JOIN instructor i ON e.INSTRUCTOR_ID = i.INSTRUCTOR_ID
     WHERE e.APRENDIZ_ID = :aid
     ORDER BY e.FECHA_LIMITE DESC",
    [':aid' => $aid]
) : [];

// Stats
$total       = count($evidencias);
$calificadas = count(array_filter($evidencias, fn($e) => $e['ESTADO']==='Calificada'));
$entregadas  = count(array_filter($evidencias, fn($e) => $e['ESTADO']==='Entregada'));
$pendientes  = count(array_filter($evidencias, fn($e) => $e['ESTADO']==='Pendiente'));
$no_ent      = count(array_filter($evidencias, fn($e) => $e['ESTADO']==='No Entregada'));
$promedioCalif = 0;
$ev_calif = array_filter($evidencias, fn($e) => $e['ESTADO']==='Calificada' && $e['CALIFICACION'] !== null);
if ($ev_calif) $promedioCalif = round(array_sum(array_column(array_values($ev_calif),'CALIFICACION')) / count($ev_calif), 1);

// Filtrar para la vista
$ev_vista = match($filtro) {
    'calificadas'  => array_values(array_filter($evidencias, fn($e) => $e['ESTADO']==='Calificada')),
    'pendientes'   => array_values(array_filter($evidencias, fn($e) => $e['ESTADO']==='Pendiente')),
    'no_entregadas'=> array_values(array_filter($evidencias, fn($e) => $e['ESTADO']==='No Entregada')),
    'entregadas'   => array_values(array_filter($evidencias, fn($e) => $e['ESTADO']==='Entregada')),
    default        => $evidencias,
};

function estadoBadge($estado, $calif=null) {
    return match($estado) {
        'Calificada'   => "<span class='ev-badge ev-badge--ok'><i class='fas fa-check-circle'></i> Calificada ".($calif!==null?"($calif)":"")."</span>",
        'Entregada'    => "<span class='ev-badge ev-badge--ent'><i class='fas fa-paper-plane'></i> Entregada</span>",
        'Pendiente'    => "<span class='ev-badge ev-badge--pend'><i class='fas fa-clock'></i> Pendiente</span>",
        'No Entregada' => "<span class='ev-badge ev-badge--no'><i class='fas fa-times-circle'></i> No Entregada</span>",
        default        => "<span class='ev-badge'>$estado</span>",
    };
}
function notaColor($n) {
    if ($n===null) return 'var(--color-texto-secundario)';
    if ($n>=4.0) return '#16a34a';
    if ($n>=3.0) return '#f59e0b';
    return '#dc2626';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Mis Evidencias — DTD SENA</title>
<link rel="stylesheet" href="<?= htmlspecialchars(asset_url('css/style.css')) ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* Stats */
.ev-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:14px;margin-bottom:24px;}
.ev-stat{background:var(--color-blanco);border:1px solid var(--border-color);border-radius:14px;padding:16px;text-align:center;box-shadow:var(--shadow-card);}
.ev-stat-val{font-size:28px;font-weight:800;line-height:1;}
.ev-stat-lbl{font-size:11px;color:var(--color-texto-secundario);font-weight:700;text-transform:uppercase;margin-top:4px;}
/* Filtros */
.ev-filtros{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;}
.ev-fil{padding:7px 14px;border-radius:20px;font-size:12px;font-weight:700;text-decoration:none;border:2px solid var(--border-color);color:var(--color-texto-secundario);transition:.2s;}
.ev-fil.active{background:var(--color-verde-1);color:#fff;border-color:var(--color-verde-1);}
.ev-fil:hover:not(.active){border-color:var(--color-verde-1);color:var(--color-verde-1);}
.ev-fil--no.active{background:#dc2626;border-color:#dc2626;}
.ev-fil--pend.active{background:#f59e0b;border-color:#f59e0b;}
/* Cards */
.ev-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;}
.ev-card{background:var(--color-blanco);border:1px solid var(--border-color);border-radius:16px;overflow:hidden;box-shadow:var(--shadow-card);transition:.25s;}
.ev-card:hover{transform:translateY(-3px);}
.ev-card--no{border-left:4px solid #dc2626;}
.ev-card--pend{border-left:4px solid #f59e0b;}
.ev-card--ok{border-left:4px solid #16a34a;}
.ev-card--ent{border-left:4px solid #3b82f6;}
.ev-card-head{padding:14px 16px 10px;border-bottom:1px solid var(--border-color);}
.ev-titulo{font-size:13px;font-weight:700;color:var(--color-texto);margin-bottom:6px;line-height:1.3;}
.ev-card-body{padding:12px 16px;}
.ev-row{display:flex;align-items:center;gap:8px;font-size:12px;color:var(--color-texto-secundario);margin-bottom:5px;}
.ev-row i{width:14px;text-align:center;color:var(--color-verde-1);}
.ev-nota{font-size:26px;font-weight:800;text-align:center;padding:10px 0 4px;}
.ev-badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;}
.ev-badge--ok  {background:rgba(22,163,74,.1);color:#15803d;}
.ev-badge--ent {background:rgba(59,130,246,.1);color:#1d4ed8;}
.ev-badge--pend{background:rgba(245,158,11,.1);color:#b45309;}
.ev-badge--no  {background:rgba(220,38,38,.1);color:#b91c1c;}
.ev-obs{font-size:11px;color:var(--color-texto-secundario);background:var(--color-gris-fondo);border-radius:6px;padding:7px 10px;margin-top:8px;line-height:1.5;}
.alert-banner{background:rgba(220,38,38,.08);border:1px solid rgba(220,38,38,.25);border-radius:12px;padding:12px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px;font-size:13px;color:#991b1b;}
.dark-mode .ev-card,.dark-mode .ev-stat{background:var(--color-gris-cuerpo);}
</style>
</head>
<body>
<div id="loader"><img src="<?= htmlspecialchars(asset_url('img/logo_sena_verde.png')) ?>" alt="" id="loader-logo"></div>
<?php include __DIR__ . '/../../config/header.php'; ?>
<main class="container" id="contenido-principal" style="display:none;opacity:0;">

<div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;flex-wrap:wrap;">
    <a href="<?= htmlspecialchars(app_url('mod/aprendiz/index.php')) ?>" class="btn-view-all"><i class="fas fa-arrow-left"></i> Dashboard</a>
    <span style="font-size:13px;color:var(--color-texto-secundario);"><i class="fas fa-file-alt"></i> Mis Evidencias</span>
</div>

<?php if (!$aid): ?>
<div style="text-align:center;padding:60px 20px;background:var(--color-blanco);border-radius:18px;border:2px dashed var(--border-color);">
    <i class="fas fa-user-slash" style="font-size:48px;opacity:.2;display:block;margin-bottom:12px;"></i>
    <p style="color:var(--color-texto-secundario);">No se encontró tu perfil de aprendiz.</p>
</div>
<?php else: ?>

<?php if ($no_ent > 0 || $pendientes > 0): ?>
<div class="alert-banner">
    <i class="fas fa-exclamation-triangle" style="font-size:18px;flex-shrink:0;"></i>
    <span>
        <?php if ($no_ent > 0): ?>Tienes <strong><?= $no_ent ?> evidencia(s) no entregada(s)</strong>. <?php endif; ?>
        <?php if ($pendientes > 0): ?>Tienes <strong><?= $pendientes ?> evidencia(s) pendiente(s)</strong>.<?php endif; ?>
        Comunícate con tu instructor.
    </span>
</div>
<?php endif; ?>

<!-- Stats -->
<div class="ev-stats">
    <div class="ev-stat">
        <div class="ev-stat-val" style="color:var(--color-verde-1);"><?= $total ?></div>
        <div class="ev-stat-lbl">Total</div>
    </div>
    <div class="ev-stat">
        <div class="ev-stat-val" style="color:#16a34a;"><?= $calificadas ?></div>
        <div class="ev-stat-lbl">Calificadas</div>
    </div>
    <div class="ev-stat">
        <div class="ev-stat-val" style="color:#3b82f6;"><?= $entregadas ?></div>
        <div class="ev-stat-lbl">Entregadas</div>
    </div>
    <div class="ev-stat">
        <div class="ev-stat-val" style="color:#f59e0b;"><?= $pendientes ?></div>
        <div class="ev-stat-lbl">Pendientes</div>
    </div>
    <div class="ev-stat">
        <div class="ev-stat-val" style="color:#dc2626;"><?= $no_ent ?></div>
        <div class="ev-stat-lbl">No Entregadas</div>
    </div>
    <div class="ev-stat">
        <div class="ev-stat-val" style="color:<?= notaColor($promedioCalif ?: null) ?>;"><?= $promedioCalif ?: '—' ?></div>
        <div class="ev-stat-lbl">Promedio</div>
    </div>
</div>

<!-- Filtros -->
<div class="ev-filtros">
    <a href="?estado=todos" class="ev-fil <?= $filtro==='todos'?'active':'' ?>"><i class="fas fa-list"></i> Todas (<?= $total ?>)</a>
    <a href="?estado=calificadas" class="ev-fil <?= $filtro==='calificadas'?'active':'' ?>"><i class="fas fa-check-circle"></i> Calificadas (<?= $calificadas ?>)</a>
    <a href="?estado=entregadas" class="ev-fil <?= $filtro==='entregadas'?'active':'' ?>"><i class="fas fa-paper-plane"></i> Entregadas (<?= $entregadas ?>)</a>
    <a href="?estado=pendientes" class="ev-fil ev-fil--pend <?= $filtro==='pendientes'?'active':'' ?>"><i class="fas fa-clock"></i> Pendientes (<?= $pendientes ?>)</a>
    <a href="?estado=no_entregadas" class="ev-fil ev-fil--no <?= $filtro==='no_entregadas'?'active':'' ?>"><i class="fas fa-times-circle"></i> No Entregadas (<?= $no_ent ?>)</a>
</div>

<?php if (empty($ev_vista)): ?>
<div style="text-align:center;padding:40px;background:var(--color-blanco);border-radius:14px;border:2px dashed var(--border-color);">
    <i class="fas fa-file-circle-check" style="font-size:40px;opacity:.2;display:block;margin-bottom:10px;"></i>
    <p style="color:var(--color-texto-secundario);font-size:13px;">No hay evidencias en esta categoría.</p>
</div>
<?php else: ?>
<div class="ev-grid">
<?php foreach ($ev_vista as $ev): ?>
<?php
    $cls = match($ev['ESTADO']) {
        'No Entregada' => 'ev-card--no',
        'Pendiente'    => 'ev-card--pend',
        'Calificada'   => 'ev-card--ok',
        default        => 'ev-card--ent',
    };
    $vencida = $ev['ESTADO']==='Pendiente' && $ev['FECHA_LIMITE'] < date('Y-m-d');
?>
<div class="ev-card <?= $cls ?>">
    <div class="ev-card-head">
        <div class="ev-titulo"><?= htmlspecialchars($ev['TITULO']) ?></div>
        <?= estadoBadge($ev['ESTADO'], $ev['CALIFICACION']) ?>
        <?php if ($vencida): ?>
        <span class="ev-badge ev-badge--no" style="margin-left:4px;"><i class="fas fa-exclamation"></i> Vencida</span>
        <?php endif; ?>
    </div>
    <div class="ev-card-body">
        <?php if ($ev['ESTADO']==='Calificada' && $ev['CALIFICACION']!==null): ?>
        <div class="ev-nota" style="color:<?= notaColor((float)$ev['CALIFICACION']) ?>;">
            <?= number_format((float)$ev['CALIFICACION'],1) ?>
            <span style="font-size:14px;color:var(--color-texto-secundario);font-weight:400;">/5.0</span>
        </div>
        <?php endif; ?>
        <div class="ev-row"><i class="fas fa-tag"></i><?= htmlspecialchars($ev['TIPO']) ?></div>
        <div class="ev-row"><i class="fas fa-calendar-times"></i>Límite: <?= date('d/m/Y',strtotime($ev['FECHA_LIMITE'])) ?></div>
        <?php if ($ev['FECHA_ENTREGA']): ?>
        <div class="ev-row"><i class="fas fa-paper-plane"></i>Entregado: <?= date('d/m/Y',strtotime($ev['FECHA_ENTREGA'])) ?></div>
        <?php endif; ?>
        <?php if (!empty($ev['inst_n'])): ?>
        <div class="ev-row"><i class="fas fa-chalkboard-teacher"></i><?= htmlspecialchars($ev['inst_n'].' '.$ev['inst_a']) ?></div>
        <?php endif; ?>
        <?php if (!empty(trim($ev['OBSERVACION']))): ?>
        <div class="ev-obs"><i class="fas fa-comment-alt" style="margin-right:4px;"></i><?= htmlspecialchars($ev['OBSERVACION']) ?></div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php endif; ?>

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