<?php
session_start();
require_once __DIR__ . '/../../config/auth.php';
if (!esAdmin()) { header('Location: ../programas.php'); exit; }
require_once __DIR__ . '/../../conexion/ProgramaDAO.php';
require_once __DIR__ . '/../../conexion/CentroDAO.php';
$programas = (new ProgramaDAO())->obtenerProgramas();
$centros = (new CentroDAO())->obtenerTodos();
$preProg = isset($_GET['programa_id']) ? (int)$_GET['programa_id'] : 0;
$preCent = isset($_GET['centro_id']) ? (int)$_GET['centro_id'] : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nueva ficha</title>
    <link rel="stylesheet" href="../../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div id="loader"><img src="../../img/logo_sena_verde.png" alt="" id="loader-logo"></div>
    <?php include __DIR__ . '/../../config/header.php'; ?>
    <main class="container" id="contenido-principal" style="display:none;opacity:0">
        <a href="../programas.php" class="btn-view-all">Volver</a>
        <div class="content-card" style="margin-top:20px">
            <div class="card-header"><h2>Nueva ficha</h2></div>
            <div class="card-body">
                <form id="f">
                    <div class="form-group"><label>Código ficha *</label><input class="form-control" name="codigo_ficha" required></div>
                    <div class="form-group"><label>Programa *</label>
                        <select class="form-control" name="programa_id" required>
                            <option value="">—</option>
                            <?php foreach ($programas as $p): ?>
                            <option value="<?= (int)$p['PROGRAMA_ID'] ?>" <?= $preProg === (int)$p['PROGRAMA_ID'] ? 'selected' : '' ?>><?= htmlspecialchars($p['NOMBRE']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Centro *</label>
                        <select class="form-control" name="centro_id" required>
                            <option value="">—</option>
                            <?php foreach ($centros as $c): ?>
                            <option value="<?= (int)$c['CENTRO_ID'] ?>" <?= $preCent === (int)$c['CENTRO_ID'] ? 'selected' : '' ?>><?= htmlspecialchars($c['NOMBRE']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Inicio *</label><input type="date" class="form-control" name="fecha_inicio" required></div>
                    <div class="form-group"><label>Fin *</label><input type="date" class="form-control" name="fecha_fin" required></div>
                    <div class="form-group"><label>Estado</label>
                        <select class="form-control" name="estado"><option value="Activa">Activa</option><option value="Inactiva">Inactiva</option></select>
                    </div>
                    <button type="submit" class="btn-action">Guardar</button>
                </form>
            </div>
        </div>
    </main>
    <?php include __DIR__ . '/../../config/footer.php'; ?>
    <script src="../../js/tema.js"></script><script src="../../js/loader.js"></script>
    <script src="../../js/profile_menu.js"></script><script src="../../js/menu.js"></script>
    <script>
    document.getElementById('f').addEventListener('submit', function(e) {
        e.preventDefault();
        fetch('guardar_ficha.php', { method: 'POST', body: new FormData(this) })
            .then(r => r.json()).then(d => {
                if (d.success) location.href = '../ficha_detalle.php?id=' + (d.id || '');
                else Swal.fire({ icon: 'error', text: d.message || 'Error' });
            });
    });
    </script>
</body>
</html>
