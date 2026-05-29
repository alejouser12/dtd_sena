<?php
// mod/aprendiz_compañeros.php
session_start();
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../conexion/AprendizDAO.php';
require_once __DIR__ . '/../conexion/FichaDAO.php';

if (!esAprendiz()) {
    redirect_to('index.php');
}

$aprendizDAO = new AprendizDAO();
$fichaDAO    = new FichaDAO();
$usuarioId   = (int)($_SESSION['usuario_id'] ?? 0);

// Obtener datos del aprendiz actual
$stmt = $aprendizDAO->ejecutarPreparado(
    "SELECT a.APRENDIZ_ID, a.NOMBRES, a.APELLIDOS, a.FICHA_ID,
            f.CODIGO_FICHA, p.NOMBRE AS programa_nombre, f.FECHA_INICIO, f.FECHA_FIN
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

$fichaId     = (int)$aprendiz['FICHA_ID'];
$companeros  = $fichaDAO->obtenerAprendices($fichaId);
$miId        = (int)$aprendiz['APRENDIZ_ID'];

// Quitar al aprendiz de la lista de compañeros
$companeros = array_filter($companeros, fn($c) => (int)$c['APRENDIZ_ID'] !== $miId);
$companeros = array_values($companeros);

$totalComp = count($companeros);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mis Compañeros — DTD SENA</title>
<link rel="stylesheet" href="<?= htmlspecialchars(asset_url('css/style.css')) ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
.ficha-banner {
    background: linear-gradient(135deg, #39a900, #2d8a00);
    border-radius: 20px;
    padding: 24px 28px;
    color: #fff;
    margin-bottom: 28px;
    display: flex; align-items: center; gap: 20px;
    flex-wrap: wrap;
    box-shadow: 0 10px 30px rgba(57,169,0,.3);
}
.ficha-banner-icon {
    width: 60px; height: 60px;
    background: rgba(255,255,255,.2);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 26px;
}
.ficha-banner h2 { font-size: 22px; font-weight: 800; margin-bottom: 4px; }
.ficha-banner p  { font-size: 13px; opacity: .85; }

.comp-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 18px;
}
.comp-card {
    background: var(--color-blanco);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 20px;
    text-align: center;
    box-shadow: var(--shadow-card);
    transition: .25s;
}
.comp-card:hover { transform: translateY(-5px); border-color: var(--color-verde-1); }
.comp-avatar {
    width: 64px; height: 64px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--color-verde-1), var(--color-verde-2));
    display: flex; align-items: center; justify-content: center;
    font-size: 24px; font-weight: 800; color: #fff;
    margin: 0 auto 12px;
}
.comp-nombre { font-weight: 700; font-size: 15px; color: var(--color-texto); margin-bottom: 5px; }
.comp-estado {
    display: inline-block; padding: 4px 12px; border-radius: 20px;
    font-size: 11px; font-weight: 700;
}
.comp-estado.activo  { background: rgba(57,169,0,.1);  color: var(--color-verde-1); }
.comp-estado.inactivo{ background: rgba(220,38,38,.1); color: #dc2626; }
.comp-riesgo { font-size: 11px; margin-top: 6px; color: var(--color-texto-secundario); }

.search-bar {
    display: flex; gap: 10px; margin-bottom: 20px;
}
.search-bar input {
    flex: 1; padding: 10px 16px;
    border: 2px solid var(--border-color);
    border-radius: 10px;
    font-size: 14px;
    background: var(--color-blanco); color: var(--color-texto);
}
.search-bar input:focus { border-color: var(--color-verde-1); outline: none; }
.counter-badge {
    background: rgba(57,169,0,.12);
    border: 1px solid rgba(57,169,0,.3);
    color: var(--color-verde-1);
    padding: 4px 14px; border-radius: 20px;
    font-size: 13px; font-weight: 700;
}
.dark-mode .comp-card { background: var(--color-gris-cuerpo); }
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

    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-users"></i> Mis Compañeros</h1>
        <p class="page-subtitle">Aprendices de tu misma ficha</p>
    </div>

    <!-- Banner ficha -->
    <div class="ficha-banner">
        <div class="ficha-banner-icon"><i class="fas fa-layer-group"></i></div>
        <div>
            <h2>Ficha <?= htmlspecialchars($aprendiz['CODIGO_FICHA'] ?? '—') ?></h2>
            <p>
                <i class="fas fa-graduation-cap"></i> <?= htmlspecialchars($aprendiz['programa_nombre'] ?? '—') ?>
                &nbsp;·&nbsp;
                <i class="fas fa-calendar-alt"></i>
                <?= !empty($aprendiz['FECHA_INICIO']) ? date('d/m/Y', strtotime($aprendiz['FECHA_INICIO'])) : '—' ?>
                –
                <?= !empty($aprendiz['FECHA_FIN']) ? date('d/m/Y', strtotime($aprendiz['FECHA_FIN'])) : '—' ?>
            </p>
        </div>
        <span class="counter-badge" style="margin-left:auto;"><?= $totalComp ?> compañero<?= $totalComp !== 1 ? 's' : '' ?></span>
    </div>

    <!-- Buscador -->
    <div class="search-bar">
        <input type="text" id="buscador" placeholder="Buscar compañero por nombre…" oninput="filtrar()">
    </div>

    <!-- Grid compañeros -->
    <?php if (empty($companeros)): ?>
        <div style="text-align:center;padding:60px 20px;color:var(--color-texto-secundario);">
            <i class="fas fa-users-slash" style="font-size:48px;opacity:.2;display:block;margin-bottom:14px;"></i>
            <p>No hay compañeros registrados en tu ficha aún.</p>
        </div>
    <?php else: ?>
    <div class="comp-grid" id="comp-grid">
        <?php foreach ($companeros as $c):
            $ini   = strtoupper(substr($c['NOMBRES'],0,1).substr($c['APELLIDOS'],0,1));
            $ea    = strtolower($c['ESTADO_ACADEMICO'] ?? 'activo');
            $riesgo = strtolower($c['NIVEL_RIESGO_GLOBAL'] ?? 'bajo');
            $riesgoColor = $riesgo === 'alto' ? '#dc2626' : ($riesgo === 'medio' ? '#f59e0b' : '#16a34a');
        ?>
        <div class="comp-card" data-nombre="<?= strtolower($c['NOMBRES'].' '.$c['APELLIDOS']) ?>">
            <div class="comp-avatar"><?= $ini ?></div>
            <div class="comp-nombre"><?= htmlspecialchars($c['NOMBRES'].' '.$c['APELLIDOS']) ?></div>
            <span class="comp-estado <?= $ea ==='activo'?'activo':'inactivo' ?>">
                <?= htmlspecialchars($c['ESTADO_ACADEMICO'] ?? 'Activo') ?>
            </span>
            <?php if (!empty($c['EMAIL'])): ?>
            <div class="comp-riesgo" style="margin-top:8px;">
                <i class="fas fa-envelope" style="color:var(--color-verde-1);"></i>
                <?= htmlspecialchars($c['EMAIL']) ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($c['TELEFONO'])): ?>
            <div class="comp-riesgo">
                <i class="fas fa-phone" style="color:var(--color-verde-1);"></i>
                <?= htmlspecialchars($c['TELEFONO']) ?>
            </div>
            <?php endif; ?>
            <div class="comp-riesgo" style="margin-top:8px;">
                <i class="fas fa-circle" style="font-size:8px;color:<?= $riesgoColor ?>;"></i>
                Nivel: <strong style="color:<?= $riesgoColor ?>;"><?= ucfirst($riesgo) ?></strong>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <div id="sin-resultados" style="display:none;text-align:center;padding:30px;color:var(--color-texto-secundario);">
        <i class="fas fa-search" style="font-size:36px;opacity:.2;display:block;margin-bottom:10px;"></i>
        No se encontraron compañeros con ese nombre.
    </div>
    <?php endif; ?>

</main>

<?php include '../config/footer.php'; ?>
<script src="<?= htmlspecialchars(asset_url('js/tema.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/loader.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/panel_menu.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/dropdowns.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/profile_menu.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/menu.js')) ?>"></script>
<script>
function filtrar() {
    const q = document.getElementById('buscador').value.toLowerCase();
    let visible = 0;
    document.querySelectorAll('.comp-card').forEach(card => {
        const match = card.dataset.nombre.includes(q);
        card.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    document.getElementById('sin-resultados').style.display = visible === 0 ? 'block' : 'none';
}
</script>
</body>
</html>