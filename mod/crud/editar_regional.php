<?php
session_start();
require_once __DIR__ . '/../../config/auth.php';
if (!esAdmin()) { header('Location: ../regionales.php'); exit; }
require_once __DIR__ . '/../../conexion/RegionalDAO.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header('Location: ../regionales.php'); exit; }
$dao = new RegionalDAO();
$regional = $dao->obtenerPorId($id);
if (!$regional) { header('Location: ../regionales.php'); exit; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar regional</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div id="loader"><img src="../../img/logo_sena_verde.png" alt="" id="loader-logo"></div>
    <?php include __DIR__ . '/../../config/header.php'; ?>
    <main class="container" id="contenido-principal" style="display:none;opacity:0">
        <a href="../regional_detalle.php?id=<?= $id ?>" class="btn-view-all"><i class="fas fa-arrow-left"></i> Volver</a>
        <div class="content-card" style="margin-top:20px">
            <div class="card-header"><h2><i class="fas fa-edit"></i> Editar regional</h2></div>
            <div class="card-body">
                <form id="form-regional">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <div class="form-group"><label>Nombre *</label><input class="form-control" name="nombre" value="<?= htmlspecialchars($regional['NOMBRE']) ?>" required></div>
                    <div class="form-group"><label>Código</label><input class="form-control" name="codigo" value="<?= htmlspecialchars($regional['CODIGO'] ?? '') ?>"></div>
                    <div class="form-group"><label>Ciudad</label><input class="form-control" name="ciudad" value="<?= htmlspecialchars($regional['CIUDAD'] ?? '') ?>"></div>
                    <div class="form-group"><label>Dirección</label><input class="form-control" name="direccion" value="<?= htmlspecialchars($regional['DIRECCION'] ?? '') ?>"></div>
                    <div class="form-group"><label>Teléfono</label><input class="form-control" name="telefono" value="<?= htmlspecialchars($regional['TELEFONO'] ?? '') ?>"></div>
                    <div class="form-group"><label>Estado *</label>
                        <select class="form-control" name="estado" required>
                            <option value="Activa" <?= ($regional['ESTADO'] ?? '') === 'Activa' ? 'selected' : '' ?>>Activa</option>
                            <option value="Inactiva" <?= ($regional['ESTADO'] ?? '') === 'Inactiva' ? 'selected' : '' ?>>Inactiva</option>
                        </select>
                    </div>
                    <div class="form-actions">
                        <a href="../regional_detalle.php?id=<?= $id ?>" class="btn-cancel">Cancelar</a>
                        <button type="submit" class="btn-action">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <?php include __DIR__ . '/../../config/footer.php'; ?>
    <script src="../../js/tema.js"></script><script src="../../js/loader.js"></script>
    <script src="../../js/profile_menu.js"></script><script src="../../js/menu.js"></script>
    <script>
    document.getElementById('form-regional').addEventListener('submit', function(e) {
        e.preventDefault();
        fetch('guardar_regional.php', { method: 'POST', body: new FormData(this) })
            .then(r => r.json()).then(d => {
                if (d.success) Swal.fire({ icon: 'success', title: 'Guardado', timer: 1000, showConfirmButton: false })
                    .then(() => location.href = '../regional_detalle.php?id=<?= $id ?>');
                else Swal.fire({ icon: 'error', text: d.message || '' });
            });
    });
    </script>
</body>
</html>
