<?php
session_start();
require_once __DIR__ . '/../../config/auth.php';
if (!esAdmin()) { header('Location: ../aprendices.php'); exit; }
require_once __DIR__ . '/../../conexion/AprendizDAO.php';
require_once __DIR__ . '/../../conexion/FichaDAO.php';
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: ../aprendices.php'); exit; }
$ap = (new AprendizDAO())->obtenerPorId($id);
if (!$ap) { header('Location: ../aprendices.php'); exit; }
$fichas = (new FichaDAO())->obtenerTodas();
?><!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Editar aprendiz</title><link rel="stylesheet" href="../../css/style.css"><script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script></head>
<body><div id="loader"><img src="../../img/logo_sena_verde.png" alt="" id="loader-logo"></div>
<?php include __DIR__ . '/../../config/header.php'; ?>
<main class="container" id="contenido-principal" style="display:none;opacity:0">
<a href="../aprendiz_detalle.php?id=<?= $id ?>" class="btn-view-all">Volver</a>
<div class="content-card" style="margin-top:20px"><div class="card-header"><h2>Editar aprendiz</h2></div><div class="card-body">
<form id="f"><input type="hidden" name="id" value="<?= $id ?>">
<div class="form-group"><label>Tipo documento</label><select name="tipo_documento" class="form-control"><?php foreach (['CC','TI','CE'] as $t): ?>
<option value="<?= $t ?>" <?= ($ap['TIPO_DOCUMENTO'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option><?php endforeach; ?></select></div>
<div class="form-group"><label>Número *</label><input name="numero_documento" class="form-control" value="<?= htmlspecialchars($ap['NUMERO_DOCUMENTO']) ?>" required></div>
<div class="form-group"><label>Nombres *</label><input name="nombres" class="form-control" value="<?= htmlspecialchars($ap['NOMBRES']) ?>" required></div>
<div class="form-group"><label>Apellidos *</label><input name="apellidos" class="form-control" value="<?= htmlspecialchars($ap['APELLIDOS']) ?>" required></div>
<div class="form-group"><label>Email *</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($ap['EMAIL']) ?>" required></div>
<div class="form-group"><label>Teléfono</label><input name="telefono" class="form-control" value="<?= htmlspecialchars($ap['TELEFONO'] ?? '') ?>"></div>
<div class="form-group"><label>Fecha nacimiento</label><input type="date" name="fecha_nacimiento" class="form-control" value="<?= htmlspecialchars(substr($ap['FECHA_NACIMIENTO'] ?? '', 0, 10)) ?>"></div>
<div class="form-group"><label>Género</label><input name="genero" class="form-control" value="<?= htmlspecialchars($ap['GENERO'] ?? '') ?>"></div>
<div class="form-group"><label>Estado académico</label><input name="estado_academico" class="form-control" value="<?= htmlspecialchars($ap['ESTADO_ACADEMICO'] ?? 'Activo') ?>"></div>
<div class="form-group"><label>Ficha</label><select name="ficha_id" class="form-control"><option value="">Sin ficha</option><?php foreach ($fichas as $fi): ?>
<option value="<?= (int)$fi['FICHA_ID'] ?>" <?= (int)($ap['FICHA_ID'] ?? 0) === (int)$fi['FICHA_ID'] ? 'selected' : '' ?>><?= htmlspecialchars($fi['CODIGO_FICHA']) ?></option><?php endforeach; ?></select></div>
<button type="submit" class="btn-action">Guardar</button></form></div></div></main>
<?php include __DIR__ . '/../../config/footer.php'; ?>
<script src="../../js/tema.js"></script><script src="../../js/loader.js"></script><script src="../../js/profile_menu.js"></script><script src="../../js/menu.js"></script>
<script>document.getElementById('f').onsubmit=function(e){e.preventDefault();fetch('guardar_aprendiz.php',{method:'POST',body:new FormData(this)}).then(r=>r.json()).then(d=>{if(d.success)location.href='../aprendiz_detalle.php?id=<?= $id ?>';else Swal.fire({icon:'error',text:d.message||''});});};</script></body></html>
