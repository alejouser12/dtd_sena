<?php
session_start();
require_once __DIR__ . '/../../config/auth.php';
if (!esAdmin()) {
    header('Location: ../evidencias.php');
    exit;
}
require_once __DIR__ . '/../../conexion/EvidenciaDAO.php';
require_once __DIR__ . '/../../conexion/FichaDAO.php';
$fichas = (new FichaDAO())->obtenerTodas();
?><!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Nueva evidencia</title><link rel="stylesheet" href="../../css/style.css"></head>
<body><div id="loader"><img src="../../img/logo_sena_verde.png" alt="" id="loader-logo"></div>
<?php include __DIR__ . '/../../config/header.php'; ?>
<main class="container" id="contenido-principal" style="display:none;opacity:0">
<a href="../evidencias.php" class="btn-view-all">Volver</a>
<div class="content-card" style="margin-top:20px"><div class="card-header"><h2>Nueva evidencia</h2></div><div class="card-body">
<form method="POST" action="guardar_evidencia.php">
<div class="form-group"><label>Nombre *</label><input name="nombre" class="form-control" required></div>
<div class="form-group"><label>Tipo *</label><input name="tipo_evidencia" class="form-control" required></div>
<div class="form-group"><label>Porcentaje</label><input type="number" step="0.01" name="porcentaje" class="form-control" value="10"></div>
<div class="form-group"><label>Fecha *</label><input type="date" name="fecha_evidencia" class="form-control" required></div>
<div class="form-group"><label>Tiempo entrega</label><input name="tiempo_entrega" class="form-control" placeholder="7 días"></div>
<div class="form-group"><label>Ficha *</label><select name="ficha_id" class="form-control" required><?php foreach ($fichas as $fi): ?>
<option value="<?= (int)$fi['FICHA_ID'] ?>"><?= htmlspecialchars($fi['CODIGO_FICHA']) ?></option><?php endforeach; ?></select></div>
<button type="submit" class="btn-action">Guardar</button></form></div></div></main>
<?php include __DIR__ . '/../../config/footer.php'; ?>
<script src="../../js/tema.js"></script><script src="../../js/loader.js"></script><script src="../../js/profile_menu.js"></script><script src="../../js/menu.js"></script></body></html>
