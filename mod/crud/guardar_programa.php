<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/auth.php';
if (!esAdmin()) { echo json_encode(['success' => false]); exit; }
require_once __DIR__ . '/../../conexion/conexion.php';
class P extends BaseDatos { protected function consultar(){} protected function insertar(){} protected function actualizar(){} protected function eliminar(){} }
$p = new P();
$cid = (int)($_POST['centro_id'] ?? 0);
$nom = trim($_POST['nombre'] ?? '');
$niv = trim($_POST['nivel_formacion'] ?? '');
$dur = (int)($_POST['duracion_meses'] ?? 0);
$est = $_POST['estado'] ?? 'Activo';
if ($cid <= 0 || $nom === '' || $niv === '' || $dur <= 0) { echo json_encode(['success' => false, 'message' => 'Complete los campos']); exit; }
if (!empty($_POST['id'])) {
    $ok = $p->ejecutarPreparado('UPDATE programa SET CENTRO_ID=:c,NOMBRE=:n,NIVEL_FORMACION=:nv,DURACION_MESES=:d,ESTADO=:e WHERE PROGRAMA_ID=:id',
        [':id' => (int)$_POST['id'], ':c' => $cid, ':n' => $nom, ':nv' => $niv, ':d' => $dur, ':e' => $est]);
    echo json_encode(['success' => (bool)$ok]);
} else {
    $ok = $p->ejecutarPreparado('INSERT INTO programa (CENTRO_ID,NOMBRE,NIVEL_FORMACION,DURACION_MESES,ESTADO) VALUES (:c,:n,:nv,:d,:e)',
        [':c' => $cid, ':n' => $nom, ':nv' => $niv, ':d' => $dur, ':e' => $est]);
    echo json_encode(['success' => (bool)$ok, 'id' => $ok ? (int)$p->obtenerUltimoId() : 0]);
}
