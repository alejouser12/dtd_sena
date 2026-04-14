<?php
session_start();
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../conexion/ProgramaDAO.php';

$dao = new ProgramaDAO();

// Obtener todos los programas
$programas = $dao->obtenerProgramas();

// Verificar si hay búsqueda
$buscar = isset($_GET['buscar']) ? $_GET['buscar'] : '';

if (!empty($buscar)) {
    $programas = $dao->buscarProgramas($buscar);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programas - Detección de Deserción SENA</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="css/programas.css">
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
                <h1 class="page-title">Programas de Formación</h1>
                <p class="page-subtitle">Gestiona los programas académicos del SENA</p>
            </div>
            <?php if (esAdmin()): ?>
            <a href="crear_programa.php" class="btn-create">
                <i class="fas fa-plus"></i> Nuevo Programa
            </a>
            <?php endif; ?>
        </div>

        <!-- Barra de búsqueda -->
        <div class="search-section">
            <form class="search-form" method="GET">
                <input type="text" name="buscar" placeholder="Buscar programa por nombre o nivel..." value="<?php echo htmlspecialchars($buscar); ?>">
                <button type="submit" class="btn-action"><i class="fas fa-search"></i> Buscar</button>
                <?php if (!empty($buscar)): ?>
                    <a href="programas.php" class="btn-cancel"><i class="fas fa-times"></i> Limpiar</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (empty($programas)): ?>
            <div class="empty-state">
                <i class="fas fa-book"></i>
                <h3>No hay programas</h3>
                <p>No se encontraron programas<?php echo !empty($buscar) ? ' con ese criterio de búsqueda' : ''; ?>.</p>
            </div>
        <?php else: ?>
            <div class="programas-grid">
                <?php foreach($programas as $prog): ?>
                <div class="programa-card" onclick="window.location.href='programa_detalle.php?id=<?php echo $prog['PROGRAMA_ID']; ?>'">
                    <div class="programa-header">
                        <div class="programa-icon">
                            <i class="fas fa-<?php echo $prog['NIVEL_FORMACION'] == 'Tecnólogo' ? 'microchip' : 'tools'; ?>"></i>
                        </div>
                        <h3><?php echo $prog['NOMBRE']; ?></h3>
                        <div class="programa-nivel">
                            <i class="fas fa-graduation-cap"></i> <?php echo $prog['NIVEL_FORMACION']; ?>
                        </div>
                        <span class="programa-duracion">
                            <i class="far fa-clock"></i> <?php echo $prog['DURACION_MESES']; ?> meses
                        </span>
                    </div>
                    <div class="programa-body">
                        <div class="programa-stats">
                            <div class="stat-item">
                                <span class="valor"><?php echo isset($prog['total_fichas']) ? $prog['total_fichas'] : 0; ?></span>
                                <span class="etiqueta">Fichas</span>
                            </div>
                            <div class="stat-item">
                                <span class="valor"><?php echo isset($prog['total_aprendices']) ? $prog['total_aprendices'] : 0; ?></span>
                                <span class="etiqueta">Aprendices</span>
                            </div>
                        </div>
                        
                        <!-- INFORMACIÓN DE UBICACIÓN -->
                        <?php if(!empty($prog['centro_nombre'])): ?>
                        <div class="programa-ubicacion">
                            <i class="fas fa-building"></i>
                            <?php echo htmlspecialchars($prog['centro_nombre']); ?>
                            <?php if(!empty($prog['regional_nombre'])): ?>
                                <span style="margin-left: 5px;">· <?php echo htmlspecialchars($prog['regional_nombre']); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <span class="programa-estado">
                            <i class="fas fa-circle"></i> <?php echo $prog['ESTADO']; ?>
                        </span>
                    </div>
                    <div class="programa-footer">
                        <span class="btn-ver-fichas">
                            Ver fichas <i class="fas fa-arrow-right"></i>
                        </span>
                        <?php if (esAdmin()): ?>
                        <div style="display: flex; gap: 5px; margin-top: 10px;">
                            <a href="editar_programa.php?id=<?= $prog['PROGRAMA_ID'] ?>" class="btn-edit" style="padding: 5px 10px;" onclick="event.stopPropagation();">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="#" class="btn-delete" style="padding: 5px 10px;" onclick="event.stopPropagation(); confirmarEliminacion(<?= $prog['PROGRAMA_ID'] ?>)">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </div>
                        <?php endif; ?>
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