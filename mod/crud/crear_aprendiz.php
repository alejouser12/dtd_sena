<?php
session_start();
require_once __DIR__ . '/../../config/auth.php';
if (!esAdmin()) { header('Location: ../aprendices.php'); exit; }
require_once __DIR__ . '/../../conexion/FichaDAO.php';
$fichas = (new FichaDAO())->obtenerTodas();
?><!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Nuevo aprendiz</title><link rel="stylesheet" href="../../css/style.css"><script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script></head>
<body><div id="loader"><img src="../../img/logo_sena_verde.png" alt="" id="loader-logo"></div>
<?php include __DIR__ . '/../../config/header.php'; ?>
<main class="container" id="contenido-principal" style="display:none;opacity:0">
<a href="../aprendices.php" class="btn-view-all">Volver</a>
<div class="content-card" style="margin-top:20px"><div class="card-header"><h2>Nuevo aprendiz</h2></div><div class="card-body">
<form id="f">
<div class="form-group"><label>Tipo documento</label><select name="tipo_documento" class="form-control"><option>CC</option><option>TI</option><option>CE</option></select></div>
<div class="form-group"><label>Número *</label><input name="numero_documento" class="form-control" required></div>
<div class="form-group"><label>Nombres *</label><input name="nombres" class="form-control" required></div>
<div class="form-group"><label>Apellidos *</label><input name="apellidos" class="form-control" required></div>
<div class="form-group"><label>Email *</label><input type="email" name="email" class="form-control" required></div>
<div class="form-group"><label>Teléfono</label><input name="telefono" class="form-control"></div>
<div class="form-group"><label>Fecha nacimiento</label><input type="date" name="fecha_nacimiento" class="form-control"></div>
<div class="form-group"><label>Género</label><select name="genero" class="form-control"><option value="">—</option><option>M</option><option>F</option><option>Otro</option></select></div>
<div class="form-group"><label>Estado académico</label><select name="estado_academico" class="form-control"><option>Activo</option><option>Inactivo</option></select></div>
<div class="form-group"><label>Ficha</label><select name="ficha_id" class="form-control"><option value="">Sin ficha</option><?php foreach ($fichas as $fi): ?>
<option value="<?= (int)$fi['FICHA_ID'] ?>"><?= htmlspecialchars($fi['CODIGO_FICHA']) ?></option><?php endforeach; ?></select></div>
<button type="submit" class="btn-action">Guardar</button></form></div></div></main>
<?php include __DIR__ . '/../../config/footer.php'; ?>
<script src="../../js/tema.js"></script><script src="../../js/loader.js"></script><script src="../../js/profile_menu.js"></script><script src="../../js/menu.js"></script>
<script>document.getElementById('f').onsubmit=function(e){e.preventDefault();fetch('guardar_aprendiz.php',{method:'POST',body:new FormData(this)}).then(r=>r.json()).then(d=>{if(d.success)location.href='../aprendiz_detalle.php?id='+d.id;else Swal.fire({icon:'error',text:d.message||''});});};</script></body></html>
