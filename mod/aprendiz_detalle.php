<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../conexion/AprendizDAO.php';
require_once __DIR__ . '/../conexion/ObservacionDAO.php';
require_once __DIR__ . '/../conexion/AsistenciaDAO.php';

$dao = new AprendizDAO();
$observacionDAO = new ObservacionDAO();
$asistenciaDAO = new AsistenciaDAO();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    redirect_to('mod/aprendices.php');
}

$aprendiz = $dao->obtenerPorId($id);
if (!$aprendiz) {
    redirect_to('mod/aprendices.php');
}

$tiposObservacion = $observacionDAO->obtenerTipos();
$nivelesRiesgo = $observacionDAO->obtenerNivelesRiesgo();

$edad = '';
if (!empty($aprendiz['FECHA_NACIMIENTO'])) {
    $cumpleanos = new DateTime($aprendiz['FECHA_NACIMIENTO']);
    $hoy = new DateTime();
    $edad = $hoy->diff($cumpleanos)->y;
}

$riesgoTexto = $aprendiz['NIVEL_RIESGO_GLOBAL'] ?? 'Bajo';
$riesgoClass = '';
switch($riesgoTexto) {
    case 'Bajo':  $riesgoClass = 'riesgo-bajo'; break;
    case 'Medio': $riesgoClass = 'riesgo-medio'; break;
    case 'Alto':  $riesgoClass = 'riesgo-alto'; break;
}

$iniciales = strtoupper(substr($aprendiz['NOMBRES'], 0, 1) . substr($aprendiz['APELLIDOS'], 0, 1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($aprendiz['NOMBRES'] . ' ' . $aprendiz['APELLIDOS']) ?> - Aprendiz</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('mod/css/aprendiz_detalle.css')) ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('css/style.css')) ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
            animation: fadeIn 0.3s ease;
        }
        .modal-content {
            background: var(--color-blanco);
            margin: 50px auto;
            padding: 30px;
            border-radius: var(--border-radius-card);
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            border: 1px solid var(--color-verde-1);
            border-top: 4px solid var(--color-verde-1);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--border-color);
        }
        .modal-header h3 {
            font-size: 22px;
            color: var(--color-verde-1);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .close {
            font-size: 28px;
            font-weight: 700;
            color: var(--color-texto-secundario);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .close:hover {
            color: var(--color-rojo-1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--color-texto);
        }
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid var(--border-color);
            border-radius: var(--border-radius-input);
            font-size: 14px;
            background: var(--color-blanco);
            color: var(--color-texto);
        }
        .form-control:focus {
            border-color: var(--color-verde-1);
            outline: none;
        }
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .dark-mode .modal-content {
            background: var(--color-gris-cuerpo);
        }
        .estado-badge.presente {
            background: rgba(57,169,0,0.1);
            color: var(--color-verde-1);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .estado-badge.ausente {
            background: rgba(220,38,38,0.1);
            color: #dc2626;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .estado-badge.retardo {
            background: rgba(245,158,11,0.1);
            color: #f59e0b;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .estado-badge.falta {
            background: rgba(220,38,38,0.1);
            color: #dc2626;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .estado-badge.asistio {
            background: rgba(57,169,0,0.1);
            color: var(--color-verde-1);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .stats-grid-asistencia {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .horas-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .horas-card {
            background: var(--color-blanco);
            border-radius: var(--border-radius-card);
            padding: 20px;
            box-shadow: var(--shadow-card);
            border: 1px solid var(--border-color);
            text-align: center;
        }
        .horas-card .valor {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .horas-card .etiqueta {
            font-size: 14px;
            color: var(--color-texto-secundario);
        }
        .dark-mode .horas-card {
            background: var(--color-gris-fondo);
        }
    </style>
</head>
<body>
    <div id="loader">
        <img src="<?= htmlspecialchars(asset_url('img/logo_sena_verde.png')) ?>" alt="Logo SENA" id="loader-logo">
    </div>

    <?php include __DIR__ . '/../config/header.php'; ?>

    <main class="container" id="contenido-principal" style="display:none; opacity:0;">
        <div class="table-controls" style="justify-content: flex-start; margin-bottom: 20px;">
            <a href="<?= htmlspecialchars(app_url('mod/aprendices.php')) ?>" class="btn-view-all">
                <i class="fas fa-arrow-left"></i> Volver a aprendices
            </a>
        </div>

        <div class="content-card" style="background: linear-gradient(135deg, #39a900, #2d8a00); margin-bottom: 30px; color: white;">
            <div class="card-body" style="display: flex; align-items: center; gap: 30px; flex-wrap: wrap;">
                <div style="flex: 1;">
                    <h1 style="margin-bottom: 5px;"><?= htmlspecialchars($aprendiz['NOMBRES'] . ' ' . $aprendiz['APELLIDOS']) ?></h1>
                    <p style="margin-bottom: 15px;">
                        <i class="fas fa-id-card"></i> <?= htmlspecialchars($aprendiz['TIPO_DOCUMENTO'] . ' ' . $aprendiz['NUMERO_DOCUMENTO']) ?>
                    </p>
                    <div class="instructor-badges" style="margin-bottom: 10px;">
                        <span class="badge"><i class="fas fa-envelope"></i> <?= htmlspecialchars($aprendiz['EMAIL']) ?></span>
                        <span class="badge"><i class="fas fa-phone"></i> <?= htmlspecialchars($aprendiz['TELEFONO'] ?? 'N/A') ?></span>
                        <span class="badge"><i class="fas fa-calendar"></i> <?= $edad ?> años</span>
                        <span class="badge"><i class="fas fa-venus-mars"></i> <?= htmlspecialchars($aprendiz['GENERO'] ?? 'N/A') ?></span>
                    </div>
                    <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                        <span><i class="fas fa-calendar-check"></i> Registrado: <?= date('d/m/Y', strtotime($aprendiz['FECHA_REGISTRO'])) ?></span>
                        <span class="status-<?= strtolower($aprendiz['ESTADO_ACADEMICO']) ?>">
                            <i class="fas fa-circle"></i> <?= htmlspecialchars($aprendiz['ESTADO_ACADEMICO']) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-star"></i></div>
                <div class="stat-content">
                    <?php 
                    $promedioEvidencias = $dao->obtenerPromedioEvidencias($id);
                    ?>
                    <span class="stat-value"><?= number_format($promedioEvidencias, 1) ?></span>
                    <span class="stat-label">Promedio</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                <div class="stat-content">
                    <span class="stat-value"><?= number_format($aprendiz['PORCENTAJE_ASISTENCIA'] ?? 0, 1) ?>%</span>
                    <span class="stat-label">Asistencia</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-content">
                    <?php 
                    $aprobadas = $dao->obtenerEvidenciasAprobadas($id);
                    ?>
                    <span class="stat-value"><?= $aprobadas ?></span>
                    <span class="stat-label">Aprobadas</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-content">
                    <?php 
                    $pendientes = $dao->obtenerEvidenciasPendientes($id);
                    ?>
                    <span class="stat-value"><?= $pendientes ?></span>
                    <span class="stat-label">Pendientes</span>
                </div>
            </div>
        </div>

        <?php if (!empty($aprendiz['FICHA_ID'])): ?>
        <div class="cards-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); margin: 30px 0;">
            <div class="card" onclick="window.location.href='<?= htmlspecialchars(app_url('mod/ficha_detalle.php?id=' . (int)$aprendiz['FICHA_ID'])) ?>'">
                <a href="javascript:void(0)" style="text-decoration: none; color: inherit;">
                    <div class="card-icon"><i class="fas fa-layer-group"></i></div>
                    <h3 class="card-title"><?= htmlspecialchars($aprendiz['CODIGO_FICHA']) ?></h3>
                    <p class="card-description">Ver detalles de la ficha</p>
                </a>
            </div>
            <?php if (!empty($aprendiz['PROGRAMA_ID'])): ?>
            <div class="card" onclick="window.location.href='<?= htmlspecialchars(app_url('mod/programa_detalle.php?id=' . (int)$aprendiz['PROGRAMA_ID'])) ?>'">
                <a href="javascript:void(0)" style="text-decoration: none; color: inherit;">
                    <div class="card-icon"><i class="fas fa-book"></i></div>
                    <h3 class="card-title"><?= htmlspecialchars($aprendiz['programa_nombre']) ?></h3>
                    <p class="card-description"><?= htmlspecialchars($aprendiz['NIVEL_FORMACION'] ?? '') ?></p>
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="content-card <?= $riesgoClass ?>" style="padding: 20px; margin-bottom: 30px; display: flex; gap: 20px; align-items: center;">
            <div style="font-size: 40px;">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div>
                <h3 style="margin: 0;">Nivel de Riesgo: <?= $riesgoTexto ?></h3>
                <p style="margin: 5px 0 0;">
                    <?php
                    if ($riesgoTexto == 'Bajo') echo 'El aprendiz presenta un desempeño satisfactorio.';
                    elseif ($riesgoTexto == 'Medio') echo 'Se recomienda realizar seguimiento al aprendiz.';
                    else echo '¡Atención! El aprendiz requiere intervención inmediata.';
                    ?>
                </p>
            </div>
        </div>

        <!-- SECCIÓN DE ASISTENCIA CORREGIDA -->
        <div class="section-header">
            <h2><i class="fas fa-calendar-alt"></i> Historial de Asistencia</h2>
        </div>

        <?php
        // Obtener datos de asistencia
        $asistencias = $asistenciaDAO->obtenerAsistenciaAprendiz($id, 20);
        $resumenAsistencia = $asistenciaDAO->obtenerResumenAprendiz($id);
        
        $totalDias = $resumenAsistencia['total_dias'] ?? 0;
        $diasAsistidos = $resumenAsistencia['dias_asistidos'] ?? 0;
        $diasRetardo = $resumenAsistencia['dias_retardo'] ?? 0;
        $diasFalta = $resumenAsistencia['dias_falta'] ?? 0;
        $totalHorasInasistencia = $resumenAsistencia['total_horas_falta'] ?? 0;
        $horasRetardo = $resumenAsistencia['horas_retardo'] ?? 0;
        
        $porcentajeAsistencia = $totalDias > 0 ? round((($diasAsistidos + $diasRetardo) / $totalDias) * 100, 1) : 100;
        ?>

        <!-- Tarjetas de resumen -->
        <div class="stats-grid-asistencia">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-calendar-check" style="color: var(--color-verde-1);"></i></div>
                <div class="stat-content">
                    <span class="stat-value"><?= $porcentajeAsistencia ?>%</span>
                    <span class="stat-label">Asistencia</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-check-circle" style="color: var(--color-verde-1);"></i></div>
                <div class="stat-content">
                    <span class="stat-value"><?= $diasAsistidos ?></span>
                    <span class="stat-label">Presente</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clock" style="color: #f59e0b;"></i></div>
                <div class="stat-content">
                    <span class="stat-value"><?= $diasRetardo ?></span>
                    <span class="stat-label">Retardos</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-times-circle" style="color: #dc2626;"></i></div>
                <div class="stat-content">
                    <span class="stat-value"><?= $diasFalta ?></span>
                    <span class="stat-label">Faltas</span>
                </div>
            </div>
        </div>

        <!-- Resumen de horas -->
        <div class="horas-grid">
            <div class="horas-card">
                <div class="valor" style="color: var(--color-verde-1);"><?= $totalHorasInasistencia ?>h</div>
                <div class="etiqueta">Total horas inasistencia</div>
            </div>
            <div class="horas-card">
                <div class="valor" style="color: #f59e0b;"><?= $horasRetardo ?>h</div>
                <div class="etiqueta">Horas por retardos</div>
            </div>
            <div class="horas-card">
                <div class="valor" style="color: #dc2626;"><?= $diasFalta * 4 ?>h</div>
                <div class="etiqueta">Horas por faltas</div>
            </div>
            <div class="horas-card">
                <div class="valor" style="color: var(--color-verde-1);"><?= $totalDias ?></div>
                <div class="etiqueta">Total días</div>
            </div>
        </div>

        <!-- Listado de asistencias -->
        <?php if (empty($asistencias)): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <p>No hay registros de asistencia.</p>
            </div>
        <?php else: ?>
            <div class="cards-grid" style="grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));">
                <?php foreach ($asistencias as $asis): ?>
                <div class="clase-card" style="flex-direction: column; align-items: flex-start; gap: 10px; padding: 15px;">
                    <div style="display: flex; justify-content: space-between; width: 100%; align-items: center;">
                        <span style="font-weight: 600;">
                            <i class="fas fa-calendar-day"></i> <?= date('d/m/Y', strtotime($asis['FECHA'])) ?>
                        </span>
                        <span class="estado-badge <?= strtolower($asis['ESTADO']) ?>">
                            <?php 
                            $estadoTexto = $asis['ESTADO'];
                            if ($asis['ESTADO'] == 'asistio') $estadoTexto = 'Presente';
                            elseif ($asis['ESTADO'] == 'retardo') $estadoTexto = 'Retardo';
                            elseif ($asis['ESTADO'] == 'falta') $estadoTexto = 'Falta';
                            echo $estadoTexto;
                            ?>
                        </span>
                    </div>
                    <?php if ($asis['ESTADO'] == 'retardo' && $asis['HORAS_FALTA'] > 0): ?>
                        <div style="font-size: 13px; color: #f59e0b; margin-top: 5px;">
                            <i class="fas fa-clock"></i> <?= $asis['HORAS_FALTA'] ?> hora(s) de retardo
                        </div>
                    <?php elseif ($asis['ESTADO'] == 'falta'): ?>
                        <div style="font-size: 13px; color: #dc2626; margin-top: 5px;">
                            <i class="fas fa-times-circle"></i> Falta completa (4 horas)
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- SECCIÓN DE OBSERVACIONES -->
        <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-top: 40px;">
            <h2><i class="fas fa-clipboard-list"></i> Seguimiento Académico</h2>
            <?php if (esInstructor() || esAdmin()): ?>
            <button class="btn-create" onclick="abrirModalObservacion()">
                <i class="fas fa-plus"></i> Nueva Observación
            </button>
            <?php endif; ?>
        </div>

        <div id="modalObservacion" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3><i class="fas fa-clipboard-list"></i> Nueva Observación</h3>
                    <span class="close" onclick="cerrarModalObservacion()">&times;</span>
                </div>
                <form id="formObservacion">
                    <input type="hidden" name="estudiante_id" value="<?= $aprendiz['APRENDIZ_ID'] ?>">
                    <input type="hidden" name="instructor_id" value="<?= (int)($_SESSION['usuario_ref_id'] ?? 0) ?>">
                    
                    <div class="form-group">
                        <label for="tipo">Tipo de Observación *</label>
                        <select name="tipo" id="tipo" class="form-control" required>
                            <option value="">Seleccione un tipo</option>
                            <?php foreach($tiposObservacion as $tipo): ?>
                                <option value="<?= $tipo ?>"><?= $tipo ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="nivel_riesgo">Nivel de Riesgo *</label>
                        <select name="nivel_riesgo" id="nivel_riesgo" class="form-control" required>
                            <option value="">Seleccione nivel</option>
                            <?php foreach($nivelesRiesgo as $nivel): ?>
                                <option value="<?= $nivel ?>"><?= $nivel ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion">Descripción *</label>
                        <textarea name="descripcion" id="descripcion" class="form-control" rows="4" required placeholder="Describa la observación..."></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn-cancel" onclick="cerrarModalObservacion()">Cancelar</button>
                        <button type="submit" class="btn-action">Guardar Observación</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="cards-grid" style="grid-template-columns: 1fr;">
            <?php
            $observaciones = $dao->obtenerObservaciones($id);
            if (empty($observaciones)): ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard"></i>
                    <p>No hay observaciones registradas.</p>
                </div>
            <?php else: ?>
                <?php foreach ($observaciones as $obs): ?>
                <div class="content-card">
                    <div class="card-header" style="padding: 15px 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; gap: 12px; flex-wrap: wrap;">
                            <span style="font-weight: 600;"><?= htmlspecialchars($obs['TIPO'] ?? 'General') ?></span>
                            <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                                <?php if (($obs['ORIGEN'] ?? 'observacion') === 'alerta'): ?>
                                    <span class="badge">Alerta</span>
                                <?php endif; ?>
                                <span class="badge"><?= date('d/m/Y H:i', strtotime($obs['FECHA'])) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body" style="padding: 20px;">
                        <p><?= nl2br(htmlspecialchars($obs['DESCRIPCION'])) ?></p>
                        <div style="display: flex; justify-content: space-between; margin-top: 15px;">
                            <span><i class="fas fa-user-tie"></i> <?= htmlspecialchars(trim(($obs['instructor_nombres'] ?? 'Instructor') . ' ' . ($obs['instructor_apellidos'] ?? ''))) ?></span>
                            <span class="<?= strtolower($obs['NIVEL_RIESGO'] ?? 'bajo') == 'alto' ? 'riesgo-alto' : (strtolower($obs['NIVEL_RIESGO'] ?? 'bajo') == 'medio' ? 'riesgo-medio' : 'riesgo-bajo') ?>">
                                <?= htmlspecialchars($obs['NIVEL_RIESGO'] ?? 'Bajo') ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if (esAdmin()): ?>
        <div style="display: flex; gap: 15px; justify-content: center; margin-top: 30px;">
            <a href="<?= htmlspecialchars(app_url('mod/crud/editar_aprendiz.php?id=' . (int)$aprendiz['APRENDIZ_ID'])) ?>" class="btn-create">
                <i class="fas fa-edit"></i> Editar aprendiz
            </a>
            <a href="#" class="btn-cancel" onclick="confirmarEliminacion(<?= $aprendiz['APRENDIZ_ID'] ?>)">
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
        function abrirModalObservacion() {
            document.getElementById('modalObservacion').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        function cerrarModalObservacion() {
            document.getElementById('modalObservacion').style.display = 'none';
            document.body.style.overflow = '';
            document.getElementById('formObservacion').reset();
            
            const submitBtn = document.querySelector('#formObservacion button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = 'Guardar Observación';
                submitBtn.disabled = false;
            }
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('modalObservacion');
            if (event.target == modal) {
                cerrarModalObservacion();
            }
        }
        
        document.getElementById('formObservacion').addEventListener('submit', function(e) {
            e.preventDefault();
            
            let formData = new FormData(this);
            let submitBtn = this.querySelector('button[type="submit"]');
            let originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            submitBtn.disabled = true;
            
            fetch('observacion.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    cerrarModalObservacion();
                    
                    Swal.fire({
                        icon: 'success',
                        title: '¡Observación guardada!',
                        text: 'La observación se ha registrado correctamente',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                    
                    if (typeof actualizarBadgeNotificaciones === 'function') {
                        actualizarBadgeNotificaciones();
                    }
                } else {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al guardar la observación'
                });
            });
        });

        function confirmarEliminacion(id) {
            Swal.fire({
                title: '¿Eliminar aprendiz?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'crud/eliminar_aprendiz.php?id=' + id;
                }
            });
        }
        
        if (typeof initThemeToggle === 'function') {
            setTimeout(initThemeToggle, 100);
        }
    </script>
</body>
</html>
