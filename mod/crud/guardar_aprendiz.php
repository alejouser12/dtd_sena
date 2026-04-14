<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/auth.php';
if (!esAdmin()) { echo json_encode(['success' => false]); exit; }
require_once __DIR__ . '/../../conexion/conexion.php';
class A extends BaseDatos { protected function consultar(){} protected function insertar(){} protected function actualizar(){} protected function eliminar(){} }
$a = new A();
$td = $_POST['tipo_documento'] ?? 'CC';
$nd = trim($_POST['numero_documento'] ?? '');
$nom = trim($_POST['nombres'] ?? '');
$ap = trim($_POST['apellidos'] ?? '');
$em = trim($_POST['email'] ?? '');
$tel = trim($_POST['telefono'] ?? '');
$fn = $_POST['fecha_nacimiento'] ?? null;
$gen = $_POST['genero'] ?? null;
$ea = $_POST['estado_academico'] ?? 'Activo';
$fid = !empty($_POST['ficha_id']) ? (int)$_POST['ficha_id'] : null;
if ($nd === '' || $nom === '' || $ap === '' || $em === '') { echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios']); exit; }
if (!empty($_POST['id'])) {
    $sql = 'UPDATE aprendiz SET TIPO_DOCUMENTO=:td,NUMERO_DOCUMENTO=:nd,NOMBRES=:n,APELLIDOS=:a,EMAIL=:e,TELEFONO=:t,FECHA_NACIMIENTO=:fn,GENERO=:g,ESTADO_ACADEMICO=:ea,FICHA_ID=:f WHERE APRENDIZ_ID=:id';
    $ok = $a->ejecutarPreparado($sql, [':id' => (int)$_POST['id'], ':td' => $td, ':nd' => $nd, ':n' => $nom, ':a' => $ap, ':e' => $em, ':t' => $tel ?: null, ':fn' => $fn ?: null, ':g' => $gen ?: null, ':ea' => $ea, ':f' => $fid]);
    echo json_encode(['success' => (bool)$ok]);
} else {
    $sql = 'INSERT INTO aprendiz (TIPO_DOCUMENTO,NUMERO_DOCUMENTO,NOMBRES,APELLIDOS,EMAIL,TELEFONO,FECHA_NACIMIENTO,GENERO,ESTADO_ACADEMICO,FICHA_ID) VALUES (:td,:nd,:n,:a,:e,:t,:fn,:g,:ea,:f)';
    $ok = $a->ejecutarPreparado($sql, [':td' => $td, ':nd' => $nd, ':n' => $nom, ':a' => $ap, ':e' => $em, ':t' => $tel ?: null, ':fn' => $fn ?: null, ':g' => $gen ?: null, ':ea' => $ea, ':f' => $fid]);
    echo json_encode(['success' => (bool)$ok, 'id' => $ok ? (int)$a->obtenerUltimoId() : 0]);
}
