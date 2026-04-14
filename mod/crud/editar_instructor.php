<?php
session_start();
require_once __DIR__ . '/../../config/auth.php';
if (!esAdmin()) { header('Location: ../instructores.php'); exit; }
require_once __DIR__ . '/../../conexion/instructorDAO.php';
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: ../instructores.php'); exit; }
$ins = (new InstructorDAO())->obtenerPorId($id);
if (!$ins) { header('Location: ../instructores.php'); exit; }
?><!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Editar instructor</title><link rel="stylesheet" href="../../css/style.css"><script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script></head>
<body><div id="loader"><img src="../../img/logo_sena_verde.png" alt="" id="loader-logo"></div>
<?php include __DIR__ . '/../../config/header.php'; ?>
<main class="container" id="contenido-principal" style="display:none;opacity:0">
<a href="../instructor_detalle.php?id=<?= $id ?>" class="btn-view-all">Volver</a>
<div class="content-card" style="margin-top:20px"><div class="card-header"><h2>Editar instructor</h2></div><div class="card-body">
<form id="f"><input type="hidden" name="id" value="<?= $id ?>">
<div class="form-group"><label>Nombres *</label><input name="nombres" class="form-control" value="<?= htmlspecialchars($ins['NOMBRES']) ?>" required></div>
<div class="form-group"><label>Apellidos *</label><input name="apellidos" class="form-control" value="<?= htmlspecialchars($ins['APELLIDOS']) ?>" required></div>
<div class="form-group"><label>Email *</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($ins['EMAIL']) ?>" required></div>
<div class="form-group"><label>Especialidad</label><input name="especialidad" class="form-control" value="<?= htmlspecialchars($ins['ESPECIALIDAD'] ?? '') ?>"></div>
<button type="submit" class="btn-action">Guardar</button></form></div></div></main>
<?php include __DIR__ . '/../../config/footer.php'; ?>
<script src="../../js/tema.js"></script><script src="../../js/loader.js"></script><script src="../../js/profile_menu.js"></script><script src="../../js/menu.js"></script>
<script>document.getElementById('f').onsubmit=function(e){e.preventDefault();fetch('guardar_instructor.php',{method:'POST',body:new FormData(this)}).then(r=>r.json()).then(d=>{if(d.success)location.href='../instructor_detalle.php?id=<?= $id ?>';else Swal.fire({icon:'error',text:d.message||''});});};</script></body></html>
