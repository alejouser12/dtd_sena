<?php
session_start();
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../conexion/FichaDAO.php';
require_once __DIR__ . '/../conexion/instructorDAO.php';

$dao = new FichaDAO();
$instructor_id = isset($_GET['instructor_id']) ? (int)$_GET['instructor_id'] : 0;

if (esInstructor()) {
    if ($instructor_id <= 0) {
        $instructor_id = (int)($_SESSION['usuario_ref_id'] ?? 0);
    }
    if ($instructor_id <= 0) {
        $instDAO_tmp = new InstructorDAO();
        $email = $_SESSION['usuario_email'] ?? '';
        if (!empty($email)) {
            $c = $instDAO_tmp->buscarPorColumna('EMAIL', $email);
            if (!empty($c) && isset($c[0]['INSTRUCTOR_ID'])) {
                $instructor_id = (int)$c[0]['INSTRUCTOR_ID'];
            }
        }
    }
}

if (esInstructor() && $instructor_id > 0) {
    $instDAO = new InstructorDAO();
    $fichasIds = $instDAO->obtenerFichasIds($instructor_id);
    $fichas = [];
    if (!empty($fichasIds)) {
        $fichas = $dao->obtenerPorIds($fichasIds);
    }
} else {
    $fichas = $dao->obtenerTodas();
}

$total_fichas = count($fichas);
$total_aprendices = array_sum(array_column($fichas, 'total_aprendices'));
$fichas_activas = count(array_filter($fichas, function($f) { return $f['ESTADO'] == 'Activa'; }));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toma de Asistencia - Fichas</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .asistencia-header {
            background: linear-gradient(135deg, var(--color-verde-1), var(--color-verde-2));
            padding: 30px;
            border-radius: var(--border-radius-card);
            margin-bottom: 30px;
            color: white;
            box-shadow: var(--shadow-card);
        }
        .asistencia-header h1 {
            font-size: 32px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .asistencia-header p {
            font-size: 16px;
            opacity: 0.9;
        }
        .asistencia-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .asistencia-stat-card {
            background: var(--color-blanco);
            border-radius: var(--border-radius-card);
            padding: 25px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: var(--shadow-card);
            border: 1px solid var(--border-color);
        }
        .asistencia-stat-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--color-verde-1), var(--color-verde-2));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }
        .asistencia-stat-content {
            display: flex;
            flex-direction: column;
        }
        .asistencia-stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--color-texto);
            line-height: 1.2;
        }
        .asistencia-stat-label {
            font-size: 14px;
            color: var(--color-texto-secundario);
        }
        .fichas-grid-moderno {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        .ficha-card-moderna {
            background: var(--color-blanco);
            border-radius: var(--border-radius-card);
            overflow: hidden;
            box-shadow: var(--shadow-card);
            border: 1px solid var(--border-color);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .ficha-card-moderna:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(57, 169, 0, 0.15);
            border-color: var(--color-verde-1);
        }
        .ficha-header-moderno {
            background: linear-gradient(to right, var(--color-verde-1), var(--color-verde-2));
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }
        .ficha-code-moderno {
            font-weight: 700;
            font-size: 18px;
        }
        .ficha-status-moderno {
            background: rgba(255,255,255,0.2);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .ficha-body-moderno {
            padding: 20px;
        }
        .ficha-info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
            color: var(--color-texto);
        }
        .ficha-info-item i {
            width: 20px;
            color: var(--color-verde-1);
        }
        .ficha-aprendices-badge {
            background: var(--color-verde-3);
            color: var(--color-verde-1);
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
        }
        .ficha-footer-moderno {
            border-top: 1px solid var(--border-color);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--color-gris-fondo);
        }
        .btn-ver-asistencia {
            color: var(--color-verde-1);
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .ficha-fecha {
            font-size: 12px;
            color: var(--color-texto-secundario);
        }
        .empty-state-moderno {
            text-align: center;
            padding: 60px 20px;
            background: var(--color-blanco);
            border-radius: var(--border-radius-card);
            border: 1px solid var(--border-color);
        }
        .empty-state-moderno i {
            font-size: 60px;
            color: var(--color-texto-secundario);
            margin-bottom: 20px;
        }
        .empty-state-moderno h3 {
            font-size: 24px;
            color: var(--color-texto);
            margin-bottom: 10px;
        }
        .empty-state-moderno p {
            color: var(--color-texto-secundario);
        }
        .section-header-moderno {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 40px 0 20px;
        }
        .section-header-moderno h2 {
            font-size: 24px;
            color: var(--color-texto);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-header-moderno .badge {
            background: var(--color-verde-3);
            color: var(--color-verde-1);
            padding: 8px 16px;
            border-radius: 30px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div id="loader">
        <img src="../img/logo_sena_verde.png" alt="Logo SENA" id="loader-logo">
    </div>

    <?php include "../config/header.php"; ?>

    <main class="container" id="contenido-principal" style="display:none; opacity:0;">
        <div class="asistencia-header">
            <h1>
                <i class="fas fa-clipboard-list"></i> Toma de Asistencia
            </h1>
            <p>Seleccione una ficha para registrar la asistencia de los aprendices</p>
        </div>

        <div class="asistencia-stats-grid">
            <div class="asistencia-stat-card">
                <div class="asistencia-stat-icon">
                    <i class="fas fa-book-open"></i>
                </div>
                <div class="asistencia-stat-content">
                    <span class="asistencia-stat-value"><?= $total_fichas ?></span>
                    <span class="asistencia-stat-label">Total fichas</span>
                </div>
            </div>
            <div class="asistencia-stat-card">
                <div class="asistencia-stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="asistencia-stat-content">
                    <span class="asistencia-stat-value"><?= $total_aprendices ?></span>
                    <span class="asistencia-stat-label">Aprendices</span>
                </div>
            </div>
            <div class="asistencia-stat-card">
                <div class="asistencia-stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="asistencia-stat-content">
                    <span class="asistencia-stat-value"><?= $fichas_activas ?></span>
                    <span class="asistencia-stat-label">Fichas activas</span>
                </div>
            </div>
        </div>

        <div class="section-header-moderno">
            <h2><i class="fas fa-layer-group"></i> Fichas disponibles</h2>
            <span class="badge"><?= $total_fichas ?> fichas</span>
        </div>

        <?php if (empty($fichas)): ?>
            <div class="empty-state-moderno">
                <i class="fas fa-folder-open"></i>
                <h3>No hay fichas disponibles</h3>
                <p>No se encontraron fichas registradas en el sistema.</p>
            </div>
        <?php else: ?>
            <div class="fichas-grid-moderno">
                <?php foreach($fichas as $ficha): ?>
                <div class="ficha-card-moderna" onclick="window.location.href='asistencias_detalle.php?id=<?= $ficha['FICHA_ID'] ?>'">
                    <div class="ficha-header-moderno">
                        <span class="ficha-code-moderno"><?= htmlspecialchars($ficha['CODIGO_FICHA']) ?></span>
                        <span class="ficha-status-moderno">
                            <i class="fas fa-circle" style="font-size: 8px; margin-right: 5px;"></i>
                            <?= htmlspecialchars($ficha['ESTADO']) ?>
                        </span>
                    </div>
                    <div class="ficha-body-moderno">
                        <div class="ficha-info-item">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Inicio: <strong><?= date('d/m/Y', strtotime($ficha['FECHA_INICIO'])) ?></strong></span>
                        </div>
                        <div class="ficha-info-item">
                            <i class="fas fa-calendar-check"></i>
                            <span>Fin: <strong><?= date('d/m/Y', strtotime($ficha['FECHA_FIN'])) ?></strong></span>
                        </div>
                        <div class="ficha-info-item">
                            <i class="fas fa-users"></i>
                            <span>Aprendices: <strong><?= $ficha['total_aprendices'] ?></strong></span>
                        </div>
                        <div class="ficha-info-item">
                            <i class="fas fa-graduation-cap"></i>
                            <span><?= htmlspecialchars($ficha['programa_nombre']) ?></span>
                        </div>
                        <?php if($ficha['total_aprendices'] > 0): ?>
                        <div class="ficha-aprendices-badge">
                            <i class="fas fa-user-graduate"></i>
                            <?= $ficha['total_aprendices'] ?> aprendices matriculados
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="ficha-footer-moderno">
                        <span class="btn-ver-asistencia">
                            Tomar asistencia <i class="fas fa-arrow-right"></i>
                        </span>
                        <span class="ficha-fecha">
                            <i class="far fa-clock"></i>
                            <?php 
                            $inicio = new DateTime($ficha['FECHA_INICIO']);
                            $ahora = new DateTime();
                            $diferencia = $ahora->diff($inicio);
                            echo $diferencia->days . ' días de iniciada';
                            ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <?php include "../config/footer.php"; ?>

    <script src="../js/tema.js"></script>
    <script src="../js/loader.js"></script>
    <script src="../js/panel_menu.js"></script>
    <script src="../js/dropdowns.js"></script>
    <script src="../js/profile_menu.js"></script>
    <script src="../js/sweetalerts.js"></script>
    <script src="../js/menu.js"></script>
</body>
</html>