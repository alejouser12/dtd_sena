<?php
// mod/calificar_evidencia.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/EvidenciaDAO.php';

$dao = new EvidenciaDAO();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: evidencias.php');
    exit;
}

$evidencia = $dao->obtenerPorId($id);
if (!$evidencia) {
    header('Location: evidencias.php');
    exit;
}

$aprendices = $dao->obtenerAprendicesPorFicha($evidencia['ficha_id']);
$calificacionesGuardadas = $dao->obtenerCalificaciones($id);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calificar Evidencia: <?= htmlspecialchars($evidencia['nombre']) ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .tabla-calificaciones input[type="number"] {
            width: 80px;
            padding: 5px;
        }
        .tabla-calificaciones select {
            width: 120px;
            padding: 5px;
        }
        .estado-badge.aprobado { background: #dcfce7; color: #16a34a; }
        .estado-badge.desaprobado { background: #fee2e2; color: #dc2626; }
    </style>
</head>
<body>
    <div id="loader">
        <img src="../img/logo_sena_verde.png" alt="Logo SENA" id="loader-logo">
    </div>

    <?php include "../config/header.php"; ?>

    <main class="container" id="contenido-principal" style="display:none; opacity:0;">
        <div style="margin-bottom: 20px;">
            <a href="evidencias.php" class="btn-view-all">
                <i class="fas fa-arrow-left"></i> Volver a evidencias
            </a>
        </div>

        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-star"></i> Calificar Evidencia
            </h1>
            <p class="page-subtitle">
                Evidencia: <strong><?= htmlspecialchars($evidencia['nombre']) ?></strong> - 
                Ficha: <?= htmlspecialchars($evidencia['CODIGO_FICHA']) ?>
            </p>
        </div>

        <div class="content-card">
            <div class="card-header">
                <h2 class="card-title">Aprendices de la ficha</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="guardar_calificaciones.php" id="form-calificaciones">
                    <input type="hidden" name="evidencia_id" value="<?= $id ?>">
                    <div class="table-container">
                        <table class="data-table tabla-calificaciones">
                            <thead>
                                <tr>
                                    <th>Aprendiz</th>
                                    <th>Documento</th>
                                    <th>Calificación (opcional)</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($aprendices)): ?>
                                <tr>
                                    <td colspan="4" class="empty-state">
                                        <i class="fas fa-users"></i> No hay aprendices en esta ficha
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($aprendices as $a): 
                                        $guardado = $calificacionesGuardadas[$a['APRENDIZ_ID']] ?? null;
                                        $estado = $guardado ? $guardado['estado_aprobacion'] : '';
                                        $calif = $guardado ? $guardado['calificacion'] : '';
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($a['NOMBRES'] . ' ' . $a['APELLIDOS']) ?></td>
                                        <td><?= htmlspecialchars($a['NUMERO_DOCUMENTO']) ?></td>
                                        <td>
                                            <input type="number" step="0.1" min="0" max="5" 
                                                   name="calificacion[<?= $a['APRENDIZ_ID'] ?>]" 
                                                   value="<?= htmlspecialchars($calif) ?>"
                                                   placeholder="0-5">
                                        </td>
                                        <td>
                                            <select name="estado[<?= $a['APRENDIZ_ID'] ?>]" class="form-control">
                                                <option value="">Seleccionar</option>
                                                <option value="aprobado" <?= $estado == 'aprobado' ? 'selected' : '' ?>>Aprobado</option>
                                                <option value="desaprobado" <?= $estado == 'desaprobado' ? 'selected' : '' ?>>Desaprobado</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="form-actions" style="margin-top: 20px;">
                        <a href="evidencias.php" class="btn-cancel">Cancelar</a>
                        <button type="submit" class="btn-action">Guardar Calificaciones</button>
                    </div>
                </form>
            </div>
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
</body>
</html>