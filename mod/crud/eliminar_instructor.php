<?php
session_start();
require_once __DIR__ . '/../../config/auth.php';
if (!esAdmin()) { header('Location: ../instructores.php'); exit; }
require_once __DIR__ . '/../../conexion/conexion.php';
class X extends BaseDatos { protected function consultar(){} protected function insertar(){} protected function actualizar(){} protected function eliminar(){} }
$x = new X(); $id = (int)($_GET['id'] ?? 0);
if ($id > 0) { $x->ejecutarPreparado('DELETE FROM instructor_ficha WHERE INSTRUCTOR_ID=:id', [':id' => $id]); $x->ejecutarPreparado('DELETE FROM instructor WHERE INSTRUCTOR_ID=:id', [':id' => $id]); }
header('Location: ../instructores.php');
exit;
