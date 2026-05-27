<?php
// mod/asistencias_detalle.php
session_start();
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../conexion/FichaDAO.php';
require_once __DIR__ . '/../conexion/AsistenciaDAO.php';
require_once __DIR__ . '/../conexion/instructorDAO.php';

$fichaDAO      = new FichaDAO();
$asistenciaDAO = new AsistenciaDAO();
$instDAO       = new InstructorDAO();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header('Location: asistencias.php'); exit; }

$ficha = $fichaDAO->obtenerPorId($id);
if (!$ficha) { header('Location: asistencias.php'); exit; }

// Instructor sólo ve sus fichas
if (esInstructor()) {
    $instId  = (int)($_SESSION['usuario_ref_id'] ?? 0);
    $misF    = $instDAO->obtenerFichas($instId);
    $misFIds = array_column($misF, 'FICHA_ID');
    if (!in_array($id, $misFIds)) { header('Location: asistencias.php'); exit; }
}

$aprendices = $fichaDAO->obtenerAprendices($id);

// ── Semana seleccionada ───────────────────────────────────────────────────
if (isset($_GET['semana']) && preg_match('/^\d{4}-W\d{1,2}$/', $_GET['semana'])) {
    [$anio, $numSem] = explode('-W', $_GET['semana']);
    $fechaInicio     = new DateTime();
    $fechaInicio->setISODate((int)$anio, (int)$numSem, 1);
    $semSel = $_GET['semana'];
} else {
    $fechaInicio = new DateTime();
    $fechaInicio->modify('monday this week');
    $semSel = $fechaInicio->format('Y-\WW');
}

$dias       = [];
$dNombres   = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
for ($i = 0; $i < 6; $i++) {
    $f = clone $fechaInicio;
    $f->modify("+$i days");
    $dias[] = [
        'nombre'  => $dNombres[$i],
        'fecha'   => $f->format('Y-m-d'),
        'display' => $f->format('d/m'),
        'hoy'     => $f->format('Y-m-d') === date('Y-m-d'),
    ];
}

$guardados = $asistenciaDAO->obtenerAsistenciasSemana($id, $dias[0]['fecha']);

// ── Helper ────────────────────────────────────────────────────────────────
function getReg(array $guardados, int $apId, string $fecha): ?array {
    foreach ($guardados[$apId] ?? [] as $r)
        if ($r['fecha'] === $fecha) return $r;
    return null;
}

// ── POST ──────────────────────────────────────────────────────────────────
$flash = ['msg'=>'','type'=>''];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_asistencia'])) {
    if (!esAdmin() && !esInstructor()) {
        $flash = ['msg'=>'Sin permiso.','type'=>'error'];
    } else {
        $celdas = [];
        foreach ($aprendices as $ap) {
            $apId = $ap['APRENDIZ_ID'] ?? null;
            if (!$apId) continue;
            foreach ($dias as $d) {
                $fecha = $d['fecha'];
                $raw   = $_POST["c_{$apId}_{$fecha}"] ?? 'a';
                // Mapear valor del select al estado real
                if (str_starts_with($raw, 'r')) {
                    $estado = 'retardo';
                    $horas  = (int)substr($raw, 1);
                } elseif ($raw === 'f') {
                    $estado = 'falta';
                    $horas  = 4;
                } elseif ($raw === 'e') {
                    $estado = 'excusa';
                    $horas  = 0;
                } else {
                    // 'a' o cualquier otro = asistio
                    $estado = 'asistio';
                    $horas  = 0;
                }
                $celdas[$apId][$fecha] = ['estado'=>$estado,'horas'=>$horas];
            }
        }

        $ok = $asistenciaDAO->guardarAsistenciasSemana($id, $dias[0]['fecha'], $celdas);
        if ($ok) {
            $flash    = ['msg'=>'Asistencia guardada.','type'=>'success'];
            $guardados = $asistenciaDAO->obtenerAsistenciasSemana($id, $dias[0]['fecha']);
        } else {
            $flash = ['msg'=>'Error: '.$asistenciaDAO->imprimirError(),'type'=>'error'];
        }
    }
}

// ── Totales para barra inferior ───────────────────────────────────────────
$tAs = $tRe = $tFa = $tEx = $tH = 0;
foreach ($guardados as $regs)
    foreach ($regs as $r) {
        match($r['estado']) {
            'asistio'=>$tAs++, 'retardo'=>$tRe++,
            'falta'  =>$tFa++, 'excusa' =>$tEx++,
            default  =>null
        };
        $tH += (int)$r['horas_falta'];
    }
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Asistencia — Ficha <?= htmlspecialchars($ficha['CODIGO_FICHA']) ?></title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
/* ═══════════════════ CABECERA ═══════════════════ */
.ah {
    background: linear-gradient(135deg, var(--color-verde-1), var(--color-verde-2));
    border-radius: var(--border-radius-card);
    padding: 22px 28px;
    color: #fff;
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 14px;
    margin-bottom: 20px;
    box-shadow: var(--shadow-card);
}
.ah h1 { font-size: 20px; font-weight: 800; display: flex; align-items: center; gap: 10px; margin: 0; }
.ah-meta { display: flex; gap: 14px; flex-wrap: wrap; font-size: 12px; opacity: .85; margin-top: 6px; }
.ah-meta span { display: flex; align-items: center; gap: 5px; }
.btn-back {
    background: rgba(255,255,255,.18); color: #fff;
    padding: 8px 20px; border-radius: 30px; text-decoration: none;
    font-weight: 700; font-size: 13px; transition: .2s; white-space: nowrap;
}
.btn-back:hover { background: rgba(255,255,255,.3); color:#fff; }

/* ═══════════════════ BARRA SEMANA ═══════════════ */
.sem-bar {
    background: var(--color-blanco);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius-card);
    padding: 13px 20px;
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 12px;
    margin-bottom: 16px;
}
.sem-label { font-size: 14px; font-weight: 700; color: var(--color-verde-1); display:flex;align-items:center;gap:8px; }
.sem-form  { display: flex; gap: 8px; align-items: center; }
.sem-form input[type="week"] {
    padding: 7px 12px; border: 2px solid var(--border-color); border-radius: 8px;
    font-size: 13px; background: var(--color-blanco); color: var(--color-texto);
}
.sem-form input[type="week"]:focus { border-color: var(--color-verde-1); outline: none; }

/* ═══════════════════ LEYENDA ════════════════════ */
.ley { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 14px; align-items: center; }
.ley-item { display: flex; align-items: center; gap: 5px; font-size: 12px; color: var(--color-texto-secundario); }
.chip {
    width: 32px; height: 28px; border-radius: 7px;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 800;
}
.chip-a { background:#dcfce7; color:#166534; }
.chip-r { background:#fef3c7; color:#92400e; }
.chip-f { background:#fee2e2; color:#991b1b; }
.chip-e { background:#ede9fe; color:#6d28d9; }
.ley-tip { margin-left:auto; font-size:11px; color:var(--color-texto-secundario); display:flex;align-items:center;gap:4px; }

/* ═══════════════════ TABLA EXCEL ════════════════ */
.xl-wrap {
    background: var(--color-blanco);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius-card);
    overflow: hidden;
    box-shadow: var(--shadow-card);
}
.xl-scroll { overflow-x: auto; }

.xl-table { width: 100%; border-collapse: collapse; font-size: 13px; }

/* Cabecera días */
.xl-table thead th {
    background: var(--color-verde-1);
    color: #fff;
    padding: 11px 8px;
    text-align: center;
    font-weight: 800;
    font-size: 12px;
    border-right: 1px solid rgba(255,255,255,.15);
    white-space: nowrap;
    user-select: none;
}
.xl-table thead th.th-ap {
    background: #1a2e1a;
    text-align: left;
    padding-left: 16px;
    min-width: 210px;
    position: sticky; left: 0; z-index: 10;
}
.xl-table thead th.th-hoy {
    background: #2d8000;
    box-shadow: inset 0 -3px 0 rgba(255,255,255,.4);
}

/* Filas */
.xl-table tbody tr { border-bottom: 1px solid var(--border-color); }
.xl-table tbody tr:last-child { border-bottom: none; }
.xl-table tbody tr:hover td { background: rgba(57,169,0,.03); }
.xl-table tbody tr:hover .td-ap { background: rgba(57,169,0,.06); }

/* Columna nombre */
.td-ap {
    position: sticky; left: 0; z-index: 5;
    background: var(--color-blanco);
    border-right: 2px solid var(--border-color);
    padding: 9px 14px;
    min-width: 210px;
    transition: background .15s;
}
.ap-cell { display: flex; align-items: center; gap: 9px; }
.ap-av {
    width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0;
    background: linear-gradient(135deg, var(--color-verde-1), var(--color-verde-2));
    color: #fff; font-size: 11px; font-weight: 800;
    display: flex; align-items: center; justify-content: center;
}
.ap-nm { font-weight: 700; font-size: 13px; color: var(--color-texto); line-height: 1.2; }
.ap-dc { font-size: 11px; color: var(--color-texto-secundario); }

/* Celda día */
.td-dia {
    padding: 7px 5px;
    text-align: center;
    border-right: 1px solid var(--border-color);
    vertical-align: middle;
}
.td-dia:last-child { border-right: none; }
.td-hoy { background: rgba(57,169,0,.05) !important; }

/* ════ SELECT — el corazón ════ */
.xl-sel {
    width: 74px; padding: 7px 2px;
    border: none; border-radius: 9px;
    font-size: 13px; font-weight: 800; text-align: center;
    cursor: pointer; outline: none;
    -webkit-appearance: none; appearance: none;
    transition: transform .12s, box-shadow .15s;
}
.xl-sel:hover  { transform: scale(1.07); }
.xl-sel:focus  { box-shadow: 0 0 0 3px rgba(57,169,0,.3); transform: scale(1.07); }

/* Paleta clara */
.s-a  { background: #dcfce7; color: #166534; }   /* asistió */
.s-r1 { background: #fef9c3; color: #854d0e; }   /* retardo 1h */
.s-r2 { background: #fef3c7; color: #92400e; }   /* retardo 2h */
.s-r3 { background: #fde68a; color: #78350f; }   /* retardo 3h */
.s-r4 { background: #fcd34d; color: #78350f; }   /* retardo 4h */
.s-f  { background: #fee2e2; color: #991b1b; }   /* falta */
.s-e  { background: #ede9fe; color: #6d28d9; }   /* excusa */

/* Sub-info debajo del select */
.cel-sub {
    font-size: 9.5px; margin-top: 3px;
    color: var(--color-texto-secundario);
    line-height: 1.2; white-space: nowrap;
}
.cel-venc { color: #dc2626; font-weight: 700; }

/* ═══════════════════ BARRA RESUMEN ══════════════ */
.res-bar {
    display: flex; gap: 16px; flex-wrap: wrap;
    padding: 13px 20px;
    background: var(--color-gris-fondo);
    border-top: 1px solid var(--border-color);
    align-items: center;
}
.res-chip { display: flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 700; }
.res-dot  { width: 10px; height: 10px; border-radius: 50%; }

/* ═══════════════════ BTN GUARDAR ════════════════ */
.btn-gd {
    background: linear-gradient(135deg, var(--color-verde-1), var(--color-verde-2));
    color: #fff; border: none;
    padding: 14px 38px; border-radius: 40px;
    font-weight: 800; font-size: 15px; cursor: pointer;
    display: flex; align-items: center; gap: 8px;
    transition: .2s;
    box-shadow: 0 4px 16px rgba(57,169,0,.3);
}
.btn-gd:hover { transform: translateY(-2px); box-shadow: 0 8px 26px rgba(57,169,0,.4); }

/* ═══════════════════ DARK MODE ══════════════════ */
.dark-mode .xl-wrap       { background: var(--color-gris-cuerpo); }
.dark-mode .td-ap         { background: var(--color-gris-cuerpo); }
.dark-mode .sem-bar       { background: var(--color-gris-cuerpo); }
.dark-mode .s-a  { background:#14532d; color:#bbf7d0; }
.dark-mode .s-r1 { background:#3b1f00; color:#fef9c3; }
.dark-mode .s-r2 { background:#451a03; color:#fde68a; }
.dark-mode .s-r3 { background:#4c1f00; color:#fcd34d; }
.dark-mode .s-r4 { background:#78350f; color:#fef3c7; }
.dark-mode .s-f  { background:#7f1d1d; color:#fecaca; }
.dark-mode .s-e  { background:#4c1d95; color:#ddd6fe; }
</style>
</head>
<body>
<div id="loader"><img src="../img/logo_sena_verde.png" alt="" id="loader-logo"></div>
<?php include '../config/header.php'; ?>

<main class="container" id="contenido-principal" style="display:none;opacity:0;">

    <!-- Cabecera -->
    <div class="ah">
        <div>
            <h1><i class="fas fa-table"></i> Asistencia — Ficha <?= htmlspecialchars($ficha['CODIGO_FICHA']) ?></h1>
            <div class="ah-meta">
                <span><i class="fas fa-graduation-cap"></i><?= htmlspecialchars($ficha['programa_nombre'] ?? '') ?></span>
                <span><i class="fas fa-users"></i><?= count($aprendices) ?> aprendices</span>
                <span><i class="fas fa-calendar-week"></i>
                    <?= date('d/m', strtotime($dias[0]['fecha'])) ?> –
                    <?= date('d/m/Y', strtotime(end($dias)['fecha'])) ?>
                </span>
            </div>
        </div>
        <a href="asistencias.php" class="btn-back"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>

    <!-- Selector semana -->
    <div class="sem-bar">
        <div class="sem-label"><i class="fas fa-calendar-week"></i>
            Semana del <?= date('d/m/Y', strtotime($dias[0]['fecha'])) ?>
            al <?= date('d/m/Y', strtotime(end($dias)['fecha'])) ?>
        </div>
        <form method="GET" class="sem-form">
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="week" name="semana" value="<?= htmlspecialchars($semSel) ?>" required>
            <button type="submit" class="btn-action" style="padding:7px 16px;font-size:13px;">
                <i class="fas fa-filter"></i> Ir
            </button>
        </form>
    </div>

    <!-- Tabla tipo Excel -->
    <form method="POST" id="form-asis" action="asistencias_detalle.php?id=<?= $id ?>&semana=<?= htmlspecialchars($semSel) ?>">
    <div class="xl-wrap">
      <div class="xl-scroll">
        <table class="xl-table">
          <thead>
            <tr>
              <th class="th-ap">Aprendiz</th>
              <?php foreach ($dias as $d): ?>
                <th class="<?= $d['hoy'] ? 'th-hoy' : '' ?>">
                  <?= $d['nombre'] ?><br>
                  <span style="font-size:11px;font-weight:500;opacity:.85;"><?= $d['display'] ?></span>
                  <?php if ($d['hoy']): ?>
                    <br><span style="font-size:9px;background:rgba(255,255,255,.25);padding:1px 6px;border-radius:6px;letter-spacing:.5px;">HOY</span>
                  <?php endif; ?>
                </th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
          <?php if (empty($aprendices)): ?>
            <tr>
              <td colspan="<?= count($dias)+1 ?>"
                  style="text-align:center;padding:50px;color:var(--color-texto-secundario);">
                <i class="fas fa-users-slash" style="font-size:32px;opacity:.2;display:block;margin-bottom:10px;"></i>
                No hay aprendices en esta ficha
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($aprendices as $ap):
                $apId = (int)$ap['APRENDIZ_ID'];
                $ini  = strtoupper(substr($ap['NOMBRES'],0,1).substr($ap['APELLIDOS'],0,1));
            ?>
            <tr>
              <!-- Nombre -->
              <td class="td-ap">
                <div class="ap-cell">
                  <div class="ap-av"><?= $ini ?></div>
                  <div>
                    <a href="aprendiz_detalle.php?id=<?= $apId ?>"
                     class="ap-nm"
                     style="text-decoration:none;color:var(--color-texto);transition:color .15s;"
                     onmouseover="this.style.color='var(--color-verde-1)'"
                     onmouseout="this.style.color='var(--color-texto)'"
                     title="Ver detalle del aprendiz"
                     onclick="event.stopPropagation();">
                    <?= htmlspecialchars($ap['NOMBRES'].' '.$ap['APELLIDOS']) ?>
                    <i class="fas fa-external-link-alt" style="font-size:9px;opacity:.5;margin-left:3px;"></i>
                  </a>
                    <div class="ap-dc"><?= htmlspecialchars($ap['TIPO_DOCUMENTO'].' '.$ap['NUMERO_DOCUMENTO']) ?></div>
                  </div>
                </div>
              </td>

              <!-- Celdas días -->
              <?php foreach ($dias as $d):
                  $fecha   = $d['fecha'];
                  $reg     = getReg($guardados, $apId, $fecha);
                  $estado  = $reg['estado']              ?? 'asistio';
                  $horas   = (int)($reg['horas_falta']   ?? 0);
                  $excusa  = $reg['excusa_presentada']   ?? false;
                  $limite  = $reg['fecha_limite_excusa'] ?? null;
                  $vencida = $limite && date('Y-m-d') > $limite && $estado === 'falta';

                  // Valor del select: a | r1 | r2 | r3 | r4 | f | e
                  $sv = match($estado) {
                      'retardo' => 'r'.$horas,
                      'falta'   => 'f',
                      'excusa'  => 'e',
                      default   => 'a',
                  };
                  // Clase CSS
                  $sc = match($sv) {
                      'r1'=>'s-r1','r2'=>'s-r2','r3'=>'s-r3','r4'=>'s-r4',
                      'f' =>'s-f', 'e' =>'s-e', default=>'s-a',
                  };
                  $name = "c_{$apId}_{$fecha}";
              ?>
              <td class="td-dia <?= $d['hoy'] ? 'td-hoy' : '' ?>">

                <select name="<?= $name ?>" id="<?= $name ?>"
                        class="xl-sel <?= $sc ?>"
                        onchange="upd(this)"
                        <?= ($estado==='excusa'&&$excusa) ? 'title="Excusa presentada"' : '' ?>>
                  <option value="a"  <?= $sv==='a'  ?'selected':'' ?>>✓ Asistió</option>
                  <option value="r1" <?= $sv==='r1' ?'selected':'' ?>>⏱ 1h</option>
                  <option value="r2" <?= $sv==='r2' ?'selected':'' ?>>⏱ 2h</option>
                  <option value="r3" <?= $sv==='r3' ?'selected':'' ?>>⏱ 3h</option>
                  <option value="r4" <?= $sv==='r4' ?'selected':'' ?>>⏱ 4h</option>
                  <option value="f"  <?= $sv==='f'  ?'selected':'' ?>>✗ Falta</option>
                  <option value="e"  <?= $sv==='e'  ?'selected':'' ?>>E Excusa</option>
                </select>

                <!-- Sub-info: fecha límite excusa o badge vencido -->
                <?php if ($vencida): ?>
                  <div class="cel-sub cel-venc"><i class="fas fa-lock"></i> Plazo vencido</div>
                <?php elseif ($estado==='falta' && $limite): ?>
                  <div class="cel-sub">Excusa hasta<br><?= date('d/m', strtotime($limite)) ?></div>
                <?php endif; ?>

              </td>
              <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Barra resumen en tiempo real -->
      <div class="res-bar">
        <div class="res-chip"><div class="res-dot" style="background:#16a34a;"></div>Asistencias: <strong id="rAs"><?= $tAs ?></strong></div>
        <div class="res-chip"><div class="res-dot" style="background:#f59e0b;"></div>Retardos: <strong id="rRe"><?= $tRe ?></strong></div>
        <div class="res-chip"><div class="res-dot" style="background:#dc2626;"></div>Faltas: <strong id="rFa"><?= $tFa ?></strong></div>
        <div class="res-chip"><div class="res-dot" style="background:#7c3aed;"></div>Excusas: <strong id="rEx"><?= $tEx ?></strong></div>
        <div class="res-chip" style="margin-left:auto;">
          <i class="fas fa-hourglass-half" style="color:var(--color-texto-secundario);"></i>
          Horas inasistencia: <strong id="rH"><?= $tH ?>h</strong>
        </div>
      </div>
    </div>

    <?php if (!empty($aprendices) && (esAdmin()||esInstructor())): ?>
    <div style="display:flex;justify-content:center;margin-top:22px;">
      <button type="submit" name="guardar_asistencia" class="btn-gd">
        <i class="fas fa-save"></i> Guardar asistencia
      </button>
    </div>
    <?php endif; ?>
    </form>

</main>

<?php include '../config/footer.php'; ?>

<script src="../js/tema.js"></script>
<script src="../js/loader.js"></script>
<script src="../js/panel_menu.js"></script>
<script src="../js/dropdowns.js"></script>
<script src="../js/profile_menu.js"></script>
<script src="../js/sweetalerts.js"></script>
<script src="../js/menu.js"></script>
<script>
// ── Mapa valor → clase CSS ──────────────────────────────────────────────
const CM = { a:'s-a', r1:'s-r1', r2:'s-r2', r3:'s-r3', r4:'s-r4', f:'s-f', e:'s-e' };
const ALL = Object.values(CM);

function upd(sel) {
    ALL.forEach(c => sel.classList.remove(c));
    sel.classList.add(CM[sel.value] || 's-a');
    recalc();
}

// ── Recalcular resumen ──────────────────────────────────────────────────
function recalc() {
    let as=0, re=0, fa=0, ex=0, h=0;
    document.querySelectorAll('.xl-sel').forEach(s => {
        const v = s.value;
        if      (v==='a')             { as++; }
        else if (v.startsWith('r'))   { re++; h += parseInt(v[1]); }
        else if (v==='f')             { fa++; h += 4; }
        else if (v==='e')             { ex++; }
    });
    document.getElementById('rAs').textContent = as;
    document.getElementById('rRe').textContent = re;
    document.getElementById('rFa').textContent = fa;
    document.getElementById('rEx').textContent = ex;
    document.getElementById('rH').textContent  = h+'h';
}

// ── Atajos teclado (solo cuando el select está en foco) ────────────────
document.addEventListener('keydown', e => {
    const s = document.activeElement;
    if (!s || !s.classList.contains('xl-sel')) return;
    const map = { a:'a', f:'f', e:'e', '1':'r1','2':'r2','3':'r3','4':'r4' };
    if (map[e.key]) { e.preventDefault(); s.value = map[e.key]; upd(s); }
});

// ── Flash post-submit ──────────────────────────────────────────────────
<?php if ($flash['msg']): ?>
Swal.fire({
    icon: '<?= $flash['type'] ?>',
    title: '<?= $flash['type']==='success' ? '¡Guardado!' : 'Error' ?>',
    text: '<?= addslashes($flash['msg']) ?>',
    timer: 2400, showConfirmButton: false,
    toast: true, position: 'top-end',
});
<?php endif; ?>

recalc();
</script>
</body>
</html>