<?php
// mod/aprendiz/asistencia.php — Vista de asistencia del aprendiz
session_start();
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../conexion/conexion.php';

if (!esAprendiz()) redirect_to('login.php');

class AsistenciaAprendizDB extends BaseDatos {
    protected function consultar(){}protected function insertar(){}
    protected function actualizar(){}protected function eliminar(){}
    public function uno($sql,$p=[]){ $s=$this->ejecutarPreparado($sql,$p); return $s?$s->fetch(PDO::FETCH_ASSOC):[]; }
    public function varios($sql,$p=[]){ $s=$this->ejecutarPreparado($sql,$p); return $s?$s->fetchAll(PDO::FETCH_ASSOC):[]; }
}
$db  = new AsistenciaAprendizDB();
$ref = (int)($_SESSION['usuario_ref_id'] ?? 0);
$uid = (int)($_SESSION['usuario_id']     ?? 0);
$usuarioEmail = $_SESSION['usuario_email'] ?? '';

require_once __DIR__ . '/../../conexion/AprendizDAO.php';
$aprendizDAO = new AprendizDAO();
$aprendiz = $aprendizDAO->obtenerPorSesion($ref, $uid, $usuarioEmail);

$aid = $aprendiz ? (int)$aprendiz['APRENDIZ_ID'] : 0;

// Filtros
$filtroMes = $_GET['mes'] ?? '';
$filtroEst = $_GET['estado'] ?? '';

// Construir query con filtros
$params = [':aid' => $aid];
$where  = 'WHERE a.ESTUDIANTE_ID = :aid';
if ($filtroMes) {
    $where .= ' AND DATE_FORMAT(a.FECHA, \'%Y-%m\') = :mes';
    $params[':mes'] = $filtroMes;
}
if ($filtroEst) {
    $where .= ' AND a.ESTADO = :est';
    $params[':est'] = $filtroEst;
}

$registros = $aid ? $db->varios(
    "SELECT a.ASISTENCIA_ID, a.FECHA, a.ESTADO, a.EXCUSA_PRESENTADA, a.HORAS_FALTA
     FROM asistencia a
     $where
     ORDER BY a.FECHA DESC",
    $params
) : [];

// Totales globales (sin filtro)
$totales = $aid ? $db->uno(
    "SELECT
        COUNT(*) AS total,
        SUM(ESTADO='asistio')  AS asistio,
        SUM(ESTADO='retardo')  AS retardo,
        SUM(ESTADO='falta')    AS falta,
        SUM(ESTADO='excusa')   AS excusa,
        SUM(HORAS_FALTA)       AS horas_falta
     FROM asistencia WHERE ESTUDIANTE_ID=:aid",
    [':aid'=>$aid]
) : [];

$total   = (int)($totales['total']   ?? 0);
$asistio = (int)($totales['asistio'] ?? 0);
$retardo = (int)($totales['retardo'] ?? 0);
$falta   = (int)($totales['falta']   ?? 0);
$excusa  = (int)($totales['excusa']  ?? 0);
$pct     = $total > 0 ? round(($asistio / $total) * 100, 1) : 0;
$pctColor = $pct >= 80 ? '#16a34a' : ($pct >= 60 ? '#f59e0b' : '#dc2626');

// Meses disponibles para filtro
$meses = $aid ? $db->varios(
    "SELECT DISTINCT DATE_FORMAT(FECHA,'%Y-%m') AS mes,
            DATE_FORMAT(FECHA,'%M %Y') AS label
     FROM asistencia WHERE ESTUDIANTE_ID=:aid
     ORDER BY mes DESC",
    [':aid'=>$aid]
) : [];

// Agrupar por mes para el calendario visual
$porMes = [];
foreach ($registros as $r) {
    $m = date('Y-m', strtotime($r['FECHA']));
    $porMes[$m][] = $r;
}

$estadoConfig = [
    'asistio' => ['label'=>'Asistió',  'color'=>'#16a34a','bg'=>'rgba(22,163,74,.1)', 'icon'=>'fa-check-circle'],
    'retardo' => ['label'=>'Retardo',  'color'=>'#f59e0b','bg'=>'rgba(245,158,11,.1)','icon'=>'fa-clock'],
    'falta'   => ['label'=>'Falta',    'color'=>'#dc2626','bg'=>'rgba(220,38,38,.1)', 'icon'=>'fa-times-circle'],
    'excusa'  => ['label'=>'Excusa',   'color'=>'#3b82f6','bg'=>'rgba(59,130,246,.1)','icon'=>'fa-file-medical'],
];
$meses_es = ['January'=>'Enero','February'=>'Febrero','March'=>'Marzo','April'=>'Abril',
             'May'=>'Mayo','June'=>'Junio','July'=>'Julio','August'=>'Agosto',
             'September'=>'Septiembre','October'=>'Octubre','November'=>'Noviembre','December'=>'Diciembre'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Mi Asistencia — DTD SENA</title>
<link rel="stylesheet" href="<?= htmlspecialchars(asset_url('css/style.css')) ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* STATS */
.at-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:14px;margin-bottom:24px;}
.at-stat{background:var(--color-blanco);border:1px solid var(--border-color);border-radius:16px;padding:18px 14px;text-align:center;box-shadow:var(--shadow-card);position:relative;overflow:hidden;}
.at-stat::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;border-radius:16px 16px 0 0;}
.at-stat.s-ok::before{background:#16a34a;}
.at-stat.s-ret::before{background:#f59e0b;}
.at-stat.s-fal::before{background:#dc2626;}
.at-stat.s-exc::before{background:#3b82f6;}
.at-stat.s-pct::before{background:var(--color-verde-1);}
.at-val{font-size:30px;font-weight:800;line-height:1;margin-bottom:4px;}
.at-lbl{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--color-texto-secundario);}
.at-ico{font-size:18px;margin-bottom:8px;}
/* BARRA */
.bar-wrap{background:var(--color-blanco);border:1px solid var(--border-color);border-radius:16px;padding:18px 22px;margin-bottom:22px;box-shadow:var(--shadow-card);}
.bar-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;}
.bar-title{font-size:14px;font-weight:700;color:var(--color-texto);display:flex;align-items:center;gap:8px;}
.bar-pct{font-size:26px;font-weight:800;}
.bar-bg{height:12px;background:var(--color-gris-fondo);border-radius:20px;overflow:hidden;margin-bottom:10px;}
.bar-fill{height:100%;border-radius:20px;transition:width 1s cubic-bezier(.4,0,.2,1);}
.bar-legend{display:flex;gap:18px;flex-wrap:wrap;}
.bar-leg-item{display:flex;align-items:center;gap:6px;font-size:12px;color:var(--color-texto-secundario);}
.bar-leg-dot{width:10px;height:10px;border-radius:50%;}
/* FILTROS */
.filtros{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px;align-items:center;}
.filtros select{padding:7px 12px;border:1px solid var(--border-color);border-radius:8px;font-size:13px;background:var(--color-blanco);color:var(--color-texto);cursor:pointer;}
.filtros select:focus{outline:none;border-color:var(--color-verde-1);}
.filtro-label{font-size:12px;font-weight:700;color:var(--color-texto-secundario);text-transform:uppercase;letter-spacing:.5px;}
.btn-limpiar{padding:7px 14px;background:none;border:1px solid var(--border-color);border-radius:8px;font-size:12px;color:var(--color-texto-secundario);cursor:pointer;text-decoration:none;display:flex;align-items:center;gap:5px;transition:.2s;}
.btn-limpiar:hover{border-color:var(--color-verde-1);color:var(--color-verde-1);}
/* TABLA */
.at-table{width:100%;border-collapse:collapse;font-size:13px;}
.at-table th{background:var(--color-gris-fondo);padding:10px 14px;text-align:left;border-bottom:2px solid var(--border-color);font-size:11px;font-weight:700;color:var(--color-texto-secundario);text-transform:uppercase;letter-spacing:.4px;}
.at-table td{padding:11px 14px;border-bottom:1px solid var(--border-color);vertical-align:middle;}
.at-table tr:hover td{background:rgba(57,169,0,.03);}
.at-table tr:last-child td{border-bottom:none;}
/* ESTADO BADGE */
.est-badge{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700;}
/* MES GROUP HEADER */
.mes-header{background:rgba(57,169,0,.06);padding:9px 14px;font-size:12px;font-weight:700;color:var(--color-verde-1);border-bottom:2px solid rgba(57,169,0,.15);display:flex;align-items:center;gap:8px;}
/* VACIO */
.vacio{text-align:center;padding:50px 20px;background:var(--color-blanco);border-radius:18px;border:2px dashed var(--border-color);}
.vacio i{font-size:48px;opacity:.2;display:block;margin-bottom:12px;}
/* EXCUSA BADGE */
.excusa-si{color:#3b82f6;font-size:11px;}
</style>
</head>
<body>
<div id="loader"><img src="<?= htmlspecialchars(asset_url('img/logo_sena_verde.png')) ?>" alt="" id="loader-logo"></div>
<?php include __DIR__ . '/../../config/header.php'; ?>
<main class="container" id="contenido-principal" style="display:none;opacity:0;">

<!-- Breadcrumb -->
<div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;flex-wrap:wrap;">
    <a href="<?= htmlspecialchars(app_url('mod/aprendiz/index.php')) ?>" class="btn-view-all">
        <i class="fas fa-arrow-left"></i> Dashboard
    </a>
    <span style="font-size:13px;color:var(--color-texto-secundario);">
        <i class="fas fa-calendar-check"></i> Mi Asistencia
    </span>
    <?php if ($aprendiz): ?>
    <span style="font-size:13px;color:var(--color-texto-secundario);margin-left:auto;">
        <?= htmlspecialchars($aprendiz['NOMBRES'].' '.$aprendiz['APELLIDOS']) ?>
    </span>
    <?php endif; ?>
</div>

<?php if (!$aid): ?>
<div class="vacio">
    <i class="fas fa-user-slash"></i>
    <p style="color:var(--color-texto-secundario);">No se encontró tu perfil de aprendiz. Contacta al administrador.</p>
</div>
<?php else: ?>

<!-- STATS -->
<div class="at-stats">
    <div class="at-stat s-pct">
        <div class="at-ico" style="color:<?= $pctColor ?>"><i class="fas fa-chart-pie"></i></div>
        <div class="at-val" style="color:<?= $pctColor ?>"><?= $pct ?>%</div>
        <div class="at-lbl">Asistencia</div>
    </div>
    <div class="at-stat s-ok">
        <div class="at-ico" style="color:#16a34a"><i class="fas fa-check-circle"></i></div>
        <div class="at-val" style="color:#16a34a"><?= $asistio ?></div>
        <div class="at-lbl">Asistencias</div>
    </div>
    <div class="at-stat s-ret">
        <div class="at-ico" style="color:#f59e0b"><i class="fas fa-clock"></i></div>
        <div class="at-val" style="color:#f59e0b"><?= $retardo ?></div>
        <div class="at-lbl">Retardos</div>
    </div>
    <div class="at-stat s-fal">
        <div class="at-ico" style="color:#dc2626"><i class="fas fa-times-circle"></i></div>
        <div class="at-val" style="color:#dc2626"><?= $falta ?></div>
        <div class="at-lbl">Faltas</div>
    </div>
    <div class="at-stat s-exc">
        <div class="at-ico" style="color:#3b82f6"><i class="fas fa-file-medical"></i></div>
        <div class="at-val" style="color:#3b82f6"><?= $excusa ?></div>
        <div class="at-lbl">Excusas</div>
    </div>
    <div class="at-stat">
        <div class="at-ico" style="color:var(--color-texto-secundario)"><i class="fas fa-calendar-alt"></i></div>
        <div class="at-val" style="color:var(--color-texto)"><?= $total ?></div>
        <div class="at-lbl">Total días</div>
    </div>
</div>

<!-- BARRA DE ASISTENCIA -->
<div class="bar-wrap">
    <div class="bar-header">
        <span class="bar-title"><i class="fas fa-chart-bar" style="color:var(--color-verde-1)"></i> Resumen de Asistencia</span>
        <span class="bar-pct" style="color:<?= $pctColor ?>"><?= $pct ?>%</span>
    </div>
    <?php if ($total > 0): ?>
    <!-- Barra segmentada -->
    <div class="bar-bg" style="height:16px;">
        <div style="display:flex;height:100%;border-radius:20px;overflow:hidden;">
            <?php if ($asistio): ?>
            <div style="width:<?= round($asistio/$total*100,1) ?>%;background:#16a34a;" title="Asistió: <?= $asistio ?>"></div>
            <?php endif; ?>
            <?php if ($retardo): ?>
            <div style="width:<?= round($retardo/$total*100,1) ?>%;background:#f59e0b;" title="Retardo: <?= $retardo ?>"></div>
            <?php endif; ?>
            <?php if ($excusa): ?>
            <div style="width:<?= round($excusa/$total*100,1) ?>%;background:#3b82f6;" title="Excusa: <?= $excusa ?>"></div>
            <?php endif; ?>
            <?php if ($falta): ?>
            <div style="width:<?= round($falta/$total*100,1) ?>%;background:#dc2626;" title="Falta: <?= $falta ?>"></div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    <div class="bar-legend">
        <div class="bar-leg-item"><div class="bar-leg-dot" style="background:#16a34a"></div><?= $asistio ?> asistencias</div>
        <div class="bar-leg-item"><div class="bar-leg-dot" style="background:#f59e0b"></div><?= $retardo ?> retardos</div>
        <div class="bar-leg-item"><div class="bar-leg-dot" style="background:#3b82f6"></div><?= $excusa ?> excusas</div>
        <div class="bar-leg-item"><div class="bar-leg-dot" style="background:#dc2626"></div><?= $falta ?> faltas</div>
    </div>
</div>

<!-- FILTROS -->
<div class="filtros">
    <span class="filtro-label"><i class="fas fa-filter"></i> Filtrar:</span>
    <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
        <select name="mes" onchange="this.form.submit()">
            <option value="">Todos los meses</option>
            <?php foreach ($meses as $m): ?>
            <option value="<?= $m['mes'] ?>" <?= $filtroMes===$m['mes']?'selected':'' ?>>
                <?php
                    $partes = explode(' ', $m['label']);
                    $mesNombre = $meses_es[$partes[0]] ?? $partes[0];
                    echo htmlspecialchars($mesNombre.' '.$partes[1]);
                ?>
            </option>
            <?php endforeach; ?>
        </select>
        <select name="estado" onchange="this.form.submit()">
            <option value="">Todos los estados</option>
            <?php foreach ($estadoConfig as $k=>$v): ?>
            <option value="<?= $k ?>" <?= $filtroEst===$k?'selected':'' ?>><?= $v['label'] ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ($filtroMes || $filtroEst): ?>
        <a href="?" class="btn-limpiar"><i class="fas fa-times"></i> Limpiar</a>
        <?php endif; ?>
    </form>
    <span style="margin-left:auto;font-size:12px;color:var(--color-texto-secundario);">
        <?= count($registros) ?> registro<?= count($registros)!==1?'s':'' ?>
    </span>
</div>

<!-- TABLA DE REGISTROS -->
<?php if (empty($registros)): ?>
<div class="vacio">
    <i class="fas fa-calendar-times"></i>
    <p style="color:var(--color-texto-secundario);">No hay registros de asistencia<?= ($filtroMes||$filtroEst)?' para los filtros seleccionados':' aún' ?>.</p>
    <?php if ($filtroMes||$filtroEst): ?>
    <a href="?" class="btn-view-all" style="display:inline-flex;margin-top:12px;"><i class="fas fa-times"></i> Quitar filtros</a>
    <?php endif; ?>
</div>
<?php else: ?>
<div style="background:var(--color-blanco);border:1px solid var(--border-color);border-radius:18px;overflow:hidden;box-shadow:var(--shadow-card);">
    <table class="at-table">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Día</th>
                <th>Estado</th>
                <th>Horas falta</th>
                <th>Excusa</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $diasSemana = ['Sunday'=>'Domingo','Monday'=>'Lunes','Tuesday'=>'Martes',
                       'Wednesday'=>'Miércoles','Thursday'=>'Jueves','Friday'=>'Viernes','Saturday'=>'Sábado'];
        $mesAnterior = '';
        foreach ($registros as $r):
            $mesActual = date('Y-m', strtotime($r['FECHA']));
            if ($mesActual !== $mesAnterior):
                $mesAnterior = $mesActual;
                $nombreMes = date('F Y', strtotime($r['FECHA']));
                $nombreMes = strtr($nombreMes, $meses_es);
        ?>
            <tr>
                <td colspan="5" style="padding:0;">
                    <div class="mes-header">
                        <i class="fas fa-calendar-alt"></i>
                        <?= htmlspecialchars($nombreMes) ?>
                        <span style="margin-left:auto;font-weight:400;opacity:.7;font-size:11px;">
                            <?php
                                $countMes = array_reduce($registros, function($c,$x) use($mesActual){
                                    return $c + (date('Y-m',strtotime($x['FECHA']))===$mesActual?1:0);
                                }, 0);
                                echo $countMes.' días';
                            ?>
                        </span>
                    </div>
                </td>
            </tr>
        <?php endif; ?>
        <?php
            $cfg   = $estadoConfig[$r['ESTADO']] ?? ['label'=>$r['ESTADO'],'color'=>'#888','bg'=>'#eee','icon'=>'fa-circle'];
            $diaNom = $diasSemana[date('l', strtotime($r['FECHA']))] ?? '';
        ?>
            <tr>
                <td style="font-weight:600;">
                    <?= date('d/m/Y', strtotime($r['FECHA'])) ?>
                </td>
                <td style="color:var(--color-texto-secundario);font-size:12px;">
                    <?= $diaNom ?>
                </td>
                <td>
                    <span class="est-badge" style="background:<?= $cfg['bg'] ?>;color:<?= $cfg['color'] ?>;">
                        <i class="fas <?= $cfg['icon'] ?>"></i>
                        <?= $cfg['label'] ?>
                    </span>
                </td>
                <td style="text-align:center;">
                    <?php if ($r['HORAS_FALTA'] > 0): ?>
                    <span style="color:#dc2626;font-weight:700;font-size:13px;">
                        <?= $r['HORAS_FALTA'] ?>h
                    </span>
                    <?php else: ?>
                    <span style="color:var(--color-texto-secundario);font-size:12px;">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($r['EXCUSA_PRESENTADA']): ?>
                    <span class="excusa-si"><i class="fas fa-check-circle"></i> Presentada</span>
                    <?php else: ?>
                    <span style="color:var(--color-texto-secundario);font-size:12px;">—</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
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