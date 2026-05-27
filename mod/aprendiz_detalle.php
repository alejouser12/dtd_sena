<?php
// mod/aprendiz_detalle.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../conexion/AprendizDAO.php';
require_once __DIR__ . '/../conexion/ObservacionDAO.php';
require_once __DIR__ . '/../conexion/AsistenciaDAO.php';

$dao           = new AprendizDAO();
$observacionDAO = new ObservacionDAO();
$asistenciaDAO  = new AsistenciaDAO();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) redirect_to('mod/aprendices.php');

$aprendiz = $dao->obtenerPorId($id);
if (!$aprendiz) redirect_to('mod/aprendices.php');

$tiposObservacion = $observacionDAO->obtenerTipos();
$nivelesRiesgo    = $observacionDAO->obtenerNivelesRiesgo();

$edad = '';
if (!empty($aprendiz['FECHA_NACIMIENTO'])) {
    $edad = (new DateTime())->diff(new DateTime($aprendiz['FECHA_NACIMIENTO']))->y;
}

$riesgoTexto = $aprendiz['NIVEL_RIESGO_GLOBAL'] ?? 'Bajo';
$riesgoClass = match($riesgoTexto) {
    'Alto'  => 'riesgo-alto',
    'Medio' => 'riesgo-medio',
    default => 'riesgo-bajo',
};

$iniciales = strtoupper(substr($aprendiz['NOMBRES'],0,1).substr($aprendiz['APELLIDOS'],0,1));

// ── Datos de asistencia ──────────────────────────────────────────────────
$asistencias      = $asistenciaDAO->obtenerAsistenciaAprendiz($id, 40);
$resumen          = $asistenciaDAO->obtenerResumenAprendiz($id);
$pct              = $asistenciaDAO->obtenerPorcentajeAsistencia($id);

$totalDias        = (int)($resumen['total_dias']        ?? 0);
$diasAsistidos    = (int)($resumen['dias_asistidos']    ?? 0);
$diasRetardo      = (int)($resumen['dias_retardo']      ?? 0);
$diasFalta        = (int)($resumen['dias_falta']        ?? 0);
$diasExcusa       = (int)($resumen['dias_excusa']       ?? 0);
$horasRetardo     = (int)($resumen['horas_retardo']     ?? 0);
$totalHoras       = (int)($resumen['total_horas_falta'] ?? 0);
$horasFaltaPura   = $diasFalta * 4;

// Color barra porcentaje
$pctColor = $pct >= 80 ? '#16a34a' : ($pct >= 60 ? '#f59e0b' : '#dc2626');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($aprendiz['NOMBRES'].' '.$aprendiz['APELLIDOS']) ?> — Aprendiz</title>
<link rel="stylesheet" href="<?= htmlspecialchars(asset_url('mod/css/aprendiz_detalle.css')) ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(asset_url('css/style.css')) ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
/* ═══ MODAL ══════════════════════════════════════════════════════════ */
.modal { display:none;position:fixed;z-index:9999;inset:0;background:rgba(0,0,0,.5);backdrop-filter:blur(5px);animation:fdIn .3s ease; }
.modal-content { background:var(--color-blanco);margin:50px auto;padding:30px;border-radius:var(--border-radius-card);max-width:500px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.3);border:1px solid var(--color-verde-1);border-top:4px solid var(--color-verde-1); }
.modal-header { display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;padding-bottom:10px;border-bottom:2px solid var(--border-color); }
.modal-header h3 { font-size:20px;color:var(--color-verde-1);display:flex;align-items:center;gap:10px; }
.close { font-size:26px;font-weight:700;color:var(--color-texto-secundario);cursor:pointer;transition:.3s; }
.close:hover { color:#dc2626; }
.form-group { margin-bottom:18px; }
.form-group label { display:block;margin-bottom:7px;font-weight:600;color:var(--color-texto); }
.form-control { width:100%;padding:10px 14px;border:2px solid var(--border-color);border-radius:var(--border-radius-input);font-size:14px;background:var(--color-blanco);color:var(--color-texto); }
.form-control:focus { border-color:var(--color-verde-1);outline:none; }
textarea.form-control { min-height:100px;resize:vertical; }
.form-actions { display:flex;gap:10px;justify-content:flex-end;margin-top:18px; }
@keyframes fdIn { from{opacity:0} to{opacity:1} }
.dark-mode .modal-content { background:var(--color-gris-cuerpo); }

/* ═══ TARJETAS STATS ASISTENCIA ══════════════════════════════════════ */
.asis-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
    gap: 14px;
    margin-bottom: 20px;
}
.asc {
    background: var(--color-blanco);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius-card);
    padding: 16px;
    text-align: center;
    box-shadow: var(--shadow-card);
    position: relative; overflow: hidden;
}
.asc::before {
    content:''; position:absolute; top:0;left:0;right:0;height:3px;
    background: var(--ac-color, var(--color-verde-1));
}
.asc .av { font-size: 28px; font-weight: 800; color: var(--ac-color, var(--color-verde-1)); line-height: 1; }
.asc .al { font-size: 11px; color: var(--color-texto-secundario); margin-top: 4px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
.dark-mode .asc { background: var(--color-gris-fondo); }

/* ═══ BARRA DE PORCENTAJE ════════════════════════════════════════════ */
.pct-wrap {
    background: var(--color-blanco);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius-card);
    padding: 20px 24px;
    margin-bottom: 20px;
    box-shadow: var(--shadow-card);
}
.pct-header { display:flex;justify-content:space-between;align-items:center;margin-bottom:10px; }
.pct-label  { font-size:14px;font-weight:700;display:flex;align-items:center;gap:7px; }
.pct-value  { font-size:22px;font-weight:800; }
.pct-bar-bg {
    height: 12px; background: var(--color-gris-fondo);
    border-radius: 20px; overflow: hidden;
}
.pct-bar-fill {
    height: 100%; border-radius: 20px;
    background: var(--pct-color, #16a34a);
    transition: width .8s cubic-bezier(.4,0,.2,1);
}
.pct-legend { display:flex;gap:16px;flex-wrap:wrap;margin-top:10px;font-size:12px;color:var(--color-texto-secundario); }
.pct-legend span { display:flex;align-items:center;gap:5px; }
.pct-dot { width:8px;height:8px;border-radius:50%; }
.dark-mode .pct-wrap { background:var(--color-gris-fondo); }

/* ═══ HISTORIAL TABLA ════════════════════════════════════════════════ */
.his-wrap {
    background: var(--color-blanco);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius-card);
    overflow: hidden;
    box-shadow: var(--shadow-card);
    margin-bottom: 30px;
}
.his-table { width:100%;border-collapse:collapse;font-size:13px; }
.his-table th {
    background: var(--color-gris-fondo);
    padding: 11px 14px; text-align:left;
    font-size:12px;font-weight:700;
    color:var(--color-texto-secundario);
    border-bottom:2px solid var(--border-color);
    text-transform:uppercase;letter-spacing:.5px;
}
.his-table td {
    padding: 10px 14px;
    border-bottom: 1px solid var(--border-color);
    vertical-align: middle;
}
.his-table tbody tr:last-child td { border-bottom:none; }
.his-table tbody tr:hover td { background:rgba(57,169,0,.03); }

/* Badges estado */
.badge-a { background:#dcfce7;color:#166534; padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700; }
.badge-r { background:#fef3c7;color:#92400e; padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700; }
.badge-f { background:#fee2e2;color:#991b1b; padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700; }
.badge-e { background:#ede9fe;color:#6d28d9; padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700; }

.his-horas { font-weight:700;font-size:13px; }
.his-limite { font-size:11px;color:var(--color-texto-secundario);margin-top:2px; }
.his-venc   { font-size:11px;color:#dc2626;font-weight:700; }

.dark-mode .his-wrap { background:var(--color-gris-cuerpo); }

/* ═══ SECTION HEADER ════════════════════════════════════════════════ */
.sec-hdr {
    display:flex;justify-content:space-between;align-items:center;
    margin: 30px 0 16px; flex-wrap:wrap; gap:10px;
}
.sec-hdr h2 { font-size:18px;font-weight:800;display:flex;align-items:center;gap:8px;color:var(--color-texto); }

/* estado badges aprendiz_detalle */
.estado-badge.asistio { background:rgba(57,169,0,.1);color:var(--color-verde-1); padding:4px 10px;border-radius:20px;font-size:12px;font-weight:600; }
.estado-badge.retardo { background:rgba(245,158,11,.1);color:#f59e0b; padding:4px 10px;border-radius:20px;font-size:12px;font-weight:600; }
.estado-badge.falta   { background:rgba(220,38,38,.1);color:#dc2626; padding:4px 10px;border-radius:20px;font-size:12px;font-weight:600; }
.estado-badge.excusa  { background:rgba(109,40,217,.1);color:#6d28d9; padding:4px 10px;border-radius:20px;font-size:12px;font-weight:600; }
</style>
</head>
<body>
<div id="loader">
    <img src="<?= htmlspecialchars(asset_url('img/logo_sena_verde.png')) ?>" alt="" id="loader-logo">
</div>

<?php include __DIR__ . '/../config/header.php'; ?>

<main class="container" id="contenido-principal" style="display:none;opacity:0;">

    <!-- Volver -->
    <div class="table-controls" style="justify-content:flex-start;margin-bottom:20px;">
        <a href="<?= htmlspecialchars(app_url('mod/aprendices.php')) ?>" class="btn-view-all">
            <i class="fas fa-arrow-left"></i> Volver a aprendices
        </a>
    </div>

    <!-- Hero aprendiz -->
    <div class="content-card" style="background:linear-gradient(135deg,#39a900,#2d8a00);margin-bottom:28px;color:#fff;">
        <div class="card-body" style="display:flex;align-items:center;gap:30px;flex-wrap:wrap;">
            <div style="flex:1;">
                <h1 style="margin-bottom:5px;"><?= htmlspecialchars($aprendiz['NOMBRES'].' '.$aprendiz['APELLIDOS']) ?></h1>
                <p style="margin-bottom:14px;">
                    <i class="fas fa-id-card"></i>
                    <?= htmlspecialchars($aprendiz['TIPO_DOCUMENTO'].' '.$aprendiz['NUMERO_DOCUMENTO']) ?>
                </p>
                <div class="instructor-badges" style="margin-bottom:10px;">
                    <span class="badge"><i class="fas fa-envelope"></i> <?= htmlspecialchars($aprendiz['EMAIL']) ?></span>
                    <span class="badge"><i class="fas fa-phone"></i> <?= htmlspecialchars($aprendiz['TELEFONO'] ?? 'N/A') ?></span>
                    <span class="badge"><i class="fas fa-calendar"></i> <?= $edad ?> años</span>
                    <span class="badge"><i class="fas fa-venus-mars"></i> <?= htmlspecialchars($aprendiz['GENERO'] ?? 'N/A') ?></span>
                </div>
                <div style="display:flex;gap:18px;flex-wrap:wrap;font-size:13px;">
                    <span><i class="fas fa-calendar-check"></i> Registrado: <?= date('d/m/Y', strtotime($aprendiz['FECHA_REGISTRO'])) ?></span>
                    <span class="status-<?= strtolower($aprendiz['ESTADO_ACADEMICO']) ?>">
                        <i class="fas fa-circle"></i> <?= htmlspecialchars($aprendiz['ESTADO_ACADEMICO']) ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats generales -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-star"></i></div>
            <div class="stat-content">
                <span class="stat-value"><?= number_format($dao->obtenerPromedioEvidencias($id), 1) ?></span>
                <span class="stat-label">Promedio</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
            <div class="stat-content">
                <span class="stat-value"><?= $pct ?>%</span>
                <span class="stat-label">Asistencia</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-content">
                <span class="stat-value"><?= $dao->obtenerEvidenciasAprobadas($id) ?></span>
                <span class="stat-label">Aprobadas</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-content">
                <span class="stat-value"><?= $dao->obtenerEvidenciasPendientes($id) ?></span>
                <span class="stat-label">Pendientes</span>
            </div>
        </div>
    </div>

    <!-- Fichas / Programa -->
    <?php if (!empty($aprendiz['FICHA_ID'])): ?>
    <div class="cards-grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));margin:24px 0;">
        <div class="card" onclick="location.href='<?= htmlspecialchars(app_url('mod/ficha_detalle.php?id='.(int)$aprendiz['FICHA_ID'])) ?>'">
            <div class="card-icon"><i class="fas fa-layer-group"></i></div>
            <h3 class="card-title"><?= htmlspecialchars($aprendiz['CODIGO_FICHA']) ?></h3>
            <p class="card-description">Ver detalles de la ficha</p>
        </div>
        <?php if (!empty($aprendiz['PROGRAMA_ID'])): ?>
        <div class="card" onclick="location.href='<?= htmlspecialchars(app_url('mod/programa_detalle.php?id='.(int)$aprendiz['PROGRAMA_ID'])) ?>'">
            <div class="card-icon"><i class="fas fa-book"></i></div>
            <h3 class="card-title"><?= htmlspecialchars($aprendiz['programa_nombre']) ?></h3>
            <p class="card-description"><?= htmlspecialchars($aprendiz['NIVEL_FORMACION'] ?? '') ?></p>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Nivel de riesgo -->
    <div class="content-card <?= $riesgoClass ?>" style="padding:18px 22px;margin-bottom:28px;display:flex;gap:18px;align-items:center;">
        <div style="font-size:36px;"><i class="fas fa-exclamation-triangle"></i></div>
        <div>
            <h3 style="margin:0;">Nivel de Riesgo: <?= $riesgoTexto ?></h3>
            <p style="margin:5px 0 0;font-size:13px;">
                <?php
                if ($riesgoTexto==='Bajo')   echo 'El aprendiz presenta un desempeño satisfactorio.';
                elseif ($riesgoTexto==='Medio') echo 'Se recomienda realizar seguimiento al aprendiz.';
                else echo '¡Atención! El aprendiz requiere intervención inmediata.';
                ?>
            </p>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         SECCIÓN ASISTENCIA
    ══════════════════════════════════════════════════════ -->
    <div class="sec-hdr">
        <h2><i class="fas fa-calendar-alt"></i> Asistencia</h2>
        <?php if (!empty($aprendiz['FICHA_ID']) && (esAdmin()||esInstructor())): ?>
        <a href="<?= htmlspecialchars(app_url('mod/asistencias_detalle.php?id='.(int)$aprendiz['FICHA_ID'])) ?>"
           class="btn-action" style="padding:8px 18px;font-size:13px;">
            <i class="fas fa-table"></i> Tomar asistencia
        </a>
        <?php endif; ?>
    </div>

    <!-- Barra de porcentaje -->
    <div class="pct-wrap">
        <div class="pct-header">
            <div class="pct-label" style="color:<?= $pctColor ?>;">
                <i class="fas fa-chart-pie"></i> Porcentaje de asistencia
            </div>
            <div class="pct-value" style="color:<?= $pctColor ?>;"><?= $pct ?>%</div>
        </div>
        <div class="pct-bar-bg">
            <div class="pct-bar-fill" style="width:<?= $pct ?>%;--pct-color:<?= $pctColor ?>;"></div>
        </div>
        <div class="pct-legend">
            <span><div class="pct-dot" style="background:#16a34a;"></div><?= $diasAsistidos ?> días asistidos</span>
            <span><div class="pct-dot" style="background:#f59e0b;"></div><?= $diasRetardo ?> retardos</span>
            <span><div class="pct-dot" style="background:#dc2626;"></div><?= $diasFalta ?> faltas</span>
            <span><div class="pct-dot" style="background:#6d28d9;"></div><?= $diasExcusa ?> excusas</span>
            <span style="margin-left:auto;font-weight:600;">Total: <?= $totalDias ?> días registrados</span>
        </div>
    </div>

    <!-- Tarjetas estadísticas -->
    <div class="asis-stats">
        <div class="asc" style="--ac-color:#16a34a;">
            <div class="av"><?= $diasAsistidos ?></div>
            <div class="al">Días presentes</div>
        </div>
        <div class="asc" style="--ac-color:#f59e0b;">
            <div class="av"><?= $diasRetardo ?></div>
            <div class="al">Retardos</div>
        </div>
        <div class="asc" style="--ac-color:#dc2626;">
            <div class="av"><?= $diasFalta ?></div>
            <div class="al">Faltas</div>
        </div>
        <div class="asc" style="--ac-color:#6d28d9;">
            <div class="av"><?= $diasExcusa ?></div>
            <div class="al">Excusas</div>
        </div>
        <div class="asc" style="--ac-color:#f59e0b;">
            <div class="av"><?= $horasRetardo ?>h</div>
            <div class="al">Hrs retardo</div>
        </div>
        <div class="asc" style="--ac-color:#dc2626;">
            <div class="av"><?= $horasFaltaPura ?>h</div>
            <div class="al">Hrs faltas</div>
        </div>
        <div class="asc" style="--ac-color:#991b1b;">
            <div class="av"><?= $totalHoras ?>h</div>
            <div class="al">Total inasistencia</div>
        </div>
    </div>

    <!-- Historial tabla -->
    <?php if (empty($asistencias)): ?>
        <div class="empty-state" style="margin-bottom:30px;">
            <i class="fas fa-calendar-times"></i>
            <p>No hay registros de asistencia.</p>
        </div>
    <?php else: ?>
    <div class="his-wrap">
        <table class="his-table">
            <thead>
                <tr>
                    <th><i class="fas fa-calendar-day"></i> Fecha</th>
                    <th><i class="fas fa-circle"></i> Estado</th>
                    <th><i class="fas fa-hourglass-half"></i> Horas</th>
                    <th><i class="fas fa-file-alt"></i> Excusa</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($asistencias as $a):
                $limite  = $a['FECHA_LIMITE_EXCUSA'] ?? null;
                $vencida = $limite && date('Y-m-d') > $limite && $a['ESTADO']==='falta';
                $puedExc = $limite && date('Y-m-d') <= $limite && $a['ESTADO']==='falta';
            ?>
            <tr>
                <td style="font-weight:600;"><?= date('d/m/Y', strtotime($a['FECHA'])) ?></td>
                <td>
                    <?php
                    $bc = match($a['ESTADO']) {
                        'asistio'=>'badge-a','retardo'=>'badge-r',
                        'falta'  =>'badge-f','excusa' =>'badge-e', default=>'badge-a'
                    };
                    $bt = match($a['ESTADO']) {
                        'asistio'=>'✓ Presente','retardo'=>'⏱ Retardo',
                        'falta'  =>'✗ Falta',  'excusa' =>'E Excusa', default=>'—'
                    };
                    ?>
                    <span class="<?= $bc ?>"><?= $bt ?></span>
                </td>
                <td>
                    <?php if ($a['ESTADO']==='retardo' && $a['HORAS_FALTA']>0): ?>
                        <span class="his-horas" style="color:#f59e0b;"><?= $a['HORAS_FALTA'] ?>h tardanza</span>
                    <?php elseif ($a['ESTADO']==='falta'): ?>
                        <span class="his-horas" style="color:#dc2626;">4h inasistencia</span>
                    <?php elseif ($a['ESTADO']==='excusa'): ?>
                        <span class="his-horas" style="color:#6d28d9;">0h (excusado)</span>
                    <?php else: ?>
                        <span style="color:#16a34a;">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($a['ESTADO']==='excusa'): ?>
                        <span class="badge-e"><i class="fas fa-check"></i> Presentada</span>
                    <?php elseif ($vencida): ?>
                        <div class="his-venc"><i class="fas fa-lock"></i> Plazo vencido</div>
                    <?php elseif ($puedExc): ?>
                        <div class="his-limite">
                            <i class="fas fa-clock"></i>
                            Hasta <?= date('d/m/Y', strtotime($limite)) ?>
                            <br><span style="color:#f59e0b;font-weight:600;">
                                <?= (new DateTime())->diff(new DateTime($limite))->days ?> día(s) restante(s)
                            </span>
                        </div>
                    <?php elseif ($a['ESTADO']==='asistio'): ?>
                        <span style="color:#16a34a;font-size:11px;">N/A</span>
                    <?php else: ?>
                        <span style="color:var(--color-texto-secundario);font-size:11px;">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- ══════════════════════════════════════════════════════
         SECCIÓN OBSERVACIONES
    ══════════════════════════════════════════════════════ -->
    <div class="sec-hdr">
        <h2><i class="fas fa-clipboard-list"></i> Seguimiento Académico</h2>
        <?php if (esInstructor()||esAdmin()): ?>
        <button class="btn-create" onclick="abrirModal()">
            <i class="fas fa-plus"></i> Nueva Observación
        </button>
        <?php endif; ?>
    </div>

    <!-- Modal observación -->
    <div id="modalObs" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-clipboard-list"></i> Nueva Observación</h3>
                <span class="close" onclick="cerrarModal()">&times;</span>
            </div>
            <form id="formObs">
                <input type="hidden" name="estudiante_id" value="<?= $aprendiz['APRENDIZ_ID'] ?>">
                <input type="hidden" name="instructor_id" value="<?= (int)($_SESSION['usuario_ref_id'] ?? 0) ?>">
                <div class="form-group">
                    <label>Tipo de Observación *</label>
                    <select name="tipo" class="form-control" required>
                        <option value="">Seleccione un tipo</option>
                        <?php foreach($tiposObservacion as $t): ?>
                            <option value="<?= $t ?>"><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Nivel de Riesgo *</label>
                    <select name="nivel_riesgo" class="form-control" required>
                        <option value="">Seleccione nivel</option>
                        <?php foreach($nivelesRiesgo as $n): ?>
                            <option value="<?= $n ?>"><?= $n ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Descripción *</label>
                    <textarea name="descripcion" class="form-control" rows="4" required placeholder="Describa la observación..."></textarea>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="cerrarModal()">Cancelar</button>
                    <button type="submit" class="btn-action">Guardar Observación</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista observaciones -->
    <div class="cards-grid" style="grid-template-columns:1fr;">
        <?php $observaciones = $dao->obtenerObservaciones($id);
        if (empty($observaciones)): ?>
            <div class="empty-state">
                <i class="fas fa-clipboard"></i>
                <p>No hay observaciones registradas.</p>
            </div>
        <?php else: ?>
            <?php foreach ($observaciones as $obs): ?>
            <div class="content-card">
                <div class="card-header" style="padding:14px 20px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;width:100%;gap:12px;flex-wrap:wrap;">
                        <span style="font-weight:600;"><?= htmlspecialchars($obs['TIPO'] ?? 'General') ?></span>
                        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                            <?php if (($obs['ORIGEN'] ?? '') === 'alerta'): ?>
                                <span class="badge">Alerta</span>
                            <?php endif; ?>
                            <span class="badge"><?= date('d/m/Y H:i', strtotime($obs['FECHA'])) ?></span>
                        </div>
                    </div>
                </div>
                <div class="card-body" style="padding:18px 20px;">
                    <p><?= nl2br(htmlspecialchars($obs['DESCRIPCION'])) ?></p>
                    <div style="display:flex;justify-content:space-between;margin-top:14px;font-size:13px;">
                        <span><i class="fas fa-user-tie"></i> <?= htmlspecialchars(trim(($obs['instructor_nombres']??'Instructor').' '.($obs['instructor_apellidos']??''))) ?></span>
                        <span class="<?= strtolower($obs['NIVEL_RIESGO']??'bajo')==='alto'?'riesgo-alto':(strtolower($obs['NIVEL_RIESGO']??'bajo')==='medio'?'riesgo-medio':'riesgo-bajo') ?>">
                            <?= htmlspecialchars($obs['NIVEL_RIESGO'] ?? 'Bajo') ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if (esAdmin()): ?>
    <div style="display:flex;gap:14px;justify-content:center;margin-top:28px;">
        <a href="<?= htmlspecialchars(app_url('mod/crud/editar_aprendiz.php?id='.(int)$aprendiz['APRENDIZ_ID'])) ?>" class="btn-create">
            <i class="fas fa-edit"></i> Editar aprendiz
        </a>
        <a href="#" class="btn-cancel" onclick="confirmarElim(<?= $aprendiz['APRENDIZ_ID'] ?>)">
            <i class="fas fa-trash-alt"></i> Eliminar
        </a>
    </div>
    <?php endif; ?>

</main>

<?php include __DIR__ . '/../config/footer.php'; ?>

<script src="<?= htmlspecialchars(asset_url('js/tema.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/loader.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/panel_menu.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/dropdowns.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/profile_menu.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/sweetalerts.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('js/menu.js')) ?>"></script>
<script>
function abrirModal()  { document.getElementById('modalObs').style.display='block'; document.body.style.overflow='hidden'; }
function cerrarModal() { document.getElementById('modalObs').style.display='none'; document.body.style.overflow=''; document.getElementById('formObs').reset(); }
window.onclick = e => { if (e.target===document.getElementById('modalObs')) cerrarModal(); };

document.getElementById('formObs').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = this.querySelector('[type=submit]');
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    btn.disabled = true;

    fetch('observacion.php', { method:'POST', body: new FormData(this) })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                cerrarModal();
                Swal.fire({ icon:'success', title:'¡Guardado!', timer:1500, showConfirmButton:false })
                    .then(() => location.reload());
            } else {
                btn.innerHTML = orig; btn.disabled = false;
                Swal.fire({ icon:'error', title:'Error', text: d.message });
            }
        })
        .catch(() => { btn.innerHTML = orig; btn.disabled = false; Swal.fire({ icon:'error', title:'Error de red' }); });
});

function confirmarElim(id) {
    Swal.fire({ title:'¿Eliminar aprendiz?', text:'Esta acción no se puede deshacer',
        icon:'warning', showCancelButton:true, confirmButtonText:'Sí, eliminar',
        cancelButtonText:'Cancelar', reverseButtons:true })
    .then(r => { if (r.isConfirmed) location.href='crud/eliminar_aprendiz.php?id='+id; });
}
</script>
</body>
</html>