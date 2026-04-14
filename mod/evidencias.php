<?php
// mod/evidencias.php
session_start();
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../conexion/EvidenciaDAO.php';

$dao = new EvidenciaDAO();
$evidencias = $dao->obtenerTodas();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Evidencias</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div id="loader">
        <img src="../img/logo_sena_verde.png" alt="Logo SENA" id="loader-logo">
    </div>

    <?php include "../config/header.php"; ?>

    <main class="container" id="contenido-principal" style="display:none; opacity:0;">
        <div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1 class="page-title">
                    <i class="fas fa-file-alt"></i> Gestión de Evidencias
                </h1>
                <p class="page-subtitle">Listado de evidencias asignadas por ficha</p>
            </div>
            <?php if (esAdmin()): ?>
            <a href="crud/crear_evidencia.php" class="btn-create">
                <i class="fas fa-plus"></i> Crear Evidencia
            </a>
            <?php endif; ?>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>%</th>
                        <th>Fecha</th>
                        <th>Tiempo entrega</th>
                        <th>Ficha</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                        <?php if (esAdmin()): ?>
                        <th>Admin</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($evidencias)): ?>
                    <tr>
                        <td colspan="<?= esAdmin() ? 9 : 8 ?>" class="empty-state">
                            <i class="fas fa-file"></i> No hay evidencias registradas
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($evidencias as $e): ?>
                        <tr>
                            <td><?= htmlspecialchars($e['nombre']) ?></td>
                            <td><?= htmlspecialchars($e['tipo_evidencia'] ?? '-') ?></td>
                            <td><?= $e['porcentaje'] ? number_format($e['porcentaje'], 2).'%' : '-' ?></td>
                            <td><?= date('d/m/Y', strtotime($e['fecha_evidencia'])) ?></td>
                            <td><?= date('d/m/Y', strtotime($e['tiempo_entrega'])) ?></td>
                            <td><?= htmlspecialchars($e['CODIGO_FICHA']) ?></td>
                            <td>
                                <span class="estado-badge <?= $e['estado_evidencia'] ?>">
                                    <?= ucfirst($e['estado_evidencia']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="calificar_evidencia.php?id=<?= $e['evidencias_id'] ?>" class="btn-view-all" title="Calificar">
                                    <i class="fas fa-star"></i> Calificar
                                </a>
                            </td>
                            <?php if (esAdmin()): ?>
                            <td>
                                <div style="display: flex; gap: 5px;">
                                    <a href="editar_evidencia.php?id=<?= $e['evidencias_id'] ?>" class="btn-edit" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="#" class="btn-delete" title="Eliminar" onclick="confirmarEliminacion(<?= $e['evidencias_id'] ?>)">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
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
                title: '¿Eliminar evidencia?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'eliminar_evidencia.php?id=' + id;
                }
            });
        }

        if (typeof initThemeToggle === 'function') {
            setTimeout(initThemeToggle, 100);
        }
    </script>
</body>
</html>