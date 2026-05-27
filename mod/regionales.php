<?php
session_start();
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../conexion/RegionalDAO.php';

$dao        = new RegionalDAO();
$regionales = $dao->obtenerTodas();

$coordsCiudades = [
    'bogota'=>[4.7110,-74.0721],'bogotá'=>[4.7110,-74.0721],
    'medellin'=>[6.2442,-75.5812],'medellín'=>[6.2442,-75.5812],
    'cali'=>[3.4516,-76.5320],'barranquilla'=>[10.9639,-74.7964],
    'cartagena'=>[10.3910,-75.4794],'bucaramanga'=>[7.1193,-73.1227],
    'manizales'=>[5.0703,-75.5138],'pereira'=>[4.8133,-75.6961],
    'armenia'=>[4.5339,-75.6811],'ibague'=>[4.4389,-75.2322],
    'ibagué'=>[4.4389,-75.2322],'villavicencio'=>[4.1420,-73.6266],
    'cucuta'=>[7.8939,-72.5078],'cúcuta'=>[7.8939,-72.5078],
    'santa marta'=>[11.2408,-74.2110],'pasto'=>[1.2136,-77.2811],
    'monteria'=>[8.7575,-75.8878],'montería'=>[8.7575,-75.8878],
    'sincelejo'=>[9.3047,-75.3978],'valledupar'=>[10.4631,-73.2532],
    'popayan'=>[2.4448,-76.6147],'popayán'=>[2.4448,-76.6147],
    'neiva'=>[2.9273,-75.2819],'tunja'=>[5.5353,-73.3678],
    'florencia'=>[1.6144,-75.6062],'riohacha'=>[11.5444,-72.9072],
    'quibdo'=>[5.6919,-76.6583],'quibdó'=>[5.6919,-76.6583],
    'yopal'=>[5.3378,-72.3959],'mocoa'=>[1.1522,-76.6486],
    'arauca'=>[7.0897,-70.7618],'leticia'=>[-4.2153,-69.9406],
];

$regionalesMap = [];
foreach ($regionales as $r) {
    $lat = $r['LAT'] ?? null;
    $lng = $r['LNG'] ?? null;
    if (!$lat || !$lng) {
        $ciudad     = strtolower(trim($r['CIUDAD'] ?? ''));
        $ciudadNorm = strtr($ciudad,['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u']);
        if (isset($coordsCiudades[$ciudad]))         [$lat,$lng] = $coordsCiudades[$ciudad];
        elseif (isset($coordsCiudades[$ciudadNorm])) [$lat,$lng] = $coordsCiudades[$ciudadNorm];
        else { $lat=4.5709; $lng=-74.2973; }
    }
    $regionalesMap[] = [
        'id'        => (int)$r['REGIONAL_ID'],
        'nombre'    => $r['NOMBRE'],
        'ciudad'    => $r['CIUDAD'] ?? '',
        'codigo'    => $r['CODIGO'] ?? '',
        'direccion' => $r['DIRECCION'] ?? '',
        'telefono'  => $r['TELEFONO'] ?? '',
        'lat'       => (float)$lat,
        'lng'       => (float)$lng,
        'url'       => app_url('mod/regional_detalle.php?id='.(int)$r['REGIONAL_ID']),
    ];
}
$regionalesJson = json_encode($regionalesMap, JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Regionales SENA</title>
<link rel="stylesheet" href="<?= htmlspecialchars(asset_url('css/style.css')) ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(asset_url('mod/css/regionales.css')) ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
/* ══ MODAL ═══════════════════════════════════════ */
#mapa-modal { display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.82);backdrop-filter:blur(8px); }
#mapa-modal.visible { display:flex;align-items:center;justify-content:center;animation:fdIn .3s ease; }
@keyframes fdIn{from{opacity:0}to{opacity:1}}

.mapa-container {
    width:95vw;max-width:1300px;height:88vh;
    background:#060d06;border-radius:20px;overflow:hidden;
    position:relative;box-shadow:0 30px 80px rgba(0,0,0,.8);
    animation:slideUp .4s cubic-bezier(.22,1,.36,1);
    display:flex;flex-direction:column;
}
@keyframes slideUp{from{opacity:0;transform:translateY(40px) scale(.97)}to{opacity:1;transform:none}}

/* Topbar */
.mapa-topbar {
    height:52px;flex-shrink:0;
    background:linear-gradient(90deg,#0a160a,#060d06);
    border-bottom:1px solid rgba(57,169,0,.2);
    display:flex;align-items:center;justify-content:space-between;
    padding:0 20px;z-index:1000;
}
.mapa-topbar-left{display:flex;align-items:center;gap:10px;color:#39a900;font-size:14px;font-weight:700;}
.mapa-pill{background:rgba(57,169,0,.15);border:1px solid rgba(57,169,0,.3);color:#39a900;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;}
.mapa-btn-close{width:32px;height:32px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:8px;color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:.2s;font-size:15px;}
.mapa-btn-close:hover{background:#dc2626;}

/* Cuerpo dividido: mapa + panel */
.mapa-body{flex:1;display:flex;overflow:hidden;position:relative;}

/* Leaflet */
#leaflet-map{flex:1;z-index:1;}

/* ══ PANEL LATERAL ════════════════════════════════ */
#info-panel {
    width:0;flex-shrink:0;
    background:#0a140a;
    border-left:1px solid rgba(57,169,0,.15);
    overflow:hidden;
    transition:width .4s cubic-bezier(.4,0,.2,1);
    display:flex;flex-direction:column;
}
#info-panel.abierto{width:360px;}

.panel-top {
    padding:16px 16px 12px;
    border-bottom:1px solid rgba(57,169,0,.12);
    flex-shrink:0;
}
.panel-nombre{font-size:16px;font-weight:800;color:#39a900;line-height:1.2;}
.panel-ciudad{font-size:12px;color:rgba(255,255,255,.45);margin-top:3px;display:flex;align-items:center;gap:5px;}
.panel-close{background:none;border:none;color:rgba(255,255,255,.35);font-size:20px;cursor:pointer;float:right;margin-top:-2px;transition:.2s;padding:0;}
.panel-close:hover{color:#dc2626;}

/* Tabs */
.panel-tabs{display:flex;border-bottom:1px solid rgba(57,169,0,.12);flex-shrink:0;}
.panel-tab{flex:1;padding:10px;text-align:center;font-size:12px;font-weight:700;color:rgba(255,255,255,.35);cursor:pointer;border:none;background:none;transition:.2s;border-bottom:2px solid transparent;}
.panel-tab.active{color:#39a900;border-bottom-color:#39a900;}
.panel-tab:hover{color:rgba(57,169,0,.7);}

.panel-body{flex:1;overflow-y:auto;padding:14px;}
.panel-body::-webkit-scrollbar{width:4px;}
.panel-body::-webkit-scrollbar-thumb{background:rgba(57,169,0,.25);border-radius:4px;}

/* Info básica */
.info-row{display:flex;align-items:flex-start;gap:10px;padding:10px 0;border-bottom:1px solid rgba(57,169,0,.07);}
.info-row:last-child{border-bottom:none;}
.info-icon{width:32px;height:32px;border-radius:8px;background:rgba(57,169,0,.1);display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.info-icon i{color:#39a900;font-size:13px;}
.info-label{font-size:10px;color:rgba(255,255,255,.35);font-weight:700;text-transform:uppercase;letter-spacing:.5px;}
.info-val{font-size:13px;color:rgba(255,255,255,.8);margin-top:2px;line-height:1.4;}

/* Mapa mini satélite embebido */
#mini-sat{
    width:100%;height:200px;border-radius:10px;overflow:hidden;
    border:1px solid rgba(57,169,0,.15);margin-bottom:12px;
    position:relative;
}
#mini-sat-map{width:100%;height:100%;}
.sat-badge{
    position:absolute;top:8px;right:8px;
    background:rgba(0,0,0,.7);color:#39a900;
    font-size:10px;font-weight:700;padding:3px 8px;
    border-radius:6px;border:1px solid rgba(57,169,0,.3);
    pointer-events:none;
}

/* Fotos de Wikimedia */
.fotos-grid{display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-bottom:12px;}
.foto-item{border-radius:8px;overflow:hidden;background:rgba(57,169,0,.05);border:1px solid rgba(57,169,0,.1);cursor:pointer;}
.foto-item:first-child{grid-column:1/-1;}
.foto-item img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .3s;}
.foto-item:hover img{transform:scale(1.05);}
.foto-item-tall{aspect-ratio:16/9;}
.foto-item-sq{aspect-ratio:1;}

/* Skeleton */
.skeleton{background:linear-gradient(90deg,rgba(57,169,0,.06) 25%,rgba(57,169,0,.12) 50%,rgba(57,169,0,.06) 75%);background-size:200% 100%;animation:skl 1.5s infinite;border-radius:6px;}
@keyframes skl{0%{background-position:200%}100%{background-position:-200%}}

/* Btns panel */
.panel-btn{display:flex;align-items:center;justify-content:center;gap:7px;border-radius:10px;padding:10px;font-size:13px;font-weight:700;cursor:pointer;width:100%;margin-bottom:8px;transition:.2s;text-decoration:none;border:none;}
.panel-btn-green{background:linear-gradient(135deg,#39a900,#2d8a00);color:#fff;}
.panel-btn-green:hover{opacity:.85;color:#fff;}
.panel-btn-outline{background:rgba(57,169,0,.08);border:1px solid rgba(57,169,0,.25);color:#39a900;}
.panel-btn-outline:hover{background:rgba(57,169,0,.16);}

/* Empty state */
.panel-empty{text-align:center;padding:30px 16px;color:rgba(255,255,255,.3);}
.panel-empty i{font-size:36px;display:block;margin-bottom:10px;opacity:.3;}
.panel-empty p{font-size:12px;}

/* ══ LOADER — GLOBO TERRÁQUEO GIRANDO ══════════════ */
#mapa-loader {
    position:absolute; inset:0;
    background:#060d06;
    display:flex; flex-direction:column;
    align-items:center; justify-content:center;
    z-index:998; overflow:hidden;
    transition:opacity .8s ease;
}
#mapa-loader.hidden { opacity:0; pointer-events:none; }

/* Canvas 3D del globo */
#globe-canvas {
    display:block;
    filter: drop-shadow(0 0 30px rgba(57,169,0,.5)) drop-shadow(0 0 60px rgba(57,169,0,.2));
}

/* Anillo orbital */
.globe-ring {
    position:absolute; border-radius:50%;
    border:1px solid rgba(57,169,0,.18);
    animation:gRing 3s linear infinite;
    pointer-events:none;
}
@keyframes gRing { from{transform:scale(1) rotate(0deg)} to{transform:scale(1) rotate(360deg)} }

/* Pulsos expansivos */
.globe-pulse {
    position:absolute; border-radius:50%;
    border:1px solid rgba(57,169,0,.15);
    animation:gPulse 2.5s ease-out infinite;
    pointer-events:none;
}
@keyframes gPulse { 0%{transform:scale(1);opacity:.7} 100%{transform:scale(2.4);opacity:0} }

/* HUD coords */
.ld-hud {
    font-family:'Courier New',monospace;
    margin-top:20px; text-align:center;
    position:relative; z-index:4;
}
.ld-hud-line {
    position:absolute; top:50%; width:50px; height:1px;
    background:linear-gradient(90deg,transparent,rgba(57,169,0,.4));
}
.ld-hud-line.left  { right:calc(100% + 10px); transform:scaleX(-1); }
.ld-hud-line.right { left:calc(100% + 10px); }
.ld-coords {
    font-size:12px; color:rgba(57,169,0,.8);
    letter-spacing:1.5px; animation:cf 1.4s steps(1) infinite;
}
@keyframes cf { 0%,100%{opacity:1} 50%{opacity:.2} }
.ld-status {
    font-size:10px; color:rgba(57,169,0,.4);
    letter-spacing:2.5px; margin-top:5px;
}

/* Botón ver mapa */
.btn-ver-mapa{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#0f172a,#1e293b);color:#7dd3fc;border:1px solid rgba(125,211,252,.25);padding:10px 20px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;transition:.25s;text-decoration:none;}
.btn-ver-mapa:hover{background:linear-gradient(135deg,#1e3a5f,#1e40af);border-color:rgba(125,211,252,.5);color:#bfdbfe;transform:translateY(-2px);box-shadow:0 6px 20px rgba(59,130,246,.25);}

/* Popup Leaflet */
.leaflet-popup-content-wrapper{background:#0d1a0d !important;border:1px solid rgba(57,169,0,.4) !important;border-radius:14px !important;box-shadow:0 10px 40px rgba(0,0,0,.5) !important;min-width:200px !important;}
.leaflet-popup-tip{background:#0d1a0d !important;}
.leaflet-popup-close-button{color:#39a900 !important;font-size:18px !important;top:8px !important;right:10px !important;}
.popup-reg{padding:4px 2px;}
.popup-titulo{font-size:14px;font-weight:800;color:#39a900;margin-bottom:8px;display:flex;align-items:center;gap:6px;}
.popup-row{display:flex;align-items:flex-start;gap:6px;font-size:11px;color:rgba(255,255,255,.65);margin-bottom:4px;}
.popup-row i{color:#39a900;font-size:10px;margin-top:2px;flex-shrink:0;}
.popup-btn{display:block;margin-top:8px;padding:7px;border-radius:8px;font-size:12px;font-weight:700;text-align:center;text-decoration:none;transition:.2s;}
.popup-btn-green{background:linear-gradient(90deg,#39a900,#2d8a00);color:#fff;}
.popup-btn-green:hover{opacity:.85;color:#fff;}
.popup-btn-outline{background:rgba(57,169,0,.1);border:1px solid rgba(57,169,0,.3);color:#39a900;cursor:pointer;border:none;width:100%;}
.popup-btn-outline:hover{background:rgba(57,169,0,.2);}

@keyframes pulseRing{0%{transform:scale(.6);opacity:1}100%{transform:scale(2.2);opacity:0}}

/* Hint */
.mapa-hint{position:absolute;bottom:16px;left:50%;transform:translateX(-50%);background:rgba(0,0,0,.75);color:rgba(255,255,255,.8);font-size:12px;padding:7px 16px;border-radius:20px;z-index:999;pointer-events:none;white-space:nowrap;backdrop-filter:blur(4px);border:1px solid rgba(255,255,255,.1);animation:hintFade 4s ease 3.5s forwards;}
@keyframes hintFade{to{opacity:0}}

/* Visor foto fullscreen */
#foto-viewer{display:none;position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,.92);align-items:center;justify-content:center;}
#foto-viewer.visible{display:flex;}
#foto-viewer img{max-width:90vw;max-height:90vh;border-radius:10px;box-shadow:0 20px 60px rgba(0,0,0,.8);}
#foto-viewer-close{position:absolute;top:20px;right:24px;color:#fff;font-size:28px;cursor:pointer;background:rgba(255,255,255,.1);border:none;border-radius:50%;width:44px;height:44px;display:flex;align-items:center;justify-content:center;}
</style>
</head>
<body>
<div id="loader">
    <img src="<?= htmlspecialchars(asset_url('img/logo_sena_verde.png')) ?>" alt="" id="loader-logo">
</div>
<?php include '../config/header.php'; ?>

<main class="container" id="contenido-principal" style="display:none;opacity:0;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;flex-wrap:wrap;gap:12px;">
        <h1 style="margin:0;color:var(--color-verde-1);font-size:28px;font-weight:800;">
            <i class="fas fa-map-marked-alt" style="margin-right:10px;"></i>Regionales SENA
        </h1>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <button class="btn-ver-mapa" onclick="abrirMapa()">
                <i class="fas fa-globe-americas"></i> Ver en mapa
            </button>
            <?php if (esAdmin()): ?>
            <a href="<?= htmlspecialchars(app_url('mod/crud/crear_regional.php')) ?>" class="btn-create">
                <i class="fas fa-plus"></i> Nueva Regional
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="regionales-grid">
        <?php if (empty($regionales)): ?>
            <div style="grid-column:1/-1;text-align:center;padding:60px 20px;color:var(--color-texto-secundario);">
                <i class="fas fa-map-marked-alt" style="font-size:48px;opacity:.2;display:block;margin-bottom:14px;"></i>
                <p>No hay regionales registradas</p>
            </div>
        <?php else: ?>
            <?php foreach($regionales as $r): ?>
            <div class="regional-card" onclick="window.location.href='<?= htmlspecialchars(app_url('mod/regional_detalle.php?id='.(int)$r['REGIONAL_ID'])) ?>'">
                <div class="regional-header">
                    <i class="fas fa-map-marker-alt"></i>
                    <h3><?= htmlspecialchars($r['NOMBRE']) ?></h3>
                    <span class="regional-codigo"><?= htmlspecialchars($r['CODIGO'] ?? '') ?></span>
                </div>
                <div class="regional-body">
                    <div class="regional-info">
                        <i class="fas fa-city"></i>
                        <span><?= htmlspecialchars($r['CIUDAD'] ?? 'Ciudad no especificada') ?></span>
                    </div>
                    <div class="regional-info">
                        <i class="fas fa-phone"></i>
                        <span><?= htmlspecialchars($r['TELEFONO'] ?? 'Teléfono no disponible') ?></span>
                    </div>
                    <div class="regional-info">
                        <i class="fas fa-map-pin"></i>
                        <span><?= htmlspecialchars($r['DIRECCION'] ?? 'Dirección no especificada') ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<!-- ══ MODAL MAPA ═════════════════════════════════════════════════ -->
<div id="mapa-modal">
    <div class="mapa-container">
        <!-- Topbar -->
        <div class="mapa-topbar">
            <div class="mapa-topbar-left">
                <i class="fas fa-globe-americas"></i>
                Regionales SENA — Colombia
                <span class="mapa-pill"><?= count($regionalesMap) ?> regional<?= count($regionalesMap)!==1?'es':'' ?></span>
            </div>
            <button class="mapa-btn-close" onclick="cerrarMapa()"><i class="fas fa-times"></i></button>
        </div>

        <!-- Cuerpo: mapa + panel -->
        <div class="mapa-body">

            <!-- LOADER: globo girando -->
            <div id="mapa-loader">
                <!-- Anillos orbitales decorativos -->
                <div class="globe-pulse" style="width:310px;height:310px;margin:-155px 0 0 -155px;top:50%;left:50%;position:absolute;animation-delay:0s;"></div>
                <div class="globe-pulse" style="width:310px;height:310px;margin:-155px 0 0 -155px;top:50%;left:50%;position:absolute;animation-delay:.9s;"></div>
                <div class="globe-pulse" style="width:310px;height:310px;margin:-155px 0 0 -155px;top:50%;left:50%;position:absolute;animation-delay:1.8s;"></div>
                <!-- Canvas del globo 3D -->
                <canvas id="globe-canvas"></canvas>
                <!-- HUD -->
                <div class="ld-hud">
                    <div class="ld-hud-line left"></div>
                    <div class="ld-coords" id="ld-coords">4.5709° N · 74.2973° W</div>
                    <div class="ld-status" id="ld-status">INICIANDO · · ·</div>
                    <div class="ld-hud-line right"></div>
                </div>
            </div>

            <!-- Leaflet map -->
            <div id="leaflet-map"></div>

            <!-- Panel lateral -->
            <div id="info-panel">
                <div class="panel-top">
                    <button class="panel-close" onclick="cerrarPanel()"><i class="fas fa-times"></i></button>
                    <div class="panel-nombre" id="panel-nombre">—</div>
                    <div class="panel-ciudad" id="panel-ciudad"><i class="fas fa-city"></i> —</div>
                </div>
                <div class="panel-tabs">
                    <button class="panel-tab active" onclick="cambiarTab('info')"><i class="fas fa-info-circle"></i> Info</button>
                    <button class="panel-tab" onclick="cambiarTab('mapa')"><i class="fas fa-satellite"></i> Satélite</button>
                    <button class="panel-tab" onclick="cambiarTab('fotos')"><i class="fas fa-images"></i> Fotos</button>
                </div>
                <div class="panel-body" id="panel-body">
                    <div class="panel-empty">
                        <i class="fas fa-hand-pointer"></i>
                        <p>Haz clic en un marcador del mapa para ver la información</p>
                    </div>
                </div>
            </div>

            <div class="mapa-hint">
                <i class="fas fa-hand-pointer"></i> Haz clic en un marcador para ver detalles, satélite y fotos
            </div>
        </div>
    </div>
</div>

<!-- Visor foto fullscreen -->
<div id="foto-viewer">
    <button id="foto-viewer-close" onclick="cerrarFoto()"><i class="fas fa-times"></i></button>
    <img id="foto-viewer-img" src="" alt="Foto">
</div>

<?php include '../config/footer.php'; ?>
<script src="<?= htmlspecialchars(asset_url('js/tema.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/loader.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/panel_menu.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/dropdowns.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/profile_menu.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/sweetalerts.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/menu.js')) ?>"></script>

<script>
const REGIONALES   = <?= $regionalesJson ?>;
let mapaIniciado   = false;
let leafletMap     = null;
let miniMap        = null;
let tabActual      = 'info';
let regionalActual = null;

// ── Modal ───────────────────────────────────────────────────────────
function abrirMapa() {
    document.getElementById('mapa-modal').classList.add('visible');
    document.body.style.overflow = 'hidden';
    if (!mapaIniciado) {
        cargarLeaflet().then(() => setTimeout(inicializarMapa, 100));
    } else {
        setTimeout(() => leafletMap.invalidateSize(), 200);
    }
}
function cerrarMapa() {
    document.getElementById('mapa-modal').classList.remove('visible');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', e => { if (e.key==='Escape') { cerrarFoto(); cerrarMapa(); } });
document.getElementById('mapa-modal').addEventListener('click', function(e) { if(e.target===this) cerrarMapa(); });

// ── Leaflet ────────────────────────────────────────────────────────
function cargarLeaflet() {
    return new Promise(resolve => {
        if (window.L) { resolve(); return; }
        const s = document.createElement('script');
        s.src = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js';
        s.onload = resolve; document.head.appendChild(s);
    });
}

// ── Loader: globo terráqueo girando en canvas 3D ────────────────────
function iniciarLoader() {
    const canvas = document.getElementById('globe-canvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const R   = 130;   // radio del globo
    canvas.width  = R * 2 + 20;
    canvas.height = R * 2 + 20;
    const cx = canvas.width  / 2;
    const cy = canvas.height / 2;

    // Continentes como polígonos en coordenadas geográficas [lon, lat]
    const continentes = [
        // América del Norte
        [[-168,72],[-140,68],[-120,60],[-100,50],[-80,42],[-70,44],[-60,46],
         [-64,50],[-52,54],[-56,58],[-70,62],[-85,66],[-100,70],[-120,72],[-140,74],[-168,72]],
        // América Central
        [[-90,20],[-78,8],[-76,10],[-84,10],[-90,14],[-92,18],[-90,20]],
        // América del Sur
        [[-80,12],[-62,12],[-50,4],[-36,-4],[-35,-10],[-40,-20],[-44,-24],
         [-48,-28],[-52,-34],[-56,-38],[-64,-42],[-66,-50],[-68,-56],[-72,-50],
         [-76,-46],[-80,-40],[-76,-34],[-80,-8],[-76,0],[-78,4],[-80,12]],
        // Groenlandia
        [[-50,84],[-22,84],[-18,76],[-24,70],[-44,60],[-52,66],[-56,76],[-50,84]],
        // Europa
        [[-10,36],[4,36],[10,44],[20,46],[30,46],[32,50],[28,56],[22,60],
         [14,58],[6,58],[0,54],[-4,50],[-8,44],[-10,36]],
        // Escandinavia
        [[4,58],[14,56],[28,56],[30,70],[22,72],[14,70],[6,62],[4,58]],
        // África
        [[-18,16],[0,14],[10,10],[20,6],[36,4],[42,12],[44,10],[42,-12],
         [36,-24],[26,-34],[18,-34],[14,-30],[10,-18],[6,-4],[0,4],
         [-4,4],[-8,4],[-16,12],[-18,16]],
        // Asia (bloque principal)
        [[26,46],[40,38],[50,28],[60,24],[70,22],[80,20],[90,22],[100,20],
         [110,18],[120,24],[130,34],[134,44],[130,54],[120,56],[110,52],
         [100,50],[90,50],[80,50],[70,52],[60,50],[50,44],[40,44],[34,46],[26,46]],
        // Siberia norte
        [[30,68],[60,72],[90,76],[120,74],[150,72],[168,68],[160,60],[140,56],
         [120,58],[100,60],[80,62],[60,64],[40,64],[30,68]],
        // India
        [[66,24],[78,8],[80,10],[88,20],[80,28],[72,26],[66,24]],
        // Indochina
        [[100,22],[106,20],[110,14],[104,4],[100,6],[96,10],[98,18],[100,22]],
        // Japón (simplificado)
        [[130,34],[132,34],[134,40],[132,44],[130,42],[128,38],[130,34]],
    // Australia
        [[114,-22],[122,-18],[132,-12],[138,-14],[148,-18],[152,-26],[150,-36],
         [144,-38],[134,-36],[124,-34],[116,-34],[112,-28],[114,-22]],
        // Nueva Zelanda norte
        [[172,-36],[178,-36],[178,-42],[174,-44],[170,-40],[172,-36]],
    ]

    let angulo = 0;   // rotación actual del globo

    function geoTo3D(lon, lat, rot) {
        // Proyección esférica con rotación en Y
        const phi   = (90 - lat)  * Math.PI / 180;
        const theta = (lon + rot) * Math.PI / 180;
        return {
            x: R * Math.sin(phi) * Math.cos(theta),
            y: R * Math.cos(phi),
            z: R * Math.sin(phi) * Math.sin(theta),
        };
    }

    function drawGlobe() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        // ── Sombra exterior ──
        const shadow = ctx.createRadialGradient(cx+20, cy+20, R*.6, cx, cy, R*1.2);
        shadow.addColorStop(0, 'rgba(0,0,0,0)');
        shadow.addColorStop(1, 'rgba(0,0,0,.45)');
        ctx.beginPath(); ctx.arc(cx+14, cy+14, R, 0, Math.PI*2);
        ctx.fillStyle='rgba(0,0,0,.25)'; ctx.fill();

        // ── Océano (esfera base) ──
        const ocean = ctx.createRadialGradient(cx-R*.3, cy-R*.3, R*.1, cx, cy, R);
        ocean.addColorStop(0,  '#0a2a1a');
        ocean.addColorStop(.5, '#061810');
        ocean.addColorStop(1,  '#030d08');
        ctx.beginPath(); ctx.arc(cx, cy, R, 0, Math.PI*2);
        ctx.fillStyle = ocean; ctx.fill();

        // ── Líneas de latitud ──
        for (let lat = -60; lat <= 60; lat += 30) {
            const pts3 = [];
            for (let lon = -180; lon <= 180; lon += 5) {
                const p = geoTo3D(lon, lat, angulo);
                if (p.z >= 0) pts3.push({x: cx + p.x, y: cy - p.y, first: lon===-180 || pts3.length===0});
            }
            if (pts3.length > 1) {
                ctx.beginPath();
                pts3.forEach((p,i) => i===0 ? ctx.moveTo(p.x,p.y) : ctx.lineTo(p.x,p.y));
                ctx.strokeStyle='rgba(57,169,0,.08)'; ctx.lineWidth=.5; ctx.stroke();
            }
        }
        // ── Líneas de longitud ──
        for (let lon = -180; lon < 180; lon += 30) {
            const pts3 = [];
            for (let lat = -90; lat <= 90; lat += 5) {
                const p = geoTo3D(lon, lat, angulo);
                if (p.z >= 0) pts3.push({x: cx + p.x, y: cy - p.y});
            }
            if (pts3.length > 1) {
                ctx.beginPath();
                pts3.forEach((p,i) => i===0 ? ctx.moveTo(p.x,p.y) : ctx.lineTo(p.x,p.y));
                ctx.strokeStyle='rgba(57,169,0,.08)'; ctx.lineWidth=.5; ctx.stroke();
            }
        }

        // ── Continentes ──
        continentes.forEach(poly => {
            const pts3 = poly.map(([lon,lat]) => {
                const p = geoTo3D(lon, lat, angulo);
                return { x: cx+p.x, y: cy-p.y, visible: p.z >= -R*.1 };
            });
            // Solo dibujar si la mayoría de puntos son visibles
            const visibles = pts3.filter(p=>p.visible).length;
            if (visibles < pts3.length * .5) return;

            ctx.beginPath();
            pts3.forEach((p,i) => {
                if (i===0) ctx.moveTo(p.x,p.y);
                else ctx.lineTo(p.x,p.y);
            });
            ctx.closePath();

            // Relleno con gradiente verde oscuro
            ctx.fillStyle   = 'rgba(57,169,0,.18)';
            ctx.strokeStyle = '#39a900';
            ctx.lineWidth   = .8;
            ctx.fill();
            ctx.stroke();
        });

        // ── Brillo especular (luz desde arriba-izquierda) ──
        const glare = ctx.createRadialGradient(cx-R*.4, cy-R*.4, R*.05, cx-R*.2, cy-R*.2, R*.9);
        glare.addColorStop(0,   'rgba(255,255,255,.12)');
        glare.addColorStop(.4,  'rgba(255,255,255,.03)');
        glare.addColorStop(1,   'rgba(0,0,0,0)');
        ctx.beginPath(); ctx.arc(cx, cy, R, 0, Math.PI*2);
        ctx.fillStyle = glare; ctx.fill();

        // ── Borde del globo ──
        ctx.beginPath(); ctx.arc(cx, cy, R, 0, Math.PI*2);
        ctx.strokeStyle = 'rgba(57,169,0,.4)';
        ctx.lineWidth = 1.5;
        ctx.stroke();

        // ── Pin Colombia ── (lon=-74, lat=4.7)
        const colP = geoTo3D(-74, 4.7, angulo);
        if (colP.z > 0) {
            const px = cx + colP.x, py = cy - colP.y;
            // Pulso
            ctx.beginPath(); ctx.arc(px, py, 10 + Math.sin(Date.now()*.003)*4, 0, Math.PI*2);
            ctx.strokeStyle = 'rgba(57,169,0,.4)'; ctx.lineWidth=1; ctx.stroke();
            // Gota
            ctx.beginPath();
            ctx.arc(px, py-2, 5, 0, Math.PI*2);
            ctx.fillStyle='#39a900';
            ctx.shadowColor='#39a900'; ctx.shadowBlur=10;
            ctx.fill();
            ctx.shadowBlur=0;
            // Punto blanco interior
            ctx.beginPath(); ctx.arc(px, py-2, 2, 0, Math.PI*2);
            ctx.fillStyle='#060d06'; ctx.fill();
        }

        angulo += .35;   // velocidad de rotación
        if (angulo > 360) angulo -= 360;
    }

    let animId = setInterval(drawGlobe, 16);  // ~60fps

    // HUD coordenadas
    const coords=['4.5709° N · 74.2973° W','6.2442° N · 75.5812° W','3.4516° N · 76.5320° W','10.9639° N · 74.7964° W','1.2136° N · 77.2811° W'];
    const stats =['INICIANDO · · ·','CONECTANDO · · ·','PROCESANDO · · ·','MAPEANDO · · · ','LISTO ✓'];
    let ci=0;
    const timer=setInterval(()=>{
        ci=(ci+1)%coords.length;
        const ce=document.getElementById('ld-coords'),se=document.getElementById('ld-status');
        if(ce)ce.textContent=coords[ci]; if(se)se.textContent=stats[ci];
    },650);

    const loader=document.getElementById('mapa-loader');
    loader._animId=animId; loader._timer=timer; loader._isInterval=true;
}

// ── Inicializar mapa principal ──────────────────────────────────────
function inicializarMapa() {
    mapaIniciado = true;
    iniciarLoader();
    const loader = document.getElementById('mapa-loader');

    leafletMap = L.map('leaflet-map',{center:[20,-30],zoom:2,zoomControl:false,attributionControl:false});

    // Tile oscuro base
    const darkTile = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',{maxZoom:19});
    // Tile satélite (ESRI, gratuito sin key)
    const satTile  = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',{maxZoom:19});

    darkTile.addTo(leafletMap);
    L.control.zoom({position:'topright'}).addTo(leafletMap);
    L.control.layers({'Oscuro':darkTile,'Satélite':satTile},{},{position:'topright'}).addTo(leafletMap);

    // ── Tooltip hover ──────────────────────────────────────────────
    const tooltip = document.createElement('div');
    tooltip.style.cssText = 'position:absolute;z-index:800;pointer-events:none;background:rgba(6,13,6,.92);border:1px solid rgba(57,169,0,.5);color:#39a900;font-size:13px;font-weight:700;padding:7px 14px;border-radius:20px;white-space:nowrap;backdrop-filter:blur(6px);box-shadow:0 4px 20px rgba(0,0,0,.5);opacity:0;transition:opacity .18s ease;';
    document.getElementById('leaflet-map').appendChild(tooltip);
    leafletMap.on('mousemove', function(e) {
        const pt = leafletMap.latLngToContainerPoint(e.latlng);
        tooltip.style.left = (pt.x + 16) + 'px';
        tooltip.style.top  = (pt.y - 20) + 'px';
    });

    // Icono marcador
    function mkIcon() {
        return L.divIcon({
            className:'',
            html:`<div style="position:relative;width:34px;height:34px;display:flex;align-items:center;justify-content:center;">
                    <div style="position:absolute;inset:0;border-radius:50%;background:rgba(57,169,0,.2);border:2px solid rgba(57,169,0,.5);animation:pulseRing 2s ease-out infinite;"></div>
                    <div style="width:22px;height:22px;background:linear-gradient(135deg,#39a900,#2d6e00);border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:2px solid rgba(255,255,255,.4);box-shadow:0 3px 10px rgba(0,0,0,.4);"></div>
                  </div>`,
            iconSize:[34,34],iconAnchor:[17,34],popupAnchor:[0,-36],
        });
    }

    const markers=[];
    REGIONALES.forEach(r=>{
        const mk=L.marker([r.lat,r.lng],{icon:mkIcon(),title:r.nombre,opacity:0}).addTo(leafletMap);

        mk.bindPopup(`<div class="popup-reg">
            <div class="popup-titulo"><i class="fas fa-map-marker-alt"></i>${r.nombre}</div>
            ${r.ciudad?`<div class="popup-row"><i class="fas fa-city"></i>${r.ciudad}</div>`:''}
            ${r.direccion?`<div class="popup-row"><i class="fas fa-map-pin"></i>${r.direccion}</div>`:''}
            ${r.telefono?`<div class="popup-row"><i class="fas fa-phone"></i>${r.telefono}</div>`:''}
            <a href="${r.url}" class="popup-btn popup-btn-green"><i class="fas fa-arrow-right"></i> Ver regional</a>
            <button class="popup-btn popup-btn-outline" onclick='seleccionarRegional(${JSON.stringify(r).replace(/'/g,"&#39;")})'>
                <i class="fas fa-info-circle"></i> Detalles, satélite y fotos
            </button>
        </div>`,{maxWidth:260,minWidth:210});

        mk.on('mouseover', () => {
            tooltip.textContent = r.nombre + (r.ciudad ? '  ·  ' + r.ciudad : '');
            tooltip.style.opacity = '1';
        });
        mk.on('mouseout', () => { tooltip.style.opacity = '0'; });

        mk.on('click', () => {
            tooltip.style.opacity = '0';
            leafletMap.flyTo([r.lat,r.lng],14,{animate:true,duration:1.6,easeLinearity:.25});
        });
        markers.push(mk);
    });

    // Animación entrada
    setTimeout(()=>{
        leafletMap.flyTo([4.5709,-74.2973],5.5,{animate:true,duration:2.4,easeLinearity:.2});
    },400);
    setTimeout(()=>{
        loader.classList.add('hidden');
        setTimeout(()=>{
            if(loader._animId){if(loader._isInterval)clearInterval(loader._animId);else cancelAnimationFrame(loader._animId);}
            if(loader._timer)clearInterval(loader._timer);
            const gc=document.getElementById('globe-canvas');if(gc)gc.getContext('2d').clearRect(0,0,gc.width,gc.height);
            if(loader._ctx)loader._ctx.clearRect(0,0,loader._canvas.width,loader._canvas.height);
        },850);
        markers.forEach((m,i)=>setTimeout(()=>m.setOpacity(1),i*150));
    },3200);

    if(!document.getElementById('lf-anim')){
        const st=document.createElement('style');st.id='lf-anim';
        st.textContent='@keyframes pulseRing{0%{transform:scale(.6);opacity:1}100%{transform:scale(2.2);opacity:0}}';
        document.head.appendChild(st);
    }
}

// ── Panel lateral ───────────────────────────────────────────────────
function seleccionarRegional(r) {
    regionalActual = r;
    document.getElementById('panel-nombre').textContent = r.nombre;
    document.getElementById('panel-ciudad').innerHTML   = `<i class="fas fa-city"></i> ${r.ciudad||'Colombia'}`;
    document.getElementById('info-panel').classList.add('abierto');
    setTimeout(()=>leafletMap.invalidateSize(),420);
    cambiarTab('info');
}

function cerrarPanel() {
    document.getElementById('info-panel').classList.remove('abierto');
    setTimeout(()=>leafletMap.invalidateSize(),420);
    if (miniMap) { miniMap.remove(); miniMap=null; }
    // Volver a vista de Colombia
    setTimeout(()=>{
        if (leafletMap) leafletMap.flyTo([4.5709,-74.2973], 5.5, {animate:true,duration:1.4,easeLinearity:.3});
    },450);
    regionalActual = null;
}

function cambiarTab(tab) {
    tabActual = tab;
    document.querySelectorAll('.panel-tab').forEach((t,i)=>t.classList.toggle('active',['info','mapa','fotos'][i]===tab));
    if (!regionalActual) return;
    if (tab==='info')  renderInfo(regionalActual);
    if (tab==='mapa')  renderSatelite(regionalActual);
    if (tab==='fotos') renderFotos(regionalActual);
}

function renderInfo(r) {
    document.getElementById('panel-body').innerHTML = `
        <div class="info-row">
            <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
            <div><div class="info-label">Regional</div><div class="info-val">${r.nombre}</div></div>
        </div>
        ${r.ciudad?`<div class="info-row"><div class="info-icon"><i class="fas fa-city"></i></div><div><div class="info-label">Ciudad</div><div class="info-val">${r.ciudad}</div></div></div>`:''}
        ${r.codigo?`<div class="info-row"><div class="info-icon"><i class="fas fa-hashtag"></i></div><div><div class="info-label">Código</div><div class="info-val">${r.codigo}</div></div></div>`:''}
        ${r.direccion?`<div class="info-row"><div class="info-icon"><i class="fas fa-map-pin"></i></div><div><div class="info-label">Dirección</div><div class="info-val">${r.direccion}</div></div></div>`:''}
        ${r.telefono?`<div class="info-row"><div class="info-icon"><i class="fas fa-phone"></i></div><div><div class="info-label">Teléfono</div><div class="info-val">${r.telefono}</div></div></div>`:''}
        <div style="margin-top:16px;">
            <a href="${r.url}" class="panel-btn panel-btn-green">
                <i class="fas fa-arrow-right"></i> Ver regional
            </a>
            <a href="https://www.openstreetmap.org/?mlat=${r.lat}&mlon=${r.lng}#map=16/${r.lat}/${r.lng}"
               target="_blank" class="panel-btn panel-btn-outline">
                <i class="fas fa-external-link-alt"></i> Ver en OpenStreetMap
            </a>
        </div>`;
}

function renderSatelite(r) {
    document.getElementById('panel-body').innerHTML = `
        <div id="mini-sat">
            <div id="mini-sat-map"></div>
            <div class="sat-badge"><i class="fas fa-satellite"></i> Satélite</div>
        </div>
        <p style="font-size:11px;color:rgba(255,255,255,.35);text-align:center;margin-bottom:12px;">
            Vista satelital de ${r.ciudad||r.nombre} — ESRI World Imagery
        </p>
        <a href="https://www.openstreetmap.org/?mlat=${r.lat}&mlon=${r.lng}#map=17/${r.lat}/${r.lng}"
           target="_blank" class="panel-btn panel-btn-outline">
            <i class="fas fa-external-link-alt"></i> Abrir en mapa completo
        </a>`;

    // Mini mapa satélite embebido con Leaflet
    setTimeout(()=>{
        if (miniMap) { miniMap.remove(); miniMap=null; }
        miniMap = L.map('mini-sat-map',{
            center:[r.lat,r.lng], zoom:15,
            zoomControl:false, attributionControl:false,
            dragging:false, scrollWheelZoom:false,
        });
        L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',{maxZoom:19}).addTo(miniMap);
        // Marcador en el mini mapa
        L.marker([r.lat,r.lng]).addTo(miniMap);
    },100);
}

function renderFotos(r) {
    const body = document.getElementById('panel-body');
    body.innerHTML = `
        <div style="text-align:center;padding:10px 0 16px;">
            <div class="skeleton" style="height:160px;border-radius:10px;margin-bottom:6px;"></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;">
                <div class="skeleton" style="height:80px;border-radius:8px;"></div>
                <div class="skeleton" style="height:80px;border-radius:8px;"></div>
            </div>
        </div>
        <p style="text-align:center;font-size:11px;color:rgba(255,255,255,.3);">Buscando fotos en Wikimedia...</p>`;

    // Buscar fotos en Wikimedia Commons usando la ciudad
    const termino = `SENA ${r.ciudad || r.nombre} Colombia`;
    const url = `https://en.wikipedia.org/w/api.php?action=query&list=search&srsearch=${encodeURIComponent(termino)}&format=json&origin=*&srlimit=3`;

    fetch(url)
        .then(res=>res.json())
        .then(data=>{
            const pages = data?.query?.search || [];
            if (!pages.length) { buscarFotosWikimedia(r, body); return; }
            // Tomar el primer resultado y buscar imágenes de esa página
            const titulo = pages[0].title;
            buscarImagenesWikipedia(titulo, r, body);
        })
        .catch(()=>buscarFotosWikimedia(r, body));
}

function buscarImagenesWikipedia(titulo, r, body) {
    const url = `https://en.wikipedia.org/w/api.php?action=query&titles=${encodeURIComponent(titulo)}&prop=images&format=json&origin=*&imlimit=10`;
    fetch(url)
        .then(res=>res.json())
        .then(data=>{
            const pages  = Object.values(data?.query?.pages||{});
            const images = (pages[0]?.images||[])
                .map(i=>i.title)
                .filter(t=>/\.(jpg|jpeg|png)$/i.test(t));
            if (!images.length) { buscarFotosWikimedia(r, body); return; }
            // Obtener URLs de las imágenes
            const names = images.slice(0,6).map(t=>encodeURIComponent(t)).join('|');
            const urlInfo = `https://en.wikipedia.org/w/api.php?action=query&titles=${names}&prop=imageinfo&iiprop=url|thumburl&iiurlwidth=600&format=json&origin=*`;
            fetch(urlInfo)
                .then(res=>res.json())
                .then(d=>{
                    const imgs = Object.values(d?.query?.pages||{})
                        .map(p=>p?.imageinfo?.[0]?.thumburl)
                        .filter(Boolean);
                    renderFotosGrid(imgs, r, body);
                })
                .catch(()=>buscarFotosWikimedia(r, body));
        })
        .catch(()=>buscarFotosWikimedia(r, body));
}

function buscarFotosWikimedia(r, body) {
    // Buscar directamente en Wikimedia Commons
    const q = `${r.ciudad || r.nombre} Colombia SENA`;
    const url = `https://commons.wikimedia.org/w/api.php?action=query&list=search&srsearch=${encodeURIComponent(q)}&srnamespace=6&format=json&origin=*&srlimit=8`;
    fetch(url)
        .then(res=>res.json())
        .then(data=>{
            const items = (data?.query?.search||[]).map(i=>i.title).filter(t=>/\.(jpg|jpeg|png)$/i.test(t));
            if (!items.length) { mostrarSinFotos(r, body); return; }
            const names = items.slice(0,6).map(t=>encodeURIComponent(t)).join('|');
            const urlInfo = `https://commons.wikimedia.org/w/api.php?action=query&titles=${names}&prop=imageinfo&iiprop=url|thumburl&iiurlwidth=600&format=json&origin=*`;
            fetch(urlInfo)
                .then(res=>res.json())
                .then(d=>{
                    const imgs = Object.values(d?.query?.pages||{})
                        .map(p=>p?.imageinfo?.[0]?.thumburl)
                        .filter(Boolean);
                    if (!imgs.length) mostrarSinFotos(r, body);
                    else renderFotosGrid(imgs, r, body);
                })
                .catch(()=>mostrarSinFotos(r, body));
        })
        .catch(()=>mostrarSinFotos(r, body));
}

function renderFotosGrid(imgs, r, body) {
    if (!imgs.length) { mostrarSinFotos(r, body); return; }
    const fotosHtml = imgs.map((src,i)=>`
        <div class="${i===0?'foto-item foto-item-tall':'foto-item foto-item-sq'}"
             onclick="verFoto('${src}')">
            <img src="${src}" alt="Foto ${i+1}" loading="lazy"
                 onerror="this.parentElement.style.display='none'">
        </div>`).join('');

    body.innerHTML = `
        <p style="font-size:11px;color:rgba(255,255,255,.3);margin-bottom:10px;text-align:center;">
            <i class="fas fa-images"></i> Fotos de ${r.ciudad||r.nombre} — Wikimedia Commons
        </p>
        <div class="fotos-grid">${fotosHtml}</div>
        <p style="font-size:10px;color:rgba(255,255,255,.2);text-align:center;margin-top:8px;">
            Imágenes de dominio público — Wikimedia Commons
        </p>`;
}

function mostrarSinFotos(r, body) {
    body.innerHTML = `
        <div class="panel-empty">
            <i class="fas fa-image"></i>
            <p>No se encontraron fotos de ${r.ciudad||r.nombre} en Wikimedia</p>
        </div>
        <a href="https://commons.wikimedia.org/w/index.php?search=${encodeURIComponent(r.ciudad+' Colombia')}"
           target="_blank" class="panel-btn panel-btn-outline" style="margin-top:8px;">
            <i class="fas fa-external-link-alt"></i> Buscar en Wikimedia Commons
        </a>`;
}

// ── Visor foto fullscreen ───────────────────────────────────────────
function verFoto(src) {
    document.getElementById('foto-viewer-img').src = src;
    document.getElementById('foto-viewer').classList.add('visible');
}
function cerrarFoto() {
    document.getElementById('foto-viewer').classList.remove('visible');
}
document.getElementById('foto-viewer').addEventListener('click', function(e) {
    if (e.target===this) cerrarFoto();
});
</script>
</body>
</html>