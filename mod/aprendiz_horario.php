<?php
// mod/aprendiz_horario.php
session_start();
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../conexion/AprendizDAO.php';
require_once __DIR__ . '/../conexion/HorarioDAO.php';

if (!esAprendiz()) {
    redirect_to('index.php');
}

$aprendizDAO = new AprendizDAO();
$horarioDAO  = new HorarioDAO();
$usuarioId   = (int)($_SESSION['usuario_id'] ?? 0);

$stmt = $aprendizDAO->ejecutarPreparado(
    "SELECT a.APRENDIZ_ID, a.NOMBRES, a.APELLIDOS, a.FICHA_ID,
            f.CODIGO_FICHA, p.NOMBRE AS programa_nombre
     FROM aprendiz a
     LEFT JOIN ficha f ON a.FICHA_ID = f.FICHA_ID
     LEFT JOIN programa p ON f.PROGRAMA_ID = p.PROGRAMA_ID
     WHERE a.usuario_id = :uid LIMIT 1",
    [':uid' => $usuarioId]
);
$aprendiz = $stmt ? $stmt->fetch() : null;

if (!$aprendiz || !$aprendiz['FICHA_ID']) {
    redirect_to('aprendiz_perfil.php');
}

$fichaId = (int)$aprendiz['FICHA_ID'];

// Trimestre seleccionado
$trimestres = [];
for ($a = date('Y') - 1; $a <= date('Y') + 1; $a++) {
    for ($t = 1; $t <= 4; $t++) {
        $trimestres[] = "$a-$t";
    }
}
$trimestreActual = date('Y') . '-' . ceil(date('m') / 3);
$trimestreSel    = $_GET['trimestre'] ?? $trimestreActual;

$horario = $horarioDAO->obtenerHorarioFicha($fichaId, $trimestreSel);

// Agrupar por día
$porDia = [];
$diasNombre = [1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado'];
foreach ($horario as $h) {
    $porDia[$h['DIA_SEMANA']][] = $h;
}
ksort($porDia);

$coloresDia = [
    1 => '#3b82f6', 2 => '#8b5cf6', 3 => '#f59e0b',
    4 => '#ef4444', 5 => '#10b981', 6 => '#39a900',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mi Horario — DTD SENA</title>
<link rel="stylesheet" href="<?= htmlspecialchars(asset_url('css/style.css')) ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
.horario-header {
    background: linear-gradient(135deg, #39a900, #2d8a00);
    border-radius: 20px;
    padding: 24px 28px;
    color: #fff;
    margin-bottom: 24px;
    box-shadow: 0 10px 30px rgba(57,169,0,.3);
}
.horario-header h1 { font-size: 24px; font-weight: 800; margin-bottom: 4px; }
.horario-header p  { font-size: 13px; opacity: .85; }

.trimestre-bar {
    display: flex; gap: 10px; align-items: center;
    margin-bottom: 24px; flex-wrap: wrap;
}
.trimestre-bar label { font-weight: 700; font-size: 14px; }
.trimestre-bar select {
    padding: 8px 14px; border: 2px solid var(--border-color);
    border-radius: 10px; font-size: 14px;
    background: var(--color-blanco); color: var(--color-texto);
}
.trimestre-bar select:focus { border-color: var(--color-verde-1); outline: none; }

.horario-semana {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 18px;
}
.dia-col {
    background: var(--color-blanco);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: var(--shadow-card);
}
.dia-header {
    padding: 12px 18px;
    color: #fff;
    font-weight: 800;
    font-size: 15px;
    display: flex; align-items: center; gap: 8px;
}
.dia-body { padding: 12px; }
.clase-item {
    background: var(--color-gris-fondo);
    border-radius: 10px;
    padding: 12px 14px;
    margin-bottom: 8px;
    border-left: 4px solid transparent;
    transition: .2s;
}
.clase-item:last-child { margin-bottom: 0; }
.clase-item:hover { transform: translateX(3px); }
.clase-hora  { font-size: 11px; font-weight: 700; color: var(--color-texto-secundario); margin-bottom: 3px; display: flex; align-items: center; gap: 5px; }
.clase-hora i { color: var(--color-verde-1); }
.clase-mat   { font-size: 14px; font-weight: 700; color: var(--color-texto); margin-bottom: 3px; }
.clase-aula  { font-size: 11px; color: var(--color-texto-secundario); display: flex; align-items: center; gap: 4px; }
.clase-aula i { color: var(--color-verde-1); }

.empty-dia { text-align: center; padding: 20px; color: var(--color-texto-secundario); font-size: 13px; opacity: .6; }

.no-horario {
    text-align: center; padding: 60px 20px;
    background: var(--color-blanco); border-radius: 16px;
    border: 2px dashed var(--border-color);
    color: var(--color-texto-secundario);
}
.no-horario i { font-size: 48px; opacity: .2; display: block; margin-bottom: 14px; }

.dark-mode .dia-col, .dark-mode .clase-item { background: var(--color-gris-cuerpo); }
.dark-mode .clase-item { background: var(--color-gris-fondo); }
</style>
</head>
<body>
<div id="loader">
    <img src="<?= htmlspecialchars(asset_url('img/logo_sena_verde.png')) ?>" alt="" id="loader-logo">
</div>
<?php include '../config/header.php'; ?>

<main class="container" id="contenido-principal" style="display:none;opacity:0;">

    <div style="margin-bottom:18px;">
        <a href="<?= htmlspecialchars(app_url('mod/aprendiz_perfil.php')) ?>" class="btn-view-all">
            <i class="fas fa-arrow-left"></i> Mi Perfil
        </a>
    </div>

    <div class="horario-header">
        <h1><i class="fas fa-calendar-week"></i> Mi Horario de Clases</h1>
        <p>
            <i class="fas fa-layer-group"></i> Ficha <?= htmlspecialchars($aprendiz['CODIGO_FICHA'] ?? '—') ?>
            &nbsp;·&nbsp;
            <i class="fas fa-graduation-cap"></i> <?= htmlspecialchars($aprendiz['programa_nombre'] ?? '—') ?>
        </p>
    </div>

    <!-- Selector de trimestre -->
    <div class="trimestre-bar">
        <label><i class="fas fa-calendar"></i> Trimestre:</label>
        <select onchange="window.location.href='?trimestre='+this.value">
            <?php foreach ($trimestres as $t): ?>
            <option value="<?= $t ?>" <?= $t === $trimestreSel ? 'selected' : '' ?>>
                Trimestre <?= $t ?>
            </option>
            <?php endforeach; ?>
        </select>
        <?php if ($trimestreSel !== $trimestreActual): ?>
        <a href="?" style="color:var(--color-verde-1);font-size:13px;font-weight:600;">
            <i class="fas fa-redo"></i> Trimestre actual
        </a>
        <?php endif; ?>
    </div>

    <?php if (empty($horario)): ?>
        <div class="no-horario">
            <i class="fas fa-calendar-times"></i>
            <h3>Sin horario cargado</h3>
            <p>Tu instructor aún no ha cargado el horario para el trimestre <strong><?= htmlspecialchars($trimestreSel) ?></strong>.</p>
            <p style="margin-top:8px;font-size:13px;">Consulta con tu instructor para más información.</p>
        </div>
    <?php else: ?>
        <?php
        // Rango del horario
        $primeroH = $horario[0];
        $desdeStr = !empty($primeroH['FECHA_DESDE']) ? date('d/m/Y', strtotime($primeroH['FECHA_DESDE'])) : '';
        $hastaStr = !empty($primeroH['FECHA_HASTA']) ? date('d/m/Y', strtotime($primeroH['FECHA_HASTA'])) : '';
        ?>
        <?php if ($desdeStr): ?>
        <div style="background:rgba(57,169,0,.08);border:1px solid rgba(57,169,0,.2);border-radius:10px;padding:10px 18px;margin-bottom:20px;font-size:13px;color:var(--color-verde-1);font-weight:600;">
            <i class="fas fa-calendar-alt"></i>
            Vigencia: <?= $desdeStr ?> — <?= $hastaStr ?>
        </div>
        <?php endif; ?>

        <div class="horario-semana">
            <?php foreach ($diasNombre as $num => $nombre):
                $color = $coloresDia[$num] ?? '#39a900';
                $clases = $porDia[$num] ?? [];
            ?>
            <div class="dia-col">
                <div class="dia-header" style="background:<?= $color ?>;">
                    <i class="fas fa-calendar-day"></i> <?= $nombre ?>
                    <span style="margin-left:auto;background:rgba(255,255,255,.2);padding:2px 8px;border-radius:10px;font-size:11px;">
                        <?= count($clases) ?> clase<?= count($clases)!==1?'s':'' ?>
                    </span>
                </div>
                <div class="dia-body">
                    <?php if (empty($clases)): ?>
                        <div class="empty-dia"><i class="fas fa-moon"></i> Sin clases</div>
                    <?php else: ?>
                        <?php foreach ($clases as $cl): ?>
                        <div class="clase-item" style="border-left-color:<?= $color ?>;">
                            <div class="clase-hora">
                                <i class="fas fa-clock"></i>
                                <?= substr($cl['HORA_INICIO'] ?? '', 0, 5) ?> – <?= substr($cl['HORA_FIN'] ?? '', 0, 5) ?>
                            </div>
                            <div class="clase-mat"><?= htmlspecialchars($cl['MATERIA'] ?? '—') ?></div>
                            <?php if (!empty($cl['AULA'])): ?>
                            <div class="clase-aula">
                                <i class="fas fa-door-open"></i>
                                Aula: <?= htmlspecialchars($cl['AULA']) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Nota: sábado opcional -->
        <p style="font-size:12px;color:var(--color-texto-secundario);margin-top:16px;text-align:center;">
            <i class="fas fa-info-circle"></i>
            El sábado es optativo según el programa. Consulta con tu instructor.
        </p>
    <?php endif; ?>

</main>

<?php include '../config/footer.php'; ?>
<script src="<?= htmlspecialchars(asset_url('js/tema.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/loader.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/panel_menu.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/dropdowns.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/profile_menu.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/menu.js')) ?>"></script>
</body>
</html>