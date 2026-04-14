<?php
session_start();
require_once __DIR__ . '/../../config/auth.php';
if (!esAdmin()) { header('Location: ../regionales.php'); exit; }
require_once __DIR__ . '/../../conexion/RegionalDAO.php';
$regionalDAO = new RegionalDAO();
$regionales = $regionalDAO->obtenerTodas();
$regionalPre = isset($_GET['regional_id']) ? (int)$_GET['regional_id'] : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo centro</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div id="loader"><img src="../../img/logo_sena_verde.png" alt="" id="loader-logo"></div>
    <?php include __DIR__ . '/../../config/header.php'; ?>
    <main class="container" id="contenido-principal" style="display:none;opacity:0">
        <a href="<?= $regionalPre ? '../regional_detalle.php?id=' . $regionalPre : '../regionales.php' ?>" class="btn-view-all"><i class="fas fa-arrow-left"></i> Volver</a>
        <div class="content-card" style="margin-top:20px">
            <div class="card-header"><h2><i class="fas fa-plus"></i> Nuevo centro</h2></div>
            <div class="card-body">
                <form id="form-centro">
                    <div class="form-group"><label>Regional *</label>
                        <select class="form-control" name="regional_id" id="regional_id" required>
                            <option value="">—</option>
                            <?php foreach ($regionales as $r): ?>
                            <option value="<?= (int)$r['REGIONAL_ID'] ?>" <?= $regionalPre === (int)$r['REGIONAL_ID'] ? 'selected' : '' ?>><?= htmlspecialchars($r['NOMBRE']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Nombre *</label><input class="form-control" name="nombre" required></div>
                    <div class="form-group"><label>Código</label><input class="form-control" name="codigo"></div>
                    <div class="form-group"><label>Dirección</label><input class="form-control" name="direccion"></div>
                    <div class="form-group"><label>Teléfono</label><input class="form-control" name="telefono"></div>
                    <div class="form-group"><label>Estado *</label>
                        <select class="form-control" name="estado" required><option value="Activo">Activo</option><option value="Inactivo">Inactivo</option></select>
                    </div>
                    <div class="form-actions">
                        <a href="<?= $regionalPre ? '../regional_detalle.php?id=' . $regionalPre : '../regionales.php' ?>" class="btn-cancel">Cancelar</a>
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
    document.getElementById('form-centro').addEventListener('submit', function(e) {
        e.preventDefault();
        fetch('guardar_centro.php', { method: 'POST', body: new FormData(this) })
            .then(r => r.json()).then(d => {
                if (d.success) {
                    const rid = document.getElementById('regional_id').value;
                    Swal.fire({ icon: 'success', title: 'Centro creado', timer: 1000, showConfirmButton: false })
                        .then(() => { location.href = rid ? '../regional_detalle.php?id=' + encodeURIComponent(rid) : '../regionales.php'; });
                } else Swal.fire({ icon: 'error', text: d.message || '' });
            });
    });
    </script>
</body>
</html>
