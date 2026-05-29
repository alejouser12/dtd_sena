<?php
// mod/crud/crear_instructor.php
session_start();
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../conexion/conexion.php';
if (!esAdmin()) redirect_to('login.php');

class CrearInstructorDB extends BaseDatos {
    protected function consultar(){}protected function insertar(){}
    protected function actualizar(){}protected function eliminar(){}
    public function varios($sql,$p=[]){ $s=$this->ejecutarPreparado($sql,$p); return $s?$s->fetchAll(PDO::FETCH_ASSOC):[]; }
    public function exec($sql,$p=[]){ return $this->ejecutarPreparado($sql,$p); }
    public function lastId(){ $s=$this->ejecutarPreparado("SELECT LAST_INSERT_ID() AS id",[]); $r=$s?$s->fetch(PDO::FETCH_ASSOC):null; return $r?(int)$r['id']:0; }
}
$db = new CrearInstructorDB();

$regionales = $db->varios("SELECT REGIONAL_ID, NOMBRE FROM regional WHERE ESTADO='Activa' ORDER BY NOMBRE");
$centros    = $db->varios("SELECT CENTRO_ID, NOMBRE, REGIONAL_ID FROM centro ORDER BY NOMBRE");
$fichas     = $db->varios(
    "SELECT f.FICHA_ID, f.CODIGO_FICHA, f.CENTRO_ID, p.NOMBRE AS programa
     FROM ficha f LEFT JOIN programa p ON f.PROGRAMA_ID=p.PROGRAMA_ID
     WHERE f.ESTADO='Activa' ORDER BY f.CODIGO_FICHA"
);

$msg=''; $msgType='success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombres      = trim($_POST['nombres']      ?? '');
    $apellidos    = trim($_POST['apellidos']    ?? '');
    $email        = trim($_POST['email']        ?? '');
    $espec        = trim($_POST['especialidad'] ?? '');
    $fichasSel    = (array)($_POST['fichas']    ?? []);
    $gestorFicha  = (int)($_POST['gestor_ficha_id'] ?? 0);
    $userEmail    = trim($_POST['user_email']   ?? $email);
    $userPass     = trim($_POST['user_pass']    ?? '');

    if (!$nombres || !$apellidos || !$email) {
        $msg='✗ Completa los campos obligatorios.'; $msgType='error';
    } elseif (strlen($userPass) < 6) {
        $msg='✗ La contraseña debe tener al menos 6 caracteres.'; $msgType='error';
    } else {
        // Validar que ficha gestor esté entre las fichas seleccionadas
        if ($gestorFicha && !in_array((string)$gestorFicha, $fichasSel)) {
            $gestorFicha = 0;
        }

        $db->exec(
            "INSERT INTO instructor (NOMBRES,APELLIDOS,EMAIL,ESPECIALIDAD,GESTOR_FICHA_ID)
             VALUES (:n,:a,:e,:es,:gf)",
            [':n'=>$nombres,':a'=>$apellidos,':e'=>$email,':es'=>$espec,
             ':gf'=>($gestorFicha ?: null)]
        );
        $instructorId = $db->lastId();

        if ($instructorId) {
            foreach ($fichasSel as $fid) {
                $fid=(int)$fid;
                if ($fid) $db->exec(
                    "INSERT IGNORE INTO instructor_ficha (INSTRUCTOR_ID,FICHA_ID) VALUES (:i,:f)",
                    [':i'=>$instructorId,':f'=>$fid]
                );
            }
            $passHash = password_hash($userPass, PASSWORD_BCRYPT);
            $db->exec(
                "INSERT INTO usuarios (email,password,nombre,rol,referencia_id)
                 VALUES (:em,:pw,:nom,'instructor',:rid)
                 ON DUPLICATE KEY UPDATE referencia_id=:rid2,password=:pw2",
                [':em'=>$userEmail,':pw'=>$passHash,':nom'=>"$nombres $apellidos",
                 ':rid'=>$instructorId,':rid2'=>$instructorId,':pw2'=>$passHash]
            );
            $tipo = $gestorFicha ? "(Gestor de ficha $gestorFicha)" : "(Instructor regular)";
            $msg  = "✓ Instructor creado $tipo. Usuario: $userEmail — ".count($fichasSel)." ficha(s) asignada(s)";
        } else {
            $msg='✗ Error al crear el instructor.'; $msgType='error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Crear Instructor — DTD SENA</title>
<link rel="stylesheet" href="<?= htmlspecialchars(asset_url('css/style.css')) ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
.form-card{background:var(--color-blanco);border:1px solid var(--border-color);border-radius:18px;padding:28px;box-shadow:var(--shadow-card);margin-bottom:22px;}
.s-title{font-size:13px;font-weight:800;color:var(--color-verde-1);text-transform:uppercase;letter-spacing:.5px;margin-bottom:14px;padding-bottom:8px;border-bottom:2px solid rgba(57,169,0,.15);display:flex;align-items:center;gap:8px;}
.f-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;}
.fg{display:flex;flex-direction:column;gap:4px;}
.fg label{font-size:11px;font-weight:700;color:var(--color-texto-secundario);text-transform:uppercase;letter-spacing:.4px;}
.fg input,.fg select{padding:9px 12px;border:1px solid var(--border-color);border-radius:8px;font-size:13px;background:var(--color-blanco);color:var(--color-texto);width:100%;}
.fg input:focus,.fg select:focus{outline:none;border-color:var(--color-verde-1);box-shadow:0 0 0 3px rgba(57,169,0,.08);}
.cascade-box{padding:16px;background:rgba(57,169,0,.04);border-radius:12px;border:1px solid rgba(57,169,0,.15);margin-bottom:14px;}
.fichas-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:8px;max-height:220px;overflow-y:auto;padding:4px;}
.ficha-item{display:flex;align-items:center;gap:8px;padding:8px 10px;border:1px solid var(--border-color);border-radius:8px;font-size:12px;cursor:pointer;transition:.15s;}
.ficha-item:hover{border-color:var(--color-verde-1);background:rgba(57,169,0,.04);}
.ficha-item input[type=checkbox]{accent-color:var(--color-verde-1);width:15px;height:15px;flex-shrink:0;}
.gestor-box{background:rgba(245,158,11,.06);border:1px solid rgba(245,158,11,.25);border-radius:12px;padding:14px;margin-top:14px;display:none;}
.gestor-box label{font-size:12px;font-weight:700;color:#92400e;display:flex;align-items:center;gap:6px;margin-bottom:8px;}
.msg-ok{background:rgba(57,169,0,.1);border:1px solid rgba(57,169,0,.3);color:#1a5c00;padding:12px 16px;border-radius:8px;margin-bottom:18px;font-weight:600;font-size:13px;}
.msg-err{background:rgba(220,38,38,.1);border:1px solid rgba(220,38,38,.3);color:#991b1b;padding:12px 16px;border-radius:8px;margin-bottom:18px;font-weight:600;font-size:13px;}
.pw-wrap{position:relative;}
.pw-wrap input{padding-right:38px;}
.pw-eye{position:absolute;right:10px;top:50%;transform:translateY(-50%);cursor:pointer;color:var(--color-texto-secundario);font-size:14px;background:none;border:none;}
select:disabled{opacity:.45;}
#fichas-panel{display:none;}
</style>
</head>
<body>
<div id="loader"><img src="<?= htmlspecialchars(asset_url('img/logo_sena_verde.png')) ?>" alt="" id="loader-logo"></div>
<?php include __DIR__ . '/../../config/header.php'; ?>
<main class="container" id="contenido-principal" style="display:none;opacity:0;">

<div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;">
    <a href="<?= htmlspecialchars(app_url('mod/instructores.php')) ?>" class="btn-view-all"><i class="fas fa-arrow-left"></i> Instructores</a>
    <span style="font-size:13px;color:var(--color-texto-secundario);"><i class="fas fa-chalkboard-teacher"></i> Nuevo Instructor</span>
</div>

<?php if ($msg): ?>
<div class="<?= $msgType==='success'?'msg-ok':'msg-err' ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<form method="POST">

<!-- Datos -->
<div class="form-card">
    <div class="s-title"><i class="fas fa-user-tie"></i> Datos del Instructor</div>
    <div class="f-row">
        <div class="fg"><label>Nombres *</label><input type="text" name="nombres" required placeholder="Nombres completos"></div>
        <div class="fg"><label>Apellidos *</label><input type="text" name="apellidos" required placeholder="Apellidos completos"></div>
        <div class="fg"><label>Correo institucional *</label><input type="email" name="email" id="email_i" required placeholder="nombre@sena.edu.co" oninput="syncEmail()"></div>
        <div class="fg"><label>Especialidad</label><input type="text" name="especialidad" placeholder="Ej: Desarrollo de Software"></div>
    </div>
</div>

<!-- Fichas -->
<div class="form-card">
    <div class="s-title"><i class="fas fa-layer-group"></i> Asignar Fichas y Gestoría</div>
    <div class="cascade-box">
        <div class="f-row" style="margin-bottom:0;">
            <div class="fg">
                <label>Regional</label>
                <select id="sel_regional" onchange="filtrarCentros()">
                    <option value="">— Selecciona —</option>
                    <?php foreach ($regionales as $r): ?>
                    <option value="<?= $r['REGIONAL_ID'] ?>"><?= htmlspecialchars($r['NOMBRE']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fg">
                <label>Centro</label>
                <select id="sel_centro" onchange="filtrarFichas()" disabled>
                    <option value="">— Elige regional —</option>
                </select>
            </div>
        </div>
    </div>

    <div id="fichas-panel">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
            <label style="font-size:11px;font-weight:700;color:var(--color-texto-secundario);text-transform:uppercase;">Fichas en las que dicta clase</label>
            <span id="sel-count" style="font-size:11px;color:var(--color-verde-1);font-weight:700;">0 seleccionadas</span>
        </div>
        <div class="fichas-grid" id="fichas-grid"></div>

        <!-- Gestor de una sola ficha -->
        <div class="gestor-box" id="gestor-box">
            <label><i class="fas fa-crown" style="color:#f59e0b;"></i> ¿De cuál ficha es Gestor de Grupo?</label>
            <select name="gestor_ficha_id" id="gestor_ficha_sel"
                    style="padding:9px 12px;border:1px solid rgba(245,158,11,.4);border-radius:8px;font-size:13px;background:var(--color-blanco);width:100%;color:var(--color-texto);">
                <option value="0">— No es gestor de ninguna —</option>
            </select>
            <p style="font-size:11px;color:#92400e;margin-top:6px;">
                <i class="fas fa-info-circle"></i>
                El gestor puede crear y editar el horario de esa ficha. Solo puede ser gestor de <strong>una</strong>.
            </p>
        </div>
    </div>
</div>

<!-- Credenciales -->
<div class="form-card">
    <div class="s-title"><i class="fas fa-key"></i> Credenciales de Acceso</div>
    <div class="f-row">
        <div class="fg">
            <label>Correo de usuario *</label>
            <input type="email" name="user_email" id="user_email" required placeholder="correo para iniciar sesión">
        </div>
        <div class="fg">
            <label>Contraseña *</label>
            <div class="pw-wrap">
                <input type="password" name="user_pass" id="p1" required minlength="6" placeholder="Mínimo 6 caracteres">
                <button type="button" class="pw-eye" onclick="eye('p1',this)"><i class="fas fa-eye"></i></button>
            </div>
        </div>
        <div class="fg">
            <label>Confirmar contraseña *</label>
            <div class="pw-wrap">
                <input type="password" id="p2" required minlength="6" placeholder="Repite la contraseña">
                <button type="button" class="pw-eye" onclick="eye('p2',this)"><i class="fas fa-eye"></i></button>
            </div>
        </div>
    </div>
</div>

<div style="display:flex;gap:10px;">
    <button type="submit" class="btn-action" onclick="return check()"><i class="fas fa-save"></i> Crear Instructor</button>
    <a href="<?= htmlspecialchars(app_url('mod/instructores.php')) ?>" class="btn-cancel">Cancelar</a>
</div>
</form>
</main>

<?php include __DIR__ . '/../../config/footer.php'; ?>
<script src="<?= htmlspecialchars(asset_url('js/tema.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/loader.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/panel_menu.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/dropdowns.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/profile_menu.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/menu.js')) ?>"></script>
<script>
const CENTROS = <?= json_encode($centros) ?>;
const FICHAS  = <?= json_encode($fichas) ?>;

function filtrarCentros() {
    const rid = parseInt(document.getElementById('sel_regional').value);
    const s   = document.getElementById('sel_centro');
    s.innerHTML = '<option value="">— Selecciona centro —</option>';
    s.disabled  = true;
    document.getElementById('fichas-panel').style.display = 'none';
    if (!rid) return;
    CENTROS.filter(c => parseInt(c.REGIONAL_ID)===rid)
           .forEach(c => s.innerHTML += `<option value="${c.CENTRO_ID}">${c.NOMBRE}</option>`);
    s.disabled = false;
}

function filtrarFichas() {
    const cid   = parseInt(document.getElementById('sel_centro').value);
    const panel = document.getElementById('fichas-panel');
    const grid  = document.getElementById('fichas-grid');
    if (!cid) { panel.style.display='none'; return; }
    const lista = FICHAS.filter(f => parseInt(f.CENTRO_ID)===cid);
    panel.style.display = 'block';
    document.getElementById('gestor-box').style.display = 'none';
    if (!lista.length) {
        grid.innerHTML='<p style="color:var(--color-texto-secundario);font-size:12px;padding:10px;">Sin fichas activas en este centro.</p>';
        return;
    }
    grid.innerHTML = lista.map(f => `
        <label class="ficha-item">
            <input type="checkbox" name="fichas[]" value="${f.FICHA_ID}" onchange="actualizarGestor()">
            <div>
                <strong>${f.CODIGO_FICHA}</strong>
                <div style="font-size:10px;opacity:.7;">${f.programa||''}</div>
            </div>
        </label>`).join('');
}

function actualizarGestor() {
    const checks = [...document.querySelectorAll('input[name="fichas[]"]:checked')];
    const n = checks.length;
    document.getElementById('sel-count').textContent = n + ' seleccionada' + (n!==1?'s':'');

    const gBox = document.getElementById('gestor-box');
    const gSel = document.getElementById('gestor_ficha_sel');

    if (!n) { gBox.style.display='none'; return; }

    // Reconstruir select del gestor con las fichas seleccionadas
    gSel.innerHTML = '<option value="0">— No es gestor de ninguna —</option>';
    checks.forEach(cb => {
        // Buscar nombre de la ficha
        const ficha = FICHAS.find(f => f.FICHA_ID == cb.value);
        if (ficha) {
            gSel.innerHTML += `<option value="${ficha.FICHA_ID}">${ficha.CODIGO_FICHA} — ${ficha.programa||''}</option>`;
        }
    });
    gBox.style.display = 'block';
}

function syncEmail() {
    document.getElementById('user_email').value = document.getElementById('email_i').value;
}
function eye(id, btn) {
    const i=document.getElementById(id);
    i.type=i.type==='password'?'text':'password';
    btn.innerHTML=i.type==='password'?'<i class="fas fa-eye"></i>':'<i class="fas fa-eye-slash"></i>';
}
function check() {
    if (document.getElementById('p1').value !== document.getElementById('p2').value) {
        alert('Las contraseñas no coinciden.'); return false;
    }
    return true;
}
</script>
</body>
</html>