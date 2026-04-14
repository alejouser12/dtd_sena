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
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Editar programa</title><link rel="stylesheet" href="../../css/style.css"><script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script></head>
<body><div id="loader"><img src="../../img/logo_sena_verde.png" alt="" id="loader-logo"></div>
<?php include __DIR__ . '/../../config/header.php'; ?>
<main class="container" id="contenido-principal" style="display:none;opacity:0">
<a href="../programa_detalle.php?id=<?= $id ?>" class="btn-view-all">Volver</a>
<div class="content-card" style="margin-top:20px"><div class="card-header"><h2>Editar programa</h2></div><div class="card-body">
<form id="f"><input type="hidden" name="id" value="<?= $id ?>">
<div class="form-group"><label>Centro *</label><select name="centro_id" class="form-control" required><?php foreach ($centros as $c): ?>
<option value="<?= (int)$c['CENTRO_ID'] ?>" <?= (int)($pr['CENTRO_ID'] ?? 0) === (int)$c['CENTRO_ID'] ? 'selected' : '' ?>><?= htmlspecialchars($c['NOMBRE']) ?></option><?php endforeach; ?></select></div>
<div class="form-group"><label>Nombre *</label><input name="nombre" class="form-control" value="<?= htmlspecialchars($pr['NOMBRE']) ?>" required></div>
<div class="form-group"><label>Nivel *</label><input name="nivel_formacion" class="form-control" value="<?= htmlspecialchars($pr['NIVEL_FORMACION']) ?>" required></div>
<div class="form-group"><label>Duración *</label><input type="number" name="duracion_meses" class="form-control" value="<?= (int)$pr['DURACION_MESES'] ?>" required></div>
<div class="form-group"><label>Estado</label><select name="estado" class="form-control"><option value="Activo" <?= ($pr['ESTADO'] ?? '') === 'Activo' ? 'selected' : '' ?>>Activo</option><option value="Inactivo" <?= ($pr['ESTADO'] ?? '') === 'Inactivo' ? 'selected' : '' ?>>Inactivo</option></select></div>
<button type="submit" class="btn-action">Guardar</button></form></div></div></main>
<?php include __DIR__ . '/../../config/footer.php'; ?>
<script src="../../js/tema.js"></script><script src="../../js/loader.js"></script><script src="../../js/profile_menu.js"></script><script src="../../js/menu.js"></script>
<script>document.getElementById('f').onsubmit=function(e){e.preventDefault();fetch('guardar_programa.php',{method:'POST',body:new FormData(this)}).then(r=>r.json()).then(d=>{if(d.success)location.href='../programa_detalle.php?id=<?= $id ?>';else Swal.fire({icon:'error',text:d.message||''});});};</script></body></html>
