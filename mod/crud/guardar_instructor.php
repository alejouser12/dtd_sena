<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/auth.php';
if (!esAdmin()) { echo json_encode(['success' => false]); exit; }
require_once __DIR__ . '/../../conexion/conexion.php';
class I extends BaseDatos { protected function consultar(){} protected function insertar(){} protected function actualizar(){} protected function eliminar(){} }
$i = new I();
$n = trim($_POST['nombres'] ?? ''); $a = trim($_POST['apellidos'] ?? ''); $e = trim($_POST['email'] ?? ''); $esp = trim($_POST['especialidad'] ?? '');
if ($n === '' || $a === '' || $e === '') { echo json_encode(['success' => false, 'message' => 'Complete nombres, apellidos y email']); exit; }
if (!empty($_POST['id'])) {
    $ok = $i->ejecutarPreparado('UPDATE instructor SET NOMBRES=:n,APELLIDOS=:a,EMAIL=:e,ESPECIALIDAD=:s WHERE INSTRUCTOR_ID=:id',
        [':id' => (int)$_POST['id'], ':n' => $n, ':a' => $a, ':e' => $e, ':s' => $esp ?: null]);
    echo json_encode(['success' => (bool)$ok]);
} else {
    $ok = $i->ejecutarPreparado('INSERT INTO instructor (NOMBRES,APELLIDOS,EMAIL,ESPECIALIDAD) VALUES (:n,:a,:e,:s)', [':n' => $n, ':a' => $a, ':e' => $e, ':s' => $esp ?: null]);
    echo json_encode(['success' => (bool)$ok, 'id' => $ok ? (int)$i->obtenerUltimoId() : 0]);
}
