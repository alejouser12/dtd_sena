<?php
session_start();
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../conexion/AprendizDAO.php';

if (!esAprendiz()) {
    redirect_to('index.php');
}

$aprendizDAO = new AprendizDAO();
$aprendizId  = (int)($_SESSION['usuario_ref_id'] ?? 0);
$usuarioId   = (int)($_SESSION['usuario_id'] ?? 0);
$usuarioEmail = $_SESSION['usuario_email'] ?? '';

$aprendiz = $aprendizDAO->obtenerPorSesion($aprendizId, $usuarioId, $usuarioEmail);
if (!$aprendiz) {
    redirect_to('index.php');
}

$aprendizId = $aprendiz['APRENDIZ_ID'];

$yearActual = date('Y');
$mesActual = date('m');

$yearSeleccionado = isset($_GET['year']) ? (int)$_GET['year'] : $yearActual;
$mesSeleccionado = isset($_GET['month']) ? (int)$_GET['month'] : $mesActual;

$asistenciaMensual = $aprendizDAO->obtenerAsistenciaMensual($aprendizId, $yearSeleccionado, $mesSeleccionado);

$diasDelMes = cal_days_in_month(CAL_GREGORIAN, $mesSeleccionado, $yearSeleccionado);
$asistenciaPorDia = [];
for ($i = 1; $i <= $diasDelMes; $i++) {
    $asistenciaPorDia[$i] = null;
}
foreach ($asistenciaMensual as $reg) {
    $asistenciaPorDia[$reg['dia']] = $reg;
}

$labels = [];
$asistenciaData = [];
$horasData = [];
for ($i = 1; $i <= $diasDelMes; $i++) {
    $labels[] = $i;
    $estado = $asistenciaPorDia[$i]['ESTADO'] ?? 'sin_registro';
    if ($estado == 'asistio') {
        $asistenciaData[] = 1;
        $horasData[] = 0;
    } elseif ($estado == 'retardo') {
        $asistenciaData[] = 0.5;
        $horasData[] = (int)($asistenciaPorDia[$i]['HORAS_FALTA'] ?? 0);
    } elseif ($estado == 'excusa') {
        $asistenciaData[] = 0.75;
        $horasData[] = 0;
    } elseif ($estado == 'falta') {
        $asistenciaData[] = 0;
        $horasData[] = 4;
    } else {
        $asistenciaData[] = null;
        $horasData[] = null;
    }
}

$estadosCount = [
    'asistio' => 0,
    'retardo' => 0,
    'falta' => 0,
    'excusa' => 0
];
foreach ($asistenciaMensual as $reg) {
    $estadosCount[$reg['ESTADO']] = ($estadosCount[$reg['ESTADO']] ?? 0) + 1;
}

$evolucionNotas = $aprendizDAO->obtenerEvolucionNotas($aprendizId);
$evolucionLabels = [];
$evolucionData = [];
foreach ($evolucionNotas as $nota) {
    $evolucionLabels[] = date('d/m/Y', strtotime($nota['fecha'])) . "\n" . substr($nota['evidencia_nombre'], 0, 20);
    $evolucionData[] = (float)$nota['calificacion'];
}

$meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

$years = range($yearActual - 2, $yearActual);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Estadísticas - DTD SENA</title>
    <link rel="stylesheet" href="<?= asset_url('css/style.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .chart-container {
            background: var(--color-blanco);
            border-radius: var(--border-radius-card);
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-card);
            border: 1px solid var(--border-color);
        }
        .chart-container h3 {
            margin-bottom: 20px;
            color: var(--color-verde-1);
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .filtro-mes {
            display: flex;
            gap: 15px;
            align-items: flex-end;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .filtro-mes .form-group {
            margin: 0;
        }
        .dark-mode .chart-container {
            background: var(--color-gris-cuerpo);
        }
        .stats-resumen {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        .stat-resumen {
            background: var(--color-blanco);
            border-radius: var(--border-radius-card);
            padding: 15px;
            text-align: center;
            box-shadow: var(--shadow-card);
            border: 1px solid var(--border-color);
        }
        .stat-resumen .valor {
            font-size: 28px;
            font-weight: 700;
            color: var(--color-verde-1);
        }
        .stat-resumen .label {
            font-size: 12px;
            color: var(--color-texto-secundario);
            margin-top: 5px;
        }
        @media (max-width: 768px) {
            .stats-resumen {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div id="loader">
        <img src="<?= asset_url('img/logo_sena_verde.png') ?>" alt="Logo SENA" id="loader-logo">
    </div>

    <?php include __DIR__ . '/../config/header.php'; ?>

    <main class="container" id="contenido-principal" style="display:none; opacity:0;">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-chart-line"></i> Mis Estadísticas
            </h1>
            <p class="page-subtitle">Análisis de asistencia y rendimiento académico</p>
        </div>

        <div class="stats-resumen">
            <div class="stat-resumen">
                <div class="valor"><?= $estadosCount['asistio'] ?></div>
                <div class="label">Días Asistidos</div>
            </div>
            <div class="stat-resumen">
                <div class="valor"><?= $estadosCount['retardo'] ?></div>
                <div class="label">Retardos</div>
            </div>
            <div class="stat-resumen">
                <div class="valor"><?= $estadosCount['falta'] ?></div>
                <div class="label">Faltas</div>
            </div>
            <div class="stat-resumen">
                <div class="valor"><?= $estadosCount['excusa'] ?></div>
                <div class="label">Excusas</div>
            </div>
        </div>

        <div class="filtro-mes">
            <form method="GET" action="" style="display: flex; gap: 15px; align-items: flex-end;">
                <div class="form-group">
                    <label for="year">Año</label>
                    <select name="year" id="year" class="form-control">
                        <?php foreach ($years as $y): ?>
                            <option value="<?= $y ?>" <?= $y == $yearSeleccionado ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="month">Mes</label>
                    <select name="month" id="month" class="form-control">
                        <?php foreach ($meses as $num => $nombre): ?>
                            <option value="<?= $num ?>" <?= $num == $mesSeleccionado ? 'selected' : '' ?>><?= $nombre ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-action">Filtrar</button>
                <a href="<?= app_url('mod/aprendiz_estadisticas.php') ?>" class="btn-cancel">Reset</a>
            </form>
        </div>

        <div class="chart-container">
            <h3><i class="fas fa-calendar-check"></i> Asistencia Diaria - <?= $meses[$mesSeleccionado] ?> <?= $yearSeleccionado ?></h3>
            <canvas id="asistenciaChart" width="400" height="200"></canvas>
        </div>

        <div class="chart-container">
            <h3><i class="fas fa-chart-pie"></i> Distribución de Asistencia</h3>
            <canvas id="pieChart" width="400" height="200"></canvas>
        </div>

        <div class="chart-container">
            <h3><i class="fas fa-chart-line"></i> Evolución de Calificaciones</h3>
            <canvas id="notasChart" width="400" height="200"></canvas>
        </div>

        <div style="margin-top: 20px; text-align: center;">
            <a href="<?= app_url('mod/aprendiz_perfil.php') ?>" class="btn-view-all">
                <i class="fas fa-arrow-left"></i> Volver a Mi Perfil
            </a>
        </div>
    </main>

    <?php include __DIR__ . '/../config/footer.php'; ?>

    <script src="<?= asset_url('js/tema.js') ?>"></script>
    <script src="<?= asset_url('js/loader.js') ?>"></script>
    <script src="<?= asset_url('js/dropdowns.js') ?>"></script>
    <script src="<?= asset_url('js/profile_menu.js') ?>"></script>
    <script src="<?= asset_url('js/sweetalerts.js') ?>"></script>
    <script src="<?= asset_url('js/menu.js') ?>"></script>

    <script>
        const ctxAsistencia = document.getElementById('asistenciaChart').getContext('2d');
        new Chart(ctxAsistencia, {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [
                    {
                        label: 'Asistencia (1=presente, 0.5=retardo, 0.75=excusa, 0=falta)',
                        data: <?= json_encode($asistenciaData) ?>,
                        backgroundColor: 'rgba(57, 169, 0, 0.6)',
                        borderColor: '#39a900',
                        borderWidth: 1
                    },
                    {
                        label: 'Horas de inasistencia',
                        data: <?= json_encode($horasData) ?>,
                        backgroundColor: 'rgba(220, 38, 38, 0.6)',
                        borderColor: '#dc2626',
                        borderWidth: 1,
                        type: 'line',
                        tension: 0.3,
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Valor / Horas' }
                    },
                    x: {
                        title: { display: true, text: 'Día del mes' }
                    }
                }
            }
        });

        const ctxPie = document.getElementById('pieChart').getContext('2d');
        new Chart(ctxPie, {
            type: 'pie',
            data: {
                labels: ['Presente', 'Retardo', 'Falta', 'Excusa'],
                datasets: [{
                    data: [<?= $estadosCount['asistio'] ?>, <?= $estadosCount['retardo'] ?>, <?= $estadosCount['falta'] ?>, <?= $estadosCount['excusa'] ?>],
                    backgroundColor: ['#16a34a', '#f59e0b', '#dc2626', '#6d28d9']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });

        <?php if (!empty($evolucionData)): ?>
        const ctxNotas = document.getElementById('notasChart').getContext('2d');
        new Chart(ctxNotas, {
            type: 'line',
            data: {
                labels: <?= json_encode($evolucionLabels) ?>,
                datasets: [{
                    label: 'Calificación',
                    data: <?= json_encode($evolucionData) ?>,
                    borderColor: '#39a900',
                    backgroundColor: 'rgba(57, 169, 0, 0.1)',
                    tension: 0.3,
                    fill: true,
                    pointBackgroundColor: '#39a900',
                    pointBorderColor: '#fff',
                    pointRadius: 5
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 5,
                        title: { display: true, text: 'Calificación' }
                    }
                }
            }
        });
        <?php endif; ?>

        if (typeof initThemeToggle === 'function') {
            setTimeout(initThemeToggle, 100);
        }
    </script>
</body>
</html>