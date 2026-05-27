<?php
// mod/calificar_evidencia.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/auth.php';
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

// Validar permisos: instructor solo puede calificar si la ficha le pertenece
if (!esAdmin()) {
    $instructorId = $_SESSION['usuario_id'] ?? null;
    if (!$instructorId || !$dao->instructorPuedeCalificar($instructorId, $id)) {
        header('Location: evidencias.php?error=sin_permiso');
        exit;
    }
}

$aprendices = $dao->obtenerAprendicesPorFicha($evidencia['ficha_id']);
$calificacionesGuardadas = $dao->obtenerCalificaciones($id);
// Indexar por aprendiz_id
$califMap = [];
foreach ($calificacionesGuardadas as $c) {
    $califMap[$c['APRENDIZ_ID']] = $c;
}
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
            width: 100px;
            padding: 8px;
        }
        .estado-aprobado {
            background: #dcfce7;
            color: #16a34a;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-block;
        }
        .estado-desaprobado {
            background: #fee2e2;
            color: #dc2626;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-block;
        }
        .estado-sin-calificar {
            background: #f3f4f6;
            color: #6b7280;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-block;
        }
        .info-evidencia {
            background: var(--color-gris-fondo);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .info-evidencia p {
            margin: 5px 0;
        }
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
            <div class="info-evidencia">
                <p><strong>Evidencia:</strong> <?= htmlspecialchars($evidencia['nombre']) ?></p>
                <p><strong>Ficha:</strong> <?= htmlspecialchars($evidencia['CODIGO_FICHA']) ?></p>
                <p><strong>Porcentaje:</strong> <?= $evidencia['porcentaje'] ? number_format($evidencia['porcentaje'], 2).'%' : '-' ?></p>
                <p><strong>Fecha límite:</strong> <?= date('d/m/Y', strtotime($evidencia['tiempo_entrega'])) ?></p>
            </div>
        </div>

        <div class="content-card">
            <div class="card-header">
                <h2 class="card-title">Aprendices de la ficha</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="guardar_calificaciones.php">
                    <input type="hidden" name="evidencia_id" value="<?= $id ?>">
                    <div class="table-container">
                        <table class="data-table tabla-calificaciones">
                            <thead>
                                <tr>
                                    <th>Aprendiz</th>
                                    <th>Documento</th>
                                    <th>Calificación (0-5)</th>
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
                                        $guardado = $califMap[$a['APRENDIZ_ID']] ?? null;
                                        $calif = $guardado ? $guardado['calificacion'] : '';
                                        $estado = $guardado ? $guardado['estado_aprobacion'] : '';
                                        $estadoTexto = '';
                                        $estadoClase = '';
                                        if ($estado === 'aprobado') {
                                            $estadoTexto = 'Aprobado';
                                            $estadoClase = 'estado-aprobado';
                                        } elseif ($estado === 'desaprobado') {
                                            $estadoTexto = 'Desaprobado';
                                            $estadoClase = 'estado-desaprobado';
                                        } else {
                                            $estadoTexto = 'Sin calificar';
                                            $estadoClase = 'estado-sin-calificar';
                                        }
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($a['NOMBRES'] . ' ' . $a['APELLIDOS']) ?></td>
                                        <td><?= htmlspecialchars($a['NUMERO_DOCUMENTO']) ?></td>
                                        <td>
                                            <input type="number" step="0.1" min="0" max="5" 
                                                   name="calificacion[<?= $a['APRENDIZ_ID'] ?>]" 
                                                   value="<?= htmlspecialchars($calif) ?>"
                                                   placeholder="0-5"
                                                   class="form-control">
                                        </td>
                                        <td>
                                            <span id="estado-<?= $a['APRENDIZ_ID'] ?>" class="<?= $estadoClase ?>">
                                                <?= $estadoTexto ?>
                                            </span>
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

    <script>
        // Actualizar estado en tiempo real según la calificación ingresada
        const inputs = document.querySelectorAll('input[name^="calificacion"]');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                const nota = parseFloat(this.value);
                const span = this.closest('tr').querySelector('td:last-child span');
                if (isNaN(nota)) {
                    span.textContent = 'Sin calificar';
                    span.className = 'estado-sin-calificar';
                } else if (nota >= 3) {
                    span.textContent = 'Aprobado';
                    span.className = 'estado-aprobado';
                } else {
                    span.textContent = 'Desaprobado';
                    span.className = 'estado-desaprobado';
                }
            });
        });

        if (typeof initThemeToggle === 'function') {
            setTimeout(initThemeToggle, 100);
        }
    </script>
</body>
</html>