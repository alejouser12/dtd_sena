<?php
session_start();
require_once __DIR__ . '/../../config/auth.php';
if (!esAdmin()) { header('Location: ../programas.php'); exit; }
require_once __DIR__ . '/../../conexion/conexion.php';
class X extends BaseDatos { protected function consultar(){} protected function insertar(){} protected function actualizar(){} protected function eliminar(){} }
$x = new X();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0) $x->ejecutarPreparado('DELETE FROM ficha WHERE FICHA_ID = :id', [':id' => $id]);
header('Location: ../programas.php');
exit;
