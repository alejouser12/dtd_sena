<?php
session_start();
require_once __DIR__ . '/../../config/auth.php';
if (!esAdmin()) { header('Location: ../evidencias.php'); exit; }
require_once __DIR__ . '/../../conexion/EvidenciaDAO.php';
$id = (int)($_GET['id'] ?? 0);
if ($id > 0) (new EvidenciaDAO())->eliminarEvidencia($id);
header('Location: ../evidencias.php');
exit;
