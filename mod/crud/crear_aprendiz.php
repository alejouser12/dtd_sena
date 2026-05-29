<?php
// mod/crud/crear_aprendiz.php
session_start();
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../conexion/conexion.php';

if (!esAdmin() && !esInstructor()) redirect_to('login.php');

class CrearAprendizDB extends BaseDatos {
    protected function consultar(){}protected function insertar(){}
    protected function actualizar(){}protected function eliminar(){}
    public function varios($sql,$p=[]){
        $s=$this->ejecutarPreparado($sql,$p);
        return $s ? $s->fetchAll(PDO::FETCH_ASSOC) : [];
    }
    public function exec($sql,$p=[]){ return $this->ejecutarPreparado($sql,$p); }
    public function lastId(){
        $s=$this->ejecutarPreparado("SELECT LAST_INSERT_ID() AS id",[]);
        $r=$s?$s->fetch(PDO::FETCH_ASSOC):null;
        return $r?(int)$r['id']:0;
    }
}
$db = new CrearAprendizDB();

// Cargar TODOS los datos de una vez (sin AJAX)
$regionales = $db->varios("SELECT REGIONAL_ID, NOMBRE FROM regional WHERE ESTADO='Activa' ORDER BY NOMBRE");
$centros    = $db->varios("SELECT CENTRO_ID, NOMBRE, REGIONAL_ID FROM centro ORDER BY NOMBRE");
$programas  = $db->varios(
    "SELECT DISTINCT p.PROGRAMA_ID, p.NOMBRE, p.NIVEL_FORMACION, f.CENTRO_ID
     FROM programa p
     INNER JOIN ficha f ON f.PROGRAMA_ID = p.PROGRAMA_ID
     WHERE f.ESTADO = 'Activa'
     ORDER BY p.NOMBRE"
);
$fichas = $db->varios(
    "SELECT f.FICHA_ID, f.CODIGO_FICHA, f.PROGRAMA_ID, f.CENTRO_ID
     FROM ficha f WHERE f.ESTADO = 'Activa' ORDER BY f.CODIGO_FICHA"
);

$msg=''; $msgType='success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombres   = trim($_POST['nombres']   ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $tipodoc   = $_POST['tipo_documento'] ?? 'CC';
    $numdoc    = trim($_POST['numero_documento'] ?? '');
    $email     = trim($_POST['email']     ?? '');
    $tel       = trim($_POST['telefono']  ?? '');
    $nacim     = $_POST['fecha_nacimiento'] ?? null;
    $genero    = $_POST['genero']           ?? '';
    $fichaId   = (int)($_POST['ficha_id']   ?? 0);
    $estado    = $_POST['estado_academico'] ?? 'Activo';
    $userEmail = trim($_POST['user_email']  ?? $email);
    $userPass  = trim($_POST['user_pass']   ?? '');

    if (!$nombres || !$apellidos || !$numdoc || !$email || !$fichaId) {
        $msg='✗ Completa todos los campos obligatorios.'; $msgType='error';
    } elseif (strlen($userPass) < 6) {
        $msg='✗ La contraseña debe tener al menos 6 caracteres.'; $msgType='error';
    } else {
        $db->exec(
            "INSERT INTO aprendiz
             (TIPO_DOCUMENTO,NUMERO_DOCUMENTO,NOMBRES,APELLIDOS,EMAIL,TELEFONO,
              FECHA_NACIMIENTO,GENERO,ESTADO_ACADEMICO,FICHA_ID)
             VALUES (:td,:nd,:nom,:ape,:em,:tel,:nac,:gen,:est,:fid)",
            [':td'=>$tipodoc,':nd'=>$numdoc,':nom'=>$nombres,':ape'=>$apellidos,
             ':em'=>$email,':tel'=>$tel,':nac'=>($nacim?:null),
             ':gen'=>$genero,':est'=>$estado,':fid'=>$fichaId]
        );
        $aprendizId = $db->lastId();

        if ($aprendizId) {
            $passHash = password_hash($userPass, PASSWORD_BCRYPT);
            $db->exec(
                "INSERT INTO usuarios (email,password,nombre,rol,referencia_id)
                 VALUES (:em,:pw,:nom,'aprendiz',:rid)
                 ON DUPLICATE KEY UPDATE referencia_id=:rid2,password=:pw2",
                [':em'=>$userEmail,':pw'=>$passHash,
                 ':nom'=>"$nombres $apellidos",':rid'=>$aprendizId,
                 ':rid2'=>$aprendizId,':pw2'=>$passHash]
            );
            $usuarioId = $db->lastId();
            $db->exec("UPDATE aprendiz SET usuario_id=:uid WHERE APRENDIZ_ID=:aid",
                [':uid'=>$usuarioId,':aid'=>$aprendizId]);
            $msg="✓ Aprendiz creado. Usuario: $userEmail";
        } else {
            $msg='✗ Error al crear. ¿Número de documento duplicado?';
            $msgType='error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Crear Aprendiz — DTD SENA</title>
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
.cascade-box{padding:16px;background:rgba(57,169,0,.04);border-radius:12px;border:1px solid rgba(57,169,0,.15);}
.msg-ok{background:rgba(57,169,0,.1);border:1px solid rgba(57,169,0,.3);color:#1a5c00;padding:12px 16px;border-radius:8px;margin-bottom:18px;font-weight:600;font-size:13px;}
.msg-err{background:rgba(220,38,38,.1);border:1px solid rgba(220,38,38,.3);color:#991b1b;padding:12px 16px;border-radius:8px;margin-bottom:18px;font-weight:600;font-size:13px;}
.pw-wrap{position:relative;}
.pw-wrap input{padding-right:38px;}
.pw-eye{position:absolute;right:10px;top:50%;transform:translateY(-50%);cursor:pointer;color:var(--color-texto-secundario);font-size:14px;background:none;border:none;}
select:disabled{opacity:.45;cursor:not-allowed;}
</style>
</head>
<body>
<div id="loader"><img src="<?= htmlspecialchars(asset_url('img/logo_sena_verde.png')) ?>" alt="" id="loader-logo"></div>
<?php include __DIR__ . '/../../config/header.php'; ?>
<main class="container" id="contenido-principal" style="display:none;opacity:0;">

<div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;">
    <a href="<?= htmlspecialchars(app_url('mod/aprendices.php')) ?>" class="btn-view-all"><i class="fas fa-arrow-left"></i> Aprendices</a>
    <span style="font-size:13px;color:var(--color-texto-secundario);"><i class="fas fa-user-plus"></i> Nuevo Aprendiz</span>
</div>

<?php if ($msg): ?>
<div class="<?= $msgType==='success'?'msg-ok':'msg-err' ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<form method="POST">

<div class="form-card">
    <div class="s-title"><i class="fas fa-map-marked-alt"></i> Ubicación SENA</div>
    <div class="cascade-box">
        <div class="f-row">
            <div class="fg">
                <label>Regional *</label>
                <select id="sel_regional" onchange="filtrarCentros()">
                    <option value="">— Selecciona —</option>
                    <?php foreach ($regionales as $r): ?>
                    <option value="<?= $r['REGIONAL_ID'] ?>"><?= htmlspecialchars($r['NOMBRE']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fg">
                <label>Centro *</label>
                <select id="sel_centro" onchange="filtrarProgramas()" disabled>
                    <option value="">— Elige regional —</option>
                </select>
            </div>
            <div class="fg">
                <label>Programa *</label>
                <select id="sel_programa" onchange="filtrarFichas()" disabled>
                    <option value="">— Elige centro —</option>
                </select>
            </div>
            <div class="fg">
                <label>Ficha *</label>
                <select id="sel_ficha" name="ficha_id" disabled required>
                    <option value="">— Elige programa —</option>
                </select>
            </div>
        </div>
    </div>
</div>

<div class="form-card">
    <div class="s-title"><i class="fas fa-id-card"></i> Datos Personales</div>
    <div class="f-row">
        <div class="fg"><label>Nombres *</label><input type="text" name="nombres" required placeholder="Nombres completos"></div>
        <div class="fg"><label>Apellidos *</label><input type="text" name="apellidos" required placeholder="Apellidos completos"></div>
        <div class="fg">
            <label>Tipo documento</label>
            <select name="tipo_documento">
                <option value="CC">Cédula de Ciudadanía</option>
                <option value="TI">Tarjeta de Identidad</option>
                <option value="CE">Cédula Extranjería</option>
                <option value="PA">Pasaporte</option>
            </select>
        </div>
        <div class="fg"><label>Número documento *</label><input type="text" name="numero_documento" required placeholder="Número"></div>
        <div class="fg"><label>Correo *</label><input type="email" name="email" id="email_p" required placeholder="correo@ejemplo.com" oninput="syncEmail()"></div>
        <div class="fg"><label>Teléfono</label><input type="tel" name="telefono" placeholder="3XX XXXXXXX"></div>
        <div class="fg"><label>Fecha nacimiento</label><input type="date" name="fecha_nacimiento"></div>
        <div class="fg">
            <label>Género</label>
            <select name="genero">
                <option value="">— Selecciona —</option>
                <option>Masculino</option><option>Femenino</option>
                <option>Otro</option><option>No especifica</option>
            </select>
        </div>
        <div class="fg">
            <label>Estado académico</label>
            <select name="estado_academico">
                <option value="Activo">Activo</option>
                <option value="Retirado">Retirado</option>
                <option value="Graduado">Graduado</option>
            </select>
        </div>
    </div>
</div>

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
    <button type="submit" class="btn-action" onclick="return check()"><i class="fas fa-save"></i> Crear Aprendiz</button>
    <a href="<?= htmlspecialchars(app_url('mod/aprendices.php')) ?>" class="btn-cancel">Cancelar</a>
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
// Datos precargados desde PHP — sin AJAX
const CENTROS   = <?= json_encode($centros) ?>;
const PROGRAMAS = <?= json_encode($programas) ?>;
const FICHAS    = <?= json_encode($fichas) ?>;

function filtrarCentros() {
    const rid = parseInt(document.getElementById('sel_regional').value);
    const s   = document.getElementById('sel_centro');
    s.innerHTML = '<option value="">— Selecciona centro —</option>';
    if (!rid) { s.disabled=true; return; }
    CENTROS.filter(c => parseInt(c.REGIONAL_ID)===rid)
           .forEach(c => s.innerHTML += `<option value="${c.CENTRO_ID}">${c.NOMBRE}</option>`);
    s.disabled = false;
    // Reset downstream
    document.getElementById('sel_programa').innerHTML='<option value="">— Elige centro —</option>';
    document.getElementById('sel_programa').disabled=true;
    document.getElementById('sel_ficha').innerHTML='<option value="">— Elige programa —</option>';
    document.getElementById('sel_ficha').disabled=true;
}

function filtrarProgramas() {
    const cid = parseInt(document.getElementById('sel_centro').value);
    const s   = document.getElementById('sel_programa');
    s.innerHTML = '<option value="">— Selecciona programa —</option>';
    if (!cid) { s.disabled=true; return; }
    // Programas únicos para este centro
    const vistos = new Set();
    PROGRAMAS.filter(p => parseInt(p.CENTRO_ID)===cid).forEach(p => {
        if (!vistos.has(p.PROGRAMA_ID)) {
            vistos.add(p.PROGRAMA_ID);
            s.innerHTML += `<option value="${p.PROGRAMA_ID}">${p.NOMBRE} (${p.NIVEL_FORMACION})</option>`;
        }
    });
    s.disabled = false;
    document.getElementById('sel_ficha').innerHTML='<option value="">— Elige programa —</option>';
    document.getElementById('sel_ficha').disabled=true;
}

function filtrarFichas() {
    const pid = parseInt(document.getElementById('sel_programa').value);
    const cid = parseInt(document.getElementById('sel_centro').value);
    const s   = document.getElementById('sel_ficha');
    s.innerHTML = '<option value="">— Selecciona ficha —</option>';
    if (!pid) { s.disabled=true; return; }
    FICHAS.filter(f => parseInt(f.PROGRAMA_ID)===pid && parseInt(f.CENTRO_ID)===cid)
          .forEach(f => s.innerHTML += `<option value="${f.FICHA_ID}">${f.CODIGO_FICHA}</option>`);
    s.disabled = false;
}

function syncEmail() {
    document.getElementById('user_email').value = document.getElementById('email_p').value;
}
function eye(id, btn) {
    const i = document.getElementById(id);
    i.type = i.type==='password'?'text':'password';
    btn.innerHTML = i.type==='password'?'<i class="fas fa-eye"></i>':'<i class="fas fa-eye-slash"></i>';
}
function check() {
    if (document.getElementById('p1').value !== document.getElementById('p2').value) {
        alert('Las contraseñas no coinciden.'); return false;
    }
    if (!document.getElementById('sel_ficha').value) {
        alert('Selecciona una ficha.'); return false;
    }
    return true;
}
</script>
</body>
</html>