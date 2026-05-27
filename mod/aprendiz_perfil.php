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

$sql = "SELECT a.*, f.CODIGO_FICHA, p.NOMBRE AS programa_nombre, p.NIVEL_FORMACION,
               c.NOMBRE AS centro_nombre, r.NOMBRE AS regional_nombre
        FROM aprendiz a
        LEFT JOIN ficha f ON a.FICHA_ID = f.FICHA_ID
        LEFT JOIN programa p ON f.PROGRAMA_ID = p.PROGRAMA_ID
        LEFT JOIN centro c ON f.CENTRO_ID = c.CENTRO_ID
        LEFT JOIN regional r ON c.REGIONAL_ID = r.REGIONAL_ID
        WHERE a.usuario_id = :usuario_id
        LIMIT 1";
$stmt = $aprendizDAO->ejecutarPreparado($sql, [':usuario_id' => $usuarioId]);
$aprendiz = $stmt ? $stmt->fetch() : null;

if (!$aprendiz) {
    redirect_to('index.php');
}

$edad = '';
if (!empty($aprendiz['FECHA_NACIMIENTO'])) {
    $edad = (new DateTime())->diff(new DateTime($aprendiz['FECHA_NACIMIENTO']))->y;
}

$riesgoTexto = $aprendiz['NIVEL_RIESGO_GLOBAL'] ?? 'Bajo';
$riesgoClass = '';
if ($riesgoTexto == 'Alto') $riesgoClass = 'riesgo-alto';
elseif ($riesgoTexto == 'Medio') $riesgoClass = 'riesgo-medio';
else $riesgoClass = 'riesgo-bajo';

$promedio = $aprendizDAO->obtenerPromedioEvidencias($aprendiz['APRENDIZ_ID']);
$aprobadas = $aprendizDAO->obtenerEvidenciasAprobadas($aprendiz['APRENDIZ_ID']);
$pendientes = $aprendizDAO->obtenerEvidenciasPendientes($aprendiz['APRENDIZ_ID']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - DTD SENA</title>
    <link rel="stylesheet" href="<?= asset_url('css/style.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div id="loader">
        <img src="<?= asset_url('img/logo_sena_verde.png') ?>" alt="Logo SENA" id="loader-logo">
    </div>

    <?php include __DIR__ . '/../config/header.php'; ?>

    <main class="container" id="contenido-principal" style="display:none; opacity:0;">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-user-circle"></i> Mi Perfil
            </h1>
            <p class="page-subtitle">Información personal y académica</p>
        </div>

        <div class="content-card" style="margin-bottom: 30px;">
            <div class="card-header">
                <h2 class="card-title">Datos Personales</h2>
            </div>
            <div class="card-body" style="display: flex; flex-wrap: wrap; gap: 30px;">
                <div style="flex: 1;">
                    <p><strong><i class="fas fa-id-card"></i> Tipo Documento:</strong> <?= htmlspecialchars($aprendiz['TIPO_DOCUMENTO']) ?></p>
                    <p><strong><i class="fas fa-hashtag"></i> Número Documento:</strong> <?= htmlspecialchars($aprendiz['NUMERO_DOCUMENTO']) ?></p>
                    <p><strong><i class="fas fa-user"></i> Nombres:</strong> <?= htmlspecialchars($aprendiz['NOMBRES']) ?></p>
                    <p><strong><i class="fas fa-user"></i> Apellidos:</strong> <?= htmlspecialchars($aprendiz['APELLIDOS']) ?></p>
                </div>
                <div style="flex: 1;">
                    <p><strong><i class="fas fa-calendar-alt"></i> Fecha Nacimiento:</strong> <?= date('d/m/Y', strtotime($aprendiz['FECHA_NACIMIENTO'])) ?> (<?= $edad ?> años)</p>
                    <p><strong><i class="fas fa-venus-mars"></i> Género:</strong> <?= htmlspecialchars($aprendiz['GENERO']) ?></p>
                    <p><strong><i class="fas fa-phone"></i> Teléfono:</strong> <?= htmlspecialchars($aprendiz['TELEFONO'] ?? 'No registrado') ?></p>
                    <p><strong><i class="fas fa-envelope"></i> Email:</strong> <?= htmlspecialchars($aprendiz['EMAIL']) ?></p>
                </div>
            </div>
        </div>

        <div class="content-card" style="margin-bottom: 30px;">
            <div class="card-header">
                <h2 class="card-title">Información Académica</h2>
            </div>
            <div class="card-body">
                <div style="display: flex; flex-wrap: wrap; gap: 30px;">
                    <div style="flex: 1;">
                        <p><strong><i class="fas fa-layer-group"></i> Ficha:</strong> <?= htmlspecialchars($aprendiz['CODIGO_FICHA'] ?? 'Sin asignar') ?></p>
                        <p><strong><i class="fas fa-book"></i> Programa:</strong> <?= htmlspecialchars($aprendiz['programa_nombre'] ?? 'Sin asignar') ?></p>
                        <p><strong><i class="fas fa-graduation-cap"></i> Nivel:</strong> <?= htmlspecialchars($aprendiz['NIVEL_FORMACION'] ?? '') ?></p>
                    </div>
                    <div style="flex: 1;">
                        <p><strong><i class="fas fa-building"></i> Centro:</strong> <?= htmlspecialchars($aprendiz['centro_nombre'] ?? 'Sin asignar') ?></p>
                        <p><strong><i class="fas fa-map-marker-alt"></i> Regional:</strong> <?= htmlspecialchars($aprendiz['regional_nombre'] ?? 'Sin asignar') ?></p>
                        <p><strong><i class="fas fa-check-circle"></i> Estado:</strong> <span class="estado-badge <?= strtolower($aprendiz['ESTADO_ACADEMICO']) ?>"><?= htmlspecialchars($aprendiz['ESTADO_ACADEMICO']) ?></span></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-star"></i></div>
                <div class="stat-content">
                    <span class="stat-value"><?= number_format($promedio, 1) ?></span>
                    <span class="stat-label">Promedio General</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-content">
                    <span class="stat-value"><?= $aprobadas ?></span>
                    <span class="stat-label">Evidencias Aprobadas</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-content">
                    <span class="stat-value"><?= $pendientes ?></span>
                    <span class="stat-label">Evidencias Pendientes</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="stat-content">
                    <span class="stat-value <?= $riesgoClass ?>"><?= $riesgoTexto ?></span>
                    <span class="stat-label">Nivel de Riesgo</span>
                </div>
            </div>
        </div>

        <div style="display: flex; gap: 15px; justify-content: center; margin-top: 30px;">
            <a href="<?= app_url('mod/aprendiz_estadisticas.php') ?>" class="btn-action">
                <i class="fas fa-chart-line"></i> Ver Estadísticas
            </a>
            <a href="<?= app_url('mod/aprendiz_faltas.php') ?>" class="btn-action">
                <i class="fas fa-calendar-times"></i> Historial de Faltas
            </a>
            <a href="<?= app_url('mod/aprendiz_calendario.php') ?>" class="btn-action">
                <i class="fas fa-calendar-alt"></i> Calendario
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
        if (typeof initThemeToggle === 'function') {
            setTimeout(initThemeToggle, 100);
        }
    </script>
</body>
</html>