<?php
session_start();
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../conexion/InstructorDAO.php';

$dao = new InstructorDAO();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: instructores.php');
    exit;
}

$instructor = $dao->obtenerPorId($id);

if (!$instructor) {
    header('Location: instructores.php');
    exit;
}

$fichas = $dao->obtenerFichas($id);
$proximasClases = $dao->obtenerProximasClases($id, 3);

$iniciales = substr($instructor['NOMBRES'], 0, 1) . substr($instructor['APELLIDOS'], 0, 1);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $instructor['NOMBRES'] . ' ' . $instructor['APELLIDOS']; ?> - Instructor</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="css/instructores.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div id="loader">
        <img src="../img/logo_sena_verde.png" alt="Logo SENA" id="loader-logo">
    </div>

    <?php include "../config/header.php"; ?>

    <main class="container" id="contenido-principal">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <a href="instructores.php" class="btn-volver-detalle">
                <i class="fas fa-arrow-left"></i> Volver a la lista
            </a>
            <a href="gestionar_horario.php?ficha_id=<?= $ficha['FICHA_ID'] ?>" class="btn-action">
    <i class="fas fa-calendar-alt"></i> Horario
</a>
            <?php if (esAdmin()): ?>
            <div style="display: flex; gap: 10px;">
                <a href="crud/editar_instructor.php?id=<?= $id ?>" class="btn-create">
                    <i class="fas fa-edit"></i> Editar Instructor
                </a>
                <a href="#" class="btn-cancel" onclick="confirmarEliminacion(<?= $id ?>)">
                    <i class="fas fa-trash-alt"></i> Eliminar
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Cabecera del Instructor -->
        <div class="instructor-detalle-header">
            <div class="instructor-avatar-large">
                <span><?php echo $iniciales; ?></span>
            </div>
            <div class="instructor-info-detalle">
                <h1><?php echo $instructor['NOMBRES'] . ' ' . $instructor['APELLIDOS']; ?></h1>
                <p class="instructor-title-detalle">
                    <i class="fas fa-briefcase"></i> Instructor - <?php echo $instructor['ESPECIALIDAD']; ?>
                </p>
                <div class="instructor-badges-detalle">
                    <span class="badge"><i class="fas fa-envelope"></i> <?php echo $instructor['EMAIL']; ?></span>
                </div>
                <div class="instructor-stats-mini-detalle">
                    <span class="status-activo-detalle">
                        <i class="fas fa-circle"></i> Activo
                    </span>
                </div>
            </div>
        </div>

        <!-- Tarjetas de estadísticas -->
        <div class="stats-grid-detalle">
            <div class="stat-card-detalle">
                <div class="stat-icon-detalle"><i class="fas fa-users"></i></div>
                <div class="stat-content-detalle">
                    <span class="stat-value-detalle"><?php echo $instructor['total_aprendices'] ?? 0; ?></span>
                    <span class="stat-label-detalle">Total Aprendices</span>
                </div>
            </div>
            <div class="stat-card-detalle">
                <div class="stat-icon-detalle"><i class="fas fa-book-open"></i></div>
                <div class="stat-content-detalle">
                    <span class="stat-value-detalle"><?php echo $instructor['total_fichas'] ?? 0; ?></span>
                    <span class="stat-label-detalle">Fichas a Cargo</span>
                </div>
            </div>
            <div class="stat-card-detalle">
                <div class="stat-icon-detalle"><i class="fas fa-check-circle"></i></div>
                <div class="stat-content-detalle">
                    <span class="stat-value-detalle"><?php echo $instructor['fichas_activas'] ?? 0; ?></span>
                    <span class="stat-label-detalle">Fichas Activas</span>
                </div>
            </div>
        </div>

        <!-- Fichas del Instructor -->
        <div class="section-header-detalle">
            <h2><i class="fas fa-book-open"></i> Fichas a Cargo</h2>
        </div>

        <div class="fichas-grid-detalle">
            <?php if (empty($fichas)): ?>
                <div class="empty-state-detalle">
                    <i class="fas fa-info-circle"></i>
                    <p>No hay fichas asignadas a este instructor</p>
                </div>
            <?php else: ?>
                <?php foreach($fichas as $ficha): ?>
                <div class="ficha-card-detalle" onclick="window.location.href='ficha_detalle.php?id=<?php echo $ficha['FICHA_ID']; ?>'">
                    <div class="ficha-header-detalle">
                        <span class="ficha-code-detalle"><?php echo $ficha['CODIGO_FICHA']; ?></span>
                        <span class="ficha-status-detalle"><?php echo $ficha['ESTADO']; ?></span>
                    </div>
                    <div class="ficha-body-detalle">
                        <h3><?php echo $ficha['PROGRAMA_NOMBRE']; ?></h3>
                        <div class="ficha-info-item-detalle">
                            <i class="fas fa-users"></i>
                            <span>Aprendices: <strong><?php echo $ficha['total_aprendices']; ?></strong></span>
                        </div>
                        <div class="ficha-info-item-detalle">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Inicio: <strong><?php echo date('d/m/Y', strtotime($ficha['FECHA_INICIO'])); ?></strong></span>
                        </div>
                        <div class="ficha-info-item-detalle">
                            <i class="fas fa-clock"></i>
                            <span>Horario: <strong><?php echo $ficha['HORARIO'] ?? 'No definido'; ?></strong></span>
                        </div>
                        <div class="ficha-info-item-detalle">
                            <i class="fas fa-door-open"></i>
                            <span>Aula: <strong><?php echo $ficha['AULA'] ?? 'N/A'; ?></strong></span>
                        </div>
                        
                        <?php if($ficha['total_aprendices'] > 0): ?>
                        <div class="ficha-aprendices-badge-detalle">
                            <i class="fas fa-user-graduate"></i>
                            <?php echo $ficha['total_aprendices']; ?> aprendices
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="ficha-footer-detalle">
                        <span class="btn-ver-ficha-detalle">
                            Ver detalles <i class="fas fa-arrow-right"></i>
                        </span>
                        <span class="ficha-fecha-detalle">
                            <i class="far fa-clock"></i>
                            <?php 
                            $inicio = new DateTime($ficha['FECHA_INICIO']);
                            $ahora = new DateTime();
                            $diferencia = $ahora->diff($inicio);
                            echo $diferencia->days . ' días';
                            ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <?php include "../config/footer.php"; ?>

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
                title: '¿Eliminar instructor?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'crud/eliminar_instructor.php?id=' + id;
                }
            });
        }

        if (typeof initThemeToggle === 'function') {
            setTimeout(initThemeToggle, 100);
        }
    </script>
</body>
</html>