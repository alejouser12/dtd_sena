<?php
session_start();
require_once __DIR__ . '/../../config/auth.php';
if (!esAdmin()) { header('Location: ../regionales.php'); exit; }
require_once __DIR__ . '/../../conexion/CentroDAO.php';
require_once __DIR__ . '/../../conexion/RegionalDAO.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header('Location: ../regionales.php'); exit; }
$centroDAO = new CentroDAO();
$centro = $centroDAO->obtenerPorId($id);
if (!$centro) { header('Location: ../regionales.php'); exit; }
$regionalDAO = new RegionalDAO();
$regionales = $regionalDAO->obtenerTodas();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar centro</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div id="loader"><img src="../../img/logo_sena_verde.png" alt="" id="loader-logo"></div>
    <?php include __DIR__ . '/../../config/header.php'; ?>
    <main class="container" id="contenido-principal" style="display:none;opacity:0">
        <a href="../centro_detalle.php?id=<?= $id ?>" class="btn-view-all"><i class="fas fa-arrow-left"></i> Volver</a>
        <div class="content-card" style="margin-top:20px">
            <div class="card-header"><h2>Editar centro</h2></div>
            <div class="card-body">
                <form id="form-centro">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <div class="form-group"><label>Regional *</label>
                        <select class="form-control" name="regional_id" required>
                            <?php foreach ($regionales as $r): ?>
                            <option value="<?= (int)$r['REGIONAL_ID'] ?>" <?= (int)$centro['REGIONAL_ID'] === (int)$r['REGIONAL_ID'] ? 'selected' : '' ?>><?= htmlspecialchars($r['NOMBRE']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Nombre *</label><input class="form-control" name="nombre" value="<?= htmlspecialchars($centro['NOMBRE']) ?>" required></div>
                    <div class="form-group"><label>Código</label><input class="form-control" name="codigo" value="<?= htmlspecialchars($centro['CODIGO'] ?? '') ?>"></div>
                    <div class="form-group"><label>Dirección</label><input class="form-control" name="direccion" value="<?= htmlspecialchars($centro['DIRECCION'] ?? '') ?>"></div>
                    <div class="form-group"><label>Teléfono</label><input class="form-control" name="telefono" value="<?= htmlspecialchars($centro['TELEFONO'] ?? '') ?>"></div>
                    <div class="form-group"><label>Estado *</label>
                        <select class="form-control" name="estado" required>
                            <option value="Activo" <?= ($centro['ESTADO'] ?? '') === 'Activo' ? 'selected' : '' ?>>Activo</option>
                            <option value="Inactivo" <?= ($centro['ESTADO'] ?? '') === 'Inactivo' ? 'selected' : '' ?>>Inactivo</option>
                        </select>
                    </div>
                    <div class="form-actions">
                        <a href="../centro_detalle.php?id=<?= $id ?>" class="btn-cancel">Cancelar</a>
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
                if (d.success) Swal.fire({ icon: 'success', title: 'Guardado', timer: 1000, showConfirmButton: false })
                    .then(() => location.href = '../centro_detalle.php?id=<?= $id ?>');
                else Swal.fire({ icon: 'error', text: d.message || '' });
            });
    });
    </script>
</body>
</html>
