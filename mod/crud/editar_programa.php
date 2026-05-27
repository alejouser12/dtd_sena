<?php
session_start();
require_once __DIR__ . '/../../config/auth.php';
if (!esAdmin()) { header('Location: ../programas.php'); exit; }
require_once __DIR__ . '/../../conexion/ProgramaDAO.php';
require_once __DIR__ . '/../../conexion/CentroDAO.php';
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: ../programas.php'); exit; }
$pr = (new ProgramaDAO())->obtenerPorId($id);
if (!$pr) { header('Location: ../programas.php'); exit; }
$centros = (new CentroDAO())->obtenerTodos();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Programa</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .form-container { max-width: 700px; margin: 0 auto; }
        .form-header { background: linear-gradient(135deg, var(--color-verde-1), var(--color-verde-2)); padding: 20px; border-radius: 20px 20px 0 0; color: white; text-align: center; }
        .form-header i { font-size: 50px; margin-bottom: 10px; }
        .card-body { padding: 30px; }
        .form-actions { display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px; }
    </style>
</head>
<body>
    <div id="loader"><img src="../../img/logo_sena_verde.png" alt="" id="loader-logo"></div>
    <?php include __DIR__ . '/../../config/header.php'; ?>
    <main class="container" id="contenido-principal" style="display:none;opacity:0">
        <div class="form-container">
            <div class="form-header">
                <i class="fas fa-edit"></i>
                <h2>Editar Programa</h2>
                <p>Modifique los datos del programa</p>
            </div>
            <div class="content-card" style="border-radius: 0 0 20px 20px; margin-top:0;">
                <div class="card-body">
                    <form id="form-programa">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <div class="form-group">
                            <label><i class="fas fa-building"></i> Centro *</label>
                            <select name="centro_id" class="form-control" required>
                                <?php foreach ($centros as $c): ?>
                                    <option value="<?= $c['CENTRO_ID'] ?>" <?= ($pr['CENTRO_ID'] == $c['CENTRO_ID']) ? 'selected' : '' ?>><?= htmlspecialchars($c['NOMBRE']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-tag"></i> Nombre *</label>
                            <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($pr['NOMBRE']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-graduation-cap"></i> Nivel *</label>
                            <input type="text" name="nivel_formacion" class="form-control" value="<?= htmlspecialchars($pr['NIVEL_FORMACION']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-calendar-alt"></i> Duración (meses)</label>
                            <input type="number" name="duracion_meses" class="form-control" value="<?= (int)$pr['DURACION_MESES'] ?>" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-toggle-on"></i> Estado</label>
                            <select name="estado" class="form-control">
                                <option value="Activo" <?= ($pr['ESTADO'] == 'Activo') ? 'selected' : '' ?>>Activo</option>
                                <option value="Inactivo" <?= ($pr['ESTADO'] == 'Inactivo') ? 'selected' : '' ?>>Inactivo</option>
                            </select>
                        </div>
                        <div class="form-actions">
                            <a href="../programa_detalle.php?id=<?= $id ?>" class="btn-cancel"><i class="fas fa-times"></i> Cancelar</a>
                            <button type="submit" class="btn-action"><i class="fas fa-save"></i> Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
    <?php include __DIR__ . '/../../config/footer.php'; ?>
    <script src="../../js/tema.js"></script><script src="../../js/loader.js"></script>
    <script src="../../js/panel_menu.js"></script><script src="../../js/dropdowns.js"></script>
    <script src="../../js/profile_menu.js"></script><script src="../../js/sweetalerts.js"></script><script src="../../js/menu.js"></script>
    <script>
    document.getElementById('form-programa').addEventListener('submit', function(e) {
        e.preventDefault();
        fetch('guardar_programa.php', { method: 'POST', body: new FormData(this) })
            .then(r => r.json()).then(d => {
                if (d.success) Swal.fire({ icon: 'success', title: 'Actualizado', text: 'Redirigiendo...', timer: 1500, showConfirmButton: false })
                    .then(() => window.location.href = '../programa_detalle.php?id=<?= $id ?>');
                else Swal.fire({ icon: 'error', title: 'Error', text: d.message || 'No se pudo actualizar' });
            });
    });
    </script>
</body>
</html>