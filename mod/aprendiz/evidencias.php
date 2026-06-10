<?php
// mod/aprendiz/evidencias.php
session_start();
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../conexion/conexion.php';

if (!esAprendiz()) redirect_to('login.php');

class EvidenciasAprendizDB extends BaseDatos {
    protected function consultar(){}protected function insertar(){}
    protected function actualizar(){}protected function eliminar(){}
    public function uno($sql,$p=[]){ $s=$this->ejecutarPreparado($sql,$p); return $s?$s->fetch(PDO::FETCH_ASSOC):null; }
    public function varios($sql,$p=[]){ $s=$this->ejecutarPreparado($sql,$p); return $s?$s->fetchAll(PDO::FETCH_ASSOC):[]; }
}
$db  = new EvidenciasAprendizDB();
$uid = (int)($_SESSION['usuario_id']     ?? 0);
$ref = (int)($_SESSION['usuario_ref_id'] ?? 0);

// Obtener aprendiz
$aprendiz = $ref
    ? $db->uno("SELECT a.*, f.CODIGO_FICHA, f.FICHA_ID FROM aprendiz a LEFT JOIN ficha f ON f.FICHA_ID=a.FICHA_ID WHERE a.APRENDIZ_ID=:id",[':id'=>$ref])
    : null;
if (!$aprendiz && $uid) {
    $aprendiz = $db->uno("SELECT a.*, f.CODIGO_FICHA, f.FICHA_ID FROM aprendiz a LEFT JOIN ficha f ON f.FICHA_ID=a.FICHA_ID WHERE a.usuario_id=:id",[':id'=>$uid]);
}
$aid  = $aprendiz ? (int)$aprendiz['APRENDIZ_ID']  : 0;
$fid  = $aprendiz ? (int)$aprendiz['FICHA_ID']     : 0;

// ── Evidencias del catálogo de la ficha + calificación del aprendiz ──────
$catalogoCalif = $fid && $aid ? $db->varios(
    "SELECT e.evidencias_id, e.nombre, e.tipo_evidencia, e.porcentaje,
            e.fecha_evidencia, e.tiempo_entrega, e.estado_evidencia,
            ce.calificacion_id, ce.calificacion, ce.estado_aprobacion
     FROM evidencias e
     LEFT JOIN calificaciones_evidencias ce
           ON ce.evidencia_id = e.evidencias_id AND ce.aprendiz_id = :aid
     WHERE e.ficha_id = :fid
     ORDER BY e.fecha_evidencia ASC",
    [':fid'=>$fid, ':aid'=>$aid]
) : [];

// ── Evidencias individuales (entregas libres) ────────────────────────────
$individuales = $aid ? $db->varios(
    "SELECT * FROM evidencia WHERE APRENDIZ_ID=:aid ORDER BY FECHA_LIMITE ASC",
    [':aid'=>$aid]
) : [];

// ── Estadísticas combinadas ──────────────────────────────────────────────
$totalCat     = count($catalogoCalif);
$calificadas  = 0; $notas = [];
foreach ($catalogoCalif as $ev) {
    if ($ev['calificacion'] !== null) { $calificadas++; $notas[] = (float)$ev['calificacion']; }
}
$sinCalificar  = $totalCat - $calificadas;
$promedioCat   = count($notas) ? round(array_sum($notas)/count($notas), 2) : null;

$totalInd  = count($individuales);
$notasInd  = array_filter(array_column($individuales,'CALIFICACION'), fn($v)=>$v!==null);
$promedioInd = count($notasInd) ? round(array_sum($notasInd)/count($notasInd),2) : null;

$notasTotales = array_merge($notas, array_values($notasInd));
$promedioGlobal = count($notasTotales) ? round(array_sum($notasTotales)/count($notasTotales),2) : null;

function colorNota($n) {
    if ($n === null) return '#94a3b8';
    if ($n >= 4.0) return '#16a34a';
    if ($n >= 3.0) return '#f59e0b';
    return '#dc2626';
}
function labelEstado($est) {
    return match($est) {
        'Calificada'   => ['Calificada','#16a34a','rgba(22,163,74,.1)','fa-check-circle'],
        'Entregada'    => ['En revisión','#3b82f6','rgba(59,130,246,.1)','fa-clock'],
        'Pendiente'    => ['Pendiente','#f59e0b','rgba(245,158,11,.1)','fa-hourglass-half'],
        'No Entregada' => ['No entregada','#dc2626','rgba(220,38,38,.1)','fa-times-circle'],
        default        => [$est,'#94a3b8','rgba(148,163,184,.1)','fa-circle'],
    };
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Mis Evidencias — DTD SENA</title>
<link rel="stylesheet" href="<?= asset_url('css/style.css') ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* ── PROMEDIO HERO ── */
.prom-hero{display:grid;grid-template-columns:auto 1fr;gap:20px;align-items:center;background:var(--color-blanco);border:1px solid var(--border-color);border-radius:18px;padding:24px 28px;margin-bottom:22px;box-shadow:var(--shadow-card);}
.prom-circle{width:90px;height:90px;border-radius:50%;display:flex;flex-direction:column;align-items:center;justify-content:center;font-weight:800;border:4px solid;}
.prom-val{font-size:28px;line-height:1;}
.prom-lbl{font-size:10px;text-transform:uppercase;letter-spacing:.5px;margin-top:2px;}
.prom-info h3{font-size:18px;font-weight:700;margin-bottom:6px;}
.prom-info p{font-size:13px;color:var(--color-texto-secundario);margin:0;}
.prom-chips{display:flex;gap:10px;margin-top:10px;flex-wrap:wrap;}
.prom-chip{padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;display:flex;align-items:center;gap:5px;}
/* ── STATS ── */
.ev-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:12px;margin-bottom:22px;}
.ev-stat{background:var(--color-blanco);border:1px solid var(--border-color);border-radius:14px;padding:16px 12px;text-align:center;box-shadow:var(--shadow-card);}
.ev-stat-val{font-size:28px;font-weight:800;line-height:1;margin-bottom:3px;}
.ev-stat-lbl{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--color-texto-secundario);}
/* ── SECTION TITLE ── */
.sec-title{font-size:16px;font-weight:700;display:flex;align-items:center;gap:8px;margin:24px 0 14px;padding-bottom:8px;border-bottom:2px solid var(--border-color);}
.sec-title i{color:var(--color-verde-1);}
/* ── CARD EVIDENCIA ── */
.ev-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:14px;margin-bottom:10px;}
.ev-card{background:var(--color-blanco);border:1px solid var(--border-color);border-radius:14px;overflow:hidden;box-shadow:var(--shadow-card);transition:.2s;}
.ev-card:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(0,0,0,.1);}
.ev-card-head{padding:14px 16px 10px;border-bottom:1px solid var(--border-color);display:flex;align-items:flex-start;justify-content:space-between;gap:10px;}
.ev-card-titulo{font-size:13.5px;font-weight:700;line-height:1.3;flex:1;}
.ev-card-tipo{font-size:10px;font-weight:700;padding:2px 8px;border-radius:10px;background:var(--color-gris-fondo);color:var(--color-texto-secundario);white-space:nowrap;}
.ev-card-body{padding:12px 16px;}
.ev-row{display:flex;justify-content:space-between;align-items:center;font-size:12.5px;padding:5px 0;border-bottom:1px solid var(--border-color);}
.ev-row:last-child{border-bottom:none;}
.ev-row-lbl{color:var(--color-texto-secundario);}
.ev-row-val{font-weight:700;}
/* ── NOTA GRANDE ── */
.nota-grande{display:inline-flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:50%;font-size:14px;font-weight:800;border:2px solid;}
/* ── SIN CALIFICAR BADGE ── */
.sin-cal{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700;}
/* ── ALERTA BANNER ── */
.banner-alerta{background:rgba(220,38,38,.07);border:1px solid rgba(220,38,38,.25);border-left:4px solid #dc2626;border-radius:0 10px 10px 0;padding:12px 16px;margin-bottom:18px;font-size:13.5px;display:flex;align-items:center;gap:10px;color:#991b1b;}
.banner-ok{background:rgba(22,163,74,.07);border:1px solid rgba(22,163,74,.25);border-left:4px solid #16a34a;border-radius:0 10px 10px 0;padding:12px 16px;margin-bottom:18px;font-size:13.5px;display:flex;align-items:center;gap:10px;color:#15803d;}
/* ── VENCIDA ── */
.ev-card.vencida .ev-card-head{background:rgba(220,38,38,.04);}
/* ── TABS ── */
.tabs{display:flex;gap:6px;margin-bottom:16px;flex-wrap:wrap;}
.tab-btn{padding:7px 16px;border-radius:20px;border:1px solid var(--border-color);background:var(--color-blanco);font-size:12px;font-weight:700;cursor:pointer;transition:.15s;color:var(--color-texto-secundario);}
.tab-btn.active,.tab-btn:hover{background:var(--color-verde-1);color:#fff;border-color:var(--color-verde-1);}
.ev-card.hidden{display:none;}
/* ── VACIO ── */
.vacio{text-align:center;padding:44px 20px;background:var(--color-blanco);border-radius:16px;border:2px dashed var(--border-color);}
.vacio i{font-size:40px;opacity:.18;display:block;margin-bottom:10px;}
</style>
</head>
<body>
<div id="loader"><img src="<?= asset_url('img/logo_sena_verde.png') ?>" alt="" id="loader-logo"></div>
<?php include __DIR__ . '/../../config/header.php'; ?>
<main class="container" id="contenido-principal" style="display:none;opacity:0;">

<!-- Breadcrumb -->
<div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;flex-wrap:wrap;">
    <a href="<?= app_url('mod/aprendiz/index.php') ?>" class="btn-view-all">
        <i class="fas fa-arrow-left"></i> Dashboard
    </a>
    <span style="font-size:13px;color:var(--color-texto-secundario);">
        <i class="fas fa-file-alt"></i> Mis Evidencias
    </span>
    <?php if ($aprendiz): ?>
    <span style="margin-left:auto;font-size:12px;color:var(--color-texto-secundario);">
        Ficha: <strong><?= htmlspecialchars($aprendiz['CODIGO_FICHA'] ?? '—') ?></strong>
    </span>
    <?php endif; ?>
</div>

<?php if (!$aid): ?>
<div class="vacio"><i class="fas fa-user-slash"></i><p>No se encontró tu perfil. Contacta al administrador.</p></div>
<?php else: ?>

<?php
// ── BANNER ────────────────────────────────────────────────────────────────
$noEntregadas = count(array_filter($individuales, fn($e)=>$e['ESTADO']==='No Entregada'));
$pendientes   = count(array_filter($individuales, fn($e)=>$e['ESTADO']==='Pendiente'));
$vencidas     = count(array_filter($individuales, fn($e)=>
    $e['ESTADO']==='Pendiente' && $e['FECHA_LIMITE'] && $e['FECHA_LIMITE'] < date('Y-m-d')
));
if ($noEntregadas > 0 || $vencidas > 0): ?>
<div class="banner-alerta">
    <i class="fas fa-exclamation-triangle" style="font-size:18px;flex-shrink:0;"></i>
    <span>
        Tienes <?= $noEntregadas + $vencidas ?> evidencia<?= ($noEntregadas+$vencidas)>1?'s':'' ?>
        <?php if ($noEntregadas) echo "<strong>$noEntregadas no entregada".($noEntregadas>1?'s':'')."</strong>"; ?>
        <?php if ($noEntregadas && $vencidas) echo ' y '; ?>
        <?php if ($vencidas) echo "<strong>$vencidas vencida".($vencidas>1?'s':'')."</strong>"; ?>.
        Habla con tu instructor.
    </span>
</div>
<?php elseif ($sinCalificar === 0 && $totalCat > 0): ?>
<div class="banner-ok">
    <i class="fas fa-check-circle" style="font-size:18px;flex-shrink:0;"></i>
    <strong>¡Todas tus evidencias del catálogo han sido calificadas!</strong>
</div>
<?php endif; ?>

<!-- PROMEDIO HERO -->
<?php
$pc = colorNota($promedioGlobal);
$ap = $promedioGlobal !== null ? ($promedioGlobal >= 3.0 ? 'Aprobado' : 'Reprobado') : 'Sin calificar';
$apColor = $promedioGlobal !== null ? ($promedioGlobal >= 3.0 ? '#16a34a' : '#dc2626') : '#94a3b8';
?>
<div class="prom-hero">
    <div class="prom-circle" style="color:<?= $pc ?>;border-color:<?= $pc ?>;">
        <span class="prom-val"><?= $promedioGlobal ?? '—' ?></span>
        <span class="prom-lbl" style="color:var(--color-texto-secundario)">/ 5.0</span>
    </div>
    <div class="prom-info">
        <h3>Promedio General</h3>
        <p>Calculado sobre <?= count($notasTotales) ?> evidencia<?= count($notasTotales)!=1?'s':'' ?> calificada<?= count($notasTotales)!=1?'s':'' ?>.</p>
        <div class="prom-chips">
            <span class="prom-chip" style="background:<?= $apColor ?>;color:#fff;">
                <i class="fas fa-<?= $promedioGlobal >= 3.0 ? 'check' : 'times' ?>"></i> <?= $ap ?>
            </span>
            <?php if ($sinCalificar > 0): ?>
            <span class="prom-chip" style="background:rgba(245,158,11,.15);color:#92400e;">
                <i class="fas fa-clock"></i> <?= $sinCalificar ?> por calificar
            </span>
            <?php endif; ?>
            <?php if ($promedioCat !== null): ?>
            <span class="prom-chip" style="background:rgba(57,169,0,.12);color:var(--color-verde-2);">
                <i class="fas fa-clipboard-list"></i> Catálogo: <?= $promedioCat ?>
            </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- STATS -->
<div class="ev-stats">
    <div class="ev-stat">
        <div class="ev-stat-val" style="color:var(--color-verde-1)"><?= $totalCat ?></div>
        <div class="ev-stat-lbl">Catálogo</div>
    </div>
    <div class="ev-stat">
        <div class="ev-stat-val" style="color:#16a34a"><?= $calificadas ?></div>
        <div class="ev-stat-lbl">Calificadas</div>
    </div>
    <div class="ev-stat">
        <div class="ev-stat-val" style="color:#f59e0b"><?= $sinCalificar ?></div>
        <div class="ev-stat-lbl">Por calificar</div>
    </div>
    <div class="ev-stat">
        <div class="ev-stat-val" style="color:#3b82f6"><?= $totalInd ?></div>
        <div class="ev-stat-lbl">Entregas</div>
    </div>
    <div class="ev-stat">
        <div class="ev-stat-val" style="color:#dc2626"><?= $noEntregadas ?></div>
        <div class="ev-stat-lbl">No entregadas</div>
    </div>
    <div class="ev-stat">
        <div class="ev-stat-val" style="color:var(--color-texto)" style="font-size:22px;">
            <?= $promedioGlobal ?? '—' ?>
        </div>
        <div class="ev-stat-lbl">Promedio</div>
    </div>
</div>

<!-- ── CATÁLOGO DE EVIDENCIAS ────────────────────────────────────────── -->
<?php if (!empty($catalogoCalif)): ?>
<div class="sec-title">
    <i class="fas fa-clipboard-list"></i>
    Evidencias del Catálogo
    <span style="margin-left:auto;font-size:12px;font-weight:400;color:var(--color-texto-secundario);">
        <?= $calificadas ?>/<?= $totalCat ?> calificadas
    </span>
</div>

<div class="ev-grid" id="gridCatalogo">
<?php foreach ($catalogoCalif as $ev):
    $calificado = $ev['calificacion'] !== null;
    $nc  = colorNota($calificado ? (float)$ev['calificacion'] : null);
    $hoy = date('Y-m-d');
    $vencida = !$calificado && $ev['tiempo_entrega'] && $ev['tiempo_entrega'] < $hoy;
?>
<div class="ev-card <?= $vencida?'vencida':'' ?>">
    <div class="ev-card-head">
        <span class="ev-card-titulo"><?= htmlspecialchars($ev['nombre']) ?></span>
        <span class="ev-card-tipo"><?= htmlspecialchars($ev['tipo_evidencia'] ?? 'General') ?></span>
    </div>
    <div class="ev-card-body">
        <div class="ev-row">
            <span class="ev-row-lbl"><i class="fas fa-balance-scale" style="width:14px;color:var(--color-verde-1)"></i> Porcentaje</span>
            <span class="ev-row-val"><?= $ev['porcentaje'] ? $ev['porcentaje'].'%' : '—' ?></span>
        </div>
        <div class="ev-row">
            <span class="ev-row-lbl"><i class="fas fa-calendar-day" style="width:14px;color:var(--color-verde-1)"></i> Fecha límite</span>
            <span class="ev-row-val <?= $vencida?'color:#dc2626':'' ?>">
                <?= $ev['tiempo_entrega'] ? date('d/m/Y', strtotime($ev['tiempo_entrega'])) : '—' ?>
                <?php if ($vencida): ?><i class="fas fa-exclamation-circle" style="color:#dc2626;margin-left:4px;"></i><?php endif; ?>
            </span>
        </div>
        <div class="ev-row">
            <span class="ev-row-lbl"><i class="fas fa-star" style="width:14px;color:var(--color-verde-1)"></i> Calificación</span>
            <span class="ev-row-val">
                <?php if ($calificado): ?>
                <span class="nota-grande" style="color:<?= $nc ?>;border-color:<?= $nc ?>;background:<?= $nc ?>18;">
                    <?= number_format((float)$ev['calificacion'],1) ?>
                </span>
                <?php else: ?>
                <span class="sin-cal" style="background:rgba(245,158,11,.12);color:#92400e;">
                    <i class="fas fa-clock"></i> Por calificar
                </span>
                <?php endif; ?>
            </span>
        </div>
        <?php if ($calificado): ?>
        <div class="ev-row">
            <span class="ev-row-lbl"><i class="fas fa-check-circle" style="width:14px;color:var(--color-verde-1)"></i> Estado</span>
            <span class="ev-row-val">
                <?php $apEv = (float)$ev['calificacion'] >= 3.0; ?>
                <span style="color:<?= $apEv?'#16a34a':'#dc2626' ?>;font-size:12px;font-weight:700;">
                    <i class="fas fa-<?= $apEv?'check':'times' ?>"></i>
                    <?= htmlspecialchars($ev['estado_aprobacion'] ?? ($apEv?'Aprobado':'Desaprobado')) ?>
                </span>
            </span>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ── ENTREGAS INDIVIDUALES ─────────────────────────────────────────── -->
<?php if (!empty($individuales)): ?>
<div class="sec-title" style="margin-top:30px;">
    <i class="fas fa-file-signature"></i>
    Mis Entregas Individuales
    <span style="margin-left:auto;font-size:12px;font-weight:400;color:var(--color-texto-secundario);">
        <?= $totalInd ?> entrega<?= $totalInd!=1?'s':'' ?>
    </span>
</div>

<!-- Filtros -->
<div class="tabs" id="tabsInd">
    <button class="tab-btn active" onclick="filtrar('todos')">Todas (<?= $totalInd ?>)</button>
    <?php
    $cnts = array_count_values(array_column($individuales,'ESTADO'));
    foreach (['Calificada','Entregada','Pendiente','No Entregada'] as $e):
        if (!isset($cnts[$e])) continue;
        [$lbl,$col] = labelEstado($e);
    ?>
    <button class="tab-btn" onclick="filtrar('<?= $e ?>')"><?= $lbl ?> (<?= $cnts[$e] ?>)</button>
    <?php endforeach; ?>
</div>

<div class="ev-grid" id="gridInd">
<?php foreach ($individuales as $ev):
    [$lbl,$col,$bg,$icon] = labelEstado($ev['ESTADO']);
    $calificado = $ev['CALIFICACION'] !== null;
    $nc = colorNota($calificado ? (float)$ev['CALIFICACION'] : null);
    $hoy = date('Y-m-d');
    $vencida = $ev['ESTADO']==='Pendiente' && $ev['FECHA_LIMITE'] && $ev['FECHA_LIMITE'] < $hoy;
?>
<div class="ev-card <?= $vencida?'vencida':'' ?>" data-estado="<?= htmlspecialchars($ev['ESTADO']) ?>">
    <div class="ev-card-head">
        <span class="ev-card-titulo"><?= htmlspecialchars($ev['TITULO']) ?></span>
        <span class="ev-card-tipo"><?= htmlspecialchars($ev['TIPO'] ?? '—') ?></span>
    </div>
    <div class="ev-card-body">
        <div class="ev-row">
            <span class="ev-row-lbl"><i class="fas fa-tag" style="width:14px;color:var(--color-verde-1)"></i> Estado</span>
            <span class="ev-row-val">
                <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:20px;background:<?= $bg ?>;color:<?= $col ?>;font-size:11px;font-weight:700;">
                    <i class="fas <?= $icon ?>"></i> <?= $lbl ?>
                </span>
            </span>
        </div>
        <div class="ev-row">
            <span class="ev-row-lbl"><i class="fas fa-calendar-times" style="width:14px;color:var(--color-verde-1)"></i> Fecha límite</span>
            <span class="ev-row-val <?= $vencida?'':''; ?>" style="<?= $vencida?'color:#dc2626':'' ?>">
                <?= $ev['FECHA_LIMITE'] ? date('d/m/Y',strtotime($ev['FECHA_LIMITE'])) : '—' ?>
                <?php if ($vencida): ?><i class="fas fa-exclamation-circle" style="color:#dc2626;margin-left:3px;"></i><?php endif; ?>
            </span>
        </div>
        <?php if ($ev['FECHA_ENTREGA']): ?>
        <div class="ev-row">
            <span class="ev-row-lbl"><i class="fas fa-paper-plane" style="width:14px;color:var(--color-verde-1)"></i> Entregado</span>
            <span class="ev-row-val"><?= date('d/m/Y',strtotime($ev['FECHA_ENTREGA'])) ?></span>
        </div>
        <?php endif; ?>
        <div class="ev-row">
            <span class="ev-row-lbl"><i class="fas fa-star" style="width:14px;color:var(--color-verde-1)"></i> Calificación</span>
            <span class="ev-row-val">
                <?php if ($calificado): ?>
                <span class="nota-grande" style="color:<?= $nc ?>;border-color:<?= $nc ?>;background:<?= $nc ?>18;">
                    <?= number_format((float)$ev['CALIFICACION'],1) ?>
                </span>
                <?php else: ?>
                <span class="sin-cal" style="background:rgba(245,158,11,.12);color:#92400e;">
                    <i class="fas fa-clock"></i> Por calificar
                </span>
                <?php endif; ?>
            </span>
        </div>
        <?php if ($ev['OBSERVACION']): ?>
        <div style="margin-top:8px;padding:8px 10px;background:var(--color-gris-fondo);border-radius:8px;font-size:12px;color:var(--color-texto-secundario);">
            <i class="fas fa-comment-alt" style="margin-right:5px;"></i><?= htmlspecialchars($ev['OBSERVACION']) ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (empty($catalogoCalif) && empty($individuales)): ?>
<div class="vacio">
    <i class="fas fa-folder-open"></i>
    <p style="color:var(--color-texto-secundario);margin-bottom:0;">Tu instructor aún no ha registrado evidencias.</p>
</div>
<?php endif; ?>

<?php endif; ?>
</main>

<?php include __DIR__ . '/../../config/footer.php'; ?>
<script src="<?= asset_url('js/tema.js') ?>"></script>
<script src="<?= asset_url('js/loader.js') ?>"></script>
<script src="<?= asset_url('js/panel_menu.js') ?>"></script>
<script src="<?= asset_url('js/dropdowns.js') ?>"></script>
<script src="<?= asset_url('js/profile_menu.js') ?>"></script>
<script src="<?= asset_url('js/menu.js') ?>"></script>
<script>
function filtrar(estado) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    event.currentTarget.classList.add('active');
    document.querySelectorAll('#gridInd .ev-card').forEach(card => {
        card.classList.toggle('hidden',
            estado !== 'todos' && card.dataset.estado !== estado
        );
    });
}
</script>
</body>
</html>