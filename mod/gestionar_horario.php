<?php
// mod/gestionar_horario.php
session_start();
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../conexion/conexion.php';

if (!esAdmin() && !esInstructor()) redirect_to('login.php');

class HorarioMgr extends BaseDatos {
    protected function consultar(){}
    protected function insertar(){}
    protected function actualizar(){}
    protected function eliminar(){}
    public function uno($sql,$p=[]){ $s=$this->ejecutarPreparado($sql,$p); return $s?$s->fetch(PDO::FETCH_ASSOC):[]; }
    public function varios($sql,$p=[]){ $s=$this->ejecutarPreparado($sql,$p); return $s?$s->fetchAll(PDO::FETCH_ASSOC):[]; }
    public function exec($sql,$p=[]){ return $this->ejecutarPreparado($sql,$p); }
}
$db  = new HorarioMgr();
$ref = (int)($_SESSION['usuario_ref_id'] ?? 0);

// Fichas del instructor (las que dicta o admin=todas)
if (esAdmin()) {
    $fichas = $db->varios(
        "SELECT f.FICHA_ID, f.CODIGO_FICHA, p.NOMBRE AS programa,
                c.NOMBRE AS centro, r.NOMBRE AS regional,
                COALESCE(i2.INSTRUCTOR_ID,0) AS gestor_id,
                CONCAT(COALESCE(i2.NOMBRES,''),' ',COALESCE(i2.APELLIDOS,'')) AS gestor_nombre
         FROM ficha f
         LEFT JOIN programa p  ON f.PROGRAMA_ID = p.PROGRAMA_ID
         LEFT JOIN centro   c  ON f.CENTRO_ID   = c.CENTRO_ID
         LEFT JOIN regional r  ON c.REGIONAL_ID = r.REGIONAL_ID
         LEFT JOIN instructor i2 ON i2.GESTOR_FICHA_ID = f.FICHA_ID
         WHERE f.ESTADO='Activa' ORDER BY f.CODIGO_FICHA"
    );
} else {
    $fichas = $db->varios(
        "SELECT f.FICHA_ID, f.CODIGO_FICHA, p.NOMBRE AS programa,
                c.NOMBRE AS centro, r.NOMBRE AS regional,
                COALESCE(i2.INSTRUCTOR_ID,0) AS gestor_id,
                CONCAT(COALESCE(i2.NOMBRES,''),' ',COALESCE(i2.APELLIDOS,'')) AS gestor_nombre
         FROM instructor_ficha ifi
         INNER JOIN ficha    f  ON ifi.FICHA_ID   = f.FICHA_ID
         LEFT  JOIN programa p  ON f.PROGRAMA_ID  = p.PROGRAMA_ID
         LEFT  JOIN centro   c  ON f.CENTRO_ID    = c.CENTRO_ID
         LEFT  JOIN regional r  ON c.REGIONAL_ID  = r.REGIONAL_ID
         LEFT  JOIN instructor i2 ON i2.GESTOR_FICHA_ID = f.FICHA_ID
         WHERE ifi.INSTRUCTOR_ID = :id AND f.ESTADO='Activa'
         ORDER BY f.CODIGO_FICHA",
        [':id' => $ref]
    );
}

$fichaId   = (int)($_GET['ficha']     ?? ($fichas[0]['FICHA_ID'] ?? 0));
$trimestre = $_GET['trimestre']       ?? date('Y').'-1';

// ¿Es gestor de ESTA ficha en particular?
$esMiGestor = false;
if (esAdmin()) {
    $esMiGestor = true;
} elseif ($ref > 0 && $fichaId > 0) {
    $chk = $db->uno(
        "SELECT INSTRUCTOR_ID FROM instructor
         WHERE INSTRUCTOR_ID=:id AND GESTOR_FICHA_ID=:fid",
        [':id'=>$ref, ':fid'=>$fichaId]
    );
    $esMiGestor = !empty($chk);
}

$msg=''; $msgType='success';

// POST solo para gestores/admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $esMiGestor) {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear') {
        $fid   = (int)$_POST['ficha_id'];
        $dia   = (int)$_POST['dia_semana'];
        $hi    = $_POST['hora_inicio'];
        $hf    = $_POST['hora_fin'];
        $mat   = trim($_POST['materia']);
        $aula  = trim($_POST['aula'] ?? '');
        $tri   = trim($_POST['trimestre']);
        $desde = $_POST['fecha_desde'];
        $hasta = $_POST['fecha_hasta'];
        $instructorId = (int)($_POST['instructor_id'] ?? $ref);
        if ($instructorId <= 0) $instructorId = $ref;

        if ($mat && $hi && $hf && $dia && $tri && $desde && $hasta) {
            $db->exec(
                "INSERT INTO horario (FICHA_ID,INSTRUCTOR_ID,DIA_SEMANA,HORA_INICIO,HORA_FIN,
                  MATERIA,AULA,TRIMESTRE,FECHA_DESDE,FECHA_HASTA)
                 VALUES (:fid,:iid,:dia,:hi,:hf,:mat,:aula,:tri,:desde,:hasta)",
                [':fid'=>$fid,':iid'=>$instructorId,':dia'=>$dia,':hi'=>$hi,':hf'=>$hf,
                 ':mat'=>$mat,':aula'=>$aula,':tri'=>$tri,':desde'=>$desde,':hasta'=>$hasta]
            );
            $msg='✓ Bloque agregado.'; $fichaId=$fid; $trimestre=$tri;
        } else {
            $msg='✗ Completa todos los campos.'; $msgType='error';
        }
    } elseif ($accion === 'eliminar') {
        $db->exec("DELETE FROM horario WHERE HORARIO_ID=:id", [':id'=>(int)$_POST['horario_id']]);
        $msg='✓ Bloque eliminado.';
    }
}

// Cargar horario actual
$horario = $fichaId ? $db->varios(
    "SELECT h.*, i.NOMBRES AS inst_n, i.APELLIDOS AS inst_a, i.INSTRUCTOR_ID AS inst_id
     FROM horario h 
     LEFT JOIN instructor i ON h.INSTRUCTOR_ID = i.INSTRUCTOR_ID
     WHERE h.FICHA_ID=:fid AND h.TRIMESTRE=:tri
     ORDER BY h.DIA_SEMANA, h.HORA_INICIO",
    [':fid'=>$fichaId,':tri'=>$trimestre]
) : [];

$fichaInfo = $fichaId ? (array_values(array_filter($fichas, fn($f) => $f['FICHA_ID']==$fichaId))[0] ?? []) : [];

// Lista de instructores para el selector
if (esAdmin()) {
    $instructoresList = $db->varios(
        "SELECT INSTRUCTOR_ID, NOMBRES, APELLIDOS FROM instructor ORDER BY NOMBRES"
    );
} else {
    $instructoresList = $db->varios(
        "SELECT i.INSTRUCTOR_ID, i.NOMBRES, i.APELLIDOS
         FROM instructor i
         JOIN instructor_ficha ifi ON i.INSTRUCTOR_ID = ifi.INSTRUCTOR_ID
         WHERE ifi.FICHA_ID = :fid
         UNION
         SELECT INSTRUCTOR_ID, NOMBRES, APELLIDOS FROM instructor WHERE GESTOR_FICHA_ID = :fid2",
        [':fid' => $fichaId, ':fid2' => $fichaId]
    );
}

$dias = [1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado'];
$trimestres = [];
for ($y=date('Y')-1;$y<=date('Y')+2;$y++){
    $trimestres[]=$y.'-1'; $trimestres[]=$y.'-2'; $trimestres[]=$y.'-3';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Horarios — DTD SENA</title>
<link rel="stylesheet" href="<?= htmlspecialchars(asset_url('css/style.css')) ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
.form-card{background:var(--color-blanco);border:1px solid var(--border-color);border-radius:18px;padding:24px;box-shadow:var(--shadow-card);margin-bottom:22px;}
.s-title{font-size:13px;font-weight:800;color:var(--color-verde-1);text-transform:uppercase;letter-spacing:.5px;margin-bottom:14px;padding-bottom:8px;border-bottom:2px solid rgba(57,169,0,.15);display:flex;align-items:center;gap:8px;}
.f-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;}
.fg{display:flex;flex-direction:column;gap:4px;}
.fg label{font-size:10px;font-weight:700;color:var(--color-texto-secundario);text-transform:uppercase;letter-spacing:.4px;}
.fg input,.fg select{padding:8px 11px;border:1px solid var(--border-color);border-radius:8px;font-size:13px;background:var(--color-blanco);color:var(--color-texto);}
.fg input:focus,.fg select:focus{outline:none;border-color:var(--color-verde-1);}
.ficha-tab{display:inline-flex;align-items:center;gap:6px;padding:7px 13px;border-radius:20px;font-size:12px;font-weight:700;border:2px solid var(--border-color);color:var(--color-texto-secundario);text-decoration:none;margin:3px;transition:.2s;}
.ficha-tab.active{background:var(--color-verde-1);color:#fff;border-color:var(--color-verde-1);}
.ficha-tab:hover:not(.active){border-color:var(--color-verde-1);color:var(--color-verde-1);}
.badge-g{display:inline-flex;align-items:center;gap:5px;background:rgba(245,158,11,.1);color:#d97706;border:1px solid rgba(245,158,11,.3);padding:3px 9px;border-radius:20px;font-size:11px;font-weight:700;}
.badge-ro{display:inline-flex;align-items:center;gap:5px;background:rgba(100,116,139,.1);color:#475569;border:1px solid rgba(100,116,139,.3);padding:3px 9px;border-radius:20px;font-size:11px;font-weight:700;}
.msg-ok{background:rgba(57,169,0,.1);border:1px solid rgba(57,169,0,.3);color:#1a5c00;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-weight:600;font-size:13px;}
.msg-err{background:rgba(220,38,38,.1);border:1px solid rgba(220,38,38,.3);color:#991b1b;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-weight:600;font-size:13px;}
.readonly-notice{background:rgba(59,130,246,.07);border:1px solid rgba(59,130,246,.2);color:#1e40af;padding:12px 16px;border-radius:10px;margin-bottom:20px;font-size:13px;display:flex;align-items:center;gap:10px;}
/* Estilos para el buscador de instructores */
.instructor-search-wrapper{position:relative;width:100%;}
.instructor-search-wrapper input{width:100%;padding:8px 11px;border:1px solid var(--border-color);border-radius:8px;font-size:13px;background:var(--color-blanco);color:var(--color-texto);}
.instructor-search-wrapper input:focus{outline:none;border-color:var(--color-verde-1);}
.instructor-dropdown{position:absolute;top:100%;left:0;right:0;background:var(--color-blanco);border:1px solid var(--border-color);border-top:none;border-radius:0 0 8px 8px;max-height:200px;overflow-y:auto;z-index:100;display:none;box-shadow:var(--shadow-card);}
.instructor-dropdown .instructor-option{padding:8px 11px;cursor:pointer;font-size:13px;border-bottom:1px solid var(--border-color);}
.instructor-dropdown .instructor-option:hover{background:var(--color-verde-3);color:var(--color-verde-1);}
.instructor-dropdown .instructor-option.selected{background:var(--color-verde-1);color:white;}
</style>
</head>
<body>
<div id="loader"><img src="<?= htmlspecialchars(asset_url('img/logo_sena_verde.png')) ?>" alt="" id="loader-logo"></div>
<?php include __DIR__ . '/../config/header.php'; ?>
<main class="container" id="contenido-principal" style="display:none;opacity:0;">

<div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;flex-wrap:wrap;">
    <?php if (esInstructor()): ?>
    <a href="<?= htmlspecialchars(app_url('mod/instructor_dashboard.php')) ?>" class="btn-view-all"><i class="fas fa-arrow-left"></i> Dashboard</a>
    <?php else: ?>
    <a href="<?= htmlspecialchars(app_url('index.php')) ?>" class="btn-view-all"><i class="fas fa-arrow-left"></i> Inicio</a>
    <?php endif; ?>
    <span style="font-size:13px;color:var(--color-texto-secundario);"><i class="fas fa-calendar-week"></i> Horarios</span>
    <?php if ($esMiGestor): ?>
    <span class="badge-g"><i class="fas fa-crown"></i> Gestor de esta ficha</span>
    <?php elseif (esInstructor()): ?>
    <span class="badge-ro"><i class="fas fa-eye"></i> Solo lectura</span>
    <?php endif; ?>
</div>

<?php if ($msg): ?>
<div class="<?= $msgType==='success'?'msg-ok':'msg-err' ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<?php if (empty($fichas)): ?>
<div style="text-align:center;padding:50px;background:var(--color-blanco);border-radius:18px;border:2px dashed var(--border-color);">
    <i class="fas fa-layer-group" style="font-size:48px;opacity:.2;display:block;margin-bottom:12px;"></i>
    <p style="color:var(--color-texto-secundario);">No tienes fichas asignadas.</p>
</div>
<?php else: ?>

<!-- Selector fichas -->
<div style="margin-bottom:18px;">
    <p style="font-size:11px;font-weight:700;color:var(--color-texto-secundario);margin-bottom:6px;text-transform:uppercase;">FICHA</p>
    <?php foreach ($fichas as $f): ?>
    <?php $esGestorEstaFicha = esAdmin() || ($ref > 0 && !empty($f['gestor_id']) && $f['gestor_id'] == $ref); ?>
    <a href="?ficha=<?= $f['FICHA_ID'] ?>&trimestre=<?= urlencode($trimestre) ?>"
       class="ficha-tab <?= $f['FICHA_ID']==$fichaId?'active':'' ?>">
        <i class="fas fa-layer-group"></i>
        <?= htmlspecialchars($f['CODIGO_FICHA']) ?>
        <?php if ($esGestorEstaFicha): ?>
        <i class="fas fa-crown" style="color:#f59e0b;font-size:10px;" title="Eres gestor de esta ficha"></i>
        <?php endif; ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Selector trimestre -->
<div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;flex-wrap:wrap;">
    <span style="font-size:11px;font-weight:700;color:var(--color-texto-secundario);text-transform:uppercase;">TRIMESTRE</span>
    <form method="GET" style="display:flex;gap:8px;align-items:center;">
        <input type="hidden" name="ficha" value="<?= $fichaId ?>">
        <select name="trimestre" onchange="this.form.submit()"
                style="padding:7px 12px;border:1px solid var(--border-color);border-radius:8px;font-size:13px;background:var(--color-blanco);color:var(--color-texto);">
            <?php foreach ($trimestres as $t): ?>
            <option value="<?= $t ?>" <?= $t===$trimestre?'selected':'' ?>><?= $t ?></option>
            <?php endforeach; ?>
        </select>
    </form>
    <?php if ($fichaInfo): ?>
    <span style="font-size:12px;color:var(--color-texto-secundario);">
        <i class="fas fa-graduation-cap"></i>
        <?= htmlspecialchars($fichaInfo['programa']??'') ?> — <?= htmlspecialchars($fichaInfo['centro']??'') ?>
    </span>
    <?php if (!empty($fichaInfo['gestor_nombre']) && trim($fichaInfo['gestor_nombre'])): ?>
    <span style="font-size:12px;color:var(--color-texto-secundario);">
        <i class="fas fa-crown" style="color:#f59e0b;"></i>
        Gestor: <?= htmlspecialchars(trim($fichaInfo['gestor_nombre'])) ?>
    </span>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php if (!$esMiGestor && esInstructor()): ?>
<div class="readonly-notice">
    <i class="fas fa-info-circle" style="font-size:18px;flex-shrink:0;"></i>
    <span>Eres instructor de esta ficha pero no su gestor. Puedes consultar el horario pero no editarlo.</span>
</div>
<?php endif; ?>

<!-- Formulario SOLO para gestor/admin -->
<?php if ($esMiGestor): ?>
<div class="form-card">
    <div class="s-title"><i class="fas fa-plus-circle"></i> Agregar Bloque al Trimestre <?= htmlspecialchars($trimestre) ?></div>
    <form method="POST" id="formHorario">
        <input type="hidden" name="accion" value="crear">
        <input type="hidden" name="ficha_id" value="<?= $fichaId ?>">
        <div class="f-row" style="margin-bottom:14px;">
            <div class="fg"><label>Día *</label>
                <select name="dia_semana" required>
                    <?php for($d=1;$d<=6;$d++): ?><option value="<?=$d?>"><?=$dias[$d]?></option><?php endfor; ?>
                </select>
            </div>
            <div class="fg"><label>Hora inicio *</label><input type="time" name="hora_inicio" required></div>
            <div class="fg"><label>Hora fin *</label><input type="time" name="hora_fin" required></div>
            <div class="fg"><label>Materia *</label><input type="text" name="materia" placeholder="Ej: Bases de Datos" required></div>
            <div class="fg"><label>Aula</label><input type="text" name="aula" placeholder="Ej: 201"></div>
            <div class="fg"><label>Trimestre *</label>
                <select name="trimestre" required>
                    <?php foreach($trimestres as $t): ?><option value="<?=$t?>" <?=$t===$trimestre?'selected':''?>><?=$t?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="fg"><label>Desde *</label><input type="date" name="fecha_desde" required></div>
            <div class="fg"><label>Hasta *</label><input type="date" name="fecha_hasta" required></div>
            <!-- Instructor asignado con buscador -->
            <div class="fg">
                <label>Instructor que dicta *</label>
                <div class="instructor-search-wrapper">
                    <input type="text" id="instructorSearch" placeholder="Buscar instructor por nombre..." autocomplete="off">
                    <input type="hidden" name="instructor_id" id="instructorId" required>
                    <div id="instructorDropdown" class="instructor-dropdown"></div>
                </div>
                <small style="font-size:10px;color:var(--color-texto-secundario);">Escribe el nombre del instructor para buscarlo</small>
            </div>
        </div>
        <button type="submit" class="btn-action"><i class="fas fa-plus"></i> Agregar bloque</button>
    </form>
</div>

<script>
// Datos de instructores desde PHP
const instructores = <?php 
    $lista = [];
    foreach ($instructoresList as $ins) {
        $lista[] = [
            'id' => $ins['INSTRUCTOR_ID'],
            'nombre' => $ins['NOMBRES'] . ' ' . $ins['APELLIDOS']
        ];
    }
    echo json_encode($lista);
?>;

const searchInput = document.getElementById('instructorSearch');
const instructorIdInput = document.getElementById('instructorId');
const dropdown = document.getElementById('instructorDropdown');

function renderDropdown(filterText = '') {
    const filtered = instructores.filter(inst => 
        inst.nombre.toLowerCase().includes(filterText.toLowerCase())
    );
    
    if (filtered.length === 0) {
        dropdown.innerHTML = '<div class="instructor-option" style="color:var(--color-texto-secundario);">No se encontraron instructores</div>';
        dropdown.style.display = 'block';
        return;
    }
    
    dropdown.innerHTML = '';
    filtered.forEach(inst => {
        const option = document.createElement('div');
        option.className = 'instructor-option';
        option.textContent = inst.nombre;
        option.dataset.id = inst.id;
        option.dataset.nombre = inst.nombre;
        option.onclick = () => {
            searchInput.value = inst.nombre;
            instructorIdInput.value = inst.id;
            dropdown.style.display = 'none';
        };
        dropdown.appendChild(option);
    });
    dropdown.style.display = 'block';
}

searchInput.addEventListener('input', (e) => {
    renderDropdown(e.target.value);
});

searchInput.addEventListener('focus', () => {
    renderDropdown(searchInput.value);
});

document.addEventListener('click', (e) => {
    if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.style.display = 'none';
    }
});

// Validar que se haya seleccionado un instructor antes de enviar
document.getElementById('formHorario').addEventListener('submit', (e) => {
    if (!instructorIdInput.value) {
        e.preventDefault();
        alert('Por favor selecciona un instructor de la lista');
    }
});
</script>
<?php endif; ?>

<!-- Tabla horario -->
<?php if (!empty($horario)): ?>
<div class="form-card">
    <div class="s-title">
        <i class="fas fa-list-alt"></i>
        Horario trimestre <?= htmlspecialchars($trimestre) ?>
        <span style="margin-left:auto;font-weight:400;font-size:11px;color:var(--color-texto-secundario);"><?= count($horario) ?> bloques</span>
    </div>
    <div style="overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead><tr>
            <th style="background:var(--color-gris-fondo);padding:9px 12px;text-align:left;border-bottom:2px solid var(--border-color);font-size:11px;font-weight:700;color:var(--color-texto-secundario);text-transform:uppercase;">Día</th>
            <th style="background:var(--color-gris-fondo);padding:9px 12px;text-align:left;border-bottom:2px solid var(--border-color);font-size:11px;font-weight:700;color:var(--color-texto-secundario);text-transform:uppercase;">Horario</th>
            <th style="background:var(--color-gris-fondo);padding:9px 12px;text-align:left;border-bottom:2px solid var(--border-color);font-size:11px;font-weight:700;color:var(--color-texto-secundario);text-transform:uppercase;">Materia</th>
            <th style="background:var(--color-gris-fondo);padding:9px 12px;text-align:left;border-bottom:2px solid var(--border-color);font-size:11px;font-weight:700;color:var(--color-texto-secundario);text-transform:uppercase;">Aula</th>
            <th style="background:var(--color-gris-fondo);padding:9px 12px;text-align:left;border-bottom:2px solid var(--border-color);font-size:11px;font-weight:700;color:var(--color-texto-secundario);text-transform:uppercase;">Instructor</th>
            <?php if ($esMiGestor): ?><th style="background:var(--color-gris-fondo);border-bottom:2px solid var(--border-color);width:50px;"></th><?php endif; ?>
        </tr></thead>
        <tbody>
        <?php foreach($horario as $h): ?>
        <tr>
            <td style="padding:10px 12px;border-bottom:1px solid var(--border-color);font-weight:700;color:var(--color-verde-1);"><?=$dias[$h['DIA_SEMANA']]?></td>
            <td style="padding:10px 12px;border-bottom:1px solid var(--border-color);font-size:12px;white-space:nowrap;"><?=substr($h['HORA_INICIO'],0,5)?> – <?=substr($h['HORA_FIN'],0,5)?></td>
            <td style="padding:10px 12px;border-bottom:1px solid var(--border-color);font-weight:600;"><?=htmlspecialchars($h['MATERIA'])?></td>
            <td style="padding:10px 12px;border-bottom:1px solid var(--border-color);color:var(--color-texto-secundario);"><?=htmlspecialchars($h['AULA']??'—')?></td>
            <td style="padding:10px 12px;border-bottom:1px solid var(--border-color);font-size:12px;">
                <?php if ($h['inst_id']): ?>
                    <span style="display:inline-flex;align-items:center;gap:4px;"><i class="fas fa-chalkboard-teacher" style="color:var(--color-verde-1);"></i> <?=htmlspecialchars(trim($h['inst_n'].' '.$h['inst_a']))?></span>
                <?php else: ?>
                    <span style="color:var(--color-texto-secundario);">— Sin asignar —</span>
                <?php endif; ?>
            </td>
            <?php if ($esMiGestor): ?>
            <td style="padding:10px 12px;border-bottom:1px solid var(--border-color);text-align:center;">
                <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar este bloque?')">
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="horario_id" value="<?=$h['HORARIO_ID']?>">
                    <button type="submit" style="background:rgba(220,38,38,.1);color:#dc2626;border:1px solid rgba(220,38,38,.25);padding:5px 9px;border-radius:6px;cursor:pointer;font-size:11px;font-weight:700;"><i class="fas fa-trash"></i></button>
                </form>
            </td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php else: ?>
<div style="text-align:center;padding:30px;background:var(--color-blanco);border-radius:14px;border:2px dashed var(--border-color);">
    <i class="fas fa-calendar-plus" style="font-size:36px;opacity:.2;display:block;margin-bottom:8px;"></i>
    <p style="color:var(--color-texto-secundario);font-size:13px;">
        Sin bloques para el trimestre <?= htmlspecialchars($trimestre) ?>.
        <?= $esMiGestor ? 'Agrega el primero arriba.' : 'El gestor aún no ha cargado el horario.' ?>
    </p>
</div>
<?php endif; ?>

<?php endif; ?>
</main>
<?php include __DIR__ . '/../config/footer.php'; ?>
<script src="<?= htmlspecialchars(asset_url('js/tema.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/loader.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/panel_menu.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/dropdowns.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/profile_menu.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/menu.js')) ?>"></script>
</body>
</html>