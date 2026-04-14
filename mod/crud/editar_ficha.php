<?php
session_start();
require_once __DIR__ . '/../../config/auth.php';
if (!esAdmin()) { header('Location: ../programas.php'); exit; }
require_once __DIR__ . '/../../conexion/FichaDAO.php';
require_once __DIR__ . '/../../conexion/ProgramaDAO.php';
require_once __DIR__ . '/../../conexion/CentroDAO.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header('Location: ../programas.php'); exit; }
$f = (new FichaDAO())->obtenerPorId($id);
if (!$f) { header('Location: ../programas.php'); exit; }
$programas = (new ProgramaDAO())->obtenerProgramas();
$centros = (new CentroDAO())->obtenerTodos();
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Editar ficha</title><link rel="stylesheet" href="../../css/style.css"><script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script></head>
<body>
<div id="loader"><img src="../../img/logo_sena_verde.png" alt="" id="loader-logo"></div>
<?php include __DIR__ . '/../../config/header.php'; ?>
<main class="container" id="contenido-principal" style="display:none;opacity:0">
<a href="../ficha_detalle.php?id=<?= $id ?>" class="btn-view-all">Volver</a>
<div class="content-card" style="margin-top:20px"><div class="card-header"><h2>Editar ficha</h2></div>
<div class="card-body">
<form id="f"><input type="hidden" name="id" value="<?= $id ?>">
<div class="form-group"><label>Código</label><input class="form-control" name="codigo_ficha" value="<?= htmlspecialchars($f['CODIGO_FICHA']) ?>" required></div>
<div class="form-group"><label>Programa</label><select class="form-control" name="programa_id" required><?php foreach ($programas as $p): ?>
<option value="<?= (int)$p['PROGRAMA_ID'] ?>" <?= (int)$f['PROGRAMA_ID'] === (int)$p['PROGRAMA_ID'] ? 'selected' : '' ?>><?= htmlspecialchars($p['NOMBRE']) ?></option><?php endforeach; ?></select></div>
<div class="form-group"><label>Centro</label><select class="form-control" name="centro_id" required><?php foreach ($centros as $c): ?>
<option value="<?= (int)$c['CENTRO_ID'] ?>" <?= (int)($f['CENTRO_ID'] ?? 0) === (int)$c['CENTRO_ID'] ? 'selected' : '' ?>><?= htmlspecialchars($c['NOMBRE']) ?></option><?php endforeach; ?></select></div>
<div class="form-group"><label>Inicio</label><input type="date" class="form-control" name="fecha_inicio" value="<?= htmlspecialchars(substr($f['FECHA_INICIO'], 0, 10)) ?>" required></div>
<div class="form-group"><label>Fin</label><input type="date" class="form-control" name="fecha_fin" value="<?= htmlspecialchars(substr($f['FECHA_FIN'], 0, 10)) ?>" required></div>
<div class="form-group"><label>Estado</label><select class="form-control" name="estado"><option value="Activa" <?= ($f['ESTADO'] ?? '') === 'Activa' ? 'selected' : '' ?>>Activa</option><option value="Inactiva" <?= ($f['ESTADO'] ?? '') === 'Inactiva' ? 'selected' : '' ?>>Inactiva</option></select></div>
<button type="submit" class="btn-action">Guardar</button></form></div></div></main>
<?php include __DIR__ . '/../../config/footer.php'; ?>
<script src="../../js/tema.js"></script><script src="../../js/loader.js"></script><script src="../../js/profile_menu.js"></script><script src="../../js/menu.js"></script>
<script>
document.getElementById('f').addEventListener('submit', function(e) {
    e.preventDefault();
    fetch('guardar_ficha.php', { method: 'POST', body: new FormData(this) }).then(r => r.json()).then(d => {
        if (d.success) location.href = '../ficha_detalle.php?id=<?= $id ?>';
        else Swal.fire({ icon: 'error', text: d.message || '' });
    });
});
</script></body></html>
