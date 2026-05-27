<?php
session_start();
require_once __DIR__ . '/../../config/auth.php';
if (!esAprendiz()) {
    header('Location: ../../login.php');
    exit;
}
require_once __DIR__ . '/../../conexion/AprendizDAO.php';
require_once __DIR__ . '/../../conexion/AsistenciaDAO.php';
require_once __DIR__ . '/../../conexion/HorarioDAO.php';

$aprendizDAO = new AprendizDAO();
$asistenciaDAO = new AsistenciaDAO();
$horarioDAO = new HorarioDAO();

// Obtener datos del aprendiz usando su ID de sesión
$aprendiz = $aprendizDAO->obtenerPorId($_SESSION['aprendiz_id']);
if (!$aprendiz) {
    session_destroy();
    header('Location: ../../login.php');
    exit;
}

$resumenAsistencia = $asistenciaDAO->obtenerResumenAprendiz($aprendiz['APRENDIZ_ID']);
$evidencias = $aprendizDAO->obtenerEvidenciasConCalificacion($aprendiz['APRENDIZ_ID']);
$horario = $horarioDAO->obtenerHorarioFicha($aprendiz['FICHA_ID']);

$porcentajeAsistencia = $resumenAsistencia['total_dias'] > 0 
    ? round(($resumenAsistencia['dias_asistidos'] / $resumenAsistencia['total_dias']) * 100, 1) 
    : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Aprendiz SENA</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .perfil-header {
            background: linear-gradient(135deg, var(--color-verde-1), var(--color-verde-2));
            border-radius: 24px;
            padding: 30px;
            margin-bottom: 30px;
            color: white;
            display: flex;
            align-items: center;
            gap: 30px;
            flex-wrap: wrap;
        }
        .avatar-grande {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: 700;
            color: var(--color-verde-1);
        }
        .info-ficha {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            background: var(--color-blanco);
            padding: 20px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-card);
        }
        .tarjeta {
            background: var(--color-blanco);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-card);
        }
        .tabla-horario {
            width: 100%;
            border-collapse: collapse;
        }
        .tabla-horario th, .tabla-horario td {
            border: 1px solid var(--border-color);
            padding: 10px;
            text-align: center;
        }
        .tabla-horario th {
            background: var(--color-gris-fondo);
        }
        .calificacion-aprobado { color: #16a34a; font-weight: bold; }
        .calificacion-desaprobado { color: #dc2626; font-weight: bold; }
        .calificacion-pendiente { color: #f59e0b; }
    </style>
</head>
<body>
    <div id="loader"><img src="../../img/logo_sena_verde.png" alt="Logo SENA" id="loader-logo"></div>
    <?php include '../../config/header.php'; ?>
    <main class="container">
        <div class="perfil-header">
            <div class="avatar-grande">
                <?= strtoupper(substr($aprendiz['NOMBRES'], 0, 1) . substr($aprendiz['APELLIDOS'], 0, 1)) ?>
            </div>
            <div>
                <h1><?= htmlspecialchars($aprendiz['NOMBRES'] . ' ' . $aprendiz['APELLIDOS']) ?></h1>
                <p><i class="fas fa-id-card"></i> <?= htmlspecialchars($aprendiz['TIPO_DOCUMENTO'] . ' ' . $aprendiz['NUMERO_DOCUMENTO']) ?></p>
                <p><i class="fas fa-envelope"></i> <?= htmlspecialchars($_SESSION['email']) ?></p>
                <p><i class="fas fa-phone"></i> <?= htmlspecialchars($aprendiz['TELEFONO'] ?? 'No registrado') ?></p>
            </div>
        </div>

        <div class="info-ficha">
            <div><i class="fas fa-layer-group"></i> <strong>Ficha:</strong> <?= htmlspecialchars($aprendiz['CODIGO_FICHA'] ?? 'N/A') ?></div>
            <div><i class="fas fa-graduation-cap"></i> <strong>Programa:</strong> <?= htmlspecialchars($aprendiz['programa_nombre'] ?? 'N/A') ?></div>
            <div><i class="fas fa-building"></i> <strong>Centro:</strong> <?= htmlspecialchars($aprendiz['centro_nombre'] ?? 'N/A') ?></div>
            <div><i class="fas fa-map-marker-alt"></i> <strong>Regional:</strong> <?= htmlspecialchars($aprendiz['regional_nombre'] ?? 'N/A') ?></div>
        </div>

        <div class="tarjeta">
            <h3><i class="fas fa-chart-line"></i> Resumen de Asistencia</h3>
            <div style="display: flex; gap: 25px; flex-wrap: wrap; margin: 15px 0;">
                <div><strong>Días asistidos:</strong> <?= $resumenAsistencia['dias_asistidos'] ?></div>
                <div><strong>Retardos:</strong> <?= $resumenAsistencia['dias_retardo'] ?> (<?= $resumenAsistencia['horas_retardo'] ?>h)</div>
                <div><strong>Faltas:</strong> <?= $resumenAsistencia['dias_falta'] ?> (<?= $resumenAsistencia['total_horas_falta'] ?>h)</div>
                <div><strong>Excusas:</strong> <?= $resumenAsistencia['dias_excusa'] ?? 0 ?></div>
                <div><strong>Porcentaje asistencia:</strong> <span style="color: var(--color-verde-1);"><?= $porcentajeAsistencia ?>%</span></div>
            </div>
            <div style="background: #e0e0e0; border-radius: 10px; overflow: hidden;">
                <div style="width: <?= $porcentajeAsistencia ?>%; background: var(--color-verde-1); height: 8px;"></div>
            </div>
        </div>

        <?php if (!empty($horario)): ?>
        <div class="tarjeta">
            <h3><i class="fas fa-calendar-week"></i> Horario de Clases</h3>
            <div style="overflow-x: auto;">
                <table class="tabla-horario">
                    <thead>
                        <tr>
                            <th>Día</th>
                            <th>Hora inicio</th>
                            <th>Hora fin</th>
                            <th>Materia</th>
                            <th>Aula</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($horario as $h): ?>
                        <tr>
                            <td><?= $h['dia_nombre'] ?></td>
                            <td><?= substr($h['HORA_INICIO'], 0, 5) ?></td>
                            <td><?= substr($h['HORA_FIN'], 0, 5) ?></td>
                            <td><?= htmlspecialchars($h['MATERIA']) ?></td>
                            <td><?= htmlspecialchars($h['AULA'] ?? 'N/A') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <small><i class="fas fa-info-circle"></i> Horario correspondiente al trimestre actual</small>
        </div>
        <?php else: ?>
        <div class="tarjeta">
            <h3><i class="fas fa-calendar-week"></i> Horario de Clases</h3>
            <p>No hay horario cargado aún. Consulta con tu instructor.</p>
        </div>
        <?php endif; ?>

        <div class="tarjeta">
            <h3><i class="fas fa-file-alt"></i> Mis Evidencias</h3>
            <?php if (empty($evidencias)): ?>
                <p>No hay evidencias asignadas aún.</p>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Evidencia</th>
                                <th>Tipo</th>
                                <th>Porcentaje</th>
                                <th>Fecha asignación</th>
                                <th>Fecha entrega</th>
                                <th>Calificación</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($evidencias as $e): 
                                $estadoClase = '';
                                $estadoTexto = 'Pendiente';
                                if (!empty($e['estado_aprobacion'])) {
                                    if ($e['estado_aprobacion'] === 'aprobado') {
                                        $estadoClase = 'calificacion-aprobado';
                                        $estadoTexto = 'Aprobado';
                                    } elseif ($e['estado_aprobacion'] === 'desaprobado') {
                                        $estadoClase = 'calificacion-desaprobado';
                                        $estadoTexto = 'Desaprobado';
                                    }
                                }
                                $calificacion = !empty($e['calificacion']) ? number_format($e['calificacion'], 1) : '—';
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($e['nombre']) ?></td>
                                <td><?= htmlspecialchars($e['tipo_evidencia']) ?></td>
                                <td><?= $e['porcentaje'] ? number_format($e['porcentaje'], 1).'%' : '—' ?></td>
                                <td><?= date('d/m/Y', strtotime($e['fecha_evidencia'])) ?></td>
                                <td><?= $e['tiempo_entrega'] ? date('d/m/Y', strtotime($e['tiempo_entrega'])) : '—' ?></td>
                                <td><?= $calificacion ?></td>
                                <td><span class="<?= $estadoClase ?>"><?= $estadoTexto ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <?php include '../../config/footer.php'; ?>
    <script src="../../js/tema.js"></script>
    <script src="../../js/loader.js"></script>
    <script src="../../js/panel_menu.js"></script>
    <script src="../../js/dropdowns.js"></script>
    <script src="../../js/profile_menu.js"></script>
    <script src="../../js/sweetalerts.js"></script>
    <script src="../../js/menu.js"></script>
    <script>if (typeof initThemeToggle === 'function') setTimeout(initThemeToggle, 100);</script>
</body>
</html>