<?php
session_start();
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../conexion/AprendizDAO.php';

if (!esAprendiz()) {
    redirect_to('index.php');
}

$aprendizDAO = new AprendizDAO();
$usuarioId = $_SESSION['usuario_id'] ?? 0;

$sql = "SELECT APRENDIZ_ID, NOMBRES, APELLIDOS
        FROM aprendiz
        WHERE usuario_id = :usuario_id
        LIMIT 1";
$stmt = $aprendizDAO->ejecutarPreparado($sql, [':usuario_id' => $usuarioId]);
$aprendiz = $stmt ? $stmt->fetch() : null;

if (!$aprendiz) {
    redirect_to('index.php');
}

$aprendizId = $aprendiz['APRENDIZ_ID'];

$dias = isset($_GET['dias']) ? (int)$_GET['dias'] : 30;
if (!in_array($dias, [15, 30, 60])) $dias = 30;

$evidencias = $aprendizDAO->obtenerEvidenciasProximas($aprendizId, $dias);

$eventos = [];
foreach ($evidencias as $e) {
    $fecha = $e['fecha_evidencia'];
    if (!isset($eventos[$fecha])) {
        $eventos[$fecha] = [];
    }
    $eventos[$fecha][] = $e;
}

$fechas = array_keys($eventos);
sort($fechas);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendario de Eventos - DTD SENA</title>
    <link rel="stylesheet" href="<?= asset_url('css/style.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .calendario-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .filtro-dias {
            display: flex;
            gap: 10px;
        }
        .filtro-dias a {
            padding: 8px 16px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .filtro-dias a.activo {
            background: var(--color-verde-1);
            color: white;
        }
        .filtro-dias a.inactivo {
            background: var(--color-gris-cuerpo);
            color: var(--color-texto);
            border: 1px solid var(--border-color);
        }
        .calendario-grid {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .dia-card {
            background: var(--color-blanco);
            border-radius: var(--border-radius-card);
            overflow: hidden;
            box-shadow: var(--shadow-card);
            border: 1px solid var(--border-color);
            transition: transform 0.3s ease;
        }
        .dia-card:hover {
            transform: translateY(-3px);
        }
        .dia-header {
            background: linear-gradient(135deg, var(--color-verde-1), var(--color-verde-2));
            padding: 12px 20px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .dia-fecha {
            font-weight: 700;
            font-size: 1.1rem;
        }
        .dia-cantidad {
            background: rgba(255,255,255,0.2);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        .dia-body {
            padding: 15px 20px;
        }
        .evidencia-item {
            padding: 12px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .evidencia-item:last-child {
            border-bottom: none;
        }
        .evidencia-info {
            flex: 1;
        }
        .evidencia-nombre {
            font-weight: 700;
            color: var(--color-verde-1);
        }
        .evidencia-tipo {
            font-size: 12px;
            color: var(--color-texto-secundario);
            margin-top: 4px;
        }
        .evidencia-estado {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .badge-pendiente {
            background: #fef3c7;
            color: #d97706;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-calificado {
            background: #dcfce7;
            color: #16a34a;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .btn-ver-evidencia {
            background: transparent;
            border: 1px solid var(--color-verde-1);
            color: var(--color-verde-1);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-ver-evidencia:hover {
            background: var(--color-verde-1);
            color: white;
        }
        .empty-state-calendario {
            text-align: center;
            padding: 60px 20px;
            background: var(--color-blanco);
            border-radius: var(--border-radius-card);
            border: 1px solid var(--border-color);
        }
        .empty-state-calendario i {
            font-size: 60px;
            color: var(--color-texto-secundario);
            margin-bottom: 20px;
        }
        .dark-mode .dia-card {
            background: var(--color-gris-cuerpo);
        }
        .dark-mode .badge-pendiente {
            background: #78350f;
            color: #fed7aa;
        }
        .dark-mode .badge-calificado {
            background: #14532d;
            color: #bbf7d0;
        }
        @media (max-width: 768px) {
            .evidencia-item {
                flex-direction: column;
                align-items: flex-start;
            }
            .evidencia-estado {
                width: 100%;
                justify-content: space-between;
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
                <i class="fas fa-calendar-alt"></i> Calendario de Eventos
            </h1>
            <p class="page-subtitle">Próximas entregas de evidencias y fechas importantes</p>
        </div>

        <div class="calendario-controls">
            <div class="filtro-dias">
                <a href="?dias=15" class="<?= $dias == 15 ? 'activo' : 'inactivo' ?>">Próximos 15 días</a>
                <a href="?dias=30" class="<?= $dias == 30 ? 'activo' : 'inactivo' ?>">Próximos 30 días</a>
                <a href="?dias=60" class="<?= $dias == 60 ? 'activo' : 'inactivo' ?>">Próximos 60 días</a>
            </div>
            <div>
                <span class="badge-pendiente" style="display: inline-block; margin-right: 10px;"><i class="fas fa-hourglass-half"></i> Pendiente</span>
                <span class="badge-calificado"><i class="fas fa-check-circle"></i> Calificado</span>
            </div>
        </div>

        <?php if (empty($eventos)): ?>
            <div class="empty-state-calendario">
                <i class="fas fa-calendar-times"></i>
                <h3>No hay eventos próximos</h3>
                <p>No se encontraron evidencias programadas en los próximos <?= $dias ?> días.</p>
            </div>
        <?php else: ?>
            <div class="calendario-grid">
                <?php foreach ($fechas as $fecha): 
                    $fechaObj = new DateTime($fecha);
                    $hoy = new DateTime();
                    $diff = $hoy->diff($fechaObj)->days;
                    $esPasado = $fechaObj < $hoy;
                ?>
                <div class="dia-card">
                    <div class="dia-header">
                        <div class="dia-fecha">
                            <i class="fas fa-calendar-day"></i> 
                            <?= $fechaObj->format('l, d \d\e F \d\e Y') ?>
                        </div>
                        <div class="dia-cantidad">
                            <?= count($eventos[$fecha]) ?> evidencia(s)
                            <?php if ($diff == 0): ?>
                                <span style="background: #dc2626; margin-left: 8px;">Hoy</span>
                            <?php elseif (!$esPasado && $diff <= 3): ?>
                                <span style="background: #f59e0b; margin-left: 8px;">Próximo</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="dia-body">
                        <?php foreach ($eventos[$fecha] as $e): ?>
                        <div class="evidencia-item">
                            <div class="evidencia-info">
                                <div class="evidencia-nombre"><?= htmlspecialchars($e['nombre']) ?></div>
                                <div class="evidencia-tipo">
                                    <i class="fas fa-tag"></i> <?= htmlspecialchars($e['tipo_evidencia'] ?? 'Sin tipo') ?> 
                                    | <i class="fas fa-percent"></i> <?= $e['porcentaje'] ? number_format($e['porcentaje'], 0).'%' : 'Sin porcentaje' ?>
                                </div>
                            </div>
                            <div class="evidencia-estado">
                                <?php if ($e['estado_calificacion'] == 'calificado'): ?>
                                    <span class="badge-calificado"><i class="fas fa-check"></i> Calificado</span>
                                <?php else: ?>
                                    <span class="badge-pendiente"><i class="fas fa-hourglass-half"></i> Pendiente</span>
                                <?php endif; ?>
                                <button class="btn-ver-evidencia" onclick="verDetalleEvidencia(<?= $e['evidencias_id'] ?>)">
                                    <i class="fas fa-eye"></i> Ver
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div style="margin-top: 30px; text-align: center;">
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
        function verDetalleEvidencia(id) {
            window.location.href = '<?= app_url('mod/calificar_evidencia.php') ?>?id=' + id;
        }

        if (typeof initThemeToggle === 'function') {
            setTimeout(initThemeToggle, 100);
        }
    </script>
</body>
</html>