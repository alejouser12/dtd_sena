<?php
session_start();
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../conexion/ProgramaDAO.php';
require_once __DIR__ . '/../conexion/CentroDAO.php';

$programaDAO = new ProgramaDAO();
$centroDAO = new CentroDAO();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: programas.php');
    exit;
}

$programa = $programaDAO->obtenerPorId($id);

if (!$programa) {
    header('Location: programas.php');
    exit;
}

$centro = null;
if (!empty($programa['CENTRO_ID'])) {
    $centro = $centroDAO->obtenerPorId($programa['CENTRO_ID']);
}

$fichas = $programaDAO->obtenerFichas($id);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($programa['NOMBRE']); ?> - Programa</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="css/programa_detalle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div id="loader">
        <img src="../img/logo_sena_verde.png" alt="Logo SENA" id="loader-logo">
    </div>

    <?php include '../config/header.php'; ?>

    <main class="container" id="contenido-principal">
        <!-- Botón volver dinámico -->
        <?php
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if (strpos($referer, 'centro_detalle.php') !== false):
        ?>
        <a href="<?php echo htmlspecialchars($referer); ?>" class="btn-view-all" style="margin-bottom: 20px; display: inline-block;">
            <i class="fas fa-arrow-left"></i> Volver al centro
        </a>
        <?php else: ?>
        <a href="programas.php" class="btn-view-all" style="margin-bottom: 20px; display: inline-block;">
            <i class="fas fa-arrow-left"></i> Volver a programas
        </a>
        <?php endif; ?>

        <!-- Cabecera del Programa con botones de edición/eliminación para admin -->
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; margin-bottom: 20px;">
            <div class="programa-header" style="flex: 1;">
                <h1 class="programa-titulo"><?php echo htmlspecialchars($programa['NOMBRE']); ?></h1>
                <div class="programa-meta">
                    <span><i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($programa['NIVEL_FORMACION']); ?></span>
                    <span><i class="far fa-clock"></i> <?php echo $programa['DURACION_MESES']; ?> meses</span>
                    <span><i class="fas fa-circle"></i> <?php echo htmlspecialchars($programa['ESTADO']); ?></span>
                </div>
            </div>
            <?php if (esAdmin()): ?>
            <div style="display: flex; gap: 10px;">
                <a href="editar_programa.php?id=<?= $programa['PROGRAMA_ID'] ?>" class="btn-create">
                    <i class="fas fa-edit"></i> Editar
                </a>
                <a href="#" class="btn-cancel" onclick="confirmarEliminacion(<?= $programa['PROGRAMA_ID'] ?>)">
                    <i class="fas fa-trash-alt"></i> Eliminar
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Información del centro si existe -->
        <?php if ($centro): ?>
        <div class="ubicacion-header">
            <div class="ubicacion-titulo">
                <i class="fas fa-map-marker-alt"></i> Centro de Formación
            </div>
            <div class="ubicacion-grid">
                <div class="ubicacion-item" onclick="window.location.href='centro_detalle.php?id=<?php echo $centro['CENTRO_ID']; ?>'" style="cursor: pointer;">
                    <div class="ubicacion-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="ubicacion-texto">
                        <span class="label">Centro</span>
                        <span class="valor"><?php echo htmlspecialchars($centro['NOMBRE']); ?></span>
                        <span class="detalle"><i class="fas fa-chevron-right"></i> Ver detalles del centro</span>
                    </div>
                </div>
                <?php if (!empty($centro['REGIONAL_NOMBRE'])): ?>
                <div class="ubicacion-item">
                    <div class="ubicacion-icon">
                        <i class="fas fa-map-marked-alt"></i>
                    </div>
                    <div class="ubicacion-texto">
                        <span class="label">Regional</span>
                        <span class="valor"><?php echo htmlspecialchars($centro['REGIONAL_NOMBRE']); ?></span>
                        <span class="detalle"><i class="fas fa-city"></i> <?php echo htmlspecialchars($centro['CIUDAD'] ?? ''); ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Estadísticas del programa -->
        <div class="programa-stats-grid">
            <div class="programa-stat-card">
                <div class="programa-stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="programa-stat-content">
                    <span class="programa-stat-value"><?php echo $programa['total_aprendices'] ?? 0; ?></span>
                    <span class="programa-stat-label">Total Aprendices</span>
                </div>
            </div>
            <div class="programa-stat-card">
                <div class="programa-stat-icon">
                    <i class="fas fa-book-open"></i>
                </div>
                <div class="programa-stat-content">
                    <span class="programa-stat-value"><?php echo $programa['total_fichas'] ?? 0; ?></span>
                    <span class="programa-stat-label">Fichas Activas</span>
                </div>
            </div>
            <div class="programa-stat-card">
                <div class="programa-stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="programa-stat-content">
                    <span class="programa-stat-value"><?php echo $programa['DURACION_MESES']; ?></span>
                    <span class="programa-stat-label">Meses duración</span>
                </div>
            </div>
        </div>

        <!-- Fichas del Programa -->
        <div class="section-header-moderno">
            <h2><i class="fas fa-layer-group"></i> Fichas del Programa</h2>
            <span class="badge" style="font-size: 16px; padding: 8px 20px;">
                <?php echo count($fichas); ?> fichas encontradas
            </span>
        </div>

        <?php if (empty($fichas)): ?>
            <div class="empty-state-moderno">
                <i class="fas fa-folder-open"></i>
                <h3>No hay fichas disponibles</h3>
                <p>Este programa no tiene fichas asociadas actualmente.</p>
            </div>
        <?php else: ?>
            <div class="fichas-grid-moderno">
                <?php foreach($fichas as $ficha): ?>
                <div class="ficha-card-moderna" onclick="window.location.href='ficha_detalle.php?id=<?php echo $ficha['FICHA_ID']; ?>'">
                    <div class="ficha-header-moderno">
                        <span class="ficha-code-moderno"><?php echo htmlspecialchars($ficha['CODIGO_FICHA']); ?></span>
                        <span class="ficha-status-moderno">
                            <i class="fas fa-circle" style="font-size: 8px; margin-right: 5px;"></i>
                            <?php echo htmlspecialchars($ficha['ESTADO']); ?>
                        </span>
                    </div>
                    <div class="ficha-body-moderno">
                        <div class="ficha-info-item">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Inicio: <strong><?php echo date('d/m/Y', strtotime($ficha['FECHA_INICIO'])); ?></strong></span>
                        </div>
                        <div class="ficha-info-item">
                            <i class="fas fa-calendar-check"></i>
                            <span>Fin: <strong><?php echo date('d/m/Y', strtotime($ficha['FECHA_FIN'])); ?></strong></span>
                        </div>
                        <div class="ficha-info-item">
                            <i class="fas fa-users"></i>
                            <span>Aprendices: <strong><?php echo $ficha['total_aprendices']; ?></strong></span>
                        </div>
                        
                        <?php if($ficha['total_aprendices'] > 0): ?>
                        <div class="ficha-aprendices-badge">
                            <i class="fas fa-user-graduate"></i>
                            <?php echo $ficha['total_aprendices']; ?> aprendices matriculados
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="ficha-footer-moderno">
                        <span class="btn-ver-aprendices">
                            Ver detalles <i class="fas fa-arrow-right"></i>
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

    <?php include '../config/footer.php'; ?>

    <script src="../js/tema.js"></script>
    <script src="../js/loader.js"></script>
    <script src="../js/panel_menu.js"></script>
    <script src="../js/dropdowns.js"></script>
    <script src="../js/profile_menu.js"></script>
    <script src="../js/sweetalerts.js"></script>
    <script src="../js/menu.js"></script>

    <script>
        function confirmarEliminacion(id) {
            Swal.fire({
                title: '¿Eliminar programa?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'eliminar_programa.php?id=' + id;
                }
            });
        }

        if (typeof initThemeToggle === 'function') {
            setTimeout(initThemeToggle, 100);
        }
    </script>
</body>
</html>