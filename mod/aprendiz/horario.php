<?php
// mod/aprendiz/horario.php — Vista de horario para el aprendiz
session_start();
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../conexion/conexion.php';

if (!esAprendiz()) redirect_to('login.php');

class HorarioAprendizDB extends BaseDatos {
    protected function consultar(){}protected function insertar(){}
    protected function actualizar(){}protected function eliminar(){}
    public function uno($sql,$p=[]){$s=$this->ejecutarPreparado($sql,$p);return $s?$s->fetch(PDO::FETCH_ASSOC):[];}
    public function varios($sql,$p=[]){$s=$this->ejecutarPreparado($sql,$p);return $s?$s->fetchAll(PDO::FETCH_ASSOC):[];}
}
$db  = new HorarioAprendizDB();
$ref = (int)($_SESSION['usuario_ref_id'] ?? 0);
$uid = (int)($_SESSION['usuario_id']     ?? 0);

// Obtener aprendiz
$aprendiz = $ref ? $db->uno(
    "SELECT a.*, f.FICHA_ID, f.CODIGO_FICHA, p.NOMBRE AS programa
     FROM aprendiz a
     LEFT JOIN ficha   f ON a.FICHA_ID   = f.FICHA_ID
     LEFT JOIN programa p ON f.PROGRAMA_ID = p.PROGRAMA_ID
     WHERE a.APRENDIZ_ID = :id", [':id'=>$ref]
) : null;

if (!$aprendiz && $uid) {
    $aprendiz = $db->uno(
        "SELECT a.*, f.FICHA_ID, f.CODIGO_FICHA, p.NOMBRE AS programa
         FROM aprendiz a
         LEFT JOIN ficha   f ON a.FICHA_ID   = f.FICHA_ID
         LEFT JOIN programa p ON f.PROGRAMA_ID = p.PROGRAMA_ID
         WHERE a.usuario_id = :id", [':id'=>$uid]
    );
}

$fichaId   = (int)($aprendiz['FICHA_ID'] ?? 0);
$trimestre = $_GET['trimestre'] ?? date('Y').'-1';

// Trimestres disponibles para esta ficha
$trimestresDisp = $fichaId ? $db->varios(
    "SELECT DISTINCT TRIMESTRE FROM horario WHERE FICHA_ID=:fid ORDER BY TRIMESTRE",
    [':fid'=>$fichaId]
) : [];

// Si no hay trimestres en BD, generar opciones genéricas
$trimestresOpc = [];
if (!empty($trimestresDisp)) {
    $trimestresOpc = array_column($trimestresDisp, 'TRIMESTRE');
} else {
    for ($y=date('Y')-1; $y<=date('Y')+1; $y++) {
        $trimestresOpc[] = $y.'-1';
        $trimestresOpc[] = $y.'-2';
        $trimestresOpc[] = $y.'-3';
    }
}

// Horario de la ficha
$horario = $fichaId ? $db->varios(
    "SELECT h.DIA_SEMANA, h.HORA_INICIO, h.HORA_FIN, h.MATERIA, h.AULA,
            i.NOMBRES AS inst_n, i.APELLIDOS AS inst_a
     FROM horario h
     LEFT JOIN instructor i ON h.INSTRUCTOR_ID = i.INSTRUCTOR_ID
     WHERE h.FICHA_ID = :fid AND h.TRIMESTRE = :tri
     ORDER BY h.DIA_SEMANA, h.HORA_INICIO",
    [':fid'=>$fichaId, ':tri'=>$trimestre]
) : [];

// Agrupar por día
$porDia = [];
foreach ($horario as $h) $porDia[$h['DIA_SEMANA']][] = $h;

$dias = [1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado'];
$diaColors = [1=>'#3b82f6',2=>'#8b5cf6',3=>'#10b981',4=>'#f59e0b',5=>'#ef4444',6=>'#ec4899'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Mi Horario — DTD SENA</title>
<link rel="stylesheet" href="<?= htmlspecialchars(asset_url('css/style.css')) ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
.hor-header{background:linear-gradient(135deg,#39a900,#2d8a00);border-radius:20px;padding:24px 28px;margin-bottom:24px;color:#fff;}
.hor-header h1{font-size:22px;font-weight:800;margin-bottom:4px;}
.hor-header p{opacity:.8;font-size:13px;}
.tri-selector{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:24px;}
.tri-btn{padding:7px 16px;border-radius:20px;font-size:12px;font-weight:700;text-decoration:none;border:2px solid var(--border-color);color:var(--color-texto-secundario);transition:.2s;}
.tri-btn.active{background:var(--color-verde-1);color:#fff;border-color:var(--color-verde-1);}
.tri-btn:hover:not(.active){border-color:var(--color-verde-1);color:var(--color-verde-1);}
.dias-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;}
.dia-card{background:var(--color-blanco);border:1px solid var(--border-color);border-radius:16px;overflow:hidden;box-shadow:var(--shadow-card);transition:.25s;}
.dia-card:hover{transform:translateY(-3px);}
.dia-header{padding:12px 16px;display:flex;align-items:center;gap:8px;font-weight:800;font-size:14px;color:#fff;}
.dia-body{padding:8px 0;}
.bloque{padding:10px 16px;border-bottom:1px solid var(--border-color);display:flex;gap:12px;align-items:flex-start;}
.bloque:last-child{border-bottom:none;}
.bloque-hora{font-size:11px;font-weight:700;color:var(--color-texto-secundario);min-width:90px;white-space:nowrap;padding-top:2px;}
.bloque-info{flex:1;}
.bloque-mat{font-size:13px;font-weight:700;color:var(--color-texto);margin-bottom:3px;}
.bloque-det{font-size:11px;color:var(--color-texto-secundario);display:flex;gap:10px;flex-wrap:wrap;}
.bloque-det i{color:var(--color-verde-1);}
.empty-dia{padding:16px;text-align:center;color:var(--color-texto-secundario);font-size:12px;}
.vacio-box{text-align:center;padding:60px 20px;background:var(--color-blanco);border-radius:18px;border:2px dashed var(--border-color);}
.badge-ficha{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.2);padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;margin-top:8px;}
</style>
</head>
<body>
<div id="loader"><img src="<?= htmlspecialchars(asset_url('img/logo_sena_verde.png')) ?>" alt="" id="loader-logo"></div>
<?php include __DIR__ . '/../../config/header.php'; ?>

<main class="container" id="contenido-principal" style="display:none;opacity:0;">

<div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;">
    <a href="<?= htmlspecialchars(app_url('mod/aprendiz/index.php')) ?>" class="btn-view-all">
        <i class="fas fa-arrow-left"></i> Dashboard
    </a>
    <span style="font-size:13px;color:var(--color-texto-secundario);">
        <i class="fas fa-calendar-week"></i> Mi Horario
    </span>
</div>

<!-- Hero -->
<div class="hor-header">
    <h1><i class="fas fa-calendar-week"></i> Mi Horario de Clases</h1>
    <?php if ($aprendiz): ?>
    <p><?= htmlspecialchars($aprendiz['NOMBRES'].' '.$aprendiz['APELLIDOS']) ?></p>
    <?php if (!empty($aprendiz['CODIGO_FICHA'])): ?>
    <div class="badge-ficha">
        <i class="fas fa-layer-group"></i>
        Ficha <?= htmlspecialchars($aprendiz['CODIGO_FICHA']) ?>
        <?php if (!empty($aprendiz['programa'])): ?>
        — <?= htmlspecialchars($aprendiz['programa']) ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php if (!$fichaId): ?>
<div class="vacio-box">
    <i class="fas fa-layer-group" style="font-size:48px;opacity:.2;display:block;margin-bottom:12px;"></i>
    <p style="color:var(--color-texto-secundario);">No tienes una ficha asignada aún.</p>
    <p style="font-size:12px;color:var(--color-texto-secundario);margin-top:6px;">Contacta al administrador para vincular tu cuenta a una ficha.</p>
</div>

<?php else: ?>

<!-- Selector trimestre -->
<div class="tri-selector">
    <span style="font-size:12px;font-weight:700;color:var(--color-texto-secundario);text-transform:uppercase;">Trimestre:</span>
    <?php foreach ($trimestresOpc as $t): ?>
    <a href="?trimestre=<?= urlencode($t) ?>"
       class="tri-btn <?= $t===$trimestre?'active':'' ?>"><?= htmlspecialchars($t) ?></a>
    <?php endforeach; ?>
</div>

<?php if (empty($horario)): ?>
<div class="vacio-box">
    <i class="fas fa-calendar-plus" style="font-size:48px;opacity:.2;display:block;margin-bottom:12px;"></i>
    <p style="color:var(--color-texto-secundario);">No hay horario registrado para el trimestre <strong><?= htmlspecialchars($trimestre) ?></strong>.</p>
    <p style="font-size:12px;color:var(--color-texto-secundario);margin-top:6px;">El gestor de tu grupo aún no ha cargado el horario.</p>
</div>

<?php else: ?>

<!-- Resumen -->
<div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:20px;font-size:13px;">
    <div style="background:var(--color-blanco);border:1px solid var(--border-color);border-radius:10px;padding:10px 16px;display:flex;align-items:center;gap:8px;">
        <i class="fas fa-clock" style="color:var(--color-verde-1);"></i>
        <strong><?= count($horario) ?></strong> <span style="color:var(--color-texto-secundario);">clases / trimestre</span>
    </div>
    <div style="background:var(--color-blanco);border:1px solid var(--border-color);border-radius:10px;padding:10px 16px;display:flex;align-items:center;gap:8px;">
        <i class="fas fa-calendar-day" style="color:var(--color-verde-1);"></i>
        <strong><?= count($porDia) ?></strong> <span style="color:var(--color-texto-secundario);">días activos</span>
    </div>
    <div style="background:var(--color-blanco);border:1px solid var(--border-color);border-radius:10px;padding:10px 16px;display:flex;align-items:center;gap:8px;">
        <i class="fas fa-book-open" style="color:var(--color-verde-1);"></i>
        <strong><?= count(array_unique(array_column($horario,'MATERIA'))) ?></strong> <span style="color:var(--color-texto-secundario);">materias distintas</span>
    </div>
</div>

<!-- Grid de días -->
<div class="dias-grid">
    <?php foreach ($dias as $num => $nombreDia): ?>
    <?php if (!isset($porDia[$num])) continue; ?>
    <div class="dia-card">
        <div class="dia-header" style="background:<?= $diaColors[$num] ?>;">
            <i class="fas fa-calendar-day"></i>
            <?= $nombreDia ?>
            <span style="margin-left:auto;background:rgba(255,255,255,.2);padding:2px 8px;border-radius:10px;font-size:11px;">
                <?= count($porDia[$num]) ?> <?= count($porDia[$num])===1?'clase':'clases' ?>
            </span>
        </div>
        <div class="dia-body">
            <?php foreach ($porDia[$num] as $b): ?>
            <div class="bloque">
                <div class="bloque-hora">
                    <i class="fas fa-clock" style="color:<?= $diaColors[$num] ?>;margin-right:4px;"></i>
                    <?= substr($b['HORA_INICIO'],0,5) ?><br>
                    <span style="opacity:.6;">↓</span><br>
                    <?= substr($b['HORA_FIN'],0,5) ?>
                </div>
                <div class="bloque-info">
                    <div class="bloque-mat"><?= htmlspecialchars($b['MATERIA']) ?></div>
                    <div class="bloque-det">
                        <?php if (!empty($b['AULA'])): ?>
                        <span><i class="fas fa-door-open"></i> <?= htmlspecialchars($b['AULA']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($b['inst_n'])): ?>
                        <span><i class="fas fa-chalkboard-teacher"></i> <?= htmlspecialchars($b['inst_n'].' '.$b['inst_a']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
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