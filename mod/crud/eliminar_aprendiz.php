<?php
session_start();
require_once __DIR__ . '/../../config/auth.php';
if (!esAdmin()) { header('Location: ../aprendices.php'); exit; }
require_once __DIR__ . '/../../conexion/AprendizDAO.php';
$id = (int)($_GET['id'] ?? 0);
if ($id > 0) (new AprendizDAO())->eliminarAprendizForzado($id);
header('Location: ../aprendices.php');
exit;
