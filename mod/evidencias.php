<?php
// mod/evidencias.php
session_start();
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../conexion/EvidenciaDAO.php';
require_once __DIR__ . '/../conexion/RegionalDAO.php';
require_once __DIR__ . '/../conexion/CentroDAO.php';

$dao = new EvidenciaDAO();
$regionalDAO = new RegionalDAO();
$centroDAO = new CentroDAO();

$rol = $_SESSION['usuario_rol'] ?? 'aprendiz';
$usuarioId = null;
if (esAdmin()) {
    $usuarioId = $_SESSION['usuario_id'] ?? null;
} elseif (esInstructor()) {
    $usuarioId = $_SESSION['usuario_ref_id'] ?? null;
    if (empty($usuarioId)) {
        $email = $_SESSION['usuario_email'] ?? '';
        if (!empty($email)) {
            require_once __DIR__ . '/../conexion/instructorDAO.php';
            $instDAO = new InstructorDAO();
            $c = $instDAO->buscarPorColumna('EMAIL', $email);
            if (!empty($c) && isset($c[0]['INSTRUCTOR_ID'])) {
                $usuarioId = $c[0]['INSTRUCTOR_ID'];
            }
        }
    }
} else {
    $usuarioId = $_SESSION['usuario_id'] ?? null;
}

// Filtros (solo para admin)
$regionalId = isset($_GET['regional_id']) ? (int)$_GET['regional_id'] : null;
$centroId   = isset($_GET['centro_id']) ? (int)$_GET['centro_id'] : null;

$regionales = [];
$centros = [];
if (esAdmin()) {
    $regionales = $regionalDAO->obtenerTodas();
    if ($regionalId) {
        $centros = $centroDAO->obtenerPorRegional($regionalId);
    }
}

// Obtener evidencias según rol y filtros
$evidencias = $dao->obtenerEvidenciasConFiltros($rol, $usuarioId, $regionalId, $centroId);

// (Opcional) Log de depuración
// error_log("Rol: $rol, Usuario: $usuarioId, Regional: $regionalId, Centro: $centroId, Evidencias: " . count($evidencias));
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
    <style>
        .filtros {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        .filtros .form-group {
            margin: 0;
        }
        .filtros select, .filtros button {
            padding: 8px 15px;
        }
        .estado-badge.activa { background: #dcfce7; color: #16a34a; }
        .estado-badge.inactiva { background: #fee2e2; color: #dc2626; }
    </style>
</head>
<body>
    <div id="loader">
        <img src="../img/logo_sena_verde.png" alt="Logo SENA" id="loader-logo">
    </div>

    <?php include "../config/header.php"; ?>

    <main class="container" id="contenido-principal" style="display:none; opacity:0;">
        <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <div>
                <h1 class="page-title">
                    <i class="fas fa-file-alt"></i> Gestión de Evidencias
                </h1>
                <p class="page-subtitle">Listado de evidencias asignadas por ficha</p>
            </div>
            <?php if (esAdmin() || esInstructor()): ?>
            <a href="crud/crear_evidencia.php" class="btn-create">
                <i class="fas fa-plus"></i> Crear Evidencia
            </a>
            <?php endif; ?>
        </div>

        <!-- Filtros solo visibles para admin -->
        <?php if (esAdmin()): ?>
        <div class="filtros">
            <div class="form-group">
                <label for="regional_id">Regional</label>
                <select name="regional_id" id="regional_id" class="form-control">
                    <option value="">Todas las regionales</option>
                    <?php foreach ($regionales as $reg): ?>
                        <option value="<?= $reg['REGIONAL_ID'] ?>" <?= ($regionalId == $reg['REGIONAL_ID']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($reg['NOMBRE']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="centro_id">Centro</label>
                <select name="centro_id" id="centro_id" class="form-control">
                    <option value="">Todos los centros</option>
                    <?php foreach ($centros as $cent): ?>
                        <option value="<?= $cent['CENTRO_ID'] ?>" <?= ($centroId == $cent['CENTRO_ID']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cent['NOMBRE']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <button type="button" id="btn-filtrar" class="btn-action">Filtrar</button>
                <button type="button" id="btn-limpiar" class="btn-cancel">Limpiar</button>
            </div>
        </div>
        <?php endif; ?>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>%</th>
                        <th>Fecha</th>
                        <th>Entrega</th>
                        <th>Ficha</th>
                        <th>Regional / Centro</th>
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
                        <td colspan="<?= esAdmin() ? 10 : 9 ?>" class="empty-state">
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
                                <?= htmlspecialchars($e['regional_nombre'] ?? '-') ?> /
                                <?= htmlspecialchars($e['centro_nombre'] ?? '-') ?>
                            </td>
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
                                    <a href="crud/editar_evidencia.php?id=<?= $e['evidencias_id'] ?>" class="btn-edit" title="Editar">
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
                    window.location.href = 'crud/eliminar_evidencia.php?id=' + id;
                }
            });
        }

        <?php if (esAdmin()): ?>
        document.getElementById('btn-filtrar')?.addEventListener('click', function() {
            const regional = document.getElementById('regional_id').value;
            const centro = document.getElementById('centro_id').value;
            let url = 'evidencias.php?';
            if (regional) url += 'regional_id=' + regional;
            if (centro) url += (regional ? '&' : '') + 'centro_id=' + centro;
            window.location.href = url;
        });
        document.getElementById('btn-limpiar')?.addEventListener('click', function() {
            window.location.href = 'evidencias.php';
        });
        <?php endif; ?>

        if (typeof initThemeToggle === 'function') {
            setTimeout(initThemeToggle, 100);
        }
    </script>
</body>
</html>