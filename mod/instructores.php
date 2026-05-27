<?php
session_start();
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../conexion/InstructorDAO.php';

$dao = new InstructorDAO();

// Obtener todos los instructores
$instructores = $dao->obtenerInstructores();

// Verificar si hay búsqueda
$buscar = isset($_GET['buscar']) ? $_GET['buscar'] : '';
$columna = isset($_GET['columna']) ? $_GET['columna'] : 'NOMBRES';

if (!empty($buscar)) {
    $instructores = $dao->buscarPorColumna($columna, $buscar);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructores - Detección de Deserción SENA</title>
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
        <div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1 class="page-title">Instructores</h1>
                <p class="page-subtitle">Gestión de instructores del SENA</p>
            </div>
            <?php if (esAdmin()): ?>
            <a href="crud/crear_instructor.php" class="btn-create">
                <i class="fas fa-plus"></i> Nuevo Instructor
            </a>
            <?php endif; ?>
        </div>

        <!-- Barra de búsqueda -->
        <div class="search-section">
            <form class="search-form" method="GET">
                <select name="columna" class="search-select">
                    <option value="NOMBRES" <?php echo $columna == 'NOMBRES' ? 'selected' : ''; ?>>Nombre</option>
                    <option value="APELLIDOS" <?php echo $columna == 'APELLIDOS' ? 'selected' : ''; ?>>Apellido</option>
                    <option value="EMAIL" <?php echo $columna == 'EMAIL' ? 'selected' : ''; ?>>Email</option>
                    <option value="ESPECIALIDAD" <?php echo $columna == 'ESPECIALIDAD' ? 'selected' : ''; ?>>Especialidad</option>
                </select>
                <input type="text" name="buscar" placeholder="Buscar instructor..." value="<?php echo htmlspecialchars($buscar); ?>">
                <button type="submit" class="btn-action"><i class="fas fa-search"></i> Buscar</button>
                <?php if (!empty($buscar)): ?>
                    <a href="instructores.php" class="btn-cancel"><i class="fas fa-times"></i> Limpiar</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Grid de instructores -->
        <div class="instructores-grid">
            <?php if (empty($instructores)): ?>
                <div class="empty-state" style="grid-column: 1/-1;">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <h3>No hay instructores</h3>
                    <p>No se encontraron instructores<?php echo !empty($buscar) ? ' con ese criterio de búsqueda' : ''; ?>.</p>
                </div>
            <?php else: ?>
                <?php foreach($instructores as $ins): 
                    $iniciales = substr($ins['NOMBRES'], 0, 1) . substr($ins['APELLIDOS'], 0, 1);
                ?>
                <div class="instructor-card" onclick="window.location.href='instructor_detalle.php?id=<?php echo $ins['INSTRUCTOR_ID']; ?>'">
                    <div class="instructor-card-header">
                        <div class="instructor-avatar"><?php echo $iniciales; ?></div>
                        <h3><?php echo $ins['NOMBRES'] . ' ' . $ins['APELLIDOS']; ?></h3>
                    </div>
                    <div class="instructor-card-body">
                        <div class="instructor-especialidad">
                            <i class="fas fa-code"></i> <?php echo $ins['ESPECIALIDAD']; ?>
                        </div>
                        <div class="instructor-contacto">
                            <i class="fas fa-envelope"></i> <?php echo $ins['EMAIL']; ?>
                        </div>
                    </div>
                    <div class="instructor-card-footer">
                        <a href="instructor_detalle.php?id=<?php echo $ins['INSTRUCTOR_ID']; ?>" class="btn-ver-perfil">
                            Ver perfil completo <i class="fas fa-arrow-right"></i>
                        </a>
                        <?php if (esAdmin()): ?>
                        <div style="display: flex; gap: 5px; margin-top: 10px;" onclick="event.stopPropagation();">
                            <a href="crud/editar_instructor.php?id=<?= $ins['INSTRUCTOR_ID'] ?>" class="btn-edit" style="padding: 5px 10px;">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="#" class="btn-delete" style="padding: 5px 10px;" onclick="confirmarEliminacion(<?= $ins['INSTRUCTOR_ID'] ?>)">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </div>
                        <?php endif; ?>
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