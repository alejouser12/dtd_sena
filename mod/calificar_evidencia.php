<?php
session_start();
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../conexion/EvidenciaDAO.php';
require_once __DIR__ . '/../conexion/instructorDAO.php';

if (!esAdmin() && !esInstructor()) {
    header('Location: evidencias.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: evidencias.php?error=ID inválido');
    exit;
}

$dao = new EvidenciaDAO();
$evidencia = $dao->obtenerPorId($id);
if (!$evidencia) {
    header('Location: evidencias.php?error=Evidencia no encontrada');
    exit;
}

// PERMISO: Si es instructor, verificar que tenga la ficha asignada
if (!esAdmin()) {
    if (!esInstructor()) {
        header('Location: evidencias.php?error=No tienes permiso');
        exit;
    }
    
    $instructorId = (int)($_SESSION['usuario_ref_id'] ?? 0);
    
    // Si no tiene usuario_ref_id, buscar por email
    if ($instructorId <= 0) {
        $email = $_SESSION['usuario_email'] ?? '';
        if (!empty($email)) {
            $instDAO = new InstructorDAO();
            $c = $instDAO->buscarPorColumna('EMAIL', $email);
            if (!empty($c) && isset($c[0]['INSTRUCTOR_ID'])) {
                $instructorId = (int)$c[0]['INSTRUCTOR_ID'];
            }
        }
    }

    if ($instructorId <= 0) {
        header('Location: evidencias.php?error=No se pudo identificar tu ID de instructor');
        exit;
    }

    $fichaId = $evidencia['ficha_id'];
    // Verificar que la ficha está asignada al instructor
    $instDAO = new InstructorDAO();
    $fichasAsignadas = $instDAO->obtenerFichasIds($instructorId);
    
    if (!in_array($fichaId, $fichasAsignadas)) {
        header('Location: evidencias.php?error=Esta evidencia no pertenece a ninguna de tus fichas');
        exit;
    }
}

$aprendices = $dao->obtenerAprendicesPorFicha($evidencia['ficha_id']);
$calificacionesGuardadas = $dao->obtenerCalificaciones($id);
$califMap = [];
foreach ($calificacionesGuardadas as $c) {
    $califMap[$c['APRENDIZ_ID']] = $c;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Calificar: <?= htmlspecialchars($evidencia['nombre']) ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .tabla-calificaciones input[type="number"] { width: 100px; padding: 8px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 14px; }
        .tabla-calificaciones input[type="number"]:focus { outline: none; border-color: var(--color-verde-1); box-shadow: 0 0 0 3px rgba(57, 169, 0, 0.1); }
        .estado-aprobado { background: #dcfce7; color: #16a34a; padding: 4px 12px; border-radius: 20px; font-weight: 600; display: inline-block; font-size: 13px; }
        .estado-desaprobado { background: #fee2e2; color: #dc2626; padding: 4px 12px; border-radius: 20px; font-weight: 600; display: inline-block; font-size: 13px; }
        .estado-sin-calificar { background: #f3f4f6; color: #6b7280; padding: 4px 12px; border-radius: 20px; font-weight: 600; display: inline-block; font-size: 13px; }
    </style>
</head>
<body>
    <div id="loader"><img src="../img/logo_sena_verde.png" alt="Logo SENA" id="loader-logo"></div>
    <?php include "../config/header.php"; ?>
    <main class="container" id="contenido-principal" style="display:none; opacity:0;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
            <div>
                <h1 style="margin: 0 0 5px 0; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-star"></i> Calificar: <?= htmlspecialchars($evidencia['nombre']) ?>
                </h1>
            </div>
            <a href="evidencias.php" class="btn-view-all"><i class="fas fa-arrow-left"></i> Volver</a>
        </div>

        <div class="content-card" style="background: var(--color-blanco); border-radius: 12px; padding: 20px; margin-bottom: 25px; border: 1px solid var(--border-color); box-shadow: var(--shadow-card);">
            <h3 style="margin-top: 0; color: var(--color-texto); border-bottom: 2px solid var(--color-verde-1); padding-bottom: 10px;">
                <i class="fas fa-info-circle"></i> Información de la Evidencia
            </h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-top: 15px;">
                <div>
                    <label style="color: var(--color-texto-secundario); font-size: 12px; font-weight: 600;">Ficha</label>
                    <p style="margin: 5px 0 0 0; font-size: 16px; font-weight: 600; color: var(--color-texto);"><?= htmlspecialchars($evidencia['CODIGO_FICHA']) ?></p>
                </div>
                <div>
                    <label style="color: var(--color-texto-secundario); font-size: 12px; font-weight: 600;">Porcentaje</label>
                    <p style="margin: 5px 0 0 0; font-size: 16px; font-weight: 600; color: var(--color-texto);"><?= $evidencia['porcentaje'] ? number_format($evidencia['porcentaje'],2).'%' : '-' ?></p>
                </div>
                <div>
                    <label style="color: var(--color-texto-secundario); font-size: 12px; font-weight: 600;">Fecha Límite</label>
                    <p style="margin: 5px 0 0 0; font-size: 16px; font-weight: 600; color: var(--color-texto);"><?= date('d/m/Y', strtotime($evidencia['tiempo_entrega'])) ?></p>
                </div>
            </div>
        </div>

        <form method="POST" action="guardar_calificaciones.php">
            <input type="hidden" name="evidencia_id" value="<?= $id ?>">
            <div class="content-card" style="background: var(--color-blanco); border-radius: 12px; padding: 20px; border: 1px solid var(--border-color); box-shadow: var(--shadow-card);">
                <h3 style="margin-top: 0; color: var(--color-texto); border-bottom: 2px solid var(--color-verde-1); padding-bottom: 10px;">
                    <i class="fas fa-users"></i> Calificaciones
                </h3>
                <div class="table-container" style="margin-top: 15px;">
                    <table class="data-table tabla-calificaciones">
                        <thead>
                            <tr>
                                <th>Aprendiz</th>
                                <th>Documento</th>
                                <th>Nota (0-5)</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($aprendices as $a):
                            $guardado = $califMap[$a['APRENDIZ_ID']] ?? null;
                            $nota = $guardado ? $guardado['calificacion'] : '';
                            $estado = $guardado ? $guardado['estado_aprobacion'] : '';
                            $clase = ($estado === 'aprobado') ? 'estado-aprobado' : (($estado === 'desaprobado') ? 'estado-desaprobado' : 'estado-sin-calificar');
                            $texto = ($estado === 'aprobado') ? 'Aprobado' : (($estado === 'desaprobado') ? 'Desaprobado' : 'Sin calificar');
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($a['NOMBRES'].' '.$a['APELLIDOS']) ?></td>
                            <td><?= htmlspecialchars($a['NUMERO_DOCUMENTO']) ?></td>
                            <td><input type="number" step="0.1" min="0" max="5" name="calificacion[<?= $a['APRENDIZ_ID'] ?>]" value="<?= htmlspecialchars($nota) ?>" class="form-control"></td>
                            <td><span class="<?= $clase ?>"><?= $texto ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="form-actions" style="margin-top: 20px;">
                <button type="submit" class="btn-action">Guardar calificaciones</button>
                <a href="evidencias.php" class="btn-cancel">Cancelar</a>
            </div>
            </div>
        </form>
    </main>
    <?php include "../config/footer.php"; ?>
    <script>
        document.querySelectorAll('input[name^="calificacion"]').forEach(input => {
            input.addEventListener('input', function() {
                let nota = parseFloat(this.value);
                let span = this.closest('tr').querySelector('td:last-child span');
                if (isNaN(nota)) { span.textContent = 'Sin calificar'; span.className = 'estado-sin-calificar'; }
                else if (nota >= 3) { span.textContent = 'Aprobado'; span.className = 'estado-aprobado'; }
                else { span.textContent = 'Desaprobado'; span.className = 'estado-desaprobado'; }
            });
        });
        if (typeof initThemeToggle === 'function') setTimeout(initThemeToggle, 100);
    </script>
    <script src="../js/tema.js"></script>
    <script src="../js/loader.js"></script>
    <script src="../js/panel_menu.js"></script>
    <script src="../js/dropdowns.js"></script>
    <script src="../js/profile_menu.js"></script>
    <script src="../js/sweetalerts.js"></script>
    <script src="../js/menu.js"></script>
</body>
</html>