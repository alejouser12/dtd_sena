<?php
session_start();
require_once __DIR__ . '/../../config/auth.php';
// Instructores y administradores pueden editar evidencias
if (!esAdmin() && !esInstructor()) { 
    header('Location: ../evidencias.php'); 
    exit; 
}
require_once __DIR__ . '/../../conexion/EvidenciaDAO.php';
require_once __DIR__ . '/../../conexion/FichaDAO.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { 
    header('Location: ../evidencias.php'); 
    exit; 
}
$evidenciaDAO = new EvidenciaDAO();
$ev = $evidenciaDAO->obtenerPorId($id);
if (!$ev) { 
    header('Location: ../evidencias.php'); 
    exit; 
}

$fichas = (new FichaDAO())->obtenerTodas();
$tipos = ['Taller', 'Examen', 'Proyecto', 'Tarea', 'Foro', 'Otro'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Evidencia - DTD SENA</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .form-container {
            max-width: 700px;
            margin: 0 auto;
        }
        .form-header {
            background: linear-gradient(135deg, var(--color-verde-1), var(--color-verde-2));
            padding: 25px;
            border-radius: 20px 20px 0 0;
            color: white;
            text-align: center;
        }
        .form-header i {
            font-size: 50px;
            margin-bottom: 10px;
        }
        .form-header h2 {
            margin: 0;
            font-size: 28px;
        }
        .form-header p {
            margin: 5px 0 0;
            opacity: 0.9;
        }
        .card-body {
            padding: 30px;
        }
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
        }
        @media (max-width: 768px) {
            .form-actions {
                flex-direction: column;
            }
            .form-actions .btn-cancel,
            .form-actions .btn-action {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div id="loader"><img src="../../img/logo_sena_verde.png" alt="Logo SENA" id="loader-logo"></div>
    <?php include __DIR__ . '/../../config/header.php'; ?>
    <main class="container" id="contenido-principal" style="display:none; opacity:0;">
        <div class="form-container">
            <div class="form-header">
                <i class="fas fa-edit"></i>
                <h2>Editar Evidencia</h2>
                <p>Actualice los datos de la evidencia</p>
            </div>
            <div class="content-card" style="border-radius: 0 0 20px 20px; margin-top: 0;">
                <div class="card-body">
                    <form id="form-evidencia" method="POST" action="actualizar_evidencia.php">
                        <input type="hidden" name="evidencias_id" value="<?= $id ?>">
                        <div class="form-group">
                            <label><i class="fas fa-tag"></i> Nombre de la evidencia *</label>
                            <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($ev['nombre']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-list"></i> Tipo de evidencia *</label>
                            <select name="tipo_evidencia" class="form-control" required>
                                <option value="">Seleccione un tipo</option>
                                <?php foreach ($tipos as $tipo): ?>
                                    <option value="<?= $tipo ?>" <?= ($ev['tipo_evidencia'] ?? '') === $tipo ? 'selected' : '' ?>><?= $tipo ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-percent"></i> Porcentaje (%)</label>
                            <input type="number" step="0.01" name="porcentaje" class="form-control" value="<?= htmlspecialchars($ev['porcentaje'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-calendar-alt"></i> Fecha de asignación *</label>
                            <input type="date" name="fecha_evidencia" class="form-control" value="<?= htmlspecialchars(substr($ev['fecha_evidencia'], 0, 10)) ?>" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-hourglass-half"></i> Fecha de entrega *</label>
                            <input type="date" name="tiempo_entrega" class="form-control" value="<?= htmlspecialchars(substr($ev['tiempo_entrega'], 0, 10)) ?>" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-layer-group"></i> Ficha *</label>
                            <select name="ficha_id" class="form-control" required>
                                <option value="">Seleccione una ficha</option>
                                <?php foreach ($fichas as $f): ?>
                                    <option value="<?= $f['FICHA_ID'] ?>" <?= (int)$ev['ficha_id'] === $f['FICHA_ID'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($f['CODIGO_FICHA']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-actions">
                            <a href="../evidencias.php" class="btn-cancel"><i class="fas fa-times"></i> Cancelar</a>
                            <button type="submit" class="btn-action"><i class="fas fa-save"></i> Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
    <?php include __DIR__ . '/../../config/footer.php'; ?>
    <script src="../../js/tema.js"></script>
    <script src="../../js/loader.js"></script>
    <script src="../../js/panel_menu.js"></script>
    <script src="../../js/dropdowns.js"></script>
    <script src="../../js/profile_menu.js"></script>
    <script src="../../js/sweetalerts.js"></script>
    <script src="../../js/menu.js"></script>
    <script>
        document.getElementById('form-evidencia').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const nombre = this.querySelector('[name="nombre"]').value.trim();
            const tipo = this.querySelector('[name="tipo_evidencia"]').value;
            const ficha = this.querySelector('[name="ficha_id"]').value;
            const fechaAsignacion = this.querySelector('[name="fecha_evidencia"]').value;
            const fechaEntrega = this.querySelector('[name="tiempo_entrega"]').value;
            
            if (!nombre || !tipo || !ficha) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Complete los campos obligatorios (*)' });
                return;
            }
            
            if (!fechaAsignacion) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Seleccione la fecha de asignación' });
                return;
            }
            
            if (!fechaEntrega) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Seleccione la fecha de entrega' });
                return;
            }
            
            if (new Date(fechaEntrega) < new Date(fechaAsignacion)) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'La fecha de entrega no puede ser menor a la fecha de asignación' });
                return;
            }
            
            this.submit();
        });
        
        if (typeof initThemeToggle === 'function') setTimeout(initThemeToggle, 100);
    </script>
</body>
</html>