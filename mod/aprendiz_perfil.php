<?php
// mod/aprendiz_perfil.php — Perfil completo del aprendiz
session_start();
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../conexion/AprendizDAO.php';
require_once __DIR__ . '/../conexion/AsistenciaDAO.php';

// ── Cargar datos según rol ────────────────────────────────────────
$rolUsuario  = $_SESSION['usuario_rol']    ?? '';
$nombreSesion = $_SESSION['usuario_nombre'] ?? 'Usuario';
$emailSesion  = $_SESSION['usuario_email']  ?? '';
$refId        = (int)($_SESSION['usuario_ref_id'] ?? 0);
$usuarioId    = (int)($_SESSION['usuario_id']     ?? 0);

$datos = [];  // datos completos del perfil

// ── Clase anónima para queries directas ─────────────────────────
class PerfilDB extends BaseDatos {
    protected function consultar() {}
    protected function insertar() {}
    protected function actualizar() {}
    protected function eliminar() {}
    public function query($sql, $params = []) {
        $stmt = $this->ejecutarPreparado($sql, $params);
        return $stmt ? $stmt->fetch() : null;
    }
}
$db = new PerfilDB();

if (esAprendiz()) {
    $aprendizId   = (int)($_SESSION['usuario_ref_id'] ?? 0);
    $usuarioEmail = $_SESSION['usuario_email'] ?? '';

    $aprendizDAO = new AprendizDAO();
    $datos = $aprendizDAO->obtenerPorSesion($aprendizId, $usuarioId, $usuarioEmail);

    if (!empty($datos['APRENDIZ_ID'])) {
        $datos['PROMEDIO_GENERAL'] = $datos['PROMEDIO_GENERAL'] ?? $aprendizDAO->obtenerPromedioEvidencias($datos['APRENDIZ_ID']);
        $datos['PORCENTAJE_ASISTENCIA'] = $datos['PORCENTAJE_ASISTENCIA'] ?? (new AsistenciaDAO())->obtenerPorcentajeAsistencia($datos['APRENDIZ_ID']);
        $datos['COMPETENCIAS_APROBADAS'] = $datos['COMPETENCIAS_APROBADAS'] ?? $aprendizDAO->obtenerEvidenciasAprobadas($datos['APRENDIZ_ID']);
        $datos['COMPETENCIAS_PENDIENTES'] = $datos['COMPETENCIAS_PENDIENTES'] ?? $aprendizDAO->obtenerEvidenciasPendientes($datos['APRENDIZ_ID']);
    }

} elseif (esInstructor()) {
    $sql = "SELECT
                i.INSTRUCTOR_ID, i.NOMBRES, i.APELLIDOS, i.EMAIL, i.ESPECIALIDAD,
                (SELECT COUNT(*) FROM instructor_ficha WHERE INSTRUCTOR_ID = i.INSTRUCTOR_ID) AS total_fichas,
                (SELECT COUNT(DISTINCT a.APRENDIZ_ID)
                 FROM instructor_ficha ifi
                 INNER JOIN ficha f ON ifi.FICHA_ID = f.FICHA_ID
                 INNER JOIN aprendiz a ON a.FICHA_ID = f.FICHA_ID
                 WHERE ifi.INSTRUCTOR_ID = i.INSTRUCTOR_ID) AS total_aprendices,
                c.NOMBRE  AS centro_nombre,
                r.NOMBRE  AS regional_nombre,
                r.CIUDAD  AS regional_ciudad
            FROM instructor i
            LEFT JOIN instructor_ficha ifi2 ON ifi2.INSTRUCTOR_ID = i.INSTRUCTOR_ID
            LEFT JOIN ficha   f2 ON ifi2.FICHA_ID  = f2.FICHA_ID
            LEFT JOIN centro  c  ON f2.CENTRO_ID   = c.CENTRO_ID
            LEFT JOIN regional r ON c.REGIONAL_ID  = r.REGIONAL_ID
            WHERE i.INSTRUCTOR_ID = :id
            GROUP BY i.INSTRUCTOR_ID
            LIMIT 1";
    $datos = $db->query($sql, [':id' => $refId]);

} elseif (esAdmin()) {
    $datos = [
        'NOMBRES'   => $nombreSesion,
        'APELLIDOS' => '',
        'EMAIL'     => $emailSesion,
    ];
}

$datos = $datos ?: [];

// Nombre a mostrar
$nombre = trim(($datos['NOMBRES'] ?? $nombreSesion) . ' ' . ($datos['APELLIDOS'] ?? ''));
$email  = $datos['EMAIL'] ?? $emailSesion;

$iniciales = strtoupper(
    substr($datos['NOMBRES'] ?? $nombreSesion, 0, 1) .
    substr($datos['APELLIDOS'] ?? '', 0, 1)
);
if ($iniciales === '') $iniciales = strtoupper(substr($nombreSesion, 0, 2));

switch ($rolUsuario) {
    case 'admin':
        $rolLabel = ['label' => 'Administrador', 'color' => '#dc2626', 'icon' => 'fa-shield-halved'];
        break;
    case 'instructor':
        $rolLabel = ['label' => 'Instructor', 'color' => '#3b82f6', 'icon' => 'fa-chalkboard-teacher'];
        break;
    case 'aprendiz':
        $rolLabel = ['label' => 'Aprendiz', 'color' => '#39a900', 'icon' => 'fa-user-graduate'];
        break;
    default:
        $rolLabel = ['label' => ucfirst($rolUsuario), 'color' => '#64748b', 'icon' => 'fa-user'];
        break;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mi Perfil — DTD SENA</title>
<link rel="stylesheet" href="<?= htmlspecialchars(asset_url('css/style.css')) ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* ══ HERO ══════════════════════════════════════════════════════ */
.perfil-hero {
    background: linear-gradient(135deg, #1a2e1a, #0d1f0d);
    border-radius: 28px;
    padding: 40px;
    margin-bottom: 28px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 20px 50px rgba(0,0,0,.35);
    border: 1px solid rgba(57,169,0,.2);
}
.perfil-hero::before {
    content: '';
    position: absolute; inset: 0;
    background: radial-gradient(circle at 70% 30%, rgba(57,169,0,.12) 0%, transparent 60%);
    pointer-events: none;
}
.perfil-hero-inner {
    display: flex; align-items: center; gap: 32px; flex-wrap: wrap;
    position: relative; z-index: 2;
}
.perfil-avatar-ring {
    position: relative; flex-shrink: 0;
}
.perfil-avatar-ring::after {
    content: '';
    position: absolute; inset: -4px;
    border-radius: 50%;
    background: conic-gradient(#39a900 0%, #2d8a00 40%, #39a900 70%, transparent 100%);
    z-index: -1;
    animation: spinRing 6s linear infinite;
}
@keyframes spinRing { to { transform: rotate(360deg); } }
.perfil-avatar {
    width: 100px; height: 100px;
    border-radius: 50%;
    background: linear-gradient(135deg, #39a900, #1a5c00);
    display: flex; align-items: center; justify-content: center;
    font-size: 40px; font-weight: 800; color: #fff;
    border: 4px solid #060d06;
    box-shadow: 0 8px 25px rgba(57,169,0,.4);
}
.perfil-hero-info { flex: 1; min-width: 200px; }
.perfil-nombre {
    font-size: 28px; font-weight: 800;
    color: #fff; margin-bottom: 6px;
    text-shadow: 0 2px 8px rgba(0,0,0,.4);
}
.perfil-email { font-size: 14px; color: rgba(255,255,255,.55); margin-bottom: 14px; }
.perfil-rol-badge {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 8px 18px; border-radius: 30px;
    font-size: 13px; font-weight: 700; letter-spacing: .3px;
    backdrop-filter: blur(6px);
    border: 1px solid rgba(255,255,255,.15);
}
/* ══ GRID DE SECCIONES ════════════════════════════════════════ */
.perfil-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}
.perfil-card {
    background: var(--color-blanco);
    border: 1px solid var(--border-color);
    border-radius: 20px;
    overflow: hidden;
    box-shadow: var(--shadow-card);
    transition: .25s;
}
.perfil-card:hover { transform: translateY(-3px); border-color: var(--color-verde-1); }
.perfil-card-header {
    padding: 14px 20px;
    display: flex; align-items: center; gap: 10px;
    border-bottom: 1px solid var(--border-color);
    font-weight: 800; font-size: 14px; color: var(--color-texto);
}
.perfil-card-header i { color: var(--color-verde-1); font-size: 16px; }
.perfil-card-body { padding: 18px 20px; }
/* ══ FILAS DE DATO ════════════════════════════════════════════ */
.dato-row {
    display: flex; align-items: flex-start; gap: 12px;
    padding: 9px 0;
    border-bottom: 1px solid rgba(57,169,0,.07);
}
.dato-row:last-child { border-bottom: none; }
.dato-icon {
    width: 32px; height: 32px;
    background: rgba(57,169,0,.09);
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.dato-icon i { color: var(--color-verde-1); font-size: 13px; }
.dato-label { font-size: 10px; color: var(--color-texto-secundario); font-weight: 700; text-transform: uppercase; letter-spacing: .5px; }
.dato-val   { font-size: 14px; color: var(--color-texto); font-weight: 600; margin-top: 1px; }
/* ══ STATS MINI ═══════════════════════════════════════════════ */
.mini-stats { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
.mini-stat {
    background: var(--color-gris-fondo);
    border-radius: 12px; padding: 14px;
    text-align: center; border: 1px solid var(--border-color);
}
.mini-stat-val { font-size: 24px; font-weight: 800; color: var(--color-verde-1); line-height: 1; }
.mini-stat-lbl { font-size: 11px; color: var(--color-texto-secundario); margin-top: 4px; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; }
/* ══ BARRA PORCENTAJE ══════════════════════════════════════════ */
.pct-bar-bg   { height: 10px; background: var(--color-gris-fondo); border-radius: 20px; overflow: hidden; }
.pct-bar-fill { height: 100%; border-radius: 20px; transition: width .8s cubic-bezier(.4,0,.2,1); }
/* ══ CHIP ESTADO ══════════════════════════════════════════════ */
.chip { display: inline-block; padding: 3px 11px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.chip-green  { background: rgba(57,169,0,.1);  color: var(--color-verde-1); }
.chip-red    { background: rgba(220,38,38,.1); color: #dc2626; }
.chip-yellow { background: rgba(245,158,11,.1); color: #f59e0b; }
/* ══ DARK ══════════════════════════════════════════════════════ */
.dark-mode .perfil-card { background: var(--color-gris-cuerpo); }
.dark-mode .mini-stat   { background: var(--color-gris-fondo); }
/* ══ BREADCRUMB ═══════════════════════════════════════════════ */
.back-row { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
</style>
</head>
<body>
<div id="loader"><img src="<?= htmlspecialchars(asset_url('img/logo_sena_verde.png')) ?>" alt="" id="loader-logo"></div>
<?php include '../config/header.php'; ?>

<main class="container" id="contenido-principal" style="display:none;opacity:0;">

    <!-- Volver según rol -->
    <div class="back-row">
        <?php if (esAprendiz()): ?>
        <a href="<?= htmlspecialchars(app_url('mod/aprendiz/index.php')) ?>" class="btn-view-all"><i class="fas fa-arrow-left"></i> Inicio</a>
        <?php elseif (esInstructor()): ?>
        <a href="<?= htmlspecialchars(app_url('mod/instructor_dashboard.php')) ?>" class="btn-view-all"><i class="fas fa-arrow-left"></i> Dashboard</a>
        <?php else: ?>
        <a href="<?= htmlspecialchars(app_url('index.php')) ?>" class="btn-view-all"><i class="fas fa-arrow-left"></i> Inicio</a>
        <?php endif; ?>
        <span style="font-size:13px;color:var(--color-texto-secundario);">
            <i class="fas fa-home"></i> / Mi cuenta / Perfil
        </span>
    </div>

    <!-- HERO -->
    <div class="perfil-hero">
        <div class="perfil-hero-inner">
            <div class="perfil-avatar-ring">
                <div class="perfil-avatar"><?= htmlspecialchars($iniciales) ?></div>
            </div>
            <div class="perfil-hero-info">
                <div class="perfil-nombre"><?= htmlspecialchars($nombre) ?></div>
                <div class="perfil-email"><i class="fas fa-envelope" style="color:rgba(57,169,0,.7);margin-right:5px;"></i><?= htmlspecialchars($email) ?></div>
                <span class="perfil-rol-badge" style="background:<?= $rolLabel['color'] ?>22;border-color:<?= $rolLabel['color'] ?>44;color:<?= $rolLabel['color'] ?>;">
                    <i class="fas <?= $rolLabel['icon'] ?>"></i>
                    <?= $rolLabel['label'] ?>
                </span>
            </div>
        </div>
    </div>

    <?php if (esAprendiz() && empty($datos)): ?>
        <div class="perfil-card" style="background:#fff3cd;border:1px solid #ffeeba;color:#856404;">
            <div class="perfil-card-header"><i class="fas fa-exclamation-circle"></i> Información no disponible</div>
            <div class="perfil-card-body">
                <p>Hubo un problema al cargar tu perfil. Por favor refresca la página o contacta al administrador si el problema persiste.</p>
            </div>
        </div>
    <?php endif; ?>

    <!-- ════ APRENDIZ ════════════════════════════════════════════ -->
    <?php if (esAprendiz() && !empty($datos)): ?>

    <div class="perfil-grid">
        <!-- Datos personales -->
        <div class="perfil-card">
            <div class="perfil-card-header"><i class="fas fa-id-card"></i> Datos Personales</div>
            <div class="perfil-card-body">
                <div class="dato-row">
                    <div class="dato-icon"><i class="fas fa-hashtag"></i></div>
                    <div><div class="dato-label">Documento</div><div class="dato-val"><?= htmlspecialchars(($datos['TIPO_DOCUMENTO']??'').' '.($datos['NUMERO_DOCUMENTO']??'')) ?></div></div>
                </div>
                <?php if (!empty($datos['FECHA_NACIMIENTO'])): ?>
                <div class="dato-row">
                    <div class="dato-icon"><i class="fas fa-cake-candles"></i></div>
                    <div>
                        <div class="dato-label">Fecha nacimiento</div>
                        <div class="dato-val">
                            <?= date('d/m/Y', strtotime($datos['FECHA_NACIMIENTO'])) ?>
                            <span style="color:var(--color-texto-secundario);font-size:12px;">
                                (<?= (new DateTime())->diff(new DateTime($datos['FECHA_NACIMIENTO']))->y ?> años)
                            </span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (!empty($datos['GENERO'])): ?>
                <div class="dato-row">
                    <div class="dato-icon"><i class="fas fa-venus-mars"></i></div>
                    <div><div class="dato-label">Género</div><div class="dato-val"><?= htmlspecialchars($datos['GENERO']) ?></div></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($datos['TELEFONO'])): ?>
                <div class="dato-row">
                    <div class="dato-icon"><i class="fas fa-phone"></i></div>
                    <div><div class="dato-label">Teléfono</div><div class="dato-val"><?= htmlspecialchars($datos['TELEFONO']) ?></div></div>
                </div>
                <?php endif; ?>
                <div class="dato-row">
                    <div class="dato-icon"><i class="fas fa-circle-check"></i></div>
                    <div>
                        <div class="dato-label">Estado académico</div>
                        <div class="dato-val">
                            <?php $ea = strtolower($datos['ESTADO_ACADEMICO']??'activo'); ?>
                            <span class="chip <?= $ea==='activo'?'chip-green':($ea==='retirado'?'chip-red':'chip-yellow') ?>">
                                <?= htmlspecialchars($datos['ESTADO_ACADEMICO']??'Activo') ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php if (!empty($datos['FECHA_REGISTRO'])): ?>
                <div class="dato-row">
                    <div class="dato-icon"><i class="fas fa-calendar-plus"></i></div>
                    <div><div class="dato-label">Registrado</div><div class="dato-val"><?= date('d/m/Y', strtotime($datos['FECHA_REGISTRO'])) ?></div></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Ficha / Programa -->
        <div class="perfil-card">
            <div class="perfil-card-header"><i class="fas fa-layer-group"></i> Ficha y Programa</div>
            <div class="perfil-card-body">
                <?php if (!empty($datos['CODIGO_FICHA'])): ?>
                <div class="dato-row">
                    <div class="dato-icon"><i class="fas fa-layer-group"></i></div>
                    <div>
                        <div class="dato-label">Ficha</div>
                        <div class="dato-val">
                            <?= htmlspecialchars($datos['CODIGO_FICHA']) ?>
                            <?php if (!empty($datos['ficha_estado'])): ?>
                                <span class="chip chip-green" style="margin-left:6px;"><?= htmlspecialchars($datos['ficha_estado']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php if (!empty($datos['FECHA_INICIO'])): ?>
                <div class="dato-row">
                    <div class="dato-icon"><i class="fas fa-calendar-alt"></i></div>
                    <div>
                        <div class="dato-label">Periodo</div>
                        <div class="dato-val"><?= date('d/m/Y', strtotime($datos['FECHA_INICIO'])) ?> – <?= !empty($datos['FECHA_FIN']) ? date('d/m/Y', strtotime($datos['FECHA_FIN'])) : '—' ?></div>
                    </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>
                <?php if (!empty($datos['programa_nombre'])): ?>
                <div class="dato-row">
                    <div class="dato-icon"><i class="fas fa-graduation-cap"></i></div>
                    <div><div class="dato-label">Programa</div><div class="dato-val"><?= htmlspecialchars($datos['programa_nombre']) ?></div></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($datos['NIVEL_FORMACION'])): ?>
                <div class="dato-row">
                    <div class="dato-icon"><i class="fas fa-award"></i></div>
                    <div><div class="dato-label">Nivel</div><div class="dato-val"><?= htmlspecialchars($datos['NIVEL_FORMACION']) ?></div></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($datos['DURACION_MESES'])): ?>
                <div class="dato-row">
                    <div class="dato-icon"><i class="fas fa-clock"></i></div>
                    <div><div class="dato-label">Duración</div><div class="dato-val"><?= (int)$datos['DURACION_MESES'] ?> meses</div></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Centro / Regional -->
        <div class="perfil-card">
            <div class="perfil-card-header"><i class="fas fa-building"></i> Centro y Regional</div>
            <div class="perfil-card-body">
                <?php if (!empty($datos['centro_nombre'])): ?>
                <div class="dato-row">
                    <div class="dato-icon"><i class="fas fa-building"></i></div>
                    <div><div class="dato-label">Centro de formación</div><div class="dato-val"><?= htmlspecialchars($datos['centro_nombre']) ?></div></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($datos['centro_dir'])): ?>
                <div class="dato-row">
                    <div class="dato-icon"><i class="fas fa-map-pin"></i></div>
                    <div><div class="dato-label">Dirección centro</div><div class="dato-val"><?= htmlspecialchars($datos['centro_dir']) ?></div></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($datos['regional_nombre'])): ?>
                <div class="dato-row">
                    <div class="dato-icon"><i class="fas fa-map-marked-alt"></i></div>
                    <div>
                        <div class="dato-label">Regional</div>
                        <div class="dato-val">
                            <?= htmlspecialchars($datos['regional_nombre']) ?>
                            <?php if (!empty($datos['regional_ciudad'])): ?>
                                <span style="color:var(--color-texto-secundario);font-size:12px;"> — <?= htmlspecialchars($datos['regional_ciudad']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (empty($datos['centro_nombre']) && empty($datos['regional_nombre'])): ?>
                <p style="color:var(--color-texto-secundario);font-size:13px;text-align:center;padding:20px 0;">
                    <i class="fas fa-info-circle"></i> Sin centro asignado aún.
                </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Desempeño académico -->
        <div class="perfil-card">
            <div class="perfil-card-header"><i class="fas fa-chart-line"></i> Desempeño Académico</div>
            <div class="perfil-card-body">
                <div class="dato-row">
                    <div class="dato-icon"><i class="fas fa-star"></i></div>
                    <div>
                        <div class="dato-label">Promedio General</div>
                        <div class="dato-val"><?= number_format($datos['PROMEDIO_GENERAL']??0, 2) ?>/5.0</div>
                    </div>
                </div>
                <div class="dato-row">
                    <div class="dato-icon"><i class="fas fa-calendar-check"></i></div>
                    <div>
                        <div class="dato-label">Asistencia</div>
                        <div class="dato-val"><?= ($datos['PORCENTAJE_ASISTENCIA']??0) ?>%</div>
                    </div>
                </div>
                <div class="dato-row">
                    <div class="dato-icon"><i class="fas fa-check-circle"></i></div>
                    <div>
                        <div class="dato-label">Evidencias Aprobadas</div>
                        <div class="dato-val"><?= ($datos['COMPETENCIAS_APROBADAS']??0) ?></div>
                    </div>
                </div>
                <div class="dato-row">
                    <div class="dato-icon"><i class="fas fa-hourglass-half"></i></div>
                    <div>
                        <div class="dato-label">Evidencias Pendientes</div>
                        <div class="dato-val"><?= ($datos['COMPETENCIAS_PENDIENTES']??0) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Accesos rápidos -->
    <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:4px;">
        <a href="<?= htmlspecialchars(app_url('mod/aprendiz_horario.php')) ?>" class="btn-action"><i class="fas fa-calendar-week"></i> Mi horario</a>
        <a href="<?= htmlspecialchars(app_url('mod/aprendiz_compañeros.php')) ?>" class="btn-action"><i class="fas fa-users"></i> Mis compañeros</a>
        <a href="<?= htmlspecialchars(app_url('mod/aprendiz_faltas.php')) ?>" class="btn-cancel"><i class="fas fa-calendar-times"></i> Mis faltas</a>
    </div>

    <!-- ════ INSTRUCTOR ════════════════════════════════════════════ -->
    <?php elseif (esInstructor() && !empty($datos)): ?>

    <div class="perfil-grid">
        <div class="perfil-card">
            <div class="perfil-card-header"><i class="fas fa-user-tie"></i> Datos del Instructor</div>
            <div class="perfil-card-body">
                <div class="dato-row">
                    <div class="dato-icon"><i class="fas fa-envelope"></i></div>
                    <div><div class="dato-label">Correo</div><div class="dato-val"><?= htmlspecialchars($email) ?></div></div>
                </div>
                <?php if (!empty($datos['ESPECIALIDAD'])): ?>
                <div class="dato-row">
                    <div class="dato-icon"><i class="fas fa-code"></i></div>
                    <div><div class="dato-label">Especialidad</div><div class="dato-val"><?= htmlspecialchars($datos['ESPECIALIDAD']) ?></div></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($datos['centro_nombre'])): ?>
                <div class="dato-row">
                    <div class="dato-icon"><i class="fas fa-building"></i></div>
                    <div><div class="dato-label">Centro</div><div class="dato-val"><?= htmlspecialchars($datos['centro_nombre']) ?></div></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($datos['regional_nombre'])): ?>
                <div class="dato-row">
                    <div class="dato-icon"><i class="fas fa-map-marked-alt"></i></div>
                    <div>
                        <div class="dato-label">Regional</div>
                        <div class="dato-val"><?= htmlspecialchars($datos['regional_nombre']) ?>
                        <?php if (!empty($datos['regional_ciudad'])): ?><span style="color:var(--color-texto-secundario);font-size:12px;"> — <?= htmlspecialchars($datos['regional_ciudad']) ?></span><?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="perfil-card">
            <div class="perfil-card-header"><i class="fas fa-chart-bar"></i> Estadísticas</div>
            <div class="perfil-card-body">
                <div class="mini-stats">
                    <div class="mini-stat"><div class="mini-stat-val"><?= (int)($datos['total_fichas']??0) ?></div><div class="mini-stat-lbl">Fichas</div></div>
                    <div class="mini-stat"><div class="mini-stat-val"><?= (int)($datos['total_aprendices']??0) ?></div><div class="mini-stat-lbl">Aprendices</div></div>
                </div>
            </div>
        </div>
    </div>
    <div style="display:flex;gap:12px;flex-wrap:wrap;">
        <a href="<?= htmlspecialchars(app_url('mod/instructor_dashboard.php')) ?>" class="btn-action"><i class="fas fa-tachometer-alt"></i> Mi Dashboard</a>
        <a href="<?= htmlspecialchars(app_url('mod/aprendices.php' . (esInstructor() ? '?instructor_id=' . (int)($_SESSION['usuario_ref_id'] ?? 0) : ''))) ?>" class="btn-action"><i class="fas fa-users"></i> Mis Aprendices</a>
    </div>

    <!-- ════ ADMIN ════════════════════════════════════════════════ -->
    <?php elseif (esAdmin()): ?>
    <div class="perfil-card" style="max-width:400px;">
        <div class="perfil-card-header"><i class="fas fa-shield-halved"></i> Cuenta de Administrador</div>
        <div class="perfil-card-body">
            <div class="dato-row">
                <div class="dato-icon"><i class="fas fa-envelope"></i></div>
                <div><div class="dato-label">Correo</div><div class="dato-val"><?= htmlspecialchars($email) ?></div></div>
            </div>
            <div class="dato-row">
                <div class="dato-icon"><i class="fas fa-infinity"></i></div>
                <div><div class="dato-label">Acceso</div><div class="dato-val"><span class="chip chip-green">Control total del sistema</span></div></div>
            </div>
        </div>
    </div>
    <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:16px;">
        <a href="<?= htmlspecialchars(app_url('index.php')) ?>" class="btn-action"><i class="fas fa-home"></i> Inicio</a>
    </div>
    <?php endif; ?>

</main>

<?php include '../config/footer.php'; ?>
<script src="<?= htmlspecialchars(asset_url('js/tema.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/loader.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/panel_menu.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/dropdowns.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/profile_menu.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/sweetalerts.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/menu.js')) ?>"></script>
</body>
</html>